<?php
/**
 * The Forminator_Mixpanel class.
 *
 * @package Forminator
 */

/**
 * Mixpanel main class.
 */
class Forminator_Mixpanel {

	/**
	 * Mixpanel token for Forminator
	 */
	const TOKEN = '5d545622e3a040aca63f2089b0e6cae7';

	/**
	 * Mixpanel instance.
	 *
	 * @var Mixpanel
	 */
	private $mixpanel = null;

	/**
	 * Plugin instance
	 *
	 * @since  1.27.0
	 * @access private
	 * @var null
	 */
	private static $instance = null;

	/**
	 * General
	 *
	 * @var mixed
	 */
	public $general = null;

	/**
	 * Return the plugin instance
	 *
	 * @return Forminator_mixpanel
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Construct
	 */
	public function __construct() {
		if ( is_null( $this->mixpanel ) ) {
			$extra_options  = array(
				'consumer' => 'socket',
			);
			$this->mixpanel = new WPMUDEV_Analytics_V4( 'forminator', 'Forminator', 55, self::TOKEN, $extra_options );

			// Configure mixpanel.
			$this->mixpanel->identify( $this->identity() );
			$this->mixpanel->registerAll( $this->super_properties() );
		}

		$this->include_init();
		$this->include_class();
	}

	/**
	 * Include initialize
	 *
	 * @return void
	 */
	public function include_init() {
		include_once $this->mixpanel_dir() . 'class-event.php';
		include_once $this->mixpanel_dir() . 'class-settings.php';
		include_once $this->mixpanel_dir() . 'class-general.php';
		include_once $this->mixpanel_dir() . 'class-modules.php';
		include_once $this->mixpanel_dir() . 'class-notifications.php';
		include_once $this->mixpanel_dir() . 'class-feedback.php';
		include_once $this->mixpanel_dir() . 'class-deactivation-survey.php';
		include_once $this->mixpanel_dir() . 'class-hub-deactivation-survey.php';
	}

	/**
	 * Include class
	 *
	 * @return void
	 */
	public function include_class() {
		Forminator_Mixpanel_Settings::init();
		Forminator_Mixpanel_General::init();
		Forminator_Mixpanel_Modules::init();
		Forminator_Mixpanel_Notifications::init();
		Forminator_Mixpanel_Feedback::init();
		Forminator_Mixpanel_Deactivation_Survey::init();
		Forminator_Mixpanel_Hub_Deactivation_Survey::init();
	}

	/**
	 * Get configured mixpanel instance.
	 *
	 * Use this method to make tracking events.
	 *
	 * @return Mixpanel
	 * @since 1.27.0
	 */
	public function tracker() {
		return $this->mixpanel;
	}

	/**
	 * Get unique identity for current site.
	 *
	 * @return string
	 * @since 1.27.0
	 */
	private function identity() {
		$url = str_replace( array( 'http://', 'https://', 'www.' ), '', home_url() );

		return untrailingslashit( $url );
	}

