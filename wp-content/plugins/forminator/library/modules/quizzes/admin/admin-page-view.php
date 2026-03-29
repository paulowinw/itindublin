<?php
/**
 * The Forminator_Quiz_Page class.
 *
 * @package Forminator
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_Quiz_Page
 *
 * @since 1.0
 */
class Forminator_Quiz_Page extends Forminator_Admin_Module_Edit_Page {

	/**
	 * Module slug
	 *
	 * @var string
	 */
	protected static $module_slug = 'quiz';

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
			'has_leads'       => self::has_leads( $model ),
			'leads_id'        => self::get_leads_id( $model ),
			'leads'           => Forminator_Form_Entry_Model::count_leads( $id ),
			'last_entry_time' => forminator_get_latest_entry_time_by_form_id( $id ),
			'views'           => $views,
			'type'            => $model->quiz_type,
			'date'            => $date,
			'status'          => $status,
			'name'            => forminator_get_name_from_model( $model ),
		);
	}

	/**
	 * Check if quiz has leads
	 *
	 * @param object $model Form model.
	 *
	 * @return bool
	 */
	public static function has_leads( $model ) {
		if ( isset( $model->settings['hasLeads'] ) && filter_var( $model->settings['hasLeads'], FILTER_VALIDATE_BOOLEAN ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Check has lead
	 *
	 * @param object $model Form model.
	 *
	 * @return int
	 */
	public static function get_leads_id( $model ) {
		$leads_id = 0;
		if ( self::has_leads( $model ) && isset( $model->settings['leadsId'] ) ) {
			$leads_id = $model->settings['leadsId'];
		}

		return $leads_id;
	}

	/**
	 * Return leads rate
	 *
	 * @since 1.14
	 *
	 * @param array $module Module.
	 *
	 * @return float|int
	 */
	public static function getLeadsRate( $module ) {
		if ( $module['views'] > 0 ) {
			$rate = round( ( $module['leads'] * 100 ) / $module['views'], 1 );
		} else {
			$rate = 0;
		}

		return $rate;
	}

	/**
	 * Bulk actions
	 *
	 * @since 1.0
	 * @return array
	 */
	public function bulk_actions() {
		return apply_filters(
			'forminator_quizzes_bulk_actions',
			array(
				'publish-quizzes'        => esc_html__( 'Publish', 'forminator' ),
				'draft-quizzes'          => esc_html__( 'Unpublish', 'forminator' ),
				'reset-views-quizzes'    => esc_html__( 'Reset Tracking Data', 'forminator' ),
				'delete-entries-quizzes' => esc_html__( 'Delete Submissions', 'forminator' ),
				'delete-quizzes'         => esc_html__( 'Delete', 'forminator' ),
			)
		);
	}
}
