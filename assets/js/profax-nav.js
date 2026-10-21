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

// Cookie-Hinweis schliessen: Session-Cookie wie auf www.profaxonline.com. Unter *.profax.com gilt es für die
// ganze Domain, damit es auch die Hauptseite sieht, sobald sie auf www.profax.com läuft.
( function() {
	var banner = document.getElementById( 'cookieconsent' );
	if ( ! banner ) {
		return;
	}
	banner.querySelector( '.cookiecClose' ).addEventListener( 'click', function() {
		var domain = /(^|\.)profax\.com$/.test( location.hostname ) ? '; domain=profax.com' : '';
		document.cookie = 'cookieconsent=1; path=/; SameSite=Lax' + domain;
		banner.remove();
	} );
} )();
