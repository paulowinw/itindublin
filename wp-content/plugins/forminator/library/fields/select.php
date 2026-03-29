<?php
/**
 * The Forminator_Select class.
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
class Forminator_Select extends Forminator_Field {

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
	public $slug = 'select';

	/**
	 * Type
	 *
	 * @var string
	 */
	public $type = 'select';

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
	 * Icon
	 *
	 * @var string
	 */
	public $icon = 'sui-icon-element-select';

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

		$this->name = esc_html__( 'Select', 'forminator' );
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
			'value_type'        => 'single',
			'field_label'       => esc_html__( 'Select', 'forminator' ),
			'options'           => array(
				array(
					'label' => esc_html__( 'Option 1', 'forminator' ),
					'value' => 'one',
					'limit' => '',
					'key'   => forminator_unique_key(),
				),
				array(
					'label' => esc_html__( 'Option 2', 'forminator' ),
					'value' => 'two',
					'limit' => '',
					'key'   => forminator_unique_key(),
				),
			),
			'multiselect_style' => 'modern',
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

		$i             = 1;
		$html          = '';
		$id            = self::get_property( 'element_id', $field );
		$name          = $id;
		$uniq_id       = Forminator_CForm_Front::$uid;
		$form_id       = $settings['form_id'];
		$id            = 'forminator-form-' . $form_id . '__field--' . $id . '_' . $uniq_id;
		$required      = self::get_property( 'required', $field, false, 'bool' );
		$options       = self::get_options( $field );
		$post_value    = self::get_post_data( $name, false );
		$description   = self::get_property( 'description', $field, '' );
		$label         = esc_html( self::get_property( 'field_label', $field, '' ) );
		$design        = $this->get_form_style( $settings );
		$field_type    = self::get_property( 'value_type', $field, '' );
		$search_status = self::get_property( 'search_status', $field, '' );
		$is_limit      = self::get_property( 'limit_status', $field, '' );
		$placeholder   = esc_html( self::get_property( 'placeholder', $field, '' ) );
		$calc_enabled  = self::get_property( 'calculations', $field, false, 'bool' );
		$field_style   = self::get_property( 'multiselect_style', $field, 'standard' );
		$draft_values  = $draft_value;

		$hidden_behavior      = self::get_property( 'hidden_behavior', $field );
		$checkbox_in_dropdown = self::get_property( 'checkbox_in_dropdown', $field, 'hide' );
		$descr_position       = self::get_description_position( $field, $settings );
		$custom_input_name    = $name;

		$html .= '<div class="forminator-field">';

		$html .= self::get_field_label( $label, $id, $required );

		if ( 'above' === $descr_position ) {
			$html .= self::get_description( $description, $id, $descr_position );
		}

		if ( $required && empty( $placeholder ) ) {
			$placeholder = esc_html__( 'Please select an option', 'forminator' );
		}

		$hidden_calc_behavior = '';
		if ( $hidden_behavior && 'zero' === $hidden_behavior ) {
			$hidden_calc_behavior = ' data-hidden-behavior="' . $hidden_behavior . '"';
		}

		if ( 'multiselect' === $field_type && 'standard' === $field_style ) {
			$post_value  = self::get_post_data( $name, self::FIELD_PROPERTY_VALUE_NOT_EXIST );
			$field_name  = $name;
			$name        = $name . '[]';
			$draft_value = isset( $draft_value['value'] ) ? (array) $draft_value['value'] : array();
			$draft_value = array_map( 'trim', $draft_value );

			$html .= sprintf(
				'<div class="forminator-multiselect" aria-labelledby="%s"%s>',
				esc_attr( $id . '-label' ),
				( ! empty( $description ) ? ' aria-describedby="' . esc_attr( $id . '-description' ) . '"' : '' )
			);

			// Multi values.
			$default_arr  = array();
			$default      = '';
			$prefill      = false;
			$prefil_valid = false;
			$draft_valid  = false;

			// Check if Pre-fill parameter used.
			if ( $this->has_prefill( $field ) ) {
				// We have pre-fill parameter, use its value or $value.
				$prefill = $this->get_prefill( $field, $prefill );
			}

			foreach ( $options as $key => $option ) {
				$pref_value  = $option['value'] ? esc_html( wp_strip_all_tags( $option['value'] ) ) : wp_kses_post( wp_strip_all_tags( $option['label'] ) );
				$pref_values = explode( ',', $prefill );
				if ( in_array( strval( $pref_value ), array_map( 'strval', $pref_values ), true ) ) {
					$prefil_valid  = true;
					$default_arr[] = $pref_value;
				}

				if ( ! empty( $draft_value ) ) {
					if ( in_array( trim( $pref_value ), $draft_value ) ) { // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
						$draft_valid   = true;
						$default_arr[] = $pref_value;
					}
				}
			}

			foreach ( $options as $key => $option ) {

				$value             = $option['value'] ? esc_html( wp_strip_all_tags( $option['value'] ) ) : '';
				$input_id          = $id . '-' . $i;
				$option_default    = isset( $option['default'] ) ? filter_var( $option['default'], FILTER_VALIDATE_BOOLEAN ) : false;
				$calculation_value = $calc_enabled && isset( $option['calculation'] ) ? $option['calculation'] : 0.0;
				$selected          = false;

				// Skip options with empty values.
				if ( '' === $value ) {
					continue;
				}

				if ( isset( $is_limit ) && 'enable' === $is_limit
					&& Forminator_Form_Entry_Model::is_option_limit_reached( $form_id, $field_name, $field_type, $option ) ) {
					continue;
				}

				if ( self::FIELD_PROPERTY_VALUE_NOT_EXIST !== $post_value ) {
					if ( is_array( $post_value ) ) {
						$selected = in_array( $value, $post_value ); // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
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

				if ( $option_default && ! $prefil_valid && ! $draft_valid ) {
					$default_arr[] = $value;
				}

				$selected    = $selected ? 'checked="checked"' : '';
				$extra_class = $selected ? ' forminator-is_checked' : '';

				$class = 'forminator-option' . $extra_class;

				$label_id = $input_id . '-label';

				$html .= sprintf( '<label id="' . $label_id . '" for="%s" class="' . $class . '">', $input_id );

				$html .= sprintf(
					'<input type="checkbox" name="%s" value="%s" id="%s" aria-labelledby="%s" data-calculation="%s" %s %s />',
					$name,
					$value,
					$input_id,
					$label_id,
					$calculation_value,
					$hidden_calc_behavior,
					$selected
				);

				$html .= wp_kses_post( wp_strip_all_tags( $option['label'] ) );

				$html .= '</label>';

				++$i;
			}

			if ( ! empty( $default_arr ) ) {
				$default = wp_json_encode( $default_arr, JSON_FORCE_OBJECT );
			}

			$html .= sprintf(
				"<input type='hidden' name='%s' class='%s' value='%s' />",
				$field_name . '-multiselect-default-values',
				'multiselect-default-values',
				$default
			);

			$html .= '</div>';

		} else {
			$default_arr    = array();
			$options_markup = '';
			$default        = '';
			$search         = 'false';

			$draft_valid  = false;
			$post_valid   = false;
			$prefil_valid = false;

			if ( 'enable' === $search_status ) {
				$search = 'true';
			}

			if ( ! empty( $placeholder ) ) {
				switch ( $field_type ) {
					case 'multiselect':
						if ( 'none' !== $design ) {
							break;
						}
						$options_markup = sprintf( '<option value="" disabled>%s</option>', $placeholder );
						break;

					default:
						$selected       = ( 'none' === $design ) ? 'selected' : '';
						$disabled       = ( 'none' === $design ) ? 'disabled' : '';
						$options_markup = sprintf( '<option value="" %s %s>%s</option>', $disabled, $selected, $placeholder );
						break;
				}
			}

			foreach ( $options as $key => $option ) {
				$pref_value = ( $option['value'] || is_numeric( $option['value'] ) ? esc_html( wp_strip_all_tags( $option['value'] ) ) : '' );
				if ( isset( $draft_value['value'] ) ) {
					if ( is_array( $draft_value['value'] ) ) {
						if ( in_array( trim( $pref_value ), $draft_value['value'], true ) ) {
							$draft_valid   = true;
							$default_arr[] = $pref_value;
						}
					} elseif ( trim( $draft_value['value'] ) === trim( $pref_value ) ) {
						$draft_valid = true;
						$default     = $pref_value;
					}
				}

				if ( $this->has_prefill( $field ) ) {
					// We have pre-fill parameter, use its value or $value.
					$prefill        = $this->get_prefill( $field, false );
					$prefill_values = explode( ',', $prefill );

					if ( in_array( $pref_value, $prefill_values ) ) { // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
						$default      = $pref_value;
						$prefil_valid = true;
					}
				}

				if ( $pref_value === $post_value ) {
					$default    = $pref_value;
					$post_valid = true;
				}
			}

			foreach ( $options as $key => $option ) {
				$value             = ( $option['value'] || is_numeric( $option['value'] ) ? esc_html( wp_strip_all_tags( $option['value'] ) ) : '' );
				$option_default    = isset( $option['default'] ) ? filter_var( $option['default'], FILTER_VALIDATE_BOOLEAN ) : false;
				$calculation_value = $calc_enabled && isset( $option['calculation'] ) ? esc_html( $option['calculation'] ) : 0.0;
				$option_selected   = false;

				// Skip options with empty values.
				if ( '' === $value ) {
					continue;
				}

				if ( isset( $is_limit ) && 'enable' === $is_limit
						&& Forminator_Form_Entry_Model::is_option_limit_reached( $form_id, $name, $field_type, $option ) ) {
					continue;
				}

				if ( $option_default && ! $draft_valid && ! $prefil_valid && ! $post_valid ) {
					$default = $value;
				}

				if ( $post_valid ) {
					if ( $value === $post_value ) {
						$option_selected = true;
					}
				} elseif ( $draft_valid ) {
					if ( ! empty( $default_arr ) && in_array( $value, $default_arr, true ) ) {
						$option_selected = true;
					} elseif ( $value === $default ) {
						$option_selected = true;
					}
				} elseif ( $prefil_valid ) {
					if ( $value === $default ) {
						$option_selected = true;
					}
				} elseif ( $option_default ) {
					$option_selected = true;
				}

				$selected = $option_selected ? 'selected="selected"' : '';

				$options_markup .= sprintf(
					'<option value="%s" %s data-calculation="%s">%s</option>',
					esc_html( $value ),
					$selected,
					esc_html( $calculation_value ),
					wp_kses_post( wp_strip_all_tags( $option['label'] ) )
				);
			}

			$select_type        = '';
			$has_checkbox       = 'false';
			$allow_clear        = 'false';
			$search_placeholder = $placeholder;

			if ( 'multiselect' === $field_type && 'modern' === $field_style ) {
				$select_type        = 'multiple';
				$name               = $name . '[]';
				$allow_clear        = 'true';
				$search_placeholder = 'Search';

				if ( 'show' === $checkbox_in_dropdown ) {
					$has_checkbox = 'true';
				}
			}
			$html .= sprintf(
				'<select %s id="%s" class="%s" data-required="%s" name="%s" data-default-value="%s"%s data-placeholder="%s" data-search="%s" data-search-placeholder="%s" data-checkbox="%s" data-allow-clear="%s" aria-labelledby="%s"%s>',
				$select_type,
				$id,
				'forminator-select--field forminator-select2 forminator-select2-multiple', // class.
				$required,
				$name,
				$default,
				$hidden_calc_behavior,
				esc_attr( wp_strip_all_tags( html_entity_decode( $placeholder ) ) ),
				$search,
				$search_placeholder,
				$has_checkbox,
				$allow_clear,
				esc_attr( $id . '-label' ),
				( ! empty( $description ) ? ' aria-describedby="' . esc_attr( $id . '-description' ) . '"' : '' )
			);

			$html .= $options_markup;

			$html .= sprintf( '</select>' );
		}

		$custom_input_attributes = array(
			'id'   => 'custom-' . $id,
			'name' => 'custom-' . $custom_input_name,
		);
		$html                   .= self::maybe_add_custom_option( $field, $options, $custom_input_attributes, $draft_values );

		if ( 'above' !== $descr_position ) {
			$html .= self::get_description( $description, $id, $descr_position );
		}

		$html .= '</div>';

		return apply_filters( 'forminator_field_single_markup', $html, $id, $required, $options );
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
		$field_type  = self::get_property( 'value_type', $field, '' );

		if ( $is_required && 'multiselect' !== $field_type ) {
			$rules .= '"' . $this->get_id( $field ) . '": "required",';
		}

		if ( $is_required && 'multiselect' === $field_type ) {
			$rules .= '"' . $this->get_id( $field ) . '[]": "required",';
		}

		$enable_custom_option = self::get_property( 'enable_custom_option', $field, false );
		if ( $enable_custom_option && $is_required ) {
			$field_style = self::get_property( 'multiselect_style', $field, 'standard' );
			if ( 'multiselect' !== $field_type ) {
				$rule_param = 'single-select';
			} elseif ( 'modern' === $field_style ) {
				$rule_param = 'multi-select';
			} else {
				$rule_param = 'checkbox';
			}
			$rules .= '"custom-' . $this->get_id( $field ) . '": {' . "\n";
			$rules .= '"customInputForOtherOption": "' . $rule_param . '",';
			$rules .= '},' . "\n";
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
		$field_type  = self::get_property( 'value_type', $field, '' );

		if ( $is_required ) {
			$required_message = self::get_property( 'required_message', $field, $this->get_required_error_message() );
			$required_message = apply_filters(
				'forminator_single_field_required_validation_message',
				$required_message,
				$id,
				$field
			);

			if ( 'multiselect' === $field_type ) {
				$messages .= '"' . $this->get_id( $field ) . '[]": "' . forminator_addcslashes( $required_message ) . '",' . "\n";
			} else {
				$messages .= '"' . $this->get_id( $field ) . '": "' . forminator_addcslashes( $required_message ) . '",' . "\n";
			}

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
	 * Are submitted options reached limit
	 *
	 * @param array $field Field settings.
	 * @param array $selected_options Submitted select options.
	 * @return bool
	 */
	private static function options_reached_limit( $field, $selected_options ) {
		$is_limit = self::get_property( 'limit_status', $field );
		// Skip if this field is not limitted.
		if ( ! isset( $is_limit ) || 'enable' !== $is_limit ) {
			return false;
		}

		$field_name = self::get_property( 'element_id', $field );
		$field_type = self::get_property( 'value_type', $field );
		$form_id    = Forminator_CForm_Front_Action::$module_id;

		foreach ( $field['options'] as $option ) {
			// Ski if this option was not selected.
			if ( ! in_array( $option['value'], $selected_options, true ) ) {
				continue;
			}
			if ( Forminator_Form_Entry_Model::is_option_limit_reached( $form_id, $field_name, $field_type, $option, true ) ) {
				return true;
			}
		}

		return false;
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
		$select_type  = isset( $field['value_type'] ) ? $field['value_type'] : 'single';
		$id           = self::get_property( 'element_id', $field );
		$value_exists = true;

		if ( is_array( $data ) ) {
			foreach ( $data as $value ) {
				if ( false === array_search( strval( htmlspecialchars_decode( $value ) ), array_map( 'strval', array_column( $field['options'], 'value' ) ), true ) ) {
					$value_exists = false;
					break;
				}
			}
		} elseif ( ! empty( $data ) && false === array_search( strval( htmlspecialchars_decode( $data ) ), array_map( 'strval', array_column( $field['options'], 'value' ) ), true ) ) {
			$value_exists = false;
		}

		if ( ! $value_exists ) {
			$this->validation_message[ $id ] = apply_filters(
				'forminator_select_field_nonexistent_validation_message',
				esc_html__( 'Selected value does not exist.', 'forminator' ),
				$id,
				$field
			);
		}

		// Check if select options is reached limit.
		$value_limited = self::options_reached_limit( $field, (array) $data );
		if ( $value_limited ) {
			$this->validation_message[ $id ] = apply_filters(
				'forminator_select_field_nonavailable_validation_message',
				esc_html__( 'The selected option is no longer available.', 'forminator' ),
				$id,
				$field
			);
		}

		if ( $this->is_required( $field ) ) {
			if ( ! isset( $data ) ||
				( 'single' === $select_type && $this->is_single_select_empty( $data ) ) ||
				( 'multiselect' === $select_type && empty( $data ) )
			) {
				$required_message                = self::get_property( 'required_message', $field, esc_html( $this->get_required_error_message() ) );
				$this->validation_message[ $id ] = apply_filters(
					'forminator_single_field_required_validation_message',
					$required_message,
					$id,
					$field
				);
			}

			$enable_custom_option = self::get_property( 'enable_custom_option', $field, false );
			if ( ! empty( $data ) && $enable_custom_option &&
					( ( 'single' === $select_type && 'custom_option' === $data ) ||
					( 'multiselect' === $select_type && in_array( 'custom_option', $data, true ) ) )
				) {
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
	 * Check is simple select empty.
	 *
	 * @param mixed $data Value.
	 * @return bool
	 */
	private function is_single_select_empty( $data ) {
		if ( is_array( $data ) ) {
			$data = implode( '', $data );
		}
		if ( is_string( $data ) && ! strlen( $data ) ) {
			return true;
		}
		return false;
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

		return apply_filters( 'forminator_field_single_sanitize', $data, $field, $original_data );
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

		$field_type = self::get_property( 'value_type', $field_settings, '' );
		$options    = self::get_property( 'options', $field_settings, array() );
		$is_single  = 'multiselect' !== $field_type;

		if ( $is_single && ! is_array( $submitted_field ) ) {
			// process as array.
			$submitted_field = array( $submitted_field );
		}
		if ( ! is_array( $submitted_field ) ) {
			return $sums;
		}

		foreach ( $options as $option ) {
			$option_value      = isset( $option['value'] ) ? $option['value'] : ( isset( $option['label'] ) ? $option['label'] : '' );
			$option_value      = trim( $option_value );
			$calculation_value = isset( $option['calculation'] ) ? $option['calculation'] : 0.0;

			// strict array compare disabled to allow non-coercion type compare.
			$first_key = array_search( $option_value, $submitted_field, true );
			if ( false !== $first_key ) {
				// this one is selected.
				$sums += floatval( $calculation_value );
				unset( $submitted_field[ $first_key ] );
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
		 * Filter formula being used on calculable value on select field
		 *
		 * @since 1.7
		 *
		 * @param float $calculable_value
		 * @param array $submitted_field_data
		 * @param array $field_settings
		 *
		 * @return string|int|float
		 */
		$calculable_value = apply_filters( 'forminator_field_select_calculable_value', $calculable_value, $submitted_field_data, $field_settings );

		return $calculable_value;
	}
}
