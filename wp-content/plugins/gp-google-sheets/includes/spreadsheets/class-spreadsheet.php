<?php

namespace GP_Google_Sheets\Spreadsheets;

defined( 'ABSPATH' ) or exit;

use GP_Google_Sheets\Accounts\Google_Account;
use GP_Google_Sheets\Accounts\Google_Accounts;
use GP_Google_Sheets\Accounts\Tokens;
use GP_Google_Sheets\Spreadsheets\Traits\Entry_Writer;
use GP_Google_Sheets\Spreadsheets\Traits\Has_Field_Map;
use GP_Google_Sheets\Spreadsheets\Traits\Metadata_Writer;
use GP_Google_Sheets\Spreadsheets\Traits\Reader;
use GP_Google_Sheets\Spreadsheets\Traits\Test_Entry_Writer;
use GP_Google_Sheets\Spreadsheets\Traits\Writer;
use GP_Google_Sheets\Spreadsheets\Traits\Cache;

class Spreadsheet {
	use Entry_Writer;
	use Has_Field_Map;
	use Metadata_Writer;
	use Reader;
	use Test_Entry_Writer;
	use Writer;
	use Cache;

	/**
	 * @var string
	 */
	private $id;

	/**
	 * @var string|int
	 */
	private $sheet_id;

	/**
	 * @var array|null
	 */
	private $feed;

	/**
	 * @var Google_Account|null
	 */
	private $google_account;

	/**
	 * Simple variable to store the error when retrieving the spreadsheet so it can be shown in the UI.
	 *
	 * @var string|null
	 */
	private $error;

	/**
	 * Runtime cache of spreadsheet instances.
	 *
	 * @var array
	 */
	private static $instances = array();

	/**
	 * @var string SHEET_KEY The metadata key and value written to the sheet tab we use in the Sheet.
	 * @deprecated We no longer rely on this and instead, we save the sheet ID in the feed meta.
	 */
	const SHEET_KEY = 'entries_to_google_sheet';

	/**
	 * If passing without arguments, it assumed that you will likely be creating a spreadsheet with this instance.
	 */
	public function __construct( $id = null, $sheet_id = null, $google_account = null ) {
		$this->id       = $id;
		$this->sheet_id = $sheet_id;

		$this->google_account = $google_account ? $google_account : $this->find_google_account();

		$this->set_cache_keys( array(
			'spreadsheet'        => 60, // 60 seconds to try to reduce number of calls against per minute quota
			'drive_file'         => 0,
			'sheet'              => 60,
			'metadata_field_map' => 60,
		) );
	}

	public static function get( $id, $sheet_id = null, $google_account = null ) {
		if ( isset( self::$instances[ $id ] ) ) {
			$instance = self::$instances[ $id ];

			$instance->set_sheet_id( $sheet_id );

			if ( $google_account ) {
				$instance->set_google_account( $google_account );
			}

			return $instance;
		}

		self::$instances[ $id ] = new self( $id, $sheet_id, $google_account );

		return self::$instances[ $id ];
	}

	/**
	 * Gets a spreadsheet for a feed.
	 *
	 * @param array $feed GPGS feed.
	 *
	 * @return Spreadsheet|null
	 */
	public static function from_feed( $feed ) {
		$spreadsheet_id = gpgs_get_spreadsheet_id_from_feed( $feed );
		$sheet_id       = rgars( $feed, 'meta/google_sheet_id' );

		if ( ! $spreadsheet_id ) {
			return null;
		}

		// If the feed has a picked_token (e.g. legacy token using the picker for selecting an existing sheet), use it.
		// This method will return null if it's not a legacy feed.
		$google_account = Google_Account::from_legacy_feed( $feed );

		$instance = self::get( $spreadsheet_id, $sheet_id, $google_account );
		$instance->set_feed( $feed );

		return $instance;
	}

	public function get_id() {
		return $this->id;
	}

	public function get_error() {
		return $this->error;
	}

	public function get_sheet_id() {
		// If we already have a sheet ID, just return it. Otherwise we'll do more checks.
		if ( ! rgblank( $this->sheet_id ) ) {
			return $this->sheet_id;
		}

		$sheet = $this->get_sheet();

		if ( ! $sheet ) {
			return null;
		}

		$properties = $sheet->getProperties();
		$sheet_id   = $properties->getSheetId();

		$this->set_sheet_id( $sheet_id );

		return $sheet_id;
	}

	public function get_feed() {
		return $this->feed;
	}

	public function set_feed( $feed ) {
		$this->feed = $feed;
	}

	public function set_google_account( $google_account ) {
		$this->google_account = $google_account;
	}

