<?php
/**
 * The Forminator_Assets_Enqueue_Form class.
 *
 * @package Forminator
 */

/**
 * Conditionally load assets class
 *
 * @since 1.11
 */
class Forminator_Assets_Enqueue_Form extends Forminator_Assets_Enqueue {
	/**
	 * Module slug
	 *
	 * @var string
	 */
	protected static $module_slug = 'form';

	/**
	 * Enqueue form styles
	 *
	 * @param object $render_obj Forminator_CForm_Front object.
	 * @since 1.11
	 */
	public function enqueue_styles( $render_obj ) {
		// Load base styles.
		$this->load_base_styles();

		// Load FUIselect2.
		$this->load_select_styles( $render_obj );

		// Forminator UI - Full stylesheet.
		$this->load_full_styles( $render_obj );
	}

	/**
	 * Enqueue form scripts
	 *
	 * @param mixed $render_obj Render Object.
	 *
	 * @since 1.11
	 */
	public function enqueue_scripts( $render_obj ) {
		// Load FUIselect2.
		$this->load_select_scripts( $render_obj );

		// Load form base scripts.
		$this->load_base_scripts();

		// FIELD: Slider.
		if ( $render_obj->has_field_type( 'slider' ) ) {
			self::load_slider_scripts();
		}

		// FIELD: Phone.
		if ( $render_obj->has_field_type( 'phone' ) ) {
			$this->load_phone_scripts();
		}

		// FIELD: HTML.
		if ( $render_obj->has_field_type( 'html' ) ) {
			self::load_dompurify_scripts();
		}

		// FIELD: Date picker.
		if ( $render_obj->has_field_type( 'date' ) ) {
			$this->load_date_scripts();
		}

		// FIELD: calculation picker.
		if ( $render_obj->has_field_type( 'calculation' )
			|| $render_obj->has_field_type( 'currency' )
			|| $render_obj->has_field_type( 'number' ) ) {
			$this->load_number_scripts();
		}

		// ACTION: Enqueue custom form scripts.
		do_action( 'forminator_custom_forms_enqueue_scripts', $render_obj );
	}

	/**
	 * Load base styles and scripts
	 *
	 * @since 1.11
	 */
	public function load_base_styles() {
		$form_design     = $this->get_module_design();
		$form_settings   = $this->get_settings();
		$field_style_key = 'basic' === $form_design ? 'basic-fields-style' : 'fields-style';

		parent::load_base_styles();

		if ( ! isset( $form_settings['basic-fields-style'] ) ) {
			$form_settings['basic-fields-style'] = 'open';
		}

		// Forminator UI - Grid.
		if ( isset( $form_settings[ $field_style_key ] ) && 'open' === $form_settings[ $field_style_key ] ) {

			Forminator_Assets_Enqueue::fui_enqueue_style(
				'forminator-grid-default',
				forminator_plugin_url() . 'assets/forminator-ui/css/src/grid/forminator-grid.open.min.css',
				array(),
				FORMINATOR_VERSION
			);
		} elseif ( isset( $form_settings[ $field_style_key ] ) && 'enclosed' === $form_settings[ $field_style_key ] ) {

			Forminator_Assets_Enqueue::fui_enqueue_style(
				'forminator-grid-enclosed',
				forminator_plugin_url() . 'assets/forminator-ui/css/src/grid/forminator-grid.enclosed.min.css',
				array(),
				FORMINATOR_VERSION
			);
		}

		// Forminator UI - Base stylesheet.
		if ( 'none' !== $form_design && 'basic' !== $form_design ) {
			Forminator_Assets_Enqueue::fui_enqueue_style(
				'forminator-forms-' . $form_design . '-base',
				forminator_plugin_url() . 'assets/forminator-ui/css/src/form/forminator-form-' . $form_design . '.base.min.css',
				array(),
				FORMINATOR_VERSION
			);
		}

		if ( 'basic' === $form_design ) {
			Forminator_Assets_Enqueue::fui_enqueue_style(
				'forminator-forms-' . $form_design . '-base',
				forminator_plugin_url() . 'assets/forminator-ui/css/forminator-base.min.css',
				array(),
				FORMINATOR_VERSION
			);
		}
	}

