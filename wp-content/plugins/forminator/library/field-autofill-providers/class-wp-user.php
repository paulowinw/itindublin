<?php
/**
 * The Forminator_WP_User_Autofill_Provider class.
 *
 * @package Forminator
 */

/**
 * Class Forminator_WP_User_Autofill_Provider
 */
class Forminator_WP_User_Autofill_Provider extends Forminator_Autofill_Provider_Abstract {

	/**
	 * Slug
	 *
	 * @var string
	 */
	protected $_slug = 'wp_user';

	/**
	 * Name
	 *
	 * @var string
	 */
	protected $_name = 'WordPress User';

	/**
	 * Short name
	 *
	 * @var string
	 */
	protected $_short_name = 'WP User';

	/**
	 * WP User
	 *
	 * @var WP_User
	 */
	private $wp_user;

	/**
	 * Forminator_WP_User_Autofill_Provider Instance
	 *
	 * @var self|null
	 */
	private static $_instance = null;

	/**
	 * Get instance
	 *
	 * @return Forminator_Autofill_Provider_Interface|Forminator_WP_User_Autofill_Provider|null
	 */
	public static function get_instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Forminator_WP_User_Autofill_Provider constructor.
	 */
	public function __construct() {
		$attributes_map = array(
			'display_name' => array(
				'name'         => esc_html__( 'Display Name', 'forminator' ),
				'value_getter' => array( $this, 'get_value_display_name' ),
			),
			'first_name'   => array(
				'name'         => esc_html__( 'First Name', 'forminator' ),
				'value_getter' => array( $this, 'get_value_firstname' ),
			),
			'last_name'    => array(
				'name'         => esc_html__( 'Last Name', 'forminator' ),
				'value_getter' => array( $this, 'get_value_lastname' ),
			),
			'description'  => array(
				'name'         => esc_html__( 'Description', 'forminator' ),
				'value_getter' => array( $this, 'get_value_description' ),
			),
			'email'        => array(
				'name'         => esc_html__( 'Email', 'forminator' ),
				'value_getter' => array( $this, 'get_value_email' ),
			),
			'login'        => array(
				'name'         => esc_html__( 'Username', 'forminator' ),
				'value_getter' => array( $this, 'get_value_login' ),
			),
		);

		$this->attributes_map = $attributes_map;

		$this->hook_to_fields();
	}


	/**
	 * Check if autofill provider can be enabled
	 *
	 * @example check settings
	 *          when its false, it wont show up on select autofill value of form setting
	 *
	 * @return bool
	 */
	public function is_enabled() {
		// no prequisities / requirements.
		return true;
	}

	/**
	 * Init your fillable data here, like feching data from your server or database, etc
	 */
	public function init() {
		$this->wp_user = wp_get_current_user();
	}

	/**
	 * Check if its fillable
	 *
	 * @example when wp_get_curent_user failed, then it shouldn't be fillable
	 *
	 * @return bool
	 */
	public function is_fillable() {
		if ( ! $this->wp_user instanceof WP_User ) {
			return false;
		}

		return true;
	}

	/**
	 * Define what field to be hooked and what attribute will be used as auto fill provider
	 *
	 * @example {
	 *  'FIELD_TYPE_TO_HOOK' => [
	 *          'PROVIDER_SLUG.ATTRIBUTE_PROVIDER_KEY'
	 *              ],
	 *   'text' => [
	 *          // you can add multiple here.
	 *          // or you can add other provider too! simply by knowing its slug and attribute key.
	 *          'simple.simple_text',
	 *              ],
	 *    'number' => [
	 *          'simple.simple_number',
	 *              ]
	 *
	 *
	 * ...}
	 * @return array
	 */
	public function get_attribute_to_hook() {
		return array(
			'name'            => array(
				'wp_user.display_name',
				'wp_user.login',
				'wp_user.first_name',
				'wp_user.last_name',
			),
			'name_first_name' => array(
				'wp_user.first_name',
				'wp_user.display_name',
				'wp_user.login',
			),
			'name_last_name'  => array(
				'wp_user.last_name',
				'wp_user.login',
			),
			'text'            => array(
				'wp_user.display_name',
				'wp_user.first_name',
				'wp_user.last_name',
				'wp_user.login',
				'wp_user.description',
			),
			'email'           => array(
				'wp_user.email',
			),
		);
	}

	/**
	 * Get user Description
	 *
	 * @return string
	 */
	public function get_value_description() {
		return $this->wp_user->user_description;
	}

	/**
	 * Get username
	 *
	 * @return string
	 */
	public function get_value_login() {
		return $this->wp_user->user_login;
	}

	/**
	 * Get user mail
	 *
	 * @return string
	 */
	public function get_value_email() {
		return $this->wp_user->user_email;
	}

	/**
	 * Get firstname
	 *
	 * @return string
	 */
	public function get_value_firstname() {
		return $this->wp_user->user_firstname;
	}

	/**
	 * Get lastname
	 *
	 * @return string
	 */
	public function get_value_lastname() {
		return $this->wp_user->user_lastname;
	}

	/**
	 * Get nice name
	 *
	 * @return string
	 */
	public function get_value_nicename() {
		return $this->wp_user->user_nicename;
	}

	/**
	 * Get user url
	 *
	 * @return string
	 */
	public function get_value_url() {
		return $this->wp_user->user_url;
	}

	/**
	 * Get display name
	 *
	 * @return string
	 */
	public function get_value_display_name() {
		return $this->wp_user->display_name;
	}
}
