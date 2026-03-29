<?php
/**
 * Forminator Addon Mailjet.
 *
 * @package Forminator
 */

// @noinspection HtmlUnknownTarget.

require_once __DIR__ . '/lib/class-forminator-addon-mailjet-wp-api.php';

/**
 * Class Forminator_Mailjet
 * The class that defines mailjet integration
 */
class Forminator_Mailjet extends Forminator_Integration {

	/**
	 * Mailjet Integration Instance
	 *
	 * @var self|null
	 */
	protected static $instance = null;

	/**
	 * Slug
	 *
	 * @var string
	 */
	protected $_slug = 'mailjet';

	/**
	 * Mailjet version
	 *
	 * @var string
	 */
	protected $_version = FORMINATOR_ADDON_MAILJET_VERSION;

	/**
	 * Forminator minimum version
	 *
	 * @var string
	 */
	protected $_min_forminator_version = '1.28';

	/**
	 * Short title
	 *
	 * @var string
	 */
	protected $_short_title = 'Mailjet';

	/**
	 * Title
	 *
	 * @var string
	 */
	protected $_title = 'Mailjet';

	/**
	 * Hold account information that currently connected
	 * Will be saved to @see Forminator_Mailjet::save_settings_values()
	 *
	 * @var array
	 */
	private $_connected_account = array();

	/**
	 * Position
	 *
	 * @var int
	 */
	protected $_position = 3;

	/**
	 * Sensitive key names that require encryption.
	 *
	 * @var array
	 */
	protected $_sensitive_keys = array( 'secret_key' );

	/**
	 * Forminator_Mailjet constructor.
	 * - Set dynamic translatable text(s) that will be displayed to end-user
	 * - Set dynamic icons and images
	 */
	public function __construct() {
		// late init to allow translation.
		$this->_description    = esc_html__( 'Get awesome by your form.', 'forminator' );
		$this->is_multi_global = true;
	}

	/**
	 * Hook before save settings values
	 * to include @see Forminator_Mailjet::$_connected_account
	 * for future reference
	 *
	 * @param array $values Values to save.
	 *
	 * @return array
	 */
	public function before_save_settings_values( $values ) {
		forminator_addon_maybe_log( __METHOD__, $values );

		if ( ! empty( $this->_connected_account ) ) {
			$values['connected_account'] = $this->_connected_account;
		}

		return $values;
	}

	/**
	 * Check if user already completed settings
	 *
	 * @return bool
	 */
	public function is_authorized() {
		$setting_values = $this->get_settings_values();

		// check api_key and connected_account exists and not empty.
		return ! empty( $setting_values['api_key'] ) && ! empty( $setting_values['secret_key'] ) && ! empty( $setting_values['connected_account'] );
	}

	/**
	 * Return with true / false, you may update you setting update message too
	 *
	 * @param string $api_key API key.
	 * @param string $secret_key Secret key.
	 *
	 * @return bool
	 * @throws Forminator_Integration_Exception Throws Integration Exception.
	 */
	protected function validate_api_keys( $api_key, $secret_key ) {
		try {
			// Check API Key and Secret key.
			$info = $this->get_api( $api_key, $secret_key )->get_info();
			forminator_addon_maybe_log( __METHOD__, $info );

			$this->_connected_account = array(
				'account_name' => $info->data[0]->username ?? '',
				'email'        => $info->data[0]->email ?? '',
			);

		} catch ( Forminator_Integration_Exception $e ) {
			$this->_update_settings_error_message = $e->getMessage();
			return false;
		}

		return true;
	}

	/**
	 * Get API Instance
	 *
	 * @param string|null $api_key API Key.
	 * @param string|null $secret_key API secret Key.
	 *
	 * @return Forminator_Mailjet_Wp_Api|null
	 */
	public function get_api( $api_key = null, $secret_key = null ) {
		if ( is_null( $api_key ) ) {
			$api_key = $this->get_api_key();
		}
		if ( is_null( $secret_key ) ) {
			$secret_key = $this->get_secret_key();
		}
		$api = Forminator_Mailjet_Wp_Api::get_instance( $api_key, $secret_key );
		return $api;
	}

	/**
	 * Get currently saved api key
	 *
	 * @return string|null
	 */
	private function get_api_key() {
		$setting_values = $this->get_settings_values();
		if ( isset( $setting_values['api_key'] ) ) {
			return $setting_values['api_key'];
		}

		return null;
	}

	/**
	 * Get currently saved secret key
	 *
	 * @return string|null
	 */
	private function get_secret_key() {
		$setting_values = $this->get_settings_values( true );
		if ( isset( $setting_values['secret_key'] ) ) {
			return $setting_values['secret_key'];
		}

		return null;
	}

