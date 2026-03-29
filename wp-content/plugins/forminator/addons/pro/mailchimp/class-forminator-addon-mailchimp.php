<?php
/**
 * Forminator Addon Mailchimp.
 *
 * @package Forminator
 */

// @noinspection HtmlUnknownTarget.

require_once __DIR__ . '/lib/class-forminator-addon-mailchimp-wp-api.php';

/**
 * Class Forminator_Mailchimp
 * The class that defines mailchimp integration
 *
 * @since 1.0 Mailchimp Integration
 */
class Forminator_Mailchimp extends Forminator_Integration {

	/**
	 * Mailchimp Integration Instance
	 *
	 * @since 1.0 Mailchimp Integration
	 *
	 * @var self|null
	 */
	protected static $instance = null;

	/**
	 * Slug
	 *
	 * @since 1.0 Mailchimp Integration
	 * @var string
	 */
	protected $_slug = 'mailchimp';

	/**
	 * Mailchimp version
	 *
	 * @since 1.0 Mailchimp Integration
	 * @var string
	 */
	protected $_version = FORMINATOR_ADDON_MAILCHIMP_VERSION;

	/**
	 * Forminator min version
	 *
	 * @since 1.0 Mailchimp Integration
	 * @var string
	 */
	protected $_min_forminator_version = '1.1';

	/**
	 * Short title
	 *
	 * @since 1.0 Mailchimp Integration
	 * @var string
	 */
	protected $_short_title = 'Mailchimp';

	/**
	 * Title
	 *
	 * @since 1.0 Mailchimp Integration
	 * @var string
	 */
	protected $_title = 'Mailchimp';

	/**
	 * Hold account information that currently connected
	 * Will be saved to @see Forminator_Mailchimp::save_settings_values()
	 *
	 * @since 1.0 Mailchimp Integration
	 * @var array
	 */
	private $_connected_account = array();

	/**
	 * Position
	 *
	 * @var int
	 */
	protected $_position = 2;

	/**
	 * Sensitive key names that require encryption.
	 *
	 * @var array
	 */
	protected $_sensitive_keys = array( 'api_key' );

	/**
	 * Forminator_Mailchimp constructor.
	 * - Set dynamic translatable text(s) that will be displayed to end-user
	 * - Set dynamic icons and images
	 *
	 * @since 1.0 Mailchimp Integration
	 */
	public function __construct() {
		// late init to allow translation.
		$this->_description = esc_html__( 'Make form data as Mailchimp List', 'forminator' );

		if ( wp_doing_ajax() ) {
			add_action( 'wp_ajax_forminator_mailchimp_get_group_interests', array( $this, 'ajax_group_interests' ) );
		}

		$this->is_multi_global = true;
	}

	/**
	 * Hook before save settings values
	 * to include @see Forminator_Mailchimp::$_connected_account
	 * for future reference
	 *
	 * @since 1.0 Mailchimp Integration
	 *
	 * @param array $values Settings.
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
	 * @since 1.0 Mailchimp Integration
	 * @return bool
	 */
	public function is_authorized() {
		$setting_values = $this->get_settings_values();

		// check api_key and connected_account exists and not empty.
		return ! empty( $setting_values['api_key'] ) && ! empty( $setting_values['connected_account'] );
	}

