<?php
/**
 * The Forminator_Integration class.
 *
 * @package Forminator
 */

/**
 * Class Forminator_Integration
 * Extend this class to create new forminator addon / integrations
 * Any change(s) to this file is subject to:
 * - Properly Written DocBlock! (what is this, why is that, how to be like those, etc, as long as you want!)
 * - Properly Written Changelog!
 * - Properly Written Sample Usage on @see Forminator_Integration_Simple
 *
 * @since 1.1
 */
abstract class Forminator_Integration implements Forminator_Integration_Interface {
	/**
	 * Multi Id
	 *
	 * @var mixed
	 */
	public $multi_id;

	/**
	 * Slug will be used as identifier throughout forminator
	 * make sure its unique, else it won't be loaded
	 * or will carelessly override other addon with same slug
	 *
	 * @since 1.1
	 * @var string
	 */
	protected $_slug;

	/**
	 * Version number of the Add-On
	 * It will save on the wp options
	 * And if user updated the addon, it will try to call @see Forminator_Integration::version_changed()
	 *
	 * @since 1.1
	 * @var string
	 */
	protected $_version;

	/**
	 * Minimum version of Forminator, that the addon will work correctly
	 *
	 * @since 1.1
	 * @var string
	 */
	protected $_min_forminator_version;

	/**
	 * URL info to of the Integration website / doc / info
	 *
	 * @since  1.1
	 * @var string
	 */
	protected $_url = 'http://wpmudev.com';

	/**
	 * Title of the addon will be used on add on list and add on setting
	 *
	 * @since  1.1
	 * @var string
	 */
	protected $_title;

	/**
	 * Short version of the addon title, will be used at small places for the addon to be displayed
	 * its optional, when its omitted it will use $_title
	 * make sure its less then 10 chars to displayed correctly, we will auto truncate it if its more than 10 chars
	 *
	 * @since  1.1
	 * @var string
	 */
	protected $_short_title;

	/**
	 * Integration Brief Desription, of what it does
	 *
	 * @since  1.1
	 * @var string
	 */
	protected $_description = '';

	/**
	 * Integration promotion description
	 *
	 * @since  1.7.1
	 * @var string
	 */
	protected $_promotion = '';

	/**
	 * Integration documentation link
	 *
	 * @since  1.7.1
	 * @var string
	 */
	protected $_documentation = '';

	/**
	 * Flag that an addon can be activated, that auto set by abstract
	 *
	 * @since  1.1
	 * @var bool
	 */
	private $is_activable = null;

	/**
	 * Semaphore non redundant hooks for admin side
	 *
	 * @since  1.1
	 * @var bool
	 */
	private $_is_admin_hooked = false;

	/**
	 * Semaphore non redundant hooks for global hooks
	 *
	 * @since  1.1
	 * @var bool
	 */
	private $_is_global_hooked = false;

	/**
	 * Add-on order position
	 *
	 * @since 1.7.1
	 * @var int
	 */
	protected $_position = 1;


	/*********************************** Errors Messages ********************************/
	/**
	 * These error message can be set on the start of addon as default, or dynamically set on each related process
	 *
	 * @example $_activation_error_message can be dynamically set on activate() to display custom error messages when activatation failed
	 *          Default is empty, which will be replaced by forminator default messages
	 */
	/**
	 * Error Message on activation
	 *
	 * @since  1.1
	 * @var string
	 */
	protected $_activation_error_message = '';

	/**
	 * Error Message on deactivation
	 *
	 * @since  1.1
	 * @var string
	 */
	protected $_deactivation_error_message = '';

	/**
	 * Error Message on update general settings
	 *
	 * @since  1.1
	 * @var string
	 */
	protected $_update_settings_error_message = '';
	/*********************************** END Errors Messages ********************************/

	/**
	 * Form Hooks Instances with `module_id` as key
	 *
	 * @since  1.1
	 * @var Forminator_Integration_Hooks[]|array
	 */
	protected $addon_hooks_instances = array();

	/**
	 * Id of multiple provider accounts
	 *
	 * @var string
	 */
	public $multi_global_id;

	/**
	 * Global Id for new integrations
	 *
	 * @var string
	 */
	public $global_id_for_new_integrations;

	/**
	 * Support multiple accounts
	 *
	 * @var bool
	 */
	public $is_multi_global = false;

	/**
	 * Wizard steps
	 *
	 * @var array
	 */
	private $steps = array();

	/**
	 * Sensitive key names that require encryption.
	 *
	 * @var array
	 */
	protected $_sensitive_keys = array();

	/**
	 * Nonce option name
	 *
	 * @var string
	 */
	const NONCE_OPTION_NAME = 'forminator_custom_nonce';

	const DOMAIN       = 'https://wpmudev.com';
	const REDIRECT_URI = 'https://wpmudev.com/api/forminator/v1/provider';


	/**
	 * Get addon instance
	 *
	 * @return self
	 */
	public static function get_instance() {
		if ( is_null( static::$instance ) ) {
			static::$instance = new static();
		}

		return static::$instance;
	}

	/**
	 * Get this addon slug
	 *
	 * @see    Forminator_Integration::$_slug
	 *
	 * its behave like `IDENTIFIER`, used for :
	 * - easly calling this instance with @see forminator_get_addon(`slug`)
	 * - avoid collision, registered as FIFO of @see do_action()
	 *
	 * Shouldn't be implemented / overridden on addons
	 *
	 * @since  1.1
	 * @return string
	 */
	final public function get_slug() {
		return $this->_slug;
	}

	/**
	 * Get this addon version
	 *
	 * @since  1.1
	 * @return string
	 */
	final public function get_version() {
		return $this->_version;
	}

	/**
	 * Get this addon requirement of installed forminator version
	 *
	 * @since  1.1
	 * @return string
	 */
	final public function get_min_forminator_version() {
		return $this->_min_forminator_version;
	}

	/**
	 * Get external url of addon website / info / doc
	 *
	 * Can be overridden to offer dynamic external url display
	 *
	 * @since  1.1
	 * @return string
	 */
	public function get_url() {
		return $this->_url;
	}

	/**
	 * Get redirect URL
	 *
	 * @param string $provider Provider.
	 * @param string $action Action.
	 * @param array  $params Params.
	 * @return string
	 */
	public static function redirect_uri( $provider, $action, $params ) {
		$params = wp_parse_args(
			$params,
			array(
				'action'   => $action,
				'provider' => $provider,
			)
		);

		return add_query_arg( $params, self::REDIRECT_URI );
	}

	/**
	 * Validates request callback from WPMU DEV
	 *
	 * @param string $provider Provider.
	 * @return bool
	 */
	public static function validate_callback_request( $provider ) {
		$wpnonce        = filter_input( INPUT_GET, 'wpnonce', FILTER_SANITIZE_SPECIAL_CHARS );
		$domain         = filter_input( INPUT_GET, 'domain', FILTER_VALIDATE_URL );
		$provider_input = filter_input( INPUT_GET, 'provider', FILTER_SANITIZE_SPECIAL_CHARS );

		return ! empty( $wpnonce ) && self::verify_nonce( $wpnonce )
			&& self::DOMAIN === $domain && $provider === $provider_input;
	}

	/**
	 * Helper function to validate nonce value.
	 *
	 * @param string $nonce Nonce.
	 *
	 * @return bool
	 */
	private static function verify_nonce( $nonce ) {
		return self::get_nonce_value() === $nonce;
	}

	/**
	 * Helper function to generate unique none changeable nonce.
	 *
	 * @return string The unique nonce value.
	 */
	public static function get_nonce_value() {
		$nonce = get_option( self::NONCE_OPTION_NAME );

		if ( empty( $nonce ) ) {
			/**
			 * Generate the nonce value only once to avoid error response
			 * when retrieving access token.
			 */
			$nonce = wp_generate_password( 40, false, false );

			update_option( self::NONCE_OPTION_NAME, $nonce );
		}

		return $nonce;
	}

	/**
	 * Get external title of addon
	 *
	 * @since  1.1
	 * @return string
	 */
	final public function get_title() {
		return $this->_title;
	}


	/**
	 * Get short title for small width placeholder
	 *
	 * @since  1.1
	 * @return string
	 */
	final public function get_short_title() {
		if ( empty( $this->_short_title ) ) {
			$this->_short_title = $this->_title;
		}

		return substr( $this->_short_title, 0, self::SHORT_TITLE_MAX_LENGTH );
	}

	/**
	 * Get Image
	 *
	 * @since  1.1
	 * @return string
	 */
	public function get_image() {
		return $this->assets_path() . 'image.png';
	}

	/**
	 * Get Retina image
	 *
	 * @since  1.1
	 * @return string
	 */
	public function get_image_x2() {
		return $this->assets_path() . 'image@2x.png';
	}

	/**
	 * Get banner image
	 *
	 * @since  1.7.1
	 * @return string
	 */
	public function get_banner() {
		return $this->assets_path() . 'banner.png';
	}

	/**
	 * Get retina banner image
	 *
	 * @since  1.7.1
	 * @return string
	 */
	public function get_banner_x2() {
		return $this->assets_path() . 'banner@2x.png';
	}

	/**
	 * Get icon
	 *
	 * @since  1.1
	 * @return string
	 */
	public function get_icon() {
		return $this->assets_path() . 'icon.png';
	}

	/**
	 * Get Retina icon
	 *
	 * @since  1.1
	 * @return string
	 */
	public function get_icon_x2() {
		return $this->assets_path() . 'icon@2x.png';
	}

	/**
	 * Get path to assets folder
	 *
	 * @return string
	 */
	public function assets_path(): string {
		return $this->addon_path() . 'assets/';
	}
	/**
	 * Get path to assets folder
	 *
	 * @return string
	 */
	public function addon_path(): string {
		return trailingslashit( forminator_plugin_url() . 'addons/pro/' . $this->get_slug() );
	}

	/**
	 * Get Description
	 *
	 * @since  1.1
	 * @return string
	 */
	public function get_description() {
		return $this->_description;
	}

	/**
	 * Get promotion
	 *
	 * @since  1.7.1
	 * @return string
	 */
	public function get_promotion() {
		return __( $this->_promotion, 'forminator' ); // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText
	}

	/**
	 * Get documentation link
	 *
	 * @since  1.7.1
	 * @return string
	 */
	public function get_documentation() {
		return $this->_documentation;
	}

	/**
	 * Get add-on position
	 *
	 * @since 1.7.1
	 * @return int
	 */
	public function get_position() {
		return $this->_position;
	}

	/**
	 * WP options name that holds settings of addon
	 *
	 * @since  1.1
	 * @return string
	 */
	final public function get_settings_options_name() {
		$addon_slug            = $this->get_slug();
		$addon                 = $this;
		$settings_options_name = 'forminator_addon_' . $this->get_slug() . '_settings';

		/**
		 * Filter wp options name for saving addon settings
		 *
		 * @since 1.1
		 *
		 * @param string                    $settings_options_name
		 * @param Forminator_Integration $addon Integration instance.
		 */
		$settings_options_name = apply_filters( 'forminator_addon_' . $addon_slug . '_settings_options_name', $settings_options_name, $addon );

		return $settings_options_name;
	}

