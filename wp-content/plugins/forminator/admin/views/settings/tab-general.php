<?php
/**
 * Template admin/views/settings/tab-general.php
 *
 * @package Forminator
 */

$section = Forminator_Core::sanitize_text_field( 'section', 'dashboard' );
$nonce   = wp_create_nonce( 'forminator_save_dashboard_settings' );
?>

<div class="sui-box" data-nav="dashboard" style="<?php echo esc_attr( 'dashboard' !== $section ? 'display: none;' : '' ); ?>">

	<div class="sui-box-header">
		<h2 class="sui-box-title"><?php esc_html_e( 'General', 'forminator' ); ?></h2>
	</div>

	<form class="forminator-settings-save" action="">

		<div class="sui-box-body">

			<?php $this->template( 'settings/tab-dashboard' ); ?>

			<?php $this->template( 'settings/tab-autosave' ); ?>

			<?php $this->template( 'settings/tab-emails' ); ?>

			<?php $this->template( 'settings/tab-pagination' ); ?>

			<?php $this->template( 'settings/tab-editor' ); ?>

			<?php
			if ( ! FORMINATOR_PRO && ! class_exists( 'WPMUDEV_Dashboard' ) ) {
				$this->template( 'settings/tab-hub-connector' );
			}

			if ( ! forminator_usage_tracking_disabled() && ( is_wpmu_dev_admin() || ! forminator_can_whitelabel() ) ) {
				$this->template( 'settings/tab-tracking' );
			}
			?>

		</div>

		<div class="sui-box-footer">

			<div class="sui-actions-right">

				<button class="sui-button sui-button-blue wpmudev-action-done" data-title="<?php esc_attr_e( 'General settings', 'forminator' ); ?>" data-action="dashboard_settings" data-nonce="<?php echo esc_attr( $nonce ); ?>">
					<span class="sui-loading-text"><?php esc_html_e( 'Save Settings', 'forminator' ); ?></span>
					<i class="sui-icon-loader sui-loading" aria-hidden="true"></i>
				</button>

			</div>

		</div>

	</form>

</div>
