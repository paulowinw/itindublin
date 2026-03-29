<?php
/**
 * The Forminator_Integration_Hooks class.
 *
 * @package Forminator
 */

/**
 * Class Forminator_Integration_Hooks
 */
abstract class Forminator_Integration_Hooks {
	/**
	 * Integration Instance
	 *
	 * @since 1.1
	 * @var Forminator_Integration
	 */
	protected $addon;

	/**
	 * Customizable submit form error message
	 *
	 * @since 1.1
	 * @var string
	 */
	protected $submit_error_message = '';

	/**
	 * Form settings instance
	 *
	 * @since 1.1
	 * @var Forminator_Integration_Settings|null
	 */
	protected $settings_instance;

	/**
	 * Prefix for calculation element
	 *
	 * @since 1.7
	 */
	const CALCULATION_ELEMENT_PREFIX = 'calculation-';

	/**
	 * Prefix for stripe element
	 *
	 * @since 1.7
	 */
	const STRIPE_ELEMENT_PREFIX = 'stripe-';

	/**
	 * Current Module ID
	 *
	 * @since 1.1
	 * @var int
	 */
	protected $module_id;

	/**
	 * Custom Module Model
	 *
	 * @since 1.2
	 * @var Forminator_Form_Model|Forminator_Poll_Model|Forminator_Quiz_Model
	 */
	protected $module;

	/**
	 * Forminator_Integration_Hooks constructor.
	 *
	 * @param Forminator_Integration $addon Integration.
	 * @param int                    $module_id Module ID.
	 *
	 * @since 1.1
	 * @throws Forminator_Integration_Exception When module ID is invalid.
	 */
	public function __construct( Forminator_Integration $addon, int $module_id ) {
		$this->addon     = $addon;
		$this->module_id = $module_id;
		$this->module    = Forminator_Base_Form_Model::get_model( $this->module_id );
		if ( ! $this->module ) {
			/* translators: 1. Module type 2. Module ID */
			throw new Forminator_Integration_Exception( sprintf( esc_html__( '%1$s with id %1$d could not be found', 'forminator' ), esc_html( ucfirst( static::$slug ) ), esc_html( $this->module_id ) ) );
		}

		/* translators: Module type */
		$this->submit_error_message = sprintf( esc_html__( '%1$s failed to process submitted data. Please check your %2$s and try again', 'forminator' ), $this->addon->get_title(), static::$slug );

		// get module settings instance to be available throughout cycle.
		$this->settings_instance = $this->addon->get_addon_settings( $this->module_id, static::$slug );
	}

