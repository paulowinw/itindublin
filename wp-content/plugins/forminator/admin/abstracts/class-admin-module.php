<?php
/**
 * Forminator Admin Module
 *
 * @package Forminator
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_Admin_Module
 *
 * @since 1.0
 */
abstract class Forminator_Admin_Module {

	/**
	 * Pages
	 *
	 * @var array
	 */
	public $pages = array();

	/**
	 * Page
	 *
	 * @var string
	 */
	public $page = '';

	/**
	 * Edit page
	 *
	 * @var string
	 */
	public $page_edit = '';

	/**
	 * Page entries
	 *
	 * @var string
	 */
	public $page_entries = '';

	/**
	 * Directory
	 *
	 * @var string
	 */
	public $dir = '';

	/**
	 * Forminator_Admin_Module constructor.
	 *
	 * @since 1.0
	 */
	public function __construct() {

		$this->init();

		$this->includes();

		add_action( 'admin_menu', array( $this, 'add_menu_pages' ) );
		add_action( 'admin_head', array( $this, 'hide_menu_pages' ) );

		add_action( 'wp_loaded', array( $this, 'create_module' ) );

		// admin-menu-editor compat.
		add_action( 'admin_menu_editor-menu_replaced', array( $this, 'hide_menu_pages' ) );

		add_filter( 'forminator_data', array( $this, 'add_js_defaults' ) );
		add_filter( 'forminator_l10n', array( $this, 'add_l10n_strings' ) );
		add_filter( 'submenu_file', array( $this, 'admin_submenu_file' ), 10, 2 );

		add_filter( 'forminator_form_admin_data', array( 'Forminator_Base_Form_Model', 'add_saved_changes' ), 10, 2 );
		add_filter( 'forminator_poll_admin_data', array( 'Forminator_Base_Form_Model', 'add_saved_changes' ), 10, 2 );
		add_filter( 'forminator_quiz_admin_data', array( 'Forminator_Base_Form_Model', 'add_saved_changes' ), 10, 2 );
	}

	/**
	 * Init
	 *
	 * @since 1.0
	 */
	public function init() {
		// Call init instead of __construct in modules.
	}

	/**
	 * Attach admin pages
	 *
	 * @since 1.0
	 */
	public function add_menu_pages() {}

	/**
	 * Create module
	 *
	 * @since 1.0
	 */
	public function create_module() {}

	/**
	 * Hide pages from menu
	 *
	 * @since 1.0
	 */
	public function hide_menu_pages() {
		remove_submenu_page( 'forminator', $this->page_edit );
		remove_submenu_page( 'forminator', $this->page_entries );
		echo '<style>
			#toplevel_page_forminator ul.wp-submenu li a[href="admin.php?page=forminator-addons"] { color: #1ABC9C !important; }
			#toplevel_page_forminator ul.wp-submenu li a[href="admin.php?page=forminator-templates"] { display: flex; justify-content: space-between; align-items: center; }
			#toplevel_page_forminator ul.wp-submenu li a[href="admin.php?page=forminator-templates"] .menu-new-tag { font-size: 8px; line-height: 8px; padding: 2px 6px; background: #1ABC9C; border-radius: 9px; text-transform: uppercase; color: #fff; font-weight: 900; height: 100%; letter-spacing: -0.25px; }
		</style>';
		if ( ! FORMINATOR_PRO ) {
			echo '<style>#toplevel_page_forminator ul.wp-submenu li:last-child a[href^="https://wpmudev.com"] { background-color: #8d00b1 !important; color: #fff !important; font-weight: 500 !important; letter-spacing: -0.2px; }</style>';
			echo '<script>jQuery(function() {jQuery(\'#toplevel_page_forminator ul.wp-submenu li:last-child a[href^="https://wpmudev.com"]\').attr("target", "_blank");});</script>';
		}
	}

	/**
	 * Used to include files
	 *
	 * @since 1.0
	 */
	public function includes() {
		include_once $this->dir . '/admin-page-new.php';
		include_once $this->dir . '/admin-page-view.php';
		include_once $this->dir . '/admin-page-entries.php';
		include_once $this->dir . '/admin-renderer-entries.php';
	}

	/**
	 * Inject module options to JS
	 *
	 * @since 1.0
	 * @param mixed $data Data.
	 * @return mixed
	 */
	public function add_js_defaults( $data ) {
		return $data;
	}

