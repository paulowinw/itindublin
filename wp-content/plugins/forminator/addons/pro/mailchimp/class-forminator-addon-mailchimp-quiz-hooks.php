<?php
/**
 * Addon Mailchimp quiz hook.
 *
 * @package Forminator
 */

/**
 * Class Forminator_Mailchimp_Quiz_Hooks
 *
 * Hooks that used by Mailchimp Integration defined here
 *
 * @since 1.0 Mailchimp Integration
 */
class Forminator_Mailchimp_Quiz_Hooks extends Forminator_Integration_Quiz_Hooks {

	/**
	 * Flag of gpdr field checked on submit
	 *
	 * @since 1.0 Mailchimp Integration
	 *
	 * @var bool
	 */
	private $gdpr_is_checked = true;

	/**
	 * Return custom entry fields
	 *
	 * @param array $submitted_data Submitted data.
	 * @param array $current_entry_fields Current entry fields.
	 * @return array
	 * @throws Forminator_Integration_Exception Throws Integration Exception.
	 */
	protected function custom_entry_fields( $submitted_data, $current_entry_fields ): array {
		$quiz_id                = $this->module_id;
		$quiz_settings_instance = $this->settings_instance;
		$gdpr                   = false;

		$quiz_submitted_data = get_quiz_submitted_data( $this->module, $submitted_data, $current_entry_fields );

		$addon_setting_values = $this->settings_instance->get_settings_values();
		$quiz_settings        = $this->settings_instance->get_quiz_settings();
		$addons_fields        = $this->settings_instance->get_form_fields();
		$submitted_data       = get_addons_lead_form_entry_data( $quiz_settings, $submitted_data, $addons_fields );

		// Check if there is a date field-type then modify it to a format that mailchimp accepts.
		foreach ( $submitted_data as $field => $value ) {
			// Also Check the date field doesn't include the '-year', '-month' or '-day'.
			if (
				false !== stripos( $field, 'date-' ) &&
				false === stripos( $field, '-year' ) &&
				false === stripos( $field, '-month' ) &&
				false === stripos( $field, '-day' ) &&
				! empty( $value )
				) {
				$date_format              = Forminator_API::get_form_field( $quiz_id, $field, false )->date_format;
				$normalized_format        = new Forminator_Date();
				$normalized_format        = $normalized_format->normalize_date_format( $date_format );
				$mailchimp_format         = DateTime::createFromFormat( $normalized_format, $value );
				$mailchimp_formatted      = $mailchimp_format->format( 'Y-m-d' );
				$submitted_data[ $field ] = $mailchimp_formatted;
			}
			if (
				! empty( $value ) &&
				( false !== strpos( $field, 'gdprcheckbox' ) || false !== strpos( $field, 'consent' ) )
			) {
				$gdpr = true;
			}
		}

		$submitted_data = array_merge( $submitted_data, $quiz_submitted_data );

		// initialize as null.
		$mailchimp_api = null;

		$settings_values = $this->addon->get_settings_values();
		$identifier      = $settings_values['identifier'] ?? '';
		$entry_name      = 'status';
		if ( ! empty( $this->addon->multi_global_id ) ) {
			$entry_name .= "-{$this->addon->multi_global_id}";
		}

		// check required fields.
		try {
			$mailchimp_api = $this->addon->get_api();

			if ( Forminator_Mailchimp::is_enable_gdpr() ) {
				// GDPR.
				if ( isset( $addon_setting_values['enable_gdpr'] ) && $addon_setting_values['enable_gdpr'] ) {
					if ( isset( $addon_setting_values['gdpr_text'] ) && ! empty( $addon_setting_values['gdpr_text'] ) ) {
						if ( ! $this->gdpr_is_checked ) {
							// GDPR not checked, add error.
							throw new Forminator_Integration_Exception(
								esc_html__(
									'Forminator Mailchimp integration did not send subscriber to Mailchimp as GDPR field was not checked on input',
									'forminator'
								)
							);

						}
					}
				}
			}

			// EMAIL : super required**.
			if ( ! isset( $addon_setting_values['fields_map']['EMAIL'] ) ) {
				throw new Forminator_Integration_Exception(/* translators: ... */
					sprintf( /* translators: 1: EMAIL */
						esc_html__( 'Required Field %1$s not mapped yet to Forminator Form Field, Please check your Mailchimp Configuration on Form Settings', 'forminator' ),
						'EMAIL'
					)
				);
			}

			if ( ! isset( $submitted_data[ $addon_setting_values['fields_map']['EMAIL'] ] ) || empty( $submitted_data[ $addon_setting_values['fields_map']['EMAIL'] ] ) ) {
				throw new Forminator_Integration_Exception(/* translators: ... */
					sprintf(
					/* translators: 1: EMAIL */
						esc_html__( 'Required Field %1$s is not filled by user', 'forminator' ),
						'EMAIL'
					)
				);
			}

			$mailchimp_fields_list_request = $this->addon->get_api()->get_list_merge_fields( $addon_setting_values['mail_list_id'], array() );
			forminator_addon_maybe_log( __METHOD__, $mailchimp_fields_list_request );
			$mailchimp_required_fields = array();
			$mailchimp_fields_list     = array();
			if ( isset( $mailchimp_fields_list_request->merge_fields ) && is_array( $mailchimp_fields_list_request->merge_fields ) && ! empty( $mailchimp_fields_list_request->merge_fields ) ) {
				$mailchimp_fields_list = $mailchimp_fields_list_request->merge_fields;
			}

			foreach ( $mailchimp_fields_list as $item ) {
				if ( $item->required ) {
					$mailchimp_required_fields[] = $item;
				}
			}

			// check required fields fulfilled.
			foreach ( $mailchimp_required_fields as $mailchimp_required_field ) {
				if ( 'address' === $mailchimp_required_field->type ) {
					$address_fields = $this->settings_instance->mail_address_fields();
					foreach ( $address_fields as $addr => $address ) {
						if ( ! isset( $addon_setting_values['fields_map'][ $mailchimp_required_field->tag ][ $addr ] ) ) {
							throw new Forminator_Integration_Exception(
								sprintf(
								/* translators: 1: Required field name */
									esc_html__( 'Required Field %1$s not mapped yet to Forminator Form Field, Please check your Mailchimp Configuration on Form Settings', 'forminator' ),
									esc_html( $mailchimp_required_field->name )
								)
							);
						}

						if ( ! isset( $submitted_data[ $addon_setting_values['fields_map'][ $mailchimp_required_field->tag ][ $addr ] ] )
							|| empty( $submitted_data[ $addon_setting_values['fields_map'][ $mailchimp_required_field->tag ][ $addr ] ] ) ) {
							throw new Forminator_Integration_Exception(
								sprintf(
								/* translators: 1: Required field name */
									esc_html__( 'Required Field %1$s not filled by user', 'forminator' ),
									esc_html( $mailchimp_required_field->name )
								)
							);
						}
					}
				} else {

					if ( ! isset( $addon_setting_values['fields_map'][ $mailchimp_required_field->tag ] ) ) {
						throw new Forminator_Integration_Exception(
							sprintf(
							/* translators: 1: Required field name */
								esc_html__( 'Required Field %1$s not mapped yet to Forminator Form Field, Please check your Mailchimp Configuration on Form Settings', 'forminator' ),
								esc_html( $mailchimp_required_field->name )
							)
						);
					}

					$element_id      = $addon_setting_values['fields_map'][ $mailchimp_required_field->tag ];
					$is_calculation  = self::element_is_calculation( $element_id );
					$is_stripe       = self::element_is_stripe( $element_id );
					$has_submit_data = isset( $submitted_data[ $element_id ] ) && ! empty( $submitted_data[ $element_id ] );

					if ( ! $is_calculation && ! $is_stripe && ! $has_submit_data ) {
						throw new Forminator_Integration_Exception(
							sprintf(
							/* translators: 1: Required field name */
								esc_html__( 'Required Field %1$s not filled by user', 'forminator' ),
								esc_html( $mailchimp_required_field->name )
							)
						);
					}
				}
			}

			// check if user already on the list.
			$subscriber_hash = md5( strtolower( trim( $submitted_data[ $addon_setting_values['fields_map']['EMAIL'] ] ) ) );

			$is_double_opt_in_enabled = isset( $addon_setting_values['enable_double_opt_in'] ) && filter_var( $addon_setting_values['enable_double_opt_in'], FILTER_VALIDATE_BOOLEAN ) ? true : false;
			$status                   = 'subscribed';
			if ( $is_double_opt_in_enabled ) {
				$status = 'pending';
			}

			try {
				// keep subscribed if already subscribed.
				$member_status_request = $mailchimp_api->get_member( $addon_setting_values['mail_list_id'], $subscriber_hash, array() );
				if ( isset( $member_status_request->status ) && ! empty( $member_status_request->status ) ) {
					if ( 'subscribed' === $member_status_request->status ) {
						// already subscribed, keep it subscribed, just update merge_fields.
						$status = 'subscribed';
					}
				}
			} catch ( Forminator_Integration_Exception $e ) {
				// Member not yet subscribed, keep going on, mark status based on double-opt-in option.
				if ( $is_double_opt_in_enabled ) {
					$status = 'pending';
				}
			}

			$args = array(
				'status'        => $status,
				'status_if_new' => $status,
				'email_address' => strtolower( trim( $submitted_data[ $addon_setting_values['fields_map']['EMAIL'] ] ) ),
			);

			$merge_fields = array();
			foreach ( $mailchimp_fields_list as $item ) {
				// its mapped ?
				if ( 'address' === $item->type ) {
					$address_fields = $this->settings_instance->mail_address_fields();
					foreach ( $address_fields as $addr => $address ) {
						if ( isset( $addon_setting_values['fields_map'][ $item->tag ] ) && ! empty( $addon_setting_values['fields_map'][ $item->tag ] ) ) {
							if ( isset( $submitted_data[ $addon_setting_values['fields_map'][ $item->tag ][ $addr ] ] ) && ! empty( $submitted_data[ $addon_setting_values['fields_map'][ $item->tag ][ $addr ] ] ) ) {
								$merge_fields[ $item->tag ][ $addr ] = trim( $submitted_data[ $addon_setting_values['fields_map'][ $item->tag ][ $addr ] ] );
							}
						}
					}
				} elseif ( isset( $addon_setting_values['fields_map'][ $item->tag ] ) && ! empty( $addon_setting_values['fields_map'][ $item->tag ] ) ) {
						$element_id = $addon_setting_values['fields_map'][ $item->tag ];
					if ( isset( $submitted_data[ $element_id ] ) && ( ! empty( $submitted_data[ $element_id ] ) || 0 === (int) $submitted_data[ $element_id ] ) ) {
						$element_value = trim( $submitted_data[ $element_id ] );
					}

					if ( isset( $element_value ) ) {
						$merge_fields[ $item->tag ] = $element_value;
						unset( $element_value ); // unset for next loop.
					}
				}
			}

			forminator_addon_maybe_log( __METHOD__, $mailchimp_fields_list, $addon_setting_values, $submitted_data, $merge_fields );

			if ( ! empty( $merge_fields ) ) {
				$args['merge_fields'] = $merge_fields;
			}

			$args = self::maybe_add_additional_data( $args, $addon_setting_values, $gdpr );

			$mail_list_id = $addon_setting_values['mail_list_id'];

			/**
			 * Filter mail list id to send to Mailchimp API
			 *
			 * Change $mail_list_id that will be send to Mailchimp API,
			 * Any validation required by the mail list should be done.
			 * Else if it's rejected by Mailchimp API, It will only add Request to Log.
			 * Log can be viewed on Entries Page
			 *
			 * @since 1.1
			 *
			 * @param string                                   $mail_list_id
			 * @param int                                      $quiz_id                current Form ID.
			 * @param array                                    $submitted_data         Submitted data.
			 * @param Forminator_Mailchimp_Quiz_Settings $quiz_settings_instance Mailchimp Form Settings.
			 */
			$mail_list_id = apply_filters(
				'forminator_addon_mailchimp_add_update_member_request_mail_list_id',
				$mail_list_id,
				$quiz_id,
				$submitted_data,
				$quiz_settings_instance
			);

			/**
			 * Filter Mailchimp API request arguments
			 *
			 * Request Arguments will be added to request body.
			 * Default args that will be send contains these keys:
			 * - status
			 * - status_if_new
			 * - merge_fields
			 * - email_address
			 * - interests
			 *
			 * @since 1.1
			 *
			 * @param array                                    $args
			 * @param int                                      $quiz_id                current Quiz ID.
			 * @param array                                    $submitted_data         Submitted data.
			 * @param Forminator_Mailchimp_Quiz_Settings $quiz_settings_instance Mailchimp Quiz Settings.
			 */
			$args = apply_filters(
				'forminator_addon_mailchimp_add_update_member_request_args',
				$args,
				$quiz_id,
				$submitted_data,
				$quiz_settings_instance
			);

			/**
			 * Fires before Integration send request `add_or_update_member` to Mailchimp API
			 *
			 * If this action throw an error,
			 * then `add_or_update_member` process will be cancelled
			 *
			 * @since 1.1
			 *
			 * @param int                                      $quiz_id                current Quiz ID.
			 * @param array                                    $submitted_data         Submitted data.
			 * @param Forminator_Mailchimp_Quiz_Settings $quiz_settings_instance Mailchimp Quiz Settings.
			 */
			do_action( 'forminator_addon_mailchimp_before_add_update_member', $quiz_id, $submitted_data, $quiz_settings_instance );

			$add_member_request = $mailchimp_api->add_or_update_member( $mail_list_id, $subscriber_hash, $args );

			if ( ! isset( $add_member_request->id ) || ! $add_member_request->id ) {
				throw new Forminator_Integration_Exception(
					esc_html__(
						'Failed adding or updating member on Mailchimp list',
						'forminator'
					)
				);
			}

			forminator_addon_maybe_log( __METHOD__, 'Success Add Member' );

			$entry_fields = array(
				array(
					'value' => array(
						'is_sent'       => true,
						'description'   => esc_html__( 'Successfully added or updated member on Mailchimp list', 'forminator' ),
						'data_sent'     => $mailchimp_api->get_last_data_sent(),
						'data_received' => $mailchimp_api->get_last_data_received(),
						'url_request'   => $mailchimp_api->get_last_url_request(),
					),
				),
			);

		} catch ( Forminator_Integration_Exception $e ) {
			forminator_addon_maybe_log( __METHOD__, 'Failed to Add Member' );

			$entry_fields = array(
				array(
					'value' => array(
						'is_sent'       => false,
						'description'   => $e->getMessage(),
						'data_sent'     => ( ( $mailchimp_api instanceof Forminator_Mailchimp_Wp_Api ) ? $mailchimp_api->get_last_data_sent() : array() ),
						'data_received' => ( ( $mailchimp_api instanceof Forminator_Mailchimp_Wp_Api ) ? $mailchimp_api->get_last_data_received() : array() ),
						'url_request'   => ( ( $mailchimp_api instanceof Forminator_Mailchimp_Wp_Api ) ? $mailchimp_api->get_last_url_request() : '' ),
					),
				),
			);
		}

		$entry_fields[0]['name']                     = $entry_name;
		$entry_fields[0]['value']['connection_name'] = $identifier;

		return $entry_fields;
	}

