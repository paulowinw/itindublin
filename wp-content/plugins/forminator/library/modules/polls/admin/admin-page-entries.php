<?php
/**
 * The Forminator_Poll_View_Page class.
 *
 * @package Forminator
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_Poll_View_Page
 *
 * @since 1.0
 */
class Forminator_Poll_View_Page extends Forminator_Admin_View_Page {

	/**
	 * Moduel type
	 *
	 * @var string
	 */
	protected static $module_slug = 'poll';

	/**
	 * Initialise variables
	 *
	 * @since 1.0
	 */
	public function before_render() {
		$this->maybe_redirect();

		$form_id = filter_input( INPUT_GET, 'form_id', FILTER_VALIDATE_INT );
		if ( $form_id ) {
			$this->form_id = $form_id;
			parent::before_render();
			if ( Forminator_Form_Entry_Model::count_entries( $this->form_id ) ) {
				add_action( 'admin_footer', array( $this, 'render_pie_chart' ), 100 );
				add_action( 'admin_footer', array( $this, 'chart_style_override' ), 100 );
			}

			++$this->total_fields;
			$this->process_request();
		}
	}

	/**
	 * Action delete
	 *
	 * @param int $id ID.
	 */
	public function delete_action( $id ) {
		Forminator_Form_Entry_Model::delete_by_form( $id );
	}

	/**
	 * Get model name
	 *
	 * @since 1.0
	 * @return string
	 */
	public function get_model_name() {
		if ( $this->model ) {
			return isset( $this->model->settings['poll-title'] ) ? $this->model->settings['poll-title'] : $this->model->name;
		}

		return '';
	}

	/**
	 * Map Custom Votes
	 *
	 * @since   1.0.5
	 * @example {
	 *  'ELEMENT_ID' => [
	 *      'EXTRA_VALUE' => COUNT
	 *  ],
	 * 'answer-2' => [
	 *      'skip it' => 9
	 *  ]
	 * }
	 *
	 * @return array
	 */
	public function map_custom_votes() {
		$custom_votes = array();
		if ( is_object( $this->model ) ) {
			$fields_with_extra_enabled = array();

			$fields_array = $this->model->get_fields_as_array();
			// Trigger Update DB if needed.
			Forminator_Form_Entry_Model::map_polls_entries( $this->model->id, $fields_array );

			$fields = $this->model->get_fields();

			foreach ( (array) $fields as $field ) {
				if ( filter_var( $field->use_extra, FILTER_VALIDATE_BOOLEAN ) === true ) {
					$fields_with_extra_enabled[] = $field->slug;
				}
			}

			if ( ! empty( $fields_with_extra_enabled ) ) {
				$custom_votes = Forminator_Form_Entry_Model::count_polls_with_extra( $this->model->id, $fields_with_extra_enabled );
			}
		}

		return $custom_votes;
	}

	/**
	 * Get Element Title
	 *
	 * @since 1.0.5
	 *
	 * @param string $element_id Element Id.
	 *
	 * @return mixed
	 */
	public function get_field_title( $element_id ) {
		$fields = $this->model->pluck_fields_array( 'title', 'element_id', $element_id );

		return ( isset( $fields[ $element_id ] ) ? $fields[ $element_id ] : $element_id );
	}

	/**
	 * Render the chart
	 * Generate the google charts js for the chart
	 *
	 * @since 1.0
	 */
	public function render_pie_chart() {

		$chart_colors         = forminator_get_poll_chart_colors( $this->model->id );
		$default_chart_colors = $chart_colors;
		$settings             = ! empty( $this->model->settings ) ? $this->model->settings : array();
		$chart_design         = Forminator_Poll_Front::get_chart_design( $settings );
		$chart_data           = forminator_get_chart_data( $this->model );
		?>

		<script type="text/javascript">

			( function ( $, doc ) {

				'use strict';

				$(function() {

					var randomScalingFactor = function() {
						return Math.round( Math.random() * 100 );
					};

					var chartId   = '#forminator-chart-poll';
					var chartData = <?php echo wp_json_encode( $chart_data ); ?>;
					var chartDesign = '<?php echo esc_html( $chart_design ); ?>';

					var chartExtras = [
						'<?php echo esc_html__( 'vote(s)', 'forminator' ); ?>',
						true, // Always show votes.
						[
							'#E5E5E5', // [0] Grid lines color.
							'#777771', // [1] Axis labels color.
							'#333333'  // [2] On-chart label (bars).
						],
						[
							'#333333', // [0] Background color.
							'#FFFFFF' // [1] Text color.
						]
					];

					FUI.pollChart( chartId, chartData, chartDesign, chartExtras );

					var chartCanvas  = $( '#forminator-chart-poll' ),
						chartBody    = chartCanvas.closest( '.forminator-poll-body' ),
						chartWrapper = chartBody.find( '.forminator-chart-wrapper' )
						;

					if ( chartWrapper.length ) {

						chartCanvas.addClass( 'forminator-show' );

						chartWrapper.addClass( 'forminator-show' );
						chartWrapper.removeAttr( 'aria-hidden' );
						chartWrapper.attr( 'tabindex', '-1' );

						chartWrapper.focus();

					}

				});

			}( jQuery, document ) );

		</script>
		<?php
	}

	/**
	 * Override style used for displayed chart
	 *
	 * @since 1.5.4
	 *
	 * @return void
	 */
	public function chart_style_override() {
		ob_start();
		?>
		<style>
			.forminator-poll--chart svg > g:last-child > g:last-child {
				pointer-events: none;
			}

			.forminator-poll--chart .google-visualization-tooltip {
				pointer-events: none;
			}

			.forminator-poll--chart .google-visualization-tooltip-item {
				white-space: nowrap;
			}
		</style>
		<?php
		$style = ob_get_clean();
		/**
		 * Filter styles to be attached in poll submissions page
		 *
		 * @since 1.5.4
		 *
		 * @param string $style
		 */
		$style = apply_filters( 'forminator_admin_poll_chart_style_override', $style );
		echo $style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
