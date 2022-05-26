<?php
/**
 * The template for displaying all pages
 *
 * This is the template that displays all pages by default.
 * Please note that this is the WordPress construct of pages
 * and that other 'pages' on your WordPress site may use a
 * different template.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package dgwltd
 */

get_header();
?>

	<div id="primary" class="dgwltd-content-wrapper">
		
		<?php
		while ( have_posts() ) :
			the_post();

			get_template_part( 'template-parts/_templates/content', 'page' ); 

		endwhile; // End of the loop.
		?>
	
	</div><!-- #primary -->

<?php
get_footer();
