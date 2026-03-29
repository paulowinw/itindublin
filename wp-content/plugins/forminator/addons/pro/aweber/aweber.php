<?php
/**
 * Integration Name: Aweber
 * Version: 1.0
 * Plugin URI:  https://wpmudev.com/
 * Description: Integrate Forminator Custom Forms with Aweber to get notified in real time.
 * Author: WPMU DEV
 * Author URI: http://wpmudev.com
 *
 * @package Forminator
 */

define( 'FORMINATOR_ADDON_AWEBER_VERSION', '1.0' );

/**
 * Addon aweber directory path
 *
 * @return string
 */
function forminator_addon_aweber_dir() {
	return trailingslashit( __DIR__ );
}

Forminator_Integration_Loader::get_instance()->register( 'aweber' );
