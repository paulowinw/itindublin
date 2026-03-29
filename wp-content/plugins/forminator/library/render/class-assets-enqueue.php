<?php
/**
 * The Forminator_Assets_Enqueue class.
 *
 * @package Forminator
 */

/**
 * Class Forminator_Assets_Enqueue
 *
 * @since 1.11
 */
abstract class Forminator_Assets_Enqueue {
	/**
	 * Model data
	 *
	 * @var Forminator_Base_Form_Model
	 */
	public $model = null;

	/**
	 * Is form loaded with AJAX
	 *
	 * @var bool
	 */
	public $is_ajax_load = false;

	/**
	 * Forminator_Render_Form constructor.
	 *
	 * @param mixed $model Model.
	 * @param bool  $is_ajax_load Is ajax load.
	 *
	 * @since 1.11
	 */
	public function __construct( $model, $is_ajax_load ) {
		$this->model        = $model;
		$this->is_ajax_load = $is_ajax_load;
	}

	/**
	 * Return Form Design
	 *
	 * @since 1.0
	 * @return mixed|string
	 */
	public function get_module_design() {
		$form_settings  = $this->get_settings();
		$form_style     = $form_settings['form-style'] ?? 'default';
		$form_sub_style = $form_settings['form-substyle'] ?? 'default';

		return 'default' === $form_style ?
			$form_sub_style : $form_style;
	}

	/**
	 * Get CSS upload.
	 *
	 * @param int    $id Id.
	 * @param string $type Type.
	 * @param bool   $maybe_create Create CSS file if it does not exist.
	 * @return string
	 */
	public static function get_css_upload( $id, $type = 'url', $maybe_create = false ) {
		$filename = 'style-' . $id . '.css';
		$css_dir  = forminator_get_upload_path( $id, 'css' );
		$css_url  = forminator_get_upload_url( $id, 'css' );
		if ( ! is_dir( $css_dir ) ) {
			wp_mkdir_p( $css_dir );
		}
		if ( ! forminator_create_index_file_disabled() ) {
			// Create Index file.
			Forminator_Field::forminator_upload_index_file( $id, $css_dir );
		}

		$fullname = $css_dir . '/' . $filename;
		if ( ! forminator_disable_regenerate_css_on_form_load() && $maybe_create && ! file_exists( $fullname ) ) {
			Forminator_Render_Form::regenerate_css_file( $id );
		}

		if ( ! empty( $type ) && 'dir' === $type ) {
			$return = $fullname;
		} else {
			$return = $css_url . '/' . $filename;
		}

		return $return;
	}

	/**
	 * Return Form Settins
	 *
	 * @since 1.11
	 * @return mixed
	 */
	public function get_settings() {
		return $this->model->settings;
	}

	/**
	 * Enqueue module styles
	 *
	 * @since 1.11
	 */
	public function load_base_styles() {
		$this->load_module_css();

		// Forminator UI - Icons font.
		self::fui_enqueue_style(
			'forminator-icons',
			forminator_plugin_url() . 'assets/forminator-ui/css/forminator-icons.min.css',
			array(),
			FORMINATOR_VERSION
		);

		// Forminator UI - Utilities.
		self::fui_enqueue_style(
			'forminator-utilities',
			forminator_plugin_url() . 'assets/forminator-ui/css/src/forminator-utilities.min.css',
			array(),
			FORMINATOR_VERSION
		);
	}

	/**
	 * Load relevant module CSS
	 */
	protected function load_module_css() {
		if ( ! empty( $this->model->id ) && ! is_admin() ) {
			$id        = $this->model->id;
			$timestamp = ! empty( $this->model->raw->post_modified_gmt )
					? strtotime( $this->model->raw->post_modified_gmt )
					: wp_unique_id();

			// Module styles.
			wp_enqueue_style(
				'forminator-module-css-' . $id,
				self::get_css_upload( $id, 'url', true ),
				array(),
				$timestamp
			);
		}
	}

