<?php
/**
 * Template for Error authorize.
 *
 * @package Forminator
 */

// defaults.
$vars = array(
	'auth_url'      => '',
	'error_message' => '',
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

	<h3 id="forminator-integration-popup__title" class="sui-box-title sui-lg" style="overflow: initial; white-space: normal; text-overflow: initial;">
		<?php
		/* translators: 1: Add-on name */
			printf( esc_html__( 'Connect %1$s', 'forminator' ), 'HubSpot' );
		?>
	</h3>

	<p id="forminator-integration-popup__description" class="sui-description">
		<?php esc_html_e( "Authenticate your HubSpot account using the button below. Note that you'll be taken to the HubSpot website to grant access to Forminator and then redirected back.", 'forminator' ); ?>
	</p>

</div>

<?php if ( ! empty( $vars['error_message'] ) ) : ?>
	<?php
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is already escaped.
		echo Forminator_Admin::get_red_notice( esc_html( $vars['error_message'] ) );
	?>
<?php endif; ?>

<div class="forminator-integration-popup__footer-temp">
	<a href="<?php echo esc_attr( $vars['auth_url'] ); ?>" target="_blank" class="sui-button sui-button-primary forminator-addon-connect forminator-integration-popup__close"><?php esc_html_e( 'Authenticate', 'forminator' ); ?></a>
</div>
