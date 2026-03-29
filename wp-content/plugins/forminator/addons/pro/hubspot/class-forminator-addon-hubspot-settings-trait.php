<?php
/**
 * Trait for common methods for Hubspot settings classes
 *
 * @since 1.30
 * @package Hubspot Integration
 */

/**
 * Trait Forminator_Hubspot_Settings_Trait
 */
trait Forminator_Hubspot_Settings_Trait {
	/**
	 * HubSpot Module Settings wizard
	 *
	 * @since 1.0 HubSpot Integration
	 * @return array
	 */
	public function module_settings_wizards() {
		// numerical array steps.
		return array(
			array(
				'callback'     => array( $this, 'map_fields' ),
				'is_completed' => array( $this, 'map_fields_is_completed' ),
			),
			array(
				'callback'     => array( $this, 'create_ticket' ),
				'is_completed' => array( $this, 'create_ticket_is_completed' ),
			),
		);
	}

	/**
	 * Migrate old list_id to new list_id if needed
	 *
	 * @since 1.52.0
	 *
	 * @param array $settings Settings.
	 *
	 * @return mixed
	 */
	public function migrate_list_id( $settings ) {
		if ( ! empty( $settings ) ) {
			foreach ( $settings as $key => $setting ) {
				if ( ! empty( $setting['list_id'] )
				&& ( empty( $setting['list_api_version'] ) || 'v3' !== $setting['list_api_version'] ) ) {
					try {
						$api     = $this->addon->get_api();
						$v3_list = $api->get_v3_list_id( $setting['list_id'] );
						if ( ! empty( $v3_list->listId ) ) { //phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
							$settings[ $key ]['list_id']          = $v3_list->listId; //phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
							$settings[ $key ]['list_api_version'] = 'v3';
							$this->save_multi_id_setting_values(
								$key,
								$settings[ $key ]
							);
						}
					} catch ( Forminator_Integration_Settings_Exception $e ) {
						continue;
					}
				}
			}
		}
		return $settings;
	}

	/**
	 * Get list id
	 *
	 * @param mixed $multi_id Multi id.
	 * @return mixed
	 */
	public function get_list_id( $multi_id ) {
		$settings = $this->get_settings_values();
		$settings = $this->migrate_list_id( $settings );
		if ( ! empty( $settings ) && ! empty( $settings[ $multi_id ] ) && ! empty( $settings[ $multi_id ]['list_id'] ) ) {
			return $settings[ $multi_id ]['list_id'];
		}
		return '';
	}

