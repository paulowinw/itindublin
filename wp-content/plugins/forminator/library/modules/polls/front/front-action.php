<?php
/**
 * The Forminator_Poll_Front_Action class.
 *
 * @package Forminator
 */

// Block direct access.
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Front action for polls
 *
 * @since 1.0
 */
class Forminator_Poll_Front_Action extends Forminator_Front_Action {

	/**
	 * Module slug
	 *
	 * @var string
	 */
	protected static $module_slug = 'poll';

	/**
	 * Entry type
	 *
	 * @var string
	 */
	public static $entry_type = 'poll';

	/**
	 * Handle form action
	 *
	 * @since 1.0
	 *
	 * @param bool $preview Preview.
	 *
	 * @return bool|array
	 */
	protected function handle_form( $preview = false ) {
		if ( ! self::$module_object ) {
			return false;
		}

		try {
			self::can_submit( true );

			$entry = self::get_entry();

			// If preview, skip integrations.
			if ( ! $preview ) {
				self::attach_addons_on_poll_submit( self::$module_object );

				if ( ! self::prevent_store() ) {
					$entry->save();
				}
			}

			self::save_entry_fields( $entry );
			self::attach_addons_after_entry_saved( $entry );
			self::send_email( $entry );

			$response = self::get_response();
		} catch ( Exception $e ) {
			return self::return_error( $e->getMessage() );
		}

		return $response;
	}

	/**
	 * Check if submission is possible.
	 *
	 * @param bool $freeze Optional. Should it prevent parallel requests. False by default.
	 * @throws Exception When there is an error.
	 */
	private static function can_submit( $freeze = false ) {
		// disable submissions if not published.
		if ( Forminator_Poll_Model::STATUS_PUBLISH !== self::$module_object->status ) {
			throw new Exception( esc_html__( 'Poll submissions disabled.', 'forminator' ) );
		}

		// Check poll opening status.
		$status_info = self::$module_object->opening_status();
		if ( 'open' !== $status_info['status'] ) {
			throw new Exception( esc_html( $status_info['msg'] ) );
		}

		$user_can_vote = self::$module_object->current_user_can_vote( $freeze );
		/**
		 * Filter to check if current user can vote
		 *
		 * @since 1.0.2
		 *
		 * @param bool $user_can_vote - if can vote depending on above conditions.
		 * @param int $form_id - the form id.
		 *
		 * @return bool $user_can_vote - true|false
		 */
		$user_can_vote = apply_filters( 'forminator_poll_handle_form_user_can_vote', $user_can_vote, self::$module_id );

		if ( ! $user_can_vote ) {
			self::$response_attrs['notice'] = 'error';
			throw new Exception( esc_html__( 'You have already submitted a vote to this poll', 'forminator' ) );
		}
	}

	/**
	 * Get field data
	 *
	 * @return string
	 * @throws Exception When there is an error.
	 */
	private static function get_field_data() {
		$field_data = isset( self::$prepared_data[ self::$module_id ] ) ? self::$prepared_data[ self::$module_id ] : false;
		if ( empty( $field_data ) ) {
			throw new Exception( esc_html__( 'You need to select a poll option', 'forminator' ) );
		}

		return $field_data;
	}

	/**
	 * Get field data array
	 *
	 * @param object $entry Entry object.
	 * @return array
	 */
	private static function get_field_data_array( $entry ) {
		$field_data = self::get_field_data();
		// get fields labels.
		$fields_labels    = self::$module_object->pluck_fields_array( 'title', 'element_id', '1' );
		$field_data_array = array(
			array(
				'name'  => $field_data,
				'value' => isset( $fields_labels[ $field_data ] ) ? $fields_labels[ $field_data ] : '1',
			),
		);
		if ( self::$module_object->is_method_browser_cookie() ) {
			self::set_vote_browser_cookie( self::$module_id );
		} else {
			$field_data_array[] = array(
				'name'  => '_forminator_user_ip',
				'value' => Forminator_Geo::get_user_ip(),
			);
		}

		$extra_field = isset( self::$prepared_data[ self::$module_id . '-extra' ] ) ? self::$prepared_data[ self::$module_id . '-extra' ] : false;
		if ( ! empty( $extra_field ) ) {
			$field_data_array[] = array(
				'name'  => 'extra',
				'value' => $extra_field,
			);

			if ( self::is_spam() ) {
				$entry->is_spam = 1;
				self::$is_spam  = true;
			}
		}

		return $field_data_array;
	}

