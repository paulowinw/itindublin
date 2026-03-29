<?php
/**
 * Forminator Hubspot form hooks
 *
 * @package Forminator
 */

/**
 * Class Forminator_Hubspot_Form_Hooks
 *
 * @since 1.0 HubSpot Integration
 */
class Forminator_Hubspot_Form_Hooks extends Forminator_Integration_Form_Hooks {

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
		$addon_setting_values = $this->settings_instance->migrate_list_id( $addon_setting_values );
		$data                 = array();

		foreach ( $addon_setting_values as $key => $addon_setting_value ) {
			// save it on entry field, with name `status-$MULTI_ID`, and value is the return result on sending data to hubspot.
			if ( $this->settings_instance->is_multi_id_completed( $key ) ) {
				// exec only on completed connection.
				$data[] = array(
					'name'  => 'status-' . $key,
					'value' => $this->get_status_on_contact_sync( $key, $submitted_data, $addon_setting_value, $current_entry_fields, $entry ),
				);
			}
		}

		return $data;
	}

	/**
	 * Get status on send message to HubSpot
	 *
	 * @since 1.0 HubSpot Integration
	 *
	 * @param string $connection_id Connection Id.
	 * @param array  $submitted_data Submitted data.
	 * @param array  $connection_settings Connection settings.
	 * @param array  $form_entry_fields Form entry fields.
	 * @param object $entry Entry instance.
	 *
	 * @return array `is_sent` true means its success send data to HubSpot, false otherwise.
	 * @throws Forminator_Integration_Exception Throws Integration Exception.
	 */
	private function get_status_on_contact_sync( $connection_id, $submitted_data, $connection_settings, $form_entry_fields, $entry ) {
		// initialize as null.
		$api = null;

		$form_id                = $this->module_id;
		$form_settings_instance = $this->settings_instance;
		$form_settings          = $this->settings_instance->get_form_settings();

		if ( empty( $connection_settings['name'] ) ) {
			$connection_settings['name'] = esc_html__( 'HubSpot', 'forminator' );
		}
		// check required fields.
		try {
			$api  = $this->addon->get_api();
			$args = array();

			$list_id = $connection_settings['list_id'];

			$deafult_fields    = $connection_settings['fields_map'];
			$custom_fields_map = array_filter( $connection_settings['custom_fields_map'] );

			$fields_map = array_merge( $deafult_fields, $custom_fields_map );

			$email_element_id = $connection_settings['fields_map']['email'];
			if ( ! isset( $submitted_data[ $email_element_id ] ) || empty( $submitted_data[ $email_element_id ] ) ) {
				throw new Forminator_Integration_Exception(
					sprintf(
					/* translators: 1: Email field ID */
						esc_html__( 'Email on element %1$s not found or not filled on submitted data.', 'forminator' ),
						$email_element_id
					)
				);
			}
			$email         = $submitted_data[ $email_element_id ];
			$email         = strtolower( trim( $email ) );
			$args['email'] = $email;

			// processed.
			unset( $fields_map['email'] );
			$common_fields = array(
				'firstname',
				'lastname',
				'jobtitle',
			);
			$extra_field   = array();
			if ( ! empty( $custom_fields_map ) ) {
				foreach ( $custom_fields_map as $custom => $custom_field ) {
					if ( ! empty( $custom ) ) {
						$extra_field[] = $custom;
					}
				}
			}

			$common_fields = array_merge( $common_fields, $extra_field );
			foreach ( $common_fields as $common_field ) {
				// not setup.
				if ( ! isset( $fields_map[ $common_field ] ) ) {
					continue;
				}

				if ( ! empty( $fields_map[ $common_field ] ) ) {
					$element_id = $fields_map[ $common_field ];
					if ( ! isset( $submitted_data[ $element_id ] ) ) {
						continue;
					}
					$element_value = $submitted_data[ $element_id ];
					if ( self::element_is_datepicker( $element_id ) ) {
						$hs_field_type = $api->get_property( 'fieldType', $common_field, $args );

						if ( 'date' === $hs_field_type && ! empty( $element_value ) ) {
							$element_value = self::get_date_in_ms( $element_id, $submitted_data[ $element_id ], $form_id );
						}
					} elseif ( self::element_is_multi_option( $element_id, $form_id ) ) {
						$hs_field_type = $api->get_property( 'fieldType', $common_field, $args );
						if ( 'checkbox' === $hs_field_type ) {
							// Handle multiple selectbox values.
							if ( strpos( $element_id, 'select-' ) !== false ) {
								$element_value = json_decode( $element_value );
								if ( is_array( $element_value ) ) {
									$element_value = implode( ', ', $element_value );
								}
							}
							// Remove semicolon from value to avoid conflict.
							$element_value = str_replace( ';', '', $element_value );
							// For checkbox, it should be ; separated.
							$element_value = str_replace( ', ', ';', $element_value );
						}
					}
					if ( ! is_null( $element_value ) ) {
						$args[ $common_field ] = $element_value;
					}
				}
				// processed.
				unset( $fields_map[ $common_field ] );
			}

			/**
			 * Filter arguments to passed on to Contact Sync HubSpot API
			 *
			 * @since 1.2
			 *
			 * @param array $args
			 * @param int $form_id Current Form id.
			 * @param string $connection_id ID of current connection.
			 * @param array $submitted_data
			 * @param array $connection_settings current connection setting, contains options of like `name`, `list_id` etc.
			 * @param array $form_settings Displayed Form settings.
			 * @param Forminator_Hubspot_Form_Settings $form_settings_instance HubSpot Integration Form Settings instance.
			 */
			$args       = apply_filters(
				'forminator_addon_hubspot_create_contact_args',
				$args,
				$form_id,
				$connection_id,
				$submitted_data,
				$connection_settings,
				$form_settings,
				$form_settings_instance
			);
			$contact_id = $api->add_update_contact( $args );
			// Add contact to contact list.
			$to_object_id = null;
			if ( ! empty( $contact_id ) && ! is_object( $contact_id ) && (int) $contact_id > 0 ) {
				$to_object_id = $contact_id;

				if ( ! empty( $list_id ) ) {
					$api->add_to_contact_list( $contact_id, $list_id );
				}
			}

			$create_ticket  = isset( $connection_settings['create_ticket'] ) ? $connection_settings['create_ticket'] : '';
			$from_object_id = null;
			if ( '1' === $create_ticket ) {
				$ticket['pipeline_id']        = $connection_settings['pipeline_id'];
				$ticket['status_id']          = $connection_settings['status_id'];
				$ticket_name                  = forminator_addon_replace_custom_vars( $connection_settings['ticket_name'], $submitted_data, $this->module, $form_entry_fields, false, $entry );
				$ticket['ticket_name']        = $ticket_name;
				$ticket_description           = forminator_addon_replace_custom_vars( $connection_settings['ticket_description'], $submitted_data, $this->module, $form_entry_fields, false, $entry );
				$ticket['ticket_description'] = $ticket_description;
				$ticket['supported_file']     = isset( $submitted_data[ $connection_settings['supported_file'] ] ) ? $submitted_data[ $connection_settings['supported_file'] ] : '';

				$object_id = $api->create_ticket( $ticket );

				if ( ! is_null( $to_object_id ) && ! is_object( $object_id ) && (int) $object_id > 0 ) {
					$from_object_id            = $object_id;
					$associate['fromObjectId'] = $from_object_id;
					$associate['toObjectId']   = $to_object_id;
					$api->ticket_associate_contact( $associate );
				}
			}

			forminator_addon_maybe_log( __METHOD__, 'Success Send Data' );

			$multi_global_ids = $this->addon->get_multi_global_ids();
			$name_suffix      = ! empty( $this->addon->multi_global_id )
					&& ! empty( $multi_global_ids[ $this->addon->multi_global_id ] )
					? ' - ' . $multi_global_ids[ $this->addon->multi_global_id ] : '';

			return array(
				'is_sent'         => true,
				'connection_name' => $connection_settings['name'] . $name_suffix,
				'description'     => esc_html__( 'Successfully send data to HubSpot', 'forminator' ),
				'data_sent'       => $api->get_last_data_sent(),
				'data_received'   => $api->get_last_data_received(),
				'url_request'     => $api->get_last_url_request(),
				'contact_id'      => $to_object_id,
				'ticket_id'       => $from_object_id,
			);

		} catch ( Forminator_Integration_Exception $e ) {

			forminator_addon_maybe_log( __METHOD__, 'Failed to Send to HubSpot' );

			$addon_entry_fields = array(
				'is_sent'         => false,
				'description'     => $e->getMessage(),
				'connection_name' => $connection_settings['name'],
				'data_sent'       => ( ( $api instanceof Forminator_Hubspot_Wp_Api ) ? $api->get_last_data_sent() : array() ),
				'data_received'   => ( ( $api instanceof Forminator_Hubspot_Wp_Api ) ? $api->get_last_data_received() : array() ),
				'url_request'     => ( ( $api instanceof Forminator_Hubspot_Wp_Api ) ? $api->get_last_url_request() : '' ),
				'contact_id'      => null,
				'ticket_id'       => null,
			);

			return $addon_entry_fields;
		}
	}

	/**
	 * It will delete sent chat
	 *
	 * @since 1.0 HubSpot Integration
	 *
	 * @param Forminator_Form_Entry_Model $entry_model Form Entry Model.
	 * @param  array                       $addon_meta_data Addon meta data.
	 *
	 * @return bool
	 */
	public function on_before_delete_entry( Forminator_Form_Entry_Model $entry_model, $addon_meta_data ) {
		// attach hook first.
		$form_id                = $this->module_id;
		$form_settings_instance = $this->settings_instance;

		/**
		 *
		 * Filter HubSpot integration metadata that previously saved on db to be processed
		 *
		 * @since 1.4
		 *
		 * @param array $addon_meta_data
		 * @param int $form_id current Form ID.
		 * @param Forminator_Form_Entry_Model $entry_model Forminator Entry Model.
		 * @param Forminator_Hubspot_Form_Settings $form_settings_instance HubSpot Integration Form Settings instance.
		 */
		$addon_meta_data = apply_filters(
			'forminator_addon_hubspot_metadata',
			$addon_meta_data,
			$form_id,
			$entry_model,
			$form_settings_instance
		);

		/**
		 * Fires when HubSpot connected form delete a submission
		 *
		 * @since 1.1
		 *
		 * @param int $form_id current Form ID.
		 * @param Forminator_Form_Entry_Model $entry_model Forminator Entry Model.
		 * @param array $addon_meta_data integration meta data.
		 * @param Forminator_Hubspot_Form_Settings $form_settings_instance HubSpot Integration Form Settings instance.
		 */
		do_action(
			'forminator_addon_hubspot_on_before_delete_submission',
			$form_id,
			$entry_model,
			$addon_meta_data,
			$form_settings_instance
		);
		if ( ! Forminator_Hubspot::is_enable_delete_member() ) {
			// its disabled, go for it!
			return true;
		}
		try {

			$contact_to_delete = array();
			$ticket_to_delete  = array();
			if ( is_array( $addon_meta_data ) ) {
				foreach ( $addon_meta_data as $addon_meta ) {

					if ( isset( $addon_meta['value'] ) && is_array( $addon_meta['value'] ) ) {
						$addon_meta_value = $addon_meta['value'];
						if ( isset( $addon_meta_value['is_sent'] ) && $addon_meta_value['is_sent'] ) {
							if ( isset( $addon_meta_value['contact_id'] ) && ! is_null( $addon_meta_value['contact_id'] ) ) {
								$contact_to_delete[] = $addon_meta_value['contact_id'];
							}
							if ( isset( $addon_meta_value['ticket_id'] ) && ! is_null( $addon_meta_value['ticket_id'] ) ) {
								$ticket_to_delete[] = $addon_meta_value['ticket_id'];
							}
						}
					}
				}
			}

			$contact_to_delete = apply_filters(
				'forminator_addon_hubspot_contact_to_delete',
				$contact_to_delete,
				$form_id,
				$addon_meta_data,
				$form_settings_instance
			);
			if ( ! empty( $contact_to_delete ) ) {
				$api = $this->addon->get_api();
				foreach ( $contact_to_delete as $contact ) {

					if ( ! empty( $contact ) ) {
						$api->delete_contact( $contact );
					}
				}
			}

			$ticket_to_delete = apply_filters(
				'forminator_addon_hubspot_ticket_to_delete',
				$ticket_to_delete,
				$form_id,
				$addon_meta_data,
				$form_settings_instance
			);
			if ( ! empty( $ticket_to_delete ) ) {
				$api = $this->addon->get_api();
				foreach ( $ticket_to_delete as $ticket ) {

					if ( ! empty( $ticket ) ) {
						$api->delete_ticket( $ticket );
					}
				}
			}

			return true;

		} catch ( Forminator_Integration_Exception $e ) {
			// use wp_error, for future usage it can be returned to page entries.
			$wp_error
				= new WP_Error( 'forminator_addon_hubspot_delete_contact', $e->getMessage() );
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
