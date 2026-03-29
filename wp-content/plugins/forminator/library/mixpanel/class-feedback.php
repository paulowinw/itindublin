<?php
/**
 * The Forminator_Mixpanel_Feedback class.
 *
 * @package Forminator
 */

/**
 * Mixpanel Feedback Events class
 */
class Forminator_Mixpanel_Feedback extends Events {

	/**
	 * Initialize class.
	 *
	 * @since 1.49.0
	 */
	public static function init() {
		add_action( 'forminator_share_feedback_to_mixpanel', array( __CLASS__, 'share_feedback' ), 10, 2 );
	}

	/**
	 * Share Feedback
	 *
	 * @param integer $rating Rating.
	 * @param string  $message Message.
	 *
	 * @return void
	 * @since 1.49.0
	 */
	public static function share_feedback( $rating, $message = '' ) {
		self::track_event(
			'for_feedback_survey',
			array(
				'Survey Topic' => 'Form editor phase one',
				'rating'       => $rating,
				'message'      => ! empty( $message ) ? self::limit_text( $message ) : '',
			)
		);
	}
}
