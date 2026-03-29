<?php
/**
 * Template pick name.
 *
 * @package Forminator
 */

$vars = array(
	'error_message'            => '',
	'multi_id'                 => '',
	'tags_error'               => '',
	'forms'                    => array(),
	'double_opt_form_id'       => '',
	'double_opt_form_id_error' => '',
	'instantresponders'        => 0,
	'instantresponders_error'  => '',
	'lastmessage'              => 0,
	'lastmessage_error'        => '',
	'tags_fields'              => array(),
	'tags_selected_fields'     => array(),
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

	<h3 id="forminator-integration-popup__title" class="sui-box-title sui-lg" style="overflow: initial; white-space: normal; text-overflow: initial;"><?php esc_html_e( 'Additional Options', 'forminator' ); ?></h3>

	<p id="forminator-integration-popup__description" class="sui-description"><?php esc_html_e( 'Configure additional options for ActiveCampaign integration.', 'forminator' ); ?></p>

	<?php if ( ! empty( $vars['error_message'] ) ) : ?>
		<?php
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is already escaped.
			echo Forminator_Admin::get_red_notice( esc_html( $vars['error_message'] ) );
		?>
	<?php endif; ?>

</div>

<form>

	<div class="sui-form-field<?php echo esc_attr( ! empty( $vars['tags_error'] ) ? ' sui-form-field-error' : '' ); ?>">

		<label class="sui-label" for="tags"><?php esc_html_e( 'Tags', 'forminator' ); ?></label>

		<select name="tags[]"
			multiple="multiple"
			data-reorder="1"
			data-tags="true"
			data-token-separators="[',']"
			data-placeholder=""
			data-allow-clear="false"
			id="tags"
			class="sui-select"
		>

			<?php foreach ( $vars['tags_selected_fields'] as $forminator_field ) : ?>

				<option value="<?php echo esc_attr( $forminator_field['element_id'] ); ?>"
					selected="selected">
					<?php echo esc_html( wp_strip_all_tags( $forminator_field['field_label'] ) ); ?>
				</option>

			<?php endforeach; ?>

			<?php foreach ( $vars['tags_fields'] as $forminator_field ) : ?>

				<option value="{<?php echo esc_attr( $forminator_field['element_id'] ); ?>}">
					<?php echo esc_html( wp_strip_all_tags( $forminator_field['field_label'] ) . ' | ' . $forminator_field['element_id'] ); ?>
				</option>

			<?php endforeach; ?>

		</select>

		<?php if ( ! empty( $vars['tags_error'] ) ) : ?>
			<span class="sui-error-message"><?php echo esc_html( $vars['tags_error'] ); ?></span>
		<?php endif; ?>

		<span class="sui-description">
			<?php esc_html_e( 'Tags for contact that sent to ActiveCampaign.', 'forminator' ); ?>
		</span>

	</div>

	<div class="sui-form-field <?php echo esc_attr( ! empty( $vars['double_opt_form_id_error'] ) ? 'sui-form-field-error' : '' ); ?>">
		<label class="sui-label" for="double_opt_form_id"><?php esc_html_e( 'Double Opt-In Form', 'forminator' ); ?></label>
		<select name="double_opt_form_id" id="double_opt_form_id" class="sui-select" data-placeholder="<?php esc_html_e( 'No form selected', 'forminator' ); ?>">
			<option></option>
			<?php foreach ( $vars['forms'] as $form_id => $form_name ) : ?>
				<option value="<?php echo esc_attr( $form_id ); ?>" <?php selected( $form_id, $vars['double_opt_form_id'] ); ?>><?php echo esc_html( $form_name ); ?></option>
			<?php endforeach; ?>
		</select>
		<?php if ( ! empty( $vars['double_opt_form_id_error'] ) ) : ?>
			<span class="sui-error-message"><?php echo esc_html( $vars['double_opt_form_id_error'] ); ?></span>
		<?php endif; ?>
		<span class="sui-description">
			<?php
			esc_html_e(
				'Select which ActiveCampaign form will be used when adding to ActiveCampaign to send the opt-in email. You can read more information ',
				'forminator'
			);
			?>
			<a href="https://help.activecampaign.com/hc/en-us/articles/115000839230-How-do-I-enable-double-opt-in-confirmation-" target="_blank">here</a>.
		</span>
	</div>

	<div class="sui-row">
		<div class="sui-col-md-6">

			<div class="sui-form-field <?php echo esc_attr( ! empty( $vars['instantresponders_error'] ) ? 'sui-form-field-error' : '' ); ?>">
				<label class="sui-toggle">

					<input type="checkbox"
							name="instantresponders"
							id="instantresponders"
							value="1"
						<?php checked( 1, $vars['instantresponders'] ); ?>>
					<span class="sui-toggle-slider"></span>
					<span class="sui-toggle-label" for="instantresponders"><?php esc_html_e( 'Enable Instant Responders', 'forminator' ); ?></span>
				</label>
				<?php if ( ! empty( $vars['instantresponders_error'] ) ) : ?>
					<span class="sui-error-message"><?php echo esc_html( $vars['instantresponders_error'] ); ?></span>
				<?php endif; ?>
				<span class="sui-description">
					<?php
					esc_html_e(
						'When the instant responders option is enabled, ActiveCampaign will send any instant responders set up when the contact is added to the list.
						This option is not available to users on a free trial.',
						'forminator'
					);
					?>
				</span>
			</div>

		</div>
		<div class="sui-col-md-6">

			<div class="sui-form-field <?php echo esc_attr( ! empty( $vars['lastmessage_error'] ) ? 'sui-form-field-error' : '' ); ?>">
				<label class="sui-toggle">
					<input type="checkbox"
							name="lastmessage"
							id="lastmessage"
							value="1"
						<?php checked( 1, $vars['lastmessage'] ); ?>>
					<span class="sui-toggle-slider"></span>
					<span class="sui-toggle-label"><?php esc_html_e( 'Send last broadcast campaign', 'forminator' ); ?></span>
				</label>
				<?php if ( ! empty( $vars['lastmessage_error'] ) ) : ?>
					<span class="sui-error-message"><?php echo esc_html( $vars['lastmessage_error'] ); ?></span>
				<?php endif; ?>
				<span class="sui-description">
					<?php
					esc_html_e(
						'When the send last broadcast campaign option is enabled, ActiveCampaign will send the last campaign sent out to the list to the contact being added.
								This option is not available to users on a free trial.',
						'forminator'
					);
					?>
				</span>
			</div>

		</div>
	</div>

	<input type="hidden" name="multi_id" value="<?php echo esc_attr( $vars['multi_id'] ); ?>">
</form>