	/**
	 * Save entry fields
	 *
	 * @param object $entry Entry object.
	 */
	private static function save_entry_fields( $entry ) {
		$field_data_array = self::get_field_data_array( $entry );

		/**
		 * Filter saved data before persisted into the database
		 *
		 * @since 1.0.2
		 *
		 * @param array $field_data_array - the entry data.
		 * @param int $form_id - the form id.
		 *
		 * @return array $field_data_array
		 */
		$field_data_array = apply_filters( 'forminator_polls_submit_field_data', $field_data_array, self::$module_id );

		/**
		 * Action called before setting fields to database
		 *
		 * @since 1.0.2
		 *
		 * @param Forminator_Form_Entry_Model $entry - the entry model.
		 * @param int $form_id - the form id.
		 * @param array $field_data_array - the entry data.
		 */
		do_action( 'forminator_polls_submit_before_set_fields', $entry, self::$module_id, $field_data_array );

		// ADDON add_entry_fields.
		$added_data_array = self::attach_addons_add_entry_fields( $field_data_array, $entry );

		$entry->set_fields( $added_data_array );
	}

	/**
	 * Send email
	 *
	 * @param object $entry Entry.
	 */
	private static function send_email( $entry ) {
		if ( self::$is_spam ) {
			return;
		}
		$forminator_mail_sender = new Forminator_Poll_Front_Mail();
		$forminator_mail_sender->process_mail( self::$module_object, $entry );
	}

	/**
	 * Get submission response
	 */
	private static function get_response() {
		self::$response_attrs['notice'] = 'success';

		$response = self::return_success( esc_html__( 'Your vote has been saved', 'forminator' ) );
		if ( ! isset( self::$module_settings['results-behav'] ) || ! in_array( self::$module_settings['results-behav'], array( 'show_after', 'link_on' ), true ) ) {
			return $response;
		}

		$response = self::prepare_response( $response, self::$module_object, self::$module_settings, self::$prepared_data );

		return $response;
	}

	/**
	 * Prepare response array
	 *
	 * @param array  $response response array.
	 * @param object $poll Module.
	 * @param array  $setting Settings.
	 * @param array  $post_data Post data.
	 * @return array
	 */
	private static function prepare_response( $response, $poll, $setting, $post_data ) {
		$url = add_query_arg(
			array(
				'saved'     => 'true',
				'form_id'   => self::$module_id,
				'render_id' => $post_data['render_id'],
			),
			forminator_get_current_url()
		);
		$url = apply_filters( 'forminator_poll_submit_url', $url, self::$module_id );

		if ( ! isset( $setting['enable-ajax'] ) || empty( $setting['enable-ajax'] ) ) {
			$is_ajax_enabled = false;
		} else {
			$is_ajax_enabled = filter_var( $setting['enable-ajax'], FILTER_VALIDATE_BOOLEAN );
		}

		// Results behav.
		$response['results_behav'] = (string) $setting['results-behav'];

		// Votes count.
		$response['votes_count'] = isset( $setting['show-votes-count'] ) ? filter_var( $setting['show-votes-count'], FILTER_VALIDATE_BOOLEAN ) : false;

		// Chart basic colors.
		$response['grids_color']   = ! empty( $setting['grid_lines'] ) ? $setting['grid_lines'] : '#E5E5E5';
		$response['labels_color']  = ! empty( $setting['grid_labels'] ) ? $setting['grid_labels'] : '#777771';
		$response['onchart_label'] = ! empty( $setting['onbar_votes'] ) ? $setting['onbar_votes'] : '#333333';

		// Tooltips.
		$response['tooltips_bg']    = ! empty( $setting['tooltips_background'] ) ? $setting['tooltips_background'] : '#333333';
		$response['tooltips_color'] = ! empty( $setting['tooltips_text'] ) ? $setting['tooltips_text'] : '#FFFFFF';

		// On chart label text.
		$response['votes_text'] = (string) esc_html__( 'vote(s)', 'forminator' );

		// View results link.
		$response['results_link'] = sprintf(
			'<a href="%s" class="forminator-link">%s</a>',
			esc_url( $url ),
			esc_html__( 'View results', 'forminator' )
		);

		if ( $is_ajax_enabled ) {
			// ajax enabled send result data to front end.
			$response['chart_data'] = self::get_chart_data( $poll );

			if ( isset( $setting['enable-votes-limit'] ) && 'true' === $setting['enable-votes-limit'] ) {
				$response['back_button'] = '<button type="button" class="forminator-button forminator-button-back">' . esc_html__( 'Back To Poll', 'forminator' ) . '</button>';
			}
		} else {
			// its not ajax enabled, send url result to front end.
			$response['url'] = $url;
		}

		return $response;
	}

