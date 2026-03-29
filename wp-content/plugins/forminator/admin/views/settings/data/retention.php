<?php
/**
 * Template admin/views/settings/data/retention.php
 *
 * @package Forminator
 */

?>
<?php $option_slug = $args['option_slug']; ?>
<?php $forever = '' !== $args['forever']; ?>

<label for="forminator-field-<?php echo esc_attr( $option_slug ); ?>_number" class="sui-settings-label"><?php echo esc_html( $args['title'] ); ?></label>
<span class="sui-description"><?php echo esc_html( $args['description'] ); ?></span>

<div class="sui-side-tabs" style="margin-top: 10px;">

	<div class="sui-tabs-menu">

		<label for="<?php echo esc_attr( $option_slug ); ?>-true" class="sui-tab-item<?php echo( $forever ? ' active' : '' ); ?>">
			<input type="radio"
				name="<?php echo esc_attr( $option_slug ); ?>_forever"
				value="true"
				id="<?php echo esc_attr( $option_slug ); ?>-true"
				<?php checked( $forever ); ?> />
			<?php esc_html_e( 'Forever', 'forminator' ); ?>
		</label>

		<label for="<?php echo esc_attr( $option_slug ); ?>-false" class="sui-tab-item<?php echo( ! $forever ? ' active' : '' ); ?>">
			<input type="radio"
				name="<?php echo esc_attr( $option_slug ); ?>_forever"
				value="false"
				id="<?php echo esc_attr( $option_slug ); ?>-false"
				data-tab-menu="<?php echo esc_attr( $option_slug ); ?>"
				<?php checked( ! $forever ); ?> />
			<?php esc_html_e( 'Custom', 'forminator' ); ?>
		</label>

	</div>

	<div class="sui-tabs-content">

		<div data-tab-content="<?php echo esc_attr( $option_slug ); ?>" class="sui-tab-content sui-tab-boxed<?php echo( ! $forever ? ' active' : '' ); ?>">
			<div class="sui-row">
				<div class="sui-col-md-6">
					<div class="sui-form-field">

						<input type="number"
							id="forminator-field-<?php echo esc_attr( $option_slug ); ?>_number"
							name="<?php echo esc_attr( $option_slug ); ?>_number"
							placeholder="<?php esc_html_e( 'E.g. 10', 'forminator' ); ?>"
							value="<?php echo esc_attr( $args['number'] ); ?>"
							min="0"
							class="sui-form-control sui-form-control-inline"/>
					</div>
				</div>
				<div class="sui-col-md-6">
					<div class="sui-form-field">

						<select id="<?php echo esc_attr( $option_slug ); ?>_unit" name="<?php echo esc_attr( $option_slug ); ?>_unit" class="sui-select">
							<option value="days" <?php selected( $args['unit'], 'days' ); ?>>
								<?php esc_html_e( 'day(s)', 'forminator' ); ?></option>
							<option value="weeks" <?php selected( $args['unit'], 'weeks' ); ?>>
								<?php esc_html_e( 'week(s)', 'forminator' ); ?></option>
							<option value="months" <?php selected( $args['unit'], 'months' ); ?>>
								<?php esc_html_e( 'month(s)', 'forminator' ); ?></option>
							<option value="years" <?php selected( $args['unit'], 'years' ); ?>>
								<?php esc_html_e( 'years(s)', 'forminator' ); ?></option>
						</select>

					</div>
				</div>
			</div>
			<span class="sui-description">
				<?php esc_html_e( 'Set the value to "0" or leave it blank to store data forever.', 'forminator' ); ?>
			</span>

		</div>

	</div>

</div>
