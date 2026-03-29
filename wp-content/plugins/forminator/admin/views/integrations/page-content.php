<?php
/**
 * Template admin/views/integrations/page-content.php
 *
 * @package Forminator
 */

?>
<p>
	<?php
		esc_html_e( 'Forminator integrates with your favorite third party apps. You can connect to the available apps via their API here and activate them to collect data in the Integrations tab of your forms, polls or quizzes.', 'forminator' );
	?>
</p>

<div class="fui-integrations-block">

	<span class="sui-table-title"><?php esc_html_e( 'Connected Apps', 'forminator' ); ?></span>

	<?php
	if ( ! empty( $addons['connected'] ) ) {
		?>

		<table class="sui-table fui-table--apps">

			<tbody>

				<?php foreach ( $addons['connected'] as $key => $provider ) : ?>

					<?php echo wp_kses_post( forminator_addon_row_html_markup( $provider, 0 ) ); ?>

				<?php endforeach; ?>

			</tbody>

		</table>

		<span class="sui-description"><?php esc_html_e( 'To activate any of these to collect data, go to the Integrations tab of your forms, polls or quizzes.', 'forminator' ); ?></span>

	<?php } else { ?>

		<div
			role="alert"
			class="sui-notice sui-notice-blue sui-active"
			style="display: block; text-align: left;"
			aria-live="assertive"
		>

			<div class="sui-notice-content">

				<div class="sui-notice-message">

					<span class="sui-notice-icon sui-icon-info" aria-hidden="true"></span>

					<p><?php esc_html_e( 'You are not connected to any third party apps. You can connect to the available apps listed below and activate them in your modules to collect data.', 'forminator' ); ?></p>

				</div>

			</div>

		</div>

	<?php } ?>

</div>

<div class="fui-integrations-block">

	<span class="sui-table-title"><?php esc_html_e( 'Available Apps', 'forminator' ); ?></span>

	<?php
	if ( ! empty( $addons['not_connected'] ) ) {
		?>

		<table class="sui-table fui-table--apps">

			<tbody>

				<?php foreach ( $addons['not_connected'] as $key => $provider ) : ?>

					<?php echo wp_kses_post( forminator_addon_row_html_markup( $provider, 0, 'form', true, false ) ); ?>

				<?php endforeach; ?>

			</tbody>

		</table>

		<?php
	}
	?>

</div>
