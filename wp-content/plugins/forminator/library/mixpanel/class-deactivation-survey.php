<?php
/**
 * The Forminator_Mixpanel_Deactivation_Survey class.
 *
 * @package Forminator
 */

/**
 * Mixpanel Deactivation Survey Events class
 */
class Forminator_Mixpanel_Deactivation_Survey extends Events {

	/**
	 * Initialize class.
	 *
	 * @since 1.50.0
	 */
	public static function init() {
		add_action( 'forminator_share_deactivation_survey_to_mixpanel', array( __CLASS__, 'share_deactivation_survey' ), 10, 4 );
	}

	/**
	 * Share deactivation survey
	 *
	 * @param string $reason Reason.
	 * @param string $action Model action.
	 * @param string $requested_assistance Requested assistance.
	 * @param string $message Message.
	 *
	 * @return void
	 * @since 1.50.0
	 */
	public static function share_deactivation_survey( $reason, $action, $requested_assistance, $message ) {
		$reason_options               = array(
			'temporary_user',
			'found_better',
			'temp_deactivation',
			'not_working',
			'technical_issues',
			'missing_features',
			'other',
			'not_set',
		);
		$action_options               = array(
			'Skip',
			'Submit',
		);
		$requested_assistance_options = array(
			'na',
			'no',
			'yes',
		);
		if ( ! in_array( $reason, $reason_options, true )
			|| ! in_array( $action, $action_options, true )
			|| ! in_array( $requested_assistance, $requested_assistance_options, true ) ) {
			return;
		}

		// Get timestamp options and format to ISO 8601 UTC.
		$first_activation = get_site_option( 'forminator_first_activation_date', false );
		$last_activation  = get_site_option( 'forminator_last_activation_date', false );
		$last_updated     = get_site_option( 'forminator_last_updated_date', false );

		$properties = array(
			'Modal Action'          => $action,
			'Requested Assistance'  => $requested_assistance,
			'Tracking Status'       => Forminator_Core::is_tracking_active() ? 'opted_in' : 'opted_out',
			'published_forms'       => forminator_cforms_total( 'publish' ),
			'submissions_count'     => Forminator_Form_Entry_Model::count_all_entries_by_type(),
			'active_plugins'        => self::get_active_plugins(),
			'first_activation_date' => $first_activation ? gmdate( 'Y-m-d\\TH:i:s', $first_activation ) : '',
			'last_activation_date'  => $last_activation ? gmdate( 'Y-m-d\\TH:i:s', $last_activation ) : '',
			'last_updated'          => $last_updated ? gmdate( 'Y-m-d\\TH:i:s', $last_updated ) : '',
		);

		if ( 'Submit' === $action ) {
			$properties['Reason']  = $reason;
			$properties['Message'] = ! empty( $message ) ? self::limit_text( $message ) : '';
		}

		self::track_event(
			'deactivation_survey',
			$properties
		);
	}

	/**
	 * Get active plugins
	 *
	 * @return string[]
	 */
	private static function get_active_plugins() {
		$active_plugins      = array();
		$active_plugin_files = self::get_active_and_valid_plugin_files();
		foreach ( $active_plugin_files as $plugin_file ) {
			$plugin_file_path = WP_PLUGIN_DIR . '/' . $plugin_file;
			$plugin_name      = self::get_plugin_name( $plugin_file_path );
			if ( $plugin_name ) {
				$active_plugins[] = $plugin_name;
			}
		}

		return $active_plugins;
	}

	/**
	 * Get active and valid plugin files
	 *
	 * @return array
	 */
	private static function get_active_and_valid_plugin_files() {
		$active_plugins = get_option( 'active_plugins', array() );
		if ( is_multisite() ) {
			$network_active_plugins = get_site_option( 'active_sitewide_plugins', array() );
			$active_plugins         = array_merge( $active_plugins, array_keys( $network_active_plugins ) );
		}

		return array_unique( $active_plugins );
	}

	/**
	 * Get plugin name from plugin file
	 *
	 * @param string $plugin_file Plugin file.
	 * @return string
	 */
	private static function get_plugin_name( $plugin_file ) {
		$plugin_data = get_plugin_data( $plugin_file, false, false );

		return ! empty( $plugin_data['Name'] ) ? $plugin_data['Name'] : '';
	}
}
