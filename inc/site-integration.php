<?php
/**
 * Integration in profax.com: Assets, Farben, Menüs, Header, Footer, Warenkorb-Link, Kategorie-Navigation.
 * @package storefront-child-profax
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Kein direkter Aufruf
}

add_action( 'wp_enqueue_scripts', 'profax_enqueue_assets', 20 );
function profax_enqueue_assets() {
	// zimi ersetzt die Google-Schrift von Storefront
	wp_dequeue_style( 'storefront-fonts' );
	wp_enqueue_style( 'profax-site', get_stylesheet_directory_uri() . '/assets/css/profax-site.css', array( 'storefront-style' ), PROFAX_THEME_VERSION );
	wp_enqueue_script( 'profax-nav', get_stylesheet_directory_uri() . '/assets/js/profax-nav.js', array(), PROFAX_THEME_VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) );
}

// style.css enthält nur den Theme-Header, alle Styles stehen in profax-site.css.
// Storefront lädt die style.css des Child-Themes mit Priorität 30.
add_action( 'wp_enqueue_scripts', 'profax_dequeue_child_style', 40 );
function profax_dequeue_child_style() {
	wp_dequeue_style( 'storefront-child-style' );
}

// Farben aus dem Storefront-Customizer, fest im Code, damit sie zur Hauptseite passen.
// Priorität 20: Storefront setzt seine Standardwerte mit 10.
add_action( 'after_setup_theme', 'profax_theme_mod_colors' );
function profax_theme_mod_colors() {
	$colors = array(
		'storefront_header_background_color'     => '#50A8FF',
		'storefront_header_text_color'           => '#FFFFFF',
		'storefront_header_link_color'           => '#FFFFFF',
		'storefront_footer_background_color'     => '#50A8FF',
		'storefront_footer_heading_color'        => '#FFFFFF',
		'storefront_footer_text_color'           => '#FFFFFF',
		'storefront_footer_link_color'           => '#FFFFFF',
		'storefront_text_color'                  => '#000000',
		'storefront_heading_color'               => '#000000',
		'storefront_accent_color'                => '#3088E0',
		'storefront_button_background_color'     => '#1088FF',
		'storefront_button_text_color'           => '#FFFFFF',
		'storefront_button_alt_background_color' => '#1088FF',
		'storefront_button_alt_text_color'       => '#FFFFFF',
		'background_color'                       => 'F8F8F8',
	);
	foreach ( $colors as $key => $value ) {
		add_filter( 'theme_mod_' . $key, function() use ( $value ) {
			return $value;
		}, 20 );
	}
}

add_action( 'after_setup_theme', 'profax_register_menus' );
function profax_register_menus() {
	register_nav_menus( array(
		'profax_header_main'  => __( 'Header: Hauptnavigation', 'storefront-child-profax' ),
		'profax_header_login' => __( 'Header: Login', 'storefront-child-profax' ),
		'profax_footer_help'  => __( 'Footer: Hilfe', 'storefront-child-profax' ),
		'profax_footer_about' => __( 'Footer: Über profax', 'storefront-child-profax' ),
	) );
}

// Einträge der obersten Ebene des Menüs an $location, [] wenn keines zugewiesen ist
function profax_menu_items( $location ) {
	$locations = get_nav_menu_locations();
	if ( empty( $locations[ $location ] ) ) {
		return array();
	}
	$items = wp_get_nav_menu_items( $locations[ $location ] );
	if ( ! $items ) {
		return array();
	}
	return array_values( array_filter( $items, function( $item ) {
		return 0 === (int) $item->menu_item_parent;
	} ) );
}

// Links auf diesen Shop werden auf jeder Shop-Seite als aktiv markiert
function profax_is_shop_link( $url ) {
	$host = wp_parse_url( $url, PHP_URL_HOST );
	return $host && $host === wp_parse_url( home_url(), PHP_URL_HOST );
}

function profax_menu_link( $item ) {
	$classes = array_filter( (array) $item->classes );
	if ( profax_is_shop_link( $item->url ) ) {
		$classes[] = 'profax-current';
	}
	$attrs = ' href="' . esc_url( $item->url ) . '"';
	if ( $classes ) {
		$attrs .= ' class="' . esc_attr( implode( ' ', $classes ) ) . '"';
	}
	if ( $item->target ) {
		$attrs .= ' target="' . esc_attr( $item->target ) . '"';
	}
	if ( $item->xfn ) {
		$attrs .= ' rel="' . esc_attr( $item->xfn ) . '"';
	}
	return '<a' . $attrs . '>' . esc_html( $item->title ) . '</a>';
}

// zimiicons hat kein Warenkorb-Symbol, deshalb ist es ein Inline-SVG im gleichen flächigen Stil
function profax_cart_icon() {
	return '<svg class="profax-cart__icon" viewBox="0 0 24 24" width="1em" height="1em" fill="currentColor" aria-hidden="true" focusable="false"><path d="M1 2h3.3l.8 3H23l-2.7 9.3a1 1 0 0 1-1 .7H8.4l.5 2H20v2H8.1a1 1 0 0 1-1-.8L3.7 4H1z"/><circle cx="9.5" cy="21.2" r="1.8"/><circle cx="18.5" cy="21.2" r="1.8"/></svg>';
}

function profax_cart_link() {
	if ( ! function_exists( 'WC' ) ) {
		return '';
	}
	$count = WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
	$html  = '<a class="profax-cart" href="' . esc_url( wc_get_cart_url() ) . '">' . profax_cart_icon();
	if ( $count > 0 ) {
		$html .= '<span class="profax-cart__count">' . (int) $count . '</span>';
	}
	return $html . '<span class="profax-cart__label">' . esc_html__( 'Warenkorb', 'storefront-child-profax' ) . '</span></a>';
}

add_filter( 'woocommerce_add_to_cart_fragments', 'profax_cart_link_fragment' );
function profax_cart_link_fragment( $fragments ) {
	$fragments['a.profax-cart'] = profax_cart_link();
	return $fragments;
}

// WooCommerce cacht die Fragmente im sessionStorage, bis sich der Warenkorb-Hash ändert.
// Bei Änderungen am Markup von profax_cart_link() die Version erhöhen, damit alte Fragmente verworfen werden.
add_filter( 'woocommerce_cart_hash', 'profax_cart_hash_version' );
function profax_cart_hash_version( $hash ) {
	return '' === $hash ? $hash : md5( $hash . 'profax-cart-3' );
}

// Skip-Links von Storefront, „Zur Navigation springen“ aber nur, wo die Kategorie-Navigation erscheint
function profax_skip_links() {
	if ( profax_show_shop_nav() ) {
		echo '<a class="skip-link screen-reader-text" href="#site-navigation">' . esc_html__( 'Skip to navigation', 'storefront' ) . '</a>';
	}
	echo '<a class="skip-link screen-reader-text" href="#content">' . esc_html__( 'Skip to content', 'storefront' ) . '</a>';
}

function profax_site_header() {
	profax_skip_links();
	?>
	<header id="header" class="profax-site-header">
		<nav id="menu1" class="topnav">
			<div class="subLog">
				<?php
				foreach ( profax_menu_items( 'profax_header_login' ) as $item ) {
					echo profax_menu_link( $item );
				}
				echo profax_cart_link();
				?>
			</div>
			<div class="subNav">
				<?php
				foreach ( profax_menu_items( 'profax_header_main' ) as $item ) {
					echo profax_menu_link( $item );
				}
				?>
			</div>
			<a class="home" href="<?php echo esc_url( PROFAX_MAIN_URL ); ?>">profax</a>
			<a class="icon" href="#" aria-label="<?php esc_attr_e( 'Menü', 'storefront-child-profax' ); ?>" aria-expanded="false" aria-controls="menu1">&#xf0c9;</a>
		</nav>
	</header>
	<?php
}

// Name des Menüs an $location, dient als Spaltenüberschrift im Footer
function profax_menu_title( $location ) {
	$locations = get_nav_menu_locations();
	if ( empty( $locations[ $location ] ) ) {
		return '';
	}
	$menu = wp_get_nav_menu_object( $locations[ $location ] );
	return $menu ? $menu->name : '';
}

function profax_site_footer() {
	?>
	<footer class="profax-site-footer">
		<h3><a class="home" href="<?php echo esc_url( PROFAX_MAIN_URL ); ?>">profaxonline</a></h3>
		<nav class="fboxS">
			<?php foreach ( array( 'profax_footer_help', 'profax_footer_about' ) as $location ) : ?>
				<div class="f2">
					<?php if ( $title = profax_menu_title( $location ) ) : ?>
						<h4><?php echo esc_html( $title ); ?></h4>
					<?php endif; ?>
					<?php if ( $items = profax_menu_items( $location ) ) : ?>
						<ul>
							<?php foreach ( $items as $item ) : ?>
								<li><?php echo profax_menu_link( $item ); ?></li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</nav>
	</footer>
	<?php
}

// Kategorie-Navigation nur auf der Startseite und auf WooCommerce-Seiten (Shop, Kategorien, Schlagwörter,
// Produkte, Produktsuche, Konto); nicht im Bestellprozess (Warenkorb, Kasse), auf Inhaltsseiten, im Blog und
// in der allgemeinen Suche. Das Suchfeld (Header-Widget-Bereich) bleibt auf jeder Seite.
function profax_show_shop_nav() {
	if ( ! has_nav_menu( 'primary' ) ) {
		return false;
	}
	if ( is_front_page() ) {
		return true;
	}
	return function_exists( 'is_woocommerce' ) && is_woocommerce() && ! is_cart() && ! is_checkout();
}

// Produktkategorien (primäres Storefront-Menü) als Shop-Navigation unter dem Suchfeld (Priorität 10)
add_action( 'storefront_before_content', 'profax_shop_nav', 15 );
function profax_shop_nav() {
	if ( ! profax_show_shop_nav() ) {
		return;
	}
	echo '<nav id="site-navigation" class="profax-shop-nav" aria-label="' . esc_attr__( 'Produktkategorien', 'storefront-child-profax' ) . '">';
	echo '<span class="profax-filter-label">' . esc_html__( 'Produktkategorien:', 'storefront-child-profax' ) . '</span>';
	wp_nav_menu( array(
		'theme_location' => 'primary',
		'container'      => false,
		'depth'          => 1,
		'fallback_cb'    => false,
	) );
	echo '</nav>';
}
