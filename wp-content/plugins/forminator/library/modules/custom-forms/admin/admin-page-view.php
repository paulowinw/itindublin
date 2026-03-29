<?php
/**
 * The Forminator_CForm_Page class.
 *
 * @package Forminator
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_CForm_Page
 *
 * @since 1.0
 */
class Forminator_CForm_Page extends Forminator_Admin_Module_Edit_Page {

	/**
	 * Module slug
	 *
	 * @var string
	 */
	protected static $module_slug = 'form';

	/**
	 * Initialize
	 */
	public function init() {
		parent::init();
		self::maybe_migrate_stripe_field();
	}

	/**
	 * Migration for stripe field
	 *
	 * @return bool
	 */
	private static function maybe_migrate_stripe_field() {
		$form_id = filter_input( INPUT_GET, 'id', FILTER_VALIDATE_INT );
		if ( 'true' !== filter_input( INPUT_GET, 'migrate_stripe' ) || ! $form_id ) {
			return false;
		}
		$meta   = get_post_meta( $form_id, Forminator_Base_Form_Model::META_KEY, true );
		$fields = ! empty( $meta['fields'] ) ? $meta['fields'] : array();

		$fields_types = wp_list_pluck( $fields, 'type' );

		// Check if form has stripe field and not stripe-ocs field.
		if ( ! in_array( 'stripe', $fields_types, true )
			|| in_array( 'stripe-ocs', $fields_types, true ) ) {
			return false;
		}
		// Get stripe field.
		$stripe_field_key = array_search( 'stripe', $fields_types, true );
		$stripe_field     = $fields[ $stripe_field_key ];
		$stripe_object    = new Forminator_Stripe_Payment_Element();
		// Creat stripe-ocs field based on stripe field.
		$new_stripe_field = $stripe_object->migrate_stripe_settings( $stripe_field );
		array_splice( $fields, $stripe_field_key, 0, array( $new_stripe_field ) );
		// Update fields.
		$meta['fields'] = $fields;
		update_post_meta( $form_id, Forminator_Base_Form_Model::META_KEY, $meta );

		/**
		 * Fires after stripe migrated to stripe-ocs
		 *
		 * @param int $form_id Form ID.
		 */
		do_action( 'forminator_after_stripe_migrated', $form_id );

		return true;
	}

	/**
	 * Bulk actions
	 *
	 * @since 1.0
	 * @return array
	 */
	public function bulk_actions() {
		return apply_filters(
			'forminator_cform_bulk_actions',
			array(
				'publish-forms'        => esc_html__( 'Publish', 'forminator' ),
				'draft-forms'          => esc_html__( 'Unpublish', 'forminator' ),
				'clone-forms'          => esc_html__( 'Duplicate', 'forminator' ),
				'reset-views-forms'    => esc_html__( 'Reset Tracking Data', 'forminator' ),
				'apply-preset-forms'   => esc_html__( 'Apply Appearance Preset', 'forminator' ),
				'delete-entries-forms' => esc_html__( 'Delete Submissions', 'forminator' ),
				'delete-forms'         => esc_html__( 'Delete', 'forminator' ),
			)
		);
	}

	/**
	 * Return module array
	 *
	 * @since 1.14.10
	 *
	 * @param int    $id Id.
	 * @param string $title Title.
	 * @param mixed  $views Views.
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
			'abandoned'       => Forminator_Form_Entry_Model::count_report_entries( $id, '', '', 'abandoned' ),
			'views'           => $views,
			'date'            => $date,
			'status'          => $status,
			'model'           => $model,
		);
	}

	/**
	 * Override scripts to be loaded
	 *
	 * @since 1.6.1
	 *
	 * @param string $hook Hook.
	 */
	public function enqueue_scripts( $hook ) {
		parent::enqueue_scripts( $hook );

		// for preview.
		$style_src     = forminator_plugin_url() . 'assets/css/intlTelInput.min.css';
		$style_version = '4.0.3';

		$script_src     = forminator_plugin_url() . 'assets/js/library/intlTelInput.min.js';
		$script_version = FORMINATOR_VERSION;

		wp_enqueue_style( 'intlTelInput-forminator-css', $style_src, array(), $style_version ); // intlTelInput.
		wp_enqueue_script( 'forminator-intlTelInput', $script_src, array( 'jquery' ), $script_version, false ); // intlTelInput.
	}
}
