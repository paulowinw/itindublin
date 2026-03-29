<?php
/**
 * Forminator Webhook Quiz Hooks
 *
 * @package Forminator
 */

/**
 * Class Forminator_Webhook_Quiz_Hooks
 *
 * @since 1.6.2
 */
class Forminator_Webhook_Quiz_Hooks extends Forminator_Integration_Quiz_Hooks {

	/**
	 * Return custom entry fields
	 *
	 * @param array $submitted_data Submitted data.
	 * @param array $current_entry_fields Current entry fields.
	 * @return array
	 */
	protected function custom_entry_fields( $submitted_data, $current_entry_fields ): array {
		$addon_setting_values = $this->settings_instance->get_settings_values();
		$data                 = array();

		foreach ( $addon_setting_values as $key => $addon_setting_value ) {
			// save it on entry field, with name `status-$MULTI_ID`, and value is the return result on sending data towebhook.
			$data[] = array(
				'name'  => 'status-' . $key,
				'value' => $this->get_status_on_send_data( $key, $submitted_data, $addon_setting_value, $current_entry_fields ),
			);
		}

		return $data;
	}

	/**
	 * Get status on sending data towebhook
	 *
	 * @since 1.6.2
	 *
	 * @param string $connection_id Connection Id.
	 * @param array  $submitted_data Submitted data.
	 * @param array  $connection_settings Connection settings.
	 * @param array  $current_entry_fields Form entry fields.
	 *
	 * @return array `is_sent` true means its success send data towebhook, false otherwise
	 * @throws Forminator_Integration_Exception Throws Integration Exception.
	 */
	private function get_status_on_send_data( $connection_id, $submitted_data, $connection_settings, $current_entry_fields ) {
		$quiz_settings = $this->settings_instance->get_quiz_settings();
		// initialize as null.
		$webhook_api = null;

		$quiz_id                = $this->module_id;
		$quiz_settings_instance = $this->settings_instance;

		// check required fields.
		try {
			if ( ! isset( $connection_settings['webhook_url'] ) ) {
				throw new Forminator_Integration_Exception( esc_html__( 'Webhook URL is not properly set up', 'forminator' ) );
			}

			$endpoint = $connection_settings['webhook_url'];
			/**
			 * Filter Endpoint Webhook URL to send
			 *
			 * @since 1.6.2
			 *
			 * @param string $endpoint
			 * @param int    $quiz_id             current Form ID.
			 * @param array  $connection_settings current connection setting, it contains `name` and `webhook_url`.
			 */
			$endpoint = apply_filters(
				'forminator_addon_webhook_quiz_endpoint',
				$endpoint,
				$quiz_id,
				$connection_settings
			);

			$webhook_api = $this->addon->get_api( $endpoint );

			$args = $this->build_post_data( $current_entry_fields, $submitted_data );
			$args = $quiz_settings_instance::replace_dashes_in_keys( $args, $endpoint );

			/**
			 * Filter arguments to passed on to Webhook API
			 *
			 * @since 1.6.2
			 *
			 * @param array                                 $args
			 * @param int                                   $quiz_id                Current Quiz id.
			 * @param string                                $connection_id          ID of current connection.
			 * @param array                                 $submitted_data
			 * @param array                                 $connection_settings    current connection setting, contains `name` and `webhook_url`.
			 * @param array                                 $quiz_settings          Displayed Quiz settings.
			 * @param Forminator_Webhook_Quiz_Settings $quiz_settings_instance Webhook Quiz Settings instance.
			 */
			$args = apply_filters(
				'forminator_addon_webhook_quiz_post_to_webhook_args',
				$args,
				$quiz_id,
				$connection_id,
				$submitted_data,
				$connection_settings,
				$quiz_settings,
				$quiz_settings_instance
			);

			$args = $quiz_settings_instance::replace_dashes_in_keys( $args, $endpoint );

			$webhook_api->post_( $args );

			forminator_addon_maybe_log( __METHOD__, 'Success Send Data' );

			return array(
				'is_sent'         => true,
				'connection_name' => $connection_settings['name'],
				'description'     => esc_html__( 'Successfully send data to Webhook', 'forminator' ),
				'data_sent'       => $webhook_api->get_last_data_sent(),
				'data_received'   => $webhook_api->get_last_data_received(),
				'url_request'     => $webhook_api->get_last_url_request(),
			);

		} catch ( Forminator_Integration_Exception $e ) {
			forminator_addon_maybe_log( __METHOD__, 'Failed to Send to Webhook' );

			return array(
				'is_sent'         => false,
				'description'     => $e->getMessage(),
				'connection_name' => $connection_settings['name'],
				'data_sent'       => ( ( $webhook_api instanceof Forminator_Webhook_Wp_Api ) ? $webhook_api->get_last_data_sent() : array() ),
				'data_received'   => ( ( $webhook_api instanceof Forminator_Webhook_Wp_Api ) ? $webhook_api->get_last_data_received() : array() ),
				'url_request'     => ( ( $webhook_api instanceof Forminator_Webhook_Wp_Api ) ? $webhook_api->get_last_url_request() : '' ),
			);
		}
	}

