<?php
// phpcs:ignoreFile -- this class currently unused, for reference only
/**
 * PayPal Express Payment Gateway
 *
 * @since 1.0
 */

/**
 * To do
 * - do form validation before requesting paypal
 */
class Forminator_PayPal_Express extends Forminator_Payment_Gateway {
	/**
	 * Gateway slug
	 *
	 * @var string
	 */
	protected $_slug = 'paypal_express';

	/**
	 * Api mode
	 *
	 * @var string
	 */
	protected $api_mode = '';

	/**
	 * Sandbox Client ID
	 *
	 * @var string
	 */
	protected $sandbox_id = '';

	/**
	 * Sandbox Secret
	 *
	 * @var string
	 */
	protected $sandbox_secret = '';

	/**
	 * Sandbox encrypted Secret
	 *
	 * @var string
	 */
	protected $sandbox_secret_encrypted = '';

	/**
	 * Live Client Id
	 *
	 * @var string
	 */
	protected $live_id = '';

	/**
	 * Live Secret
	 *
	 * @var string
	 */
	protected $live_secret = '';

	/**
	 * Live encrypted Secret
	 *
	 * @var string
	 */
	protected $live_secret_encrypted = '';

	/**
	 * Live Mode flag
	 *
	 * @var bool
	 */
	protected $is_live = false;

	protected $redirect_url = '';

	protected $apiContext = null;

	/**
	 * PayPal API url
	 */
	protected $api_live = 'https://api.paypal.com/';

	/**
	 * PayPal Sandbox API url
	 */
	protected $api_sandbox = 'https://api.sandbox.paypal.com/';

	/**
	 * Currency
	 *
	 * @var string
	 */
	protected $currency = 'USD';

	const INVALID_SANDBOX_SECRET_EXCEPTION = 90;
	const INVALID_LIVE_SECRET_EXCEPTION = 91;

	const INVALID_SANDBOX_ID_EXCEPTION = 92;
	const INVALID_LIVE_ID_EXCEPTION = 93;

	const EMPTY_SANDBOX_SECRET_EXCEPTION = 94;
	const EMPTY_LIVE_SECRET_EXCEPTION = 95;

	const EMPTY_SANDBOX_ID_EXCEPTION = 96;
	const EMPTY_LIVE_ID_EXCEPTION = 97;

	/**
	 * Init PayPal settings
	 *
	 * @since 1.0
	 */
	public function init_settings() {
		global $wp;
		$config_key = 'forminator_paypal_configuration';
		$config     = get_option( $config_key, array() );
		if ( ! empty( $config['is_salty'] ) && defined( 'FORMINATOR_ENCRYPTION_KEY' ) ) {
			// Re-encrypt settings after setting FORMINATOR_ENCRYPTION_KEY constant.
			self::reencrypt_settings( $config );
			$config = get_option( $config_key, array() );
		} elseif ( ( ! empty( $config['sandbox_secret'] ) && empty( $config['sandbox_secret_encrypted'] ) )
		           || ( ! empty( $config['live_secret'] ) && empty( $config['live_secret_encrypted'] ) ) ) {
			// Encrypt secret keys.
			self::store_settings( $config );
			$config = get_option( $config_key, array() );
		}

		$this->sandbox_id     = isset( $config['sandbox_id'] ) ? esc_html( $config['sandbox_id'] ) : '';
		$this->sandbox_secret = isset( $config['sandbox_secret'] ) ? esc_html( $config['sandbox_secret'] ) : '';
		$this->live_id        = isset( $config['live_id'] ) ? esc_html( $config['live_id'] ) : '';
		$this->live_secret    = isset( $config['live_secret'] ) ? esc_html( $config['live_secret'] ) : '';
		$this->currency       = isset( $config['currency'] ) ? esc_html ( $config['currency'] ) : 'USD';
		$this->_enabled       = forminator_has_paypal_settings();
		$this->redirect_url   = home_url( $wp->request );

		if ( empty( $this->sandbox_id ) && defined( 'FORMINATOR_PAYPAL_SANDBOX_ID' ) ) {
			$this->sandbox_id = FORMINATOR_PAYPAL_SANDBOX_ID;
		}

		if ( empty( $this->sandbox_secret ) && defined( 'FORMINATOR_PAYPAL_SANDBOX_SECRET' ) ) {
			$this->sandbox_secret = FORMINATOR_PAYPAL_SANDBOX_SECRET;
		} else {
			$this->sandbox_secret_encrypted = $config['sandbox_secret_encrypted'] ?? '';
		}

		$this->live_secret_encrypted = $config['live_secret_encrypted'] ?? '';

		add_filter( 'script_loader_src', array( $this, 'forminator_remove_ver_paypal' ), 9999 );
	}

	/**
	 * @return string
	 */
	public function get_sandbox_id() {
		return $this->sandbox_id;
	}

