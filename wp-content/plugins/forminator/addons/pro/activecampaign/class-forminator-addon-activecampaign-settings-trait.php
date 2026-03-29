<?php
/**
 * The Activecampaign Settings Trait.
 *
 * @package    Forminator
 */

/**
 * Trait for common methods for Activecampaign settings classes
 *
 * @since 1.30
 * @package Activecampaign Integration
 */

/**
 * Trait Forminator_Activecampaign_Settings_Trait
 */
trait Forminator_Activecampaign_Settings_Trait {

	/**
	 * Custom Fields.
	 *
	 * @var Forminator_Activecampaign_CustomField
	 * @since 1.7 Activecampaign Custom Fields
	 */
	protected $custom_fields;

	/**
	 * Activecampaign Quiz Settings wizard
	 *
	 * @since 1.0 Activecampaign Integration
	 * @return array
	 */
	public function module_settings_wizards() {
		// numerical array steps.
		return array(
			array(
				'callback'     => array( $this, 'pick_name' ),
				'is_completed' => array( $this, 'setup_name_is_completed' ),
			),
			array(
				'callback'     => array( $this, 'select_list' ),
				'is_completed' => array( $this, 'select_list_is_completed' ),
			),
			array(
				'callback'     => array( $this, 'map_fields' ),
				'is_completed' => array( $this, 'map_fields_is_completed' ),
			),
			array(
				'callback'     => array( $this, 'setup_options' ),
				'is_completed' => array( $this, 'setup_options_is_completed' ),
			),
		);
	}


	/**
	 * Set up Connection Name
	 *
	 * @since 1.0 Activecampaign Integration
	 *
	 * @param array $submitted_data Submitted data.
	 *
	 * @return array
	 * @throws Forminator_Integration_Exception Integration Exception.
	 */
	public function pick_name( $submitted_data ) {
		$template = forminator_addon_activecampaign_dir() . 'views/module-settings/pick-name.php';

		$multi_id = $this->generate_multi_id();
		if ( isset( $submitted_data['multi_id'] ) ) {
			$multi_id = $submitted_data['multi_id'];
		}

		$template_params = array(
			'name'       => $this->get_multi_id_settings( $multi_id, 'name' ),
			'name_error' => '',
			'multi_id'   => $multi_id,
		);

		unset( $submitted_data['multi_id'] );

		$is_submit  = ! empty( $submitted_data );
		$has_errors = false;
		if ( $is_submit ) {
			$name                    = isset( $submitted_data['name'] ) ? $submitted_data['name'] : '';
			$template_params['name'] = $name;

			try {
				if ( empty( $name ) ) {
					throw new Forminator_Integration_Exception( esc_html__( 'Please pick valid name', 'forminator' ) );
				}

				$time_added = $this->get_multi_id_settings( $multi_id, 'time_added', time() );
				$this->save_multi_id_setting_values(
					$multi_id,
					array(
						'name'       => $name,
						'time_added' => $time_added,
					)
				);

			} catch ( Forminator_Integration_Exception $e ) {
				$template_params['name_error'] = $e->getMessage();
				$has_errors                    = true;
			}
		}

		$buttons = array();
		if ( $this->setup_name_is_completed( array( 'multi_id' => $multi_id ) ) ) {
			$buttons['disconnect']['markup'] = Forminator_Integration::get_button_markup(
				esc_html__( 'Deactivate', 'forminator' ),
				'sui-button-ghost sui-tooltip sui-tooltip-top-center forminator-addon-form-disconnect',
				esc_html__( 'Deactivate this ActiveCampaign Integration from this module.', 'forminator' )
			);
		}

		$buttons['next']['markup'] = '<div class="sui-actions-right">' .
			Forminator_Integration::get_button_markup( esc_html__( 'Next', 'forminator' ), 'forminator-addon-next' ) .
			'</div>';

		return array(
			'html'       => Forminator_Integration::get_template( $template, $template_params ),
			'buttons'    => $buttons,
			'redirect'   => false,
			'has_errors' => $has_errors,
		);
	}

