<?php
/**
 * The Forminator_Name class.
 *
 * @package Forminator
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_Name
 *
 * @since 1.0
 */
class Forminator_Name extends Forminator_Field {

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
	public $slug = 'name';

	/**
	 * Type
	 *
	 * @var string
	 */
	public $type = 'name';

	/**
	 * Position
	 *
	 * @var int
	 */
	public $position = 1;

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
	public $icon = 'sui-icon-profile-male';

	/**
	 * Forminator_Name constructor.
	 *
	 * @since 1.0
	 */
	public function __construct() {
		parent::__construct();
		$this->name = esc_html__( 'Name', 'forminator' );

		// Set default required error messages.
		$required       = __( 'This field is required. Please input your name.', 'forminator' );
		$fname_required = __( 'This field is required. Please input your first name.', 'forminator' );
		$mname_required = __( 'This field is required. Please input your middle name.', 'forminator' );
		$lname_required = __( 'This field is required. Please input your last name.', 'forminator' );

		self::$default_required_messages[ $this->type ]            = $required;
		self::$default_required_messages[ 'fname_' . $this->type ] = $fname_required;
		self::$default_required_messages[ 'mname_' . $this->type ] = $mname_required;
		self::$default_required_messages[ 'lname_' . $this->type ] = $lname_required;
	}

