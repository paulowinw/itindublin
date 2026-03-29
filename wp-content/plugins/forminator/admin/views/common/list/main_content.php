<?php
/**
 * Template admin/views/common/list/main_content.php
 *
 * @package Forminator
 */

?>
<!-- START: Bulk actions and pagination -->
<div class="fui-listings-pagination">

	<div class="fui-pagination-mobile sui-pagination-wrap">
		<?php $this->pagination( $is_search, $count ); ?>
	</div>

	<div class="fui-pagination-desktop sui-box">

		<div class="sui-box-search">

			<form
				method="post"
				name="bulk-action-form"
				class="sui-search-left"
				style="display: flex; align-items: center;"
				>

				<?php wp_nonce_field( 'forminator_' . $this::$module_slug . '_request', 'forminatorNonce' ); ?>

				<input type="hidden" id="forminator_bulk_ids" name="ids" value="" />
				<input type="hidden" name="msearch" value="" />

				<label for="forminator-check-all-modules" class="sui-checkbox">
					<input type="checkbox" id="forminator-check-all-modules">
					<span aria-hidden="true"></span>
					<span class="sui-screen-reader-text"><?php esc_html_e( 'Select all', 'forminator' ); ?></span>
				</label>

				<select
					name="forminator_action"
					class="sui-select sui-select-sm sui-select-inline fui-select-listing-actions"
					data-search="false"
				>
					<option value=""><?php esc_html_e( 'Bulk Action', 'forminator' ); ?></option>
					<?php foreach ( $this->bulk_actions() as $val => $label ) : ?>
						<option value="<?php echo esc_attr( $val ); ?>"><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>

				<button id="forminator_bulk_apply_button" class="sui-button"><?php esc_html_e( 'Apply', 'forminator' ); ?></button>

				<?php if ( 'forminator_forms' === $forminator_post_type ) { ?>
				<button id="forminator_bulk_apply_preset" data-modal="apply_preset" class="sui-hidden wpmudev-open-modal" aria-labelledby="forminator_bulk_apply_button"></button>
				<?php } ?>

			</form>

			<div class="sui-search-right">

				<div class="sui-pagination-wrap">
					<?php $this->pagination( $is_search, $count ); ?>
				</div>

			</div>

		</div>

	</div>

</div>
<!-- END: Bulk actions and pagination -->

<div id="search_loader" class="sui-box" style="display:none;padding:40px;text-align:center;">
	<span class="sui-icon-loader sui-loading" aria-hidden="true">
		<span style="padding-left:10px;"><?php esc_html_e( 'Searching forms, please wait', 'forminator' ); ?>...</span>
	</span>
</div>

<div class="sui-accordion sui-accordion-block" id="forminator-modules-list">
	<?php
	if ( empty( $search_keyword ) ) {
		Forminator_Admin_Module_Edit_Page::show_modules( $modules, $this::$module_slug, $preview_dialog, $preview_title, $export_dialog, $forminator_post_type, $soon, $sql_month_start_date, $wizard_page );
	}
	?>
</div>
