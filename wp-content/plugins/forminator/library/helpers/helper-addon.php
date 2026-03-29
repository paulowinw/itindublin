<?php
/**
 * Addon helper functions.
 *
 * @package Forminator
 */

/**
 * Get registered addon instance by `$slug`
 *
 * @since 1.1
 *
 * @param string $slug Addon slug.
 *
 * @return Forminator_Integration|null
 */
function forminator_get_addon( $slug ) {
	return Forminator_Integration_Loader::get_instance()->get_addon( $slug );
}

/**
 * Get Pro Addon List
 *
 * @todo  : Verify pro addon list from non user modifiable data source (API or similar others)
 *
 * @since 1.1
 * @return array
 */
function forminator_get_pro_addon_list() {
	$pro_addons = array(
		'mailchimp' => array(
			'_image'                  => 'https://via.placeholder.com/350x150?',
			'_icon'                   => 'mailchimp',
			'_title'                  => 'Mailchimp',
			'_short_title'            => 'Mailchimp',
			'_version'                => '1.0',
			'_description'            => esc_html__( 'Unlock this as part of a WPMU DEV Membership', 'forminator' ),
			'_min_forminator_version' => FORMINATOR_VERSION,
		),
		'webhook'   => array(
			'_image'                  => 'https://via.placeholder.com/350x150',
			'_icon'                   => 'webhook',
			'_title'                  => 'Webhook',
			'_short_title'            => 'Webhook',
			'_version'                => '1.0',
			'_description'            => esc_html__( 'Unlock this as part of a WPMU DEV Membership', 'forminator' ),
			'_min_forminator_version' => FORMINATOR_VERSION,
		),
	);

	return $pro_addons;
}


/**
 * Get all add-ons as list
 *
 * @since 1.1
 * @return array
 */
function forminator_get_registered_addons_list() {
	$addon_list = Forminator_Integration_Loader::get_instance()->get_addons()->to_array();
	usort( $addon_list, 'sort_addons' );

	return $addon_list;
}

/**
 * Sort addons
 *
 * @param array $a Addon.
 * @param array $b Addon.
 *
 * @return mixed
 */
function sort_addons( $a, $b ) {
	return $a['position'] - $b['position'];
}

/**
 * Get registered addons grouped by connected status
 *
 * @since 1.1
 * @return array
 */
function forminator_get_registered_addons_grouped_by_connected() {
	$addon_list           = forminator_get_registered_addons_list();
	$connected_addons     = array();
	$not_connected_addons = array();

	// late init properties.
	foreach ( $addon_list as $addon ) {
		if ( $addon['is_connected'] ) {
			$obj              = forminator_get_addon( $addon['slug'] );
			$multi_global_ids = $obj->get_multi_global_ids();
			if ( ! empty( $multi_global_ids ) ) {
				foreach ( $multi_global_ids as $global_id => $identifier ) {
					$addon['global_id']  = $global_id;
					$addon['identifier'] = $identifier;
					$connected_addons[]  = $addon;
					unset( $addon['global_id'] );
					unset( $addon['identifier'] );
				}
			} else {
				$connected_addons[] = $addon;
			}
		}
		if ( $addon['is_multi_global'] || ! $addon['is_connected'] ) {
			$addon['is_connected'] = false;
			if ( ! empty( $addon['new_global_id'] ) ) {
				$addon['global_id'] = $addon['new_global_id'];
			}
			$not_connected_addons[] = $addon;
			unset( $addon['global_id'] );
		}
	}

	return array(
		'connected'     => $connected_addons,
		'not_connected' => $not_connected_addons,
	);
}

/**
 * Get addon instances that connected with a module
 *
 * @todo  make instances static and available through runtime
 *
 * @param int    $module_id Module ID.
 * @param string $module_type Module type.
 *
 * @return Forminator_Integration[]
 */
function forminator_get_addons_instance_connected_with_module( $module_id, $module_type ) {
	$grouped_addons = forminator_get_registered_addons_grouped_by_module_connected( $module_id, $module_type, true );

	$addons = array();
	foreach ( $grouped_addons['connected'] as $property ) {
		$addon = forminator_get_addon( $property['slug'] );
		if ( ! empty( $property['global_id'] ) ) {
			$addon->multi_global_id = $property['global_id'];
		}
		if ( ! empty( $property['multi_id'] ) ) {
			$addon->multi_id = $property['multi_id'];
		}
		$addons[] = clone $addon;
	}

	return $addons;
}

/**
 * Get addon(s) in array format grouped by connected / not connected with $module_id
 *
 * Every addon inside this array will be formatted first by @see Forminator_Integration::to_array_with_form()
 *
 * @since 1.1
 *
 * @param int    $module_id Module ID.
 * @param string $module_type Module type.
 * @param bool   $connected_only Return connected integrations only.
 *
 * @return array
 */
function forminator_get_registered_addons_grouped_by_module_connected( $module_id, $module_type, $connected_only = false ) {
	$grouped_addons = array(
		'connected'     => array(),
		'not_connected' => array(),
	);

	$addons = Forminator_Integration_Loader::get_instance()->get_addons()->to_array();
	// Get only connected integrations to the module to avoid checking all integrations.
	if ( $connected_only ) {
		$post_meta = get_post_meta( $module_id );
		// Remove empty integrations.
		$post_meta = array_filter(
			$post_meta,
			function ( $value ) {
				return ! empty( $value ) && array( 'a:0:{}' ) !== $value;
			}
		);
		if ( ! is_array( $post_meta ) ) {
			$post_meta = array();
		}
		$meta_keys = array_keys( $post_meta );
		$addons    = array_filter(
			$addons,
			function ( $addon ) use ( $meta_keys, $module_type ) {
				$integration_slug = $addon['slug'] ?? '';
				foreach ( $meta_keys as $meta_key ) {
					// Check if there are relevant settings for this integration - leave it, otherwise - remove it.
					if ( strpos( $meta_key, 'forminator_addon_' . $integration_slug . '_' . $module_type . '_settings' ) === 0 ) {
						return true;
					}
				}
				return false;
			}
		);
	}

	foreach ( $addons as $slug => $addon_settings ) {
		$addon           = Forminator_Integration_Loader::get_instance()->get_addon( $slug );
		$to_array_method = 'to_array_with_' . $module_type;
		if ( ! method_exists( $addon, $to_array_method ) && method_exists( $addon, 'is_module_connected' ) ) {
			continue;
		}
		$multi_global_ids = $addon->get_multi_global_ids();
		if ( $multi_global_ids ) {
			foreach ( $multi_global_ids as $global_id => $identifier ) {
				$addon->multi_global_id              = $global_id;
				$addon_settings                      = $addon->$to_array_method( $module_id );
				$addon_settings['global_id']         = $global_id;
				$addon_settings['global_identifier'] = $identifier;

				$grouped_addons = forminator_group_addons_by_module( $grouped_addons, $addon, $addon_settings, $module_id, $module_type );
				unset( $addon_settings['global_id'], $addon_settings['global_identifier'] );
				$addon->multi_global_id = null;
			}
		} else {
			$addon_settings = $addon->$to_array_method( $module_id );
			$grouped_addons = forminator_group_addons_by_module( $grouped_addons, $addon, $addon_settings, $module_id, $module_type );
		}
	}

	return $grouped_addons;
}

