<?php
/**
 * Template admin/views/common/reports/basic-widget.php
 *
 * @package Forminator
 */

?>
<div class="sui-box"<?php echo isset( $id ) ? ' id="' . esc_attr( $id ) . '"' : ''; ?>>
	<div class="sui-box__message sui-box__message--loading">
		<p><span class="sui-icon-loader sui-loading" aria-hidden="true"></span>
			<?php esc_html_e( 'Fetching latest data...', 'forminator' ); ?>
		</p>
	</div>

	<div class="sui-box-header">
		<h3 class="sui-box-title">
			<i class="<?php echo esc_attr( $icon ); ?>" aria-hidden="true"></i>
			<?php echo esc_html( $title ); ?>
		</h3>
		<?php if ( isset( $title_link ) ) { ?>
			<div class="sui-actions-right">
				<a href="<?php echo esc_url( $title_link ); ?>" target="_blank" class="sui-link-blue">
					<?php echo esc_html( $title_text ); ?>
					<span class="sui-icon-arrow-right sui-sm" aria-hidden="true"></span>
				</a>
			</div>
		<?php } ?>
	</div>

	<div class="sui-box-body">
		<p><?php echo esc_html( $description ); ?></p>

		<?php if ( ! empty( $notice ) ) { ?>
			<?php $class = ! empty( $notice_type ) ? ' sui-notice-' . $notice_type : ''; ?>
			<div class="sui-notice<?php echo esc_attr( $class ); ?>">
				<div class="sui-notice-content">
					<div class="sui-notice-message">
						<i aria-hidden="true" class="sui-notice-icon sui-icon-info sui-md"></i>
						<p><?php echo wp_kses_post( $notice ); ?></p>
					</div>
				</div>
			</div>
		<?php } ?>

	</div>

	<div class="forminator-report-widget-content">
	<?php if ( ! empty( $content ) ) { ?>
		<?php echo wp_kses_post( $content ); ?>
	<?php } ?>
	</div>

</div>
