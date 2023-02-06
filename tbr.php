<?php
/*
Plugin Name: Mt. TBR
Plugin URI: https://alis.me/
Description: Book tracking and review thingie.
Version: 0.1
Author: Alis
Author URI: http://alis.me/
*/
namespace alis\tbr;

// die if called directly
if(!function_exists('add_action')){
	echo 'No sweetie...';
	exit;
}


// includes
include(plugin_dir_path( __FILE__ ) .'/app/init.php');
include(plugin_dir_path( __FILE__ ) .'/app/meta.php');
include(plugin_dir_path( __FILE__ ) .'/app/save.php');
include(plugin_dir_path( __FILE__ ) .'/app/ajax.php');
include(plugin_dir_path( __FILE__ ) .'/app/admin.php');

// custom post types
add_action('init', '\alis\tbr\app\init\book');
add_action('init', '\alis\tbr\app\init\book_update');

// custom meta boxes
add_action('add_meta_boxes', '\alis\tbr\app\init\book_details');

// saving meta
add_action('save_post_book', '\alis\tbr\app\save\book_details');
add_action('save_post_book', '\alis\tbr\app\save\book_update_add');
add_filter('wp_insert_post_data', '\alis\tbr\app\save\book_insert', 10, 3);

// ajax stuff
add_action('wp_ajax_book_lookup', '\alis\tbr\app\ajax\book_lookup');
add_action('admin_footer', '\alis\tbr\app\ajax\admin_scripts');

// admin pages 
add_action('admin_menu', '\alis\tbr\app\admin\admin_menus');

// custom statuses
add_action('init', '\alis\tbr\app\init\wishlist');
add_action('init', '\alis\tbr\app\init\tbr');
add_action('init', '\alis\tbr\app\init\reading');
add_action('init', '\alis\tbr\app\init\read');
add_action('init', '\alis\tbr\app\init\dnf');

?>