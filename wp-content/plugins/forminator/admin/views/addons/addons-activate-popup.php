<?php
/**
 * Template admin/views/addons/addons-activate-popup.php
 *
 * @package Forminator
 */

if ( ! isset( $addons ) ) {
	return;
} ?>
<div class="sui-modal sui-modal-sm">

	<div
		role="dialog"
		id="forminator-activate-popup-<?php echo esc_attr( $addons->pid ); ?>"
		class="sui-modal-content"
		aria-modal="true"
		aria-labelledby="forminator-activate-popup-<?php echo esc_attr( $addons->pid ); ?>__title"
		aria-describedby="forminator-activate-popup-<?php echo esc_attr( $addons->pid ); ?>__description"
	>

		<div class="sui-box">

			<div class="sui-box-header sui-flatten sui-content-center sui-spacing-top--60">

				<button class="sui-button-icon sui-button-float--right" data-modal-close>
					<span class="sui-icon-close sui-md" aria-hidden="true"></span>
					<span class="sui-screen-reader-text">
						<?php esc_html_e( 'Close this modal', 'forminator' ); ?>
					</span>
				</button>

				<h3 id="forminator-activate-popup-<?php echo esc_attr( $addons->pid ); ?>__title" class="sui-box-title sui-lg" style="overflow: initial; white-space: initial; text-overflow: initial;"><?php /* translators: %s: Add-on name */ printf( esc_html__( '%s installed!', 'forminator' ), esc_html( $addons->name ) ); ?></h3>

				<p id="forminator-activate-popup-<?php echo esc_attr( $addons->pid ); ?>__description" class="sui-description" style="margin-top: 15px;"><?php esc_html_e( 'Would you like to activate it now?', 'forminator' ); ?></p>

			</div>

			<div class="sui-box-footer sui-flatten sui-content-center">

				<button
					class="sui-button addons-modal-close"
					data-addon="<?php echo esc_attr( $addons->pid ); ?>"
					data-element="forminator-activate-popup"
				>
					<?php esc_html_e( 'Close', 'forminator' ); ?>
				</button>

				<button
					class="sui-button sui-button-blue addons-actions"
					data-action="addons-activate"
					data-popup="true"
					data-nonce="<?php echo esc_attr( wp_create_nonce( 'forminator_popup_addons_actions' ) ); ?>"
					data-addon="<?php echo esc_attr( $addons->pid ); ?>"
				>
					<span class="sui-loading-text">
						<?php esc_html_e( 'Activate', 'forminator' ); ?>
					</span>
					<span class="sui-icon-loader sui-loading" aria-hidden="true"></span>
				</button>

			</div>

		</div>

	</div>

</div>