	/**
	 * WP options name that holds current version of addon
	 *
	 * @since  1.1
	 * @return string
	 */
	final public function get_version_options_name() {
		$addon_slug           = $this->get_slug();
		$addon                = $this;
		$version_options_name = 'forminator_addon_' . $this->get_slug() . '_version';

		/**
		 * Filter wp options name for saving addon version
		 *
		 * @since 1.1
		 *
		 * @param string                    $version_options_name
		 * @param Forminator_Integration $addon Integration instance.
		 */
		$version_options_name = apply_filters( 'forminator_addon_' . $addon_slug . '_version_options_name', $version_options_name, $addon );

		return $version_options_name;
	}

	/**
	 * Get multi global ids with identifiers.
	 *
	 * @return array
	 */
	final public function get_multi_global_ids() {
		$all_settings     = $this->get_all_settings_values();
		$multi_global_ids = array();
		foreach ( $all_settings as $key => $settings ) {
			$multi_global_ids[ $key ] = ! empty( $settings['identifier'] ) ? $settings['identifier'] : '';
		}

		return $multi_global_ids;
	}

	/**
	 * Transform addon instance into array
	 *
	 * @since  1.1
	 * @return array
	 */
	final public function to_array() {
		$to_array = array(
			'slug'                   => $this->get_slug(),
			'is_pro'                 => $this->is_pro(),
			'icon'                   => $this->get_icon(),
			'icon_x2'                => $this->get_icon_x2(),
			'image'                  => $this->get_image(),
			'image_x2'               => $this->get_image_x2(),
			'banner'                 => $this->get_banner(),
			'banner_x2'              => $this->get_banner_x2(),
			'short_title'            => $this->get_short_title(),
			'title'                  => $this->get_title(),
			'url'                    => $this->get_url(),
			'description'            => $this->get_description(),
			'promotion'              => $this->get_promotion(),
			'documentation'          => $this->get_documentation(),
			'version'                => $this->get_version(),
			'min_forminator_version' => $this->get_min_forminator_version(),
			'setting_options_name'   => $this->get_settings_options_name(),
			'version_option_name'    => $this->get_version_options_name(),
			'is_activable'           => $this->is_activable(),
			'is_settings_available'  => $this->is_settings_available(),
			'is_connected'           => $this->is_connected(),
			'is_multi_global'        => $this->is_multi_global,
			'new_global_id'          => $this->global_id_for_new_integrations,
			'position'               => $this->get_position(),
		);

		$addon_slug = $this->get_slug();
		$addon      = $this;

		/**
		 * Filter array of addon properties
		 *
		 * @since 1.1
		 *
		 * @param array                     $to_array array of addonn properties.
		 * @param int                       $form_id  Form ID.
		 * @param Forminator_Integration $addon    Integration Instance.
		 */
		$to_array = apply_filters( 'forminator_addon_' . $addon_slug . '_to_array', $to_array, $addon );

		return $to_array;
	}

	/**
	 * Transform addon instance into array with form relation
	 *
	 * @since  1.1
	 * @since  1.2 generate new multi_id to allow reference on wizard
	 *
	 * @param int $form_id Form Id.
	 *
	 * @return array
	 */
	final public function to_array_with_form( $form_id ) {
		$to_array                               = $this->to_array();
		$is_allow_multi_on_form                 = $this->is_allow_multi_on_form();
		$to_array['is_form_connected']          = $this->is_module_connected( $form_id );
		$to_array['is_form_settings_available'] = $this->is_form_settings_available( $form_id );
		$to_array['is_allow_multi_on_form']     = $is_allow_multi_on_form;

		$to_array['multi_id'] = $this->generate_form_settings_multi_id( $form_id );

		// handle multiple form setting.
		if ( $is_allow_multi_on_form ) {
			$to_array['multi_ids'] = $this->get_form_settings_multi_ids( $form_id );
		}

		$to_array_with_form = $to_array;
		$addon_slug         = $this->get_slug();
		$addon              = $this;

		/**
		 * Filter array of addon properties
		 *
		 * Including relation with form_id
		 *
		 * @since 1.1
		 *
		 * @param array                     $to_array_with_form array of addonn properties.
		 * @param int                       $form_id            Form ID.
		 * @param Forminator_Integration $addon              Integration instance.
		 */
		$to_array_with_form = apply_filters( 'forminator_addon_' . $addon_slug . '_to_array_with_form', $to_array_with_form, $form_id, $addon );

		return $to_array_with_form;
	}


