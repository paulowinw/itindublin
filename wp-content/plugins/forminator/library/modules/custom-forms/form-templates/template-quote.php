<?php
/**
 * The Forminator_Template_Quote class.
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
class Forminator_Template_Quote extends Forminator_Template {

	/**
	 * Template defaults
	 *
	 * @since 1.0
	 * @return array
	 */
	public function defaults() {
		return array(
			'id'          => 'quote',
			'name'        => esc_html__( 'Quote Request', 'forminator' ),
			'description' => esc_html__( 'Enables customers to request a quote for products or services, with fields for personal details, specific needs, and preferences.', 'forminator' ),
			'icon'        => 'clipboard-notes',
			'priority'    => 3,
			'category'    => 'customer-service',
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
				'wrapper_id' => 'wrapper-1988247712118-9512',
				'fields'     => array(
					array(
						'element_id'  => 'checkbox-1',
						'type'        => 'checkbox',
						'cols'        => '12',
						'required'    => false,
						'field_label' => esc_html__( 'Services', 'forminator' ),
						'placeholder' => '',
						'value_type'  => 'checkbox',
						'options'     => array(
							array(
								'label' => 'Service 1',
								'value' => 'service-1',
							),
							array(
								'label' => 'Service 2',
								'value' => 'service-2',
							),
						),
					),
				),
			),
			array(
				'wrapper_id' => 'wrapper-1988247712118-9871',
				'fields'     => array(
					array(
						'element_id'  => 'textarea-1',
						'type'        => 'textarea',
						'cols'        => '12',
						'required'    => false,
						'field_label' => esc_html__( 'Additional notes', 'forminator' ),
						'placeholder' => '',
						'input_type'  => 'paragraph',
						'limit'       => '180',
						'limit_type'  => 'characters',
					),
				),
			),
			array(
				'wrapper_id' => 'wrapper-1511347711918-1669',
				'fields'     => array(
					array(
						'element_id'     => 'name-1',
						'type'           => 'name',
						'cols'           => '12',
						'required'       => 'true',
						'prefix'         => 'true',
						'fname'          => 'true',
						'mname'          => 'true',
						'lname'          => 'true',
						'multiple_name'  => 'true',
						'fname_required' => true,
						'lname_required' => true,
						'field_label'    => esc_html__( 'First Name', 'forminator' ),
						'prefix_label'   => esc_html__( 'Prefix', 'forminator' ),
						'fname_label'    => esc_html__( 'First Name', 'forminator' ),
						'mname_label'    => esc_html__( 'Middle Name', 'forminator' ),
						'lname_label'    => esc_html__( 'Last Name', 'forminator' ),
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
				'wrapper_id' => 'wrapper-1311247712118-1194',
				'fields'     => array(
					array(
						'element_id'      => 'phone-1',
						'type'            => 'phone',
						'cols'            => '12',
						'required'        => true,
						'field_label'     => esc_html__( 'Phone Number', 'forminator' ),
						'validation'      => true,
						'validation_text' => '',
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
			'form-type'                         => 'default',
			'submission-behaviour'              => 'behaviour-thankyou',
			'thankyou-message'                  => esc_html__( 'Thanks! We’ve received your request and will send your quote shortly.', 'forminator' ),
			'submitData'                        => array(
				'custom-submit-text'          => esc_html__( 'Request Quote', 'forminator' ),
				'custom-invalid-form-message' => esc_html__( 'Error: Your form is not valid, please fix the errors!', 'forminator' ),
			),
			'enable-ajax'                       => 'true',
			'validation-inline'                 => true,
			'fields-style'                      => 'open',
			'basic-fields-style'                => 'open',
			'form-expire'                       => 'no_expire',
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
			'payment_require_ssl'               => false,
			'submission-file'                   => 'delete',
			// Layout - Radio/Checkbox.
			'field-image-size'                  => 'custom',
			'cform-color-option'                => 'theme',
		);
	}
}
