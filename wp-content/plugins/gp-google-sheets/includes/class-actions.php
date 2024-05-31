<?php
namespace GP_Google_Sheets;

defined( 'ABSPATH' ) or exit;

use GFAPI;
use GP_Google_Sheets\Retry;
use GP_Google_Sheets\Spreadsheets\Spreadsheet;

class Actions {
	public static function hooks() {

		// Action Scheduler hooks
		add_action( 'gp_google_sheets_add_entry_to_sheet', array( __CLASS__, 'add_entry_to_sheet' ), 10, 4 );
		add_action( 'gp_google_sheets_delete_entry_from_sheet', array( __CLASS__, 'delete_entry_from_sheet' ), 10, 4 );
		add_action( 'gp_google_sheets_edit_entry_in_sheet', array( __CLASS__, 'edit_entry_in_sheet' ), 10, 4 );

	}

	/**
	 * @since  1.0
	 *
	 * @param  int $feed_id  Feed ID.
	 * @param  int $entry_id Entry ID.
	 * @param  int $form_id  Form ID.
	 *
	 * @throws \Exception Expected to be handled by Action Scheduler.
	 */
	public static function add_entry_to_sheet( $feed_id, $entry_id, $form_id, $attempts_made = 0 ) {
		$feed          = gp_google_sheets()->get_feed( $feed_id );
		$entry         = GFAPI::get_entry( $entry_id );
		$form          = GFAPI::get_form( $form_id );
		$attempts_made = $attempts_made + 1;
		$slug          = gp_google_sheets()->get_slug();

		//Has the entry been marked spam?
		$is_spam = $entry['status'] === 'spam';

		if ( $is_spam ) {
			//Yes, abort
			gp_google_sheets()->log_debug( __METHOD__ . '(): ' . __( 'Entry marked as spam', 'gp-google-sheets' ) );
			return;
		}

		try {
			// Filter hook purely for testing that retry logic works as intended.
			if ( apply_filters( 'gpgs_testing_should_fail_action', false, __METHOD__, $attempts_made, $feed, $entry ) ) {
				throw new \Exception( 'Testing failure of action.' );
			}

			$spreadsheet = Spreadsheet::from_feed( $feed );

			/*
			 * Check if a row for the entry already exists in the Sheet. If so, bail out and rely on the user enabling
			 * "Update & Delete Rows" in the feed settings.
			 */
			if ( gform_get_meta( $entry['id'], "{$slug}_{$feed['id']}_inserted" ) ) {
				$row_index = $spreadsheet->find_row_by_metadata_value( $entry['id'] );

				if ( $row_index !== false ) {
					if ( empty( $feed['meta']['edit_rows'] ) ) {
						$message = sprintf(
							'Row for Entry %s in Sheet %s already exists. Enable "Update & Delete Rows" in the feed settings to overwrite existing rows.',
							$entry['id'],
							$spreadsheet->get_id()
						);

						gp_google_sheets()->log_debug( __METHOD__ . '(): ' . $message );
						gp_google_sheets()->add_feed_error( $message, $feed, $entry, $form );
					} else {
						gp_google_sheets()->handle_after_update_entry( $form, $entry );
					}

					return;
				}
			}

			$row_data = $spreadsheet->create_row_data( array( $entry ), true );
			// result will be, either, a string (range that rows were written to) or an exception.
			$result = $spreadsheet->append_rows( $row_data );

			$inserted = gform_get_meta( $entry['id'], "{$slug}_{$feed['id']}_inserted" );

			// Set inserted time only when feed is processed the first time. Set updated time when feed is processed again.
			if ( ! $inserted ) {
				gform_update_meta( $entry['id'], "{$slug}_{$feed['id']}_inserted", time() );
			} else {
				gform_update_meta( $entry['id'], "{$slug}_{$feed['id']}_updated", time() );
			}

			/**
			 * Adding the entry ID to the row metadata was previously handled with Action Scheduler. However, this
			 * caused a massive job pileup and was generally inefficient.
			 *
			 * The one tradeoff is this is one more failure point during the write job, but it's a tradeoff we're
			 * willing to make after issues with the previous approach.
			 */
			$spreadsheet->add_entry_id_to_row_metadata( $feed, $row_data, $result );
		} catch ( \Exception $ex ) {
			/*
			 * If the error contains "createDeveloperMetadata", we can ignore the error as there isn't anything we
			 * can do in terms of retrying due to the quota of developer metadata being hit.
			 *
			 * The solutions here include the user needing to create a new spreadsheet from scratch or we need to
			 * look into moving away from developer metadata in favor of a hidden column.
			 *
			 * https://developers.google.com/sheets/api/guides/metadata#metadata_storage_limits
			 */
			if ( strpos( $ex->getMessage(), 'createDeveloperMetadata' ) !== false ) {
				$message = 'Developer metadata quota has been hit. This impacts the ability for entry edits/deletions to sync to Google Sheets if enabled. It is recommended that you connect this feed to a new spreadsheet.' . "\n\n" . $ex->getMessage();
				gp_google_sheets()->log_debug( __METHOD__ . '(): ' . $message );
				gp_google_sheets()->add_feed_error( $message, $feed, $entry, $form );
				return;
			}

			if ( $attempts_made > Retry::MAX_RETRY_ATTEMPTS ) {
				$message = 'Could not add entry to Google Sheets and maximum retries have already been attempted.' . "\n\n" . $ex->getMessage();
				gp_google_sheets()->log_debug( __METHOD__ . '(): ' . $message );
				gp_google_sheets()->add_feed_error( $message, $feed, $entry, GFAPI::get_form( $entry['form_id'] ) );
				return;
			}

			if ( $attempts_made === Retry::NOTIFY_ATTEMPT ) {
				/*
				 * Send an email to the site administrator with the site name in the subject title that there is an
				 * issue. We don't want it to be feed-specific as there are likely other issues going on.
				 */
				$subject = sprintf(
					'[%s] %s',
					get_bloginfo( 'name' ),
					__( 'GP Google Sheets Feed Error', 'gp-google-sheets' ),
				);

				/*
				 * Message template:
				 *
				 * Greetings, administrator,
				 *
				 * There was an issue adding one or more entries to Google Sheets.
				 *
				 * We recommend:
				 *
				 *   - Checking the GP Google Sheets settings for any issues.
				 *   - Reviewing recently failed jobs in Action Scheduler.
				 *   - Inspecting the notes for any error messages on recently submitted entries.
				 *
				 * Best,
				 *
				 * The Gravity Wiz Team
				 */
				$message = __( 'Greetings, administrator,', 'gp-google-sheets' ) .
					'<br /><br />' .
					__( 'There was an issue adding one or more entries to Google Sheets.' ) .
					'<br /><br />' .
					__( 'We recommend:', 'gp-google-sheets' ) .
					'<ul>' .
					'<li>' . sprintf(
						// translators: %s is the URL to the GP Google Sheets settings page.
						__( 'Checking the <a href="%s">GP Google Sheets settings</a> for any issues.', 'gp-google-sheets' ),
						admin_url( 'admin.php?page=gf_settings&subview=gp-google-sheets' )
					) . '</li>' .
					'<li>' . sprintf(
						// translators: %s is the URL to the Action Scheduler page.
						__( 'Reviewing recently <a href="%s">failed jobs</a> in Action Scheduler.', 'gp-google-sheets' ),
						admin_url( 'tools.php?page=action-scheduler&s=gp_google_sheets' )
					) . '</li>' .
					'<li>' . __( 'Inspecting the notes for any error messages on recently submitted entries.', 'gp-google-sheets' ) . '</li>' .
					'</ul>' .
					__( 'Best,', 'gp-google-sheets' ) .
					'<br /><br />' .
					__( 'The Gravity Wiz Team', 'gp-google-sheets' );

				// Do not send this notification more than once every hour site-wide so we'll use a transient to track it.
				$transient_key = 'gp_google_sheets_sent_error_notification';

				if ( ! get_transient( $transient_key ) ) {
					$headers = array( 'Content-Type: text/html; charset=UTF-8' );

					wp_mail( get_option( 'admin_email' ), $subject, $message, $headers );
				}

				set_transient( $transient_key, true, HOUR_IN_SECONDS );
			}

			$message = 'Cannot connect to Google.' . "\n\n" . $ex->getMessage();
			gp_google_sheets()->log_debug( __METHOD__ . '(): ' . $message );
			gp_google_sheets()->add_feed_error( $message, $feed, $entry, $form );

			Retry::schedule_single_action(
				'gp_google_sheets_add_entry_to_sheet',
				array(
					'feed_id'       => $feed_id,
					'entry_id'      => $entry_id,
					'form_id'       => $form_id,
					'attempts_made' => $attempts_made,
				),
				$entry_id,
				5, // Run before other actions so things like edit/delete can happen in the same batch.
				$attempts_made
			);

			// Re-throw the exception for Action Scheduler to catch, log, and mark the action as failed.
			throw $ex;
		}
	}

