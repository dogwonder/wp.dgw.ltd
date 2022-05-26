<?php
/**
 * dgwltd functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package dgwltd
 */

 /*********************
SCRIPTS & ENQUEUEING
*********************/
$envs = array(
	'development' => 'http://dev.sandbox.dev',
	'staging'     => 'https://staging.dogwonder.dev',
	'production'  => 'https://www.dogwonder.dev'
  );

  define('ENVIRONMENTS', serialize($envs));
  
  if ( ! function_exists( 'dgwltd_env' ) ) :
	function dgwltd_env($env) {
		$site_url = site_url();
		switch ($env) {
		case 'dev':
				if(strpos($site_url, 'dev.dogwonder.dev') !== FALSE) {
					return true;
				}
		break;
		case 'staging':
			if(strpos($site_url, 'staging.dogwonder.dev') !== FALSE) {
				return true;
			}
		break;
		default:
			'prod';
		break;
		}
	}
endif;

if ( ! function_exists( 'dgwltd_setup' ) ) :
	/**
	 * Sets up theme defaults and registers support for various WordPress features.
	 *
	 * Note that this function is hooked into the after_setup_theme hook, which
	 * runs before the init hook. The init hook is too late for some features, such
	 * as indicating support for post thumbnails.
	 */
	function dgwltd_setup() {
		/*
		 * Make theme available for translation.
		 * Translations can be filed in the /languages/ directory.
		 * If you're building a theme based on dgwltd, use a find and replace
		 * to change 'dgwltd' to the name of your theme in all the template files.
		 */
		load_theme_textdomain( 'dgwltd', get_template_directory() . '/languages' );

		// Add default posts and comments RSS feed links to head.
		add_theme_support( 'automatic-feed-links' );

		/*
		 * Enable support for Post Thumbnails on posts and pages.
		 *
		 * @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
		 */
		add_theme_support( 'post-thumbnails' );

		// Image sizes;
		add_image_size( 'dgwltd-tiny', 16, 0, false ); // For Low quality image placeholders (LQIP)
		add_image_size( 'dgwltd-small', 320, 0, false );
		add_image_size( 'dgwltd-medium', 640, 0, false );
		add_image_size( 'dgwltd-medium-crop', 640, 640, array( 'center', 'center' ) );
		add_image_size( 'dgwltd-large', 1536, 0, false );
		add_image_size( 'dgwltd-social-image', 1200, 630 );

		// This theme uses wp_nav_menu() in one location.
		register_nav_menus(
			array(
				'primary' => esc_html__( 'Primary', 'dgwltd' ),
				'footer-links' => __( 'Footer Menu', 'dgwltd' ), 
				'legal'   => esc_html__( 'Legal', 'dgwltd' )
			)
		);

		/*
		 * Switch default core markup for search form, comment form, and comments
		 * to output valid HTML5.
		 */
		add_theme_support(
			'html5',
			array(
				'comment-form',
				'comment-list',
				'gallery',
				'caption',
				'style',
				'script',
				'navigation-widgets',
			)
		);

		// Add support for full and wide align images.
		add_theme_support( 'align-wide' );

		// Add support for block styles.
		add_theme_support( 'wp-block-styles' );

	}

endif;
add_action( 'after_setup_theme', 'dgwltd_setup' );

// Remove admin stuff - e.g. Emojis
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );
remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
remove_action( 'admin_print_styles', 'print_emoji_styles' );

/**
 * Register widget area.
 *
 * @link https://developer.wordpress.org/themes/functionality/sidebars/#registering-a-sidebar
 */
function dgwltd_widgets_init() {
	register_sidebar(
		array(
			'name'          => esc_html__( 'Sidebar', 'dgwltd' ),
			'id'            => 'sidebar-1',
			'description'   => esc_html__( 'Add widgets here.', 'dgwltd' ),
			'before_widget' => '<section id="%1$s" class="widget %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h2 class="widget-title">',
			'after_title'   => '</h2>',
		)
	);
}
add_action( 'widgets_init', 'dgwltd_widgets_init' );

/**
 * Enqueue scripts and styles.
 */
function dgwltd_scripts() {

	// Register theme stylesheet.
	$theme_version = wp_get_theme()->get( 'Version' );

	$version_string = is_string( $theme_version ) ? $theme_version : false;
	wp_register_style(
		'dgwltd-style',
		get_template_directory_uri() . '/style.css',
		array(),
		$version_string
	);

	// Add styles inline.
	wp_add_inline_style( 'dgwltd-style', dgwltd_get_font_face_styles() );

	// Enqueue theme stylesheet.
	wp_enqueue_style( 'dgwltd-style' );

	wp_deregister_script( 'jquery' );

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}

}
add_action( 'wp_enqueue_scripts', 'dgwltd_scripts' );

if ( ! function_exists( 'dgwltd_editor_styles' ) ) :

	/**
	 * Enqueue editor styles.
	 *
	 * @since Twenty Twenty-Two 1.0
	 *
	 * @return void
	 */
	function dgwltd_editor_styles() {

		// Add styles inline.
		wp_add_inline_style( 'wp-block-library', dgwltd_get_font_face_styles() );

	}

endif;

add_action( 'admin_init', 'dgwltd_editor_styles' );


if ( ! function_exists( 'dgwltd_get_font_face_styles' ) ) :

	/**
	 * Get font face styles.
	 * Called by functions twentytwentytwo_styles() and twentytwentytwo_editor_styles() above.
	 *
	 * @since Twenty Twenty-Two 1.0
	 *
	 * @return string
	 */
	function dgwltd_get_font_face_styles() {

		return "
		@font-face {
			font-family: 'Söhne Dreiviertelfett';
			font-weight: 700;
			font-style: normal;
			font-display: swap;
			src: url('" . get_theme_file_uri( 'dist/fonts/soehne/soehne-web-dreiviertelfett.woff2' ) . "') format('woff2');
		  }
		  
		  @font-face {
			font-family: 'Söhne Halbfett';
			font-weight: 600;
			font-style: normal;
			font-display: swap;
			src: url('" . get_theme_file_uri( 'dist/fonts/soehne/soehne-web-halbfett.woff2' ) . "') format('woff2');
		  }
		  
		  @font-face {
			font-family: 'Söhne Leicht';
			font-weight: 300;
			font-style: normal;
			font-display: swap;
			src: url('" . get_theme_file_uri( 'dist/fonts/soehne/soehne-web-leicht.woff2' ) . "') format('woff2');
		  }
		";

	}

endif;

/**
 * Custom template tags for this theme.
 */
require get_template_directory() . '/inc/dgwltd-template-tags.php';

/**
 * Functions which enhance the theme by hooking into WordPress.
 */
require get_template_directory() . '/inc/dgwltd-functions.php';

/**
 * Functions for Advanced Custom Fields
 */
require get_template_directory() . '/inc/dgwltd-acf.php';

/**
 * Functions for forms
 */
require get_template_directory() . '/inc/dgwltd-forms.php';


/**
 * Customizer additions.
 */
require get_template_directory() . '/inc/customizer.php';
