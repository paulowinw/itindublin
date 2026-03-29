<?php
/**
 * Trait for common methods for Slack settings classes
 *
 * @since 1.30
 * @package Slack Integration
 */

/**
 * Trait Forminator_Slack_Settings_Trait
 */
trait Forminator_Slack_Settings_Trait {

	/**
	 * Target array
	 *
	 * @var array
	 */
	public $target_types = array();

	/**
	 * Forminator_Slack_Form_Settings constructor.
	 *
	 * @since 1.0 Slack Integration
	 *
	 * @param Forminator_Integration $addon Forminator Integration Addon.
	 * @param int                    $form_id Form Id.
	 */
	public function __construct( Forminator_Integration $addon, $form_id ) {
		parent::__construct( $addon, $form_id );

		$this->target_types = array(
			Forminator_Slack::TARGET_TYPE_PUBLIC_CHANNEL  => esc_html__( 'Public Channel', 'forminator' ),
			Forminator_Slack::TARGET_TYPE_PRIVATE_CHANNEL => esc_html__( 'Private Channel', 'forminator' ),
			Forminator_Slack::TARGET_TYPE_DIRECT_MESSAGE  => esc_html__( 'Direct Message', 'forminator' ),
		);
	}

	/**
	 * Slack Module Settings wizard
	 *
	 * @since 1.0 Slack Integration
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
				'callback'     => array( $this, 'select_type' ),
				'is_completed' => array( $this, 'select_type_is_completed' ),
			),
			array(
				'callback'     => array( $this, 'select_target' ),
				'is_completed' => array( $this, 'select_target_is_completed' ),
			),
			array(
				'callback'     => array( $this, 'setup_message' ),
				'is_completed' => array( $this, 'setup_message_is_completed' ),
			),
		);
	}

	/**
	 * Setup Connection Name
	 *
	 * @since 1.0 Slack Integration
	 * @param array $submitted_data Submitted data.
	 * @return array
	 * @throws Forminator_Integration_Exception Throws Integration Exception.
	 */
	public function pick_name( $submitted_data ) {
		$template = forminator_addon_slack_dir() . 'views/module/pick-name.php';

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
				esc_html__( 'Deactivate this Slack Integration from this module.', 'forminator' )
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
	 * Select Message Type
	 *
	 * @since 1.0 Slack Integration
	 * @param array $submitted_data Submitted data.
	 * @return array
	 * @throws Forminator_Integration_Exception Throws Integration Exception.
	 */
	public function select_type( $submitted_data ) {
		$template = forminator_addon_slack_dir() . 'views/module/select-type.php';

		if ( ! isset( $submitted_data['multi_id'] ) ) {
			return $this->get_force_closed_wizard();
		}

		$multi_id = $submitted_data['multi_id'];
		unset( $submitted_data['multi_id'] );

		$template_params = array(
			'type'          => $this->get_multi_id_settings( $multi_id, 'type' ),
			'type_error'    => '',
			'multi_id'      => $multi_id,
			'error_message' => '',
		);

		$is_submit  = ! empty( $submitted_data );
		$has_errors = false;

		$types                    = $this->target_types;
		$template_params['types'] = $types;

		if ( $is_submit ) {
			$type                    = isset( $submitted_data['type'] ) ? $submitted_data['type'] : '';
			$template_params['type'] = $type;

			try {

				if ( empty( $type ) ) {
					throw new Forminator_Integration_Exception( esc_html__( 'Please pick valid type', 'forminator' ) );
				}

				if ( ! in_array( $type, array_keys( $types ), true ) ) {
					throw new Forminator_Integration_Exception( esc_html__( 'Please pick valid type', 'forminator' ) );
				}

				$this->save_multi_id_setting_values(
					$multi_id,
					array(
						'type' => $type,
					)
				);

			} catch ( Forminator_Integration_Exception $e ) {
				$template_params['type_error'] = $e->getMessage();
				$has_errors                    = true;
			}
		}

		$buttons = array();
		if ( $this->setup_name_is_completed( array( 'multi_id' => $multi_id ) ) ) {
			$buttons['disconnect']['markup'] = Forminator_Integration::get_button_markup(
				esc_html__( 'Deactivate', 'forminator' ),
				'sui-button-ghost sui-tooltip sui-tooltip-top-center forminator-addon-form-disconnect',
				esc_html__( 'Deactivate this Slack Integration from this module.', 'forminator' )
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
	 * Check if select type completed
	 *
	 * @since 1.0 Slack Integration
	 * @param array $submitted_data Submitted data.
	 * @return bool
	 */
	public function select_type_is_completed( $submitted_data ) {
		$multi_id = $submitted_data['multi_id'] ?? '';

		if ( empty( $multi_id ) ) {
			return false;
		}

		$type = $this->get_multi_id_settings( $multi_id, 'type' );

		if ( empty( $type ) ) {
			return false;
		}

		$types = $this->target_types;
		if ( ! in_array( $type, array_keys( $types ), true ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Select Target
	 *
	 * @since 1.0 Slack Integration
	 * @param array $submitted_data Submitted data.
	 * @return array
	 * @throws Forminator_Integration_Exception Throws Integration Exception.
	 */
	public function select_target( $submitted_data ) {
		$template = forminator_addon_slack_dir() . 'views/module/select-target.php';
		if ( ! isset( $submitted_data['multi_id'] ) ) {
			return $this->get_force_closed_wizard();
		}

		$multi_id = $submitted_data['multi_id'];
		unset( $submitted_data['multi_id'] );

		$template_params = array(
			'target_id'       => $this->get_multi_id_settings( $multi_id, 'target_id' ),
			'target_id_error' => '',
			'multi_id'        => $multi_id,
			'error_message'   => '',
			'targets'         => array(),
			'help_message'    => '',
		);

		$type = $this->get_multi_id_settings( $multi_id, 'type' );
		switch ( $type ) {
			case Forminator_Slack::TARGET_TYPE_PRIVATE_CHANNEL:
				$func_get_targets                = 'get_groups_list';
				$key_to_walk                     = 'channels';
				$template_params['help_message'] = esc_html__( 'Select which Slack private group / channel this feed will post a message to.', 'forminator' );
				break;
			case Forminator_Slack::TARGET_TYPE_DIRECT_MESSAGE:
				$func_get_targets                = 'get_users_list';
				$key_to_walk                     = 'members';
				$template_params['help_message'] = esc_html__( 'Select which Slack user this feed will post a message to.', 'forminator' );
				break;
			default:
				$func_get_targets                = 'get_channels_list';
				$key_to_walk                     = 'channels';
				$template_params['help_message'] = esc_html__( 'Select which Slack channel this feed will post a message to.', 'forminator' );
				break;
		}

		$is_submit  = ! empty( $submitted_data );
		$has_errors = false;

		$targets = array();

		try {

			$api             = $this->addon->get_api();
			$targets_request = call_user_func( array( $api, $func_get_targets ) );
			if ( ! is_object( $targets_request ) || ! isset( $targets_request->$key_to_walk ) || ! is_array( $targets_request->$key_to_walk ) || empty( $targets_request->$key_to_walk ) ) {
				throw new Forminator_Integration_Exception( esc_html__( 'No target found on your selected target type.', 'forminator' ) );
			}

			foreach ( $targets_request->$key_to_walk as $value ) {
				$targets[ $value->id ] = $value->name;
			}

			$template_params['targets'] = $targets;

		} catch ( Forminator_Integration_Exception $e ) {
			$template_params['error_message'] = $e->getMessage();
			$has_errors                       = true;
		}

		if ( $is_submit ) {
			$target_id                    = isset( $submitted_data['target_id'] ) ? $submitted_data['target_id'] : '';
			$template_params['target_id'] = $target_id;

			try {

				if ( empty( $target_id ) ) {
					throw new Forminator_Integration_Exception( esc_html__( 'Please pick valid target', 'forminator' ) );
				}

				if ( ! in_array( $target_id, array_keys( $targets ), true ) ) {
					throw new Forminator_Integration_Exception( esc_html__( 'Please pick valid target', 'forminator' ) );
				}

				$target_name = $targets[ $target_id ];

				$this->save_multi_id_setting_values(
					$multi_id,
					array(
						'target_id'   => $target_id,
						'target_name' => $target_name,
					)
				);

			} catch ( Forminator_Integration_Exception $e ) {
				$template_params['target_id_error'] = $e->getMessage();
				$has_errors                         = true;
			}
		}

		$buttons = array();
		if ( $this->setup_name_is_completed( array( 'multi_id' => $multi_id ) ) ) {
			$buttons['disconnect']['markup'] = Forminator_Integration::get_button_markup(
				esc_html__( 'Deactivate', 'forminator' ),
				'sui-button-ghost sui-tooltip sui-tooltip-top-center forminator-addon-form-disconnect',
				esc_html__( 'Deactivate this Slack Integration from this module.', 'forminator' )
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
	 * Check if select target completed
	 *
	 * @since 1.0 Slack Integration
	 * @param array $submitted_data Submitted data.
	 * @return bool
	 */
	public function select_target_is_completed( $submitted_data ) {
		return $this->if_properties_exist( $submitted_data, 'target_id' );
	}


	/**
	 * Setup Message
	 *
	 * @since 1.0 Slack Integration
	 * @param array $submitted_data Submitted data.
	 * @return array
	 * @throws Forminator_Integration_Exception Throws Integration Exception.
	 */
	public function setup_message( $submitted_data ) {
		$template = forminator_addon_slack_dir() . 'views/' . static::$module_slug . '-settings/setup-message.php';
		if ( ! isset( $submitted_data['multi_id'] ) ) {
			return $this->get_force_closed_wizard();
		}

		$multi_id = $submitted_data['multi_id'];
		unset( $submitted_data['multi_id'] );

		$template_params = array(
			'message_error' => '',
			'multi_id'      => $multi_id,
			'error_message' => '',
		);

		if ( 'form' === static::$module_slug ) {
			$template_params['message'] = $this->get_multi_id_settings( $multi_id, 'message', esc_html__( 'New submission from *{form_name}*', 'forminator' ) );
			$template_params['fields']  = $this->form_fields;
		} elseif ( 'poll' === static::$module_slug ) {
			$template_params['message'] = $this->get_multi_id_settings( $multi_id, 'message', 'New votes from *{poll_name}*' );
			$template_params['tags']    = array_merge( array( 'poll_name' => esc_html__( 'Poll Name', 'forminator' ) ), forminator_get_vars() );
		} elseif ( 'quiz' === static::$module_slug ) {
			$template_params['message']     = $this->get_multi_id_settings( $multi_id, 'message', 'New submissions from *{quiz_name}*' );
			$template_params['tags']        = array_merge( array( 'quiz_name' => esc_html__( 'Quiz Name', 'forminator' ) ), forminator_get_vars() );
			$template_params['lead_fields'] = ! empty( $this->quiz_settings['hasLeads'] ) ? $this->form_fields : array();
		}

		$is_submit    = ! empty( $submitted_data );
		$has_errors   = false;
		$notification = array();
		$is_close     = false;

		if ( $is_submit ) {
			$message                    = $submitted_data['message'] ?? '';
			$template_params['message'] = $message;

			try {

				if ( empty( $message ) ) {
					throw new Forminator_Integration_Exception( esc_html__( 'Please add a message', 'forminator' ) );
				}

				$this->save_multi_id_setting_values(
					$multi_id,
					array(
						'message' => $message,
					)
				);

				$notification = array(
					'type' => 'success',
					'text' => '<strong>' . $this->addon->get_title() . '</strong> ' . esc_html__( 'Successfully connected to your module', 'forminator' ),
				);
				$is_close     = true;

			} catch ( Forminator_Integration_Exception $e ) {
				$template_params['message_error'] = $e->getMessage();
				$has_errors                       = true;
			}
		}

		$buttons = array();
		if ( $this->setup_name_is_completed( array( 'multi_id' => $multi_id ) ) ) {
			$buttons['disconnect']['markup'] = Forminator_Integration::get_button_markup(
				esc_html__( 'Deactivate', 'forminator' ),
				'sui-button-ghost sui-tooltip sui-tooltip-top-center forminator-addon-form-disconnect',
				esc_html__( 'Deactivate this Slack Integration from this module.', 'forminator' )
			);
		}

		$buttons['next']['markup'] = '<div class="sui-actions-right">' .
			Forminator_Integration::get_button_markup( esc_html__( 'CONNECT', 'forminator' ), 'forminator-addon-next' ) .
			'</div>';

		return array(
			'html'         => Forminator_Integration::get_template( $template, $template_params ),
			'buttons'      => $buttons,
			'redirect'     => false,
			'has_errors'   => $has_errors,
			'has_back'     => true,
			'notification' => $notification,
			'is_close'     => $is_close,
			'size'         => 'normal',
		);
	}

	/**
	 * Check if setup message completed
	 *
	 * @since 1.0 Slack Integration
	 * @param array $submitted_data Submitted data.
	 * @return bool
	 */
	public function setup_message_is_completed( $submitted_data ) {
		return $this->if_properties_exist( $submitted_data, 'message' );
	}
}
