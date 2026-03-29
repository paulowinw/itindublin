<?php
/**
 * Forminator Admin Report Page
 *
 * @package Forminator
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_Admin_Report_page
 *
 * @since 1.18.0
 */
class Forminator_Admin_Report_Page {

	/**
	 * Plugin instance
	 *
	 * @since  1.18.0
	 * @access private
	 * @var null
	 */
	private static $instance = null;

	/**
	 * Return the plugin instance
	 *
	 * @return Forminator_Admin_Report_page|null
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Forminator_Admin_Report_Page constructor.
	 *
	 * @since 1.0
	 */
	public function __construct() {
		// Include all necessary files.
		$this->process_request();
	}

	/**
	 * Process request
	 *
	 * @since 1.0
	 */
	public function process_request() {
		$action = Forminator_Core::sanitize_text_field( 'forminator_action' );
		if ( ! $action ) {
			return;
		}
		$page = Forminator_Core::sanitize_text_field( 'page' );
		// Check if the page is not the relevant.
		if ( 'forminator-reports' !== $page ) {
			return;
		}

		$id = filter_input( INPUT_POST, 'id', FILTER_VALIDATE_INT );

		// Verify nonce.
		$nonce = Forminator_Core::sanitize_text_field( 'forminatorNonce' );
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'forminator-report-action' ) ) {
			return;
		}

		$ids         = Forminator_Core::sanitize_text_field( 'ids' );
		$report_ids  = ! empty( $ids ) ? explode( ',', $ids ) : array();
		$form_report = Forminator_Form_Reports_Model::get_instance();

		switch ( $action ) {
			case 'delete-report':
				if ( ! empty( $id ) ) {
					$form_report->report_delete( $id );
				}
				break;
			case 'bulk-delete':
				if ( ! empty( $report_ids ) ) {
					foreach ( $report_ids as $report_id ) {
						$form_report->report_delete( $report_id );
					}
				}
				break;
			case 'bulk-active':
				if ( ! empty( $report_ids ) ) {
					foreach ( $report_ids as $report_id ) {
						$form_report->report_update_status( $report_id, 'active' );
					}
				}
				break;
			case 'bulk-inactive':
				if ( ! empty( $report_ids ) ) {
					foreach ( $report_ids as $report_id ) {
						$form_report->report_update_status( $report_id, 'inactive' );
					}
				}
				break;
			default:
				break;
		}
	}

	/**
	 * Get Reports data
	 *
	 * @param int    $form_id Form Id.
	 * @param string $form_type Form type.
	 * @param string $start_date Start date.
	 * @param string $end_date End date.
	 * @param string $range_type Range type.
	 *
	 * @return array
	 */
	public function forminator_report_data( $form_id, $form_type, $start_date = '', $end_date = '', $range_type = '' ) {
		$reports   = array();
		$form_view = Forminator_Form_Views_Model::get_instance();
		if ( ! empty( $form_id ) ) {
			$start_date          = ! empty( $start_date ) ? $start_date : date_i18n( 'Y-m-01' );
			$end_date            = ! empty( $end_date ) ? $end_date : date_i18n( 'Y-m-t' );
			$module_slug         = $this->get_module_slug( $form_type );
			$module_time         = get_the_date( 'Y-m-d H:i:s', $form_id );
			$previous_time       = ! empty( $range_type ) ? $range_type : 'This Month';
			$previous_start_date = $this->forminator_previous_time( $previous_time, $start_date, $end_date );
			$previous_end_date   = gmdate( 'Y-m-d', strtotime( '-1 day', strtotime( $start_date ) ) );
			$reports             = array(
				'start_date'       => $start_date,
				'end_date'         => $end_date,
				'previous_start'   => $previous_start_date,
				'previous_end'     => $previous_end_date,
				'last_entry_time'  => forminator_get_latest_entry_time_by_form_id( $form_id ),
				'average_month'    => self::forminator_montly_average( $module_time ),
				'previous_entries' => Forminator_Form_Entry_Model::count_report_entries( $form_id, $previous_start_date, $previous_end_date ),
				'selected_entries' => Forminator_Form_Entry_Model::count_report_entries( $form_id, $start_date, $end_date ),
				'total_entries'    => Forminator_Form_Entry_Model::count_report_entries( $form_id ),
				'previous_views'   => $form_view->count_views( $form_id, $previous_start_date, $previous_end_date ),
				'selected_views'   => $form_view->count_views( $form_id, $start_date, $end_date ),
				'total_views'      => $form_view->count_views( $form_id ),
				'previous_payment' => 0,
				'selected_payment' => 0,
				'stripe_payment'   => 0,
				'paypal_payment'   => 0,
				'integration'      => array(),
			);

			$abandoned_data = array(
				'total_abandoned'    => Forminator_Form_Entry_Model::count_report_entries( $form_id, '', '', 'abandoned' ),
				'previous_abandoned' => Forminator_Form_Entry_Model::count_report_entries( $form_id, $previous_start_date, $previous_end_date, 'abandoned' ),
				'selected_abandoned' => Forminator_Form_Entry_Model::count_report_entries( $form_id, $start_date, $end_date, 'abandoned' ),
			);
			$reports        = array_merge( $reports, $abandoned_data );

			if ( 'quiz' === $module_slug ) {
				$has_lead = false;
				$model    = Forminator_Base_Form_Model::get_model( $form_id );
				if ( is_object( $model )
					&& isset( $model->settings['hasLeads'] )
					&& $model->settings['hasLeads']
				) {
					$has_lead                  = $model->settings['hasLeads'];
					$reports['total_leads']    = Forminator_Form_Entry_Model::count_leads( $form_id );
					$reports['selected_leads'] = Forminator_Form_Entry_Model::count_leads( $form_id, $start_date, $end_date );
					$reports['previous_leads'] = Forminator_Form_Entry_Model::count_leads( $form_id, $previous_start_date, $previous_end_date );
				}
				$reports['has_leads'] = $has_lead;
			}

			if ( self::has_live_payments( $form_id ) ) {
				$payment_report = $this->forminator_payment_report_data( $form_id, $start_date, $end_date, $previous_start_date, $previous_end_date );
				$reports        = array_merge( $reports, $payment_report );
			}

			$connected_addons = forminator_get_addons_instance_connected_with_module( $form_id, $module_slug );
			if ( ! empty( $connected_addons ) ) {
				$reports['integration'] = $connected_addons;
			}
			forminator_maybe_log( __METHOD__, $start_date, $end_date );
		}

		return apply_filters( 'forminator_reports_data', $reports );
	}

	/**
	 * Get monthly average
	 *
	 * @param string $start_date Start date.
	 *
	 * @return mixed|void
	 */
	public static function forminator_montly_average( $start_date ) {
		$total_month = 0;
		if ( ! empty( $start_date ) ) {
			$start_date  = strtotime( trim( $start_date ) );
			$end_date    = strtotime( gmdate( 'Y/m/d' ) );
			$start_year  = gmdate( 'Y', $start_date );
			$end_year    = gmdate( 'Y', $end_date );
			$start_month = gmdate( 'm', $start_date );
			$end_month   = gmdate( 'm', $end_date );
			$total_month = ( ( $end_year - $start_year ) * 12 ) + ( $end_month - $start_month );
		}

		return apply_filters( 'forminator_reports_average_month', $total_month );
	}

	/**
	 * Check payment
	 *
	 * @param int $form_id Form Id.
	 *
	 * @return bool
	 */
	public static function has_live_payments( $form_id ) {
		$model = Forminator_Form_Entry_Model::has_live_payment( $form_id );

		return $model;
	}

	/**
	 * Check payment
	 *
	 * @param int $form_id Form Id.
	 *
	 * @return bool
	 */
	public static function has_payments( $form_id ) {
		$model = Forminator_Base_Form_Model::get_model( $form_id );
		if ( is_object( $model ) && $model->has_stripe_or_paypal() ) {
			return true;
		}

		return false;
	}

	/**
	 * Get module slug
	 *
	 * @param string $form_type Form type.
	 *
	 * @return string
	 */
	public function get_module_slug( $form_type ) {
		switch ( $form_type ) {
			case 'forminator_forms':
				$slug = 'form';
				break;
			case 'forminator_polls':
				$slug = 'poll';
				break;
			case 'forminator_quizzes':
				$slug = 'quiz';
				break;
			default:
				$slug = '';
				break;
		}

		return $slug;
	}

	/**
	 * Report array
	 *
	 * @param array $reports Reports.
	 * @param int   $form_id Form Id.
	 *
	 * @return array[]
	 */
	public function forminator_report_array( $reports, $form_id ) {
		$report_data = array();
		if ( ! empty( $reports ) ) {
			$entries_and_abandoned          = intval( $reports['selected_entries'] + $reports['selected_abandoned'] );
			$previous_entries_and_abandoned = intval( $reports['previous_entries'] + $reports['previous_abandoned'] );

			$selected_drop_off   = 0 < $entries_and_abandoned
				? number_format( ( $reports['selected_abandoned'] * 100 ) / $entries_and_abandoned, 1 )
				: 0;
			$previous_drop_off   = 0 < $previous_entries_and_abandoned
				? number_format( ( $reports['previous_abandoned'] * 100 ) / $previous_entries_and_abandoned, 1 )
				: 0;
			$selected_conversion = 0 < $reports['selected_views']
				? number_format( ( $reports['selected_entries'] * 100 ) / $reports['selected_views'], 1 )
				: 0;
			$previous_conversion = 0 < $reports['previous_views']
				? number_format( ( $reports['previous_entries'] * 100 ) / $reports['previous_views'], 1 )
				: 0;
			$report_data         = array(
				'views'      => array(
					'selected'   => intval( $reports['selected_views'] ),
					'previous'   => intval( $reports['previous_views'] ),
					'increment'  => $this->forminator_difference_calculate( $reports['selected_views'], $reports['previous_views'] ),
					'average'    => 0 < $reports['average_month']
						? round( intval( $reports['total_views'] ) / intval( $reports['average_month'] ) )
						: '',
					'difference' => $reports['selected_views'] > $reports['previous_views'] ? 'high' : 'low',
				),
				'conversion' => array(
					'selected'   => 0 < $selected_conversion ? floatval( $selected_conversion ) . '%' : 0,
					'previous'   => 0 < $previous_conversion ? floatval( $previous_conversion ) . '%' : 0,
					'increment'  => $this->forminator_difference_calculate( $selected_conversion, $previous_conversion ),
					'average'    => 0 < $reports['average_month']
						? number_format( floatval( $selected_conversion ) / intval( $reports['average_month'] ), 1 ) . '%'
						: 0,
					'difference' => $selected_conversion > $previous_conversion ? 'high' : 'low',
				),
				'abandoned'  => array(
					'selected'   => intval( $reports['selected_abandoned'] ),
					'previous'   => intval( $reports['previous_abandoned'] ),
					'increment'  => $this->forminator_difference_calculate( $reports['selected_abandoned'], $reports['previous_abandoned'] ),
					'average'    => 0 < $reports['average_month']
						? round( intval( $reports['total_abandoned'] ) / intval( $reports['average_month'] ) )
						: '',
					'difference' => $reports['selected_abandoned'] > $reports['previous_abandoned'] ? 'high' : 'low',
				),
				'drop_off'   => array(
					'selected'   => 0 < $selected_drop_off ? floatval( $selected_drop_off ) . '%' : 0,
					'previous'   => 0 < $previous_drop_off ? floatval( $previous_drop_off ) . '%' : 0,
					'increment'  => $this->forminator_difference_calculate( $selected_drop_off, $previous_drop_off ),
					'difference' => $selected_drop_off > $previous_drop_off ? 'high' : 'low',
				),
				'payment'    => array(
					'selected'   => 0 < $reports['selected_payment']
						? '$' . number_format( $reports['selected_payment'], 2 )
						: 0,
					'previous'   => 0 < $reports['previous_payment']
						? '$' . number_format( $reports['previous_payment'], 2 )
						: 0,
					'stripe'     => 0 < $reports['stripe_payment']
						? '$' . number_format( $reports['stripe_payment'], 2 )
						: 0,
					'paypal'     => 0 < $reports['paypal_payment']
						? '$' . number_format( $reports['paypal_payment'], 2 )
						: 0,
					'increment'  => 0 < $reports['selected_payment']
						? round( intval( $reports['previous_payment'] ) ) * 100 / intval( $reports['selected_payment'] ) . '%'
						: '',
					'difference' => $reports['selected_payment'] > $reports['previous_payment'] ? 'high' : 'low',
				),
				'entries'    => array(
					'selected'   => intval( $reports['selected_entries'] ),
					'previous'   => intval( $reports['previous_entries'] ),
					'increment'  => $this->forminator_difference_calculate( $reports['selected_entries'], $reports['previous_entries'] ),
					'average'    => 0 < $reports['average_month']
						? round( intval( $reports['total_entries'] ) / intval( $reports['average_month'] ) )
						: '',
					'difference' => $reports['selected_entries'] > $reports['previous_entries'] ? 'high' : 'low',
				),
			);
			if ( isset( $reports['has_leads'] ) && $reports['has_leads'] ) {
				$report_data['leads'] = array(
					'selected'   => intval( $reports['selected_leads'] ),
					'previous'   => intval( $reports['previous_leads'] ),
					'increment'  => $this->forminator_difference_calculate( $reports['selected_leads'], $reports['previous_leads'] ),
					'average'    => 0 < $reports['average_month']
						? round( intval( $reports['total_leads'] ) / intval( $reports['average_month'] ) )
						: '',
					'difference' => $reports['selected_leads'] > $reports['previous_leads'] ? 'high' : 'low',
				);
			}
			if ( ! empty( $reports['integration'] ) ) {
				$integration_data = array();
				foreach ( $reports['integration'] as $integration ) {
					$addon_data = $integration->to_array();
					$slug       = $addon_data['slug'];
					if ( ! empty( $integration->multi_id ) ) {
						$multi_id = $integration->multi_id;
					} elseif ( ! empty( $integration->multi_global_id ) ) {
						$multi_id = $integration->multi_global_id;
					} else {
						$multi_id = '';
					}
					if ( ! empty( $multi_id ) ) {
						$meta_key      = 'forminator_addon_' . $slug . '_status-' . $multi_id;
						$previous_send = Forminator_Form_Entry_Model::addons_data( $form_id, $meta_key, $reports['previous_start'], $reports['previous_end'] );
						$selected_sent = Forminator_Form_Entry_Model::addons_data( $form_id, $meta_key, $reports['start_date'], $reports['end_date'] );

						$integration_data[ $slug ] = array(
							'title'       => $addon_data['title'],
							'short_title' => $addon_data['short_title'],
							'image'       => $addon_data['image'],
							'selected'    => $selected_sent,
							'previous'    => $previous_send,
							'increment'   => $this->forminator_difference_calculate( $selected_sent, $previous_send ),
							'difference'  => $selected_sent >= $previous_send ? 'high' : 'low',
						);
					}
				}
				$report_data['integration'] = $integration_data;
			}
		}

		return $report_data;
	}

	/**
	 * Get payment report data
	 *
	 * @param int    $form_id Form Id.
	 * @param string $start_date Start date.
	 * @param string $end_date End date.
	 * @param string $previous_start Previous start date.
	 * @param string $previous_end Previous end date.
	 *
	 * @return array
	 */
	public function forminator_payment_report_data( $form_id, $start_date, $end_date, $previous_start, $previous_end ) {
		$payments     = array(
			'selected_payment' => 0,
			'previous_payment' => 0,
			'stripe_payment'   => 0,
			'paypal_payment'   => 0,
		);
		$end_date     = $end_date . ' 23:59:00';
		$payment_data = Forminator_Form_Entry_Model::payment_amount( $form_id, $previous_start, $end_date );
		if ( ! empty( $payment_data ) ) {
			foreach ( $payment_data as $data ) {
				$meta_value = maybe_unserialize( $data->meta_value );
				if ( $data->date_created >= $start_date && $data->date_created <= $end_date ) {
					$payments['selected_payment'] += $meta_value['amount'];
					if ( 'stripe-1' === $data->meta_key || 'stripe-ocs-1' === $data->meta_key ) {
						$payments['stripe_payment'] += $meta_value['amount'];
					}
					if ( 'paypal-1' === $data->meta_key ) {
						$payments['paypal_payment'] += $meta_value['amount'];
					}
				}
				if ( $data->date_created >= $previous_start && $data->date_created <= $previous_end ) {
					$payments['previous_payment'] += $meta_value['amount'];
				}
			}
		}

		return $payments;
	}

	/**
	 * Chart data
	 *
	 * @param int    $form_id Form Id.
	 * @param string $start_date Start date.
	 * @param string $end_date End date.
	 *
	 * @return array
	 */
	public function forminator_report_chart_data( $form_id, $start_date = '', $end_date = '' ) {
		$days_array    = array();
		$default_array = array();

		if ( empty( $start_date ) ) {
			$start_date = gmdate( 'Y-m-01' );
		}
		if ( empty( $end_date ) ) {
			$end_date = gmdate( 'Y-m-t' );
		}
		$sdate = strtotime( $start_date );
		$edate = strtotime( $end_date );

		while ( $sdate <= $edate ) {
			$default_date                   = gmdate( 'Y-m-d', $sdate );
			$days_array[]                   = gmdate( 'M j, Y', $sdate );
			$default_array[ $default_date ] = 0;
			$sdate                          = strtotime( '+1 day', $sdate );
		}

		$report_entries = Forminator_Form_Entry_Model::count_report_entries( $form_id, $start_date, $end_date );
		if ( 0 === $report_entries ) {
			$submissions_data = $default_array;
		} else {
			$submissions       = Forminator_Form_Entry_Model::get_form_latest_entries_count_grouped_by_day( $form_id, $start_date, $end_date );
			$submissions_array = wp_list_pluck( $submissions, 'entries_amount', 'date_created' );
			$submissions_data  = array_merge( $default_array, array_intersect_key( $submissions_array, $default_array ) );
		}

		$canvas_spacing = max( $submissions_data ) + 8;

		return array(
			'monthDays'      => $days_array,
			'submissions'    => $submissions_data,
			'canvas_spacing' => intval( $canvas_spacing ),
		);
	}

	/**
	 * Previous Time
	 *
	 * @param string $time Time.
	 * @param string $start_date Start date.
	 * @param string $end_date End date.
	 *
	 * @return false|string
	 */
	public function forminator_previous_time( $time, $start_date, $end_date ) {
		switch ( $time ) {
			case 'Today':
				$previous_start_date = gmdate( 'Y-m-d', strtotime( '-1 day', strtotime( $start_date ) ) );

				break;

			case 'Last 7 Days':
				$previous_start_date = gmdate( 'Y-m-d', strtotime( '-7 day', strtotime( $start_date ) ) );
				break;

			case 'This Month':
				$previous_start_date = gmdate( 'Y-m-d', strtotime( 'first day of last month', strtotime( $start_date ) ) );
				break;

			case 'Last 30 Days':
				$previous_start_date = gmdate( 'Y-m-d', strtotime( '-30 day', strtotime( $start_date ) ) );
				break;

			case 'This Year':
				$previous_start_date = gmdate( 'Y-m-d', strtotime( 'last year January 1st', strtotime( $start_date ) ) );
				break;
			case 'Custom':
				$datediff            = strtotime( $end_date ) - strtotime( $start_date );
				$total_days          = round( $datediff / ( 60 * 60 * 24 ) ) + 1;
				$previous_days       = '-' . $total_days . 'day';
				$previous_start_date = gmdate( 'Y-m-d', strtotime( $previous_days, strtotime( $start_date ) ) );
				break;

			default:
				$previous_start_date = '';
		}

		return $previous_start_date;
	}

	/**
	 * Difference_calculate
	 *
	 * @param int $selected Selected.
	 * @param int $previous Previous.
	 *
	 * @return float|int
	 */
	public function forminator_difference_calculate( $selected, $previous ) {
		$percent = 0;
		if ( 0 < $previous && 0 < $selected ) {
			if ( $previous < $selected ) {
				// Increase percent.
				$percent_from = $selected - $previous;
			} else {
				// Decrease percent.
				$percent_from = $previous - $selected;
			}
			$percent_value = ( $percent_from * 100 ) / $previous;
			$percent       = 0 < $percent_value ? round( $percent_from / $previous * 100 ) . '%' : 0;
		}

		return $percent;
	}

	/**
	 * Get app link
	 *
	 * @param int    $module_id Module Id.
	 * @param string $module_type Module type.
	 *
	 * @return string|void
	 */
	public function get_app_link_module_id( $module_id, $module_type ) {
		switch ( $module_type ) {
			case 'forminator_quizzes':
				$quiz_model  = Forminator_Base_Form_Model::get_model( $module_id );
				$quiz_type   = isset( $quiz_model->quiz_type ) ? $quiz_model->quiz_type : '';
				$wizard_slug = 'forminator-' . $quiz_type . '-wizard';
				break;
			case 'forminator_polls':
				$wizard_slug = 'forminator-poll-wizard';
				break;
			default:
				$wizard_slug = 'forminator-cform-wizard';
				break;
		}
		$wizard_link = admin_url( 'admin.php?page=' . $wizard_slug . '&id=' . $module_id );

		return $wizard_link;
	}

	/**
	 * Fetch Reports
	 *
	 * @return array|object|stdClass[]|null
	 */
	public function fetch_reports() {
		$form_reports = Forminator_Form_Reports_Model::get_instance();

		return $form_reports->fetch_all_report();
	}

	/**
	 * Get total forms
	 *
	 * @param string $module Module type.
	 *
	 * @return int
	 */
	public function get_total_forms( $module ) {

		switch ( $module ) {
			case 'forms':
				$total = forminator_cforms_total( 'publish' );
				break;
			case 'quizzes':
				$total = forminator_quizzes_total( 'publish' );
				break;
			case 'polls':
				$total = forminator_polls_total( 'publish' );
				break;
			default:
				$total = 0;
				break;
		}

		return $total;
	}
}
