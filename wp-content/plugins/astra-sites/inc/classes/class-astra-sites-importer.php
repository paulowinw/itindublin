<?php
/**
 * Astra Sites Importer
 *
 * @since  1.0.0
 * @package Astra Sites
 */

use STImporter\Importer\ST_Importer_Helper;
use STImporter\Importer\WXR_Importer\ST_WXR_Importer;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Astra_Sites_Importer' ) ) {

	/**
	 * Astra Sites Importer
	 */
	class Astra_Sites_Importer {

		/**
		 * Instance
		 *
		 * @since  1.0.0
		 * @var self Class object
		 */
		public static $instance = null;

		/**
		 * Set Instance
		 *
		 * @since  1.0.0
		 *
		 * @return self Class object.
		 */
		public static function get_instance() {
			if ( ! isset( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Constructor.
		 *
		 * @since  1.0.0
		 */
		public function __construct() {

			require_once ASTRA_SITES_DIR . 'inc/classes/class-astra-sites-importer-log.php';
			require_once ASTRA_SITES_DIR . 'inc/importers/class-astra-sites-helper.php';
			require_once ASTRA_SITES_DIR . 'inc/importers/class-astra-widget-importer.php';
			require_once ASTRA_SITES_DIR . 'inc/importers/class-astra-customizer-import.php';
			require_once ASTRA_SITES_DIR . 'inc/importers/class-astra-site-options-import.php';

			// Hooks in AJAX.
			add_action( 'wp_ajax_astra-sites-import-wpforms', array( $this, 'import_wpforms' ) );
			add_action( 'wp_ajax_astra-sites-import-cartflows', array( $this, 'import_cartflows' ) );
			add_action( 'wp_ajax_astra-sites-import-cart-abandonment-recovery', array( $this, 'import_cart_abandonment_recovery' ) );
			add_action( 'wp_ajax_astra-sites-import-latepoint', array( $this, 'import_latepoint' ) );
			add_action( 'wp_ajax_astra-sites-verify-required-plugins', array( $this, 'verify_required_plugins' ) );
			add_action( 'astra_sites_import_complete', array( $this, 'clear_related_cache' ) );

			require_once ASTRA_SITES_DIR . 'inc/importers/batch-processing/class-astra-sites-batch-processing.php';

			if ( version_compare( get_bloginfo( 'version' ), '5.1.0', '>=' ) ) {
				add_filter( 'http_request_timeout', array( $this, 'set_timeout_for_images' ), 10, 2 ); //phpcs:ignore WordPressVIPMinimum.Hooks.RestrictedHooks.http_request_timeout -- We need this to avoid timeout on slow servers while installing theme, plugin etc.
			}

			add_action( 'init', array( $this, 'disable_default_woo_pages_creation' ), 2 );
			add_filter( 'upgrader_package_options', array( $this, 'plugin_install_clear_directory' ) );
			add_filter( 'plugins_api', array( $this, 'maybe_download_spectra_v3_beta_version' ), 10, 3 );
		}

		/**
		 * Delete imported posts
		 *
		 * @since 1.3.0
		 * @since 1.4.0 The `$post_id` was added.
		 * Note: This function can be deleted after a few releases since we are performing the delete operation in chunks.
		 *
		 * @param  integer $post_id Post ID.
		 * @return void
		 */
		public function delete_imported_posts( $post_id = 0 ) {

			if ( wp_doing_ajax() ) {
				// Verify Nonce.
				check_ajax_referer( 'astra-sites', '_ajax_nonce' );

				if ( ! current_user_can( 'customize' ) ) {
					wp_send_json_error( __( "Permission denied: You don't have the required capability to delete imported posts. Please contact your site administrator.", 'astra-sites' ) );
				}
			}

			$post_id = isset( $_REQUEST['post_id'] ) ? absint( $_REQUEST['post_id'] ) : $post_id;

			$message = 'Deleted - Post ID ' . $post_id . ' - ' . get_post_type( $post_id ) . ' - ' . get_the_title( $post_id );

			$message = '';
			if ( $post_id ) {

				$post_type = get_post_type( $post_id );
				$message   = 'Deleted - Post ID ' . $post_id . ' - ' . $post_type . ' - ' . get_the_title( $post_id );

				do_action( 'astra_sites_before_delete_imported_posts', $post_id, $post_type );

				Astra_Sites_Importer_Log::add( $message );
				wp_delete_post( $post_id, true );
			}

			Astra_Sites_Helper::success_response( $message );
		}

		/**
		 * Delete imported WP forms
		 *
		 * @since 1.3.0
		 * @since 1.4.0 The `$post_id` was added.
		 * Note: This function can be deleted after a few releases since we are performing the delete operation in chunks.
		 *
		 * @param  integer $post_id Post ID.
		 * @return void
		 */
		public function delete_imported_wp_forms( $post_id = 0 ) {

			if ( ! defined( 'WP_CLI' ) && wp_doing_ajax() ) {
				// Verify Nonce.
				check_ajax_referer( 'astra-sites', '_ajax_nonce' );

				if ( ! current_user_can( 'customize' ) ) {
					wp_send_json_error( __( "Permission denied: You don't have the required capability to delete imported forms. Please contact your site administrator.", 'astra-sites' ) );
				}
			}

			$post_id = isset( $_REQUEST['post_id'] ) ? absint( $_REQUEST['post_id'] ) : $post_id;

			$message = '';
			if ( $post_id ) {

				do_action( 'astra_sites_before_delete_imported_wp_forms', $post_id );

				$message = 'Deleted - Form ID ' . $post_id . ' - ' . get_post_type( $post_id ) . ' - ' . get_the_title( $post_id );
				Astra_Sites_Importer_Log::add( $message );
				wp_delete_post( $post_id, true );
			}

			Astra_Sites_Helper::success_response( $message );
		}

		/**
		 * Delete imported terms
		 *
		 * @since 1.3.0
		 * @since 1.4.0 The `$post_id` was added.
		 * Note: This function can be deleted after a few releases since we are performing the delete operation in chunks.
		 *
		 * @param  integer $term_id Term ID.
		 * @return void
		 */
		public function delete_imported_terms( $term_id = 0 ) {
			if ( ! defined( 'WP_CLI' ) && wp_doing_ajax() ) {
				// Verify Nonce.
				check_ajax_referer( 'astra-sites', '_ajax_nonce' );

				if ( ! current_user_can( 'customize' ) ) {
					wp_send_json_error( __( "Permission denied: You don't have the required capability to delete imported terms. Please contact your site administrator.", 'astra-sites' ) );
				}
			}

			$term_id = isset( $_REQUEST['term_id'] ) ? absint( $_REQUEST['term_id'] ) : $term_id;

			$message = '';
			if ( $term_id ) {
				$term = get_term( $term_id );
				if ( ! is_wp_error( $term ) && ! empty( $term ) && is_object( $term ) ) {

					do_action( 'astra_sites_before_delete_imported_terms', $term_id, $term );

					$message = 'Deleted - Term ' . $term_id . ' - ' . $term->name . ' ' . $term->taxonomy;
					Astra_Sites_Importer_Log::add( $message );
					wp_delete_term( $term_id, $term->taxonomy );
				}
			}

			Astra_Sites_Helper::success_response( $message );
		}

		/**
		 * Delete related transients
		 *
		 * @since 3.1.3
		 */
		public function delete_related_transient() {
			delete_option( 'astra_sites_batch_process_started' );
			Astra_Sites_File_System::get_instance()->delete_demo_content();
			delete_option( 'ast_ai_import_current_url' );
			delete_option( 'astra_sites_ai_import_started' );
		}

		/**
		 * Delete directory when installing plugin.
		 *
		 * Set by enabling `clear_destination` option in the upgrader.
		 *
		 * @since 3.0.10
		 * @param array $options Options for the upgrader.
		 * @return array $options The options.
		 */
		public function plugin_install_clear_directory( $options ) {
			Astra_Sites_Importer_Log::add( 'Checking if plugin directory clearing is needed' );

			if ( true !== astra_sites_has_import_started() ) {
				return $options;
			}

			$is_ast_request = isset( $_REQUEST['is_ast_request'] ) && 'true' === $_REQUEST['is_ast_request']; //phpcs:ignore -- Nonce verification is not required here as we are just reading a request parameter.

			if ( ! $is_ast_request ) {
				Astra_Sites_Importer_Log::add( 'Plugin install clear directory - Not an Astra Sites request, skipping' );
				return $options;
			}

			// Verify Nonce.
			check_ajax_referer( 'astra-sites', 'ajax_nonce' );

			if ( isset( $_REQUEST['clear_destination'] ) && 'true' === $_REQUEST['clear_destination'] ) {
				Astra_Sites_Importer_Log::add( 'Plugin install clear directory enabled - Will clear destination directory' );
				$options['clear_destination'] = true;
			}

			Astra_Sites_Importer_Log::add( 'Plugin install clear directory options returned' );
			return $options;
		}

		/**
		 * Maybe download Spectra v3 beta version during Astra Sites import.
		 *
		 * @param false|object|array $result The result object or array. Default false.
		 * @param string             $action The type of information being requested from the Plugin Installation API.
		 * @param object             $args   Plugin API arguments.
		 * @return false|object|array Modified result.
		 */
		public function maybe_download_spectra_v3_beta_version( $result, $action, $args ) {
			// Only apply during Astra Sites import and for plugin_information action.
			if ( true !== astra_sites_has_import_started() || 'plugin_information' !== $action ) {
				return $result;
			}

			// Only modify for ultimate-addons-for-gutenberg.
			if ( ! isset( $args->slug ) || 'ultimate-addons-for-gutenberg' !== $args->slug ) {
				return $result;
			}

			// Check for our custom request parameter to ensure it's an Astra Sites import request.
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verification is not required here as we are just reading a request parameter.
			if ( ! isset( $_REQUEST['is_ast_request'] ) || 'true' !== sanitize_text_field( $_REQUEST['is_ast_request'] ) ) {
				return $result;
			}

			$spectra_blocks_version = astra_get_site_data( 'spectra-blocks-ver' );
			$class_list             = astra_get_site_data( 'class_list' );

			// Only proceed if it's v3 template.
			if ( empty( $spectra_blocks_version ) || ! in_array( 'spectra-blocks-ver-v3', $class_list, true ) ) {
				return $result;
			}

			// Prepare custom response for Spectra v3 beta.
			if ( ! is_object( $result ) ) {
				$result = new stdClass();
			}

			$result->version       = '3.0.0-beta.2';
			$result->download_link = 'https://downloads.wordpress.org/plugin/ultimate-addons-for-gutenberg.3.0.0-beta.2.zip';

			return $result;
		}

		/**
		 * Restrict WooCommerce Pages Creation process
		 *
		 * Why? WooCommerce creates set of pages on it's activation
		 * These pages are re created via our XML import step.
		 * In order to avoid the duplicacy we restrict these page creation process.
		 *
		 * @since 3.0.0
		 */
		public function disable_default_woo_pages_creation() {
			if ( astra_sites_has_import_started() ) {
				add_filter( 'woocommerce_create_pages', '__return_empty_array' );
			}
		}

		/**
		 * Set the timeout for the HTTP request by request URL.
		 *
		 * E.g. If URL is images (jpg|png|gif|jpeg) are from the domain `https://websitedemos.net` then we have set the timeout by 30 seconds. Default 5 seconds.
		 *
		 * @since 1.3.8
		 *
		 * @param int    $timeout_value Time in seconds until a request times out. Default 5.
		 * @param string $url           The request URL.
		 */
		public function set_timeout_for_images( $timeout_value, $url ) {

			// URL not contain `https://websitedemos.net` then return $timeout_value.
			if ( strpos( $url, 'https://websitedemos.net' ) === false ) {
				return $timeout_value;
			}

			// Check is image URL of type jpg|png|gif|jpeg.
			if ( astra_sites_is_valid_image( $url ) ) {
				$timeout_value = 300;
			}

			return $timeout_value;
		}

		/**
		 * Change flow status
		 *
		 * @since 2.0.0
		 *
		 * @param  array $args Flow query args.
		 * @return array Flow query args.
		 */
		public function change_flow_status( $args ) {
			$args['post_status'] = 'publish';
			return $args;
		}

		/**
		 * Track Flow
		 *
		 * @since 2.0.0
		 *
		 * @param  integer $flow_id Flow ID.
		 * @return void
		 */
		public function track_flows( $flow_id ) {
			Astra_Sites_Importer_Log::add( 'Flow ID ' . $flow_id );
			ST_Importer_Helper::track_post( $flow_id );
		}

		/**
		 * Common function for downloading and validating import data files.
		 *
		 * @since 4.4.39
		 *
		 * @param string $url URL of the JSON data file to download and validate.
		 * @param bool   $decode Whether to decode the JSON data. Default true.
		 * @return string|array|WP_Error Returns the file contents as a string if $decode is false, or an associative array if $decode is true. Returns WP_Error on failure.
		 */
		private function download_and_validate_import_data( $url = '', $decode = true ) {
			// Skip import gracefully if no URL (normal condition).
			if ( empty( $url ) ) {
				// Success response - no data to import is normal.
				if ( defined( 'WP_CLI' ) ) {
					WP_CLI::line( 'No data to import - ' . esc_url( $url ) );
				}

				return array();
			}

			// Validate URL format.
			if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
				return new WP_Error(
					'invalid_url_format',
					sprintf(
						/* translators: %s: URL */
						__( 'Invalid URL format - %s', 'astra-sites' ),
						$url
					)
				);
			}

			// Validate URL security.
			if ( ! astra_sites_is_valid_url( $url ) ) {
				return new WP_Error(
					'invalid_url',
					sprintf(
						/* translators: %s: URL */
						__( 'Invalid data file URL - %s', 'astra-sites' ),
						esc_url_raw( $url )
					)
				);
			}

			// Download JSON file.
			$file_path = ST_WXR_Importer::download_file( $url );
			Astra_Sites_Importer_Log::add( 'File downloaded successfully from: ' . $url, 'success' );

			if ( empty( $file_path['success'] ) ) {
				$error_message = ! empty( $file_path['data'] )
					? $file_path['data']
					: __( 'Could not download data file. Please check your internet connection and try again.', 'astra-sites' );
				Astra_Sites_Importer_Log::add( 'Download failed for URL: ' . $url );
				return new WP_Error(
					'download_failed',
					sprintf(
						/* translators: %s: Error message */
						__( 'File download failed - %s', 'astra-sites' ),
						$error_message
					)
				);
			}

			if ( empty( $file_path['data']['file'] ) ) {
				return new WP_Error(
					'missing_file_path',
					__( 'Downloaded file path is missing in the response.', 'astra-sites' )
				);
			}

			$downloaded_file_path = $file_path['data']['file'];

			// Verify file exists.
			if ( ! file_exists( $downloaded_file_path ) ) {
				return new WP_Error(
					'file_not_found',
					__( 'Downloaded file is missing. Please retry the import.', 'astra-sites' )
				);
			}

			// Verify file is readable.
			if ( ! is_readable( $downloaded_file_path ) ) {
				return new WP_Error(
					'file_not_readable',
					__( 'Downloaded file is not readable. Please check server file permissions.', 'astra-sites' )
				);
			}

			// Validate file extension.
			$ext = strtolower( pathinfo( $downloaded_file_path, PATHINFO_EXTENSION ) );
			if ( 'json' !== $ext ) {
				return new WP_Error(
					'invalid_file_type',
					__( 'The file must be in JSON format.', 'astra-sites' )
				);
			}

			// Read file contents.
			$contents = Astra_Sites::get_instance()->get_filesystem()->get_contents( $downloaded_file_path );

			// If decoding not required, return raw contents.
			if ( ! $decode ) {
				return $contents;
			}

			if ( false === $contents ) {
				return new WP_Error(
					'file_read_failed',
					__( 'Could not read the file from the server.', 'astra-sites' )
				);
			}

			if ( empty( $contents ) ) {
				return new WP_Error(
					'empty_file',
					__( 'The file is empty.', 'astra-sites' )
				);
			}

			// Parse JSON.
			$data       = json_decode( $contents, true );
			$json_error = json_last_error();

			if ( JSON_ERROR_NONE !== $json_error ) {
				return new WP_Error(
					'invalid_json',
					sprintf(
						/* translators: %s: JSON error */
						__( 'Invalid JSON format - %s', 'astra-sites' ),
						json_last_error_msg()
					)
				);
			}

			return $data;
		}

		/**
		 * Import WP Forms
		 *
		 * @since 1.2.14
		 * @since 1.4.0 The `$wpforms_url` was added.
		 *
		 * @param  string $wpforms_url WP Forms JSON file URL.
		 * @return void
		 */
		public function import_wpforms( $wpforms_url = '' ) {
			Astra_Sites_Importer_Log::add( '---' . PHP_EOL );
			Astra_Sites_Importer_Log::add( 'Starting WP Forms import process' );

			if ( ! defined( 'WP_CLI' ) && wp_doing_ajax() ) {
				// Verify Nonce.
				check_ajax_referer( 'astra-sites', '_ajax_nonce' );

				if ( ! current_user_can( 'customize' ) ) {
					wp_send_json_error( __( "Permission denied: You don't have the required capability to import forms. Please contact your site administrator.", 'astra-sites' ) );
				}
			}

			try {
				$screen = ( isset( $_REQUEST['screen'] ) ) ? sanitize_text_field( $_REQUEST['screen'] ) : '';
				$id = ( isset( $_REQUEST['id'] ) ) ? absint( $_REQUEST['id'] ) : '';

				// Get WPForms URL with enhanced error handling.
				if ( 'elementor' === $screen ) {
					if ( empty( $id ) ) {
						Astra_Sites_Helper::error_response(
							sprintf(
								// translators: %s is Elementor plugin name.
								__( 'WPForms import failed: Template ID is missing. Unable to proceed with %s import.', 'astra-sites' ),
								'Elementor'
							)
						);
						return;
					}
					$wpforms_url = astra_sites_get_wp_forms_url( $id );
					Astra_Sites_Importer_Log::add( 'Retrieved WPForms URL for Elementor template: ' . $wpforms_url );
				} else {
					$wpforms_url = astra_get_site_data( 'astra-site-wpforms-path' );
				}

				$ids_mapping = array();

				$forms = $this->download_and_validate_import_data( $wpforms_url );
				if ( is_wp_error( $forms ) ) {
					Astra_Sites_Helper::error_response(
						sprintf(
							// translators: %s is the error message.
							__( 'WPForms import failed: %s', 'astra-sites' ),
							$forms->get_error_message()
						)
					);
					return;
				}

				if ( empty( $forms ) ) {
					Astra_Sites_Helper::success_response(
						array(
							'message' => __( 'No WP Forms to import.', 'astra-sites' ),
							'file'    => $wpforms_url,
						)
					);
					return;
				}

				// Ensure WPForms plugin is loaded.
				$result = $this->ensure_plugin_loaded(
					'wpforms-lite/wpforms.php',
					'WPForms',
					function() {
						return function_exists( 'wpforms_encode' );
					}
				);

				if ( is_wp_error( $result ) ) {
					Astra_Sites_Helper::error_response( $result->get_error_message() );
					return;
				}

				// Process forms with error handling.
				foreach ( $forms as $form ) {
					if ( ! is_array( $form ) ) {
						continue; // Skip invalid form data.
					}

					$title = ! empty( $form['settings']['form_title'] ) ? sanitize_text_field( $form['settings']['form_title'] ) : '';
					$desc  = ! empty( $form['settings']['form_desc'] ) ? sanitize_textarea_field( $form['settings']['form_desc'] ) : '';

					if ( empty( $title ) ) {
						continue; // Skip forms without titles.
					}

					$new_id = post_exists( $title );

					if ( ! $new_id ) {
						try {
							$new_id = wp_insert_post(
								array(
									'post_title'   => $title,
									'post_status'  => 'publish',
									'post_type'    => 'wpforms',
									'post_excerpt' => $desc,
								)
							);

							if ( is_wp_error( $new_id ) ) {
								astra_sites_error_log( 'WPForms import error: ' . $new_id->get_error_message() );
								continue; // Skip this form and continue with others.
							}

							if ( defined( 'WP_CLI' ) ) {
								WP_CLI::line( 'Imported Form ' . $title );
							}

							// Set meta for tracking the post..
							update_post_meta( $new_id, '_astra_sites_imported_wp_forms', true );
							Astra_Sites_Importer_Log::add( 'Successfully inserted WP Form ID ' . $new_id . ' - ' . $title, 'success' );

						} catch ( Exception $e ) {
							astra_sites_error_log( 'WPForms post creation error: ' . $e->getMessage() );
							continue; // Skip this form and continue with others.
						}
					}

					if ( $new_id && ! empty( $form['id'] ) ) {
						// ID mapping..
						$ids_mapping[ $form['id'] ] = $new_id;

						$form['id'] = $new_id;

						try {
							$encoded_form = wpforms_encode( $form );
							wp_update_post(
								array(
									'ID' => $new_id,
									'post_content' => $encoded_form,
								)
							);
						} catch ( Exception $e ) {
							astra_sites_error_log( 'WPForms content update error: ' . $e->getMessage() );
							// Continue even if content update fails.
						}
					}
				}

				// Save ID mapping.
				update_option( 'astra_sites_wpforms_ids_mapping', $ids_mapping, 'no' );
				Astra_Sites_Importer_Log::add( 'WPForms ID mappings saved: ' . count( $ids_mapping ) . ' mappings' );

				Astra_Sites_Helper::success_response(
					array(
						'message' => __( 'WP Forms Imported.', 'astra-sites' ),
						'mapping' => $ids_mapping,
					)
				);
				return;
			} catch ( \Exception $e ) {
				// Catch any unexpected errors.
				astra_sites_error_log( 'WPForms import error: ' . $e->getMessage() );

				Astra_Sites_Helper::error_response(
					sprintf(
						// translators: %s is exception error message.
						__( 'WPForms import failed: Unexpected error - %s', 'astra-sites' ),
						$e->getMessage()
					)
				);
				return;
			} catch ( \Error $e ) {
				Astra_Sites_Helper::error_response(
					sprintf(
						// translators: %s: Fatal error message.
						__( 'WPForms import failed: Fatal Error: %s', 'astra-sites' ),
						$e->getMessage()
					)
				);
				return;
			}
		}

		/**
		 * Import CartFlows
		 *
		 * @since 2.0.0
		 *
		 * @param  string $url Cartflows JSON file URL.
		 * @return void
		 */
		public function import_cartflows( $url = '' ) {
			Astra_Sites_Importer_Log::add( '---' . PHP_EOL );
			Astra_Sites_Importer_Log::add( 'Starting CartFlows import process' );

			if ( ! defined( 'WP_CLI' ) && wp_doing_ajax() ) {
				// Verify Nonce.
				check_ajax_referer( 'astra-sites', '_ajax_nonce' );

				if ( ! current_user_can( 'edit_posts' ) ) {
					wp_send_json_error( __( "Permission Denied: You don't have permission to import CartFlows flows. Please contact your site administrator.", 'astra-sites' ) );
				}
			}

			// Disable CartFlows import logging.
			add_filter( 'cartflows_enable_log', '__return_false' );

			// Make the flow publish.
			add_filter( 'cartflows_flow_importer_args', array( $this, 'change_flow_status' ) );
			add_action( 'cartflows_flow_imported', array( $this, 'track_flows' ) );
			add_action( 'cartflows_step_imported', array( $this, 'track_flows' ) );
			add_filter( 'cartflows_enable_imported_content_processing', '__return_false' );

			$url = astra_get_site_data( 'astra-site-cartflows-path' );

			try {
				$flows = $this->download_and_validate_import_data( $url );
				if ( is_wp_error( $flows ) ) {
					Astra_Sites_Helper::error_response(
						sprintf(
							// translators: %s is the error message.
							__( 'CartFlows import failed: %s', 'astra-sites' ),
							$flows->get_error_message()
						)
					);
					return;
				}

				if ( empty( $flows ) ) {
					Astra_Sites_Helper::success_response(
						array(
							'message' => __( 'No CartFlows data to import.', 'astra-sites' ),
							'file'    => $url,
						)
					);
					return;
				}

				// Ensure CartFlows plugin is loaded.
				$result = $this->ensure_plugin_loaded(
					'cartflows/cartflows.php',
					'CartFlows',
					function() {
						return class_exists( 'CartFlows_Importer' );
					}
				);

				if ( is_wp_error( $result ) ) {
					Astra_Sites_Helper::error_response( $result->get_error_message() );
					return;
				}

				// Import CartFlows flows.
				$import_result = CartFlows_Importer::get_instance()->import_from_json_data( $flows );
				Astra_Sites_Importer_Log::add( 'CartFlows data imported successfully', 'success' );

				if ( is_wp_error( $import_result ) ) {
					Astra_Sites_Helper::error_response(
						sprintf(
							// translators: Sending cartflows import failed.
							__( 'CartFlows import failed: %s', 'astra-sites' ),
							$import_result->get_error_message()
						)
					);
				}
			} catch ( \Exception $e ) {
				astra_sites_error_log( 'Astra Sites CartFlows Import Exception: ' . $e->getMessage() );
				// translators: %s: Exception error message.
				Astra_Sites_Helper::error_response( sprintf( __( 'CartFlows import failed: Unexpected error - %s', 'astra-sites' ), $e->getMessage() ) );
			} catch ( \Error $e ) {
				astra_sites_error_log( 'Astra Sites CartFlows Import Fatal Error: ' . $e->getMessage() );
				// translators: %s: Fatal error message.
				Astra_Sites_Helper::error_response( sprintf( __( 'CartFlows import failed: Fatal Error - %s', 'astra-sites' ), $e->getMessage() ) );
			}

			Astra_Sites_Helper::success_response(
				// translators: %s is the URL.
				sprintf( __( 'Imported from %s', 'astra-sites' ), $url )
			);
		}

		/**
		 * Import Cart Abandonment Recovery data.
		 *
		 * @since 4.4.27
		 *
		 * @param  string $url JSON file URL.
		 * @return void
		 */
		public function import_cart_abandonment_recovery( $url = '' ) {
			Astra_Sites_Importer_Log::add( '---' . PHP_EOL );
			Astra_Sites_Importer_Log::add( 'Starting Cart Abandonment Recovery import process' );

			try {
				if ( ! defined( 'WP_CLI' ) && wp_doing_ajax() ) {
					// Verify Nonce.
					check_ajax_referer( 'astra-sites', '_ajax_nonce' );

					if ( ! current_user_can( 'edit_posts' ) ) {
						wp_send_json_error(
							__( "Permission denied: You don't have permission to import Cart Abandonment Recovery data. Please contact your site administrator.", 'astra-sites' )
						);
					}
				}

				$url = astra_get_site_data( 'astra-site-cart-abandonment-recovery-path' );

				$data = $this->download_and_validate_import_data( $url );
				if ( is_wp_error( $data ) ) {
					Astra_Sites_Helper::error_response(
						sprintf(
							// translators: %s is the error message.
							__( 'Cart Abandonment Recovery import failed: %s', 'astra-sites' ),
							$data->get_error_message()
						)
					);
					return;
				}

				// Skip import gracefully if no URL (normal condition).
				if ( empty( $data ) ) {
					Astra_Sites_Helper::success_response(
						array(
							'message' => __( 'No Cart Abandonment Recovery data to import.', 'astra-sites' ),
							'file'    => $url,
						)
					);
					return;
				}

				// Ensure Cart Abandonment Recovery plugin is loaded.
				$class_file = WP_PLUGIN_DIR . '/woo-cart-abandonment-recovery/modules/cart-abandonment/classes/class-cartflows-ca-email-template-importer-exporter.php';
				$loaded     = $this->ensure_plugin_loaded(
					'woo-cart-abandonment-recovery/woo-cart-abandonment-recovery.php',
					'Cart Abandonment Recovery',
					function() {
						return class_exists( 'Cartflows_CA_Email_Template_Importer_Exporter' );
					},
					$class_file
				);

				if ( is_wp_error( $loaded ) ) {
					Astra_Sites_Helper::error_response( $loaded->get_error_message() );
					return;
				}

				Cartflows_CA_Email_Template_Importer_Exporter::get_instance()->insert_templates( $data );
				Astra_Sites_Importer_Log::add( 'Cart Abandonment Recovery templates inserted successfully', 'success' );

				Astra_Sites_Helper::success_response(
					// translators: %s is the URL.
					sprintf( __( 'Imported Cart Abandonment Recovery data from %s', 'astra-sites' ), $url )
				);
			} catch ( Exception $e ) {
				// translators: %s: Exception error message.
				Astra_Sites_Helper::error_response( sprintf( __( 'Cart Abandonment Recovery import failed: Unexpected error - %s', 'astra-sites' ), $e->getMessage() ) );
			} catch ( \Error $e ) {
				// translators: %s: Fatal error message.
				Astra_Sites_Helper::error_response( sprintf( __( 'Cart Abandonment Recovery import failed: Fatal Error - %s', 'astra-sites' ), $e->getMessage() ) );
			}
		}

		/**
		 * Import LatePoint
		 *
		 * @since 2.0.0
		 *
		 * @param  string $url LatePoint JSON file URL.
		 * @return void
		 */
		public function import_latepoint( $url = '' ) {
			Astra_Sites_Importer_Log::add( '---' . PHP_EOL );
			Astra_Sites_Importer_Log::add( 'Starting LatePoint import process' );

			if ( ! defined( 'WP_CLI' ) && wp_doing_ajax() ) {
				// Verify Nonce.
				check_ajax_referer( 'astra-sites', '_ajax_nonce' );

				if ( ! current_user_can( 'edit_posts' ) ) {
					wp_send_json_error(
						__( "Permission denied: You don't have permission to import LatePoint data. Please contact your site administrator.", 'astra-sites' )
					);
				}
			}

			$url = astra_get_site_data( 'astra-site-latepoint-path' );

			try {
				$content = $this->download_and_validate_import_data( $url, false );

				if ( is_wp_error( $content ) ) {
					Astra_Sites_Helper::error_response(
						sprintf(
							// translators: %s is the error message.
							__( 'LatePoint import failed: %s', 'astra-sites' ),
							$content->get_error_message()
						)
					);
					return;
				}

				// Ensure LatePoint plugin is loaded and ready.
				$loaded = $this->ensure_plugin_loaded(
					'latepoint/latepoint.php',
					'LatePoint',
					function() {
						return class_exists( 'OsSettingsHelper' );
					}
				);

				if ( is_wp_error( $loaded ) ) {
					Astra_Sites_Helper::error_response( $loaded->get_error_message() );
					return;
				}

				try {
					OsSettingsHelper::import_data( $content );
					Astra_Sites_Importer_Log::add( 'LatePoint data imported successfully', 'success' );
				} catch ( \Exception $e ) {
					Astra_Sites_Helper::error_response(
						// translators: %s is exception error message.
						sprintf( __( 'LatePoint import failed: %s', 'astra-sites' ), $e->getMessage() )
					);
					return;
				}
			} catch ( \Exception $e ) {
				Astra_Sites_Helper::error_response(
					// translators: %s is exception error message.
					sprintf( __( 'LatePoint import failed: Unexpected error - %s', 'astra-sites' ), $e->getMessage() )
				);
			} catch ( \Error $e ) {
				Astra_Sites_Helper::error_response(
					// translators: %s: Fatal error message.
					sprintf( __( 'LatePoint import failed: Fatal Error - %s', 'astra-sites' ), $e->getMessage() )
				);
			}

			Astra_Sites_Helper::success_response(
				// translators: %s is the URL.
				sprintf( __( 'Imported from %s', 'astra-sites' ), $url )
			);
		}

		/**
		 * Verify that all required plugins are installed and activated.
		 *
		 * This is called before import to ensure all plugins are ready.
		 *
		 * @since 4.4.51
		 * @return void
		 */
		public function verify_required_plugins() {
			// Verify Nonce.
			check_ajax_referer( 'astra-sites', '_ajax_nonce' );

			if ( ! current_user_can( 'activate_plugins' ) ) {
				wp_send_json_error(
					array(
						'message' => __( 'Permission denied: You do not have permission to verify plugins.', 'astra-sites' ),
					)
				);
				return;
			}

			// Include plugin.php for is_plugin_active check.
			if ( ! function_exists( 'is_plugin_active' ) ) {
				include_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- JSON data needs to be decoded first.
			$required_plugins_json = isset( $_POST['required_plugins'] ) ? wp_unslash( $_POST['required_plugins'] ) : '';
			$required_plugins      = ! empty( $required_plugins_json ) ? json_decode( $required_plugins_json, true ) : array();

			if ( empty( $required_plugins ) || ! is_array( $required_plugins ) ) {
				// No plugins to verify - that's fine.
				wp_send_json_success(
					array(
						'message' => __( 'No plugins to verify.', 'astra-sites' ),
						'verified' => true,
					)
				);
				return;
			}

			$missing       = array();
			$not_activated = array();

			foreach ( $required_plugins as $plugin ) {
				if ( ! is_array( $plugin ) || empty( $plugin['init'] ) ) {
					continue;
				}

				$plugin_init = sanitize_text_field( $plugin['init'] );

				// Reject path traversal attempts.
				if ( false !== strpos( $plugin_init, '..' ) || false !== strpos( $plugin_init, '\\' ) ) {
					Astra_Sites_Importer_Log::add( 'Plugin verification blocked - invalid path: ' . $plugin_init );
					continue;
				}

				$plugin_file = WP_PLUGIN_DIR . '/' . $plugin_init;

				if ( ! file_exists( $plugin_file ) ) {
					$missing[] = $plugin;
					Astra_Sites_Importer_Log::add( 'Plugin verification failed - missing: ' . $plugin_init );
				} elseif ( ! is_plugin_active( $plugin_init ) ) {
					$not_activated[] = $plugin;
					Astra_Sites_Importer_Log::add( 'Plugin verification failed - not activated: ' . $plugin_init );
				}
			}

			if ( ! empty( $missing ) || ! empty( $not_activated ) ) {
				Astra_Sites_Importer_Log::add( 'Plugin verification failed. Missing: ' . count( $missing ) . ', Not activated: ' . count( $not_activated ) );
				wp_send_json_error(
					array(
						'message'       => __( 'Some required plugins are not ready.', 'astra-sites' ),
						'missing'       => $missing,
						'not_activated' => $not_activated,
						'verified'      => false,
					)
				);
				return;
			}

			Astra_Sites_Importer_Log::add( 'All required plugins verified successfully.' );
			wp_send_json_success(
				array(
					'message'  => __( 'All plugins verified.', 'astra-sites' ),
					'verified' => true,
				)
			);
		}

		/**
		 * Get single demo.
		 *
		 * @since  1.0.0
		 *
		 * @param  (String) $demo_api_uri API URL of a demo.
		 *
		 * @return (Array) $astra_demo_data demo data for the demo.
		 */
		public static function get_single_demo( $demo_api_uri ) {

			if ( is_int( $demo_api_uri ) ) {
				$demo_api_uri = Astra_Sites::get_instance()->get_api_url() . 'astra-sites/' . $demo_api_uri;
			}

			// default values.
			$remote_args = array();
			$defaults    = array(
				'id'                                        => '',
				'astra-site-widgets-data'                   => '',
				'astra-site-customizer-data'                => '',
				'astra-site-options-data'                   => '',
				'astra-post-data-mapping'                   => '',
				'astra-site-wxr-path'                       => '',
				'astra-site-wpforms-path'                   => '',
				'astra-site-cartflows-path'                 => '',
				'astra-site-cart-abandonment-recovery-path' => '',
				'astra-site-latepoint-path'                 => '',
				'astra-site-surecart-settings'              => '',
				'astra-enabled-extensions'                  => '',
				'astra-custom-404'                          => '',
				'required-plugins'                          => '',
				'astra-site-taxonomy-mapping'               => '',
				'license-status'                            => '',
				'site-type'                                 => '',
				'astra-site-url'                            => '',
			);

			$api_args = apply_filters(
				'astra_sites_api_args',
				array(
					'timeout' => 15,
				)
			);

			// Use this for premium demos.
			$request_params = apply_filters(
				'astra_sites_api_params',
				array(
					'purchase_key' => '',
					'site_url'     => '',
				)
			);

			$demo_api_uri = add_query_arg( $request_params, trailingslashit( $demo_api_uri ) );

			// API Call.
			$response = wp_safe_remote_get( $demo_api_uri, $api_args );

			if ( is_wp_error( $response ) || ( isset( $response->status ) && 0 === $response->status ) ) {
				if ( isset( $response->status ) ) {
					$data = json_decode( $response, true );
				} else {
					return new WP_Error( 'api_invalid_response_code', $response->get_error_message() );
				}
			}

			if ( wp_remote_retrieve_response_code( $response ) !== 200 ) {
				return new WP_Error( 'api_invalid_response_code', wp_remote_retrieve_body( $response ) );
			} else {
				$data = json_decode( wp_remote_retrieve_body( $response ), true );
			}

			$data = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( ! isset( $data['code'] ) ) {
				$remote_args['id']                                        = $data['id'];
				$remote_args['astra-site-widgets-data']                   = json_decode( $data['astra-site-widgets-data'] );
				$remote_args['astra-site-customizer-data']                = $data['astra-site-customizer-data'];
				$remote_args['astra-site-options-data']                   = $data['astra-site-options-data'];
				$remote_args['astra-post-data-mapping']                   = $data['astra-post-data-mapping'];
				$remote_args['astra-site-wxr-path']                       = $data['astra-site-wxr-path'];
				$remote_args['astra-site-wpforms-path']                   = $data['astra-site-wpforms-path'];
				$remote_args['astra-site-cartflows-path']                 = isset( $data['astra-site-cartflows-path'] ) ? $data['astra-site-cartflows-path'] : '';
				$remote_args['astra-site-cart-abandonment-recovery-path'] = isset( $data['astra-site-cart-abandonment-recovery-path'] ) ? $data['astra-site-cart-abandonment-recovery-path'] : '';
				$remote_args['astra-site-latepoint-path']                 = isset( $data['astra-site-latepoint-path'] ) ? $data['astra-site-latepoint-path'] : '';
				$remote_args['astra-site-surecart-settings']              = isset( $data['astra-site-surecart-settings'] ) ? $data['astra-site-surecart-settings'] : '';
				$remote_args['astra-enabled-extensions']                  = $data['astra-enabled-extensions'];
				$remote_args['astra-custom-404']                          = $data['astra-custom-404'];
				$remote_args['required-plugins']                          = $data['required-plugins'];
				$remote_args['astra-site-taxonomy-mapping']               = $data['astra-site-taxonomy-mapping'];
				$remote_args['license-status']                            = $data['license-status'];
				$remote_args['site-type']                                 = $data['astra-site-type'];
				$remote_args['astra-site-url']                            = $data['astra-site-url'];
			}

			// Merge remote demo and defaults.
			return wp_parse_args( $remote_args, $defaults );
		}

		/**
		 * Clear PHP opcode cache for a specific file or entire cache.
		 *
		 * This helps prevent issues where PHP opcode cache serves stale bytecode
		 * after a plugin is freshly installed or updated.
		 *
		 * @since 4.4.47
		 *
		 * @param string $file_path Optional. Specific file path to invalidate. Default empty (clears entire cache).
		 * @return bool True if cache was cleared, false otherwise.
		 */
		private function clear_opcode_cache( $file_path = '' ) {
			$cleared = false;

			// Try to reset entire opcode cache if no specific file provided.
			if ( empty( $file_path ) && function_exists( 'opcache_reset' ) ) {
				// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.opcache_opcache_reset, PHPCompatibility.FunctionUse.NewFunctions.opcache_resetFound -- Needed to clear entire opcode cache.
				$cleared = opcache_reset();
			} elseif ( ! empty( $file_path ) && function_exists( 'opcache_invalidate' ) ) {
				// Invalidate specific file from opcode cache.
				// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.opcache_opcache_invalidate, PHPCompatibility.FunctionUse.NewFunctions.opcache_invalidateFound -- Needed to clear specific file from opcode cache.
				$cleared = opcache_invalidate( $file_path, true );
			}

			return $cleared;
		}

		/**
		 * Ensure a plugin is properly loaded and its classes/functions are available.
		 *
		 * This method handles the complete plugin loading process including verification,
		 * cache clearing, and initialization to prevent timing/race condition issues.
		 *
		 * @since 4.4.47
		 *
		 * @param string   $plugin_file        Relative path to plugin file (e.g., 'wpforms-lite/wpforms.php').
		 * @param string   $plugin_name        Human-readable plugin name for error messages.
		 * @param callable $is_loaded_callback Callback that returns true if plugin is loaded, false otherwise.
		 * @param string   $plugin_main_file   Optional. Full path to the main plugin file. If empty, it will be constructed.
		 *
		 * @return bool|WP_Error True if plugin loaded successfully, WP_Error on failure.
		 */
		private function ensure_plugin_loaded( $plugin_file, $plugin_name, $is_loaded_callback, $plugin_main_file = '' ) {
			// Initial verification if plugin is already loaded.
			Astra_Sites_Importer_Log::add( 'Checking if ' . $plugin_name . ' is already loaded' );
			if ( call_user_func( $is_loaded_callback ) ) {
				Astra_Sites_Importer_Log::add( $plugin_name . ' is already loaded and ready' );
				return true;
			}

			// Include plugin.php for is_plugin_active check.
			if ( ! function_exists( 'is_plugin_active' ) ) {
				include_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			// Construct full plugin main file path if not provided.
			if ( empty( $plugin_main_file ) ) {
				$plugin_main_file = WP_PLUGIN_DIR . '/' . $plugin_file;
			}

			// Verify plugin file exists.
			if ( ! file_exists( $plugin_main_file ) ) {
				return new WP_Error(
					'plugin_files_not_found',
					sprintf(
						/* translators: %1$s: Plugin name */
						__( '%1$s import failed: Plugin files not found. Please ensure %1$s is properly installed.', 'astra-sites' ),
						$plugin_name
					)
				);
			}

			// Verify plugin is actually activated.
			if ( wp_doing_ajax() && ! is_plugin_active( $plugin_file ) ) {
				return new WP_Error(
					'plugin_not_activated',
					sprintf(
						/* translators: %1$s: Plugin name */
						__( '%1$s import failed: Plugin is installed but not activated. Please activate %1$s and try again.', 'astra-sites' ),
						$plugin_name
					)
				);
			}

			// Clear opcode cache to prevent stale bytecode.
			Astra_Sites_Importer_Log::add( 'Clearing opcode cache for ' . $plugin_name );
			$this->clear_opcode_cache( $plugin_main_file );

			// Force load the plugin.
			Astra_Sites_Importer_Log::add( 'Manually loading ' . $plugin_name . ' from: ' . $plugin_main_file );
			require_once $plugin_main_file;

			// Trigger WordPress plugin loaded hooks to initialize autoloaders.
			if ( ! did_action( 'plugins_loaded' ) ) {
				do_action( 'plugins_loaded' );
			}

			// Wait a moment for autoloaders to register.
			usleep( 500000 ); // 0.5 second

			// Final verification that plugin is ready.
			if ( ! call_user_func( $is_loaded_callback ) ) {
				return new WP_Error(
					'plugin_not_ready',
					sprintf(
						/* translators: %s: Plugin name */
						__( '%s import failed: Plugin class/functions not available after loading. Please try again or contact support.', 'astra-sites' ),
						$plugin_name
					)
				);
			}

			// Log success for debugging.
			astra_sites_error_log( sprintf( '%s successfully loaded after manual initialization.', $plugin_name ) );

			return true;
		}

		/**
		 * Clear Cache.
		 *
		 * @since  1.0.9
		 */
		public function clear_related_cache() {

			// Clear 'Builder Builder' cache.
			if ( is_callable( 'FLBuilderModel::delete_asset_cache_for_all_posts' ) ) {
				FLBuilderModel::delete_asset_cache_for_all_posts();
				Astra_Sites_Importer_Log::add( 'Cache for Beaver Builder cleared.' );
			}

			// Clear 'Astra Addon' cache.
			if ( is_callable( 'Astra_Minify::refresh_assets' ) ) {
				Astra_Minify::refresh_assets();
				Astra_Sites_Importer_Log::add( 'Cache for Astra Addon cleared.' );
			}

			Astra_Sites_Utils::third_party_cache_plugins_clear_cache();

			$this->update_latest_checksums();

			// Flush permalinks.
			flush_rewrite_rules(); //phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.flush_rewrite_rules_flush_rewrite_rules -- This function is called only after import is completed
		}

		/**
		 * Update Latest Checksums
		 *
		 * Store latest checksum after batch complete.
		 *
		 * @since 2.0.0
		 * @return void
		 */
		public function update_latest_checksums() {
			$latest_checksums = get_site_option( 'astra-sites-last-export-checksums-latest', '' );
			update_site_option( 'astra-sites-last-export-checksums', $latest_checksums );
		}
	}

	/**
	 * Kicking this off by calling 'get_instance()' method
	 */
	Astra_Sites_Importer::get_instance();
}