	/**
	 * Override this function to add another entry field to storage
	 *
	 * Return an multi array with format (at least, or it will be skipped)
	 * [
	 *  'name' => NAME,
	 *  'value' => VALUE', => can be array/object/scalar, it will serialized on storage
	 * ],
	 * [
	 *  'name' => NAME,
	 *  'value' => VALUE'
	 * ]
	 *
	 * @since          1.1
	 * @since          1.2 Add `$current_entry_fields` as optional param on inherit
	 *
	 * @param array $submitted_data Submitted data.
	 * @param array $current_entry_fields default entry fields that will be saved,
	 *                                    its here for reference, this function doesnt need to return it
	 *                                    only return new entry fields.
	 * @param array $entry Entry.
	 *
	 * @return array
	 */
	public function add_entry_fields( $submitted_data, $current_entry_fields, $entry ) {
		$addon_slug        = $this->addon->get_slug();
		$module_id         = $this->module_id;
		$settings_instance = $this->settings_instance;

		/**
		 * Filter submitted data to be processed by addon
		 *
		 * Although it can be used for all addon.
		 * Please keep in mind that if the addon override this method,
		 * then this filter won't be applied.
		 * To be sure please check individual addon documentations.
		 *
		 * @since 1.1
		 *
		 * @param array                                        $submitted_data Submitted data.
		 * @param int                                          $module_id Current Module ID.
		 * @param Forminator_Integration_Form_Settings|null $settings_instance Integration Settings instance.
		 */
		$submitted_data = apply_filters(
			'forminator_addon_' . $addon_slug . '_' . static::$slug . '_submitted_data',
			$submitted_data,
			$module_id,
			$settings_instance
		);

		forminator_addon_maybe_log( __METHOD__, $submitted_data );

		do_action_deprecated( 'forminator_addon_' . $addon_slug . '_before_contact_sync', array( $module_id, $submitted_data, $settings_instance ), '1.33', 'forminator_addon_' . $addon_slug . '_' . static::$slug . '_submitted_data', );
		do_action_deprecated( 'forminator_addon_' . $addon_slug . '_before_add_subscriber', array( $module_id, $submitted_data, $settings_instance ), '1.33', 'forminator_addon_' . $addon_slug . '_' . static::$slug . '_submitted_data', );
		do_action_deprecated( 'forminator_addon_' . $addon_slug . '_before_send_message', array( $module_id, $submitted_data, $settings_instance ), '1.33', 'forminator_addon_' . $addon_slug . '_' . static::$slug . '_submitted_data', );
		do_action_deprecated( 'forminator_addon_' . $addon_slug . '_' . static::$slug . '_before_send_message', array( $module_id, $submitted_data, $settings_instance ), '1.33', 'forminator_addon_' . $addon_slug . '_' . static::$slug . '_submitted_data', );
		do_action_deprecated( 'forminator_addon_' . $addon_slug . '_before_create_row', array( $module_id, $submitted_data, $settings_instance ), '1.33', 'forminator_addon_' . $addon_slug . '_' . static::$slug . '_submitted_data', );
		do_action_deprecated( 'forminator_addon_' . $addon_slug . '_' . static::$slug . '_before_create_row', array( $module_id, $submitted_data, $settings_instance ), '1.33', 'forminator_addon_' . $addon_slug . '_' . static::$slug . '_submitted_data', );
		do_action_deprecated( 'forminator_addon_' . $addon_slug . '_before_post_to_webhook', array( $module_id, $submitted_data, $settings_instance ), '1.33', 'forminator_addon_' . $addon_slug . '_' . static::$slug . '_submitted_data', );
		do_action_deprecated( 'forminator_addon_' . $addon_slug . '_' . static::$slug . '_before_post_to_webhook', array( $module_id, $submitted_data, $settings_instance ), '1.33', 'forminator_addon_' . $addon_slug . '_' . static::$slug . '_submitted_data', );
		do_action_deprecated( 'forminator_addon_' . $addon_slug . '_before_create_card', array( $module_id, $submitted_data, $settings_instance ), '1.33', 'forminator_addon_' . $addon_slug . '_' . static::$slug . '_submitted_data', );
		do_action_deprecated( 'forminator_addon_' . $addon_slug . '_' . static::$slug . '_before_create_card', array( $module_id, $submitted_data, $settings_instance ), '1.33', 'forminator_addon_' . $addon_slug . '_' . static::$slug . '_submitted_data', );

		$entry_fields = $this->custom_entry_fields( $submitted_data, $current_entry_fields, $entry );

		/**
		 * Filter addon entry fields to be saved to entry model
		 *
		 * @since 1.1
		 * @since 1.2 Add `$entry_fields` as param
		 *
		 * @param array                              $entry_fields Entry fields.
		 * @param int                                $module_id Current Module ID.
		 * @param array                              $submitted_data Submitted data.
		 * @param Forminator_Integration_Settings $settings_instance Integration Settings instance.
		 * @param array                              $current_entry_fields Current entry fields.
		 */
		$entry_fields = apply_filters(
			'forminator_addon_' . static::$slug . '_' . $addon_slug . '_entry_fields',
			$entry_fields,
			$module_id,
			$submitted_data,
			$settings_instance,
			$current_entry_fields
		);

		$entry_fields = apply_filters_deprecated(
			'forminator_addon_' . $addon_slug . '_entry_fields',
			array(
				$entry_fields,
				$module_id,
				$submitted_data,
				$settings_instance,
				$current_entry_fields,
			),
			'1.33',
			'forminator_addon_' . static::$slug . '_' . $addon_slug . '_entry_fields'
		);

		return $entry_fields;
	}

