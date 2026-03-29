<?php
/**
 * Forminator Webhook form settings
 *
 * @package Forminator
 */

/**
 * Class Forminator_Webhook_Form_Settings
 * Handle how form settings displayed and saved
 */
class Forminator_Webhook_Form_Settings extends Forminator_Integration_Form_Settings {
	use Forminator_Webhook_Settings_Trait;

	/**
	 * Setup webhook url
	 *
	 * @param array $submitted_data Submitted data.
	 *
	 * @return array
	 */
	public function setup_webhook_url( $submitted_data ) {
		$this->addon_settings = $this->get_settings_values();

		$multi_id = $this->generate_multi_id();
		if ( isset( $submitted_data['multi_id'] ) ) {
			$multi_id = $submitted_data['multi_id'];
		}

		unset( $submitted_data['multi_id'] );

		$is_submit = ! empty( $submitted_data );

		$current_data = array(
			'webhook_url' => '',
			'name'        => '',
		);

		if ( isset( $submitted_data['name'] ) ) {
			$submitted_data['name'] = sanitize_text_field( $submitted_data['name'] );
		}
		forminator_addon_maybe_log( __METHOD__, $submitted_data );

		$notification = array();

		foreach ( $current_data as $key => $value ) {
			if ( isset( $submitted_data[ $key ] ) ) {
				$current_data[ $key ] = $submitted_data[ $key ];
			} elseif ( isset( $this->addon_settings[ $multi_id ][ $key ] ) ) {
				$current_data[ $key ] = $this->addon_settings[ $multi_id ][ $key ];
			}
		}

		$error_message        = '';
		$input_error_messages = '';

		try {
			if ( $is_submit ) {
				$input_exceptions = new Forminator_Integration_Settings_Exception();
				if ( empty( $current_data['name'] ) ) {
					$input_exceptions->add_input_exception( esc_html__( 'Please create a name for this Webhook integration', 'forminator' ), 'name' );
				}

				$this->validate_and_send_sample( $submitted_data, $input_exceptions );
				$this->addon_settings = array_merge(
					$this->addon_settings,
					array(
						$multi_id => array(
							'webhook_url' => $submitted_data['webhook_url'],
							'name'        => $submitted_data['name'],
						),
					)
				);

				$this->save_module_settings_values();
				$notification = array(
					'type' => 'success',
					'text' => '<strong>' . $this->addon->get_title() . ' [' . esc_html( $submitted_data['name'] ) . ']</strong> '
							. esc_html__( 'Successfully connected and sent sample data to your Webhook', 'forminator' ),
				);
			}
		} catch ( Forminator_Integration_Settings_Exception $e ) {
			$input_error_messages = $e->get_input_exceptions();
		} catch ( Forminator_Integration_Exception $e ) {
			$error_message = $e->get_error_notice();
		}

		$buttons = array();
		if ( $this->setup_webhook_url_is_completed( array( 'multi_id' => $multi_id ) ) ) {
			$buttons['disconnect']['markup'] = Forminator_Integration::get_button_markup(
				esc_html__( 'Deactivate', 'forminator' ),
				'sui-button-ghost sui-tooltip sui-tooltip-top-center forminator-addon-form-disconnect',
				esc_html__( 'Deactivate Webhook from this Form.', 'forminator' )
			);
		}

		$buttons['next']['markup'] = '<div class="sui-actions-right">' .
			Forminator_Integration::get_button_markup( esc_html__( 'Save', 'forminator' ), 'sui-button-primary forminator-addon-finish' ) .
			'</div>';

		$help_message = esc_html__( 'Give your webhook integration a name and add the webhook URL.', 'forminator' );

		return array(
			'html'         => '<div class="forminator-integration-popup__header"><h3 class="sui-box-title sui-lg" id="dialogTitle2">' . esc_html__( 'Set Up Webhook', 'forminator' ) . '</h3>
							<p class="sui-description">' . $help_message . '</p>
							' . $error_message . '</div>
							<form enctype="multipart/form-data">
								<div class="sui-form-field ' . ( isset( $input_error_messages['name'] ) ? 'sui-form-field-error' : '' ) . '">
									<label class="sui-label">' . esc_html__( 'Friendly Name', 'forminator' ) . '</label>
									<div class="sui-control-with-icon">
										<input type="text"
											name="name"
											placeholder="' . esc_attr__( 'Enter a friendly name E.g. Zapier to Gmail', 'forminator' ) . '"
											value="' . esc_attr( $current_data['name'] ) . '"
											class="sui-form-control"
										/>
										<i class="sui-icon-web-globe-world" aria-hidden="true"></i>
									</div>
									' . ( isset( $input_error_messages['name'] ) ? '<span class="sui-error-message">' . esc_html( $input_error_messages['name'] ) . '</span>' : '' ) . '
								</div>
								<div class="sui-form-field ' . ( isset( $input_error_messages['webhook_url_error'] ) ? 'sui-form-field-error' : '' ) . '">
									<label class="sui-label">' . esc_html__( 'Webhook URL', 'forminator' ) . '</label>
									<div class="sui-control-with-icon">
										<input
										type="text"
										name="webhook_url"
										placeholder="' . esc_attr__( 'Enter your webhook URL', 'forminator' ) . '"
										value="' . esc_attr( $current_data['webhook_url'] ) . '"
										class="sui-form-control" />
										<i class="sui-icon-link" aria-hidden="true"></i>
									</div>
									' . ( isset( $input_error_messages['webhook_url_error'] ) ? '<span class="sui-error-message">' . esc_html( $input_error_messages['webhook_url_error'] ) . '</span>' : '' ) . '
									' . ( forminator_is_show_documentation_link() ?
										'<div class="sui-description">' . sprintf(
											/* translators: 1: article anchor start, 2: article anchor end. */
											esc_html__( 'Check %1$sour documentation%2$s for more information on using webhook URLs for your preferred automation tools.', 'forminator' ),
											'<a href="https://wpmudev.com/docs/wpmu-dev-plugins/forminator/#webhook" target="_blank">',
											'</a>'
										) . '</div>' : '' ) .
									'</div>
								<input type="hidden" name="multi_id" value="' . esc_attr( $multi_id ) . '" />
							</form>',
			'redirect'     => false,
			'is_close'     => ( $is_submit && empty( $error_message ) && empty( $input_error_messages ) ),
			'buttons'      => $buttons,
			'has_errors'   => ( ! empty( $error_message ) || ! empty( $input_error_messages ) ),
			'notification' => $notification,
		);
	}

	/**
	 * Build seample data form current fields
	 *
	 * @return array
	 */
	private function build_form_sample_data() {
		$sample_data = $this->prepare_form_fields_data();

		// send form title, date.
		$sample_data['form-title'] = $this->form_settings['formName'];
		$sample_data['entry-time'] = current_time( 'Y-m-d H:i:s' );

		return $sample_data;
	}
}
