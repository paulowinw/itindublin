<?php
/**
 * The Forminator_Email class.
 *
 * @package Forminator
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_Email
 *
 * @since 1.0
 */
class Forminator_Email extends Forminator_Field {

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
	public $slug = 'email';

	/**
	 * Position
	 *
	 * @var int
	 */
	public $position = 2;

	/**
	 * Type
	 *
	 * @var string
	 */
	public $type = 'email';

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
	public $icon = 'sui-icon-mail';

	/**
	 * Forminator_Email constructor.
	 *
	 * @since 1.0
	 */
	public function __construct() {
		parent::__construct();
		$this->name = esc_html__( 'Email', 'forminator' );
		$required   = __( 'This field is required. Please input a valid email.', 'forminator' );

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
			'validation'                => false,
			'field_label'               => esc_html__( 'Email Address', 'forminator' ),
			'confirm-email-label'       => esc_html__( 'Confirm Email Address', 'forminator' ),
			'confirm-email-placeholder' => esc_html__( 'Re-type Email Address', 'forminator' ),
			'confirm-email-mismatch'    => esc_html__( 'Your email addresses do not match', 'forminator' ),
			'required_confirm_message'  => esc_html__( 'You must confirm your email address', 'forminator' ),
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
			'email' => array(
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

		$id          = self::get_property( 'element_id', $field );
		$name        = $id;
		$id          = self::get_field_id( $id );
		$required    = self::get_property( 'required', $field, false );
		$ariareq     = 'false';
		$placeholder = $this->sanitize_value( self::get_property( 'placeholder', $field ) );
		$value       = esc_html( self::get_property( 'value', $field ) );
		$label       = esc_html( self::get_property( 'field_label', $field ) );
		$description = self::get_property( 'description', $field );
		$is_confirm  = self::get_property( 'confirm-email', $field, '', 'bool' );

		$descr_position    = self::get_description_position( $field, $settings );
		$browser_autofill  = self::get_property( 'browser_autofill', $field, 'enabled' );
		$autocomplete_attr = 'enabled' === $browser_autofill ? 'email' : 'off';

		if ( (bool) $required ) {
			$ariareq = 'true';
		}

		if ( isset( $draft_value['value'] ) ) {

			$value = esc_attr( $draft_value['value'] );

		} elseif ( $this->has_prefill( $field ) ) {

			// We have pre-fill parameter, use its value or $value.
			$value = $this->get_prefill( $field, $value );
		}

		$email_attr = array(
			'type'          => 'email',
			'name'          => $name,
			'value'         => $value,
			'placeholder'   => $placeholder,
			'id'            => $id,
			'class'         => 'forminator-input forminator-email--field',
			'data-required' => $required,
			'aria-required' => $ariareq,
			'autocomplete'  => $autocomplete_attr,
		);

		$autofill_markup = $this->get_element_autofill_markup_attr( self::get_property( 'element_id', $field ) );

		$email_attr = array_merge( $email_attr, $autofill_markup );

		$html = '<div class="forminator-field">';

			$html .= self::create_input(
				$email_attr,
				$label,
				$description,
				$required,
				$descr_position
			);

		$html .= '</div>';

		// Confirm email.
		if ( $is_confirm ) {
			$name = 'confirm_' . self::get_property( 'element_id', $field );
			$id   = self::get_field_id( $name );

			$confirm_email_label       = self::get_property( 'confirm-email-label', $field, __( 'Confirm Email Address', 'forminator' ) );
			$confirm_email_placeholder = self::get_property( 'confirm-email-placeholder', $field, __( 'Re-type Email Address', 'forminator' ) );
			$confirm_email_description = self::get_property( 'confirm-email-description', $field );

			$confirm_input_text = array(
				'type'          => 'email',
				'name'          => $name,
				'value'         => $value,
				'placeholder'   => $confirm_email_placeholder,
				'id'            => $id,
				'class'         => 'forminator-input forminator-name--field',
				'data-required' => $required,
				'aria-required' => $ariareq,
				'autocomplete'  => $autocomplete_attr,
			);

			if ( ! empty( $confirm_email_description ) ) {
				$confirm_input_text['aria-describedby'] = $id . '-description';
			}

			$confirm_input_text = array_merge( $confirm_input_text, $autofill_markup );

			$confirm_input = '<div class="forminator-field">';

			$confirm_input .= self::create_input(
				$confirm_input_text,
				$confirm_email_label,
				$confirm_email_description,
				$required,
				$descr_position
			);

			$confirm_input .= '</div>';

			$html = '<div class="forminator-row-inside forminator-row-with-confirmation-email">' .
				'<div class="forminator-col forminator-col-6">' . $html . '</div>' .
				'<div class="forminator-col forminator-col-6">' . $confirm_input . '</div>' .
			'</div>';
		}

		return apply_filters( 'forminator_field_email_markup', $html, $id, $required, $placeholder, $value );
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
		$rules       = '"' . $this->get_id( $field ) . '": {' . "\n";
		$is_validate = self::get_property( 'validation', $field, false );
		$is_confirm  = self::get_property( 'confirm-email', $field, '', 'bool' );
		$filter_type = self::get_property( 'filter_type', $field );
		$email_list  = 'deny' === $filter_type ? self::get_property( 'denylist', $field ) : self::get_property( 'allowlist', $field );
		if ( $this->is_required( $field ) ) {
			$rules .= '"required": true,';
		}

		if ( $is_validate ) {
			$rules .= '"emailWP": true,';
		} else {
			$rules .= '"email": false,';
		}
		if ( $email_list && in_array( $filter_type, array( 'allow', 'deny' ), true ) ) {
			$email_list = str_replace( ',', '|', $email_list );

			$rules .= '"emailFilter": {"filter_type":"' . $filter_type . '","email_list":"' . $email_list . '"},';
		}

		$rules .= '},' . "\n";
		if ( $is_confirm ) {
			$rules .= '"confirm_' . $this->get_id( $field ) . '": {' . "\n";
			if ( $this->is_required( $field ) ) {
				$rules .= '"required": true,';
			}
			$rules .= $is_validate ? '"emailWP": true,' : '"email": false,';
			$rules .= '"equalToClosestEmail": true,';
			$rules .= '},' . "\n";
		}

		return apply_filters( 'forminator_field_email_validation_rules', $rules, $id, $field );
	}

	/**
	 * Return field inline validation errors
	 *
	 * @since 1.0
	 * @return string
	 */
	public function get_validation_messages() {
		$field              = $this->field;
		$id                 = $this->get_id( $field );
		$is_validate        = self::get_property( 'validation', $field );
		$validation_message = self::get_property( 'validation_message', $field, esc_html__( 'This is not a valid email.', 'forminator' ) );
		$is_confirm         = self::get_property( 'confirm-email', $field, '', 'bool' );
		$filter_type        = self::get_property( 'filter_type', $field );
		$filter_error       = self::get_filter_error( $field );
		$is_required        = $this->is_required( $field );

		$validation_message = htmlentities( $validation_message );

		$messages = '"' . $id . '": {' . "\n";

		if ( $this->is_required( $field ) ) {
			$default_required_error_message =
				$this->get_field_multiple_required_message(
					$id,
					$field,
					'required_message',
					'',
					self::$default_required_messages[ $this->type ]
				);
			$messages                      .= '"required": "' . forminator_addcslashes( $default_required_error_message ) . '",' . "\n";
		}

		if ( $is_validate ) {
			$messages .= '"emailWP": "' . forminator_addcslashes( $validation_message ) . '",' . "\n";
			$messages .= '"email": "' . forminator_addcslashes( $validation_message ) . '",' . "\n";
		}
		if ( 'deny' === $filter_type || 'allow' === $filter_type ) {
			$messages .= '"emailFilter": "' . forminator_addcslashes( $filter_error ) . '",' . "\n";
		}

		$messages .= '},' . "\n";

		if ( $is_confirm ) {
			$required_confirm_message = self::get_property( 'required_confirm_message', $field );

			$messages .= '"confirm_' . $id . '": {' . "\n";
			if ( $is_required ) {
				$required_error = apply_filters(
					'forminator_confirm_email_field_required_validation_message',
					! empty( $required_confirm_message ) ? $required_confirm_message : esc_html__( 'You must confirm your email address', 'forminator' ),
					$id,
					$field
				);

				$messages .= '"required": "' . forminator_addcslashes( $required_error ) . '",' . "\n";
			}
			if ( $is_validate ) {
				$messages .= '"emailWP": "' . forminator_addcslashes( $validation_message ) . '",' . "\n";
				$messages .= '"email": "' . forminator_addcslashes( $validation_message ) . '",' . "\n";
			}

			$validation_message_not_match = self::get_property( 'confirm-email-mismatch', $field );
			$not_match_error              = apply_filters(
				'forminator_confirm_email_field_not_match_validation_message',
				! empty( $validation_message_not_match ) ? $validation_message_not_match : esc_html__( 'Your email addresses do not match', 'forminator' ),
				$id,
				$field
			);
			$messages                    .= '"equalToClosestEmail": "' . forminator_addcslashes( $not_match_error ) . '",' . "\n";
			$messages                    .= '},' . "\n";
		}

		$messages = apply_filters(
			'forminator_email_field_validation_message',
			$messages,
			$id,
			$field,
			$validation_message
		);

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
	 * @return bool|void
	 */
	public function validate( $field, $data ) {
		$id                 = self::get_property( 'element_id', $field );
		$is_validate        = self::get_property( 'validation', $field );
		$validation_message = self::get_property( 'validation_message', $field, esc_html__( 'This is not a valid email.', 'forminator' ) );
		$is_confirm         = self::get_property( 'confirm-email', $field, '', 'bool' );
		if ( $this->is_required( $field ) ) {
			$required_error_message =
				$this->get_field_multiple_required_message(
					$id,
					$field,
					'required_message',
					'',
					esc_html( self::$default_required_messages[ $this->type ] )
				);

			if ( empty( $data ) ) {
				$this->validation_message[ $id ] = $required_error_message;
				return false;
			}
		}

		if ( $is_validate && ! empty( $data ) ) {
			$validation_message = htmlentities( $validation_message );
			if ( 320 < strlen( $data ) || ! is_email( $data ) || ! filter_var( $data, FILTER_VALIDATE_EMAIL ) ) {
				$this->validation_message[ $id ] = $validation_message;
			}
		}

		if ( ! empty( $data ) ) {
			$filter_type = self::get_property( 'filter_type', $field );
			$error       = self::get_filter_error( $field );
			if ( 'deny' === $filter_type ) {
				$denylist = self::get_property( 'denylist', $field );
				$found    = self::filter_email( $data, $denylist );
				if ( $found ) {
					$this->validation_message[ $id ] = $error;
				}
			} elseif ( 'allow' === $filter_type ) {
				$allowlist = self::get_property( 'allowlist', $field );
				$found     = self::filter_email( $data, $allowlist );
				if ( false === $found ) {
					$this->validation_message[ $id ] = $error;
				}
			}
		}
		$confirm_email = Forminator_CForm_Front_Action::$prepared_data[ 'confirm_' . $id ] ?? '';
		if ( $is_confirm && ! empty( $data ) && $data !== $confirm_email ) {
			$validation_message_not_match         = self::get_property( 'confirm-email-mismatch', $field );
			$validation_message_not_match_message = apply_filters(
				'forminator_confirm_email_field_not_match_validation_message',
				! empty( $validation_message_not_match ) ? $validation_message_not_match : esc_html__( 'Your email addresses do not match', 'forminator' ),
				$id,
				$field
			);

			$this->validation_message[ 'confirm_' . $id ] = $validation_message_not_match_message;
		}
	}

	/**
	 * Get filter error
	 *
	 * @param array $field Field.
	 *
	 * @return string
	 */
	public static function get_filter_error( array $field ): string {
		$error = self::get_property( 'filter-error', $field );
		if ( empty( $error ) ) {
			$error = esc_html__( 'This email is not allowed. Please use a different one.', 'forminator' );
		}

		return $error;
	}

	/**
	 * Filter email
	 *
	 * @param string $email Email.
	 * @param string $email_list List.
	 *
	 * @return bool|null
	 */
	public static function filter_email( $email, $email_list ): ?bool {
		if ( empty( $email_list ) ) {
			return null;
		}
		$email_list = explode( ',', $email_list );
		foreach ( $email_list as $item ) {
			// Remove spaces in email addresses.
			$item = str_replace( array( ' ', "\n", "\r", "\t" ), '', $item );
			// Escape special characters.
			$item = preg_quote( $item, '/' );
			// Support * as wildcard.
			$item = str_replace( '\*', '.*', $item );
			// Add start and end delimiters.
			$item = '/' . $item . '$/';
			if ( preg_match( $item, $email ) ) {
				return true;
			}
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
		$is_validate   = self::get_property( 'validation', $field );

		// Sanitize email.
		if ( is_string( $data ) ) {
			if ( $is_validate ) {
				$data = sanitize_email( $data );
			} else {
				$data = sanitize_text_field( $data );
			}
		}

		return apply_filters( 'forminator_field_email_sanitize', $data, $field, $original_data );
	}
}