	/**
	 * Return custom entry fields
	 *
	 * @param array $submitted_data Submitted data.
	 * @param array $current_entry_fields Current entry fields.
	 * @return array
	 * @throws Forminator_Integration_Exception When there is an Integration error.
	 */
	protected function custom_entry_fields( array $submitted_data, array $current_entry_fields ): array {
		$module_id            = $this->module_id;
		$settings_instance    = $this->settings_instance;
		$submitted_data       = $this->prepare_submitted_data( $submitted_data, $module_id, $current_entry_fields );
		$addon_setting_values = $settings_instance->get_settings_values();
		// initialize as null.
		$addon_api = null;

		$settings_values = $this->addon->get_settings_values();
		$identifier      = $settings_values['identifier'] ?? '';
		$entry_name      = 'status';
		if ( ! empty( $this->addon->multi_global_id ) ) {
			$entry_name .= "-{$this->addon->multi_global_id}";
		}

		// check required fields.
		try {
			$addon_api = $this->addon->get_api();

			// email : super required**.
			if ( ! isset( $addon_setting_values['fields_map']['email'] ) ) {
				throw new Forminator_Integration_Exception(
				/* translators: 1: email */
					sprintf( esc_html__( 'Required Field %1$s not mapped yet to Forminator Field, Please check your Integration Configuration on Module Settings', 'forminator' ), 'email' )
				);
			}

			if ( empty( $submitted_data[ $addon_setting_values['fields_map']['email'] ] ) ) {
				throw new Forminator_Integration_Exception(
				/* translators: 1: Email */
					sprintf( esc_html__( 'Required Field %1$s is not filled by user', 'forminator' ), 'email' )
				);
			}

			$addon_fields = $this->addon->get_api()->get_contact_properties();
			forminator_addon_maybe_log( __METHOD__, $addon_fields );

			$email = strtolower( trim( $submitted_data[ $addon_setting_values['fields_map']['email'] ] ) );

			$merge_fields = array();
			foreach ( $addon_fields as $item ) {
				// its mapped ?
				if ( ! empty( $addon_setting_values['fields_map'][ $item->id ] ) ) {
					$element_id = $addon_setting_values['fields_map'][ $item->id ];
					if ( ! isset( $submitted_data[ $element_id ] ) ) {
						continue;
					}
					$value    = $submitted_data[ $element_id ];
					$item_key = $item->key ?? $item->name;

					$merge_fields[ $item_key ] = $this->prepare_addon_field_format( $item, $value );
				}
			}

			forminator_addon_maybe_log( __METHOD__, $addon_fields, $addon_setting_values, $submitted_data, $merge_fields );

			$args = $this->get_special_addon_args( $submitted_data, $addon_setting_values );
			if ( ! empty( $merge_fields ) ) {
				$args['merge_fields'] = $merge_fields;
			}

			$mail_list_id = $addon_setting_values['mail_list_id'];

			/**
			 * Filter mail list id to send to Integration API
			 *
			 * Change $mail_list_id that will be sent to Integration API,
			 * Any validation required by the mail list should be done.
			 * Else if it's rejected by Integration API, It will only add Request to Log.
			 * Log can be viewed on Entries Page
			 *
			 * @param string                                  $mail_list_id
			 * @param int                                     $module_id Module ID.
			 * @param array                                   $submitted_data Submitted data.
			 * @param Forminator_Integration_Settings $settings_instance Integration Settings.
			 */
			$mail_list_id = apply_filters(
				'forminator_addon_' . $this->addon->get_slug() . '_add_update_member_request_mail_list_id',
				$mail_list_id,
				$module_id,
				$submitted_data,
				$settings_instance
			);

			/**
			 * Filter Integration API request arguments
			 *
			 * Request Arguments will be added to request body.
			 *
			 * @param array                              $args
			 * @param int                                $module_id Module ID.
			 * @param array                              $submitted_data Submitted data.
			 * @param Forminator_Integration_Settings $settings_instance Integration Settings.
			 */
			$args = apply_filters(
				'forminator_addon_' . $this->addon->get_slug() . '_add_update_member_request_args',
				$args,
				$module_id,
				$submitted_data,
				$settings_instance
			);

			/**
			 * Fires before Integration send request `add_or_update_member` to Integration API
			 *
			 * If this action throw an error,
			 * then `add_or_update_member` process will be cancelled
			 *
			 * @param int                                $module_id Module ID.
			 * @param array                              $submitted_data Submitted data.
			 * @param Forminator_Integration_Settings $settings_instance Integration Settings.
			 */
			do_action( 'forminator_addon_' . $this->addon->get_slug() . '_before_add_update_member', $module_id, $submitted_data, $settings_instance );

			$add_member_request = $addon_api->add_or_update_member( $mail_list_id, $email, $args );
			if ( ! $add_member_request ) {
				throw new Forminator_Integration_Exception(
					esc_html__( 'Failed adding or updating member on Integration list', 'forminator' )
				);
			}

			forminator_addon_maybe_log( __METHOD__, 'Success Add Member' );

			$entry_fields = array(
				array(
					'value' => array(
						'is_sent'       => true,
						'description'   => esc_html__( 'Successfully added or updated member on the list', 'forminator' ),
						'data_sent'     => $addon_api->get_last_data_sent(),
						'data_received' => $addon_api->get_last_data_received(),
						'url_request'   => $addon_api->get_last_url_request(),
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
						'data_sent'     => method_exists( $addon_api, 'get_last_data_sent' ) ? $addon_api->get_last_data_sent() : array(),
						'data_received' => method_exists( $addon_api, 'get_last_data_received' ) ? $addon_api->get_last_data_received() : array(),
						'url_request'   => method_exists( $addon_api, 'get_last_url_request' ) ? $addon_api->get_last_url_request() : '',
					),
				),
			);
		}

		$entry_fields[0]['name']                     = $entry_name;
		$entry_fields[0]['value']['connection_name'] = $identifier;

		return $entry_fields;
	}

