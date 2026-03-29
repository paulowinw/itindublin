<?php
/**
 * Template for setup worksheet.
 *
 * @package Forminator
 */

// defaults.
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
}
?>

<div class="forminator-integration-popup__header">

	<p class="sui-description" style="margin-bottom: 10px;" aria-hidden="true">
		<span class="sui-icon-loader sui-md sui-loading"></span>
	</p>

	<h3 id="forminator-integration-popup__title" class="sui-box-title sui-lg" style="overflow: initial; white-space: normal; text-overflow: initial;">
		<?php esc_html_e( 'Waiting', 'forminator' ); ?>
	</h3>

</div>

<p class="sui-description" style="text-align: center;"><?php esc_html_e( 'We are waiting for authorization from Google...', 'forminator' ); ?></p>

<div class="forminator-integration-popup__footer-temp">
	<a href="<?php echo esc_attr( $vars['auth_url'] ); ?>" target="_blank" class="sui-button forminator-integration-popup__close"><?php esc_html_e( 'Retry', 'forminator' ); ?></a>
</div>
