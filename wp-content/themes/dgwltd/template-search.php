<?php
/**
 * Template Name: Search page
 *
 * The template for displaying the search results
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#single-post
 *
 * @package dgwltd
 */

get_header();
?>
<div id="primary" class="dgwltd-content-wrapper">
	<article class="entry-content">
		<h2><?php esc_html_e( 'Search:', 'dgwltd' ); ?></h2>
		<div id="search-form" class="site-search">
				<span class="visually-hidden"><?php esc_html_e( 'Search this website', 'dgwltd' ); ?></span>
				<?php get_search_form(); ?>
		</div>
	</article>
</div><!-- #primary -->
<?php
get_footer();
