<?php
/**
 * The template for displaying the footer.
 *
 * Contains the closing of the #content div and all content after
 *
 * @package storefront
 */

?>

		</div><!-- .col-full -->
	</div><!-- #content -->

	<?php do_action( 'storefront_before_footer' ); ?>

	<footer id="colophon" class="site-footer" role="contentinfo">
		<div class="col-full">

			<?php
			/**
			 * Functions hooked in to storefront_footer action
			 *
			 * @hooked storefront_footer_widgets - 10
			 * @hooked storefront_credit         - 20
			 */
			do_action( 'storefront_footer' ); ?>

		</div><!-- .col-full -->
	</footer><!-- #colophon -->

	<?php do_action( 'storefront_after_footer' ); ?>

</div><!-- #page -->

<?php wp_footer(); ?>

<?php
    if (!isset($_COOKIE['cookieconsent'])) {
        echo '<div id="cookieconsent">Wir setzen Cookies ein, um Einstellungen zur aktuellen Sitzung zu speichern.<br />Wenn Sie die Website weiter nutzen, gehen wir von Ihrem Einverständnis aus.<br />Weitere Informationen finden Sie in unserer <a href="https://www.profaxonline.com/privacypolicy">Datenschutzerklärung</a>.<button onclick="var d = new Date(); d.setTime(d.getTime() + 365*24*60*60*1000); document.cookie = \'cookieconsent=1;\' + d.toUTCString() + \'; path=/\';jQuery(\'#cookieconsent\').hide();">Weiter</button></div>';
    } else {
        setcookie('cookieconsent', 1, strtotime('+365 days'), '/');             // extend cookie by one year
    }
?>

</body>
</html>
