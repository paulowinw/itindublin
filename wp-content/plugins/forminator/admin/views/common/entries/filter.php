<?php
/**
 * Template admin/views/common/entries/filter.php
 *
 * @package Forminator
 */

/**
 * JS reference : assets/js/admin/layout.js
 */

/**
 * Forminator_CForm_View_Page
 *
 * @var $this Forminator_CForm_View_Page */
$is_filter_enabled = $this->is_filter_box_enabled();
$count             = $this->filtered_total_entries();
$date_range        = '';
$date_format       = 'Y-m-d';
$date_created      = isset( $this->filters['date_created'] ) ? $this->filters['date_created'] : '';
if ( is_array( $date_created ) && isset( $date_created[0] ) && isset( $date_created[1] ) ) {
	$date_created[0] = gmdate( $date_format, strtotime( $date_created[0] ) );
	$date_created[1] = gmdate( $date_format, strtotime( $date_created[1] ) );
	$date_range      = implode( ' - ', $date_created );
}
$search_filter = isset( $this->filters['search'] ) ? $this->filters['search'] : '';
$min_id        = isset( $this->filters['min_id'] ) ? $this->filters['min_id'] : '';
$max_id        = isset( $this->filters['max_id'] ) ? $this->filters['max_id'] : '';
$order_by      = isset( $this->order['order_by'] ) ? $this->order['order_by'] : '';
$order_filter  = isset( $this->order['order'] ) ? $this->order['order'] : '';
$entry_status  = isset( $this->filters['entry_status'] ) ? $this->filters['entry_status'] : 'all';

$user_status_filter = isset( $this->filters['user_status'] ) ? $this->filters['user_status'] : '';
$is_registration    = ! empty( $args['is_registration'] );
$is_show_fields     = ! method_exists( $this, 'has_leads' ) || (bool) $this->has_leads();
?>
<div class="sui-box-search">

	<div class="sui-search-left">

		<?php $this->bulk_actions( 'top', $is_registration ); ?>

	</div>

	<div class="sui-search-right">

		<div class="sui-pagination-wrap">

			<?php $this->paginate(); ?>

			<button aria-label="<?php esc_attr_e( 'Filter entries', 'forminator' ); ?>" class="sui-button-icon sui-button-outlined forminator-toggle-entries-filter <?php echo( $is_filter_enabled ? 'sui-active' : '' ); ?>">
				<i class="sui-icon-filter" aria-hidden="true"></i>
			</button>

		</div>

	</div>

</div>

