<?php
/**
 * Template admin/views/integrations/main.php
 *
 * @package Forminator
 */

$integrations_page = admin_url( 'admin.php?page=forminator-integrations' );

if ( empty( $module_id ) ) {
	$module_id = 0;
}

if ( empty( $addons['connected'] ) && empty( $addons['not_connected'] ) ) { ?>

	<div
		role="alert"
		class="sui-notice sui-notice-blue sui-active"
		style="display: block; text-align: left;"
		aria-live="assertive"
	>

		<div class="sui-notice-content">

			<div class="sui-notice-message">

				<span class="sui-notice-icon sui-icon-info" aria-hidden="true"></span>

				<p>
				<?php
				printf(
					/* Translators: 1. Opening <a> tag with integration page link, 2. closing <a> tag. */
					esc_html__( 'You are not connected to any third party apps. You can connect to the available apps via their API on the %1$sIntegrations%2$s page and come back to activate them for collecting data of this module.', 'forminator' ),
					'<a href="' . esc_url( $integrations_page ) . '">',
					'</a>'
				);
				?>
				</p>

			</div>

		</div>

	</div>

<?php } else { ?>

	<div class="fui-integrations-block">

		<span class="sui-table-title"><?php esc_html_e( 'Active Apps', 'forminator' ); ?></span>

		<?php if ( empty( $addons['connected'] ) ) { ?>

			<div
				role="alert"
				class="sui-notice sui-notice-blue sui-active"
				style="display: block; text-align: left;"
				aria-live="assertive"
			>

				<div class="sui-notice-content">

					<div class="sui-notice-message">

						<span class="sui-notice-icon sui-icon-info" aria-hidden="true"></span>

						<p><?php esc_html_e( 'You are not sending this module\'s data to any third party apps. You can activate any of the connected apps below and start sending this module\'s data to them.', 'forminator' ); ?></p>

					</div>

				</div>

			</div>

		<?php } else { ?>

			<table class="sui-table fui-table--apps fui-connected">

				<tbody>

					<?php foreach ( $addons['connected'] as $key => $provider ) : ?>

						<?php echo wp_kses_post( forminator_addon_row_html_markup( $provider, $module_id, $module_slug ) ); ?>

					<?php endforeach; ?>

				</tbody>

			</table>

			<span class="sui-description"><?php esc_html_e( 'These apps are collecting data of your module.', 'forminator' ); ?></span>

		<?php } ?>

	</div>

	<div class="fui-integrations-block">

		<span class="sui-table-title"><?php esc_html_e( 'Connected Apps', 'forminator' ); ?></span>

		<?php if ( empty( $addons['not_connected'] ) ) { ?>

			<div
				role="alert"
				class="sui-notice sui-active"
				style="display: block; text-align: left;"
				aria-live="assertive"
			>

				<div class="sui-notice-content">

					<div class="sui-notice-message">

						<span class="sui-notice-icon sui-icon-info" aria-hidden="true"></span>

						<p>
						<?php
						printf(
							/* Translators: 1. Opening <a> tag with integration page link, 2. closing <a> tag. */
							esc_html__( 'Connect to more third party apps on the %1$sIntegrations%2$s page and activate them to collect the data of this module here.', 'forminator' ),
							'<a href="' . esc_url( $integrations_page ) . '">',
							'</a>'
						);
						?>
						</p>

					</div>

				</div>

			</div>

		<?php } else { ?>

			<table class="sui-table fui-table--apps">

				<tbody>

					<?php foreach ( $addons['not_connected'] as $key => $provider ) : ?>
						<?php
						if ( ! $provider[ 'is_' . $module_slug . '_settings_available' ] ) {
							continue;
						}
						?>

						<?php echo wp_kses_post( forminator_addon_row_html_markup( $provider, $module_id, $module_slug ) ); ?>

					<?php endforeach; ?>

				</tbody>

			</table>

			<span class="sui-description">
				<?php
				printf(
				/* Translators: 1. Opening <a> tag with integration page link, 2. closing <a> tag. */
					esc_html__( 'You are connected to these apps via their API. Connect to more apps on the %1$sIntegrations%2$s page.', 'forminator' ),
					'<a href="' . esc_url( $integrations_page ) . '">',
					'</a>'
				);
				?>
			</span>

		<?php } ?>

	</div>

	<?php
}