	/**
	 * Load base scripts
	 *
	 * @since 1.11
	 */
	public function load_base_scripts() {
		// LOAD: Forminator validation scripts.
		wp_enqueue_script( 'forminator-jquery-validate', forminator_plugin_url() . 'assets/js/library/jquery.validate.min.js', array( 'jquery' ), FORMINATOR_VERSION, false );

		$slug = 'quiz' !== static::$module_slug ? static::$module_slug : 'ui';
		// LOAD: Forminator UI JS.
		wp_enqueue_script(
			'forminator-' . $slug,
			forminator_plugin_url() . 'assets/forminator-ui/js/forminator-' . $slug . '.min.js',
			array( 'jquery' ),
			FORMINATOR_VERSION,
			false
		);

		// LOAD: Forminator front scripts.
		wp_enqueue_script(
			'forminator-front-scripts',
			forminator_plugin_url() . 'build/front/front.multi.min.js',
			array( 'jquery', 'forminator-' . $slug, 'forminator-jquery-validate' ),
			FORMINATOR_VERSION,
			false
		);

		// Localize front script.
		wp_localize_script( 'forminator-front-scripts', 'ForminatorFront', forminator_localize_data() );
	}

	/**
	 * Detect Divi Builder Activation or Preview Mode
	 *
	 * This utility function checks if the Divi Builder is actively used on the current page
	 * or if it is in preview mode. It determines this through the following conditions:
	 *
	 * 1. **Divi Preview Mode Check:** If Divi is in preview mode (detected via query parameters),
	 *    the function confirms this by validating the `et_pb_preview_nonce` nonce. If verified,
	 *    the function immediately returns `true`, signaling that Divi preview mode is active.
	 *
	 * 2. **Divi Theme Check:** If Divi preview mode is not active, the function checks if the
	 *    Divi theme is the currently active theme. If Divi is active as the main theme, the
	 *    function returns `false`, as there is no need to load builder-specific assets outside
	 *    of preview mode.
	 *
	 * 3. **Divi Builder Plugin Check:** If the Divi theme is not active, the function then checks
	 *    if the Divi Builder plugin is active by looking for the `ET_CORE_VERSION` constant and
	 *    the `_et_pb_use_builder` meta key set to "on" in the current post's metadata. If both
	 *    conditions are met, the function returns `true`, indicating that the Divi Builder plugin
	 *    is active on this page.
	 *
	 * @since 1.37
	 *
	 * @return true|false
	 */
	public static function is_divi_active_or_preview() {
		// Check if Divi is in preview mode.
		$is_builder_preview = isset( $_GET['et_pb_preview'] ) && isset( $_GET['et_pb_preview_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['et_pb_preview_nonce'] ) ), 'et_pb_preview_nonce' );

		if ( $is_builder_preview ) {
			return true;
		}

		// Check if Divi theme is active.
		$theme    = wp_get_theme();
		$template = strtolower( $theme->get_template() );

		if ( $theme->exists() && ( 'divi' === $template || 'extra' === $template ) ) {
			return false;
		}

		// Check if Divi builder is active on a page without the Divi theme.
		return defined( 'ET_CORE_VERSION' ) && ( 'on' === get_post_meta( get_the_ID(), '_et_pb_use_builder', true ) );
	}

	/**
	 * Load styles based on builder.
	 *
	 * This function conditionally loads a specific stylesheet depending on whether
	 * the Divi builder is installed and activated. If Divi is detected, the function
	 * modifies the file path to load the Divi-specific stylesheet.
	 *
	 * @since 1.36
	 *
	 * @param string $name      The name of the stylesheet.
	 * @param string $file_path The path to the stylesheet.
	 * @param array  $deps      An array of dependencies for the stylesheet. Defaults to an empty array.
	 * @param string $version   The version number for the stylesheet. Defaults to FORMINATOR_VERSION.
	 *
	 * @return true|WP_Error True on success, WP_Error on failure.
	 */
	public static function fui_enqueue_style( $name, $file_path, $deps = array(), $version = FORMINATOR_VERSION ) {
		// Check for empty parameters.
		if ( empty( $name ) || empty( $file_path ) || ! is_array( $deps ) || empty( $version ) ) {
			return new WP_Error( 'Please pass all the parameters.' );
		}

		// Default to the original file path.
		$modified_path = $file_path;

		// Use the Divi detection function.
		if ( self::is_divi_active_or_preview() ) {
			$modified_path = str_replace( '.min.css', '.builder_divi.min.css', $file_path );
		}

		$modified_path = apply_filters( 'forminator_modified_path_for_fui_enqueue_style', $modified_path, $name, $file_path, $version );

		// Enqueued style.
		wp_enqueue_style(
			$name,
			$modified_path,
			$deps,
			$version
		);

		return true;
	}
}
