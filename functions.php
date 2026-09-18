<?php
/**
 * storefront child theme functions.php file.
 * @package storefront-child-profax
 */

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
	
	remove_action('storefront_content_top', 'woocommerce_breadcrumb', 10);

//	add_action( 'storefront_header', 'storefront_display_custom_logo', 20 );
	//add_action( 'woocommerce_single_product_summary', 'add_custom_field', 0 );
	
	
	// wrap thumbnails, so that we can center them
	remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail', 10);
	add_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail2', 10);
	
	if ( ! function_exists( 'woocommerce_template_loop_product_thumbnail2' ) ) {
		function woocommerce_template_loop_product_thumbnail2() {
			echo woocommerce_get_product_thumbnail2();
		} 
	}
	if ( ! function_exists( 'woocommerce_get_product_thumbnail2' ) ) {   
		function woocommerce_get_product_thumbnail2( $size = 'shop_catalog', $placeholder_width = 0, $placeholder_height = 0  ) {
			global $post, $woocommerce;
			$output = '<div class="thumbnail_wrapper">';
	
			if ( has_post_thumbnail() ) {               
				$output .= get_the_post_thumbnail( $post->ID, $size );              
			}                       
			$output .= '</div>';
			return $output;
		}
	}
	
	

	// move meta to end of post
	remove_action('storefront_single_post', 'storefront_post_meta', 20);
	add_action('storefront_single_post', 'storefront_post_meta', 31);
	remove_action('storefront_loop_post', 'storefront_post_meta', 20);
	add_action('storefront_loop_post', 'storefront_post_meta', 31);

	// custom meta for products
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40 );
	add_action( 'woocommerce_single_product_summary', 'custom_woocommerce_template_single_meta', 40 );

	// remove "similar products"
	remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 );
	
	// credits
	remove_action('storefront_footer', 'storefront_credit', 20);
	
	// custom profax lerngeraet reference
	add_action( 'woocommerce_after_single_product_summary', 'custom_woocommerce_after_single_product_summary_pfg', 12 );
	add_filter( 'woocommerce_product_tabs', 'woo_remove_product_tabs', 98 );
	function woo_remove_product_tabs( $tabs ) {
		//unset( $tabs['description'] );      	// Remove the description tab
		//unset( $tabs['reviews'] ); 			// Remove the reviews tab
		unset( $tabs['additional_information'] );  	// Remove the additional information tab
	
		return $tabs;
	
	}
}

