<?php
/**
 * Forminator Hubspot
 *
 * @package Forminator
 */

// Include addon-hubspot-wp-api.
require_once __DIR__ . '/lib/class-forminator-addon-hubspot-wp-api.php';

/**
 * Class Forminator_Hubspot
 * HubSpot Integration Main Class
 *
 * @since 1.0 HubSpot Integration
 */
final class Forminator_Hubspot extends Forminator_Integration {

	/**
	 * Forminator_Hubspot Instance
	 *
	 * @var self|null
	 */
	protected static $instance = null;

	/**
	 * Slug
	 *
	 * @var string
	 */
	protected $_slug = 'hubspot';

	/**
	 * Hubspot version
	 *
	 * @var string
	 */
	protected $_version = FORMINATOR_ADDON_HUBSPOT_VERSION;

	/**
	 * Min Forminator version
	 *
	 * @var string
	 */
	protected $_min_forminator_version = '1.1';

	/**
	 * Short title
	 *
	 * @var string
	 */
	protected $_short_title = 'HubSpot';

	/**
	 * Title
	 *
	 * @var string
	 */
	protected $_title = 'HubSpot';

	/**
	 * Token
	 *
	 * @var string
	 */
	private $_token = '';

	/**
	 * Error message
	 *
	 * @var string
	 */
	private $_auth_error_message = '';

	const TARGET_TYPE_PUBLIC_CHANNEL  = 'public_channel';
	const TARGET_TYPE_PRIVATE_CHANNEL = 'private_channel';
	const TARGET_TYPE_DIRECT_MESSAGE  = 'direct_message';

	/**
	 * Position
	 *
	 * @var int
	 */
	protected $_position = 4;

	/**
	 * Forminator_Hubspot constructor.
	 *
	 * @since 1.0 HubSpot Integration
	 */
	public function __construct() {
		// late init to allow translation.
		$this->_description = esc_html__( 'Get awesome by your form.', 'forminator' );

		$this->is_multi_global = true;

		$this->global_id_for_new_integrations = uniqid( '', true );
		add_action( 'wp_ajax_forminator_hubspot_support_request', array( $this, 'hubspot_support_request' ) );

		add_action( 'forminator_after_activated_addons_removed', array( $this, 'clear_db' ), 10, 2 );
	}

	/**
	 * Clear Database
	 *
	 * @param string $slug Slug.
	 * @param object $addon Integration.
	 */
	public function clear_db( $slug, $addon ) {
		if ( $this->_slug === $slug ) {
			$api = $addon->get_api( 'any_random_token' );
			$api->clear_db();
		}
	}

	/**
	 * Override settings available,
	 *
	 * @since 1.0 HubSpot Integration
	 * @return bool
	 */
	public function is_settings_available() {
		return true;
	}

	/**
	 * Allow multiple connection on one form
	 *
	 * @since 1.0 HubSpot Integration
	 * @return bool
	 */
	public function is_allow_multi_on_form() {
		return true;
	}

	/**
	 * Settings wizard
	 *
	 * @since 1.0 HubSpot Integration
	 * @return array
	 */
	public function settings_wizards() {
		return array(
			array(
				'callback'     => array( $this, 'authorize_access' ),
				'is_completed' => array( $this, 'authorize_access_is_completed' ),
			),
			array(
				'callback'     => array( $this, 'wait_authorize_access' ),
				'is_completed' => array( $this, 'is_authorized' ),
			),
		);
	}

	/**
	 * Authorize Access wizard
	 *
	 * @return array
	 * @throws Forminator_Integration_Exception Throws Integration Exception.
	 */
	public function authorize_access() {

		$template = forminator_addon_hubspot_dir() . 'views/settings/authorize.php';

		$buttons = array();
		if ( $this->is_connected() ) {
			$buttons['disconnect'] = array(
				'markup' => self::get_button_markup( esc_html__( 'DISCONNECT', 'forminator' ), 'sui-button-ghost forminator-addon-disconnect forminator-integration-popup__close' ),
			);

			$setting_values  = $this->get_settings_values();
			$template_params = array(
				'auth_url' => $this->get_auth_url(),
				'token'    => $this->_token,
				'user'     => isset( $setting_values['user'] ) ? $setting_values['user'] : '',
			);
		} else {
			// Force save empty settings.
			$template_params = array(
				'auth_url' => $this->get_auth_url(),
				'token'    => '',
				'user'     => '',
			);
		}

		return array(
			'html'       => self::get_template( $template, $template_params ),
			'buttons'    => $buttons,
			'redirect'   => false,
			'has_errors' => false,
		);
	}

	/**
	 * Authorize access is completed
	 *
	 * @return bool
	 */
	public function authorize_access_is_completed() {
		return true;
	}

