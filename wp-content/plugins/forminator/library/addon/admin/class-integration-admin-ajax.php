<?php
/**
 * The Forminator_Integration_Admin_Ajax class.
 *
 * @package Forminator
 */

/* @noinspection PhpIncludeInspection */
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_Integration_Admin_Ajax
 * Available ajax action for interacting with forminator addons
 *
 * @since 1.1
 */
class Forminator_Integration_Admin_Ajax {

	/**
	 * Default nonce action
	 *
	 * @since 1.1
	 * @var string
	 */
	private static $_nonce_action = 'forminator_addon_action';

	/**
	 * Current Nonce
	 *
	 * @since 1.1
	 * @var string
	 */
	private $_nonce = '';

	/**
	 * Current Instance
	 *
	 * @since 1.1
	 * @var self
	 */
	private static $_instance = null;

	/**
	 * Singleton
	 *
	 * @since 1.1
	 * @return Forminator_Integration_Admin_Ajax
	 */
	public static function get_instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Semaphore to avoid wp_ajax hook called multiple times
	 *
	 * @var bool
	 */
	private static $is_ajax_hooked = false;

	/**
	 * Define actions and its callback
	 *
	 * @since 1.1
	 * Forminator_Integration_Admin_Ajax constructor.
	 */
	public function __construct() {
		if ( ! self::$is_ajax_hooked ) {
			add_action( 'wp_ajax_forminator_addon_get_addons', array( $this, 'get_addons' ) );
			add_action( 'wp_ajax_forminator_addon_deactivate', array( $this, 'deactivate' ) );
			add_action( 'wp_ajax_forminator_addon_settings', array( $this, 'settings' ) );

			add_action( 'wp_ajax_forminator_addon_get_module_addons', array( $this, 'get_module_addons' ) );
			add_action( 'wp_ajax_forminator_addon_module_settings', array( $this, 'module_settings' ) );
			add_action( 'wp_ajax_forminator_addon_deactivate_for_module', array( $this, 'deactivate_for_module' ) );

			add_action( 'wp_ajax_forminator_refresh_email_lists', array( $this, 'refresh_email_lists' ) );

			self::$is_ajax_hooked = true;
		}
	}

	/**
	 * Validate Ajax request
	 *
	 * @param mixed $page_slug Page slug.
	 *
	 * @since 1.1
	 */
	private function validate_ajax( $page_slug = '' ) {
		if ( ! forminator_is_user_allowed( $page_slug ) || ! check_ajax_referer( self::$_nonce_action, false, false ) ) {
			$this->send_json_errors( esc_html__( 'Invalid request, you are not allowed to do that action.', 'forminator' ) );
		}
	}


	/**
	 * Deactivate Integration
	 *
	 * @since 1.1
	 */
	public function deactivate() {
		$this->validate_ajax( 'forminator-integrations' );
		$data  = $this->validate_and_sanitize_fields( array( 'slug' ) );
		$slug  = $data['slug'];
		$addon = forminator_get_addon( $slug );

		if ( ! empty( $data['global_id'] ) ) {
			$addon->multi_global_id = $data['global_id'];
			unset( $data['global_id'] );
		}

		forminator_maybe_attach_addon_hook( $addon );

		$deactivated = Forminator_Integration_Loader::get_instance()->deactivate_addon( $slug );
		if ( ! $deactivated ) {
			$this->send_json_errors(
				Forminator_Integration_Loader::get_instance()->get_last_error_message(),
				array(),
				array(
					'notification' => array(
						'type' => 'error',
						'text' => Forminator_Integration_Loader::get_instance()->get_last_error_message(),
					),
				)
			);
		}

		$this->send_json_success(
			esc_html__( 'Integration Deactivated', 'forminator' ),
			array(
				'notification' => array(
					'type' => 'success',
					'text' => '<strong>' . $addon->get_title() . '</strong> ' . esc_html__( 'has been disconnected successfully.', 'forminator' ),
				),
			)
		);
	}//end deactivate()

