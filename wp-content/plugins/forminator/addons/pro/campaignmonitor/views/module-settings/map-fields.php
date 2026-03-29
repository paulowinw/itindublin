<?php
/**
 * Template map fields
 *
 * @package Forminator
 */

$vars = array(
	'error_message' => '',
	'multi_id'      => '',
	'fields_map'    => array(),
	'fields'        => array(),
	'module_fields' => array(),
	'email_fields'  => array(),
);

/**
 * Template variables.
 *
 * @var array $template_vars
 * */
foreach ( $template_vars as $key => $val ) {
	$vars[ $key ] = $val;
} ?>

<div class="forminator-integration-popup__header">

	<h3 id="forminator-integration-popup__title" class="sui-box-title sui-lg" style="overflow: initial; white-space: normal; text-overflow: initial;">
		<?php esc_html_e( 'Assign Fields', 'forminator' ); ?>
	</h3>

	<p id="forminator-integration-popup__description" class="sui-description">
		<?php esc_html_e( "Match up your module fields with your Campaign Monitor fields to make sure we're sending data to the right place.", 'forminator' ); ?>
	</p>

	<?php if ( ! empty( $vars['error_message'] ) ) : ?>
		<?php
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is already escaped.
			echo Forminator_Admin::get_red_notice( esc_html( $vars['error_message'] ) );
		?>
	<?php endif; ?>

</div>

<form>

	<table class="sui-table" style="margin-bottom: 0;">

		<thead>

			<tr>
				<th><?php esc_html_e( 'Campaign Monitor Field', 'forminator' ); ?></th>
				<th><?php esc_html_e( 'Forminator Field', 'forminator' ); ?></th>
			</tr>

		</thead>

		<tbody>

			<?php foreach ( $vars['fields'] as $key => $field_title ) : ?>

				<tr>

					<td>
						<?php echo esc_html( $field_title ); ?>
						<?php if ( 'default_field_email' === $key || 'default_field_name' === $key ) : ?>
							<span class="integrations-required-field">*</span>
						<?php endif; ?>
					</td>

					<td>
						<?php
						$forminator_fields = $vars['module_fields'];
						if ( 'default_field_email' === $key ) {
							$forminator_fields = $vars['email_fields'];
						}
						$current_error    = '';
						$current_selected = '';
						if ( ! empty( $vars[ $key . '_error' ] ) ) {
							$current_error = $vars[ $key . '_error' ];
						}
						if ( ! empty( $vars['fields_map'][ $key ] ) ) {
							$current_selected = $vars['fields_map'][ $key ];
						}
						?>
						<div class="sui-form-field <?php echo esc_attr( ! empty( $current_error ) ? 'sui-form-field-error' : '' ); ?>">
							<label>
								<?php // DEV NOTE: Select without JS. ?>
								<select style="max-width: none;" name="fields_map[<?php echo esc_attr( $key ); ?>]">
									<option value=""><?php esc_html_e( 'None', 'forminator' ); ?></option>
									<?php foreach ( $forminator_fields as $field_key => $field_label ) : ?>
										<option value="<?php echo esc_attr( $field_key ); ?>"
											<?php selected( $current_selected, $field_key ); ?>>
											<?php echo esc_html( $field_label . ' | ' . $field_key ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</label>
							<?php if ( ! empty( $current_error ) ) : ?>
								<span class="sui-error-message"><?php echo esc_html( $current_error ); ?></span>
							<?php endif; ?>
						</div>
					</td>

				</tr>

			<?php endforeach; ?>

		</tbody>

	</table>

	<input type="hidden" name="multi_id" value="<?php echo esc_attr( $vars['multi_id'] ); ?>">

</form>
