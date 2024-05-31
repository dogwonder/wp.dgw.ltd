<?php
namespace GP_Google_Sheets\Compatibility;

defined( 'ABSPATH' ) or exit;

class Gravity_Flow_Step_Feed_GP_Google_Sheets extends \Gravity_Flow_Step_Feed_Add_On {
	/**
	 * The step type.
	 *
	 * @var string
	 */
	public $_step_type = 'gp_google_sheets';

	/**
	 * The name of the class used by the add-on.
	 *
	 * @var string
	 */
	protected $_class_name = 'GP_Google_Sheets';

	/**
	 * Returns the step label.
	 *
	 * @return string
	 */
	public function get_label() {
		return 'Google Sheets';
	}

	/**
	 * Returns the step icon.
	 */
	public function get_icon_url() {
		return plugin_dir_url( __DIR__ ) . 'assets/menu-icon.svg';
	}

	/**
	 * Returns the feed name.
	 *
	 * @param array $feed The Emma feed properties.
	 *
	 * @return string
	 */
	public function get_feed_label( $feed ) {
		$label = $feed['meta']['feed_name'];

		return $label;
	}
}
