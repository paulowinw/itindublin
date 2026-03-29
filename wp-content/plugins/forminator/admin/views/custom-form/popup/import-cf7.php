<?php
/**
 * Template admin/views/custom-form/popup/import-cf7.php
 *
 * @package Forminator
 */

$is_addons = false;
$nonce     = wp_create_nonce( 'forminator_save_import_form_cf7' );
$forms     = forminator_list_thirdparty_contact_forms( 'cf7' );
?>

<div class="forminator-cf7-import">

	<form class="forminator-cf7-import-form" method="post">

		<input type="hidden" name="action" value="forminator_save_import_form_cf7_popup" />
		<input type="hidden" name="_ajax_nonce" value="<?php echo esc_attr( $nonce ); ?>" />

		<div class="sui-box-body wpmudev-popup-form">

			<div
				role="alert"
				id="wpmudev-ajax-error-placeholder"
				class="sui-notice sui-notice-error"
				aria-live="assertive"
			>
				<!-- Nothing should be placed here -->
			</div>

			<?php // ROW: Forms. ?>
			<div class="sui-box-settings-row">

				<div class="sui-box-settings-col-2">

					<h4 class="sui-settings-label sui-dark"><?php esc_html_e( 'Forms', 'forminator' ); ?></h4>

					<p class="sui-description" style="margin-bottom: 10px;"><?php /* Translators: 1. Opening <a> tag with # href, 2. closing <a> tag. */ printf( esc_html__( "Choose the forms you'd like to import from the Contact Form 7 plugin. Note that we will strip off any %1\$sunsupported form fields and settings%2\$s during the import.", 'forminator' ), '<a href="#" class="forminator-toggle-unsupported-settings">', '</a>' ); ?></p>

					<div class="forminator-unsupported-settings fui-dismiss-box fui-flushed" style="display: none;">

						<p class="sui-description" style="margin-bottom: 10px; color: #333; font-weight: bold;"><?php esc_html_e( 'Unsupported form fields and settings', 'forminator' ); ?></p>

						<ol class="fui-dismiss-list">
							<li>
								<?php
								printf(
								/* Translators: 1. Opening <strong> tag, 2. closing <strong> tag. */
									esc_html__( "%1\$s1. Quiz field:%2\$s Forminator doesn't have a built-in quiz field, however, you can enable CAPTCHA and Honeypot protection on your imported forms.", 'forminator' ),
									'<strong>',
									'</strong>'
								);
								?>
							</li>
							<li>
								<?php
								printf(
								/* Translators: 1. Opening <strong> tag, 2. closing <strong> tag. 3. Opening <a> tag with link to the wpmudev webhook, 2. closing <a> tag. */
									esc_html__( "%1\$s2. ConstantContact:%2\$s Forminator doesn't integrate directly with ConstantContact. However, you can use %3\$sWebhook integration%4\$s to send your leads to ConstantContact.", 'forminator' ),
									'<strong>',
									'</strong>',
									forminator_is_show_documentation_link() ? '<a href="https://wpmudev.com/docs/wpmu-dev-plugins/forminator/#webhook" target="_blank">' : '',
									forminator_is_show_documentation_link() ? '</a>' : ''
								);
								?>
							</li>
							<li>
								<?php
								printf(
								/* Translators: 1. Opening <strong> tag, 2. closing <strong> tag. */
									esc_html__( "%1\$s3. reCAPTCHA v3 integration:%2\$s At this stage, Forminator can't import your existing reCAPTCHA integration. You can set this up manually on your imported forms once they are transferred.", 'forminator' ),
									'<strong>',
									'</strong>'
								);
								?>
							</li>
							<li>
								<?php
								printf(
								/* Translators: 1. Opening <strong> tag, 2. closing <strong> tag. */
									esc_html__( "%1\$s4. Additional settings:%2\$s Forminator doesn't support CF7’s additional form settings.", 'forminator' ),
									'<strong>',
									'</strong>'
								);
								?>
							</li>
							<li>
								<?php
								printf(
								/* Translators: 1. Opening <strong> tag, 2. closing <strong> tag. */
									esc_html__( '%1$s5. Custom field IDs:%2$s Forminator creates a unique ID for each field, and the conditional logic relies on them. However, you can provide a custom CSS class for each field.', 'forminator' ),
									'<strong>',
									'</strong>'
								);
								?>
							</li>
						</ol>

						<button role="button" class="fui-dismiss-button forminator-dismiss-unsupported">
							<i class="sui-icon-close sui-sm" aria-hidden="true"></i>
							<?php
							printf(
							/* Translators: 1. Opening <span> tag, 2. closing <span> tag. */
								esc_html__( 'Dismiss%1$s this notice%2$s', 'forminator' ),
								'<span class="sui-screen-reader-text">',
								'</span>'
							);
							?>
						</button>

					</div>

					<div class="sui-side-tabs" style="margin-top: 10px;">

						<div class="sui-tabs-menu">

							<label for="cf7_forms_all" class="sui-tab-item active">
								<input
									type="radio"
									name="cf7_forms"
									value="all"
									id="cf7_forms_all"
									class="forminator-import-forms"
									checked="checked"
								/>
								<?php esc_html_e( 'All', 'forminator' ); ?>
							</label>

							<label for="cf7_forms_specific" class="sui-tab-item">
								<input
									type="radio"
									name="cf7_forms"
									value="specific"
									id="cf7_forms_specific"
									class="forminator-import-forms"
									data-tab-menu="cf7_forms"
								/>
								<?php esc_html_e( 'Specific Forms', 'forminator' ); ?>
							</label>

						</div>

						<div class="sui-tabs-content">

							<div data-tab-content="cf7_forms" class="sui-tab-content sui-tab-boxed">

								<div class="sui-form-field">

									<label class="sui-label"><?php esc_html_e( 'Choose Forms', 'forminator' ); ?></label>

									<select id="forminator-choose-import-form" class="sui-select" multiple="multiple" name="cf7-form-id[]">
										<?php
										if ( ! empty( $forms ) ) :

											foreach ( $forms as $key => $value ) {
												printf(
													'<option value="%f">%s</option>',
													absint( $value->ID ),
													esc_html( $value->post_title )
												);
											}

										endif;
										?>
									</select>

								</div>

							</div>

						</div>

					</div>

				</div>

			</div>

			<?php // ROW: Add-ons. ?>
			<div class="sui-box-settings-row">

				<div class="sui-box-settings-col-2">

					<h4 class="sui-settings-label sui-dark"><?php esc_html_e( 'Add-ons', 'forminator' ); ?></h4>

					<p class="sui-description" style="margin-bottom: 20px;">
						<?php
						printf(
						/* Translators: 1. Opening <strong> tag, 2. closing <strong> tag. */
							esc_html__( "Choose the Contact Form 7 add-ons you wish to import form data and settings from. %1\$sNote:%2\$s The importer only supports the most widely used add-ons. For less common add-ons, you'll need to manually configure the equivalent functionality in those imported forms.", 'forminator' ),
							'<strong>',
							'</strong>'
						);
						?>
					</p>

					<?php
					if ( is_plugin_active( 'flamingo/flamingo.php' ) ) :
						$is_addons = true;
						?>

						<div class="fui-addons-option">

							<label for="forminator-cf7-addon-flamingo" class="sui-checkbox sui-checkbox-sm sui-checkbox-stacked" style="margin-bottom: 2px;">
								<input
									type="checkbox"
									name="cf7-addons[]"
									value="flamingo"
									id="forminator-cf7-addon-flamingo"
									aria-labelledby="listings-cf7-addon-label listings-cf7-addon-flamingo"
									aria-describedby="listings-cf7-addon-message"
									checked="checked"
								/>
								<span aria-hidden="true"></span>
								<span id="listings-cf7-addon-flamingo"><?php esc_html_e( 'Flamingo', 'forminator' ); ?></span>
							</label>

							<span class="sui-description sui-checkbox-description"><?php esc_html_e( 'Import your form submissions from Flamingo and show them on the submissions page.', 'forminator' ); ?></span>

						</div>

						<?php
					endif;

					if ( is_plugin_active( 'contact-form-7-honeypot/honeypot.php' ) ) :
						$is_addons = true;
						?>

						<div class="fui-addons-option">

							<label for="forminator-cf7-addon-honeypot" class="sui-checkbox sui-checkbox-sm sui-checkbox-stacked" style="margin-bottom: 2px;">
								<input
									type="checkbox"
									name="cf7-addons[]"
									value="honeypot"
									id="forminator-cf7-addon-honeypot"
									aria-labelledby="listings-cf7-addon-label listings-cf7-addon-honeypot"
									aria-describedby="listings-cf7-addon-message"
									checked="checked"
								/>
								<span aria-hidden="true"></span>
								<span id="listings-cf7-addon-honeypot"><?php esc_html_e( 'Honeypot for Contact Form 7', 'forminator' ); ?></span>
							</label>

							<span class="sui-description sui-checkbox-description"><?php esc_html_e( 'Enable honeypot protection on the imported forms in Forminator.', 'forminator' ); ?></span>

						</div>

						<?php
					endif;

					if ( is_plugin_active( 'contact-form-cfdb7/contact-form-cfdb-7.php' ) ) :
						$is_addons = true;
						?>

						<div class="fui-addons-option">

							<label for="forminator-cf7-addon-cfdb7" class="sui-checkbox sui-checkbox-sm sui-checkbox-stacked" style="margin-bottom: 2px;">
								<input
										type="checkbox"
										name="cf7-addons[]"
										value="cfdb7"
										id="forminator-cf7-addon-cfdb7"
										aria-labelledby="listings-cf7-addon-label listings-cf7-addon-cfdb7"
										aria-describedby="listings-cf7-addon-message"
										checked="checked"
								/>
								<span aria-hidden="true"></span>
								<span id="listings-cf7-addon-cfdb7"><?php esc_html_e( 'Contact Form 7 Database Addon – CFDB7', 'forminator' ); ?></span>
							</label>

							<span class="sui-description sui-checkbox-description"><?php esc_html_e( 'Import your form submissions from CFDB7 add-on and show them on the submissions page.', 'forminator' ); ?></span>

						</div>

						<?php
					endif;

					if ( is_plugin_active( 'wpcf7-redirect/wpcf7-redirect.php' ) ) :
						$is_addons = true;
						?>

						<div class="fui-addons-option">

							<label for="forminator-cf7-addon-redirection" class="sui-checkbox sui-checkbox-sm sui-checkbox-stacked" style="margin-bottom: 2px;">
								<input
										type="checkbox"
										name="cf7-addons[]"
										value="redirection"
										id="forminator-cf7-addon-redirection"
										aria-labelledby="listings-cf7-addon-label listings-cf7-addon-redirection"
										aria-describedby="listings-cf7-addon-message"
										checked="checked"
								/>
								<span aria-hidden="true"></span>
								<span id="listings-cf7-addon-redirection"><?php esc_html_e( 'Contact Form 7 Redirection', 'forminator' ); ?></span>
							</label>

							<span class="sui-description sui-checkbox-description"><?php esc_html_e( 'Import redirection URL settings and apply them on your imported forms in Forminator. Note that Forminator doesn’t support passing form fields as query parameters into redirect URL, redirection delay, and running a script after form submission.', 'forminator' ); ?></span>

						</div>

						<?php
					endif;

					if ( is_plugin_active( 'cf7-conditional-fields/contact-form-7-conditional-fields.php' ) ) :
						$is_addons = true;
						?>

						<div class="fui-addons-option">

							<label for="forminator-cf7-addon-conditional" class="sui-checkbox sui-checkbox-sm sui-checkbox-stacked" style="margin-bottom: 2px;">
								<input
										type="checkbox"
										name="cf7-addons[]"
										value="conditional"
										id="forminator-cf7-addon-conditional"
										aria-labelledby="listings-cf7-addon-label listings-cf7-addon-conditional"
										aria-describedby="listings-cf7-addon-message"
										checked="checked"
								/>
								<span aria-hidden="true"></span>
								<span id="listings-cf7-addon-conditional"><?php esc_html_e( 'Contact Form 7 Conditional Fields', 'forminator' ); ?></span>
							</label>

							<span class="sui-description sui-checkbox-description"><?php esc_html_e( 'Import your form fields conditions and apply them automatically on your imported forms.', 'forminator' ); ?></span>

						</div>

						<?php
					endif;

					if ( is_plugin_active( 'contact-form-submissions/contact-form-submissions.php' ) ) :
						$is_addons = true;
						?>

						<div class="fui-addons-option">

							<label for="forminator-cf7-addon-submissions" class="sui-checkbox sui-checkbox-sm sui-checkbox-stacked" style="margin-bottom: 2px;">
								<input
										type="checkbox"
										name="cf7-addons[]"
										value="submissions"
										id="forminator-cf7-addon-submissions"
										aria-labelledby="listings-cf7-addon-label listings-cf7-addon-submissions"
										aria-describedby="listings-cf7-addon-message"
										checked="checked"
								/>
								<span aria-hidden="true"></span>
								<span id="listings-cf7-addon-submissions"><?php esc_html_e( 'Contact Form Submissions', 'forminator' ); ?></span>
							</label>

							<span class="sui-description sui-checkbox-description"><?php esc_html_e( 'Import your form submissions and show them on the submissions page.', 'forminator' ); ?></span>

						</div>

						<?php
					endif;

					if ( is_plugin_active( 'wpcf7-recaptcha/wpcf7-recaptcha.php' ) ) :
						$is_addons = true;
						?>

						<div class="fui-addons-option">

							<label for="forminator-cf7-addon-recaptchav2" class="sui-checkbox sui-checkbox-sm sui-checkbox-stacked" style="margin-bottom: 2px;">
								<input
										type="checkbox"
										name="cf7-addons[]"
										value="recaptchav2"
										id="forminator-cf7-addon-recaptchav2"
										aria-labelledby="listings-cf7-addon-label listings-cf7-addon-recaptchav2"
										aria-describedby="listings-cf7-addon-message"
										checked="checked"
								/>
								<span aria-hidden="true"></span>
								<span id="listings-cf7-addon-recaptchav2"><?php esc_html_e( 'Contact Form 7 - reCAPTCHA v2', 'forminator' ); ?></span>
							</label>

							<span class="sui-description sui-checkbox-description"><?php esc_html_e( 'Import your reCaptcha v2 API keys and configure the reCaptcha v2 on your imported forms.', 'forminator' ); ?></span>

						</div>

						<?php
					endif;

					if ( is_plugin_active( 'advanced-cf7-db/advanced-cf7-db.php' ) ) :
						$is_addons = true;
						?>

						<div class="fui-addons-option">

							<label for="forminator-cf7-addon-advanced_cf7" class="sui-checkbox sui-checkbox-sm sui-checkbox-stacked" style="margin-bottom: 2px;">
								<input
										type="checkbox"
										name="cf7-addons[]"
										value="advanced_cf7"
										id="forminator-cf7-addon-advanced_cf7"
										aria-labelledby="listings-cf7-addon-label listings-cf7-addon-advanced_cf7"
										aria-describedby="listings-cf7-addon-message"
										checked="checked"
								/>
								<span aria-hidden="true"></span>
								<span id="listings-cf7-addon-advanced_cf7"><?php esc_html_e( 'Advanced Contact form 7 DB', 'forminator' ); ?></span>
							</label>

							<span class="sui-description sui-checkbox-description"><?php esc_html_e( 'Import your form submissions and show them on the submissions page.', 'forminator' ); ?></span>

						</div>

					<?php endif; ?>

					<?php if ( ! $is_addons ) { ?>

						<div class="sui-notice sui-notice-warning sui-notice-active" style="display: block;">
							<div class="sui-notice-content">
								<div class="sui-notice-message">
									<span class="sui-notice-icon sui-icon-info sui-md" aria-hidden="true"></span>
									<p><?php esc_html_e( 'We couldn\'t find any supported add-ons.', 'forminator' ); ?></p>
								</div>
							</div>
						</div>

					<?php } ?>

				</div>

			</div>

		</div>

		<div class="sui-box-footer">

			<div class="sui-actions-right">

				<button class="sui-button sui-button-blue wpmudev-action-ajax-cf7-import">
					<span class="sui-loading-text"><?php esc_html_e( 'Begin Import', 'forminator' ); ?></span>
					<i class="sui-icon-loader sui-loading" aria-hidden="true"></i>
				</button>

			</div>

		</div>

	</form>
