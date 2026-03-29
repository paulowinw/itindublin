<?php
/**
 * Forminator Addon Mailjet API.
 *
 * @package Forminator
 */

/**
 * Class Forminator_Mailjet_Wp_Api
 * Wrapper @see wp_remote_request() to be used to do request to mailjet server
 */
class Forminator_Mailjet_Wp_Api {

	/**
	 * Mailjet API instance
	 *
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * Endpoint of Mailjet API
	 *
	 * @var string
	 */
	private $endpoint = 'https://api.mailjet.com/v3/REST/';

	/**
	 * API Key used to send request
	 *
	 * @var string
	 */
	private $api_key = '';

	/**
	 * Secret Key used to send request
	 *
	 * @var string
	 */
	private $secret_key = '';

	/**
	 * Last data sent to mailjet API
	 *
	 * @var array
	 */
	private $last_data_sent = array();

	/**
	 * Last data received from mailjet API
	 *
	 * @var array
	 */
	private $last_data_received = array();

	/**
	 * Last URL requested
	 *
	 * @var string
	 */
	private $last_url_request = '';

	/**
	 * Forminator_Mailjet_Wp_Api constructor.
	 *
	 * @param string $api_key API Key.
	 * @param string $secret_key API Secret Key.
	 * @throws Forminator_Integration_Exception Throws Integration Exception.
	 */
	public function __construct( $api_key, $secret_key ) {
		if ( ! $api_key ) {
			throw new Forminator_Integration_Exception( esc_html__( 'Missing required API Key', 'forminator' ) );
		}

		$this->api_key = $api_key;
		if ( ! $secret_key ) {
			throw new Forminator_Integration_Exception( esc_html__( 'Missing required Secret Key', 'forminator' ) );
		}

		$this->secret_key = $secret_key;
	}

	/**
	 * Get singleton
	 *
	 * @param string $api_key API Key.
	 * @param string $secret_key API Secret Key.
	 *
	 * @return Forminator_Mailjet_Wp_Api|null
	 */
	public static function get_instance( $api_key, $secret_key ) {
		if ( is_null( self::$instance ) || self::$instance->api_key !== $api_key || self::$instance->secret_key !== $secret_key ) {
			self::$instance = new self( $api_key, $secret_key );
		}

		return self::$instance;
	}

