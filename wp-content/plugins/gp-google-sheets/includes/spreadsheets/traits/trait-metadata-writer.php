<?php
namespace GP_Google_Sheets\Spreadsheets\Traits;

defined( 'ABSPATH' ) or exit;

use \GP_Google_Sheets\Dependencies\Google\Service\Sheets\DeveloperMetadata as Google_Service_Sheets_DeveloperMetadata;
use \GP_Google_Sheets\Dependencies\Google\Service\Sheets\DeveloperMetadataLocation as Google_Service_Sheets_DeveloperMetadataLocation;
use \GP_Google_Sheets\Dependencies\Google\Service\Sheets\CreateDeveloperMetadataRequest as Google_Service_Sheets_CreateDeveloperMetadataRequest;
use \GP_Google_Sheets\Dependencies\Google\Service\Sheets\DimensionRange as Google_Service_Sheets_DimensionRange;
use \GP_Google_Sheets\Dependencies\Google\Service\Sheets\Request as Google_Service_Sheets_Request;
use \GP_Google_Sheets\Dependencies\Google\Service\Sheets\DeveloperMetadataLookup as Google_Service_Sheets_DeveloperMetadataLookup;
use \GP_Google_Sheets\Dependencies\Google\Service\Sheets\DataFilter as Google_Service_Sheets_DataFilter;
use \GP_Google_Sheets\Dependencies\Google\Service\Sheets\DeleteDeveloperMetadataRequest as Google_Service_Sheets_DeleteDeveloperMetadataRequest;

/**
 * This class reads and writes metadata to a Google Sheet
 */
trait Metadata_Writer {

	/**
	 * Developer metadata key for linking columns to feeds.
	 *
	 * @deprecated Use column_key() instead which reduces the amount of characters used.
	 */
	public function column_key_legacy() {
		$feed    = $this->get_feed();
		$feed_id = $feed['id'];

		return "feed_{$feed_id}";
	}

	/**
	 * Developer metadata key for linking rows to entry IDs.
	 *
	 * @deprecated Use row_key() instead which reduces the amount of characters used.
	 */
	public function row_key_legacy() {
		$feed    = $this->get_feed();
		$form_id = $feed['form_id'];

		return "form_{$form_id}_entry_id";
	}

	/**
	 * Developer metadata key for linking columns to feeds.
	 *
	 * "fe" here means "feed"
	 */
	public function column_key() {
		$feed    = $this->get_feed();
		$feed_id = $feed['id'];

		return "fe{$feed_id}";
	}

	/**
	 * Developer metadata key for linking rows to entry IDs.
	 *
	 * "f" here means "form"
	 */
	public function row_key() {
		$feed    = $this->get_feed();
		$form_id = $feed['form_id'];

		return "f{$form_id}";
	}

	/**
	 * create_location
	 *
	 * Creates a Google_Service_Sheets_DeveloperMetadataLocation object that
	 * identifies a single column or row.
	 *
	 * @param  string $dimension "COLUMNS" or "ROWS
	 * @param  int $column_index
	 *
	 * @return Google_Service_Sheets_DeveloperMetadataLocation
	 */
	protected function create_location( $dimension, $column_index ) {
		if ( ! in_array( $dimension, array( 'COLUMNS', 'ROWS' ) ) ) {
			$dimension = 'COLUMNS';
		}

		//Create a range that means "this column"
		$range = new Google_Service_Sheets_DimensionRange();
		$range->setSheetId( $this->get_sheet_id() );
		$range->setDimension( $dimension );
		$range->setStartIndex( $column_index );
		$range->setEndIndex( $column_index + 1 );

		//And use it to create a location
		$location = new Google_Service_Sheets_DeveloperMetadataLocation();
		$location->setDimensionRange( $range );

		return $location;
	}

	/**
	 * create_request_delete_column
	 *
	 * Creates a Google_Service_Sheets_Request that deletes our column developer
	 * metadata value.
	 *
	 * @param int $column_index
	 *
	 * @return Google_Service_Sheets_Request
	 */
	public function create_request_delete_column( $column_index ) {
		$lookup = new Google_Service_Sheets_DeveloperMetadataLookup();
		$lookup->setMetadataKey( $this->column_key() );
		$lookup->setMetadataLocation( $this->create_location( 'COLUMNS', $column_index ) );

		$dataFilter = new Google_Service_Sheets_DataFilter();
		$dataFilter->setDeveloperMetadataLookup( $lookup );

		$delete = new Google_Service_Sheets_DeleteDeveloperMetadataRequest();
		$delete->setDataFilter( $dataFilter );

		$request = new Google_Service_Sheets_Request();
		$request->setDeleteDeveloperMetadata( $delete );

		$this->flush_metadata_field_map_cache();

		return $request;
	}

	/**
	 * create_request_write_column
	 *
	 * Creates a Google_Service_Sheets_Request that helps write a metadata key
	 * value pair on a column.
	 *
	 * @param  int $column_index zero-based
	 * @param  string $value
	 *
	 * @return Google_Service_Sheets_Request
	 */
	public function create_request_write_column( $column_index, $value ) {
		$metadata = new Google_Service_Sheets_DeveloperMetadata();
		$metadata->setLocation( $this->create_location( 'COLUMNS', $column_index ) );
		$metadata->setMetadataKey( $this->column_key() );
		$metadata->setMetadataValue( (string) $value );
		$metadata->setVisibility( 'DOCUMENT' ); //Let any app see this metadata

		$create = new Google_Service_Sheets_CreateDeveloperMetadataRequest();
		$create->setDeveloperMetadata( $metadata );

		$request = new Google_Service_Sheets_Request();
		$request->setCreateDeveloperMetadata( $create );

		$this->flush_metadata_field_map_cache();

		return $request;
	}

	/**
	 * create_write_row_single_request
	 *
	 * Creates a Google_Service_Sheets_Request that helps write a metadata key
	 * value pair on a row.
	 *
	 * @param  int $row_index zero-based
	 * @param  string $value
	 *
	 * @return Google_Service_Sheets_Request
	 */
	public function create_request_write_row( $row_index, $value ) {
		$metadata = new Google_Service_Sheets_DeveloperMetadata();
		$metadata->setLocation( $this->create_location( 'ROWS', $row_index ) );
		$metadata->setMetadataKey( $this->row_key() );
		$metadata->setMetadataValue( (string) $value );
		$metadata->setVisibility( 'DOCUMENT' ); //Let any app see this metadata

		$create = new Google_Service_Sheets_CreateDeveloperMetadataRequest();
		$create->setDeveloperMetadata( $metadata );

		$request = new Google_Service_Sheets_Request();
		$request->setCreateDeveloperMetadata( $create );

		return $request;
	}

	/**
	 * Flushes the cache after the metadata field map has been updated.
	 */
	public function flush_metadata_field_map_cache() {
		$feed = $this->get_feed();
		$this->flush_cache( 'metadata_field_map', $this->get_sheet_id() . '_' . $feed['id'] );

		// Needed for the feed settings as we get the column names from the first row.
		$this->flush_cache( 'range_' . $this->prepare_range( '1:1' ), $this->get_sheet_id() );
	}
}
