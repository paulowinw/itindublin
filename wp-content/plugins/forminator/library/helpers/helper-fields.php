<?php
/**
 * Fields helper functions.
 *
 * @package Forminator
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Return custom form
 *
 * @param int  $id Id.
 * @param bool $is_preview Is preview?.
 * @param bool $hidden Is hidden?.
 *
 * @since 1.0
 * @return mixed
 */
function forminator_form( $id, $is_preview = false, $hidden = true ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
	$view = new Forminator_CForm_Front();

	return $view->render_shortcode(
		array(
			'id'         => $id,
			'is_preview' => $is_preview,
		)
	);
}

/**
 * Return custom form
 *
 * @param int  $id Id.
 * @param bool $is_preview Is preview?.
 * @param bool $hidden Is hidden?.
 *
 * @since 1.0
 * @return mixed
 */
function forminator_poll( $id, $is_preview = false, $hidden = true ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
	$view = new Forminator_Poll_Front();

	return $view->render_shortcode(
		array(
			'id'         => $id,
			'is_preview' => $is_preview,
		)
	);
}

/**
 * Return custom form
 *
 * @param int  $id Id.
 * @param bool $is_preview Is preview?.
 * @param bool $hidden Is hidden?.
 *
 * @since 1.0
 * @return mixed
 */
function forminator_quiz( $id, $is_preview = false, $hidden = true ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
	$view = new Forminator_QForm_Front();

	return $view->render_shortcode(
		array(
			'id'         => $id,
			'is_preview' => $is_preview,
		)
	);
}

/**
 * Return module
 *
 * @param int    $id Id.
 * @param string $type Form type.
 * @param bool   $ajax Is Ajax?.
 * @param mixed  $data Data.
 *
 * @since 1.0
 * @return mixed
 */
function forminator_preview( $id, $type, $ajax = false, $data = false ) {
	$class = 'Forminator_CForm_Front';
	if ( 'poll' === $type ) {
		$class = 'Forminator_Poll_Front';
	} elseif ( 'quiz' === $type ) {
		$class = 'Forminator_QForm_Front';
	}
	$view = new $class();
	$data = forminator_stripslashes_deep( $data );

	return $view->render_shortcode(
		array(
			'id'           => $id,
			'is_preview'   => $ajax,
			'preview_data' => $data,
		)
	);
}

/**
 * Return stripslashed string or array
 *
 * @param string $val Content to strip slashes.
 *
 * @since 1.0
 * @return mixed
 */
function forminator_stripslashes_deep( $val ) {
	$val = is_array( $val ) ? array_map( 'stripslashes_deep', $val ) : stripslashes( $val );

	return $val;
}

/**
 * Sanitize field
 *
 * @since 1.0.2
 *
 * @param mixed       $field Field.
 * @param null|string $key Key.
 *
 * @return array|string
 */
function forminator_sanitize_field( &$field, $key = null ) {
	if ( 'question_description' === $key ) {
		return wp_kses_post( $field );
	}
	// If array map all fields.
	if ( is_array( $field ) ) {
		array_walk( $field, 'forminator_sanitize_field' );
		return $field;
	}

	return sanitize_text_field( $field );
}

/**
 * Forminator sanitation for module settings before saving.
 * Used for sanitizing form integration settings description field.
 *
 * @param array $fields Fields.
 *
 * @return mixed
 */
function forminator_sanitize_array_field( $fields ) {
	$allow_html = array(
		'consent_description',
		'variations',
		'sc_message',
		'hc_invisible_notice',
		'ticket_description',
		'footer_value',
		'value',
	);

	foreach ( $fields as $key => &$value ) {
		if ( is_array( $value ) ) {
			$value = forminator_sanitize_array_field( $value );
		} elseif ( in_array( $key, $allow_html, true ) ) {
				$value = wp_kses_post( $value );
		} elseif ( 'card_description' === $key
						|| 'gdpr_text' === $key
						|| 'message' === $key
			) {
			$value = sanitize_textarea_field( $value );
		} else {
			$value = sanitize_text_field( $value );
		}
	}

	return $fields;
}

/**
 * Forminator decode HTML entity
 *
 * @param array $fields Fields.
 *
 * @return mixed
 */
function forminator_decode_html_entity( $fields ) {
	foreach ( $fields as &$value ) {
		if ( is_array( $value ) ) {
			$value = forminator_decode_html_entity( $value );
		} else {
			$value = wp_specialchars_decode( $value );
		}
	}

	return $fields;
}

/**
 * Sort fields so that grouped fields appear immediately after their parent group.
 *
 * @since 1.51.0
 * @param array $fields Array of field arrays.
 * @return array Sorted fields array.
 */
function forminator_sort_fields_with_groups( array $fields ): array {
	if ( empty( $fields ) ) {
		return $fields;
	}

	$top_level = array();
	$children  = array();

	// Separate top-level fields from grouped (child) fields.
	foreach ( $fields as $field ) {
		$parent_group = $field['parent_group'] ?? '';
		if ( '' === $parent_group ) {
			$top_level[] = $field;
		} else {
			$children[ $parent_group ][] = $field;
		}
	}

	// No need to sort if there are no grouped fields.
	if ( empty( $children ) ) {
		return $top_level;
	}

	// Build sorted array: each group field followed by its children.
	$sorted = array();
	foreach ( $top_level as $field ) {
		$sorted[] = $field;
		$field_id = $field['element_id'] ?? '';
		if ( isset( $children[ $field_id ] ) ) {
			array_push( $sorted, ...$children[ $field_id ] );
		}
	}

	return $sorted;
}

/**
 * Sanitize text area
 *
 * @param string $field Field value.
 *
 * @return string
 */
function forminator_sanitize_textarea( $field ) {

	return sanitize_textarea_field( $field );
}

/**
 * Return the array of ALL fields objects
 *
 * @since 1.0
 * @return mixed
 */
function forminator_get_fields() {
	$forminator = Forminator_Core::get_instance();

	return $forminator->fields;
}

/**
 * Return the array of PRO fields for promotion PRO version
 *
 * @return array
 */
function forminator_get_pro_fields() {
	$forminator = Forminator_Core::get_instance();

	return $forminator->pro_fields;
}

/**
 * Return all existing custom fields
 *
 * @since      1.0
 * @deprecated 1.5.4
 * @return mixed
 */
function forminator_get_existing_cfields() {
	_deprecated_function( 'forminator_get_existing_cfields', '1.5.4' );

	return array();
}

/**
 * Convert array to array compatible with field values
 *
 * @since 1.0
 *
 * @param array $array_values Field values.
 * @param bool  $replace_value Replace value.
 *
 * @return array
 */
function forminator_to_field_array( $array_values, $replace_value = false ) {
	$field_array = array();

	if ( ! empty( $array_values ) ) {
		foreach ( $array_values as $key => $value ) {
			// Use value instead of key.
			if ( $replace_value ) {
				$field_array[] = array(
					'value' => $value,
					'label' => $value,
				);
			} else {
				$field_array[] = array(
					'value' => $key,
					'label' => $value,
				);
			}
		}
	}

	return $field_array;
}

/**
 * Return max upload limit from server
 *
 * @since 1.6
 * @return int Mb
 */
function forminator_get_max_upload() {
	$max_upload = wp_max_upload_size();

	return (int) ( $max_upload / 1000000 ); // convert to mb;.
}

/**
 * Return users list
 *
 * @since 1.6
 * @return array
 */
function forminator_list_users() {
	$users_list = array();
	$page       = Forminator_Core::sanitize_text_field( 'page' );
	if ( 'forminator-cform-wizard' !== $page ) {
		return $users_list;
	}

	$users = get_users(
		array(
			'role__in' => array( 'administrator', 'editor', 'author' ),
			'fields'   => array( 'ID', 'display_name' ),
		)
	);
	foreach ( $users as $user ) {
		$users_list[] = array(
			'value' => $user->ID,
			'label' => ucfirst( $user->display_name ),
		);
	}

	return apply_filters( 'forminator_postdata_users_list', $users_list );
}

/**
 * Return post type list
 *
 * @since 1.7
 * @return array
 */
function forminator_post_type_list() {
	$post_type_list = array();
	$post_types     = get_post_types( array( 'public' => true ), 'objects' );

	unset( $post_types['attachment'] );
	unset( $post_types['revision'] );
	unset( $post_types['nav_menu_item'] );
	unset( $post_types['custom_css'] );
	unset( $post_types['customize_changeset'] );
	unset( $post_types['oembed_cache'] );
	unset( $post_types['user_request'] );
	unset( $post_types['wp_block'] );

	foreach ( $post_types as $post ) {
		$post_type_list[] = array(
			'value' => $post->name,
			'label' => ucfirst( $post->label ),
		);
	}

	return apply_filters( 'forminator_postdata_post_type_list', $post_type_list );
}

/**
 * Return post type Categories
 *
 * @since 1.7
 *
 * @param string $type Type.
 *
 * @return array
 */
function forminator_post_categories( $type = '' ) {
	$categories = array();
	$category   = array();
	$post_types = forminator_post_type_list();

	foreach ( $post_types as $post ) {
		$post_type  = $post['value'];
		$categories = get_object_taxonomies( $post_type, 'objects' );

		if ( ! empty( $categories ) ) {
			foreach ( $categories as $cat ) {
				if ( 'post_format' !== $cat->name ) {
					$category[ $post_type ][] = array(
						'value'    => $cat->name,
						'label'    => ucfirst( $cat->label ),
						'singular' => $cat->labels->singular_name,
					);
				}
			}
		}
		$categories = $category;
	}
	if ( ! empty( $type ) ) {
		$category_list = isset( $categories[ $type ] ) ? $categories[ $type ] : array();
	} else {
		$category_list = $categories;
	}
	return apply_filters( 'forminator_postdata_post_categories', $category_list );
}

/**
 * Return vars
 *
 * @since 1.0
 * @since 1.5 add `user_id`
 *
 * @param bool $for_hidden_field For hidden field.   @since 1.15.6.
 *
 * @return mixed
 */
function forminator_get_vars( $for_hidden_field = false ) {
	$vars_list = array(
		'user_ip'             => esc_html__( 'User IP Address', 'forminator' ),
		'date_mdy'            => esc_html__( 'Date (mm/dd/yyyy)', 'forminator' ),
		'date_dmy'            => esc_html__( 'Date (dd/mm/yyyy)', 'forminator' ),
		'submission_id'       => esc_html__( 'Submission ID', 'forminator' ),
		'submission_time'     => esc_html__( 'Submission Time (hh:mm:ss am/pm, timezone)', 'forminator' ),
		'submission_date'     => esc_html__( 'Submission Date (F d, Y)', 'forminator' ),
		'submission_date_mdy' => esc_html__( 'Submission Date (mm/dd/yyyy)', 'forminator' ),
		'submission_date_dmy' => esc_html__( 'Submission Date (dd/mm/yyyy)', 'forminator' ),
		'embed_id'            => esc_html__( 'Embed Post/Page ID', 'forminator' ),
		'embed_title'         => esc_html__( 'Embed Post/Page Title', 'forminator' ),
		'embed_url'           => esc_html__( 'Embed URL', 'forminator' ),
		'login_url'           => esc_html__( 'Login URL', 'forminator' ),
		'user_agent'          => esc_html__( 'HTTP User Agent', 'forminator' ),
		'refer_url'           => esc_html__( 'HTTP Refer URL', 'forminator' ),
		'user_id'             => esc_html__( 'User ID', 'forminator' ),
		'user_name'           => esc_html__( 'User Display Name', 'forminator' ),
		'user_email'          => esc_html__( 'User Email', 'forminator' ),
		'user_login'          => esc_html__( 'User Login', 'forminator' ),
	);

	if ( $for_hidden_field ) {
		$vars_list['custom_value'] = esc_html__( 'Custom Value', 'forminator' );
		$vars_list['query']        = esc_html__( 'Query Parameter', 'forminator' );
	}

	/**
	 * Filter forminator var list
	 *
	 * @see   forminator_replace_variables()
	 *
	 * @since 1.0
	 *
	 * @param array $vars_list
	 */
	return apply_filters( 'forminator_vars_list', $vars_list );
}

/**
 * Return Stripe vars
 *
 * @since 1.7
 * @return mixed
 */
function forminator_get_payment_vars() {
	$vars_list = array(
		'payment_amount'   => esc_html__( 'Amount', 'forminator' ),
		'payment_currency' => esc_html__( 'Currency', 'forminator' ),
		'payment_mode'     => esc_html__( 'Mode', 'forminator' ),
		'payment_status'   => esc_html__( 'Status', 'forminator' ),
		'transaction_id'   => esc_html__( 'Transaction ID', 'forminator' ),
	);

	/**
	 * Filter forminator Stripe var list
	 *
	 * @since 1.7
	 *
	 * @param array $vars_list
	 */
	return apply_filters( 'forminator_stripe_vars_list', $vars_list );
}

/**
 * Return Stripe subscription vars
 *
 * @since 1.38
 * @return array
 */
function forminator_get_stripe_subscription_vars() {
	$vars_list = array(
		'subscription_id' => esc_html__( 'Subscription ID', 'forminator' ),
	);

	/**
	 * Filter forminator Stripe subscription var list
	 *
	 * @since 1.38
	 *
	 * @param array $vars_list
	 */
	return apply_filters( 'forminator_stripe_subscription_vars_list', $vars_list );
}

/**
 * Return required icon
 *
 * @since 1.0
 * @return string
 */
function forminator_get_required_icon() {
	return '<span class="forminator-required">*</span>';
}

/**
 * Return week days
 *
 * @since 1.0
 * @return array
 */
function forminator_week_days() {
	return apply_filters(
		'forminator_week_days',
		array(
			'sunday'    => esc_html__( 'Sunday', 'forminator' ),
			'monday'    => esc_html__( 'Monday', 'forminator' ),
			'tuesday'   => esc_html__( 'Tuesday', 'forminator' ),
			'wednesday' => esc_html__( 'Wednesday', 'forminator' ),
			'thursday'  => esc_html__( 'Thursday', 'forminator' ),
			'friday'    => esc_html__( 'Friday', 'forminator' ),
			'saturday'  => esc_html__( 'Saturday', 'forminator' ),
		)
	);
}

/**
 * Return name prefixes
 *
 * @since 1.0
 * @return array
 */
function forminator_get_name_prefixes() {
	return apply_filters(
		'forminator_name_prefixes',
		array(
			'Mr'   => esc_html__( 'Mr.', 'forminator' ),
			'Mrs'  => esc_html__( 'Mrs.', 'forminator' ),
			'Ms'   => esc_html__( 'Ms.', 'forminator' ),
			'Mx'   => esc_html__( 'Mx.', 'forminator' ),
			'Miss' => esc_html__( 'Miss', 'forminator' ),
			'Dr'   => esc_html__( 'Dr.', 'forminator' ),
			'Prof' => esc_html__( 'Prof.', 'forminator' ),
		)
	);
}

/**
 * Translate name prefix
 *
 * @param string                     $prefix Prefix.
 * @param Forminator_Form_Model|null $form_model Form model.
 * @return string
 */
function forminator_translate_name_prefix( $prefix, $form_model = null ) {
	if ( is_null( $form_model ) ) {
		return $prefix;
	}
	$print_value = ! empty( $form_model->settings['print_value'] )
			? filter_var( $form_model->settings['print_value'], FILTER_VALIDATE_BOOLEAN ) : false;
	if ( $print_value ) {
		return $prefix;
	}
	$prefixes = forminator_get_name_prefixes();
	if ( isset( $prefixes[ $prefix ] ) ) {
		return $prefixes[ $prefix ];
	}
	return $prefix;
}

/**
 * Return field id by string
 *
 * @since 1.0
 *
 * @param string $field_id Field id.
 *
 * @return mixed
 */
function forminator_clear_field_id( $field_id ) {
	$field_id = str_replace( '{', '', $field_id );
	$field_id = str_replace( '}', '', $field_id );

	return $field_id;
}

/**
 * Return filtered editor content with form data
 *
 * @since 1.0
 * @param string                           $content Content.
 * @param Forminator_Form_Model|null       $custom_form Forminator_Form_Model.
 * @param Forminator_Form_Entry_Model|null $entry Forminator_Form_Entry_Model.
 * @param bool                             $get_labels Optional. Set true for getting labels instead of values for select, radio and checkbox.
 * @param bool                             $urlencode Encode URL.
 * @param mixed                            $user_meta User meta.
 * @param bool                             $is_pdf Is PDF.
 * @return mixed
 */
function forminator_replace_form_data( $content, ?Forminator_Form_Model $custom_form = null, ?Forminator_Form_Entry_Model $entry = null, $get_labels = false, $urlencode = false, $user_meta = false, $is_pdf = false ) {
	if ( is_null( $content ) ) {
		$content = '';
	}

	$data             = Forminator_CForm_Front_Action::$prepared_data;
	$matches          = array();
	$field_types      = Forminator_Core::get_field_types();
	$original_content = $content;

	if ( $is_pdf ) {
		$data = recreate_prepared_data( $custom_form, $entry );
	}

	$content = forminator_replace_form_payment_data( $content, $custom_form, $entry );

	$randomed_field_pattern  = 'field-\d+-\d+';
	$increment_field_pattern = sprintf( '(%s)-\d+', implode( '|', $field_types ) );

	$pattern = '/\{((' . $randomed_field_pattern . ')|(' . $increment_field_pattern . '))(\-[A-Za-z0-9-_]+)?(\-\*)?\}/';

	// Find all field ID's.
	if ( preg_match_all( $pattern, $content, $matches ) ) {
		if ( ! isset( $matches[0] ) || ! is_array( $matches[0] ) ) {
			return $content;
		}
		foreach ( $matches[0] as $match ) {
			$element_id = forminator_clear_field_id( $match );
			// For repeated fields.
			if ( '-*' === substr( $element_id, -2 ) ) {
				$element_id = substr( $element_id, 0, -2 );

				// Get matched repeated elements from the form data.
				$available_repeated_elements = array_filter(
					$data,
					function ( $key ) use ( $element_id ) {
						$split_element = explode( '-', $element_id );
						if ( in_array( $split_element[0], array( 'address', 'time', 'date', 'slider', 'name' ), true ) ) {
							if ( 2 === count( $split_element ) ) {
								// For cases like address-1-* and time-1-*.
								$split_key  = explode( '-', $key );
								$third_item = isset( $split_key[2] ) ? $split_key[2] : '';
								if ( isset( $split_key[3] ) ) {
									return false;
								}
								return strpos( $key, $element_id . '-' ) === 0 && intval( $third_item ) > 0;
							} else {
								// For cases like address-1-city-* and time-1-hours-*.
								$start = implode( '-', array_slice( $split_element, 0, 2 ) );
								$end   = implode( '-', array_slice( $split_element, 2 ) );

								// For cases like address-1-2-city, address-1-3-city [saved entry format].
								$pattern_number_in_middle = sprintf( '/^%s-\d+-%s$/', preg_quote( $start, '/' ), preg_quote( $end, '/' ) );
								// For cases like address-1-city-2, address-1-city-3 [form post-data format].
								$pattern_number_at_end = sprintf( '/^%s-%s-\d+$/', preg_quote( $start, '/' ), preg_quote( $end, '/' ) );
								return preg_match( $pattern_number_in_middle, $key ) || preg_match( $pattern_number_at_end, $key );
							}
						} else {
							return strpos( $key, $element_id . '-' ) === 0;
						}
					},
					ARRAY_FILTER_USE_KEY
				);

				// Value of first repeated field.
				$value = forminator_get_value_from_form_entry( $element_id, $custom_form, $entry, $get_labels, $urlencode, $user_meta, $is_pdf );

				$repeated_field_values = array();
				if ( ! empty( $available_repeated_elements ) ) {
					if ( '' !== $value ) {
						$repeated_field_values[] = $value;
					}
					foreach ( array_keys( $available_repeated_elements ) as $repeated_element_id ) {
						$field_value = forminator_get_value_from_form_entry( $repeated_element_id, $custom_form, $entry, $get_labels, $urlencode, $user_meta, $is_pdf );
						if ( '' !== $field_value ) {
							$repeated_field_values[] = $field_value;
						}
					}
					if ( ! empty( $repeated_field_values ) ) {
						$value = '<p>' . implode( '</p><p>', $repeated_field_values ) . '</p>';
					}
				}
			} else {
				$value = forminator_get_value_from_form_entry( $element_id, $custom_form, $entry, $get_labels, $urlencode, $user_meta, $is_pdf );
			}

			// If null, set to empty string.
			if ( is_null( $value ) ) {
				$value = '';
			}
			if ( ! $urlencode ) {
				$content = forminator_replace_placeholder_in_urls( $content, $match, $value );
			}
			$content = str_replace( $match, $value, $content );
		}
	}

	return apply_filters( 'forminator_replace_form_data', $content, $data, $original_content );
}

