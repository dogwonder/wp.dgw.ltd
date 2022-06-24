<?php
/**
 * ACF functionality
 *
 * @package dgwltd
 */
if ( function_exists( 'acf_add_options_page' ) ) {
	acf_add_options_page(
		array(
			'page_title' => 'Site General Settings',
			'menu_title' => 'Site Settings',
			'menu_slug'  => 'site-general-settings',
			'capability' => 'edit_posts',
			'redirect'   => false,
		)
	);
}

function one_acf_color_palette() {
	?>
		<script type="text/javascript">
		(function($) {
			acf.add_filter('color_picker_args', function( args, $field ){
				args.palettes = ['#14D5DE', '#FF00A6', '#000000', '#ffffff']
				return args;
			});
		})(jQuery);
		</script>
<?php }
add_action('acf/input/admin_footer', 'one_acf_color_palette');