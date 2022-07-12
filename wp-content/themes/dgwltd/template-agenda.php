<?php
/**
 * Template Name: Agenda template
 *
 * The template for displaying all blocks
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#single-post
 *
 * @package dgwltd
 */

get_header();
?>
<link href="https://assets.codepen.io/1801/govuk-frontend-4.2.0.min.css" rel="stylesheet">
<style>
	.govuk-tabs {
		text-align: center;
		display: flex;
		flex-direction: column;
	}
	.govuk-tabs .govuk-tabs__list {
		list-style-type:none;
		padding: 0;
		margin: 0 auto;
		border:0;
	}
	.js-enabled .govuk-tabs__panel {
		border: 0;
	}
	.js-enabled .govuk-tabs .govuk-tabs__panel {
		text-align: left;
	}
	.js-enabled .govuk-tabs__list-item {
		background-color: transparent;
		border-bottom: 3px solid #F2F2F2;
		padding: 0;
	}
	.js-enabled .govuk-tabs__list-item a {
		text-decoration: none;
		display: block;
		padding: 10px 20px;
	}
	.js-enabled .govuk-tabs__list-item--selected {
		border:0;
		border-bottom: 3px solid #5C79F7;
		padding: 0;
		margin-top: 0;
	}
	.js-enabled .govuk-tabs__list-item:after {
		position: relative;
		bottom: -8px;
		content: "";
		display: block;
		width: 0;
		height: 0;
		border-left: 5px solid transparent;
		border-right: 5px solid transparent;
		border-top: 5px solid transparent;
		margin: 0 auto;
		margin-top: -3px;
	}
	.js-enabled .govuk-tabs__list-item:hover {
		border-bottom-color: #5C79F7;
	}
	.js-enabled .govuk-tabs__list-item:hover:after, 
	.js-enabled .govuk-tabs__list-item--selected:after {
		border-top-color: #5C79F7;
	}
	.js-enabled .govuk-tabs__tab:active, .js-enabled .govuk-tabs__tab:focus {
		background-color: #F2F2F2;
		border-bottom-color: #5C79F7;
		box-shadow: none;
	}

	.wp-block-table td, .wp-block-table th {
		border: 0;
		border-bottom: 1px solid #F2F2F2;
		padding: 1rem 0.5rem 2rem 0.5rem;
		vertical-align: top;
	}
	.wp-block-table tr td:first-of-type {
		min-width: 200px;
	}
	.wp-block-table tr td strong {
		display: inline-block;
		margin-bottom: 1rem;
	}
</style>
<div id="primary" class="dgwltd-content-wrapper">

		<?php
		while ( have_posts() ) :
			the_post();
			get_template_part( 'template-parts/_templates/content', 'page' );
		endwhile; // End of the loop.
		?>

		
</div><!-- #primary -->
<script src="https://assets.codepen.io/1801/govuk-frontend-4.2.0.min.js"></script>

<?php
get_footer();
