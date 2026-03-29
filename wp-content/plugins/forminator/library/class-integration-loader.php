<?php
/**
 * Forminator Integration Loader
 *
 * @package Forminator
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/* @noinspection PhpIncludeInspection */
require_once forminator_plugin_dir() . 'library/addon/class-integration-exception.php';
require_once forminator_plugin_dir() . 'library/addon/class-forminator-integration-settings-exception.php';
require_once forminator_plugin_dir() . 'library/addon/class-addon-container.php';
require_once forminator_plugin_dir() . 'library/addon/contracts/interface-integration.php';
require_once forminator_plugin_dir() . 'library/addon/class-integration.php';
require_once forminator_plugin_dir() . 'library/addon/class-integration-settings.php';
require_once forminator_plugin_dir() . 'library/addon/class-integration-form-settings.php';
require_once forminator_plugin_dir() . 'library/addon/class-integration-hooks.php';
require_once forminator_plugin_dir() . 'library/addon/class-integration-form-hooks.php';
require_once forminator_plugin_dir() . 'library/addon/class-integration-poll-settings.php';
require_once forminator_plugin_dir() . 'library/addon/class-integration-poll-hooks.php';
require_once forminator_plugin_dir() . 'library/addon/class-integration-quiz-settings.php';
require_once forminator_plugin_dir() . 'library/addon/class-integration-quiz-hooks.php';
require_once forminator_plugin_dir() . 'library/addon/admin/class-integration-admin-ajax.php';

/**
 * Class Forminator_Integration_Loader
 * Responsible for registering addon and hold its information throughout application
 *
 * @since 1.1
 */
class Forminator_Integration_Loader {

	/**
	 * WP option name of activated addons
	 *
	 * @since 1.1
	 * @var string
	 */
	private static $_active_addons_option = 'forminator_activated_addons';

	/**
	 * Forminator_Integration_Loader instance
	 *
	 * @since 1.1
	 * @var self
	 */
	private static $instance = null;

	/**
	 * Get instance of loader
	 *
	 * @since 1.1
	 * @return Forminator_Integration_Loader
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Define pre_activated addons here
	 *
	 * @since 1.1
	 * @var array
	 */
	private static $pre_activated_addons = array();

	/**
	 * Array Access-able of Registered Integrations
	 *
	 * @since 1.1
	 * @var Forminator_Addon_Container
	 */
	private $addons;

	/**
	 * Array of slug activated addons
	 *
	 * @since 1.1
	 * @var array
	 */
	private $activated_addons = array();

	/**
	 * Default addon error messages
	 * will be used when error happened and loader cant get addon error messages
	 *
	 * @since 1.1
	 * @var array
	 */
	private $default_addon_error_messages = array();

	/**
	 * Last Error Message on loader
	 *
	 * @since 1.1
	 * @var string
	 */
	private $last_error_message = '';

	/**
	 * Flag when options of `forminator_activated_addons` not exist on database
	 * When its true, Loader will assume, user is first time updated to 1.1
	 * And will try to activate @see Forminator_Integration_Loader::$pre_activated_addons
	 *
	 * @since 1.1
	 * @var bool
	 */
	private $is_non_exist_activated_option = false;

	/**
	 * Flag when options of `forminator_activated_addons` exist but empty on database
	 * When its true, Loader will assume, user is already have 1.1,
	 * but no addons are currently activated
	 *
	 * @since 1.1
	 * @var bool
	 */
	private $is_empty_activated_option = false;

