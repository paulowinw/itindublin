<?php
/**
 * Template admin/views/settings/payments/section-paypal.php
 *
 * @package Forminator
 */

$plugin_url              = forminator_plugin_url();
$paypal_is_configured    = false;
$forminator_currencies   = forminator_pp_currency_list();
$paypal_default_currency = 'USD';

try {
	$paypal = new Forminator_PayPal_Express();

	$paypal_default_currency = $paypal->get_default_currency();
	if ( $paypal->is_test_ready() || $paypal->is_live_ready() ) {
		$paypal_is_configured = true;
	}
} catch ( Forminator_Gateway_Exception $e ) {
	$paypal_is_configured = false;
}

?>
<div class="sui-box-settings-col-1">

	<span class="sui-settings-label"><?php esc_html_e( 'PayPal', 'forminator' ); ?></span>

	<span class="sui-description"><?php esc_html_e( 'Use PayPal Checkout to process payments in your forms.', 'forminator' ); ?></span>

</div>

<div class="sui-box-settings-col-2">

	<span class="sui-settings-label"><?php esc_html_e( 'Authorization', 'forminator' ); ?></span>

	<span class="sui-description"><?php esc_html_e( 'Connect your PayPal business account with Forminator to use PayPal field for collecting payments in your forms.', 'forminator' ); ?></span>

	<?php if ( ! $paypal_is_configured ) { ?>

		<div class="sui-form-field" style="margin-top: 10px;">

			<button
					class="sui-button paypal-connect-modal"
					type="button"
					data-modal-image="<?php echo esc_url( $plugin_url . 'assets/images/paypal-logo.png' ); ?>"
					data-modal-image-x2="<?php echo esc_url( $plugin_url . 'assets/images/paypal-logo@2x.png' ); ?>"
					data-modal-title="<?php esc_html_e( 'Connect PayPal Account', 'forminator' ); ?>"
					data-modal-nonce="<?php echo esc_html( wp_create_nonce( 'forminator_paypal_settings_modal' ) ); ?>"
			>
				<?php esc_html_e( 'Connect To PayPal', 'forminator' ); ?>
			</button>

		</div>

	<?php } else { ?>
		<table class="sui-table" style="margin-top: 10px;">

			<thead>

			<tr>
				<th><?php esc_html_e( 'Account Type', 'forminator' ); ?></th>
				<th colspan="2"><?php esc_html_e( 'Client Id', 'forminator' ); ?></th>
			</tr>

			</thead>

			<tbody>

			<tr>
				<td class="sui-table-title"><?php esc_html_e( 'Sandbox', 'forminator' ); ?></td>
				<td colspan="2"><span style="display: block; word-break: break-all;"><?php echo esc_html( $paypal->get_sandbox_id() ); ?></span></td>
			</tr>

			<tr>
				<td class="sui-table-title"><?php esc_html_e( 'Live', 'forminator' ); ?></td>
				<td colspan="2"><span style="display: block; word-break: break-all;"><?php echo esc_html( $paypal->get_live_id() ); ?></span></td>
			</tr>

			</tbody>

			<tfoot>

			<tr>

				<td colspan="3">

					<div class="fui-buttons-alignment">

						<form class="forminator-settings-save">

							<button
									class="sui-button sui-button-ghost wpmudev-open-modal"
									data-modal="disconnect-paypal"
									data-modal-title="<?php esc_attr_e( 'Disconnect PayPal Account', 'forminator' ); ?>"
									data-modal-content="<?php esc_attr_e( 'Are you sure you want to disconnect your PayPal Account? This will affect the forms using the PayPal field.', 'forminator' ); ?>"
									data-nonce="<?php echo esc_attr( wp_create_nonce( 'forminatorSettingsRequest' ) ); ?>"
							>

									<span class="sui-loading-text">
										<?php esc_html_e( 'Disconnect', 'forminator' ); ?>
									</span>

								<i class="sui-icon-loader sui-loading" aria-hidden="true"></i>

							</button>

						</form>

						<button
								class="sui-button paypal-connect-modal"
								type="button"
								data-modal-image="<?php echo esc_url( $plugin_url . 'assets/images/paypal-logo.png' ); ?>"
								data-modal-image-x2="<?php echo esc_url( $plugin_url . 'assets/images/paypal-logo@2x.png' ); ?>"
								data-modal-title="<?php esc_html_e( 'Connect PayPal Account', 'forminator' ); ?>"
								data-modal-nonce="<?php echo esc_html( wp_create_nonce( 'forminator_paypal_settings_modal' ) ); ?>"
						>
							<?php esc_html_e( 'Configure', 'forminator' ); ?>
						</button>

					</div>

				</td>

			</tr>

			</tfoot>

		</table>

		<div class="sui-form-field">

			<label for="forminator-paypal-currency" class="sui-settings-label"><?php esc_html_e( 'Default charge currency', 'forminator' ); ?></label>

			<span class="sui-description" aria-describedby="forminator-paypal-currency"><?php esc_html_e( 'Choose the default charge currency for your PayPal payments. You can override this while setting up the PayPal field in your forms.', 'forminator' ); ?></span>

			<div style="max-width: 240px; display: block; margin-top: 10px;">

				<select class="sui-select" id="forminator-paypal-currency" name="paypal-default-currency">
					<?php foreach ( $forminator_currencies as $currency => $currency_nice ) : ?>
						<option value="<?php echo esc_attr( $currency ); ?>" <?php echo selected( $currency, $paypal_default_currency ); ?>><?php echo esc_html( $currency ); ?></option>
					<?php endforeach; ?>
				</select>

			</div>

		</div>
	<?php } ?>

</div>
