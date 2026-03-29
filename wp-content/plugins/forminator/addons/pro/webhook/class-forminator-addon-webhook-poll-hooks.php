<?php
/**
 * Forminator Webhook Poll Hooks
 *
 * @package Forminator
 */

/**
 * Class Forminator_Webhook_Poll_Hooks
 *
 * @since 1.6.1
 */
class Forminator_Webhook_Poll_Hooks extends Forminator_Integration_Poll_Hooks {

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
			// save it on entry field, with name `status-$MULTI_ID`, and value is the return result on sending data towebhook.
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
	 * @since 1.6.1
	 *
	 * @param string $connection_id Connection Id.
	 * @param array  $submitted_data Submitted data.
	 * @param array  $connection_settings Connection settings.
	 * @param array  $current_entry_fields Form entry fields.
	 *
	 * @return array `is_sent` true means its success send data towebhook, false otherwise
	 * @throws Forminator_Integration_Exception Throws Integration Exception.
	 */
	private function get_status_on_send_data( $connection_id, $submitted_data, $connection_settings, $current_entry_fields ) {
		$poll_settings = $this->settings_instance->get_poll_settings();
		// initialize as null.
		$webhook_api = null;

		$poll_id                = $this->module_id;
		$poll_settings_instance = $this->settings_instance;

		// check required fields.
		try {
			if ( ! isset( $connection_settings['webhook_url'] ) ) {
				throw new Forminator_Integration_Exception( esc_html__( 'Webhook URL is not properly set up', 'forminator' ) );
			}

			$endpoint = $connection_settings['webhook_url'];
			/**
			 * Filter Endpoint Webhook URL to send
			 *
			 * @since 1.6.1
			 *
			 * @param string $endpoint
			 * @param int    $poll_id             current Form ID.
			 * @param array  $connection_settings current connection setting, it contains `name` and `webhook_url`.
			 */
			$endpoint = apply_filters(
				'forminator_addon_webhook_poll_endpoint',
				$endpoint,
				$poll_id,
				$connection_settings
			);

			$webhook_api = $this->addon->get_api( $endpoint );

			$args              = array();
			$args['poll-name'] = forminator_get_name_from_model( $this->module );

			$answer_data   = isset( $submitted_data[ $this->module_id ] ) ? $submitted_data[ $this->module_id ] : '';
			$extra_field   = isset( $submitted_data[ $this->module_id . '-extra' ] ) ? $submitted_data[ $this->module_id . '-extra' ] : '';
			$fields_labels = $this->module->pluck_fields_array( 'title', 'element_id', '1' );

			$answer = isset( $fields_labels[ $answer_data ] ) ? $fields_labels[ $answer_data ] : $answer_data;
			$extra  = $extra_field;

			$args['vote']       = $answer;
			$args['vote-extra'] = $extra;
			$args['results']    = array();

			$fields_array = $this->module->get_fields_as_array();
			$map_entries  = Forminator_Form_Entry_Model::map_polls_entries( $this->module_id, $fields_array );

			// append new answer.
			if ( ! $this->module->is_prevent_store() ) {
				$answer_data = isset( $submitted_data[ $this->module_id ] ) ? $submitted_data[ $this->module_id ] : '';

				$entries = 0;
				// exists on map entries.
				if ( in_array( $answer_data, array_keys( $map_entries ), true ) ) {
					$entries = $map_entries[ $answer_data ];
				}

				++$entries;
				$map_entries[ $answer_data ] = $entries;

			}

			$fields = $this->module->get_fields();

			if ( ! is_null( $fields ) ) {
				foreach ( $fields as $field ) {
					$label = addslashes( $field->title );

					$slug    = isset( $field->slug ) ? $field->slug : sanitize_title( $label );
					$entries = 0;
					if ( in_array( $slug, array_keys( $map_entries ), true ) ) {
						$entries = $map_entries[ $slug ];
					}
					$args['results'][ $slug ] = array(
						'label' => $label,
						'votes' => $entries,
					);
				}
			}

			/**
			 * Filter arguments to passed on to Webhook API
			 *
			 * @since 1.6.1
			 *
			 * @param array                                 $args
			 * @param int                                   $poll_id                Current Poll id.
			 * @param string                                $connection_id          ID of current connection.
			 * @param array                                 $submitted_data
			 * @param array                                 $connection_settings    current connection setting, contains `name` and `webhook_url`.
			 * @param array                                 $poll_settings          Displayed Poll settings.
			 * @param Forminator_Webhook_Poll_Settings $poll_settings_instance Webhook Poll Settings instance.
			 */
			$args = apply_filters(
				'forminator_addon_webhook_poll_post_to_webhook_args',
				$args,
				$poll_id,
				$connection_id,
				$submitted_data,
				$connection_settings,
				$poll_settings,
				$poll_settings_instance
			);

			$args = $poll_settings_instance::replace_dashes_in_keys( $args, $endpoint );

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
