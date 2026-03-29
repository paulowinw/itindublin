<?php
/**
 * Forminator GFBlock Quizzes.
 *
 * @package Forminator
 */

/**
 * Class Forminator_GFBlock_Quizzes
 *
 * @since 1.0 Gutenber Integration
 */
class Forminator_GFBlock_Quizzes extends Forminator_GFBlock_Abstract {

	/**
	 * Forminator_GFBlock_Quizzes Instance
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
	protected $_slug = 'quizzes';

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
			return forminator_quiz( $properties['module_id'], true, false );
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
			'forminator-block-quizzes',
			forminator_gutenberg()->get_plugin_url() . '/js/quizzes-block.min.js',
			array( 'wp-blocks', 'wp-i18n', 'wp-element' ),
			filemtime( forminator_gutenberg()->get_plugin_dir() . 'js/quizzes-block.min.js' ),
			false
		);

		// Localize scripts.
		wp_localize_script(
			'forminator-block-quizzes',
			'frmnt_quiz_data',
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
	 * Print block preview markup
	 *
	 * @since 1.0 Gutenberg Integration
	 * @param WP_REST_Request $data Request data.
	 */
	public function preview_block_markup( $data ) {
		// Get properties.
		$properties = $data->get_params();

		// Get module ID.
		$id = isset( $properties['module_id'] ) ? $properties['module_id'] : false;

		// Get block preview markup.
		$markup = $this->preview_block( $properties );

		// Get quiz.
		$quiz = Forminator_API::get_module( $id );

		if ( $markup ) {
			wp_send_json_success(
				array(
					'markup' => trim( $markup ),
					'type'   => $quiz->quiz_type,
				)
			);
		} else {
			wp_send_json_error();
		}
	}

	/**
	 * Return forms IDs and Names
	 *
	 * @since 1.0 Gutenberg Integration
	 * @return array
	 */
	public function get_forms() {
		$forms = Forminator_API::get_quizzes( null, 1, 100, Forminator_Form_Model::STATUS_PUBLISH );

		$form_list = array(
			array(
				'value' => '',
				'label' => esc_html__( 'Select a quiz', 'forminator' ),
			),
		);

		if ( is_array( $forms ) ) {
			foreach ( $forms as $form ) {
				$quiz_name = $form->name;

				if ( isset( $form->settings['formName'] ) && ! empty( $form->settings['formName'] ) ) {
					$quiz_name = $form->settings['formName'];
				}

				$form_list[] = array(
					'value' => $form->id,
					'label' => $quiz_name,
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
			'choose_quiz'      => esc_html__( 'Choose Quiz', 'forminator' ),
			'customize_quiz'   => esc_html__( 'Customize quiz', 'forminator' ),
			'rendering'        => esc_html__( 'Rendering...', 'forminator' ),
			'quiz'             => esc_html__( 'Quiz', 'forminator' ),
			'quiz_description' => esc_html__( 'Embed and display your Forminator quiz in this block', 'forminator' ),
			'preview_image'    => forminator_plugin_url() . 'addons/pro/gutenberg/assets/quiz-preview-image.png',
			'preview_alt'      => esc_html__( 'Preview', 'forminator' ),
		);
	}
}

new Forminator_GFBlock_Quizzes();