	/**
	 * Forminator_Integration_Loader constructor.
	 *
	 * @since 1.1
	 */
	public function __construct() {
		$this->addons = new Forminator_Addon_Container();

		/**
		 * Initiate activated addons
		 */
		$active_addons = get_option( self::$_active_addons_option, false );
		if ( false === $active_addons ) {
			$this->is_non_exist_activated_option = true;
			$active_addons                       = array();
		} elseif ( empty( $active_addons ) ) {
			$active_addons                   = array();
			$this->is_empty_activated_option = true;
		}

		$active_addons = array_unique( $active_addons );

		$this->activated_addons = $active_addons;
		if ( in_array( 'zapier', $active_addons, true ) ) {
			add_action( 'forminator_addons_loaded', array( $this, 'update_zapier_to_webhook' ) );
		}

		/**
		 * Initiate standard default error messages
		 */
		$this->default_addon_error_messages = array(
			'activate'             => esc_html__( 'Failed to activate addon', 'forminator' ),
			'deactivate'           => esc_html__( 'Failed to deactivate addon', 'forminator' ),
			'update_settings'      => esc_html__( 'Failed to update settings', 'forminator' ),
			'update_form_settings' => esc_html__( 'Failed to update form settings', 'forminator' ),
		);

		// Only enable wp_ajax hooks.
		Forminator_Integration_Admin_Ajax::get_instance();
	}

	/**
	 * Load default addon files
	 *
	 * @param string $addon_slug Integration slug.
	 * @return void
	 */
	private static function load_default_files( $addon_slug ) {
		$files = array(
			'',
			'-settings-trait',
			'-form-settings',
			'-poll-settings',
			'-quiz-settings',
			'-form-hooks',
			'-poll-hooks',
			'-quiz-hooks',
		);

		foreach ( $files as $file ) {
			$file_path = forminator_plugin_dir() . 'addons/pro/' . $addon_slug . '/class-forminator-addon-' . $addon_slug . $file . '.php';
			if ( file_exists( $file_path ) ) {
				require_once $file_path;
			}
		}
	}

	/**
	 * Register new Integration
	 *
	 * @since 1.1
	 *
	 * @param Forminator_Integration|string $class_name instance of Integration or its classname.
	 *
	 * @return bool
	 */
	public function register( $class_name ) {
		try {

			/**
			 * Fires immediately after an addon registering.
			 *
			 * This action executed before whole process of registering addon.
			 * Validation and requirement check has not been done,
			 * so its possible the addon will not registered in the end,
			 * when validation fail or requirement not set @see Forminator_Integration_Loader::register()
			 *
			 * @since 1.1
			 *
			 * @param Forminator_Integration|string $class_name instance of Integration or its class name.
			 */
			do_action( 'forminator_before_addon_registered', $class_name );

			if ( $class_name instanceof Forminator_Integration ) {
				$addon_class = $class_name;
			} else {
				if ( ! strpos( $class_name, '_' ) ) {
					// Integration slug is passed.
					$class_slug = $class_name;
					$class_name = 'Forminator_' . ucfirst( $class_name );
					self::load_default_files( $class_slug );
				}
				$addon_class = $this->validate_addon_class( $class_name );
			}

			$registered_addons = $this->addons;

			/**
			 * Filter addon instance.
			 *
			 * Its possible to replace / modify addon instance when its registered
			 * Keep in mind instance that returned by this filter will be used throughout app
			 * Return must be instance of @see Forminator_Integration.
			 * It will be then validated by @see Forminator_Integration_Loader::validate_addon_instance()
			 *
			 * @since 1.1
			 *
			 * @param Forminator_Integration $addon_class       Current Integration class instance.
			 * @param array                     $registered_addons Current registered addons.
			 */
			$addon_class = apply_filters( 'forminator_addon_instance', $addon_class, $registered_addons );

			$addon = $this->validate_addon_instance( $addon_class );

			$this->addons[ $addon->get_slug() ] = $addon;

			if ( $this->is_non_exist_activated_option && self::$pre_activated_addons ) {
				if ( in_array( $addon->get_slug(), self::$pre_activated_addons, true ) ) {
					$this->activate_addon( $addon->get_slug() );
				}
			}

			/**
			 * Fires after addon successfully registered
			 *
			 * When addon registered, this action will be fire
			 * If addon not registered because one or other things,
			 * this action will not executed
			 *
			 * @since 1.1
			 *
			 * @param Forminator_Integration $addon Current addon that successfully registered.
			 */
			do_action( 'forminator_after_addon_registered', $addon );

			return true;
		} catch ( Forminator_Integration_Exception $e ) {
			forminator_addon_maybe_log( __METHOD__, $class_name, $e->getMessage() );

			return false;
		}
	}

