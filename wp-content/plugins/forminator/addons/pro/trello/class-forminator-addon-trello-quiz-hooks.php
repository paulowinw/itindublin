<?php
/**
 * Forminator Trello poll hooks
 *
 * @package Forminator
 */

/**
 * Class Forminator_Trello_Quiz_Hooks
 *
 * @since 1.6.2
 */
class Forminator_Trello_Quiz_Hooks extends Forminator_Integration_Quiz_Hooks {

	/**
	 * Return custom entry fields
	 *
	 * @param array $submitted_data Submitted data.
	 * @param array $current_entry_fields Current entry fields.
	 * @return array
	 */
	protected function custom_entry_fields( $submitted_data, $current_entry_fields ): array {
		$entry                = func_get_args()[2];
		$addon_setting_values = $this->settings_instance->get_settings_values();
		$data                 = array();

		foreach ( $addon_setting_values as $key => $addon_setting_value ) {
			// save it on entry field, with name `status-$MULTI_ID`, and value is the return result on sending data to trello.
			if ( $this->settings_instance->is_multi_id_completed( $key ) ) {
				// exec only on completed connection.
				$data[] = array(
					'name'  => 'status-' . $key,
					'value' => $this->get_status_on_create_card( $key, $submitted_data, $addon_setting_value, $current_entry_fields, $entry ),
				);
			}
		}

		return $data;
	}