	/**
	 * Load full styles
	 *
	 * @param object $render_obj Forminator_CForm_Front object.
	 */
	public function load_full_styles( $render_obj ) {
		$form_design   = $this->get_module_design();
		$form_settings = $this->get_settings();

		$has_phone_national      = $render_obj->has_field_type_with_setting_value( 'phone', 'validation', 'standard' );
		$has_phone_international = $render_obj->has_field_type_with_setting_value( 'phone', 'validation', 'international' );
		$has_phone_settings      = ( $has_phone_national || $has_phone_international );

		$has_address_country = $render_obj->has_field_type_with_setting_value( 'address', 'address_country', 'true' );

		$has_select_single   = $render_obj->has_field_type_with_setting_value( 'select', 'value_type', 'single' );
		$has_select_multiple = $render_obj->has_field_type_with_setting_value( 'select', 'value_type', 'multiselect' );

		$has_datepicker = $render_obj->has_field_type_with_setting_value( 'date', 'field_type', 'picker' );
		$has_dateselect = $render_obj->has_field_type_with_setting_value( 'date', 'field_type', 'select' );

		$has_timepicker = $render_obj->has_field_type( 'time' );

		$has_uploader = $render_obj->has_field_type( 'upload' );

		$has_post_feat_image  = $render_obj->has_field_type_with_setting_value( 'postdata', 'post_image', true );
		$has_post_categories  = $render_obj->has_field_type_with_setting_value( 'postdata', 'category', true );
		$has_post_tags        = $render_obj->has_field_type_with_setting_value( 'postdata', 'post_tag', true );
		$has_multi_categories = $render_obj->has_field_type_with_setting_value( 'postdata', 'category_multiple', '1' );
		$has_multi_tags       = $render_obj->has_field_type_with_setting_value( 'postdata', 'post_tag_multiple', '1' );

		$has_currency = $render_obj->has_field_type( 'currency' );
		$has_paypal   = $render_obj->has_field_type( 'paypal' );
		$has_stripe   = $render_obj->has_field_type( 'stripe' ) || $render_obj->has_field_type( 'stripe-ocs' );

		$has_password = $render_obj->has_field_type( 'password' );

		$has_signature = $render_obj->has_field_type( 'signature' );

		// Forminator UI - Base stylesheet.
		if ( 'none' !== $form_design && 'basic' !== $form_design ) {

			// Forminator UI - Full stylesheet.
			if ( $has_phone_settings || $has_address_country || $has_select_multiple || $has_datepicker || $has_timepicker || $has_uploader || $has_post_feat_image || ( $has_post_categories && $has_multi_categories ) || ( $has_post_tags && $has_multi_tags ) || $has_currency || $has_paypal || $has_stripe || $has_signature || $has_dateselect || $has_select_single || $has_timepicker ) {
				Forminator_Assets_Enqueue::fui_enqueue_style(
					'forminator-forms-' . $form_design . '-full',
					forminator_plugin_url() . 'assets/forminator-ui/css/src/form/forminator-form-' . $form_design . '.full.min.css',
					array(),
					FORMINATOR_VERSION
				);
			}

			// Forminator UI - Pagination stylesheet.
			if ( $render_obj->has_field_type( 'page-break' ) ) {

				Forminator_Assets_Enqueue::fui_enqueue_style(
					'forminator-forms-' . $form_design . '-pagination',
					forminator_plugin_url() . 'assets/forminator-ui/css/src/form/forminator-form-' . $form_design . '.pagination.min.css',
					array(),
					FORMINATOR_VERSION
				);
			}
		}

		// Forminator UI - Authentication stylesheet.
		if ( $has_password ) {

			Forminator_Assets_Enqueue::fui_enqueue_style(
				'forminator-authentication',
				forminator_plugin_url() . 'assets/forminator-ui/css/src/form/forminator-authentication.min.css',
				array(),
				FORMINATOR_VERSION
			);
		}
	}

