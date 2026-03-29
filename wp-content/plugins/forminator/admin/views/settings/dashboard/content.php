<?php
/**
 * Template admin/views/settings/dashboard/content.php
 *
 * @package Forminator
 */

$module_type        = $args['module_type'];
$dashboard_settings = forminator_get_dashboard_settings( $module_type, array() );
$num_recent         = isset( $dashboard_settings['num_recent'] ) ? $dashboard_settings['num_recent'] : 5;
$published          = isset( $dashboard_settings['published'] ) ? filter_var( $dashboard_settings['published'], FILTER_VALIDATE_BOOLEAN ) : true;
$draft              = isset( $dashboard_settings['draft'] ) ? filter_var( $dashboard_settings['draft'], FILTER_VALIDATE_BOOLEAN ) : true;
?>

<div class="sui-form-field">

	<label for="listings-<?php echo esc_attr( $module_type ); ?>-limit" id="listings-<?php echo esc_attr( $module_type ); ?>-limit-label" class="sui-settings-label"><?php echo esc_html( $args['title'] ); ?></label>

	<span id="listings-<?php echo esc_attr( $module_type ); ?>-limit-message" class="sui-description" style="margin-bottom: 10px;"><?php echo esc_html( $args['description'] ); ?></span>

	<input
		type="number"
		min="0"
		value="<?php echo esc_attr( $num_recent ); ?>"
		placeholder="0"
		id="listings-<?php echo esc_attr( $module_type ); ?>-limit"
		name="num_recent[<?php echo esc_attr( $module_type ); ?>]"
		class="sui-form-control sui-input-sm"
		style="max-width: 100px;"
		aria-labelledby="listings-<?php echo esc_attr( $module_type ); ?>-limit-label"
		aria-describedby="listings-<?php echo esc_attr( $module_type ); ?>-limit-message"
		aria-required="true"
	/>

	<span class="sui-error-message" style="display: none;"><?php esc_html_e( 'This field shouldn\'t be empty.', 'forminator' ); ?></span>

</div>

<div class="sui-form-field">

	<label id="listings-<?php echo esc_attr( $module_type ); ?>-status-label" class="sui-settings-label"><?php esc_html_e( 'Status', 'forminator' ); ?></label>

	<span id="listings-<?php echo esc_attr( $module_type ); ?>-status-message" class="sui-description" style="margin-bottom: 10px;"><?php echo esc_html( $args['status_descr'] ); ?></span>

	<label for="forminator-<?php echo esc_attr( $module_type ); ?>-status-published" class="sui-checkbox sui-checkbox-sm sui-checkbox-stacked">
		<input
			type="checkbox"
			name="published[<?php echo esc_attr( $module_type ); ?>]"
			value="true"
			id="forminator-<?php echo esc_attr( $module_type ); ?>-status-published"
			aria-labelledby="listings-<?php echo esc_attr( $module_type ); ?>-status-label listings-<?php echo esc_attr( $module_type ); ?>-status-published"
			aria-describedby="listings-<?php echo esc_attr( $module_type ); ?>-status-message"
			<?php echo checked( $published ); ?>
		/>
		<span aria-hidden="true"></span>
		<span id="listings-<?php echo esc_attr( $module_type ); ?>-status-published"><?php esc_html_e( 'Published', 'forminator' ); ?></span>
	</label>

	<label for="forminator-<?php echo esc_attr( $module_type ); ?>-status-drafts" class="sui-checkbox sui-checkbox-sm sui-checkbox-stacked">
		<input
			type="checkbox"
			name="draft[<?php echo esc_attr( $module_type ); ?>]"
			value="true"
			id="forminator-<?php echo esc_attr( $module_type ); ?>-status-drafts"
			aria-labelledby="listings-<?php echo esc_attr( $module_type ); ?>-status-label listings-<?php echo esc_attr( $module_type ); ?>-status-drafts"
			aria-describedby="listings-<?php echo esc_attr( $module_type ); ?>-status-message"
			<?php echo checked( $draft ); ?>
		/>
		<span aria-hidden="true"></span>
		<span id="listings-<?php echo esc_attr( $module_type ); ?>-status-drafts"><?php esc_html_e( 'Drafts', 'forminator' ); ?></span>
	</label>

</div>
