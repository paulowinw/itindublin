<?php
/**
 * The Forminator_Template_Leads class.
 *
 * @package Forminator
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_Template_Leads
 *
 * @since 1.0
 */
class Forminator_Template_Leads extends Forminator_Template {

	/**
	 * Template defaults
	 *
	 * @since 1.0
	 * @return array
	 */
	public function defaults() {
		return array(
			'id'          => 'leads',
			'name'        => esc_html__( 'Leads for Quizzes & Polls', 'forminator' ),
			'description' => esc_html__( '...', 'forminator' ),
			'icon'        => 'mail',
			'priority'    => 2,
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
				'wrapper_id' => 'wrapper-6160-5978',
				'fields'     => array(
					array(
						'element_id'      => 'html-1',
						'type'            => 'html',
						'cols'            => '12',
						'field_label'     => '',
						/* translators: 1. Open <p>, 2. Close </p>. */
						'variations'      => sprintf( esc_html__( '%1$sPlease provide your contact information to proceed.%2$s', 'forminator' ), '<p>', '</p>' ),
						'validation'      => true,
						'validation_text' => '',
					),
				),
			),
			array(
				'wrapper_id' => 'wrapper-1511347712118-1739',
				'fields'     => array(
					array(
						'element_id'      => 'email-1',
						'type'            => 'email',
						'cols'            => '12',
						'required'        => 'true',
						'field_label'     => esc_html__( 'Email Address', 'forminator' ),
						'validation'      => true,
						'validation_text' => '',
					),
				),
			),
			array(
				'wrapper_id' => 'wrapper-1511347711918-1669',
				'fields'     => array(
					array(
						'element_id'   => 'name-1',
						'type'         => 'name',
						'cols'         => '12',
						'required'     => 'true',
						'field_label'  => esc_html__( 'First Name', 'forminator' ),
						'prefix_label' => esc_html__( 'Prefix', 'forminator' ),
						'fname_label'  => esc_html__( 'First Name', 'forminator' ),
						'mname_label'  => esc_html__( 'Middle Name', 'forminator' ),
						'lname_label'  => esc_html__( 'Last Name', 'forminator' ),
					),
				),
			),
			array(
				'wrapper_id' => 'wrapper-9037-977',
				'fields'     => array(
					array(
						'element_id'          => 'consent-1',
						'type'                => 'consent',
						'cols'                => '12',
						'required'            => 'true',
						'field_label'         => esc_html__( 'Consent', 'forminator' ),
						'validation'          => true,
						'validation_text'     => '',
						'consent_description' =>
							sprintf(
								/* Translators: 1. Opening <a> tag with # href, 2. closing <a> tag, 3. Opening <a> tag with # href, 4. closing <a> tag. */
								esc_html__( 'Yes, I agree with the %1$sprivacy policy%2$s and %3$sterms and conditions%4$s.', 'forminator' ),
								'<a href="#" target="_blank">',
								'</a>',
								'<a href="#" target="_blank">',
								'</a>'
							),
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
			'form-type'                         => 'leads',
			'submission-behaviour'              => 'behaviour-thankyou',
			'thankyou-message'                  => esc_html__( 'Thank you for contacting us, we will be in touch shortly.', 'forminator' ),
			'submitData'                        => array(
				'custom-submit-text'          => esc_html__( 'Submit', 'forminator' ),
				'custom-invalid-form-message' => esc_html__( 'Error: Your form is not valid, please fix the errors!', 'forminator' ),
			),
			'enable-ajax'                       => 'true',
			'validation-inline'                 => true,
			'fields-style'                      => 'open',
			'basic-fields-style'                => 'open',
			'form-expire'                       => 'no_expire',
			// Main container.
			'form-padding'                      => 'custom',
			'form-padding-top'                  => '30',
			'form-padding-right'                => '30',
			'form-padding-bottom'               => '30',
			'form-padding-left'                 => '30',
			'form-border-width'                 => '0',
			'form-border-style'                 => 'none',
			'form-border-radius'                => '0',
			// Colors.
			'cform-color-settings'              => 'true',
			'cform-form-background'             => '#FAFAFA',
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
			'payment_require_ssl'               => false,
			'submission-file'                   => 'delete',
			// Layout - Radio/Checkbox.
			'field-image-size'                  => 'custom',
			'cform-color-option'                => 'theme',
		);
	}
}
