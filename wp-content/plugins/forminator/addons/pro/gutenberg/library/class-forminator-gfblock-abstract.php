<?php
/**
 * Forminator GFBlock Abstract.
 *
 * @package Forminator
 */

/**
 * Class Forminator_GFBlock_Abstract
 * Extend this class to create new gutenberg block
 *
 * @since 1.0 Gutenber Integration
 */
abstract class Forminator_GFBlock_Abstract {

	/**
	 * Type will be used as identifier
	 *
	 * @since 1.0 Gutenber Integration
	 *
	 * @var string
	 */
	protected $_slug;

	/**
	 * Get block type
	 *
	 * @since  1.0 Gutenber Integration
	 * @return string
	 */
	final public function get_slug() {
		return $this->_slug;
	}

	/**
	 * Initialize block
	 *
	 * @since 1.0 Gutenberg Integration
	 */
	public function init() {
		// Register block.
		add_action( 'init', array( $this, 'register_block' ), 6 );

		// Register preview REST API.
		add_action( 'rest_api_init', array( $this, 'block_preview_api' ) );

		// Load block scripts.
		add_action( 'enqueue_block_editor_assets', array( $this, 'load_assets' ) );

		// Load elementor scripts.
		add_action( 'elementor/preview/enqueue_scripts', array( $this, 'load_assets' ) );
	}

	/**
	 * Register block type callback
	 * Shouldn't be overridden on block class
	 *
	 * @since 1.0 Gutenberg Integration
	 */
	public function register_block() {
		if ( is_admin() ) {
			// Load block scripts.
			add_action( 'enqueue_block_assets', array( $this, 'load_assets' ) );

			register_block_type(
				'forminator/' . $this->get_slug(),
				array(
					'editor_style'    => array(
						'forminator-ui-icons',
						'forminator-ui-utilities',
						'forminator-ui-grid-open',
						'forminator-ui-grid-enclosed',
						'forminator-ui-basic',
						'forminator-ui',
					),
					'script'          => array( 'forminator-front-scripts', 'select2-forminator', 'jquery-ui-slider' ),
					'render_callback' => array( $this, 'render_block' ),
				)
			);
		}
	}

	/**
	 * Check if user has permissions to preview block.
	 *
	 * @since 1.50.3
	 *
	 * @return bool
	 */
	public function check_preview_permissions() {
		// If user can manage option or edit any of the content types, it means that they can access the editor and preview the block.
		if ( current_user_can( 'manage_options' ) || current_user_can( 'edit_pages' )
			|| current_user_can( 'edit_posts' ) || current_user_can( 'edit_themes' ) ) {
			$allow_preview = true;
		} else {
			$allow_preview = false;
		}

		/**
		 * Filter to modify block preview permissions.
		 *
		 * @since 1.50.3
		 *
		 * @param bool $allow_preview Whether to allow block preview.
		 * @param string $form_type The form type (slug) for which the preview is being checked.
		 */
		return apply_filters( 'forminator_block_preview_permissions', $allow_preview, $this->get_slug() );
	}

	/**
	 * Register REST API route for block preview.
	 *
	 * @since 1.0 Gutenberg Integration
	 */
	public function block_preview_api() {
		register_rest_route(
			'forminator/v1',
			'/preview/' . $this->get_slug(),
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'preview_block_markup' ),
					'permission_callback' => array( $this, 'check_preview_permissions' ),
					'args'                => array(
						'module_id' => array(
							'description' => esc_html__( 'Module ID', 'forminator' ),
							'type'        => 'integer',
							'required'    => true,
						),
					),
				),
			)
		);
	}

	/**
	 * Print block preview markup
	 *
	 * @since 1.0 Gutenberg Integration
	 * @param mixed $data Data.
	 */
	public function preview_block_markup( $data ) {
		// Get properties.
		$properties = $data->get_params();

		// Get module ID.
		$id = isset( $properties['module_id'] ) ? $properties['module_id'] : false;

		// Get block preview markup.
		$markup = $this->preview_block( $properties );

		if ( $markup ) {
			wp_send_json_success( array( 'markup' => trim( $markup ) ) );
		} else {
			wp_send_json_error();
		}
	}

	/**
	 * Render block on front-end
	 * Should be overriden in block class
	 *
	 * @since 1.0 Gutenberg Integration
	 * @param array $properties Block properties.
	 *
	 * @return string
	 */
	public function render_block( $properties = array() ) {
		return '';
	}

	/**
	 * Preview form in the block
	 * Should be overriden in block class
	 *
	 * @since 1.0 Gutenberg Integration
	 * @param array $properties Block properties.
	 *
	 * @return string
	 */
	public function preview_block( $properties = array() ) {
		return '';
	}

	/**
	 * Enqueue assets ( scritps / styles )
	 * Should be overriden in block class
	 *
	 * @since 1.0 Gutenberg Integration
	 */
	public function load_assets() {
		return true;
	}
}