	/**
	 * Setup Connection Name
	 *
	 * @param array $submitted_data Submitted data.
	 * @return array
	 * @throws Forminator_Integration_Exception Throws Integration Exception.
	 */
	public function map_fields( $submitted_data ) {
		$template = forminator_addon_hubspot_dir() . 'views/settings/create-contact.php';

		$multi_id = $this->generate_multi_id();
		if ( isset( $submitted_data['multi_id'] ) ) {
			$multi_id = $submitted_data['multi_id'];
			unset( $submitted_data['multi_id'] );
		}

		$lists                        = array();
		$property                     = array();
		$form_fields                  = $this->get_fields_for_type();
		$forminator_field_element_ids = wp_list_pluck( $form_fields, 'element_id' );

		$fields          = array(
			'email'     => esc_html__( 'Email Address', 'forminator' ),
			'firstname' => esc_html__( 'First Name', 'forminator' ),
			'lastname'  => esc_html__( 'Last Name', 'forminator' ),
			'jobtitle'  => esc_html__( 'Job Title', 'forminator' ),
		);
		$template_params = array(
			'fields_map'        => $this->get_multi_id_settings( $multi_id, 'fields_map', array() ),
			'error_message'     => '',
			'multi_id'          => $multi_id,
			'fields'            => $fields,
			'form_fields'       => $form_fields,
			'email_fields'      => $this->get_fields_for_type( 'email' ),
			'list_id'           => $this->get_list_id( $multi_id ),
			'list_name'         => $this->get_multi_id_settings( $multi_id, 'list_name' ),
			'custom_fields_map' => $this->get_multi_id_settings( $multi_id, 'custom_fields_map', array() ),
		);

		try {
			$api            = $this->addon->get_api();
			$lists_request  = $api->get_contact_list();
			$lists_property = $api->get_properties();

			if ( ! empty( $lists_request->lists ) ) {
				foreach ( $lists_request->lists as $key => $data ) {
					if ( isset( $data->listId ) && isset( $data->name ) ) { //phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
						$lists[ $data->listId ] = $data->name; //phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
					}
				}
			}
			if ( ! empty( $lists_property ) ) {
				foreach ( $lists_property as $key => $data ) {
					if ( isset( $data->name ) ) {
						$property[ $data->name ] = $data->label;
					}
				}
			}
		} catch ( Forminator_Integration_Settings_Exception $e ) {
			$template_params['error_message'] = $e->getMessage();
			$has_errors                       = true;
		}

		$template_params['lists']      = $lists;
		$template_params['properties'] = $property;
		$is_submit                     = ! empty( $submitted_data );
		$has_errors                    = false;
		if ( $is_submit ) {
			$custom_property                      = $submitted_data['custom_property'] ?? array();
			$custom_field                         = $submitted_data['custom_field'] ?? array();
			$custom_field_map                     = array_combine( $custom_property, $custom_field );
			$fields_map                           = $submitted_data['fields_map'] ?? array();
			$template_params['fields_map']        = $fields_map;
			$template_params['custom_fields_map'] = $custom_field_map;

			try {
				$input_exceptions = new Forminator_Integration_Settings_Exception();
				if ( empty( $fields_map['email'] ) ) {
					$input_exceptions->add_input_exception( 'Please assign field for Email Address', 'email_error' );
				}
				foreach ( $fields as $key => $title ) {
					if ( ! empty( $fields_map[ $key ] ) ) {
						$element_id = $fields_map[ $key ];
						if ( ! in_array( $element_id, $forminator_field_element_ids, true ) ) {
							$input_exceptions->add_input_exception(
								sprintf(
								/* translators: %s: Field title */
									esc_html__( 'Please assign valid field for %s', 'forminator' ),
									esc_html( $title )
								),
								$key . '_error'
							);
							continue;
						}
					}
				}

				if ( $input_exceptions->input_exceptions_is_available() ) {
					throw $input_exceptions;
				}

				$list_id = $submitted_data['list_id'] ?? '';

				$list_name = $lists[ $list_id ] ?? '';

				$this->save_multi_id_setting_values(
					$multi_id,
					array(
						'fields_map'        => $fields_map,
						'custom_fields_map' => $custom_field_map,
						'list_id'           => $list_id,
						'list_name'         => $list_name,
						'list_api_version'  => 'v3',
					)
				);

			} catch ( Forminator_Integration_Settings_Exception $e ) {
				$template_params = array_merge( $template_params, $e->get_input_exceptions() );
				$has_errors      = true;
			} catch ( Forminator_Integration_Exception $e ) {
				$template_params['error_message'] = $e->getMessage();
				$has_errors                       = true;
			}
		}

		$buttons = array();
		if ( $this->map_fields_is_completed( array( 'multi_id' => $multi_id ) ) ) {
			$buttons['disconnect']['markup'] = Forminator_Integration::get_button_markup(
				esc_html__( 'Deactivate', 'forminator' ),
				'sui-button-ghost sui-tooltip sui-tooltip-top-center forminator-addon-form-disconnect',
				esc_html__( 'Deactivate this HubSpot Integration from this Form.', 'forminator' )
			);
		}

		$buttons['next']['markup'] = '<div class="sui-actions-right">' .
			Forminator_Integration::get_button_markup( esc_html__( 'Continue', 'forminator' ), 'forminator-addon-next' ) .
			'</div>';

		return array(
			'html'       => Forminator_Integration::get_template( $template, $template_params ),
			'buttons'    => $buttons,
			'size'       => 'normal',
			'redirect'   => false,
			'has_errors' => $has_errors,
		);
	}

