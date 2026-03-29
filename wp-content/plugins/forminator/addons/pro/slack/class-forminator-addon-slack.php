<?php
/**
 * Forminator Slack
 *
 * @package Forminator
 */

// Include addon-slack-wp-api.
require_once __DIR__ . '/lib/class-forminator-addon-slack-wp-api.php';

/**
 * Class Forminator_Slack
 * Slack Integration Main Class
 *
 * @since 1.0 Slack Integration
 */
final class Forminator_Slack extends Forminator_Integration {

	/**
	 * Forminator_Slack Instance
	 *
	 * @var self|null
	 */
	protected static $instance = null;

	/**
	 * Slug
	 *
	 * @var string
	 */
	protected $_slug = 'slack';

	/**
	 * Slack version
	 *
	 * @var string
	 */
	protected $_version = FORMINATOR_ADDON_SLACK_VERSION;

	/**
	 * Forminator minimum version
	 *
	 * @var string
	 */
	protected $_min_forminator_version = '1.1';

	/**
	 * Short title
	 *
	 * @var string
	 */
	protected $_short_title = 'Slack';

	/**
	 * Title
	 *
	 * @var string
	 */
	protected $_title = 'Slack';

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
	 * Slack API
	 *
	 * @var null|Forminator_Slack_Wp_Api
	 */
	private static $_api = null;

	/**
	 * Position
	 *
	 * @var int
	 */
	protected $_position = 4;

	/**
	 * Sensitive key names that require encryption.
	 *
	 * @var array
	 */
	protected $_sensitive_keys = array( 'client_secret' );

	/**
	 * Forminator_Slack constructor.
	 *
	 * @since 1.0 Slack Integration
	 */
	public function __construct() {
		// late init to allow translation.
		$this->_description = esc_html__( 'Get awesome by your form.', 'forminator' );

		add_filter( 'forminator_addon_slack_api_request_headers', array( $this, 'default_filter_api_headers' ), 1, 4 );
	}

	/**
	 * Override settings available,
	 *
	 * @since 1.0 Slack Integration
	 * @return bool
	 */
	public function is_settings_available() {
		return true;
	}

	/**
	 * Flag to enable delete chat
	 *
	 * @since 1.0 Slack Integration
	 * @return bool
	 */
	public static function enable_delete_chat() {
		$enable_delete_chat = false;
		if ( defined( 'FORMINATOR_ADDON_SLACK_ENABLE_DELETE_CHAT' ) && FORMINATOR_ADDON_SLACK_ENABLE_DELETE_CHAT ) {
			$enable_delete_chat = true;
		}

		/**
		 * Filter Flag to enable delete chat
		 *
		 * @since  1.4
		 *
		 * @params bool $enable_delete_chat
		 */
		$enable_delete_chat = apply_filters( 'forminator_addon_slack_enable_delete_chat', $enable_delete_chat );

		return $enable_delete_chat;
	}

	/**
	 * Allow multiple connection on one form
	 *
	 * @since 1.0 Slack Integration
	 * @return bool
	 */
	public function is_allow_multi_on_form() {
		return true;
	}