	/**
	 * Return special addon args
	 *
	 * @param array $submitted_data Submitted data.
	 * @param array $addon_setting_values Integration settings.
	 * @return array
	 */
	protected function get_special_addon_args( $submitted_data, $addon_setting_values ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Used in extended class
		return array();
	}

	/**
	 * Add new Column on header of export file
	 * for instance, `ActiveCampaign Info`
	 *
	 * @return array
	 */
	public function on_export_render_title_row(): array {

		$export_headers = array(
			/* translators: Integration name */
			'info' => sprintf( esc_html__( '%s Info', 'forminator' ), $this->addon->get_title() ),
		);

		$module_id         = $this->module_id;
		$settings_instance = $this->settings_instance;

		/**
		 * Filter Activecampaign headers on export file
		 *
		 * @since 1.1
		 *
		 * @param array                              $export_headers Headers to be displayed on export file.
		 * @param int                                $module_id Current Module ID.
		 * @param Forminator_Integration_Settings $settings_instance Integration Settings instance.
		 */
		$export_headers = apply_filters(
			'forminator_addon_' . $this->addon->get_slug() . '_export_headers',
			$export_headers,
			$module_id,
			$settings_instance
		);

		return $export_headers;
	}

	/**
	 * Loop through addon meta data on multiple connections
	 *
	 * @since 1.0
	 *
	 * @param array $addon_meta_datas Addon meta.
	 *
	 * @return array
	 */
	protected function on_render_entry_multi_connection( $addon_meta_datas ) {
		if ( ! isset( $addon_meta_datas[0] ) || ! is_array( $addon_meta_datas[0] ) ) {
			return array();
		}
		$additional_entry_item = array();
		foreach ( $addon_meta_datas as $addon_meta_data ) {
			$additional_entry_item[] = $this->get_additional_entry_item( $addon_meta_data );
		}

		return $additional_entry_item;
	}