	/**
	 * Inject l10n strings to JS
	 *
	 * @param mixed $strings Strings.
	 * @since 1.0
	 * @return mixed
	 */
	public function add_l10n_strings( $strings ) {
		return $strings;
	}

	/**
	 * Is the admin page being viewed in edit mode
	 *
	 * @since 1.0
	 * @return mixed
	 */
	public static function is_edit() {
		return filter_input( INPUT_GET, 'id', FILTER_VALIDATE_INT ) || filter_input( INPUT_POST, 'id', FILTER_VALIDATE_INT );
	}

	/**
	 * Is the module admin dashboard page
	 *
	 * @since 1.0
	 * @return bool
	 */
	public function is_admin_home() {
		global $plugin_page;

		return $this->page === $plugin_page;
	}

	/**
	 * Is the module admin new/edit page
	 *
	 * @since 1.0
	 * @return bool
	 */
	public function is_admin_wizard() {
		global $plugin_page;

		// $plugin_page may not be set if we call the function too early, retrieve the page slug from GET.
		$page = Forminator_Core::sanitize_text_field( 'page' );
		if ( empty( $plugin_page ) ) {
			return $this->page_edit === $page;
		}

		return $this->page_edit === $plugin_page;
	}

	/**
	 * Highlight parent page in sidebar
	 *
	 * @deprecated 1.1 No longer used because this function override prohibited WordPress global of $plugin_page
	 * @since      1.0
	 *
	 * @param mixed $file File.
	 *
	 * @return mixed
	 */
	public function highlight_admin_parent( $file ) {
		_deprecated_function( __METHOD__, '1.1', null );
		return $file;
	}

	/**
	 * Prepare settings
	 *
	 * @param array $original_settings Sent settings.
	 * @return array
	 */
	protected static function validate_settings( $original_settings ) {
		// Sanitize settings.
		$settings = forminator_sanitize_array_field( $original_settings );

		// Sanitize custom css.
		if ( isset( $original_settings['custom_css'] ) ) {
			$settings['custom_css'] = sanitize_textarea_field( $original_settings['custom_css'] );
		}

		// Sanitize admin email message.
		if ( isset( $original_settings['admin-email-editor'] ) ) {
			$settings['admin-email-editor'] = wp_kses_post( $original_settings['admin-email-editor'] );
		}

		// Sanitize quiz description.
		if ( isset( $original_settings['quiz_description'] ) ) {
			$settings['quiz_description'] = wp_kses_post( $original_settings['quiz_description'] );
		}

		if ( isset( $original_settings['social-share-message'] ) ) {
			$settings['social-share-message'] = forminator_sanitize_textarea( $original_settings['social-share-message'] );
		}

		if ( isset( $original_settings['msg_count'] ) ) {
			// Backup, we allow html here.
			$settings['msg_count'] = wp_kses_post( $original_settings['msg_count'] );
		}

		$settings = apply_filters( 'forminator_builder_data_settings_before_saving', $settings, $original_settings );

		return $settings;
	}

	/**
	 * Highlight submenu on admin page
	 *
	 * @since 1.1
	 *
	 * @param string $submenu_file Submenu file.
	 * @param string $parent_file Parent file.
	 *
	 * @return string
	 */
	public function admin_submenu_file( $submenu_file, $parent_file ) {
		global $plugin_page;

		if ( 'forminator' !== $parent_file ) {
			return $submenu_file;
		}

		if ( $this->page_edit === $plugin_page || $this->page_entries === $plugin_page ) {
			$submenu_file = $this->page;
		}

		return $submenu_file;
	}


