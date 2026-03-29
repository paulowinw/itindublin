<?php
/**
 * The Forminator_Calculator_Symbol_Function_Max class.
 *
 * @package Forminator
 */

/**
 * PHP max() function. Expects at least one parameter.
 * Example: "max(1,2,3)" => 3, "max(1,-1)" => 1, "max(0,0)" => 0, "max(2)" => 2
 *
 * @see http://php.net/manual/en/ref.math.php
 */
class Forminator_Calculator_Symbol_Function_Max extends Forminator_Calculator_Symbol_Function_Abstract {

	/**
	 * Identifiers
	 *
	 * @var array
	 */
	protected $identifiers = array( 'max' );

	/**
	 * Execute
	 *
	 * @inheritdoc
	 * @param mixed $arguments Arguments.
	 * @throws Forminator_Calculator_Exception When there is an Calculator error.
	 */
	public function execute( $arguments ) {
		if ( count( $arguments ) < 1 ) {
			throw new Forminator_Calculator_Exception( 'Error: Expected one argument, got ' . count( $arguments ) );
		}

		$max = max( $arguments );

		return $max;
	}
}