	/**
	 * Integration will add a column that give user information whether sending data to the Integration successfully or not
	 * It will only add one column even its multiple connection, every connection will be separated by comma
	 *
	 * @since 1.0
	 *
	 * @param Forminator_Form_Entry_Model $entry_model Form entry model.
	 * @param array                       $addon_meta_data Addon meta data.
	 *
	 * @return array
	 */
	public function on_export_render_entry( Forminator_Form_Entry_Model $entry_model, $addon_meta_data ) {
		$addon_slug        = $this->addon->get_slug();
		$module_id         = $this->module_id;
		$settings_instance = $this->settings_instance;

		/**
		 * Filter Integration metadata that previously saved on db to be processed
		 *
		 *  Although it can be used for all addon.
		 *  Please keep in mind that if the addon override this method,
		 *  then this filter probably won't be applied.
		 *  To be sure please check individual addon documentations.
		 *
		 * @param array                              $addon_meta_data
		 * @param int                                $module_id Current Module ID.
		 * @param Forminator_Integration_Settings $settings_instance Integration Settings instance.
		 *@since 1.1
		 */
		$addon_meta_data = apply_filters(
			'forminator_addon_' . static::$slug . '_' . $addon_slug . '_metadata',
			$addon_meta_data,
			$module_id,
			$settings_instance
		);

		$export_columns = array(
			'info' => $this->get_from_addon_meta_data( $addon_meta_data, 'description' ),
		);

		/**
		 * Filter Integration columns to be displayed on export submissions
		 *
		 * @since 1.1
		 *
		 * @param array                                          $export_columns         column to be exported.
		 * @param int                                            $module_id                current Module ID.
		 * @param Forminator_Form_Entry_Model                    $entry_model            Form Entry Model.
		 * @param array                                          $addon_meta_data        meta data saved by addon on entry fields.
		 * @param Forminator_Integration_Settings             $settings_instance Integration Settings instance.
		 */
		$export_columns = apply_filters(
			'forminator_addon_' . static::$slug . '_' . $addon_slug . '_export_columns',
			$export_columns,
			$module_id,
			$entry_model,
			$addon_meta_data,
			$settings_instance
		);

		return $export_columns;
	}

