<?php
/**
 * Form helper functions.
 *
 * @package Forminator
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Return local timestamp
 *
 * @since 1.0
 *
 * @param string $timestamp Timestamp.
 *
 * @return mixed
 */
function forminator_local_timestamp( $timestamp = null ) {
	// If no timestamp, get it current.
	if ( is_null( $timestamp ) ) {
		$timestamp = time();
	}

	return $timestamp + ( get_option( 'gmt_offset' ) * 3600 );
}

/**
 * Return user IP
 *
 * @since 1.0
 * @return string
 */
function forminator_user_ip() {
	return Forminator_Geo::get_user_ip();
}

/**
 * Return user property
 *
 * @param mixed $property Property.
 *
 * @since 1.0
 * @return string
 */
function forminator_get_user_data( $property ) {
	global $current_user;

	return $current_user->get( $property );
}

/**
 * Return user property
 *
 * @since 1.0
 *
 * @param string $property Property.
 * @param int    $post_id Post Id.
 * @param string $default_value Default value.
 *
 * @return string
 */
function forminator_get_post_data( $property, $post_id = null, $default_value = '' ) {
	global $post;
	$current_post_object = $post;
	if ( $post_id ) {
		$post_object = get_post( $post_id );
		// make sure its wp_post.
		if ( $post_object instanceof WP_Post ) {
			$current_post_object = $post_object;
		}
	}

	if ( ! $current_post_object ) {
		// fallback on wp_ajax, `global $post` not available.
		$wp_referer = wp_get_referer();
		if ( $wp_referer ) {
			$post_id = url_to_postid( $wp_referer );
			if ( $post_id ) {
				$post_object = get_post( $post_id );
				// make sure its wp_post.
				if ( $post_object instanceof WP_Post ) {
					$current_post_object = $post_object;
				}
			}
		}
	}

	$post_data = forminator_object_to_array( $current_post_object );
	if ( isset( $post_data[ $property ] ) ) {
		return $post_data[ $property ];
	} else {
		return $default_value;
	}
}

/**
 * Return total custom form records
 *
 * @param string $status Status.
 * @since 1.0
 *
 * @return int
 */
function forminator_cforms_total( $status = '' ) {
	return Forminator_Form_Model::model()->count_all( $status );
}

/**
 * Return custom forms
 *
 * @since 1.0
 * @return Forminator_Base_Form_Model[]
 */
function forminator_custom_forms() {
	return Forminator_Form_Model::model()->get_all_paged();
}

/**
 * Return conversion rate from module
 *
 * @param array $module Module.
 *
 * @since 1.0
 * @return mixed
 */
function forminator_get_rate( $module ) {
	if ( 0 === $module['views'] ) {
		$rate = 0;
	} else {
		$rate = round( ( $module['entries'] * 100 ) / $module['views'], 1 );
	}

	return $rate;
}

/**
 * Return total polls form records
 *
 * @param string $status Status.
 * @since 1.0
 *
 * @return int
 */
function forminator_polls_total( $status = '' ) {
	return Forminator_Poll_Model::model()->count_all( $status );
}

/**
 * Return polls
 *
 * @since 1.0
 * @return Forminator_Base_Form_Model[]
 */
function forminator_polls_forms() {
	return Forminator_Poll_Model::model()->get_all_paged();
}

/**
 * Return total quizzes records
 *
 * @param string $status Status.
 * @since 1.0
 *
 * @return int
 */
function forminator_quizzes_total( $status = '' ) {
	return Forminator_Quiz_Model::model()->count_all( $status );
}

/**
 * Return quizzes
 *
 * @since 1.0
 * @return Forminator_Base_Form_Model[]
 */
function forminator_quizzes_forms() {
	return Forminator_Quiz_Model::model()->get_all_paged();
}

/**
 * Check if quiz has leads
 *
 * @param mixed $model Model.
 *
 * @since 1.14
 *
 * @return bool
 */
function forminator_quiz_has_leads( $model ) {
	if ( isset( $model->settings['hasLeads'] ) && in_array( $model->settings['hasLeads'], array( true, 'true' ), true ) ) {
		return true;
	}

	return false;
}

