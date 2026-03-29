<?php
/**
 * Forminator Addons Page
 *
 * @package Forminator
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_Addons_Page
 *
 * @since 1.15
 */
class Forminator_Addons_Page extends Forminator_Admin_Page {

	/**
	 * Fetch Add-ons data
	 *
	 * @return array
	 */
	public function get_addons_data() {
		$project_data = array();
		if ( class_exists( 'WPMUDEV_Dashboard' ) ) {
			$project_info = array(
				array(
					'pid' => Forminator_Admin_Addons_Page::GEOLOCATION_PID, // Geolocation Add-on.
				),
				// PDF Add-on.
				array(
					'pid' => 4262971,
				),
				// Stripe Add-on.
				array(
					'pid' => 3953609,
				),
			);

			if ( ! forminator_form_abandonment_disabled() ) {
				// Extension Pack Add-on.
				$project_info[] = array(
					'pid' => Forminator_Admin_Addons_Page::EXTENSION_PACK_PID,
				);
			}

			foreach ( $project_info as $project ) {
				$project_data[] = Forminator_Admin_Addons_Page::get_project_info_from_wpmudev_dashboard( $project['pid'] );
			}
		}

		return $project_data;
	}


	/**
	 * Get addons by action
	 *
	 * @return array
	 */
	public function get_addons_by_action() {
		$update   = array();
		$projects = $this->get_addons_data();

		if ( ! empty( $projects ) ) {
			foreach ( $projects as $project ) {
				if ( ! empty( $project ) ) {
					if ( $project->has_update ) {
						$update[] = $project;
					}
				}
			}
		}

		if ( empty( $projects ) && ! FORMINATOR_PRO && ! class_exists( 'WPMUDEV_Dashboard' ) ) {
			$projects = Forminator_Admin_Addons_Page::forminator_get_static_addons();
		}

		// Remove Stripe Add-on if payments are disabled.
		if ( forminator_payments_disabled() ) {
			$projects = array_filter(
				$projects,
				function ( $project ) {
					return 3953609 !== $project->pid;
				}
			);
		}

		$response['all']    = $projects;
		$response['update'] = $update;

		return $response;
	}
}