	/**
	 * Delete and entry from a given feed and retry if the delete attempt fails
	 *
	 * @param $entry_id The ID of the entry that was deleted
	 * @param $feed_id The ID of the feed which connects to the given $spreadsheet_id
	 * @param $spreadsheet_id The ID of the spreadsheet that the entry should be deleted from
	 *
	 * @throws \Exception Expected to be handled by Action Scheduler.
	 */
	public static function delete_entry_from_sheet( $entry_id, $feed_id, $spreadsheet_id, $attempts_made = 0 ) {
		$feed          = gp_google_sheets()->get_feed( $feed_id );
		$entry         = GFAPI::get_entry( $entry_id );
		$attempts_made = $attempts_made + 1;

		try {
			$spreadsheet = Spreadsheet::from_feed( $feed );

			// Filter hook purely for testing that retry logic works as intended.
			if ( apply_filters( 'gpgs_testing_should_fail_action', false, __METHOD__, $attempts_made, $feed, $entry ) ) {
				throw new \Exception( 'Testing failure of action.' );
			}

			//Find this entry's row in the sheet so we can overwrite that single row
			$row_index = $spreadsheet->find_row_by_metadata_value( $entry_id );

			if ( $row_index === false ) {
				//Something is wrong, we can't find the entry in the Sheet
				$message = sprintf(
					'Cannot find Entry %s in Sheet %s to delete.',
					$entry_id,
					$spreadsheet_id
				);

				$form = GFAPI::get_form( $entry['form_id'] );

				gp_google_sheets()->log_debug( __METHOD__ . '(): ' . $message );
				gp_google_sheets()->add_feed_error( $message, $feed, $entry, $form );
				return;
			}

			//Delete the row at $row_index
			$spreadsheet->delete_row( $row_index + 1 );
		} catch ( \Exception $ex ) {
			if ( $attempts_made > Retry::MAX_RETRY_ATTEMPTS ) {
				$message = 'Could not delete entry from Google Sheets and maximum retries have already been attempted. ' . $ex->getMessage();
				gp_google_sheets()->log_debug( __METHOD__ . '(): ' . $message );
				gp_google_sheets()->add_feed_error( $message, $feed, $entry, GFAPI::get_form( $entry['form_id'] ) );
				return;
			}

			Retry::schedule_single_action(
				'gp_google_sheets_delete_entry_from_sheet',
				array(
					'entry_id'       => $entry_id,
					'feed_id'        => $feed_id,
					'spreadsheet_id' => $spreadsheet_id,
					'attempts_made'  => $attempts_made,
				),
				$entry_id,
				15,
				$attempts_made
			);

			// Re-throw the exception for Action Scheduler to catch, log, and mark the action as failed.
			throw $ex;
		}
	}

