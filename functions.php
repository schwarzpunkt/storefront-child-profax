<?php
/**
 * functions.php des Storefront-Child-Themes für den profax Shop.
 * @package storefront-child-profax
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Kein direkter Aufruf
}

// Integration in profax.com: Schriften, Farben, Header und Footer wie auf www.profaxonline.com
define( 'PROFAX_THEME_VERSION', wp_get_theme()->get( 'Version' ) );
// Basis-URL der Hauptseite; anpassen, wenn profaxonline.com nach profax.com umzieht
define( 'PROFAX_MAIN_URL', 'https://www.profaxonline.com/dech/' );

require_once __DIR__ . '/inc/woocommerce.php';
require_once __DIR__ . '/inc/site-integration.php';
require_once __DIR__ . '/inc/klassen-filter.php';
require_once __DIR__ . '/inc/front-page-sections.php';
