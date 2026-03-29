<?php
/**
 * Forminator GFBlock Polls.
 *
 * @package Forminator
 */

/**
 * Class Forminator_GFBlock_Polls
 *
 * @since 1.0 Gutenber Integration
 */
class Forminator_GFBlock_Polls extends Forminator_GFBlock_Abstract {

	/**
	 * Forminator_GFBlock_Polls Instance
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
	protected $_slug = 'polls';

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
			return forminator_poll( $properties['module_id'], true, false );
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
			'forminator-block-polls',
			forminator_gutenberg()->get_plugin_url() . '/js/polls-block.min.js',
			array( 'wp-blocks', 'wp-i18n', 'wp-element' ),
			filemtime( forminator_gutenberg()->get_plugin_dir() . 'js/polls-block.min.js' ),
			false
		);

		// Localize scripts.
		wp_localize_script(
			'forminator-block-polls',
			'frmnt_poll_data',
			array(
				'forms'     => $this->get_forms(),
				'admin_url' => admin_url( 'admin.php' ),
				'l10n'      => $this->localize(),
			)
		);

		forminator_print_front_styles();
		forminator_print_front_scripts();
	}

	/**
	 * Return forms IDs and Names
	 *
	 * @since 1.0 Gutenberg Integration
	 * @return array
	 */
	public function get_forms() {
		$forms     = Forminator_API::get_polls( null, 1, 100, Forminator_Form_Model::STATUS_PUBLISH );
		$form_list = array(
			array(
				'value' => '',
				'label' => esc_html__( 'Select a poll', 'forminator' ),
			),
		);

		if ( is_array( $forms ) ) {
			foreach ( $forms as $form ) {
				if ( ! is_object( $form ) ) {
					continue;
				}
				$poll_name = $form->name;

				if ( isset( $form->settings['formName'] ) && ! empty( $form->settings['formName'] ) ) {
					$poll_name = $form->settings['formName'];
				}

				$form_list[] = array(
					'value' => $form->id,
					'label' => $poll_name,
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
			'choose_poll'      => esc_html__( 'Choose Poll', 'forminator' ),
			'customize_poll'   => esc_html__( 'Customize poll', 'forminator' ),
			'rendering'        => esc_html__( 'Rendering...', 'forminator' ),
			'poll'             => esc_html__( 'Poll', 'forminator' ),
			'poll_description' => esc_html__( 'Embed and display your Forminator polls in this block', 'forminator' ),
			'preview_image'    => forminator_plugin_url() . 'addons/pro/gutenberg/assets/poll-preview-image.png',
			'preview_alt'      => esc_html__( 'Preview', 'forminator' ),
		);
	}
}

new Forminator_GFBlock_Polls();