	/**
	 * @throws \Exception Expected to be handled by Action Scheduler.
	 */
	public static function edit_entry_in_sheet( $entry_id, $feed_id, $spreadsheet_id, $attempts_made = 0 ) {
		$feed          = gp_google_sheets()->get_feed( $feed_id );
		$entry         = GFAPI::get_entry( $entry_id );
		$form          = GFAPI::get_form( $entry['form_id'] );
		$attempts_made = $attempts_made + 1;

		try {
			$spreadsheet = Spreadsheet::from_feed( $feed );

			// Filter hook purely for testing that retry logic works as intended.
			if ( apply_filters( 'gpgs_testing_should_fail_action', false, __METHOD__, $attempts_made, $feed, $entry ) ) {
				throw new \Exception( 'Testing failure of action.' );
			}

			//Look at the sheet, make sure our field map is still accurate
			$feed = gp_google_sheets()->maybe_update_field_map_setting( $feed['id'], $feed['form_id'] );

			/**
			 * Create a row for the recently-edited entry. Convert $entry_id to an
			 * integer because Gravity Forms lies about this parameter being an
			 * integer.
			 */
			$row_data = $spreadsheet->create_row_data( array( $entry ) );
			if ( empty( $row_data['rows'] ) ) {
				/**
				 * There's no data to write--this feed probably has an empty
				 * field map, so the entry exists but there's nothing to
				 * write.
				 */
				return;
			}

			//Find this entry's row in the sheet so we can overwrite that single row
			$row_index = $spreadsheet->find_row_by_metadata_value( $entry_id );

			if ( $row_index === false ) {
				/**
				 * Can't find the entry in the Sheet
				 * This feed could have been inactive when this entry was
				 * submitted.
				 *
				 * Do not log an issue if there is a payment status associated as it's likely a delayed
				 * feed. Note: $this->maybe_delay_feed() isn't a reliable check here.
				 */
				if ( ! rgar( $entry, 'payment_status' ) ) {
					$message = sprintf(
						'Cannot find Entry %s in Sheet %s to edit.',
						$entry_id,
						$spreadsheet_id
					);

					gp_google_sheets()->log_debug( __METHOD__ . '(): ' . $message );
					gp_google_sheets()->add_feed_error( $message, $feed, $entry, $form );
				}

				return;
			}

			$spreadsheet->edit_row( $row_index, $row_data );
		} catch ( \Exception $ex ) {
			if ( $attempts_made > Retry::MAX_RETRY_ATTEMPTS ) {
				$message = 'Could not edit entry in Google Sheets and maximum retries have already been attempted. ' . $ex->getMessage();
				gp_google_sheets()->log_debug( __METHOD__ . '(): ' . $message );
				gp_google_sheets()->add_feed_error( $message, $feed, $entry, GFAPI::get_form( $entry['form_id'] ) );
				return;
			}

			// queue up a retry for the edit action.
			Retry::schedule_single_action(
				'gp_google_sheets_edit_entry_in_sheet',
				array(
					'entry_id'       => $entry_id,
					'feed_id'        => $feed_id,
					'spreadsheet_id' => $spreadsheet_id,
					'attempts_made'  => $attempts_made,
				),
				$entry_id,
				10,
				$attempts_made
			);

			// Re-throw the exception for Action Scheduler to catch, log, and mark the action as failed.
			throw $ex;
		}
	}

}