	/**
	 * Add additional data if needed.
	 *
	 * @param array $args Existed arguments.
	 * @param array $addon_setting_values Integration settings.
	 * @param bool  $gdpr Is a GDPR field exist or not.
	 * @return array
	 */
	private static function maybe_add_additional_data( $args, $addon_setting_values, $gdpr ) {
		if ( ! empty( $addon_setting_values['group_interest'] ) ) {
			$interests         = (array) $addon_setting_values['group_interest'];
			$args['interests'] = array_fill_keys( $interests, true );
		}

		if ( ! empty( $addon_setting_values['tags'] ) ) {
			$args['tags'] = array_values( $addon_setting_values['tags'] );
		}

		if ( true === $gdpr && ! empty( $addon_setting_values['gdpr'] ) ) {
			$args['marketing_permissions'] = self::prepare_marketing_permissions( $addon_setting_values['gdpr'] );
		}

		return $args;
	}

	/**
	 * Prepare GDPR fields for Mailchimp API
	 *
	 * @param array $gdpr_fields Saved GDPR fields.
	 * @return array
	 */
	private static function prepare_marketing_permissions( $gdpr_fields ) {
		$permissions = array();
		foreach ( $gdpr_fields as $key => $title ) {
			$permissions[] = array(
				'marketing_permission_id' => $key,
				'enabled'                 => true,
			);
		}

		return $permissions;
	}

