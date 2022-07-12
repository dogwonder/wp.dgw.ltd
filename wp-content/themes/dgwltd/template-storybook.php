<?php
/**
 * Template Name: Storybook
 *
 * The template for displaying all blocks
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#single-post
 *
 * @package dgwltd
 */

get_header();

global $post;
?>
<style>
	.story-book__item {
		margin-bottom: 2rem;
	}
	.story-book__block {
		margin-bottom: 2rem;
	}
</style>
<div id="primary" class="dgwltd-content-wrapper">

		<?php
		while ( have_posts() ) :
			the_post();
			get_template_part( 'template-parts/_templates/content', 'page' );
		endwhile; // End of the loop.
		?>

		<div class="entry-content">

		<?php

		$excludedBlocks = [];
		$excludedUrls = [];

		$pages = get_posts(
			['post_type' => ['page', 'post'],
			'posts_per_page' => -1,
			'post_status' => array('publish')]);
		$blockData = [];

		foreach($pages as $page) {

			$blocks =  ( parse_blocks( $page->post_content ) );
			foreach($blocks as $block) {
				$blockExcluded = in_array($block['blockName'], $excludedBlocks) || !substr_count($block['blockName'], 'acf/');
				if(!$blockExcluded) {
					$url = get_permalink($page->ID);
					$proceed = true;
					foreach($excludedUrls as $excludedUrl) {
						if(substr_count($url, $excludedUrl)) {
							$proceed = false;
						}
					}
					if($proceed) {
						$count++;
						$block['url'] = $url;
						$blockData[$block['blockName']] = $blockData[$block['blockName']] ? : [];
						array_push($blockData[$block['blockName']], $block);
					}
				}
			}
		}
		ksort($blockData);
		//var_dump($blockData);

		foreach($blockData as $blockDisplay) {
			echo '<div class="story-book__item">
			<h2>' . $blockDisplay[0]['blockName'] . '</h2>';
				foreach($blockDisplay as $blockDisplayContent) {
					echo '<h3><a href="' . $blockDisplayContent['url'] . '" class="link">' . $blockDisplayContent['url'] . '</a></h3>';
					echo '<div class="story-book__block">';
					echo render_block($blockDisplayContent);
					echo '</div>';
				}
			echo '</div><hr />';
		}
		?>
		</div>
		
</div><!-- #primary -->
<?php
get_footer();
