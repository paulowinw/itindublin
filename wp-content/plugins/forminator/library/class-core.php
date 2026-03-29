<?php
/**
 * Forminator Core
 *
 * @package Forminator
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_Core
 *
 * @since 1.0
 */
class Forminator_Core {

	/**
	 * Forminator_Admin Instance
	 *
	 * @var Forminator_Admin
	 */
	public $admin;

	/**
	 * Store modules objects
	 *
	 * @var array
	 */
	public $modules = array();

	/**
	 * Store forms objects
	 *
	 * @var array
	 */
	public $forms = array();

	/**
	 * Store fields objects
	 *
	 * @var array
	 */
	public $fields = array();

	/**
	 * Store PRO fields
	 *
	 * @var array
	 */
	public $pro_fields = array();

	/**
	 * Store field objects
	 *
	 * @var array
	 */
	private static $field_objects = array();

	/**
	 * Plugin instance
	 *
	 * @var null
	 */
	private static $instance = null;

	/**
	 * Return the plugin instance
	 *
	 * @since 1.0
	 * @return Forminator
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Forminator_Core constructor.
	 *
	 * @since 1.0
	 */
	public function __construct() {
		// Include all necessary files.
		$this->includes();

		// First check if upgrade of data is needed.
		Forminator_Upgrade::init();

		if ( is_admin() ) {
			// Initialize admin core.
			$this->admin = new Forminator_Admin();

			new Forminator_Shortcode_Generator();

			add_action( 'wp_head', array( $this, 'localize_pointers' ) );
		}

		Forminator_Hub_Connector::get_instance();

		// Get enabled modules.
		$modules       = new Forminator_Modules();
		$this->modules = $modules->get_modules();

		// Get enabled fields.
		$fields       = new Forminator_Fields();
		$this->fields = $fields->get_fields();

		$this->set_field_objects();

		/**
		 * Filter Pro fields for promotion PRO version
		 *
		 * @since 1.13
		 * @param array $pro_fields Array of PRO fields e.g. [ 'field_type' => 'test', 'name' => 'test, 'icon' => 'sui-icon-pencil' ].
		 */
		$this->pro_fields = apply_filters( 'forminator_pro_fields', array() );

		// HACK: Add settings and entries page at the end of the list.
		if ( is_admin() ) {
			$this->admin->add_templates_page();
			$this->admin->add_entries_page();
			if ( Forminator::is_addons_feature_enabled() ) {
				$this->admin->add_integrations_page();
			}
			if ( forminator_global_tracking() ) {
				$this->admin->add_reports_page();
			}
			$this->admin->add_settings_page();

			if ( ! forminator_addons_disabled() ) {
				$this->admin->add_addons_page();
			}

			if ( ! FORMINATOR_PRO ) {
				$this->admin->add_upgrade_page();
			}
		}

		// Protection management.
		Forminator_Protection::get_instance();

		// Export management.
		Forminator_Export::get_instance();

		if ( forminator_global_tracking() ) {
			Forminator_Reports::get_instance();
		}

		// Post meta box.
		add_action( 'init', array( &$this, 'post_field_meta_box' ) );

		if ( ! FORMINATOR_PRO ) {
			add_action( 'init', array( __CLASS__, 'load_cross_sell_module' ) );
		}

		// Clean up Action Scheduler.
		add_action( 'init', array( $this, 'schedule_action_scheduler_cleanup' ), 999 );
		add_action( 'forminator_action_scheduler_cleanup', array( &$this, 'action_scheduler_cleanup' ) );
	}

	/**
	 * Set field objects
	 */
	private function set_field_objects() {
		if ( self::$field_objects ) {
			return;
		}
		foreach ( $this->fields as $field_object ) {
			self::$field_objects[ $field_object->slug ] = $field_object;
		}
	}

	/**
	 * Get field object by field type
	 *
	 * @param string $type Field type.
	 * @return object
	 */
	public static function get_field_object( $type ) {
		if ( 'stripe-ocs' === $type ) {
			$type = 'stripe';
		}
		$object = isset( self::$field_objects[ $type ] ) ? self::$field_objects[ $type ] : null;

		return $object;
	}

	/**
	 * Get field types
	 *
	 * @return array
	 */
	public static function get_field_types() {
		$types = array_keys( self::$field_objects );

		return $types;
	}

