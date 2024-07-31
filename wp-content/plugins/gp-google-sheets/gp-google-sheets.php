<?php
/**
 * Plugin Name: GP Google Sheets
 * Plugin URI: https://gravitywiz.com/documentation/gravity-forms-google-sheets/
 * Description: Automatically send and sync Gravity Forms data with Google Sheets - and unlock new possibilities for your data.
 * Version: 1.2.1
 * Author: Gravity Wiz
 * Author URI: http://gravitywiz.com/
 * Text Domain: gp-google-sheets
 * Domain Path: /languages
 * License: GPL2
 * Perk: True
 */

defined( 'ABSPATH' ) or exit;

define( 'GP_GOOGLE_SHEETS_VERSION', '1.2.1' );

//Initialize this Perk
require_once plugin_dir_path( __FILE__ ) . 'includes/autoload.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/functions.php';
require_once plugin_dir_path( __FILE__ ) . 'third-party/woocommerce/action-scheduler/action-scheduler.php';

$GP_Google_Sheets_Bootstrap = new \GP_Google_Sheets\GP_Bootstrap( 'class-gp-google-sheets.php', __FILE__ );

/*
* Gravity Flow compatibility. We need to load Steps sooner than GF Add-ons are typically initialized.
*/
add_action( 'gravityflow_loaded', function() {
	Gravity_Flow_Steps::register( new \GP_Google_Sheets\Compatibility\Gravity_Flow_Step_Feed_GP_Google_Sheets() );
} );
