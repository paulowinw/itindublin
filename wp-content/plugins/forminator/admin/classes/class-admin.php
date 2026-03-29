<?php
/**
 * Forminator Admin
 *
 * @package Forminator
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_Admin
 *
 * @since 1.0
 */
class Forminator_Admin {

	/**
	 * Pages
	 *
	 * @var array
	 */
	public $pages = array();

	/**
	 * Forminator_Admin constructor.
	 */
	public function __construct() {
		$this->includes();

		// Init admin pages.
		add_action( 'admin_menu', array( $this, 'add_dashboard_page' ) );
		if ( current_user_can( 'manage_options' ) ) {
			add_action( 'admin_notices', array( $this, 'show_stripe_restricted_api_key_notice' ) );
			add_action( 'admin_notices', array( $this, 'show_stripe_updated_notice' ) );
			add_action( 'admin_notices', array( $this, 'show_rating_notice' ) );
			add_action( 'admin_notices', array( $this, 'show_pro_available_notice' ) );

			// Show Promote free plan notice only for Free version, for admins and if WPMU DEV Dashboard is not activated.
			if ( ! FORMINATOR_PRO && ! class_exists( 'WPMUDEV_Dashboard' )
					// The notice was already dismissed.
					&& ! self::was_notification_dismissed( 'forminator_promote_free_plan' )
					// Remind me later was clicked.
					&& ! self::maybe_remind_later()
				) {

				add_action( 'admin_notices', array( $this, 'promote_free_plan' ) );
				add_action( 'admin_enqueue_scripts', array( $this, 'promote_free_plan_scripts' ) );
			}

			add_action( 'admin_notices', array( $this, 'check_stripe_addon_version' ) );
			add_action( 'admin_notices', array( $this, 'show_cf7_importer_notice' ) );
			if ( ! forminator_addons_disabled() ) {
				add_action( 'admin_notices', array( $this, 'show_addons_update_notice' ) );
			}
			add_action( 'admin_notices', array( $this, 'set_encryption_key_notice' ) );

			add_action( 'admin_enqueue_scripts', array( $this, 'deactivation_scripts' ) );
			add_action( 'admin_footer-plugins.php', array( $this, 'load_deactivation_survey_modal' ) );
		}

		// Add plugin action links.
		add_filter( 'plugin_action_links_' . FORMINATOR_PLUGIN_BASENAME, array( $this, 'add_plugin_action_links' ) );
		if ( forminator_is_networkwide() ) {
			add_filter(
				'network_admin_plugin_action_links_' . FORMINATOR_PLUGIN_BASENAME,
				array(
					$this,
					'add_plugin_action_links',
				)
			);
		}
		// Add links next to plugin details.
		add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 3 );

		// Update permissions when user profile is updated.
		add_action( 'profile_update', array( $this, 'maybe_update_permissions' ), 10, 1 );

		// Clear pages cache.
		add_action( 'save_post', array( $this, 'clear_pages_cache' ) );
		add_action( 'delete_post', array( $this, 'clear_pages_cache' ) );
		add_action( 'trash_post', array( $this, 'clear_pages_cache' ) );
		add_action( 'untrash_post', array( $this, 'clear_pages_cache' ) );

		// Init Admin AJAX class.
		new Forminator_Admin_AJAX();

