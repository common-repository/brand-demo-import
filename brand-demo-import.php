<?php

/*
Plugin Name: Brand Demo Import
Plugin URI: https://www.wp-brandtheme.com/downloads/brand-demo-import/
Description: Import Brand theme demos with one click.
Version: 1.0.3
Author: Massimo Sanfelice | Maxsdesign
Author URI: https://wp-brandtheme.com
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: bdi
*/

/*
* This plugin is based on https://wordpress.org/plugins/one-click-demo-import/
*
*/

// Block direct access to the main plugin file.
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/**
 * Main plugin class with initialization tasks.
 */
class BDI_Plugin {
	/**
	 * Constructor for this class.
	 */
	public function __construct() {
		// Retrieve active theme.
		$theme = wp_get_theme();

		/**
		 * Display admin error message if PHP version is older than 5.4.
		 * Otherwise execute the main plugin class.
		 */
		if ( version_compare( phpversion(), '5.4', '<' ) ) {
			add_action( 'admin_notices', array( $this, 'old_php_admin_error_notice' ) );
		}

		/**
		 * Display admin error message if Brand theme is not active.
		 * Otherwise execute the main plugin class.
		 */
		 if($theme->template !== 'brand') {
			 add_action( 'admin_notices', array( $this, 'wrong_theme_error_notice' ) );
		 }

		else {
			// Set plugin constants.
			$this->set_plugin_constants();

			// Load main class.
			require_once BDI_PATH . 'inc/BrandDemoImport.php';
			require_once BDI_PATH . 'inc/Helpers.php';

			// Instantiate the main plugin class *Singleton*.
			$brand_demo_import = BDI\BrandDemoImport::get_instance();
		}
	}


	/**
	 * Display an admin error notice when PHP is older the version 5.4.
	 * Hook it to the 'admin_notices' action.
	 */
	public function old_php_admin_error_notice() {
		$message = sprintf( esc_html__( 'The %2$sBrand Demo Import%3$s plugin requires %2$sPHP 5.4+%3$s to run properly. Please contact your hosting company and ask them to update the PHP version of your site to at least PHP 5.4%4$s Your current version of PHP: %2$s%1$s%3$s', 'bdi' ), phpversion(), '<strong>', '</strong>', '<br>' );

		printf( '<div class="notice notice-error is-dismissible"><p>%1$s</p></div>', wp_kses_post( $message ) );
	}

	/**
	 * Display an admin error notice when Brand theme is not active.
	 * Hook it to the 'admin_notices' action.
	 */
	public function wrong_theme_error_notice() {
		$message = sprintf( esc_html__( 'You have to install and activate %1$sBrand theme%2$s to use Brand Demo Import. %3$s %4$sInstall Brand theme%5$s', 'bdi' ), '<strong>', '</strong>', '</br>', '<a href="' . admin_url( 'theme-install.php?theme=brand' ) . '">', '</a>'  );

		printf( '<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', wp_kses_post( $message ) );
	}


	/**
	 * Set plugin constants.
	 *
	 * Path/URL to root of this plugin, with trailing slash and plugin version.
	 */
	private function set_plugin_constants() {
		// Path/URL to root of this plugin, with trailing slash.
		if ( ! defined( 'BDI_PATH' ) ) {
			define( 'BDI_PATH', plugin_dir_path( __FILE__ ) );
		}
		if ( ! defined( 'BDI_URL' ) ) {
			define( 'BDI_URL', plugin_dir_url( __FILE__ ) );
		}

		// Action hook to set the plugin version constant.
		add_action( 'admin_init', array( $this, 'set_plugin_version_constant' ) );
	}


	/**
	 * Set plugin version constant -> BDI_VERSION.
	 */
	public function set_plugin_version_constant() {
		if ( ! defined( 'BDI_VERSION' ) ) {
			$plugin_data = get_plugin_data( __FILE__ );
			define( 'BDI_VERSION', $plugin_data['Version'] );
		}
	}
}

// Instantiate the plugin class.
$bdi_plugin = new BDI_Plugin();

// Demos to import
function brand_import_demos() {
  return array(
    array(
      'import_file_name'           => 'Simple',
      'categories'                 => array( 'Basic' ),
			'import_file_url'            => 'http://demo1.wp-brandtheme.com/brand-demos/simple/simple.2017-08-17.xml',
      'import_widget_file_url'     => 'http://demo1.wp-brandtheme.com/brand-demos/simple/simple-widgets.wie',
      'import_customizer_file_url' => 'http://demo1.wp-brandtheme.com/brand-demos/simple/brand-settings-export-08-17-2017.json',
			'import_preview_image_url'   => 'http://demo1.wp-brandtheme.com/brand-demos/simple/simple.png',
			'preview_url'                => 'http://demo1.wp-brandtheme.com/',
			'primary_menu'               => 'Top Menu',
			'front_page'                 => 'Home',
			'blog_page'                  => 'Blog',
			'portfolio_page'             => '',
			'sections_pages'             => array( 'Home' ),
			'elementor_container_width'  => '',
      'import_notice'              => __( 'You need to install Contact Form 7 and Brand Premium plugins before install this demo.', 'bdi' ),
    ),

  );
}
add_filter( 'bdi/import_files', 'brand_import_demos' );

add_filter( 'bdi/disable_pt_branding', '__return_true' );
