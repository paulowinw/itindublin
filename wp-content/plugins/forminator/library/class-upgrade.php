<?php
/**
 * Forminator Upgrade
 *
 * @package Forminator
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_Upgrade
 *
 * Handle any installation upgrade or install tasks
 */
class Forminator_Upgrade {

	/**
	 * Initialise data before plugin is fully loaded
	 *
	 * @since 1.0
	 */
	public static function init() {
		/**
		 * Initialize the plugin data
		 */
		$old_version = get_option( 'forminator_version', false );
		if ( $old_version ) {
			$version_changed = version_compare( $old_version, FORMINATOR_VERSION, 'lt' );

			if ( $version_changed ) {
				update_option( 'forminator_version_upgraded', true );
				// Set last updated date.
				update_site_option( 'forminator_last_updated_date', time() );

				$activation_date      = get_site_option( 'forminator_first_activation_date' );
				$last_activation_date = get_site_option( 'forminator_last_activation_date' );
				// Set activation dates if not set already.
				if ( empty( $activation_date ) || empty( $last_activation_date ) ) {
					Forminator::set_activation_dates();
				}
			}
		} else {
			$version_changed = true;
		}
		if ( $version_changed ) {
			// Update tables if required.
			Forminator_Database_Tables::install_database_tables();

			Forminator_Database_Tables::insert_default_entries();

			// Run status migration on version update.
			add_action( 'forminator_update_version', array( 'Forminator_Database_Tables', 'maybe_migrate_entry_status' ), 10, 2 );

			add_action( 'admin_init', array( __CLASS__, 'flush_rewrite' ) );

			// Update version.
			update_option( 'forminator_version', FORMINATOR_VERSION );

			add_action(
				'forminator_loaded',
				function () use ( $old_version ) {
					/**
					 * Triggered when Forminator version is updated
					 *
					 * @param string FORMINATOR_VERSION New plugin version
					 * @param string $old_version Old plugin version.
					 */
					do_action( 'forminator_update_version', FORMINATOR_VERSION, $old_version );
				}
			);

			if ( $old_version ) {
				self::maybe_enable_place_api_notice( $old_version );
			}
		}
	}

	/**
	 * Enable notice for Place API update if the old version is less than 1.51.0.
	 *
	 * @param string $old_version Old plugin version.
	 */
	private static function maybe_enable_place_api_notice( $old_version ) {
		// Show notice for Place API update if the old version is less than 1.51.0-alpha.
		if ( version_compare( $old_version, '1.51.0-alpha', 'lt' ) ) {
			$geolocation_settings = get_option( 'forminator_geolocation_settings', array() );
			if ( ! empty( $geolocation_settings['api_key'] ) ) {
				update_option( 'forminator_geolocation_update_place_api_notice', true );
			}
		}
	}

	/**
	 * Flush rewrite
	 *
	 * @return void
	 */
	public static function flush_rewrite() {
		// Flush rewrite rules.
		flush_rewrite_rules();
	}
}