/**
 * Return quiz edit url
 *
 * @since 1.0
 *
 * @param array $module Module.
 * @param int   $id Module id.
 *
 * @return mixed
 */
function forminator_quiz_get_edit_url( $module, $id ) {
	if ( isset( $module['type'] ) && 'nowrong' === $module['type'] ) {
		return admin_url( 'admin.php?page=forminator-nowrong-wizard&id=' . $id );
	} else {
		return admin_url( 'admin.php?page=forminator-knowledge-wizard&id=' . $id );
	}
}

/**
 * Return total forms
 *
 * @param string $status Status.
 *
 * @since 1.0
 *
 * @return int
 */
function forminator_total_forms( $status = '' ) {
	$modules = array(
		forminator_cforms_total( $status ),
		forminator_polls_total( $status ),
		forminator_quizzes_total( $status ),
	);

	return array_sum( $modules );
}

/**
 * Return form nice name by id
 *
 * @since 1.0
 *
 * @param int $id Id.
 *
 * @return mixed
 */
function forminator_get_form_name( $id ) {
	$model = Forminator_Base_Form_Model::get_model( $id );

	// Fallback just in case.
	if ( ! empty( $model->settings['formName'] ) ) {
		return $model->settings['formName'];
	} else {
		return $model->raw->post_title;
	}
}

/**
 * Central per page for form view
 *
 * @param string $type View type.
 *
 * @since 1.0
 * @return int
 */
function forminator_form_view_per_page( $type = 'listings' ) {

	if ( 'entries' === $type ) {
		$per_page = get_option( 'forminator_pagination_entries', 10 );
	} else {
		$per_page = get_option( 'forminator_pagination_listings', 10 );
	}

	// force at least 1 data per page.
	if ( $per_page < 1 ) {
		$per_page = 1;
	}
	return apply_filters( 'forminator_form_per_page', $per_page, $type );
}

/**
 * Return admin page url by slug
 *
 * @param string $slug Slug.
 *
 * @since 1.0
 * @return mixed
 */
function forminator_get_admin_link( $slug ) {
	return menu_page_url( $slug, false );
}

/**
 * Return JS model to form model
 *
 * @since 1.0
 *
 * @param array $data Data.
 *
 * @return array
 */
function forminator_data_to_model_form( $data ) {
	$model = array();

	if ( empty( $data ) ) {
		return $model;
	}

	// Set wrappers.
	$model['wrappers'] = $data['wrappers'];

	// Remove wrappers to get all form settings.
	unset( $data['wrappers'] );

	// Set settings.
	$model['settings'] = $data['settings'];

	return $model;
}

/**
 * Return JS model to form model
 *
 * @since 1.0
 *
 * @param array $data Data.
 *
 * @return array
 */
function forminator_data_to_model_poll( $data ) {
	$model = array();

	if ( empty( $data ) ) {
		return $model;
	}

	if ( isset( $data['answers'] ) ) {
		// Set wrappers.
		$model['answers'] = $data['answers'];

		// Remove wrappers to get all form settings.
		unset( $data['answers'] );
	}

	// Set settings.
	$model['settings'] = $data['settings'];

	return $model;
}


/**
 * Return JS model to form model
 *
 * @since 1.0
 *
 * @param array $data Data.
 *
 * @return array
 */
function forminator_data_to_model_quiz( $data ) {
	$model = array();

	if ( empty( $data ) ) {
		return $model;
	}

	if ( isset( $data['type'] ) ) {
		$model['type'] = $data['type'];
		unset( $data['type'] );
	}

	// Set results.
	if ( isset( $data['results'] ) ) {
		$model['results'] = $data['results'];
		unset( $data['results'] );
	}

	// Set results.
	if ( isset( $data['questions'] ) ) {
		$model['questions'] = $data['questions'];
		unset( $data['questions'] );
	}

	// Set settings.
	$model['settings'] = $data;
	if ( isset( $data['settings'] ) ) {
		$model['settings'] = $data['settings'];
	}

	return $model;
}

