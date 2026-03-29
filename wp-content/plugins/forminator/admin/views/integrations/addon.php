<?php
/**
 * Template admin/views/integrations/addon.php
 *
 * @package Forminator
 */

$empty_icon   = forminator_plugin_url() . 'assets/images/forminator-summary.png';
$empty_icon2x = forminator_plugin_url() . 'assets/images/forminator-summary@2x.png';
if ( empty( $module_id ) ) {
	$module_id = 0;
}

$show_action  = false;
$addon_action = 'forminator_addon_settings';

$multi_id   = 0;
$multi_name = false;

if ( ! empty( $module_id ) ) {
	$addon_action = 'forminator_addon_module_settings';
	if ( ! empty( $addon[ 'is_' . $module_slug . '_settings_available' ] ) && true === $addon[ 'is_' . $module_slug . '_settings_available' ] ) {
		$show_action = true;
		if ( $addon[ 'is_allow_multi_on_' . $module_slug ] ) {
			if ( isset( $addon['multi_name'] ) ) {
				$multi_id   = $addon['multi_id'];
				$multi_name = $addon['multi_name'];
				$activated  = true;
			} else {

				if ( isset( $addon['multi_id'] ) ) {
					$multi_id = $addon['multi_id'];
				}

				$activated = false;

			}
		} else {
			$activated = (bool) $addon[ 'is_' . $module_slug . '_connected' ];
		}
	}
} elseif ( ! empty( $addon['is_settings_available'] ) && true === $addon['is_settings_available'] ) {
	// on integrations page.
	$is_integrations_page = true;

	$show_action = true;

	if ( $addon['is_connected'] ) {
		$activated = true;
	} else {
		$activated = false;
		$tooltip   = esc_html__( 'Connect App', 'forminator' );
	}
}

$icon_class_action = empty( $activated ) ? 'sui-icon-plus' : 'sui-icon-widget-settings-config';
if ( empty( $tooltip ) ) {
	if ( ! isset( $activated ) ) {
		$tooltip = esc_html__( 'Configure Integration', 'forminator' );
	} else {
		$tooltip = empty( $activated ) ? esc_html__( 'Activate App', 'forminator' ) : esc_html__( 'Configure App', 'forminator' );
	}
}

$conditions_button = '';
$unique_id         = ! empty( $addon['global_id'] ) ? esc_attr( $addon['global_id'] ) : esc_attr( $multi_id );
if ( empty( $is_integrations_page ) && ! empty( $activated ) && 'form' === $module_slug ) {
	$conditions_button = '<button class="sui-button sui-button-ghost conditions-integration"
			data-title="' . esc_attr( $addon['title'] ) . '"  data-multi-id="' . $unique_id . '">' .
		esc_html__( 'Conditions', 'forminator' ) . '</button>';
}

$action_available = false;

if ( ! empty( $show_pro_info ) && $show_pro_info ) {
	$show_pro_info = true;
} else {
	$show_pro_info = false;
}

$advertising = false;

if ( 'webhook' === $addon['slug'] ) {
	$advertising = true;
}

/**
 * Force Disable pro tag y default
 */
$show_pro_info = false;

$pro_url        = 'https://wpmudev.com';
$pro_url_target = '_blank';

/*
MULTI NAME (SAMPLE).
// To be added in the table later when design is ready.

// <td><?php if ( ! empty( $multi_name ) ): ?>
// <?php echo esc_html( $multi_name ); ?>
// <?php endif; ?></td>
*/ ?>

