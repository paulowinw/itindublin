<?php
/**
 * The Forminator_Mixpanel_Hub_Deactivation_Survey class.
 *
 * @package Forminator
 */

/**
 * Mixpanel Hub Deactivation Survey Events class
 */
class Forminator_Mixpanel_Hub_Deactivation_Survey extends Events {

	/**
	 * Initialize class.
	 *
	 * @since 1.50.0
	 */
	public static function init() {
		add_action( 'forminator_share_hub_deactivation_survey_to_mixpanel', array( __CLASS__, 'share_hub_deactivation_survey' ), 10, 2 );
	}

	/**
	 * Share hub deactivation survey
	 *
	 * @param string $action Model action.
	 * @param string $message Message.
	 *
	 * @return void
	 * @since 1.50.0
	 */
	public static function share_hub_deactivation_survey( $action, $message = '' ) {
		$properties = array(
			'Modal Action'    => $action,
			'Message'         => ! empty( $message ) ? self::limit_text( $message ) : '',
			'Tracking Status' => Forminator_Core::is_tracking_active() ? 'opted_in' : 'opted_out',
		);

		self::track_event(
			'disconnect_site',
			$properties
		);
	}
}
