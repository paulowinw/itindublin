<?php
/**
 * Forminator GFBlock Forms.
 *
 * @package Forminator
 */

/**
 * Class Forminator_GFBlock_Forms
 *
 * @since 1.0 Gutenber Integration
 */
class Forminator_GFBlock_Forms extends Forminator_GFBlock_Abstract {

	/**
	 * Forminator_GFBlock_Forms Instance
	 *
	 * @var self|null
	 */
	private static $_instance = null;

	/**
	 * Block identifier
	 *
	 * @since 1.0 Gutenber Integration
	 *
	 * @var string
	 */
	protected $_slug = 'forms';

	/**
	 * Get Instance
	 *
	 * @since 1.0 Gutenberg Integration
	 * @return self|null
	 */
	public static function get_instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Forminator_GFBlock_Forms constructor.
	 *
	 * @since 1.0 Gutenberg Integration
	 */
	public function __construct() {
		// Initialize block.
		$this->init();
	}

	/**
	 * Render block markup on front-end
	 *
	 * @since 1.0 Gutenberg Integration
	 * @param array $properties Block properties.
	 *
	 * @return string
	 */
	public function render_block( $properties = array() ) {
		return '';
	}

	/**
	 * Preview form markup in block
	 *
	 * @since 1.0 Gutenberg Integration
	 * @param array $properties Block properties.
	 *
	 * @return string
	 */
	public function preview_block( $properties = array() ) {
		if ( isset( $properties['module_id'] ) ) {
			$html = forminator_form( $properties['module_id'], true, false );

			return $html;
		}

		return false;
	}

	/**
	 * Enqueue assets ( scritps / styles )
	 * Should be overriden in block class
	 *
	 * @since 1.0 Gutenberg Integration
	 */
	public function load_assets() {
		// Scripts.
		wp_enqueue_script(
			'forminator-block-forms',
			forminator_gutenberg()->get_plugin_url() . '/js/forms-block.min.js',
			array( 'wp-blocks', 'wp-i18n', 'wp-element' ),
			filemtime( forminator_gutenberg()->get_plugin_dir() . 'js/forms-block.min.js' ),
			false
		);

		// Localize scripts.
		wp_localize_script(
			'forminator-block-forms',
			'frmnt_form_data',
			array(
				'forms'     => $this->get_forms(),
				'admin_url' => admin_url( 'admin.php' ),
				'l10n'      => $this->localize(),
			)
		);

		forminator_print_front_styles();
		forminator_print_front_scripts();

		wp_enqueue_script(
			'select2-forminator',
			forminator_plugin_url() . 'assets/forminator-ui/js/select2.full.min.js',
			array( 'jquery' ),
			FORMINATOR_VERSION,
			false
		);

		wp_enqueue_script(
			'forminator-jquery-validate',
			forminator_plugin_url() . 'assets/js/library/jquery.validate.min.js',
			array( 'jquery' ),
			FORMINATOR_VERSION,
			false
		);

		wp_enqueue_script(
			'forminator-front-scripts',
			forminator_plugin_url() . 'build/front/front.multi.min.js',
			array( 'jquery', 'select2-forminator', 'forminator-jquery-validate' ),
			FORMINATOR_VERSION,
			false
		);

		wp_enqueue_script( 'jquery-ui-datepicker' );
		Forminator_Assets_Enqueue_Form::load_slider_scripts();
		$style_src     = forminator_plugin_url() . 'assets/css/intlTelInput.min.css';
		$style_version = '4.0.3';

		$script_src     = forminator_plugin_url() . 'assets/js/library/intlTelInput.min.js';
		$script_version = FORMINATOR_VERSION;

		wp_enqueue_style( 'intlTelInput-forminator-css', $style_src, array(), $style_version ); // intlTelInput.
		wp_enqueue_script( 'forminator-intlTelInput', $script_src, array( 'jquery' ), $script_version, false ); // intlTelInput.

		wp_localize_script( 'forminator-front-scripts', 'ForminatorFront', forminator_localize_data() );

		Forminator_Assets_Enqueue_Form::load_dompurify_scripts();
	}

	/**
	 * Return forms IDs and Names
	 *
	 * @since 1.0 Gutenberg Integration
	 * @return array
	 */
	public function get_forms() {
		$forms     = Forminator_API::get_forms( null, 1, 100, Forminator_Form_Model::STATUS_PUBLISH );
		$form_list = array(
			array(
				'value' => '',
				'label' => esc_html__( 'Select a form', 'forminator' ),
			),
		);

		if ( is_array( $forms ) ) {
			foreach ( $forms as $form ) {
				$form_name = $form->name;

				if ( isset( $form->settings['formName'] ) && ! empty( $form->settings['formName'] ) ) {
					$form_name = $form->settings['formName'];
				}

				$form_list[] = array(
					'value' => $form->id,
					'label' => $form_name,
				);
			}
		}

		return $form_list;
	}

	/**
	 * Localize
	 *
	 * @return string[]
	 */
	public function localize() {
		return array(
			'choose_form'      => esc_html__( 'Choose Form', 'forminator' ),
			'customize_form'   => esc_html__( 'Customize form', 'forminator' ),
			'rendering'        => esc_html__( 'Rendering...', 'forminator' ),
			'form'             => esc_html__( 'Form', 'forminator' ),
			'form_description' => esc_html__( 'Embed and display your custom Forminator forms in this block', 'forminator' ),
			'preview_image'    => forminator_plugin_url() . 'addons/pro/gutenberg/assets/form-preview-image.png',
			'preview_alt'      => esc_html__( 'Preview', 'forminator' ),
		);
	}
}

new Forminator_GFBlock_Forms();
