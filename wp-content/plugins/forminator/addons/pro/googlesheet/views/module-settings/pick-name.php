<?php
/**
 * Template for pick name
 *
 * @package Forminator
 */

$vars = array(
	'error_message' => '',
	'name'          => '',
	'name_error'    => '',
	'multi_id'      => '',
	'file_id'       => '',
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
		<?php esc_html_e( 'Set Up Name', 'forminator' ); ?>
	</h3>

	<p id="forminator-integration-popup__description" class="sui-description">
		<?php esc_html_e( 'Set up a friendly name for this integration, so you can easily identify it.', 'forminator' ); ?>
	</p>

	<?php if ( ! empty( $vars['file_id'] ) ) : ?>
		<div
			role="alert"
			class="sui-notice sui-notice-blue sui-active"
			style="display: block; text-align: left;"
			aria-live="assertive"
		>

		<div class="sui-notice-content">

			<div class="sui-notice-message">

					<span class="sui-notice-icon sui-icon-info" aria-hidden="true"></span>

					<p>
						<?php
						printf(
						/* Translators: 1. Opening <a> tag with link spreadsheet link, 2. closing <a> tag. */
							esc_html__( 'You can open your current Spreadsheet %1$shere%2$s.', 'forminator' ),
							'<a target="_blank" href="https://docs.google.com/spreadsheets/d/' . esc_attr( $vars['file_id'] ) . '">',
							'</a>'
						);
						?>
					</p>

				</div>

			</div>

		</div>
	<?php endif; ?>

	<?php if ( ! empty( $vars['error_message'] ) ) : ?>
		<?php
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is already escaped.
			echo Forminator_Admin::get_red_notice( esc_html( $vars['error_message'] ) );
		?>
	<?php endif; ?>

</div>

<form>
	<div class="sui-form-field <?php echo esc_attr( ! empty( $vars['name_error'] ) ? 'sui-form-field-error' : '' ); ?>" style="margin: 0;">
		<label class="sui-label"><?php esc_html_e( 'Name', 'forminator' ); ?></label>
		<input
				class="sui-form-control"
				name="name" placeholder="<?php esc_attr_e( 'Friendly Name', 'forminator' ); ?>"
				value="<?php echo esc_attr( $vars['name'] ); ?>" />
		<?php if ( ! empty( $vars['name_error'] ) ) : ?>
			<span class="sui-error-message"><?php echo esc_html( $vars['name_error'] ); ?></span>
		<?php endif; ?>
	</div>
	<input type="hidden" name="multi_id" value="<?php echo esc_attr( $vars['multi_id'] ); ?>">
</form>