	/**
	 * Import Form
	 *
	 * @param string $json JSON data to import.
	 * @param string $name Module name.
	 * @param string $slug Module type.
	 * @param bool   $change_recipients Change recipients.
	 * @param bool   $draft Draft status.
	 * @param array  $extra_args extra arguments.
	 *
	 * @throws Exception When import failed.
	 */
	public static function import_json( string $json, string $name, string $slug, bool $change_recipients, bool $draft = false, array $extra_args = array() ) {
		$import_data = json_decode( $json, true );

		if ( $import_data ) {
			array_walk_recursive(
				$import_data,
				function ( &$item ) {
					if ( isset( $item ) ) {
						$item = html_entity_decode( $item, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
					}
				}
			);
		}

		$import_data = Forminator_Core::sanitize_array( $import_data );

		if ( $change_recipients ) {
			$import_data = self::change_recipients( $import_data );
		}

		// hook custom data here.
		$import_data = apply_filters( 'forminator_' . $slug . '_import_data', $import_data );

		if ( empty( $import_data ) || ! is_array( $import_data ) ) {
			throw new Exception( esc_html__( 'Oops, looks like we found an issue. Import text can not include whitespace or special characters.', 'forminator' ) );
		}

		if ( ! isset( $import_data['type'] ) || $slug !== $import_data['type'] ) {
			throw new Exception( esc_html__( 'Oops, wrong module type. You can only import a module of the same type that you\'re currently viewing.', 'forminator' ) );
		}

		$class = 'Forminator_' . forminator_get_prefix( $slug, '', true ) . '_Model';
		if ( $draft ) {
			$import_data['status'] = $class::STATUS_DRAFT;
		}

		if ( ! empty( $extra_args ) && isset( $import_data['data']['settings'] ) ) {
			$import_data['data']['settings'] = array_merge( $import_data['data']['settings'], $extra_args );
		}

		if ( ! empty( $import_data['data']['settings'] ) ) {
			$validate = forminator_validate_registration_form_settings( $import_data['data']['settings'] );
			if ( is_wp_error( $validate ) ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception message is already escaped.
				throw new Exception( $validate->get_error_message() );
			}
		}

		$model = $class::create_from_import_data( $import_data, $name );

		if ( is_wp_error( $model ) ) {
			throw new Exception( esc_html( $model->get_error_message() ) );
		}

		if ( ! $model instanceof Forminator_Base_Form_Model ) {
			throw new Exception( esc_html__( 'Failed to import module, please make sure import text is valid, and try again.', 'forminator' ) );
		}

		return $model;
	}


	/**
	 * Change the recipients
	 *
	 * @since 1.18.0
	 *
	 * @param mixed $data imported module data.
	 *
	 * @return array $data
	 */
	private static function change_recipients( $data ) {
		if ( ! empty( $data ) ) {
			$current_user_email = wp_get_current_user()->user_email;

			if ( 'poll' === $data['type'] ) {

				if ( ! empty( $data['data']['settings']['admin-email-recipients'] ) ) {
					$data['data']['settings']['admin-email-recipients'] = self::apply_user_email( $data['data']['settings']['admin-email-recipients'], $current_user_email );
				}
				if ( ! empty( $data['data']['settings']['admin-email-cc-address'] ) ) {
					$data['data']['settings']['admin-email-cc-address'] = self::apply_user_email( $data['data']['settings']['admin-email-cc-address'], $current_user_email );
				}
				if ( ! empty( $data['data']['settings']['admin-email-bcc-address'] ) ) {
					$data['data']['settings']['admin-email-bcc-address'] = self::apply_user_email( $data['data']['settings']['admin-email-bcc-address'], $current_user_email );
				}
			} elseif ( ! empty( $data['data']['notifications'] ) ) {
				$email_fields_to_update = array(
					'recipients',
					'bcc-email',
					'cc-email',
				);
				foreach ( $data['data']['notifications'] as $notif_key => $notif ) {
					foreach ( $email_fields_to_update as $key ) {
						if ( ! empty( $notif[ $key ] ) ) {
							$data['data']['notifications'][ $notif_key ][ $key ] = self::apply_user_email( $notif[ $key ], $current_user_email );
						}
					}

					// Modify the routing recipients.
					if ( ! empty( $notif['routing'] ) ) {

						foreach ( $notif['routing'] as $routing_key => $route ) {
							if ( ! empty( $route['email'] ) ) {
								$route_emails = self::apply_user_email( $route['email'], $current_user_email );
								$data['data']['notifications'][ $notif_key ]['routing'][ $routing_key ]['email'] = $route_emails;
							}
						}
					}
				}
			}
		}
		return $data;
	}

	/**
	 * Apply user emails
	 *
	 * @since 1.18.0
	 *
	 * @param string $data Email recipients.
	 * @param string $current_user_email User email.
	 *
	 * @return array
	 */
	private static function apply_user_email( $data, $current_user_email ) {
		$recipients = ! is_array( $data ) ? explode( ',', $data ) : $data;

		foreach ( $recipients as $key => $recipient ) {
			$recipient = trim( $recipient );

			// Will not change recipients that use field tags like {email-1}.
			if ( false === strpos( $recipient, '{' ) ) {
				$recipients[ $key ] = $current_user_email;
			}
		}
		$recipients = array_unique( $recipients );

		return ! is_array( $data ) ? implode( ',', $recipients ) : $recipients;
	}
}