	/**
	 * Wait Authorize Access wizard
	 *
	 * @since 1.0 HubSpot Integration
	 * @return array
	 */
	public function wait_authorize_access() {
		$template       = forminator_addon_hubspot_dir() . 'views/settings/wait-authorize.php';
		$template_error = forminator_addon_hubspot_dir() . 'views/settings/error-authorize.php';

		$is_poll = false;

		$setting_values = $this->get_settings_values();

		$template_params = array(
			'token'    => $this->_token,
			'auth_url' => $this->get_auth_url(),
			'user'     => $setting_values['user'] ?? '',
		);

		$has_errors = false;

		if ( $this->_token ) {
			$html = $this->success_authorize();
		} elseif ( $this->_auth_error_message ) {
			$template_params['error_message'] = $this->_auth_error_message;
			$has_errors                       = true;

			// reset err msg.
			if ( $this->_auth_error_message ) {
				unset( $setting_values['auth_error_message'] );
				$this->save_settings_values( $setting_values );
				$this->_auth_error_message = '';
			}

			$html = self::get_template( $template_error, $template_params );
		} else {
			$is_poll = true;
			$html    = self::get_template( $template, $template_params );
		}

		return array(
			'html'       => $html,
			'is_poll'    => $is_poll,
			'redirect'   => false,
			'has_errors' => $has_errors,
		);
	}

	/**
	 * Authorized Callback
	 *
	 * @since 1.0 HubSpot Integration
	 * @return bool
	 */
	public function is_authorized() {
		$setting_values = $this->get_settings_values();

		return ! empty( $setting_values['token'] );
	}

	/**
	 * Get Access Token
	 *
	 * @since 1.0 HubSpot Integration
	 * @return string
	 */
	public function get_client_access_token() {
		$settings_values = $this->get_settings_values();
		$token           = '';
		if ( isset( $settings_values ['token'] ) ) {
			$token = $settings_values ['token'];
		}

		/**
		 * Filter access_token used
		 *
		 * @since 1.2
		 *
		 * @param string $token Token.
		 */
		$token = apply_filters( 'forminator_addon_hubspot_client_access_token', $token );

		return $token;
	}

	/**
	 * Register a page for redirect url of HubSpot auth
	 *
	 * @since 1.0 HubSpot Integration
	 *
	 * @return array
	 */
	public function register_integration_sections() {
		return array(
			'authorize' => array( $this, 'authorize_page_callback' ),
		);
	}

	/**
	 * Flag if delete member on delete entry enabled
	 *
	 * Default is `true`,
	 * which can be changed via `FORMINATOR_ADDON_HUBSPOT_ENABLE_DELETE_MEMBER` constant
	 *
	 * @return bool
	 */
	public static function is_enable_delete_member() {
		if ( defined( 'FORMINATOR_ADDON_HUBSPOT_ENABLE_DELETE_MEMBER' ) && FORMINATOR_ADDON_HUBSPOT_ENABLE_DELETE_MEMBER ) {
			return true;
		}

		return false;
	}

	/**
	 * Prepare redirect URL to wpmudev.com
	 *
	 * @return string
	 */
	private static function prepare_redirect_url() {
		return self::redirect_uri(
			'hubspot',
			'authorize',
			array(
				'client_id' => Forminator_Hubspot_Wp_Api::CLIENT_ID,
			)
		);
	}

	/**
	 * Get Auth Url
	 *
	 * @return string
	 */
	public function get_auth_url() {
		$base_authorize_url = Forminator_Hubspot_Wp_Api::AUTHORIZE_URL;
		$client_id          = Forminator_Hubspot_Wp_Api::CLIENT_ID;
		$final_redirect_url = rawurlencode( forminator_addon_integration_section_admin_url( $this, 'authorize', false ) );
		$redirect_url       = self::prepare_redirect_url();
		$scopes             = Forminator_Hubspot_Wp_Api::$oauth_scopes;

		/**
		 * Filter OAuth Scopes
		 *
		 * @since 1.3
		 *
		 * @param array $scopes
		 */
		$scopes = apply_filters( 'forminator_addon_hubspot_oauth_scopes', $scopes );

		$auth_url = add_query_arg(
			array(
				'client_id'    => $client_id,
				'scope'        => rawurlencode( $scopes ),
				'redirect_uri' => rawurlencode( $redirect_url ),
				'state'        => rawurlencode( self::get_nonce_value() . '|' . $final_redirect_url ),
			),
			$base_authorize_url
		);

		/**
		 * Filter HubSpot Auth Url
		 *
		 * @since 1.3
		 *
		 * @param string $auth_url
		 * @param string $base_authorize_url
		 * @param string $client_id
		 * @param array $scopes
		 * @param string $redirect_url
		 */
		$auth_url = apply_filters( 'forminator_addon_hubspot_auth_url', $auth_url, $base_authorize_url, $client_id, $scopes, $redirect_url );

		return $auth_url;
	}

