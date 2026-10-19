<?php
/**
 * functions.php des Storefront-Child-Themes für den profax Shop.
 * @package storefront-child-profax
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Kein direkter Aufruf
}

// 100 Produkte pro Seite anzeigen
add_filter( 'loop_shop_per_page', function( $cols ) {
	return 100;
}, 20 );

// Private Produkte erscheinen nicht in Shop, Kategorien und Produktsuche, auch nicht für eingeloggte
// Nutzer, die sie lesen dürfen (Administratoren, Shop-Manager); WordPress würde sie sonst einblenden
add_action( 'woocommerce_product_query', function( $q ) {
	$q->set( 'post_status', 'publish' );
} );

add_action( 'init', 'storefront_custom_logo' );
function storefront_custom_logo() {
	remove_action('storefront_before_content', 'woocommerce_breadcrumb', 10);

	// Vorschaubilder umschliessen, damit sie zentriert werden können
	add_action( 'woocommerce_before_shop_loop_item_title', function() {
		echo '<div class="thumbnail_wrapper">';
	}, 9 );
	add_action( 'woocommerce_before_shop_loop_item_title', function() {
		echo '</div>';
	}, 11 );

	// Katalog-Bildgrösse 300 px beibehalten, bis die Vorschaubilder neu erzeugt sind
	add_filter( 'single_product_archive_thumbnail_size', function() {
		return 'shop_catalog';
	} );



	// Metadaten ans Ende des Beitrags verschieben
	remove_action('storefront_post_header_before', 'storefront_post_meta', 10);
	add_action('storefront_single_post', 'storefront_post_meta', 31);
	add_action('storefront_loop_post', 'storefront_post_meta', 31);

	// Eigene Metadaten für Produkte
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40 );
	add_action( 'woocommerce_single_product_summary', 'custom_woocommerce_template_single_meta', 40 );

	// „Ähnliche Produkte“ entfernen
	remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 );

	// Blättern zum vorherigen/nächsten Produkt entfernen
	remove_action( 'woocommerce_after_single_product_summary', 'storefront_single_product_pagination', 30 );

	// Eigener Hinweis auf das profax Lerngerät
	add_action( 'woocommerce_after_single_product_summary', 'custom_woocommerce_after_single_product_summary_pfg', 12 );
	add_filter( 'woocommerce_product_tabs', 'profax_remove_product_tabs', 98 );
	function profax_remove_product_tabs( $tabs ) {
		//unset( $tabs['description'] );      	// Tab „Beschreibung“ entfernen
		//unset( $tabs['reviews'] ); 			// Tab „Bewertungen“ entfernen
		unset( $tabs['additional_information'] );  	// Tab „Zusätzliche Informationen“ entfernen

		return $tabs;

	}
}

function custom_woocommerce_template_single_meta() {
	global $post, $product;

	$cats = get_the_terms( $post->ID, 'product_cat' );
	$tags = get_the_terms( $post->ID, 'product_tag' );
	$cat_count = is_array( $cats ) ? count( $cats ) : 0;
	$tag_count = is_array( $tags ) ? count( $tags ) : 0;
	$sku = ($sku = $product->get_sku()) ? $sku : __('N/A', 'woocommerce');
	$book = FALSE;
	if (stristr($sku, 'ISBN'))
		$book = TRUE;

	echo '<div class="product_meta">';
	do_action( 'woocommerce_product_meta_start' );
	if ( wc_product_sku_enabled() && ( $product->get_sku() || $product->is_type( 'variable' ))) {
		echo '<span class="sku_wrapper">';
		if ($book) {
			echo 'ISBN-13: <span class="sku">'.esc_html(substr($sku, 5)).'</span></span>';
		} else {
			_e('SKU:', 'woocommerce');
			echo ' <span class="sku">'.esc_html($sku).'</span></span>';
		}
	}

	$p_sort = array('pa_language', 'pa_zyklus', 'pa_klasse', 'pa_alter', 'pa_autor', 'pa_illustration', 'pa_design', 'pa_programing', 'pa_wissenschaftliche-beratung');
	$attrs = $product->get_attributes();
	//foreach ($attrs as $attr => $attribute) {
	foreach ($p_sort as $attr) {
		if (isset($attrs[$attr])) {
			$attribute = $attrs[$attr];
			$taxonomy = get_taxonomy( $attribute->get_name() );
			$attribute_string = '';
			if ( $taxonomy && ! is_wp_error( $taxonomy ) ) {
				$terms = wp_get_post_terms( $post->ID, $taxonomy->name );

				if ( !empty( $terms ) ) {
					foreach ( $terms as $term ) {
						if (strlen($attribute_string) > 0) {
							$attribute_string .= ', ';
						}
						$archive_link = get_term_link( $term );
						if ( is_wp_error( $archive_link ) ) {
							$attribute_string .= esc_html( $term->name );
							continue;
						}
						$attribute_string .= '<a href="' . esc_url( $archive_link ) . '">' . esc_html( $term->name ) . '</a>';
					}
				}
			}
			switch ($attr) {
				case 'pa_autor':
					echo '<span class="'.$attr.'" style="display: block;">Autor: '.$attribute_string.'</span>';
					break;
				case 'pa_illustration':
					echo '<span class="'.$attr.'" style="display: block;">Illustration: '.$attribute_string.'</span>';
					break;
				case 'pa_programing':
					echo '<span class="'.$attr.'" style="display: block;">Programmierung: '.$attribute_string.'</span>';
					break;
				case 'pa_wissenschaftliche-beratung':
					echo '<span class="'.$attr.'" style="display: block;">Wissenschaftliche Beratung: '.$attribute_string.'</span>';
					break;
				case 'pa_design':
					echo '<span class="'.$attr.'" style="display: block;">Gestaltung: '.$attribute_string.'</span>';
					break;
				case 'pa_alter':
					echo '<span class="'.$attr.'" style="display: block;" >Alter: <span>'.strip_tags($attribute_string).'</span></span>';
					break;
				case 'pa_klasse':
					echo '<span class="'.$attr.'" style="display: block;">Klasse: '.$attribute_string.'</span>';
					break;
				case 'pa_zyklus':
					echo '<span class="'.$attr.'" style="display: block;">Zyklus: '.$attribute_string.'</span>';
					break;
				case 'pa_language':
					echo '<span class="'.$attr.'" style="display: block;" >Sprache: '.$attribute_string.'</span>';
					break;
			}
		}
	}

	echo wc_get_product_category_list( $product->get_id(), ', ', '<span class="posted_in">' . _n( 'Category:', 'Categories:', $cat_count, 'woocommerce' ) . ' ', '</span>' );
	echo wc_get_product_tag_list( $product->get_id(), ', ', '<span class="tagged_as">' . _n( 'Tag:', 'Tags:', $tag_count, 'woocommerce' ) . ' ', '</span>' );
	do_action( 'woocommerce_product_meta_end' );
	echo '</div>';

}

// schema.org-Book-Daten im Produkt-JSON-LD von WooCommerce ergänzen, für Produkte mit ISBN als Artikelnummer
add_filter( 'woocommerce_structured_data_product', 'profax_structured_data_book', 10, 2 );
function profax_structured_data_book( $markup, $product ) {
	$sku = $product->get_sku();
	if ( ! stristr( $sku, 'ISBN' ) ) {
		return $markup;
	}

	$markup['isbn'] = substr( $sku, 5 );

	$attrs = $product->get_attributes();
	$terms = function( $taxonomy, $field = 'names' ) use ( $attrs, $product ) {
		return isset( $attrs[ $taxonomy ] ) ? wc_get_product_terms( $product->get_id(), $taxonomy, array( 'fields' => $field ) ) : array();
	};

	$people = array(
		'author'      => array( 'pa_autor', 'pa_programing', 'pa_wissenschaftliche-beratung' ),
		'illustrator' => array( 'pa_illustration' ),
	);
	foreach ( $people as $prop => $taxonomies ) {
		foreach ( $taxonomies as $taxonomy ) {
			foreach ( $terms( $taxonomy ) as $name ) {
				$markup[ $prop ][] = array( '@type' => 'Person', 'name' => $name );
			}
		}
	}

	if ( $languages = $terms( 'pa_language', 'slugs' ) ) {
		$markup['inLanguage'] = $languages;
	}
	if ( $ages = $terms( 'pa_alter' ) ) {
		$markup['typicalAgeRange'] = implode( ', ', $ages );
	}

	return $markup;
}

// Buch-Produkte als Product + Book auszeichnen. WooCommerce gruppiert strukturierte Daten nach
// einem einzelnen @type-String, deshalb wird der Typ erst beim Zusammensetzen der Ausgabe erweitert.
add_filter( 'woocommerce_structured_data_context', 'profax_structured_data_book_type', 10, 4 );
function profax_structured_data_book_type( $context, $data, $type, $value ) {
	if ( 'product' === $type && is_array( $value ) && 1 === count( $value ) && isset( $value[0]['isbn'] ) ) {
		$context['@type'] = array( 'Product', 'Book' );
	}
	return $context;
}

function custom_woocommerce_after_single_product_summary_pfg() {
	global $post, $product;

	if (has_term('lernhefte-fuer-profaxli-lerngeraet', 'product_cat', $product->get_id())) {
		$link = esc_url(get_permalink(33)); // Produkt "profaxli Lerngerät"
		$img = wp_get_attachment_image(1320, 'thumbnail', false, array('alt' => 'profax Lerngerät'));
		echo '<div class="profax-promo profax-promo--profaxli">
<a class="profax-promo__image" href="'.$link.'">'.$img.'</a>
<h3>benötigt das <a href="'.$link.'">profaxli Lerngerät</a></h3><br />
Das <a href="'.$link.'">profaxli Lerngerät</a> und die Logo-Hefte 1-8 bilden zusammen ein Lernsystem, das ideal auf die Schule vorbereitet. Spielerisch an den Voraussetzungen für Mathe und Lesen arbeiten, aber ohne Zahlen und Buchstaben.
		</div>';
	} else if (has_term('lernhefte-fuer-profax-lerngeraet', 'product_cat', $product->get_id())) {
		$link = esc_url(get_permalink(89)); // Produkt "profax Lerngerät"
		$img = wp_get_attachment_image(1988, 'medium', false, array('alt' => 'profax Lerngerät'));
		echo '<div class="profax-promo profax-promo--profax">
<a class="profax-promo__image" href="'.$link.'">'.$img.'</a>
<h3>benötigt das <a href="'.$link.'">profax Lerngerät</a></h3><br />
Das <a href="'.$link.'">profax Lerngerät</a> mit Sofortrückmeldung und Kontrollblatt ist zusammen mit den Lehrmitteln zur Rechtschreibung, zum Textverständnis, zur Mathe und zu andern Themen ein wirksames Lernsystem. Es eignet sich für alle Formen von selbstständigem Lernen: Werkstattunterricht, offener Unterricht, Förderunterricht, Nachhilfe, usw.
		</div>';
	}
}

// Integration in profax.com: Schriften, Farben, Header und Footer wie auf www.profaxonline.com
define( 'PROFAX_THEME_VERSION', wp_get_theme()->get( 'Version' ) );
// Basis-URL der Hauptseite; anpassen, wenn profaxonline.com nach profax.com umzieht
define( 'PROFAX_MAIN_URL', 'https://www.profaxonline.com/dech/' );

add_action( 'wp_enqueue_scripts', 'profax_enqueue_assets', 20 );
function profax_enqueue_assets() {
	// zimi ersetzt die Google-Schrift von Storefront
	wp_dequeue_style( 'storefront-fonts' );
	wp_enqueue_style( 'profax-site', get_stylesheet_directory_uri() . '/assets/css/profax-site.css', array( 'storefront-child-style' ), PROFAX_THEME_VERSION );
	wp_enqueue_script( 'profax-nav', get_stylesheet_directory_uri() . '/assets/js/profax-nav.js', array(), PROFAX_THEME_VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) );
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
	$html  = '<a class="profax-cart" href="' . esc_url( wc_get_cart_url() ) . '" aria-label="' . esc_attr__( 'Warenkorb', 'storefront-child-profax' ) . '">' . profax_cart_icon();
	if ( $count > 0 ) {
		$html .= '<span class="profax-cart__count">' . (int) $count . '</span>';
	}
	return $html . '</a>';
}

add_filter( 'woocommerce_add_to_cart_fragments', 'profax_cart_link_fragment' );
function profax_cart_link_fragment( $fragments ) {
	$fragments['a.profax-cart'] = profax_cart_link();
	return $fragments;
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
// Produkte, Produktsuche, Warenkorb, Kasse, Konto); nicht auf Inhaltsseiten, im Blog und in der allgemeinen
// Suche. Das Suchfeld (Header-Widget-Bereich) bleibt auf jeder Seite.
function profax_show_shop_nav() {
	if ( ! has_nav_menu( 'primary' ) ) {
		return false;
	}
	if ( is_front_page() ) {
		return true;
	}
	return function_exists( 'is_woocommerce' ) && ( is_woocommerce() || is_cart() || is_checkout() || is_account_page() );
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

/*
 * Startseite (front-page.php): Geräte-Karten, Klassen-Pills, beliebte Lernhefte, Aktuelles.
 * Produkte und Kategorien werden über den Slug gesucht, die IDs unterscheiden sich lokal und live.
 */