</div>

<div class="forminator-cf7-importing sui-hidden">

	<div class="sui-box-body wpmudev-popup-form">

		<div class="sui-box-settings-row">

			<div class="sui-box-settings-col-2">

				<p><?php esc_html_e( 'Please keep this modal open while we import your Contact Form 7 forms and add-ons. It will only take a few seconds.', 'forminator' ); ?></p>

				<div class="sui-progress-block">

					<div class="sui-progress">

						<span class="sui-progress-icon" aria-hidden="true">
							<i class="sui-icon-loader sui-loading"></i>
						</span>

						<span class="sui-progress-text">
							<span>50%</span>
						</span>

						<div class="sui-progress-bar" aria-hidden="true">
							<span style="width: 50%"></span>
						</div>

					</div>

				</div>

				<span class="sui-progress-state"><?php esc_html_e( 'Importing submissions from Flamingo', 'forminator' ); ?></span>

			</div>

		</div>

	</div>

</div>

<div class="forminator-cf7-imported sui-hidden">

	<div class="sui-box-body wpmudev-popup-form">

		<div class="sui-notice sui-notice-success">
			<p><?php esc_html_e( 'Your selected forms from Contact Form 7 and the add-ons imported successfully.', 'forminator' ); ?></p>
		</div>

		<div class="fui-dismiss-box fui-flushed">

			<p class="sui-description" style="margin-bottom: 10px; color: #333; font-weight: bold;"><?php esc_html_e( 'Recommendations', 'forminator' ); ?></p>

			<p class="sui-description" style="margin-bottom: 5px;"><?php esc_html_e( 'Following are the next recommended steps:', 'forminator' ); ?></p>

			<ol class="fui-dismiss-list">
				<li><?php esc_html_e( '1. Visit Forminator and preview your forms to make sure everything looks perfect and adjust your forms with the additional settings Forminator offers as per your needs.', 'forminator' ); ?></li>
				<li><?php esc_html_e( '2. Use the Gutenberg block or shortcode to embed your forms in the required places.', 'forminator' ); ?></li>
				<li><?php esc_html_e( '3. Deactivate Contact Form 7 and the add-ons if you don’t wish to use them anymore.', 'forminator' ); ?></li>
			</ol>

		</div>

	</div>

	<div class="sui-box-footer" style="padding-top: 0; border-top: 0;">

		<button class="sui-button sui-button-ghost forminator-popup-close" data-a11y-dialog-hide="forminator-popup">
			<span class="sui-loading-text"><?php esc_html_e( 'Close', 'forminator' ); ?></span>
			<i class="sui-icon-loader sui-loading" aria-hidden="true"></i>
		</button>

		<div class="sui-actions-right">

			<a href="<?php echo esc_url( forminator_get_disable_url( 'cf7', 'contact-form-7/wp-contact-form-7.php' ) ); ?>" class="sui-button">
				<span class="sui-loading-text">
					<i class="sui-icon-power-on-off" aria-hidden="true"></i>
					<?php esc_html_e( 'Deactivate contact form 7', 'forminator' ); ?>
				</span>
				<i class="sui-icon-loader sui-loading" aria-hidden="true"></i>
			</a>

		</div>

	</div>