/**
 * Get value from from entry
 *
 * @since 1.46
 * @param string                           $element_id Field Id.
 * @param Forminator_Form_Model|null       $custom_form Forminator_Form_Model.
 * @param Forminator_Form_Entry_Model|null $entry Forminator_Form_Entry_Model.
 * @param bool                             $get_labels Optional. Set true for getting labels instead of values for select, radio and checkbox.
 * @param bool                             $urlencode Encode URL.
 * @param mixed                            $user_meta User meta.
 * @param bool                             $is_pdf Is PDF.
 * @return mixed
 */
function forminator_get_value_from_form_entry( $element_id, ?Forminator_Form_Model $custom_form = null, ?Forminator_Form_Entry_Model $entry = null, $get_labels = false, $urlencode = false, $user_meta = false, $is_pdf = false ) {
	$value       = '';
	$print_value = ! empty( $custom_form->settings['print_value'] )
			? filter_var( $custom_form->settings['print_value'], FILTER_VALIDATE_BOOLEAN ) : false;
	$data        = Forminator_CForm_Front_Action::$prepared_data;
	if ( $is_pdf ) {
		$data = recreate_prepared_data( $custom_form, $entry );
	}
	// For HTML field we get the relevant field label instead of field value for select, radio and checkboxes and for them themselves.
	if ( $get_labels && ( strpos( $element_id, 'radio' ) === 0
		|| strpos( $element_id, 'select' ) === 0
		|| strpos( $element_id, 'checkbox' ) === 0
		) ) {
		$value = forminator_replace_field_data( $custom_form, $element_id, $data, $is_pdf, $print_value );
	} elseif ( ( strpos( $element_id, 'postdata' ) !== false
			|| strpos( $element_id, 'upload' ) !== false
			|| strpos( $element_id, 'html' ) !== false
			|| strpos( $element_id, 'section' ) !== false
			|| strpos( $element_id, 'signature' ) !== false )
		&& $custom_form && $entry ) {
		$value = forminator_get_field_from_form_entry( $element_id, $custom_form, $entry, $user_meta );

		if ( strpos( $element_id, 'html' ) !== false ) {
			$value = forminator_replace_form_data( $value, $custom_form, $entry, $get_labels );
		}
	} elseif ( isset( $data[ $element_id ] ) ) {

		if ( strpos( $element_id, 'number' ) !== false
			|| strpos( $element_id, 'currency' ) !== false
			|| strpos( $element_id, 'calculation' ) !== false ) {
			$field = $custom_form->get_field( $element_id, true );
			$value = Forminator_Field::forminator_number_formatting( $field, $data[ $element_id ] );
		} elseif (
			false !== stripos( $element_id, 'time' ) &&
			( false !== stripos( $element_id, '-hours' ) || false !== stripos( $element_id, '-minutes' ) )
		) {
			$value = str_pad( $data[ $element_id ], 2, '0', STR_PAD_LEFT );
		} elseif ( false !== stripos( $element_id, 'hidden' )
			&& 'submission_id' === $data[ $element_id ] ) {
			$value = forminator_get_submission_id( $custom_form, $entry );
		} else {
			$value = $data[ $element_id ];
		}
	} elseif ( false !== stripos( $element_id, 'date' ) ) {
		// element with suffixes, etc.
		// use submitted `data` since its possible to disable DB storage,.
		// causing Forminator_Form_Entry_Model = nothing.
		// and cant be used as reference.

		// DATE.
		$day_element_id    = $element_id . '-day';
		$month_element_id  = $element_id . '-month';
		$year_element_id   = $element_id . '-year';
		$format_element_id = $element_id . '-format';

		if ( isset( $data[ $day_element_id ] ) && isset( $data[ $month_element_id ] ) && isset( $data[ $year_element_id ] ) ) {
			$meta_value = array(
				'day'    => $data[ $day_element_id ],
				'month'  => $data[ $month_element_id ],
				'year'   => $data[ $year_element_id ],
				'format' => $data[ $format_element_id ],
			);
			$value      = Forminator_Form_Entry_Model::meta_value_to_string( 'date', $meta_value, true );
		}
	}
	if ( false !== strpos( $element_id, 'group' ) ) {
		$value = forminator_prepare_formatted_group_field( $element_id, $custom_form, $entry );
	}

	// If array, convert it to string.
	if ( is_array( $value ) ) {
		$value = implode( ', ', $value );
	}
	if ( $urlencode ) {
		$value = rawurlencode( $value );
	}

	return $value;
}

/**
 * Replace select, checkbox and radio fields data
 *
 * @since 1.23.0
 *
 * @param Forminator_Form_Model $custom_form Forminator_Form_Model.
 * @param string                $element_id Element Id.
 * @param array                 $data Data.
 * @param bool                  $is_pdf Is PDF.
 * @param bool                  $print_value Whether to show values instead of labels.
 *
 * @return mixed
 */
function forminator_replace_field_data( $custom_form, $element_id, $data, $is_pdf = false, $print_value = false ) {
	$field_value = isset( $data[ $element_id ] ) ? $data[ $element_id ] : null;
	$value       = '';
	if ( ! is_null( $field_value ) ) {
		$form_fields   = $custom_form ? $custom_form->get_fields() : array();
		$fields_slugs  = wp_list_pluck( $form_fields, 'slug' );
		$parent_id     = preg_replace( '/(-[^-]+)-[^-]+$/', '$1', $element_id );
		$field_key     = array_search( $parent_id, $fields_slugs, true );
		$field_options = array();
		if ( false !== $field_key && ! empty( $form_fields[ $field_key ]->raw['options'] ) ) {
			// Include both label and image data for each option.
			foreach ( $form_fields[ $field_key ]->raw['options'] as $option ) {
				$field_options[ $option['value'] ] = array(
					'label' => $option['label'],
					'image' => isset( $option['image'] ) ? $option['image'] : '',
				);
			}
		}
		if ( $is_pdf ) {
			// Since PDFs use entry meta which is already from the database, it has been processed already.
			if ( is_array( $field_value ) && isset( $field_value['value'] ) ) {
				$value = $field_value['value'];
			} else {
				$value = $field_value;
			}
		} else {
			$selected_values = is_array( $field_value ) ? $field_value : array( $field_value );
			$selected_values = array_map( 'htmlspecialchars_decode', $selected_values );
			$field           = $custom_form->get_field( $element_id, true );

			// Check if we should display images for radio/checkbox fields.
			$enable_images = Forminator_Field::get_property( 'enable_images', $field, false, 'bool' );

			// Check if we're in email context using parent class static property.
			$is_email = false;
			if ( class_exists( 'Forminator_Mail' ) && method_exists( 'Forminator_Mail', 'is_email_context' ) ) {
				$is_email = Forminator_Mail::is_email_context();
			}

			if ( ! empty( $field_options ) ) {
				$display_items              = array();
				$email_image_display_option = $custom_form->settings['email_image_display_option'] ?? 'preview';
				foreach ( $selected_values as $selected_value ) {
					$selected_value = stripslashes( $selected_value );

					if ( isset( $field_options[ $selected_value ] ) ) {
						$option_data  = $field_options[ $selected_value ];
						$display_text = $print_value ? $selected_value : $option_data['label'];

						// Handle custom option text enhancement.
						if ( 'custom_option' === $selected_value ) {
							$enable_custom_option = Forminator_Field::get_property( 'enable_custom_option', $field, false );
							$custom_value         = $data[ 'custom-' . $element_id ] ?? '';
							// Append the custom input value for the "Other" option.
							if ( $enable_custom_option && '' !== $custom_value ) {
								$display_text .= ': ' . $custom_value;
							}
						}

						// Apply image markup if enabled and this is for email.
						if ( $enable_images && $is_email && 'preview' === $email_image_display_option && ! empty( $option_data['image'] ) ) {
							$display_items[] = forminator_get_email_image_markup( $option_data['image'], $display_text, $display_text, 'field' );
						} else {
							$display_items[] = $display_text;
						}
					} else {
						$display_items[] = $selected_value;
					}
				}
				if ( $enable_images && $is_email ) {
					// To display images on new lines in emails.
					$value = implode( '<br/>', $display_items );
				} else {
					$value = implode( ', ', $display_items );
				}
			}
		}
	}

	return $value;
}

/**
 * Format custom form data variables to html formatted
 *
 * @since 1.0.3
 *
 * @param string                      $content Content.
 * @param Forminator_Form_Model       $custom_form Forminator_Form_Model.
 * @param Forminator_Form_Entry_Model $entry Forminator_Form_Entry_Model.
 * @param array                       $excluded Excluded.
 *
 * @return mixed
 */
function forminator_replace_custom_form_data( $content, Forminator_Form_Model $custom_form, Forminator_Form_Entry_Model $entry, $excluded = array() ) {
	$custom_form_datas = array(
		'{all_fields}'            => 'forminator_get_formatted_form_entry',
		'{all_non_empty_fields}'  => 'forminator_get_formatted_form_non_empty_entry',
		'{form_name}'             => 'forminator_get_formatted_form_name',
		'{submission_id}'         => 'forminator_get_submission_id',
		'{submission_url}'        => 'forminator_get_submission_url',
		'{account_approval_link}' => 'forminator_get_account_approval_link',
		'{username}'              => 'forminator_get_formatted_username',
		'{line_break}'            => 'forminator_get_formatted_line_break',
	);

	foreach ( $custom_form_datas as $custom_form_data => $function ) {
		if ( in_array( $custom_form_data, $excluded, true ) ) {
			continue;
		}
		if ( strpos( $content, $custom_form_data ) !== false ) {
			if ( is_callable( $function ) ) {
				$replacer = call_user_func( $function, $custom_form, $entry );
				$content  = forminator_replace_placeholder_in_urls( $content, $custom_form_data, $replacer );
				$content  = str_replace( $custom_form_data, $replacer, $content );
			}
		}
	}

	return apply_filters( 'forminator_replace_custom_form_data', $content, $custom_form, Forminator_CForm_Front_Action::$prepared_data, $entry, $excluded, $custom_form_datas );
}

/**
 * Replace placeholder occurrences inside URLs with provided value.
 *
 * @param string $content Original content.
 * @param string $placeholder Placeholder.
 * @param string $value       Replacement value.
 *
 * @return string Updated content.
 */
function forminator_replace_placeholder_in_urls( string $content, string $placeholder, string $value ): string {
	$value   = rawurlencode( $value );
	$pattern = '#https?://[^\s"\'<>()]*' . preg_quote( $placeholder, '#' ) . '[^\s"\'<>()]*#i';

	return preg_replace_callback(
		$pattern,
		static function ( $matches ) use ( $placeholder, $value ) {
			return str_replace( $placeholder, $value, $matches[0] );
		},
		$content
	);
}

/**
 * Get Html Formatted of form entry for email notification
 *
 * @since 1.0.3
 *
 * @param Forminator_Form_Model       $custom_form Forminator_Form_Model.
 * @param Forminator_Form_Entry_Model $entry Forminator_Form_Entry_Model.
 * @param boolean                     $exclude_empty [Optional] Exclude empty form entry.
 *
 * @return string
 */
function forminator_get_formatted_form_entry( Forminator_Form_Model $custom_form, Forminator_Form_Entry_Model $entry, $exclude_empty = false ) {
	$ignored_field_types = Forminator_Form_Entry_Model::ignored_fields();

	$html        = forminator_prepare_formatted_form_entry( $custom_form, $entry, $exclude_empty );
	$filter_name = $exclude_empty ? 'forminator_get_formatted_form_non_empty_entry' : 'forminator_get_formatted_form_entry';

	return apply_filters( $filter_name, $html, $custom_form, Forminator_CForm_Front_Action::$prepared_data, $entry, $ignored_field_types );
}

/**
 * Prepare Html Formatted of group field for email notification
 *
 * @param string                      $group_id Group ID.
 * @param Forminator_Form_Model       $custom_form Forminator_Form_Model.
 * @param Forminator_Form_Entry_Model $entry Forminator_Form_Entry_Model.
 * @param boolean                     $exclude_empty Exclude empty form entry.
 *
 * @return string
 */
function forminator_prepare_formatted_group_field( string $group_id, Forminator_Form_Model $custom_form, Forminator_Form_Entry_Model $entry, bool $exclude_empty = false ): string {
	$group_fields = $custom_form->get_grouped_fields( $group_id );

	$value  = '<hr>';
	$value .= forminator_prepare_formatted_form_entry( $custom_form, $entry, $exclude_empty, $group_fields );
	$value .= '<hr>';

	$original_keys = wp_list_pluck( $group_fields, 'slug' );
	$repeater_keys = forminator_get_cloned_field_keys( $entry, $original_keys );

	foreach ( $repeater_keys as $repeater_slug ) {
		$value .= forminator_prepare_formatted_form_entry( $custom_form, $entry, $exclude_empty, $group_fields, $repeater_slug );
		$value .= '<hr>';
	}

	return $value;
}

/**
 * Prepare Html Formatted of form entry for email notification
 *
 * @param Forminator_Form_Model       $custom_form Forminator_Form_Model.
 * @param Forminator_Form_Entry_Model $entry Forminator_Form_Entry_Model.
 * @param boolean                     $exclude_empty Exclude empty form entry.
 * @param array                       $form_fields Form fields.
 * @param string                      $repeater_suffix Repeater suffix.
 * @param boolean                     $show_label Show label.
 * @param string                      $list_tag List tag.
 * @param boolean                     $is_pdf Is PDF.
 *
 * @return string
 */
function forminator_prepare_formatted_form_entry(
	Forminator_Form_Model $custom_form,
	Forminator_Form_Entry_Model $entry,
	$exclude_empty,
	$form_fields = null,
	$repeater_suffix = '',
	$show_label = true,
	$list_tag = 'ol',
	$is_pdf = false
) {
	$ignored_field_types = Forminator_Form_Entry_Model::ignored_fields();

	if ( is_null( $form_fields ) ) {
		$rendering_group = false;
		/**
		 * Filter form fields before displaying
		 *
		 * @since 1.11
		 *
		 * @param array $form_fields
		 * @param Forminator_Form_Model $custom_form
		 *
		 * @return array
		 */
		$form_fields = apply_filters( 'forminator_custom_form_before_form_fields', $custom_form->get_fields(), $custom_form, Forminator_CForm_Front_Action::$prepared_data );
		if ( is_null( $form_fields ) ) {
			$form_fields = array();
		}
	} else {
		$rendering_group = true;
	}
	$html = '<br/><' . $list_tag . '>';

	foreach ( $form_fields as $form_field ) {
		$field_array = $form_field->to_formatted_array();
		$field_type  = $field_array['type'];
		$field_id    = Forminator_Field::get_property( 'element_id', $field_array ) . $repeater_suffix;

		if ( in_array( $field_id, Forminator_CForm_Front_Action::$hidden_fields, true ) || ( $form_field->parent_group && ! $rendering_group ) ) {
			continue;
		}

		if ( 'section' === $field_type ) {
			$value = $form_field->__get( 'section_title' );
			$html .= '</' . $list_tag . '>';
			$html .= '<h4><b>' . (string) $value . '</b></h4>';
			$html .= '<' . $list_tag . '>';
		} elseif ( 'html' === $field_type ) {
			$label = $form_field->__get( 'field_label' );
			$value = $form_field->__get( 'variations' );
			if ( $repeater_suffix ) {
				$group_fields  = $custom_form->get_grouped_fields( $form_field->parent_group );
				$original_keys = wp_list_pluck( $group_fields, 'slug' );
				foreach ( $original_keys as $original_key ) {
					$value = str_replace( '{' . $original_key . '}', '{' . $original_key . $repeater_suffix . '}', $value );
				}
			}
			$content = forminator_replace_form_data( $value, $custom_form, $entry, true, false, false, $is_pdf );
			$content = forminator_replace_variables( $content, $custom_form->id );
			$content = forminator_replace_custom_form_data( $content, $custom_form, $entry );
			$html   .= '</' . $list_tag . '>';
			if ( ! empty( $label ) && $show_label ) {
				$html .= '<h4><b>' . Forminator_Field::convert_markdown( $label ) . '</b></h4>';
			}
			$html .= $content;
			$html .= '<' . $list_tag . '>';
		} elseif ( in_array( $field_type, $ignored_field_types, true ) ) {
			continue;
		} elseif ( 'group' === $field_type ) {
			$label = $form_field->get_label_for_entry();
			if ( ! empty( $label ) && $show_label ) {
				$html .= '<b>' . Forminator_Field::convert_markdown( $label ) . '</b><br/>';
			}

			$html .= forminator_prepare_formatted_group_field( $field_id, $custom_form, $entry, $exclude_empty );
		} else {
			$slug = $form_field->slug . $repeater_suffix;
			if ( strpos( $slug, 'radio' ) !== false
					|| strpos( $slug, 'select' ) !== false
					|| strpos( $slug, 'checkbox' ) !== false
					) {
				$value = forminator_replace_form_data( '{' . $slug . '}', $custom_form, $entry, true, false, false, $is_pdf );
			} else {
				$separator = ! $show_label ? ' ' : '<br>';
				$value     = render_entry( $entry, $slug, $field_array, '', $exclude_empty, false, $separator, $show_label );
			}
			/**
			 * Filter value of a field that is not saved in DB
			 */
			$value = apply_filters( 'forminator_custom_form_after_render_value', $value, $custom_form, $slug, Forminator_CForm_Front_Action::$prepared_data );
			if ( ! $exclude_empty || ! empty( $value ) ) {
				$html .= '<li>';
				$label = $form_field->get_label_for_entry();

				$display_label = $show_label;
				// Hide label for multiple name field.
				if ( 'name' === $field_type && ! empty( $field_array['multiple_name'] ) && 'true' === $field_array['multiple_name'] ) {
					$display_label = false;
				}

				if ( ! empty( $label ) && $display_label ) {
					$html .= '<b>' . Forminator_Field::convert_markdown( $label ) . '</b><br/>';
				}
				if ( isset( $value ) && '' !== $value ) {
					if ( 'textarea' === $field_type ) {
						$value = forminator_replace_linebreaks( $value );
					}
					$html .= $value . '<br/>';
				}
				$html .= '</li>';
			}
		}
	}
	$html .= '</' . $list_tag . '><br/>';

	return $html;
}

