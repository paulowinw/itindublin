<?php
/**
 * The Forminator_Poll_Model class.
 *
 * @package Forminator
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Author: Hoang Ngo
 */
class Forminator_Poll_Model extends Forminator_Base_Form_Model {

	/**
	 * Module slug
	 *
	 * @var string
	 */
	public static $module_slug = 'poll';

	/**
	 * Post type
	 *
	 * @var string
	 */
	protected $post_type = 'forminator_polls';

	/**
	 * Whether to check form access
	 *
	 * @since 1.0.5
	 *
	 * @var bool
	 */
	protected $check_access = true;

	/**
	 * Prepare data for preview
	 *
	 * @param object $form_model Model.
	 * @param array  $data Passed data.
	 *
	 * @return object
	 */
	public static function prepare_data_for_preview( $form_model, $data ) {
		// build the field.
		$fields = array();
		if ( isset( $data['answers'] ) ) {
			$fields = $data['answers'];
			unset( $data['answers'] );
		}

		// Set fields.
		foreach ( $fields as $f ) {
			$field          = new Forminator_Form_Field_Model();
			$field->form_id = isset( $f['wrapper_id'] ) ? $f['wrapper_id'] : $f['title'];
			$field->slug    = isset( $f['element_id'] ) ? $f['element_id'] : $f['title'];
			$field->import( $f );
			$form_model->add_field( $field );
		}

		return $form_model;
	}

	/**
	 * Check if the vote clause is set up and if a user can vote again
	 *
	 * @since 1.0
	 * @param bool $freeze Optional. Should it prevent parallel requests. False by default.
	 * @return bool
	 */
	public function current_user_can_vote( $freeze = false ) {
		/**
		 * Added condition for poll access.
		 *
		 * @since 1.0.5
		 */
		if ( $this->check_access ) {
			if ( $this->is_method_browser_cookie() ) {
				return $this->poll_votes_method_browser_cookie();
			} else {
				$user_ip      = Forminator_Geo::get_user_ip();
				$key_requests = 'forminator_request_from_ip_' . $this->id . '_' . $user_ip;
				// Check if the IP has made a request within the allowed interval.
				if ( get_transient( $key_requests ) ) {
					// IP has made a request too soon.
					$can_vote = false;
				} else {
					if ( $freeze ) {
						/**
						 * Filter the time interval (in seconds) between consecutive requests allowed per IP
						 *
						 * @param int    $seconds Second amount. 10 by default.
						 * @param string $user_ip Current IP.
						 */
						$expiration    = apply_filters( 'forminator_poll_request_interval_seconds', 10, $user_ip );
						$set_transient = set_transient( $key_requests, 1, $expiration );
						if ( ! $set_transient ) {
							// It doesn't set transient for parallel requests.
							return false;
						}
					}
					$can_vote = $this->poll_votes_method_user_ip();
				}

				return $can_vote;
			}
		}

		return true;
	}

