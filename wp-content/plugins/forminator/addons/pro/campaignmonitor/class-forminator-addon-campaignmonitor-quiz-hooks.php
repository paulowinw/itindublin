<?php
/**
 * The Forminator Campaign Monitor Quiz Hooks.
 *
 * @package Forminator
 */

/**
 * Class Forminator_Campaignmonitor_Quiz_Hooks
 *
 * @since 1.0 Campaignmonitor Integration
 */
class Forminator_Campaignmonitor_Quiz_Hooks extends Forminator_Integration_Quiz_Hooks {

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
			// save it on entry field, with name `status-$MULTI_ID`, and value is the return result on sending data to campaign monitor.
			if ( $this->settings_instance->is_multi_id_completed( $key ) ) {
				// exec only on completed connection.
				$data[] = array(
					'name'  => 'status-' . $key,
					'value' => $this->get_status_on_add_subscriber( $key, $submitted_data, $addon_setting_value, $current_entry_fields ),
				);
			}
		}

		return $data;
	}

	/**
	 * Get status on add subscriber to Campaign Monitor
	 *
	 * @since 1.0 Campaign Monitor Integration
	 * @since 1.7 Add $form_entry_fields args
	 *
	 * @param string $connection_id Connection Id.
	 * @param array  $submitted_data Submitted data.
	 * @param array  $connection_settings Connection settings.
	 * @param array  $current_entry_fields Form entry fields.
	 *
	 * @return array `is_sent` true means its success send data to ampaign Monitor, false otherwise.
	 * @throws Forminator_Integration_Exception Throws Integration Exception.
	 */
	private function get_status_on_add_subscriber( $connection_id, $submitted_data, $connection_settings, $current_entry_fields ) {
		$quiz_submitted_data = get_quiz_submitted_data( $this->module, $submitted_data, $current_entry_fields );
		$quiz_settings       = $this->settings_instance->get_quiz_settings();
		$addons_fields       = $this->settings_instance->get_form_fields();
		$submitted_data      = get_addons_lead_form_entry_data( $quiz_settings, $submitted_data, $addons_fields );
		$submitted_data      = array_merge( $submitted_data, $quiz_submitted_data );
		// initialize as null.
		$api = null;

		$quiz_id                = $this->module_id;
		$quiz_settings_instance = $this->settings_instance;

		// check required fields.
		try {
			$api  = $this->addon->get_api();
			$args = array();

			if ( ! isset( $connection_settings['list_id'] ) ) {
				throw new Forminator_Integration_Exception( esc_html__( 'List ID not properly set up.', 'forminator' ) );
			}

			$list_id = $connection_settings['list_id'];

			$fields_map = $connection_settings['fields_map'];

			$email_element_id = $connection_settings['fields_map']['default_field_email'];
			if ( ! isset( $submitted_data[ $email_element_id ] ) || empty( $submitted_data[ $email_element_id ] ) ) {
				throw new Forminator_Integration_Exception(
					sprintf(
					/* translators: 1: Email field ID */
						esc_html__( 'Email Address on element %1$s not found or not filled on submitted data.', 'forminator' ),
						$email_element_id
					)
				);
			}
			$email = $submitted_data[ $email_element_id ];
			$email = strtolower( trim( $email ) );

			// processed.
			unset( $fields_map['default_field_email'] );

			$name_element_id = $connection_settings['fields_map']['default_field_name'];
			if ( ! isset( $submitted_data[ $name_element_id ] ) || empty( $submitted_data[ $name_element_id ] ) ) {
				throw new Forminator_Integration_Exception(
					sprintf(
					/* translators: 1: Name field ID */
						esc_html__( 'Name on element %1$s not found or not filled on submitted data.', 'forminator' ),
						$name_element_id
					)
				);
			}

			if ( isset( $name ) ) {
				$args['Name'] = $name;
			} else {
				$args['Name'] = $submitted_data[ $name_element_id ];
			}

			// processed.
			unset( $fields_map['default_field_name'] );

			$custom_fields = array();
			// process rest extra fields if available.
			foreach ( $fields_map as $field_id => $element_id ) {
				if ( ! empty( $element_id ) ) {
					if ( isset( $submitted_data[ $element_id ] ) && ( ! empty( $submitted_data[ $element_id ] ) || 0 === (int) $submitted_data[ $element_id ] ) ) {
						$element_value = $submitted_data[ $element_id ];
						if ( is_array( $element_value ) ) {
							$element_value = implode( ',', $element_value );
						}
					}

					if ( isset( $element_value ) ) {
						$custom_fields[] = array(
							'Key'   => $field_id,
							'Value' => $element_value,
						);
						unset( $element_value ); // unset for next loop.
					}
				}
			}
			$args['CustomFields'] = $custom_fields;

			if ( isset( $connection_settings['resubscribe'] ) ) {
				$resubscribe         = filter_var( $connection_settings['resubscribe'], FILTER_VALIDATE_BOOLEAN );
				$args['Resubscribe'] = $resubscribe;
			}

			if ( isset( $connection_settings['restart_subscription_based_autoresponders'] ) ) {
				$restart_subscription_based_autoresponders      = filter_var( $connection_settings['restart_subscription_based_autoresponders'], FILTER_VALIDATE_BOOLEAN );
				$args['RestartSubscriptionBasedAutoresponders'] = $restart_subscription_based_autoresponders;
			}

			if ( isset( $connection_settings['consent_to_track'] ) ) {
				$consent_to_track       = $connection_settings['consent_to_track'];
				$args['ConsentToTrack'] = $consent_to_track;
			}

			/**
			 * Filter arguments to passed on to Add Subscriber Campaign Monitor API
			 *
			 * @since 1.3
			 *
			 * @param array                                          $args
			 * @param int                                            $quiz_id                Current Quiz id.
			 * @param string                                         $connection_id          ID of current connection.
			 * @param array                                          $submitted_data
			 * @param array                                          $connection_settings    current connection setting, contains options of like `name`, `list_id` etc.
			 * @param Forminator_Campaignmonitor_Quiz_Settings $quiz_settings_instance Campaign Monitor Integration Quiz Settings instance.
			 */
			$args = apply_filters(
				'forminator_addon_campaignmonitor_add_subscriber_args',
				$args,
				$quiz_id,
				$connection_id,
				$submitted_data,
				$connection_settings,
				$quiz_settings_instance
			);

			$api->add_subscriber( $list_id, $email, $args );

			forminator_addon_maybe_log( __METHOD__, 'Success Send Data' );
			forminator_addon_maybe_log( __METHOD__, $api->get_last_data_received() );

			return array(
				'is_sent'          => true,
				'connection_name'  => $connection_settings['name'],
				'description'      => esc_html__( 'Successfully send data to Campaign Monitor', 'forminator' ),
				'data_sent'        => $api->get_last_data_sent(),
				'data_received'    => $api->get_last_data_received(),
				'url_request'      => $api->get_last_url_request(),
				'subscriber_email' => $api->get_last_data_received(), // for delete reference.
				'list_id'          => $list_id, // for delete reference.
			);

		} catch ( Forminator_Integration_Exception $e ) {
			forminator_addon_maybe_log( __METHOD__, 'Failed to Send to Campaign Monitor' );

			return array(
				'is_sent'         => false,
				'description'     => $e->getMessage(),
				'connection_name' => $connection_settings['name'],
				'data_sent'       => ( ( $api instanceof Forminator_Campaignmonitor_Wp_Api ) ? $api->get_last_data_sent() : array() ),
				'data_received'   => ( ( $api instanceof Forminator_Campaignmonitor_Wp_Api ) ? $api->get_last_data_received() : array() ),
				'url_request'     => ( ( $api instanceof Forminator_Campaignmonitor_Wp_Api ) ? $api->get_last_url_request() : '' ),
			);
		}
	}

	/**
	 * It will delete subscriber on Campaign Monitor from list
	 *
	 * @since 1.0 Campaign Monitor Integration
	 *
	 * @param Forminator_Form_Entry_Model $entry_model Form entry model.
	 * @param  array                       $addon_meta_data Addon meta data.
	 *
	 * @return bool
	 */
	public function on_before_delete_entry( Forminator_Form_Entry_Model $entry_model, $addon_meta_data ) {
		// attach hook first.
		$quiz_id                = $this->module_id;
		$quiz_settings_instance = $this->settings_instance;

		/**
		 *
		 * Filter Campaign Monitor integration metadata that previously saved on db to be processed
		 *
		 * @since 1.1
		 *
		 * @param array                                          $addon_meta_data
		 * @param int                                            $quiz_id                current Quiz ID.
		 * @param Forminator_Form_Entry_Model                    $entry_model            Forminator Entry Model.
		 * @param Forminator_Campaignmonitor_Quiz_Settings $quiz_settings_instance Campaign Monitor Integration Quiz Settings instance.
		 */
		$addon_meta_data = apply_filters(
			'forminator_addon_campaignmonitor_metadata',
			$addon_meta_data,
			$quiz_id,
			$entry_model,
			$quiz_settings_instance
		);

		/**
		 * Fires when Campaign Monitor connected quiz delete a submission
		 *
		 * @since 1.1
		 *
		 * @param int                                            $quiz_id                current Quiz ID.
		 * @param Forminator_Form_Entry_Model                    $entry_model            Forminator Entry Model.
		 * @param array                                          $addon_meta_data        integration meta data.
		 * @param Forminator_Campaignmonitor_Quiz_Settings $quiz_settings_instance Campaign Monitor Integration Quiz Settings instance.
		 */
		do_action(
			'forminator_addon_campaignmonitor_on_before_delete_submission',
			$quiz_id,
			$entry_model,
			$addon_meta_data,
			$quiz_settings_instance
		);

		if ( ! Forminator_Campaignmonitor::is_enable_delete_subscriber() ) {
			// its disabled, go for it!
			return true;
		}
		$api = null;
		try {
			$subscribers_to_delete = array();

			if ( is_array( $addon_meta_data ) ) {
				foreach ( $addon_meta_data as $addon_meta_datum ) {

					if ( isset( $addon_meta_datum['value'] ) && is_array( $addon_meta_datum['value'] ) ) {
						$addon_meta_datum_value = $addon_meta_datum['value'];
						if ( isset( $addon_meta_datum_value['is_sent'] ) && $addon_meta_datum_value['is_sent'] ) {
							if ( isset( $addon_meta_datum_value['list_id'] ) && ! empty( $addon_meta_datum_value['list_id'] )
								&& isset( $addon_meta_datum_value['subscriber_email'] )
								&& ! empty( $addon_meta_datum_value['subscriber_email'] ) ) {
								$subscribers_to_delete[] = array(
									'list_id' => $addon_meta_datum_value['list_id'],
									'email'   => $addon_meta_datum_value['subscriber_email'],
								);
							}
						}
					}
				}
			}

			/**
			 * Filter subscribers to delete
			 *
			 * @since 1.3
			 *
			 * @param array                                          $subscriber_ids_to_delete
			 * @param int                                            $quiz_id                current Quiz ID.
			 * @param array                                          $addon_meta_data        integration meta data.
			 * @param Forminator_Campaignmonitor_Quiz_Settings $quiz_settings_instance Campaign Monitor Integration Quiz Settings instance.
			 */
			$subscribers_to_delete = apply_filters(
				'forminator_addon_campaignmonitor_subscribers_to_delete',
				$subscribers_to_delete,
				$quiz_id,
				$addon_meta_data,
				$quiz_settings_instance
			);

			if ( ! empty( $subscribers_to_delete ) ) {
				$api = $this->addon->get_api();
				foreach ( $subscribers_to_delete as $subscriber ) {

					if ( isset( $subscriber['list_id'] ) && isset( $subscriber['email'] ) ) {
						$api->delete_subscriber( $subscriber['list_id'], $subscriber['email'] );
					}
				}
			}

			return true;

		} catch ( Forminator_Integration_Exception $e ) {
			// handle all internal integration exceptions with `Forminator_Integration_Exception`.

			// use wp_error, for future usage it can be returned to page entries.
			$wp_error = new WP_Error( 'forminator_addon_campaignmonitor_delete_subscriber', $e->getMessage() );
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
