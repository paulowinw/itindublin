<?php
/**
 * The Forminator_Mixpanel_Notifications class.
 *
 * @package Forminator
 */

/**
 * Mixpanel Notifications Events class
 */
class Forminator_Mixpanel_Notifications extends Events {

	/**
	 * Initialize class.
	 *
	 * @since 1.27.0
	 */
	public static function init() {
		add_action( 'forminator_after_notification_update', array( __CLASS__, 'tracking_update_notification' ) );
		add_action(
			'forminator_after_notification_status_update',
			array(
				__CLASS__,
				'tracking_update_status_notification',
			),
			10,
			2
		);
		add_action( 'forminator_after_notification_delete', array( __CLASS__, 'tracking_delete_notification' ) );
	}

	/**
	 * Tracking update notification
	 *
	 * @param array $data Data.
	 *
	 * @return void
	 * @since 1.27.0
	 */
	public static function tracking_update_notification( $data ) {
		self::event_update_notification( $data );
	}

	/**
	 * Tracking update status notification
	 *
	 * @param int $report_id Report Id.
	 *
	 * @return void
	 * @since 1.27.0
	 */
	public static function tracking_update_status_notification( $report_id ) {
		$report_data = self::notifications_data( $report_id );
		self::event_update_notification( $report_data );
	}

	/**
	 * Tracking Delete notification
	 *
	 * @param int $report_id Report Id.
	 *
	 * @return void
	 * @since 1.27.0
	 */
	public static function tracking_delete_notification( $report_id ) {
		self::event_delete_notification( $report_id );
	}

	/**
	 * Fetch notification data
	 *
	 * @param int $report_id Report Id.
	 *
	 * @return array
	 */
	private static function notifications_data( $report_id ) {
		$report_value = array();
		$report_data  = Forminator_Form_Reports_Model::get_instance()->fetch_report_by_id( $report_id );
		if ( ! empty( $report_data ) ) {
			$report_value                  = ! empty( $report_data->report_value ) ?
				Forminator_Core::sanitize_html_array( maybe_unserialize( $report_data->report_value ) )
				: array();
			$report_value['report_status'] = esc_html( $report_data->status );
			if ( empty( $report_value['report_id'] ) ) {
				$report_value['report_id'] = intval( $report_id );
			}
		}

		return $report_value;
	}

	/**
	 * Event Notification update
	 *
	 * @param array $data Data.
	 *
	 * @return void
	 */
	private static function event_update_notification( $data ) {
		$module_type = self::settings_value( $data['settings'], 'module', '' );

		self::track_event(
			'for_notification_updated',
			array(
				'ID'          => intval( $data['report_id'] ),
				'update type' => 'active' === $data['report_status'] ? 'active' : 'draft',
				'module type' => esc_html( $module_type ),
				'report type' => self::settings_value( $data['settings'], $module_type . '_type', 'all' ),
				'schedule'    => esc_html( forminator_get_schedule_time( $data['schedule'] ) ),
				'frequency'   => self::settings_value( $data['schedule'], 'frequency', 'daily' ),
			)
		);
	}

	/**
	 * Event Delete notification
	 *
	 * @param int $report_id Report Id.
	 *
	 * @return void
	 */
	private static function event_delete_notification( $report_id ) {
		self::track_event(
			'for_notification_removed',
			array(
				'ID'          => intval( $report_id ),
				'update type' => 'removed',
			)
		);
	}
}
