<?php
/**
 * Forminator Trello API
 *
 * @package Forminator
 */

/**
 * Class Forminator_Trello_Wp_Api
 */
class Forminator_Trello_Wp_Api {

	/**
	 * Trello API endpoint
	 *
	 * @var string
	 */
	private $_endpoint = 'https://trello.com/1/';

	/**
	 * Trello APP Key
	 *
	 * @var string
	 */
	private $_app_key = '';

	/**
	 * Trello user Token
	 *
	 * @var string
	 */
	private $_token = '';

	/**
	 * Last data sent to trello
	 *
	 * @since 1.0 Trello Integration
	 * @var array
	 */
	private $_last_data_sent = array();

	/**
	 * Last data received from trello
	 *
	 * @since 1.0 Trello Integration
	 * @var array
	 */
	private $_last_data_received = array();

	/**
	 * Last URL requested
	 *
	 * @since 1.0 Trello Integration
	 * @var string
	 */
	private $_last_url_request = '';

	/**
	 * Forminator_Trello_Wp_Api constructor.
	 *
	 * @since 1.0 Trello Integration
	 *
	 * @param string $app_key App Key.
	 * @param string $token Token.
	 *
	 * @throws Forminator_Integration_Exception Throws Integration Exception.
	 */
	public function __construct( $app_key, $token ) {
		// prerequisites.
		if ( ! $app_key ) {
			throw new Forminator_Integration_Exception( esc_html__( 'Missing required APP Key', 'forminator' ) );
		}

		if ( ! $token ) {
			throw new Forminator_Integration_Exception( esc_html__( 'Missing required Token', 'forminator' ) );
		}

		$this->_app_key = $app_key;
		$this->_token   = $token;
	}

	/**
	 * Add custom user agent on request
	 *
	 * @since 1.0 Trello Integration
	 *
	 * @param string $user_agent User Agent.
	 *
	 * @return string
	 */
	public function filter_user_agent( $user_agent ) {
		$user_agent .= ' ForminatorTrello/' . FORMINATOR_ADDON_TRELLO_VERSION;

		/**
		 * Filter user agent to be used by trello api
		 *
		 * @since 1.1
		 *
		 * @param string $user_agent current user agent.
		 */
		$user_agent = apply_filters( 'forminator_addon_trello_api_user_agent', $user_agent );

		return $user_agent;
	}

