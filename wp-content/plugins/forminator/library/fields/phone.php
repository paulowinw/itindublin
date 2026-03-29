<?php
/**
 * The Forminator_Phone class.
 *
 * @package Forminator
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_Phone
 *
 * @since 1.0
 */
class Forminator_Phone extends Forminator_Field {

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
	public $slug = 'phone';

	/**
	 * Position
	 *
	 * @var int
	 */
	public $position = 3;

	/**
	 * Type
	 *
	 * @var string
	 */
	public $type = 'phone';

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
	 * Has counter
	 *
	 * @var bool
	 */
	public $has_counter = true;

	/**
	 * Icon
	 *
	 * @var string
	 */
	public $icon = 'sui-icon-phone';

	/**
	 * Forminator_Phone constructor.
	 *
	 * @since 1.0
	 */
	public function __construct() {
		parent::__construct();

		$this->name = esc_html__( 'Phone', 'forminator' );
		$required   = __( 'This field is required. Please input a phone number.', 'forminator' );

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
			'forminator_phone_defaults_settings',
			array(
				'required'    => false,
				'limit'       => 10,
				'limit_type'  => 'characters',
				'validation'  => 'none',
				'field_label' => esc_html__( 'Phone', 'forminator' ),
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
			'phone' => array(
				'values' => forminator_build_autofill_providers( $providers ),
			),
		);

		return $autofill_settings;
	}

	/**
	 * Phone formats
	 *
	 * @since 1.0
	 * @since 1.5.1 add regex for international phone number
	 * @return array
	 */
	public function get_phone_formats() {

		$phone_formats = array(
			'standard'      => array(
				'label'       => '(###) ###-####',
				'mask'        => '(999) 999-9999',
				/**
				 * Match jquery-validation phoneUS validation
				 * https://github.com/jquery-validation/jquery-validation/blob/1.17.0/src/additional/phoneUS.js#L20
				 */
				'regex'       => '/^(\d|\s|\(|\)|\-|\.|\+){5,20}$/',
				'instruction' => esc_html__( 'Please make sure the number has a national format.', 'forminator' ),
			),
			'international' => array(
				'label'       => esc_html__( 'International', 'forminator' ),
				'mask'        => '(123) 456-789',
				/**
				 * Allowed `+`, but only on first character
				 * allowed `{`, `)`, `_space_`, `-` and `digits`
				 * allowed 10-20 in total characters
				 */
				'regex'       => '/^(\+){0,1}(\d|\s|\(|\)|\-){10,20}$/',
				'instruction' => esc_html__( 'Please make sure the number has an international format.', 'forminator' ),
			),
		);

		return apply_filters( 'forminator_phone_formats', $phone_formats );
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

		$descr_position = self::get_description_position( $field, $settings );

		$html                  = '';
		$id                    = self::get_property( 'element_id', $field );
		$name                  = $id;
		$id                    = self::get_field_id( $id );
		$required              = self::get_property( 'required', $field, false, 'bool' );
		$ariareq               = 'false';
		$placeholder           = $this->sanitize_value( self::get_property( 'placeholder', $field ) );
		$value                 = esc_html( self::get_property( 'value', $field ) );
		$national_country      = self::get_property( 'phone_national_country', $field, 'AF' );
		$international_country = self::get_property( 'phone_international_country', $field, 'US' );
		$limit                 = esc_html( self::get_property( 'limit', $field, 10 ) );
		$label                 = esc_html( self::get_property( 'field_label', $field, '' ) );
		$description           = self::get_property( 'description', $field, '' );
		$format_check          = self::get_property( 'validation', $field, self::FIELD_PROPERTY_VALUE_NOT_EXIST );

		$description_block = '';
		if ( ! empty( $description ) || ( 'character_limit' === $format_check && 0 < $limit ) ) {

			$description_block .= sprintf( '<span id="' . esc_attr( $id . '-description' ) . '" class="forminator-description" id="%s">', $id . '-description' );

			if ( ! empty( $description ) ) {
				$description_block .= self::convert_markdown( wp_kses_data( $description ) );
			}

			if ( 'character_limit' === $format_check && 0 < $limit ) {
				$description_block .= sprintf( '<span data-limit="%s" data-type="%s">0 / %s</span>', $limit, '', $limit );
			}

			$description_block .= '</span>';
		}

		$description_block = apply_filters( 'forminator_field_description', $description_block, $description, $id, $descr_position );

		if ( (bool) $required ) {
			$ariareq = 'true';
		}

		if ( self::FIELD_PROPERTY_VALUE_NOT_EXIST === $format_check ) {
			// read old attribute.
			$phone_check  = self::get_property( 'phone_validation', $field, false, 'bool' );
			$format_check = false === $phone_check ? 'none' : '';
		}

		if ( isset( $draft_value['value'] ) ) {

			$value = esc_attr( $draft_value['value'] );

		} elseif ( $this->has_prefill( $field ) ) {

			// We have pre-fill parameter, use its value or $value.
			$value = $this->get_prefill( $field, $value );

			// If validation is not international, replace 1st space with plus.
			if ( 'international' !== $format_check ) {
				$value = 0 === strpos( $value, ' ' ) ? str_replace( ' ', '+', $value ) : trim( $value );
			}
		}
		$browser_autofill = self::get_property( 'browser_autofill', $field, 'disabled' );
		$phone_attr       = array(
			'type'          => 'text',
			'name'          => $name,
			'value'         => $value,
			'placeholder'   => $placeholder,
			'id'            => $id,
			'class'         => 'forminator-input forminator-field--phone',
			'data-required' => $required,
			'aria-required' => $ariareq,
			'autocomplete'  => 'enabled' === $browser_autofill ? 'tel-national' : 'off',
		);

		if ( wp_is_mobile() ) {
			$phone_attr['inputmode'] = 'numeric';
		}

		if ( 'none' !== $format_check ) {

			if ( 'character_limit' === $format_check && 0 < $limit ) {

				$phone_attr['maxlength'] = $limit;

			} elseif ( 'standard' === $format_check ) {

				$phone_attr['data-national_mode'] = 'enabled';

				if ( $national_country ) {
					$phone_attr['data-country'] = $national_country;
				}
			} elseif ( 'international' === $format_check ) {

				$phone_attr['data-national_mode'] = 'disabled';

				if ( $international_country ) {
					$phone_attr['data-country'] = $international_country;
				}
			}
			$phone_attr['data-validation'] = $format_check;
		}

		if ( ! empty( $description ) ) {
			$phone_attr['aria-describedby'] = esc_attr( $id . '-description' );
		}

		$html .= '<div class="forminator-field">';
		$html .= self::get_field_label( $label, $id, $required );
		if ( 'above' === $descr_position ) {
			$html .= $description_block;
		}

			$html .= self::create_input( $phone_attr, '', '', $required );

		if ( 'above' !== $descr_position ) {
			$html .= $description_block;
		}

		$html .= '</div>';

		return apply_filters( 'forminator_field_phone_markup', $html, $id, $required, $placeholder, $value );
	}

