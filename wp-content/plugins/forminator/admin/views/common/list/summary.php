<?php
/**
 * Template admin/views/common/list/summary.php
 *
 * @package Forminator
 */

$preview_dialog       = 'preview_' . forminator_get_prefix( $this::$module_slug, 'c', false, true );
$export_dialog        = 'export_' . $this::$module_slug;
$forminator_post_type = 'forminator_' . forminator_get_prefix( $this::$module_slug, '', false, true );
$soon                 = 'quiz' === $this::$module_slug;

if ( $count > 0 || $is_search ) {
	$count_active = $this->countModules( 'publish' );
	// Count total entries from last 30 days.
	$total_entries_from_last_month = count( Forminator_Form_Entry_Model::get_newer_entry_ids( $entry_type, $sql_month_start_date ) );

	$most_entry = Forminator_Form_Entry_Model::get_most_entry( $entry_type );

	?>

	<div class="sui-box sui-summary <?php echo esc_attr( $this->get_box_summary_classes() ); ?>">

		<div class="sui-summary-image-space" aria-hidden="true" style="<?php echo esc_attr( $this->get_box_summary_image_style() ); ?>"></div>

		<div class="sui-summary-segment">

			<div class="sui-summary-details">

				<span id="forminator-summary-active-<?php echo esc_attr( static::$module_slug ); ?>-count" class="sui-summary-large"><?php echo esc_html( $count_active ); ?></span>

				<span class="sui-summary-sub">
				<?php
				/* translators: %s is status. */
				$active_text     = esc_html__( 'Active %s', 'forminator' );
				$active_singular = esc_html__( forminator_get_prefix( $this::$module_slug, '', true ), 'forminator' ); // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText
				$active_plural   = esc_html__( forminator_get_prefix( $this::$module_slug, '', true, true ), 'forminator' ); // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText
				if ( 1 < $count_active ) {
					printf( esc_html( $active_text ), esc_html( $active_plural ) );
				} else {
					printf( esc_html( $active_text ), esc_html( $active_singular ) );
				}
				?>
				</span>

				<form id="forminator-search-modules" class="forminator-search-modules" data-searched="false">

					<div class="sui-row">

						<div class="sui-col-lg-10 sui-col-md-12">

							<div class="sui-form-field">

								<div class="sui-control-with-icon">
									<button class="forminator-search-submit" aria-label="<?php esc_attr_e( 'Search', 'forminator' ); ?>"><i class="sui-icon-magnifying-glass-search"></i></button>
									<?php
									if ( 'form' === $this::$module_slug ) {
										$module_name = esc_html__( 'form', 'forminator' );
									} elseif ( 'poll' === $this::$module_slug ) {
										$module_name = esc_html__( 'poll', 'forminator' );
									} else {
										$module_name = esc_html__( 'quiz', 'forminator' );
									}
									/* translators: %s: Module slug */
									$placeholder_text = sprintf( esc_html__( 'Search %s...', 'forminator' ), $module_name );
									?>
									<input type="text" name="search" value="<?php echo esc_attr( $search_keyword ); ?>" aria-label="<?php echo esc_attr( $placeholder_text ); ?>" placeholder="<?php echo esc_attr( $placeholder_text ); ?>" id="forminator-module-search" class="sui-form-control">
								</div>
								<button role="button" class="search-reset sui-button-icon" title="<?php esc_attr_e( 'Reset search', 'forminator' ); ?>">
									<span class="sui-icon-cross-close" aria-hidden="true"></span>
									<span class="sui-screen-reader-text"><?php esc_html_e( 'Reset search', 'forminator' ); ?></span>
								</button>

							</div>

						</div>

					</div>

					<input type="hidden" name="module_slug" value="<?php echo esc_attr( $this::$module_slug ); ?>" />
					<input type="hidden" name="preview_title" value="<?php echo esc_attr( $preview_title ); ?>" />
					<input type="hidden" name="sql_month_start_date" value="<?php echo esc_attr( $sql_month_start_date ); ?>" />
					<input type="hidden" name="wizard_page" value="<?php echo esc_attr( $wizard_page ); ?>" />

					<input type="hidden" name="preview_dialog" value="<?php echo esc_attr( $preview_dialog ); ?>" />
					<input type="hidden" name="export_dialog" value="<?php echo esc_attr( $export_dialog ); ?>" />
					<input type="hidden" name="post_type" value="<?php echo esc_attr( $forminator_post_type ); ?>" />
					<input type="hidden" name="soon" value="<?php echo esc_attr( $soon ); ?>" />
					<input type="hidden" name="page" value="<?php echo isset( $_GET['page'] ) ? esc_html( Forminator_Core::sanitize_text_field( 'page' ) ) : ''; /* phpcs:ignore WordPress.Security.NonceVerification.Recommended */ ?>" />
					<input type="hidden" name="paged" value="1" />
					<?php
						wp_nonce_field( $search_module_nonce, $search_module_nonce, false );
					?>

				</form>

			</div>

		</div>

		<div class="sui-summary-segment">

			<ul class="sui-list">

				<li id="forminator-summary-last-submission">
					<span class="sui-list-label"><?php esc_html_e( 'Last Submission', 'forminator' ); ?></span>
					<span class="sui-list-detail"><?php echo esc_html( forminator_get_latest_entry_time( $this::$module_slug ) ); ?></span>
				</li>

				<li id="forminator-summary-submissions-in-30-days">
					<span class="sui-list-label"><?php esc_html_e( 'Submissions in the last 30 days', 'forminator' ); ?></span>
					<span class="sui-list-detail"><?php echo esc_html( $total_entries_from_last_month ); ?></span>
				</li>
				<?php if ( ! empty( $most_entry ) && get_post_status( $most_entry->form_id ) && 0 !== (int) $most_entry->entry_count ) { ?>
					<li id="forminator-summary-most-submissions">
						<span class="sui-list-label"><?php esc_html_e( 'Most submissions', 'forminator' ); ?></span>
						<span class="sui-list-detail">
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . $wizard_page . '&id=' . $most_entry->form_id ) ); ?>">
								<?php echo esc_html( forminator_get_form_name( $most_entry->form_id ) ); ?>
							</a>
						</span>
					</li>
				<?php } ?>
			</ul>

		</div>

	</div>

	<?php
	// Call the css here to prevent search icon from flashing above the search form while the page is loading...
	$this->template( 'common/list/temp_css' );
}
