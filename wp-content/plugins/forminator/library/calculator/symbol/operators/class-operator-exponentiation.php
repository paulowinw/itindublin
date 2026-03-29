<?php
/**
 * The Forminator_Calculator_Symbol_Operator_Exponentiation class.
 *
 * @package Forminator
 */

/**
 * Operator for mathematical exponentiation.
 * Example: "3^2" => 9, "-3^2" => -9, "3^-2" equals "3^(-2)"
 *
 * @see     https://en.wikipedia.org/wiki/Exponentiation
 */
class Forminator_Calculator_Symbol_Operator_Exponentiation extends Forminator_Calculator_Symbol_Operator_Abstract {

	/**
	 * Identifiers
	 *
	 * @var array
	 */
	protected $identifiers = array( '^' );

	/**
	 * Precedence
	 *
	 * @var int
	 */
	protected $precedence = 300;

	/**
	 * Operate
	 *
	 * @inheritdoc
	 * @param int $left_number Left number.
	 * @param int $right_number Right number.
	 */
	public function operate( $left_number, $right_number ) {
		return pow( $left_number, $right_number );
	}
}