	/**
	 * Add member status information
	 *
	 * @param array $sub_entries Sub-entries.
	 * @param array $addon_data Integration meta data.
	 * @return void
	 */
	public static function add_extra_entry_items( array &$sub_entries, array $addon_data ) {
		Forminator_Mailchimp_Form_Hooks::add_extra_entry_items( $sub_entries, $addon_data );
	}

	/**
	 * It will delete members on mailchimp list
	 *
	 * @since 1.0 Mailchimp Integration
	 *
	 * @param Forminator_Form_Entry_Model $entry_model Form Entry Model.
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
		 * Filter mailchimp integration metadata that previously saved on db to be processed
		 *
		 * Although it can be used for all integration.
		 * Please keep in mind that if the integration override this method,
		 * then this filter probably won't be applied.
		 * To be sure please check individual integration documentations.
		 *
		 * @since 1.1
		 *
		 * @param array                                        $addon_meta_data
		 * @param int                                          $quiz_id                current quiz ID.
		 * @param Forminator_Form_Entry_Model                  $entry_model            Forminator Entry Model.
		 * @param Forminator_Integration_Quiz_Settings|null $quiz_settings_instance of Integration quiz Settings.
		 */
		$addon_meta_data = apply_filters(
			'forminator_addon_mailchimp_metadata',
			$addon_meta_data,
			$quiz_id,
			$entry_model,
			$quiz_settings_instance
		);

