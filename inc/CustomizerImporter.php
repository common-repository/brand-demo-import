<?php
/**
 * Class for the customizer importer used in the Brand Demo Import plugin.
 *
 * Code is mostly from the Customizer Export/Import plugin.
 *
 * @see https://wordpress.org/plugins/customizer-export-import/
 * @package bdi
 */

namespace BDI;

class CustomizerImporter {
	/**
	 * Import customizer from a json file, generated by the Brand Import Export plugin.
	 *
	 * @param string $customizer_import_file_path path to the customizer import file.
	 */
	public static function import( $customizer_import_file_path ) {
		if( ! class_exists( 'BDI\\CustomizerOption' ) ) {
			require_once BDI_PATH . 'inc/CustomizerOption.php';
		}
		if( ! class_exists( 'BDI\\Helpers' ) ) {
			require_once BDI_PATH . 'inc/Helpers.php';
		}
		if( ! class_exists( 'BDI\\BrandDemoImport' ) ) {
			require_once BDI_PATH . 'inc/BrandDemoImport.php';
		}
		$bdi          = BrandDemoImport::get_instance();

		// Try to import the customizer settings.
		$results = self::import_customizer_options( $customizer_import_file_path );

		// Check for errors, else write the results to the log file.
		if ( is_wp_error( $results ) ) {
			$error_message = $results->get_error_message();

			// Add any error messages to the frontend_error_messages variable in BDI main class.
			$bdi->append_to_frontend_error_messages( $error_message );

		}
	}


	/**
	 * Imports uploaded mods and calls WordPress core customize_save actions so
	 * themes that hook into them can act before mods are saved to the database.
	 *
	 * Update: WP core customize_save actions were removed, because of some errors.
	 *
	 * @since 1.1.1
	 * @param string $import_file_path Path to the import file.
	 * @return void|WP_Error
	 */
	public static function import_customizer_options( $import_file_path ) {
		// Setup global vars.
		global $wp_customize;

		// Setup internal vars.
		$template = get_template();

		// Make sure we have an import file.
		if ( ! file_exists( $import_file_path ) ) {
			return new \WP_Error(
				'missing_cutomizer_import_file',
				sprintf(
					esc_html__( 'Error: The customizer import file is missing! File path: %s', 'bdi' ),
					$import_file_path
				)
			);
		}

		// Get the upload data.
		$data = Helpers::data_from_file( $import_file_path );

		// Make sure we got the data.
		if ( is_wp_error( $data ) ) {
			return $data;
		}

		$data = json_decode( $data, true );

		// Data checks.
		if ( ! is_array( $data ) && ( ! isset( $data['options'] ) || ! isset( $data['mods'] ) ) ) {
			return new \WP_Error(
				'customizer_import_data_error',
				esc_html__( 'Error: The customizer import file is not in a correct format. Please make sure to use the correct customizer import file.', 'bdi' )
			);
		}
		if ( $data['template'] !== $template ) {
			return new \WP_Error(
				'customizer_import_wrong_theme',
				esc_html__( 'Error: The customizer import file is not suitable for current theme. You can only import customizer settings for the same theme or a child theme.', 'bdi' )
			);
		}

		// Import images for mods
		$data['mods'] = self::_import_images( $data['mods'] );

		// Import images for options
		$data['options'] = self::_import_images( $data['options'] );

		// Save options
		update_option( 'brand_settings', $data['options'] );

		// If wp_css is set then import it.
		if( function_exists( 'wp_update_custom_css_post' ) && isset( $data['wp_css'] ) && '' !== $data['wp_css'] ) {
			wp_update_custom_css_post( $data['wp_css'] );
		}

		// Loop through the mods.
		foreach ( $data['mods'] as $key => $val ) {

			// Save the mod.
			set_theme_mod( $key, $val );
		}
	}

	/**
	 * Imports images for Customizer options or mods.
	 *
	 * @since 1.0
	 * @access private
	 * @param array $mods An array of customizer mods or options.
	 * @return array The mods or options array with any new import data.
	 */
	static private function _import_images( $mods ) {
		foreach ( $mods as $key => $val ) {

			if ( self::_is_image_url( $val ) ) {

				$data = self::_sideload_image( $val );

				if ( ! is_wp_error( $data ) ) {

					$mods[ $key ] = $data->url;

					// Handle header image controls.
					if ( isset( $mods[ $key . '_data' ] ) ) {
						$mods[ $key . '_data' ] = $data;
						update_post_meta( $data->attachment_id, '_wp_attachment_is_custom_header', get_stylesheet() );
					}
				}
			}
		}

		return $mods;
	}

	/**
	 * Taken from the core media_sideload_image function and
	 * modified to return an array of data instead of html.
	 *
	 * @since 1.0
	 * @access private
	 * @param string $file The image file path.
	 * @return array An array of image data.
	 */
	static private function _sideload_image( $file ) {
		$data = new \stdClass();

		if ( ! function_exists( 'media_handle_sideload' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/media.php' );
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
			require_once( ABSPATH . 'wp-admin/includes/image.php' );
		}
		if ( ! empty( $file ) ) {

			// Set variables for storage, fix file filename for query strings.
			preg_match( '/[^\?]+\.(jpe?g|jpe|gif|png)\b/i', $file, $matches );
			$file_array = array();
			$file_array['name'] = basename( $matches[0] );

			$attachment_name = explode( ".", $file_array['name'] );

			//Checks if attachment already exists.
			$attachment_exists = get_page_by_title( $attachment_name[0], 'OBJECT', 'attachment' );

			// If attachment exists returns the metadata from existing attachment.
			if( $attachment_exists !== NULL ) {

				// Build the object to return.
				$meta					= wp_get_attachment_metadata( $attachment_exists->ID );
				$data->attachment_id	= $attachment_exists->ID;
				$data->url				= wp_get_attachment_url( $attachment_exists->ID );
				$data->thumbnail_url	= wp_get_attachment_thumb_url( $attachment_exists->ID );
				$data->height			= $meta['height'];
				$data->width			= $meta['width'];
				return $data;
			}

			// Download file to temp location.
			$file_array['tmp_name'] = download_url( $file );

			// If error storing temporarily, return the error.
			if ( is_wp_error( $file_array['tmp_name'] ) ) {
				return $file_array['tmp_name'];
			}

			// Do the validation and storage stuff.
			$id = media_handle_sideload( $file_array, 0 );

			// If error storing permanently, unlink.
			if ( is_wp_error( $id ) ) {
				@unlink( $file_array['tmp_name'] );
				return $id;
			}

			// Build the object to return.
			$meta					= wp_get_attachment_metadata( $id );
			$data->attachment_id	= $id;
			$data->url				= wp_get_attachment_url( $id );
			$data->thumbnail_url	= wp_get_attachment_thumb_url( $id );
			$data->height			= $meta['height'];
			$data->width			= $meta['width'];
		}

		return $data;
	}

	/**
	 * Checks to see whether a string is an image url or not.
	 *
	 * @since 1.0
	 * @access private
	 * @param string $string The string to check.
	 * @return bool Whether the string is an image url or not.
	 */
	static private function _is_image_url( $string = '' ) {
		if ( is_string( $string ) ) {

			if ( preg_match( '/\.(jpg|jpeg|png|gif)/i', $string ) ) {
				return true;
			}
		}

		return false;
	}
}
