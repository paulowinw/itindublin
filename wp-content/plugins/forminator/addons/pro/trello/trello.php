<?php
/**
 * Integration Name: Trello
 * Version: 1.0
 * Plugin URI:  https://wpmudev.com/
 * Description: Integrate Forminator Custom Forms with Trello to get notified in real time.
 * Author: WPMU DEV
 * Author URI: http://wpmudev.com
 *
 * @package Forminator
 */

define( 'FORMINATOR_ADDON_TRELLO_VERSION', '1.1' );

/**
 * Forminator addon trello directoty path
 *
 * @return string
 */
function forminator_addon_trello_dir() {
	return trailingslashit( __DIR__ );
}

Forminator_Integration_Loader::get_instance()->register( 'trello' );
