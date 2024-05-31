<?php
namespace GP_Google_Sheets\AJAX;

defined( 'ABSPATH' ) or exit;

use GP_Google_Sheets\Spreadsheets\Spreadsheet;

class Feed_Settings extends AJAX {

	public static function hooks() {
		//Handle an AJAX call behind the Disconnect buttons
		add_action( 'wp_ajax_gpgs_disconnect', array( __CLASS__, 'disconnect' ) );

		//Handle an AJAX call behind the Insert Test Row button
		add_action( 'wp_ajax_gpgs_insert_test_row', array( __CLASS__, 'insert_test_row' ) );

		// Endpoint to be used after a sheet is selected
		add_action( 'wp_ajax_gpgs_select_sheet', array( __CLASS__, 'ajax_select_sheet' ) );
	}

	public static function disconnect() {
		self::check_nonce_and_caps(
			__( 'There was an error disconnecting the current feed. Double check that you have permissions to use GP Google Sheets and try again.', 'gp-google-sheets' ),
			null,
			gp_google_sheets()->get_capabilities( 'form_settings' ),
		);

		if ( empty( $_POST['form_id'] ) ) {
			wp_send_json_error(array(
				'message' => __( 'Form ID not provided.', 'gp-google-sheets' ),
			));
		}

		$feed_id = intval( $_POST['feed_id'] );
		$feed    = gp_google_sheets()->get_feed( $feed_id );

		if ( is_wp_error( $feed ) ) {
			wp_send_json_error();
		}

		// Purge cache data
		$spreadsheet = Spreadsheet::get( $feed['meta']['google_spreadsheet_id'] );
		$spreadsheet->flush_spreadsheet_cache();

		/**
		 * When deleting a feed token, we need to also try and delete any token that may have been stored
		 * with legacy methods. Thus, the call to delete_feed_mapping() and delete_feed_token().
		 */
		$meta_to_delete = array(
			'token', // Deprecated, used for legacy tokens.
			'picked_token', // Deprecated, used for legacy tokens.
			'google_sheet_url_field',
			'google_sheet_url',
			'google_sheet_id',
			'sheet_was_picked', // Deprecated, but we still want to delete it
		);

		foreach ( $meta_to_delete as $field_name ) {
			if ( rgar( $feed['meta'], $field_name ) ) {
				unset( $feed['meta'][ $field_name ] );
			}
		}

		gp_google_sheets()->update_feed_meta( $feed['id'], $feed['meta'] );

		wp_send_json_success();
	}

	/**
	 * @throws \Error
	 */
	public static function insert_test_row() {
		self::check_nonce_and_caps(
			__( 'There was an error inserting a test row. Double check that you have permissions to use GP Google Sheets and try again.', 'gp-google-sheets' ),
			null,
			gp_google_sheets()->get_capabilities( 'form_settings' ),
		);

		if ( empty( $_POST['form_id'] ) || empty( $_POST['feed_id'] ) ) {
			wp_send_json_error(array(
				'message' => __( 'Test row insertion failed. Parameters not provided.', 'gp-google-sheets' ),
			));
		}

		$error_message = __( 'Test row insertion failed.', 'gp-google-sheets' );

		$form_id = intval( $_POST['form_id'] );
		//This is how you get the settings of a feed type add-on
		$feed_id = intval( $_POST['feed_id'] );

		$feed        = gp_google_sheets()->get_feed( $feed_id );
		$spreadsheet = Spreadsheet::from_feed( $feed );

		if ( ! $spreadsheet ) {
			wp_send_json_error( array(
				'message' => __( 'Could not connect to Google Sheets. Please check your Google Accounts and try again.', 'gp-google-sheets' ),
			) );
		}

		//If there is no field map, there's nothing to test yet
		if ( $spreadsheet->field_map_is_empty() ) {
			gp_google_sheets()->log_debug( __METHOD__ . '(): Cannot insert a test row. There is no field map.' );
			wp_send_json_error( array(
				'message' => __( ' Choose at least one field to send in the Column Mapping section.', 'gp-google-sheets' ),
			) );
		}

		//Look at the sheet, make sure our field map is still accurate
		$feed = gp_google_sheets()->maybe_update_field_map_setting( (string) $feed_id, $form_id );

		$row_data = $spreadsheet->create_row_data( array(), true );

		gp_google_sheets()->log_debug( __METHOD__ . '(): Inserting a test row' );

		try {
			$spreadsheet->append_rows( $row_data );

			wp_send_json_success();
		} catch ( \Exception $ex ) {
			gp_google_sheets()->log_debug( __METHOD__ . '(): ' . $ex->getMessage() );
			wp_send_json_error( array(
				'message' => $error_message . ' ' . $ex->getMessage(),
			) );
		}
	}

	public static function ajax_select_sheet() {
		self::check_nonce_and_caps(
			__( 'There was an error selecting the sheet. Double check that you have permissions to use GP Google Sheets and try again.', 'gp-google-sheets' ),
			null,
			gp_google_sheets()->get_capabilities( 'form_settings' ),
		);

		$fake_feed   = self::create_feed_from_get();
		$spreadsheet = Spreadsheet::from_feed( $fake_feed );

		if ( ! $spreadsheet ) {
			wp_send_json_error( array(
				'message' => __( 'Could not connect to Google Sheets. Please check your credentials and try again.', 'gp-google-sheets' ),
			) );
		}

		wp_send_json_success( array(
			'controlsHTML' => self::force_field_markup_field_map( $fake_feed, $spreadsheet ),
		) );
	}

	/**
	 * @param array $fake_feed
	 * @param Spreadsheet $spreadsheet
	 */
	protected static function force_field_markup_field_map( $fake_feed, $spreadsheet ) {
		$fake_field = array(
			'type'      => 'generic_map',
			'name'      => 'column_mapping',
			'key_field' => array(
				'title'       => __( 'Sheet Column', 'gp-google-sheets' ),
				'placeholder' => __( 'Column heading', 'gp-google-sheets' ),
				'choices'     => $spreadsheet->field_map_key_field_choices(),
			),
		);

		$renderer = new \Gravity_Forms\Gravity_Forms\Settings\Settings(
			array(
				'capability'     => gp_google_sheets()->get_capabilities( 'form_settings' ),
				'fields'         => array(),
				'initial_values' => $fake_feed['meta'],
			)
		);

		gp_google_sheets()->set_settings_renderer( $renderer );
		$html = gp_google_sheets()->settings_generic_map( $fake_field, false );

		//Remove Windows line breaks and whitespace
		$html = str_replace(
			"\n\t\t\t\t",
			'',
			$html
		);

		return $html;
	}

	protected static function create_feed_from_get() {
		$feed_id = intval( $_GET['feed_id'] );

		if ( $feed_id === 0 ) {
			$feed['meta']['token'] = (array) json_decode( stripslashes( sanitize_text_field( rgget( 'token' ) ) ) );
		} else {
			$feed = gp_google_sheets()->get_feed( $feed_id );

			if ( ! $feed ) {
				wp_send_json_error(array(
					'message' => __( 'Feed not found.', 'gp-google-sheets' ),
				));
			}
		}

		$feed['meta']['google_sheet_url'] = sanitize_text_field( rgget( 'sheet_url' ) );
		$feed['meta']['google_sheet_id']  = sanitize_text_field( rgget( 'sheet_id' ) );

		return $feed;
	}
}
