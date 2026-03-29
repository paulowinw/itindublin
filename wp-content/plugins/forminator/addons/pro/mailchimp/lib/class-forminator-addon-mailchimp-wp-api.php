<?php
/**
 * Forminator Addon Mailchimp API.
 *
 * @package Forminator
 */

/**
 * Class Forminator_Mailchimp_Wp_Api
 * Wrapper @see wp_remote_request() to be used to do request to mailchimp server
 *
 * @since 1.0 Mailchimp Integration
 */
class Forminator_Mailchimp_Wp_Api {

	/**
	 * Mailchimp API instance
	 *
	 * @since 1.0 Mailchimp Integration
	 * @var self|null
	 */
	private static $_instance = null;

	/**
	 * Endpoint of Mailchimp API
	 *
	 * @since 1.0 Mailchimp Integration
	 * @var string
	 */
	private $_endpoint = 'https://{dc}.api.mailchimp.com/3.0/';

	/**
	 * API Key used to send request
	 *
	 * @since 1.0 Mailchimp Integration
	 * @var string
	 */
	private $_api_key = '';

	/**
	 * Last data sent to mailchimp API
	 *
	 * @since 1.0 Mailchimp Integration
	 * @var array
	 */
	private $_last_data_sent = array();

	/**
	 * Last data received from mailchimp API
	 *
	 * @since 1.0 Mailchimp Integration
	 * @var array
	 */
	private $_last_data_received = array();

	/**
	 * Last URL requested
	 *
	 * @since 1.0 Mailchimp Integration
	 * @var string
	 */
	private $_last_url_request = '';

	/**
	 * Forminator_Mailchimp_Wp_Api constructor.
	 *
	 * @since 1.0 Mailchimp Integration
	 *
	 * @param string $api_key API key.
	 *
	 * @throws Forminator_Integration_Exception Throws Integration Exception.
	 */
	public function __construct( $api_key ) {
		// prerequisite.
		if ( ! $api_key ) {
			throw new Forminator_Integration_Exception( esc_html__( 'Missing required API Key', 'forminator' ) );
		}

		$this->_api_key = $api_key;

		$exploded    = explode( '-', $this->_api_key );
		$data_center = end( $exploded );

		// endpoint data center are taken from api key.
		$this->_endpoint = str_replace( '{dc}', $data_center, $this->_endpoint );
	}

	/**
	 * Get singleton
	 *
	 * @since 1.0 Mailchimp Integration
	 *
	 * @param string|null $api_key API Key.
	 *
	 * @return Forminator_Mailchimp_Wp_Api|null
	 */
	public static function get_instance( $api_key = null ) {
		if ( is_null( self::$_instance ) || self::$_instance->_api_key !== $api_key ) {
			self::$_instance = new self( $api_key );
		}

		return self::$_instance;
	}

	/**
	 * Add extra info on user agent header used to send request
	 *
	 * @since 1.0 Mailchimp Integration
	 *
	 * @param string $user_agent User Agent.
	 *
	 * @return string
	 */
	public function filter_user_agent( $user_agent ) {

		$user_agent .= ' ForminatorMailChimp/' . FORMINATOR_ADDON_MAILCHIMP_VERSION;

		/**
		 * Filter user agent to be used by mailchimp api
		 *
		 * @since 1.1
		 *
		 * @param string $user_agent current user agent.
		 */
		$user_agent = apply_filters( 'forminator_addon_mailchimp_api_user_agent', $user_agent );

		return $user_agent;
	}