/**
 * Get Html Formatted of form entry
 *
 * @since 1.0.3
 *
 * @param Forminator_Form_Model       $custom_form Forminator_Form_Model.
 * @param Forminator_Form_Entry_Model $entry Forminator_Form_Entry_Model.
 *
 * @return string
 */
function forminator_get_formatted_form_non_empty_entry( Forminator_Form_Model $custom_form, Forminator_Form_Entry_Model $entry ) {
	return forminator_get_formatted_form_entry( $custom_form, $entry, true );
}

/**
 * Get field from registered entries
 *
 * @since 1.0.5
 * @since 1.24  Added $user_meta
 *
 * @param string                      $element_id Element Id.
 * @param Forminator_Form_Model       $custom_form Forminator_Form_Model.
 * @param Forminator_Form_Entry_Model $entry Forminator_Form_Entry_Model.
 * @param bool                        $user_meta Check if usage is for user meta.
 *
 * @return string
 */
function forminator_get_field_from_form_entry( $element_id, Forminator_Form_Model $custom_form, Forminator_Form_Entry_Model $entry, $user_meta = false ) {
	$form_fields = $custom_form->get_fields();
	if ( is_null( $form_fields ) ) {
		$form_fields = array();
	}

	foreach ( $form_fields as $form_field ) {
		/**
		 * Forminator_Form_Field_Model
		 *
		 * @var  Forminator_Form_Field_Model $form_field */
		if ( $form_field->slug !== $element_id ) {
			continue;
		}
		$field_type = $form_field->__get( 'type' );
		if ( 'section' === $field_type ) {
			$value = $form_field->__get( 'section_title' );
		} elseif ( 'html' === $field_type ) {
			$variations = $form_field->__get( 'variations' );
			$value      = forminator_replace_variables( $variations, $custom_form->id );
		} elseif ( 'upload' === $field_type && $user_meta ) {
			$value = render_entry( $entry, $form_field->slug, null, '', false, true );
		} else {
			$value = render_entry( $entry, $form_field->slug );
		}

		return $value;
	}
}

/**
 * Get Html Formatted of form name
 *
 * @since 1.0.3
 *
 * @param Forminator_Form_Model       $custom_form Forminator_Form_Model.
 * @param Forminator_Form_Entry_Model $entry Forminator_Form_Entry_Model.
 *
 * @return string
 */
function forminator_get_formatted_form_name( Forminator_Form_Model $custom_form, Forminator_Form_Entry_Model $entry ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
	return esc_html( forminator_get_form_name( $custom_form->id ) );
}

/**
 * Get Submission ID
 *
 * @since 1.1
 *
 * @param Forminator_Form_Model|null  $custom_form Forminator_Form_Model.
 * @param Forminator_Form_Entry_Model $entry Forminator_Form_Entry_Model.
 *
 * @return string
 */
function forminator_get_submission_id( ?Forminator_Form_Model $custom_form = null, $entry = null ) {
	return is_object( $entry ) && isset( $entry->entry_id ) ? esc_html( $entry->entry_id ) : 0;
}

/**
 * Get referer url
 *
 * @since ?
 *
 * @param string $embed_url Embed URL.
 * @return string
 */