/**
 * Group addons by module. It's used only for forminator_get_registered_addons_grouped_by_module_connected()
 *
 * @param array  $grouped_addons Grouped addons.
 * @param object $addon Addon object.
 * @param array  $addon_settings Addon settings.
 * @param int    $module_id Module ID.
 * @param string $module_type Module type.
 * @return array $grouped_addons
 */
function forminator_group_addons_by_module( $grouped_addons, $addon, $addon_settings, $module_id, $module_type ) {
	$allow_method = 'is_allow_multi_on_' . $module_type;
	$is_complete  = 'is_multi_id_completed';
	/**
	 * Forminator_Integration
	 *
	 * @var Forminator_Integration $addon */
	if ( $addon->is_connected() && ( 'quiz' !== $module_type || $addon->is_module_connected( $module_id, 'quiz', true ) ) ) {
		if ( method_exists( $addon, $allow_method ) && $addon->$allow_method() ) {
			$addon_array = $addon_settings;
			if ( $addon->is_module_connected( $module_id, $module_type ) && isset( $addon_array['multi_ids'] ) && is_array( $addon_array['multi_ids'] ) ) {
				foreach ( $addon_array['multi_ids'] as $multi_id ) {
					$form_settings_instance = $addon->get_addon_settings( $module_id, $module_type );
					if ( $form_settings_instance->$is_complete( $multi_id['id'] ) ) {
						$addon_array['multi_id']       = $multi_id['id'];
						$addon_array['multi_name']     = ! empty( $multi_id['label'] ) ? $multi_id['label'] : $multi_id['id'];
						$grouped_addons['connected'][] = $addon_array;
					}
				}
			} else {
				$grouped_addons['not_connected'][] = $addon_settings;
			}
		} elseif ( $addon->is_module_connected( $module_id, $module_type ) ) {
				$grouped_addons['connected'][] = $addon_settings;
		} else {
			$grouped_addons['not_connected'][] = $addon_settings;
		}
	}

	return $grouped_addons;
}

/**
 * Attach default addon hooks for Addon.
 *
 * Call when needed only,
 * defined in @see Forminator_Integration::global_hookable()
 * and @see Forminator_Integration::admin_hookable on admin mode
 *
 * @since 1.1
 *
 * @param Forminator_Integration $addon Addon.
 */
function forminator_maybe_attach_addon_hook( Forminator_Integration $addon ) {
	$addon->global_hookable();
	// only hooks that available on admin.
	if ( is_admin() ) {
		$addon->admin_hookable();
	}
}

/**
 * Helper Check if addon is active
 *
 * @since 1.1
 *
 * @param string $slug Addon slug.
 *
 * @return bool
 */
function forminator_addon_is_active( $slug ) {
	return Forminator_Integration_Loader::get_instance()->addon_is_active( $slug );
}

/**
 * Get allowed field type available for addon
 *
 * @since 1.1
 * @return array
 */
function forminator_get_allowed_field_types_for_addon() {
	$allowed_field_types = array(
		'address-street_address',
		'address-address_line',
		'address-city',
		'address-state',
		'address-zip',
		'address-country',
		'date', // force into one.
		'email',
		'hidden',
		'checkbox',
		'consent',
		'gdprcheckbox',
		'name', // single.
		'name-prefix', // multiple.
		'name-first-name',
		'name-middle-name',
		'name-last-name',
		'number',
		'slider',
		'slider-min',
		'slider-max',
		'phone',
		'postdata-post-title',
		'postdata-post-content',
		'postdata-post-excerpt',
		'postdata-post-category',
		'postdata-category',
		'postdata-post-tags',
		'postdata-post_tag',
		'postdata-post-image',
		'select',
		'text',
		'time',
		'upload',
		'url',
		// 1.6 fields.
		'textarea',
		'radio',
		'checkbox',
		// 1.7 fields.
		'calculation',
		'stripe',
		'stripe-ocs',
		'paypal',
		'signature',
		// 1.15.
		'currency',
		'rating',
	);

	/**
	 * Filter allowed filed types to be used by addons
	 *
	 * This value will be used by **ALL** addons
	 *
	 * @since 1.1
	 *
	 * @param array $allowed_field_types current allowed field types.
	 */
	$allowed_field_types = apply_filters( 'forminator_addon_allowed_field_types', $allowed_field_types );

	return $allowed_field_types;
}

/**
 * Format Form Fields
 *
 * @since 1.1
 *
 * @param Forminator_Base_Form_Model $custom_form_model Base form model.
 *
 * @return array
 */
function forminator_addon_format_form_fields( Forminator_Base_Form_Model $custom_form_model ) {
	$formatted_fields    = array();
	$fields              = $custom_form_model->get_real_fields();
	$allowed_field_types = forminator_get_allowed_field_types_for_addon();

	foreach ( $fields as $field ) {
		$field_as_array = $field->to_formatted_array();
		// check non label fields.
		if ( empty( $field_as_array['field_label'] ) ) {
			$field_as_array['field_label'] = $field_as_array['type'];
		}

		// handle multiple.
		$multi_fields = forminator_addon_flatten_mutiple_field( $field_as_array );
		if ( false === $multi_fields ) {
			if ( ! in_array( $field_as_array['type'], $allowed_field_types, true ) ) {
				continue;
			}
			$field_as_array['field_type'] = $field_as_array['type'];
			$formatted_fields[]           = $field_as_array;
		} else {
			foreach ( $multi_fields as $multi_field ) {
				if ( ! in_array( $multi_field['type'], $allowed_field_types, true ) ) {
					continue;
				}
				$multi_field['field_type'] = $field_as_array['type'];
				$formatted_fields[]        = $multi_field;
			}
		}
	}

	/**
	 * Filter formatted fields to be used by addon
	 *
	 * This value will be used by **ALL** addons
	 *
	 * @since 1.1
	 *
	 * @param array                        $formatted_fields  current formatted fields.
	 * @param Forminator_Form_Model $custom_form_model Custom form Model.
	 */
	$formatted_fields = apply_filters( 'forminator_addon_formatted_fields', $formatted_fields, $custom_form_model );

	return $formatted_fields;
}