</div>

<div class="forminator-cf7-imported-fail sui-hidden">

	<div class="sui-box-body wpmudev-popup-form">
		<?php $support_url = apply_filters( 'forminator_support_url', 'https://wordpress.org/support/plugin/forminator' ); ?>
		<p>
		<?php
		esc_html_e( 'We have encountered an error while importing your forms from Contact Form 7 and selected add-ons.', 'forminator' );
		if ( forminator_is_show_documentation_link() ) {
			printf(
			/* Translators: 1. Opening <a> tag with link to the support url, 2. closing <a> tag. */
				' ' . esc_html__( 'Unable to solve this? Contact our %1$ssupport%2$s team for further help.', 'forminator' ),
				'<a href="' . esc_url( $support_url ) . '" target="_blank">',
				'</a>'
			);
		}
		?>
		</p>

		<div class="sui-notice sui-notice-error">
			<p><?php esc_html_e( 'We couldn\'t find any compatible data to import.', 'forminator' ); ?></p>
		</div>

	</div>

	<div class="sui-box-footer" style="padding-top: 0; border-top: 0;">

		<button class="sui-button sui-button-ghost forminator-popup-close" data-a11y-dialog-hide="forminator-popup">
			<span class="sui-loading-text"><?php esc_html_e( 'Cancel', 'forminator' ); ?></span>
			<i class="sui-icon-loader sui-loading" aria-hidden="true"></i>
		</button>

		<div class="sui-actions-right">

			<button class="sui-button forminator-retry-import">
				<span class="sui-loading-text"><?php esc_html_e( 'Retry Import', 'forminator' ); ?></span>
				<i class="sui-icon-loader sui-loading" aria-hidden="true"></i>
			</button>

		</div>

	</div>

</div>
