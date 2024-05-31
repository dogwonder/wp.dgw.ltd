<?php

namespace GP_Google_Sheets;

defined( 'ABSPATH' ) or exit;

use GP_Google_Sheets\Accounts\Tokens;

class Migration_1_0 {
	public static function hooks() {
		add_action( 'gp_google_sheets_migrate_google_tokens', array( __CLASS__, 'upgrade_to_1_0' ), 10, 0 );
	}

	public static function schedule() {
		as_schedule_single_action(
			time(),
			'gp_google_sheets_migrate_google_tokens',
			array(),
			'gpgs-migrate-google-tokens',
			false, // Do not use unique as it does not appear to take the arguments into consideration.
			10
		);
	}

	/**
	 * Handles migrating the plugin to version 1.0.
	 *
	 * This primarily involves migrating tokens to a new storage schema:
	 *
	 *  - In Beta 1.0, users created their own OAuth application and then tokens were stored
	 *    in feed meta _or_ the plugin setting (the latter is known as the "global token").
	 *    Any token created during this time is now known as a "legacy token" and must be refreshed
	 *    using the personal OAuth application.
	 *
	 * - In Beta 2.0, we created our own OAuth application but continued to store tokens in the same
	 *   way. That is, in feed meta and plugin settings.
	 *
	 * - In 1.0, we are migrating to a new storage schema that is more robust and allows us to only
	 *   over have a single token per WordPress user. Implementation of this new schema is primarily
	 *   in the GP_Google_Sheets_Tokens class.
	 */
	public static function upgrade_to_1_0() {
		$feeds = gp_google_sheets()->get_feeds();

		$settings               = gp_google_sheets()->get_plugin_settings();
		$global_token           = $settings['token'];
		$global_token_email     = null;
		$global_token_not_empty = ! empty( $global_token ) || ( ! is_array( $global_token ) && ! \GFCommon::is_json( $global_token ) );

		/**
		 * Check for a global token and move it over to the email -> token mappings _if_ it is not a legacy token.
		 * The reason for this is that legacy tokens do not have the correct scopes to get the associated google
		 * account email and thus we have no way of creating the email -> token mapping if it is a legacy token.
		 */
		if ( rgar( $global_token, 'gwiz_oauth' ) === true && $global_token_not_empty ) {
			$global_token_email = Tokens::get_token_email( $global_token );
			Tokens::set_email_to_token( $global_token_email, $global_token, true );

			unset( $settings['token'] );

			gp_google_sheets()->update_plugin_settings( $settings );
		}

		foreach ( $feeds as $feed ) {
			if ( ! empty( $feed['meta']['sheet_was_picked'] ) && $feed['meta']['sheet_was_picked'] === '1' ) {
				$token = rgars( $feed, 'meta/picked_token' );

				// this is a legacy token and should not be migrated.
				if ( rgar( $token, 'gwiz_oauth' ) !== true ) {
					continue;
				}

				// token already migrated
				if ( empty( $token ) ) {
					continue;
				}

				$email          = Tokens::get_token_email( $token );
				$spreadsheet_id = gpgs_get_spreadsheet_id_from_feed( $feed );

				// Ensure we have an email for these functions. Even if we don't have it, we want to nuke this token as it's bad any way.
				if ( $email ) {
					// Do not overwrite a user's token if it has already been set since it could be the
					// global token and we do not want to overwrite that one.
					if ( ! Tokens::get_token_by_google_email( $email ) ) {
						Tokens::set_email_to_token( $email, $token, false );
					}

					// update the spreadsheet_id -> email mapping so that we know which token
					// belongs to this spreadsheet.
					Tokens::map_account_to_spreadsheet( $email, $spreadsheet_id );
				}

				// Clear out the old meta fields that are no longer used.
				$deprecated_feed_meta_fields = array(
					'token',
					'picked_token',
					'sheet_was_picked',
				);

				foreach ( $deprecated_feed_meta_fields as $field_name ) {
					if ( rgar( $feed['meta'], $field_name ) ) {
						unset( $feed['meta'][ $field_name ] );
					}
				}

				gp_google_sheets()->update_feed_meta( $feed['id'], $feed['meta'] );
			} elseif ( $global_token_email ) {
				// if there is a global token that is _not_ a legacy token, then we've already migrated it.
				// now we just need to create to spreadsheet -> email mapping.
				$spreadsheet_id = gpgs_get_spreadsheet_id_from_feed( $feed );
				Tokens::map_account_to_spreadsheet( $global_token_email, $spreadsheet_id );
			}
		}
	}
}
