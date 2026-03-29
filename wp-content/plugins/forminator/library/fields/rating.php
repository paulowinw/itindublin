<?php
/**
 * The Forminator_Rating class.
 *
 * @package Forminator
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_Rating
 *
 * @property  array field
 * @since 1.32
 */
class Forminator_Rating extends Forminator_Field {

	/**
	 * Slug
	 *
	 * @var string
	 */
	public $slug = 'rating';

	/**
	 * Type
	 *
	 * @var string
	 */
	public $type = 'rating';

	/**
	 * Position
	 *
	 * @var int
	 */
	public $position = 28;

	/**
	 * Options
	 *
	 * @var string
	 */
	public $options = array();

	/**
	 * Icon
	 *
	 * @var string
	 */
	public $icon = 'sui-icon-star';

	/**
	 * Forminator_Rating constructor.
	 */
	public function __construct() {
		parent::__construct();

		$this->name = esc_html__( 'Rating', 'forminator' );
		$required   = __( 'This field is required. Please select a rating.', 'forminator' );

		self::$default_required_messages[ $this->type ] = $required;
	}

	/**
	 * Field defaults
	 *
	 * @return array
	 * @since 1.32.0
	 */
	public function defaults(): array {
		return array(
			'validation'  => false,
			'field_label' => esc_html__( 'Rating', 'forminator' ),
			'max_rating'  => 5,
			'suffix'      => true,
			'icon'        => 'star',
			'size'        => 'md',
		);
	}

	/**
	 * Autofill Setting
	 *
	 * @param array $settings Settings.
	 *
	 * @return array
	 * @since 1.32.0
	 */
	public function autofill_settings( $settings = array() ): array {
		return array();
	}

	/**
	 * Field front-end markup
	 *
	 * @param array                  $field Field.
	 * @param Forminator_Render_Form $views_obj Forminator_Render_Form object.
	 * @param array|null             $draft_value Draft value(s).
	 *
	 * @return string
	 * @since 1.32
	 */
	public function markup( $field, $views_obj, $draft_value = array() ): string {
		$this->field = $field;
		$name        = self::get_property( 'element_id', $field );
		$id          = self::get_field_id( $name );
		$required    = self::get_property( 'required', $field, false );
		$label       = self::get_property( 'field_label', $field );
		$description = self::get_property( 'description', $field );
		$icon        = self::get_property( 'icon', $field, 'star' );
		$size        = self::get_property( 'size', $field, 'md' );
		$max_rating  = self::get_property( 'max_rating', $field, 5 );
		$suffix      = self::get_property( 'suffix', $field, true );
		$settings    = $views_obj->model->settings;

		$descr_position = self::get_description_position( $field, $settings );

		$value = 0;
		if ( isset( $draft_value['value'] ) ) {
			$rating_value = explode( '/', $draft_value['value'] )[0] ?? 0;
			$value        = esc_html( $rating_value );
		}

		$attributes = array(
			'name'              => $name,
			'id'                => $id,
			'class'             => 'forminator-rating',
			'aria-errormessage' => $id . '-error',
			'data-type'         => $icon,
			'data-size'         => $size,
			'data-suffix'       => $suffix ? 'true' : 'false',
		);

		if ( ! empty( $description ) ) {
			$attributes['aria-describedby'] = $id . '-description';
		}

		$options = array(
			array(
				'value'    => 0,
				'label'    => 0,
				'disabled' => true,
			),
		);

		$maximum_rating = max( 0, min( $max_rating, 50 ) );
		for ( $rating = 1; $rating <= $maximum_rating; $rating++ ) {
			$options[] = array(
				'value'    => $rating,
				'label'    => $rating,
				'disabled' => false,
			);
		}

		$html = '<div class="forminator-field">';

		$html .= self::create_select(
			$attributes,
			$label,
			$options,
			$value,
			$description,
			$required,
			$descr_position,
		);

		$html .= '</div>';

		return apply_filters( 'forminator_field_rating_markup', $html, $field );
	}

	/**
	 * Return field inline validation rules
	 *
	 * @return string
	 * @since 1.32
	 */
	public function get_validation_rules(): string {
		$field = $this->field;
		$rules = '"' . $this->get_id( $field ) . '": {' . "\n";
		if ( $this->is_required( $field ) ) {
			$rules .= '"required": true,';
		}

		$rules .= '},' . "\n";

		return apply_filters( 'forminator_field_rating_validation_rules', $rules, $this->get_id( $field ), $field );
	}

	/**
	 * Return field inline validation errors
	 *
	 * @return string
	 * @since 1.32
	 */
	public function get_validation_messages(): string {
		$message = '';
		$field   = $this->field;
		if ( $this->is_required( $field ) ) {
			$required_message = self::get_property( 'required_message', $field, self::$default_required_messages[ $this->type ] );
			$required_message = apply_filters(
				'forminator_rating_field_required_validation_message',
				$required_message,
				$this->get_id( $field ),
				$field
			);

			$message = '"' . $this->get_id( $field ) . '": {"required":"' . forminator_addcslashes( $required_message ) . '"},' . "\n";
		}

		return $message;
	}

	/**
	 * Field back-end validation
	 *
	 * @param array        $field Field settings.
	 * @param array|string $data Data to validate.
	 *
	 * @return bool
	 */
	public function validate( $field, $data ): bool {
		$id           = self::get_property( 'element_id', $field );
		$rating_value = explode( '/', $data )[0] ?? 0;
		if ( $this->is_required( $field ) ) {
			$required_error_message =
				$this->get_field_multiple_required_message(
					$id,
					$field,
					'required_message',
					'',
					esc_html( self::$default_required_messages[ $this->type ] )
				);

			if ( empty( $rating_value ) ) {
				$this->validation_message[ $id ] = $required_error_message;

				return false;
			}
		}

		return true;
	}

	/**
	 * Sanitize field value
	 *
	 * @param array        $field Field settings.
	 * @param array|string $data Data to sanitize.
	 *
	 * @return string
	 */
	public function sanitize( $field, $data ): string {
		// Sanitize.
		return esc_html( $data );
	}
}
