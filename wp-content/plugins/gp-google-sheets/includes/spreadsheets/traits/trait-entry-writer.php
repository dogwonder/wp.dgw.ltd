<?php
namespace GP_Google_Sheets\Spreadsheets\Traits;

defined( 'ABSPATH' ) or exit;

use GFAPI;
use GFCommon;
use GFExport;
use GP_Google_Sheets\Spreadsheets\Range_Parser;
use RGFormsModel;

trait Entry_Writer {

	/**
	 * Creates an array structure including column labels and at least one row
	 * of values that can be passed to GP_Google_Sheets_Writer class methods as the data
	 * payload to add to a Google Sheet. Without an $entry_id, a single sample
	 * row will be populated into the rows member of the output.
	 *
	 * @param int|array $entries An entry ID, a GF_Entry object, or an array of GF_Entry objects from which to extract row data
	 * @param bool $for_append is row data being prepared for a append_rows() call? If so, we have to write empty strings in user columns
	 */
	public function create_row_data( $entries = array(), $for_append = false ) {
		// Ensure necessary Gravity Forms classes are loaded
		require_once GFCommon::get_base_path() . '/form_display.php';
		require_once GFCommon::get_base_path() . '/export.php';

		$row_data = array(
			'columns' => array(),
			'rows'    => array(),
		);

		//Look at the sheet, make sure our field map is still accurate
		$feed = $this->get_feed();
		$feed = gp_google_sheets()->maybe_update_field_map_setting( $feed['id'] );

		$form_id = $feed['form_id'];

		//Do we have a field map?
		if ( ! $this->field_map_is_empty() ) {
			//We have a field map
			$form = GFAPI::get_form( $form_id );

			//Add fields to the form like user agent & ip
			if ( $form !== false && ! empty( $form['fields'] ) ) {
				/**
				 * @see GFForms::select_export_form()
				 */
				$form = GFExport::add_default_export_fields( $form );
			}

			if ( is_integer( $entries ) ) {
				$entries = array( GFAPI::get_entry( $entries ) );
			}

			//If the $entry is null, use $fields to populate junk into it
			if ( empty( $entries ) && ! empty( $form['fields'] ) ) {
				$fields  = $this->extract_field_ids( $form );
				$entries = array( $this->create_test_entry( $form, $fields ) );
			}

			if ( ! is_array( $entries ) ) {
				$entries = array( $entries );
			}

			for ( $e = 0; $e < sizeof( $entries ); $e++ ) {
				$values       = array();
				$column_index = $e;
				foreach ( $feed['meta']['column_mapping'] as $column ) {
					$column_index = Range_Parser::letters_to_index( $column['key'] );
					if ( $e == 0 ) {
						$row_data['columns'][ $column_index ] = array(
							'label' => $this->prepare_field_label( $form, $column['value'] ),
							'value' => $column['value'],
						);
					}
					$values[ $column_index ] = $this->prepare_entry_value( $entries[ $e ], $column, $form );
				}

				//Add entry ID, it gets written as developer metadata
				$row_data['entry_ids'][] = rgar( $entries[ $e ], 'id' );

				if ( $for_append ) {
					$row_data['rows'][] = array_values( gpgs_fill_missing_array_keys( $values, 0, '' ) );
				} else {
					$first_key_index    = gpgs_array_key_first( $values );
					$row_data['rows'][] = array_values( gpgs_fill_missing_array_keys( $values, $first_key_index, null ) );
				}
			}
		}

		/**
		 * Filter the data that is used to populate a row.
		 *
		 * @param array $row_data The data to use for population.
		 * @param array $feed The current feed.
		 * @param int $form_id The current form ID.
		 * @param array $entries Entries to be inserted.
		 *
		 * @since 1.0-beta-1
		 */
		return apply_filters( 'gpgs_sheet_row_data', $row_data, $feed, $form_id, $entries );
	}