	/**
	 * Get super properties for all events.
	 *
	 * These properties are attached to all events.
	 *
	 * @return array
	 * @since 1.27.0
	 */
	private function super_properties() {
		global $wpdb, $wp_version;

		$properties = array(
			'active_theme'       => get_stylesheet(),
			'locale'             => get_locale(),
			'mysql_version'      => $wpdb->get_var( 'SELECT VERSION()' ), // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			'php_version'        => phpversion(),
			'plugin'             => 'Forminator',
			'plugin_type'        => 'Free',
			'plugin_version'     => FORMINATOR_VERSION,
			'server_type'        => $this->get_server_type(),
			'device'             => $this->get_device(),
			'wp_type'            => is_multisite() ? 'multisite' : 'single',
			'wp_version'         => $wp_version,
			'user_agent'         => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '',
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			'memory_limit'       => ini_get( 'memory_limit' ),
			'max_execution_time' => ini_get( 'max_execution_time' ),
			'competitor_plugin'  => $this->get_competitors(),
		);

		/**
		 * Filter hook to modify super properties.
		 *
		 * @param array $properties Properties.
		 *
		 * @since 1.27.0
		 */
		return apply_filters( 'Forminator_mixpanel_super_properties', $properties ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.NotLowercase
	}

	/**
	 * Get current server type name.
	 *
	 * Only apache and ngnix can be detected.
	 *
	 * @return string
	 * @since 1.27.0
	 */
	private function get_server_type() {
		if ( empty( $_SERVER['SERVER_SOFTWARE'] ) ) {
			return '';
		}

		$server_software = wp_unslash( $_SERVER['SERVER_SOFTWARE'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		if ( ! is_array( $server_software ) ) {
			$server_software = array( $server_software );
		}

		$server_software = array_map( 'strtolower', $server_software );

		if ( $this->array_has_needle( $server_software, 'nginx' ) ) {
			return 'nginx';
		}

		if ( $this->array_has_needle( $server_software, 'apache' ) ) {
			return 'apache';
		}

		return '';
	}

	/**
	 * Is Tablet
	 *
	 * @return bool|string
	 */
	private function is_tablet() {
		if ( empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
			return false;
		}

		$tablet_pattern = '/(tablet|ipad|playbook|kindle|silk)/i';

		return preg_match( $tablet_pattern, sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) );
	}

	/**
	 * Is mobile
	 *
	 * @return bool|string
	 */
	private function is_mobile() {
		if ( empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
			return false;
		}

		$mobile_patten = '/Mobile|iP(hone|od|ad)|Android|BlackBerry|tablet|IEMobile|Kindle|NetFront|Silk|(hpw|web)OS|Fennec|Minimo|Opera M(obi|ini)|Blazer|Dolfin|Dolphin|Skyfire|Zune|playbook/i';

		return preg_match( $mobile_patten, sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) );
	}

	/**
	 * Get current device type.
	 *
	 * @return string
	 */
	protected function get_device() {
		if ( $this->is_mobile() ) {
			return 'mobile';
		}

		if ( $this->is_tablet() ) {
			return 'tablet';
		}

		return 'desktop';
	}

	/**
	 * Get competitor plugins.
	 *
	 * @return string
	 * @since 1.27.0
	 */
	private function get_competitors() {
		$competitors = array();

		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugins = array(
			'wpforms-lite/wpforms.php'             => 'WP Forms',
			'wpforms/wpforms.php'                  => 'WP Forms Pro',
			'ninja-forms/ninja-forms.php'          => 'Ninja Forms',
			'gravityforms/gravityforms.php'        => 'Gravity Forms',
			'contact-form-7/wp-contact-form-7.php' => 'Contact Form 7',
			'formidable/formidable.php'            => 'Formidable Forms',
			'everest-forms/everest-forms.php'      => 'Everest Forms',
			'fluentform/fluentform.php'            => 'Fluent Forms (Contact Form Plugin)',
			'metform/metform.php'                  => 'MetForm',
			'sureforms/sureforms.php'              => 'SureForms',
		);

		foreach ( $plugins as $plugin => $name ) {
			if ( is_plugin_active( $plugin ) || is_plugin_active_for_network( $plugin ) ) {
				$competitors[] = $name;
			}
		}

		return implode( ', ', $competitors );
	}

	/**
	 * Module updates
	 *
	 * @param string $update_type Update type.
	 *
	 * @return array
	 */
	public static function module_updates( $update_type ) {
		$properties       = array(
			'Update Type'        => $update_type,
			'Active Forms'       => forminator_cforms_total( 'publish' ),
			'Forms Submission'   => Forminator_Form_Entry_Model::count_all_entries_by_type( 'custom-forms' ),
			'Active Quizzes'     => forminator_quizzes_total( 'publish' ),
			'Quizzes Submission' => Forminator_Form_Entry_Model::count_all_entries_by_type( 'quizzes' ),
			'Active Polls'       => forminator_polls_total( 'publish' ),
			'Polls Submission'   => Forminator_Form_Entry_Model::count_all_entries_by_type( 'poll' ),
		);
		$addons           = forminator_get_registered_addons_grouped_by_connected();
		$integration_list = array();
		if ( ! empty( $addons['connected'] ) ) {
			foreach ( $addons['connected'] as $connected ) {
				$integration_list[] = esc_html( $connected['title'] );
			}
		}
		$properties['Connected Integration list'] = implode( ', ', $integration_list );

		return $properties;
	}

	/**
	 * Check if array of strings has a string.
	 *
	 * @param array  $haystack Array of strings.
	 * @param string $needle Value to search.
	 *
	 * @return bool
	 * @since 1.27.0
	 */
	private function array_has_needle( $haystack, $needle ) {
		foreach ( $haystack as $item ) {
			if ( strpos( $item, $needle ) !== false ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Mixpanel directory
	 *
	 * @return string
	 */
	public function mixpanel_dir() {
		return plugin_dir_path( __FILE__ );
	}
}