<tr class="
	<?php
	if ( ! $is_active && true === $advertising ) {
		echo 'fui-app--promote '; }
	?>
	<?php echo( $is_active ? 'fui-integration-enabled' : '' ); ?>">

	<td class="sui-table-item-title">
		<?php if ( ! empty( $activated ) && 'form' === $module_slug ) { ?>
			<span class="fui-conditions sui-tooltip sui-tooltip-left sui-tooltip-top-right-mobile" data-tooltip="" data-integration-id="<?php echo esc_attr( $unique_id ); ?>" aria-hidden="true"><i class="sui-icon-link sui-sm"></i></span>
		<?php } ?>

		<div class="fui-app--wrapper">

			<?php if ( true === $advertising && ! $is_active ) { ?>

				<?php if ( isset( $addon['banner'] ) && ! empty( $addon['banner'] ) ) { ?>

					<div
							role="banner"
							class="fui-app--banner"
							data-app="<?php echo esc_attr( $addon['slug'] ); ?>"
							aria-hidden="true"
					>

						<img
								src="<?php echo esc_url( $addon['banner'] ); ?>"
								srcset="<?php echo esc_url( $addon['banner'] ); ?> 1x, <?php echo esc_url( $addon['banner_x2'] ); ?> 2x"
								alt="<?php echo esc_attr( $addon['short_title'] ); ?>"
								class="sui-image"
						/>

					</div>

				<?php } ?>

				<div class="fui-app--content">

					<div class="fui-app--title">

						<span><?php echo esc_html( $addon['title'] ); ?><?php if ( $show_pro_info && $addon['is_pro'] ) : ?>
								<span class="sui-tag sui-tag-pro">
								<?php esc_html_e( 'PRO', 'forminator' ); ?>
							</span>
							<?php endif; ?></span>

						<?php if ( ! $is_active && ! empty( $addon['documentation'] ) && forminator_is_show_documentation_link() ) { ?>
							<a href="<?php echo esc_url( $addon['documentation'] ); ?>" target="_blank"><?php esc_html_e( 'View Docs', 'forminator' ); ?></a>
						<?php } ?>

						<?php if ( $show_action ) : ?>
							<?php echo $conditions_button; /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ ?>
							<button class="sui-button-icon sui-tooltip sui-tooltip-top-right connect-integration"
									data-tooltip="<?php echo esc_attr( $tooltip ); ?>"
									data-slug="<?php echo esc_attr( $addon['slug'] ); ?>"
									data-title="<?php echo esc_attr( $addon['title'] ); ?>"
									data-image="<?php echo esc_attr( $addon['image'] ); ?>"
									data-imagex2="<?php echo esc_attr( $addon['image_x2'] ); ?>"
									data-nonce="<?php echo esc_attr( wp_create_nonce( 'forminator_addon_action' ) ); ?>"
									data-action="<?php echo esc_attr( $addon_action ); ?>"
									data-<?php echo esc_attr( $module_slug ); ?>-id="<?php echo esc_attr( $module_id ); ?>"
									data-multi-id="<?php echo esc_attr( $multi_id ); ?>">
								<i class="<?php echo esc_attr( $icon_class_action ); ?>" aria-hidden="true"></i>
								<span class="sui-screen-reader-text"><?php esc_html_e( 'Connect this integration', 'forminator' ); ?></span>
							</button>
						<?php endif; ?>

					</div>

					<?php if ( ! $is_active && isset( $addon['promotion'] ) && ! empty( $addon['promotion'] ) ) { ?>
						<span class="fui-app--description"><?php echo wp_kses_post( $addon['promotion'] ); ?></span>
					<?php } ?>

				</div>

			<?php } else { ?>

				<?php if ( isset( $addon['icon'] ) && ! empty( $addon['icon'] ) ) { ?>
					<img
							src="<?php echo esc_url( $addon['icon'] ); ?>"
							srcset="<?php echo esc_url( $addon['icon'] ); ?> 1x, <?php echo esc_url( $addon['icon_x2'] ); ?> 2x"
							alt="<?php echo esc_attr( $addon['short_title'] ); ?>"
							class="sui-image"
							aria-hidden="true"
					/>
				<?php } else { ?>
					<img
							src="<?php echo esc_url( $empty_icon ); ?>"
							srcset="<?php echo esc_url( $empty_icon ); ?> 1x, <?php echo esc_url( $empty_icon2x ); ?> 2x"
							alt="<?php echo esc_attr( $addon['short_title'] ); ?>"
							class="sui-image"
							aria-hidden="true"
					/>
				<?php } ?>

				<span><?php echo esc_html( $addon['title'] ); ?><?php echo ! empty( $addon['global_identifier'] ) ? ' - ' . esc_html( $addon['global_identifier'] ) : ''; ?><?php if ( $show_pro_info && $addon['is_pro'] ) : ?>
						<span class="sui-tag sui-tag-pro">
						<?php esc_html_e( 'PRO', 'forminator' ); ?>
					</span>
					<?php endif; ?>
					<?php
					if ( ! empty( $addon['identifier'] ) ) {
						echo ' - ' . esc_html( $addon['identifier'] );
					}
					?>
				</span>

				<?php if ( $show_action ) : ?>
					<?php echo $conditions_button; /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ ?>
				<button class="sui-button-icon sui-tooltip sui-tooltip-top-right connect-integration"
						data-tooltip="<?php echo esc_attr( $tooltip ); ?>"
						data-slug="<?php echo esc_attr( $addon['slug'] ); ?>"
						data-title="<?php echo esc_attr( $addon['title'] ); ?>"
						data-image="<?php echo esc_attr( $addon['image'] ); ?>"
						data-imagex2="<?php echo esc_attr( $addon['image_x2'] ); ?>"
						data-nonce="<?php echo esc_attr( wp_create_nonce( 'forminator_addon_action' ) ); ?>"
						data-action="<?php echo esc_attr( $addon_action ); ?>"
						data-<?php echo esc_attr( $module_slug ); ?>-id="<?php echo esc_attr( $module_id ); ?>"
						data-multi-global-id="<?php echo ! empty( $addon['global_id'] ) ? esc_attr( $addon['global_id'] ) : ''; ?>"
						data-multi-id="<?php echo esc_attr( $multi_id ); ?>">
					<i class="<?php echo esc_attr( $icon_class_action ); ?>" aria-hidden="true"></i>
					<span class="sui-screen-reader-text"><?php esc_html_e( 'Connect this integration', 'forminator' ); ?></span>
				</button>
				<?php endif; ?>

			<?php } ?>

			</div>

	</td>

</tr>
