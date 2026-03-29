<?php
/**
 * The Forminator_Mixpanel_Settings class.
 *
 * @package Forminator
 */

/**
 * Mixpanel Settings Events class
 */
class Forminator_Mixpanel_Settings extends Events {

	/**
	 * Initialize class.
	 *
	 * @since 1.27.0
	 */
	public static function init() {
		add_action( 'forminator_disable_usage_tracking', array( __CLASS__, 'tracking_settings_disabled' ) );
		add_action( 'forminator_enable_usage_tracking', array( __CLASS__, 'tracking_settings_enabled' ) );
		add_action( 'forminator_before_reset_settings', array( __CLASS__, 'tracking_settings_reset' ) );
		add_action( 'forminator_before_uninstall', array( __CLASS__, 'tracking_plugin_uninstall' ) );
		add_action( 'deactivated_plugin', array( __CLASS__, 'tracking_deactivate' ) );
		add_action( 'forminator_before_stripe_connected', array( __CLASS__, 'tracking_stripe_rak_use' ), 10, 5 );
		add_action( 'forminator_after_stripe_migrated', array( __CLASS__, 'tracking_stripe_migrated' ) );
		add_action( 'forminator_auto_save_setting', array( __CLASS__, 'tracking_auto_save' ) );
	}

	/**
	 * Track stripe migrated.
	 *
	 * @return void
	 */
	public static function tracking_stripe_migrated() {
		self::track_event( 'stripe_field_migrated', array() );
	}

	/**
	 * Handle settings reset.
	 *
	 * We need to opt out after settings reset.
	 *
	 * @return void
	 * @since 1.27.0
	 */
	public static function tracking_settings_reset() {
		self::track_opt_toggle( false, 'Data Reset' );
	}

	/**
	 * Handle Plugin Deactivate.
	 *
	 * We need to opt out after plugin deactivate.
	 *
	 * @param mixed $plugin Plugin name.
	 *
	 * @return void
	 * @since 1.27.0
	 */
	public static function tracking_deactivate( $plugin ) {
		// Only if Forminator plugin.
		if ( FORMINATOR_PLUGIN_BASENAME !== $plugin ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$action = isset( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) : '';

		$triggered_from = 'Unknown';

		// Deactivated from WPMUDEV Dashboard.
		if ( 'wdp-project-deactivate' === $action ) {
			$triggered_from = 'Plugin deactivation - dashboard';
		} elseif ( 'deactivate' === $action ) {
			// Deactivated from WP plugins page.
			$triggered_from = 'Plugin deactivation - wpadmin';
		}

		self::track_opt_toggle( false, $triggered_from );
	}

	/**
	 * Handle settings uninstall.
	 *
	 * We need to opt out after plugin uninstall if not keep settings.
	 *
	 * @param bool $keep_settings Determine whether to save current settings for next time, or reset them.
	 *
	 * @return void
	 *
	 * @since 1.27.0
	 */
	public static function tracking_plugin_uninstall( $keep_settings ) {
		// Opt out only if it doesn't require to keep settings.
		if ( $keep_settings ) {
			self::track_opt_toggle( false, 'Data Reset' );
		}
	}

	/**
	 * Handle settings enabling.
	 *
	 * @param string $triggered_from Source of the action.
	 * @return void
	 */
	public static function tracking_settings_enabled( $triggered_from ) {
		self::track_opt_toggle( true, $triggered_from );
		// Track common data.
		$properties = Forminator_Mixpanel::module_updates( 'opt-in' );
		self::track_event( 'for_module_updated', $properties );
	}

	/**
	 * Handle settings disabling.
	 *
	 * @param string $triggered_from Source of the action.
	 * @return void
	 */
	public static function tracking_settings_disabled( $triggered_from = 'Disable Tracking' ) {
		self::track_opt_toggle( false, $triggered_from );
	}

	/**
	 * Track data tracking opt in and opt out.
	 *
	 * @param bool   $active Toggle value.
	 * @param string $method method.
	 *
	 * @return void
	 * @since 1.27.0
	 */
	private static function track_opt_toggle( $active, $method ) {
		$properties = array( 'Method' => $method );

		self::tracker()->track( $active ? 'Opt In' : 'Opt Out', $properties );
	}

	/**
	 * Track stripe RAK use.
	 *
	 * @param string $test_key Test API key.
	 * @param string $test_secret Test Secret/Restricted Key.
	 * @param string $live_key Live API key.
	 * @param string $live_secret Live Secret/Restricted Key.
	 * @param string $page_slug Page slug.
	 *
	 * @return void
	 */
	public static function tracking_stripe_rak_use( string $test_key, string $test_secret, string $live_key, string $live_secret, $page_slug ) {
		// Process only if the keys are Restricted API Keys.
		if ( ( ! empty( $test_secret ) && 'rk_' === substr( $test_secret, 0, 3 ) ) && ( ! empty( $live_secret ) && 'rk_' === substr( $live_secret, 0, 3 ) ) ) {
			$config             = get_option( 'forminator_stripe_configuration', array() );
			$config_test_secret = $config['test_secret'] ?? '';
			$config_live_secret = $config['live_secret'] ?? '';

			// Ignore if already switched to Restricted API Keys.
			if ( ( ! empty( $config_test_secret ) && 'rk_' === substr( $config_test_secret, 0, 3 ) ) && ( ! empty( $config_live_secret ) && 'rk_' === substr( $config_live_secret, 0, 3 ) ) ) {
				return;
			}

			$properties['triggered_from'] = $page_slug;
			if ( 'forminator-settings' === $page_slug ) {
				$properties['triggered_from'] = 'Settings';
			} elseif ( 'forminator-addons' === $page_slug ) {
				$properties['triggered_from'] = 'Stripe Subscription Add-On';
			}

			$properties['switch_from_secret_key'] = 'No';
			if ( ( ! empty( $config_test_secret ) && 'sk_' === substr( $config_test_secret, 0, 3 ) ) || ( ! empty( $config_live_secret ) && 'sk_' === substr( $config_live_secret, 0, 3 ) ) ) {
				$properties['switch_from_secret_key'] = 'Yes';
			}

			self::track_event( 'for_stripe_rak_use', $properties );
		}
	}

	/**
	 * Track Auto Save setting.
	 *
	 * @param bool $auto_save Auto Save status.
	 *
	 * @return void
	 */
	public static function tracking_auto_save( bool $auto_save ) {
		$settings = array( 'Auto Save' => $auto_save ? 'Enabled' : 'Disabled' );
		self::track_event( 'for_general_settings_update', $settings );
	}
}
