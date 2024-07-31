<?php
namespace GP_Google_Sheets\Spreadsheets\Traits;

defined( 'ABSPATH' ) or exit;

use \GP_Google_Sheets\Dependencies\Google\Service\Sheets\DeveloperMetadataLookup as Google_Service_Sheets_DeveloperMetadataLookup;
use \GP_Google_Sheets\Dependencies\Google\Service\Sheets\DataFilter as Google_Service_Sheets_DataFilter;
use \GP_Google_Sheets\Dependencies\Google\Service\Sheets\SearchDeveloperMetadataRequest as Google_Service_Sheets_SearchDeveloperMetadataRequest;

/**
 * This class finds row and column indexes in a Google Sheet row using cell
 * values and developer metadata.
 */
trait Reader {

	/**
	 * find_first_empty_column_index
	 *
	 * @return int|false Returns 0-based column index, -1 if there are no empty columns, or false if cannot connect
	 */
	public function find_first_empty_column_index() {
		//read_range() doesn't return empty cells
		$cells        = $this->read_range( $this->get_sheet_name() );
		$column_count = $this->column_count();

		//Are there any columns that have no value in row 1?
		if ( is_array( $cells ) ) {
			if ( sizeof( $cells[0] ) == sizeof( array_filter( $cells[0] ) )
				&& sizeof( $cells[0] ) == $column_count ) {
				//No
				return -1;
			}

			for ( $c = 0; $c < sizeof( $cells[0] ); $c++ ) {
				$column_is_empty = true;
				if ( isset( $cells[0][ $c ] ) && $cells[0][ $c ] == '' ) {
					//$c has no value in row 1, check the rest of the rows
					for ( $r = 0; $r < sizeof( $cells ); $r++ ) {
						//Is this row empty in column $c?
						if ( isset( $cells[ $r ][ $c ] ) && $cells[ $r ][ $c ] != '' ) {
							//No, there's a value in row $r+1
							$column_is_empty = false;
							break;
						}
					}
					if ( $column_is_empty ) {
						return $c;
					}
				}
			}
			if ( sizeof( $cells[0] ) < $column_count ) {
				return sizeof( $cells[0] );
			}
		} elseif ( $column_count > 0 ) {
			//Blank sheet with empty columns
			//If sheet is brand new, $column_count is 26
			return 0;
		}

		return -1;
	}

	/**
	 * How many columns are in the Sheet? Read the first row.
	 */
	public function get_first_row() {
		$range = '1:1';

		$range = $this->prepare_range( $range );

		return $this->read_range( $range );
	}


	/**
	 * find_row_by_metadata_value
	 *
	 * Finds the zero-based row index that contains the provided value.
	 *
	 * @param  string $value The metadata value to find
	 *
	 * @return int|false Zero-based row index
	 */
	public function find_row_by_metadata_value( $value ) {
		try {
			$metadata = $this->metadata_map_rows();

			if ( ! $metadata ) {
				return false;
			}

			foreach ( $metadata as $index => $entry_id ) {
				if ( $value == $entry_id ) {
					return $index;
				}
			}
		} catch ( \Exception $e ) {
			gp_google_sheets()->log_error( __METHOD__ . '(): Unable to find row by metadata value: ' . $e->getMessage() );
			return false;
		}

		return false;
	}

	/**
	 * metadata_field_map
	 *
	 * Creates an associative array where the keys are column indices and the
	 * values are Gravity Forms field IDs. Used to rearrange arrays of row data
	 * before writes in case the position of columns has been changed in the
	 * Sheet.
	 *
	 * @return array|false Associative array with column index keys and Gravity Forms field ID values
	 */
	public function metadata_field_map() {
		$feed    = $this->get_feed();
		$feed_id = $feed['id'];

		$cache_scope = $this->get_sheet_id() . '_' . $feed_id;

		if ( $this->get_cache( 'metadata_field_map', $cache_scope ) ) {
			return $this->get_cache( 'metadata_field_map', $cache_scope );
		}

		/**
		 * @var \GP_Google_Sheets\Dependencies\Google\Service\Sheets\Resource\SpreadsheetsDeveloperMetadata|null
		 */
		$developer_metadata = $this->get_sheets_resource( 'spreadsheets_developerMetadata' );

		if ( ! $developer_metadata ) {
			gp_google_sheets()->log_error( 'Could not get Google Spreadsheets DeveloperMetadata Resource.' );
			return false;
		}

		$lookup_legacy = new Google_Service_Sheets_DeveloperMetadataLookup();
		$lookup_legacy->setMetadataKey( $this->column_key_legacy() );

		$dataFilter_legacy = new Google_Service_Sheets_DataFilter();
		$dataFilter_legacy->setDeveloperMetadataLookup( $lookup_legacy );

		$lookup = new Google_Service_Sheets_DeveloperMetadataLookup();
		$lookup->setMetadataKey( $this->column_key() );

		$dataFilter = new Google_Service_Sheets_DataFilter();
		$dataFilter->setDeveloperMetadataLookup( $lookup );

		$search = new Google_Service_Sheets_SearchDeveloperMetadataRequest();
		$search->setDataFilters( array( $dataFilter_legacy, $dataFilter ) );

		try {
			$response = $developer_metadata->search( $this->get_id(), $search );

			$found_metadata = $response->getMatchedDeveloperMetadata();

			if ( empty( $found_metadata ) ) {
				$this->set_cache( 'metadata_field_map', array(), $cache_scope );
				return array();
			}

			$metadata = array();
			foreach ( $found_metadata as $metadata_obj ) {
				$location     = $metadata_obj->getDeveloperMetadata()->getLocation();
				$range        = $location->getDimensionRange();
				$column_index = (int) $range->getStartIndex();
				$value        = $metadata_obj->getDeveloperMetadata()->getMetadataValue();

				// Skip metadata not in the current sheet
				if ( (string) $range->getSheetId() !== (string) $this->get_sheet_id() ) {
					continue;
				}

				if ( rgblank( $value ) ) {
					continue;
				}

				$metadata[ $column_index ] = strval( $metadata_obj->getDeveloperMetadata()->getMetadataValue() );
			}

			//sort by array keys to make debugging easy
			ksort( $metadata );
		} catch ( \Exception $e ) {
			gp_google_sheets()->log_error( __METHOD__ . '(): Unable to get field map: ' . $e->getMessage() );
			$metadata = array();
		}

		$this->set_cache( 'metadata_field_map', $metadata, $cache_scope );

		return $metadata;
	}