	public function set_sheet_id( $sheet_id ) {
		$this->sheet_id = $sheet_id;
	}
	public function get_spreadsheet() {
		if ( $this->get_cache( 'spreadsheet' ) ) {
			return $this->get_cache( 'spreadsheet' );
		}

		if ( ! $this->google_account ) {
			// translators: %d is the spreadsheet ID.
			$this->error = sprintf( __( 'Unable to find a suitable Google Account for spreadsheet ID #%d.', 'gp-google-sheets' ), $this->get_id() );
			gp_google_sheets()->log_error( __METHOD__ . '(): Could not find spreadsheet, unable to find a suitable Google Account for spreadsheet ID #' . $this->get_id() );

			return null;
		}

		try {
			/**
			 * @var \GP_Google_Sheets\Dependencies\Google\Service\Sheets\Resource\Spreadsheets|null
			 */
			$spreadsheets_resource = $this->get_sheets_resource( 'spreadsheets' );

			$spreadsheet = $spreadsheets_resource->get( $this->id, array(
				/*
				 * Use a field mask to only get the spreadsheet properties and sheet properties, otherwise we can get
				 * back obscenely large JSON objects if the sheets have a lot of conditional formatting, etc.
				 */
				'fields' => 'properties,sheets.properties',
			) );

			// Set the cache
			$this->set_cache( 'spreadsheet', $spreadsheet );

			return $spreadsheet;
		} catch ( \Exception $ex ) {
			gp_google_sheets()->log_error( __METHOD__ . '(): Could not find Sheet. ' . $ex->getMessage() );
			$this->error = $ex->getMessage();

			return null;
		}
	}

	public function has_spreadsheet() {
		return ! empty( $this->get_spreadsheet() );
	}

	public function get_drive_file() {
		if ( $this->get_cache( 'drive_file' ) ) {
			return $this->get_cache( 'drive_file' );
		}

		$drive_file = $this->google_account->google_drive_service->files->get( $this->id );

		$this->set_cache( 'drive_file', $drive_file );

		return $drive_file;
	}

	/**
	 * @return \GP_Google_Sheets\Dependencies\Google\Service\Resource|null
	 */
	public function get_sheets_resource( $resource ) {
		if ( ! $this->google_account ) {
			return null;
		}

		return $this->google_account->get_sheets_resource( $resource );
	}

	/**
	 * Manually set the Google Drive File.
	 *
	 * @param \GP_Google_Sheets\Dependencies\Google\Service\Drive\DriveFile $drive_file
	 */
	public function set_drive_file( $drive_file ) {
		$this->set_cache( 'drive_file', $drive_file );

		return $drive_file;
	}

	public function get_name() {
		// If we have the Drive File already, pull the name from there to save an API call.
		if ( $this->get_cache( 'drive_file' ) ) {
			$drive_file = $this->get_cache( 'drive_file' );

			return $drive_file->getName();
		}

		if ( ! $this->has_spreadsheet() ) {
			return null;
		}

		return $this->get_spreadsheet()->getProperties()->getTitle();
	}

	public function get_sheets() {
		$spreadsheet = $this->get_spreadsheet();

		if ( empty( $spreadsheet ) ) {
			return array();
		}

		$sheets = array();

		foreach ( $spreadsheet->getSheets() as $sheet ) {
			$sheets[ $sheet->getProperties()->getSheetId() ] = $sheet->getProperties()->title;
		}

		return $sheets;
	}

	public function get_google_account() {
		return $this->google_account;
	}

	/**
	 * Finds the Google account for a spreadsheet.
	 */
	public function find_google_account() {
		if ( $this->get_google_account() ) {
			return $this->get_google_account();
		}

		/*
		 * If we don't have a spreadsheet ID, we don't have an account. This can happen if we instantiated this class
		 * to create a new spreadsheet.
		 */
		if ( ! $this->get_id() ) {
			return null;
		}

		// Get all of the Google tokens to know which ones are healthy or not.
		$google_accounts = Google_Accounts::get_all();

		// Check if the feed is in our spreadsheets to email mapping.
		$mapped_account = Tokens::get_spreadsheet_account( $this->get_id() );

		// If the feed is in the mapping and the token is healthy, return it.
		if (
			$mapped_account
		) {
			foreach ( $google_accounts as $google_account ) {
				if ( $google_account->get_id() === $mapped_account ) {
					if ( ! $google_account->is_token_healthy() ) {
						break;
					}

					return $google_account;
				}
			}
		}

		// Fallback to searching through all of the available spreadsheets grouped by email.
		$available_spreadsheets = Spreadsheets::get();

		foreach ( $available_spreadsheets as $account_id => $spreadsheets ) {
			foreach ( $spreadsheets as $spreadsheet ) {
				if ( $spreadsheet->get_id() === $this->get_id() ) {
					foreach ( $google_accounts as $google_account ) {
						if ( $google_account->get_id() === $account_id ) {
							// Update the mapping.
							Tokens::map_account_to_spreadsheet( $account_id, $this->get_id() );

							return $google_account;
						}
					}
				}
			}
		}

		return null;
	}

