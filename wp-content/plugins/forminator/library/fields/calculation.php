<?php
/**
 * The Forminator_Calculation class.
 *
 * @package Forminator
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_Calculation
 *
 * @since 1.7
 */
class Forminator_Calculation extends Forminator_Field {

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
	public $slug = 'calculation';

	/**
	 * Type
	 *
	 * @var string
	 */
	public $type = 'calculation';

	/**
	 * Position
	 *
	 * @var int
	 */
	public $position = 11;

	/**
	 * Options
	 *
	 * @var array
	 */
	public $options = array();

	/**
	 * Is input
	 *
	 * @var bool
	 */
	public $is_input = false;

	/**
	 * Has counter
	 *
	 * @var bool
	 */
	public $has_counter = false;

	/**
	 * Icon
	 *
	 * @var string
	 */
	public $icon = 'sui-icon-calculator';

	/**
	 * Is calculable
	 *
	 * @var bool
	 */
	public $is_calculable = true;

	/**
	 * Forminator_Text constructor.
	 *
	 * @since 1.7
	 */
	public function __construct() {
		parent::__construct();

		$this->name = esc_html__( 'Calculations', 'forminator' );
	}

	/**
	 * Field defaults
	 *
	 * @since 1.7
	 * @return array
	 */
	public function defaults() {
		return array(
			'field_label' => esc_html__( 'Calculations', 'forminator' ),
		);
	}

	/**
	 * Field front-end markup
	 *
	 * @since 1.7
	 *
	 * @param array                  $field Field.
	 * @param Forminator_Render_Form $views_obj Forminator_Render_Form object.
	 *
	 * @return mixed
	 */
	public function markup( $field, $views_obj ) {

		$settings            = $views_obj->model->settings;
		$this->field         = $field;
		$this->form_settings = $settings;
		$hidden_behavior     = self::get_property( 'hidden_behavior', $field );
		$descr_position      = self::get_description_position( $field, $settings );

		$html        = '';
		$wrapper     = array();
		$id          = self::get_property( 'element_id', $field );
		$name        = $id;
		$id          = $id . '-field_' . Forminator_CForm_Front::$uid;
		$required    = self::get_property( 'required', $field, false );
		$value       = esc_html( self::get_post_data( $name, self::get_property( 'default_value', $field ) ) );
		$label       = esc_html( self::get_property( 'field_label', $field, '' ) );
		$description = self::get_property( 'description', $field, '' );
		$formula     = self::get_property( 'formula', $field, '', 'str' );
		$is_hidden   = self::get_property( 'hidden', $field, false, 'bool' );
		$suffix      = self::get_property( 'suffix', $field );
		$prefix      = self::get_property( 'prefix', $field );
		$precision   = self::get_calculable_precision( $field );
		$separator   = self::get_property( 'separators', $field, 'blank' );
		$separators  = $this->forminator_separators( $separator, $field );

		$point = ! empty( $precision ) ? $separators['point'] : '';

		if ( is_numeric( $formula ) ) {
			$formula = $formula . '*1';
		}

		$fields_in_formula = Forminator_CForm_Front_Action::calculator_pull_fields( $formula );
		$full_matches      = $fields_in_formula[0];
		foreach ( $fields_in_formula[1] as $key => $field_id ) {
			if ( ! isset( $full_matches[ $key ] ) ) {
				continue;
			}
			$form_id = isset( $this->form_settings['form_id'] ) ? $this->form_settings['form_id'] : 0;
			if ( ! empty( $form_id ) ) {
				if ( false !== strpos( $field_id, 'number-' )
					|| false !== strpos( $field_id, 'currency-' )
					|| false !== strpos( $field_id, 'slider-' )
				) {
					$field_form    = Forminator_Form_Model::model()->load( $form_id );
					$formula_field = $field_form->get_field( $field_id, true );
					$calc_enabled  = self::get_property( 'calculations', $formula_field, true, 'bool' );
					if ( ! $calc_enabled ) {
						$field_val    = Forminator_CForm_Front_Action::replace_to( $field_id, $formula );
						$find_str     = $full_matches[ $key ];
						$replace_with = '(' . ( $field_val ) . ')';
						$formula      = implode( $replace_with, explode( $find_str, $formula, 2 ) );
					}
				}
			}
		}

		$number_attr = array(
			'type'               => 'text',
			'name'               => $name,
			'value'              => $value,
			'id'                 => $id,
			'class'              => 'forminator-calculation',
			'data-formula'       => $formula,
			'data-required'      => $required,
			'data-decimal-point' => $point,
			'data-precision'     => $precision,
			'data-is-hidden'     => $is_hidden,
			'disabled'           => 'disabled', // mark as disabled so this value won't send to backend later.
			'data-decimals'      => $precision,
			'data-inputmask'     => "'groupSeparator': '" . $separators['separator'] . "', 'radixPoint': '" . $point . "', 'digits': '" . $precision . "'",
			'readonly'           => 'readonly',
		);

		if ( $hidden_behavior && 'zero' === $hidden_behavior ) {
			$number_attr['data-hidden-behavior'] = $hidden_behavior;
		}

		if ( empty( $prefix ) && empty( $suffix ) ) {
			$number_attr['class'] .= ' forminator-input';
		}

		if ( ! empty( $prefix ) || ! empty( $suffix ) ) {
			$wrapper = array(
				'<div class="forminator-input forminator-input-with-prefix">',
				sprintf( '<span class="forminator-suffix">%s</span></div>', esc_html( $suffix ) ),
				'',
				esc_html( $prefix ),
			);
		}

		$html .= '<div class="forminator-field">';

			$html .= self::create_input(
				$number_attr,
				$label,
				$description,
				$required,
				$descr_position,
				$wrapper
			);

		$html .= '</div>';

		return apply_filters( 'forminator_field_calculation_markup', $html, $id, $required, $value );
	}

