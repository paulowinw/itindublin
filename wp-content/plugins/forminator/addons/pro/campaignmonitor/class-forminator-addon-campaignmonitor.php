<?php
/**
 * The Forminator Campaign Monitor
 *
 * @package Forminator
 */

// Include addon-campaignmonitor-wp-api.
require_once __DIR__ . '/lib/class-forminator-addon-campaignmonitor-wp-api.php';

/**
 * Class Forminator_Campaignmonitor
 * Campaignmonitor Integration Main Class
 *
 * @since 1.0 Campaignmonitor Integration
 */
final class Forminator_Campaignmonitor extends Forminator_Integration {

	/**
	 * Forminator_Campaignmonitor Instance
	 *
	 * @var self|null
	 */
	protected static $instance = null;

	/**
	 * Slug
	 *
	 * @var string
	 */
	protected $_slug = 'campaignmonitor';

	/**
	 * Campaign Monitor version
	 *
	 * @var string
	 */
	protected $_version = FORMINATOR_ADDON_CAMPAIGNMONITOR_VERSION;

	/**
	 * Version
	 *
	 * @var string
	 */
	protected $_min_forminator_version = '1.1';

	/**
	 * Short title
	 *
	 * @var string
	 */
	protected $_short_title = 'Campaign Monitor';

	/**
	 * Title
	 *
	 * @var string
	 */
	protected $_title = 'Campaign Monitor';

	/**
	 * Position
	 *
	 * @var integer
	 */
	protected $_position = 6;

	/**
	 * Sensitive key names that require encryption.
	 *
	 * @var array
	 */
	protected $_sensitive_keys = array( 'api_key', 'client_id' );

	/**
	 * Forminator_Campaignmonitor constructor.
	 *
	 * @since 1.0 Campaignmonitor Integration
	 */
	public function __construct() {
		// late init to allow translation.
		$this->_description = esc_html__( 'Get awesome by your form.', 'forminator' );

		$this->is_multi_global = true;
	}

	/**
	 * Override settings available,
	 *
	 * @since 1.0 Campaignmonitor Integration
	 * @return bool
	 */
	public function is_settings_available() {
		return true;
	}

	/**
	 * Flag delete subscriber on delete submission
	 *
	 * @since 1.0 Campaignmonitor Integration
	 * @return bool
	 */
	public static function is_enable_delete_subscriber() {
		$delete_subscriber = false;
		if ( defined( 'FORMINATOR_ADDON_CAMPAIGNMONITOR_ENABLE_DELETE_SUBSCRIBER' ) && FORMINATOR_ADDON_CAMPAIGNMONITOR_ENABLE_DELETE_SUBSCRIBER ) {
			$delete_subscriber = true;
		}

		/**
		 * Filter Flag delete subscriber on delete submission
		 *
		 * @since  1.3
		 *
		 * @params bool $delete_subscriber
		 */
		$delete_subscriber = apply_filters( 'forminator_addon_campaignmonitor_enable_delete_subscriber', $delete_subscriber );

		return $delete_subscriber;
	}

	/**
	 * Allow multiple connection on one form
	 *
	 * @since 1.0 Campaignmonitor Integration
	 * @return bool
	 */
	public function is_allow_multi_on_form() {
		return true;
	}

	/**
	 * Setting wizard of Campaign Monitor
	 *
	 * @since 1.0 Campaign Monitor Integration
	 * @return array
	 */
	public function settings_wizards() {
		return array(
			array(
				'callback'     => array( $this, 'setup_api' ),
				'is_completed' => array( $this, 'is_authorized' ),
			),
		);
	}


