<?php
/**
 * Forminator Trello poll hooks
 *
 * @package Forminator
 */

/**
 * Class Forminator_Trello_Poll_Hooks
 *
 * @since 1.6.1
 */
class Forminator_Trello_Poll_Hooks extends Forminator_Integration_Poll_Hooks {

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
	 * @since 1.6.1
	 *
	 * @param string $connection_id Connection Id.
	 * @param array  $submitted_data Submitted data.
	 * @param array  $connection_settings Connection settings.
	 * @param array  $current_entry_fields Form entry fields.
	 * @param object $entry Entry instance.
	 *
	 * @return array `is_sent` true means its success send data to Trello, false otherwise
	 */
	private function get_status_on_create_card( $connection_id, $submitted_data, $connection_settings, $current_entry_fields, $entry ) {
		// initialize as null.
		$api = null;

		$poll_id                = $this->module_id;
		$poll_settings_instance = $this->settings_instance;

		// check required fields.
		try {
			$api  = $this->addon->get_api();
			$args = array();

			$poll_settings = $this->settings_instance->get_poll_settings();

			if ( isset( $connection_settings['list_id'] ) ) {
				$args['idList'] = $connection_settings['list_id'];
			}

			if ( isset( $connection_settings['card_name'] ) ) {
				$card_name = $connection_settings['card_name'];
				// disable all_fields here.
				$card_name = forminator_replace_variables( $card_name, $poll_id, $entry );
				$card_name = str_ireplace( '{poll_name}', forminator_get_name_from_model( $this->module ), $card_name );
				$card_name = str_ireplace( '{poll_answer}', $this->poll_answer_to_plain_text( $submitted_data ), $card_name );
				$card_name = str_ireplace( '{poll_result}', $this->poll_result_to_plain_text( $submitted_data ), $card_name );

				/**
				 * Filter Card Name to passed on to Create Trello Card API
				 *
				 * @since 1.6.1
				 *
				 * @param string                                $card_name
				 * @param int                                   $poll_id                Current Poll id.
				 * @param string                                $connection_id          ID of current connection.
				 * @param array                                 $submitted_data
				 * @param array                                 $connection_settings    current connection setting, contains options of like `name`, `list_id` etc.
				 * @param array                                 $current_entry_fields   default entry fields of poll.
				 * @param array                                 $poll_settings          Displayed Poll settings.
				 * @param Forminator_Trello_Poll_Settings $poll_settings_instance Trello Integration Poll Settings instance.
				 */
				$card_name    = apply_filters(
					'forminator_addon_trello_poll_card_name',
					$card_name,
					$poll_id,
					$connection_id,
					$submitted_data,
					$connection_settings,
					$current_entry_fields,
					$poll_settings,
					$poll_settings_instance
				);
				$args['name'] = $card_name;

			}

			if ( isset( $connection_settings['card_description'] ) ) {
				$card_description        = $connection_settings['card_description'];
				$poll_name_to_markdown   = $this->poll_name_to_markdown();
				$poll_answer_to_markdown = $this->poll_answer_to_markdown( $submitted_data );
				$poll_result_to_markdown = $this->poll_result_to_markdown( $submitted_data );
				$card_description        = str_ireplace( '{poll_name}', $poll_name_to_markdown, $card_description );
				$card_description        = str_ireplace( '{poll_answer}', $poll_answer_to_markdown, $card_description );
				$card_description        = str_ireplace( '{poll_result}', $poll_result_to_markdown, $card_description );
				$card_description        = forminator_replace_variables( $card_description, $poll_id, $entry );

				/**
				 * Filter Card Description to passed on to Create Trello Card API
				 *
				 * @since 1.6.1
				 *
				 * @param string                                $card_description
				 * @param int                                   $poll_id                Current Poll id.
				 * @param string                                $connection_id          ID of current connection.
				 * @param array                                 $submitted_data
				 * @param array                                 $connection_settings    current connection setting, contains options of like `name`, `list_id` etc.
				 * @param array                                 $current_entry_fields   default entry fields of poll.
				 * @param array                                 $poll_settings          Displayed Poll settings.
				 * @param Forminator_Trello_Poll_Settings $poll_settings_instance Trello Integration Poll Settings instance.
				 */
				$card_description = apply_filters(
					'forminator_addon_trello_poll_card_description',
					$card_description,
					$poll_id,
					$connection_id,
					$submitted_data,
					$connection_settings,
					$current_entry_fields,
					$poll_settings,
					$poll_settings_instance
				);
				$args['desc']     = $card_description;
			}

			if ( isset( $connection_settings['due_date'] ) && ! empty( $connection_settings['due_date'] ) ) {
				$due_date    = $connection_settings['due_date'];
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
			 * @since 1.6.1
			 *
			 * @param array                                 $args
			 * @param int                                   $poll_id                Current Poll id.
			 * @param string                                $connection_id          ID of current connection.
			 * @param array                                 $submitted_data
			 * @param array                                 $connection_settings    current connection setting, contains options of like `name`, `list_id` etc.
			 * @param array                                 $poll_settings          Displayed Poll settings.
			 * @param Forminator_Trello_Poll_Settings $poll_settings_instance Trello Integration Poll Settings instance.
			 */
			$args = apply_filters(
				'forminator_addon_trello_poll_create_card_args',
				$args,
				$poll_id,
				$connection_id,
				$submitted_data,
				$connection_settings,
				$poll_settings,
				$poll_settings_instance
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
	 * Special Replacer `{poll_name}` to markdown with Trello Flavour
	 *
	 * @since 1.6.2
	 * @return string
	 */
	private function poll_name_to_markdown() {

		$poll_name = forminator_get_name_from_model( $this->module );

		$markdown = '##' . $poll_name . "\n";

		/**
		 * Filter markdown for `poll_answer`
		 *
		 * @since 1.6.1
		 *
		 * @param string $markdown
		 * @param array  $submitted_data Submit data.
		 * @param array  $fields_labels  Poll Answers Labels.
		 */
		$markdown = apply_filters(
			'forminator_addon_trello_poll_name_markdown',
			$markdown
		);

		return $markdown;
	}

	/**
	 * Special Replacer `{poll_answer}` to markdown with Trello Flavour
	 *
	 * @since 1.6.1
	 *
	 * @param array $submitted_data Submitted Data.
	 *
	 * @return string
	 */
	private function poll_answer_to_markdown( $submitted_data ) {

		$answer_data   = isset( $submitted_data[ $this->module_id ] ) ? $submitted_data[ $this->module_id ] : '';
		$extra_field   = isset( $submitted_data[ $this->module_id . '-extra' ] ) ? $submitted_data[ $this->module_id . '-extra' ] : '';
		$fields_labels = $this->module->pluck_fields_array( 'title', 'element_id', '1' );

		$answer = isset( $fields_labels[ $answer_data ] ) ? $fields_labels[ $answer_data ] : $answer_data;
		$extra  = $extra_field;

		$markdown  = '##' . esc_html__( 'Poll Answer', 'forminator' ) . "\n";
		$markdown .= '**' . esc_html__( 'Vote', 'forminator' ) . ':** ' . $answer;
		if ( ! empty( $extra ) ) {
			$markdown .= "\n**" . esc_html__( 'Extra', 'forminator' ) . ':** ' . $extra;
		}

		/**
		 * Filter markdown for `poll_answer`
		 *
		 * @since 1.6.1
		 *
		 * @param string $markdown
		 * @param array  $submitted_data Submit data.
		 * @param array  $fields_labels  Poll Answers Labels.
		 */
		$markdown = apply_filters(
			'forminator_addon_trello_poll_answer_markdown',
			$markdown,
			$submitted_data,
			$fields_labels
		);

		return $markdown;
	}

	/**
	 * Special Replacer `{poll_result}` to markdown with Trello Flavour
	 *
	 * @since 1.6.1
	 *
	 * @param array $submitted_data Submitted data.
	 *
	 * @return string
	 */
	private function poll_result_to_markdown( $submitted_data ) {
		$fields_array = $this->module->get_fields_as_array();
		$map_entries  = Forminator_Form_Entry_Model::map_polls_entries( $this->module_id, $fields_array );

		// append new answer.
		if ( ! $this->module->is_prevent_store() ) {
			$answer_data = isset( $submitted_data[ $this->module_id ] ) ? $submitted_data[ $this->module_id ] : '';

			$entries = 0;
			// exists on map entries.
			if ( in_array( $answer_data, array_keys( $map_entries ), true ) ) {
				$entries = $map_entries[ $answer_data ];
			}

			++$entries;
			$map_entries[ $answer_data ] = $entries;

		}

		$fields = $this->module->get_fields();

		$markdown = '##' . esc_html__( 'Poll Results', 'forminator' );
		if ( ! is_null( $fields ) ) {
			foreach ( $fields as $field ) {
				$label = addslashes( $field->title );

				$slug    = isset( $field->slug ) ? $field->slug : sanitize_title( $label );
				$entries = 0;
				if ( in_array( $slug, array_keys( $map_entries ), true ) ) {
					$entries = $map_entries[ $slug ];
				}
				$markdown .= "\n**" . $label . ':** ' . $entries;
			}
		}

		/**
		 * Filter markdown for `poll_result`
		 *
		 * @since 1.6.1
		 *
		 * @param string $markdown
		 * @param array  $fields_array Answers list.
		 * @param array  $map_entries  Poll Entries.
		 */
		$markdown = apply_filters(
			'forminator_addon_trello_poll_result_markdown',
			$markdown,
			$fields_array,
			$map_entries
		);

		return $markdown;
	}

	/**
	 * Special Replacer `{poll_answer}` to markdown with Trello Flavour
	 *
	 * @since 1.6.2
	 *
	 * @param array $submitted_data Submitted data.
	 *
	 * @return string
	 */
	private function poll_answer_to_plain_text( $submitted_data ) {

		$answer_data   = isset( $submitted_data[ $this->module_id ] ) ? $submitted_data[ $this->module_id ] : '';
		$extra_field   = isset( $submitted_data[ $this->module_id . '-extra' ] ) ? $submitted_data[ $this->module_id . '-extra' ] : '';
		$fields_labels = $this->module->pluck_fields_array( 'title', 'element_id', '1' );

		$answer = isset( $fields_labels[ $answer_data ] ) ? $fields_labels[ $answer_data ] : $answer_data;
		$extra  = $extra_field;

		$plain_text = $answer;
		if ( ! empty( $extra ) ) {
			$plain_text .= ', ' . $extra;
		}

		/**
		 * Filter plain text for `poll_answer`
		 *
		 * @since 1.6.2
		 *
		 * @param string $plain_text
		 * @param array  $submitted_data Submit data.
		 * @param array  $fields_labels  Poll Answers Labels.
		 */
		$plain_text = apply_filters(
			'forminator_addon_trello_poll_answer_plain_text',
			$plain_text,
			$submitted_data,
			$fields_labels
		);

		return $plain_text;
	}

	/**
	 * Special Replacer `{poll_result}` to markdown with Trello Flavour
	 *
	 * @since 1.6.2
	 *
	 * @param array $submitted_data Submitted data.
	 *
	 * @return string
	 */
	private function poll_result_to_plain_text( $submitted_data ) {
		$fields_array = $this->module->get_fields_as_array();
		$map_entries  = Forminator_Form_Entry_Model::map_polls_entries( $this->module_id, $fields_array );

		// append new answer.
		if ( ! $this->module->is_prevent_store() ) {
			$answer_data = isset( $submitted_data[ $this->module_id ] ) ? $submitted_data[ $this->module_id ] : '';

			$entries = 0;
			// exists on map entries.
			if ( in_array( $answer_data, array_keys( $map_entries ), true ) ) {
				$entries = $map_entries[ $answer_data ];
			}

			++$entries;
			$map_entries[ $answer_data ] = $entries;

		}

		$fields = $this->module->get_fields();

		$plain_text = '';
		if ( ! is_null( $fields ) ) {
			foreach ( $fields as $field ) {
				$label = addslashes( $field->title );

				$slug    = isset( $field->slug ) ? $field->slug : sanitize_title( $label );
				$entries = 0;
				if ( in_array( $slug, array_keys( $map_entries ), true ) ) {
					$entries = $map_entries[ $slug ];
				}
				$plain_text .= '' . $label . ': ' . $entries . ' ';
			}
		}

		/**
		 * Filter markdown for `poll_result`
		 *
		 * @since 1.6.2
		 *
		 * @param string $markdown
		 * @param array  $fields_array Answers list.
		 * @param array  $map_entries  Poll Entries.
		 */
		$plain_text = apply_filters(
			'forminator_addon_trello_poll_result_plain_text',
			$plain_text,
			$fields_array,
			$map_entries
		);

		return $plain_text;
	}
}