	/**
	 * Validate Integration by its class name
	 *
	 * @since 1.1
	 *
	 * @param string $class_name Class Name.
	 *
	 * @return Forminator_Integration
	 * @throws Forminator_Integration_Exception When there is an addon error.
	 */
	private function validate_addon_class( $class_name ) {
		if ( ! class_exists( $class_name ) ) {
			throw new Forminator_Integration_Exception( esc_html( 'Integration with ' . $class_name . ' does not exist' ) );
		}

		if ( ! is_callable( array( $class_name, 'get_instance' ) ) ) {
			throw new Forminator_Integration_Exception( esc_html( 'Integration with ' . $class_name . ' does not have get_instance method' ) );
		}

		$addon_class = call_user_func( array( $class_name, 'get_instance' ) );

		return $addon_class;
	}

	/**
	 * Valdate addon instance
	 *
	 * @since 1.1
	 *
	 * @param Forminator_Integration $instance Forminator_Integration.
	 *
	 * @return Forminator_Integration
	 * @throws Forminator_Integration_Exception When there is an addon error.
	 */
	private function validate_addon_instance( Forminator_Integration $instance ) {
		/**
		 * Forminator_Integration
		 *
		 * @var Forminator_Integration $addon_class */
		$addon_class = $instance;
		$class_name  = get_class( $instance );

		if ( ! $addon_class instanceof Forminator_Integration ) {
			throw new Forminator_Integration_Exception( esc_html( 'Integration with ' . $class_name . ' is not instanceof Forminator_Integration' ) );
		}
		$slug    = $addon_class->get_slug();
		$version = $addon_class->get_version();

		if ( empty( $slug ) ) {
			throw new Forminator_Integration_Exception( esc_html( 'Integration with ' . $class_name . ' does not have slug' ) );
		}

		// FIFO.
		if ( isset( $this->addons[ $slug ] ) ) {
			throw new Forminator_Integration_Exception( esc_html( 'Integration with slug ' . $slug . ' already exist' ) );
		}
		if ( empty( $version ) ) {
			throw new Forminator_Integration_Exception( esc_html( 'Integration with slug ' . $slug . ' does not have valid version' ) );
		}

		// check version changed if active.
		if ( $this->addon_is_active( $slug ) ) {
			try {
				// silent.
				if ( $addon_class->is_version_changed() ) {
					$addon_class->version_changed( $addon_class->get_installed_version(), $addon_class->get_installed_version() );
				}
			} catch ( Exception $e ) {
				forminator_addon_maybe_log( $addon_class->get_slug(), 'failed to trigger version_changed', $e->getMessage() );
			}
		}

		return $addon_class;
	}

	/**
	 * Get Integration Instance
	 *
	 * @since 1.1
	 *
	 * @param string $slug Slug.
	 *
	 * @return Forminator_Integration|null
	 */
	public function get_addon( $slug ) {
		return $this->addons[ $slug ];
	}

	/**
	 * Get All registered Integrations
	 *
	 * @since 1.1
	 * @return Forminator_Addon_Container
	 */
	public function get_addons() {
		return $this->addons;
	}