	/**
	 * Return with true / false, you may update you setting update message too
	 *
	 * @see   _update_settings_error_message
	 *
	 * @since 1.0 Mailchimp Integration
	 *
	 * @param string $api_key API Key.
	 *
	 * @return bool
	 * @throws Forminator_Integration_Exception Throws Integration Exception.
	 */
	protected function validate_api_key( $api_key ) {
		if ( empty( $api_key ) ) {
			$this->_update_settings_error_message = esc_html__( 'Please add valid Mailchimp API Key.', 'forminator' );

			return false;
		}

		try {
			// Check API Key by validating it on get_info request.
			$info = $this->get_api( $api_key )->get_info();
			forminator_addon_maybe_log( __METHOD__, $info );

			if ( 'Forminator_Integration_Exception' === get_class( $info ) ) {
				throw new Forminator_Integration_Exception( $info->getMessage() );
			}

			$this->_connected_account = array(
				'account_id'   => $info->account_id,
				'account_name' => $info->account_name,
				'email'        => $info->email,
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
	 * @since 1.0 Mailchimp Integration
	 *
	 * @param string|null $api_key API Key.
	 *
	 * @return Forminator_Mailchimp_Wp_Api|null
	 */
	public function get_api( $api_key = null ) {
		if ( is_null( $api_key ) ) {
			$api_key = $this->get_api_key( true );
		}
		$api = Forminator_Mailchimp_Wp_Api::get_instance( $api_key );
		return $api;
	}

	/**
	 * Get currently saved api key
	 *
	 * @since 1.0 Mailchimp Integration
	 *
	 * @param bool $decrypt Whether to decrypt the API key or not.
	 * @return string|null
	 */
	private function get_api_key( $decrypt = false ) {
		$setting_values = $this->get_settings_values( $decrypt );
		if ( isset( $setting_values['api_key'] ) ) {
			return $setting_values['api_key'];
		}

		return null;
	}

	/**
	 * Build settings help on settings
	 *
	 * @since 1.0 Mailchimp Integration
	 * @return string
	 */
	public function settings_help() {

		// Display how to get mailchimp API Key by default.
		/* Translators: 1. Opening <a> tag with link to the Mailchimp API Key, 2. closing <a> tag. */
		$help = sprintf( esc_html__( 'Please get your Mailchimp API key %1$shere%2$s', 'forminator' ), '<a href="https://admin.mailchimp.com/account/api-key-popup" target="_blank">', '</a>' );

		$help = '<span class="sui-description" style="margin-top: 20px;">' . $help . '</span>';

		$setting_values = $this->get_settings_values();

		if (
			isset( $setting_values['api_key'] )
			&& $setting_values['api_key']
			&& isset( $setting_values['connected_account'] )
			&& ! empty( $setting_values['connected_account'] )
		) {

			$connected_account = $setting_values['connected_account'];

			// Show currently connected mailchimp account if its already connected.
			/* translators:  placeholder is Name and Email of Connected MailChimp Account */
			$help = '<span class="sui-description" style="margin-top: 20px;">' . esc_html__( 'Change your API Key or disconnect this Mailchimp Integration below.', 'forminator' ) . '</span>';

		}

		return $help;
	}

	/**
	 * Settings description
	 *
	 * @return string
	 */
	public function settings_description() {

		$description = '';

		$setting_values = $this->get_settings_values();

		if (
			isset( $setting_values['api_key'] )
			&& $setting_values['api_key']
			&& isset( $setting_values['connected_account'] )
			&& ! empty( $setting_values['connected_account'] )
		) {

			// Show currently connected mailchimp account if its already connected.
			/* translators:  placeholder is Name and Email of Connected MailChimp Account */
			$description .= '<span class="sui-description">' . esc_html__( 'Please note that changing your API Key or disconnecting this integration will affect ALL of your connected forms.', 'forminator' ) . '</span>';

		}

		return $description;
	}

	/**
	 * Account info
	 *
	 * @return string
	 */
	public function settings_account() {

		$myaccount = '';

		$setting_values = $this->get_settings_values();

		if (
			isset( $setting_values['api_key'] )
			&& $setting_values['api_key']
			&& isset( $setting_values['connected_account'] )
			&& ! empty( $setting_values['connected_account'] )
		) {

			$connected_account = $setting_values['connected_account'];

			// Show currently connected mailchimp account if its already connected.
			$notice = sprintf(
				/* translators:  placeholder is Name and Email of Connected MailChimp Account */
				esc_html__( 'Your Mailchimp is connected to %1$s: %2$s.', 'forminator' ),
				'<strong>' . esc_html( $connected_account['account_name'] ) . '</strong>',
				sanitize_email( $connected_account['email'] )
			);

			$myaccount = Forminator_Admin::get_green_notice( $notice );

		}

		return $myaccount;
	}

	/**
	 * Flag if delete member on delete entry enabled
	 *
	 * Default is `true`,
	 * which can be changed via `FORMINATOR_ADDON_MAILCHIMP_ENABLE_DELETE_MEMBER` constant
	 *
	 * @return bool
	 */
	public static function is_enable_delete_member() {
		if ( defined( 'FORMINATOR_ADDON_MAILCHIMP_ENABLE_DELETE_MEMBER' ) && FORMINATOR_ADDON_MAILCHIMP_ENABLE_DELETE_MEMBER ) {
			return true;
		}

		return false;
	}

	/**
	 * Flag to show full if GDPR feature enabled
	 * GDPR is experimental feature on 1.0 version of this mailchimp integration
	 * And disabled by default to enable it set @see FORMINATOR_ADDON_MAILCHIMP_ENABLE_GDPR to true in wp-config.php
	 * Please bear in mind that currently its experimental, means not properly and thoroughly tested
	 *
	 * @since 1.0 Mailchimp Integration
	 * @return bool
	 */
	public static function is_enable_gdpr() {
		if ( defined( 'FORMINATOR_ADDON_MAILCHIMP_ENABLE_GDPR' ) && FORMINATOR_ADDON_MAILCHIMP_ENABLE_GDPR ) {
			return true;
		}

		return false;
	}

	/**
	 * Settings wizard
	 *
	 * @since 1.0 Mailchimp Integration
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
	 * @since 1.0 Mailchimp Integration
	 *
	 * @param array $submitted_data Submitted data.
	 * @param int   $form_id Form Id.
	 *
	 * @return array
	 */
	public function configure_api_key( $submitted_data, $form_id = 0 ) {
		$error_message         = '';
		$api_key_error_message = '';
		$setting_values        = $this->get_settings_values();
		$identifier            = '';
		$api_key               = $this->get_api_key();
		if ( ! empty( $setting_values['identifier'] ) ) {
			$identifier = $setting_values['identifier'];
		}

		// ON Submit.
		if ( isset( $submitted_data['api_key'] ) ) {
			$api_key           = $submitted_data['api_key'];
			$identifier        = isset( $submitted_data['identifier'] ) ? $submitted_data['identifier'] : '';
			$real_api_key      = $this->get_real_value( $api_key, 'api_key' );
			$api_key_validated = $this->validate_api_key( $real_api_key );

			/**
			 * Filter validating api key result
			 *
			 * @since 1.1
			 *
			 * @param bool   $api_key_validated
			 * @param string $api_key API Key to be validated.
			 */
			$api_key_validated = apply_filters( 'forminator_addon_mailchimp_validate_api_key', $api_key_validated, $api_key );

			$save_values = array(
				'api_key'    => $real_api_key,
				'identifier' => $identifier,
			);
			if ( ! $api_key_validated ) {
				$api_key_error_message = $this->_update_settings_error_message;
			} else {
				$show_success = true;
				if ( ! forminator_addon_is_active( $this->_slug ) ) {
					$activated = Forminator_Integration_Loader::get_instance()->activate_addon( $this->_slug );
					if ( ! $activated ) {
						$error_message = '<div class="sui-notice sui-notice-error"><p>' . Forminator_Integration_Loader::get_instance()->get_last_error_message() . '</p></div>';
						$show_success  = false;
					} else {
						$this->save_settings_values( $save_values );
					}
				} else {
					$this->save_settings_values( $save_values );
				}

				if ( $show_success ) {
					if ( ! empty( $form_id ) ) {
						// initiate form settings wizard.
						return $this->get_form_settings_wizard( array(), $form_id, 0, 0 );
					}

					return array(
						'html'         => $this->success_authorize(),
						'redirect'     => false,
						'has_errors'   => false,
						'notification' => array(
							'type' => 'success',
							'text' => '<strong>' . $this->get_title() . '</strong> ' . esc_html__( 'is connected successfully.', 'forminator' ),
						),
					);
				}
			}
		}

		$buttons = array();

		$is_edit = false;

		if ( $this->is_connected() ) {
			$is_edit = true;
		}

		if ( $is_edit ) {
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
			$html .= '<h3 id="dialogTitle2" class="sui-box-title sui-lg" style="overflow: initial; text-overflow: none; white-space: normal;">' . /* translators: 1: Add-on name */ sprintf( esc_html__( 'Configure %1$s', 'forminator' ), 'Mailchimp' ) . '</h3>';
			$html .= $this->settings_help();
			$html .= $error_message;
		$html     .= '</div>';
		$html     .= '<form>';
			// FIELD: API Key.
			$html     .= '<div class="sui-form-field ' . ( ! empty( $api_key_error_message ) ? 'sui-form-field-error' : '' ) . '">';
				$html .= '<label class="sui-label">' . esc_html__( 'API Key', 'forminator' ) . '</label>';
				$html .= '<div class="sui-control-with-icon">';
					/* translators: ... */
					$html .= '<input name="api_key" value="' . esc_attr( $api_key ) . '" placeholder="' . /* translators: 1: Add-on name */ sprintf( esc_html__( 'Enter %1$s API Key', 'forminator' ), 'Mailchimp' ) . '" class="sui-form-control" />';
					$html .= '<i class="sui-icon-key" aria-hidden="true"></i>';
				$html     .= '</div>';
				$html     .= ( ! empty( $api_key_error_message ) ? '<span class="sui-error-message">' . esc_html( $api_key_error_message ) . '</span>' : '' );
				$html     .= $this->settings_description();
			$html         .= '</div>';
			// FIELD: Identifier.
			$html     .= '<div class="sui-form-field">';
				$html .= '<label class="sui-label">' . esc_html__( 'Identifier', 'forminator' ) . '</label>';
				$html .= '<input name="identifier" value="' . esc_attr( $identifier ) . '" placeholder="' . esc_attr__( 'E.g., Business Account', 'forminator' ) . '" class="sui-form-control" />';
				$html .= '<span class="sui-description">' . esc_html__( 'Helps distinguish between integrations if connecting to the same third-party app with multiple accounts.', 'forminator' ) . '</span>';
			$html     .= '</div>';
		$html         .= '</form>';
		$html         .= $this->settings_account();

		return array(
			'html'       => $html,
			'buttons'    => $buttons,
			'redirect'   => false,
			'has_errors' => ! empty( $error_message ) || ! empty( $api_key_error_message ),
		);
	}

	/**
	 * AJAX load group interests
	 */
	public function ajax_group_interests() {
		forminator_validate_ajax( 'forminator_mailchimp_interests', false, 'forminator-integrations' );
		$html = '';
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput -- The nonce is verified before and sanitized in Forminator_Core::sanitize_array.
		$post_data = isset( $_POST['data'] ) ? Forminator_Core::sanitize_array( $_POST['data'], 'data' ) : array();
		$data      = array();
		wp_parse_str( $post_data, $data );
		$module_id   = $data['module_id'] ?? '';
		$module_type = $data['module_type'] ?? '';
		if ( $module_id ) {
			$module_settings_instance = $this->get_addon_settings( $module_id, $module_type );
			$html                     = $module_settings_instance->get_group_interests( $data );
		}

		wp_send_json_success( $html );
	}
}