	/**
	 * Build sample data form current fields
	 *
	 * @since 1.6.2
	 *
	 * @param array $quiz_entry_fields Quiz entry fields.
	 * @param array $submitted_data Submitted data.
	 *
	 * @return array
	 */
	private function build_post_data( $quiz_entry_fields, $submitted_data ) {
		$sample = array();

		$sample['quiz-name'] = forminator_get_name_from_model( $this->module );

		$answers         = array();
		$correct_answers = 0;
		$total_answers   = 0;
		$nowrong_result  = '';
		$questions       = $this->module->questions;

		if ( is_array( $quiz_entry_fields ) && isset( $quiz_entry_fields[0] ) ) {
			$quiz_entry = $quiz_entry_fields[0];
			if ( isset( $quiz_entry['name'] ) && isset( $quiz_entry['value'] ) && 'entry' === $quiz_entry['name'] ) {
				if ( is_array( $quiz_entry['value'] ) ) {

					// KNOWLEDGE.
					if ( 'knowledge' === $this->module->quiz_type ) {
						foreach ( $quiz_entry['value'] as $key => $data ) {
							$question_id = ( ( isset( $questions[ $key ] ) && isset( $questions[ $key ]['slug'] ) ) ? $questions[ $key ]['slug'] : uniqid() );
							// bit cleanup.
							$question_id = str_replace( 'question-', '', $question_id );

							$question   = isset( $data['question'] ) ? $data['question'] : '';
							$answer     = isset( $data['answers'] ) ? $data['answers'] : '';
							$is_correct = isset( $data['isCorrect'] ) ? $data['isCorrect'] : false;

							$answers[ $question_id ] = array(
								'question'   => $question,
								'answer'     => $answer,
								'is_correct' => $is_correct,
							);
							if ( $is_correct ) {
								++$correct_answers;
							}
							++$total_answers;
						}
					} elseif ( 'nowrong' === $this->module->quiz_type ) {
						if ( isset( $quiz_entry['value'][0] )
							&& is_array( $quiz_entry['value'][0] )
							&& isset( $quiz_entry['value'][0]['value'] )
							&& is_array( $quiz_entry['value'][0]['value'] ) ) {

							$quiz_entry = $quiz_entry['value'][0]['value'];

							$nowrong_result = ( isset( $quiz_entry['result'] ) && isset( $quiz_entry['result']['title'] ) ) ? $quiz_entry['result']['title'] : '';

							$entry_questions = ( isset( $quiz_entry['answers'] ) && is_array( $quiz_entry['answers'] ) ) ? $quiz_entry['answers'] : array();

							foreach ( $entry_questions as $key => $entry_question ) {
								$question_id = ( ( isset( $questions[ $key ] ) && isset( $questions[ $key ]['slug'] ) ) ? $questions[ $key ]['slug'] : uniqid() );
								// bit cleanup.
								$question_id = str_replace( 'question-', '', $question_id );

								$question = isset( $entry_question['question'] ) ? $entry_question['question'] : '';
								$answer   = isset( $entry_question['answer'] ) ? $entry_question['answer'] : '';

								$answers[ $question_id ] = array(
									'question' => $question,
									'answer'   => $answer,
								);
							}
						}
					}
				}
			}
		}

		$sample['answers'] = $answers;
		$result            = array();

		if ( 'knowledge' === $this->module->quiz_type ) {
			$result['correct'] = $correct_answers;
			$result['answers'] = $total_answers;

		} elseif ( 'nowrong' === $this->module->quiz_type ) {
			$result['result'] = $nowrong_result;
		}

		$sample['result'] = $result;

		$quiz_settings = $this->settings_instance->get_quiz_settings();

		// Use the following code block only when Leads is active.
		if ( isset( $quiz_settings['hasLeads'] ) && $quiz_settings['hasLeads'] ) {
			$addons_fields       = $this->settings_instance->get_form_fields();
			$quiz_submitted_data = get_addons_lead_form_entry_data( $quiz_settings, $submitted_data, $addons_fields );

			if ( ! empty( $quiz_submitted_data ) ) {
				foreach ( $quiz_submitted_data as $s => $quiz_submitted ) {
					$sample[ $s ] = $quiz_submitted;
				}
			}
		}

		return $sample;
	}
}
