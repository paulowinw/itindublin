<?php
/**
 * Template admin/views/settings/tab-editor.php
 *
 * @package Forminator
 */

$forminator_editor_settings = get_option( 'forminator_editor_settings', 'true' );

?>
<div id="forminator-section-settings-form-editor" class="sui-box-settings-row">

	<div class="sui-box-settings-col-1">
		<span class="sui-settings-label"><?php esc_html_e( 'Form Editor', 'forminator' ); ?></span>
		<span class="sui-description"><?php esc_html_e( 'Customize your form editor preferences.', 'forminator' ); ?></span>
	</div>

	<div class="sui-box-settings-col-2">
		<label class="sui-settings-label"><?php esc_html_e( 'Automatically open field settings', 'forminator' ); ?></label>

		<span class="sui-description"
			style="margin-bottom: 10px;">
			<?php
			printf(
			/* Translators: 1. Opening <strong> tag, 2. closing <strong> tag. */
				esc_html__( 'Enabling this option will automatically open the field settings modal when you insert just %1$sone field%2$s at a time into your form.', 'forminator' ),
				'<strong>',
				'</strong>'
			);
			?>
		</span>

		<label for="forminator-editor-settings" class="sui-toggle">
			<input type="checkbox"
				name="editor_settings"
				value="true"
				id="forminator-editor-settings" <?php checked( $forminator_editor_settings, 'true' ); ?>/>
			<span class="sui-toggle-slider" aria-hidden="true"></span>
			<span class="sui-toggle-label"><?php esc_html_e( 'Enable', 'forminator' ); ?></span>
		</label>

	</div>

</div>
