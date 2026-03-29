<?php
/**
 * Template for Wait authorize.
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
} ?>

<div class="forminator-integration-popup__header">

	<h3 id="forminator-integration-popup__title" class="sui-box-title sui-lg" style="overflow: initial; white-space: normal; text-overflow: initial;">
		<?php
		/* translators: 1: Add-on name */
			printf( esc_html__( 'Connect %1$s', 'forminator' ), 'HubSpot' );
		?>
	</h3>

</div>

<?php if ( ! empty( $vars['token'] ) ) : ?>
	<?php
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is already escaped.
	echo Forminator_Admin::get_green_notice(
		/* translators: 1: Add-on name */
		sprintf( esc_html__( 'Your %1$s account is already authorized.', 'forminator' ), 'HubSpot' )
	);
	?>
<?php else : ?>

	<p id="forminator-integration-popup__description" class="sui-description" style="text-align: center;">
		<?php esc_html_e( "Authenticate your HubSpot account using the button below. Note that you'll be taken to the HubSpot website to grant access to Forminator and then redirected back.", 'forminator' ); ?>
	</p>

	<div class="forminator-integration-popup__footer-temp">
		<button type="button" class="sui-button forminator-integration-popup__close">
			<span class="sui-loading-text"><?php esc_html_e( 'Authenticating', 'forminator' ); ?></span>
			<span class="sui-icon-loader sui-loading" aria-hidden="true"></span>
		</button>
	</div>

<?php endif; ?>
