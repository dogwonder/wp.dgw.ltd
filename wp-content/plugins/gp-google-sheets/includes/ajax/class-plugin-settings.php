<?php
namespace GP_Google_Sheets\AJAX;

defined( 'ABSPATH' ) or exit;

use GFAPI;
use GP_Google_Sheets\Accounts\Google_Accounts;
use GP_Google_Sheets\Accounts\Tokens;
use GP_Google_Sheets\Issues_Scanner;
use GP_Google_Sheets\Spreadsheets\Spreadsheet;

class Plugin_Settings extends AJAX {

	public static function hooks() {
		add_action( 'wp_ajax_gpgs_google_accounts', array( __CLASS__, 'ajax_google_accounts' ) );
		add_action( 'wp_ajax_gpgs_scan_issues', array( __CLASS__, 'ajax_scan_issues' ) );
		add_action( 'wp_ajax_gpgs_delete_google_account', array( __CLASS__, 'ajax_delete_google_account' ) );
	}

	public static function ajax_google_accounts() {
		self::check_nonce_and_caps(
			__( 'There was an error fetching token health data. Double check that you have permissions to edit GP Google Sheets Settings and try again.', 'gp-google-sheets' ),
		);

		Spreadsheet::flush_entire_cache();

		$google_accounts = Google_Accounts::get_all( true );

		$google_accounts_json = array_map( function( $google_account ) {
			return $google_account->to_json();
		}, $google_accounts );

		// Loop over accounts and find the feeds that are attached to them.
		foreach ( $google_accounts_json as &$google_account ) {
			if ( rgar( $google_account, 'legacyFeedId' ) ) {
				$legacy_feed = gp_google_sheets()->get_feed( $google_account['legacyFeedId'] );

				if ( $legacy_feed ) {
					$legacy_feed_form = GFAPI::get_form( $legacy_feed['form_id'] );

					$google_account['connectedFeeds'] = array(
						array(
							'feed_id'        => $legacy_feed['id'],
							'spreadsheet_id' => gpgs_get_spreadsheet_id_from_feed( $legacy_feed ),
							'form_id'        => $legacy_feed['form_id'],
							'form_title'     => $legacy_feed_form['title'],
							'feed_name'      => $legacy_feed['meta']['feed_name'],
							'feed_url'       => admin_url( 'admin.php?page=gf_edit_forms&view=settings&subview=gp-google-sheets&id=' . $legacy_feed['form_id'] . '&fid=' . $legacy_feed['id'] ),
						),
					);
				}

				continue;
			}

			$google_account['connectedFeeds'] = Tokens::get_feeds_connected_to_email( $google_account['accountId'] );
		}

		wp_send_json_success( array(
			'token_data' => $google_accounts_json,
		) );
	}

	public static function ajax_scan_issues() {
		self::check_nonce_and_caps(
			__( 'There was an error scanning for issues. Double check that you have permissions to edit GP Google Sheets Settings and try again.', 'gp-google-sheets' ),
			null,
			null,
			array(
				'issues' => array(),
			)
		);

		$issues = Issues_Scanner::scan_for_issues();

		wp_send_json_success( array(
			'issues' => $issues,
		) );
	}

	/**
	 * AJAX callback for deleting tokens.
	 *
	 * @todo How can we do this for legacy tokens?
	 */
	public static function ajax_delete_google_account() {
		self::check_nonce_and_caps(
			__( 'There was an error deleting the Google account. Double check that you have permissions to edit GP Google Sheets Settings and try again.', 'gp-google-sheets' ),
		);

		$account_id = rgpost( 'account_id' );

		if ( empty( $account_id ) ) {
			wp_send_json_error( array(
				'message' => 'Missing required parameter: Google Account ID.',
			) );
		}

		if ( strpos( $account_id, 'legacy_token_feed_' ) === 0 ) {
			// Get the feed ID from the account ID.
			$feed_id = str_replace( 'legacy_token_feed_', '', $account_id );

			// Get the feed
			$feed = gp_google_sheets()->get_feed( $feed_id );

			if ( ! $feed ) {
				wp_send_json_error( array(
					'message' => 'Feed not found to remove legacy token.',
				) );
			}

			// Remove the "picked_token" meta from the feed.
			unset( $feed['meta']['picked_token'] );

			gp_google_sheets()->update_feed_meta( $feed_id, $feed['meta'] );
		} elseif ( $account_id === 'legacy_token_global' ) {
			// Get the plugin settings.
			$settings = gp_google_sheets()->get_plugin_settings();

			// Remove the "token". Leave "client_id" and "client_secret" in case there are still feeds using legacy tokens.
			unset( $settings['token'] );

			gp_google_sheets()->update_plugin_settings( $settings );
		}

		Tokens::delete_email_to_token_mapping( $account_id );

		wp_send_json_success( array(
			'message' => 'Token deleted.',
		) );
	}
}
