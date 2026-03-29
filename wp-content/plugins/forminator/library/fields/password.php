<?php
/**
 * The Forminator_Password class.
 *
 * @package Forminator
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_Password
 *
 * @since 1.0
 */
class Forminator_Password extends Forminator_Field {

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
	public $slug = 'password';

	/**
	 * Type
	 *
	 * @var string
	 */
	public $type = 'password';

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
	 * Has Counter
	 *
	 * @var bool
	 */
	public $has_counter = false;

	/**
	 * Icon
	 *
	 * @var string
	 */
	public $icon = 'sui-icon-key';

	/**
	 * Confirm prefix
	 *
	 * @var string
	 * @since 1.11
	 */
	public $confirm_prefix = 'confirm';

	/**
	 * Forminator_Text constructor.
	 *
	 * @since 1.0
	 */
	public function __construct() {
		parent::__construct();

		$this->name = esc_html__( 'Password', 'forminator' );
		$required   = __( 'Your password is required.', 'forminator' );

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
			'field_label'                  => esc_html__( 'Password', 'forminator' ),
			'placeholder'                  => esc_html__( 'Enter your password', 'forminator' ),
			'confirm-password-label'       => esc_html__( 'Confirm Password', 'forminator' ),
			'confirm-password-placeholder' => esc_html__( 'Confirm new password', 'forminator' ),
			'strength'                     => 'none',
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
	 *
	 * @return mixed
	 */
	public function markup( $field, $views_obj ) {

		$settings            = $views_obj->model->settings;
		$this->field         = $field;
		$this->form_settings = $settings;

		$html        = '';
		$id          = self::get_property( 'element_id', $field );
		$name        = $id;
		$id          = self::get_field_id( $id );
		$required    = self::get_property( 'required', $field, false );
		$ariareq     = 'false';
		$default     = self::get_property( 'default', $field, false );
		$placeholder = $this->sanitize_value( self::get_property( 'placeholder', $field ) );
		$field_type  = trim( self::get_property( 'input_type', $field ) );
		$label       = self::get_property( 'field_label', $field, '' );
		$description = self::get_property( 'description', $field, '' );
		$limit       = self::get_property( 'limit', $field, 0, 'num' );
		$limit_type  = self::get_property( 'limit_type', $field, '', 'str' );
		$is_confirm  = self::get_property( 'confirm-password', $field, '', 'bool' );

		$autofill_markup = $this->get_element_autofill_markup_attr( self::get_property( 'element_id', $field ) );

		if ( (bool) $required ) {
			$ariareq = 'true';
		}

		$input_text = array(
			'name'          => $name,
			'value'         => $default,
			'placeholder'   => $placeholder,
			'id'            => $id,
			'class'         => 'forminator-input forminator-name--field',
			'data-required' => $required,
			'type'          => 'password',
		);

		if ( ! empty( $default ) ) {
			$input_text['value'] = $default;
		}

		if ( ! empty( $description ) ) {
			$input_text['aria-describedby'] = $id . '-description';
		}

		$input_text = array_merge( $input_text, $autofill_markup );

		$html .= '<div class="forminator-field">';

			$html .= self::create_input(
				$input_text,
				$label,
				'',
				$required,
			);

		$html .= '</div>';

		// Counter.
		if ( ! empty( $description ) || ( ! empty( $limit ) && ! empty( $limit_type ) ) ) {

			$html .= sprintf( '<div class="forminator-description forminator-description-password" id="%s">', $id . '-description' );

			$description = str_replace( '{lostpassword_url}', wp_lostpassword_url( get_permalink() ), $description );

			if ( ! empty( $description ) ) {
				$html .= self::convert_markdown( wp_kses_post( $description ) );
			}

			if ( ( ! empty( $limit ) && ! empty( $limit_type ) ) ) {
				$html .= sprintf( '<span data-limit="%s" data-type="%s">0 / %s</span>', $limit, $limit_type, $limit );
			}

			$html .= '</div>';

		}

		// Confirm password.
		if ( $is_confirm ) {
			$id   = $this->confirm_prefix . '_' . self::get_property( 'element_id', $field );
			$name = $id;
			$id   = self::get_field_id( $id );

			$confirm_password_label       = self::get_property( 'confirm-password-label', $field, '' );
			$confirm_password_placeholder = self::get_property( 'confirm-password-placeholder', $field );
			$confirm_password_description = self::get_property( 'confirm-password-description', $field, '' );

			$confirm_input_text = array(
				'name'          => $name,
				'value'         => $default,
				'placeholder'   => $confirm_password_placeholder,
				'id'            => $id,
				'class'         => 'forminator-input forminator-name--field',
				'data-required' => $required,
				'type'          => 'password',
			);

			if ( ! empty( $confirm_password_description ) ) {
				$input_text['aria-describedby'] = $id . '-description';
			}

			if ( ! empty( $default ) ) {
				$confirm_input_text['value'] = $default;
			}

			$confirm_input_text = array_merge( $confirm_input_text, $autofill_markup );

			// Field 'Confirm password' is inside the separated 'forminator row'.
			$html_prev_field = '</div></div>';// because there are '.forminator-row' and '.forminator-col' classes for Password field.
			$html           .= apply_filters( 'forminator_prev_last_tag_before_conf_password_field_markup', $html_prev_field, $field );

			$html                           .= '<div class="forminator-row">';
			$cols                            = 12;
			$html_before_conf_password_field = sprintf( '<div class="forminator-col forminator-col-%s">', $cols );

			$html .= apply_filters( 'forminator_before_conf_password_field_markup', $html_before_conf_password_field );
			$html .= '<div class="forminator-field">';

			$html .= self::create_input(
				$confirm_input_text,
				$confirm_password_label,
				'',
				$required,
			);

			$html .= '</div>';

			// Counter.
			if ( ! empty( $confirm_password_description ) || ( ! empty( $limit ) && ! empty( $limit_type ) ) ) {
				$html .= sprintf( '<span class="forminator-description" id="%s">', $id . '-description' );
				if ( ! empty( $confirm_password_description ) ) {
					$html .= self::convert_markdown( wp_kses_post( $confirm_password_description ) );
				}

				if ( ( ! empty( $limit ) && ! empty( $limit_type ) ) ) {
					$html .= sprintf( '<span data-limit="%s" data-type="%s">0 / %s</span>', $limit, $limit_type, $limit );
				}
				$html .= '</span>';
			}
		}

		return apply_filters( 'forminator_field_password_markup', $html, $field );
	}

	/**
	 * Calculate the password score
	 *
	 * @since 1.11
	 *
	 * @param string $password Password.
	 *
	 * @return bool
	 */
	private function get_password_strength( $password = '' ) {
		$symbol_size = 0;
		$strlen      = mb_strlen( $password );

		// Password is optional and empty so don't check strength.
		if ( 0 === $strlen ) {
			return true;
		}

		if ( $strlen < 8 ) {
			return false;
		}

		if ( preg_match( '/[0-9]/', $password ) ) {
			$symbol_size += 10;
		}
		if ( preg_match( '/[a-z]/', $password ) ) {
			$symbol_size += 20;
		}
		if ( preg_match( '/[A-Z]/', $password ) ) {
			$symbol_size += 20;
		}
		if ( preg_match( '/[^a-zA-Z0-9]/', $password ) ) {
			$symbol_size += 30;
		}
		if ( preg_match( '/[=!\-@.,_*#&?^`%$+\/{\[\]|}^?~]/', $password ) ) {
			$symbol_size += 30;
		}

		$nat_log = log( pow( $symbol_size, $strlen ) );
		$score   = $nat_log / log( 2 );

		return $score >= 54;
	}

	/**
	 * Return field inline validation rules
	 *
	 * @since 1.0
	 * @return string
	 */
	public function get_validation_rules() {
		$field                 = $this->field;
		$id                    = self::get_property( 'element_id', $field );
		$is_required           = $this->is_required( $field );
		$has_limit             = $this->has_limit( $field );
		$rules                 = '';
		$is_confirm            = self::get_property( 'confirm-password', $field, '', 'bool' );
		$min_password_strength = self::get_property( 'strength', $field );
		$module_id             = isset( $this->form_settings['form_id'] ) ? $this->form_settings['form_id'] : '';
		$module_selector       = '';

		if ( ! empty( $module_id ) ) {
			$module_selector  = "#forminator-module-{$module_id}";
			$render_id        = Forminator_Render_Form::get_render_id( $module_id );
			$module_selector .= "[data-forminator-render='{$render_id}']";
		}

		if ( ! isset( $field['limit'] ) ) {
			$field['limit'] = 0;
		}

		$rules = '"' . $this->get_id( $field ) . '": {';
		if ( $is_required || $has_limit ) {
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
		}
		// Min password strength.
		if ( isset( $min_password_strength ) && '' !== $min_password_strength && 'none' !== $min_password_strength ) {
			$rules .= '"forminatorPasswordStrength": true,';
		}
		$rules .= '},';

		if ( $is_confirm ) {
			$rules .= '"' . $this->confirm_prefix . '_' . $this->get_id( $field ) . '": {';
			if ( $is_required ) {
				$rules .= '"required": true,';
			}
			$rules .= '"equalTo": "' . $module_selector . ' #' . self::get_field_id( $this->get_id( $field ) ) . '",' . "\n";
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
		$is_confirm       = self::get_property( 'confirm-password', $field, '', 'bool' );

		$min_password_strength = self::get_property( 'strength', $field );

		$messages .= '"' . $this->get_id( $field ) . '": {';
		if ( $is_required || $has_limit ) {
			if ( $is_required ) {
				$required_error = apply_filters(
					'forminator_password_field_required_validation_message',
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
		}
		// Min password strength.
		if ( isset( $min_password_strength ) && '' !== $min_password_strength && 'none' !== $min_password_strength ) {
			$strength_validation_message = self::get_property( 'strength_validation_message', $field, '' );
			$min_strength_error          = apply_filters(
				'forminator_text_field_min_password_strength_validation_message',
				! empty( $strength_validation_message ) ? $strength_validation_message : __( 'Your password doesn\'t meet the minimum strength requirement. We recommend using 8 or more characters with a mix of letters, numbers & symbols.', 'forminator' ),
				$id,
				$field
			);
			$messages                   .= '"forminatorPasswordStrength": "' . forminator_addcslashes( $min_strength_error ) . '",' . "\n";
		}
		$messages .= '},';

		if ( $is_confirm ) {
			$required_confirm_message = self::get_property( 'required_confirm_message', $field, '' );

			$messages .= '"' . $this->confirm_prefix . '_' . $this->get_id( $field ) . '": {';
			if ( $is_required ) {
				$required_error = apply_filters(
					'forminator_confirm_password_field_required_validation_message',
					! empty( $required_confirm_message ) ? $required_confirm_message : esc_html__( 'You must confirm your chosen password.', 'forminator' ),
					$id,
					$field
				);

				$messages .= '"required": "' . forminator_addcslashes( $required_error ) . '",' . "\n";
			}

			$validation_message_not_match = self::get_property( 'validation_message', $field, '' );
			$not_match_error              = apply_filters(
				'forminator_confirm_password_field_not_match_validation_message',
				! empty( $validation_message_not_match ) ? $validation_message_not_match : esc_html__( 'Your passwords don\'t match.', 'forminator' ),
				$id,
				$field
			);
			$messages                    .= '"equalTo": "' . forminator_addcslashes( $not_match_error ) . '",' . "\n";
			$messages                    .= '},';
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
		$id                    = self::get_property( 'element_id', $field );
		$min_password_strength = self::get_property( 'strength', $field );
		$is_confirm            = self::get_property( 'confirm-password', $field, '', 'bool' );

		// TODO: Remove old property "validation".

		if ( ! isset( $field['limit'] ) ) {
			$field['limit'] = 0;
		}

		if ( $this->is_required( $field ) ) {
			$required_message = self::get_property( 'required_message', $field, self::$default_required_messages[ $this->type ] );
			if ( empty( $data ) ) {
				$this->validation_message[ $id ] = apply_filters(
					'forminator_password_field_required_validation_message',
					$required_message,
					$id,
					$field
				);
			}
		}
		if ( $this->has_limit( $field ) ) {
			if ( ( isset( $field['limit_type'] ) && 'characters' === trim( $field['limit_type'] ) ) && ( strlen( $data ) > $field['limit'] ) ) {
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
		if ( isset( $min_password_strength ) && '' !== $min_password_strength && 'none' !== $min_password_strength ) {
			$strength_validation_message = self::get_property( 'strength_validation_message', $field, '' );
			if ( ! $this->get_password_strength( $data ) ) {
				$this->validation_message[ $id ] = apply_filters(
					'forminator_text_field_min_password_strength_validation_message',
					! empty( $strength_validation_message ) ? $strength_validation_message : __( 'Your password doesn\'t meet the minimum strength requirement. We recommend using 8 or more characters with a mix of letters, numbers & symbols.', 'forminator' ),
					$id,
					$field
				);
			}
		}

		$password         = Forminator_CForm_Front_Action::$prepared_data[ $id ];
		$confirm_password = ! empty( Forminator_CForm_Front_Action::$prepared_data[ 'confirm_' . $id ] ) ? Forminator_CForm_Front_Action::$prepared_data[ 'confirm_' . $id ] : '';
		if ( $is_confirm && ! empty( $data ) && $password !== $confirm_password ) {
			$validation_message_not_match         = self::get_property( 'validation_message', $field, '' );
			$validation_message_not_match_message = apply_filters(
				'forminator_confirm_password_field_not_match_validation_message',
				! empty( $validation_message_not_match ) ? $validation_message_not_match : esc_html__( 'Your passwords don\'t match.', 'forminator' ),
				$id,
				$field
			);

			$this->validation_message[ $id ]              = $validation_message_not_match_message;
			$this->validation_message[ 'confirm_' . $id ] = $validation_message_not_match_message;
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
			$data = $this->sanitize_array_field( $data );
		} else {
			$data = $this->sanitize_field( $data );
		}

		return apply_filters( 'forminator_field_text_sanitize', $data, $field, $original_data );
	}

	/**
	 * Sanitize password array field.
	 *
	 * @param array $data Array values.
	 *
	 * @return mixed
	 */
	private function sanitize_array_field( $data ) {
		foreach ( $data as &$value ) {
			if ( is_array( $value ) ) {
				$value = $this->sanitize_array_field( $value );
			} else {
				$value = $this->sanitize_field( $value );
			}
		}

		return $data;
	}

	/**
	 * Sanitize password field.
	 *
	 * @param string $data Password value.
	 *
	 * @return string
	 */
	private function sanitize_field( $data ) {
		// Password doesn't required sanitize as it is hashed while processing/save. Also it fails to support tags and characters like %1d, %20.
		// Add slashes as we removed from original post while sanitize post data (It fails to support quotation marks).
		return wp_slash( $data );
	}

	/**
	 * Remove password-N fields
	 *
	 * @param array $data Submitted data.
	 * @return array
	 */
	public static function remove_password_field_values( $data ) {
		foreach ( $data as $key => $value ) {
			if ( false !== stripos( $key, 'password-' ) ) {
				unset( $data[ $key ] );
			}
		}

		return $data;
	}
}
