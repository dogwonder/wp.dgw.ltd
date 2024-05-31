<?php
namespace GP_Google_Sheets\Accounts;

defined( 'ABSPATH' ) or exit;

use \GP_Google_Sheets\Dependencies\Google\Client as Google_Client;

class Legacy_Tokens {
	public static function get_global_token() {
		$settings = gp_google_sheets()->get_plugin_settings();
		$token    = rgar( $settings, 'token' );

		$token = \GFCommon::maybe_decode_json( $token );

		if ( $token ) {
			$token['is_global_token'] = true;

			return self::maybe_refresh_token( $token, array(), true );
		}

		return $token;
	}

	public static function get_google_client_id() {
		$feed_addon = gp_google_sheets();
		$settings   = $feed_addon->get_plugin_settings();

		return rgar( $settings, 'client_id' );
	}

	public static function refresh_access_token( $token ) {
		try {
			gp_google_sheets()->log_debug( __METHOD__ . '(): Refreshing legacy token.' );

			$feed_addon      = gp_google_sheets();
			$plugin_settings = $feed_addon->get_plugin_settings();

			if ( ! isset( $plugin_settings['client_secret'] ) ) {
				gp_google_sheets()->log_error( __METHOD__ . '(): Failed to refresh legacy token due to missing client secret.' );
				return null;
			}

			// refresh the old way if the token was created before the gwiz oauth service (as inidicated by the absence of `gwiz_oauth`)
			$client    = new Google_Client();
			$client_id = self::get_google_client_id();
			$client->setClientId( $client_id );
			$client->setClientSecret( $plugin_settings['client_secret'] );
			$client->setAccessToken( $token );

			$client->fetchAccessTokenWithRefreshToken( $client->getRefreshToken() );

			$access_token = $client->getAccessToken();

			if ( empty( $access_token ) ) {
				gp_google_sheets()->log_error( __METHOD__ . '(): Failed to refresh legacy token due to empty response.' );
				return null;
			}

			gp_google_sheets()->log_debug( __METHOD__ . '(): Refreshed legacy token.' );

			return $access_token;
		} catch ( \Exception $e ) {
			gp_google_sheets()->log_error( __METHOD__ . '(): Failed to refresh legacy token. ' . $e->getMessage() );
			return null;
		}
	}

	public static function maybe_refresh_token( $token, $feed, $is_global_token = false ) {
		if ( ! self::should_refresh_access_token( $token ) ) {
			return $token;
		}

		if ( $is_global_token ) {
			return self::refresh_and_persist_global_token( $token );
		} else {
			return self::refresh_and_persist_normal_token( $token, $feed );
		}
	}

	public static function refresh_and_persist_global_token( $token ) {
		$refreshed_token = self::refresh_access_token( $token );
		$instance        = gp_google_sheets();

		$settings          = $instance->get_plugin_settings();
		$settings['token'] = $refreshed_token;
		$instance->update_plugin_settings( $settings );

		return $refreshed_token;
	}

	public static function refresh_and_persist_normal_token( $token, $feed ) {
		$refreshed_token = self::refresh_access_token( $token );
		$instance        = gp_google_sheets();

		$feed['meta']['token'] = $refreshed_token;
		$instance->update_feed_meta( $feed['id'], $feed['meta'] );

		return $refreshed_token;
	}

	/**
	 * Determine if the token has expired or is within 20 seconds of expiration.
	 *
	 * @param array $token
	 *
	 * @return bool
	 */
	public static function should_refresh_access_token( $token ) {
		$current_milliseconds = floor( microtime( true ) * 1000 );

		// Fallback to 0 if we can't find the expiry time in the token.
		$expiry_time = 0;

		if ( rgar( $token, 'expiry_date' ) ) {
			// Handle tokens with expiry_date set on them. This is set in milliseconds and comes from GWiz OAuth
			$expiry_time = (int) rgar( $token, 'expiry_date' );
		} elseif ( rgar( $token, 'expires_in' ) ) {
			// Handle tokens refreshed with the PHP API. These have an expires_in and created timestamp in seconds.
			$expiry_time = (int) rgar( $token, 'expires_in' ) + (int) rgar( $token, 'created' );

			// Convert expiry time to milliseconds.
			$expiry_time = $expiry_time * 1000;
		}

		return $expiry_time !== null && $expiry_time < ( $current_milliseconds + 20000 );
	}
}