	/**
	 * Get status on create Trello card
	 *
	 * @since 1.6.2
	 *
	 * @param string      $connection_id Connection Id.
	 * @param array       $submitted_data Submitted data.
	 * @param array       $connection_settings Connection settings.
	 * @param array       $current_entry_fields Form entry fields.
	 * @param null|object $entry Entry instance.
	 *
	 * @return array `is_sent` true means its success send data to Trello, false otherwise
	 */
	private function get_status_on_create_card( $connection_id, $submitted_data, $connection_settings, $current_entry_fields, $entry = null ) {
		// initialize as null.
		$api = null;

		$quiz_id                = $this->module_id;
		$quiz_settings_instance = $this->settings_instance;

		// check required fields.
		try {
			$api     = $this->addon->get_api();
			$args    = array();
			$entries = null;

			$quiz_settings = $this->settings_instance->get_quiz_settings();
			$addons_fields = $this->settings_instance->get_form_fields();

			if ( isset( $connection_settings['list_id'] ) ) {
				$args['idList'] = $connection_settings['list_id'];
			}

			$form_entry_fields   = forminator_lead_form_data( $submitted_data );
			$lead_submitted_data = forminator_addons_lead_submitted_data( $addons_fields, $form_entry_fields );

			if ( isset( $connection_settings['card_name'] ) ) {
				$card_name = $connection_settings['card_name'];
				// disable all_fields here.
				$card_name = forminator_replace_variables( $card_name, $quiz_id, $entry );
				$card_name = str_ireplace( '{quiz_name}', forminator_get_name_from_model( $this->module ), $card_name );

				if ( isset( $quiz_settings['hasLeads'] ) && $quiz_settings['hasLeads'] ) {
					$card_name = forminator_addon_replace_custom_vars( $card_name, $lead_submitted_data, $this->lead_model, $form_entry_fields, $entry );
				}

				/**
				 * Filter Card Name to passed on to Create Trello Card API
				 *
				 * @since 1.6.2
				 *
				 * @param string                                $card_name
				 * @param int                                   $quiz_id                Current Quiz id.
				 * @param string                                $connection_id          ID of current connection.
				 * @param array                                 $submitted_data
				 * @param array                                 $connection_settings    current connection setting, contains options of like `name`, `list_id` etc.
				 * @param array                                 $current_entry_fields   default entry fields of quiz.
				 * @param array                                 $quiz_settings          Displayed Quiz settings.
				 * @param Forminator_Trello_Quiz_Settings $quiz_settings_instance Trello Integration Quiz Settings instance.
				 */
				$card_name    = apply_filters(
					'forminator_addon_trello_quiz_card_name',
					$card_name,
					$quiz_id,
					$connection_id,
					$submitted_data,
					$connection_settings,
					$current_entry_fields,
					$quiz_settings,
					$quiz_settings_instance
				);
				$args['name'] = $card_name;

			}

			if ( isset( $connection_settings['card_description'] ) ) {
				$card_description         = $connection_settings['card_description'];
				$quiz_answers_to_markdown = $this->quiz_answers_to_markdown( $current_entry_fields );
				$quiz_result_to_markdown  = $this->quiz_result_to_markdown( $current_entry_fields );
				$card_description         = str_ireplace( '{quiz_name}', '#' . forminator_get_name_from_model( $this->module ), $card_description );
				$card_description         = str_ireplace( '{quiz_answer}', $quiz_answers_to_markdown, $card_description );
				$card_description         = str_ireplace( '{quiz_result}', $quiz_result_to_markdown, $card_description );
				$card_description         = forminator_replace_variables( $card_description, $quiz_id, $entry );
				if ( isset( $quiz_settings['hasLeads'] ) && $quiz_settings['hasLeads'] ) {
					$card_description = forminator_addon_replace_custom_vars( $card_description, $lead_submitted_data, $this->lead_model, $form_entry_fields, false, $entry );
				}

				/**
				 * Filter Card Description to passed on to Create Trello Card API
				 *
				 * @since 1.6.2
				 *
				 * @param string                                $card_description
				 * @param int                                   $quiz_id                Current Quiz id.
				 * @param string                                $connection_id          ID of current connection.
				 * @param array                                 $submitted_data
				 * @param array                                 $connection_settings    current connection setting, contains options of like `name`, `list_id` etc.
				 * @param array                                 $current_entry_fields   default entry fields of quiz.
				 * @param array                                 $quiz_settings          Displayed Quiz settings.
				 * @param Forminator_Trello_Quiz_Settings $quiz_settings_instance Trello Integration Quiz Settings instance.
				 */
				$card_description = apply_filters(
					'forminator_addon_trello_quiz_card_description',
					$card_description,
					$quiz_id,
					$connection_id,
					$submitted_data,
					$connection_settings,
					$current_entry_fields,
					$quiz_settings,
					$quiz_settings_instance
				);
				$args['desc']     = $card_description;
			}
			if ( ! empty( $quiz_settings['hasLeads'] ) && ! empty( $connection_settings['due_date'] ) ) {
				$due_date    = forminator_addon_replace_custom_vars( $connection_settings['due_date'], $lead_submitted_data, $this->lead_model, $form_entry_fields, false );
				$args['due'] = $due_date;
			}
			if ( isset( $connection_settings['position'] ) ) {
				$args['pos'] = $connection_settings['position'];
			}

			if ( isset( $connection_settings['label_ids'] ) && is_array( $connection_settings['label_ids'] ) ) {
				$args['idLabels'] = implode( ',', $connection_settings['label_ids'] );
			}

			if ( isset( $connection_settings['member_ids'] ) && is_array( $connection_settings['member_ids'] ) ) {
				$args['idMembers'] = implode( ',', $connection_settings['member_ids'] );
			}

			if ( isset( $submitted_data['_wp_http_referer'] ) ) {
				$url_source = home_url( $submitted_data['_wp_http_referer'] );
				if ( wp_http_validate_url( $url_source ) ) {
					$args['urlSource'] = $url_source;
				}
			}

			/**
			 * Filter arguments to passed on to Create Trello Card API
			 *
			 * @since 1.6.2
			 *
			 * @param array                                 $args
			 * @param int                                   $quiz_id                Current Quiz id.
			 * @param string                                $connection_id          ID of current connection.
			 * @param array                                 $submitted_data
			 * @param array                                 $connection_settings    current connection setting, contains options of like `name`, `list_id` etc.
			 * @param array                                 $quiz_settings          Displayed Quiz settings.
			 * @param Forminator_Trello_Quiz_Settings $quiz_settings_instance Trello Integration Quiz Settings instance.
			 */
			$args = apply_filters(
				'forminator_addon_trello_quiz_create_card_args',
				$args,
				$quiz_id,
				$connection_id,
				$submitted_data,
				$connection_settings,
				$quiz_settings,
				$quiz_settings_instance
			);

			$api->create_card( $args );

			forminator_addon_maybe_log( __METHOD__, 'Success Send Data' );

			$multi_global_ids = $this->addon->get_multi_global_ids();
			$name_suffix      = ! empty( $this->addon->multi_global_id )
					&& ! empty( $multi_global_ids[ $this->addon->multi_global_id ] )
					? ' - ' . $multi_global_ids[ $this->addon->multi_global_id ] : '';

			return array(
				'is_sent'         => true,
				'connection_name' => $connection_settings['name'] . $name_suffix,
				'description'     => esc_html__( 'Successfully send data to Trello', 'forminator' ),
				'data_sent'       => $api->get_last_data_sent(),
				'data_received'   => $api->get_last_data_received(),
				'url_request'     => $api->get_last_url_request(),
			);

		} catch ( Forminator_Integration_Exception $e ) {
			forminator_addon_maybe_log( __METHOD__, 'Failed to Send to Trello' );

			return array(
				'is_sent'         => false,
				'description'     => $e->getMessage(),
				'connection_name' => $connection_settings['name'],
				'data_sent'       => ( ( $api instanceof Forminator_Trello_Wp_Api ) ? $api->get_last_data_sent() : array() ),
				'data_received'   => ( ( $api instanceof Forminator_Trello_Wp_Api ) ? $api->get_last_data_received() : array() ),
				'url_request'     => ( ( $api instanceof Forminator_Trello_Wp_Api ) ? $api->get_last_url_request() : '' ),
			);
		}
	}