	/**
	 * Load slider field scripts
	 */
	public static function load_slider_scripts() {
		wp_enqueue_script( 'jquery-touch-punch' );
		wp_enqueue_script( 'jquery-ui-slider' );
	}

	/**
	 * Load date field scripts
	 *
	 * @since 1.11
	 */
	public function load_date_scripts() {
		wp_enqueue_script( 'moment' );

		// load date picker scripts always.
		wp_enqueue_script( 'jquery-ui-datepicker' );
		wp_localize_script( 'jquery-ui-datepicker', 'datepickerLang', self::get_datepicker_data() );
	}

	/**
	 * Get date-picker data for using in JS
	 *
	 * @return array
	 */
	private static function get_datepicker_data() {
		global $wp_locale;

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
				'y',            // Year.
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

		$datepicker_data = array(
			'monthNames'      => array_values( $wp_locale->month ),
			'monthNamesShort' => array_values( $wp_locale->month_abbrev ),
			'dayNames'        => array_values( $wp_locale->weekday ),
			'dayNamesShort'   => array_values( $wp_locale->weekday_abbrev ),
			'dayNamesMin'     => array_values( $wp_locale->weekday_initial ),
			'dateFormat'      => $datepicker_date_format,
			'firstDay'        => absint( get_option( 'start_of_week' ) ),
			'isRTL'           => $wp_locale->is_rtl(),
		);

		return $datepicker_data;
	}

	/**
	 * Load phone field scripts conditionally
	 *
	 * @since 1.11
	 */
	private function load_phone_scripts() {

		// Load int-tels.
		$style_src     = forminator_plugin_url() . 'assets/css/intlTelInput.min.css';
		$style_version = '4.0.3';

		$script_src     = forminator_plugin_url() . 'assets/js/library/intlTelInput.min.js';
		$script_version = FORMINATOR_VERSION;

		Forminator_Assets_Enqueue::fui_enqueue_style( 'intlTelInput-forminator-css', $style_src, array(), $style_version ); // intlTelInput.
		wp_enqueue_script( 'forminator-intlTelInput', $script_src, array( 'jquery' ), $script_version, false ); // intlTelInput.
	}

	/**
	 * Load DOMPurify script for HTML field
	 *
	 * @return void
	 */
	public static function load_dompurify_scripts() {
		$script_src     = forminator_plugin_url() . 'assets/js/library/dompurify.min.js';
		$script_version = '3.3.1';

		wp_enqueue_script( 'forminator-dompurify', $script_src, array(), $script_version, false );
	}

	/**
	 * Load calculation,currency and number field scripts mask
	 *
	 * @since 1.11
	 */
	private function load_number_scripts() {
		$script_version = FORMINATOR_VERSION;

		wp_enqueue_script(
			'forminator-inputmask',
			forminator_plugin_url() . 'assets/js/library/inputmask.min.js',
			array( 'jquery' ),
			$script_version,
			false
		); // inputmask.
		wp_enqueue_script(
			'forminator-jquery-inputmask',
			forminator_plugin_url() . 'assets/js/library/jquery.inputmask.min.js',
			array( 'jquery' ),
			$script_version,
			false
		); // jquery inputmask.
		wp_enqueue_script(
			'forminator-inputmask-binding',
			forminator_plugin_url() . 'assets/js/library/inputmask.binding.js',
			array( 'jquery' ),
			$script_version,
			false
		); // inputmask binding.
	}

