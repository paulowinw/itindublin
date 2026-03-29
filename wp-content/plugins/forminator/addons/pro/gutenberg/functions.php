<?php
/**
 * Forminator Gutenberg functions.
 *
 * @package Forminator
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Instance of Gutenberb Integration
 *
 * @since 1.0 Gutenberg Integration
 *
 * @return Forminator_Gutenberg
 */
function forminator_gutenberg() {
	return Forminator_Gutenberg::get_instance();
}
