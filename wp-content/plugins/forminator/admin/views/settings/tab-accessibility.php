<?php
/**
 * Template admin/views/settings/tab-accessibility.php
 *
 * @package Forminator
 */

$section               = Forminator_Core::sanitize_text_field( 'section', 'dashboard' );
$nonce                 = wp_create_nonce( 'forminator_save_accessibility_settings' );
$accessibility_enabled = get_option( 'forminator_enable_accessibility', false );
$accessibility_enabled = filter_var( $accessibility_enabled, FILTER_VALIDATE_BOOLEAN );

?>

<div class="sui-box" data-nav="accessibility" style="<?php echo esc_attr( 'accessibility' !== $section ? 'display: none;' : '' ); ?>">

	<div class="sui-box-header">
		<h2 class="sui-box-title"><?php esc_html_e( 'Accessibility', 'forminator' ); ?></h2>
	</div>

	<form class="forminator-settings-save" action="">

		<div class="sui-box-body">

			<div class="sui-box-settings-row">
				<p><?php esc_html_e( 'Enable support for any accessibility enhancements available in the plugin interface.', 'forminator' ); ?></p>
			</div>
			<div class="sui-box-settings-row">

				<div class="sui-box-settings-col-1">
					<span class="sui-settings-label"><?php esc_html_e( 'High Contrast Mode', 'forminator' ); ?></span>
					<span class="sui-description"><?php esc_html_e( 'Increase the visibility and accessibility of elements and components to meet WCAG AAA requirements.', 'forminator' ); ?></span>
				</div>

				<div class="sui-box-settings-col-2">

					<div class="sui-form-field">

						<label for="forminator-color-accessibility" class="sui-toggle">
							<input type="checkbox"
								name="enable_accessibility"
								value="true"
								id="forminator-color-accessibility" <?php checked( $accessibility_enabled ); ?>/>
							<span class="sui-toggle-slider" aria-hidden="true"></span>
							<span class="sui-toggle-label"><?php esc_html_e( 'Enable high contrast mode', 'forminator' ); ?></span>
						</label>

					</div>

				</div>

			</div>

		</div>

		<div class="sui-box-footer">

			<div class="sui-actions-right">

				<button class="sui-button sui-button-blue wpmudev-action-done"
						data-title="<?php esc_attr_e( 'Accessibility settings', 'forminator' ); ?>"
						data-action="accessibility_settings"
						data-nonce="<?php echo esc_attr( $nonce ); ?>"
						data-is-reload="true">
					<span class="sui-loading-text"><?php esc_html_e( 'Save Settings', 'forminator' ); ?></span>
					<i class="sui-icon-loader sui-loading" aria-hidden="true"></i>
				</button>

			</div>

		</div>

	</form>

</div>