function profax_front_devices() {
	return array(
		array( 'product' => 'profax-lerngeraet', 'category' => 'lernhefte-fuer-profax-lerngeraet' ),
		array( 'product' => 'profaxli-lerngeraet', 'category' => 'lernhefte-fuer-profaxli-lerngeraet' ),
	);
}

// Eine Karte pro Gerät; fehlt das Produkt, fällt die Karte weg, fehlt die Kategorie, nur ihr Link
function profax_front_device_cards( $devices ) {
	if ( ! function_exists( 'wc_get_product' ) ) {
		return;
	}
	$cards = '';
	foreach ( $devices as $device ) {
		$post    = get_page_by_path( $device['product'], OBJECT, 'product' );
		$product = $post && 'publish' === $post->post_status ? wc_get_product( $post ) : null;
		if ( ! $product ) {
			continue;
		}
		$url   = get_permalink( $product->get_id() );
		$term  = get_term_by( 'slug', $device['category'], 'product_cat' );
		$cards .= '<article class="profax-device">';
		$cards .= '<a class="profax-device__image" href="' . esc_url( $url ) . '" tabindex="-1" aria-hidden="true">' . $product->get_image( 'shop_catalog' ) . '</a>';
		$cards .= '<h2 class="profax-device__title"><a href="' . esc_url( $url ) . '">' . esc_html( $product->get_name() ) . '</a></h2>';
		if ( $text = wp_strip_all_tags( $product->get_short_description() ) ) {
			$cards .= '<p>' . esc_html( $text ) . '</p>';
		}
		$cards .= '<p class="profax-device__links"><a class="button" href="' . esc_url( $url ) . '">' . esc_html__( 'Gerät ansehen', 'storefront-child-profax' ) . '</a>';
		if ( $term && ! is_wp_error( $link = get_term_link( $term ) ) ) {
			$cards .= ' <a class="profax-device__more" href="' . esc_url( $link ) . '">' . esc_html__( 'Lernhefte ansehen', 'storefront-child-profax' ) . '</a>';
		}
		$cards .= '</p></article>';
	}
	if ( $cards ) {
		echo '<section class="profax-front-devices">' . $cards . '</section>';
	}
}

