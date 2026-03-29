<?php
/**
 * The Forminator_Poll_Page class.
 *
 * @package Forminator
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_Poll_Page
 *
 * @since 1.0
 */
class Forminator_Poll_Page extends Forminator_Admin_Module_Edit_Page {

	/**
	 * Module slug
	 *
	 * @var string
	 */
	protected static $module_slug = 'poll';

	/**
	 * Return module array
	 *
	 * @since 1.14.10
	 *
	 * @param int    $id Id.
	 * @param string $title Title.
	 * @param array  $views Views.
	 * @param string $date Date.
	 * @param string $status Status.
	 * @param mixed  $model Model.
	 *
	 * @return array
	 */
	protected static function module_array( $id, $title, $views, $date, $status, $model ) {
		return array(
			'id'              => $id,
			'title'           => $title,
			'entries'         => Forminator_Form_Entry_Model::count_entries( $id ),
			'last_entry_time' => forminator_get_latest_entry_time_by_form_id( $id ),
			'views'           => $views,
			'date'            => $date,
			'status'          => $status,
			'name'            => forminator_get_name_from_model( $model ),
		);
	}

	/**
	 * Bulk actions
	 *
	 * @since 1.0
	 * @return array
	 */
	public function bulk_actions() {
		return apply_filters(
			'forminator_polls_bulk_actions',
			array(
				'publish-polls'      => esc_html__( 'Publish', 'forminator' ),
				'draft-polls'        => esc_html__( 'Unpublish', 'forminator' ),
				'clone-polls'        => esc_html__( 'Duplicate', 'forminator' ),
				'reset-views-polls'  => esc_html__( 'Reset Tracking Data', 'forminator' ),
				'delete-votes-polls' => esc_html__( 'Delete Votes', 'forminator' ),
				'delete-polls'       => esc_html__( 'Delete', 'forminator' ),
			)
		);
	}
}
