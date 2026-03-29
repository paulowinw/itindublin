<?php
/**
 * Forminator Webhook form hooks
 *
 * @package Forminator
 */

/**
 * Class Forminator_Webhook_Form_Hooks
 */
class Forminator_Webhook_Form_Hooks extends Forminator_Integration_Form_Hooks {

	/**
	 * Return custom entry fields
	 *
	 * @param array $submitted_data Submitted data.
	 * @param array $current_entry_fields Current entry fields.
	 * @return array
	 */
	protected function custom_entry_fields( $submitted_data, $current_entry_fields ): array {
		$addon_setting_values = $this->settings_instance->get_settings_values();
		$data                 = array();

		foreach ( $addon_setting_values as $key => $addon_setting_value ) {
			// save it on entry field, with name `status-$MULTI_ID`, and value is the return result on sending data to webhook.
			$data[] = array(
				'name'  => 'status-' . $key,
				'value' => $this->get_status_on_send_data( $key, $submitted_data, $addon_setting_value, $current_entry_fields ),
			);
		}

		return $data;
	}

	/**
	 * Get status on sending data towebhook
	 *
	 * @since 1.7 Add $form_entry_fields arg
	 *
	 * @param string $connection_id Connection Id.
	 * @param array  $submitted_data Submitted data.
	 * @param array  $connection_settings Connection settings.
	 * @param array  $form_entry_fields Form entry fields.
	 *
	 * @return array `is_sent` true means its success send data towebhook, false otherwise
	 * @throws Forminator_Integration_Exception Throws Integration Exception.
	 */
	private function get_status_on_send_data( $connection_id, $submitted_data, $connection_settings, $form_entry_fields ) {
		$form_settings = $this->settings_instance->get_form_settings();
		// initialize as null.
		$webhook_api = null;

		$form_id                = $this->module_id;
		$form_settings_instance = $this->settings_instance;

		// check required fields.
		try {
			if ( ! isset( $connection_settings['webhook_url'] ) ) {
				throw new Forminator_Integration_Exception( esc_html__( 'Webhook URL is not properly set up', 'forminator' ) );
			}

			$endpoint = $connection_settings['webhook_url'];
			/**
			 * Filter Endpoint Webhook URL to send
			 *
			 * @since 1.1
			 *
			 * @param string $endpoint
			 * @param int    $form_id             current Form ID.
			 * @param array  $connection_settings current connection setting, it contains `name` and `webhook_url`.
			 */
			$endpoint = apply_filters(
				'forminator_addon_webhook_endpoint',
				$endpoint,
				$form_id,
				$connection_settings
			);

			$webhook_api = $this->addon->get_api( $endpoint );

			$args = $submitted_data;
			// Remove password in webhook data.
			$args = Forminator_Password::remove_password_field_values( $args );

			$args['form-title'] = $form_settings['formName'];
			$args['entry-time'] = current_time( 'Y-m-d H:i:s' );

			/**
			 * Filter arguments to passed on to Webhook API
			 *
			 * @since 1.1
			 *
			 * @param array                                 $args
			 * @param int                                   $form_id                Current Form id.
			 * @param string                                $connection_id          ID of current connection.
			 * @param array                                 $submitted_data
			 * @param array                                 $connection_settings    current connection setting, contains `name` and `webhook_url`.
			 * @param array                                 $form_settings          Displayed Form settings.
			 * @param Forminator_Webhook_Form_Settings $form_settings_instance Webhook Form Settings instance.
			 */
			$args = apply_filters(
				'forminator_addon_webhook_post_to_webhook_args',
				$args,
				$form_id,
				$connection_id,
				$submitted_data,
				$connection_settings,
				$form_settings,
				$form_settings_instance
			);

			$args = $form_settings_instance::replace_dashes_in_keys( $args, $endpoint );

			$webhook_api->post_( $args );

			forminator_addon_maybe_log( __METHOD__, 'Success Send Data' );

			return array(
				'is_sent'         => true,
				'connection_name' => $connection_settings['name'],
				'description'     => esc_html__( 'Successfully send data to Webhook', 'forminator' ),
				'data_sent'       => $webhook_api->get_last_data_sent(),
				'data_received'   => $webhook_api->get_last_data_received(),
				'url_request'     => $webhook_api->get_last_url_request(),
			);

		} catch ( Forminator_Integration_Exception $e ) {
			forminator_addon_maybe_log( __METHOD__, 'Failed to Send to Webhook' );

			return array(
				'is_sent'         => false,
				'description'     => $e->getMessage(),
				'connection_name' => $connection_settings['name'],
				'data_sent'       => ( ( $webhook_api instanceof Forminator_Webhook_Wp_Api ) ? $webhook_api->get_last_data_sent() : array() ),
				'data_received'   => ( ( $webhook_api instanceof Forminator_Webhook_Wp_Api ) ? $webhook_api->get_last_data_received() : array() ),
				'url_request'     => ( ( $webhook_api instanceof Forminator_Webhook_Wp_Api ) ? $webhook_api->get_last_url_request() : '' ),
			);
		}
	}
}