	/**
	 * Special Replacer `{quiz_answer}` to markdown with Trello Flavour
	 *
	 * @param array $quiz_entry_fields Quiz entry fields.
	 *
	 * @return string
	 */
	private function quiz_answers_to_markdown( $quiz_entry_fields ) {
		$markdown = '';

		if ( is_array( $quiz_entry_fields ) && isset( $quiz_entry_fields[0] ) ) {
			$quiz_entry = $quiz_entry_fields[0];
			if ( isset( $quiz_entry['name'] ) && isset( $quiz_entry['value'] ) && 'entry' === $quiz_entry['name'] ) {
				if ( is_array( $quiz_entry['value'] ) ) {
					if ( 'knowledge' === $this->module->quiz_type ) {
						foreach ( $quiz_entry['value'] as $data ) {
							$question   = isset( $data['question'] ) ? $data['question'] : '';
							$answer     = isset( $data['answer'] ) ? $data['answer'] : '';
							$is_correct = isset( $data['isCorrect'] ) ? $data['isCorrect'] : false;

							$markdown .= '###' . $question . "\n";
							$markdown .= $answer . "\n";
							$markdown .= esc_html__( 'Correct : ', 'forminator' )
										. '**' . ( $is_correct ? esc_html__( 'Yes', 'forminator' ) : esc_html__( 'No', 'forminator' ) ) . '**'
										. "\n";
						}
					} elseif ( 'nowrong' === $this->module->quiz_type ) {
						if ( isset( $quiz_entry['value'][0] )
							&& is_array( $quiz_entry['value'][0] )
							&& isset( $quiz_entry['value'][0]['value'] )
							&& is_array( $quiz_entry['value'][0]['value'] ) ) {

							$quiz_entry = $quiz_entry['value'][0]['value'];

							$entry_questions = ( isset( $quiz_entry['answers'] ) && is_array( $quiz_entry['answers'] ) ) ? $quiz_entry['answers'] : array();

							foreach ( $entry_questions as $entry_question ) {
								$question = isset( $entry_question['question'] ) ? $entry_question['question'] : '';
								$answer   = isset( $entry_question['answer'] ) ? $entry_question['answer'] : '';

								$markdown .= '###' . $question . "\n";
								$markdown .= $answer . "\n";
							}
						}
					}
				}
			}
		}

		/**
		 * Filter markdown for `quiz_answer`
		 *
		 * @since 1.6.2
		 *
		 * @param string $markdown
		 * @param array  $quiz_entry_fields Entry Fields.
		 */
		$markdown = apply_filters(
			'forminator_addon_trello_quiz_answer_markdown',
			$markdown,
			$quiz_entry_fields
		);

		return $markdown;
	}

	/**
	 * Special Replacer `{quiz_result}` to markdown with Trello Flavour
	 *
	 * @since 1.6.2
	 *
	 * @param array $quiz_entry_fields Quiz entry fields.
	 *
	 * @return string
	 */
	private function quiz_result_to_markdown( $quiz_entry_fields ) {
		$markdown = '';

		if ( is_array( $quiz_entry_fields ) && isset( $quiz_entry_fields[0] ) ) {
			$quiz_entry = $quiz_entry_fields[0];
			if ( isset( $quiz_entry['name'] ) && isset( $quiz_entry['value'] ) && 'entry' === $quiz_entry['name'] ) {
				if ( is_array( $quiz_entry['value'] ) ) {
					if ( 'knowledge' === $this->module->quiz_type ) {
						$total_correct = 0;
						$total_answers = 0;
						foreach ( $quiz_entry['value'] as $data ) {
							$is_correct = isset( $data['isCorrect'] ) ? $data['isCorrect'] : false;
							if ( $is_correct ) {
								++$total_correct;
							}
							++$total_answers;
						}

						$markdown .= '##' . esc_html__( 'Quiz Result', 'forminator' ) . "\n";
						$markdown .= esc_html__( 'Correct Answers : ', 'forminator' )
									. '**' . $total_correct . '**'
									. "\n";
						$markdown .= esc_html__( 'Total Answers : ', 'forminator' )
									. '**' . $total_answers . '**'
									. "\n";

					} elseif ( 'nowrong' === $this->module->quiz_type ) {
						if ( isset( $quiz_entry['value'][0] )
							&& is_array( $quiz_entry['value'][0] )
							&& isset( $quiz_entry['value'][0]['value'] )
							&& is_array( $quiz_entry['value'][0]['value'] ) ) {

							$quiz_entry     = $quiz_entry['value'][0]['value'];
							$nowrong_result = ( isset( $quiz_entry['result'] ) && isset( $quiz_entry['result']['title'] ) ) ? $quiz_entry['result']['title'] : '';

							$markdown .= '##' . esc_html__( 'Quiz Result', 'forminator' ) . "\n";
							$markdown .= '**' . $nowrong_result . '**'
										. "\n";

						}
					}
				}
			}
		}

		/**
		 * Filter markdown for `quiz_result`
		 *
		 * @since 1.6.2
		 *
		 * @param string $markdown
		 * @param array  $quiz_entry_fields Entry Fields.
		 */
		$markdown = apply_filters(
			'forminator_addon_trello_quiz_result_markdown',
			$markdown,
			$quiz_entry_fields
		);

		return $markdown;
	}
}
