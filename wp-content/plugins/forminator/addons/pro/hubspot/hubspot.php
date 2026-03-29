<?php
/**
 * Integration Name: HubSpot
 * Version: 1.0
 * Plugin URI:  https://wpmudev.com/
 * Description: Integrate Forminator Custom Forms with HubSpot to get notified in real time.
 * Author: WPMU DEV
 * Author URI: http://wpmudev.com
 *
 * @package Forminator
 */

define( 'FORMINATOR_ADDON_HUBSPOT_VERSION', '1.0' );

/**
 * Forminator addon hubspot directory path
 *
 * @return string
 */
function forminator_addon_hubspot_dir() {
	return trailingslashit( __DIR__ );
}

Forminator_Integration_Loader::get_instance()->register( 'hubspot' );