	/**
	 * HTTP Request
	 *
	 * @since 1.0 Mailchimp Integration
	 *
	 * @param string $verb `GET` `POST` `PUT` `DELETE` `PATCH`.
	 * @param string $path Requested path.
	 * @param array  $args Arguments.
	 *
	 * @return array|mixed|object
	 * @throws Forminator_Integration_Exception Throws Integration Exception.
	 */
	private function request( $verb, $path, $args = array() ) {
		try {
			// Adding extra user agent for wp remote request.
			add_filter( 'http_headers_useragent', array( $this, 'filter_user_agent' ) );
			$url  = trailingslashit( $this->_endpoint ) . $path;
			$verb = ! empty( $verb ) ? $verb : 'GET';

			/**
			 * Filter mailchimp url to be used on sending api request
			 *
			 * @since 1.1
			 *
			 * @param string $url  full url with scheme.
			 * @param string $verb `GET` `POST` `PUT` `DELETE` `PATCH`.
			 * @param string $path requested path resource.
			 * @param array  $args argument sent to this function.
			 */
			$url = apply_filters( 'forminator_addon_mailchimp_api_url', $url, $verb, $path, $args );

			$this->_last_url_request = $url;

			$headers = array(
				'Authorization' => 'ForminatorMailChimp ' . $this->_api_key,
			);

			/**
			 * Filter mailchimp headers to sent on api request
			 *
			 * @since 1.1
			 *
			 * @param array  $headers
			 * @param string $verb `GET` `POST` `PUT` `DELETE` `PATCH`.
			 * @param string $path requested path resource.
			 * @param array  $args argument sent to this function.
			 */
			$headers = apply_filters( 'forminator_addon_mailchimp_api_request_headers', $headers, $verb, $path, $args );

			$_args = array(
				'method'  => $verb,
				'headers' => $headers,
			);

			$request_data = $args;
			/**
			 * Filter mailchimp request data to be used on sending api request
			 *
			 * @since 1.1
			 *
			 * @param array  $request_data it will be `http_build_query`-ed when `GET` or `wp_json_encode`-ed otherwise.
			 * @param string $verb         `GET` `POST` `PUT` `DELETE` `PATCH`.
			 * @param string $path         requested path resource.
			 */
			$args = apply_filters( 'forminator_addon_mailchimp_api_request_data', $request_data, $verb, $path );

			if ( 'GET' === $verb ) {
				$url .= ( '?' . http_build_query( $args ) );
			} else {
				$_args['body'] = wp_json_encode( $args );
			}

			$this->_last_data_sent = $args;

			$res = wp_remote_request( $url, $_args );

			remove_filter( 'http_headers_useragent', array( $this, 'filter_user_agent' ) );

			if ( is_wp_error( $res ) || ! $res ) {
				forminator_addon_maybe_log( __METHOD__, $res );
				throw new Forminator_Integration_Exception( esc_html__( 'Failed to process request, make sure API KEY is correct and your server has internet connection.', 'forminator' ) );
			}

			$body = wp_remote_retrieve_body( $res );

			$response = null;

			// DELETE probably won't receiving contents on success.
			if ( 'DELETE' !== $verb ) {
				// Got no response from API.
				if ( empty( $body ) ) {
					forminator_addon_maybe_log( __METHOD__, $res );
					throw new Forminator_Integration_Exception(
						esc_html__(
							'Failed to process request, make sure API KEY is correct and your server has internet connection.',
							'forminator'
						)
					);
				}
			}

			if ( ! empty( $body ) ) {
				$response      = json_decode( $body );
				$response_code = wp_remote_retrieve_response_code( $res );

				// check response status from API.
				if ( isset( $response_code ) ) {
					if ( $response_code >= 400 ) {
						forminator_addon_maybe_log( __METHOD__, $response );
						$msg = '';
						if ( isset( $response->detail ) ) {
							// if exist, error detail is given by mailchimp here.
							$msg = $response->detail;
						}
						$this->_last_data_received = $response;
						if ( 404 === $response_code ) {
							throw new Forminator_Integration_Exception(
								sprintf(
								/* translators: %s: Error message */
									esc_html__( 'Failed to process request : %s', 'forminator' ),
									esc_html( $msg )
								)
							);
						}
						throw new Forminator_Integration_Exception(
							sprintf(
							/* translators: %s: Error message */
								esc_html__( 'Failed to process request : %s', 'forminator' ),
								esc_html( $msg )
							)
						);
					}
				}

				// Probably response is failed to be json decoded.
				if ( is_null( $response ) ) {
					$this->_last_data_received = $body;
					forminator_addon_maybe_log( __METHOD__, $res );
					throw new Forminator_Integration_Exception(
						sprintf(
						/* translators: %s: Error message */
							esc_html__( 'Failed to process request : %s', 'forminator' ),
							json_last_error_msg()
						)
					);
				}
			}

			$wp_response = $res;

			// in case not receving json decoded body use $wp_response.
			if ( is_null( $response ) ) {
				$response = $wp_response;
			}
			/**
			 * Filter mailchimp api response returned to integration
			 *
			 * @since 1.1
			 *
			 * @param mixed          $response
			 * @param string         $body        original content of http response's body.
			 * @param array|WP_Error $wp_response original wp remote request response.
			 */
			$response = apply_filters( 'forminator_addon_mailchimp_api_response', $response, $body, $wp_response );

			$this->_last_data_received = $response;
		} catch ( Forminator_Integration_Exception $e ) {
			$response = $e;
		}

		return $response;
	}

