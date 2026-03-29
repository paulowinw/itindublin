<?php
/**
 * Astra Sites Compatibility for 'WooCommerce'
 *
 * @see  https://wordpress.org/plugins/woocommerce/
 *
 * @package Astra Sites
 * @since 1.1.4
 */

if ( ! class_exists( 'Astra_Sites_Compatibility_WooCommerce' ) ) :

	/**
	 * WooCommerce Compatibility
	 *
	 * @since 1.1.4
	 */
	class Astra_Sites_Compatibility_WooCommerce {

		/**
		 * Instance
		 *
		 * @access private
		 * @var object Class object.
		 * @since 1.1.4
		 */
		private static $instance;

		/**
		 * Initiator
		 *
		 * @since 1.1.4
		 * @return object initialized object of class.
		 */
		public static function instance() {
			if ( ! isset( self::$instance ) ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Constructor
		 *
		 * @since 1.1.4
		 */
		public function __construct() {
			add_action( 'astra_sites_after_plugin_activation', array( $this, 'install_wc' ), 10, 2 );
			add_action( 'astra_sites_before_import_prepare_xml', array( $this, 'import_attributes' ), 10 );
			add_filter( 'cfvsw_is_required_screen_for_swatch_types', array( $this, 'screen_required_check' ), 10, 1 );

			// WooCommerce product attributes registration.
			if ( class_exists( 'WooCommerce' ) ) {
				add_filter( 'wxr_importer.pre_process.term', array( $this, 'woocommerce_product_attributes_registration' ), 10, 1 );
				add_action( 'astra_sites_import_complete', array( $this, 'update_wc_lookup_table' ) );
				add_action( 'wxr_importer.pre_process.post_meta', array( $this, 'map_new_term_id' ), 10, 2 );
			}
			add_filter( 'astra_sites_pre_process_post_disable_content', '__return_false' );
			add_filter( 'astra_sites_pre_process_post_empty_excerpt', '__return_false' );
		}

		/**
		 * Map new term id.
		 * 
		 * @param array $meta Meta data.
		 * @param int   $post_id Post ID.
		 * @return array Modified meta data.
		 * @since 4.4.19
		 */
		public function map_new_term_id( $meta, $post_id ) {
			// Early return if not the target meta key.
			if ( ! isset( $meta['key'] ) || 'cfvsw_product_attr_pa_color' !== $meta['key'] ) {
				return $meta;
			}

			// Early return if value is empty or not serialized.
			if ( empty( $meta['value'] ) || ! is_serialized( $meta['value'] ) ) {
				return $meta;
			}

			// Safely unserialize data.
			$unserialized_data = astra_sites_safe_unserialize( $meta['value'] );
			if ( false === $unserialized_data || ! is_array( $unserialized_data ) ) {
				return $meta;
			}

			// Get terms and validate.
			$terms = wp_get_post_terms( $post_id, 'pa_color' );
			if ( is_wp_error( $terms ) || empty( $terms ) ) {
				return $meta;
			}

			// Process only if we have valid color data.
			if ( empty( $unserialized_data['type'] ) || 'color' !== $unserialized_data['type'] ) {
				return $meta;
			}

			$modified = false;
			foreach ( $terms as $term ) {
				if ( ! is_object( $term ) || ! isset( $term->term_id ) ) {
					continue;
				}

				$color_id = get_term_meta( $term->term_id, 'pa_color_id', true );
				if ( '' === $color_id || ! isset( $unserialized_data[ $color_id ] ) ) {
					continue;
				}

				$unserialized_data[ $term->term_id ] = $unserialized_data[ $color_id ];
				$modified = true;
			}

			// Only serialize and update if changes were made.
			if ( $modified ) {
				$meta['value'] = maybe_serialize( $unserialized_data );
			}

			return $meta;
		}

		/**
		 * Returns true/false that need screen check required to retieve the swatch types
		 * 
		 * @since 4.4.19
		 * @return bool
		 */
		public function screen_required_check() {
			if ( ! is_plugin_active( 'variation-swatches-woo/variation-swatches-woo.php' ) ) {
				return false;
			}

			if ( ! function_exists( 'astra_sites_has_import_started' ) || ! astra_sites_has_import_started() ) {
				return false;
			}
			return true;
		}

		/**
		 * Add product attributes.
		 *
		 * @since 1.1.4
		 *
		 * @param  string $demo_data        Import data.
		 * @param  array  $demo_api_uri     Demo site URL.
		 * @return void
		 */
		public function add_attributes( $demo_data = array(), $demo_api_uri = '' ) {
			$attributes = ( isset( $demo_data['astra-site-options-data']['woocommerce_product_attributes'] ) ) ? $demo_data['astra-site-options-data']['woocommerce_product_attributes'] : array();

			if ( ! empty( $attributes ) && function_exists( 'wc_create_attribute' ) ) {
				foreach ( $attributes as $key => $attribute ) {
					$args = array(
						'name'         => $attribute['attribute_label'],
						'slug'         => $attribute['attribute_name'],
						'type'         => $attribute['attribute_type'],
						'order_by'     => $attribute['attribute_orderby'],
						'has_archives' => $attribute['attribute_public'],
					);

					$id = wc_create_attribute( $args );
				}
			}
		}

		/**
		 * Create default WooCommerce tables
		 *
		 * @param string $plugin_init Plugin file which is activated.
		 * @return void
		 */
		public function install_wc( $plugin_init ) {
			if ( 'woocommerce/woocommerce.php' !== $plugin_init ) {
				return;
			}

			// Create WooCommerce database tables.
			if ( is_callable( '\Automattic\WooCommerce\Admin\Install::create_tables' ) ) {
				\Automattic\WooCommerce\Admin\Install::create_tables();
				\Automattic\WooCommerce\Admin\Install::create_events();
			}

			if ( is_callable( 'WC_Install::install' ) ) {
				WC_Install::install();
			}
		}

		/**
		 * Responsible for importing the product attribute taxonomies
		 * 
		 * @since 4.4.19
		 * @return void
		 */
		public function import_attributes() {
			if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) && ! is_plugin_active( 'variation-swatches-woo/variation-swatches-woo.php' ) ) {
				return;
			}
			// Get site options data containing product attributes.
			$options_data = astra_get_site_data( 'astra-site-options-data' );

			// Get formatted attribute arguments.
			$attribute_args = $this->get_attribute_args( $options_data );
			if ( ! empty( $attribute_args ) && function_exists( 'wc_create_attribute' ) ) {
				foreach ( $attribute_args as $attribute_arg ) {
					// Create WooCommerce attribute.
					$id = wc_create_attribute( $attribute_arg );
					// Store attribute ID if creation was successful.
					if ( $id && ! is_wp_error( $id ) ) {
						update_option( $attribute_arg['slug'] . '_attribute_id', $id );
					}
				}
			}
		}

		/**
		 * Creates array of attributes with proper attribute values
		 * 
		 * @since 4.4.19
		 * @param array $options_data Options data in import json.
		 * @return array
		 */
		public function get_attribute_args( $options_data ) {
			$attributes     = ( isset( $options_data['woocommerce_product_attributes'] ) ) ? $options_data['woocommerce_product_attributes'] : array();
			$attribute_args = array();
			foreach ( $attributes as $key => $attribute ) {
				if ( taxonomy_exists( 'pa_' . $attribute['attribute_name'] ) ) {
					continue;
				}
				$attribute_args[] = array(
					'name'         => $attribute['attribute_label'],
					'slug'         => $attribute['attribute_name'],
					'type'         => $attribute['attribute_type'],
					'order_by'     => $attribute['attribute_orderby'],
					'has_archives' => $attribute['attribute_public'],
				);
			}
			return apply_filters( 'astra_sites_woocommerce_attribute_args', $attribute_args );
		}

		/**
		 * Hook into the pre-process term filter of the content import and register the
		 * custom WooCommerce product attributes, so that the terms can then be imported normally.
		 *
		 * This should probably be removed once the WP importer 2.0 support is added in WooCommerce.
		 *
		 * Fixes: [WARNING] Failed to import pa_size L warnings in content import.
		 * Code from: woocommerce/includes/admin/class-wc-admin-importers.php (ver 2.6.9).
		 *
		 * Github issue: https://github.com/proteusthemes/one-click-demo-import/issues/71
		 *
		 * @since 3.0.0
		 * @param  array $data The term data to import.
		 * @return array       The unchanged term data.
		 */
		public function woocommerce_product_attributes_registration( $data ) {
			global $wpdb;

			if ( strstr( $data['taxonomy'], 'pa_' ) ) {
				if ( ! taxonomy_exists( $data['taxonomy'] ) ) {
					$attribute_name = wc_sanitize_taxonomy_name( str_replace( 'pa_', '', $data['taxonomy'] ) );

					// Create the taxonomy.
					if ( ! in_array( $attribute_name, wc_get_attribute_taxonomies(), true ) ) {
						$attribute = array(
							'attribute_label'   => $attribute_name,
							'attribute_name'    => $attribute_name,
							'attribute_type'    => 'select',
							'attribute_orderby' => 'menu_order',
							'attribute_public'  => 0,
						);
						$wpdb->insert( $wpdb->prefix . 'woocommerce_attribute_taxonomies', $attribute ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- WP Query would be expensive here, we are adding taxonomy attributes for WooCommerce.
						delete_transient( 'wc_attribute_taxonomies' );
					}

					// Register the taxonomy now so that the import works!
					register_taxonomy(
						$data['taxonomy'],
						apply_filters( 'woocommerce_taxonomy_objects_' . $data['taxonomy'], array( 'product' ) ),
						apply_filters(
							'woocommerce_taxonomy_args_' . $data['taxonomy'], array(
								'hierarchical' => true,
								'show_ui'      => false,
								'query_var'    => true,
								'rewrite'      => false,
							)
						)
					);
				}
			}

			return $data;
		}

		/**
		 * Update WooCommerce Lookup Table.
		 *
		 * @since 3.0.0
		 *
		 * @return void
		 */
		public function update_wc_lookup_table() {
			if ( function_exists( 'wc_update_product_lookup_tables' ) ) {
				if ( ! wc_update_product_lookup_tables_is_running() ) {
					wc_update_product_lookup_tables();
				}
			}
		}
	}

	/**
	 * Kicking this off by calling 'instance()' method
	 */
	Astra_Sites_Compatibility_WooCommerce::instance();

endif;
