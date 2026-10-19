<?php
/**
 * Ersetzt header.php von Storefront 4.6.2: Statt des Storefront-Masthead kommt
 * der Header von www.profaxonline.com (profax_site_header()).
 *
 * @package storefront-child-profax
 */

?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="profile" href="http://gmpg.org/xfn/11">
<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>">

<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>

<?php wp_body_open(); ?>

<?php do_action( 'storefront_before_site' ); ?>

<div id="page" class="hfeed site">
	<?php do_action( 'storefront_before_header' ); ?>

	<?php profax_site_header(); ?>

	<?php
	/**
	 * An storefront_before_content gehängte Funktionen
	 *
	 * @hooked storefront_header_widget_region - 10 (Suchfeld)
	 * @hooked profax_shop_nav - 15 (Produktkategorien)
	 * @hooked profax_archive_klassen - 16 (Klassen-Filter)
	 */
	do_action( 'storefront_before_content' );
	?>

	<div id="content" class="site-content" tabindex="-1">
		<div class="col-full">

		<?php
		do_action( 'storefront_content_top' );