	/**
	 * Build settings help on settings
	 *
	 * @return string
	 */
	public function settings_help() {

		// Display how to get mailjet API Key by default.
		/* Translators: 1. Opening <a> tag with link to the Mailjet API Key, 2. closing <a> tag. */
		$help = sprintf( esc_html__( 'Please get your Mailjet API keys %1$shere%2$s', 'forminator' ), '<a href="https://app.mailjet.com/account/apikeys" target="_blank">', '</a>' );

		$help = '<span class="sui-description" style="margin-top: 20px;">' . $help . '</span>';

		$setting_values = $this->get_settings_values();

		if (
			! empty( $setting_values['api_key'] )
			&& ! empty( $setting_values['secret_key'] )
			&& ! empty( $setting_values['connected_account'] )
		) {
			// Show currently connected mailjet account if its already connected.
			$help = '<span class="sui-description" style="margin-top: 20px;">' . esc_html__( 'Change your API Key and Secret Key or disconnect this Mailjet Integration below.', 'forminator' ) . '</span>';
		}

		return $help;
	}

	/**
	 * Settings description
	 *
	 * @return string
	 */
	public function settings_description() {
		$description    = '';
		$setting_values = $this->get_settings_values();

		if (
			! empty( $setting_values['api_key'] )
			&& ! empty( $setting_values['secret_key'] )
			&& ! empty( $setting_values['connected_account'] )
		) {
			// Show currently connected mailjet account if its already connected.
			$description .= '<span class="sui-description">' . esc_html__( 'Please note that changing your API Key and Secret Key or disconnecting this integration will affect ALL of your connected forms.', 'forminator' ) . '</span>';
		}

		return $description;
	}

	/**
	 * Connected account info
	 *
	 * @return string
	 */
	public function settings_account() {
		$myaccount = '';
		$settings  = $this->get_settings_values();

		if ( ! empty( $settings['api_key'] ) && ! empty( $settings['secret_key'] ) && ! empty( $settings['connected_account'] ) ) {

			$connected_account = $settings['connected_account'];

			// Show currently connected mailjet account if its already connected.
			$notice = sprintf(
				/* translators:  placeholder is Name and Email of Connected MailJet Account */
				esc_html__( 'Your Mailjet is connected to %1$s: %2$s.', 'forminator' ),
				'<strong>' . esc_html( $connected_account['account_name'] ) . '</strong>',
				sanitize_email( $connected_account['email'] )
			);

			$myaccount = Forminator_Admin::get_green_notice( $notice );

		}

		return $myaccount;
	}

	/**
	 * Settings wizard
	 *
	 * @return array
	 */
	public function settings_wizards() {
		return array(
			array(
				'callback'     => array( $this, 'configure_api_key' ),
				'is_completed' => array( $this, 'is_authorized' ),
			),
		);
	}

