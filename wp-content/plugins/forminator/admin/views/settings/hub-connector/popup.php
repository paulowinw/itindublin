<?php
/**
 * Template admin/views/templates/hub-connector/popup.php
 *
 * @package Forminator
 */

?>
<!-- Modal for disconnected site -->
<div class="sui-modal sui-modal-sm">
	<div
		role="dialog"
		id="forminator-disconnect-hub-modal"
		class="sui-modal-content"
		aria-modal="true"
		aria-labelledby="forminator-disconnect-hub-title"
		aria-describedby="forminator-disconnect-hub-description"
		data-esc-close="true"
	>
		<div class="sui-box">
			<div class="sui-box-header sui-flatten sui-content-center sui-spacing-top--40">
				<h3 id="forminator-disconnect-hub-title" class="sui-box-title sui-lg">
					<?php esc_html_e( 'Disconnect Site?', 'forminator' ); ?>
				</h3>
				<p id="forminator-disconnect-hub-description" class="sui-description">
					<?php esc_html_e( 'Disconnecting this site will disable key Forminator features and other connected WPMU DEV tools and services.', 'forminator' ); ?>
				</p>
			</div>
			<div class="sui-box-body sui-flatten">
				<div class="sui-notice">
					<div class="sui-notice-content">
						<div class="sui-notice-message">
							<h3><?php esc_html_e( 'You’ll lose access to:', 'forminator' ); ?></h3>
							<ul>
								<li class="sui-icon-cross-close"><?php esc_html_e( 'Forminator Form Templates and Add-ons', 'forminator' ); ?></li>
								<li class="sui-icon-cross-close"><?php esc_html_e( 'AntiBot Global Firewall', 'forminator' ); ?></li>
								<li class="sui-icon-cross-close"><?php esc_html_e( 'Cloud Broken Link Checker', 'forminator' ); ?></li>
								<li class="sui-icon-cross-close"><?php esc_html_e( 'WPMU DEV site management tools via The Hub', 'forminator' ); ?></li>
							</ul>
						</div>
					</div>
				</div>
				<div class="sui-box-settings-row">
					<input placeholder="<?php esc_attr_e( 'Mind sharing why you’re disconnecting?', 'forminator' ); ?>"
						id="forminator-disconnect-site-message" name="disconnect_site_message" class="sui-form-control" maxlength="255"/>
				</div>
			</div>
			<div class="sui-box-footer sui-flatten sui-content-center sui-spacing-bottom--40">
				<button type="button" class="sui-button sui-button-ghost" id="forminator-cancel-hub-disconnection" data-modal-close>
					<?php esc_html_e( 'Cancel', 'forminator' ); ?>
				</button>
				<button type="button" class="sui-button" id="forminator-disconnect-hub" aria-live="polite">
					<span class="sui-button-text-default">
						<i aria-hidden="true" class="sui-icon-plug-disconnected"></i>
						<?php esc_html_e( 'Disconnect site', 'forminator' ); ?>
					</span>
					<span class="sui-button-text-onload">
						<i aria-hidden="true" class="sui-icon-loader sui-loading"></i>
						<?php esc_html_e( 'Disconnect site', 'forminator' ); ?>
					</span>
				</button>
			</div>
		</div>
	</div>
</div>
