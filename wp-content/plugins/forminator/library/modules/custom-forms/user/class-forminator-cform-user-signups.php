<?php
/**
 * The Forminator_CForm_User_Signups class.
 *
 * @package Forminator
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Front user class for signups
 *
 * @since 1.11
 */
class Forminator_CForm_User_Signups {

	/**
	 * Meta
	 *
	 * @var array
	 */
	public $meta;

	/**
	 * Entry
	 *
	 * @var Forminator_Form_Entry_Model
	 */
	public $entry;

	/**
	 * Form
	 *
	 * @var Forminator_Base_Form_Model
	 */
	public $form;

	/**
	 * Settings
	 *
	 * @var array
	 */
	public $settings;

	/**
	 * Submitted data
	 *
	 * @var array
	 */
	public $submitted_data;

	/**
	 * User data
	 *
	 * @var array
	 */
	public $user_data;

	/**
	 * Dynamic properties.
	 *
	 * @var array
	 */
	private $properties = array();

	/**
	 * Magic method to handle setting dynamic properties
	 *
	 * @param string $name Property name.
	 * @param mixed  $value Property value.
	 */
	public function __set( $name, $value ) {
		$this->properties[ $name ] = $value;
	}

	/**
	 * Magic method to handle getting dynamic properties
	 *
	 * @param string $name Property name.
	 *
	 * @return mixed
	 */
	public function __get( $name ) {
		return $this->properties[ $name ] ?? null;
	}

	/**
	 * Forminator_CForm_User_Signups constructor
	 *
	 * @param array $signup Signup.
	 */
	public function __construct( $signup ) {
		// This is for internal variables (i.e. activation key).
		foreach ( $signup as $key => $value ) {
			$this->$key = $value;
		}

		$this->meta     = maybe_unserialize( $signup->meta );
		$this->entry    = new Forminator_Form_Entry_Model( $this->meta['entry_id'] );
		$this->form     = Forminator_Base_Form_Model::get_model( $this->meta['form_id'] );
		$this->settings = $this->form->settings;
		// Don't use null coalescing operator for PHP version 5.6.*.
		$this->user_data      = isset( $this->meta['user_data'] ) ? $this->meta['user_data'] : array();
		$this->submitted_data = array();
		if ( isset( $this->meta['prepared_data'] ) ) {
			$this->submitted_data = $this->meta['prepared_data'];
		} elseif ( isset( $this->meta['submitted_data'] ) ) {
			$this->submitted_data = $this->meta['submitted_data'];
		}
	}

	/**
	 * Get
	 *
	 * @param mixed $key Key.
	 *
	 * @return Forminator_CForm_User_Signups|WP_Error
	 */
	public static function get( $key ) {
		if ( ! is_multisite() ) {
			self::create_signups_table();
		}
		global $wpdb;

		$signup = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->signups WHERE activation_key = %s", $key ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery

		if ( empty( $signup ) ) {
			return new WP_Error( 'invalid_key', esc_html__( 'Invalid activation key.', 'forminator' ) );
		}
		if ( $signup->active ) {
			return new WP_Error( 'already_active', esc_html__( 'The user is already active.', 'forminator' ), $signup );
		}

		return new Forminator_CForm_User_Signups( $signup );
	}

	/**
	 * Get activation method
	 *
	 * @return mixed
	 */
	public function get_activation_method() {

		return ( isset( $this->settings['activation-method'] ) && ! empty( $this->settings['activation-method'] ) )
			? $this->settings['activation-method']
			: '';
	}

	/**
	 * Set as activated
	 *
	 * @return bool|int
	 */
	public function set_as_activated() {
		global $wpdb;

		// Remove password for security.
		$this->meta['user_data']['user_pass'] = '';
		$this->meta['prepared_data']          = '';

		$now = current_time( 'mysql', true );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $wpdb->update(
			$wpdb->signups,
			array(
				'active'    => 1,
				'activated' => $now,
				'meta'      => maybe_serialize( $this->meta ),
			),
			array( 'activation_key' => $this->activation_key )
		);

		return $result;
	}

	/**
	 * Get activation email
	 *
	 * @param mixed $key Key.
	 * @return mixed
	 */
	public static function get_activation_email( $key ) {
		$signup = self::get( $key );
		return ! empty( $signup->settings['activation-email'] )
			? $signup->settings['activation-email']
			: 'default';
	}