	/**
	 * Field defaults
	 *
	 * @since 1.0
	 * @return array
	 */
	public function defaults() {
		return array(
			'field_label'    => esc_html__( 'Name', 'forminator' ),
			'prefix_label'   => esc_html__( 'Prefix', 'forminator' ),
			'fname_label'    => esc_html__( 'First Name', 'forminator' ),
			'mname_label'    => esc_html__( 'Middle Name', 'forminator' ),
			'lname_label'    => esc_html__( 'Last Name', 'forminator' ),
			'prefix'         => 'true',
			'fname'          => 'true',
			'mname'          => 'true',
			'lname'          => 'true',
			'layout_columns' => '2',
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

		// single name.
		$name_providers = apply_filters( 'forminator_field_' . $this->slug . '_autofill', array(), $this->slug );

		// multi name.
		$prefix_providers = apply_filters( 'forminator_field_' . $this->slug . '_prefix_autofill', array(), $this->slug . '_prefix' );
		$fname_providers  = apply_filters( 'forminator_field_' . $this->slug . '_first_name_autofill', array(), $this->slug . '_first_name' );
		$mname_providers  = apply_filters( 'forminator_field_' . $this->slug . '_middle_name_autofill', array(), $this->slug . '_middle_name' );
		$lname_providers  = apply_filters( 'forminator_field_' . $this->slug . '_last_name_autofill', array(), $this->slug . '_last_name' );

		$autofill_settings = array(
			'name'             => array(
				'values' => forminator_build_autofill_providers( $name_providers ),
			),
			'name-prefix'      => array(
				'values' => forminator_build_autofill_providers( $prefix_providers ),
			),
			'name-first-name'  => array(
				'values' => forminator_build_autofill_providers( $fname_providers ),
			),
			'name-middle-name' => array(
				'values' => forminator_build_autofill_providers( $mname_providers ),
			),
			'name-last-name'   => array(
				'values' => forminator_build_autofill_providers( $lname_providers ),
			),
		);

		return $autofill_settings;
	}

	/**
	 * Return simple field markup
	 *
	 * @since 1.0
	 *
	 * @param array  $field Field.
	 * @param string $design Design.
	 * @param array  $draft_value Draft value.
	 *
	 * @return string
	 */
	public function get_simple( $field, $design, $draft_value = null ) {
		$html        = '';
		$name        = self::get_property( 'element_id', $field );
		$id          = self::get_field_id( $name );
		$required    = self::get_property( 'required', $field, false );
		$ariareq     = 'false';
		$label       = esc_html( self::get_property( 'field_label', $field, '' ) );
		$description = self::get_property( 'description', $field, '' );
		$placeholder = $this->sanitize_value( self::get_property( 'placeholder', $field ) );

		$descr_position   = self::get_property( 'descr_position', $field );
		$browser_autofill = self::get_property( 'browser_autofill', $field, 'enabled' );

		if ( (bool) $required ) {
			$ariareq = 'true';
		}

		$value = '';

		if ( isset( $draft_value['value'] ) ) {

			$value = esc_attr( $draft_value['value'] );

		} elseif ( $this->has_prefill( $field ) ) {

			// We have pre-fill parameter, use its value or $value.
			$value = $this->get_prefill( $field, $value );

		}

		$name_attr = array(
			'type'          => 'text',
			'name'          => $name,
			'value'         => $value,
			'placeholder'   => $placeholder,
			'id'            => $id,
			'class'         => 'forminator-input forminator-name--field',
			'aria-required' => $ariareq,
			'autocomplete'  => 'enabled' === $browser_autofill ? 'name' : 'off',
		);

		$autofill_markup = $this->get_element_autofill_markup_attr( $name );

		$name_attr = array_merge( $name_attr, $autofill_markup );

		$html .= '<div class="forminator-field">';

			$html .= self::create_input( $name_attr, $label, $description, $required, $descr_position );

		$html .= '</div>';

		return $html;
	}

	/**
	 * Return multi field first row markup
	 *
	 * @since 1.0
	 *
	 * @param array  $field Field.
	 * @param string $design Design.
	 * @param array  $draft_value Draft value.
	 *
	 * @return string
	 */
	public function get_multi_first_row( $field, $design, $draft_value = null ) {
		$html        = '';
		$cols        = 12;
		$id          = self::get_property( 'element_id', $field );
		$name        = $id;
		$required    = self::get_property( 'required', $field, false );
		$prefix      = self::get_property( 'prefix', $field, false );
		$fname       = self::get_property( 'fname', $field, false );
		$mname       = self::get_property( 'mname', $field, false );
		$lname       = self::get_property( 'lname', $field, false );
		$columns     = self::get_property( 'layout_columns', $field, false );
		$draft_value = isset( $draft_value['value'] ) ? $draft_value['value'] : '';

		$descr_position = self::get_property( 'descr_position', $field );

		// Return If prefix and first name, middle name and last name is not enabled.
		if ( empty( $prefix ) && empty( $fname ) && empty( $mname ) && empty( $lname ) ) {
			return '';
		}
		/**
		 * Backward compat, we dont have separate required configuration per fields
		 * Fallback value from global `required`
		 *
		 * @since 1.6
		 */
		$prefix_required = self::get_property( 'prefix_required', $field, false, 'bool' );
		$fname_required  = self::get_property( 'fname_required', $field, false, 'bool' );
		$fname_ariareq   = 'false';
		$mname_required  = self::get_property( 'mname_required', $field, false, 'bool' );
		$mname_ariareq   = 'false';
		$lname_required  = self::get_property( 'lname_required', $field, false, 'bool' );
		$lname_ariareq   = 'false';

		if ( (bool) self::get_property( 'fname_required', $field, false ) ) {
			$fname_ariareq = 'true';
		}

		if ( (bool) self::get_property( 'mname_required', $field, false ) ) {
			$mname_ariareq = 'true';
		}

		if ( (bool) self::get_property( 'lname_required', $field, false ) ) {
			$lname_ariareq = 'true';
		}

		// Columns in name field.
		switch ( $columns ) {
			case 1:
				$cols = 12;
				break;
			case 3:
				$cols = 4;
				break;
			case 4:
				$cols = 3;
				break;
			default:
				$cols = 6;
		}

		if ( 'default' === self::get_property( 'default_layout', $field, false ) ) {
			$cols = 6;
		}

		// START: Row.
		$html .= '<div class="forminator-row forminator-no-margin" data-multiple="true">';

			// FIELD: Prefix.
		if ( $prefix ) {
			$browser_autofill = self::get_property( 'prefix_browser_autofill', $field, 'enabled' );
			$prefix_data      = array(
				'name'         => self::get_subfield_id( $id, '-prefix' ),
				'id'           => self::get_field_id( $this->form_settings['form_id'] . '__field--' . $id ),
				'class'        => 'basic' === $design ? '' : 'forminator-select2',
				'data-multi'   => true,
				'autocomplete' => 'enabled' === $browser_autofill ? 'honorific-prefix' : 'off',
			);

			$options        = array();
			$value          = false;
			$prefix_options = forminator_get_name_prefixes();

			if ( isset( $draft_value['prefix'] ) ) {

				$value = esc_attr( $draft_value['prefix'] );

			} elseif ( $this->has_prefill( $field, 'prefix' ) ) {

				// We have pre-fill parameter, use its value or $value.
				$value = $this->get_prefill( $field, false, 'prefix' );
			}

			foreach ( $prefix_options as $key => $pfx ) {
				$selected = false;

				if ( strtolower( $key ) === strtolower( $value ) ) {
					$selected = true;
				}
				$options[] = array(
					'value'    => esc_html( $key ),
					'label'    => esc_html( $pfx ),
					'selected' => $selected,
				);
			}

			$html .= sprintf( '<div class="forminator-col forminator-col-md-%s">', $cols );

				$html .= '<div class="forminator-field">';

					$html .= self::create_select(
						$prefix_data,
						self::get_property( 'prefix_label', $field ),
						$options,
						self::get_property( 'prefix_placeholder', $field ),
						self::get_property( 'prefix_description', $field ),
						$prefix_required,
						$descr_position,
					);

				$html .= '</div>';

			$html .= '</div>';
		}

			// FIELD: First Name.
		if ( $fname ) {
			$browser_autofill = self::get_property( 'fname_browser_autofill', $field, 'enabled' );
			$first_name       = array(
				'type'          => 'text',
				'name'          => self::get_subfield_id( $id, '-first-name' ),
				'placeholder'   => $this->sanitize_value( self::get_property( 'fname_placeholder', $field ) ),
				'id'            => self::get_field_id( 'first-' . $id ),
				'class'         => 'forminator-input',
				'aria-required' => $fname_ariareq,
				'data-multi'    => true,
				'autocomplete'  => 'enabled' === $browser_autofill ? 'given-name' : 'off',
			);

			$autofill_markup = $this->get_element_autofill_markup_attr( self::get_subfield_id( $id, '-first-name' ) );

			$first_name = array_merge( $first_name, $autofill_markup );

			if ( isset( $draft_value['first-name'] ) ) {

				$first_name['value'] = $draft_value['first-name'];

			} elseif ( $this->has_prefill( $field, 'fname' ) ) {

				$first_name = $this->replace_from_prefill( $field, $first_name, 'fname' );

			}

			$html .= sprintf( '<div class="forminator-col forminator-col-md-%s">', $cols );

				$html .= '<div class="forminator-field">';

					$html .= self::create_input(
						$first_name,
						esc_html( self::get_property( 'fname_label', $field ) ),
						esc_html( self::get_property( 'fname_description', $field ) ),
						$fname_required,
						$descr_position,
					);

				$html .= '</div>';

			$html .= '</div>';
		}

			// FIELD: Middle Name.
		if ( $mname ) {
			$browser_autofill = self::get_property( 'mname_browser_autofill', $field, 'enabled' );
			$middle_name      = array(
				'type'          => 'text',
				'name'          => self::get_subfield_id( $id, '-middle-name' ),
				'placeholder'   => $this->sanitize_value( self::get_property( 'mname_placeholder', $field ) ),
				'id'            => self::get_field_id( 'middle-' . $id ),
				'class'         => 'forminator-input',
				'aria-required' => $mname_ariareq,
				'data-multi'    => true,
				'autocomplete'  => 'enabled' === $browser_autofill ? 'additional-name' : 'off',
			);

			if ( isset( $draft_value['middle-name'] ) ) {

				$middle_name['value'] = $draft_value['middle-name'];

			} elseif ( $this->has_prefill( $field, 'mname' ) ) {

				$middle_name = $this->replace_from_prefill( $field, $middle_name, 'mname' );

			}

			$html .= sprintf( '<div class="forminator-col forminator-col-md-%s">', $cols );

				$html .= '<div class="forminator-field">';

				$html .= self::create_input(
					$middle_name,
					esc_html( self::get_property( 'mname_label', $field ) ),
					esc_html( self::get_property( 'mname_description', $field ) ),
					$mname_required,
					$descr_position,
				);

				$html .= '</div>';

			$html .= '</div>';
		}

			// FIELD: Last Name.
		if ( $lname ) {
			$browser_autofill = self::get_property( 'lname_browser_autofill', $field, 'enabled' );
			$last_name        = array(
				'type'          => 'text',
				'name'          => self::get_subfield_id( $id, '-last-name' ),
				'placeholder'   => $this->sanitize_value( self::get_property( 'lname_placeholder', $field ) ),
				'id'            => self::get_field_id( 'last-' . $id ),
				'class'         => 'forminator-input',
				'aria-required' => $lname_ariareq,
				'data-multi'    => true,
				'autocomplete'  => 'enabled' === $browser_autofill ? 'family-name' : 'off',
			);

			$autofill_markup = $this->get_element_autofill_markup_attr( self::get_subfield_id( $id, '-last-name' ) );

			$last_name = array_merge( $last_name, $autofill_markup );

			if ( isset( $draft_value['last-name'] ) ) {

				$last_name['value'] = $draft_value['last-name'];

			} elseif ( $this->has_prefill( $field, 'lname' ) ) {

				$last_name = $this->replace_from_prefill( $field, $last_name, 'lname' );

			}

			$html .= sprintf( '<div class="forminator-col forminator-col-md-%s">', $cols );

			$html .= '<div class="forminator-field">';

				$html .= self::create_input(
					$last_name,
					esc_html( self::get_property( 'lname_label', $field ) ),
					esc_html( self::get_property( 'lname_description', $field ) ),
					$lname_required,
					$descr_position,
				);

				$html .= '</div>';

			$html .= '</div>';
		}

		// END: Row.
		$html .= '</div>';

		return $html;
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

		$field['descr_position'] = self::get_description_position( $field, $settings );

		$multiple = self::get_property( 'multiple_name', $field, false, 'bool' );
		$design   = $this->get_form_style( $settings );

		// Check we use multi fields.
		if ( ! $multiple ) {
			// Only one field.
			$html = $this->get_simple( $field, $design, $draft_value );
		} else {
			// Multiple fields.
			$html = $this->get_multi_first_row( $field, $design, $draft_value );
		}

		return apply_filters( 'forminator_field_name_markup', $html, $field );
	}

	/**
	 * Return field inline validation rules
	 *
	 * @since 1.0
	 * @return string
	 */
	public function get_validation_rules() {
		$rules    = '';
		$field    = $this->field;
		$id       = self::get_property( 'element_id', $field );
		$multiple = self::get_property( 'multiple_name', $field, false, 'bool' );
		$required = $this->is_required( $field );

		if ( $multiple ) {
			$prefix = self::get_property( 'prefix', $field, false, 'bool' );
			$fname  = self::get_property( 'fname', $field, false, 'bool' );
			$mname  = self::get_property( 'mname', $field, false, 'bool' );
			$lname  = self::get_property( 'lname', $field, false, 'bool' );

			$prefix_required = self::get_property( 'prefix_required', $field, false, 'bool' );
			$fname_required  = self::get_property( 'fname_required', $field, false, 'bool' );
			$mname_required  = self::get_property( 'mname_required', $field, false, 'bool' );
			$lname_required  = self::get_property( 'lname_required', $field, false, 'bool' );

			if ( $prefix ) {
				if ( $prefix_required ) {
					$rules .= '"' . $this->get_id( $field ) . '-prefix": "trim",';
					$rules .= '"' . $this->get_id( $field ) . '-prefix": "required",';
				}
			}

			if ( $fname ) {
				if ( $fname_required ) {
					$rules .= '"' . $this->get_id( $field ) . '-first-name": "trim",';
					$rules .= '"' . $this->get_id( $field ) . '-first-name": "required",';
				}
			}

			if ( $mname ) {
				if ( $mname_required ) {
					$rules .= '"' . $this->get_id( $field ) . '-middle-name": "trim",';
					$rules .= '"' . $this->get_id( $field ) . '-middle-name": "required",';
				}
			}

			if ( $lname ) {
				if ( $lname_required ) {
					$rules .= '"' . $this->get_id( $field ) . '-last-name": "trim",';
					$rules .= '"' . $this->get_id( $field ) . '-last-name": "required",';
				}
			}
		} elseif ( $required ) {
				$rules .= '"' . $this->get_id( $field ) . '": "required",';
				$rules .= '"' . $this->get_id( $field ) . '": "trim",';
		}

		return apply_filters( 'forminator_field_name_validation_rules', $rules, $id, $field );
	}

	/**
	 * Return field inline validation errors
	 *
	 * @since 1.0
	 * @return string
	 */
	public function get_validation_messages() {
		$field    = $this->field;
		$id       = self::get_property( 'element_id', $field );
		$multiple = self::get_property( 'multiple_name', $field, false, 'bool' );
		$messages = '';
		$required = $this->is_required( $field );

		if ( $multiple ) {
			$prefix = self::get_property( 'prefix', $field, false, 'bool' );
			$fname  = self::get_property( 'fname', $field, false, 'bool' );
			$mname  = self::get_property( 'mname', $field, false, 'bool' );
			$lname  = self::get_property( 'lname', $field, false, 'bool' );

			$prefix_required = self::get_property( 'prefix_required', $field, false, 'bool' );
			$fname_required  = self::get_property( 'fname_required', $field, false, 'bool' );
			$mname_required  = self::get_property( 'mname_required', $field, false, 'bool' );
			$lname_required  = self::get_property( 'lname_required', $field, false, 'bool' );

			if ( $prefix && $prefix_required ) {
				$required_message = $this->get_field_multiple_required_message(
					$id,
					$field,
					'prefix_required_message',
					'prefix',
					esc_html__( 'Prefix is required.', 'forminator' )
				);
				$messages        .= '"' . $this->get_id( $field ) . '-prefix": "' . forminator_addcslashes( $required_message ) . '",' . "\n";
			}

			if ( $fname && $fname_required ) {
				$required_message = $this->get_field_multiple_required_message(
					$id,
					$field,
					'fname_required_message',
					'first',
					self::$default_required_messages[ 'fname_' . $this->type ]
				);
				$messages        .= '"' . $this->get_id( $field ) . '-first-name": "' . forminator_addcslashes( $required_message ) . '",' . "\n";
			}

			if ( $mname && $mname_required ) {
				$required_message = $this->get_field_multiple_required_message(
					$id,
					$field,
					'mname_required_message',
					'middle',
					self::$default_required_messages[ 'mname_' . $this->type ]
				);
				$messages        .= '"' . $this->get_id( $field ) . '-middle-name": "' . forminator_addcslashes( $required_message ) . '",' . "\n";
			}

			if ( $lname && $lname_required ) {
				$required_message = $this->get_field_multiple_required_message(
					$id,
					$field,
					'lname_required_message',
					'last',
					self::$default_required_messages[ 'lname_' . $this->type ]
				);
				$messages        .= '"' . $this->get_id( $field ) . '-last-name": "' . forminator_addcslashes( $required_message ) . '",' . "\n";
			}
		} elseif ( $required ) {
				$required_message = self::get_property( 'required_message', $field, self::$default_required_messages[ $this->type ], 'string' );
				$required_message = apply_filters( 'forminator_name_field_required_validation_message', $required_message, $id, $field );
				$messages        .= '"' . $this->get_id( $field ) . '": "' . forminator_addcslashes( $required_message ) . '",' . "\n";
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
		$id          = self::get_property( 'element_id', $field );
		$is_multiple = self::get_property( 'multiple_name', $field, false, 'bool' );
		$required    = $this->is_required( $field );

		if ( $is_multiple ) {
			$prefix = self::get_property( 'prefix', $field, false, 'bool' );
			$fname  = self::get_property( 'fname', $field, false, 'bool' );
			$mname  = self::get_property( 'mname', $field, false, 'bool' );
			$lname  = self::get_property( 'lname', $field, false, 'bool' );

			$prefix_required = self::get_property( 'prefix_required', $field, false, 'bool' );
			$fname_required  = self::get_property( 'fname_required', $field, false, 'bool' );
			$mname_required  = self::get_property( 'mname_required', $field, false, 'bool' );
			$lname_required  = self::get_property( 'lname_required', $field, false, 'bool' );

			$prefix_data = isset( $data['prefix'] ) ? $data['prefix'] : '';
			$fname_data  = isset( $data['first-name'] ) ? $data['first-name'] : '';
			$mname_data  = isset( $data['middle-name'] ) ? $data['middle-name'] : '';
			$lname_data  = isset( $data['last-name'] ) ? $data['last-name'] : '';

			if ( is_array( $data ) ) {
				if ( $prefix && $prefix_required && empty( $prefix_data ) ) {
					$this->validation_message[ $id . '-prefix' ] = $this->get_field_multiple_required_message(
						$id,
						$field,
						'prefix_required_message',
						'prefix',
						esc_html__( 'Prefix is required.', 'forminator' )
					);
				}

				if ( $fname && $fname_required && empty( $fname_data ) ) {
					$this->validation_message[ $id . '-first-name' ] = $this->get_field_multiple_required_message(
						$id,
						$field,
						'fname_required_message',
						'first',
						esc_html( self::$default_required_messages[ 'fname_' . $this->type ] )
					);
				}

				if ( $mname && $mname_required && empty( $mname_data ) ) {
					$this->validation_message[ $id . '-middle-name' ] = $this->get_field_multiple_required_message(
						$id,
						$field,
						'mname_required_message',
						'middle',
						esc_html( self::$default_required_messages[ 'mname_' . $this->type ] )
					);
				}

				if ( $lname && $lname_required && empty( $lname_data ) ) {
					$this->validation_message[ $id . '-last-name' ] = $this->get_field_multiple_required_message(
						$id,
						$field,
						'lname_required_message',
						'last',
						esc_html( self::$default_required_messages[ 'lname_' . $this->type ] )
					);
				}
			}
		} elseif ( $required ) {
			if ( empty( $data ) ) {
				$required_message = self::get_property( 'required_message', $field, esc_html( self::$default_required_messages[ $this->type ] ), 'string' );

				$required_message                = apply_filters( 'forminator_name_field_required_validation_message', $required_message, $id, $field );
				$this->validation_message[ $id ] = $required_message;
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
		if ( is_array( $data ) ) {
			$data = forminator_sanitize_array_field( $data );
		} else {
			$data = forminator_sanitize_field( $data );
		}

		return apply_filters( 'forminator_field_name_sanitize', $data, $field, $original_data );
	}
}
