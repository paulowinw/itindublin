<?php
/**
 * Template admin/views/common/reports/tab-notification.php
 *
 * @package Forminator
 */

$count           = forminator_total_forms( 'publish' );
$section         = Forminator_Core::sanitize_text_field( 'section', 'dashboard' );
$report_instance = Forminator_Admin_Report_Page::get_instance();
$notifications   = $report_instance->fetch_reports();
?>
<div class="sui-box-reports forminator-notifications" data-nav="notification"
	style="<?php echo esc_attr( 'notification' !== $section ? 'display: none;' : '' ); ?>">
	<div class="sui-box">
		<div class="sui-box-header">
			<h2 class="sui-box-title"><?php esc_html_e( 'Notifications', 'forminator' ); ?></h2>
		</div>
		<div class="sui-box-body">

			<p>
			<?php
			esc_html_e(
				'Keep an eye on the performance of your forms, quizzes, and polls.
                Get daily, weekly or monthly report notifications sent directly
                to your inbox.',
				'forminator'
			);
			?>
			</p>
			<?php if ( $count > 0 ) { ?>
				<button class="sui-button sui-button-blue wpmudev-button-open-modal"
						data-modal="configure-report"
						data-id="0">
					<i class="sui-icon-plus" aria-hidden="true"></i>
					<?php esc_html_e( 'Add New report', 'forminator' ); ?>
				</button>
				<?php if ( ! empty( $notifications ) ) { ?>
				<div class="notification-action">
					<form method="post" name="report-bulk-action">
						<input type="hidden" name="forminatorNonce" value="<?php echo esc_attr( wp_create_nonce( 'forminator-report-action' ) ); ?>">
						<input type="hidden" name="_wp_http_referer" value="<?php echo esc_url( admin_url( 'admin.php?page=forminator-reports&section=notification' ) ); ?>">
						<input type="hidden" name="ids" value="">
						<input type="hidden" name="page" value="forminator-reports">
						<select name="forminator_action" data-width="140px" class="sui-select sui-select-sm sui-select-inline select2-hidden-accessible sui-screen-reader-text" >
							<option value=""><?php esc_html_e( 'Bulk Actions', 'forminator' ); ?></option>
							<option value="bulk-active"><?php esc_html_e( 'Activate', 'forminator' ); ?></option>
							<option value="bulk-inactive"><?php esc_html_e( 'Deactivate', 'forminator' ); ?></option>
							<option value="bulk-delete"><?php esc_html_e( 'Delete', 'forminator' ); ?></option>
						</select>
						<button class="sui-button">
							<span class="sui-loading-text"><?php esc_html_e( 'Apply', 'forminator' ); ?></span>
						</button>
					</form>
				</div>
					<?php
				} else {
					$args = array(
						'content' => esc_html__( 'No report has been added to your scheduled report yet. Click on the button above to add one now.', 'forminator' ),
					);
					$this->template( 'common/reports/notice', $args );
				}
			} else {
				$args = array(
					'content' => esc_html__( 'You haven\'t created any forms, polls, or quizzes yet. When you do, you’ll be able to schedule notifications here.', 'forminator' ),
				);
				$this->template( 'common/reports/notice', $args );
			}
			?>
		</div>
		<?php if ( $count > 0 && ! empty( $notifications ) ) { ?>
		<table class="sui-table sui-table-flushed" id="forminator-reports-list">
			<thead>
				<tr>
					<th colspan="2">
						<label for="forminator-checked-all-reports" class="sui-checkbox">
							<input type="checkbox" id="forminator-checked-all-reports">
							<span aria-hidden="true"></span>
							<span><?php esc_html_e( 'Report', 'forminator' ); ?></span>
							<span class="sui-screen-reader-text">
								<?php esc_html_e( 'Select all notifications', 'forminator' ); ?>
							</span>
						</label>
					</th>
					<th colspan="1"><?php esc_html_e( 'Module', 'forminator' ); ?></th>
					<th colspan="2"><?php esc_html_e( 'Recipients', 'forminator' ); ?></th>
					<th colspan="3"><?php esc_html_e( 'Schedule', 'forminator' ); ?></th>
				</tr>
			</thead>

			<tbody>
			<?php
			foreach ( $notifications as $notification ) {
				$report_id       = $notification->report_id;
				$report_value    = Forminator_Core::sanitize_array( maybe_unserialize( $notification->report_value ) );
				$module          = ! empty( $report_value['settings']['module'] ) ? $report_value['settings']['module'] : 'forms';
				$module_type     = isset( $report_value['settings'][ $module . '_type' ] )
					? $report_value['settings'][ $module . '_type' ] : '';
				$module_selected = isset( $report_value['settings'][ 'selected_' . $module ] )
					? $report_value['settings'][ 'selected_' . $module ] : 0;
				?>
				<tr class="forminator-default">
				<td colspan="2">
					<div class="sui-tooltip sui-tooltip-constrained" style="--tooltip-width: 164px;" data-tooltip="<?php echo esc_html( $report_value['settings']['label'] ); ?>">
						<div class="report-name-wrapper">
							<label for="report-<?php echo esc_html( $report_id ); ?>" class="sui-checkbox">
								<input type="checkbox" class="report-checkbox" id="report-<?php echo esc_html( $report_id ); ?>"
									value="<?php echo esc_html( $report_id ); ?>">
								<span aria-hidden="true"></span>
								<span class="sui-screen-reader-text">
									<?php echo esc_html( $report_value['settings']['label'] ); ?>
								</span>
							</label>
							<div>
								<div class="sui-table-item-title report-name">
									<?php echo esc_html( $report_value['settings']['label'] ); ?>
								</div>
								<div>
									<?php
									echo 'all' === $module_type
										? /* translators: %s: Module name */ sprintf( esc_html__( 'All %s', 'forminator' ), esc_html( $module ) )
										: /* translators: %s: Module name */ sprintf( esc_html__( 'Selected %s', 'forminator' ), esc_html( $module ) );
									?>
									<span class="sui-tag sui-tag-sm">
										<?php
										if ( 'all' === $module_type ) {
											echo absint( $report_instance->get_total_forms( $module ) );
										} else {
											echo ! empty( $module_selected ) ? absint( count( $module_selected ) ) : 0;
										}
										?>
									</span>
								</div>
							</div>
						</div>
					</div>
				</td>
				<td colspan="1"><span class="sui-tag"><?php echo esc_html( ucfirst( $module ) ); ?></span></td>
				<td colspan="2">
					<?php if ( ! empty( $report_value['recipients'] ) ) { ?>
						<div class="subscriber-wrapper">
						<?php
						$i = 0;
						foreach ( $report_value['recipients'] as $recipient ) {
							if ( empty( $recipient ) || ! is_array( $recipient ) ) {
								continue;
							}
							if ( $i++ > 2 ) {
								break;
							}
							?>
							<div class="sui-tooltip" data-tooltip="<?php echo esc_html( $recipient['email'] ); ?>">
								<span class="subscriber inline-block subscribed">
									<img src="<?php echo esc_html( $recipient['avatar'] ); ?>" alt="<?php echo esc_html( $recipient['email'] ); ?>">
								</span>
							</div>
							<?php
						}
						if ( $i > 3 ) {
							printf( /* translators: %d - number of recipients */
								esc_html__( '+%d more', 'forminator' ),
								absint( count( $report_value['recipients'] ) ) - 3
							);
						}
						?>
						</div>
						<?php
					}
					?>
				</td>
				<td colspan="3">
					<div class="schedule">
						<div class="schedule-date">
						<?php
							$schedule_time = forminator_get_schedule_time( $report_value['schedule'] );
							echo esc_html( $schedule_time );
						?>
						</div>
						<?php $tooltip_action = 'active' === $notification->status ? esc_html__( 'Deactivate report', 'forminator' ) : esc_html__( 'Activate report', 'forminator' ); ?>
						<div class="sui-tooltip sui-tooltip-constrained report-status-tooltip" style="--tooltip-width: 164px;" data-tooltip="<?php echo esc_html( $tooltip_action ); ?>">
							<label for="notification-status-<?php echo esc_html( $report_id ); ?>" class="sui-toggle">
								<input type="checkbox" class="notification-status" value="<?php echo esc_html( $report_id ); ?>"
										id="notification-status-<?php echo esc_html( $report_id ); ?>"
										aria-labelledby="notification-status-<?php echo esc_html( $report_id ); ?>-label"
										<?php echo 'active' === $notification->status ? 'checked' : ''; ?>>
								<span class="sui-toggle-slider" aria-hidden="true"></span>
								<span id="notification-status-<?php echo esc_html( $report_id ); ?>-label" class="sui-screen-reader-text"><?php esc_html_e( 'Toggle Label', 'forminator' ); ?></span>
							</label>
						</div>
						<div class="sui-dropdown">
							<button class="sui-button-icon sui-dropdown-anchor" aria-label="Dropdown">
								<span class="sui-icon-widget-settings-config" aria-hidden="true"></span>
							</button>
							<ul>
								<li><button class="wpmudev-open-modal"
											data-modal="configure-report"
											data-id="<?php echo esc_html( $report_id ); ?>">
										<i class="sui-icon-widget-settings-config" aria-hidden="true"></i>
										<?php esc_html_e( 'Configure', 'forminator' ); ?>
									</button>
								</li>
								<li>
									<button class="sui-option-red wpmudev-open-modal" data-modal="delete-report"
											data-modal-title="<?php esc_html_e( 'Delete Report', 'forminator' ); ?>"
											data-modal-content="<?php esc_html_e( 'Are you sure you wish to permanently delete this report?', 'forminator' ); ?>"
											data-form-id="<?php echo esc_html( $report_id ); ?>"
											data-action="delete-report"
											data-nonce="<?php echo esc_attr( wp_create_nonce( 'forminator-report-action' ) ); ?>">
										<i class="sui-icon-trash" aria-hidden="true"></i>
										<?php esc_html_e( 'Delete Report', 'forminator' ); ?>
									</button>
								</li>
							</ul>
						</div>
					</div>
				</td>
			</tr>
			<?php } ?>
			</tbody>
		</table>
		<div class="sui-box-footer"></div>
		<?php } ?>
	</div>
</div>
