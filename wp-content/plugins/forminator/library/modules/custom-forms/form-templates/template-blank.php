<?php
/**
 * The Forminator_Template_Blank class.
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
class Forminator_Template_Blank extends Forminator_Template {

	/**
	 * Template defaults
	 *
	 * @since 1.0
	 * @return array
	 */
	public function defaults() {
		return array(
			'id'          => 'blank',
			'name'        => esc_html__( 'Blank Form', 'forminator' ),
			'description' => esc_html__( 'Use this template to create a form with no default fields.', 'forminator' ),
			'icon'        => 'clipboard-notes',
			'priority'    => 1,
			'category'    => '',
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
		return array();
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
			'thankyou-message'                  => esc_html__( 'Thanks! We’ve received your submission and will get back to you shortly.', 'forminator' ),
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
