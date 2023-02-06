<?php
namespace alis\tbr\app\init;

// die if called directly
if(!function_exists('add_action')){
	echo 'No sweetie...';
	exit;
}

//** CUSTOM POST TYPES ***********************************************//
function book(){
	$labels = array(
		'name' => 'Books',
		'singular_name' => 'Book',
		'menu_name' => 'Books',
		'name_admin_bar' => 'Books',
		'add_new' => 'Add New Book',
		'add_new_item' => 'Add New Book',
		'new_item' => 'New Book',
		'edit_item' => 'Edit Book',
		'view_item' => 'View Book',
		'all_items' => 'All Books',
		'search_items' => 'Search Books',
		'parent_item_colon' => 'Parent Book:',
		'not_found' => 'No books found.',
		'not_found_in_trash' => 'No books found in Trash.'
	);
	
	$args = array(
		'public' 						=> true,
		'labels' 						=> $labels,
		'description' 			=> 'Books read, or to read, or not read.',
		'show_ui'						=> true,
		'show_in_menu'			=> true,
		'menu_icon'					=> 'dashicons-book-alt',
		//'capability_type'		=> 'book',
		'supports'					=> array('thumbnail'),
		'taxonomies'				=> array('post_tag'),
		'has_archive'				=> 'library',
		'delete_with_user'	=> true
	);
	\register_post_type('book', $args);
}

function book_update(){
	$labels = array(
		'name' => 'Updates',
		'singular_name' => 'Update',
		'menu_name' => 'Updates',
		'name_admin_bar' => 'Updates',
		'add_new' => 'Add New Book Update',
		'add_new_item' => 'Add New Book Update',
		'new_item' => 'New Update',
		'edit_item' => 'Edit Update',
		'view_item' => 'View Update',
		'all_items' => 'All Book Updates',
		'search_items' => 'Search Book Updates',
		'parent_item_colon' => 'Parent Book:',
		'not_found' => 'No updates found.',
		'not_found_in_trash' => 'No updates found in Trash.'
	);
	
	$args = array(
		'public' 						=> true,
		'labels' 						=> $labels,
		'description' 			=> 'Book status updates.',
		'show_ui'						=> false,
		'show_in_menu'			=> false,
		//'menu-icon'					=> 'dashicons-book-alt',
		//'capability_type'		=> 'book',
		'supports'					=> array('thumbnail'),
		'has_archive'				=> false,
		'delete_with_user'	=> true
	);
	\register_post_type('book_update', $args);
}

//** EDIT PAGE META BOXES ********************************************//
function book_details(){
	add_meta_box(
				'book-details',
				__('Book Details'),
				'\alis\tbr\app\meta\book_details',
				'book'
		);
	
	add_meta_box(
				'book-update-add',
				__('Add update'),
				'\alis\tbr\app\meta\book_update_add',
				'book'
		);
	
	add_meta_box(
				'book-updates',
				__('Reading status updates'),
				'\alis\tbr\app\meta\book_updates',
				'book'
		);
}

//** CUSTOM POST TYPE STATUSES ***************************************//
function wishlist(){
	$args = array(
		'label'				=> 'Wishlist',
		//'label_count'	=> 'Items',
		// 'exclude_from_search'	=> true,
		'public'			=> true,
		'internal'		=> false,
		'protected'		=> false,
		'private'			=> false,
	);
	\register_post_status('wishlist', $args);
}

function tbr(){
	$args = array(
		'label'				=> 'Up next',
		//'label_count'	=> 'Items',
		// 'exclude_from_search'	=> true,
		'public'			=> true,
		'internal'		=> false,
		'protected'		=> false,
		'private'			=> false,
	);
	\register_post_status('tbr', $args);
}

function reading(){
	$args = array(
		'label'				=> 'In progress',
		//'label_count'	=> 'Items',
		// 'exclude_from_search'	=> true,
		'public'			=> true,
		'internal'		=> false,
		'protected'		=> false,
		'private'			=> false,
	);
	\register_post_status('reading', $args);
}

function read(){
	$args = array(
		'label'				=> 'Completed',
		//'label_count'	=> 'Items',
		// 'exclude_from_search'	=> true,
		'public'			=> true,
		'internal'		=> false,
		'protected'		=> false,
		'private'			=> false,
	);
	\register_post_status('read', $args);
}

function dnf(){
	$args = array(
		'label'				=> 'Unfinished',
		//'label_count'	=> 'Items',
		// 'exclude_from_search'	=> true,
		'public'			=> false,
		'internal'		=> false,
		'protected'		=> false,
		'private'			=> false,
	);
	\register_post_status('dnf', $args);
}
?>