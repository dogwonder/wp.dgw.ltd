<?php
namespace GP_Google_Sheets\Spreadsheets\Traits;

defined( 'ABSPATH' ) or exit;

use \GP_Google_Sheets\Dependencies\Google\Service\Sheets\DimensionRange as Google_Service_Sheets_DimensionRange;
use \GP_Google_Sheets\Dependencies\Google\Service\Sheets\Request as Google_Service_Sheets_Request;
use \GP_Google_Sheets\Dependencies\Google\Service\Sheets\BatchUpdateSpreadsheetRequest as Google_Service_Sheets_BatchUpdateSpreadsheetRequest;
use \GP_Google_Sheets\Dependencies\Google\Service\Sheets\ValueRange as Google_Service_Sheets_ValueRange;
use \GP_Google_Sheets\Dependencies\Google\Service\Sheets\AppendDimensionRequest as Google_Service_Sheets_AppendDimensionRequest;
use \GP_Google_Sheets\Dependencies\Google\Service\Sheets\GridRange as Google_Service_Sheets_GridRange;
use \GP_Google_Sheets\Dependencies\Google\Service\Sheets\CellData as Google_Service_Sheets_CellData;
use \GP_Google_Sheets\Dependencies\Google\Service\Sheets\RowData as Google_Service_Sheets_RowData;
use \GP_Google_Sheets\Dependencies\Google\Service\Sheets\UpdateCellsRequest as Google_Service_Sheets_UpdateCellsRequest;
use \GP_Google_Sheets\Dependencies\Google\Service\Sheets\ExtendedValue as Google_Service_Sheets_ExtendedValue;
use \GP_Google_Sheets\Dependencies\Google\Service\Sheets\DeleteDimensionRequest as Google_Service_Sheets_DeleteDimensionRequest;
use GP_Google_Sheets\Spreadsheets\Range_Parser;


/**
 * This class reads and writes data to Google Sheets using the PHP
 */
trait Writer {
	/**
	 * @return int|\WP_Error Zero-based column index of the new column
	 */
	public function append_column() {
		$spreadsheet_id = $this->get_id();
		$account        = $this->get_google_account();
		$sheet_id       = $this->get_sheet_id();
		$sheet_name     = $this->get_sheet_name();

		$body = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest();
		$body->setRequests( array(
			self::create_append_columns_request( $sheet_id ),
		) );

		$spreadsheets = $account->google_sheets_service->spreadsheets;

		try {
			$spreadsheets->batchUpdate( $spreadsheet_id, $body );
		} catch ( \Exception $e ) {
			return new \WP_Error( $e->getCode(), $e->getMessage() );
		}

		$cells = $this->read_range( $sheet_name );
		return sizeof( isset( $cells[0] ) ? $cells[0] : array() ); //no -1 here because we are not getting a response for the new column, it's empty
	}

	/**
	 * @param  array $row_data An array with keys 'columns' and 'rows'. The columns member is an array of column headers keyed by field IDs, and rows is an array of arrays making up the entry data to populate the columns.
	 *
	 * @throws \Exception
	 *
	 * @return string
	 */
	public function append_rows( array $row_data ) {
		if ( empty( $row_data ) || ! is_array( $row_data ) ) {
			throw new \Exception( __( 'Unable to write to Google Sheets as row data is empty.', 'gp-google-sheets' ) );
		}

		if ( empty( $row_data['columns'] ) ) {
			throw new \Exception( __( 'Unable to write to Google Sheets as there are no columns specified for the request.', 'gp-google-sheets' ) );
		}

		if ( empty( $row_data['rows'] ) ) {
			throw new \Exception( __( 'Unable to write to Google Sheets as there are no rows specified for the request.', 'gp-google-sheets' ) );
		}

		/**
		 * @var \GP_Google_Sheets\Dependencies\Google\Service\Sheets\Resource\SpreadsheetsValues|null
		 */
		$spreadsheets_values = $this->get_sheets_resource( 'spreadsheets_values' );

		if ( ! $spreadsheets_values ) {
			throw new \Exception( 'Could not get Google Spreadsheets Values Resource.' );
		}

		$field_map = $this->metadata_field_map();

		if ( $field_map === false ) {
			throw new \Exception( 'Missing field map.' );
		}

		/*
		 * https://issuetracker.google.com/issues/36760568#comment8
		 * https://issuetracker.google.com/issues/36760568#comment11
		 */
		$range = 'A:A';

		$sheet_name = $this->get_sheet_name();

		/**
		 * Add the sheet name to the beginning of the "A1" notation.
		 * This is how you tell the Google Sheets API to write to a specific sheet (rather than defaulting to the first sheet).
		 */
		if ( $sheet_name !== false ) {
			$range = "{$sheet_name}!{$range}";
		}

		$request_body = new Google_Service_Sheets_ValueRange(array(
			'majorDimension' => 'ROWS',
			'range'          => $range,
			'values'         => $row_data['rows'],
		));

		$params = array(
			'valueInputOption' => self::are_user_entered_values_allowed( $this->get_feed(), $row_data['rows'], $row_data['columns'] )
				? 'USER_ENTERED'
				: 'RAW',
			'insertDataOption' => 'INSERT_ROWS', //add rows, never overwrite
		);

		/**
		 * See table at the bottom of https://developers.google.com/sheets/api/guides/values#append_values to get an
		 * idea of how Google appends values.
		 */
		$result = $spreadsheets_values->append( $this->get_id(), $range, $request_body, $params );

		//Write row metadata containing the entry ID
		$updated_range = $result->getUpdates()->getUpdatedRange(); //"Sheet1!A26:AF26"

		return $updated_range;
	}

