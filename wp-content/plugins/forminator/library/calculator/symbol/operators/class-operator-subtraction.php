<?php
/**
 * The Forminator_Calculator_Symbol_Operator_Subtraction class.
 *
 * @package Forminator
 */

/**
 * Operator for mathematical multiplication.
 * Example: "1+2" => 3
 *
 * @see     https://en.wikipedia.org/wiki/Multiplication
 */
class Forminator_Calculator_Symbol_Operator_Subtraction extends Forminator_Calculator_Symbol_Operator_Abstract {

	/**
	 * Identifiers
	 *
	 * @var array
	 */
	protected $identifiers = array( '-' );

	/**
	 * Precedence
	 *
	 * @var int
	 */
	protected $precedence = 100;

	/**
	 * Operates unary
	 *
	 * @inheritdoc
	 * Notice: The subtraction operator is unary AND binary!
	 *
	 * @var bool
	 */
	protected $operates_unary = true;

	/**
	 * Operate
	 *
	 * @inheritdoc
	 * @param int $left_number Left number.
	 * @param int $right_number Right number.
	 */
	public function operate( $left_number, $right_number ) {
		return $left_number - $right_number;
	}
}
