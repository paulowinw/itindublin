<?php
/**
 * Forminator Reports Page
 *
 * @package Forminator
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_Reports_Page
 *
 * @since 1.18.0
 */
class Forminator_Reports_Page extends Forminator_Admin_Page {

	/**
	 * Merged default parameter with superglobal REQUEST
	 *
	 * @since 1.18.0
	 * @var array
	 */
	private $screen_params = array();

	/**
	 * Current Form Model
	 *
	 * @var null|Forminator_Base_Form_Model
	 */
	private $form_model = null;

	/**
	 * Get Form types based on available modules
	 *
	 * @return mixed
	 * @since 1.18.0
	 */
	public function get_form_types() {
		$form_types = $this->modules_form_type();

		return apply_filters( 'forminator_reports_page_modules', $form_types );
	}

	/**
	 * Render Form switcher / select based on current form_type
	 *
	 * @param string $form_type Form type.
	 * @param int    $form_id Form Id.
	 *
	 * @since 1.18.0
	 */
	public static function render_form_switcher( $form_type = 'forminator_forms', $form_id = 0 ) {
		$classes = 'sui-select';
		if ( 0 !== $form_id ) {
			$classes .= ' sui-select-sm sui-select-inline';
		}

		$empty_option = esc_html__( 'Choose a Form', 'forminator' );
		$method       = 'get_forms';
		$model        = 'Forminator_Form_Model';

		if ( Forminator_Poll_Model::model()->get_post_type() === $form_type ) {
			$empty_option = esc_html__( 'Choose a Poll', 'forminator' );
			$method       = 'get_polls';
			$model        = 'Forminator_Poll_Model';
		} elseif ( Forminator_Quiz_Model::model()->get_post_type() === $form_type ) {
			$empty_option = esc_html__( 'Choose a Quiz', 'forminator' );
			$method       = 'get_quizzes';
			$model        = 'Forminator_Quiz_Model';
		}

		echo '<select aria-label="' . esc_html__( 'Choose a Form', 'forminator' ) . '" name="form_id" data-allow-search="1" data-minimum-results-for-search="0" class="' . esc_attr( $classes ) . '" data-search="true" data-search="true" data-placeholder="' . esc_attr( $empty_option ) . '">';
		echo '<option><option>';

		$forms = Forminator_API::$method( null, 1, 999, $model::STATUS_PUBLISH );
		$forms = apply_filters( 'forminator_reports_get_forms', $forms, $form_type );

		foreach ( $forms as $form ) {
			/**
			 * Forminator_Base_Form_Model
			 *
			 * @var Forminator_Base_Form_Model $form */
			$title = ! empty( $form->settings['formName'] ) ? $form->settings['formName'] : $form->raw->post_title;
			echo '<option value="' . esc_attr( $form->id ) . '" ' . selected( $form->id, $form_id, false ) . '>' . esc_html( $title ) . '</option>';
		}
		echo '</select>';
	}

	/**
	 * Executed Action before render the page
	 *
	 * @since 1.18.0
	 */
	public function before_render() {
		$this->populate_screen_params();
		$this->prepare_reports_page();
		$this->enqueue_reports_scripts();
	}

	/**
	 * Populating Current Page Parameters
	 *
	 * @since 1.18.0
	 */
	public function populate_screen_params() {
		$this->screen_params = array(
			'form_type' => Forminator_Core::sanitize_text_field( 'form_type', 'forminator_forms' ),
			'form_id'   => Forminator_Core::sanitize_text_field( 'form_id', 0 ),
		);
	}

	/**
	 * Prepare Reports Page
	 *
	 * @since 1.18.0
	 */
	private function prepare_reports_page() {
		$this->form_model = $this->get_form_model();
		// Form not found.
		if ( ! $this->form_model instanceof Forminator_Base_Form_Model ) {
			// if form_id available remove it from request, and redirect.
			if ( $this->get_current_form_id() ) {
				$url = remove_query_arg( 'form_id' );
				if ( wp_safe_redirect( $url ) ) {
					exit;
				}
			}
		}
	}

	/**
	 * Get current form type
	 *
	 * @return mixed
	 */
	public function get_current_form_type() {
		return $this->screen_params['form_type'];
	}

	/**
	 * Get current form id
	 *
	 * @return mixed
	 */
	public function get_current_form_id() {
		return $this->screen_params['form_id'];
	}

	/**
	 * Custom scripts that only used on submissions page
	 *
	 * @since 1.18.0
	 */
	public function enqueue_reports_scripts() {

		$this->forminator_daterange_script();

		$this->forminator_report_chart_script();

		add_filter( 'forminator_l10n', array( $this, 'add_l10n' ) );
	}

