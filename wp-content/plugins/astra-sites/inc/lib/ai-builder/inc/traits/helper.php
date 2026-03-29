<?php
/**
 * Trait.
 *
 * @package {{package}}
 * @since 0.0.1
 */

namespace AiBuilder\Inc\Traits;

use AiBuilder\Inc\Classes\Ai_Builder_Importer_Log;
use AiBuilder\Inc\Classes\Importer\Ai_Builder_Error_Handler;
use STImporter\Importer\ST_Importer;
use STImporter\Importer\ST_Importer_File_System;
use STImporter\Resetter\ST_Resetter;

/**
 * Trait Instance.
 */
class Helper {
	use Instance;

	/**
	 * Get an option from the database.
	 *
	 * @param string $key              The option key.
	 * @param mixed  $default          The option default value if option is not available.
	 * @param bool   $network_override Whether to allow the network admin setting to be overridden on subsites.
	 * @since 1.0.0
	 * @return mixed  The option value.
	 */
	public static function get_admin_settings_option( $key, $default = false, $network_override = false ) {
		// Get the site-wide option if we're in the network admin.
		return $network_override && is_multisite() ? get_site_option( $key, $default ) : get_option( $key, $default );
	}

	/**
	 * Delete an option from the database for.
	 *
	 * @param string $key              The option key.
	 * @param bool   $network_override Whether to allow the network admin setting to be overridden on subsites.
	 * @since 1.0.0
	 * @return void
	 */
	public static function delete_admin_settings_option( $key, $network_override = false ) {
		// Delete the site-wide option if we're in the network admin.
		if ( $network_override && is_multisite() ) {
			delete_site_option( $key );
		} else {
			delete_option( $key );
		}
	}

	/**
	 * Get image placeholder array.
	 *
	 * @since 4.0.9
	 * @return array<int, array<string, string>>
	 */
	public static function get_image_placeholders() {

		return array(
			array(
				'auther_name'   => 'Placeholder',
				'auther_url'    => '#',
				'id'            => 'placeholder-landscape',
				'orientation'   => 'landscape',
				'optimized_url' => 'https://websitedemos.net/wp-content/uploads/2024/02/placeholder-landscape.png',
				'url'           => 'https://websitedemos.net/wp-content/uploads/2024/02/placeholder-landscape.png',
				'engine'        => 'placeholder',
				'engine_url'    => '#',
			),
			array(
				'auther_name'   => 'Placeholder',
				'auther_url'    => '#',
				'id'            => 'placeholder-portrait',
				'orientation'   => 'portrait',
				'optimized_url' => 'https://websitedemos.net/wp-content/uploads/2024/02/placeholder-portrait.png',
				'url'           => 'https://websitedemos.net/wp-content/uploads/2024/02/placeholder-portrait.png',
				'engine'        => 'placeholder',
				'engine_url'    => '#',
			),
		);
	}

	/**
	 * Get Saved Token.
	 *
	 * @since 4.0.0
	 * @return string
	 */
	public static function get_token() {
		$token_details = get_option(
			'zip_ai_settings',
			array(
				'auth_token' => '',
				'zip_token'  => '',
				'email'      => '',
			)
		);
		return is_array( $token_details ) && isset( $token_details['zip_token'] ) ? self::decrypt( $token_details['zip_token'] ) : '';
	}

