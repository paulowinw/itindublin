<?php
/**
 * Forminator Addon Mailerlite.
 *
 * @package Forminator
 */

/**
 * Class Forminator_Mailerlite
 * The class that defines mailerlite integration
 */
class Forminator_Mailerlite extends Forminator_Integration {

	/**
	 * Mailerlite Integration Instance
	 *
	 * @var self|null
	 */
	protected static $instance = null;

	/**
	 * Slug
	 *
	 * @var string
	 */
	protected $_slug = 'mailerlite';

	/**
	 * Mailerlite version
	 *
	 * @var string
	 */
	protected $_version = FORMINATOR_ADDON_MAILERLITE_VERSION;

	/**
	 * Forminator minimum version
	 *
	 * @var string
	 */
	protected $_min_forminator_version = '1.30';

	/**
	 * Short title
	 *
	 * @var string
	 */
	protected $_short_title = 'MailerLite';

	/**
	 * Title
	 *
	 * @var string
	 */
	protected $_title = 'MailerLite';

	/**
	 * Position
	 *
	 * @var integer
	 */
	protected $_position = 4;

	/**
	 * Sensitive key names that require encryption.
	 *
	 * @var array
	 */
	protected $_sensitive_keys = array( 'api_key' );

	/**
	 * Forminator_Mailerlite constructor.
	 * - Set dynamic translatable text(s) that will be displayed to end-user
	 * - Set dynamic icons and images
	 */
	public function __construct() {
		// late init to allow translation.
		$this->_description    = esc_html__( 'Get awesome by your form.', 'forminator' );
		$this->is_multi_global = true;
	}

	/**
	 * Check if user already completed settings
	 *
	 * @return bool
	 */
	public function is_authorized() {
		$setting_values = $this->get_settings_values();

		return ! empty( $setting_values['api_key'] );
	}

	/**
	 * Return with true / false, you may update you setting update message too
	 *
	 * @param string $api_key API key.
	 *
	 * @return bool
	 */
	protected function validate_api_keys( $api_key ) {
		try {
			// Check API Key and Secret key.
			$info = $this->get_api( $api_key )->get_info();
			forminator_addon_maybe_log( __METHOD__, $info );

		} catch ( Forminator_Integration_Exception $e ) {
			$this->_update_settings_error_message = $e->getMessage();
			return false;
		}

		return true;
	}

	/**
	 * Get API Instance
	 *
	 * @param string|null $api_key API key.
	 *
	 * @return Forminator_Mailerlite_Wp_Api|null
	 */
	public function get_api( $api_key = null ) {
		if ( is_null( $api_key ) ) {
			$api_key = $this->get_api_key();
		}
		$api = Forminator_Mailerlite_Wp_Api::get_instance( $api_key );
		return $api;
	}

	/**
	 * Get currently saved api key
	 *
	 * @return string|null
	 */
	private function get_api_key() {
		$setting_values = $this->get_settings_values( true );
		if ( isset( $setting_values['api_key'] ) ) {
			return $setting_values['api_key'];
		}

		return null;
	}

	/**
	 * Build settings help on settings
	 *
	 * @return string
	 */
	private function settings_help() {
		$help = '<span class="sui-description" style="margin-top: 20px;">';
		if ( $this->is_authorized() ) {
			// Show currently connected mailerlite account if it's already connected.
			$help .= esc_html__( 'Change your API Key or disconnect this MailerLite Integration below.', 'forminator' );
		} else {
			// Display how to get mailerlite API Key by default.
			/* Translators: 1. Opening <a> tag with link to the MailerLite API Key, 2. closing <a> tag. */
			$help .= sprintf( esc_html__( 'Please get your MailerLite API Key %1$shere%2$s', 'forminator' ), '<a href="https://dashboard.mailerlite.com/integrations/api" target="_blank">', '</a>' );
		}
		$help .= '</span>';

		return $help;
	}