	/**
	 * Check user can vote by browser cookie
	 *
	 * @return bool
	 */
	public function poll_votes_method_browser_cookie() {
		$settings    = $this->settings;
		$poll_cookie = 'poll-cookie-' . md5( $this->id );
		if ( ! isset( $_COOKIE[ $poll_cookie ] ) ) {
			return true;
		}
		if ( $this->is_allow_multiple_votes() ) {
			if ( isset( $settings['vote_limit_input'] ) && ! empty( $settings['vote_limit_input'] ) ) {
				$duration           = is_numeric( $settings['vote_limit_input'] ) ? $settings['vote_limit_input'] : '1';
				$vote_limit_options = isset( $settings['vote_limit_options'] ) ? $settings['vote_limit_options'] : 'm';
				switch ( $vote_limit_options ) {
					case 'h':
						$interval = 'hour';
						break;
					case 'd':
						$interval = 'day';
						break;
					case 'W':
						$interval = 'week';
						break;
					case 'M':
						$interval = 'month';
						break;
					case 'm':
						$interval = 'minute';
						break;
					case 'Y':
						$interval = 'year';
						break;
					default:
						$interval = 'year';
						break;
				}
				$cookie_value  = date_i18n( 'Y-m-d H:i:s', strtotime( Forminator_Core::sanitize_text_field( $_COOKIE[ $poll_cookie ] ) ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
				$cookie_expire = $cookie_value . ' +' . $duration . ' ' . $interval;
				if ( time() < strtotime( $cookie_expire ) ) {
					return false;
				} else {
					return true;
				}
			} else {
				return true;
			}
		} else {
			return false;
		}
	}


	/**
	 * Check user can vote by user IP
	 *
	 * @return bool
	 */
	public function poll_votes_method_user_ip() {
		$settings = $this->settings;
		$user_ip  = Forminator_Geo::get_user_ip();
		if ( $this->is_allow_multiple_votes() ) {

			if ( isset( $settings['vote_limit_input'] ) ) {
				$duration           = is_numeric( $settings['vote_limit_input'] ) ? $settings['vote_limit_input'] : 0;
				$vote_limit_options = isset( $settings['vote_limit_options'] ) ? $settings['vote_limit_options'] : 'm';
				switch ( $vote_limit_options ) {
					case 'h':
						$interval = "INTERVAL $duration HOUR";
						break;
					case 'd':
						$interval = "INTERVAL $duration DAY";
						break;
					case 'W':
						$interval = "INTERVAL $duration WEEK";
						break;
					case 'M':
						$interval = "INTERVAL $duration MONTH";
						break;
					case 'Y':
						$interval = "INTERVAL $duration YEAR";
						break;
					default:
						$interval = "INTERVAL $duration MINUTE";
						break;
				}
				$last_entry = Forminator_Form_Entry_Model::get_last_entry_by_ip_and_form( $this->id, $user_ip );
				if ( $last_entry ) {
					$can_vote = Forminator_Form_Entry_Model::check_entry_date_by_ip_and_form( $this->id, $user_ip, $last_entry, $interval );
					if ( $can_vote ) {
						return true;
					} else {
						return false;
					}
				} else {
					return true;
				}
			} else {
				return true;
			}
		} else {
			$last_entry = Forminator_Form_Entry_Model::get_last_entry_by_ip_and_form( $this->id, $user_ip );
			if ( $last_entry ) {
				return false;
			}
		}
		return true;
	}


	/**
	 * Overridden load function to check element_id of answers for older poll
	 * Backward compat for <= 1.0.4
	 * which is forminator poll doesnt have element_id on poll answers
	 *
	 * @since 1.0.5
	 *
	 * @param int  $id Id.
	 * @param bool $callback Callback.
	 *
	 * @return bool|Forminator_Poll_Model
	 */
	public function load( $id, $callback = false ) {
		$model = parent::load( $id, $callback );

		// callback means load latest post and replace data,.
		// so we dont need to add element_id since its must be try to loading preview.
		if ( ! $callback ) {
			if ( $model instanceof Forminator_Poll_Model ) {
				// patch for backward compat.
				return $this->maybe_add_element_id_on_answers( $model );
			}
		}

		return $model;
	}

	/**
	 * Add Element id on answers that doesnt have it
	 *
	 * @since 1.0.5
	 *
	 * @param Forminator_Poll_Model $model Poll model.
	 *
	 * @return Forminator_Poll_Model
	 */
	private function maybe_add_element_id_on_answers( Forminator_Poll_Model $model ) {
		$answers                   = $model->get_fields_as_array();
		$is_need_to_add_element_id = false;

		foreach ( $answers as $key => $answer ) {
			if ( ! isset( $answer['element_id'] ) || ! $answer['element_id'] ) {
				$is_need_to_add_element_id = true;
				break;
			}
		}

		if ( $is_need_to_add_element_id ) {

			// get max element id here.
			$max_element_id = 0;
			foreach ( $answers as $answer ) {
				if ( isset( $answer['element_id'] ) && $answer['element_id'] ) {
					$element_id = trim( str_replace( 'answer-', '', $answer['element_id'] ) );
					if ( $element_id > $max_element_id ) {
						$max_element_id = $element_id;
					}
				}
			}
			foreach ( $answers as $key => $answer ) {
				if ( ! isset( $answer['element_id'] ) || ! $answer['element_id'] ) {
					++$max_element_id;
					$answers[ $key ]['element_id'] = 'answer-' . $max_element_id; // start from 1.
					$answers[ $key ]['id']         = 'answer-' . $max_element_id; // start from 1.
				}
			}

			$model->clear_fields();
			foreach ( $answers as $answer ) {
				$field          = new Forminator_Form_Field_Model();
				$field->form_id = $model->id;
				$field->slug    = $answer['id'];
				unset( $answer['id'] );
				$field->import( $answer );
				$model->add_field( $field );
			}

			return $this->resave_and_reload( $model );
		}

		return $model;
	}

	/**
	 * Resave model and then load to return new model
	 *
	 * @since 1.0.5
	 *
	 * @param Forminator_Poll_Model $model Poll model.
	 *
	 * @return Forminator_Poll_Model
	 */
	private function resave_and_reload( Forminator_Poll_Model $model ) {
		$model->save();

		return $model;
	}

	/**
	 * Get Fields as array with `$key` as key of array and `$pluck_key` as $value with `$default` as fallback
	 *
	 * @since 1.0.5
	 *
	 * @param  string      $pluck_key Pluck key.
	 * @param  string|null $key Key.
	 * @param null        $default_value Default value.
	 *
	 * @return array
	 */
	public function pluck_fields_array( $pluck_key, $key = null, $default_value = null ) {
		$fields_with_key = array();
		$fields          = $this->get_fields_as_array();

		foreach ( $fields as $field ) {
			if ( '*' === $pluck_key ) {
				$field_value = $field;
			} elseif ( isset( $field[ $pluck_key ] ) ) {
					$field_value = $field[ $pluck_key ];
			} else {
				$field_value = $default_value;
			}

			if ( ! is_null( $key ) ) {
				if ( isset( $field[ $key ] ) ) {
					$fields_with_key[ $field[ $key ] ] = $field_value;
				} else {
					$fields_with_key[] = $field_value;
				}
			} else {
				$fields_with_key[] = $field_value;
			}
		}

		return $fields_with_key;
	}

	/**
	 * Get enable limit votes status flag
	 *
	 * @since 1.6.1
	 * @return bool
	 */
	public function is_allow_multiple_votes() {
		$settings             = $this->settings;
		$poll_id              = $this->id;
		$allow_multiple_votes = isset( $settings['enable-votes-limit'] ) ? filter_var( $settings['enable-votes-limit'], FILTER_VALIDATE_BOOLEAN ) : false;

		/**
		 * Filter allow_multiple_votes flag of a poll
		 *
		 * @since 1.6.1
		 *
		 * @param bool $allow_multiple_votes
		 * @param int $poll_id
		 * @param array $settings
		 */
		$allow_multiple_votes = apply_filters( 'forminator_poll_allow_multiple_votes', $allow_multiple_votes, $poll_id, $settings );

		return $allow_multiple_votes;
	}

	/**
	 * Get Browser votes method enable status flag
	 *
	 * @since 1.7
	 *
	 * @return bool
	 */
	public function is_method_browser_cookie() {
		$settings       = $this->settings;
		$poll_id        = $this->id;
		$browser_method = isset( $settings['enable-votes-method'] ) && 'browser_cookie' === $settings['enable-votes-method'] ? true : false;

		/**
		 * Filter browser_method flag of a poll
		 *
		 * @since 1.7
		 *
		 * @param bool $browser_method
		 * @param int $poll_id
		 * @param array $settings
		 */
		$browser_method = apply_filters( 'forminator_poll_method_browser_cookie', $browser_method, $poll_id, $settings );

		return $browser_method;
	}

	/**
	 * Check vote opening status
	 *
	 * @return array
	 */
	public function opening_status() {
		static $info = array();

		if ( isset( $info[ $this->id ] ) ) {
			return $info[ $this->id ];
		}

		$settings          = $this->settings;
		$info[ $this->id ] = array(
			'status' => 'open',
			'msg'    => '',
		);

		$close_msg = ( isset( $settings['opening_close_msg'] ) ) ? trim( $settings['opening_close_msg'] ) : '';
		if ( '' === $close_msg ) {
			$close_msg = esc_html__( 'Voting is closed', 'forminator' );
		}

		$pause_msg = ( isset( $settings['opening_pause_msg'] ) ) ? trim( $settings['opening_pause_msg'] ) : '';
		if ( '' === $pause_msg ) {
			$pause_msg = esc_html__( 'Voting is paused, check again later', 'forminator' );
		}

		$before_open_from_msg = ( isset( $settings['opening_before_open_from_msg'] ) ) ? trim( $settings['opening_before_open_from_msg'] ) : '';
		if ( '' === $before_open_from_msg ) {
			$before_open_from_msg = esc_html__( 'Voting has not been started yet, check again later', 'forminator' );
		}

		$status = ( isset( $settings['opening_status'] ) ) ? $settings['opening_status'] : 'open';
		if ( ! in_array( $status, array( 'open', 'pause', 'close' ), true ) ) {
			$status = 'open';
		}

		$info[ $this->id ]['status'] = $status;

		switch ( $status ) {
			case 'close':
				$info[ $this->id ]['msg'] = $close_msg;
				break;

			case 'pause':
				$info[ $this->id ]['msg'] = $pause_msg;
				break;

			case 'open':
				$current_time = current_time( 'timestamp' ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested -- We are using the current timestamp based on the site's timezone.

				// check open from and open until time.
				$open_from = ( isset( $settings['opening_open_from'] ) ) ? trim( $settings['opening_open_from'] ) : 'now';
				if ( '' === $open_from ) {
					$open_from = 'now';
				}

				$open_until = ( isset( $settings['opening_open_until'] ) ) ? trim( $settings['opening_open_until'] ) : 'forever';
				if ( '' === $open_until ) {
					$open_until = 'forever';
				}

				if ( 'now' !== $open_from ) {
					$open_from_time = ( isset( $settings['opening_open_from_date_time'] ) ) ? trim( $settings['opening_open_from_date_time'] ) : '';
					if ( '' !== $open_from_time ) {
						$open_from_timestamp = strtotime( $open_from_time );
						if ( $current_time < $open_from_timestamp ) {
							$info[ $this->id ]['status'] = 'before_open_from';
							$info[ $this->id ]['msg']    = $before_open_from_msg;
							return $info[ $this->id ];
						}
					}
				}

				if ( 'forever' !== $open_until ) {
					$open_until_time = ( isset( $settings['opening_open_until_date_time'] ) ) ? trim( $settings['opening_open_until_date_time'] ) : '';
					if ( '' !== $open_until_time ) {
						$open_until_timestamp = strtotime( $open_until_time );
						if ( $current_time > $open_until_timestamp ) {
							$info[ $this->id ]['status'] = 'close';
							$info[ $this->id ]['msg']    = $close_msg;
							return $info[ $this->id ];
						}
					}
				}

				break;

			default:
				break;

		}

		return $info[ $this->id ];
	}
}
