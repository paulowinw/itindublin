<?php
/**
 * Template admin/views/addons/content.php
 *
 * @package Forminator
 */

global $current_user;
$projects = $this->get_addons_by_action();
$img_path = forminator_plugin_url() . 'assets/images/';
?>

<div id="forminator-addons" class="sui-tabs">

	<div class="sui-tabs-content">

		<div
			role="tabpanel"
			tabindex="0"
			id="all-addons-content"
			class="sui-tab-content active"
			style="border: 0; padding: 0;"
			aria-labelledby="all-addons"
		>

			<?php if ( ! FORMINATOR_PRO ) { ?>
				<div id="forminator-builder-status" class="sui-box">
					<div class="sui-box-status">
						<div class="sui-status" style="height: 30px">
						<div>
							<img
								src="<?php echo esc_url( $img_path . 'icon-unlock.png' ); ?>"
								srcset="<?php echo esc_url( $img_path . 'icon-unlock.png' ); ?> 1x,<?php echo esc_url( $img_path . 'icon-unlock@2x.png' ); ?> 2x"
								alt="<?php esc_html_e( 'unlock icon', 'forminator' ); ?>"
								aria-hidden="true">
						</div>
							&nbsp;&nbsp;
							<h4 style="margin: 0;">
								<?php esc_html_e( 'Unlock all Add-ons & advanced features with Forminator Pro', 'forminator' ); ?>
							</h4>
						</div>
						<div class="sui-actions">
							<a class="sui-button sui-button-purple" href="https://wpmudev.com/project/forminator-pro/?utm_source=forminator&utm_medium=plugin&utm_campaign=forminator_addon-page-upsell" target="_blank">
								<?php esc_html_e( 'Unlock Forminator Pro', 'forminator' ); ?>
							</a>
						</div>
					</div>
				</div>
				<?php
			}

			echo forminator_template( 'common/banner/content' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			?>

			<?php
			if ( empty( $projects['all'] ) ) {
				Forminator_Admin_Addons_Page::get_instance()->render_template(
					'admin/views/addons/content-empty',
					array(
						'title'       => esc_html__( 'No Add-Ons', 'forminator' ),
						'description' => esc_html__( 'We couldn\'t find any add-on listed. Perhaps refresh the page and try again?', 'forminator' ),
					)
				);
			} else {
				?>

				<div class="sui-row">

					<?php
					foreach ( $projects['all'] as $idx => $addons ) {
						if ( ! empty( $addons ) ) {
							++$idx;

							Forminator_Admin_Addons_Page::get_instance()->addons_render( 'addons-list', $addons->pid, $addons );

							// Close current row and open a new one.
							if ( 0 === $idx % 2 ) :
								echo '</div><div class="sui-row">';
							endif;
						}
					}
					?>

				</div>

			<?php } ?>

		</div>

	</div>

</div>

<?php
foreach ( $projects['all'] as $slug => $addons ) {
	if ( ! empty( $addons ) && ( FORMINATOR_PRO || ! empty( $addons->is_free ) ) ) {
		Forminator_Admin_Addons_Page::get_instance()->addons_render( 'addons-activate-popup', $addons->pid, $addons ); // Need to remove this from the process.
		Forminator_Admin_Addons_Page::get_instance()->addons_render( 'addon-details', $addons->pid, $addons );
	}
}
