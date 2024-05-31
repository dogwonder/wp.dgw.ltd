<?php
namespace GP_Google_Sheets\Accounts;

defined( 'ABSPATH' ) or exit;

use GP_Google_Sheets;
use GP_Google_Sheets\Spreadsheets\Spreadsheet;
class Tokens {
	/**
	 * A map of spreadsheet_id => google_account_email.
	 *
	 * This can be used to look up the email address of the Google account that is connected to a given spreadsheet
	 * and then consequently the access token for that account via the self::$_emails_to_tokens map.
	 *
	 * In other words, this can be used to get the access token for a given spreadsheet.
	 */
	private static $spreadsheet_account_mapping = null;

	/**
	 * A map of google account emails => token pairs for all users who have connected to Google Sheets.
	 *
	 * Notes:
	 *     * No more than one of these should have is_global_token set to true. This
	 *       property indicates that the token was used to connect the plugin to Google from the GF settings page.
	 */
	private static $emails_to_tokens = null;

	/**
	 * A map of wordpress_user_id => google_account_email arrays.
	 *
	 * The google_account_email arrays should only ever contain one email at the moment. In the future, we might change this to allow WordPress users to connect themselves to multiple Google
	 * Accounts.
	 */
	private static $user_ids_to_emails = null;

	public static function get_user_ids_to_emails() {
		if ( self::$user_ids_to_emails === null ) {
			self::$user_ids_to_emails = get_option( 'gp_google_sheets_user_ids_to_emails', array() );
		}

		return self::$user_ids_to_emails;
	}

	public static function get_emails_to_tokens() {
		if ( self::$emails_to_tokens === null ) {
			self::$emails_to_tokens = get_option( 'gp_google_sheets_emails_to_tokens', array() );
		}

		return self::$emails_to_tokens;
	}

	public static function get_spreadsheet_account_mapping() {
		if ( self::$spreadsheet_account_mapping === null ) {
			self::$spreadsheet_account_mapping = get_option( 'gp_google_sheets_spreadsheet_account_mapping', array() );
		}

		return self::$spreadsheet_account_mapping;
	}

	public static function map_account_to_spreadsheet( $google_account, $spreadsheet_id ) {
		// Ensure that the spreadsheet account mapping has been fetched from options.
		self::get_spreadsheet_account_mapping();

		self::$spreadsheet_account_mapping[ $spreadsheet_id ] = $google_account;
		update_option( 'gp_google_sheets_spreadsheet_account_mapping', self::$spreadsheet_account_mapping );
	}

	public static function get_spreadsheet_account( $spreadsheet_id ) {
		return rgar( self::get_spreadsheet_account_mapping(), $spreadsheet_id );
	}

	public static function set_email_to_token( $email, $token, $is_global_token = false ) {
		if ( empty( $email ) ) {
			return;
		}

		// Ensure that the emails to tokens mapping has been fetched from options.
		self::get_emails_to_tokens();

		if ( $is_global_token ) {
			$token['is_global_token'] = true;
		}

		$token['gwiz_oauth']              = true;
		self::$emails_to_tokens[ $email ] = $token;
		update_option( 'gp_google_sheets_emails_to_tokens', self::$emails_to_tokens );
		return $token;
	}

	public static function delete_email_to_token_mapping( $email ) {
		if ( ! rgar( self::get_emails_to_tokens(), $email ) ) {
			return;
		}

		unset( self::$emails_to_tokens[ $email ] );
		update_option( 'gp_google_sheets_emails_to_tokens', self::$emails_to_tokens );

		// Remove the email from the user_ids_to_emails map.
		foreach ( self::get_user_ids_to_emails() as $user_id => $user_emails ) {
			foreach ( $user_emails as $user_email_index => $user_email ) {
				if ( $email === $user_email ) {
					unset( self::$user_ids_to_emails[ $user_id ][ $user_email_index ] );

					if ( empty( self::$user_ids_to_emails[ $user_id ] ) ) {
						unset( self::$user_ids_to_emails[ $user_id ] );
					}
				}
			}
		}

		update_option( 'gp_google_sheets_user_ids_to_emails', self::$user_ids_to_emails );

		// Remove the account from the spreadsheet_account_mapping.
	}

	public static function set_user_id_to_email( $user_id, $email ) {
		if ( empty( self::get_user_ids_to_emails() ) ) {
			self::$user_ids_to_emails[ $user_id ] = array();
		}

		// If $email is empty, do not add it.
		if ( empty( $email ) ) {
			return;
		}

		// Clear this email out of any other user's array.
		foreach ( self::$user_ids_to_emails as $other_user_id => $other_emails ) {
			if ( $other_user_id === $user_id ) {
				continue;
			}

			if ( in_array( $email, $other_emails ) ) {
				$other_emails                               = array_diff( $other_emails, array( $email ) );
				self::$user_ids_to_emails[ $other_user_id ] = $other_emails;
			}
		}

		if ( ! in_array( $email, self::get_user_ids_to_emails() ) ) {
			self::$user_ids_to_emails[ $user_id ][] = $email;
		}

		update_option( 'gp_google_sheets_user_ids_to_emails', self::$user_ids_to_emails );
	}

