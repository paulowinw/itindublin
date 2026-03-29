<?php
/**
 * The Forminator_Gateway_Stripe class.
 *
 * @package Forminator
 */

// Include class-exception.php.
require_once __DIR__ . '/class-exception.php';

/**
 * Wrapper Stripe
 * Class Forminator_Gateway_Stripe
 *
 * @since 1.7
 */
class Forminator_Gateway_Stripe {

	/**
	 * Stripe Test Pub key
	 *
	 * @var string
	 */
	protected $test_key = '';

	/**
	 * Stripe Test Sec key
	 *
	 * @var string
	 */
	protected $test_secret = '';

	/**
	 * Stripe Test Secret key encrypted
	 *
	 * @var string
	 */
	protected $test_secret_encrypted = '';

	/**
	 * Stripe Live Pub key
	 *
	 * @var string
	 */
	protected $live_key = '';

	/**
	 * Stripe Live Sec key
	 *
	 * @var string
	 */
	protected $live_secret = '';

	/**
	 * Stripe Live Secret key encrypted
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

	/**
	 * Default Currency for Stripe
	 *
	 * @var string
	 */
	protected $default_currency = 'USD';

	const INVALID_TEST_SECRET_EXCEPTION = 90;
	const INVALID_LIVE_SECRET_EXCEPTION = 91;

	const INVALID_TEST_KEY_EXCEPTION = 92;
	const INVALID_LIVE_KEY_EXCEPTION = 93;

	const EMPTY_TEST_SECRET_EXCEPTION = 94;
	const EMPTY_LIVE_SECRET_EXCEPTION = 95;

	const EMPTY_TEST_KEY_EXCEPTION = 96;
	const EMPTY_LIVE_KEY_EXCEPTION = 97;

	/**
	 * Forminator_Gateway_Stripe constructor.
	 *
	 * @throws Forminator_Gateway_Exception When there is a Gateway error.
	 */
	public function __construct() {

		if ( ! self::is_available() ) {
			throw new Forminator_Gateway_Exception( esc_html__( 'Stripe not available, please check your WordPress installation for PHP Version and plugin conflicts.', 'forminator' ) );
		}

		$config_key = 'forminator_stripe_configuration';
		$config     = get_option( $config_key, array() );
		if ( ! empty( $config['is_salty'] ) && defined( 'FORMINATOR_ENCRYPTION_KEY' ) ) {
			// Re-encrypt settings after setting FORMINATOR_ENCRYPTION_KEY constant.
			self::reencrypt_settings( $config );
			$config = get_option( $config_key, array() );
		} elseif ( ( ! empty( $config['test_secret'] ) && empty( $config['test_secret_encrypted'] ) )
				|| ( ! empty( $config['live_secret'] ) && empty( $config['live_secret_encrypted'] ) ) ) {
			// Encrypt secret keys.
			self::store_settings( $config );
			$config = get_option( $config_key, array() );
		}

		$this->test_key         = isset( $config['test_key'] ) ? $config['test_key'] : '';
		$this->test_secret      = isset( $config['test_secret'] ) ? $config['test_secret'] : '';
		$this->default_currency = isset( $config['default_currency'] ) ? $config['default_currency'] : 'USD';

		if ( empty( $this->test_key ) && defined( 'FORMINATOR_STRIPE_TEST_KEY' ) ) {
			$this->test_key = FORMINATOR_STRIPE_TEST_KEY;
		}

		if ( empty( $this->test_secret ) && defined( 'FORMINATOR_STRIPE_TEST_SECRET' ) ) {
			$this->test_secret = FORMINATOR_STRIPE_TEST_SECRET;
		} else {
			$this->test_secret_encrypted = isset( $config['test_secret_encrypted'] ) ? $config['test_secret_encrypted'] : '';
		}

		$this->live_key    = isset( $config['live_key'] ) ? $config['live_key'] : '';
		$this->live_secret = isset( $config['live_secret'] ) ? $config['live_secret'] : '';

		$this->live_secret_encrypted = isset( $config['live_secret_encrypted'] ) ? $config['live_secret_encrypted'] : '';

		/**
		 * Filter CA bundle path to be used on Stripe HTTP Request
		 * Default is WP Core ca bundle path `ABSPATH . WPINC . '/certificates/ca-bundle.crt'`
		 *
		 * @param string
		 *
		 * @return string
		 */
		$stripe_ca_bundle_path = apply_filters( 'forminator_payments_stripe_ca_bundle_path', ABSPATH . WPINC . '/certificates/ca-bundle.crt' );

		\Forminator\Stripe\Stripe::setCABundlePath( $stripe_ca_bundle_path );
	}

	/**
	 * Set Stripe APP info
	 *
	 * @since 1.12
	 */
	public static function set_stripe_app_info() {
		// Send our plugin info over with the API request.
		\Forminator\Stripe\Stripe::setAppInfo(
			'WordPress Forminator',
			FORMINATOR_VERSION,
			FORMINATOR_PRO_URL,
			FORMINATOR_STRIPE_PARTNER_ID
		);

		// Send the API info over.
		\Forminator\Stripe\Stripe::setApiVersion( FORMINATOR_STRIPE_LIB_DATE );
	}

