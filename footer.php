<?php
/**
 * Ersetzt footer.php von Storefront 4.6.2: Statt des Storefront-Colophon kommt
 * der Footer von www.profaxonline.com (profax_site_footer()).
 *
 * @package storefront-child-profax
 */

?>

		</div><!-- .col-full -->
	</div><!-- #content -->

	<?php do_action( 'storefront_before_footer' ); ?>

	<?php profax_site_footer(); ?>

	<?php do_action( 'storefront_after_footer' ); ?>

</div><!-- #page -->

<?php wp_footer(); ?>

</body>
</html>
