<?php
/**
 * Forminator Dashboard Page
 *
 * @package Forminator
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_Dashboard_Page
 *
 * @since 1.0
 */
class Forminator_Dashboard_Page extends Forminator_Admin_Page {

	/**
	 * Print Dashboard box
	 *
	 * @since 1.0
	 */
	public function dashboard_create_screen() {
		$modules = forminator_get_modules();
		$this->template(
			'dashboard/create-content',
			array(
				'modules' => $modules,
			)
		);
	}

	/**
	 * Count modules
	 *
	 * @param string $status Status.
	 *
	 * @since 1.6
	 * @return int
	 */
	public function countModules( $status = '' ) {
		return Forminator_Form_Model::model()->count_all( $status );
	}

	/**
	 * Return all forms containing Stripe field
	 *
	 * @since 1.9
	 *
	 * @return array
	 */
	public function stripeModules() {
		return Forminator_Form_Model::model()->get_models_by_field_and_version( 'stripe-1', '1.9-alpha.1' );
	}

	/**
	 * Override scripts to be loaded
	 *
	 * @since 1.11
	 *
	 * @param string $hook Hook name.
	 */
	public function enqueue_scripts( $hook ) {
		parent::enqueue_scripts( $hook );

		forminator_print_front_styles();
		forminator_print_front_scripts();
	}
}