	/**
	 * Get field type based on $element_id
	 *
	 * @param string $element_id Field slug.
	 * @return array
	 */
	public static function get_field_type( $element_id ) {
		$field_type = '';
		$parts      = explode( '-', $element_id );
		// all avail fields on library.
		$field_types = self::get_field_types();

		if ( in_array( $parts[0], $field_types, true ) ) {
			$field_type = $parts[0];
		}

		return $field_type;
	}

	/**
	 * Initialize Mixpanel tracking.
	 *
	 * @param bool $force Force tracking.
	 * @return void
	 */
	public static function init_mixpanel( bool $force = false ) {
		if ( ! forminator_usage_tracking_disabled() && ( ( ! self::is_tracking_active() && ! $force ) || ! is_admin() ) ) {
			return;
		}

		if ( ! class_exists( 'WPMUDEV_Analytics_V4' ) ) {
			$autoload = plugin_dir_path( __FILE__ ) . 'lib/analytics/autoload.php';
			if ( ! file_exists( $autoload ) ) {
				return;
			}
			// Prefixed vendor autoload.
			include_once $autoload;
		}

		include_once plugin_dir_path( __FILE__ ) . 'mixpanel/class-mixpanel.php';

		Forminator_Mixpanel::get_instance();
	}

	/**
	 * Check if usage tracking is active.
	 *
	 * @return bool
	 */
	public static function is_tracking_active() {
		return get_option( 'forminator_usage_tracking' );
	}