// Im Katalog sichtbare Produkte (veröffentlicht, Katalog-Sichtbarkeit, is_visible) mit ihren Kategorien
// (inkl. übergeordneter, nach Term-ID) und „Klasse“-Werten (nach Slug); einmal pro Seitenaufruf aufgebaut
function profax_catalog_index() {
	static $index = null;
	if ( null !== $index ) {
		return $index;
	}
	$index = array( 'ids' => array(), 'cats' => array(), 'klassen' => array() );
	if ( ! function_exists( 'wc_get_products' ) ) {
		return $index;
	}
	$ids = wc_get_products( array( 'status' => 'publish', 'visibility' => 'catalog', 'limit' => -1, 'return' => 'ids' ) );
	update_object_term_cache( $ids, 'product' );
	foreach ( $ids as $id ) {
		$product = wc_get_product( $id );
		if ( ! $product || ! $product->is_visible() ) {
			continue;
		}
		$index['ids'][] = $id;
		foreach ( $product->get_category_ids() as $cat_id ) {
			foreach ( array_merge( array( $cat_id ), get_ancestors( $cat_id, 'product_cat' ) ) as $c ) {
				$index['cats'][ $c ][] = $id;
			}
		}
		foreach ( wc_get_product_terms( $id, 'pa_klasse', array( 'fields' => 'slugs' ) ) as $slug ) {
			$index['klassen'][ $slug ][] = $id;
		}
	}
	return $index;
}

