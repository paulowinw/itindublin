<?php
/**
 * Template admin/views/settings/tab-recaptcha.php
 *
 * @package Forminator
 */

$section = Forminator_Core::sanitize_text_field( 'section', 'dashboard' );

$recaptcha_logo              = forminator_plugin_url() . 'assets/img/recaptcha_logo.png';
$recaptcha_logo_2x           = forminator_plugin_url() . 'assets/img/recaptcha_logo@2x.png';
$hcaptcha_logo               = forminator_plugin_url() . 'assets/img/hcaptcha_logo.png';
$hcaptcha_logo_2x            = forminator_plugin_url() . 'assets/img/hcaptcha_logo@2x.png';
$turnstile_logo              = forminator_plugin_url() . 'assets/images/icons/turnstile_logo.png';
$turnstile_logo_2x           = forminator_plugin_url() . 'assets/images/icons/turnstile_logo@2x.png';
$v2_captcha_key              = get_option( 'forminator_captcha_key', '' );
$v2_captcha_secret           = get_option( 'forminator_captcha_secret', '' );
$v2_invisible_captcha_key    = get_option( 'forminator_v2_invisible_captcha_key', '' );
$v2_invisible_captcha_secret = get_option( 'forminator_v2_invisible_captcha_secret', '' );
$v3_captcha_key              = get_option( 'forminator_v3_captcha_key', '' );
$v3_captcha_secret           = get_option( 'forminator_v3_captcha_secret', '' );
$captcha_language            = get_option( 'forminator_captcha_language', '' );
$nonce                       = wp_create_nonce( 'forminator_save_popup_captcha' );

$captcha_tab_saved = get_option( 'forminator_captcha_tab_saved', 'recaptcha' );
$hcaptcha_key      = get_option( 'forminator_hcaptcha_key', '' );
$hcaptcha_secret   = get_option( 'forminator_hcaptcha_secret', '' );

$turnstile_key    = get_option( 'forminator_turnstile_key', '' );
$turnstile_secret = get_option( 'forminator_turnstile_secret', '' );

$new = true;
?>

