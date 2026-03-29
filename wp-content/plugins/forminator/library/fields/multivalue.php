<?php
/**
 * The Forminator_MultiValue class.
 *
 * @package Forminator
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_MultiValue
 *
 * @since 1.0
 */
class Forminator_MultiValue extends Forminator_Field {

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
	public $slug = 'checkbox';

	/**
	 * Type
	 *
	 * @var string
	 */
	public $type = 'checkbox';

	/**
	 * Position
	 *
	 * @var int
	 */
	public $position = 10;

	/**
	 * Options
	 *
	 * @var array
	 */
	public $options = array();

	/**
	 * Icon
	 *
	 * @var string
	 */
	public $icon = 'sui-icon-element-checkbox';

	/**
	 * Is calculable
	 *
	 * @var bool
	 */
	public $is_calculable = true;

	/**
	 * Forminator_MultiValue constructor.
	 *
	 * @since 1.0
	 */
	public function __construct() {
		parent::__construct();

		$this->name = esc_html__( 'Checkbox', 'forminator' );
		$required   = __( 'This field is required. Please select a value.', 'forminator' );

		self::$default_required_messages[ $this->type ] = $required;
	}

	/**
	 * Field defaults
	 *
	 * @since 1.0
	 * @return array
	 */
	public function defaults() {
		return array(
			'value_type'  => 'checkbox',
			'field_label' => esc_html__( 'Checkbox', 'forminator' ),
			'layout'      => 'vertical',
			'options'     => array(
				array(
					'label' => esc_html__( 'Option 1', 'forminator' ),
					'value' => 'one',
					'key'   => forminator_unique_key(),
				),
				array(
					'label' => esc_html__( 'Option 2', 'forminator' ),
					'value' => 'two',
					'key'   => forminator_unique_key(),
				),
			),
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
			'checkbox' => array(
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

		$settings    = $views_obj->model->settings;
		$this->field = $field;
		$i           = 1;
		$html        = '';
		$id          = self::get_property( 'element_id', $field );
		$name        = $id;
		$id          = 'forminator-field-' . $id;
		$uniq_id     = Forminator_CForm_Front::$uid;
		$post_value  = self::get_post_data( $name, self::FIELD_PROPERTY_VALUE_NOT_EXIST );
		$custom_name = 'custom-' . $name;
		$name        = $name . '[]';
		$required    = self::get_property( 'required', $field, false );
		$options     = self::get_options( $field );
		$value_type  = trim( isset( $field['value_type'] ) ? $field['value_type'] : 'multiselect' );
		$description = self::get_property( 'description', $field, '' );
		$label       = esc_html( self::get_property( 'field_label', $field, '' ) );
		$design      = $this->get_form_style( $settings );
		$descr_id    = $id . '-' . $uniq_id;

		$calc_enabled     = self::get_property( 'calculations', $field, false, 'bool' );
		$images_enabled   = self::get_property( 'enable_images', $field, false );
		$images_enabled   = filter_var( $images_enabled, FILTER_VALIDATE_BOOLEAN );
		$input_visibility = self::get_property( 'input_visibility', $field, 'true' );
		$input_visibility = filter_var( $input_visibility, FILTER_VALIDATE_BOOLEAN );
		$draft_values     = $draft_value;
		$draft_value      = isset( $draft_value['value'] ) && ! empty( $draft_value['value'] ) ? array_map( 'trim', $draft_value['value'] ) : '';
		$hidden_behavior  = self::get_property( 'hidden_behavior', $field );
		$descr_position   = self::get_description_position( $field, $settings );

		$draft_valid  = false;
		$prefil_valid = false;
		$default_arr  = array();

		$html .= sprintf(
			'<div role="group" class="%s" aria-labelledby="%s">',
			esc_attr( $required ? 'forminator-field required' : 'forminator-field' ),
			esc_attr( 'forminator-checkbox-group-' . $id . '-' . $uniq_id . '-label' )
		);

		if ( $label ) {
			$label  = self::convert_markdown( $label );
			$label .= $required ? ' ' . forminator_get_required_icon() : '';
			$html  .= sprintf(
				'<span id="%s" class="forminator-label">%s</span>',
				'forminator-checkbox-group-' . $id . '-' . $uniq_id . '-label',
				$label
			);
		}

		if ( 'above' === $descr_position ) {
			$html .= self::get_description( $description, $descr_id, $descr_position );
		}

		$hidden_calc_behavior = '';
		if ( $hidden_behavior && 'zero' === $hidden_behavior ) {
			$hidden_calc_behavior = ' data-hidden-behavior="' . $hidden_behavior . '"';
		}

		foreach ( $options as $option ) {
			$pref_value = $option['value'] ? esc_html( $option['value'] ) : esc_html( $option['label'] );
			if ( ! empty( $draft_value ) ) {
				if ( in_array( trim( $pref_value ), $draft_value ) ) { // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
					$draft_valid   = true;
					$default_arr[] = $pref_value;
				}
			}

			if ( $this->has_prefill( $field ) ) {
				// We have pre-fill parameter, use its value or $value.
				$prefill        = $this->get_prefill( $field, false );
				$prefill_values = explode( ',', $prefill );
				$prefill_values = array_map( 'trim', $prefill_values );
				if ( in_array( $pref_value, $prefill_values ) ) { // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
					$prefil_valid  = true;
					$default_arr[] = $pref_value;
				}
			}
		}

		foreach ( $options as $option ) {
			$value             = '' !== $option['value'] ? $option['value'] : $option['label'];
			$input_id          = $id . '-' . $i . '-' . $uniq_id;
			$option_default    = isset( $option['default'] ) ? filter_var( $option['default'], FILTER_VALIDATE_BOOLEAN ) : false;
			$calculation_value = $calc_enabled && isset( $option['calculation'] ) ? $option['calculation'] : 0.0;
			$option_image_url  = array_key_exists( 'image', $option ) ? $option['image'] : '';
			$option_label      = '<span class="forminator-checkbox-label">' . wp_kses_post( $option['label'] ) . '</span>';
			$aria_label        = '<span class="forminator-screen-reader-only">' . wp_kses_post( $option['label'] ) . '</span>';

			$class = 'forminator-checkbox';

			if ( $images_enabled && ! empty( $option_image_url ) ) {

				$class .= ' forminator-has_image';

				if ( $input_visibility ) {
					$class .= ' forminator-has_box';
				}
			}

			if ( 'horizontal' === self::get_property( 'layout', $field, '' ) ) {
				$class .= ' forminator-checkbox-inline';
			}

			$selected = false;

			if ( self::FIELD_PROPERTY_VALUE_NOT_EXIST !== $post_value ) {
				if ( is_array( $post_value ) ) {
					$selected = in_array( $value, $post_value );// phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
				}
			} elseif ( $draft_valid ) {
				if ( in_array( strval( $value ), array_map( 'strval', $default_arr ), true ) ) {
					$selected = true;
				}
			} elseif ( $prefil_valid ) {
				if ( in_array( strval( $value ), array_map( 'strval', $default_arr ), true ) ) {
					$selected = true;
				}
			} else {
				$selected = $option_default;
			}

			$selected = $selected ? 'checked="checked"' : '';

			$label_id = $input_id . '-label';

			$html .= sprintf(
				'<label id="%s" for="%s" class="%s" title="%s">',
				esc_attr( $label_id ),
				esc_attr( $input_id ),
				esc_attr( $class ),
				esc_attr( $option['label'] )
			);

				$html .= sprintf(
					'<input type="checkbox" name="%s" value="%s" id="%s" aria-labelledby="%s" data-calculation="%s" %s %s%s/>',
					$name,
					esc_html( $value ),
					$input_id,
					$label_id,
					$calculation_value,
					$selected,
					$hidden_calc_behavior,
					( ! empty( $description ) ? ' aria-describedby="' . esc_attr( $id . '-' . $uniq_id . '-description' ) . '"' : '' )
				);

			if ( $input_visibility && ( $images_enabled && ! empty( $option_image_url ) ) ) {
				// Bullet + Label.
				$html .= '<span class="forminator-checkbox-box" aria-hidden="true"></span>';
				$html .= $option_label;

				// Image.
				if ( 'none' === $design ) {
					$html .= '<img class="forminator-checkbox-image" src="' . esc_url( $option_image_url ) . '" aria-hidden="true" />';
				} else {
					$html     .= '<span class="forminator-checkbox-image" aria-hidden="true">';
						$html .= '<span style="background-image: url(' . esc_url( $option_image_url ) . ');"></span>';
					$html     .= '</span>';
				}
			} elseif ( ! $input_visibility && ( $images_enabled && ! empty( $option_image_url ) ) ) {

				// Image.
				if ( 'none' === $design ) {
					$html .= '<img class="forminator-checkbox-image" src="' . esc_url( $option_image_url ) . '" aria-hidden="true" />';
				} else {
					$html     .= '<span class="forminator-checkbox-image" aria-hidden="true">';
						$html .= '<span style="background-image: url(' . esc_url( $option_image_url ) . ');"></span>';
					$html     .= '</span>';
				}

				// Aria Label.
				$html .= $aria_label;

			} else {

				// Bullet + Label.
				$html .= '<span class="forminator-checkbox-box" aria-hidden="true"></span>';
				$html .= $option_label;

			}

			$html .= '</label>';

			++$i;
		}

		$custom_input_id         = $id . '-' . ( $i - 1 ) . '-' . $uniq_id;
		$input_labelledby        = $custom_input_id . '-label';
		$custom_input_attributes = array(
			'id'              => 'custom-' . $custom_input_id,
			'name'            => $custom_name,
			'aria-labelledby' => $input_labelledby,
		);
		$html                   .= self::maybe_add_custom_option( $field, $options, $custom_input_attributes, $draft_values );

		if ( 'above' !== $descr_position ) {
			$html .= self::get_description( $description, $descr_id, $descr_position );
		}

		$html .= '</div>';

		return apply_filters( 'forminator_field_multiple_markup', $html, $id, $required, $options, $value_type );
	}

	/**
	 * Return field inline validation rules
	 *
	 * @since 1.0
	 * @return string
	 */
	public function get_validation_rules() {
		$rules       = '';
		$field       = $this->field;
		$id          = self::get_property( 'element_id', $field );
		$is_required = $this->is_required( $field );

		if ( $is_required ) {
			$rules .= '"' . $this->get_id( $field ) . '[]": "required",';

			$enable_custom_option = self::get_property( 'enable_custom_option', $field, false );
			if ( $enable_custom_option ) {
				$rules .= '"custom-' . $this->get_id( $field ) . '": {' . "\n";
				$rules .= '"customInputForOtherOption": "checkbox",';
				$rules .= '},' . "\n";
			}
		}

		return apply_filters( 'forminator_field_multiple_validation_rules', $rules, $id, $field );
	}

	/**
	 * Return field inline validation errors
	 *
	 * @since 1.0
	 * @return string
	 */
	public function get_validation_messages() {
		$messages    = '';
		$field       = $this->field;
		$id          = self::get_property( 'element_id', $field );
		$is_required = $this->is_required( $field );

		if ( $is_required ) {
			$required_message = self::get_property( 'required_message', $field, $this->get_required_error_message() );
			$required_message = apply_filters(
				'forminator_multi_field_required_validation_message',
				$required_message,
				$id,
				$field
			);
			$messages        .= '"' . $this->get_id( $field ) . '[]": "' . forminator_addcslashes( $required_message ) . '",' . "\n";

			$enable_custom_option = self::get_property( 'enable_custom_option', $field, false );
			if ( $enable_custom_option ) {
				$custom_value_required_message = self::get_property( 'custom_value_error_message', $field, '' );
				$custom_value_required_message = apply_filters(
					'forminator_custom_value_field_required_validation_message',
					( ! empty( $custom_value_required_message ) ? $custom_value_required_message : esc_html__( 'Please, enter a custom value', 'forminator' ) ),
					'custom-' . $id,
					$field
				);
				$messages                     .= '"custom-' . $this->get_id( $field ) . '": "' . forminator_addcslashes( $custom_value_required_message ) . '",' . "\n";
			}
		}

		return $messages;
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
		$id = self::get_property( 'element_id', $field );

		foreach ( $data as $value ) {
			if ( false === array_search( strval( htmlspecialchars_decode( $value ) ), array_map( 'strval', array_column( $field['options'], 'value' ) ), true ) ) {
				$this->validation_message[ $id ] = apply_filters(
					'forminator_checkbox_field_nonexistent_validation_message',
					esc_html__( 'Selected value does not exist.', 'forminator' ),
					$id,
					$field
				);
				break;
			}
		}
		if ( $this->is_required( $field ) ) {
			$required_message = self::get_property( 'required_message', $field, esc_html( $this->get_required_error_message() ) );
			if ( empty( $data ) ) {
				$slug = ! empty( $field['original_id'] ) ? $field['original_id'] : $id;

				$this->validation_message[ $slug ] = apply_filters(
					'forminator_multi_field_required_validation_message',
					$required_message,
					$id,
					$field
				);
			}

			$enable_custom_option = self::get_property( 'enable_custom_option', $field, false );
			if ( ! empty( $data ) && in_array( 'custom_option', $data, true ) && $enable_custom_option ) {
				$custom_value_required_message = self::get_property( 'custom_value_error_message', $field, '' );
				$custom_value                  = Forminator_CForm_Front_Action::$prepared_data[ 'custom-' . $id ] ?? '';
				if ( trim( $custom_value ) === '' ) {
					// For cloned fields, use the original ID.
					$custom_input_name                              = empty( $field['original_id'] ) ? 'custom-' . $id : 'custom-' . $field['original_id'];
					$this->validation_message[ $custom_input_name ] = apply_filters(
						'forminator_custom_value_field_required_validation_message',
						( ! empty( $custom_value_required_message ) ? esc_html( $custom_value_required_message ) : esc_html__( 'Please, enter a custom value', 'forminator' ) ),
						$custom_input_name,
						$field
					);
				}
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
		if ( is_array( $data ) ) {
			foreach ( $data as $key => $val ) {
				$data[ $key ] = trim( wp_kses_post( $val ) );
			}
		} else {
			$data = trim( wp_kses_post( $data ) );
		}

		return apply_filters( 'forminator_field_multi_sanitize', $data, $field, $original_data );
	}

	/**
	 * Internal calculable value
	 *
	 * @since 1.7
	 *
	 * @param array $submitted_field Submitted field.
	 * @param array $field_settings Field settings.
	 *
	 * @return float|string
	 */
	private static function calculable_value( $submitted_field, $field_settings ) {
		$enabled = self::get_property( 'calculations', $field_settings, false, 'bool' );
		if ( ! $enabled ) {
			return self::FIELD_NOT_CALCULABLE;
		}

		$sums = 0.0;

		$options = self::get_property( 'options', $field_settings, array() );

		if ( ! is_array( $submitted_field ) ) {
			return $sums;
		}

		foreach ( $options as $option ) {
			$option_value      = ( isset( $option['value'] ) && ! empty( $option['value'] ) ) ? $option['value'] : ( isset( $option['label'] ) ? $option['label'] : '' );
			$calculation_value = isset( $option['calculation'] ) ? $option['calculation'] : 0.0;

			forminator_maybe_log( __METHOD__, $option_value, $submitted_field );

			// strict array compare disabled to allow non-coercion type compare.
			if ( in_array( $option_value, $submitted_field ) ) { // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
				// this one is selected.
				$sums += floatval( $calculation_value );
			}
		}

		return self::get_calculable_number_format( $field_settings, $sums );
	}

	/**
	 * Get calculable value
	 *
	 * @since 1.7
	 * @inheritdoc
	 *
	 * @param array $submitted_field_data Submitted field data.
	 * @param array $field_settings Field settings.
	 */
	public static function get_calculable_value( $submitted_field_data, $field_settings ) {
		$calculable_value = self::calculable_value( $submitted_field_data, $field_settings );
		/**
		 * Filter formula being used on calculable value on multi-value / checkbox field
		 *
		 * @since 1.7
		 *
		 * @param float $calculable_value
		 * @param array $submitted_field_data
		 * @param array $field_settings
		 *
		 * @return string|int|float
		 */
		$calculable_value = apply_filters( 'forminator_field_multi_calculable_value', $calculable_value, $submitted_field_data, $field_settings );

		return $calculable_value;
	}
}
