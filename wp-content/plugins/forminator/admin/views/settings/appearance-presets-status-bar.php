<?php
/**
 * Template admin/views/settings/appearance-presets-status-bar.php
 *
 * @package Forminator
 */

$mobile = 'forminator-for-mobile' === $args['class'];
$label  = esc_html__( 'Preset', 'forminator' );
?>

<div class="sui-box-status <?php echo esc_attr( $args['class'] ); ?>">
	<div class="sui-status">

	<?php if ( $mobile ) { ?>

		<div class="sui-form-field">
			<label class="sui-label">
				<?php echo esc_html( $label ); ?>
			</label>

	<?php } else { ?>

		<div class="sui-status-module">
			<?php echo esc_html( $label ); ?>
		</div>
		<div class="fui-bar-selectors">

	<?php } ?>

			<select name="appearance_preset" class="sui-select sui-select-sm">
				<?php foreach ( $args['presets'] as $key => $preset_name ) { ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $key === $args['preset'] ); ?>><?php echo esc_html( $preset_name ); ?></option>
				<?php } ?>
			</select>

		</div>

<?php if ( $mobile ) { ?>

	</div>
	<div class="sui-actions">

<?php } ?>

		<button
			class="sui-button sui-button-ghost sui-button-blue wpmudev-button-open-modal forminator-new-appearance-preset"
			data-modal="create_preset"
			data-nonce="<?php echo esc_attr( wp_create_nonce( 'forminator_create_preset' ) ); ?>"
			data-modal-title="<?php esc_attr_e( 'Create Preset', 'forminator' ); ?>"
			data-modal-content="<?php esc_attr_e( 'Enter a name for your new preset below.', 'forminator' ); ?>"
			data-modal-preset-form-label="<?php esc_attr_e( 'Import style from form (Optional)', 'forminator' ); ?>"
			data-modal-preset-loading-text="<?php esc_attr_e( 'Creating...', 'forminator' ); ?>"
			data-modal-preset-name-label="<?php esc_attr_e( 'Preset Name', 'forminator' ); ?>"
			data-modal-preset-name-placeholder="<?php esc_attr_e( 'E.g., New preset', 'forminator' ); ?>"
		>
				<span class="sui-icon-plus" aria-hidden="true"></span><?php esc_html_e( 'New Preset', 'forminator' ); ?>
		</button>

	<?php if ( $mobile ) { ?>

		<div class="sui-right-actions">

	<?php } else { ?>

		</div>
		<div class="sui-actions">

	<?php } ?>

		<?php if ( $args['preset'] && 'default' !== $args['preset'] ) { ?>
			<button
				data-modal="delete_preset"
				data-modal-title="<?php esc_attr_e( 'Delete Preset', 'forminator' ); ?>"
				data-modal-content="<?php esc_attr_e( 'Are you sure you want to delete this preset?', 'forminator' ); ?>"
				class="sui-button-icon sui-button-red sui-tooltip sui-tooltip-left wpmudev-button-open-modal"
				data-tooltip="<?php esc_attr_e( 'Delete Preset', 'forminator' ); ?>"
			>
				<i class="sui-icon-trash" aria-hidden="true"></i>
			</button>
		<?php } ?>

		<button class="sui-button sui-button-blue forminator-update-appearance-preset">
			<span class="sui-loading-text">
				<span class="sui-icon-save" aria-hidden="true"></span>
				<span class="button-text"><?php esc_html_e( 'Update', 'forminator' ); ?></span>
			</span>
			<span class="sui-icon-loader sui-loading" aria-hidden="true"></span>
		</button>

	<?php if ( $mobile ) { ?>
		</div>
	<?php } ?>

	</div>
</div>
