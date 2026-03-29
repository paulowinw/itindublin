<?php
/**
 * The Forminator Aweber Integration.
 *
 * @package Forminator
 */

// Include forminator-addon-aweber-wp-api.
require_once __DIR__ . '/lib/class-forminator-addon-aweber-wp-api.php';

/**
 * Class Forminator_Aweber
 * Aweber Integration Main Class
 *
 * @since 1.0 Aweber Integration
 */
final class Forminator_Aweber extends Forminator_Integration {

	/**
	 * Forminator_Aweber Instance
	 *
	 * @var Forminator_Aweber|null
	 */
	protected static $instance = null;

	/**
	 * Slug
	 *
	 * @var string
	 */
	protected $_slug = 'aweber';

	/**
	 * Addon Version
	 *
	 * @var string
	 */
	protected $_version = FORMINATOR_ADDON_AWEBER_VERSION;

	/**
	 * Forminator Version
	 *
	 * @var string
	 */
	protected $_min_forminator_version = '1.1';

	/**
	 * Short title
	 *
	 * @var string
	 */
	protected $_short_title = 'AWeber';

	/**
	 * Title
	 *
	 * @var string
	 */
	protected $_title = 'AWeber';

	/**
	 * Position
	 *
	 * @var int
	 */
	protected $_position = 7;

	/**
	 * App Id
	 *
	 * @var string
	 */
	private $_app_id = 'd806984a';

	/**
	 * Connected Account Info
	 *
	 * @var integer
	 */
	private $_account_id = 0;

	/**
	 * Forminator_Aweber constructor.
	 *
	 * @since 1.0 Aweber Integration
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
	 * @since 1.0 Aweber Integration
	 * @return bool
	 */
	public function is_settings_available() {
		return true;
	}

	/**
	 * Allow multiple connection on one form
	 *
	 * @since 1.0 Aweber Integration
	 * @return bool
	 */
	public function is_allow_multi_on_form() {
		return true;
	}

