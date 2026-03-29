<?php
/**
 * Template admin/views/settings/data/polls-privacy.php
 *
 * @package Forminator
 */

?>
<div class="sui-box-settings-row">

	<div class="sui-box-settings-col-1">
		<span class="sui-settings-label"><?php esc_html_e( 'Poll Privacy', 'forminator' ); ?></span>
		<span class="sui-description"><?php esc_html_e( 'Choose how you want to handle the polls data storage.', 'forminator' ); ?></span>
	</div>

	<div class="sui-box-settings-col-2">
		<?php
		$this->template(
			'settings/data/retention',
			array(
				'option_slug' => 'poll_retain_submission',
				'forever'     => get_option( 'poll_retain_submission_forever' ),
				'number'      => get_option( 'forminator_retain_poll_submissions_interval_number', 0 ),
				'unit'        => get_option( 'forminator_retain_poll_submissions_interval_unit', 'days' ),
				'title'       => esc_html__( 'Submissions Retention', 'forminator' ),
				'description' => esc_html__( 'How long do you want to retain the poll\'s submissions for?', 'forminator' ),
			)
		);

		$this->template(
			'settings/data/retention',
			array(
				'option_slug' => 'poll_retain_ip',
				'forever'     => get_option( 'retain_poll_forever' ),
				'number'      => get_option( 'forminator_retain_votes_interval_number', 0 ),
				'unit'        => get_option( 'forminator_retain_votes_interval_unit', 'days' ),
				'title'       => esc_html__( 'IP Retention', 'forminator' ),
				'description' => esc_html__( 'Choose how long to retain IP address before a submission is anonymized. Keep in mind that the IP address is being used in checking multiple votes from same user.', 'forminator' ),
			)
		);
		?>
	</div>

</div>

