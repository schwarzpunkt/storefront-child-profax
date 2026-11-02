<?php
/**
 * functions.php des Storefront-Child-Themes für den profax Shop.
 * @package storefront-child-profax
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Kein direkter Aufruf
}

// Integration in profax.com: Schriften, Farben, Header und Footer wie auf www.profax.com
define( 'PROFAX_THEME_VERSION', wp_get_theme()->get( 'Version' ) );
define( 'PROFAX_MAIN_URL', 'https://www.profax.com/' );

require_once __DIR__ . '/inc/woocommerce.php';
require_once __DIR__ . '/inc/site-integration.php';
require_once __DIR__ . '/inc/klassen-filter.php';
require_once __DIR__ . '/inc/front-page-sections.php';
