<?php
/**
 * Template admin/views/settings/tab-data.php
 *
 * @package Forminator
 */

$section                  = Forminator_Core::sanitize_text_field( 'section', 'dashboard' );
$nonce                    = wp_create_nonce( 'forminator_save_popup_uninstall_settings' );
$forminator_uninstall     = get_option( 'forminator_uninstall_clear_data', false );
$forminator_custom_upload = get_option( 'forminator_custom_upload', false );
$upload_root              = get_option( 'forminator_custom_upload_root' );
$upload_root              = ! empty( $upload_root ) ? basename( $upload_root ) : 'forminator';
?>

<div class="sui-box" data-nav="data" style="<?php echo esc_attr( 'data' !== $section ? 'display: none;' : '' ); ?>">

	<div class="sui-box-header">
		<h2 class="sui-box-title"><?php esc_html_e( 'Data', 'forminator' ); ?></h2>
	</div>

	<form class="forminator-settings-save" action="">

		<div class="sui-box-body">

			<?php if ( is_main_site() ) : ?>

				<div class="sui-box-settings-row">

					<div class="sui-box-settings-col-1">
						<span class="sui-settings-label"><?php esc_html_e( 'Uninstallation', 'forminator' ); ?></span>
						<span class="sui-description"><?php esc_html_e( 'When you uninstall this plugin, what do you want to do with your plugin\'s settings and data?', 'forminator' ); ?></span>
					</div>

					<div class="sui-box-settings-col-2">
						<div class="sui-side-tabs">

							<div class="sui-tabs-menu">

								<label for="delete_uninstall-false" class="sui-tab-item<?php echo $forminator_uninstall ? '' : ' active'; ?>">
									<input type="radio"
										name="delete_uninstall"
										value="false"
										id="delete_uninstall-false"
										<?php echo esc_attr( checked( $forminator_uninstall, false ) ); ?> />
									<?php esc_html_e( 'Preserve', 'forminator' ); ?>
								</label>

								<label for="delete_uninstall-true" class="sui-tab-item<?php echo $forminator_uninstall ? ' active' : ''; ?>">
									<input type="radio"
										name="delete_uninstall"
										value="true"
										id="delete_uninstall-true"
										<?php echo esc_attr( checked( $forminator_uninstall, true ) ); ?> />
									<?php esc_html_e( 'Reset', 'forminator' ); ?>
								</label>

							</div>

						</div>
					</div>

				</div>

			<?php endif; ?>

			<div class="sui-box-settings-row">

				<div class="sui-box-settings-col-1">
					<span class="sui-settings-label"><?php esc_html_e( 'File Upload Storage', 'forminator' ); ?></span>
					<span class="sui-description">
						<?php
						printf(
						/* Translators: 1. Opening <strong> tag, 2. closing <strong> tag. */
							esc_html__( 'For security reasons, we store all the file uploads of your forms with random names under the designated subdirectories of the “%1$s/wp-content/uploads/forminator/%2$s” directory. You can also specify a custom storage directory under the custom tab.', 'forminator' ),
							'<strong>',
							'</strong>'
						);
						?>
					</span>
				</div>
				<div class="sui-box-settings-col-2">
					<div class="sui-side-tabs" style="margin-top: 10px;">

						<div class="sui-tabs-menu">

							<label for="custom_upload-false" class="sui-tab-item<?php echo( $forminator_custom_upload ? '' : ' active' ); ?>">
								<input type="radio"
										name="custom_upload"
										value="false"
										id="custom_upload-false"
									<?php echo esc_attr( checked( $forminator_custom_upload, false ) ); ?> />
								<?php esc_html_e( 'Default', 'forminator' ); ?>
							</label>

							<label for="custom_upload-true" class="sui-tab-item<?php echo( $forminator_custom_upload ? ' active' : '' ); ?>">
								<input type="radio"
										name="custom_upload"
										value="true"
										id="custom_upload-true"
										data-tab-menu="forminator-custom_upload-true"
									<?php echo esc_attr( checked( $forminator_custom_upload, true ) ); ?> />
								<?php esc_html_e( 'Custom', 'forminator' ); ?>
							</label>

						</div>

						<div class="sui-tabs-content">

							<div data-tab-content="forminator-custom_upload-true" class="sui-tab-content sui-tab-boxed<?php echo( $forminator_custom_upload ? ' active' : '' ); ?>">
								<div class="sui-form-field">
									<label for="forminator-field-custom_upload_root" class="sui-field-prefix">
										<?php esc_html_e( 'Enter a custom directory to store uploaded files', 'forminator' ); ?>
									</label>
									<div class="sui-control-with-icon">
										<span class="sui-icon-folder" aria-hidden="true"></span>
										<input type="text"
											id="forminator-field-custom_upload_root"
											name="custom_upload_root"
											value="<?php echo esc_html( $upload_root ); ?>"
											class="sui-form-control forminator-custom-directory-value"
										/>
									</div>
									<span class="sui-description">
										<strong><?php esc_html_e( 'Path: ', 'forminator' ); ?></strong>
										<span class="forminator-custom-directory"><?php esc_html_e( '/wp-content/uploads/', 'forminator' ); ?><strong><?php echo esc_html( $upload_root ); ?></strong></span>
									</span>
								</div>

							</div>

						</div>
					</div>
				</div>
			</div>

			<div class="sui-box-settings-row">

				<div class="sui-box-settings-col-1">
					<span class="sui-settings-label"><?php esc_html_e( 'Reset Plugin', 'forminator' ); ?></span>
					<span class="sui-description"><?php esc_html_e( 'Needing to start fresh? Use this setting to roll back to the default plugin state.', 'forminator' ); ?></span>
				</div>

				<div class="sui-box-settings-col-2">
					<button
							class="sui-button sui-button-ghost wpmudev-open-modal"
							data-modal="reset-plugin-settings"
							data-modal-title="<?php esc_attr_e( 'Reset Plugin', 'forminator' ); ?>"
							data-modal-content="<?php esc_attr_e( 'Are you sure you want to reset the plugin to its default state?', 'forminator' ); ?>"
							data-nonce="<?php echo esc_attr( wp_create_nonce( 'forminatorSettingsRequest' ) ); ?>"
					>

						<span class="sui-loading-text">
							<i class="sui-icon-refresh"></i> <?php esc_html_e( 'RESET', 'forminator' ); ?>
						</span>
						<i class="sui-icon-loader sui-loading" aria-hidden="true"></i>

					</button>
					<span class="sui-description">
						<?php
						esc_html_e(
							'Note: This will delete all the form/polls/quizzes you currently have and revert all settings to their default state.',
							'forminator'
						);
						?>
					</span>
				</div>

			</div>

		</div>

		<div class="sui-box-footer">

			<div class="sui-actions-right">

				<button class="sui-button sui-button-blue wpmudev-action-done" data-title="<?php esc_attr_e( 'Data settings', 'forminator' ); ?>" data-action="uninstall_settings"
						data-nonce="<?php echo esc_attr( $nonce ); ?>">
					<span class="sui-loading-text"><?php esc_html_e( 'Save Settings', 'forminator' ); ?></span>
					<i class="sui-icon-loader sui-loading" aria-hidden="true"></i>
				</button>

			</div>

		</div>

	</form>

</div>
