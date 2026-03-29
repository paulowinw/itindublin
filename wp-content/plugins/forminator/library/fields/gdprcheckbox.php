<?php
/**
 * The Forminator_GdprCheckbox class.
 *
 * @package Forminator
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_GdprCheckbox
 *
 * @since 1.0.5
 */
class Forminator_GdprCheckbox extends Forminator_Field {

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
	public $slug = 'gdprcheckbox';

	/**
	 * Type
	 *
	 * @var string
	 */
	public $type = 'gdprcheckbox';

	/**
	 * Position
	 *
	 * @var int
	 */
	public $position = 21;

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
	public $icon = 'sui-icon-gdpr';

	/**
	 * Forminator_GdprChecbox constructor.
	 *
	 * @since 1.0.5
	 */
	public function __construct() {
		parent::__construct();

		$this->name = esc_html__( 'GDPR Approval', 'forminator' );
		$required   = __( 'This field is required. Please check it.', 'forminator' );

		self::$default_required_messages[ $this->type ] = $required;
	}

	/**
	 * Field defaults
	 *
	 * @since 1.0.5
	 * @return array
	 */
	public function defaults() {

		$privacy_url = get_privacy_policy_url();
		$privacy_url = ! empty( $privacy_url ) ? $privacy_url : '#';

		return array(
			'required'         => 'true',
			'field_label'      => 'GDPR',
			'gdpr_description' =>
				sprintf(
					/* Translators: 1. Opening <a> tag with link to privacy policy, 2. closing <a> tag, 3. Opening <a> tag with #href, 4. closing <a> tag. */
					esc_html__( 'Yes, I agree with the %1$sprivacy policy%2$s and %3$sterms and conditions%4$s.', 'forminator' ),
					'<a href="' . esc_url( $privacy_url ) . '" target="_blank">',
					'</a>',
					'<a href="#" target="_blank">',
					'</a>'
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
		// Unsupported Autofill.
		$autofill_settings = array();

		return $autofill_settings;
	}

	/**
	 * Field front-end markup
	 *
	 * @since 1.0.5
	 *
	 * @param array                  $field Field.
	 * @param Forminator_Render_Form $views_obj Forminator_Render_Form object.
	 *
	 * @return mixed
	 */
	public function markup( $field, $views_obj ) {

		$settings    = $views_obj->model->settings;
		$this->field = $field;

		$html        = '';
		$id          = self::get_property( 'element_id', $field );
		$name        = $id;
		$form_id     = isset( $settings['form_id'] ) ? $settings['form_id'] : false;
		$description = wp_kses_post( forminator_replace_variables( self::get_property( 'gdpr_description', $field ), $form_id ) );
		$id          = self::get_field_id( $id );
		$label       = esc_html( self::get_property( 'field_label', $field ) );

		$html .= '<div class="forminator-field">';

		$html .= self::get_field_label( $label, $id, true );

			$html .= sprintf( '<label for="%s" class="forminator-checkbox">', $id );

				$html .= sprintf(
					'<input type="checkbox" name="%s" value="true" id="%s" data-required="true" aria-required="true" />',
					$name,
					$id
				);

				$html .= '<span class="forminator-checkbox-box" aria-hidden="true"></span>';

				$html .= sprintf( '<span class="forminator-checkbox-label">%s</span>', $description );

			$html .= '</label>';

		$html .= '</div>';

		return apply_filters( 'forminator_field_gdprcheckbox_markup', $html, $id, $description );
	}

	/**
	 * Return field inline validation rules
	 *
	 * @since 1.0.5
	 * @return string
	 */
	public function get_validation_rules() {
		$field = $this->field;
		$id    = self::get_property( 'element_id', $field );
		$rules = '"' . $this->get_id( $field ) . '":{"required":true},';

		return apply_filters( 'forminator_field_gdprcheckbox_validation_rules', $rules, $id, $field );
	}

	/**
	 * Return field inline validation errors
	 *
	 * @since 1.0.5
	 * @return string
	 */
	public function get_validation_messages() {
		$messages         = '';
		$field            = $this->field;
		$id               = $this->get_id( $field );
		$required_message = self::get_property( 'required_message', $field, self::$default_required_messages[ $this->type ] );
		$required_message = apply_filters(
			'forminator_gdprcheckbox_field_required_validation_message',
			$required_message,
			$id,
			$field
		);
		$messages        .= '"' . $this->get_id( $field ) . '": {"required":"' . forminator_addcslashes( $required_message ) . '"},' . "\n";

		return $messages;
	}

	/**
	 * Field back-end validation
	 *
	 * @since 1.0.5
	 *
	 * @param array        $field Field.
	 * @param array|string $data Data.
	 */
	public function validate( $field, $data ) {
		// value of gdpr checkbox is `string` *true*.
		$id = $this->get_id( $field );
		if ( empty( $data ) || 'true' !== $data ) {
			$this->validation_message[ $id ] = apply_filters(
				'forminator_gdprcheckbox_field_required_validation_message',
				esc_html( self::$default_required_messages[ $this->type ] ),
				$id,
				$field
			);
		}
	}

	/**
	 * Sanitize data
	 *
	 * @since 1.0.5
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

		return apply_filters( 'forminator_field_gdprcheckbox_sanitize', $data, $field, $original_data );
	}
}
