<?php
/**
 * Template admin/views/addons/addons-list.php
 *
 * @package Forminator
 */

if ( empty( $addons ) || empty( $addons->pid ) ) {
	return;
}

$addons_slug = Forminator_Admin_Addons_Page::get_addon_slug( $addons->pid );

$is_network_active      = $addons->is_network_admin && is_plugin_active_for_network( $addons->filename );
$free_addon             = ! empty( $addons->is_free );
$connected_free_addon   = $free_addon && Forminator_Hub_Connector::hub_connector_connected();
$unconnected_free_addon = $free_addon && ! Forminator_Hub_Connector::hub_connector_connected();

$tags = array();

if ( FORMINATOR_PRO || $connected_free_addon ) {

	if ( $addons->is_installed ) {

		if ( is_plugin_active( $addons->filename ) ) {
			$tags['label'] = esc_html__( 'Active', 'forminator' );
			$tags['class'] = 'sui-tag sui-tag-sm sui-tag-blue';
		} else {
			$tags['label'] = esc_html__( 'Inactive', 'forminator' );
			$tags['class'] = 'sui-tag sui-tag-sm';
		}
	} else {
		$tags['label'] = esc_html__( 'Not Installed', 'forminator' );
		$tags['class'] = 'sui-tag sui-tag-sm sui-tag-grey';
	}
} ?>

<div class="sui-col-md-6 addons-<?php echo esc_attr( $addons->pid ); ?>">

	<div id="forminator-addon-<?php echo esc_attr( $addons->pid ); ?>__card" class="sui-box forminator-addon-card">

		<div class="forminator-addon-card--body">

			<?php if ( forminator_is_show_branding() ) : ?>
				<div class="forminator-addon-card--body-left" aria-hidden="true">
					<div class="forminator-addon-card--thumb" style="background-image: url(<?php echo esc_url( $addons->url->thumbnail ); ?>);"></div>
				</div>
			<?php endif; ?>

			<div class="forminator-addon-card--body-right">

				<div class="forminator-addon-card--title">

					<h3><?php echo esc_html( $addons->name ); ?></h3>

					<div class="forminator-addon-card--tags">

						<?php if ( ! empty( $tags['label'] ) ) { ?>
							<span class="<?php echo esc_attr( $tags['class'] ); ?>"><?php echo esc_html( $tags['label'] ); ?></span>
						<?php } ?>

						<?php if ( ( FORMINATOR_PRO || $free_addon ) && $addons->is_installed && $addons->has_update ) { ?>
							<?php /* translators: Plugin latest version */ ?>
							<span class="sui-tag sui-tag-yellow sui-tag-sm"><?php printf( esc_html__( 'v%s update available', 'forminator' ), esc_html( $addons->version_latest ) ); ?></span>
						<?php } ?>

						<?php if ( ! FORMINATOR_PRO && $unconnected_free_addon ) { ?>
							<span class="sui-tag sui-tag-free-plan sui-tag-sm"><?php esc_html_e( 'FREE PLAN', 'forminator' ); ?></span>
						<?php } ?>

					</div>

				</div>

				<p class="sui-description"><?php echo esc_html( $addons->info ); ?></p>

			</div>

		</div>

		<div class="forminator-addon-card--footer">

			<?php if ( FORMINATOR_PRO || $free_addon ) { ?>
				<div class="forminator-addon-card--footer-left">
					<a
						role="button"
						class="forminator-pseudo-link addons-page-details"
						data-nonce="<?php echo esc_attr( wp_create_nonce( 'forminator_addons_page_details' ) ); ?>"
						data-form-id="<?php echo esc_attr( $addons->pid ); ?>"
						data-modal-title="<?php echo esc_attr( $addons->name ); ?>"
						data-modal-mask="false"
						data-modal="addons_page_details"
					>
						<?php esc_html_e( 'Details', 'forminator' ); ?>
					</a>
				</div>
			<?php } ?>

			<div class="forminator-addon-card--footer-right">

				<?php Forminator_Admin_Addons_Page::get_instance()->addons_render( 'addon-cta-button', $addons->pid, $addons ); ?>

			</div>

		</div>

	</div>

</div>
