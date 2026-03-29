<?php
/**
 * Trait for common methods for Trello settings classes
 *
 * @since 1.30
 * @package Trello Integration
 */

/**
 * Trait Forminator_Trello_Settings_Trait
 */
trait Forminator_Trello_Settings_Trait {

	/**
	 * Trello Module Settings wizard
	 *
	 * @since 1.0 Trello Integration
	 * @return array
	 */
	public function module_settings_wizards(): array {
		return array(
			array(
				'callback'     => array( $this, 'setup_name' ),
				'is_completed' => array( $this, 'setup_name_is_completed' ),
			),
			array(
				'callback'     => array( $this, 'setup_board' ),
				'is_completed' => array( $this, 'setup_board_is_completed' ),
			),
			array(
				'callback'     => array( $this, 'setup_list' ),
				'is_completed' => array( $this, 'select_list_is_completed' ),
			),
			array(
				'callback'     => array( $this, 'setup_card' ),
				'is_completed' => array( $this, 'setup_card_is_completed' ),
			),
		);
	}

	/**
	 * Setup Name
	 *
	 * @since 1.0 Trello Integration
	 *
	 * @param array $submitted_data Submitted data.
	 * @return array
	 * @throws Forminator_Integration_Exception Throws Integration Exception.
	 */
	public function setup_name( $submitted_data ) {
		$template = forminator_addon_trello_dir() . 'views/form-settings/setup-name.php';

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
				esc_html__( 'Deactivate this Trello Integration from this module.', 'forminator' )
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
	 * Setup Board
	 *
	 * @since 1.0 Trello Integration
	 *
	 * @param array $submitted_data Submitted data.
	 * @return array
	 * @throws Forminator_Integration_Exception Throws Integration Exception.
	 */
	public function setup_board( $submitted_data ) {
		$template = forminator_addon_trello_dir() . 'views/form-settings/setup-board.php';

		if ( ! isset( $submitted_data['multi_id'] ) ) {
			return $this->get_force_closed_wizard();
		}

		$multi_id = $submitted_data['multi_id'];
		unset( $submitted_data['multi_id'] );

		$template_params = array(
			'board_id'       => $this->get_multi_id_settings( $multi_id, 'board_id' ),
			'board_id_error' => '',
			'multi_id'       => $multi_id,
			'error_message'  => '',
			'boards'         => array(),
		);

		$is_submit  = ! empty( $submitted_data );
		$has_errors = false;

		$boards = array();

		try {

			$api            = $this->addon->get_api();
			$boards_request = $api->get_boards();

			foreach ( $boards_request as $key => $data ) {
				if ( isset( $data->id ) && isset( $data->name ) ) {
					$boards[ $data->id ] = $data->name;
				}
			}

			if ( empty( $boards ) ) {
				throw new Forminator_Integration_Exception( esc_html__( 'No board found on your Trello account. Please create one.', 'forminator' ) );
			}

			$template_params['boards'] = $boards;

		} catch ( Forminator_Integration_Exception $e ) {
			$template_params['error_message'] = $e->getMessage();
			$has_errors                       = true;
		}

		if ( $is_submit ) {
			$board_id                    = isset( $submitted_data['board_id'] ) ? $submitted_data['board_id'] : '';
			$template_params['board_id'] = $board_id;

			try {
				if ( empty( $board_id ) ) {
					throw new Forminator_Integration_Exception( esc_html__( 'Please pick valid board.', 'forminator' ) );
				}

				// phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
				if ( ! in_array( $board_id, array_keys( $boards ) ) ) {
					throw new Forminator_Integration_Exception( esc_html__( 'Please pick valid board.', 'forminator' ) );
				}

				$board_name = $boards[ $board_id ];

				$this->save_multi_id_setting_values(
					$multi_id,
					array(
						'board_id'   => $board_id,
						'board_name' => $board_name,
					)
				);

			} catch ( Forminator_Integration_Exception $e ) {
				$template_params['board_id_error'] = $e->getMessage();
				$has_errors                        = true;
			}
		}

		$buttons = array();
		if ( $this->setup_name_is_completed( array( 'multi_id' => $multi_id ) ) ) {
			$buttons['disconnect']['markup'] = Forminator_Integration::get_button_markup(
				esc_html__( 'Deactivate', 'forminator' ),
				'sui-button-ghost sui-tooltip sui-tooltip-top-center forminator-addon-form-disconnect',
				esc_html__( 'Deactivate this Trello Integration from this module.', 'forminator' )
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
	 * Check if setup board is completed
	 *
	 * @since 1.0 Trello Integration
	 * @param array $submitted_data Submitted data.
	 * @return bool
	 */
	public function setup_board_is_completed( $submitted_data ) {
		return $this->if_properties_exist( $submitted_data, 'board_id' );
	}
	/**
	 * Setup List on Board
	 *
	 * @since 1.0 Trello Integration
	 * @param array $submitted_data Submitted data.
	 * @return array
	 * @throws Forminator_Integration_Exception Throws Integration Exception.
	 */
	public function setup_list( $submitted_data ) {
		$template = forminator_addon_trello_dir() . 'views/form-settings/setup-list.php';

		if ( ! isset( $submitted_data['multi_id'] ) ) {
			return $this->get_force_closed_wizard();
		}

		$multi_id = $submitted_data['multi_id'];
		unset( $submitted_data['multi_id'] );

		// todo: validate this, step wizard back if needed.
		$board_name = $this->get_multi_id_settings( $multi_id, 'board_name' );
		$board_id   = $this->get_multi_id_settings( $multi_id, 'board_id' );

		$template_params = array(
			'list_id'       => $this->get_multi_id_settings( $multi_id, 'list_id' ),
			'list_id_error' => '',
			'board_name'    => $board_name,
			'multi_id'      => $multi_id,
			'error_message' => '',
			'lists'         => array(),
		);

		$is_submit  = ! empty( $submitted_data );
		$has_errors = false;

		$lists = array();

		try {

			$api          = $this->addon->get_api();
			$list_request = $api->get_board_lists( $board_id );

			foreach ( $list_request as $key => $data ) {
				if ( isset( $data->id ) && isset( $data->name ) ) {
					$lists[ $data->id ] = $data->name;
				}
			}

			if ( empty( $lists ) ) {
				throw new Forminator_Integration_Exception(
					sprintf(
						/* translators: 1: Board name */
						esc_html__( 'No list found on Trello Board of %1$s. Please create one.', 'forminator' ),
						esc_html( $board_name )
					)
				);
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
				if ( empty( $list_id ) ) {
					throw new Forminator_Integration_Exception( esc_html__( 'Please pick valid list.', 'forminator' ) );
				}

				// phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
				if ( ! in_array( $list_id, array_keys( $lists ) ) ) {
					throw new Forminator_Integration_Exception( esc_html__( 'Please pick valid list.', 'forminator' ) );
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
				esc_html__( 'Deactivate this Trello Integration from this module.', 'forminator' )
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
	 * Setup Card
	 *
	 * @since 1.0
	 *
	 * @param array $submitted_data Submitted data.
	 * @return array
	 * @throws Forminator_Integration_Exception Throws Integration Exception.
	 */
	public function setup_card( $submitted_data ) {
		$template = forminator_addon_trello_dir() . 'views/' . static::$module_slug . '-settings/setup-card.php';

		if ( ! isset( $submitted_data['multi_id'] ) ) {
			return $this->get_force_closed_wizard();
		}

		$multi_id = $submitted_data['multi_id'];
		unset( $submitted_data['multi_id'] );

		$positions = array(
			'top'    => esc_html__( 'Top', 'forminator' ),
			'bottom' => esc_html__( 'Bottom', 'forminator' ),
		);

		// todo: validate this, step wizard back if needed.
		$board_id = $this->get_multi_id_settings( $multi_id, 'board_id' );

		$template_params = array(
			'card_name'              => $this->get_multi_id_settings( $multi_id, 'card_name', esc_html__( 'New submission from {quiz_name}', 'forminator' ) ),
			'card_name_error'        => '',
			'card_description'       => $this->get_multi_id_settings( $multi_id, 'card_description', "{quiz_answer}\n{quiz_result}" ),
			'card_description_error' => '',
			'due_date'               => $this->get_multi_id_settings( $multi_id, 'due_date' ),
			'position'               => $this->get_multi_id_settings( $multi_id, 'position', 'bottom' ),
			'position_error'         => '',
			'positions'              => $positions,
			'label_ids'              => $this->get_multi_id_settings( $multi_id, 'label_ids', array() ),
			'label_ids_error'        => '',
			'labels'                 => array(),
			'member_ids'             => $this->get_multi_id_settings( $multi_id, 'member_ids', array() ),
			'member_ids_error'       => '',
			'members'                => array(),
			'error_message'          => '',
			'multi_id'               => $multi_id,
			'list_name'              => $this->get_multi_id_settings( $multi_id, 'list_name' ),
		);

		if ( 'form' === static::$module_slug ) {
			$template_params['card_name']        = $this->get_multi_id_settings( $multi_id, 'card_name', esc_html__( 'New submission from {form_name}', 'forminator' ) );
			$template_params['card_description'] = $this->get_multi_id_settings( $multi_id, 'card_description', '{all_fields}' );
			$template_params['fields']           = $this->form_fields;
		} elseif ( 'poll' === static::$module_slug ) {
			$template_params['card_name']        = $this->get_multi_id_settings( $multi_id, 'card_name', 'New votes from {poll_name}' );
			$template_params['card_description'] = $this->get_multi_id_settings( $multi_id, 'card_description', "{poll_answer}\n{poll_result}" );
			$var_list                            = array_merge( array( 'poll_name' => esc_html__( 'Poll Name', 'forminator' ) ), forminator_get_vars() );
			unset( $var_list['custom_value'] );
			$template_params['name_fields'] = $var_list;
			$template_params['desc_fields'] = array_merge( forminator_get_poll_vars(), $var_list );
		} elseif ( 'quiz' === static::$module_slug ) {
			$template_params['card_name']        = $this->get_multi_id_settings( $multi_id, 'card_name', esc_html__( 'New submission from {quiz_name}', 'forminator' ) );
			$template_params['card_description'] = $this->get_multi_id_settings( $multi_id, 'card_description', "{quiz_answer}\n{quiz_result}" );
			$template_params['lead_fields']      = array();
			$var_list                            = array_merge( array( 'quiz_name' => esc_html__( 'Quiz Name', 'forminator' ) ), forminator_get_vars() );
			unset( $var_list['custom_value'], $var_list['query'] );
			$template_params['name_fields'] = $var_list;
			$template_params['desc_fields'] = array_merge( forminator_get_quiz_vars(), $var_list );

			if ( isset( $this->quiz_settings['hasLeads'] ) && $this->quiz_settings['hasLeads'] ) {
				$template_params['lead_fields'] = $this->form_fields;
			}
		}

		$is_submit    = ! empty( $submitted_data );
		$has_errors   = false;
		$is_close     = false;
		$notification = array();

		$labels  = array();
		$members = array();

		try {
			// get available labels.
			$api            = $this->addon->get_api();
			$labels_request = $api->get_board_labels( $board_id );

			foreach ( $labels_request as $data ) {
				if ( isset( $data->id ) ) {
					$name = $data->color;
					if ( ! empty( $data->name ) ) {
						$name = $data->name;
					}
					$labels[ $data->id ] = array(
						'name'  => $name,
						'color' => $data->color,
					);
				}
			}

			// get available members.
			$members_request = $api->get_board_members( $board_id );

			foreach ( $members_request as $data ) {
				if ( isset( $data->id ) && isset( $data->username ) ) {
					$display_name = $data->username;
					// its from API var.
					// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
					if ( ! empty( $data->fullName ) ) {
						// its from API var.
						// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
						$display_name = $data->fullName;
					}
					$members[ $data->id ] = $display_name;
				}
			}
		} catch ( Forminator_Integration_Exception $e ) {
			$template_params['error_message'] = $e->getMessage();
			$has_errors                       = true;
		}

		$template_params['labels']  = $labels;
		$template_params['members'] = $members;

		if ( $is_submit ) {
			$card_name                    = isset( $submitted_data['card_name'] ) ? trim( $submitted_data['card_name'] ) : '';
			$template_params['card_name'] = $card_name;

			$card_description                    = isset( $submitted_data['card_description'] ) ? trim( $submitted_data['card_description'] ) : '';
			$template_params['card_description'] = $card_description;

			$position                    = isset( $submitted_data['position'] ) ? $submitted_data['position'] : '';
			$template_params['position'] = $position;

			$label_ids                    = isset( $submitted_data['label_ids'] ) ? $submitted_data['label_ids'] : array();
			$template_params['label_ids'] = $label_ids;

			$member_ids                    = isset( $submitted_data['member_ids'] ) ? $submitted_data['member_ids'] : array();
			$template_params['member_ids'] = $member_ids;

			$due_date                    = isset( $submitted_data['due_date'] ) ? $submitted_data['due_date'] : '';
			$template_params['due_date'] = $due_date;

			try {
				$input_exceptions = new Forminator_Integration_Settings_Exception();

				if ( empty( $card_name ) ) {
					$input_exceptions->add_input_exception( 'Please specify card name.', 'card_name_error' );
				}

				if ( empty( $card_description ) ) {
					$input_exceptions->add_input_exception( 'Please specify card description.', 'card_description_error' );
				}

				if ( empty( $position ) ) {
					$input_exceptions->add_input_exception( 'Please specify position.', 'position_error' );
				}

				if ( ! in_array( $position, array_keys( $positions ), true ) ) {
					$input_exceptions->add_input_exception( 'Please pick valid position.', 'position_error' );
				}

				// optional label.
				if ( ! empty( $label_ids ) && is_array( $label_ids ) ) {
					$labels_keys = array_keys( $labels );
					foreach ( $label_ids as $label_id ) {
						// phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
						if ( ! in_array( $label_id, $labels_keys ) ) {
							$input_exceptions->add_input_exception( 'Please pick valid label.', 'label_ids_error' );
						}
					}
				} else {
					$label_ids = array();
				}

				// optional member.
				if ( ! empty( $member_ids ) && is_array( $member_ids ) ) {
					$members_keys = array_keys( $members );
					foreach ( $member_ids as $member_id ) {
						// phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
						if ( ! in_array( $member_id, $members_keys ) ) {
							$input_exceptions->add_input_exception( 'Please pick valid member.', 'member_ids_error' );
						}
					}
				} else {
					$member_ids = array();
				}

				if ( $input_exceptions->input_exceptions_is_available() ) {
					throw $input_exceptions;
				}

				$this->save_multi_id_setting_values(
					$multi_id,
					array(
						'card_name'        => $card_name,
						'card_description' => $card_description,
						'position'         => $position,
						'label_ids'        => $label_ids,
						'member_ids'       => $member_ids,
						'due_date'         => $due_date,
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
				esc_html__( 'Deactivate this Trello Integration from this module.', 'forminator' )
			);
		}

		$buttons['next']['markup'] = '<div class="sui-actions-right">' .
			Forminator_Integration::get_button_markup( esc_html__( 'Save', 'forminator' ), 'sui-button-primary forminator-addon-finish' ) .
			'</div>';

		return array(
			'html'         => Forminator_Integration::get_template( $template, $template_params ),
			'buttons'      => $buttons,
			'redirect'     => false,
			'has_errors'   => $has_errors,
			'has_back'     => true,
			'size'         => 'normal',
			'is_close'     => $is_close,
			'notification' => $notification,
		);
	}

	/**
	 * Check if card completed
	 *
	 * @since 1.0 Trello Integration
	 * @param array $submitted_data Submitted data.
	 * @return bool
	 */
	public function setup_card_is_completed( $submitted_data ) {
		return $this->if_properties_exist( $submitted_data, array( 'card_name', 'card_description' ) );
	}
}
