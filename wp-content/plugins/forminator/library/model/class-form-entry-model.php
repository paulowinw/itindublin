<?php
/**
 * The Forminator_Form_Entry_Model class.
 *
 * @package Forminator
 */

/**
 * Form Entry model
 * Base model for all form entries
 *
 * @since 1.0
 */
class Forminator_Form_Entry_Model {

	/**
	 * Cache group for entry model
	 */
	const FORM_ENTRY_CACHE_GROUP = 'Forminator_Form_Entry_Model';

	/**
	 * Cache group for total entries
	 */
	const FORM_COUNT_CACHE_GROUP = 'forminator_total_entries';

	/**
	 * Entry id
	 *
	 * @var int
	 */
	public $entry_id = 0;

	/**
	 * Updating entry
	 *
	 * @var bool
	 */
	public $updating_entry = false;

	/**
	 * Entry type
	 *
	 * @var string
	 */
	public $entry_type;

	/**
	 * Form id
	 *
	 * @var int
	 */
	public $form_id;

	/**
	 * Draft id
	 *
	 * @var string
	 */
	public $draft_id;

	/**
	 * Spam flag
	 *
	 * @var bool
	 */
	public $is_spam = false;

	/**
	 * Entry status
	 *
	 * @var string 'active'|'spam'|'draft'|'abandoned'
	 */
	public $status = 'active';

	/**
	 * Compute status using explicit source status if present, otherwise from this model's flags.
	 *
	 * @param object $source Source object that may contain `status`.
	 *
	 * @return string
	 */
	private function get_entry_status( $source ) {
		return ! empty( $source->status ) ? $source->status : ( $this->is_spam ? 'spam' : ( ! empty( $this->draft_id ) ? 'draft' : 'active' ) );
	}


	/**
	 * Date created in sql format 0000-00-00 00:00:00
	 *
	 * @var string
	 */
	public $date_created_sql;

	/**
	 * Date created in sql format D M Y
	 *
	 * @var string
	 */
	public $date_created;

	/**
	 * Time created in sql format M D Y @ g:i A
	 *
	 * @var string
	 */
	public $time_created;

	/**
	 * Meta data
	 *
	 * @var array
	 */
	public $meta_data = array();

	/**
	 * The table name
	 *
	 * @var string
	 */
	protected $table_name;

	/**
	 * The table meta name
	 *
	 * @var string
	 */
	protected $table_meta_name;

	/**
	 * Hold information about connected addons
	 *
	 * @since 1.1
	 * @var array
	 */
	private static $connected_addons = array();


