<?php
/**
 * The Forminator_WP_Post_Autofill_Provider class.
 *
 * @package Forminator
 */

/**
 * Class Forminator_WP_Post_Autofill_Provider
 */
class Forminator_WP_Post_Autofill_Provider extends Forminator_Autofill_Provider_Abstract {

	/**
	 * Slug
	 *
	 * @var string
	 */
	protected $_slug = 'wp_post';

	/**
	 * Name
	 *
	 * @var string
	 */
	protected $_name = 'WordPress Post';

	/**
	 * Short name
	 *
	 * @var string
	 */
	protected $_short_name = 'WP Post';

	/**
	 * Forminator_WP_Post_Autofill_Provider
	 *
	 * @var self|null
	 */
	private static $_instance = null;

	/**
	 * WP Post
	 *
	 * @var WP_Post
	 */
	private $wp_post;

	/**
	 * Forminator_WP_Post_Autofill_Provider constructor.
	 */
	public function __construct() {

		$attributes_map = array(
			'id'        => array(
				'name'         => esc_html__( 'ID', 'forminator' ),
				'value_getter' => array( $this, 'get_value_id' ),
			),
			'title'     => array(
				'name'         => esc_html__( 'Title', 'forminator' ),
				'value_getter' => array( $this, 'get_value_title' ),
			),
			'permalink' => array(
				'name'         => esc_html__( 'Permalink', 'forminator' ),
				'value_getter' => array( $this, 'get_value_permalink' ),
			),
		);

		$this->attributes_map = $attributes_map;

		$this->hook_to_fields();
	}

	/**
	 * Get Id
	 *
	 * @return int
	 */
	public function get_value_id() {
		return $this->wp_post->ID;
	}

	/**
	 * Get title
	 *
	 * @return string
	 */
	public function get_value_title() {
		return $this->wp_post->post_title;
	}

	/**
	 * Get permalink
	 *
	 * @return false|string
	 */
	public function get_value_permalink() {
		return get_permalink( $this->wp_post->ID );
	}

	/**
	 * Is enabled
	 *
	 * @return bool
	 */
	public function is_enabled() {
		return true;
	}

	/**
	 * Fillable
	 *
	 * @return bool
	 */
	public function is_fillable() {
		if ( ! $this->wp_post instanceof WP_Post ) {
			return false;
		}

		return true;
	}

	/**
	 * Get instance
	 *
	 * @return Forminator_Autofill_Provider_Interface|Forminator_WP_Post_Autofill_Provider|null
	 */
	public static function get_instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Init post
	 */
	public function init() {
		global $post;

		if ( $post instanceof WP_Post ) {
			$this->wp_post = get_post();
		} else {
			$wp_referer = wp_get_referer();
			if ( $wp_referer ) {
				$post_id = url_to_postid( $wp_referer );
				if ( $post_id ) {
					$post_object = get_post( $post_id );
					// make sure its wp_post.
					if ( $post_object instanceof WP_Post ) {
						$this->wp_post = $post_object;
					}
				}
			}
		}
	}

	/**
	 * Get attribute
	 *
	 * @return array
	 */
	public function get_attribute_to_hook() {
		return array(
			'text' => array(
				'wp_post.title',
			),
		);
	}
}
