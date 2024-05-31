<?php
namespace GP_Google_Sheets\Spreadsheets;

defined( 'ABSPATH' ) or exit;

use GP_Google_Sheets\Accounts\Google_Accounts;
use GP_Google_Sheets\Accounts\Google_Account;

class Spreadsheets {
	/**
	 * Runtime cache of spreadsheets.
	 *
	 * @var array|null
	 */
	private static $spreadsheets = array();

	/**
	 * Whether or not we've fetched all user spreadsheets.
	 *
	 * @var bool
	 */
	private static $fetched_all_user_spreadsheets = false;

	/**
	 * Gets the sheets available by iterating over each token and
	 * querying the Google Drive API for the files available to each
	 * token.
	 *
	 * @param string|null $account_id The ID (typically the email) of the Google account to get spreadsheets for.
	 *   If null, all available spreadsheets are returned.
	 *
	 * @return array<int|string, array<int|string, Spreadsheet>>|array<int|string, Spreadsheet> An array of spreadsheets keyed by account ID if
	 *   $account_id is null, or an array of spreadsheets keyed by spreadsheet ID if $account_id is not null.
	 */
	public static function get( $account_id = null ) {
		if ( $account_id && isset( self::$spreadsheets[ $account_id ] ) ) {
			return self::$spreadsheets[ $account_id ];
		}

		if ( ! $account_id && self::$fetched_all_user_spreadsheets ) {
			return self::$spreadsheets;
		}

		$accounts     = array();
		$spreadsheets = array();

		// Get the Google Accounts or account.
		if ( ! $account_id ) {
			$accounts = Google_Accounts::get_all();
		} else {
			$account = Google_Account::from_email( $account_id );

			if ( ! $account ) {
				return array();
			}

			$accounts = array(
				$account_id => $account,
			);
		}

		/** @var Google_Account|null $account */
		foreach ( $accounts as $account ) {
			if ( ! $account || ! isset( $account->google_drive_service ) ) {
				continue;
			}

			try {
				/** @var \GP_Google_Sheets\Dependencies\Google\Service\Drive\Resource\Files $files_resource */
				$files_resource = $account->google_drive_service->files;

				$files = $files_resource->listFiles(array(
					'q'                         => 'trashed = false',
					'pageSize'                  => 1000,
					'fields'                    => 'files(contentHints/thumbnail,iconLink,id,name,size,thumbnailLink,webViewLink,mimeType,modifiedTime)',
					// Necessary parameters to support files in shared drives, all three are required XD
					'supportsAllDrives'         => true,
					'corpora'                   => 'allDrives',
					'includeItemsFromAllDrives' => true,
				));
			} catch ( \Exception $e ) {
				gp_google_sheets()->log_error(
					"Unable to get spreadsheets for access token belonging to: {$account->get_id()}." . $e->getMessage()
				);
				continue;
			}

			foreach ( $files as $file ) {
				if ( ! isset( $spreadsheets[ $account->get_id() ] ) ) {
					$spreadsheets[ $account->get_id() ] = array();
				}

				$spreadsheet = Spreadsheet::get( $file['id'], null, $account );
				$spreadsheet->set_drive_file( $file );

				$spreadsheets[ $account->get_id() ][ $file['id'] ] = $spreadsheet;
			}
		}

		if ( $account_id ) {
			$spreadsheets = isset( $spreadsheets[ $account_id ] ) ? $spreadsheets[ $account_id ] : array();

			self::$spreadsheets[ $account_id ] = $spreadsheets;

			return $spreadsheets;
		} else {
			self::$spreadsheets                  = $spreadsheets;
			self::$fetched_all_user_spreadsheets = true;
		}

		return $spreadsheets;
	}

}