	/**
	 * Settings wizard
	 *
	 * @since 1.0 AWeber Integration
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
	 * @since 1.0 AWeber Integration
	 * @return array
	 */
	public function authorize_access() {
		$template = forminator_addon_aweber_dir() . 'views/settings/authorize.php';

		$buttons = array();

		$template_params = array(
			'account_id'   => $this->_account_id,
			'auth_url'     => $this->get_auth_url(),
			'is_connected' => $this->is_connected(),
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
	 * @since 1.0 AWeber Integration
	 * @return array
	 */
	public function wait_authorize_access() {
		$template = forminator_addon_aweber_dir() . 'views/settings/wait-authorize.php';

		$is_poll = true;

		$template_params = array(
			'account_id' => $this->_account_id,
			'auth_url'   => $this->get_auth_url(),
		);

		if ( $this->_account_id ) {
			$is_poll = false;
			$html    = $this->success_authorize();
		} else {
			$html = self::get_template( $template, $template_params );
		}

		return array(
			'html'       => $html,
			'is_poll'    => $is_poll,
			'redirect'   => false,
			'has_errors' => false,
		);
	}

	/**
	 * Authorized Callback
	 *
	 * @since 1.0 AWeber Integration
	 * @return bool
	 */
	public function is_authorized() {
		$setting_values = $this->get_settings_values();

		// check account_id there.
		return ! empty( $setting_values['account_id'] );
	}

	/**
	 * Pseudo step
	 *
	 * @since 1.0 AWeber Integration
	 * @return bool
	 */
	public function authorize_access_is_completed() {
		return true;
	}

	/**
	 * Register a page for redirect url of AWeber auth
	 *
	 * @since 1.0 AWeber Integration
	 *
	 * @return array
	 */
	public function register_integration_sections() {
		return array(
			'authorize' => array( $this, 'authorize_page_callback' ),
		);
	}

	/**
	 * AWeber Authorize Page
	 *
	 * @since 1.0 AWeber Integration
	 *
	 * @param array $query_args Query arguments.
	 *
	 * @return string
	 * @throws Forminator_Integration_Exception Throws Integration Exception.
	 */
	public function authorize_page_callback( $query_args ) {
		$template        = forminator_addon_aweber_dir() . 'views/sections/authorize.php';
		$template_params = array(
			'error_message' => '',
			'is_close'      => false,
		);

		if ( isset( $query_args['authorization_code'] ) && ! empty( $query_args['authorization_code'] ) ) {
			try {
				$authorization_code = $query_args['authorization_code'];

				$identifier  = ! empty( $query_args['identifier'] ) ? $query_args['identifier'] : '';
				$split_codes = explode( '|', $authorization_code );

				// https://labs.aweber.com/docs/authentication#distributed-app
				// the authorization code is an application key, application secret, request token, token secret, and oauth_verifier, delimited by pipes (|).
				if ( ! is_array( $split_codes ) || 5 !== count( $split_codes ) ) {
					new Forminator_Integration_Exception( esc_html__( 'Invalid Authorization Code', 'forminator' ) );
				}

				$application_key    = $split_codes[0];
				$application_secret = $split_codes[1];
				$request_token      = $split_codes[2];
				$token_secret       = $split_codes[3];
				$oauth_verifier     = $split_codes[4];

				if ( ! empty( $query_args['global_id'] ) ) {
					$this->multi_global_id = $query_args['global_id'];
				}

				$api = $this->validate_access_token( $application_key, $application_secret, $request_token, $token_secret, $oauth_verifier );

				$this->_account_id = $this->get_validated_account_id( $api );
				if ( ! $this->is_active() ) {
					$activated = Forminator_Integration_Loader::get_instance()->activate_addon( $this->_slug );
					if ( ! $activated ) {
						$last_message = Forminator_Integration_Loader::get_instance()->get_last_error_message();
						throw new Forminator_Integration_Exception( $last_message );
					}
				}

				$this->save_settings_values(
					array(
						'identifier'         => $identifier,
						'application_key'    => $application_key,
						'application_secret' => $application_secret,
						'oauth_token'        => $api->get_oauth_token(),
						'oauth_token_secret' => $api->get_oauth_token_secret(),
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
	 * Get AWeber Auth URL
	 *
	 * @since 1.1 AWeber Integration
	 *
	 * @param string $return_url Return URL.
	 *
	 * @return string
	 */
	public function get_auth_url( $return_url = '' ) {
		$app_id = $this->get_app_id();

		$authorize_url = 'https://auth.aweber.com/1.0/oauth/authorize_app/' . trim( $app_id );

		if ( ! $return_url ) {
			$return_url = forminator_addon_integration_section_admin_url( $this, 'authorize', true );
		}
		$return_url = rawurlencode( $return_url );

		$auth_params = array(
			'oauth_callback' => $return_url, // un-official https://labs.aweber.com/getting_started/public#1.
		);

		/**
		 * Filter params used to authorize AWeber user
		 *
		 * @since 1.3
		 *
		 * @param array $auth_params
		 */
		$auth_params = apply_filters( 'forminator_addon_aweber_authorize_params', $auth_params );

		$authorize_url = add_query_arg( $auth_params, $authorize_url );

		return $authorize_url;
	}

	/**
	 * Get AWeber APP ID
	 *
	 * @see   https://labs.aweber.com/docs/authentication
	 *
	 * @since 1.0 AWeber Integration
	 *
	 * @return string;
	 */
	public function get_app_id() {
		$app_id = $this->_app_id;
		// check override by config constant.
		if ( defined( 'FORMINATOR_ADDON_AWEBER_APP_ID' ) && FORMINATOR_ADDON_AWEBER_APP_ID ) {
			$app_id = FORMINATOR_ADDON_AWEBER_APP_ID;
		}

		/**
		 * Filter APP ID used for API request(s) of AWeber
		 *
		 * @since 1.2
		 *
		 * @param string $app_id
		 */
		$app_id = apply_filters( 'forminator_addon_aweber_app_id', $app_id );

		return $app_id;
	}

	/**
	 * Get API Instance
	 *
	 * @since 1.0
	 *
	 * @param array|null $api_credentials API credentials.
	 *
	 * @return Forminator_Aweber_Wp_Api
	 * @throws Forminator_Integration_Exception Throws Integration Exceptions.
	 */
	public function get_api( $api_credentials = null ) {
		if ( is_null( $api_credentials ) ) {
			$api_credentials      = array();
			$setting_values       = $this->get_settings_values();
			$api_credentials_keys = array(
				'application_key',
				'application_secret',
				'oauth_token',
				'oauth_token_secret',
			);

			foreach ( $api_credentials_keys as $api_credentials_key ) {
				$api_credentials[ $api_credentials_key ] = isset( $setting_values[ $api_credentials_key ] ) ? $setting_values[ $api_credentials_key ] : '';
			}
		}

		$_application_key    = isset( $api_credentials['application_key'] ) ? $api_credentials['application_key'] : '';
		$_application_secret = isset( $api_credentials['application_secret'] ) ? $api_credentials['application_secret'] : '';
		$_oauth_token        = isset( $api_credentials['oauth_token'] ) ? $api_credentials['oauth_token'] : '';
		$_oauth_token_secret = isset( $api_credentials['oauth_token_secret'] ) ? $api_credentials['oauth_token_secret'] : '';

		return new Forminator_Aweber_Wp_Api( $_application_key, $_application_secret, $_oauth_token, $_oauth_token_secret );
	}

	/**
	 * Validate Access Token
	 *
	 * @param string $application_key Application Key.
	 * @param string $application_secret Application Secret.
	 * @param string $request_token Request Token.
	 * @param string $token_secret Secret Token.
	 * @param string $oauth_verifier Verifier.
	 *
	 * @throws Forminator_Integration_Exception Throws Integration Exceptions.
	 */
	public function validate_access_token( $application_key, $application_secret, $request_token, $token_secret, $oauth_verifier ) {
		// get access_token.
		$api           = $this->get_api(
			array(
				'application_key'    => $application_key,
				'application_secret' => $application_secret,
				'oauth_token'        => $request_token,
				'oauth_token_secret' => $token_secret,
			)
		);
		$access_tokens = $api->get_access_token( $oauth_verifier );

		return $this->get_api(
			array(
				'application_key'    => $application_key,
				'application_secret' => $application_secret,
				'oauth_token'        => $access_tokens->oauth_token,
				'oauth_token_secret' => $access_tokens->oauth_token_secret,
			)
		);
	}

	/**
	 * Get validated account_id
	 *
	 * @param Forminator_Aweber_Wp_Api $api API.
	 * @return integer
	 * @throws Forminator_Integration_Exception Throws Integration Exception.
	 */
	public function get_validated_account_id( $api ) {
		$accounts = $api->get_accounts();
		if ( ! isset( $accounts->entries ) ) {
			throw new Forminator_Integration_Exception( esc_html__( 'Failed to get AWeber account information', 'forminator' ) );
		}

		$entries = $accounts->entries;
		if ( ! isset( $entries[0] ) ) {
			throw new Forminator_Integration_Exception( esc_html__( 'Failed to get AWeber account information', 'forminator' ) );
		}

		$first_entry = $entries[0];
		$account_id  = $first_entry->id;

		/**
		 * Filter validated account_id
		 *
		 * @since 1.3
		 *
		 * @param integer $account_id Account Id.
		 * @param object $accounts Accounts.
		 * @param Forminator_Aweber_Wp_Api $api
		 */
		$account_id = apply_filters( 'forminator_addon_aweber_validated_account_id', $account_id, $accounts, $api );

		return $account_id;
	}

	/**
	 * Set account_id on class if exist on settings
	 *
	 * @param array $values Setting values.
	 *
	 * @return mixed
	 */
	public function before_get_settings_values( $values ) {
		if ( is_array( $values ) && isset( $values['account_id'] ) ) {
			$this->_account_id = $values['account_id'];
		}

		return $values;
	}

	/**
	 * Set account_id on class if exist on settings
	 *
	 * @param array $values Setting values.
	 *
	 * @return mixed
	 */
	public function before_save_settings_values( $values ) {
		if ( ! empty( $this->_account_id ) ) {
			$values['account_id'] = $this->_account_id;
		}

		return $values;
	}

	/**
	 * Get connected account id
	 *
	 * @return int
	 */
	public function get_account_id() {
		return $this->_account_id;
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
