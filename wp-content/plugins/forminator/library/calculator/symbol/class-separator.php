<?php
/**
 * The Forminator_Calculator_Symbol_Separator class.
 *
 * @package Forminator
 */

/**
 * This class is a class that represents symbols of type "separator".
 * A separator separates the arguments of a (mathematical) function.
 * Most likely we will only need one concrete "separator" class.
 */
class Forminator_Calculator_Symbol_Separator extends Forminator_Calculator_Symbol_Abstract {

	/**
	 * Identifiers
	 *
	 * @var array
	 */
	protected $identifiers = array( ',' );
}
