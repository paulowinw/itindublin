<?php
/**
 * Core helper functions.
 *
 * @package Forminator
 */

/**
 * Return needed cap for admin pages
 *
 * @since 1.0
 * @return string
 */
function forminator_get_admin_cap() {
	$cap = 'manage_options';

	if ( is_multisite() && is_network_admin() ) {
		$cap = 'manage_network';
	}

	if ( current_user_can( 'manage_forminator' ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown -- false positive
		$cap = 'manage_forminator';
	}

	return apply_filters( 'forminator_admin_cap', $cap );
}

/**
 * Checks if user is allowed to perform the ajax actions
 *
 * @since 1.0
 * @since 1.28 Added $slug param.
 *
 * @param string $slug The page slug that will be used for identifying permission.
 *
 * @return bool
 */
function forminator_is_user_allowed( $slug = '' ) {
	return current_user_can(
		forminator_get_permission( $slug )
	);
}

/**
 * Check if array value exists
 *
 * @since 1.0
 *
 * @param array  $array_values Array for check value exists.
 * @param string $key - the string key.
 *
 * @return bool
 */
function forminator_array_value_exists( $array_values, $key ) {
	return isset( $array_values[ $key ] ) && ( ! empty( $array_values[ $key ] ) || in_array( $array_values[ $key ], array( 0, '0' ), true ) );
}

/**
 * Check for theme and return key.
 *
 * @since 1.36.0
 *
 * @param string $key - default key value.
 * @param string $theme - current theme of the form.
 * @since 1.0
 * @return string
 */
function forminator_get_prefixed_key_name( $key, $theme ) {
	// If the theme is 'basic', modify the key to have the prefix.
	if ( 'basic' === $theme ) {
		$key = 'basic-' . $key;
	}

	return $key;
}

/**
 * Check if array value exists
 *
 * @since 1.14.7
 *
 * @param array  $properties All properties.
 * @param string $key Key.
 */
function forminator_echo_font_weight( $properties, $key ) {
	$styles = array( 'italic', 'oblique' );
	$weight = str_replace( 'None', 'inherit', $properties[ $key ] );
	$weight = str_replace( 'regular', 'normal', $weight );
	// if 400italic.
	$style = str_replace( (int) $weight, '', $weight );
	if ( in_array( $style, $styles, true ) ) {
		// if just italic.
		$weight = intval( $weight ) ? intval( $weight ) : 'normal';
		echo 'font-weight: ' . esc_attr( $weight ) . ';';
		echo 'font-style: ' . esc_attr( $style ) . ';';
	} else {
		echo 'font-weight: ' . esc_attr( $weight ) . ';';
	}
}

/**
 * Convert object to array
 *
 * @since 1.0
 *
 * @param object $object_values Object for convert to array.
 *
 * @return array
 */
function forminator_object_to_array( $object_values ) {
	$array = array();

	if ( empty( $object_values ) ) {
		return $array;
	}

	foreach ( $object_values as $key => $value ) {
		$array[ $key ] = $value;
	}

	return $array;
}

/**
 * Return AJAX url
 *
 * @since 1.0
 * @return mixed
 */
function forminator_ajax_url() {
	return admin_url( 'admin-ajax.php', is_ssl() ? 'https' : 'http' );
}

/**
 * Checks if the AJAX call is valid
 * For logged-in usage only.
 *
 * @since 1.0
 * @since 1.17 Added $query_arg
 * @since 1.28 Added $page_slug For determining capability to check.
 *
 * @param string $action Ajax action.
 * @param mixed  $query_arg Query arguments.
 * @param string $page_slug Page slug.
 */
function forminator_validate_ajax( $action, $query_arg = false, $page_slug = '' ) {
	if ( ! check_ajax_referer( $action, $query_arg, false ) || ! forminator_is_user_allowed( $page_slug ) ) {
		wp_send_json_error( esc_html__( 'Invalid request, you are not allowed to do that action.', 'forminator' ) );
	}
}

/**
 * Checks if the AJAX call is valid
 *
 * @param string      $action Action name.
 * @param string|bool $query_arg Query arg.
 *
 * @return void
 */
function forminator_validate_nonce_ajax( $action, $query_arg = false ) {
	if ( ! check_ajax_referer( $action, $query_arg, false ) ) {
		wp_send_json_error( esc_html__( 'Invalid request, you are not allowed to do that action.', 'forminator' ) );
	}
}

/**
 * Enqueue admin fonts
 *
 * @since 1.0
 */
function forminator_admin_enqueue_fonts() {
	$version = '1.0';
	wp_enqueue_style(
		'forminator-roboto',
		'https://fonts.bunny.net/css?family=Roboto+Condensed:300,300i,400,400i,700,700i|Roboto:300,300i,400,400i,500,500i,700,700i',
		array(),
		$version
	); // cache as long as you can.
	wp_enqueue_style(
		'forminator-opensans',
		'https://fonts.bunny.net/css?family=Open+Sans:400,400i,700,700i',
		array(),
		$version
	); // cache as long as you can.
	wp_enqueue_style(
		'forminator-source',
		'https://fonts.bunny.net/css?family=Source+Code+Pro',
		array(),
		$version
	); // cache as long as you can.
}

/**
 * Enqueue admin styles
 *
 * @since 1.0
 * @since 1.1 Remove forminator-admin css after migrate to shared-ui
 */
function forminator_admin_enqueue_styles() {
	wp_enqueue_style( 'shared-ui', forminator_plugin_url() . 'build/css/shared-ui.min.css', array(), FORMINATOR_VERSION, false );
}

/**
 * Enqueue jQuery UI scripts on admin
 *
 * @since 1.13 Loaded locally
 * @since 1.0
 */
function forminator_admin_jquery_ui() {
	wp_enqueue_script( 'jquery-ui-core' );
}

/**
 * Load admin scripts
 *
 * @since 1.0
 */
function forminator_admin_jquery_ui_init() {
	wp_enqueue_script( 'jquery-ui-core' );
	wp_enqueue_script( 'jquery-ui-widget' );
	wp_enqueue_script( 'jquery-ui-mouse' );
	wp_enqueue_script( 'jquery-ui-tabs' );
	wp_enqueue_script( 'jquery-ui-sortable' );
	wp_enqueue_script( 'jquery-ui-draggable' );
	wp_enqueue_script( 'jquery-ui-droppable' );
	wp_enqueue_script( 'jquery-ui-datepicker' );
	wp_enqueue_script( 'jquery-ui-resize' );
	wp_enqueue_style( 'wp-color-picker' );
}

/**
 * Enqueue SUI scripts on admin
 *
 * @since 1.1
 */
function forminator_sui_scripts() {

	$chartjs_version = '2.7.2';

	$sanitize_version = str_replace( '.', '-', FORMINATOR_SUI_VERSION );
	$sui_body_class   = "sui-$sanitize_version";

	wp_enqueue_script(
		'shared-ui',
		forminator_plugin_url() . 'build/js/shared-ui.min.js',
		array( 'jquery', 'clipboard' ),
		$sui_body_class,
		true
	);
}

/**
 * Remove select2 script of PMPro on admin
 *
 * @since 1.36
 */
function forminator_remove_pmpro_scripts() {
	if ( class_exists( 'PMPro_Field' ) ) {
		$screen = get_current_screen();
		if ( ! str_contains( $screen->id, 'pmpro' ) ) {
			wp_deregister_script( 'pmpro_admin' );
			wp_dequeue_script( 'pmpro_admin' );
			wp_deregister_script( 'select2' );
			wp_dequeue_script( 'select2' );
			wp_deregister_style( 'select2' );
			wp_dequeue_style( 'select2' );
		}
	}
}

/**
 * Enqueue common admin scripts
 *
 * @since 1.0
 * @param bool $is_new_page Load scripts for new page classes.
 */
function forminator_common_admin_enqueue_scripts( $is_new_page = false ) {
	// Load jquery ui.
	forminator_admin_jquery_ui_init();

	// Load shared-ui scripts.
	forminator_sui_scripts();

	// Load admin fonts.
	forminator_admin_enqueue_fonts();

	// Load admin styles.
	forminator_admin_enqueue_styles();

	// Remove PMPro plugin scripts.
	forminator_remove_pmpro_scripts();

	// LOAD: Forminator UI – Select2.
	wp_enqueue_script(
		'select2-forminator',
		forminator_plugin_url() . 'assets/forminator-ui/js/select2.full.min.js',
		array( 'jquery' ),
		FORMINATOR_VERSION,
		false
	);
	wp_enqueue_script( 'ace-editor', forminator_plugin_url() . 'assets/js/library/ace/ace.js', array( 'jquery' ), FORMINATOR_VERSION, false );
	wp_enqueue_script( 'google-charts', 'https://www.gstatic.com/charts/loader.js', array( 'jquery' ), FORMINATOR_VERSION, false );

	if ( function_exists( 'wp_enqueue_editor' ) ) {
		wp_enqueue_editor();
	}
	if ( function_exists( 'wp_enqueue_media' ) ) {
		wp_enqueue_media();
	}

	wp_enqueue_script( 'forminator-admin-layout', forminator_plugin_url() . 'requirejs/admin/layout.js', array( 'jquery' ), FORMINATOR_VERSION, false );

	$forminator_data = new Forminator_Admin_Data();
	$forminator_l10n = new Forminator_Admin_L10n();

	$data = $forminator_data->get_options_data();
	$l10n = $forminator_l10n->get_l10n_strings();
	wp_localize_script( 'forminator-admin', 'forminatorData', $data );
	wp_localize_script( 'forminator-admin', 'forminatorl10n', $l10n );
	wp_enqueue_script( 'forminator-admin' );

	if ( $is_new_page ) {
		forminator_enqueue_color_picker_alpha();
		// Load front scripts for preview_form.
		forminator_print_front_styles();
		forminator_print_front_scripts();
	}

	if ( 'forminator-settings' === filter_input( INPUT_GET, 'page' ) ) {
		forminator_enqueue_color_picker_alpha();
	}
}

/**
 * Enqueue color picker alpha scripts
 *
 * @since 1.14
 */
function forminator_enqueue_color_picker_alpha() {
	wp_enqueue_script( 'wp-color-picker-alpha', forminator_plugin_url() . 'assets/js/library/wp-color-picker-alpha.min.js', array( 'wp-color-picker' ), FORMINATOR_VERSION, true );

	wp_localize_script(
		'wp-color-picker-alpha',
		'wpColorPickerL10n',
		array(
			'clear'            => esc_html__( 'Clear', 'forminator' ),
			'clearAriaLabel'   => esc_html__( 'Clear color', 'forminator' ),
			'defaultString'    => esc_html__( 'Default', 'forminator' ),
			'defaultAriaLabel' => esc_html__( 'Select default color', 'forminator' ),
			'pick'             => esc_html__( 'Select Color', 'forminator' ),
			'defaultLabel'     => esc_html__( 'Color value', 'forminator' ),
		)
	);
}

/**
 * Enqueue front-end styles
 *
 * Only use core here, if the style dynamically loaded, then load on model
 *
 * @since 1.0
 */
function forminator_print_front_styles() {
	// Load old styles.
	// Remove on v1.12.0 quizzes migrate to Forminator UI.
	Forminator_Assets_Enqueue::fui_enqueue_style( 'forminator-ui-icons', forminator_plugin_url() . 'assets/forminator-ui/css/forminator-icons.min.css', array(), FORMINATOR_VERSION );
	Forminator_Assets_Enqueue::fui_enqueue_style( 'forminator-ui-utilities', forminator_plugin_url() . 'assets/forminator-ui/css/src/forminator-utilities.min.css', array(), FORMINATOR_VERSION );
	Forminator_Assets_Enqueue::fui_enqueue_style( 'forminator-ui-grid-open', forminator_plugin_url() . 'assets/forminator-ui/css/src/grid/forminator-grid.open.min.css', array(), FORMINATOR_VERSION );
	Forminator_Assets_Enqueue::fui_enqueue_style( 'forminator-ui-grid-enclosed', forminator_plugin_url() . 'assets/forminator-ui/css/src/grid/forminator-grid.enclosed.min.css', array(), FORMINATOR_VERSION );
	Forminator_Assets_Enqueue::fui_enqueue_style( 'forminator-ui-basic', forminator_plugin_url() . 'assets/forminator-ui/css/forminator-base.min.css', array(), FORMINATOR_VERSION );
	Forminator_Assets_Enqueue::fui_enqueue_style( 'forminator-ui', forminator_plugin_url() . 'assets/forminator-ui/css/src/forminator-ui.min.css', array(), FORMINATOR_VERSION );
}

/**
 * Enqueue front-end script
 *
 * Only use core here, if the style dynamically loaded, then load on model
 *
 * @since 1.0
 */
function forminator_print_front_scripts() {

	global $wp_locale;

	// LOAD: ChartJS.
	wp_enqueue_script(
		'forminator-chartjs',
		forminator_plugin_url() . 'assets/js/front/Chart.min.js',
		array( 'jquery' ),
		'2.8.0',
		false
	);
	$save_global_color    = "if (typeof window !== 'undefined' && typeof window.Color !== 'undefined') {window.notChartColor = window.Color;}";
	$restore_global_color = "if (typeof window !== 'undefined' && typeof window.notChartColor !== 'undefined') {window.Color = window.notChartColor;}";
	wp_add_inline_script( 'forminator-chartjs', $save_global_color, 'before' );
	wp_add_inline_script( 'forminator-chart', $save_global_color, 'before' );
	wp_add_inline_script( 'forminator-chartjs', $restore_global_color );
	wp_add_inline_script( 'forminator-chart', $restore_global_color );

	// LOAD: Datalabels plugin for ChartJS.
	wp_enqueue_script(
		'chartjs-plugin-datalabels',
		forminator_plugin_url() . 'assets/js/front/chartjs-plugin-datalabels.min.js',
		array( 'jquery' ),
		'0.6.0',
		false
	);

	// LOAD: Forminator UI Select2.
	wp_enqueue_script(
		'select2-forminator',
		forminator_plugin_url() . 'assets/forminator-ui/js/select2.full.min.js',
		array( 'jquery' ),
		FORMINATOR_VERSION,
		false
	);

	// LOAD: Forminator UI Global Scripts.
	wp_enqueue_script(
		'forminator-ui',
		forminator_plugin_url() . 'assets/forminator-ui/js/forminator-ui.min.js',
		array( 'jquery' ),
		'1.7.1',
		false
	);

	// TODO : check if its always needed.
	wp_enqueue_script( 'forminator-jquery-validate', forminator_plugin_url() . 'assets/js/library/jquery.validate.min.js', array( 'jquery' ), FORMINATOR_VERSION, false );

	wp_enqueue_script(
		'forminator-front-scripts',
		forminator_plugin_url() . 'build/front/front.multi.min.js',
		array( 'jquery', 'forminator-ui', 'forminator-jquery-validate' ),
		FORMINATOR_VERSION,
		false
	);

	wp_localize_script( 'forminator-front-scripts', 'ForminatorFront', forminator_localize_data() );

	// localize Datepicker js.
	$datepicker_date_format = str_replace(
		array(
			'd',
			'j',
			'l',
			'z', // Day.
			'F',
			'M',
			'n',
			'm', // Month.
			'Y',
			'y', // Year.
		),
		array(
			'dd',
			'd',
			'DD',
			'o',
			'MM',
			'M',
			'm',
			'mm',
			'yy',
			'y',
		),
		get_option( 'date_format' )
	);
	$datepicker_data        = array(
		'monthNames'      => array_values( $wp_locale->month ),
		'monthNamesShort' => array_values( $wp_locale->month_abbrev ),
		'dayNames'        => array_values( $wp_locale->weekday ),
		'dayNamesShort'   => array_values( $wp_locale->weekday_abbrev ),
		'dayNamesMin'     => array_values( $wp_locale->weekday_initial ),
		'dateFormat'      => $datepicker_date_format,
		'firstDay'        => absint( get_option( 'start_of_week' ) ),
		'isRTL'           => $wp_locale->is_rtl(),
	);
	wp_localize_script( 'forminator-front-scripts', 'datepickerLang', $datepicker_data );
}

/**
 * Return front-end localization data
 *
 * @since 1.0
 */
function forminator_localize_data() {
	$data = array(
		'ajaxUrl' => forminator_ajax_url(),
		'cform'   => array(
			'processing'                => esc_html__( 'Submitting form, please wait', 'forminator' ),
			'error'                     => esc_html__( 'An error occurred while processing the form. Please try again', 'forminator' ),
			'upload_error'              => esc_html__( 'An upload error occurred while processing the form. Please try again', 'forminator' ),
			'pagination_prev'           => esc_html__( 'Previous', 'forminator' ),
			'pagination_next'           => esc_html__( 'Next', 'forminator' ),
			'pagination_go'             => esc_html__( 'Submit', 'forminator' ),
			'gateway'                   => array(
				'processing' => esc_html__( 'Processing payment, please wait', 'forminator' ),
				'paid'       => esc_html__( 'Success! Payment confirmed. Submitting form, please wait', 'forminator' ),
				'error'      => esc_html__( 'Error! Something went wrong when verifying the payment', 'forminator' ),
			),
			'captcha_error'             => esc_html__( 'Invalid CAPTCHA', 'forminator' ),
			'no_file_chosen'            => esc_html__( 'No file chosen', 'forminator' ),
			// This is the file "/build/js/utils.js" found into intlTelInput plugin. Renamed so it makes sense within the "js/library" directory context.
			'intlTelInput_utils_script' => forminator_plugin_url() . 'assets/js/library/intlTelInputUtils.js',
			'process_error'             => esc_html__( 'Please try again', 'forminator' ),
			'payment_failed'            => esc_html__( 'Payment failed. Please try again.', 'forminator' ),
			'payment_cancelled'         => esc_html__( 'Payment was cancelled', 'forminator' ),
		),
		'poll'    => array(
			'processing' => esc_html__( 'Submitting vote, please wait', 'forminator' ),
			'error'      => esc_html__( 'An error occurred saving the vote. Please try again', 'forminator' ),
		),
		'quiz'    => array(
			'view_results' => esc_html__( 'View Results', 'forminator' ),
		),
		'select2' => array(
			'load_more'       => esc_html__( 'Loading more results…', 'forminator' ),
			'no_result_found' => esc_html__( 'No results found', 'forminator' ),
			'searching'       => esc_html__( 'Searching…', 'forminator' ),
			'loaded_error'    => esc_html__( 'The results could not be loaded.', 'forminator' ),
		),
	);

	/**
	 * Filter localize data
	 *
	 * @param array $data Current data array.
	 */
	return apply_filters( 'forminator_localize_data', $data );
}

/**
 * Return existing templates
 *
 * @since 1.0
 *
 * @param string $path Path.
 * @param array  $args Arguments.
 *
 * @return mixed
 */
function forminator_template( $path, $args = array() ) {
	$file    = forminator_plugin_dir() . "admin/views/$path.php";
	$content = '';

	if ( is_file( $file ) ) {
		ob_start();

		if ( isset( $args['id'] ) ) {
			$args['template_class'] = $args['class'];
			$args['template_id']    = $args['id'];
			$title                  = $args['title'];
			$header_callback        = $args['header_callback'];
			$main_callback          = $args['main_callback'];
			$footer_callback        = $args['footer_callback'];
		}

		include $file;

		$content = ob_get_clean();
	}

	return $content;
}

/**
 * Return if template exist
 *
 * @since 1.0
 *
 * @param string $path Path.
 *
 * @return bool
 */
function forminator_template_exist( $path ) {
	$file = forminator_plugin_dir() . "admin/views/$path.php";

	return is_file( $file );
}

/**
 * Return if paypal settings are filled
 *
 * @since 1.0
 * @return bool
 */
function forminator_has_paypal_settings() {
	$config = get_option( 'forminator_paypal_configuration', array() );

	if ( empty( $config ) ) {
		return false;
	}

	return true;
}

/**
 * Return if captcha settings are filled
 *
 * @since 1.0
 * @return bool
 */
function forminator_has_captcha_settings() {
	$key    = get_option( 'forminator_captcha_key', false );
	$secret = get_option( 'forminator_captcha_secret', false );

	if ( empty( $key ) || empty( $secret ) ) {
		return false;
	}

	return true;
}

/**
 * Return if captcha v2 settings are filled
 *
 * @since 1.0
 * @return bool
 */
function forminator_has_v2_captcha_settings() {
	$key    = get_option( 'forminator_captcha_key', false );
	$secret = get_option( 'forminator_captcha_secret', false );

	if ( empty( $key ) || empty( $secret ) ) {
		return false;
	}

	return true;
}

/**
 * Return if captcha v2 invisible settings are filled
 *
 * @since 1.0
 * @return bool
 */
function forminator_has_v2_invisible_captcha_settings() {
	$key    = get_option( 'forminator_v2_invisible_captcha_key', false );
	$secret = get_option( 'forminator_v2_invisible_captcha_secret', false );

	if ( empty( $key ) || empty( $secret ) ) {
		return false;
	}

	return true;
}

/**
 * Return if captcha v3 settings are filled
 *
 * @since 1.0
 * @return bool
 */
function forminator_has_v3_captcha_settings() {
	$key    = get_option( 'forminator_v3_captcha_key', false );
	$secret = get_option( 'forminator_v3_captcha_secret', false );

	if ( empty( $key ) || empty( $secret ) ) {
		return false;
	}

	return true;
}

/**
 * Return if hCaptcha keys are filled
 *
 * @since 1.15.5
 * @return bool
 */
function forminator_has_hcaptcha_settings() {
	$key    = get_option( 'forminator_hcaptcha_key', false );
	$secret = get_option( 'forminator_hcaptcha_secret', false );

	if ( empty( $key ) || empty( $secret ) ) {
		return false;
	}

	return true;
}

/**
 * Return if Cloudflare Turnstile keys are filled
 *
 * @return bool
 */
function forminator_has_turnstile_settings(): bool {
	$key    = get_option( 'forminator_turnstile_key', false );
	$secret = get_option( 'forminator_turnstile_secret', false );

	if ( empty( $key ) || empty( $secret ) ) {
		return false;
	}

	return true;
}

/**
 * Return if Stripe is is_connected
 *
 * @since 1.7
 * @return bool
 */
function forminator_has_stripe_connected() {
	if ( class_exists( 'Forminator_Gateway_Stripe' ) ) {
		try {
			$stripe = new Forminator_Gateway_Stripe();
			if ( $stripe->is_test_ready() && $stripe->is_live_ready() ) {
				return true;
			}
		} catch ( Forminator_Gateway_Exception $e ) {
			return false;
		}
	}

	return false;
}
/**
 * Return form ID
 *
 * @since 1.0
 * @return int
 */
function forminator_get_form_id_helper() {
	$screen = get_current_screen();
	$ids    = forminator_get_page_ids_helper();

	if ( ! in_array( $screen->id, $ids, true ) ) {
		return 0;
	}

	$form_id = (int) filter_input( INPUT_GET, 'form_id', FILTER_VALIDATE_INT );
	return $form_id;
}

/**
 * Get Page IDs
 *
 * @since 1.2
 * @return array
 */
function forminator_get_page_ids_helper() {
	// Free version.
	$title    = sanitize_title( esc_html__( 'Forminator', 'forminator' ) );
	$page_ids = array(
		$title . '_page_forminator-quiz-view',
		$title . '_page_forminator-cform-view',
		$title . '_page_forminator-poll-view',
		$title . '_page_forminator-entries',
	);

	return apply_filters( 'forminator_page_ids', $page_ids );
}

/**
 * Return form type
 *
 * @param mixed $common_name Common name.
 *
 * @since 1.0
 * @return int|null|string
 */
function forminator_get_form_type_helper( $common_name = false ) {
	$screen = get_current_screen();
	$ids    = forminator_get_page_ids_helper();
	if ( ! in_array( $screen->id, $ids, true ) ) {
		return 0;
	}

	$form_type = '';
	$page      = Forminator_Core::sanitize_text_field( 'page', null );

	if ( is_null( $page ) ) {
		return null;
	}

	switch ( $page ) {
		case 'forminator-quiz-view':
			$form_type = 'quiz';
			break;
		case 'forminator-poll-view':
			$form_type = 'poll';
			break;
		case 'forminator-cform-view':
			$form_type = 'cform';
			break;
		case 'forminator-entries':
			$form_type = Forminator_Core::sanitize_text_field( 'form_type' );
			switch ( $form_type ) {
				case 'forminator_forms':
					if ( ! $common_name ) {
						$form_type = 'cform';
					} else {
						$form_type = 'form';
					}

					break;
				case 'forminator_polls':
					$form_type = 'poll';
					break;
				case 'forminator_quizzes':
					$form_type = 'quiz';
					break;
				default:
					break;
			}
			break;
		default:
			break;
	}

	return $form_type;
}

/**
 * Forminator get exporter info
 *
 * @since 1.0
 *
 * @param string $info Exporter Info.
 * @param string $key Key.
 *
 * @return mixed
 */
function forminator_get_exporter_info( $info, $key ) {
	$data = get_option( 'forminator_entries_export_schedule', array() );
	if ( 'email' === $info && ! empty( $data[ $key ][ $info ] ) && ! is_array( $data[ $key ][ $info ] ) ) {
		return array( $data[ $key ][ $info ] );
	}

	return isset( $data[ $key ][ $info ] ) ? $data[ $key ][ $info ] : null;
}

/**
 * Return current logged in username
 *
 * @since 1.0
 * @return string
 */
function forminator_get_current_username() {
	$current_user = wp_get_current_user();
	if ( ! ( $current_user instanceof WP_User ) || empty( $current_user->user_login ) ) {
		return '';
	}
	$username = ! empty( $current_user->user_firstname ) ? $current_user->user_firstname : $current_user->user_login;

	return $username;
}

/**
 * Delete export logs
 *
 * @since 1.0
 *
 * @param int $form_id Form Id.
 *
 * @return bool
 */
function delete_export_logs( $form_id ) {
	if ( ! $form_id ) {
		return false;
	}

	$data   = get_option( 'forminator_exporter_log', array() );
	$delete = false;

	if ( isset( $data[ $form_id ] ) ) {
		unset( $data[ $form_id ] );
		$delete = update_option( 'forminator_exporter_log', $data );
	}

	return $delete;
}

/**
 * Forminator get export logs
 *
 * @since 1.0
 *
 * @param int $form_id Form Id.
 *
 * @return array
 */
function forminator_get_export_logs( $form_id ) {
	if ( ! $form_id ) {
		return array();
	}

	$data = get_option( 'forminator_exporter_log', array() );
	$row  = isset( $data[ $form_id ] ) ? $data[ $form_id ] : array();

	foreach ( $row as &$item ) {
		$item['time'] = gmdate( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $item['time'] );
	}

	return $row;
}

/**
 * Return current page url
 *
 * @since 1.0.3
 *
 * @return mixed
 */
function forminator_get_current_url() {
	if ( ! empty( $_SERVER['REQUEST_URI'] ) && false !== strpos( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ), 'admin-ajax.php' ) ) {
		$post_id = url_to_postid( wp_get_referer() );
	} else {
		$post_id = get_the_ID();
	}

	return esc_url( get_permalink( $post_id ) );
}

