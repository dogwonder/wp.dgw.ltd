<?php
namespace GP_Google_Sheets\Accounts;

defined( 'ABSPATH' ) or exit;

use \GP_Google_Sheets\Dependencies\Google\Client as Google_Client;
use \GP_Google_Sheets\Dependencies\Google\Service\Sheets as Google_Service_Sheets;
use \GP_Google_Sheets\Dependencies\Google\Service\Drive as Google_Service_Drive;

class Google_Account {

	/**
	 * The Google email associated with the token. We use the actual token to get the email using an API request.
	 *
	 * @var string
	 */
	private $google_email;

	/**
	 * An identifier for the token/account. If we have an email for the account, that's what we'll use.
	 * Otherwise, we can provide an ID for things like legacy tokens.
	 */
	private $id;

	/**
	 * @var array{
	 *   access_token: string,
	 *   refresh_token: string,
	 *   expiry_date?: number,
	 *   scope?: string,
	 *   token_type?: string,
	 *   expires_in?: number,
	 *   created?: number,
	 *   gwiz_oauth?: boolean,
	 *   is_global_token?: boolean,
	 * } | null
	 */
	private $token;

	/**
	 * @var bool
	 */
	private $token_is_healthy = false;

	/**
	 * @var bool
	 */
	private $is_legacy_token = false;

	/**
	 * @var int
	 */
	private $legacy_feed_id;

	/**
	 * @var int
	 */
	private $user_id;

	/**
	 * @var string
	 */
	private $user_display_name;

	/**
	 * @var string
	 */
	private $user_edit_link;

	/**
	 * @var Google_Client|null
	 */
	public $google_client;

	/**
	 * @var Google_Service_Sheets|null
	 */
	public $google_sheets_service;

	/**
	 * @var Google_Service_Drive|null
	 */
	public $google_drive_service;

	/**
	 * @var array<string, \GP_Google_Sheets\Dependencies\Google\Service\Resource> | array{
	 *   sheets_spreadsheets: \GP_Google_Sheets\Dependencies\Google\Service\Sheets\Resource\Spreadsheets,
	 *   sheets_spreadsheets_values: \GP_Google_Sheets\Dependencies\Google\Service\Sheets\Resource\SpreadsheetsValues,
	 *   sheets_spreadsheets_developerMetadata: \GP_Google_Sheets\Dependencies\Google\Service\Sheets\Resource\SpreadsheetsDeveloperMetadata,
	 * }
	 */
	public $google_service_resources;

	/**
	 * @param array{
	 *   google_email?: string,
	 *   token: array,
	 *   account_id?: string,
	 *   is_legacy_token?: boolean,
	 *   legacy_feed_id?: int,
	 * } $args
	 *
	 * @param bool $force_token_refresh Whether to force a token refresh.
	 */
	public function __construct( $args, $force_token_refresh = false ) {
		$this->token           = $args['token'];
		$this->google_email    = rgar( $args, 'google_email' );
		$this->id              = rgar( $args, 'account_id', $this->google_email );
		$this->is_legacy_token = rgar( $args, 'is_legacy_token' );
		$this->legacy_feed_id  = rgar( $args, 'legacy_feed_id' );

		$this->check_token( $force_token_refresh );

		$this->user_id = $this->get_user_id();

		if ( $this->user_id ) {
			$user_data = get_userdata( $this->user_id );

			if ( $user_data ) {
				$this->user_display_name = $user_data->display_name;
			}

			$this->user_edit_link = get_edit_user_link( $this->user_id );
		}

		$this->google_client = $this->create_google_client();

		if ( $this->google_client ) {
			$this->google_sheets_service = new Google_Service_Sheets( $this->google_client );
			$this->google_drive_service  = new Google_Service_Drive( $this->google_client );
		}
	}

	/**
	 * @return \GP_Google_Sheets\Dependencies\Google\Service\Resource|null
	 */
	public function get_sheets_resource( $resource ) {
		if ( ! $this->google_client || ! $this->google_sheets_service ) {
			return null;
		}

		$runtime_cache_key = 'sheets_' . $resource;

		if ( ! isset( $this->google_service_resources[ $runtime_cache_key ] ) ) {
			$this->google_service_resources[ $runtime_cache_key ] = $this->google_sheets_service->$resource;
		}

		return $this->google_service_resources[ $runtime_cache_key ];
	}

	public function to_json() {
		return array(
			'googleEmail'     => $this->google_email,
			'accountId'       => $this->id,
			'isLegacyToken'   => $this->is_legacy_token,
			'legacyFeedId'    => $this->legacy_feed_id,
			'tokenIsHealthy'  => $this->token_is_healthy,
			'userId'          => $this->user_id,
			'userDisplayName' => $this->user_display_name,
			'userEditLink'    => $this->user_edit_link,
			'token'           => $this->token,
		);
	}

	public static function from_email( $email ) {
		$token = Tokens::get_token_by_google_email( $email );

		if ( $token ) {
			return new self( array(
				'google_email' => $email,
				'token'        => $token,
			) );
		}

		return null;
	}

