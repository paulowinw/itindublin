<?php
/**
 * Template admin/views/dashboard/widgets/widget-resume.php
 *
 * @package Forminator
 */

$total_modules           = forminator_total_forms();
$count_active            = forminator_total_forms( 'publish' );
$forms_total_submissions = Forminator_Form_Entry_Model::count_all_entries_by_type( 'custom-forms' );
$poll_total_submissions  = Forminator_Form_Entry_Model::count_all_entries_by_type( 'poll' );
$quiz_total_submissions  = Forminator_Form_Entry_Model::count_all_entries_by_type( 'quizzes' );
$last_submission         = forminator_get_latest_entry_time( 'all' );
?>

<div id="forminator-dashboard-box-summary" class="sui-box sui-summary <?php echo esc_attr( $this->get_box_summary_classes() ); ?>">

	<div class="sui-summary-image-space" aria-hidden="true" style="<?php echo esc_attr( $this->get_box_summary_image_style() ); ?>"></div>

	<div class="sui-summary-segment">

		<div class="sui-summary-details">

			<?php if ( 0 < $total_modules ) { ?>
				<span class="sui-summary-large"><?php echo esc_html( $count_active ); ?></span>
			<?php } else { ?>
				<span class="sui-summary-large">0</span>
			<?php } ?>

			<?php if ( 1 === $total_modules ) { ?>
				<span class="sui-summary-sub"><?php esc_html_e( 'Active Module', 'forminator' ); ?></span>
			<?php } else { ?>
				<span class="sui-summary-sub"><?php esc_html_e( 'Active Modules', 'forminator' ); ?></span>
			<?php } ?>

			<?php if ( $total_modules > 0 ) { ?>
				<span class="sui-summary-detail"><strong><?php echo esc_html( $last_submission ); ?></strong></span>
			<?php } else { ?>
				<span class="sui-summary-detail"><strong><?php esc_html_e( 'Never', 'forminator' ); ?></strong></span>
			<?php } ?>

			<span class="sui-summary-sub"><?php esc_html_e( 'Last Submission', 'forminator' ); ?></span>

		</div>

	</div>

	<div class="sui-summary-segment">

		<ul class="sui-list">

			<li>
				<span class="sui-list-label"><?php esc_html_e( 'Form Submissions', 'forminator' ); ?></span>
				<?php if ( $forms_total_submissions > 0 ) { ?>
					<span class="sui-list-detail"><?php echo esc_html( $forms_total_submissions ); ?></span>
				<?php } else { ?>
					<span class="sui-list-detail">0</span>
				<?php } ?>
			</li>

			<li>
				<span class="sui-list-label"><?php esc_html_e( 'Poll Submissions', 'forminator' ); ?></span>
				<?php if ( $poll_total_submissions > 0 ) { ?>
					<span class="sui-list-detail"><?php echo esc_html( $poll_total_submissions ); ?></span>
				<?php } else { ?>
					<span class="sui-list-detail">0</span>
				<?php } ?>
			</li>

			<li>
				<span class="sui-list-label"><?php esc_html_e( 'Quiz Submissions', 'forminator' ); ?></span>
				<?php if ( $quiz_total_submissions > 0 ) { ?>
					<span class="sui-list-detail"><?php echo esc_html( $quiz_total_submissions ); ?></span>
				<?php } else { ?>
					<span class="sui-list-detail">0</span>
				<?php } ?>
			</li>

		</ul>

	</div>

</div>
