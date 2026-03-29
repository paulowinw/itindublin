<?php
/**
 * Forminator Google sheet
 *
 * @package Forminator
 */

/**
 * Class Forminator_Googlesheet
 * Google Sheets Integration Main Class
 *
 * @since 1.0 Google Sheets Integration
 */
final class Forminator_Googlesheet extends Forminator_Integration {

	/**
	 * Forminator_Googlesheet instance
	 *
	 * @var Forminator_Googlesheet|null
	 */
	protected static $instance = null;

	/**
	 * Slug
	 *
	 * @var string
	 */
	protected $_slug = 'googlesheet';

	/**
	 * Google sheet version
	 *
	 * @var string
	 */
	protected $_version = FORMINATOR_ADDON_GOOGLESHEET_VERSION;

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
	protected $_short_title = 'Google Sheets';

	/**
	 * Title
	 *
	 * @var string
	 */
	protected $_title = 'Google Sheets';

	const MIME_TYPE_GOOGLE_DRIVE_FOLDER = 'application/vnd.google-apps.folder';
	const MIME_TYPE_GOOGLE_SPREADSHEET  = 'application/vnd.google-apps.spreadsheet';

	/**
	 * Position
	 *
	 * @var int
	 */
	protected $_position = 3;

	/**
	 * Sensitive key names that require encryption.
	 *
	 * @var array
	 */
	protected $_sensitive_keys = array( 'client_secret' );

	/**
	 * Forminator_Googlesheet constructor.
	 *
	 * @since 1.0 Google Sheets Integration
	 */
	public function __construct() {
		// late init to allow translation.
		$this->_description = esc_html__( 'Get awesome by your form.', 'forminator' );

		$this->is_multi_global = true;

		$this->global_id_for_new_integrations = uniqid( '', true );
	}

	/**
	 * Override on is_connected
	 *
	 * @since 1.0 Google Sheets Integration
	 *
	 * @return bool
	 * @throws Forminator_Integration_Exception Throws Integration Exception.
	 */
	public function is_connected() {
		try {
			// check if its active.
			if ( ! $this->is_active() ) {
				throw new Forminator_Integration_Exception( esc_html__( 'Google Sheets is not active', 'forminator' ) );
			}

			$is_connected   = false;
			$setting_values = $this->get_settings_values();
			// if user completed api setup.
			if ( ! empty( $setting_values ) ) {
				$is_connected = true;
			}
		} catch ( Forminator_Integration_Exception $e ) {
			$is_connected = false;
		}

		/**
		 * Filter connected status of Google Sheets
		 *
		 * @since 1.2
		 *
		 * @param bool $is_connected
		 */
		$is_connected = apply_filters( 'forminator_addon_googlesheet_is_connected', $is_connected );

		return $is_connected;
	}

	/**
	 * Override settings available,
	 *
	 * @since 1.0 Google Sheets Integration
	 * @return bool
	 */
	public function is_settings_available() {
		return true;
	}

	/**
	 * Allow multiple connection on one form
	 *
	 * @since 1.0 Google Sheets Integration
	 * @return bool
	 */
	public function is_allow_multi_on_form() {
		return true;
	}

