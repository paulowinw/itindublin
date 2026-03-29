<?php
/**
 * Template for setup worksheet.
 *
 * @package Forminator
 */

// Defaults.
$vars = array(
	'auth_url' => '',
	'token'    => '',
);

/**
 * Template variables.
 *
 * @var array $template_vars
 * */
foreach ( $template_vars as $key => $val ) {
	$vars[ $key ] = $val;
} ?>

<div class="forminator-integration-popup__header">

	<h3 id="forminator-integration-popup__title" class="sui-box-title sui-lg" style="overflow: initial; white-space: normal; text-overflow: initial;">
		<?php printf( /* translators: 1: Add-on name */ esc_html__( 'Connect %1$s', 'forminator' ), 'Google Sheets' ); ?>
	</h3>

</div>

<p id="forminator-integration-popup__description" class="sui-description" style="text-align: center;">
	<?php if ( ! empty( $vars['token'] ) ) : ?>
		<?php esc_html_e( 'Click button below to re-authorize.', 'forminator' ); ?>
	<?php else : ?>
		<?php esc_html_e( 'Authorize Forminator to connect with your Google account in order to send data from your forms.', 'forminator' ); ?>
	<?php endif; ?>
</p>

<div class="forminator-integration-popup__footer-temp">
	<?php if ( empty( $vars['token'] ) ) : ?>
		<a href="<?php echo esc_attr( $vars['auth_url'] ); ?>"
			target="_blank"
			class="sui-button sui-button-blue forminator-addon-connect forminator-integration-popup__close">
			<?php esc_html_e( 'Authorize', 'forminator' ); ?>
		</a>
	<?php else : ?>
		<a href="<?php echo esc_attr( $vars['auth_url'] ); ?>"
			target="_blank"
			class="sui-button sui-button-blue forminator-addon-connect forminator-integration-popup__close">
			<?php esc_html_e( 'Re-authorize', 'forminator' ); ?>
		</a>
	<?php endif; ?>
</div>