	/**
	 * HTTP Request
	 *
	 * @since 1.0 Trello Integration
	 *
	 * @param string $verb HTTP Request type.
	 * @param string $path Request path.
	 * @param array  $args Arguments.
	 *
	 * @return array|mixed|object
	 * @throws Forminator_Integration_Exception Throws Integration Exception.
	 */
	private function request( $verb, $path, $args = array() ) {
		// Adding extra user agent for wp remote request.
		add_filter( 'http_headers_useragent', array( $this, 'filter_user_agent' ) );

		$url  = trailingslashit( $this->_endpoint ) . $path;
		$verb = ! empty( $verb ) ? $verb : 'GET';

		/**
		 * Filter trello url to be used on sending api request
		 *
		 * @since 1.1
		 *
		 * @param string $url  full url with scheme.
		 * @param string $verb `GET` `POST` `PUT` `DELETE` `PATCH`.
		 * @param string $path requested path resource.
		 * @param array  $args argument sent to this function.
		 */
		$url = apply_filters( 'forminator_addon_trello_api_url', $url, $verb, $path, $args );

		$this->_last_url_request = $url;

		$headers = array();

		/**
		 * Filter trello headers to sent on api request
		 *
		 * @since 1.1
		 *
		 * @param array  $headers
		 * @param string $verb `GET` `POST` `PUT` `DELETE` `PATCH`.
		 * @param string $path requested path resource.
		 * @param array  $args argument sent to this function.
		 */
		$headers = apply_filters( 'forminator_addon_trello_api_request_headers', $headers, $verb, $path, $args );

		$_args = array(
			'method'  => $verb,
			'headers' => $headers,
		);

		$request_data = $args;
		/**
		 * Filter trello request data to be used on sending api request
		 *
		 * @since 1.1
		 *
		 * @param array  $request_data it will be `http_build_query`-ed when `GET` or `wp_json_encode`-ed otherwise.
		 * @param string $verb         `GET` `POST` `PUT` `DELETE` `PATCH`.
		 * @param string $path         requested path resource.
		 */
		$args = apply_filters( 'forminator_addon_trello_api_request_data', $request_data, $verb, $path );

		if ( 'GET' === $verb ) {
			$url .= ( '?' . http_build_query( $args ) );
		} else {
			$_args['body'] = $args;
		}

		$this->_last_data_sent = $args;

		$res         = wp_remote_request( $url, $_args );
		$wp_response = $res;

		remove_filter( 'http_headers_useragent', array( $this, 'filter_user_agent' ) );

		if ( is_wp_error( $res ) || ! $res ) {
			forminator_addon_maybe_log( __METHOD__, $res );
			throw new Forminator_Integration_Exception(
				esc_html__( 'Failed to process request, make sure you authorized Trello and your server has internet connection.', 'forminator' )
			);
		}

		if ( isset( $res['response']['code'] ) ) {
			$status_code = $res['response']['code'];
			$msg         = '';
			if ( $status_code > 400 ) {
				if ( isset( $res['response']['message'] ) ) {
					$msg = $res['response']['message'];
				}

				if ( 404 === $status_code ) {
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

		$body = wp_remote_retrieve_body( $res );

		// probably silent mode.
		if ( ! empty( $body ) ) {
			$res = json_decode( $body );
			forminator_addon_maybe_log( __METHOD__, $res );
		}

		$response = $res;
		/**
		 * Filter trello api response returned to integration
		 *
		 * @since 1.1
		 *
		 * @param mixed          $response    original wp remote request response or decoded body if available.
		 * @param string         $body        original content of http response's body.
		 * @param array|WP_Error $wp_response original wp remote request response.
		 */
		$res = apply_filters( 'forminator_addon_trello_api_response', $response, $body, $wp_response );

		$this->_last_data_received = $res;

		forminator_addon_maybe_log( $res );

		return $res;
	}


	/**
	 * Send POST Request
	 *
	 * @since 1.0 Trello Integration
	 *
	 * @param string $path Request path.
	 * @param array  $args Arguments.
	 *
	 * @return array|mixed|object
	 */
	public function post_( $path, $args = array() ) {
		$default_args = array(
			'key'   => $this->_app_key,
			'token' => $this->_token,
		);

		$args = array_merge( $default_args, $args );

		return $this->request(
			'POST',
			$path,
			$args
		);
	}

	/**
	 * Send GET Request
	 *
	 * @since 1.0 Trello Integration
	 *
	 * @param string $path Request path.
	 * @param array  $args Arguments.
	 *
	 * @return array|mixed|object
	 */
	public function get_( $path, $args = array() ) {
		$default_args = array(
			'key'   => $this->_app_key,
			'token' => $this->_token,
		);

		$args = array_merge( $default_args, $args );

		return $this->request(
			'GET',
			$path,
			$args
		);
	}

	/**
	 * Send PUT Request
	 *
	 * @since 1.0 Trello Integration
	 *
	 * @param string $path Request path.
	 * @param array  $args Arguments.
	 *
	 * @return array|mixed|object
	 */
	public function put_( $path, $args = array() ) {
		$default_args = array(
			'key'   => $this->_app_key,
			'token' => $this->_token,
		);

		$args = array_merge( $default_args, $args );

		return $this->request(
			'PUT',
			$path,
			$args
		);
	}

	/**
	 * Send DELETE Request
	 *
	 * @since 1.0 Trello Integration
	 *
	 * @param string $path Request path.
	 * @param array  $args Arguments.
	 *
	 * @return array|mixed|object
	 */
	public function delete_( $path, $args = array() ) {
		$default_args = array(
			'key'   => $this->_app_key,
			'token' => $this->_token,
		);

		$args = array_merge( $default_args, $args );

		return $this->request(
			'DELETE',
			$path,
			$args
		);
	}

	/**
	 * Get Boards
	 *
	 * @since 1.0 Trello Integration
	 *
	 * @param array $args Arguments.
	 *
	 * @return array|mixed|object
	 */
	public function get_boards( $args = array() ) {
		$default_args = array();
		$args         = array_merge( $default_args, $args );

		return $this->get_( 'members/me/boards', $args );
	}

	/**
	 * Get List
	 *
	 * @since 1.0 Trello Integration
	 *
	 * @param string $board_id Board Id.
	 * @param array  $args Arguments.
	 *
	 * @return array|mixed|object
	 */
	public function get_board_lists( $board_id, $args = array() ) {
		$default_args = array();
		$args         = array_merge( $default_args, $args );

		return $this->get_( 'boards/' . trim( $board_id ) . '/lists', $args );
	}

	/**
	 * Get Members
	 *
	 * @since 1.0 Trello Integration
	 *
	 * @param string $board_id Board Id.
	 * @param array  $args Arguments.
	 *
	 * @return array|mixed|object
	 */
	public function get_board_members( $board_id, $args = array() ) {
		$default_args = array();
		$args         = array_merge( $default_args, $args );

		return $this->get_( 'boards/' . trim( $board_id ) . '/members', $args );
	}

	/**
	 * Get Members
	 *
	 * @since 1.0 Trello Integration
	 *
	 * @param array $args Arguments.
	 *
	 * @return array|mixed|object
	 * @throws Forminator_Integration_Exception Throws Integration Exception.
	 */
	public function create_card( $args = array() ) {
		$default_args = array(
			'name' => esc_html__( 'Forminator Trello Card', 'forminator' ),
			'pos'  => 'bottom',
		);
		$args         = array_merge( $default_args, $args );

		if ( ! isset( $args['idList'] ) ) {
			throw new Forminator_Integration_Exception( esc_html__( 'idList Required to create a Trello Card', 'forminator' ) );
		}

		return $this->post_( 'cards', $args );
	}

	/**
	 * Add Attachment
	 *
	 * @since 1.15.? Trello Integration
	 *
	 * @param string $card_id Card Id.
	 * @param array  $upload Attachment.
	 *
	 * @return array|mixed|object
	 */
	public function add_attachment( $card_id, $upload ) {
		$arg         = array();
		$arg['name'] = basename( wp_parse_url( $upload, PHP_URL_PATH ) );
		$arg['url']  = $upload;

		return $this->post_( 'cards/' . trim( $card_id ) . '/attachments', $arg );
	}

	/**
	 * Delete Card (not reversible)
	 *
	 * @since 1.0 Trello Integration
	 *
	 * @param string $card_id Card Id.
	 * @param array  $args Arguments.
	 *
	 * @return array|mixed|object
	 */
	public function delete_card( $card_id, $args = array() ) {
		$default_args = array();
		$args         = array_merge( $default_args, $args );

		return $this->delete_( 'cards/' . trim( $card_id ), $args );
	}

	/**
	 * Close card shortcut
	 *
	 * @since 1.0 Trello Integration
	 *
	 * @param string $card_id Card Id.
	 * @param array  $args Arguments.
	 *
	 * @return array|mixed|object
	 */
	public function close_card( $card_id, $args = array() ) {
		$default_args = array(
			'closed' => true,
		);
		$args         = array_merge( $default_args, $args );

		return $this->update_card( $card_id, $args );
	}

	/**
	 * Update Card
	 *
	 * @since 1.0 Trello Integration
	 *
	 * @param string $card_id Card Id.
	 * @param array  $args Arguments.
	 *
	 * @return array|mixed|object
	 */
	public function update_card( $card_id, $args = array() ) {
		$default_args = array();
		$args         = array_merge( $default_args, $args );

		return $this->put_( 'cards/' . trim( $card_id ), $args );
	}

	/**
	 * Get Labels
	 *
	 * @since 1.0 Trello Integration
	 *
	 * @param string $board_id Board Id.
	 * @param array  $args Arguments.
	 *
	 * @return array|mixed|object
	 */
	public function get_board_labels( $board_id, $args = array() ) {
		$default_args = array();
		$args         = array_merge( $default_args, $args );

		return $this->get_( 'boards/' . trim( $board_id ) . '/labels', $args );
	}

	/**
	 * Get last data sent
	 *
	 * @since 1.0 Trello Integration
	 *
	 * @return array
	 */
	public function get_last_data_sent() {
		return $this->_last_data_sent;
	}

	/**
	 * Get last data received
	 *
	 * @since 1.0 Trello Integration
	 *
	 * @return array
	 */
	public function get_last_data_received() {
		return $this->_last_data_received;
	}

	/**
	 * Get last data received
	 *
	 * @since 1.0 Trello Integration
	 *
	 * @return string
	 */
	public function get_last_url_request() {
		return $this->_last_url_request;
	}

	/**
	 * Get card ID
	 *
	 * @since 1.0 Trello Integration
	 *
	 * @return array
	 */
	public function get_card_id() {
		return $this->get_last_data_received()->id;
	}
}