	/**
	 * Settings wizard
	 *
	 * @since 1.0 Slack Integration
	 * @return array
	 */
	public function settings_wizards() {
		return array(
			array(
				'callback'     => array( $this, 'setup_client_id' ),
				'is_completed' => array( $this, 'setup_client_id_is_completed' ),
			),
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
	 * @since 1.0 Slack Integration
	 *
	 * @param array $submitted_data Submitted Data.
	 *
	 * @return array
	 */
	public function setup_client_id( $submitted_data ) {
		$settings_values = $this->get_settings_values();
		$template        = forminator_addon_slack_dir() . 'views/settings/setup-client.php';

		$buttons = array();
		if ( $this->is_connected() ) {
			$buttons['disconnect']     = array(
				'markup' => self::get_button_markup( esc_html__( 'Disconnect', 'forminator' ), 'sui-button-ghost forminator-addon-disconnect' ),
			);
			$buttons['next']['markup'] = '<div class="sui-actions-right">' .
										self::get_button_markup( esc_html__( 'RE-AUTHORIZE', 'forminator' ), 'forminator-addon-next' ) .
										'</div>';
		} else {
			$buttons['next']['markup'] = '<div class="sui-actions-right">' .
										self::get_button_markup( esc_html__( 'Next', 'forminator' ), 'forminator-addon-next' ) .
										'</div>';
		}

		$template_params = array(
			'token'               => $this->_token,
			'client_id'           => '',
			'client_id_error'     => '',
			'client_secret'       => '',
			'client_secret_error' => '',
			'error_message'       => '',
			'redirect_url'        => forminator_addon_integration_section_admin_url( $this, 'authorize', false ),
		);

		$has_errors = false;
		$is_submit  = ! empty( $submitted_data );

		foreach ( $template_params as $key => $value ) {
			if ( isset( $submitted_data[ $key ] ) ) {
				$template_params[ $key ] = $submitted_data[ $key ];
			} elseif ( isset( $settings_values[ $key ] ) ) {
				$template_params[ $key ] = $settings_values[ $key ];
			}
		}

		if ( empty( $template_params['client_id'] ) ) {
			$saved_client_id = $this->get_client_id();
			if ( ! empty( $saved_client_id ) ) {
				$template_params['client_id'] = $saved_client_id;
			}
		}

		if ( empty( $template_params['client_secret'] ) ) {
			$saved_client_secret = $this->get_client_secret();

			if ( ! empty( $saved_client_secret ) ) {
				$template_params['client_secret'] = $saved_client_secret;
			}
		}

		if ( $is_submit ) {
			$client_id     = isset( $submitted_data['client_id'] ) ? $submitted_data['client_id'] : '';
			$client_secret = isset( $submitted_data['client_secret'] ) ? $submitted_data['client_secret'] : '';

			if ( empty( $client_id ) ) {
				$template_params['client_id_error'] = esc_html__( 'Please input valid Client ID', 'forminator' );
				$has_errors                         = true;
			}

			if ( empty( $client_secret ) ) {
				$template_params['client_secret_error'] = esc_html__( 'Please input valid Client Secret', 'forminator' );
				$has_errors                             = true;
			}

			if ( ! $has_errors ) {
				// validate api.
				try {
					$real_client_secret = $this->get_real_value( $client_secret, 'client_secret' );
					if ( $this->get_client_id() !== $client_id || $this->get_client_secret() !== $real_client_secret ) {
						// reset connection!
						$settings_values = array();
					}
					$settings_values['client_id']     = $client_id;
					$settings_values['client_secret'] = $real_client_secret;

					$this->save_settings_values( $settings_values );

				} catch ( Forminator_Integration_Exception $e ) {
					$template_params['error_message'] = $e->getMessage();
					$has_errors                       = true;
				}
			}
		}

		return array(
			'html'       => self::get_template( $template, $template_params ),
			'buttons'    => $buttons,
			'redirect'   => false,
			'has_errors' => $has_errors,
			'size'       => 'normal',
		);
	}

	/**
	 * Setup client id is complete
	 *
	 * @param array $submitted_data Submitted Data.
	 *
	 * @return bool
	 */
	public function setup_client_id_is_completed( $submitted_data ) {
		$client_id     = $this->get_client_id();
		$client_secret = $this->get_client_secret();

		if ( ! empty( $client_id ) && ! empty( $client_secret ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Authorize Access wizard
	 *
	 * @since 1.0 Slack Integration
	 * @return array
	 */
	public function authorize_access() {

		$template = forminator_addon_slack_dir() . 'views/settings/authorize.php';

		$buttons = array();
		if ( $this->is_connected() ) {
			$buttons['disconnect'] = array(
				'markup' => self::get_button_markup( esc_html__( 'DISCONNECT', 'forminator' ), 'sui-button-ghost forminator-addon-disconnect' ),
			);
		}

		$template_params = array(
			'auth_url' => $this->get_auth_url(),
			'token'    => $this->_token,
		);

		return array(
			'html'       => self::get_template( $template, $template_params ),
			'buttons'    => $buttons,
			'redirect'   => false,
			'has_errors' => false,
		);
	}

	/**
	 * Authorize access is completed.
	 *
	 * @return bool
	 */
	public function authorize_access_is_completed() {
		return true;
	}

	/**
	 * Wait Authorize Access wizard
	 *
	 * @since 1.0 Slack Integration
	 * @return array
	 */
	public function wait_authorize_access() {
		$template       = forminator_addon_slack_dir() . 'views/settings/wait-authorize.php';
		$template_error = forminator_addon_slack_dir() . 'views/settings/error-authorize.php';
		$token          = $this->get_client_access_token();
		$is_poll        = false;

		$template_params = array(
			'token'    => $token,
			'auth_url' => $this->get_auth_url(),
		);

		$has_errors = false;

		if ( $token ) {
			$html = $this->success_authorize();
		} elseif ( $this->_auth_error_message ) {
			$template_params['error_message'] = $this->_auth_error_message;
			$has_errors                       = true;

			$setting_values = $this->get_settings_values();
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
	 * @since 1.0 Slack Integration
	 * @return bool
	 */
	public function is_authorized() {
		$setting_values = $this->get_settings_values();

		return ! empty( $setting_values['token'] );
	}

	/**
	 * Get Client ID
	 *
	 * @since 1.0 Slack Integration
	 * @return string
	 */
	public function get_client_id() {
		$settings_values = $this->get_settings_values();
		$client_id       = '';
		if ( isset( $settings_values ['client_id'] ) ) {
			$client_id = $settings_values ['client_id'];
		} else {
			$settings = $this->get_slack_settings();

			if ( isset( $settings['client_id'] ) ) {
				$client_id = $settings['client_id'];
			}
		}

		/**
		 * Filter client id used
		 *
		 * @since 1.2
		 *
		 * @param string $client_id
		 */
		$client_id = apply_filters( 'forminator_addon_slack_client_id', $client_id );

		return $client_id;
	}

	/**
	 * Get Client secret
	 *
	 * @since 1.0 Slack Integration
	 * @return string
	 */
	public function get_client_secret() {
		$settings_values = $this->get_settings_values( true );
		$client_secret   = '';
		if ( isset( $settings_values ['client_secret'] ) ) {
			$client_secret = $settings_values ['client_secret'];
		} else {
			$settings = $this->get_slack_settings();
			// Decrypt client_secret if it has been encrypted.
			$settings = $this->decrypt_sensitive_data( $settings );
			if ( isset( $settings['client_secret'] ) ) {
				$client_secret = $settings['client_secret'];
			}
		}

		/**
		 * Filter client secret used
		 *
		 * @since 1.2
		 *
		 * @param string $client_secret
		 */
		$client_secret = apply_filters( 'forminator_addon_slack_client_secret', $client_secret );

		return $client_secret;
	}

	/**
	 * Get Access Token
	 *
	 * @since 1.0 Slack Integration
	 * @return string
	 */
	public function get_client_access_token() {
		$settings_values = $this->get_settings_values();
		$token           = '';
		if ( isset( $settings_values ['token'] ) ) {
			$token = $settings_values ['token'];
		} else {
			$settings = $this->get_slack_settings();

			if ( isset( $settings['token'] ) ) {
				$token = $settings['token'];
			}
		}

		/**
		 * Filter access_token used
		 *
		 * @since 1.2
		 *
		 * @param string $token
		 */
		$token = apply_filters( 'forminator_addon_slack_client_access_token', $token );

		return $token;
	}

	/**
	 * Get slack settings while app is being connected
	 *
	 * @since 1.18 Slack Integration
	 *
	 * @return array
	 */
	public function get_slack_settings() {
		$settings = get_option( 'forminator_addon_slack_settings' );
		if ( ! empty( $settings ) ) {
			$slice_settings = array_slice( $settings, 0, 1 );
			$settings       = array_shift( $slice_settings );
		}

		return $settings;
	}

	/**
	 * Register a page for redirect url of Slack auth
	 *
	 * @since 1.0 Slack Integration
	 *
	 * @return array
	 */
	public function register_integration_sections() {
		return array(
			'authorize' => array( $this, 'authorize_page_callback' ),
		);
	}

	/**
	 * Get Auth Url
	 *
	 * @return string
	 */
	public function get_auth_url() {
		$base_authorize_url = Forminator_Slack_Wp_Api::AUTHORIZE_URL;
		$client_id          = $this->get_client_id();
		$redirect_url       = rawurlencode( forminator_addon_integration_section_admin_url( $this, 'authorize', false ) );
		$scopes             = Forminator_Slack_Wp_Api::$oauth_scopes;

		/**
		 * Filter OAuth Scopes
		 *
		 * @since 1.3
		 *
		 * @param array $scopes
		 */
		$scopes = apply_filters( 'forminator_addon_slack_oauth_scopes', $scopes );

		$auth_url = add_query_arg(
			array(
				'client_id'    => $client_id,
				'scope'        => implode( ',', $scopes ),
				'redirect_uri' => $redirect_url,
			),
			$base_authorize_url
		);

		/**
		 * Filter Slack Auth Url
		 *
		 * @since 1.3
		 *
		 * @param string $auth_url
		 * @param string $base_authorize_url
		 * @param string $client_id
		 * @param array  $scopes
		 * @param string $redirect_url
		 */
		$auth_url = apply_filters( 'forminator_addon_slack_auth_url', $auth_url, $base_authorize_url, $client_id, $scopes, $redirect_url );

		return $auth_url;
	}

	/**
	 * Slack Authorize Page
	 *
	 * @since 1.0 Slack Integration
	 *
	 * @param array $query_args Arguments.
	 *
	 * @return string
	 * @throws Forminator_Integration_Exception Throws Integration Exception.
	 */
	public function authorize_page_callback( $query_args ) {
		$settings        = $this->get_settings_values( true );
		$template        = forminator_addon_slack_dir() . 'views/sections/authorize.php';
		$template_params = array(
			'error_message' => '',
			'is_close'      => false,
		);

		if ( isset( $query_args['code'] ) ) {
			try {
				$code  = $query_args['code'];
				$token = '';

				// prefer new instance.
				$api           = Forminator_Slack_Wp_Api::get_instance( uniqid() );
				$redirect_uri  = forminator_addon_integration_section_admin_url( $this, 'authorize', false );
				$token_request = $api->get_access_token( $code, $redirect_uri );

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

				$settings['token'] = $token;
				$this->save_settings_values( $settings );
				$template_params['is_close'] = true;
			} catch ( Forminator_Integration_Exception $e ) {
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
	 * @since 1.0 Slack Integration
	 *
	 * @param null|string $access_token Access token.
	 *
	 * @return Forminator_Slack_Wp_Api|null
	 */
	public function get_api( $access_token = null ) {
		if ( is_null( self::$_api ) ) {
			if ( is_null( $access_token ) ) {
				$access_token = $this->get_client_access_token();
			}

			$api        = Forminator_Slack_Wp_Api::get_instance( $access_token );
			self::$_api = $api;
		}

		return self::$_api;
	}

	/**
	 * Before get Setting Values
	 *
	 * @since 1.0 Slack Integration
	 *
	 * @param array $values Setting to save.
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
	 * Default filter for header
	 *
	 * Its add / change Authorization header
	 * - on get access token it uses Basic realm of encoded client id and secret
	 * - on web API request it uses Bearer realm of access token which default of @see Forminator_Slack_Wp_Api
	 *
	 * @since 1.0 Slack Integration
	 *
	 * @param array  $headers Headers.
	 * @param string $verb HTTP request type.
	 * @param string $path Request path.
	 * @param array  $args Arguments.
	 *
	 * @return array
	 */
	public function default_filter_api_headers( $headers, $verb, $path, $args ) {
		if ( false !== stripos( $path, 'oauth.access' ) ) {
			$encoded_auth             = base64_encode( $this->get_client_id() . ':' . $this->get_client_secret() ); //phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			$headers['Authorization'] = 'Basic ' . $encoded_auth;
			unset( $headers['Content-Type'] );
		}

		return $headers;
	}

	/**
	 * Allow multiple connection on one poll
	 *
	 * @since 1.6.1
	 * @return bool
	 */
	public function is_allow_multi_on_poll() {
		return true;
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
