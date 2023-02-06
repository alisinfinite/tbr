<?php
namespace alis\tbr\app\meta;

// die if called directly
if(!function_exists('add_action')){
	echo 'No sweetie...';
	exit;
}

function book_details($post){
	
	$value = \get_post_meta($post->ID, '_book_meta', true);
	$current = \get_post_meta($post->ID, '_book_current', true);
	
	// authors can be single strings or arrays of multiple people
	$author = '';
	if(is_array($value) && array_key_exists('author', $value) && is_array($value['author']))
		{ $author = implode(', ', $value['author']); }
	elseif(is_array($value) && array_key_exists('author', $value))
		{ $author = $value['author']; }
	// display the editing form
	if($post->post_status == 'draft' || $post->post_status == 'auto-draft' || !$post->post_status){
		\wp_nonce_field('book_details_nonce', 'book_details_nonce');
?>
<table class="form-table" role="presentation"><tbody>
<tr>
	<th scope="row">
		<label for="book-isbn">ISBN:</label>
	</th><td>
		<input type="text" minlength="10" maxlength="17" id="book-isbn" name="book[isbn]" value="<?php _e($value, 'isbn'); ?>">
		<?php if(empty($value['title'])) { echo '<input type="button" class="button" id="book-lookup" value="Lookup">'; } ?>
	</td>
</tr>

<tr>
	<th scope="row">
		<label for="book-isbn">Title:</label>
	</th><td>
		<input type="text" id="book-title" name="book[title]" value="<?php _e($value, 'title'); ?>" style="width: 100%;">
	</td>
</tr>

<tr>
	<th scope="row">
		<label for="book-author">Author(s):</label>
	</th><td>
		<input type="text" id="book-author" name="book[author]" value="<?php _e($author); ?>" style="width: 100%;">
	</td>
</tr>

<tr>
	<th scope="row">
		<label for="book-publisher">Publisher:</label>
	</th><td>
		<input type="text" id="book-publisher" name="book[publisher]" value="<?php _e($value, 'publisher'); ?>" style="width: 100%;">
	</td>
</tr>

<tr>
	<th scope="row">
		<label for="book-date">Date published:</label>
	</th><td>
		<input type="text" id="book-date" name="book[date]" value="<?php _e($value, 'date'); ?>">
	</td>
</tr>

<tr>
	<th scope="row">
		<label for="book-length">Length:</label>
	</th><td>
		<input type="text" id="book-length" name="book[length]" value="<?php _e($value, 'length'); ?>">
		<p class="description">In pages, hours and minutes, etc.</p>
	</td>
</tr>
</tbody></table>

<?php	} else {
	
		echo '<h3>'; _e($value, 'title'); echo '</h3>';
		echo '<h4>'; _e($author); echo '</h4>';
		
		if(is_array($value) && array_key_exists('rec', $value) && $value['rec'] == 'true'){
			echo '<p>⭐️ Recommended.</p>';
		}
		
		if(is_array($current)){
			switch($current['status']){
				case 'wishlist':
					echo '<p>On wishlist.</p>';
					break;
				case 'tbr':
					echo '<p>Up next.</p>';
					break;
				case 'reading':
					echo '<p>Currently reading.</p>';
					break;
				case 'read':
					echo '<p>Finished reading.</p>';
					break;
				case 'dnf':
					echo '<p>Did not finish.</p>';
					break;
			}
		}
		
		echo '<ul>';
		if($value['isbn']) { echo '<li>ISBN: '; _e($value, 'isbn'); echo '</li>'; }
		if($value['publisher']) { echo '<li>Publisher: '; _e($value, 'publisher'); echo '</li>'; }
		if($value['date']) { echo '<li>Published on: '; _e($value, 'date'); echo '</li>'; }
		if($value['length']) { echo '<li>Length: '; _e($value, 'length'); echo '</li>'; }
		echo '</ul>';
	}
}

function book_updates($post) {
	\wp_nonce_field('book_updates_nonce', 'book_updates_nonce');
	
	// get children
	$args = array(
		'posts_per_page' => -1,
		'order'          => 'DESC',
		'post_parent'    => $post->ID,
		'post_type'			 => array('post', 'book-update')
	);
	$updates = get_children($args);
	
	foreach($updates as $u){
		$date = wp_date(get_option('date_format'), strtotime($u->post_date));
		$time = wp_date(get_option('time_format'), strtotime($u->post_date));
		
		$meta = \get_post_meta($u->ID, '_update_meta', true);
		$status = \get_post_status_object($meta['status']);
		
		$complete = ($meta['status'] == 'reading' && $meta['progress']) ? ' ('. $meta['progress'] .' complete)' : '';
		
		$content = ($u->post_excerpt) ? $u->post_excerpt : $u->post_content;
		
		$edit = ($u->post_type == 'post') ? '<a href="/wp-admin/post.php?post='. $u->ID .'&action=edit" style="font-size:80%; border:0; text-decoration: none;">✏️</a> ' : '';
?>
		<article id="post-<?php echo $u->ID; ?>">
			<h4><?php echo $edit . $date .' @ '. $time; ?>: <?php echo $status->label . $complete; ?></h4>
			<?php if($u->post_content){ echo apply_filters('the_content', $content); } ?>
		</article>
		<hr>
<?php
	}
}

function book_update_add($post) {
	\wp_nonce_field('book_update_add_nonce', 'book_update_add_nonce');
	
	$book = \get_post_meta($post->ID, '_book_meta', true);
	$current = \get_post_meta($post->ID, '_book_current', true);
	
	$stati = array(
		'wishlist'	=> 'Want to read',
		'tbr'				=> 'Up next',
		'reading'		=> 'Currently reading',
		'read'			=> 'Complete',
		'dnf'				=> 'Did not finish'
	);
?>
<table class="form-table" role="presentation"><tbody>
<tr>
	<th scope="row">
		<label for="book-status">Status:</label>
	</th><td>
		<select id="book-status" name="book[status]">
<?php
	foreach($stati as $k => $v){
		$selected = (is_array($current) && array_key_exists('status', $current) && $current['status'] == $k) ? ' selected' : '';
		echo "			<option value='$k'$selected>$v</option>";
	}
?>
		</select>
	</td>
</tr>

<?php if(is_array($current) && array_key_exists('status', $current) && $current['status'] != 'read' && $current['status'] != 'dnf'): ?>
<tr>
	<th scope="row">
		<label for="book-progress">Progress:</label>
	</th><td>
		<input type="text" id="book-progress" name="book[progress]" value="<?php _e($current, 'progress'); ?>">
		<p class="description">Current page number, percent complete, minute, etc.</p>
	</td>
</tr>
<?php
endif;
	echo '</tbody></table>';

	\wp_editor('', 'book-update', array('media_buttons' => false, 'textarea_rows' => 5));
	
	$rchecked = (is_array($book) && array_key_exists('rec', $book) && $book['rec'] == 'true') ? ' checked' : '';
	echo '<p><label for="book-rec"><input type="checkbox" id="book-rec" name="book[rec]" value="true"'. $rchecked .'> Recommended.</label></p>';
	echo '<p><label for="book-update-published"><input type="checkbox" id="book-update-published" name="book[update-published]" value="true"> Publish update in blog?</label></p>';
}

// internal helper functions
function _e($a, $v = null){
	if(is_array($a) && array_key_exists($v, $a) && $a[$v])
		{ echo sanitize_text_field($a[$v]); }
	else
		{ echo sanitize_text_field($a); }
}
?>