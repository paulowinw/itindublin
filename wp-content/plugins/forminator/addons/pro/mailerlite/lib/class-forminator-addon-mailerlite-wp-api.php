<?php
/**
 * Forminator Addon Mailerlite API.
 *
 * @package Forminator
 */

/**
 * Class Forminator_Mailerlite_Wp_Api
 * Wrapper @see wp_remote_request() to be used to do request to mailerlite server
 */
class Forminator_Mailerlite_Wp_Api {

	/**
	 * Mailerlite API instance
	 *
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * Endpoint of Mailerlite API
	 *
	 * @var string
	 */
	private $endpoint = 'https://connect.mailerlite.com/api/';

	/**
	 * API Key used to send request
	 *
	 * @var string
	 */
	private $api_key = '';

	/**
	 * Last data sent to mailerlite API
	 *
	 * @var array
	 */
	private $last_data_sent = array();

	/**
	 * Last data received from mailerlite API
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
	 * Forminator_Mailerlite_Wp_Api constructor.
	 *
	 * @param string $api_key API Key.
	 */
	private function __construct( $api_key ) {
		$this->api_key = $api_key;
	}

	/**
	 * Get singleton
	 *
	 * @param string $api_key API Key.
	 *
	 * @return Forminator_Mailerlite_Wp_Api
	 */
	public static function get_instance( $api_key ) {
		if ( is_null( self::$instance ) || self::$instance->api_key !== $api_key ) {
			self::$instance = new self( $api_key );
		}

		return self::$instance;
	}

	/**
	 * HTTP Request
	 *
	 * @param string $verb Request type.
	 * @param string $path Request path.
	 * @param array  $args Arguments.
	 *
	 * @return array|mixed|object
	 * @throws Forminator_Integration_Exception Throws Integration Exception.
	 */
	private function request( $verb, $path, $args = array() ) {
		$url   = $this->get_endpoint() . $path;
		$_args = array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $this->api_key,
				'Content-Type'  => 'application/json',
				'Accept'        => 'application/json',
				'User-Agent'    => 'ForminatorMailerLite/1.0',
			),
		);

		if ( 'GET' === $verb ) {
			$url .= ( '?' . http_build_query( $args ) );
		} else {
			$_args['body']   = wp_json_encode( $args );
			$_args['method'] = 'POST';
		}

		$this->last_data_sent   = $args;
		$this->last_url_request = $url;
		$res                    = wp_remote_request( $url, $_args );

		$default_error = esc_html__( 'Failed to process the request. Please ensure the API key is correct and the server has an active internet connection.', 'forminator' );

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
			if ( isset( $response_code ) && $response_code >= 400 ) {
				forminator_addon_maybe_log( __METHOD__, $response );
				$msg = '';
				if ( isset( $response->message ) ) {
					// if exist, error detail is given by mailerlite here.
					$msg = $response->message;
				}
				$this->last_data_received = $response;
				throw new Forminator_Integration_Exception(
					sprintf(
					/* translators: %s: Error message */
						esc_html__( 'Failed to process request : %s', 'forminator' ),
						esc_html( $msg )
					)
				);
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

		// in case not receiving json decoded body use $wp_response.
		if ( is_null( $response ) ) {
			$response = $wp_response;
		}
		/**
		 * Filter mailerlite api response returned to integration
		 *
		 * @param mixed          $response
		 * @param string         $body        original content of http response's body.
		 * @param array|WP_Error $wp_response original wp remote request response.
		 */
		$response = apply_filters( 'forminator_addon_mailerlite_api_response', $response, $body, $wp_response );

		$this->last_data_received = $response;

		return $response;
	}

	/**
	 * Get User Info for the current API KEY
	 *
	 * @return array|mixed|object
	 */
	public function get_info() {
		return $this->request( 'GET', 'subscribers', array( 'limit' => 0 ) );
	}

	/**
	 * Get Mailerlite Lists
	 *
	 * @param array $args Arguments.
	 *
	 * @return array|mixed|object
	 */
	public function get_lists( $args ) {
		return $this->request(
			'GET',
			'groups',
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
		$option_key = 'forminator_mailerlite_' . $this->api_key;
		if ( ! $force ) {
			$lists = get_option( $option_key );
			if ( ! empty( $lists ) && is_array( $lists ) ) {
				return $lists;
			}
		}

		$args     = array(
			'sort' => 'name',
		);
		$response = $this->get_lists( $args );

		if ( is_wp_error( $response ) || ! isset( $response->data ) || ! is_array( $response->data ) ) {
			forminator_addon_maybe_log( __METHOD__, __( 'The request to retrieve the lists has failed.', 'forminator' ) );
			return array();
		}
		$lists = $response->data;

		update_option( $option_key, $lists );

		return $lists;
	}

	/**
	 * Get List of contact_properties
	 *
	 * @return array
	 */
	public function get_contact_properties() {
		$fields = $this->request(
			'GET',
			'fields',
			array( 'sort' => 'name' )
		);

		$properties = array();
		if ( ! empty( $fields->data ) && is_array( $fields->data ) ) {
			$properties = $fields->data;
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
			'email'  => $email,
			'groups' => array( $list_id ),
			'status' => 'active',
		);
		if ( ! empty( $args['merge_fields'] ) ) {
			$data['fields'] = (object) $args['merge_fields'];
		}

		$result = $this->request(
			'POST',
			'subscribers/',
			$data
		);
		if ( empty( $result->data ) ) {
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
