<?php
/**
 * Mailer helper functions.
 *
 * @package Forminator
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Mail Helper function
 **/

/**
 * Set the message variables
 *
 * @since 1.0
 * @param string $embed_id Embed Id.
 * @param string $embed_title Embed title.
 * @param string $embed_url Embed URL.
 * @param string $user_name User name.
 * @param string $user_email User email.
 * @param string $user_login User login.
 * @param string $site_url Site URL.
 *
 * @return array
 */
function forminator_set_message_vars( $embed_id, $embed_title, $embed_url, $user_name, $user_email, $user_login, $site_url ) {
	$message_vars                    = array();
	$message_vars['user_ip']         = Forminator_Geo::get_user_ip();
	$message_vars['date_mdy']        = gmdate( 'm/d/Y' );
	$message_vars['date_dmy']        = gmdate( 'd/m/Y' );
	$message_vars['submission_time'] = date_i18n( 'g:i:s a, T', forminator_local_timestamp(), true );
	$message_vars['embed_id']        = $embed_id;
	$message_vars['embed_title']     = $embed_title;
	$message_vars['embed_url']       = $embed_url;
	$message_vars['user_agent']      = isset( $_SERVER['HTTP_USER_AGENT'] ) ? esc_html( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) ) : 'none';
	$message_vars['refer_url']       = forminator_get_referer_url( $embed_url );
	$message_vars['http_refer']      = $message_vars['refer_url'];
	$message_vars['user_name']       = $user_name;
	$message_vars['user_email']      = $user_email;
	$message_vars['user_login']      = $user_login;
	$message_vars['site_url']        = $site_url;

	return $message_vars;
}

/**
 * Get global sender email from Global Settings
 *
 * @since 1.1
 * @return string
 */
function get_global_sender_email_address() {
	$global_sender_email = get_option( 'forminator_sender_email_address', 'noreply@' . wp_parse_url( get_site_url(), PHP_URL_HOST ) );

	return apply_filters( 'forminator_sender_email_address', $global_sender_email );
}

/**
 * Get global sender name from Global Settings
 *
 * @since 1.1
 * @return string
 */
function get_global_sender_name() {
	$global_sender_email = get_option( 'forminator_sender_name', get_option( 'blogname' ) );

	return apply_filters( 'forminator_sender_name', $global_sender_email );
}
