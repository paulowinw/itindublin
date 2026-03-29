<?php
/**
 * Plugin Name: Forminator Autofill Simple
 * Version: 1
 * Description: Simple Addon Autofill Provider.
 * Author: WPMU DEV
 * Author URI: http://wpmudev.com
 * Text Domain: forminator
 * Domain Path: /languages/
 *
 * @package Forminator
 */

add_action( 'forminator_register_autofill_provider', 'load_forminator_autofill_simple' );

/**
 * Load forminator autofill simple
 *
 * @return void
 */
function load_forminator_autofill_simple() {
	if ( class_exists( 'Forminator_Autofill_Provider_Abstract' ) ) {
		include_once plugin_dir_path( __FILE__ ) . 'forminator-autofill-simple.php';
		if ( class_exists( 'Forminator_Autofill_Loader' ) ) {
			Forminator_Autofill_Loader::get_instance()->register( 'Forminator_Autofill_Simple' );
		}
	}
}