// „Klasse“-Werte der angegebenen Produkte: Vorschule, 1–9 (numerisch), übrige Werte, IF zuletzt
function profax_klasse_terms( $ids ) {
	$terms = array();
	foreach ( profax_catalog_index()['klassen'] as $slug => $klasse_ids ) {
		if ( array_intersect( $klasse_ids, $ids ) && ( $term = get_term_by( 'slug', (string) $slug, 'pa_klasse' ) ) ) {
			$terms[] = $term;
		}
	}
	$rank = function( $term ) {
		if ( 'vorschule' === $term->slug ) {
			return array( 0, 0, '' );
		}
		if ( 'if' === $term->slug ) {
			return array( 3, 0, '' );
		}
		return is_numeric( $term->slug ) ? array( 1, (int) $term->slug, '' ) : array( 2, 0, $term->name );
	};
	usort( $terms, function( $a, $b ) use ( $rank ) {
		return $rank( $a ) <=> $rank( $b );
	} );
	return $terms;
}

function profax_klasse_label( $term ) {
	if ( is_numeric( $term->slug ) ) {
		/* translators: %d: Schulklasse */
		return sprintf( __( '%d. Klasse', 'storefront-child-profax' ), (int) $term->slug );
	}
	return 'if' === $term->slug ? 'IF' : $term->name;
}

