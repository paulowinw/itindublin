<?php
/**
 * Template admin/views/settings/tab-dashboard.php
 *
 * @package Forminator
 */

?>
<div id="forminator-section-settings-dashboard" class="sui-box-settings-row">

	<div class="sui-box-settings-col-1">
		<span class="sui-settings-label"><?php esc_html_e( 'Dashboard', 'forminator' ); ?></span>
		<span class="sui-description"><?php esc_html_e( 'Customize the Forminator dashboard as per your liking.', 'forminator' ); ?></span>
	</div>

	<div class="sui-box-settings-col-2">

		<label class="sui-settings-label"><?php esc_html_e( 'Modules Listing', 'forminator' ); ?></label>

		<span class="sui-description" style="margin-bottom: 10px;"><?php esc_html_e( 'Choose the number of modules by module type and/or by status that you want displayed on the dashboard.', 'forminator' ); ?></span>

		<div class="sui-tabs sui-tabs-overflow">

			<div role="tablist" class="sui-tabs-menu">
				<button type="button" role="tab" id="dashboard-forms-tab" class="sui-tab-item active" aria-controls="dashboard-forms-panel" aria-selected="true"><?php esc_html_e( 'Forms', 'forminator' ); ?></button>
				<button type="button" role="tab" id="dashboard-polls-tab" class="sui-tab-item" aria-controls="dashboard-polls-panel" aria-selected="false" tabindex="-1"><?php esc_html_e( 'Polls', 'forminator' ); ?></button>
				<button type="button" role="tab" id="dashboard-quizzes-tab" class="sui-tab-item" aria-controls="dashboard-quizzes-panel" aria-selected="false" tabindex="-1"><?php esc_html_e( 'Quizzes', 'forminator' ); ?></button>
			</div>

			<div class="sui-tabs-content">

				<?php // TAB: Forms. ?>
				<div tabindex="0" role="tabpanel" id="dashboard-forms-panel" class="forms-content sui-tab-content active" aria-labelledby="dashboard-forms-tab">
					<?php
					$this->template(
						'settings/dashboard/content',
						array(
							'module_type'  => 'forms',
							'title'        => esc_html__( 'Number of Forms', 'forminator' ),
							'description'  => esc_html__( 'Choose the number of recent forms to be shown on your dashboard.', 'forminator' ),
							'status_descr' => esc_html__( 'By default, all forms, regardless of status, are displayed on the dashboard. Use this setting to display the forms with a specific status only.', 'forminator' ),
						)
					);
					?>
				</div>

				<?php // TAB: Polls. ?>
				<div tabindex="0" role="tabpanel" id="dashboard-polls-panel" class="polls-content sui-tab-content" aria-labelledby="dashboard-polls-tab" hidden>
					<?php
					$this->template(
						'settings/dashboard/content',
						array(
							'module_type'  => 'polls',
							'title'        => esc_html__( 'Number of Polls', 'forminator' ),
							'description'  => esc_html__( 'Choose the number of recent polls to be shown on your dashboard.', 'forminator' ),
							'status_descr' => esc_html__( 'By default, all polls, regardless of status, are displayed on the dashboard. Use this setting to display the polls with a specific status only.', 'forminator' ),
						)
					);
					?>
				</div>

				<?php // TAB: Quizzes. ?>
				<div tabindex="0" role="tabpanel" id="dashboard-quizzes-panel" class="quizzes-content sui-tab-content" aria-labelledby="dashboard-quizzes-tab" hidden>
					<?php
					$this->template(
						'settings/dashboard/content',
						array(
							'module_type'  => 'quizzes',
							'title'        => esc_html__( 'Number of Quizzes', 'forminator' ),
							'description'  => esc_html__( 'Choose the number of recent quizzes to be shown on your dashboard.', 'forminator' ),
							'status_descr' => esc_html__( 'By default, all quizzes, regardless of status, are displayed on the dashboard. Use this setting to display the quizzes with a specific status only.', 'forminator' ),
						)
					);
					?>
				</div>

			</div>

		</div>

	</div>

</div>