	/**
	 * Settings wizard
	 *
	 * @since 1.0 Google Sheets Integration
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
	 * @since 1.0 Google Sheets Integration
	 *
	 * @param array $submitted_data Submitted data.
	 *
	 * @return array
	 */
	public function setup_client_id( $submitted_data ) {
		$settings_values = $this->get_settings_values();
		$template        = forminator_addon_googlesheet_dir() . 'views/settings/setup-client.php';

		$buttons = array();
		if ( $this->is_connected() ) {
			$buttons['disconnect']     = array(
				'markup' => self::get_button_markup( esc_html__( 'DISCONNECT', 'forminator' ), 'sui-button-ghost forminator-addon-disconnect' ),
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
			'identifier'          => '',
			'token'               => $this->get_client_access_token(),
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
			$identifier    = isset( $submitted_data['identifier'] ) ? $submitted_data['identifier'] : '';

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
						// reset connection.
						$settings_values = array();
					}
					$settings_values['client_id']     = $client_id;
					$settings_values['client_secret'] = $real_client_secret;
					$settings_values['identifier']    = $identifier;

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
	 * @param array $submitted_data Submitted data.
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
	 * @since 1.0 Google Sheets Integration
	 * @return array
	 */
	public function authorize_access() {

		$template = forminator_addon_googlesheet_dir() . 'views/settings/authorize.php';

		$buttons = array();
		if ( $this->is_connected() ) {
			$buttons['disconnect'] = array(
				'markup' => self::get_button_markup( esc_html__( 'DISCONNECT', 'forminator' ), 'sui-button-ghost forminator-addon-disconnect' ),
			);
		}

		$template_params = array(
			'auth_url' => $this->get_auth_url(),
			'token'    => $this->get_client_access_token(),
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
	 * @since 1.0 Google Sheets Integration
	 * @return array
	 */
	public function wait_authorize_access() {
		$template = forminator_addon_googlesheet_dir() . 'views/settings/wait-authorize.php';

		$is_poll = true;
		$token   = $this->get_client_access_token();

		$template_params = array(
			'token'    => $token,
			'auth_url' => $this->get_auth_url(),
		);

		if ( $token ) {
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
	 * @since 1.0 Google Sheets Integration
	 * @return bool
	 */
	public function is_authorized() {
		$setting_values = $this->get_settings_values();

		return ! empty( $setting_values['token'] );
	}

	/**
	 * Get Auth Url
	 *
	 * @return string
	 */
	public function get_auth_url() {
		$google_client = $this->get_google_client();
		$auth_url      = $google_client->createAuthUrl();

		return $auth_url;
	}

	/**
	 * Get Client ID
	 *
	 * @since 1.0 Google Sheets Integration
	 * @return string
	 */
	public function get_client_id() {
		$settings_values = $this->get_settings_values();
		$client_id       = '';
		if ( isset( $settings_values ['client_id'] ) ) {
			$client_id = $settings_values ['client_id'];
		}

		/**
		 * Filter client id used
		 *
		 * @since 1.2
		 *
		 * @param string $client_id
		 */
		$client_id = apply_filters( 'forminator_addon_googlesheet_client_id', $client_id );

		return $client_id;
	}

	/**
	 * Get Client secret
	 *
	 * @since 1.0 Google Sheets Integration
	 * @return string
	 */
	public function get_client_secret() {
		$settings_values = $this->get_settings_values( true );
		$client_secret   = '';
		if ( isset( $settings_values ['client_secret'] ) ) {
			$client_secret = $settings_values ['client_secret'];
		}

		/**
		 * Filter client secret used
		 *
		 * @since 1.2
		 *
		 * @param string $client_secret
		 */
		$client_secret = apply_filters( 'forminator_addon_googlesheet_client_secret', $client_secret );

		return $client_secret;
	}

	/**
	 * Get Access Token
	 *
	 * @since 1.0 Google Sheets Integration
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
		 * @param string $token (json encoded).
		 */
		$token = apply_filters( 'forminator_addon_googlesheet_client_access_token', $token );

		return $token;
	}

	/**
	 * Update Access Token
	 *
	 * @since 1.0 Google Sheets Integration
	 *
	 * @param string $access_token Access token.
	 */
	public function update_client_access_token( $access_token ) {
		$settings_values           = $this->get_settings_values( true );
		$settings_values ['token'] = $access_token;
		$this->save_settings_values( $settings_values );
	}

	/**
	 * Register a page for redirect url of Goolge auth
	 *
	 * @since 1.0 Google Sheets Integration
	 *
	 * @return array
	 */
	public function register_integration_sections() {
		return array(
			'authorize' => array( $this, 'authorize_page_callback' ),
		);
	}


	/**
	 * Google Sheets Authorize Page
	 *
	 * @since 1.0 Google Sheets Integration
	 *
	 * @param array $query_args Arguments.
	 *
	 * @return string
	 * @throws Forminator_Integration_Exception Throws Integration Exception.
	 */
	public function authorize_page_callback( $query_args ) {
		if ( isset( $query_args['global_id'] ) ) {
			$this->multi_global_id = $query_args['global_id'];
		}
		$settings        = $this->get_settings_values( true );
		$template        = forminator_addon_googlesheet_dir() . 'views/sections/authorize.php';
		$template_params = array(
			'error_message' => '',
			'is_close'      => false,
		);

		if ( isset( $query_args['code'] ) ) {
			try {
				$google_client = $this->get_google_client();
				$google_client->fetchAccessTokenWithAuthCode( $query_args['code'] );
				$token = $google_client->getAccessToken();
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
			} catch ( Exception $e ) {
				// catch all exception.
				$template_params['error_message'] = $e->getMessage();
			}
		}

		return self::get_template( $template, $template_params );
	}

	/**
	 * Refresh expired token
	 *
	 * @param ForminatorGoogleAddon\Google\Client $google_client Google client.
	 * @return ForminatorGoogleAddon\Google\Client
	 * @throws Forminator_Integration_Exception Throws Integration Exception.
	 */
	public function refresh_token_if_expired( $google_client ) {
		if ( $google_client->isAccessTokenExpired() ) {
			$client_access_token = $this->get_client_access_token();
			if ( ! empty( $client_access_token ) && is_string( $client_access_token ) ) {
				// Backward compatible.
				$client_access_token = json_decode( $client_access_token, true );
			}
			if ( empty( $client_access_token['refresh_token'] ) ) {
				throw new Forminator_Integration_Exception( esc_html__( 'Refresh token doesn\'t exist. Please reauthorize the Google Sheets integration.', 'forminator' ) );
			}
			$google_client->fetchAccessTokenWithRefreshToken( $client_access_token['refresh_token'] );
		}
		return $google_client;
	}

	/**
	 * Get Forminator_Google_Client Object
	 *
	 * @since 1.0 Google Sheets Integration
	 * @return ForminatorGoogleAddon\Google\Client
	 */
	public function get_google_client() {
		$redirect_url  = forminator_addon_integration_section_admin_url( $this, 'authorize', false );
		$client_id     = $this->get_client_id();
		$client_secret = $this->get_client_secret();
		$scopes        = array(
			ForminatorGoogleAddon\Google\Service\Sheets::SPREADSHEETS,
			ForminatorGoogleAddon\Google\Service\Sheets::DRIVE,
		);

		$google_client = new ForminatorGoogleAddon\Google\Client();
		$google_client->setApplicationName( esc_html__( 'Forminator Pro', 'forminator' ) );
		$google_client->setClientId( $client_id );
		$google_client->setClientSecret( $client_secret );
		$google_client->setScopes( $scopes );
		$google_client->setRedirectUri( $redirect_url );
		$google_client->setAccessType( 'offline' );
		$google_client->setPrompt( 'consent' );

		/**
		 * Filter Google API Client used through out cycle
		 *
		 * @since 1.2
		 *
		 * @param ForminatorGoogleAddon\Google\Client $google_client
		 * @param string        $client_id
		 * @param string        $client_secret
		 * @param array         $scopes
		 * @param string        $redirect_url
		 */
		$google_client = apply_filters( 'forminator_addon_googlesheet_google_client', $google_client, $client_id, $client_secret, $scopes, $redirect_url );

		return $google_client;
	}


	/**
	 * Revoke token on Google before deactivate
	 *
	 * @since 1.0 Google Sheets Integration
	 * @return bool
	 * @throws Forminator_Integration_Exception Throws Integration Exception.
	 */
	public function deactivate() {
		try {
			$google_client = $this->get_google_client();
			$access_token  = $this->get_client_access_token();
			if ( $access_token ) {
				$google_client->setAccessToken( $access_token );
				$revoked = $google_client->revokeToken();
			}
		} catch ( Forminator_Integration_Exception $e ) {
			$this->_deactivation_error_message = $e->getMessage();

			return false;
		}

		return true;
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
