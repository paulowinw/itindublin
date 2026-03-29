<?php
/**
 * The Forminator_Form_Views_Model class.
 *
 * @package Forminator
 */

/**
 * Form Views
 * Handles conversions and views of the different forms
 */
class Forminator_Form_Views_Model {

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
	 * @since 1.0
	 * @return Forminator_Form_Views_Model
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Forminator_Form_Views_Model constructor.
	 *
	 * @since 1.0
	 */
	public function __construct() {
		$this->table_name = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_VIEWS );
	}

	/**
	 * Save conversion
	 *
	 * @since 1.0
	 * @param int $form_id - the form id.
	 * @param int $page_id - the page id.
	 */
	public function save_view( $form_id, $page_id ) {
		global $wpdb;

		$sql = "SELECT `view_id` FROM {$this->get_table_name()} WHERE `form_id` = %d AND `page_id` = %d AND DATE(`date_created`) = CURDATE()";

		$view_id = $wpdb->get_var( $wpdb->prepare( $sql, $form_id, $page_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery
		if ( $view_id ) {
			$this->_update( $view_id, $wpdb );
		} else {
			$this->_save( $form_id, $page_id, $wpdb );
		}
	}

	/**
	 * Save Data to database
	 *
	 * @param int         $form_id - the form id.
	 * @param int         $page_id - the page id.
	 * @param bool|object $db - the wp db object.
	 */
	private function _save( $form_id, $page_id, $db = false ) { // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
		if ( ! $db ) {
			global $wpdb;
			$db = $wpdb;
		}

		$db->insert(
			$this->table_name,
			array(
				'form_id'      => $form_id,
				'page_id'      => $page_id,
				'date_created' => date_i18n( 'Y-m-d H:i:s' ),
			)
		);
	}

	/**
	 * Update view
	 *
	 * @since 1.0
	 * @param int         $id - entry id.
	 * @param bool|object $db - the wp db object.
	 */
	private function _update( $id, $db = false ) { // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
		if ( ! $db ) {
			global $wpdb;
			$db = $wpdb;
		}
		$db->query( $db->prepare( "UPDATE {$this->get_table_name()} SET `count` = `count`+1, `date_updated` = now() WHERE `view_id` = %d", $id ) );
	}

	/**
	 * Count views
	 *
	 * @since 1.0
	 * @param int    $form_id - the form id.
	 * @param string $starting_date - the start date (dd-mm-yyy).
	 * @param string $ending_date - the end date (dd-mm-yyy).
	 *
	 * @return int - totol views based on parameters
	 */
	public function count_views( $form_id, $starting_date = null, $ending_date = null ) {
		return $this->_count( $form_id, $starting_date, $ending_date );
	}

	/**
	 * Delete views by form
	 *
	 * @since 1.0
	 * @param int $form_id - the form id.
	 */
	public function delete_by_form( $form_id ) {
		global $wpdb;
		$sql = "DELETE FROM {$this->get_table_name()} WHERE `form_id` = %d";
		$wpdb->query( $wpdb->prepare( $sql, $form_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery
	}

	/**
	 * Count data
	 *
	 * @since 1.0
	 * @param int    $form_id - the form id.
	 * @param string $starting_date - the start date (dd-mm-yyy).
	 * @param string $ending_date - the end date (dd-mm-yyy).
	 *
	 * @return int - totol counts based on parameters
	 */
	private function _count( $form_id, $starting_date = null, $ending_date = null ) { // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
		global $wpdb;
		$date_query = $this->generate_date_query( $wpdb, $starting_date, $ending_date );
		$sql        = "SELECT SUM(`count`) FROM {$this->get_table_name()} WHERE `form_id` = %d $date_query";
		$counts     = $wpdb->get_var( $wpdb->prepare( $sql, $form_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery

		if ( $counts ) {
			return $counts;
		}

		return 0;
	}

	/**
	 * Generate the date query
	 *
	 * @since 1.0
	 * @param object $wpdb - the WordPress database object.
	 * @param string $starting_date - the start date (dd-mm-yyy).
	 * @param string $ending_date - the end date (dd-mm-yyy).
	 * @param string $prefix Prefix.
	 * @param string $clause Clause.
	 *
	 * @return string $date_query
	 */
	private function generate_date_query( $wpdb, $starting_date = null, $ending_date = null, $prefix = '', $clause = 'AND' ) {
		$date_query = '';
		if ( ! is_null( $starting_date ) && ! is_null( $ending_date ) && ! empty( $starting_date ) && ! empty( $ending_date ) ) {
			$ending_date = $ending_date . ' 23:59:00';
			$date_query  = $wpdb->prepare( "$clause date_created >= %s AND date_created <= %s", $starting_date, $ending_date ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		} elseif ( ! is_null( $starting_date ) && ! empty( $starting_date ) ) {
				$date_query = $wpdb->prepare( "$clause date_created >= %s", $starting_date ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		} elseif ( ! is_null( $ending_date ) && ! empty( $ending_date ) ) {
			$date_query = $wpdb->prepare( "$clause date_created <= %s", $starting_date ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}

		return $date_query;
	}

	/**
	 * Return views table name
	 *
	 * @since 1.6.3
	 *
	 * @return string
	 */
	public function get_table_name() {
		return Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_VIEWS );
	}
}
