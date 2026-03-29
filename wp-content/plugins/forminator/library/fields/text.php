<?php
/**
 * The Forminator_Text class.
 *
 * @package Forminator
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_Text
 *
 * @since 1.0
 */
class Forminator_Text extends Forminator_Field {

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
	public $slug = 'text';

	/**
	 * Type
	 *
	 * @var string
	 */
	public $type = 'text';

	/**
	 * Position
	 *
	 * @var int
	 */
	public $position = 6;

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
	public $icon = 'sui-icon-style-type';

	/**
	 * Forminator_Text constructor.
	 *
	 * @since 1.0
	 */
	public function __construct() {
		parent::__construct();

		$this->name = esc_html__( 'Input', 'forminator' );
		$required   = __( 'This field is required. Please enter text.', 'forminator' );

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
			'input_type'  => 'line',
			'limit_type'  => 'characters',
			'field_label' => esc_html__( 'Text', 'forminator' ),
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
			'text' => array(
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
		$descr_position      = self::get_description_position( $field, $settings );

		$html        = '';
		$name        = self::get_property( 'element_id', $field );
		$id          = self::get_field_id( $name );
		$required    = self::get_property( 'required', $field, false );
		$ariareq     = 'false';
		$default     = esc_html( self::get_property( 'default', $field, false ) );
		$placeholder = $this->sanitize_value( self::get_property( 'placeholder', $field ) );
		$field_type  = trim( self::get_property( 'input_type', $field ) );
		$label       = esc_html( self::get_property( 'field_label', $field, '' ) );
		$description = self::get_property( 'description', $field, '' );
		$limit       = self::get_property( 'limit', $field, 0, 'num' );
		$limit_type  = self::get_property( 'limit_type', $field, '', 'str' );

		$autofill_markup = $this->get_element_autofill_markup_attr( self::get_property( 'element_id', $field ) );

		if ( (bool) $required ) {
			$ariareq = 'true';
		}

		$description_block = '';
		if ( ! empty( $description ) || ( ! empty( $limit ) && ! empty( $limit_type ) ) ) {
			$description_block .= sprintf( '<span id="%s" class="forminator-description">', esc_attr( $id . '-description' ) );
			if ( ! empty( $description ) ) {
				$description_block .= self::convert_markdown( self::esc_description( $description, $name ) );
			}
			// Counter.
			if ( ( ! empty( $limit ) && ! empty( $limit_type ) ) ) {
				$description_block .= sprintf( '<span data-limit="%s" data-type="%s">0 / %s</span>', $limit, $limit_type, $limit );
			}
			$description_block .= '</span>';
		}
		$description_block = apply_filters( 'forminator_field_description', $description_block, $description, $id, $descr_position );

		if ( 'paragraph' === $field_type ) {

			$textarea = array(
				'name'          => $name,
				'placeholder'   => $placeholder,
				'id'            => $id,
				'class'         => 'forminator-textarea',
				'aria-required' => $ariareq,
			);

			// Add maxlength attribute if limit_type is characters.
			if ( ! empty( $limit ) && 'characters' === $limit_type ) {
				$textarea['maxlength'] = $limit;
			}

			if ( isset( $draft_value['value'] ) ) {

				$default = wp_kses_post( $draft_value['value'] );

			} elseif ( $this->has_prefill( $field ) ) {
				// We have pre-fill parameter, use its value or $value.
				$default = $this->get_prefill( $field, $default );
			}

			if ( ! empty( $default ) ) {
				$textarea['content'] = $default;
			} elseif ( isset( $autofill_markup['value'] ) ) {
				$textarea['content'] = $autofill_markup['value'];
				unset( $autofill_markup['value'] );
			}

			if ( ! empty( $description ) ) {
				$textarea['aria-describedby'] = $id . '-description';
			}

			$textarea = array_merge( $textarea, $autofill_markup );

			$html .= '<div class="forminator-field">';
			$html .= self::get_field_label( $label, $id, $required );

			if ( 'above' === $descr_position ) {
				$html .= $description_block;
			}

				$html .= self::create_textarea(
					$textarea,
					'',
					'',
					$required,
				);

			$html .= '</div>';

			if ( 'above' !== $descr_position ) {
				$html .= $description_block;
			}
		} else {

			$input_text = array(
				'type'          => 'text',
				'name'          => $name,
				'value'         => $default,
				'placeholder'   => $placeholder,
				'id'            => $id,
				'class'         => 'forminator-input forminator-name--field',
				'data-required' => $required,
			);

			// Add maxlength attribute if limit_type is characters.
			if ( ! empty( $limit ) && 'characters' === $limit_type ) {
				$input_text['maxlength'] = $limit;
			}

			if ( isset( $draft_value['value'] ) ) {

				$default = wp_kses_post( $draft_value['value'] );

			} elseif ( $this->has_prefill( $field ) ) {
				// We have pre-fill parameter, use its value or $value.
				$default = $this->get_prefill( $field, $default );
			}

			if ( ! empty( $default ) ) {
				$input_text['value'] = $default;
			}

			if ( ! empty( $description ) ) {
				$input_text['aria-describedby'] = $id . '-description';
			}

			$input_text = array_merge( $input_text, $autofill_markup );

			$html .= '<div class="forminator-field">';
			$html .= self::get_field_label( $label, $id, $required );

			if ( 'above' === $descr_position ) {
				$html .= $description_block;
			}

				$html .= self::create_input(
					$input_text,
					'',
					'',
					$required,
				);

			if ( 'above' !== $descr_position ) {
				$html .= $description_block;
			}

			$html .= '</div>';
		}

		return apply_filters( 'forminator_field_text_markup', $html, $field );
	}

	/**
	 * Return field inline validation rules
	 *
	 * @since 1.0
	 * @return string
	 */
	public function get_validation_rules() {
		$field       = $this->field;
		$id          = self::get_property( 'element_id', $field );
		$is_required = $this->is_required( $field );
		$has_limit   = $this->has_limit( $field );
		$rules       = '';

		if ( ! isset( $field['limit'] ) ) {
			$field['limit'] = 0;
		}

		if ( $is_required || $has_limit ) {
			$rules = '"' . $this->get_id( $field ) . '": {';
			if ( $is_required ) {
				$rules .= '"required": true,';
			}

			if ( $has_limit ) {
				if ( isset( $field['limit_type'] ) && 'characters' === trim( $field['limit_type'] ) ) {
					$rules .= '"maxlength": ' . $field['limit'] . ',';
				} else {
					$rules .= '"maxwords": ' . $field['limit'] . ',';
				}
			}
			$rules .= '},';
		}

		return apply_filters( 'forminator_field_text_validation_rules', $rules, $id, $field );
	}

	/**
	 * Return field inline validation errors
	 *
	 * @since 1.0
	 * @return string
	 */
	public function get_validation_messages() {
		$field            = $this->field;
		$id               = self::get_property( 'element_id', $field );
		$is_required      = $this->is_required( $field );
		$has_limit        = $this->has_limit( $field );
		$messages         = '';
		$required_message = self::get_property( 'required_message', $field, self::$default_required_messages[ $this->type ] );

		if ( $is_required || $has_limit ) {
			$messages .= '"' . $this->get_id( $field ) . '": {';

			if ( $is_required ) {
				$required_error = apply_filters(
					'forminator_text_field_required_validation_message',
					$required_message,
					$id,
					$field
				);
				$messages      .= '"required": "' . forminator_addcslashes( $required_error ) . '",' . "\n";
			}

			if ( $has_limit ) {
				if ( isset( $field['limit_type'] ) && 'characters' === trim( $field['limit_type'] ) ) {
					$max_length_error = apply_filters(
						'forminator_text_field_characters_validation_message',
						esc_html__( 'You exceeded the allowed amount of characters. Please check again.', 'forminator' ),
						$id,
						$field
					);
					$messages        .= '"maxlength": "' . forminator_addcslashes( $max_length_error ) . '",' . "\n";
				} else {
					$max_words_error = apply_filters(
						'forminator_text_field_words_validation_message',
						esc_html__( 'You exceeded the allowed amount of words. Please check again.', 'forminator' ),
						$id,
						$field
					);
					$messages       .= '"maxwords": "' . forminator_addcslashes( $max_words_error ) . '",' . "\n";
				}
			}

			$messages .= '},';
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

		if ( ! isset( $field['limit'] ) ) {
			$field['limit'] = 0;
		}

		if ( $this->is_required( $field ) && '' === $data ) {
			$required_message = self::get_property( 'required_message', $field, esc_html( self::$default_required_messages[ $this->type ] ) );

			$this->validation_message[ $id ] = apply_filters(
				'forminator_text_field_required_validation_message',
				$required_message,
				$id,
				$field
			);
		}

		if ( $this->has_limit( $field ) ) {
			$data = html_entity_decode( $data );
			if ( ( isset( $field['limit_type'] ) && 'characters' === trim( $field['limit_type'] ) ) && ( mb_strlen( $data ) > $field['limit'] ) ) {
				$this->validation_message[ $id ] = apply_filters(
					'forminator_text_field_characters_validation_message',
					esc_html__( 'You exceeded the allowed amount of characters. Please check again.', 'forminator' ),
					$id,
					$field
				);
			} elseif ( ( isset( $field['limit_type'] ) && 'words' === trim( $field['limit_type'] ) ) ) {
				$words = preg_split( '/\s+/', $data );
				if ( is_array( $words ) && count( $words ) > $field['limit'] ) {
					$this->validation_message[ $id ] = apply_filters(
						'forminator_text_field_words_validation_message',
						esc_html__( 'You exceeded the allowed amount of words. Please check again.', 'forminator' ),
						$id,
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
		$data = forminator_sanitize_field( $data );

		return apply_filters( 'forminator_field_text_sanitize', $data, $field, $original_data );
	}
}
