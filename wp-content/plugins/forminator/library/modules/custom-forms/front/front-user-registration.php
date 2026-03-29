<?php
/**
 * The Forminator_CForm_Front_User_Registration class.
 *
 * @package Forminator
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Front user class for custom registration forms
 *
 * @since 1.11
 */
class Forminator_CForm_Front_User_Registration extends Forminator_User {

	/**
	 * User data
	 *
	 * @var array
	 */
	private $user_data = array();

	/**
	 * Mail sender
	 *
	 * @var string
	 */
	private $mail_sender;

	/**
	 * Forminator_CForm_Front_User_Registration constructor
	 */
	public function __construct() {
		parent::__construct();

		$this->mail_sender = new Forminator_CForm_Front_Mail();

		if ( forminator_is_main_site() ) {
			add_action( 'forminator_cform_user_registration_validation', array( $this, 'multisite_validation' ), 10, 3 );
			add_action( 'forminator_cform_user_registered', array( $this, 'create_site' ), 10, 4 );
		}
		add_filter( 'forminator_custom_registration_form_errors', array( $this, 'submit_errors' ), 11, 3 );
		// Change value of a field that is not saved in DB.
		add_filter( 'forminator_custom_form_after_render_value', array( $this, 'change_field_value' ), 11, 4 );
	}

	/**
	 * Change submitted data
	 *
	 * @param string $value Field value.
	 * @param array  $custom_form Custom form.
	 * @param string $column_name Column name.
	 * @param array  $data Data.
	 *
	 * @return string
	 */
	public function change_field_value( $value, $custom_form, $column_name, $data ) {
		if ( ! $value
			&& isset( $custom_form->settings['form-type'] )
			&& 'registration' === $custom_form->settings['form-type']
			&& isset( $column_name )
		) {
			$value = isset( $data[ $column_name ] ) ? $data[ $column_name ] : '';
		}

		return $value;
	}

	/**
	 * Check activation method
	 *
	 * @param string $method Method.
	 *
	 * @return bool
	 */
	private function check_activation_method( $method ) {
		return in_array( $method, array( 'email', 'manual' ) );// phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
	}

	/**
	 * Show submit_errors
	 *
	 * @param string $submit_errors Error message.
	 * @param int    $form_id Form Id.
	 * @param array  $field_data_array Field data.
	 *
	 * @return bool|string
	 */
	public function submit_errors( $submit_errors, $form_id, $field_data_array ) {
		$custom_form = Forminator_Base_Form_Model::get_model( $form_id );
		$settings    = $custom_form->settings;

		$username   = '';
		$user_email = '';
		if ( isset( $settings['registration-username-field'] ) && ! empty( $settings['registration-username-field'] ) ) {
			$username = $this->replace_value( $field_data_array, $settings['registration-username-field'] );
		}
		if ( isset( $settings['registration-email-field'] ) && ! empty( $settings['registration-email-field'] ) ) {
			$user_email = $this->replace_value( $field_data_array, $settings['registration-email-field'] );
		}

		// Additional processing of multisite installs.
		if ( is_multisite() ) {
			// Convert username to lowercase.
			$username = strtolower( $username );

			$result = wpmu_validate_user_signup( $username, $user_email );
			$errors = $result['errors']->errors;

			// Check if there are any errors.
			if ( ! empty( $errors ) ) {
				foreach ( $errors as $type => $error_msgs ) {
					foreach ( $error_msgs as $error_msg ) {
						// Depending on the error type, display a different validation error.
						switch ( $type ) {
							case 'user_name':
							case 'user_email':
								$submit_errors = $error_msg;
								break;
							default:
								break;
						}
					}
				}

				return $submit_errors;
			}
		}

		return true;
	}

	/**
	 * Change submitted data
	 *
	 * @param string $new_value New value.
	 */
	public function change_submitted_data( $new_value ) {
		foreach ( Forminator_CForm_Front_Action::$prepared_data as $field_key => $field_value ) {
			if ( false !== stripos( $field_key, 'password-' ) ) {
				Forminator_CForm_Front_Action::$prepared_data[ $field_key ] = $new_value;
			}
		}
	}