	/**
	 * Add entry ID's to rows in the spreadsheet so that we can match entries to sheet rows later on.
	 *
	 * @param array $feed the current feed.
	 * @param  array $row_data An array with keys 'columns' and 'rows'. The columns member is an array of column headers keyed by field IDs, and rows is an array of arrays making up the entry data to populate the columns.
	 * @param $updated_range string The range of cells that were updated by the append_rows() method.
	 *
	 * @throws \Exception
	 *
	 * @return void
	 */
	public function add_entry_id_to_row_metadata( $feed, $row_data, $updated_range ) {
		/**
		 * @var \GP_Google_Sheets\Dependencies\Google\Service\Sheets\Resource\Spreadsheets|null
		 */
		$spreadsheets = $this->get_sheets_resource( 'spreadsheets' );

		if ( ! $spreadsheets ) {
			gp_google_sheets()->log_error( __METHOD__ . '(): Could not get Google Spreadsheets Resource.' );
			throw new \Exception( 'Could not get Google Spreadsheets Resource.' );
		}

		//Match the row numbers in the range. Expects something like Sheet1!A2 or Sheet1!A3:C3
		if ( preg_match( '/.*![^0-9]+([0-9]+)(?:[^0-9]+([0-9]+))?$/', $updated_range, $matches ) ) {
			$offset = (int) $matches[1];

			if ( empty( $matches[2] ) ) {
				$matches[2] = $offset;
			}

			for ( $sheet_row = $offset - 1; $sheet_row < ( (int) $matches[2] ); $sheet_row++ ) {
				$row_index = $sheet_row - ( $offset - 1 ); //zero the first time
				$entry_id  = $row_data['entry_ids'][ $row_index ];

				$body = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest();
				$body->setRequests( array(
					$this->create_request_write_row( $sheet_row, $entry_id ),
				) );

				try {
					$spreadsheets->batchUpdate( $this->get_id(), $body );
				} catch ( \Exception $e ) {
					gp_google_sheets()->log_error( __METHOD__ . '(): Could not write entry ID to row metadata: ' . $e->getMessage() );
					throw new \Exception( 'Could not write entry ID to row metadata: ' . $e->getMessage() );
				}
			}
		}
	}

	/**
	 * Convenience wrapper for the gpgs_allow_user_entered_values filter since we use it in several locations.
	 */
	public function are_user_entered_values_allowed( $feed, $rows, $columns = array() ) {
		/**
		 * Allows formula values, dates, numbers, currency, etc to be written to a sheet instead of raw strings.
		 *
		 * @reference https://developers.google.com/sheets/api/reference/rest/v4/ValueInputOption
		 *
		 * @param bool  $allow_user_entered_values Whether or not to store formatted values or raw strings. (default is false which stores raw strings)
		 * @param array $feed The feed being processed.
		 * @param array $rows The rows being written to the Sheet.
		 * @param array $columns The column mappings as configured for this feed.
		 */
		return gf_apply_filters( array( 'gpgs_allow_user_entered_values', $feed['form_id'] ), false, $feed, $rows, $columns );
	}

