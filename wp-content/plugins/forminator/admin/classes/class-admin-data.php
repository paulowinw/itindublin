<?php
/**
 * Forminator Admin Data
 *
 * @package Forminator
 */

/**
 * Class Forminator_Admin_Data
 *
 * @since 1.0
 */
class Forminator_Admin_Data {

	/**
	 * Forminator Instance
	 *
	 * @var Forminator|null
	 */
	public $core = null;

	/**
	 * Current Nonce
	 *
	 * @since 1.2
	 * @var string
	 */
	private $_nonce = '';

	/**
	 * Forminator_Admin_Data constructor.
	 *
	 * @since 1.0
	 */
	public function __construct() {
		$this->core = Forminator::get_instance();
	}

	/**
	 * Combine Data and pass to JS
	 *
	 * @return array
	 * @since 1.0
	 */
	public function get_options_data() {
		$data              = $this->admin_js_defaults();
		$data              = apply_filters( 'forminator_data', $data );
		$data['fields']    = forminator_get_fields_sorted( 'position', SORT_ASC );
		$data['fieldsPro'] = forminator_get_pro_fields();

		$data['default_required_messages'] = Forminator_Field::$default_required_messages;

		return $data;
	}

	/**
	 * Generate nonce
	 *
	 * @since 1.2
	 */
	public function generate_nonce() {
		$this->_nonce = wp_create_nonce( 'forminator_load_google_fonts' );
	}

	/**
	 * Get current generated nonce
	 *
	 * @return string
	 * @since 1.2
	 */
	public function get_nonce() {
		return $this->_nonce;
	}

	/**
	 * Return published pages
	 *
	 * @return array
	 */
	public static function get_pages() {
		$cached_pages = wp_cache_get( 'forminator_cached_pages', 'forminator-cache' );
		if ( false !== $cached_pages ) {
			return $cached_pages;
		}
		global $wpdb;
		$sql   = "SELECT ID, post_title FROM {$wpdb->posts} WHERE post_type = 'page' AND post_status = 'publish' ORDER BY post_title ASC";
		$pages = $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery
		// Cache the result.
		wp_cache_set( 'forminator_cached_pages', $pages, 'forminator-cache' );

		return $pages;
	}

