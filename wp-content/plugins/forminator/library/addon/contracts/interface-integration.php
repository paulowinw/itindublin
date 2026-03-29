<?php
/**
 * The Forminator_Integration_Interface class.
 *
 * @package Forminator
 */

/**
 * Interface Forminator_Integration_Interface
 *
 * @since 1.1
 */
interface Forminator_Integration_Interface {
	const SHORT_TITLE_MAX_LENGTH = 10;

	/**
	 * Action to execute on activation
	 *
	 * @since 1.1
	 * @return bool
	 */
	public function activate();

	/**
	 * Action to execute on de-activation
	 *
	 * @since 1.1
	 * @return bool
	 */
	public function deactivate();
}
