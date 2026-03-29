<?php
/**
 * The Forminator_Calculator_Symbol_Constant_Pi class.
 *
 * @package Forminator
 */

/**
 * PHP M_PI constant
 * Value: 3.14...
 *
 * @see http://php.net/manual/en/math.constants.php
 */
class Forminator_Calculator_Symbol_Constant_Pi extends Forminator_Calculator_Symbol_Constant_Abstract {

	/**
	 * Identifiers
	 *
	 * @var array
	 */
	protected $identifiers = array( 'pi' );

	/**
	 * Value
	 *
	 * @var float
	 */
	protected $value = M_PI;
}
