<?php
/**
 * Template admin/views/common/popup/import.php
 *
 * @package Forminator
 */

$slug  = $args['slug'];
$nonce = wp_create_nonce( 'forminator_save_import_' . $slug );
?>

<div class="sui-box-body wpmudev-popup-form">

	<div
		role="alert"
		id="wpmudev-ajax-error-placeholder"
		class="sui-notice sui-notice-error"
		aria-live="assertive"
	>
		<!-- Nothing should be placed here -->
	</div>

	<div class="sui-form-field">

		<textarea class="sui-form-control" rows="10" name="importable"></textarea>

		<span class="sui-description">
		<?php

			printf(
				/* translators: %s: Module slug */
				esc_html__( 'Paste exported %s above.', 'forminator' ),
				esc_html__( $slug, 'forminator' ) // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText
			);
			?>
		</span>

	</div>

	<div class="sui-form-field">

		<label for="change-recipients" class="sui-checkbox">

			<input
				type="checkbox"
				id="change-recipients"
				aria-labelledby="change-recipients-label"
				name="change_recipients"
				value="checked"
			/>

			<span aria-hidden="true"></span>

			<span id="change-recipients-label"><?php esc_html_e( 'Change all recipients in this form to current user email.', 'forminator' ); ?></span>

		</label>
		<?php if ( ! forminator_cloud_templates_disabled() && forminator_is_site_connected_to_hub() ) : ?>
			<label for="forminator-save-form-to-cloud" class="sui-checkbox">
				<input
					type="checkbox"
					id="forminator-save-form-to-cloud"
					aria-labelledby="forminator-save-form-to-cloud"
					name="save_to_cloud"
					value="checked"
				/>
				<span aria-hidden="true"></span>
				<span id=forminator-save-form-to-cloud-label"><?php esc_html_e( 'Save to Cloud Templates.', 'forminator' ); ?></span>
			</label>
		<?php endif; ?>

	</div>

	<?php
	if ( 'form' === $slug && forminator_is_show_documentation_link() ) {
		echo forminator_template( 'common/popup/cloud-templates-notice', array( 'slug' => $slug ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	?>

</div>

<div class="sui-box-footer">

	<button class="sui-button forminator-popup-cancel" data-a11y-dialog-hide="forminator-popup">
		<span class="sui-loading-text"><?php esc_html_e( 'Cancel', 'forminator' ); ?></span>
		<i class="sui-icon-loader sui-loading" aria-hidden="true"></i>
	</button>

	<div class="sui-actions-right">

		<button class="sui-button sui-button-primary wpmudev-action-ajax-done" data-nonce="<?php echo esc_attr( $nonce ); ?>">
			<span class="sui-loading-text"><?php esc_html_e( 'Import', 'forminator' ); ?></span>
			<i class="sui-icon-loader sui-loading" aria-hidden="true"></i>
		</button>

	</div>

</div>
