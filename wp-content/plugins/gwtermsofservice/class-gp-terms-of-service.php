<?php

if ( ! class_exists( 'GP_Plugin' ) ) {
	return;
}

class GP_Terms_Of_Service extends GP_Plugin {

	private static $instance = null;

	protected $_version     = GP_TERMS_OF_SERVICE_VERSION;
	protected $_path        = 'gwtermsofservice/gwtermsofservice.php';
	protected $_full_path   = __FILE__;
	protected $_slug        = 'gp-terms-of-service';
	protected $_title       = 'Gravity Forms Terms of Service';
	protected $_short_title = 'Terms of Service';

	public function minimum_requirements() {
		return array(
			'gravityforms' => array(
				'version' => '1.9.18.2',
			),
			'plugins'      => array(
				'gravityperks/gravityperks.php' => array(
					'name'    => 'Gravity Perks',
					'version' => '1.2.12',
				),
			),
		);
	}

	public static function get_instance() {
		if ( self::$instance === null ) {
			self::$instance = isset( self::$perk ) ? new self( new self::$perk ) : new self();
		}

		return self::$instance;
	}

	public function pre_init() {
		parent::pre_init();

		require_once( 'includes/class-gf-field-terms-of-service.php' );

	}

	public function init() {
		parent::init();

		load_plugin_textdomain( 'gp-terms-of-service', false, basename( dirname( __file__ ) ) . '/languages/' );
	}

	public function tooltips( $tooltips ) {
		$tooltips[ $this->perk->key( 'require_scroll' ) ] = sprintf( '<h6>%s</h6> %s', __( 'Require Full Scroll', 'gp-terms-of-service' ), __( 'Checking this option will disable the acceptance checkbox until the user has scrolled through the full Terms of Service.', 'gp-terms-of-service' ) );
		$tooltips[ $this->perk->key( 'terms' ) ]          = sprintf( '<h6>%s</h6> %s', __( 'The Terms', 'gp-terms-of-service' ), __( 'Specify the terms the user is agreeing to here.', 'gp-terms-of-service' ) );

		return $tooltips;
	}

}

class GWTermsOfService extends GP_Terms_Of_Service { };

function gp_terms_of_service() {
	return GP_Terms_Of_Service::get_instance( null );
}

GFAddOn::register( 'GP_Terms_Of_Service' );
