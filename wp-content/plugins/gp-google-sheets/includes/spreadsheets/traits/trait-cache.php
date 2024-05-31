<?php
namespace GP_Google_Sheets\Spreadsheets\Traits;

defined( 'ABSPATH' ) or exit;

/**
 * This class can handle caching of spreadsheet data through runtime caching and optionally transients
 * to reduce the number of API calls made to Google.
 */
trait Cache {
	/**
	 * @var array<string, mixed>
	 */
	private static $cache = array();

	/**
	 * Dictionary of cache keys and their expiration times.
	 *
	 * @var array<string, int>
	 */
	private static $cache_keys = array();

	/**
	 * @param array<string, int> $keys
	 */
	public static function set_cache_keys( $keys ) {
		self::$cache_keys = $keys;
	}

	public static function get_key_expiration( $key ) {
		return rgar( self::$cache_keys, $key );
	}

	private function get_key( $key, $scope = null ) {
		$key = $this->get_id() . '_' . $key;

		if ( $scope ) {
			$key .= '_' . $scope;
		}

		return $key;
	}

	private static function transient_key( $key ) {
		return self::transient_key_prefix() . $key;
	}

	private static function transient_key_prefix() {
		return 'gpgs_cache_';
	}

	/**
	 * @param string $key
	 * @param string $scope Additional scope to differentiate the cache key. Useful for sheets inside a spreadsheet that
	 *   way we can use a single key to know the expiration time for all sheets.
	 */
	public function get_cache( $key, $scope = null ) {
		$expiration = self::get_key_expiration( $key );
		$key        = $this->get_key( $key, $scope );

		if ( isset( self::$cache[ $key ] ) ) {
			return self::$cache[ $key ];
		}

		if ( $expiration > 0 ) {
			$transient_key = $this->transient_key( $key );
			$value         = get_transient( $transient_key );

			if ( $value ) {
				self::$cache[ $key ] = $value;

				return $value;
			}
		}

		return null;
	}

	public function set_cache( $key, $value, $scope = null ) {
		$expiration = self::get_key_expiration( $key );
		$key        = $this->get_key( $key, $scope );

		self::$cache[ $key ] = $value;

		if ( $expiration > 0 ) {
			$transient_key = $this->transient_key( $key );

			set_transient( $transient_key, $value, $expiration );
		}
	}

	public function flush_cache( $key = 'all', $scope = null ) {
		global $wpdb;

		$key = $this->get_key( $key, $scope );

		if ( isset( self::$cache[ $key ] ) ) {
			unset( self::$cache[ $key ] );
		}

		$transient_key = $this->transient_key( $key );

		delete_transient( $transient_key );
	}

	public static function flush_entire_cache() {
		global $wpdb;

		self::$cache = array();

		$like = $wpdb->esc_like( '_transient_' . self::transient_key_prefix() ) . '%';

		$wpdb->query(
			$wpdb->prepare( "DELETE FROM `$wpdb->options` WHERE `option_name` LIKE %s", $like )
		);
	}
}