	/**
	 * Get / Save settings
	 *
	 * @since 1.1
	 */
	public function settings() {
		$this->validate_ajax( 'forminator-integrations' );
		$sanitized_post_data = $this->validate_and_sanitize_fields( array( 'slug', 'current_step', 'step' ) );
		$slug                = $sanitized_post_data['slug'];
		$step                = $sanitized_post_data['step'];
		$current_step        = $sanitized_post_data['current_step'];
		$form_id             = 0;
		if ( isset( $sanitized_post_data['form_id'] ) ) {
			$form_id = $sanitized_post_data['form_id'];
			unset( $sanitized_post_data['form_id'] );
		}
		$addon = $this->validate_addon_from_slug( $slug );

		if ( ! $addon->is_settings_available() ) {
			$this->send_json_errors( esc_html__( 'This Integration does not have settings available', 'forminator' ) );
		}

		if ( isset( $sanitized_post_data['global_id'] ) ) {
			$addon->multi_global_id = $sanitized_post_data['global_id'];
			unset( $sanitized_post_data['global_id'] );
		}

		forminator_maybe_attach_addon_hook( $addon );

		unset( $sanitized_post_data['slug'] );
		unset( $sanitized_post_data['step'] );
		unset( $sanitized_post_data['current_step'] );

		$wizard = $addon->get_settings_wizard( $sanitized_post_data, $form_id, $current_step, $step );

		$this->send_json_success(
			'',
			$wizard
		);
	}//end settings()

	/**
	 * Disconnect module from addon
	 *
	 * @since 1.1
	 */
	public function deactivate_for_module() {
		$this->validate_ajax( 'forminator' );
		$sanitized_post_data = $this->validate_and_sanitize_fields( array( 'slug', 'module_id', 'module_type' ) );
		$slug                = $sanitized_post_data['slug'];
		$module_id           = $sanitized_post_data['module_id'];
		$module_type         = $sanitized_post_data['module_type'];

		$addon = $this->validate_addon_from_slug( $slug );
		if ( ! empty( $sanitized_post_data['global_id'] ) ) {
			$addon->multi_global_id = $sanitized_post_data['global_id'];
			unset( $sanitized_post_data['global_id'] );
		}

		forminator_maybe_attach_addon_hook( $addon );

		$settings = $addon->get_addon_settings( $module_id, $module_type );
		if ( $settings instanceof Forminator_Integration_Settings ) {
			unset( $sanitized_post_data['slug'] );
			unset( $sanitized_post_data['module_id'] );
			unset( $sanitized_post_data['module_type'] );

			$addon_title = $addon->get_title();

			// handling multi_id.
			if ( isset( $sanitized_post_data['multi_id'] ) ) {
				$multi_id_label = '';
				$multi_ids      = $settings->get_multi_ids();
				foreach ( $multi_ids as $key => $multi_id ) {
					if ( isset( $multi_id['id'] ) && $multi_id['label'] ) {
						if ( $multi_id['id'] === $sanitized_post_data['multi_id'] ) {
							$multi_id_label = $multi_id['label'];
							break;
						}
					}
				}

				if ( ! empty( $multi_id_label ) ) {
					$addon_title .= ' [' . $multi_id_label . '] ';
				}
			}

			$settings->disconnect_module( $sanitized_post_data );

			$this->send_json_success(
			/* translators: integration title */
				sprintf( esc_html__( 'Successfully disconnected %s from this module', 'forminator' ), $addon->get_title() ),
				array(
					'notification' => array(
						'type' => 'success',
						'text' => '<strong>' . $addon_title . '</strong> ' . esc_html__( 'Successfully disconnected from this module', 'forminator' ),
					),
				)
			);
		} else {
			$this->send_json_errors(
			/* translators: integration title */
				sprintf( esc_html__( 'Failed to disconnect %s from this module', 'forminator' ), $addon->get_title() ),
				array(),
				array(
					'notification' => array(
						'type' => 'error',
						'text' => '<strong>' . $addon->get_title() . '</strong> ' . esc_html__( 'Failed to disconnected from this module', 'forminator' ),
					),
				)
			);
		}
	}

	/**
	 * Get Integrations list, grouped by connected status
	 *
	 * @since 1.1
	 */
	public function get_addons() {
		$this->validate_ajax( 'forminator-integrations' );
		/* @noinspection PhpUnusedLocalVariableInspection */
		$addons = forminator_get_registered_addons_grouped_by_connected();

		ob_start();

		/* @noinspection PhpIncludeInspection */
		include_once forminator_plugin_dir() . 'admin/views/integrations/page-content.php';

		$html = ob_get_clean();

		$this->send_json_success(
			'',
			$html
		);
	}

