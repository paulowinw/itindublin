<?php
/**
 * Template admin/views/settings/data/quizzes-privacy.php
 *
 * @package Forminator
 */

?>
<div class="sui-box-settings-row">

	<div class="sui-box-settings-col-1">
		<span class="sui-settings-label"><?php esc_html_e( 'Quiz Privacy', 'forminator' ); ?></span>
		<span class="sui-description"><?php esc_html_e( 'Choose how you want to handle the quizzes submissions.', 'forminator' ); ?></span>
	</div>

	<div class="sui-box-settings-col-2">
		<?php
		$this->template(
			'settings/data/retention',
			array(
				'option_slug' => 'quiz_retain_submission',
				'forever'     => get_option( 'quiz_retain_submission_forever' ),
				'number'      => get_option( 'forminator_retain_quiz_submissions_interval_number', 0 ),
				'unit'        => get_option( 'forminator_retain_quiz_submissions_interval_unit', 'days' ),
				'title'       => esc_html__( 'Submissions Retention', 'forminator' ),
				'description' => esc_html__( 'How long do you want to retain the quiz submissions for?', 'forminator' ),
			)
		);
		?>
	</div>

</div>
