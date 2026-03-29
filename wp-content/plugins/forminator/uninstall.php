<?php
/**
 * Forminator Uninstall methods
 * Called when plugin is deleted
 *
 * @since 1.0.2
 * @package Forminator
 */

// if uninstall.php is not called by WordPress, die.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

/**
 * Drop custom tables
 *
 * @since 1.0.2
 * @since 1.14.10 Added $db_prefix parameter
 *
 * @param string $db_prefix - database prefix.
 */
function forminator_drop_custom_tables( $db_prefix = 'wp_' ) {
	global $wpdb;
	// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
	$wpdb->query( "DROP TABLE IF EXISTS {$db_prefix}frmt_form_entry" );
	$wpdb->query( "DROP TABLE IF EXISTS {$db_prefix}frmt_form_entry_meta" );
	$wpdb->query( "DROP TABLE IF EXISTS {$db_prefix}frmt_form_views" );
	// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
}

/**
 * Clear custom posts
 *
 * @since 1.0.2
 */
function forminator_delete_custom_posts() {
	$args     = array(
		'fields'         => 'ids',
		'post_type'      => array(
			'forminator_forms',
			'forminator_polls',
			'forminator_quizzes',
		),
		'post_status'    => 'any',
		'posts_per_page' => - 1,
	);
	$post_ids = get_posts( $args );

	include_once plugin_dir_path( __FILE__ ) . 'library/model/class-form-entry-model.php';

	foreach ( $post_ids as $post_id ) {
		wp_delete_post( $post_id, true );
		Forminator_Form_Entry_Model::delete_form_entry_cache( $post_id );
	}
}

/**
 * Delete custom options and addon options
 *
 * @since 1.0.2
 * @since 1.0.6 Delete privacy options
 * @since 1.14.10 Deletes all forminator options including the addons' options
 * @since 1.14.10 Added $db_prefix parameter
 *
 * @param string $db_prefix - database prefix.
 */
function forminator_delete_custom_options( $db_prefix = 'wp_' ) {
	global $wpdb;

	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery
	$forminator_options = $wpdb->get_results( "SELECT option_name FROM {$db_prefix}options WHERE option_name LIKE 'forminator_%'" );

	foreach ( $forminator_options as $option ) {
		delete_option( $option->option_name );
	}
}

/**
 * Clear the module submissions cache data
 *
 * @since 1.14.10 Added $db_prefix parameter
 *
 * @param string $db_prefix - database prefix.
 */
function forminator_clear_module_submissions( $db_prefix = 'wp_' ) {
	global $wpdb;

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
	$max_entry_id = $wpdb->get_var( 'SELECT MAX(`entry_id`) FROM ' . esc_sql( $db_prefix ) . 'frmt_form_entry' );

	if ( $max_entry_id && is_numeric( $max_entry_id ) && $max_entry_id > 0 ) {
		for ( $i = 1; $i <= $max_entry_id; $i++ ) {
			wp_cache_delete( $i, Forminator_Form_Entry_Model::FORM_ENTRY_CACHE_GROUP );
		}
	}

	wp_cache_delete( 'all_form_types', Forminator_Form_Entry_Model::FORM_COUNT_CACHE_GROUP );
	wp_cache_delete( 'custom-forms_form_type', Forminator_Form_Entry_Model::FORM_COUNT_CACHE_GROUP );
	wp_cache_delete( 'poll_form_type', Forminator_Form_Entry_Model::FORM_COUNT_CACHE_GROUP );
	wp_cache_delete( 'quizzes_form_type', Forminator_Form_Entry_Model::FORM_COUNT_CACHE_GROUP );
}

/**
 * Remove forminator files in uploads folder
 */
function forminator_remove_upload_files() {
	$upload_dir = wp_upload_dir();
	$folder     = $upload_dir['basedir'] . '/forminator/';
	$recursive  = true;
	if ( ! class_exists( 'WP_Filesystem_Direct', false ) ) {
		require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';
	}
	$filesystem = new WP_Filesystem_Direct( null );
	$filesystem->rmdir( $folder, $recursive );
}

$clear_data = get_option( 'forminator_uninstall_clear_data', false );

require_once plugin_dir_path( __FILE__ ) . 'library/class-core.php';
require_once plugin_dir_path( __FILE__ ) . 'constants.php';
require_once plugin_dir_path( __FILE__ ) . 'functions.php';
Forminator_Core::init_mixpanel();

/**
 * Action hook to run before plugin reset.
 *
 * @param bool $clear_data Uninstallation data settings reset or preserve
 */
do_action( 'forminator_before_uninstall', $clear_data );

if ( $clear_data ) {
	global $wpdb;
	include_once plugin_dir_path( __FILE__ ) . 'library/helpers/helper-core.php';

	if ( ! is_multisite() ) {
		$db_prefix = $wpdb->prefix;

		forminator_delete_permissions();
		forminator_delete_custom_options( $db_prefix );
		forminator_delete_custom_posts();
		forminator_clear_module_submissions( $db_prefix );
		forminator_remove_upload_files();
		forminator_drop_custom_tables( $db_prefix );
		Forminator_Core::action_scheduler_cleanup( $db_prefix );

	} else {
		$sites = get_sites();

		foreach ( $sites as $site ) {
			$blog_id_for_switch = $site->blog_id;
			$db_prefix          = $wpdb->get_blog_prefix( $blog_id_for_switch );

			forminator_delete_custom_posts();

			// Switch to blog before deleting options.
			switch_to_blog( $blog_id_for_switch );
			forminator_delete_permissions();
			forminator_delete_custom_options( $db_prefix );
			restore_current_blog();

			forminator_clear_module_submissions( $db_prefix );

			switch_to_blog( $blog_id_for_switch );
			forminator_remove_upload_files();
			restore_current_blog();

			forminator_drop_custom_tables( $db_prefix );
			Forminator_Core::action_scheduler_cleanup( $db_prefix );
		}
	}
}

/**
 * Action hook to run after plugin reset.
 *
 * @since 1.27.0
 */
do_action( 'forminator_after_uninstall' );
