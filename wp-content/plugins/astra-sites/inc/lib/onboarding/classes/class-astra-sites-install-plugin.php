<?php
/**
 * Astra Sites Install Plugin Class
 *
 * This file contains the logic for handling plugin installations during site import via REST API.
 *
 * @since 4.4.31
 * @package Astra Sites
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Astra_Sites_Install_Plugin' ) ) :

	/**
	 * Class Astra_Sites_Install_Plugin
	 *
	 * Main class for handling plugin installations during site import via AJAX.
	 */
	class Astra_Sites_Install_Plugin {

		/**
		 * Instance
		 *
		 * @access private
		 * @var object Class object.
		 * @since 4.4.31
		 */
		private static $instance;

		/**
		 * AJAX action name
		 *
		 * @var string
		 */
		private $ajax_action = 'astra_sites_install_plugin';

		/**
		 * Initiator
		 *
		 * @since 4.4.31
		 * @return object initialized object of class.
		 */
		public static function get_instance() {
			if (self::$instance === null) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Constructor
		 */
		public function __construct() {
			// Register AJAX handler only for logged-in users (plugin installation requires authentication).
			add_action( 'wp_ajax_' . $this->ajax_action, array( $this, 'handle_ajax_install_plugin' ) );
		}

		/**
		 * Handle AJAX request for plugin installation
		 *
		 * @return void
		 */
		public function handle_ajax_install_plugin() {
			// Set proper content type for JSON response.
			header( 'Content-Type: application/json' );

			try {
				// Verify nonce for security.
				if ( ! $this->verify_ajax_nonce() ) {
					wp_send_json_error(
						array(
							'message' => __( 'Nonce verification failed. Your session may have expired — please refresh the page and try again.', 'astra-sites' ),
							'code'    => 'invalid_nonce',
						),
						403
					);
				}

				// Check user permissions.
				$permission_check = $this->check_install_plugin_permissions();
				if ( is_wp_error( $permission_check ) ) {
					wp_send_json_error(
						array(
							'message' => $permission_check->get_error_message(),
							'code'    => $permission_check->get_error_code(),
						),
						403
					);
				}

				// Get and validate plugin data.
				$plugin_data = $this->get_and_validate_plugin_data();
				if ( is_wp_error( $plugin_data ) ) {
					wp_send_json_error(
						array(
							'message' => $plugin_data->get_error_message(),
							'code'    => $plugin_data->get_error_code(),
						),
						400
					);
				}

				// Extract plugin information.
				$plugin_slug = $plugin_data['slug'];
				$plugin_name = $plugin_data['name'];

				// Include necessary WordPress files.
				$this->include_required_files();

				// Check if plugin is already installed.
				$installed_plugins = get_plugins();
				$plugin_file       = $this->get_plugin_file( $plugin_slug );
				
				if ( $plugin_file && isset( $installed_plugins[ $plugin_file ] ) ) {
					// Plugin already installed.
					wp_send_json_success(
						array(
							// translators: %s is the plugin name.
							'message' => sprintf( __( '%s plugin is already installed.', 'astra-sites' ), $plugin_name ),
							'status'  => 'already_installed',
							'data'    => array(
								'plugin' => array(
									'slug' => $plugin_slug,
									'name' => $plugin_name,
									'file' => $plugin_file,
								),
							),
						)
					);
				}

				// Install plugin.
				$install_result = $this->perform_plugin_installation( $plugin_slug, $plugin_name );
				
				if ( is_wp_error( $install_result ) ) {
					wp_send_json_error(
						array(
							'message' => $install_result->get_error_message(),
							'code'    => $install_result->get_error_code(),
							'data'    => $install_result->get_error_data(),
						),
						500
					);
				}

				// Get plugin file after installation.
				$plugin_file = $this->get_plugin_file( $plugin_slug );
				
				if ( ! $plugin_file ) {
					wp_send_json_error(
						array(
							'message' => sprintf(
								// translators: %s: Plugin name.
								__( 'Plugin %s was installed but the main plugin file could not be found. The installation may be incomplete or the plugin structure is invalid.', 'astra-sites' ),
								$plugin_name
							),
							'code'    => 'plugin_file_not_found_after_install',
						),
						500
					);
				}

				// Success response.
				wp_send_json_success(
					array(
						'message' => sprintf( __( '%s plugin installed successfully.', 'astra-sites' ), $plugin_name ),
						'status'  => 'installed',
						'data'    => array(
							'plugin' => array(
								'slug' => $plugin_slug,
								'name' => $plugin_name,
								'file' => $plugin_file,
							),
						),
					)
				);

			} catch ( Exception $e ) {
				// Handle any unexpected exceptions.
				astra_sites_error_log( 'Astra Sites Plugin Installation Error: ' . $e->getMessage() );
				
				wp_send_json_error(
					array(
						'message' => sprintf(
							// translators: %s: Error message.
							__( 'An unexpected error occurred during plugin installation: %s', 'astra-sites' ),
							$e->getMessage()
						),
						'code'    => 'unexpected_error',
					),
					500
				);
			}
		}

		/**
		 * Verify AJAX nonce for security
		 *
		 * @return bool True if nonce is valid, false otherwise.
		 */
		private function verify_ajax_nonce() {
			$nonce = isset( $_POST['_ajax_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_ajax_nonce'] ) ) : '';
			
			if ( empty( $nonce ) ) {
				return false;
			}

			// Only accept nonce actions specific to this plugin.
			$nonce_actions = array(
				'astra-sites',      // Primary nonce action used in Astra Sites AJAX calls.
				'astra_sites_ajax', // Alternative nonce action.
			);

			foreach ( $nonce_actions as $action ) {
				if ( wp_verify_nonce( $nonce, $action ) ) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Check permissions for plugin installation
		 *
		 * @return bool|WP_Error True if user has permission, otherwise WP_Error
		 */
		private function check_install_plugin_permissions() {
			// Check if user has permission to install plugins
			if ( ! current_user_can( 'install_plugins' ) ) {
				return new WP_Error(
					'insufficient_permissions',
					__( 'Permission denied: You do not have the required capability to install plugins. Please contact your site administrator.', 'astra-sites' )
				);
			}

			// Additional security check - ensure user is logged in
			if ( ! is_user_logged_in() ) {
				return new WP_Error(
					'user_not_logged_in',
					__( 'Login required: You must be logged in to install plugins.', 'astra-sites' )
				);
			}

			return true;
		}

		/**
		 * Get and validate plugin data from AJAX request
		 *
		 * @return array<string, mixed>|WP_Error Plugin data array or WP_Error on failure
		 */
		private function get_and_validate_plugin_data() {
			// Get plugin slug
			$plugin_slug = isset( $_POST['slug'] ) ? sanitize_text_field( wp_unslash( $_POST['slug'] ) ) : '';
			if ( empty( $plugin_slug ) ) {
				return new WP_Error(
					'missing_plugin_slug',
					__( 'Plugin installation failed: Missing plugin slug.', 'astra-sites' )
				);
			}

			// Validate plugin slug format
			if ( ! $this->validate_plugin_slug( $plugin_slug ) ) {
				return new WP_Error(
					'invalid_plugin_slug',
					__( 'Plugin installation failed: Invalid plugin slug format. Only alphanumeric, dashes, and underscores are allowed.', 'astra-sites' )
				);
			}

			// Get plugin name (optional, defaults to slug)
			$plugin_name = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : $plugin_slug;

			// Get plugin init (optional)
			$plugin_init = isset( $_POST['init'] ) ? sanitize_text_field( wp_unslash( $_POST['init'] ) ) : '';

			return array(
				'slug' => $plugin_slug,
				'name' => $plugin_name,
				'init' => $plugin_init,
			);
		}

		/**
		 * Validate plugin slug format
		 *
		 * @param string $slug Plugin slug to validate
		 * @return mixed 1 if valid, false if failure
		 */
		private function validate_plugin_slug( $slug ) {
			// Basic slug validation (alphanumeric, hyphens, underscores)
			return preg_match( '/^[a-zA-Z0-9_-]+$/', $slug );
		}

		/**
		 * Include required WordPress files
		 *
		 * @return void
		 */
		private function include_required_files() {
			if ( ! function_exists( 'get_plugins' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			if ( ! function_exists( 'request_filesystem_credentials' ) ) {
				require_once ABSPATH . 'wp-admin/includes/file.php';
			}
			if ( ! class_exists( 'Plugin_Upgrader' ) ) {
				require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
			}
		}

		/**
		 * Get plugin file path from slug
		 *
		 * @param string $plugin_slug Plugin slug.
		 * @return string|false Plugin file path or false if not found.
		 */
		private function get_plugin_file( $plugin_slug ) {
			// Try common plugin file patterns
			$possible_files = array(
				$plugin_slug . '/' . $plugin_slug . '.php',
				$plugin_slug . '/index.php',
				$plugin_slug . '/plugin.php',
			);

			$installed_plugins = get_plugins();
			foreach ( $possible_files as $file ) {
				if ( isset( $installed_plugins[ $file ] ) ) {
					return $file;
				}
			}

			// Search through all installed plugins for this slug
			foreach ( $installed_plugins as $file => $plugin_data ) {
				if ( strpos( $file, $plugin_slug . '/' ) === 0 ) {
					return $file;
				}
			}

			return false;
		}

		/**
		 * Perform plugin installation using WordPress.org repository
		 *
		 * @param string $plugin_slug Plugin slug.
		 * @param string $plugin_name Plugin name.
		 * @return true|WP_Error True on success, WP_Error on failure.
		 */
		private function perform_plugin_installation( $plugin_slug, $plugin_name ) {
			try {
				// Validate input parameters
				if ( empty( $plugin_slug ) || empty( $plugin_name ) ) {
					throw new Exception(
						__( 'Plugin installation failed: Missing required slug or name.', 'astra-sites' ),
						0 // code must be int
					);
				}

				astra_sites_error_log( sprintf( 'Starting installation of plugin %s (%s)', $plugin_name, $plugin_slug ) );

				// Step 1: Get plugin API information
				$api = $this->get_plugin_api_info( $plugin_slug, $plugin_name );
				if ( is_wp_error( $api ) ) {
					return $api;
				}

				// Step 2: Perform the actual installation
				$result = $this->execute_plugin_installation( $api, $plugin_slug, $plugin_name );
				if ( is_wp_error( $result ) ) {
					return $result;
				}

				astra_sites_error_log( sprintf( 'Successfully installed plugin %s (%s)', $plugin_name, $plugin_slug ) );
				return true;

			} catch ( Exception $e ) {
				// Catch any unexpected exceptions
				$error_message = sprintf(
					// translators: 1: Plugin name, 2: Exception message.
					__( 'Plugin installation error for "%1$s": %2$s', 'astra-sites' ), 
					$plugin_name, 
					$e->getMessage() 
				);
				
				astra_sites_error_log( sprintf( 'Unexpected exception for plugin %s: %s', $plugin_slug, $e->getMessage() ) );
				
				return new WP_Error(
					'unexpected_error',
					$error_message,
					array( 
						'plugin_slug' => $plugin_slug,
						'exception_message' => $e->getMessage(),
						'exception_code' => $e->getCode(),
						'troubleshooting_tips' => array(
							__( 'Please try the installation again.', 'astra-sites' ),
							__( 'If the problem persists, try installing the plugin manually from the WordPress admin.', 'astra-sites' ),
							__( 'Contact your hosting provider if you continue to experience issues.', 'astra-sites' ),
						),
					)
				);
			}
		}

		/**
		 * Get plugin information from WordPress.org API
		 *
		 * @param string $plugin_slug Plugin slug.
		 * @param string $plugin_name Plugin name.
		 * @return object|WP_Error Plugin API object on success, WP_Error on failure.
		 */
		private function get_plugin_api_info( $plugin_slug, $plugin_name ) {
			// Include required WordPress files with error handling
			try {
				if ( ! function_exists( 'plugins_api' ) ) {
					require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
				}
				if ( ! class_exists( 'WP_Ajax_Upgrader_Skin' ) ) {
					require_once ABSPATH . 'wp-admin/includes/class-wp-ajax-upgrader-skin.php';
				}
			} catch ( Exception $e ) {
				astra_sites_error_log( sprintf( 'Failed to load required WordPress files for %s: %s', $plugin_slug, $e->getMessage() ) );
				return new WP_Error(
					'files_load_failed',
					// translators: %s is the exception message.
					sprintf( __( 'Installation failed: Required WordPress files could not be loaded. Error: %s', 'astra-sites' ), $e->getMessage() ),
					array( 'plugin_slug' => $plugin_slug )
				);
			}

			// Get plugin information from WordPress.org API with timeout and retry logic
			$api_attempts = 0;
			$max_api_attempts = 2;
			$api = null;

			while ( $api_attempts <= $max_api_attempts && ( is_null( $api ) || is_wp_error( $api ) ) ) {
				$api_attempts++;
				
				try {
					astra_sites_error_log( sprintf( 'API attempt %d for plugin %s', $api_attempts, $plugin_slug ) );
					
					$api = plugins_api(
						'plugin_information',
						array(
							'slug'   => $plugin_slug,
							'fields' => array(
								'short_description' => false,
								'sections'          => false,
								'requires'          => false,
								'rating'            => false,
								'ratings'           => false,
								'downloaded'        => false,
								'last_updated'      => false,
								'added'             => false,
								'tags'              => false,
								'compatibility'     => false,
								'homepage'          => false,
								'donate_link'       => false,
							),
						)
					);

					// Break if successful
					if ( ! is_wp_error( $api ) && ! empty( $api->download_link ) ) {
						break;
					}

					// Wait before retry (except on last attempt)
					if ( $api_attempts < $max_api_attempts ) {
						sleep( 1 );
					}

				} catch ( Exception $e ) {
					astra_sites_error_log( sprintf( 'API exception on attempt %d for %s: %s', $api_attempts, $plugin_slug, $e->getMessage() ) );
					if ( $api_attempts >= $max_api_attempts ) {
						return new WP_Error(
							'api_exception',
							sprintf(
								// translators: 1: Plugin name, 2: Max attempts, 3: Exception message.
								__( 'Installation failed: Plugin API request for %1$s failed after %2$d attempts. Error: %3$s', 'astra-sites' ),
								$plugin_name,
								$max_api_attempts,
								$e->getMessage()
							),
							array( 'plugin_slug' => $plugin_slug )
						);
					}
				}
			}

			if ( ! $api ) {
				astra_sites_error_log( sprintf( 'API failed to return data for plugin %s', $plugin_slug ) );
				return new WP_Error(
					'plugin_api_failed',
					sprintf(
						// translators: %s is the plugin name.
						__( 'Installation failed: Could not retrieve plugin details for %s from WordPress.org. Please try again.', 'astra-sites' ),
						$plugin_name
					),
				);
			}

			// Handle API errors
			if ( is_wp_error( $api ) && $api instanceof WP_Error ) {
				$api_error_message = $api->get_error_message();
				$api_error_code = $api->get_error_code();
				
				astra_sites_error_log( sprintf( 'API error for plugin %s: %s (%s)', $plugin_slug, $api_error_message, $api_error_code ) );
				
				// Provide specific error messages based on API error codes
				switch ( $api_error_code ) {
					case 'plugin_not_found':
						$user_message = sprintf( 
							__( 'Installation failed: Plugin %s was not found in the WordPress.org repository. Please verify the plugin name and try again.', 'astra-sites' ), 
							$plugin_name 
						);
						break;
					case 'plugins_api_failed':
					default:
						$user_message = sprintf( 
							__( 'Installation failed: Could not retrieve plugin information for %1$s from WordPress.org. Error: %2$s', 'astra-sites' ), 
							$plugin_name, 
							$api_error_message 
						);
						break;
				}

				return new WP_Error(
					'plugin_api_failed',
					$user_message,
					array( 
						'plugin_slug' => $plugin_slug,
						'api_error' => $api_error_message,
						'api_error_code' => $api_error_code,
						'attempts_made' => $api_attempts,
					)
				);
			}

			// Validate API response
			if ( ! is_object( $api ) ) {
				astra_sites_error_log( sprintf( 'Invalid API response for plugin %s', $plugin_slug ) );
				return new WP_Error(
					'invalid_api_response',
					sprintf(
						// translators: %s is the plugin name.
						__( 'Installation failed: Received an invalid response from WordPress.org for %s. Please try again.', 'astra-sites' ),
						$plugin_name
					),
					array( 'plugin_slug' => $plugin_slug )
				);
			}

			// Check for download URL
			if ( empty( $api->download_link ) ) {
				astra_sites_error_log( sprintf( 'No download URL found for plugin %s', $plugin_slug ) );
				return new WP_Error(
					'no_download_url',
					sprintf(
						// translators: %s is the plugin name.
						__( 'Installation failed: Download link not available for %s. The plugin may be premium or removed from WordPress.org.', 'astra-sites' ),
						$plugin_name
					),
					array(
						'plugin_slug'  => $plugin_slug,
						'api_response' => $api,
					)
				);
			}

			return $api;
		}

		/**
		 * Execute the actual plugin installation
		 *
		 * @param object $api Plugin API object.
		 * @param string $plugin_slug Plugin slug.
		 * @param string $plugin_name Plugin name.
		 * @return true|WP_Error True on success, WP_Error on failure.
		 */
		private function execute_plugin_installation( $api, $plugin_slug, $plugin_name ) {
			// Validate WordPress and PHP requirements
			try {
				$this->validate_plugin_requirements( $api, $plugin_name );
			} catch ( Exception $e ) {
				astra_sites_error_log( sprintf( 'Requirements not met for plugin %s: %s', $plugin_slug, $e->getMessage() ) );
				return new WP_Error(
					'requirements_not_met',
					$e->getMessage(),
					array( 
						'plugin_slug' => $plugin_slug,
						'requirements' => array(
							'requires' => isset( $api->requires ) ? $api->requires : '',
							'requires_php' => isset( $api->requires_php ) ? $api->requires_php : '',
						),
					)
				);
			}

			// Check available disk space
			try {
				$this->check_disk_space( $plugin_name );
			} catch ( Exception $e ) {
				astra_sites_error_log( sprintf( 'Insufficient disk space for plugin %s: %s', $plugin_slug, $e->getMessage() ) );
				return new WP_Error(
					'insufficient_disk_space',
					$e->getMessage(),
					array( 'plugin_slug' => $plugin_slug )
				);
			}

			// Set up the Plugin_Upgrader with enhanced error handling
			try {
				$upgrader_skin = new WP_Ajax_Upgrader_Skin();
				$upgrader = new Plugin_Upgrader( $upgrader_skin );
			} catch ( Exception $e ) {
				astra_sites_error_log( sprintf( 'Failed to initialize upgrader for plugin %s: %s', $plugin_slug, $e->getMessage() ) );
				return new WP_Error(
					'upgrader_init_failed',
					sprintf(
						// translators: 1: Plugin name, 2: Exception message.
						__( 'Installation failed: Failed to initialize plugin installer for %1$s: %2$s', 'astra-sites' ),
						$plugin_name, $e->getMessage()
					),
					array( 'plugin_slug' => $plugin_slug )
				);
			}

			// Perform the actual installation with detailed error handling
			// @phpstan-ignore-next-line
			astra_sites_error_log( sprintf( 'Starting plugin installation for %s from %s', $plugin_slug, $api->download_link ) );
			
			$result = null;
			try {
				// @phpstan-ignore-next-line
				$result = $upgrader->install( $api->download_link );
			} catch ( Exception $e ) {
				astra_sites_error_log( sprintf( 'Installation exception for plugin %s: %s', $plugin_slug, $e->getMessage() ) );
				return new WP_Error(
					'installation_exception',
					sprintf(
						// translators: 1: Plugin name, 2: Exception message.
						__( 'Installation failed: Plugin %1$s failed to installed. Error: %2$s', 'astra-sites' ),
						$plugin_name,
						$e->getMessage()
					),
					array(
						'plugin_slug' => $plugin_slug,
						'exception_message' => $e->getMessage(),
					)
				);
			}

			// Handle installation result errors
			if ( is_wp_error( $result ) ) {
				$error_message = $result->get_error_message();
				$error_code = $result->get_error_code();
				$error_data = $result->get_error_data();
				
				astra_sites_error_log( sprintf( 'Installation error for plugin %s: %s (%s)', $plugin_slug, $error_message, $error_code ) );
				
				// Enhanced error messages based on common installation issues
				$enhanced_message = $this->get_enhanced_error_message( $error_code, $error_message, $plugin_name );

				return new WP_Error(
					'installation_failed',
					$enhanced_message,
					array( 
						'plugin_slug' => $plugin_slug,
						'original_error' => $error_message,
						'error_code' => $error_code,
						'error_data' => $error_data,
						'troubleshooting_tips' => $this->get_troubleshooting_tips( $error_code ),
					)
				);
			}

			// Check if installation was unsuccessful
			if ( ! $result ) {
				astra_sites_error_log( sprintf( 'Installation returned false for plugin %s', $plugin_slug ) );
				return new WP_Error(
					'installation_failed',
					sprintf(
						// translators: %s is the plugin name.
						__( 'Installation failed: Plugin %s installation failed: An unknown error occurred. Please try again or install manually.', 'astra-sites' ),
						$plugin_name
					),
					array( 
						'plugin_slug' => $plugin_slug,
						'troubleshooting_tips' => array(
							__( 'Try refreshing the page and attempting the installation again.', 'astra-sites' ),
							__( 'Check if you have sufficient permissions to install plugins.', 'astra-sites' ),
							__( 'Verify that your WordPress installation has write permissions to the plugins directory.', 'astra-sites' ),
						),
					)
				);
			}

			// Verify installation by checking if plugin files exist
			try {
				$this->verify_plugin_installation( $plugin_slug, $plugin_name );
			} catch ( Exception $e ) {
				astra_sites_error_log( sprintf( 'Installation verification failed for plugin %s: %s', $plugin_slug, $e->getMessage() ) );
				return new WP_Error(
					'installation_verification_failed',
					$e->getMessage(),
					array( 'plugin_slug' => $plugin_slug )
				);
			}

			return true;
		}

		/**
		 * Validate plugin requirements against current WordPress and PHP versions
		 *
		 * @param object $api Plugin API response object.
		 * @param string $plugin_name Plugin name.
		 * @throws Exception If requirements are not met.
		 * @return void
		 */
		private function validate_plugin_requirements( $api, $plugin_name ) {
			global $wp_version;

			// Check WordPress version requirement
			if ( ! empty( $api->requires ) ) {
				if ( version_compare( $wp_version, $api->requires, '<' ) ) {
					throw new Exception(
						sprintf(
							// translators: 1: Plugin name, 2: Required WordPress version, 3: Current WordPress version.
							__( 'Installation failed: Plugin %1$s requires WordPress version %2$s or higher. You are currently running version %3$s. Please update WordPress first.', 'astra-sites' ),
							$plugin_name,
							$api->requires,
							$wp_version
						)
					);
				}
			}

			// Check PHP version requirement
			if ( ! empty( $api->requires_php ) ) {
				if ( version_compare( PHP_VERSION, $api->requires_php, '<' ) ) {
					throw new Exception(
						sprintf(
							// translators: 1: Plugin name, 2: Required PHP version, 3: Current PHP version.
							__( 'Installation failed: Plugin %1$s requires PHP version %2$s or higher. You are currently running version %3$s. Please contact your hosting provider to upgrade PHP.', 'astra-sites' ),
							$plugin_name,
							$api->requires_php,
							PHP_VERSION
						)
					);
				}
			}
		}

		/**
		 * Check available disk space before installation
		 *
		 * @param string $plugin_name Plugin name.
		 * @throws Exception If insufficient disk space.
		 * @return void
		 */
		private function check_disk_space( $plugin_name ) {
			$wp_content_dir = WP_CONTENT_DIR;
			
			// Check if we can get disk space information
			if ( function_exists( 'disk_free_space' ) && is_dir( $wp_content_dir ) ) {
				$free_bytes = disk_free_space( $wp_content_dir );
				$required_bytes = 50 * 1024 * 1024; // 50MB minimum requirement
				$required_mb    = 50; // Keep this separately for message clarity.
				
				if ( $free_bytes !== false && $free_bytes < $required_bytes ) {
					throw new Exception(
						sprintf(
							__( 'Installation failed: Insufficient disk space to install %1$s. At least %2$dMB of free space is required. Please free up some disk space and try again.', 'astra-sites' ),
							$plugin_name,
							$required_mb
						)
					);
				}
			}
		}

		/**
		 * Verify plugin installation by checking if plugin files exist
		 *
		 * @param string $plugin_slug Plugin slug.
		 * @param string $plugin_name Plugin name.
		 * @throws Exception If plugin verification fails.
		 * @return void
		 */
		private function verify_plugin_installation( $plugin_slug, $plugin_name ) {
			// Clear plugin cache to ensure fresh data
			wp_clean_plugins_cache();
			
			// Check if plugin directory exists
			$plugin_dir = WP_PLUGIN_DIR . '/' . $plugin_slug;
			if ( ! is_dir( $plugin_dir ) ) {
				throw new Exception(
					sprintf(
						// translators: %s: Plugin name.
						__( 'Plugin %s installation verification failed: Plugin directory not found after installation. The installation may have been incomplete.', 'astra-sites' ),
						$plugin_name
					)
				);
			}

			// Try to find the main plugin file
			$plugin_file = $this->get_plugin_file( $plugin_slug );
			if ( ! $plugin_file ) {
				throw new Exception(
					sprintf(
						// translators: %s: Plugin name.
						__( 'Plugin %s installation verification failed: Could not locate main plugin file after installation.', 'astra-sites' ),
						$plugin_name
					)
				);
			}

			// Check if the plugin file is readable
			$full_plugin_path = WP_PLUGIN_DIR . '/' . $plugin_file;
			if ( ! is_readable( $full_plugin_path ) ) {
				throw new Exception(
					sprintf(
						// translators: %s: Plugin name.
						__( 'Plugin %s installation verification failed: Plugin file exists but is not readable. Please check file permissions.', 'astra-sites' ),
						$plugin_name
					)
				);
			}
		}

		/**
		 * Get enhanced error message based on error code
		 *
		 * @param string|int $error_code Error code.
		 * @param string $error_message Original error message.
		 * @param string $plugin_name Plugin name.
		 * @return string Enhanced error message.
		 */
		private function get_enhanced_error_message( $error_code, $error_message, $plugin_name ) {
			$error_code = (string) $error_code;

			switch ( $error_code ) {
				case 'folder_exists':
					return sprintf( 
						// translators: %s: Plugin name.
						__( 'Plugin %s installation failed: Plugin folder already exists. This usually means the plugin is already installed. Try refreshing the plugins page to see if it appears.', 'astra-sites' ), 
						$plugin_name 
					);

				case 'no_credentials':
				case 'request_filesystem_credentials':
					return sprintf( 
						// translators: %s: Plugin name.
						__( 'Plugin %s installation failed: WordPress does not have permission to write files. Please check that your web server has write permissions to the plugins directory (/wp-content/plugins/), or contact your hosting provider for assistance.', 'astra-sites' ), 
						$plugin_name 
					);

				case 'fs_unavailable':
				case 'fs_error':
					return sprintf( 
						// translators: %s: Plugin name.
						__( 'Plugin %s installation failed: Filesystem is not accessible. This is usually a server configuration issue. Please contact your hosting provider to resolve filesystem access problems.', 'astra-sites' ), 
						$plugin_name 
					);

				case 'download_failed':
				case 'http_request_failed':
					return sprintf( 
						// translators: %s: Plugin name.
						__( 'Plugin %s installation failed: Could not download plugin file from WordPress.org. Please check your internet connection and try again. If the problem persists, your server may be blocking outbound connections.', 'astra-sites' ), 
						$plugin_name 
					);

				case 'incompatible_archive':
				case 'empty_archive':
					return sprintf( 
						// translators: %s: Plugin name.
						__( 'Plugin %s installation failed: The downloaded plugin file appears to be corrupted or invalid. Please try the installation again.', 'astra-sites' ), 
						$plugin_name 
					);

				case 'mkdir_failed':
					return sprintf( 
						// translators: %s: Plugin name.
						__( 'Plugin %s installation failed: Could not create plugin directory. Please check that your web server has write permissions to the plugins directory.', 'astra-sites' ), 
						$plugin_name 
					);

				case 'copy_failed':
					return sprintf( 
						// translators: %s: Plugin name.
						__( 'Plugin %s installation failed: Could not copy plugin files. This may be due to insufficient disk space or file permission issues.', 'astra-sites' ), 
						$plugin_name 
					);

				default:
					// translators: 1: Plugin name, 2: Original error message.
					return sprintf(
						__( 'Plugin %1$s installation failed: %2$s. Please try again or install the plugin manually from the WordPress admin area.', 'astra-sites' ),
						$plugin_name,
						$error_message
					);
			}
		}

		/**
		 * Get troubleshooting tips based on error code
		 *
		 * @param string|int $error_code Error code.
		 * @return array<int, string> Array of troubleshooting tips.
		 */
		private function get_troubleshooting_tips( $error_code ) {
			$common_tips = array(
				__( 'Try refreshing the page and attempting the installation again.', 'astra-sites' ),
				__( 'If the problem persists, try installing the plugin manually from WordPress admin > Plugins > Add New.', 'astra-sites' ),
			);

			switch ( $error_code ) {
				case 'folder_exists':
					return array_merge( array(
						__( 'Check if the plugin is already installed by going to Plugins > Installed Plugins.', 'astra-sites' ),
						__( 'If the plugin appears but is not working, try deactivating and reactivating it.', 'astra-sites' ),
					), $common_tips );

				case 'no_credentials':
				case 'request_filesystem_credentials':
				case 'fs_unavailable':
				case 'fs_error':
				case 'mkdir_failed':
				case 'copy_failed':
					return array_merge( array(
						__( 'Contact your hosting provider to check file permissions for the /wp-content/plugins/ directory.', 'astra-sites' ),
						__( 'Ensure your web server has write permissions to the WordPress installation directory.', 'astra-sites' ),
						__( 'Check if there is sufficient disk space available on your server.', 'astra-sites' ),
					), $common_tips );

				case 'download_failed':
				case 'http_request_failed':
					return array_merge( array(
						__( 'Check your internet connection and try again.', 'astra-sites' ),
						__( 'Contact your hosting provider if your server is blocking outbound connections to WordPress.org.', 'astra-sites' ),
						__( 'Try installing the plugin at a different time when server load might be lower.', 'astra-sites' ),
					), $common_tips );

				case 'incompatible_archive':
				case 'empty_archive':
					return array_merge( array(
						__( 'The plugin download may have been corrupted. Try the installation again.', 'astra-sites' ),
						__( 'Clear your browser cache and try again.', 'astra-sites' ),
					), $common_tips );

				default:
					return array_merge( array(
						__( 'Check the WordPress error logs for more detailed information about the failure.', 'astra-sites' ),
						__( 'Contact your hosting provider if you continue to experience issues.', 'astra-sites' ),
					), $common_tips );
			}
		}
	}

endif;

// Initialize the class
Astra_Sites_Install_Plugin::get_instance();
