<?php
/**
 * Template admin/views/settings/payments/section-stripe.php
 *
 * @package Forminator
 */

$plugin_url              = forminator_plugin_url();
$stripe_loaded           = forminator_payment_lib_stripe_version_loaded();
$stripe_is_configured    = false;
$forminator_currencies   = forminator_currency_list();
$stripe_default_currency = 'USD';

if ( $stripe_loaded ) {

	try {
		$stripe = new Forminator_Gateway_Stripe();

		$stripe_default_currency = $stripe->get_default_currency();
		if ( $stripe->is_test_ready() || $stripe->is_live_ready() ) {
			$stripe_is_configured = true;
		}
	} catch ( Forminator_Gateway_Exception $e ) {
		$stripe_is_configured = false;
	}
}
?>

<div class="sui-box-settings-col-1">

	<span class="sui-settings-label"><?php esc_html_e( 'Stripe', 'forminator' ); ?></span>

	<span class="sui-description"><?php esc_html_e( 'Use Stripe Checkout to process payments in your forms.', 'forminator' ); ?></span>

</div>

<div class="sui-box-settings-col-2">

	<?php if ( ! $stripe_loaded ) : ?>

		<div
			role="alert"
			class="sui-notice sui-notice-yellow sui-active"
			style="display: block; text-align: left;"
			aria-live="assertive"
		>

			<div class="sui-notice-content">

				<div class="sui-notice-message">

					<span class="sui-notice-icon sui-icon-info" aria-hidden="true"></span>

					<p><?php esc_html_e( 'Failed to load Stripe Library, possibly conflict with other plugins. Please contact our support .', 'forminator' ); ?></p>

				</div>

			</div>

		</div>

	<?php else : ?>

		<span class="sui-settings-label"><?php esc_html_e( 'Authorization', 'forminator' ); ?></span>

		<span class="sui-description"><?php esc_html_e( 'Connect your Stripe account with Forminator to use Stripe field for processing payments in your forms.', 'forminator' ); ?></span>

		<?php if ( ! $stripe_is_configured ) { ?>

			<div class="sui-form-field" style="margin-top: 10px;">

					<button
						class="sui-button stripe-connect-modal"
						type="button"
						data-modal-image="<?php echo esc_url( $plugin_url . 'assets/images/stripe-logo.png' ); ?>"
						data-modal-image-x2="<?php echo esc_url( $plugin_url . 'assets/images/stripe-logo@2x.png' ); ?>"
						data-modal-title="<?php esc_html_e( 'Connect Stripe Account', 'forminator' ); ?>"
						data-modal-nonce="<?php echo esc_html( wp_create_nonce( 'forminator_stripe_settings_modal' ) ); ?>"
					>
						<?php esc_html_e( 'Connect To Stripe', 'forminator' ); ?>
					</button>

			</div>

		<?php } else { ?>

			<?php
			// SETTINGS: Authorization.
			?>
			<table class="sui-table" style="margin-top: 10px;">

				<thead>

					<tr>
						<th><?php esc_html_e( 'Key Type', 'forminator' ); ?></th>
						<th colspan="2"><?php esc_html_e( 'Publishable Key', 'forminator' ); ?></th>
					</tr>

				</thead>

				<tbody>

					<tr>
						<td class="sui-table-title"><?php esc_html_e( 'Test', 'forminator' ); ?></td>
						<td colspan="2"><span style="display: block; word-break: break-all;"><?php echo esc_html( $stripe->get_test_key() ); ?></span></td>
					</tr>

					<tr>
						<td class="sui-table-title"><?php esc_html_e( 'Live', 'forminator' ); ?></td>
						<td colspan="2"><span style="display: block; word-break: break-all;"><?php echo esc_html( $stripe->get_live_key() ); ?></span></td>
					</tr>

				</tbody>

				<tfoot>

					<tr>

						<td colspan="3">

							<div class="fui-buttons-alignment">

								<form class="forminator-settings-save">

									<button
										class="sui-button sui-button-ghost wpmudev-open-modal"
										data-modal="disconnect-stripe"
										data-modal-title="<?php esc_attr_e( 'Disconnect Stripe Account', 'forminator' ); ?>"
										data-modal-content="<?php esc_attr_e( 'Are you sure you want to disconnect your Stripe Account? This will affect the forms using the Stripe field.', 'forminator' ); ?>"
										data-nonce="<?php echo esc_attr( wp_create_nonce( 'forminatorSettingsRequest' ) ); ?>"
									>

										<span class="sui-loading-text">
											<?php esc_html_e( 'Disconnect', 'forminator' ); ?>
										</span>

										<i class="sui-icon-loader sui-loading" aria-hidden="true"></i>

									</button>

								</form>

								<button
									class="sui-button stripe-connect-modal"
									type="button"
									data-modal-image="<?php echo esc_url( $plugin_url . 'assets/images/stripe-logo.png' ); ?>"
									data-modal-image-x2="<?php echo esc_url( $plugin_url . 'assets/images/stripe-logo@2x.png' ); ?>"
									data-modal-title="<?php esc_html_e( 'Connect Stripe Account', 'forminator' ); ?>"
									data-modal-nonce="<?php echo esc_html( wp_create_nonce( 'forminator_stripe_settings_modal' ) ); ?>"
								>
									<?php esc_html_e( 'Configure', 'forminator' ); ?>
								</button>

							</div>

						</td>

					</tr>

				</tfoot>

			</table>

			<?php // SETTINGS: Default Charge Currency. ?>
			<div class="sui-form-field">

				<label for="forminator-stripe-currency" class="sui-settings-label"><?php esc_html_e( 'Default charge currency', 'forminator' ); ?></label>

				<span class="sui-description" aria-describedby="forminator-stripe-currency"><?php esc_html_e( 'Choose the default charge currency for your Stripe payments. You can override this while setting up the Stripe field in your forms.', 'forminator' ); ?></span>

				<div style="max-width: 240px; display: block; margin-top: 10px;">

					<select class="sui-select" id="forminator-stripe-currency" name="stripe-default-currency">
						<?php foreach ( $forminator_currencies as $currency => $currency_nice ) : ?>
							<option value="<?php echo esc_attr( $currency ); ?>" <?php echo selected( $currency, $stripe_default_currency ); ?>><?php echo esc_html( $currency ); ?></option>
						<?php endforeach; ?>
					</select>

				</div>

			</div>

		<?php } ?>

	<?php endif; ?>

</div>