	/**
	 * HTTP Request
	 *
	 * @param string $verb HTTP Request type.
	 * @param string $path Request path.
	 * @param array  $args Arguments.
	 *
	 * @return array|mixed|object
	 * @throws Forminator_Integration_Exception Throws Integration Exception.
	 */
	private function request( $verb, $path, $args = array() ) {
		$url = $this->get_endpoint() . $path;

		$this->last_url_request = $url;

		$headers = array(
			'Authorization' => 'Basic  ' . base64_encode( $this->api_key . ':' . $this->secret_key ), //phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		);

		$_args = array(
			'method' => $verb,
		);

		if ( 'GET' === $verb ) {
			$url .= ( '?' . http_build_query( $args ) );
		} else {
			$_args['body']           = wp_json_encode( $args );
			$headers['Content-Type'] = 'application/json';
		}

		$_args['headers'] = $headers;

		$this->last_data_sent = $args;

		$res = wp_remote_request( $url, $_args );

		$default_error = esc_html__( 'Failed to process the request. Please ensure the API keys are correct and the server has an active internet connection.', 'forminator' );

		if ( is_wp_error( $res ) || ! $res ) {
			forminator_addon_maybe_log( __METHOD__, $res );
			throw new Forminator_Integration_Exception( esc_html( $default_error ) );
		}

		$body = wp_remote_retrieve_body( $res );

		// Got no response from API.
		if ( empty( $body ) ) {
			forminator_addon_maybe_log( __METHOD__, $res );
			throw new Forminator_Integration_Exception( esc_html( $default_error ) );
		}

		$response = null;
		if ( ! empty( $body ) ) {
			$response      = json_decode( $body );
			$response_code = wp_remote_retrieve_response_code( $res );

			// check response status from API.
			if ( isset( $response_code ) ) {
				if ( $response_code >= 400 ) {
					forminator_addon_maybe_log( __METHOD__, $response );
					$msg = '';
					if ( isset( $response->ErrorMessage ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
						// if exist, error detail is given by mailjet here.
						$msg = $response->ErrorMessage; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
					}
					$this->last_data_received = $response;
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
				$this->last_data_received = $body;
				forminator_addon_maybe_log( __METHOD__, $res );
				throw new Forminator_Integration_Exception(
					sprintf(
					/* translators: %s: Error message */
						esc_html__( 'Failed to process request : %s', 'forminator' ),
						esc_html( json_last_error_msg() )
					)
				);
			}
		}

		$wp_response = $res;

		// in case not receving json decoded body use $wp_response.
		if ( is_null( $response ) ) {
			$response = $wp_response;
		} else {
			$response = self::array_change_key_case_recursive( $response );
		}
		/**
		 * Filter mailjet api response returned to integration
		 *
		 * @param mixed          $response
		 * @param string         $body        original content of http response's body.
		 * @param array|WP_Error $wp_response original wp remote request response.
		 */
		$response = apply_filters( 'forminator_addon_mailjet_api_response', $response, $body, $wp_response );

		$this->last_data_received = $response;

		return $response;
	}

	/**
	 * Recursively convert all the keys to lowercase
	 *
	 * @param array|object $arr Base variable.
	 * @return array|object
	 */
	private static function array_change_key_case_recursive( $arr ) {
		if ( is_object( $arr ) ) {
			$is_object = true;
			$arr       = (array) $arr;
		}
		$new_arr = array_map(
			function ( $item ) {
				if ( is_array( $item ) || is_object( $item ) ) {
					$item = self::array_change_key_case_recursive( $item );
				}
				return $item;
			},
			array_change_key_case( $arr )
		);

		if ( ! empty( $is_object ) ) {
			$new_arr = (object) $new_arr;
		}
		return $new_arr;
	}

	/**
	 * Get User Info for the current API KEY
	 *
	 * @return array|mixed|object
	 */
	public function get_info() {
		return $this->request( 'GET', 'user' );
	}

	/**
	 * Get Mailjet Lists
	 *
	 * @param array $args Arguments.
	 *
	 * @return array|mixed|object
	 */
	public function get_lists( $args ) {
		return $this->request(
			'GET',
			'contactslist',
			$args
		);
	}

	/**
	 * Get prepared array of Mailchimp lists
	 *
	 * @param bool $force Use cache or not.
	 * @return array
	 */
	public function get_prepared_lists( $force = false ) {
		try {
			$lists = $this->get_all_lists( $force );
			$lists = wp_list_pluck( $lists, 'name', 'id' );
		} catch ( Forminator_Integration_Exception $e ) {
			forminator_addon_maybe_log( __METHOD__, $e->getMessage() );
			return array();
		}

		return $lists;
	}

	/**
	 * Get all lists
	 *
	 * @param bool $force Use cahce or not.
	 * @return array
	 */
	public function get_all_lists( $force = false ) {
		$option_key = 'forminator_mailjet_' . $this->api_key;
		if ( ! $force ) {
			$lists = get_option( $option_key );
			if ( ! empty( $lists ) && is_array( $lists ) ) {
				return $lists;
			}
		}

		$lists  = array();
		$limit  = 1000;
		$offset = 0;

		$get_total = $this->get_lists( array( 'countOnly' => true ) );
		if ( is_wp_error( $get_total ) || ! isset( $get_total->total ) ) {
			forminator_addon_maybe_log( __METHOD__, __( 'The request to retrieve the total number of lists has failed.', 'forminator' ) );
			return array();
		}

		$total = $get_total->total;

		do {
			$args     = array(
				'Limit'  => $limit,
				'Offset' => $offset,
			);
			$response = $this->get_lists( $args );

			if ( is_wp_error( $response ) || ! isset( $response->data ) || ! is_array( $response->data ) ) {
				forminator_addon_maybe_log( __METHOD__, __( 'The request to retrieve the lists has failed.', 'forminator' ) );
				return array();
			}

			$_lists = $response->data;
			if ( is_array( $_lists ) ) {
				$lists = array_merge( $lists, $_lists );
			}

			$offset += $limit;
		} while ( $total > $offset );

		update_option( $option_key, $lists );

		return $lists;
	}

	/**
	 * Get List of contact_properties
	 *
	 * @return array
	 */
	public function get_contact_properties() {
		$contactmetadata = $this->request(
			'GET',
			'contactmetadata/',
			array( 'Limit' => 1000 )
		);

		$properties = array();
		if ( ! empty( $contactmetadata->data ) && is_array( $contactmetadata->data ) ) {
			$properties = $contactmetadata->data;
		}

		return $properties;
	}

	/**
	 * Add member if not available, or update member if exist
	 *
	 * @param string $list_id List ID.
	 * @param string $email Email.
	 * @param array  $args Additional arguments.
	 *
	 * @return array|mixed|object
	 */
	public function add_or_update_member( $list_id, $email, $args ) {
		$data = array(
			'Email'  => $email,
			'Action' => 'addnoforce',
		);
		if ( ! empty( $args['name'] ) ) {
			$data['Name'] = $args['name'];
		}
		if ( ! empty( $args['merge_fields'] ) ) {
			$data['Properties'] = $args['merge_fields'];
		}

		$result = $this->request(
			'POST',
			'contactslist/' . $list_id . '/managecontact',
			$data
		);

		if ( empty( $result->total ) ) {
			return false;
		}

		return $result;
	}

	/**
	 * Get last data sent
	 *
	 * @return array
	 */
	public function get_last_data_sent() {
		return $this->last_data_sent;
	}

	/**
	 * Get last data received
	 *
	 * @return array
	 */
	public function get_last_data_received() {
		return $this->last_data_received;
	}

	/**
	 * Get last data received
	 *
	 * @return string
	 */
	public function get_last_url_request() {
		return $this->last_url_request;
	}

	/**
	 * Get current endpoint to send to Malchimp
	 *
	 * @return string
	 */
	public function get_endpoint() {
		return $this->endpoint;
	}
}
