<?php
/**
 * Template admin/views/common/reports/integration-report.php
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
			<i class="sui-icon-sitemap" aria-hidden="true"></i>
			<?php esc_html_e( 'Apps', 'forminator' ); ?>
		</h3>
		<div class="sui-actions-right">
			<a href="<?php echo esc_html( $app_link ); ?>"
				target="_blank">
				<?php esc_html_e( 'Manage Apps', 'forminator' ); ?>
				<span class="sui-icon-arrow-right sui-sm" aria-hidden="true"></span>
			</a>
		</div>
	</div>

	<div class="sui-box-body">
		<p><?php esc_html_e( 'Data sent to third party apps over the selected period.', 'forminator' ); ?></p>
		<?php if ( empty( $integrations ) ) { ?>
			<div class="sui-notice">
				<div class="sui-notice-content">
					<div class="sui-notice-message">
						<i aria-hidden="true" class="sui-notice-icon sui-icon-info sui-md"></i>
						<p>
						<?php
						printf(
							/* Translators: 1. Opening <a> tag with link to the Integration page, 2. closing <a> tag. */
							esc_html__( 'No third-party app connected. %1$sConnect a third-party app%2$s to view data in this report.', 'forminator' ),
							'<a href="' . esc_url( admin_url( 'admin.php?page=forminator-integrations' ) ) . '" target=_blank>',
							'</a>'
						);
						?>
						</p>
					</div>
				</div>
			</div>
		<?php } ?>
	</div>
	<?php if ( ! empty( $integrations ) ) { ?>
		<table class="sui-table sui-table-flushed fui-table--apps">
			<thead>
			<tr>
				<th><?php esc_html_e( 'Apps', 'forminator' ); ?></th>
				<th colspan="1" width="25%"><?php esc_html_e( 'Data', 'forminator' ); ?></th>
				<th colspan="1" width="25%"><?php esc_html_e( 'Previous', 'forminator' ); ?></th>
			</tr>
			</thead>

			<tbody>
			<?php foreach ( $integrations as $key => $integration ) { ?>
				<tr>
					<td class="sui-table-item-title">
						<div class="fui-app--wrapper">
							<img src="<?php echo esc_html( $integration['image'] ); ?>"
								alt="<?php echo esc_html( $integration['short_title'] ); ?>" class="sui-image"
								aria-hidden="true">
							<span><?php echo esc_html( $integration['title'] ); ?></span>
						</div>
					</td>
					<td colspan="1" width="25%">
						<strong class="selected-<?php echo esc_html( $key ); ?>"><?php echo absint( $integration['selected'] ); ?></strong>
						<?php
						$arrow_color = 'high' === $integration['difference'] ? 'green' : 'red';
						$arrow_icon  = 'high' === $integration['difference'] ? 'up' : 'down';
						?>
						<span class="fui-trend fui-trend-<?php echo esc_html( $arrow_color ); ?> increment-<?php echo esc_attr( $key ); ?>">
							<?php if ( $integration['increment'] > 0 ) { ?>
								<i class="sui-icon-arrow-<?php echo esc_html( $arrow_icon ); ?> sui-sm" aria-hidden="true"></i>
								<?php
								echo esc_html( $integration['increment'] );
							}
							?>
						</span>
					</td>
					<td colspan="1" width="25%" class="previous-<?php echo esc_html( $key ); ?>">
						<?php echo absint( $integration['previous'] ); ?>
					</td>
				</tr>
			<?php } ?>
			</tbody>
		</table>
	<?php } ?>
</div>