		/**
		 * Fires when mailchimp connected form delete a submission
		 *
		 * Although it can be used for all integration.
		 * Please keep in mind that if the integration override this method,
		 * then this action won't be triggered.
		 * To be sure please check individual integration documentations.
		 *
		 * @since 1.1
		 *
		 * @param int                                          $quiz_id                current quiz ID.
		 * @param Forminator_Form_Entry_Model                  $entry_model            Forminator Entry Model.
		 * @param array                                        $addon_meta_data        integration meta data.
		 * @param Forminator_Integration_Quiz_Settings|null $quiz_settings_instance of Integration quiz Settings.
		 */
		do_action(
			'forminator_addon_mailchimp_on_before_delete_submission',
			$quiz_id,
			$entry_model,
			$addon_meta_data,
			$quiz_settings_instance
		);

		if ( ! Forminator_Mailchimp::is_enable_delete_member() ) {
			// its disabled, go for it!
			return true;
		}
		$mailchimp_api = null;
		try {
			$delete_member_url = '';
			/**
			 * Filter delete member url to send to mailchimp api
			 */
			$delete_member_url = apply_filters(
				'forminator_addon_mailchimp_delete_member_url',
				$delete_member_url,
				$quiz_id,
				$addon_meta_data,
				$quiz_settings_instance
			);

			if ( empty( $delete_member_url ) ) {
				$delete_member_url = self::get_delete_member_url_from_addon_meta_data( $addon_meta_data );
			}

			forminator_addon_maybe_log( __METHOD__, $delete_member_url );

			if ( ! empty( $delete_member_url ) ) {
				$mailchimp_api = $this->addon->get_api();
				$mailchimp_api->delete_( $delete_member_url );
			}

			return true;

		} catch ( Forminator_Integration_Exception $e ) {
			// its not found, probably already deleted on mailchimp.
			return true;
		} catch ( Forminator_Integration_Exception $e ) {
			// handle all internal integration exceptions with `Forminator_Integration_Exception`.

			// use wp_error, for future usage it can be returned to page entries.
			$wp_error = new WP_Error( 'forminator_addon_mailchimp_delete_member', $e->getMessage() );
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

	/**
	 * Get valid integration meta data
	 *
	 * @since 1.0 Mailchimp Integration
	 *
	 * @param array $addon_meta_data Addon meta data.
	 *
	 * @return array
	 */
	public static function get_valid_addon_meta_data_value( $addon_meta_data ) {
		// preliminary check of integration_meta_data.
		if ( ! isset( $addon_meta_data[0] ) || ! is_array( $addon_meta_data[0] ) ) {
			return array();
		}

		$addon_meta_data = $addon_meta_data[0];

		// make sure its `status`, because we only add this.
		if ( 'status' !== $addon_meta_data['name'] ) {
			return array();
		}
		if ( ! isset( $addon_meta_data['value'] ) || ! is_array( $addon_meta_data['value'] ) ) {
			return array();
		}

		return $addon_meta_data['value'];
	}

	/**
	 * Get DELETE member url form saved integration meta data
	 *
	 * @since 1.0 Mailchimp Integration
	 *
	 * @param array $addon_meta_data Addon meta data.
	 *
	 * @return string
	 */
	public static function get_delete_member_url_from_addon_meta_data( $addon_meta_data ) {

		// delete links available on data_received of mailchimp.
		/** == Integration meta data reference ==
		// [
		// {.
		// "name": "status",
		// "value": {.
		// "is_sent": true,
		// "description": "Successfully added or updated member on Mailchimp list",.
		// "data_sent": {.
		// ...
		// },
		// "data_received": {.
		// "id": "XXXXXXX",
		// ...
		// "list_id": "XXXXXXX",.
		// "_links": [.
		// {.
		// "rel": "upsert",
		// "href": "https:\/\/us9.api.mailchimp.com\/3.0\/lists\/XXXXXXX\/members\/XXXXXXX",.
		// "method": "PUT",.
		// "targetSchema": "https:\/\/us9.api.mailchimp.com\/schema\/3.0\/Definitions\/Lists\/Members\/Response.json",.
		// "schema": "https:\/\/us9.api.mailchimp.com\/schema\/3.0\/Definitions\/Lists\/Members\/PUT.json".
		// },.
		// {.
		// "rel": "delete",
		// "href": "https:\/\/us9.api.mailchimp.com\/3.0\/lists\/XXXXXXX\/members\/XXXXXXX",.
		// "method": "DELETE".
		// },.
		// ...
		// ].
		// },.
		// "url_request": "https:\/\/us9.api.mailchimp.com\/3.0\/lists\/XXXX\/members\/XXXXXXX".
		// }.
		// }.
		// ]
		== Integration meta data reference == */

		$delete_member_url = '';

		$meta_data_value = self::get_valid_addon_meta_data_value( $addon_meta_data );
		if ( empty( $meta_data_value ) ) {
			// probably this entry added before connected to mailchimp, mark it as okay to delete entry.
			return '';
		}

		if ( isset( $meta_data_value['is_sent'] ) && ! $meta_data_value['is_sent'] ) {
			// its not sent to mailchimp so it won't have delete member uri.
			return '';
		}

		if ( ! isset( $meta_data_value['data_received'] ) || ! is_object( $meta_data_value['data_received'] ) ) {
			// something is happened on integration meta data.
			return '';
		}

		$data_received = $meta_data_value['data_received'];

		if ( ! isset( $data_received->_links ) || ! is_array( $data_received->_links ) ) {
			// something is happened on integration meta data.
			return '';
		}

		foreach ( $data_received->_links as $link ) {
			if ( ! isset( $link->rel ) || ! isset( $link->method ) || ! isset( $link->href ) ) {
				continue;
			}
			if ( 'delete' === $link->rel && 'DELETE' === $link->method ) {
				$delete_member_url = $link->href;
			}
		}

		return $delete_member_url;
	}
}
