<?php
/**
 * The Forminator_Quiz_General_Data_Protection class.
 *
 * @package Forminator
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_Quiz_General_Data_Protection
 *
 * General Data Protection Applied for Quiz
 *
 * @since 1.6.2
 */
class Forminator_Quiz_General_Data_Protection extends Forminator_General_Data_Protection {

	/**
	 * Module slug
	 *
	 * @var string
	 */
	protected static $module_slug = 'quiz';

	/**
	 * Forminator_Quiz_General_Data_Protection constructor
	 */
	public function __construct() {
		parent::__construct( esc_html__( 'Forminator Quizzes', 'forminator' ) );
	}

	/**
	 * Add Privacy Message
	 *
	 * @since 1.0.6
	 *
	 * @return string
	 */
	public function get_privacy_message() {
		ob_start();
		include __DIR__ . '/policy-text.php';
		$content = ob_get_clean();
		$content = apply_filters( 'forminator_quiz_privacy_policy_content', $content );

		return $content;
	}

	/**
	 * Clean up quiz submissions
	 *
	 * @since 1.6.2
	 *
	 * @return bool
	 */
	public function personal_data_cleanup() {

		$global_retain_number = get_option( 'forminator_retain_quiz_submissions_interval_number', 0 );
		$global_retain_unit   = get_option( 'forminator_retain_quiz_submissions_interval_unit', 'days' );

		$quiz_status = 'any';

		/**
		 * Filter quiz status to be processed for data cleanup
		 *
		 * @param string $quiz_status
		 *
		 * @return string
		 */
		$quiz_status = apply_filters( 'forminator_quiz_general_data_protection_cleanup_quiz_status', $quiz_status );

		/**
		 * Get all quizzes
		 */
		$quizzes = Forminator_Quiz_Model::model()->get_all_models( $quiz_status );

		/**
		 * Forminator_Quiz_Model
		 *
		 * @var Forminator_Quiz_Model[] $models */
		$models = isset( $quizzes['models'] ) && is_array( $quizzes['models'] ) ? $quizzes['models'] : array();

		/**
		 * Walk through quizzes
		 */
		foreach ( $models as $model ) {
			if ( ! $model instanceof Forminator_Quiz_Model ) {
				continue;
			}

			$settings = $model->settings;

			/**
			 * Find out whether its overridden
			 */
			$is_overridden = false;
			if ( isset( $settings['enable-submissions-retention'] ) ) {
				$is_overridden = filter_var( $settings['enable-submissions-retention'], FILTER_VALIDATE_BOOLEAN );
			}

			// use overridden settings.
			if ( $is_overridden ) {
				$retain_number = 0;
				if ( isset( $settings['submissions-retention-number'] ) ) {
					$retain_number = intval( $settings['submissions-retention-number'] );
				}

				$retain_unit = 'days';
				if ( isset( $settings['submissions-retention-unit'] ) ) {
					$retain_unit = $settings['submissions-retention-unit'];
				}
			} else {
				// Use GLOBAL settings.
				$retain_number = $global_retain_number;
				$retain_unit   = $global_retain_unit;

			}

			$retain_time = $this->get_retain_time( $retain_number, $retain_unit );
			if ( ! $retain_time ) {
				continue;
			}
			$this->delete_older_entries( $model->id, $retain_time );
		}

		return true;
	}
}
