<?php
/**
 * Forminator Addon Trello
 *
 * @package Forminator
 */

// Include addon-trello-wp-api.
require_once __DIR__ . '/lib/class-forminator-addon-trello-wp-api.php';

/**
 * Class Forminator_Trello
 * Trello Integration Main Class
 *
 * @since 1.0 Trello Integration
 */
final class Forminator_Trello extends Forminator_Integration {

	/**
	 * Forminator_Trello Instance
	 *
	 * @var self|null
	 */
	protected static $instance = null;

	/**
	 * Slug
	 *
	 * @var string
	 */
	protected $_slug = 'trello';

	/**
	 * Trello version
	 *
	 * @var string
	 */
	protected $_version = FORMINATOR_ADDON_TRELLO_VERSION;

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
	protected $_short_title = 'Trello';

	/**
	 * Title
	 *
	 * @var string
	 */
	protected $_title = 'Trello';

	/**
	 * Position
	 *
	 * @var int
	 */
	protected $_position = 5;

	const CARD_DELETE_MODE_DELETE = 'delete';
	const CARD_DELETE_MODE_CLOSED = 'closed';

	/**
	 * Card delete modes
	 *
	 * @var array
	 */
	private static $card_delete_modes
		= array(
			self::CARD_DELETE_MODE_DELETE,
			self::CARD_DELETE_MODE_CLOSED,
		);

	/**
	 * Connected Account Info
	 *
	 * @var array
	 */
	private $connected_account = array();

	/**
	 * Current Token
	 *
	 * @var string
	 */
	private $_token = '';

	/**
	 * API key
	 *
	 * @var string
	 */
	private $_app_key = '';

	/**
	 * Identifier
	 *
	 * @var string
	 */
	private $identifier = '';

	/**
	 * Sensitive key names that require encryption.
	 *
	 * @var array
	 */
	protected $_sensitive_keys = array( 'api_key' );

	/**
	 * Forminator_Trello constructor.
	 *
	 * @since 1.0 Trello Integration
	 */
	public function __construct() {
		// late init to allow translation.
		$this->_description = esc_html__( 'Get awesome by your form.', 'forminator' );

		$this->is_multi_global = true;

		$this->global_id_for_new_integrations = uniqid( '', true );
	}

	/**
	 * Override settings available,
	 *
	 * @since 1.0 Trello Integration
	 * @return bool
	 */
	public function is_settings_available() {
		return true;
	}

	/**
	 * Flag enable delete card before delete entries
	 *
	 * Its disabled by default
	 *
	 * @since 1.0 Trello Integration
	 * @return bool
	 */
	public static function is_enable_delete_card() {
		$enable_delete_card = ( defined( 'FORMINATOR_ADDON_TRELLO_ENABLE_DELETE_CARD' ) && FORMINATOR_ADDON_TRELLO_ENABLE_DELETE_CARD );

		/**
		 * Filter Flag enable delete card before delete entries
		 *
		 * @since  1.2
		 *
		 * @params bool $enable_delete_card
		 */
		$enable_delete_card = apply_filters( 'forminator_addon_trello_delete_card', $enable_delete_card );

		return $enable_delete_card;
	}

	/**
	 * Get Delete mode for card
	 *
	 * Acceptable values : 'delete', 'closed'
	 * default is 'delete'
	 *
	 * @see   Forminator_Trello::is_enable_delete_card()
	 *
	 * @since 1.0 Trello Integration
	 * @return string
	 */
	public static function get_card_delete_mode() {
		$card_delete_mode = self::CARD_DELETE_MODE_DELETE;

		if ( defined( 'FORMINATOR_ADDON_TRELLO_CARD_DELETE_MODE' ) ) {
			$card_delete_mode = FORMINATOR_ADDON_TRELLO_CARD_DELETE_MODE;
		}

		/**
		 * Filter delete mode for card
		 *
		 * @since  1.2
		 *
		 * @params string $card_delete_mode
		 */
		$card_delete_mode = apply_filters( 'forminator_addon_trello_card_delete_mode', $card_delete_mode );

		// fallback to delete.
		if ( ! in_array( $card_delete_mode, self::get_card_delete_modes(), true ) ) {
			$card_delete_mode = self::CARD_DELETE_MODE_DELETE;
		}

		return $card_delete_mode;
	}

