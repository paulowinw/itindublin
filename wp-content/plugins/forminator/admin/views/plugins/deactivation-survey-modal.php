<?php
/**
 * Template admin/views/plugins/deactivation-survey-modal.php
 *
 * @package Forminator
 */

$sanitize_version = str_replace( '.', '-', FORMINATOR_SUI_VERSION );
$sui_body_class   = "sui-$sanitize_version";
?>
<div class="<?php echo esc_attr( $sui_body_class ); ?>">
	<div class="sui-wrap">
		<div class="sui-modal sui-modal-lg">
			<div
				role="dialog"
				id="forminator-deactivation-survey-modal"
				class="sui-modal-content"
				aria-live="polite"
				aria-modal="true"
				aria-labelledby="forminator-deactivation-survey__title"
			>
				<div class="sui-box forminator-deactivation-survey-modal" data-nonce="<?php echo esc_attr( wp_create_nonce( 'forminator_deactivation_survey' ) ); ?>">

					<div class="sui-box-header">
						<h3 class="sui-box-title">
							<?php if ( forminator_is_show_branding() ) { ?>
								<span class="fui-field--icon">
									<span class="forminator-header-logo"></span>
								</span>
							<?php } ?>
							<?php esc_html_e( 'Deactivate Forminator?', 'forminator' ); ?>
						</h3>

						<button class="sui-button-icon sui-button-float--right forminator-dismiss-deactivation-survey" data-type="dismiss" data-modal-close>
							<span class="sui-icon-close sui-md" aria-hidden="true"></span>
							<span class="sui-screen-reader-text"><?php esc_html_e( 'Close this dialog.', 'forminator' ); ?></span>
						</button>
					</div>

					<div class="sui-box-body">
						<p>
							<?php
							esc_html_e( 'Please tell us why. Your feedback helps us improve.', 'forminator' );
							do_action( 'forminator_after_deactivation_survey_message' );
							?>
						</p>
						<form class="forminator-deactivation-survey">
							<div class="forminator-deactivation-survey-option">
								<label for="forminator-temporary_user">
									<input type="radio"
										name="forminator_deactivation_reason"
										id="forminator-temporary_user"
										value="temporary_user"/>
									<?php esc_html_e( 'I only needed it for a short-term project / I no longer need the plugin', 'forminator' ); ?>
								</label>
							</div>
							<div class="forminator-deactivation-survey-option">
								<label for="forminator-found_better">
									<input type="radio"
										name="forminator_deactivation_reason"
										id="forminator-found_better"
										value="found_better"/>
									<?php esc_html_e( 'I found a better plugin / alternative', 'forminator' ); ?>
								</label>
								<div class="forminator-deactivation-survey-message sui-hidden">
									<input placeholder="<?php esc_attr_e( 'Which plugin did you switch to, and why? (optional)', 'forminator' ); ?>"
										name="found_better_message" class="sui-form-control" maxlength="255"/>
								</div>
							</div>
							<div class="forminator-deactivation-survey-option">
								<label for="forminator-temp_deactivation">
									<input type="radio"
										name="forminator_deactivation_reason"
										id="forminator-temp_deactivation"
										value="temp_deactivation"/>
									<?php esc_html_e( 'It’s a temporary deactivation', 'forminator' ); ?>
								</label>
							</div>
							<div class="forminator-deactivation-survey-option">
								<label for="forminator-not_working">
									<input type="radio"
										name="forminator_deactivation_reason"
										id="forminator-not_working"
										value="not_working"/>
									<?php esc_html_e( 'I couldn’t get the plugin to work', 'forminator' ); ?>
								</label>
								<div class="forminator-deactivation-survey-message sui-hidden">
									<input placeholder="<?php esc_attr_e( 'What issue did you encounter? (optional)', 'forminator' ); ?>"
										name="not_working_message" class="sui-form-control" maxlength="255"/>
								</div>
							</div>
							<div class="forminator-deactivation-survey-option">
								<label for="forminator-technical_issues">
									<input type="radio"
										name="forminator_deactivation_reason"
										id="forminator-technical_issues"
										value="technical_issues"/>
									<?php esc_html_e( 'I’m having technical issues / the plugin is broken', 'forminator' ); ?>
								</label>
								<div class="forminator-deactivation-survey-message sui-hidden">
									<input placeholder="<?php esc_attr_e( 'What technical issue did you encounter? (optional)', 'forminator' ); ?>"
										name="technical_issues_message" class="sui-form-control" maxlength="255"/>
								</div>
							</div>
							<div class="forminator-deactivation-survey-option">
								<label for="forminator-missing_features">
									<input type="radio"
										name="forminator_deactivation_reason"
										id="forminator-missing_features"
										value="missing_features"/>
									<?php esc_html_e( 'It’s missing features I need', 'forminator' ); ?>
								</label>
								<div class="forminator-deactivation-survey-message sui-hidden">
									<input placeholder="<?php esc_attr_e( 'Which features are you looking for? (optional)', 'forminator' ); ?>"
										name="missing_features_message" class="sui-form-control" maxlength="255"/>
								</div>
							</div>
							<div class="forminator-deactivation-survey-option">
								<label for="forminator-other">
									<input type="radio"
										name="forminator_deactivation_reason"
										id="forminator-other"
										value="other"/>
									<?php esc_html_e( 'Other', 'forminator' ); ?>
								</label>
								<div class="forminator-deactivation-survey-message sui-hidden">
									<input placeholder="<?php esc_attr_e( 'Tell us more (optional)', 'forminator' ); ?>"
										name="other_message" class="sui-form-control" maxlength="255"/>
								</div>
							</div>
						</form>
					</div>
					<div class="sui-box-footer sui-content-separated">
						<button class="sui-button sui-button-ghost forminator-skip-and-deactivation">
							<span class="sui-button-text-default">
								<?php esc_html_e( 'Skip & Deactivate', 'forminator' ); ?>
							</span>
							<span class="sui-button-text-onload">
								<?php esc_html_e( 'Deactivating...', 'forminator' ); ?>
							</span>
						</button>
						<button class="sui-button sui-button-blue forminator-deactivate-button" disabled aria-live="polite">
							<span class="sui-button-text-default">
								<?php esc_html_e( 'Submit & Deactivate', 'forminator' ); ?>
							</span>
							<span class="sui-button-text-onload">
								<?php esc_html_e( 'Deactivating...', 'forminator' ); ?>
							</span>
						</button>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>