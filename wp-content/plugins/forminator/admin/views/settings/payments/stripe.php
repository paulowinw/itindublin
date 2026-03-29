<?php
/**
 * Template admin/views/settings/payments/stripe.php
 *
 * @package Forminator
 */

// defaults.
$vars = array(
	'error_message'     => '',
	'test_key'          => '',
	'test_key_error'    => '',
	'test_secret'       => '',
	'test_secret_error' => '',
	'live_key'          => '',
	'live_key_error'    => '',
	'live_secret'       => '',
	'live_secret_error' => '',
);
/**
 * Template variables
 *
 * @var array $template_vars */
foreach ( $template_vars as $key => $val ) {
	$vars[ $key ] = $val;
}
?>

<p class="sui-description" style="margin-top: 0; text-align: center;">
	<?php
	esc_html_e( 'Enter your Stripe API keys below to connect your account.', 'forminator' );

	if ( forminator_is_show_documentation_link() ) {
		printf(
			/* Translators: 1. Opening <a> tag with link Stripe Forminator App, 2. closing <a> tag, 3. Opening <a> tag with link Stripe API key. */
			' ' . esc_html__( 'Install the %1$sStripe Forminator App%2$s to get your Stripe API keys. See instructions on how to get your Stripe API keys %3$shere%2$s.', 'forminator' ),
			'<a href="https://marketplace.stripe.com/apps/forminator-stripe-app" target="_blank">',
			'</a>',
			'<a href="https://wpmudev.com/docs/wpmu-dev-plugins/forminator/#connect-to-stripe" target="_blank">'
		);
	}
	?>
</p>

<?php
if ( ! empty( $vars['error_message'] ) ) {
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is already escaped.
	echo Forminator_Admin::get_red_notice( esc_html( $vars['error_message'] ) );
}
if ( ! empty( $template_vars['has_deprecated_secret_key'] ) ) {
	?>
	<div role="alert" class="sui-notice sui-notice-yellow sui-active"
			style="display: block; text-align: left;" aria-live="assertive">
		<div class="sui-notice-content">
			<div class="sui-notice-message">
				<span class="sui-notice-icon sui-icon-info" aria-hidden="true"></span>
				<p>
					<?php
					printf(
						/* Translators: 1. Opening <b> tag, 2. closing <b> tag, 3. Opening <a> tag with link Stripe API key, 4. closing <a> tag. */
						esc_html__( '%1$sNotice%2$s: You are using the deprecated Stripe %1$sSecret Key%2$s. To avoid unexpected issues in your form, we recommend using the new Stripe %3$sRestricted API Key%4$s instead.', 'forminator' ),
						'<b>',
						'</b>',
						'<a href="' .
							( forminator_is_show_documentation_link()
								? 'https://wpmudev.com/docs/wpmu-dev-plugins/forminator/#connect-to-stripe'
								: 'https://docs.stripe.com/keys#create-restricted-api-secret-key' ) .
							'" target="_blank">',
						'</a>'
					);
					?>
				</p>
			</div>
		</div>
	</div>
	<?php
}
?>

<form class="sui-form-field">

	<div class="sui-form-field <?php echo esc_attr( ! empty( $vars['test_key_error'] ) ? 'sui-form-field-error' : '' ); ?>">

		<label for="forminator-field-stripe-test-key" class="sui-label"><?php esc_html_e( 'Test Publishable Key', 'forminator' ); ?></label>

		<input
			id="forminator-field-stripe-test-key"
			class="sui-form-control"
			name="test_key" placeholder="<?php echo esc_attr__( 'Enter your test publishable key', 'forminator' ); ?>"
			value="<?php echo esc_attr( $vars['test_key'] ); ?>"
		/>
		<?php if ( ! empty( $vars['test_key_error'] ) ) : ?>
			<span class="sui-error-message"><?php echo esc_html( $vars['test_key_error'] ); ?></span>
		<?php endif; ?>

	</div>

	<div class="sui-form-field <?php echo esc_attr( ! empty( $vars['test_secret_error'] ) ? 'sui-form-field-error' : '' ); ?>">

		<label for="forminator-field-stripe-test-secret-key" class="sui-label"><?php esc_html_e( 'Test Restricted API Key', 'forminator' ); ?></label>

		<input
			id="forminator-field-stripe-test-secret-key"
			class="sui-form-control"
			name="test_secret" placeholder="<?php echo esc_attr__( 'Enter your test restricted API key', 'forminator' ); ?>"
			value="<?php echo esc_attr( $vars['test_secret'] ); ?>"
		/>

		<?php if ( ! empty( $vars['test_secret_error'] ) ) : ?>
			<span class="sui-error-message"><?php echo esc_html( $vars['test_secret_error'] ); ?></span>
		<?php endif; ?>

	</div>

	<div class="sui-form-field <?php echo esc_attr( ! empty( $vars['live_key_error'] ) ? 'sui-form-field-error' : '' ); ?>">

		<label for="forminator-field-stripe-live-key" class="sui-label"><?php esc_html_e( 'Live Publishable Key', 'forminator' ); ?></label>

		<input
			id="forminator-field-stripe-live-key"
			class="sui-form-control"
			name="live_key" placeholder="<?php echo esc_attr__( 'Enter your live publishable key', 'forminator' ); ?>"
			value="<?php echo esc_attr( $vars['live_key'] ); ?>"
		/>

		<?php if ( ! empty( $vars['live_key_error'] ) ) : ?>
			<span class="sui-error-message"><?php echo esc_html( $vars['live_key_error'] ); ?></span>
		<?php endif; ?>

	</div>

	<div class="sui-form-field <?php echo esc_attr( ! empty( $vars['live_secret_error'] ) ? 'sui-form-field-error' : '' ); ?>">

		<label for="forminator-field-stripe-live-secret-key" class="sui-label"><?php esc_html_e( 'Live Restricted API Key', 'forminator' ); ?></label>

		<input
			id="forminator-field-stripe-live-secret-key"
			class="sui-form-control"
			name="live_secret" placeholder="<?php echo esc_attr__( 'Enter your live restricted API key', 'forminator' ); ?>"
			value="<?php echo esc_attr( $vars['live_secret'] ); ?>"
		/>

		<?php if ( ! empty( $vars['live_secret_error'] ) ) : ?>
			<span class="sui-error-message"><?php echo esc_html( $vars['live_secret_error'] ); ?></span>
		<?php endif; ?>

	</div>

</form>
