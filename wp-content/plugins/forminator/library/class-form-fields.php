<?php
/**
 * Forminator Fields
 *
 * @package Forminator
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_Fields
 *
 * @since 1.0
 */
class Forminator_Fields {
	/**
	 * Store fields objects
	 *
	 * @var array
	 */
	public $fields = array();

	/**
	 * Forminator_Fields constructor.
	 *
	 * @since 1.0
	 */
	public function __construct() {

		$this->load_forminator_autofill_providers();
		$this->maybe_load_external_autofill_providers();

		$loader = new Forminator_Loader();

		$fields = $loader->load_files(
			'library/fields',
			array(
				'stripe.php' => array(
					'php' => '5.6.0',
				),
			)
		);

		/**
		 * Filters the form fields
		 */
		$this->fields = apply_filters( 'forminator_fields', $fields );

		add_action( 'init', array( &$this, 'schedule_forminator_daily_cron' ) );

		add_action( 'forminator_daily_cron', array( &$this, 'cron_init' ) );

		add_action( 'forminator_update_version', array( &$this, 'upgrade_actions' ), 10, 2 );
	}

	/**
	 * Retrieve fields objects
	 *
	 * @since 1.0
	 * @return array
	 */
	public function get_fields() {
		return $this->fields;
	}

	/**
	 * Load autofill providers requirements
	 *
	 * @since 1.0.5
	 */
	public function load_forminator_autofill_providers() {
		include_once forminator_plugin_dir() . 'library/class-autofill-loader.php';
		$required_files = array(
			// load contracts.
			forminator_plugin_dir() . 'library/field-autofill-providers/contracts/class-autofill-provider-interface.php',
			forminator_plugin_dir() . 'library/field-autofill-providers/contracts/class-autofill-provider-abstract.php',
			// load Forminator provider autoload.
			forminator_plugin_dir() . 'library/field-autofill-providers/autoload.php',
		);

		$required_files_exists = true;
		foreach ( $required_files as $required_file ) {
			if ( ! file_exists( $required_file ) ) {
				$required_files_exists = false;
				break;
			}
		}

		if ( $required_files_exists ) {
			foreach ( $required_files as $required_file ) {
				/* @noinspection PhpIncludeInspection */
				include_once $required_file;
			}
		}
	}

	/**
	 * Load member's autofill provider
	 *
	 * @since 1.0.5
	 */
	public function maybe_load_external_autofill_providers() {
		// See samples/forminator-simple-autofill-plugin for example how to use it.
		do_action( 'forminator_register_autofill_provider' );
	}

	/**
	 * Set up the schedule delete file
	 *
	 * @since 1.13
	 * @since 1.27 Change from WP cron to Action Scheduler
	 */
	public function schedule_forminator_daily_cron() {
		forminator_set_recurring_action( 'forminator_daily_cron', DAY_IN_SECONDS );
	}

	/**
	 * Forminator Cron
	 *
	 * @since 1.13
	 */
	public function cron_init() {
		$this->schedule_delete_temp_files();
	}

	/**
	 * Schedule delete temp file
	 *
	 * @since 1.13
	 */
	public function schedule_delete_temp_files() {
		$temp_path = forminator_upload_root_temp();
		if ( is_wp_error( $temp_path ) ) {
			return;
		}
		$temp_path = $temp_path . '/';
		$handle    = @opendir( $temp_path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		if ( $handle ) {
			// Check if the dir exist before opening it.
			if ( is_dir( $temp_path ) ) {
				$handle = opendir( $temp_path );
				if ( $handle ) {
					while ( false !== ( $file = readdir( $handle ) ) ) { // phpcs:ignore Generic.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition -- false positive
						if ( ! empty( $file ) && ! in_array( $file, array( '.', '..' ), true ) ) {
							$temp_file = $temp_path . $file;
							$file_time = filemtime( $temp_file );
							if ( file_exists( $temp_file ) && ( time() - $file_time ) >= 60 * 60 * 12 ) {
								wp_delete_file( $temp_file );
							}
						}
					}
					closedir( $handle );
				}
			}
		}
	}

	/**
	 * Upgrade actions
	 */
	public function upgrade_actions() {
		if ( ! forminator_create_index_file_disabled() ) {
			$this->add_security_files();
		}
		$this->delete_old_temp_dir();
	}

	/**
	 * Delete old temp dir
	 *
	 * @return void|null
	 */
	public function delete_old_temp_dir() {
		$dir       = wp_upload_dir();
		$temp_path = trailingslashit( $dir['basedir'] ) . 'forminator_temp';

		if ( $dir['error'] || ! is_dir( $temp_path ) ) {

			return null;
		}

		// Ensure the WP_Filesystem is initialized.
		if ( ! function_exists( 'wp_filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		WP_Filesystem();

		// Global $wp_filesystem should be available now.
		global $wp_filesystem;

		// remove `css` folder.
		if ( $wp_filesystem->is_dir( $temp_path ) ) {
			$wp_filesystem->rmdir( $temp_path );
		}
	}

	/**
	 * Adds index files to the upload root for security.
	 *
	 * @return void
	 */
	public function add_security_files() {

		$upload_root = forminator_upload_root();

		if ( is_wp_error( $upload_root ) || ! is_dir( $upload_root ) ) {
			return;
		}

		Forminator_Field::check_upload_root_index_file();

		if ( ! file_exists( $upload_root . 'css/index.php' ) ) {
			Forminator_Field::add_index_file( $upload_root . 'css/index.php' );
		}
		if ( ! file_exists( $upload_root . 'signatures/index.php' ) ) {
			Forminator_Field::add_index_file( $upload_root . 'signatures/index.php' );
		}
	}
}
