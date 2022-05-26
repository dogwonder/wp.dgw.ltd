<?php
/**
 * Define the blocks functionality.
 *
 * Loads and defines the Block rules for post types
 *
 * @since      1.0.0
 * @package    ONE_Blocks
 * @subpackage ONE_Blocks/includes
 * @author     Rich Holman <dogwonder@gmail.com>
 */
class ONE_Blocks_Rules {

	// Define the post types and the allowed blocks
	public function one_register_block_rules( $allowed_block_types ) {

        global $current_screen;

		// Limit blocks in 'post' post type
		if ( ! empty( $current_screen->post_type === 'post' ) ) {
			// Return an array containing the allowed block types
			return array(
				'core/paragraph',
				'core/heading', 
                'core/image', 
                'core/list'
			);
		}
		return $allowed_block_types;

	}

}