	/**
	 * Ping Mailchimp API to check if API key is valid
	 *
	 * @since 1.21.0 Mailchimp Integration
	 *
	 * @return array|mixed|object
	 */
	public function ping() {
		return $this->request(
			'GET',
			'ping'
		);
	}

	/**
	 * Get User Info for the current API KEY
	 *
	 * @since 1.0 Mailchimp Integration
	 *
	 * @param array $fields Fields.
	 *
	 * @return array|mixed|object
	 */
	public function get_info( $fields = array() ) {
		if ( empty( $fields ) ) {
			$fields = array( 'account_id', 'account_name', 'email' );
		}

		return $this->request(
			'GET',
			'',
			array(
				'fields' => implode( ',', $fields ),
			)
		);
	}

	/**
	 * Get Mailchimp Lists
	 *
	 * @since 1.0 Mailchimp Integration
	 *
	 * @param array $args Arguments.
	 *
	 * @return array|mixed|object
	 * @throws Forminator_Integration_Exception Throws Integration Exception.
	 */
	public function get_lists( $args ) {
		$default_args = array(
			'fields'     => implode( ',', array( 'lists.id', 'lists.name', 'total_items' ) ),
			'count'      => 10,
			'sort_field' => 'date_created',
			'sort_dir'   => 'DESC',
		);

		$args = array_merge( $default_args, $args );

		return $this->request(
			'GET',
			'lists',
			$args
		);
	}

	/**
	 * Get all lists
	 *
	 * @param bool $force Use cahce or not.
	 * @return array
	 */
	public function get_all_lists( $force = false ) {
		$option_key = 'forminator_mailchimp_' . $this->_api_key;
		if ( ! $force ) {
			$lists = get_option( $option_key );
			if ( ! empty( $lists ) && is_array( $lists ) ) {
				return $lists;
			}
		}

		$lists  = array();
		$limit  = 1000;
		$offset = 0;

		do {
			$args     = array(
				'count'  => $limit,
				'offset' => $offset,
			);
			$response = $this->get_lists( $args );

			if ( is_wp_error( $response ) || ! isset( $response->lists ) || ! is_array( $response->lists ) ) {
				return array();
			}

			$_lists = $response->lists;
			$total  = $response->total_items;
			if ( is_array( $_lists ) ) {
				$lists = array_merge( $lists, $_lists );
			}

			$offset += $limit;
		} while ( $total > $offset );

		update_option( $option_key, $lists );

		return $lists;
	}

	/**
	 * Get List of merge fields
	 *
	 * @since 1.0 Mailchimp Integration
	 *
	 * @param string $list_id List Id.
	 * @param string $args Arguments.
	 *
	 * @return array|mixed|object
	 */
	public function get_list_merge_fields( $list_id, $args ) {
		$default_args = array(
			'fields'     => implode( ',', array( 'merge_fields.merge_id', 'merge_fields.tag', 'merge_fields.name', 'merge_fields.type', 'merge_fields.required' ) ),
			'count'      => 100,
			'sort_field' => 'display_order',
			'sort_dir'   => 'ASC',
		);

		$args = array_merge( $default_args, $args );

		return $this->request(
			'GET',
			'lists/' . $list_id . '/merge-fields/',
			$args
		);
	}

	/**
	 * Add new Merge Field To List
	 *
	 * @since 1.0 Mailchimp Integration
	 *
	 * @param string $list_id List Id.
	 * @param array  $args Arguments.
	 *
	 * @return array|mixed|object
	 * @throws Forminator_Integration_Exception Throws Integration Exception.
	 */
	public function add_merge_field_to_list( $list_id, $args ) {
		$available_types = array(
			'text',
			'number',
			'address',
			'phone',
			'date',
			'url',
			'image',
			'radio',
			'dropdown',
			'birthday',
			'zip',
		);

		$default_args = array(
			'type' => 'text',
		);

		$args = array_merge( $default_args, $args );
		if ( ! in_array( $args['type'], $available_types, true ) ) {
			throw new Forminator_Integration_Exception( esc_html__( 'Invalid Field Type', 'forminator' ) );
		}

		return $this->request(
			'POST',
			'lists/' . $list_id . '/merge-fields/',
			$args
		);
	}

