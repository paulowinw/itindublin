<?php
/**
 * Forminator Captcha Verification
 *
 * @package Forminator
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_Captcha_Verification
 *
 * Handle Captcha verification
 *
 * @since 1.15.5
 */
class Forminator_Captcha_Verification {

	/**
	 * Secred Key
	 *
	 * @var string
	 * @since 1.5.3
	 */
	private $secret_key = '';

	/**
	 * Provider
	 *
	 * @var string
	 * @since 1.15.5
	 */
	private $provider = '';

	/**
	 * Forminator_Captcha_Verification constructor.
	 *
	 * @since 1.5.3
	 *
	 * @param string $secret_key Secret Key.
	 * @param string $provider Provider - Added since 1.15.5.
	 */
	public function __construct( $secret_key, $provider ) {
		$this->secret_key = $secret_key;
		$this->provider   = $provider;
	}

	/**
	 * Verify captcha
	 *
	 * @since 1.5.3
	 *
	 * @param string      $user_response User response.
	 * @param null|string $remote_ip Remote IP.
	 * @param string      $score Score.
	 *
	 * @return bool|WP_Error (true on success, WP_Error on fail)
	 */
	public function verify( $user_response, $remote_ip = null, $score = '' ) {

		$provider = $this->provider;
		$url      = $this->get_verify_endpoint();

		$args = array(
			'method' => 'POST',
			'body'   => array(
				'secret'   => $this->secret_key,
				'response' => $user_response,
				'remoteip' => $remote_ip ? $remote_ip : Forminator_Geo::get_user_ip(),
			),
		);

		$res = wp_remote_request( $url, $args );

		if ( is_wp_error( $res ) ) {
			forminator_maybe_log( __METHOD__, $res );

			return $res;
		}

		$body = wp_remote_retrieve_body( $res );
		if ( empty( $body ) ) {
			$error = new WP_Error( $provider . '_empty_response', 'Empty response', array( $res ) );
			forminator_maybe_log( __METHOD__, $error );

			return $error;
		}

		$json = json_decode( $body, true );
		if ( empty( $json ) ) {
			$error = new WP_Error( $provider . '_failed_decode', 'Fail to decode', array( $body ) );
			forminator_maybe_log( __METHOD__, $error );

			return $error;
		}

		if ( 'recaptcha' === $provider ) {

			if ( ! empty( $score ) && ! empty( $json['score'] ) && floatval( $json['score'] ) < floatval( $score ) ) {
				$error = new WP_Error( 'recaptcha_failed_score', 'Score is lower than expected.', array( $body ) );
				forminator_maybe_log( __METHOD__, $error );

				return $error;
			}
		} elseif ( ! empty( $score ) && ! empty( $json['score'] ) && floatval( $json['score'] ) >= floatval( $score ) ) {

				$error = new WP_Error( 'hcaptcha_failed_score', 'Score is higher than expected.', array( $body ) );
				forminator_maybe_log( __METHOD__, $error );

				return $error;
		}

		// success verify.
		if ( isset( $json['success'] ) && true === $json['success'] ) {
			return true;
		}

		// read error.
		$error = new WP_Error( $provider . '_failed_verify', 'Fail to verify', array( $json ) );

		return $error;
	}

	/**
	 * Get Recaptcha endpoint to verify user response
	 *
	 * @since 1.5.3
	 * @since 1.15.5    Added hcaptcha endpoint
	 *
	 * @return string
	 */
	private function get_verify_endpoint() {
		$provider = $this->provider;

		if ( 'recaptcha' === $provider ) {
			$endpoint = 'https://www.google.com/recaptcha/api/siteverify';
		} elseif ( 'turnstile' === $provider ) {
			$endpoint = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
		} else {
			$endpoint = 'https://hcaptcha.com/siteverify';
		}

		/**
		 * Filter endpoint to be used for verify captcha
		 *
		 * @since 1.5.3     forminator_recaptcha_verify_endpoint
		 * @since 1.15.5    Added filter for hcaptcha: forminator_hcaptcha_verify_endpoint
		 *
		 * @param string $endpoint
		 *
		 * @return string
		 */
		$endpoint = apply_filters( 'forminator_' . $provider . '_verify_endpoint', $endpoint );

		return $endpoint;
	}
}