	/**
	 * Validate keys
	 *
	 * @param string $key Key.
	 * @param string $secret Secret key.
	 * @param string $error Error.
	 *
	 * @throws Forminator_Gateway_Exception When there is a Gateway error.
	 */
	public static function validate_keys( $key, $secret, $error = self::INVALID_TEST_SECRET_EXCEPTION ) {
		/**
		 * You should use Checkout, Elements, or our mobile libraries to perform this process, client-side.
		 * This ensures that no sensitive card data touches your server, and allows your integration to operate in a PCI-compliant way.
		 *
		 * @see https://stripe.com/docs/api/tokens?lang=php
		 */

		try {
			\Forminator\Stripe\Stripe::setApiKey( $secret );
			self::set_stripe_app_info();

			$data = \Forminator\Stripe\Account::retrieve();

			forminator_maybe_log( __METHOD__, $data );
		} catch ( Exception $e ) {
			forminator_maybe_log( __METHOD__, $e->getMessage() );
			throw new Forminator_Gateway_Exception(
				esc_html__( 'Some error has occurred while connecting to your Stripe account. Please resolve the following errors and try to connect again.', 'forminator' ),
				esc_html( $error ),
				$e // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			);
		}
	}

	/**
	 * Is Available
	 *
	 * @return bool
	 */
	public static function is_available() {
		return forminator_payment_lib_stripe_version_loaded();
	}

	/**
	 * Get test key
	 *
	 * @return string
	 */
	public function get_test_key() {
		return $this->test_key;
	}

	/**
	 * Get test secret
	 *
	 * @param bool $decrypted Get decrypted key.
	 * @return string
	 */
	public function get_test_secret( bool $decrypted = false ) {
		if ( $decrypted && ! empty( $this->test_secret_encrypted ) ) {
			return Forminator_Encryption::decrypt( $this->test_secret_encrypted );
		}
		return $this->test_secret;
	}

	/**
	 * Get live key
	 *
	 * @return string
	 */
	public function get_live_key() {
		return $this->live_key;
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
	 * Get currency
	 *
	 * @return string
	 */
	public function get_default_currency() {
		return $this->default_currency;
	}

	/**
	 * Is live
	 *
	 * @return bool
	 */
	public function is_live() {
		return $this->is_live;
	}

	/**
	 * Store stripe settings
	 *
	 * @param array $settings Settings.
	 */
	public static function store_settings( array $settings ) {
		$option_name = 'forminator_stripe_configuration';
		$settings    = self::prepare_settings( $settings );
		update_option( $option_name, $settings );
	}

	/**
	 * Store default currency
	 *
	 * @param string $currency Currency.
	 */
	public static function store_default_currency( string $currency ) {
		$option_name                  = 'forminator_stripe_configuration';
		$settings                     = get_option( $option_name, array() );
		$settings['default_currency'] = $currency;
		update_option( $option_name, $settings );
	}

	/**
	 * Encrypt secret keys
	 *
	 * @param array $settings Settings.
	 * @return array
	 */
	public static function prepare_settings( array $settings ): array {
		$symbols_to_save = array( 8, 4 );

		return Forminator_Encryption::encrypt_secret_keys(
			array( 'test_secret', 'live_secret' ),
			$settings,
			$symbols_to_save
		);
	}

	/**
	 * Re-encrypt settings
	 *
	 * @param array $settings Settings.
	 */
	public static function reencrypt_settings( array $settings ) {
		foreach ( $settings as $key => $val ) {
			if ( in_array( $key, array( 'test_secret_encrypted', 'live_secret_encrypted' ), true ) ) {
				$k              = str_replace( '_encrypted', '', $key );
				$settings[ $k ] = Forminator_Encryption::decrypt( $val, true );
			}
		}

		self::store_settings( $settings );
	}

	/**
	 * Is live ready
	 *
	 * @return bool
	 */
	public function is_live_ready() {
		return ! empty( $this->live_key ) && ! empty( $this->live_secret );
	}

	/**
	 * Is test ready
	 *
	 * @return bool
	 */
	public function is_test_ready() {
		return ! empty( $this->test_key ) && ! empty( $this->test_secret );
	}

	/**
	 * Is ready
	 *
	 * @return bool
	 */
	public function is_ready() {
		if ( $this->is_live ) {
			return $this->is_live_ready();
		}

		return $this->is_test_ready();
	}

	/**
	 * Set live
	 *
	 * @param bool $live Live?.
	 */
	public function set_live( $live ) {
		$this->is_live = $live;
	}

	/**
	 * Charge
	 *
	 * @param mixed $data Data.
	 *
	 * @return \Forminator\Stripe\ApiResource
	 */
	public function charge( $data ) {
		$api_key = $this->is_live() ? $this->live_secret : $this->test_secret;
		\Forminator\Stripe\Stripe::setApiKey( $api_key );
		self::set_stripe_app_info();

		return \Forminator\Stripe\Charge::create( $data );
	}

	/**
	 * Retrieve ifo from token
	 *
	 * @param string $token Token.
	 *
	 * @return \Forminator\Stripe\StripeObject
	 */
	public function retrieve_info_from_token( $token ) {
		$api_key = $this->is_live() ? $this->live_secret : $this->test_secret;
		\Forminator\Stripe\Stripe::setApiKey( $api_key );
		self::set_stripe_app_info();

		return \Forminator\Stripe\Token::retrieve( $token );
	}

	/**
	 * Get the exception error and return WP_Error
	 *
	 * @param mixed $e Exception.
	 *
	 * @since 1.15
	 *
	 * @return WP_Error
	 */
	public function get_error( $e ) {
		$code = $e->getCode();

		if ( is_int( $code ) ) {
			$code = ( 0 === $code ) ? 'zero' : $code;

			return new WP_Error( $code, $e->getMessage() );
		} else {
			return new WP_Error( $e->getError()->code, $e->getError()->message );
		}
	}
}
