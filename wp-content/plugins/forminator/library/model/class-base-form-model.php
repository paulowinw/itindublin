<?php
/**
 * The Forminator_Base_Form_Model class.
 *
 * @package Forminator
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Author: Hoang Ngo
 *
 * @property  string $status
 */
abstract class Forminator_Base_Form_Model {
	const META_KEY      = 'forminator_form_meta';
	const TEMP_META_KEY = 'forminator_temp_form_meta';
	/**
	 * Form ID
	 *
	 * @var int
	 */
	public $id;
	/**
	 * Form name
	 *
	 * @var string
	 */
	public $name;

	/**
	 * Client Id
	 *
	 * @var string
	 */
	public $client_id;

	/**
	 * Status
	 *
	 * @var string
	 */
	public $status;

	/**
	 * Contain fields of this form
	 *
	 * @var Forminator_Form_Field_Model[]
	 */
	public $fields = array();

	/**
	 * Contain real fields of this form excluding 'html', 'page-break', 'section' and so on.
	 *
	 * @var Forminator_Form_Field_Model[]
	 */
	private $real_fields;

	/**
	 * Form settings
	 *
	 * @var array
	 */
	public $settings = array();

	/**
	 * Form notification
	 *
	 * @var array
	 */
	public $notifications = array();

	/**
	 * Form behaviors
	 *
	 * @var array
	 */
	public $behaviors = array();

	/**
	 * Integration Conditions
	 *
	 * @var array
	 */
	public $integration_conditions = array();

	/**
	 * WP_Post
	 *
	 * @var WP_Post
	 */
	public $raw;
	/**
	 * This post type
	 *
	 * @var string
	 */
	protected $post_type;

	const STATUS_PUBLISH = 'publish';
	const STATUS_DRAFT   = 'draft';

	/**
	 * Save form
	 *
	 * @param bool $clone_form Clone.
	 *
	 * @return mixed
	 * @since 1.0
	 */
	public function save( $clone_form = false ) {
		// todo use save_post for saving the form and update_post_meta for saving fields.
		// prepare the data.
		$maps      = array_merge( $this->get_default_maps(), $this->get_maps() );
		$post_data = array();
		$meta_data = array();
		if ( ! empty( $maps ) ) {
			foreach ( $maps as $map ) {
				$attribute = $map['property'];
				if ( 'post' === $map['type'] ) {
					if ( isset( $this->{$attribute} ) ) {
						$post_data[ $map['field'] ] = $this->{$attribute};
					} elseif ( isset( $map['default'] ) ) {
						$post_data[ $map['field'] ] = $map['default'];
					}
				} elseif ( 'fields' === $map['field'] ) {
					$meta_data[ $map['field'] ] = forminator_sort_fields_with_groups( $this->get_fields_as_array() );
				} else {
					$meta_data[ $map['field'] ] = $this->{$attribute};
				}
			}
		}
		$validate = forminator_validate_registration_form_settings( $meta_data['settings'] );
		if ( is_wp_error( $validate ) ) {
			return $validate;
		}

		$post_data['post_type'] = $this->post_type;

		// storing.
		if ( is_null( $this->id ) ) {
			$id = wp_insert_post( $post_data );
		} else {
			$id = wp_update_post( $post_data );
		}

		// If cloned we have to update the fromID.
		if ( $clone_form ) {
			$meta_data['settings']['form_id'] = $id;
		}

		update_post_meta( $id, self::META_KEY, $meta_data );

		return $id;
	}

	/**
	 * Save temporary settings
	 *
	 * @param int   $id Module ID.
	 * @param array $module_data Module data.
	 *
	 * @return bool
	 */
	public static function save_temp_settings( int $id, array $module_data ): bool {
		unset( $module_data['pdfs'] ); // Remove PDFs from temp settings.
		$response = update_post_meta( $id, self::TEMP_META_KEY, $module_data );
		// It can be false, if new data is the same as old data.
		if ( ! $response && self::get_temp_settings( $id ) === $module_data ) {
			return true;
		}

		return (bool) $response;
	}

	/**
	 * Get temporary settings
	 *
	 * @param int $id Module ID.
	 *
	 * @return array|bool
	 */
	public static function get_temp_settings( int $id ) {
		$temp_meta = get_post_meta( $id, self::TEMP_META_KEY, true );

		if ( ! empty( $temp_meta ) && is_array( $temp_meta ) ) {
			return $temp_meta;
		}

		return false;
	}

	/**
	 * Remove temporary settings
	 *
	 * @param int $id Module ID.
	 * @return bool
	 */
	public static function remove_temp_settings( int $id ): bool {
		$response = delete_post_meta( $id, self::TEMP_META_KEY );

		return (bool) $response;
	}

	/**
	 * Add temp saved changes to the data
	 *
	 * @param array                           $data Data.
	 * @param Forminator_Base_Form_Model|null $model Model.
	 *
	 * @return array
	 */
	public static function add_saved_changes( $data, $model ) {
		if ( $model ) {
			$data['originalSettings'] = $data['currentForm'];

			$saved_changes = self::get_temp_settings( $model->id );
			// Add saved changes to the data.
			if ( ! empty( $saved_changes ) ) {
				$data['currentForm'] = $saved_changes;
				if ( isset( $data['originalSettings']['pdfs'] ) ) {
					// If PDFs are set in original settings, we need to keep them in the current form.
					$data['currentForm']['pdfs'] = $data['originalSettings']['pdfs'];
				} else {
					// If PDFs are not set in original settings, we need to remove them from the current form.
					unset( $data['currentForm']['pdfs'] );
				}
			}
		}

		return $data;
	}

	/**
	 * Get fields
	 *
	 * @return Forminator_Form_Field_Model[]
	 * @since 1.0
	 */
	public function get_fields() {
		return $this->fields;
	}

	/**
	 * Get real fields - all fields except ignored ones
	 *
	 * @return Forminator_Form_Field_Model[]
	 */
	public function get_real_fields() {
		if ( is_null( $this->real_fields ) ) {
			$fields              = $this->fields;
			$ignored_field_types = Forminator_Form_Entry_Model::ignored_fields();
			foreach ( $fields as $field_index => $field ) {
				$field_array = $field->to_formatted_array();
				if ( empty( $field_array['type'] ) ) {
					continue;
				}
				if ( in_array( $field_array['type'], $ignored_field_types, true ) ) {
					unset( $fields[ $field_index ] );
				}
			}

			$this->real_fields = $fields;
		}

		return $this->real_fields;
	}

	/**
	 * Get filtered fields by group. If group ID is empty - it returns ungrouped fields
	 *
	 * @param string $group_id Group ID.
	 * @return array
	 */
	public function get_grouped_real_fields( $group_id = '' ) {
		$real_fields = $this->get_real_fields();
		$fields      = array_filter(
			$real_fields,
			function ( $value ) use ( $group_id ) {
				return ! $group_id ? empty( $value->parent_group ) : $group_id === $value->parent_group;
			}
		);

		return $fields;
	}

