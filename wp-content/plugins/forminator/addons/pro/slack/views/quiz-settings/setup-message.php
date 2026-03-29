<?php
/**
 * Template for setup message
 *
 * @package Forminator
 */

// defaults.
$vars = array(
	'message'       => '',
	'message_error' => '',
	'error_message' => '',
	'multi_id'      => '',
	'tags'          => array(),
	'lead_fields'   => array(),
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
		<?php esc_html_e( 'Set Up Message', 'forminator' ); ?>
	</h3>

	<p id="forminator-integration-popup__description" class="forminator-description"><?php esc_html_e( 'Configure message to be sent.', 'forminator' ); ?></p>

	<?php if ( ! empty( $vars['error_message'] ) ) : ?>
		<?php
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is already escaped.
			echo Forminator_Admin::get_red_notice( esc_html( $vars['error_message'] ) );
		?>
	<?php endif; ?>

</div>

<form>
	<div class="sui-form-field <?php echo esc_attr( ! empty( $vars['message_error'] ) ? 'sui-form-field-error' : '' ); ?>" style="margin: 0;">

		<label class="sui-label"><?php esc_html_e( 'Message', 'forminator' ); ?></label>

		<div class="sui-insert-variables">

			<textarea
				id="slack_message"
				class="sui-form-control"
				name="message"
				placeholder="<?php esc_attr_e( 'Message', 'forminator' ); ?>"
			><?php echo esc_html( $vars['message'] ); ?></textarea>

			<select class="sui-variables" data-textarea-id="slack_message">
				<?php foreach ( $vars['tags'] as $short_tag => $label ) : ?>
					<option value="{<?php echo esc_attr( $short_tag ); ?>}" data-content="{<?php echo esc_attr( $short_tag ); ?>}"><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
				<?php
				if ( ! empty( $vars['lead_fields'] ) ) :
					foreach ( $vars['lead_fields'] as $field ) :
						?>
					<option value="{<?php echo esc_attr( $field['element_id'] ); ?>}" data-content="{<?php echo esc_attr( $field['element_id'] ); ?>}"><?php echo esc_html( wp_strip_all_tags( $field['field_label'] ) ); ?></option>
						<?php
				endforeach;
					endif;
				?>
			</select>

		</div>

		<?php if ( ! empty( $vars['message_error'] ) ) : ?>
			<span class="sui-error-message"><?php echo esc_html( $vars['message_error'] ); ?></span>
		<?php endif; ?>

		<span class="sui-description">
			<?php
			printf(
			/* Translators: 1. Opening <a> tag with link to the article link, 2. closing <a> tag. */
				esc_html__( 'You can format your message using Slack Flavored Markdown. Find more information %1$shere%2$s.', 'forminator' ),
				'<a href="https://get.slack.help/hc/en-us/articles/202288908-how-can-i-add-formatting-to-my-messages" target="_blank">',
				'</a>'
			);
			?>
		</span>

		<span class="sui-description">
			<?php
			printf(
				/* Translators: 1. Quiz answers and results text. */
				esc_html__( 'By default, the message sent will include %s as an attachment using Forminator formatting to make your job easier. ', 'forminator' ),
				esc_html__( 'Quiz answers and results', 'forminator' )
			);

			printf(
				/* Translators: 1. Opening <a> tag with link to the message attach link, 2. closing <a> tag. */
				esc_html__( 'More information about attachments can be found %1$shere%2$s.', 'forminator' ),
				'<a href="https://api.slack.com/docs/message-attachments" target="_blank">',
				'</a>'
			);
			?>
		</span>

	</div>

	<input type="hidden" name="multi_id" value="<?php echo esc_attr( $vars['multi_id'] ); ?>">
</form>