<div class="sui-box" data-nav="captcha" style="<?php echo esc_attr( 'captcha' !== $section ? 'display: none;' : '' ); ?>">

	<div class="sui-box-header">
		<h2 class="sui-box-title"><?php esc_html_e( 'CAPTCHA', 'forminator' ); ?></h2>
	</div>

	<form class="forminator-settings-save" action="">
		<input type="hidden" name="captcha_tab_saved" value="<?php echo esc_attr( $captcha_tab_saved ); ?>" />

		<div class="sui-box-body">

			<div class="sui-box-settings-slim-row">

				<div class="sui-box-settings-col-1">
					<span class="sui-settings-label"><?php esc_html_e( 'Configuration', 'forminator' ); ?></span>
					<span class="sui-description"><?php esc_html_e( 'A CAPTCHA is an anti-spam technique which helps to protect your website from spam and abuse.', 'forminator' ); ?></span>
					<span class="sui-description"><?php esc_html_e( 'Forminator currently supports reCAPTCHA, hCaptcha, and Cloudflare Turnstile. Select and configure your preferred CAPTCHA settings and language.', 'forminator' ); ?></span>
				</div>

				<div class="sui-box-settings-col-2">

					<div class="sui-form-field">

						<div class="sui-tabs sui-tabs-overflow">

							<div
								tabindex="-1"
								class="sui-tabs-navigation"
								aria-hidden="true"
							>
								<button
									type="button"
									class="sui-button-icon sui-tabs-navigation--left"
									aria-label="<?php esc_attr_e( 'Scroll left', 'forminator' ); ?>"
								>
									<span class="sui-icon-chevron-left"></span>
								</button>
								<button
									type="button"
									class="sui-button-icon sui-tabs-navigation--right"
									aria-label="<?php esc_attr_e( 'Scroll right', 'forminator' ); ?>"
								>
									<span class="sui-icon-chevron-right"></span>
								</button>
							</div>

							<div role="tablist" class="sui-tabs-menu">
								<button type="button" role="tab" id="g-recaptcha-btn"
									class="captcha-main-tab sui-tab-item <?php echo esc_attr( 'recaptcha' === $captcha_tab_saved ? 'active' : '' ); ?>"
									aria-controls="g-recaptcha-tab"
									aria-selected="true"
									data-tab-name="recaptcha"
								>
									<img
										src="<?php echo esc_url( $recaptcha_logo ); ?>"
										srcset="<?php echo esc_url( $recaptcha_logo ); ?> 1x, <?php echo esc_url( $recaptcha_logo_2x ); ?> 2x"
										alt="<?php echo esc_attr( 'Recaptcha' ); ?>"
										style="pointer-events: none;"
									/>
									<?php esc_html_e( 'Recaptcha', 'forminator' ); ?>
								</button>
								<button type="button" role="tab" id="hcaptcha-btn"
									class="captcha-main-tab sui-tab-item <?php echo esc_attr( 'hcaptcha' === $captcha_tab_saved ? 'active' : '' ); ?>"
									aria-controls="hcaptcha-tab"
									aria-selected="false"
									data-tab-name="hcaptcha"
								>
									<img
										src="<?php echo esc_url( $hcaptcha_logo ); ?>"
										srcset="<?php echo esc_url( $hcaptcha_logo ); ?> 1x, <?php echo esc_url( $hcaptcha_logo_2x ); ?> 2x"
										alt="<?php echo esc_attr( 'Hcaptcha' ); ?>"
										style="pointer-events: none;"
									/>
									<?php esc_html_e( 'Hcaptcha', 'forminator' ); ?>
								</button>
								<button type="button" role="tab" id="turnstile-btn"
									class="captcha-main-tab sui-tab-item <?php echo esc_attr( 'turnstile' === $captcha_tab_saved ? 'active' : '' ); ?>"
									aria-controls="turnstile-tab"
									aria-selected="false"
									data-tab-name="turnstile"
								>
									<img
										src="<?php echo esc_url( $turnstile_logo ); ?>"
										srcset="<?php echo esc_url( $turnstile_logo ); ?> 1x, <?php echo esc_url( $turnstile_logo_2x ); ?> 2x"
										alt="<?php echo esc_attr( 'Cloudflare Turnstile' ); ?>"
										style="pointer-events: none;"
									/>
									<?php esc_html_e( 'Cloudflare Turnstile', 'forminator' ); ?>
								</button>
							</div>

							<div class="sui-tabs-content">

								<?php // TAB: Recaptcha. ?>
								<div tabindex="1" role="tabpanel" id="g-recaptcha-tab" class="sui-tab-content <?php echo esc_attr( 'recaptcha' === $captcha_tab_saved ? 'active' : '' ); ?>" aria-labelledby="g-recaptcha-btn">

									<span class="sui-settings-label"><?php esc_html_e( 'reCaptcha API Keys', 'forminator' ); ?></span>
									<span class="sui-description" style="margin-bottom: 10px;">
										<?php
										printf(
										/* Translators: 1. Opening <a> tag with link to Google recaptcha, 2. closing <a> tag. */
											esc_html__( 'Enter the API keys for each reCAPTCHA type you want to use in your forms. Note that each reCAPTCHA type requires a different set of API keys. %1$sGenerate API keys%2$s', 'forminator' ),
											'<a href="https://www.google.com/recaptcha/admin#list" target="_blank">',
											'</a>'
										);
										?>
									</span>

									<div class="sui-tabs sui-side-tabs">

										<div role="tablist" class="sui-tabs-menu">
											<button type="button" role="tab" id="v2-checkbox" class="sui-tab-item active" aria-controls="v2-checkbox-tab" aria-selected="true"><?php esc_html_e( 'v2 Checkbox', 'forminator' ); ?></button>
											<button type="button" role="tab" id="v2-invisible" class="sui-tab-item" aria-controls="v2-invisible-tab" aria-selected="false" tabindex="-1"><?php esc_html_e( 'v2 Invisible', 'forminator' ); ?></button>
											<button type="button" role="tab" id="recaptcha-v3" class="sui-tab-item" aria-controls="v3-recaptcha-tab" aria-selected="false" tabindex="-1"><?php esc_html_e( 'v3 reCaptcha', 'forminator' ); ?></button>
										</div>

										<div class="sui-tabs-content">

											<?php // TAB: v2 Checkbox. ?>
											<div tabindex="0" role="tabpanel" id="v2-checkbox-tab" class="sui-tab-content sui-tab-boxed active" aria-labelledby="v2-checkbox">

												<span class="sui-description"><?php esc_html_e( 'Enter the API keys for reCAPTCHA v2 Checkbox type below:', 'forminator' ); ?></span>

												<div class="sui-form-field">
													<label for="v2_captcha_key" id="v2checkbox-sitekey-label" class="sui-label"><?php esc_html_e( 'Site Key', 'forminator' ); ?></label>
													<input
														type="text"
														name="v2_captcha_key"
														placeholder="<?php esc_html_e( 'Enter your site key here', 'forminator' ); ?>"
														value="<?php echo esc_attr( $v2_captcha_key ); ?>"
														id="v2_captcha_key"
														class="sui-form-control"
														aria-labelledby="v2checkbox-sitekey-label"
													/>
												</div>

												<div class="sui-form-field">
													<label for="v2_captcha_secret" id="v2checkbox-secretkey-label" class="sui-label"><?php esc_html_e( 'Secret Key', 'forminator' ); ?></label>
													<input
														type="text"
														name="v2_captcha_secret"
														placeholder="<?php esc_html_e( 'Enter your secret key here', 'forminator' ); ?>"
														value="<?php echo esc_attr( $v2_captcha_secret ); ?>"
														id="v2_captcha_secret"
														class="sui-form-control"
														aria-labelledby="v2checkbox-secretkey-label"
													/>
												</div>

												<div class="sui-form-field">
													<label class="sui-label"><?php esc_html_e( 'reCAPTCHA Preview', 'forminator' ); ?></label>
													<div id="v2-recaptcha-preview">
														<p class="fui-loading-dialog">
															<i class="sui-icon-loader sui-loading" aria-hidden="true"></i>
														</p>
													</div>
													<span class="sui-description"><?php esc_html_e( 'If you see any errors in the preview, make sure the key you\'ve entered are valid, and you\'ve listed your domain name while generating the keys.', 'forminator' ); ?></span>
												</div>

											</div>

											<?php // TAB: v2 Invisible. ?>
											<div tabindex="0" role="tabpanel" id="v2-invisible-tab" class="sui-tab-content sui-tab-boxed" aria-labelledby="v2-invisible" hidden>

												<span class="sui-description"><?php esc_html_e( 'Enter the API keys for reCAPTCHA v2 Invisible type below:', 'forminator' ); ?></span>

												<div class="sui-form-field">
													<label for="invisible_captcha_key" id="v2invisible-sitekey-label" class="sui-label"><?php esc_html_e( 'Site Key', 'forminator' ); ?></label>
													<input
														type="text"
														name="v2_invisible_captcha_key"
														placeholder="<?php esc_html_e( 'Enter your site key here', 'forminator' ); ?>"
														value="<?php echo esc_attr( $v2_invisible_captcha_key ); ?>"
														id="invisible_captcha_key"
														class="sui-form-control"
														aria-labelledby="v2invisible-sitekey-label"
													/>
												</div>

												<div class="sui-form-field">
													<label for="invisible_captcha_secret" id="v2invisible-secretkey-label" class="sui-label"><?php esc_html_e( 'Secret Key', 'forminator' ); ?></label>
													<input
														type="text"
														name="v2_invisible_captcha_secret"
														placeholder="<?php esc_html_e( 'Enter your secret key here', 'forminator' ); ?>"
														value="<?php echo esc_attr( $v2_invisible_captcha_secret ); ?>"
														id="invisible_captcha_secret"
														class="sui-form-control"
														aria-labelledby="v2invisible-secretkey-label"
													/>
												</div>

												<div class="sui-form-field">
													<label class="sui-label"><?php esc_html_e( 'reCAPTCHA Preview', 'forminator' ); ?></label>

													<div id="v2-invisible-recaptcha-preview">
														<p class="fui-loading-dialog">
															<i class="sui-icon-loader sui-loading" aria-hidden="true"></i>
														</p>
													</div>

													<span class="sui-description"><?php esc_html_e( 'If you see any errors in the preview, make sure the key you\'ve entered are valid, and you\'ve listed your domain name while generating the keys.', 'forminator' ); ?></span>
												</div>

											</div>

											<?php // TAB: v3 reCaptcha. ?>
											<div tabindex="0" role="tabpanel" id="v3-recaptcha-tab" class="sui-tab-content sui-tab-boxed" aria-labelledby="recaptcha-v3" hidden>

												<span class="sui-description"><?php esc_html_e( 'Enter the API keys for reCAPTCHA v3 type below:', 'forminator' ); ?></span>

												<div class="sui-form-field">
													<label for="v3_captcha_key" id="v3recaptcha-sitekey-label" class="sui-label"><?php esc_html_e( 'Site Key', 'forminator' ); ?></label>
													<input
														type="text"
														name="v3_captcha_key"
														placeholder="<?php esc_html_e( 'Enter your site key here', 'forminator' ); ?>"
														value="<?php echo esc_attr( $v3_captcha_key ); ?>"
														id="v3_captcha_key"
														class="sui-form-control"
														aria-labelledby="v3recaptcha-sitekey-label"
													/>
												</div>

												<div class="sui-form-field">
													<label for="v3_captcha_secret" id="v3recaptcha-secretkey-label" class="sui-label"><?php esc_html_e( 'Secret Key', 'forminator' ); ?></label>
													<input
														type="text"
														name="v3_captcha_secret"
														placeholder="<?php esc_html_e( 'Enter your secret key here', 'forminator' ); ?>"
														value="<?php echo esc_attr( $v3_captcha_secret ); ?>"
														id="v3_captcha_secret"
														class="sui-form-control"
														aria-labelledby="v3recaptcha-secretkey-label"
													/>
												</div>

												<div class="sui-form-field">

													<label class="sui-label"><?php esc_html_e( 'reCAPTCHA Preview', 'forminator' ); ?></label>

													<div id="v3-recaptcha-preview">
														<p class="fui-loading-dialog">
															<i class="sui-icon-loader sui-loading" aria-hidden="true"></i>
														</p>
													</div>

													<span class="sui-description"><?php esc_html_e( 'If you see any errors in the preview, make sure the key you\'ve entered are valid, and you\'ve listed your domain name while generating the keys.', 'forminator' ); ?></span>

												</div>

											</div>

										</div>

									</div>

								</div>

								<?php // TAB: HCaptcha. ?>
								<div tabindex="1" role="tabpanel" id="hcaptcha-tab" class="sui-tab-content <?php echo esc_attr( 'hcaptcha' === $captcha_tab_saved ? 'active' : '' ); ?>" aria-labelledby="hcaptcha-btn">

									<span class="sui-settings-label"><?php esc_html_e( 'hCaptcha API Keys', 'forminator' ); ?></span>
									<span class="sui-description" style="margin-bottom: 10px;">
										<?php
										printf(
										/* Translators: 1. Opening <a> tag with hCaptcha API key, 2. closing <a> tag. */
											esc_html__( 'Enter your hCaptcha API keys below to enable hCaptcha option in your form’s CAPTCHA field. See %1$sinstructions%2$s on how to get your API Keys.', 'forminator' ),
											'<a href="https://docs.hcaptcha.com/switch#get-your-hcaptcha-sitekey-and-secret-key" target="_blank">',
											'</a>'
										);
										?>
									</span>

									<div class="sui-form-field">
										<label for="hcaptcha_key" id="hcaptcha-sitekey-label" class="sui-label"><?php esc_html_e( 'Site Key', 'forminator' ); ?></label>
										<input
											type="text"
											name="hcaptcha_key"
											placeholder="<?php esc_html_e( 'Enter your site key here', 'forminator' ); ?>"
											value="<?php echo esc_attr( $hcaptcha_key ); ?>"
											id="hcaptcha_key"
											class="sui-form-control"
											aria-labelledby="hcaptcha-sitekey-label"
										/>
									</div>

									<div class="sui-form-field">
										<label for="hcaptcha_secret" id="hcaptcha-secretkey-label" class="sui-label"><?php esc_html_e( 'Secret Key', 'forminator' ); ?></label>
										<input
											type="text"
											name="hcaptcha_secret"
											placeholder="<?php esc_html_e( 'Enter your secret key here', 'forminator' ); ?>"
											value="<?php echo esc_attr( $hcaptcha_secret ); ?>"
											id="hcaptcha_secret"
											class="sui-form-control"
											aria-labelledby="hcaptcha-secretkey-label"
										/>
									</div>

									<?php
									// remove this for now, can be put back later when needed for noconflict.
									/**
									// <div class="sui-form-field">
									//  <label for="hcaptcha_noconflict" class="sui-checkbox">
									//      <input
									//          type="checkbox"
									//          name="hcaptcha_noconflict"
									//          id="hcaptcha_noconflict"
									//          aria-labelledby="hcaptcha-noconflict-label"
									//          value="true"
									//          <?php echo checked( $hcaptcha_noconflict ); ?>
									//      />
									//      <span aria-hidden="true"></span>
									//      <span id="hcaptcha-noconflict-label">
									//          <?php esc_html_e( 'Enable no-conflict mode', 'forminator' ); ?>
									//          <button
									//              class="sui-button-icon sui-tooltip sui-tooltip-top-center sui-tooltip-constrained"
									//              style="--tooltip-width: 171px;height:20px;"
									//              data-tooltip="Forcefully remove other CAPTCHA occurrences in order to prevent conflicts. Only enable this option if your site is having compatibility issues."
									//          >
									//              <span class="sui-icon-info" aria-hidden="true"></span>
									//          </button>
									//      </span>
									//  </label>
									</div>
									 */
									?>

									<div class="sui-form-field">
										<label for="hcaptcha_preview" id="hcaptcha_preview-label" class="sui-label"><?php esc_html_e( 'hCAPTCHA Preview', 'forminator' ); ?></label>
										<div id="hcaptcha-preview">
											<p class="fui-loading-dialog">
												<i class="sui-icon-loader sui-loading" aria-hidden="true"></i>
											</p>
										</div>
										<span class="sui-description">
											<?php esc_html_e( 'If you see any errors in the preview, make sure the keys you’ve entered are valid, and you’ve listed your domain name while generating the keys.', 'forminator' ); ?>
										</span>
									</div>

								</div>

								<?php // TAB: Cloudflare Turnstile. ?>
								<div tabindex="1" role="tabpanel" id="turnstile-tab" class="sui-tab-content <?php echo esc_attr( 'turnstile' === $captcha_tab_saved ? 'active' : '' ); ?>" aria-labelledby="turnstile-btn">

									<span class="sui-settings-label"><?php esc_html_e( 'Turnstile API Keys', 'forminator' ); ?></span>
									<span class="sui-description" style="margin-bottom: 10px;">
										<?php
										esc_html_e( 'Enter your Turnstile API keys below to enable Cloudflare Turnstile option in your form’s CAPTCHA field.', 'forminator' );
										if ( forminator_is_show_documentation_link() ) {
											printf(
												/* Translators: 1. Opening <a> tag, 2. closing <a> tag. */
												' ' . esc_html__( 'See %1$sinstructions%2$s on how to get your API Keys.', 'forminator' ),
												'<a href="https://wpmudev.com/docs/wpmu-dev-plugins/forminator/#cloudflare-turnstile-settings" target="_blank" rel="noreferrer">',
												'</a>'
											);
										}
										?>
									</span>

									<div class="sui-form-field">
										<label for="turnstile_key" id="turnstile-sitekey-label" class="sui-label"><?php esc_html_e( 'Site Key', 'forminator' ); ?></label>
										<input
											type="text"
											name="turnstile_key"
											placeholder="<?php esc_html_e( 'Enter your site key here', 'forminator' ); ?>"
											value="<?php echo esc_attr( $turnstile_key ); ?>"
											id="turnstile_key"
											class="sui-form-control"
											aria-labelledby="turnstile-sitekey-label"
										/>
									</div>

									<div class="sui-form-field">
										<label for="turnstile_secret" id="turnstile-secretkey-label" class="sui-label"><?php esc_html_e( 'Secret Key', 'forminator' ); ?></label>
										<input
											type="text"
											name="turnstile_secret"
											placeholder="<?php esc_html_e( 'Enter your secret key here', 'forminator' ); ?>"
											value="<?php echo esc_attr( $turnstile_secret ); ?>"
											id="turnstile_secret"
											class="sui-form-control"
											aria-labelledby="turnstile-secretkey-label"
										/>
									</div>

									<div class="sui-form-field">
										<label for="turnstile_preview" id="turnstile_preview-label" class="sui-label"><?php esc_html_e( 'Turnstile widget preview', 'forminator' ); ?></label>
										<div id="turnstile-preview">
											<p class="fui-loading-dialog">
												<i class="sui-icon-loader sui-loading" aria-hidden="true"></i>
											</p>
										</div>
										<span class="sui-description">
											<?php esc_html_e( 'If you see any errors in the preview, make sure the keys you’ve entered are valid, and you’ve listed your domain name while generating the keys.', 'forminator' ); ?>
										</span>
									</div>

								</div>

							</div>

						</div>
						<!-- Main Captcha tabs -->

					</div>

					<div class="sui-form-field">

						<span class="sui-settings-label"><?php esc_html_e( 'Language', 'forminator' ); ?></span>
						<span class="sui-description" style="margin-bottom: 10px;"><?php esc_html_e( 'By default, we\'ll show the CAPTCHA in your website\'s language.', 'forminator' ); ?></span>

						<select name="captcha_language" id="captcha_language" class="sui-select" data-width="240">
							<?php $languages = forminator_get_captcha_languages(); ?>
							<option value=""><?php esc_html_e( 'Automatic', 'forminator' ); ?></option>
							<?php foreach ( $languages as $key => $lang ) : ?>
								<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $captcha_language, $key ); ?>><?php echo esc_html( $lang ); ?></option>
							<?php endforeach; ?>
						</select>

					</div>

				</div>

			</div>

		</div>

		<div class="sui-box-footer">

			<div class="sui-actions-right">

				<button
					class="sui-button sui-button-blue wpmudev-action-done"
					data-title="<?php esc_attr_e( 'Captcha settings', 'forminator' ); ?>"
					data-action="captcha"
					data-nonce="<?php echo esc_attr( $nonce ); ?>"
				>
					<span class="sui-loading-text"><?php esc_html_e( 'Save Settings', 'forminator' ); ?></span>
					<i class="sui-icon-loader sui-loading" aria-hidden="true"></i>
				</button>

			</div>

		</div>

	</form>

</div>
