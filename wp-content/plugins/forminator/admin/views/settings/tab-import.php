<?php
/**
 * Template admin/views/settings/tab-import.php
 *
 * @package Forminator
 */

?>
<?php $section = Forminator_Core::sanitize_text_field( 'section', 'dashboard' ); ?>

<div class="sui-box" data-nav="import" style="<?php echo esc_attr( 'import' !== $section ? 'display: none;' : '' ); ?>">

	<div class="sui-box-header">
		<h2 class="sui-box-title"><?php esc_html_e( 'Import', 'forminator' ); ?></h2>
	</div>

	<form class="forminator-settings-save" action="">

		<div class="sui-box-body">

			<div class="sui-box-settings-row">

				<div class="sui-box-settings-col-1">

					<h2 class="sui-settings-label"><?php esc_html_e( 'Third-Party Plugins', 'forminator' ); ?></h2>

					<p class="sui-description"><?php esc_html_e( 'Use this tool to import your existing forms from other third-party form builder plugins automatically to Forminator.', 'forminator' ); ?></p>

				</div>

				<div class="sui-box-settings-col-2">

					<div class="sui-form-field">

						<h3 class="sui-settings-label"><?php esc_html_e( 'Contact Form 7', 'forminator' ); ?></h3>

						<p class="sui-description" style="margin-bottom: 10px;"><?php esc_html_e( 'Import your existing forms and the relevant plugin settings from the Contact Form 7. The importer supports a few widely used add-ons as well.', 'forminator' ); ?></p>

						<?php if ( forminator_is_import_plugin_enabled( 'cf7' ) ) : ?>

							<button
								role="button"
								class="sui-button wpmudev-open-modal"
								data-modal="import_form_cf7"
								data-modal-title=""
								data-nonce="<?php echo esc_attr( wp_create_nonce( 'forminator_popup_import_form_cf7' ) ); ?>"
							>
								<i class="sui-icon-upload-cloud" aria-hidden="true"></i> <?php esc_html_e( 'Import', 'forminator' ); ?>
							</button>

						<?php else : ?>

							<div
								role="alert"
								class="sui-notice sui-active"
								style="display: block; text-align: left;"
								aria-live="assertive"
							>

								<div class="sui-notice-content">

									<div class="sui-notice-message">

										<span class="sui-notice-icon sui-icon-info" aria-hidden="true"></span>

										<p><?php echo esc_html__( 'Contact Form 7 plugin is not active on your website.', 'forminator' ); ?></p>

									</div>

								</div>

							</div>

						<?php endif; ?>

					</div>

					<?php if ( forminator_is_import_plugin_enabled( 'ninjaforms' ) ) : ?>

						<div class="sui-form-field">

							<h3 class="sui-settings-label"><?php esc_html_e( 'Ninja Forms', 'forminator' ); ?></h3>

							<p class="sui-description" style="margin-bottom: 10px;"><?php esc_html_e( 'Import your forms from Ninja Forms', 'forminator' ); ?></p>

							<button
								role="button"
								class="sui-button wpmudev-open-modal"
								data-modal="import_form_ninja"
								data-modal-title=""
								data-nonce="<?php echo esc_attr( wp_create_nonce( 'forminator_popup_import_form_ninjaforms' ) ); ?>"
							>
								<i class="sui-icon-upload-cloud" aria-hidden="true"></i> <?php esc_html_e( 'Import Ninja Forms', 'forminator' ); ?>
							</button>

						</div>

					<?php endif; ?>

					<?php if ( forminator_is_import_plugin_enabled( 'gravityforms' ) ) : ?>

						<div class="sui-form-field">

							<h3 class="sui-settings-label"><?php esc_html_e( 'Gravity Forms', 'forminator' ); ?></h3>

							<p class="sui-description" style="margin-bottom: 10px;"><?php esc_html_e( 'Import your forms from Gravity Forms', 'forminator' ); ?></p>

							<button
								role="button"
								class="sui-button wpmudev-open-modal"
								data-modal="import_form_gravity"
								data-modal-title=""
								data-nonce="<?php echo esc_attr( wp_create_nonce( 'forminator_popup_import_form_gravityforms' ) ); ?>"
							>
								<i class="sui-icon-upload-cloud" aria-hidden="true"></i> <?php esc_html_e( 'Import Gravity Forms', 'forminator' ); ?>
							</button>

						</div>

					<?php endif; ?>

				</div>

			</div>

		</div>

	</form>

</div>
