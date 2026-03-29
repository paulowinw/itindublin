<?php
/**
 * The Activecampaign Quiz Hooks.
 *
 * @package    Forminator
 */

/**
 * Class Forminator_Activecampaign_Quiz_Hooks
 *
 * @since 1.0 Activecampaign Integration
 */
class Forminator_Activecampaign_Quiz_Hooks extends Forminator_Integration_Quiz_Hooks {

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
			// save it on entry field, with name `status-$MULTI_ID`, and value is the return result on sending data to active campaign.
			if ( $this->settings_instance->is_multi_id_completed( $key ) ) {
				// exec only on completed connection.
				$data[] = array(
					'name'  => 'status-' . $key,
					'value' => $this->get_status_on_contact_sync( $key, $submitted_data, $addon_setting_value, $current_entry_fields ),
				);
			}
		}

		return $data;
	}

	/**
	 * Get status on contact sync to ActiveCampaign
	 *
	 * @since 1.0 Activecampaign Integration
	 * @since 1.7 Add $form_entry_fields
	 *
	 * @param string $connection_id ID of current connection.
	 * @param array  $submitted_data Submitted data.
	 * @param array  $connection_settings Connection settings.
	 * @param array  $form_entry_fields Form Entry fields.
	 *
	 * @return array `is_sent` true means its success send data to ActiveCampaign, false otherwise.
	 *
	 * @throws Forminator_Integration_Exception Exception.
	 */
	private function get_status_on_contact_sync( $connection_id, $submitted_data, $connection_settings, $form_entry_fields ) {
		$quiz_submitted_data = get_quiz_submitted_data( $this->module, $submitted_data, $form_entry_fields );
		$quiz_settings       = $this->settings_instance->get_quiz_settings();
		$addons_fields       = $this->settings_instance->get_form_fields();
		$submitted_data      = get_addons_lead_form_entry_data( $quiz_settings, $submitted_data, $addons_fields );
		$submitted_data      = array_merge( $submitted_data, $quiz_submitted_data );
		// initialize as null.
		$ac_api = null;

		$quiz_id                = $this->module_id;
		$quiz_settings_instance = $this->settings_instance;

		// check required fields.
		try {
			$ac_api = $this->addon->get_api();
			$args   = array();

			if ( ! isset( $connection_settings['list_id'] ) ) {
				throw new Forminator_Integration_Exception( esc_html__( 'List ID not properly set up.', 'forminator' ) );
			}

			$args[ 'p[' . $connection_settings['list_id'] . ']' ] = $connection_settings['list_id'];
			// subscribed.
			$args[ 'status[' . $connection_settings['list_id'] . ']' ] = '1';

			$fields_map = $connection_settings['fields_map'];

			$email_element_id = $connection_settings['fields_map']['email'];
			if ( ! isset( $submitted_data[ $email_element_id ] ) || empty( $submitted_data[ $email_element_id ] ) ) {
				throw new Forminator_Integration_Exception( esc_html__( 'Email on element not found or not filled on submitted data.', 'forminator' ) );
			}
			$email         = $submitted_data[ $email_element_id ];
			$email         = strtolower( trim( $email ) );
			$args['email'] = $email;

			// processed.
			unset( $fields_map['email'] );

			$common_fields = array(
				'first_name',
				'last_name',
				'phone',
				'orgname',
			);

			foreach ( $common_fields as $common_field ) {
				// not setup.
				if ( ! isset( $fields_map[ $common_field ] ) ) {
					continue;
				}

				if ( ! empty( $fields_map[ $common_field ] ) ) {
					$element_id = $fields_map[ $common_field ];

					if ( isset( $submitted_data[ $element_id ] ) && ! empty( $submitted_data[ $element_id ] ) ) {
						$element_value = $submitted_data[ $element_id ];
						if ( is_array( $element_value ) ) {
							$element_value = implode( ',', $element_value );
						}
					}
					if ( isset( $element_value ) ) {
						$args[ $common_field ] = $element_value;
						unset( $element_value ); // unset for next loop.
					}
				}
				// processed.
				unset( $fields_map[ $common_field ] );
			}

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
						$args[ 'field[' . $field_id . ',0]' ] = $element_value;
						unset( $element_value ); // unset for next loop.
					}
				}
			}

			// process tags.
			if ( isset( $connection_settings['tags'] ) && is_array( $connection_settings['tags'] ) ) {
				$tags = array();
				foreach ( $connection_settings['tags'] as $tag ) {
					if ( stripos( $tag, '{' ) === 0
						&& stripos( $tag, '}' ) === ( strlen( $tag ) - 1 )
					) {
						// translate to value.
						$element_id = str_ireplace( '{', '', $tag );
						$element_id = str_ireplace( '}', '', $element_id );
						if ( isset( $submitted_data[ $element_id ] ) && ! empty( $submitted_data[ $element_id ] ) ) {
							$element_value = $submitted_data[ $element_id ];
							if ( is_array( $element_value ) ) {
								$element_value = implode( ',', $element_value );
							}
						}

						if ( isset( $element_value ) ) {
							$tags[] = $element_value;
							unset( $element_value ); // unset for next loop.
						}
					} else {
						$tags[] = $tag;
					}
				}
				if ( ! empty( $tags ) ) {
					$tags         = implode( ',', $tags );
					$args['tags'] = $tags;
				}
			}

			if ( isset( $connection_settings['double_opt_form_id'] ) && ! empty( $connection_settings['double_opt_form_id'] ) ) {
				$args['form'] = $connection_settings['double_opt_form_id'];
			}

			if ( isset( $connection_settings['instantresponders'] ) ) {
				$instant_responders = filter_var( $connection_settings['instantresponders'], FILTER_VALIDATE_BOOLEAN );
				if ( $instant_responders ) {
					$args[ 'instantresponders[' . $connection_settings['list_id'] . ']' ] = '1';
				}
			}

			if ( isset( $connection_settings['lastmessage'] ) ) {
				$last_message = filter_var( $connection_settings['lastmessage'], FILTER_VALIDATE_BOOLEAN );
				if ( $last_message ) {
					$args[ 'lastmessage[' . $connection_settings['list_id'] . ']' ] = '1';
				}
			}

			/**
			 * Filter arguments to passed on to Contact Sync Active Campaign API
			 *
			 * @since 1.2
			 *
			 * @param array                                         $args
			 * @param int                                           $quiz_id                Current Quiz id.
			 * @param string                                        $connection_id          ID of current connection.
			 * @param array                                         $submitted_data
			 * @param array                                         $connection_settings    current connection setting, contains options of like `name`, `list_id` etc.
			 * @param Forminator_Activecampaign_Quiz_Settings $quiz_settings_instance ActiveCampaign Integration Quiz Settings instance.
			 */
			$args = apply_filters(
				'forminator_addon_activecampaign_contact_sync_args',
				$args,
				$quiz_id,
				$connection_id,
				$submitted_data,
				$connection_settings,
				$quiz_settings_instance
			);

			$ac_api->contact_sync( $args );

			forminator_addon_maybe_log( __METHOD__, 'Success Send Data' );

			return array(
				'is_sent'         => true,
				'connection_name' => $connection_settings['name'],
				'description'     => esc_html__( 'Successfully send data to ActiveCampaign', 'forminator' ),
				'data_sent'       => $ac_api->get_last_data_sent(),
				'data_received'   => $ac_api->get_last_data_received(),
				'url_request'     => $ac_api->get_last_url_request(),
			);

		} catch ( Forminator_Integration_Exception $e ) {
			forminator_addon_maybe_log( __METHOD__, 'Failed to Send to ActiveCampaign' );

			return array(
				'is_sent'         => false,
				'description'     => $e->getMessage(),
				'connection_name' => $connection_settings['name'],
				'data_sent'       => ( ( $ac_api instanceof Forminator_Activecampaign_Wp_Api ) ? $ac_api->get_last_data_sent() : array() ),
				'data_received'   => ( ( $ac_api instanceof Forminator_Activecampaign_Wp_Api ) ? $ac_api->get_last_data_received() : array() ),
				'url_request'     => ( ( $ac_api instanceof Forminator_Activecampaign_Wp_Api ) ? $ac_api->get_last_url_request() : '' ),
			);
		}
	}

	/**
	 * It will delete contact on ActiveCampaign list
	 *
	 * @since 1.0 ActiveCampaign Integration
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
		 * Filter ActiveCampaign integration metadata that previously saved on db to be processed
		 *
		 * @since 1.1
		 *
		 * @param array                                         $addon_meta_data
		 * @param int                                           $quiz_id                current Quiz ID.
		 * @param Forminator_Form_Entry_Model                   $entry_model            Forminator Entry Model.
		 * @param Forminator_Activecampaign_Quiz_Settings $quiz_settings_instance Activecampaign Quiz Settings instance.
		 */
		$addon_meta_data = apply_filters(
			'forminator_addon_activecampaign_metadata',
			$addon_meta_data,
			$quiz_id,
			$entry_model,
			$quiz_settings_instance
		);

		/**
		 * Fires when Activecampaign connected quiz delete a submission
		 *
		 * @since 1.1
		 *
		 * @param int                                           $quiz_id                current Quiz ID.
		 * @param Forminator_Form_Entry_Model                   $entry_model            Forminator Entry Model.
		 * @param array                                         $addon_meta_data        integration meta data.
		 * @param Forminator_Activecampaign_Quiz_Settings $quiz_settings_instance Activecampaign Quiz Settings instance.
		 */
		do_action(
			'forminator_addon_activecampaign_on_before_delete_submission',
			$quiz_id,
			$entry_model,
			$addon_meta_data,
			$quiz_settings_instance
		);

		if ( ! Forminator_Activecampaign::is_enable_delete_contact() ) {
			// its disabled, go for it!
			return true;
		}
		$ac_api = null;
		try {
			$subscriber_ids_to_delete = array();

			if ( is_array( $addon_meta_data ) ) {
				foreach ( $addon_meta_data as $addon_meta_datum ) {

					/** Data received reference
					 * data_received: {
					 *      subscriber_id: 1,
					 *      sendlast_should: 0,
					 *      sendlast_did: 0,
					 *      result_code: 1,
					 *      result_message: Contact added,
					 *      result_output: json
					 * }
					 */

					if ( isset( $addon_meta_datum['value'] ) && is_array( $addon_meta_datum['value'] ) ) {
						$addon_meta_datum_value = $addon_meta_datum['value'];
						if ( isset( $addon_meta_datum_value['is_sent'] ) && $addon_meta_datum_value['is_sent'] ) {
							if ( isset( $addon_meta_datum_value['data_received'] ) && is_object( $addon_meta_datum_value['data_received'] ) ) {
								$addon_meta_datum_received = $addon_meta_datum_value['data_received'];
								if ( isset( $addon_meta_datum_received->subscriber_id ) && ! empty( $addon_meta_datum_received->subscriber_id ) ) {
									$subscriber_ids_to_delete [] = $addon_meta_datum_received->subscriber_id;
								}
							}
						}
					}
				}
			}

			/**
			 * Filter subscriber ids to delete
			 *
			 * @since 1.2
			 *
			 * @param array                                         $subscriber_ids_to_delete
			 * @param int                                           $quiz_id                current Quiz ID.
			 * @param array                                         $addon_meta_data        integration meta data.
			 * @param Forminator_Activecampaign_Quiz_Settings $quiz_settings_instance Activecampaign Quiz Settings instance.
			 */
			$subscriber_ids_to_delete = apply_filters(
				'forminator_addon_activecampaign_subscriber_ids_to_delete',
				$subscriber_ids_to_delete,
				$quiz_id,
				$addon_meta_data,
				$quiz_settings_instance
			);

			if ( ! empty( $subscriber_ids_to_delete ) ) {
				$ac_api = $this->addon->get_api();
				foreach ( $subscriber_ids_to_delete as $subscriber_id_to_delete ) {
					$ac_api->contact_delete(
						array(
							'id' => $subscriber_id_to_delete,
						)
					);
				}
			}

			return true;

		} catch ( Forminator_Integration_Exception $e ) {
			// handle all internal integration exceptions with `Forminator_Integration_Exception`.

			// use wp_error, for future usage it can be returned to page entries.
			$wp_error = new WP_Error( 'forminator_addon_activecampaign_delete_contact', $e->getMessage() );
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
