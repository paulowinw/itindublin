<?php
/**
 * The Forminator_Form_Reports_Model class.
 *
 * Author: Hoang Ngo
 *
 * @package Forminator
 */

/**
 * Form Reports
 */
class Forminator_Form_Reports_Model {

	/**
	 * The table name
	 *
	 * @var string
	 */
	protected $table_name;


	/**
	 * Plugin instance
	 *
	 * @var null
	 */
	private static $instance = null;

	/**
	 * Return the plugin instance
	 *
	 * @return Forminator_Form_Views_Model
	 * @since 1.0
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Forminator_Form_Reports_Model constructor.
	 *
	 * @since 1.0
	 */
	public function __construct() {
		$this->table_name = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_REPORTS );
	}

	/**
	 * Save reports to database
	 *
	 * @param mixed  $report Report.
	 * @param string $status Status.
	 *
	 * @return int
	 */
	public function report_save( $report, $status ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert(
			$this->table_name,
			array(
				'report_value' => maybe_serialize( $report ),
				'status'       => $status,
				'date_created' => date_i18n( 'Y-m-d H:i:s' ),
				'date_updated' => date_i18n( 'Y-m-d H:i:s' ),
			)
		);

		return $wpdb->insert_id;
	}

	/**
	 * Update report
	 *
	 * @param int    $report_id Report Id.
	 * @param mixed  $report Report.
	 * @param string $status Status.
	 *
	 * @return bool|int|mysqli_result|resource|null
	 */
	public function report_update( $report_id, $report, $status ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		return $wpdb->update(
			$this->get_table_name(),
			array(
				'report_value' => maybe_serialize( $report ),
				'status'       => $status,
				'date_updated' => date_i18n( 'Y-m-d H:i:s' ),
			),
			array(
				'report_id' => $report_id,
			)
		);
	}

	/**
	 * Update report data
	 *
	 * @param int $report_id Report Id.
	 *
	 * @return bool|int|mysqli_result|resource|null
	 */
	public function report_update_date( $report_id ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		return $wpdb->update(
			$this->get_table_name(),
			array(
				'date_updated' => date_i18n( 'Y-m-d H:i:s' ),
			),
			array(
				'report_id' => $report_id,
			)
		);
	}

	/**
	 * Delete Report
	 *
	 * @param int $report_id Report Id.
	 *
	 * @return bool|int|mysqli_result|resource|null
	 */
	public function report_delete( $report_id ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $wpdb->delete(
			$this->get_table_name(),
			array(
				'report_id' => $report_id,
			)
		);

		if ( ! is_wp_error( $result ) ) {
			/**
			 * Fires after report status update
			 *
			 * @param string $report_id Report ID.
			 *
			 * @since 1.27.0
			 */
			do_action( 'forminator_after_notification_delete', $report_id );
		}

		return $result;
	}

	/**
	 * Load all report data
	 *
	 * @since 1.20.0
	 */
	public function fetch_all_report() {
		global $wpdb;
		$table_name = $this->get_table_name();
		$results    = $wpdb->get_results( 'SELECT * FROM ' . esc_sql( $table_name ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery

		return $results;
	}

	/**
	 * Load reports data by id
	 *
	 * @param int $id Id.
	 *
	 * @return array|object|stdClass[]|null
	 */
	public function fetch_report_by_id( $id ) {
		global $wpdb;
		$table_name = $this->get_table_name();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$results = $wpdb->get_row( $wpdb->prepare( 'SELECT report_id, report_value, status FROM ' . esc_sql( $table_name ) . ' WHERE report_id = %d', $id ) );

		return $results;
	}

	/**
	 * Update report status
	 *
	 * @param int    $report_id Report Id.
	 * @param string $status Status.
	 *
	 * @return bool|int|mysqli_result|resource|null
	 */
	public function report_update_status( $report_id, $status ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $wpdb->update(
			$this->get_table_name(),
			array(
				'status' => $status,
			),
			array(
				'report_id' => $report_id,
			)
		);

		if ( ! is_wp_error( $result ) ) {
			/**
			 * Fires after report status update
			 *
			 * @param string $report_id Report ID.
			 * @param string $status Report status.
			 *
			 * @since 1.27.0
			 */
			do_action( 'forminator_after_notification_status_update', $report_id, $status );
		}

		return $result;
	}

	/**
	 * Return report table name
	 *
	 * @return string
	 * @since 1.20.0
	 */
	public function get_table_name() {
		return Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_REPORTS );
	}

	/**
	 * Insert default report entry
	 *
	 * @since 1.20.0
	 */
	public function default_report_entry() {
		$current_user = wp_get_current_user();
		if ( empty( $current_user->ID ) ) {
			$current_user = get_userdata( 1 );
		}

		$get_default = get_option( 'forminator_default_report_entry', false );
		if ( ! $get_default ) {
			$reports = array(
				'exclude'       => ! empty( $current_user->ID ) ? array( $current_user->ID ) : array( 1 ),
				'settings'      => array(
					'label'      => 'Form reports',
					'module'     => 'forms',
					'forms_type' => 'all',
				),
				'schedule'      => array(
					'frequency' => 'monthly',
					'monthDay'  => '4',
					'monthTime' => '04:00 AM',
				),
				'report_status' => 'inactive',
				'recipients'    => array(
					array(
						'id'     => $current_user->ID,
						'name'   => $current_user->display_name,
						'email'  => $current_user->user_email,
						'role'   => empty( $current_user->roles ) ? null : ucfirst( $current_user->roles[0] ),
						'avatar' => get_avatar_url( $current_user->user_email ),
					),
				),
			);

			$this->report_save( $reports, 'inactive' );
			update_option( 'forminator_default_report_entry', true );
		}
	}
}
