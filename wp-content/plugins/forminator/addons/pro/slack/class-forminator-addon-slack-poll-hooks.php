<?php
/**
 * Forminator Addon Slack Poll Hooks.
 *
 * @package Forminator
 */

/**
 * Class Forminator_Slack_Poll_Hooks
 *
 * @since 1.6.1
 */
class Forminator_Slack_Poll_Hooks extends Forminator_Integration_Poll_Hooks {

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
	 * @since 1.6.1
	 *
	 * @param string $connection_id Connection Id.
	 * @param array  $submitted_data Submitted data.
	 * @param array  $connection_settings Connection settings.
	 * @param array  $poll_entry_fields Poll entry fields.
	 *
	 * @return array `is_sent` true means its success send data to Slack, false otherwise
	 * @throws Forminator_Integration_Exception Throws Integration Exception.
	 */
	private function get_status_on_send_message( $connection_id, $submitted_data, $connection_settings, $poll_entry_fields ) {
		// initialize as null.
		$api = null;

		$poll_id                = $this->module_id;
		$poll_settings_instance = $this->settings_instance;
		$poll_settings          = $this->settings_instance->get_poll_settings();

		// check required fields.
		try {
			$api  = $this->addon->get_api();
			$args = array();

			if ( ! isset( $connection_settings['target_id'] ) ) {
				throw new Forminator_Integration_Exception( esc_html__( 'Target ID not properly set up.', 'forminator' ) );
			}

			if ( ! isset( $connection_settings['message'] ) ) {
				throw new Forminator_Integration_Exception( esc_html__( 'Message not properly set up.', 'forminator' ) );
			}
			$text_message = $connection_settings['message'];
			$text_message = forminator_replace_variables( $text_message );

			$text_message = str_ireplace( '{poll_name}', forminator_get_name_from_model( $this->module ), $text_message );

			$attachments = $this->get_poll_data_as_attachments( $submitted_data, $poll_entry_fields );

			/**
			 * Filter `attachments` to passed onto API
			 *
			 * @since 1.4
			 *
			 * @param string                               $card_name
			 * @param int                                  $poll_id                Current Poll id.
			 * @param string                               $connection_id          ID of current connection.
			 * @param array                                $submitted_data
			 * @param array                                $connection_settings    current connection setting, contains options of like `name`, `target_id` etc.
			 * @param array                                $poll_entry_fields      default entry fields of form.
			 * @param array                                $poll_settings          Displayed Poll settings.
			 * @param Forminator_Slack_Poll_Settings $poll_settings_instance Slack Integration Poll Settings instance.
			 */
			$attachments = apply_filters(
				'forminator_addon_slack_poll_message_attachments',
				$attachments,
				$poll_id,
				$connection_id,
				$submitted_data,
				$connection_settings,
				$poll_entry_fields,
				$poll_settings,
				$poll_settings_instance
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
			 * @param int                                  $poll_id                Current Poll id.
			 * @param string                               $connection_id          ID of current connection.
			 * @param array                                $submitted_data
			 * @param array                                $connection_settings    current connection setting, contains options of like `name`, `target_id` etc.
			 * @param array                                $poll_entry_fields      default entry fields of form.
			 * @param array                                $poll_settings          Displayed Poll settings.
			 * @param Forminator_Slack_Poll_Settings $poll_settings_instance Slack Integration Poll Settings instance.
			 */
			$args = apply_filters(
				'forminator_addon_slack_poll_send_message_args',
				$args,
				$poll_id,
				$connection_id,
				$submitted_data,
				$connection_settings,
				$poll_entry_fields,
				$poll_settings,
				$poll_settings_instance
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
	 * Get Poll Data as attachments
	 *
	 * @since 1.6.1
	 *
	 * @param array $submitted_data Submitted data.
	 * @param array $poll_entry_fields Poll entry fields.
	 *
	 * @return array
	 */
	public function get_poll_data_as_attachments( $submitted_data, $poll_entry_fields ) {
		$attachments = array();

		/**
		 * Attachment 1
		 * Answer          Extra
		 */
		$answer_data   = isset( $submitted_data[ $this->module_id ] ) ? $submitted_data[ $this->module_id ] : '';
		$extra_field   = isset( $submitted_data[ $this->module_id . '-extra' ] ) ? $submitted_data[ $this->module_id . '-extra' ] : '';
		$fields_labels = $this->module->pluck_fields_array( 'title', 'element_id', '1' );

		$answer = isset( $fields_labels[ $answer_data ] ) ? $fields_labels[ $answer_data ] : $answer_data;
		$extra  = $extra_field;

		$attachment_fields   = array();
		$attachment_fields[] = array(
			'title' => esc_html__( 'Vote', 'forminator' ),
			'value' => esc_html( $answer ),
			'short' => ! empty( $extra ),
		);

		if ( ! empty( $extra ) ) {
			$attachment_fields[] = array(
				'title' => esc_html__( 'Extra', 'forminator' ),
				'value' => esc_html( $extra ),
				'short' => true,
			);
		}

		$attachments[] = array(
			'title'  => esc_html__( 'Submitted Vote', 'forminator' ),
			'fields' => $attachment_fields,
		);

		/**
		 * Attachment 2
		 * Poll Result
		 */
		$attachment_fields = array();
		$fields_array      = $this->module->get_fields_as_array();
		$map_entries       = Forminator_Form_Entry_Model::map_polls_entries( $this->module_id, $fields_array );

		// append new answer.
		if ( ! $this->module->is_prevent_store() ) {
			$entries = 0;
			// exists on map entries.
			if ( in_array( $answer_data, array_keys( $map_entries ), true ) ) {
				$entries = $map_entries[ $answer_data ];
			}

			++$entries;
			$map_entries[ $answer_data ] = $entries;

		}

		$fields = $this->module->get_fields();
		if ( ! is_null( $fields ) ) {
			foreach ( $fields as $field ) {
				$label = addslashes( $field->title );

				$slug    = isset( $field->slug ) ? $field->slug : sanitize_title( $label );
				$entries = 0;
				if ( in_array( $slug, array_keys( $map_entries ), true ) ) {
					$entries = $map_entries[ $slug ];
				}

				$attachment_fields[] = array(
					'title' => $label,
					'value' => $entries,
					'short' => false,
				);
			}
		}

		$attachments[] = array(
			'title'  => esc_html__( 'Current Poll Result', 'forminator' ),
			'fields' => $attachment_fields,
		);

		return $attachments;
	}
}