	/**
	 * Handle activation user
	 *
	 * @param array                       $user_data User data.
	 * @param array                       $custom_form Custom form.
	 * @param Forminator_Form_Entry_Model $entry Form entry model.
	 *
	 * @return bool|void
	 */
	public function handle_user_activation( $user_data, $custom_form, $entry ) {
		global $wpdb;

		require_once __DIR__ . '/../user/class-forminator-cform-user-signups.php';

		Forminator_CForm_User_Signups::prep_signups_functionality();

		$settings = $custom_form->settings;
		// For password security.
		$prepare_user_data              = $user_data;
		$encrypted_password             = self::openssl_encrypt( $user_data['user_pass'] );
		$prepare_user_data['user_pass'] = $encrypted_password;
		$this->change_submitted_data( $encrypted_password );

		$meta = array(
			'form_id'       => $entry->form_id,
			'entry_id'      => $entry->entry_id,
			'prepared_data' => Forminator_CForm_Front_Action::$prepared_data,
			'user_data'     => $prepare_user_data,
		);

		// Change default text of notifications for other activation methods: 'email' && 'manual'.
		if ( ! empty( $custom_form->notifications ) ) {
			$custom_form->notifications = $this->change_notifications( $settings['activation-method'], $custom_form->notifications );
		}
		// Sending notifications before saving activation_key.
		if ( 'email' === $settings['activation-method'] ) {
			$this->mail_sender->process_mail( $custom_form, $entry );
		}

		$option_create_site = forminator_get_property( $settings, 'site-registration' );
		$site_data          = is_multisite() ? $this->get_site_data( $settings, 0, $user_data ) : array();
		if ( is_multisite()
			&& isset( $option_create_site )
			&& 'enable' === $option_create_site
			&& $site_data
		) {
			if ( ! has_action( 'after_signup_site', 'wpmu_signup_blog_notification' ) ) {
				add_action( 'after_signup_site', 'wpmu_signup_blog_notification', 10, 7 );
			}

			wpmu_signup_blog( $site_data['domain'], $site_data['path'], $site_data['title'], $user_data['user_login'], $user_data['user_email'], $meta );
		} else {
			$user_data['user_login'] = preg_replace( '/\s+/', '', sanitize_user( $user_data['user_login'], true ) );

			if ( ! has_action( 'after_signup_user', 'wpmu_signup_user_notification' ) ) {
				add_action( 'after_signup_user', 'wpmu_signup_user_notification', 10, 4 );
			}

			wpmu_signup_user( $user_data['user_login'], $user_data['user_email'], $meta );
		}
		$activation_key = $wpdb->get_var( $wpdb->prepare( "SELECT activation_key FROM {$wpdb->base_prefix}signups WHERE user_login = %s ORDER BY registered DESC LIMIT 1", $user_data['user_login'] ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery

		// used for filtering on activation listing UI.
		Forminator_CForm_User_Signups::add_signup_meta( $entry, 'activation_method', $settings['activation-method'] );
		Forminator_CForm_User_Signups::add_signup_meta( $entry, 'activation_key', $activation_key );

		// Sending notifications with {account_approval_link} after saving activation_key.
		if ( 'manual' === $settings['activation-method'] ) {
			$this->mail_sender->process_mail( $custom_form, $entry );
		}

		return true;
	}

	/**
	 * Create user
	 *
	 * @param array                       $new_user_data New user data.
	 * @param array                       $custom_form Custom form.
	 * @param Forminator_Form_Entry_Model $entry Form entry model.
	 * @param bool                        $is_user_signon Is user sign-on.
	 *
	 * @return int|string|void|WP_Error
	 */
	public function create_user( $new_user_data, $custom_form, $entry, $is_user_signon = false ) {
		$new_user_data = apply_filters( 'forminator_custom_form_user_registration_before_insert', $new_user_data, $custom_form, $entry );

		$user_id = wp_insert_user( $new_user_data );
		if ( is_wp_error( $user_id ) ) {

			return esc_html__( 'Couldn&#8217;t register you&hellip; please contact us if you continue to have problems.', 'forminator' );
		}

		$settings = $custom_form->settings;
		$this->add_user_meta( $user_id, $settings, $custom_form, $entry );

		if ( ! $this->check_activation_method( $settings['activation-method'] ) ) {
			// Sending notification.
			$this->mail_sender->process_mail( $custom_form, $entry );
		}

		if ( isset( $settings['activation-email'] ) && 'default' === $settings['activation-email'] ) {

			if ( isset( $settings['registration-password-field'] ) && 'auto' === $settings['registration-password-field'] ) {
				$this->forminator_new_user_notification( $user_id, $new_user_data['user_pass'], 'both' );
			} else {
				$this->forminator_new_user_notification( $user_id, '', 'both' );
			}
		} else {
			// Send notification to admin.
			$this->forminator_new_user_notification( $user_id, '', 'admin' );
		}

		do_action( 'forminator_cform_user_registered', $user_id, $custom_form, $entry, $new_user_data['user_pass'] );

		if ( ! $is_user_signon && isset( $settings['automatic-login'] ) && ! empty( $settings['automatic-login'] ) ) {
			$this->automatic_login( $user_id );
		}

		return $user_id;
	}

	/**
	 * Check a pending activation for the specified user_login or user_email.
	 *
	 * @param string $key user_login or user_email.
	 * @param string $value Value.
	 *
	 * @return bool
	 */
	public function pending_activation_exists( $key, $value ) {
		global $wpdb;

		require_once __DIR__ . '/../user/class-forminator-cform-user-signups.php';

		$table_name = $wpdb->base_prefix . 'signups';

		if ( Forminator_CForm_User_Signups::table_exists( $table_name ) && in_array( $key, array( 'user_login', 'user_email' ) ) ) {// phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
			if ( 'user_login' === $key ) {
				$value = preg_replace( '/\s+/', '', sanitize_user( $value, true ) );
			}

			$result = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE active=0 AND " . esc_sql( $key ) . '=%s', $value ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery
			if ( ! is_null( $result ) ) {
				$diff = time() - mysql2date( 'U', $result->registered );
				// If registered more than two days ago, cancel registration and delete this signup.
				if ( $diff > 2 * DAY_IN_SECONDS ) {
					return (bool) $wpdb->delete( $table_name, array( $key => $value ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				} else {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Validate registration mapping data
	 *
	 * @param object $custom_form Custom form.
	 * @param array  $field_data_array Field data.
	 * @param bool   $is_approve Is approve.
	 *
	 * @return array
	 */
	public function validate( $custom_form, $field_data_array, $is_approve = false ) {
		$settings = $custom_form->settings;

		// Field username.
		$username = '';
		if ( isset( $settings['registration-username-field'] ) && ! empty( $settings['registration-username-field'] ) ) {
			$username = $this->replace_value( $field_data_array, $settings['registration-username-field'] );
		}
		$validate = $this->validate_username( $username );
		if ( ! $validate['result'] ) {

			return $validate['message'];
		}
		// Username is valid, but has already pending activation.
		if ( ! is_multisite() && $this->pending_activation_exists( 'user_login', $username ) ) {

			return esc_html__( 'That username is currently reserved but may be available in a couple of days', 'forminator' );
		}

		// Field user email.
		$user_email = '';
		if ( isset( $settings['registration-email-field'] ) && ! empty( $settings['registration-email-field'] ) ) {
			$user_email = $this->replace_value( $field_data_array, $settings['registration-email-field'] );
		}
		$validate = $this->validate_email( $user_email );
		if ( ! $validate['result'] ) {

			return $validate['message'];
		}
		// Email is valid, but has already pending activation.
		if ( ! is_multisite() && $this->pending_activation_exists( 'user_email', $user_email ) ) {

			return esc_html__( 'That email address has already been used. Please check your inbox for an activation email. It will become available in a couple of days if you do nothing.', 'forminator' );
		}

		// Multisite validation.
		$validate = apply_filters( 'forminator_cform_user_registration_validation', $validate, $custom_form, Forminator_CForm_Front_Action::$prepared_data, $is_approve );
		if ( ! $validate['result'] ) {

			return $validate['message'];
		}

		// Field password.
		$password = '';
		if ( isset( $settings['registration-password-field'] ) && ! empty( $settings['registration-password-field'] ) ) {
			if ( 'auto' === $settings['registration-password-field'] ) {
				$password = wp_generate_password();
			} else {
				$password = $this->replace_value( $field_data_array, $settings['registration-password-field'] );
			}
		} else {
			foreach ( $field_data_array as $key => $field_arr ) {
				if ( false !== stripos( $field_arr['name'], 'password-' ) ) {
					$password = $field_arr['value'];
					break;
				}
			}
		}

		// Check password length.
		if ( 255 < mb_strlen( $password ) ) {
			return esc_html__( 'User password may not be longer than 255 characters.', 'forminator' );
		}

		$new_user_data = array(
			'user_login' => $username,
			'user_pass'  => $password,
			'user_email' => $user_email,
		);

		// Field first name.
		if ( isset( $settings['registration-first-name-field'] ) && ! empty( $settings['registration-first-name-field'] ) ) {
			$new_user_data['first_name'] = $this->replace_value( $field_data_array, $settings['registration-first-name-field'] );
		}

		// Field last name.
		if ( isset( $settings['registration-last-name-field'] ) && ! empty( $settings['registration-last-name-field'] ) ) {
			$new_user_data['last_name'] = $this->replace_value( $field_data_array, $settings['registration-last-name-field'] );
		}
		// Field website.
		if ( isset( $settings['registration-website-field'] ) && ! empty( $settings['registration-website-field'] ) ) {
			$new_user_data['user_url'] = $this->replace_value( $field_data_array, $settings['registration-website-field'] );

			if ( 100 < mb_strlen( $new_user_data['user_url'] ) ) {
				return esc_html__( 'User website URL may not be longer than 100 characters.', 'forminator' );
			}
		}

		// Field user role.
		$registration_user_role = isset( $settings['registration-user-role'] ) ? $settings['registration-user-role'] : 'fixed';
		if ( 'conditionally' === $registration_user_role ) {
			$new_user_data['role'] = $this->conditional_user_role( $settings );
		} else {
			$new_user_data['role'] = $settings['registration-role-field'];
		}

		return $new_user_data;
	}

	/**
	 * Process validation
	 *
	 * @param Forminator_Form_Model $custom_form Custom form.
	 * @param array                 $field_data_array Field data.
	 *
	 * @return array|mixed
	 */
	public function process_validation( $custom_form, $field_data_array ) {
		$user_data = $this->validate( $custom_form, $field_data_array );
		if ( ! is_array( $user_data ) ) {
			return $user_data;
		}

		$this->user_data = $user_data;

		return true;
	}

	/**
	 * Process registration
	 *
	 * @param Forminator_Form_Model       $custom_form Form model.
	 * @param Forminator_Form_Entry_Model $entry Form Entry model.
	 *
	 * @return array|mixed
	 */
	public function process_registration( $custom_form, Forminator_Form_Entry_Model $entry ) {
		$settings      = $custom_form->settings;
		$new_user_data = $this->user_data;

		if ( isset( $settings['activation-method'] ) && ! empty( $settings['activation-method'] ) ) {
			if ( $this->check_activation_method( $settings['activation-method'] ) ) {
				$activation = $this->handle_user_activation( $new_user_data, $custom_form, $entry );
				if ( true !== $activation ) {
					return $activation;
				}
			} else {
				$user_id = $this->create_user( $new_user_data, $custom_form, $entry );
				if ( is_int( $user_id ) ) {
					$new_user_data['user_id'] = $user_id;
				} else {
					return $user_id;
				}
			}
		}

		return $new_user_data;
	}

	/**
	 * Validation for email fields
	 *
	 * @param string $email Email.
	 *
	 * @return array
	 */
	private function validate_email( $email ) {
		$data = array(
			'result'  => true,
			'message' => '',
		);

		if ( $email ) {
			if ( ! is_email( $email ) ) {
				$data['result']  = false;
				$data['message'] = esc_html__( 'This email address is not valid.', 'forminator' );

				return $data;
			}

			// Throws an error if the email is already registered.
			if ( email_exists( $email ) ) {
				$data['result']  = false;
				$data['message'] = esc_html__( 'This email address is already registered.', 'forminator' );

				return $data;
			}

			// Check if email is within the character limit.
			if ( 100 < mb_strlen( $email ) ) {
				$data['result']  = false;
				$data['message'] = esc_html__( 'User email may not be longer than 100 characters.', 'forminator' );

				return $data;
			}
		} else {
			$data['result']  = false;
			$data['message'] = esc_html__( 'The email address can not be empty.', 'forminator' );

			return $data;
		}

		return $data;
	}

	/**
	 * Validation for username fields
	 *
	 * @param string $username User name.
	 *
	 * @return array
	 */
	private function validate_username( $username ) {
		$data = array(
			'result'  => true,
			'message' => '',
		);
		if ( $username ) {
			// Throws an error if the username contains invalid characters.
			if ( ! validate_username( $username ) ) {
				$data['result']  = false;
				$data['message'] = esc_html__( 'This username is invalid because it uses illegal characters. Please enter a valid username.', 'forminator' );

				return $data;
			}

			// Throws an error if the username already exists.
			if ( username_exists( $username ) ) {
				$data['result']  = false;
				$data['message'] = esc_html__( 'This username is already registered.', 'forminator' );

				return $data;
			}

			// Check if username is greater than 60 characters.
			if ( 60 < mb_strlen( $username ) ) {
				$data['result']  = false;
				$data['message'] = esc_html__( 'Username may not be longer than 60 characters.', 'forminator' );

				return $data;
			}
		} else {
			$data['result']  = false;
			$data['message'] = esc_html__( 'The username can not be empty.', 'forminator' );

			return $data;
		}

		return $data;
	}

	/**
	 * Automatic login
	 *
	 * @param int $user_id User Id.
	 * @return void
	 */
	private function automatic_login( $user_id ) {
		wp_clear_auth_cookie();
		wp_set_auth_cookie( $user_id );
		wp_set_current_user( $user_id );
	}

	/**
	 * Validation for multi site
	 *
	 * @param array                 $validate Validate.
	 * @param Forminator_Form_Model $custom_form Form model.
	 * @param bool                  $is_approve Is approve.
	 *
	 * @return array
	 */
	public function multisite_validation( $validate, $custom_form, $is_approve ) {
		$data    = array(
			'result'  => true,
			'message' => '',
		);
		$setting = $custom_form->settings;

		$activation_method = forminator_get_property( $setting, 'activation-method' );
		// Site validation is not required if site registration is disabled and activation is not set to manual.
		if ( 'manual' !== $activation_method && ! forminator_is_site_registration_enabled() ) {
			return $data;
		}

		// Make sure option 'Site registration' is set.
		$option_create_site = forminator_get_property( $setting, 'site-registration' );
		if ( $is_approve || ! $option_create_site || ( isset( $option_create_site ) && 'enable' !== $option_create_site ) ) {

			return $data;
		}

		$blog_data    = $this->replace_site_data( $setting );
		$blog_address = $blog_data['address'];
		$blog_title   = $blog_data['title'];

		// get validation result for multi-site fields.
		$validation_result = wpmu_validate_blog_signup( $blog_address, $blog_title, wp_get_current_user() );

		// Site address validation.
		if ( isset( $blog_address ) && ! empty( $blog_address ) ) {
			$error_msg = isset( $validation_result['errors']->errors['blogname'][0] ) ? $validation_result['errors']->errors['blogname'][0] : false;

			if ( false !== $error_msg ) {
				$data['result']  = false;
				$data['message'] = $error_msg;

				return $data;
			}
		}
		// Site title validation.
		if ( isset( $blog_title ) && ! empty( $blog_title ) ) {
			$error_msg = isset( $validation_result['errors']->errors['blog_title'][0] ) ? $validation_result['errors']->errors['blog_title'][0] : false;

			if ( false !== $error_msg ) {
				$data['result']  = false;
				$data['message'] = $error_msg;

				return $data;
			}
		}

		return $data;
	}

	/**
	 * Create site
	 *
	 * @param int                         $user_id User Id.
	 * @param Forminator_Form_Model       $custom_form Form model.
	 * @param Forminator_Form_Entry_Model $entry Form entry model.
	 * @param string                      $password Password.
	 *
	 * @return bool|int
	 */
	public function create_site( $user_id, $custom_form, $entry, $password ) {
		global $current_site;

		$setting = $custom_form->settings;

		// Is option 'Site registration' enabled?
		$option_create_site = forminator_get_property( $setting, 'site-registration' );

		$activation_method = forminator_get_property( $setting, 'activation-method' );
		// If activation method is not 'manual' and site registration is disabled, do not create a site.
		if ( 'manual' !== $activation_method && ! forminator_is_site_registration_enabled() ) {
			$option_create_site = false;
		}
		if ( ! $option_create_site || ( isset( $option_create_site ) && 'enable' !== $option_create_site ) ) {
			$this->maybe_remove_user_from_main_site( $setting, $user_id );
			return false;
		}

		$site_data = $this->get_site_data( $setting, $user_id );
		if ( ! $site_data ) {

			return false;
		}

		/**
		 * Allows modifications to the new site meta
		 *
		 * @param array An array of new site arguments (ex. if the site is public => 1)
		 * @param array $custom_form The Form Object to filter through.
		 * @param array $entry The Entry Object to filter through.
		 * @param int $user_id Filer through the ID of the user who creates the site.
		 */
		$site_meta = apply_filters( 'forminator_cform_user_registration_new_site_meta', array( 'public' => 1 ), $custom_form, $entry, $user_id );
		$blog_id   = wpmu_create_blog( $site_data['domain'], $site_data['path'], $site_data['title'], $user_id, $site_meta, $current_site->id );

		if ( is_wp_error( $blog_id ) ) {

			return false;
		}

		if ( ! is_super_admin( $user_id ) && get_user_option( 'primary_blog', $user_id ) === $current_site->blog_id ) {
			update_user_option( $user_id, 'primary_blog', $blog_id, true );
		}

		$site_role = forminator_get_property( $setting, 'site-registration-role-field' );
		if ( $site_role ) {
			$user = new WP_User( $user_id, null, $blog_id );
			$user->set_role( $site_role );
		}

		$user_removed = $this->maybe_remove_user_from_main_site( $setting, $user_id );

		if ( ! $user_removed ) {
			$root_role = $this->get_root_role( $setting );
			// update their role on current site.
			$user = new WP_User( $user_id );
			$user->set_role( $root_role );
		}

		if ( isset( $setting['registration-password-field'] ) && 'auto' === $setting['registration-password-field'] ) {

			$password  = $password . "\r\n";
			$password .= '(' . esc_html__( 'This password was generated automatically, and it is recommended that you set a new password once you log in to your account.', 'forminator' ) . ")\r\n\r\n";

		} else {

			$password  = esc_html__( 'Use the password that you submitted when registering your account, or set a new password at the link below.', 'forminator' ) . "\r\n";
			$password .= '<' . $this->get_set_password_url( $user ) . ">\r\n\r\n";

		}

		// Send a notification if a new site was added.
		if ( isset( $setting['activation-email'] ) && 'none' !== $setting['activation-email'] ) {
			wpmu_welcome_notification( $blog_id, $user_id, $password, $site_data['title'], array( 'public' => 1 ) );
		}

		do_action( 'forminator_cform_site_created', $blog_id, $user_id, $entry, $custom_form, $password );

		return $blog_id;
	}

	/**
	 * May be remove user from network main site.
	 *
	 * @param array $setting Setting.
	 * @param int   $user_id User Id.
	 * @return bool
	 */
	private function maybe_remove_user_from_main_site( $setting, $user_id ) {
		$root_role = $this->get_root_role( $setting );

		if ( ! $root_role || 'notCreate' === $root_role ) {
			remove_user_from_blog( $user_id );
			return true;
		}
		return false;
	}

	/**
	 * Get root role from settings.
	 *
	 * @param mixed $setting Settings.
	 * @return string|null
	 */
	private function get_root_role( $setting ) {
		$registration_user_role = forminator_get_property( $setting, 'registration-user-role' );
		if ( 'conditionally' === $registration_user_role ) {
			return $this->conditional_user_role( $setting );
		}

		return forminator_get_property( $setting, 'registration-role-field' );
	}

	/**
	 * Get user data
	 *
	 * @param bool|int $user_id User Id.
	 * @param array    $prepare_user_data Prepare user data.
	 *
	 * @return bool|array
	 */
	private function get_user_data( $user_id = false, $prepare_user_data = array() ) {
		if ( ! $user_id ) {
			if ( ! empty( $prepare_user_data ) ) {
				$user_login = $prepare_user_data['user_login'];
				$user_email = $prepare_user_data['user_email'];
				$user_pass  = $prepare_user_data['user_pass'];
			}
		} else {
			$user       = new WP_User( $user_id );
			$user_login = $user->get( 'user_login' );
			$user_email = $user->get( 'user_email' );
			$user_pass  = $user->get( 'user_pass' );
		}

		if ( empty( $user_login ) || empty( $user_email ) ) {
			return false;
		}

		return array(
			'user_login' => $user_login,
			'user_email' => $user_email,
			'password'   => $user_pass,
		);
	}

	/**
	 * Replace site data
	 *
	 * @param array $setting Settings.
	 * @return array
	 */
	private function replace_site_data( $setting ) {
		$submitted_data = Forminator_CForm_Front_Action::$prepared_data;

		$blog_address = '';
		$address      = forminator_get_property( $setting, 'site-registration-name-field' );
		if ( isset( $submitted_data[ $address ] ) && ! empty( $submitted_data[ $address ] ) ) {
			$blog_address = strtolower( $submitted_data[ $address ] );

			/*
			 * If the username and sitename is from the same field,
			 * cleanup the blog_address so that only errors for username will show up
			*/
			if ( $setting['registration-username-field'] === $setting['site-registration-name-field'] ) {
				$blog_address = str_replace( array( ' ', '-', '_' ), '', $blog_address );
			}
		}

		$blog_title = forminator_get_property( $setting, 'site-registration-title-field' );
		if ( isset( $submitted_data[ $blog_title ] ) && ! empty( $submitted_data[ $blog_title ] ) ) {
			$blog_title = $submitted_data[ $blog_title ];
		}

		return array(
			'address' => $blog_address,
			'title'   => $blog_title,
		);
	}

	/**
	 * Get site data
	 *
	 * @param array $setting Setting.
	 * @param int   $user_id User Id.
	 * @param array $prepare_user_data Prepare user data.
	 *
	 * @return array
	 */
	public function get_site_data( $setting, $user_id, $prepare_user_data = array() ) {
		global $current_site;

		$user_data = $this->get_user_data( $user_id, $prepare_user_data );
		$blog_data = $this->replace_site_data( $setting );

		if ( empty( $blog_data['address'] ) || empty( $user_data['user_email'] ) || ! is_email( $user_data['user_email'] ) ) {
			return array();
		}

		if ( is_subdomain_install() ) {
			$blog_domain = $blog_data['address'] . '.' . preg_replace( '|^www\.|', '', $current_site->domain );
			$path        = $current_site->path;
		} else {
			$blog_domain = $current_site->domain;
			$path        = trailingslashit( $current_site->path ) . $blog_data['address'] . '/';
		}

		return array(
			'domain' => $blog_domain,
			'path'   => $path,
			'title'  => $blog_data['title'],
			'email'  => $user_data['user_email'],
		);
	}

	/**
	 * Get custom user meta
	 *
	 * @param array $setting Setting.
	 *
	 * @return array
	 */
	public function get_custom_user_meta( $setting ) {
		$meta = array();

		if ( empty( $setting['options'] ) ) {
			return $meta;
		}

		foreach ( $setting['options'] as $meta_item ) {
			list( $meta_key, $meta_value, $custom_meta_key ) = array_pad( array_values( $meta_item ), 3, false );

			$meta_key          = $custom_meta_key ? $custom_meta_key : $meta_key;
			$meta[ $meta_key ] = $meta_value;
		}

		return $meta;
	}

	/**
	 * Add user meta
	 *
	 * @param int    $user_id User Id.
	 * @param array  $setting Setting.
	 * @param object $custom_form Custom form.
	 * @param object $entry Entry.
	 *
	 * @return void
	 */
	public function add_user_meta( $user_id, $setting, $custom_form, $entry ) {
		$custom_meta = $this->get_custom_user_meta( $setting );

		if ( ! is_array( $custom_meta ) || empty( $custom_meta ) ) {
			return;
		}

		foreach ( $custom_meta as $meta_key => $meta_value ) {
			// Skip empty meta items.
			if ( ! $meta_key || ! $meta_value ) {
				continue;
			}
			if ( strpos( $meta_value, '{' ) !== false ) {
				$meta_value = forminator_replace_form_data( $meta_value, $custom_form, $entry, false, false, true );
				$meta_value = forminator_replace_variables( $meta_value, $setting['form_id'] );
			}

			update_user_meta( $user_id, $meta_key, $meta_value );
		}
	}

	/**
	 * Change notifications
	 *
	 * @param string $activation_method Activation method.
	 * @param array  $notifications Notifications.
	 *
	 * @return array
	 */
	private function change_notifications( $activation_method, $notifications ) {
		foreach ( $notifications as $key => $notification ) {
			if ( isset( $notifications[ $key ][ 'email-subject-method-' . $activation_method ] ) ) {
				$notifications[ $key ]['email-subject'] = $notifications[ $key ][ 'email-subject-method-' . $activation_method ];
			}
			if ( isset( $notifications[ $key ][ 'email-editor-method-' . $activation_method ] ) ) {
				$notifications[ $key ]['email-editor'] = $notifications[ $key ][ 'email-editor-method-' . $activation_method ];
			}
		}

		return $notifications;
	}

	/**
	 * Get the set password url for the specified user.
	 *
	 * @global wpdb         $wpdb      WordPress database object for queries.
	 * @global PasswordHash $wp_hasher Portable PHP password hashing framework instance.
	 *
	 * @param WP_User $user User object.
	 *
	 * @return string
	 */
	public function get_set_password_url( $user ) {
		// Generate a random password reset key.
		$key = get_password_reset_key( $user );

		/** This action is documented in wp-login.php */
		do_action( 'retrieve_password_key', $user->user_login, $key );

		return network_site_url( "wp-login.php?action=rp&key=$key&login=" . rawurlencode( $user->user_login ), 'login' );
	}

	/**
	 * Overrides wp_new_user_notification to email login credentials to a newly-registered user.
	 *
	 * @param int    $user_id        User ID.
	 * @param string $plaintext_pass The password being sent to the user.
	 * @param string $notify         Optional. Type of notification that should happen. Accepts 'admin' or an empty.
	 *                               string (admin only), 'user', or 'both' (admin and user). Default empty.
	 */
	public function forminator_new_user_notification( $user_id, $plaintext_pass = '', $notify = '' ) {
		$user     = get_userdata( $user_id );
		$username = $user->user_login;

		// The blogname option is escaped with esc_html on the way into the database in sanitize_option.
		// we want to reverse this for the plain text arena of emails.
		$blogname = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );

		$switched_locale = switch_to_locale( get_locale() );

		if ( $switched_locale ) {
			restore_previous_locale();
		}

		if ( 'admin' === $notify || ( empty( $plaintext_pass ) && empty( $notify ) ) ) {
			return;
		}

		$switched_locale = switch_to_locale( get_user_locale( $user ) );

		/* translators: 1. User name. */
		$message = sprintf( esc_html__( 'Dear %s,', 'forminator' ), $username ) . "\r\n\r\n";
		/* translators: 1. Blog name. */
		$message .= sprintf( esc_html__( 'Your account on %s has been activated! Please find your login details below.', 'forminator' ), $blogname ) . "\r\n\r\n\r\n";
		/* translators: 1. Login URL. */
		$message .= sprintf( esc_html__( 'Login page: %s', 'forminator' ), wp_login_url() ) . "\r\n\r\n";
		/* translators: 1. User name. */
		$message .= sprintf( esc_html__( 'Username: %s', 'forminator' ), $username ) . "\r\n\r\n";

		if ( empty( $plaintext_pass ) ) {

			$message .= esc_html__( 'Password: Use the password that you submitted when registering your account, or set a new password at the link below.', 'forminator' ) . "\r\n";
			$message .= '<' . $this->get_set_password_url( $user ) . ">\r\n\r\n\r\n";

		} else {
			/* translators: 1. Password. */
			$message .= sprintf( esc_html__( 'Password: %s', 'forminator' ), $plaintext_pass ) . "\r\n";
			$message .= '(' . esc_html__( 'This password was generated automatically, and it is recommended that you set a new password once you log in to your account.', 'forminator' ) . ")\r\n\r\n\r\n";

		}
		/* translators: 1. Home URL. */
		$message .= sprintf( esc_html__( 'This message was sent from %s', 'forminator' ), home_url() );

		/* translators: 1. Blog name. */
		$result = wp_mail( $user->user_email, sprintf( esc_html__( '[%s] Account Activated', 'forminator' ), $blogname ), $message );

		if ( $switched_locale ) {
			restore_previous_locale();
		}
	}

	/**
	 * Get conditional user role
	 *
	 * @param array $settings Settings.
	 *
	 * @return string
	 */
	public function conditional_user_role( $settings ) {
		$user_role  = 'subscriber';
		$conditions = isset( $settings['user_role'] ) ? $settings['user_role'] : array();
		if ( ! empty( $conditions ) ) {
			foreach ( $conditions as $condition ) {
				if ( Forminator_Field::is_condition_matched( $condition ) ) {
					return $condition['role'];
				}
			}
		}

		return $user_role;
	}
}