	/**
	 * Get Created categories withing a list
	 *
	 * @since 1.0 Mailchimp Integration
	 *
	 * @param string $list_id List Id.
	 * @param array  $args Arguments.
	 *
	 * @return array|mixed|object
	 */
	public function get_list_categories( $list_id, $args ) {
		$default_args = array(
			'fields'     => implode( ',', array( 'categories.id', 'categories.title', 'categories.type' ) ),
			'count'      => 10,
			'sort_field' => 'date_created',
			'sort_dir'   => 'DESC',
		);

		$args = array_merge( $default_args, $args );

		$groups = $this->request(
			'GET',
			'lists/' . $list_id . '/interest-categories',
			$args
		);

		if ( is_wp_error( $groups ) ) {
			$groups = array();
		} else {
			$groups = (array) $groups->categories;
		}

		return $groups;
	}

	/**
	 * Get members by list ID
	 *
	 * @param string $list_id List ID.
	 * @return array
	 */
	private function get_members( $list_id ) {
		$data = $this->request(
			'GET',
			'lists/' . $list_id . '/members'
		);

		return $data && is_object( $data ) && ! empty( $data->members ) ? $data->members : array();
	}


	/**
	 * Gets all the GDPR fields under a list
	 *
	 * @param string $list_id List ID.
	 * @return array
	 */
	public function get_gdpr_fields( $list_id ) {
		$gdpr_fieds = array();
		$members    = $this->get_members( $list_id );
		if ( ! $members ) {
			$email = 'dummy@incsub.com';
			$args  = array(
				'email_address' => $email,
				'status'        => 'unsubscribed',
			);
			$this->add_member_to_list( $list_id, $args );
			$members = $this->get_members( $list_id );
			$this->delete_member( $list_id, md5( strtolower( $email ) ) );
		}

		if ( empty( $members ) || ! is_array( $members ) || empty( $members[0]->marketing_permissions ) || ! is_array( $members[0]->marketing_permissions ) ) {
			return $gdpr_fieds;
		}

		foreach ( $members[0]->marketing_permissions as $value ) {
			if ( ! isset( $value->marketing_permission_id ) || ! isset( $value->text ) ) {
				continue;
			}
			$gdpr_fieds[ $value->marketing_permission_id ] = $value->text;
		}

		return $gdpr_fieds;
	}

	/**
	 * Gets all the tags/static segments on a list
	 *
	 * @param string $list_id List ID.
	 * @return array|mixed|object|WP_Error
	 */
	public function get_tags( $list_id ) {
		$tags = $this->request(
			'GET',
			'lists/' . $list_id . '/segments',
			array(
				'count' => 1000,
				'type'  => 'static',
			)
		);

		$tags    = $tags->segments;
		$options = wp_list_pluck( $tags, 'name', 'id' );

		return $options;
	}

	/**
	 * Get Created Interest Groups within a category
	 *
	 * @since 1.0 Mailchimp Integration
	 *
	 * @param string $list_id List Id.
	 * @param string $category_id Category Id.
	 * @param array  $args Arguments.
	 *
	 * @return array|mixed|object
	 */
	public function get_category_interests( $list_id, $category_id, $args ) {
		$default_args = array(
			'fields'     => implode( ',', array( 'interests.id', 'interests.name' ) ),
			'count'      => 10,
			'sort_field' => 'display_order',
			'sort_dir'   => 'ASC',
		);

		$args = array_merge( $default_args, $args );

		return $this->request(
			'GET',
			'lists/' . $list_id . '/interest-categories/' . $category_id . '/interests',
			$args
		);
	}

	/**
	 * Gets all the interests under a group list
	 *
	 * @param string $list_id List id.
	 * @param string $category_id Category id.
	 * @param int    $count Count.
	 *
	 * @return array|mixed|object|WP_Error
	 */
	public function get_interests( $list_id, $category_id, $count = 1000 ) {
		$response = $this->request(
			'GET',
			'lists/' . $list_id . '/interest-categories/' . $category_id . '/interests',
			array(
				'count' => $count,
			)
		);

		if ( is_wp_error( $response ) || empty( $response->interests ) || ! is_array( $response->interests ) ) {
			return array();
		}

		$interests = wp_list_pluck( $response->interests, 'name', 'id' );

		return $interests;
	}

