/**
 * Init: enterMediaDb brickvertical (jquery-brickvertical,js)
 * Init: enterMediaDb simpleLightbox (jquery-simpleLightbox.js)
 */
(function ($) {
	jQuery(document).ready(function () {
	    var grid = $(".masonry-grid2");
		grid.brickvertical();
		
		$('.lightbox').simpleLightbox();
	});
}(jQuery));	
