<?php
/**
 * Template admin/views/settings/tab-pagination.php
 *
 * @package Forminator
 */

$entries_per_page = get_option( 'forminator_pagination_entries', 10 );
$module_per_page  = get_option( 'forminator_pagination_listings', 10 );
?>
<div id="forminator-section-settings-pagination" class="sui-box-settings-row">

	<div class="sui-box-settings-col-1">
		<span class="sui-settings-label"><?php esc_html_e( 'Pagination', 'forminator' ); ?></span>
		<span class="sui-description"><?php esc_html_e( 'Choose the number of items to show per page on your submissions or modules listing pages.', 'forminator' ); ?></span>
	</div>

	<div class="sui-box-settings-col-2">

		<label class="sui-settings-label"><?php esc_html_e( 'Modules', 'forminator' ); ?></label>

		<span class="sui-description"
			style="margin-bottom: 10px;"><?php esc_html_e( 'Choose the number of forms, polls, and quizzes to show on each listing page.', 'forminator' ); ?></span>

		<div class="sui-form-field">
			<input type="number"
				name="pagination_listings"
				placeholder="<?php esc_html_e( '10', 'forminator' ); ?>"
				value="<?php echo esc_attr( $module_per_page ); ?>"
				min="1"
				id="forminator-limit-listing"
				class="sui-form-control forminator-required sui-input-sm sui-field-has-suffix"/>

			<label for="forminator-limit-listing" class="sui-field-suffix"><?php esc_html_e( 'modules per page', 'forminator' ); ?></label>
			<span class="sui-error-message"
				style="display: none;"><?php esc_html_e( 'This field cannot be empty.', 'forminator' ); ?></span>

		</div>
		<label class="sui-settings-label"><?php esc_html_e( 'Submissions', 'forminator' ); ?></label>

		<span class="sui-description"
			style="margin-bottom: 10px;"><?php esc_html_e( 'Choose the number of submissions to show per page.', 'forminator' ); ?></span>

		<div class="sui-form-field">
			<input type="number"
				name="pagination_entries"
				placeholder="<?php esc_html_e( '10', 'forminator' ); ?>"
				value="<?php echo esc_attr( $entries_per_page ); ?>"
				min="1"
				id="forminator-limit-entries"
				class="sui-form-control forminator-required sui-input-sm sui-field-has-suffix"/>
			<label for="forminator-limit-entries" class="sui-field-suffix"><?php esc_html_e( 'submissions per page', 'forminator' ); ?></label>
			<span class="sui-error-message"
				style="display: none;"><?php esc_html_e( 'This field cannot be empty.', 'forminator' ); ?></span>

		</div>

	</div>

</div>