	/**
	 * Decrypt data using base64.
	 *
	 * @param string $input The input string which needs to be decrypted.
	 * @since 4.0.0
	 * @return string The decrypted string.
	 */
	public static function decrypt( $input ) {
		// If the input is empty or not a string, then abandon ship.
		if ( empty( $input ) || ! is_string( $input ) ) {
			return '';
		}

		// Decrypt the input and return it.
		$base_64 = $input . str_repeat( '=', strlen( $input ) % 4 );
		return base64_decode( $base_64 ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
	}

	/**
	 * Get installed PHP version.
	 *
	 * @return string PHP version.
	 * @since 3.0.16
	 */
	public static function get_php_version() {
		if ( defined( 'PHP_MAJOR_VERSION' ) && defined( 'PHP_MINOR_VERSION' ) && defined( 'PHP_RELEASE_VERSION' ) ) { // phpcs:ignore
			return PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION . '.' . PHP_RELEASE_VERSION;
		}

		return phpversion();
	}

	/**
	 * Check whether WooCommerce plugin is installed and active.
	 *
	 * @since 1.2.43
	 *
	 * @return bool True if WooCommerce is active, false otherwise.
	 */
	public static function is_woocommerce_active() {
		return class_exists( 'WooCommerce' ) || is_plugin_active( 'woocommerce/woocommerce.php' );
	}

	/**
	 * Determine if a plugin requires WooCommerce to function.
	 *
	 * @since 1.2.43
	 *
	 * @param string $plugin Plugin slug or init file.
	 * @return bool True if plugin depends on WooCommerce.
	 */
	public static function plugin_requires_woocommerce( $plugin ) {
		$wc_plugins = array(
			'woocommerce-payments',
			'woocommerce-payments/woocommerce-payments.php',
			'cartflows',
			'cartflows/cartflows.php',
			'woo-cart-abandonment-recovery',
			'woo-cart-abandonment-recovery/woo-cart-abandonment-recovery.php',
			'modern-cart',
			'modern-cart/modern-cart.php',
			'power-coupons',
			'power-coupons/power-coupons.php',
		);

		return in_array( $plugin, $wc_plugins, true );
	}

	/**
	 * Has Pro Version Support?
	 * And
	 * Is Pro Version Installed?
	 *
	 * Check Pro plugin version exist of requested plugin lite version.
	 *
	 * Eg. If plugin 'BB Lite Version' required to import demo. Then we check the 'BB Agency Version' is exist?
	 * If yes then we only 'Activate' Agency Version. [We couldn't install agency version.]
	 * Else we 'Activate' or 'Install' Lite Version.
	 *
	 * @since 1.0.1
	 *
	 * @param  string $lite_version Lite version init file.
	 * @return mixed               Return false if not installed or not supported by us
	 *                                    else return 'Pro' version details.
	 */
	public static function pro_plugin_exist( $lite_version = '' ) {

		// Lite init => Pro init.
		$plugins = apply_filters(
			'astra_sites_pro_plugin_exist',
			array(
				'beaver-builder-lite-version/fl-builder.php' => array(
					'slug' => 'bb-plugin',
					'init' => 'bb-plugin/fl-builder.php',
					'name' => 'Beaver Builder Plugin',
				),
				'ultimate-addons-for-beaver-builder-lite/bb-ultimate-addon.php' => array(
					'slug' => 'bb-ultimate-addon',
					'init' => 'bb-ultimate-addon/bb-ultimate-addon.php',
					'name' => 'Ultimate Addon for Beaver Builder',
				),
				'wpforms-lite/wpforms.php' => array(
					'slug' => 'wpforms',
					'init' => 'wpforms/wpforms.php',
					'name' => 'WPForms',
				),
			),
			$lite_version
		);

		if ( isset( $plugins[ $lite_version ] ) ) {

			// Pro plugin directory exist?
			if ( file_exists( WP_PLUGIN_DIR . '/' . $plugins[ $lite_version ]['init'] ) ) {
				return $plugins[ $lite_version ];
			}
		}

		return false;
	}

	/**
	 * Get the status of file system permission of "/wp-content/uploads" directory.
	 *
	 * @return void
	 */
	public static function filesystem_permission() {
		self::verify_ajax_request( 'customize', __( 'You do not have permission to perform this action.', 'astra-sites' ) );

		$wp_upload_path = wp_upload_dir();
		$permissions    = array(
			'is_readable' => false,
			'is_writable' => false,
		);

		foreach ( $permissions as $file_permission => $value ) {
			$permissions[ $file_permission ] = $file_permission( $wp_upload_path['basedir'] );
		}

		$permissions['is_wp_filesystem'] = true;
		if ( ! WP_Filesystem() ) {
			$permissions['is_wp_filesystem'] = false;
		}

		if ( defined( 'WP_CLI' ) ) {
			if ( ! $permissions['is_readable'] || ! $permissions['is_writable'] || ! $permissions['is_wp_filesystem'] ) {
				self::error_response( esc_html__( 'Please contact the hosting service provider to help you update the permissions so that you can successfully import a complete template.', 'astra-sites' ) );
				return;
			}
		}

		self::success_response(
			array(
				'permissions' => $permissions,
				'directory'   => $wp_upload_path['basedir'],
			)
		);
	}

	/**
	 * Required Plugins
	 *
	 * @since 2.0.0
	 *
	 * @param  array<int, array<string, string>> $required_plugins Required Plugins.
	 * @param  array<string, mixed>              $options            Site Options.
	 * @param  array<string, mixed>              $enabled_extensions Enabled Extensions.
	 * @return mixed
	 */
	public static function required_plugins( $required_plugins = array(), $options = array(), $enabled_extensions = array() ) {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verification is done in verify_ajax_request().
		self::verify_ajax_request( 'edit_posts', __( 'Permission Denied!', 'astra-sites' ) );

		$response = array(
			'active'       => array(),
			'inactive'     => array(),
			'notinstalled' => array(),
		);

		$id     = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : '';
		$screen = isset( $_POST['screen'] ) ? sanitize_text_field( $_POST['screen'] ) : '';

		if ( ! isset( $_POST['ai_plugin_permission'] ) ) {
			if ( 'elementor' === $screen ) {
				$imported_demo_data = get_option( 'astra_sites_import_elementor_data_' . $id, array() );
				if ( isset( $imported_demo_data['type'] ) && 'astra-blocks' === $imported_demo_data['type'] ) { // @phpstan-ignore-line
						$plugins          = astra_sites_safe_unserialize( $imported_demo_data['post-meta']['astra-blocks-required-plugins'] ); // @phpstan-ignore-line
						$required_plugins = false !== $plugins ? $plugins : array();
				} else {
						$required_plugins = isset( $imported_demo_data['site-pages-required-plugins'] ) ? $imported_demo_data['site-pages-required-plugins'] : array(); // @phpstan-ignore-line
				}
			} else {
				$required_plugins = astra_get_site_data( 'required-plugins' );
			}
		}

		if ( ! empty( $_POST['feature_plugins'] ) ) {
			$feature_plugins  = is_string( $_POST['feature_plugins'] ) ? json_decode( wp_unslash( $_POST['feature_plugins'] ), true ) : array();
			$required_plugins = '' === $required_plugins ? [] : $required_plugins;

			if ( is_array( $feature_plugins ) && is_array( $required_plugins ) ) {
				// Create a set of existing plugin slugs.
				$existing_slugs = array_column( $required_plugins, 'slug' );

				// Merge only the new feature plugins that aren't already in the required plugins.
				foreach ( $feature_plugins as $feature_plugin ) {
					if ( isset( $feature_plugin['slug'] ) && ! in_array( $feature_plugin['slug'], $existing_slugs, true ) ) {
						$required_plugins[] = $feature_plugin;
						$existing_slugs[]   = $feature_plugin['slug']; // Keep the slug list updated.
					}
				}
			}
		}

		$data = self::get_required_plugins_data( $response, $required_plugins ); // @phpstan-ignore-line

		self::success_response( $data );
		return $data;
		// phpcs:enable
	}

	/**
	 * Retrieves the required plugins data based on the response and required plugin list.
	 *
	 * @param array<string, array<string, mixed>> $response            The response containing the plugin data.
	 * @param array<int, array<string, string>>   $required_plugins    The list of required plugins.
	 * @since 3.2.5
	 * @return array<string, mixed>|null                The array of required plugins data or null on error.
	 */
	public static function get_required_plugins_data( $response, $required_plugins ) {

		$learndash_course_grid = 'https://www.learndash.com/add-on/course-grid/';
		$learndash_woocommerce = 'https://www.learndash.com/add-on/woocommerce/';
		if ( is_plugin_active( 'sfwd-lms/sfwd_lms.php' ) ) {
			$learndash_addons_url  = admin_url( 'admin.php?page=learndash_lms_addons' );
			$learndash_course_grid = $learndash_addons_url;
			$learndash_woocommerce = $learndash_addons_url;
		}

		$third_party_required_plugins = array();
		$third_party_plugins          = array(
			'sfwd-lms'              => array(
				'init' => 'sfwd-lms/sfwd_lms.php',
				'name' => 'LearnDash LMS',
				'link' => 'https://www.learndash.com/',
			),
			'learndash-course-grid' => array(
				'init' => 'learndash-course-grid/learndash_course_grid.php',
				'name' => 'LearnDash Course Grid',
				'link' => $learndash_course_grid,
			),
			'learndash-woocommerce' => array(
				'init' => 'learndash-woocommerce/learndash_woocommerce.php',
				'name' => 'LearnDash WooCommerce Integration',
				'link' => $learndash_woocommerce,
			),
		);

		$plugin_updates          = get_plugin_updates();
		$update_avilable_plugins = array();
		$incompatible_plugins    = array();

		if ( ! empty( $required_plugins ) ) {
			$php_version = Helper::get_php_version();
			foreach ( $required_plugins as $key => $plugin ) {

				$plugin = (array) $plugin;

				if ( 'woocommerce' === $plugin['slug'] && version_compare( $php_version, '7.0', '<' ) ) {
					$plugin['min_php_version'] = '7.0';
					$incompatible_plugins[]    = $plugin;
				}

				if ( 'presto-player' === $plugin['slug'] && version_compare( $php_version, '7.3', '<' ) ) {
					$plugin['min_php_version'] = '7.3';
					$incompatible_plugins[]    = $plugin;
				}

				/**
				 * Has Pro Version Support?
				 * And
				 * Is Pro Version Installed?
				 */
				$plugin_pro = Helper::pro_plugin_exist( $plugin['init'] );
				if ( is_array( $plugin_pro ) ) {

					if ( array_key_exists( $plugin_pro['init'], $plugin_updates ) ) {
						$update_avilable_plugins[] = $plugin_pro;
					}

					if ( ! is_array( $response ) ) {
						$response = array();
					}
					// Pro - Active.
					if ( is_plugin_active( $plugin_pro['init'] ) ) {
						$response['active'][] = $plugin_pro;

						self::after_plugin_activate( $plugin['init'], array(), array(), $plugin['slug'], true );

						// Pro - Inactive.
					} else {
						$response['inactive'][] = $plugin_pro;
					}
				} else {
					if ( array_key_exists( $plugin['init'], $plugin_updates ) ) {
						$update_avilable_plugins[] = $plugin;
					}

					// Lite - Installed but Inactive.
					if ( file_exists( WP_PLUGIN_DIR . '/' . $plugin['init'] ) && is_plugin_inactive( $plugin['init'] ) ) {
						$link                   = wp_nonce_url(
							add_query_arg(
								array(
									'action' => 'activate',
									'plugin' => $plugin['init'],
								),
								admin_url( 'plugins.php' )
							),
							'activate-plugin_' . $plugin['init']
						);
						$link                   = str_replace( '&amp;', '&', $link );
						$plugin['action']       = $link;
						$response['inactive'][] = $plugin;

						// Lite - Not Installed.
					} elseif ( ! file_exists( WP_PLUGIN_DIR . '/' . $plugin['init'] ) ) {

						// Added premium plugins which need to install first.
						if ( array_key_exists( $plugin['slug'], $third_party_plugins ) ) {
							$third_party_required_plugins[] = $third_party_plugins[ $plugin['slug'] ];
						} else {
							$link                       = wp_nonce_url(
								add_query_arg(
									array(
										'action' => 'install-plugin',
										'plugin' => $plugin['slug'],
									),
									admin_url( 'update.php' )
								),
								'install-plugin_' . $plugin['slug']
							);
							$link                       = str_replace( '&amp;', '&', $link );
							$plugin['action']           = $link;
							$response['notinstalled'][] = $plugin;
						}

						// Lite - Active.
					} else {
						$response['active'][] = $plugin;

						self::after_plugin_activate( $plugin['init'], array(), array(), $plugin['slug'], true );
					}
				}
			}
		}

		// Checking the `install_plugins` and `activate_plugins` capability for the current user.
		// To perform plugin installation process.
		$has_notinstalled = ! empty( $response['notinstalled'] );
		$has_inactive     = ! empty( $response['inactive'] );
		$can_install      = current_user_can( 'install_plugins' );
		$can_activate     = current_user_can( 'activate_plugins' );

		if ( is_multisite() ) {
			/**
			 * For multisite: Super admins can handle both, subsite admins need activation capability.
			 * Determine if there is a permission error based on plugin installation/activation needs and user capabilities.
			 * Scenarios:
			 * 1. User has no permissions (can't install or activate) and plugins need to be installed or activated.
			 * 2. User can activate but not install:
			 *    - If plugins need installation -> error.
			 *    - If plugins only need activation -> no error.
			 * 3. User can install but not activate:
			 *    - If plugins need activation -> error.
			 *    - If plugins only need installation -> no error.
			 * 4. User has both permissions -> no error.
			 */
			$is_error = false;
			if ( $has_notinstalled && ! $can_install ) {
				$is_error = true;
			} elseif ( $has_inactive && ! $can_activate ) {
				$is_error = true;
			}
		} else {
			// For single site: Check install and activate permissions separately.
			$is_error = ( ! $can_install && $has_notinstalled ) || ( ! $can_activate && $has_inactive );
		}

		if (
			( ! defined( 'WP_CLI' ) && wp_doing_ajax() ) && $is_error ) {
			$message               = __( 'Insufficient Permission. Please contact your Super Admin to allow the install required plugin permissions.', 'astra-sites' );
			$required_plugins_list = array_merge( $response['notinstalled'], $response['inactive'] );

			// Show only not installed plugins if user can activate plugins.
			if ( $can_activate ) {
				$required_plugins_list = $response['notinstalled'];
			}
			$markup  = $message;
			$markup .= '<ul>';
			foreach ( $required_plugins_list as $key => $required_plugin ) {
				$markup .= '<li>' . esc_html( $required_plugin['name'] ) . '</li>';
			}
			$markup .= '</ul>';

			$data = array(
				'markup'                       => $markup,
				'required_plugins'             => $response,
				'third_party_required_plugins' => $third_party_required_plugins,
				'update_avilable_plugins'      => $update_avilable_plugins,
				'incompatible_plugins'         => $incompatible_plugins,
				'error'                        => $is_error,
			);
			self::error_response( $data );
			return null;
		}

		return array(
			'required_plugins'             => $response,
			'third_party_required_plugins' => $third_party_required_plugins,
			'update_avilable_plugins'      => $update_avilable_plugins,
			'incompatible_plugins'         => $incompatible_plugins,
			'error'                        => $is_error,
		);
	}

	/**
	 * After Plugin Activate
	 *
	 * @since 2.0.0
	 *
	 * @param  string               $plugin_init        Plugin Init File.
	 * @param  array<string, mixed> $options            Site Options.
	 * @param  array<string, mixed> $enabled_extensions Enabled Extensions.
	 * @param  string               $plugin_slug        Plugin slug.
	 * @param  bool                 $was_plugin_active  Flag indicating if the plugin was already active.
	 * @return void
	 */
	public static function after_plugin_activate( $plugin_init = '', $options = array(), $enabled_extensions = array(), $plugin_slug = '', $was_plugin_active = false ) {
		$data = array(
			'astra_site_options' => $options,
			'enabled_extensions' => $enabled_extensions,
			'plugin_slug'        => $plugin_slug,
			'was_plugin_active'  => $was_plugin_active,
		);

		do_action( 'astra_sites_after_plugin_activation', $plugin_init, $data );
	}

	/**
	 * Required Plugin Activate
	 *
	 * @since 2.0.0 Added parameters $init, $options & $enabled_extensions to add the WP CLI support.
	 * @since 1.0.0
	 * @param  string               $init               Plugin init file.
	 * @param  array<string, mixed> $options            Site options.
	 * @param  array<string, mixed> $enabled_extensions Enabled extensions.
	 * @return void
	 */
	public static function required_plugin_activate( $init = '', $options = array(), $enabled_extensions = array() ) {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verification is done in verify_ajax_request().
		self::verify_ajax_request( 'activate_plugins', __( "Error: You don't have the required permissions to activate plugins.", 'astra-sites' ) );

		// Additional validation for AJAX requests.
		if ( ! defined( 'WP_CLI' ) && wp_doing_ajax() && ( ! isset( $_POST['init'] ) || ! sanitize_text_field( $_POST['init'] ) ) ) {
			self::error_response(
				array(
					'success' => false,
					'error'   => __( 'Error: Missing or invalid plugin initialization parameter.', 'astra-sites' ),
				)
			);
			return;
		}

		Ai_Builder_Error_Handler::Instance()->start_error_handler();

		$plugin_init = isset( $_POST['init'] ) ? esc_attr( sanitize_text_field( $_POST['init'] ) ) : $init;
		$plugin_slug = isset( $_POST['slug'] ) ? esc_attr( sanitize_text_field( $_POST['slug'] ) ) : '';

		// Check if plugin requires WooCommerce but WooCommerce is not active.
		if ( self::plugin_requires_woocommerce( $plugin_slug ) || self::plugin_requires_woocommerce( $plugin_init ) ) {
			if ( ! self::is_woocommerce_active() ) {
				$message = __( 'This plugin requires WooCommerce to be installed and activated first.', 'astra-sites' );

				if ( defined( 'WP_CLI' ) ) {
					self::error_response( $message );
				} elseif ( wp_doing_ajax() ) {
					// Send deprioritize response instead of error.
					self::success_response(
						array(
							'success'     => false,
							'status'      => 'deprioritize',
							'action'      => 'defer',
							'message'     => $message,
							'reason'      => 'missing_woocommerce',
							'dependency'  => 'woocommerce',
							'plugin_slug' => $plugin_slug,
							'plugin_init' => $plugin_init,
							'retry_after' => 'woocommerce_activation',
						)
					);
				}
				return;
			}
		}

		/**
		 * Disabled redirection to plugin page after activation.
		 * Silecing the callback for WP Live Chat plugin.
		 */
		add_filter( 'wp_redirect', '__return_false' );
		$silent = 'wp-live-chat-support/wp-live-chat-support.php' === $plugin_init ? true : false;

		$was_plugin_active = is_plugin_active( $plugin_init );

		$activate = activate_plugin( $plugin_init, '', false, $silent );

		Ai_Builder_Error_Handler::Instance()->stop_error_handler();

		if ( is_wp_error( $activate ) ) {
			self::error_response(
				defined( 'WP_CLI' )
					? sprintf(
						// translators: 1: Error message.
						__( 'Plugin Activation Error: %s', 'astra-sites' ),
						$activate->get_error_message()
					)
					: $activate->get_error_message()
			);
			return;
		}

		$options            = astra_get_site_data( 'astra-site-options-data' );
		$enabled_extensions = astra_get_site_data( 'astra-enabled-extensions' );

		$options            = is_array( $options ) ? $options : array();
		$enabled_extensions = is_array( $enabled_extensions ) ? $enabled_extensions : array();

		self::after_plugin_activate( $plugin_init, $options, $enabled_extensions, $plugin_slug, $was_plugin_active );

		$success_data = array(
			'success' => true,
			'status'  => 'activated',
			'message' => __( 'Plugin Activated', 'astra-sites' ),
		);

		self::success_response( $success_data );
		// phpcs:enable
	}

	/**
	 * Backup our existing settings.
	 *
	 * @return void
	 */
	public static function backup_settings() {

		Ai_Builder_Importer_Log::add( 'Backing up existing settings', 'info' );

		self::verify_ajax_request( 'manage_options', __( 'User does not have permission!', 'astra-sites' ) );

		if ( ! class_exists( 'STImporter\Resetter\ST_Resetter' ) ) {
			self::error_response( __( 'Required class not found.', 'astra-sites' ) );
			return;
		}

		$log_file_path = ST_Resetter::backup_settings();

		Ai_Builder_Importer_Log::add( 'Backup completed successfully', 'success', array( 'backup_file' => $log_file_path ) );

		// translators: %s: Log file path.
		self::success_response( sprintf( __( 'File generated at %s', 'astra-sites' ), $log_file_path ) );
	}

	/**
	 * Import Options.
	 *
	 * @since 1.0.14
	 * @since 1.4.0 The `$options_data` was added.
	 *
	 * @param  array<string, mixed> $options_data Site Options.
	 * @return void
	 */
	public static function import_options( $options_data = array() ) {

		Ai_Builder_Importer_Log::add( 'Importing site options', 'info', array( 'options' => $options_data ) );

		self::verify_ajax_request( 'customize' );

		if ( empty( $options_data ) ) {
			$options_data = astra_get_site_data( 'astra-site-options-data' );
		}

		if ( ! class_exists( 'STImporter\Importer\ST_Importer' ) || ! class_exists( 'STImporter\Importer\ST_Option_Importer' ) ) {
			self::error_response( __( 'Required class not found.', 'astra-sites' ) );
			return;
		}

		$result = ST_Importer::import_options( $options_data );

		if ( false === $result['status'] ) {
			self::error_response( $result['error'] );
			return;
		}

		self::success_response( __( 'Site options Imported!', 'astra-sites' ) );
	}

	/**
	 * Import Widgets.
	 *
	 * @since 1.0.14
	 * @since 1.4.0 The `$widgets_data` was added.
	 *
	 * @param  string $widgets_data Widgets Data.
	 * @return void
	 */
	public static function import_widgets( $widgets_data = '' ) {

		Ai_Builder_Importer_Log::add( 'Importing widgets', 'info' );

		self::verify_ajax_request( 'customize' );

		$data = astra_get_site_data( 'astra-site-widgets-data' );

		if ( defined( 'WP_CLI' ) ) {
			$data = $widgets_data;
		}

		if ( ! class_exists( 'STImporter\Importer\ST_Importer' ) ) {
			self::error_response( __( 'Required class not found.', 'astra-sites' ) );
			return;
		}

		$result = ST_Importer::import_widgets( $widgets_data, $data );

		if ( false === $result['status'] ) {
			self::error_response( $result['error'] );
			return;
		}

		self::success_response( __( 'Widget Imported!', 'astra-sites' ) );
	}

	/**
	 * Import End.
	 *
	 * @since 1.0.14
	 * @return void
	 */
	public static function import_end() {

		Ai_Builder_Importer_Log::add( 'Finalizing import process', 'info' );

		self::verify_ajax_request( 'customize', __( "Permission denied: You don't have sufficient permissions to import. Please contact your site administrator.", 'astra-sites' ) );

		if ( ! class_exists( 'STImporter\Importer\ST_Importer_File_System' ) ) {
			self::error_response( __( 'Import failed: ST_Importer_File_System class not found. Please ensure the importer is properly loaded.', 'astra-sites' ) );
			return;
		}

		// Handle demo content retrieval with specific error handling.
		try {
			$demo_data = ST_Importer_File_System::get_instance()->get_demo_content();
			if ( is_null( $demo_data ) ) {
				$demo_data = array(); // Fallback to prevent null errors.
			}
		} catch ( \Exception $e ) {
			astra_sites_error_log( 'Import End: Exception getting demo content - ' . $e->getMessage() );
			// translators: %s: Error message.
			self::error_response( sprintf( __( 'Import failed: Unexpected error - %s', 'astra-sites' ), $e->getMessage() ) );
			return;
		} catch ( \Error $e ) {
			// translators: %s: Error message.
			self::error_response( sprintf( __( 'Import failed: Fatal error - %s', 'astra-sites' ), $e->getMessage() ) );
			return;
		}

		// Set permalink structure to use post name.
		Ai_Builder_Importer_Log::add( 'Setting permalink structure to post name', 'info' );
		update_option( 'permalink_structure', '/%postname%/' );
		update_option( 'astra-site-permalink-update-status', 'no' );

		// Safely execute import complete action.
		try {
			Ai_Builder_Importer_Log::add( 'Executing import complete actions', 'info' );
			do_action( 'astra_sites_import_complete', $demo_data );
		} catch ( \Exception $e ) {
			astra_sites_error_log( 'Import End: Exception in import_complete action - ' . $e->getMessage() );
			Ai_Builder_Importer_Log::add( 'Warning: Exception in import_complete action - ' . $e->getMessage(), 'warning' );
			// Continue execution - don't fail the entire import for hook issues.
		}

		self::success_response( __( 'Import completed successfully!', 'astra-sites' ) );
	}

	/**
	 * Reset customizer data
	 *
	 * @since 1.3.0
	 * @return void
	 */
	public static function reset_customizer_data() {

		$customizer_settings = get_option( 'astra-settings', array() );

		Ai_Builder_Importer_Log::add( 'Resetting customizer data', 'info', array( 'settings_count' => is_array( $customizer_settings ) ? count( $customizer_settings ) : 0 ) );

		self::verify_ajax_request( 'customize' );

		if ( ! class_exists( 'STImporter\Resetter\ST_Resetter' ) ) {
			self::error_response( __( 'Required class not found.', 'astra-sites' ) );
			return;
		}

		ST_Resetter::reset_customizer_data();

		self::success_response( __( 'Deleted Customizer Settings!', 'astra-sites' ) );
	}

	/**
	 * Reset site options
	 *
	 * @since 1.3.0
	 * @return void
	 */
	public static function reset_site_options() {

		$options = get_option( '_astra_sites_old_site_options', array() );

		Ai_Builder_Importer_Log::add( 'Resetting site options', 'info', array( 'options_count' => is_array( $options ) ? count( $options ) : 0 ) );

		self::verify_ajax_request( 'customize' );

		if ( ! class_exists( 'STImporter\Resetter\ST_Resetter' ) ) {
			self::error_response( __( 'Required class not found.', 'astra-sites' ) );
			return;
		}

		ST_Resetter::reset_site_options( $options );

		self::success_response( __( 'Deleted Site Options!', 'astra-sites' ) );
	}

	/**
	 * Reset widgets data
	 *
	 * @since 1.3.0
	 * @return void
	 */
	public static function reset_widgets_data() {

		// Get all old widget ids.
		$old_widgets_data = (array) get_option( '_astra_sites_old_widgets_data', array() );

		Ai_Builder_Importer_Log::add( 'Resetting widgets data', 'info', array( 'widgets_count' => count( $old_widgets_data ) ) );

		self::verify_ajax_request( 'customize' );

		if ( ! class_exists( 'STImporter\Resetter\ST_Resetter' ) ) {
			self::error_response( __( 'Required class not found.', 'astra-sites' ) );
			return;
		}

		ST_Resetter::reset_widgets_data( $old_widgets_data );

		self::success_response( __( 'Deleted Widgets!', 'astra-sites' ) );
	}

	/**
	 * Import Customizer Settings.
	 *
	 * @since 1.0.14
	 * @since 1.4.0  The `$customizer_data` was added.
	 *
	 * @param  array<string, mixed> $customizer_data Customizer Data.
	 * @return void
	 */
	public static function import_customizer_settings( $customizer_data = array() ) {

		Ai_Builder_Importer_Log::add( 'Importing customizer settings', 'info', array( 'settings_count' => count( $customizer_data ) ) );

		self::verify_ajax_request( 'customize' );

		if ( empty( $customizer_data ) ) {
			$customizer_data = astra_get_site_data( 'astra-site-customizer-data' );
			Ai_Builder_Importer_Log::add( 'Fetched customizer data from site data', 'info', array( 'settings_count' => is_array( $customizer_data ) ? count( $customizer_data ) : 0 ) );
		}

		if ( empty( $customizer_data ) ) {
			self::error_response( __( 'Customizer data is empty!', 'astra-sites' ) );
			return;
		}

		if ( ! class_exists( 'STImporter\Importer\ST_Importer' ) ) {
			self::error_response( __( 'Required class not found.', 'astra-sites' ) );
			return;
		}

		$result = ST_Importer::import_customizer_settings( $customizer_data );

		if ( false === $result['status'] ) {
			self::error_response( $result['error'] );
			return;
		}

		Ai_Builder_Importer_Log::add( 'Customizer settings imported successfully', 'success' );

		self::success_response();
	}

	/**
	 * Verify AJAX request nonce and user capabilities.
	 *
	 * This method performs security checks for AJAX requests by verifying the nonce
	 * and checking if the current user has the required capability.
	 *
	 * @param string $capability The required capability. Default 'manage_options'.
	 * @param string $error_message Custom error message. Default 'You are not allowed to perform this action'.
	 * @since 1.2.70
	 * @return void Exits with error response if checks fail.
	 */
	public static function verify_ajax_request( $capability = 'manage_options', $error_message = '' ) {
		// Skip checks if running in WP-CLI.
		if ( defined( 'WP_CLI' ) || ! wp_doing_ajax() ) {
			return;
		}

		// Verify nonce.
		check_ajax_referer( 'astra-sites', '_ajax_nonce' );

		// Check user capability.
		if ( ! current_user_can( $capability ) ) {
			if ( empty( $error_message ) ) {
				$error_message = __( 'You are not allowed to perform this action', 'astra-sites' );
			}

			// Log the error.
			Ai_Builder_Importer_Log::add( $error_message, 'error' );

			wp_send_json_error( $error_message );
		}
	}

	/**
	 * Send error response for both WP_CLI and AJAX contexts.
	 *
	 * @param string|array<string, mixed>|mixed|null $data Error message or data to display.
	 *
	 * @since 1.2.63
	 * @return void
	 */
	public static function error_response( $data = null ) {
		// Extract error message for logging.
		$error_message = '';
		$context       = array();

		if ( is_array( $data ) ) {
			if ( isset( $data['error'] ) ) {
				$error_message = is_string( $data['error'] ) ? $data['error'] : wp_json_encode( $data['error'] );
			} elseif ( isset( $data['message'] ) ) {
				$error_message = is_string( $data['message'] ) ? $data['message'] : wp_json_encode( $data['message'] );
			} elseif ( isset( $data['data'] ) ) {
				$error_message = is_string( $data['data'] ) ? $data['data'] : wp_json_encode( $data['data'] );
			} else {
				$error_message = wp_json_encode( $data );
			}
			$context = $data;
		} else {
			$error_message = is_string( $data ) ? $data : wp_json_encode( $data );
		}

		// Determine severity based on error code.
		$severity      = is_array( $data ) && isset( $data['code'] ) && str_contains( $data['code'], 'fatal' ) ? 'fatal' : 'error';
		$error_message = $error_message ? $error_message : 'An unknown error occurred.';

		// Log the error with context.
		Ai_Builder_Importer_Log::add( $error_message, $severity, $context );

		if ( defined( 'WP_CLI' ) ) {
			$error = is_array( $data ) && isset( $data['error'] ) ? $data['error'] : $data;
			$error = is_array( $error ) ? wp_json_encode( $error ) : $error;

			\WP_CLI::error( $error );
		} elseif ( wp_doing_ajax() ) {
			wp_send_json_error( $data );
		}
	}

	/**
	 * Send success response for both WP_CLI and AJAX contexts.
	 *
	 * @param string|array<string|int, mixed>|null $data Success message or data to send.
	 *
	 * @since 1.2.63
	 * @return void
	 */
	public static function success_response( $data = null ) {
		// Extract success message for logging.
		$success_message = '';
		$context         = array();

		if ( is_array( $data ) ) {
			if ( isset( $data['message'] ) ) {
				$success_message = is_string( $data['message'] ) ? $data['message'] : wp_json_encode( $data['message'] );
			} elseif ( isset( $data['data'] ) ) {
				$success_message = is_string( $data['data'] ) ? $data['data'] : wp_json_encode( $data['data'] );
			} else {
				$success_message = wp_json_encode( $data );
			}
			$context = $data;
		} else {
			$success_message = is_string( $data ) ? $data : wp_json_encode( $data );
		}

		// Log the success with context.
		if ( $success_message && 'null' !== $success_message ) {
			Ai_Builder_Importer_Log::add( $success_message, 'success', $context );
		}

		if ( defined( 'WP_CLI' ) ) {
			$message = is_array( $data ) && isset( $data['message'] ) ? $data['message'] : $data;
			$message = is_array( $message ) ? wp_json_encode( $message ) : $message;

			\WP_CLI::line( $message );
			return;
		}

		if ( wp_doing_ajax() ) {
			$response = empty( $data ) ? array() : $data;
			wp_send_json_success( $response );
		}
	}

	/**
	 * Get the server's country code using its public IP.
	 *
	 * @param string $provider Optional. GeoIP provider: 'ipwhois', 'ipapi', or 'ipinfo'. Default 'ipwhois'.
	 * @param string $token    Optional. API token (only needed for ipapi/ipinfo).
	 *
	 * @since 1.2.59
	 * @return string Two-letter ISO country code (e.g., 'RU', 'US'), or 'unknown' on failure.
	 */
	public static function get_server_country_code( $provider = 'ipwhois', $token = '' ) {
		// Step 1: Get server's public IP.
		$response = wp_remote_get( 'https://api.ipify.org' );
		if ( is_wp_error( $response ) ) {
			return 'unknown';
		}

		$ip = wp_remote_retrieve_body( $response );
		if ( empty( $ip ) ) {
			return 'unknown';
		}

		// Step 2: Select provider endpoint.
		switch ( strtolower( $provider ) ) {
			case 'ipapi':
				// Requires token for higher limits.
				$url = "https://ipapi.co/{$ip}/country/";
				if ( ! empty( $token ) ) {
					$url = "https://ipapi.co/{$ip}/country/?key={$token}";
				}
				break;

			case 'ipinfo':
				$url = "https://ipinfo.io/{$ip}/country";
				if ( ! empty( $token ) ) {
					$url .= "?token={$token}";
				}
				break;

			case 'ipwhois':
			default:
				// Default: ipwho.is (no token needed).
				$url = "https://ipwho.is/{$ip}";
				break;
		}

		// Step 3: Make request.
		$response = wp_remote_get( $url );
		if ( is_wp_error( $response ) ) {
			return 'unknown';
		}

		$body = wp_remote_retrieve_body( $response );

		// Step 4: Parse response based on provider.
		if ( 'ipwhois' === $provider ) {
			$data = json_decode( $body, true );
			if ( is_array( $data ) && isset( $data['country_code'] ) && is_string( $data['country_code'] ) ) {
				return $data['country_code'];
			}
			return 'unknown';
		}

		// ipapi and ipinfo return plain text country code.
		$country = trim( $body );
		return ! empty( $country ) ? $country : 'unknown';
	}

	/**
	 * Get Images Engine
	 *
	 * @since 1.2.59
	 * @return string Image Engine.
	 */
	public static function get_images_engine() {
		$country_code = get_transient( 'zipwp_images_server_country_code' );

		if ( false === $country_code ) {
			$country_code = self::get_server_country_code();
			set_transient( 'zipwp_images_server_country_code', $country_code, MONTH_IN_SECONDS );
		}

		// Use Unsplash for Russia as Pexels is blocked there.
		if ( 'RU' === $country_code ) {
			return 'unsplash';
		}

		// Default to Pexels.
		return 'pexels';
	}
}
