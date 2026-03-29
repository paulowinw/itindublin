<?php
/**
 * Trait for common methods for Webhook settings classes
 *
 * @since 1.30
 * @package Webhook Integration
 */

/**
 * Trait Forminator_Webhook_Settings_Trait
 */
trait Forminator_Webhook_Settings_Trait {

	/**
	 * Webhook Settings wizard on Module
	 *
	 * @return array
	 */
	public function module_settings_wizards() {
		return array(
			// 0
			array(
				'callback'     => array( $this, 'setup_webhook_url' ),
				'is_completed' => array( $this, 'setup_webhook_url_is_completed' ),
			),
		);
	}

	/**
	 * Sending test sample to webhook URL
	 * Data sent will be used on webhook to map fields on their zap action
	 *
	 * @param array                                     $submitted_data Submitted data.
	 * @param Forminator_Integration_Settings_Exception $current_input_exception Integration settings exception.
	 *
	 * @throws Forminator_Integration_Settings_Exception Throws Integration Settings Exception.
	 */
	private function validate_and_send_sample( $submitted_data, Forminator_Integration_Settings_Exception $current_input_exception ) {
		$module_id = $this->module_id;
		if ( ! isset( $submitted_data['webhook_url'] ) ) {
			$current_input_exception->add_input_exception( esc_html__( 'Please put a valid Webhook URL.', 'forminator' ), 'webhook_url_error' );
			throw $current_input_exception;
		}

		// must not be in silent mode.
		if ( stripos( $submitted_data['webhook_url'], 'silent' ) !== false ) {
			$current_input_exception->add_input_exception( esc_html__( 'Please disable Silent Mode on Webhook URL.', 'forminator' ), 'webhook_url_error' );
			throw $current_input_exception;
		}

		$endpoint = wp_http_validate_url( $submitted_data['webhook_url'] );
		if ( false === $endpoint ) {
			$current_input_exception->add_input_exception( esc_html__( 'Please put a valid Webhook URL.', 'forminator' ), 'webhook_url_error' );
			throw $current_input_exception;
		}

		if ( $current_input_exception->input_exceptions_is_available() ) {
			throw $current_input_exception;
		}

		$connection_settings = $submitted_data;
		/**
		 * Filter Endpoint Webhook URL to send
		 *
		 * @since 1.1
		 *
		 * @param string $endpoint
		 * @param int    $module_id             current Form ID.
		 * @param array  $connection_settings Submitted data by user, it contains `name` and `webhook_url`.
		 */
		$endpoint = apply_filters(
			'forminator_addon_webhook_' . static::$module_slug . '_endpoint',
			$endpoint,
			$module_id,
			$connection_settings
		);
		$endpoint = apply_filters_deprecated(
			'forminator_addon_webhook_endpoint',
			array(
				$endpoint,
				$module_id,
				$connection_settings,
			),
			'1.33',
			'forminator_addon_webhook_' . static::$module_slug . '_endpoint'
		);

		forminator_addon_maybe_log( __METHOD__, $endpoint );
		$api = $this->addon->get_api( $endpoint );

		// build form sample data.
		$sample_data            = $this->build_form_sample_data();
		$sample_data            = self::replace_dashes_in_keys( $sample_data, $endpoint );
		$sample_data['is_test'] = true;

		/**
		 * Filter sample data to send to Webhook URL
		 *
		 * It fires when user saved Webhook connection on Form Settings Page.
		 * Sample data contains `is_test` key with value `true`,
		 * this key indicating that it wont process trigger on Webhook.
		 *
		 * @since 1.1
		 *
		 * @param array $sample_data
		 * @param int   $module_id        current Form ID.
		 * @param array $submitted_data Submitted data by user, it contains `name` and `webhook_url`.
		 */
		$sample_data = apply_filters(
			'forminator_addon_webhook_' . static::$module_slug . '_sample_data',
			$sample_data,
			$module_id,
			$submitted_data
		);

		$sample_data = apply_filters_deprecated(
			'forminator_addon_webhook_sample_data',
			array(
				$sample_data,
				$module_id,
				$submitted_data,
			),
			'1.33',
			'forminator_addon_webhook_' . static::$module_slug . '_sample_data'
		);

		$api->post_( $sample_data );
	}

