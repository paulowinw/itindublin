<?php
/**
 * Integration Name: Google Sheets
 * Version: 1.1
 * Plugin URI:  https://wpmudev.com/
 * Description: Integrate Forminator Custom Forms and Polls with Google Sheets to get notified in real time.
 * Author: WPMU DEV
 * Author URI: http://wpmudev.com
 *
 * @package Forminator
 */

define( 'FORMINATOR_ADDON_GOOGLESHEET_VERSION', '2.16.0' );

/**
 * Forminator addon Google sheet directory path
 *
 * @return string
 */
function forminator_addon_googlesheet_dir() {
	return trailingslashit( __DIR__ );
}

require_once __DIR__ . '/lib/external/vendor/autoload.php';

Forminator_Integration_Loader::get_instance()->register( 'googlesheet' );