/**
 * Prepares the custom css string
 *
 * @since 1.0
 *
 * @param string     $css_string CSS string.
 * @param string     $prefix prefix.
 * @param bool|false $as_array Prepare css as array.
 * @param bool|true  $separate_prefix Separate prefix.
 * @param string     $wildcard string.
 *
 * @return array|string
 */
function forminator_prepare_css( $css_string, $prefix, $as_array = false, $separate_prefix = true, $wildcard = '' ) {
	$css_array = array(); // master array to hold all values.
	$elements  = explode( '}', $css_string );
	// Output is the final processed CSS string.
	$output          = '';
	$prepared        = '';
	$have_media      = false;
	$media_names     = array();
	$media_names_key = 0;
	$index           = 0;
	foreach ( $elements as $element ) {
		// We need to null prepared else styles are multiplied.
		$prepared = '';

		$check_element = trim( $element );
		if ( empty( $check_element ) ) {
			// Still increment $index even if empty.
			++$index;
			continue;
		}

		// get the name of the CSS element.
		$a_name = explode( '{', $element );
		$name   = $a_name[0];

		// check if @media is  present.
		$media_name = '';
		if ( strpos( $name, '@media' ) !== false && isset( $a_name[1] ) ) {
			$have_media                      = true;
			$media_name                      = $name;
			$media_names[ $media_names_key ] = array(
				'name' => $media_name,
			);
			$name                            = $a_name[1];
			++$media_names_key;
		}

		if ( $have_media ) {
			$prepared = '';
		}

		// get all the key:value pair styles.
		$a_styles = explode( ';', $element );
		// remove element name from first property element.
		$remove_element_name = ( ! empty( $media_name ) ) ? $media_name . '{' . $name : $name;
		$a_styles[0]         = str_replace( $remove_element_name . '{', '', $a_styles[0] );
		$names               = explode( ',', $name );
		foreach ( $names as $name ) {
			$name = trim( $name );
			if ( 0 === strpos( $name, ':' ) ) {
				$space_needed = false;
			} elseif ( $separate_prefix && empty( $wildcard ) ) {
				$space_needed = true;
			} elseif ( $separate_prefix && ! empty( $wildcard ) ) {
				// wildcard is the sibling class of target selector e.g. "wph-modal".
				if ( strpos( $name, $wildcard ) && ! strpos( $name, $wildcard . '-' ) ) {
					$space_needed = false;
				} else {
					$space_needed = true;
				}
			} else {
				$space_needed = false;
			}
			$maybe_put_space = ( $space_needed ) ? ' ' : '';
			$prepared       .= ( trim( $prefix ) . $maybe_put_space . trim( $name ) . ',' );
		}
		$prepared  = trim( $prepared, ',' );
		$prepared .= '{';
		// loop through each style and split apart the key from the value.
		$count = count( $a_styles );
		for ( $a = 0; $a < $count; $a++ ) {
			if ( '' !== trim( $a_styles[ $a ] ) ) {
				$a_key_value = array_map( 'trim', explode( ':', $a_styles[ $a ] ) );
				// build the master css array.
				if ( count( $a_key_value ) >= 2 ) {
					$a_key_value_to_join = array_slice( $a_key_value, 1 );

					$a_key_value[1] = implode( ':', $a_key_value_to_join );

					$css_array[ $name ][ $a_key_value[0] ] = $a_key_value[1];
					$prepared                             .= ( $a_key_value[0] . ': ' . $a_key_value[1] );
					if ( strpos( $a_key_value[1], '!important' ) === false ) {
						$prepared .= ' !important';
					}
					$prepared .= ';';
				}
			}
		}
		$prepared .= '}';

		// if have @media earlier, append these styles.
		$prev_media_names_key = $media_names_key - 1;
		if ( isset( $media_names[ $prev_media_names_key ] ) ) {
			if ( isset( $media_names[ $prev_media_names_key ]['styles'] ) ) {
				// See if there were two closing '}' or just one.
				// (each element is exploded/split on '}' symbol, so having two empty strings afterward in the elements array means two '}'s.
				$next_element = isset( $elements[ $index + 2 ] ) ? trim( $elements[ $index + 2 ] ) : false;
				// If inside @media block.
				if ( ! empty( $next_element ) ) {
					$media_names[ $prev_media_names_key ]['styles'] .= $prepared;
				} else {
					// If outside of @media block, add to output.
					$output .= $prepared;
				}
			} else {
				$media_names[ $prev_media_names_key ]['styles'] = $prepared;
			}
		} else {
			// If no @media, add styles to $output outside @media.
			$output .= $prepared;
		}
		// Increase index.
		++$index;
	}

	// if have @media, populate styles using $media_names.
	if ( $have_media ) {
		// reset first $prepared styles.
		$prepared = '';
		foreach ( $media_names as $media ) {
			$prepared .= $media['name'] . '{ ' . $media['styles'] . ' }';
		}
		// Add @media styles to output.
		$output .= $prepared;
	}

	return $as_array ? $css_array : $output;
}

