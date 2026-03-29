<?php
/**
 * The Forminator_Widget functions.
 *
 * @package Forminator
 */

/**
 * Register widget
 *
 * @since 1.0
 */
function forminator_widget_register_widget() {
	register_widget( 'forminator_widget' );
}

add_action( 'widgets_init', 'forminator_widget_register_widget' );
