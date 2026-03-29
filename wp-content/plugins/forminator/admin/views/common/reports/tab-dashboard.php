<?php
/**
 * Template admin/views/common/reports/tab-dashboard.php
 *
 * @package Forminator
 */

$count     = forminator_total_forms( 'publish' );
$section   = Forminator_Core::sanitize_text_field( 'section', 'dashboard' );
$nonce     = wp_create_nonce( 'forminator_save_dashboard_settings' );
$form_id   = filter_input( INPUT_GET, 'form_id', FILTER_VALIDATE_INT );
$form_type = Forminator_Core::sanitize_text_field( 'form_type' );
if ( empty( $form_type ) ) {
	$form_type = 'forminator_forms';
}
$reports = Forminator_Admin_Report_Page::get_instance()->forminator_report_data( $form_id, $form_type );
?>
	<div class="sui-box-reports" data-nav="dashboard" style="<?php echo esc_attr( 'dashboard' !== $section ? 'display: none;' : '' ); ?>">
	<?php if ( $count > 0 ) { ?>
		<div class="sui-box sui-box-sticky">
			<div class="fui-entries-bar">
				<div class="fui-bar-selectors">
					<form method="get" name="bulk-action-form">
						<input type="hidden" name="page" value="forminator-reports"/>
						<select
							name="form_type"
							onchange="submit()"
							class="sui-select sui-select-sm fui-bar-selectors__module"
							data-placeholder="<?php esc_html_e( 'Type', 'forminator' ); ?>"
							data-search="false"
						>
							<option></option>
							<?php foreach ( $this->get_form_types() as $form_post_type => $name ) { ?>
								<option value="<?php echo esc_attr( $form_post_type ); ?>" <?php echo selected( $form_post_type, $form_type ); ?>><?php echo esc_html( $name ); ?></option>
							<?php } ?>
						</select>
						<?php Forminator_Reports_Page::render_form_switcher( $form_type, $form_id ); ?>
						<button class="sui-button sui-button-blue view-reports"
							onclick="submit()"><?php esc_html_e( 'View Reports', 'forminator' ); ?></button>
					</form>
				</div>

				<div class="sui-actions-right">
					<div class="sui-form-field">
						<label for="forminator-forms-filter--by-date" id="unique-id--label"
								class="sui-label"><?php esc_html_e( 'Date range', 'forminator' ); ?></label>

						<div class="sui-control-with-icon">
							<input type="text"
									placeholder="<?php esc_html_e( 'Select date range', 'forminator' ); ?>"
									id="forminator-forms-filter--by-date"
									name="date_range"
									autocomplete="off"
									data-id="<?php echo intval( $form_id ); ?>"
									data-nonce="<?php echo esc_attr( wp_create_nonce( 'forminator_filter_report_data' ) ); ?>"
									class="sui-form-control forminator-reports-filter-date"/>
							<span class="sui-icon-calendar" aria-hidden="true"></span>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
		if ( ! empty( $form_id ) ) {
			$report_args = array(
				'form_id'   => $form_id,
				'form_type' => $form_type,
				'reports'   => $reports,
			);
			$this->template( 'common/reports/report-content', $report_args );
		} else {
			$data_arg = array(
				'title'       => esc_html__( 'Almost there!', 'forminator' ),
				'description' => esc_html__( 'Select a form, poll, or quiz to view its report.', 'forminator' ),
				'image'       => 'forminator-info.png',
				'image_x2'    => 'forminator-info@2x.png',
			);
			$this->template( 'common/reports/content-none', $data_arg );
		}
	} else {
		$data_arg = array(
			'title'       => esc_html__( 'Oops! Nothing to show', 'forminator' ),
			'description' => esc_html__( 'You haven\'t created any forms, polls, or quizzes yet. When you do, you\'ll be able to view their reports here.', 'forminator' ),
			'image'       => 'forminator-warning.png',
			'image_x2'    => 'forminator-warning@2x.png',
		);
		$this->template( 'common/reports/content-none', $data_arg );
	}
	?>
</div>
