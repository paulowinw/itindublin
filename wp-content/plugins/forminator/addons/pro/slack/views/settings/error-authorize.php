<?php
/**
 * Template for authorize
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
		printf( esc_html__( 'Failed to add %1$s', 'forminator' ), 'Slack' );
		?>
	</h3>

</div>

<?php if ( ! empty( $vars['error_message'] ) ) : ?>
	<p><?php echo esc_html( $vars['error_message'] ); ?></p>
<?php endif; ?>

<div class="forminator-integration-popup__footer-temp">
	<a href="<?php echo esc_attr( $vars['auth_url'] ); ?>" target="_blank" class="sui-button forminator-addon-connect forminator-integration-popup__close"><?php esc_html_e( 'Retry', 'forminator' ); ?></a>
</div>
