<?php
/**
 * The Forminator_Html class.
 *
 * @package Forminator
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_Html
 *
 * @since 1.0
 */
class Forminator_Html extends Forminator_Field {

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
	public $slug = 'html';

	/**
	 * Type
	 *
	 * @var string
	 */
	public $type = 'html';

	/**
	 * Position
	 *
	 * @var int
	 */
	public $position = 17;

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
	public $icon = 'sui-icon-code';

	/**
	 * Forminator_Html constructor.
	 *
	 * @since 1.0
	 */
	public function __construct() {
		parent::__construct();

		$this->name = esc_html__( 'HTML', 'forminator' );
	}

	/**
	 * Field defaults
	 *
	 * @since 1.0
	 * @return array
	 */
	public function defaults() {
		return array(
			'field_label' => esc_html__( 'HTML', 'forminator' ),
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
	 *
	 * @param array                  $field Field.
	 * @param Forminator_Render_Form $views_obj Forminator_Render_Form object.
	 *
	 * @return mixed
	 */
	public function markup( $field, $views_obj ) {
		$settings = $views_obj->model->settings;

		$html    = '';
		$label   = esc_html( self::get_property( 'field_label', $field ) );
		$id      = self::get_property( 'element_id', $field );
		$form_id = false;

		$html .= '<div class="forminator-field forminator-merge-tags" data-field="' . $id . '">';

		if ( $label ) {

			$html .= sprintf(
				'<label class="forminator-label">%s</label>',
				self::convert_markdown( $label )
			);
		}

			// Check if form_id exist.
		if ( isset( $settings['form_id'] ) ) {
			$form_id = $settings['form_id'];
		}

		$html .= forminator_replace_variables(
			self::get_property( 'variations', $field ),
			$form_id
		);

		$html .= '</div>';

		// To allow iframes in content.
		add_filter( 'wp_kses_allowed_html', array( 'Forminator_Core', 'add_iframe_to_kses_allowed_html' ) );
		$html = wp_kses_post( $html );
		remove_filter( 'wp_kses_allowed_html', array( 'Forminator_Core', 'add_iframe_to_kses_allowed_html' ) );

		return $html;
	}
}
