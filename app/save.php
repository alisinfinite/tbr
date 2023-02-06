<?php
namespace alis\tbr\app\save;

// die if called directly
if(!function_exists('add_action')){
	echo 'No sweetie...';
	exit;
}

function book_details($post_id){
	if(!isset($_POST['book_details_nonce'])) { return; }
	if(!\wp_verify_nonce($_POST['book_details_nonce'], 'book_details_nonce')) { return; }

	$book = array(
		'isbn'			=> sanitize_text_field($_POST['book']['isbn']),
		'title'			=> sanitize_text_field($_POST['book']['title']),
		'author'		=> sanitize_text_field($_POST['book']['author']),
		'publisher'	=> sanitize_text_field($_POST['book']['publisher']),
		'date'			=> sanitize_text_field($_POST['book']['date']),
		'length'		=> sanitize_text_field($_POST['book']['length']),
	);

	// set the metadata values
	\update_post_meta($post_id, '_book_meta', $book);
}

function book_insert($data, $postarr, $unsanitized_postarr){
	// only for books pls ma'am
	if($data['post_type'] != 'book') { return $data; }
	
	// give our book a proper title
	$data = book_title($data, $postarr);

	return $data;
}

function book_title($data, $postarr){
	// only for books pls ma'am
	if($data['post_type'] != 'book') { return $data; }
	
	$book = \get_post_meta($postarr['ID'], '_book_meta', true);
	
	// first try and get values from our post meta
	if(is_array($book) && array_key_exists('title', $book)){
		$data['post_title'] = $book['title'] ? $book['title'] : 'untitled';
		
		if(array_key_exists('author', $book) && $book['author'])
			{ $data['post_title'] .= ' ('. $book['author'] .')'; }
		
		$data['post_name'] = \sanitize_title($data['post_title']);
	}
	
	// then see if there's form data
	elseif(array_key_exists('book', $_POST) && is_array($_POST['book'])){
		$data['post_title'] = $_POST['book']['title'] ? $_POST['book']['title'] : 'untitled';
		
		if(array_key_exists('author', $_POST['book']) && $_POST['book']['author'])
			{ $data['post_title'] .= ' ('. $_POST['book']['author'] .')'; }
		
		$data['post_name'] = \sanitize_title($data['post_title']);
	}
	
	// finally... unknown
	else {
		$data['post_title'] = '(new book)';
		$data['post_name'] = (array_key_exists('ID', $data) && is_array($data)) ? \sanitize_title($data['ID']) : '';
	}
	
	return $data;
}

function book_status($data){
	$current = \get_post_meta($postarr['ID'], '_book_current', true);
	\wp_mail('me@alis.me', 'book status', print_r($current, true));
	if(is_array($current) && array_key_exists('status', $current) && $current['status'] != $data['post_status']){
		$data['post_status'] = $current['status'];
	}
	
	return $data;
}

function book_update_add($post_id){
	if(!isset($_POST['book_update_add_nonce'])) { return; }
	if(!\wp_verify_nonce($_POST['book_update_add_nonce'], 'book_update_add_nonce')) { return; }
	
	$book = \get_post_meta($post_id, '_book_meta', true);
	
	$old = \get_post_meta($post_id, '_book_current', true);
	$new = array(
		'status'						=> sanitize_text_field($_POST['book']['status']),
		'progress'					=> sanitize_text_field($_POST['book']['progress']),
	);
	// set the metadata values
	\update_post_meta($post_id, '_book_current', $new);
	
	// are we recommending this book?
	if((is_array($book) && array_key_exists('rec', $book) && $book['rec'] != $_POST['book']['rec'])
		|| (is_array($book) && !array_key_exists('rec', $book) && $_POST['book']['rec'])){
		$book['rec'] = $_POST['book']['rec'] == 'true' ? 'true' : 'false';
		\update_post_meta($post_id, '_book_meta', $book);
	}

	// if we have update text, or our status or progress has changed, post an update
	if($_POST['book-update'] || $old['status'] != $new['status'] || $old['progress'] != $new['progress']){
		
		// build ye title
		$title = 'Update for';
		switch($new['status']){
			case 'wishlist':
				$title = "Added to wishlist";
				break;
			case 'tbr':
				$title = "Up next";
				break;
			case 'reading':
				$title = "Currently reading";
				break;
			case 'read':
				$title = "Finished reading";
				break;
			case 'dnf':
				$title = "Did not finish";
				break;
		}
		$title = ($book['title']) ? $title .': '. $book['title'] : $title;
		
		// post statuses base on whether we want to publish this or nah
		$post_status	= 'published';
		$post_type		= 'book-update';
		$tags_input		= '';
		if($_POST['book']['update-published']){
			
			// populate some tags for blog posts
			$otags = \wp_get_post_tags($post_id);
			$tags_input = array('books', 'books-read');
			foreach($otags as $t){ $tags_input[] = $t->slug; }
			
			// recommended tag?
			if($_POST['book']['rec'] == 'true') { $tags_input[] = 'recommended'; }
			
			$post_status	= 'draft';
			$post_type		= 'post';
		}
		
		// populate new post data
		$post = array(
			'post_content'	=> $_POST['book-update'],
			'post_title'		=> $title,
			'post_status'		=> $post_status,
			'post_type'			=> $post_type,
			'post_parent'		=> $post_id,
			'tags_input'		=> $tags_input,
		);
		
		$statusID = \wp_insert_post($post);
		// also save some book metadata at time of update
		if($statusID){
			$smeta = array(
				'status'		=> $new['status'],
				'progress'	=> $new['progress'],
			);
			\update_post_meta($statusID, '_update_meta', $smeta);
		}
	}
	
	// now transition the status of our parent post
	// (we need to unhook here so we don't get into an infinite loop of this function)
	// (also always need to do this or wordpress likes to default the post status to "published")
	$status = $new['status'] ? $new['status'] : $old['status'];
	
	if($status){
		remove_action('save_post_book', '\alis\tbr\app\save\book_update_add');
		wp_update_post(array('ID' => $post_id, 'post_status' => $new['status']));
		add_action('save_post_book', '\alis\tbr\app\save\book_update_add');
	}
}

?>