	/**
	 * Setup API Wizard
	 *
	 * @since 1.0 Campaign Monitor Integration
	 *
	 * @param array $submitted_data Submitted data.
	 * @param int   $form_id Form Id.
	 *
	 * @return array
	 * @throws Forminator_Integration_Exception Throws Integration Exception.
	 */
	public function setup_api( $submitted_data, $form_id = 0 ) {
		$settings_values = $this->get_settings_values();

		$template = forminator_addon_campaignmonitor_dir() . 'views/settings/setup-api.php';

		$template_params = array(
			'identifier'      => '',
			'error_message'   => '',
			'api_key'         => '',
			'client_id'       => '',
			'api_key_error'   => '',
			'client_id_error' => '',
			'client_name'     => '',
		);

		$has_errors   = false;
		$show_success = false;
		$buttons      = array();
		$is_submit    = ! empty( $submitted_data );

		foreach ( $template_params as $key => $value ) {
			if ( isset( $submitted_data[ $key ] ) ) {
				$template_params[ $key ] = $submitted_data[ $key ];
			} elseif ( isset( $settings_values[ $key ] ) ) {
				$template_params[ $key ] = $settings_values[ $key ];
			}
		}

		if ( $is_submit ) {
			$api_key    = $submitted_data['api_key'] ?? '';
			$client_id  = $submitted_data['client_id'] ?? '';
			$identifier = $submitted_data['identifier'] ?? '';

			try {
				$real_api_key = $this->get_real_value( $api_key, 'api_key' );
				$api_key      = $this->validate_api_key( $real_api_key );
			} catch ( Forminator_Integration_Exception $e ) {
				$template_params['api_key_error'] = $e->getMessage();
				$has_errors                       = true;
			}

			if ( ! $has_errors ) {
				// validate api.
				try {

					$this->validate_api( $api_key );

					$client_details = null;

					if ( ! empty( $client_id ) ) {
						$client_id      = $this->get_real_value( $client_id, 'client_id' );
						$client_details = $this->validate_client( $api_key, $client_id );
					} else {
						// find first client.
						$clients = $this->get_api( $api_key )->get_clients();
						if ( is_array( $clients ) ) {
							if ( isset( $clients[0] ) ) {
								$client = $clients[0];
								if ( isset( $client->ClientID ) ) { //phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
									$client_id      = $client->ClientID; //phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
									$client_details = $this->validate_client( $api_key, $client_id );
								}
							}
						}
					}

					if ( ! isset( $client_details->BasicDetails ) //phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
						|| ! isset( $client_details->BasicDetails->ClientID ) //phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
						|| ! isset( $client_details->BasicDetails->CompanyName ) ) { //phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
						throw new Forminator_Integration_Exception( esc_html__( 'Could not find client details, please try again', 'forminator' ) );
					}

					$client_name = $client_details->BasicDetails->CompanyName; //phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

					if ( ! forminator_addon_is_active( $this->_slug ) ) {
						$activated = Forminator_Integration_Loader::get_instance()->activate_addon( $this->_slug );
						if ( ! $activated ) {
							throw new Forminator_Integration_Exception( Forminator_Integration_Loader::get_instance()->get_last_error_message() );
						}
					}

					$settings_values = array(
						'identifier'  => $identifier,
						'api_key'     => $api_key,
						'client_id'   => $client_id,
						'client_name' => $client_name,
					);
					$this->save_settings_values( $settings_values );

					// No form_id its on global settings.
					if ( empty( $form_id ) ) {
						$show_success = true;
					}
				} catch ( Forminator_Integration_Exception $e ) {
					$template_params['error_message'] = $e->getMessage();
					$has_errors                       = true;
				}
			}
		}

		if ( $show_success ) {
			$html = $this->success_authorize();
		} else {
			if ( $this->is_connected() ) {
				$buttons['disconnect'] = array(
					'markup' => self::get_button_markup( esc_html__( 'Disconnect', 'forminator' ), 'sui-button-ghost forminator-addon-disconnect' ),
				);
				$buttons['submit']     = array(
					'markup' => '<div class="sui-actions-right">' .
								self::get_button_markup( esc_html__( 'Save', 'forminator' ), 'forminator-addon-connect' ) .
								'</div>',
				);
			} else {
				$buttons['submit'] = array(
					'markup' => '<div class="sui-actions-right">' .
								self::get_button_markup( esc_html__( 'CONNECT', 'forminator' ), 'forminator-addon-connect' ) .
								'</div>',
				);
			}
			$html = self::get_template( $template, $template_params );
		}

		return array(
			'html'       => $html,
			'buttons'    => $buttons,
			'redirect'   => false,
			'has_errors' => $has_errors,
		);
	}

