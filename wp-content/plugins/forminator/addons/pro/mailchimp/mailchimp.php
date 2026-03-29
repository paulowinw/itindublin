<?php
/**
 * Integration Name: Mailchimp
 * Version: 1.0
 * Plugin URI:  https://wpmudev.com/
 * Description: Integrate Forminator Custom Forms with Mailchimp email list easily
 * Author: WPMU DEV
 * Author URI: http://wpmudev.com
 *
 * @package Forminator
 */

define( 'FORMINATOR_ADDON_MAILCHIMP_VERSION', '1.0' );

Forminator_Integration_Loader::get_instance()->register( 'mailchimp' );
