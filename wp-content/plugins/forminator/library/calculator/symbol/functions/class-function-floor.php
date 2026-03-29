<?php
/**
 * The Forminator_Calculator_Symbol_Function_Floor class.
 *
 * @package Forminator
 */

/**
 * PHP floor() function aka round fractions down.
 * Expects one parameter.
 *
 * @see http://php.net/manual/en/ref.math.php
 */
class Forminator_Calculator_Symbol_Function_Floor extends Forminator_Calculator_Symbol_Function_Abstract {

	/**
	 * Identifiers
	 *
	 * @var array
	 */
	protected $identifiers = array( 'floor' );

	/**
	 * Execute
	 *
	 * @inheritdoc
	 * @param mixed $arguments Arguments.
	 * @throws Forminator_Calculator_Exception When there is an Calculator error.
	 */
	public function execute( $arguments ) {
		if ( 1 !== count( $arguments ) ) {
			throw new Forminator_Calculator_Exception( 'Error: Expected one argument, got ' . count( $arguments ) );
		}

		$number = $arguments[0];

		return floor( $number );
	}
}
