<?php
/**
 * Trait for common methods for Mailjet settings classes
 *
 * @since 1.30
 * @package Mailjet Integration
 */

/**
 * Trait Forminator_Mailjet_Settings_Trait
 */
trait Forminator_Mailjet_Settings_Trait {

	/**
	 * For settings Wizard steps
	 *
	 * @return array
	 */
	public function module_settings_wizards() {
		// Already filtered on Forminator_Integration::get_wizard.
		$this->addon_settings = $this->get_settings_values();
		// Numerical array steps.
		return array(
			// 1
			array(
				'callback'     => array( $this, 'choose_mail_list' ),
				'is_completed' => array( $this, 'step_choose_mail_list_is_completed' ),
			),
			// 2
			array(
				'callback'     => array( $this, 'get_map_fields' ),
				'is_completed' => array( $this, 'step_map_fields_is_completed' ),
			),
		);
	}

	/**
	 * Get mail list data
	 *
	 * @param array $submitted_data Submitted data.
	 * @return array
	 */
	private function mail_list_data( $submitted_data ) {
		$default_data = array(
			'mail_list_id' => '',
		);

		return $this->get_current_data( $default_data, $submitted_data );
	}

	/**
	 * Choose Mail wizard
	 *
	 * @param array $submitted_data Submitted data.
	 *
	 * @return array
	 */
	public function choose_mail_list( $submitted_data ) {
		$current_data = $this->mail_list_data( $submitted_data );

		$api_error  = '';
		$list_error = '';
		$lists      = array();

		try {
			$lists = $this->get_prepared_lists();

			if ( empty( $lists ) ) {
				$list_error = __( 'Your Mailjet List is empty, please create one.', 'forminator' );
			} elseif ( ! empty( $submitted_data ) ) {
				// logic when user submit mail list.
				$mail_list_name = $this->get_choosen_mail_list_name( $lists, $submitted_data );

				if ( empty( $mail_list_name ) ) {
					$list_error = __( 'Please select a valid Email List', 'forminator' );
				} else {
					$this->save_settings( $submitted_data, $mail_list_name );
				}
			}
		} catch ( Forminator_Integration_Exception $e ) {
			// send error back to client.
			$api_error = $e->get_error_notice();
		}

		$html = self::get_choose_list_header( $api_error );

		if ( ! $api_error ) {
			$html .= '<form enctype="multipart/form-data">';
			$html .= self::get_choose_list_field( $current_data, $lists, $list_error );
			$html .= '</form>';
		}

		return array(
			'html'       => $html,
			'redirect'   => false,
			'buttons'    => $this->get_choose_list_buttons( $api_error ),
			'has_errors' => ! empty( $api_error ) || ! empty( $list_error ),
			'size'       => 'small',
		);
	}

	/**
	 * Save submitted settings
	 *
	 * @param array  $submitted_data Submitted data.
	 * @param string $list_name List name.
	 */
	private function save_settings( $submitted_data, $list_name ) {
		$this->addon_settings['mail_list_id']   = $submitted_data['mail_list_id'];
		$this->addon_settings['mail_list_name'] = $list_name;

		$this->save_module_settings_values();
	}

	/**
	 * Get current data based on submitted or saved data
	 *
	 * @param array $current_data Default data.
	 * @param array $submitted_data Submitted data.
	 * @return array
	 */
	private function get_current_data( $current_data, $submitted_data ) {
		foreach ( array_keys( $current_data ) as $key ) {
			if ( isset( $submitted_data[ $key ] ) ) {
				$current_data[ $key ] = $submitted_data[ $key ];
			} elseif ( isset( $this->addon_settings[ $key ] ) ) {
				$current_data[ $key ] = $this->addon_settings[ $key ];
			}
		}

		forminator_addon_maybe_log( __METHOD__, 'current_data', $current_data );

		return $current_data;
	}

	/**
	 * Get HTML for buttons on Choose List step.
	 *
	 * @param string $api_error API error.
	 * @return array
	 */
	private function get_choose_list_buttons( $api_error ) {
		$buttons = array();

		if ( ! $api_error ) {
			if ( $this->addon->is_module_connected( $this->module_id, static::$module_slug ) ) {
				$buttons['disconnect']['markup'] = Forminator_Integration::get_button_markup(
					esc_html__( 'Deactivate', 'forminator' ),
					'sui-button-ghost sui-tooltip sui-tooltip-top-center forminator-addon-form-disconnect',
					esc_html__( 'Deactivate Mailjet from this module.', 'forminator' )
				);
			}

			$buttons['next']['markup'] = '<div class="sui-actions-right">' .
				Forminator_Integration::get_button_markup( esc_html__( 'Next', 'forminator' ), 'forminator-addon-next' ) .
				'</div>';
		}

		return $buttons;
	}

	/**
	 * Get additional integration fields.
	 *
	 * @return array
	 */
	protected function get_additional_addon_fields() {
		return array(
			// Add default fields.
			'email' => (object) array(
				'name'     => __( 'Email', 'forminator' ),
				'id'       => 'email',
				'type'     => 'email',
				'required' => true,
			),
			'name'  => (object) array(
				'name' => __( 'Name', 'forminator' ),
				'id'   => 'name',
				'type' => 'str',
			),
		);
	}

	/**
	 * Get mail List Name of submitted data
	 *
	 * @param array $lists Lists.
	 * @param array $submitted_data Submitted data.
	 *
	 * @return string
	 */
	private function get_choosen_mail_list_name( $lists, $submitted_data ) {
		forminator_addon_maybe_log( __METHOD__, '$submitted_data', $submitted_data );
		$mail_list_id   = $submitted_data['mail_list_id'] ?? 0;
		$mail_list_name = $lists[ $mail_list_id ] ?? '';
		forminator_addon_maybe_log( __METHOD__, '$mail_list_name', $mail_list_name );

		return $mail_list_name;
	}

	/**
	 * Check if map fields is completed
	 *
	 * @return bool
	 */
	public function step_map_fields_is_completed() {
		$this->addon_settings = $this->get_settings_values();
		if ( ! $this->step_choose_mail_list_is_completed() ) {
			return false;
		}

		if ( empty( $this->addon_settings['fields_map'] ) ) {
			return false;
		}

		if ( ! is_array( $this->addon_settings['fields_map'] ) ) {
			return false;
		}

		if ( count( $this->addon_settings['fields_map'] ) < 1 ) {
			return false;
		}

		/**
		 * TODO: check if saved fields_map still valid, by request merge_fields on mailjet
		 * Easy achieved but will add overhead on site
		 * force_form_disconnect();
		 * save_force_form_disconnect_reason();
		 */

		return true;
	}

	/**
	 * Check if mail list already selected completed
	 *
	 * @return bool
	 */
	public function step_choose_mail_list_is_completed() {
		$this->addon_settings = $this->get_settings_values();
		if ( ! isset( $this->addon_settings['mail_list_id'] ) ) {
			// preliminary value.
			$this->addon_settings['mail_list_id'] = 0;

			return false;
		}

		if ( empty( $this->addon_settings['mail_list_id'] ) ) {
			return false;
		}

		/**
		 * TODO: check if saved mail list id still valid, by request info on mailjet
		 * Easy achieved but will add overhead on site
		 * force_form_disconnect();
		 * save_force_form_disconnect_reason();
		 */

		return true;
	}
}
