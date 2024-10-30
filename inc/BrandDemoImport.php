<?php
/**
 * Main Brand Demo Import plugin class/file.
 *
 * @package bdi
 */

namespace BDI;

/**
 * Brand Demo Import class, so we don't have to worry about namespaces.
 */
class BrandDemoImport {
	/**
	 * The instance *Singleton* of this class
	 *
	 * @var object
	 */
	private static $instance;

	/**
	 * The instance of the BDI\Importer class.
	 *
	 * @var object
	 */
	private $importer;

	/**
	 * The resulting page's hook_suffix, or false if the user does not have the capability required.
	 *
	 * @var boolean or string
	 */
	private $plugin_page;

	/**
	 * Holds the verified import files.
	 *
	 * @var array
	 */
	private $import_files;

	/**
	 * The index of the `import_files` array (which import files was selected).
	 *
	 * @var int
	 */
	private $selected_index;

	/**
	 * The paths of the actual import files to be used in the import.
	 *
	 * @var array
	 */
	private $selected_import_files;

	/**
	 * Holds any error messages, that should be printed out at the end of the import.
	 *
	 * @var string
	 */
	private $frontend_error_messages = array();

	/**
	 * Returns the *Singleton* instance of this class.
	 *
	 * @return PT_One_Click_Demo_Import the *Singleton* instance.
	 */
	public static function get_instance() {
		if ( null === static::$instance ) {
			static::$instance = new static();
		}

		return static::$instance;
	}


