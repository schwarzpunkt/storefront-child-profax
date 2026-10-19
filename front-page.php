<?php
/**
 * Shop-Startseite: Inhalt der statischen Startseite (im Backend gepflegt),
 * danach die Abschnitte aus functions.php („Startseite“).
 * Siehe docs/superpowers/specs/2026-10-19-shop-startseite-design.md
 *
 * @package storefront-child-profax
 */

get_header(); ?>

	<div id="primary" class="content-area">
		<main id="main" class="site-main" role="main">

			<?php
			while ( have_posts() ) :
				the_post();
				?>
				<article id="post-<?php the_ID(); ?>" <?php post_class( 'profax-front-content' ); ?>>
					<h1 class="screen-reader-text"><?php the_title(); ?></h1>
					<div class="entry-content">
						<?php the_content(); ?>
					</div>
				</article>
				<?php
			endwhile;

			profax_front_device_cards( profax_front_devices() );
			profax_front_klassen();
			profax_front_featured();
			profax_front_news();
			?>

		</main><!-- #main -->
	</div><!-- #primary -->

<?php
get_footer();