	/**
	 * Initialize the Model
	 *
	 * @param int $entry_id Entry Id.
	 *
	 * @since 1.0
	 * @since 1.1 Add instantiate connected addons
	 * @since 1.2 Limit initiate addon only on custom-forms by default
	 */
	public function __construct( $entry_id = 0 ) {
		$this->table_name      = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY );
		$this->table_meta_name = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY_META );

		/*
		 * @since 1.17.0
		 * draft_id could be used in place of the entry_id in the argument
		 * but we still need to get the entry_id which is used as the key for object cache
		*/
		if ( ! is_numeric( $entry_id ) && is_string( $entry_id ) && ctype_alnum( $entry_id ) ) {
			$entry_id = $this->get_entry_id_by_draft_id( $entry_id );
		}

		if ( is_numeric( $entry_id ) && $entry_id > 0 ) {
			$this->get( $entry_id );
			// get connected addons.
			if ( ! empty( $this->form_id ) ) {
				$entry_types = array(
					'custom-forms',
				);
				/**
				 * Filter Entry Types that can be connected with addons
				 *
				 * @param array $entry_types
				 *
				 * @since 1.2
				 */
				$entry_types = apply_filters( 'forminator_addon_entry_types', $entry_types );
				if ( ! empty( $this->entry_type ) && in_array( $this->entry_type, $entry_types, true ) ) {
					self::get_connected_addons( $this->form_id );
				}
			}
		}
	}

	/**
	 * Load entry by id
	 * After load set entry to cache
	 *
	 * @param int $entry_id - the entry id.
	 *
	 * @return bool|mixed
	 * @since 1.0
	 */
	public function get( $entry_id ) {
		global $wpdb;

		$cache_key          = self::FORM_ENTRY_CACHE_GROUP;
		$entry_object_cache = wp_cache_get( $entry_id, $cache_key );

		if ( $entry_object_cache ) {
			$this->entry_id         = $entry_object_cache->entry_id;
			$this->entry_type       = $entry_object_cache->entry_type;
			$this->form_id          = $entry_object_cache->form_id;
			$this->is_spam          = $entry_object_cache->is_spam;
			$this->date_created_sql = $entry_object_cache->date_created_sql;
			$this->date_created     = $entry_object_cache->date_created;
			$this->time_created     = $entry_object_cache->time_created;
			$this->meta_data        = $entry_object_cache->meta_data;
			$this->draft_id         = $entry_object_cache->draft_id;
			$this->status           = $this->get_entry_status( $entry_object_cache );

			return $entry_object_cache;
		} else {
			$table_name = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY );
			$sql        = "SELECT `entry_type`, `form_id`, `is_spam`, `date_created`, `draft_id`, `status` FROM {$table_name} WHERE `entry_id` = %d";
			$entry      = $wpdb->get_row( $wpdb->prepare( $sql, $entry_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery
			if ( $entry ) {
				$this->entry_id         = $entry_id;
				$this->entry_type       = $entry->entry_type;
				$this->form_id          = $entry->form_id;
				$this->is_spam          = $entry->is_spam;
				$this->date_created_sql = $entry->date_created;
				$this->date_created     = date_i18n( 'j M Y', strtotime( $entry->date_created ) );
				$this->time_created     = date_i18n( 'M j, Y @ g:i A', strtotime( $entry->date_created ) );
				$this->draft_id         = $entry->draft_id;
				$this->status           = $this->get_entry_status( $entry );
				$this->load_meta();
				wp_cache_set( $entry_id, $this, $cache_key );
			}
		}
	}

	/**
	 * Set fields
	 *
	 * @param array  $meta_array Array of data to be saved.
	 * @param string $entry_date Entry date.
	 *
	 * @type key - string the meta key
	 * @type value - string the meta value
	 * }
	 *
	 * @return bool - true or false
	 * @since 1.5 set meta_data values even entry_id is null for object reference in the future usage
	 *        entry_id has null value is the outcome of failed to save or prevent_store is enabled
	 *
	 * @since 1.0
	 */
	public function set_fields( $meta_array, $entry_date = '' ) {
		global $wpdb;

		if ( $meta_array && ! is_array( $meta_array ) && ! empty( $meta_array ) ) {
			return false;
		}

		// probably prevent_store enabled.
		$prevent_store = ! $this->entry_id;

		if ( ! $prevent_store ) {
			// clear cache first.
			wp_cache_delete( $this->entry_id, self::FORM_ENTRY_CACHE_GROUP );
			wp_cache_delete( 'poll_entries_' . $this->form_id, self::FORM_ENTRY_CACHE_GROUP );
		}
		if ( $this->updating_entry ) {
			// Delete all meta data for abandoned entry.
			$wpdb->delete( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$this->table_meta_name,
				array(
					'entry_id' => $this->entry_id,
				)
			);
		}
		foreach ( $meta_array as $meta ) {
			if ( ! isset( $meta['name'] ) || ! isset( $meta['value'] ) ) {
				continue;
			}
			$key   = wp_unslash( $meta['name'] );
			$value = wp_unslash( $meta['value'] );

			if ( ! $prevent_store ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$meta_id = $wpdb->insert(
					esc_sql( $this->table_meta_name ),
					array(
						'entry_id'     => $this->entry_id,
						'meta_key'     => $key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
						'meta_value'   => maybe_serialize( $value ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
						'date_created' => ! empty( $entry_date ) ? $entry_date : date_i18n( 'Y-m-d H:i:s' ),
					)
				);
			} else {
				$meta_id = $key;
			}

			/**
			 * Set Meta data for later usage
			 *
			 * @since 1.0.3
			 */
			if ( $meta_id ) {
				$this->meta_data[ $key ] = array(
					'id'    => $meta_id,
					'value' => $value,
				);
			}
		}
		if ( 'abandoned' === $this->status ) {
			$form_uid = filter_input( INPUT_POST, 'form_uid' );
			if ( $form_uid ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$meta_id = $wpdb->insert(
					esc_sql( $this->table_meta_name ),
					array(
						'entry_id'     => $this->entry_id,
						'meta_key'     => 'form_uid', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
						'meta_value'   => $form_uid, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
						'date_created' => ! empty( $entry_date ) ? $entry_date : date_i18n( 'Y-m-d H:i:s' ),
					)
				);
			}
		}

		return ! $prevent_store;
	}

	/**
	 * Load all meta data for entry
	 *
	 * @param object|bool $db - the WP_Db object.
	 *
	 * @since 1.0
	 */
	public function load_meta( $db = false ) {
		if ( ! $db ) {
			global $wpdb;
			$db = $wpdb;
		}
		$this->meta_data = array();
		$table_meta_name = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY_META );
		$sql             = "SELECT `meta_id`, `meta_key`, `meta_value` FROM {$table_meta_name} WHERE `entry_id` = %d";
		$results         = $db->get_results( $db->prepare( $sql, $this->entry_id ) );
		foreach ( $results as $result ) {
			$this->meta_data[ $result->meta_key ] = array(
				'id'    => $result->meta_id,
				'value' => is_array( $result->meta_value ) ? array_map( 'maybe_unserialize', $result->meta_value ) : maybe_unserialize( $result->meta_value ),
			);
		}

		// Allow new field to get data of old field for old submissions.
		if ( in_array( 'stripe-1', array_keys( $this->meta_data ), true ) && ! isset( $this->meta_data['stripe-ocs-1'] ) ) {
			$this->meta_data['stripe-ocs-1'] = $this->meta_data['stripe-1'];
		}
	}

	/**
	 * Get Meta
	 *
	 * @param string $meta_key - the meta key.
	 * @param mixed  $default_value - the default value.
	 *
	 * @return mixed
	 * @since 1.0
	 */
	public function get_meta( $meta_key, $default_value = false ) {
		if ( ! empty( $this->meta_data ) && isset( $this->meta_data[ $meta_key ] ) ) {
			return $this->meta_data[ $meta_key ]['value'];
		}

		return $this->get_grouped_meta( $meta_key, $default_value );
	}

	/**
	 * Get Grouped Meta
	 * Sometimes the meta prefix is same
	 *
	 * @param string $meta_key - the meta key.
	 * @param mixed  $default_value - the default value.
	 *
	 * @return mixed
	 * @since 1.0
	 */
	public function get_grouped_meta( $meta_key, $default_value = false ) {
		if ( ! empty( $this->meta_data ) ) {
			$response     = '';
			$field_suffix = self::field_suffix();
			foreach ( $field_suffix as $suffix ) {
				if ( isset( $this->meta_data[ $meta_key . '-' . $suffix ] ) ) {
					$response .= $this->meta_data[ $meta_key . '-' . $suffix ]['value'] . ' ' . $suffix . ' , ';
				}
			}
			if ( ! empty( $response ) ) {
				return substr( trim( $response ), 0, - 1 );
			}
		}

		return $default_value;
	}

	/**
	 * Save entry
	 *
	 * @param string|null $data_created Optional custom date created.
	 * @param int|null    $entry_id Entry Id.
	 * @param int|null    $previous_draft Draft Id.
	 *
	 * @return bool
	 * @since 1.6.1 add $data_created arg
	 *
	 * @since 1.0
	 */
	public function save( $data_created = null, $entry_id = null, $previous_draft = null ) {
		global $wpdb;
		$this->delete_previous_draft( $previous_draft );

		// Check if abandonment feature is disabled and prevent saving abandoned entries.
		if ( 'abandoned' === $this->status && forminator_form_abandonment_disabled() ) {
			return false;
		}

		if ( ! empty( $entry_id ) ) {
			$this->entry_id = $entry_id;

			return true;
		}

		if ( empty( $data_created ) ) {
			$data_created = date_i18n( 'Y-m-d H:i:s' );
		}
		// Derive status if not explicitly set.
		$status = $this->get_entry_status( $this );

		$saved_entry_id = $this->get_saved_entry_id();

		if ( ! $saved_entry_id ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$result = $wpdb->insert(
				$this->table_name,
				array(
					'entry_type'   => $this->entry_type,
					'form_id'      => $this->form_id,
					'is_spam'      => $this->is_spam,
					'date_created' => $data_created,
					'draft_id'     => $this->draft_id,
					'status'       => $status,
				)
			);

			$this->entry_id = (int) $wpdb->insert_id;
		} else {
			$this->entry_id = $saved_entry_id;
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$result = $wpdb->update(
				$this->table_name,
				array(
					'is_spam'      => $this->is_spam,
					'date_created' => $data_created,
					'draft_id'     => $this->draft_id,
					'status'       => $status,
				),
				array( 'entry_id' => $saved_entry_id )
			);
		}

		if ( ! $result ) {
			return false;
		}

		self::delete_form_entry_cache( $this->form_id );
		wp_cache_delete( 'all_form_types', self::FORM_COUNT_CACHE_GROUP );
		wp_cache_delete( $this->entry_type . '_form_type', self::FORM_COUNT_CACHE_GROUP );

		return true;
	}

	/**
	 * Get saved entry id
	 *
	 * @return int
	 */
	private function get_saved_entry_id() {
		$form_uid = filter_input( INPUT_POST, 'form_uid' );
		if ( ! $form_uid ) {
			return 0;
		}
		global $wpdb;
		$sql      = "SELECT m.entry_id FROM {$this->table_meta_name} m JOIN {$this->table_name} e ON (m.entry_id = e.entry_id)" .
			" WHERE e.form_id = %d AND e.status = 'abandoned' AND m.meta_key = 'form_uid' AND m.meta_value = %s";
		$entry_id = $wpdb->get_var( $wpdb->prepare( $sql, $this->form_id, $form_uid ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared

		$this->updating_entry = ! empty( $entry_id );

		return (int) $entry_id;
	}

	/**
	 * Delete entry with meta
	 *
	 * @since 1.0
	 */
	public function delete() {
		self::delete_by_entry( $this->entry_id );
	}

	/**
	 * Field suffix
	 * Some fields are grouped and have the same suffix
	 *
	 * @return array
	 * @since 1.0
	 */
	public static function field_suffix() {
		return apply_filters(
			'forminator_field_suffix',
			array(
				'hours',
				'minutes',
				'ampm',
				'street_address',
				'address_line',
				'city',
				'state',
				'zip',
				'country',
				'year',
				'day',
				'month',
				'prefix',
				'first-name',
				'middle-name',
				'last-name',
				'post-title',
				'post-content',
				'post-excerpt',
				'post-image',
				'post-category',
				'post-tags',
				'product-id',
				'product-quantity',
				'min',
				'max',
			)
		);
	}

	/**
	 * Field suffix label
	 * Displayable label for suffix
	 *
	 * @param string $suffix Suffix.
	 *
	 * @return string
	 * @since 1.0.5
	 */
	public static function translate_suffix( $suffix ) {
		$translated_suffix = $suffix;
		$field_suffixes    = self::field_suffix();
		$default_label_map = array(
			'hours'            => esc_html__( 'Hour', 'forminator' ),
			'minutes'          => esc_html__( 'Minute', 'forminator' ),
			'ampm'             => esc_html__( 'AM/PM', 'forminator' ),
			'country'          => esc_html__( 'Country', 'forminator' ),
			'city'             => esc_html__( 'City', 'forminator' ),
			'state'            => esc_html__( 'State', 'forminator' ),
			'zip'              => esc_html__( 'Zip', 'forminator' ),
			'street_address'   => esc_html__( 'Street Address', 'forminator' ),
			'address_line'     => esc_html__( 'Address Line 2', 'forminator' ),
			'year'             => esc_html__( 'Year', 'forminator' ),
			'day'              => esc_html__( 'Day', 'forminator' ),
			'month'            => esc_html__( 'Month', 'forminator' ),
			'prefix'           => esc_html__( 'Prefix', 'forminator' ),
			'first-name'       => esc_html__( 'First Name', 'forminator' ),
			'middle-name'      => esc_html__( 'Middle Name', 'forminator' ),
			'last-name'        => esc_html__( 'Last Name', 'forminator' ),
			'post-title'       => esc_html__( 'Post Title', 'forminator' ),
			'post-content'     => esc_html__( 'Post Content', 'forminator' ),
			'post-excerpt'     => esc_html__( 'Post Excerpt', 'forminator' ),
			'post-image'       => esc_html__( 'Post Image', 'forminator' ),
			'post-category'    => esc_html__( 'Post Category', 'forminator' ),
			'post-tags'        => esc_html__( 'Post Tags', 'forminator' ),
			'product-id'       => esc_html__( 'Product ID', 'forminator' ),
			'product-quantity' => esc_html__( 'Product Quantity', 'forminator' ),
		);

		// could be filtered out field_suffix.
		if ( in_array( $suffix, $field_suffixes, true ) && isset( $default_label_map[ $suffix ] ) ) {
			$translated_suffix = $default_label_map[ $suffix ];
		}

		/**
		 * Translatable suffix
		 *
		 * @param string $translated_suffix
		 * @param string $suffix original suffix.
		 * @param array $default_label_map default translated suffix.
		 *
		 * @since 1.0.5
		 */
		return apply_filters( 'forminator_translate_suffix', $translated_suffix, $suffix, $default_label_map );
	}

	/**
	 * Ignored fields
	 * Fields not saved or shown
	 *
	 * @return array
	 * @since 1.0
	 */
	public static function ignored_fields() {
		$ignored_fields = array(
			'html',
			'page-break',
			'captcha',
			'section',
		);
		if ( forminator_payments_disabled() ) {
			$ignored_fields[] = 'stripe';
			$ignored_fields[] = 'paypal';
			$ignored_fields[] = 'stripe-ocs';
		}

		return apply_filters( 'forminator_entry_ignored_fields', $ignored_fields );
	}

	/**
	 * Return if form has live payment entry
	 *
	 * @param int $form_id - the form id.
	 *
	 * @return mixed
	 * @since 1.10
	 */
	public static function has_live_payment( $form_id ) {
		global $wpdb;

		$cached_count = wp_cache_get( 'live_payment_count_' . $form_id, self::FORM_COUNT_CACHE_GROUP );

		if ( false !== $cached_count ) {
			return $cached_count;
		}

		$table_name       = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY_META );
		$entry_table_name = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY );

		$sql = "SELECT count(1) > 0
			FROM {$table_name} m
			LEFT JOIN {$entry_table_name} e
			ON (m.entry_id = e.entry_id)
			WHERE e.form_id = %d
			AND ( m.meta_key = 'stripe-1' || m.meta_key = 'stripe-ocs-1' || m.meta_key = 'paypal-1' )
			AND m.meta_value LIKE '%4:\"mode\";s:4:\"live\"%'
			LIMIT 1";

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery  -- unprepared SQL ok. false positive.
		$count = $wpdb->get_var( $wpdb->prepare( $sql, $form_id ) );

		wp_cache_set( 'live_payment_count_' . $form_id, $count, self::FORM_COUNT_CACHE_GROUP );

		return $count;
	}

	/**
	 * Get all entries
	 *
	 * @param int   $form_id - the form id.
	 * @param array $filters Filters.
	 *
	 * @return Forminator_Form_Entry_Model[]
	 * @since 1.40
	 */
	public static function get_all_entries( $form_id, $filters = array() ) {
		$per_page = apply_filters( 'forminator_get_all_entries_limit', 100, $form_id );
		$offset   = 0;
		$args     = array(
			'form_id'  => $form_id,
			'is_spam'  => 0,
			'per_page' => $per_page,
			'offset'   => $offset,
		);
		if ( ! empty( $filters ) && is_array( $filters ) ) {
			$args = array_merge( $filters, $args );
		}

		$all_entries = array();
		$page        = 1;
		do {
			$entries = self::query_entries( $args );
			if ( ! empty( $entries ) ) {
				$all_entries = array_merge( $all_entries, $entries );
			}
			$args['offset'] = $page * $per_page;
			$total_items    = count( $entries );
			++$page;
		} while ( $total_items === $per_page );

		return $all_entries;
	}

	/**
	 * Group count of Entries with extra selected
	 *
	 * @param int   $form_id Form id.
	 * @param array $fields_element_id_with_extra Fields element Id.
	 *
	 * @return array|null|object
	 * @example = [
	 *  'FIELDS_WITH_EXTRA_ELEMENT_ID' => [
	 *      'META_VALUE-1' => COUNT
	 *      'META_VALUE-2' => COUNT
	 * ],
	 * 'answer-3' => [
	 *      'javascript is the best' => 8
	 *      'php is the best' => 7
	 * ],
	 * ]
	 *
	 * @since   1.0.5
	 */
	public static function count_polls_with_extra( $form_id, $fields_element_id_with_extra ) {
		global $wpdb;

		$polls_with_extras = array();

		$table_name       = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY_META );
		$entry_table_name = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY );

		// Cached key.
		$cache_key = 'forminator_count_polls_with_extra_' . $form_id;
		// Check if cached data exists.
		$cached_data = wp_cache_get( $cache_key, self::FORM_ENTRY_CACHE_GROUP );
		if ( ! is_array( $cached_data ) ) {
			$cached_data = array();
		}
		foreach ( $fields_element_id_with_extra as $field_element_id_with_extra ) {
			$votes = array();
			if ( isset( $cached_data[ $field_element_id_with_extra ] ) ) {
				$votes = $cached_data[ $field_element_id_with_extra ];
			} else {
				$sql       = "SELECT m.entry_id AS entry_id
								FROM {$table_name} m
								LEFT JOIN {$entry_table_name} e
								ON (m.entry_id = e.entry_id)
								WHERE e.form_id = %d
								AND m.meta_key = %s
								GROUP BY m.entry_id";
				$sql       = $wpdb->prepare( $sql, $form_id, esc_sql( $field_element_id_with_extra ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$entry_ids = $wpdb->get_col( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery

				if ( ! empty( $entry_ids ) ) {
					$entry_id_placeholders = implode( ', ', array_fill( 0, count( $entry_ids ), '%d' ) );

					$sql = "SELECT m.meta_value AS meta_value, COUNT(1) votes
								FROM {$table_name} m
								WHERE m.entry_id IN ({$entry_id_placeholders})
								AND m.meta_key = 'extra'
								GROUP BY m.meta_value ORDER BY votes DESC";
					$sql = $wpdb->prepare( $sql, $entry_ids ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

					$votes = $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery

					$cached_data[ $field_element_id_with_extra ] = $votes;
					// Store the result in the cache.
					wp_cache_set( $cache_key, $cached_data, self::FORM_ENTRY_CACHE_GROUP );
				}
			}
			if ( ! empty( $votes ) ) {
				$polls_with_extras[ $field_element_id_with_extra ] = array();
				foreach ( $votes as $vote ) {
					$polls_with_extras[ $field_element_id_with_extra ][ $vote['meta_value'] ] = $vote['votes'];
				}
			}
		}

		return $polls_with_extras;
	}

	/**
	 * Count entries by form
	 *
	 * @param int   $form_id - the form id.
	 * @param mixed $db DB.
	 * @param bool  $all_types Include draft, abandoned, etc.
	 *
	 * @return int - total entries
	 * @since 1.0
	 */
	public static function count_entries( $form_id, $db = false, $all_types = false ) {
		if ( ! $db ) {
			global $wpdb;
			$db = $wpdb;
		}
		// Cached key.
		$cache_key = 'forminator_count_entries_of_active_status_' . $form_id;
		if ( $all_types ) {
			$cache_key = 'forminator_count_entries_of_all_status_' . $form_id;
		}
		$entries_cache = wp_cache_get( $cache_key, self::FORM_COUNT_CACHE_GROUP );
		$where         = '';

		if ( $entries_cache ) {
			return $entries_cache;
		} else {
			$table_name = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY );
			$where      = $all_types ? " AND `status` != 'spam'" : " AND `status` = 'active'";
			$sql        = "SELECT count(`entry_id`) FROM {$table_name} WHERE `form_id` = %d {$where}";
			$entries    = $db->get_var( $db->prepare( $sql, $form_id ) );
			if ( $entries ) {
				wp_cache_set( $cache_key, $entries, self::FORM_COUNT_CACHE_GROUP );

				return $entries;
			}
		}

		return 0;
	}

	/**
	 * Count lead entries by form
	 *
	 * @param int    $form_id Form Id.
	 * @param string $start_date Start date.
	 * @param string $end_date End date.
	 *
	 * @return int|string
	 */
	public static function count_leads( $form_id, $start_date = '', $end_date = '' ) {
		global $wpdb;
		$table_name       = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY_META );
		$entry_table_name = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY );

		// Cached key.
		$cache_key = 'forminator_count_leads_' . $form_id;
		$unique_id = md5( wp_json_encode( array( $start_date, $end_date ) ) );
		// Get cached data.
		$cached_data = wp_cache_get( $cache_key, self::FORM_ENTRY_CACHE_GROUP );
		if ( ! is_array( $cached_data ) ) {
			$cached_data = array();
		}
		if ( isset( $cached_data[ $unique_id ] ) ) {
			return $cached_data[ $unique_id ];
		} else {
			$where = '';
			if ( ! empty( $start_date ) && ! empty( $end_date ) ) {
				$end_date = $end_date . ' 23:59:00';
				$where   .= $wpdb->prepare( ' AND ( e.date_created >= %s AND e.date_created <= %s )', esc_sql( $start_date ), esc_sql( $end_date ) );
			}

			$sql     =
				"SELECT count(DISTINCT e.`entry_id`) FROM {$table_name} m
				LEFT JOIN {$entry_table_name} e ON(e.`entry_id` = m.`entry_id`)
				WHERE e.`form_id` = %d AND m.meta_key = 'skip_form'
				{$where}
				AND m.meta_value = '0' AND e.`status` = 'active'";
			$entries = $wpdb->get_var( $wpdb->prepare( $sql, $form_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery -- false positive

			if ( ! $entries ) {
				$entries = 0;
			}

			$cached_data[ $unique_id ] = $entries;
			// Store the result in the cache.
			wp_cache_set( $cache_key, $cached_data, self::FORM_ENTRY_CACHE_GROUP );

			return $entries;
		}
	}


	/**
	 * Count entries by form
	 *
	 * @param int $form_id - the form id.
	 *
	 * @return int - total entries
	 * @since 1.0
	 * @deprecated
	 */
	public static function count_entries_by_form_and_field( $form_id ) {
		_deprecated_function( 'count_entries_by_form_and_field', '1.0.5' );
		global $wpdb;
		$field            = '';
		$table_name       = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY_META );
		$entry_table_name = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY );
		$sql              =
			"SELECT count(m.`meta_id`) FROM {$table_name} m LEFT JOIN {$entry_table_name} e ON(e.`entry_id` = m.`entry_id`) WHERE e.`form_id` = %d AND m.`meta_key` = %s AND e.`status` = 'active'";
		$entries          = $wpdb->get_var( $wpdb->prepare( $sql, $form_id, $field ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery

		if ( $entries ) {
			return $entries;
		}

		return 0;
	}

	/**
	 * Map Polls Entries with its votes
	 *
	 * @param int   $form_id Form id.
	 * @param array $fields Fields.
	 *
	 * @return array
	 * @example {
	 *  'ELEMENT_ID' => 'NUMBER'
	 *  'answer-1' = 9
	 * }
	 *
	 * @since   1.0.5
	 */
	public static function map_polls_entries( $form_id, $fields ) {
		$cache_key      = 'poll_entries_' . $form_id;
		$cached_entries = wp_cache_get( $cache_key, self::FORM_ENTRY_CACHE_GROUP );
		if ( false !== $cached_entries ) {
			return $cached_entries;
		}
		global $wpdb;
		$map_entries      = array();
		$table_name       = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY_META );
		$entry_table_name = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY );

		// Make sure $form_id is always number.
		if ( ! is_numeric( $form_id ) ) {
			$form_id = 0;
		}

		$element_ids = array();
		foreach ( $fields as $field ) {
			$element_id    = (string) $field['element_id'];
			$element_ids[] = $element_id;
			$title         = sanitize_title( $field['title'] );

			// First, escape the link for use in a LIKE statement.
			$new_element_id_format = $wpdb->esc_like( 'answer-' );
			// Add wildcards.
			$new_element_id_format = $new_element_id_format . '%';

			// find old format entries of this field.
			$sql = "SELECT count(1) FROM {$table_name} m LEFT JOIN {$entry_table_name} e
					ON (e.`entry_id` = m.`entry_id`)
					WHERE e.form_id = %d AND m.meta_key NOT LIKE %s AND m.meta_value = '1' AND m.meta_key = %s LIMIT 1";

			$old_format_entries = $wpdb->get_var( $wpdb->prepare( $sql, $form_id, esc_sql( $new_element_id_format ), esc_sql( $title ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery

			// old format exist.
			if ( $old_format_entries ) {
				// update old format entries if avail.
				self::maybe_update_poll_entries_meta_key_to_element_id( $form_id, $title, $element_id );
			}
		}

		if ( ! empty( $element_ids ) ) {
			$element_ids_placeholders = implode( ', ', array_fill( 0, count( $element_ids ), '%s' ) );

			$sql
				= "SELECT m.meta_key as element_id, count(1) as votes
					FROM {$table_name} m LEFT JOIN {$entry_table_name} e
					ON (e.`entry_id` = m.`entry_id`)
					WHERE e.form_id = {$form_id} AND m.meta_key IN ({$element_ids_placeholders}) GROUP BY m.meta_key";

			$sql = $wpdb->prepare( $sql, $element_ids ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

			$results = $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery
			foreach ( $results as $result ) {
				$map_entries[ $result['element_id'] ] = $result['votes'];
			}
		}

		wp_cache_set( $cache_key, $map_entries, self::FORM_ENTRY_CACHE_GROUP );

		return $map_entries;
	}

	/**
	 * Map Polls Entries to be used for export
	 * Pretty much @see Form_Entry_Model::map_polls_entries(), but returning what's required for the export.
	 *
	 * @since   1.6
	 *
	 * @example {
	 *  'meta_id' => 'values'
	 *  '2' = [
	 *        'date_created' = '1999-12-31 23:59:59',
	 *        'meta_key'       = 'answer-1',
	 *        'is_spam'       = '0',
	 *    ]
	 * }
	 *
	 * @param int   $form_id Form Id.
	 * @param array $fields Fields.
	 *
	 * @return array
	 */
	public static function map_polls_entries_for_export( $form_id, $fields ) {
		global $wpdb;
		$map_entries      = array();
		$table_name       = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY_META );
		$entry_table_name = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY );

		// Make sure $form_id is always number.
		if ( ! is_numeric( $form_id ) ) {
			$form_id = 0;
		}

		$element_ids = array();
		foreach ( $fields as $field ) {
			$element_id    = (string) $field['element_id'];
			$element_ids[] = $element_id;
			$title         = sanitize_title( $field['title'] );

			// First, escape the link for use in a LIKE statement.
			$new_element_id_format = $wpdb->esc_like( 'answer-' );
			// Add wildcards.
			$new_element_id_format = $new_element_id_format . '%';

			// find old format entries of this field.
			$sql = "SELECT count(1) FROM {$table_name} m LEFT JOIN {$entry_table_name} e
				ON (e.`entry_id` = m.`entry_id`)
				WHERE e.form_id = %d AND m.meta_key NOT LIKE %s AND m.meta_value = '1' AND m.meta_key = %s LIMIT 1";

			$old_format_entries = $wpdb->get_var( $wpdb->prepare( $sql, $form_id, esc_sql( $new_element_id_format ), esc_sql( $title ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery

			// old format exist.
			if ( $old_format_entries ) {
				// update old format entries if avail.
				self::maybe_update_poll_entries_meta_key_to_element_id( $form_id, $title, $element_id );
			}
		}

		if ( ! empty( $element_ids ) ) {
			$element_ids_placeholders = implode( ', ', array_fill( 0, count( $element_ids ), '%s' ) );

			$sql
				= "SELECT m.meta_id, m.meta_key, m.meta_value, m.date_created, e.status, m.entry_id
					FROM {$table_name} m LEFT JOIN {$entry_table_name} e
					ON (e.`entry_id` = m.`entry_id`)
					WHERE e.form_id = {$form_id} AND m.meta_key IN ({$element_ids_placeholders})";

			$sql = $wpdb->prepare( $sql, $element_ids ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

			$results = $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery

			foreach ( $results as $result ) {
				$map_entries[ $result['meta_id'] ]['entry_id']     = $result['entry_id'];
				$map_entries[ $result['meta_id'] ]['status']       = $result['status'];
				$map_entries[ $result['meta_id'] ]['date_created'] = $result['date_created'];
				$map_entries[ $result['meta_id'] ]['meta_key']     = $result['meta_key']; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				$map_entries[ $result['meta_id'] ]['meta_value']   = $result['meta_value']; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			}
		}

		return $map_entries;
	}

	/**
	 * Update poll entries meta_key to its element_id
	 *
	 * @param int    $form_id Form id.
	 * @param string $old_meta_key Old meta key.
	 * @param string $element_id Element Id.
	 *
	 * @since 1.0.5
	 */
	public static function maybe_update_poll_entries_meta_key_to_element_id( $form_id, $old_meta_key, $element_id ) {
		global $wpdb;
		$table_name       = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY_META );
		$entry_table_name = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY );
		// find entries that using old format.
		$sql = "SELECT entry_id FROM {$entry_table_name} where form_id = %d";

		$sql       = $wpdb->prepare( $sql, $form_id ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$entry_ids = $wpdb->get_col( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery
		if ( ! empty( $entry_ids ) && count( $entry_ids ) > 0 ) {
			$entry_ids = implode( ', ', $entry_ids );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE %s SET meta_key = %s, meta_value = %s WHERE entry_id IN (%s) AND meta_key = %s AND meta_value = '1'",
					$table_name,
					$element_id,
					$entry_ids,
					$old_meta_key,
					$old_meta_key
				)
			);
		}
	}

	/**
	 * Get entry date by ip and form
	 *
	 * @param int    $form_id - the form id.
	 * @param string $ip -  the user ip.
	 *
	 * @return string|bool
	 * @since 1.0
	 */
	public static function get_entry_date_by_ip_and_form( $form_id, $ip ) {
		global $wpdb;
		$table_name       = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY_META );
		$entry_table_name = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY );
		$sql              =
			"SELECT m.`date_created` FROM {$table_name} m LEFT JOIN {$entry_table_name} e ON(e.`entry_id` = m.`entry_id`) WHERE e.`form_id` = %d AND m.`meta_key` = %s AND m.`meta_value` = %s order by m.`meta_id` desc limit 0,1";
		$entry_date       = $wpdb->get_var( $wpdb->prepare( $sql, $form_id, '_forminator_user_ip', $ip ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery

		if ( $entry_date ) {
			return $entry_date;
		}

		return false;
	}

	/**
	 * Get amount submissions by current user
	 *
	 * @param int $form_id Form ID.
	 * @return int
	 */
	public static function count_user_entries( $form_id ) {
		global $wpdb;
		$user_id = get_current_user_id();
		// Cached key.
		$cache_key = 'forminator_count_user_entries_' . $form_id;
		// Get cached data.
		$cached_data = wp_cache_get( $cache_key, self::FORM_ENTRY_CACHE_GROUP );
		if ( ! is_array( $cached_data ) ) {
			$cached_data = array();
		}
		if ( isset( $cached_data[ $user_id ] ) ) {
			return $cached_data[ $user_id ];
		} else {
			$table_name              = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY_META );
			$entry_table_name        = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY );
			$sql                     =
				"SELECT COUNT(*) FROM {$table_name} m LEFT JOIN {$entry_table_name} e ON(e.`entry_id` = m.`entry_id`) WHERE e.`form_id` = %d AND m.`meta_key` = '_user_id' AND m.`meta_value` = %s";
			$amount                  = $wpdb->get_var( $wpdb->prepare( $sql, $form_id, $user_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery
			$amount                  = intval( $amount );
			$cached_data[ $user_id ] = $amount;
			// Store the result in the cache.
			wp_cache_set( $cache_key, $cached_data, self::FORM_ENTRY_CACHE_GROUP );

			return $amount;
		}
	}

	/**
	 * Get last entry by IP and form
	 *
	 * @param int    $form_id - the form id.
	 * @param string $ip -  the user ip.
	 *
	 * @return string|bool
	 * @since 1.0
	 */
	public static function get_last_entry_by_ip_and_form( $form_id, $ip ) {
		$cache_key    = 'last_entry_by_ip_' . $form_id . '_' . md5( $ip );
		$cache_group  = self::FORM_ENTRY_CACHE_GROUP . '_' . $form_id;
		$cached_entry = wp_cache_get( $cache_key, $cache_group );
		if ( false !== $cached_entry ) {
			return $cached_entry;
		}
		global $wpdb;
		$table_name       = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY_META );
		$entry_table_name = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY );
		$sql              =
			"SELECT m.`entry_id` FROM {$table_name} m LEFT JOIN {$entry_table_name} e ON(e.`entry_id` = m.`entry_id`) WHERE e.`form_id` = %d AND m.`meta_key` = %s AND m.`meta_value` = %s order by m.`meta_id` desc limit 0,1";
		$entry_id         = $wpdb->get_var( $wpdb->prepare( $sql, $form_id, '_forminator_user_ip', $ip ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery

		if ( $entry_id ) {
			wp_cache_set( $cache_key, $entry_id, $cache_group );

			return $entry_id;
		}

		return false;
	}

	/**
	 * Get entry date by ip and form
	 *
	 * @param int    $form_id - the form id.
	 * @param string $ip -  the user ip.
	 * @param int    $entry_id - the entry id.
	 * @param string $interval - the mysql interval. Eg (INTERVAL 1 HOUR).
	 *
	 * @return string|bool
	 * @since 1.0
	 */
	public static function check_entry_date_by_ip_and_form( $form_id, $ip, $entry_id, $interval = '' ) {
		global $wpdb;
		$current_date     = date_i18n( 'Y-m-d H:i:s' );
		$table_name       = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY_META );
		$entry_table_name = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY );
		$interval         = esc_sql( $interval );
		$sql              =
			"SELECT m.`meta_id` FROM {$table_name} m LEFT JOIN {$entry_table_name} e ON(e.`entry_id` = m.`entry_id`) WHERE e.`form_id` = %d AND m.`meta_key` = %s AND m.`meta_value` = %s AND m.`entry_id` = %d AND DATE_ADD(m.`date_created`, {$interval}) < %s order by m.`meta_id` desc limit 0,1";
		$entry            = $wpdb->get_var( $wpdb->prepare( $sql, $form_id, '_forminator_user_ip', $ip, $entry_id, $current_date ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery

		if ( $entry ) {
			return $entry;
		}

		return false;
	}

	/**
	 * Bulk delete form entries
	 *
	 * @param int   $form_id - the form id.
	 * @param mixed $db - DB.
	 *
	 * @since 1.0
	 */
	public static function delete_by_form( $form_id, $db = false ) {
		if ( ! $db ) {
			global $wpdb;
			$db = $wpdb;
		}
		$table_name = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY );
		$sql        = "SELECT GROUP_CONCAT(`entry_id`) FROM {$table_name} WHERE `form_id` = %d";
		$entries    = $db->get_var( $db->prepare( $sql, $form_id ) );

		if ( $entries ) {
			self::delete_by_entrys( $form_id, $entries, $db );
		}
	}

	/**
	 * Delete by string of comma separated entry ids
	 *
	 * @param int       $form_id Form id.
	 * @param string    $entries Entries.
	 * @param bool|wpdb $db DB.
	 *
	 * @return bool
	 * @since 1.0
	 * @since 1.1 Add init addons and Add hooks `forminator_before_delete_entry`
	 */
	public static function delete_by_entrys( $form_id, $entries, $db = false ) {
		if ( ! $db ) {
			global $wpdb;
			$db = $wpdb;
		}

		if ( empty( $form_id ) || empty( $entries ) ) {
			return false;
		}

		$form_id = (int) $form_id;

		$table_name      = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY );
		$table_meta_name = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY_META );

		forminator_maybe_log( 'delete_by_entrys', $form_id, $entries );
		$entries_to_array = explode( ',', $entries );
		forminator_maybe_log( 'delete_by_entrys', $form_id, $entries_to_array );

		$valid_entries_to_delete = array();
		if ( ! empty( $entries_to_array ) && is_array( $entries_to_array ) ) {
			foreach ( $entries_to_array as $entry_id ) {
				$entry_id    = (int) $entry_id;
				$entry_model = new Forminator_Form_Entry_Model( $entry_id );
				// validate : entry must be exist on requested $form_id.
				if ( $form_id === (int) $entry_model->form_id ) {
					$valid_entries_to_delete[] = $entry_id;
					self::attach_addons_on_before_delete_entry( $form_id, $entry_model );
					self::entry_delete_upload_files( $form_id, $entry_model );
				}
			}
		}

		if ( empty( $valid_entries_to_delete ) ) {
			return false;
		}

		// modify $entries with $valid_entries_to_delete.
		$entries = implode( ', ', $valid_entries_to_delete );
		/**
		 * Fires just before an entry getting deleted
		 *
		 * @param int $form_id Current Form ID.
		 * @param int $entry_id Current Entry ID to be deleted.
		 *
		 * @since 1.1
		 */
		do_action_ref_array( 'forminator_before_delete_entries', array( $form_id, $entries ) );

		$sql = "DELETE FROM {$table_meta_name} WHERE `entry_id` IN ($entries)";
		$db->query( $sql );

		$sql = "DELETE FROM {$table_name} WHERE `entry_id` IN ($entries)";
		$db->query( $sql );

		self::delete_form_entry_cache( $form_id );
		wp_cache_delete( 'all_form_types', self::FORM_COUNT_CACHE_GROUP );

		$model = forminator_get_model_from_id( $form_id );
		if ( is_object( $model ) ) {
			wp_cache_delete( $model->get_entry_type() . '_form_type', self::FORM_COUNT_CACHE_GROUP );
		}
	}

	/**
	 * Delete by entry
	 *
	 * @param int $entry_id - the entry id.
	 *
	 * @since 1.0
	 * @since 1.1 Add init addons and Add hooks `forminator_before_delete_entry`
	 */
	public static function delete_by_entry( $entry_id ) {
		global $wpdb;

		$table_name      = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY );
		$table_meta_name = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY_META );
		$entry_model     = new Forminator_Form_Entry_Model( $entry_id );

		if ( empty( $entry_model->entry_id ) ) {
			return;
		}

		$form_id  = (int) $entry_model->form_id;
		$entry_id = (int) $entry_id;
		forminator_maybe_log( 'forminator_before_delete_entry', $form_id, $entry_id );
		/**
		 * Fires just before an entry getting deleted
		 *
		 * @param int $form_id Current Form ID.
		 * @param int $entry_id Current Entry ID to be deleted.
		 *
		 * @since 1.1
		 */
		do_action_ref_array( 'forminator_before_delete_entry', array( $form_id, $entry_id ) );
		self::attach_addons_on_before_delete_entry( $form_id, $entry_model );
		self::entry_delete_upload_files( $form_id, $entry_model );

		$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . esc_sql( $table_meta_name ) . ' WHERE `entry_id` = %d', $entry_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery

		$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . esc_sql( $table_name ) . ' WHERE `entry_id` = %d', $entry_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery

		wp_cache_delete( $entry_id, self::FORM_ENTRY_CACHE_GROUP );
		self::delete_form_entry_cache( $form_id );
		wp_cache_delete( 'all_form_types', self::FORM_COUNT_CACHE_GROUP );
		wp_cache_delete( $entry_model->entry_type . '_form_type', self::FORM_COUNT_CACHE_GROUP );
	}

	/**
	 * Delete files from upload folder
	 *
	 * @param int                         $form_id Form Id.
	 * @param Forminator_Form_Entry_Model $entry_model Form Entry model.
	 *
	 * @since 1.7
	 */
	public static function entry_delete_upload_files( $form_id, $entry_model ) {
		$custom_form     = Forminator_Base_Form_Model::get_model( $form_id );
		$submission_file = 'delete';
		if ( is_object( $custom_form ) ) {
			$settings        = $custom_form->settings;
			$submission_file = isset( $settings['submission-file'] ) ? $settings['submission-file'] : 'delete';
		}
		if ( 'delete' === $submission_file ) {
			$upload_root = wp_upload_dir();
			if ( empty( $upload_root['basedir'] ) ) {
				return;
			}
			$upload_root = $upload_root['basedir'];
			foreach ( $entry_model->meta_data as $slug => $meta_data ) {
				$meta_value = $meta_data['value'];
				$field_type = Forminator_Core::get_field_type( $slug );
				if ( in_array( $field_type, array( 'upload', 'signature' ), true )
						&& is_array( $meta_value ) && isset( $meta_value['file'] ) ) {
					$file_path = is_array( $meta_value['file']['file_path'] ) ? $meta_value['file']['file_path'] : array( $meta_value['file']['file_path'] );
					if ( ! empty( $file_path ) ) {
						foreach ( $file_path as $key => $path ) {
							$path = realpath( $path );
							if ( ! $path || ! file_exists( $path ) ) {
								continue;
							}

							$basename  = wp_basename( $path );
							$sanitized = sanitize_file_name( $basename );
							if ( $basename !== $sanitized ) {
								continue;
							}

							$normalized_upload_root = wp_normalize_path( $upload_root );
							$normalized_path        = wp_normalize_path( $path );
							if ( ! empty( $normalized_upload_root ) && 0 !== strpos( $normalized_path, $normalized_upload_root ) ) {
								continue;
							}

							wp_delete_file( $path );
							if ( isset( $meta_value['file']['file_url'][ $key ] ) ) {
								$attachment_id = attachment_url_to_postid( $meta_value['file']['file_url'][ $key ] );
								if ( $attachment_id ) {
									wp_delete_attachment( $attachment_id );
								}
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Convert meta value to string
	 * Useful on displaying metadata without PHP warning on conversion
	 *
	 * @param string $field_type Field type.
	 * @param mixed  $meta_value Meta value.
	 * @param bool   $allow_html Allow HTML.
	 * @param int    $truncate truncate returned string (usefull if display container is limited).
	 * @param mixed  $field Field data (optional).
	 *
	 * @return string
	 * @since 1.0.5
	 */
	public static function meta_value_to_string( $field_type, $meta_value, $allow_html = false, $truncate = PHP_INT_MAX, $field = null ) {
		switch ( $field_type ) {
			case 'postdata':
				$string_value = self::postdata_to_string( $meta_value, $allow_html, $truncate );
				break;
			case 'time':
				if ( ! isset( $meta_value['hours'] ) || ! isset( $meta_value['minutes'] ) ) {
					$string_value = '';
				} else {
					$string_value = sprintf( '%02d', $meta_value['hours'] ) . ':' . sprintf( '%02d', $meta_value['minutes'] ) . ' ' . ( isset( $meta_value ['ampm'] ) ? $meta_value['ampm'] : '' );
				}
				// truncate.
				if ( strlen( $string_value ) > $truncate ) {
					$string_value = substr( $string_value, 0, $truncate ) . '...';
				}
				break;
			case 'date':
				if ( ! isset( $meta_value['year'] ) || ! isset( $meta_value['month'] ) || ! isset( $meta_value['day'] ) ) {
					// is it date picker?
					if ( ! empty( $meta_value ) && is_string( $meta_value ) ) {
						$string_value = $meta_value;
					} else {
						$string_value = '';
					}
				} elseif ( empty( $meta_value['year'] ) || empty( $meta_value['month'] ) || empty( $meta_value['day'] ) ) {
						$string_value = '';
				} else {
					$date_value = $meta_value['year'] . '/' . sprintf( '%02d', $meta_value['month'] ) . '/' . sprintf( '%02d', $meta_value['day'] );
					if ( isset( $meta_value['format'] ) && ! empty( $meta_value['format'] ) ) {
						$string_value = date_i18n( $meta_value['format'], strtotime( $date_value ) );
					} else {
						$string_value = date_i18n( get_option( 'date_format' ), strtotime( $date_value ) );
					}
				}
				// truncate.
				if ( strlen( $string_value ) > $truncate ) {
					$string_value = substr( $string_value, 0, $truncate ) . '...';
				}
				break;
			case 'email':
				if ( ! empty( $meta_value ) ) {
					$string_value = $meta_value;
					// truncate.
					if ( $allow_html ) {
						// make link.
						$email = $string_value;
						// truncate.
						if ( strlen( $email ) > $truncate ) {
							$email = substr( $email, 0, $truncate ) . '...';
						}
						$string_value = '<a href="mailto:' . $email . '" target="_blank" rel="noopener noreferrer" title="' . esc_html__( 'Send Email', 'forminator' ) . '">' . $email . '</a>';
					} elseif ( strlen( $string_value ) > $truncate ) {
						// truncate url.
						$string_value = substr( $string_value, 0, $truncate ) . '...';
					}
				} else {
					$string_value = '';
				}

				break;
			case 'url':
				if ( ! empty( $meta_value ) ) {
					$string_value = $meta_value;
					// truncate.
					if ( $allow_html ) {
						// make link.
						$website = esc_url( $string_value );
						// truncate.
						if ( strlen( $website ) > $truncate ) {
							$website = substr( $website, 0, $truncate ) . '...';
						}
						$string_value = '<a href="' . $website . '" target="_blank" rel="noopener noreferrer" title="' . esc_html__( 'View Website', 'forminator' ) . '">' . $website . '</a>';
					} elseif ( strlen( $string_value ) > $truncate ) {
						// truncate url.
						$string_value = substr( $string_value, 0, $truncate ) . '...';
					}
				} else {
					$string_value = '';
				}

				break;
			case 'upload':
				$file = '';
				if ( isset( $meta_value['file'] ) ) {
					$file = $meta_value['file'];
				}
				if ( ! empty( $file ) && is_array( $file ) && isset( $file['file_url'] ) && ! empty( $file['file_url'] ) ) {
					if ( $allow_html ) {
						// make link.
						$string_value = '';
						$upload_count = 0;
						$file_values  = is_array( $file['file_url'] ) ? $file['file_url'] : array( $file['file_url'] );
						foreach ( $file_values as $file_value ) {
							$url       = $file_value;
							$file_name = basename( $url );
							$file_name = ! empty( $file_name ) ? $file_name : esc_html__( '(no filename)', 'forminator' );
							// truncate.
							if ( strlen( $file_name ) > $truncate ) {
								$file_name = substr( $file_name, 0, $truncate ) . '...';
							}

							++$upload_count;
							if ( $upload_count > 1 ) {
								$string_value .= '<br/>';
							}

							$string_value .= '<a href="' . $url . '" rel="noopener noreferrer" target="_blank" title="' . esc_html__( 'View File', 'forminator' ) . '">' . $file_name . '</a>';
						}
					} else {
						// truncate url.
						$string_value = is_array( $file['file_url'] ) ? implode( ', ', $file['file_url'] ) : $file['file_url'];
						if ( strlen( $string_value ) > $truncate ) {
							$string_value = substr( $string_value, 0, $truncate ) . '...';
						}
					}
				} else {
					$string_value = '';
				}
				break;
			case 'checkbox':
				if ( is_array( $meta_value ) ) {
					$string_value = implode( ', ', $meta_value );
				} elseif ( is_string( $meta_value ) ) {
					$string_value = $meta_value;
				} else {
					$string_value = '';
				}
				// truncate.
				if ( strlen( $string_value ) > $truncate ) {
					$string_value = substr( $string_value, 0, $truncate ) . '...';
				}
				break;
			case 'calculation':
				if ( ! is_array( $meta_value ) ) {
					$string_value = '0.0';
				} elseif ( ! empty( $meta_value['error'] ) ) {
						$string_value = $meta_value['error'];
				} else {
					if ( isset( $meta_value['result'] ) && is_array( $field ) && ! empty( $field ) ) {
							$result = Forminator_Field::forminator_number_formatting( $field, $meta_value['result'] );
					}
					if ( ! isset( $result ) ) {
						if ( isset( $meta_value['formatting_result'] ) ) {
							$result = $meta_value['formatting_result'];
						} else {
							$result = $meta_value['result'];
						}
					}
					if ( ! isset( $result ) ) {
						$string_value = '0.0';
					} elseif ( is_infinite( floatval( $result ) ) ) {
							$string_value = 'INF';
					} else {
						$string_value = (string) $result;
					}
				}
				// truncate.
				if ( strlen( $string_value ) > $truncate ) {
					$string_value = substr( $string_value, 0, $truncate ) . '...';
				}
				break;
			case 'stripe':
			case 'stripe-ocs':
				// In case stripe requested without mapper, we return transaction_id.
				$string_value = '';
				if ( is_array( $meta_value ) && isset( $meta_value['transaction_id'] ) ) {
					if ( ! empty( $meta_value['transaction_id'] ) ) {
						$string_value = $meta_value['transaction_id'];
					}
				}
				// truncate.
				if ( strlen( $string_value ) > $truncate ) {
					$string_value = substr( $string_value, 0, $truncate ) . '...';
				}

				/**
				 * Filter string value of Stripe meta entry
				 *
				 * @param string $string_value
				 * @param array $meta_value
				 * @param boolean $allow_html
				 * @param int $truncate
				 *
				 * @return string
				 * @since 1.7
				 */
				$string_value = apply_filters( 'forminator_entry_stripe_meta_value_to_string', $string_value, $meta_value, $allow_html, $truncate );
				break;
			case 'password':
				// Hide value for login/template forms.
				$string_value = '*****';
				break;
			case 'slider':
				// Change behavior for range slider.
				if ( is_array( $meta_value ) && isset( $meta_value['min'] ) && isset( $meta_value['max'] ) ) {
					$string_value = $meta_value['min'] . ' - ' . $meta_value['max'];
					break;
				}
				// fall-through.
			case 'currency':
			case 'number':
				if ( is_array( $field ) && ! empty( $meta_value ) ) {
					$string_value = Forminator_Field::forminator_number_formatting( $field, $meta_value );
				} else {
					$string_value = (string) $meta_value;
				}
				break;
			default:
				// base flattener.
				// implode on array.
				if ( is_array( $meta_value ) ) {
					$string_value = wp_json_encode( $meta_value );
				} else {
					// or juggling to string.
					$string_value = (string) $meta_value;
				}
				// truncate.
				if ( strlen( $string_value ) > $truncate ) {
					$string_value = substr( $string_value, 0, $truncate ) . '...';
				}
				break;
		}

		/**
		 * Filter string value of meta entry
		 *
		 * @param string $string_value
		 * @param string $field_type
		 * @param array $meta_value
		 * @param boolean $allow_html
		 * @param int $truncate
		 *
		 * @return string
		 * @since 1.7
		 */
		$string_value = apply_filters( 'forminator_entry_meta_value_to_string', $string_value, $field_type, $meta_value, $allow_html, $truncate );

		return $string_value;
	}

	/**
	 * Process postdata meta value
	 *
	 * @param array $meta_value Meta value.
	 * @param bool  $allow_html Allow HTML.
	 * @param int   $truncate Truncate.
	 *
	 * @return string
	 * @since 1.17.0
	 */
	public static function postdata_to_string( $meta_value, $allow_html = false, $truncate = PHP_INT_MAX ) {
		if ( empty( $meta_value ) ) {
			$string_value = '';
		} elseif ( is_string( $meta_value ) ) {
			$string_value = $meta_value;
		} elseif ( ! empty( $meta_value['postdata'] ) ) {
			$post_id = $meta_value['postdata'];

			// Title.
			if ( current_user_can( 'edit_post', $post_id ) ) {
				$url = get_edit_post_link( $post_id, 'link' );
			} else {
				// is not logged in.
				$url = get_home_url();
			}

			// Title make link.
			$title        = get_the_title( $post_id );
			$post_content = isset( $meta_value['value']['post-content'] ) ? $meta_value['value']['post-content'] : '';
			$post_excerpt = isset( $meta_value['value']['post-excerpt'] ) ? $meta_value['value']['post-excerpt'] : '';
			$category     = isset( $meta_value['value']['category'] ) ? $meta_value['value']['category'] : array();
			$tags         = isset( $meta_value['value']['post_tag'] ) ? $meta_value['value']['post_tag'] : array();
			$post_custom  = isset( $meta_value['value']['post-custom'] ) ? $meta_value['value']['post-custom'] : '';
			$tax_keys     = forminator_list_custom_taxonomies( $meta_value['value'] );

			if ( $allow_html ) {
				$post_image = ! empty( $meta_value['value']['post-image'] ) && ! empty( $meta_value['value']['post-image']['attachment_id'] )
					? $meta_value['value']['post-image']['attachment_id']
					: '';

				$string_value  = self::get_postdata_title( $title, 'allow_html', $truncate, $url );
				$string_value .= self::get_postdata_content( $post_content, true, $truncate );
				$string_value .= self::get_postdata_excerpt( $post_excerpt, true, $truncate );
				$string_value .= self::get_postdata_categories( $category, true );
				$string_value .= self::get_postdata_tags( $tags, true );
				$string_value .= self::get_postdata_image( $post_image, true );
				$string_value .= self::get_postdata_customfields( $post_custom, true );

				if ( ! empty( $tax_keys ) ) {
					foreach ( $tax_keys as $tax_key => $tax_name ) {
						$taxonomy_val  = isset( $meta_value['value'][ $tax_name ] ) ? $meta_value['value'][ $tax_name ] : array();
						$string_value .= self::get_postdata_taxonomies( $tax_name, $taxonomy_val );
					}
				}
			} else {
				$post_image = ! empty( $meta_value['value']['post-image'] ) && ! empty( $meta_value['value']['post-image']['uploaded_file'] )
					? $meta_value['value']['post-image']['uploaded_file'][0]
					: '';

				$string_value  = self::get_postdata_title( $title, 'no_html', $truncate );
				$string_value .= self::get_postdata_content( $post_content, false, $truncate );
				$string_value .= self::get_postdata_excerpt( $post_excerpt, false, $truncate );
				$string_value .= self::get_postdata_categories( $category, false );
				$string_value .= self::get_postdata_tags( $tags, false );
				$string_value .= self::get_postdata_image( $post_image, false );
				$string_value .= self::get_postdata_customfields( $post_custom, false, $truncate );

				if ( ! empty( $tax_keys ) ) {
					foreach ( $tax_keys as $tax_key => $tax_name ) {
						$taxonomy_val  = isset( $meta_value['value'][ $tax_name ] ) ? $meta_value['value'][ $tax_name ] : array();
						$string_value .= self::get_postdata_taxonomies( $tax_name, $taxonomy_val, false );
					}
				}

				// truncate.
				if ( strlen( $string_value ) > $truncate ) {
					$string_value = substr( $string_value, 0, $truncate ) . '...';
				}
			}
		} else {
			// Draft postdata values.
			$title        = isset( $meta_value['post-title'] ) ? $meta_value['post-title'] : '';
			$post_content = isset( $meta_value['post-content'] ) ? $meta_value['post-content'] : '';
			$post_excerpt = isset( $meta_value['post-excerpt'] ) ? $meta_value['post-excerpt'] : '';
			$category     = isset( $meta_value['category'] ) ? $meta_value['category'] : array();
			$tags         = isset( $meta_value['post_tag'] ) ? $meta_value['post_tag'] : array();
			$tax_keys     = forminator_list_custom_taxonomies( $meta_value );

			$string_value  = self::get_postdata_title( $title, 'draft', $truncate );
			$string_value .= self::get_postdata_content( $post_content, true, $truncate );
			$string_value .= self::get_postdata_excerpt( $post_excerpt, true, $truncate );
			$string_value .= self::get_postdata_categories( $category, true );
			$string_value .= self::get_postdata_tags( $tags, true );
			if ( ! empty( $tax_keys ) ) {
				foreach ( $tax_keys as $tax_key => $tax_name ) {
					$taxonomy_val  = isset( $meta_value[ $tax_name ] ) ? $meta_value[ $tax_name ] : array();
					$string_value .= self::get_postdata_taxonomies( $tax_name, $taxonomy_val );
				}
			}
			// Custom fields are not being saved in drafts but automatically created after full submission.
		}

		return $string_value;
	}

	/**
	 * Get the postdata title depending on context
	 *
	 * @param string $value Value.
	 * @param string $type allow_html, no_html, draft.
	 * @param int    $truncate Truncate, Default: PHP_INT_MAX.
	 * @param string $url URL.
	 *
	 * @since 1.17.0
	 */
	public static function get_postdata_title( $value, $type = 'allow_html', $truncate = PHP_INT_MAX, $url = '' ) {
		if ( empty( $value ) ) {
			return;
		}

		$title = ! empty( $value ) ? $value : esc_html__( '(no title)', 'forminator' );
		$title = forminator_truncate_text( wp_kses_post( $title ), $truncate );
		$label = esc_html__( 'Title', 'forminator' );

		if ( 'no_html' !== $type ) {
			$value = '<b>' . $label . ':</b> ';
		}

		if ( 'allow_html' === $type ) {
			$value .= '<a href="' . $url . '" target="_blank" rel="noopener noreferrer" title="' . esc_attr__( 'Edit Post', 'forminator' ) . '">' . $title . '</a>';
		} elseif ( 'draft' === $type ) {
			$value .= $title;
		} else {
			$value  = $label . ': ';
			$value .= $title . ' | ';
		}

		return $value;
	}

	/**
	 * Get the postdata content depending on context
	 *
	 * @param string $value Value.
	 * @param bool   $allow_html Allow HTML.
	 * @param int    $truncate Truncate.
	 *
	 * @since 1.17.0
	 */
	public static function get_postdata_content( $value = '', $allow_html = true, $truncate = PHP_INT_MAX ) {
		if ( empty( $value ) ) {
			return '';
		}

		$post_content = forminator_truncate_text( $value, $truncate );
		$label        = esc_html__( 'Content', 'forminator' );

		if ( $allow_html ) {
			$value  = '<hr>';
			$value .= '<b>' . $label . ':</b><br>';
			$value .= wp_kses_post( forminator_replace_linebreaks( $post_content ), 'post' );
		} else {
			$post_content = wp_strip_all_tags( $post_content );
			$value        = $label . ': ';
			$value       .= $post_content . ' | ';
		}

		return $value;
	}

	/**
	 * Get the postdata excerpt depending on context
	 *
	 * @param string $value Value.
	 * @param bool   $allow_html Allow HTML.
	 * @param int    $truncate Truncate.
	 *
	 * @since 1.17.0
	 */
	public static function get_postdata_excerpt( $value = '', $allow_html = true, $truncate = PHP_INT_MAX ) {
		if ( empty( $value ) ) {
			return;
		}

		$post_excerpt = forminator_truncate_text( $value, $truncate );
		$label        = esc_html__( 'Excerpt', 'forminator' );

		if ( $allow_html ) {
			$value  = '<hr>';
			$value .= '<b>' . $label . ':</b><br>';
			$value .= wp_strip_all_tags( $post_excerpt );
		} else {
			$post_excerpt = wp_strip_all_tags( $post_excerpt );
			$value        = $label . ': ';
			$value       .= $post_excerpt . ' | ';
		}

		return $value;
	}

	/**
	 * Get the postdata categories depending on context
	 *
	 * @param string|array $value Value.
	 * @param bool         $allow_html Allow HTML.
	 *
	 * @since 1.17.0
	 */
	public static function get_postdata_categories( $value = '', $allow_html = true ) {
		if ( empty( $value ) ) {
			return;
		}

		$post_category  = $value;
		$the_categories = '';
		$countegories   = 0;
		$single_label   = esc_html__( 'Category', 'forminator' );

		if ( is_array( $post_category ) ) {
			foreach ( $post_category as $category ) {
				$categories[] = get_the_category_by_ID( $category );
			}

			$countegories   = count( $categories );
			$the_categories = implode( ', ', $categories );
		} else {
			$the_categories = get_the_category_by_ID( $post_category );
		}

		if ( $allow_html ) {
			$value = '<hr>';

			if ( is_array( $post_category ) ) {
				$value .= '<b>' . esc_html( _n( 'Category', 'Categories', $countegories, 'forminator' ) ) . ':</b> ';
			} else {
				$value .= '<b>' . $single_label . ':</b> ';
			}

			$value .= $the_categories;
		} else {
			$value  = '';
			$value .= $single_label . ': ';
			$value .= $the_categories . ' | ';
		}

		return $value;
	}

	/**
	 * Get the postdata taxonmies depending on context
	 *
	 * @param string       $tax_name Tax name.
	 * @param string|array $value Value.
	 * @param bool         $allow_html Allow HTML.
	 *
	 * @since 1.20.0
	 */
	public static function get_postdata_taxonomies( $tax_name, $value = '', $allow_html = true ) {
		if ( empty( $value ) ) {
			return;
		}

		$post_taxonomy = $value;
		$tax_obj       = get_taxonomy( $tax_name );
		$single_label  = '';
		$plural_label  = '';

		if ( ! empty( $tax_obj ) ) {
			$single_label = ! empty( $tax_obj->labels->singular_name ) ? $tax_obj->labels->singular_name : $tax_obj->labels->name;
			$plural_label = $tax_obj->labels->name;
		}

		$the_taxonomies = '';
		$countonomies   = 0;
		if ( is_array( $post_taxonomy ) ) {
			foreach ( $post_taxonomy as $taxonomy ) {
				if ( ! is_wp_error( get_the_category_by_ID( $taxonomy ) ) ) {
					$taxonomies[] = get_the_category_by_ID( $taxonomy );
				}
			}

			if ( ! empty( $taxonomies ) ) {
				$countonomies   = count( $taxonomies );
				$the_taxonomies = implode( ', ', $taxonomies );
			}
		} else {
			$the_taxonomies = get_the_category_by_ID( $post_taxonomy );
			if ( is_wp_error( $the_taxonomies ) ) {
				$the_taxonomies = '';
			}
		}

		if ( $allow_html ) {
			$value = '<hr>';
			if ( is_array( $post_taxonomy ) ) {
				$value .= '<b>' . esc_html( _n( $single_label, $plural_label, $countonomies, 'forminator' ) ) . ':</b> '; // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralSingle, WordPress.WP.I18n.NonSingularStringLiteralPlural
			} else {
				$value .= '<b>' . $single_label . ':</b> ';
			}
			$value .= $the_taxonomies;
		} else {
			$value  = '';
			$value .= $single_label . ': ';
			$value .= $the_taxonomies . ' | ';
		}

		return $value;
	}

	/**
	 * Get the postdata tags depending on context
	 *
	 * @param string|array $value Value.
	 * @param bool         $allow_html Allow HTML.
	 *
	 * @since 1.17.0
	 */
	public static function get_postdata_tags( $value = '', $allow_html = true ) {
		if ( empty( $value ) ) {
			return;
		}

		$post_tags  = $value;
		$the_tags   = '';
		$tags_count = 0;

		$term_args  = array(
			'taxonomy'         => 'post_tag',
			'term_taxonomy_id' => $post_tags,
			'hide_empty'       => false,
			'fields'           => 'names',
		);
		$term_query = new WP_Term_Query( $term_args );

		$tags = $term_query->terms;
		if ( ! empty( $tags ) ) {
			$tags_count = count( $tags );
			$the_tags  .= implode( ', ', $tags );
		}

		$label = esc_html( _n( 'Tag', 'Tags', $tags_count, 'forminator' ) );

		if ( $allow_html ) {
			$value  = '<hr>';
			$value .= '<b>' . $label . ':</b> ';

			$value .= $the_tags;
		} else {
			$value  = '';
			$value .= $label;
			$value .= $the_tags . ' | ';
		}

		return $value;
	}

	/**
	 * Get the postdata featured image depending on context
	 *
	 * @param string|array $value Value.
	 * @param bool         $allow_html Allow HTML.
	 *
	 * @since 1.17.0
	 */
	public static function get_postdata_image( $value = '', $allow_html = true ) {
		if ( empty( $value ) ) {
			return;
		}

		$label = esc_html__( 'Featured image', 'forminator' ) . ': ';

		if ( $allow_html ) {
			$post_image_id = $value;
			$value         = '<hr>';
			$value        .= '<b>' . $label . ':</b><br>';
			$value        .= wp_get_attachment_image( $post_image_id, array( 100, 100 ) );
		} else {
			$post_image_url = $value;
			$value          = $label . ': ';
			$value         .= $post_image_url;
		}

		return $value;
	}

	/**
	 * Get the postdata Custom fields depending on context
	 *
	 * @param string|array $value Value.
	 * @param bool         $allow_html Allow HTML.
	 * @param int          $truncate Truncate.
	 *
	 * @since 1.17.0
	 */
	public static function get_postdata_customfields( $value = array(), $allow_html = true, $truncate = PHP_INT_MAX ) {
		if ( empty( $value ) ) {
			return '';
		}

		$post_custom = $value;
		$label       = esc_html__( 'Custom fields', 'forminator' );

		if ( $allow_html ) {
			$value  = '<hr>';
			$value .= '<b>' . $label . ':</b><br>';

			$value .= '<ul class="' . esc_attr( 'bulleted' ) . '">';

			foreach ( $post_custom as $field ) {
				if ( ! empty( $field['value'] ) ) {
					$value .= '<li>';
					$value .= esc_html( $field['key'] ) . ': ';
					$value .= wp_kses_post( $field['value'] );
					$value .= '</li>';
				}
			}

			$value .= '</ul>';
		} else {
			$value = $label . ': ';

			foreach ( $post_custom as $index => $field ) {
				if ( ! empty( $field['value'] ) ) {
					if ( 0 !== $index ) {
						$value .= ', ';
					}
					$value .= esc_html( $field['key'] ) . ' = ';
					$value .= esc_html( $field['value'] );
				}
			}

			$value = forminator_truncate_text( $value, $truncate );
		}

		return $value;
	}

	/**
	 * Count all entries for all form_type
	 */
	public static function count_all_entries() {
		global $wpdb;
		$entries_cache = wp_cache_get( 'all_form_types', self::FORM_COUNT_CACHE_GROUP );

		if ( $entries_cache ) {
			return $entries_cache;
		} else {
			$table_name = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY );
			$sql        = "SELECT count(`entry_id`) FROM {$table_name} WHERE `status` = 'active'";
			$entries    = $wpdb->get_var( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery
			if ( $entries ) {
				wp_cache_set( 'all_form_types', $entries, self::FORM_COUNT_CACHE_GROUP );

				return $entries;
			}
		}

		return 0;
	}

	/**
	 * Count all entries for the selected entry type
	 *
	 * @param string $entry_type Entry type.
	 *
	 * @return int
	 * @since 1.5.4
	 */
	public static function count_all_entries_by_type( $entry_type = 'custom-forms' ) {
		$available_entry_types = array(
			'custom-forms',
			'quizzes',
			'poll',
		);

		if ( ! in_array( $entry_type, $available_entry_types, true ) ) {
			return null;
		}

		global $wpdb;
		$entries_cache = wp_cache_get( $entry_type . '_form_type', self::FORM_COUNT_CACHE_GROUP );

		if ( $entries_cache ) {

			return $entries_cache;
		} else {
			$table_name = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY );
			$sql        = "SELECT count(`entry_id`) FROM {$table_name} WHERE `entry_type` = %s AND `status` = 'active'";
			$entries    = $wpdb->get_var( $wpdb->prepare( $sql, $entry_type ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery
			if ( $entries ) {
				wp_cache_set( $entry_type . '_form_type', $entries, self::FORM_COUNT_CACHE_GROUP );

				return $entries;
			}
		}

		return 0;
	}

	/**
	 * Get Latest Entry
	 *
	 * @param string $entry_type Entry type.
	 *
	 * @return Forminator_Form_Entry_Model|null
	 */
	public static function get_latest_entry( $entry_type = 'custom-forms' ) {
		if ( 'form' === $entry_type ) {
			$entry_type = 'custom-forms';
		} elseif ( 'quiz' === $entry_type ) {
			$entry_type = 'quizzes';
		}
		$available_entry_types = array(
			'custom-forms',
			'quizzes',
			'poll',
			'all',
		);

		if ( ! in_array( $entry_type, $available_entry_types, true ) ) {
			return null;
		}

		global $wpdb;
		$entry      = null;
		$table_name = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY );
		if ( 'all' !== $entry_type ) {
			$sql = "SELECT `entry_id` FROM {$table_name} WHERE `entry_type` = %s AND `status` = 'active' ORDER BY `date_created` DESC";
			$sql = $wpdb->prepare( $sql, $entry_type ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		} else {
			$sql = "SELECT `entry_id` FROM {$table_name} WHERE `status` = 'active' ORDER BY `date_created` DESC";
		}
		$entry_id = $wpdb->get_var( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery

		if ( ! empty( $entry_id ) ) {
			$entry = new Forminator_Form_Entry_Model( $entry_id );
		}

		return $entry;
	}

	/**
	 * Get Latest Entry by form_id
	 *
	 * @param int    $form_id Form Id.
	 * @param string $order Order by.
	 *
	 * @return Forminator_Form_Entry_Model|null
	 */
	public static function get_latest_entry_by_form_id( $form_id, $order = 'DESC' ) {

		global $wpdb;
		$entry      = null;
		$table_name = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY );
		$sql        = "SELECT `entry_id` FROM {$table_name} WHERE `form_id` = %d AND `status` = 'active' ORDER BY `date_created` {$order}";
		$entry_id   = $wpdb->get_var( $wpdb->prepare( $sql, $form_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery

		if ( ! empty( $entry_id ) ) {
			$entry = new Forminator_Form_Entry_Model( $entry_id );
		}

		return $entry;
	}

	/**
	 * Get Connected Addons for form_id, avoid overhead for checking connected addons many times
	 *
	 * @param int    $module_id Module id.
	 * @param string $module_slug Module slug.
	 *
	 * @return array|Forminator_Integration[]
	 * @since 1.1
	 */
	public static function get_connected_addons( $module_id, $module_slug = 'form' ) {
		if ( ! isset( self::$connected_addons[ $module_id ] ) ) {
			self::$connected_addons[ $module_id ] = array();

			$connected_addons = forminator_get_addons_instance_connected_with_module( $module_id, $module_slug );

			foreach ( $connected_addons as $connected_addon ) {
				try {
					$module_hooks = $connected_addon->get_addon_hooks( $module_id, $module_slug );
					if ( $module_hooks instanceof Forminator_Integration_Hooks ) {
						self::$connected_addons[ $module_id ][] = $connected_addon;
					}
				} catch ( Exception $e ) {
					forminator_addon_maybe_log( $connected_addon->get_slug(), 'failed to get_addon_hooks', $e->getMessage() );
				}
			}
		}

		return self::$connected_addons[ $module_id ];
	}

	/**
	 * Attach hooks for delete entry on connected addons
	 *
	 * @param int                         $form_id Form Id.
	 * @param Forminator_Form_Entry_Model $entry_model Form entry model.
	 *
	 * @since 1.1
	 * @throws Exception When there is an error.
	 */
	public static function attach_addons_on_before_delete_entry( $form_id, Forminator_Form_Entry_Model $entry_model ) {
		$module_slug = 'form';
		if ( ! empty( $entry_model->entry_type ) && 'poll' === $entry_model->entry_type ) {
			$module_slug = 'poll';
		} elseif ( ! empty( $entry_model->entry_type ) && 'quizzes' === $entry_model->entry_type ) {
			$module_slug = 'quiz';
		}
		$connected_addons = self::get_connected_addons( $form_id, $module_slug );

		foreach ( $connected_addons as $connected_addon ) {
			try {
				$method = "get_addon_{$module_slug}_hooks";
				if ( ! method_exists( $connected_addon, $method ) ) {
					throw new Exception( 'Method ' . $method . ' doesn\'t exist.' );
				}
				$module_hooks    = $connected_addon->$method( $form_id );
				$addon_meta_data = forminator_find_addon_meta_data_from_entry_model( $connected_addon, $entry_model );
				$module_hooks->on_before_delete_entry( $entry_model, $addon_meta_data );
			} catch ( Exception $e ) {
				forminator_addon_maybe_log( $connected_addon->get_slug(), 'failed to on_before_delete_entry', $e->getMessage() );
			}
		}
	}

	/**
	 * Get entries by email
	 *
	 * @since 1.0.6
	 *
	 * @param string $email Email.
	 *
	 * @return array
	 */
	public static function get_custom_form_entry_ids_by_email( $email ) {
		global $wpdb;
		$meta_table_name = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY_META );
		$sql             = "SELECT m.entry_id AS entry_id
							FROM {$meta_table_name} m
							WHERE (m.meta_key LIKE %s OR m.meta_key LIKE %s)
							AND m.meta_value = %s
							GROUP BY m.entry_id";

		$sql       = $wpdb->prepare(
			$sql, // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->esc_like( 'email-' ) . '%',
			$wpdb->esc_like( 'text-' ) . '%',
			$email
		);
		$entry_ids = $wpdb->get_col( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery

		return $entry_ids;
	}

	/**
	 * Get entries older than $date_created
	 *
	 * @param string $date_created Created date.
	 * @param string $entry_type Entry type.
	 * @param int    $id Id.
	 * @param bool   $is_draft Is draft.
	 *
	 * @return array
	 * @since 1.0.6
	 */
	public static function get_older_entry_ids( $date_created, $entry_type = '', $id = 0, $is_draft = false ) {
		global $wpdb;
		$where = '';
		if ( $entry_type ) {
			$where .= $wpdb->prepare( ' AND e.entry_type = %s', $entry_type );
		}
		if ( $id ) {
			$where .= $wpdb->prepare( ' AND e.form_id = %d', $id );
		}

		// wpdb prepare needs something to substitute or else it will throw an error.
		if ( ! $is_draft ) {
			$where .= $wpdb->prepare( ' AND ( e.draft_id IS NULL OR e.draft_id = %s )', '' );
		} else {
			$where .= $wpdb->prepare( ' AND e.draft_id IS NOT NULL AND e.draft_id != %s', '' );
		}

		$entry_table_name = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY );
		$sql              = "SELECT e.entry_id AS entry_id
							FROM {$entry_table_name} e
							WHERE e.date_created < %s {$where}";

		$sql = $wpdb->prepare(
			$sql, // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			esc_sql( $date_created )
		);

		$entry_ids = $wpdb->get_col( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery

		return $entry_ids;
	}

	/**
	 * Get entries newer than $date_created
	 *
	 * @param string $entry_type Entry type.
	 * @param string $date_created Created date.
	 *
	 * @return array
	 * @since 1.5.3
	 */
	public static function get_newer_entry_ids( $entry_type, $date_created ) {
		global $wpdb;
		$entry_table_name = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY );
		$sql              = "SELECT e.entry_id AS entry_id
							FROM {$entry_table_name} e
							WHERE e.entry_type = %s
							AND e.date_created > %s";

		$sql = $wpdb->prepare(
			$sql, // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$entry_type,
			esc_sql( $date_created )
		);

		$entry_ids = $wpdb->get_col( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery

		return $entry_ids;
	}

	/**
	 * Get entries newer than $date_created
	 *
	 * @param string $entry_type Entry type.
	 *
	 * @return array|object
	 * @since 1.5.3
	 */
	public static function get_most_entry( $entry_type ) {
		global $wpdb;
		$entry_table_name = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY );
		$sql              = "SELECT form_id, count(*) entry_count
							FROM {$entry_table_name}
							WHERE entry_type = %s
							GROUP BY form_id ORDER BY entry_count DESC LIMIT 1";

		$most_entry = $wpdb->get_row( $wpdb->prepare( $sql, $entry_type ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery -- false positive

		return $most_entry;
	}

	/**
	 * Get entries newer than $date_created of form_id
	 *
	 * @param int    $form_id Form Id.
	 * @param string $date_created Created date.
	 *
	 * @return array
	 * @since 1.5.3
	 */
	public static function get_newer_entry_ids_of_form_id( $form_id, $date_created ) {
		global $wpdb;
		$entry_table_name = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY );
		$sql              = "SELECT e.entry_id AS entry_id
							FROM {$entry_table_name} e
							WHERE e.form_id = %d
							AND e.date_created > %s";

		$sql = $wpdb->prepare(
			$sql, // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$form_id,
			esc_sql( $date_created )
		);

		$entry_ids = $wpdb->get_col( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery

		return $entry_ids;
	}

	/**
	 * Get entries newer than $date_created of form_id grouped by date_created Day
	 *
	 * @param int    $form_id Form Id.
	 * @param string $date_created Created date.
	 * @param string $date_end End date.
	 *
	 * @return array
	 * @since 1.5.3
	 */
	public static function get_form_latest_entries_count_grouped_by_day( $form_id, $date_created, $date_end = '' ) {
		global $wpdb;
		$end_date         = '';
		$entry_table_name = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY );
		if ( ! empty( $date_end ) ) {
			$date_end  = $date_end . ' 23:59:00';
			$end_date .= $wpdb->prepare( ' AND e.date_created <= %s ', esc_sql( $date_end ) );
		}
		$sql = "SELECT COUNT(e.entry_id) AS entries_amount,
						  	DATE(e.date_created) AS date_created
							FROM {$entry_table_name} e
							WHERE e.form_id = %d
							AND e.date_created > %s
							AND e.status = 'active'
							{$end_date}
							GROUP BY DATE(e.date_created)
							ORDER BY e.date_created DESC";

		$sql = $wpdb->prepare(
			$sql, // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$form_id,
			esc_sql( $date_created )
		);

		$entry_ids = $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery

		return $entry_ids;
	}

	/**
	 * Get entries newer than $date_created of form_id grouped by date_created Day
	 *
	 * @param int    $form_id Form id.
	 * @param string $date_created Created date.
	 *
	 * @return array
	 * @since 1.14
	 */
	public static function get_form_latest_lead_entries_count_grouped_by_day( $form_id, $date_created ) {
		global $wpdb;
		$entry_table_name      = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY );
		$entry_meta_table_name = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY_META );
		$sql                   = "SELECT COUNT(e.entry_id) AS entries_amount,
						  	DATE(e.date_created) AS date_created
							FROM {$entry_table_name} e
							LEFT JOIN {$entry_meta_table_name} m
							ON(e.`entry_id` = m.`entry_id`)
							WHERE e.form_id = %d
							AND e.date_created > %s
							AND e.status = 'active'
							AND m.meta_key = 'skip_form'
							AND m.meta_value = ''
							GROUP BY DATE(e.date_created)
							ORDER BY e.date_created DESC";

		$sql = $wpdb->prepare(
			$sql, // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$form_id,
			esc_sql( $date_created )
		);

		$entry_ids = $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery

		return $entry_ids;
	}

	/**
	 * Update Meta
	 *
	 * @param int         $meta_id Meta Id.
	 * @param string      $meta_key - the meta key.
	 * @param bool|object $default_value - the default value.
	 * @param string      $date_updated Update date.
	 * @param string      $date_created Create date.
	 *
	 * @since 1.0.6
	 * @since 1.5 : add optional `$date_updated` and `$date_created` arguments
	 */
	public function update_meta( $meta_id, $meta_key, $default_value = false, $date_updated = '', $date_created = '' ) {
		global $wpdb;

		$updated_meta = array(
			'entry_id'   => $this->entry_id,
			'meta_key'   => $meta_key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value' => $default_value, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		);

		if ( ! empty( $date_updated ) ) {
			$updated_meta['date_updated'] = $date_updated;
		}

		if ( ! empty( $date_created ) ) {
			$updated_meta['date_created'] = $date_created;
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->update(
			$this->table_meta_name,
			$updated_meta,
			array(
				'meta_id' => $meta_id,
			)
		);
		wp_cache_delete( $this->entry_id, self::FORM_ENTRY_CACHE_GROUP );
		$this->get( $this->entry_id );
	}

	/**
	 * Custom Query entries
	 *
	 * @param array $args Arguments. `form_id` is required to retrieve entries.
	 *
	 * @return Forminator_Form_Entry_Model[] | array
	 * @since 1.5.4
	 *
	 * @since 1.40
	 * @param bool  $get_count If true, returns an array with the keys `count` and `data`. Default: false.
	 */
	public static function query_entries( $args, $get_count = false ) {
		// Check if Form ID is set.
		if ( empty( $args['form_id'] ) ) {
			return array();
		}
		$form_id            = $args['form_id'];
		$entries            = array();
		$total_count        = 0;
		$cache_count_exists = false;
		$cache_data_exists  = false;

		$unique_id = md5( wp_json_encode( $args ) );
		// Cached key.
		$cache_key = 'forminator_query_entries_' . $form_id;
		// Check if cached data exists.
		$cached_data = wp_cache_get( $cache_key, self::FORM_ENTRY_CACHE_GROUP );
		if ( ! is_array( $cached_data ) ) {
			$cached_data = array();
		}
		if ( isset( $cached_data[ $unique_id ] ) ) {
			$cache_data_exists = true;
			if ( ! empty( $cached_data[ $unique_id ] ) && is_array( $cached_data[ $unique_id ] ) ) {
				foreach ( $cached_data[ $unique_id ] as $result ) {
					$entries[] = new Forminator_Form_Entry_Model( $result->entry_id );
				}
			}
			// Return cached entries if count not requested.
			if ( false === $get_count ) {
				return $entries;
			}
		}
		if ( true === $get_count ) {
			// Convert args array to a unique key for count.
			$count_args  = $args;
			$remove_keys = array( 'order_by', 'order', 'offset', 'per_page' );
			// Remove unused keys for count query.
			foreach ( $remove_keys as $remove_key ) {
				if ( isset( $count_args[ $remove_key ] ) ) {
					unset( $count_args[ $remove_key ] );
				}
			}
			$unique_count_id = md5( wp_json_encode( $count_args ) );
			$count_cache_key = 'forminator_query_entries_count_' . $form_id;
			// Check if cached data exists.
			$cached_count = wp_cache_get( $count_cache_key, self::FORM_ENTRY_CACHE_GROUP );
			if ( ! is_array( $cached_count ) ) {
				$cached_count = array();
			}
			if ( isset( $cached_count[ $unique_count_id ] ) ) {
				$cache_count_exists = true;
				$total_count        = $cached_count[ $unique_count_id ];
			}
		}
		// Return cached data if both count and data cache exists.
		if ( true === $cache_data_exists && true === $cache_count_exists ) {
			return array(
				'count' => $total_count,
				'data'  => $entries,
			);
		}

		global $wpdb;

		/**
		 * $args
		 * [
		 *  form_id => X,
		 *  date_created=> array(),
		 *  search = '',
		 *  min_id =>
		 *  max_id =>
		 *  orderby => 'x',
		 *  order => 'DESC',
		 *  per_page => '10'
		 *  offset => 0
		 * ]
		 */

		if ( ! isset( $args['per_page'] ) ) {
			$args['per_page'] = 10;
		}

		if ( ! isset( $args['offset'] ) ) {
			$args['offset'] = 0;
		}

		if ( ! isset( $args['order'] ) ) {
			$args['order'] = 'DESC';
		}

		$entries_table_name      = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY );
		$entries_meta_table_name = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY_META );

		// Building where.
		$where = 'WHERE 1=1';
		// exclude Addon meta.
		$where .= $wpdb->prepare( ' AND metas.meta_key NOT LIKE %s', $wpdb->esc_like( 'forminator_addon_' ) . '%' );

		if ( isset( $args['form_id'] ) ) {
			$where .= $wpdb->prepare( ' AND entries.form_id = %d', esc_sql( $args['form_id'] ) );
		}

		if ( isset( $args['is_spam'] ) ) {
			$where .= $wpdb->prepare( ' AND entries.is_spam = %s', esc_sql( $args['is_spam'] ) );
		}

		// Prefer status filter when present. Supports string or array values.
		if ( ! empty( $args['status'] ) ) {
			if ( is_array( $args['status'] ) ) {
				$placeholders = implode( ',', array_fill( 0, count( $args['status'] ), '%s' ) );
				$in           = $wpdb->prepare( $placeholders, array_map( 'esc_sql', $args['status'] ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$where       .= " AND entries.status IN ( {$in} )";
			} else {
				$where .= $wpdb->prepare( ' AND entries.status = %s', esc_sql( $args['status'] ) );
			}
		}

		if ( isset( $args['date_created'] ) ) {
			$date_created = $args['date_created'];
			if ( is_array( $date_created ) && isset( $date_created[0] ) && isset( $date_created[1] ) ) {
				// hack to before nextday.
				// https://app.asana.com/0/385581670491499/864371485201331/f.
				$date_created[1] = $date_created[1] . ' 23:59:00';
				$where          .= $wpdb->prepare( ' AND ( entries.date_created >= %s AND entries.date_created <= %s )', esc_sql( $date_created[0] ), esc_sql( $date_created[1] ) );
			}
		}

		if ( isset( $args['user_status'] ) ) {
			require_once __DIR__ . '/../modules/custom-forms/user/class-forminator-cform-user-signups.php';
			Forminator_CForm_User_Signups::prep_signups_functionality();
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery
			$ids = $wpdb->get_col( "SELECT entries.entry_id FROM {$entries_meta_table_name} entries INNER JOIN {$wpdb->base_prefix}signups AS `signups` ON (entries.meta_value = `signups`.activation_key) WHERE entries.meta_key='activation_key' AND `signups`.active = 0" );
			$not = 'approved' === $args['user_status'] ? ' NOT' : '';
			if ( $ids ) {
				$where .= " AND entries.entry_id {$not} IN(" . implode( ', ', $ids ) . ')';
			}
		}

		if ( isset( $args['search'] ) ) {
			$where .= $wpdb->prepare( ' AND metas.meta_value LIKE %s', '%' . $wpdb->esc_like( $args['search'] ) . '%' );
		}

		if ( isset( $args['min_id'] ) ) {
			$where .= $wpdb->prepare( ' AND entries.entry_id >= %d', esc_sql( $args['min_id'] ) );
		}

		if ( isset( $args['max_id'] ) ) {
			$where .= $wpdb->prepare( ' AND entries.entry_id <= %d', esc_sql( $args['max_id'] ) );
		}

		if ( isset( $args['entry_status'] ) && 'completed' === $args['entry_status'] ) {
			$where .= " AND entries.status = 'active'";
		}

		if ( isset( $args['entry_status'] ) && 'draft' === $args['entry_status'] ) {
			$where .= " AND entries.status = 'draft'";
		}

		if ( isset( $args['entry_status'] ) && 'abandoned' === $args['entry_status'] ) {
			$where .= " AND entries.status = 'abandoned'";
		}

		/**
		 * Filter where query to be used on query-ing entries
		 *
		 * @param string $where
		 * @param array $args
		 *
		 * @since 1.5.4
		 */
		$where = apply_filters( 'forminator_query_entries_where', $where, $args );

		// group.
		$group_by = 'GROUP BY entries.entry_id';

		/**
		 * Filter GROUP BY query to be used on query-ing entries
		 *
		 * @param string $group_by
		 * @param array $args
		 *
		 * @since 1.5.4
		 */
		$group_by = apply_filters( 'forminator_query_entries_group_by', $group_by, $args );

		// order.
		$order_by = 'ORDER BY entries.entry_id';
		$order    = 'DESC';
		if ( isset( $args['order_by'] ) ) {
			$valid_order_bys = array(
				'entries.date_created',
				'entries.entry_id',
			);
			if ( in_array( $args['order_by'], $valid_order_bys, true ) ) {
				$order_by = 'ORDER BY ' . $args['order_by'];
			}

			/**
			 * Filter ORDER BY query to be used on query-ing entries
			 *
			 * @param string $order_by
			 * @param array $args
			 *
			 * @since 1.5.4
			 */
			$order_by = apply_filters( 'forminator_query_entries_order_by', $order_by, $args );

			if ( isset( $args['order'] ) && in_array( strtoupper( $args['order'] ), array( 'DESC', 'ASC' ), true ) ) {
				$order = $args['order'];
			}

			/**
			 * Filter order (DESC/ASC) query to be used on query-ing entries
			 *
			 * @param string $order
			 * @param array $args
			 *
			 * @since 1.5.4
			 */
			$order = apply_filters( 'forminator_query_entries_order', $order, $args );
		}

		// limit.
		$limit = $wpdb->prepare( 'LIMIT %d, %d', esc_sql( $args['offset'] ), esc_sql( $args['per_page'] ) );

		/**
		 * Filter LIMIT query to be used on query-ing entries
		 *
		 * @param string $limit
		 * @param array $args
		 *
		 * @since 1.5.4
		 */
		$limit = apply_filters( 'forminator_query_entries_limit', $limit, $args );

		if ( true === $get_count && false === $cache_count_exists ) {
			// sql count.
			$sql_count
				= "SELECT count(DISTINCT entries.entry_id) as total_entries
					FROM
					  {$entries_table_name} AS entries
					  INNER JOIN {$entries_meta_table_name} AS metas
					ON (entries.entry_id = metas.entry_id)
					{$where}
					";

			$sql_count   = apply_filters( 'forminator_query_entries_sql_count', $sql_count, $args );
			$total_count = intval( $wpdb->get_var( $sql_count ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery

			$cached_count[ $unique_count_id ] = $total_count;
			// Store the count in the cache.
			wp_cache_set( $count_cache_key, $cached_count, self::FORM_ENTRY_CACHE_GROUP );
		}

		if ( false === $cache_data_exists ) {
			// sql.
			$sql
				= "SELECT entries.entry_id AS entry_id
				FROM
  				{$entries_table_name} AS entries
  				INNER JOIN {$entries_meta_table_name} AS metas
    			ON (entries.entry_id = metas.entry_id)
    			{$where}
    			{$group_by}
    			{$order_by} {$order}
    			{$limit}
    			";

			$sql     = apply_filters( 'forminator_query_entries_sql', $sql, $args );
			$results = $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery

			$cached_data[ $unique_id ] = $results;
			// Store the result in the cache.
			wp_cache_set( $cache_key, $cached_data, self::FORM_ENTRY_CACHE_GROUP );

			foreach ( $results as $result ) {
				$entries[] = new Forminator_Form_Entry_Model( $result->entry_id );
			}
		}

		if ( true === $get_count ) {
			return array(
				'count' => $total_count,
				'data'  => $entries,
			);
		}

		return $entries;
	}

	/**
	 * Is option limit reached
	 *
	 * @param int    $module_id - Module ID.
	 * @param string $field_name - Field name.
	 * @param string $field_type - Field type.
	 * @param array  $option Option settings.
	 * @param bool   $freeze Optional. Should it save transient option for the current request or not. False by default.
	 * @return bool
	 */
	public static function is_option_limit_reached( $module_id, $field_name, $field_type, $option, $freeze = false ) {
		if ( empty( $option['limit'] ) ) {
			return false;
		}
		$entries = self::select_count_entries_by_meta_field( $module_id, $field_name, $option['value'], $option['label'], $field_type );
		if ( $option['limit'] <= $entries ) {
			return true;
		}

		if ( $freeze ) {
			/**
			 * Filter the time interval (in seconds) after which transient option for limited Select Option for submissions in process will be expired.
			 *
			 * @param int    $seconds Second amount. 10 by default.
			 * @param string $module_id Module ID.
			 */
			$expiration = apply_filters( 'forminator_form_select_option_limit_interval', 10, $module_id );

			do {
				$transient_key = 'forminator_select_option_limit_in_process' . $module_id . $field_name . $option['value'] . '_' . $entries;
				$is_set        = set_transient( $transient_key, 1, $expiration );
			} while ( ! $is_set && $entries++ );

			add_filter(
				'forminator_submission_success',
				function ( $response ) use ( $transient_key ) {
					delete_transient( $transient_key );
					return $response;
				}
			);
			add_filter(
				'forminator_submission_error',
				function ( $response ) use ( $transient_key ) {
					delete_transient( $transient_key );
					return $response;
				}
			);

			if ( $option['limit'] <= $entries ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Count entries of form select key and value
	 *
	 * @param int    $form_id - the form id.
	 * @param string $field_name - the field name.
	 * @param string $field_value - the field value.
	 * @param string $field_label - the field label.
	 * @param string $option_type - Option type (multiselect|single).
	 *
	 * @return int - total entries
	 * @since 1.7
	 */
	public static function select_count_entries_by_meta_field( $form_id, $field_name, $field_value, $field_label, $option_type = 'single' ) {
		global $wpdb;

		if ( 'single' === $option_type ) {
			$condition = $wpdb->prepare( ' AND ( m.`meta_value` = %s OR m.`meta_value` = %s )', esc_sql( $field_value ), esc_sql( $field_label ) );
		} else {
			// todo: change this condition to check if it's multiple one - do this, otherwise do the previous code block.
			$condition = $wpdb->prepare(
				' AND ( m.`meta_value` LIKE %s OR m.`meta_value` LIKE %s )',
				'%' . $wpdb->esc_like( $field_value ) . '%',
				'%' . $wpdb->esc_like( $field_label ) . '%'
			);
		}

		$table_name       = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY_META );
		$entry_table_name = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY );
		$sql              = "SELECT count(m.`meta_id`) FROM {$table_name} m
								LEFT JOIN {$entry_table_name} e ON(e.`entry_id` = m.`entry_id`)
								WHERE e.`form_id` = %d
								AND m.`meta_key` = '%s'
								{$condition}
								AND e.`status` = 'active'";
		$entries          = $wpdb->get_var( $wpdb->prepare( $sql, $form_id, esc_sql( $field_name ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery

		if ( ! $entries || is_wp_error( $entries ) ) {
			$entries = 0;
		}

		return (int) $entries;
	}

	/**
	 * Load entry by draft_id
	 *
	 * @param int $draft_id - the draft id.
	 *
	 * @return bool|mixed
	 * @since 1.17.0
	 */
	public function get_entry_id_by_draft_id( $draft_id ) {
		global $wpdb;

		$table_name = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY );
		$sql        = "SELECT `entry_id` FROM {$table_name} WHERE `draft_id` = %s";
		$entry      = $wpdb->get_row( $wpdb->prepare( $sql, $draft_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery

		return is_object( $entry ) ? $entry->entry_id : null;
	}

	/**
	 * Delete previous draft
	 *
	 * @param string $previous_draft - draft ID of previously saved draft.
	 *
	 * @since 1.17.0
	 */
	public function delete_previous_draft( $previous_draft ) {
		if ( ! is_null( $previous_draft ) ) {
			$entry_id = $this->get_entry_id_by_draft_id( $previous_draft );
			if ( ! empty( $entry_id ) ) {
				self::delete_by_entry( $entry_id );
			}
		}
	}

	/**
	 * Get entries with filters
	 *
	 * @param int    $form_id Form Id.
	 * @param string $start_date Start date.
	 * @param string $end_date End date.
	 * @param string $status Entries status.
	 *
	 * @return int
	 */
	public static function count_report_entries( $form_id, $start_date = '', $end_date = '', $status = 'active' ) {
		// Cached key.
		$cache_key   = 'forminator_count_report_entries_' . $form_id;
		$cached_data = array();
		$unique_key  = md5( wp_json_encode( array( $start_date, $end_date, $status ) ) );
		// Get cached data.
		$cached_data = wp_cache_get( $cache_key, self::FORM_ENTRY_CACHE_GROUP );
		if ( ! is_array( $cached_data ) ) {
			$cached_data = array();
		}
		if ( isset( $cached_data[ $unique_key ] ) ) {
			return $cached_data[ $unique_key ];
		}

		global $wpdb;
		$entry_count             = 0;
		$where                   = 'entries.`form_id` = %d AND entries.`status` = %s';
		$table_name              = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY );
		$entries_meta_table_name = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY_META );
		if ( ! empty( $start_date ) && ! empty( $end_date ) ) {
			$end_date = $end_date . ' 23:59:00';
			$where   .= $wpdb->prepare( ' AND ( entries.date_created >= %s AND entries.date_created <= %s )', esc_sql( $start_date ), esc_sql( $end_date ) );
		}
		$sql    = "SELECT count(DISTINCT entries.`entry_id`) entry_count FROM {$table_name} entries
						LEFT JOIN {$entries_meta_table_name} AS metas
						ON (entries.entry_id = metas.entry_id)
						WHERE {$where}";
		$result = $wpdb->get_var( $wpdb->prepare( $sql, $form_id, $status ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery -- false positive

		if ( ! empty( $result ) ) {
			$entry_count = $result;
		}

		$cached_data[ $unique_key ] = $entry_count;
		// Store the result in the cache.
		wp_cache_set( $cache_key, $cached_data, self::FORM_ENTRY_CACHE_GROUP );

		return $entry_count;
	}

	/**
	 * Payment array
	 *
	 * @param int    $form_id Form Id.
	 * @param string $start_date Start date.
	 * @param string $end_date End date.
	 *
	 * @return array
	 */
	public static function payment_amount( $form_id, $start_date = '', $end_date = '' ) {
		global $wpdb;
		$where            = '';
		$table_name       = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY_META );
		$entry_table_name = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY );

		if ( ! empty( $start_date ) && ! empty( $end_date ) ) {
			$end_date = $end_date . ' 23:59:00';
			$where   .= $wpdb->prepare( ' AND ( e.date_created >= %s AND e.date_created <= %s )', esc_sql( $start_date ), esc_sql( $end_date ) );
		}

		$sql = "SELECT m.meta_key, m.meta_value, e.date_created
			FROM {$table_name} m
			LEFT JOIN {$entry_table_name} e
			ON (m.entry_id = e.entry_id)
			WHERE e.form_id = %d
			AND ( m.meta_key = 'stripe-1' || m.meta_key = 'stripe-ocs-1' || m.meta_key = 'paypal-1' )
			AND m.meta_value LIKE '%4:\"mode\";s:4:\"live\"%'{$where}";

		$results = $wpdb->get_results( $wpdb->prepare( $sql, $form_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery -- false positive

		return $results;
	}

	/**
	 * Addon array
	 *
	 * @param int    $form_id Form Id.
	 * @param string $data_key Meta key name.
	 * @param string $start_date Start date.
	 * @param string $end_date End date.
	 *
	 * @return int
	 */
	public static function addons_data( $form_id, $data_key, $start_date = '', $end_date = '' ) {
		global $wpdb;
		$where            = '';
		$table_name       = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY_META );
		$entry_table_name = Forminator_Database_Tables::get_table_name( Forminator_Database_Tables::FORM_ENTRY );

		if ( ! empty( $start_date ) && ! empty( $end_date ) ) {
			$end_date = $end_date . ' 23:59:00';
			$where   .= $wpdb->prepare( ' AND ( e.date_created >= %s AND e.date_created <= %s )', esc_sql( $start_date ), esc_sql( $end_date ) );
		}

		$sql   = "SELECT count(m.entry_id)
			FROM {$table_name} m
			LEFT JOIN {$entry_table_name} e
			ON (m.entry_id = e.entry_id)
			WHERE e.form_id = %d
			AND ( m.meta_key = %s )
			AND m.meta_value LIKE '%:\"is_sent\";b:1;%'
			{$where}";
		$count = $wpdb->get_var( $wpdb->prepare( $sql, $form_id, $data_key ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery -- false positive

		return $count;
	}

	/**
	 * Delete form cache
	 *
	 * @param int $form_id Form ID.
	 *
	 * @return void
	 */
	public static function delete_form_entry_cache( int $form_id ): void {
		// Delete cache for form count entries of active status.
		wp_cache_delete( 'forminator_count_entries_of_active_status_' . $form_id, self::FORM_COUNT_CACHE_GROUP );

		// Delete cache for form count entries of all statuses.
		wp_cache_delete( 'forminator_count_entries_of_all_status_' . $form_id, self::FORM_COUNT_CACHE_GROUP );

		// Delete cache for payment count.
		wp_cache_delete( 'live_payment_count_' . $form_id, self::FORM_COUNT_CACHE_GROUP );

		if ( 'forminator_polls' === get_post_type( $form_id ) ) {
			// Delete cache for polls entries.
			wp_cache_delete( 'poll_entries_' . $form_id, self::FORM_ENTRY_CACHE_GROUP );

			// Delete cache for poll count.
			wp_cache_delete( 'forminator_count_polls_with_extra_' . $form_id, self::FORM_ENTRY_CACHE_GROUP );
		}

		// Delete the cache for the form entries query.
		self::delete_form_entries_query_cache( $form_id );

		// Delete the cache for the report count.
		wp_cache_delete( 'forminator_count_report_entries_' . $form_id, self::FORM_ENTRY_CACHE_GROUP );

		// Delete the cache for counting user entries.
		wp_cache_delete( 'forminator_count_user_entries_' . $form_id, self::FORM_ENTRY_CACHE_GROUP );

		// Delete the cache for the leads count.
		wp_cache_delete( 'forminator_count_leads_' . $form_id, self::FORM_ENTRY_CACHE_GROUP );
	}

	/**
	 * Delete form entries query cache
	 *
	 * @param int $form_id Form Id.
	 * @return void
	 */
	public static function delete_form_entries_query_cache( $form_id ): void {
		// Delete the cache for entries.
		wp_cache_delete( 'forminator_query_entries_' . $form_id, self::FORM_ENTRY_CACHE_GROUP );

		// Delete the cache for entries count.
		wp_cache_delete( 'forminator_query_entries_count_' . $form_id, self::FORM_ENTRY_CACHE_GROUP );
	}
}
