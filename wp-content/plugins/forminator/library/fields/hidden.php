<?php
/**
 * The Forminator_Hidden class.
 *
 * @package Forminator
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_Hidden
 *
 * @since 1.0
 */
class Forminator_Hidden extends Forminator_Field {

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
	public $slug = 'hidden';

	/**
	 * Type
	 *
	 * @var string
	 */
	public $type = 'hidden';

	/**
	 * Position
	 *
	 * @var int
	 */
	public $position = 19;

	/**
	 * Options
	 *
	 * @var array
	 */
	public $options = array();

	/**
	 * Hide advanced
	 *
	 * @var string
	 */
	public $hide_advanced = 'true';

	/**
	 * Icon
	 *
	 * @var string
	 */
	public $icon = 'sui-icon-eye-hide';

	/**
	 * Forminator_Hidden constructor.
	 *
	 * @since 1.0
	 */
	public function __construct() {
		parent::__construct();

		$this->name = esc_html__( 'Hidden Field', 'forminator' );
	}

	/**
	 * Field defaults
	 *
	 * @since 1.0
	 * @return array
	 */
	public function defaults() {
		return array(
			'field_label'   => '',
			'default_value' => 'user_ip',
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
	 * @since 1.0
	 * @param array                  $field Field.
	 * @param Forminator_Render_Form $views_obj Forminator_Render_Form object.
	 *
	 * @return mixed
	 */
	public function markup( $field, $views_obj ) {

		$id           = self::get_property( 'element_id', $field );
		$name         = $id;
		$value        = esc_html( $this->get_value( $field, true ) );
		$custom_class = self::get_property( 'custom-class', $field, '' );

		// Build class attribute if custom class is set.
		$class_attr = '';
		if ( ! empty( $custom_class ) ) {
			$class_attr = sprintf( ' class="%s"', esc_attr( $custom_class ) );
		}

		return sprintf( '<input type="hidden" id="%s"%s name="%s" value="%s" />', $id . '_' . Forminator_CForm_Front::$uid, $class_attr, $name, $value );
	}

	/**
	 * Return replaced value
	 *
	 * @since 1.0
	 * @since 1.5 add user_id value getter
	 * @param array   $field Field.
	 * @param boolean $is_markup For front-end markup.
	 *
	 * @return mixed|string
	 */
	public function get_value( $field, $is_markup = false ) {
		$value       = '';
		$saved_value = self::get_property( 'default_value', $field );
		$embed_url   = forminator_get_current_url();

		switch ( $saved_value ) {
			case 'embed_id':
				$value = forminator_get_post_data( 'ID' );
				break;
			case 'embed_title':
				$value = forminator_get_post_data( 'post_title' );
				break;
			case 'refer_url':
				if ( true === $is_markup ) {
					$value = forminator_get_referer_url( $embed_url );
				} else {
					$element_id = self::get_property( 'element_id', $field );
					$post_value = self::get_post_data( $element_id );
					$value      = empty( $post_value ) ? $embed_url : $this->sanitize( $field, $post_value );
				}
				break;
			case 'submission_id':
				$value = 'submission_id';
				break;
			case 'custom_value':
				$value = self::get_property( 'custom_value', $field );
				break;
			case 'query':
				$value = $this->replace_prefill( $field );
				break;
			default:
				$placeholder       = '{' . $saved_value . '}';
				$placeholder_value = forminator_replace_variables( $placeholder );
				if ( $placeholder_value !== $placeholder ) {
					$value = $placeholder_value;
				}
				break;
		}

		return apply_filters( 'forminator_field_hidden_field_value', $value, $saved_value, $field, $this );
	}

	/**
	 * Get prefill value
	 *
	 * @since 1.10
	 *
	 * @param array $field Field.
	 * @return mixed|string
	 */
	public function replace_prefill( $field ) {
		$value = '';

		if ( $this->has_prefill( $field ) ) {
			// We have pre-fill parameter, use its value or $value.
			$value = $this->get_prefill( $field, $value );
		}

		return $value;
	}

	/**
	 * Get calculable value
	 *
	 * @param string $submitted_field_data Submitted data.
	 * @param array  $field_settings Field settings.
	 * @return string
	 */
	public static function get_calculable_value( $submitted_field_data, $field_settings ) {
		$calculable_value = $submitted_field_data;
		/**
		 * Filter formula being used on calculable value on hidden field
		 *
		 * @param float $calculable_value
		 * @param array $submitted_field_data
		 * @param array $field_settings
		 *
		 * @return string|int|float
		 */
		$calculable_value = apply_filters( 'forminator_field_hidden_calculable_value', $calculable_value, $submitted_field_data, $field_settings );

		return $calculable_value;
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
		if ( in_array( $field['default_value'], array( 'refer_url', 'embed_url' ), true ) ) {
			$data = urldecode_deep( $data );
		}
		$data = forminator_sanitize_field( $data );

		return apply_filters( 'forminator_field_hidden_sanitize', $data, $field, $original_data );
	}
}
