<?php
/**
 * Integration Name: Mailjet
 * Version: 1.0
 * Plugin URI:  https://wpmudev.com/
 * Description: Integrate Forminator Modules with Mailjet email list easily
 * Author: WPMU DEV
 * Author URI: http://wpmudev.com
 *
 * @package Forminator
 */

define( 'FORMINATOR_ADDON_MAILJET_VERSION', '1.0' );

Forminator_Integration_Loader::get_instance()->register( 'mailjet' );