<div class="sui-pagination-filter <?php echo( $is_filter_enabled ? 'sui-open' : '' ); ?>">

	<div class="sui-row">

		<div class="sui-col-md-6">

			<div class="sui-form-field">

				<label for="forminator-forms-filter--by-date" class="sui-label"><?php esc_html_e( 'Submission Date Range', 'forminator' ); ?></label>

				<div class="sui-date">
					<i class="sui-icon-calendar" aria-hidden="true"></i>
					<input type="text"
						placeholder="<?php esc_html_e( 'Pick a date range', 'forminator' ); ?>"
						id="forminator-forms-filter--by-date"
						name="date_range"
						autocomplete="off"
						value="<?php echo esc_attr( $date_range ); ?>"
						class="sui-form-control forminator-entries-filter-date"/>
				</div>

			</div>

		</div>

		<div class="sui-col-md-3">

			<label for="forminator-forms-filter--from-id" class="sui-label"><?php esc_html_e( 'From ID', 'forminator' ); ?></label>
			<input type="number"
				name="min_id"
				min="0"
				value="<?php echo esc_attr( $min_id ); ?>"
				placeholder="<?php esc_html_e( 'E.g. 100', 'forminator' ); ?>"
				id="forminator-forms-filter--from-id"
				class="sui-form-control"/>

		</div>

		<div class="sui-col-md-3">

			<label for="forminator-forms-filter--to-id" class="sui-label"><?php esc_html_e( 'To ID', 'forminator' ); ?></label>
			<input type="number"
				name="max_id"
				min="0"
				value="<?php echo esc_attr( $max_id ); ?>"
				placeholder="<?php esc_html_e( 'E.g. 100', 'forminator' ); ?>"
				id="forminator-forms-filter--to-id"
				class="sui-form-control"/>

		</div>

	</div>

	<div class="sui-row">

		<div class="sui-col-md-6">

			<div class="sui-form-field">

				<label for="forminator-forms-filter--by-keyword" class="sui-label"><?php esc_html_e( 'Keyword', 'forminator' ); ?></label>

				<div class="sui-control-with-icon">
					<i class="sui-icon-magnifying-glass-search" aria-hidden="true"></i>
					<input type="text"
						name="search"
						value="<?php echo esc_attr( $search_filter ); ?>"
						placeholder="<?php esc_html_e( 'E.g. search', 'forminator' ); ?>"
						id="forminator-forms-filter--by-keyword"
						class="sui-form-control"/>
				</div>

			</div>

		</div>

		<div class="sui-col-md-3">

			<label for="forminator-forms-filter--sort-by" class="sui-label"><?php esc_html_e( 'Sort by', 'forminator' ); ?></label>
			<select id="forminator-forms-filter--sort-by" name="order_by">
				<option value="entries.date_created" <?php selected( 'entries.date_created', $order_by ); ?> ><?php esc_html_e( 'Submissions Date', 'forminator' ); ?></option>
			</select>

		</div>

		<div class="sui-col-md-3">

			<label for="forminator-forms-filter--sort-order" class="sui-label"><?php esc_html_e( 'Sort Order', 'forminator' ); ?></label>
			<select id="forminator-forms-filter--sort-order" name="order">
				<option value="DESC" <?php selected( 'DESC', $order_filter ); ?>><?php esc_html_e( 'Descending', 'forminator' ); ?></option>
				<option value="ASC" <?php selected( 'ASC', $order_filter ); ?>><?php esc_html_e( 'Ascending', 'forminator' ); ?></option>
			</select>

		</div>

	</div>

	<div class="sui-row">

		<?php if ( 'form' === $this::$module_slug ) { ?>
			<div class="sui-col-md-6">

				<div class="sui-form-field">

					<label for="forminator-forms-filter--entry-status" class="sui-label"><?php esc_html_e( 'Submission Status', 'forminator' ); ?></label>
					<select id="forminator-forms-filter--entry-status" name="entry_status">
						<option value="all" <?php selected( 'all', $entry_status ); ?> ><?php esc_html_e( 'All', 'forminator' ); ?></option>
						<option value="completed" <?php selected( 'completed', $entry_status ); ?> ><?php esc_html_e( 'Completed', 'forminator' ); ?></option>
						<option value="draft" <?php selected( 'draft', $entry_status ); ?> ><?php esc_html_e( 'Draft', 'forminator' ); ?></option>
						<?php if ( ! forminator_form_abandonment_disabled() ) { ?>
							<option value="abandoned" <?php selected( 'abandoned', $entry_status ); ?> ><?php esc_html_e( 'Abandoned', 'forminator' ); ?></option>
						<?php } ?>
					</select>

				</div>

			</div>
		<?php } ?>

		<?php if ( $is_registration ) { ?>

			<div class="sui-col-md-6">

				<label for="forminator-forms-filter--user-status" class="sui-label"><?php esc_html_e( 'User Status', 'forminator' ); ?></label>
				<select id="forminator-forms-filter--user-status" name="user_status">
					<option value="" <?php selected( ! $user_status_filter ); ?>><?php esc_html_e( 'All', 'forminator' ); ?></option>
					<option value="approved" <?php selected( 'approved', $user_status_filter ); ?>><?php esc_html_e( 'Approved', 'forminator' ); ?></option>
					<option value="pending" <?php selected( 'pending', $user_status_filter ); ?>><?php esc_html_e( 'Pending Approval', 'forminator' ); ?></option>
				</select>

			</div>

		<?php } ?>

	</div>

	<?php if ( $is_show_fields ) { ?>
		<div class="sui-row">

			<div class="sui-col-md-12">

				<div class="sui-form-field">

					<label class="sui-label"><?php esc_html_e( 'Display Fields', 'forminator' ); ?></label>

					<div class="sui-side-tabs forminator-field-select-tab">

						<div class="sui-tabs-menu">

							<label for="forminator-forms-filter--display-false" class="sui-tab-item <?php echo ( $this->fields_is_filtered ? '' : 'active' ); ?>" data-tab-index="1">
								<input type="radio"
									name="fields_select"
									id="forminator-forms-filter--display-false"
									value="false"/>
								<?php esc_html_e( 'All', 'forminator' ); ?>
							</label>

							<label for="forminator-forms-filter--display-true" class="sui-tab-item <?php echo ( $this->fields_is_filtered ? 'active' : '' ); ?>" data-tab-index="2">
								<input type="radio"
									name="fields_select"
									id="forminator-forms-filter--display-true"
									value="true"/>
								<?php esc_html_e( 'Specified Fields', 'forminator' ); ?>
							</label>

						</div>

						<div class="sui-tabs-content">

							<div class="sui-tab-content <?php echo ( $this->fields_is_filtered ? '' : 'active' ); ?>" data-tab-index="1">
							</div>
							<div class="sui-tab-content sui-tab-boxed <?php echo ( $this->fields_is_filtered ? 'active' : '' ); ?>" data-tab-index="2">

								<fieldset class="forminator-entries-fields-filter" <?php echo ( $this->fields_is_filtered ? '' : 'disabled=disabled' ); ?>>
									<?php
									$fields = apply_filters( 'forminator_custom_form_filter_fields', $args['fields'] );

									foreach ( $fields as $field ) {

										$label      = $field->__get( 'field_label' );
										$field_type = $field->__get( 'type' );

										if ( ! $label ) {
											$label = $field->title;
										}

										if ( empty( $label ) ) {
											$label = ucfirst( $field_type );
										}

										$slug = isset( $field->slug ) ? $field->slug : sanitize_title( $label );
										?>

										<label class="sui-checkbox" for="<?php echo esc_attr( $slug ); ?>-enable">
											<input type="checkbox"
												name="field[]"
												value="<?php echo esc_attr( $slug ); ?>"
												id="<?php echo esc_attr( $slug ); ?>-enable"
												<?php $this->checked_field( $slug ); ?> />
											<span aria-hidden="true"></span>
											<span class="sui-description"><?php echo esc_html( $label ); ?></span>
										</label>

									<?php } ?>
								</fieldset>

							</div>

						</div>

					</div>

				</div>
			</div>

		</div>
	<?php } ?>

	<div class="sui-filter-footer">

		<button class="sui-button sui-button-ghost forminator-entries-clear-filter"><?php esc_html_e( 'Clear Filters', 'forminator' ); ?></button>

		<div class="sui-actions-right">
			<button class="sui-button forminator-entries-apply-filter" type="submit"><?php esc_html_e( 'Apply', 'forminator' ); ?></button>
		</div>

	</div>

</div>
