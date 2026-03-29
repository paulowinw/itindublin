<?php
/**
 * Template admin/views/quiz/entries/content.php
 *
 * @package Forminator
 */

/**
 * Forminator_Quiz_Renderer
 *
 *  @var Forminator_Quiz_Renderer $this */
$plugin_path       = forminator_plugin_url();
$count             = $this->filtered_total_entries();
$entries_per_page  = $this->get_per_page();
$is_filter_enabled = $this->is_filter_box_enabled();
$total_page        = ceil( $count / $entries_per_page );
$submissions       = $this->get_total_entries();
?>
<?php
if ( $this->error_message() ) {
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is already escaped.
	echo Forminator_Admin::get_red_notice( esc_html( $this->error_message() ) );
}
?>

<?php if ( $this->total_entries() > 0 ) : ?>

	<form method="get" class="sui-box fui-listings-pagination fui-box-entries forminator-entries-actions">

		<div class="fui-pagination-mobile fui-pagination-entries sui-pagination-wrap">
			<?php $this->paginate(); ?>
		</div>

		<div class="fui-pagination-desktop sui-box fui-box-entries">

			<fieldset class="forminator-entries-nonce">
				<?php wp_nonce_field( 'forminatorQuizEntries', 'forminatorEntryNonce' ); ?>
			</fieldset>

			<div class="sui-box-body fui-box-actions">

				<?php $this->template( 'common/entries/prompt', array( 'submissions' => $submissions ) ); ?>

				<input type="hidden" name="page" value="<?php echo esc_attr( $this->get_admin_page() ); ?>">
				<input type="hidden" name="form_type" value="<?php echo esc_attr( $this->forminator_get_form_type() ); ?>">
				<input type="hidden" name="form_id" value="<?php echo esc_attr( $this->form_id ); ?>"/>

				<?php $this->template( 'common/entries/filter', array( 'fields' => $this->get_lead_fields() ) ); ?>

			</div>

			<?php if ( true === $is_filter_enabled ) : ?>

				<?php $this->template( 'common/entries/active_filters_row' ); ?>

			<?php endif; ?>

			<table class="sui-table sui-table-flushed sui-accordion fui-table-entries">

				<?php $this->entries_header(); ?>

				<tbody>

					<?php
					if ( $this->has_leads() ) {
						$this->template( 'quiz/entries/content-leads' );
					} else {
						$this->template( 'quiz/entries/content-leads-none' );
					}
					?>
				</tbody>

			</table>

			<div class="sui-box-body">

				<div class="sui-box-search">

					<div class="sui-search-left">

						<?php $this->bulk_actions( 'bottom' ); ?>

					</div>

					<div class="sui-search-right">

						<div class="sui-pagination-wrap">
							<?php $this->paginate(); ?>
						</div>

					</div>

				</div>

			</div>
		</div>

	</form>

<?php else : ?>

	<?php include_once forminator_plugin_dir() . 'admin/views/common/entries/content-none.php'; ?>
	<?php
endif;