	/**
	 * create_append_columns_request
	 *
	 * Creates a Google_Service_Sheets_Request that will append any number of
	 * columns to a Sheet.
	 *
	 * @param  int $sheet_id
	 * @param  int $number_of_columns_to_add
	 * @return Google_Service_Sheets_Request
	 */
	private function create_append_columns_request( $sheet_id, $number_of_columns_to_add = 1 ) {
		$append_dimension = new Google_Service_Sheets_AppendDimensionRequest();
		$append_dimension->setSheetId( $sheet_id );
		$append_dimension->setDimension( 'COLUMNS' );
		$append_dimension->setLength( $number_of_columns_to_add );

		$request = new Google_Service_Sheets_Request();
		$request->setAppendDimension( $append_dimension );

		return $request;
	}

	/**
	 * create_row_dimension_range
	 *
	 * Creates a Google_Service_Sheets_DimensionRange that identifies a single
	 * row.
	 *
	 * @param  int $row_number
	 * @param  int $sheet_id
	 * @return Google_Service_Sheets_DimensionRange
	 */
	private function create_row_dimension_range( $row_number, $sheet_id ) {
		$range = new Google_Service_Sheets_DimensionRange();
		$range->setSheetId( $sheet_id ); //which tab in the Sheet
		$range->setDimension( 'ROWS' );
		$range->setStartIndex( $row_number - 1 );
		$range->setEndIndex( $row_number );
		return $range;
	}

	/**
	 * create_write_rows_request
	 *
	 * @param  string $range A Google Sheet cell range like A1 or A1:B1 that describes where to write $rows
	 * @param  array $rows An array of arrays containing the cell contents to write

	 * @return Google_Service_Sheets_Request
	 */
	public function create_write_rows_request( $range, $rows ) {
		$grid_range = new Google_Service_Sheets_GridRange();
		$grid_range->setSheetId( $this->get_sheet_id() );
		$grid_range->setStartRowIndex( Range_Parser::start_row_index( $range ) );
		$grid_range->setEndRowIndex( Range_Parser::end_row_index( $range, sizeof( $rows ) ) );
		$grid_range->setStartColumnIndex( Range_Parser::start_column_index( $range ) );
		$grid_range->setEndColumnIndex( Range_Parser::end_column_index( $range, sizeof( $rows[0] ) ) );

		$insert_user_entered_values = self::are_user_entered_values_allowed( $this->get_feed(), $rows );

		$formatted_rows = array();
		for ( $r = 0; $r < sizeof( $rows ); $r++ ) {
			$formatted_row = array();
			for ( $c = 0; $c < sizeof( $rows[ $r ] ); $c++ ) {
				$value = new Google_Service_Sheets_ExtendedValue();

				if ( is_bool( $rows[ $r ][ $c ] ) ) {
					$value->setBoolValue( $rows[ $r ][ $c ] );
				} elseif ( is_numeric( $rows[ $r ][ $c ] ) ) {
					$value->setNumberValue( $rows[ $r ][ $c ] );
				} elseif ( $insert_user_entered_values && strpos( $rows[ $r ][ $c ], ' = ' ) === 0 ) {
					// This condition should only occur if the user has the "user entered values" filter enabled.
					// If it is not enabled, then the value should be stored as a raw string in the Sheet.
					$value->setFormulaValue( $rows[ $r ][ $c ] );
				} else {
					$value->setStringValue( $rows[ $r ][ $c ] );
				}

				$cell_data = new Google_Service_Sheets_CellData();
				$cell_data->setUserEnteredValue( $value );
				$formatted_row[] = $cell_data;
			}
			$formatted_rows[] = $formatted_row;
		}

		$row_data = new Google_Service_Sheets_RowData();
		$row_data->setValues( $formatted_rows );

		$update = new Google_Service_Sheets_UpdateCellsRequest();
		$update->setRange( $grid_range );
		$update->setRows( array( $row_data ) );
		$update->setFields( 'userEnteredValue' );

		$request = new Google_Service_Sheets_Request();
		$request->setUpdateCells( $update );

		return $request;
	}

