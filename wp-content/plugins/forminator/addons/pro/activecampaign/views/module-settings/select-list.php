<?php
/**
 * Template pick name.
 *
 * @package Forminator
 */

$vars = array(
	'error_message' => '',
	'list_id'       => '',
	'list_id_error' => '',
	'multi_id'      => '',
	'lists'         => array(),
);
/**
 * Template variable
 *
 * @var array $template_vars */
foreach ( $template_vars as $key => $val ) {
	$vars[ $key ] = $val;
}
?>

<div class="forminator-integration-popup__header">

	<h3 id="forminator-integration-popup__title" class="sui-box-title sui-lg" style="overflow: initial; white-space: normal; text-overflow: initial;"><?php esc_html_e( 'Choose Contact List', 'forminator' ); ?></h3>

	<p id="forminator-integration-popup__description" class="sui-description"><?php esc_html_e( 'Pick ActiveCampaign List for new contacts to be added to.', 'forminator' ); ?></p>

	<?php if ( ! empty( $vars['error_message'] ) ) : ?>
		<?php
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is already escaped.
			echo Forminator_Admin::get_red_notice( esc_html( $vars['error_message'] ) );
		?>
	<?php endif; ?>

</div>

<form>
	<div class="sui-form-field <?php echo esc_attr( ! empty( $vars['list_id_error'] ) ? 'sui-form-field-error' : '' ); ?>" style="margin-bottom: 0;">
		<label class="sui-label" for="activecampaign-list-id"><?php esc_html_e( 'List', 'forminator' ); ?></label>
		<select class="sui-select" name="list_id" id="activecampaign-list-id" data-placeholder="<?php esc_html_e( 'Please select a list', 'forminator' ); ?>">
			<option></option>
			<?php foreach ( $vars['lists'] as $list_id => $list_name ) : ?>
				<option value="<?php echo esc_attr( $list_id ); ?>" <?php selected( $vars['list_id'], $list_id ); ?>><?php echo esc_html( $list_name ); ?></option>
			<?php endforeach; ?>
		</select>
		<?php if ( ! empty( $vars['list_id_error'] ) ) : ?>
			<span class="sui-error-message"><?php echo esc_html( $vars['list_id_error'] ); ?></span>
		<?php endif; ?>
	</div>
	<input type="hidden" name="multi_id" value="<?php echo esc_attr( $vars['multi_id'] ); ?>">
</form>
