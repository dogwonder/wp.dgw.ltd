<?php
namespace GP_Google_Sheets\AJAX;

defined( 'ABSPATH' ) or exit;

class AJAX {
	/**
	 * @param string               $error_message_nonce
	 * @param string|null          $error_message_caps
	 * @param string|string[]|null $caps
	 * @param array                $additional_response_data
	 *
	 * @return void|never
	 */
	protected static function check_nonce_and_caps( $error_message_nonce, $error_message_caps = null, $caps = null, $additional_response_data = array() ) {
		$nonce = rgar( $_REQUEST, '_ajax_nonce' );
		$caps  = $caps ? $caps : gp_google_sheets()->get_capabilities( 'settings_page' );

		if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, \GP_Google_Sheets::NONCE_AJAX ) ) {
			wp_send_json_error( array_merge( array(
				'message' => $error_message_nonce,
			), $additional_response_data ) );
		}

		if ( ! \GFCommon::current_user_can_any( $caps ) ) {
			wp_send_json_error( array_merge( array(
				'message' => $error_message_caps ? $error_message_caps : $error_message_nonce,
			), $additional_response_data ) );
		}
	}
}
