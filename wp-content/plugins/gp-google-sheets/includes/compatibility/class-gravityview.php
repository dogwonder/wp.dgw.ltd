<?php
namespace GP_Google_Sheets\Compatibility;

defined( 'ABSPATH' ) or exit;

/**
 * Compatibility class for GravityView/GravityKit.
 */
class GravityView {
	private static $instance = null;

	public static function get_instance() {
		if ( self::$instance === null ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	public function __construct() {
		add_filter( 'gravityview-inline-edit/entry-updated', array( $this, 'after_gv_inline_edit' ), 10, 5 );
	}

	function after_gv_inline_edit( $update_result, $entry, $form_id, $gf_field, $original_entry ) {
		if ( $update_result ) {
			// entry_edited() has a check to determine if updates should be sent to the sheet.
			gp_google_sheets()->handle_after_update_entry( \GFAPI::get_form( $form_id ), $entry );
		}

		return $update_result;
	}
}
