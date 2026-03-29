<?php
/**
 * Template admin/views/templates/cloud/upgrade-content.php
 *
 * @package Forminator
 */

$logo_name = Forminator_Hub_Connector::get_hub_connect_logo();
?>
<div class="sui-box sui-message sui-message-lg">
	<img src="<?php echo esc_url( forminator_plugin_url() . 'assets/images/' . $logo_name . '.png' ); ?>"
		srcset="<?php echo esc_url( forminator_plugin_url() . 'assets/images/' . $logo_name . '.png' ); ?> 1x, <?php echo esc_url( forminator_plugin_url() . 'assets/images/' . $logo_name . '@2x.png' ); ?> 2x"
		alt="<?php esc_attr_e( 'Forminator no result', 'forminator' ); ?>"
		class="sui-image sui-image-center fui-image">
	<div class="sui-message-content">
		<h2><?php echo esc_html( Forminator_Hub_Connector::get_hub_connect_title() ); ?></h2>
		<p>
			<?php echo esc_html( Forminator_Hub_Connector::get_hub_connect_description() ); ?>
		</p>
		<p>
			<a href="<?php echo esc_url( Forminator_Hub_Connector::get_hub_connect_url() ); ?>" class="sui-button sui-button-icon-right sui-button-bright-blue">
				<?php echo esc_html( Forminator_Hub_Connector::get_hub_connect_cta_text() ); ?>
			</a>
		</p>
	</div>
</div>
