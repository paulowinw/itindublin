<?php
/**
 * Template admin/views/custom-form/entries/content-details.php
 *
 * @package Forminator
 */

/**
 * Content details of Submissions
 *
 * @param array   $detail_item Item details.
 * @param boolean $inside_group Is this content inside Group field or not.
 */
function forminator_submissions_content_details( $detail_item, $inside_group = false ) {
	$sub_entries  = $detail_item['sub_entries'];
	$inside_group = isset( $inside_group ) ? $inside_group : false;
	?>

	<div class="sui-box-settings-slim-row sui-sm">

		<?php
		if ( isset( $detail_item['type'] ) && in_array( $detail_item['type'], array( 'stripe', 'paypal', 'group', 'stripe-ocs' ), true ) ) {

			if ( ! empty( $sub_entries ) ) {
				?>

					<div class=<?php echo 'group' === $detail_item['type'] ? 'sui-box-settings' : 'sui-box-settings-col-2'; ?>>

					<span class="sui-settings-label sui-dark sui-sm">
					<?php
						// PHPCS:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						echo Forminator_Field::convert_markdown( esc_html( $detail_item['label'] ) );
					?>
					</span>

					<table id="fui-table-<?php echo esc_attr( $detail_item['type'] ); ?>" class="sui-table sui-accordion <?php echo 'group' === $detail_item['type'] ? 'fui-table-entries' : 'fui-table-details'; ?>">

						<thead>

							<tr>
								<?php
								$max_fields  = 'group' === $detail_item['type'] ? 4 : 5;
								$is_multiple = count( $sub_entries ) > $max_fields;
								$end         = count( $sub_entries );
								$sub_entries = forminator_submissions_remove_quantity( $sub_entries, $detail_item['type'] );

								foreach ( $sub_entries as $sub_key => $sub_entry ) {

									++$sub_key;

									if ( $max_fields < $sub_key ) {

										continue;
									}

									if ( $max_fields === $sub_key && $max_fields < count( $sub_entries ) ) {

										echo '<th aria-label="' . esc_attr__( 'Other fields', 'forminator' ) . '"></th>';

									} else {

										echo '<th>' .
											// PHPCS:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
											Forminator_Field::convert_markdown( esc_html( $sub_entry['label'] ) ) .
										'</th>';

									}
								}
								?>

							</tr>

						</thead>

						<tbody>

						<?php do { ?>

							<?php $key = ! empty( $detail_item['repeated_group_keys'] ) ? array_shift( $detail_item['repeated_group_keys'] ) : ''; ?>
							<?php $sub_entries = ! empty( $detail_item[ 'sub_entries' . $key ] ) ? $detail_item[ 'sub_entries' . $key ] : array(); ?>

							<tr class="sui-accordion-item">

								<?php
								$end = count( $sub_entries );
								foreach ( $sub_entries as $sub_key => $sub_entry ) {

									++$sub_key;

									if ( $max_fields < $sub_key ) {

										continue;
									}

									if ( $max_fields === $sub_key && $max_fields < count( $sub_entries ) ) {
										$sub_count = count( $sub_entries ) - $max_fields + 1;
										echo '<td style="padding-top: 5px; padding-bottom: 5px;">';
										echo '<span class="fui-accordion-open-text">' . sprintf(
												/* translators: %s: field sub count */
											esc_html__( '+ %s other fields', 'forminator' ),
											esc_html( $sub_count )
										) . '</span>';
										echo '<span class="sui-accordion-open-indicator">';
										echo '<i class="sui-icon-chevron-down"></i>';
										echo '</span>';
										echo '</td>';
									} elseif ( ! empty( $sub_entry['sub_entries'] ) ) {
											echo '<td style="padding-top: 5px; padding-bottom: 5px;">';
											forminator_submissions_content_details( $sub_entry, true );
											echo '</td>';
									} else {
										echo '<td style="padding-top: 5px; padding-bottom: 5px;">';
										if ( strpos( $sub_entry['key'], 'textarea' ) !== false ) {
											$sub_entry['value'] = forminator_replace_linebreaks( $sub_entry['value'] );
										}
										echo wp_kses_post( $sub_entry['value'] );
										if ( 1 !== $sub_key && 2 < $end && 'group' === $detail_item['type'] ) {
											echo '<span class="sui-accordion-open-indicator fui-mobile-only" aria-hidden="true"><i class="sui-icon-chevron-down"></i></span>';
										}
										echo '</td>';
									}
								}
								?>

							</tr>

							<?php if ( 2 < $end ) { ?>

							<tr class="sui-accordion-item-content<?php echo ! $is_multiple ? ' sui-accordion-item--mobile' : ''; ?>">

								<td colspan="<?php echo intval( $max_fields ); ?>">

									<div class="sui-box">

										<div class="sui-box-body">

												<?php
												$sub_entries = forminator_submissions_remove_quantity( $sub_entries, $detail_item['type'] );
												foreach ( $sub_entries as $sub_key => $sub_entry ) {
													$class_names = 'sui-box-settings-col-2';
													if ( ! empty( $sub_entry['sub_entries'] ) ) {
														$class_names .= ' sui-border-frame';
													}
													?>
													<div class="sui-box-settings-slim-row sui-sm">
														<div class="sui-box-settings-col-1">
															<span class="sui-settings-label sui-sm">
																<?php
																// PHPCS:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
																echo Forminator_Field::convert_markdown( esc_html( $sub_entry['label'] ) );
																?>
															</span>
														</div>
														<div class="<?php echo esc_attr( $class_names ); ?>">
															<?php
															if ( empty( $sub_entry['sub_entries'] ) ) {
																?>
																<span class="sui-description">
																	<?php echo wp_kses_post( $sub_entry['value'] ); ?>
																</span>
																<?php
															} else {
																forminator_submissions_content_details( $sub_entry, true );
															}
															?>
														</div>
													</div>
													<?php
												}
												?>

										</div>

									</div>

								</td>

							</tr>
							<?php } ?>

						<?php } while ( 'group' === $detail_item['type'] && ! empty( $detail_item['repeated_group_keys'] ) ); ?>

						</tbody>

					</table>

				</div>

				<?php
			}
		} else {
			?>

			<?php if ( ! $inside_group ) { ?>
			<div class="sui-box-settings-col-1">
				<span class="sui-settings-label sui-sm">
					<?php
					// PHPCS:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo Forminator_Field::convert_markdown( esc_html( $detail_item['label'] ) )
					?>
				</span>
			</div>
			<?php } ?>

			<div class="sui-box-settings-col-2<?php echo ( empty( $sub_entries ) || $inside_group ) ? '' : ' sui-border-frame'; ?>">

				<?php if ( empty( $sub_entries ) ) { ?>

					<?php if ( 'textarea' === $detail_item['type'] && ( isset( $detail_item['rich'] ) && 'true' === $detail_item['rich'] ) ) { ?>

						<div class="fui-rich-textarea"><?php echo wp_kses_post( forminator_replace_linebreaks( $detail_item['value'] ) ); ?></div>

						<?php
					} elseif ( 'number' === $detail_item['type'] || 'currency' === $detail_item['type'] || ( 'calculation' === $detail_item['type'] && is_numeric( $detail_item['value'] ) ) ) {
						$separator = isset( $detail_item['separator'] ) ? $detail_item['separator'] : '';
						$point     = isset( $detail_item['point'] ) ? $detail_item['point'] : '';
						$precision = isset( $detail_item['precision'] ) ? $detail_item['precision'] : 2;
						?>

						<span class="sui-description" data-inputmask="'alias': 'decimal','rightAlign': false, 'digitsOptional': false, 'groupSeparator': '<?php echo esc_attr( $separator ); ?>', 'radixPoint': '<?php echo esc_attr( $point ); ?>', 'digits': '<?php echo esc_attr( $precision ); ?>'"><?php echo wp_kses_post( $detail_item['value'] ); ?></span>

					<?php } elseif ( 'textarea' === $detail_item['type'] ) { ?>

						<span class="sui-description"><?php echo wp_kses_post( forminator_replace_linebreaks( $detail_item['value'] ) ); ?></span>

					<?php } else { ?>

						<span class="sui-description"><?php echo wp_kses_post( $detail_item['value'] ); ?></span>

					<?php } ?>

				<?php } else { ?>

					<?php
					foreach ( $sub_entries as $sub_entry ) {
						?>

						<div class="sui-form-field">
							<div class="sui-row">
								<div class="sui-col-md-3">
									<span class="sui-settings-label">
									<?php
										// PHPCS:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
										echo Forminator_Field::convert_markdown( esc_html( $sub_entry['label'] ) );
									?>
									</span>
								</div>
								<div class="sui-col-md-9">
									<span class="sui-description"><?php echo wp_kses_post( $sub_entry['value'] ); ?></span>
								</div>
							</div>
						</div>

					<?php } ?>

				<?php } ?>

			</div>

		<?php } ?>

	</div>

	<?php
}

/**
 * Remove quantity for Stripe One-Time payments
 *
 * @param array  $sub_entries - The sub entries.
 * @param string $item_type - The field type.
 *
 * @return array
 */
function forminator_submissions_remove_quantity( $sub_entries, $item_type ) {
	if ( 'stripe' === $item_type || 'stripe-ocs' === $item_type ) {
		$payment_type_index = array_search( 'payment_type', array_column( $sub_entries, 'key' ), true );
		$quantity_index     = array_search( 'quantity', array_column( $sub_entries, 'key' ), true );
		$subscription_index = array_search( 'subscription_id', array_column( $sub_entries, 'key' ), true );
		$payment_type       = $sub_entries[ $payment_type_index ]['value'];

		if ( strtolower( esc_html__( 'One Time', 'forminator' ) ) === strtolower( $payment_type ) ) {
			unset( $sub_entries[ $quantity_index ] );
			unset( $sub_entries[ $subscription_index ] );
		}
	}

	return $sub_entries;
}