	/**
	 * Settings description
	 *
	 * @return string
	 */
	private function settings_description() {
		$description = '';
		if ( $this->is_authorized() ) {
			// Show currently connected mailerlite account if its already connected.
			$description .= '<span class="sui-description">' . esc_html__( 'Please note that changing your API Key or disconnecting this integration will affect ALL of your connected forms.', 'forminator' ) . '</span>';
		}

		return $description;
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
	 *
	 * @return array
	 */
	public function configure_api_key( $submitted_data ) {
		$setting_values = $this->get_settings_values();
		$identifier     = $setting_values['identifier'] ?? '';
		$api_key        = $this->get_api_key();
		// ON Submit.
		if ( isset( $submitted_data['api_key'] ) ) {
			$api_key    = $submitted_data['api_key'];
			$identifier = $submitted_data['identifier'] ?? '';

			if ( empty( $api_key ) ) {
				$api_key_error_message = esc_html__( 'Please add valid MailerLite API Key.', 'forminator' );
			} else {
				$real_api_key      = $this->get_real_value( $api_key, 'api_key' );
				$api_key_validated = $this->validate_api_keys( $real_api_key );

				/**
				 * Filter validating api key result
				 *
				 * @param bool   $api_key_validated
				 * @param string $api_key API Key to be validated.
				 */
				$api_key_validated = apply_filters( 'forminator_addon_mailerlite_validate_api_keys', $api_key_validated, $api_key );

				if ( ! $api_key_validated ) {
					$error_message         = $this->_update_settings_error_message;
					$api_key_error_message = esc_html__( 'Invalid API key. Please check and try again.', 'forminator' );
				} else {
					$save_values = array(
						'api_key'    => $real_api_key,
						'identifier' => $identifier,
					);

					if ( ! forminator_addon_is_active( $this->get_slug() ) ) {
						$activated = Forminator_Integration_Loader::get_instance()->activate_addon( $this->get_slug() );
						if ( ! $activated ) {
							$error_message = Forminator_Integration_Loader::get_instance()->get_last_error_message();
						} else {
							$this->save_settings_values( $save_values );
						}
					} else {
						$this->save_settings_values( $save_values );
					}
				}
			}

			$has_error = ! empty( $error_message ) || ! empty( $api_key_error_message );
			if ( ! $has_error ) {
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

		$error_message = $error_message ?? false;
		$has_error     = $has_error ?? '';
		$buttons       = array();

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
		$html .= '<h3 id="dialogTitle2" class="sui-box-title sui-lg" style="overflow: initial; text-overflow: none; white-space: normal;">' . /* translators: 1: Add-on name */ sprintf( esc_html__( 'Configure %1$s', 'forminator' ), 'MailerLite' ) . '</h3>';
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
		$html .= '<input name="api_key" value="' . esc_attr( $setting_values['api_key'] ?? '' ) . '" placeholder="' . /* translators: 1: Add-on name */ sprintf( esc_html__( 'Enter %1$s API Key', 'forminator' ), 'MailerLite' ) . '" class="sui-form-control" />';
		$html .= '<i class="sui-icon-key" aria-hidden="true"></i>';
		$html .= '</div>';
		$html .= ( ! empty( $api_key_error_message ) ? '<span class="sui-error-message">' . esc_html( $api_key_error_message ) . '</span>' : '' );
		$html .= $this->settings_description();
		$html .= '</div>';
		// FIELD: Identifier.
		$html .= '<div class="sui-form-field">';
		$html .= '<label class="sui-label">' . esc_html__( 'Identifier', 'forminator' ) . '</label>';
		$html .= '<input name="identifier" value="' . esc_attr( $identifier ) . '" placeholder="' . esc_attr__( 'E.g., Business Account', 'forminator' ) . '" class="sui-form-control" />';
		$html .= '<span class="sui-description">' . esc_html__( 'Helps distinguish between integrations if connecting to the same third-party app with multiple accounts.', 'forminator' ) . '</span>';
		$html .= '</div>';
		$html .= '</form>';

		return array(
			'html'       => $html,
			'buttons'    => $buttons,
			'redirect'   => false,
			'has_errors' => $has_error,
		);
	}

	/**
	 * Flag for check if has lead form integration connected to a quiz
	 * by default it will check if last step of form settings already completed by user
	 *
	 * @param int $quiz_id Quiz Id.
	 *
	 * @return bool
	 * @throws Forminator_Integration_Exception Throws Integration Exception.
	 */
	public function is_quiz_lead_connected( $quiz_id ) {

		try {
			// initialize with null.
			$quiz_settings_instance = null;
			if ( ! $this->is_connected() ) {
				throw new Forminator_Integration_Exception( esc_html__( 'MailerLite integration not connected.', 'forminator' ) );
			}
			$quiz_settings_instance = $this->get_addon_settings( $quiz_id, 'quiz' );

			if ( ! $quiz_settings_instance instanceof Forminator_Mailerlite_Quiz_Settings ) {
				throw new Forminator_Integration_Exception( esc_html__( 'Form settings instance is not valid.', 'forminator' ) );
			}

			$quiz_settings     = $quiz_settings_instance->get_quiz_settings();
			$is_quiz_connected = ! empty( $quiz_settings['hasLeads'] );
		} catch ( Forminator_Integration_Exception $e ) {
			$is_quiz_connected = false;

			forminator_addon_maybe_log( __METHOD__, $e->getMessage() );
		}

		/**
		 * Filter connected status of mailerlite with the form
		 *
		 * @param bool                                          $is_quiz_connected
		 * @param int                                           $quiz_id                Current Form ID.
		 * @param Forminator_Mailerlite_Quiz_Settings|null $quiz_settings_instance Instance of form settings, or null when unavailable.
		 */
		$is_quiz_connected = apply_filters( 'forminator_addon_mailerlite_is_quiz_lead_connected', $is_quiz_connected, $quiz_id, $quiz_settings_instance );

		return $is_quiz_connected;
	}
}
