<?php
/**
 * Template admin/views/common/reports/content-none.php
 *
 * @package Forminator
 */

?>
<div class="sui-box sui-message">

	<?php if ( forminator_is_show_branding() ) : ?>
		<img src="<?php echo esc_url( forminator_plugin_url() . 'assets/img/' . $args['image'] ); ?>"
			srcset="<?php echo esc_url( forminator_plugin_url() . 'assets/img/' . $args['image'] ); ?> 1x,
			<?php echo esc_url( forminator_plugin_url() . 'assets/img/' . $args['image_x2'] ); ?> 2x"
			alt="<?php esc_html_e( 'Forminator', 'forminator' ); ?>"
			class="sui-image"
			aria-hidden="true"/>
	<?php endif; ?>

	<div class="sui-message-content">
		<h2><?php echo esc_html( $args['title'] ); ?></h2>
		<p><?php echo esc_html( $args['description'] ); ?></p>
	</div>

</div>
