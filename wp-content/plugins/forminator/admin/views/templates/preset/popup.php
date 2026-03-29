<?php
/**
 * Template admin/views/templates/preset/popup.php
 *
 * @package Forminator
 */

?>
<div class="sui-modal sui-modal-xl">
	<div
		role="dialog"
		id="forminator-modal-template-preview"
		class="sui-modal-content"
		aria-modal="true"
		aria-labelledby="forminator-modal-template-preview__title"
		aria-describedby="forminator-modal-template-preview__description"
	>
		<div class="sui-box">
			<div class="sui-box-header">
				<h3 id="forminator-popup__title" class="sui-box-title">
				</h3>
				<button class="sui-button-icon sui-button-float--right forminator-popup-close" data-modal-close>
					<span class="sui-icon-close sui-md" aria-hidden="true"></span>
					<span class="sui-screen-reader-text"><?php esc_html_e( 'Close', 'forminator' ); ?></span>
				</button>
			</div>
			<div class="sui-box-body sui-content-center forminator-preview-image">
				<img src=""
					alt="<?php esc_html_e( 'Preview template', 'forminator' ); ?>"
					class="sui-image"
					aria-hidden="true"
				/>
			</div>
			<div class="sui-box-footer">
				<button class="sui-button sui-button-ghost" data-modal-close>
					<?php esc_html_e( 'Close', 'forminator' ); ?>
				</button>
				<div class="sui-actions-right"></div>
			</div>
		</div>
	</div><!-- END .sui-modal-content -->
</div><!-- END .sui-modal -->