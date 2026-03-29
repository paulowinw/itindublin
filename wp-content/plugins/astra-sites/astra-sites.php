<?php
/**
 * Plugin Name: Starter Templates
 * Plugin URI: https://wpastra.com/
 * Description: Starter Templates is all in one solution for complete starter sites, single page templates, blocks & images. This plugin offers the premium library of ready templates & provides quick access to beautiful Pixabay images that can be imported in your website easily.
 * Version: 4.4.52
 * Author: Brainstorm Force
 * Author URI: https://www.brainstormforce.com
 * Text Domain: astra-sites
 *
 * @package Astra Sites
 */

// Check PHP version before loading the plugin.
if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
	add_action( 'admin_notices', 'astra_sites_php_version_notice' );
	return;
}

/**
 * Display notice if PHP version is below 7.4
 *
 * @return void
 */
function astra_sites_php_version_notice() {
	$plugin_name = 'Starter Templates';
	?>
	<div class="error">
		<p><?php echo esc_html( $plugin_name . ' requires PHP version 7.4 or higher. Please upgrade your PHP version.' ); ?></p>
	</div>
	<?php
}

/**
 * Set constants.
 */
if ( ! defined( 'ASTRA_SITES_NAME' ) ) {
	define( 'ASTRA_SITES_NAME', 'Starter Templates' );
}

if ( ! defined( 'ASTRA_SITES_VER' ) ) {
	define( 'ASTRA_SITES_VER', '4.4.52' );
}

if ( ! defined( 'ASTRA_SITES_FILE' ) ) {
	define( 'ASTRA_SITES_FILE', __FILE__ );
}

if ( ! defined( 'ASTRA_SITES_BASE' ) ) {
	define( 'ASTRA_SITES_BASE', plugin_basename( ASTRA_SITES_FILE ) );
}

if ( ! defined( 'ASTRA_SITES_DIR' ) ) {
	define( 'ASTRA_SITES_DIR', plugin_dir_path( ASTRA_SITES_FILE ) );
}

if ( ! defined( 'ASTRA_SITES_URI' ) ) {
	define( 'ASTRA_SITES_URI', plugins_url( '/', ASTRA_SITES_FILE ) );
}

// Load One Onboarding.
if ( file_exists( ASTRA_SITES_DIR . 'inc/lib/one-onboarding/loader.php' ) ) {
	require_once ASTRA_SITES_DIR . 'inc/lib/one-onboarding/loader.php';
}

// Load AI Builder.
$ai_builder_path = ASTRA_SITES_DIR . 'inc/lib/ai-builder/ai-builder.php';
if ( file_exists( $ai_builder_path ) ) {
	require_once $ai_builder_path;
}

// Load ST Importer.
$st_importer_path = ASTRA_SITES_DIR . 'inc/lib/starter-templates-importer/starter-templates-importer.php';
if ( file_exists( $st_importer_path ) ) {
	require_once $st_importer_path;
}

// Load Getting Started.
if ( file_exists( ASTRA_SITES_DIR . 'inc/lib/getting-started/getting-started.php' ) ) {
	require_once ASTRA_SITES_DIR . 'inc/lib/getting-started/getting-started.php';
}

if ( ! function_exists( 'astra_sites_setup' ) ) :

	/**
	 * Astra Sites Setup
	 *
	 * @since 1.0.5
	 * @return void
	 */
	function astra_sites_setup() {
		require_once ASTRA_SITES_DIR . 'inc/classes/class-astra-sites.php';

		// Admin.
		require_once ASTRA_SITES_DIR . 'classes/class-astra-sites-admin.php';
	}

	add_action( 'plugins_loaded', 'astra_sites_setup' );

endif;

// Astra Notices.
require_once ASTRA_SITES_DIR . 'inc/lib/astra-notices/class-astra-notices.php';

// BSF Analytics Tracker.
if ( ! class_exists( 'BSF_Analytics_Loader' ) ) {
	require_once ASTRA_SITES_DIR . 'admin/bsf-analytics/class-bsf-analytics-loader.php';
}

// BSF_Quick_Links.
if ( ! class_exists( 'BSF_Quick_Links' ) ) {
	require_once ASTRA_SITES_DIR . 'inc/lib/bsf-quick-links/class-bsf-quick-links.php';
}

add_action( 'init', 'astra_sites_init_bsf_analytics', 5 );

/**
 * Initializes BSF Analytics.
 *
 * @since 4.4.14
 * @return void
 */
function astra_sites_init_bsf_analytics() {
	if ( ! class_exists( 'BSF_Analytics_Loader' ) || ! is_callable( 'BSF_Analytics_Loader::get_instance' ) ) {
		return;
	}

	$bsf_analytics = BSF_Analytics_Loader::get_instance();

	$bsf_analytics->set_entity(
		array(
			'astra_sites' => array(
				'product_name'        => __( 'Starter Templates', 'astra-sites' ),
				'path'                => ASTRA_SITES_DIR . 'admin/bsf-analytics',
				'author'              => 'Brainstorm Force',
				'time_to_display'     => '+24 hours',
				'deactivation_survey' => apply_filters(
					'astra_sites_bsf_analytics_deactivation_survey_data',
					array(
						array(
							'id'                => 'deactivation-survey-astra-sites',
							'popup_logo'        => ASTRA_SITES_URI . 'inc/lib/onboarding/assets/images/logo.svg',
							'plugin_slug'       => 'astra-sites',
							'plugin_version'    => ASTRA_SITES_VER,
							'popup_title'       => __( 'Quick Feedback', 'astra-sites' ),
							'support_url'       => 'https://wpastra.com/starter-templates-support/',
							'popup_description' => __( 'If you have a moment, please share why you are deactivating Starter Templates:', 'astra-sites' ),
							'show_on_screens'   => array( 'plugins' ),
						),
					)
				),
			),
		)
	);
}

if ( ! function_exists( 'astra_sites_redirect_to_onboarding' ) ) :

	/**
	 * Redirect to onboarding.
	 *
	 * @since 3.3.0
	 * @return void
	 */
	function astra_sites_redirect_to_onboarding() {
		if ( ! get_option( 'st_start_onboarding', false ) ) {
			return;
		}

		delete_option( 'st_start_onboarding' );
		if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
			
			if ( ! class_exists( 'Astra_Sites' ) ) {
				require_once ASTRA_SITES_DIR . '/inc/classes/class-astra-sites.php';
			}
			// Use helper function to get URL with appropriate ci parameter based on flags.
			/* @phpstan-ignore-next-line staticMethod.notFound */
			$redirect_url = Astra_Sites::get_starter_templates_url();
			wp_safe_redirect( $redirect_url );
			exit();
		}
	}

	add_action( 'admin_init', 'astra_sites_redirect_to_onboarding' );

endif;

if ( ! function_exists( 'astra_pro_sites_activate' ) ) :

	/**
	 * Astra pro sites activate.
	 *
	 * @since 4.1.2
	 * @return void
	 */
	function astra_pro_sites_activate() {
		update_option( 'st_start_onboarding', true );
	}
	register_activation_hook( __FILE__, 'astra_pro_sites_activate' );

endif;
