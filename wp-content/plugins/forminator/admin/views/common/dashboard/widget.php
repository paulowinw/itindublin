<?php
/**
 * Template admin/views/common/dashboard/widget.php
 *
 * @package Forminator
 */

$_per_page          = forminator_form_view_per_page();
$module_type        = forminator_get_prefix( $module_slug, 'c' );
$export_dialog      = 'export_' . $module_slug;
$preview_dialog     = 'preview_' . forminator_get_prefix( $module_slug, 'c', false, true );
$create_dialog      = forminator_get_prefix( $module_slug, 'custom_', false, true );
$dashboard_settings = forminator_get_dashboard_settings( forminator_get_prefix( $module_slug, '', false, true ), array() );
$num_recent         = isset( $dashboard_settings['num_recent'] ) ? $dashboard_settings['num_recent'] : 5;
$published          = isset( $dashboard_settings['published'] ) ? filter_var( $dashboard_settings['published'], FILTER_VALIDATE_BOOLEAN ) : true;
$draft              = isset( $dashboard_settings['draft'] ) ? filter_var( $dashboard_settings['draft'], FILTER_VALIDATE_BOOLEAN ) : true;
$statuses           = array();

if ( $published ) {
	$statuses[] = Forminator_Base_Form_Model::STATUS_PUBLISH;
}

if ( $draft ) {
	$statuses[] = Forminator_Base_Form_Model::STATUS_DRAFT;
}

if ( 0 === $num_recent ) {
	return;
}

$method  = 'get_' . forminator_get_prefix( $module_slug, '', false, true );
$modules = Forminator_API::$method( null, 1, $num_recent, $statuses );
?>

