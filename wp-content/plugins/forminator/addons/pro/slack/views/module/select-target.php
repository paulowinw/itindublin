<?php
/**
 * Template for select target
 *
 * @package Forminator
 */

// defaults.
$vars = array(
	'error_message'   => '',
	'target_id'       => '',
	'target_id_error' => '',
	'targets'         => array(),
	'help_message'    => '',
);
/**
 * Template variables.
 *
 * @var array $template_vars
 * */
foreach ( $template_vars as $key => $val ) {
	$vars[ $key ] = $val;
}
?>

<div class="forminator-integration-popup__header">

	<h3 id="forminator-integration-popup__title" class="sui-box-title sui-lg" style="overflow: initial; white-space: normal; text-overflow: initial;">
		<?php esc_html_e( 'Select Target', 'forminator' ); ?>
	</h3>

	<p id="forminator-integration-popup__description" class="sui-description"><?php echo esc_html( $vars['help_message'] ); ?></p>

	<?php if ( ! empty( $vars['error_message'] ) ) : ?>
		<?php
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is already escaped.
			echo Forminator_Admin::get_red_notice( esc_html( $vars['error_message'] ) );
		?>
	<?php endif; ?>

</div>

<form>

	<div class="sui-form-field <?php echo esc_attr( ! empty( $vars['target_id_error'] ) ? 'sui-form-field-error' : '' ); ?>" style="margin-bottom: 0;">
		<label class="sui-label" for="slack-target-id"><?php esc_html_e( 'Type', 'forminator' ); ?></label>

		<select name="target_id" id="slack-target-id" class="sui-select" data-placeholder="<?php esc_html_e( 'Please select target', 'forminator' ); ?>" data-search="true">
			<option></option>
			<?php foreach ( $vars['targets'] as $target_id => $target_name ) : ?>
				<option value="<?php echo esc_attr( $target_id ); ?>" <?php selected( $vars['target_id'], $target_id ); ?>><?php echo esc_html( $target_name ); ?></option>
			<?php endforeach; ?>
		</select>

		<?php if ( ! empty( $vars['target_id_error'] ) ) : ?>
			<span class="sui-error-message"><?php echo esc_html( $vars['target_id_error'] ); ?></span>
		<?php endif; ?>
	</div>

	<input type="hidden" name="multi_id" value="<?php echo esc_attr( $vars['multi_id'] ); ?>">
</form>
