<?php
/**
 * Template admin/views/common/reports/report-content.php
 *
 * @package Forminator
 */

if ( empty( $args['reports'] ) ) {
	return;
}
$reports     = $args['reports'];
$report_data = Forminator_Admin_Report_Page::get_instance()->forminator_report_array( $reports, $args['form_id'] );
?>
<div class="sui-tab-content">
	<?php
	$chart_data = array(
		'form_id'         => $args['form_id'],
		'form_type'       => $args['form_type'],
		'last_entry_time' => $reports['last_entry_time'],
		'start_date'      => $reports['start_date'],
		'end_date'        => $reports['end_date'],
		'reports'         => $report_data,
	);
	$this->template( 'common/reports/chart-content', $chart_data );
	?>
	<div class="sui-row forminator-reports-box">
		<div class="sui-col-md-6">
			<?php
			// Views box.
			Forminator_Admin_Addons_Page::get_instance()->render_template(
				'admin/views/common/reports/single-report',
				array(
					'title'       => esc_html__( 'Views', 'forminator' ),
					'description' => esc_html__( 'Views for the selected period.', 'forminator' ),
					'data_class'  => 'views',
					'icon'        => 'sui-icon-eye',
					'attrs'       => $report_data['views'],
				)
			);

			if ( forminator_global_tracking() ) {
				// Conversion rate box.
				Forminator_Admin_Addons_Page::get_instance()->render_template(
					'admin/views/common/reports/single-report',
					array(
						'title'       => esc_html__( 'Conversion Rate', 'forminator' ),
						'description' => esc_html__( 'Conversion rate for the selected period.', 'forminator' ),
						'icon'        => 'sui-icon forminator-icon-conversion',
						'data_class'  => 'conversion',
						'attrs'       => $report_data['conversion'],
					)
				);
			}

			// Payment box.
			if ( 'forminator_forms' === $args['form_type'] && ! forminator_payments_disabled() ) {
				Forminator_Admin_Addons_Page::get_instance()->render_template(
					'admin/views/common/reports/single-report',
					array(
						'title'            => esc_html__( 'Payments', 'forminator' ),
						'description'      => esc_html__( 'Payments collected over the selected period.', 'forminator' ),
						'icon'             => 'sui-icon forminator-icon-payment',
						'data_class'       => 'payment',
						'attrs'            => $report_data['payment'],
						'has_payment'      => Forminator_Admin_Report_Page::has_payments( $args['form_id'] ),
						'has_live_payment' => Forminator_Admin_Report_Page::has_live_payments( $args['form_id'] ),
					)
				);
			}
			// Submission box.
			if ( isset( $report_data['leads'] ) && ! empty( $report_data['leads'] ) ) {
				Forminator_Admin_Addons_Page::get_instance()->render_template(
					'admin/views/common/reports/single-report',
					array(
						'title'       => esc_html__( 'Leads', 'forminator' ),
						'description' => esc_html__( 'Leads generated within the selected period.', 'forminator' ),
						'icon'        => 'sui-icon-clipboard-notes',
						'data_class'  => 'leads',
						'attrs'       => $report_data['leads'],
					)
				);
			}
			?>
		</div>
		<div class="sui-col-md-6">
			<?php
			// Submission box.
			Forminator_Admin_Addons_Page::get_instance()->render_template(
				'admin/views/common/reports/single-report',
				array(
					'title'       => esc_html__( 'Submissions', 'forminator' ),
					'description' => esc_html__( 'Submissions for the selected period.', 'forminator' ),
					'icon'        => 'sui-icon-clipboard-notes',
					'data_class'  => 'entries',
					'title_text'  => esc_html__( 'View submissions', 'forminator' ),
					'title_link'  => esc_url( admin_url( 'admin.php?page=forminator-entries&form_type=' . forminator_get_prefix( $args['form_type'] ) . '&form_id=' . $args['form_id'] ) ),
					'attrs'       => $report_data['entries'],
				)
			);

			// Abandonment box.
			if ( 'forminator_forms' === $args['form_type'] && ! forminator_form_abandonment_disabled() ) {
				$abandonment_status = Forminator_Abandonment::get_abandoned_status( $args['form_id'] );

				if ( 'active' === $abandonment_status ) {
					// TODO: Move it to the Free Add-on.
					$vars = array(
						'title'       => esc_html__( 'Form Abandonment', 'forminator' ),
						'description' => esc_html__( 'Form abandonment for the selected period.', 'forminator' ),
						'icon'        => 'sui-icon-tracking-disabled',
						'title_text'  => esc_html__( 'View entries', 'forminator' ),
						'title_link'  => esc_url( admin_url( 'admin.php?page=forminator-entries&form_type=' . forminator_get_prefix( $args['form_type'] ) . '&form_id=' . $args['form_id'] . '&entry_status=abandoned' ) ),
						'data_class'  => 'abandoned',
						'data'        => array(
							array(
								'title' => esc_html__( 'Abandoned', 'forminator' ),
								'attrs' => $report_data['abandoned'],
							),
							array(
								'title' => esc_html__( 'Drop-off Rate', 'forminator' ),
								'attrs' => $report_data['drop_off'],
							),
						),
						'attrs'       => $report_data['abandoned'],
					);
				} else {
					if ( 'inactive' === $abandonment_status ) {
						$notice = sprintf(
							/* translators: 1. Open link tag. 2. Close link tag. */
							__( 'Form Abandonment is currently disabled for this form. Go to %1$sForm Abandonment%2$s to enable it and start tracking partial entries and viewing related stats here.', 'forminator' ),
							'<a href="' . esc_url( admin_url( 'admin.php?page=forminator-cform-wizard&id=' . $args['form_id'] . '&gotosection=abandonment' ) ) . '" target="_blank">',
							'</a>'
						);
					} else {
						$abandonment_link = Forminator_Abandonment::get_abandoned_cta_link( $abandonment_status, 'report_widget' );
						$notice           = __( 'Collect partial entries if users exit without submission.', 'forminator' ) . ' ' . $abandonment_link;
						$notice           = str_replace( 'sui-tooltip ', '', $notice ); // Remove tooltip from notices.
					}
					$vars = apply_filters(
						'forminator_reports_abandonment_widget',
						array(
							'id'          => 'forminator_reports_abandonment_widget',
							'title'       => __( 'Form Abandonment', 'forminator' ),
							'description' => __( 'Form abandonment for the selected period.', 'forminator' ),
							'icon'        => 'sui-icon-tracking-disabled',
							'notice'      => $notice,
						),
						$args
					);
				}

				Forminator_Admin_Addons_Page::get_instance()->render_template(
					! empty( $vars['notice'] ) ? 'admin/views/common/reports/basic-widget' : 'admin/views/common/reports/single-report',
					$vars
				);
			}

			// Integration box.
			Forminator_Admin_Addons_Page::get_instance()->render_template(
				'admin/views/common/reports/integration-report',
				array(
					'form_id'      => $args['form_id'],
					'integrations' => ! empty( $report_data['integration'] ) ? $report_data['integration'] : array(),
					'app_link'     => Forminator_Admin_Report_Page::get_instance()->get_app_link_module_id( $args['form_id'], $args['form_type'] ),
				)
			);

			if ( ! forminator_addons_disabled() && 'forminator_forms' === $args['form_type'] ) {
				// Geolocation widget.
				$vars = apply_filters(
					'forminator_reports_geolocation_widget',
					array(
						'id'          => 'forminator_report_geolocation_widget',
						'title'       => __( 'Location', 'forminator' ),
						'description' => __( 'Summary of users\' locations.', 'forminator' ),
						'icon'        => 'sui-icon-pin',
						'notice'      => sprintf(
							/* translators: 1. Open link tag. 2. Close link tag. */
							__( 'Install the %1$sGeolocation Add-on%2$s to view the locations from where your form submissions are from.', 'forminator' ),
							'<a href="' . esc_url( menu_page_url( 'forminator-addons', false ) ) . '" target="_blank">',
							'</a>'
						),
					),
					$args
				);

				Forminator_Admin_Addons_Page::get_instance()->render_template(
					'admin/views/common/reports/basic-widget',
					$vars
				);
			}
			?>
		</div>
	</div>
</div>
