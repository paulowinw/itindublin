<?php
/**
 * Template admin/views/settings/tab-permissions.php
 *
 * @package Forminator
 */

	$section = Forminator_Core::sanitize_text_field( 'section', 'dashboard' );
	$style   = 'permissions' !== $section ? 'display: none;' : '';
	$nonce   = wp_create_nonce( 'forminator_save_permission_settings' );
?>

<div class="sui-box" data-nav="permissions" style="<?php echo esc_attr( $style ); ?>" id="forminator-permissions"></div>
