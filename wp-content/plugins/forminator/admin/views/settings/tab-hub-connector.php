<?php
/**
 * Template admin/views/settings/tab-hub-connector.php
 *
 * @package Forminator
 */

?>
<div id="forminator-section-settings-hub-connector" class="sui-box-settings-row">

	<div class="sui-box-settings-col-1">
		<span class="sui-settings-label"><?php esc_html_e( 'Hub Connector', 'forminator' ); ?></span>
		<span class="sui-description">
			<?php
			esc_html_e( 'Connect your site to a free WPMU DEV account to unlock the plugin’s Free plan features.', 'forminator' );
			?>
		</span>
	</div>

	<div class="sui-box-settings-col-2">
		<?php if ( Forminator_Hub_Connector::hub_connector_connected() ) { ?>
			<button type="button" class="sui-button sui-button-ghost" data-modal-open="forminator-disconnect-hub-modal">
				<i class="sui-icon-plug-disconnected" aria-hidden="true"></i>
				<?php esc_html_e( 'Disconnect site', 'forminator' ); ?>
			</button>
			<?php
			$this->template( 'settings/hub-connector/popup' );
		} else {
			?>
			<a class="sui-button sui-button-bright-blue"
				href="<?php echo esc_url( Forminator_Hub_Connector::get_hub_connect_url() ); ?>">
				<i class="sui-icon-plug-connected" aria-hidden="true"></i>
				<?php echo esc_html( Forminator_Hub_Connector::get_hub_connect_cta_text() ); ?>
			</a>
		<?php } ?>
	</div>

</div>