	/**
	 * Default Admin properties
	 *
	 * @return array
	 * @since 1.0
	 */
	public function admin_js_defaults() {
		// Generate addon nonce.
		Forminator_Integration_Admin_Ajax::get_instance()->generate_nonce();
		$id   = filter_input( INPUT_GET, 'id', FILTER_VALIDATE_INT );
		$user = wp_get_current_user();

		$dashboard      = class_exists( 'WPMUDEV_Dashboard' );
		$extension_pack = class_exists( 'Forminator_Extension_Pack' );

		return array(
			'ajaxUrl'                        => forminator_ajax_url(),
			'adminUrl'                       => admin_url(),
			'siteUrl'                        => site_url(),
			'akismetEnabled'                 => is_plugin_active( 'akismet/akismet.php' ),
			'application'                    => '',
			'is_touch'                       => wp_is_mobile(),
			'dashboardUrl'                   => menu_page_url( 'forminator', false ),
			'formEditUrl'                    => menu_page_url( 'forminator-cform-wizard', false ),
			'noWrongEditUrl'                 => menu_page_url( 'forminator-nowrong-wizard', false ),
			'knowledgeEditUrl'               => menu_page_url( 'forminator-knowledge-wizard', false ),
			'pollEditUrl'                    => menu_page_url( 'forminator-poll-wizard', false ),
			'settingsUrl'                    => menu_page_url( 'forminator-settings', false ),
			'integrationsUrl'                => menu_page_url( 'forminator-integrations', false ),
			'addonsUrl'                      => menu_page_url( 'forminator-addons', false ),
			'hasCaptcha'                     => forminator_has_captcha_settings(),
			'hasV2Captcha'                   => forminator_has_v2_captcha_settings(),
			'hasV2InvisibleCaptcha'          => forminator_has_v2_invisible_captcha_settings(),
			'hasV3Captcha'                   => forminator_has_v3_captcha_settings(),
			'hasHCaptcha'                    => forminator_has_hcaptcha_settings(),
			'hasTurnstile'                   => forminator_has_turnstile_settings(),
			'loadCaptcha'                    => wp_create_nonce( 'forminator_load_captcha_settings' ),
			'hasStripe'                      => forminator_has_stripe_connected(),
			'formNonce'                      => $this->get_nonce(),
			'resetTrackingDataNonce'         => wp_create_nonce( 'forminator_reset_tracking_data' ),
			'createNonce'                    => wp_create_nonce( 'forminator_create_module' ),
			'previewNonce'                   => wp_create_nonce( 'forminator_load_module' ),
			'updateLivePreviewNonce'         => wp_create_nonce( 'forminator_update_live_preview' ),
			'searchNonce'                    => wp_create_nonce( 'forminator_search_emails' ),
			'gFontNonce'                     => wp_create_nonce( 'forminator_load_google_fonts' ),
			'dismissNonce'                   => wp_create_nonce( 'forminator_dismiss_notification' ),
			'dismissNoticeNonce'             => wp_create_nonce( 'forminator_dismiss_notice' ),
			'formProcessNonce'               => wp_create_nonce( 'forminator_form_request' ),
			'formExportNonce'                => wp_create_nonce( 'forminator_popup_export_form' ),
			'pollProcessNonce'               => wp_create_nonce( 'forminator_poll_request' ),
			'pollExportNonce'                => wp_create_nonce( 'forminator_popup_export_poll' ),
			'quizProcessNonce'               => wp_create_nonce( 'forminator_quiz_request' ),
			'quizExportNonce'                => wp_create_nonce( 'forminator_popup_export_quiz' ),
			'cloneNonce'                     => wp_create_nonce( 'forminator-nonce-clone-' . $id ),
			'load_cloud_templates'           => wp_create_nonce( 'forminator_load_cloud_templates' ),
			'save_cloud_templates'           => wp_create_nonce( 'forminator_save_cloud_templates' ),
			'create_form_nonce'              => wp_create_nonce( 'forminator_create_form_from_template' ),
			'disconnect_hub_nonce'           => wp_create_nonce( 'forminator_disconnect_from_hub' ),
			'templates_per_page'             => apply_filters( 'forminator_templates_per_page', 100 ),
			'addons_enabled'                 => Forminator::is_addons_feature_enabled(),
			'pluginUrl'                      => forminator_plugin_url(),
			'imagesUrl'                      => forminator_plugin_url() . 'assets/images',
			'addonNonce'                     => Forminator_Integration_Admin_Ajax::get_instance()->get_nonce(),
			'countries'                      => forminator_get_countries_list(),
			'userList'                       => forminator_list_users(),
			'variables'                      => forminator_get_vars(),
			'variablesForHiddenField'        => forminator_get_vars( true ),
			'payment_variables'              => forminator_get_payment_vars(),
			'stripe_subscription_variables'  => forminator_get_stripe_subscription_vars(),
			'maxUpload'                      => forminator_get_max_upload(),
			'captchaLangs'                   => forminator_get_captcha_languages(),
			'erasure'                        => get_option( 'forminator_enable_erasure_request_erase_form_submissions', false ),
			'retain_number'                  => get_option( 'forminator_retain_submissions_interval_number', 0 ),
			'retain_unit'                    => get_option( 'forminator_retain_submissions_interval_unit', 'days' ),
			'poll_ip_retain_number'          => get_option( 'forminator_retain_votes_interval_number', 0 ),
			'poll_ip_retain_unit'            => get_option( 'forminator_retain_votes_interval_unit', 'days' ),
			'submissions_ip_retain_number'   => get_option( 'forminator_retain_poll_submissions_interval_number', 0 ),
			'submissions_ip_retain_unit'     => get_option( 'forminator_retain_poll_submissions_interval_unit', 'days' ),
			'submissions_quiz_retain_number' => get_option( 'forminator_retain_quiz_submissions_interval_number', 0 ),
			'submissions_quiz_retain_unit'   => get_option( 'forminator_retain_quiz_submissions_interval_unit', 'days' ),
			'skip_pro_notice'                => get_option( 'forminator_skip_pro_notice', false ),
			'fileExts'                       => forminator_get_ext_types(),
			'version'                        => FORMINATOR_VERSION,
			'showDocLink'                    => forminator_is_show_documentation_link(),
			'showBranding'                   => forminator_is_show_branding(),
			'currencies'                     => forminator_currency_list(),
			'ppCurrencies'                   => forminator_pp_currency_list(),
			'postTypeList'                   => forminator_post_type_list(),
			'postCategories'                 => forminator_post_categories(),
			'isPro'                          => FORMINATOR_PRO,
			'isHubConnected'                 => false,
			'dashboardPlugin'                => $dashboard,
			'isWPMUDEVloggedIn'              => $dashboard && WPMUDEV_Dashboard::$api->get_key(),
			'expiredMembership'              => $dashboard && forminator_get_wpmudev_membership() === 'expired',
			'extensionPack'                  => $extension_pack,
			'userRoles'                      => forminator_get_accessible_user_roles(),
			'pages'                          => self::get_pages(),
			'hasPayPal'                      => forminator_has_paypal_settings(),
			'pollAnswerColors'               => forminator_get_poll_chart_colors(),
			'isMainSite'                     => forminator_is_main_site(),
			'isSubdomainNetwork'             => forminator_is_subdomain_network(),
			'showFieldSettings'              => get_option( 'forminator_editor_settings', 'true' ),
			'hasStripePro'                   => defined( 'FORMINATOR_STRIPE_ADDON' ) && class_exists( 'Forminator_Stripe_Addon' ),
			'stripeForms'                    => $this->get_forms_by_field_type( 'stripe' ),
			'paypalForms'                    => $this->get_forms_by_field_type( 'paypal' ),
			'form_modules'                   => $this->get_modules( 'get_forms' ),
			'quiz_modules'                   => $this->get_modules( 'get_quizzes' ),
			'poll_modules'                   => $this->get_modules( 'get_polls' ),
			'pdfAddonActive'                 => class_exists( 'Forminator_PDF_Addon' ),
			'wpmudevMembership'              => forminator_get_wpmudev_membership(), // 'free'
			'pdfExtensionsEnabled'           => $this->pdf_extensions_enabled(),
			'isPDFAddonCompatible'           => $this->is_pdf_addon_compatible(),
			'userPermissions'                => $user->get_role_caps(),
			'manage_forminator_templates'    => forminator_is_user_allowed( 'forminator-templates' ),
			'cloudDisabled'                  => forminator_cloud_templates_disabled(),
			'globalTracking'                 => forminator_global_tracking(),
			'hasSavedChanges'                => is_int( $id ) && Forminator_Base_Form_Model::get_temp_settings( $id ),
			'saveDelay'                      => apply_filters( 'forminator_save_delay', 1500 ),
			'autoSave'                       => get_option( 'forminator_auto_saving', true ),
			'addonsDisabled'                 => forminator_addons_disabled(),
			'abandonmentDisabled'            => forminator_form_abandonment_disabled(),
			'formColorSettings'              => Forminator_Custom_Form_Admin::get_default_color_settings(),
			'install_addon'                  => filter_input( INPUT_GET, 'forminator_install_addon', FILTER_VALIDATE_INT ),
			'open_addon'                     => filter_input( INPUT_GET, 'forminator_open_addon', FILTER_VALIDATE_INT ),
			'EXTENSION_PACK_PID'             => Forminator_Admin_Addons_Page::EXTENSION_PACK_PID,
			'isUserRegistrationEnabled'      => forminator_is_user_registration_enabled(),
			'isSiteRegistrationEnabled'      => forminator_is_site_registration_enabled(),
			'networkAdminUrl'                => network_admin_url(),
			'isMultisite'                    => is_multisite(),
			'shareFeedbackNonce'             => wp_create_nonce( 'forminator_share_feedback' ),
			'feedbackDisabled'               => forminator_feedback_disabled(),
			'isTrackingActive'               => Forminator_Core::is_tracking_active(),
		);
	}