function custom_woocommerce_template_single_meta() {

	if ( ! defined( 'ABSPATH' ) ) {
		exit; // Exit if accessed directly
	}
	
	
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
			echo 'ISBN-13: <span class="sku" itemprop="isbn">'.substr($sku, 5).'</span></span>';
		} else {
			_e('SKU:', 'woocommerce');
			echo ' <span class="sku" itemprop="sku">'.$sku.'</span></span>';
		}
	}
	
	$p_sort = array('pa_language', 'pa_zyklus', 'pa_klasse', 'pa_alter', 'pa_autor', 'pa_illustration', 'pa_design', 'pa_programing', 'pa_wissenschaftliche-beratung');
	$attrs = $product->get_attributes();
	//foreach ($attrs as $attr => $attribute) {
	foreach ($p_sort as $attr) {
		if (isset($attrs[$attr])) {
			$attribute = $attrs[$attr];
			$taxonomy = get_taxonomy( $attribute['name'] );
			if ( $taxonomy && ! is_wp_error( $taxonomy ) ) {
				$attribute_string = '';
				$terms = wp_get_post_terms( $post->ID, $taxonomy->name );
	
				if ( !empty( $terms ) ) {
					foreach ( $terms as $term ) {
						if (strlen($attribute_string) > 0) {
							$attribute_string .= ', ';
						}
						$archive_link = get_term_link( $term->slug, $attribute['name'] );
						if ($attr == 'pa_language') {
							$attribute_string .= '<a itemprop href="' . $archive_link . '" content="'.$term->slug.'">'. $term->name . '</a>';
						} else {
							$attribute_string .= '<a itemprop href="' . $archive_link . '">'. $term->name . '</a>';
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
					echo '<span class="'.$attr.'" style="display: block;" >Sprache: '.str_replace(' itemprop ', ' itemprop="inLanguage"', $attribute_string).'</span>';
					break;
			}
		}
	}
	
	echo $product->get_categories( ', ', '<span class="posted_in">' . _n( 'Category:', 'Categories:', $cat_count, 'woocommerce' ) . ' ', '</span>' );
	echo $product->get_tags( ', ', '<span class="tagged_as">' . _n( 'Tag:', 'Tags:', $tag_count, 'woocommerce' ) . ' ', '</span>' );
	do_action( 'woocommerce_product_meta_end' );
	echo '</div>';

}

function custom_woocommerce_after_single_product_summary_pfg() {
	global $post, $product;

	if (strpos($product->get_categories(), "produkt-kategorie/lernhefte-fuer-profaxli-lerngeraet")) {
		echo '<div style="margin: -32px 0 64px; border-bottom: solid 1px #DDD; padding: 0 0 64px;">
<a href="//www.profax.ch/produkt/profaxli-lerngeraet/"><img src="/wordpress/wp-content/uploads/2016/07/profaxli_002-1.jpg" alt="profax Lerngerät" width="1075" height="1075" style="width:150px; height: 150px; float: left; margin: 0 40px 0 0;" /></a>
<h3 style="margin-top: 0 ! important;text-align: left; display: inline-block;">benötigt das <a href="/produkt/profaxli-lerngeraet/">profaxli Lerngerät</a></h3><br />
Das <a href="/produkt/profaxli-lerngeraet/">profaxli Lerngerät</a> und die Logo-Hefte 1-8 bilden zusammen ein Lernsystem, das ideal auf die Schule vorbereitet. Spielerisch an den Voraussetzungen für Mathe und Lesen arbeiten, aber ohne Zahlen und Buchstaben. 
		</div>';
	} else if (strpos($product->get_categories(), "produkt-kategorie/lernhefte-fuer-profax-lerngeraet")) {
		echo '<div style="margin: -32px 0 64px; border-bottom: solid 1px #DDD; padding: 0 0 32px;">
<a href="/produkt/profax-lerngeraet/"><img src="/wordpress/wp-content/uploads/2017/02/profaxkasten.png" alt="profax Lerngerät" width="1600" height="800" style="width:300px; height: 150px; float: left; margin-right: 40px;" /></a>
<h3 style="margin-top: 0 ! important;text-align: left; display: inline-block;">benötigt das <a href="/produkt/profax-lerngeraet/">profax Lerngerät</a></h3><br />
Das <a href="/produkt/profax-lerngeraet/">profax Lerngerät</a> mit Sofortrückmeldung und Kontrollblatt ist zusammen mit den Lehrmitteln zur Rechtschreibung, zum Textverständnis, zur Mathe und zu andern Themen ein wirksames Lernsystem. Es eignet sich für alle Formen von selbstständigem Lernen: Werkstattunterricht, offener Unterricht, Förderunterricht, Nachhilfe, usw. 
		</div>';
	} else if (strpos($product->get_categories(), "E-Learning")) {
		$file_headers = @get_headers('https://www.profaxonline.com/c/manuals/'.$product->get_sku().'_manual_de-DE.pdf');
		if ($file_headers && $file_headers[0] != 'HTTP/1.1 404 Not Found') {
			$manual = '<a target="_blank" href="https://www.profaxonline.com/c/manuals/'.$product->get_sku().'_manual_de-DE.pdf">⬇︎ '.$product->get_title().' Handbuch (.pdf)</a><br>';
		} else {
			$manual = "";
		}
		echo '<div style="margin: -32px 0 64px; border-bottom: solid 1px #DDD; padding: 0 0 32px 300px;">
'.$manual.'
<a target="_blank" href="https://www.profaxonline.com/programs/'.$product->get_sku().'&pdf">⬇︎ '.$product->get_title().' Prospekt (.pdf)</a>
		</div>';
	}
}


add_action( 'wp_enqueue_scripts', 'register_profaxonline_showcase_scripts' );

function register_profaxonline_showcase_scripts() {
    $server_url = "https://www.profaxonline.com";
    wp_register_script( 'hmac-sha512', $server_url.'/script/hmac-sha512.js', array (), 1.0, true);
    //wp_register_script( 't5', $server_url.'/script/t5.min.js', array ('jquery'), 1.01, true);
    wp_register_script( 't5', $server_url.'/script/t5.min.js', array (), 1.01, true);
    //wp_register_script( 'profaxonlinelogin', get_template_directory_uri().'-child-profax/profaxonlinelogin.js', array ('jquery', 't5', 'hmac-sha512'), 1.01, true);
    wp_register_script( 'profaxonlinelogin', get_template_directory_uri().'-child-profax/profaxonlinelogin.js', array ('t5', 'hmac-sha512'), 1.01, true);
}


function profaxonline_showcase($atts) {
	$server_url = "https://www.profaxonline.com";
	$tstring = '<script>GL_Config = {server_url: \''.$server_url.'\'}; if ((typeof $ == \'undefined\') && (typeof jQuery != \'undefined\')) {$ = jQuery;}</script>';
	
	$storeLookupTable = array(
		'dob' => 'dob-visuelles-wahrnehmungstraining',
		'dobpro' => 'dob-visuelles-wahrnehmungstraining-pro',
		'geograch' => 'geografie-schweiz-2',
		'juniorba' => 'junior-xplore-bauerninsel',
		'juniorfe' => 'junior-xplore-feuerinsel',
		'juniorre' => 'junior-xplore-regenwaldinsel',
		'katzemtz' => 'katze-mit-tz',
		'kk' => 'klangrad',
		'kkmini' => 'klangrad-mini',
		'multidda' => 'multidingsda',
		'krt5' => 'rechentraining-5',
		'krt6' => 'rechentraining-6',
		'rtregeln' => 'regeltrainer',
		'rtpro' => 'regeltrainer-pro',
		'wgverben' => 'wortgrammatik-verben',
		'wgadjekt' => 'wortgrammatik-adjektive',
		'wgnomen' => 'wortgrammatik-nomen',
		'rtwortst' => 'wortstamme'
	);
	//wp_enqueue_script( 'hmac-sha512' );
	//wp_enqueue_script( 't5' );
	wp_enqueue_script('profaxonlinelogin');

	$tstring .= '
<div id="profaxonline_centerbox">
	<div id="showcase"><div class="nav" id="showcase_left" onclick="window.ch.profax.online.backend.showcase.left();">.</div><div id="showcase_items"><div id="showcase_items_mover">';
    
                    // Fehler dokumentieren
                    ini_set('error_reporting', E_ERROR);                                        // E_ALL
                    ini_set('display_errors','1');

                    $json = file_get_contents($server_url."/program");
                    $rows = json_decode($json, true);
                    foreach ($rows as $key => $row) {
                        if ($row['price'] == 0) {
                            $tPriceprivate = "<br>Gratis";
                            $tPrice = "";
                        } else {
                            $tPriceprivate = $row['currency'].' '.sprintf("%.2f", $row['priceprivate']);
                            $tPrice = '<span localizetext="Schools"></span> '.$row['currency'].' '.sprintf("%.2f", $row['price']);
                        }
                        preg_match('|\<div class="prolog"\>(.*?)\<\/div\>|', preg_replace('/(\n|\r)/', ' ', $row['description']), $matches);
                        $prolog = $matches[1];
                        preg_match('/<div.class="author">(.*?)<\/div>/msi', $row['description'], $author);
                        $row['description'] = preg_replace('/<div.class="description">(.*?)<\/div>/msi', '', $row['description']);  // remove obsolete description, fix iPad add to homescreen
                        if (isset($storeLookupTable[$row['abr']])) {
                        	$url = './produkt/'.$storeLookupTable[$row['abr']].'/';
                        } else {
                        	$url = $server_url.'/programs/'.$row['abr'];
                        }
                        $tstring .= '<a class="tray" href="'.$url.'" target="_top">
                                        <div class="program '.$row['classname'].'" id="'.$row['id'].'" style="background-color:'.$row['color'].';">
                                            <img src="'.$server_url.'/c/'.$row['abr'].'_icon_144.png" />'.preg_replace("/(<\/?)a/i", '$1'.'span', $author[0]).'<div class="price" style="margin-top:-1em;text-align:right;">'.$tPriceprivate.'<br><span style="font-weight:normal">'.$tPrice.'</span></div><h3>'.$row['name'].'</h3>'.$prolog.'
                                        </div>
                                    </a>';
                    }
	$tstring .= '
        </div></div><div class="nav" id="showcase_right" onclick="window.ch.profax.online.backend.showcase.right();">.</div></div>

            <div id="loginbox">
                <form id="loginform" onsubmit="return window.ch.profax.online.backend.template.login()">
                    <h3>Login</h3>
                    <div><span localizetext="Username">Username</span> <input type="text" id="loginUsername" localizePlaceholder="Username" autocapitalize="none"  autocorrect="off" required autofocus /></div>
                    <div><span localizetext="Password">Password</span> <input type="password" id="loginPassword" localizePlaceholder="Password"/></div>
					<div><input type="submit" localizevalue="Login" value="Login"/></div>
					<div id="altLogin">... oder mit
						<a id="qrlogin" onclick="t5.mediaBox('."'".'<iframe width=&quot;640&quot; height=&quot;480&quot; style=&quot;border: none;&quot; src=&quot;https://www.profaxonline.com/backend/php/widgets/userloginapi/index.php?qrlogin&quot; sandbox=&quot;allow-top-navigation allow-scripts allow-same-origin allow-forms&quot; allow=&quot;camera&quot;></iframe>'."'".');"></a>
						<a onclick="window.ch.profax.online.backend.template.ssologin(\'microsoft\', '.time().')" id="mslogin"></a>
						<a onclick="window.ch.profax.online.backend.template.ssologin(\'google\', '.time().')" id="glogin"></a>
					</div>
                </form><div id="newlogin">
                    <h3 localizetext="NewQ">New?</h3>
                    <a onclick="ch.profax.online.backend.template.registerSelect()" localizetext="Register">Register</a>
                    <div>
                        <a href="https://www.profaxonline.com/dech/howto/" localizetext="VideoHowto">Videoanleitungen</a>
                    </div>
                    <a onclick="$(\'#demo\').submit();" localizetext="Demo">Demo</a><form target="_top" id="demo" method="post" action="'.$server_url.'"><input type="hidden" name="demo" value="1"></form>
                    <div><a onclick="return window.ch.profax.online.backend.template.passwordReset()" localizetext="ForgotPasswordQ">Forgot your password?</a></div>
                </div>
            </div>
         </div>
         <div style="height: 460px; z-index: -1000;"> </div>';
	return $tstring;
}
add_shortcode( 'profaxonlineshowcase', 'profaxonline_showcase' );