<?php
/**
 * Forminator Template API
 *
 * @package Forminator
 */

/**
 * Class Forminator_Template_API
 */
class Forminator_Template_API {
	/**
	 * API key
	 *
	 * @var string
	 */
	private static ?string $api_key = null;

	/**
	 * Singleton instance
	 *
	 * @var Forminator_Template_API
	 */
	private static ?Forminator_Template_API $instance = null;


	/**
	 * Get instance of this class.
	 *
	 * @return Forminator_Template_API
	 */
	public static function get_instance(): Forminator_Template_API {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {}

	/**
	 * Get API key
	 *
	 * @return string
	 */
	private static function get_api_key() {
		if ( is_null( self::$api_key ) ) {
			if ( class_exists( 'WPMUDEV_Dashboard' ) ) {
				self::$api_key = WPMUDEV_Dashboard::$api->get_key();
			} elseif ( Forminator_Hub_Connector::hub_connector_connected() ) {
				self::$api_key = \WPMUDEV\Hub\Connector\API::get()->get_api_key();
			}
		}
		return self::$api_key;
	}

	/**
	 * Get templates
	 *
	 * @param bool   $is_official - official or custom cloud templates.
	 * @param int    $page_number - page number.
	 * @param string $category - template category.
	 * @param string $search - search term.
	 * @return array
	 */
	public static function get_templates( bool $is_official, int $page_number = 0, string $category = '', string $search = '' ): array {
		/**
		 * Filter templates per page
		 *
		 * @param int $per_page - templates per page.
		 */
		$per_page = apply_filters( 'forminator_templates_per_page', 100 );
		$args     = array(
			'is_official' => $is_official ? 1 : 0,
			'per_page'    => $per_page,
		);
		if ( $is_official ) {
			$args['order_by'] = 'name';
			$args['order']    = 'ASC';
		}
		if ( $category ) {
			$args['category'] = $category;
		}
		if ( $search ) {
			$args['search'] = $search;
		}
		if ( $page_number ) {
			$args['page'] = $page_number;
		}
		$templates = self::request(
			'api/hub/v1/forminator-templates',
			'GET',
			$args
		);

		$templates = self::prepare_templates_structure( $templates );

		/**
		 * Filter templates
		 *
		 * @param array $templates - templates.
		 * @param bool $is_official - official or custom templates.
		 * @param int    $page_number - page number.
		 * @param string $search - search term.
		 * @param string $category - template category.
		 */
		return apply_filters( 'forminator_templates', $templates, $is_official, $page_number, $search, $category );
	}

	/**
	 * Get template
	 *
	 * @param int $id - template id.
	 *
	 * @return array
	 */
	public static function get_template( int $id ): array {
		$template = self::request(
			'api/hub/v1/forminator-templates/' . $id,
			'GET'
		);

		return self::prepare_template_structure( $template );
	}

	/**
	 * Prepare templates structure
	 *
	 * @param array $templates - templates.
	 *
	 * @return array
	 */
	private static function prepare_templates_structure( array $templates ): array {
		foreach ( $templates as $key => $template ) {
			$templates[ $key ] = self::prepare_template_structure( $template );
		}

		return $templates;
	}

	/**
	 * Prepare template structure
	 *
	 * @param array $template - template.
	 *
	 * @return array
	 */
	private static function prepare_template_structure( array $template ): array {
		if ( empty( $template ) ) {
			return $template;
		}
		$template['id']         = $template['template_id'];
		$template['category']   = $template['category']['slug'];
		$template['screenshot'] = $template['thumbnail']['original'] ?? '';
		$template['thumbnail']  = $template['thumbnail']['original'] ?? '';
		$template['pro']        = true;

		return $template;
	}

	/**
	 * Create template
	 *
	 * @param string $name - template name.
	 * @param string $json - template config.
	 * @param string $description - template description.
	 *
	 * @return array
	 */
	public static function create_template( string $name, string $json, string $description = '' ): array {
		if ( ! forminator_is_user_allowed( 'forminator-templates' ) ) {
			return array();
		}
		$args     = self::prepare_template_args( $name, $json, $description );
		$response = self::request(
			'api/hub/v1/forminator-templates',
			'POST',
			$args
		);

		return self::prepare_template_structure( $response );
	}

	/**
	 * Update template
	 *
	 * @param int    $id - template id.
	 * @param string $name - template name.
	 * @param string $json - template config.
	 * @param string $description - template description.
	 *
	 * @return array
	 */
	public static function update_template( int $id, string $name, string $json, string $description = '' ): array {
		if ( ! forminator_is_user_allowed( 'forminator-templates' ) ) {
			return array();
		}
		$args     = self::prepare_template_args( $name, $json, $description );
		$response = self::request(
			'api/hub/v1/forminator-templates/' . $id,
			'POST',
			$args
		);

		return self::prepare_template_structure( $response );
	}

	/**
	 * Prepare update template args
	 *
	 * @param string $name - template name.
	 * @param string $json - template config.
	 * @param string $description - template description.
	 *
	 * @return array
	 */
	private static function prepare_template_args( string $name, string $json, string $description ): array {
		$args = array();
		if ( $json ) {
			$args = array(
				'config' => $json,
			);
		}
		if ( $name ) {
			$args['name'] = $name;
		}
		if ( $description ) {
			$args['description'] = $description;
		}

		return $args;
	}

	/**
	 * Delete template
	 *
	 * @param int $id - template id.
	 *
	 * @return bool
	 */
	public static function delete_template( int $id ): bool {
		if ( ! forminator_is_user_allowed( 'forminator-templates' ) ) {
			return false;
		}
		$response = self::request(
			'api/hub/v1/forminator-templates/' . $id,
			'DELETE'
		);

		return $response['removed'] ?? false;
	}

	/**
	 * Get template categories
	 *
	 * @return array
	 */
	public static function get_categories(): array {
		$categories = self::request(
			'api/hub/v1/forminator-templates/categories',
			'GET'
		);

		return $categories;
	}


	/**
	 * Remote request
	 *
	 * @param string $endpoint - API endpoint.
	 * @param string $method - HTTP method.
	 * @param array  $data - request data.
	 *
	 * @return array
	 */
	private static function request( string $endpoint, string $method, array $data = array() ): array {
		$base = 'https://wpmudev.com/';

		// Support custom API base.
		if ( defined( 'WPMUDEV_CUSTOM_API_SERVER' ) && ! empty( WPMUDEV_CUSTOM_API_SERVER ) ) {
			$base = trailingslashit( WPMUDEV_CUSTOM_API_SERVER );
		}
		$url = $base . $endpoint;

		$args = array(
			'method' => $method,
		);

		try {
			if ( class_exists( 'WPMUDEV_Dashboard' ) && method_exists( WPMUDEV_Dashboard::$api, 'get_site_id' ) ) {
				$data['site_id'] = WPMUDEV_Dashboard::$api->get_site_id();
			} elseif ( Forminator_Hub_Connector::hub_connector_logged_in() ) {
				$data['site_id'] = \WPMUDEV\Hub\Connector\Data::get()->hub_site_id();
			}

			if ( 'GET' === $method ) {
				$url = add_query_arg( $data, $url );
			} else {
				$args['body'] = $data;
			}

			$api_key = self::get_api_key();
			if ( $api_key ) {
				$args['headers'] = array(
					'Authorization' => $api_key,
				);
			}

			$response = wp_remote_request( $url, $args );

			if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
				return array();
			}

			$body = wp_remote_retrieve_body( $response );
		} catch ( Exception $e ) {
			return array();
		}

		$result = json_decode( $body, true );
		if ( is_array( $result ) ) {
			return $result;
		}

		return array();
	}
}
