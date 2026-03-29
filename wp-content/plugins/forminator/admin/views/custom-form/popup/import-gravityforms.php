<?php
/**
 * Template admin/views/custom-form/popup/import-gravityforms.php
 *
 * @package Forminator
 */

$nonce = wp_create_nonce( 'forminator_save_import_form_gravity' );
$forms = forminator_list_thirdparty_contact_forms( 'gravityforms' );
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
		<select class="sui-form-dropdown" name="gravityforms">
			<option value="all"><?php esc_html_e( 'All Forms', 'forminator' ); ?></option>
			<?php
			if ( ! empty( $forms ) ) :
				foreach ( $forms as $key => $value ) {
					printf(
						'<option value="%f">%s</option>',
						absint( $value['id'] ),
						esc_html( $value['title'] )
					);
				}
			endif;

			?>
		</select>

		<span class="sui-description"><?php esc_html_e( 'Select the form.', 'forminator' ); ?></span>

	</div>

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