	/**
	 * Get form by field
	 *
	 * @param string $type Field type.
	 *
	 * @return array
	 */
	public function get_forms_by_field_type( $type ) {
		$field_forms = array();
		$forms       = Forminator_Form_Model::model()->get_models( 99 );
		if ( ! empty( $forms ) ) {
			foreach ( $forms as $form ) {
				if ( ! empty( $form->fields ) ) {
					foreach ( $form->fields as $f => $field ) {
						$field_array = $field->to_formatted_array();
						$field_type  = isset( $field_array['type'] ) ? $field_array['type'] : '';
						if ( $type === $field_type || ( 'stripe' === $type && 'stripe-ocs' === $field_type ) ) {
							$field_forms[ $form->id ] = isset( $form->settings['formName'] ) ? $form->settings['formName'] : '';
						}
					}
				}
			}
		}

		return $field_forms;
	}

	/**
	 * Print forms select
	 *
	 * @param string $method Method time.
	 *
	 * @return array
	 * @since 1.0
	 */
	public function get_modules( $method ) {
		$modules      = array();
		$modules_data = Forminator_API::$method( null, 1, 999, 'publish' );
		if ( ! empty( $modules_data ) ) {
			foreach ( $modules_data as $m => $module ) {
				$module = (array) $module;
				$title  = forminator_get_form_name( $module['id'] );
				if ( mb_strlen( $title ) > 25 ) {
					$title = mb_substr( $title, 0, 25 ) . '...';
				}
				$modules[ $m ]['id']   = $module['id'];
				$modules[ $m ]['name'] = $title;
			}
		}

		return $modules;
	}

	/**
	 * Check MPDF extensions.
	 *
	 * @since 1.25
	 *
	 * @return bool
	 */
	public function pdf_extensions_enabled() {
		if ( function_exists( 'forminator_pdf_extensions_enabled' ) ) {
			return forminator_pdf_extensions_enabled();
		}

		return false;
	}

	/**
	 * Check if PDF addon is compatible.
	 *
	 * @since 1.51
	 *
	 * @return bool
	 */
	public function is_pdf_addon_compatible() {
		if ( class_exists( 'Forminator_PDF_Addon' ) ) {
			$pdf_addon = Forminator_PDF_Addon::get_instance();
			if ( method_exists( $pdf_addon, 'is_supported_version' ) ) {
				return $pdf_addon->is_supported_version();
			}
		}

		return false;
	}
}
