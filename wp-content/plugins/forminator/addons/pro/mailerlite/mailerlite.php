<?php
/**
 * Integration Name: MailerLite
 * Version: 1.0
 * Plugin URI:  https://wpmudev.com/
 * Description: Integrate Forminator Custom Forms with MailerLite to get notified in real time.
 * Author: WPMU DEV
 * Author URI: http://wpmudev.com
 *
 * @package Forminator
 */

define( 'FORMINATOR_ADDON_MAILERLITE_VERSION', '1.0' );

require_once __DIR__ . '/lib/class-forminator-addon-mailerlite-wp-api.php';

Forminator_Integration_Loader::get_instance()->register( 'mailerlite' );