	/**
	 * Format additional entry item as label and value arrays
	 *
	 * - Integration Name : its defined by user when they are adding integration on their module
	 * - Sent To {Integration name} : will be Yes/No value, that indicates whether sending data to the addon was successful
	 * - Info : Text that are generated by addon when building and sending data to it
	 * - Below subentries will be added if full log enabled, @see Forminator_Integration::is_show_full_log()
	 *      - API URL : URL that wes requested when sending data to the addon
	 *      - Data sent to {Integration name} : json encoded body request that was sent
	 *      - Data received from {Integration name} : json encoded body response that was received
	 *
	 * @param array $addon_meta_data Integration metadata.
	 *
	 * @return array
	 */
	protected function get_additional_entry_item( $addon_meta_data ) {

		if ( ! isset( $addon_meta_data['value'] ) || ! is_array( $addon_meta_data['value'] ) ) {
			return array();
		}
		$status                = $addon_meta_data['value'];
		$additional_entry_item = array(
			/* translators: Integration name */
			'label' => sprintf( esc_html__( '%s Integration', 'forminator' ), $this->addon->get_title() ),
			'value' => '',
		);

		$sub_entries = array();
		if ( isset( $status['connection_name'] ) ) {
			$sub_entries[] = array(
				'label' => esc_html__( 'Integration Name', 'forminator' ),
				'value' => $status['connection_name'],
			);
		}

		if ( isset( $status['is_sent'] ) ) {
			$is_sent       = true === $status['is_sent'] ? esc_html__( 'Yes', 'forminator' ) : esc_html__( 'No', 'forminator' );
			$sub_entries[] = array(
				/* translators: Integration name */
				'label' => sprintf( esc_html__( 'Sent To %s', 'forminator' ), $this->addon->get_title() ),
				'value' => $is_sent,
			);
		}

		if ( isset( $status['description'] ) ) {
			$sub_entries[] = array(
				'label' => esc_html__( 'Info', 'forminator' ),
				'value' => $status['description'],
			);
		}

		if ( is_callable( array( $this, 'add_extra_entry_items' ) ) ) {
			static::add_extra_entry_items( $sub_entries, $status );
		}

		if ( $this->addon->is_show_full_log() ) {
			// too long to be added on entry data enable this with the relevant constant.
			if ( isset( $status['url_request'] ) ) {
				$sub_entries[] = array(
					'label' => esc_html__( 'API URL', 'forminator' ),
					'value' => $status['url_request'],
				);
			}

			if ( isset( $status['data_sent'] ) ) {
				$sub_entries[] = array(
					/* translators: Integration name */
					'label' => sprintf( esc_html__( 'Data sent to %s', 'forminator' ), $this->addon->get_title() ),
					'value' => '<pre class="sui-code-snippet">' . wp_json_encode( $status['data_sent'], JSON_PRETTY_PRINT ) . '</pre>',
				);
			}

			if ( isset( $status['data_received'] ) ) {
				$sub_entries[] = array(
					/* translators: Integration name */
					'label' => sprintf( esc_html__( 'Data received from %s', 'forminator' ), $this->addon->get_title() ),
					'value' => '<pre class="sui-code-snippet">' . wp_json_encode( $status['data_received'], JSON_PRETTY_PRINT ) . '</pre>',
				);
			}
		}

		$additional_entry_item['sub_entries'] = $sub_entries;

		// return single array.
		return $additional_entry_item;
	}

	/**
	 * It wil add new row on entry table of submission page, with couple of sub-entries
	 * sub-entries included are defined in @see Forminator_Integration_Hooks::get_additional_entry_item()
	 *
	 * @since 1.0
	 *
	 * @param Forminator_Form_Entry_Model $entry_model Form entry model.
	 * @param array                       $addon_meta_data Addon meta.
	 *
	 * @return array
	 */
	public function on_render_entry( Forminator_Form_Entry_Model $entry_model, $addon_meta_data ) {
		$module_id         = $this->module_id;
		$settings_instance = $this->settings_instance;

		/**
		 *
		 * Filter addon metadata that previously saved on db to be processed
		 *
		 * Although it can be used for all addon.
		 * Please keep in mind that if the addon override this method,
		 * then this filter probably won't be applied.
		 * To be sure please check individual addon documentations.
		 *
		 * @param array  $addon_meta_data Integration metadata.
		 * @param int    $module_id Current Module ID.
		 * @param Forminator_Form_Entry_Model $entry_model Forminator Entry Model.
		 * @param object $settings_instance Integration Settings instance.
		 */
		$addon_meta_data = apply_filters(
			'forminator_addon_' . $this->addon->get_slug() . '_metadata',
			$addon_meta_data,
			$module_id,
			$entry_model,
			$settings_instance
		);

		return $this->on_render_entry_multi_connection( $addon_meta_data );
	}

