<?php
/**
 * Template admin/views/common/entries/content-none.php
 *
 * @package Forminator
 */

	$none_title = esc_html__( 'Submissions', 'forminator' );
	$none_text  = esc_html__( 'You haven’t received any form, poll or quiz submissions yet. When you do, you’ll be able to view all the data here.', 'forminator' );
	$form_id    = filter_input( INPUT_GET, 'form_id', FILTER_VALIDATE_INT );
if ( $form_id ) {
	$none_title = esc_html( forminator_get_form_name( $form_id ) );
	$none_text  = sprintf(
	/* translators: %s: Form type helper */
		esc_html__( 'You haven\'t received any submissions for this %s yet. When you do, you\'ll be able to view all the data here.', 'forminator' ),
		esc_html( forminator_get_form_type_helper( true ) )
	);
}
?>
<div class="sui-box sui-message">

	<?php if ( forminator_is_show_branding() ) : ?>
		<img src="<?php echo esc_url( forminator_plugin_url() . 'assets/images/forminator-no-result.png' ); ?>"
			srcset="<?php echo esc_url( forminator_plugin_url() . 'assets/images/forminator-no-result.png' ); ?> 1x, <?php echo esc_url( forminator_plugin_url() . 'assets/images/forminator-no-result.png' ); ?> 2x"
			alt="<?php esc_html_e( 'Forminator', 'forminator' ); ?>"
			class="sui-image"
			aria-hidden="true"/>
	<?php endif; ?>

	<div class="sui-message-content">

		<h2><?php echo esc_html( $none_title ); ?></h2>

		<p><?php echo esc_html( $none_text ); ?></p>

	</div>

</div>