	/**
	 * Includes
	 *
	 * @since 1.0
	 */
	private function includes() {
		// Abstracts.
		/* @noinspection PhpIncludeInspection */
		include_once forminator_plugin_dir() . 'library/abstracts/abstract-class-field.php';
		/* @noinspection PhpIncludeInspection */
		include_once forminator_plugin_dir() . 'library/abstracts/abstract-class-form-result.php';
		/* @noinspection PhpIncludeInspection */
		include_once forminator_plugin_dir() . 'library/abstracts/abstract-class-form-template.php';
		/* @noinspection PhpIncludeInspection */
		include_once forminator_plugin_dir() . 'library/abstracts/abstract-class-front-action.php';
		/* @noinspection PhpIncludeInspection */
		include_once forminator_plugin_dir() . 'library/abstracts/abstract-class-mail.php';
		/* @noinspection PhpIncludeInspection */
		include_once forminator_plugin_dir() . 'library/abstracts/abstract-class-payment-gateway.php';
		/* @noinspection PhpIncludeInspection */
		include_once forminator_plugin_dir() . 'library/abstracts/abstract-class-spam-protection.php';
		/* @noinspection PhpIncludeInspection */
		include_once forminator_plugin_dir() . 'library/abstracts/abstract-class-user.php';

		// Classes.
		/* @noinspection PhpIncludeInspection */
		include_once forminator_plugin_dir() . 'library/class-abandonment.php';
		/* @noinspection PhpIncludeInspection */
		include_once forminator_plugin_dir() . 'library/class-loader.php';
		/* @noinspection PhpIncludeInspection */
		include_once forminator_plugin_dir() . 'library/class-modules.php';
		/* @noinspection PhpIncludeInspection */
		include_once forminator_plugin_dir() . 'library/class-form-fields.php';
		/* @noinspection PhpIncludeInspection */
		include_once forminator_plugin_dir() . 'library/class-database-tables.php';
		/* @noinspection PhpIncludeInspection */
		include_once forminator_plugin_dir() . 'library/class-upgrade.php';
		/* @noinspection PhpIncludeInspection */
		include_once forminator_plugin_dir() . 'library/class-geo.php';
		/* @noinspection PhpIncludeInspection */
		include_once forminator_plugin_dir() . 'library/class-protection.php';
		/* @noinspection PhpIncludeInspection */
		include_once forminator_plugin_dir() . 'library/class-shortcode-generator.php';
		/* @noinspection PhpIncludeInspection */
		include_once forminator_plugin_dir() . 'library/class-export-result.php';
		/* @noinspection PhpIncludeInspection */
		include_once forminator_plugin_dir() . 'library/class-export.php';
		include_once forminator_plugin_dir() . 'library/class-template-api.php';
		/* @noinspection PhpIncludeInspection */
		include_once forminator_plugin_dir() . 'library/class-reports.php';
		/* @noinspection PhpIncludeInspection */
		include_once forminator_plugin_dir() . 'library/render/class-render-form.php';
		/* @noinspection PhpIncludeInspection */
		include_once forminator_plugin_dir() . 'library/render/class-assets-enqueue.php';
		/* @noinspection PhpIncludeInspection */

		if ( file_exists( forminator_plugin_dir() . 'library/gateways/class-paypal-express.php' ) ) {
			include_once forminator_plugin_dir() . 'library/gateways/class-paypal-express.php';
		}

		if ( file_exists( forminator_plugin_dir() . 'library/gateways/class-stripe.php' ) ) {
			/* @noinspection PhpIncludeInspection */
			include_once forminator_plugin_dir() . 'library/gateways/class-stripe.php';
		}

		/* @noinspection PhpIncludeInspection */
		include_once forminator_plugin_dir() . 'library/render/class-widget.php';
		/* @noinspection PhpIncludeInspection */
		include_once forminator_plugin_dir() . 'library/class-captcha-verification.php';
		/* @noinspection PhpIncludeInspection */
		include_once forminator_plugin_dir() . 'library/class-migration.php';

		// Models.
		/* @noinspection PhpIncludeInspection */
		include_once forminator_plugin_dir() . 'library/model/class-form-entry-model.php';

		// Helpers.
		include_once forminator_plugin_dir() . 'library/helpers/encryption.php';
		/* @noinspection PhpIncludeInspection */
		include_once forminator_plugin_dir() . 'library/helpers/helper-core.php';
		/* @noinspection PhpIncludeInspection */
		include_once forminator_plugin_dir() . 'library/helpers/helper-importer.php';
		/* @noinspection PhpIncludeInspection */
		include_once forminator_plugin_dir() . 'library/helpers/helper-modules.php';
		/* @noinspection PhpIncludeInspection */
		include_once forminator_plugin_dir() . 'library/helpers/helper-forms.php';
		/* @noinspection PhpIncludeInspection */
		include_once forminator_plugin_dir() . 'library/helpers/helper-fields.php';
		/* @noinspection PhpIncludeInspection */
		include_once forminator_plugin_dir() . 'library/helpers/helper-google-fonts.php';
		/* @noinspection PhpIncludeInspection */
		include_once forminator_plugin_dir() . 'library/helpers/helper-mail.php';
		/* @noinspection PhpIncludeInspection */
		include_once forminator_plugin_dir() . 'library/helpers/helper-currency.php';
		/* @noinspection PhpIncludeInspection */
		include_once forminator_plugin_dir() . 'library/helpers/helper-autofill.php';
		/* @noinspection PhpIncludeInspection */
		include_once forminator_plugin_dir() . 'library/helpers/helper-calculator.php';

		/* @noinspection PhpIncludeInspection */
		include_once forminator_plugin_dir() . 'library/helpers/helper-payment.php';

		// Model.
		/* @noinspection PhpIncludeInspection */
		include_once forminator_plugin_dir() . 'library/model/class-base-form-model.php';
		/* @noinspection PhpIncludeInspection */
		include_once forminator_plugin_dir() . 'library/model/class-custom-form-model.php';
		/* @noinspection PhpIncludeInspection */
		include_once forminator_plugin_dir() . 'library/model/class-form-field-model.php';
		/* @noinspection PhpIncludeInspection */
		include_once forminator_plugin_dir() . 'library/model/class-poll-form-model.php';
		/* @noinspection PhpIncludeInspection */
		include_once forminator_plugin_dir() . 'library/model/class-quiz-form-model.php';
		/* @noinspection PhpIncludeInspection */
		include_once forminator_plugin_dir() . 'library/model/class-form-views-model.php';
		include_once forminator_plugin_dir() . 'library/model/class-form-reports-model.php';

		include_once forminator_plugin_dir() . 'library/class-forminator-hub-connector.php';
		$hub_connector_lib = forminator_plugin_dir() . 'library/lib/hub-connector/connector.php';
		if ( file_exists( $hub_connector_lib ) ) {
			include_once $hub_connector_lib;
		}

		if ( is_admin() ) {
			/* @noinspection PhpIncludeInspection */
			include_once forminator_plugin_dir() . 'admin/abstracts/class-admin-page.php';
			/* @noinspection PhpIncludeInspection */
			include_once forminator_plugin_dir() . 'admin/abstracts/class-admin-view-page.php';
			/* @noinspection PhpIncludeInspection */
			include_once forminator_plugin_dir() . 'admin/abstracts/class-admin-module-edit-page.php';
			/* @noinspection PhpIncludeInspection */
			include_once forminator_plugin_dir() . 'admin/abstracts/class-admin-module.php';
			/* @noinspection PhpIncludeInspection */
			include_once forminator_plugin_dir() . 'admin/abstracts/class-admin-import-mediator.php';
			/* @noinspection PhpIncludeInspection */
			include_once forminator_plugin_dir() . 'admin/classes/class-admin.php';
			/* @noinspection PhpIncludeInspection */
			if ( ! class_exists( 'WP_List_Table' ) ) {
				/* @noinspection PhpIncludeInspection */
				require_once ABSPATH . 'wp-admin/includes/class-wp-screen.php';// added.
				/* @noinspection PhpIncludeInspection */
				require_once ABSPATH . 'wp-admin/includes/screen.php';// added.
				/* @noinspection PhpIncludeInspection */
				require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
				/* @noinspection PhpIncludeInspection */
				require_once ABSPATH . 'wp-admin/includes/template.php';
			}
			self::init_mixpanel();
		}

		if ( Forminator::is_internal_page_cache_support_enabled() ) {
			/* @noinspection PhpIncludeInspection */
			include_once forminator_plugin_dir() . 'library/class-page-cache.php';
		}
	}