	/**
	 * Check if pick name step completed
	 *
	 * @since 1.0 HubSpot Integration
	 * @param array $submitted_data Submitted data.
	 * @return bool
	 */
	public function map_fields_is_completed( $submitted_data ) {
		$multi_id = $submitted_data['multi_id'] ?? '';

		if ( empty( $multi_id ) ) {
			return false;
		}

		$fields_map = $this->get_multi_id_settings( $multi_id, 'fields_map', array() );

		if ( empty( $fields_map ) || ! is_array( $fields_map ) || count( $fields_map ) < 1 ) {
			return false;
		}

		if ( empty( $fields_map['email'] ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Setup Create ticket
	 *
	 * @param array $submitted_data Submitted data.
	 * @return array
	 * @throws Forminator_Integration_Exception Throws Integration Exception.
	 */
	public function create_ticket( $submitted_data ) {
		$template = forminator_addon_hubspot_dir() . 'views/create-ticket.php';

		if ( ! isset( $submitted_data['multi_id'] ) ) {
			return $this->get_force_closed_wizard();
		}

		$multi_id = $submitted_data['multi_id'];

		$is_poll     = false;
		$file_fields = $this->get_fields_for_type( 'upload' );

		$fields = wp_list_pluck( $this->form_fields, 'field_label', 'element_id' );
		if ( 'quiz' === static::$module_slug ) {
			$fields = array_merge(
				array(
					'{quiz_name}' => __( 'Quiz Name', 'forminator' ),
				),
				$fields
			);
		}

		$template_params = array(
			'fields'             => $fields,
			'file_fields'        => $file_fields,
			'create_ticket'      => $this->get_multi_id_settings( $multi_id, 'create_ticket' ),
			'pipeline_id'        => $this->get_multi_id_settings( $multi_id, 'pipeline_id' ),
			'pipeline_name'      => $this->get_multi_id_settings( $multi_id, 'pipeline_name' ),
			'status_id'          => $this->get_multi_id_settings( $multi_id, 'status_id' ),
			'status_name'        => $this->get_multi_id_settings( $multi_id, 'status_name' ),
			'ticket_name'        => $this->get_multi_id_settings( $multi_id, 'ticket_name' ),
			'ticket_description' => $this->get_multi_id_settings( $multi_id, 'ticket_description' ),
			'supported_file'     => $this->get_multi_id_settings( $multi_id, 'supported_file' ),
			'list_id_error'      => '',
			'multi_id'           => $multi_id,
			'error_message'      => '',
			'auth_url'           => $this->addon->get_auth_url(),
		);

		$settings = $this->addon->get_settings_values();
		if ( ! empty( $settings['token'] ) ) {
			$template_params['token'] = $settings['token'];
		}

		$button_id = 'ticket-activate';
		if ( ! empty( $settings['re-authorize'] ) ) {
			$template_params['re-authorize'] = $settings['re-authorize'];
			$button_id                       = 'ticket-activated';
		}

		if ( ( isset( $submitted_data['create_ticket'] ) && '1' === $submitted_data['create_ticket'] ) && empty( $settings['re-authorize'] ) ) {
			$is_poll = true;
		}

		unset( $submitted_data['multi_id'] );

		$is_submit    = ! empty( $submitted_data );
		$has_errors   = false;
		$notification = array();
		$is_close     = false;

		$pipeline = array();
		$status   = array();

		try {

			$api              = $this->addon->get_api();
			$pipeline_request = $api->get_pipeline();
			if ( ! empty( $submitted_data['pipeline_id'] ) ) {
				$pipeline_id = $submitted_data['pipeline_id'];
			} else {
				$pipeline_id = $this->get_multi_id_settings( $multi_id, 'pipeline_id', 0 );
			}
			if ( ! empty( $pipeline_request->results ) ) {
				$default_status = reset( $pipeline_request->results );
				foreach ( $pipeline_request->results as $key => $data ) {
					if ( isset( $data->pipelineId ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
						$pipeline[ $data->pipelineId ] = $data->label; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
						if ( $pipeline_id === $data->pipelineId ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
							$pipeline_status = $data->stages;
						} else {
							$pipeline_status = $default_status->stages;
						}
						if ( ! empty( $pipeline_status ) ) {
							foreach ( $pipeline_status as $s => $stat ) {
								if ( isset( $stat->stageId ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
									$status[ $stat->stageId ] = $stat->label; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
								}
							}
						}
					}
				}
			}
			if ( empty( $pipeline ) ) {
				throw new Forminator_Integration_Exception( esc_html__( 'No pipeline found on your HubSpot account. Please create one.', 'forminator' ) );
			}

			if ( empty( $status ) ) {
				throw new Forminator_Integration_Exception( esc_html__( 'No status found on your HubSpot account. Please create one.', 'forminator' ) );
			}

			$template_params['status']   = $status;
			$template_params['pipeline'] = $pipeline;

		} catch ( Forminator_Integration_Exception $e ) {
			$template_params['error_message'] = $e->getMessage();
			$has_errors                       = true;
		}

		if ( $is_submit ) {
			$pipeline_id                           = isset( $submitted_data['pipeline_id'] ) ? $submitted_data['pipeline_id'] : null;
			$status_id                             = isset( $submitted_data['status_id'] ) ? $submitted_data['status_id'] : null;
			$ticket_name                           = isset( $submitted_data['ticket_name'] ) ? $submitted_data['ticket_name'] : '';
			$ticket_description                    = isset( $submitted_data['ticket_description'] ) ? $submitted_data['ticket_description'] : '';
			$create_ticket                         = isset( $submitted_data['create_ticket'] ) ? $submitted_data['create_ticket'] : '';
			$supported_file                        = isset( $submitted_data['supported_file'] ) ? $submitted_data['supported_file'] : '';
			$template_params['pipeline_id']        = $pipeline_id;
			$template_params['status_id']          = $status_id;
			$template_params['ticket_name']        = $ticket_name;
			$template_params['ticket_description'] = $ticket_description;
			$template_params['create_ticket']      = $create_ticket;
			$template_params['supported_file']     = $supported_file;
			try {

				$input_exceptions = new Forminator_Integration_Settings_Exception();
				if ( '1' === $create_ticket && isset( $submitted_data['pipeline_id'] ) && empty( $submitted_data['pipeline_id'] )
					&& 0 !== (int) $submitted_data['pipeline_id'] ) {
					$input_exceptions->add_input_exception( 'Please select pipeline', 'pipeline_error' );
				}
				if ( '1' === $create_ticket && isset( $submitted_data['status_id'] ) && empty( $submitted_data['status_id'] )
					&& 0 !== (int) $submitted_data['status_id'] ) {
					$input_exceptions->add_input_exception( 'Please select status', 'status_error' );
				}

				if ( '1' === $create_ticket && isset( $submitted_data['ticket_name'] ) && empty( $submitted_data['ticket_name'] ) ) {
					$input_exceptions->add_input_exception( 'Please enter ticket name', 'ticket_name_error' );
				}

				if ( $input_exceptions->input_exceptions_is_available() ) {
					throw $input_exceptions;
				}

				if ( empty( $create_ticket ) || ( '1' === $create_ticket && isset( $submitted_data['ticket_name'] ) ) ) {
					$pipeline_name = $pipeline[ $pipeline_id ];
					$status_name   = $status[ $status_id ];

					$this->save_multi_id_setting_values(
						$multi_id,
						array(
							'pipeline_id'        => $pipeline_id,
							'pipeline_name'      => $pipeline_name,
							'status_id'          => $status_id,
							'status_name'        => $status_name,
							'ticket_name'        => $ticket_name,
							'ticket_description' => $ticket_description,
							'create_ticket'      => $create_ticket,
							'supported_file'     => $supported_file,
						)
					);
					$notification = array(
						'type' => 'success',
						'text' => '<strong>' . $this->addon->get_title() . '</strong> ' . esc_html__( 'is activated successfully.', 'forminator' ),
					);
					$is_close     = true;
				}
			} catch ( Forminator_Integration_Settings_Exception $e ) {
				$template_params = array_merge( $template_params, $e->get_input_exceptions() );
				$has_errors      = true;
			} catch ( Forminator_Integration_Exception $e ) {
				$template_params['ticket_error'] = $e->getMessage();
				$has_errors                      = true;
			}
		}

		$buttons = array();
		if ( $this->create_ticket_is_completed( array( 'multi_id' => $multi_id ) ) ) {
			$buttons['disconnect']['markup'] = Forminator_Integration::get_button_markup(
				esc_html__( 'Deactivate', 'forminator' ),
				'sui-button-ghost sui-tooltip sui-tooltip-top-center forminator-addon-form-disconnect',
				esc_html__( 'Deactivate this HubSpot Integration from this Form.', 'forminator' )
			);
			$buttons['next']['markup']       = '<div class="sui-actions-right">' .
				Forminator_Integration::get_button_markup( esc_html__( 'Update', 'forminator' ), 'forminator-addon-next sui-button-blue' ) .
				'</div>';
		} elseif ( ( isset( $submitted_data['create_ticket'] ) && '1' === $submitted_data['create_ticket'] ) && empty( $settings['re-authorize'] ) ) {
			$buttons = array();
		} else {
			$buttons['next']['markup'] = '<div class="sui-actions-right" id="' . $button_id . '">' .
				Forminator_Integration::get_button_markup( esc_html__( 'Activate', 'forminator' ), 'forminator-addon-next sui-button-blue' ) .
				'</div>';
		}

		return array(
			'html'         => Forminator_Integration::get_template( $template, $template_params ),
			'buttons'      => $buttons,
			'size'         => 'reduced',
			'redirect'     => false,
			'has_errors'   => $has_errors,
			'has_back'     => true,
			'notification' => $notification,
			'is_close'     => $is_close,
			'is_poll'      => $is_poll,
		);
	}

	/**
	 * Check if setup list completed
	 *
	 * @since 1.0 HubSpot Integration
	 * @param array $submitted_data Submitted data.
	 * @return bool
	 */
	public function create_ticket_is_completed( $submitted_data ) {
		$multi_id = $submitted_data['multi_id'] ?? '';

		if ( empty( $multi_id ) ) {
			return false;
		}

		$create_ticket = $this->get_multi_id_settings( $multi_id, 'create_ticket' );
		$pipeline_id   = $this->get_multi_id_settings( $multi_id, 'pipeline_id' );
		$status_id     = $this->get_multi_id_settings( $multi_id, 'status_id' );
		$ticket_name   = $this->get_multi_id_settings( $multi_id, 'ticket_name' );

		if ( ( empty( $create_ticket ) || '1' === $create_ticket ) && empty( $pipeline_id ) && empty( $status_id ) && empty( $ticket_name ) ) {
			return false;
		}

		return true;
	}
}
