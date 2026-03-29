<?php
/**
 * Template admin/views/templates/cloud/content.php
 *
 * @package Forminator
 */

?>
<div
	role="tabpanel"
	tabindex="0"
	id="cloud-templates-content"
	class="sui-tab-content<?php echo 'cloud' === $args['current_tab'] ? ' active' : ''; ?>"
	aria-labelledby="cloud-templates"
>
	<!-- Pro saved template box -->
	<div class="sui-box">
		<?php
		if ( ! Forminator_Hub_Connector::hub_connector_connected() ) {
			echo forminator_template( 'templates/cloud/upgrade-content' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		} else {
			echo forminator_template( 'templates/cloud/listings' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo forminator_template( 'templates/cloud/empty-content' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		?>
	</div>
</div>