// Pills, die auf den nach Klasse gefilterten Shop verlinken (WooCommerce-Filter, ohne Attribut-Archive)
function profax_front_klassen() {
	if ( ! function_exists( 'wc_get_page_id' ) || ! ( $terms = profax_klasse_terms( profax_catalog_index()['ids'] ) ) ) {
		return;
	}
	$shop = get_permalink( wc_get_page_id( 'shop' ) );
	echo '<section class="profax-front-klassen"><h2>' . esc_html__( 'Lernhefte nach Klasse', 'storefront-child-profax' ) . '</h2><ul class="profax-pills">';
	foreach ( $terms as $term ) {
		echo '<li><a href="' . esc_url( add_query_arg( 'filter_klasse', $term->slug, $shop ) ) . '">' . esc_html( profax_klasse_label( $term ) ) . profax_count_html( profax_klasse_count( $term->slug, profax_catalog_index()['ids'] ) ) . '</a></li>';
	}
	echo '</ul></section>';
}

// Zufällige Auswahl empfohlener Produkte (Stern im Backend) aus den angegebenen Kategorien.
// Nur Produkte, die der Shop zeigt (is_visible: Katalog-Sichtbarkeit, „Nicht vorrätige ausblenden“), damit das Raster nie zu kurz ist.
function profax_front_featured_ids( $categories, $limit = 4 ) {
	if ( ! function_exists( 'wc_get_products' ) ) {
		return array();
	}
	$products = wc_get_products( array(
		'featured'   => true,
		'category'   => $categories,
		'status'     => 'publish',
		'visibility' => 'catalog',
		'limit'      => -1,
		'orderby'    => 'rand',
	) );
	$ids = array();
	foreach ( $products as $product ) {
		if ( $product->is_visible() ) {
			$ids[] = $product->get_id();
		}
	}
	return array_slice( $ids, 0, $limit );
}

// Empfohlene Lernhefte im normalen Katalog-Raster; mit Seiten-Cache wechselt die Auswahl erst, wenn er erneuert wird
function profax_front_featured() {
	$ids = profax_front_featured_ids( array( 'lernhefte-fuer-profax-lerngeraet', 'lernhefte-fuer-profaxli-lerngeraet' ) );
	if ( ! $ids ) {
		return;
	}
	echo '<section class="profax-front-featured"><h2>' . esc_html__( 'Beliebte Lernhefte', 'storefront-child-profax' ) . '</h2>';
	echo do_shortcode( '[products ids="' . implode( ',', array_map( 'intval', $ids ) ) . '" orderby="post__in" columns="4" cache="false"]' );
	echo '</section>';
}