	/**
	 * Get filtered fields by group. If group ID is empty - it returns ungrouped fields
	 *
	 * @param string $group_id Group ID.
	 * @return array
	 */
	public function get_grouped_fields( $group_id = '' ) {
		$fields         = $this->get_fields();
		$grouped_fields = array_filter(
			$fields,
			function ( $value ) use ( $group_id ) {
				return ! $group_id ? empty( $value->parent_group ) : $group_id === $value->parent_group;
			}
		);

		return $grouped_fields;
	}


	/**
	 * Get filtered fields by page break.
	 *
	 * @param string $page_break_id Page break ID.
	 * @return array
	 */
	public function get_page_fields( $page_break_id ) {
		$fields             = $this->get_fields();
		$last_page_break_id = null;
		$page_fields        = array();
		$collecting         = false;
		foreach ( $fields as $field ) {
			if ( 0 === strpos( $field->slug, 'page-break-' ) ) {
				if ( $field->element_id === $page_break_id ) {
					$collecting = true;
				} else {
					$collecting = false;
				}
				$last_page_break_id = $field->element_id;
			} elseif ( $collecting ) {
				if ( ! empty( $field->parent_group ) ) {
					continue;
				}
				// If the field is a group, get its fields.
				if ( 'group' === $field->type ) {
					$group_fields = $this->get_grouped_fields( $field->element_id );
					foreach ( $group_fields as $group_field ) {
						$page_fields[] = $group_field;
					}
					continue;
				}
				$page_fields[] = $field;
			}
		}

		// If it's the last page break id, then the page fields are empty.
		// Because we ignore visibility conditions on the last page.
		if ( $page_break_id === $last_page_break_id ) {
			$page_fields = array();
		}

		return $page_fields;
	}

	/**
	 * Get fields IDs of filtered fields by group
	 *
	 * @param string $group_id Group ID.
	 * @return array
	 */
	public function get_grouped_fields_slugs( $group_id ) {
		$grouped_fields = $this->get_grouped_fields( $group_id );
		$field_slugs    = wp_list_pluck( $grouped_fields, 'slug' );

		return $field_slugs;
	}

	/**
	 * Set var in array
	 *
	 * @param string $property Property.
	 * @param string $name Name.
	 * @param string $array_values Array values.
	 * @param string $sanitize_function custom sanitize function to use, default is sanitize_title.
	 *
	 * @since 1.0
	 * @since 1.2 Add $sanitize_function as optional param
	 */
	public function set_var_in_array( $property, $name, $array_values, $sanitize_function = 'sanitize_title' ) {
		$val = isset( $array_values[ $name ] ) ? $array_values[ $name ] : null;
		if ( is_callable( $sanitize_function ) ) {
			$val = call_user_func_array( $sanitize_function, array( $val ) );
		}
		$this->$property = $val;
	}

	/**
	 * Add field
	 *
	 * @param mixed $field Field.
	 *
	 * @since 1.0
	 */
	public function add_field( $field ) {
		$this->fields[] = $field;
	}

	/**
	 * Get field
	 *
	 * @param string $slug Slug.
	 *
	 * @return Forminator_Form_Field|null
	 * @since 1.0
	 */
	public function get_field( $slug ) {
		// get a field and return as object.
		return isset( $this->fields[ $slug ] ) ? $this->fields[ $slug ] : null;
	}

	/**
	 * Remove field
	 *
	 * @param string $slug Slug.
	 *
	 * @since 1.0
	 */
	public function remove_field( $slug ) {
		unset( $this->fields[ $slug ] );
	}

	/**
	 * Clear fields
	 *
	 * @since 1.0
	 */
	public function clear_fields() {
		$this->fields = array();
	}

	/**
	 * Load model
	 *
	 * @param int   $id Id.
	 * @param mixed $callback Callback function.
	 *
	 * @return bool|$this
	 * @since 1.0
	 */
	public function load( $id, $callback = false ) {
		$post = get_post( $id );

		if ( ! is_object( $post ) ) {
			// If we haven't saved yet, fallback to latest ID and replace the data.
			if ( $callback ) {
				$id   = $this->get_latest_id();
				$post = get_post( $id );

				if ( ! is_object( $post ) ) {
					return false;
				}
			} else {
				return false;
			}
		}

		return $this->_load( $post );
	}

	/**
	 * Load preview
	 *
	 * @param int   $id Id.
	 * @param array $data Data for preview.
	 *
	 * @return bool|Forminator_Base_Form_Model
	 * @since 1.0
	 */
	public function load_preview( $id, $data ) {
		$form_model = $this->load( $id, true );

		// If bool, abort.
		if ( is_bool( $form_model ) ) {
			return false;
		}

		$form_model->clear_fields();
		$form_model->set_var_in_array( 'name', 'formName', $data );

		// build the settings.
		if ( isset( $data['settings'] ) ) {
			$settings             = $data['settings'];
			$form_model->settings = $settings;
		}

		$form_model = static::prepare_data_for_preview( $form_model, $data );

		return $form_model;
	}

	/**
	 * Get relevant module object based on its ID.
	 *
	 * @param int $id Module ID.
	 *
	 * @return boolean|object
	 */
	public static function get_model( $id ) {
		$class = self::get_model_class( $id );
		if ( $class ) {
			$model = $class::model()->load( $id );

			return $model;
		}

		return false;
	}

	/**
	 * Get module class by module ID
	 *
	 * @param int $id Module ID.
	 *
	 * @return boolean|string
	 */
	public static function get_model_class( $id ) {
		$post_type = get_post_type( $id );
		switch ( $post_type ) {
			case 'forminator_forms':
				$class = 'Forminator_Form_Model';
				break;
			case 'forminator_polls':
				$class = 'Forminator_Poll_Model';
				break;
			case 'forminator_quizzes':
				$class = 'Forminator_Quiz_Model';
				break;
			default:
				return false;
		}

		return $class;
	}

	/**
	 * Return latest id for the post_type
	 *
	 * @return int
	 * @since 1.0
	 */
	public function get_latest_id() {
		$id   = 1;
		$args = array(
			'post_type'   => $this->post_type,
			'numberposts' => 1,
			'fields'      => 'ids',
		);

		$post = get_posts( $args );

		if ( isset( $post[0] ) ) {
			$id = $post[0];
		}

		return $id;
	}

	/**
	 * Count all form types
	 *
	 * @param string $status Status.
	 *
	 * @return int
	 * @since 1.0
	 * @since 1.6 add optional param `status`
	 */
	public function count_all( $status = '' ) {
		$count_posts = wp_count_posts( $this->post_type );
		$count_posts = (array) $count_posts;
		if ( ! empty( $status ) ) {
			if ( isset( $count_posts[ $status ] ) ) {
				return $count_posts[ $status ];
			} else {
				return 0;
			}
		} elseif ( 'forminator_forms' === $this->post_type ) {
				unset( $count_posts['leads'] );
				unset( $count_posts['pdf_form'] );
		}

		return array_sum( $count_posts );
	}

	/**
	 * Get entry type
	 *
	 * @return string
	 */
	public function get_entry_type() {
		$post_type = $this->get_post_type();
		switch ( $post_type ) {
			case 'forminator_forms':
				$entry_type = 'custom-forms';
				break;
			case 'forminator_polls':
				$entry_type = 'polls';
				break;
			case 'forminator_quizzes':
				$entry_type = 'quizzes';
				break;
			default:
				$entry_type = '';
				break;
		}

		return $entry_type;
	}

