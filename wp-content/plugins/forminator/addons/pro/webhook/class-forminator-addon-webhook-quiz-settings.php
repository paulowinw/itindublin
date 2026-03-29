<?php
/**
 * Forminator Webhook Quiz Settings
 *
 * @package Forminator
 */

/**
 * Class Forminator_Webhook_Quiz_Settings
 * Handle how quiz settings displayed and saved
 *
 * @since 1.6.2
 */
class Forminator_Webhook_Quiz_Settings extends Forminator_Integration_Quiz_Settings {
	use Forminator_Webhook_Settings_Trait;

	/**
	 * Has lead
	 *
	 * @return bool
	 */
	public function has_lead() {
		return true;
	}

	/**
	 * Build sample data form current fields
	 *
	 * @since 1.6.2
	 *
	 * @return array
	 */
	private function build_form_sample_data() {
		$sample_data = array();

		$sample_data['quiz-name'] = forminator_get_name_from_model( $this->quiz );
		$answers                  = array();

		$num_correct = 0;

		$questions = $this->quiz->questions;

		foreach ( $questions as $question ) {
			$question_title = $question['title'] ?? '';
			$question_id    = $question['slug'] ?? uniqid();

			// bit cleanup.
			$question_id  = str_replace( 'question-', '', $question_id );
			$answer_title = 'Sample Answer';

			$answer = array(
				'question' => $question_title,
				'answer'   => $answer_title,
			);

			if ( 'knowledge' === $this->quiz->quiz_type ) {
				$answer['is_correct'] = wp_rand( 0, 1 ) ? true : false;

				if ( $answer['is_correct'] ) {
					++$num_correct;
				}
			}

			$answers[ $question_id ] = $answer;
		}

		$sample_data['answers'] = $answers;
		$result                 = array();

		if ( 'knowledge' === $this->quiz->quiz_type ) {
			$result['correct'] = $num_correct;
			$result['answers'] = count( $answers );
		} elseif ( 'nowrong' === $this->quiz->quiz_type ) {
			$results           = $this->quiz->results;
			$random_result_key = array_rand( $results );
			$result_title      = ( ( isset( $results[ $random_result_key ] ) && isset( $results[ $random_result_key ]['title'] ) ) ? $results[ $random_result_key ]['title'] : '' );
			$result['result']  = $result_title;
		}

		$sample_data['result'] = $result;
		$sample_data          += $this->prepare_form_fields_data();

		return $sample_data;
	}
}