/**
 * Detect whether current request comes from any page builder preveiw page
 *
 * @since 1.13
 *
 * @return bool
 */
function forminator_is_page_builder_preview() {
	static $decision;
	if ( isset( $decision ) ) {
		return $decision;
	}

	$decision = false;
	global $wp;

	// Check Pro theme by Themeco https://theme.co/.
	if ( defined( 'X_TEMPLATE_PATH' ) && 'cornerstone-endpoint' === $wp->request ) {
		$decision = true;
		return $decision;
	}

	// Check DIVI theme page builder.
	// Note : following lines of codes are perfect to detect DIVI builder.
	// But DIVI builder is not showing Forminator forms in preview mood.
	// So commenting out these code for now.

	/*
	// $et_pb_preview = Forminator_Core::sanitize_text_field( 'et_pb_preview' );
	// if( defined( 'ET_CORE_VERSION' ) && $et_pb_preview ) {
	//  $decision = true;
	//  return $decision;
	// }
	*/

	// Check Elementor plugin.
	$action         = Forminator_Core::sanitize_text_field( 'action' );
	$editor_post_id = (int) Forminator_Core::sanitize_text_field( 'editor_post_id' );
	if ( defined( 'ELEMENTOR_VERSION' ) && 'elementor_ajax' === $action && $editor_post_id ) {
		$decision = true;
		return $decision;
	}

	return $decision;
}

