<?php
/**
 * Template for choose worksheet.
 *
 * @package    Forminator
 */

// Defaults.
$vars = array(
	'error_message' => '',
	'file_id'       => '',
	'worksheet_id'  => '',
	'worksheets'    => array(),
);

// @var array $template_vars Variables.
foreach ( $template_vars as $key => $val ) {
	$vars[ $key ] = $val;
} ?>

<div class="forminator-integration-popup__header">

	<h3 id="forminator-integration-popup__title" class="sui-box-title sui-lg" style="overflow: initial; white-space: normal; text-overflow: initial;">
		<?php esc_html_e( 'Choose Worksheet', 'forminator' ); ?>
	</h3>

	<p id="forminator-integration-popup__description" class="sui-description">
		<?php esc_html_e( 'Choose the worksheet you want to send submissions to.', 'forminator' ); ?>
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
			// The message is escaped.
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo Forminator_Admin::get_red_notice( esc_html( $vars['error_message'] ) );
		?>
	<?php endif; ?>

	<form>
		<div class="sui-form-field <?php echo esc_attr( ! empty( $vars['worksheet_id_error'] ) ? 'sui-form-field-error' : '' ); ?>">
			<label class="sui-label"><?php esc_html_e( 'Worksheet', 'forminator' ); ?></label>
			<select name="worksheet_id" class="sui-select sui-form-control">
				<option value=""><?php esc_html_e( 'Select worksheet', 'forminator' ); ?></option>
				<?php
				foreach ( $vars['worksheets'] as $key => $sheet ) {
					$selected = ( strval( $key ) === strval( $vars['worksheet_id'] ) ) ? " selected='selected'" : ''
					?>
				<option value="<?php echo esc_attr( $key ); ?>"<?php echo esc_html( $selected ); ?>><?php echo esc_html( $sheet ); ?></option>
				<?php } ?>
			</select>
			<?php if ( ! empty( $vars['worksheet_id_error'] ) ) : ?>
				<span class="sui-error-message"><?php echo esc_html( $vars['worksheet_id_error'] ); ?></span>
			<?php endif; ?>
		</div>

		<input type="hidden" name="multi_id" value="<?php echo esc_attr( $vars['multi_id'] ); ?>">
		<input type="hidden" name="sheet_type" value="worksheet"/>

	</form>
</div>
