<?php
namespace GP_Google_Sheets\Accounts;

defined( 'ABSPATH' ) or exit;

class Google_Accounts {

	/**
	 * Gets all of the Google accounts.
	 *
	 * @param bool $force_refresh Whether to force a refresh of the tokens.
	 *
	 * @return array<Google_Account>
	 */
	public static function get_all( $force_refresh = false ) {
		static $accounts = null;

		if ( $accounts !== null && ! $force_refresh ) {
			return $accounts;
		}

		$accounts = array();

		/**
		 * Global legacy token.
		 */
		$legacy_global_account = Google_Account::from_legacy_global();

		if ( $legacy_global_account ) {
			$accounts[] = $legacy_global_account;
		}

		/**
		 * 1.0 tokens.
		 */
		foreach ( Tokens::get_emails_to_tokens() as $email => $token ) {
			$accounts[] = new Google_Account( array(
				'google_email' => $email,
				'token'        => $token,
			), $force_refresh );
		}

		$feeds = self::get_feeds_with_legacy_token();

		/**
		 * Legacy tokens that used the Google Picker on feeds.
		 */
		foreach ( $feeds as $feed ) {
			$legacy_token_account = Google_Account::from_legacy_feed( $feed );

			if ( $legacy_token_account ) {
				$accounts[] = $legacy_token_account;
			}
		}

		return $accounts;
	}

	private static function get_feeds_with_legacy_token() {
		global $wpdb;

		$picked_token_like_1   = '%' . $wpdb->esc_like( '"picked_token":{' ) . '%';
		$picked_token_like_2   = '%' . $wpdb->esc_like( '"picked_token":"{' ) . '%';
		$picked_token_not_like = '%' . $wpdb->esc_like( '"picked_token":""' ) . '%';

		$results = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}gf_addon_feed
                               WHERE addon_slug=%s
							   AND (`meta` LIKE %s OR `meta` LIKE %s)
							   AND `meta` NOT LIKE %s",
			gp_google_sheets()->get_slug(),
			$picked_token_like_1,
			$picked_token_like_2,
			$picked_token_not_like
		), ARRAY_A );

		foreach ( $results as &$result ) {
			$result['meta'] = json_decode( $result['meta'], true );
		}

		return $results;
	}

}
