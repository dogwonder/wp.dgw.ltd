<?php

namespace GP_Google_Sheets;

defined( 'ABSPATH' ) or exit;

class Retry {
	const MAX_RETRY_ATTEMPTS = 7;

	const NOTIFY_ATTEMPT = 3;

	/**
	 * Given a number of attempts made, calculate the delay (in seconds) before the next attempt.
	 *
	 * @param int $attempts_made The number of attempts that have been made so far.
	 * @return int $delay The number of seconds to delay before the next attempt.
	 */
	public static function calculate_retry_delay( $attempts_made ) {
		$interval = 60;
		$base     = 2;
		// Bound the maximum retry delay by MAX_RETRY_ATTEMPTS.
		$exponent = min( $attempts_made, self::MAX_RETRY_ATTEMPTS );
		// subtract one since we want our exponent to be zero based, but the attempt count is one based.
		$exponent = $exponent - 1;

		return $interval * pow( $base, $exponent );
	}

	/**
	 * Enqueue an action for immediate processing by Action Scheduler.
	 *
	 * @param string $hook The hook to be executed. (same as should be passed to as_enqueue_async_action())
	 * @param array $args The arguments to be passed to the hook. (same as should be passed to as_enqueue_async_action())
	 * @param int $entry_id The ID of the entry that is being processed.
	 */
	public static function enqueue_async_action( $hook, $args, $entry_id ) {
		$group = 'gpgs-entry-' . $entry_id;

		// Queue up the edit action for immediately processing
		$action_id = as_enqueue_async_action(
			$hook,
			$args,
			$group,
			false, // Do not use unique as it does not appear to take the arguments into consideration.
			1
		);

		// Get the queue runner instance and process the action immediately.
		$runner = \ActionScheduler_QueueRunner::instance();
		$runner->process_action( $action_id, 'Gravity Forms Async Feed Processing' );
	}

	/**
	 * Schedule an action for processing in the future by Action Scheduler.
	 *
	 * @param string $hook The hook to be executed. (same as should be passed to as_enqueue_async_action())
	 * @param array $args The arguments to be passed to the hook. (same as should be passed to as_enqueue_async_action())
	 * @param int $entry_id The ID of the entry that is being processed.
	 * @param int $priority The priority of the action. Useful for ensuring other actions run first such as adding a row.
	 * @param int $attempts_made The number of attempts that have been made so far.
	 */
	public static function schedule_single_action( $hook, $args, $entry_id, $priority = 10, $attempts_made = 1 ) {
		as_schedule_single_action(
			time() + self::calculate_retry_delay( $attempts_made ),
			$hook,
			$args,
			'gpgs-entry-' . $entry_id,
			false, // Do not use unique as it does not appear to take the arguments into consideration.
			$priority
		);
	}
}
