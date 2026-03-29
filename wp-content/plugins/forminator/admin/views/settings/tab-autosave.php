<?php
/**
 * Template admin/views/settings/tab-autosave.php
 *
 * @package Forminator
 */

// Whether autosave is enabled for builders.
$forminator_auto_saving = get_option( 'forminator_auto_saving', true );
?>
<div id="forminator-section-settings-autosave" class="sui-box-settings-row">

	<div class="sui-box-settings-col-1">
		<span class="sui-settings-label">
			<?php esc_html_e( 'Autosave', 'forminator' ); ?>&nbsp;
		</span>
		<span class="sui-description">
			<?php
				esc_html_e( 'Automatically save changes in the editor while making changes.', 'forminator' );
			?>
		</span>
	</div>

	<div class="sui-box-settings-col-2">

		<label for="forminator-auto-saving" class="sui-toggle">
			<input type="checkbox"
				name="auto_saving"
				value="true"
				id="forminator-auto-saving" <?php checked( $forminator_auto_saving, 1 ); ?>/>
			<span class="sui-toggle-slider" aria-hidden="true"></span>
			<span class="sui-toggle-label"><?php esc_html_e( 'Enable autosave option', 'forminator' ); ?></span>
		</label>

	</div>

</div>