	/**
	 * Allow multiple connection on one form
	 *
	 * @since 1.0 Trello Integration
	 * @return bool
	 */
	public function is_allow_multi_on_form() {
		return true;
	}

	/**
	 * Get Trello APP key
	 *
	 * @see   https://trello.com/app-key
	 *
	 * @since 1.0 Trello Integration
	 *
	 * @return string;
	 */
	public function get_app_key() {
		$app_key = $this->_app_key;
		// check override by config constant.
		if ( defined( 'FORMINATOR_ADDON_TRELLO_APP_KEY' ) && FORMINATOR_ADDON_TRELLO_APP_KEY ) {
			$app_key = FORMINATOR_ADDON_TRELLO_APP_KEY;
		}

		/**
		 * Filter APP Key used for API request(s) of Trello
		 *
		 * @since 1.2
		 *
		 * @param string $app_key
		 */
		$app_key = apply_filters( 'forminator_addon_trello_app_key', $app_key );

		return $app_key;
	}

	/**
	 * Settings wizard
	 *
	 * @since 1.0 Trello Integration
	 * @return array
	 */
	public function settings_wizards() {
		return array(
			array(
				'callback'     => array( $this, 'setup_api_key' ),
				'is_completed' => array( $this, 'setup_api_key_is_completed' ),
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
	 * Setup API key
	 *
	 * @param array $submitted_data Submitted data.
	 *
	 * @return array
	 */
	public function setup_api_key( $submitted_data ) {
		$settings_values = $this->get_settings_values();
		$template        = forminator_addon_trello_dir() . 'views/settings/setup-api.php';

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
			'identifier'    => '',
			'token'         => $this->_token,
			'api_key'       => '',
			'api_key_error' => '',
			'error_message' => '',
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

		if ( empty( $template_params['api_key'] ) ) {
			$saved_api_key = $this->get_app_key();
			if ( ! empty( $saved_api_key ) ) {
				$template_params['api_key'] = $saved_api_key;
			}
		}

		if ( $is_submit ) {
			$api_key    = isset( $submitted_data['api_key'] ) ? $submitted_data['api_key'] : '';
			$identifier = isset( $submitted_data['identifier'] ) ? $submitted_data['identifier'] : '';

			if ( empty( $api_key ) ) {
				$template_params['api_key_error'] = esc_html__( 'Please input valid API Key', 'forminator' );
				$has_errors                       = true;
			}

			if ( ! $has_errors ) {
				$real_api_key = $this->get_real_value( $api_key, 'api_key' );
				// validate api.
				$this->_app_key   = $real_api_key;
				$this->identifier = $identifier;
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
	 * Setup API key is complete
	 *
	 * @param array $submitted_data Submitted data.
	 *
	 * @return bool
	 */
	public function setup_api_key_is_completed( $submitted_data ) {
		return true;
	}

	/**
	 * Authorize Access wizard
	 *
	 * @since 1.0 Trello Integration
	 * @return array
	 */
	public function authorize_access() {
		$template = forminator_addon_trello_dir() . 'views/settings/authorize.php';

		$buttons = array();
		if ( $this->is_connected() ) {
			$buttons['disconnect'] = array(
				'markup' => self::get_button_markup( esc_html__( 'DISCONNECT', 'forminator' ), 'sui-button-ghost forminator-addon-disconnect' ),
			);
		}

		$template_params = array(
			'connected_account' => $this->connected_account,
			'token'             => $this->_token,
			'auth_url'          => $this->get_auth_url(),
			'is_connected'      => $this->is_connected(),
		);

		return array(
			'html'       => self::get_template( $template, $template_params ),
			'buttons'    => $buttons,
			'redirect'   => false,
			'has_errors' => false,
		);
	}

	/**
	 * Wait Authorize Access wizard
	 *
	 * @since 1.0 Trello Integration
	 * @return array
	 */
	public function wait_authorize_access() {
		$template = forminator_addon_trello_dir() . 'views/settings/wait-authorize.php';

		$buttons = array();

		$is_poll = true;

		$template_params = array(
			'connected_account' => $this->connected_account,
			'token'             => $this->_token,
			'auth_url'          => $this->get_auth_url(),
		);

		if ( $this->_token ) {
			$is_poll = false;
			$html    = $this->success_authorize();
		} else {
			$html = self::get_template( $template, $template_params );
		}

		return array(
			'html'       => $html,
			'buttons'    => $buttons,
			'is_poll'    => $is_poll,
			'redirect'   => false,
			'has_errors' => false,
		);
	}

	/**
	 * Authorized Callback
	 *
	 * @since 1.0 Trello Integration
	 * @return bool
	 */
	public function is_authorized() {
		$setting_values = $this->get_settings_values();

		return ! empty( $setting_values['token'] );
	}

	/**
	 * Pseudo step
	 *
	 * @since 1.0 Trello Integration
	 * @return bool
	 */
	public function authorize_access_is_completed() {
		return true;
	}

	/**
	 * Get Connected Account
	 *
	 * @since 1.0 Trello Integration
	 *
	 * @return array
	 */
	public function get_connected_account() {
		return $this->connected_account;
	}

	/**
	 * Register a page for redirect url of trello auth
	 *
	 * @since 1.0 Trello Integration
	 *
	 * @return array
	 */
	public function register_integration_sections() {
		return array(
			'authorize' => array( $this, 'authorize_page_callback' ),
		);
	}

	/**
	 * Trello Authorize Page
	 *
	 * @since 1.0 Trello Integration
	 *
	 * @param array $query_args Arguments.
	 *
	 * @return string
	 * @throws Forminator_Integration_Exception Throws Integration Exception.
	 */
	public function authorize_page_callback( $query_args ) {
		$template        = forminator_addon_trello_dir() . 'views/sections/authorize.php';
		$template_params = array(
			'error_message' => '',
			'is_close'      => false,
		);

		if ( isset( $query_args['token'] ) ) {
			try {
				$identifier = ! empty( $query_args['identifier'] ) ? $query_args['identifier'] : '';
				if ( ! empty( $query_args['global_id'] ) ) {
					$this->multi_global_id = $query_args['global_id'];
				}
				$token     = $query_args['token'];
				$api_key   = $query_args['api_key'];
				$validated = $this->validate_token( $token, $api_key );
				if ( true !== $validated ) {
					throw new Forminator_Integration_Exception( $validated );
				}
				if ( ! $this->is_active() ) {
					$activated = Forminator_Integration_Loader::get_instance()->activate_addon( $this->_slug );
					if ( ! $activated ) {
						$last_message = Forminator_Integration_Loader::get_instance()->get_last_error_message();
						throw new Forminator_Integration_Exception( $last_message );
					}
				}
				$this->save_settings_values(
					array(
						'api_key'    => $api_key,
						'token'      => $token,
						'identifier' => $identifier,
					)
				);
				$template_params['is_close'] = true;
			} catch ( Forminator_Integration_Exception $e ) {
				$template_params['error_message'] = $e->getMessage();
			}
		}

		return self::get_template( $template, $template_params );
	}

	/**
	 * Get Trello Auth URL
	 *
	 * @since 1.1 Trello Integration
	 *
	 * @param string $return_url Return URL.
	 *
	 * @return string
	 */
	public function get_auth_url( $return_url = '' ) {
		$authorize_url = 'https://trello.com/1/authorize/';
		if ( ! $return_url ) {
			$return_url = forminator_addon_integration_section_admin_url( $this, 'authorize', true, $this->identifier );
			$return_url = add_query_arg( 'api_key', $this->get_app_key(), $return_url );
		}
		$return_url = rawurlencode( $return_url );
		// https://developers.trello.com/page/authorization.
		$auth_params = array(
			'callback_method' => 'fragment',
			'scope'           => 'read,write,account',
			'expiration'      => 'never',
			'name'            => esc_html__( 'Forminator Pro', 'forminator' ),
			'key'             => $this->get_app_key(),
			'response_type'   => 'token',
			'return_url'      => $return_url,
		);

		/**
		 * Filter params used to authorize user
		 *
		 * @since 1.2
		 *
		 * @param array $auth_params
		 */
		$auth_params = apply_filters( 'forminator_addon_trello_authorize_params', $auth_params );

		$authorize_url = add_query_arg( $auth_params, $authorize_url );

		return $authorize_url;
	}

	/**
	 * Validate token with trello API
	 *
	 * Using `members/me`
	 *
	 * @since 1.0 Trello Integration
	 *
	 * @param string $token Token.
	 * @param string $api_key API key.
	 *
	 * @return bool|string
	 * @throws Forminator_Integration_Exception Throws Integration Exception.
	 */
	public function validate_token( $token, $api_key ) {
		try {
			// ensure new instance.
			$api        = $this->get_api( $token, $api_key );
			$me_request = $api->get_( 'members/me/' );

			if ( ! isset( $me_request->id ) || empty( $me_request->id ) ) {
				throw new Forminator_Integration_Exception( esc_html__( 'Failed to acquire user ID.', 'forminator' ) );
			}

			if ( isset( $me_request->url ) ) {
				$this->connected_account['url'] = $me_request->url;
			}

			if ( isset( $me_request->email ) ) {
				$this->connected_account['email'] = $me_request->email;
			}

			$validated = true;

		} catch ( Forminator_Integration_Exception $e ) {
			$validated = $e->getMessage();
		}

		return $validated;
	}

	/**
	 * Get API
	 *
	 * @param string|null $token Token.
	 * @param string|null $api_key API Key.
	 *
	 * @return Forminator_Trello_Wp_Api
	 */
	public function get_api( $token = null, $api_key = null ) {
		if ( is_null( $token ) ) {
			$setting_values = $this->get_settings_values( true );
			if ( isset( $setting_values['token'] ) ) {
				$token = $setting_values['token'];
			}
			if ( isset( $setting_values['api_key'] ) ) {
				$api_key = $setting_values['api_key'];
			}
		}
		$api = new Forminator_Trello_Wp_Api( $api_key, $token );
		return $api;
	}

	/**
	 * Before get Setting Values
	 *
	 * @since 1.0 Slack Integration
	 *
	 * @param array $values Settings to save.
	 *
	 * @return mixed
	 */
	public function before_get_settings_values( $values ) {
		forminator_addon_maybe_log( __METHOD__, $values );
		if ( isset( $values['connected_account'] ) && ! empty( $values['connected_account'] ) && is_array( $values['connected_account'] ) ) {
			$this->connected_account = $values['connected_account'];
		}
		if ( isset( $values['token'] ) ) {
			$this->_token = $values['token'];
			forminator_addon_maybe_log( __METHOD__, $this->_token );
		}

		return $values;
	}

	/**
	 * Revoke token on Trello before deactivate
	 *
	 * @since 1.0 Trello Integration
	 * @return bool
	 */
	public function deactivate() {
		$this->connected_account = array();
		try {
			$api = $this->get_api();
			// revoke token from trello server.
			forminator_addon_maybe_log( __METHOD__, $this->_token );
			$api->delete_( 'tokens/' . $this->_token );
		} catch ( Forminator_Integration_Exception $e ) {
			forminator_addon_maybe_log( __METHOD__, $e->getMessage() );
			$this->_deactivation_error_message = $e->getMessage();

			return false;
		}

		return true;
	}

	/**
	 * Get available card delete modes
	 *
	 * @since 1.0 Trello Integration
	 * @return array
	 */
	public static function get_card_delete_modes() {
		$card_delete_modes = self::$card_delete_modes;

		/**
		 * Filter available delete modes for cards
		 *
		 * @since 1.2
		 *
		 * @param array $card_delete_modes
		 */
		$card_delete_modes = apply_filters( 'forminator_addon_trello_card_delete_modes', $card_delete_modes );

		return $card_delete_modes;
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
	 * @since 1.6.2
	 * @return bool
	 */
	public function is_allow_multi_on_quiz() {
		return true;
	}
}