	/**
	 * Class construct function, to initiate the plugin.
	 * Protected constructor to prevent creating a new instance of the
	 * *Singleton* via the `new` operator from outside of this class.
	 */
	protected function __construct() {
		// Loads Helpers class
		if( ! class_exists( 'BDI\\Helpers' ) ) {
			require_once( BDI_PATH . 'inc/Helpers.php' );
		}

		// Actions.
		add_action( 'admin_menu', array( $this, 'create_top_menu_page' ) );
		add_action( 'admin_menu', array( $this, 'remove_theme_submenu' ), 999 );
		add_action( 'admin_menu', array( $this, 'create_plugin_page' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_action( 'wp_ajax_bdi_import_demo_data', array( $this, 'import_demo_data_ajax_callback' ) );
		add_action( 'after_setup_theme', array( $this, 'setup_plugin_with_filter_data' ) );
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
	}


	/**
	 * Private clone method to prevent cloning of the instance of the *Singleton* instance.
	 *
	 * @return void
	 */
	private function __clone() {}


	/**
	 * Private unserialize method to prevent unserializing of the *Singleton* instance.
	 *
	 * @return void
	 */
	private function __wakeup() {}


	/**
	 * Creates the main settings page for Brand theme and its plugins if it doesn't exist.
	 */
	public function create_top_menu_page() {
		 global $admin_page_hooks;
		 if( empty( $admin_page_hooks['brand_setting_page'] )) {
			 add_menu_page(
	 				'Brand Theme',
	 				'Brand Theme',
	 				'manage_options',
	 				'brand_setting_page'
	 		);
		 }
	}

	/**
	 * removes Brand theme settings page  and its plugins if it doesn't exist.
	 */
	public function remove_theme_submenu() {
 		remove_submenu_page( 'themes.php', 'brand_setting_page' );
 	}

	/**
	 * Creates the plugin page and a submenu item in WP Appearance menu.
	 */
	public function create_plugin_page() {
		$plugin_page_setup = apply_filters( 'bdi/plugin_page_setup', array(
				'parent_slug' => 'brand_setting_page',
				'page_title'  => esc_html__( 'Brand Demo Import' , 'bcdi' ),
				'menu_title'  => esc_html__( 'Import Demo Data' , 'bdi' ),
				'capability'  => 'import',
				'menu_slug'   => 'brand-demo-import',
			)
		);

		$this->plugin_page = add_submenu_page(
			$plugin_page_setup['parent_slug'],
			$plugin_page_setup['page_title'],
			$plugin_page_setup['menu_title'],
			$plugin_page_setup['capability'],
			$plugin_page_setup['menu_slug'],
			apply_filters( 'bdi/plugin_page_display_callback_function', array( $this, 'display_plugin_page' ) )
		);
	}


	/**
	 * Plugin page display.
	 * Output (HTML) is in another file.
	 */
	public function display_plugin_page() {
		require_once BDI_PATH . 'views/plugin-page.php';
	}


	/**
	 * Enqueue admin scripts (JS and CSS)
	 *
	 * @param string $hook holds info on which admin page you are currently loading.
	 */
	public function admin_enqueue_scripts( $hook ) {
		// Enqueue the scripts only on the plugin page.
		if ( $this->plugin_page === $hook ) {
			wp_enqueue_script( 'jquery-ui-dialog' );
			wp_enqueue_style( 'wp-jquery-ui-dialog' );

			wp_enqueue_script( 'bdi-main-js', BDI_URL . 'assets/js/main.js' , array( 'jquery', 'jquery-ui-dialog' ), BDI_VERSION );

			// Get theme data.
			$theme = wp_get_theme();

			wp_localize_script( 'bdi-main-js', 'bdi',
				array(
					'ajax_url'         => admin_url( 'admin-ajax.php' ),
					'ajax_nonce'       => wp_create_nonce( 'bdi-ajax-verification' ),
					'import_files'     => $this->import_files,
					'wp_customize_on'  => apply_filters( 'bdi/enable_wp_customize_save_hooks', false ),
					'import_popup'     => apply_filters( 'bdi/enable_grid_layout_import_popup_confirmation', true ),
					'theme_screenshot' => $theme->get_screenshot(),
					'texts'            => array(
						'missing_preview_image' => esc_html__( 'No preview image defined for this import.', 'bdi' ),
						'dialog_title'          => esc_html__( 'Are you sure?', 'bdi' ),
						'dialog_no'             => esc_html__( 'Cancel', 'bdi' ),
						'dialog_yes'            => esc_html__( 'Yes, import!', 'bdi' ),
						'selected_import_title' => esc_html__( 'Selected demo import:', 'bdi' ),
					),
					'dialog_options' => apply_filters( 'bdi/confirmation_dialog_options', array() )
				)
			);

			wp_enqueue_style( 'bdi-main-css', BDI_URL . 'assets/css/main.css', array() , BDI_VERSION );
		}
	}

	/**
	 * Main AJAX callback function for:
	 * 1). prepare import files (uploaded or predefined via filters)
	 * 2). execute 'before content import' actions (before import WP action)
	 * 3). import content
	 * 4). execute 'after content import' actions (before widget import WP action, widget import, customizer import, after import WP action)
	 */
	public function import_demo_data_ajax_callback() {
		// Try to update PHP memory limit (so that it does not run out of it).
		ini_set( 'memory_limit', apply_filters( 'bdi/import_memory_limit', '350M' ) );

		// Verify if the AJAX call is valid (checks nonce and current_user_can).
		require_once BDI_PATH . 'inc/Helpers.php';
		Helpers::verify_ajax_call();

			// Create a date and time string to use for demo and log file names.
			Helpers::set_demo_import_start_time();

			// Get selected file index or set it to 0.
			$this->selected_index = empty( $_POST['selected'] ) ? 0 : absint( $_POST['selected'] );

			/**
			 * 1). Prepare import files.
			 * Manually uploaded import files or predefined import files via filter: bdi/import_files
			 */
			if ( ! empty( $_FILES ) ) { // Using manual file uploads?
				// Get paths for the uploaded files.
				$this->selected_import_files = Helpers::process_uploaded_files( $_FILES, $this->log_file_path );

				// Set the name of the import files, because we used the uploaded files.
				$this->import_files[ $this->selected_index ]['import_file_name'] = esc_html__( 'Manually uploaded files', 'bdi' );
			}
			elseif ( ! empty( $this->import_files[ $this->selected_index ] ) ) { // Use predefined import files from wp filter: bdi/import_files.
				// Download the import files (content, widgets and customizer files).
				$this->selected_import_files = Helpers::download_import_files( $this->import_files[ $this->selected_index ] );
				// Check Errors.
				if ( is_wp_error( $this->selected_import_files ) ) {
					// Send an AJAX response with the error.
					Helpers::error_send_ajax_response( $this->selected_import_files->get_error_message() );
				}

			}
			else {
				// Send JSON Error response to the AJAX call.
				wp_send_json( esc_html__( 'No import files specified!', 'bdi' ) );
			}

		// Save the initial import data as a transient, so other import parts (in new AJAX calls) can use that data.
		Helpers::set_bdi_import_data_transient( $this->get_current_importer_data() );

		/**
		 * Import content.
		 * Returns errors that will be displayed on front page.
		 */
		//$this->append_to_frontend_error_messages( $this->importer->import_content( $this->selected_import_files['content'] ) );
		$this->importer->import_content( $this->selected_import_files['content'] );
		if( ! empty( $this->frontend_error_messages ) ) {
			wp_send_json( $this->frontend_error_messages );
		}

		/**
		 *
		 * Import widgets.
		 */
		$this->widgets_import( $this->selected_import_files, $this->import_files, $this->selected_index );

		/**
		 *
		 * Import customizer options and mods.
		 */
		$this->customizer_import( $this->selected_import_files, $this->import_files, $this->selected_index );

		/**
		 *
		 * Set nav menu location.
		 */
		 if( isset( $this->import_files[ $this->selected_index ]['primary_menu'] ) ) {
			 $primary_menu = $this->import_files[ $this->selected_index ]['primary_menu'];
			 $main_menu = get_term_by( 'name', $primary_menu, 'nav_menu' );
			 set_theme_mod( 'nav_menu_locations', array(
	             'primary' => $main_menu->term_id,
	         )
	     );
		 }

		 /**
 		 *
 		 * Set front page.
 		 */
 		 if( isset( $this->import_files[ $this->selected_index ]['front_page'] ) ) {
 			 $front_page = $this->import_files[ $this->selected_index ]['front_page'];
			 $front_page = get_page_by_title( $front_page );
			 update_option( 'show_on_front', 'page' );
	     update_option( 'page_on_front', $front_page->ID );
 		 }

		 /**
 		 *
 		 * Set blog page.
 		 */
 		 if( isset( $this->import_files[ $this->selected_index ]['blog_page'] ) ) {
 			 $blog_page = $this->import_files[ $this->selected_index ]['blog_page'];
			 $blog_page = get_page_by_title( $blog_page );
	     update_option( 'page_for_posts', $blog_page->ID );
 		 }

		 /**
 		 *
 		 * Set portfolio page.
 		 */
 		 if( isset( $this->import_files[ $this->selected_index ]['portfolio_page'] ) ) {
 			 $portfolio_page = $this->import_files[ $this->selected_index ]['portfolio_page'];
			 $portfolio_page = get_page_by_title( $portfolio_page );
	     update_option( 'brand_portfolio_page', $portfolio_page );
 		 }

		 /**
 		 *
 		 * Set Elementor container width.
 		 */
 		 if( isset( $this->import_files[ $this->selected_index ]['elementor_container_width'] ) ) {
 			 $elementor_container_width = $this->import_files[ $this->selected_index ]['elementor_container_width'];
	     update_option( 'elementor_container_width', $elementor_container_width );
 		 }

		 /**
 		 *
 		 * Set sections background images ids.
 		 */
 		 if( isset( $this->import_files[ $this->selected_index ]['sections_pages'] ) ) {
			 $this->set_sections_background_ids( $this->import_files[ $this->selected_index ]['sections_pages'] );
 		 }

		// Save the import data as a transient, so other import parts (in new AJAX calls) can use that data.
		Helpers::set_bdi_import_data_transient( $this->get_current_importer_data() );

		// Send a JSON response with final report.
		$this->final_response();
	}

	/**
	 * Execute the widgets import.
	 *
	 * @param array $selected_import_files Actual selected import files (content, widgets, customizer).
	 * @param array $import_files          The filtered import files defined in `bdi/import_files` filter.
	 * @param int   $selected_index        Selected index of import.
	 */
	public function widgets_import( $selected_import_files, $import_files, $selected_index ) {
		if ( ! empty( $selected_import_files['widgets'] ) ) {
			require_once BDI_PATH . 'inc/WidgetImporter.php';
			WidgetImporter::import( $selected_import_files['widgets'] );
		}
	}

	/**
	 * Execute the customizer import.
	 *
	 * @param array $selected_import_files Actual selected import files (content, widgets, customizer).
	 */
	public function customizer_import( $selected_import_files ) {
		if ( ! empty( $selected_import_files['customizer'] ) ) {
			require_once BDI_PATH . 'inc/CustomizerImporter.php';
			CustomizerImporter::import( $selected_import_files['customizer'] );
		}
	}


	/**
	 * Set sections background images ids.
	 *
	 * @param array $sections_pages Pages name containing sections.
	 */
	public function set_sections_background_ids( $sections_pages ) {
		foreach ( $sections_pages as $sections_page) {
		 $page_meta = get_page_by_title( $sections_page );
		 $page_sections_meta = get_post_meta( $page_meta->ID, '_brand_sections_meta', true );
		 $sections_count = count( $page_sections_meta['section_background_image'] );
		 for ($i = 0; $i < $sections_count; $i++) {
			 if( $page_sections_meta['section_background_image'][$i] !== 0 ) {
				 $background_meta = get_page_by_title( $page_sections_meta['section_background_image_title'][$i], 'OBJECT', 'attachment' );
				 $page_sections_meta['section_background_image'][$i] = $background_meta->ID;
			 }
		 }
		 update_post_meta( $page_meta->ID, '_brand_sections_meta', $page_sections_meta );
		}
	}


	/**
	 * Send a JSON response with final report.
	 */
	private function final_response() {
		// Delete importer data transient for current import.
		delete_transient( 'bdi_importer_data' );

		// Display final messages (success or error messages).
		if ( empty( $this->frontend_error_messages ) ) {
			$response['message'] = '';

			if ( ! apply_filters( 'bdi/disable_pt_branding', false ) ) {
				$twitter_status = esc_html__( 'Just installed Brand Theme  and it\'s awesome! Thanks @MaxsdesignBrand! https://www.wp-brandtheme.com/', 'bdi' );

				$response['message'] .= sprintf(
					__( '%1$sThis plugin is based on the awesome One Click Demo Import plugin created by %3$sProteusThemes%4$s. %2$s%5$sClick to Tweet!%4$s%6$s', 'bdi' ),
					'<div class="notice  notice-info"><p>',
					'<br>',
					'<strong><a href="https://www.proteusthemes.com/" target="_blank">',
					'</a></strong>',
					'<strong><a href="' . add_query_arg( 'status', urlencode( $twitter_status ), 'http://twitter.com/home' ) . '" target="_blank">',
					'</p></div>'
				);
			}

			$response['message'] .= sprintf(
				__( '%1$s%3$sThat\'s it, all done!%4$s%2$sThe demo import has finished. Please check your page and make sure that everything has imported correctly. If it did, you can deactivate the %3$sBrand Demo Import%4$s plugin, because it has done its job.%5$s', 'bdi' ),
				'<div class="notice  notice-success"><p>',
				'<br>',
				'<strong>',
				'</strong>',
				'</p></div>'
			);
		}
		else {
			$response['message'] = $this->frontend_error_messages_display() . '<br>';
			$response['message'] .= sprintf(
				__( '%1$sThe demo import has finished, but there were some import errors.%2$s', 'bdi' ),
				'<div class="notice  notice-warning"><p>',
				'</p></div>'
			);
		}

		wp_send_json( $response );
	}


	/**
	 * Get content importer data, so we can continue the import with this new AJAX request.
	 *
	 * @return boolean
	 */
	private function use_existing_importer_data() {
		if ( $data = get_transient( 'bdi_importer_data' ) ) {
			$this->frontend_error_messages = empty( $data['frontend_error_messages'] ) ? array() : $data['frontend_error_messages'];
			$this->log_file_path           = empty( $data['log_file_path'] ) ? '' : $data['log_file_path'];
			$this->selected_index          = empty( $data['selected_index'] ) ? 0 : $data['selected_index'];
			$this->selected_import_files   = empty( $data['selected_import_files'] ) ? array() : $data['selected_import_files'];
			$this->importer->set_importer_data( $data );

			return true;
		}
		return false;
	}


	/**
	 * Get the current state of selected data.
	 *
	 * @return array
	 */
	public function get_current_importer_data() {
		return array(
			'frontend_error_messages' => $this->frontend_error_messages,
			'selected_index'          => $this->selected_index,
			'selected_import_files'   => $this->selected_import_files,
		);
	}


	/**
	 * Getter function to retrieve the private log_file_path value.
	 *
	 * @return string The log_file_path value.
	 */
	public function get_log_file_path() {
		return $this->log_file_path;
	}


	/**
	 * Setter function to append additional value to the private frontend_error_messages value.
	 *
	 * @param string $additional_value The additional value that will be appended to the existing frontend_error_messages.
	 */
	public function append_to_frontend_error_messages( $text ) {
		$lines = array();

		if ( ! empty( $text ) ) {
			$text = str_replace( '<br>', PHP_EOL, $text );
			$lines = explode( PHP_EOL, $text );
		}

		foreach ( $lines as $line ) {
			if ( ! empty( $line ) && ! in_array( $line , $this->frontend_error_messages ) ) {
				$this->frontend_error_messages[] = $line;
			}
		}
	}


	/**
	 * Display the frontend error messages.
	 *
	 * @return string Text with HTML markup.
	 */
	public function frontend_error_messages_display() {
		$output = '';

		if ( ! empty( $this->frontend_error_messages ) ) {
			foreach ( $this->frontend_error_messages as $line ) {
				$output .= esc_html( $line );
				$output .= '<br>';
			}
		}

		return $output;
	}


	/**
	 * Load the plugin textdomain, so that translations can be made.
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'bdi', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );
	}

	/**
	 * Get data from filters, after the theme has loaded and instantiate the importer.
	 */
	public function setup_plugin_with_filter_data() {
		// Get info of import data files and filter it.
		require_once BDI_PATH . 'inc/Helpers.php';
		$this->import_files = Helpers::validate_import_file_info( apply_filters( 'bdi/import_files', array() ) );


		// Importer options array.
		$importer_options = apply_filters( 'bdi/importer_options', array(
			'fetch_attachments' => true,
		) );

		// Create importer instance with proper parameters.
		if( ! class_exists( 'BDI\Importer' ) ) {
			require_once( BDI_PATH . 'inc/Importer.php' );
		}
		$this->importer = new Importer( $importer_options );
	}
}