	/**
	 * Return field inline validation rules
	 *
	 * @since 1.0
	 * @since 1.5.1 add forminatorPhoneInternational for jQueryValidation
	 * @return string
	 */
	public function get_validation_rules() {
		$field        = $this->field;
		$id           = self::get_property( 'element_id', $field );
		$limit        = self::get_property( 'limit', $field, 10 );
		$format_check = self::get_property( 'validation', $field, self::FIELD_PROPERTY_VALUE_NOT_EXIST );

		if ( self::FIELD_PROPERTY_VALUE_NOT_EXIST === $format_check ) {
			// read old attribute.
			$phone_check  = self::get_property( 'phone_validation', $field, false, 'bool' );
			$format_check = false === $phone_check ? 'none' : '';
		}

		$rules = '"' . $this->get_id( $field ) . '": {';

		if ( $this->is_required( $field ) ) {
			$rules .= '"required": true,';
			$rules .= '"trim": true,';
		}

		// standard means phoneUS.
		if ( 'none' !== $format_check ) {
			if ( 'standard' === $format_check ) {
				$rules .= '"forminatorPhoneNational": true,';
			} elseif ( 'character_limit' === $format_check ) {
				$rules .= '"maxlength": ' . $limit . ',';
			} elseif ( 'international' === $format_check ) {
				$rules .= '"forminatorPhoneInternational": true,';
			}
		}

		$rules .= '},';

		return apply_filters( 'forminator_field_phone_validation_rules', $rules, $id, $field );
	}

