<?php
/**
 * The Forminator_CForm_Front_User_Login class.
 *
 * @package Forminator
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

// Show/Hide the field Remember Me.
add_action( 'forminator_cform_render_fields', array( 'Forminator_CForm_Front_User_Login', 'render_fields' ), 11, 2 );

/**
 * Front user class for custom login forms
 *
 * @since 1.11
 */
class Forminator_CForm_Front_User_Login extends Forminator_User {

	/**
	 * Remember cookie number
	 *
	 * @var int
	 */
	protected $remember_cookie_number;

	/**
	 * Remember cookie type
	 *
	 * @var string
	 */
	protected $remember_cookie_type;

	/**
	 * Form settings
	 *
	 * @var array
	 */
	public $settings;

	/**
	 * Forminator_CForm_Front_User_Login constructor
	 */
	public function __construct() {
		parent::__construct();

		$this->remember_cookie_number = 14;
		$this->remember_cookie_type   = DAY_IN_SECONDS;

		add_filter( 'auth_cookie_expiration', array( $this, 'change_cookie_expiration' ), 10, 3 );
		add_action( 'wd_forced_reset_password_url', array( $this, 'force_change_password' ), 10, 2 );
	}

	/**
	 * Change cookie expiration
	 *
	 * @param int  $expiration Expiration.
	 * @param int  $user_id User Id.
	 * @param bool $remember Remember.
	 *
	 * @return int
	 */
	public function change_cookie_expiration( $expiration, $user_id, $remember ) {
		if ( $remember ) {
			$expiration = $this->remember_cookie_number * $this->remember_cookie_type;
		}

		return $expiration;
	}

	/**
	 * Is "Remember Me" submitted?
	 *
	 * @return bool
	 */
	private function is_submitted_remember_me() {
		$submitted_remember_me = false;
		foreach ( Forminator_CForm_Front_Action::$prepared_data as $field_key => $field_val ) {
			if ( false !== stripos( $field_key, 'checkbox-' ) && 'remember-me' === $field_val[0] ) {
				$submitted_remember_me = true;
				break;
			}
		}

		return $submitted_remember_me;
	}

	/**
	 * Process login
	 *
	 * @param object                      $custom_form Custom form.
	 * @param Forminator_Form_Entry_Model $entry Form Entry model.
	 * @param array                       $field_data_array Field data.
	 *
	 * @return array
	 */
	public function process_login( $custom_form, Forminator_Form_Entry_Model $entry, $field_data_array ) {
		$settings       = $custom_form->settings;
		$this->settings = $settings;

		// Field username.
		$response = array();
		$username = '';
		if ( isset( $settings['login-username-field'] ) && ! empty( $settings['login-username-field'] ) ) {
			$username = $this->replace_value( $field_data_array, $settings['login-username-field'] );
		}
		$username = apply_filters( 'forminator_custom_form_login_username_before_signon', $username, $custom_form, Forminator_CForm_Front_Action::$prepared_data, $entry );

		// Field password.
		$password = '';
		if ( isset( $settings['login-password-field'] ) && ! empty( $settings['login-password-field'] ) ) {
			$password = $this->replace_value( $field_data_array, $settings['login-password-field'] );
		}
		$password              = apply_filters( 'forminator_custom_form_login_password_before_signon', $password, $custom_form, Forminator_CForm_Front_Action::$prepared_data, $entry );
		$submitted_remember_me = $this->is_submitted_remember_me();

		if ( $submitted_remember_me && isset( $settings['remember-me'] ) && 'true' === $settings['remember-me'] ) {
			$remember = true;

			if ( isset( $settings['remember-me-cookie-type'] ) ) {

				switch ( $settings['remember-me-cookie-type'] ) {
					case 'weeks':
						$this->remember_cookie_type = WEEK_IN_SECONDS;
						break;

					case 'months':
						$this->remember_cookie_type = MONTH_IN_SECONDS;
						break;

					case 'years':
						$this->remember_cookie_type = YEAR_IN_SECONDS;
						break;

					case 'days':
					default:
						$this->remember_cookie_type = DAY_IN_SECONDS;
						break;
				}
			} else {
				$this->remember_cookie_type = DAY_IN_SECONDS;
			}

			$this->remember_cookie_number = isset( $settings['remember-me-cookie-number'] ) ? (int) $settings['remember-me-cookie-number'] : $this->remember_cookie_number;

		} else {
			$remember = false;
		}
		$remember = apply_filters( 'forminator_custom_form_login_remember_before_signon', $remember, $custom_form, $entry );

		$defender_data = forminator_defender_compatibility();
		if ( $defender_data['is_activated'] ) {
			$sign_on          = wp_authenticate( $username, $password );
			$two_fa_component = new $defender_data['two_fa_component']();
			if ( ! is_wp_error( $sign_on ) ) {
				$available_providers = $two_fa_component->get_available_providers_for_user( $sign_on );
				$token               = uniqid();
				// create and store a login token so we can query this user again.
				update_user_meta( $sign_on->ID, 'defender_two_fa_token', $token );
				$enable_otp = $two_fa_component->is_user_enabled_otp( $sign_on->ID );
				if ( $enable_otp && ! empty( $available_providers ) ) {
					$auth_method = isset( Forminator_CForm_Front_Action::$prepared_data['auth_method'] ) ? Forminator_CForm_Front_Action::$prepared_data['auth_method'] : '';
					if ( empty( $auth_method ) ) {
						$auth_method = $two_fa_component->get_default_provider_slug_for_user( $sign_on->ID );
					}
					if ( ! isset( Forminator_CForm_Front_Action::$prepared_data['auth_method'] ) ) {
						$response['authentication'] = 'show';
						$response['user']           = $sign_on;
						$response['auth_token']     = $token;
						$response['auth_method']    = $auth_method;
						$response['auth_nav']       = $this->forminator_show_2fa_nav( $available_providers );

						return $response;
					} else {
						$provider = $two_fa_component->get_provider_by_slug( $auth_method );
						if ( ! is_wp_error( $provider ) ) {
							$result = $provider->validate_authentication( $sign_on );
							if ( ! empty( $result ) && ! is_wp_error( $result ) ) {
								delete_user_meta( $sign_on->ID, 'defender_two_fa_token' );
								$response['authentication'] = 'valid';
							} else {
								$response['authentication'] = 'invalid';
								$response['user']           = $sign_on;

								return $response;
							}
						}
					}
				}
			}
		}
		$user_fields = array(
			'user_login'    => $username,
			'user_password' => $password,
			'remember'      => $remember,
		);

		$sign_on = wp_signon( $user_fields );

		$response['authentication'] = '';
		$response['user']           = $sign_on;

		return $response;
	}