	/**
	 * Get detail of member
	 *
	 * @since 1.0 Mailchimp Integration
	 *
	 * @param string $list_id List Id.
	 * @param string $subscriber_hash The MD5 hash of the lowercase version of the list member’s email address.
	 * @param array  $args Arguments.
	 *
	 * @return array|mixed|object
	 */
	public function get_member( $list_id, $subscriber_hash, $args ) {
		$default_args = array(
			'fields' => implode( ',', array( 'id', 'email_address', 'status' ) ),
		);

		$args = array_merge( $default_args, $args );

		return $this->request(
			'GET',
			'lists/' . $list_id . '/members/' . $subscriber_hash,
			$args
		);
	}

	/**
	 * Add Member to list
	 *
	 * @since 1.0 Mailchimp Integration
	 *
	 * @param string $list_id List Id.
	 * @param array  $args Arguments.
	 *
	 * @return array|mixed|object
	 * @throws Forminator_Integration_Exception Throws Integration Exception.
	 */
	public function add_member_to_list( $list_id, $args ) {
		$default_args = array(
			'status'       => 'pending',
			'merge_fields' => new stdClass(),
			'interests'    => new stdClass(),
		);

		$args = array_merge( $default_args, $args );

		if ( ! isset( $args['email_address'] ) ) {
			throw new Forminator_Integration_Exception( esc_html__( 'email_address are required for mailchimp', 'forminator' ) );
		}

		return $this->request(
			'POST',
			'lists/' . $list_id . '/members/',
			$args
		);
	}

	/**
	 * Add member if not available, or update member if exist
	 *
	 * @since 1.0 Mailchimp Integration
	 *
	 * @param string $list_id List Id.
	 * @param string $subscriber_hash Subscriber hash.
	 * @param array  $args Arguments.
	 *
	 * @return array|mixed|object
	 * @throws Forminator_Integration_Exception Throws Integration Exception.
	 */
	public function add_or_update_member( $list_id, $subscriber_hash, $args ) {
		$default_args = array(
			'status_if_new' => 'pending',
			'status'        => 'pending',
			'merge_fields'  => array(),
			'interests'     => array(),
		);

		$args = array_merge( $default_args, $args );
		if ( ! isset( $args['email_address'] ) ) {
			throw new Forminator_Integration_Exception( esc_html__( 'email_address are required for adding member to mailchimp list', 'forminator' ) );
		}

		if ( empty( $args['merge_fields'] ) ) {
			unset( $args['merge_fields'] );
		} else {
			$args['merge_fields'] = (object) $args['merge_fields'];
		}

		if ( empty( $args['interests'] ) ) {
			unset( $args['interests'] );
		} else {
			$args['interests'] = (object) $args['interests'];
		}

		if ( empty( $args['interests'] ) ) {
			unset( $args['interests'] );
		}

		return $this->request(
			'PUT',
			'lists/' . $list_id . '/members/' . $subscriber_hash,
			$args
		);
	}

	/**
	 * Get detail of member
	 *
	 * @since 1.0 Mailchimp Integration
	 *
	 * @param string $list_id List Id.
	 * @param string $subscriber_hash The MD5 hash of the lowercase version of the list member’s email address.
	 *
	 * @return array|mixed|object
	 */
	public function delete_member( $list_id, $subscriber_hash ) {
		return $this->request(
			'DELETE',
			'lists/' . $list_id . '/members/' . $subscriber_hash,
			array()
		);
	}

	/**
	 * Send `DELETE` request to URL
	 *
	 * Useful to interact with mailchimp schema _links.
	 *
	 * @since 1.0 Mailchimp Integration
	 *
	 * @param string $url href from _links response which will be converted to path.
	 *
	 * @return array|mixed|object
	 */
	public function delete_( $url ) {
		$path = str_ireplace( $this->_endpoint, '', $url );

		return $this->request( 'DELETE', $path );
	}

	/**
	 * Get last data sent
	 *
	 * @since 1.0 Mailchimp Integration
	 *
	 * @return array
	 */
	public function get_last_data_sent() {
		return $this->_last_data_sent;
	}

	/**
	 * Get last data received
	 *
	 * @since 1.0 Mailchimp Integration
	 *
	 * @return array
	 */
	public function get_last_data_received() {
		return $this->_last_data_received;
	}

	/**
	 * Get last data received
	 *
	 * @since 1.0 Mailchimp Integration
	 *
	 * @return string
	 */
	public function get_last_url_request() {
		return $this->_last_url_request;
	}

	/**
	 * Get current endpoint to send to Malchimp
	 *
	 * @since 1.0 Mailchimp Integration
	 * @return string
	 */
	public function get_endpoint() {
		return $this->_endpoint;
	}
}
