<?php
/**
 * Startseite (front-page.php): Geräte-Karten, Klassen-Pills, beliebte Lernhefte, Aktuelles.
 * Produkte und Kategorien werden über den Slug gesucht, die IDs unterscheiden sich lokal und live.
 * @package storefront-child-profax
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Kein direkter Aufruf
}

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
