<?php
/**
 * Template for setup board
 *
 * @package Forminator
 */

// defaults.
$vars = array(
	'error_message'  => '',
	'board_id'       => '',
	'board_id_error' => '',
	'multi_id'       => '',
	'boards'         => array(),
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
		<?php esc_html_e( 'Assign Board', 'forminator' ); ?>
	</h3>

	<p id="forminator-integration-popup" class="sui-description"><?php esc_html_e( 'Your account is now authorized, choose which board you want Trello cards to be added to.', 'forminator' ); ?></p>

	<?php if ( ! empty( $vars['error_message'] ) ) : ?>
		<?php
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is already escaped.
			echo Forminator_Admin::get_red_notice( esc_html( $vars['error_message'] ) );
		?>
	<?php endif; ?>

</div>

<form>
	<div class="sui-form-field <?php echo esc_attr( ! empty( $vars['board_id_error'] ) ? 'sui-form-field-error' : '' ); ?>" style="margin: 0;">
		<label class="sui-label" for="trello-board-id"><?php esc_html_e( 'Board', 'forminator' ); ?></label>
			<?php // DEV NOTE: Select without JS. ?>
			<select name="board_id" style="max-width: none;" id="trello-board-id">
				<option><?php esc_html_e( 'Please select a board', 'forminator' ); ?></option>
				<?php foreach ( $vars['boards'] as $board_id => $board_name ) : ?>
					<option value="<?php echo esc_attr( $board_id ); ?>" <?php selected( $vars['board_id'], $board_id ); ?>><?php echo esc_html( $board_name ); ?></option>
				<?php endforeach; ?>
			</select>
			<?php if ( ! empty( $vars['board_id_error'] ) ) : ?>
				<span class="sui-error-message"><?php echo esc_html( $vars['board_id_error'] ); ?></span>
			<?php endif; ?>
	</div>
	<input type="hidden" name="multi_id" value="<?php echo esc_attr( $vars['multi_id'] ); ?>">
</form>
