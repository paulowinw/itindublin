<?php
/**
 * The Forminator_Radio class.
 *
 * @package Forminator
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_SingleValue
 *
 * @property  array field
 * @since 1.0
 */
class Forminator_Radio extends Forminator_Field {

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
	public $slug = 'radio';

	/**
	 * Type
	 *
	 * @var string
	 */
	public $type = 'radio';

	/**
	 * Position
	 *
	 * @var int
	 */
	public $position = 9;

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
	public $icon = 'sui-icon-element-radio';

	/**
	 * Is calculable
	 *
	 * @var bool
	 */
	public $is_calculable = true;

	/**
	 * Forminator_SingleValue constructor.
	 *
	 * @since 1.0
	 */
	public function __construct() {
		parent::__construct();

		$this->name = esc_html__( 'Radio', 'forminator' );
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
			'value_type'  => 'radio',
			'field_label' => esc_html__( 'Radio', 'forminator' ),
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
			'select' => array(
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

		$i                = 1;
		$html             = '';
		$id               = self::get_property( 'element_id', $field );
		$name             = $id;
		$id               = 'forminator-field-' . $id;
		$required         = self::get_property( 'required', $field, false );
		$ariareq          = 'false';
		$options          = self::get_options( $field );
		$value_type       = isset( $field['value_type'] ) ? trim( $field['value_type'] ) : 'multiselect';
		$post_value       = self::get_post_data( $name, false );
		$description      = self::get_property( 'description', $field, '' );
		$label            = esc_html( self::get_property( 'field_label', $field, '' ) );
		$design           = $this->get_form_style( $settings );
		$calc_enabled     = self::get_property( 'calculations', $field, false, 'bool' );
		$images_enabled   = self::get_property( 'enable_images', $field, false );
		$images_enabled   = filter_var( $images_enabled, FILTER_VALIDATE_BOOLEAN );
		$input_visibility = self::get_property( 'input_visibility', $field, 'true' );
		$input_visibility = filter_var( $input_visibility, FILTER_VALIDATE_BOOLEAN );
		$hidden_behavior  = self::get_property( 'hidden_behavior', $field );
		$descr_position   = self::get_description_position( $field, $settings );

		$prefil_valid = false;
		$draft_valid  = false;
		$post_valid   = false;
		$default      = '';
		$uniq_id      = Forminator_CForm_Front::$uid;
		$descr_id     = 'forminator-radiogroup-' . $name . '-' . $uniq_id;

		if ( (bool) $required ) {
			$ariareq = 'true';
		}

		$hidden_calc_behavior = '';
		if ( $hidden_behavior && 'zero' === $hidden_behavior ) {
			$hidden_calc_behavior = ' data-hidden-behavior="' . $hidden_behavior . '"';
		}

		$html .= sprintf(
			'<div role="radiogroup" class="forminator-field" aria-labelledby="%s"%s>',
			esc_attr( $descr_id . '-label' ),
			( ! empty( $description ) ? ' aria-describedby="' . esc_attr( $descr_id . '-description' ) . '"' : '' )
		);

		if ( $label ) {
			$label = self::convert_markdown( $label );
			if ( $required ) {
				$html .= sprintf(
					'<span id="%s" class="forminator-label">%s %s</span>',
					$descr_id . '-label',
					$label,
					forminator_get_required_icon()
				);
			} else {
				$html .= sprintf(
					'<span id="%s" class="forminator-label">%s</span>',
					$descr_id . '-label',
					$label
				);
			}
		}

		if ( 'above' === $descr_position ) {
			$html .= self::get_description( $description, $descr_id, $descr_position );
		}

		foreach ( $options as $option ) {
			$pref_value = ( $option['value'] || is_numeric( $option['value'] ) ? esc_html( $option['value'] ) : esc_html( $option['label'] ) );
			if ( isset( $draft_value['value'] ) ) {
				if ( trim( $draft_value['value'] ) === trim( $pref_value ) ) {
					$draft_valid = true;
					$default     = $pref_value;
				}
			}

			if ( $this->has_prefill( $field ) ) {
				// We have pre-fill parameter, use its value or $value.
				$prefill = $this->get_prefill( $field, false );
				if ( $prefill === $pref_value ) {
					$prefil_valid = true;
					$default      = $pref_value;
				}
			}

			if ( $pref_value === $post_value ) {
				$default    = $pref_value;
				$post_valid = true;
			}
		}

		foreach ( $options as $option ) {
			$input_id          = $id . '-' . $i . '-' . $uniq_id;
			$value             = ( $option['value'] || is_numeric( $option['value'] ) ? esc_html( $option['value'] ) : esc_html( $option['label'] ) );
			$option_default    = isset( $option['default'] ) ? filter_var( $option['default'], FILTER_VALIDATE_BOOLEAN ) : false;
			$calculation_value = $calc_enabled && isset( $option['calculation'] ) ? $option['calculation'] : 0.0;
			$option_image_url  = array_key_exists( 'image', $option ) ? $option['image'] : '';
			$option_selected   = false;
			$option_label      = sprintf(
				'<span class="forminator-radio-label">%s</span>',
				wp_kses(
					$option['label'],
					array(
						'a'      => array(
							'href'  => array(),
							'title' => array(),
						),
						'span'   => array(
							'class' => array(),
						),
						'b'      => array(),
						'i'      => array(),
						'br'     => array(),
						'em'     => array(),
						'strong' => array(),
					)
				)
			);
			$aria_label        = sprintf(
				'<span class="forminator-screen-reader-only">%s</span>',
				wp_kses(
					$option['label'],
					array(
						'a'      => array(
							'href'  => array(),
							'title' => array(),
						),
						'span'   => array(
							'class' => array(),
						),
						'b'      => array(),
						'i'      => array(),
						'br'     => array(),
						'em'     => array(),
						'strong' => array(),
					)
				)
			);

			$class = 'forminator-radio';

			if ( $images_enabled && ! empty( $option_image_url ) ) {

				$class .= ' forminator-has_image';

				if ( $input_visibility ) {
					$class .= ' forminator-has_bullet';
				}
			}

			if ( 'horizontal' === self::get_property( 'layout', $field, '' ) ) {
				$class .= ' forminator-radio-inline';
			}

			if ( $post_valid ) {
				if ( $value === $post_value ) {
					$option_selected = true;
				}
			} elseif ( $draft_valid ) {
				if ( $value === $default ) {
					$option_selected = true;
				}
			} elseif ( $prefil_valid ) {
				if ( $value === $default ) {
					$option_selected = true;
				}
			} elseif ( $option_default ) {
				$option_selected = true;
			}

			$selected = $option_selected ? 'checked="checked"' : '';
			$label_id = $id . '-label-' . $i;

			$html .= '<label id="' . esc_attr( $label_id ) . '" for="' . esc_attr( $input_id ) . '" class="' . esc_attr( $class ) . '" title="' . esc_attr( $option['label'] ) . '">';

				$html .= sprintf(
					'<input type="radio" name="%s" value="%s" id="%s" aria-labelledby="%s" data-calculation="%s" %s %s%s/>',
					$name,
					$value,
					$input_id,
					$label_id,
					$calculation_value,
					$selected,
					$hidden_calc_behavior,
					( ! empty( $description ) ? ' aria-describedby="' . esc_attr( $id . '-' . $uniq_id . '-description' ) . '"' : '' )
				);

			if ( $input_visibility && ( $images_enabled && ! empty( $option_image_url ) ) ) {

				// Bullet + Label.
				$html .= '<span class="forminator-radio-bullet" aria-hidden="true"></span>';
				$html .= $option_label;

				// Image.
				if ( 'none' === $design ) {
					$html .= '<img class="forminator-radio-image" src="' . esc_url( $option_image_url ) . '" aria-hidden="true" />';
				} else {
					$html     .= '<span class="forminator-radio-image" aria-hidden="true">';
						$html .= '<span style="background-image: url(' . esc_url( $option_image_url ) . ');"></span>';
					$html     .= '</span>';
				}
			} elseif ( ! $input_visibility && ( $images_enabled && ! empty( $option_image_url ) ) ) {

				// Image.
				if ( 'none' === $design ) {
					$html .= '<img class="forminator-radio-image" src="' . esc_url( $option_image_url ) . '" aria-hidden="true" />';
				} else {
					$html     .= '<span class="forminator-radio-image" aria-hidden="true">';
						$html .= '<span style="background-image: url(' . esc_url( $option_image_url ) . ');"></span>';
					$html     .= '</span>';
				}

				// Aria Label.
				$html .= $aria_label;

			} else {

				// Bullet + Label.
				$html .= '<span class="forminator-radio-bullet" aria-hidden="true"></span>';
				$html .= $option_label;

			}

			$html .= '</label>';

			++$i;

		}

		$custom_input_id         = $id . '-' . ( $i - 1 ) . '-' . $uniq_id;
		$input_labelledby        = $id . '-label-' . ( $i - 1 );
		$custom_input_attributes = array(
			'id'              => 'custom-' . $custom_input_id,
			'name'            => 'custom-' . $name,
			'aria-labelledby' => $input_labelledby,
		);
		$html                   .= self::maybe_add_custom_option( $field, $options, $custom_input_attributes, $draft_value );

		if ( 'above' !== $descr_position ) {
			$html .= self::get_description( $description, $descr_id, $descr_position );
		}

		$html .= '</div>';

		return apply_filters( 'forminator_field_single_markup', $html, $id, $required, $options, $value_type );
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
			$rules .= '"' . $this->get_id( $field ) . '": "required",';

			$enable_custom_option = self::get_property( 'enable_custom_option', $field, false );
			if ( $enable_custom_option ) {
				$rules .= '"custom-' . $this->get_id( $field ) . '": {' . "\n";
				$rules .= '"customInputForOtherOption": "radio",';
				$rules .= '},' . "\n";
			}
		}

		return apply_filters( 'forminator_field_single_validation_rules', $rules, $id, $field );
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
				'forminator_single_field_required_validation_message',
				$required_message,
				$id,
				$field
			);
			$messages        .= '"' . $this->get_id( $field ) . '": "' . forminator_addcslashes( $required_message ) . '",' . "\n";

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
		if ( ! empty( $data ) && false === array_search( strval( htmlspecialchars_decode( $data ) ), array_map( 'strval', array_column( $field['options'], 'value' ) ), true ) ) {
			$this->validation_message[ $id ] = apply_filters(
				'forminator_radio_field_nonexistent_validation_message',
				esc_html__( 'Selected value does not exist.', 'forminator' ),
				$id,
				$field
			);
		}
		if ( $this->is_required( $field ) ) {
			$required_message = self::get_property( 'required_message', $field, esc_html( $this->get_required_error_message() ) );
			if ( empty( $data ) && '0' !== $data ) {
				$this->validation_message[ $id ] = apply_filters(
					'forminator_single_field_required_validation_message',
					$required_message,
					$id,
					$field
				);
			}

			$enable_custom_option = self::get_property( 'enable_custom_option', $field, false );
			if ( ! empty( $data ) && 'custom_option' === $data && $enable_custom_option ) {
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

		/*
		* Field sanitization has been moved to library\abstracts\abstract-class-front-action.php > get_post_data > Forminator_Core::sanitize_array
		* Due to members' request to allow html, we now use wp_kses_post for sanitization of this field

		// Sanitize.
		if ( is_array( $data ) ) {
			$data = forminator_sanitize_array_field( $data );
		} else {
			$data = forminator_sanitize_field( $data );
		}
		*/
		return apply_filters( 'forminator_field_single_sanitize', $data, $field, $original_data );
	}

	/**
	 * Internal Calculable value
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
			// process as array.
			$submitted_field = array( $submitted_field );
		}

		if ( ! is_array( $submitted_field ) ) {
			return $sums;
		}

		foreach ( $options as $option ) {
			$option_value      = isset( $option['value'] ) ? $option['value'] : ( isset( $option['label'] ) ? $option['label'] : '' );
			$calculation_value = isset( $option['calculation'] ) ? $option['calculation'] : 0.0;

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
	 * @param array $submitted_field_data Submitted field data.
	 * @param array $field_settings Field settings.
	 */
	public static function get_calculable_value( $submitted_field_data, $field_settings ) {
		$calculable_value = self::calculable_value( $submitted_field_data, $field_settings );
		/**
		 * Filter formula being used on calculable value on radio field
		 *
		 * @since 1.7
		 *
		 * @param float $calculable_value
		 * @param array $submitted_field_data
		 * @param array $field_settings
		 *
		 * @return string|int|float
		 */
		$calculable_value = apply_filters( 'forminator_field_radio_calculable_value', $calculable_value, $submitted_field_data, $field_settings );

		return $calculable_value;
	}
}
