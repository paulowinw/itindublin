<?php
/**
 * Template admin/views/poll/entries/content.php
 *
 * @package Forminator
 */

$count = Forminator_Form_Entry_Model::count_entries( $this->form_id );

$poll_question    = Forminator_Poll_Front::get_poll_question( $this->model );
$poll_description = Forminator_Poll_Front::get_poll_description( $this->model );

$custom_votes = $this->map_custom_votes();
?>

<?php
if ( $this->error_message() ) {
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is already escaped.
	echo Forminator_Admin::get_red_notice( esc_html( $this->error_message() ) );
}
?>

<?php if ( $count > 0 ) : ?>

	<div class="sui-box sui-poll-submission">

		<div class="sui-box-body">

			<?php $this->template( 'common/entries/prompt', array( 'submissions' => $count ) ); ?>

			<div class="sui-block-content-center">
				<?php if ( ! empty( $poll_question ) ) { ?>

					<h2><?php echo wp_kses_post( $poll_question ); ?></h2>

				<?php } ?>

				<?php if ( ! empty( $poll_description ) ) { ?>

					<p><?php echo wp_kses_post( $poll_description ); ?></p>

				<?php } ?>
			</div>

		</div>

		<div class="sui-box-body">

			<canvas id="forminator-chart-poll" role="img" style="max-width: 800px; margin: 0 auto;"></canvas>

			<?php if ( ! empty( $custom_votes ) && count( $custom_votes ) > 0 ) { ?>

				<?php
				foreach ( $custom_votes as $element_id => $custom_vote ) {

					echo '<div style="margin-top: 30px;">';

						echo '<label class="sui-label">' . esc_html( $this->get_field_title( $element_id ) ) . '</label>';

						echo '<div style="margin-top: 10px;">';

					foreach ( $custom_vote as $answer => $vote ) {
						echo '<span class="sui-tag">' . /* translators: 1: Number of vote, 2: Answer */ esc_html( sprintf( _n( '%1$s (%2$s) vote', '%1$s (%2$s) votes', $vote, 'forminator' ), $answer, $vote ) ) . '</span>';
					}

						echo '</div>';

					echo '</div>';

				}
				?>

			<?php } ?>

		</div>

		<div class="sui-box-footer">

			<div class="sui-actions-right">

				<button
						type="button"
						class="sui-button sui-button-ghost wpmudev-open-modal"
						data-modal="delete-poll-submission"
						data-modal-title="<?php esc_attr_e( 'Delete Submissions', 'forminator' ); ?>"
						data-modal-content="<?php esc_attr_e( 'Are you sure you wish to delete the submissions on this poll?', 'forminator' ); ?>"
						data-form-id="<?php echo esc_attr( $this->form_id ); ?>"
						data-nonce="<?php echo esc_attr( wp_create_nonce( 'forminatorPollEntries' ) ); ?>"
				>
					<i class="sui-icon-trash" aria-hidden="true"></i> <?php esc_html_e( 'Delete Submissions', 'forminator' ); ?>
				</button>

			</div>

		</div>

	</div>

<?php else : ?>

	<?php include_once forminator_plugin_dir() . 'admin/views/common/entries/content-none.php'; ?>
	<?php
endif;