	/**
	 * Get Chart data of Poll
	 *
	 * @param Forminator_Poll_Model $poll Poll model.
	 *
	 * @return array
	 */
	private static function get_chart_data( Forminator_Poll_Model $poll ) {

		$chart_colors         = forminator_get_poll_chart_colors( $poll->id );
		$default_chart_colors = $chart_colors;
		$chart_datas          = array();

		$form_settings        = $poll->settings;
		$number_votes_enabled = false; // TO-DO: Remove later. This will be handled through ChartJS function.

		$fields_array = $poll->get_fields_as_array();
		$map_entries  = Forminator_Form_Entry_Model::map_polls_entries( $poll->id, $fields_array );
		$fields       = $poll->get_fields();

		if ( ! is_null( $fields ) ) {

			foreach ( $fields as $field ) {

				// Label.
				$label = addslashes( $field->title );

				// Votes.
				$slug    = isset( $field->slug ) ? $field->slug : sanitize_title( $label );
				$entries = 0;

				if ( in_array( $slug, array_keys( $map_entries ), true ) ) {
					$entries = $map_entries[ $slug ];
				}

				$color = $field->color;

				if ( empty( $color ) ) {
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

		return $chart_datas;
	}

	/**
	 * Response message
	 *
	 * @param int $form_id Form Id.
	 * @param int $render_id Render Id.
	 *
	 * @since 1.0
	 */
	public function form_response_message( $form_id, $render_id ) {
		$response     = self::$response;
		$post_form_id = isset( $response['form_id'] ) ? sanitize_text_field( $response['form_id'] ) : 0;

		if ( empty( $response ) || ! is_array( $response ) ) {
			return;
		}

		// Only show to related form.
		if ( ! empty( $response ) && is_array( $response ) && (int) $form_id === (int) $post_form_id ) {
			$label_class = $response['success'] ? 'forminator-success' : 'forminator-error';
			if ( ( isset( $response['notice'] ) && 'error' === $response['notice'] )
				|| ( isset( $response['success'] ) && $response['success'] ) ) {
				?>
				<div role="alert" aria-live="polite" class="forminator-response-message forminator-show <?php echo esc_attr( $label_class ); ?>">
					<p class="forminator-label--<?php echo esc_attr( $label_class ); ?>">
						<?php echo esc_html( $response['message'] ); ?>
					</p>
				</div>
				<?php
			}
		}
	}

	/**
	 * Executor On form submit for attached addons
	 *
	 * @see   Forminator_Integration_Poll_Hooks::on_module_submit()
	 * @since 1.6.1
	 *
	 * @return bool true on success|string error message from addon otherwise
	 * @throws Exception When there is an error.
	 */
	private static function attach_addons_on_poll_submit() {
		$submitted_data = static::get_submitted_data();
		// find is_form_connected.
		$connected_addons = forminator_get_addons_instance_connected_with_module( self::$module_id, 'poll' );

		foreach ( $connected_addons as $connected_addon ) {
			try {
				$poll_hooks = $connected_addon->get_addon_hooks( self::$module_id, 'poll' );
				if ( ! $poll_hooks instanceof Forminator_Integration_Poll_Hooks ) {
					continue;
				}
				$addon_return = $poll_hooks->on_module_submit( $submitted_data );
			} catch ( Exception $e ) {
				forminator_addon_maybe_log( $connected_addon->get_slug(), 'failed to attach_addons_on_poll_submit', $e->getMessage() );
			}
			if ( true !== $addon_return ) {
				throw new Exception( esc_html( $poll_hooks->get_submit_error_message() ) );
			}
		}

		return true;
	}

	/**
	 * Set Browser Cookie when poll submit
	 *
	 * @param int $form_id Form Id.
	 */
	public static function set_vote_browser_cookie( $form_id ) {
		$poll        = Forminator_Base_Form_Model::get_model( $form_id );
		$settings    = $poll->settings;
		$duration    = 1;
		$expire      = time() + YEAR_IN_SECONDS * $duration;
		$cookie_name = 'poll-cookie-' . md5( $form_id );
		if ( $poll->is_allow_multiple_votes() ) {
			$duration           = ! empty( $settings['vote_limit_input'] ) ? absint( $settings['vote_limit_input'] ) : 1;
			$vote_limit_options = ! empty( $settings['vote_limit_options'] ) ? $settings['vote_limit_options'] : 'Y';
			switch ( $vote_limit_options ) {
				case 'h':
					$expire = time() + HOUR_IN_SECONDS * $duration;
					break;
				case 'd':
					$expire = time() + DAY_IN_SECONDS * $duration;
					break;
				case 'W':
					$expire = time() + WEEK_IN_SECONDS * $duration;
					break;
				case 'M':
					$expire = time() + MONTH_IN_SECONDS * $duration;
					break;
				case 'm':
					$expire = time() + MINUTE_IN_SECONDS * $duration;
					break;
				case 'Y':
					$expire = time() + YEAR_IN_SECONDS * $duration;
					break;
				default:
					$expire = time() + YEAR_IN_SECONDS * $duration;
					break;
			}
		}
		$current_date = date_i18n( 'Y-m-d H:i:s' );
		$secure       = ( 'https' === wp_parse_url( wp_login_url(), PHP_URL_SCHEME ) );
		setcookie( $cookie_name, $current_date, $expire, COOKIEPATH, COOKIE_DOMAIN, $secure, true );
	}
}
