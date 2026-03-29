<?php
/**
 * The Forminator_Integration_Exception class.
 *
 * @package Forminator
 */

/**
 * Class Forminator_Integration_Exception
 * Exception used for Integration
 *
 * @since 1.1
 */
class Forminator_Integration_Exception extends Exception {

	/**
	 * Get error notice HTML
	 *
	 * @return string
	 */
	public function get_error_notice(): string {
		return Forminator_Admin::get_red_notice( esc_html( $this->getMessage() ) );
	}
}
