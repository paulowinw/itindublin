<?php
/**
 * The Activecampaign.
 *
 * @package    Forminator
 */

/**
 * Integration Name: Activecampaign
 * Version: 1.0
 * Plugin URI:  https://wpmudev.com/
 * Description: Integrate Forminator Custom Forms with Activecampaign to get notified in real time.
 * Author: WPMU DEV
 * Author URI: http://wpmudev.com
 */

define( 'FORMINATOR_ADDON_ACTIVECAMPAIGN_VERSION', '1.0' );

/**
 * Forminator addon active campaign directory.
 *
 * @return string
 */
function forminator_addon_activecampaign_dir() {
	return trailingslashit( __DIR__ );
}
Forminator_Integration_Loader::get_instance()->register( 'activecampaign' );