	/**
	 * Redirect page if form is submitted through ajax and defender Pwned passwords is enabled
	 *
	 * @since 1.15.1
	 *
	 * @param string $url URL.
	 * @param string $action Action.
	 */
	public function force_change_password( $url, $action ) {
		if ( isset( $this->settings['enable-ajax'] ) && 'true' === $this->settings['enable-ajax'] ) {
			wp_send_json_success(
				array(
					'url' => esc_url_raw( $url ),
				)
			);
		}
	}

	/**
	 * Get element ID for "Remember Me". There may be several checkboxes in the form.
	 * "Remember Me" is the last form field. Before the submit button.
	 *
	 * @param Forminator_Form_Model $custom_form Form model.
	 *
	 * @return int
	 */
	public static function get_element_id_for_remember_me( $custom_form ) {
		$id      = 1;
		$last_id = 0;
		if ( is_object( $custom_form ) ) {
			$fields = $custom_form->get_fields();
			foreach ( $fields as $field ) {
				$field_array = $field->to_formatted_array();
				$field_type  = $field_array['type'];
				if ( 'checkbox' === $field_type ) {
					$last_id = Forminator_Field::get_property( 'element_id', $field_array );
					$last_id = (int) str_replace( 'checkbox-', '', $last_id );
				}
			}
			$id = $last_id + 1;
		}

		return $id;
	}

	/**
	 * Show/Hide the field Remember Me
	 *
	 * @param array $wrappers Wrappers.
	 * @param int   $id Id.
	 *
	 * @return array
	 */
	public static function render_fields( $wrappers, $id ) {
		$custom_form = Forminator_Base_Form_Model::get_model( $id );

		if ( isset( $custom_form->settings['form-type'] )
			&& 'login' === $custom_form->settings['form-type']
			&& isset( $custom_form->settings['remember-me'] )
			&& 'true' === $custom_form->settings['remember-me']
			&& ! empty( $wrappers )
		) {
			$id = self::get_element_id_for_remember_me( $custom_form );

			if ( isset( $custom_form->settings['remember-me-label'] ) && ! empty( $custom_form->settings['remember-me-label'] ) ) {
				$label = trim( $custom_form->settings['remember-me-label'] );
			} else {
				$label = esc_html__( 'Remember Me', 'forminator' );
			}

			$new_wrappers = array(
				'wrapper_id' => 'wrapper-1511347711918-2169',
				'fields'     => array(
					array(
						'element_id'   => 'checkbox-' . $id,
						'type'         => 'checkbox',
						'options'      => array(
							array(
								'label'   => $label,
								'value'   => 'remember-me',
								'default' => false,
							),
						),
						'cols'         => 12,
						'wrapper_id'   => 'wrapper-8730-999',
						'value_type'   => 'checkbox',
						'field_label'  => '',
						'layout'       => 'vertical',
						'custom-class' => 'remember-me',
					),
				),
			);

			array_push( $wrappers, $new_wrappers );
		}

		return $wrappers;
	}

	/**
	 * Show 2FA Nav
	 *
	 * @param array $providers Providers.
	 *
	 * @return string
	 */
	public function forminator_show_2fa_nav( $providers ) {
		$html = '';
		if ( ! empty( $providers ) ) {
			foreach ( $providers as $slug => $provider ) {
				$html .= '<li class="forminator-2fa-link" id="forminator-2fa-link-' . esc_attr( $slug ) . '" data-slug="' . esc_attr( $slug ) . '">';
				$html .= $provider->get_login_label();
				$html .= '</li>';
			}
		}

		return $html;
	}
}