	/**
	 * Generate nonce
	 *
	 * @since 1.1
	 */
	public function generate_nonce() {
		$this->_nonce = wp_create_nonce( self::$_nonce_action );
	}

	/**
	 * Get current generated nonce
	 *
	 * @since 1.1
	 * @return string
	 */
	public function get_nonce() {
		return $this->_nonce;
	}

	/**
	 * Send Json Success to client
	 *
	 * @since 1.1
	 *
	 * @param string $message Message.
	 * @param array  $additional_data Additional data.
	 */
	private function send_json_success( $message = '', $additional_data = array() ) {
		wp_send_json_success(
			array(
				'message' => $message,
				'data'    => $additional_data,
				'nonce'   => $this->_nonce,
			)
		);
	}//end send_json_success()

	/**
	 * Send Json Error to client
	 *
	 * @since 1.1
	 *
	 * @param string $message Message.
	 * @param array  $errors Errors.
	 * @param array  $additional_data Additional data.
	 */
	private function send_json_errors( $message = '', $errors = array(), $additional_data = array() ) {
		wp_send_json_error(
			array(
				'message' => $message,
				'errors'  => $errors,
				'data'    => $additional_data,
				'nonce'   => $this->_nonce,
			)
		);
	}//end send_json_errors()


	/**
	 * Validate required fields, and sanitized post data
	 *
	 * @since 1.1
	 *
	 * @param array $required_fields Required fields.
	 *
	 * @return mixed
	 */
	private function validate_and_sanitize_fields( $required_fields = array() ) {
		$post_data = isset( $_POST['data'] ) // phpcs:ignore WordPress.Security.NonceVerification.Missing
			? ( is_string( $_POST['data'] ) // phpcs:ignore WordPress.Security.NonceVerification.Missing
				? filter_input( INPUT_POST, 'data' )
				: Forminator_Core::sanitize_array( $_POST['data'], 'data' ) ) // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput
			: '';
		if ( ! $post_data ) {
			$post_data = filter_input( INPUT_GET, 'data' );
		}

		// for serialized data or form.
		if ( is_string( $post_data ) ) {
			$post_string = $post_data;
			$post_data   = array();
			wp_parse_str( $post_string, $post_data );
		}

		$errors = array();
		foreach ( $required_fields as $key => $required_field ) {
			if ( ! isset( $post_data[ $required_field ] ) ) {
				/* translators: %s: Required field */
				$errors[] = sprintf( esc_html__( 'Field %s is required', 'forminator' ), $required_field );
				continue;
			}
		}

		if ( ! empty( $errors ) ) {
			$this->send_json_errors( esc_html__( 'Please check your form.', 'forminator' ), $errors );
		}

		// TODO: sanitize.
		foreach ( $post_data as $key => $post_datum ) {
			// sanitize here, every request will sanitized here,.
			// so we dont need to sanitize it again on other methods, unless need special treatment.
			$post_data[ $key ] = $post_datum;
		}

		return $post_data;
	}


	/**
	 * Validate addon from slug
	 *
	 * @since 1.5.2
	 *
	 * @param string $slug Addon slug.
	 *
	 * @return Forminator_Integration
	 */
	private function validate_addon_from_slug( $slug ) {
		$addon = forminator_get_addon( $slug );

		if ( ! $addon || ( ! $addon instanceof Forminator_Integration ) ) {
			$this->send_json_errors(
				esc_html__( 'Integration not found', 'forminator' ),
				array(),
				array(
					'notification' => array(
						'type' => 'error',
						'text' => '<strong>' . $slug . '</strong> ' . esc_html__( 'Integration Not Found', 'forminator' ),
					),
				)
			);
		}

		return $addon;
	}

