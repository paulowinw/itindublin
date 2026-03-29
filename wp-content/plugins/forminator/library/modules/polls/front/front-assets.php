<?php
/**
 * The Forminator_Assets_Enqueue_Poll class.
 *
 * @package Forminator
 */

/**
 * Conditionally load assets class
 *
 * @since 1.11
 */
class Forminator_Assets_Enqueue_Poll extends Forminator_Assets_Enqueue {
	/**
	 * Module slug
	 *
	 * @var string
	 */
	protected static $module_slug = 'poll';

	/**
	 * Enqueue form styles
	 *
	 * @param object $render_obj Forminator_Poll_Front object.
	 * @since 1.11
	 */
	public function enqueue_styles( $render_obj ) {
		$this->load_base_styles();

		$form_settings = $this->get_settings();
		$form_design   = 'default';

		if ( isset( $form_settings['forminator-poll-design'] ) ) {

			if ( '' !== $form_settings['forminator-poll-design'] ) {
				$form_design = $form_settings['forminator-poll-design'];
			}
		}

		$results_behav = isset( $form_settings['results-behav'] ) ? $form_settings['results-behav'] : 'not_show';
		$results_style = isset( $form_settings['results-style'] ) ? $form_settings['results-style'] : 'bar';

		$has_custom_answer = $this->has_custom_answer( $render_obj->get_fields() ); // Check if any of the answers has "custom input" enabled.
		$has_chart_enabled = false; // Check if "Results Display" has "link on poll" or "show after voted" options enabled.
		$has_chart_pie     = ( 'pie' === $results_style ) ? true : false; // Check if "Pie Chart" has been selected.
		$has_chart_bar     = ( 'bar' === $results_style ) ? true : false; // Check if "Bar Chart" has been selected.

		if ( 'not_show' !== $results_behav ) {
			$has_chart_enabled = true;
		}

		// Forminator UI - Load correct stylesheet.
		if ( 'none' !== $form_design ) {

			if ( $has_custom_answer || $has_chart_enabled ) {

				Forminator_Assets_Enqueue::fui_enqueue_style(
					'forminator-polls-' . $form_design . '-full',
					forminator_plugin_url() . 'assets/forminator-ui/css/src/poll/forminator-poll-' . $form_design . '.full.min.css',
					array(),
					FORMINATOR_VERSION
				);
			} else {

				Forminator_Assets_Enqueue::fui_enqueue_style(
					'forminator-polls-' . $form_design . '-base',
					forminator_plugin_url() . 'assets/forminator-ui/css/src/poll/forminator-poll-' . $form_design . '.base.min.css',
					array(),
					FORMINATOR_VERSION
				);
			}
		}

		// Forminator UI - Pie chart.
		if ( $has_chart_enabled && $has_chart_pie ) {

			Forminator_Assets_Enqueue::fui_enqueue_style(
				'forminator-chart-pie',
				forminator_plugin_url() . 'assets/forminator-ui/css/src/chart/forminator-chart.pie.min.css',
				array(),
				FORMINATOR_VERSION
			);
		}

		// Forminator UI - Bar chart.
		if ( $has_chart_enabled && $has_chart_bar ) {

			Forminator_Assets_Enqueue::fui_enqueue_style(
				'forminator-chart-bar',
				forminator_plugin_url() . 'assets/forminator-ui/css/src/chart/forminator-chart.bar.min.css',
				array(),
				FORMINATOR_VERSION
			);
		}
	}

	/**
	 * Enqueue form scripts
	 *
	 * @since 1.11
	 */
	public function enqueue_scripts() {
		// Load form base scripts.
		$this->load_base_scripts();

		// LOAD: ChartJS.
		wp_enqueue_script(
			'forminator-chartjs',
			forminator_plugin_url() . 'assets/js/front/Chart.min.js',
			array( 'jquery' ),
			'2.9.4',
			false
		);

		// LOAD: Datalabels plugin for ChartJS.
		wp_enqueue_script(
			'chartjs-plugin-datalabels',
			forminator_plugin_url() . 'assets/js/front/chartjs-plugin-datalabels.min.js',
			array( 'jquery' ),
			'0.6.0',
			false
		);
	}

	/**
	 * Check if poll has custom answer
	 *
	 * @since 1.11
	 * @param array $fields Fields.
	 *
	 * @return bool
	 */
	public function has_custom_answer( $fields ) {
		$has_custom = false;

		foreach ( $fields as $field ) {
			if ( isset( $field['use_extra'] ) ) {
				$has_custom = true;
			}
		}

		return $has_custom;
	}
}
