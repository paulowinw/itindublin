<?php
/**
 * Template admin/views/settings/payments/paypal.php
 *
 * @package Forminator
 */

// defaults.
$vars = array(
	'error_message'        => '',
	'sandbox_id'           => '',
	'sandbox_id_error'     => '',
	'sandbox_secret'       => '',
	'sandbox_secret_error' => '',
	'live_id'              => '',
	'live_id_error'        => '',
	'live_secret'          => '',
	'live_secret_error'    => '',

);
/**
 * Template variables
 *
 * @var array $template_vars */
foreach ( $template_vars as $key => $val ) {
	$vars[ $key ] = $val;
}
?>

<span class="sui-description">
	<?php
	printf(
	/* Translators: 1. Opening <a> tag with link to the PayPal account, 2. closing <a> tag. */
		esc_html__( 'Enter your PayPal REST API keys to connect your account. You can create a REST API app %1$shere%2$s to grab the credentials.', 'forminator' ),
		'<a href="https://developer.paypal.com/developer/applications/" target="_blank">',
		'</a>'
	);
	?>
</span>

<?php
if ( ! empty( $vars['error_message'] ) ) {
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is already escaped.
	echo Forminator_Admin::get_red_notice( esc_html( $vars['error_message'] ) );
}
?>

<form class="sui-form-field">

	<div class="sui-form-field <?php echo esc_attr( ! empty( $vars['sandbox_id_error'] ) ? 'sui-form-field-error' : '' ); ?>">

		<label for="forminator-field-paypal-sandbox_id" class="sui-label"><?php esc_html_e( 'Sandbox Client ID', 'forminator' ); ?></label>

		<input
			id="forminator-field-paypal-sandbox_id"
			class="sui-form-control"
			name="sandbox_id" placeholder="<?php echo esc_attr__( 'Enter your sandbox client id', 'forminator' ); ?>"
			value="<?php echo esc_attr( $vars['sandbox_id'] ); ?>"
		/>
		<?php if ( ! empty( $vars['sandbox_id_error'] ) ) : ?>
			<span class="sui-error-message"><?php echo esc_html( $vars['sandbox_id_error'] ); ?></span>
		<?php endif; ?>

	</div>

	<div class="sui-form-field <?php echo esc_attr( ! empty( $vars['sandbox_secret_error'] ) ? 'sui-form-field-error' : '' ); ?>">

		<label for="forminator-field-paypal-sandbox_secret" class="sui-label"><?php esc_html_e( 'Sandbox Secret', 'forminator' ); ?></label>

		<input
			id="forminator-field-paypal-sandbox_secret"
			class="sui-form-control"
			name="sandbox_secret" placeholder="<?php echo esc_attr__( 'Enter your sandbox secret', 'forminator' ); ?>"
			value="<?php echo esc_attr( $vars['sandbox_secret'] ); ?>"
		/>

		<?php if ( ! empty( $vars['sandbox_secret_error'] ) ) : ?>
			<span class="sui-error-message"><?php echo esc_html( $vars['sandbox_secret_error'] ); ?></span>
		<?php endif; ?>

	</div>

	<div class="sui-form-field <?php echo esc_attr( ! empty( $vars['live_id_error'] ) ? 'sui-form-field-error' : '' ); ?>">

		<label for="forminator-field-paypal-live_id" class="sui-label"><?php esc_html_e( 'Live Client ID', 'forminator' ); ?></label>

		<input
			id="forminator-field-paypal-live_id"
			class="sui-form-control"
			name="live_id" placeholder="<?php echo esc_attr__( 'Enter your live client id', 'forminator' ); ?>"
			value="<?php echo esc_attr( $vars['live_id'] ); ?>"
		/>

		<?php if ( ! empty( $vars['live_id_error'] ) ) : ?>
			<span class="sui-error-message"><?php echo esc_html( $vars['live_id_error'] ); ?></span>
		<?php endif; ?>

	</div>

	<div class="sui-form-field <?php echo esc_attr( ! empty( $vars['live_secret_error'] ) ? 'sui-form-field-error' : '' ); ?>">

		<label for="forminator-field-paypal-live_secret" class="sui-label"><?php esc_html_e( 'Live Secret Key', 'forminator' ); ?></label>

		<input
			id="forminator-field-paypal-live_secret"
			class="sui-form-control"
			name="live_secret" placeholder="<?php echo esc_attr__( 'Enter your live secret id', 'forminator' ); ?>"
			value="<?php echo esc_attr( $vars['live_secret'] ); ?>"
		/>

		<?php if ( ! empty( $vars['live_secret_error'] ) ) : ?>
			<span class="sui-error-message"><?php echo esc_html( $vars['live_secret_error'] ); ?></span>
		<?php endif; ?>

	</div>

</form>