/**
 * Flatten multiple field
 *
 * @since 1.1
 *
 * @param array $field_array Field.
 *
 * @return array|bool array flatten multi-field or false, when its not considered as multi-field
 */
function forminator_addon_flatten_mutiple_field( $field_array ) {
	$multiple_field_types = array(
		'name',
		'postdata',
		'address',
		'slider',
	);

	if ( ! in_array( $field_array['type'], $multiple_field_types, true ) ) {
		return false;
	}

	// flatten name.
	if ( 'name' === $field_array['type'] ) {
		$is_multiple_name = isset( $field_array['multiple_name'] ) && filter_var( $field_array['multiple_name'], FILTER_VALIDATE_BOOLEAN ) ? true : false;
		if ( ! $is_multiple_name ) {
			return false;
		}

		$prefix_enabled      = isset( $field_array['prefix'] ) && filter_var( $field_array['prefix'], FILTER_VALIDATE_BOOLEAN ) ? true : false;
		$first_name_enabled  = isset( $field_array['fname'] ) && filter_var( $field_array['fname'], FILTER_VALIDATE_BOOLEAN ) ? true : false;
		$middle_name_enabled = isset( $field_array['mname'] ) && filter_var( $field_array['mname'], FILTER_VALIDATE_BOOLEAN ) ? true : false;
		$last_name_enabled   = isset( $field_array['lname'] ) && filter_var( $field_array['lname'], FILTER_VALIDATE_BOOLEAN ) ? true : false;
		if ( $prefix_enabled || $first_name_enabled || $middle_name_enabled || $last_name_enabled ) {
			$multi_fields = array();
			if ( $prefix_enabled ) {
				$multi_field = $field_array;

				$default_label = Forminator_Form_Entry_Model::translate_suffix( 'prefix' );
				$label         = isset( $multi_field['prefix_label'] ) ? $multi_field['prefix_label'] : '';

				$multi_field['type']        = $multi_field['type'] . '-prefix';
				$multi_field['element_id']  = $multi_field['element_id'] . '-prefix';
				$multi_field['field_label'] = ( $label ? $label : $default_label );

				$multi_fields [] = $multi_field;
			}

			if ( $first_name_enabled ) {
				$multi_field = $field_array;

				$default_label = Forminator_Form_Entry_Model::translate_suffix( 'first-name' );
				$label         = isset( $multi_field['fname_label'] ) ? $multi_field['fname_label'] : '';

				$multi_field['type']        = $multi_field['type'] . '-first-name';
				$multi_field['element_id']  = $multi_field['element_id'] . '-first-name';
				$multi_field['field_label'] = ( $label ? $label : $default_label );

				$multi_fields [] = $multi_field;
			}

			if ( $middle_name_enabled ) {
				$multi_field = $field_array;

				$default_label = Forminator_Form_Entry_Model::translate_suffix( 'middle-name' );
				$label         = isset( $multi_field['mname_label'] ) ? $multi_field['mname_label'] : '';

				$multi_field['type']        = $multi_field['type'] . '-middle-name';
				$multi_field['element_id']  = $multi_field['element_id'] . '-middle-name';
				$multi_field['field_label'] = ( $label ? $label : $default_label );

				$multi_fields [] = $multi_field;
			}

			if ( $last_name_enabled ) {
				$multi_field = $field_array;

				$default_label = Forminator_Form_Entry_Model::translate_suffix( 'last-name' );
				$label         = isset( $multi_field['lname_label'] ) ? $multi_field['lname_label'] : '';

				$multi_field['type']        = $multi_field['type'] . '-last-name';
				$multi_field['element_id']  = $multi_field['element_id'] . '-last-name';
				$multi_field['field_label'] = ( $label ? $label : $default_label );

				$multi_fields [] = $multi_field;
			}

			return $multi_fields;
		}
	} elseif ( 'slider' === $field_array['type'] ) {
		if ( 'range' !== $field_array['slider_type'] ) {
			return false;
		}
		return array(
			array(
				'type'        => $field_array['type'] . '-min',
				'element_id'  => $field_array['element_id'] . '-min',
				'field_label' => $field_array['field_label'] . ' - min',
			),
			array(
				'type'        => $field_array['type'] . '-max',
				'element_id'  => $field_array['element_id'] . '-max',
				'field_label' => $field_array['field_label'] . ' - max',
			),
		);
	} elseif ( 'postdata' === $field_array['type'] ) {
		// flatten POSTDATA.
		$title_enabled   = isset( $field_array['post_title'] ) && ! empty( $field_array['post_title'] ) ? true : false;
		$content_enabled = isset( $field_array['post_content'] ) && ! empty( $field_array['post_content'] ) ? true : false;
		$excerpt_enabled = isset( $field_array['post_excerpt'] ) && ! empty( $field_array['post_excerpt'] ) ? true : false;
		$image_enabled   = isset( $field_array['post_image'] ) && ! empty( $field_array['post_image'] ) ? true : false;
		$post_type       = isset( $field_array['post_type'] ) && ! empty( $field_array['post_type'] ) ? $field_array['post_type'] : 'post';
		$category_list   = forminator_post_categories( $post_type );
		if ( $title_enabled || $content_enabled || $excerpt_enabled || $image_enabled || $category_list ) {
			$multi_fields = array();

			if ( $title_enabled ) {
				$multi_field = $field_array;

				$default_label = Forminator_Form_Entry_Model::translate_suffix( 'post-title' );
				$label         = isset( $multi_field['post_title_label'] ) ? $multi_field['post_title_label'] : '';

				$multi_field['type']        = $multi_field['type'] . '-post-title';
				$multi_field['element_id']  = $multi_field['element_id'] . '-post-title';
				$multi_field['field_label'] = ( $label ? $label : $default_label );

				$multi_fields [] = $multi_field;
			}

			if ( $content_enabled ) {
				$multi_field = $field_array;

				$default_label = Forminator_Form_Entry_Model::translate_suffix( 'post-content' );
				$label         = isset( $multi_field['post_content_label'] ) ? $multi_field['post_content_label'] : '';

				$multi_field['type']        = $multi_field['type'] . '-post-content';
				$multi_field['element_id']  = $multi_field['element_id'] . '-post-content';
				$multi_field['field_label'] = ( $label ? $label : $default_label );

				$multi_fields [] = $multi_field;
			}

			if ( $excerpt_enabled ) {
				$multi_field = $field_array;

				$default_label = Forminator_Form_Entry_Model::translate_suffix( 'post-excerpt' );
				$label         = isset( $multi_field['post_excerpt_label'] ) ? $multi_field['post_excerpt_label'] : '';

				$multi_field['type']        = $multi_field['type'] . '-post-excerpt';
				$multi_field['element_id']  = $multi_field['element_id'] . '-post-excerpt';
				$multi_field['field_label'] = ( $label ? $label : $default_label );

				$multi_fields [] = $multi_field;
			}

			if ( ! empty( $category_list ) ) {
				foreach ( $category_list as $category ) {
					$category_enabled = isset( $field_array[ $category['value'] ] ) && ! empty( $field_array[ $category['value'] ] ) ? true : false;
					if ( $category_enabled ) {
						$multi_field = $field_array;

						$default_label = $category['label'];
						$label         = isset( $multi_field[ $category['value'] . '_label' ] ) ? $multi_field[ $category['value'] . '_label' ] : '';

						$multi_field['type']        = $multi_field['type'] . '-' . $category['value'];
						$multi_field['element_id']  = $multi_field['element_id'] . '-' . $category['value'];
						$multi_field['field_label'] = ( $label ? $label : $default_label );

						$multi_fields [] = $multi_field;
					}
				}
			}

			if ( $image_enabled ) {
				$multi_field = $field_array;

				$default_label = Forminator_Form_Entry_Model::translate_suffix( 'post-image' );
				$label         = isset( $multi_field['post_image_label'] ) ? $multi_field['post_image_label'] : '';

				$multi_field['type']        = $multi_field['type'] . '-post-image';
				$multi_field['element_id']  = $multi_field['element_id'] . '-post-image';
				$multi_field['field_label'] = ( $label ? $label : $default_label );

				$multi_fields [] = $multi_field;
			}

			return $multi_fields;
		}
	} elseif ( 'address' === $field_array['type'] ) {
		// flatten ADDRESS.
		$street_enabled  = isset( $field_array['street_address'] ) && filter_var( $field_array['street_address'], FILTER_VALIDATE_BOOLEAN ) ? true : false;
		$line_enabled    = isset( $field_array['address_line'] ) && filter_var( $field_array['address_line'], FILTER_VALIDATE_BOOLEAN ) ? true : false;
		$city_enabled    = isset( $field_array['address_city'] ) && filter_var( $field_array['address_city'], FILTER_VALIDATE_BOOLEAN ) ? true : false;
		$state_enabled   = isset( $field_array['address_state'] ) && filter_var( $field_array['address_state'], FILTER_VALIDATE_BOOLEAN ) ? true : false;
		$zip_enabled     = isset( $field_array['address_zip'] ) && filter_var( $field_array['address_zip'], FILTER_VALIDATE_BOOLEAN ) ? true : false;
		$country_enabled = isset( $field_array['address_country'] ) && filter_var( $field_array['address_country'], FILTER_VALIDATE_BOOLEAN ) ? true : false;
		if ( $street_enabled || $line_enabled || $city_enabled || $state_enabled || $zip_enabled || $country_enabled ) {
			$multi_fields = array();
			if ( $street_enabled ) {
				$multi_field = $field_array;

				$default_label = Forminator_Form_Entry_Model::translate_suffix( 'street_address' );
				$label         = isset( $multi_field['street_address_label'] ) ? $multi_field['street_address_label'] : '';

				$multi_field['type']        = $multi_field['type'] . '-street_address';
				$multi_field['element_id']  = $multi_field['element_id'] . '-street_address';
				$multi_field['field_label'] = ( $label ? $label : $default_label );

				$multi_fields [] = $multi_field;
			}

			if ( $line_enabled ) {
				$multi_field = $field_array;

				$default_label = Forminator_Form_Entry_Model::translate_suffix( 'address_line' );
				$label         = isset( $multi_field['address_line_label'] ) ? $multi_field['address_line_label'] : '';

				$multi_field['type']        = $multi_field['type'] . '-address_line';
				$multi_field['element_id']  = $multi_field['element_id'] . '-address_line';
				$multi_field['field_label'] = ( $label ? $label : $default_label );

				$multi_fields [] = $multi_field;
			}

			if ( $city_enabled ) {
				$multi_field = $field_array;

				$default_label = Forminator_Form_Entry_Model::translate_suffix( 'city' );
				$label         = isset( $multi_field['address_city_label'] ) ? $multi_field['address_city_label'] : '';

				$multi_field['type']        = $multi_field['type'] . '-city';
				$multi_field['element_id']  = $multi_field['element_id'] . '-city';
				$multi_field['field_label'] = ( $label ? $label : $default_label );

				$multi_fields [] = $multi_field;
			}

			if ( $state_enabled ) {
				$multi_field = $field_array;

				$default_label = Forminator_Form_Entry_Model::translate_suffix( 'state' );
				$label         = isset( $multi_field['address_state_label'] ) ? $multi_field['address_state_label'] : '';

				$multi_field['type']        = $multi_field['type'] . '-state';
				$multi_field['element_id']  = $multi_field['element_id'] . '-state';
				$multi_field['field_label'] = ( $label ? $label : $default_label );

				$multi_fields [] = $multi_field;
			}

			if ( $zip_enabled ) {
				$multi_field = $field_array;

				$default_label = Forminator_Form_Entry_Model::translate_suffix( 'zip' );
				$label         = isset( $multi_field['address_zip_label'] ) ? $multi_field['address_zip_label'] : '';

				$multi_field['type']        = $multi_field['type'] . '-zip';
				$multi_field['element_id']  = $multi_field['element_id'] . '-zip';
				$multi_field['field_label'] = ( $label ? $label : $default_label );

				$multi_fields [] = $multi_field;
			}

			if ( $country_enabled ) {
				$multi_field = $field_array;

				$default_label = Forminator_Form_Entry_Model::translate_suffix( 'country' );
				$label         = isset( $multi_field['address_country_label'] ) ? $multi_field['address_country_label'] : '';

				$multi_field['type']        = $multi_field['type'] . '-country';
				$multi_field['element_id']  = $multi_field['element_id'] . '-country';
				$multi_field['field_label'] = ( $label ? $label : $default_label );

				$multi_fields [] = $multi_field;
			}

			return $multi_fields;
		}
	}

	return false;
}