	/**
	 * Creates an associative array where the keys are row indices and the
	 * values are Gravity Forms entry IDs. Used to find entries in the sheet
	 * when editing or deleting.
	 *
	 * @return array|false Associative array with row index keys and Gravity Forms entry ID values
	 *
	 * @throws \Exception
	 */
	public function metadata_map_rows() {
		/**
		 * @var \GP_Google_Sheets\Dependencies\Google\Service\Sheets\Resource\SpreadsheetsDeveloperMetadata|null
		 */
		$developer_metadata = $this->get_sheets_resource( 'spreadsheets_developerMetadata' );

		if ( ! $developer_metadata ) {
			gp_google_sheets()->log_error( 'Could not get Google Spreadsheets DeveloperMetadata Resource.' );
			return false;
		}

		$lookup_legacy = new Google_Service_Sheets_DeveloperMetadataLookup();
		$lookup_legacy->setMetadataKey( $this->row_key_legacy() );

		$dataFilter_legacy = new Google_Service_Sheets_DataFilter();
		$dataFilter_legacy->setDeveloperMetadataLookup( $lookup_legacy );

		$lookup = new Google_Service_Sheets_DeveloperMetadataLookup();
		$lookup->setMetadataKey( $this->row_key() );

		$dataFilter = new Google_Service_Sheets_DataFilter();
		$dataFilter->setDeveloperMetadataLookup( $lookup );

		$search = new Google_Service_Sheets_SearchDeveloperMetadataRequest();
		$search->setDataFilters( array( $dataFilter_legacy, $dataFilter ) );

		/** @throws \Exception */
		$response = $developer_metadata->search( $this->get_id(), $search );

		$found_metadata = $response->getMatchedDeveloperMetadata();

		if ( empty( $found_metadata ) ) {
			return array();
		}

		$metadata = array();
		foreach ( $found_metadata as $metadata_obj ) {
			$location                                  = $metadata_obj->getDeveloperMetadata()->getLocation();
			$range                                     = $location->getDimensionRange();
			$metadata[ (int) $range->getStartIndex() ] = strval( $metadata_obj->getDeveloperMetadata()->getMetadataValue() );
		}

		//sort by array keys to make debugging easy
		ksort( $metadata );

		return $metadata;
	}

	/**
	 * prepare_range
	 *
	 * Turns a range like "1:1" into "Sheet1!1:1" after connecting to the Sheet and
	 * finding the tab where our data is stored.
	 *
	 * @param  mixed $range
	 *
	 * @return void|string
	 */
	public function prepare_range( $range ) {
		$sheet_name = $this->get_sheet_name();

		if ( ! $sheet_name ) {
			return $range;
		}

		$preamble = $sheet_name . '!';

		if ( $preamble != substr( $range, 0, strlen( $preamble ) ) ) {
			$range = $preamble . $range;
		}

		return $range;
	}

	public function column_count() {
		$sheet      = $this->get_sheet();
		$props      = $sheet->getProperties();
		$grid_props = $props->getGridProperties();

		return $grid_props->getColumnCount();
	}

	/**
	 * read_range
	 *
	 * Connects to a Sheet, reads a cell range, and returns a two-dimensional
	 * array of the values.
	 *
	 * @param  string $range A Google Sheet cell range like A1 or A1:B1 that describes where to write $rows
	 *
	 * @return array[]|false A two-dimensional array of values.
	 */
	public function read_range( $range ) {
		$cache_key = 'range_' . $range;

		if ( $this->get_cache( $cache_key, $this->get_sheet_id() ) ) {
			return $this->get_cache( $cache_key, $this->get_sheet_id() );
		}

		/**
		 * @var \GP_Google_Sheets\Dependencies\Google\Service\Sheets\Resource\SpreadsheetsValues|null
		 */
		$spreadsheets_values = $this->get_sheets_resource( 'spreadsheets_values' );

		if ( ! $spreadsheets_values ) {
			gp_google_sheets()->log_error( __METHOD__ . '(): Unable to read range. Account not available.' );
			return false;
		}

		try {
			$result = $spreadsheets_values->get( $this->id, $range );

			$values = $result->getValues();

			$this->set_cache( $cache_key, $values, $this->get_sheet_id() );

			return $values;
		} catch ( \Exception $ex ) {
			gp_google_sheets()->log_error( __METHOD__ . '(): Unable to read range: ' . $ex->getMessage() );

			return false;
		}
	}
}