	/**
	 * Daterange script
	 */
	public function forminator_daterange_script() {
		wp_enqueue_script(
			'forminator-reports-datepicker-range',
			forminator_plugin_url() . 'assets/js/library/daterangepicker.min.js',
			array( 'moment' ),
			'3.0.3',
			true
		);

		$daterangepicker_ranges
			= sprintf(
				"
			var forminator_reports_datepicker_ranges = {
				'%s': [moment(), moment()],
		        '%s': [moment().subtract(6,'days'), moment()],
		        '%s': [moment().startOf('month'), moment().endOf('month')],
		        '%s': [moment().subtract(29,'days'), moment()],
		        '%s': [moment().startOf('year'), moment().endOf('year')],
			};",
				esc_html__( 'Today', 'forminator' ),
				esc_html__( 'Last 7 Days', 'forminator' ),
				esc_html__( 'This Month', 'forminator' ),
				esc_html__( 'Last 30 Days', 'forminator' ),
				esc_html__( 'This Year', 'forminator' )
			);

		/**
		 * Filter ranges to be used on reports date range
		 *
		 * @param string $daterangepicker_ranges
		 *
		 * @since 1.18.0
		 */
		$daterangepicker_ranges = apply_filters( 'forminator_reports_datepicker_ranges', $daterangepicker_ranges );

		wp_add_inline_script( 'forminator-reports-datepicker-range', $daterangepicker_ranges );
	}

	/**
	 * Chart script
	 */
	public function forminator_report_chart_script() {
		$form_id    = $this->get_current_form_id();
		$chart_data = Forminator_Admin_Report_Page::get_instance()->forminator_report_chart_data( $form_id );

		$chart_vars
			= sprintf(
				"
			var chart_label = '%s',
				chart_form_id = %d,
                monthDays = ['%s'],
                submissions = [%s],
                canvas_spacing = %s;",
				esc_html__( 'Submissions', 'forminator' ),
				$form_id,
				wp_kses_post( implode( "', '", $chart_data['monthDays'] ) ),
				esc_html( implode( ', ', $chart_data['submissions'] ) ),
				$chart_data['canvas_spacing']
			);

		/**
		 * Filter chart vars to be used on reports
		 *
		 * @param string $chart_vars
		 *
		 * @since 1.18.0
		 */
		$chart_vars = apply_filters( 'forminator_reports_chart', $chart_vars );

		wp_add_inline_script( 'forminator-chartjs', $chart_vars );

		return $chart_vars;
	}

	/**
	 * Hook into forminator_l10n
	 *
	 * Allow to modify `daterangepicker` locale
	 *
	 * @param array $l10n locale.
	 *
	 * @return mixed
	 */
	public function add_l10n( $l10n ) {
		$daterangepicker_lang = array(
			'daysOfWeek' => Forminator_Admin_L10n::get_short_days_names(),
			'monthNames' => Forminator_Admin_L10n::get_months_names(),
		);

		/**
		 * Filter daterangepicker locale to be used
		 *
		 * @param array $daterangepicker_lang
		 *
		 * @since 1.18.0
		 */
		$daterangepicker_lang    = apply_filters( 'forminator_l10n_daterangepicker', $daterangepicker_lang );
		$l10n['daterangepicker'] = $daterangepicker_lang;

		return $l10n;
	}

	/**
	 * Override scripts to be loaded
	 *
	 * @param string $hook Hook name.
	 */
	public function enqueue_scripts( $hook ) {
		parent::enqueue_scripts( $hook );

		wp_enqueue_script(
			'forminator-chartjs',
			forminator_plugin_url() . 'assets/js/front/Chart.min.js',
			array( 'jquery' ),
			'2.8.0',
			false
		);
		$save_global_color    = "if (typeof window !== 'undefined' && typeof window.Color !== 'undefined') {window.notChartColor = window.Color;}";
		$restore_global_color = "if (typeof window !== 'undefined' && typeof window.notChartColor !== 'undefined') {window.Color = window.notChartColor;}";
		wp_add_inline_script( 'forminator-chartjs', $save_global_color, 'before' );
		wp_add_inline_script( 'forminator-chart', $save_global_color, 'before' );
		wp_add_inline_script( 'forminator-chartjs', $restore_global_color );
		wp_add_inline_script( 'forminator-chart', $restore_global_color );

		// LOAD: Datalabels plugin for ChartJS.
		wp_enqueue_script(
			'chartjs-plugin-datalabels',
			forminator_plugin_url() . 'assets/js/front/chartjs-plugin-datalabels.min.js',
			array( 'jquery' ),
			'0.6.0',
			false
		);

		$this->forminator_report_chart_script();
	}
}
