<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://dgw.ltd
 * @since      1.0.0
 *
 * @package    dgwltd_Blocks
 * @subpackage dgwltd_Blocks/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    dgwltd_Blocks
 * @subpackage dgwltd_Blocks/admin
 * @author     Rich Holman <dogwonder@gmail.com>
 */
class dgwltd_Blocks_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $dgwltd_Blocks    The ID of this plugin.
	 */
	private $dgwltd_Blocks;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string $dgwltd_Blocks       The name of this plugin.
	 * @param      string $version    The version of this plugin.
	 */
	public function __construct( $dgwltd_Blocks, $version ) {

		$this->dgwltd_Blocks = $dgwltd_Blocks;
		$this->version       = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function dgwltd_enqueue_admin_styles() {

		/**
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in dgwltd_Blocks_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The dgwltd_Blocks_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->dgwltd_Blocks, plugin_dir_url( __FILE__ ) . 'css/dgwltd-blocks-editor.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function dgwltd_enqueue_admin_scripts() {

		/**
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in dgwltd_Blocks_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The dgwltd_Blocks_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		// wp_enqueue_script( $this->dgwltd_Blocks, plugin_dir_url( __FILE__ ) . 'js/admin.js', array( 'jquery' ), $this->version, false );
	}

}