	/**
	 * Set up Contact List
	 *
	 * @since 1.0 Activecampaign Integration
	 *
	 * @param array $submitted_data Submitted data.
	 *
	 * @return array
	 * @throws Forminator_Integration_Exception Integration Exception.
	 */
	public function select_list( $submitted_data ) {
		$template = forminator_addon_activecampaign_dir() . 'views/module-settings/select-list.php';

		if ( ! isset( $submitted_data['multi_id'] ) ) {
			return $this->get_force_closed_wizard();
		}

		$multi_id = $submitted_data['multi_id'];
		unset( $submitted_data['multi_id'] );

		$template_params = array(
			'list_id'       => $this->get_multi_id_settings( $multi_id, 'list_id' ),
			'list_id_error' => '',
			'multi_id'      => $multi_id,
			'error_message' => '',
			'lists'         => array(),
		);

		$is_submit  = ! empty( $submitted_data );
		$has_errors = false;

		$lists = array();

		try {

			$ac_api        = $this->addon->get_api();
			$lists_request = $ac_api->get_lists();
			foreach ( $lists_request as $key => $data ) {
				if ( isset( $data->id ) && isset( $data->name ) ) {
					$lists[ $data->id ] = $data->name;
				}
			}

			if ( empty( $lists ) ) {
				throw new Forminator_Integration_Exception( esc_html__( 'No lists found on your ActiveCampaign account. Please create one.', 'forminator' ) );
			}

			$template_params['lists'] = $lists;

		} catch ( Forminator_Integration_Exception $e ) {
			$template_params['error_message'] = $e->getMessage();
			$has_errors                       = true;
		}

		if ( $is_submit ) {
			$list_id                    = isset( $submitted_data['list_id'] ) ? $submitted_data['list_id'] : '';
			$template_params['list_id'] = $list_id;

			try {
				// phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
				if ( empty( $list_id ) || ! in_array( $list_id, array_keys( $lists ) ) ) {
					throw new Forminator_Integration_Exception( esc_html__( 'Please pick valid list', 'forminator' ) );
				}

				$list_name = $lists[ $list_id ];

				$this->save_multi_id_setting_values(
					$multi_id,
					array(
						'list_id'   => $list_id,
						'list_name' => $list_name,
					)
				);

			} catch ( Forminator_Integration_Exception $e ) {
				$template_params['list_id_error'] = $e->getMessage();
				$has_errors                       = true;
			}
		}

		$buttons = array();
		if ( $this->setup_name_is_completed( array( 'multi_id' => $multi_id ) ) ) {
			$buttons['disconnect']['markup'] = Forminator_Integration::get_button_markup(
				esc_html__( 'Deactivate', 'forminator' ),
				'sui-button-ghost sui-tooltip sui-tooltip-top-center forminator-addon-form-disconnect',
				esc_html__( 'Deactivate this ActiveCampaign Integration from this module.', 'forminator' )
			);
		}

		$buttons['next']['markup'] = '<div class="sui-actions-right">' .
			Forminator_Integration::get_button_markup( esc_html__( 'Next', 'forminator' ), 'forminator-addon-next' ) .
			'</div>';

		return array(
			'html'       => Forminator_Integration::get_template( $template, $template_params ),
			'buttons'    => $buttons,
			'redirect'   => false,
			'has_errors' => $has_errors,
			'has_back'   => true,
		);
	}

