<?php
/**
 * Template admin/views/common/entries/active_filters_row.php
 *
 * @package Forminator
 */

?>
<div class="sui-box-body fui-box-actions-filters">

	<label class="sui-label"><?php esc_html_e( 'Active Filters', 'forminator' ); ?></label>

	<div class="sui-pagination-active-filters forminator-entries-fields-filters">

		<?php if ( isset( $this->filters['search'] ) ) : ?>
			<div class="sui-active-filter">
				<?php
				printf(
				/* translators: %s: Search Keyword */
					esc_html__( 'Keyword: %s', 'forminator' ),
					esc_html( $this->filters['search'] )
				);
				?>
				<button class="sui-active-filter-remove" type="submit" name="search" value="">
					<span class="sui-screen-reader-text"><?php esc_html_e( 'Remove this keyword', 'forminator' ); ?></span>
				</button>
			</div>
		<?php endif; ?>

		<?php if ( isset( $this->filters['min_id'] ) ) : ?>
			<div class="sui-active-filter">
				<?php
				printf(
				/* translators: %s: Minimum ID */
					esc_html__( 'From ID: %s', 'forminator' ),
					esc_html( $this->filters['min_id'] )
				);
				?>
				<button class="sui-active-filter-remove" type="submit" name="min_id" value="">
					<span class="sui-screen-reader-text"><?php esc_html_e( 'Remove this keyword', 'forminator' ); ?></span>
				</button>
			</div>
		<?php endif; ?>

		<?php if ( isset( $this->filters['max_id'] ) ) : ?>
			<div class="sui-active-filter">
				<?php
				printf(
				/* translators: %s: Maximum ID */
					esc_html__( 'To ID: %s', 'forminator' ),
					esc_html( $this->filters['max_id'] )
				);
				?>
				<button class="sui-active-filter-remove" type="submit" name="max_id" value="">
					<span class="sui-screen-reader-text"><?php esc_html_e( 'Remove this keyword', 'forminator' ); ?></span>
				</button>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $this->filters['user_status'] ) ) : ?>
			<div class="sui-active-filter">
				<?php
				printf(
				/* translators: %s: User status */
					esc_html__( 'User status: %s', 'forminator' ),
					( 'pending' === $this->filters['user_status'] )
						? esc_html__( 'Pending Approval', 'forminator' )
						: esc_html__( 'Approved', 'forminator' )
				);
				?>
				<button class="sui-active-filter-remove" type="submit" name="user_status" value="">
					<span class="sui-screen-reader-text"><?php esc_html_e( 'Remove this keyword', 'forminator' ); ?></span>
				</button>
			</div>
		<?php endif; ?>

		<?php if ( isset( $this->filters['date_created'][0] ) || isset( $this->filters['date_created'][1] ) ) : ?>
			<div class="sui-active-filter">
				<?php
				printf(
				/* translators: 1: First date from date range, 2: Second date from date range */
					esc_html__( 'Submission Date Range: %1$s to %2$s', 'forminator' ),
					esc_html( $this->filters['date_created'][0] ),
					esc_html( $this->filters['date_created'][1] )
				);
				?>
				<button class="sui-active-filter-remove" type="submit" name="date_range" value="">
					<span class="sui-screen-reader-text"><?php esc_html_e( 'Remove this keyword', 'forminator' ); ?></span>
				</button>
			</div>
		<?php endif; ?>

		<?php if ( isset( $this->filters['entry_status'] ) ) : ?>
			<div class="sui-active-filter">
				<?php

				switch ( $this->filters['entry_status'] ) {
					case 'completed':
						$entry_status = __( 'Completed', 'forminator' );
						break;

					case 'draft':
						$entry_status = __( 'Draft', 'forminator' );
						break;

					case 'abandoned':
						if ( ! forminator_form_abandonment_disabled() ) {
							$entry_status = __( 'Abandoned', 'forminator' );
						}
						break;

					default:
						$entry_status = __( 'All', 'forminator' );
						break;
				}

				printf(
				/* translators: %s: Entry status */
					esc_html__( 'Entry status: %s', 'forminator' ),
					esc_html( $entry_status )
				);
				?>
				<button class="sui-active-filter-remove" type="submit" name="entry_status" value="">
					<span class="sui-screen-reader-text"><?php esc_html_e( 'Remove this filter', 'forminator' ); ?></span>
				</button>
			</div>
		<?php endif; ?>

		<div class="sui-active-filter">
			<?php
			esc_html_e( 'Sort Order', 'forminator' );
			echo ': ';
			if ( 'DESC' === $this->order['order'] ) {
				esc_html_e( 'Descending', 'forminator' );
			} else {
				esc_html_e( 'Ascending', 'forminator' );
			}
			?>
		</div>

	</div>

</div>
