<?php
/**
 * The Forminator_Quiz_New_NoWrong class.
 *
 * @package Forminator
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_Quiz_New_NoWrong
 *
 * @since 1.0
 */
class Forminator_Quiz_New_NoWrong extends Forminator_Admin_Page {

	/**
	 * Return wizard title
	 *
	 * @since 1.0
	 */
	public function getWizardTitle() {
		$id = filter_input( INPUT_GET, 'id', FILTER_VALIDATE_INT );
		if ( $id ) {
			return esc_html__( 'Edit Quiz', 'forminator' );
		} else {
			return esc_html__( 'New Quiz', 'forminator' );
		}
	}


	/**
	 * Add page screen hooks
	 *
	 * @since 1.6.2
	 * @param string $hook Hook.
	 */
	public function enqueue_scripts( $hook ) {
		// Load admin scripts.
		wp_register_script(
			'forminator-admin',
			forminator_plugin_url() . 'build/personality-scripts.js',
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

		// Remove default WordPress jQuery touch punch.
		wp_deregister_script( 'jquery-touch-punch' );
		// Register new modified jQuery touch punch script.
		wp_register_script( 'jquery-touch-punch', forminator_plugin_url() . 'assets/js/library/jquery.ui.touch-punch.min.js', array( 'jquery-ui-core', 'jquery-ui-mouse' ), FORMINATOR_VERSION, true );
		// Enqueue the script.
		wp_enqueue_script( 'jquery-touch-punch' );
		forminator_common_admin_enqueue_scripts( true );
	}
}
