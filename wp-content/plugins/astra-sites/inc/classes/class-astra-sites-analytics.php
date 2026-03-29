<?php
/**
 * Astra Sites Analytics
 *
 * @since  4.4.27
 * @package Astra Sites
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Astra_Sites_Analytics' ) ) {

	/**
	 * Astra_Sites_Analytics
	 */
	class Astra_Sites_Analytics {

		/**
		 * Instance of Astra_Sites_Analytics
		 *
		 * @since  4.4.27
		 * @var self Astra_Sites_Analytics
		 */
		private static $instance = null;

		/**
		 * Instance of Astra_Sites_Analytics.
		 *
		 * @since  4.4.27
		 *
		 * @return self Class object.
		 */
		public static function get_instance() {
			if ( ! isset( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Constructor.
		 *
		 * @since  4.4.27
		 */
		private function __construct() {
			add_action( 'astra_sites_after_plugin_activation', array( $this, 'update_settings_after_plugin_activation' ), 10, 2 );
			add_action( 'admin_init', array( $this, 'maybe_update_finish_setup_banner_clicked' ) );
			add_action( 'wp_ajax_astra_sites_set_woopayments_analytics', array( $this, 'set_woopayments_analytics' ) );
			add_filter( 'bsf_core_stats', array( $this, 'add_astra_sites_analytics_data' ), 10, 1 );
			add_action( 'customize_save_after', array( $this, 'track_astra_customizer_update' ) );

			// Track import events for events_record.
			add_action( 'astra_sites_import_complete', array( $this, 'track_template_imported_event' ) );
			add_action( 'ast_block_templates_after_block_import', array( $this, 'track_block_pattern_imported_event' ), 10, 2 );
			add_action( 'ast_block_templates_after_kit_import', array( $this, 'track_kit_imported_event' ) );

			// Track content edits for KPI records.
			add_action( 'save_post', array( $this, 'track_content_edited' ), 10, 2 );
		}

		/**
		 * Update settings after plugin activation.
		 *
		 * @param string $plugin_init The plugin initialization path.
		 * @param array  $data        Additional data (optional).
		 *
		 * @since 4.4.27
		 * @return void
		 */
		public function update_settings_after_plugin_activation( $plugin_init, $data = array() ) {
			// Bail if the plugin slug is not set or empty.
			if ( ! isset( $data['plugin_slug'] ) || '' === $data['plugin_slug'] ) {
				return;
			}

			// Set WooPayments related settings.
			$this->maybe_woopayments_included( $plugin_init, $data );

			$plugin_slug      = $data['plugin_slug'];
			$required_plugins = Astra_Sites_Page::get_instance()->get_setting( 'required_plugins', array() );

			// If the plugin is already activated by starter templates, return early.
			if ( ( isset( $required_plugins[ $plugin_slug ] ) && 'activated' === $required_plugins[ $plugin_slug ] ) ) {
				return;
			}

			// If required plugins is not an array, initialize it.
			if ( ! is_array( $required_plugins ) ) {
				$required_plugins = array();
			}

			// Set the plugin activation status and update in settings.
			$required_plugins[ $plugin_slug ] = isset( $data['was_plugin_active'] ) && $data['was_plugin_active'] ? 'was_active' : 'activated';
			Astra_Sites_Page::get_instance()->update_settings(
				array(
					'required_plugins' => $required_plugins,
				)
			);
		}

		/**
		 * Maybe update finish setup banner clicked.
		 *
		 * @since 4.4.37
		 * @return void
		 */
		public function maybe_update_finish_setup_banner_clicked() {
			// Only allow administrators to update this setting.
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			// Bail early if the source is not dashboard-banner.
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verification not needed here.
			if ( ! isset( $_GET['source'] ) || 'dashboard-banner' !== sanitize_text_field( $_GET['source'] ) ) {
				return;
			}

			// Update the finish setup banner clicked setting.
			Astra_Sites_Page::get_instance()->update_settings(
				array(
					'fs_banner_clicked' => 'yes',
				)
			);
		}

		/**
		 * Check if WooCommerce Payments plugin is included and update settings accordingly.
		 * 
		 * @param string $plugin_init The plugin initialization path.
		 * @param array  $data Additional data (optional).
		 *
		 * @since 4.4.23
		 * @return void
		 */
		public function maybe_woopayments_included( $plugin_init, $data = array() ) {
			if ( 'woocommerce-payments/woocommerce-payments.php' === $plugin_init ) {
				// Prevent showing the banner if plugin was already active.
				if ( ! isset( $data['was_plugin_active'] ) || ! $data['was_plugin_active'] ) {
					Astra_Sites_Page::get_instance()->update_settings(
						array(
							'woopayments_ref'     => true,
							'wcpay_referred_time' => time(),
						)
					);
				}

				Astra_Sites_Page::get_instance()->update_settings(
					array(
						'woopayments_included' => true,
					)
				);
			}
		}

		/**
		 * Set WooPayments analytics.
		 *
		 * @since 4.4.23
		 * @return void
		 */
		public function set_woopayments_analytics() {
			// Verify user has permission to modify settings.
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'astra-sites' ) ) );
				exit;
			}

			// Verify nonce.
			if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'woopayments_nonce' ) ) {
				wp_send_json_error( array( 'message' => __( 'Invalid nonce', 'astra-sites' ) ) );
				exit;
			}

			$source = isset( $_POST['source'] ) ? sanitize_text_field( wp_unslash( $_POST['source'] ) ) : '';
			if ( ! in_array( $source, array( 'banner', 'onboarding' ), true ) ) {
				wp_send_json_error( array( 'message' => __( 'Invalid source', 'astra-sites' ) ) );
				exit;
			}

			$key = "woopayments_{$source}_clicked";
			Astra_Sites_Page::get_instance()->update_settings( array( $key => true ) );

			wp_send_json_success( array( 'message' => 'WooPayments analytics updated!' ) );
			exit;
		}

		/**
		 * Track when a full template import completes.
		 *
		 * @param array $demo_data Demo data from the import.
		 *
		 * @since 4.4.51
		 * @return void
		 */
		public function track_template_imported_event( $demo_data = array() ) {
			$properties = array(
				'template_id'  => ! empty( $demo_data['id'] ) ? $demo_data['id'] : '',
				'template_url' => ! empty( $demo_data['astra-site-url'] ) ? $demo_data['astra-site-url'] : '',
				'title'        => ! empty( $demo_data['title']['rendered'] ) ? sanitize_text_field( $demo_data['title']['rendered'] ) : '',
				'site_type'    => ! empty( $demo_data['astra-site-type'] ) ? $demo_data['astra-site-type'] : '',
			);

			Astra_Sites_Analytics_Events::track(
				'template_imported',
				defined( 'ASTRA_SITES_VER' ) ? ASTRA_SITES_VER : 'unknown',
				$properties
			);

			// Store timestamp for KPI records.
			$this->store_kpi_timestamp( 'template' );
		}

		/**
		 * Track when a block pattern is imported.
		 *
		 * @param int    $block_id The ID of the imported block.
		 * @param string $type     The type of pattern (e.g., 'block', 'page').
		 *
		 * @since 4.4.51
		 * @return void
		 */
		public function track_block_pattern_imported_event( $block_id, $type ) {
			$properties = array(
				'block_id' => $block_id,
				'type'     => $type,
			);

			Astra_Sites_Analytics_Events::track(
				'block_pattern_imported',
				defined( 'ASTRA_SITES_VER' ) ? ASTRA_SITES_VER : 'unknown',
				$properties
			);

			// Store timestamp for KPI records — $type is 'block' or 'page'.
			$this->store_kpi_timestamp( $type );
		}

		/**
		 * Track when a template kit is imported.
		 *
		 * @since 4.4.51
		 * @return void
		 */
		public function track_kit_imported_event() {
			Astra_Sites_Analytics_Events::track(
				'kit_imported',
				defined( 'ASTRA_SITES_VER' ) ? ASTRA_SITES_VER : 'unknown'
			);

			// Store timestamp for KPI records.
			$this->store_kpi_timestamp( 'kit' );
		}

		/**
		 * Store a timestamp for KPI tracking.
		 *
		 * @param string $type The type of import ('template', 'block', 'page', 'kit', 'content_edited').
		 *
		 * @since 4.4.51
		 * @return void
		 */
		private function store_kpi_timestamp( $type ) {
			$timestamps = Astra_Sites_Page::get_instance()->get_setting( '_import_timestamps', array() );
			if ( ! is_array( $timestamps ) ) {
				$timestamps = array();
			}

			$bucket_map = array(
				'template'       => 'templates',
				'kit'            => 'kits',
				'content_edited' => 'content_edited',
			);
			$bucket     = isset( $bucket_map[ $type ] ) ? $bucket_map[ $type ] : 'block_patterns';

			if ( ! isset( $timestamps[ $bucket ] ) || ! is_array( $timestamps[ $bucket ] ) ) {
				$timestamps[ $bucket ] = array();
			}

			$timestamps[ $bucket ][] = time();
			Astra_Sites_Page::get_instance()->update_settings( array( '_import_timestamps' => $timestamps ) );
		}

		/**
		 * Track when a post, page, or CPT is added or edited.
		 *
		 * Stores a timestamp in the 'content_edited' bucket for KPI tracking.
		 * Skips auto-saves, revisions, and non-public post types.
		 *
		 * @param int      $post_id The post ID.
		 * @param \WP_Post $post    The post object.
		 *
		 * @since 4.4.51
		 * @return void
		 */
		public function track_content_edited( $post_id, $post ) {
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
				return;
			}

			if ( wp_is_post_revision( $post_id ) ) {
				return;
			}

			$post_type_obj = get_post_type_object( $post->post_type );
			if ( ! $post_type_obj || ! $post_type_obj->public ) {
				return;
			}

			$this->store_kpi_timestamp( 'content_edited' );
		}

		/**
		 * Track Astra Customizer updates after template import.
		 *
		 * This method tracks when users modify Astra Customizer settings after
		 * a successful template import. It only tracks once per user to avoid
		 * repeated logging on every save.
		 *
		 * @since 4.4.47
		 * @return void
		 */
		public function track_astra_customizer_update() {
			// Only track if import has been completed.
			$import_complete = get_option( 'astra_sites_import_complete', 'no' );
			if ( 'yes' !== $import_complete ) {
				return;
			}

			// Check if we've already tracked this (one-time tracking).
			$already_tracked = Astra_Sites_Page::get_instance()->get_setting( 'astra_customizer_updated', false );
			if ( $already_tracked ) {
				return;
			}

			// Get the current theme to ensure this is Astra-related.
			$current_theme = get_stylesheet();
			if ( 0 !== strpos( $current_theme, 'astra' ) ) {
				return;
			}

			// Mark as tracked to ensure one-time logging.
			Astra_Sites_Page::get_instance()->update_settings(
				array(
					'astra_customizer_updated' => true,
				)
			);
		}

		/**
		 * Check if WooPayments is configured and connected to Stripe.
		 *
		 * @since 4.4.24
		 * @return bool True if WooPayments is active and connected to Stripe, false otherwise.
		 */
		public static function is_woo_payments_configured() {
			// Check if WCPay account is connected to Stripe.
			if ( class_exists( 'WC_Payments' ) && method_exists( 'WC_Payments', 'get_account_service' ) ) {
				$account_service = WC_Payments::get_account_service();
				if ( method_exists( $account_service, 'is_stripe_connected' ) ) {
					return $account_service->is_stripe_connected();
				}
			}

			return false;
		}

		/**
		 * Checks if WooPayments has processed at least one transaction.
		 *
		 * @since 4.4.34
		 * @return bool True if WooPayments has transactions, false otherwise.
		 */
		private static function has_woopayments_successful_transaction() {
			// Check if WooPayments plugin is active.
			if ( ! is_plugin_active( 'woocommerce-payments/woocommerce-payments.php' ) ) {
				return false;
			}

			// Check if WooCommerce is active (required for WooPayments).
			if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) || ! function_exists( 'wc_get_orders' ) ) {
				return false;
			}

			// Get the timestamp after which to check for transactions.
			$after_timestamp = Astra_Sites_Page::get_instance()->get_setting( 'wcpay_referred_time', 0 );

			// Use WooCommerce's built-in order query system to support both legacy and HPOS storage.
			$orders = wc_get_orders(
				array(
					'payment_method' => 'woocommerce_payments',
					'status'         => array( 'completed', 'processing', 'on-hold' ),
					'limit'          => 1,
					'date_after'     => intval( $after_timestamp ),
					'meta_query'     => array( //phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Required for analytics and run in background.
						array(
							'key'     => '_wcpay_payment_transaction_id',
							'value'   => '',
							'compare' => '!=',
						),
					),
				)
			);

			return ! empty( $orders );
		}

		/**
		 * Get all posts that have been imported and not modified since import.
		 *
		 * These are posts that were imported via Starter Templates and haven't been
		 * edited by the user since import (within a 3-minute buffer for background processing).
		 *
		 * @param string $post_type The post type to check. Default is 'any'.
		 * 
		 * @since 4.4.33
		 * @return array Post IDs that have not been modified since their import.
		 */
		private static function get_unmodified_imported_post_ids( $post_type = 'any' ) {
			// Get all posts that have been imported.
			$args  = array(
				'post_type'      => $post_type,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'meta_query'     => array( //phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Required for analytics and run in background.
					array(
						'key'     => '_astra_sites_imported_post',
						'value'   => '1',
						'compare' => '=',
					),
				),
			);
			$posts = get_posts( $args );

			// Get all post IDs that have not been modified since their import.
			return array_reduce(
				$posts,
				function ( $carry, $post ) {
					$post_date     = strtotime( $post->post_date );
					$modified_date = strtotime( $post->post_modified );

					// Check if modified time is within 3 minutes (180 seconds) of post date. 3 mins buffer for background post processing.
					if ( abs( $modified_date - $post_date ) <= 180 ) {
						$carry[] = $post->ID;
					}
					return $carry;
				},
				array()
			);
		}

		/**
		 * Check if any user-engaged posts exist for a given post type.
		 *
		 * "User-engaged" means either:
		 * - Posts created by the user (not imported).
		 * - Imported posts that have been modified by the user since import.
		 *
		 * @param string $post_type The post type to check.
		 * @param array  $args      Optional arguments for WP_Query.
		 *
		 * @since 4.4.33
		 * @return bool True if any qualifying posts exist, false otherwise.
		 */
		private static function has_user_engaged_posts_of_type( $post_type = 'any', $args = array() ) {
			// If post type is not 'any', ensure it exists.
			if ( 'any' !== $post_type && ! post_type_exists( $post_type ) ) {
				return false;
			}

			// Merge default arguments with provided ones.
			$args = array_merge(
				array(
					'post_type'   => $post_type,
					'post_status' => 'publish',
					'fields'      => 'ids',
					'numberposts' => 1,
				),
				$args
			);

			// Exclude post__not_in if already set.
			if ( ! isset( $args['post__not_in'] ) ) {
				// Exclude posts that have been imported and not modified since import.
				$excluded_post_ids = self::get_unmodified_imported_post_ids( $post_type );
				if ( ! empty( $excluded_post_ids ) ) {
					$args['post__not_in'] = $excluded_post_ids;
				}
			}

			$query = new \WP_Query( $args );
			$found = $query->have_posts();
			wp_reset_postdata();

			return $found;
		}

		/**
		 * Checks if Astra Customizer has been updated after template import.
		 *
		 * ACTIVE CONDITION:
		 * - Astra theme is active.
		 * - User modified Astra Customizer settings after template import.
		 *
		 * @since 4.4.47
		 * @return bool True if Astra Customizer was updated post-import, false otherwise.
		 */
		public static function is_astra_customizer_updated() {
			// Verify Astra theme is still active.
			$current_theme = get_stylesheet();
			if ( 0 !== strpos( $current_theme, 'astra' ) ) {
				return false;
			}

			// Check if the setting has been tracked.
			$customizer_updated = (bool) Astra_Sites_Page::get_instance()->get_setting( 'astra_customizer_updated', false );
			if ( ! $customizer_updated ) {
				return false;
			}

			return true;
		}

		/**
		 * Checks if any Spectra block is used on the site.
		 *
		 * ACTIVE CONDITION:
		 * - Imported post/page is updated, published and has at least one Spectra block.
		 * - New post/page is published and has at least one Spectra block.
		 *
		 * @since 4.4.27
		 *
		 * @return bool
		 */
		public static function is_spectra_blocks_used() {
			if ( ! is_plugin_active( 'ultimate-addons-for-gutenberg/ultimate-addons-for-gutenberg.php' ) ) {
				return false;
			}

			// Check for Spectra v2 blocks (wp:uagb/).
			$has_spectra_v2_blocks = self::has_user_engaged_posts_of_type( 'any', array( 's' => '<!-- wp:uagb/' ) );
			if ( $has_spectra_v2_blocks ) {
				return true;
			}

			// Check for Spectra v3 blocks (wp:spectra/).
			return self::has_user_engaged_posts_of_type( 'any', array( 's' => '<!-- wp:spectra/' ) );
		}

		/**
		 * Checks if any UAE Header Footer Layout is published or any UAE widget is used on the site.
		 *
		 * ACTIVE CONDITION:
		 * - Imported header footer post is updated, published.
		 * - New header footer post is published.
		 *
		 * @since 4.4.27
		 *
		 * @return bool
		 */
		public static function is_uae_widgets_used() {
			if ( ! is_plugin_active( 'header-footer-elementor/header-footer-elementor.php' ) ) {
				return false;
			}

			// Check for user-engaged elementor-hf posts.
			if ( self::has_user_engaged_posts_of_type( 'elementor-hf' ) ) {
				return true;
			}

			// Check UAE widget usage data (fallback for older detection method).
			$uae_used_widgets = get_option( 'uae_widgets_usage_data_option', array() );
			if ( is_array( $uae_used_widgets ) && ! empty( $uae_used_widgets ) ) {
				return true;
			}

			return false;
		}

		/**
		 * Checks if any SureForms form is published.
		 *
		 * ACTIVE CONDITION:
		 * - Imported form is updated and published.
		 * - New form is published.
		 *
		 * @since 4.4.27
		 *
		 * @return bool
		 */
		public static function is_sureforms_form_published() {
			return self::has_user_engaged_posts_of_type( 'sureforms_form' );
		}

		/**
		 * Checks if SureMail has at least one connection configured.
		 *
		 * ACTIVE CONDITION: SureMails is connected.
		 *
		 * @since 4.4.27
		 *
		 * @return bool
		 */
		public static function is_suremails_connected() {
			if ( ! is_plugin_active( 'suremails/suremails.php' ) ) {
				return false;
			}

			// Get SureMails connections from options.
			$suremails_connections_option = defined( 'SUREMAILS_CONNECTIONS' ) ? SUREMAILS_CONNECTIONS : 'suremails_connections';
			$suremails_connections        = get_option( $suremails_connections_option, array() );
			if ( is_array( $suremails_connections ) && isset( $suremails_connections['connections'] ) && ! empty( $suremails_connections['connections'] ) ) {
				return true;
			}

			return false;
		}

		/**
		 * Checks if SureCart has any published product.
		 *
		 * ACTIVE CONDITION:
		 * - Imported SureCart product is updated and published.
		 * - New SureCart product is published.
		 *
		 * @since 4.4.27
		 *
		 * @return bool
		 */
		public static function is_surecart_product_published() {
			return self::has_user_engaged_posts_of_type( 'sc_product' );
		}

		/**
		 * Checks if CartFlows has any published funnel.
		 *
		 * ACTIVE CONDITION:
		 * - Imported funnel is updated and published.
		 * - New funnel is published.
		 *
		 * @since 4.4.27
		 *
		 * @return bool
		 */
		public static function is_cartflows_funnel_published() {
			return self::has_user_engaged_posts_of_type( 'cartflows_flow' );
		}

		/**
		 * Checks if LatePoint activities are made by admin or appointment created by users.
		 *
		 * ACTIVE CONDITION: Any activity is made by admin or appointment created by users.
		 *
		 * @since 4.4.27
		 *
		 * @return bool
		 */
		public static function is_latepoint_booking_managed() {
			if ( ! is_plugin_active( 'latepoint/latepoint.php' ) ) {
				return false;
			}

			global $wpdb;
			$latepoint_activities_table = defined( 'LATEPOINT_TABLE_ACTIVITIES' ) ? LATEPOINT_TABLE_ACTIVITIES : $wpdb->prefix . 'latepoint_activities';

			// Table names can't be parameterized in wpdb::prepare(), safe to ignore PHPCS here.
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$count = $wpdb->get_var( "SELECT COUNT(*) FROM {$latepoint_activities_table}" );

			return $count > 0;
		}

		/**
		 * Checks if a Presto Player video is embedded on the site.
		 *
		 * ACTIVE CONDITION: Presto Player block/shortcode used on any post/page
		 *
		 * @since 4.4.27
		 *
		 * @return bool
		 */
		public static function is_presto_player_used() {
			if ( ! is_plugin_active( 'presto-player/presto-player.php' ) ) {
				return false;
			}

			// Exclude 'pp_video_block' post type to avoid false positives.
			$args = array(
				'post_type'     => array_diff(
					get_post_types(
						array( 'public' => true ),
						'names'
					),
					array( 'pp_video_block' )
				),
				'posts__not_in' => array(), // To search in all posts including imported ones.
				's'             => '<!-- /wp:presto-player',
			);

			// Check for Presto Player block in posts.
			$is_presto_player_block_used = self::has_user_engaged_posts_of_type( 'any', $args );
			if ( $is_presto_player_block_used ) {
				return true;
			}

			// Check for Presto Player shortcode in posts.
			$args['s'] = '[presto_player id=';

			return self::has_user_engaged_posts_of_type( 'any', $args );
		}

		/**
		 * Checks if SureRank optimization is done on any post or 3+ site level SEO settings.
		 *
		 * ACTIVE CONDITION:
		 * - Optimization of any pages/posts (update SureRank meta, etc. of any posts/pages)
		 * - Optimize 3+ things in "site level" SEO settings (change configuration of 3 things from default values)
		 *
		 * @since 4.4.39
		 *
		 * @return bool True if optimization is done on any post or 3+ site level SEO settings, false otherwise.
		 */
		public static function is_surerank_optimization_done() {
			if ( ! defined( 'SURERANK_VERSION' ) || ! is_plugin_active( 'surerank/surerank.php' ) ) {
				return false;
			}

			// Check if 3 or more site-level settings are changed from default.
			if ( is_callable( '\SureRank\Inc\Functions\Defaults::get_instance' ) ) {
				$key               = defined( 'SURERANK_SETTINGS' ) ? SURERANK_SETTINGS : 'surerank_settings';
				$surerank_defaults = \SureRank\Inc\Functions\Defaults::get_instance()->get_global_defaults();
				$surerank_settings = get_option( $key, array() );

				if ( is_array( $surerank_settings ) && is_array( $surerank_defaults ) ) {
					$changed_settings = self::shallow_two_level_diff( $surerank_settings, $surerank_defaults );
					if ( count( $changed_settings ) >= 3 ) {
						return true;
					}
				}
			}

			global $wpdb;
			// Use direct SQL with IN() for multiple meta keys instead of WP_Query to avoid heavy JOINs and improve performance.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$posts = $wpdb->get_col(
				$wpdb->prepare(
					"
						SELECT DISTINCT pm.post_id
						FROM {$wpdb->postmeta} pm
						INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
						WHERE pm.meta_key IN (%s, %s, %s, %s, %s, %s)
						AND p.post_status = 'publish'
						LIMIT 1
					",
					'surerank_settings_general',
					'surerank_settings_schemas',
					'surerank_settings_social',
					'surerank_settings_post_no_index',
					'surerank_settings_post_no_follow',
					'surerank_settings_post_no_archive'
				)
			);

			return count( $posts ) >= 1;
		}

		/**
		 * Checks if ModernCart is enabled.
		 *
		 * ACTIVE CONDITION: Modern Cart setting is enabled.
		 *
		 * @since 4.4.41
		 *
		 * @return bool True if ModernCart is enabled, false otherwise.
		 */
		public static function is_modern_cart_enabled() {
			if ( ! defined( 'MODERNCART_VER' ) || ! is_plugin_active( 'modern-cart/modern-cart.php' ) ) {
				return false;
			}

			$option_name = defined( 'MODERNCART_MAIN_SETTINGS' ) ? MODERNCART_MAIN_SETTINGS : 'moderncart_settings';
			$settings    = get_option( $option_name, array() );

			if ( ! is_array( $settings ) || ! isset( $settings['enable_moderncart'] ) ) {
				return true; // Default is set to `all` which indicates that Modern Cart is enabled everywhere.
			}

			// Check if Modern Cart is enabled on WooCommerce pages or everywhere.
			return 'wc_pages' === $settings['enable_moderncart'] || 'all' === $settings['enable_moderncart'];
		}

		/**
		 * Add required plugins analytics data.
		 *
		 * @param array $stats Stats array.
		 *
		 * @since 4.4.27
		 * @return void
		 */
		private static function add_required_plugins_analytics( &$stats ) {
			$required_plugins = Astra_Sites_Page::get_instance()->get_setting( 'required_plugins', array() );
			if ( ! is_array( $required_plugins ) ) {
				return;
			}

			$stats['plugins_data'] = ! empty( $required_plugins ) ? wp_json_encode( $required_plugins ) : '';
		}

		/**
		 * Add plugin active analytics data.
		 *
		 * @param array $stats Stats array.
		 *
		 * @since 4.4.27
		 * @return void
		 */
		private static function add_plugin_active_analytics( &$stats ) {
			$stats = array_merge(
				$stats,
				array(
					'astra_customizer_updated'    => self::is_astra_customizer_updated(),
					'spectra_blocks_used'         => self::is_spectra_blocks_used(),
					'uae_widgets_used'            => self::is_uae_widgets_used(),
					'sureforms_form_published'    => self::is_sureforms_form_published(),
					'suremails_connected'         => self::is_suremails_connected(),
					'surecart_product_published'  => self::is_surecart_product_published(),
					'cartflows_funnel_published'  => self::is_cartflows_funnel_published(),
					'latepoint_booking_managed'   => self::is_latepoint_booking_managed(),
					'presto_player_used'          => self::is_presto_player_used(),
					'surerank_optimization_done'  => self::is_surerank_optimization_done(),
					'modern_cart_enabled'         => self::is_modern_cart_enabled(),
				)
			);
		}

		/**
		 * Add finish setup analytics data.
		 *
		 * @param array $stats Stats array.
		 *
		 * @since 4.4.28
		 * @return void
		 */
		private static function add_finish_setup_analytics( &$stats ) {
			// Get setup wizard showing option name.
			$option_name = class_exists( '\GS\Classes\GS_Helper' )
				? \GS\Classes\GS_Helper::get_setup_wizard_showing_option_name()
				: 'getting_started_is_setup_wizard_showing';
			$is_setup_wizard_showing = get_option( $option_name, false );
			$action_items_status     = get_option( 'getting_started_action_items', array() );
			$fs_banner_clicked       = (string) Astra_Sites_Page::get_instance()->get_setting( 'fs_banner_clicked', '' );
 
			$courses_status          = array();
			$no_of_completed_courses = 0;

			// Get the courses status from action items.
			if ( is_array( $action_items_status ) ) {
				foreach ( $action_items_status as $key => $action_item ) {
					$status                 = isset( $action_item['status'] ) ? $action_item['status'] : false;
					$courses_status[ $key ] = $status ? 'done' : 'not_done';
					if ( $status ) {
						$no_of_completed_courses++;
					}
				}
			}

			// Total number of courses.
			$total_courses = count( $courses_status );

			// Boolean and numeric values.
			$stats['boolean_values']['is_finish_setup_showing'] = $is_setup_wizard_showing;
			$stats['numeric_values']['total_courses']           = $total_courses;
			$stats['numeric_values']['no_of_completed_courses'] = $no_of_completed_courses;
			$stats['boolean_values']['course_completed']        = 0 !== $total_courses && $no_of_completed_courses >= $total_courses;

			// Dashboard banner clicked.
			if ( $fs_banner_clicked ) {
				$stats['boolean_values']['finish_setup_banner_clicked'] = 'yes' === $fs_banner_clicked;
			}

			// Plain Json data.
			$stats['courses_status'] = ! empty( $courses_status ) ? wp_json_encode( $courses_status ) : '';
		}

		/**
		 * Add KPI tracking data for import events.
		 *
		 * Groups stored timestamps by date, counts per metric per day,
		 * skips today's (incomplete) data, and cleans up sent timestamps.
		 *
		 * @param array $stats Reference to the astra sites stats data.
		 * @since 4.4.51
		 * @return void
		 */
		public static function add_kpi_tracking_data( &$stats ) {
			$timestamps = Astra_Sites_Page::get_instance()->get_setting( '_import_timestamps', array() );
			if ( empty( $timestamps ) || ! is_array( $timestamps ) ) {
				return;
			}

			$kpi_data = array();

			// Mapping: option key => KPI metric name.
			$metric_map = array(
				'templates'      => 'templates_imported',
				'block_patterns' => 'block_patterns_imported',
				'kits'           => 'kits_imported',
				'content_edited' => 'post_content_edited',
			);

			$remaining_timestamps = array();

			foreach ( $metric_map as $bucket => $metric_name ) {
				if ( empty( $timestamps[ $bucket ] ) || ! is_array( $timestamps[ $bucket ] ) ) {
					continue;
				}

				$result                          = self::process_kpi_bucket( $timestamps[ $bucket ], $metric_name, $kpi_data );
				$kpi_data                        = $result['kpi_data'];
				$remaining_timestamps[ $bucket ] = $result['remaining'];
			}

			// Only add to stats if we have data to report.
			if ( ! empty( $kpi_data ) ) {
				$stats['kpi_records'] = $kpi_data;
			}

			// Cleanup: keep only today's timestamps.
			$remaining_timestamps = array_filter( $remaining_timestamps );
			Astra_Sites_Page::get_instance()->update_settings( array( '_import_timestamps' => $remaining_timestamps ) );
		}

		/**
		 * Process a single bucket of timestamps for KPI reporting.
		 *
		 * @param array  $bucket_timestamps Array of Unix timestamps.
		 * @param string $metric_name       The KPI metric name.
		 * @param array  $kpi_data          Existing KPI data to merge into.
		 *
		 * @since 4.4.51
		 * @return array { kpi_data: array, remaining: array }
		 */
		private static function process_kpi_bucket( $bucket_timestamps, $metric_name, $kpi_data ) {
			$today     = gmdate( 'Y-m-d' );
			$remaining = array();

			foreach ( $bucket_timestamps as $timestamp ) {
				if ( ! is_numeric( $timestamp ) ) {
					continue;
				}

				$date = gmdate( 'Y-m-d', (int) $timestamp );

				// Keep today's timestamps — incomplete day, skip from reporting.
				if ( $date === $today ) {
					$remaining[] = $timestamp;
					continue;
				}

				if ( ! isset( $kpi_data[ $date ] ) ) {
					$kpi_data[ $date ] = array( 'numeric_values' => array() );
				}

				if ( ! isset( $kpi_data[ $date ]['numeric_values'][ $metric_name ] ) ) {
					$kpi_data[ $date ]['numeric_values'][ $metric_name ] = 0;
				}

				$kpi_data[ $date ]['numeric_values'][ $metric_name ]++;
			}

			return array(
				'kpi_data'  => $kpi_data,
				'remaining' => $remaining,
			);
		}

		/**
		 * Add astra sites analytics data.
		 *
		 * @param array $stats stats array.
		 *
		 * @since 4.4.27
		 * @return array
		 */
		public function add_astra_sites_analytics_data( $stats ) {
			// Load the plugin.php file to use is_plugin_active function.
			if ( ! function_exists( 'is_plugin_active' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			$import_complete = get_option( 'astra_sites_import_complete', 'no' ) === 'yes';

			$stats['plugin_data']['astra_sites'] = array(
				'version'        => defined( 'ASTRA_PRO_SITES_NAME' ) ? 'premium' : 'free',
				'site_language'  => get_locale(),
				'plugin_version' => defined( 'ASTRA_SITES_VER' ) ? ASTRA_SITES_VER : 'unknown',
				'page_builder'   => Astra_Sites_Page::get_instance()->get_setting( 'page_builder' ),
				'boolean_values' => array(
					'import_complete'                => $import_complete,
					'woopayments_included'           => Astra_Sites_Page::get_instance()->get_setting( 'woopayments_included' ),
					'was_woopayments_referred'       => Astra_Sites_Page::get_instance()->get_setting( 'woopayments_ref' ),
					'woopayments_banner_clicked'     => Astra_Sites_Page::get_instance()->get_setting( 'woopayments_banner_clicked' ),
					'woopayments_onboarding_clicked' => Astra_Sites_Page::get_instance()->get_setting( 'woopayments_onboarding_clicked' ),
					'woopayments_configured'         => self::is_woo_payments_configured(),
					'has_woopayments_transaction'    => self::has_woopayments_successful_transaction(),
				),
				'numeric_values' => array(
					'woopayments_banner_dismissed_count' => Astra_Sites_Page::get_instance()->get_setting( 'woopayments_banner_dismissed_count' ),
				),
				'steps_visited' => Astra_Sites_Page::get_instance()->get_setting( 'steps_visited' ),
			);

			if ( $import_complete ) {
				self::add_required_plugins_analytics( $stats['plugin_data']['astra_sites'] );
				self::add_plugin_active_analytics( $stats['plugin_data']['astra_sites']['boolean_values'] );
				self::add_finish_setup_analytics( $stats['plugin_data']['astra_sites'] );
			}

			// Add KPI tracking data.
			self::add_kpi_tracking_data( $stats['plugin_data']['astra_sites'] );

			// Flush pending events into payload (only if any exist).
			$pending_events = Astra_Sites_Analytics_Events::flush_pending();
			if ( ! empty( $pending_events ) ) {
				$stats['plugin_data']['astra_sites']['events_record'] = $pending_events;
			}

			return $stats;
		}

		/**
		 * Compare top-level and one-level nested settings with defaults.
		 *
		 * @param array $settings Current settings.
		 * @param array $defaults Default settings.
		 *
		 * @since 4.4.39
		 * @return array Changed settings (top-level + one-level deep).
		 */
		public static function shallow_two_level_diff( array $settings, array $defaults ) {
			$difference = array();

			foreach ( $settings as $key => $value ) {
				// Key missing in defaults = changed.
				if ( ! array_key_exists( $key, $defaults ) ) {
					$difference[ $key ] = $value;
					continue;
				}

				// If value is an array, only check one level deep.
				if ( is_array( $value ) && is_array( $defaults[ $key ] ) ) {
					$nested_diff = array();
					foreach ( $value as $sub_key => $sub_value ) {
						if ( ! array_key_exists( $sub_key, $defaults[ $key ] ) || $sub_value !== $defaults[ $key ][ $sub_key ] ) {
							$nested_diff[ $sub_key ] = $sub_value;
						}
					}
					if ( ! empty( $nested_diff ) ) {
						$difference[ $key ] = $nested_diff;
					}
				} elseif ( $value !== $defaults[ $key ] ) {
					// Compare scalar values directly.
					$difference[ $key ] = $value;
				}
			}

			return $difference;
		}
	}

	/**
	 * Kicking this off by calling 'get_instance()' method
	 */
	Astra_Sites_Analytics::get_instance();
}
