<?php
/**
 * Template for wait authorize
 *
 * @package Forminator
 */

// defaults.
$vars = array(
	'connected_account' => array(),
	'auth_url'          => '',
	'token'             => '',
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
		<?php esc_html_e( 'Waiting...', 'forminator' ); ?>
	</h3>

</div>

<p id="forminator-integration-popup__description" class="sui-description" style="text-align: center;"><?php esc_html_e( 'We are waiting for authorization from Trello.', 'forminator' ); ?></p>

<?php if ( empty( $vars['token'] ) ) : ?>
	<div class="forminator-integration-popup__footer-temp">
		<a href="<?php echo esc_attr( $vars['auth_url'] ); ?>" target="_blank" class="sui-button forminator-integration-popup__close disable-loader"><?php esc_html_e( 'Retry', 'forminator' ); ?></a>
	</div>
<?php endif ?>
