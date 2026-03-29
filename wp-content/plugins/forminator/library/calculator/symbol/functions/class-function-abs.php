<?php
/**
 * The Forminator_Calculator_Symbol_Function_Abs class.
 *
 * @package Forminator
 */

/**
 * PHP abs() function. Expects one parameter.
 * Example: "abs(2)" => 2, "abs(-2)" => 2, "abs(0)" => 0
 *
 * @see http://php.net/manual/en/ref.math.php
 */
class Forminator_Calculator_Symbol_Function_Abs extends Forminator_Calculator_Symbol_Function_Abstract {

	/**
	 * Identifiers
	 *
	 * @var array
	 */
	protected $identifiers = array( 'abs' );

	/**
	 * Execute
	 *
	 * @inheritdoc
	 * @param mixed $arguments Arguments.
	 * @throws Forminator_Calculator_Exception When there is an Calculator error.
	 */
	public function execute( $arguments ) {
		if ( count( $arguments ) !== 1 ) {
			throw new Forminator_Calculator_Exception( 'Error: Expected one argument, got ' . count( $arguments ) );
		}

		$number = $arguments[0];

		return abs( $number );
	}
}
