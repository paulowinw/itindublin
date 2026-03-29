<?php
/**
 * Template setup options
 *
 * @package Forminator
 */

$vars = array(
	'error_message'                                   => '',
	'multi_id'                                        => '',
	'resubscribe'                                     => 0,
	'resubscribe_error'                               => '',
	'restart_subscription_based_autoresponders'       => 0,
	'restart_subscription_based_autoresponders_error' => '',
	'consent_to_track'                                => 'Unchanged',
	'consent_to_track_error'                          => '',
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
		<?php esc_html_e( 'Additional Options', 'forminator' ); ?>
	</h3>

	<p id="forminator-integration-popup__description" class="sui-description">
		<?php esc_html_e( 'Configure additional options for Campaign Monitor integration.', 'forminator' ); ?>
	</p>

	<?php if ( ! empty( $vars['error_message'] ) ) : ?>
		<?php
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is already escaped.
			echo Forminator_Admin::get_red_notice( esc_html( $vars['error_message'] ) );
		?>
	<?php endif; ?>

</div>

<form>

	<div class="sui-form-field<?php echo esc_attr( ! empty( $vars['resubscribe_error'] ) ? ' sui-form-field-error' : '' ); ?>">

		<label class="sui-toggle">

			<input type="checkbox"
				name="resubscribe"
				value="1"
				id="resubscribe"
				<?php checked( true, $vars['resubscribe'] ); ?> />

			<span class="sui-toggle-slider" aria-hidden="true"></span>

			<span class="sui-toggle-label"><?php esc_html_e( 'Re-subscribe', 'forminator' ); ?></span>

		</label>

		<?php if ( ! empty( $vars['resubscribe_error'] ) ) : ?>
			<span class="sui-error-message"><?php echo esc_html( $vars['resubscribe_error'] ); ?></span>
		<?php endif; ?>

		<span class="sui-description"><?php esc_html_e( 'If the subscriber is in an inactive state or has previously been unsubscribed or added to the suppression list and you enabled Re-subscribe, they will be re-added to the list. Therefore, this options should be used with caution and only where suitable. If Re-subscribe is disabled, the subscriber will not be re-added to the active list.', 'forminator' ); ?></span>

	</div>

	<div class="sui-form-field<?php echo esc_attr( ! empty( $vars['restart_subscription_based_autoresponders_error'] ) ? ' sui-form-field-error' : '' ); ?>">

		<label class="sui-toggle">

			<input type="checkbox"
				name="restart_subscription_based_autoresponders"
				value="1"
				id="restart_subscription_based_autoresponders"
				<?php checked( true, $vars['restart_subscription_based_autoresponders'] ); ?> />

			<span class="sui-toggle-slider" aria-hidden="true"></span>

			<span class="sui-toggle-label"><?php esc_html_e( 'Restart Subscription based Autoresponders', 'forminator' ); ?></span>

		</label>

		<?php if ( ! empty( $vars['restart_subscription_based_autoresponders_error'] ) ) : ?>
			<span class="sui-error-message"><?php echo esc_html( $vars['restart_subscription_based_autoresponders_error'] ); ?></span>
		<?php endif; ?>

		<span class="sui-description"><?php esc_html_e( 'By default, resubscribed subscribers will not restart any automated workflows, but they will receive any remaining emails. However, if you enable the Restart Subscription based Autoresponders, any sequences will be restarted. Restart Subscription based Autoresponders only affects resubscribing subscribers.', 'forminator' ); ?></span>

	</div>

	<div class="sui-form-field<?php echo esc_attr( ! empty( $vars['consent_to_track_error'] ) ? ' sui-form-field-error' : '' ); ?>" style="margin-bottom: 0;">

		<label class="sui-label" for="consent_to_track"><?php esc_html_e( 'Consent to Track', 'forminator' ); ?></label>

		<?php // DEV NOTE: Select without JS. ?>
		<select name="consent_to_track" id="consent_to_track" style="max-width: none;">
			<option value="Unchanged" <?php selected( 'Unchanged', $vars['consent_to_track'] ); ?>>Unchanged</option>
			<option value="Yes" <?php selected( 'Yes', $vars['consent_to_track'] ); ?>>Yes</option>
			<option value="No" <?php selected( 'No', $vars['consent_to_track'] ); ?>>No</option>
		</select>

		<?php if ( ! empty( $vars['consent_to_track_error'] ) ) : ?>
			<span class="sui-error-message"><?php echo esc_html( $vars['consent_to_track_error'] ); ?></span>
		<?php endif; ?>

		<span class="sui-description"><?php esc_html_e( 'Whether or not the subscriber has consented to having their email opens and clicks tracked. This value applies to all subscribers with the same email address, within the same client. If an email address has no value stored for Consent to Track, it is assumed that the subscriber has given consent. You can find more information ', 'forminator' ); ?><a href="https://help.campaignmonitor.com/gdpr-faqs" target="_blank">here</a>.
		</span>
	</div>

	<input type="hidden" name="multi_id" value="<?php echo esc_attr( $vars['multi_id'] ); ?>" />

</form>
