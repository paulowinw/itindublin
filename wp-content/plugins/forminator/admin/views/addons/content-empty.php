<?php
/**
 * Template admin/views/addons/content-empty.php
 *
 * @package Forminator
 */

$can_browse  = isset( $browse ) && is_bool( $browse ) === true;
$can_refresh = isset( $refresh ) && is_bool( $refresh ) === true;
$url_check   = add_query_arg( 'action', 'check-updates' );
?>

<div class="sui-box">

	<div class="sui-message">

		<?php if ( forminator_is_show_branding() ) : ?>
			<img
				src="<?php echo esc_url( forminator_plugin_url() . 'assets/images/forminator-no-result.png' ); ?>"
				srcset="<?php echo esc_url( forminator_plugin_url() . 'assets/images/forminator-no-result.png' ); ?> 1x, <?php echo esc_url( forminator_plugin_url() . 'assets/images/forminator-no-result@2x.png' ); ?> 2x"
				alt="<?php esc_html_e( 'Forminator', 'forminator' ); ?>"
				class="sui-image"
			/>
		<?php endif; ?>

		<div class="sui-message-content">

			<?php if ( isset( $title ) && '' !== trim( $title ) ) { ?>
				<h2><?php echo esc_html( $title ); ?></h2>
			<?php } ?>

			<?php if ( isset( $description ) && '' !== trim( $description ) ) { ?>
				<p><?php echo esc_html( $description ); ?></p>
			<?php } ?>

			<?php if ( $can_browse || $can_refresh ) { ?>
				<p>
					<?php
					// BUTTON: Browse Add-Ons.
					if ( $can_browse ) {
						Forminator_Admin_Addons_Page::get_instance()->render_template(
							'admin/views/addons/action-button',
							array(
								'label' => esc_html__( 'Browse Add-Ons', 'forminator' ),
							)
						);
					}

					// BUTTON: Check Again.
					if ( $can_refresh ) {
						?>
						<a href="<?php echo esc_url( $url_check ); ?>" class="sui-button sui-button-blue">
							<span class="sui-loading-text">
								<span class="sui-icon-refresh" aria-hidden="true"></span>
								<?php esc_html_e( 'Check Again', 'forminator' ); ?>
							</span>
							<span class="sui-icon-loader sui-loading" aria-hidden="true"></span>
						</a>
					<?php } ?>
				</p>
			<?php } ?>

		</div>

	</div>

</div>
