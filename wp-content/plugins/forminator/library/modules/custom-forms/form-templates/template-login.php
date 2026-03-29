<?php
/**
 * The Forminator_Template_Login class.
 *
 * @package Forminator
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_Template_Contact_Form
 *
 * @since 1.0
 */
class Forminator_Template_Login extends Forminator_Template {

	/**
	 * Template defaults
	 *
	 * @since 1.0
	 * @return array
	 */
	public function defaults() {
		return array(
			'id'          => 'login',
			'name'        => esc_html__( 'User Login', 'forminator' ),
			'description' => esc_html__( 'Standard login form for returning users with fields for username/email and password, and links for account recovery or registration.', 'forminator' ),
			'icon'        => 'profile-male',
			'priority'    => 6,
			'category'    => 'custom-form',
			'pro'         => false,
		);
	}

	/**
	 * Template fields
	 *
	 * @since 1.0
	 * @return array
	 */
	public function fields() {
		return array(
			array(
				'wrapper_id' => 'wrapper-1511347711918-1669',
				'fields'     => array(
					array(
						'element_id'  => 'text-1',
						'type'        => 'text',
						'cols'        => '12',
						'required'    => 'true',
						'field_label' => esc_html__( 'Username or Email Address', 'forminator' ),
					),
				),
			),
			array(
				'wrapper_id' => 'wrapper-1511347712118-1739',
				'fields'     => array(
					array(
						'element_id'             => 'password-1',
						'type'                   => 'password',
						'cols'                   => '12',
						'required'               => 'true',
						'field_label'            => esc_html__( 'Password', 'forminator' ),
						/* translators: 1. Open <a>, 2. Close </a>. */
						'description'            => sprintf( esc_html__( '%1$sLost your password?%2$s', 'forminator' ), '<a href="{lostpassword_url}" title="Lost Password" target="_blank">', '</a>' ),
						'confirm-password-label' => esc_html__( 'Confirm Password', 'forminator' ),
					),
				),
			),
		);
	}

	/**
	 * Template settings
	 *
	 * @since 1.0
	 * @return array
	 */
	public function settings() {
		return array(
			'form-type'                         => 'login',
			'submission-behaviour'              => 'behaviour-redirect',
			'thankyou-message'                  => esc_html__( 'Welcome back! You’re now logged in.', 'forminator' ),
			'redirect-url'                      => admin_url(),
			'submitData'                        => array(
				'custom-submit-text'          => esc_html__( 'Login', 'forminator' ),
				'custom-invalid-form-message' => esc_html__( 'Error: Your form is not valid, please fix the errors!', 'forminator' ),
			),
			'enable-ajax'                       => 'true',
			'validation-inline'                 => true,
			'fields-style'                      => 'open',
			'basic-fields-style'                => 'open',
			'form-expire'                       => 'no_expire',
			'use-admin-email'                   => 'true',
			'admin-email-title'                 => '',
			'admin-email-editor'                => '',
			'admin-email-recipients'            => array(),
			'user-email-title'                  => '',
			'user-email-editor'                 => '',

			// Main container.
			'form-padding-top'                  => '0',
			'form-padding-right'                => '0',
			'form-padding-bottom'               => '0',
			'form-padding-left'                 => '0',
			'form-border-width'                 => '0',
			'form-border-style'                 => 'none',
			'form-border-radius'                => '0',
			// Typography - Label.
			'cform-label-font-family'           => 'Roboto',
			'cform-label-custom-family'         => '',
			'cform-label-font-size'             => '12',
			'cform-label-font-weight'           => 'bold',
			// Typography - Section Title.
			'cform-title-font-family'           => 'Roboto',
			'cform-title-custom-family'         => '',
			'cform-title-font-size'             => '45',
			'cform-title-font-weight'           => 'normal',
			'cform-title-text-align'            => 'left',
			// Typography - Section Subtitle.
			'cform-subtitle-font-family'        => 'Roboto',
			'cform-subtitle-custom-font'        => '',
			'cform-subtitle-font-size'          => '18',
			'cform-subtitle-font-weight'        => 'normal',
			'cform-subtitle-text-align'         => 'left',
			// Typography - Input & Textarea.
			'cform-input-font-family'           => 'Roboto',
			'cform-input-custom-font'           => '',
			'cform-input-font-size'             => '16',
			'cform-input-font-weight'           => 'normal',
			// Typography - Radio & Checkbox.
			'cform-radio-font-family'           => 'Roboto',
			'cform-radio-custom-font'           => '',
			'cform-radio-font-size'             => '14',
			'cform-radio-font-weight'           => 'normal',
			// Typography - Select.
			'cform-select-font-family'          => 'Roboto',
			'cform-select-custom-family'        => '',
			'cform-select-font-size'            => '16',
			'cform-select-font-weight'          => 'normal',
			// Typography - Multi Select.
			'cform-multiselect-font-family'     => 'Roboto',
			'cform-multiselect-custom-font'     => '',
			'cform-multiselect-font-size'       => '16',
			'cform-multiselect-font-weight'     => 'normal',
			// Typography - Multi Select tag.
			'cform-multiselect-tag-font-family' => 'Roboto',
			'cform-multiselect-tag-custom-font' => '',
			'cform-multiselect-tag-font-size'   => '12',
			'cform-multiselect-tag-font-weight' => 'medium',
			// Typography - Dropdown.
			'cform-dropdown-font-family'        => 'Roboto',
			'cform-dropdown-custom-font'        => '',
			'cform-dropdown-font-size'          => '16',
			'cform-dropdown-font-weight'        => 'normal',
			// Typography - Calendar.
			'cform-calendar-font-family'        => 'Roboto',
			'cform-calendar-custom-font'        => '',
			'cform-calendar-font-size'          => '13',
			'cform-calendar-font-weight'        => 'normal',
			// Typography - Buttons.
			'cform-button-font-family'          => 'Roboto',
			'cform-button-custom-font'          => '',
			'cform-button-font-size'            => '14',
			'cform-button-font-weight'          => '500',
			// Typography - Timeline.
			'cform-timeline-font-family'        => 'Roboto',
			'cform-timeline-custom-font'        => '',
			'cform-timeline-font-size'          => '12',
			'cform-timeline-font-weight'        => 'normal',
			// Typography - Pagination.
			'cform-pagination-font-family'      => '',
			'cform-pagination-custom-font'      => '',
			'cform-pagination-font-size'        => '16',
			'cform-pagination-font-weight'      => 'normal',
			'payment_require_ssl'               => 'true,',
			'submission-file'                   => 'delete',
			// Default Form Fields.
			'login-username-field'              => 'text-1',
			'login-password-field'              => 'password-1',
			'remember-me'                       => 'true',
			'remember-me-label'                 => esc_html__( 'Remember Me', 'forminator' ),
			'remember-me-cookie-number'         => '2',
			'remember-me-cookie-type'           => 'weeks',
			// Additional settings.
			'hide-login-form'                   => '1',
			'hidden-login-form-message'         => '<p>' . esc_html__( 'You are already logged in.', 'forminator' ) . '</p>',
			// Layout - Radio/Checkbox.
			'field-image-size'                  => 'custom',
			'cform-color-option'                => 'theme',
		);
	}
}
