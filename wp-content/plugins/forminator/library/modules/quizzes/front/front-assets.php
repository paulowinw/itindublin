<?php
/**
 * The Forminator_Assets_Enqueue_Quiz class.
 *
 * @package Forminator
 */

/**
 * Conditionally load assets class
 *
 * @since 1.12
 */
class Forminator_Assets_Enqueue_Quiz extends Forminator_Assets_Enqueue {
	/**
	 * Module slug
	 *
	 * @var string
	 */
	protected static $module_slug = 'quiz';

	/**
	 * Enqueue form styles
	 *
	 * @since 1.12
	 */
	public function enqueue_styles() {
		$this->load_base_styles();

		$form_settings = $this->get_settings();
		$form_design   = isset( $form_settings['forminator-quiz-theme'] ) ? $form_settings['forminator-quiz-theme'] : '';

		if ( 'none' !== $form_design ) {

			Forminator_Assets_Enqueue::fui_enqueue_style(
				'forminator-quiz-' . $form_design . '-base',
				forminator_plugin_url() . 'assets/forminator-ui/css/src/quiz/forminator-quiz-' . $form_design . '.base.min.css',
				array(),
				FORMINATOR_VERSION
			);
		}

		if ( isset( $form_settings['hasLeads'] ) && $form_settings['hasLeads'] ) {

			Forminator_Assets_Enqueue::fui_enqueue_style(
				'forminator-quiz-' . $form_design . '-leads',
				forminator_plugin_url() . 'assets/forminator-ui/css/src/quiz/forminator-quiz-' . $form_design . '.leads.min.css',
				array(),
				FORMINATOR_VERSION
			);

			// Load the base styles of leads form.
			$use_ajax_load = isset( $form_settings['use_ajax_load'] ) ? $form_settings['use_ajax_load'] : false;
			if ( $use_ajax_load ) {
				$lead_form = new Forminator_Assets_Enqueue_Form( forminator_get_model_from_id( $form_settings['leadsId'] ), true );
				$lead_form->load_base_styles();
			}
		}
	}

	/**
	 * Enqueue scripts
	 *
	 * @since 1.11
	 */
	public function enqueue_scripts() {
		// Load form base scripts.
		$this->load_base_scripts();
	}
}