	/**
	 * Wizard of configure_api_key
	 *
	 * @param array $submitted_data Submitted data.
	 * @param int   $form_id Form Id.
	 *
	 * @return array
	 */
	public function configure_api_key( $submitted_data, $form_id = 0 ) {
		$error_message         = '';
		$api_key_error_message = '';
		$secret_key_error      = '';
		$setting_values        = $this->get_settings_values();
		$identifier            = $setting_values['identifier'] ?? '';
		$api_key               = $this->get_api_key();
		$secret_key            = $setting_values['secret_key'] ?? '';
		$show_success          = false;

		// ON Submit.
		if ( isset( $submitted_data['api_key'] ) ) {
			$api_key    = $submitted_data['api_key'];
			$secret_key = $submitted_data['secret_key'] ?? '';
			$identifier = $submitted_data['identifier'] ?? '';

			if ( empty( $api_key ) ) {
				$api_key_error_message = esc_html__( 'Please add valid Mailjet API Key.', 'forminator' );
			} elseif ( empty( $secret_key ) ) {
				$secret_key_error = esc_html__( 'Please add valid Mailjet Secret Key.', 'forminator' );
			} else {
				$real_secret_key   = $this->get_real_value( $secret_key, 'secret_key' );
				$api_key_validated = $this->validate_api_keys( $api_key, $real_secret_key );

				/**
				 * Filter validating api key result
				 *
				 * @param bool   $api_key_validated
				 * @param string $api_key API Key to be validated.
				 */
				$api_key_validated = apply_filters( 'forminator_addon_mailjet_validate_api_keys', $api_key_validated, $api_key, $secret_key );

				if ( ! $api_key_validated ) {
					$error_message = $this->_update_settings_error_message;
				} else {
					$save_values = array(
						'api_key'    => $api_key,
						'secret_key' => $real_secret_key,
						'identifier' => $identifier,
					);

					if ( ! forminator_addon_is_active( $this->_slug ) ) {
						$activated = Forminator_Integration_Loader::get_instance()->activate_addon( $this->_slug );
						if ( ! $activated ) {
							$error_message = Forminator_Integration_Loader::get_instance()->get_last_error_message();
						} else {
							$this->save_settings_values( $save_values );
							$show_success = true;
						}
					} else {
						$this->save_settings_values( $save_values );
						$show_success = true;
					}
				}
			}

			if ( $show_success ) {
				if ( ! empty( $form_id ) ) {
					// initiate form settings wizard.
					return $this->get_form_settings_wizard( array(), $form_id, 0, 0 );
				}

				$html = $this->success_authorize();
				return array(
					'html'         => $html,
					'redirect'     => false,
					'has_errors'   => false,
					'notification' => array(
						'type' => 'success',
						'text' => '<strong>' . $this->get_title() . '</strong> ' . esc_html__( 'is connected successfully.', 'forminator' ),
					),
				);
			}
		}

		$buttons = array();

		if ( $this->is_connected() ) {
			$buttons['disconnect'] = array(
				'markup' => self::get_button_markup( esc_html__( 'Disconnect', 'forminator' ), 'sui-button-ghost forminator-addon-disconnect' ),
			);

			$buttons['submit'] = array(
				'markup' => '<div class="sui-actions-right">' .
							self::get_button_markup( esc_html__( 'Save', 'forminator' ), 'forminator-addon-connect' ) .
							'</div>',
			);
		} else {
			$buttons['submit'] = array(
				'markup' => self::get_button_markup( esc_html__( 'Connect', 'forminator' ), 'forminator-addon-connect' ),
			);
		}

		$html = '<div class="forminator-integration-popup__header">';
		/* translators: ... */
		$html .= '<h3 id="dialogTitle2" class="sui-box-title sui-lg" style="overflow: initial; text-overflow: none; white-space: normal;">' . /* translators: 1: Add-on name */ sprintf( esc_html__( 'Configure %1$s', 'forminator' ), 'Mailjet' ) . '</h3>';
		$html .= $this->settings_help();
		$html .= $error_message ? '<div class="sui-notice sui-notice-error"><div class="sui-notice-content"><div class="sui-notice-message">
										<span class="sui-notice-icon sui-icon-info" aria-hidden="true" ></span>
										<p>' . $error_message . '</p>
									</div></div></div>' : '';
		$html .= '</div>';
		$html .= '<form>';
		// FIELD: API Key.
		$html .= '<div class="sui-form-field ' . ( ! empty( $api_key_error_message ) ? 'sui-form-field-error' : '' ) . '">';
		$html .= '<label class="sui-label">' . esc_html__( 'API Key', 'forminator' ) . '</label>';
		$html .= '<div class="sui-control-with-icon">';
		/* translators: ... */
		$html .= '<input name="api_key" value="' . esc_attr( $api_key ) . '" placeholder="' . /* translators: 1: Add-on name */ sprintf( esc_html__( 'Enter %1$s API Key', 'forminator' ), 'Mailjet' ) . '" class="sui-form-control" />';
		$html .= '<i class="sui-icon-key" aria-hidden="true"></i>';
		$html .= '</div>';
		$html .= ( ! empty( $api_key_error_message ) ? '<span class="sui-error-message">' . esc_html( $api_key_error_message ) . '</span>' : '' );
		$html .= $this->settings_description();
		$html .= '</div>';
		// FIELD: API Secret.
		$html .= '<div class="sui-form-field ' . ( ! empty( $secret_key_error ) ? 'sui-form-field-error' : '' ) . '">';
		$html .= '<label class="sui-label">' . esc_html__( 'API Secret', 'forminator' ) . '</label>';
		$html .= '<div class="sui-control-with-icon">';
		/* translators: ... */
		$html .= '<input name="secret_key" value="' . esc_attr( $secret_key ) . '" placeholder="' . /* translators: 1: Add-on name */ sprintf( esc_html__( 'Enter %1$s API Secret', 'forminator' ), 'Mailjet' ) . '" class="sui-form-control" />';
		$html .= '<i class="sui-icon-lock" aria-hidden="true"></i>';
		$html .= '</div>';
		$html .= ( ! empty( $secret_key_error ) ? '<span class="sui-error-message">' . esc_html( $secret_key_error ) . '</span>' : '' );
		$html .= $this->settings_description();
		$html .= '</div>';
		// FIELD: Identifier.
		$html .= '<div class="sui-form-field">';
		$html .= '<label class="sui-label">' . esc_html__( 'Identifier', 'forminator' ) . '</label>';
		$html .= '<input name="identifier" value="' . esc_attr( $identifier ) . '" placeholder="' . esc_attr__( 'E.g., Business Account', 'forminator' ) . '" class="sui-form-control" />';
		$html .= '<span class="sui-description">' . esc_html__( 'Helps distinguish between integrations if connecting to the same third-party app with multiple accounts.', 'forminator' ) . '</span>';
		$html .= '</div>';
		$html .= '</form>';
		$html .= $this->settings_account();

		return array(
			'html'       => $html,
			'buttons'    => $buttons,
			'redirect'   => false,
			'has_errors' => ! empty( $error_message ) || ! empty( $api_key_error_message ) || ! empty( $secret_key_error ),
		);
	}
}
