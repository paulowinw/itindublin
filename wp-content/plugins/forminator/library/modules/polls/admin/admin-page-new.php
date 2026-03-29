<?php
/**
 * The Forminator_Poll_New_Page class.
 *
 * @package Forminator
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_Poll_New_Page
 *
 * @since 1.0
 */
class Forminator_Poll_New_Page extends Forminator_Admin_Page {

	/**
	 * Return wizard title
	 *
	 * @since 1.0
	 * @return mixed
	 */
	public function getWizardTitle() {
		$id = filter_input( INPUT_GET, 'id', FILTER_VALIDATE_INT );
		if ( $id ) {
			return esc_html__( 'Edit Poll', 'forminator' );
		} else {
			return esc_html__( 'New Poll', 'forminator' );
		}
	}

	/**
	 * Add page screen hooks
	 *
	 * @since 1.6.1
	 *
	 * @param string $hook Hook.
	 */
	public function enqueue_scripts( $hook ) {
		// Load admin scripts.
		wp_register_script(
			'forminator-admin',
			forminator_plugin_url() . 'build/poll-scripts.js',
			array(
				'jquery',
				'wp-color-picker',
				'react',
				'react-dom',
				'wp-element',
			),
			FORMINATOR_VERSION,
			true
		);
		forminator_common_admin_enqueue_scripts( true );
	}
}