	/**
	 * Get all paginated
	 *
	 * @param int         $current_page Current page.
	 * @param null|int    $per_page Limit per page.
	 * @param string      $status Status.
	 * @param null|int    $pdf_parent_id PDF parent Id.
	 * @param null|string $search Optional search. Added in 1.52.
	 *
	 * @return array
	 * @since 1.5.4 add optional param per_page
	 * @since 1.5.4 add optional param $status
	 *
	 * @since 1.2
	 */
	public function get_all_paged( $current_page = 1, $per_page = null, $status = '', $pdf_parent_id = null, $search = '' ) {
		if ( is_null( $per_page ) ) {
			$per_page = forminator_form_view_per_page();
		}
		$args = array(
			'post_type'      => $this->post_type,
			'post_status'    => 'any',
			'posts_per_page' => $per_page,
			'paged'          => $current_page,
		);

		if ( ! empty( $search ) ) {
			add_filter( 'posts_search', array( $this, 'filter_multi_search' ), 10, 2 );
			$args['s']                       = $search;
			$args['forminator_multi_search'] = true;
		}

		if ( ! empty( $status ) ) {
			$args['post_status'] = $status;
		}

		if ( 'forminator_forms' === $this->post_type ) {
			if ( 'pdf_form' === $status ) {
				$args['meta_key']     = 'forminator_form_meta'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				$args['meta_value']   = '("parent_form_id";s:[0-9]+:"' . $pdf_parent_id . '";)'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				$args['meta_compare'] = 'REGEXP';
			} else {
				$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'     => 'forminator_form_meta',
						'value'   => 'form-type";s:5:"leads"',
						'compare' => 'NOT LIKE',
					),
					array(
						'key'     => 'forminator_form_meta',
						'value'   => '"form-type";s:[0-9]+:"pdf-form"',
						'compare' => 'NOT REGEXP',
					),
				);
			}
		}

		$query = new WP_Query( $args );

		if ( ! empty( $search ) ) {
			remove_filter( 'posts_search', array( $this, 'filter_multi_search' ) );
		}

		$models = array();

		foreach ( $query->posts as $post ) {
			$models[] = $this->_load( $post );
		}

		return array(
			'totalPages'   => $query->max_num_pages,
			'totalRecords' => $query->post_count,
			'models'       => $models,
			'foundPosts'   => $query->found_posts,
		);
	}

	/**
	 * Get all
	 *
	 * @param string $status post_status arg.
	 * @param int    $limit Limit.
	 *
	 * @return array()
	 * @since 1.0
	 * @since 1.6 add `status` in param
	 * @since 1.6 add `limit` in param
	 */
	public function get_all_models( $status = '', $limit = - 1 ) {
		$args = array(
			'post_type'      => $this->post_type,
			'post_status'    => 'any',
			'posts_per_page' => $limit,
		);

		if ( ! empty( $status ) ) {
			$args['post_status'] = $status;
		}
		$query  = new WP_Query( $args );
		$models = array();

		foreach ( $query->posts as $post ) {
			$models[] = $this->_load( $post );
		}

		return array(
			'totalPages'   => $query->max_num_pages,
			'totalRecords' => $query->post_count,
			'models'       => $models,
		);
	}

	/**
	 * Get 3 models with old stripe field
	 *
	 * @return array
	 */
	public function get_models_with_old_stripe() {
		$max_title_length = 50;

		$models = array();
		$data   = $this->get_models( 99, 'publish' );

		foreach ( $data as $model ) {
			if ( $model->get_field( 'stripe-1' ) && ! $model->get_field( 'stripe-ocs-1' ) ) {
				$title = $model->settings['formName'] ?? "Form #{$model->id}";
				$title = mb_strlen( $title ) > $max_title_length ? mb_substr( $title, 0, $max_title_length ) . '...' : $title;

				$models[] = array(
					'id'    => $model->id,
					'title' => $title,
				);
			}
			// Return only 3 forms.
			if ( 3 === count( $models ) ) {
				break;
			}
		}

		return $models;
	}

	/**
	 * Get modules from field
	 *
	 * @param int $id Id.
	 *
	 * @return array
	 * @since 1.9
	 */
	public function get_models_by_field( $id ) {
		$modules = array();
		$data    = $this->get_models( 999 );

		foreach ( $data as $model ) {
			if ( $model->get_field( $id ) ) {
				$modules[] = array(
					'id'      => $model->id,
					'title'   => $model->name,
					'version' => $model->version,
				);
			}
		}

		return $modules;
	}

	/**
	 * Get modules from field id & version
	 *
	 * @param int    $id Id.
	 * @param string $version Version.
	 *
	 * @return array
	 * @since 1.9
	 */
	public function get_models_by_field_and_version( $id, $version ) {
		$modules = array();
		$data    = $this->get_models( 999 );

		foreach ( array_filter( $data ) as $model ) {
			if ( $model->get_field( $id ) && version_compare( $model->settings['version'], $version, 'lt' ) ) {
				$modules[] = array(
					'id'      => $model->id,
					'title'   => $model->name,
					'version' => $model->settings['version'],
				);
			}
		}

		return $modules;
	}

	/**
	 * Get Models
	 *
	 * @param int    $total - the total. Defaults to 4.
	 * @param string $status Status.
	 *
	 * @return array $models
	 * @since 1.6 add `status` as optional param
	 *
	 * @since 1.0
	 */
	public function get_models( $total = 4, $status = '' ) {
		$args = array(
			'post_type'      => $this->post_type,
			'post_status'    => 'any',
			'posts_per_page' => $total,
			'order'          => 'DESC',
		);
		if ( ! empty( $status ) ) {
			$args['post_status'] = $status;
		}
		$query  = new WP_Query( $args );
		$models = array();

		foreach ( $query->posts as $post ) {
			$models[] = $this->_load( $post );
		}

		return $models;
	}

	/**
	 * Load form
	 *
	 * @param WP_Post $post Post.
	 *
	 * @return mixed
	 * @since 1.0
	 */
	private function _load( $post ) { // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
		if ( $this->post_type === $post->post_type ) {
			$class         = get_class( $this );
			$object        = new $class();
			$meta          = get_post_meta( $post->ID, self::META_KEY, true );
			$fields        = ! empty( $meta['fields'] ) ? $meta['fields'] : array();
			$form_settings = array(
				'version'                    => '1.0',
				'cform-section-border-color' => '#E9E9E9',
			);
			if ( $fields ) {
				$meta['fields'] = static::disable_fields( $fields );
			}
			$maps = array_merge( $this->get_default_maps(), $this->get_maps() );

			// Update version from form settings.
			if ( isset( $meta['settings']['version'] ) ) {
				$form_settings['version'] = $meta['settings']['version'];
			}

			// Update section border color.
			if ( isset( $meta['settings']['cform-section-border-color'] ) ) {
				$form_settings['cform-section-border-color'] = $meta['settings']['cform-section-border-color'];
			}

			// Decode HTML entities.
			$decode_html_keys = array( 'notifications', 'behaviors', 'integration_conditions' );
			foreach ( $decode_html_keys as $decode_html_key ) {
				if ( ! empty( $meta[ $decode_html_key ] ) ) {
					foreach ( $meta[ $decode_html_key ] as $key => $meta_data ) {
						if ( ! empty( $meta_data['conditions'] ) ) {
							$meta[ $decode_html_key ][ $key ]['conditions'] = forminator_decode_html_entity( $meta_data['conditions'] );
						}
						if ( 'notifications' === $decode_html_key && ! empty( $meta_data['routing'] ) ) {
							$meta[ $decode_html_key ][ $key ]['routing'] = forminator_decode_html_entity( $meta_data['routing'] );
						}
					}
				}
			}
			if ( ! empty( $meta['settings']['user_role'] ) ) {
				$meta['settings']['user_role'] = forminator_decode_html_entity( $meta['settings']['user_role'] );
			}

			if ( ! empty( $maps ) ) {
				foreach ( $maps as $map ) {
					$attribute = $map['property'];
					if ( 'post' === $map['type'] ) {
						$att = $map['field'];
						if ( 'pdf_form' === $post->post_status && 'name' === $attribute ) {
							// To support non-English characters in the file name.
							$object->{$attribute} = urldecode( $post->{$att} );
						} else {
							$object->{$attribute} = $post->{$att};
						}
					} elseif ( ! empty( $meta['fields'] ) && 'fields' === $map['field'] ) {
							$meta['fields'] = forminator_decode_html_entity( $meta['fields'] );
							$password_count = 0;
						foreach ( $meta['fields'] as $field_data ) {
							// Prevent creating empty wrappers.
							if ( isset( $field_data['type'] ) ) {

								if ( 'honeypot' === $field_data['type'] ) {
									continue;
								}

								if ( 'password' === $field_data['type'] ) {
									if ( $password_count >= 1 ) {
										continue;
									} else {
										++$password_count;
									}
								}
							}
							$field          = new Forminator_Form_Field_Model( $form_settings );
							$field->form_id = $post->ID;
							$field->slug    = $field_data['id'];
							unset( $field_data['id'] );
							$field->import( $field_data );
							$object->add_field( $field );
						}
					} elseif ( isset( $meta[ $map['field'] ] ) ) {
								$object->{$attribute} = $meta[ $map['field'] ];
					}
				}
			}

			$form_settings = $object->settings;
			if ( is_array( $form_settings ) && ! isset( $form_settings['form_id'] ) ) {
				$form_settings['form_id'] = $object->id;
			}

			// Migrate settings Custom Form.
			if ( 'forminator_forms' === $this->post_type ) {
				$form_settings         = self::validate_registration_fields_mapping( $form_settings, $fields );
				$object->settings      = Forminator_Migration::migrate_custom_form_settings( $form_settings, $fields );
				$object->notifications = Forminator_Migration::migrate_custom_form_notifications( $object->notifications, $form_settings, $meta );
			}

			// Migrate settings Polls.
			if ( 'forminator_polls' === $this->post_type ) {
				$object->settings = Forminator_Migration::migrate_polls_settings( $form_settings );
			}

			// Migrate settings Polls.
			if ( 'forminator_quizzes' === $this->post_type ) {
				$object->settings      = Forminator_Migration::migrate_quizzes_settings( $form_settings );
				$object->notifications = Forminator_Migration::migrate_quizzes_notifications( $object->notifications, $form_settings, $meta );
			}

			$object->raw = $post;

			return $object;
		}

		return false;
	}

	/**
	 * Disable fields
	 *
	 * @param array $fields Fields.
	 * @return array
	 */
	protected static function disable_fields( $fields ) {
		return $fields;
	}

	/**
	 * Validate registration fields mapping for Registration forms
	 * If the field is removed - replace it to the first field in the list
	 *
	 * @param array $form_settings Form settings.
	 * @param array $fields Form fields.
	 *
	 * @return array
	 */
	private static function validate_registration_fields_mapping( $form_settings, $fields ) {
		$field_ids = wp_list_pluck( $fields, 'id' );
		if ( ! empty( $form_settings['form-type'] ) && 'registration' === $form_settings['form-type'] && ! empty( $field_ids ) ) {
			// Get first field id (not password).
			$i = 0;
			do {
				$first_id = isset( $field_ids[ $i ] ) ? $field_ids[ $i ] : null;
				++$i;
				$is_password = false !== strpos( $first_id, 'password' );
				$go_next     = empty( $first_id ) || $is_password;
			} while ( $go_next );

			foreach ( $form_settings as $key => $value ) {
				if ( ! is_string( $value ) ) {
					continue;
				}
				$value_parts = explode( '-', $value );
				if ( ! $first_id
					|| 'registration-' !== substr( $key, 0, 13 )
					|| '-field' !== substr( $key, - 6 )
					|| 'registration-role-field' === $key
					|| in_array( $value, $field_ids, true )
					// for multiple fields like name, address.
					|| ( 2 < count( $value_parts ) && in_array( $value_parts[0] . '-' . $value_parts[1], $field_ids, true ) )
				) {
					continue;
				}
				if ( 'registration-password-field' === $key ) {
					$form_settings[ $key ] = 'auto';
				} else {
					$form_settings[ $key ] = $first_id;
				}
			}
		}

		return $form_settings;
	}

	/**
	 * Return fields as array
	 *
	 * @return array
	 * @since 1.0
	 */
	public function get_fields_as_array() {
		$arr = array();

		if ( empty( $this->fields ) ) {
			return $arr;
		}

		foreach ( $this->fields as $field ) {
			$arr[] = $field->to_array();
		}

		return $arr;
	}

	/**
	 * Return fields grouped
	 *
	 * @return array
	 * @since 1.0
	 */
	public function get_fields_grouped() {
		$wrappers = array();

		if ( empty( $this->fields ) ) {
			return $wrappers;
		}

		foreach ( $this->fields as $field ) {
			/**
			 * Forminator_Form_Field_Model
			 *
			 * @var Forminator_Form_Field_Model $field */
			if ( strpos( $field->form_id, 'wrapper-' ) === 0 ) {
				$form_id = $field->form_id;
			} else {
				// Backward Compat.
				$form_id = $field->formID; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			}

			if ( ! isset( $wrappers[ $form_id ] ) ) {
				$wrappers[ $form_id ] = array(
					'wrapper_id' => $form_id,
				);
			}
			$field_data                           = $field->to_formatted_array();
			$field_data                           = $this->migrate_payments( $field_data );
			$wrappers[ $form_id ]['parent_group'] = ! empty( $field->parent_group ) ? $field->parent_group : '';
			$wrappers[ $form_id ]['fields'][]     = $field_data;

		}
		$wrappers = array_values( $wrappers );

		return $wrappers;
	}

	/**
	 * Migrate payment fields to new behavior with multi payments
	 *
	 * @param array $field Field.
	 *
	 * @return array
	 * @since 1.15
	 */
	private function migrate_payments( $field ) {
		if ( ! isset( $field['type'] ) || ( 'stripe' !== $field['type'] && 'stripe-ocs' !== $field['type'] ) ) {
			return $field;
		}

		if ( ! isset( $field['payments'] ) ) {
			$type     = isset( $field['amount_type'] ) ? $field['amount_type'] : 'fixed';
			$amount   = isset( $field['amount'] ) ? $field['amount'] : 0;
			$variable = isset( $field['variable'] ) ? $field['variable'] : '';

			$field['payments'][] = array(
				'plan_name'      => 'Plan 1',
				'payment_method' => 'single',
				'amount_type'    => $type,
				'amount'         => $amount,
				'variable'       => $variable,
			);
		}

		return $field;
	}

	/**
	 * Model to array
	 *
	 * @return array
	 * @since 1.0
	 */
	public function to_array() {
		$data = array();
		$maps = array_merge( $this->get_default_maps(), $this->get_maps() );

		if ( empty( $maps ) ) {
			return $data;
		}

		foreach ( $maps as $map ) {
			$property          = $map['property'];
			$data[ $property ] = $this->$property;
		}

		return $data;
	}

	/**
	 * Model to json
	 *
	 * @return mixed|string
	 * @since 1.0
	 */
	public function to_json() {
		$wrappers = array();

		if ( ! empty( $this->fields ) ) {
			foreach ( $this->fields as $field ) {
				$wrappers[] = $field->to_json();
			}
		}

		$settings      = $this->settings;
		$notifications = $this->notifications;
		$data          = array_merge(
			array(
				'wrappers' => array(
					'fields' => $wrappers,
				),
			),
			$settings,
			$notifications
		);
		$ret           = array(
			'formName' => $this->name,
			'data'     => $data,
		);

		return wp_json_encode( $ret );
	}

	/**
	 * In here we will define how we store the properties
	 *
	 * @return array
	 * @since 1.0
	 */
	public function get_default_maps() {
		return array(
			array(
				'type'     => 'post',
				'property' => 'id',
				'field'    => 'ID',
			),
			array(
				'type'     => 'post',
				'property' => 'name',
				'field'    => 'post_title',
			),
			array(
				'type'     => 'post',
				'property' => 'status',
				'field'    => 'post_status',
				'default'  => self::STATUS_PUBLISH,
			),
			array(
				'type'     => 'meta',
				'property' => 'fields',
				'field'    => 'fields',
			),
			array(
				'type'     => 'meta',
				'property' => 'settings',
				'field'    => 'settings',
			),
			array(
				'type'     => 'meta',
				'property' => 'client_id',
				'field'    => 'client_id',
			),
			array(
				'type'     => 'meta',
				'property' => 'integration_conditions',
				'field'    => 'integration_conditions',
			),
			array(
				'type'     => 'meta',
				'property' => 'behaviors',
				'field'    => 'behaviors',
			),
			array(
				'type'     => 'meta',
				'property' => 'notifications',
				'field'    => 'notifications',
			),
		);
	}

	/**
	 * This should be get override by children
	 *
	 * @return array
	 * @since 1.0
	 */
	public function get_maps() {
		return array();
	}

	/**
	 * Return model
	 *
	 * @param string $class_name Class name.
	 *
	 * @return self
	 * @since 1.0
	 */
	public static function model( $class_name = null ) {
		if ( is_null( $class_name ) ) {
			$class_name = static::class;
		}
		$class = new $class_name();

		return $class;
	}

	/**
	 * Get Post Type of cpt
	 *
	 * @return mixed
	 * @since 1.0.5
	 */
	public function get_post_type() {
		return $this->post_type;
	}

	/**
	 * Get export Model
	 *
	 * @return array
	 * @since 1.4
	 * Override-able, but use of hook `forminator_{$module_type}_model_to_exportable_data` encouraged
	 */
	public function to_exportable_data() {

		if ( ! Forminator::is_import_export_feature_enabled() ) {
			return array();
		}

		$hook = 'forminator_' . static::$module_slug . '_model_to_exportable_data';
		if ( Forminator::is_export_integrations_feature_enabled() ) {
			add_filter( $hook, array( $this, 'export_integrations_data' ), 1, 1 );
		}

		$model_id    = $this->id;
		$module_type = static::$module_slug;

		// cleanup form id.
		$post_meta = get_post_meta( $this->id, self::META_KEY, true );
		if ( is_array( $post_meta ) ) {
			if ( isset( $post_meta['settings'] ) && isset( $post_meta['settings'] ) ) {
				if ( isset( $post_meta['settings']['form_id'] ) ) {
					unset( $post_meta['settings']['form_id'] );
				}
				if ( isset( $post_meta['settings']['formID'] ) ) {
					unset( $post_meta['settings']['formID'] );
				}
			}
		}

		$exportable_data = array(
			'type'    => $module_type,
			'data'    => $post_meta,
			'status'  => $this->status,
			'version' => FORMINATOR_VERSION,
		);

		$exportable_data = apply_filters( $hook, $exportable_data, $module_type, $model_id );

		// avoid filter executed on next cycle.
		remove_filter( $hook, array( $this, 'export_integrations_data' ), 1 );

		return $exportable_data;
	}

	/**
	 * Export integrations setting
	 *
	 * @param array $exportable_data Exportable data.
	 *
	 * @return array
	 * @since 1.4
	 */
	public function export_integrations_data( $exportable_data ) {
		$model_id                = $this->id;
		$exportable_integrations = array();

		$connected_addons = forminator_get_addons_instance_connected_with_module( $model_id, static::$module_slug );

		foreach ( $connected_addons as $connected_addon ) {
			try {
				$settings = $connected_addon->get_addon_settings( $model_id, 'form' );
				if ( $settings instanceof Forminator_Integration_Settings ) {
					$exportable_integrations[ $connected_addon->get_slug() ] = $settings->to_exportable_data();
				}
			} catch ( Exception $e ) {
				forminator_addon_maybe_log( $connected_addon->get_slug(), 'failed to get to_exportable_data', $e->getMessage() );
			}
		}

		/**
		 * Filter integrations data to export
		 *
		 * @param array $exportable_integrations
		 * @param array $exportable_data all exportable data from model, useful.
		 *
		 * @since 1.4
		 */
		$exportable_integrations         = apply_filters( 'forminator_' . static::$module_slug . '_model_export_integrations_data', $exportable_integrations, $model_id );
		$exportable_data['integrations'] = $exportable_integrations;

		return $exportable_data;
	}

	/**
	 * Create model from import data
	 *
	 * @param array  $import_data Import data.
	 * @param string $name Form name.
	 *
	 * @return self|Forminator_Form_Model|Forminator_Poll_Model|Forminator_Quiz_Model|WP_Error
	 * @since 1.4
	 * @throws Exception When there is an error.
	 */
	public static function create_from_import_data( $import_data, $name = '' ) {
		$class = static::class;

		if ( Forminator::is_import_integrations_feature_enabled() ) {
			add_filter( 'forminator_import_model', array( $class, 'import_integrations_data' ), 1, 3 );
		}

		try {
			if ( ! Forminator::is_import_export_feature_enabled() ) {
				throw new Exception( esc_html__( 'Export Import feature disabled', 'forminator' ) );
			}

			if ( ! is_callable( array( $class, 'model' ) ) ) {
				throw new Exception( esc_html__( 'Model loader for importer does not exist.', 'forminator' ) );
			}

			// call static method ::model.
			$model = call_user_func( array( $class, 'model' ) );

			/**
			 * Executes before create model from import data
			 *
			 * @param array $import_data
			 * @param string $module
			 *
			 * @since 1.4
			 */
			do_action( 'forminator_before_create_model_from_import_data', $import_data, $class );

			if ( empty( $import_data['type'] ) ) {
				throw new Exception( esc_html__( 'Invalid format of import data type', 'forminator' ) );
			}

			$meta = ( isset( $import_data['data'] ) ? $import_data['data'] : array() );

			$meta = self::clear_stripe_plan_ids( $meta );

			if ( empty( $meta ) ) {
				throw new Exception( esc_html__( 'Invalid format of import data', 'forminator' ) );
			}

			if ( empty( $meta['settings'] ) ) {
				throw new Exception( esc_html__( 'Invalid format of import data settings', 'forminator' ) );
			}

			if ( empty( $meta['settings']['formName'] ) ) {
				throw new Exception( esc_html__( 'Invalid format of import data name', 'forminator' ) );
			}

			if ( $name ) {
				$meta['settings']['formName'] = $name;
			}

			$form_name = $meta['settings']['formName'];

			$type = $import_data['type'];

			switch ( $type ) {
				case 'quiz':
					$post_type = 'forminator_quizzes';
					break;
				case 'poll':
					$post_type = 'forminator_polls';
					break;
				default:
					$post_type = 'forminator_forms';
					break;
			}

			$post_status = ( isset( $import_data['status'] ) && ! empty( $import_data['status'] ) ) ? $import_data['status'] : self::STATUS_PUBLISH;

			/**
			 * Todo : use @see self::save()
			 */
			$post_data = array(
				'post_title'  => $form_name,
				'post_type'   => $post_type,
				'post_status' => $post_status,
			);

			$post_id = wp_insert_post( $post_data, true );

			if ( is_wp_error( $post_id ) ) {
				throw new Exception( $post_id->get_error_message(), $post_id->get_error_code() );
			}

			// update form_id.
			$meta['settings']['form_id']         = $post_id;
			$meta['settings']['previous_status'] = 'draft';

			// Sort fields to ensure grouped fields appear after their parent group.
			if ( ! empty( $meta['fields'] ) && is_array( $meta['fields'] ) ) {
				$meta['fields'] = forminator_sort_fields_with_groups( $meta['fields'] );
			}

			update_post_meta( $post_id, self::META_KEY, $meta );

			/**
			 * Forminator_Base_Form_Model
			 *
			 * @var Forminator_Base_Form_Model|Forminator_Poll_Model|Forminator_Quiz_Model|Forminator_Form_Model $model */
			$model = $model->load( $post_id );

			if ( ! $model instanceof $class ) {
				throw new Exception( esc_html__( 'Failed to load imported Forminator model', 'forminator' ) );
			}

			/**
			 * Action called after module imported
			 *
			 * @param int $post_id - module id.
			 * @param string $post_status - module status.
			 * @param object $model - module model.
			 * @param array $import_data - Import data.
			 *
			 * @since 1.11
			 * @since 1.32 Added the `$import_data` parameter.
			 */
			do_action( 'forminator_' . $type . '_action_imported', $post_id, $post_status, $model, $import_data );

			// Call do action after create imported module.
			self::module_update_do_action( $type, $post_id, $model );

		} catch ( Exception $e ) {
			$code = $e->getCode();
			if ( empty( $code ) ) {
				$code = 'forminator_import_model_error';
			}
			$model = new WP_Error( $code, $e->getMessage(), $import_data );
		}

		/**
		 * Filter imported model of form
		 *
		 * @param Forminator_Base_Form_Model|WP_Error $model
		 * @param array $import_data
		 * @param string $module
		 *
		 * @since 1.4
		 */
		$model = apply_filters( 'forminator_import_model', $model, $import_data, $class );

		// avoid filter executed on next cycle.
		remove_filter( 'forminator_import_model', array( $class, 'import_integrations_data' ), 1 );

		return $model;
	}

	/**
	 * Clear Stripe plan IDs
	 *
	 * @param array $data Data.
	 *
	 * @return mixed
	 */
	public static function clear_stripe_plan_ids( $data ) {
		if ( isset( $data['fields'] ) ) {
			$i = 0;
			foreach ( $data['fields'] as $field ) {
				if ( isset( $field['type'] ) && ( 'stripe' === $field['type'] || 'stripe-ocs' === $field['type'] ) ) {
					if ( isset( $field['payments'] ) ) {
						$x = 0;
						foreach ( $field['payments'] as $plan ) {
							if ( ! FORMINATOR_PRO && 'subscription' === $plan['payment_method'] ) {
								if ( isset( $plan['subscription_amount'] ) ) {
									$data['fields'][ $i ]['payments'][ $x ]['subscription_amount'] = '';
								}
								if ( isset( $plan['subscription_variable'] ) ) {
									$data['fields'][ $i ]['payments'][ $x ]['subscription_variable'] = '';
								}
							}
							$data['fields'][ $i ]['payments'][ $x ]['plan_id']      = '';
							$data['fields'][ $i ]['payments'][ $x ]['live_plan_id'] = '';
							$data['fields'][ $i ]['payments'][ $x ]['test_plan_id'] = '';
							++$x;
						}
					}
				}

				++$i;
			}
		}

		return $data;
	}

	/**
	 * Import Integrations data model
	 *
	 * @param mixed  $model Model.
	 * @param array  $import_data Import data.
	 * @param string $module Module.
	 *
	 * @return Forminator_Base_Form_Model
	 * @since 1.4
	 */
	public static function import_integrations_data( $model, $import_data, $module ) {
		// return what it is.
		if ( is_wp_error( $model ) ) {
			return $model;
		}

		if ( static::class !== $module ) {
			return $model;
		}

		if ( ! isset( $import_data['integrations'] ) || empty( $import_data['integrations'] ) || ! is_array( $import_data['integrations'] ) ) {
			return $model;
		}

		$integrations_data = $import_data['integrations'];
		foreach ( $integrations_data as $slug => $integrations_datum ) {
			try {
				$addon = forminator_get_addon( $slug );
				if ( $addon instanceof Forminator_Integration ) {
					$method = 'get_addon_settings';
					if ( method_exists( $addon, $method ) ) {
						$settings = $addon->$method( $model->id, static::$module_slug );
					}
					if ( ! empty( $settings ) && $settings instanceof Forminator_Integration_Form_Settings ) {
						$settings->import_data( $integrations_datum );
					}
				}
			} catch ( Exception $e ) {
				forminator_addon_maybe_log( $slug, 'failed to get import module settings', $e->getMessage() );
			}
		}

		return $model;
	}

	/**
	 * Get status of prevent_store
	 *
	 * @param int   $id Id.
	 * @param array $settings Settings.
	 *
	 * @return boolean
	 * @since 1.5
	 */
	public function is_prevent_store( $id = null, $settings = array() ) {
		$module_id = ! empty( $id ) ? $id : (int) $this->id;
		$settings  = ! empty( $settings ) ? $settings : $this->settings;

		// default is always store.
		$store_submissions = true;

		$store_submissions = isset( $settings['store_submissions'] ) ? $settings['store_submissions'] : $store_submissions;
		// We have to reverse this because disable store submissions was changed to positive statement since 1.15.12
		// from prevent store to store submissions.
		$is_prevent_store = filter_var( $store_submissions, FILTER_VALIDATE_BOOLEAN ) ? false : true;

		/**
		 * Filter is_prevent_store flag of the module
		 *
		 * @param bool $is_prevent_store
		 * @param int $module_id
		 * @param array $settings
		 *
		 * @since 1.5
		 */
		$is_prevent_store = apply_filters( 'forminator_' . static::$module_slug . '_is_prevent_store', $is_prevent_store, $module_id, $settings );

		return $is_prevent_store;
	}

	/**
	 * Flag if module should be loaded via ajax
	 *
	 * @param bool $force Force.
	 *
	 * @return bool
	 * @since 1.6.1
	 */
	public function is_ajax_load( $force = false ) {
		$module_id      = (int) $this->id;
		$settings       = $this->settings;
		$global_enabled = self::is_global_ajax_load( $force );

		$enabled = isset( $settings['use_ajax_load'] ) ? $settings['use_ajax_load'] : false;
		$enabled = filter_var( $enabled, FILTER_VALIDATE_BOOLEAN );

		$enabled = $global_enabled || $enabled;

		/**
		 * Filter is ajax load for module
		 *
		 * @param bool $enabled
		 * @param bool $global_enabled
		 * @param int $form_id
		 * @param array $form_settings
		 *
		 * @return bool
		 * @since 1.6.1
		 */
		$enabled = apply_filters( 'forminator_' . static::$module_slug . '_is_ajax_load', $enabled, $global_enabled, $module_id, $settings );

		return $enabled;
	}

	/**
	 * Flag if module should be loaded via ajax (Global settings)
	 *
	 * @param bool $force Force.
	 *
	 * @return bool
	 * @since 1.6.1
	 */
	private static function is_global_ajax_load( $force = false ) {
		// from constant.
		$enabled = defined( 'FORMINATOR_MODULE_ENABLE_LOAD_AJAX' ) && FORMINATOR_MODULE_ENABLE_LOAD_AJAX;

		// if one is true, then its enabled.
		$enabled = $force || $enabled;

		/**
		 * Filter flag is ajax load of module
		 *
		 * @param bool $enabled
		 * @param bool $force
		 *
		 * @return bool
		 * @since  1.6
		 */
		$enabled = apply_filters( 'forminator_module_is_ajax_load', $enabled, $force );

		return $enabled;
	}

	/**
	 * Flag to use `DONOTCACHEPAGE`
	 *
	 * @return bool
	 * @since 1.6.1
	 */
	public function is_use_donotcachepage_constant() {
		$module_id      = (int) $this->id;
		$settings       = $this->settings;
		$global_enabled = self::is_global_use_donotcachepage_constant();

		$enabled = isset( $settings['use_donotcachepage'] ) ? $settings['use_donotcachepage'] : false;
		$enabled = filter_var( $enabled, FILTER_VALIDATE_BOOLEAN );

		$enabled = $global_enabled || $enabled;

		/**
		 * Filter use `DONOTCACHEPAGE` Module
		 *
		 * @param bool $enabled
		 * @param bool $global_enabled
		 * @param int $module_id
		 * @param array $settings
		 *
		 * @return bool
		 * @since 1.6.1
		 */
		$enabled = apply_filters( 'forminator_custom_form_is_use_donotcachepage_constant', $enabled, $global_enabled, $module_id, $settings );

		return $enabled;
	}

	/**
	 * Flag to use `DONOTCACHEPAGE`
	 *
	 * @return bool
	 * @since 1.6.1
	 */
	private static function is_global_use_donotcachepage_constant() {
		// from constant.
		$enabled = defined( 'FORMINATOR_MODULE_USE_DONOTCACHEPAGE' ) && FORMINATOR_MODULE_USE_DONOTCACHEPAGE;

		/**
		 * Filter flag is use `DONOTCACHEPAGE` of module
		 *
		 * @param bool $enabled
		 *
		 * @return bool
		 * @since  1.6
		 */
		$enabled = apply_filters( 'forminator_module_is_use_donotcachepage_constant', $enabled );

		return $enabled;
	}

	/**
	 * Check if the result enable for share
	 *
	 * @return bool
	 * @since 1.7
	 */
	public function is_entry_share_enabled() {
		$module_id   = (int) $this->id;
		$module_type = $this->post_type;

		// from settings.
		$settings_enabled = get_option( 'forminator_module_enable_share_entry', false );

		// from constant.
		$enabled = defined( 'FORMINATOR_MODULE_ENABLE_SHARE_ENTRY' ) && FORMINATOR_MODULE_ENABLE_SHARE_ENTRY;

		// if one is true, then its enabled.
		$enabled = $settings_enabled || $enabled;

		/**
		 * Filter flag is use `ENABLE_SHARE_ENTRY` of module
		 *
		 * @param bool $enabled
		 * @param int $module_id
		 * @param string $module_type
		 *
		 * @return bool
		 * @since  1.7
		 */
		$enabled = apply_filters( 'forminator_module_is_entry_share_enabled', $enabled, $module_id, $module_type );

		return $enabled;
	}

	/**
	 * Check if we can submit the form
	 *
	 * @since 1.19.0
	 * @return array
	 */
	public function form_can_submit() {
		$form_settings = $this->settings;
		$can_show      = array(
			'can_submit' => true,
			'error'      => '',
		);

		// Allow module slug to be translated for messages.
		$module_slug = static::$module_slug;
		switch ( $module_slug ) {
			case 'quiz':
				$module_slug = esc_html__( 'quiz', 'forminator' );
				break;
			case 'poll':
				$module_slug = esc_html__( 'poll', 'forminator' );
				break;
			default:
				$module_slug = esc_html__( 'form', 'forminator' );
				break;
		}

		$only_users = ! empty( $form_settings['logged-users'] ) && filter_var( $form_settings['logged-users'], FILTER_VALIDATE_BOOLEAN );
		if ( $only_users && ! is_user_logged_in() ) {
			$can_show = array(
				'can_submit' => false,
				/* translators: %s: module slug */
				'error'      => sprintf( esc_html__( 'Only logged in users can submit this %s.', 'forminator' ), $module_slug ),
			);
		}
		if ( $only_users && ! empty( $form_settings['limit-per-user'] ) ) {
			$user_submitted = Forminator_Form_Entry_Model::count_user_entries( $this->id );
			if ( $form_settings['limit-per-user'] <= $user_submitted ) {
				$error    = ! empty( $form_settings['limit-per-user-error'] ) ? $form_settings['limit-per-user-error']
						: esc_html__( 'You’ve already reached submissions limit.', 'forminator' );
				$can_show = array(
					'can_submit' => false,
					'error'      => $error,
				);
			}
		}
		if ( $can_show['can_submit'] ) {
			if ( isset( $form_settings['form-expire'] ) ) {
				if ( 'submits' === $form_settings['form-expire'] ) {
					if ( isset( $form_settings['expire_submits'] ) && ! empty( $form_settings['expire_submits'] ) ) {
						$submits       = intval( $form_settings['expire_submits'] );
						$total_entries = Forminator_Form_Entry_Model::count_entries( $this->id );
						if ( $total_entries >= $submits ) {
							$can_show = array(
								'can_submit' => false,
								/* translators: %s: module slug */
								'error'      => sprintf( esc_html__( 'You have reached the maximum allowed submissions for this %s.', 'forminator' ), $module_slug ),
							);
						}
					}
				} elseif ( 'date' === $form_settings['form-expire'] ) {
					if ( isset( $form_settings['expire_date'] ) && ! empty( $form_settings['expire_date'] ) ) {
						$expire_date  = $this->get_expiry_date( $form_settings['expire_date'] );
						$current_date = strtotime( 'now' );
						if ( $current_date > $expire_date ) {
							$can_show = array(
								'can_submit' => false,
								/* translators: %s: module slug */
								'error'      => sprintf( esc_html__( 'Unfortunately this %s has expired.', 'forminator' ), $module_slug ),
							);
						}
					}
				}
			}
		}

		if ( $can_show['can_submit'] ) {
			// disable submit if status is draft.
			if ( self::STATUS_DRAFT === $this->status ) {
				$can_show = array(
					'can_submit' => false,
					/* translators: %s: module slug */
					'error'      => sprintf( esc_html__( 'This %s is not published.', 'forminator' ), $module_slug ),
				);
			}
		}

		return apply_filters( 'forminator_cform_' . static::$module_slug . '_is_submittable', $can_show, $this->id, $form_settings );
	}

	/**
	 * Check if form has active PayPal field
	 *
	 * @return bool
	 * @since 1.51.0
	 */
	public function has_active_paypal() {
		$is_enabled = forminator_has_paypal_settings();
		$active     = 0;
		$fields     = $this->get_fields_as_array();

		if ( ! empty( $fields ) ) {
			foreach ( $fields as $field ) {
				if ( 'paypal' === $field['type'] && ! Forminator_Field::is_hidden( $field ) ) {
					++$active;
					break;
				}
			}
		}

		return ( $is_enabled && $active > 0 ) ? true : false;
	}

	/**
	 * Check if we can show the form
	 *
	 * @param bool $is_preview Is preview.
	 *
	 * @since 1.19.0
	 * @return bool
	 */
	public function form_is_visible( $is_preview ) {
		$form_settings = $this->settings;
		$can_show      = true;

		if ( isset( $form_settings['logged-users'] ) && ! empty( $form_settings['logged-users'] ) ) {
			if ( filter_var( $form_settings['logged-users'], FILTER_VALIDATE_BOOLEAN ) && ! is_user_logged_in() ) {
				$can_show = false;
			}
		}
		if ( $can_show ) {
			if ( isset( $form_settings['form-expire'] ) ) {
				if ( 'submits' === $form_settings['form-expire'] ) {
					if ( isset( $form_settings['expire_submits'] ) && ! empty( $form_settings['expire_submits'] ) ) {
						$submits       = intval( $form_settings['expire_submits'] );
						$total_entries = Forminator_Form_Entry_Model::count_entries( $this->id );
						if ( $total_entries >= $submits && ! $is_preview ) {
							$can_show = false;
						}
					}
				} elseif ( 'date' === $form_settings['form-expire'] ) {
					if ( isset( $form_settings['expire_date'] ) && ! empty( $form_settings['expire_date'] ) ) {
						$expire_date  = $this->get_expiry_date( $form_settings['expire_date'] );
						$current_date = strtotime( 'now' );
						if ( $current_date > $expire_date && ! $is_preview ) {
							$can_show = false;
						}
					}
				}
			}
		}

		return apply_filters( 'forminator_cform_' . static::$module_slug . '_is_visible', $can_show, $this->id, $form_settings );
	}

	/**
	 * Get expiry date
	 *
	 * Before 1.15.4 expiry date is being saved like: 1 Mar 2022
	 * Since we changed it to save as Unix timestamp, we need to process
	 *
	 * @param string $expire_date Expire date.
	 *
	 * @since 1.19.0
	 * @return string
	 */
	public function get_expiry_date( $expire_date ) {
		return is_numeric( $expire_date ) ? (int) $expire_date / 1000 : strtotime( $expire_date );
	}

	/**
	 * Filter multi search.
	 * This filter is used to search for multiple words in post titles (OR logic).
	 *
	 * @param string   $search The existing SQL WHERE clause for search.
	 * @param WP_Query $query  The WP_Query instance.
	 *
	 * @since 1.52
	 * @return string Modified SQL WHERE clause for search.
	 */
	public function filter_multi_search( $search, $query ) {
		global $wpdb;
		if ( empty( $query->query_vars['s'] ) || ! isset( $query->query_vars['forminator_multi_search'] ) ) {
			return $search;
		}
		$terms = explode( ' ', $query->query_vars['s'] );
		$likes = array();
		foreach ( $terms as $term ) {
			$likes[] = $wpdb->prepare(
				"{$wpdb->posts}.post_title LIKE %s",
				'%' . $wpdb->esc_like( $term ) . '%'
			);
		}
		return ' AND (' . implode( ' OR ', $likes ) . ') ';
	}

	/**
	 * Call do action when module update
	 *
	 * @param string $type module type.
	 * @param string $post_id post ID.
	 * @param object $model model array.
	 *
	 * @return void
	 */
	public static function module_update_do_action( $type, $post_id, $model ) {
		$model_array                 = (array) $model;
		$post_status                 = ! empty( $model_array['status'] ) ? $model_array['status'] : '';
		$settings                    = ! empty( $model_array['settings'] ) ? $model_array['settings'] : array();
		$settings['previous_status'] = 'draft';
		try {
			switch ( $type ) {
				case 'form':
					$fields    = ! empty( $model->fields ) ? $model->get_fields_grouped() : array();
					$form_name = $model_array['settings']['formName'];
					/** This action is documented in library/modules/custom-forms/admin/admin-loader.php */
					do_action( 'forminator_custom_form_action_update', $post_id, $form_name, $post_status, $fields, $settings );
					break;
				case 'poll':
					$answer = ! empty( $model->fields ) ? $model->get_fields_as_array() : array();
					/** This action is documented in library/modules/polls/admin/admin-loader.php */
					do_action( 'forminator_poll_action_update', $post_id, $post_status, $answer, $settings );
					break;
				case 'quiz':
					$quiz_type = $model_array['quiz_type'];
					$questions = $model_array['questions'];
					$results   = $model_array['results'];
					/** This action is documented in library/modules/quizzes/admin/admin-loader.php */
					do_action( 'forminator_quiz_action_update', $post_id, $quiz_type, $post_status, $questions, $results, $settings );
					break;
				default:
					break;
			}
		} catch ( Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			// Ignore errors coming from the add-on for now.
			// TODO: Display an appropriate error message.
		}
	}

	/**
	 * Check if model is publishable
	 *
	 * @return true|WP_Error
	 */
	public function is_publishable() {
		if ( empty( $this->name ) ) {
			return new WP_Error(
				'form_error',
				__( 'Please, enter a valid name.', 'forminator' )
			);
		}

		return true;
	}
}
