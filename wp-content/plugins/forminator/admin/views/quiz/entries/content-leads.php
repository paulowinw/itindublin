<?php
/**
 * Template admin/views/quiz/entries/content-leads.php
 *
 * @package Forminator
 */

$form_type    = $this->get_form_type();
$url_entry_id = filter_input( INPUT_GET, 'entry_id', FILTER_VALIDATE_INT );
$url_entry_id = $url_entry_id ? $url_entry_id : 0;
foreach ( $this->entries_iterator() as $entries ) {

	$db_entry_id = isset( $entries['entry_id'] ) ? $entries['entry_id'] : '';
	$entry_date  = isset( $entries['entry_date'] ) ? $entries['entry_date'] : '';

	$summary       = $entries['summary'];
	$summary_items = $summary['items'];

	$detail       = $entries['detail'];
	$detail_items = $detail['items'];
	$quiz_entry   = $detail['quiz_entry'];
	$integrations = $detail['integrations'];
	// Open entry tab by received submission link.
	$cls_open_tab = $url_entry_id === (int) $db_entry_id ? 'sui-accordion-item--open' : '';
	?>

	<tr class="sui-accordion-item <?php echo esc_attr( $cls_open_tab ); ?>" data-entry-id="<?php echo esc_attr( $db_entry_id ); ?>">

		<?php foreach ( $summary_items as $key => $summary_item ) { ?>

			<?php
			if ( ! $summary['num_fields_left'] && ( count( $summary_items ) - 1 ) === $key ) :

				echo '<td>';

					echo esc_html( $summary_item['value'] );

					echo '<span class="sui-accordion-open-indicator">';

					echo '<span class="sui-icon-chevron-down"></span>';

					echo '</span>';

				echo '</td>';

			elseif ( 1 === $summary_item['colspan'] ) :

				echo '<td class="sui-accordion-item-title">';

					echo '<label class="sui-checkbox">';

						echo '<input type="checkbox" name="ids[]" value="' . esc_attr( $db_entry_id ) . '" id="quiz-answer-' . esc_attr( $db_entry_id ) . '" />';

						echo '<span aria-hidden="true"></span>';

						echo '<span class="sui-screen-reader-text">' . sprintf(
							/* translators: %s: Entry ID */
							esc_html__( 'Select entry number %s', 'forminator' ),
							esc_html( $db_entry_id )
						) . '</span>';

					echo '</label>';

					echo esc_html( $db_entry_id );

				echo '</td>';

			else :

				echo '<td>';

					echo esc_html( $summary_item['value'] );

					echo '<span class="sui-accordion-open-indicator fui-mobile-only" aria-hidden="true">';
						echo '<span class="sui-icon-chevron-down"></span>';
					echo '</span>';

				echo '</td>';

			endif;
			?>

		<?php } ?>

		<?php
		if ( $summary['num_fields_left'] ) {

			echo '<td>';
				echo '' . sprintf(
					/* translators: %s: number of other fields left */
					esc_html__( '+ %s other fields', 'forminator' ),
					esc_html( $summary['num_fields_left'] )
				) . '';
				echo '<span class="sui-accordion-open-indicator">';
					echo '<span class="sui-icon-chevron-down"></span>';
				echo '</span>';
			echo '</td>';

		}
		?>

	</tr>

	<tr class="sui-accordion-item-content">

		<td colspan="<?php echo esc_attr( $detail['colspan'] ); ?>">

			<div class="sui-box">

				<div class="sui-box-body fui-entries--knowledge">

					<?php // ROW: Title. ?>
					<div class="fui-entries-block">

						<h2 class="fui-entries-title"><?php echo '#' . esc_attr( $db_entry_id ); ?></h2>

						<p class="sui-description"><?php echo esc_html( $entry_date ); ?></p>

					</div>

					<?php // ROW: Lead Details. ?>
					<div class="fui-entries-block">

						<h3 class="fui-entries-subtitle"><?php esc_html_e( 'Lead Details', 'forminator' ); ?></h3>

						<?php if ( ! empty( $detail_items ) ) { ?>
							<?php foreach ( $detail_items as $detail_item ) { ?>
								<?php include_once forminator_plugin_dir() . 'admin/views/custom-form/entries/content-details.php'; ?>
								<?php forminator_submissions_content_details( $detail_item ); ?>
							<?php } ?>
						<?php } else { ?>

							<div
								role="alert"
								class="sui-notice sui-active"
								style="display: block; text-align: left;"
								aria-live="assertive"
							>

								<div class="sui-notice-content">

									<div class="sui-notice-message">

										<span class="sui-notice-icon sui-icon-info" aria-hidden="true"></span>

										<p><?php esc_html_e( 'Lead details are not available for this submission. Looks like the participant opted to skip the lead generation form while submitting the quiz.', 'forminator' ); ?></p>

									</div>

								</div>

							</div>

						<?php } ?>

					</div>

					<?php // ROW: Quiz Results. ?>
					<div class="fui-entries-block">

						<h3 class="fui-entries-subtitle"><?php esc_html_e( 'Quiz Results', 'forminator' ); ?></h3>

						<?php
						if ( ! empty( $quiz_entry ) ) {

							if ( 'knowledge' === $form_type ) {

								$meta  = isset( $quiz_entry['value'] ) ? $quiz_entry['value'] : array();
								$total = count( $meta );
								$right = 0;
								if ( ! empty( $meta ) ) {
									foreach ( $meta as $answer ) {
										if ( isset( $answer['isCorrect'] ) && $answer['isCorrect'] ) {
											++$right;
										}
									}
								}
								?>

								<p class="sui-description">
									<?php
									printf(
									/* translators: 1: Correct answer, 2: Total */
										esc_html__( 'You got %1$s/%2$s correct answers.', 'forminator' ),
										esc_html( $right ),
										esc_html( $total )
									);
									?>
								</p>

								<table class="fui-entries-table">

									<thead>

										<tr>
											<th><?php esc_html_e( 'Question', 'forminator' ); ?></th>
											<th><?php esc_html_e( 'Answer', 'forminator' ); ?></th>
										</tr>

									</thead>

									<tbody>

										<?php foreach ( $meta as $answer ) : ?>

											<?php $user_answer = $answer['answer']; ?>

											<tr>
												<td><strong><?php echo esc_html( $answer['question'] ); ?></strong></td>
												<td>
													<?php
													if ( $answer['isCorrect'] ) {
														echo '<span class="sui-tag sui-tag-success">' . esc_html( $user_answer ) . '</span>';
													} else {
														echo '<span class="sui-tag sui-tag-error">' . esc_html( $user_answer ) . '</span>';
													}
													?>
												</td>
											</tr>

										<?php endforeach; ?>

									</tbody>

									<tfoot aria-hidden="true">

										<tr>

											<td colspan="2">

												<div class="fui-entries-table-legend">

													<p class="correct"><?php esc_html_e( 'Correct', 'forminator' ); ?></p>

													<p class="incorrect"><?php esc_html_e( 'Incorrect', 'forminator' ); ?></p>

												</div>

											</td>

										</tr>

									</tfoot>

								</table>

							<?php } else { ?>

								<?php $meta = $quiz_entry['value'][0]['value']; ?>

								<?php if ( isset( $meta['answers'] ) && is_array( $meta['answers'] ) ) : ?>

									<table class="fui-entries-table">

										<thead>

											<tr>
												<th><?php esc_html_e( 'Question', 'forminator' ); ?></th>
												<th><?php esc_html_e( 'Answer', 'forminator' ); ?></th>
											</tr>

										</thead>

										<tbody>

											<?php foreach ( $meta['answers'] as $answer ) : ?>

												<tr>
													<td><strong><?php echo esc_html( $answer['question'] ); ?></strong></td>
													<td><?php echo esc_html( $answer['answer'] ); ?></td>
												</tr>

											<?php endforeach; ?>

										</tbody>

										<tfoot aria-hidden="true">

											<tr>

												<td colspan="2">
													<?php
													printf(
														'<strong>%1$s<strong> %2$s',
														esc_html__( 'Quiz Result:', 'forminator' ),
														esc_html( $meta['result']['title'] )
													);
													?>
												</td>

											</tr>

										</tfoot>

									</table>

								<?php endif; ?>

							<?php } ?>

						<?php } else { ?>
							<div
								role="alert"
								class="sui-notice sui-active"
								style="display: block; text-align: left;"
								aria-live="assertive"
							>

								<div class="sui-notice-content">

									<div class="sui-notice-message">

										<span class="sui-notice-icon sui-icon-info" aria-hidden="true"></span>

										<p><?php esc_html_e( 'Quiz results are not available for this submission. The participant either couldn\'t finish the quiz or had some errors while submitting the quiz.', 'forminator' ); ?></p>

									</div>

								</div>

							</div>
						<?php } ?>

					</div>

					<?php // ROW: Integrations. ?>
					<?php if ( ! empty( $integrations ) ) { ?>

						<div class="fui-entries-block">

							<h3 class="fui-entries-subtitle"><?php esc_html_e( 'Active Integrations', 'forminator' ); ?></h3>

							<p class="sui-description"><?php esc_html_e( 'Check the status and response of your active 3rd-party applications for this submissions.', 'forminator' ); ?></p>

							<div class="sui-accordion">

								<div class="sui-accordion-header">
									<div><?php esc_html_e( 'Integration Name', 'forminator' ); ?></div>
									<div><?php esc_html_e( 'Data send to integration', 'forminator' ); ?></div>
									<div></div>
								</div>

								<?php foreach ( $integrations as $integration ) : ?>

									<div class="sui-accordion-item">

										<div class="sui-accordion-item-header">

											<div class="fui-app--wrapper">
												<img
													src="<?php echo esc_url( $integration['banner'] ); ?>"
													srcset="<?php echo esc_url( $integration['banner'] ); ?> 1x, <?php echo esc_url( $integration['banner_x2'] ); ?> 2x"
													alt="<?php echo esc_attr( $integration['label'] ); ?>"
													class="sui-image"
													style="width: 20px; height: 20px;"
												/>
												<span style="margin-left: 10px;"><?php echo wp_kses_post( $integration['title'] ); ?></span>
											</div>

											<div>

												<?php
												$sub_entries = isset( $integration['sub_entries'] ) ? $integration['sub_entries'] : array();

												if ( ! empty( $sub_entries ) && is_array( $sub_entries ) ) :

													$success = isset( $sub_entries[1]['value'] ) ? $sub_entries[1]['value'] : '';
													?>

													<span class="integration-"<?php echo esc_attr( $success ); ?>><?php echo esc_html( $success ); ?></span>

												<?php else : ?>

													<span><?php echo wp_kses_post( $integration['value'] ); ?></span>

												<?php endif; ?>

											</div>

											<div>
												<span class="sui-button-icon sui-accordion-open-indicator" aria-label="Open item">
													<i class="sui-icon-chevron-down" aria-hidden="true"></i>
												</span>
											</div>

										</div>

										<div class="sui-accordion-item-body">

											<div class="sui-box">

												<div class="sui-box-body">

													<?php if ( ! empty( $sub_entries ) && is_array( $sub_entries ) ) { ?>

														<?php foreach ( $sub_entries as $sub_entry ) { ?>

															<div class="">
																<span class="sui-settings-label"><?php echo esc_html( $sub_entry['label'] ); ?></span>
																<span class="sui-description"><?php echo wp_kses_post( $sub_entry['value'] ); ?></span>
															</div>

														<?php } ?>

													<?php } ?>

												</div>

											</div>

										</div>

									</div>

								<?php endforeach; ?>

							</div>

						</div>

					<?php } ?>

				</div>

				<div class="sui-box-footer">

					<button
							type="button"
							class="sui-button sui-button-ghost sui-button-red wpmudev-open-modal"
						<?php
						if ( isset( $entries['activation_key'] ) ) {
							$button_title      = esc_html__( 'Delete Submission & User', 'forminator' );
							$is_activation_key = true;
							?>
							data-activation-key="<?php echo esc_attr( $entries['activation_key'] ); ?>"
							data-modal="delete-unconfirmed-user-module"
							data-entry-id="<?php echo esc_attr( $db_entry_id ); ?>"
							data-form-id="<?php echo esc_attr( $this->model->id ); ?>"
							<?php
						} else {
							$button_title      = esc_html__( 'Delete', 'forminator' );
							$is_activation_key = false;
							?>
							data-modal="delete-module"
							data-form-id="<?php echo esc_attr( $db_entry_id ); ?>"
						<?php } ?>
							data-modal-title="<?php esc_attr_e( 'Delete Submission', 'forminator' ); ?>"
							data-modal-content="<?php esc_attr_e( 'Are you sure you wish to permanently delete this submission?', 'forminator' ); ?>"
							data-nonce="<?php echo esc_attr( wp_create_nonce( 'forminatorQuizEntries' ) ); ?>"
					>
						<i class="sui-icon-trash" aria-hidden="true"></i> <?php echo wp_kses_post( $button_title ); ?>
					</button>

				</div>

			</div>

		</td>

	</tr>

<?php } ?>
