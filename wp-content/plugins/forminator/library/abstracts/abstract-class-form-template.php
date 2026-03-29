<?php
/**
 * The Forminator Template.
 *
 * @package Forminator
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_Module
 *
 * Abstract class for modules
 *
 * @property array fields
 * @property array settings
 * @since 1.0
 */
abstract class Forminator_Template {
	/**
	 * Template fields
	 *
	 * @var array
	 */
	public $fields;

	/**
	 * Template settings
	 *
	 * @var array
	 */
	public $settings;

	/**
	 * Template options
	 *
	 * @var array
	 */
	public $options = array();

	/**
	 * Forminator_Template constructor
	 */
	public function __construct() {
		$this->fields   = $this->fields();
		$this->settings = $this->settings();
		$this->options  = $this->defaults();
	}

	/**
	 * Fields
	 *
	 * @since 1.0
	 * @return array
	 */
	public function fields() {
		return array();
	}

	/**
	 * Settings
	 *
	 * @since 1.0
	 * @return array
	 */
	public function settings() {
		return array();
	}

	/**
	 * Defaults
	 *
	 * @since 1.0
	 * @return array
	 */
	public function defaults() {
		return array();
	}

	/**
	 * Get specific option from module options
	 *
	 * @since 1.0
	 * @param string $option Option key.
	 * @param string $default_value Default value.
	 *
	 * @return mixed|string
	 */
	public function get_option( $option, $default_value = '' ) {
		if ( isset( $this->options[ $option ] ) ) {
			return $this->options[ $option ];
		}
		return $default_value;
	}
}
