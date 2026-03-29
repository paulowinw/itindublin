<?php
/**
 * The Forminator_Calculator_Parser_Token class.
 *
 * @package Forminator
 */

/**
 * Class Forminator_Calculator_Token
 */
class Forminator_Calculator_Parser_Token {

	const TYPE_WORD   = 1;
	const TYPE_CHAR   = 2;
	const TYPE_NUMBER = 3;

	/**
	 * Type
	 *
	 * @var int
	 */
	public $type;

	/**
	 * Value
	 *
	 * @var string|int|float
	 */
	public $value;

	/**
	 * Position
	 *
	 * @var int
	 */
	public $position;

	/**
	 * Forminator_Calculator_Parser_Token constructor
	 *
	 * @param mixed $type Type.
	 * @param mixed $value Value.
	 * @param mixed $position Position.
	 */
	public function __construct( $type, $value, $position ) {
		$this->type     = $type;
		$this->value    = $value;
		$this->position = $position;
	}
}
