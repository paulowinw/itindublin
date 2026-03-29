<?php
/**
 * The Forminator_Number class.
 *
 * @package Forminator
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_Number
 *
 * @since 1.0
 */
class Forminator_Number extends Forminator_Field {

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
	public $slug = 'number';

	/**
	 * Type
	 *
	 * @var string
	 */
	public $type = 'number';

	/**
	 * Position
	 *
	 * @var int
	 */
	public $position = 8;

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
	public $is_input = true;

	/**
	 * Icon
	 *
	 * @var string
	 */
	public $icon = 'sui-icon-element-number';

	/**
	 * Is calculable
	 *
	 * @var bool
	 */
	public $is_calculable = true;

	/**
	 * Forminator_Number constructor.
	 *
	 * @since 1.0
	 */
	public function __construct() {
		parent::__construct();

		$this->name = esc_html__( 'Number', 'forminator' );
		$required   = __( 'This field is required. Please enter number.', 'forminator' );

		self::$default_required_messages[ $this->type ] = $required;
	}

	/**
	 * Field defaults
	 *
	 * @since 1.0
	 * @return array
	 */
	public function defaults() {
		return apply_filters(
			'forminator_number_defaults_settings',
			array(
				'calculations' => 'true',
				'limit_min'    => 1,
				'limit_max'    => 150,
				'field_label'  => esc_html__( 'Number', 'forminator' ),
			)
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
		$providers = apply_filters( 'forminator_field_' . $this->slug . '_autofill', array(), $this->slug );

		$autofill_settings = array(
			'number' => array(
				'values' => forminator_build_autofill_providers( $providers ),
			),
		);

		return $autofill_settings;
	}

	/**
	 * Field front-end markup
	 *
	 * @since 1.0
	 *
	 * @param array                  $field Field.
	 * @param Forminator_Render_Form $views_obj Forminator_Render_Form object.
	 * @param array                  $draft_value Draft value.
	 *
	 * @return mixed
	 */
	public function markup( $field, $views_obj, $draft_value = null ) {

		$settings            = $views_obj->model->settings;
		$this->field         = $field;
		$this->form_settings = $settings;
		$hidden_behavior     = self::get_property( 'hidden_behavior', $field );
		$descr_position      = self::get_description_position( $field, $settings );

		$html        = '';
		$min         = 0;
		$max         = '';
		$id          = self::get_property( 'element_id', $field );
		$name        = $id;
		$id          = self::get_field_id( $id );
		$required    = self::get_property( 'required', $field, false );
		$ariareq     = 'false';
		$placeholder = $this->sanitize_value( self::get_property( 'placeholder', $field ) );
		$value       = esc_html( self::get_post_data( $name, self::get_property( 'default_value', $field ) ) );
		$label       = esc_html( self::get_property( 'field_label', $field, '' ) );
		$description = self::get_property( 'description', $field, '' ); // wp_kses_data already applied in get_description.
		$min         = esc_html( self::get_property( 'limit_min', $field, false ) );
		$max         = esc_html( self::get_property( 'limit_max', $field, false ) );
		$precision   = self::get_calculable_precision( $field );
		$separator   = self::get_property( 'separators', $field, 'blank' );
		$separators  = $this->forminator_separators( $separator, $field );

		if ( (bool) $required ) {
			$ariareq = 'true';
		}

		if ( isset( $draft_value['value'] ) ) {

			$value = esc_attr( $draft_value['value'] );

		} elseif ( $this->has_prefill( $field ) ) {

			// We have pre-fill parameter, use its value or $value.
			$value = $this->get_prefill( $field, $value );
		}

		if ( 'comma_dot' === $separator && false !== strpos( $value, ',' ) ) {
			$value = str_replace( ',', '', $value );
		}

		$point = ! empty( $precision ) ? $separators['point'] : '';

		$number_attr = array(
			'name'           => $name,
			'value'          => $value,
			'placeholder'    => $placeholder,
			'id'             => $id,
			'class'          => 'forminator-input forminator-number--field',
			'inputmode'      => 'decimal',
			'data-required'  => $required,
			'data-decimals'  => $precision,
			'aria-required'  => $ariareq,
			'data-inputmask' => "'groupSeparator': '" . $separators['separator'] . "', 'radixPoint': '" . $point . "', 'digits': '" . $precision . "'",
		);

		if ( $hidden_behavior && 'zero' === $hidden_behavior ) {
			$number_attr['data-hidden-behavior'] = $hidden_behavior;
		}

		if ( 'blank' === $separator ) {
			$number_attr['type'] = 'number';
			$number_attr['step'] = 'any';
			if ( false !== $min ) {
				$number_attr['min'] = $min;
			}
			if ( $max ) {
				$number_attr['max'] = $max;
			}
		}

		$autofill_markup = $this->get_element_autofill_markup_attr( self::get_property( 'element_id', $field ) );
		$number_attr     = array_merge( $number_attr, $autofill_markup );

		$html .= '<div class="forminator-field">';

			$html .= self::create_input(
				$number_attr,
				$label,
				$description,
				$required,
				$descr_position,
			);

		$html .= '</div>';

		return apply_filters( 'forminator_field_number_markup', $html, $id, $required, $placeholder, $value );
	}

	/**
	 * Return field inline validation rules
	 *
	 * @since 1.0
	 * @return string
	 */
	public function get_validation_rules() {
		$field = $this->field;
		$id    = self::get_property( 'element_id', $field );
		$min   = self::get_property( 'limit_min', $field, false );
		$max   = self::get_property( 'limit_max', $field, false );

		$rules = '"' . $this->get_id( $field ) . '": {';

		if ( $this->is_required( $field ) ) {
			$rules .= '"required": true,';
		}

		if ( false !== $min && is_numeric( $min ) ) {
			$rules .= '"minNumber": ' . (float) $min . ',';
		}
		if ( false !== $max && is_numeric( $max ) ) {
			$rules .= '"maxNumber": ' . (float) $max . ',';
		}

		$rules .= '},';

		return apply_filters( 'forminator_field_number_validation_rules', $rules, $id, $field );
	}

	/**
	 * Return field inline validation errors
	 *
	 * @since 1.0
	 * @return string
	 */
	public function get_validation_messages() {
		$field          = $this->field;
		$min            = self::get_property( 'limit_min', $field, null );
		$max            = self::get_property( 'limit_max', $field, null );
		$custom_message = self::get_property( 'limit_message', $field, false, 'bool' );
		$separator      = self::get_property( 'separators', $field, 'blank' );

		$messages = '"' . $this->get_id( $field ) . '": {' . "\n";

		if ( $this->is_required( $field ) ) {
			$required_validation_message = self::get_property( 'required_message', $field, self::$default_required_messages[ $this->type ] );
			$required_validation_message = apply_filters(
				'forminator_field_number_required_validation_message',
				$required_validation_message,
				$field
			);
			$messages                   .= '"required": "' . forminator_addcslashes( $required_validation_message ) . '",' . "\n";
		}

		$number_validation_message = apply_filters(
			'forminator_field_number_number_validation_message',
			esc_html__( 'This is not valid number.', 'forminator' ),
			$field
		);
		$messages                 .= '"number": "' . forminator_addcslashes( $number_validation_message ) . '",' . "\n";

		if ( ! is_null( $min ) ) {
			$min_validation_message = self::get_property( 'limit_min_message', $field );
			$min_validation_message = apply_filters(
				'forminator_field_number_min_validation_message',
				$custom_message && $min_validation_message ? $min_validation_message : esc_html__( 'Please enter a value greater than or equal to {0}.', 'forminator' ),
				$field
			);

			$validator = 'minNumber';
			if ( 'blank' === $separator ) {
				$validator = 'min';
			}
			$messages .= '"' . $validator . '": "' . forminator_addcslashes( $min_validation_message ) . '",' . "\n";
		}
		if ( ! is_null( $max ) ) {
			$max_validation_message = self::get_property( 'limit_max_message', $field );
			$max_validation_message = apply_filters(
				'forminator_field_number_max_validation_message',
				$custom_message && $max_validation_message ? $max_validation_message : esc_html__( 'Please enter a value less than or equal to {0}.', 'forminator' ),
				$field
			);

			$validator = 'maxNumber';
			if ( 'blank' === $separator ) {
				$validator = 'max';
			}
			$messages .= '"' . $validator . '": "' . forminator_addcslashes( $max_validation_message ) . '",' . "\n";
		}

		$messages .= '},' . "\n";

		return apply_filters( 'forminator_field_number_validation_message', $messages, $field );
	}

	/**
	 * Field back-end validation
	 *
	 * @since 1.0
	 *
	 * @param array        $field Field.
	 * @param array|string $data Data.
	 */
	public function validate( $field, $data ) {
		$id             = self::get_property( 'element_id', $field );
		$max            = self::get_property( 'limit_max', $field, $data );
		$min            = self::get_property( 'limit_min', $field, $data );
		$custom_message = self::get_property( 'limit_message', $field, false, 'bool' );
		$precision      = self::get_calculable_precision( $field );
		$separator      = self::get_property( 'separators', $field, 'blank' );

		$max     = trim( $max );
		$min     = trim( $min );
		$max_len = strlen( $max );
		$min_len = strlen( $min );

		if ( $this->is_required( $field ) ) {

			if ( empty( $data ) && '0' !== $data ) {
				$required_validation_message     = self::get_property( 'required_message', $field, esc_html( self::$default_required_messages[ $this->type ] ) );
				$this->validation_message[ $id ] = apply_filters(
					'forminator_field_number_required_field_validation_message',
					$required_validation_message,
					$id,
					$field,
					$data,
					$this
				);
			}
		}

		if ( ! empty( $data ) ) {
				$separators = $this->forminator_separators( $separator, $field );
				$point      = ! empty( $precision ) ? $separators['point'] : '';
				$data       = str_replace( array( $separators['separator'], $point ), array( '', '.' ), $data );
				$data       = floatval( $data );
				$min        = floatval( $min );
				$max        = floatval( $max );
				// Note : do not compare max or min if that settings field is blank string ( not zero ).
			if ( 0 !== $min_len && $data < $min ) {
				$min_validation_message          = self::get_property( 'limit_min_message', $field );
				$min_validation_message          = $custom_message && $min_validation_message ? $min_validation_message : /* translators: 1: Minimum value, 2: Maximum value */ sprintf( esc_html__( 'The number should be greater than %1$d and less than %2$d.', 'forminator' ), $min, $max );
				$this->validation_message[ $id ] = sprintf(
					apply_filters(
						'forminator_field_number_max_min_validation_message',
						/* translators: ... */
						$min_validation_message,
						$id,
						$field,
						$data
					),
					$max,
					$min
				);
			} elseif ( 0 !== $max_len && $data > $max ) {
				$max_validation_message          = self::get_property( 'limit_max_message', $field );
				$max_validation_message          = $custom_message && $max_validation_message ? $max_validation_message : /* translators: 1: Minimum value, 2: Maximum value */ sprintf( esc_html__( 'The number should be greater than %1$d and less than %2$d.', 'forminator' ), $min, $max );
				$this->validation_message[ $id ] = sprintf(
					apply_filters(
						'forminator_field_number_max_min_validation_message',
						/* translators: ... */
						$max_validation_message,
						$id,
						$field,
						$data
					),
					$max,
					$min
				);
			}
		}
	}

	/**
	 * Sanitize data
	 *
	 * @since 1.0.2
	 *
	 * @param array        $field Field.
	 * @param array|string $data - the data to be sanitized.
	 *
	 * @return array|string $data - the data after sanitization
	 */
	public function sanitize( $field, $data ) {
		$original_data = $data;
		// Sanitize.
		$data = forminator_sanitize_field( $data );

		return apply_filters( 'forminator_field_number_sanitize', $data, $field, $original_data );
	}

	/**
	 * Internal calculable value
	 *
	 * @since 1.7
	 *
	 * @param array|mixed $submitted_field Submitted field.
	 * @param array       $field_settings Field settings.
	 *
	 * @return mixed
	 */
	private static function calculable_value( $submitted_field, $field_settings ) {
		$enabled = self::get_property( 'calculations', $field_settings, false, 'bool' );
		if ( ! $enabled ) {
			return self::FIELD_NOT_CALCULABLE;
		}

		return self::get_calculable_number_format( $field_settings, $submitted_field );
	}

	/**
	 * Get calculable value
	 *
	 * @since 1.7
	 * @inheritdoc
	 * @param array $submitted_field_data Submitted field data.
	 * @param array $field_settings Field settings.
	 */
	public static function get_calculable_value( $submitted_field_data, $field_settings ) {
		$formatting_value = self::forminator_replace_number( $field_settings, $submitted_field_data );
		$calculable_value = self::calculable_value( $formatting_value, $field_settings );
		/**
		 * Filter formula being used on calculable value on number field
		 *
		 * @since 1.7
		 *
		 * @param float $calculable_value
		 * @param array $submitted_field_data
		 * @param array $field_settings
		 *
		 * @return string|int|float
		 */
		$calculable_value = apply_filters( 'forminator_field_number_calculable_value', $calculable_value, $submitted_field_data, $field_settings );

		return $calculable_value;
	}
}