	/**
	 * Check to exist table
	 *
	 * @param string $table_name Table name.
	 *
	 * @return bool
	 */
	public static function table_exists( $table_name ) {
		global $wpdb;

		return (bool) $wpdb->get_results( "SHOW TABLES LIKE '{$table_name}'" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery
	}

	/**
	 * Create Signups table
	 */
	public static function create_signups_table() {
		global $wpdb;

		self::add_signups_to_wpdb();

		$table_name = $wpdb->signups;
		if ( self::table_exists( $table_name ) ) {
			$column_exists = $wpdb->query( "SHOW COLUMNS FROM {$table_name} LIKE 'signup_id'" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery
			if ( empty( $column_exists ) ) {
				// New primary key for signups.
				$wpdb->query( "ALTER TABLE {$table_name} ADD signup_id BIGINT(20) NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.DirectQuery
				$wpdb->query( "ALTER TABLE {$table_name} DROP INDEX domain" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.DirectQuery
			}
		}

		self::install_signups();
	}

	/**
	 * Install signups
	 *
	 * @return void
	 */
	private static function install_signups() {
		global $wpdb;

		// Signups is not there and we need it so let's create it.
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// Use WP's core CREATE TABLE query.
		$create_queries = wp_get_db_schema( 'ms_global' );
		if ( ! is_array( $create_queries ) ) {
			$create_queries = explode( ';', $create_queries );
			$create_queries = array_filter( $create_queries );
		}

		// Filter out all the queries except wp_signups.
		foreach ( $create_queries as $key => $query ) {
			if ( preg_match( '|CREATE TABLE ([^ ]*)|', $query, $matches ) ) {
				if ( trim( $matches[1], '`' ) !== $wpdb->signups ) {
					unset( $create_queries[ $key ] );
				}
			}
		}

		// Run WordPress's database upgrader.
		if ( ! empty( $create_queries ) ) {
			$result = dbDelta( $create_queries );
		}
	}

	/**
	 * Add signups property to $wpdb object. Used by several MS functions.
	 */
	private static function add_signups_to_wpdb() {
		global $wpdb;
		$wpdb->signups = $wpdb->base_prefix . 'signups';
	}

	/**
	 * Delete
	 *
	 * @return bool|int
	 */
	public function delete() {
		global $wpdb;

		return $wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->signups WHERE activation_key = %s", $this->activation_key ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
	}

	/**
	 * Delete signup entry by user data
	 *
	 * @param string $user_key User key.
	 * @param string $user_value Value.
	 *
	 * @return int|bool
	 */
	public static function delete_by_user( $user_key, $user_value ) {
		global $wpdb;

		self::add_signups_to_wpdb();

		if ( ! self::table_exists( $wpdb->signups ) ) {
			return true;
		}
		return $wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->signups WHERE " . esc_sql( $user_key ) . ' = %s', $user_value ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery
	}

	/**
	 * Prep signups functionality
	 *
	 * @return void
	 */
	public static function prep_signups_functionality() {
		if ( ! is_multisite() ) {
			// require MS functions.
			require_once ABSPATH . 'wp-includes/ms-functions.php';

			self::create_signups_table();

			// remove filter which checks for Network setting (not active on non-ms install).
			remove_filter( 'option_users_can_register', 'users_can_register_signup_filter' );
		}

		// Update the signup URL.
		add_filter( 'wpmu_signup_user_notification_email', array( 'Forminator_CForm_User_Signups', 'modify_signup_user_notification_message' ), 10, 4 );
		add_filter( 'wpmu_signup_blog_notification_email', array( 'Forminator_CForm_User_Signups', 'modify_signup_blog_notification_message' ), 10, 7 );

		// Disable activation email for manual activation method.
		add_filter( 'wpmu_signup_user_notification', array( 'Forminator_CForm_User_Signups', 'maybe_suppress_signup_user_notification' ), 10, 3 );
		add_filter( 'wpmu_signup_blog_notification', array( 'Forminator_CForm_User_Signups', 'maybe_suppress_signup_blog_notification' ), 10, 6 );

		add_filter( 'wpmu_signup_user_notification', array( 'Forminator_CForm_User_Signups', 'add_site_name_filter' ) );
		add_filter( 'wpmu_signup_user_notification_subject', array( 'Forminator_CForm_User_Signups', 'remove_site_name_filter' ) );
	}

	/**
	 * Get pending activations
	 *
	 * @param string $activation_key Activation key.
	 *
	 * @return array|object|null
	 */
	public static function get_pending_activations( $activation_key ) {
		// Create table Signups for non-multisite installs.
		if ( ! is_multisite() ) {
			self::create_signups_table();
		}
		global $wpdb;

		return $wpdb->get_row( $wpdb->prepare( "SELECT signup_id FROM {$wpdb->signups} WHERE activation_key = %s AND active = 0", $activation_key ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
	}

	/**
	 * May be suppress signup user notification
	 *
	 * @param mixed  $user User.
	 * @param string $user_email Email.
	 * @param string $key Key.
	 * @return mixed
	 */
	public static function maybe_suppress_signup_user_notification( $user, $user_email, $key ) {
		return self::is_manual_activation( $key ) ? false : $user;
	}

	/**
	 * May be suppress signup blog notification
	 *
	 * @param mixed $domain Domain.
	 * @param mixed $path Path.
	 * @param mixed $title Title.
	 * @param mixed $user User.
	 * @param mixed $user_email User email.
	 * @param mixed $key Key.
	 * @return mixed
	 */
	public static function maybe_suppress_signup_blog_notification( $domain, $path, $title, $user, $user_email, $key ) {
		return self::is_manual_activation( $key ) ? false : $user;
	}

	/**
	 * Is manual activation
	 *
	 * @param string $key Key.
	 * @return bool
	 */
	public static function is_manual_activation( $key ) {
		$signup = self::get( $key );

		return ! is_wp_error( $signup ) && 'manual' === $signup->get_activation_method();
	}

	/**
	 * Modify signup user notification message
	 *
	 * @param mixed $message Message.
	 * @param mixed $user User.
	 * @param mixed $user_email User email.
	 * @param mixed $key Key.
	 * @return string
	 */
	public static function modify_signup_user_notification_message( $message, $user, $user_email, $key ) {
		if ( 'none' === self::get_activation_email( $key ) ) {
			/* translators: New user notification email. %s: Activation URL. */
			$message = esc_html__( "To activate your user, please click the following link:\n\n%s\n", 'forminator' );
		}
		$url = add_query_arg(
			array(
				'page' => 'account_activation',
				'key'  => $key,
			),
			home_url( '/' )
		);

		return sprintf( $message, esc_url_raw( $url ) );
	}

	/**
	 * Modify signup blog notification message
	 *
	 * @param mixed $message Message.
	 * @param mixed $domain Domain.
	 * @param mixed $path Path.
	 * @param mixed $title Tittle.
	 * @param mixed $user User.
	 * @param mixed $user_email User email.
	 * @param mixed $key Key.
	 * @return string
	 */
	public static function modify_signup_blog_notification_message( $message, $domain, $path, $title, $user, $user_email, $key ) {
		if ( 'none' === self::get_activation_email( $key ) ) {
			/* translators: New site notification email. 1: Activation URL, 2: New site URL. */
			$message = esc_html__( "To activate your site, please click the following link:\n\n%1\$s\n\nAfter you activate, you can visit your site here:\n\n%2\$s", 'forminator' );
		}

		$url = add_query_arg(
			array(
				'page' => 'account_activation',
				'key'  => $key,
			),
			home_url( '/' )
		);

		return sprintf( $message, esc_url_raw( $url ), esc_url( "http://{$domain}{$path}" ), $key );
	}

	/**
	 * Add site name filter
	 *
	 * @param mixed $user User.
	 * @return mixed
	 */
	public static function add_site_name_filter( $user ) {
		add_filter( 'site_option_site_name', array( __CLASS__, 'modify_site_name' ) );

		return $user;
	}

	/**
	 * Remove site name filter.
	 *
	 * @param mixed $user User.
	 * @return mixed
	 */
	public static function remove_site_name_filter( $user ) {
		remove_filter( 'site_option_site_name', array( __CLASS__, 'modify_site_name' ) );

		return $user;
	}

	/**
	 * Modify site name
	 *
	 * @param mixed $site_name Site name.
	 * @return mixed
	 */
	public static function modify_site_name( $site_name ) {
		if ( ! $site_name ) {
			$site_name = get_site_option( 'blogname' );
		}

		return $site_name;
	}

	/**
	 * Add meta of a user sign-up
	 *
	 * @param Forminator_Form_Entry_Model $entry Form entry model.
	 * @param string                      $meta_key Meta key name.
	 * @param string                      $meta_value Meta value.
	 */
	public static function add_signup_meta( $entry, $meta_key, $meta_value ) {
		$entry->set_fields(
			array(
				array(
					'name'  => $meta_key,
					'value' => $meta_value,
				),
			)
		);
	}

	/**
	 * Activate signup
	 *
	 * @param string $key Key.
	 * @param bool   $is_user_signon Is user sign-on.
	 *
	 * @return array|Forminator_CForm_User_Signups|WP_Error
	 */
	public static function activate_signup( $key, $is_user_signon ) {
		global $wpdb, $current_site;

		$blog_id = is_object( $current_site ) ? $current_site->id : false;
		$signup  = self::get( $key );
		if ( is_wp_error( $signup ) ) {
			return $signup;
		}

		// Do not allow if it is a backend request and the user lacks access to the specified user role.
		if ( $is_user_signon && ! forminator_can_approve_user_and_create_site( $signup ) ) {
			return new WP_Error( 'invalid_access', esc_html__( 'Unfortunately, you do not have the required permissions or user role to perform this action.', 'forminator' ), $signup );
		}

		$user_id = username_exists( $signup->user_data['user_login'] );
		if ( $user_id ) {
			// User already exists.
			$signup->set_as_activated();

			return new WP_Error( 'user_already_exists', esc_html__( 'That username is already activated.', 'forminator' ), $signup );
		}

		if ( email_exists( $signup->user_data['user_email'] ) ) {
			// Email already exists.
			return new WP_Error( 'email_already_exists', esc_html__( 'Sorry, that email address is already used!', 'forminator' ), $signup );
		}

		if ( forminator_is_main_site() ) {
			remove_action( 'forminator_cform_user_registered', array( 'Forminator_CForm_Front_User_Registration', 'create_site' ) );
		}

		$password = Forminator_CForm_Front_User_Registration::openssl_decrypt( $signup->user_data['user_pass'] );

		$forminator_user_registration = new Forminator_CForm_Front_User_Registration();
		$user_data                    = $signup->user_data;
		$user_data['user_pass']       = $password;
		if ( ! is_array( $user_data ) ) {

			return new WP_Error( 'create_user', $user_data );
		}
		Forminator_CForm_Front_Action::$prepared_data = $signup->submitted_data;
		// For decrypted password.
		$forminator_user_registration->change_submitted_data( $password );

		$user_id = $forminator_user_registration->create_user( $user_data, $signup->form, $signup->entry, $is_user_signon );
		if ( ! $user_id ) {
			return new WP_Error( 'create_user', esc_html__( 'Could not create user', 'forminator' ), $signup );
		}

		$signup->set_as_activated();

		do_action( 'forminator_activate_user', $user_id, $signup->meta );

		if ( ! empty( $signup->form->id ) ) {
			// Delete the cache for the form entries query.
			Forminator_Form_Entry_Model::delete_form_entries_query_cache( $signup->form->id );
		}

		if ( isset( $signup->settings['activation-method'] )
			&& 'manual' === $signup->settings['activation-method']
			&& ! current_user_can( forminator_get_permission( 'forminator-entries' ) )
		) {
			return new WP_Error( 'user_activated', esc_html__( 'User account has been activated.', 'forminator' ), $signup );
		}

		// Create site only on main site and if option for that is enabled.
		if ( forminator_is_main_site() ) {
			$option_create_site = forminator_get_property( $signup->settings, 'site-registration' );
			if ( isset( $option_create_site ) && 'enable' === $option_create_site ) {
				$forminator_user_registration->create_site( $user_id, $signup->form, $signup->entry, $password );
			}
		}

		$result = array(
			'user_id'  => $user_id,
			'blog_id'  => $blog_id,
			'form_id'  => $signup->form->id,
			'entry_id' => $signup->entry->entry_id,
		);
		// Redirected page for Email-activation method.
		if ( isset( $signup->settings['activation-method'], $signup->settings['confirmation-page'] )
			&& 'email' === $signup->settings['activation-method']
			&& ! empty( $signup->settings['confirmation-page'] )
		) {
			$result['redirect_page'] = $signup->settings['confirmation-page'];
		}

		return $result;
	}

	/**
	 * Delete signup
	 *
	 * @param mixed $key Key.
	 *
	 * @return bool|int|WP_Error
	 */
	public static function delete_signup( $key ) {
		if ( ! current_user_can( 'delete_users' ) ) {
			return new WP_Error( 'invalid_access', esc_html__( 'Unfortunately, you do not have the required permissions or user role to perform this action.', 'forminator' ) );
		}
		$signup = self::get( $key );
		if ( is_wp_error( $signup ) ) {
			return $signup;
		}

		do_action( 'forminator_cform_userregistration_delete_signup', $signup );

		return $signup->delete();
	}
}