/**
 * Handle all pagination
 *
 * @since 1.0
 *
 * @param int       $total - the total records.
 * @param string    $type - The type of page (listings or entries).
 * @param null|bool $is_ajax  - If using ajax. Added in 1.52.
 */
function forminator_list_pagination( $total, $type = 'listings', $is_ajax = false ) {
	$pagenum     = (int) Forminator_Core::sanitize_text_field( 'paged' );
	$page_number = max( 1, $pagenum );
	$per_page    = forminator_form_view_per_page( $type );
	if ( 'entries' === $type ) {
		$per_page = forminator_form_view_per_page( 'entries' );
	}
	if ( $total > $per_page ) {
		$removable_query_args = wp_removable_query_args();

		$http_hosts    = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
		$request_uri   = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		$current_url   = set_url_scheme( 'http://' . $http_hosts . $request_uri );
		$current_url   = remove_query_arg( $removable_query_args, $current_url );
		$current       = $page_number + 1;
		$total_pages   = ceil( $total / $per_page );
		$total_pages   = absint( $total_pages );
		$disable_first = false;
		$disable_last  = false;
		$disable_prev  = false;
		$disable_next  = false;
		$mid_size      = 2;
		$end_size      = 1;
		$show_skip     = false;
		$previous_page = min( $total_pages, $page_number - 1 );
		$next_page     = min( $total_pages, $page_number + 1 );
		$last_page     = max( $total_pages, $page_number - 1 );

		if ( $total_pages > 10 ) {
			$show_skip = true;
		}

		if ( $total_pages >= 4 ) {
			$disable_prev = true;
			$disable_next = true;
		}

		if ( 1 === $page_number ) {
			$disable_first = true;
		}

		if ( $page_number === $total_pages ) {
			$disable_last = true;

		}

		?>
		<ul class="sui-pagination  
		<?php
		if ( $is_ajax ) {
			echo 'forminator-ajax-pagination'; }
		?>
		">

			<?php if ( ! $disable_first ) : ?>
				<?php
				$prev_url  = $is_ajax ? '#' : esc_url( add_query_arg( 'paged', $previous_page, $current_url ) );
				$first_url = $is_ajax ? '#' : esc_url( add_query_arg( 'paged', min( 1, $total_pages ), $current_url ) );
				?>
				<?php if ( $show_skip ) : ?>
					<li class="wpmudev-pagination--prev">
						<a href="<?php echo esc_url( $first_url ); ?>"  data-page="1"><i class="sui-icon-arrow-skip-start" aria-hidden="true"></i></a>
					</li>
				<?php endif; ?>
				<?php if ( $disable_prev ) : ?>
					<li class="wpmudev-pagination--prev">
						<a href="<?php echo esc_url( $prev_url ); ?>" data-page="<?php echo esc_attr( $previous_page ); ?>"><i class="sui-icon-chevron-left" aria-hidden="true"></i></a>
					</li>
				<?php endif; ?>
			<?php endif; ?>
			<?php
			$dots = false;
			for ( $i = 1; $i <= $total_pages; $i++ ) :
				$class = ( $page_number === $i ) ? 'sui-active' : '';
				$url   = $is_ajax ? '#' : esc_url( add_query_arg( 'paged', ( $i ), $current_url ) );
				if ( ( $i <= $end_size || ( $current && $i >= $current - $mid_size && $i <= $current + $mid_size ) || $i > $total_pages - $end_size ) ) {
					?>
					<li class="<?php echo esc_attr( $class ); ?>"><a href="<?php echo esc_url( $url ); ?>" class="<?php echo esc_attr( $class ); ?>" data-page="<?php echo esc_attr( $i ); ?>"><?php echo esc_html( $i ); ?></a></li>
					<?php
					$dots = true;
				} elseif ( $dots ) {
					?>
					<li class="sui-pagination-dots"><span><?php esc_html_e( '&hellip;', 'forminator' ); ?></span></li>
					<?php
					$dots = false;
				}

				?>

			<?php endfor; ?>

			<?php if ( ! $disable_last ) : ?>
				<?php
				$next_url = $is_ajax ? '#' : esc_url( add_query_arg( 'paged', $next_page, $current_url ) );
				$last_url = $is_ajax ? '#' : esc_url( add_query_arg( 'paged', $last_page, $current_url ) );
				?>
				<?php if ( $disable_next ) : ?>
					<li class="wpmudev-pagination--next">
						<a href="<?php echo esc_url( $next_url ); ?>" data-page="<?php echo esc_attr( $next_page ); ?>"><i class="sui-icon-chevron-right" aria-hidden="true"></i></a>
					</li>
				<?php endif; ?>
				<?php if ( $show_skip ) : ?>
					<li class="wpmudev-pagination--next">
						<a href="<?php echo esc_url( $last_url ); ?>"  data-page="<?php echo esc_attr( $last_page ); ?>"><i class="sui-icon-arrow-skip-end" aria-hidden="true"></i></a>
					</li>
				<?php endif; ?>
			<?php endif; ?>
		</ul>
		<?php
	}
}

