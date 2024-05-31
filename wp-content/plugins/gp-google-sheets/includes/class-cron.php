<?php
namespace GP_Google_Sheets;

defined( 'ABSPATH' ) or exit;

use GP_Google_Sheets\Accounts\Legacy_Tokens;
use GP_Google_Sheets\Accounts\Tokens;

class Cron {

	public static function hooks() {
		// Check to see if the refresh token cron needs to be run.
		self::maybe_schedule_access_token_refresh_cron();

		add_action( 'gpgs_refresh_access_tokens_cron_hook', array( __CLASS__, 'refresh_access_tokens' ) );
	}

	public static function maybe_schedule_access_token_refresh_cron() {
		if ( wp_next_scheduled( 'gpgs_refresh_access_tokens_cron_hook' ) ) {
			return;
		}

		$cron_interval = 'weekly';
		/**
		 * Filter the interval at which the plugin will refresh access tokens.
		 *
		 * @param string $cron_interval The interval in seconds to run the cron job. See wp_get_schedules() for the accepted values. https://developer.wordpress.org/reference/functions/wp_get_schedules/
		 */
		$cron_interval = apply_filters( 'gpgs_refresh_access_tokens_cron_interval', $cron_interval );

		wp_schedule_event( time(), $cron_interval, 'gpgs_refresh_access_tokens_cron_hook' );
	}

	public static function refresh_access_tokens() {
		foreach ( Tokens::get_emails_to_tokens() as $email => $token ) {
			Tokens::refresh_and_persist_token( $token );
		}

		// ------------------------------------------
		// Legacy token refreshing
		// ------------------------------------------
		$global_token = Legacy_Tokens::get_global_token();

		if ( $global_token ) {
			if ( ! Legacy_Tokens::refresh_and_persist_global_token( $global_token ) ) {
				gp_google_sheets()->log_error( __METHOD__ . '(): Failed to refresh global access token due to a null response from refresh_and_persist_global_token().' );
			}
		}

		$feeds = gp_google_sheets()->get_feeds();

		foreach ( $feeds as $feed ) {
			$token = rgars( $feed, 'meta/token' );

			if ( ! $token || rgar( $token, 'gwiz_oauth' ) !== true ) {
				continue;
			}

			Legacy_Tokens::refresh_and_persist_normal_token( $token, $feed );
		}
	}
}
