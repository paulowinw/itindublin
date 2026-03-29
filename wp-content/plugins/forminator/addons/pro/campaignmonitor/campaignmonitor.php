<?php
/**
 * Integration Name: Campaignmonitor
 * Version: 1.0
 * Plugin URI:  https://wpmudev.com/
 * Description: Integrate Forminator Custom Forms with Campaignmonitor to get notified in real time.
 * Author: WPMU DEV
 * Author URI: http://wpmudev.com
 *
 * @package Forminator
 */

define( 'FORMINATOR_ADDON_CAMPAIGNMONITOR_VERSION', '1.0' );

/**
 * Forminator addon campaign monitor directory.
 *
 * @return string
 */
function forminator_addon_campaignmonitor_dir() {
	return trailingslashit( __DIR__ );
}

Forminator_Integration_Loader::get_instance()->register( 'campaignmonitor' );
