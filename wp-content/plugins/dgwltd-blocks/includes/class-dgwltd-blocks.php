<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://dgw.ltd
 * @since      1.0.0
 *
 * @package    dgwltd_Blocks
 * @subpackage dgwltd_Blocks/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    dgwltd_Blocks
 * @subpackage dgwltd_Blocks/includes
 * @author     Rich Holman <dogwonder@gmail.com>
 */
class dgwltd_Blocks {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      dgwltd_Blocks_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $dgwltd_Blocks    The string used to uniquely identify this plugin.
	 */
	protected $dgwltd_Blocks;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'ONE_BLOCKS_VERSION' ) ) {
			$this->version = ONE_BLOCKS_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->dgwltd_Blocks = 'dgwltd-blocks';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->define_acf_hooks();
		$this->define_block_patterns_hooks();
		$this->define_block_rules();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - dgwltd_Blocks_Loader. Orchestrates the hooks of the plugin.
	 * - dgwltd_Blocks_I18n. Defines internationalization functionality.
	 * - dgwltd_Blocks_Admin. Defines all hooks for the admin area.
	 * - dgwltd_Blocks_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-dgwltd-blocks-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-dgwltd-blocks-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-dgwltd-blocks-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-dgwltd-blocks-public.php';

		/**
		 * The class responsible for defining all actions that occur for building out the custom blocks
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-dgwltd-blocks-acf.php';

		/**
		 * The class responsible for defining all actions that occur for building out the block patterns
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-dgwltd-blocks-patterns.php';

		/**
		 * The class responsible for defining all actions that occur for building out the custom rules
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-dgwltd-blocks-rules.php';


		$this->loader = new dgwltd_Blocks_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the dgwltd_Blocks_I18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new dgwltd_Blocks_I18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new dgwltd_Blocks_Admin( $this->get_dgwltd_Blocks(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'dgwltd_enqueue_admin_styles' );
		// $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'dgwltd_enqueue_admin_scripts' );
	}

	/**
	 * Register all of the hooks related to the ACF blocks area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_acf_hooks() {

		$plugin_acf = new dgwltd_Blocks_ACF();

		$this->loader->add_action( 'acf/init', $plugin_acf, 'dgwltd_register_blocks' );
		$this->loader->add_action( 'acf/settings/save_json', $plugin_acf, 'dgwltd_acf_json_save_point' );
		$this->loader->add_action( 'acf/settings/load_json', $plugin_acf, 'dgwltd_acf_json_load_point' );

	}

	/**
	 * Register all of the hooks related to the block patterns
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_block_patterns_hooks() {

		$plugin_patterns = new dgwltd_Blocks_Patterns();

		$this->loader->add_action( 'init', $plugin_patterns, 'dgwltd_register_block_categories' );
		$this->loader->add_action( 'init', $plugin_patterns, 'dgwltd_register_block_patterns' );

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new dgwltd_Blocks_Public( $this->get_dgwltd_Blocks(), $this->get_version() );

		// $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'dgwltd_enqueue_theme_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'dgwltd_enqueue_theme_scripts' );

	}

	/**
	 * Register all of the hooks related to the ACF blocks area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_block_rules() {

		$plugin_rules = new ONE_Blocks_Rules();
		$this->loader->add_filter( 'allowed_block_types_all', $plugin_rules, 'one_register_block_rules' );
		
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_dgwltd_Blocks() {
		return $this->dgwltd_Blocks;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    dgwltd_Blocks_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
