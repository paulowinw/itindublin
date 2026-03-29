<?php
/**
 * Template admin/views/templates/cloud/empty-content.php
 *
 * @package Forminator
 */

?>
<div class="sui-box sui-message sui-message-lg" id="no-templates" style="display: none;">
	<img src="<?php echo esc_url( forminator_plugin_url() . 'assets/images/forminator-no-result.png' ); ?>"
		srcset="<?php echo esc_url( forminator_plugin_url() . 'assets/images/forminator-no-result.png' ); ?> 1x, <?php echo esc_url( forminator_plugin_url() . 'assets/images/forminator-no-result@2x.png' ); ?> 2x"
		alt="<?php esc_attr_e( 'Forminator no result', 'forminator' ); ?>"
		class="sui-image sui-image-center fui-image">
	<div class="sui-message-content">
		<h2><?php esc_html_e( 'No saved templates yet', 'forminator' ); ?></h2>
		<p>
			<?php
			esc_html_e( 'Save any of your forms as cloud templates to reuse them across your sites connected to The Hub – no need to start from scratch. Your saved templates will appear here. ', 'forminator' );
			?>
			<br/>
			<?php
			if ( forminator_is_show_documentation_link() ) {
				printf(
					/* translators: %1$s - opening anchor tag, %2$s - closing anchor tag */
					esc_html__( '%1$sLearn how to save forms as cloud templates%2$s.', 'forminator' ),
					'<a href="https://wpmudev.com/docs/wpmu-dev-plugins/forminator/#templates" target="_blank">',
					'</a>'
				);
			}
			?>
		</p>
	</div>
</div>