/**
 * Return week day from number
 *
 * @since 1.0
 *
 * @param string $day Day.
 *
 * @return string
 */
function forminator_get_day_translated( $day ) {
	$days = array(
		'mon' => esc_html__( 'Monday', 'forminator' ),
		'tue' => esc_html__( 'Tuesday', 'forminator' ),
		'wed' => esc_html__( 'Wednesday', 'forminator' ),
		'thu' => esc_html__( 'Thursday', 'forminator' ),
		'fri' => esc_html__( 'Friday', 'forminator' ),
		'sat' => esc_html__( 'Saturday', 'forminator' ),
		'sun' => esc_html__( 'Sunday', 'forminator' ),
	);

	return isset( $days[ $day ] ) ? $days[ $day ] : $day;
}

/**
 * Add log of forminator
 *
 * By default it will check `WP_DEBUG` and `FORMINATOR_DEBUG`
 * Then will check `filters`
 *
 * @since 1.1
 * @since 1.3 add FORMINATOR_DEBUG as enabled flag
 */
function forminator_maybe_log() {
	$wp_debug_enabled = ( defined( 'WP_DEBUG' ) && WP_DEBUG );

	$enabled = ( defined( 'FORMINATOR_DEBUG' ) && FORMINATOR_DEBUG );

	$enabled = ( $wp_debug_enabled && $enabled );

	/**
	 * Filter log enable for forminator
	 *
	 * By default it will check `WP_DEBUG`, `FORMINATOR_DEBUG` must be true
	 *
	 * @since 1.1
	 *
	 * @param bool $enabled current enable status.
	 */
	$enabled = apply_filters( 'forminator_enable_log', $enabled );

	if ( $enabled ) {
		$args    = func_get_args();
		$message = wp_json_encode( $args );
		if ( false !== $message ) {
			error_log( '[Forminator] ' . $message );// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}
	}
}

/**
 * Helper to cast variable to target type
 *
 * @since 1.6
 *
 * @param mixed  $variable Variable.
 * @param string $type Variable type.
 *
 * @return mixed
 */
function forminator_var_type_cast( $variable, $type ) {
	switch ( $type ) {
		case 'bool':
			if ( ! is_bool( $variable ) ) {
				$variable = filter_var( $variable, FILTER_VALIDATE_BOOLEAN );
			}
			break;
		case 'str':
			if ( ! is_string( $variable ) ) {
				if ( is_array( $variable ) ) {
					$variable = implode( ', ', $variable );
				} else {
					// juggling.
					$variable = (string) $variable;
				}
			}
			break;
		case 'num':
			if ( ! is_numeric( $variable ) ) {
				// juggling.
				$variable = (int) $variable;
			}
			$variable = $variable + 0;
			break;
		case 'array':
			if ( ! is_array( $variable ) ) {
				// juggling.
				$variable = (array) $variable;
			}
			break;
		default:
			break;
	}

	return $variable;
}

/**
 * Get chart colors combination for Polls
 *
 * @since 1.5.3
 *
 * @param int  $poll_id Poll Id.
 * @param bool $accessibility_enabled Enable accessibility.
 *
 * @return array
 */
function forminator_get_poll_chart_colors( $poll_id = null, $accessibility_enabled = false ) {

	$chart_colors = $accessibility_enabled ?
		array(
			'rgba(137, 137, 137, 0.2)', // Monochrome Blue.
			'rgba(149, 149, 149, 0.2)', // Monochrome Red.
			'rgba(207 ,207 , 207, 0.2)', // Monochrome Yellow.
			'rgba(156, 156, 156, 0.2)', // Monochrome Green.
			'rgba(177 ,177 , 177, 0.2)', // Monochrome Orange.
			'rgba(134 ,134 , 134, 0.2)', // Monochrome Purple.
			'rgba(129 ,129 , 129, 0.2)', // Monochrome Blue Alt.
			'rgba(133 ,133 , 133, 0.2)', // Monochrome Red Alt.
			'rgba(206 ,206 , 206, 0.2)', // Monochrome Yellow Alt.
			'rgba(162 ,162 , 162, 0.2)', // Monochrome Green Alt.
			'rgba(156 ,156 , 156, 0.2)', // Monochrome Orange Alt.
			'rgba(114 ,114 , 114, 0.2)', // Monochrome Purple Alt.
			'rgba(0, 0, 0, 0.2)', // Monochrome Black.
			'rgba(136, 136, 136, 0.2)', // Monochrome Black Alt.
		) :
		array(
			'rgba(54, 162, 235, 0.2)', // Blue.
			'rgba(255, 99, 132, 0.2)', // Red.
			'rgba(255, 206, 86, 0.2)', // Yellow.
			'rgba(75, 192, 192, 0.2)', // Green.
			'rgba(255, 159, 64, 0.2)', // Orange.
			'rgba(153, 102, 255, 0.2)', // Purple.
			'rgba(102, 137, 161, 0.2)', // Blue Alt.
			'rgba(234, 86, 118, 0.2)', // Red Alt.
			'rgba(216, 220, 106, 0.2)', // Yellow Alt.
			'rgba(107, 193, 146, 0.2)', // Green Alt.
			'rgba(235, 130, 88, 0.2)', // Orange Alt.
			'rgba(153, 93, 129, 0.2)', // Purple Alt.
			'rgba(0, 0, 0, 0.2)', // Black.
			'rgba(136, 136, 136, 0.2)', // Black Alt.
		);

	/**
	 * Filter chart colors to be used for polls
	 *
	 * @since 1.5.3
	 *
	 * @param array $chart_colors
	 * @param int   $poll_id
	 */
	$chart_colors = apply_filters( 'forminator_poll_chart_colors', $chart_colors, $poll_id );

	return $chart_colors;
}

/**
 * Return CAPTCHA languages
 *
 * @since 1.5.4
 * @return array
 */
function forminator_get_captcha_languages() {
	return apply_filters(
		'forminator_captcha_languages',
		array(
			'ar'     => esc_html__( 'Arabic', 'forminator' ),
			'af'     => esc_html__( 'Afrikaans', 'forminator' ),
			'am'     => esc_html__( 'Amharic', 'forminator' ),
			'hy'     => esc_html__( 'Armenian', 'forminator' ),
			'az'     => esc_html__( 'Azerbaijani', 'forminator' ),
			'eu'     => esc_html__( 'Basque', 'forminator' ),
			'bn'     => esc_html__( 'Bengali', 'forminator' ),
			'bg'     => esc_html__( 'Bulgarian', 'forminator' ),
			'ca'     => esc_html__( 'Catalan', 'forminator' ),
			'zh-HK'  => esc_html__( 'Chinese (Hong Kong)', 'forminator' ),
			'zh-CN'  => esc_html__( 'Chinese (Simplified)', 'forminator' ),
			'zh-TW'  => esc_html__( 'Chinese (Traditional)', 'forminator' ),
			'hr'     => esc_html__( 'Croatian', 'forminator' ),
			'cs'     => esc_html__( 'Czech', 'forminator' ),
			'da'     => esc_html__( 'Danish', 'forminator' ),
			'nl'     => esc_html__( 'Dutch', 'forminator' ),
			'en-GB'  => esc_html__( 'English (UK)', 'forminator' ),
			'en'     => esc_html__( 'English (US)', 'forminator' ),
			'et'     => esc_html__( 'Estonian', 'forminator' ),
			'fil'    => esc_html__( 'Filipino', 'forminator' ),
			'fi'     => esc_html__( 'Finnish', 'forminator' ),
			'fr'     => esc_html__( 'French', 'forminator' ),
			'fr-CA'  => esc_html__( 'French (Canadian)', 'forminator' ),
			'gl'     => esc_html__( 'Galician', 'forminator' ),
			'ka'     => esc_html__( 'Georgian', 'forminator' ),
			'de'     => esc_html__( 'German', 'forminator' ),
			'de-AT'  => esc_html__( 'German (Austria)', 'forminator' ),
			'de-CH'  => esc_html__( 'German (Switzerland)', 'forminator' ),
			'el'     => esc_html__( 'Greek', 'forminator' ),
			'gu'     => esc_html__( 'Gujarati', 'forminator' ),
			'iw'     => esc_html__( 'Hebrew', 'forminator' ),
			'hi'     => esc_html__( 'Hindi', 'forminator' ),
			'hu'     => esc_html__( 'Hungarain', 'forminator' ),
			'is'     => esc_html__( 'Icelandic', 'forminator' ),
			'id'     => esc_html__( 'Indonesian', 'forminator' ),
			'it'     => esc_html__( 'Italian', 'forminator' ),
			'ja'     => esc_html__( 'Japanese', 'forminator' ),
			'kn'     => esc_html__( 'Kannada', 'forminator' ),
			'ko'     => esc_html__( 'Korean', 'forminator' ),
			'lo'     => esc_html__( 'Laothian', 'forminator' ),
			'lv'     => esc_html__( 'Latvian', 'forminator' ),
			'lt'     => esc_html__( 'Lithuanian', 'forminator' ),
			'ms'     => esc_html__( 'Malay', 'forminator' ),
			'ml'     => esc_html__( 'Malayalam', 'forminator' ),
			'mr'     => esc_html__( 'Marathi', 'forminator' ),
			'mn'     => esc_html__( 'Mongolian', 'forminator' ),
			'no'     => esc_html__( 'Norwegian', 'forminator' ),
			'fa'     => esc_html__( 'Persian', 'forminator' ),
			'pl'     => esc_html__( 'Polish', 'forminator' ),
			'pt'     => esc_html__( 'Portuguese', 'forminator' ),
			'pt-BR'  => esc_html__( 'Portuguese (Brazil)', 'forminator' ),
			'pt-PT'  => esc_html__( 'Portuguese (Portugal)', 'forminator' ),
			'ro'     => esc_html__( 'Romanian', 'forminator' ),
			'ru'     => esc_html__( 'Russian', 'forminator' ),
			'rs'     => esc_html__( 'Serbian', 'forminator' ),
			'si'     => esc_html__( 'Sinhalese', 'forminator' ),
			'sk'     => esc_html__( 'Slovak', 'forminator' ),
			'sl'     => esc_html__( 'Slovenian', 'forminator' ),
			'es'     => esc_html__( 'Spanish', 'forminator' ),
			'es-419' => esc_html__( 'Spanish (Latin America)', 'forminator' ),
			'sw'     => esc_html__( 'Swahili', 'forminator' ),
			'sv'     => esc_html__( 'Swedish', 'forminator' ),
			'ta'     => esc_html__( 'Tamil', 'forminator' ),
			'te'     => esc_html__( 'Telugu', 'forminator' ),
			'th'     => esc_html__( 'Thai', 'forminator' ),
			'tr'     => esc_html__( 'Turkish', 'forminator' ),
			'uk'     => esc_html__( 'Ukrainian', 'forminator' ),
			'ur'     => esc_html__( 'Urdu', 'forminator' ),
			'vi'     => esc_html__( 'Vietnamese', 'forminator' ),
			'zu'     => esc_html__( 'Zulu', 'forminator' ),
		)
	);
}

/**
 * Flag whether doc link should shown or not
 *
 * @since 1.6
 * @return bool
 */
function forminator_is_show_documentation_link() {
	if ( Forminator::is_wpmudev_member() ) {
		return ! apply_filters( 'wpmudev_branding_hide_doc_link', false );
	}

	return true;
}

/**
 * Flag whether branding should shown or not
 *
 * @since 1.6
 * @return bool
 */
function forminator_is_show_branding() {
	if ( Forminator::is_wpmudev_member() ) {
		return ! apply_filters( 'wpmudev_branding_hide_branding', false );
	}

	return true;
}

/**
 * Check if whitelabel enable.
 *
 * @return bool
 */
function forminator_can_whitelabel() {
	if (
		! class_exists( '\WPMUDEV_Dashboard' ) ||
		! isset( \WPMUDEV_Dashboard::$whitelabel ) ||
		( method_exists( \WPMUDEV_Dashboard::$whitelabel, 'can_whitelabel' ) &&
			! \WPMUDEV_Dashboard::$whitelabel->can_whitelabel()
		)
	) {
		return false;
	}

	return true;
}

/**
 * Get Dashboard settings
 *
 * @since 1.6.3
 *
 * @param string|null $widget Widget.
 * @param mixed       $default_value Default value.
 *
 * @return array|mixed
 */
function forminator_get_dashboard_settings( $widget = null, $default_value = array() ) {
	$settings           = array();
	$dashboard_settings = get_option( 'forminator_dashboard_settings', $default_value );

	if ( ! is_null( $widget ) ) {
		if ( isset( $dashboard_settings[ $widget ] ) ) {
			$settings = $dashboard_settings[ $widget ];
		} else {
			$settings = $default_value;
		}
	}

	/**
	 * Filter Dashboard settings
	 *
	 * @since 1.6.3
	 *
	 * @param mixed $settings
	 * @param string widget
	 * @param mixed $default_value
	 *
	 * @return mixed
	 */
	$settings = apply_filters( 'forminator_dashboard_settings', $settings, $widget, $default_value );

	return $settings;
}

/**
 * Reset Forminator Settings
 *
 * @see   forminator_delete_custom_options()
 * @see   forminator_delete_addon_options()
 * @see   forminator_delete_custom_posts()
 * @since 1.6.3
 */
function forminator_reset_settings() {
	global $wpdb;

	/**
	 * Fires before Settings reset
	 *
	 * @since 1.6.3
	 */
	do_action( 'forminator_before_reset_settings' );

	// Permissions option is deleted inside this function too.
	forminator_delete_permissions();

	/**
	 * Forminator_delete_custom_options
	 *
	 * @see forminator_delete_custom_options()
	 */

	delete_option( 'forminator_pagination_listings' );
	delete_option( 'forminator_pagination_entries' );
	delete_option( 'forminator_captcha_key' );
	delete_option( 'forminator_captcha_secret' );
	delete_option( 'forminator_v2_invisible_captcha_key' );
	delete_option( 'forminator_v2_invisible_captcha_secret' );
	delete_option( 'forminator_v3_captcha_key' );
	delete_option( 'forminator_v3_captcha_secret' );
	delete_option( 'forminator_captcha_language' );
	delete_option( 'forminator_captcha_theme' );
	delete_option( 'forminator_captcha_tab_saved' );
	delete_option( 'forminator_hcaptcha_key' );
	delete_option( 'forminator_hcaptcha_secret' );
	delete_option( 'forminator_welcome_dismissed' );
	delete_option( 'forminator_version' );
	delete_option( 'forminator_retain_votes_interval_number' );
	delete_option( 'forminator_retain_votes_interval_unit' );
	delete_option( 'forminator_retain_submissions_interval_number' );
	delete_option( 'forminator_retain_submissions_interval_unit' );
	delete_option( 'forminator_enable_erasure_request_erase_form_submissions' );
	delete_option( 'forminator_form_privacy_settings' );
	delete_option( 'forminator_poll_privacy_settings' );
	delete_option( 'forminator_retain_ip_interval_number' );
	delete_option( 'forminator_retain_ip_interval_unit' );
	delete_option( 'retain_geolocation_forever' );
	delete_option( 'forminator_retain_geolocation_interval_number' );
	delete_option( 'forminator_retain_geolocation_interval_unit' );
	delete_option( 'forminator_retain_poll_submissions_interval_number' );
	delete_option( 'forminator_retain_poll_submissions_interval_unit' );
	delete_option( 'forminator_posts_map' );
	delete_option( 'forminator_retain_quiz_submissions_interval_number' );
	delete_option( 'forminator_retain_quiz_submissions_interval_unit' );
	delete_option( 'forminator_dashboard_settings' );
	delete_option( 'forminator_sender_email_address' );
	delete_option( 'forminator_sender_name' );
	delete_option( 'forminator_enable_accessibility' );
	delete_option( 'forminator_entries_export_schedule' );
	delete_option( 'forminator_paypal_api_mode' );
	delete_option( 'forminator_paypal_secret' );
	delete_option( 'forminator_currency' );
	delete_option( 'forminator_exporter_log' );
	delete_option( 'forminator_uninstall_clear_data' );
	delete_option( 'forminator_custom_upload' );
	delete_option( 'forminator_custom_upload_root' );
	delete_option( 'forminator_stripe_configuration' );
	delete_option( 'forminator_stripe_payment_intents' );
	delete_option( 'forminator_paypal_configuration' );
	delete_option( 'forminator_usage_tracking' );
	delete_option( 'forminator_auto_saving' );

	/**
	 * Forminator_delete_addon_options
	 *
	 * @see forminator_delete_addon_options()
	 */
	delete_option( 'forminator_activated_addons' );
	$registered_addons = forminator_get_registered_addons();
	foreach ( $registered_addons as $addon_slug => $registered_addon ) {
		delete_option( "forminator_addon_{$addon_slug}_version" );
		delete_option( "forminator_addon_{$addon_slug}_settings" );
	}

	/**
	 * Forminator_delete_custom_posts
	 *
	 * @see forminator_delete_custom_posts()
	 */
	// Now we delete the custom posts.
	$entry_table      = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY );
	$entry_meta_table = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY_META );
	$views_table      = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_VIEWS );
	$reports_table    = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_REPORTS );
	$forms_sql        = "SELECT `ID` FROM {$wpdb->posts} WHERE `post_type` = %s";
	$form_types       = forminator_form_types();
	foreach ( $form_types as $type ) {
		$query = $wpdb->prepare( $forms_sql, $type ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$ids   = $wpdb->get_col( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( $ids ) {
			foreach ( $ids as $id ) {
				Forminator_Form_Entry_Model::delete_form_entry_cache( $id );
				wp_delete_post( $id );
			}
		}
	}
	$wpdb->query( "TRUNCATE TABLE {$entry_table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery
	$wpdb->query( "TRUNCATE TABLE {$entry_meta_table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery
	$wpdb->query( "TRUNCATE TABLE {$views_table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery
	$wpdb->query( "TRUNCATE TABLE {$reports_table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery

	/**
	 * Fires after Settings reset
	 *
	 * @since 1.27.0
	 */
	do_action( 'forminator_after_reset_settings' );
}

/**
 * Get Forminator CPT names
 *
 * @return array
 */
function forminator_form_types() {
	$form_types = array(
		'forminator_forms',
		'forminator_polls',
		'forminator_quizzes',
	);

	return $form_types;
}

/**
 * DON'T USE IT!!! It's only for backward compatibility
 * Get prefix based on module slug.
 *
 * @param string $module_slug Module slug.
 * @param string $form_prefix Optional. Prefix before Custom Form type or `post_type` value.
 * @param bool   $ucfirst Optional. With capital the first letter.
 * @param bool   $plural Optional. Plural or singular.
 * @return string
 */
function forminator_get_prefix( $module_slug, $form_prefix = '', $ucfirst = false, $plural = false ) {
	if ( 'post_type' === $form_prefix ) {
		$prefix = '';
		switch ( $module_slug ) {
			case 'form':
				$prefix = 'forminator_forms';
				break;
			case 'poll':
				$prefix = 'forminator_polls';
				break;
			case 'quiz':
				$prefix = 'forminator_quizzes';
				break;
			default:
				break;
		}
		return $prefix;
	}
	$prefix = $module_slug;
	if ( $ucfirst ) {
		$prefix = ucfirst( $prefix );
	}
	if ( ! empty( $form_prefix ) && 'form' === $module_slug ) {
		$prefix = $form_prefix . $prefix;
	}
	if ( $ucfirst ) {
		// for getting CForm, Custom_Form, Custom-Form, etc.
		$prefix = ucfirst( $prefix );
	}
	if ( $plural ) {
		if ( 'quiz' === $module_slug ) {
			$prefix .= 'ze';
		}
		$prefix .= 's';
	}

	return $prefix;
}

/**
 * Add Slash in string
 *
 * @since 1.8
 *
 * @param string $value Values to add slashes.
 * @param string $char Character.
 *
 * @return string
 */
function forminator_addcslashes( $value, $char = '"\\/' ) {
	$value = esc_html( $value );

	return addcslashes( $value, $char );
}

/**
 * Return URL link.
 *
 * @since 1.13
 *
 * @param string $link_for Accepts: 'docs', 'plugin', 'rate', 'support', 'roadmap'.
 * @param string $campaign  Utm campaign tag to be used in link. Default: ''.
 *
 * @return string
 */
function forminator_get_link( $link_for, $campaign = '' ) {
	$domain   = 'https://wpmudev.com';
	$wp_org   = 'https://wordpress.org';
	$utm_tags = "?utm_source=forminator&utm_medium=plugin&utm_campaign={$campaign}";

	switch ( $link_for ) {
		case 'docs':
			$link = "{$domain}/docs/wpmu-dev-plugins/forminator/{$utm_tags}";
			break;
		case 'plugin':
			$link = "{$domain}/project/forminator-pro/{$utm_tags}";
			break;
		case 'rate':
			$link = "{$wp_org}/support/plugin/forminator/reviews/#new-post";
			break;
		case 'support':
			$link = "{$wp_org}/support/plugin/forminator/";
			break;
		case 'roadmap':
			$link = "{$domain}/roadmap/";
			break;
		default:
			$link = '';
			break;
	}

	return $link;
}

/**
 * Check if the plugin is active network wide.
 *
 * @since 1.13 forminator_membership_status
 * @since 1.18.2 Change how membership is detected
 *
 * @return bool
 */
function forminator_can_install_pro() {
	// Dashboard is active.
	if ( class_exists( 'WPMUDEV_Dashboard' ) && method_exists( WPMUDEV_Dashboard::$upgrader, 'user_can_install' ) ) {
		$has_access = WPMUDEV_Dashboard::$upgrader->user_can_install( 2097296, true );
	} else {
		$has_access = false;
	}

	return apply_filters( 'forminator_wpmudev_can_install_pro', $has_access );
}

/**
 * Check if the plugin is active network wide.
 *
 * @since 1.13
 *
 * @return bool
 */
function forminator_is_networkwide() {
	if ( is_multisite() ) {
		// Makes sure the plugin is defined before trying to use it.
		if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
			require_once ABSPATH . '/wp-admin/includes/plugin.php';
		}

		$active = is_plugin_active_for_network( plugin_basename( FORMINATOR_PLUGIN_BASENAME ) );
	} else {
		$active = false;
	}

	return $active;
}
/**
 * Check if user is a WPMU DEV admin.
 *
 * @since 3.1.4
 *
 * @return bool
 */
function is_wpmu_dev_admin() {
	if ( class_exists( 'WPMUDEV_Dashboard' ) ) {
		if ( method_exists( 'WPMUDEV_Dashboard_Site', 'allowed_user' ) ) {
			$user_id = get_current_user_id();
			return WPMUDEV_Dashboard::$site->allowed_user( $user_id );
		}
	}

	return false;
}

/**
 * Check membership status
 *
 * Possible return values:
 * 'free'    - Free hub membership.
 * 'single'  - Single membership (i.e. only 1 project is licensed)
 * 'unit'    - One or more projects licensed
 * 'full'    - Full membership, no restrictions.
 * 'paused'  - Membership access is paused.
 * 'expired' - Expired membership.
 * ''        - (empty string) If user is not logged in or with an unknown type.
 *
 * @since 1.24.1
 *
 * @return mixed
 */
function forminator_get_wpmudev_membership() {
	if ( class_exists( 'WPMUDEV_Dashboard' ) ) {
		if ( method_exists( 'WPMUDEV_Dashboard_Api', 'get_membership_status' ) ) {
			return WPMUDEV_Dashboard::$api->get_membership_status();
		}
	}

	return '';
}

/**
 * Check if site is connected
 *
 * @since 1.24.1
 *
 * @return bool
 */
function forminator_is_site_connected_to_hub() {
	switch ( forminator_get_wpmudev_membership() ) {
		case 'free':
		case 'single':
		case 'unit':
		case 'full':
		case 'paused':
		case 'expired':
			$status = true;
			break;

		default:
			$status = false;
			break;
	}

	return $status;
}

/**
 * Global tracking
 *
 * @return mixed|null
 */
function forminator_global_tracking() {
	return apply_filters( 'forminator_global_tracking', true );
}

/**
 * Get forminator capabilities.
 *
 * @return array
 */
function forminator_get_capabilities() {
	return array(
		'manage_forminator_modules',
		'manage_forminator_submissions',
		'manage_forminator_templates',
		'manage_forminator_addons',
		'manage_forminator_integrations',
		'manage_forminator_reports',
		'manage_forminator_settings',
	);
}

/**
 * Apply forminator capabilities.
 *
 * @param object $subject    Either WP_User or WP_Role object.
 * @param array  $permission The current permission setting.
 *
 * @return [type]
 */
function forminator_apply_capabilities( $subject, $permission ) {
	if ( false === $subject || is_null( $subject ) ) {
		return;
	}

	$caps = forminator_get_capabilities();

	foreach ( $caps as $cap ) {
		if ( $permission[ $cap ] ) {
			$subject->add_cap( $cap, true );
		} else {
			$subject->remove_cap( $cap );
		}
	}
}

/**
 * Get the appropriate capability based on Permission settings.
 *
 * @param string $page_slug Used for determining capability.
 * Can also be used for getting cap for 'current_user_can' function.
 *
 * @return string
 */
function forminator_get_permission( $page_slug ) {
	$default_cap = forminator_get_admin_cap();

	// If current user is admin, allow on all Forminator pages.
	if ( current_user_can( $default_cap ) || empty( $page_slug ) ) {
		return $default_cap;
	}
	// If current user a guest, return the default admin cap because they don't have any capabilities anyway.
	$user = wp_get_current_user();
	if ( empty( $user->ID ) ) {
		return $default_cap;
	}

	$permissions = get_option( 'forminator_permissions', array() );
	if ( empty( $permissions ) ) {
		return $default_cap;
	}

	// Assign appropriate cap based on page_slug.
	switch ( $page_slug ) {
		case 'forminator':
		case 'forminator-cform':
		case 'forminator-cform-wizard':
		case 'forminator-cform-view':
		case 'forminator-poll':
		case 'forminator-poll-wizard':
		case 'forminator-poll-view':
		case 'forminator-quiz':
		case 'forminator-nowrong-wizard':
		case 'forminator-knowledge-wizard':
		case 'forminator-quiz-view':
			$cap = 'manage_forminator_modules';
			break;
		case 'forminator-entries':
			$cap = 'manage_forminator_submissions';
			break;

		case 'forminator-templates':
			$cap = 'manage_forminator_templates';
			break;

		case 'forminator-addons':
			$cap = 'manage_forminator_addons';
			break;
		case 'forminator-integrations':
			$cap = 'manage_forminator_integrations';
			break;
		case 'forminator-reports':
			$cap = 'manage_forminator_reports';
			break;
		case 'forminator-settings':
			$cap = 'manage_forminator_settings';
			break;
		default:
			$cap = $default_cap;
			break;
	}

	$user_allowed = false;
	$role_allowed = false;

	// Check permissions for excluded users.
	foreach ( $permissions as $permission ) {

		// If role.
		if ( 'role' === $permission['permission_type'] ) {

			if ( empty( $permission['exclude_users'] ) ) {
				$role_allowed = true;
				continue;
			}

			// If current user is in excluded users, return the default admin cap.
			if ( in_array( intval( $user->ID ), array_map( 'intval', $permission['exclude_users'] ), true ) ) {
				$role_allowed = false;
			} else {
				$role_allowed = true;
			}
		}

		// If user.
		if ( 'specific' === $permission['permission_type'] && in_array( intval( $user->ID ), array_map( 'intval', $permission['specific_user'] ), true ) ) {

			if ( isset( $permission[ $cap ] ) && $permission[ $cap ] ) {
				$user_allowed = true;
			} else {
				$user_allowed = false;
			}
		}
	}

	if (
		( $user_allowed || $role_allowed ) ||
		( $user_allowed && ! $role_allowed )
	) {
		return $cap;
	} elseif ( ! $user_allowed || ! $role_allowed ) {
		return $default_cap;
	}
}

/**
 * Delete forminator permissions and revoke caps.
 * Used on uninstallation and
 *
 * @since 1.28.0
 */
function forminator_delete_permissions() {
	$permissions = get_option( 'forminator_permissions', array() );
	if ( empty( $permissions ) ) {
		return;
	}

	// Get the Forminator caps.
	$caps = forminator_get_capabilities();

	foreach ( $permissions as $permission ) {
		// If specific user.
		if ( 'specific' === $permission['permission_type'] ) {

			// Remove caps from users.
			foreach ( $permission['specific_user'] as $email ) {
				$user = get_user_by( 'email', $email );

				if ( false !== $user ) {
					foreach ( $caps as $cap ) {
						$user->remove_cap( $cap );
					}
				}
			}

			// If role.
		} else {
			$role = get_role( $permission['user_role'] );

			// Remove caps from the role.
			if ( ! is_null( $role ) ) {
				foreach ( $caps as $cap ) {
					$role->remove_cap( $cap );
				}
			}
		}
	}

	// Finally, delete the option.
	delete_option( 'forminator_permissions' );
}

/**
 * Searches for $needle in the multidimensional array $haystack.
 *
 * @url https://stackoverflow.com/a/28473219
 *
 * @param mixed $needle The item to search for.
 * @param array $haystack The array to search.
 *
 * @return array|bool The indices of $needle in $haystack across the
 *  various dimensions. FALSE if $needle was not found.
 */
function forminator_recursive_array_search( $needle, $haystack ) {
	foreach ( $haystack as $key => $value ) {
		if ( ! is_array( $value ) && (string) $needle === (string) $value ) {
			return array( $key );
		} elseif ( is_array( $value ) ) {
			$subkey = forminator_recursive_array_search( $needle, $value );
			if ( $subkey ) {
				array_unshift( $subkey, $key );
				return $subkey;
			}
		}
	}
	return null; // Return null if the needle is not found.
}

/**
 * Get Accessible user roles
 *
 * @return array
 */
function forminator_get_accessible_user_roles() {
	// Get the current user object.
	$current_user = wp_get_current_user();
	// Check if user is logged in | Have access to create user.
	if ( empty( $current_user ) || ! current_user_can( 'create_users' ) ) {
		return array();
	}

	if ( ! function_exists( 'get_editable_roles' ) ) {
		require_once ABSPATH . 'wp-admin/includes/user.php';
	}

	// Get roles.
	$roles = get_editable_roles();

	// Allow all roles if the user is a super admin or has the administrator role.
	if ( ( is_multisite() && is_super_admin() ) || in_array( 'administrator', $current_user->roles, true ) ) {
		return $roles;
	}

	// Allow all roles except `administrator` if the user has the `promote_users` capability.
	if ( current_user_can( 'promote_users' ) ) {
		if ( isset( $roles['administrator'] ) ) {
			unset( $roles['administrator'] );
		}
	} else {
		// Allow only the default role if the user does not have the `promote_users` capability.
		$default_role = get_option( 'default_role', 'subscriber' );
		if ( $roles[ $default_role ] ) {
			$roles = array( $default_role => $roles[ $default_role ] );
		} else {
			$roles = array();
		}
	}
	return $roles;
}

/**
 * Validate registration form settings.
 *
 * @param array $settings Settings.
 * @return bool|WP_Error
 */
function forminator_validate_registration_form_settings( $settings ) {
	if ( ! empty( $settings['form-type'] ) && 'registration' === $settings['form-type'] ) {
		$error_message = esc_html__( 'Unfortunately, you do not have the required permissions or user role to perform this action.', 'forminator' );
		if ( ! current_user_can( 'create_users' ) ) {
			return new WP_Error( 'invalid_access', $error_message );
		}
		$roles = forminator_get_accessible_user_roles();
		if ( isset( $settings['registration-user-role'] ) && 'fixed' === $settings['registration-user-role'] ) {
			if ( isset( $settings['registration-role-field'] ) && ! isset( $roles[ $settings['registration-role-field'] ] )
				&& 'notCreate' !== $settings['registration-role-field'] ) { // Respect the "Don't create a user in the network's main site" option.
				return new WP_Error( 'invalid_user_role', $error_message );
			}
		} elseif ( ! empty( $settings['user_role'] ) && is_array( $settings['user_role'] ) ) {
			foreach ( $settings['user_role'] as $user_role ) {
				if ( isset( $user_role['role'] ) && ! isset( $roles[ $user_role['role'] ] )
					&& 'notCreate' !== $user_role['role'] ) { // Respect the "Don't create a user in the network's main site" option.
					return new WP_Error( 'invalid_user_role', $error_message );
				}
			}
		}
	}
	return true;
}

/**
 * Can the current user approve a user and create a site.
 *
 * @param object $signup Signup.
 * @return bool
 */
function forminator_can_approve_user_and_create_site( $signup ) {
	$roles = forminator_get_accessible_user_roles();
	if ( ! empty( $signup->user_data['role'] ) ) {
		if ( forminator_is_main_site() ) {
			// Either the 'Don't create a user' option is chosen, or the current user has access to the required user roles.
			if ( 'notCreate' === $signup->user_data['role'] || isset( $roles[ $signup->user_data['role'] ] ) ) { // Respect the "Don't create a user in the network's main site" option.
				$option_create_site = forminator_get_property( $signup->settings, 'site-registration' );
				// Either the 'site-registration' option is disabled, or the current user has access to create sites.
				if ( 'enable' !== $option_create_site || current_user_can( 'create_sites' ) ) {
					return true;
				}
			}
		} elseif ( isset( $roles[ $signup->user_data['role'] ] ) ) {
			return true;
		}
	}
	return false;
}

/**
 * Can apply forminator default color
 *
 * @param array $settings Form settings.
 * @return bool
 */
function forminator_can_apply_default_color( $settings ) {
	$form_style = $settings['form-style'] ?? 'default';
	$prefix     = '';
	if ( 'basic' === $form_style ) {
		$prefix = 'basic-';
	}
	$color_option_key = $prefix . 'cform-color-option';
	// For backward compatible.
	$default_color_option = empty( $settings[ $color_option_key ] ) ? 'forminator' : 'theme';

	$color_option = $settings[ $color_option_key ] ?? $default_color_option;
	return 'forminator' === $color_option;
}

/**
 * Schedule recurring action.
 *
 * @param string $action Action name.
 * @param int    $interval Expiration time in seconds.
 *
 * @return bool
 */
function forminator_set_recurring_action( string $action, int $interval ): bool {
	// Check cache first.
	if ( get_transient( $action ) ) {
		return true;
	}

	// if tables exist.
	if ( ! Forminator_Core::check_action_scheduler_tables() ) {
		return false;
	}

	// Clear old cron schedule.
	if ( wp_next_scheduled( $action ) ) {
		wp_clear_scheduled_hook( $action );
	}

	$scheduled = as_has_scheduled_action( $action );
	if ( $scheduled ) {
		// Set cache.
		$expiration = 2 * HOUR_IN_SECONDS;
		set_transient( $action, true, $expiration );
	} else {
		// Create new schedule using AS.
		$action_id = as_schedule_recurring_action( time() + 20, $interval, $action, array(), 'forminator', true );
		$scheduled = $action_id > 0;
	}

	return $scheduled;
}

/**
 * Check if cloud templates are disabled
 *
 * @return bool
 */
function forminator_cloud_templates_disabled(): bool {
	$is_disabled = apply_filters( 'forminator_disable_cloud_templates', false );
	if ( ! $is_disabled ) {
		if ( is_wpmu_dev_admin() ) {
			$is_disabled = false;
		} elseif ( forminator_can_whitelabel() ) {
			$is_disabled = true;
		}
	}

	return $is_disabled;
}

/**
 * Check if user registration is enabled in WordPress settings.
 *
 * @return bool
 */
function forminator_is_user_registration_enabled() {
	if ( is_multisite() ) {
		$registration_option = get_site_option( 'registration' );
		if ( in_array( $registration_option, array( 'all', 'user' ), true ) ) {
			return true;
		}
	} elseif ( get_option( 'users_can_register' ) ) {
		return true;
	}
	return false;
}

/**
 * Check if site registration is enabled in WordPress settings.
 *
 * @return bool
 */
function forminator_is_site_registration_enabled() {
	// Allow site registration only if on the main site and site registration is enabled.
	if ( forminator_is_main_site() ) {
		$registration_option = get_site_option( 'registration' );
		// Site registration is enabled only if the option is set to 'all'.
		// For the 'blog' option, only logged-in users can register new sites, so we don't support that here.
		if ( 'all' === $registration_option ) {
			return true;
		}
	}
	return false;
}