	/**
	 * Get Integration meta data, will be recursive if meta data is multiple because of multiple connection added
	 *
	 * @since 1.6.2
	 *
	 * @param array  $addon_meta_data All meta data.
	 * @param string $key Meta key.
	 * @param mixed  $default_value Default returning value.
	 *
	 * @return string
	 */
	protected function get_from_addon_meta_data( $addon_meta_data, $key, $default_value = '' ) {
		$addon_meta_datas = $addon_meta_data;
		if ( ! isset( $addon_meta_data[0] ) || ! is_array( $addon_meta_data[0] ) ) {
			return $default_value;
		}

		$addon_meta_data = $addon_meta_data[0];

		// make sure its `status`, because we only add this.
		if ( 'status' !== $addon_meta_data['name'] ) {
			if ( stripos( $addon_meta_data['name'], 'status-' ) === 0 ) {
				$meta_data = array();
				foreach ( $addon_meta_datas as $addon_meta_data ) {
					// make it like single value so it will be processed like single meta data.
					$addon_meta_data['name'] = 'status';

					// add it on an array for next recursive process.
					$meta_data[] = $this->get_from_addon_meta_data( array( $addon_meta_data ), $key, $default_value );
				}

				return implode( ', ', $meta_data );
			}

			return $default_value;

		}

		if ( ! isset( $addon_meta_data['value'] ) || ! is_array( $addon_meta_data['value'] ) ) {
			return $default_value;
		}
		$status = $addon_meta_data['value'];
		if ( isset( $status[ $key ] ) ) {
			$connection_name = '';
			if ( 'connection_name' !== $key ) {
				if ( isset( $status['connection_name'] ) ) {
					$connection_name = '[' . $status['connection_name'] . '] ';
				}
			}

			return $connection_name . $status[ $key ];
		}

		return $default_value;
	}

	/**
	 * Override this function to execute action before submission deleted
	 *
	 * If function generate output, it will output-ed
	 * race condition between addon probably happen
	 * its void function, so return value will be ignored, and forminator process will always continue
	 * unless it generates unrecoverable error, so please be careful on extending this function
	 *
	 * @since 1.1
	 *
	 * @param Forminator_Form_Entry_Model $entry_model Forminator Entry Model.
	 * @param array                       $addon_meta_data Integration meta data.
	 */
	public function on_before_delete_entry( Forminator_Form_Entry_Model $entry_model, $addon_meta_data ) {
		$addon_slug        = $this->addon->get_slug();
		$module_id         = $this->module_id;
		$settings_instance = $this->settings_instance;

		/**
		 * Fires when connected module deletes a submission
		 *
		 * Although it can be used for all addon.
		 * Please keep in mind that if the addon override this method,
		 * then this action won't be triggered.
		 * To be sure please check individual addon documentations.
		 *
		 * @since 1.1
		 *
		 * @param int                                     $module_id Current Module ID.
		 * @param Forminator_Form_Entry_Model             $entry_model Forminator Entry Model.
		 * @param array                                   $addon_meta_data Integration meta data.
		 * @param Forminator_Integration_Settings|null $settings_instance Integration Settings.
		 */
		do_action(
			'forminator_addon_' . static::$slug . '_' . $addon_slug . '_on_before_delete_submission',
			$module_id,
			$entry_model,
			$addon_meta_data,
			$settings_instance
		);
	}