/**
 * Get Form Model from id
 *
 * @since 1.0.5
 *
 * @param int $id Form Id.
 *
 * @return bool|Forminator_Base_Form_Model|null
 */
function forminator_get_model_from_id( $id ) {
	$post = get_post( $id );
	if ( ! $post instanceof WP_Post ) {
		return null;
	}

	$custom_form_model = Forminator_Form_Model::model();
	$quiz_form_model   = Forminator_Quiz_Model::model();
	$poll_form_model   = Forminator_Poll_Model::model();

	switch ( $post->post_type ) {
		case $custom_form_model->get_post_type():
			$form_model = $custom_form_model->load( $id );
			break;
		case $quiz_form_model->get_post_type():
			$form_model = $quiz_form_model->load( $id );
			break;
		case $poll_form_model->get_post_type():
			$form_model = $poll_form_model->load( $id );
			break;
		default:
			$form_model = null;
			break;
	}

	return $form_model;
}

/**
 * Get Latest entry based on $entry_type
 * [custom-forms, quizzes, poll]
 * will return null if there is no entry
 *
 * @param string $entry_type Entry Type.
 *
 * @return Forminator_Form_Entry_Model|null
 */
function forminator_get_latest_entry( $entry_type ) {
	$latest_entry = Forminator_Form_Entry_Model::get_latest_entry( $entry_type );

	return $latest_entry;
}

/**
 * Get Time of latest entry created based on $entry_type
 * [custom-forms, quizzes, poll]
 *
 * @param string $entry_type Entry Type.
 *
 * @return string
 */