	/**
	 * Get test secret
	 *
	 * @param bool $decrypted Get decrypted key.
	 * @return string
	 */
	public function get_sandbox_secret( bool $decrypted = false ) {
		if ( $decrypted && ! empty( $this->sandbox_secret_encrypted ) ) {
			return Forminator_Encryption::decrypt( $this->sandbox_secret_encrypted );
		}
		return $this->sandbox_secret;
	}

	/**
	 * @return string
	 */
	public function get_api_url( $mode ) {
		if ( 'sandbox' === $mode ) {
			return $this->api_sandbox;
		}

		return $this->api_live;
	}

	/**
	 * @return string
	 */
	public function get_live_id() {
		return $this->live_id;
	}

	/**
	 * Get live secret
	 *
	 * @param bool $decrypted Get decrypted key.
	 * @return string
	 */
	public function get_live_secret( bool $decrypted = false ) {
		if ( $decrypted && ! empty( $this->live_secret_encrypted ) ) {
			return Forminator_Encryption::decrypt( $this->live_secret_encrypted );
		}
		return $this->live_secret;
	}

	/**
	 * @return string
	 */
	public function get_default_currency() {
		return $this->currency;
	}

	/**
	 * @return bool
	 */
	public function is_live() {
		return $this->is_live;
	}

	/**
	 * Store stripe settings
	 *
	 * @param $settings
	 */
	public static function store_settings( array $settings ) {
		$option_name = 'forminator_paypal_configuration';
		$settings    = self::prepare_settings( $settings );
		update_option( $option_name, $settings );
	}

	/**
	 * Store default currency
	 *
	 * @param string $currency Currency.
	 */
	public static function store_default_currency( string $currency ) {
		$option_name          = 'forminator_paypal_configuration';
		$settings             = get_option( $option_name, array() );
		$settings['currency'] = $currency;
		update_option( $option_name, $settings );
	}

	/**
	 * Encrypt secret keys
	 *
	 * @param array $settings Settings.
	 * @return array
	 */
	public static function prepare_settings( array $settings ): array {
		return Forminator_Encryption::encrypt_secret_keys(
			array( 'sandbox_secret', 'live_secret' ),
			$settings,
		);
	}

	/**
	 * Re-encrypt settings
	 *
	 * @param array $settings Settings.
	 */
	public static function reencrypt_settings( array $settings ) {
		foreach ( $settings as $key => $val ) {
			if ( in_array( $key, array( 'sandbox_secret_encrypted', 'live_secret_encrypted' ), true ) ) {
				$k              = str_replace( '_encrypted', '', $key );
				$settings[ $k ] = Forminator_Encryption::decrypt( $val, true );
			}
		}
		self::store_settings( $settings );
	}

	/**
	 * @return bool
	 */
	public function is_live_ready() {
		return ! empty( $this->live_id ) && ! empty( $this->live_secret );
	}

	/**
	 * @return bool
	 */
	public function is_test_ready() {
		return ! empty( $this->sandbox_id ) && ! empty( $this->sandbox_secret );
	}

	/**
	 * @return bool
	 */
	public function is_ready() {
		if ( $this->is_live ) {
			return $this->is_live_ready();
		}

		return $this->is_test_ready();
	}

	/**
	 * @param bool $live
	 */
	public function set_live( $live ) {
		$this->is_live = $live;
	}

	public static function is_available() {
		return forminator_payment_lib_paypal_version_loaded();
	}


	/**
	 * Handle purchase
	 *
	 * @since 1.0
	 *
	 * @param array $response
	 * @param array $product_fields
	 * @param $field_data_array
	 * @param int $entry_id
	 * @param int $page_id
	 * @param int $shipping
	 *
	 * @return array
	 */
	protected function handle_purchase( $response, $product_fields, $field_data_array, $entry_id, $page_id, $shipping ) {
		return $response;
	}

	/**
	 * Gateway footer scripts
	 *
	 * @since 1.0
	 */
	public function gateway_footer_scripts() {

	}

	/**
	 * Gateway footer scripts
	 *
	 * @since 1.0
	 */
	public function render_buttons_script( $paypal_form_id ) {

	}

	/**
	 * @param $mode
	 * @param $id
	 * @param $secret
	 * @param $error
	 *
	 * @throws Forminator_Gateway_Exception
	 */
	public function validate_id( $mode, $id, $secret, $error = self::INVALID_SANDBOX_SECRET_EXCEPTION ) {
		 $args = array(
			'headers' => array(
				'Content-Type'  => 'application/x-www-form-urlencoded',
				'Authorization' => 'Basic ' . base64_encode( $id . ':' . $secret )
			),
			'body'    => array(
				'grant_type'    => 'client_credentials',
			),
		);

		try {
			$result = $this->api_request( 'v1/oauth2/token', $mode, $args, 'POST' );

			if ( ! isset( $result->access_token ) || empty( $result->access_token ) ) {
				throw new Forminator_Gateway_Exception( esc_html__( 'Failed to configure PayPal payment', 'forminator' ) );
			}
		} catch ( Exception $e ) {
			forminator_maybe_log( __METHOD__, $e->getMessage() );
			throw new Forminator_Gateway_Exception(
				esc_html__( 'Some error has occurred while connecting to your PayPal account. Please resolve the following errors and try to connect again.', 'forminator' ),
				$error,
				$e
			);
		}
	}

