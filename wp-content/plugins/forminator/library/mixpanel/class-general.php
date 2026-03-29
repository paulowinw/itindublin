<?php
/**
 * The Forminator_Mixpanel_General class.
 *
 * @package Forminator
 */

/**
 * Mixpanel General Events class
 */
class Forminator_Mixpanel_General extends Events {

	/**
	 * Initialize class.
	 *
	 * @since 1.27.0
	 */
	public static function init() {
		add_action( 'forminator_after_form_import', array( __CLASS__, 'tracking_import' ) );
		add_action( 'forminator_after_form_export', array( __CLASS__, 'tracking_export' ) );
		add_action( 'forminator_before_manual_export_download', array( __CLASS__, 'tracking_manual_export' ), 10, 2 );
		add_action( 'forminator_after_export_schedule_save', array( __CLASS__, 'tracking_schedule_export' ), 10, 3 );
		add_action( 'forminator_before_rating_dismiss_notice', array( __CLASS__, 'event_rating_notice_dismissal' ), 10, 3 );
	}

	/**
	 * Tracking Import
	 *
	 * @param string $slug Slug.
	 *
	 * @return void
	 * @since 1.27.0
	 */
	public static function tracking_import( $slug ) {
		self::event_form_import_export( $slug, 'import' );
	}

	/**
	 * Tracking Export
	 *
	 * @param string $slug Slug.
	 *
	 * @return void
	 * @since 1.27.0
	 */
	public static function tracking_export( $slug ) {
		self::event_form_import_export( $slug, 'export' );
	}

	/**
	 * Tracking Schedule Export
	 *
	 * @param int    $form_id Form id.
	 * @param string $form_type Form type.
	 *
	 * @return void
	 * @since 1.27.0
	 */
	public static function tracking_manual_export( $form_id, $form_type ) {
		self::event_submission_export( $form_type, 'manual' );
	}

	/**
	 * Tracking Schedule Export
	 *
	 * @param int    $form_id Form Id.
	 * @param string $form_type Form type.
	 * @param array  $data Data.
	 *
	 * @return void
	 * @since 1.27.0
	 */
	public static function tracking_schedule_export( $form_id, $form_type, $data ) {
		$schedule_active = isset( $data[ $form_id . $form_type ] ) ? $data[ $form_id . $form_type ] : array();

		if ( ! $schedule_active['enabled'] ) {
			return;
		}

		self::event_submission_export( $form_type, 'schedule' );
	}

	/**
	 * Event Import/Export
	 *
	 * @param string $slug Slug.
	 * @param string $type Type.
	 *
	 * @return void
	 */
	private static function event_form_import_export( $slug, $type ) {
		self::track_event(
			'form_import_export',
			array(
				'form type' => esc_html( $slug ),
				'exim type' => esc_html( $type ),
			)
		);
	}

	/**
	 * Event Submission export
	 *
	 * @param string $form_type Form type.
	 * @param string $action Action.
	 *
	 * @return void
	 */
	private static function event_submission_export( $form_type, $action ) {
		if ( 'cform' === $form_type ) {
			$form_type = 'form';
		}
		self::track_event(
			'for_submission_exported',
			array(
				'form type'   => esc_html( $form_type ),
				'export type' => esc_html( $action ),
			)
		);
	}

	/**
	 * Event Rating Notice Dismissal
	 *
	 * @param string $action_type Action type.
	 * @param string $notice_type Notice type.
	 * @param string $location Location.
	 *
	 * @return void
	 */
	public static function event_rating_notice_dismissal( $action_type, $notice_type, $location ) {

		$action = '';
		switch ( $action_type ) {
			case 'forminator_rating_success':
				$action = 'rate';
				break;

			case 'forminator_rating_dismissed':
				$action = 'dismiss';
				break;

			case 'forminator_publish_rating_later':
			case 'forminator_publish_rating_later_dismiss':
			case 'forminator_days_rating_later_dismiss':
			case 'forminator_submission_rating_later':
			case 'forminator_submission_rating_later_dismiss':
				$action = 'remind_later';
				break;
		}

		$allowed_notice_types = array(
			'seven_days',
			'five_modules',
			'ten_modules',
			'ten_submissions',
			'hundred_submissions',
			'one_payment',
		);

		if ( ! in_array( $notice_type, $allowed_notice_types, true ) ) {
			return;
		}

		switch ( true ) {
			case 'dashboard' === $location:
				$location = 'Dashboard';
				break;
			case 'forminator-pro_page_forminator-entries' === $location:
			case 'forminator_page_forminator-entries' === $location:
				$location = 'Submissions';
				break;
			case 'toplevel_page_forminator' === $location:
			case false !== strpos( $location, 'forminator' ):
				$location = 'Forminator Dashboard';
				break;
			default:
				$location = '';
				break;
		}

		if ( empty( $action ) || empty( $notice_type ) ) {
			return;
		}

		self::track_event(
			'Rating Notice',
			array(
				'Action'      => $action,
				'Notice Type' => $notice_type,
				'Location'    => $location,
			),
		);
	}
}
