<?php
/**
 * Katalog-Index und Klassen-Filter; profax_catalog_index(), profax_klasse_terms() und profax_klasse_label() nutzt auch die Startseite.
 * @package storefront-child-profax
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Kein direkter Aufruf
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