/**
 * Formatted submmiited data of Form to used by addon
 *
 * @since 1.1
 * @since 1.3 add entry fields as parameter to trace back submit data to entry fields to be added
 *
 * @param array $form_fields          Existing form fields.
 * @param array $current_entry_fields Current entry fields.
 *
 * @return array
 */
function forminator_format_submitted_data_for_addon( $form_fields, $current_entry_fields = array() ) {
	$files_data          = $_FILES; // phpcs:ignore WordPress.Security.NonceVerification.Missing
	$formatted_post_data = array();

	$render_id = filter_input( INPUT_POST, 'render_id', FILTER_VALIDATE_INT );
	if ( $render_id ) {
		$formatted_post_data['render_id'] = $render_id;
	}

	$page_id = filter_input( INPUT_POST, 'page_id', FILTER_VALIDATE_INT );
	if ( isset( $page_id ) ) {
		$formatted_post_data['page_id'] = $page_id;
	}

	$current_url = filter_input( INPUT_POST, 'current_url', FILTER_VALIDATE_URL );
	if ( $current_url ) {
		$formatted_post_data['current_url'] = $current_url;
	}

	$_wp_http_referer = filter_input( INPUT_POST, '_wp_http_referer', FILTER_VALIDATE_URL );
	if ( $_wp_http_referer ) {
		$formatted_post_data['_wp_http_referer'] = $_wp_http_referer;
	}

	$skipped_keys = array( 'forminator_nonce', 'form_id', 'action' );

	// loop on form fields.
	foreach ( $form_fields as $form_field ) {
		if ( isset( $_POST[ $form_field['element_id'] ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			if ( strpos( $form_field['type'], 'category' ) !== false || strpos( $form_field['type'], 'tag' ) !== false ) {
				$form_value = '';
				if ( is_array( $_POST[ $form_field['element_id'] ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
					$form_post_data = filter_input( INPUT_POST, $form_field['element_id'], FILTER_VALIDATE_INT, FILTER_REQUIRE_ARRAY );
				} else {
					$form_post_data = array( filter_input( INPUT_POST, $form_field['element_id'], FILTER_VALIDATE_INT ) );
				}
				if ( ! empty( $form_post_data ) ) {
					foreach ( $form_post_data as $form_post ) {
						$form_value .= get_term_by( 'term_taxonomy_id', $form_post )->name . ' (ID=' . $form_post . '), ';
					}
				}
				$value = substr( $form_value, 0, -2 );
			} else {
				$value = Forminator_Core::sanitize_array( $_POST[ $form_field['element_id'] ], $form_field['element_id'] );// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput
			}
			$formatted_post_data[ $form_field['element_id'] ] = $value;
		} elseif ( 'time' === $form_field['type'] ) {

				// need to be concatenated.
				$element_id         = $form_field['element_id'];
				$hours_element_id   = $element_id . '-hours';
				$minutes_element_id = $element_id . '-minutes';
				$ampm_element_id    = $element_id . '-ampm';
				$hours              = filter_input( INPUT_POST, $hours_element_id, FILTER_VALIDATE_INT );
				$minutes            = filter_input( INPUT_POST, $minutes_element_id, FILTER_VALIDATE_INT );
			if ( $hours && $minutes ) {
				$data = array(
					'hours'   => $hours,
					'minutes' => $minutes,
				);

				$ampm = Forminator_Core::sanitize_text_field( $ampm_element_id );
				if ( $ampm ) {
					$data['ampm'] = $ampm;
				}

				$time = Forminator_Form_Entry_Model::meta_value_to_string( $form_field['type'], $data );

				$formatted_post_data[ $form_field['element_id'] ] = $time;

				$skipped_keys = array_merge( $skipped_keys, array( $hours_element_id, $minutes_element_id, $ampm_element_id ) );
			}
		} elseif ( 'signature' === $form_field['type'] ) {
			$fields_data = wp_list_pluck( $current_entry_fields, 'value', 'name' );
			if ( ! empty( $form_field['element_id'] )
				&& ! empty( $fields_data[ $form_field['element_id'] ] )
				&& ! empty( $fields_data[ $form_field['element_id'] ]['file'] )
				&& ! empty( $fields_data[ $form_field['element_id'] ]['file']['file_url'] )
					) {
				$formatted_post_data[ $form_field['element_id'] ] = $fields_data[ $form_field['element_id'] ]['file']['file_url'];
			}
		} elseif ( 'date' === $form_field['type'] ) {
			$element_id       = $form_field['element_id'];
			$day_element_id   = $element_id . '-day';
			$month_element_id = $element_id . '-month';
			$year_element_id  = $element_id . '-year';

			$day   = Forminator_Core::sanitize_text_field( $day_element_id );
			$month = Forminator_Core::sanitize_text_field( $month_element_id );
			$year  = Forminator_Core::sanitize_text_field( $year_element_id );
			if ( $day && $month && $year ) {
				$data = array(
					'day'   => $day,
					'month' => $month,
					'year'  => $year,
				);
				if ( ! empty( $form_field['date_format'] ) ) {
					$data['format'] = datepicker_default_format( $form_field['date_format'] );
				}

				$date = Forminator_Form_Entry_Model::meta_value_to_string( $form_field['type'], $data );

				$formatted_post_data[ $form_field['element_id'] ] = $date;

				$skipped_keys = array_merge( $skipped_keys, array( $day_element_id, $month_element_id, $year_element_id ) );
			}
		} elseif ( isset( $files_data[ $form_field['element_id'] ] ) ) {
			// $_FILES.
			$formatted_post_data[ $form_field['element_id'] ] = $files_data[ $form_field['element_id'] ];

			foreach ( $current_entry_fields as $current_entry_field ) {

				if ( isset( $current_entry_field['name'] ) && $form_field['element_id'] === $current_entry_field['name'] ) {
					if ( isset( $current_entry_field['value'] ) && isset( $current_entry_field['value']['file'] ) ) {
						$file_props                                       = $current_entry_field['value']['file'];
						$formatted_post_data[ $form_field['element_id'] ] = array_merge( $formatted_post_data[ $form_field['element_id'] ], $file_props );
						break;
					}
				}

				if ( isset( $current_entry_field['value']['value'] ) ) {
					foreach ( $current_entry_field['value']['value'] as $key => $item ) {
						if ( isset( $current_entry_field['name'] ) && $form_field['element_id'] === $current_entry_field['name'] . '-' . $key ) {
							if ( isset( $item['uploaded_file'][0] ) ) {
								$file_direct_link                                 = $item['uploaded_file'][0];
								$formatted_post_data[ $form_field['element_id'] ] = $file_direct_link;
								break;
							}
						}
					}
				}
			}

			// For ajax multi-upload.
		} elseif (
				'upload' === $form_field['type'] &&
				'multiple' === $form_field['file-type'] &&
				( ! isset( $form_field['upload-method'] ) || 'ajax' === $form_field['upload-method'] )
			) {
			$entry_key = array_search( $form_field['element_id'], array_column( $current_entry_fields, 'name' ), true );
			if ( false !== $entry_key && ! empty( $current_entry_fields[ $entry_key ] ) ) {
				$formatted_post_data[ $form_field['element_id'] ] = implode( ',', $current_entry_fields[ $entry_key ]['value']['file']['file_url'] );
			}
		}
	}

	// add left-over superglobal POST.
	foreach ( $_POST as $key => $post_datum ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! isset( $formatted_post_data[ $key ] ) && ! in_array( $key, $skipped_keys, true ) ) {
			$formatted_post_data[ $key ] = Forminator_Core::sanitize_array( $post_datum, $key );
		}
	}

	// add left-over $_FILES.
	foreach ( $files_data as $key => $files_datum ) {
		if ( ! isset( $formatted_post_data[ $key ] ) ) {
			$formatted_post_data[ $key ] = $files_datum;
		}
	}

	/**
	 * Filter formatted form submmitted data to be used by addon
	 *
	 * @since 1.1
	 *
	 * @param array $formatted_post_data current formatted post data.
	 * @param array $form_fields         form fields that exist on the form.
	 */
	$formatted_post_data = apply_filters( 'forminator_addon_formatted_submitted_data', $formatted_post_data, $form_fields );

	return $formatted_post_data;
}

/**
 * Format form settings to used by addon
 *
 * @since 1.1
 *
 * @param Forminator_Base_Form_Model $custom_form Base form model.
 *
 * @return array formatted and filtered form settings
 */
function forminator_addon_format_form_settings( Forminator_Base_Form_Model $custom_form ) {
	$form_settings = $custom_form->settings;

	/**
	 * Filter form settings to used by addon
	 *
	 * It will be used by all Addons
	 *
	 * @since 1.1
	 *
	 * @param array                      $form_settings Current formatted form_settings.
	 * @param Forminator_Base_Form_Model $custom_form   Custom Form Model.
	 */
	$form_settings = apply_filters( 'forminator_addon_formatted_form_settings', $form_settings, $custom_form );

	return $form_settings;
}

/**
 * Find addon meta data from entry model that saved on db
 *
 * @since 1.1
 *
 * @param Forminator_Integration      $connected_addon Connected addon.
 * @param Forminator_Form_Entry_Model $entry_model Form entry model.
 *
 * @return array
 */
function forminator_find_addon_meta_data_from_entry_model( Forminator_Integration $connected_addon, Forminator_Form_Entry_Model $entry_model ) {
	$addon_meta_data        = array();
	$slug                   = $connected_addon->get_slug();
	$addon_meta_data_prefix = 'forminator_addon_' . $slug . '_';
	foreach ( $entry_model->meta_data as $key => $meta_datum ) {
		$addon_info_exist = false !== stripos( $key, $addon_meta_data_prefix );
		if ( ! $addon_info_exist && 'webhook' === $slug
				&& false !== stripos( $key, 'forminator_addon_zapier_' ) ) {
			// Old submission Zapier information.
			$addon_info_exist = true;
		}
		if ( $addon_info_exist ) {
			$addon_meta_data[] = array(
				'title'     => $connected_addon->get_title(),
				'name'      => str_ireplace( $addon_meta_data_prefix, '', $key ),
				'value'     => $meta_datum['value'],
				'banner'    => $connected_addon->get_image(),
				'banner_x2' => $connected_addon->get_image_x2(),
			);
		}
	}

	/**
	 * Filter addon's meta data retrieved from db
	 *
	 * @since 1.1
	 *
	 * @param array                       $addon_meta_data        Current addon meta data retrieved from db.
	 * @param Forminator_Integration   $connected_addon Connected addon.
	 * @param Forminator_Form_Entry_Model $entry_model Entry model.
	 * @param string                      $addon_meta_data_prefix default prefix of connected addon meta data key.
	 */
	$addon_meta_data = apply_filters( 'forminator_addon_meta_data_from_entry_model', $addon_meta_data, $connected_addon, $entry_model, $addon_meta_data_prefix );

	return $addon_meta_data;
}

/**
 * Generate Html for **single** addon
 *
 * Used on Integrations page, and Form Settings Integration Tab
 *
 * @param array  $addon     that already formatted to_array.
 * @param int    $module_id Module Id.
 * @param string $module_slug Module type.
 * @param bool   $show_pro_info Show PRO info.
 * @param bool   $is_active (show as active addon ?).
 *
 * @return string
 */
function forminator_addon_row_html_markup( $addon, $module_id, $module_slug = 'form', $show_pro_info = true, $is_active = true ) {
	ob_start();

	$single_addon_template_path = forminator_plugin_dir() . 'admin/views/integrations/addon.php';

	/**
	 * Filter Template path of single addon html
	 *
	 * @since 1.1
	 *
	 * @param string $single_addon_template_path current used path.
	 */
	$single_addon_template_path = apply_filters( 'forminator_addon_single_' . $module_slug . '_addon_template_path', $single_addon_template_path );

	/* @noinspection PhpIncludeInspection */
	include $single_addon_template_path;

	$html = ob_get_clean();

	/**
	 * Filter displayed html **single** addon
	 *
	 * @since 1.1
	 * @since 1.5.1 add $is_active
	 *
	 * @param string $html          current html to be displayed.
	 * @param array  $addon         addon instance that already formatted to_array.
	 * @param int    $module_id
	 * @param bool   $show_pro_info whether to show pro info.
	 */
	$html = apply_filters( 'forminator_addon_' . $module_slug . '_row_html', $html, $addon, $module_id, $show_pro_info, $is_active );

	return $html;
}

/**
 * Add log of forminator addon related if permitted
 *
 * To be this can be active :
 * - WP_DEBUG : true
 * - FORMINATOR_ADDON_DEBUG : true
 * - forminator_addon_enable_log : true
 *
 * @see   forminator_maybe_log()
 *
 * @since 1.1
 * @since 1.3 add FORMINATOR_ADDON_DEBUG as enabled flag
 */
function forminator_addon_maybe_log() {
	$enabled = ( defined( 'FORMINATOR_ADDON_DEBUG' ) && FORMINATOR_ADDON_DEBUG );

	/**
	 * Filter log enable for forminator addon
	 *
	 * By default it will check `WP_DEBUG`, `FORMINATOR_ADDON_DEBUG`, `FORMINATOR_DEBUG` must be true
	 *
	 * @since 1.1
	 *
	 * @param bool $enabled current enable status.
	 */
	$enabled = apply_filters( 'forminator_addon_enable_log', $enabled );

	if ( $enabled ) {
		if ( is_callable( 'forminator_maybe_log' ) ) {
			$args  = array( '[ADDON]' );
			$fargs = func_get_args();
			$args  = array_merge( $args, $fargs );
			call_user_func_array( 'forminator_maybe_log', $args );
		}
	}
}

/**
 * Replacer custom form vars
 *
 * @since 1.2
 *
 * @param string                $content Content.
 * @param array                 $submitted_data Submitted data.
 * @param Forminator_Form_Model $custom_form Form model.
 * @param array                 $entry_meta Entry meta.
 * @param bool                  $allow_html Allow HTML.
 * @param mixed                 $entry Entry.
 *
 * @return mixed|string
 */
function forminator_addon_replace_custom_vars( $content, $submitted_data, Forminator_Form_Model $custom_form, $entry_meta, $allow_html = false, $entry = null ) {
	$entry_model = new Forminator_Form_Entry_Model();
	foreach ( $entry_meta as $meta ) {
		if ( isset( $meta['name'] ) ) {
			$entry_model->meta_data[ $meta['name'] ] = array(
				'id'    => $meta['name'],
				'value' => wp_unslash( $meta['value'] ),
			);
		}
	}

	$content = forminator_replace_variables( $content, $custom_form->id, $entry );
	$content = forminator_replace_custom_form_data( $content, $custom_form, $entry_model );

	$field_types = Forminator_Core::get_field_types();

	// sort by length, so stripos will work by traverse from longest field type first.
	$field_types_strlen = array_map( 'strlen', $field_types );
	array_multisort( $field_types_strlen, $field_types, SORT_DESC );
	$field_types = array_reverse( $field_types );

	$randomed_field_pattern  = 'field-\d+-\d+';
	$increment_field_pattern = sprintf( '(%s)-\d+', implode( '|', $field_types ) );
	$pattern                 = '/\{((' . $randomed_field_pattern . ')|(' . $increment_field_pattern . '))(\-[A-Za-z-_]+)?\}/';

	// Find all field ID's.
	if ( preg_match_all( $pattern, $content, $matches ) ) {
		if ( ! isset( $matches[0] ) || ! is_array( $matches[0] ) ) {
			return $content;
		}
		foreach ( $matches[0] as $match ) {
			$element_id = forminator_clear_field_id( $match );

			$meta_value = array();
			$field_type = Forminator_Core::get_field_type( $element_id );

			if ( isset( $entry_model->meta_data[ $element_id ] ) ) {
				$meta_value = $entry_model->meta_data[ $element_id ]['value'];
			} elseif ( isset( $submitted_data[ $element_id ] ) ) {
				$meta_value = $submitted_data[ $element_id ];
			}

			$value = Forminator_Form_Entry_Model::meta_value_to_string( $field_type, $meta_value, $allow_html );

			$content = str_replace( $match, $value, $content );
		}
	}

	return $content;
}

/**
 * Get admin url of addon integration page
 *
 * @since 1.2
 *
 * @param mixed  $addon Addon.
 * @param mixed  $section Section.
 * @param bool   $with_nonce With nonce.
 * @param string $identifier Identifier.
 *
 * @return string
 */
function forminator_addon_integration_section_admin_url( $addon, $section, $with_nonce = true, $identifier = '' ) {
	$admin_url  = admin_url( 'admin.php' );
	$query_args = array(
		'page'    => 'forminator-integrations',
		'slug'    => $addon->get_slug(),
		'section' => $section,
	);

	if ( $addon->is_multi_global ) {
		if ( $identifier ) {
			$query_args['identifier'] = rawurlencode( $identifier );
		}
		$query_args['global_id'] = $addon->multi_global_id;
	}

	if ( $with_nonce ) {
		if ( ! class_exists( 'Forminator_Integrations_Page' ) ) {
			return '';
		}
		$nonce               = Forminator_Integrations_Page::get_addon_page_nonce();
		$query_args['nonce'] = $nonce;
	}

	return add_query_arg(
		$query_args,
		$admin_url
	);
}

/**
 * Get Registered Addons
 *
 * @since 1.5.3
 *
 * @return Forminator_Integration[]
 */
function forminator_get_registered_addons() {
	$addons            = array();
	$registered_addons = Forminator_Integration_Loader::get_instance()->get_addons();

	foreach ( $registered_addons as $slug => $registered_addon ) {
		$addon = forminator_get_addon( $slug );
		if ( $addon instanceof Forminator_Integration ) {
			$addons[ $addon->get_slug() ] = $addon;
		}
	}

	return $addons;
}

/**
 * Find addon meta data from entry model that saved on db
 *
 * @since 1.5.3
 *
 * @param Forminator_Form_Entry_Model $entry_model Form entry model.
 *
 * @return array
 */
function forminator_find_addon_slugs_from_entry_model( Forminator_Form_Entry_Model $entry_model ) {
	$addon_slugs                   = array();
	$addon_metadata_pattern_prefix = 'forminator_addon_';
	foreach ( $entry_model->meta_data as $key => $meta_datum ) {
		if ( false !== stripos( $key, $addon_metadata_pattern_prefix ) ) {
			$key  = str_ireplace( $addon_metadata_pattern_prefix, '', $key );
			$keys = explode( '_', $key, 2 );
			if ( isset( $keys[0] ) ) {
				$slug = $keys[0];
				if ( ! in_array( $slug, $addon_slugs, true ) ) {
					$addon_slugs[] = $slug;
				}
			}
		}
	}

	/**
	 * Filter addons slug from entry model retrieved from db
	 *
	 * @since 1.5.3
	 *
	 * @param Forminator_Form_Entry_Model $entry_model
	 * @param string                      $addon_meta_data_prefix default prefix of connected addon meta data key.
	 */
	$addon_slugs = apply_filters( 'forminator_addon_slugs_from_entry_model', $addon_slugs, $entry_model, $addon_metadata_pattern_prefix );

	return $addon_slugs;
}

/**
 * Format poll settings to used by addon
 *
 * @since 1.6.1
 *
 * @param Forminator_Base_Form_Model $poll Base form model.
 *
 * @return array formatted and filtered form settings
 */
function forminator_addon_format_poll_settings( Forminator_Base_Form_Model $poll ) {
	$poll_settings = $poll->settings;

	/**
	 * Filter poll settings to used by addon
	 *
	 * It will be used by all Addons
	 *
	 * @since 1.6.1
	 *
	 * @param array                      $poll_settings Current formatted form_settings.
	 * @param Forminator_Base_Form_Model $poll          Custom Form Model.
	 */
	$poll_settings = apply_filters( 'forminator_addon_formatted_poll_settings', $poll_settings, $poll );

	return $poll_settings;
}

/**
 * Format Poll Fields (contains poll answers)
 *
 * @since 1.6.1
 *
 * @param Forminator_Base_Form_Model $poll Base form model.
 *
 * @return array
 */
function forminator_addon_format_poll_fields( Forminator_Base_Form_Model $poll ) {
	$formatted_fields = array();
	$fields           = $poll->get_fields();

	foreach ( $fields as $field ) {
		$field_as_array     = $field->to_formatted_array();
		$formatted_fields[] = $field_as_array;
	}

	/**
	 * Filter formatted fields to be used by addon
	 *
	 * This value will be used by **ALL** addons
	 *
	 * @since 1.1
	 *
	 * @param array                        $formatted_fields current formatted fields.
	 * @param Forminator_Form_Model $poll             Custom form Model.
	 */
	$formatted_fields = apply_filters( 'forminator_addon_formatted_poll_fields', $formatted_fields, $poll );

	return $formatted_fields;
}

/**
 * Format quiz settings to used by addon
 *
 * @since 1.6.2
 *
 * @param Forminator_Quiz_Model $quiz Quiz model.
 *
 * @return array formatted and filtered form settings
 */
function forminator_addon_format_quiz_settings( Forminator_Quiz_Model $quiz ) {
	$quiz_settings = $quiz->settings;

	/**
	 * Filter quiz settings to used by addon
	 *
	 * It will be used by all Addons
	 *
	 * @since 1.6.2
	 *
	 * @param array                      $quiz_settings Current formatted quiz_settings.
	 * @param Forminator_Quiz_Model $quiz          Quiz Model.
	 */
	$quiz_settings = apply_filters( 'forminator_addon_formatted_quiz_settings', $quiz_settings, $quiz );

	return $quiz_settings;
}

/**
 * Lead form data
 *
 * @param array $submitted_data Submitted data.
 *
 * @return Forminator_Form_Entry_Model|null
 */
function forminator_lead_form_data( $submitted_data ) {
	$entry_data = null;
	$data_entry = isset( $submitted_data['entry_id'] ) ? $submitted_data['entry_id'] : 0;
	$entries    = new Forminator_Form_Entry_Model( $data_entry );
	if ( ! empty( $entries ) ) {
		$entry_data = $entries;
	}

	return $entry_data;
}

/**
 * Addons lead submitted data
 *
 * @param array $form_fields Form fields.
 * @param mixed $entries Entries.
 *
 * @return array
 */
function forminator_addons_lead_submitted_data( $form_fields, $entries ) {
	$submitted_data = array();
	if ( ! empty( $form_fields ) && ! empty( $entries->meta_data ) ) {
		foreach ( $form_fields as $form_field ) {
			foreach ( $entries->meta_data as $meta_key => $entry ) {
				if ( is_array( $entry['value'] ) &&
					( strpos( $meta_key, 'postdata-' ) !== false
						|| strpos( $meta_key, 'name-' ) !== false
						|| strpos( $meta_key, 'address-' ) !== false
					) ) {
					if ( strpos( $meta_key, 'postdata-' ) !== false && isset( $entry['value']['value'] ) ) {
						$meta_entry_value = $entry['value']['value'];
					} else {
						$meta_entry_value = $entry['value'];
					}
					foreach ( $meta_entry_value as $entry_key => $entry_value ) {
						$entry_name = $meta_key . '-' . $entry_key;
						if ( $form_field['element_id'] === $entry_name ) {
							$submitted_data[ $entry_name ] = $entry_value;
						}
					}
				} else {
					$submitted_data[ $meta_key ] = $entry['value'];
				}
			}
		}
	}

	return $submitted_data;
}

/**
 * Get lead data
 *
 * @param array $quiz_settings Quiz settings.
 * @param array $submitted_data Submitted data.
 * @param array $addons_fields Addon fields.
 *
 * @return array
 */
function get_addons_lead_form_entry_data( $quiz_settings, $submitted_data, $addons_fields ) {
	if ( isset( $quiz_settings['hasLeads'] ) && $quiz_settings['hasLeads'] ) {
		$entries        = forminator_lead_form_data( $submitted_data );
		$submitted_data = forminator_addons_lead_submitted_data( $addons_fields, $entries );
		if ( ! empty( $addons_fields ) ) {
			foreach ( $addons_fields as $form_field ) {
				if ( array_key_exists( $form_field['element_id'], $submitted_data ) ) {
					$form_value                                  = Forminator_Form_Entry_Model::meta_value_to_string( $form_field['field_type'], $submitted_data[ $form_field['element_id'] ] );
					$submitted_data[ $form_field['element_id'] ] = $form_value;
				}
			}
		}
	}

	return $submitted_data;
}

/**
 * Get quiz data
 *
 * @param mixed $quiz Quiz.
 * @param array $data Data.
 * @param array $quiz_entry_fields Quiz entry fields.
 *
 * @return mixed
 */
function get_quiz_submitted_data( $quiz, $data, $quiz_entry_fields ) {
	if ( is_array( $quiz_entry_fields ) && isset( $quiz_entry_fields[0] ) ) {
		$quiz_entry = $quiz_entry_fields[0];
		if ( isset( $quiz_entry['name'] ) && isset( $quiz_entry['value'] ) && 'entry' === $quiz_entry['name'] ) {
			if ( is_array( $quiz_entry['value'] ) && ! empty( $quiz_entry['value'] ) ) {
				if ( 'knowledge' === $quiz->quiz_type ) {
					$answers              = $quiz_entry['value'];
					$correct_answer_count = 0;
					$total_answer         = 0;
					foreach ( $answers as $answer ) {
						$is_correct = isset( $answer['isCorrect'] ) ? $answer['isCorrect'] : false;
						$is_correct = filter_var( $is_correct, FILTER_VALIDATE_BOOLEAN );
						if ( $is_correct ) {
							++$correct_answer_count;
						}

						++$total_answer;
					}

					$data['correct-answers'] = $correct_answer_count;
					$data['total-answers']   = $total_answer;
				} elseif ( 'nowrong' === $quiz->quiz_type ) {
					$result_content = '';
					$meta           = $quiz_entry['value'];

					// i know its complicated as eff, but this is how it saved since day 1.
					// and migrating this might pita and affect performance.
					if ( isset( $meta[0] ) && isset( $meta[0]['value'] ) && isset( $meta[0]['value']['result'] ) ) {
						$result         = $meta[0]['value']['result'];
						$result_content = isset( $result['title'] ) ? esc_html( (string) $result['title'] ) : '';
					}
					$data['result-answers'] = $result_content;
				}
			}
		}
	}
	$answers = isset( $data['answers'] ) ? $data['answers'] : array();
	if ( is_array( $answers ) && ! empty( $answers ) ) {
		foreach ( $answers as $question_id => $answer_id ) {
			$answer               = $quiz->getAnswer( $question_id, $answer_id );
			$answer_text          = isset( $answer['title'] ) ? $answer['title'] : '';
			$data[ $question_id ] = $answer_text;
		}
	}
	$data['quiz-name'] = forminator_get_name_from_model( $quiz );

	return $data;
}

/**
 * Flag whether doc link should shown or not for addons
 *
 * @since 1.14.2
 * @return bool
 */
function forminator_is_show_addons_documentation_link() {
	_deprecated_function( __FUNCTION__, '1.50', 'forminator_is_show_documentation_link' );
	return forminator_is_show_documentation_link();
}
