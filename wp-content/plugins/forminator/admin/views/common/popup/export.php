<?php
/**
 * Template admin/views/common/popup/export.php
 *
 * @package Forminator
 */

$form_id = filter_input( INPUT_POST, 'id', FILTER_VALIDATE_INT );
$slug    = $args['slug'];
$_page   = 'forminator-' . forminator_get_prefix( $slug, 'c' );
$nonce   = wp_create_nonce( 'forminator_' . $slug . '_request' );

$exportable = array();
$model      = Forminator_Base_Form_Model::get_model( $form_id );
if ( $model instanceof Forminator_Base_Form_Model ) {
	$exportable = $model->to_exportable_data();
}
$text_area_id = uniqid( 'export-text-' );
?>

<div class="sui-box-body wpmudev-popup-form">

	<div class="sui-form-field">
		<textarea class="sui-form-control" readonly="readonly" rows="10" id="<?php echo esc_attr( $text_area_id ); ?>"></textarea>
		<span class="sui-description">
			<?php esc_html_e( 'Copy ALL text above, and paste it into the import dialog.', 'forminator' ); ?>

			<br>
			<?php
			printf(
			/* Translators: 1. Opening <strong> tag 2. Forminator version 3. closing <strong> tag */
				esc_html__( 'Import requires %1$sForminator %2$s%3$s or higher; older versions may not work.', 'forminator' ),
				'<b>',
				esc_html( FORMINATOR_VERSION ),
				'</b>'
			);
			?>
			</span>
		</div>

	<?php
	if ( 'form' === $slug && forminator_is_show_documentation_link() ) {
		echo forminator_template( 'common/popup/cloud-templates-notice', array( 'slug' => $slug ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	?>

</div>

<div class="sui-box-footer">

	<button class="sui-button forminator-popup-cancel" data-a11y-dialog-hide="forminator-popup"><?php esc_html_e( 'Close', 'forminator' ); ?></button>

	<div class="sui-actions-right">

		<form action="<?php echo esc_attr( admin_url( 'admin.php?page=' . $_page ) ); ?>" method="post">
			<input type="hidden" name="forminator_action" value="export">
			<input type="hidden" name="forminatorNonce" value="<?php echo esc_attr( $nonce ); ?>">
			<input type="hidden" name="id" value="<?php echo esc_attr( $form_id ); ?>">
			<button class="sui-button sui-button-primary"><i class="sui-icon-download" aria-hidden="true"></i> <?php esc_html_e( 'Download', 'forminator' ); ?></button>
		</form>

	</div>

</div>

<?php // using jquery to avoid html escape on popup ajax load. ?>
<script type="text/javascript">
	jQuery('#<?php echo esc_attr( $text_area_id ); ?>').val(JSON.stringify(<?php echo wp_json_encode( $exportable ); ?>));
</script>