	public static function refresh_access_token( $token ) {
		$refresh_token_url = GP_Google_Sheets::GWIZ_OAUTH_SERVICE_URL . '/oauth/google/refresh';

		$license_info = gp_google_sheets()->get_gp_license_info();

		$response = wp_remote_post(
			$refresh_token_url,
			array(
				'headers'     => array( 'Content-Type' => 'application/json; charset=utf-8' ),
				'body'        => json_encode( array(
					'refreshToken' => $token['refresh_token'],
					'licenseId'    => $license_info['id'],
				) ),
				'method'      => 'POST',
				'data_format' => 'body',
			),
		);

		gp_google_sheets()->log_debug( __METHOD__ . '(): Access token refresh response code: ' . wp_remote_retrieve_response_code( $response ) );

		if ( is_wp_error( $response ) ) {
			gp_google_sheets()->log_error( __METHOD__ . '(): Failed to refresh token. ' . $response->get_error_message() );
			return null;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! rgar( $body, 'token' ) ) {
			gp_google_sheets()->log_error( __METHOD__ . '(): Failed to refresh token. Token missing from response. ' . json_encode( $body ) );
		} else {
			gp_google_sheets()->log_debug( __METHOD__ . '(): Refreshed access token.' );
		}

		return rgar( $body, 'token' );
	}

	public static function check_token_scope( $token ) {
		/*
		 * If the scope is present and does not include drive.file, then this token is bad and needs to be reconnected
		 * entirely.
		 */
		if ( rgar( $token, 'scope' ) && strpos( $token['scope'], 'drive.file' ) === false ) {
			return null;
		}

		return $token;
	}

	public static function maybe_refresh_token( $token ) {
		if ( empty( self::check_token_scope( $token ) ) ) {
			return null;
		}

		if ( ! self::should_refresh_access_token( $token ) ) {
			return $token;
		}

		return self::refresh_and_persist_token( $token );
	}

	public static function refresh_and_persist_token( $token ) {
		if ( empty( self::check_token_scope( $token ) ) ) {
			return null;
		}

		$is_global_token = rgar( $token, 'is_global_token', false );
		$token           = self::refresh_access_token( $token );

		if ( empty( $token ) ) {
			return null;
		}

		$email = self::get_token_email( $token );

		return self::set_email_to_token( $email, $token, $is_global_token );
	}

	public static function get_google_client_id() {
		$transient_key = GP_Google_Sheets::get_addon_slug() . '_google-oauth-client-id';

		// Check for a cached client id and use that if it exists
		$client_id = get_transient( $transient_key );
		if ( $client_id !== false ) {
			return $client_id;
		}

		$response = wp_remote_get(
			GP_Google_Sheets::GWIZ_OAUTH_SERVICE_URL . '/oauth/google/client-id',
			array(
				'headers'     => array(
					'Content-Type' => 'application/json; charset=utf-8',
				),
				'method'      => 'GET',
				'data_format' => 'body',
			),
		);

		$body = json_decode( $response['body'], true );

		if ( ! empty( $body['client_id'] ) ) {
			// Cache the client id for a week.
			$one_week = 60 * 60 * 24 * 7;
			set_transient( $transient_key, $body['client_id'], $one_week );

			return $body['client_id'];
		}

		return null;
	}

	public static function get_token_email( $token ) {
		$access_token = rgar( $token, 'access_token' );

		if ( ! $access_token ) {
			return null;
		}

		$url      = add_query_arg( 'access_token', $access_token, 'https://www.googleapis.com/oauth2/v1/userinfo' );
		$response = wp_remote_get( $url );

		if ( is_wp_error( $response ) ) {
			return null;
		}

		$data = \GFCommon::maybe_decode_json( wp_remote_retrieve_body( $response ) );

		return rgar( $data, 'email' );
	}

	public static function set_token( $token, $user_id, $is_global_token = false ) {
		$email = self::get_token_email( $token );

		self::set_email_to_token( $email, $token, $is_global_token );
		self::set_user_id_to_email( $user_id, $email );
	}

	public static function get_token_by_google_email( $email ) {
		$token = rgar( self::get_emails_to_tokens(), $email );

		if ( $token === null ) {
			return null;
		}

		return self::maybe_refresh_token( $token );
	}

	public static function should_refresh_access_token( $token ) {
		$current_milliseconds = floor( microtime( true ) * 1000 );
		// if the expiry token has expired or will do so within the next 20 seconds, it should be refreshed.
		$expiry_time = rgar( $token, 'expiry_date' );

		return $expiry_time !== null && $expiry_time < ( $current_milliseconds + 20000 );
	}

	public static function get_feeds_connected_to_email( $account_id ) {
		static $active_feeds;

		if ( ! isset( $active_feeds ) ) {
			$active_feeds = gp_google_sheets()->get_active_feeds();
		}

		$connected_feeds = array();

		foreach ( $active_feeds as $feed ) {
			// Get the spreadsheet for the feed.
			$spreadsheet = Spreadsheet::from_feed( $feed );

			if ( ! $spreadsheet || ! $spreadsheet->get_google_account() ) {
				continue;
			}

			if ( $spreadsheet->get_google_account()->get_id() === $account_id ) {
				$form = \GFAPI::get_form( $feed['form_id'] );

				$connected_feeds[] = array(
					'spreadsheet_id' => $spreadsheet->get_id(),
					'feed_id'        => $feed['id'],
					'form_id'        => $feed['form_id'],
					'form_title'     => $form['title'],
					'feed_name'      => $feed['meta']['feed_name'],
					'feed_url'       => admin_url( 'admin.php?page=gf_edit_forms&view=settings&subview=gp-google-sheets&id=' . $feed['form_id'] . '&fid=' . $feed['id'] ),
				);
			}
		}

		return $connected_feeds;
	}
}
