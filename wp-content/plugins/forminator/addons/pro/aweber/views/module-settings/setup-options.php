<?php
/**
 * Template Setup options.
 *
 * @package Forminator
 */

$vars = array(
	'error_message'        => '',
	'multi_id'             => '',
	'ad_tracking'          => '',
	'ad_tracking_error'    => '',
	'tags_fields'          => array(),
	'tags_selected_fields' => array(),
	'fields'               => array(),
	'tags_error'           => '',
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

	<h3 id="forminator-integration-popup__title" class="sui-box-title sui-lg"><?php esc_html_e( 'Additional Options', 'forminator' ); ?></h3>

	<p id="forminator-integration-popup__description" class="sui-description"><?php esc_html_e( 'Configure additional options for AWeber integration.', 'forminator' ); ?></p>

	<?php if ( ! empty( $vars['error_message'] ) ) : ?>
		<?php
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is already escaped.
			echo Forminator_Admin::get_red_notice( esc_html( $vars['error_message'] ) );
		?>
	<?php endif; ?>

</div>

<form>

	<div class="sui-form-field<?php echo esc_attr( ! empty( $vars['ad_tracking_error'] ) ? ' sui-form-field-error' : '' ); ?>">

		<label class="sui-label"><?php esc_html_e( 'Ad Tracking', 'forminator' ); ?></label>

		<div class="sui-insert-variables">

			<input name="ad_tracking"
				placeholder="<?php esc_attr_e( 'Ad Tracking', 'forminator' ); ?>"
				value="<?php echo esc_attr( $vars['ad_tracking'] ); ?>"
				id="ad_tracking"
				class="sui-form-control" />

			<select id="ad-tracking-variables" class="sui-variables" data-textarea-id="ad_tracking"
				<?php echo ( count( $vars['fields'] ) > 5 ) ? 'data-search="true"' : ''; ?>
			>
				<?php foreach ( $vars['fields'] as $field ) : ?>
					<option value="{<?php echo esc_attr( $field['element_id'] ); ?>}"
						data-content="{<?php echo esc_attr( $field['element_id'] ); ?>}">
						<?php echo esc_html( wp_strip_all_tags( $field['field_label'] ) ); ?>
					</option>
				<?php endforeach; ?>

			</select>

		</div>

		<?php if ( ! empty( $vars['ad_tracking_error'] ) ) : ?>
			<span class="sui-error-message"><?php echo esc_html( $vars['ad_tracking_error'] ); ?></span>
		<?php endif; ?>

		<span class="sui-description">
			<?php
			printf(
				/* Translators: 1. Opening <a> tag with link to the article link, 2. closing <a> tag. */
				esc_html__( 'Use 20 or fewer characters to label subscribers based on how they signed up. Find complete article %1$shere%2$s.', 'forminator' ),
				'<a href="https://help.aweber.com/hc/en-us/articles/204028836-What-is-Ad-Tracking-" target="_blank">',
				'</a>'
			);
			?>
		</span>

	</div>

	<div class="sui-form-field <?php echo esc_attr( ! empty( $vars['tags_error'] ) ? 'sui-form-field-error' : '' ); ?>" style="margin-bottom: 0;">

		<label class="sui-label" for="tags"><?php esc_html_e( 'Tags', 'forminator' ); ?></label>

		<select
				name="tags[]"
				id="tags"
				class="sui-select"
				multiple="multiple"
				data-reorder="1"
				data-tags="true"
				data-token-separators="[',']"
				data-placeholder=""
				data-allow-clear="false"
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
			<?php
			printf(
			/* Translators: 1. Opening <a> tag with link to the campaign tags, 2. closing <a> tag. */
				esc_html__( 'Available fields value or free text can be used as tags. Have a campaign that’s triggered when a subscriber is tagged? Enter the appropriate tag to add the subscriber to the campaign, and they will immediately begin receiving your messages. More info about AWeber subscriber tags can be found %1$shere%2$s.', 'forminator' ),
				'<a href="https://help.aweber.com/hc/en-us/articles/212677877-How-do-I-use-Tags-with-Campaigns-" target="_blank">',
				'</a>'
			);
			?>
		</span>

	</div>

	<input type="hidden" name="multi_id" value="<?php echo esc_attr( $vars['multi_id'] ); ?>">

</form>
