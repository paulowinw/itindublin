<?php
/**
 * Forminator Trello form hooks
 *
 * @package Forminator
 */

/**
 * Class Forminator_Trello_Form_Hooks
 *
 * @since 1.0 Trello Integration
 */
class Forminator_Trello_Form_Hooks extends Forminator_Integration_Form_Hooks {

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
	 * @since 1.0 Trello Integration
	 *
	 * @param string $connection_id Connection Id.
	 * @param array  $submitted_data Submitted data.
	 * @param array  $connection_settings Connection settings.
	 * @param array  $form_entry_fields Form entry fields.
	 * @param object $entry Entry instance.
	 *
	 * @return array `is_sent` true means its success send data to Trello, false otherwise
	 */
	private function get_status_on_create_card( $connection_id, $submitted_data, $connection_settings, $form_entry_fields, $entry ) {
		// initialize as null.
		$api = null;

		$form_id                = $this->module_id;
		$form_settings_instance = $this->settings_instance;
		$uploads                = $this->get_uploads( $form_entry_fields );

		// check required fields.
		try {
			$api  = $this->addon->get_api();
			$args = array();

			$form_settings = $this->settings_instance->get_form_settings();

			if ( isset( $connection_settings['list_id'] ) ) {
				$args['idList'] = $connection_settings['list_id'];
			}

			if ( isset( $connection_settings['card_name'] ) ) {
				$card_name = $connection_settings['card_name'];
				// disable all_fields here.
				$card_name = str_ireplace( '{all_fields}', '', $card_name );
				$card_name = forminator_addon_replace_custom_vars( $card_name, $submitted_data, $this->module, $form_entry_fields, false, $entry );

				/**
				 * Filter Card Name to passed on to Create Trello Card API
				 *
				 * @since 1.2
				 *
				 * @param string $card_name
				 * @param int $form_id Current Form id.
				 * @param string $connection_id ID of current connection.
				 * @param array $submitted_data
				 * @param array $connection_settings current connection setting, contains options of like `name`, `list_id` etc.
				 * @param array $form_entry_fields default entry fields of form.
				 * @param array $form_settings Displayed Form settings.
				 * @param Forminator_Trello_Form_Settings $form_settings_instance Trello Integration Form Settings instance.
				 */
				$card_name    = apply_filters(
					'forminator_addon_trello_card_name',
					$card_name,
					$form_id,
					$connection_id,
					$submitted_data,
					$connection_settings,
					$form_entry_fields,
					$form_settings,
					$form_settings_instance
				);
				$args['name'] = $card_name;

			}

			if ( isset( $connection_settings['card_description'] ) ) {
				$card_description       = $connection_settings['card_description'];
				$all_fields_to_markdown = $this->all_fields_to_markdown();
				$card_description       = str_ireplace( '{all_fields}', $all_fields_to_markdown, $card_description );
				$card_description       = forminator_addon_replace_custom_vars( $card_description, $submitted_data, $this->module, $form_entry_fields, false, $entry );

				/**
				 * Filter Card Description to passed on to Create Trello Card API
				 *
				 * @since 1.2
				 *
				 * @param string $card_description
				 * @param int $form_id Current Form id.
				 * @param string $connection_id ID of current connection.
				 * @param array $submitted_data
				 * @param array $connection_settings current connection setting, contains options of like `name`, `list_id` etc.
				 * @param array $form_entry_fields default entry fields of form.
				 * @param array $form_settings Displayed Form settings.
				 * @param Forminator_Trello_Form_Settings $form_settings_instance Trello Integration Form Settings instance.
				 */
				$card_description = apply_filters(
					'forminator_addon_trello_card_description',
					$card_description,
					$form_id,
					$connection_id,
					$submitted_data,
					$connection_settings,
					$form_entry_fields,
					$form_settings,
					$form_settings_instance
				);
				$args['desc']     = $card_description;
			}

			if ( isset( $connection_settings['due_date'] ) && ! empty( $connection_settings['due_date'] ) ) {
				$due_date = forminator_addon_replace_custom_vars( $connection_settings['due_date'], $submitted_data, $this->module, $form_entry_fields, false, $entry );
				if ( false !== strpos( $connection_settings['due_date'], '{' ) ) {
					$date_field       = str_replace( array( '{', '}' ), '', $connection_settings['due_date'] );
					$date_field_index = array_search( $date_field, array_column( $form_entry_fields, 'name' ), true );
					$date_format      = Forminator_Field::get_property( 'date_format', $form_entry_fields[ $date_field_index ]['field_array'] );
					$due_date         = forminator_reformat_date( $due_date, $date_format, 'F j Y' );
				}

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
			 * @since 1.2
			 *
			 * @param array $args
			 * @param int $form_id Current Form id.
			 * @param string $connection_id ID of current connection.
			 * @param array $submitted_data
			 * @param array $connection_settings current connection setting, contains options of like `name`, `list_id` etc.
			 * @param array $form_settings Displayed Form settings.
			 * @param Forminator_Trello_Form_Settings $form_settings_instance Trello Integration Form Settings instance.
			 */
			$args = apply_filters(
				'forminator_addon_trello_create_card_args',
				$args,
				$form_id,
				$connection_id,
				$submitted_data,
				$connection_settings,
				$form_settings,
				$form_settings_instance
			);

			$api->create_card( $args );
			$this->add_attachments( $api, $uploads );

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
	 * Special Replacer `{all_fields}` to markdown with Trello Flavour
	 */
	private function all_fields_to_markdown() {
		$form_fields = $this->settings_instance->get_form_fields();

		$markdown         = '';
		$post_element_ids = array();
		$field_format     = array();
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
					$value_format = '[' . esc_html__( 'Edit Post', 'forminator' ) . ']({' . $post_element_id . '})';
					break;
				case 'url':
					$value_format = '[{' . $element_id . '}]({' . $element_id . '})';
					break;
				case 'upload':
					$value_format = '[{' . $element_id . '}]({' . $element_id . '})';
					break;
				default:
					$value_format = '{' . $element_id . '}';
					break;
			}

			if ( in_array( $field_type, $field_format, true ) ) {

				$value_format = '[' . esc_html__( 'Edit Post', 'forminator' ) . ']({' . $post_element_id . '})';
			}

			$markdown .= self::get_field_markdown( $field_type, $field_label, $value_format );
		}

		/**
		 * Filter markdown for `all_fields`
		 *
		 * @since 1.2
		 *
		 * @param string $markdown
		 * @param array $form_fields all fields on form.
		 */
		$markdown = apply_filters(
			'forminator_addon_trello_all_fields_markdown',
			$markdown,
			$form_fields
		);

		return $markdown;
	}

	/**
	 * Get Markdown for single field
	 *
	 * @since 1.0 Trello Integration
	 *
	 * @param string $type Field type.
	 * @param string $label Field label.
	 * @param string $value Field value.
	 *
	 * @return string
	 */
	public static function get_field_markdown( $type, $label, $value ) {
		$markdown = "**{$label}**: {$value}\n";

		/**
		 * Filter single field markdown used by {all_fields}
		 *
		 * @since 1.2
		 *
		 * @param string $markdown
		 * @param string $type field type.
		 * @param string $label field label.
		 * @param string $value field string.
		 */
		$markdown = apply_filters(
			'forminator_addon_trello_field_markdown',
			$markdown,
			$type,
			$label,
			$value
		);

		return $markdown;
	}

	/**
	 * It will delete card on trello list
	 *
	 * @since 1.0 Trello Integration
	 *
	 * @param Forminator_Form_Entry_Model $entry_model Form entry Model.
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
		 * Filter Trello integration metadata that previously saved on db to be processed
		 *
		 * @since 1.1
		 *
		 * @param array $addon_meta_data
		 * @param int $form_id current Form ID.
		 * @param Forminator_Form_Entry_Model $entry_model Forminator Entry Model.
		 * @param Forminator_Trello_Form_Settings $form_settings_instance Trello Integration Form Settings instance.
		 */
		$addon_meta_data = apply_filters(
			'forminator_addon_trello_metadata',
			$addon_meta_data,
			$form_id,
			$entry_model,
			$form_settings_instance
		);

		/**
		 * Fires when Trello connected form delete a submission
		 *
		 * @since 1.1
		 *
		 * @param int $form_id current Form ID.
		 * @param Forminator_Form_Entry_Model $entry_model Forminator Entry Model.
		 * @param array $addon_meta_data integration meta data.
		 * @param Forminator_Trello_Form_Settings $form_settings_instance Trello Integration Form Settings instance.
		 */
		do_action(
			'forminator_addon_trello_on_before_delete_submission',
			$form_id,
			$entry_model,
			$addon_meta_data,
			$form_settings_instance
		);

		if ( ! Forminator_Trello::is_enable_delete_card() ) {
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

					if ( ! isset( $addon_meta_datum_value['data_received'] ) || ! is_object( $addon_meta_datum_value['data_received'] ) ) {
						continue;
					}

					$addon_meta_datum_received = $addon_meta_datum_value['data_received'];

					if ( ! isset( $addon_meta_datum_received->id ) || empty( $addon_meta_datum_received->id ) ) {
						continue;
					}
					/** Data received reference
					 *
					 * Data_received: {
					 *      "id": "XXXX",
					 * }
					 */
					$card_id = $addon_meta_datum_received->id;
					$this->delete_card( $card_id, $card_delete_mode, $addon_meta_datum );

				}
			}

