<?php
/**
 * The Forminator_Section class.
 *
 * @package Forminator
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_Section
 *
 * @since 1.0
 */
class Forminator_Section extends Forminator_Field {

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
	public $slug = 'section';

	/**
	 * Type
	 *
	 * @var string
	 */
	public $type = 'section';

	/**
	 * Position
	 *
	 * @var int
	 */
	public $position = 20;

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
	public $icon = 'sui-icon-inlinecss';

	/**
	 * Forminator_Section constructor.
	 *
	 * @since 1.0
	 */
	public function __construct() {
		parent::__construct();

		$this->name = esc_html__( 'Section', 'forminator' );
	}

	/**
	 * Field defaults
	 *
	 * @since 1.0
	 * @return array
	 */
	public function defaults() {
		return array(
			'section_title'              => esc_html__( 'Form Section', 'forminator' ),
			'cform-section-border-style' => 'none',
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

		$this->field = $field;

		$html         = '';
		$id           = self::get_property( 'element_id', $field );
		$id           = $id . '-field';
		$title        = esc_html( self::get_property( 'section_title', $field ) );
		$subtitle     = esc_html( self::get_property( 'section_subtitle', $field ) );
		$border       = self::get_property( 'section_border', $field, 'none' );
		$border_width = self::get_property( 'cform-section-border-width', $field, 1 );
		$border_color = self::get_property( 'cform-section-border-color', $field, 1 );

		$html .= '<div class="forminator-field">';

		if ( ! empty( $title ) ) {
			$title = wp_specialchars_decode( $title );
			$html .= sprintf( '<h2 class="forminator-title">%s</h2>', $this->sanitize_output( $title ) );
		}

		if ( ! empty( $subtitle ) ) {
			$subtitle = wp_specialchars_decode( $subtitle );
			$html    .= sprintf( '<h3 class="forminator-subtitle">%s</h3>', $this->sanitize_output( $subtitle ) );
		}

		if ( 'none' !== $border ) {

			$border_width = self::get_property( 'cform-section-border-width', $field, 1 );
			$border_color = self::get_property( 'cform-section-border-color', $field, 1 );

			$html .= sprintf(
				'<hr class="forminator-border" style="border: %s %s %s;" />',
				$border_width . 'px',
				$border,
				$border_color
			);
		}

		$html .= '</div>';

		return apply_filters( 'forminator_field_section_markup', $html, $field );
	}

	/**
	 * Return sanitized form data
	 *
	 * @since 1.0
	 *
	 * @param string $content Content.
	 *
	 * @return mixed
	 */
	public function sanitize_output( $content ) {
		return esc_html( $content );
	}
}