	/**
	 * Check Api settings Completed
	 *
	 * @since 1.o Campaign Monitor
	 * @return bool
	 */
	public function is_authorized() {
		$setting_values = $this->get_settings_values();

		// check api_key set up.
		return ( isset( $setting_values['api_key'] ) && ! empty( $setting_values['api_key'] ) );
	}

	/**
	 * Get API Instance
	 *
	 * @since 1.0 Campaign Monitor Integration
	 *
	 * @param string|null $api_key API Key.
	 *
	 * @return Forminator_Campaignmonitor_Wp_Api
	 * @throws Forminator_Integration_Exception Throws Integration Exception.
	 */
	public function get_api( $api_key = null ) {
		if ( is_null( $api_key ) ) {
			$setting_values = $this->get_settings_values( true );
			$api_key        = '';
			if ( isset( $setting_values['api_key'] ) ) {
				$api_key = $setting_values['api_key'];
			}
		}
		$api = Forminator_Campaignmonitor_Wp_Api::get_instance( $api_key );
		return $api;
	}

	/**
	 * Validate API Key
	 *
	 * @since 1.0 Campaign Monitor
	 *
	 * @param string $api_key API Key.
	 *
	 * @return string
	 * @throws Forminator_Integration_Exception Throws Integration Exception.
	 */
	public function validate_api_key( $api_key ) {
		if ( empty( $api_key ) ) {
			throw new Forminator_Integration_Exception( esc_html__( 'Please put a valid Campaign Monitor API Key', 'forminator' ) );
		}

		return $api_key;
	}

	/**
	 * Validate API
	 *
	 * @since 1.0 Campaign Monitor Integration
	 *
	 * @param string $api_key API Key.
	 *
	 * @throws Forminator_Integration_Exception Throws Integration Exception.
	 */
	public function validate_api( $api_key ) {
		$api         = $this->get_api( $api_key );
		$system_date = $api->get_system_date();

		if ( ! isset( $system_date->SystemDate ) || empty( $system_date->SystemDate ) ) { //phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			throw new Forminator_Integration_Exception( esc_html__( 'Failed to validate API Key.', 'forminator' ) );
		}
	}

	/**
	 * Validate Client
	 *
	 * @since 1.0 Campaign Monitor Integration
	 *
	 * @param string $api_key API Key.
	 * @param string $client_id Client Id.
	 *
	 * @return array|mixed|object
	 * @throws Forminator_Integration_Exception Throws Integration Exception.
	 */
	public function validate_client( $api_key, $client_id ) {
		$api            = $this->get_api( $api_key );
		$client_details = $api->get_client( $client_id );

		return $client_details;
	}

	/**
	 * Get Client ID
	 *
	 * @since 1.0 Campaign Monitor Integration
	 * @return string
	 */
	public function get_client_id() {
		$settings_values = $this->get_settings_values( true );
		$client_id       = '';
		if ( isset( $settings_values ['client_id'] ) ) {
			$client_id = $settings_values ['client_id'];
		}

		/**
		 * Filter Campaign Monitor client id used
		 *
		 * @since 1.3
		 *
		 * @param string $client_id
		 */
		$client_id = apply_filters( 'forminator_addon_campaignmonitor_client_id', $client_id );

		return $client_id;
	}

	/**
	 * Allow multiple connection on one quiz
	 *
	 * @since 1.6.1
	 * @return bool
	 */
	public function is_allow_multi_on_quiz() {
		return true;
	}
}
