<?php
namespace alis\tbr\app\admin;

// die if called directly
if(!function_exists('add_action')){
	echo 'No sweetie...';
	exit;
}

//= ADD SUBMENUS ===============================================================//
function admin_menus() {
	$hookname = add_submenu_page( 
		'edit.php?post_type=book',
		'Import Books',
		'Import Books',
		'manage_options',
		'book-import',
		'\alis\tbr\app\admin\import_page'
	);
	add_action('load-' . $hookname, '\alis\tbr\app\admin\do_pre_import');
}

// ... this is a huge big mess that needs neatening up
function import_page() {
	// check user capabilities
	if(!current_user_can('manage_options')){ return; }
	
	if(array_key_exists('step', $_GET) && $_GET['step'] == 'do'){
		$r = do_import();
		
		echo '<div class="wrap"><h1>'. esc_html(get_admin_page_title()) .'</h1>';
		if($r && is_array($r) && array_key_exists('legend', $r) && array_key_exists('data', $r)){
			echo '<li>';
			foreach($r['data'] as $book){
				if(array_key_exists('Title', $book)){
					//print_r($book);
					$title = str_replace('||', ',', $book['Title']) .' ('. $book['Author'] .')';
					$post_id = false;
					// the wordpress post
					$status = 'wishlist';
					switch($book['Exclusive Shelf']){
						case 'currently-reading':
							$status = 'reading';
							break;
						case 'up-next':
							$status = 'tbr';
							break;
						case 'reference':
						case 'read':
							$status = 'read';
							break;
						case 'to-read':
							$status = 'wishlist';
							break;
						case 'on-hold':
							$status = 'dnf';
							break;
					}
					$post = array(
						'post_date' => date('Y-m-d H:i:s', strtotime($book['Date Added'])),
						'post_title' => $title,
						'post_status' => $status,
						'post_type' => 'book',
						'tags_input' => explode('||', str_replace('"', '', $book['Bookshelves']))
					);
					$post_id = \wp_insert_post($post);
					echo '<li>Added book <em>'. $title .'</em>.</li>';
					//echo '<pre>'; print_r($post); echo '</pre>'; 
					
					// the metadata for the wordpress post
					if($post_id){
						$meta = array(
							'isbn'			=> sanitize_text_field(preg_replace('/[^0-9]/', '', $book['ISBN13'])),
							'title'			=> sanitize_text_field(str_replace('||', ',', $book['Title'])),
							'author'		=> sanitize_text_field($book['Author']),
							'publisher'	=> sanitize_text_field($book['Publisher']),
							'date'			=> sanitize_text_field($book['Year Published']),
							'length'		=> sanitize_text_field($book['Number of Pages']),
						);
						//print_r($meta);
						\update_post_meta($post_id, '_book_meta', $meta);
						
						// have to re-do the post to update the title... ugh
						$updated = \wp_update_post(array('ID' => $post_id, 'post_title' => $title));
						
						// the review post (if we have one)
						if($book['My Review']){
							$review = array(
								'post_date' => date('Y-m-d H:i:s', strtotime($book['Date Added'])),
								'post_title' => 'GoodReads review: '. $title,
								'post_status' => 'published',
								'post_type' => 'book-update',
								'post_parent'		=> $post_id,
								'post_content'	=> substr(str_replace('||', ',', $book['My Review']), 1, -1)
							);
							//print_r($review);
							$review_id = \wp_insert_post($review);
							
							// review post status
							if($review_id){
								$smeta = array(
									'status'		=> $status,
									'progress'	=> 0,
								);
								//print_r($smeta);
								\update_post_meta($review_id, '_update_meta', $smeta);
							}
						}
					}
				}
			}
			echo '</ol>';
			echo '<p>Done!</p><p><a href="'. menu_page_url('book-import', false) .'">Go back</a>.</p>';
		} else {
			echo '<p>Something went wrong, sorry...</p><p><a href="'. menu_page_url('book-import', false) .'">Go back</a>.</p>';
		}
		echo '</div>';
	} else {
?>
<div class="wrap">
	<h1><?php echo esc_html(get_admin_page_title()); ?></h1>
	<p>The following will allow you to upload a <code>.csv</code> <a href="https://help.goodreads.com/s/article/How-do-I-import-or-export-my-books-1553870934590">export from GoodReads</a> to import 
	into WordPress</p>
	
	<form action="<?php menu_page_url('book-import'); ?>&step=do" method="post" enctype="multipart/form-data">
		<?php
		// TODO: these properly...
		settings_fields('tbr_options');
		do_settings_sections('tbr');
		echo '<input type="file" id="gr_csv" name="gr_csv">';
		submit_button('Do Import');
		?>
	</form>
</div>
<?php
	}
}

//= ACTUALLY DO STUFF I GUESS ==================================================//
function do_pre_import(){
	if(!current_user_can('manage_options')){ return; }
	set_time_limit(0);
	return;
}

function do_import(){
	// check user capabilities
	if(!current_user_can('manage_options')){ return; }

	if(isset($_FILES) && array_key_exists('gr_csv', $_FILES) && $_FILES['gr_csv']['type'] == 'text/csv') {
		$str = file_get_contents($_FILES['gr_csv']['tmp_name']);
		if($str){
			$data = array();
			$lines = explode("\n", $str);
			foreach($lines as $l) {
				if(array_key_exists('legend', $data)){
					// ... lol so messy
					// https://stackoverflow.com/questions/38300941/replace-comma-between-quotes-in-csv-with-regex
					$l = preg_replace('/,(?!(?:[^"]*"[^"]*")*[^"]*$)/', '||', $l);
					$tmp = explode(',', $l);
					$index = array();
					foreach($tmp as $k => $v){
						$name = $data['legend'][$k];
						$index[$name] = $v;
					}
					$data['data'][] = $index;
				} else {
					$data['legend'] = explode(',', $l);
				}
			}
			return $data;
		}
	}
	
	return false;
}

?>