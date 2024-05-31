<?php
namespace GP_Google_Sheets;
use GP_Google_Sheets\Accounts\Google_Account;
use GP_Google_Sheets\Spreadsheets\Spreadsheet;

defined( 'ABSPATH' ) or exit;

use GP_Google_Sheets\Compatibility\GPPA_Object_Type_Google_Sheet;
use GP_Google_Sheets\Spreadsheets\Spreadsheets;

class Issues_Scanner {
	/**
	 * Scans for issues in feeds.
	 */
	public static function scan_for_feed_issues() {
		$feeds  = gp_google_sheets()->get_feeds();
		$issues = array();

		$available_spreadsheets = Spreadsheets::get();

		foreach ( $feeds as $feed ) {
			$spreadsheet_id = gpgs_get_spreadsheet_id_from_feed( $feed );
			$form           = \GFAPI::get_form( $feed['form_id'] );

			$issue_template = array(
				'type'           => 'feed',
				'formUrl'        => admin_url( 'admin.php?page=gf_edit_forms&id=' . $form['id'] ),
				'formTitle'      => $form['title'],
				'feedUrl'        => admin_url( 'admin.php?page=gf_edit_forms&view=settings&subview=gp-google-sheets&id=' . $feed['form_id'] . '&fid=' . $feed['id'] ),
				'feedName'       => $feed['meta']['feed_name'],
				'spreadsheetUrl' => $feed['meta']['google_sheet_url'],
			);

			// If the form is trashed, skip it.
			if ( $form['is_trash'] ) {
				continue;
			}

			// Check that a spreadsheet is assigned.
			if ( ! $spreadsheet_id ) {
				$issues[] = array_merge( $issue_template, array(
					'code'           => 'missing_spreadsheet',
					'spreadsheetUrl' => '',
				) );

				continue;
			}

			// Ensure that the sheet is accessible with any authorized Google account.
			foreach ( $available_spreadsheets as $spreadsheets ) {
				if ( isset( $spreadsheets[ $spreadsheet_id ] ) ) {
					continue 2;
				}
			}

			$legacy_feed_account = Google_Account::from_legacy_feed( $feed );

			if ( $legacy_feed_account ) {
				$spreadsheet = Spreadsheet::from_feed( $feed );

				if ( $spreadsheet->get_spreadsheet() ) {
					continue;
				}
			}

			$issues[] = array_merge( $issue_template, array(
				'code' => 'spreadsheet_not_accessible',
			) );
		}

		return $issues;
	}

	/**
	 * Scans for feeds and fields using the GPPA Google Sheets Object Type that are pointing to spreadsheets
	 * that cannot be accessed.
	 *
	 * @return array An array of issues.
	 */
	public static function scan_for_issues() {
		$issues = self::scan_for_feed_issues();

		if ( class_exists( 'GPPA_Object_Type' ) ) {
			$issues = array_merge( $issues, GPPA_Object_Type_Google_Sheet::scan_for_issues() );
		}

		return $issues;
	}
}
