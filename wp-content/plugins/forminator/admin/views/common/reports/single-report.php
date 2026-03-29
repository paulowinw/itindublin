<?php
/**
 * Template admin/views/common/reports/single-report.php
 *
 * @package Forminator
 */

?>
<div class="sui-box">
	<div class="sui-box__message sui-box__message--loading">
		<p><span class="sui-icon-loader sui-loading" aria-hidden="true"></span>
			<?php esc_html_e( 'Fetching latest data...', 'forminator' ); ?>
		</p>
	</div>

	<div class="sui-box-header">
		<h3 class="sui-box-title">
			<i class="<?php echo esc_html( $icon ); ?>" aria-hidden="true"></i>
			<?php echo esc_html( $title ); ?>
		</h3>
		<?php if ( isset( $title_link ) ) { ?>
			<div class="sui-actions-right">
				<a href="<?php echo esc_url( $title_link ); ?>" target="_blank"
					class="sui-link-blue"><?php echo esc_html( $title_text ); ?>
					<span class="sui-icon-arrow-right sui-sm" aria-hidden="true"></span>
				</a>
			</div>
		<?php } ?>
	</div>

	<div class="sui-box-body">
		<p><?php echo esc_html( $description ); ?></p>
		<?php
		if ( ( isset( $has_payment ) && ! $has_payment )
					|| ( isset( $has_live_payment ) && ! $has_live_payment ) ) {
			?>
			<div class="sui-notice">
				<div class="sui-notice-content">
					<div class="sui-notice-message">
						<i aria-hidden="true" class="sui-notice-icon sui-icon-info sui-md"></i>
						<?php if ( isset( $has_payment ) && ! $has_payment ) { ?>
							<p><?php esc_html_e( 'No payment field found. Add a PayPal or a Stripe field to your form to start collecting payments.', 'forminator' ); ?></p>
						<?php } else { ?>
							<p>
							<?php
							printf(
								/* Translators: 1. Opening <strong> tag, 2. closing <strong> tag. */
								esc_html__( 'One or more of your payment fields are set to %1$sTest/Sandbox%2$s mode. Forminator reports only capture data from live payments. Please switch to live mode to view the payment data here', 'forminator' ),
								'<strong>',
								'</strong>'
							);
							?>
								</p>
						<?php } ?>
					</div>
				</div>
			</div>
		<?php } ?>
	</div>
	<?php
	if ( ( ! isset( $has_payment ) && ! isset( $has_live_payment ) )
				|| ( isset( $has_live_payment ) && $has_live_payment ) ) {

		if ( ! isset( $data ) ) {
			// Support for old data structure.
			$data = array(
				array(
					'title' => $title,
					'attrs' => $attrs,
				),
			);
		}
		?>
	<div class="sui-flushed">
		<table class="sui-table sui-table-flushed report-<?php echo esc_attr( $data_class ); ?>">
			<tbody>
			<?php foreach ( $data as $item ) { ?>
			<tr>
				<td>
					<div class="fui-table-title">
						<?php echo esc_html( $item['title'] ); ?>
					</div>
					<div class="fui-table-content">
						<span class="selected-<?php echo esc_attr( $data_class ); ?>">
							<?php echo esc_html( $item['attrs']['selected'] ); ?>
						</span>
						<?php
							$arrow_color = 'high' === $item['attrs']['difference'] ? 'fui-trend-green' : 'fui-trend-red';
							$arrow_icon  = 'high' === $item['attrs']['difference'] ? 'sui-icon-arrow-up' : 'sui-icon-arrow-down';
						?>
							<span class="fui-trend <?php echo esc_html( $arrow_color ); ?> increment-<?php echo esc_attr( $data_class ); ?>">
								<?php if ( $item['attrs']['increment'] > 0 ) { ?>
									<i class="<?php echo esc_html( $arrow_icon ); ?> sui-sm" aria-hidden="true"></i>
									<?php
									echo esc_html( $item['attrs']['increment'] );
								}
								?>
							</span>

					</div>
				</td>
				<td>
					<div class="fui-table-title"><?php esc_html_e( 'previous period', 'forminator' ); ?>
						<?php $tooltip_text = esc_html__( 'Displays the statistics for the same previous period you selected.', 'forminator' ); ?>
						<button class="sui-button-icon sui-tooltip sui-tooltip-top-center sui-tooltip-constrained sui-tooltip-top-right-mobile"
								data-tooltip="<?php echo esc_attr( $tooltip_text ); ?>"
								aria-label="<?php echo esc_attr( $tooltip_text ); ?>">
							<span class="sui-icon-info" aria-hidden="true"></span>
						</button>
					</div>
					<div class="fui-table-content previous-<?php echo esc_attr( $data_class ); ?>">
						<?php echo esc_html( $item['attrs']['previous'] ); ?>
					</div>
				</td>
			</tr>
			<?php } ?>
			<?php if ( isset( $has_payment ) && $has_payment ) { ?>
				<tr>
					<td>
						<div class="fui-table-title"><?php esc_html_e( 'stripe', 'forminator' ); ?></div>
						<div class="fui-table-content stripe-report">
							<?php echo esc_html( $attrs['stripe'] ); ?>
						</div>
					</td>
					<td>
						<div class="fui-table-title"><?php esc_html_e( 'paypal', 'forminator' ); ?></div>
						<div class="fui-table-content paypal-report">
							<?php echo esc_html( $attrs['paypal'] ); ?>
						</div>
					</td>
				</tr>
			<?php } ?>
			</tbody>
		</table>
	</div>
		<?php if ( isset( $attrs['average'] ) ) { ?>
		<div class="sui-box-footer">
			<strong><?php esc_html_e( 'Monthly average', 'forminator' ); ?></strong>
			<div class="sui-actions-right">
				<p class="average-<?php echo esc_attr( $data_class ); ?>">
					<?php echo esc_html( $attrs['average'] ); ?>
				</p>
			</div>
		</div>
			<?php
		}
	}
	?>
</div>