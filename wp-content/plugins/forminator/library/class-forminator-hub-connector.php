<?php
/**
 * Forminator Hub Connector
 *
 * @package Forminator
 */

/**
 * Class Forminator_Hub_Connector
 * Handles the functionality related to the Hub Connector module.
 */
class Forminator_Hub_Connector {

	/**
	 * The identifier for the Forminator plugin in the Hub.
	 *
	 * @const string
	 */
	public const PLUGIN_IDENTIFIER = 'forminator';

	/**
	 * The action name used for the Hub connection.
	 *
	 * @const string
	 */
	public const CONNECTION_ACTION = 'hub_connection';

	/**
	 * The instance of this class.
	 *
	 * @var Forminator_Hub_Connector|null
	 */
	private static $instance;

	/**
	 * Forminator_Hub_Connector constructor.
	 */
	private function __construct() {
		add_filter( 'forminator_data', array( __CLASS__, 'add_hub_connector_data' ) );

		if ( forminator_cloud_templates_disabled() ) {
			return;
		}

		add_filter( 'wpmudev_hub_connector_localize_text_vars', array( __CLASS__, 'customize_text_vars' ), 10, 2 );
		$this->init();
	}

	/**
	 * Get instance of this class
	 *
	 * @return Forminator_Hub_Connector
	 */
	public static function get_instance(): Forminator_Hub_Connector {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Checks if Hub Connector is connected. If Dash plugin is not installed Hub connector can take over.
	 *
	 * @return bool
	 */
	public static function hub_connector_connected(): bool {
		static $connected = null;

		if ( is_null( $connected ) ) {
			$connected = false;
			if ( class_exists( 'WPMUDEV_Dashboard' ) ) {
				$connected = self::get_dashboard_api()->has_key();
			} elseif ( ! FORMINATOR_PRO ) {
				$connected = self::hub_connector_logged_in();
			}
		}

		return $connected;
	}

	/**
	 * Returns WPMUDEV Dashboard API object
	 *
	 * @return \WPMUDEV_Dashboard_Api|null
	 */
	public static function get_dashboard_api(): ?\WPMUDEV_Dashboard_Api {
		if ( class_exists( 'WPMUDEV_Dashboard' ) && ! empty( \WPMUDEV_Dashboard::$api ) ) {
			return \WPMUDEV_Dashboard::$api;
		}
		return null;
	}

	/**
	 * Checks if Hub Connector is logged in.
	 *
	 * @return bool
	 */
	public static function hub_connector_logged_in(): bool {
		return ! forminator_cloud_templates_disabled() && \WPMUDEV\Hub\Connector\API::get()->is_logged_in();
	}

	/**
	 * Adds the Hub connector data to the Forminator data.
	 *
	 * @param array $data The Forminator data.
	 *
	 * @return array The Forminator data with the Hub connector data.
	 */
	public static function add_hub_connector_data( array $data ): array {
		$data['isHubConnected']              = self::hub_connector_connected();
		$data['hubConnectLogo']              = esc_attr( self::get_hub_connect_logo() );
		$data['hubConnectTitle']             = esc_html( self::get_hub_connect_title() );
		$data['hubConnectDescription']       = esc_html( self::get_hub_connect_description() );
		$data['hubConnectUrl']               = esc_url( self::get_hub_connect_url() );
		$data['hubConnectPresetTemplateUrl'] = esc_url( self::get_hub_connect_url( 'preset-template' ) );
		$data['hubConnectButton']            = esc_html( self::get_hub_connect_cta_text() );

		return $data;
	}

	/**
	 * Modify text string vars.
	 *
	 * @param array  $texts  Vars.
	 * @param string $plugin Plugin identifier.
	 *
	 * @return array
	 */
	public static function customize_text_vars( $texts, $plugin ): array {
		if ( self::PLUGIN_IDENTIFIER === $plugin ) {
			$feature = filter_input( INPUT_GET, 'feature' );
			if ( 'preset-template' === $feature ) {
				$feature_name = esc_html__( 'Preset Templates', 'forminator' );
			} elseif ( 'extension-pack' === $feature ) {
				$feature_name = esc_html__( 'Extension Pack Add-on', 'forminator' );
			} else {
				$feature_name = esc_html__( 'Cloud Templates', 'forminator' );
			}
			$texts['create_account_desc'] = sprintf(
				/* translators: 1. Opened tag. 2. Closed tag. 3. Opened tag. 4. Closed tag. */
				esc_html__( 'Create a free account to connect your site to WPMU DEV and activate %1$sForminator - %5$s%2$s. %3$sIt`s fast, seamless, and free%4$s.', 'forminator' ),
				'<strong>',
				'</strong>',
				'<i>',
				'</i>',
				$feature_name
			);
			$texts['login_desc'] = sprintf(
				/* translators: %s: Feature name. */
				esc_html__( 'Log in with your WPMU DEV account credentials to activate %s.', 'forminator' ),
				$feature_name
			);
		}

		return $texts;
	}

	/**
	 * Initialize the Hub Connector module and set its options.
	 *
	 * The `extra/hub-connector/connector.php` file is required, and the options are set for the Hub Connector module.
	 *
	 * @return void
	 */
	public function init() {
		if ( class_exists( 'WPMUDEV\Hub\Connector' ) ) {
			$page          = filter_input( INPUT_GET, 'page' );
			$feature       = filter_input( INPUT_GET, 'feature' );
			$from          = filter_input( INPUT_GET, 'from' );
			$page_referral = filter_input( INPUT_GET, 'page_referral' );
			if ( $page && false !== strpos( $page, 'forminator-cform-wizard' ) ) {
				$utm_campaign = 'forminator_form-builder-wizard_hub-connector_cloud-templates';
			} elseif ( 'new-features-modal' === $from ) {
				$utm_campaign = 'forminator_upgrade_modal_hub-connector_preset-templates_connector';
			} elseif ( $page && false !== strpos( $page, 'forminator-templates' ) ) {
				if ( 'wp-dashboard' === $page_referral ) {
					$utm_campaign = 'forminator_global-notice_hub-connector';
				} elseif ( 'preset-template' === $feature ) {
					$utm_campaign = 'forminator_template-page_preset-templates_hub-connector';
				} else {
					$utm_campaign = 'forminator_template-page_cloud-templates_hub-connector';
				}
			} elseif ( 'forminator-settings' === $page ) {
				$utm_campaign = 'forminator_settings_general_hub-connector_connect-site';
			} elseif ( 'preset-template' === $feature ) {
				$utm_campaign = 'forminator_form-builder_hub-connector_preset-templates_connector';
			} elseif ( 'extension-pack' === $feature ) {
				switch ( $page_referral ) {
					case 'form_builder':
						$page_referral = 'forminator_form-builder_form-abandonment';
						break;
					case 'forms_page':
						$page_referral = 'forminator_form-page_form-overview';
						break;
					case 'report_page':
						$page_referral = 'forminator_form-report-page_report-overview';
						break;
					case 'report_widget':
						$page_referral = 'forminator_form-report-page_form-abandonment-widget';
						break;
					default:
						$page_referral = 'forminator_addons-page';
						break;
				}
				$utm_campaign = $page_referral . '-free-addon_hub-connector';
			} else {
				$utm_campaign = 'forminator_form-builder_hub-connector_cloud-templates_connector';
			}

			$options = array(
				'screens'    => array(
					'forminator_page_forminator-settings',
					'forminator_page_forminator-templates',
					'forminator-pro_page_forminator-templates',
					'forminator_page_forminator-addons',
					'forminator-pro_page_forminator-addons',
					'forminator_page_forminator-cform',
					'forminator-pro_page_forminator-cform',
					'forminator_page_forminator-cform-wizard',
					'forminator-pro_page_forminator-cform-wizard',
					'toplevel_page_forminator',
				),
				'extra_args' => array(
					'register' => array(
						'utm_medium'   => 'plugin',
						'utm_campaign' => $utm_campaign,
						'utm_content'  => 'hub-connector',
						'utm_source'   => self::PLUGIN_IDENTIFIER,
					),
				),
			);

			\WPMUDEV\Hub\Connector::get()->set_options( self::PLUGIN_IDENTIFIER, $options );
		}
	}

	/**
	 * Check if WPMUDEV Dashboard is installed
	 *
	 * @return bool
	 */
	public static function is_wpmudev_dashboard_installed(): bool {
		return self::get_dashboard_api() instanceof WPMUDEV_Dashboard_Api;
	}


	/**
	 * Get Hub connect URL
	 *
	 * @param string $feature Feature name, default is 'cloud'.
	 * @return string
	 */
	public static function get_hub_connect_url( $feature = 'cloud' ): string {
		$page = filter_input( INPUT_GET, 'page' );
		if ( empty( $page ) ) {
			$page = 'forminator-templates';
		}
		if ( self::is_wpmudev_dashboard_installed() ) {
			return add_query_arg(
				array(
					'page'         => 'wpmudev',
					'utm_source'   => self::PLUGIN_IDENTIFIER,
					'utm_medium'   => 'plugin',
					'utm_campaign' => $page,
				),
				network_admin_url()
			);
		}
		$args = array(
			'page'    => $page,
			'feature' => $feature,
		);
		if ( 'wp-dashboard-preset-template' === $feature ) {
			$args['feature']       = 'preset-template';
			$args['page_referral'] = 'wp-dashboard';
		}

		$tab = filter_input( INPUT_GET, 'tab' );
		if ( $tab ) {
			$args['tab'] = $tab;
		}
		$id = filter_input( INPUT_GET, 'id' );
		if ( $id ) {
			$args['id'] = $id;
		}
		$page_referral = filter_input( INPUT_GET, 'page_referral' );
		if ( $page_referral ) {
			$args['page_referral'] = $page_referral;
		}

		$args['page_action'] = self::CONNECTION_ACTION;

		$url = add_query_arg(
			$args,
			admin_url( 'admin.php' )
		);
		return apply_filters( 'forminator_hub_connect_url', $url, $args );
	}

	/**
	 * Get Hub connect CTA text
	 *
	 * @return string
	 */
	public static function get_hub_connect_cta_text(): string {
		if ( self::is_wpmudev_dashboard_installed() ) {
			return __( 'LOG IN TO WPMU DEV', 'forminator' );
		}

		return apply_filters( 'forminator_hub_connect_cta_text', __( 'Connect site', 'forminator' ) );
	}

	/**
	 * Get Hub connect title
	 *
	 * @return string
	 */
	public static function get_hub_connect_title(): string {
		return apply_filters( 'forminator_hub_connect_title', __( 'Save Forms as Templates', 'forminator' ) );
	}

	/**
	 * Get Hub connect description
	 *
	 * @return string
	 */
	public static function get_hub_connect_description(): string {
		return apply_filters( 'forminator_hub_connect_description', __( 'Save your forms as templates in The Hub cloud to easily reuse them on any sites you manage via The Hub. Customize once and reuse on different sites with one click.', 'forminator' ) );
	}

	/**
	 * Get Hub connect logo
	 *
	 * @return string
	 */
	public static function get_hub_connect_logo(): string {
		return apply_filters( 'forminator_hub_connect_logo', 'forminator-templates' );
	}
}