	/**
	 * Make request to PayPal API
	 *
	 * @since 1.15
	 *
	 * @param $request
	 * @param $mode
	 * @param $args
	 * @param $method
	 *
	 * @return array
	 */
	public function api_request( $request, $mode, $args = array(), $method = 'GET' ) {
		// Add request to the api URL.
		$api_url	= $this->get_api_url( $mode );
		$url     = $api_url . $request;

		// If method is GET we have to add the args as URL params.
		if ( 'GET' === $method ) {
			$url = add_query_arg( $args, $url );
		}

		// Determinate client ID & Secret from mode.
		$client_id     = 'live' === $mode ? $this->live_id : $this->sandbox_id;
		$client_secret = 'live' === $mode ? $this->get_live_secret( true ) : $this->get_sandbox_secret( true );

		$headers = array(
			'Content-Type'  => 'application/json',
			'Authorization' => 'Basic ' . base64_encode( $client_id . ':' . $client_secret )
		);

		$headers = isset( $args['headers'] ) ? wp_parse_args( $args['headers'], $headers ) : $headers;
		$body    = isset( $args['body'] ) ? $args['body'] : $args;

		// If request is POST then we have to encode the body.
		if ( ! empty ( $body ) && 'POST' === $method ) {
			if ( $headers['Content-Type'] === 'application/json' ) {
				$body = json_encode( $body );
			}
		}

		$query_args = array(
			'method'    => $method,
			'headers'   => $headers,
			'sslverify' => apply_filters( 'forminator_paypal_request_sslverify', false ),
			'timeout'   => apply_filters( 'forminator_paypal_request_timeout', 30 ),
		);

		if ( ! empty( $body ) ) {
			$query_args['body'] = $body;
		}

		$query_result = wp_remote_request( $url, $query_args );

		if ( is_wp_error( $query_result ) ) {
			return $query_result;
		}

		$result_body = wp_remote_retrieve_body( $query_result );

		$body_decoded = json_decode( $result_body );

		if ( empty( $body_decoded ) ) {
			$body_decoded = array();
		}

		return $body_decoded;
	}

	/**
	 * Create Order with PayPal API request
	 *
	 * @since 1.14.5
	 *
	 * @param $order
	 * @param $mode
	 *
	 * @return mixed
	 */
	public function create_order( $order, $mode ) {
		return $this->api_request( 'v2/checkout/orders', $mode, $order, 'POST' );
	}

	/**
	 * Get Order with PayPal API request
	 *
	 * @since 1.14.5
	 *
	 * @param $id
	 * @param $mode
	 *
	 * @return mixed
	 */
	public function get_order( $id, $mode ) {
		return $this->api_request( 'v2/checkout/orders/' . $id, $mode );
	}

	/**
	 * Update Order with PayPal API request
	 *
	 * @since 1.14.5
	 *
	 * @param $id
	 * @param $mode
	 * @param $op
	 * @param $attribute
	 * @param $value
	 *
	 * @return mixed
	 */
	public function update_order( $id, $mode, $op, $attribute, $value ) {
		$options = array(
			array(
				'op'    => $op,
				'path'  => "/purchase_units/@reference_id=='default'/{$attribute}",
				'value' => $value,
			),
		);

		return $this->api_request( 'v2/checkout/orders/' . $id, $mode, $options, 'PATCH' );
	}

	/**
	 * Authorize Order with PayPal API request
	 *
	 * @since 1.14.5
	 *
	 * @param $id
	 * @param $mode
	 *
	 * @return mixed
	 */
	public function authorize_order( $id, $mode ) {
		return $this->api_request( 'v2/checkout/orders/' . $id . '/authorize', $mode, array(), 'POST' );
	}

	/**
	 * Capture Order with PayPal API request
	 *
	 * @since 1.14.5
	 *
	 * @param $id
	 * @param $mode
	 *
	 * @return mixed
	 */
	public function capture_order( $id, $mode ) {
		return $this->api_request( 'v2/checkout/orders/' . $id . '/capture', $mode, array(), 'POST' );
	}

	/**
	 * Remove ver from script
	 * @param $src
	 *
	 * @return string
	 */
	public function forminator_remove_ver_paypal( $src ) {
		if ( $src && strpos( $src, 'paypal.com' ) && strpos( $src, 'ver=' ) )
			$src = remove_query_arg( 'ver', $src );
		return $src;
	}
}
