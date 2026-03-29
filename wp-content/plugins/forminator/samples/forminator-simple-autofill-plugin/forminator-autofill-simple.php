<?php
/**
 * The Forminator_Autofill_Simple class.
 *
 * @package Forminator
 */

/* @noinspection PhpUndefinedClassInspection */
/**
 * Class Forminator_Autofill_Simple
 */
class Forminator_Autofill_Simple extends Forminator_Autofill_Provider_Abstract {

	/**
	 * Slug
	 *
	 * @var string
	 */
	protected $_slug = 'simple';

	/**
	 * Name
	 *
	 * @var string
	 */
	protected $_name = 'Simple';

	/**
	 * Short name
	 *
	 * @var string
	 */
	protected $_short_name = 'Simple';

	/**
	 * Simple data
	 *
	 * @var array
	 */
	private $my_simple_data;

	/**
	 * Forminator_Autofill_Provider_Interface
	 *
	 * @var Forminator_Autofill_Provider_Interface|Forminator_Autofill_Simple|null
	 */
	private static $_instance = null;


	/**
	 * Get instance
	 *
	 * @return Forminator_Autofill_Provider_Interface|Forminator_Autofill_Simple|null
	 */
	public static function get_instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Forminator_Autofill_Simple constructor
	 */
	public function __construct() {
		$attributes_map = array(
			'simple_attribute_text'   => array(
				'name'         => esc_html__( 'Text', 'forminator' ),
				'value_getter' => array( $this, 'get_value_simple_text' ),
			),
			'simple_attribute_number' => array(
				'name'         => esc_html__( 'Number', 'forminator' ),
				'value_getter' => array( $this, 'get_value_simple_number' ),
			),
		);

		$this->attributes_map = $attributes_map;

		// Call this to Start attaching your autofill provider to Forminator field.
		$this->hook_to_fields();
	}

	/**
	 * Define what field to be hooked and what attribute will be used as auto fill provider
	 *
	 * @example [
	 *  'FIELD_TYPE_TO_HOOK' => [
	 *          'PROVIDER_SLUG.ATTRIBUTE_PROVIDER_KEY'
	 *              ],
	 *   'text' => [
	 *          'simple.simple_text',
	 *              ],
	 *    'number' => [
	 *          'simple.simple_number',
	 *              ]
	 *
	 *
	 * ];
	 * @return array
	 */
	public function get_attribute_to_hook() {
		return array(
			'text'   => array(
				// you can add multiple here.
				// or you can add other provider too! simply by knowing its slug and attribute key.
				'simple.simple_attribute_text',
				'simple.simple_attribute_number',
			),
			'number' => array(
				// you can add multiple here.
				'simple.simple_attribute_number',
			),

		);
	}


	/**
	 * Init your fillable data here, like feching data from your server or database, etc
	 */
	public function init() {
		$this->my_simple_data = array(
			'simple_text'   => 'I am text',
			'simple_number' => 300,
		);
	}

	/**
	 * Check if autofill provider can be enabled
	 *
	 * @example check settings or domain
	 *          when its false, it wont show up on select autofill value of form setting
	 *
	 * @return bool
	 */
	public function is_enabled() {
		return true;
	}


	/**
	 * Check if its fillable
	 *
	 * @example when when get data from server failed, then it shouldn't be fillable
	 *
	 * @return bool
	 */
	public function is_fillable() {
		if ( ! empty( $this->my_simple_data ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Get value simple text
	 *
	 * @return string
	 */
	public function get_value_simple_text() {
		return $this->my_simple_data['simple_text'];
	}

	/**
	 * Get value simple number
	 *
	 * @return int
	 */
	public function get_value_simple_number() {
		return $this->my_simple_data['simple_number'];
	}
}
