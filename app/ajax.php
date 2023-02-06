<?php
namespace alis\tbr\app\ajax;

// die if called directly
if(!function_exists('add_action')){
	echo 'No sweetie...';
	exit;
}

//== MAGIC... ========================================================//
function book_lookup() {
	// do stuff here
	$isbn = intval(preg_replace("/[^0-9]/", '', $_POST['isbn']));
	
	$str = file_get_contents('https://www.googleapis.com/books/v1/volumes?q=isbn:' . $isbn);
	//$str = file_get_contents('https://openlibrary.org/isbn/'. $isbn .'.json');
	$r = json_decode($str);
	
	// TODO: some kind of ui for when we get multiple return values for this?
	// in theory there should only ever be one record against an isbn but
	// google books's data are really... messy
	if(is_object($r) && $r->totalItems > 0){
		//print_r($r->items[0]);
		
		$book = array(
			'title'			=> $r->items[0]->volumeInfo->title,
			'authors'		=> $r->items[0]->volumeInfo->authors,
			'published'	=> $r->items[0]->volumeInfo->publishedDate,
			'pages'			=> $r->items[0]->volumeInfo->pageCount
		);
		
		//wp_send_json($book);
		echo json_encode($book);
	} else { echo json_encode(false); }
	
	// apparently we die?
	wp_die();
}

//== SCRIPTS =========================================================//
function admin_scripts() { ?>
	<script type="text/javascript" >
		jQuery(document).ready(function($) {
		
				$('#book-lookup').click(function(){
						var data = {
								action: 'book_lookup',
								isbn: $("#book-isbn").val()
						};

						$.post(ajaxurl, data, function(response) {
							var book = JSON.parse(response);
							
							if(book){
								$('#book-title').attr('value', book.title);
								$('#book-author').attr('value', book.authors.toString());
								$('#book-date').attr('value', book.published);
								$('#book-length').attr('value', book.pages);
								
								$('#book-lookup').hide();
							} else {
								alert('ISBN not found.');
							}
						});
				});
		});
		</script> <?php
}
?>