	/**
	 * Return field inline validation rules
	 *
	 * @since 1.7
	 * @return string
	 */
	public function get_validation_rules() {
		return '';
	}

	/**
	 * Return field inline validation errors
	 *
	 * @since 1.7
	 * @return string
	 */
	public function get_validation_messages() {
		return '';
	}

	/**
	 * Sanitize data
	 *
	 * @since 1.7
	 *
	 * @param array        $field Field.
	 * @param array|string $data - the data to be sanitized.
	 *
	 * @return array|string $data - the data after sanitization
	 */
	public function sanitize( $field, $data ) {
		// Sanitize.
		$data = forminator_sanitize_field( $data );

		return apply_filters( 'forminator_field_calculation_sanitize', $data, $field );
	}

	/**
	 * Get calculable value for repeted item in Group fields
	 *
	 * @param array  $submitted_field_data Submitted data.
	 * @param array  $field_settings Field settings.
	 * @param string $group_suffix Group suffix.
	 * @param array  $grouped_fields Fields in the same group.
	 * @return string Formula
	 */
	public static function get_calculable_repeater_value( $submitted_field_data, $field_settings, $group_suffix, $grouped_fields ) {
		$calculable_value = self::get_calculable_value( $submitted_field_data, $field_settings );
		if ( ! $group_suffix ) {
			return $calculable_value;
		}

		foreach ( $grouped_fields as $group_field_slug ) {
			$calculable_value = str_replace( '{' . $group_field_slug . '}', '{' . $group_field_slug . $group_suffix . '}', $calculable_value );
		}

		return $calculable_value;
	}

	/**
	 * Get calculable value
	 *
	 * @since 1.7
	 * @inheritdoc
	 * @param mixed $submitted_field_data Submitted field data.
	 * @param array $field_settings Field settings.
	 */
	public static function get_calculable_value( $submitted_field_data, $field_settings ) {
		$formula = self::get_property( 'formula', $field_settings, '', 'str' );

		/**
		 * Filter formula being used on calculable value of calculation field
		 *
		 * @since 1.7
		 *
		 * @param string $formula
		 * @param array  $submitted_data
		 * @param array  $field_settings
		 *
		 * @return string|int|float formula, or hardcoded value
		 */
		$formula = apply_filters( 'forminator_field_calculation_calculable_value', $formula, Forminator_CForm_Front_Action::$prepared_data, $field_settings );

		if ( empty( $formula ) ) {
			return 0.0;
		}

		return $formula;
	}

	/**
	 * Get default error message
	 *
	 * @since 1.7
	 *
	 * @return string
	 */
	public static function default_error_message() {
		$message = esc_html__( 'Failed to calculate field.', 'forminator' );

		/**
		 * Filter default error message
		 *
		 * @since 1.7
		 *
		 * @param string $message
		 *
		 * @return string
		 */
		$message = apply_filters( 'forminator_field_calculation_default_error_message', $message );

		return $message;
	}
}