	/**
	 * This will only return a feed if the feed has a legacy token using the Picker. If they used a global token,
	 * that token will have been migrated to the new options introduced in GPGS 1.0.
	 *
	 * @param array $feed
	 * @param bool  $force_token_refresh
	 *
	 * @return Google_Account|null
	 */
	public static function from_legacy_feed( $feed, $force_token_refresh = false ) {
		$legacy_token = rgars( $feed, 'meta/picked_token' );

		if ( ! $legacy_token ) {
			return null;
		}

		return new Google_Account( array(
			'token'           => $legacy_token,
			// translators: %s is the name of the feed.
			'account_id'      => 'legacy_token_feed_' . $feed['id'],
			'is_legacy_token' => true,
			'legacy_feed_id'  => $feed['id'],
		) );
	}

	/**
	 * @param bool $force_token_refresh
	 *
	 * @return Google_Account|null
	 */
	public static function from_legacy_global( $force_token_refresh = false ) {
		$legacy_global_token = Legacy_Tokens::get_global_token();

		if ( empty( $legacy_global_token ) ) {
			return null;
		}

		return new Google_Account( array(
			'token'           => $legacy_global_token,
			'account_id'      => 'legacy_token_global',
			'is_legacy_token' => true,
			'legacy_feed_id'  => 0,
		), $force_token_refresh );
	}

	public function check_token( $force_refresh = false ) {
		if ( $force_refresh ) {
			$this->refresh_token();
		} else {
			$this->maybe_refresh_token();
		}

		$this->token_is_healthy = true;

		if ( empty( $this->token ) || $this->should_refresh() ) {
			$this->token_is_healthy = false;
		}
	}

	public function should_refresh() {
		if ( $this->is_legacy_token ) {
			return Legacy_Tokens::should_refresh_access_token( $this->token );
		}

		return Tokens::should_refresh_access_token( $this->token );
	}

	public function maybe_refresh_token() {
		if ( ! $this->should_refresh() ) {
			return;
		}

		$this->refresh_token();
	}

	public function refresh_token() {
		if ( $this->is_legacy_token ) {
			if ( rgar( $this->token, 'is_global_token' ) ) {
				$this->token = Legacy_Tokens::refresh_and_persist_global_token( $this->token );
			} else {
				$feed        = gp_google_sheets()->get_feed( $this->legacy_feed_id );
				$this->token = Legacy_Tokens::refresh_and_persist_normal_token( $this->token, $feed );
			}
		} else {
			$this->token = Tokens::refresh_and_persist_token( $this->token );
		}
	}

	public function get_user_id() {
		if ( ! $this->google_email ) {
			return null;
		}

		$user_ids_to_emails = Tokens::get_user_ids_to_emails();

		foreach ( $user_ids_to_emails as $user_id => $emails ) {
			$emails = array_values( array_filter( $emails ) ); // Filter out any null values

			if ( empty( $emails ) ) {
				continue;
			}

			if ( in_array( $this->google_email, $emails ) ) {
				return $user_id;
			}
		}

		return null;
	}

	/**
	 * @return Google_Client|null
	 */
	public function create_google_client() {
		$client = new Google_Client();

		$token = $this->token;

		if ( $token === null || ! is_array( $token ) ) {
			gp_google_sheets()->log_error( __METHOD__ . '(): Missing token for account: ' . $this->google_email );
			return null;
		}

		try {
			$client->setAccessToken( $token['access_token'] );

			if ( rgar( $token, 'gwiz_oauth' ) ) {
				$client_id = Tokens::get_google_client_id();
			} else {
				$client_id = Legacy_Tokens::get_google_client_id();
			}

			$client->setClientId( $client_id );

			if ( ! rgar( $token, 'gwiz_oauth' ) ) {
				$plugin_settings = gp_google_sheets()->get_plugin_settings();
				$client->setClientSecret( $plugin_settings['client_secret'] );
			}

			$timeout = apply_filters( 'gpgs_http_request_timeout', 15, null );

			$stack = new \GP_Google_Sheets\Dependencies\GuzzleHttp\HandlerStack();
			$stack->setHandler( new \GP_Google_Sheets\Guzzle_WP_Remote_Handler() );

			$stack->push(\GP_Google_Sheets\Dependencies\GuzzleHttp\Middleware::mapRequest(function ( \GP_Google_Sheets\Dependencies\Psr\Http\Message\RequestInterface $r ) {
				gp_google_sheets()->log_debug( 'Google API Request: ' . $r->getMethod() . ' ' . (string) $r->getUri() );

				if ( defined( 'GPGS_LOG_REQUESTS' ) && GPGS_LOG_REQUESTS ) {
					error_log( 'GP Google Sheets Google API Request (PID ' . getmypid() . ') ' . $r->getMethod() . ' ' . (string) $r->getUri() );
				}

				return $r;
			}));

			$httpClient = new \GP_Google_Sheets\Dependencies\GuzzleHttp\Client( array(
				'timeout' => $timeout,
				'handler' => $stack,
			) );

			$client->setHttpClient( $httpClient );

			return $client;
		} catch ( \Exception $e ) {
			gp_google_sheets()->log_error( __METHOD__ . '(): Invalid token for account: ' . $this->google_email . '. Error: ' . $e->getMessage() );

			return null;
		}
	}

	public function get_email() {
		return $this->google_email;
	}

	public function get_id() {
		return $this->id;
	}

	public function is_token_healthy() {
		return $this->token_is_healthy;
	}

	public function get_token() {
		return $this->token;
	}

}
