<?php
/**
 * Template for existing worksheet.
 *
 * @package    Forminator
 */

$vars = array(
	'error_message' => '',
	'file_id_error' => '',
	'file_id'       => '',
);

// @var array $template_vars Variables.
foreach ( $template_vars as $key => $val ) {
	$vars[ $key ] = $val;
} ?>

<div class="forminator-integration-popup__header">

	<h3 id="forminator-integration-popup__title" class="sui-box-title sui-lg" style="overflow: initial; white-space: normal; text-overflow: initial;">
		<?php esc_html_e( 'Choose Spreadsheet', 'forminator' ); ?>
	</h3>

	<p id="forminator-integration-popup__description" class="sui-description">
		<?php esc_html_e( 'Choose the spreadsheet you want to send submissions to.', 'forminator' ); ?>
	</p>

	<div class="sui-side-tabs ">
		<div class="sui-tabs-menu">
			<div class="sui-tab-item forminator-google-spreadsheet-option"><?php esc_html_e( 'New spreadsheet', 'forminator' ); ?></div>
			<div class="sui-tab-item active"><?php esc_html_e( 'Existing spreadsheet', 'forminator' ); ?></div>
		</div>
		<div class="sui-tabs-content">
			<div class="sui-tab-content sui-tab-boxed sui-tab-content active">

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
					<div class="sui-form-field <?php echo esc_attr( ! empty( $vars['file_id_error'] ) ? 'sui-form-field-error' : '' ); ?>">
						<label class="sui-label"><?php esc_html_e( 'Spreadsheet ID', 'forminator' ); ?></label>
						<input
								class="sui-form-control"
								name="file_id" placeholder="<?php esc_html_e( 'Enter spreadsheet ID', 'forminator' ); ?>"
								value="<?php echo esc_attr( $vars['file_id'] ); ?>">
						<?php if ( ! empty( $vars['file_id_error'] ) ) : ?>
							<span class="sui-error-message"><?php echo esc_html( $vars['file_id_error'] ); ?></span>
						<?php endif; ?>
						<span class="sui-description" style="text-align:left">
							<ol class="instructions" id="directory-instructions" style="display: block;">
								<li>
									<?php
									printf(
										/* Translators: 1. Opening <a> tag with Google Sheets link, 2. closing <a> tag. */
										esc_html__( 'Go to your %1$s.', 'forminator' ),
										'<a href="https://docs.google.com/spreadsheets" target="_blank">' . esc_html__( 'Google Sheets', 'forminator' ) . '</a>'
									);
									?>
									<?php esc_html_e( 'and open the spreadsheet you want to send the submissions to.', 'forminator' ); ?>
								</li>
								<li>
								<?php
								printf(
								/* Translators: 1. <em> tag with spreadsheet ID link, 2. <em> tag with path before spreadsheet ID, 3. <em> tag with path after spreadsheet ID 4. <strong> tag with spreadsheet ID. */
									esc_html__( 'The URL for the spreadsheet would be similar to %1$s. The spreadsheet ID is between the %2$s and %3$s, which in this case would be %4$s.', 'forminator' ),
									'<em>https://docs.google.com/spreadsheets/d/127I8jsYk2YsfaPqg5E3vgjhsGWW0AAmOelv1c/edit#gid=0</em>',
									'<em>/spreadsheets/d/</em>',
									'<em>/edit</em>',
									'<strong>127I8jsYk2YsfaPqg5E3vgjhsGWW0AAmOelv1c</strong>'
								);
								?>
								</li>
							</ol>
						</span>
					</div>

					<input type="hidden" name="multi_id" value="<?php echo esc_attr( $vars['multi_id'] ); ?>">
					<input type="hidden" name="sheet_type" value="existing"/>

				</form>
			</div>
		</div>
	</div>
</div>
