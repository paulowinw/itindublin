<?php
/**
 * Forminator Admin Addon Ajax
 *
 * @package Forminator
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_Admin_AJAX
 *
 * @since 1.0
 */
class Forminator_Admin_AJAX {

	/**
	 * Forminator_Admin_AJAX constructor.
	 *
	 * @since 1.0
	 */
	public function __construct() {

		// Handle close welcome box.
		add_action( 'wp_ajax_forminator_dismiss_welcome', array( $this, 'dismiss_welcome' ) );
		add_action( 'wp_ajax_nopriv_forminator_dismiss_welcome', array( $this, 'dismiss_welcome' ) );

		// Handle load google fonts.
		add_action( 'wp_ajax_forminator_load_google_fonts', array( $this, 'load_google_fonts' ) );

		// Handle load reCaptcha preview.
		add_action( 'wp_ajax_forminator_load_recaptcha_preview', array( $this, 'load_recaptcha_preview' ) );
		add_action( 'wp_ajax_forminator_load_hcaptcha_preview', array( $this, 'load_hcaptcha_preview' ) );
		add_action( 'wp_ajax_forminator_load_turnstile_preview', array( $this, 'load_turnstile_preview' ) );

		// Handle save settings.
		add_action( 'wp_ajax_forminator_save_builder', array( $this, 'save_builder' ) );
		add_action( 'wp_ajax_forminator_revert_builder', array( __CLASS__, 'revert_builder' ) );
		add_action( 'wp_ajax_forminator_save_poll', array( $this, 'save_poll_form' ) );
		add_action( 'wp_ajax_forminator_save_quiz_nowrong', array( $this, 'save_quiz' ) );
		add_action( 'wp_ajax_forminator_save_quiz_knowledge', array( $this, 'save_quiz' ) );

		// Handle settings popups.
		add_action( 'wp_ajax_forminator_load_captcha_popup', array( $this, 'load_captcha' ) );
		add_action( 'wp_ajax_forminator_save_captcha_popup', array( $this, 'save_captcha' ) );

		add_action( 'wp_ajax_forminator_load_currency_popup', array( $this, 'load_currency' ) );
		add_action( 'wp_ajax_forminator_save_currency_popup', array( $this, 'save_currency' ) );

		add_action( 'wp_ajax_forminator_load_pagination_entries_popup', array( $this, 'load_pagination_entries' ) );
		add_action( 'wp_ajax_forminator_save_pagination_entries_popup', array( $this, 'save_pagination_entries' ) );

		add_action( 'wp_ajax_forminator_load_pagination_listings_popup', array( $this, 'load_pagination_listings' ) );
		add_action( 'wp_ajax_forminator_save_pagination_listings_popup', array( $this, 'save_pagination_listings' ) );

		add_action( 'wp_ajax_forminator_load_email_settings_popup', array( $this, 'load_email_form' ) );

		add_action( 'wp_ajax_forminator_load_uninstall_settings_popup', array( $this, 'load_uninstall_form' ) );
		add_action( 'wp_ajax_forminator_save_uninstall_settings_popup', array( $this, 'save_uninstall_form' ) );

		add_action( 'wp_ajax_forminator_load_preview_cforms_popup', array( $this, 'preview_module' ) );
		add_action( 'wp_ajax_forminator_load_preview_polls_popup', array( $this, 'preview_module' ) );
		add_action( 'wp_ajax_forminator_load_preview_quizzes_popup', array( $this, 'preview_module' ) );

		// Handle exports popup.
		add_action( 'wp_ajax_forminator_load_exports_popup', array( $this, 'load_exports' ) );
		add_action( 'wp_ajax_forminator_clear_exports_popup', array( $this, 'clear_exports' ) );

		// Handle search user email.
		add_action( 'wp_ajax_forminator_builder_search_emails', array( $this, 'search_emails' ) );

		add_action( 'wp_ajax_forminator_create_module_from_template', array( $this, 'create_module_from_template' ) );

		add_action( 'wp_ajax_forminator_load_privacy_settings_popup', array( $this, 'load_privacy_settings' ) );
		add_action( 'wp_ajax_forminator_save_privacy_settings_popup', array( $this, 'save_privacy_settings' ) );

		add_action( 'wp_ajax_forminator_load_export_form_popup', array( $this, 'load_export' ) );
		add_action( 'wp_ajax_forminator_load_import_form_popup', array( $this, 'load_import' ) );
		add_action( 'wp_ajax_forminator_save_import_form_popup', array( $this, 'save_import' ) );

		add_action( 'wp_ajax_forminator_load_import_form_cf7_popup', array( $this, 'load_import_form_cf7' ) );
		add_action( 'wp_ajax_forminator_save_import_form_cf7_popup', array( $this, 'save_import_form_cf7' ) );

		add_action( 'wp_ajax_forminator_load_import_form_ninja_popup', array( $this, 'load_import_form_ninja' ) );
		add_action( 'wp_ajax_forminator_save_import_form_ninja_popup', array( $this, 'save_import_form_ninja' ) );

		add_action( 'wp_ajax_forminator_load_import_form_gravity_popup', array( $this, 'load_import_form_gravity' ) );
		add_action( 'wp_ajax_forminator_save_import_form_gravity_popup', array( $this, 'save_import_form_gravity' ) );

		add_action( 'wp_ajax_forminator_load_export_poll_popup', array( $this, 'load_export' ) );
		add_action( 'wp_ajax_forminator_load_import_poll_popup', array( $this, 'load_import' ) );
		add_action( 'wp_ajax_forminator_save_import_poll_popup', array( $this, 'save_import' ) );

		add_action( 'wp_ajax_forminator_delete_poll_submissions', array( $this, 'delete_poll_submissions' ) );

		add_action( 'wp_ajax_forminator_load_export_quiz_popup', array( $this, 'load_export' ) );
		add_action( 'wp_ajax_forminator_load_import_quiz_popup', array( $this, 'load_import' ) );
		add_action( 'wp_ajax_forminator_save_import_quiz_popup', array( $this, 'save_import' ) );

		// Handle PDFs.
		add_action( 'wp_ajax_forminator_save_pdf', array( $this, 'save_pdf' ) );
		add_action( 'wp_ajax_forminator_fetch_pdfs', array( $this, 'fetch_pdfs' ) );
		add_action( 'wp_ajax_forminator_delete_pdf', array( $this, 'delete_pdf' ) );

		add_action(
			'wp_ajax_forminator_save_accessibility_settings_popup',
			array(
				$this,
				'save_accessibility_settings',
			)
		);

		add_action( 'wp_ajax_forminator_save_dashboard_settings_popup', array( $this, 'save_dashboard_settings' ) );

		add_action( 'wp_ajax_forminator_stripe_settings_modal', array( $this, 'stripe_settings_modal' ) );
		add_action( 'wp_ajax_forminator_stripe_update_page', array( $this, 'stripe_update_page' ) );
		add_action( 'wp_ajax_forminator_disconnect_stripe', array( $this, 'stripe_disconnect' ) );
		add_action( 'wp_ajax_forminator_set_encryption_key', array( $this, 'set_encryption_key' ) );

		add_action( 'wp_ajax_forminator_paypal_settings_modal', array( $this, 'paypal_settings_modal' ) );
		add_action( 'wp_ajax_forminator_paypal_update_page', array( $this, 'paypal_update_page' ) );
		add_action( 'wp_ajax_forminator_disconnect_paypal', array( $this, 'paypal_disconnect' ) );

		add_action( 'wp_ajax_forminator_save_payments_settings_popup', array( $this, 'save_payments' ) );
		add_action( 'wp_ajax_forminator_dismiss_notification', array( $this, 'dismiss_notice' ) );
		add_action( 'wp_ajax_forminator_dismiss_notice', array( $this, 'dismiss_admin_notice' ) );
		add_action( 'wp_ajax_forminator_usage_tracking', array( $this, 'toggle_usage_tracking' ) );

		add_action( 'wp_ajax_forminator_promote_remind_later', array( $this, 'promote_remind_later' ) );

		add_action( 'wp_ajax_forminator_later_notification', array( $this, 'later_notice' ) );

		add_action( 'wp_ajax_forminator_reset_tracking_data', array( $this, 'reset_tracking_data' ) );

		add_action( 'wp_ajax_forminator_save_appearance_preset', array( $this, 'save_appearance_preset' ) );
		add_action( 'wp_ajax_forminator_create_appearance_preset', array( $this, 'create_appearance_preset' ) );
		add_action( 'wp_ajax_forminator_apply_appearance_preset', array( $this, 'apply_appearance_preset' ) );
		add_action( 'wp_ajax_forminator_delete_appearance_preset', array( $this, 'delete_appearance_preset' ) );

		add_action( 'wp_ajax_forminator_module_search', array( $this, 'module_search' ) );

		// Process ajax actions.
		$ajax_actions = array(
			'forminator_addons-install',
			'forminator_addons-install-activate',
			'forminator_addons-activate',
			'forminator_addons-deactivate',
			'forminator_addons-delete',
			'forminator_addons-update',
		);
		foreach ( $ajax_actions as $action ) {
			add_action( "wp_ajax_$action", array( $this, 'addons_page_actions' ) );
		}

		add_action( 'wp_ajax_forminator_filter_report_data', array( $this, 'filter_report_data' ) );
		add_action( 'wp_ajax_forminator_search_users', array( $this, 'search_users' ) );
		add_action( 'wp_ajax_forminator_get_avatar', array( $this, 'get_avatar' ) );
		add_action( 'wp_ajax_forminator_save_report', array( $this, 'save_report' ) );
		add_action( 'wp_ajax_forminator_fetch_report', array( $this, 'fetch_report' ) );
		add_action( 'wp_ajax_forminator_report_update_status', array( $this, 'update_report_status' ) );

		// Process Permission settings.
		add_action( 'wp_ajax_forminator_save_permissions', array( $this, 'save_permissions' ) );

		if ( ! forminator_feedback_disabled() ) {
			add_action( 'wp_ajax_forminator_share_feedback', array( $this, 'forminator_share_feedback' ) );
		}

		add_action( 'wp_ajax_forminator_deactivation_survey', array( $this, 'submit_deactivation_survey' ) );
	}

