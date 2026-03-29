<?php
/**
 * Forminator Addon Slack Quiz Hooks.
 *
 * @package Forminator
 */

/**
 * Class Forminator_Slack_Quiz_Hooks
 *
 * @since 1.6.2
 */
class Forminator_Slack_Quiz_Hooks extends Forminator_Integration_Quiz_Hooks {

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
			// save it on entry field, with name `status-$MULTI_ID`, and value is the return result on sending data to slack.
			if ( $this->settings_instance->is_multi_id_completed( $key ) ) {
				// exec only on completed connection.
				$data[] = array(
					'name'  => 'status-' . $key,
					'value' => $this->get_status_on_send_message( $key, $submitted_data, $addon_setting_value, $current_entry_fields ),
				);
			}
		}

		return $data;
	}

	/**
	 * Get status on send message to Slack
	 *
	 * @since 1.6.2
	 *
	 * @param string $connection_id Connection Id.
	 * @param array  $submitted_data Submitted data.
	 * @param array  $connection_settings Connection settings.
	 * @param array  $quiz_entry_fields Quiz entry fields.
	 *
	 * @return array `is_sent` true means its success send data to Slack, false otherwise
	 * @throws Forminator_Integration_Exception Throws Integration Exception.
	 */
	private function get_status_on_send_message( $connection_id, $submitted_data, $connection_settings, $quiz_entry_fields ) {
		// initialize as null.
		$api = null;

		$quiz_id                = $this->module_id;
		$quiz_settings_instance = $this->settings_instance;
		$quiz_settings          = $this->settings_instance->get_quiz_settings();
		$addons_fields          = $this->settings_instance->get_form_fields();

		// check required fields.
		try {
			$api              = $this->addon->get_api();
			$args             = array();
			$lead_attachments = array();

			if ( ! isset( $connection_settings['target_id'] ) ) {
				throw new Forminator_Integration_Exception( esc_html__( 'Target ID not properly set up.', 'forminator' ) );
			}

			if ( ! isset( $connection_settings['message'] ) ) {
				throw new Forminator_Integration_Exception( esc_html__( 'Message not properly set up.', 'forminator' ) );
			}
			$text_message = $connection_settings['message'];
			$text_message = forminator_replace_variables( $text_message );

			$text_message = str_ireplace( '{quiz_name}', forminator_get_name_from_model( $this->module ), $text_message );

			$quiz_attachments = $this->get_quiz_data_as_attachments( $submitted_data, $quiz_entry_fields );

			if ( isset( $quiz_settings['hasLeads'] ) && $quiz_settings['hasLeads'] ) {
				$form_entry_fields   = forminator_lead_form_data( $submitted_data );
				$lead_submitted_data = forminator_addons_lead_submitted_data( $addons_fields, $form_entry_fields );
				$text_message        = forminator_addon_replace_custom_vars( $text_message, $lead_submitted_data, $this->lead_model, $form_entry_fields );
				$lead_attachments    = $this->get_lead_form_fields_as_attachments( $lead_submitted_data, $form_entry_fields );
			}

			$attachments = array_merge( $quiz_attachments, $lead_attachments );
			/**
			 * Filter `attachments` to passed onto API
			 *
			 * @since 1.4
			 *
			 * @param string                               $card_name
			 * @param int                                  $quiz_id                Current Quiz id.
			 * @param string                               $connection_id          ID of current connection.
			 * @param array                                $submitted_data
			 * @param array                                $connection_settings    current connection setting, contains options of like `name`, `target_id` etc.
			 * @param array                                $quiz_entry_fields      default entry fields of form.
			 * @param array                                $quiz_settings          Displayed Quiz settings.
			 * @param Forminator_Slack_Quiz_Settings $quiz_settings_instance Slack Integration Quiz Settings instance.
			 */
			$attachments = apply_filters(
				'forminator_addon_slack_quiz_message_attachments',
				$attachments,
				$quiz_id,
				$connection_id,
				$submitted_data,
				$connection_settings,
				$quiz_entry_fields,
				$quiz_settings,
				$quiz_settings_instance
			);

			if ( ! empty( $attachments ) ) {
				$args['attachments'] = $attachments;
			}

			$args['mrkdwn'] = true;
			/**
			 * Filter arguments to passed on to Send Message Slack API
			 *
			 * @since 1.3
			 *
			 * @param array                                $args
			 * @param int                                  $quiz_id                Current Quiz id.
			 * @param string                               $connection_id          ID of current connection.
			 * @param array                                $submitted_data
			 * @param array                                $connection_settings    current connection setting, contains options of like `name`, `target_id` etc.
			 * @param array                                $quiz_entry_fields      default entry fields of form.
			 * @param array                                $quiz_settings          Displayed Quiz settings.
			 * @param Forminator_Slack_Quiz_Settings $quiz_settings_instance Slack Integration Quiz Settings instance.
			 */
			$args = apply_filters(
				'forminator_addon_slack_quiz_send_message_args',
				$args,
				$quiz_id,
				$connection_id,
				$submitted_data,
				$connection_settings,
				$quiz_entry_fields,
				$quiz_settings,
				$quiz_settings_instance
			);

			$post_message_request = $api->chat_post_message( $connection_settings['target_id'], $text_message, $args );

			$ts = '';
			if ( is_object( $post_message_request ) && isset( $post_message_request->ts ) ) {
				$ts = (string) $post_message_request->ts;
			}

			return array(
				'is_sent'         => true,
				'connection_name' => $connection_settings['name'],
				'description'     => esc_html__( 'Successfully send data to Slack', 'forminator' ),
				'data_sent'       => $api->get_last_data_sent(),
				'data_received'   => $api->get_last_data_received(),
				'url_request'     => $api->get_last_url_request(),
				'ts'              => $ts, // for delete reference.
				'target_id'       => $connection_settings['target_id'], // for delete reference.
			);

		} catch ( Forminator_Integration_Exception $e ) {
			$addon_entry_fields = array(
				'is_sent'         => false,
				'description'     => $e->getMessage(),
				'connection_name' => $connection_settings['name'],
				'data_sent'       => ( ( $api instanceof Forminator_Slack_Wp_Api ) ? $api->get_last_data_sent() : array() ),
				'data_received'   => ( ( $api instanceof Forminator_Slack_Wp_Api ) ? $api->get_last_data_received() : array() ),
				'url_request'     => ( ( $api instanceof Forminator_Slack_Wp_Api ) ? $api->get_last_url_request() : '' ),
				'ts'              => '', // for delete reference,.
				'target_id'       => '', // for delete reference,.
			);

			return $addon_entry_fields;
		}
	}

	/**
	 * Get Quiz Data as attachments
	 *
	 * @since 1.6.2
	 *
	 * @param array $submitted_data Submitted data.
	 * @param array $quiz_entry_fields Quiz entry fields.
	 *
	 * @return array
	 */
	public function get_quiz_data_as_attachments( $submitted_data, $quiz_entry_fields ) {
		$attachments = array();

		/**
		 * Attachment 1
		 * Answers
		 *  - Questions
		 *  - Answer
		 */
		$answers         = array();
		$correct_answers = 0;
		$total_answers   = 0;
		$nowrong_result  = '';
		if ( is_array( $quiz_entry_fields ) && isset( $quiz_entry_fields[0] ) ) {
			$quiz_entry = $quiz_entry_fields[0];
			if ( isset( $quiz_entry['name'] ) && isset( $quiz_entry['value'] ) && 'entry' === $quiz_entry['name'] ) {
				if ( is_array( $quiz_entry['value'] ) ) {

					// KNOWLEDGE.
					if ( 'knowledge' === $this->module->quiz_type ) {
						foreach ( $quiz_entry['value'] as $data ) {
							$question   = isset( $data['question'] ) ? $data['question'] : '';
							$answer     = isset( $data['answer'] ) ? $data['answer'] : '';
							$is_correct = isset( $data['isCorrect'] ) ? $data['isCorrect'] : false;

							$answers[] = array(
								'question'   => $question,
								'answer'     => $answer,
								'is_correct' => $is_correct,
								'result'     => $is_correct ? esc_html__( 'Correct', 'forminator' ) : esc_html__( 'Incorrect', 'forminator' ),
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

							foreach ( $entry_questions as $entry_question ) {
								$question = isset( $entry_question['question'] ) ? $entry_question['question'] : '';
								$answer   = isset( $entry_question['answer'] ) ? $entry_question['answer'] : '';

								$answers[] = array(
									'question'   => $question,
									'answer'     => $answer,
									'result'     => $nowrong_result,
									'is_correct' => true,
								);
							}
						}
					}
				}
			}
		}

		foreach ( $answers as $answer ) {
			$attachment = array(
				'title' => $answer['question'],
			);
			if ( 'knowledge' === $this->module->quiz_type ) {
				$attachment['color'] = $answer['is_correct'] ? 'good' : 'danger';
			}

			$attachment_field     = array(
				'title' => '',
				'value' => $answer['answer'],
				'short' => false,
			);
			$attachment['fields'] = array( $attachment_field );

			$attachments[] = $attachment;
		}

		/**
		 * Attachment 2
		 * Result
		 */
		$attachment_fields = array();

		if ( 'knowledge' === $this->module->quiz_type ) {
			$attachment_fields[] = array(
				'title' => esc_html__( 'Correct Answers', 'forminator' ),
				'value' => $correct_answers,
				'short' => true,
			);
			$attachment_fields[] = array(
				'title' => esc_html__( 'Total Answers', 'forminator' ),
				'value' => $total_answers,
				'short' => true,
			);
		} elseif ( 'nowrong' === $this->module->quiz_type ) {
			$attachment_fields[] = array(
				'title' => $nowrong_result,
				'value' => '',
				'short' => false,
			);
		}

		$attachments[] = array(
			'title'  => esc_html__( 'Quiz Result', 'forminator' ),
			'fields' => $attachment_fields,
			'color'  => 'warning',
		);

		return $attachments;
	}

	/**
	 * Get All Form Fields as attachments
	 *
	 * @since 1.0 Slack Integration
	 *
	 * @param array $submitted_data Submitted data.
	 * @param array $form_entry_fields Form entry fields.
	 *
	 * @return array
	 */
	public function get_lead_form_fields_as_attachments( $submitted_data, $form_entry_fields ) {
		$attachments                   = array();
		$all_fields_attachments        = array();
		$all_fields_attachments_fields = array();
		$form_fields                   = $this->settings_instance->get_form_fields();
		$field_format                  = array();
		$post_element_ids              = array();

		foreach ( $form_fields as $form_field ) {
			$element_id  = $form_field['element_id'];
			$field_type  = $form_field['type'];
			$field_label = $form_field['field_label'];

			$post_element_id = $element_id;
			if ( stripos( $field_type, 'postdata' ) !== false ) {
				$post_type       = $form_field['post_type'];
				$category_list   = forminator_post_categories( $post_type );
				$post_element_id = str_ireplace( '-post-title', '', $post_element_id );
				$post_element_id = str_ireplace( '-post-content', '', $post_element_id );
				$post_element_id = str_ireplace( '-post-excerpt', '', $post_element_id );
				if ( ! empty( $category_list ) ) {
					foreach ( $category_list as $category ) {
						$post_element_id = str_ireplace( '-' . $category['value'], '', $post_element_id );
						$field_format[]  = 'postdata-' . $category['value'];
					}
				}
				$post_element_id = str_ireplace( '-post-image', '', $post_element_id );

				// only add postdata as single.
				if ( in_array( $post_element_id, $post_element_ids, true ) ) {
					continue;
				}
				$post_element_ids[] = $post_element_id;
			}

			switch ( $field_type ) {
				case 'postdata-post-title':
				case 'postdata-post-content':
				case 'postdata-post-excerpt':
				case 'postdata-post-image':
					$field_value                     = '{' . $post_element_id . '}';
					$field_value                     = forminator_addon_replace_custom_vars( $field_value, $submitted_data, $this->lead_model, $form_entry_fields, false );
					$all_fields_attachments_fields[] = array(
						'title' => $field_label,
						'value' => ( empty( $field_value ) ? '-' : $field_value ),
						'short' => false,
					);

					break;
				default:
					$field_value                     = '{' . $element_id . '}';
					$field_value                     = forminator_addon_replace_custom_vars( $field_value, $submitted_data, $this->lead_model, $form_entry_fields, false );
					$all_fields_attachments_fields[] = array(
						'title' => $field_label,
						'value' => ( empty( $field_value ) ? '-' : $field_value ),
						'short' => false,
					);
					break;
			}

			if ( in_array( $field_type, $field_format, true ) ) {

				$field_value                     = '{' . $post_element_id . '}';
				$field_value                     = forminator_addon_replace_custom_vars( $field_value, $submitted_data, $this->lead_model, $form_entry_fields, false );
				$all_fields_attachments_fields[] = array(
					'title' => $field_label,
					'value' => ( empty( $field_value ) ? '-' : $field_value ),
					'short' => false,
				);
			}
		}

		$all_fields_attachments['fields'] = $all_fields_attachments_fields;
		$attachments[]                    = $all_fields_attachments;

		return $attachments;
	}
}