	/**
	 * Sets the Plugins Cross Sell submodule.
	 */
	public static function load_cross_sell_module() {
		$cross_sell_path = forminator_plugin_dir() . 'library/lib/plugins-cross-sell-page/plugin-cross-sell.php';
		if ( ! file_exists( $cross_sell_path ) ) {
			return;
		}

		static $cross_sell = null;

		if ( is_null( $cross_sell ) ) {
			if ( ! class_exists( '\WPMUDEV\Modules\Plugin_Cross_Sell' ) ) {
				require_once $cross_sell_path;
			}

			$submenu_params = array(
				'slug'        => 'forminator',
				'parent_slug' => 'forminator',
				'menu_slug'   => 'forminator_cross_sell',
				'position'    => 17,
			);

			$cross_sell = new \WPMUDEV\Modules\Plugin_Cross_Sell( $submenu_params );
		}
	}

	/**
	 * Start creating meta box for the posts
	 *
	 * @since 1.0
	 */
	public function post_field_meta_box() {
		add_action( 'add_meta_boxes', array( $this, 'setup_post_meta_box' ) );
	}

	/**
	 * Setup the meta box
	 *
	 * @since 1.0
	 */
	public function setup_post_meta_box() {
		global $post;
		if ( is_object( $post ) ) {
			$is_forminator_meta = get_post_meta( $post->ID, '_has_forminator_meta', false );
			if ( $is_forminator_meta ) {
				add_meta_box(
					'forminator-post-meta-box',
					esc_html__( 'Post Custom Data', 'forminator' ),
					array( $this, 'render_post_meta_box' ),
					$post->post_type,
					'normal',
					'default'
				);
			}
		}
	}

	/**
	 * Localize pointers
	 *
	 * @return void
	 */
	public function localize_pointers() {
		?>
		<script type="text/javascript">
			var ajaxurl = '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>';
		</script>
		<?php
	}