	/**
	 * Check if Plugin Is Pro
	 *
	 * @see    forminator_get_pro_addon_list()
	 * @since  1.1
	 * @return bool
	 */
	final public function is_pro() {
		if ( in_array( $this->_slug, array_keys( forminator_get_pro_addon_list() ), true ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Get activable status
	 *
	 * @since  1.1
	 * @return bool
	 */
	final public function is_activable() {
		if ( is_null( $this->is_activable ) ) {
			$this->is_activable = $this->check_is_activable();
		}

		return $this->is_activable;
	}

	/**
	 * Actually check requirement of an addon that can be activated
	 * Override this method if you have another logic for checking activable_plugins
	 *
	 * @since  1.1
	 * @return bool
	 */
	public function check_is_activable() {
		// Check supported forminator version.
		if ( empty( $this->_min_forminator_version ) ) {
			forminator_addon_maybe_log( __METHOD__, $this->get_slug(), 'empty _min_forminator_version' );

			return false;
		}

		$is_forminator_version_supported = version_compare( FORMINATOR_VERSION, $this->_min_forminator_version . '-alpha', '>=' );
		if ( ! $is_forminator_version_supported ) {
			forminator_addon_maybe_log( __METHOD__, $this->get_slug(), $this->_min_forminator_version, FORMINATOR_VERSION, 'Forminator Version not supported' );

			// un-strict version compare of forminator, override if needed.
			return true;
		}

		return true;
	}

	/**
	 * Override or implement this method to add action when user deactivate addon
	 *
	 * @example DROP table
	 * return true when succes
	 * return false on failure, forminator will stop deactivate process
	 *
	 * @since   1.1
	 * @return bool
	 */
	public function deactivate() {
		return true;
	}


	/**
	 * Override or implement this method to add action when user activate addon
	 *
	 * @example CREATE table
	 * return true when succes
	 * return false on failure, forminator will stop activation process
	 *
	 * @since   1.1
	 * @return bool
	 */
	public function activate() {
		return true;
	}

	/**
	 * Override or implement this method to add action when version of addon changed
	 *
	 * @example CREATE table
	 * return true when succes
	 * return false on failure, forminator will stop activation process
	 *
	 * @since   1.1
	 *
	 * @param string $old_version Old version.
	 * @param string $new_version New version.
	 *
	 * @return bool
	 */
	public function version_changed( $old_version, $new_version ) {
		return true;
	}

	/**
	 * Check if addon version has changed
	 *
	 * @since   1.1
	 * @return bool
	 */
	final public function is_version_changed() {
		$installed_version = $this->get_installed_version();
		// new installed.
		if ( false === $installed_version ) {
			return false;
		}
		$version_is_changed = version_compare( $this->_version, $installed_version, '!=' );
		if ( $version_is_changed ) {
			return true;
		}

		return false;
	}

	/**
	 * Get currently installed addon version
	 * retrieved from wp options
	 *
	 * @since 1.1
	 * @return string|bool
	 */
	final public function get_installed_version() {
		return get_option( $this->get_version_options_name(), false );
	}

	/**
	 * Get error message on activation
	 *
	 * @since 1.1
	 * @return string
	 */
	public function get_activation_error_message() {
		if ( ! empty( $this->_activation_error_message ) ) {
			return $this->_activation_error_message;
		}

		/* translators: integration title */
		return sprintf( esc_html__( 'Sorry but we failed to activate %s Integration, don\'t hesitate to contact us', 'forminator' ), $this->get_title() );
	}

	/**
	 * Get error message on deactivation
	 *
	 * @since 1.1
	 * @return string
	 */
	public function get_deactivation_error_message() {
		if ( ! empty( $this->_deactivation_error_message ) ) {
			return $this->_deactivation_error_message;
		}

		/* translators: integration title */
		return sprintf( esc_html__( 'Sorry but we failed to deactivate %s Integration, please try again', 'forminator' ), $this->get_title() );
	}

	/**
	 * Get error message on deactivation
	 *
	 * @since 1.1
	 * @return string
	 */
	public function get_update_settings_error_message() {
		if ( ! empty( $this->_update_settings_error_message ) ) {
			return $this->_update_settings_error_message;
		}

		return esc_html__( 'Sorry, we failed to update settings, please check your form and try again', 'forminator' );
	}


	/**
	 * Override this function to set wizardable settings
	 * Default its and empty array which is indicating that Integration doesnt have settings
	 *
	 * Its multi array, with numerical key, start with `0`
	 * Every step on wizard, will consist at least
	 * - `callback` : when application requesting wizard, Forminator will do `call_user_func` on this value, with these arguments
	 *      - `$submitted_data` : array of submitted data POST-ed by user
	 *      - `$form_id` : current form_id when called on `Form Settings` or 0 when called on Global Settings
	 * - `is_completed` : when application requesting wizard, will check if `Previous Step` `is_completed` by doing `call_user_func` on its value
	 *      this function should return `true` or `false`
	 *
	 * @since 1.1
	 * @return array
	 */
	public function settings_wizards() {
		// What this function return should looks like.
		$steps = array(
			// First Step / step `0`.
			array(
				/**
				 * Value of `callback` will be passed as first argument of `call_user_func`
				 * it does not have to be passed `$this` as reference such as `array( $this, 'sample_setting_first_step' )`,
				 * But its encouraged to passed `$this` because you will be benefited with $this class instance, in case you need to call private function or variable inside it
				 * you can make the value to be `some_function_name` as long `some_function_name` as long it will globally callable which will be checked with `is_callable`
				 * and should be able to accept 2 arguments $submitted_data, $form_id
				 *
				 * This callback should return an array @see Forminator_Integration::sample_setting_first_step()
				 *
				 * @see Forminator_Integration::sample_setting_first_step()
				 */
				'callback'     => array( $this, 'sample_setting_first_step' ),
				/**
				 * Before Forminator call the `calback`, Forminator will attempt to run `is_completed` from the previous step
				 * In this case, `is_completed` will be called when Forminator trying to display Settings Wizard for Second Step / step `1`
				 * Like `callback` its value will be passed as first argument of `call_user_func`
				 * and no arguments passed to this function when its called
				 *
				 * @see Forminator_Integration::sample_setting_first_step_is_completed()
				 */
				'is_completed' => array( $this, 'sample_setting_first_step_is_completed' ),
			),
		);

		return array();
	}

	/**
	 * Get Global Setting Wizard
	 * This function will process @see Forminator_Integration::settings_wizards()
	 * Please keep in mind this function will only be called when @see Forminator_Integration::is_settings_available() return `true`
	 * Which is doing check on @see Forminator_Integration::settings_wizards() requirements is passed
	 *
	 * @since 1.1
	 *
	 * @param array $submitted_data Submitted data.
	 * @param int   $form_id Form Id.
	 * @param int   $current_step Current step.
	 * @param int   $step Step.
	 *
	 * @return array|mixed
	 */
	final public function get_settings_wizard( $submitted_data, $form_id = 0, $current_step = 0, $step = 0 ) {

		$steps = $this->settings_wizards();
		if ( ! is_array( $steps ) ) {
			/* translators: integration title */
			return $this->get_empty_wizard( sprintf( esc_html__( 'No Settings available for %1$s', 'forminator' ), $this->get_title() ) );
		}
		$total_steps = count( $steps );
		if ( $total_steps < 1 ) {
			/* translators: integration title */
			return $this->get_empty_wizard( sprintf( esc_html__( 'No Settings available for %1$s', 'forminator' ), $this->get_title() ) );
		}

		if ( ! isset( $steps[ $step ] ) ) {
			// go to last step.
			$step = $total_steps - 1;

			return $this->get_settings_wizard( $submitted_data, $form_id, $current_step, $step );
		}

		if ( $step > 0 ) {
			if ( $current_step > 0 ) {
				// check previous step is complete.
				$prev_step              = $current_step - 1;
				$prev_step_is_completed = true;
				// only call `is_completed` when its defined.
				if ( isset( $steps[ $prev_step ]['is_completed'] ) && is_callable( $steps[ $prev_step ]['is_completed'] ) ) {
					$prev_step_is_completed = call_user_func( $steps[ $prev_step ]['is_completed'], $submitted_data );
				}
				if ( ! $prev_step_is_completed ) {
					--$step;

					return $this->get_settings_wizard( $submitted_data, $form_id, $current_step, $step );
				}
			}

			// only validation when it moves forward.
			if ( $step > $current_step ) {
				$current_step_result = $this->get_settings_wizard( $submitted_data, $form_id, $current_step, $current_step );
				if ( isset( $current_step_result['has_errors'] ) && true === $current_step_result['has_errors'] ) {
					return $current_step_result;
				} else {
					// set empty submitted data for next step.
					$submitted_data = array();
				}
			}
		}

		return $this->get_wizard( $steps, $submitted_data, $form_id, $step );
	}

	/**
	 * Get steps.
	 *
	 * @param int    $form_id Form id.
	 * @param string $module_type Module type.
	 * @return array
	 */
	final public function get_steps( $form_id, $module_type = 'form' ) {
		if ( ! empty( $this->steps[ $form_id ] ) ) {
			$steps = $this->steps[ $form_id ];
		} else {
			$settings_steps = array();
			if ( ! $this->is_connected() ) {
				$settings_steps = $this->settings_wizards();
			}
			$get_module_settings_steps = 'get_' . $module_type . '_settings_steps';

			$form_settings_steps = $this->$get_module_settings_steps( $form_id );

			$steps = array_merge( $settings_steps, $form_settings_steps );
		}

		return $steps;
	}

	/**
	 * Get Form Setting Wizard
	 * This function will process @see Forminator_Integration::settings_wizards()
	 * Please keep in mind this function will only be called when @see Forminator_Integration::is_settings_available() return `true`
	 * Which is doing check on @see Forminator_Integration::settings_wizards() requirements is passed
	 *
	 * @since 1.1
	 *
	 * @param array $submitted_data Submitted data.
	 * @param int   $form_id Form Id.
	 * @param int   $current_step Current step.
	 * @param int   $step Step.
	 *
	 * @return array|mixed
	 */
	final public function get_form_settings_wizard( $submitted_data, $form_id, $current_step = 0, $step = 0 ) {
		$steps = $this->get_steps( $form_id );
		if ( ! is_array( $steps ) ) {
			/* translators: integration title */
			return $this->get_empty_wizard( sprintf( esc_html__( 'No Form Settings available for %1$s', 'forminator' ), $this->get_title() ) );
		}
		$total_steps = count( $steps );
		if ( $total_steps < 1 ) {
			/* translators: integration title */
			return $this->get_empty_wizard( sprintf( esc_html__( 'No Form Settings available for %1$s', 'forminator' ), $this->get_title() ) );
		}

		if ( ! isset( $steps[ $step ] ) ) {
			// go to last step.
			$step = $total_steps - 1;

			return $this->get_form_settings_wizard( $submitted_data, $form_id, $current_step, $step );
		}

		if ( $step > 0 ) {
			if ( $current_step > 0 ) {
				// check previous step is complete.
				$prev_step              = $current_step - 1;
				$prev_step_is_completed = true;
				// only call `is_completed` when its defined.
				if ( isset( $steps[ $prev_step ]['is_completed'] ) && is_callable( $steps[ $prev_step ]['is_completed'] ) ) {
					$prev_step_is_completed = call_user_func( $steps[ $prev_step ]['is_completed'], $submitted_data );
				}
				if ( ! $prev_step_is_completed ) {
					--$step;

					return $this->get_form_settings_wizard( $submitted_data, $form_id, $current_step, $step );
				}
			}

			// only validation when it moves forward.
			if ( $step > $current_step ) {
				$current_step_result = $this->get_form_settings_wizard( $submitted_data, $form_id, $current_step, $current_step );
				if ( isset( $current_step_result['has_errors'] ) && true === $current_step_result['has_errors'] ) {
					return $current_step_result;
				} else {
					// set empty submitted data for next step, except preserved as reference.
					$preserved_keys = array(
						'multi_id',
					);
					foreach ( $submitted_data as $key => $value ) {
						if ( ! in_array( $key, $preserved_keys, true ) ) {
							unset( $submitted_data[ $key ] );
						}
					}
					// Reset steps cache - uses when wizard steps are conditional.
					unset( $this->steps[ $form_id ] );
					$steps = $this->get_steps( $form_id );
				}
			}
		}

		$form_settings_wizard = $this->get_wizard( $steps, $submitted_data, $form_id, $step );

		$addon_slug             = $this->get_slug();
		$addon                  = $this;
		$form_settings_instance = $this->get_addon_settings( $form_id, 'form' );

		/**
		 * Filter form settings wizard returned to client
		 *
		 * @since 1.1
		 *
		 * @param array                                        $form_settings_wizard
		 * @param array                                        $submitted_data         $_POST from client.
		 * @param int                                          $form_id                Form ID requested for.
		 * @param int                                          $current_step           Current Step displayed to user, start from 0.
		 * @param int                                          $step                   Step requested by client, start from 0.
		 * @param Forminator_Integration                    $addon                  Integration Instance.
		 * @param Forminator_Integration_Form_Settings|null $form_settings_instance Integration Form settings instancce, or null if unavailable.
		 */
		$form_settings_wizard = apply_filters(
			'forminator_addon_' . $addon_slug . '_form_settings_wizard',
			$form_settings_wizard,
			$submitted_data,
			$form_id,
			$current_step,
			$step,
			$addon,
			$form_settings_instance
		);

		return $form_settings_wizard;
	}

	/**
	 * Get form settings wizard steps
	 *
	 * @since 1.1
	 *
	 * @param int $form_id Form Id.
	 *
	 * @return array
	 */
	private function get_form_settings_steps( $form_id ) {
		$addon_slug             = $this->get_slug();
		$addon                  = $this;
		$form_settings_steps    = array();
		$form_settings_instance = $this->get_addon_settings( $form_id, 'form' );
		if ( ! is_null( $form_settings_instance ) && $form_settings_instance instanceof Forminator_Integration_Form_Settings ) {
			$form_settings_steps = $form_settings_instance->module_settings_wizards();
		}

		/**
		 * Filter form settings step that will be used for building wizard
		 *
		 * More detail : @param array $form_settings_steps
		 *
		 * @param int                                     $form_id current form id.
		 * @param Forminator_Integration_Form_Settings $addon   Integration instance.
		 * @param Forminator_Integration_Form_Settings|null Form settings of addon if available, or null otherwise
		 *@see Forminator_Integration_Form_Settings::module_settings_wizards()
		 *
		 * @since 1.1
		 */
		$form_settings_steps = apply_filters( 'forminator_addon_' . $addon_slug . '_form_settings_steps', $form_settings_steps, $form_id, $addon, $form_settings_instance );

		return $form_settings_steps;
	}

	/**
	 * Get settings multi id
	 *
	 * @since 1.1
	 *
	 * @param int $form_id Form Id.
	 *
	 * @return array
	 */
	private function get_form_settings_multi_ids( $form_id ) {
		$addon_slug             = $this->get_slug();
		$addon                  = $this;
		$multi_ids              = array();
		$form_settings_instance = $this->get_addon_settings( $form_id, 'form' );
		if ( $this->is_allow_multi_on_form() && ! is_null( $form_settings_instance ) && $form_settings_instance instanceof Forminator_Integration_Form_Settings ) {
			$multi_ids = $form_settings_instance->get_multi_ids();
		}

		/**
		 * Filter multi id of addon form settings
		 *
		 * @since 1.1
		 *
		 * @param array                                   $multi_ids
		 * @param Forminator_Integration_Form_Settings $addon                  Integration Instance.
		 * @param Forminator_Integration_Form_Settings $form_settings_instance Integration Form Settings Instance.
		 */
		$multi_ids = apply_filters( 'forminator_addon_' . $addon_slug . '_form_settings_multi_ids', $multi_ids, $addon, $form_settings_instance );

		return $multi_ids;
	}

	/**
	 * Get the requested wizard
	 *
	 * @since 1.1
	 * @since 1.2 Refactor setup default values, rename `hasBack` to `has_back`
	 *
	 * @param array $steps Steps.
	 * @param array $submitted_data Submitted data.
	 * @param int   $module_id Module id.
	 * @param int   $step Step.
	 *
	 * @return array|mixed
	 */
	private function get_wizard( $steps, $submitted_data, $module_id, $step = 0 ) {
		$total_steps = count( $steps );

		// validate callback, when it's empty or not callable, mark as no wizard.
		if ( ! isset( $steps[ $step ]['callback'] ) || ! is_callable( $steps[ $step ]['callback'] ) ) {
			/* translators: integration title */
			return $this->get_empty_wizard( sprintf( esc_html__( 'No Settings available for %1$s', 'forminator' ), $this->get_title() ) );
		}

		$wizard = call_user_func( $steps[ $step ]['callback'], $submitted_data, $module_id );
		// a wizard to be able to processed by our application need to has at least `html` which will be rendered or `redirect` which will be the url for redirect user to go to.
		if ( ! isset( $wizard['html'] ) && ! isset( $wizard['redirect'] ) ) {
			/* translators: integration title */
			return $this->get_empty_wizard( sprintf( esc_html__( 'No Settings available for %1$s', 'forminator' ), $this->get_title() ) );
		}
		$wizard['forminator_addon_current_step']  = $step;
		$wizard['forminator_addon_count_step']    = $total_steps;
		$wizard['forminator_addon_has_next_step'] = ( ( $step + 1 ) >= $total_steps ? false : true );
		$wizard['forminator_addon_has_prev_step'] = ( $step > 0 ? true : false );

		$wizard_default_values = array(
			'has_errors'   => false,
			'is_close'     => false,
			'notification' => array(),
			'size'         => 'small',
			'has_back'     => false,
			'is_poll'      => false,
		);

		foreach ( $wizard_default_values as $key => $wizard_default_value ) {
			if ( ! isset( $wizard[ $key ] ) ) {
				$wizard[ $key ] = $wizard_default_value;
			}
		}

		$addon_slug = $this->get_slug();
		$addon      = $this;

		/**
		 * Filter returned setting wizard to client
		 *
		 * @since 1.1
		 *
		 * @param array                     $wizard         current wizard.
		 * @param Forminator_Integration $addon          current addon instance.
		 * @param array                     $steps          defined settings / form settings steps.
		 * @param array                     $submitted_data $_POST.
		 * @param int                       $module_id      current form_id.
		 * @param int                       $step           requested step.
		 */
		$wizard = apply_filters( 'forminator_addon_' . $addon_slug . '_wizard', $wizard, $addon, $steps, $submitted_data, $module_id, $step );

		return $wizard;
	}

	/**
	 * Get Empty wizard markup
	 *
	 * @since   1.1
	 *
	 * @param string $notice Message.
	 *
	 * @return array
	 */
	protected function get_empty_wizard( $notice ) {
		$empty_wizard_html = Forminator_Admin::get_red_notice( esc_html( $notice ) );

		/**
		 * Filter html markup for empty wizard
		 *
		 * @since 1.1
		 *
		 * @param string $empty_wizard_html
		 * @param string $notice notice or message to be displayed on empty wizard.
		 */
		$empty_wizard_html = apply_filters( 'forminator_addon_empty_wizard_html', $empty_wizard_html, $notice );

		return array(
			'html'    => $empty_wizard_html,
			'buttons' => array(
				'close' => array(
					'action' => 'close',
					'data'   => array(),
					'markup' => self::get_button_markup( esc_html__( 'Close', 'forminator' ), 'sui-button-ghost forminator-addon-close' ),
				),
			),
		);
	}


	/**
	 * Override this function if addon need to do something with addon setting values
	 *
	 * @example transform, load from other storage ?
	 * called when rendering settings form
	 *
	 * @since   1.1
	 *
	 * @param array $values Settings.
	 *
	 * @return mixed
	 */
	public function before_get_settings_values( $values ) {
		return $values;
	}

	/**
	 * Get settings value
	 * its already hooked with
	 *
	 * @see     before_get_settings_values
	 *
	 * @since   1.47
	 * @param bool $decrypt Decrypt sensitive data.
	 *
	 * @since   1.1
	 * @return array
	 */
	final public function get_settings_values( $decrypt = false ) {
		$all_values = $this->get_all_settings_values();

		if ( is_null( $this->multi_global_id ) ) {
			$values = $all_values ? reset( $all_values ) : array();
		} elseif ( isset( $all_values[ $this->multi_global_id ] ) ) {
			$values = $all_values[ $this->multi_global_id ];
		} else {
			$values = array();
		}

		$addon_slug = $this->get_slug();

		if ( true === $decrypt ) {
			$values = $this->decrypt_sensitive_data( $values );
		}

		/**
		 * Filter retrieved saved addon's settings values from db
		 *
		 * @since 1.1
		 *
		 * @param mixed $values
		 */
		$values = apply_filters( 'forminator_addon_' . $addon_slug . '_get_settings_values', $values );

		return $values;
	}

	/**
	 * Remove unused settings
	 *
	 * @since 1.50.1
	 *
	 * @param mixed $settings All settings.
	 * @return mixed
	 */
	private function remove_unused_settings( $settings ) {
		if ( ! is_array( $settings ) || empty( $settings ) || empty( $this->_sensitive_keys ) ) {
			return $settings;
		}
		foreach ( $settings as $key => $setting ) {
			if ( is_array( $setting ) ) {
				foreach ( $this->_sensitive_keys as $sensitive_key ) {
					// Ignore Campaign Monitor client_id as this is optional field.
					if ( 'campaignmonitor' === $this->get_slug() && 'client_id' === $sensitive_key ) {
						continue;
					}
					if ( empty( $setting[ $sensitive_key ] ) ) {
						unset( $settings[ $key ] );
						update_option( $this->get_settings_options_name(), $settings );
						break; // No need to check other sensitive keys once the setting is removed.
					}
				}
			}
		}
		return $settings;
	}

	/**
	 * Encrypt sensitive data if it isn't encrypted for backward compatibility.
	 *
	 * @since 1.47
	 * @param mixed $settings All settings.
	 * @return mixed
	 */
	private function may_be_encrypt_sensitive_data( $settings ) {
		if ( ! is_array( $settings ) || empty( $settings ) || empty( $this->_sensitive_keys ) ) {
			return $settings;
		}
		$resave_option = false;
		foreach ( $settings as $key => $setting ) {
			if ( is_array( $setting ) ) {
				foreach ( $this->_sensitive_keys as $sensitive_key ) {
					if ( empty( $setting[ $sensitive_key ] ) ) {
						continue;
					}
					$encrypted_key_name = $sensitive_key . '_encrypted';
					if ( ! empty( $setting['is_salty'] ) && defined( 'FORMINATOR_ENCRYPTION_KEY' ) ) {
						// Re-encrypt settings after setting FORMINATOR_ENCRYPTION_KEY constant.
						$settings[ $key ][ $sensitive_key ] = Forminator_Encryption::decrypt( $setting[ $encrypted_key_name ], true );
						$settings[ $key ]                   = $this->encrypt_sensitive_data( $settings[ $key ] );
						$resave_option                      = true;
					} elseif ( empty( $setting[ $encrypted_key_name ] ) ) {
						// Re-save the setting to encrypt sensitive data.
						$settings[ $key ] = $this->encrypt_sensitive_data( $setting );
						$resave_option    = true;
					}
				}
			}
		}
		if ( true === $resave_option ) {
			update_option( $this->get_settings_options_name(), $settings );
			$settings = $this->get_all_settings_values();
		}

		return $settings;
	}

	/**
	 * Encrypt sensitive data
	 *
	 * @since 1.47
	 * @param array $settings Settings.
	 * @return array
	 */
	private function encrypt_sensitive_data( $settings ) {
		if ( ! is_array( $settings ) || empty( $settings ) ) {
			return $settings;
		}

		if ( ! empty( $this->_sensitive_keys ) ) {
			$settings = Forminator_Encryption::encrypt_secret_keys(
				$this->_sensitive_keys,
				$settings
			);
		}

		return $settings;
	}

	/**
	 * Decrypt sensitive data
	 *
	 * @since 1.47
	 * @param array $settings Settings.
	 * @return array
	 */
	protected function decrypt_sensitive_data( $settings ) {
		if ( ! is_array( $settings ) || empty( $settings ) || empty( $this->_sensitive_keys ) ) {
			return $settings;
		}
		foreach ( $this->_sensitive_keys as $sensitive_key ) {
			$encrypted_key_name = $sensitive_key . '_encrypted';
			if ( ! empty( $settings[ $encrypted_key_name ] ) ) {
				$settings[ $sensitive_key ] = Forminator_Encryption::decrypt( $settings[ $encrypted_key_name ] );
			}
		}

		return $settings;
	}

	/**
	 * Get decrypted real value if the value is the same as saved value
	 *
	 * @param string $value Post value.
	 * @param string $key_name Key name.
	 * @return string
	 */
	protected function get_real_value( $value, $key_name ) {
		$saved_settings = $this->get_settings_values();
		if ( isset( $saved_settings[ $key_name ] ) ) {
			if ( $saved_settings[ $key_name ] === $value ) {
				$decrypted_settings = $this->get_settings_values( true );
				return $decrypted_settings[ $key_name ];
			}
		}
		return $value;
	}

	/**
	 * Get settings value for all accouns
	 *
	 * @return array
	 */
	final public function get_all_settings_values() {
		$all_values = get_option( $this->get_settings_options_name(), array() );

		if ( $all_values && is_array( $all_values ) && ! is_array( reset( $all_values ) ) ) {
			// Backward compatibility before having multiple aprovider accounts.
			$this->multi_global_id = uniqid( '', true );
			$all_values            = array( $this->multi_global_id => $all_values );
			update_option( $this->get_settings_options_name(), $all_values );
			// Update modules integration options.
			$types = array( 'form', 'poll', 'quiz' );
			global $wpdb;
			foreach ( $types as $type ) {
				$meta_key = 'forminator_addon_' . $this->get_slug() . '_' . $type . '_settings';
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$results = $wpdb->get_results( $wpdb->prepare( "SELECT post_id, meta_value FROM $wpdb->postmeta WHERE meta_key = %s", $meta_key ), ARRAY_A );
				$results = wp_list_pluck( $results, 'meta_value', 'post_id' );
				foreach ( $results as $id => $value ) {
					update_post_meta( $id, $meta_key . '_' . $this->multi_global_id, maybe_unserialize( $value ) );
					delete_post_meta( $id, $meta_key );
				}
			}
			if ( 'hubspot' === $this->get_slug() ) {
				$option_name  = 'forminator-hubspot-token';
				$option_value = get_option( $option_name );
				if ( $option_value ) {
					update_option( $option_name . $this->multi_global_id, $option_value );
					delete_option( $option_name );
				}
			}
		}

		// Remove unused settings.
		$all_values = $this->remove_unused_settings( $all_values );

		// Backward compatibility encrypt data if not encrypted.
		$all_values = $this->may_be_encrypt_sensitive_data( $all_values );

		return $all_values;
	}

	/**
	 * Override this function if addon need to do something with addon setting values
	 *
	 * @example transform, save to other storage ?
	 * Called before settings values saved to db
	 *
	 * @since   1.1
	 *
	 * @param array $values Settings.
	 *
	 * @return mixed
	 */
	public function before_save_settings_values( $values ) {
		return $values;
	}

	/**
	 * Save settings value
	 * its already hooked with
	 *
	 * @see     before_save_settings_values
	 *
	 * @since   1.1
	 *
	 * @param array $values Settings.
	 */
	final public function save_settings_values( $values ) {
		$addon_slug = $this->get_slug();
		$all_values = $this->get_all_settings_values();

		/**
		 * Filter settings values of addon to be saved
		 *
		 * `$addon_slug` is current slug of addon that will on save.
		 * Example : `malchimp`, `webhook`, `etc`
		 *
		 * @since 1.1
		 *
		 * @param mixed $values
		 */
		$values = apply_filters( 'forminator_addon_' . $addon_slug . '_save_settings_values', $values );

		if ( empty( $this->multi_global_id ) ) {
			$this->multi_global_id = uniqid( '', true );
		}

		if ( $this->is_multi_global ) {
			$all_values[ $this->multi_global_id ] = forminator_sanitize_array_field( $values );
		} else {
			$all_values = array(
				$this->multi_global_id => forminator_sanitize_array_field( $values ),
			);
		}

		// Encrypt sensitive data.
		$all_values[ $this->multi_global_id ] = $this->encrypt_sensitive_data( $all_values[ $this->multi_global_id ] );
		update_option( $this->get_settings_options_name(), $all_values );
	}

	/**
	 * Auto Attach Default Admin hooks for addon
	 *
	 * @since   1.1
	 * @return bool
	 */
	final public function admin_hookable() {
		if ( $this->_is_admin_hooked ) {
			return true;
		}
		$default_filters = array(
			'forminator_addon_' . $this->get_slug() . '_save_settings_values' => array( array( $this, 'before_save_settings_values' ), 1 ),
		);

		if ( $this->is_connected() ) {
			$default_filters[ 'forminator_addon_' . $this->get_slug() . '_save_form_settings_values' ] = array( array( $this, 'before_save_form_settings_values' ), 2 );
			$default_filters[ 'forminator_addon_' . $this->get_slug() . '_save_poll_settings_values' ] = array( array( $this, 'before_save_poll_settings_values' ), 2 );
			$default_filters[ 'forminator_addon_' . $this->get_slug() . '_save_quiz_settings_values' ] = array( array( $this, 'before_save_quiz_settings_values' ), 2 );
		}

		foreach ( $default_filters as $filter => $default_filter ) {
			$function_to_add = $default_filter[0];
			if ( is_callable( $function_to_add ) ) {
				$accepted_args = $default_filter[1];
				add_filter( $filter, $function_to_add, 10, $accepted_args );
			}
		}
		$this->_is_admin_hooked = true;

		return true;
	}

	/**
	 * Maintain hooks all pages for addons
	 *
	 * @since   1.1
	 * @return bool
	 */
	final public function global_hookable() {
		if ( $this->_is_global_hooked ) {
			return true;
		}

		$default_filters = array(
			'forminator_addon_' . $this->get_slug() . '_get_settings_values' => array( array( $this, 'before_get_settings_values' ), 1 ),
		);

		if ( $this->is_connected() ) {
			$default_filters[ 'forminator_addon_' . $this->get_slug() . '_get_form_settings_values' ] = array( array( $this, 'before_get_form_settings_values' ), 2 );
			$default_filters[ 'forminator_addon_' . $this->get_slug() . '_get_poll_settings_values' ] = array( array( $this, 'before_get_poll_settings_values' ), 2 );
			$default_filters[ 'forminator_addon_' . $this->get_slug() . '_get_quiz_settings_values' ] = array( array( $this, 'before_get_quiz_settings_values' ), 2 );
		}

		foreach ( $default_filters as $filter => $default_filter ) {
			$function_to_add = $default_filter[0];
			if ( is_callable( $function_to_add ) ) {
				$accepted_args = $default_filter[1];
				add_filter( $filter, $function_to_add, 10, $accepted_args );
			}
		}
		$this->_is_global_hooked = true;

		return true;
	}

	/**
	 * Override this function if you need to apply some conditional logic on it
	 * By Default this function will only check @see Forminator_Integration::settings_wizards() as valid multi array
	 *
	 * @since 1.1
	 * @return bool
	 */
	public function is_settings_available() {
		$steps = $this->settings_wizards();
		if ( ! is_array( $steps ) ) {
			return false;
		}

		if ( count( $steps ) < 1 ) {
			return false;
		}

		return true;
	}

	/**
	 * Override this function if you need to apply some conditional logic on it
	 * By Default this function will check
	 *
	 * @param int $form_id Form Id.
	 *
	 * @return bool
	 * @since   1.1
	 *
	 * @see     Forminator_Integration::settings_wizards()
	 * @see     Forminator_Integration_Form_Settings::module_settings_wizards()
	 * as valid multi array
	 */
	public function is_form_settings_available( $form_id ) {
		$steps = $this->get_steps( $form_id );
		if ( ! is_array( $steps ) ) {
			return false;
		}

		if ( count( $steps ) < 1 ) {
			return false;
		}

		return true;
	}

	/**
	 * Flag for check if and addon connected (global settings such as api key complete)
	 *
	 * Please apply necessary WordPress hook on the inheritance class
	 *
	 * @since   1.1
	 * @return boolean
	 */
	public function is_connected() {
		$is_connected = $this->is_active() && $this->is_authorized();

		/**
		 * Filter connected status
		 *
		 * @param bool $is_connected
		 */
		$is_connected = apply_filters( 'forminator_addon_' . $this->get_slug() . '_is_connected', $is_connected );

		return $is_connected;
	}

	/**
	 * Authorized Callback
	 *
	 * @return bool
	 */
	public function is_authorized() {
		return false; }

	/**
	 * Flag for check if and addon connected to a form(form settings such as list id completed)
	 *
	 * Please apply necessary WordPress hook on the inheritance class
	 *
	 * @since   1.1
	 * @param int    $module_id Form ID.
	 * @param string $module_slug Module type.
	 * @param bool   $check_lead Check is lead connected or not.
	 * @return boolean
	 * @throws Forminator_Integration_Exception When there is an Interaction error.
	 */
	public function is_module_connected( $module_id, $module_slug = 'form', $check_lead = false ) {
		try {
			$addon_settings = null;
			if ( ! $this->is_connected() ) {
				throw new Forminator_Integration_Exception( esc_html__( ' Integration is not connected', 'forminator' ) );
			}

			$addon_settings = $this->get_addon_settings( $module_id, $module_slug );
			if ( ! $addon_settings instanceof Forminator_Integration_Settings ) {
				throw new Forminator_Integration_Exception( esc_html__( 'Invalid Module Integration Settings', 'forminator' ) );
			}

			if ( $check_lead ) {
				$is_connected = $addon_settings->has_lead();
			} else {
				// Mark as active when there is at least one active connection.
				if ( false === $addon_settings->find_one_active_connection() ) {
					throw new Forminator_Integration_Exception( esc_html__( 'No active Integration connection found in this module', 'forminator' ) );
				}

				$is_connected = true;
			}
		} catch ( Forminator_Integration_Exception $e ) {
			$is_connected = false;
			forminator_addon_maybe_log( __METHOD__, '[' . $this->get_title() . ']' . $e->getMessage() );
		}

		/**
		 * Filter addon connected status with the form
		 *
		 * @param bool                                    $is_connected
		 * @param int                                     $module_id Current Module ID.
		 * @param Forminator_Integration_Form_Settings $addon_settings Instance of form settings, or null when unavailable.
		 */
		$is_connected = apply_filters( 'forminator_addon_' . $this->get_slug() . '_is_' . $module_slug . ( $check_lead ? '_lead' : '' ) . '_connected', $is_connected, $module_id, $addon_settings );

		return $is_connected;
	}

	/**
	 * Check if this addon on active
	 *
	 * @since   1.1
	 * @return bool
	 */
	final public function is_active() {
		return forminator_addon_is_active( $this->get_slug() );
	}

	/**
	 * Flag show full log on entries
	 *
	 * @return bool
	 */
	public function is_show_full_log() {
		$slug  = $this->get_slug();
		$const = 'FORMINATOR_ADDON_' . strtoupper( $slug ) . '_SHOW_FULL_LOG';
		$glob  = 'FORMINATOR_ADDON_SHOW_FULL_LOG';

		$show_full_log = ( defined( $const ) && constant( $const ) ) || ( defined( $glob ) && constant( $glob ) );

		/**
		 * Filter Flag show full log on entries
		 *
		 * @params bool $show_full_log
		 */
		$show_full_log = apply_filters( 'forminator_addon_' . $slug . '_show_full_log', $show_full_log );

		return $show_full_log;
	}

	/**
	 * Get ClassName of addon Module Settings
	 *
	 * @see   Forminator_Integration_Settings
	 *
	 * @param string $module_type Module type.
	 *
	 * @since 1.1
	 * @return null|string
	 */
	final public function get_settings_class_name( $module_type ) {
		$addon_slug          = $this->get_slug();
		$settings_class_name = 'Forminator_' . ucfirst( $addon_slug ) . '_' . ucfirst( $module_type ) . '_Settings';

		/**
		 * Filter class name of the addon module settings
		 *
		 * Module settings class name is a string
		 * it will be validated by `class_exists` and must be instanceof @see Forminator_Integration_Settings
		 *
		 * @since 1.1
		 *
		 * @param string $settings_class_name
		 */
		$settings_class_name = apply_filters( 'forminator_addon_' . $addon_slug . '_' . $module_type . '_settings_class_name', $settings_class_name );

		return $settings_class_name;
	}

	/**
	 * Get Form Settings Instance
	 *
	 * @since   1.1
	 *
	 * @param int    $module_id Module type.
	 * @param string $module_type Moodule type.
	 *
	 * @return Forminator_Integration_Form_Settings | null
	 * @throws Forminator_Integration_Exception When there is an Integration error.
	 */
	final public function get_addon_settings( $module_id, $module_type ) {
		$class_name = $this->get_settings_class_name( $module_type );
		if ( empty( $class_name ) || ! class_exists( $class_name ) ) {
			return null;
		}

		try {
			$settings_instance = new $class_name( $this, $module_id );
			if ( ! $settings_instance instanceof Forminator_Integration_Settings ) {
				throw new Forminator_Integration_Exception( $class_name . ' is not instanceof Forminator_Integration_Settings' );
			}
			forminator_maybe_attach_addon_hook( $this );
			return $settings_instance;
		} catch ( Exception $e ) {
			forminator_addon_maybe_log( $this->get_slug(), 'Failed to instantiate its _addon_settings_instances', $e->getMessage(), $e->getTrace() );
			return null;
		}
	}

	/**
	 * Executor of before get form settings values, to be correctly mapped with form_setting instance for form_id
	 *
	 * @since 1.1
	 *
	 * @param string $values Settings.
	 * @param int    $form_id Form Id.
	 *
	 * @return mixed
	 */
	final public function before_get_form_settings_values( $values, $form_id ) {
		$form_settings = $this->get_addon_settings( $form_id, 'form' );
		if ( $form_settings instanceof Forminator_Integration_Form_Settings ) {
			if ( is_callable( array( $form_settings, 'before_get_form_settings_values' ) ) ) {
				return $form_settings->before_get_form_settings_values( $values );
			}
		}

		return $values;
	}

	/**
	 * Executor of before save form settings values, to be correctly mapped with form_setting instance for form_id
	 *
	 * @since 1.1
	 *
	 * @param string $values Settings.
	 * @param int    $form_id Form Id.
	 *
	 * @return mixed
	 */
	final public function before_save_form_settings_values( $values, $form_id ) {
		$form_settings = $this->get_addon_settings( $form_id, 'form' );
		if ( $form_settings instanceof Forminator_Integration_Form_Settings ) {
			if ( is_callable( array( $form_settings, 'before_save_form_settings_values' ) ) ) {
				return $form_settings->before_save_form_settings_values( $values );
			}
		}

		return $values;
	}


	/**
	 * Get Hooks object of Integrations
	 *
	 * @param int $module_id Module ID.
	 * @param int $module_type Module type.
	 *
	 * @return Forminator_Integration_Hooks|null
	 * @since 1.1
	 */
	final public function get_addon_hooks( $module_id, $module_type ) {
		if ( ! isset( $this->addon_hooks_instances[ $module_id ] ) || ! $this->addon_hooks_instances[ $module_id ] instanceof Forminator_Integration_Hooks ) {
			$addon_slug = $this->get_slug();
			$classname  = 'Forminator_' . ucfirst( $addon_slug ) . '_' . ucfirst( $module_type ) . '_Hooks';

			if ( ! class_exists( $classname ) ) {
				return null;
			}

			try {
				$this->addon_hooks_instances[ $module_id ] = new $classname( $this, $module_id );
			} catch ( Exception $e ) {
				forminator_addon_maybe_log( $this->get_slug(), 'Failed to instantiate its addon_hooks_instance', $e->getMessage() );

				return null;
			}
		}

		return $this->addon_hooks_instances[ $module_id ];
	}

	/**
	 * SAMPLE of callback wizard
	 *
	 * @example {
	 * 'html' : '', => will contains title, description, and form it self
	 * 'has_errors' : true/false => true when it has error, such as invalid input
	 * buttons [
	 *      submit [
	 *          action: forminator_load_mailchimp_settings
	 *          data: {
	 *              step: 2
	 *          },
	 *          markup: '<a></a>'
	 *      ],
	 *      disconnect [
	 *          action: forminator_disconnect_mailchimp,
	 *          data: [],
	 *          markup: '<a></a>'
	 *      ]
	 * }
	 * 'redirect': '',
	 * 'is_close' : true if wizard should be closed
	 * ]
	 *
	 * @param array $submitted_data Submitted data.
	 * @param int   $form_id Form Id.
	 *
	 * @since   1.1
	 * @return array
	 */
	private function sample_setting_first_step( $submitted_data, $form_id ) {
		// TODO: break `html` into `parts` to make easier for addon to extend.
		return array(
			'html'       => '<p>Hello im from first step settings</p>',
			'has_errors' => false,
		);
	}

	/**
	 * SAMPLE of is_completed wizard step
	 *
	 * @since   1.1
	 * @return bool
	 */
	private function sample_setting_first_step_is_completed() {
		// check something.
		return true; // when check is passed.
	}

	/**
	 * Override this function if you wanna make an addon allow multiple instance on 1 form
	 *
	 * @since 1.1
	 * @return bool
	 */
	public function is_allow_multi_on_form() {
		return false;
	}

	/**
	 * Return button markup
	 *
	 * @since 1.1
	 *
	 * @param string $label Text label.
	 * @param string $classes Class names.
	 * @param string $tooltip Content for Tooltip.
	 *
	 * @return string
	 */
	public static function get_button_markup( $label, $classes = '', $tooltip = '' ) {
		$markup = '<button type="button" class="sui-button ';
		if ( ! empty( $classes ) ) {
			$markup .= $classes;
		}
		$markup .= '"';
		if ( ! empty( $tooltip ) ) {
			$markup .= 'data-tooltip="' . $tooltip . '"';
		}
		$markup .= '>';
		$markup .= '<span class="sui-loading-text">' . $label . '</span>';
		$markup .= '<i class="sui-icon-loader sui-loading" aria-hidden="true"></i>';
		$markup .= '</button>';

		/**
		 * Filter Integration button markup for setting
		 *
		 * Its possible @see Forminator_Integration::get_button_markup() overridden.
		 * Thus this filter wont be called
		 *
		 * @since 1.1
		 *
		 * @param string $markup  Current markup.
		 * @param string $label   Button label.
		 * @param string $classes Additional classes for `<button>`.
		 * @param string $tooltip
		 */
		$markup = apply_filters( 'forminator_addon_setting_button_markup', $markup, $label, $classes, $tooltip );

		return $markup;
	}

	/**
	 * Return link markup
	 *
	 * @since 1.13
	 *
	 * @param string $url URL.
	 * @param string $label Text for label.
	 * @param string $target Target attribute.
	 * @param string $classes Class names.
	 * @param string $tooltip Content for tooltip.
	 *
	 * @return string
	 */
	public static function get_link_markup( $url, $label, $target = '_blank', $classes = '', $tooltip = '' ) {
		$markup = '<a href="' . $url . '" target="' . $target . '" class="sui-button ';
		if ( ! empty( $classes ) ) {
			$markup .= $classes;
		}
		$markup .= '"';
		if ( ! empty( $tooltip ) ) {
			$markup .= 'data-tooltip="' . $tooltip . '"';
		}
		$markup .= '>';
		$markup .= '<span class="sui-loading-text">' . $label . '</span>';
		$markup .= '<i class="sui-icon-loader sui-loading" aria-hidden="true"></i>';
		$markup .= '</a>';

		/**
		 * Filter Integration link markup for setting
		 *
		 * Its possible @see Forminator_Integration::get_link_markup() overridden.
		 * Thus this filter wont be called
		 *
		 * @since 1.1
		 *
		 * @param string $markup  Current markup.
		 * @param string $url     Link URL.
		 * @param string $label   Button label.
		 * @param string $target  Link target.
		 * @param string $classes Additional classes for `<button>`.
		 * @param string $tooltip
		 */
		$markup = apply_filters( 'forminator_addon_setting_link_markup', $markup, $url, $label, $target, $classes, $tooltip );

		return $markup;
	}

	/**
	 * Get Template as string
	 *
	 * @since 1.2
	 *
	 * @param string $template Template path.
	 * @param array  $params Template variables.
	 *
	 * @return string
	 */
	public static function get_template( $template, $params ) {
		/* @noinspection PhpUnusedLocalVariableInspection */
		$template_vars = $params;
		ob_start();
		/* @noinspection PhpIncludeInspection */
		include $template;
		$html = ob_get_clean();

		/**
		 * Filter Html String from template
		 *
		 * @since 1.2
		 *
		 * @param string $html
		 * @param string $template template file path.
		 * @param array  $params   template variables.
		 */
		$html = apply_filters( 'forminator_addon_get_template', $html, $template, $params );

		return $html;
	}

	/**
	 * Register section(s) on integrations page
	 *
	 * @see   wp-admin/admin.php?page=forminator-integrations
	 *
	 * @since 1.2
	 *
	 * @return array
	 */
	public function register_integration_sections() {
		// callback must be public method on this class.
		return array();
	}

	/**
	 * Get Callback of section on integration page
	 *
	 * When section not provided, it will return all callbacks
	 *
	 * @since 1.2
	 *
	 * @param string $section Section.
	 *
	 * @return array|null
	 */
	public function get_integration_section_callback( $section = null ) {
		$addon_slug           = $this->_slug;
		$integration_sections = $this->register_integration_sections();

		$callback = null;

		if ( is_null( $section ) ) {
			$callback = $integration_sections;
		} elseif ( isset( $integration_sections[ $section ] ) ) {
			$callback = $integration_sections[ $section ];
		}

		/**
		 * Filter Integration section callback
		 *
		 * @since 1.2
		 *
		 * @param array|null  $callback
		 * @param string|null $section              requested section.
		 * @param array       $integration_sections registered sections.
		 */
		$callback = apply_filters( 'forminator_addon_' . $addon_slug . '_integration_section_callback', $callback, $section, $integration_sections );

		return $callback;
	}

	/**
	 * Generate multi_id
	 *
	 * @since 1.2
	 *
	 * @param int $form_id Form id.
	 *
	 * @return array
	 */
	private function generate_form_settings_multi_id( $form_id ) {
		$addon_slug             = $this->get_slug();
		$addon                  = $this;
		$multi_id               = 0;
		$form_settings_instance = $this->get_addon_settings( $form_id, 'form' );
		if ( $this->is_allow_multi_on_form() && ! is_null( $form_settings_instance ) && $form_settings_instance instanceof Forminator_Integration_Form_Settings ) {
			$multi_id = $form_settings_instance->generate_multi_id();
		}

		/**
		 * Filter new generated multi id of addon form setting
		 *
		 * @since 1.2
		 *
		 * @param string                                  $multi_id
		 * @param Forminator_Integration_Form_Settings $addon                  Integration Instance.
		 * @param Forminator_Integration_Form_Settings $form_settings_instance Integration Form Settings Instance.
		 */
		$multi_ids = apply_filters( 'forminator_addon_' . $addon_slug . '_form_settings_multi_id', $multi_id, $addon, $form_settings_instance );

		return $multi_ids;
	}

	/**
	 * Executor of before get form settings values, to be correctly mapped with poll_setting instance for poll_id
	 *
	 * @since 1.6.1
	 *
	 * @param array $values Settings.
	 * @param int   $poll_id Poll Id.
	 *
	 * @return mixed
	 */
	final public function before_get_poll_settings_values( $values, $poll_id ) {
		$poll_settings = $this->get_addon_settings( $poll_id, 'poll' );
		if ( $poll_settings instanceof Forminator_Integration_Poll_Settings ) {
			if ( is_callable( array( $poll_settings, 'before_get_poll_settings_values' ) ) ) {
				return $poll_settings->before_get_poll_settings_values( $values );
			}
		}

		return $values;
	}

	/**
	 * Executor of before save form settings values, to be correctly mapped with poll_setting instance for poll_id
	 *
	 * @since 1.6.1
	 *
	 * @param array $values Settings.
	 * @param int   $poll_id Poll Id.
	 *
	 * @return mixed
	 */
	final public function before_save_poll_settings_values( $values, $poll_id ) {
		$poll_settings = $this->get_addon_settings( $poll_id, 'poll' );
		if ( $poll_settings instanceof Forminator_Integration_Poll_Settings ) {
			if ( is_callable( array( $poll_settings, 'before_save_poll_settings_values' ) ) ) {
				return $poll_settings->before_save_poll_settings_values( $values );
			}
		}

		return $values;
	}

	/**
	 * Get Poll Setting Wizard
	 * This function will process @see Forminator_Integration::settings_wizards()
	 * Please keep in mind this function will only be called when @see Forminator_Integration::is_settings_available() return `true`
	 * Which is doing check on @see Forminator_Integration::settings_wizards() requirements is passed
	 *
	 * @since 1.6.1
	 *
	 * @param array $submitted_data Submitted data.
	 * @param int   $poll_id Poll Id.
	 * @param int   $current_step Current step.
	 * @param int   $step Step.
	 *
	 * @return array|mixed
	 */
	final public function get_poll_settings_wizard( $submitted_data, $poll_id, $current_step = 0, $step = 0 ) {

		$settings_steps = array();
		if ( ! $this->is_connected() ) {
			$settings_steps = $this->settings_wizards();
		}

		$poll_settings_steps = $this->get_poll_settings_steps( $poll_id );

		$steps = array_merge( $settings_steps, $poll_settings_steps );

		if ( ! is_array( $steps ) ) {
			/* translators: integration title */
			return $this->get_empty_wizard( sprintf( esc_html__( 'No Poll Settings available for %1$s', 'forminator' ), $this->get_title() ) );
		}
		$total_steps = count( $steps );
		if ( $total_steps < 1 ) {
			/* translators: integration title */
			return $this->get_empty_wizard( sprintf( esc_html__( 'No Poll Settings available for %1$s', 'forminator' ), $this->get_title() ) );
		}

		if ( ! isset( $steps[ $step ] ) ) {
			// go to last step.
			$step = $total_steps - 1;

			return $this->get_poll_settings_wizard( $submitted_data, $poll_id, $current_step, $step );
		}

		if ( $step > 0 ) {
			if ( $current_step > 0 ) {
				// check previous step is complete.
				$prev_step              = $current_step - 1;
				$prev_step_is_completed = true;
				// only call `is_completed` when its defined.
				if ( isset( $steps[ $prev_step ]['is_completed'] ) && is_callable( $steps[ $prev_step ]['is_completed'] ) ) {
					$prev_step_is_completed = call_user_func( $steps[ $prev_step ]['is_completed'], $submitted_data );
				}
				if ( ! $prev_step_is_completed ) {
					--$step;

					return $this->get_poll_settings_wizard( $submitted_data, $poll_id, $current_step, $step );
				}
			}

			// only validation when it moves forward.
			if ( $step > $current_step ) {
				$current_step_result = $this->get_poll_settings_wizard( $submitted_data, $poll_id, $current_step, $current_step );
				if ( isset( $current_step_result['has_errors'] ) && true === $current_step_result['has_errors'] ) {
					return $current_step_result;
				} else {
					// set empty submitted data for next step, except preserved as reference.
					$preserved_keys = array(
						'multi_id',
					);
					foreach ( $submitted_data as $key => $value ) {
						if ( ! in_array( $key, $preserved_keys, true ) ) {
							unset( $submitted_data[ $key ] );
						}
					}
				}
			}
		}

		$poll_settings_wizard = $this->get_wizard( $steps, $submitted_data, $poll_id, $step );

		$addon_slug             = $this->get_slug();
		$addon                  = $this;
		$poll_settings_instance = $this->get_addon_settings( $poll_id, 'poll' );

		/**
		 * Filter poll settings wizard returned to client
		 *
		 * @since 1.6.1
		 *
		 * @param array                                        $poll_settings_wizard
		 * @param array                                        $submitted_data         $_POST from client.
		 * @param int                                          $poll_id                poll ID requested for.
		 * @param int                                          $current_step           Current Step displayed to user, start from 0.
		 * @param int                                          $step                   Step requested by client, start from 0.
		 * @param Forminator_Integration                    $addon                  Integration Instance.
		 * @param Forminator_Integration_Poll_Settings|null $poll_settings_instance Integration Form settings instance, or null if unavailable.
		 */
		$poll_settings_wizard = apply_filters(
			'forminator_addon_' . $addon_slug . '_poll_settings_wizard',
			$poll_settings_wizard,
			$submitted_data,
			$poll_id,
			$current_step,
			$step,
			$addon,
			$poll_settings_instance
		);

		return $poll_settings_wizard;
	}

	/**
	 * Get poll settings wizard steps
	 *
	 * @since 1.6.1
	 *
	 * @param int $poll_id Poll Id.
	 *
	 * @return array
	 */
	private function get_poll_settings_steps( $poll_id ) {
		$addon_slug             = $this->get_slug();
		$addon                  = $this;
		$poll_settings_steps    = array();
		$poll_settings_instance = $this->get_addon_settings( $poll_id, 'poll' );
		if ( ! is_null( $poll_settings_instance ) && $poll_settings_instance instanceof Forminator_Integration_Poll_Settings ) {
			$poll_settings_steps = $poll_settings_instance->module_settings_wizards();
		}

		/**
		 * Filter form settings step that will be used for building wizard
		 *
		 * More detail : @param array $poll_settings_steps
		 *
		 * @param int                                          $poll_id                current form id.
		 * @param Forminator_Integration                    $addon                  Integration instance.
		 * @param Forminator_Integration_Poll_Settings|null $poll_settings_instance Form settings of addon if available, or null otherwise.
		 *@see Forminator_Integration_Poll_Settings::module_settings_wizards()
		 *
		 * @since 1.6.1
		 */
		$poll_settings_steps = apply_filters( 'forminator_addon_' . $addon_slug . '_poll_settings_steps', $poll_settings_steps, $poll_id, $addon, $poll_settings_instance );

		return $poll_settings_steps;
	}

	/**
	 * Get poll settings multi id
	 *
	 * @since 1.6.1
	 *
	 * @param int $poll_id Poll Id.
	 *
	 * @return array
	 */
	private function get_poll_settings_multi_ids( $poll_id ) {
		$addon_slug             = $this->get_slug();
		$addon                  = $this;
		$multi_ids              = array();
		$poll_settings_instance = $this->get_addon_settings( $poll_id, 'poll' );
		if ( $this->is_allow_multi_on_poll() && ! is_null( $poll_settings_instance ) && $poll_settings_instance instanceof Forminator_Integration_Poll_Settings ) {
			$multi_ids = $poll_settings_instance->get_multi_ids();
		}

		/**
		 * Filter multi id of addon poll settings
		 *
		 * @since 1.6.1
		 *
		 * @param array                                   $multi_ids
		 * @param Forminator_Integration               $addon                  Integration Instance.
		 * @param Forminator_Integration_Poll_Settings $poll_settings_instance Integration Form Settings Instance.
		 */
		$multi_ids = apply_filters( 'forminator_addon_' . $addon_slug . '_poll_settings_multi_ids', $multi_ids, $addon, $poll_settings_instance );

		return $multi_ids;
	}

	/**
	 * Override this function if you wanna make an addon allow multiple instance on 1 poll
	 *
	 * @since 1.6.1
	 * @return bool
	 */
	public function is_allow_multi_on_poll() {
		return false;
	}

	/**
	 * Transform addon instance into array with form relation
	 *
	 * @since  1.1
	 * @since  1.2 generate new multi_id to allow reference on wizard
	 *
	 * @param int $poll_id Poll Id.
	 *
	 * @return array
	 */
	final public function to_array_with_poll( $poll_id ) {
		$to_array                               = $this->to_array();
		$is_allow_multi_on_poll                 = $this->is_allow_multi_on_poll();
		$to_array['is_poll_connected']          = $this->is_module_connected( $poll_id, 'poll' );
		$to_array['is_poll_settings_available'] = $this->is_poll_settings_available( $poll_id );
		$to_array['is_allow_multi_on_poll']     = $is_allow_multi_on_poll;

		$to_array['multi_id'] = $this->generate_poll_settings_multi_id( $poll_id );

		// handle multiple form setting.
		if ( $is_allow_multi_on_poll ) {
			$to_array['multi_ids'] = $this->get_poll_settings_multi_ids( $poll_id );
		}

		$to_array_with_poll = $to_array;
		$addon_slug         = $this->get_slug();
		$addon              = $this;

		/**
		 * Filter array of addon properties
		 *
		 * Including relation with form_id
		 *
		 * @since 1.6.1
		 *
		 * @param array                     $to_array_with_poll array of addon properties.
		 * @param int                       $poll_id            Poll ID.
		 * @param Forminator_Integration $addon              Integration instance.
		 */
		$to_array_with_poll = apply_filters( 'forminator_addon_' . $addon_slug . '_to_array_with_poll', $to_array_with_poll, $poll_id, $addon );

		return $to_array_with_poll;
	}

	/**
	 * Override this function if you need to apply some conditional logic on it
	 * By Default this function will check
	 *
	 * @param int $poll_id Poll Id.
	 *
	 * @return bool
	 * @since   1.6.1
	 *
	 * @see     Forminator_Integration::settings_wizards()
	 * @see     Forminator_Integration_Poll_Settings::module_settings_wizards()
	 * as valid multi array
	 */
	public function is_poll_settings_available( $poll_id ) {
		$steps      = array();
		$poll_steps = $this->get_poll_settings_steps( $poll_id );

		$steps = array_merge( $steps, $poll_steps );
		if ( ! is_array( $steps ) ) {
			return false;
		}

		if ( count( $steps ) < 1 ) {
			return false;
		}

		return true;
	}

	/**
	 * Generate multi_id
	 *
	 * @since 1.6.1
	 *
	 * @param int $poll_id Poll Id.
	 *
	 * @return array
	 */
	private function generate_poll_settings_multi_id( $poll_id ) {
		$addon_slug             = $this->get_slug();
		$addon                  = $this;
		$multi_id               = 0;
		$poll_settings_instance = $this->get_addon_settings( $poll_id, 'poll' );
		if ( $this->is_allow_multi_on_poll() && ! is_null( $poll_settings_instance ) && $poll_settings_instance instanceof Forminator_Integration_Poll_Settings ) {
			$multi_id = $poll_settings_instance->generate_multi_id();
		}

		/**
		 * Filter new generated multi id of addon form setting
		 *
		 * @since 1.6.1
		 *
		 * @param string                                  $multi_id
		 * @param Forminator_Integration               $addon                  Integration Instance.
		 * @param Forminator_Integration_Poll_Settings $poll_settings_instance Integration Poll Settings Instance.
		 */
		$multi_ids = apply_filters( 'forminator_addon_' . $addon_slug . '_poll_settings_multi_id', $multi_id, $addon, $poll_settings_instance );

		return $multi_ids;
	}

	/**
	 * Executor of before get form settings values, to be correctly mapped with quiz_setting instance for quiz_id
	 *
	 * @since 1.6.2
	 *
	 * @param array $values Settings.
	 * @param int   $quiz_id Quiz Id.
	 *
	 * @return mixed
	 */
	final public function before_get_quiz_settings_values( $values, $quiz_id ) {
		$quiz_settings = $this->get_addon_settings( $quiz_id, 'quiz' );
		if ( $quiz_settings instanceof Forminator_Integration_Quiz_Settings ) {
			if ( is_callable( array( $quiz_settings, 'before_get_quiz_settings_values' ) ) ) {
				return $quiz_settings->before_get_quiz_settings_values( $values );
			}
		}

		return $values;
	}

	/**
	 * Executor of before save form settings values, to be correctly mapped with quiz_setting instance for quiz_id
	 *
	 * @since 1.6.2
	 *
	 * @param array $values Settings.
	 * @param int   $quiz_id Quiz Id.
	 *
	 * @return mixed
	 */
	final public function before_save_quiz_settings_values( $values, $quiz_id ) {
		$quiz_settings = $this->get_addon_settings( $quiz_id, 'quiz' );
		if ( $quiz_settings instanceof Forminator_Integration_Quiz_Settings ) {
			if ( is_callable( array( $quiz_settings, 'before_save_quiz_settings_values' ) ) ) {
				return $quiz_settings->before_save_quiz_settings_values( $values );
			}
		}

		return $values;
	}

	/**
	 * Get Quiz Setting Wizard
	 * This function will process @see Forminator_Integration::settings_wizards()
	 * Please keep in mind this function will only be called when @see Forminator_Integration::is_settings_available() return `true`
	 * Which is doing check on @see Forminator_Integration::settings_wizards() requirements is passed
	 *
	 * @since 1.6.2
	 *
	 * @param array $submitted_data Submitted data.
	 * @param int   $quiz_id Quiz Id.
	 * @param int   $current_step Current step.
	 * @param int   $step Step.
	 *
	 * @return array|mixed
	 */
	final public function get_quiz_settings_wizard( $submitted_data, $quiz_id, $current_step = 0, $step = 0 ) {
		$steps = $this->get_steps( $quiz_id, 'quiz' );

		if ( ! is_array( $steps ) ) {
			/* translators: integration title */
			return $this->get_empty_wizard( sprintf( esc_html__( 'No Quiz Settings available for %1$s', 'forminator' ), $this->get_title() ) );
		}
		$total_steps = count( $steps );
		if ( $total_steps < 1 ) {
			/* translators: integration title */
			return $this->get_empty_wizard( sprintf( esc_html__( 'No Quiz Settings available for %1$s', 'forminator' ), $this->get_title() ) );
		}

		if ( ! isset( $steps[ $step ] ) ) {
			// go to last step.
			$step = $total_steps - 1;

			return $this->get_quiz_settings_wizard( $submitted_data, $quiz_id, $current_step, $step );
		}

		if ( $step > 0 ) {
			if ( $current_step > 0 ) {
				// check previous step is complete.
				$prev_step              = $current_step - 1;
				$prev_step_is_completed = true;
				// only call `is_completed` when its defined.
				if ( isset( $steps[ $prev_step ]['is_completed'] ) && is_callable( $steps[ $prev_step ]['is_completed'] ) ) {
					$prev_step_is_completed = call_user_func( $steps[ $prev_step ]['is_completed'], $submitted_data );
				}
				if ( ! $prev_step_is_completed ) {
					--$step;

					return $this->get_quiz_settings_wizard( $submitted_data, $quiz_id, $current_step, $step );
				}
			}

			// only validation when it moves forward.
			if ( $step > $current_step ) {
				$current_step_result = $this->get_quiz_settings_wizard( $submitted_data, $quiz_id, $current_step, $current_step );
				if ( isset( $current_step_result['has_errors'] ) && true === $current_step_result['has_errors'] ) {
					return $current_step_result;
				} else {
					// set empty submitted data for next step, except preserved as reference.
					$preserved_keys = array(
						'multi_id',
					);
					foreach ( $submitted_data as $key => $value ) {
						if ( ! in_array( $key, $preserved_keys, true ) ) {
							unset( $submitted_data[ $key ] );
						}
					}
					// Reset steps cache - uses when wizard steps are conditional.
					unset( $this->steps[ $quiz_id ] );
					$steps = $this->get_steps( $quiz_id, 'quiz' );
				}
			}
		}

		$quiz_settings_wizard = $this->get_wizard( $steps, $submitted_data, $quiz_id, $step );

		$addon_slug             = $this->get_slug();
		$addon                  = $this;
		$quiz_settings_instance = $this->get_addon_settings( $quiz_id, 'quiz' );

		/**
		 * Filter quiz settings wizard returned to client
		 *
		 * @since 1.6.2
		 *
		 * @param array                                        $quiz_settings_wizard
		 * @param array                                        $submitted_data         $_POST from client.
		 * @param int                                          $quiz_id                quiz ID requested for.
		 * @param int                                          $current_step           Current Step displayed to user, start from 0.
		 * @param int                                          $step                   Step requested by client, start from 0.
		 * @param Forminator_Integration                    $addon                  Integration Instance.
		 * @param Forminator_Integration_Quiz_Settings|null $quiz_settings_instance Integration Form settings instance, or null if unavailable.
		 */
		$quiz_settings_wizard = apply_filters(
			'forminator_addon_' . $addon_slug . '_quiz_settings_wizard',
			$quiz_settings_wizard,
			$submitted_data,
			$quiz_id,
			$current_step,
			$step,
			$addon,
			$quiz_settings_instance
		);

		return $quiz_settings_wizard;
	}

	/**
	 * Get quiz settings wizard steps
	 *
	 * @since 1.6.2
	 *
	 * @param int $quiz_id Quiz Id.
	 *
	 * @return array
	 */
	private function get_quiz_settings_steps( $quiz_id ) {
		$addon_slug             = $this->get_slug();
		$addon                  = $this;
		$quiz_settings_steps    = array();
		$quiz_settings_instance = $this->get_addon_settings( $quiz_id, 'quiz' );
		if ( ! is_null( $quiz_settings_instance ) && $quiz_settings_instance instanceof Forminator_Integration_Quiz_Settings ) {
			$quiz_settings_steps = $quiz_settings_instance->module_settings_wizards();
		}

		/**
		 * Filter form settings step that will be used for building wizard
		 *
		 * More detail : @param array $quiz_settings_steps
		 *
		 * @param int                                          $quiz_id                current quiz id.
		 * @param Forminator_Integration                    $addon                  Integration instance.
		 * @param Forminator_Integration_Quiz_Settings|null $quiz_settings_instance Quiz settings of addon if available, or null otherwise.
		 *@see Forminator_Integration_Quiz_Settings::module_settings_wizards()
		 *
		 * @since 1.6.2
		 */
		$quiz_settings_steps = apply_filters( 'forminator_addon_' . $addon_slug . '_quiz_settings_steps', $quiz_settings_steps, $quiz_id, $addon, $quiz_settings_instance );

		return $quiz_settings_steps;
	}

	/**
	 * Get quiz settings multi id
	 *
	 * @since 1.6.2
	 *
	 * @param int $quiz_id Quiz Id.
	 *
	 * @return array
	 */
	private function get_quiz_settings_multi_ids( $quiz_id ) {
		$addon_slug             = $this->get_slug();
		$addon                  = $this;
		$multi_ids              = array();
		$quiz_settings_instance = $this->get_addon_settings( $quiz_id, 'quiz' );
		if ( $this->is_allow_multi_on_quiz() && ! is_null( $quiz_settings_instance ) && $quiz_settings_instance instanceof Forminator_Integration_Quiz_Settings ) {
			$multi_ids = $quiz_settings_instance->get_multi_ids();
		}

		/**
		 * Filter multi id of addon quiz settings
		 *
		 * @since 1.6.2
		 *
		 * @param array                                   $multi_ids
		 * @param Forminator_Integration               $addon                  Integration Instance.
		 * @param Forminator_Integration_Quiz_Settings $quiz_settings_instance Integration Quiz Settings Instance.
		 */
		$multi_ids = apply_filters( 'forminator_addon_' . $addon_slug . '_quiz_settings_multi_ids', $multi_ids, $addon, $quiz_settings_instance );

		return $multi_ids;
	}

	/**
	 * Override this function if you wanna make an addon allow multiple instance on 1 quiz
	 *
	 * @since 1.6.2
	 * @return bool
	 */
	public function is_allow_multi_on_quiz() {
		return false;
	}

	/**
	 * Transform addon instance into array with form relation
	 *
	 * @since  1.6.2
	 *
	 * @param int $quiz_id Quiz Id.
	 *
	 * @return array
	 */
	final public function to_array_with_quiz( $quiz_id ) {
		$to_array                               = $this->to_array();
		$is_allow_multi_on_quiz                 = $this->is_allow_multi_on_quiz();
		$to_array['is_quiz_connected']          = $this->is_module_connected( $quiz_id, 'quiz' );
		$to_array['is_quiz_settings_available'] = $this->is_quiz_settings_available( $quiz_id );
		$to_array['is_allow_multi_on_quiz']     = $is_allow_multi_on_quiz;

		$to_array['multi_id'] = $this->generate_quiz_settings_multi_id( $quiz_id );

		// handle multiple form setting.
		if ( $is_allow_multi_on_quiz ) {
			$to_array['multi_ids'] = $this->get_quiz_settings_multi_ids( $quiz_id );
		}

		$to_array_with_quiz = $to_array;
		$addon_slug         = $this->get_slug();
		$addon              = $this;

		/**
		 * Filter array of addon properties
		 *
		 * Including relation with form_id
		 *
		 * @since 1.6.2
		 *
		 * @param array                     $to_array_with_quiz array of addon properties.
		 * @param int                       $quiz_id            Quiz ID.
		 * @param Forminator_Integration $addon              Integration instance.
		 */
		$to_array_with_quiz = apply_filters( 'forminator_addon_' . $addon_slug . '_to_array_with_quiz', $to_array_with_quiz, $quiz_id, $addon );

		return $to_array_with_quiz;
	}

	/**
	 * Override this function if you need to apply some conditional logic on it
	 * By Default this function will check
	 *
	 * @param int $quiz_id Quiz Id.
	 *
	 * @return bool
	 * @since   1.6.2
	 *
	 * @see     Forminator_Integration::settings_wizards()
	 * @see     Forminator_Integration_Quiz_Settings::module_settings_wizards()
	 * as valid multi array
	 */
	public function is_quiz_settings_available( $quiz_id ) {
		$steps      = array();
		$quiz_steps = $this->get_quiz_settings_steps( $quiz_id );

		$steps = array_merge( $steps, $quiz_steps );
		if ( ! is_array( $steps ) ) {
			return false;
		}

		if ( count( $steps ) < 1 ) {
			return false;
		}

		return true;
	}

	/**
	 * Generate multi_id
	 *
	 * @since 1.6.2
	 *
	 * @param int $quiz_id Quiz Id.
	 *
	 * @return array
	 */
	private function generate_quiz_settings_multi_id( $quiz_id ) {
		$addon_slug             = $this->get_slug();
		$addon                  = $this;
		$multi_id               = 0;
		$quiz_settings_instance = $this->get_addon_settings( $quiz_id, 'quiz' );
		if ( $this->is_allow_multi_on_quiz() && ! is_null( $quiz_settings_instance ) && $quiz_settings_instance instanceof Forminator_Integration_Quiz_Settings ) {
			$multi_id = $quiz_settings_instance->generate_multi_id();
		}

		/**
		 * Filter new generated multi id of addon form setting
		 *
		 * @since 1.6.2
		 *
		 * @param string                                  $multi_id
		 * @param Forminator_Integration               $addon                  Integration Instance.
		 * @param Forminator_Integration_Quiz_Settings $quiz_settings_instance Integration Quiz Settings Instance.
		 */
		$multi_ids = apply_filters( 'forminator_addon_' . $addon_slug . '_quiz_settings_multi_id', $multi_id, $addon, $quiz_settings_instance );

		return $multi_ids;
	}

	/**
	 * Connection failed
	 *
	 * @return string
	 */
	final public function connection_failed() {

		/* translators: integration title */
		return sprintf( esc_html__( 'We couldn\'t connect to your %s account. Please resolve the errors below and try again.', 'forminator' ), $this->get_title() );
	}

	/**
	 * Get success authorize content
	 *
	 * @return string
	 */
	protected function success_authorize() {
		ob_start();
		?>
		<div class="forminator-integration-popup__header">
			<h3 id="forminator-integration-popup__title" class="sui-box-title sui-lg" style="overflow: initial; white-space: normal; text-overflow: initial;">
				<?php
				/* translators: 1: Add-on name */
				printf( esc_html__( '%1$s Connected', 'forminator' ), esc_html( $this->get_title() ) );
				?>
			</h3>
		</div>

		<p id="forminator-integration-popup__description" class="sui-description" style="text-align: center;">
			<?php
				printf(
					/* translators: 1: Title */
					esc_html__( 'Awesome! You are connected to %1$s. You can now go to your forms and activate %1$s integration to collect data.', 'forminator' ),
					esc_html( $this->get_title() )
				);
			?>
		</p>

		<div class="forminator-integration-popup__footer-temp">
			<button class="sui-button forminator-addon-close forminator-integration-popup__close">
				<?php esc_html_e( 'Close', 'forminator' ); ?>
			</button>
		</div>
		<?php
		return ob_get_clean();
	}
}