	/**
	 * Load styles and scripts for fields with select element.
	 *
	 * @param mixed $render_obj Render object.
	 *
	 * @since 1.14.10
	 */
	public function load_select_styles( $render_obj ) {
		$form_design = $this->get_module_design();
		if ( 'none' === $form_design ) {
			return;
		}

		$has_name_multi_select      = $render_obj->has_field_type_with_setting_value( 'name', 'multiple_name', 'true' );
		$has_name_prefix_select     = $render_obj->has_field_type_with_setting_value( 'name', 'prefix', true );
		$has_address_select         = $render_obj->has_field_type( 'address' );
		$has_address_country_select = $render_obj->has_field_type_with_setting_value( 'address', 'address_country', 'true' );
		$has_select                 = $render_obj->has_field_type( 'select' );
		$has_select_single          = $render_obj->has_field_type_with_setting_value( 'select', 'value_type', 'single' );
		$has_select_modern          = $render_obj->has_field_type_with_setting_value( 'select', 'multiselect_style', 'modern' );
		$has_date                   = $render_obj->has_field_type( 'date' );
		$has_date_select            = $render_obj->has_field_type_with_setting_value( 'date', 'field_type', 'select' );
		$has_timepicker             = $render_obj->has_field_type( 'time' );
		$has_time_input             = $render_obj->has_field_type_with_setting_value( 'time', 'time_type', 'twelve' );
		$has_time_select            = $render_obj->has_field_type_with_setting_value( 'time', 'field_type', 'select' );
		$has_post_categories        = $render_obj->has_field_type( 'postdata' );

		$has_element =
			( $has_name_multi_select && $has_name_prefix_select )
			|| ( $has_address_select && $has_address_country_select )
			|| ( $has_select && ( $has_select_single || $has_select_modern ) )
			|| ( $has_date && $has_date_select )
			|| ( $has_timepicker && ( $has_time_input || $has_time_select ) )
			|| $has_post_categories;

		if ( $has_element ) {
			// Load Forminator styles for select2.
			Forminator_Assets_Enqueue::fui_enqueue_style(
				'forminator-forms-' . $form_design . '-select2',
				forminator_plugin_url() . 'assets/forminator-ui/css/src/form/forminator-form-' . $form_design . '.select2.min.css',
				array(),
				FORMINATOR_VERSION
			);
		}
	}

	/**
	 * Load select scripts.
	 *
	 * @param mixed $render_obj Render object.
	 */
	public function load_select_scripts( $render_obj ) {
		$form_design = $this->get_module_design();
		if ( 'none' === $form_design ) {
			return;
		}

		$has_name_multi_select      = $render_obj->has_field_type_with_setting_value( 'name', 'multiple_name', 'true' );
		$has_name_prefix_select     = $render_obj->has_field_type_with_setting_value( 'name', 'prefix', true );
		$has_address_select         = $render_obj->has_field_type( 'address' );
		$has_address_country_select = $render_obj->has_field_type_with_setting_value( 'address', 'address_country', 'true' );
		$has_select                 = $render_obj->has_field_type( 'select' );
		$has_select_single          = $render_obj->has_field_type_with_setting_value( 'select', 'value_type', 'single' );
		$has_select_modern          = $render_obj->has_field_type_with_setting_value( 'select', 'multiselect_style', 'modern' );
		$has_date                   = $render_obj->has_field_type( 'date' );
		$has_date_select            = $render_obj->has_field_type_with_setting_value( 'date', 'field_type', 'select' );
		$has_timepicker             = $render_obj->has_field_type( 'time' );
		$has_time_input             = $render_obj->has_field_type_with_setting_value( 'time', 'time_type', 'twelve' );
		$has_time_select            = $render_obj->has_field_type_with_setting_value( 'time', 'field_type', 'select' );
		$has_post_categories        = $render_obj->has_field_type( 'postdata' );

		$has_element =
			( $has_name_multi_select && $has_name_prefix_select )
			|| ( $has_address_select && $has_address_country_select )
			|| ( $has_select && ( $has_select_single || $has_select_modern ) )
			|| ( $has_date && $has_date_select )
			|| ( $has_timepicker && ( $has_time_input || $has_time_select ) )
			|| $has_post_categories;

		if ( $has_element ) {
			wp_enqueue_script(
				'forminator-select2',
				forminator_plugin_url() . 'assets/forminator-ui/js/select2.full.min.js',
				array( 'jquery' ),
				FORMINATOR_VERSION,
				false
			);
		}
	}
}
