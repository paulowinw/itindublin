<?php
/**
 * Template admin/views/settings/data/forms-privacy.php
 *
 * @package Forminator
 */

?>
<div class="sui-box-settings-row">

	<div class="sui-box-settings-col-1">
		<span class="sui-settings-label"><?php esc_html_e( 'Forms Privacy', 'forminator' ); ?></span>
		<span class="sui-description"><?php esc_html_e( 'Choose how you want to handle the forms data storage.', 'forminator' ); ?></span>
	</div>

	<div class="sui-box-settings-col-2">
		<?php
		$this->template(
			'settings/data/retention',
			array(
				'option_slug' => 'form_retain_submission',
				'forever'     => get_option( 'retain_submission_forever' ),
				'number'      => get_option( 'forminator_retain_submissions_interval_number', 0 ),
				'unit'        => get_option( 'forminator_retain_submissions_interval_unit', 'days' ),
				'title'       => esc_html__( 'Submissions Retention', 'forminator' ),
				'description' => esc_html__( 'How long do you want to retain the form submissions for?', 'forminator' ),
			)
		);

		$this->template(
			'settings/data/retention',
			array(
				'option_slug' => 'form_retain_ip',
				'forever'     => get_option( 'retain_ip_forever' ),
				'number'      => get_option( 'forminator_retain_ip_interval_number', 0 ),
				'unit'        => get_option( 'forminator_retain_ip_interval_unit', 'days' ),
				'title'       => esc_html__( 'IP Retention', 'forminator' ),
				'description' => esc_html__( 'Choose how long to retain IP address before a submission is anonymized.', 'forminator' ),
			)
		);

		if ( ! forminator_addons_disabled() ) {
			$this->template(
				'settings/data/retention',
				array(
					'option_slug' => 'form_retain_geolocation',
					'forever'     => get_option( 'retain_geolocation_forever' ),
					'number'      => get_option( 'forminator_retain_geolocation_interval_number', 0 ),
					'unit'        => get_option( 'forminator_retain_geolocation_interval_unit', 'days' ),
					'title'       => esc_html__( 'User’s Geolocation', 'forminator' ),
					'description' => esc_html__( 'Choose how long to retain user Geolocation data before submissions are anonymized.', 'forminator' ),
				)
			);
		}

		$form_submission_erasure_enabled = get_option( 'forminator_enable_erasure_request_erase_form_submissions', false );
		?>
		<span class="sui-settings-label"><?php esc_html_e( 'Account Erasure Requests', 'forminator' ); ?></span>
		<span class="sui-description">
			<?php
			printf(
			/* Translators: 1. Opening <a> tag with Erase personal data URL, 2. closing <a> tag. */
				esc_html__( 'When handling an %1$saccount erasure request%2$s that contains an email associated with a submission, what do you want to do?', 'forminator' ),
				'<a href="' . esc_url( admin_url( 'erase-personal-data.php' ) ) . '" target="_blank">',
				'</a>'
			);
			?>
		</span>

		<div class="sui-side-tabs" style="margin-top: 10px;">

			<div class="sui-tabs-menu">

				<label for="erase_form_submissions-false" class="sui-tab-item<?php echo $form_submission_erasure_enabled ? '' : ' active'; ?>">
					<input type="radio"
						name="erase_form_submissions"
						value="false"
						id="erase_form_submissions-false"
						<?php checked( $form_submission_erasure_enabled, false ); ?> />
					<?php esc_html_e( 'Retain Submission', 'forminator' ); ?>
				</label>

				<label for="erase_form_submissions-true" class="sui-tab-item<?php echo $form_submission_erasure_enabled ? ' active' : ''; ?>">
					<input type="radio"
						name="erase_form_submissions"
						value="true"
						id="erase_form_submissions-true"
						<?php checked( $form_submission_erasure_enabled, true ); ?> />
					<?php esc_html_e( 'Remove Submission', 'forminator' ); ?>
				</label>

			</div>

		</div>

	</div>

</div>