	/**
	 * Save quizzes
	 *
	 * @since 1.0
	 * @since 1.1 change superglobal POST to `get_post_data`
	 */
	public function save_quiz() {
		if ( ! forminator_is_user_allowed( 'forminator-quiz' ) ) {
			wp_send_json_error( esc_html__( 'Invalid request, you are not allowed to do that action.', 'forminator' ) );
		}

		forminator_validate_ajax( 'forminator_save_quiz', false, 'forminator-quiz' );

		$submitted_data = $this->get_post_data();

		$quiz_data = array();
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified in forminator_validate_ajax.
		if ( ! empty( $_POST['data'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput -- Sanitized in Forminator_Core::sanitize_array.
			$quiz_data = Forminator_Core::sanitize_array( json_decode( wp_unslash( $_POST['data'] ), true ) );
		}

		$id       = isset( $submitted_data['form_id'] ) ? intval( $submitted_data['form_id'] ) : 0;
		$settings = isset( $quiz_data['settings'] ) ? $quiz_data['settings'] : array();
		$title    = isset( $submitted_data['quiz_title'] ) ? $submitted_data['quiz_title'] : $submitted_data['formName'];
		$status   = isset( $submitted_data['status'] ) ? $submitted_data['status'] : '';
		$version  = isset( $submitted_data['version'] ) ? $submitted_data['version'] : '1.0';
		$template = new stdClass();
		if ( 'temp' === $status ) {
			$res = Forminator_Base_Form_Model::save_temp_settings( $id, $quiz_data );
			if ( $res ) {
				wp_send_json_success( $id );
			} else {
				wp_send_json_error( $id );
			}
		}

		$template->type = isset( $submitted_data['action'] ) ? $submitted_data['action'] : '';
		// Check if results exist.
		if ( isset( $quiz_data['results'] ) && is_array( $quiz_data['results'] ) ) {
			$template->results = $quiz_data['results'];
		}

		// Check if answers exist.
		if ( isset( $quiz_data['questions'] ) ) {
			$template->questions = $quiz_data['questions'];
		}

		if ( isset( $quiz_data['settings'] ) ) {
			$settings = $quiz_data['settings'];
		}
		$settings['version'] = $version;
		$template->settings  = $settings;

		if ( isset( $quiz_data['notifications'] ) ) {
			$template->notifications = $quiz_data['notifications'];
		}

		$id = Forminator_Quiz_Admin::update( $id, $title, $status, $template );
		if ( is_wp_error( $id ) ) {
			wp_send_json_error( $id->get_error_message() );
		} else {
			wp_send_json_success( $id );
		}
	}

	/**
	 * Save poll
	 *
	 * @since 1.0
	 * @since 1.1 change superglobal POST to `get_post_data`
	 */
	public function save_poll_form() {
		if ( ! forminator_is_user_allowed( 'forminator-poll' ) ) {
			wp_send_json_error( esc_html__( 'Invalid request, you are not allowed to do that action.', 'forminator' ) );
		}

		forminator_validate_ajax( 'forminator_save_poll', false, 'forminator-poll' );

		$submitted_data = $this->get_post_data();
		$poll_data      = array();
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified in forminator_validate_ajax.
		if ( ! empty( $_POST['data'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput -- Sanitized in Forminator_Core::sanitize_array.
			$poll_data = Forminator_Core::sanitize_array( json_decode( wp_unslash( $_POST['data'] ), true ) );
		}

		$settings = isset( $poll_data['settings'] ) ? $poll_data['settings'] : array();
		$id       = isset( $submitted_data['form_id'] ) ? intval( $submitted_data['form_id'] ) : 0;
		$title    = $submitted_data['formName'];
		$status   = isset( $submitted_data['status'] ) ? $submitted_data['status'] : '';
		$version  = isset( $submitted_data['version'] ) ? $submitted_data['version'] : '1.0';
		$template = new stdClass();

		if ( 'temp' === $status ) {
			$res = Forminator_Base_Form_Model::save_temp_settings( $id, $poll_data );
			if ( $res ) {
				wp_send_json_success( $id );
			} else {
				wp_send_json_error( $id );
			}
		}

		if ( isset( $poll_data['answers'] ) ) {
			$template->answers = $poll_data['answers'];
		}

		$settings['version'] = $version;
		$template->settings  = $settings;

		$id = Forminator_Poll_Admin::update( $id, $title, $status, $template );
		if ( is_wp_error( $id ) ) {
			wp_send_json_error( $id->get_error_message() );
		} else {
			wp_send_json_success( $id );
		}
	}

	/**
	 * Revert builder settings
	 */
	public static function revert_builder() {
		$module_type = filter_input( INPUT_POST, 'module_type' );

		$slug        = 'forminator-cform';
		$ajax_action = 'forminator_save_builder_fields';
		if ( 'poll' === $module_type ) {
			$slug        = 'forminator-poll';
			$ajax_action = 'forminator_save_poll';
		} elseif ( 'quiz' === $module_type ) {
			$slug        = 'forminator-quiz';
			$ajax_action = 'forminator_save_quiz';
		}

		if ( ! forminator_is_user_allowed( $slug ) ) {
			wp_send_json_error( esc_html__( 'Invalid request, you are not allowed to do that action.', 'forminator' ) );
		}
		forminator_validate_ajax( $ajax_action, false, $slug );

		$id = filter_input( INPUT_POST, 'module_id', FILTER_VALIDATE_INT );
		if ( empty( $id ) ) {
			wp_send_json_error( esc_html__( 'Module ID is missing.', 'forminator' ) );
		}

		if ( ! Forminator_Base_Form_Model::remove_temp_settings( $id ) ) {
			wp_send_json_error( esc_html__( 'Failed to revert module.', 'forminator' ) );
		}

		wp_send_json_success();
	}

	/**
	 * Save custom form fields
	 *
	 * @since 1.2
	 */
	public function save_builder() {
		if ( ! forminator_is_user_allowed( 'forminator-cform' ) ) {
			wp_send_json_error( esc_html__( 'Invalid request, you are not allowed to do that action.', 'forminator' ) );
		}

		forminator_validate_ajax( 'forminator_save_builder_fields', false, 'forminator-cform' );

		$submitted_data = $this->get_post_data();
		$form_data      = array();
		$fields         = array();
		$id             = isset( $submitted_data['form_id'] ) ? intval( $submitted_data['form_id'] ) : 0;
		$title          = $submitted_data['formName'];
		$status         = isset( $submitted_data['status'] ) ? $submitted_data['status'] : '';
		$version        = isset( $submitted_data['version'] ) ? $submitted_data['version'] : '1.0';
		$template       = new stdClass();

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified in forminator_validate_ajax.
		if ( ! empty( $_POST['data'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput -- Sanitized in Forminator_Core::sanitize_array.
			$form_data = Forminator_Core::sanitize_array( json_decode( wp_unslash( $_POST['data'] ), true ) );
		}

		$form_model = Forminator_Base_Form_Model::get_model( $id );

		if ( ! is_object( $form_model ) ) {
			wp_send_json_error( esc_html__( 'Form model doesn\'t exist', 'forminator' ) );
		}

		if ( 'temp' === $status ) {
			$res = Forminator_Base_Form_Model::save_temp_settings( $id, $form_data );
			if ( $res ) {
				wp_send_json_success( $id );
			} else {
				wp_send_json_error( $id );
			}
		}

		if ( empty( $status ) ) {
			$status = $form_model->status;
		}

		// we need to empty fields cause we will send new data.
		$form_model->clear_fields();

		// Build the fields.
		if ( isset( $form_data ) ) {
			$fields = $form_data['wrappers'];
			unset( $form_data['wrappers'] );
		}
		$template->fields = $fields;

		if ( isset( $form_data['integrationConditions'] ) ) {
			$template->integration_conditions = $form_data['integrationConditions'];
		}

		if ( isset( $form_data['behaviorArray'] ) ) {
			$template->behaviors = $form_data['behaviorArray'];
		}

		if ( isset( $form_data['notifications'] ) ) {
			$template->notifications = $form_data['notifications'];
		}

		// Sanitize settings.
		$settings          = $form_data['settings'];
		$activation_method = ! empty( $settings['activation-method'] ) ? $settings['activation-method'] : '';
		if ( 'manual' === $activation_method ) {
			$settings['automatic-login'] = '';
		}

		if ( isset( $settings['fields-style'] ) && 'custom' === $settings['fields-style'] ) {
			$settings['spacing'] = ! empty( $settings['spacing'] ) ? $settings['spacing'] : 0;
		}

		$settings['version'] = $version;
		$template->settings  = $settings;

		$id = Forminator_Custom_Form_Admin::update( $id, $title, $status, $template );
		if ( is_wp_error( $id ) ) {
			wp_send_json_error( $id );
		} else {
			wp_send_json_success( $id );
		}
	}

	/**
	 * Save PDF
	 *
	 * @since 2.0
	 * @throws Exception When failed to create PDF.
	 */
	public function save_pdf() {
		if ( ! forminator_is_user_allowed( 'forminator-cform' ) ) {
			wp_send_json_error( esc_html__( 'Invalid request, you are not allowed to do that action.', 'forminator' ) );
		}

		forminator_validate_ajax( 'forminator_save_builder_fields', false, 'forminator-cform' );

		try {
			$submitted_data = $this->get_post_data();

			if ( ! class_exists( 'Forminator_PDF_Form_Actions' ) ) {
				throw new Exception( esc_html__( 'PDF add-on is not installed.', 'forminator' ) );
			}

			$pdf_data['pdf_id'] = Forminator_PDF_Form_Actions::create_new_pdf_form( $submitted_data );

			if ( empty( $pdf_data['pdf_id'] ) || ! is_numeric( $pdf_data['pdf_id'] ) ) {
				throw new Exception( esc_html__( 'Failed to create PDF. Please try again.', 'forminator' ) );
			}
			$pdf_data['pdf_filename'] = urldecode( sanitize_title( $submitted_data['pdf_filename'] ?? 'noname' ) );
			wp_send_json_success( $pdf_data );

		} catch ( Exception $e ) {
			wp_send_json_error( $e->getMessage() );
		}
	}

	/**
	 * Deleta a PDF
	 *
	 * @since 2.0
	 * @throws Exception When failed to delete PDF.
	 */
	public function delete_pdf() {
		if ( ! forminator_is_user_allowed( 'forminator-cform' ) ) {
			wp_send_json_error( esc_html__( 'Invalid request, you are not allowed to do that action.', 'forminator' ) );
		}

		forminator_validate_ajax( 'forminator_save_builder_fields', false, 'forminator-cform' );

		try {
			$pdf_data = $this->get_post_data();

			if ( ! isset( $pdf_data['pdfId'] ) || empty( $pdf_data['pdfId'] ) ) {
				throw new Exception( esc_html__( 'PDF not found.', 'forminator' ) );
			}

			Forminator_API::delete_form( $pdf_data['pdfId'] );

			wp_send_json_success( $pdf_data['pdfId'] );

		} catch ( Exception $e ) {
			wp_send_json_error( $e->getMessage() );
		}
	}

	/**
	 * Fetch PDF files
	 *
	 * @since 2.0
	 * @throws Exception When failed to fetch PDF.
	 */
	public function fetch_pdfs() {
		if ( ! forminator_is_user_allowed( 'forminator-cform' ) ) {
			wp_send_json_error( esc_html__( 'Invalid request, you are not allowed to do that action.', 'forminator' ) );
		}

		forminator_validate_ajax( 'forminator_save_builder_fields', false, 'forminator-cform' );

		try {
			$submitted_data = $this->get_post_data();
			$pdfs           = Forminator_API::get_forms( null, 1, 999, 'pdf_form', $submitted_data['parent_form_id'] );

			if ( is_wp_error( $pdfs ) || ! is_array( $pdfs ) ) {
				throw new Exception( esc_html__( 'Forminator encountered an error while fetching the PDF files.', 'forminator' ) );
			}

			wp_send_json_success( $pdfs );

		} catch ( Exception $e ) {
			wp_send_json_error( $e->getMessage() );
		}
	}

	/**
	 * Save custom form settings
	 *
	 * @since 1.2
	 */
	public function save_builder_settings() {
		_deprecated_function( 'save_builder_settings', '1.6', 'save_builder' );
		if ( ! current_user_can( forminator_get_permission( 'forminator-cform' ) ) ) {
			return;
		}

		forminator_validate_ajax( 'forminator_save_builder_fields', false, 'forminator-cform' );

		$submitted_data = $this->get_post_data();
		$id             = isset( $submitted_data['form_id'] ) ? intval( $submitted_data['form_id'] ) : 0;
		$title          = $submitted_data['formName'];
		$status         = isset( $submitted_data['status'] ) ? $submitted_data['status'] : '';
		$version        = isset( $submitted_data['version'] ) ? $submitted_data['version'] : '1.0';

		if ( is_null( $id ) || $id <= 0 ) {
			$form_model = new Forminator_Form_Model();

			if ( empty( $status ) ) {
				$status = Forminator_Form_Model::STATUS_PUBLISH;
			}
		} else {
			$form_model = Forminator_Base_Form_Model::get_model( $id );

			if ( ! is_object( $form_model ) ) {
				wp_send_json_error( esc_html__( "Form model doesn't exist", 'forminator' ) );
			}
			if ( empty( $status ) ) {
				$status = $form_model->status;
			}
		}
		$form_model->set_var_in_array( 'name', 'formName', $submitted_data, 'forminator_sanitize_field' );

		$settings = $submitted_data['data'];

		$settings['formName'] = $title;
		$settings['version']  = $version;
		$form_model->settings = $settings;

		// status.
		$form_model->status = $status;

		// Save data.
		$id = $form_model->save();

		if ( is_wp_error( $id ) ) {
			wp_send_json_error( $id->get_error_message() );
		}

		// add privacy settings to global option.
		$override_privacy = false;
		if ( isset( $settings['enable-submissions-retention'] ) ) {
			$override_privacy = filter_var( $settings['enable-submissions-retention'], FILTER_VALIDATE_BOOLEAN );
		}
		$retention_number = null;
		$retention_unit   = null;
		if ( $override_privacy ) {
			$retention_number = 0;
			$retention_unit   = 'days';
			if ( isset( $settings['submissions-retention-number'] ) ) {
				$retention_number = (int) $settings['submissions-retention-number'];
			}
			if ( isset( $settings['submissions-retention-unit'] ) ) {
				$retention_unit = $settings['submissions-retention-unit'];
			}
		}

		forminator_update_form_submissions_retention( $id, $retention_number, $retention_unit );

		wp_send_json_success( $id );
	}

	/**
	 * Apply Appearance Preset ajax.
	 */
	public function apply_appearance_preset() {
		forminator_validate_ajax( 'forminator_apply_preset', false, 'forminator-cform' );

		$preset_id = Forminator_Core::sanitize_text_field( 'preset_id' );
		$ids       = filter_input( INPUT_POST, 'ids', FILTER_VALIDATE_INT, FILTER_REQUIRE_ARRAY );
		$edit_form = filter_input( INPUT_POST, 'edit_form', FILTER_VALIDATE_BOOLEAN );
		$count     = 0;

		$new_settings = Forminator_Settings_Page::get_preset( $preset_id );
		if ( $ids ) {
			foreach ( $ids as $form_id ) {
				$form_model = Forminator_Base_Form_Model::get_model( $form_id );
				if ( ! $form_model ) {
					continue;
				}

				$form_model->settings = self::merge_appearance_settings( $form_model->settings, $new_settings );

				if ( $edit_form ) {
					// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput -- Sanitized in Forminator_Core::sanitize_array.
					$settings     = isset( $_POST['settings'] ) ? Forminator_Core::sanitize_array( $_POST['settings'] ) : array();
					$old_settings = json_decode( wp_unslash( $settings ), true );
					if ( $old_settings ) {
						$form_model->settings = self::merge_appearance_settings( $old_settings, $new_settings );
					}
					wp_send_json_success( $form_model->settings );
				}

				// Save data.
				$result = $form_model->save();
				if ( ! is_wp_error( $result ) ) {
					// Regenerare module css file.
					Forminator_Render_Form::regenerate_css_file( $form_id );

					++$count;
				}
			}
		}

		if ( $edit_form ) {
			wp_send_json_error( esc_html__( 'Something went wrong.', 'forminator' ) );
		}
		/* translators: %s: Form count */
		$success = sprintf( esc_html__( 'Preset successfully applied to %d form(s).', 'forminator' ), $count );
		wp_send_json_success( $success );
	}

	/**
	 * Merge Appearance settings
	 *
	 * @param array $settings Current Settings.
	 * @param array $new_settings New Appearance settings.
	 *
	 * @return array
	 */
	private function merge_appearance_settings( $settings, $new_settings ) {
		$appearance_settings = Forminator_Settings_Page::only_appearance_settings( $settings );
		$rest_settings       = array();
		foreach ( $settings as $key => $val ) {
			if ( ! array_key_exists( $key, $appearance_settings ) ) {
				$rest_settings[ $key ] = $val;
			}
		}
		$new_settings = array_merge( $rest_settings, $new_settings );

		return $new_settings;
	}

	/**
	 * Create Appearance Preset ajax.
	 */
	public function create_appearance_preset() {
		forminator_validate_ajax( 'forminator_create_preset', false, 'forminator-settings' );

		$id       = uniqid();
		$settings = array();
		$form_id  = filter_input( INPUT_POST, 'form_id', FILTER_VALIDATE_INT );
		$name     = Forminator_Core::sanitize_text_field( 'name' );

		if ( empty( $name ) ) {
			wp_send_json_error( esc_html__( 'Preset Name is empty.', 'forminator' ) );
		}

		if ( ! empty( $form_id ) ) {
			$form     = Forminator_Base_Form_Model::get_model( $form_id );
			$settings = $form->settings;
		}

		// Update preset list.
		Forminator_Settings_Page::save_preset_list( $id, $name );

		// Save preset.
		self::save_preset( $id, $settings );

		wp_send_json_success( $id );
	}

	/**
	 * Delete Appearance Preset ajax.
	 */
	public function delete_appearance_preset() {
		forminator_validate_ajax( 'forminator_appearance_preset', false, 'forminator-settings' );

		$id = Forminator_Core::sanitize_text_field( 'preset_id' );
		if ( empty( $id ) ) {
			wp_send_json_error( esc_html__( 'Preset ID is empty.', 'forminator' ) );
		}
		if ( 'default' === $id ) {
			wp_send_json_error( esc_html__( 'Preset ID is incorrect.', 'forminator' ) );
		}

		// Delete preset from list.
		$key          = 'forminator_appearance_presets';
		$preset_names = get_option( $key, array() );
		unset( $preset_names[ $id ] );
		update_option( $key, $preset_names );

		// Delete preset.
		delete_option( 'forminator_appearance_preset_' . $id );

		wp_send_json_success();
	}

	/**
	 * Save Appearance Preset ajax.
	 */
	public function save_appearance_preset() {
		forminator_validate_ajax( 'forminator_appearance_preset', false, 'forminator-settings' );

		$id = Forminator_Core::sanitize_text_field( 'presetId' );
		if ( ! $id ) {
			wp_send_json_error( esc_html__( 'Appearance preset id doesn\'t exist', 'forminator' ) );
		}

		$settings = array();
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified in forminator_validate_ajax.
		if ( ! empty( $_POST['settings'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput -- Sanitized in Forminator_Core::sanitize_array.
			$settings = Forminator_Core::sanitize_array( json_decode( wp_unslash( $_POST['settings'] ), true ) );
		}

		self::save_preset( $id, $settings );

		wp_send_json_success( esc_html__( 'The preset has been successfully updated.', 'forminator' ) );
	}

	/**
	 * Save Appearance Preset
	 *
	 * @param string $id ID.
	 * @param array  $settings Settings.
	 */
	private static function save_preset( $id, $settings ) {
		update_option( 'forminator_appearance_preset_' . $id, $settings );
	}

	/**
	 * Dismiss welcome message
	 *
	 * @since 1.0
	 */
	public function dismiss_welcome() {
		forminator_validate_ajax( 'forminator_dismiss_welcome' );
		update_option( 'forminator_welcome_dismissed', true );
		wp_send_json_success();
	}

	/**
	 * Set encryption key
	 */
	public function set_encryption_key() {
		forminator_validate_ajax( 'forminator_set_encryption_key' );

		try {
			$config_content = self::prepare_new_wp_config();
			$config_file    = self::get_wp_config_file_path();

			global $wp_filesystem;
			// Write the modified content back to wp-config.php.
			return $wp_filesystem->put_contents( $config_file, $config_content, FS_CHMOD_FILE );
		} catch ( Exception $e ) {
			wp_send_json_error( $e->getMessage() );
		}
		wp_send_json_success();
	}

	/**
	 * Get full path to wp-config.php file.
	 *
	 * @return string
	 */
	public static function get_wp_config_file_path(): string {
		$path = ABSPATH . 'wp-config.php';
		if ( ! file_exists( ABSPATH . 'wp-config.php' )
				&& file_exists( dirname( ABSPATH ) . '/wp-config.php' )
				&& ! file_exists( dirname( ABSPATH ) . '/wp-settings.php' ) ) {
			$path = dirname( ABSPATH ) . '/wp-config.php';
		}

		return apply_filters( 'forminator_wp_config_file_path', $path );
	}

	/**
	 * Prepare new wp-config.php content
	 *
	 * @return string
	 * @throws Exception Throws exception if failed to prepare new wp-config.php content.
	 */
	public static function prepare_new_wp_config() {
		// Check if current user can write to files.
		if ( ! current_user_can( 'manage_options' ) ) {
			throw new Exception( esc_html__( 'You do not have permission to write to the wp-config.php file.', 'forminator' ) );
		}

		global $wp_filesystem;
		// Ensure we have the WP_Filesystem initialized.
		if ( is_null( $wp_filesystem ) && ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		WP_Filesystem();
		if ( ! $wp_filesystem instanceof WP_Filesystem_Base ) {
			throw new Exception( esc_html__( 'Unable to initialize WP_Filesystem.', 'forminator' ) );
		}

		// Get the path to wp-config.php.
		$config_file = self::get_wp_config_file_path();

		// Check if the file exists.
		if ( ! $wp_filesystem->exists( $config_file ) ) {
			throw new Exception( esc_html__( 'wp-config.php file not found.', 'forminator' ) );
		}

		// Read the wp-config.php content.
		$config_content = $wp_filesystem->get_contents( $config_file );

		if ( ! $config_content ) {
			throw new Exception( esc_html__( 'Unable to read wp-config.php file content.', 'forminator' ) );
		}

		// Check if the constant already exists.
		if ( strpos( $config_content, 'FORMINATOR_ENCRYPTION_KEY' ) !== false ) {
			throw new Exception( esc_html__( 'FORMINATOR_ENCRYPTION_KEY constant already exists.', 'forminator' ) );
		}

		$secret       = Forminator_Encryption::generate_encryption_key();
		$new_constant = sprintf(
			"define( 'FORMINATOR_ENCRYPTION_KEY', '%s' );%s",
			$secret,
			PHP_EOL
		);

		// Append the new constant before the line that says "That's all, stop editing!".
		$wp_comment  = "/* That's all, stop editing! Happy blogging. */";
		$new_content = str_replace( $wp_comment, $new_constant . "\n\n$wp_comment", $config_content );

		if ( $new_content === $config_content ) {
			$wp_comment  = "/* That's all, stop editing! Happy publishing. */";
			$new_content = str_replace( $wp_comment, $new_constant . "\n\n$wp_comment", $config_content );

			if ( $new_content === $config_content ) {
				throw new Exception( esc_html__( 'WP config file does not have the expected content.', 'forminator' ) );
			}
		}
		return $new_content;
	}

	/**
	 * Check if we can write to wp-config.php file
	 *
	 * @return bool
	 */
	public static function can_write_to_wp_config(): bool {
		try {
			self::prepare_new_wp_config();
		} catch ( Exception $e ) {
			return false;
		}

		return true;
	}


	/**
	 * Load google fonts
	 *
	 * @since 1.0.5
	 */
	public function load_google_fonts() {
		forminator_validate_ajax( 'forminator_load_google_fonts', false, 'forminator' );

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified in forminator_validate_ajax.
		$is_object = isset( $_POST['data']['isObject'] ) ? sanitize_text_field( wp_unslash( $_POST['data']['isObject'] ) ) : false;

		$fonts = forminator_get_font_families( $is_object );
		wp_send_json_success( $fonts );
	}

	/**
	 * Load reCaptcha settings
	 *
	 * @since 1.0
	 */
	public function load_captcha() {
		// Validate nonce.
		forminator_validate_ajax( 'forminator_popup_captcha', false, 'forminator-settings' );

		$html = forminator_template( 'settings/popup/edit-captcha-content' );

		wp_send_json_success( $html );
	}

	/**
	 * Save reCaptcha popup data
	 *
	 * @since 1.0
	 */
	public function save_captcha() {
		// Validate nonce.
		forminator_validate_ajax( 'forminator_save_popup_captcha', false, 'forminator-settings' );

		update_option( 'forminator_captcha_key', Forminator_Core::sanitize_text_field( 'v2_captcha_key' ) );
		update_option( 'forminator_captcha_secret', Forminator_Core::sanitize_text_field( 'v2_captcha_secret' ) );

		update_option( 'forminator_v2_invisible_captcha_key', Forminator_Core::sanitize_text_field( 'v2_invisible_captcha_key' ) );
		update_option( 'forminator_v2_invisible_captcha_secret', Forminator_Core::sanitize_text_field( 'v2_invisible_captcha_secret' ) );

		update_option( 'forminator_v3_captcha_key', Forminator_Core::sanitize_text_field( 'v3_captcha_key' ) );
		update_option( 'forminator_v3_captcha_secret', Forminator_Core::sanitize_text_field( 'v3_captcha_secret' ) );

		update_option( 'forminator_turnstile_key', Forminator_Core::sanitize_text_field( 'turnstile_key' ) );
		update_option( 'forminator_turnstile_secret', Forminator_Core::sanitize_text_field( 'turnstile_secret' ) );

		update_option( 'forminator_hcaptcha_key', Forminator_Core::sanitize_text_field( 'hcaptcha_key' ) );
		update_option( 'forminator_hcaptcha_secret', Forminator_Core::sanitize_text_field( 'hcaptcha_secret' ) );
		update_option( 'forminator_captcha_tab_saved', Forminator_Core::sanitize_text_field( 'captcha_tab_saved' ) );

		update_option( 'forminator_captcha_language', Forminator_Core::sanitize_text_field( 'captcha_language' ) );

		wp_send_json_success();
	}

	/**
	 * Load currency modal
	 *
	 * @since 1.0
	 */
	public function load_currency() {
		forminator_validate_ajax( 'forminator_popup_currency', false, 'forminator-settings' );

		$html = forminator_template( 'settings/popup/edit-currency-content' );

		wp_send_json_success( $html );
	}

	/**
	 * Save reCaptcha popup data
	 *
	 * @since 1.0
	 */
	public function save_currency() {
		// Validate nonce.
		forminator_validate_ajax( 'forminator_save_popup_currency', false, 'forminator-settings' );

		update_option( 'forminator_currency', Forminator_Core::sanitize_text_field( 'currency' ) );

		wp_send_json_success();
	}

	/**
	 * Load entries pagination modal
	 *
	 * @since 1.0.2
	 */
	public function load_pagination_entries() {
		// Validate nonce.
		forminator_validate_ajax( 'forminator_popup_pagination_entries', false, 'forminator-settings' );

		$html = forminator_template( 'settings/popup/edit-pagination-entries-content' );

		wp_send_json_success( $html );
	}

	/**
	 * Load reCaptcha preview
	 *
	 * @since 1.5.4
	 */
	public function load_recaptcha_preview() {

		forminator_validate_ajax( 'forminator_load_captcha_settings', false, 'forminator-settings' );

		$site_language = get_locale();
		$language      = get_option( 'forminator_captcha_language', '' );
		$language      = ! empty( $language ) ? $language : $site_language;

		$captcha = Forminator_Core::sanitize_text_field( 'captcha' );

		if ( 'v2-invisible' === $captcha ) {
			$captcha_key  = get_option( 'forminator_v2_invisible_captcha_key', '' );
			$captcha_size = 'invisible';
			$onload       = 'forminator_render_admin_captcha_v2_invisible';
		} elseif ( 'v3' === $captcha ) {
			$captcha_key  = get_option( 'forminator_v3_captcha_key', '' );
			$captcha_size = 'invisible';
			$onload       = 'forminator_render_admin_captcha_v3';
		} else {
			$captcha_key  = get_option( 'forminator_captcha_key', '' );
			$captcha_size = 'normal';
			$onload       = 'forminator_render_admin_captcha_v2';
		}
		$html = '';

		if ( ! empty( $captcha_key ) ) {
			// phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript
			$html .= '<script src="' . esc_url( 'https://www.google.com/recaptcha/api.js?hl=' . $language ) . '&render=explicit&onload=' . $onload . '" async defer></script>';
			$html .= '<div class="forminator-g-recaptcha-' . esc_attr( $captcha ) . '" data-sitekey="' . esc_attr( $captcha_key ) . '" data-theme="light" data-size="' . $captcha_size . '"></div>';

		} else {
			$html .= '<div role="alert" class="sui-notice sui-active" style="display: block; text-align: left;" aria-live="assertive">';
			$html .= '<div class="sui-notice-content">';
			$html .= '<div class="sui-notice-message">';
			$html .= '<span class="sui-notice-icon sui-icon-info" aria-hidden="true"></span>';
			$html .= '<p>' . esc_html__( 'You have to first save your credentials to load the reCAPTCHA . ', 'forminator' ) . '</p>';
			$html .= '</div>';
			$html .= '</div>';
			$html .= '</div>';
		}

		wp_send_json_success( $html );
	}

	/**
	 * Load hCaptcha preview
	 *
	 * @since 1.15.?
	 */
	public function load_hcaptcha_preview() {

		forminator_validate_ajax( 'forminator_load_captcha_settings', false, 'forminator-settings' );

		$site_language = get_locale();
		$language      = get_option( 'forminator_captcha_language', '' );
		$language      = ! empty( $language ) ? $language : $site_language;

		$hcaptcha_key    = get_option( 'forminator_hcaptcha_key', '' );
		$hcaptcha_secret = get_option( 'forminator_hcaptcha_secret', '' );
		$onload          = 'forminator_render_admin_hcaptcha';

		if ( ! empty( $hcaptcha_key ) && ! empty( $hcaptcha_secret ) ) {
			// recaptchacompat=off seems to fix problems with hcaptcha container loading recaptcha instead of hcaptcha in the admin.
			// phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript
			$html  = '<script src="https://js.hcaptcha.com/1/api.js?hl=' . $language . '&onload=' . $onload . '&render=explicit&recaptchacompat=off" async defer></script>';
			$html .= '<div class="forminator-hcaptcha h-captcha" data-sitekey="' . esc_attr( $hcaptcha_key ) . '"></div>';
		} else {

			$html  = '<div role="alert" class="sui-notice sui-active" style="display: block; text-align: left;" aria-live="assertive">';
			$html .= '<div class="sui-notice-content">';
			$html .= '<div class="sui-notice-message">';
			$html .= '<span class="sui-notice-icon sui-icon-info" aria-hidden="true"></span>';
			$html .= '<p>' . esc_html__( 'Save your API keys to load the hCAPTCHA preview.', 'forminator' ) . '</p>';
			$html .= '</div>';
			$html .= '</div>';
			$html .= '</div>';
		}

		wp_send_json_success( $html );
	}

	/**
	 * Load Cloudflare Turnstile preview
	 */
	public function load_turnstile_preview() {

		forminator_validate_ajax( 'forminator_load_captcha_settings', false, 'forminator-settings' );

		$site_language    = get_locale();
		$language         = get_option( 'forminator_captcha_language', '' );
		$language         = ! empty( $language ) ? $language : $site_language;
		$language         = strtolower( str_replace( '_', '-', $language ) );
		$turnstile_key    = get_option( 'forminator_turnstile_key', '' );
		$turnstile_secret = get_option( 'forminator_turnstile_secret', '' );
		$html             = '';

		if ( ! empty( $turnstile_key ) && ! empty( $turnstile_secret ) ) {
			// phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript
			$html .= '<script src="' . esc_url( 'https://challenges.cloudflare.com/turnstile/v0/api.js?onload=forminator_render_admin_turnstile' ) . '" async defer></script>';
			$html .= '<div class="forminator-turnstile"
					data-sitekey="' . esc_attr( $turnstile_key ) . '"
					data-language="' . esc_attr( $language ) . '"
					></div>';

		} else {
			$html .= '<div role="alert" class="sui-notice sui-active" style="display: block; text-align: left;" aria-live="assertive">';
			$html .= '<div class="sui-notice-content">';
			$html .= '<div class="sui-notice-message">';
			$html .= '<span class="sui-notice-icon sui-icon-info" aria-hidden="true"></span>';
			$html .= '<p>' . esc_html__( 'Save your API keys to load the Turnstile preview.', 'forminator' ) . '</p>';
			$html .= '</div>';
			$html .= '</div>';
			$html .= '</div>';
		}

		wp_send_json_success( $html );
	}

	/**
	 * Load listings pagination modal
	 *
	 * @since 1.0.2
	 */
	public function load_pagination_listings() {
		// Validate nonce.
		forminator_validate_ajax( 'forminator_popup_pagination_listings', false, 'forminator-settings' );

		$html = forminator_template( 'settings/popup/edit-pagination-listings-content' );

		wp_send_json_success( $html );
	}

	/**
	 * Save listings pagination popup data
	 *
	 * @since 1.0.2
	 */
	public function save_pagination_listings() {
		// Validate nonce.
		forminator_validate_ajax( 'forminator_save_popup_pagination_listings', false, 'forminator-settings' );

		$pagination = filter_input( INPUT_POST, 'pagination_listings', FILTER_VALIDATE_INT );

		if ( 0 < $pagination ) {

			update_option( 'forminator_pagination_listings', $pagination );
			wp_send_json_success();

		} else {

			wp_send_json_error( esc_html__( 'Limit per page can not be less than one.', 'forminator' ) );

		}
	}

	/**
	 * Load the email settings form
	 *
	 * @since 1.1
	 */
	public function load_email_form() {
		// Validate nonce.
		forminator_validate_ajax( 'forminator_load_popup_email_settings', false, 'forminator-settings' );

		$html = forminator_template( 'settings/popup/edit-email-content' );

		wp_send_json_success( $html );
	}

	/**
	 * Load the uninstall form
	 *
	 * @since 1.0.2
	 */
	public function load_uninstall_form() {
		// Validate nonce.
		forminator_validate_ajax( 'forminator_popup_uninstall_form', false, 'forminator-settings' );

		$html = forminator_template( 'settings/popup/edit-uninstall-content' );

		wp_send_json_success( $html );
	}


	/**
	 * Save listings pagination popup data
	 *
	 * @since 1.0.2
	 */
	public function save_uninstall_form() {
		// Validate nonce.
		forminator_validate_ajax( 'forminator_save_popup_uninstall_settings', false, 'forminator-settings' );

		$delete_uninstall = Forminator_Core::sanitize_text_field( 'delete_uninstall', false );
		$delete_uninstall = filter_var( $delete_uninstall, FILTER_VALIDATE_BOOLEAN );

		$custom_upload      = Forminator_Core::sanitize_text_field( 'custom_upload', false );
		$custom_upload      = filter_var( $custom_upload, FILTER_VALIDATE_BOOLEAN );
		$custom_upload_root = Forminator_Core::sanitize_text_field( 'custom_upload_root' );
		$custom_upload_root = ! empty( $custom_upload_root ) ? $custom_upload_root : 'forminator';

		update_option( 'forminator_uninstall_clear_data', $delete_uninstall );

		// Custom upload.
		update_option( 'forminator_custom_upload', $custom_upload );
		update_option( 'forminator_custom_upload_root', $custom_upload_root );

		wp_send_json_success();
	}

	/**
	 * Preview module
	 *
	 * @since 1.0
	 */
	public function preview_module() {
		$current_action = current_action();
		$slug           = str_replace( array( 'wp_ajax_forminator_load_preview_', '_popup' ), '', $current_action );
		if ( 'cforms' === $slug ) {
			$slug = 'form';
		} elseif ( 'polls' === $slug ) {
			$slug = 'poll';
		} elseif ( 'quizzes' === $slug ) {
			$slug = 'quiz';
		}

		// Validate nonce.
		forminator_validate_ajax( 'forminator_popup_preview_' . $slug, false, 'forminator' );

		$preview_data = false;
		// force -1 for preview.
		$form_id = filter_input( INPUT_POST, 'id', FILTER_VALIDATE_INT );
		$form_id = $form_id ? $form_id : - 1;

		// Check if preview data set.
		if ( ! empty( $_POST['data'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified in forminator_validate_ajax.
			// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput -- Sanitized in Forminator_Core::sanitize_array.
			$data = Forminator_Core::sanitize_array( $_POST['data'], 'data' );

			if ( ! is_array( $data ) ) {
				$data = json_decode( $data, true );
			}
			$function     = 'forminator_data_to_model_' . $slug;
			$preview_data = $function( $data );
		}

		$html = forminator_preview( $form_id, $slug, true, $preview_data );

		wp_send_json_success( $html );
	}

	/**
	 * Load list of exports
	 *
	 * @since 1.0
	 */
	public function load_exports() {
		// Validate nonce.
		forminator_validate_ajax( 'forminator_load_exports', false, 'forminator-settings' );

		$form_id = filter_input( INPUT_POST, 'id', FILTER_VALIDATE_INT );

		if ( $form_id ) {
			$args = array(
				'form_id' => $form_id,
			);
			$html = forminator_template( 'settings/popup/exports-content', $args );
			wp_send_json_success( $html );
		} else {
			wp_send_json_error( esc_html__( 'Not valid module ID provided.', 'forminator' ) );
		}
	}

	/**
	 * Clear list of exports
	 *
	 * @since 1.0
	 */
	public function clear_exports() {
		// Validate nonce.
		forminator_validate_ajax( 'forminator_clear_exports', false, 'forminator-settings' );

		$form_id = filter_input( INPUT_POST, 'id', FILTER_VALIDATE_INT );

		if ( ! $form_id ) {
			wp_send_json_error( esc_html__( 'No ID was provided.', 'forminator' ) );
		}

		$was_cleared = delete_export_logs( $form_id );

		if ( $was_cleared ) {
			wp_send_json_success( esc_html__( 'Exports cleared.', 'forminator' ) );
		} else {
			wp_send_json_error( esc_html__( 'Exports couldn\'t be cleared.', 'forminator' ) );
		}
	}

	/**
	 * Search Emails
	 *
	 * @since 1.0.3
	 * @since 1.1 change superglobal POST to `get_post_data`
	 */
	public function search_emails() {
		$submitted_data  = $this->get_post_data();
		$property        = isset( $submitted_data['property'] ) ? $submitted_data['property'] : '';
		$permission_slug = isset( $submitted_data['permission'] ) ? $submitted_data['permission'] : '';

		forminator_validate_ajax( 'forminator_search_emails', false, $permission_slug );

		// TODO : add ajax validate here and js admin too.
		$admin_email    = ! empty( $submitted_data['admin_email'] ) ? true : false;
		$search_email   = ! empty( $submitted_data['q'] ) ? $submitted_data['q'] : false;
		$exclude_admins = ! empty( $submitted_data['exclude_admins'] ) ? $submitted_data['exclude_admins'] : false;
		$is_permission  = ! empty( $submitted_data['is_permission'] ) ? $submitted_data['is_permission'] : false;

		// return admin_email when requested.
		if ( $admin_email ) {
			wp_send_json_success( get_option( 'admin_email' ) );
		}

		if ( ! $search_email ) {
			wp_send_json_success( array() );
		}

		$args = array(
			'search'  => '*' . $search_email . '*',
			'number'  => 10,
			'orderby' => 'user_login',
			'order'   => 'ASC',
		);

		if ( $exclude_admins ) {
			$args['role__not_in'] = 'Administrator';
		}

		$role = isset( $submitted_data['role'] ) ? $submitted_data['role'] : array();
		if ( ! empty( $role ) ) {
			$args['role__in'] = $role;
		}

		/**
		 * Filter args to be passed on to get_users
		 *
		 * @param array $args
		 * @param string $search_email string to search.
		 *
		 * @see   get_users()
		 *
		 * @since 1.2
		 */
		$args = apply_filters( 'forminator_builder_search_emails_args', $args, $search_email );

		// Create a single array of users in permissions.
		if ( 'specific_user' === $property ) {
			$permitted_users = array();
			$permissions     = get_option( 'forminator_permissions', array() );
			$pid             = isset( $submitted_data['pid'] ) ? $submitted_data['pid'] : '';

			foreach ( $permissions as $permission ) {
				if (
					(string) $pid !== (string) $permission['pid'] &&
					isset( $permission['specific_user'] )
				) {
					$permitted_users = array_merge( $permitted_users, $permission['specific_user'] );
				}
			}
		}

		$users = get_users( $args );
		$data  = array();
		if ( ! empty( $users ) ) {
			foreach ( $users as $user ) {

				// Check if user has already been added to other permissions.
				if ( 'specific_user' === $property ) {
					if ( in_array( $user->ID, $permitted_users ) ) { // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict -- Possible of 0 or '0'.
						continue;
					}
				}

				if ( $is_permission ) {

					$data[] = array(
						'id'           => $user->ID,
						'text'         => $user->user_email,
						'display_name' => $user->display_name,
					);

				} else {

					$data[] = array(
						'id'           => $user->user_email,
						'text'         => $user->user_email,
						'display_name' => $user->display_name,
					);
				}
			}
		}

		/**
		 * Filter returned data when builder search emails
		 *
		 * @param array $data
		 * @param array $users search result of get_users.
		 * @param array $args current query args passed to get_users.
		 * @param string $search_email string to search.
		 *
		 * @since 1.2
		 */
		$data = apply_filters( 'forminator_builder_search_emails_data', $data, $users, $args, $search_email );

		wp_send_json_success( $data );
	}

	/**
	 * Get superglobal POST data
	 *
	 * @param string $nonce_action action to validate.
	 * @param array  $sanitize_callbacks {
	 *                                    custom sanitize options, its assoc array
	 *                                    'field_name_1' => 'function_to_call_1' function will called with `call_user_func_array`,
	 *                                    'field_name_2' => 'function_to_call_2',
	 *                                    }.
	 * @param string $permission_slug The slug that will be used to get the capability for checking.
	 *
	 * @return array
	 * @since 1.1
	 */
	protected function get_post_data( $nonce_action = '', $sanitize_callbacks = array(), $permission_slug = '' ) {
		// do nonce / caps check when requested.
		if ( ! empty( $nonce_action ) ) {
			// it will wp_send_json_error.
			forminator_validate_ajax( $nonce_action, false, $permission_slug );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified in forminator_validate_ajax.
		$post_data = Forminator_Core::sanitize_array( $_POST );

		// do some additional sanitize.
		foreach ( $sanitize_callbacks as $field => $sanitize_func ) {
			if ( isset( $post_data[ $field ] ) ) {
				if ( is_callable( $sanitize_func ) ) {
					$post_data[ $field ] = call_user_func_array( array( $sanitize_func ), array( $post_data[ $field ] ) );
				}
			}
		}

		// do some validation.

		return $post_data;
	}

	/**
	 * Load Privacy Settings
	 *
	 * @since 1.0.6
	 */
	public function load_privacy_settings() {
		// Validate nonce.
		forminator_validate_ajax( 'forminator_popup_privacy_settings', false, 'forminator-settings' );

		$html = forminator_template( 'settings/popup/edit-privacy-settings' );

		wp_send_json_success( $html );
	}

	/**
	 * Save Privacy Settings
	 *
	 * @since 1.0.6
	 */
	public function save_privacy_settings() {
		// Validate nonce.
		forminator_validate_ajax( 'forminator_save_privacy_settings', false, 'forminator-settings' );
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified in forminator_validate_ajax.
		$post_data = Forminator_Core::sanitize_array( $_POST );

		/**
		 * CUSTOM FORMS
		 */
		// Account Erasure Requests.
		if ( isset( $post_data['erase_form_submissions'] ) ) {
			$enable_erasure_request_erase_form_submissions = filter_var( $post_data['erase_form_submissions'], FILTER_VALIDATE_BOOLEAN );
			update_option( 'forminator_enable_erasure_request_erase_form_submissions', $enable_erasure_request_erase_form_submissions );
		}
		// Account Erasure Requests.

		// Submissions Retention.
		$cform_retain_forever = filter_var( $post_data['form_retain_submission_forever'], FILTER_VALIDATE_BOOLEAN );
		update_option( 'retain_submission_forever', (string) $cform_retain_forever );
		if ( $cform_retain_forever ) {
			$post_data['form_retain_submission_number'] = 0;
		}
		if ( isset( $post_data['form_retain_submission_number'] ) ) {
			$submissions_retention_number = intval( $post_data['form_retain_submission_number'] );
			if ( $submissions_retention_number < 0 ) {
				$submissions_retention_number = 0;
			}
			update_option( 'forminator_retain_submissions_interval_number', $submissions_retention_number );
		}
		update_option( 'forminator_retain_submissions_interval_unit', $post_data['form_retain_submission_unit'] );
		// Submissions Retention.

		// IP Retention.
		$cform_retain_ip_forever = filter_var( $post_data['form_retain_ip_forever'], FILTER_VALIDATE_BOOLEAN );
		update_option( 'retain_ip_forever', (string) $cform_retain_ip_forever );
		if ( $cform_retain_ip_forever ) {
			$post_data['form_retain_ip_number'] = 0;
		}
		if ( isset( $post_data['form_retain_ip_number'] ) ) {
			$cform_ip_retention_number = intval( $post_data['form_retain_ip_number'] );
			if ( $cform_ip_retention_number < 0 ) {
				$cform_ip_retention_number = 0;
			}
			update_option( 'forminator_retain_ip_interval_number', $cform_ip_retention_number );
		}
		update_option( 'forminator_retain_ip_interval_unit', $post_data['form_retain_ip_unit'] );
		// IP Retention.

		// Geolocation Retention.
		$cform_retain_geolocation_forever = filter_var( $post_data['form_retain_geolocation_forever'], FILTER_VALIDATE_BOOLEAN );
		update_option( 'retain_geolocation_forever', (string) $cform_retain_geolocation_forever );
		if ( $cform_retain_geolocation_forever ) {
			$post_data['form_retain_geolocation_number'] = 0;
		}
		if ( isset( $post_data['form_retain_geolocation_number'] ) ) {
			$cform_geolocation_retention_number = intval( $post_data['form_retain_geolocation_number'] );
			if ( $cform_geolocation_retention_number < 0 ) {
				$cform_geolocation_retention_number = 0;
			}
			update_option( 'forminator_retain_geolocation_interval_number', $cform_geolocation_retention_number );
		}
		update_option( 'forminator_retain_geolocation_interval_unit', $post_data['form_retain_geolocation_unit'] );

		/**
		 * POLLS
		 */
		// Submissions Retention.
		$poll_retain_submissions_forever = filter_var( $post_data['poll_retain_submission_forever'], FILTER_VALIDATE_BOOLEAN );
		update_option( 'poll_retain_submission_forever', (string) $poll_retain_submissions_forever );
		if ( $poll_retain_submissions_forever ) {
			$post_data['poll_retain_submission_number'] = 0;
		}
		// Polls.
		if ( isset( $post_data['poll_retain_submission_number'] ) ) {
			$poll_submissions_retention_number = intval( $post_data['poll_retain_submission_number'] );
			if ( $poll_submissions_retention_number < 0 ) {
				$poll_submissions_retention_number = 0;
			}
			update_option( 'forminator_retain_poll_submissions_interval_number', $poll_submissions_retention_number );
		}
		update_option( 'forminator_retain_poll_submissions_interval_unit', $post_data['poll_retain_submission_unit'] );
		// Submissions Retention.

		// IP Retention.
		$poll_retain_ip_forever = filter_var( $post_data['poll_retain_ip_forever'], FILTER_VALIDATE_BOOLEAN );
		update_option( 'retain_poll_forever', (string) $poll_retain_ip_forever );
		if ( $poll_retain_ip_forever ) {
			$post_data['poll_retain_ip_number'] = 0;
		}
		if ( isset( $post_data['poll_retain_ip_number'] ) ) {
			$votes_retention_number = intval( $post_data['poll_retain_ip_number'] );
			if ( $votes_retention_number < 0 ) {
				$votes_retention_number = 0;
			}
			update_option( 'forminator_retain_votes_interval_number', $votes_retention_number );
		}
		update_option( 'forminator_retain_votes_interval_unit', $post_data['poll_retain_ip_unit'] );
		// IP Retention.

		/**
		 * QUIZ
		 */
		// Submissions Retention.
		$quiz_retain_submissions_forever = filter_var( $post_data['quiz_retain_submission_forever'], FILTER_VALIDATE_BOOLEAN );
		update_option( 'quiz_retain_submission_forever', (string) $quiz_retain_submissions_forever );
		if ( $quiz_retain_submissions_forever ) {
			$post_data['quiz_retain_submission_number'] = 0;
		}
		if ( isset( $post_data['quiz_retain_submission_number'] ) ) {
			$quiz_submissions_retention_number = intval( $post_data['quiz_retain_submission_number'] );
			if ( $quiz_submissions_retention_number < 0 ) {
				$quiz_submissions_retention_number = 0;
			}
			update_option( 'forminator_retain_quiz_submissions_interval_number', $quiz_submissions_retention_number );
		}
		update_option( 'forminator_retain_quiz_submissions_interval_unit', $post_data['quiz_retain_submission_unit'] );
		// Submissions Retention.

		wp_send_json_success();
	}

	/**
	 * AJAX Reset tracking data
	 */
	public function reset_tracking_data() {
		// Validate nonce.
		forminator_validate_ajax( 'forminator_reset_tracking_data', false, 'forminator' );

		$id = filter_input( INPUT_POST, 'id', FILTER_VALIDATE_INT );
		if ( ! $id ) {
			wp_send_json_error( esc_html__( 'Required id parameter is not set.', 'forminator' ) );
		}

		Forminator_Admin_Module_Edit_Page::reset_module_views( $id );

		wp_send_json_success( esc_html__( 'Tracking Data has been reset successfully.', 'forminator' ) );
	}

	/**
	 * Execute Import Form
	 *
	 * @since 1.5
	 */
	public function save_import() {
		if ( ! Forminator::is_import_export_feature_enabled() ) {
			wp_send_json_error( esc_html__( 'Import Export Feature disabled.', 'forminator' ) );
		}
		if ( empty( $_POST['importable'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			wp_send_json_error( esc_html__( 'Import text can not be empty.', 'forminator' ) );
		}
		$current_action = current_action();
		$slug           = str_replace( array( 'wp_ajax_forminator_save_import_', '_popup' ), '', $current_action );
		// Validate nonce.
		forminator_validate_ajax( 'forminator_save_import_' . $slug, false, 'forminator-settings' );

		// Modify recipients if replace all recipients checkbox has been checked.
		$change_recipients = 'checked' === Forminator_Core::sanitize_text_field( 'change_recipients' );

		$save_to_cloud = 'checked' === Forminator_Core::sanitize_text_field( 'save_to_cloud' );

		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput
		$json  = wp_unslash( $_POST['importable'] );
		$model = $this->import_json( $json, $slug, $change_recipients );

		$return_url = admin_url( 'admin.php?page=forminator-' . forminator_get_prefix( $slug, 'c' ) );

		if ( $save_to_cloud && ! forminator_cloud_templates_disabled() && forminator_is_site_connected_to_hub() ) {
			Forminator_Template_API::create_template( $model->name, wp_json_encode( $model->to_exportable_data() ) );
		}
		/**
		 * Fires after form import
		 *
		 * @since 1.27.0
		 *
		 * @param string $slug Module type.
		 */
		do_action( 'forminator_after_form_import', $slug );

		wp_send_json_success(
			array(
				'id'  => $model->id,
				'url' => $return_url,
			)
		);
	}

	/**
	 * Create module from template
	 *
	 * @throws Exception When failed to create form template.
	 */
	public function create_module_from_template() {
		// Validate nonce.
		forminator_validate_ajax( 'forminator_create_form_from_template', false, 'forminator-cform' );
		forminator_validate_ajax( 'forminator_create_form_from_template', false, 'forminator-templates' );

		$template_id = filter_input( INPUT_POST, 'id' );

		if ( empty( $template_id ) ) {
			wp_send_json_error( esc_html__( 'Template ID is missing.', 'forminator' ) );
		}

		$form_admin = new Forminator_Custom_Form_Admin();
		try {
			$id = $form_admin->create_module_from_template( $template_id, '', 'template' );
			if ( is_wp_error( $id ) ) {
				throw new Exception( $id->get_error_message() );
			}
		} catch ( Exception $e ) {
			wp_send_json_error( $e->getMessage() );
		}

		$return_url = admin_url( 'admin.php?page=forminator-cform-wizard&create-status=success&id=' . $id );

		wp_send_json_success(
			array(
				'id'  => $id,
				'url' => $return_url,
			)
		);
	}

	/**
	 * Import Form
	 *
	 * @param string $json JSON data to import.
	 * @param string $slug Module type.
	 * @param bool   $change_recipients Change recipients.
	 * @param bool   $draft Draft status.
	 *
	 * @throws Exception When import failed.
	 */
	public function import_json( string $json, string $slug, bool $change_recipients, bool $draft = false ) {
		try {
			$model = Forminator_Admin_Module::import_json( $json, '', $slug, $change_recipients, $draft );
			return $model;
		} catch ( Exception $e ) {
			wp_send_json_error( $e->getMessage() );
		}
	}

	/**
	 * Get instance of thirdparty importer class
	 *
	 * @param string $type Import from.
	 *
	 * @since 1.5
	 */
	public function importers( $type ) {

		$class = '';

		switch ( $type ) {
			case 'cf7':
				if ( class_exists( 'Forminator_Admin_Import_CF7' ) ) {
					$class = new Forminator_Admin_Import_CF7();
				}
				break;
			case 'ninja':
				if ( class_exists( 'Forminator_Admin_Import_Ninja' ) ) {
					$class = new Forminator_Admin_Import_Ninja();
				}
				break;
			case 'gravity':
				if ( class_exists( 'Forminator_Admin_Import_Gravity' ) ) {
					return new Forminator_Admin_Import_Gravity();
				}
				break;
			default:
				break;
		}

		return $class;
	}


	/**
	 * Load Import Custom Form Popup
	 *
	 * @since 1.5
	 */
	public function load_import_form_cf7() {
		if ( ! Forminator::is_import_export_feature_enabled() || ! forminator_is_import_plugin_enabled( 'cf7' ) ) {
			wp_send_json_success( '' );
		}
		// Validate nonce.
		forminator_validate_ajax( 'forminator_popup_import_form_cf7', false, 'forminator-settings' );

		$html = forminator_template( 'custom-form/popup/import-cf7' );

		wp_send_json_success( $html );
	}


	/**
	 * Execute Contact Form 7 Import Form
	 *
	 * @since 1.5
	 */
	public function save_import_form_cf7() {

		if ( ! Forminator::is_import_export_feature_enabled() || ! forminator_is_import_plugin_enabled( 'cf7' ) ) {
			wp_send_json_error( esc_html__( 'Import Export Feature disabled.', 'forminator' ) );
		}
		// Validate nonce.
		forminator_validate_ajax( 'forminator_save_import_form_cf7', false, 'forminator-settings' );

		$post_data  = $this->get_post_data();
		$importable = ( isset( $post_data['cf7_forms'] ) ? $post_data['cf7_forms'] : '' );
		$importer   = $this->importers( 'cf7' );
		if ( ! empty( $importer ) ) :
			if ( ! empty( $importable ) ) {
				if ( 'specific' === $importable ) {
					$forms = isset( $post_data['cf7-form-id'] ) ? $post_data['cf7-form-id'] : array();
				} else {
					$forms = forminator_list_thirdparty_contact_forms( 'cf7' );
				}
				if ( ! empty( $forms ) ) {
					foreach ( $forms as $key => $value ) {
						$values   = 'specific' === $importable ? intval( $value ) : $value->ID;
						$imported = $importer->import_form( $values, $post_data );

						if ( 'fail' === $imported['type'] ) {

							$error = $imported['message'];
						}
					}
					if ( ! empty( $error ) ) {
						wp_send_json_error( $error );
					}

					wp_send_json_success( $imported );
				}
			} else {
				wp_send_json_error( esc_html__( 'Can\'t find form to import', 'forminator' ) );
			}
		endif;

		wp_send_json_error( esc_html__( 'Could not import the forms. Check if the selected form plugin is active', 'forminator' ) );
	}


	/**
	 * Load Import Custom Form Popup
	 *
	 * @since 1.5
	 */
	public function load_import_form_ninja() {
		if ( ! Forminator::is_import_export_feature_enabled() || ! forminator_is_import_plugin_enabled( 'ninjaforms' ) ) {
			wp_send_json_success( '' );
		}
		// Validate nonce.
		forminator_validate_ajax( 'forminator_popup_import_form_ninjaforms', false, 'forminator-settings' );

		$html = forminator_template( 'custom-form/popup/import-ninjaforms' );

		wp_send_json_success( $html );
	}

	/**
	 * Execute Ninjaforms Import Form Save
	 *
	 * @since 1.5
	 */
	public function save_import_form_ninja() {

		if ( ! Forminator::is_import_export_feature_enabled() || ! forminator_is_import_plugin_enabled( 'ninjaforms' ) ) {
			wp_send_json_error( esc_html__( 'Import Export Feature disabled.', 'forminator' ) );
		}
		// Validate nonce.
		forminator_validate_ajax( 'forminator_save_import_form_ninja', false, 'forminator-settings' );

		$importable = Forminator_Core::sanitize_text_field( 'ninjaforms' );
		$importer   = ( ! empty( $this->importers( 'ninja' ) ) ? $this->importers( 'ninja' ) : '' );

		if ( ! empty( $importer ) ) :
			if ( 'all' !== $importable && '' !== $importable ) {

				$importable = absint( $importable );
				$imported   = $importer->import_form( $importable );

				if ( 'fail' === $imported['type'] ) {
					wp_send_json_error( $imported['message'] );
				}

				wp_send_json_success( $imported );

			} elseif ( '' !== $importable ) {
				$forms = forminator_list_thirdparty_contact_forms( 'ninjaforms' );

				foreach ( $forms as $key => $value ) {
					$imported = $importer->import_form( $value->get_id() );

					if ( 'fail' === $imported['type'] ) {
						$error = $imported['message'];
					}
				}

				if ( ! empty( $error ) ) {
					wp_send_json_error( $error );
				}

				wp_send_json_success( $imported );
			}
		endif;

		wp_send_json_error( esc_html__( 'Could not import the forms. Check if the selected form plugin is active', 'forminator' ) );
	}

	/**
	 * Load Import Custom Form Popup
	 *
	 * @since 1.5
	 */
	public function load_import_form_gravity() {
		if ( ! Forminator::is_import_export_feature_enabled() || ! forminator_is_import_plugin_enabled( 'gravityforms' ) ) {
			wp_send_json_success( '' );
		}
		// Validate nonce.
		forminator_validate_ajax( 'forminator_popup_import_form_gravityforms', false, 'forminator-settings' );

		$html = forminator_template( 'custom-form/popup/import-gravityforms' );

		wp_send_json_success( $html );
	}

	/**
	 * Execute Ninjaforms Import Form Save
	 *
	 * @since 1.5
	 */
	public function save_import_form_gravity() {

		if ( ! Forminator::is_import_export_feature_enabled() || ! forminator_is_import_plugin_enabled( 'gravityforms' ) ) {
			wp_send_json_error( esc_html__( 'Import Export Feature disabled.', 'forminator' ) );
		}
		// Validate nonce.
		forminator_validate_ajax( 'forminator_save_import_form_gravity', false, 'forminator-settings' );

		$importable = Forminator_Core::sanitize_text_field( 'gravityforms' );
		$importer   = ( ! empty( $this->importers( 'gravity' ) ) ? $this->importers( 'gravity' ) : '' );

		if ( ! empty( $importer ) ) :
			if ( 'all' !== $importable && '' !== $importable ) {

				$importable = absint( $importable );
				$imported   = $importer->import_form( $importable );

				if ( 'fail' === $imported['type'] ) {
					wp_send_json_error( $imported['message'] );
				}

				wp_send_json_success( $imported );

			} elseif ( '' !== $importable ) {
				$forms = forminator_list_thirdparty_contact_forms( 'gravityforms' );

				foreach ( $forms as $key => $value ) {
					$imported = $importer->import_form( $value['id'] );

					if ( 'fail' === $imported['type'] ) {
						$error = $imported['message'];
					}
				}

				if ( ! empty( $error ) ) {
					wp_send_json_error( $error );
				}

				wp_send_json_success( $imported );
			}
		endif;
	}

	/**
	 * Load Export Module Popup
	 *
	 * @since 1.5
	 */
	public function load_export() {
		if ( ! Forminator::is_import_export_feature_enabled() ) {
			wp_send_json_success( '' );
		}
		$current_action = current_action();
		$slug           = str_replace( array( 'wp_ajax_forminator_load_export_', '_popup' ), '', $current_action );
		// Validate nonce.
		forminator_validate_ajax( 'forminator_popup_export_' . $slug, false, 'forminator' );

		$html = forminator_template( 'common/popup/export', array( 'slug' => $slug ) );

		/**
		 * Fires after form export
		 *
		 * @since 1.27.0
		 *
		 * @param int $slug Module type.
		 */

		do_action( 'forminator_after_form_export', $slug );

		wp_send_json_success( $html );
	}

	/**
	 * Load Import Popup
	 *
	 * @since 1.5
	 */
	public function load_import() {
		if ( ! Forminator::is_import_export_feature_enabled() ) {
			wp_send_json_success( '' );
		}

		$current_action = current_action();
		$slug           = str_replace( array( 'wp_ajax_forminator_load_import_', '_popup' ), '', $current_action );
		// Validate nonce.
		forminator_validate_ajax( 'forminator_popup_import_' . $slug, false, 'forminator' );

		$html = forminator_template( 'common/popup/import', array( 'slug' => $slug ) );

		wp_send_json_success( $html );
	}

	/**
	 * Save pagination data
	 *
	 * @since 1.6
	 */
	public function save_pagination() {
		// Validate nonce.
		forminator_validate_ajax( 'forminator_save_popup_pagination', false, 'forminator' );

		$pagination         = filter_input( INPUT_POST, 'pagination_entries', FILTER_VALIDATE_INT );
		$pagination_listing = filter_input( INPUT_POST, 'pagination_listings', FILTER_VALIDATE_INT );

		if ( 1 > $pagination || 1 > $pagination_listing ) {
			wp_send_json_error( esc_html__( 'Limit per page can not be less than one.', 'forminator' ) );
		}

		update_option( 'forminator_pagination_entries', $pagination );
		update_option( 'forminator_pagination_listings', $pagination_listing );
		wp_send_json_success();
	}

	/**
	 * Save accessibility_settings
	 *
	 * @since 1.6.1
	 */
	public function save_accessibility_settings() {
		// Validate nonce.
		forminator_validate_ajax( 'forminator_save_accessibility_settings', false, 'forminator-settings' );

		$enable_accessibility = filter_input( INPUT_POST, 'enable_accessibility', FILTER_VALIDATE_BOOLEAN );

		update_option( 'forminator_enable_accessibility', $enable_accessibility );
		wp_send_json_success();
	}

	/**
	 * Save dashboard
	 *
	 * @since 1.6.3
	 */
	public function save_dashboard_settings() {
		// Validate nonce.
		forminator_validate_ajax( 'forminator_save_dashboard_settings', false, 'forminator-settings' );

		$dashboard_settings = forminator_get_dashboard_settings();
		$widgets            = array( 'forms', 'polls', 'quizzes' );

		$num_recents = filter_input( INPUT_POST, 'num_recent', FILTER_VALIDATE_INT, FILTER_REQUIRE_ARRAY );
		$num_recents = is_array( $num_recents ) ? $num_recents : array();
		$publisheds  = filter_input( INPUT_POST, 'published', FILTER_VALIDATE_BOOLEAN, FILTER_REQUIRE_ARRAY );
		$publisheds  = is_array( $publisheds ) ? $publisheds : array();
		$drafts      = filter_input( INPUT_POST, 'draft', FILTER_VALIDATE_BOOLEAN, FILTER_REQUIRE_ARRAY );
		$drafts      = is_array( $drafts ) ? $drafts : array();

		// value based settings.
		foreach ( $num_recents as $widget => $value ) {
			if ( ! isset( $dashboard_settings[ $widget ] ) ) {
				$dashboard_settings[ $widget ] = array();
			}
			// at least 0.
			if ( $value >= 0 ) {
				$dashboard_settings[ $widget ]['num_recent'] = $value;
			}
		}

		// bool based settings aka checkboxes.
		foreach ( $widgets as $widget ) {
			if ( ! isset( $dashboard_settings[ $widget ] ) ) {
				$dashboard_settings[ $widget ] = array();
			}

			// default enabled, handle when not exist = false.
			if ( ! isset( $publisheds[ $widget ] ) ) {
				$dashboard_settings[ $widget ]['published'] = false;
			}
			if ( ! isset( $drafts[ $widget ] ) ) {
				$dashboard_settings[ $widget ]['draft'] = false;
			}
		}

		update_option( 'forminator_dashboard_settings', $dashboard_settings );
		$sender_email = filter_input( INPUT_POST, 'sender_email', FILTER_SANITIZE_EMAIL );
		if ( $sender_email ) {
			update_option( 'forminator_sender_email_address', $sender_email );
		}
		$sender_name = Forminator_Core::sanitize_text_field( 'sender_name' );
		if ( $sender_name ) {
			update_option( 'forminator_sender_name', $sender_name );
		}

		$pagination         = filter_input( INPUT_POST, 'pagination_entries', FILTER_VALIDATE_INT );
		$pagination_listing = filter_input( INPUT_POST, 'pagination_listings', FILTER_VALIDATE_INT );

		if ( 1 > $pagination || 1 > $pagination_listing ) {
			wp_send_json_error( esc_html__( 'Limit per page can not be less than one.', 'forminator' ) );
		}

		update_option( 'forminator_pagination_entries', $pagination );
		update_option( 'forminator_pagination_listings', $pagination_listing );

		$old_auto_saving = get_option( 'forminator_auto_saving', true );
		$auto_saving     = filter_input( INPUT_POST, 'auto_saving', FILTER_VALIDATE_BOOLEAN );
		update_option( 'forminator_auto_saving', $auto_saving );
		if ( (bool) $old_auto_saving !== (bool) $auto_saving ) {
			do_action( 'forminator_auto_save_setting', (bool) $auto_saving );
		}

		$editor_settings = Forminator_Core::sanitize_text_field( 'editor_settings', false );
		update_option( 'forminator_editor_settings', $editor_settings );

		$old_usage_tracking = get_option( 'forminator_usage_tracking' );
		$usage_tracking     = filter_input( INPUT_POST, 'usage_tracking', FILTER_VALIDATE_BOOLEAN );
		if ( ! $old_usage_tracking && $usage_tracking ) {
			Forminator_Core::init_mixpanel( true );
			do_action( 'forminator_enable_usage_tracking', 'Settings' );
		} elseif ( $old_usage_tracking && ! $usage_tracking ) {
			do_action( 'forminator_disable_usage_tracking', 'Settings' );
		}
		update_option( 'forminator_usage_tracking', $usage_tracking );

		/**
		 * Triggered after save dashboard settings
		 *
		 * @since 1.27.0
		 */
		do_action( 'forminator_after_dashboard_settings' );

		wp_send_json_success();
	}

	/**
	 * Disconnect stripe
	 *
	 * @since 1.7
	 */
	public function stripe_disconnect() {
		// Validate nonce.
		forminator_validate_ajax( 'forminatorSettingsRequest', false, 'forminator-settings' );

		if ( class_exists( 'Forminator_Gateway_Stripe' ) ) {
			Forminator_Gateway_Stripe::store_settings( array() );
		}
		$data['notification'] = array(
			'type'     => 'success',
			'text'     => esc_html__( 'Stripe account disconnected successfully.', 'forminator' ),
			'duration' => '4000',
		);
		$file                 = forminator_plugin_dir() . 'admin/views/settings/payments/section-stripe.php';

		ob_start();
		/* @noinspection PhpIncludeInspection */
		include $file;
		$data['html'] = ob_get_clean();

		wp_send_json_success( $data );
	}

	/**
	 * Disconnect PayPal
	 *
	 * @since 1.7
	 */
	public function paypal_disconnect() {
		// Validate nonce.
		forminator_validate_ajax( 'forminatorSettingsRequest', false, 'forminator-settings' );

		if ( class_exists( 'Forminator_PayPal_Express' ) ) {
			Forminator_PayPal_Express::store_settings( array() );
		}
		$data['notification'] = array(
			'type'     => 'success',
			'text'     => esc_html__( 'PayPal account disconnected successfully.', 'forminator' ),
			'duration' => '4000',
		);
		$file                 = forminator_plugin_dir() . 'admin/views/settings/payments/section-paypal.php';

		ob_start();
		/* @noinspection PhpIncludeInspection */
		include $file;
		$data['html'] = ob_get_clean();

		wp_send_json_success( $data );
	}

	/**
	 * Handle stripe settings
	 *
	 * @since 1.7
	 */
	public function stripe_update_page() {
		// Validate nonce.
		forminator_validate_ajax( 'forminator_stripe_settings_modal', false, 'forminator-settings' );

		$file = forminator_plugin_dir() . 'admin/views/settings/payments/section-stripe.php';

		ob_start();
		/* @noinspection PhpIncludeInspection */
		include $file;
		$html = ob_get_clean();

		wp_send_json_success( $html );
	}

	/**
	 * Handle PayPal settings
	 *
	 * @since 1.7
	 */
	public function paypal_update_page() {
		// Validate nonce.
		forminator_validate_ajax( 'forminator_paypal_settings_modal', false, 'forminator-settings' );

		$file = forminator_plugin_dir() . 'admin/views/settings/payments/section-paypal.php';

		ob_start();
		/* @noinspection PhpIncludeInspection */
		include $file;
		$html = ob_get_clean();

		wp_send_json_success( $html );
	}

	/**
	 * Handle stripe settings
	 *
	 * @since 1.7
	 * @throws Forminator_Gateway_Exception When connection fails.
	 */
	public function stripe_settings_modal() {
		if ( ! class_exists( 'Forminator_Gateway_Stripe' ) ) {
			return false;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified in forminator_validate_ajax.
		$post_data = Forminator_Core::sanitize_array( $_POST );

		// Validate nonce.
		forminator_validate_ajax( 'forminator_stripe_settings_modal', false, $post_data['page_slug'] );

		$data = array();

		$is_connect_request = isset( $post_data['connect'] ) ? $post_data['connect'] : false;
		$template_vars      = array();
		try {
			$stripe = new Forminator_Gateway_Stripe();

			$test_key         = isset( $post_data['test_key'] ) ? $post_data['test_key'] : $stripe->get_test_key();
			$test_secret      = isset( $post_data['test_secret'] ) ? $post_data['test_secret'] : $stripe->get_test_secret();
			$live_key         = isset( $post_data['live_key'] ) ? $post_data['live_key'] : $stripe->get_live_key();
			$live_secret      = isset( $post_data['live_secret'] ) ? $post_data['live_secret'] : $stripe->get_live_secret();
			$page_slug        = isset( $post_data['page_slug'] ) ? $post_data['page_slug'] : '';
			$default_currency = $stripe->get_default_currency();

			$template_vars['test_key']    = $test_key;
			$template_vars['test_secret'] = $test_secret;
			$template_vars['live_key']    = $live_key;
			$template_vars['live_secret'] = $live_secret;
			if ( ( ! empty( $test_secret ) && 'sk_' === substr( $test_secret, 0, 3 ) ) || ( ! empty( $live_secret ) && 'sk_' === substr( $live_secret, 0, 3 ) ) ) {
				$template_vars['has_deprecated_secret_key'] = true;
			}
			if ( ! empty( $is_connect_request ) ) {
				if ( empty( $test_key ) ) {
					throw new Forminator_Gateway_Exception(
						'',
						Forminator_Gateway_Stripe::EMPTY_TEST_KEY_EXCEPTION
					);
				}
				if ( empty( $test_secret ) ) {
					throw new Forminator_Gateway_Exception(
						'',
						Forminator_Gateway_Stripe::EMPTY_TEST_SECRET_EXCEPTION
					);
				}

				Forminator_Gateway_Stripe::validate_keys( $test_key, $test_secret, Forminator_Gateway_Stripe::INVALID_TEST_SECRET_EXCEPTION );

				if ( empty( $live_key ) ) {
					throw new Forminator_Gateway_Exception(
						'',
						Forminator_Gateway_Stripe::EMPTY_LIVE_KEY_EXCEPTION
					);
				}
				if ( empty( $live_secret ) ) {
					throw new Forminator_Gateway_Exception(
						'',
						Forminator_Gateway_Stripe::EMPTY_LIVE_SECRET_EXCEPTION
					);
				}

				Forminator_Gateway_Stripe::validate_keys( $live_key, $live_secret, Forminator_Gateway_Stripe::INVALID_LIVE_SECRET_EXCEPTION );

				/**
				 * Fires before stripe connected
				 *
				 * @since 1.35.0
				 *
				 * @param string $test_key Test key.
				 * @param string $test_secret Test secret.
				 * @param string $live_key Live key.
				 * @param string $live_secret Live secret.
				 * @param string $page_slug Page slug.
				 */
				do_action( 'forminator_before_stripe_connected', $test_key, $test_secret, $live_key, $live_secret, $page_slug );

				Forminator_Gateway_Stripe::store_settings(
					array(
						'test_key'         => $test_key,
						'test_secret'      => $test_secret,
						'live_key'         => $live_key,
						'live_secret'      => $live_secret,
						'default_currency' => $default_currency,
					)
				);

				$data['notification'] = array(
					'type'     => 'success',
					'text'     => esc_html__( 'Stripe account connected successfully. You can now add the Stripe field to your forms and start collecting payments.', 'forminator' ),
					'duration' => '4000',
				);

			}
		} catch ( Forminator_Gateway_Exception $e ) {
			forminator_maybe_log( __METHOD__, $e->getMessage(), $e->getTrace() );
			$template_vars['error_message'] = $e->getMessage();

			if ( Forminator_Gateway_Stripe::EMPTY_TEST_KEY_EXCEPTION === $e->getCode() ) {
				$template_vars['test_key_error'] = esc_html__( 'Please input test publishable key', 'forminator' );
			}
			if ( Forminator_Gateway_Stripe::EMPTY_TEST_SECRET_EXCEPTION === $e->getCode() ) {
				$template_vars['test_secret_error'] = esc_html__( 'Please input test secret key', 'forminator' );
			}
			if ( Forminator_Gateway_Stripe::EMPTY_LIVE_KEY_EXCEPTION === $e->getCode() ) {
				$template_vars['live_key_error'] = esc_html__( 'Please input live publishable key', 'forminator' );
			}
			if ( Forminator_Gateway_Stripe::EMPTY_LIVE_SECRET_EXCEPTION === $e->getCode() ) {
				$template_vars['live_secret_error'] = esc_html__( 'Please input live secret key', 'forminator' );
			}
			if ( Forminator_Gateway_Stripe::INVALID_TEST_SECRET_EXCEPTION === $e->getCode() ) {
				if ( ! empty( $test_secret ) && 'sk_' === substr( $test_secret, 0, 3 ) ) {
					$template_vars['test_secret_error'] = esc_html__( 'You\'ve entered an invalid test secret key', 'forminator' );
				} else {
					$template_vars['test_secret_error'] = esc_html__( 'You\'ve entered an invalid test restricted key', 'forminator' );
				}
			}
			if ( Forminator_Gateway_Stripe::INVALID_LIVE_SECRET_EXCEPTION === $e->getCode() ) {
				if ( ! empty( $live_secret ) && 'sk_' === substr( $live_secret, 0, 3 ) ) {
					$template_vars['live_secret_error'] = esc_html__( 'You\'ve entered an invalid live secret key', 'forminator' );
				} else {
					$template_vars['live_secret_error'] = esc_html__( 'You\'ve entered an invalid live restricted key', 'forminator' );
				}
			}
			if ( Forminator_Gateway_Stripe::INVALID_TEST_KEY_EXCEPTION === $e->getCode() ) {
				$template_vars['test_key_error'] = esc_html__( 'You\'ve entered an invalid test publishable key', 'forminator' );
			}
			if ( Forminator_Gateway_Stripe::INVALID_LIVE_KEY_EXCEPTION === $e->getCode() ) {
				$template_vars['live_key_error'] = esc_html__( 'You\'ve entered an invalid live publishable key', 'forminator' );
			}
		}

		ob_start();
		/* @noinspection PhpIncludeInspection */
		include forminator_plugin_dir() . 'admin/views/settings/payments/stripe.php';
		$html = ob_get_clean();

		$data['html'] = $html;

		$data['buttons'] = array();

		$data['buttons']['connect']['markup'] = '<div class="sui-actions-right">' .
												'<button class="sui-button forminator-stripe-connect" type="button" data-nonce="' . wp_create_nonce( 'forminator_stripe_settings_modal' ) . '">' .
												'<span class="sui-loading-text">' . esc_html__( 'Connect', 'forminator' ) . '</span>' .
												'<i class="sui-icon-loader sui-loading" aria-hidden="true"></i>' .
												'</button>' .
												'</div>';

		wp_send_json_success( $data );
	}

	/**
	 * Handle PayPal settings
	 *
	 * @since 1.7.1
	 * @throws Forminator_Gateway_Exception When failed to connect.
	 */
	public function paypal_settings_modal() {
		// Validate nonce.
		forminator_validate_ajax( 'forminator_paypal_settings_modal', false, 'forminator-settings' );

		$data = array();

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified in forminator_validate_ajax.
		$post_data          = Forminator_Core::sanitize_array( $_POST );
		$is_connect_request = isset( $post_data['connect'] ) ? $post_data['connect'] : false;
		$template_vars      = array();

		try {
			$paypal = new Forminator_PayPal_Express();

			$sandbox_id       = isset( $post_data['sandbox_id'] ) ? $post_data['sandbox_id'] : $paypal->get_sandbox_id();
			$sandbox_secret   = isset( $post_data['sandbox_secret'] ) ? $post_data['sandbox_secret'] : $paypal->get_sandbox_secret();
			$live_id          = isset( $post_data['live_id'] ) ? $post_data['live_id'] : $paypal->get_live_id();
			$live_secret      = isset( $post_data['live_secret'] ) ? $post_data['live_secret'] : $paypal->get_live_secret();
			$default_currency = $paypal->get_default_currency();

			$template_vars['sandbox_id']     = $sandbox_id;
			$template_vars['sandbox_secret'] = $sandbox_secret;
			$template_vars['live_id']        = $live_id;
			$template_vars['live_secret']    = $live_secret;

			if ( ! empty( $is_connect_request ) ) {
				if ( empty( $sandbox_id ) ) {
					throw new Forminator_Gateway_Exception(
						'',
						Forminator_PayPal_Express::EMPTY_SANDBOX_ID_EXCEPTION
					);
				}
				if ( empty( $sandbox_secret ) ) {
					throw new Forminator_Gateway_Exception(
						'',
						Forminator_PayPal_Express::EMPTY_SANDBOX_SECRET_EXCEPTION
					);
				}

				$paypal->validate_id( 'sandbox', $sandbox_id, $sandbox_secret, Forminator_PayPal_Express::INVALID_SANDBOX_SECRET_EXCEPTION );

				if ( empty( $live_id ) ) {
					throw new Forminator_Gateway_Exception(
						'',
						Forminator_PayPal_Express::EMPTY_LIVE_ID_EXCEPTION
					);
				}
				if ( empty( $live_secret ) ) {
					throw new Forminator_Gateway_Exception(
						'',
						Forminator_PayPal_Express::EMPTY_LIVE_SECRET_EXCEPTION
					);
				}

				$paypal->validate_id( 'live', $live_id, $live_secret, Forminator_PayPal_Express::INVALID_LIVE_SECRET_EXCEPTION );

				Forminator_PayPal_Express::store_settings(
					array(
						'sandbox_id'     => $sandbox_id,
						'sandbox_secret' => $sandbox_secret,
						'live_id'        => $live_id,
						'live_secret'    => $live_secret,
						'currency'       => $default_currency,
					)
				);

				$data['notification'] = array(
					'type'     => 'success',
					'text'     => esc_html__( 'PayPal account connected successfully. You can now add the PayPal field to your forms and start collecting payments.', 'forminator' ),
					'duration' => '4000',
				);

			}
		} catch ( Forminator_Gateway_Exception $e ) {
			forminator_maybe_log( __METHOD__, $e->getMessage(), $e->getTrace() );
			$template_vars['error_message'] = $e->getMessage();

			if ( Forminator_PayPal_Express::EMPTY_SANDBOX_ID_EXCEPTION === $e->getCode() ) {
				$template_vars['sandbox_id_error'] = esc_html__( 'Please input sandbox client id', 'forminator' );
			}
			if ( Forminator_PayPal_Express::EMPTY_SANDBOX_SECRET_EXCEPTION === $e->getCode() ) {
				$template_vars['sandbox_secret_error'] = esc_html__( 'Please input sandbox secret key', 'forminator' );
			}
			if ( Forminator_PayPal_Express::EMPTY_LIVE_ID_EXCEPTION === $e->getCode() ) {
				$template_vars['live_id_error'] = esc_html__( 'Please input live client id', 'forminator' );
			}
			if ( Forminator_PayPal_Express::EMPTY_LIVE_SECRET_EXCEPTION === $e->getCode() ) {
				$template_vars['live_secret_error'] = esc_html__( 'Please input live secret key', 'forminator' );
			}
			if ( Forminator_PayPal_Express::INVALID_SANDBOX_SECRET_EXCEPTION === $e->getCode() ) {
				$template_vars['sandbox_secret_error'] = esc_html__( 'You\'ve entered an invalid sandbox secret key', 'forminator' );
			}
			if ( Forminator_PayPal_Express::INVALID_LIVE_SECRET_EXCEPTION === $e->getCode() ) {
				$template_vars['live_secret_error'] = esc_html__( 'You\'ve entered an invalid live secret key', 'forminator' );
			}
			if ( Forminator_PayPal_Express::INVALID_SANDBOX_ID_EXCEPTION === $e->getCode() ) {
				$template_vars['sandbox_id_error'] = esc_html__( 'You\'ve entered an invalid sandbox client id', 'forminator' );
			}
			if ( Forminator_PayPal_Express::INVALID_LIVE_ID_EXCEPTION === $e->getCode() ) {
				$template_vars['live_id_error'] = esc_html__( 'You\'ve entered an invalid live client id', 'forminator' );
			}
		}

		ob_start();
		/* @noinspection PhpIncludeInspection */
		include forminator_plugin_dir() . 'admin/views/settings/payments/paypal.php';
		$html = ob_get_clean();

		$data['html'] = $html;

		$data['buttons'] = array();

		$data['buttons']['connect']['markup'] = '<div class="sui-actions-right">' .
												'<button class="sui-button forminator-paypal-connect" type="button" data-nonce="' . wp_create_nonce( 'forminator_paypal_settings_modal' ) . '">' .
												'<span class="sui-loading-text">' . esc_html__( 'Connect', 'forminator' ) . '</span>' .
												'<i class="sui-icon-loader sui-loading" aria-hidden="true"></i>' .
												'</button>' .
												'</div>';

		wp_send_json_success( $data );
	}

	/**
	 * Dismiss notice. Use dismiss_admin_notice method instead
	 *
	 * @since 1.9
	 */
	public function dismiss_notice() {
		forminator_validate_ajax( 'forminator_dismiss_notification' );

		$notification_name = Forminator_Core::sanitize_text_field( 'prop' );
		$input_value       = Forminator_Core::sanitize_text_field( 'value' );
		$type              = Forminator_Core::sanitize_text_field( 'type' );
		$location          = Forminator_Core::sanitize_text_field( 'location' );

		$allowed_options = array(
			'forminator_skip_pro_notice',
			'forminator_cf7_notice_dismissed',
			'forminator_stripe_rak_notice_dismissed',
			'forminator_stripe_notice_dismissed',
			'forminator_rating_success',
			'forminator_rating_dismissed',
			'forminator_publish_rating_later',
			'forminator_publish_rating_later_dismiss',
			'forminator_days_rating_later_dismiss',
			'forminator_submission_rating_later',
			'forminator_submission_rating_later_dismiss',
			'forminator_hosting_banner_dismiss',
			'forminator_hosting_banner_later',
		);

		if ( ! in_array( $notification_name, $allowed_options, true )
				&& 0 !== strpos( $notification_name, 'forminator_addons_update_' )
				&& 0 !== strpos( $notification_name, 'forminator_dismiss_feature_' )
				) {
			wp_send_json_error( esc_html__( 'Invalid option name', 'forminator' ) );
		}

		/**
		 * Fires before dismissing a notice
		 *
		 * @param string $notification_name Notification name.
		 * @param string $type Input value.
		 * @param string $referrer Referrer URL.
		 */
		do_action( 'forminator_before_rating_dismiss_notice', $notification_name, $type, $location );

		if ( ! empty( $input_value ) ) {
			update_option( $notification_name, $input_value );
		} elseif ( 'forminator_addons_update_place_api_notice_dismissed' === $notification_name ) {
			// Delete the option so the notice will not be shown again.
			delete_option( 'forminator_geolocation_update_place_api_notice' );
		} else {
			update_option( $notification_name, true );
		}

		$version_upgraded = get_option( 'forminator_version_upgraded', false );

		if ( $version_upgraded ) {
			update_option( 'forminator_version_upgraded', false );
		}

		wp_send_json_success();
	}

	/**
	 * Toggle usage tracking
	 *
	 * @return void
	 */
	public function toggle_usage_tracking() {
		forminator_validate_ajax( 'forminator_usage_tracking' );

		$usage_value = filter_input( INPUT_POST, 'enabled', FILTER_VALIDATE_BOOLEAN );
		$source      = 'Upgrade Modal';

		update_option( 'forminator_usage_tracking', $usage_value );
		Forminator_Core::init_mixpanel( true );

		if ( $usage_value ) {
			/**
			 * Triggered after enabling Feature model Usage settings on New-feature popup
			 *
			 * @param string $source Source of the action.
			 */
			do_action( 'forminator_enable_usage_tracking', $source );
		} else {
			/**
			 * Triggered after disabling Feature model Usage settings on New-feature popup
			 *
			 * @param string $source Source of the action.
			 */
			do_action( 'forminator_disable_usage_tracking', $source );
		}

		wp_send_json_success( esc_html__( 'Settings saved successfully!', 'forminator' ) );
	}

	/**
	 * Dismiss admin notice.
	 */
	public function dismiss_admin_notice() {
		forminator_validate_ajax( 'forminator_dismiss_notice' );

		$slug      = Forminator_Core::sanitize_text_field( 'slug' );
		$user_id   = get_current_user_id();
		$dismissed = get_user_meta( $user_id, 'frmt_dismissed_messages', true );
		if ( ! $dismissed || ! is_array( $dismissed ) ) {
			$dismissed = array();
		}
		$dismissed[] = $slug;
		update_user_meta( $user_id, 'frmt_dismissed_messages', $dismissed );

		wp_send_json_success();
	}

	/**
	 * Dismiss notice
	 *
	 * @since 1.9
	 */
	public function later_notice() {
		forminator_validate_ajax( 'forminator_dismiss_notification' );

		$notification_name = Forminator_Core::sanitize_text_field( 'prop' );
		$form_id           = filter_input( INPUT_POST, 'form_id', FILTER_VALIDATE_INT );
		$type              = Forminator_Core::sanitize_text_field( 'type' );
		$location          = Forminator_Core::sanitize_text_field( 'location' );

		$allowed_keys = array(
			'forminator_publish_rating_later',
			'forminator_publish_rating_later_dismiss',
			'forminator_days_rating_later_dismiss',
			'forminator_submission_rating_later',
			'forminator_submission_rating_later_dismiss',
		);

		if ( ! in_array( $notification_name, $allowed_keys, true ) ) {
			wp_send_json_error( esc_html__( 'Invalid notification name', 'forminator' ) );
		}

		do_action( 'forminator_before_rating_dismiss_notice', $notification_name, $type, $location );

		update_post_meta( $form_id, $notification_name, true );

		wp_send_json_success();
	}

	/**
	 * Promote free plan - Remind me later
	 */
	public function promote_remind_later() {
		forminator_validate_ajax( 'forminator_promote_remind_later' );

		set_transient( 'forminator_free_plan_remind_later_' . get_current_user_id(), true, WEEK_IN_SECONDS );

		wp_send_json_success();
	}

	/**
	 * Save general payments settings
	 *
	 * @since 1.7
	 */
	public function save_payments() {
		forminator_validate_ajax( 'forminator_save_payments_settings', false, 'forminator-settings' );

		// stripe.
		$default_currency = Forminator_Core::sanitize_text_field( 'stripe-default-currency' );
		if ( $default_currency ) {

			try {
				Forminator_Gateway_Stripe::store_default_currency( $default_currency );
			} catch ( Forminator_Gateway_Exception $e ) {
				wp_send_json_error( $e->getMessage() );
			}
		}

		// paypal.
		$pp_default_currency = Forminator_Core::sanitize_text_field( 'paypal-default-currency' );
		if ( $pp_default_currency ) {
			$default_currency = $pp_default_currency;

			try {
				Forminator_PayPal_Express::store_default_currency( $default_currency );
			} catch ( Forminator_Gateway_Exception $e ) {
				wp_send_json_error( $e->getMessage() );
			}
		}

		wp_send_json_success();
	}

	/**
	 * Delete all poll submission
	 *
	 * @since 1.7.2
	 */
	public function delete_poll_submissions() {
		forminator_validate_ajax( 'forminatorPollEntries', false, 'forminator-entries' );

		$form_id = filter_input( INPUT_POST, 'id', FILTER_VALIDATE_INT );
		if ( $form_id ) {

			Forminator_Form_Entry_Model::delete_by_form( $form_id );

			$file = forminator_plugin_dir() . 'admin/views/common/entries/content-none.php';

			ob_start();
			/* @noinspection PhpIncludeInspection */
			include $file;
			$html = ob_get_clean();

			$data['html']         = $html;
			$data['notification'] = array(
				'type'     => 'success',
				'text'     => esc_html__( 'All the submissions deleted successfully.', 'forminator' ),
				'duration' => '4000',
			);
			wp_send_json_success( $data );
		} else {
			$data['notification'] = array(
				'type'     => 'error',
				'text'     => esc_html__( 'Submission delete failed.', 'forminator' ),
				'duration' => '4000',
			);
			wp_send_json_error( $data );
		}
	}

	/**
	 * Module search for all types
	 *
	 * @since 1.14.12
	 */
	public function module_search() {
		forminator_validate_ajax( 'forminator-nonce-search-module', false, 'forminator' );
		$keyword               = Forminator_Core::sanitize_text_field( 'search_keyword' );
		$page_number           = Forminator_Core::sanitize_text_field( 'paged', 1 );
		$module_search_results = Forminator_Admin_Module_Edit_Page::get_searched_modules( $keyword, $page_number );
		$modules               = $module_search_results['modules'] ?? array();
		$total_modules         = $module_search_results['total_modules'] ?? 0;

		ob_start();
		Forminator_Admin_Module_Edit_Page::show_modules(
			$modules,
			Forminator_Core::sanitize_text_field( 'module_slug' ),
			Forminator_Core::sanitize_text_field( 'preview_dialog' ),
			Forminator_Core::sanitize_text_field( 'preview_title' ),
			Forminator_Core::sanitize_text_field( 'export_dialog' ),
			Forminator_Core::sanitize_text_field( 'post_type' ),
			Forminator_Core::sanitize_text_field( 'soon' ),
			Forminator_Core::sanitize_text_field( 'sql_month_start_date' ),
			Forminator_Core::sanitize_text_field( 'wizard_page' ),
			$keyword
		);
		$result_html = ob_get_clean();

		ob_start();
		forminator_list_pagination( $total_modules, 'listing', true );
		$pagination_html = ob_get_clean();

		wp_send_json_success(
			array(
				'result_html'     => $result_html,
				'pagination_html' => $pagination_html,
				'total_modules'   => $total_modules,
			)
		);
	}

	/**
	 * Addons page action
	 *
	 * @return void
	 */
	public function addons_page_actions() {
		// Validate nonce.
		forminator_validate_ajax( 'forminator_popup_addons_actions', false, 'forminator-addons' );

		$action = Forminator_Core::sanitize_text_field( 'action' );
		if ( ! $action ) {
			wp_send_json_error(
				array( 'message' => esc_html__( 'Required field missing', 'forminator' ) )
			);
		}

		$action = str_replace( 'forminator_', '', $action );

		Forminator_Admin_Addons_Page::get_instance()->addons_action_ajax( $action );

		// When the addons_action_ajax function did not send a response assume error.
		wp_send_json_error(
			array( 'message' => esc_html__( 'Unexpected action, we could not handle it.', 'forminator' ) )
		);
	}

	/**
	 * Filter report data
	 *
	 * @return void
	 */
	public function filter_report_data() {
		// Validate nonce.
		forminator_validate_ajax( 'forminator_filter_report_data', false, 'forminator-reports' );

		$form_id    = filter_input( INPUT_POST, 'form_id', FILTER_VALIDATE_INT );
		$form_type  = Forminator_Core::sanitize_text_field( 'form_type' );
		$start_date = Forminator_Core::sanitize_text_field( 'start_date' );
		$end_date   = Forminator_Core::sanitize_text_field( 'end_date' );
		$range_time = Forminator_Core::sanitize_text_field( 'range_time' );
		$range_text = ! empty( $range_time ) ? $range_time : 'Custom';

		if ( ! empty( $form_id ) && ! empty( $start_date ) && ! empty( $end_date ) ) {

			$admin_report_instance = Forminator_Admin_Report_Page::get_instance();

			$reports     = $admin_report_instance->forminator_report_data( $form_id, $form_type, $start_date, $end_date, $range_text );
			$report_data = $admin_report_instance->forminator_report_array( $reports, $form_id );
			$chart_data  = $admin_report_instance->forminator_report_chart_data( $form_id, $start_date, $end_date );
			if ( isset( $chart_data['submissions'] ) ) {
				$chart_data['submissions'] = array_values( $chart_data['submissions'] );
			}

			if ( ! empty( $report_data ) ) {
				$response = array(
					'reports'    => $report_data,
					'chart_data' => $chart_data,
				);
				$response = apply_filters( 'forminator_report_data', $response, $form_id, $form_type, $start_date, $end_date );
				wp_send_json_success( $response );
			}
		}
		wp_send_json_error(
			array( 'message' => esc_html__( 'Required field missing', 'forminator' ) )
		);
	}

	/**
	 * Search users from the add recipients modal.
	 *
	 * @since 1.20.0
	 */
	public function search_users() {
		forminator_validate_ajax( 'forminator-fetch', 'nonce', 'forminator-reports' );

		$query = Forminator_Core::sanitize_text_field( 'query' );
		$query = "*$query*";

		$exclude = filter_input( INPUT_POST, 'exclude', FILTER_VALIDATE_INT, FILTER_REQUIRE_ARRAY );

		$users = forminator_get_users_by_query( $query, $exclude );

		wp_send_json_success( $users );
	}

	/**
	 * Get avatar for the recipients modal.
	 *
	 * @since 1.20.0
	 */
	public function get_avatar() {
		forminator_validate_ajax( 'forminator-fetch', 'nonce', 'forminator-reports' );

		if ( ! current_user_can( forminator_get_permission( 'forminator-reports' ) ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Current user cannot add recipient.', 'forminator' ),
				)
			);
		}

		$email = filter_input( INPUT_POST, 'email', FILTER_VALIDATE_EMAIL );

		if ( false === $email ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Invalid email.', 'forminator' ),
				)
			);
		}

		wp_send_json_success( get_avatar_url( $email ) );
	}

	/**
	 * Fetch reports by id
	 *
	 * @since 1.20.0
	 */
	public function fetch_report() {
		forminator_validate_ajax( 'forminator-fetch', 'nonce', 'forminator-reports' );

		$report_id    = Forminator_Core::sanitize_text_field( 'report_id' );
		$report_value = array();

		if ( empty( $report_id ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Something went wrong.', 'forminator' ),
				)
			);
		}

		$report_data = Forminator_Form_Reports_Model::get_instance()->fetch_report_by_id( $report_id );
		if ( ! empty( $report_data ) ) {
			$report_value = ! empty( $report_data->report_value ) ?
				Forminator_Core::sanitize_html_array( maybe_unserialize( $report_data->report_value ) )
				: array();
			if ( isset( $report_data->status ) ) {
				$report_value['report_status'] = esc_html( $report_data->status );
			}
			$report_value['report_id'] = intval( $report_data->report_id );
		}

		wp_send_json_success( $report_value );
	}

	/**
	 * Save Report.
	 *
	 * @since 1.20.0
	 */
	public function save_report() {
		forminator_validate_ajax( 'forminator-save', 'nonce', 'forminator-reports' );

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified in forminator_validate_ajax.
		if ( empty( $_POST['reports'] ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Something went wrong.', 'forminator' ),
				)
			);
		}

		$report_id    = Forminator_Core::sanitize_text_field( 'report_id' );
		$status       = Forminator_Core::sanitize_text_field( 'status' );
		$reports_data = Forminator_Core::sanitize_array( $_POST['reports'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput

		$reports_data['report_status'] = $status;

		if ( 0 !== (int) $report_id ) {
			$result = Forminator_Form_Reports_Model::get_instance()->report_update( $report_id, $reports_data, $status );
		} else {
			$result                    = Forminator_Form_Reports_Model::get_instance()->report_save( $reports_data, $status );
			$reports_data['report_id'] = $result;
		}

		if ( is_wp_error( $result ) ) {
			wp_send_json_error();
		}

		/**
		 * Fires after report save
		 *
		 * @since 1.27.0
		 *
		 * @param array $reports_data Report data.
		 */

		do_action( 'forminator_after_notification_update', $reports_data );

		wp_send_json_success( $result );
	}

	/**
	 * Update report status
	 */
	public function update_report_status() {
		forminator_validate_ajax( 'forminator-save', 'nonce', 'forminator-reports' );

		$report_id = Forminator_Core::sanitize_text_field( 'report_id' );
		$status    = Forminator_Core::sanitize_text_field( 'status' );

		if ( empty( $status ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Something went wrong.', 'forminator' ),
				)
			);
		}

		$result = Forminator_Form_Reports_Model::get_instance()->report_update_status( $report_id, $status );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error();
		}

		wp_send_json_success( $result );
	}

	/**
	 * Save permission.
	 */
	public function save_permissions() {
		forminator_validate_ajax( 'forminator_permission_nonce' );

		$old_permissions = get_option( 'forminator_permissions', array() );
		$post_data       = Forminator_Core::sanitize_array( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified in forminator_validate_ajax.
		$permissions     = json_decode( wp_unslash( $post_data['permissions'] ), true );
		$caps            = forminator_get_capabilities();

		// Perform changes during save mode.
		if ( 'new' === $post_data['mode'] ) {

			foreach ( $permissions as $key => $permission ) {

				/**
				 * For specific users.
				 * - Add caps to the users
				 * - Check each permission for get_avatar then retrieve it.
				 */
				if ( 'specific' === $permission['permission_type'] ) {
					foreach ( $permission['specific_user'] as $user_index => $user_id ) {
						$user = get_user_by( 'ID', $user_id );

						if ( false !== $user ) {

							// Add caps to the user.
							forminator_apply_capabilities( $user, $permission );

							// Set user info.
							$permissions[ $key ]['user_info'][ $user_id ]['name']  = $user->display_name;
							$permissions[ $key ]['user_info'][ $user_id ]['email'] = $user->user_email;

							// We only need avatar for first user.
							if ( 0 === $user_index ) {
								$permissions[ $key ]['avatar'] = get_avatar_url( $user->user_email, array( 'size' => 30 ) );
							}
						}
					}

					/**
					 * For roles.
					 * - Add caps to users under these roles.
					 */
				} else {
					$role = get_role( $permission['user_role'] );

					// Add caps to the role.
					forminator_apply_capabilities( $role, $permission );

					if ( empty( $permission['exclude_users'] ) ) {
						continue;
					}

					// Set user info for excluded users.
					foreach ( $permission['exclude_users'] as $user_id ) {
						$user = get_user_by( 'ID', $user_id );

						if ( false !== $user ) {
							$permissions[ $key ]['user_info'][ $user_id ]['name']  = $user->display_name;
							$permissions[ $key ]['user_info'][ $user_id ]['email'] = $user->user_email;
						}
					}
				}
			}

			/**
			 * EDIT mode.
			 *
			 * If specific users, get the missing users from new permission state then revoke their caps.
			 * Add new caps if new ones are added.
			 *
			 * If role, add or remove caps as per new state.
			 */
		} else {
			$pid = $post_data['pid'];
			// phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict -- Possible for 0 or "0"
			$old_permission = $old_permissions[ array_search( $pid, array_column( $old_permissions, 'pid' ) ) ];

			if ( 'edit' === $post_data['mode'] ) {

				foreach ( $permissions as $key => $permission ) {
					if ( $old_permission['pid'] === $permission['pid'] ) {

						// If specific user.
						if ( 'specific' === $permission['permission_type'] ) {

							// Remove caps from deleted users.
							$deleted_users = array_diff( $old_permission['specific_user'], $permission['specific_user'] );
							foreach ( $deleted_users as $user_id ) {
								$graduate = get_user_by( 'ID', $user_id );

								if ( false !== $graduate ) {
									foreach ( $caps as $cap ) {
										$graduate->remove_cap( $cap );
									}
								}
							}

							// Add/remove caps to the current users.
							foreach ( $permission['specific_user'] as $user_index => $user_id ) {
								$user = get_user_by( 'ID', $user_id );
								forminator_apply_capabilities( $user, $permission );

								// Set user info.
								$permissions[ $key ]['user_info'][ $user_id ]['name']  = $user->display_name;
								$permissions[ $key ]['user_info'][ $user_id ]['email'] = $user->user_email;

								// We only need avatar for first user.
								if ( 0 === $user_index ) {
									$permissions[ $key ]['avatar'] = get_avatar_url( $user->user_email, array( 'size' => 30 ) );
								}
							}

							// If role.
						} else {
							$role = get_role( $permission['user_role'] );

							// Add/remove caps to the role.
							forminator_apply_capabilities( $role, $permission );

							if ( empty( $permission['exclude_users'] ) ) {
								continue;
							}

							// Set user info for excluded users.
							foreach ( $permission['exclude_users'] as $user_id ) {
								$user = get_user_by( 'ID', $user_id );

								if ( false !== $user ) {
									$permissions[ $key ]['user_info'][ $user_id ]['name']  = $user->display_name;
									$permissions[ $key ]['user_info'][ $user_id ]['email'] = $user->user_email;
								}
							}
						}
					}
				}

				/**
				 * Delete mode.
				 *
				 * If specific users, get the missing users from new permission state then revoke their caps.
				 * Add new caps if new ones are added.
				 *
				 * If role, add or remove caps as per new state.
				 */
			} elseif ( 'delete' === $post_data['mode'] ) {

				// If specific user.
				if ( 'specific' === $old_permission['permission_type'] ) {

					// Remove caps from users.
					foreach ( $old_permission['specific_user'] as $user_id ) {
						$graduate = get_user_by( 'ID', $user_id );

						if ( false !== $graduate ) {
							foreach ( $caps as $cap ) {
								$graduate->remove_cap( $cap );
							}
						}
					}

					// If role.
				} else {
					$role = get_role( $old_permission['user_role'] );

					// Remove caps from the role.
					if ( ! is_null( $role ) ) {
						foreach ( $caps as $cap ) {
							$role->remove_cap( $cap );
						}
					}
				}
			}
		}

		if ( update_option( 'forminator_permissions', $permissions ) ) {
			wp_send_json_success( $permissions );
		} else {
			wp_send_json_error();
		}
	}

	/**
	 * Share Feedback to mixpanel.
	 */
	public function forminator_share_feedback() {
		forminator_validate_ajax( 'forminator_share_feedback' );

		$rating             = filter_input( INPUT_POST, 'rating', FILTER_VALIDATE_INT );
		$additional_details = Forminator_Core::sanitize_text_field( 'additional_details' );
		if ( $rating < 1 || $rating > 5 ) {
			wp_send_json_error( esc_html__( 'Invalid rating value', 'forminator' ) );
		}
		Forminator_Core::init_mixpanel( true );
		do_action( 'forminator_share_feedback_to_mixpanel', $rating, $additional_details );
		wp_send_json_success();
	}

	/**
	 * Submit deactivation survey
	 *
	 * @return void
	 */
	public function submit_deactivation_survey() {
		forminator_validate_ajax( 'forminator_deactivation_survey' );

		$reason               = Forminator_Core::sanitize_text_field( 'reason' );
		$message              = Forminator_Core::sanitize_text_field( 'message' );
		$action               = Forminator_Core::sanitize_text_field( 'model_action' );
		$requested_assistance = Forminator_Core::sanitize_text_field( 'requested_assistance' );

		// Do not send Skip actions if tracking is disabled.
		if ( 'Submit' !== $action && ! Forminator_Core::is_tracking_active() ) {
			wp_send_json_error();
		}

		Forminator_Core::init_mixpanel( true );
		do_action( 'forminator_share_deactivation_survey_to_mixpanel', $reason, $action, $requested_assistance, $message );
		wp_send_json_success();
	}
}
