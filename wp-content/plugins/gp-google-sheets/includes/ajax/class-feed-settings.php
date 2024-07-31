<?php
namespace GP_Google_Sheets\AJAX;

defined( 'ABSPATH' ) or exit;

use GP_Google_Sheets\Spreadsheets\Spreadsheet;
use \GP_Google_Sheets\Dependencies\Fuse;


class Feed_Settings extends AJAX {

	public static function hooks() {
		//Handle an AJAX call behind the Disconnect buttons
		add_action( 'wp_ajax_gpgs_disconnect', array( __CLASS__, 'disconnect' ) );

		//Handle an AJAX call behind the Insert Test Row button
		add_action( 'wp_ajax_gpgs_insert_test_row', array( __CLASS__, 'insert_test_row' ) );

		// Endpoint to get column mappings HTML and Javascript.
		add_action( 'wp_ajax_gpgs_get_column_mappings_html', array( __CLASS__, 'ajax_get_column_mappings_html' ) );
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

	public static function ajax_get_column_mappings_html() {
		self::check_nonce_and_caps(
			__( 'There was an error accessing the sheet. Double check that you have permissions to use GP Google Sheets and try again.', 'gp-google-sheets' ),
			null,
			gp_google_sheets()->get_capabilities( 'form_settings' ),
		);

		$fake_feed = self::create_feed_from_get();
		// Choices for the key field in the column mapping.
		$choices = array();
		// Message used to show an alert in the browser if needed. (e.g. if no Sheet columns were matched with form fields)
		$alert_message = null;

		switch ( rgget( 'variant' ) ) {
			case 'from_current_feed_mappings':
				/**
				 * Get the column mappings markup based on the current feed meta.
				 *
				 * This will return the column mappings markup based on the current feed meta.
				 */

				$spreadsheet = Spreadsheet::from_feed( $fake_feed );
				$choices     = $spreadsheet->field_map_key_field_choices();

				if ( ! $spreadsheet ) {
					wp_send_json_error( array(
						'message' => __( 'Could not connect to Google Sheets. Please check your credentials and try again.', 'gp-google-sheets' ),
					) );
				}
				break;
			case 'from_form_fields':
				/**
				 * Get column mappings markup based on the fields of the form.
				 *
				 * There will be one mapping for each form field that can be validated (e.g. section
				 * and page fields are omitted.)
				 * For example: this can used for the initial feed setup as an optional time saving step.
				 */

				$column_mappings                     = self::column_mappings_from_form_fields( $fake_feed );
				$fake_feed['meta']['column_mapping'] = $column_mappings;
				break;
			case 'from_sheet_columns':
				/**
				 * Get column mappings markup based on the column headers of the connecte Sheet.
				 *
				 * This attempts to fuzzy map column headers to form fields. If a match is found,
				 * a mapping row is automatically added to the markup.
				 */

				$spreadsheet = Spreadsheet::from_feed( $fake_feed );
				$choices     = $spreadsheet->field_map_key_field_choices();

				$column_mappings = self::column_mappings_from_sheet_columns(
					$fake_feed,
					$spreadsheet
				);

				$fake_feed['meta']['column_mapping'] = $column_mappings;

				$column_mapping = rgars( $fake_feed, 'meta/column_mapping' );
				if ( empty( $column_mapping ) ) {
					$alert_message = __( 'No Google Sheet columns were matched with form fields. Please manually map the columns.', 'gp-google-sheets' );
				}

				if ( ! $spreadsheet ) {
					wp_send_json_error( array(
						'message' => __( 'Could not connect to Google Sheets. Please check your credentials and try again.', 'gp-google-sheets' ),
					) );
				}
				break;
			default:
				wp_send_json_error( array(
					'message' => __( 'Incorrect `variant` sent with request.', 'gp-google-sheets' ),
				) );
		}

		wp_send_json_success( array(
			'controlsHTML' => self::get_column_mapping_setting_markup( $fake_feed, $choices ),
			'alertMessage' => $alert_message,
		) );
	}

	/**
	 * Fitlers fields that don't have an associated value. This is used to filter
	 * out fields such as sections, pages, etc.
	 */
	protected static function filter_non_value_fields( $fields ) {
		$filtered = array();
		foreach ( $fields as $field ) {
			if ( \GFFormDisplay::is_field_validation_supported( $field ) ) {
				$filtered[] = $field;
			}
		}

		return $filtered;
	}

	/**
	 * Fuzzy matches spreadsheet column headings with fields in the form and then updates
	 * the feed meta accordingly. This is used for automating creation of column mappings.
	 *
	 * @param array $feed
	 * @param Spreadsheet $spreadsheet
	 */
	protected static function column_mappings_from_sheet_columns( $feed, $spreadsheet ) {
		$form   = \GFAPI::get_form( $feed['form_id'] );
		$fields = self::filter_non_value_fields( $form['fields'] );

		$column_mappings = array();

		list( $header_row ) = $spreadsheet->get_first_row();
		foreach ( $header_row as $idx => $val ) {
			$options             = array(
				'keys'            => array( 'label', 'adminLabel' ),
				'shouldSort'      => true,
				'includeScore'    => true,
				'threshold'       => 0.5,
				'ignoreLocation'  => true,
				'findAllMatches'  => true,
				'isCaseSensitive' => true,
			);
			$fuse                = new Fuse\Fuse( $fields, $options );
			list( $first_match ) = $fuse->search( $val );

			if ( $first_match ) {
				$field = $first_match['item'];

				// add 1 to $idx as Google Sheets columns are 1-indexed
				$letters = gpgs_number_to_column_letters( $idx + 1 );

				$column_mappings[] = array(
					'key'          => $letters,
					'custom_key'   => $val,
					'value'        => $field['id'],
					'custom_value' => '',
				);
			}
		}
		return $column_mappings;
	}

	protected static function column_mappings_from_form_fields( $feed ) {
		$form            = \GFAPI::get_form( $feed['form_id'] );
		$fields          = self::filter_non_value_fields( $form['fields'] );
		$column_mappings = array();

		foreach ( $fields as $field ) {
			$column_mappings[] = array(
				'key'          => 'gf_custom',
				'custom_key'   => $field['label'],
				'value'        => $field['id'],
				'custom_value' => '',
			);
		}

		return $column_mappings;
	}

	/**
	 * @param array $fake_feed
	 * @param array $choices
	 */
	protected static function get_column_mapping_setting_markup( $fake_feed, $choices = array() ) {
		$fake_field = array(
			'type'      => 'generic_map',
			'name'      => 'column_mapping',
			'key_field' => array(
				'title'       => __( 'Sheet Column', 'gp-google-sheets' ),
				'placeholder' => __( 'Column heading', 'gp-google-sheets' ),
				'choices'     => $choices,
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

		// Add the feed id to the feed manually if it's not already there. This is needed if the feed has not yet been saved in the DB.
		if ( empty( rgar( $feed, 'form_id' ) ) && rgget( 'id' ) ) {
			$feed['form_id'] = intval( rgget( 'id' ) );
		}

		$feed['meta']['google_sheet_url'] = sanitize_text_field( rgget( 'sheet_url' ) );
		$feed['meta']['google_sheet_id']  = sanitize_text_field( rgget( 'sheet_id' ) );

		return $feed;
	}
}
