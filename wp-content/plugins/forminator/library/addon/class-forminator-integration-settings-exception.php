<?php
/**
 * Class Forminator_Integration_Settings_Exception
 * Exception used for Addon Settings
 *
 * @package Forminator
 * @author Addons
 * @version test
 */

/**
 * Class Forminator_Integration_Settings_Exception
 */
class Forminator_Integration_Settings_Exception extends Forminator_Integration_Exception {

	/**
	 * Holder of input exceptions
	 *
	 * @var array
	 */
	protected $input_exceptions = array();

	/**
	 * Forminator_Integration_Settings_Exception constructor.
	 *
	 * Useful if input_id is needed for later.
	 * If no input_id needed, use @see Forminator_Integration_Exception
	 *
	 * @param string $message Message.
	 * @param string $input_id Input Id.
	 */
	public function __construct( $message = '', $input_id = '' ) {
		parent::__construct( $message, 0 );
		if ( ! empty( $input_id ) ) {
			$this->add_input_exception( $message, $input_id );
		}
	}

	/**
	 * Set exception message for an input
	 *
	 * @param string $message Message.
	 * @param string $input_id Input Id.
	 */
	public function add_input_exception( $message, $input_id ) {
		$this->input_exceptions[ $input_id ] = $message;
	}

	/**
	 * Set exception message for an address input
	 *
	 * @param string $message Message.
	 * @param string $input_id Input Id.
	 * @param string $sub_input Input.
	 */
	public function add_sub_input_exception( $message, $input_id, $sub_input ) {
		$this->input_exceptions[ $input_id ][ $sub_input ] = $message;
	}

	/**
	 * Get all input exceptions
	 *
	 * @return array
	 */
	public function get_input_exceptions() {
		return $this->input_exceptions;
	}

	/**
	 * Check if there is input_exceptions_is_available
	 *
	 * @return bool
	 */
	public function input_exceptions_is_available() {
		return count( $this->input_exceptions ) > 0;
	}
}