	/**
	 * Set up fields map
	 *
	 * @since 1.0 Activecampaign Integration
	 *
	 * @param array $submitted_data Submitted data.
	 *
	 * @return array
	 * @throws Forminator_Integration_Exception Integration Exception.
	 */
	public function map_fields( $submitted_data ) {
		$template = forminator_addon_activecampaign_dir() . 'views/module-settings/map-fields.php';

		if ( ! isset( $submitted_data['multi_id'] ) ) {
			return $this->get_force_closed_wizard();
		}

		$multi_id = $submitted_data['multi_id'];
		unset( $submitted_data['multi_id'] );

		// find type of email.
		$email_fields                 = $this->get_fields_for_type( 'email' );
		$form_fields                  = $this->get_fields_for_type();
		$forminator_field_element_ids = wp_list_pluck( $form_fields, 'element_id' );

		$module_fields = wp_list_pluck( $this->form_fields, 'field_label', 'element_id' );

		$template_params = array(
			'fields_map'    => $this->get_multi_id_settings( $multi_id, 'fields_map', array() ),
			'multi_id'      => $multi_id,
			'error_message' => '',
			'fields'        => array(),
			'module_fields' => $module_fields,
			'email_fields'  => wp_list_pluck( $email_fields, 'field_label', 'element_id' ),
		);

		$is_submit  = ! empty( $submitted_data );
		$has_errors = false;

		$fields = array(
			'email'      => esc_html__( 'Email Address', 'forminator' ),
			'first_name' => esc_html__( 'First Name', 'forminator' ),
			'last_name'  => esc_html__( 'Last Name', 'forminator' ),
			'phone'      => esc_html__( 'Phone', 'forminator' ),
			'orgname'    => esc_html__( 'Organization Name', 'forminator' ),
		);

		$list_id = $this->get_multi_id_settings( $multi_id, 'list_id', 0 );

		try {

			$ac_api        = $this->addon->get_api();
			$lists_request = $ac_api->get_lists();
			$custom_fields = array();

			foreach ( $lists_request as $list ) {
				if ( $list_id === $list->id ) {
					if ( ! empty( $list->fields ) ) {
						$custom_fields = $list->fields;
					}
				}
			}

			// get global fields assigned to the form as well as explecit field.
			if ( ! empty( $custom_fields ) ) {
				foreach ( $custom_fields as $custom_field ) {
					$fields[ $custom_field->id ] = $custom_field->title;
				}
			}

			$template_params['fields'] = $fields;

		} catch ( Forminator_Integration_Exception $e ) {
			$template_params['error_message'] = $e->getMessage();
			$has_errors                       = true;
		}

		if ( $is_submit ) {
			$fields_map                    = $submitted_data['fields_map'] ?? array();
			$template_params['fields_map'] = $fields_map;

			try {
				if ( empty( $fields_map ) ) {
					throw new Forminator_Integration_Exception( esc_html__( 'Please assign fields.', 'forminator' ) );
				}

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

				$this->save_multi_id_setting_values( $multi_id, array( 'fields_map' => $fields_map ) );

			} catch ( Forminator_Integration_Settings_Exception $e ) {
				$template_params = array_merge( $template_params, $e->get_input_exceptions() );
				$has_errors      = true;
			} catch ( Forminator_Integration_Exception $e ) {
				$template_params['error_message'] = $e->getMessage();
				$has_errors                       = true;
			}
		}

		$buttons = array();
		if ( $this->setup_name_is_completed( array( 'multi_id' => $multi_id ) ) ) {
			$buttons['disconnect']['markup'] = Forminator_Integration::get_button_markup(
				esc_html__( 'Deactivate', 'forminator' ),
				'sui-button-ghost sui-tooltip sui-tooltip-top-center forminator-addon-form-disconnect',
				esc_html__( 'Deactivate this ActiveCampaign Integration from this module.', 'forminator' )
			);
		}

		$buttons['next']['markup'] = '<div class="sui-actions-right">' .
			Forminator_Integration::get_button_markup( esc_html__( 'Next', 'forminator' ), 'forminator-addon-next' ) .
			'</div>';

		return array(
			'html'       => Forminator_Integration::get_template( $template, $template_params ),
			'buttons'    => $buttons,
			'size'       => 'normal',
			'redirect'   => false,
			'has_errors' => $has_errors,
			'has_back'   => true,
		);
	}

