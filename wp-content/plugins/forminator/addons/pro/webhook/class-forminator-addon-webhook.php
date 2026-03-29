<?php
/**
 * Forminator Addon Webhook
 *
 * @package Forminator
 */

// Include addon-webhook-wp-api.
require_once __DIR__ . '/lib/class-forminator-addon-webhook-wp-api.php';

/**
 * Class Forminator_Webhook
 * Webhook Integration Main Class
 */
final class Forminator_Webhook extends Forminator_Integration {

	/**
	 * Forminator_Webhook Instance
	 *
	 * @var self|null
	 */
	protected static $instance = null;

	/**
	 * Slug
	 *
	 * @var string
	 */
	protected $_slug = 'webhook';

	/**
	 * Webhook version
	 *
	 * @var string
	 */
	protected $_version = FORMINATOR_ADDON_WEBHOOK_VERSION;

	/**
	 * Forminator minimum version
	 *
	 * @var string
	 */
	protected $_min_forminator_version = '1.1';

	/**
	 * Short title
	 *
	 * @var string
	 */
	protected $_short_title = 'Webhook';

	/**
	 * Title
	 *
	 * @var string
	 */
	protected $_title = 'Webhook';

	/**
	 * Documentation URL
	 *
	 * @var string
	 */
	protected $_documentation = 'https://wpmudev.com/docs/wpmu-dev-plugins/forminator/#webhook';

	/**
	 * Position
	 *
	 * @var int
	 */
	protected $_position = 0;

	/**
	 * Forminator_Webhook constructor.
	 */
	public function __construct() {
		// late init to allow translation.
		$this->_description = esc_html__( 'Get awesome by your form.', 'forminator' );
		$this->_promotion   = sprintf(
		/* translators: 1: Zapier link 2. Closing a tag 3. Integrately link 4. Tray.io link 5. Make.com link 6. Workato link 7. n8n link */
			esc_html__( 'Connect Forminator with automation tools through webhook. You can use this to send submissions to automation apps like %7$sn8n%2$s, %1$sZapier%2$s, %3$sIntegrately%2$s, %4$sTray.io%2$s, %5$sMake%2$s, %6$sWorkato%2$s, and other automation tools that support webhooks.', 'forminator' ),
			'<a href="https://zapier.com/" target="_blank">',
			'</a>',
			'<a href="https://integrately.com/" target="_blank">',
			'<a href="https://tray.io/" target="_blank">',
			'<a href="https://www.make.com/" target="_blank">',
			'<a href="https://www.workato.com/" target="_blank">',
			'<a href="https://n8n.io/" target="_blank">',
		);
	}

	/**
	 * Setting apier Integration
	 *
	 * @return array
	 */
	public function settings_wizards() {
		return array(
			array(
				'callback'     => array( $this, 'setup_connect' ),
				'is_completed' => array( $this, 'is_connected' ),
			),
		);
	}

	/**
	 * Activate Webhook
	 *
	 * @param array $submitted_data Submitted data.
	 * @param int   $form_id Form Id.
	 *
	 * @return array
	 * @throws Forminator_Integration_Exception Throws Integration Exception.
	 */
	public function setup_connect( $submitted_data, $form_id = 0 ) {
		$settings_values = $this->get_settings_values();
		$template        = forminator_addon_webhook_dir() . 'views/settings/setup-connect.php';

		$template_params = array(
			'is_connected'  => $this->is_connected(),
			'error_message' => '',
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
			$connect = isset( $submitted_data['connect'] ) ? $submitted_data['connect'] : '';

			try {
				if ( empty( $connect ) ) {
					throw new Forminator_Integration_Exception( esc_html__( 'Please Connect Webhook', 'forminator' ) );
				}

				if ( ! forminator_addon_is_active( $this->_slug ) ) {
					$activated = Forminator_Integration_Loader::get_instance()->activate_addon( $this->_slug );
					if ( ! $activated ) {
						throw new Forminator_Integration_Exception( Forminator_Integration_Loader::get_instance()->get_last_error_message() );
					}
				}
				// no form_id its on global settings.
				if ( empty( $form_id ) ) {
					$show_success = true;
				}
			} catch ( Forminator_Integration_Exception $e ) {
				$template_params['error_message'] = $e->getMessage();
				$has_errors                       = true;
			}
		}

		if ( $show_success ) {
			$html = $this->success_authorize();
		} else {
			if ( $this->is_connected() ) {
				$buttons['disconnect'] = array(
					'markup' => self::get_button_markup( esc_html__( 'Disconnect', 'forminator' ), 'sui-button-ghost forminator-addon-disconnect forminator-integration-popup__close' ),
				);
			} else {
				$buttons['submit'] = array(
					'markup' => self::get_button_markup( esc_html__( 'Activate', 'forminator' ), 'forminator-addon-connect forminator-integration-popup__close' ),
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
	 * Authorized Callback
	 *
	 * @return bool
	 */
	public function is_authorized() {
		return true;
	}

	/**
	 * Get Webhook API
	 *
	 * @param string $endpoint Endpoint.
	 *
	 * @return Forminator_Webhook_Wp_Api|null
	 */
	public function get_api( $endpoint ) {
		return Forminator_Webhook_Wp_Api::get_instance( $endpoint );
	}

	/**
	 * Allow multiple connection on one form
	 *
	 * @return bool
	 */
	public function is_allow_multi_on_form() {
		return true;
	}

	/**
	 * Allow multiple connection on one poll
	 *
	 * @since 1.6.1
	 * @return bool
	 */
	public function is_allow_multi_on_poll() {
		return true;
	}

	/**
	 * Allow multiple connection on one quiz
	 *
	 * @since 1.6.2
	 * @return bool
	 */
	public function is_allow_multi_on_quiz() {
		return true;
	}
}