// Die 3 neusten Beiträge als Text-Karten (Beiträge haben keine Beitragsbilder) und ein Link auf die Blog-Seite
function profax_front_news() {
	$posts = new WP_Query( array(
		'posts_per_page'      => 3,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	) );
	if ( ! $posts->have_posts() ) {
		return;
	}
	echo '<section class="profax-front-news"><h2>' . esc_html__( 'Aktuelles', 'storefront-child-profax' ) . '</h2><div class="profax-news">';
	while ( $posts->have_posts() ) {
		$posts->the_post();
		$text = has_excerpt() ? get_the_excerpt() : strip_shortcodes( get_the_content() );
		echo '<article class="profax-news__item">';
		echo '<time datetime="' . esc_attr( get_the_date( 'c' ) ) . '">' . esc_html( get_the_date() ) . '</time>';
		echo '<h3><a href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() ) . '</a></h3>';
		echo '<p class="profax-news__excerpt">' . esc_html( wp_trim_words( $text, 20 ) ) . '</p>';
		echo '</article>';
	}
	wp_reset_postdata();
	echo '</div>';
	if ( $blog = (int) get_option( 'page_for_posts' ) ) {
		echo '<p><a class="profax-news__all" href="' . esc_url( get_permalink( $blog ) ) . '">' . esc_html__( 'Alle Beiträge', 'storefront-child-profax' ) . '</a></p>';
	}
	echo '</section>';
}

/*
 * Klassen-Filter auf Shop- und Kategorieseiten (WooCommerce-Filter ?filter_klasse=<slug>),
 * kombiniert mit der Kategorie-Navigation: Kategorie-Links behalten die aktive Klasse.
 */

// Aktive Klasse aus ?filter_klasse; '' wenn keine oder keine Klasse eines sichtbaren Produkts
function profax_active_klasse() {
	$slug = isset( $_GET['filter_klasse'] ) ? sanitize_title( wp_unslash( $_GET['filter_klasse'] ) ) : '';
	return '' !== $slug && isset( profax_catalog_index()['klassen'][ $slug ] ) ? $slug : '';
}

// Anzahl der angegebenen Produkte mit der Klasse $slug
function profax_klasse_count( $slug, $ids ) {
	$klassen = profax_catalog_index()['klassen'];
	return isset( $klassen[ $slug ] ) ? count( array_intersect( $klassen[ $slug ], $ids ) ) : 0;
}

// Pill-Zeile „Klassen:“ mit „Alle (n), Vorschule (n), 1. Klasse (n) …“ für die Produkte $ids, Links auf $base;
// '' bei weniger als 2 Klassen
function profax_klassen_filter_html( $base, $terms, $active, $ids = array() ) {
	if ( count( $terms ) < 2 ) {
		return '';
	}
	$pills  = array( '' => __( 'Alle', 'storefront-child-profax' ) );
	$counts = array( '' => count( $ids ) );
	foreach ( $terms as $term ) {
		$pills[ $term->slug ]  = profax_klasse_label( $term );
		$counts[ $term->slug ] = profax_klasse_count( $term->slug, $ids );
	}
	$html = '<nav class="profax-klassen-filter" aria-label="' . esc_attr__( 'Klassen', 'storefront-child-profax' ) . '"><span class="profax-filter-label">' . esc_html__( 'Klassen:', 'storefront-child-profax' ) . '</span><ul class="profax-pills">';
	foreach ( $pills as $slug => $label ) {
		$url       = '' === (string) $slug ? $base : add_query_arg( 'filter_klasse', $slug, $base );
		$is_active = (string) $slug === $active;
		$html     .= '<li' . ( $is_active ? ' class="is-active"' : '' ) . '><a href="' . esc_url( $url ) . '"' . ( $is_active ? ' aria-current="true"' : '' ) . '>' . esc_html( $label ) . profax_count_html( $counts[ $slug ] ) . '</a></li>';
	}
	return $html . '</ul></nav>';
}

