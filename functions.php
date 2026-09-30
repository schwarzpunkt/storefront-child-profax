<?php
/**
 * storefront child theme functions.php file.
 * @package storefront-child-profax
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

//Storefront adds it's own stylesheet for child themes

// Put your custom PHP below

// Display 100 products per page. Goes in functions.php
add_filter( 'loop_shop_per_page', function( $cols ) {
	return 100;
}, 20 );

add_action( 'init', 'storefront_custom_logo' );
function storefront_custom_logo() {
	remove_action('storefront_header', 'storefront_site_branding', 20 );
	remove_action('storefront_header', 'storefront_product_search', 40 );
	remove_action('storefront_header', 'storefront_secondary_navigation', 30);

	remove_action('storefront_before_content', 'woocommerce_breadcrumb', 10);

	// wrap thumbnails, so that we can center them
	add_action( 'woocommerce_before_shop_loop_item_title', function() {
		echo '<div class="thumbnail_wrapper">';
	}, 9 );
	add_action( 'woocommerce_before_shop_loop_item_title', function() {
		echo '</div>';
	}, 11 );

	// keep the 300px catalog size until thumbnails are regenerated
	add_filter( 'single_product_archive_thumbnail_size', function() {
		return 'shop_catalog';
	} );



	// move meta to end of post
	remove_action('storefront_post_header_before', 'storefront_post_meta', 10);
	add_action('storefront_single_post', 'storefront_post_meta', 31);
	add_action('storefront_loop_post', 'storefront_post_meta', 31);

	// custom meta for products
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40 );
	add_action( 'woocommerce_single_product_summary', 'custom_woocommerce_template_single_meta', 40 );

	// remove "similar products"
	remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 );

	// remove previous/next product pagination
	remove_action( 'woocommerce_after_single_product_summary', 'storefront_single_product_pagination', 30 );

	// credits
	remove_action('storefront_footer', 'storefront_credit', 20);

	// custom profax lerngeraet reference
	add_action( 'woocommerce_after_single_product_summary', 'custom_woocommerce_after_single_product_summary_pfg', 12 );
	add_filter( 'woocommerce_product_tabs', 'profax_remove_product_tabs', 98 );
	function profax_remove_product_tabs( $tabs ) {
		//unset( $tabs['description'] );      	// Remove the description tab
		//unset( $tabs['reviews'] ); 			// Remove the reviews tab
		unset( $tabs['additional_information'] );  	// Remove the additional information tab

		return $tabs;

	}
}

function custom_woocommerce_template_single_meta() {

	// https://schema.org/Book
		// illustrator
		// isbn
		// author
		// award
		// contributor
		// publisher
		// gtin13

		// inLanguage


	//print_r(get_post_custom($post->ID)['_product_attributes']);

	//print_r($product->get_attributes());


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
			echo 'ISBN-13: <span class="sku" itemprop="isbn">'.esc_html(substr($sku, 5)).'</span></span>';
		} else {
			_e('SKU:', 'woocommerce');
			echo ' <span class="sku" itemprop="sku">'.esc_html($sku).'</span></span>';
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
						if ($attr == 'pa_language') {
							$attribute_string .= '<a itemprop href="' . esc_url( $archive_link ) . '" content="' . esc_attr( $term->slug ) . '">' . esc_html( $term->name ) . '</a>';
						} else {
							$attribute_string .= '<a itemprop href="' . esc_url( $archive_link ) . '">' . esc_html( $term->name ) . '</a>';
						}
					}
				}
			}
			switch ($attr) {
				case 'pa_autor':
				// http:/wordpress/author/[pa_author]/
					echo '<span class="'.$attr.'" style="display: block;">Autor: '.str_replace(' itemprop ', ' itemprop="author" ', $attribute_string).'</span>';
					break;
				case 'pa_illustration':
					echo '<span class="'.$attr.'" style="display: block;">Illustration: '.str_replace(' itemprop ', ' itemprop="illustrator" ', $attribute_string).'</span>';
					break;
				case 'pa_programing':
					echo '<span class="'.$attr.'" style="display: block;">Programmierung: '.str_replace(' itemprop ', ' itemprop="author" ', $attribute_string).'</span>';
					break;
				case 'pa_wissenschaftliche-beratung':
					echo '<span class="'.$attr.'" style="display: block;">Wissenschaftliche Beratung: '.str_replace(' itemprop ', ' itemprop="author" ', $attribute_string).'</span>';
					break;
				case 'pa_design':
					echo '<span class="'.$attr.'" style="display: block;">Gestaltung: '.str_replace(' itemprop ', ' ', $attribute_string).'</span>';
					break;
				case 'pa_alter':
					echo '<span class="'.$attr.'" style="display: block;" >Alter: <span itemprop="typicalAgeRange">'.strip_tags($attribute_string).'</span></span>';
					break;
				case 'pa_klasse':
					echo '<span class="'.$attr.'" style="display: block;">Klasse: '.str_replace(' itemprop ', ' ', $attribute_string).'</span>';
					break;
				case 'pa_zyklus':
					echo '<span class="'.$attr.'" style="display: block;">Zyklus: '.str_replace(' itemprop ', ' ', $attribute_string).'</span>';
					break;
				case 'pa_language':
					echo '<span class="'.$attr.'" style="display: block;" >Sprache: '.str_replace(' itemprop ', ' itemprop="inLanguage" ', $attribute_string).'</span>';
					break;
			}
		}
	}

	echo wc_get_product_category_list( $product->get_id(), ', ', '<span class="posted_in">' . _n( 'Category:', 'Categories:', $cat_count, 'woocommerce' ) . ' ', '</span>' );
	echo wc_get_product_tag_list( $product->get_id(), ', ', '<span class="tagged_as">' . _n( 'Tag:', 'Tags:', $tag_count, 'woocommerce' ) . ' ', '</span>' );
	do_action( 'woocommerce_product_meta_end' );
	echo '</div>';

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
	} else if (has_term('e-learning', 'product_cat', $product->get_id()) && $product->get_sku()) {
		$response = wp_remote_head('https://www.profaxonline.com/c/manuals/'.$product->get_sku().'_manual_de-DE.pdf', array('timeout' => 3));
		if (wp_remote_retrieve_response_code($response) === 200) {
			$manual = '<a target="_blank" href="'.esc_url('https://www.profaxonline.com/c/manuals/'.$product->get_sku().'_manual_de-DE.pdf').'">⬇︎ '.esc_html($product->get_title()).' Handbuch (.pdf)</a><br>';
		} else {
			$manual = "";
		}
		echo '<div class="profax-promo profax-promo--elearning">
'.$manual.'
<a target="_blank" href="'.esc_url('https://www.profaxonline.com/programs/'.$product->get_sku().'&pdf').'">⬇︎ '.esc_html($product->get_title()).' Prospekt (.pdf)</a>
		</div>';
	}
}
