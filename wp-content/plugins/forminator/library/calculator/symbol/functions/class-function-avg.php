<?php
/**
 * The Forminator_Calculator_Symbol_Function_Avg class.
 *
 * @package Forminator
 */

/**
 * PHP array_sum() / count()
 * Example: "avg(2,3,4)" => 3, "abs(3,7,4)" => 5
 *
 * @see http://php.net/manual/en/function.array-sum.php
 * @see http://php.net/manual/en/function.count.php
 */
class Forminator_Calculator_Symbol_Function_Avg extends Forminator_Calculator_Symbol_Function_Abstract {

	/**
	 * Identifiers
	 *
	 * @var array
	 */
	protected $identifiers = array( 'avg' );

	/**
	 * Execute
	 *
	 * @inheritdoc
	 * @param mixed $arguments Arguments.
	 * @throws Forminator_Calculator_Exception When there is an Calculator error.
	 */
	public function execute( $arguments ) {
		if ( count( $arguments ) < 1 ) {
			throw new Forminator_Calculator_Exception( 'Error: Expected at least one argument, got ' . count( $arguments ) );
		}

		$sum = array_sum( $arguments );

		return $sum / count( $arguments );
	}
}
