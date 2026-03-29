<?php
/**
 * Forminator Addon Slack Form Hooks.
 *
 * @package Forminator
 */

/**
 * Class Forminator_Slack_Form_Hooks
 *
 * @since 1.0 Slack Integration
 */
class Forminator_Slack_Form_Hooks extends Forminator_Integration_Form_Hooks {

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
	 * @since 1.0 Slack Integration
	 *
	 * @param string $connection_id Connection Id.
	 * @param array  $submitted_data Submitted data.
	 * @param array  $connection_settings Connection settings.
	 * @param array  $form_entry_fields Form entry fields.
	 *
	 * @return array `is_sent` true means its success send data to Slack, false otherwise
	 * @throws Forminator_Integration_Exception Throws Integration Exception.
	 */
	private function get_status_on_send_message( $connection_id, $submitted_data, $connection_settings, $form_entry_fields ) {
		// initialize as null.
		$api = null;

		$form_id                = $this->module_id;
		$form_settings_instance = $this->settings_instance;
		$form_settings          = $this->settings_instance->get_form_settings();

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
			$text_message = forminator_addon_replace_custom_vars( $text_message, $submitted_data, $this->module, $form_entry_fields, false );

			$attachments = $this->get_form_fields_as_attachments( $submitted_data, $form_entry_fields );

			/**
			 * Filter `attachments` to passed onto API
			 *
			 * @since 1.4
			 *
			 * @param string                               $card_name
			 * @param int                                  $form_id                Current Form id.
			 * @param string                               $connection_id          ID of current connection.
			 * @param array                                $submitted_data
			 * @param array                                $connection_settings    current connection setting, contains options of like `name`, `target_id` etc.
			 * @param array                                $form_entry_fields      default entry fields of form.
			 * @param array                                $form_settings          Displayed Form settings.
			 * @param Forminator_Slack_Form_Settings $form_settings_instance Slack Integration Form Settings instance.
			 */
			$attachments = apply_filters(
				'forminator_addon_slack_message_attachments',
				$attachments,
				$form_id,
				$connection_id,
				$submitted_data,
				$connection_settings,
				$form_entry_fields,
				$form_settings,
				$form_settings_instance
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
			 * @param int                                  $form_id                Current Form id.
			 * @param string                               $connection_id          ID of current connection.
			 * @param array                                $submitted_data
			 * @param array                                $connection_settings    current connection setting, contains options of like `name`, `target_id` etc.
			 * @param array                                $form_entry_fields      default entry fields of form.
			 * @param array                                $form_settings          Displayed Form settings.
			 * @param Forminator_Slack_Form_Settings $form_settings_instance Slack Integration Form Settings instance.
			 */
			$args = apply_filters(
				'forminator_addon_slack_send_message_args',
				$args,
				$form_id,
				$connection_id,
				$submitted_data,
				$connection_settings,
				$form_entry_fields,
				$form_settings,
				$form_settings_instance
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
	 * Get All Form Fields as attachments
	 *
	 * @since 1.0 Slack Integration
	 *
	 * @param array $submitted_data Submitted data.
	 * @param array $form_entry_fields Form entry fields.
	 *
	 * @return array
	 */
	public function get_form_fields_as_attachments( $submitted_data, $form_entry_fields ) {
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
					$field_value                     = forminator_addon_replace_custom_vars( $field_value, $submitted_data, $this->module, $form_entry_fields, false );
					$all_fields_attachments_fields[] = array(
						'title' => $field_label,
						'value' => ( empty( $field_value ) ? '-' : $field_value ),
						'short' => false,
					);

					break;
				default:
					$field_value                     = '{' . $element_id . '}';
					$field_value                     = forminator_addon_replace_custom_vars( $field_value, $submitted_data, $this->module, $form_entry_fields, false );
					$all_fields_attachments_fields[] = array(
						'title' => $field_label,
						'value' => ( empty( $field_value ) ? '-' : $field_value ),
						'short' => false,
					);
					break;
			}

			if ( in_array( $field_type, $field_format, true ) ) {

				$field_value                     = '{' . $post_element_id . '}';
				$field_value                     = forminator_addon_replace_custom_vars( $field_value, $submitted_data, $this->module, $form_entry_fields, false );
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

	/**
	 * It will delete sent chat
	 *
	 * @since 1.0 Slack Integration
	 *
	 * @param Forminator_Form_Entry_Model $entry_model Form Entry Model.
	 * @param  array                       $addon_meta_data Addon Meta Data.
	 *
	 * @return bool
	 */
	public function on_before_delete_entry( Forminator_Form_Entry_Model $entry_model, $addon_meta_data ) {
		// attach hook first.
		$form_id                = $this->module_id;
		$form_settings_instance = $this->settings_instance;

		/**
		 *
		 * Filter Slack integration metadata that previously saved on db to be processed
		 *
		 * @since 1.4
		 *
		 * @param array                                $addon_meta_data
		 * @param int                                  $form_id                current Form ID.
		 * @param Forminator_Form_Entry_Model          $entry_model            Forminator Entry Model.
		 * @param Forminator_Slack_Form_Settings $form_settings_instance Slack Integration Form Settings instance.
		 */
		$addon_meta_data = apply_filters(
			'forminator_addon_slack_metadata',
			$addon_meta_data,
			$form_id,
			$entry_model,
			$form_settings_instance
		);

		/**
		 * Fires when Slack connected form delete a submission
		 *
		 * @since 1.1
		 *
		 * @param int                                  $form_id                current Form ID.
		 * @param Forminator_Form_Entry_Model          $entry_model            Forminator Entry Model.
		 * @param array                                $addon_meta_data        integration meta data.
		 * @param Forminator_Slack_Form_Settings $form_settings_instance Slack Integration Form Settings instance.
		 */
		do_action(
			'forminator_addon_slack_on_before_delete_submission',
			$form_id,
			$entry_model,
			$addon_meta_data,
			$form_settings_instance
		);

		if ( ! Forminator_Slack::enable_delete_chat() ) {
			// its disabled, go for it!
			return true;
		}

		try {
			if ( is_array( $addon_meta_data ) ) {
				$card_delete_mode = Forminator_Trello::get_card_delete_mode();

				foreach ( $addon_meta_data as $addon_meta_datum ) {

					// basic data validation.
					if ( ! isset( $addon_meta_datum['value'] ) || ! is_array( $addon_meta_datum['value'] ) ) {
						continue;
					}

					$addon_meta_datum_value = $addon_meta_datum['value'];
					if ( ! isset( $addon_meta_datum_value['is_sent'] ) || ! $addon_meta_datum_value['is_sent'] ) {
						continue;
					}

					if ( ! isset( $addon_meta_datum_value['ts'] ) || empty( $addon_meta_datum_value['ts'] ) ) {
						continue;
					}

					if ( ! isset( $addon_meta_datum_value['target_id'] ) || empty( $addon_meta_datum_value['target_id'] ) ) {
						continue;
					}

					$chat_ts    = $addon_meta_datum_value['ts'];
					$channel_id = $addon_meta_datum_value['target_id'];

					$api = $this->addon->get_api();
					$api->chat_delete( $channel_id, $chat_ts );

				}
			}

			// delete mode!
			return true;

		} catch ( Forminator_Integration_Exception $e ) {
			// handle all internal integration exceptions with `Forminator_Integration_Exception`.

			// use wp_error, for future usage it can be returned to page entries.
			$wp_error
				= new WP_Error( 'forminator_addon_slack_delete_chat', $e->getMessage() );
			// handle this in integration by self, since page entries cant handle error messages on delete yet.
			wp_die(
				esc_html( $wp_error->get_error_message() ),
				esc_html( $this->addon->get_title() ),
				array(
					'response'  => 200,
					'back_link' => true,
				)
			);

			return false;
		}
	}
}