function forminator_get_latest_entry_time( $entry_type ) {
	$latest_entry = forminator_get_latest_entry( $entry_type );
	if ( $latest_entry instanceof Forminator_Form_Entry_Model ) {
		$last_entry_time = mysql2date( 'U', $latest_entry->date_created_sql );
		$time_diff       = human_time_diff( current_time( 'timestamp' ), $last_entry_time ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested -- We are using the current timestamp based on the site's timezone.
		$last_entry_time = sprintf( /* translators: %s: Time difference */ esc_html__( '%s ago', 'forminator' ), $time_diff );

		return $last_entry_time;
	} else {
		return esc_html__( 'Never', 'forminator' );
	}
}

/**
 * Get Latest entry based on $form_id
 * will return null if there is no entry
 *
 * @param int    $form_id Form id.
 * @param string $order Order by.
 *
 * @return Forminator_Form_Entry_Model|null
 */
function forminator_get_latest_entry_by_form_id( $form_id, $order = 'DESC' ) {
	$latest_entry = Forminator_Form_Entry_Model::get_latest_entry_by_form_id( $form_id, $order );

	return $latest_entry;
}

/**
 * Get Time of latest entry created based on $form_id
 *
 * @param int    $form_id Form Id.
 * @param string $order Order by.
 *
 * @return string
 */
function forminator_get_latest_entry_time_by_form_id( $form_id, $order = 'DESC' ) {
	$latest_entry = forminator_get_latest_entry_by_form_id( $form_id, $order );
	if ( $latest_entry instanceof Forminator_Form_Entry_Model ) {
		return $latest_entry->time_created;
	} else {
		return esc_html__( 'Never', 'forminator' );
	}
}

/**
 * Get Time of view created based on $form_id
 *
 * @param int    $form_id Form Id.
 * @param string $order Order by.
 *
 * @return string
 */
function forminator_get_latest_view_time_by_form_id( $form_id, $order = 'DESC' ) {
	$latest_entry = forminator_get_view_entry_by_form_id( $form_id, $order );
	if ( $latest_entry instanceof Forminator_Form_Entry_Model ) {
		return $latest_entry->time_created;
	} else {
		return esc_html__( 'Never', 'forminator' );
	}
}

/**
 * Update Form Submission retention
 *
 * @since 1.0.6
 *
 * @param int  $form_id Form Id.
 * @param int  $retention_number Retention number.
 * @param int  $retention_unit Retention unit.
 * @param bool $draft Draft.
 */
function forminator_update_form_submissions_retention( $form_id, $retention_number, $retention_unit, $draft = false ) {
	$opt = get_option( 'forminator_form_privacy_settings', array() );
	if ( is_null( $retention_number ) && is_null( $retention_unit ) ) {
		// deletion mode.
		unset( $opt[ $form_id ] );
	} elseif ( $draft ) {
		$opt[ $form_id . '-draft' ] = array(
			'draft_retention_number' => (int) $retention_number,
			'draft_retention_unit'   => $retention_unit,
		);
	} else {
		$opt[ $form_id ] = array(
			'submissions_retention_number' => (int) $retention_number,
			'submissions_retention_unit'   => $retention_unit,
		);
	}

	update_option( 'forminator_form_privacy_settings', $opt );
}

/**
 * Clone form submission retention
 *
 * @since 1.0.6
 *
 * @param int $old_id Old Id.
 * @param int $new_id New Id.
 */
function forminator_clone_form_submissions_retention( $old_id, $new_id ) {
	$opt = get_option( 'forminator_form_privacy_settings', array() );
	if ( isset( $opt[ $old_id ] ) ) {
		$opt[ $new_id ] = $opt[ $old_id ];
	}
	update_option( 'forminator_form_privacy_settings', $opt );
}

/**
 * Update poll submission retention
 *
 * @since 1.0.6
 *
 * @param int $poll_id Poll Id.
 * @param int $retention_number Retention number.
 * @param int $retention_unit Retention unit.
 */
function forminator_update_poll_submissions_retention( $poll_id, $retention_number, $retention_unit ) {
	$opt = get_option( 'forminator_poll_privacy_settings', array() );
	if ( is_null( $retention_number ) && is_null( $retention_unit ) ) {
		// deletion mode.
		unset( $opt[ $poll_id ] );
	} else {
		$opt[ $poll_id ] = array(
			'ip_address_retention_number' => (int) $retention_number,
			'ip_address_retention_unit'   => $retention_unit,
		);
	}

	update_option( 'forminator_poll_privacy_settings', $opt );
}

/**
 * Clone poll ip retention
 *
 * @since 1.0.6
 *
 * @param int $old_id Old Id.
 * @param int $new_id New id.
 */
function forminator_clone_poll_submissions_retention( $old_id, $new_id ) {
	$opt = get_option( 'forminator_poll_privacy_settings', array() );
	if ( isset( $opt[ $old_id ] ) ) {
		$opt[ $new_id ] = $opt[ $old_id ];
	}
	update_option( 'forminator_poll_privacy_settings', $opt );
}

/**
 * Return form nice name by model
 *
 * @since 1.6.1
 *
 * @param Forminator_Base_Form_Model $model Base form model.
 *
 * @return string
 */
function forminator_get_name_from_model( $model ) {
	// Fallback just in case.
	if ( ! empty( $model->settings['formName'] ) ) {
		return $model->settings['formName'];
	} else {
		return $model->raw->post_title;
	}
}

/**
 * Return social share message
 *
 * @since 1.10
 *
 * @param mixed  $model Model.
 * @param string $title Title.
 * @param string $result Result.
 * @return mixed|string
 */
function forminator_get_social_message( $model, $title, $result ) {
	$settings = $model->settings;
	$message  = esc_html__( 'I got {quiz_result} on {quiz_name} quiz!', 'forminator' );
	if ( isset( $settings['social-share-message'] ) && ! empty( $settings['social-share-message'] ) ) {
		$message = $settings['social-share-message'];
	}

	$model_id = ! empty( $model->id ) ? $model->id : false;

	$message = forminator_replace_variables( $message, $model_id );
	$message = str_ireplace( '{quiz_name}', $title, $message );
	$message = str_ireplace( '{quiz_result}', $result, $message );

	return $message;
}

/**
 * Get Chart data of Poll
 *
 * @param Forminator_Poll_Model $poll Poll model.
 *
 * @return array
 */
function forminator_get_chart_data( Forminator_Poll_Model $poll ) {

	$accessibility_enabled = get_option( 'forminator_enable_accessibility', false );
	$chart_colors          = forminator_get_poll_chart_colors( $poll->id, $accessibility_enabled );
	$default_chart_colors  = $chart_colors;
	$chart_datas           = array();

	$form_settings        = $poll->settings;
	$number_votes_enabled = false; // TO-DO: Remove later. This will be handled through ChartJS function.

	$fields_array = $poll->get_fields_as_array();
	$map_entries  = Forminator_Form_Entry_Model::map_polls_entries( $poll->id, $fields_array );
	$fields       = $poll->get_fields();

	if ( ! is_null( $fields ) ) {

		foreach ( $fields as $field ) {

			// Label.
			$label = sanitize_text_field( $field->title );

			// Votes.
			$slug    = isset( $field->slug ) ? $field->slug : sanitize_title( $label );
			$entries = 0;

			if ( in_array( $slug, array_keys( $map_entries ), true ) ) {
				$entries = $map_entries[ $slug ];
			}

			$color = $field->color;

			if ( empty( $color ) || empty( $form_settings['poll-colors'] ) ) {
				// Colors.
				if ( empty( $chart_colors ) ) {
					$chart_colors = $default_chart_colors;
				}

				$color = array_shift( $chart_colors );
			}

			$chart_datas[] = array(
				(string) $label,
				(int) $entries,
				(string) $color,
			);
		}
	}

	/**
	 * Filters chart datas. Can be helpful in reordering data in charts.
	 *
	 * @param array $chart_datas Array with labels, number of votes and colors.
	 * @since 1.13
	 */
	return apply_filters( 'forminator_polls_chart_datas', $chart_datas );
}

/**
 * Get a specific property of an array.
 *
 * @since  1.11
 *
 * @param array  $array_values   Array from which the property's value should be retrieved.
 * @param string $prop    Name of the property to be retrieved.
 * @param string $default_value Optional. Value that should be returned if the property is not set or empty. Defaults to null.
 *
 * @return null|string|mixed The value
 */
function forminator_get_property( $array_values, $prop, $default_value = null ) {

	if ( ! is_array( $array_values ) && ! ( is_object( $array_values ) && $array_values instanceof ArrayAccess ) ) {
		return $default_value;
	}

	$value = isset( $array_values[ $prop ] ) ? $array_values[ $prop ] : '';

	return empty( $value ) && null !== $default_value ? $default_value : $value;
}

/**
 * Flag whether this is the main site or not
 *
 * @since 1.11
 * @return bool
 */
function forminator_is_main_site() {

	return ( is_multisite() && is_main_site() );
}

/**
 * Flag whether this is subdomain network or not
 *
 * @since 1.11
 * @return bool
 */
function forminator_is_subdomain_network() {

	return ( is_multisite() && is_subdomain_install() );
}

/**
 * Return quiz name by id
 *
 * @since 1.14
 *
 * @param int $id Id.
 *
 * @return string
 */
function forminator_get_quiz_name( $id ) {
	$model = Forminator_Base_Form_Model::get_model( $id );

	return ! empty( $model->settings['quiz_name'] ) ? $model->settings['quiz_name'] : '';
}

/**
 * Defender compatibility
 *
 * @return array
 */
function forminator_defender_compatibility() {
	$defender_data = array(
		'is_activated'    => false,
		'is_free'         => false,
		'img_dir_url'     => '',
		'two_fa_settings' => '',
		'lost_phone_url'  => '',
		'structure'       => '',
	);

	// Todo: Def version >= 2.4.
	if ( defined( 'DEFENDER_VERSION' ) && function_exists( 'defender_backward_compatibility' ) ) {
		/**
		 * Defender_backward_compatibility() return array of values:
		 * bool 'is_free'
		 * string 'plugin_url'
		 * object 'two_fa_settings'
		 * string 'two_fa_component'
		 * string 'lost_url'
		 */
		$defender_compatibility = defender_backward_compatibility();

		$defender_data = array(
			'is_activated'     => true,
			'is_free'          => $defender_compatibility['is_free'],
			'img_dir_url'      => $defender_compatibility['plugin_url'] . '/assets/img/',
			'two_fa_settings'  => $defender_compatibility['two_fa_settings'],
			'two_fa_component' => $defender_compatibility['two_fa_component'],
			'structure'        => 'new',
			'lost_url'         => $defender_compatibility['lost_url'],
		);
	} elseif ( function_exists( 'wp_defender' ) ) {
		$defender_data['is_activated'] = true;
		$defender_data['is_free']      = wp_defender()->isFree;
		$defender_data['img_dir_url']  = wp_defender()->getPluginUrl() . 'app/module/advanced-tools/img/';
		$defender_data['structure']    = 'old';

		// Todo: Def version < 2.2.9.
		if ( class_exists( '\WP_Defender\Module\Advanced_Tools\Model\Auth_Settings' ) ) {
			$defender_data['two_fa_settings']  = \WP_Defender\Module\Advanced_Tools\Model\Auth_Settings::instance();
			$defender_data['two_fa_component'] = '\WP_Defender\Module\Advanced_Tools\Component\Auth_API';
		} elseif ( class_exists( '\WP_Defender\Module\Two_Factor\Model\Auth_Settings' ) ) {
			// Todo: Def version >= 2.2.9 and < 2.4.
			$defender_data['two_fa_settings']  = \WP_Defender\Module\Two_Factor\Model\Auth_Settings::instance();
			$defender_data['two_fa_component'] = '\WP_Defender\Module\Two_Factor\Component\Auth_API';
		}
	}

	return $defender_data;
}

/**
 * Get schedule time
 *
 * @param array $schedule Schedule.
 *
 * @return string
 */
function forminator_get_schedule_time( $schedule ) {
	$frequency = ! empty( $schedule['frequency'] ) ? $schedule['frequency'] : 'daily';
	switch ( $frequency ) {
		case 'daily':
			$time = 'Daily, ' . $schedule['time'];
			break;
		case 'weekly':
			$time = 'Weekly on ' . ucfirst( $schedule['weekDay'] ) . ', ' . $schedule['weekTime'];
			break;
		case 'monthly':
			$time = 'Monthly/' . $schedule['monthDay'] . ', ' . $schedule['monthTime'];
			break;
		default:
			$time = '';
			break;
	}

	return $time;
}

/**
 * Replace line breaks
 *
 * @since 1.50
 *
 * @param string $value Value.
 *
 * @return string
 */
function forminator_replace_linebreaks( $value ) {
	return wpautop( $value );
}