	/**
	 * Remove instance of Integration Admin Ajax
	 *
	 * @since 1.1
	 */
	public static function remove_instance() {
		if ( ! is_null( self::$_instance ) ) {

			remove_action( 'wp_ajax_forminator_addon_get_addons', array( self::$_instance, 'get_addons' ) );
			remove_action( 'wp_ajax_forminator_addon_settings', array( self::$_instance, 'settings' ) );
			remove_action( 'wp_ajax_forminator_addon_deactivate', array( self::$_instance, 'deactivate' ) );

			remove_action( 'wp_ajax_forminator_addon_get_module_addons', array( self::$_instance, 'get_module_addons' ) );
			remove_action( 'wp_ajax_forminator_addon_module_settings', array( self::$_instance, 'module_settings' ) );
			remove_action( 'wp_ajax_forminator_addon_deactivate_for_module', array( self::$_instance, 'deactivate_for_module' ) );

			self::$is_ajax_hooked = false;
			self::$_instance      = null;
		}
	}

	/**
	 * Refresh email lists
	 */
	public function refresh_email_lists() {
		$this->validate_ajax( 'forminator-integrations' );
		$sanitized_post_data = $this->validate_and_sanitize_fields( array( 'slug', 'global_id' ) );

		$slug  = $sanitized_post_data['slug'];
		$addon = $this->validate_addon_from_slug( $slug );
		$lists = array();
		if ( ! empty( $sanitized_post_data['global_id'] ) ) {
			$addon->multi_global_id = $sanitized_post_data['global_id'];
			unset( $sanitized_post_data['global_id'] );
		}

		if ( method_exists( $addon, 'get_api' ) ) {
			$api = $addon->get_api();
			if ( method_exists( $api, 'get_all_lists' ) ) {
				$lists = $api->get_all_lists( true );
				$lists = wp_list_pluck( $lists, 'name', 'id' );
			}
		}
		$html = Forminator_Integration_Settings::get_select_html( $lists );

		wp_send_json_success(
			array(
				'options' => $html,
			)
		);
	}

	/**
	 * Get Integrations List, grouped by connected status with module
	 *
	 * @since 1.1
	 */
	public function get_module_addons() {
		$this->validate_ajax( 'forminator' );
		$sanitized_post_data = $this->validate_and_sanitize_fields( array( 'module_id', 'module_type' ) );
		$module_id           = $sanitized_post_data['module_id'];
		$module_slug         = $sanitized_post_data['module_type'];

		$addons = forminator_get_registered_addons_grouped_by_module_connected( $module_id, $module_slug );
		ob_start();

		require_once forminator_plugin_dir() . 'admin/views/integrations/main.php';

		$html = ob_get_clean();

		$this->send_json_success(
			'',
			$html
		);
	}

	/**
	 * Get / Save module settings
	 *
	 * @since 1.1
	 */
	public function module_settings() {
		$this->validate_ajax( 'forminator' );
		$sanitized_post_data = $this->validate_and_sanitize_fields( array( 'slug', 'step', 'module_id', 'module_type', 'current_step' ) );
		$slug                = $sanitized_post_data['slug'];
		$step                = $sanitized_post_data['step'];
		$current_step        = $sanitized_post_data['current_step'];
		$module_id           = $sanitized_post_data['module_id'];
		$module_type         = $sanitized_post_data['module_type'];

		$addon = $this->validate_addon_from_slug( $slug );

		$is_settings_available = 'is_' . $module_type . '_settings_available';
		if ( ! $addon->$is_settings_available( $module_id ) ) {
			$this->send_json_errors( esc_html__( 'This Integration does not have module settings available', 'forminator' ) );
		}

		forminator_maybe_attach_addon_hook( $addon );

		if ( isset( $sanitized_post_data['global_id'] ) ) {
			$addon->multi_global_id = $sanitized_post_data['global_id'];
			unset( $sanitized_post_data['global_id'] );
		}

		unset( $sanitized_post_data['slug'] );
		unset( $sanitized_post_data['current_step'] );
		unset( $sanitized_post_data['step'] );
		unset( $sanitized_post_data['module_id'] );
		unset( $sanitized_post_data['module_type'] );
		unset( $sanitized_post_data['is_submit'] );

		$get_settings_wizard = 'get_' . $module_type . '_settings_wizard';

		$wizard = $addon->$get_settings_wizard( $sanitized_post_data, $module_id, $current_step, $step );

		$this->send_json_success(
			'',
			$wizard
		);
	}//end module_settings()
}