	/**
	 * Check if fields mapped
	 *
	 * @since 1.0 Activecampaign Integration
	 *
	 * @param array $submitted_data Submitted data.
	 *
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
	 * Set up options
	 *
	 * Contains :
	 * - Double opt-in module,
	 * - tags,
	 * - instant-responder,
	 * - send last broadcast
	 *
	 * @since 1.0 Activecampaign Integration
	 *
	 * @param array $submitted_data Submitted data.
	 *
	 * @return array
	 * @throws Forminator_Integration_Exception Integration Exception.
	 */
	public function setup_options( $submitted_data ) {
		$template = forminator_addon_activecampaign_dir() . 'views/module-settings/setup-options.php';

		if ( ! isset( $submitted_data['multi_id'] ) ) {
			return $this->get_force_closed_wizard();
		}

		$multi_id = $submitted_data['multi_id'];
		unset( $submitted_data['multi_id'] );

		$forminator_form_element_ids = array();
		foreach ( $this->form_fields as $field ) {
			$forminator_form_element_ids[ $field['element_id'] ] = $field;
		}

		$template_params = array(
			'multi_id'             => $multi_id,
			'error_message'        => '',
			'forms'                => array(),
			'double_opt_form_id'   => $this->get_multi_id_settings( $multi_id, 'double_opt_form_id' ),
			'instantresponders'    => $this->get_multi_id_settings( $multi_id, 'instantresponders', 0 ),
			'lastmessage'          => $this->get_multi_id_settings( $multi_id, 'lastmessage', 0 ),
			'tags_fields'          => array(),
			'tags_selected_fields' => array(),
		);

		$saved_tags = $this->get_multi_id_settings( $multi_id, 'tags', array() );

		if ( isset( $submitted_data['tags'] ) && is_array( $submitted_data['tags'] ) ) {
			$saved_tags = $submitted_data['tags'];

		}
		$tag_selected_fields = array();
		foreach ( $saved_tags as $key => $saved_tag ) {
			// using form data.
			if ( stripos( $saved_tag, '{' ) === 0
				&& stripos( $saved_tag, '}' ) === ( strlen( $saved_tag ) - 1 )
			) {
				$element_id = str_ireplace( '{', '', $saved_tag );
				$element_id = str_ireplace( '}', '', $element_id );
				if ( in_array( $element_id, array_keys( $forminator_form_element_ids ), true ) ) {
					$forminator_form_element_ids[ $element_id ]['field_label'] = $forminator_form_element_ids[ $element_id ]['field_label'] .
						' | ' . $forminator_form_element_ids[ $element_id ]['element_id'];
					$forminator_form_element_ids[ $element_id ]['element_id']  = '{' . $forminator_form_element_ids[ $element_id ]['element_id'] . '}';

					$tag_selected_fields[] = $forminator_form_element_ids[ $element_id ];
					// let this go, its already selected.
					unset( $forminator_form_element_ids[ $element_id ] );
				} else {
					// no more exist on element ids let it go.
					unset( $saved_tags[ $key ] );
				}
			} else { // free form type.
				$tag_selected_fields[] = array(
					'element_id'  => $saved_tag,
					'field_label' => $saved_tag,
				);
			}
		}

		$template_params['tags_fields']          = $forminator_form_element_ids;
		$template_params['tags_selected_fields'] = $tag_selected_fields;

		$is_submit    = ! empty( $submitted_data );
		$has_errors   = false;
		$notification = array();
		$is_close     = false;

		$forms = array();
		try {
			$api           = $this->addon->get_api();
			$forms_request = $api->get_forms();

			foreach ( $forms_request as $data ) {
				if ( isset( $data->id ) && isset( $data->name ) ) {
					$forms[ $data->id ] = $data->name;
				}
			}
		} catch ( Forminator_Integration_Exception $e ) {
			$forms = array();
		}

		$template_params['forms'] = $forms;

		if ( $is_submit ) {
			$double_opt_form_id = isset( $submitted_data['double_opt_form_id'] ) ? $submitted_data['double_opt_form_id'] : '';
			$instantresponders  = isset( $submitted_data['instantresponders'] ) ? (int) $submitted_data['instantresponders'] : 0;
			$lastmessage        = isset( $submitted_data['lastmessage'] ) ? (int) $submitted_data['lastmessage'] : 0;

			try {
				$input_exceptions = new Forminator_Integration_Settings_Exception();

				// possible different type intended.
				// phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
				if ( ! empty( $double_opt_form_id ) && ! in_array( $double_opt_form_id, array_keys( $forms ) ) ) {
					$input_exceptions->add_input_exception( esc_html__( 'Please pick valid ActiveCampaign module', 'forminator' ), 'double_opt_form_id_error' );
				}

				if ( $input_exceptions->input_exceptions_is_available() ) {
					throw $input_exceptions;
				}

				$this->save_multi_id_setting_values(
					$multi_id,
					array(
						'tags'               => $saved_tags,
						'double_opt_form_id' => $double_opt_form_id,
						'instantresponders'  => $instantresponders,
						'lastmessage'        => $lastmessage,
					)
				);

				$notification = array(
					'type' => 'success',
					'text' => '<strong>' . $this->addon->get_title() . '</strong> ' . esc_html__( 'Successfully connected to your module', 'forminator' ),
				);
				$is_close     = true;

			} catch ( Forminator_Integration_Settings_Exception $e ) {
				$template_params = array_merge( $template_params, $e->get_input_exceptions() );
				$has_errors      = true;
			} catch ( Forminator_Integration_Exception $e ) {
				$template_params['error_message'] = $e->getMessage();
				$has_errors                       = true;
			}
		}

		$buttons = array();
		if ( $this->setup_name_is_completed( array( 'multi_id' => $multi_id ) ) ) {
			$buttons['disconnect']['markup'] = Forminator_Integration::get_button_markup(
				esc_html__( 'Deactivate', 'forminator' ),
				'sui-button-ghost sui-tooltip sui-tooltip-top-center forminator-addon-form-disconnect',
				esc_html__( 'Deactivate this ActiveCampaign Integration from this module.', 'forminator' )
			);
		}

		$buttons['next']['markup'] = '<div class="sui-actions-right">' .
			Forminator_Integration::get_button_markup( esc_html__( 'Save', 'forminator' ), 'sui-button-primary forminator-addon-finish' ) .
			'</div>';

		return array(
			'html'         => Forminator_Integration::get_template( $template, $template_params ),
			'buttons'      => $buttons,
			'size'         => 'normal',
			'redirect'     => false,
			'has_errors'   => $has_errors,
			'has_back'     => true,
			'notification' => $notification,
			'is_close'     => $is_close,
		);
	}

	/**
	 * Check if set up options completed
	 *
	 * @since 1.0 Activecampaign Integration
	 *
	 * @param array $submitted_data Submitted data.
	 *
	 * @return bool
	 */
	public function setup_options_is_completed( $submitted_data ) {
		// all settings here are optional, so it can be marked as completed.
		return true;
	}
}