function forminator_get_referer_url( $embed_url = '' ) {
	$referer_url = '';
	if ( isset( $_REQUEST['extra']['referer_url'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$referer_url = sanitize_text_field( wp_unslash( $_REQUEST['extra']['referer_url'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	} elseif ( isset( $_REQUEST['referer_url'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$referer_url = sanitize_text_field( wp_unslash( $_REQUEST['referer_url'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	} elseif ( isset( $_SERVER['HTTP_REFERER'] ) ) {
		$referer_url = sanitize_text_field( wp_unslash( $_SERVER['HTTP_REFERER'] ) );
	}

	if ( '' === $referer_url ) {
		$referer_url = $embed_url;
	}

	return $referer_url;
}

/**
 * Get Submission URL
 *
 * @since 1.11
 *
 * @param Forminator_Form_Model       $custom_form Forminator_Form_Model.
 * @param Forminator_Form_Entry_Model $entry Forminator_Form_Entry_Model.
 *
 * @return string
 */
function forminator_get_submission_url( Forminator_Form_Model $custom_form, Forminator_Form_Entry_Model $entry ) {
	return '<a href="' . esc_url( admin_url( 'admin.php?page=forminator-entries&form_type=forminator_forms&form_id=' . $entry->form_id . '&entry_id=' . $entry->entry_id ) ) . '">' . esc_html__( 'here', 'forminator' ) . '</a>';
}

/**
 * Get account approval link
 *
 * @since 1.11
 *
 * @param Forminator_Form_Model       $custom_form Forminator_Form_Model.
 * @param Forminator_Form_Entry_Model $entry Forminator_Form_Entry_Model.
 *
 * @return string
 */
function forminator_get_account_approval_link( Forminator_Form_Model $custom_form, Forminator_Form_Entry_Model $entry ) {
	$key = $entry->get_meta( 'activation_key', '' );
	if ( ! empty( $key ) ) {
		$key = esc_url(
			add_query_arg(
				array(
					'page' => 'account_activation',
					'key'  => $key,
				),
				home_url( '/' )
			)
		);
		$key = '<a href="' . $key . '" target="_blank">' . $key . '</a>';
	}

	return '<p>' . $key . '</p>';
}

/**
 * Get username from registration form
 *
 * @since 1.11
 *
 * @param Forminator_Form_Model       $custom_form Forminator_Form_Model.
 * @param Forminator_Form_Entry_Model $entry Forminator_Form_Entry_Model.
 *
 * @return string
 */
function forminator_get_formatted_username( Forminator_Form_Model $custom_form, Forminator_Form_Entry_Model $entry ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
	$username = '';
	if ( isset( $custom_form->settings['registration-username-field'] ) && ! empty( $custom_form->settings['registration-username-field'] ) ) {
		$username = $custom_form->settings['registration-username-field'];
		if ( ! empty( Forminator_CForm_Front_Action::$prepared_data[ $username ] ) ) {
			$username = '<b>' . Forminator_CForm_Front_Action::$prepared_data[ $username ] . '</b>';
		}
	}

	return $username;
}

/**
 * Get line break
 *
 * @since 1.11
 *
 * @return string
 */
function forminator_get_formatted_line_break() {
	return '&nbsp;<br/>';
}

/**
 * Get loggin URL
 *
 * @param string $embed_url Current URL.
 * @return string
 */
function forminator_get_login_url( $embed_url ) {
	if ( class_exists( 'Mask_Login' )
			&& method_exists( 'Mask_Login', 'is_active' )
			&& method_exists( 'Mask_Login', 'get_new_login_url' )
	) {
		$mask_login = new Mask_Login();
		if ( $mask_login->is_active() ) {
			$login_url = $mask_login->get_new_login_url();
		}
	}

	if ( empty( $login_url ) ) {
		global $wp_rewrite;

		$login_url = is_null( $wp_rewrite ) ? $embed_url . 'wp-login.php' : wp_login_url();
	}

	return apply_filters( 'forminator_login_url', $login_url, $embed_url );
}

/**
 * Return filtered editor content with replaced variables
 *
 * @since 1.0
 * @since 1.0.6 add `{form_id}` handle
 *
 * @param string $content Content to replace the variables.
 * @param int    $id Id.
 * @param mixed  $entry Entry.
 *
 * @return string
 */
function forminator_replace_variables( $content, $id = false, $entry = null ) {
	$content_before_replacement = $content;

	// If we have no variables, skip.
	if ( strpos( $content, '{' ) !== false ) {
		$embed_url = ! empty( Forminator_CForm_Front_Action::$prepared_data['current_url'] )
				? Forminator_CForm_Front_Action::$prepared_data['current_url']
				: forminator_get_current_url();
		$post_id   = null;
		if ( ! empty( Forminator_CForm_Front_Action::$prepared_data['page_id'] ) ) {
			$post_id = Forminator_CForm_Front_Action::$prepared_data['page_id'];
		} elseif ( $embed_url ) {
			$post_id = url_to_postid( $embed_url );
		}
		$refer_url = forminator_get_referer_url( $embed_url );
		$login_url = forminator_get_login_url( $embed_url );

		// Use the date from the entry if date_created_sql exists.
		if ( ! empty( $entry->date_created_sql ) ) {
			$date_created = strtotime( $entry->date_created_sql );
		} else {
			$date_created = forminator_local_timestamp();
		}

		$variables = array(
			// Handle User IP Address variable.
			'{user_ip}'             => forminator_user_ip(),
			// Handle Date (F d, Y) variable.
			'{date}'                => date_i18n( 'F d, Y', forminator_local_timestamp(), true ),
			// Handle Date (mm/dd/yyyy) variable.
			'{date_mdy}'            => date_i18n( 'm/d/Y', forminator_local_timestamp(), true ),
			// Handle Date (dd/mm/yyyy) variable.
			'{date_dmy}'            => date_i18n( 'd/m/Y', forminator_local_timestamp(), true ),
			// Submission ID.
			'{submission_id}'       => forminator_get_submission_id( new Forminator_Form_Model(), $entry ),
			// Submission time.
			'{submission_time}'     => date_i18n( 'g:i:s a, T', $date_created, true ),
			// Submission Date (F d, Y) variable.
			'{submission_date}'     => date_i18n( 'F d, Y', $date_created, true ),
			// Submission Date (mm/dd/yyyy) variable.
			'{submission_date_mdy}' => date_i18n( 'm/d/Y', $date_created, true ),
			// Submission Date (dd/mm/yyyy) variable.
			'{submission_date_dmy}' => date_i18n( 'd/m/Y', $date_created, true ),
			// Handle Embed Post/Page ID variable.
			'{embed_id}'            => forminator_get_post_data( 'ID', $post_id ),
			// Handle Embed Post/Page Title variable.
			'{embed_title}'         => forminator_get_post_data( 'post_title', $post_id ),
			// Handle Embed URL variable.
			'{embed_url}'           => $embed_url,
			// Handle HTTP User Agent variable.
			// some browser not sending HTTP_USER_AGENT or some servers probably stripped this value.
			'{user_agent}'          => isset( $_SERVER['HTTP_USER_AGENT'] ) ? esc_html( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) ) : '',
			// Handle site url variable.
			'{site_url}'            => site_url(),
			// Handle login url variable.
			'{login_url}'           => $login_url,
			// Handle HTTP Refer URL variable.
			'{refer_url}'           => $refer_url,
			'{http_refer}'          => $refer_url,
			// Handle User ID variable.
			'{user_id}'             => forminator_get_user_data( 'ID' ),
			// Handle User Display Name variable.
			'{user_name}'           => forminator_get_user_data( 'display_name' ),
			// Handle User Email variable.
			'{user_email}'          => forminator_get_user_data( 'user_email' ),
			// Handle User Login variable.
			'{user_login}'          => forminator_get_user_data( 'user_login' ),
			// Handle site title variable.
			'{site_title}'          => get_bloginfo( 'name' ),
		);
		if ( $id ) {
			// Handle form_name data.
			if ( strpos( $content, '{form_name}' ) !== false ) {
				$variables['{form_name}'] = esc_html( forminator_get_form_name( $id ) );
			}
			// handle form_id.
			$variables['{form_id}'] = $id;
			// Handle Submissions number.
			$variables['{submissions_number}'] = Forminator_Form_Entry_Model::count_entries( $id );
		} else {
			$variables['{form_name}']          = '';
			$variables['{form_id}']            = '';
			$variables['{submissions_number}'] = '';
		}

		foreach ( $variables as $placeholder => $value ) {
			// If null, set to empty string.
			if ( is_null( $value ) ) {
				$value = '';
			}
			$content = forminator_replace_placeholder_in_urls( $content, $placeholder, $value );
		}

		$content = str_replace( array_keys( $variables ), array_values( $variables ), $content );
	}

	return apply_filters( 'forminator_replace_variables', $content, $content_before_replacement );
}

/**
 * Render entry
 * Used in email notifications
 * TODO: refactor this
 *
 * @since 1.0
 * @since 1.24 Added $att_id_only param.
 *
 * @param object $item        - the entry.
 * @param string $column_name - the column name.
 *
 * @param null   $field       @since 1.0.5, optional Forminator_Form_Field_Model.
 * @param string $type Type.
 * @param bool   $remove_empty Remove empty.
 * @param bool   $att_id_only  If output needs attachment ID only. E.g. when upload field is used with ACF file type.
 * @param string $separator    Separator for some entries. Line break by default. (Added for PDF addon).
 * @param bool   $show_label   @since 1.25, added for PDF generation.
 * @return string
 */
function render_entry( $item, $column_name, $field = null, $type = '', $remove_empty = false, $att_id_only = false, $separator = '<br>', $show_label = true ) {
	$data = $item->get_meta( $column_name, '' );

	// Check if we're in email context using parent class static property.
	$is_email = false;
	if ( class_exists( 'Forminator_Mail' ) && method_exists( 'Forminator_Mail', 'is_email_context' ) ) {
		$is_email = Forminator_Mail::is_email_context();
	}

	$is_calculation = false;
	if ( stripos( $column_name, 'calculation' ) !== false ) {
		$is_calculation = true;
	}

	if ( $is_calculation && $data ) {
		return Forminator_Form_Entry_Model::meta_value_to_string( 'calculation', $data, true, PHP_INT_MAX, $field );
	}

	if ( $data || '0' === $data ) {
		$currency_symbol = forminator_get_currency_symbol();
		if ( is_array( $data ) ) {
			if ( 'non_empty' === $type ) {
				$data = array_filter( $data );
			}
			if ( stripos( $column_name, 'time' ) !== false && 1 === count( $data ) && isset( $data['ampm'] ) ) {
				$data = array();
			}
			$output       = '';
			$product_cost = 0;
			$is_product   = false;
			$countries    = forminator_get_countries_list();

			if ( ! empty( $data ) ) {
				$custom_form_model          = Forminator_Base_Form_Model::get_model( $item->form_id );
				$email_image_display_option = $custom_form_model->settings['email_image_display_option'] ?? 'preview';
				foreach ( $data as $key => $value ) {
					if ( is_array( $value ) ) {
						if ( 'file' === $key && isset( $value['file_url'] ) ) {

							if ( is_array( $value['file_url'] ) ) {
								$file_urls = $value['file_url'];
							} else {
								// If only attachment ID is needed like for ACF file type.
								if ( $att_id_only ) {
									return attachment_url_to_postid( $value['file_url'] );
								}

								$file_urls = array( $value['file_url'] );
							}

							$files_count = count( $file_urls );
							foreach ( $file_urls as $index => $file_url ) {
								$file_name = basename( $file_url );

								if ( $is_email && 'preview' === $email_image_display_option && forminator_can_display_as_image( $file_url ) ) {
									// For email notifications, display image if possible.
									$output .= forminator_get_email_image_markup( $file_url, $file_name, '', 'upload' );
								} else {
									// Default link display.
									$file_name = "<a href='" . esc_url( $file_url ) . "' target='_blank' rel='noreferrer' title='" . esc_html__( 'View File', 'forminator' ) . "'>$file_name</a>";
									$output   .= $file_name;
								}

								$output .= $index < $files_count - 1 ? $separator : '';
							}
						}
					} elseif ( ! is_int( $key ) ) {
						if ( 'postdata' === $key ) {
							// possible empty when postdata not required.
							if ( ! empty( $value ) ) {

								$post_id = $data['postdata'];
								$url     = get_edit_post_link( $post_id, 'link' );

								// Title.
								$title = get_the_title( $post_id );
								$title = ! empty( $title ) ? $title : esc_html__( '(no title)', 'forminator' );

								$output .= '<ul>';

								$title_label = $field['post_title_label'] ?? esc_html__( 'Title', 'forminator' );
								$output     .= '<li>';
								$output     .= '<b>' . Forminator_Field::convert_markdown( esc_html( $title_label ) ) . ':</b> ';
								$output     .= '<a href="' . $url . '" target="_blank" rel="noopener noreferrer" title="' . esc_attr__( 'Edit Post', 'forminator' ) . '">' . $title . '</a>';
								$output     .= '</li>';

									// Content.
								if ( ! empty( $data['value']['post-content'] ) ) {
									$post_content  = $data['value']['post-content'];
									$content_label = $field['post_content_label'] ?? esc_html__( 'Content', 'forminator' );
									$output       .= '<li>';
									$output       .= '<b>' . Forminator_Field::convert_markdown( esc_html( $content_label ) ) . ':</b>' . $separator;
									$output       .= wp_kses( $post_content, 'post' );
									$output       .= '</li>';
								}

									// Excerpt.
								if ( ! empty( $data['value']['post-excerpt'] ) ) {
									$post_excerpt  = $data['value']['post-excerpt'];
									$excerpt_label = $field['post_excerpt_label'] ?? esc_html__( 'Excerpt', 'forminator' );
									$output       .= '<li>';
									$output       .= '<b>' . Forminator_Field::convert_markdown( esc_html( $excerpt_label ) ) . ':</b>' . $separator;
									$output       .= wp_strip_all_tags( $post_excerpt );
									$output       .= '</li>';
								}

									// Category.
								if ( ! empty( $data['value']['category'] ) ) {
									$categories    = $data['value']['category'];
									$post_category = array();
									if ( is_array( $categories ) ) {
										foreach ( $categories as $category_id ) {
											$post_category[] = get_the_category_by_ID( $category_id );
										}
									} else {
										$post_category[] = get_the_category_by_ID( $categories );
									}
									// In case of deleted categories.
									if ( ! empty( $post_category ) ) {
										$category_count = count( $post_category );
										$label          = $field['category_label'] ?? ( ( 1 === $category_count ) ? esc_html__( 'Category', 'forminator' ) : esc_html__( 'Categories', 'forminator' ) );

										$output .= '<li>';
										$output .= '<b>' . Forminator_Field::convert_markdown( esc_html( $label ) ) . ':</b> ';
										$output .= implode( ',', $post_category );
										$output .= '</li>';
									}
								}

									// Tags.
								if ( ! empty( $data['value']['post_tag'] ) ) {
									$post_tag_id = $data['value']['post_tag'];
									$term_args   = array(
										'taxonomy'         => 'post_tag',
										'term_taxonomy_id' => $post_tag_id,
										'hide_empty'       => false,
										'fields'           => 'names',
									);
									$term_query  = new WP_Term_Query( $term_args );

									// In case of deleted tags.
									if ( ! empty( $term_query->terms ) ) {
										$term_count = count( $term_query->terms );
										$label      = $field['post_tag_label'] ?? ( ( 1 === $term_count ) ? esc_html__( 'Tag', 'forminator' ) : esc_html__( 'Tags', 'forminator' ) );

										$output .= '<li>';
										$output .= '<b>' . Forminator_Field::convert_markdown( esc_html( $label ) ) . ':</b> ';
										$output .= implode( ',', $term_query->terms );
										$output .= '</li>';
									}
								}

									// Featured Image.
								if ( ! empty( $data['value']['post-image'] ) && ! empty( $data['value']['post-image']['attachment_id'] ) ) {
									$post_image_id = $data['value']['post-image']['attachment_id'];
									$image_label   = $field['post_image_label'] ?? esc_html__( 'Featured image', 'forminator' );
									$output       .= '<li>';
									$output       .= '<b>' . Forminator_Field::convert_markdown( esc_html( $image_label ) ) . ':</b>' . $separator;
									$output       .= wp_get_attachment_image( $post_image_id, array( 100, 100 ) );
									$output       .= '</li>';
								}

									// Custom fields.
								if ( ! empty( $data['value']['post-custom'] ) ) {
									$post_custom = $data['value']['post-custom'];
									$output     .= '<li>';
										$output .= '<b>' . esc_html__( 'Custom fields', 'forminator' ) . ':</b>' . $separator;
										$output .= '<ul class="' . esc_attr( 'bulleted' ) . '">';
									foreach ( $post_custom as $field ) {
										if ( ! empty( $field['value'] ) ) {
												$output .= '<li>';
												$output .= esc_html( $field['key'] ) . ': ';
												$output .= esc_html( $field['value'] );
												$output .= '</li>';
										}
									}
										$output .= '</ul>';
										$output .= '</li>';
								}

									$tax_keys = forminator_list_custom_taxonomies( $data['value'] );

								if ( ! empty( $tax_keys ) ) {
									foreach ( $tax_keys as $tax_key => $tax_name ) {
										if ( ! empty( $data['value'][ $tax_name ] ) ) {
											$the_taxonomies = '';
											$single_label   = '';
											$plural_label   = '';
											$taxonomies     = array();
											$post_taxonomy  = $data['value'][ $tax_name ];
											if ( is_array( $post_taxonomy ) ) {
												foreach ( $post_taxonomy as $taxonomy ) {
													if ( ! is_wp_error( get_the_category_by_ID( $taxonomy ) ) ) {
														$taxonomies[] = get_the_category_by_ID( $taxonomy );
													}
												}

												if ( ! empty( $taxonomies ) ) {
													$the_taxonomies = implode( ', ', $taxonomies );
												}
											} else {
												$the_taxonomies = get_the_category_by_ID( $post_taxonomy );
												if ( is_wp_error( $the_taxonomies ) ) {
													$the_taxonomies = '';
												}
											}

											$tax_obj = get_taxonomy( $tax_name );
											if ( ! empty( $tax_obj ) ) {
												$single_label = ! empty( $tax_obj->labels->singular_name ) ? $tax_obj->labels->singular_name : $tax_obj->labels->name;
												$plural_label = $tax_obj->labels->name;
											}

											if ( ! empty( $the_taxonomies ) ) {
												$output .= '<li>';
												if ( is_array( $post_taxonomy ) ) {
													$output .= '<b>' . esc_html__( $plural_label, 'forminator' ) . ':</b> '; // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText
												} else {
													$output .= '<b>' . esc_html__( $single_label, 'forminator' ) . ':</b> '; // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText
												}

												$output .= $the_taxonomies;
												$output .= '</li>';
											}
										}
									}
								}

								$output .= '</ul>';

							} else {
								$output = ' ';
							}
						} elseif ( is_string( $key ) ) {
							if ( 'product-id' === $key || 'product-quantity' === $key ) {
								if ( 0 === $product_cost ) {
									$product_cost = $value;
								} else {
									$product_cost = $product_cost * $value;
								}
								$is_product = true;
							} else {

								$key_slug = $key;

								if ( in_array( $key, Forminator_Form_Entry_Model::field_suffix(), true ) ) {
									$key = Forminator_Form_Entry_Model::translate_suffix( $key );
								} else {
									$key = strtolower( $key );
									$key = ucfirst( str_replace( array( '-', '_' ), ' ', $key ) );
								}

								// Name labels.
								if ( 'prefix' === $key_slug && ! empty( $field['prefix_label'] ) ) {
									$key = $field['prefix_label'];
								}
								if ( 'first-name' === $key_slug && ! empty( $field['fname_label'] ) ) {
									$key = $field['fname_label'];
								}
								if ( 'middle-name' === $key_slug && ! empty( $field['mname_label'] ) ) {
									$key = $field['mname_label'];
								}
								if ( 'last-name' === $key_slug && ! empty( $field['lname_label'] ) ) {
									$key = $field['lname_label'];
								}

								// Address labels.
								if ( 'street_address' === $key_slug && ! empty( $field['street_address_label'] ) ) {
									$key = $field['street_address_label'];
								}
								if ( 'address_line' === $key_slug && ! empty( $field['address_line_label'] ) ) {
									$key = $field['address_line_label'];
								}
								if ( 'city' === $key_slug && ! empty( $field['address_city_label'] ) ) {
									$key = $field['address_city_label'];
								}
								if ( 'state' === $key_slug && ! empty( $field['address_state_label'] ) ) {
									$key = $field['address_state_label'];
								}
								if ( 'zip' === $key_slug && ! empty( $field['address_zip_label'] ) ) {
									$key = $field['address_zip_label'];
								}
								if ( 'country' === $key_slug ) {

									if ( ! empty( $field['address_country_label'] ) ) {
										$key = $field['address_country_label'];
									}
									if ( isset( $countries[ $value ] ) ) {
										$value = $countries[ $value ];
									}
								}

								// Date sub-field labels.
								if ( 'day' === $key_slug && ! empty( $field['day_label'] ) ) {
									$key = $field['day_label'];
								}
								if ( 'month' === $key_slug && ! empty( $field['month_label'] ) ) {
									$key = $field['month_label'];
								}
								if ( 'year' === $key_slug && ! empty( $field['year_label'] ) ) {
									$key = $field['year_label'];
								}

								if ( 'amount' === $key_slug && in_array( $field['type'], array( 'stripe', 'stripe-ocs', 'paypal' ), true ) ) {
									$value = Forminator_Field::get_formatted_amount( $field, $data );
								}
								if ( $remove_empty && empty( $value ) ) {
									$output .= '';
								} elseif ( $show_label ) {
										$output .= sprintf( '<strong>%1$s : </strong> %2$s', Forminator_Field::convert_markdown( esc_html( $key ) ), esc_html( $value ) ) . $separator;
								} else {
									$output .= esc_html( $value ) . $separator;
								}
							}
						}
					}
				}
			}
			if ( $is_product ) {
				/* Translators: 1. Opening <strong> tag, 2. closing <strong> tag 3. currency symbol with cost */
				$output = sprintf( esc_html__( '%1$sTotal%2$s %3$s', 'forminator' ), '<strong>', '</strong>', $currency_symbol . '' . $product_cost );
			} elseif ( ! empty( $output ) ) {
				if (
						isset( $column_name ) &&
						(
							false !== strpos( $column_name, 'name' ) ||
							false !== strpos( $column_name, 'address' ) ||
							false !== strpos( $column_name, 'upload' ) ||
							false !== strpos( $column_name, 'postdata' ) ||
							false !== strpos( $column_name, 'slider' ) ||
							false !== strpos( $column_name, 'signature' )
						)
					) {
					$output = trim( $output );

				} elseif ( false !== strpos( $column_name, 'date' ) && 'select' === $field['field_type'] ) {
					$meta_value = array(
						'day'    => $data['day'],
						'month'  => $data['month'],
						'year'   => $data['year'],
						'format' => $data['format'],
					);

					$output = Forminator_Form_Entry_Model::meta_value_to_string( 'date', $meta_value, true ) . $separator;
					/* translators: 1. Colon symbol, 2. Date format, 3. Separator. */
					$output .= sprintf( esc_html__( 'Format%1$s %2$s %3$s', 'forminator' ), ':', $data['format'], $separator );

				} elseif ( false !== strpos( $column_name, 'time' ) ) {
					$output = Forminator_Form_Entry_Model::meta_value_to_string( 'time', $data, true ) . $separator;

				} else {
					$output = substr( trim( $output ), 0, - 1 );

				}
				// If it is an array and not an upload field.
			} elseif ( is_array( $data ) && stripos( $column_name, 'upload' ) === false ) {
				$output = implode( ', ', $data );
			}

			return $output;
		} else {
			if ( stripos( $column_name, 'currency' ) !== false || stripos( $column_name, 'number' ) !== false ) {
				$data = Forminator_Field::forminator_number_formatting( $field, $data );
			}

			return $data;
		}
	}

	return '';
}

/**
 * Check, maybe field doesn't exist anymore
 *
 * @param string $element_id Field slug.
 * @param array  $fields Fields.
 * @param int    $form_id Form id.
 * @return boolean
 */
function forminator_old_field( $element_id, $fields, $form_id ) {
	$main_id     = forminator_remove_prefixes( $element_id );
	$prefix      = $main_id !== $element_id ? str_replace( $main_id . '-', '', $element_id ) : '';
	$element_ids = wp_list_pluck( $fields, 'element_id' );

	$old_field = ! in_array( $main_id, $element_ids, true );

	if ( $prefix && ! $old_field ) {
		$custom_form = Forminator_Base_Form_Model::get_model( $form_id );
		$field       = $custom_form->get_field( $main_id, false );
		if ( ! $field->is_subfield_enabled( $prefix ) ) {
			$old_field = true;
		}
	}

	return $old_field;
}

/**
 * Remove prefixes from field slug
 *
 * @param string $field_id Field slug.
 * @return string
 */
function forminator_remove_prefixes( $field_id ) {
	$field_suffixes = Forminator_Form_Entry_Model::field_suffix();
	$field_suffixes = array_map(
		function ( $str ) {
			return '-' . $str;
		},
		$field_suffixes
	);
	$field_id       = str_replace( $field_suffixes, '', $field_id );

	return $field_id;
}

/**
 * Return countries list
 *
 * @since 1.0
 * @return array
 */
function forminator_get_countries_list() {
	$countries = array(
		'AF' => esc_html__( 'Afghanistan', 'forminator' ),
		'AL' => esc_html__( 'Albania', 'forminator' ),
		'DZ' => esc_html__( 'Algeria', 'forminator' ),
		'AS' => esc_html__( 'American Samoa', 'forminator' ),
		'AD' => esc_html__( 'Andorra', 'forminator' ),
		'AO' => esc_html__( 'Angola', 'forminator' ),
		'AI' => esc_html__( 'Anguilla', 'forminator' ),
		'AQ' => esc_html__( 'Antarctica', 'forminator' ),
		'AG' => esc_html__( 'Antigua and Barbuda', 'forminator' ),
		'AR' => esc_html__( 'Argentina', 'forminator' ),
		'AM' => esc_html__( 'Armenia', 'forminator' ),
		'AU' => esc_html__( 'Australia', 'forminator' ),
		'AW' => esc_html__( 'Aruba', 'forminator' ),
		'AT' => esc_html__( 'Austria', 'forminator' ),
		'AZ' => esc_html__( 'Azerbaijan', 'forminator' ),
		'BS' => esc_html__( 'Bahamas', 'forminator' ),
		'BH' => esc_html__( 'Bahrain', 'forminator' ),
		'BD' => esc_html__( 'Bangladesh', 'forminator' ),
		'BB' => esc_html__( 'Barbados', 'forminator' ),
		'BY' => esc_html__( 'Belarus', 'forminator' ),
		'BE' => esc_html__( 'Belgium', 'forminator' ),
		'BZ' => esc_html__( 'Belize', 'forminator' ),
		'BJ' => esc_html__( 'Benin', 'forminator' ),
		'BM' => esc_html__( 'Bermuda', 'forminator' ),
		'BT' => esc_html__( 'Bhutan', 'forminator' ),
		'BO' => esc_html__( 'Bolivia', 'forminator' ),
		'BA' => esc_html__( 'Bosnia and Herzegovina', 'forminator' ),
		'BW' => esc_html__( 'Botswana', 'forminator' ),
		'BV' => esc_html__( 'Bouvet Island', 'forminator' ),
		'BR' => esc_html__( 'Brazil', 'forminator' ),
		'IO' => esc_html__( 'British Indian Ocean Territory', 'forminator' ),
		'BN' => esc_html__( 'Brunei', 'forminator' ),
		'BG' => esc_html__( 'Bulgaria', 'forminator' ),
		'BF' => esc_html__( 'Burkina Faso', 'forminator' ),
		'BI' => esc_html__( 'Burundi', 'forminator' ),
		'KH' => esc_html__( 'Cambodia', 'forminator' ),
		'CM' => esc_html__( 'Cameroon', 'forminator' ),
		'CA' => esc_html__( 'Canada', 'forminator' ),
		'CV' => esc_html__( 'Cabo Verde', 'forminator' ),
		'KY' => esc_html__( 'Cayman Islands', 'forminator' ),
		'CF' => esc_html__( 'Central African Republic', 'forminator' ),
		'TD' => esc_html__( 'Chad', 'forminator' ),
		'CL' => esc_html__( 'Chile', 'forminator' ),
		'CN' => html_entity_decode( esc_html__( 'China, People\'s Republic of', 'forminator' ), ENT_QUOTES ),
		'CX' => esc_html__( 'Christmas Island', 'forminator' ),
		'CC' => esc_html__( 'Cocos Islands', 'forminator' ),
		'CO' => esc_html__( 'Colombia', 'forminator' ),
		'KM' => esc_html__( 'Comoros', 'forminator' ),
		'CD' => esc_html__( 'Congo, Democratic Republic of the', 'forminator' ),
		'CG' => esc_html__( 'Congo, Republic of the', 'forminator' ),
		'CK' => esc_html__( 'Cook Islands', 'forminator' ),
		'CR' => esc_html__( 'Costa Rica', 'forminator' ),
		'CI' => html_entity_decode( esc_html__( 'Côte d\'Ivoire', 'forminator' ), ENT_QUOTES ),
		'HR' => esc_html__( 'Croatia', 'forminator' ),
		'CU' => esc_html__( 'Cuba', 'forminator' ),
		'CW' => esc_html__( 'Curaçao', 'forminator' ),
		'CY' => esc_html__( 'Cyprus', 'forminator' ),
		'CZ' => esc_html__( 'Czech Republic', 'forminator' ),
		'DK' => esc_html__( 'Denmark', 'forminator' ),
		'DJ' => esc_html__( 'Djibouti', 'forminator' ),
		'DM' => esc_html__( 'Dominica', 'forminator' ),
		'DO' => esc_html__( 'Dominican Republic', 'forminator' ),
		'TL' => esc_html__( 'East Timor', 'forminator' ),
		'EC' => esc_html__( 'Ecuador', 'forminator' ),
		'EG' => esc_html__( 'Egypt', 'forminator' ),
		'SV' => esc_html__( 'El Salvador', 'forminator' ),
		'GQ' => esc_html__( 'Equatorial Guinea', 'forminator' ),
		'ER' => esc_html__( 'Eritrea', 'forminator' ),
		'EE' => esc_html__( 'Estonia', 'forminator' ),
		'ET' => esc_html__( 'Ethiopia', 'forminator' ),
		'FK' => esc_html__( 'Falkland Islands', 'forminator' ),
		'FO' => esc_html__( 'Faroe Islands', 'forminator' ),
		'FJ' => esc_html__( 'Fiji', 'forminator' ),
		'FI' => esc_html__( 'Finland', 'forminator' ),
		'FR' => esc_html__( 'France', 'forminator' ),
		'FX' => esc_html__( 'France, Metropolitan', 'forminator' ),
		'GF' => esc_html__( 'French Guiana', 'forminator' ),
		'PF' => esc_html__( 'French Polynesia', 'forminator' ),
		'TF' => esc_html__( 'French South Territories', 'forminator' ),
		'GA' => esc_html__( 'Gabon', 'forminator' ),
		'GM' => esc_html__( 'Gambia', 'forminator' ),
		'GE' => esc_html__( 'Georgia', 'forminator' ),
		'DE' => esc_html__( 'Germany', 'forminator' ),
		'GG' => esc_html__( 'Guernsey', 'forminator' ),
		'GH' => esc_html__( 'Ghana', 'forminator' ),
		'GI' => esc_html__( 'Gibraltar', 'forminator' ),
		'GR' => esc_html__( 'Greece', 'forminator' ),
		'GL' => esc_html__( 'Greenland', 'forminator' ),
		'GD' => esc_html__( 'Grenada', 'forminator' ),
		'GP' => esc_html__( 'Guadeloupe', 'forminator' ),
		'GU' => esc_html__( 'Guam', 'forminator' ),
		'GT' => esc_html__( 'Guatemala', 'forminator' ),
		'GN' => esc_html__( 'Guinea', 'forminator' ),
		'GW' => esc_html__( 'Guinea-Bissau', 'forminator' ),
		'GY' => esc_html__( 'Guyana', 'forminator' ),
		'HT' => esc_html__( 'Haiti', 'forminator' ),
		'HM' => esc_html__( 'Heard Island And Mcdonald Island', 'forminator' ),
		'HN' => esc_html__( 'Honduras', 'forminator' ),
		'HK' => esc_html__( 'Hong Kong', 'forminator' ),
		'HU' => esc_html__( 'Hungary', 'forminator' ),
		'IS' => esc_html__( 'Iceland', 'forminator' ),
		'IN' => esc_html__( 'India', 'forminator' ),
		'ID' => esc_html__( 'Indonesia', 'forminator' ),
		'IR' => esc_html__( 'Iran', 'forminator' ),
		'IQ' => esc_html__( 'Iraq', 'forminator' ),
		'IE' => esc_html__( 'Ireland', 'forminator' ),
		'IL' => esc_html__( 'Israel', 'forminator' ),
		'IT' => esc_html__( 'Italy', 'forminator' ),
		'JM' => esc_html__( 'Jamaica', 'forminator' ),
		'JP' => esc_html__( 'Japan', 'forminator' ),
		'JE' => esc_html__( 'Jersey', 'forminator' ),
		'JT' => esc_html__( 'Johnston Island', 'forminator' ),
		'JO' => esc_html__( 'Jordan', 'forminator' ),
		'KZ' => esc_html__( 'Kazakhstan', 'forminator' ),
		'KE' => esc_html__( 'Kenya', 'forminator' ),
		'KI' => esc_html__( 'Kiribati', 'forminator' ),
		'KP' => html_entity_decode( esc_html__( 'Korea, Democratic People\'s Republic of', 'forminator' ), ENT_QUOTES ),
		'KR' => esc_html__( 'Korea, Republic of', 'forminator' ),
		'XK' => esc_html__( 'Kosovo', 'forminator' ),
		'KW' => esc_html__( 'Kuwait', 'forminator' ),
		'KG' => esc_html__( 'Kyrgyzstan', 'forminator' ),
		'LA' => html_entity_decode( esc_html__( 'Lao People\'s Democratic Republic', 'forminator' ), ENT_QUOTES ),
		'LV' => esc_html__( 'Latvia', 'forminator' ),
		'LB' => esc_html__( 'Lebanon', 'forminator' ),
		'LS' => esc_html__( 'Lesotho', 'forminator' ),
		'LR' => esc_html__( 'Liberia', 'forminator' ),
		'LY' => esc_html__( 'Libya', 'forminator' ),
		'LI' => esc_html__( 'Liechtenstein', 'forminator' ),
		'LT' => esc_html__( 'Lithuania', 'forminator' ),
		'LU' => esc_html__( 'Luxembourg', 'forminator' ),
		'MO' => esc_html__( 'Macau', 'forminator' ),
		'MK' => esc_html__( 'North Macedonia', 'forminator' ),
		'MG' => esc_html__( 'Madagascar', 'forminator' ),
		'MW' => esc_html__( 'Malawi', 'forminator' ),
		'MY' => esc_html__( 'Malaysia', 'forminator' ),
		'MV' => esc_html__( 'Maldives', 'forminator' ),
		'ML' => esc_html__( 'Mali', 'forminator' ),
		'MT' => esc_html__( 'Malta', 'forminator' ),
		'MH' => esc_html__( 'Marshall Islands', 'forminator' ),
		'MQ' => esc_html__( 'Martinique', 'forminator' ),
		'MR' => esc_html__( 'Mauritania', 'forminator' ),
		'MU' => esc_html__( 'Mauritius', 'forminator' ),
		'YT' => esc_html__( 'Mayotte', 'forminator' ),
		'MX' => esc_html__( 'Mexico', 'forminator' ),
		'FM' => esc_html__( 'Micronesia', 'forminator' ),
		'MD' => esc_html__( 'Moldova', 'forminator' ),
		'MC' => esc_html__( 'Monaco', 'forminator' ),
		'MN' => esc_html__( 'Mongolia', 'forminator' ),
		'MS' => esc_html__( 'Montserrat', 'forminator' ),
		'ME' => esc_html__( 'Montenegro', 'forminator' ),
		'MA' => esc_html__( 'Morocco', 'forminator' ),
		'MZ' => esc_html__( 'Mozambique', 'forminator' ),
		'MM' => esc_html__( 'Myanmar', 'forminator' ),
		'NA' => esc_html__( 'Namibia', 'forminator' ),
		'NR' => esc_html__( 'Nauru', 'forminator' ),
		'NP' => esc_html__( 'Nepal', 'forminator' ),
		'NL' => esc_html__( 'Netherlands', 'forminator' ),
		'AN' => esc_html__( 'Netherlands Antilles', 'forminator' ),
		'NC' => esc_html__( 'New Caledonia', 'forminator' ),
		'NZ' => esc_html__( 'New Zealand', 'forminator' ),
		'NI' => esc_html__( 'Nicaragua', 'forminator' ),
		'NE' => esc_html__( 'Niger', 'forminator' ),
		'NG' => esc_html__( 'Nigeria', 'forminator' ),
		'NU' => esc_html__( 'Niue', 'forminator' ),
		'NF' => esc_html__( 'Norfolk Island', 'forminator' ),
		'MP' => esc_html__( 'Northern Mariana Islands', 'forminator' ),
		'NO' => esc_html__( 'Norway', 'forminator' ),
		'OM' => esc_html__( 'Oman', 'forminator' ),
		'PK' => esc_html__( 'Pakistan', 'forminator' ),
		'PW' => esc_html__( 'Palau', 'forminator' ),
		'PS' => esc_html__( 'Palestine, State of', 'forminator' ),
		'PA' => esc_html__( 'Panama', 'forminator' ),
		'PG' => esc_html__( 'Papua New Guinea', 'forminator' ),
		'PY' => esc_html__( 'Paraguay', 'forminator' ),
		'PE' => esc_html__( 'Peru', 'forminator' ),
		'PH' => esc_html__( 'Philippines', 'forminator' ),
		'PN' => esc_html__( 'Pitcairn Islands', 'forminator' ),
		'PL' => esc_html__( 'Poland', 'forminator' ),
		'PT' => esc_html__( 'Portugal', 'forminator' ),
		'PR' => esc_html__( 'Puerto Rico', 'forminator' ),
		'QA' => esc_html__( 'Qatar', 'forminator' ),
		'RE' => esc_html__( 'Reunion Island', 'forminator' ),
		'RO' => esc_html__( 'Romania', 'forminator' ),
		'RU' => esc_html__( 'Russia', 'forminator' ),
		'RW' => esc_html__( 'Rwanda', 'forminator' ),
		'KN' => esc_html__( 'Saint Kitts and Nevis', 'forminator' ),
		'LC' => esc_html__( 'Saint Lucia', 'forminator' ),
		'VC' => esc_html__( 'Saint Vincent and the Grenadines', 'forminator' ),
		'WS' => esc_html__( 'Samoa', 'forminator' ),
		'SH' => esc_html__( 'Saint Helena', 'forminator' ),
		'PM' => html_entity_decode( esc_html__( 'Saint Pierre & Miquelon', 'forminator' ), ENT_QUOTES ),
		'SM' => esc_html__( 'San Marino', 'forminator' ),
		'ST' => esc_html__( 'Sao Tome and Principe', 'forminator' ),
		'SA' => esc_html__( 'Saudi Arabia', 'forminator' ),
		'SN' => esc_html__( 'Senegal', 'forminator' ),
		'RS' => esc_html__( 'Serbia', 'forminator' ),
		'SC' => esc_html__( 'Seychelles', 'forminator' ),
		'SL' => esc_html__( 'Sierra Leone', 'forminator' ),
		'SG' => esc_html__( 'Singapore', 'forminator' ),
		'MF' => esc_html__( 'Sint Maarten', 'forminator' ),
		'SK' => esc_html__( 'Slovakia', 'forminator' ),
		'SI' => esc_html__( 'Slovenia', 'forminator' ),
		'SB' => esc_html__( 'Solomon Islands', 'forminator' ),
		'SO' => esc_html__( 'Somalia', 'forminator' ),
		'ZA' => esc_html__( 'South Africa', 'forminator' ),
		'GS' => esc_html__( 'South Georgia and South Sandwich', 'forminator' ),
		'ES' => esc_html__( 'Spain', 'forminator' ),
		'LK' => esc_html__( 'Sri Lanka', 'forminator' ),
		'XX' => esc_html__( 'Stateless Persons', 'forminator' ),
		'SD' => esc_html__( 'Sudan', 'forminator' ),
		'SS' => esc_html__( 'Sudan, South', 'forminator' ),
		'SR' => esc_html__( 'Suriname', 'forminator' ),
		'SJ' => esc_html__( 'Svalbard and Jan Mayen', 'forminator' ),
		'SZ' => esc_html__( 'Swaziland', 'forminator' ),
		'SE' => esc_html__( 'Sweden', 'forminator' ),
		'CH' => esc_html__( 'Switzerland', 'forminator' ),
		'SY' => esc_html__( 'Syria', 'forminator' ),
		'TW' => esc_html__( 'Taiwan, Republic of China', 'forminator' ),
		'TJ' => esc_html__( 'Tajikistan', 'forminator' ),
		'TZ' => esc_html__( 'Tanzania', 'forminator' ),
		'TH' => esc_html__( 'Thailand', 'forminator' ),
		'TG' => esc_html__( 'Togo', 'forminator' ),
		'TK' => esc_html__( 'Tokelau', 'forminator' ),
		'TO' => esc_html__( 'Tonga', 'forminator' ),
		'TT' => esc_html__( 'Trinidad and Tobago', 'forminator' ),
		'TN' => esc_html__( 'Tunisia', 'forminator' ),
		'TR' => esc_html__( 'Turkey', 'forminator' ),
		'TM' => esc_html__( 'Turkmenistan', 'forminator' ),
		'TC' => esc_html__( 'Turks And Caicos Islands', 'forminator' ),
		'TV' => esc_html__( 'Tuvalu', 'forminator' ),
		'UG' => esc_html__( 'Uganda', 'forminator' ),
		'UA' => esc_html__( 'Ukraine', 'forminator' ),
		'AE' => esc_html__( 'United Arab Emirates', 'forminator' ),
		'GB' => esc_html__( 'United Kingdom', 'forminator' ),
		'UM' => esc_html__( 'US Minor Outlying Islands', 'forminator' ),
		'US' => esc_html__( 'United States of America (USA)', 'forminator' ),
		'UY' => esc_html__( 'Uruguay', 'forminator' ),
		'UZ' => esc_html__( 'Uzbekistan', 'forminator' ),
		'VU' => esc_html__( 'Vanuatu', 'forminator' ),
		'VA' => esc_html__( 'Vatican City', 'forminator' ),
		'VE' => esc_html__( 'Venezuela', 'forminator' ),
		'VN' => esc_html__( 'Vietnam', 'forminator' ),
		'VG' => esc_html__( 'Virgin Islands, British', 'forminator' ),
		'VI' => esc_html__( 'Virgin Islands, U.S.', 'forminator' ),
		'WF' => esc_html__( 'Wallis And Futuna Islands', 'forminator' ),
		'EH' => esc_html__( 'Western Sahara', 'forminator' ),
		'YE' => esc_html__( 'Yemen', 'forminator' ),
		'ZM' => esc_html__( 'Zambia', 'forminator' ),
		'ZW' => esc_html__( 'Zimbabwe', 'forminator' ),
	);
	asort( $countries );
	return apply_filters( 'forminator_countries_list', $countries );
}

/**
 * Return sorted available fields
 *
 * @since 1.6
 *
 * @param string $sort_attr Attribute.
 * @param int    $sort_flag Sort flag.
 *
 * @return array
 */
function forminator_get_fields_sorted( $sort_attr, $sort_flag = SORT_ASC ) {
	$fields       = array();
	$fields_array = forminator_get_fields();

	if ( ! empty( $fields_array ) ) {
		foreach ( $fields_array as $key => $field ) {
			$field_key = '';
			if ( isset( $field->$sort_attr ) ) {
				$field_key = $field->$sort_attr;
			}

			if ( ! empty( $field_key ) ) {
				if ( isset( $fields[ $field_key ] ) ) {
					if ( is_int( $field_key ) ) {
						$field_key = (int) max( array_keys( $fields ) );
						++$field_key;// increase where there is dupe.
					}
				}
				$fields[ $field_key ] = $field;
			} else {
				$fields[] = $field;
			}
		}
	}

	if ( SORT_ASC === $sort_flag ) {
		ksort( $fields );
	} else {
		krsort( $fields );
	}

	$fields = array_values( $fields );

	return apply_filters( 'forminator_fields_sorted', $fields, $fields_array, $sort_attr, $sort_flag );
}

/**
 * Retrieves the list of common file extensions and their types.
 *
 * Extending @see get_allowed_mime_types without filter
 *
 * @since 1.6
 */
function forminator_get_ext_types() {
	/**
	 * - image
	 * - audio
	 * - video
	 * - text
	 * - Doc
	 * - Archive
	 * - Interactive
	 */

	$forminator_types = array(
		'image'       => array(
			// Image formats.
			'jpg|jpeg|jpe' => 'image/jpeg',
			'gif'          => 'image/gif',
			'png'          => 'image/png',
			'bmp'          => 'image/bmp',
			'tiff|tif'     => 'image/tiff',
			'ico'          => 'image/x-icon',
			'psd'          => 'application/octet-stream',
			'xcf'          => 'application/octet-stream',
			'heic'         => 'image/heic',
			'webp'         => 'image/webp',
		),
		'audio'       => array(
			// Audio formats.
			'mp3|m4a|m4b' => 'audio/mpeg',
			'ra|ram'      => 'audio/x-realaudio',
			'wav|x-wav'   => 'audio/wav',
			'ogg|oga'     => 'audio/ogg',
			'mid|midi'    => 'audio/midi',
			'wma'         => 'audio/x-ms-wma',
			'wax'         => 'audio/x-ms-wax',
			'mka'         => 'audio/x-matroska',
			'aac'         => 'audio/aac',
			'flac'        => 'audio/flac',
		),
		'video'       => array(
			'asf|asx'      => 'video/x-ms-asf',
			'wmv'          => 'video/x-ms-wmv',
			'wmx'          => 'video/x-ms-wmx',
			'wm'           => 'video/x-ms-wm',
			'avi'          => 'video/avi',
			'divx'         => 'video/divx',
			'flv'          => 'video/x-flv',
			'mov|qt'       => 'video/quicktime',
			'mpeg|mpg|mpe' => 'video/mpeg',
			'mp4|m4v'      => 'video/mp4',
			'ogv'          => 'video/ogg',
			'webm'         => 'video/webm',
			'mkv'          => 'video/x-matroska',
			'3gp|3gpp'     => 'video/3gpp', // Can also be audio.
			'3g2|3gp2'     => 'video/3gpp2', // Can also be audio.
		),
		'document'    => array(
			// MS Office formats.
			'doc'                          => 'application/msword',
			'pot|pps|ppt'                  => 'application/vnd.ms-powerpoint',
			'wri'                          => 'application/vnd.ms-write',

			'mdb'                          => 'application/vnd.ms-access',
			'mpp'                          => 'application/vnd.ms-project',
			'docx'                         => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
			'docm'                         => 'application/vnd.ms-word.document.macroEnabled.12',
			'dotx'                         => 'application/vnd.openxmlformats-officedocument.wordprocessingml.template',
			'dotm'                         => 'application/vnd.ms-word.template.macroEnabled.12',

			'onetoc|onetoc2|onetmp|onepkg' => 'application/onenote',

			// OpenOffice formats.
			'odt'                          => 'application/vnd.oasis.opendocument.text',
			'odp'                          => 'application/vnd.oasis.opendocument.presentation',
			'odg'                          => 'application/vnd.oasis.opendocument.graphics',
			'odc'                          => 'application/vnd.oasis.opendocument.chart',
			'odb'                          => 'application/vnd.oasis.opendocument.database',
			'odf'                          => 'application/vnd.oasis.opendocument.formula',

			'pages'                        => 'application/vnd.apple.pages',

			'wp|wpd'                       => 'application/wordperfect',

			'pdf'                          => 'application/pdf',
			'oxps'                         => 'application/oxps',
			'xps'                          => 'application/vnd.ms-xpsdocument',

		),
		'archive'     => array(
			'tar'     => 'application/x-tar',
			'zip'     => 'application/zip',
			'gz|gzip' => 'application/x-gzip',
			'7z'      => 'application/x-7z-compressed',
		),
		'text'        => array(
			// Text formats.
			'txt|asc|c|cc|h|srt' => 'text/plain',
			'csv'                => 'text/csv',
			'tsv'                => 'text/tab-separated-values',
			'ics'                => 'text/calendar',
			'rtx'                => 'text/richtext',
			'rtf'                => 'application/rtf',
			'vtt'                => 'text/vtt',
		),
		'spreadsheet' => array(
			'xla|xls|xlt|xlw' => 'application/vnd.ms-excel',
			'xlsx'            => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
			'xlsm'            => 'application/vnd.ms-excel.sheet.macroEnabled.12',
			'xlsb'            => 'application/vnd.ms-excel.sheet.binary.macroEnabled.12',
			'xltx'            => 'application/vnd.openxmlformats-officedocument.spreadsheetml.template',
			'xltm'            => 'application/vnd.ms-excel.template.macroEnabled.12',
			'xlam'            => 'application/vnd.ms-excel.addin.macroEnabled.12',
			'ods'             => 'application/vnd.oasis.opendocument.spreadsheet',
			'numbers'         => 'application/vnd.apple.numbers',
		),
		'interactive' => array(
			'class' => 'application/java',
			'key'   => 'application/vnd.apple.keynote',
			'pptx'  => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
			'pptm'  => 'application/vnd.ms-powerpoint.presentation.macroEnabled.12',
			'ppsx'  => 'application/vnd.openxmlformats-officedocument.presentationml.slideshow',
			'ppsm'  => 'application/vnd.ms-powerpoint.slideshow.macroEnabled.12',
			'potx'  => 'application/vnd.openxmlformats-officedocument.presentationml.template',
			'potm'  => 'application/vnd.ms-powerpoint.template.macroEnabled.12',
			'ppam'  => 'application/vnd.ms-powerpoint.addin.macroEnabled.12',
			'sldx'  => 'application/vnd.openxmlformats-officedocument.presentationml.slide',
			'sldm'  => 'application/vnd.ms-powerpoint.slide.macroEnabled.12',
		),
	);

	foreach ( $forminator_types as $type => $forminator_type ) {
		$forminator_types[ $type ] = array_keys( $forminator_type );
	}

	/**
	 * Filter extensions types of files
	 *
	 * @since 1.6
	 *
	 * @param array $forminator_types
	 */
	$forminator_types = apply_filters( 'forminator_get_ext_types', $forminator_types );

	return $forminator_types;
}

/**
 * Format poll data variables to html formatted
 *
 * @since 1.6.1
 *
 * @param string                      $content Content.
 * @param Forminator_Poll_Model       $poll Forminator_Poll_Model.
 * @param array                       $data - submitted `_POST` data.
 * @param Forminator_Form_Entry_Model $entry Forminator_Form_Entry_Model.
 *
 * @return mixed
 */
function forminator_replace_poll_form_data( $content, Forminator_Poll_Model $poll, $data, Forminator_Form_Entry_Model $entry ) {
	if ( stripos( $content, '{poll_name}' ) !== false ) {
		$poll_name = forminator_get_name_from_model( $poll );
		$content   = str_ireplace( '{poll_name}', $poll_name, $content );
	}

	if ( stripos( $content, '{poll_answer}' ) !== false ) {
		$answer_data   = isset( $data[ $poll->id ] ) ? $data[ $poll->id ] : '';
		$extra_field   = isset( $data[ $poll->id . '-extra' ] ) ? $data[ $poll->id . '-extra' ] : '';
		$fields_labels = $poll->pluck_fields_array( 'title', 'element_id', '1' );

		$answer_label = isset( $fields_labels[ $answer_data ] ) ? $fields_labels[ $answer_data ] : '';
		if ( ! empty( $extra_field ) ) {
			$answer_label .= ' ' . $extra_field;
		}
		$content = str_ireplace( '{poll_answer}', $answer_label, $content );
	}

	if ( stripos( $content, '{poll_result}' ) !== false ) {
		$poll_results = array();
		$fields_array = $poll->get_fields_as_array();
		$map_entries  = Forminator_Form_Entry_Model::map_polls_entries( $poll->id, $fields_array );
		$fields       = $poll->get_fields();
		if ( ! is_null( $fields ) ) {
			foreach ( $fields as $field ) {
				$label = addslashes( $field->title );

				$slug    = isset( $field->slug ) ? $field->slug : sanitize_title( $label );
				$entries = 0;
				if ( in_array( $slug, array_keys( $map_entries ), true ) ) {
					$entries = $map_entries[ $slug ];
				}
				$poll_results[] = array(
					'label' => $label,
					'value' => $entries,
				);
			}
		}

		$poll_results_html = '<ul>';
		foreach ( $poll_results as $poll_result ) {
			$poll_results_html .= '<li>';
			$poll_results_html .= '<strong>' . $poll_result['label'] . '</strong> : ' . $poll_result['value'];
			$poll_results_html .= '</li>';
		}
		$poll_results_html .= '</ul>';
		$content            = str_ireplace( '{poll_result}', $poll_results_html, $content );
	}

	return apply_filters( 'forminator_replace_poll_form_data', $content, $poll, $data, $entry );
}

/**
 * Return vars for poll
 *
 * @since 1.6.1
 * @return mixed
 */
function forminator_get_poll_vars() {
	$vars_list = array(
		'poll_name'   => esc_html__( 'Poll Name', 'forminator' ),
		'poll_answer' => esc_html__( 'Poll Answer', 'forminator' ),
		'poll_result' => esc_html__( 'Poll Result', 'forminator' ),
	);

	/**
	 * Filter forminator Poll var list
	 *
	 * @see   forminator_replace_poll_form_data()
	 *
	 * @since 1.6.1
	 *
	 * @param array $vars_list
	 */
	return apply_filters( 'forminator_poll_vars_list', $vars_list );
}

/**
 * Format quiz data variables to html formatted
 *
 * @since 1.6.2
 *
 * @param string                      $content Content.
 * @param Forminator_Quiz_Model       $quiz Forminator_Quiz_Model.
 * @param Forminator_Form_Entry_Model $entry Forminator_Form_Entry_Model.
 *
 * @return mixed
 */
function forminator_replace_quiz_form_data( $content, Forminator_Quiz_Model $quiz, Forminator_Form_Entry_Model $entry ) {
	$result_behav = isset( $quiz->settings['results_behav'] ) ? $quiz->settings['results_behav'] : '';

	if ( stripos( $content, '{quiz_name}' ) !== false ) {
		$quiz_name = forminator_get_name_from_model( $quiz );
		$content   = str_ireplace( '{quiz_name}', $quiz_name, $content );
	}

	if ( stripos( $content, '{quiz_type}' ) !== false ) {
		$quiz_type = '';
		if ( 'knowledge' === $quiz->quiz_type ) {
			$quiz_type = 'Knowledge';
		} elseif ( 'nowrong' === $quiz->quiz_type ) {
			$quiz_type = 'Personality';
		}
		$content = str_ireplace( '{quiz_type}', $quiz_type, $content );
	}
	$answers = isset( Forminator_CForm_Front_Action::$prepared_data['answers'] )
			? Forminator_CForm_Front_Action::$prepared_data['answers']
			: array();

	// For single answer quiz.
	if ( stripos( $content, '{quiz_answer}' ) !== false && ( empty( $result_behav ) || 'after' === $result_behav ) ) {
		$answer_content = PHP_EOL . '<ul>' . PHP_EOL;
		if ( is_array( $answers ) ) {
			foreach ( $answers as $question_id => $answer_id ) {
				$question = $quiz->getQuestion( $question_id );
				$answer   = $quiz->getAnswer( $question_id, $answer_id );

				$question_text = isset( $question['title'] ) ? $question['title'] : '';
				$answer_text   = isset( $answer['title'] ) ? $answer['title'] : '';

				$answer_content .= '<li>' . PHP_EOL;

				$answer_content .= '<ul>' . PHP_EOL;
				$answer_content .= '<li><b>' . esc_html__( 'Question : ', 'forminator' ) . '</b>' . esc_html( $question_text ) . '</li>' . PHP_EOL;
				$answer_content .= '<li><b>' . esc_html__( 'Answer : ', 'forminator' ) . '</b>' . esc_html( $answer_text ) . '</li>' . PHP_EOL;
				$answer_content .= '</ul>' . PHP_EOL;

				$answer_content .= '</li>' . PHP_EOL;
			}
		}
		$answer_content .= '</ul>';

		$content = str_ireplace( '{quiz_answer}', $answer_content, $content );

	}

	// For multi-answer quiz.
	if ( stripos( $content, '{quiz_answer}' ) !== false && 'end' === $result_behav ) {
		$answer_content = PHP_EOL . '<ul>' . PHP_EOL;
		$question_ids   = array();
		if ( is_array( $answers ) ) {
			foreach ( $answers as $question_id => $answer_id ) {
				// In multi-answer, $question_id looks like this "question-2051-7608-3".
				$question_id = preg_replace( '/(-\d+$)/', '', $question_id );
				if ( ! in_array( $question_id, $question_ids ) ) { // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
					$answer                         = $quiz->getAnswer( $question_id, $answer_id );
					$answer_text                    = isset( $answer['title'] ) ? $answer['title'] : '';
					$question_ids[ $question_id ][] = $answer_text;
				}
			}
			foreach ( $question_ids as $question_id => $answer_titles ) {
				$question      = $quiz->getQuestion( $question_id );
				$question_text = isset( $question['title'] ) ? $question['title'] : '';
				$answer_head   = count( $answer_titles ) > 1 ? esc_html__( 'Answers : ', 'forminator' ) : esc_html__( 'Answer : ', 'forminator' );

				$answer_content         .= '<li>' . PHP_EOL;
					$answer_content     .= '<ul>' . PHP_EOL;
						$answer_content .= '<li><b>' . esc_html__( 'Question : ', 'forminator' ) . '</b>' . esc_html( $question_text ) . '</li>' . PHP_EOL;
						$answer_content .= '<li><b>' . $answer_head . '</b>' . esc_html( implode( ', ', $answer_titles ) ) . '</li>' . PHP_EOL;
					$answer_content     .= '</ul>' . PHP_EOL;
				$answer_content         .= '</li>' . PHP_EOL;
			}
		}
		$answer_content .= '</ul>';

		$content = str_ireplace( '{quiz_answer}', $answer_content, $content );

	}

	if ( stripos( $content, '{quiz_result}' ) !== false ) {
		$result_content = '';
		// we saved on $entry->meta_data['entry']['value'] => make sure its fulfilled before going further.
		if ( ! empty( $entry->meta_data ) && isset( $entry->meta_data['entry'] ) && isset( $entry->meta_data['entry']['value'] ) ) {

			if ( 'knowledge' === $quiz->quiz_type ) {
				$answers              = $entry->meta_data['entry']['value'];
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

				$result_content  = PHP_EOL . '<ul>' . PHP_EOL;
				$result_content .= '<li>' .
								sprintf(
								/* Translators: 1. Opening <b> tag, 2. closing <b> tag 3. Correct answer count */
									esc_html__( '%1$sCorrect Answers%2$s : %3$d', 'forminator' ),
									'<b>',
									'</b>',
									$correct_answer_count
								) .
								'</li>' . PHP_EOL;

				$result_content .= '<li>' .
								sprintf(
								/* Translators: 1. Opening <b> tag, 2. closing <b> tag 3. Total answer */
									esc_html__( '%1$sTotal Question Answered%2$s : %3$d', 'forminator' ),
									'<b>',
									'</b>',
									$total_answer
								) .
								'</li>' . PHP_EOL;
				$result_content .= '</ul>';
			} elseif ( 'nowrong' === $quiz->quiz_type ) {
				$meta = $entry->meta_data['entry']['value'];

				// i know its complicated as eff, but this is how it saved since day 1.
				// and migrating this might pita and affect performance.
				if ( isset( $meta[0] ) && isset( $meta[0]['value'] ) && isset( $meta[0]['value']['result'] ) ) {
					$result         = $meta[0]['value']['result'];
					$result_content = isset( $result['title'] ) ? esc_html( (string) $result['title'] ) : '';
				}
			}
		}
		$content = str_ireplace( '{quiz_result}', $result_content, $content );
		$content = do_shortcode( $content );
	}

	return apply_filters( 'forminator_replace_quiz_form_data', $content, $quiz, Forminator_CForm_Front_Action::$prepared_data, $entry );
}

/**
 * Return uniq key value
 *
 * @since 1.13
 *
 * @return string
 */
function forminator_unique_key() {
	return wp_rand( 1000, 9999 ) . '-' . wp_rand( 1000, 9999 );
}

/**
 * Return vars for quiz
 *
 * @since 1.6.2
 * @return array
 */
function forminator_get_quiz_vars() {
	$vars_list = array(
		'quiz_name'   => esc_html__( 'Quiz Name', 'forminator' ),
		'quiz_answer' => esc_html__( 'Quiz Answer', 'forminator' ),
		'quiz_result' => esc_html__( 'Quiz Result', 'forminator' ),
	);

	/**
	 * Filter forminator Quiz var list
	 *
	 * @see   forminator_replace_quiz_form_data()
	 *
	 * @since 1.6.2
	 *
	 * @param array $vars_list
	 */
	return apply_filters( 'forminator_quiz_vars_list', $vars_list );
}

/**
 * Replace Stripe data
 *
 * @param string                      $content Content.
 * @param Forminator_Form_Model       $custom_form Forminator_Form_Model.
 * @param Forminator_Form_Entry_Model $entry Forminator_Form_Entry_Model.
 *
 * @return mixed
 */
function forminator_replace_form_payment_data( $content, ?Forminator_Form_Model $custom_form = null, ?Forminator_Form_Entry_Model $entry = null ) {
	if ( empty( $custom_form ) ) {
		return $content;
	}
	$payment_meta = forminator_payment_data( $content, $custom_form, $entry );
	if ( ! empty( $payment_meta ) ) {
		$replaces = array(
			'{payment_mode}'     => $payment_meta['mode'],
			'{payment_status}'   => $payment_meta['status'],
			'{payment_amount}'   => Forminator_Field::get_formatted_amount( $payment_meta['field'], $payment_meta, $custom_form ),
			'{payment_currency}' => $payment_meta['currency'],
			'{transaction_id}'   => $payment_meta['transaction_id'],
			'{subscription_id}'  => ! empty( $payment_meta['subscription_id'] ) ? $payment_meta['subscription_id'] : '',
		);

		$content = str_replace( array_keys( $replaces ), array_values( $replaces ), $content );
	}

	return apply_filters( 'forminator_replace_form_payment_data', $content, $custom_form, $entry );
}

/**
 * Payment data
 *
 * @param string                      $content Content.
 * @param Forminator_Form_Model       $custom_form Forminator_Form_Model.
 * @param Forminator_Form_Entry_Model $entry Forminator_Form_Entry_Model.
 *
 * @return array|mixed
 */
function forminator_payment_data( $content, $custom_form, $entry ) {
	if ( empty( $custom_form ) ) {
		return $content;
	}
	$payment_meta = array();
	$form_fields  = $custom_form->get_fields();
	if ( ! empty( $form_fields ) && ! empty( $entry ) ) {
		foreach ( $form_fields as $field ) {
			$field_type = $field->__get( 'type' );
			if ( in_array( $field_type, array( 'stripe', 'stripe-ocs', 'paypal' ), true ) && ! empty( $entry->meta_data[ $field->slug ] ) ) {
				$payment_meta                   = $entry->meta_data[ $field->slug ]['value'];
				$payment_meta['payment_method'] = $field_type;
				// Include field data for getting the formatted amount.
				$payment_meta['field'] = $field->to_array();
			}
		}
	}

	return $payment_meta;
}

/**
 * Get Default date format
 *
 * @param string $format Date format.
 *
 * @return string
 */
function datepicker_default_format( $format ) {
	switch ( $format ) {
		case 'mm/dd/yy':
			$format = 'm/d/Y';
			break;
		case 'mm.dd.yy':
			$format = 'm.d.Y';
			break;
		case 'mm-dd-yy':
			$format = 'm-d-Y';
			break;
		case 'yy-mm-dd':
			$format = 'Y-m-d';
			break;
		case 'yy.mm.dd':
			$format = 'Y.m.d';
			break;
		case 'yy/mm/dd':
			$format = 'Y/m/d';
			break;
		case 'dd/mm/yy':
			$format = 'd/m/Y';
			break;
		case 'dd.mm.yy':
			$format = 'd.m.Y';
			break;
		case 'dd-mm-yy':
			$format = 'd-m-Y';
			break;
		default:
			$format = get_option( 'date_format' );
			break;
	}

	return $format;
}

/**
 * Reformat date
 *
 * @since 1.18.0
 *
 * @param string $date_value - The date.
 * @param string $date_format - The current date format.
 * @param string $new_format - The new date format.
 *
 * @return string
 */
function forminator_reformat_date( $date_value, $date_format, $new_format ) {
	$date        = new Forminator_Date();
	$date_format = $date->normalize_date_format( $date_format );
	$date        = date_create_from_format( $date_format, $date_value );

	return date_format( $date, $new_format );
}

/**
 * Get entry field value helper
 *
 * @since 1.14
 *
 * @param Forminator_Form_Entry_Model $entry Forminator_Form_Entry_Model.
 * @param array                       $mapper Mapper.
 * @param string                      $sub_meta_key Meta key.
 * @param bool                        $allow_html Allow HTML.
 * @param int                         $truncate Truncate.
 *
 * @return string
 */
function forminator_get_entry_field_value( $entry, $mapper, $sub_meta_key = '', $allow_html = false, $truncate = PHP_INT_MAX ) {
	/**
	 * Forminator_Form_Entry_Model
	 *
	 * @var Forminator_Form_Entry_Model $entry */
	if ( isset( $mapper['property'] ) ) {
		if ( property_exists( $entry, $mapper['property'] ) ) {
			$property = $mapper['property'];
			// casting property to string.
			$value = (string) $entry->$property;
		} else {
			$value = '';
		}
	} elseif ( 'group' === $mapper['type'] ) {
		$meta_value = $entry->get_meta( $sub_meta_key, '' );
		$field_type = Forminator_Core::get_field_type( $sub_meta_key );
		$field      = array();
		foreach ( $mapper['sub_metas'] as $sub_meta ) {
			if ( isset( $sub_meta['meta_key'] ) && false !== strpos( $sub_meta_key, $sub_meta['meta_key'] ) ) {
				$field = $sub_meta['field'] ?? array();
				break;
			}
		}
		$value = Forminator_Form_Entry_Model::meta_value_to_string( $field_type, $meta_value, $allow_html, $truncate, $field );
	} else {
		$meta_value = $entry->get_meta( $mapper['meta_key'], '' );
		$field_keys = array_keys( $entry->meta_data );

		// Fix for Stripe OCS and Stripe old field to show only one.
		if ( 'stripe-ocs' === $mapper['type'] ) {
			if ( in_array( 'stripe-1', $field_keys, true ) && empty( $meta_value['mode'] ) ) {
				$meta_value = $entry->get_meta( 'stripe-1', '' );
			}
		}

		// meta_key based.
		if ( ! isset( $mapper['sub_metas'] ) ) {
			$value = Forminator_Form_Entry_Model::meta_value_to_string( $mapper['type'], $meta_value, $allow_html, $truncate, $mapper['field'] ?? null );
		} elseif ( empty( $sub_meta_key ) ) {
				$value = '';
		} elseif ( isset( $meta_value[ $sub_meta_key ] ) && ! empty( $meta_value[ $sub_meta_key ] ) ) {
				$value      = $meta_value[ $sub_meta_key ];
				$field_type = $mapper['type'] . '.' . $sub_meta_key;
				$value      = Forminator_Form_Entry_Model::meta_value_to_string( $field_type, $value, $allow_html, $truncate, $mapper['field'] ?? null );
		} else {
			$value = '';
		}
	}

	/**
	 * Filter Get entry field value
	 *
	 * @param string $value Current value.
	 * @param object $entry Forminator_Form_Entry_Model object.
	 * @param array  $mapper Mapper property.
	 * @param string $sub_meta_key Sub meta key.
	 * @param bool   $allow_html Allow HTML.
	 * @param int    $truncate Truncate.
	 */
	return apply_filters( 'forminator_get_entry_field_value', $value, $entry, $mapper, $sub_meta_key, $allow_html, $truncate );
}

/**
 * Forminator upload path
 *
 * @return string|null|WP_Error
 */
function forminator_upload_root_temp() {
	$upload_root_path = forminator_upload_root();
	if ( is_wp_error( $upload_root_path ) ) {
		return $upload_root_path;
	}

	return $upload_root_path . 'temp';
}

/**
 * Forminator Upload root
 *
 * @param string $base Base type.
 *
 * @return string|null|WP_Error
 */
function forminator_upload_root( $base = 'path' ) {
	$dir = wp_upload_dir();

	if ( $dir['error'] ) {
		return new WP_Error( 'upload_dir_error', $dir['error'] );
	}

	$upload_root = forminator_custom_upload_root( $dir, $base );
	if ( ! empty( $upload_root ) ) {
		return esc_html( $upload_root );
	}

	$upload_base = 'url' === $base ? $dir['baseurl'] : $dir['basedir'];

	return esc_html( $upload_base ) . '/forminator/';
}

/**
 * Forminator custom root
 *
 * @param array  $dir Directory.
 * @param string $base Base.
 *
 * @return string
 */
function forminator_custom_upload_root( $dir, $base ) {
	$custom_path   = '';
	$custom_upload = get_option( 'forminator_custom_upload' );
	if ( isset( $custom_upload ) && $custom_upload ) {
		$upload_root = get_option( 'forminator_custom_upload_root' );
		$upload_root = basename( $upload_root );
		if ( ! str_contains( $upload_root, $dir['basedir'] ) ) {
			$upload_root = $dir['basedir'] . '/' . $upload_root;
		}

		if ( ! empty( $upload_root ) ) {
			if ( 'url' === $base ) {
				$dir_path    = str_replace( $dir['basedir'], '', $upload_root );
				$upload_root = $dir['baseurl'] . $dir_path;
			}
			$custom_path = esc_html( $upload_root ) . '/';
		}
	}

	return $custom_path;
}

/**
 * Forminator get upload path
 *
 * @param int    $form_id Form Id.
 * @param string $dir Directory.
 * @param string $base Base.
 *
 * @return string
 */
function forminator_get_upload_path( $form_id, $dir = '', $base = 'path' ) {
	$form_id = absint( $form_id );

	$sub_folder = $form_id . '_' . wp_hash( $form_id ) . '/' . $dir;

	/**
	 * Filter Custom upload subfolder
	 *
	 * @param string $sub_folder Upload subfolder.
	 * @param int $form_id Module ID.
	 * @param string $dir Sub directory.
	 */
	$sub_folder = apply_filters( 'forminator_custom_upload_subfolder', $sub_folder, $form_id, $dir );

	$upload_root = forminator_upload_root( $base );
	if ( is_wp_error( $upload_root ) ) {
		$upload_root = null;
	}

	return wp_normalize_path( $upload_root . $sub_folder );
}

/**
 * Forminator get upload url
 *
 * @param int    $form_id Form Id.
 * @param string $dir Directory.
 *
 * @return string
 */
function forminator_get_upload_url( $form_id, $dir = '' ) {
	$upload_path = forminator_get_upload_path( $form_id, $dir, 'url' );

	$upload_url = str_replace(
		wp_normalize_path( untrailingslashit( ABSPATH ) ),
		site_url(),
		wp_normalize_path( $upload_path )
	);

	return $upload_url;
}


/**
 * Replace lead form data
 *
 * @param string                           $content Content.
 * @param array                            $quiz_settings Quiz settings.
 * @param Forminator_Form_Entry_Model|null $entry Forminator_Form_Entry_Model.
 *
 * @return string
 */
function forminator_replace_lead_form_data( $content, $quiz_settings, ?Forminator_Form_Entry_Model $entry = null ) {

	if ( isset( $quiz_settings['hasLeads'] ) && $quiz_settings['hasLeads'] ) {
		$lead_id           = isset( $quiz_settings['leadsId'] ) ? $quiz_settings['leadsId'] : 0;
		$lead_model        = Forminator_Base_Form_Model::get_model( $lead_id );
		$form_entry_fields = forminator_lead_entry_data( $entry );
		$submitted_data    = forminator_lead_submitted_data( $form_entry_fields );
		$entry->meta_data  = $form_entry_fields;
		$content           = forminator_replace_form_data( $content, $lead_model, $entry );
	}

	return $content;
}

/**
 * Lead form data
 *
 * @param Forminator_Form_Entry_Model $entry Entry Model.
 *
 * @return array
 */
function forminator_lead_entry_data( $entry ) {
	$entry_data = array();
	if ( isset( $entry->meta_data['lead_entry'] ) && ! empty( $entry->meta_data['lead_entry']['value'] ) ) {
		$entry_data = $entry->meta_data['lead_entry']['value'];
	}

	return $entry_data;
}

/**
 * Lead form submitted data
 *
 * @param mixed $lead_entry Lead entry.
 *
 * @return array
 */
function forminator_lead_submitted_data( $lead_entry ) {
	$meta_data = array();
	if ( ! empty( $lead_entry ) ) {
		foreach ( $lead_entry as $lead_value ) {
			if ( ! empty( $lead_value['value'] ) && is_array( $lead_value['value'] ) ) {
				foreach ( $lead_value['value'] as $multi => $multi_value ) {
					if ( ! empty( $multi_value ) && is_array( $multi_value ) ) {
						foreach ( $multi_value as $sub => $sub_value ) {
							$meta_sub_name               = $lead_value['name'] . '-' . $sub;
							$meta_data[ $meta_sub_name ] = $sub_value;
						}
					} else {
						$meta_name               = $lead_value['name'] . '-' . $multi;
						$meta_data[ $meta_name ] = $multi_value;
					}
				}
			} else {
				$meta_data[ $lead_value['name'] ] = $lead_value['value'];
			}
		}
	}

	return $meta_data;
}

/**
 * Check how many instances of a field type exists in a given post ID
 *
 * @param string $field_type Field type.
 *
 * @return int
 */
function forminator_count_field_type_in_page( $field_type ) {
	global $post;

	if ( empty( $post->ID ) ) {
		return 0;
	}

	$page        = get_post( $post->ID );
	$content     = $page->post_content;
	$field_count = 0;
	$form        = Forminator_Form_Model::model();

	// Check for forminator shortcodes.
	preg_match_all(
		'/' . get_shortcode_regex( array( 'forminator_form' ) ) . '/',
		$content,
		$matches,
		PREG_SET_ORDER
	);

	// Increase count for every $field_type found.
	foreach ( $matches as $shortcode ) {
		preg_match_all( '!\d+!', $shortcode[0], $form_ids );
		$form        = $form->load( $form_ids[0][0] );
		$form_fields = $form->get_fields();

		foreach ( $form_fields as $field ) {
			if ( false !== strpos( $field->slug, $field_type ) ) {
				++$field_count;
			}
		}
	}

	return $field_count;
}

/**
 * Trim field values
 *
 * @since 1.15.15
 *
 * @param array $value Value array.
 *
 * @return array
 */
function forminator_trim_array( $value ) {

	foreach ( $value as $key => $val ) {
		if ( is_array( $val ) ) {
			$value[ $key ] = forminator_trim_array( $val );
		} else {
			$value[ $key ] = strtolower( wp_unslash( trim( $val ) ) );
		}
	}

	return $value;
}

/**
 * Convert special HTML entities back to characters
 *
 * @since 1.40
 * @param array $value Value array.
 * @return array
 */
function forminator_htmlspecialchars_decode_array( $value ) {
	foreach ( $value as $key => $val ) {
		if ( is_array( $val ) ) {
			$value[ $key ] = forminator_htmlspecialchars_decode_array( $val );
		} else {
			$value[ $key ] = htmlspecialchars_decode( $val );
		}
	}

	return $value;
}

/**
 * Get cloned fields keys
 *
 * @param object $entry Entry object.
 * @param array  $original_keys All field slugs of the current group field.
 * @return array
 */
function forminator_get_cloned_field_keys( $entry, $original_keys ) {
	if ( empty( $entry->meta_data ) ) {
		return array();
	}

	$field_ids = implode( '|', $original_keys );

	$all_suffixes = preg_filter( '/^(' . $field_ids . ')(-[^-]+)$/', '$2', array_keys( $entry->meta_data ) );
	// Exclude reserved suffixes for complex fields.
	$repeater_suffixes = array_diff(
		$all_suffixes,
		array_map(
			function ( $s ) {
				return '-' . $s;
			},
			$entry::field_suffix()
		)
	);

	return array_unique( $repeater_suffixes );
}

/**
 * Truncate text
 *
 * @since 1.17.0
 *
 * @param string $text Text for truncate.
 * @param int    $truncate   PHP_INT_MAX.
 */
function forminator_truncate_text( $text, $truncate = PHP_INT_MAX ) {
	if ( strlen( $text ) > $truncate ) {
		$text = substr( $text, 0, $truncate ) . '...';
	}

	return $text;
}

/**
 * Query users.
 *
 * @since 1.20.0
 *
 * @param string $search_string  Search query.
 * @param array  $exclude        Array of user IDs to exclude from search.
 *
 * @return array
 */
function forminator_get_users_by_query( $search_string, $exclude ) {
	$params = array(
		'orderby'        => 'ID',
		'order'          => 'DESC',
		'number'         => 10,
		'paged'          => 1,
		'exclude'        => $exclude,
		'search'         => strtolower( $search_string ),
		'search_columns' => array(
			'user_login',
			'user_email',
			'user_nicename',
			'display_name',
		),
	);

	$user_query = new WP_User_Query( $params );

	$users = array();
	foreach ( $user_query->get_results() as $user ) {
		$users[] = array(
			'id'     => $user->ID,
			'name'   => $user->get( 'display_name' ),
			'email'  => $user->get( 'user_email' ),
			'role'   => empty( $user->roles ) ? null : ucfirst( $user->roles[0] ),
			'avatar' => get_avatar_url( $user->get( 'user_email' ) ),
		);
	}

	return $users;
}

/**
 * Forminator Get allow mime type
 *
 * @param array $mimes Mimes.
 * @param bool  $allow Allow.
 *
 * @return array
 */
function forminator_allowed_mime_types( $mimes = array(), $allow = true ) {
	if ( empty( $mimes ) ) {
		$mimes = get_allowed_mime_types();
	}
	if ( ! $allow ) {
		$filters = array( 'htm|html', 'js', 'jse', 'jar', 'php', 'php3', 'php4', 'php5', 'phtml', 'svg', 'swf', 'exe', 'html', 'htm', 'shtml', 'xhtml', 'xml', 'css', 'asp', 'aspx', 'jsp', 'sql', 'hta', 'dll', 'bat', 'com', 'sh', 'bash', 'py', 'pl', 'dfxp', 'rar' );
		foreach ( array_keys( $mimes ) as $mime_key ) {
			$key = strtolower( $mime_key );
			if ( in_array( $key, $filters, true ) ) {
				unset( $mimes[ $mime_key ] );
			}
		}
	}

	return $mimes;
}

/**
 * List custom taxonomies
 *
 * @since 1.23.0
 * @param array $meta_value Meta value.
 * @return array
 */
function forminator_list_custom_taxonomies( $meta_value ) {
	$default_keys = array(
		'post-title',
		'post-content',
		'post-excerpt',
		'category',
		'post_tag',
		'post-custom',
		'post-image',
	);
	$tax_keys     = array_diff( array_keys( $meta_value ), $default_keys );
	return $tax_keys;
}

/**
 * Emulate prepared_data
 *
 * @param Forminator_Base_Form_Model $custom_form_model Base form model.
 * @param object                     $entry Entry data.
 * @return array
 */
function recreate_prepared_data( Forminator_Base_Form_Model $custom_form_model, $entry ) {
	$prepared_data = wp_list_pluck( $entry->meta_data, 'value' );
	$fields        = $custom_form_model->get_real_fields();

	foreach ( $prepared_data as $key => $value ) {
		if ( isset( $value['result'] ) ) {
			$prepared_data[ $key ] = $value['result'];
		} elseif ( is_array( $value ) ) {
			foreach ( $value as $subkey => $subvalue ) {
				$prepared_data[ $key . '-' . $subkey ] = $subvalue;
			}
		} elseif ( 0 === strpos( $key, 'select-' )
					|| 0 === strpos( $key, 'radio-' )
					|| 0 === strpos( $key, 'checkbox-' ) ) {
			foreach ( $fields as $field ) {
				if ( empty( $field->raw['element_id'] ) || $key !== $field->raw['element_id'] ) {
					continue;
				}
				if ( empty( $field->raw['options'] ) || ! is_array( $field->raw['options'] ) ) {
					break;
				}
				$field_labels    = wp_list_pluck( $field->raw['options'], 'label' );
				$field_values    = wp_list_pluck( $field->raw['options'], 'value' );
				$multiple_values = explode( ', ', $value );

				$prepared_data[ $key ] = $multiple_values;
				foreach ( $multiple_values as $multiple_key => $multiple_value ) {
					$field_value_key = array_search( $multiple_value, $field_labels, true );
					if ( false !== $field_value_key ) {
						// Replace saved field Labels to the relevant field values.
						$prepared_data[ $key ][ $multiple_key ] = $field_values[ $field_value_key ];
					}
				}
				break;
			}
		}
	}

	$prepared_data['form_id'] = $entry->form_id;

	return $prepared_data;
}

/**
 * Return state code
 *
 * @since 1.32.0
 * @param string $state Search state.
 * @return string
 */
function forminator_get_state_code( $state ) {
	$state_code = apply_filters(
		'forminator_statecode_list',
		array(
			'USA'              => array(
				'AL' => esc_html__( 'Alabama', 'forminator' ),
				'AK' => esc_html__( 'Alaska', 'forminator' ),
				'AZ' => esc_html__( 'Arizona', 'forminator' ),
				'AR' => esc_html__( 'Arkansas', 'forminator' ),
				'CA' => esc_html__( 'California', 'forminator' ),
				'CO' => esc_html__( 'Colorado', 'forminator' ),
				'CT' => esc_html__( 'Connecticut', 'forminator' ),
				'DE' => esc_html__( 'Delaware', 'forminator' ),
				'DC' => esc_html__( 'District of Columbia', 'forminator' ),
				'FL' => esc_html__( 'Florida', 'forminator' ),
				'GA' => esc_html__( 'Georgia', 'forminator' ),
				'HI' => esc_html__( 'Hawaii', 'forminator' ),
				'ID' => esc_html__( 'Idaho', 'forminator' ),
				'IL' => esc_html__( 'Illinois', 'forminator' ),
				'IN' => esc_html__( 'Indiana', 'forminator' ),
				'IA' => esc_html__( 'Iowa', 'forminator' ),
				'KS' => esc_html__( 'Kansas', 'forminator' ),
				'KY' => esc_html__( 'Kentucky', 'forminator' ),
				'LA' => esc_html__( 'Louisiana', 'forminator' ),
				'ME' => esc_html__( 'Maine', 'forminator' ),
				'MD' => esc_html__( 'Maryland', 'forminator' ),
				'MA' => esc_html__( 'Massachusetts', 'forminator' ),
				'MI' => esc_html__( 'Michigan', 'forminator' ),
				'MN' => esc_html__( 'Minnesota', 'forminator' ),
				'MS' => esc_html__( 'Mississippi', 'forminator' ),
				'MO' => esc_html__( 'Missouri', 'forminator' ),
				'MT' => esc_html__( 'Montana', 'forminator' ),
				'NE' => esc_html__( 'Nebraska', 'forminator' ),
				'NV' => esc_html__( 'Nevada', 'forminator' ),
				'NH' => esc_html__( 'New Hampshire', 'forminator' ),
				'NJ' => esc_html__( 'New Jersey', 'forminator' ),
				'NM' => esc_html__( 'New Mexico', 'forminator' ),
				'NY' => esc_html__( 'New York', 'forminator' ),
				'NC' => esc_html__( 'North Carolina', 'forminator' ),
				'ND' => esc_html__( 'North Dakota', 'forminator' ),
				'OH' => esc_html__( 'Ohio', 'forminator' ),
				'OK' => esc_html__( 'Oklahoma', 'forminator' ),
				'OR' => esc_html__( 'Oregon', 'forminator' ),
				'PA' => esc_html__( 'Pennsylvania', 'forminator' ),
				'PR' => esc_html__( 'Puerto Rico', 'forminator' ),
				'RI' => esc_html__( 'Rhode Island', 'forminator' ),
				'SC' => esc_html__( 'South Carolina', 'forminator' ),
				'SD' => esc_html__( 'South Dakota', 'forminator' ),
				'TN' => esc_html__( 'Tennessee', 'forminator' ),
				'TX' => esc_html__( 'Texas', 'forminator' ),
				'UT' => esc_html__( 'Utah', 'forminator' ),
				'VT' => esc_html__( 'Vermont', 'forminator' ),
				'VA' => esc_html__( 'Virginia', 'forminator' ),
				'WA' => esc_html__( 'Washington', 'forminator' ),
				'WV' => esc_html__( 'West Virginia', 'forminator' ),
				'WI' => esc_html__( 'Wisconsin', 'forminator' ),
				'WY' => esc_html__( 'Wyoming', 'forminator' ),
			),
			'Armed forces APO' => array(
				'AA' => esc_html__( 'Armed Forces Americas', 'forminator' ),
				'AE' => esc_html__( 'Armed Forces Europe', 'forminator' ),
				'AP' => esc_html__( 'Armed Forces Pacific', 'forminator' ),
			),
			'Outlying areas'   => array(
				'AS' => esc_html__( 'American Samoa', 'forminator' ),
				'FM' => esc_html__( 'Federated States of Micronesia', 'forminator' ),
				'GU' => esc_html__( 'Guam', 'forminator' ),
				'MH' => esc_html__( 'Marshall Islands', 'forminator' ),
				'MP' => esc_html__( 'Northern Mariana Islands', 'forminator' ),
				'PW' => esc_html__( 'Palau', 'forminator' ),
				'VI' => esc_html__( 'Virgin Islands', 'forminator' ),
			),
			'Canada'           => array(
				'AB' => esc_html__( 'Alberta', 'forminator' ),
				'BC' => esc_html__( 'British Columbia', 'forminator' ),
				'MB' => esc_html__( 'Manitoba', 'forminator' ),
				'NB' => esc_html__( 'New Brunswick', 'forminator' ),
				'NL' => esc_html__( 'Newfoundland and Labrador', 'forminator' ),
				'NT' => esc_html__( 'Northwest Territories', 'forminator' ),
				'NS' => esc_html__( 'Nova Scotia', 'forminator' ),
				'NU' => esc_html__( 'Nunavut', 'forminator' ),
				'ON' => esc_html__( 'Ontario', 'forminator' ),
				'PE' => esc_html__( 'Prince Edward Island', 'forminator' ),
				'QC' => esc_html__( 'Quebec', 'forminator' ),
				'SK' => esc_html__( 'Saskatchewan', 'forminator' ),
				'YT' => esc_html__( 'Yukon', 'forminator' ),
			),
			'India'            => array(
				'APO'         => esc_html__( 'Army Post Office', 'forminator' ),
				'Delhi (NCT)' => esc_html__( 'Delhi', 'forminator' ),
			),
			'Mexico'           => array(
				'AGS'   => esc_html__( 'Aguascalientes', 'forminator' ),
				'BC'    => esc_html__( 'Baja California', 'forminator' ),
				'BCS'   => esc_html__( 'Baja California Sur', 'forminator' ),
				'CAMP'  => esc_html__( 'Campeche', 'forminator' ),
				'CHIS'  => esc_html__( 'Chiapas', 'forminator' ),
				'CHIH'  => esc_html__( 'Chihuahua', 'forminator' ),
				'CDMX'  => esc_html__( 'Ciudad de México', 'forminator' ),
				'COAH'  => esc_html__( 'Coahuila', 'forminator' ),
				'COL'   => esc_html__( 'Colima', 'forminator' ),
				'DF'    => esc_html__( 'Distrito Federal', 'forminator' ),
				'DGO'   => esc_html__( 'Durango', 'forminator' ),
				'MEX'   => esc_html__( 'Estado de México', 'forminator' ),
				'GTO'   => esc_html__( 'Guanajuato', 'forminator' ),
				'GRO'   => esc_html__( 'Guerrero', 'forminator' ),
				'HGO'   => esc_html__( 'Hidalgo', 'forminator' ),
				'JAL'   => esc_html__( 'Jalisco', 'forminator' ),
				'MICH'  => esc_html__( 'Michoacán', 'forminator' ),
				'MOR'   => esc_html__( 'Morelos', 'forminator' ),
				'NAY'   => esc_html__( 'Nayarit', 'forminator' ),
				'NL'    => esc_html__( 'Nuevo León', 'forminator' ),
				'OAX'   => esc_html__( 'Oaxaca', 'forminator' ),
				'PUE'   => esc_html__( 'Puebla', 'forminator' ),
				'QRO'   => esc_html__( 'Querétaro', 'forminator' ),
				'Q ROO' => esc_html__( 'Quintana Roo', 'forminator' ),
				'SLP'   => esc_html__( 'San Luis Potosí', 'forminator' ),
				'SIN'   => esc_html__( 'Sinaloa', 'forminator' ),
				'SON'   => esc_html__( 'Sonora', 'forminator' ),
				'TAB'   => esc_html__( 'Tabasco', 'forminator' ),
				'TAMPS' => esc_html__( 'Tamaulipas', 'forminator' ),
				'TLAX'  => esc_html__( 'Tlaxcala', 'forminator' ),
				'VER'   => esc_html__( 'Veracruz', 'forminator' ),
				'YUC'   => esc_html__( 'Yucatán', 'forminator' ),
				'ZAC'   => esc_html__( 'Zacatecas', 'forminator' ),
			),
			'Japan'            => array(
				'AICHI-KEN'     => esc_html__( 'Aichi', 'forminator' ),
				'AKITA-KEN'     => esc_html__( 'Akita', 'forminator' ),
				'AOMORI-KEN'    => esc_html__( 'Aomori', 'forminator' ),
				'CHIBA-KEN'     => esc_html__( 'Chiba', 'forminator' ),
				'EHIME-KEN'     => esc_html__( 'Ehime', 'forminator' ),
				'FUKUI-KEN'     => esc_html__( 'Fukui', 'forminator' ),
				'FUKUOKA-KEN'   => esc_html__( 'Fukuoka', 'forminator' ),
				'FUKUSHIMA-KEN' => esc_html__( 'Fukushima', 'forminator' ),
				'GIFU-KEN'      => esc_html__( 'Gifu', 'forminator' ),
				'GUNMA-KEN'     => esc_html__( 'Gunma', 'forminator' ),
				'HIROSHIMA-KEN' => esc_html__( 'Hiroshima', 'forminator' ),
				'HOKKAIDO'      => esc_html__( 'Hokkaido', 'forminator' ),
				'HYOGO-KEN'     => esc_html__( 'Hyogo', 'forminator' ),
				'IBARAKI-KEN'   => esc_html__( 'Ibaraki', 'forminator' ),
				'ISHIKAWA-KEN'  => esc_html__( 'Ishikawa', 'forminator' ),
				'IWATE-KEN'     => esc_html__( 'Iwate', 'forminator' ),
				'KAGAWA-KEN'    => esc_html__( 'Kagawa', 'forminator' ),
				'KAGOSHIMA-KEN' => esc_html__( 'Kagoshima', 'forminator' ),
				'KANAGAWA-KEN'  => esc_html__( 'Kanagawa', 'forminator' ),
				'KOCHI-KEN'     => esc_html__( 'Kochi', 'forminator' ),
				'KUMAMOTO-KEN'  => esc_html__( 'Kumamoto', 'forminator' ),
				'KYOTO-FU'      => esc_html__( 'Kyoto', 'forminator' ),
				'MIE-KEN'       => esc_html__( 'Mie', 'forminator' ),
				'MIYAGI-KEN'    => esc_html__( 'Miyagi', 'forminator' ),
				'MIYAZAKI-KEN'  => esc_html__( 'Miyazaki', 'forminator' ),
				'NAGANO-KEN'    => esc_html__( 'Nagano', 'forminator' ),
				'NAGASAKI-KEN'  => esc_html__( 'Nagasaki', 'forminator' ),
				'NARA-KEN'      => esc_html__( 'Nara', 'forminator' ),
				'NIIGATA-KEN'   => esc_html__( 'Niigata', 'forminator' ),
				'OITA-KEN'      => esc_html__( 'Oita', 'forminator' ),
				'OKAYAMA-KEN'   => esc_html__( 'Okayama', 'forminator' ),
				'OKINAWA-KEN'   => esc_html__( 'Okinawa', 'forminator' ),
				'OSAKA-FU'      => esc_html__( 'Osaka', 'forminator' ),
				'SAGA-KEN'      => esc_html__( 'Saga', 'forminator' ),
				'SAITAMA-KEN'   => esc_html__( 'Saitama', 'forminator' ),
				'SHIGA-KEN'     => esc_html__( 'Shiga', 'forminator' ),
				'SHIMANE-KEN'   => esc_html__( 'Shimane', 'forminator' ),
				'SHIZUOKA-KEN'  => esc_html__( 'Shizuoka', 'forminator' ),
				'TOCHIGI-KEN'   => esc_html__( 'Tochigi', 'forminator' ),
				'TOKUSHIMA-KEN' => esc_html__( 'Tokushima', 'forminator' ),
				'TOKYO-TO'      => esc_html__( 'Tokyo', 'forminator' ),
				'TOTTORI-KEN'   => esc_html__( 'Tottori', 'forminator' ),
				'TOYAMA-KEN'    => esc_html__( 'Toyama', 'forminator' ),
				'WAKAYAMA-KEN'  => esc_html__( 'Wakayama', 'forminator' ),
				'YAMAGATA-KEN'  => esc_html__( 'Yamagata', 'forminator' ),
				'YAMAGUCHI-KEN' => esc_html__( 'Yamaguchi', 'forminator' ),
				'YAMANASHI-KEN' => esc_html__( 'Yamanashi', 'forminator' ),
			),
			'Italy'            => array(
				'AG' => esc_html__( 'Agrigento', 'forminator' ),
				'AL' => esc_html__( 'Alessandria', 'forminator' ),
				'AN' => esc_html__( 'Ancona', 'forminator' ),
				'AO' => esc_html__( 'Aosta', 'forminator' ),
				'AR' => esc_html__( 'Arezzo', 'forminator' ),
				'AP' => esc_html__( 'Ascoli Piceno', 'forminator' ),
				'AT' => esc_html__( 'Asti', 'forminator' ),
				'AV' => esc_html__( 'Avellino', 'forminator' ),
				'BA' => esc_html__( 'Bari', 'forminator' ),
				'BT' => esc_html__( 'Barletta-Andria-Trani', 'forminator' ),
				'BL' => esc_html__( 'Belluno', 'forminator' ),
				'BN' => esc_html__( 'Benevento', 'forminator' ),
				'BG' => esc_html__( 'Bergamo', 'forminator' ),
				'BI' => esc_html__( 'Biella', 'forminator' ),
				'BO' => esc_html__( 'Bologna', 'forminator' ),
				'BZ' => esc_html__( 'Bolzano', 'forminator' ),
				'BS' => esc_html__( 'Brescia', 'forminator' ),
				'BR' => esc_html__( 'Brindisi', 'forminator' ),
				'CA' => esc_html__( 'Cagliari', 'forminator' ),
				'CL' => esc_html__( 'Caltanissetta', 'forminator' ),
				'CB' => esc_html__( 'Campobasso', 'forminator' ),
				'CI' => esc_html__( 'Carbonia-Iglesias', 'forminator' ),
				'CE' => esc_html__( 'Caserta', 'forminator' ),
				'CT' => esc_html__( 'Catania', 'forminator' ),
				'CZ' => esc_html__( 'Catanzaro', 'forminator' ),
				'CH' => esc_html__( 'Chieti', 'forminator' ),
				'CO' => esc_html__( 'Como', 'forminator' ),
				'CS' => esc_html__( 'Cosenza', 'forminator' ),
				'CR' => esc_html__( 'Cremona', 'forminator' ),
				'KR' => esc_html__( 'Crotone', 'forminator' ),
				'CN' => esc_html__( 'Cuneo', 'forminator' ),
				'EN' => esc_html__( 'Enna', 'forminator' ),
				'FM' => esc_html__( 'Fermo', 'forminator' ),
				'FE' => esc_html__( 'Ferrara', 'forminator' ),
				'FI' => esc_html__( 'Firenze', 'forminator' ),
				'FG' => esc_html__( 'Foggia', 'forminator' ),
				'FC' => esc_html__( 'Forlì-Cesena', 'forminator' ),
				'FR' => esc_html__( 'Frosinone', 'forminator' ),
				'GE' => esc_html__( 'Genova', 'forminator' ),
				'GO' => esc_html__( 'Gorizia', 'forminator' ),
				'GR' => esc_html__( 'Grosseto', 'forminator' ),
				'IM' => esc_html__( 'Imperia', 'forminator' ),
				'IS' => esc_html__( 'Isernia', 'forminator' ),
				'AQ' => html_entity_decode( esc_html__( 'L\'Aquila', 'forminator' ), ENT_QUOTES ),
				'SP' => esc_html__( 'La Spezia', 'forminator' ),
				'LT' => esc_html__( 'Latina', 'forminator' ),
				'LE' => esc_html__( 'Lecce', 'forminator' ),
				'LC' => esc_html__( 'Lecco', 'forminator' ),
				'LI' => esc_html__( 'Livorno', 'forminator' ),
				'LO' => esc_html__( 'Lodi', 'forminator' ),
				'LU' => esc_html__( 'Lucca', 'forminator' ),
				'MC' => esc_html__( 'Macerata', 'forminator' ),
				'MN' => esc_html__( 'Mantova', 'forminator' ),
				'MS' => esc_html__( 'Massa-Carrara', 'forminator' ),
				'MT' => esc_html__( 'Matera', 'forminator' ),
				'VS' => esc_html__( 'Medio Campidano', 'forminator' ),
				'ME' => esc_html__( 'Messina', 'forminator' ),
				'MI' => esc_html__( 'Milano', 'forminator' ),
				'MO' => esc_html__( 'Modena', 'forminator' ),
				'MB' => esc_html__( 'Monza e della Brianza', 'forminator' ),
				'NA' => esc_html__( 'Napoli', 'forminator' ),
				'NO' => esc_html__( 'Novara', 'forminator' ),
				'NU' => esc_html__( 'Nuoro', 'forminator' ),
				'OG' => esc_html__( 'Ogliastra', 'forminator' ),
				'OT' => esc_html__( 'Olbia-Tempio', 'forminator' ),
				'OR' => esc_html__( 'Oristano', 'forminator' ),
				'PD' => esc_html__( 'Padova', 'forminator' ),
				'PA' => esc_html__( 'Palermo', 'forminator' ),
				'PR' => esc_html__( 'Parma', 'forminator' ),
				'PV' => esc_html__( 'Pavia', 'forminator' ),
				'PG' => esc_html__( 'Perugia', 'forminator' ),
				'PU' => esc_html__( 'Pesaro e Urbino', 'forminator' ),
				'PE' => esc_html__( 'Pescara', 'forminator' ),
				'PC' => esc_html__( 'Piacenza', 'forminator' ),
				'PI' => esc_html__( 'Pisa', 'forminator' ),
				'PT' => esc_html__( 'Pistoia', 'forminator' ),
				'PN' => esc_html__( 'Pordenone', 'forminator' ),
				'PZ' => esc_html__( 'Potenza', 'forminator' ),
				'PO' => esc_html__( 'Prato', 'forminator' ),
				'RG' => esc_html__( 'Ragusa', 'forminator' ),
				'RA' => esc_html__( 'Ravenna', 'forminator' ),
				'RC' => esc_html__( 'Reggio Calabria', 'forminator' ),
				'RE' => esc_html__( 'Reggio Emilia', 'forminator' ),
				'RI' => esc_html__( 'Rieti', 'forminator' ),
				'RN' => esc_html__( 'Rimini', 'forminator' ),
				'RM' => esc_html__( 'Roma', 'forminator' ),
				'RO' => esc_html__( 'Rovigo', 'forminator' ),
				'SA' => esc_html__( 'Salerno', 'forminator' ),
				'SS' => esc_html__( 'Sassari', 'forminator' ),
				'SV' => esc_html__( 'Savona', 'forminator' ),
				'SI' => esc_html__( 'Siena', 'forminator' ),
				'SR' => esc_html__( 'Siracusa', 'forminator' ),
				'SO' => esc_html__( 'Sondrio', 'forminator' ),
				'TA' => esc_html__( 'Taranto', 'forminator' ),
				'TE' => esc_html__( 'Teramo', 'forminator' ),
				'TR' => esc_html__( 'Terni', 'forminator' ),
				'TO' => esc_html__( 'Torino', 'forminator' ),
				'TP' => esc_html__( 'Trapani', 'forminator' ),
				'TN' => esc_html__( 'Trento', 'forminator' ),
				'TV' => esc_html__( 'Treviso', 'forminator' ),
				'TS' => esc_html__( 'Trieste', 'forminator' ),
				'UD' => esc_html__( 'Udine', 'forminator' ),
				'VA' => esc_html__( 'Varese', 'forminator' ),
				'VE' => esc_html__( 'Venezia', 'forminator' ),
				'VB' => esc_html__( 'Verbano-Cusio-Ossola', 'forminator' ),
				'VC' => esc_html__( 'Vercelli', 'forminator' ),
				'VR' => esc_html__( 'Verona', 'forminator' ),
				'VV' => esc_html__( 'Vibo Valentia', 'forminator' ),
				'VI' => esc_html__( 'Vicenza', 'forminator' ),
				'VT' => esc_html__( 'Viterbo', 'forminator' ),
			),
			'Indonesia'        => array(
				'ID-BA' => esc_html__( 'Bali', 'forminator' ),
				'ID-BB' => esc_html__( 'Bangka Belitung', 'forminator' ),
				'ID-BT' => esc_html__( 'Banten', 'forminator' ),
				'ID-BE' => esc_html__( 'Bengkulu', 'forminator' ),
				'ID-YO' => esc_html__( 'DI Yogyakarta', 'forminator' ),
				'ID-JK' => esc_html__( 'DKI Jakarta', 'forminator' ),
				'ID-GO' => esc_html__( 'Gorontalo', 'forminator' ),
				'ID-JA' => esc_html__( 'Jambi', 'forminator' ),
				'ID-JB' => esc_html__( 'Jawa Barat', 'forminator' ),
				'ID-JT' => esc_html__( 'Jawa Tengah', 'forminator' ),
				'ID-JI' => esc_html__( 'Jawa Timur', 'forminator' ),
				'ID-KB' => esc_html__( 'Kalimantan Barat', 'forminator' ),
				'ID-KS' => esc_html__( 'Kalimantan Selatan', 'forminator' ),
				'ID-KT' => esc_html__( 'Kalimantan Tengah', 'forminator' ),
				'ID-KI' => esc_html__( 'Kalimantan Timur', 'forminator' ),
				'ID-KU' => esc_html__( 'Kalimantan Utara', 'forminator' ),
				'ID-KR' => esc_html__( 'Kepulauan Riau', 'forminator' ),
				'ID-LA' => esc_html__( 'Lampung', 'forminator' ),
				'ID-MA' => esc_html__( 'Maluku', 'forminator' ),
				'ID-MU' => esc_html__( 'Maluku Utara', 'forminator' ),
				'ID-AC' => esc_html__( 'Nanggroe Aceh Darussalam', 'forminator' ),
				'ID-NB' => esc_html__( 'Nusa Tenggara Barat', 'forminator' ),
				'ID-NT' => esc_html__( 'Nusa Tenggara Timur', 'forminator' ),
				'ID-PA' => esc_html__( 'Papua', 'forminator' ),
				'ID-PB' => esc_html__( 'Papua Barat', 'forminator' ),
				'ID-RI' => esc_html__( 'Riau', 'forminator' ),
				'ID-SR' => esc_html__( 'Sulawesi Barat', 'forminator' ),
				'ID-SN' => esc_html__( 'Sulawesi Selatan', 'forminator' ),
				'ID-ST' => esc_html__( 'Sulawesi Tengah', 'forminator' ),
				'ID-SG' => esc_html__( 'Sulawesi Tenggara', 'forminator' ),
				'ID-SA' => esc_html__( 'Sulawesi Utara', 'forminator' ),
				'ID-SB' => esc_html__( 'Sumatera Barat', 'forminator' ),
				'ID-SS' => esc_html__( 'Sumatera Selatan', 'forminator' ),
				'ID-SU' => esc_html__( 'Sumatera Utara', 'forminator' ),
			),
			'Brazil'           => array(
				'AC' => esc_html__( 'Acre', 'forminator' ),
				'AL' => esc_html__( 'Alagoas', 'forminator' ),
				'AP' => esc_html__( 'Amapá', 'forminator' ),
				'AM' => esc_html__( 'Amazonas', 'forminator' ),
				'BA' => esc_html__( 'Bahia', 'forminator' ),
				'CE' => esc_html__( 'Ceará', 'forminator' ),
				'DF' => esc_html__( 'Distrito Federal', 'forminator' ),
				'ES' => esc_html__( 'Espírito Santo', 'forminator' ),
				'GO' => esc_html__( 'Goiás', 'forminator' ),
				'MA' => esc_html__( 'Maranhão', 'forminator' ),
				'MT' => esc_html__( 'Mato Grosso', 'forminator' ),
				'MS' => esc_html__( 'Mato Grosso do Sul', 'forminator' ),
				'MG' => esc_html__( 'Minas Gerais', 'forminator' ),
				'PR' => esc_html__( 'Paraná', 'forminator' ),
				'PB' => esc_html__( 'Paraíba', 'forminator' ),
				'PA' => esc_html__( 'Pará', 'forminator' ),
				'PE' => esc_html__( 'Pernambuco', 'forminator' ),
				'PI' => esc_html__( 'Piauí', 'forminator' ),
				'RN' => esc_html__( 'Rio Grande do Norte', 'forminator' ),
				'RS' => esc_html__( 'Rio Grande do Sul', 'forminator' ),
				'RJ' => esc_html__( 'Rio de Janeiro', 'forminator' ),
				'RO' => esc_html__( 'Rondônia', 'forminator' ),
				'RR' => esc_html__( 'Roraima', 'forminator' ),
				'SC' => esc_html__( 'Santa Catarina', 'forminator' ),
				'SE' => esc_html__( 'Sergipe', 'forminator' ),
				'SP' => esc_html__( 'São Paulo', 'forminator' ),
				'TO' => esc_html__( 'Tocantins', 'forminator' ),
			),
			'Argentina'        => array(
				'CIUDAD AUTÓNOMA DE BUENOS AIRES' => esc_html__( 'Buenos Aires (Ciudad)', 'forminator' ),
				'BUENOS AIRES'                    => esc_html__( 'Buenos Aires (Provincia)', 'forminator' ),
				'CATAMARCA'                       => esc_html__( 'Catamarca', 'forminator' ),
				'CHACO'                           => esc_html__( 'Chaco', 'forminator' ),
				'CHUBUT'                          => esc_html__( 'Chubut', 'forminator' ),
				'CORRIENTES'                      => esc_html__( 'Corrientes', 'forminator' ),
				'CÓRDOBA'                         => esc_html__( 'Córdoba', 'forminator' ),
				'ENTRE RÍOS'                      => esc_html__( 'Entre Ríos', 'forminator' ),
				'FORMOSA'                         => esc_html__( 'Formosa', 'forminator' ),
				'JUJUY'                           => esc_html__( 'Jujuy', 'forminator' ),
				'LA PAMPA'                        => esc_html__( 'La Pampa', 'forminator' ),
				'LA RIOJA'                        => esc_html__( 'La Rioja', 'forminator' ),
				'MENDOZA'                         => esc_html__( 'Mendoza', 'forminator' ),
				'MISIONES'                        => esc_html__( 'Misiones', 'forminator' ),
				'NEUQUÉN'                         => esc_html__( 'Neuquén', 'forminator' ),
				'RÍO NEGRO'                       => esc_html__( 'Río Negro', 'forminator' ),
				'SALTA'                           => esc_html__( 'Salta', 'forminator' ),
				'SAN JUAN'                        => esc_html__( 'San Juan', 'forminator' ),
				'SAN LUIS'                        => esc_html__( 'San Luis', 'forminator' ),
				'SANTA CRUZ'                      => esc_html__( 'Santa Cruz', 'forminator' ),
				'SANTA FE'                        => esc_html__( 'Santa Fe', 'forminator' ),
				'SANTIAGO DEL ESTERO'             => esc_html__( 'Santiago del Estero', 'forminator' ),
				'TIERRA DEL FUEGO'                => esc_html__( 'Tierra del Fuego', 'forminator' ),
				'TUCUMÁN'                         => esc_html__( 'Tucumán', 'forminator' ),
			),
		)
	);

	if ( ! empty( $state ) && ! empty( $state_code ) ) {
		foreach ( $state_code as $key => $value ) {
			if ( ! empty( $value ) && is_array( $value ) ) {
				$code = array_search( $state, $value, true );
				if ( ! empty( $code ) ) {
					$state = $code;
				}
			}
		}
	}
	return $state;
}

/**
 * Render Rating field
 *
 * @param mixed $rating_value Rating value.
 * @param array $rating_items Rating items.
 *
 * @return string
 */
function forminator_render_rating_field( $rating_value, $rating_items ) {
	$rating_value = ! empty( $rating_value ) ? $rating_value : 0;
	$max_rating   = ! empty( $rating_items['max_rating'] ) ? $rating_items['max_rating'] : 0;
	$output       = '<div class="forminator-rating-field forminator-rating-size--' . esc_attr( $rating_items['size'] ) . '">';
	$output      .= '<div class="forminator-rating-items">';
	for ( $rating = 1; $rating <= $max_rating; $rating++ ) {
		$class_name = 'forminator-icon-' . $rating_items['icon'] . ( $rating <= $rating_value ? ' forminator-rating-active' : '' );
		$output    .= '<span class="' . esc_attr( $class_name ) . '" aria-hidden="true"></span>';
	}
	$output .= '</div>';
	if ( $rating_items['suffix'] ) {
		$output .= '<span class="forminator-rating-suffix">' . esc_html( '(' . $rating_value . '/' . $max_rating . ')' ) . '</span>';
	}
	$output .= '</div>';

	return $output;
}

/**
 * Check if a file can be displayed as an image
 *
 * @param string $file_url File URL.
 * @return bool
 */
function forminator_can_display_as_image( $file_url ) {
	$image_extensions = array( 'jpg', 'jpeg', 'jpe', 'gif', 'png', 'bmp', 'tiff', 'tif', 'ico', 'webp', 'heic' );
	$file_extension   = strtolower( pathinfo( $file_url, PATHINFO_EXTENSION ) );

	return in_array( $file_extension, $image_extensions, true );
}

/**
 * Get image markup for email notifications
 *
 * @param string $image_url Image URL.
 * @param string $alt_text Alt text for the image.
 * @param string $label Label to display below image.
 * @param string $context Context where this is used (radio, checkbox, signature, upload).
 * @return string
 */
function forminator_get_email_image_markup( $image_url, $alt_text, $label = '', $context = '' ) {
	$default_markup = '<img src="' . esc_url( $image_url ) . '" alt="' . esc_attr( $alt_text ) . '" style="max-width: 300px; height: auto; margin: 5px 0;">';

	if ( ! empty( $label ) ) {
		$default_markup .= '<br/>' . esc_html( $label );
	}

	/**
	 * Filter the image markup for email notifications
	 *
	 * @since 1.0
	 *
	 * @param string $markup The HTML markup for the image.
	 * @param string $image_url The URL of the image.
	 * @param string $alt_text The alt text for the image.
	 * @param string $label The label to display below the image.
	 * @param string $context The context where this is used (radio, checkbox, signature, upload).
	 */
	return apply_filters( 'forminator_email_image_markup', $default_markup, $image_url, $alt_text, $label, $context );
}