	/**
	 * Override this function to execute action on submit module
	 *
	 * Return true will continue forminator process,
	 * return false will stop forminator process,
	 * and display error message to user @see Forminator_Integration_Hooks::get_submit_error_message()
	 *
	 * @since 1.1
	 *
	 * @param array $submitted_data Submitted data.
	 *
	 * @return bool
	 */
	public function on_module_submit( $submitted_data ) {
		$addon_slug        = $this->addon->get_slug();
		$module_id         = $this->module_id;
		$settings_instance = $this->settings_instance;

		/**
		 * Filter submitted module data to be processed by addon
		 *
		 * Although it can be used for all addon.
		 * Please keep in mind that if the addon override this method,
		 * then this filter won't be applied.
		 * To be sure please check individual addon documentations.
		 *
		 * @since 1.1
		 *
		 * @param array                                   $submitted_data
		 * @param int                                     $module_id Current Module ID.
		 * @param Forminator_Integration_Settings|null $settings_instance Integration Settings instance.
		 */
		$submitted_data = apply_filters(
			'forminator_addon_' . $addon_slug . '_' . static::$slug . '_submitted_data',
			$submitted_data,
			$module_id,
			$settings_instance
		);

		$is_success = true;
		/**
		 * Filter result of module submit
		 *
		 * Return `true` if success, or **(string) error message** on fail
		 * Although it can be used for all addon.
		 * Please keep in mind that if the addon override this method,
		 * then this filter won't be applied.
		 * To be sure please check individual addon documentations.
		 *
		 * @since 1.1
		 *
		 * @param bool                                    $is_success
		 * @param int                                     $module_id Current Module ID.
		 * @param array                                   $submitted_data
		 * @param Forminator_Integration_Settings|null $settings_instance Integration Settings instance.
		 */
		$is_success = apply_filters(
			'forminator_addon_' . $addon_slug . '_on_' . static::$slug . '_submit_result',
			$is_success,
			$module_id,
			$submitted_data,
			$settings_instance
		);

		// process filter.
		if ( true !== $is_success && ! empty( $is_success ) ) {
			// only update `submit_error_message` when not empty.
			$this->submit_error_message = (string) $is_success;
		}

		return $is_success;
	}

	/**
	 * Return submitted data.
	 *
	 * @param array $submitted_data Submitted data.
	 * @param int   $module_id Module ID.
	 * @param array $current_entry_fields Current entry fields.
	 * @return array
	 */
	protected function prepare_submitted_data( array $submitted_data, int $module_id, array $current_entry_fields ): array {
		$submitted_data = $this->reformat_submitted_data( $submitted_data, $module_id, $current_entry_fields );

		return $submitted_data;
	}

	/**
	 * Return submitted data.
	 *
	 * @param array $submitted_data Submitted data.
	 * @param int   $module_id Module ID.
	 * @param array $current_entry_fields Current entry fields.
	 * @return array
	 */
	protected function reformat_submitted_data( array $submitted_data, int $module_id, array $current_entry_fields ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- May be used in extended class.
		foreach ( $submitted_data as $field => $value ) {
			// Also Check the date field doesn't include the '-year', '-month' or '-day'.
			if (
				false !== stripos( $field, 'date-' ) &&
				false === stripos( $field, '-year' ) &&
				false === stripos( $field, '-month' ) &&
				false === stripos( $field, '-day' ) &&
				! empty( $value )
			) {
				$date_format              = Forminator_API::get_form_field( $module_id, $field, false )->date_format;
				$normalized_format        = new Forminator_Date();
				$normalized_format        = $normalized_format->normalize_date_format( $date_format );
				$addon_format             = DateTime::createFromFormat( $normalized_format, $value );
				$addon_formatted          = $addon_format->format( 'Y-m-d' );
				$submitted_data[ $field ] = $addon_formatted;
			}
		}

		return $submitted_data;
	}

	/**
	 * Prepare field value according its type
	 *
	 * @param object $field Field properties.
	 * @param string $value Origin value.
	 * @return bool|float|int|string
	 */
	protected function prepare_addon_field_format( $field, $value ) {
		$field_type = $field->datatype ?? $field->type;
		if ( 'datetime' === $field_type ) {
			$time = strtotime( $value );
			if ( $time ) {
				$value = gmdate( 'U', $time );
			} else {
				$value = '';
			}
		} elseif ( 'int' === $field_type ) {
			$value = (int) $value;
		} elseif ( 'float' === $field_type ) {
			$value = (float) $value;
		} elseif ( 'bool' === $field_type ) {
			$value = (bool) $value;
		} else {
			$value = (string) $value;
		}

		return $value;
	}
}