	/**
	 * Find the current name of the tab to which we write/read data.
	 *
	 * We now allow users to select the sheet on their own, but we previously had more basic logic to use the first
	 * sheet and we would write developer metadata to it in case it was ever moved or renamed.
	 *
	 * @return \GP_Google_Sheets\Dependencies\Google\Service\Sheets\Sheet|null
	 */
	public function get_sheet() {
		if ( $this->get_cache( 'sheet', $this->sheet_id ) ) {
			return $this->get_cache( 'sheet', $this->sheet_id );
		}

		$spreadsheet = $this->get_spreadsheet();

		if ( ! $spreadsheet ) {
			return null;
		}

		$sheets = $spreadsheet->getSheets();

		// If the sheet was created with the "legacy authentication" system,
		// we will not have a sheet id to match against and should thus skip this
		// next matching process.
		if ( ! rgblank( $this->sheet_id ) ) {
			// if a specific sheet has been chosen by the user, then its id will be in the feed meta table
			// and we should use that to find the sheet.
			foreach ( $sheets as $sheet ) {
				$properties = $sheet->getProperties();
				$sheet_id   = $properties->getSheetId();

				if ( $sheet_id != $this->sheet_id ) {
					continue;
				}

				$this->set_cache( 'sheet', $sheet, $this->sheet_id );

				return $sheet;
			}
		}

		// Everything beyond the first check is considered legacy/deprecated as we store the sheet ID in feeds/fields.

		/**
		 * Try to find the sheet that has the metadata key linked to the feed.
		 */
		foreach ( $sheets as $sheet ) {
			if ( empty( $sheet->getDeveloperMetadata() ) ) {
				continue;
			}

			foreach ( $sheet->getDeveloperMetadata() as $metadata ) {
				if ( self::SHEET_KEY != $metadata->getMetadataKey() ) {
					continue;
				}

				$this->set_cache( 'sheet', $sheet, $this->sheet_id );

				return $sheet;
			}
		}

		/**
		 * If we still don't have a tab, search for Sheet1
		 */
		foreach ( $sheets as $sheet ) {
			if ( $sheet->getProperties()->getTitle() === 'Sheet1' ) {
				$this->set_cache( 'sheet', $sheet, $this->sheet_id );

				return $sheet;
			}
		}

		// If that STILL doesn't work, pick the first tab.
		foreach ( $sheets as $sheet ) {
			$this->set_cache( 'sheet', $sheet, $this->sheet_id );

			return $sheet;
		}

		return null;
	}

	public function get_sheet_name() {
		$sheet = $this->get_sheet();

		if ( ! $sheet ) {
			return null;
		}

		return $sheet->getProperties()->getTitle();
	}

	/**
	 * Returns the URL to a spreadsheet (or specific sheet)
	 *
	 * @return string|null
	 */
	public function get_url() {
		return gpgs_build_sheet_url( $this->id, $this->sheet_id );
	}

	public function flush_spreadsheet_cache() {
		$this->flush_cache( 'spreadsheet' );

		$sheets = $this->get_sheets();
		foreach ( $sheets as $sheet_id => $_ ) {
			$this->flush_cache( 'sheet', $sheet_id );
		}
	}

	/**
	 * Create a spreadsheet. Requires that a Google Account be explicitly passed in.
	 *
	 * @param string $name
	 * @param Google_Account|null $google_account
	 *
	 * @throws \Exception
	 *
	 * @return Spreadsheet The current instance.
	 */
	public function create( $name, $google_account ) {
		// Set the Google Account
		$this->google_account = $google_account;

		$sheet      = new \GP_Google_Sheets\Dependencies\Google\Service\Sheets\Sheet();
		$properties = new \GP_Google_Sheets\Dependencies\Google\Service\Sheets\SheetProperties();

		$properties->setSheetId( '0' );
		$sheet->setProperties( $properties );

		$spreadsheet = new \GP_Google_Sheets\Dependencies\Google\Service\Sheets\Spreadsheet( array(
			'properties' => array( //more props https://developers.google.com/sheets/api/reference/rest/v4/spreadsheets/sheets#SheetProperties
				'title' => $name,
			),
			'sheets'     => array(
				$sheet,
			),
		) );

		if ( ! $this->google_account ) {
			gp_google_sheets()->log_debug( __METHOD__ . '(): Google Account is required to create a spreadsheet.' );
			throw new \Exception( 'Google Account is required to create a spreadsheet.' );
		}

		try {
			$spreadsheet = $google_account->google_sheets_service->spreadsheets->create( $spreadsheet, array(
				'fields' => 'spreadsheetId',
			) );
		} catch ( \Exception $e ) {
			gp_google_sheets()->log_error( __METHOD__ . '(): ' . $e->getMessage() );
			throw $e;
		}

		//Save the spreadsheet ID in the form settings by generating a URL
		$spreadsheet_url = sprintf(
			'https://docs.google.com/spreadsheets/d/%s/edit',
			$spreadsheet->spreadsheetId
		);

		//Sheet was created successfully
		gp_google_sheets()->log_debug( __METHOD__ . '(): Sheet was created successfully at ' . $spreadsheet_url );

		$this->id       = $spreadsheet->spreadsheetId;
		$this->sheet_id = '0';

		return $this;
	}

}
