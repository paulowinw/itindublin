<?php
/**
 * Forminator Reports
 *
 * @package Forminator
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_Reports
 *
 * Handle data exports
 *
 * @since 1.0
 */
class Forminator_Reports {

	/**
	 * Plugin instance
	 *
	 * @since  1.20.0
	 * @access private
	 * @var null
	 */
	private static $instance = null;

	/**
	 * Report instance
	 *
	 * @var null
	 */
	public $report_instance = null;

	/**
	 * Return the plugin instance
	 *
	 * @return Forminator_Reports|null
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Main constructor
	 *
	 * @since 1.0
	 */
	public function __construct() {

		add_action( 'init', array( &$this, 'schedule_reports' ) );
		add_action( 'forminator_process_report', array( &$this, 'process_report' ) );
	}

	/**
	 * Set up the schedule
	 *
	 * @since 1.0
	 */
	public function schedule_reports() {
		forminator_set_recurring_action( 'forminator_process_report', MINUTE_IN_SECONDS );
	}

	/**
	 * Process report.
	 *
	 * @since 1.20.0
	 */
	public function process_report() {
		$report_model = Forminator_Form_Reports_Model::get_instance();
		$report_data  = $report_model->fetch_all_report();

		if ( empty( $report_data ) ) {
			return;
		}

		foreach ( $report_data as $report ) {
			$report_value = Forminator_Core::sanitize_array( maybe_unserialize( $report->report_value ) );
			if ( 'active' !== $report->status || empty( $report_value ) ) {
				continue;
			}

			$report_schedule = $report_value['schedule'];
			$last_sent       = strtotime( $report->date_updated );
			$current_time    = current_time( 'timestamp' ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested -- We are using the current timestamp based on the site's timezone.
			// check the next sent.
			$next_sent = null;
			$frequency = ! empty( $report_schedule['frequency'] ) ? $report_schedule['frequency'] : 'daily';
			switch ( $frequency ) {
				case 'daily':
					$next_sent = strtotime( '+24 hours', $last_sent );
					$next_sent = date_i18n( 'Y-m-d', $next_sent ) . ' ' . $report_schedule['time'];
					break;
				case 'weekly':
					$next_sent = self::get_weekly_report_date( $last_sent, $report_schedule );
					break;
				case 'monthly':
					$next_sent = $this->get_monthly_report_date( $last_sent, $report_schedule );
					break;
				default:
					break;
			}

			if ( $current_time > strtotime( trim( $next_sent ) ) ) {
				$this->send_email_report( $report_value );
				$report_model->report_update_date( $report->report_id );
			}
		}
	}

	/**
	 * Get monthly report
	 *
	 * @param string $last_sent Last sent date.
	 * @param array  $settings Settings.
	 *
	 * @return false|string
	 */
	private function get_monthly_report_date( $last_sent, $settings ) {
		$month_date = isset( $settings['monthDay'] ) ? $settings['monthDay'] : 1;
		$hour       = isset( $settings['monthTime'] ) ? date_i18n( 'H:i', strtotime( $settings['monthTime'] ) ) : '00:00';
		$next_sent  = strtotime( date_i18n( "Y-m-{$month_date} {$hour}", $last_sent ) );

		if ( $last_sent >= $next_sent ) {
			// If not - next month.
			$next_sent = strtotime( '+1 month', $next_sent );
		}

		return date_i18n( 'Y-m-d H:i:s', $next_sent );
	}

	/**
	 * Get weekly report date
	 *
	 * @param string $last_sent Last sent date.
	 * @param array  $settings Settings.
	 *
	 * @return false|string
	 */
	private static function get_weekly_report_date( $last_sent, $settings ) {
		$week_day  = isset( $settings['weekDay'] ) ? $settings['weekDay'] : 'monday';
		$week_time = isset( $settings['weekTime'] ) ? date_i18n( 'H:i', strtotime( $settings['weekTime'] ) ) : '00:00';
		$next_sent = strtotime( "this {$week_day} {$week_time}", $last_sent );

		if ( $last_sent >= $next_sent ) {
			// If not - next week.
			$next_sent = strtotime( '+1 week', $next_sent );
		}

		return date_i18n( 'Y-m-d H:i:s', $next_sent );
	}

	/**
	 * Send out an email report.
	 *
	 * @param array $options Options.
	 *
	 * @since 1.20.0
	 *
	 * @return bool|mixed|void
	 */
	protected function send_email_report( $options ) {
		if ( empty( $options['recipients'] ) || empty( $options['settings'] ) ) {
			return;
		}
		$mail_sent   = false;
		$report_data = $this->forminator_email_report_data( $options['settings'] );
		if ( empty( $report_data ) ) {
			return;
		}
		foreach ( $options['recipients'] as $recipient ) {
			$params = array(
				'label'     => ! empty( $options['settings']['label'] ) ? $options['settings']['label'] : '',
				'module'    => ! empty( $options['settings']['module'] ) ? $options['settings']['module'] : 'forms',
				'site_url'  => site_url(),
				'site_name' => get_bloginfo( 'name' ),
				'settings'  => $options['settings'],
				'recipient' => $recipient,
				'schedule'  => forminator_get_schedule_time( $options['schedule'] ),
				'reports'   => $report_data,
			);

			$email_subject = sanitize_text_field( $this->get_subject() );
			$email_content = self::report_email_html( $params );

			// Change nl to br.
			$email_content = stripslashes( $email_content );

			$no_reply_email = 'noreply@' . wp_parse_url( get_site_url(), PHP_URL_HOST );
			$headers        = array(
				'From: Forminator <' . $no_reply_email . '>',
				'Content-Type: text/html; charset=UTF-8',
			);

			$mail_sent = wp_mail( $recipient['email'], $email_subject, $email_content, $headers );
		}

		return $mail_sent;
	}

	/**
	 * Set report email subject.
	 *
	 * @return string
	 * @since 1.20.0
	 */
	public function get_subject() {
		return sprintf( /* translators: %s: Url for site */
			__( 'Here\'s your latest report for %s', 'forminator' ),
			site_url()
		);
	}

	/**
	 * Email HTML
	 *
	 * @param array $params Params.
	 *
	 * @return mixed|string
	 */
	public function report_email_html( $params ) {
		return forminator_template( 'common/reports/email-report', $params );
	}

	/**
	 * Email report data
	 *
	 * @param array $settings Settings.
	 *
	 * @return array
	 */
	public function forminator_email_report_data( $settings ) {
		$email_report = array();
		$module       = isset( $settings['module'] ) ? $settings['module'] : 'forms';
		$module_type  = isset( $settings[ $module . '_type' ] ) ? $settings[ $module . '_type' ] : 'all';
		if ( 'selected' === $module_type ) {
			$module_ids = isset( $settings[ 'selected_' . $module ] ) ? $settings[ 'selected_' . $module ] : array();
		} else {
			$method     = 'get_' . $module;
			$modules    = Forminator_API::$method( null, 1, 999, 'publish' );
			$module_ids = array_map(
				function ( $ar ) {
					return $ar->id;
				},
				$modules
			);
		}
		if ( ! empty( $module_ids ) ) {
			foreach ( $module_ids as $m => $module_id ) {
				$views      = Forminator_Form_Views_Model::get_instance()->count_views( $module_id );
				$submission = Forminator_Form_Entry_Model::count_report_entries( $module_id );
				$abandoned  = Forminator_Form_Entry_Model::count_report_entries( $module_id, '', '', 'abandoned' );
				$based_on   = $submission + $abandoned;
				$dropoff    = 0 < $based_on
					? number_format( ( $abandoned * 100 ) / $based_on, 1 )
					: 0;
				$conversion = 0 < $views
					? number_format( ( $submission * 100 ) / $views, 1 )
					: 0;

				$email_report[ $m ] = array(
					'title'      => forminator_get_form_name( $module_id ),
					'views'      => $views,
					'submission' => $submission,
					'abandoned'  => $abandoned,
					'conversion' => ! empty( $conversion ) ? $conversion . '%' : '',
					'dropoff'    => ! empty( $dropoff ) ? $dropoff . '%' : '',
					'payments'   => null,
				);
				if ( Forminator_Form_Entry_Model::has_live_payment( $module_id ) ) {
					$payment_data = Forminator_Form_Entry_Model::payment_amount( $module_id );
					$sum_value    = null;
					if ( ! empty( $payment_data ) ) {
						$payment_value = array_map(
							function ( $payment ) {
								return maybe_unserialize( $payment->meta_value );
							},
							$payment_data
						);

						$sum_value = ! empty( $payment_value ) ? array_sum( array_column( $payment_value, 'amount' ) ) : 0;

					}
					$email_report[ $m ]['payments'] = 0 !== $sum_value ? '$' . $sum_value : 0;
				}
			}
		}

		return $email_report;
	}
}