	/**
	 * Render Meta box
	 *
	 * @param WP_Post $post Post.
	 *
	 * @since 1.0
	 */
	public function render_post_meta_box( $post ) {
		$meta_values = get_post_custom( $post->ID );
		?>
		<table class="widefat">
			<tbody>
			<?php
			foreach ( $meta_values as $key => $value ) {
				if ( '_' === $key[0] ) {
					continue;
				}
				$value = $value[0];
				?>
				<tr>
					<th><?php echo esc_html( $key ); ?></th>
					<td><?php echo esc_html( $value ); ?></td>
				</tr>
				<?php
			}
			?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Sanitize
	 *
	 * @param string $key POST key.
	 * @param mixed  $default_value Default value.
	 * @return mixed
	 */
	public static function sanitize_text_field( $key, $default_value = '' ) {
		if ( ! empty( $_POST[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$value = sanitize_text_field( wp_unslash( $_POST[ $key ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		} elseif ( ! empty( $_GET[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$value = sanitize_text_field( wp_unslash( $_GET[ $key ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		} else {
			$value = $default_value;
		}
		if ( 'page' === $key ) {
			$value = esc_attr( $value );
		}

		return $value;
	}

	/**
	 * Recursively sanitize data
	 *
	 * @param array  $data Data.
	 * @param string $current_key Current key.
	 *
	 * @return array|string
	 */
	public static function sanitize_array( $data, $current_key = '' ) {
		$data         = wp_unslash( $data );
		$skipped_keys = array( 'preview_data' );
		// TODO: Should skip fields that has its own sanitize function.
		if (
			in_array( $current_key, $skipped_keys, true ) ||
			0 === strpos( $current_key, 'url-' ) ||
			0 === strpos( $current_key, 'select-' ) ||
			0 === strpos( $current_key, 'checkbox-' ) ||
			0 === strpos( $current_key, 'password-' ) ||
			0 === strpos( $current_key, 'confirm_password-' )
		) {
			return $data;
		}

		$allow_html = array(
			'variations',
			'question_description',
			'thankyou-message',
			'email-thankyou-message',
			'manual-thankyou-message',
			'user-email-editor',
			'admin-email-editor',
			'quiz_description',
			'question_description',
			'email-editor',
			'email-editor-method-email',
			'email-editor-method-manual',
			'msg_count',
			'confirm-password-description',
			'description',
			'consent_description',
			'hc_invisible_notice',
			'options_bulk_editor',
			'label',
			'value',
			'importable',
			'sc_message',
			'hidden-registration-form-message',
			'hidden-login-form-message',
			'footer_value',
			'payee_info',
			'payer_info',
			'payment_note',
			'product_value',
		);

		$allow_iframe = array( 'variations' );
		if (
			in_array( $current_key, $allow_html, true ) ||
			0 === strpos( $current_key, 'html-' ) ||
			0 === strpos( $current_key, 'textarea-' ) ||
			0 === strpos( $current_key, 'radio-' ) ||
			false !== strpos( $current_key, '-post-title' ) ||
			false !== strpos( $current_key, '-post-content' ) ||
			false !== strpos( $current_key, '-post-excerpt' )
		) {
			if ( in_array( $current_key, $allow_iframe, true ) ) {
				// To allow iframes in content.
				add_filter( 'wp_kses_allowed_html', array( __CLASS__, 'maybe_add_iframe_to_kses_allowed_html' ) );
				$data = trim( wp_kses_post( $data ) );
				remove_filter( 'wp_kses_allowed_html', array( __CLASS__, 'maybe_add_iframe_to_kses_allowed_html' ) );
				return $data;
			}
			return trim( wp_kses_post( $data ) );
		}

		// Allow line breaks.
		$allow_linebreaks = array(
			'custom_css',
			'placeholder',
		);
		if ( in_array( $current_key, $allow_linebreaks, true ) ) {
			return sanitize_textarea_field( $data );
		}

		// Cannot use esc_url_raw coz it strips curly braces.
		if ( 'redirect-url' === $current_key
			|| 'formula' === $current_key ) { // Cannot use sanitize_text_field as it removes percentage strings.
			return trim( wp_strip_all_tags( $data ) );
		}

		if ( ! is_array( $data ) ) {
			return sanitize_text_field( $data );
		} else {
			foreach ( $data as $key => $value ) {
				$data[ $key ] = self::sanitize_array( $value, $key );
			}

			return $data;
		}
	}

	/**
	 * Recursively sanitize html data
	 *
	 * @param array $data Data.
	 *
	 * @return array|string
	 */
	public static function sanitize_html_array( $data ) {
		if ( ! is_array( $data ) ) {
			return esc_html( $data );
		} else {
			foreach ( $data as $key => $value ) {
				$data[ $key ] = self::sanitize_html_array( $value );
			}

			return $data;
		}
	}

	/**
	 * Shedule the Action Scheduler cleanup every hour.
	 *
	 * @return mixed
	 */
	public function schedule_action_scheduler_cleanup() {
		forminator_set_recurring_action( 'forminator_action_scheduler_cleanup', HOUR_IN_SECONDS * 2 );
	}

	/**
	 * Delete Action Scheduler actions and logs of Forminator.
	 *
	 * @param null|string $db_prefix DB Prefix.
	 *
	 * @return void
	 */
	public static function action_scheduler_cleanup( $db_prefix = null ) {
		global $wpdb;
		$is_uninstall = false;

		// If null, its being called by AS action hook.
		if ( is_null( $db_prefix ) ) {
			$db_prefix = $wpdb->prefix;
		} else {
			// Plugin is being uninstalled, unschedule all and all forminator scheduled actions.
			$is_uninstall = true;
		}

		$table_actions = $db_prefix . 'actionscheduler_actions';
		$table_logs    = $db_prefix . 'actionscheduler_logs';
		$table_groups  = $db_prefix . 'actionscheduler_groups';
		$slug          = 'forminator';

		// Check if all tables exist.
		if ( ! self::check_action_scheduler_tables( $db_prefix ) ) {
			return;
		}

		$group_id = (int) $wpdb->get_var( $wpdb->prepare( "SELECT group_id FROM {$table_groups} WHERE slug = %s", $slug ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery
		$and      = '';

		// If not uninstall, do not delete pending tasks.
		if ( ! $is_uninstall ) {
			$and = "AND ( as_actions.status = 'complete' || as_actions.status = 'failed' || as_actions.status = 'canceled' )";
		}
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
		$query = $wpdb->prepare(
			"SELECT action_id
			FROM {$table_actions} as_actions
			WHERE as_actions.group_id = %s
			" . $and . '
			LIMIT 100',
			$group_id
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared

		// Delete all AS forminator actions and logs.
		while ( $action_ids = $wpdb->get_col( $query ) ) { // phpcs:ignore Generic.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery
			if ( empty( $action_ids ) ) {
				break;
			}

			$where_in = implode(
				', ',
				array_fill(
					0,
					is_array( $action_ids ) || $action_ids instanceof \Countable ? count( $action_ids ) : 0,
					'%s'
				)
			);

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->query(
				// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
				$wpdb->prepare(
					"DELETE as_actions, as_logs
					 FROM {$table_actions} as_actions
					 LEFT JOIN {$table_logs} as_logs
						ON as_actions.action_id = as_logs.action_id
					 WHERE as_actions.action_id IN ( {$where_in} )",
					$action_ids
				)
				// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
			);
		}
	}

	/**
	 * Check if Action Scheduler tables exist.
	 *
	 * @param string $db_prefix DB Prefix.
	 *
	 * @return bool
	 */
	public static function check_action_scheduler_tables( $db_prefix = null ) {
		global $wpdb;

		if ( is_null( $db_prefix ) ) {
			$db_prefix = $wpdb->prefix;
		}

		$table_actions = $db_prefix . 'actionscheduler_actions';
		$table_logs    = $db_prefix . 'actionscheduler_logs';
		$table_groups  = $db_prefix . 'actionscheduler_groups';

		// Check if all tables exist.
		$table_count = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				'SELECT count(*)
				FROM information_schema.tables
				WHERE table_schema = %s AND table_name IN (%s, %s, %s)',
				$wpdb->dbname,
				$table_actions,
				$table_logs,
				$table_groups
			)
		);

		if ( 3 !== $table_count ) {
			return false;
		}

		return true;
	}

	/**
	 * Add iframe to the wp_kses_allowed_html filter for the front end.
	 * It is strongly recommended to call this method via maybe_add_iframe_to_kses_allowed_html unless it's an exceptional case.
	 *
	 * @param array $allowed_html Allowed HTML tags.
	 * @return array
	 */
	public static function add_iframe_to_kses_allowed_html( $allowed_html ) {
		$allowed_html['iframe'] = array(
			'align'           => true,
			'width'           => true,
			'height'          => true,
			'frameborder'     => true,
			'name'            => true,
			'src'             => true,
			'id'              => true,
			'class'           => true,
			'style'           => true,
			'scrolling'       => true,
			'marginwidth'     => true,
			'marginheight'    => true,
			'allowfullscreen' => true,
		);
		return $allowed_html;
	}

	/**
	 * Add iframe on filter wp_kses_allowed_html
	 *
	 * @param array $allowed_html Allowed HTML tags.
	 * @return array
	 */
	public static function maybe_add_iframe_to_kses_allowed_html( $allowed_html ) {
		if ( current_user_can( 'unfiltered_html' ) ) {
			return self::add_iframe_to_kses_allowed_html( $allowed_html );
		}
		return $allowed_html;
	}
}