	/**
	 * delete_row
	 *
	 * Deletes an entire row from the Sheet
	 *
	 * @param  int $row_number The number of the row to delete, with 1 meaning the first row in the Sheet
	 *
	 * @return void
	 *
	 * @throws \Exception
	 */
	public function delete_row( $row_number = 1 ) {
		/**
		 * @var \GP_Google_Sheets\Dependencies\Google\Service\Sheets\Resource\Spreadsheets|null
		 */
		$spreadsheets = $this->get_sheets_resource( 'spreadsheets' );

		if ( ! $spreadsheets ) {
			gp_google_sheets()->log_error( __METHOD__ . '(): Could not get Google Spreadsheets Resource.' );
			throw new \Exception( 'Could not get Google Spreadsheets Resource.' );
		}

		$delete = new Google_Service_Sheets_DeleteDimensionRequest();
		$delete->setRange( $this->create_row_dimension_range( $row_number, $this->get_sheet_id() ) );

		$request = new Google_Service_Sheets_Request();
		$request->setDeleteDimension( $delete );

		$body = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest();
		$body->setRequests( array( $request ) );

		$spreadsheets->batchUpdate( $this->get_id(), $body );
	}

	/**
	 * Edits a row in a given spreadsheet
	 *
	 * @param int $row_index
	 * @param array $row_data The row data to update in the sheet
	 *
	 * @throws \Exception
	 *
	 * @return void
	 */
	public function edit_row( $row_index, $row_data ) {

		// Sort columns to ensure correct we get first and last array keys correctly on sorted data.
		ksort( $row_data['columns'] );

		$range = sprintf(
			'%s%s:%s%s',
			gpgs_number_to_column_letters( gpgs_array_key_first( $row_data['columns'] ) + 1 ),
			$row_index + 1,
			gpgs_number_to_column_letters( gpgs_array_key_last( $row_data['columns'] ) + 1 ),
			$row_index + 1
		);

		$prepared_range = $this->prepare_range( $range );

		/**
		 * @var \GP_Google_Sheets\Dependencies\Google\Service\Sheets\Resource\Spreadsheets|null
		 */
		$spreadsheets = $this->get_sheets_resource( 'spreadsheets' );

		if ( ! $spreadsheets ) {
			gp_google_sheets()->log_error( __METHOD__ . '(): Could not get Google Spreadsheets Resource.' );
			throw new \Exception( 'Could not get Google Spreadsheets Resource.' );
		}

		/**
		 * Are we missing columns in $row_data? If users have added columns to
		 * the Sheet, $row_data['rows'][0] might be missing some array keys. The
		 * result will be that user-entered data in the Sheet will be replaced
		 * with empty strings. We do not yet know what those columns may
		 * contain, but we are rewriting the whole row.
		 */
		$fetched_rows = array();
		for ( $r = 0; $r < sizeof( $row_data['rows'] ); $r++ ) {
			for ( $c = 0; $c < sizeof( $row_data['rows'][ $r ] ); $c++ ) {
				/** @phpstan-ignore-next-line */
				if ( ! isset( $row_data['rows'][ $r ][ $c ] ) || $row_data['rows'][ $r ][ $c ] === null ) {
					//Yes, we're missing a column
					//Have we downloaded a copy of this row's cells?
					if ( ! isset( $fetched_rows[ $r ] ) ) {
						//No
						$result = $this->read_range( $prepared_range );
						if ( is_array( $result ) ) {
							$fetched_rows[ $r ] = $result[0];
						}
					}

					if ( isset( $fetched_rows[ $r ][ $c ] ) ) {
						$row_data['rows'][ $r ][ $c ] = $fetched_rows[ $r ][ $c ];
						//re-order the row data we just edited so the keys/columns are in order
						ksort( $row_data['rows'][ $r ] );
					}
				}
			}
		}

		$request = $this->create_write_rows_request( $range, $row_data['rows'] );

		//Run our cell write and metadata write requests in one batch call
		$batch = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest();
		$batch->setRequests( array( $request ) );
		$batch->setIncludeSpreadsheetInResponse( false );
		$batch->setResponseIncludeGridData( false );

		/** @throws \Exception */
		$spreadsheets->batchUpdate( $this->get_id(), $batch );
	}
}
