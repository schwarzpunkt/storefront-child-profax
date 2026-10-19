// Menü-Schalter im Header auf dem Handy, wie auf www.profaxonline.com
( function() {
	var toggle = document.querySelector( '.profax-site-header a.icon' );
	if ( ! toggle ) {
		return;
	}
	toggle.addEventListener( 'click', function( event ) {
		event.preventDefault();
		var open = document.getElementById( 'menu1' ).classList.toggle( 'responsive' );
		toggle.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
	} );
} )();