	/**
	 * Check if addon is active
	 *
	 * @since 1.1
	 *
	 * @param string $slug Slug.
	 *
	 * @return bool
	 */
	public function addon_is_active( $slug ) {
		if ( in_array( $slug, $this->activated_addons, true ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Deactivate Integration
	 * This function will call `deactivate` function of addon class if available
	 *
	 * @since 1.1
	 *
	 * @param string $slug Slug.
	 *
	 * @return bool
	 */
	public function deactivate_addon( $slug ) {
		$addon = $this->get_addon( $slug );
		if ( is_null( $addon ) ) {
			$this->last_error_message = esc_html__( 'Integration not found', 'forminator' );

			return false;
		}

		if ( ! $this->addon_is_active( $slug ) ) {
			$this->last_error_message = esc_html__( 'Integration was not activated before', 'forminator' );

			return false;
		}

		$deactivated = $addon->deactivate();
		if ( ! $deactivated ) {
			$error_message = $addon->get_deactivation_error_message();
			if ( empty( $error_message ) ) {
				$error_message = $this->default_addon_error_messages['deactivate'];
			}
			$this->last_error_message = $error_message;

			return false;
		}

		$this->force_remove_activated_addons( $slug );

		/**
		 * Fires after addon successfully deactivated
		 *
		 * Keep in mind that addon is already deactivated here,
		 * So mostly `$addon` method will fail if it requires `$addon` to be **active**
		 *
		 * @since 1.1
		 *
		 * @param Forminator_Integration $addon Current deactivated addon.
		 */
		do_action( 'forminator_after_addon_deactivated', $addon );

		return true;
	}

	/**
	 * Add activated addons to wp options
	 *
	 * @since 1.1
	 *
	 * @param string $slug Slug.
	 */
	private function add_activated_addons( $slug ) {
		$addon                    = $this->get_addon( $slug );
		$this->activated_addons[] = $slug;
		update_option( self::$_active_addons_option, $this->activated_addons );
		// take from __get version since its new addon.
		update_option( $addon->get_version_options_name(), $addon->get_version() );
	}

	/**
	 * Force Remove activated addons
	 * remove activated addons from wp options, without calling deactivate on addon function
	 *
	 * @since 1.1
	 *
	 * @param string $slug Slug.
	 */
	public function force_remove_activated_addons( $slug ) {
		$addon   = $this->get_addon( $slug );
		$options = array();

		$setting_form_meta_name = 'forminator_addon_' . $slug . '_form_settings';

		if ( $addon ) {
			$version_options_name  = $addon->get_version_options_name();
			$settions_options_name = $addon->get_settings_options_name();

			$options = get_option( $settions_options_name );
			if ( is_array( $options ) ) {
				unset( $options[ $addon->multi_global_id ] );
				update_option( $settions_options_name, $options );
			}
			$setting_form_meta_name .= '_' . $addon->multi_global_id;
		} else {
			// probably just want to remove the options.
			$version_options_name  = 'forminator_addon_' . $slug . '_version';
			$settions_options_name = 'forminator_addon_' . $slug . '_settings';
		}

		if ( empty( $options ) ) {
			// delete version.
			delete_option( $version_options_name );
			// delete general settings.
			delete_option( $settions_options_name );

			$index = array_search( $slug, $this->activated_addons, true );
			if ( false !== $index ) {
				unset( $this->activated_addons[ $index ] );
				// reset keys.
				$this->activated_addons = array_values( $this->activated_addons );
				update_option( self::$_active_addons_option, $this->activated_addons );
			}
		}
		// Delete post meta.
		delete_post_meta_by_key( $setting_form_meta_name );

		/**
		 * Fires when activated addons removed from wp options
		 *
		 * @since 1.1
		 *
		 * @param string $slug addon `slug` removed.
		 * @param Forminator_Integration|null Integration instance or null when addon instance unavailable
		 */
		do_action( 'forminator_after_activated_addons_removed', $slug, $addon );
	}

	/**
	 * Activate Integration
	 * This function will call `activate` function on addon if available
	 *
	 * @since 1.1
	 *
	 * @param string $slug Slug.
	 *
	 * @return bool
	 */
	public function activate_addon( $slug ) {
		$addon = $this->get_addon( $slug );

		/**
		 * Fires before Integration activated
		 *
		 * @since 1.1
		 *
		 * @param string                         $slug  Slug of addon that will be activated.
		 * @param Forminator_Integration|null $addon addon instance or null, when its not unavailable.
		 */
		do_action( 'forminator_before_addon_activated', $slug, $addon );

		if ( is_null( $addon ) ) {
			$this->last_error_message = esc_html__( 'Integration not found', 'forminator' );

			return false;
		}

		if ( $this->addon_is_active( $slug ) ) {
			$this->last_error_message = esc_html__( 'Integration already activated before', 'forminator' );

			return false;
		}

		if ( ! $addon->is_activable() ) {
			$this->last_error_message = esc_html__( 'Integration is not activable', 'forminator' );

			return false;
		}

		$activated = $addon->activate();
		if ( ! $activated ) {
			$error_message = $addon->get_activation_error_message();
			if ( empty( $error_message ) ) {
				$error_message = $this->default_addon_error_messages['activate'];
			}
			$this->last_error_message = $error_message;

			return false;
		}

		$this->add_activated_addons( $slug );

		/**
		 * Fires when an Integration activated
		 *
		 * @since 1.1
		 *
		 * @param Forminator_Integration $addon Current activated addon.
		 */
		do_action( 'forminator_after_addon_activated', $addon );

		return true;
	}

	/**
	 * Get Last error message
	 *
	 * @since 1.1
	 * @return string
	 */
	public function get_last_error_message() {
		$last_error_message = $this->last_error_message;

		/**
		 * Filter Last error message of the addon loader
		 *
		 * @since 1.1
		 *
		 * @param string $last_error_message Current last error message.
		 */
		$last_error_message = apply_filters( 'forminator_addon_loader_last_error_message', $last_error_message );

		return $last_error_message;
	}

	/**
	 * Get default messages provided by loader
	 *
	 * @since 1.1
	 * @return array
	 */
	public function get_default_messages() {
		$default_addon_error_messages = $this->default_addon_error_messages;

		/**
		 * Filter default addon error messages.
		 *
		 * @since 1.1
		 *
		 * @param array $default_addon_error_messages Default addon error messages that created by loader and used if Integration not specify any.
		 */
		$default_addon_error_messages = apply_filters( 'forminator_addon_loader_default_messages', $default_addon_error_messages );

		return $default_addon_error_messages;
	}

	/**
	 * Cleanup probably addons that activated before but not it doesnt exist or become invalid
	 *
	 * CAUTION : only call this when all addons already registered
	 *
	 * @since 1.1
	 */
	public function cleanup_activated_addons() {
		$this->activated_addons = array_unique( $this->activated_addons );

		$unavailable_addons = array_diff( $this->activated_addons, $this->addons->get_slugs() );

		foreach ( $unavailable_addons as $unavailable_addon ) {
			$this->force_remove_activated_addons( $unavailable_addon );
		}
	}

	/**
	 * Update old Zapier Integration to new Webhook Integration
	 */
	public function update_zapier_to_webhook() {
		// Activate Webhook Integration.
		$this->add_activated_addons( 'webhook' );

		// Rename module Zapier settings.
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- false positive
		$wpdb->query( "UPDATE $wpdb->postmeta SET meta_key = 'forminator_addon_webhook_form_settings' WHERE meta_key = 'forminator_addon_zapier_form_settings'" );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- false positive
		$wpdb->query( "UPDATE $wpdb->postmeta SET meta_key = 'forminator_addon_webhook_poll_settings' WHERE meta_key = 'forminator_addon_zapier_poll_settings'" );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- false positive
		$wpdb->query( "UPDATE $wpdb->postmeta SET meta_key = 'forminator_addon_webhook_quiz_settings' WHERE meta_key = 'forminator_addon_zapier_quiz_settings'" );

		$this->force_remove_activated_addons( 'zapier' );
	}

	/**
	 * No activated addons = true, otherwise is false
	 *
	 * @since 1.1
	 *
	 * @return bool
	 */
	public function is_empty_activated_option() {
		return $this->is_empty_activated_option;
	}

	/**
	 * First install = true, otherwise false
	 *
	 * @since 1.1
	 * @return bool
	 */
	public function is_non_exist_activated_option() {
		return $this->is_non_exist_activated_option;
	}

	/**
	 * Get Activated addons slug
	 *
	 * @since 1.1
	 * @return array
	 */
	public function get_activated_addons() {
		return $this->activated_addons;
	}
}
