<?php
/**
 * The Forminator_Captcha class.
 *
 * @package Forminator
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_Captcha
 *
 * @since 1.0
 */
class Forminator_Captcha extends Forminator_Field {

	/**
	 * Name
	 *
	 * @var string
	 */
	public $name = '';

	/**
	 * Slug
	 *
	 * @var string
	 */
	public $slug = 'captcha';

	/**
	 * Type
	 *
	 * @var string
	 */
	public $type = 'captcha';

	/**
	 * Position
	 *
	 * @var int
	 */
	public $position = 16;

	/**
	 * Options
	 *
	 * @var array
	 */
	public $options = array();

	/**
	 * Hide advanced
	 *
	 * @var string
	 */
	public $hide_advanced = 'true';

	/**
	 * Icon
	 *
	 * @var string
	 */
	public $icon = 'sui-icon-recaptcha';

	/**
	 * Forminator_Captcha constructor.
	 *
	 * @since 1.0
	 */
	public function __construct() {
		parent::__construct();

		$this->name = esc_html__( 'Captcha', 'forminator' );
	}

	/**
	 * Field defaults
	 *
	 * @since 1.0
	 * @return array
	 */
	public function defaults() {

		return array(
			'captcha_provider'        => 'recaptcha',
			'captcha_alignment'       => 'left',
			'captcha_type'            => 'v2_checkbox',
			'hcaptcha_type'           => 'hc_checkbox',
			'score_threshold'         => '0.5',
			'captcha_badge'           => 'bottomright',
			'hc_invisible_notice'     => sprintf(
				/* translators: 1. Open <a> tag for Privacy policy, 2. Close </a>, 3. Open <a> tag for Terms of Service, 4. Close </a>. */
				esc_html__(
					'This site is protected by hCaptcha and its %1$sPrivacy Policy%2$s and %3$sTerms of Service%4$s apply.',
					'forminator'
				),
				'<a href="https://hcaptcha.com/privacy">',
				'</a>',
				'<a href="https://hcaptcha.com/terms">',
				'</a>'
			),
			'recaptcha_error_message' => esc_html__( 'reCAPTCHA verification failed. Please try again.', 'forminator' ),
			'hcaptcha_error_message'  => esc_html__( 'hCaptcha verification failed. Please try again.', 'forminator' ),
		);
	}

	/**
	 * Autofill Setting
	 *
	 * @since 1.0.5
	 *
	 * @param array $settings Settings.
	 *
	 * @return array
	 */
	public function autofill_settings( $settings = array() ) {
		// Unsupported Autofill.
		$autofill_settings = array();

		return $autofill_settings;
	}

	/**
	 * Invisible recaptcha
	 *
	 * @param mixed $field Field.
	 * @return mixed
	 */
	public function is_invisible_recaptcha( $field ) {
		// backward.
		$is_invisible = self::get_property( 'invisible_captcha', $field );
		$is_invisible = filter_var( $is_invisible, FILTER_VALIDATE_BOOLEAN );
		if ( ! $is_invisible ) {
			$type = self::get_property( 'captcha_type', $field, '' );
			if ( 'invisible' === $type || 'v3_recaptcha' === $type || 'v2_invisible' === $type ) {
				$is_invisible = true;
			}
		}

		return $is_invisible;
	}

	/**
	 * Field front-end markup
	 *
	 * @since 1.0
	 *
	 * @param array                  $field Field.
	 * @param Forminator_Render_Form $views_obj Forminator_Render_Form object.
	 *
	 * @return mixed
	 */
	public function markup( $field, $views_obj ) {

		$extra_attrs     = '';
		$hcaptcha_notice = '';
		$provider        = self::get_property( 'captcha_provider', $field, 'recaptcha' );
		$alignment       = self::get_property( 'captcha_alignment', $field, 'left' );

		if ( 'recaptcha' === $provider ) {
			$captcha_type  = self::get_property( 'captcha_type', $field, 'v3_recaptcha' );
			$captcha_theme = self::get_property( 'captcha_theme', $field, 'light' );
			$captcha_size  = self::get_property( 'captcha_size', $field, 'normal' );
			$captcha_class = 'forminator-captcha-' . $alignment . ' forminator-g-recaptcha';

			if ( $this->is_invisible_recaptcha( $field ) ) {
				$extra_attrs    = 'data-badge="' . esc_attr( self::get_property( 'captcha_badge', $field, 'inline' ) ) . '"';
				$captcha_size   = 'invisible';
				$captcha_class .= ' recaptcha-invisible';
			}

			switch ( $captcha_type ) {
				case 'v2_checkbox':
					$key = get_option( 'forminator_captcha_key', '' );
					break;
				case 'v2_invisible':
					$key = get_option( 'forminator_v2_invisible_captcha_key', '' );
					break;
				case 'v3_recaptcha':
					$key = get_option( 'forminator_v3_captcha_key', '' );
					break;
			}
		} elseif ( 'turnstile' === $provider ) {
			$captcha_class = 'forminator-captcha-' . $alignment . ' forminator-turnstile';
			$key           = get_option( 'forminator_turnstile_key', '' );
			$captcha_theme = self::get_property( 'turnstile_theme', $field, 'auto' );
			$captcha_size  = self::get_property( 'turnstile_size', $field, 'normal' );
			$extra_attrs   = 'data-language="' . esc_attr( self::get_captcha_language( $field, 'turnstile' ) ) . '"';
		} else {
			$key           = get_option( 'forminator_hcaptcha_key', '' );
			$captcha_type  = self::get_property( 'hcaptcha_type', $field, 'hc_checkbox' );
			$captcha_theme = self::get_property( 'hcaptcha_theme', $field, 'light' );
			$captcha_size  = self::get_property( 'hcaptcha_size', $field, 'normal' );
			$captcha_class = 'forminator-captcha-' . $alignment . ' forminator-hcaptcha';

			if ( 'hc_invisible' === $captcha_type ) {
				$captcha_size    = 'invisible';
				$hcaptcha_notice = self::get_property( 'hc_invisible_notice', $field, '' );
				$hcaptcha_notice = sprintf( '<div class="forminator-checkbox__label">%s</div>', wp_kses_post( $hcaptcha_notice ) );
			}
		}
		// don't use .g-recaptcha class as it will render automatically when other plugin load recaptcha with default render.
		return sprintf(
			'<div class="%s" data-theme="%s" %s data-sitekey="%s" data-size="%s"></div> %s',
			esc_attr( $captcha_class ),
			esc_attr( $captcha_theme ),
			$extra_attrs,
			esc_attr( $key ),
			esc_attr( $captcha_size ),
			$hcaptcha_notice
		);
	}

