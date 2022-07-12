<?php
/**
 * Custom functions for this theme
 *
 * @package dgwltd
 */

/**
 * Adds custom classes to the array of body classes.
 *
 * @param array $classes Classes for the body element.
 * @return array
 */
if ( ! function_exists( 'dgwltd_body_classes' ) ) :
	function dgwltd_body_classes( $classes ) {
		// Adds a class of hfeed to non-singular pages.

		global $post;

		if ( ! is_singular() ) {
			$classes[] = 'hfeed';
		}

		// Adds a class of no-sidebar when there is no sidebar present.
		if ( ! is_active_sidebar( 'sidebar-1' ) ) {
			$classes[] = 'no-sidebar';
		}

		return $classes;
	}
	add_filter( 'body_class', 'dgwltd_body_classes' );
endif;

/**
 * WCAG 2.0 Attributes for Dropdown Menus
 *
 * Adjustments to menu attributes tot support WCAG 2.0 recommendations
 * for flyout and dropdown menus.
 *
 * @ref https://www.w3.org/WAI/tutorials/menus/flyout/
 */
if ( ! function_exists( 'wcag_nav_menu_link_attributes' ) ) :
	function wcag_nav_menu_link_attributes( $atts, $item, $args, $depth ) {

		// Add [aria-haspopup] and [aria-expanded] to menu items that have children
		$item_has_children = in_array( 'menu-item-has-children', $item->classes );
		if ( $item_has_children ) {
			$atts['aria-haspopup'] = 'true';
			$atts['aria-expanded'] = 'false';
		}

		return $atts;
	}
	add_filter( 'nav_menu_link_attributes', 'wcag_nav_menu_link_attributes', 10, 4 );
endif;

/*
 * Exclude Uncategorized from get_the_category_list function.
 *
 * @access public
 */
function dgwltd_the_category_filter( $thelist, $separator = ' ' ) {
	if ( ! defined( 'WP_ADMIN' ) ) {
		$exclude = array( 1 );

		$exclude2 = array();
		foreach ( $exclude as $c ) {
			$exclude2[] = get_cat_name( $c );
		}

		$cats    = explode( $separator, $thelist );
		$newlist = array();
		foreach ( $cats as $cat ) {
			$catname = trim( wp_strip_all_tags( $cat ) );
			if ( ! in_array( $catname, $exclude2 ) ) {
				$newlist[] = $cat;
			}
		}
		return implode( $separator, $newlist );
	} else {
		return $thelist;
	}
}
add_filter( 'the_category', 'dgwltd_the_category_filter', 10, 2 );

// Callable for usort. Sorts the array based on the 'date' array value - using spaceship operator <=> PHP 7+
if ( ! function_exists( 'dgwltd_sort_dates' ) ) :
	function dgwltd_sort_dates( $a, $b ) {

		// order by date (closest first) - php 7
		return new DateTime( $a['start'] ) <=> new DateTime( $b['start'] );

	}
endif;


//Remove core block patterns (WordPress ships with a handful of patterns that are enabled by default)
function wpdc_remove_core_block_patterns_support() {
	remove_theme_support( 'core-block-patterns' );
}
add_action( 'after_setup_theme', 'wpdc_remove_core_block_patterns_support' );