		/**
		 * Triggered when Admin is loaded
		 */
		do_action( 'forminator_admin_loaded' );
	}

	/**
	 * Init deactivation scripts
	 *
	 * @return void
	 */
	public function deactivation_scripts() {
		$screen = get_current_screen();
		if ( 'plugins' !== $screen->id && 'plugins-network' !== $screen->id ) {
			return;
		}

		// Load admin scripts.
		wp_register_script(
			'forminator-admin',
			forminator_plugin_url() . 'requirejs/main.js',
			array(
				'backbone',
				'underscore',
				'jquery',
			),
			FORMINATOR_VERSION,
			true
		);

		// Load jquery ui.
		forminator_admin_jquery_ui_init();

		// Load shared-ui scripts.
		forminator_sui_scripts();

		// Load admin styles.
		forminator_admin_enqueue_styles();

		$forminator_data = new Forminator_Admin_Data();
		$forminator_l10n = new Forminator_Admin_L10n();

		$data = $forminator_data->get_options_data();
		$l10n = $forminator_l10n->get_l10n_strings();
		wp_localize_script( 'forminator-admin', 'forminatorData', $data );
		wp_localize_script( 'forminator-admin', 'forminatorl10n', $l10n );
		wp_enqueue_script( 'forminator-admin' );
	}

	/**
	 * Load Deactivation Survey Modal
	 *
	 * @return void
	 */
	public function load_deactivation_survey_modal() {
		$file_name = 'deactivation-survey-modal.php';
		$file_path = forminator_plugin_dir() . 'admin/views/plugins/' . $file_name;
		require_once $file_path;
	}

	/**
	 * Include required files
	 *
	 * @since 1.0
	 */
	private function includes() {
		// Admin pages.
		include_once forminator_plugin_dir() . 'admin/pages/dashboard-page.php';
		include_once forminator_plugin_dir() . 'admin/pages/entries-page.php';
		include_once forminator_plugin_dir() . 'admin/pages/integrations-page.php';
		include_once forminator_plugin_dir() . 'admin/pages/settings-page.php';
		include_once forminator_plugin_dir() . 'admin/pages/addons-page.php';
		include_once forminator_plugin_dir() . 'admin/pages/reports-page.php';
		include_once forminator_plugin_dir() . 'admin/pages/templates-page.php';

		// Admin AJAX.
		include_once forminator_plugin_dir() . 'admin/classes/class-admin-ajax.php';

		// Admin Data.
		include_once forminator_plugin_dir() . 'admin/classes/class-admin-data.php';

		// Admin l10n.
		include_once forminator_plugin_dir() . 'admin/classes/class-admin-l10n.php';

		if ( forminator_is_import_plugin_enabled( 'cf7' ) ) {
			// CF7 Import.
			include_once forminator_plugin_dir() . 'admin/classes/thirdparty-importers/class-importer-cf7.php';
		}

		if ( forminator_is_import_plugin_enabled( 'ninjaforms' ) ) {
			// Ninjaforms Import.
			include_once forminator_plugin_dir() . 'admin/classes/thirdparty-importers/class-importer-ninja.php';
		}

		if ( forminator_is_import_plugin_enabled( 'gravityforms' ) ) {
			// Gravityforms CF7 Import.
			include_once forminator_plugin_dir() . 'admin/classes/thirdparty-importers/class-importer-gravity.php';
		}

		// Admin Addons page.
		include_once forminator_plugin_dir() . 'admin/classes/class-addons-page.php';

		// Admin report page.
		include_once forminator_plugin_dir() . 'admin/classes/class-reports-page.php';
	}

	/**
	 * Initialize Dashboard page
	 *
	 * @since 1.0
	 */
	public function add_dashboard_page() {
		$title = esc_html__( 'Forminator', 'forminator' );
		

		$this->pages['forminator']           = new Forminator_Dashboard_Page( 'forminator', 'dashboard', $title, $title, false, false );
		$this->pages['forminator-dashboard'] = new Forminator_Dashboard_Page( 'forminator', 'dashboard', esc_html__( 'Forminator Dashboard', 'forminator' ), esc_html__( 'Dashboard', 'forminator' ), 'forminator' );
	}

	/**
	 * Add Integrations page
	 *
	 * @since 1.1
	 */
	public function add_integrations_page() {
		add_action( 'admin_menu', array( $this, 'init_integrations_page' ) );
	}

	/**
	 * Initialize Integrations page
	 *
	 * @since 1.1
	 */
	public function init_integrations_page() {
		$this->pages['forminator-integrations'] = new Forminator_Integrations_Page(
			'forminator-integrations',
			'integrations',
			esc_html__( 'Integrations', 'forminator' ),
			esc_html__( 'Integrations', 'forminator' ),
			'forminator'
		);

		// TODO: remove this after converted to JS.
		$addons = Forminator_Integration_Loader::get_instance()->get_addons()->to_array();
		foreach ( $addons as $slug => $addon_array ) {
			$addon_class = forminator_get_addon( $slug );

			if ( $addon_class && is_callable( array( $addon_class, 'admin_hook_html_version' ) ) ) {
				call_user_func( array( $addon_class, 'admin_hook_html_version' ) );
			}
		}
	}

	/**
	 * Add Settings page
	 *
	 * @since 1.0
	 */
	public function add_settings_page() {
		add_action( 'admin_menu', array( $this, 'init_settings_page' ) );
	}

	/**
	 * Initialize Settings page
	 *
	 * @since 1.0
	 */
	public function init_settings_page() {
		$this->pages['forminator-settings'] = new Forminator_Settings_Page( 'forminator-settings', 'settings', esc_html__( 'Global Settings', 'forminator' ), esc_html__( 'Settings', 'forminator' ), 'forminator' );
	}

	/**
	 * Add Templates page
	 *
	 * @since 1.0
	 */
	public function add_templates_page() {
		add_action( 'admin_menu', array( $this, 'init_templates_page' ) );
	}

	/**
	 * Initialize templates page
	 *
	 * @since 1.0
	 */
	public function init_templates_page() {
		$section_name = esc_html__( 'Templates', 'forminator' );

		$this->pages['forminator-templates'] = new Forminator_Templates_Page( 'forminator-templates', 'templates', $section_name, $section_name, 'forminator' );
	}

	/**
	 * Add Entries page
	 *
	 * @since 1.0.5
	 */
	public function add_entries_page() {
		add_action( 'admin_menu', array( $this, 'init_entries_page' ) );
	}

	/**
	 * Initialize Entries page
	 *
	 * @since 1.0.5
	 */
	public function init_entries_page() {
		$this->pages['forminator-entries'] = new Forminator_Entries_Page(
			'forminator-entries',
			'common/entries',
			esc_html__( 'Forminator Submissions', 'forminator' ),
			esc_html__( 'Submissions', 'forminator' ),
			'forminator'
		);
	}

	/**
	 * Add Forminator Pro page
	 *
	 * @since 1.0
	 */
	public function add_upgrade_page() {
		add_action( 'admin_menu', array( $this, 'init_upgrade_page' ), 20 );
	}

	/**
	 * Initialize Settings page
	 *
	 * @since 1.0
	 */
	public function init_upgrade_page() {
		add_submenu_page(
			'forminator',
			esc_html__( 'Get Forminator Pro', 'forminator' ),
			esc_html__( 'Get Forminator Pro', 'forminator' ),
			forminator_get_permission( 'forminator-upgrade' ),
			'https://wpmudev.com/project/forminator-pro/?utm_source=forminator&utm_medium=plugin&utm_campaign=forminator_submenu_upsell'
		);
	}

	/**
	 * Add Add-ons page
	 *
	 * @since 1.15
	 */
	public function add_addons_page() {
		add_action( 'admin_menu', array( $this, 'init_addons_page' ) );
	}

	/**
	 * Initialize Add-ons page
	 *
	 * @since 1.15
	 */
	public function init_addons_page() {
		$this->pages['forminator-addons'] = new Forminator_Addons_Page(
			'forminator-addons',
			'addons',
			esc_html__( 'Forminator Add-ons', 'forminator' ),
			esc_html__( 'Add-ons', 'forminator' ),
			'forminator'
		);
	}

	/**
	 * Add Reports page
	 *
	 * @since 1.18.0
	 */
	public function add_reports_page() {
		add_action( 'admin_menu', array( $this, 'init_reports_page' ) );
	}

	/**
	 * Initialize Reports page
	 *
	 * @since 1.18.0
	 */
	public function init_reports_page() {
		$this->pages['forminator-reports'] = new Forminator_Reports_Page( 'forminator-reports', 'common/reports', esc_html__( 'Reports', 'forminator' ), esc_html__( 'Reports', 'forminator' ), 'forminator' );
	}

	/**
	 * Check if we have any old Stripe form
	 *
	 * @return bool
	 * @since 1.9
	 */
	public function has_old_stripe_forms() {
		$forms = Forminator_Form_Model::model()->get_models_by_field_and_version( 'stripe-1', '1.9-alpha.1' );

		if ( count( $forms ) > 0 ) {
			return true;
		}

		return false;
	}


	/**
	 * Displays an admin notice when the user is an active member and doesn't have Forminator Pro installed
	 * Shown in forminator pages. Per user notification.
	 */
	public function show_pro_available_notice() {
		if ( ( isset( $_GET['page'] ) && 'forminator' !== substr( sanitize_text_field( wp_unslash( $_GET['page'] ) ), 0, 10 ) ) || FORMINATOR_PRO ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}
		// The notice was already dismissed.
		if ( self::was_notification_dismissed( 'forminator_pro_is_available' ) ) {
			return;
		}

		// Show the notice only to users who can do something about this and who are members.
		if ( ! self::user_can_update_plugins() || ! forminator_can_install_pro() ) {
			return;
		}

		$url  = add_query_arg(
			array( 'page' => 'wpmudev-plugins' ),
			network_admin_url( 'admin.php' )
		) . '#pid=2097296';
		$link = '<a type="button" href="' . esc_url( $url ) . '" target="_self" class="button button-primary">' . esc_html__( 'Upgrade', 'forminator' ) . '</a>';

		$username = forminator_get_current_username();
		$name     = ! empty( $username ) ? $username : esc_html__( 'Hey', 'forminator' );

		$message = '<p>';
		/* translators: user's name */
		$message .= sprintf( esc_html__( '%s, it appears you have an active WPMU DEV membership but haven\'t upgraded Forminator to the pro version. You won\'t lose any settings upgrading, go for it!', 'forminator' ), $name );
		$message .= '</p>';
		$message .= '<p>' . $link . '</p>';

		echo '<div class="forminator-grouped-notice notice notice-info is-dismissible"'
			. ' data-notice-slug="forminator_pro_is_available"'
			. ' data-nonce="' . esc_attr( wp_create_nonce( 'forminator_dismiss_notice' ) ) . '">';
		echo wp_kses_post( $message );
		echo '</div>';
	}

	/**
	 * Enqueue scripts for Promote Free Plan notice
	 */
	public function promote_free_plan_scripts() {
		// Show the notice only on WP Dashboard page.
		$screen = get_current_screen();
		if ( 'dashboard' !== $screen->id ) {
			return;
		}

		$dashboard_object = $this->pages['forminator-dashboard'];
		$dashboard_object->enqueue_scripts( '' );
	}

	/**
	 * Displays Promote Free Plan notice
	 */
	public function promote_free_plan() {
		// Show the notice only on WP Dashboard page.
		$screen = get_current_screen();
		if ( 'dashboard' !== $screen->id || Forminator_Hub_Connector::hub_connector_connected() ) {
			return;
		}
		$button_1 = '<a href="' . esc_url( Forminator_Hub_Connector::get_hub_connect_url( 'wp-dashboard-preset-template' ) ) . '" class="button button-primary">'
				. esc_html__( 'Connect & Start Using Pro Templates', 'forminator' )
				. '</a>';
		$remind   = '<a style="margin-left:20px;text-decoration: none;" href="#" id="forminator-promote-remind-later" data-nonce="' . esc_attr( wp_create_nonce( 'forminator_promote_remind_later' ) ) . '">' . esc_html__( 'Remind me later', 'forminator' ) . '</a>';

		$message  = '<p><strong>';
		$message .= esc_html__( 'Pro Form Templates—Now Free for Everyone!', 'forminator' );
		$message .= '</strong></p>';
		$message .= '<p>';
		$message .= esc_html__( 'Build forms faster with professionally designed templates—now free in the Forminator plugin.', 'forminator' );
		$message .= '</p>';
		$message .= '<p>';
		$message .= esc_html__( 'Connect your site to access them instantly and unlock WPMU DEV’s full suite of tools—trusted by 50K+ developers.', 'forminator' );
		$message .= ' <b>' . esc_html__( 'All completely free.', 'forminator' ) . '</b>';
		$message .= '</p>';
		$message .= '<p>' . $button_1 . '&nbsp;' . $remind . '</p>';

		echo '<div class="forminator-grouped-notice notice notice-info is-dismissible"'
			. ' data-notice-slug="forminator_promote_free_plan"'
			. ' data-nonce="' . esc_attr( wp_create_nonce( 'forminator_dismiss_notice' ) ) . '">';
		echo wp_kses_post( $message );
		echo '</div>';
		$dashboard_object = $this->pages['forminator-dashboard'];
		echo '<div class="' . esc_attr( $dashboard_object->get_sui_body_class() ) . '">';
		echo '<div class="sui-wrap wpmudev-forminator-forminator">';
		$dashboard_object->template( 'dashboard/promote-free-plan' );
		echo '</div>';
		echo '</div>';
	}

	/**
	 * Displays an admin notice when Forminator version is 1.16.0 or higher and Stripe Addon version is less than 1.0.4
	 * Shown in forminator pages. Per user notification.
	 */
	public function check_stripe_addon_version() {
		$min_stripe_addon_version = '1.3.0';
		// Show the notice only if Stripe Addon is active and its version is less than 1.0.4.
		if ( ! defined( 'FORMINATOR_STRIPE_ADDON' ) || ! class_exists( 'Forminator_Stripe_Addon' )
			|| version_compare( FORMINATOR_STRIPE_ADDON, $min_stripe_addon_version . '-alpha', '>=' ) ) {
			return;
		}

		// Show the notice only for administrators.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$message = '<p>';
		/* translators: 1. Forminator version. 2. Min Stripe addon version. */
		$message .= sprintf( esc_html__( 'We\'ve noticed you have updated to Forminator Pro version %1$s. Please ensure you also update your Forminator Stripe Subscriptions Add-on to version %2$s or higher to ensure compatibility with the new submissions processes.', 'forminator' ), FORMINATOR_VERSION, $min_stripe_addon_version );
		$message .= '</p>';

		echo '<div class="forminator-grouped-notice notice notice-error"'
			. ' data-notice-slug="forminator_pro_is_available"'
			. '>';
		echo wp_kses_post( $message );
		echo '</div>';
	}

	/**
	 * Check if the given notification was dismissed.
	 *
	 * @param string $notification_name Notification slug.
	 *
	 * @return bool
	 */
	public static function was_notification_dismissed( $notification_name ) {
		$dismissed = get_user_meta( get_current_user_id(), 'frmt_dismissed_messages', true );

		return ( is_array( $dismissed ) && in_array( $notification_name, $dismissed, true ) );
	}

	/**
	 * Return true if Remind me later was clicked
	 *
	 * @return bool
	 */
	private static function maybe_remind_later() {
		$option = get_transient( 'forminator_free_plan_remind_later_' . get_current_user_id() );

		return (bool) $option;
	}

	/**
	 * Check if the current user is able to update plugins
	 *
	 * @return bool
	 */
	public static function user_can_update_plugins() {
		$cap = is_multisite() ? 'manage_network_plugins' : 'update_plugins';

		return current_user_can( $cap );
	}

	/**
	 * Show CF7 importer notice
	 *
	 * @since 1.11
	 */
	public function show_cf7_importer_notice() {
		$notice_dismissed = get_option( 'forminator_cf7_notice_dismissed', false );

		if ( $notice_dismissed ) {
			return;
		}

		if ( ! forminator_is_import_plugin_enabled( 'cf7' ) ) {
			return;
		}

		?>
		<div class="forminator-notice-cf7 forminator-notice notice notice-info"
			data-prop="forminator_cf7_notice_dismissed"
			data-nonce="<?php echo esc_attr( wp_create_nonce( 'forminator_dismiss_notification' ) ); ?>">
			<p style="color: #1A2432; font-size: 14px; font-weight: bold;"><?php echo esc_html__( 'Forminator - Import your Contact Form 7 forms automatically', 'forminator' ); ?></p>

			<p style="color: #72777C; line-height: 22px;"><?php echo esc_html__( 'We noticed that Contact Form 7 is active on your website. You can use our built-in Contact Form 7 importer to import your existing forms and the relevant plugin settings from Contact Form 7 to Forminator. The importer supports the most widely used add-ons as well.', 'forminator' ); ?></p>

			<p>
				<a href="<?php echo esc_url( menu_page_url( 'forminator-settings', false ) . '&section=import' ); ?>"
					class="button button-primary"><?php esc_html_e( 'Import Contact Form 7 Forms', 'forminator' ); ?></a>
				<a href="#" class="dismiss-notice"
					style="margin-left: 10px; text-decoration: none; color: #555; font-weight: 500;"><?php esc_html_e( 'Dismiss', 'forminator' ); ?></a>
			</p>

		</div>

		<script type="text/javascript">
			jQuery('.forminator-notice-cf7 .button-primary').on('click', function (e) {
				e.preventDefault();

				var $self = jQuery(this);
				var $notice = jQuery(e.currentTarget).closest('.forminator-notice');
				var ajaxUrl = '<?php echo esc_url( forminator_ajax_url() ); ?>';

				jQuery.post(
					ajaxUrl,
					{
						action: 'forminator_dismiss_notification',
						prop: $notice.data('prop'),
						_ajax_nonce: $notice.data('nonce')
					}
				).always(function () {
					location.href = $self.attr('href');
				});
			});

			jQuery('.forminator-notice-cf7 .dismiss-notice').on('click', function (e) {
				e.preventDefault();

				var $notice = jQuery(e.currentTarget).closest('.forminator-notice');
				var ajaxUrl = '<?php echo esc_url( forminator_ajax_url() ); ?>';

				jQuery.post(
					ajaxUrl,
					{
						action: 'forminator_dismiss_notification',
						prop: $notice.data('prop'),
						_ajax_nonce: $notice.data('nonce')
					}
				).always(function () {
					$notice.hide();
				});
			});
		</script>
		<?php
	}

	/**
	 * Show Stripe admin notice
	 *
	 * @since 1.9
	 */
	public function show_stripe_updated_notice() {
		$notice_dismissed = get_option( 'forminator_stripe_notice_dismissed', false );

		if ( $notice_dismissed ) {
			return;
		}

		if ( ! $this->has_old_stripe_forms() ) {
			return;
		}
		?>

		<div class="forminator-notice notice notice-warning" data-prop="forminator_stripe_notice_dismissed"
			data-nonce="<?php echo esc_attr( wp_create_nonce( 'forminator_dismiss_notification' ) ); ?>">

			<p style="color: #72777C; line-height: 22px;">
				<?php
				printf(
				/* Translators: 1. Opening <a> tag with link to stripe SCA Compliant, 2. closing <a> tag. */
					esc_html__( 'To make Forminator\'s Stripe field %1$sSCA Compliant%2$s, we have replaced the Stripe Checkout modal with Stripe Elements which adds an inline field to collect your customer\'s credit or debit card details. Your existing forms with Stripe field are automatically updated, but we recommend checking them to ensure everything works fine.', 'forminator' ),
					'<a href="https://stripe.com/gb/guides/strong-customer-authentication" target="_blank">',
					'</a>'
				);
				?>
			</p>

			<p>
				<a href="<?php echo esc_url( menu_page_url( 'forminator', false ) . '&show_stripe_dialog=true' ); ?>"
					class="button button-primary"><?php esc_html_e( 'Learn more', 'forminator' ); ?></a>
				<a href="#" class="dismiss-notice"
					style="margin-left: 10px; text-decoration: none; color: #555; font-weight: 500;"><?php esc_html_e( 'Dismiss', 'forminator' ); ?></a>
			</p>

		</div>

		<script type="text/javascript">
			jQuery('.forminator-notice .dismiss-notice').on('click', function (e) {
				e.preventDefault();

				var $notice = jQuery(e.currentTarget).closest('.forminator-notice');
				var ajaxUrl = '<?php echo esc_url( forminator_ajax_url() ); ?>';

				jQuery.post(
					ajaxUrl,
					{
						action: 'forminator_dismiss_notification',
						prop: $notice.data('prop'),
						_ajax_nonce: $notice.data('nonce')
					}
				).always(function () {
					$notice.hide();
				});
			});
		</script>
		<?php
	}

	/**
	 * Show the Stripe Restricted API Key notice.
	 *
	 * @since 1.33
	 */
	public function show_stripe_restricted_api_key_notice() {
		$notice_dismissed = get_option( 'forminator_stripe_rak_notice_dismissed', false );

		if ( $notice_dismissed ) {
			return;
		}

		$config      = get_option( 'forminator_stripe_configuration', array() );
		$test_secret = isset( $config['test_secret'] ) ? $config['test_secret'] : '';
		$live_secret = isset( $config['live_secret'] ) ? $config['live_secret'] : '';
		if ( empty( $test_secret ) && empty( $live_secret ) ) {
			return;
		}

		if ( ( ! empty( $test_secret ) && 'rk_' === substr( $test_secret, 0, 3 ) ) && ( ! empty( $live_secret ) && 'rk_' === substr( $live_secret, 0, 3 ) ) ) {
			return;
		}
		?>

		<div class="forminator-notice notice notice-warning is-dismissible" data-prop="forminator_stripe_rak_notice_dismissed"
			data-nonce="<?php echo esc_attr( wp_create_nonce( 'forminator_dismiss_notification' ) ); ?>">
			<p style="color: #72777C; line-height: 22px;">
				<?php
				$learn_more = '';
				if ( forminator_is_show_documentation_link() ) {
					$learn_more = sprintf(
						' <a href="%1$s" target="_blank">%2$s</a>.',
						esc_url( 'https://wpmudev.com/docs/wpmu-dev-plugins/forminator/#connect-to-stripe' ),
						esc_html__( 'Learn More', 'forminator' )
					);
				}
				printf(
				/* Translators: 1. Opening <b> tag, 2. closing <b> tag, 3. Opening <a> tag with link to payments settings section, 4. closing <a> tag, 5. Learn More with link (optional, already formatted HTML, may be empty). */
					esc_html__( '%1$sStripe API Notice:%2$s You are currently using the deprecated Stripe Secret key in your %3$sForminator Stripe integration%4$s. We recommend switching to the Restricted API key (RAK) instead.%5$s', 'forminator' ),
					'<b>',
					'</b>',
					'<a href="' . esc_url( admin_url( 'admin.php?page=forminator-settings&section=payments' ) ) . '" target="_blank">',
					'</a>',
					wp_kses_post( $learn_more )
				);
				?>
			</p>
			<button type="button" class="notice-dismiss forminator-stripe-rak-notice-dismiss">
				<span class="screen-reader-text"></span>
			</button>
		</div>

		<script type="text/javascript">
			jQuery('.forminator-notice .forminator-stripe-rak-notice-dismiss').on('click', function (e) {
				e.preventDefault();

				var $notice = jQuery(e.currentTarget).closest('.forminator-notice');
				var ajaxUrl = '<?php echo esc_url( forminator_ajax_url() ); ?>';

				jQuery.post(
					ajaxUrl,
					{
						action: 'forminator_dismiss_notification',
						prop: $notice.data('prop'),
						_ajax_nonce: $notice.data('nonce')
					}
				).always(function () {
					$notice.hide();
				});
			});
		</script>
		<?php
	}

	/**
	 * Has active integrations
	 *
	 * @return bool
	 */
	private function has_active_integrations() {
		$addons = forminator_get_registered_addons_grouped_by_connected();
		if ( ! empty( $addons['connected'] ) ) {
			$excluded_addons_for_encryption = array( 'webhook', 'hubspot', 'aweber' );
			$connected_addons               = array_filter(
				$addons['connected'],
				function ( $addon ) use ( $excluded_addons_for_encryption ) {
					if ( in_array( $addon['slug'], $excluded_addons_for_encryption, true ) ) {
						return false;
					}
					return true;
				}
			);
			if ( ! empty( $connected_addons ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Show admin notice for setting forminator encryption key
	 *
	 * @since 1.35.1
	 */
	public function set_encryption_key_notice() {
		// show only for WP admins.
		if ( ! current_user_can( 'manage_options' )
				|| ! Forminator_Encryption::use_wp_salt()
				|| ( ! get_option( 'forminator_stripe_configuration' ) && ! get_option( 'forminator_paypal_configuration' ) && ! $this->has_active_integrations() ) ) {
			return;
		}
		$news    = __( 'Forminator encrypts and securely stores all payment and integration keys.', 'forminator' );
		$see_doc = forminator_is_show_documentation_link() ? sprintf(
			/* Translators: 1. Opening <a> tag with link to documentation, 2. Closing <a> tag. */
			esc_html__( 'For more information, %1$ssee our documentation%2$s.', 'forminator' ),
			'<a href="https://wpmudev.com/docs/wpmu-dev-plugins/forminator/#add-forminator-encryption-key-config" target="_blank">',
			'</a>'
		) : '';
		?>

		<div class="forminator-notice notice notice-info fui-wordpress-notice" >
			<p>
				<strong>
					<?php echo esc_html__( 'Secure Your Keys', 'forminator' ); ?>
				</strong>
			</p>

			<?php if ( Forminator_Admin_AJAX::can_write_to_wp_config() ) { ?>

			<p>
				<?php echo esc_html( $news ); ?>
				<?php
				printf(
				/* Translators: 1. Opening <b> tag. 2. Closing <b< tag. 3. Constant name. 4. File name. */
					esc_html__( 'Click %1$sAdd Key%2$s to add the required %3$s to your %4$s for enhanced security.', 'forminator' ),
					'<strong>',
					'</strong>',
					'<code>FORMINATOR_ENCRYPTION_KEY</code>',
					'<code>wp-config.php</code>'
				);
				?>
				<?php echo wp_kses_post( $see_doc ); ?>
			</p>

			<p><a type="button"
					href="#"
					id="forminator-set-encryption-key"
					data-nonce="<?php echo esc_attr( wp_create_nonce( 'forminator_set_encryption_key' ) ); ?>"
					class="button button-primary button-large"
				><?php esc_html_e( 'ADD KEY', 'forminator' ); ?></a>
			</p>
			<script type="text/javascript">
				jQuery('#forminator-set-encryption-key').on('click', function (e) {
					e.preventDefault();

					var nonce = jQuery(e.currentTarget).data('nonce');
					var $notice = jQuery(e.currentTarget).closest('.forminator-notice');
					var ajaxUrl = '<?php echo esc_url( forminator_ajax_url() ); ?>';

					jQuery.post(
						ajaxUrl,
						{
							action: 'forminator_set_encryption_key',
							_ajax_nonce: nonce
						}
					).done(function (response) {
						if ( ! response.success ) {
							console.log(response);
						}
					}).always(function () {
						$notice.hide();
					});
				});
			</script>

			<?php } else { ?>

			<p>
				<?php echo esc_html( $news ); ?>
				<?php
				printf(
				/* Translators: 1. Constant name. 2. File name. */
					esc_html__( 'To ensure enhanced security, please add the %1$s constant to your %2$s file.', 'forminator' ),
					'<code>FORMINATOR_ENCRYPTION_KEY</code>',
					'<code>wp-config.php</code>'
				);
				?>
				<?php echo wp_kses_post( $see_doc ); ?>
			</p>
			<p>
				<?php
				printf(
				/* Translators: Code example. */
					esc_html__( 'Example: %s', 'forminator' ),
					'<code>define( \'FORMINATOR_ENCRYPTION_KEY\', \''
					. esc_html( Forminator_Encryption::generate_encryption_key() ) . '\' );</code>'
				);
				?>
			</p>
			<?php } ?>
		</div>
		<?php
	}

	/**
	 * Show rating admin notice
	 *
	 * @since 1.10
	 */
	public function show_rating_notice() {
		

		$notice_success   = get_option( 'forminator_rating_success', false );
		$notice_dismissed = get_option( 'forminator_rating_dismissed', false );

		if ( $notice_dismissed || $notice_success ) {
			return;
		}
		$published_modules     = forminator_total_forms( 'publish' );
		$publish_later         = get_option( 'forminator_publish_rating_later', false );
		$publish_later_dismiss = get_option( 'forminator_publish_rating_later_dismiss', false );

		if ( ( ( 5 < $published_modules && 10 >= $published_modules ) && ! $publish_later ) || ( 10 < $published_modules && ! $publish_later_dismiss ) ) {

			$milestone = ( 10 >= $published_modules ) ? 5 : 10;
			$data_type = ( 10 >= $published_modules ) ? 'five_modules' : 'ten_modules';
			?>

			<div id="forminator-free-publish-notice"
				class="forminator-rating-notice notice notice-info fui-wordpress-notice"
				data-nonce="<?php echo esc_attr( wp_create_nonce( 'forminator_dismiss_notification' ) ); ?>">

				<p style="color: #72777C; line-height: 22px;"><?php /* translators: %d: Module count. */ printf( esc_html__( 'Awesome! You\'ve published more than %d modules with Forminator. Hope you are enjoying it so far. We have spent countless hours developing this free plugin for you, and we would really appreciate it if you could drop us a rating on wp.org to help us spread the word and boost our motivation.', 'forminator' ), (int) $milestone ); ?></p>

				<p>
					<a type="button" href="#" target="_blank" class="button button-primary button-large"
						data-prop="forminator_rating_success"
						data-type="<?php echo esc_attr( $data_type ); ?>"><?php esc_html_e( 'Rate Forminator', 'forminator' ); ?></a>

					<button type="button" class="button button-large" style="margin-left: 11px;"
							data-prop="<?php echo 10 > $published_modules ? 'forminator_publish_rating_later' : 'forminator_publish_rating_later_dismiss'; ?>"
							data-type="<?php echo esc_attr( $data_type ); ?>"><?php esc_html_e( 'Maybe later', 'forminator' ); ?></button>

					<a href="#" class="dismiss"
						style="margin-left: 11px; color: #555; line-height: 16px; font-weight: 500; text-decoration: none;"
						data-prop="forminator_rating_dismissed"
						data-type="<?php echo esc_attr( $data_type ); ?>"><?php esc_html_e( 'No Thanks', 'forminator' ); ?></a>
				</p>

			</div>

			<?php
		} else {

			$install_date       = get_site_option( 'forminator_free_install_date', false );
			$days_later_dismiss = get_option( 'forminator_days_rating_later_dismiss', false );
			// phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested -- We are using the current timestamp based on the site's timezone.
			if ( $install_date && current_time( 'timestamp' ) > strtotime( '+7 days', $install_date ) && ! $publish_later && ! $publish_later_dismiss && ! $days_later_dismiss ) {
				?>

				<div id="forminator-free-usage-notice"
					class="forminator-rating-notice notice notice-info fui-wordpress-notice"
					data-nonce="<?php echo esc_attr( wp_create_nonce( 'forminator_dismiss_notification' ) ); ?>">

					<p style="color: #72777C; line-height: 22px;"><?php esc_html_e( 'Excellent! You\'ve been using Forminator for a while now. Hope you are enjoying it so far. We have spent countless hours developing this free plugin for you, and we would really appreciate it if you could drop us a rating on wp.org to help us spread the word and boost our motivation.', 'forminator' ); ?></p>

					<p>
						<a type="button" href="#" target="_blank" class="button button-primary button-large"
							data-prop="forminator_rating_success"
							data-type="seven_days"><?php esc_html_e( 'Rate Forminator', 'forminator' ); ?></a>

						<a href="#" class="dismiss"
							style="margin-left: 11px; color: #555; line-height: 16px; font-weight: 500; text-decoration: none;"
							data-prop="forminator_days_rating_later_dismiss"
							data-type="seven_days"><?php esc_html_e( 'Maybe later', 'forminator' ); ?></a>
					</p>

				</div>

				<?php
			}
		}

		?>

		<script type="text/javascript">
			jQuery('.forminator-rating-notice a, .forminator-rating-notice button').on('click', function (e) {
				e.preventDefault();

				var $notice = jQuery(e.currentTarget).closest('.forminator-rating-notice'),
					prop = jQuery(this).data('prop'),
					type = jQuery(this).data('type'),
					ajaxUrl = '<?php echo esc_url( forminator_ajax_url() ); ?>';

				if ('forminator_rating_success' === prop) {
					window.open('https://wordpress.org/support/plugin/forminator/reviews/#new-post', '_blank');
				}

				jQuery.post(
					ajaxUrl,
					{
						action: 'forminator_dismiss_notification',
						prop: prop,
						location: window.pagenow,
						type: type,
						_ajax_nonce: $notice.data('nonce')
					}
				).always(function () {
					$notice.hide();
				});
			});
		</script>

		<?php
	}

	/**
	 * Show action links on the plugin screen.
	 *
	 * @param array $links Plugin Action links.
	 *
	 * @return mixed
	 * @since 1.13
	 */
	public function add_plugin_action_links( $links ) {
		// Settings link.
		if ( current_user_can( forminator_get_admin_cap() ) ) {
			$action_links['dashboard'] = '<a href="' . admin_url( 'admin.php?page=forminator' ) . '" aria-label="' . esc_attr__( 'Go to Forminator Dashboard', 'forminator' ) . '">' . esc_html__( 'Dashboard', 'forminator' ) . '</a>';
		}

		// Documentation link.
		$action_links['docs'] = '<a href="' . forminator_get_link( 'docs', 'forminator_pluginlist_docs' ) . '" aria-label="' . esc_attr__( 'Docs', 'forminator' ) . '" target="_blank">' . esc_html__( 'Docs', 'forminator' ) . '</a>';

		// Check if the current logged-in member has access Forminator Pro.
		$can_install_pro = forminator_can_install_pro();

		$membership_type = forminator_get_wpmudev_membership();

		// Upgrade or Renew links.
		if ( ! FORMINATOR_PRO ) {
			if ( $can_install_pro ) {
				$action_links['upgrade'] = '<a href="' . forminator_get_link( 'plugin', 'forminator_pluginlist_upgrade' ) . '" aria-label="' . esc_attr__( 'Upgrade to Forminator Pro', 'forminator' ) . '" style="color: #8D00B1;" target="_blank">' . esc_html__( 'Upgrade', 'forminator' ) . '</a>';
			} else {
				$action_links['renew'] = '<a href="' . forminator_get_link( 'plugin', 'forminator_pluginlist_renew' ) . '" aria-label="' . esc_attr__( 'Get Forminator Pro', 'forminator' ) . '" style="color: #8D00B1;" target="_blank">' . esc_html__( 'Get Forminator Pro', 'forminator' ) . '</a>';
			}
		} elseif ( in_array( $membership_type, array( 'expired', 'free', 'paused', '' ), true ) && ! $can_install_pro ) {
			$action_links['renew'] = '<a href="' . forminator_get_link( 'plugin', 'forminator_pluginlist_renew' ) . '" aria-label="' . esc_attr__( 'Renew Membership', 'forminator' ) . '" style="color: #8D00B1;" target="_blank">' . esc_html__( 'Renew Membership', 'forminator' ) . '</a>';
		}

		return array_merge( $action_links, $links );
	}

	/**
	 * Show row meta on the plugin screen.
	 *
	 * @param mixed $links Plugin Row Meta.
	 * @param mixed $file Plugin Base file.
	 * @param array $plugin_data Plugin data.
	 *
	 * @return array
	 * @since 1.13
	 */
	public function plugin_row_meta( $links, $file, $plugin_data ) {
		if ( FORMINATOR_PLUGIN_BASENAME === $file ) {
			// Show network meta links only when activated network wide.
			if ( is_network_admin() && ! forminator_is_networkwide() ) {
				return $links;
			}

			// Change AuthorURI link.
			if ( isset( $links[1] ) ) {
				$author_uri = 'https://profiles.wordpress.org/wpmudev/';
				$author_uri = sprintf(
					'<a href="%s" target="_blank">%s</a>',
					$author_uri,
					esc_html__( 'WPMU DEV', 'forminator' )
				);
				$links[1]   = /* translators: %s: Authorise url. */ sprintf( esc_html__( 'By %s', 'forminator' ), $author_uri );
			}

			if ( ! FORMINATOR_PRO ) {
				// Change AuthorURI link.
				if ( isset( $links[2] ) && false === strpos( $links[2], 'target="_blank"' ) ) {
					if ( ! isset( $plugin_data['slug'] ) && $plugin_data['Name'] ) {
						$links[2] = sprintf(
							'<a href="%s" class="thickbox open-plugin-details-modal" aria-label="%s" data-title="%s">%s</a>',
							esc_url(
								network_admin_url(
									'plugin-install.php?tab=plugin-information&plugin=forminator' .
									'&TB_iframe=true&width=600&height=550'
								)
							),
							/* translators: %s: Plugin name. */
							sprintf( esc_html__( 'More information about %s', 'forminator' ), $plugin_data['Name'] ),
							esc_attr( $plugin_data['Name'] ),
							esc_html__( 'View details', 'forminator' )
						);
					} else {
						$links[2] = str_replace( 'href=', 'target="_blank" href=', $links[2] );
					}
				}
				$row_meta['rate']    = '<a href="' . esc_url( forminator_get_link( 'rate' ) ) . '" aria-label="' . esc_attr__( 'Rate Forminator', 'forminator' ) . '" target="_blank">' . esc_html__( 'Rate Forminator', 'forminator' ) . '</a>';
				$row_meta['support'] = '<a href="' . esc_url( forminator_get_link( 'support' ) ) . '" aria-label="' . esc_attr__( 'Support', 'forminator' ) . '" target="_blank">' . esc_html__( 'Support', 'forminator' ) . '</a>';
			}
			$row_meta['roadmap'] = '<a href="' . esc_url( forminator_get_link( 'roadmap' ) ) . '" aria-label="' . esc_attr__( 'Roadmap', 'forminator' ) . '" target="_blank">' . esc_html__( 'Roadmap', 'forminator' ) . '</a>';

			return array_merge( $links, $row_meta );
		}

		return $links;
	}

	/**
	 * Show addons update notice
	 */
	public function show_addons_update_notice() {
		
	}

	/**
	 * Show hosting promotion banner
	 *
	 * To test:
	 * update_option( 'forminator_free_install_date', strtotime( '-31 days', current_time( 'timestamp' ) ) );
	 * update_option( 'forminator_hosting_banner_later', strtotime( '-8 days', current_time( 'timestamp' ) ) );
	 */
	public function show_hosting_notice() {
		if ( ! current_user_can( 'manage_options' ) || forminator_is_site_connected_to_hub() || FORMINATOR_PRO ) {
			return;
		}

		// Check if the page is a forminator page but not edit module pages.
		$page = Forminator_Core::sanitize_text_field( 'page' );
		preg_match( '/^(forminator-)([a-z]+)(-wizard)/', $page, $page_slug );
		if ( isset( $page_slug[0] ) || 0 !== strpos( $page, 'forminator' ) ) {
			return;
		}

		// Check if 30days has passed since install date. I shall return...
		$install_date = get_site_option( 'forminator_free_install_date', false );
		if ( $install_date && current_time( 'timestamp' ) < strtotime( '+30 days', $install_date ) ) { // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested -- We are using the current timestamp based on the site's timezone.
			return;
		}

		$notice_dismissed = get_option( 'forminator_hosting_banner_dismiss', false );
		if ( $notice_dismissed ) {
			return;
		}

		$notice_later = get_option( 'forminator_hosting_banner_later', false );
		if ( $notice_later && current_time( 'timestamp' ) < strtotime( '+7 days', $notice_later ) ) { // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested -- We are using the current timestamp based on the site's timezone.
			return;
		}

		wp_enqueue_script(
			'forminator-hosting-banner',
			forminator_plugin_url() . 'build/hosting.js',
			array(
				'jquery',
				'react',
				'react-dom',
				'wp-element',
			),
			FORMINATOR_VERSION,
			true
		);
		?>

		<div id="shared-notifications-banner" class="sui-wrap"
			data-prop="forminator_hosting_banner_dismiss"
			data-nonce="<?php echo esc_attr( wp_create_nonce( 'forminator_dismiss_notification' ) ); ?>"
		></div>

		<script type="text/javascript">
			( function( $ ) {
			if ( 'object' !== typeof window.FORMI ) {
				window.FORMI = {};
			}
			FORMI.dismissNotice = function() {
				hanleAjaxCall( 'forminator_hosting_banner_dismiss', '' );
			};
			FORMI.reminderLater = function() {
				hanleAjaxCall( 'forminator_hosting_banner_later', <?php echo esc_html( current_time( 'timestamp' ) ); /* phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested -- We are using the current timestamp based on the site's timezone. */ ?> );
			}

			// Dismiss notice if claim button is also clicked.
			$( 'body' ).on( 'click', '#shared-notifications-banner .sui-module-notice-banner__cta-action > a', function (e) {
				$( '#shared-notifications-banner' ).find( '.sui-module-notice-banner__close' ).trigger( 'click' );
			});

			function hanleAjaxCall( prop, value ) {
				var $notice = $( '#shared-notifications-banner' );
				var ajaxUrl = '<?php echo esc_url( forminator_ajax_url() ); ?>';
				jQuery.post(
				ajaxUrl,
				{
					action: 'forminator_dismiss_notification',
					prop: prop,
					value: value,
					_ajax_nonce: $notice.data('nonce')
				}
				);
			}
			}( jQuery ) );
		</script>
		<?php
	}

	/**
	 * Upon user update, check if updated user is in permissions option.
	 *
	 * @param int|null $user_id User Id.
	 */
	public function maybe_update_permissions( $user_id = null ) {
		$permissions = get_option( 'forminator_permissions', array() );
		if ( empty( $permissions ) ) {
			return;
		}

		// Check if user ID is in the permissions.
		if ( is_null( forminator_recursive_array_search( $user_id, $permissions ) ) ) {
			return;
		}

		foreach ( $permissions as $key => $permission ) {
			/**
			 * For specific users.
			 * - Add caps to the users
			 * - Check each permission for get_avatar then retrieve it.
			 */
			if ( 'specific' === $permission['permission_type'] ) {

				foreach ( $permission['specific_user'] as $user_index => $user_id ) {
					$user = get_user_by( 'ID', $user_id );

					if ( false !== $user ) {
						// Set user info.
						$permissions[ $key ]['user_info'][ $user_id ]['name']  = $user->display_name;
						$permissions[ $key ]['user_info'][ $user_id ]['email'] = $user->user_email;

						// We only need avatar for first user.
						if ( 0 === $user_index ) {
							$permissions[ $key ]['avatar'] = get_avatar_url( $user->user_email, array( 'size' => 30 ) );
						}
					}
				}

				/**
				 * For roles.
				 * - Add caps to users under these roles.
				 */
			} else {
				if ( empty( $permission['exclude_users'] ) ) {
					continue;
				}

				// Set user info for excluded users.
				foreach ( $permission['exclude_users'] as $user_id ) {
					$user = get_user_by( 'ID', $user_id );

					if ( false !== $user ) {
						$permissions[ $key ]['user_info'][ $user_id ]['name']  = $user->display_name;
						$permissions[ $key ]['user_info'][ $user_id ]['email'] = $user->user_email;
					}
				}
			}
		}

		update_option( 'forminator_permissions', $permissions );
	}

	/**
	 * Get error notice
	 *
	 * @param string $error Error message. It should be already escaped.
	 * @return string
	 */
	public static function get_red_notice( string $error ): string {
		return static::get_notice( 'error', $error );
	}

	/**
	 * Get success notice
	 *
	 * @param string $message Success message. It should be already escaped.
	 * @return string
	 */
	public static function get_green_notice( string $message ): string {
		return static::get_notice( 'green', $message );
	}

	/**
	 * Get SUI notice
	 *
	 * @param string $type Notice type.
	 * @param string $message Message. It should be already escaped.
	 * @return string
	 */
	public static function get_notice( string $type, string $message ): string {
		$notice_class = 'green' === $type ? 'sui-notice-green' : 'sui-notice-red';
		$icon_class   = 'green' === $type ? 'sui-icon-check-tick' : 'sui-icon-info';
		return '<div role="alert" class="sui-notice ' . $notice_class . ' sui-active"
				style="display: block; text-align: left;" aria-live="assertive">
			<div class="sui-notice-content">
				<div class="sui-notice-message">
					<span class="sui-notice-icon ' . $icon_class . '" aria-hidden="true"></span>
					<p>' . $message . '</p>
				</div>
			</div>
		</div>';
	}

	/**
	 * Clear pages cache
	 *
	 * @param int|null $post_id Post ID.
	 */
	public static function clear_pages_cache( ?int $post_id = null ) {
		if ( ! is_null( $post_id ) && 'page' === get_post_type( $post_id ) ) {
			wp_cache_delete( 'forminator_cached_pages', 'forminator-cache' );
		}
	}
}