			// delete mode!
			return true;

		} catch ( Forminator_Integration_Exception $e ) {
			// handle all internal integration exceptions with `Forminator_Integration_Exception`.

			// use wp_error, for future usage it can be returned to page entries.
			$wp_error
				= new WP_Error( 'forminator_addon_trello_delete_card', $e->getMessage() );
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
	 * Delete card hooked
	 *
	 * @since 1.0 Trello Integration
	 *
	 * @param string $card_id Card Id.
	 * @param string $card_delete_mode Card delete mode.
	 * @param array  $addon_meta_datum Addon meta data.
	 */
	public function delete_card( $card_id, $card_delete_mode, $addon_meta_datum ) {
		$api                    = $this->addon->get_api();
		$form_id                = $this->module_id;
		$form_settings_instance = $this->settings_instance;
		$args                   = array();

		/**
		 * Filter arguments to send to delete/close card Trello API
		 *
		 * @since 1.2
		 *
		 * @param array $args
		 * @param string $card_id
		 * @param string $card_delete_mode
		 * @param array $addon_meta_datum
		 * @param int $form_id
		 * @param Forminator_Trello_Form_Settings $form_settings_instance Trello Integration Form Settings instance.
		 */
		$args = apply_filters(
			'forminator_addon_trello_delete_card_args',
			$args,
			$card_id,
			$card_delete_mode,
			$addon_meta_datum,
			$form_id,
			$form_settings_instance
		);

		switch ( $card_delete_mode ) {
			case Forminator_Trello::CARD_DELETE_MODE_DELETE:
				$api->delete_card( $card_id, $args );
				break;
			case Forminator_Trello::CARD_DELETE_MODE_CLOSED:
				$api->close_card( $card_id, $args );
				break;
			default:
				break;
		}

		/**
		 * Fire when card already deleted or closed
		 *
		 * @since 1.2
		 *
		 * @param array $args args sent to Trello API.
		 * @param string $card_id
		 * @param string $card_delete_mode
		 * @param array $addon_meta_datum
		 * @param int $form_id
		 * @param Forminator_Trello_Form_Settings $form_settings_instance Trello Integration Form Settings instance.
		 */
		do_action(
			'forminator_addon_trello_delete_card',
			$args,
			$card_id,
			$card_delete_mode,
			$addon_meta_datum,
			$form_id,
			$form_settings_instance
		);
	}

	/**
	 * Get uploads to be added as attachments
	 *
	 * @param array $fields Fields.
	 */
	private function get_uploads( $fields ) {
		$uploads = array();

		foreach ( $fields as $i => $val ) {
			if ( 0 === stripos( $val['name'], 'upload-' ) ) {
				if ( ! empty( $val['value'] ) ) {
					$file_url = $val['value']['file']['file_url'];

					if ( is_array( $file_url ) ) {
						foreach ( $file_url as $url ) {
							$uploads[] = $url;
						}
					} else {
						$uploads[] = $file_url;
					}
				}
			}
		}

		return $uploads;
	}

	/**
	 * Add attachments to created card
	 *
	 * @param mixed $api API.
	 * @param array $uploads Uploads.
	 */
	private function add_attachments( $api, $uploads ) {
		$card_id = $api->get_card_id();

		if ( ! empty( $uploads ) ) {
			foreach ( $uploads as $upload ) {
				$api->add_attachment( $card_id, $upload );
			}
		}
	}
}
