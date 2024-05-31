<?php
namespace GP_Google_Sheets\Accounts;

defined( 'ABSPATH' ) or exit;

use GFCommon;
use GP_Google_Sheets\Accounts\Tokens;

class Oauth {
	public static function hooks() {
		add_action( 'wp_ajax_gpgs_plugin_settings_oauth_callback', array( __CLASS__, 'ajax_plugin_settings_google_oauth_redirect_handler' ) );
		add_action( 'wp_ajax_nopriv_gpgs_plugin_settings_oauth_callback', array( __CLASS__, 'ajax_plugin_settings_google_oauth_redirect_handler' ) );

		add_action( 'wp_ajax_gpgs_picker_callback', array( __CLASS__, 'ajax_google_picker_redirect_handler' ) );
		add_action( 'wp_ajax_nopriv_gpgs_picker_callback', array( __CLASS__, 'ajax_google_picker_redirect_handler' ) );
	}

	public static function ajax_plugin_settings_google_oauth_redirect_handler() {
		$state   = self::get_state_from_post();
		$user_id = rgar( $state, 'user_id' );

		$is_timestamp_expired = ! self::is_authorized_oauth_callback_request( $state );
		if ( $is_timestamp_expired ) {
			$_POST['gwiz_oauth_success'] = '0';
			$_POST['message']            = 'The authorization request has expired. Please refresh the page and try again.';

			return self::generate_google_oauth_redirect_script( null, $state, true );
		} elseif ( $user_id === 0 ) {
			$_POST['gwiz_oauth_success'] = '0';

			if ( empty( $_POST['message'] ) ) {
				$_POST['message'] = 'You are not authorized to make this request.';
			}

			return self::generate_google_oauth_redirect_script( null, $state, true );
		}

		$token = self::get_token_from_post();

		Tokens::set_token( $token, $user_id );

		// @Todo for reconnecting or feed settings does $should_close_tab be true?
		self::generate_google_oauth_redirect_script( $token, $state, true );
	}

	public static function is_authorized_oauth_callback_request( $state ) {
		if ( ! $state ) {
			return false;
		}

		$token_timestamp = intval( GFCommon::openssl_decrypt( rgar( $state, 'oauth_validation_token' ) ) );
		$fifteen_minutes = 15 * 60;

		return time() - $token_timestamp < $fifteen_minutes;
	}

	public static function get_token_from_post() {
		$token = null;

		if ( rgpost( 'access_token' ) && rgpost( 'gwiz_oauth_success' ) === '1' ) {
			$token = array(
				'access_token'  => rgpost( 'access_token' ),
				'refresh_token' => rgpost( 'refresh_token' ),
				'id_token'      => rgpost( 'id_token' ),
				'expiry_date'   => rgpost( 'expiry_date' ),
				'scope'         => rgpost( 'scope' ),
				'token_type'    => rgpost( 'token_type' ),
				'gwiz_oauth'    => true,
			);

		}

		return $token;
	}

	public static function get_state_from_post() {
		$state = null;

		if ( rgpost( 'state' ) ) {
			$state = json_decode( rgpost( 'state' ), true );
		}

		return $state;
	}

	/**
	 * Generate Javascript to save token to localstorage and optionally close the tab
	 *
	 * @param array|null $token The token from the Google OAuth callback
	 * @param array $state The state from the Google OAuth callback
	 * @param boolean $should_close_tab whether or not the tab should be closed after the script is run.
	 *
	 */
	public static function generate_google_oauth_redirect_script( $token, $state, $should_close_tab ) {
		?>
			<html>
			<head>
				<title>Google Connected</title>
			</html>
			<body>
			<script>
				var gpgsShouldCloseWindow = <?php echo $should_close_tab ? 'true' : 'false'; ?>;
				window.localStorage.setItem( '<?php echo 'gpgs_google_oauth_data_' . rgar( $state, 'oauth_random_string' ); ?>',
					<?php
						$data = array(
							'success' => rgpost( 'gwiz_oauth_success' ) === '1' ? '1' : '0',
							'message' => rgpost( 'message' ),
							'token'   => $token,
						);

						$json = json_encode( $data );
						echo '"' . addslashes( $json ) . '"';
						?>
				);

				if (gpgsShouldCloseWindow) {
					window.close();
				}
			</script>
			</body>
			</html>
		<?php
		die();
	}

	/**
	 * Generate Javascript to save token to localstorage and close the tab
	 */
	public static function ajax_google_picker_redirect_handler() {
		$state = self::get_state_from_post();
		?>
			<html>
			<head>
				<title>Google Sheet Connected</title>
			</html>
			<body>
			<script>
				window.localStorage.setItem( '<?php echo 'gpgs_google_picker_data_' . rgar( $state, 'oauth_random_string' ); ?>',
					<?php
						$sheet_url = rgpost( 'sheet_url' );
						$data      = array(
							'success'   => $sheet_url ? '1' : '0',
							'sheet_url' => $sheet_url,
							'message'   => rgpost( 'message' ),
						);

						$json = json_encode( $data );
						echo '"' . addslashes( $json ) . '"';
						?>
				);

				window.close();
			</script>
			</body>
			</html>
		<?php
		die();
	}
}
