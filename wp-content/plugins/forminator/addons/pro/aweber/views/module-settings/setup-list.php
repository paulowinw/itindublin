<?php
/**
 * Template Setup list.
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
 * Template variables.
 *
 * @var array $template_vars
 * */
foreach ( $template_vars as $key => $val ) {
	$vars[ $key ] = $val;
}
?>

<div class="forminator-integration-popup__header">

	<h3 id="forminator-integration-popup__title" class="sui-box-title sui-lg"><?php esc_html_e( 'Choose List', 'forminator' ); ?></h3>

	<p id="forminator-integration-popup__description" class="sui-description"><?php esc_html_e( 'Pick AWeber List for new subscriber to be added to.', 'forminator' ); ?></p>

	<?php if ( ! empty( $vars['error_message'] ) ) : ?>
		<?php
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is already escaped.
			echo Forminator_Admin::get_red_notice( esc_html( $vars['error_message'] ) );
		?>
	<?php endif; ?>

</div>

<form>

	<div class="sui-form-field<?php echo esc_attr( ! empty( $vars['list_id_error'] ) ? ' sui-form-field-error' : '' ); ?>" style="margin: 0;">

		<label class="sui-label" for="aweber-list-id"><?php esc_html_e( 'List', 'forminator' ); ?></label>

		<select name="list_id" id="aweber-list-id" class="sui-select" data-placeholder="<?php esc_html_e( 'Please select a list', 'forminator' ); ?>">
			<option></option>
			<?php foreach ( $vars['lists'] as $list_id => $list_name ) : ?>
				<option value="<?php echo esc_attr( $list_id ); ?>"
					<?php selected( $vars['list_id'], $list_id ); ?>>
					<?php echo esc_html( $list_name ); ?>
				</option>
			<?php endforeach; ?>
		</select>

		<?php if ( ! empty( $vars['list_id_error'] ) ) : ?>
			<span class="sui-error-message"><?php echo esc_html( $vars['list_id_error'] ); ?></span>
		<?php endif; ?>

	</div>

	<input type="hidden" name="multi_id" value="<?php echo esc_attr( $vars['multi_id'] ); ?>">

</form>
