<?php
/**
 * The plugin functions.
 *
 * @package    Forminator
 * @subpackage Functions
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! function_exists( 'forminator' ) ) {
	/**
	 * Forminator instance.
	 */
	function forminator() {
		return Forminator::get_instance();
	}

	/**
	 * Init the plugin and load the plugin instance
	 *
	 * @since 1.0
	 * Priority is set to 4 to support Gutenberg blocks.
	 * Priority is set to 0 to support Forminator widget.
	 */
	add_action( 'init', 'forminator', 0 );
}

/**
 * Return plugin URL
 *
 * @since 1.0
 * @return string
 */
function forminator_plugin_url() {
	return trailingslashit( plugin_dir_url( __FILE__ ) );
}

/**
 * Return plugin path
 *
 * @since 1.0
 * @return string
 */
function forminator_plugin_dir() {
	return trailingslashit( plugin_dir_path( __FILE__ ) );
}

/**
 * Return plugin path
 *
 * @since 1.0.5
 * @return string
 */
function forminator_addons_dir() {
	return trailingslashit( forminator_plugin_dir() . 'addons' );
}

/**
 * Check if payments functionality are disabled
 *
 * @return bool
 */
function forminator_payments_disabled(): bool {
	return apply_filters( 'forminator_payments_disabled', false );
}

/**
 * Check if form abandonment functionality are disabled
 *
 * @return bool
 */
function forminator_form_abandonment_disabled(): bool {
	return apply_filters( 'forminator_form_abandonment_disabled', true );
}


/**
 * Check if addons functionality are disabled
 *
 * @since 1.46.0
 * @return boolean
 */
function forminator_addons_disabled() {
	return apply_filters( 'forminator_addons_disabled', false );
}

/**
 * Check if creating index files is disabled
 *
 * @since 1.48.0
 * @return boolean
 */
function forminator_create_index_file_disabled() {
	return apply_filters( 'forminator_create_index_file_disabled', false );
}

/**
 * Check if CSS regeneration is disabled when enqueuing the CSS file.
 *
 * @since 1.48.0
 * @return boolean
 */
function forminator_disable_regenerate_css_on_form_load() {
	return apply_filters( 'forminator_disable_regenerate_css_on_form_load', false );
}

/**
 * Check if usage tracking functionality are disabled
 *
 * @since 1.48.0
 * @return boolean
 */
function forminator_usage_tracking_disabled() {
	return apply_filters( 'forminator_usage_tracking_disabled', false );
}

/**
 * Check if feedback functionality is disabled.
 *
 * @since 1.49.0
 * @return boolean
 */
function forminator_feedback_disabled() {
	return apply_filters( 'forminator_feedback_disabled', false );
}