	protected function prepare_entry_value( $entry, $column, $form ) {
		/*
		 * Run gform_pre_render on the form prior to getting the entry value that way plugins such as
		 * GP Populate Anything can manipulate the form and its choices beforehand.
		 */
		$form = gf_apply_filters( array( 'gform_pre_render', $form['id'] ), $form, false, $entry );

		$field_id = $column['value'];
		$value    = '';

		switch ( $field_id ) {
			case 'date_created':
			case 'payment_date':
			case 'entry_date':
				$value = $entry[ $field_id ];
				if ( $value ) {
					$lead_gmt_time   = mysql2date( 'G', $value );
					$lead_local_time = GFCommon::get_local_timestamp( $lead_gmt_time );
					$value           = date_i18n( 'Y-m-d H:i:s', $lead_local_time, true );
				}
				break;
			case 'entry_id':
			case 'user_ip':
			case 'created_by':
			case 'transaction_id':
			case 'payment_amount':
			case 'payment_status':
			case 'post_id':
			case 'user_agent':
				$value = $entry[ $field_id ];
				break;
			case 'form_title':
				$value = $form['title'];
				break;
			case 'gf_custom':
				$value = $column['custom_value'];
				$value = GFCommon::replace_variables( $value, $form, $entry, false, false, false, 'text' );
				break;
			default:
				$field = GFAPI::get_field( $form, $field_id );

				/*
				 * If the field is a checkbox, and we're getting the selected choices, we need to bypass
				 * $field->get_value_export() as it skips anything that's rgempty() instead of rgblank() which
				 * means 0's are skipped.
				 *
				 * This is essentially duplicated logic from GF_Field_Checkbox::get_value_export() but with
				 * the rgempty() replaced with rgblank() and an array_filter to remove empty values.
				 */
				if ( $field && $field->get_input_type() === 'checkbox' && $field_id == $field->id ) {
					$selected = array();

					foreach ( $field->inputs as $input ) {
						$index = (string) $input['id'];

						$selected[] = GFCommon::selection_display( rgar( $entry, $index ), $field, rgar( $entry, 'currency' ), false );
					}

					// Array filter $selected to remove empty values.
					$selected = array_filter( $selected, function( $value ) {
						return ! rgblank( $value );
					} );

					$value = apply_filters( 'gform_export_field_value', implode( ', ', $selected ), $form['id'], $field_id, $entry );
					break;
				}

				$value          = is_object( $field ) ? $field->get_value_export( $entry, $field_id, false, true ) : rgar( $entry, $field_id );
				$original_value = $value;

				/*
				 * Cast numbers to strings so Google inserts it as a number instead of string.
				 * This makes formulas work better and not require VALUE()
				 */
				if ( is_numeric( $value ) && ! gpgs_has_leading_zero( $value ) ) {
					$value = $value + 0;
				}

				/**
				 * Cast list values to a pipe-delimited string to better match Gravity Forms export behavior. This
				 * should only be the case if the $field_id is an integer which indicates that it's the full List field
				 * value.
				 *
				 * If a specific column is selected in the list field, then we want to get only that column's values
				 * and split them with a newline.
				 */
				if ( is_array( $value ) && $field->get_input_type() === 'list' ) {
					$list_value = '';

					// If the field ID is an integer, then it's the full List field value
					if ( ! strpos( (string) $field_id, '.' ) ) {
						if ( $field->enableColumns ) {
							foreach ( $value as $row ) {
								if ( is_array( $row ) ) {
									$list_value .= implode( '|', $row ) . "\n";
								} else {
									$list_value .= $row . '|';
								}
							}

							$value = rtrim( trim( $list_value ), '|' );
						} else {
							foreach ( $value as $row ) {
								$list_value .= $row . "\n";
							}
						}
					} else {
						// If the field ID contains a "." then it's a specific column in the List field
						$column = explode( '.', $field_id );
						$column = $column[1];

						foreach ( $value as $row ) {
							$row_values = array_values( $row );

							if ( is_array( $row ) ) {
								$list_value .= rgar( $row_values, $column ) . "\n";
							} else {
								$list_value .= $row . "\n";
							}
						}
					}

					$value = rtrim( $list_value, "\n" );
				}

				$value = apply_filters( 'gform_export_field_value', $value, $form['id'], $field_id, $entry );

				break;
		}

		if ( ! isset( $original_value ) ) {
			$original_value = $value;
		}

		/**
		 * Filter a value before it gets inserted into a row in Google Sheets.
		 *
		 * @param mixed $value The value to be inserted.
		 * @param int $form_id The current form ID.
		 * @param string $field_id The current field ID.
		 * @param array $entry The current entry.
		 * @param mixed $original_value The original value before any filters were applied.
		 *
		 * @since 1.0-beta-1.10
		 */
		$value = gf_apply_filters( array( 'gpgs_row_value', $form['id'], $field_id ), $value, $form['id'], $field_id, $entry, $original_value );

		return $value;
	}


	protected function prepare_field_label( $form, $field_id ) {
		$field = RGFormsModel::get_field( $form, $field_id );

		if ( $field !== null ) {
			$field->set_context_property( 'use_admin_label', '1' );
			$value = GFCommon::get_label( $field, $field_id );
			$value = gf_apply_filters( array( 'gform_entries_field_header_pre_export', $form['id'], $field_id ), $value, $form, $field );
		} else {
			//form_title
			$value = $field_id;
		}

		$value = str_replace( '"', '""', $value );

		if ( strpos( $value, '=' ) === 0 ) {
			// Prevent Excel formulas
			$value = "'" . $value;
		}
		return $value;
	}

	/**
	 * Returns an array containing all field IDs in $form
	 * @see GFForms::select_export_form()
	 */
	public function extract_field_ids( $form ) {
		$fields = array();

		//This code from Gravity Forms core, line 3781 of gravityforms.php
		if ( is_array( $form['fields'] ) ) {
			/* @var GF_Field $field */
			foreach ( $form['fields'] as $field ) {
				$inputs = $field->get_entry_inputs();
				if ( is_array( $inputs ) ) {
					foreach ( $inputs as $input ) {
						$fields[] = array( $input['id'], GFCommon::get_label( $field, $input['id'] ) );
					}
				} elseif ( ! $field->displayOnly ) {
					$fields[] = array( (string) $field->id, GFCommon::get_label( $field ) );
				}
			}
		}
		//This code from Gravity Forms core, line 3781 of gravityforms.php

		return array_column( $fields, '0' );
	}

}