	/**
	 * HubSpot Authorize Page
	 *
	 * @since 1.0 HubSpot Integration
	 *
	 * @param array $query_args Query Arguments.
	 *
	 * @return string
	 * @throws Forminator_Integration_Exception Throws Integration Exception.
	 */
	public function authorize_page_callback( $query_args ) {
		$settings        = $this->get_settings_values();
		$template        = forminator_addon_hubspot_dir() . 'views/sections/authorize.php';
		$template_params = array(
			'error_message' => '',
			'is_close'      => false,
		);

		if ( isset( $query_args['code'] ) && self::validate_callback_request( 'hubspot' ) ) {
			try {
				$code       = $query_args['code'];
				$token      = '';
				$identifier = ! empty( $query_args['identifier'] ) ? $query_args['identifier'] : '';

				$this->multi_global_id = ! empty( $query_args['global_id'] ) ? $query_args['global_id'] : uniqid( '', true );

				// prefer new instance.
				$final_redirect_url = forminator_addon_integration_section_admin_url( $this, 'authorize', false, $identifier );

				$api           = Forminator_Hubspot_Wp_Api::get_instance( uniqid(), $this->multi_global_id );
				$redirect_uri  = self::prepare_redirect_url();
				$args          = array(
					'code'         => $code,
					'redirect_uri' => rawurlencode( $redirect_uri ),
					'state'        => rawurlencode( self::get_nonce_value() . '|' . $final_redirect_url ),
				);
				$token_request = $api->get_access_token( $args );

				if ( isset( $token_request->access_token ) ) {
					$token = $token_request->access_token;
				}

				if ( empty( $token ) ) {
					throw new Forminator_Integration_Exception( esc_html__( 'Failed to get token', 'forminator' ) );
				}

				if ( ! $this->is_active() ) {
					$activated = Forminator_Integration_Loader::get_instance()->activate_addon( $this->_slug );
					if ( ! $activated ) {
						$last_message = Forminator_Integration_Loader::get_instance()->get_last_error_message();
						throw new Forminator_Integration_Exception( $last_message );
					}
				}
				$user = $api->get_access_token_information();

				$settings['token']        = $token;
				$settings['user']         = $user;
				$settings['identifier']   = $identifier;
				$settings['re-authorize'] = 'ticket';
				$this->save_settings_values( $settings );
				$template_params['is_close'] = true;
			} catch ( Exception $e ) {
				// catch all exception.
				$template_params['error_message'] = $e->getMessage();
			}
		} else {
			$template_params['error_message'] = esc_html__( 'Failed to get authorization code.', 'forminator' );
			// todo : translate $query_args[error].
			$settings['auth_error_message'] = $template_params['error_message'];
			$this->save_settings_values( $settings );
			$template_params['is_close'] = true;
		}

		return self::get_template( $template, $template_params );
	}

	/**
	 * Get API Instance
	 *
	 * @param string|null $access_token Access token.
	 *
	 * @return Forminator_Hubspot_Wp_Api|null
	 * @throws Forminator_Integration_Exception Throws Integration Exception.
	 */
	public function get_api( $access_token = null ) {
		if ( is_null( $access_token ) ) {
			$access_token = $this->get_client_access_token();
		}

		$api = Forminator_Hubspot_Wp_Api::get_instance( $access_token, $this->multi_global_id );
		return $api;
	}

	/**
	 * Before get Setting Values
	 *
	 * @since 1.0 HubSpot Integration
	 *
	 * @param array $values Setting values.
	 *
	 * @return mixed
	 */
	public function before_get_settings_values( $values ) {
		if ( isset( $values['token'] ) ) {
			$this->_token = $values['token'];
		}

		if ( isset( $values['auth_error_message'] ) ) {
			$this->_auth_error_message = $values['auth_error_message'];
		}

		return $values;
	}

	/**
	 * Support Request Ajax
	 *
	 * @throws Forminator_Integration_Exception Throws Integration Exception.
	 */
	public function hubspot_support_request() {
		forminator_validate_ajax( 'forminator_hubspot_request', false, 'forminator-integrations' );

		$status   = array();
		$pipeline = Forminator_Core::sanitize_text_field( 'value' );
		try {
			$api              = $this->get_api();
			$pipeline_request = $api->get_pipeline();
			if ( empty( $pipeline_request ) ) {
				throw new Exception( esc_html__( 'Pipeline can not be empty.', 'forminator' ) );
			}
			if ( ! empty( $pipeline_request->results ) ) {
				foreach ( $pipeline_request->results as $key => $data ) {
					if ( isset( $data->pipelineId ) && $pipeline === $data->pipelineId ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
						foreach ( $data->stages as $stages => $stage ) {
							if ( isset( $stage->stageId ) && isset( $stage->label ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
								$status[ $stage->stageId ] = $stage->label; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
							}
						}
					}
				}
			}
			wp_send_json_success( $status );
		} catch ( Forminator_Integration_Exception $e ) {
			wp_send_json_error( $e->getMessage() );
		}
	}

	/**
	 * Allow multiple connection on one quiz
	 *
	 * @since 1.6.1
	 * @return bool
	 */
	public function is_allow_multi_on_quiz() {
		return true;
	}
}