	/**
	 * Get captcha language
	 *
	 * @param array  $field Field settings.
	 * @param string $provider Captcha provider.
	 *
	 * @return string
	 */
	public static function get_captcha_language( $field, $provider = '' ) {
		$site_language    = get_locale();
		$captcha_language = get_option( 'forminator_captcha_language', '' );
		$global_language  = ! empty( $captcha_language ) ? $captcha_language : $site_language;
		$language         = self::get_property( 'language', $field );
		$language         = ! empty( $language ) ? $language : $global_language;

		if ( 'turnstile' === $provider ) {
			$language = strtolower( str_replace( '_', '-', $language ) );
		}

		return $language;
	}


	/**
	 * Mark Captcha unavailable when captcha key not available
	 *
	 * @since 1.0.3
	 *
	 * @param array $field Field.
	 *
	 * @return bool
	 */
	public function is_available( $field ) {
		$provider     = self::get_property( 'captcha_provider', $field, 'recaptcha' );
		$captcha_type = self::get_property( 'captcha_type', $field, '' );

		if ( 'recaptcha' === $provider ) {
			switch ( $captcha_type ) {
				case 'v2_invisible':
					$key = get_option( 'forminator_v2_invisible_captcha_key', '' );
					break;
				case 'v3_recaptcha':
					$key = get_option( 'forminator_v3_captcha_key', '' );
					break;
				default:
					$key = get_option( 'forminator_captcha_key', '' );

			}
		} elseif ( 'turnstile' === $provider ) {
			$key = get_option( 'forminator_turnstile_key', '' );
		} else {
			$key = get_option( 'forminator_hcaptcha_key', '' );
		}

		if ( ! $key ) {
			return false;
		}

		return true;
	}

	/**
	 * Validate captcha
	 *
	 * @since 1.5.3
	 *
	 * @param array        $field Field.
	 * @param array|string $data Data.
	 */
	public function validate( $field, $data ) {
		$element_id   = self::get_property( 'element_id', $field );
		$provider     = self::get_property( 'captcha_provider', $field, 'recaptcha' );
		$captcha_type = self::get_property( 'captcha_type', $field, '' );
		$score        = '';

		if ( 'recaptcha' === $provider ) {

			if ( 'v2_checkbox' === $captcha_type ) {
				$secret = get_option( 'forminator_captcha_secret', '' );
			} elseif ( 'v2_invisible' === $captcha_type ) {
				$secret = get_option( 'forminator_v2_invisible_captcha_secret', '' );
			} elseif ( 'v3_recaptcha' === $captcha_type ) {
				$secret = get_option( 'forminator_v3_captcha_secret', '' );
				$score  = self::get_property( 'score_threshold', $field, '' );
			}

			$error_message = self::get_property( 'recaptcha_error_message', $field, '' );

		} elseif ( 'turnstile' === $provider ) {
			$secret        = get_option( 'forminator_turnstile_secret', '' );
			$error_message = self::get_property( 'turnstile_error_message', $field, '' );
		} else {

			// hcaptcha.
			$secret        = get_option( 'forminator_hcaptcha_secret', '' );
			$error_message = self::get_property( 'hcaptcha_error_message', $field, '' );
		}

		$captcha = new Forminator_Captcha_Verification( $secret, $provider );
		$verify  = $captcha->verify( $data, null, $score );

		if ( is_wp_error( $verify ) ) {
			$invalid_captcha_message = ( ! empty( $error_message ) ? $error_message : esc_html__( 'Captcha verification failed. Please try again.', 'forminator' ) );

			/**
			 * Filter message displayed for invalid captcha
			 *
			 * @since 1.5.3
			 *
			 * @param string   $invalid_captcha_message
			 * @param string   $element_id
			 * @param array    $field
			 * @param WP_Error $verify
			 */
			$invalid_captcha_message = apply_filters( 'forminator_invalid_captcha_message', $invalid_captcha_message, $element_id, $field, $verify );

			$this->validation_message[ $element_id ] = $invalid_captcha_message;
		}
	}
}