	/**
	 * Return field inline validation errors
	 *
	 * @since 1.0
	 * @since 1.5.1 add `international` phone
	 * @return string
	 */
	public function get_validation_messages() {
		$field        = $this->field;
		$format_check = self::get_property( 'validation', $field, self::FIELD_PROPERTY_VALUE_NOT_EXIST );

		if ( self::FIELD_PROPERTY_VALUE_NOT_EXIST === $format_check ) {
			// read old attribute.
			$phone_check  = self::get_property( 'phone_validation', $field, false, 'bool' );
			$format_check = false === $phone_check ? 'none' : '';
		}

		$validation_message = self::get_property( 'validation_message', $field, '' );
		$messages           = '"' . $this->get_id( $field ) . '": {' . "\n";

		if ( $this->is_required( $field ) ) {
			$required_message = self::get_property( 'required_message', $field, self::$default_required_messages[ $this->type ] );
			$required_message = apply_filters(
				'forminator_field_phone_required_validation_message',
				$required_message,
				$field,
				$format_check,
				$this
			);
			$messages        .= '"required": "' . forminator_addcslashes( $required_message ) . '",' . "\n";
			$required_message = apply_filters(
				'forminator_field_phone_trim_validation_message',
				$required_message,
				$field,
				$format_check,
				$this
			);
			$messages        .= '"trim": "' . forminator_addcslashes( $required_message ) . '",' . "\n";
		}

		if ( 'none' !== $format_check ) {
			if ( 'standard' === $format_check ) {
				$validation_message = apply_filters(
					'forminator_field_phone_phoneUS_validation_message', // phpcs:ignore WordPress.NamingConventions.ValidHookName.NotLowercase
					( ! empty( $validation_message ) ? $validation_message : esc_html__( 'Please input a valid phone number.', 'forminator' ) ),
					$field,
					$format_check,
					$this
				);
				$messages          .= '"forminatorPhoneNational": "' . forminator_addcslashes( $validation_message ) . '",' . "\n";
			} elseif ( 'character_limit' === $format_check ) {
				$validation_message = apply_filters(
					'forminator_field_phone_maxlength_validation_message',
					( ! empty( $validation_message ) ? $validation_message : esc_html__( 'You exceeded the allowed amount of numbers. Please check again.', 'forminator' ) ),
					$field,
					$format_check,
					$this
				);
				$messages          .= '"maxlength": "' . forminator_addcslashes( $validation_message ) . '",' . "\n";
			} elseif ( 'international' === $format_check ) {
				$validation_message = apply_filters(
					'forminator_field_phone_internation_validation_message',
					( ! empty( $validation_message ) ? $validation_message : esc_html__( 'Please input a valid international phone number.', 'forminator' ) ),
					$field,
					$format_check,
					$this
				);
				$messages          .= '"forminatorPhoneInternational": "' . forminator_addcslashes( $validation_message ) . '",' . "\n";
			}
		}

		$phone_message = apply_filters(
			'forminator_field_phone_invalid_validation_message',
			( ! empty( $validation_message ) ? $validation_message : esc_html__( 'Please enter a valid phone number.', 'forminator' ) ),
			$field,
			$format_check,
			$this
		);

		$messages .= '"phone": "' . forminator_addcslashes( $phone_message ) . '",' . "\n";

		$messages .= '},' . "\n";

		return $messages;
	}

	/**
	 * Field back-end validation
	 *
	 * @since 1.0
	 *
	 * @param array        $field Field.
	 * @param array|string $data Data.
	 *
	 * @return bool
	 */
	public function validate( $field, $data ) {
		$id = self::get_property( 'element_id', $field );

		if ( $this->is_required( $field ) ) {
			if ( empty( $data ) ) {
				$required_message                = self::get_property( 'required_message', $field, esc_html( self::$default_required_messages[ $this->type ] ) );
				$this->validation_message[ $id ] = apply_filters(
					'forminator_field_phone_required_field_validation_message',
					$required_message,
					$id,
					$field,
					$data,
					$this
				);

				return false;
			}
		}

		// if data is empty, no need to `$format_check`.
		if ( empty( $data ) ) {
			return true;
		}
		// enable phone validation if `phone_validation` property enabled and data not empty, even the field is not required.
		$format_check = self::get_property( 'validation', $field, self::FIELD_PROPERTY_VALUE_NOT_EXIST );
		if ( self::FIELD_PROPERTY_VALUE_NOT_EXIST === $format_check ) {
			// read old attribute.
			$phone_check  = self::get_property( 'phone_validation', $field, false, 'bool' );
			$format_check = false === $phone_check ? 'none' : '';
		}
		$validation_message = self::get_property( 'validation_message', $field, '' );

		if ( 'none' !== $format_check ) {
			if ( 'character_limit' === $format_check ) {
				$limit = isset( $field['limit'] ) ? intval( $field['limit'] ) : 10;

				if ( strlen( $data ) > $limit ) {
					$this->validation_message[ $id ] = apply_filters(
						'forminator_field_phone_limit_validation_message',
						( ! empty( $validation_message ) ? $validation_message : esc_html__( 'You exceeded the allowed amount of numbers. Please check again.', 'forminator' ) ),
						$id,
						$field,
						$data,
						$this
					);

					return false;
				}
			} else {
				$formats = $this->get_phone_formats();
				if ( isset( $formats[ $format_check ] ) ) {
					$validation_type = $formats[ $format_check ];
					if ( $validation_type['regex'] && ! preg_match( $validation_type['regex'], $data ) ) {
						$this->validation_message[ $id ] =
							apply_filters(
								'forminator_field_phone_format_validation_message',
								( ! empty( $validation_message )
									? $validation_message
									: sprintf(
									/* translators: %s: Validate instruction */
										esc_html__( 'Invalid phone number. %s', 'forminator' ),
										$validation_type['instruction']
									) ),
								$validation_type['instruction']
							);

						return false;
					}
				}
			}
			if ( preg_match( '/[a-z]|[^\w\-()+. ]|[\-()+.]{2,}/i', $data ) ) {
				$this->validation_message[ $id ] = apply_filters(
					'forminator_field_phone_invalid_validation_message',
					( ! empty( $validation_message ) ? $validation_message : esc_html__( 'Please enter a valid phone number.', 'forminator' ) ),
					$id,
					$field,
					$data,
					$this
				);

				return false;
			}
		}

		return true;
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

		return apply_filters( 'forminator_field_phone_sanitize', $data, $field, $original_data );
	}
}