	/**
	 * Setup webhook url
	 *
	 * @param array $submitted_data Submitted data.
	 * @return array
	 * @throws Forminator_Integration_Settings_Exception Throws Integration Settings Exception.
	 */
	public function setup_webhook_url( $submitted_data ) {
		$template = forminator_addon_webhook_dir() . 'views/module/setup-webhook.php';

		if ( ! isset( $submitted_data['multi_id'] ) ) {
			return $this->get_force_closed_wizard();
		}

		$multi_id = $submitted_data['multi_id'];
		unset( $submitted_data['multi_id'] );

		$template_params = array(
			'name'        => $this->get_multi_id_settings( $multi_id, 'name' ),
			'webhook_url' => $this->get_multi_id_settings( $multi_id, 'webhook_url' ),
			'multi_id'    => $multi_id,
		);

		$is_submit    = ! empty( $submitted_data );
		$has_errors   = false;
		$is_close     = false;
		$notification = array();

		if ( $is_submit ) {
			$name                    = isset( $submitted_data['name'] ) ? trim( $submitted_data['name'] ) : '';
			$template_params['name'] = $name;

			$webhook_url                    = isset( $submitted_data['webhook_url'] ) ? trim( $submitted_data['webhook_url'] ) : '';
			$template_params['webhook_url'] = $webhook_url;

			try {
				$input_exceptions = new Forminator_Integration_Settings_Exception();

				if ( empty( $name ) ) {
					$input_exceptions->add_input_exception( esc_html__( 'Please specify integration name.', 'forminator' ), 'name_error' );
				}

				$this->validate_and_send_sample( $submitted_data, $input_exceptions );

				if ( $input_exceptions->input_exceptions_is_available() ) {
					throw $input_exceptions;
				}

				$time_added = $this->get_multi_id_settings( $multi_id, 'time_added', time() );
				$this->save_multi_id_setting_values(
					$multi_id,
					array(
						'name'        => $name,
						'webhook_url' => $webhook_url,
						'time_added'  => $time_added,
					)
				);

				$notification = array(
					'type' => 'success',
					'text' => '<strong>' . $this->addon->get_title() . '</strong> ' . esc_html__( 'Successfully connected and sent sample data to your Webhook', 'forminator' ),
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
		if ( $this->setup_webhook_url_is_completed( array( 'multi_id' => $multi_id ) ) ) {
			$buttons['disconnect']['markup'] = Forminator_Integration::get_button_markup(
				esc_html__( 'Deactivate', 'forminator' ),
				'sui-button-ghost sui-tooltip sui-tooltip-top-center forminator-addon-form-disconnect',
				esc_html__( 'Deactivate Webhook from this module.', 'forminator' )
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
			'has_back'     => false,
			'is_close'     => $is_close,
			'notification' => $notification,
		);
	}

	/**
	 * Check if setup webhook url is completed
	 *
	 * @param array $submitted_data Submitted data.
	 * @return bool
	 */
	public function setup_webhook_url_is_completed( $submitted_data ) {
		return $this->if_properties_exist( $submitted_data, array( 'name', 'webhook_url' ) );
	}

	/**
	 * Prepare fake data to send to webhook during initialization the integration.
	 *
	 * @return array
	 */
	public function prepare_form_fields_data() {
		$sample_data = array();
		if ( empty( $this->form_fields ) ) {
			return $sample_data;
		}
		foreach ( $this->form_fields as $form_field ) {
			$sample_data[ $form_field['element_id'] ] = $form_field['field_label'];

			if ( 'upload' === $form_field['type'] ) {

				$sample_file_path = '/fake/path';
				$upload_dir       = wp_get_upload_dir();
				if ( isset( $upload_dir['basedir'] ) ) {
					$sample_file_path = $upload_dir['basedir'];
				}

				$sample_data[ $form_field['element_id'] ] = array(
					'name'      => $form_field['field_label'],
					'type'      => 'image/png',
					'size'      => 0,
					'file_url'  => get_home_url(),
					'file_path' => $sample_file_path,
				);
			}
		}

		return $sample_data;
	}
}
