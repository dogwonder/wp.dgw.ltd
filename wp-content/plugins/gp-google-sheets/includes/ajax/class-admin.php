<?php
namespace GP_Google_Sheets\AJAX;

defined( 'ABSPATH' ) or exit;

use GP_Google_Sheets\Accounts\Google_Accounts;
use GP_Google_Sheets\Spreadsheets\Spreadsheet;
use GP_Google_Sheets\Spreadsheets\Spreadsheets;

class Admin extends AJAX {
	public static function hooks() {
		add_action( 'wp_ajax_gpgs_get_spreadsheets', array( __CLASS__, 'ajax_get_spreadsheets' ) );
		add_action( 'wp_ajax_gpgs_get_google_accounts', array( __CLASS__, 'ajax_get_google_accounts' ) );
		add_action( 'wp_ajax_gpgs_get_sheets', array( __CLASS__, 'ajax_get_sheets' ) );
	}

	public static function ajax_get_spreadsheets() {
		self::check_nonce_and_caps(
			__( 'There was an error fetching the spreadsheets that GP Google Sheets has access to. Double check that you have permissions to edit GP Google Sheets Settings and try again.', 'gp-google-sheets' ),
			null,
			gp_google_sheets()->get_capabilities( 'form_settings' ),
		);

		$email = rgar( $_REQUEST, 'google_email' );

		if ( ! $email ) {
			wp_send_json_error( array(
				'message' => __( 'No Google account was selected.', 'gp-google-sheets' ),
			) );
		}

		// The plugin settings page does not allow showing spreadsheets for legacy tokens.
		$spreadsheets = Spreadsheets::get( $email );
		$response     = array();

		foreach ( $spreadsheets as $spreadsheet ) {
			$response[] = array(
				'name'        => $spreadsheet->get_name(),
				'id'          => $spreadsheet->get_id(),
				'webViewLink' => $spreadsheet->get_url(),
			);
		}

		wp_send_json_success( array(
			'spreadsheets' => $response,
		) );
	}

	/**
	 * AJAX callback for getting Google accounts/tokens.
	 */
	public static function ajax_get_google_accounts() {
		self::check_nonce_and_caps(
			__( 'There was an error fetching the Google accounts authorized to use GP Google Sheets. Double check that you have permissions to edit GP Google Sheets Settings and try again.', 'gp-google-sheets' ),
			null,
			gp_google_sheets()->get_capabilities( 'form_settings' ),
		);

		$google_accounts = Google_Accounts::get_all();

		wp_send_json_success( array(
			'google_accounts' => array_map( function( $account ) {
				return $account->to_json();
			}, $google_accounts ),
		) );
	}

	/**
	 * AJAX callback to get the sheets for a spreadsheet.
	 */
	public static function ajax_get_sheets() {
		self::check_nonce_and_caps(
			__( 'There was an error fetching the sheets for the spreadsheet. Double check that you have permissions to edit GP Google Sheets Settings and try again.', 'gp-google-sheets' ),
			null,
			gp_google_sheets()->get_capabilities( 'form_settings' ),
		);

		$spreadsheet_id = rgpost( 'spreadsheet_id' );
		$spreadsheet    = Spreadsheet::get( $spreadsheet_id );

		$sheets        = $spreadsheet->get_sheets();
		$sheet_options = array();

		foreach ( $sheets as $sheet_id => $title ) {
			$sheet_options[] = array(
				'id'    => $sheet_id,
				'title' => $title,
			);
		}

		return wp_send_json_success( array(
			'sheets' => $sheet_options,
		) );
	}

}
