<?php
/**
 * Class for declaring the content importer used in the Brand Demo Import plugin
 *
 * @package bdi
 */

namespace BDI;

class Importer {
	/**
	 * Time in milliseconds, marking the beginning of the import.
	 *
	 * @var float
	 */
	private $microtime;

	/**
	 * The instance of the Brand Demo Import class.
	 *
	 * @var object
	 */
	private $bdi;

	/**
	 * Constructor method.
	 *
	 * @param array  $importer_options Importer options.
	 */
	public function __construct( $importer_options = array() ) {
		// Get the BDI (main plugin class) instance.
		if( ! class_exists( 'BDI\\BrandDemoImport' ) ) {
			require_once BDI_PATH . 'inc/BrandDemoImport.php';
		}
		$this->bdi = BrandDemoImport::get_instance();
	}

	/**
	 * Imports content from a WordPress export file.
	 *
	 * @param string $data_file path to xml file, file with WordPress export data.
	 */
	public function import( $data_file ) {
		$this->importer->import( $data_file );
	}

	/**
	 * Import content from an WP XML file.
	 *
	 * @param string $import_file_path Path to the import file.
	 */
	public function import_content( $import_file_path ) {
		$this->microtime = microtime( true );
		$importer_error = '';

		// Increase PHP max execution time if it's not disabled in php settings. Just in case, even though the AJAX calls are only 25 sec long.
		$disabled = explode(',', ini_get('disable_functions'));
		if( ! ini_get( 'safe_mode' ) && ! in_array( 'set_time_limit', $disabled ) ) {
			set_time_limit( apply_filters( 'bdi/set_time_limit_for_demo_data_import', 300 ) );
	 	}

		// Make sure importers constant is defined
		if ( ! defined( 'WP_LOAD_IMPORTERS' ) ) {
			define( 'WP_LOAD_IMPORTERS', true );
		}

		// Import file location
		$import_file = ABSPATH . 'wp-admin/includes/import.php';

		// Include import file
		if ( ! file_exists( $import_file ) ) {
			return;
		}

		// Include import file
		require_once( $import_file );

		if ( ! class_exists( 'WP_Importer' ) ) {
			$class_wp_importer = ABSPATH . 'wp-admin/includes/class-wp-importer.php';

			if ( file_exists( $class_wp_importer ) ) {
				require_once $class_wp_importer;
			} else {
				$importer_error = __( 'Can not retrieve class-wp-importer.php', 'bdi' );
			}
		}

		if ( ! class_exists( 'WP_Import' ) ) {
			$class_wp_import = BDI_PATH . 'inc/wordpress-importer.php';

			if ( file_exists( $class_wp_import ) ) {
				require_once $class_wp_import;
			} else {
				$importer_error = __( 'Can not retrieve wordpress-importer.php', 'bdi' );
			}
		}

		// Display error
		if ( $importer_error ) {
			return new \WP_Error( 'xml_import_error', $importer_error );
		} else {
			// Import content.
			if ( ! empty( $import_file_path ) ) {
				$importer = new \WP_Import();
				$importer->fetch_attachments = true;
				ob_start();
				$importer->import( $import_file_path );
				ob_end_clean();
			}
		}

		// Return any error messages for the front page output (errors, critical, alert and emergency level messages only).
		return $importer_error;
	}


	/**
	 * Check if we need to create a new AJAX request, so that server does not timeout.
	 *
	 * @param array $data current post data.
	 * @return array
	 */
	public function new_ajax_request_maybe( $data ) {
		$time = microtime( true ) - $this->microtime;

		// We should make a new ajax call, if the time is right.
		if ( $time > apply_filters( 'bdi/time_for_one_ajax_call', 25 ) ) {
			$response = array(
				'status'  => 'newAJAX',
				'message' => 'Time for new AJAX request!: ' . $time,
			);

			// Add any output to the log file and clear the buffers.
			$message = ob_get_clean();

			// Add any error messages to the frontend_error_messages variable in BDI main class.
			if ( ! empty( $message ) ) {
				$this->bdi->append_to_frontend_error_messages( $message );
			}

			// Add message to log file.
			$log_added = Helpers::append_to_file(
				__( 'New AJAX call!' , 'bdi' ) . PHP_EOL . $message,
				$this->bdi->get_log_file_path(),
				''
			);

			// Set the current importer stat, so it can be continued on the next AJAX call.
			$this->set_current_importer_data();

			// Send the request for a new AJAX call.
			wp_send_json( $response );
		}

		return $data;
	}

}