<div id="forminator-dashboard-box-recent-<?php echo esc_attr( $module_slug ); ?>" class="sui-box">

	<div class="sui-box-header">

		<h3 class="sui-box-title"><i class="<?php echo esc_attr( $icon ); ?>" aria-hidden="true"></i><?php echo esc_html( $module_title ); ?></h3>

	</div>

	<div class="sui-box-body">

		<p>
		<?php
		if ( count( $modules ) > 0 ) {
			echo esc_html( $recent_description );
		} else {
			echo esc_html( $description );
		}
		?>
		</p>

		<?php // Strict comparison is removed for a reason! ?>
		<?php if ( 0 === $total ) { ?>

			<p><button class="sui-button sui-button-blue wpmudev-open-modal"
				data-modal="<?php echo esc_attr( $create_dialog ); ?>">
				<i class="sui-icon-plus" aria-hidden="true"></i> <?php esc_html_e( 'Add New', 'forminator' ); ?>
			</button></p>

		<?php } ?>

	</div>

	<?php if ( 0 < $total ) { ?>

		<table class="sui-table sui-table-flushed">

			<thead>

				<tr>

					<th><?php esc_html_e( 'Name', 'forminator' ); ?></th>

					<th class="fui-col-status"><?php esc_html_e( 'Status', 'forminator' ); ?></th>

				</tr>

			</thead>

			<tbody>

				<?php
				foreach ( $modules as $index => $module ) {
					$module         = (array) $module;
					$module['name'] = forminator_get_form_name( $module['id'] );
					$page_no        = ceil( ( $index + 1 ) / $_per_page );
					?>

					<tr>

						<td class="sui-table-item-title"><?php echo esc_html( htmlspecialchars( $module['name'] ) ); ?></td>

						<td class="fui-col-status">

						<?php
						if ( 'publish' === $module['status'] ) {
							$status_class = 'published';
							$status_text  = esc_html__( 'Published', 'forminator' );
						} else {
							$status_class = 'draft';
							$status_text  = esc_html__( 'Draft', 'forminator' );
						}
						// For Quizzes.
						$has_leads = isset( $module['settings']['hasLeads'] ) ? $module['settings']['hasLeads'] : false;
						$leads_id  = isset( $module['settings']['leadsId'] ) ? $module['settings']['leadsId'] : 0;
						?>

							<span
								class="sui-status-dot sui-<?php echo esc_html( $status_class ); ?> sui-tooltip"
								data-tooltip="<?php echo esc_html( $status_text ); ?>"
							>
								<span aria-hidden="true"></span>
							</span>

							<a href="<?php echo esc_url( admin_url( 'admin.php?page=forminator-' . $module_type . '&view-stats=' . $module['id'] . '&paged=' . $page_no ) ); ?>"
								class="sui-button-icon sui-tooltip sui-tooltip-top-right-mobile"
								data-tooltip="<?php esc_html_e( 'View Stats', 'forminator' ); ?>">
								<i class="sui-icon-graph-line" aria-hidden="true"></i>
							</a>

							<div class="sui-dropdown">

								<button class="sui-button-icon sui-dropdown-anchor"
									aria-expanded="false"
									aria-label="<?php esc_html_e( 'More options', 'forminator' ); ?>">
									<i class="sui-icon-widget-settings-config" aria-hidden="true"></i>
								</button>

								<ul>
									<?php if ( Forminator_Admin_Module_Edit_Page::is_module_editable( $module ) ) : ?>
									<li>
										<?php $wizard_page_prefix = 'quiz' !== $module_type ? $module_type : ( 'nowrong' === $module['quiz_type'] ? $module['quiz_type'] : 'knowledge' ); ?>
										<a href="<?php echo esc_url( admin_url( 'admin.php?page=forminator-' . $wizard_page_prefix . '-wizard&id=' . $module['id'] ) ); ?>">
											<i class="sui-icon-pencil" aria-hidden="true"></i> <?php esc_html_e( 'Edit', 'forminator' ); ?>
										</a>
									</li>
									<?php endif; ?>

									<li><button class="wpmudev-open-modal"
										data-modal="<?php echo esc_attr( $preview_dialog ); ?>"
										data-modal-title="<?php printf( '%s - %s', esc_attr( $preview_title ), esc_html( htmlspecialchars( htmlspecialchars( $module['name'] ) ) ) ); ?>"
										data-nonce-preview="<?php echo esc_attr( wp_create_nonce( 'forminator_load_module' ) ); ?>"
										data-form-id="<?php echo esc_attr( $module['id'] ); ?>"
										data-has-leads="<?php echo esc_attr( $has_leads ); ?>"
										data-leads-id="<?php echo esc_attr( $leads_id ); ?>"
										data-nonce="<?php echo esc_attr( wp_create_nonce( 'forminator_popup_' . $preview_dialog ) ); ?>">
										<i class="sui-icon-eye" aria-hidden="true"></i> <?php esc_html_e( 'Preview', 'forminator' ); ?>
									</button></li>

									<li>
										<button class="copy-clipboard" data-shortcode='[forminator_<?php echo esc_attr( $module_slug ); ?> id="<?php echo esc_attr( $module['id'] ); ?>"]'><i class="sui-icon-code" aria-hidden="true"></i> <?php esc_html_e( 'Copy Shortcode', 'forminator' ); ?></button>
									</li>

									<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=forminator-entries&form_type=' . forminator_get_prefix( $module_slug, 'post_type' ) . '&form_id=' . $module['id'] ) ); ?>"><i class="sui-icon-community-people" aria-hidden="true"></i> <?php esc_html_e( 'View Submissions', 'forminator' ); ?></a></li>

									<li <?php echo ( $has_leads ) ? 'aria-hidden="true"' : ''; ?>><form method="post">
										<input type="hidden" name="forminator_action" value="clone">
										<input type="hidden" name="form_type" value="<?php echo esc_attr( forminator_get_prefix( $module_slug, 'custom-' ) ); ?>">
										<input type="hidden" name="id" value="<?php echo esc_attr( $module['id'] ); ?>"/>
									<?php
										$clone_nonce = esc_attr( 'forminator-nonce-clone-' . $module['id'] );
										wp_nonce_field( $clone_nonce, 'forminatorNonce' );
									?>
										<?php if ( $has_leads ) : ?>
											<button type="submit" disabled="disabled" class="fui-button-with-tag sui-tooltip sui-tooltip-left sui-constrained" data-tooltip="<?php esc_html_e( 'Duplicate isn\'t supported at the moment for the quizzes with lead capturing enabled.', 'forminator' ); ?>">
												<span class="sui-icon-page-multiple" aria-hidden="true"></span>
												<span class="fui-button-label"><?php esc_html_e( 'Duplicate', 'forminator' ); ?></span>
												<span class="sui-tag sui-tag-blue sui-tag-sm"><?php echo esc_html__( 'Coming soon', 'forminator' ); ?></span>
											</button>
										<?php else : ?>
											<button type="submit">
												<i class="sui-icon-page-multiple" aria-hidden="true"></i> <?php esc_html_e( 'Duplicate', 'forminator' ); ?>
											</button>
										<?php endif; ?>
									</form></li>

									<?php if ( Forminator::is_import_export_feature_enabled() ) : ?>

										<?php if ( $has_leads ) : ?>
											<li aria-hidden="true"><a href="#" class="fui-button-with-tag sui-tooltip sui-tooltip-left"
												data-tooltip="<?php esc_html_e( 'Export isn\'t supported at the moment for the quizzes with lead capturing enabled.', 'forminator' ); ?>">
												<span class="sui-icon-cloud-migration" aria-hidden="true"></span>
												<span class="fui-button-label"><?php esc_html_e( 'Export', 'forminator' ); ?></span>
												<span class="sui-tag sui-tag-blue sui-tag-sm"><?php echo esc_html__( 'Coming soon', 'forminator' ); ?></span>
											</a></li>
										<?php else : ?>
											<li><a href="#"
												class="wpmudev-open-modal"
												data-modal="<?php echo esc_attr( $export_dialog ); ?>"
												data-modal-title=""
												data-form-id="<?php echo esc_attr( $module['id'] ); ?>"
												data-nonce="<?php echo esc_attr( wp_create_nonce( 'forminator_popup_export_' . $module_slug ) ); ?>">
												<i class="sui-icon-cloud-migration" aria-hidden="true"></i> <?php esc_html_e( 'Export', 'forminator' ); ?>
											</a></li>
										<?php endif; ?>

									<?php endif; ?>

									<li>
										<button class="sui-option-red wpmudev-open-modal"
											data-modal="delete-module"
											data-modal-title="<?php echo esc_attr( $delete_title ); ?>"
											data-modal-content="<?php echo esc_attr( $delete_description ); ?>"
											data-form-id="<?php echo esc_attr( $module['id'] ); ?>"
											data-nonce="<?php echo esc_attr( wp_create_nonce( 'forminator_' . $module_slug . '_request' ) ); ?>">
											<i class="sui-icon-trash" aria-hidden="true"></i> <?php esc_html_e( 'Delete', 'forminator' ); ?>
										</button>
									</li>

								</ul>

							</div>

						</td>

					</tr>

				<?php } ?>

			</tbody>

		</table>

		<div class="sui-box-footer">

			<button class="sui-button sui-button-blue wpmudev-open-modal forminator-create-<?php echo esc_attr( $module_slug ); ?>"
				data-modal="<?php echo esc_attr( $create_dialog ); ?>">
				<i class="sui-icon-plus" aria-hidden="true"></i> <?php esc_html_e( 'Add New', 'forminator' ); ?>
			</button>

			<div class="sui-actions-right">
				<p class="sui-description"><a href="<?php echo esc_url( admin_url( 'admin.php?page=forminator-' . $module_type ) ); ?>" class="sui-link-gray"><?php echo esc_html( $view_all ); ?></a></p>
			</div>

		</div>

	<?php } ?>

</div>