// Auf Shop- und Kategorieseiten direkt unter der Kategorie-Navigation (Priorität 15), über dem Seitentitel
add_action( 'storefront_before_content', 'profax_archive_klassen', 16 );
function profax_archive_klassen() {
	if ( ! function_exists( 'is_shop' ) || is_search() ) {
		return; // die Pill-Links würden den Suchbegriff verlieren
	}
	$index = profax_catalog_index();
	if ( is_shop() ) {
		$base = wc_get_page_permalink( 'shop' );
		$ids  = $index['ids'];
	} elseif ( is_product_category() ) {
		$term = get_queried_object();
		$base = get_term_link( $term );
		$ids  = isset( $index['cats'][ $term->term_id ] ) ? $index['cats'][ $term->term_id ] : array();
	} else {
		return;
	}
	if ( ! is_wp_error( $base ) ) {
		echo profax_klassen_filter_html( $base, profax_klasse_terms( $ids ), profax_active_klasse(), $ids );
	}
}

// Produktanzahl nach dem Pill-Text: " (n)"
function profax_count_html( $n ) {
	return ' <span class="profax-count">(' . (int) $n . ')</span>';
}

function profax_is_shop_nav( $args ) {
	return ! empty( $args->theme_location ) && 'primary' === $args->theme_location;
}

// Sichtbare Produkte, für die ein Eintrag der Kategorie-Navigation steht (Kategorie inkl. Unterkategorien,
// „Alle“ = Shop-Seite); null für andere Einträge
function profax_shop_nav_item_ids( $item ) {
	$index = profax_catalog_index();
	if ( 'product_cat' === $item->object ) {
		return isset( $index['cats'][ (int) $item->object_id ] ) ? $index['cats'][ (int) $item->object_id ] : array();
	}
	if ( 'page' === $item->object && function_exists( 'wc_get_page_id' ) && (int) $item->object_id === wc_get_page_id( 'shop' ) ) {
		return $index['ids'];
	}
	return null;
}

// Eintrag der Kategorie-Navigation bei aktiver Klasse: true = hat Produkte dieser Klasse, false = keine,
// null = nicht betroffen (keine aktive Klasse, anderes Menü, anderer Eintragstyp)
function profax_shop_nav_klasse_match( $item, $args ) {
	if ( ! profax_is_shop_nav( $args ) || ! ( $klasse = profax_active_klasse() ) || null === ( $ids = profax_shop_nav_item_ids( $item ) ) ) {
		return null;
	}
	return (bool) array_intersect( $ids, profax_catalog_index()['klassen'][ $klasse ] );
}

// Anzahl der verlinkten Seite; abgeschwächte Kategorien (keine Produkte der aktiven Klasse) bekommen keine, ihr Link lässt den Filter weg
add_filter( 'nav_menu_item_title', 'profax_shop_nav_count', 10, 3 );
function profax_shop_nav_count( $title, $item, $args ) {
	if ( ! profax_is_shop_nav( $args ) || null === ( $ids = profax_shop_nav_item_ids( $item ) ) ) {
		return $title;
	}
	if ( $klasse = profax_active_klasse() ) {
		$ids = array_intersect( $ids, profax_catalog_index()['klassen'][ $klasse ] );
		if ( ! $ids ) {
			return $title;
		}
	}
	return $title . profax_count_html( count( $ids ) );
}

add_filter( 'nav_menu_link_attributes', 'profax_shop_nav_klasse_link', 10, 3 );
function profax_shop_nav_klasse_link( $atts, $item, $args ) {
	if ( true === profax_shop_nav_klasse_match( $item, $args ) && ! empty( $atts['href'] ) ) {
		$atts['href'] = add_query_arg( 'filter_klasse', profax_active_klasse(), $atts['href'] );
	}
	return $atts;
}

add_filter( 'nav_menu_css_class', 'profax_shop_nav_klasse_class', 10, 3 );
function profax_shop_nav_klasse_class( $classes, $item, $args ) {
	if ( false === profax_shop_nav_klasse_match( $item, $args ) ) {
		$classes[] = 'profax-no-match';
	}
	return $classes;
}
