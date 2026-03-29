<?php
/**
 * The Forminator_Calculator_Symbol_Operator_Division class.
 *
 * @package Forminator
 */

/**
 * Operator for mathematical division.
 * Example: "6/2" => 3, "6/0" => PHP warning
 *
 * @see     https://en.wikipedia.org/wiki/Division_(mathematics)
 */
class Forminator_Calculator_Symbol_Operator_Division extends Forminator_Calculator_Symbol_Operator_Abstract {

	/**
	 * Identifiers
	 *
	 * @var array
	 */
	protected $identifiers = array( '/' );

	/**
	 * Precedence
	 *
	 * @var int
	 */
	protected $precedence = 200;

	/**
	 * Operate
	 *
	 * @inheritdoc
	 * @param int $left_number Left number.
	 * @param int $right_number Right number.
	 */
	public function operate( $left_number, $right_number ) {
		// backward compat, PHP < 7 return false when division by zero executed.
		// PHP >= return INF and throw exception.
		if ( empty( $right_number ) ) {
			// infinite result.
			return INF;
		}

		return $left_number / $right_number;
	}
}
