<?php
/**
 * Template for setup worksheet.
 *
 * @package Forminator
 */

$vars = array(
	'error_message'   => '',
	'folder_id'       => '',
	'folder_id_error' => '',
	'file_name'       => '',
	'file_name_error' => '',
	'file_id'         => '',
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
		<?php esc_html_e( 'Create Spreadsheet', 'forminator' ); ?>
	</h3>

	<p id="forminator-integration-popup__description" class="sui-description">
		<?php esc_html_e( 'Create Spreadsheet that will be used to send submissions.', 'forminator' ); ?>
	</p>

	<div class="sui-side-tabs ">
		<div class="sui-tabs-menu">
			<div class="sui-tab-item active"><?php esc_html_e( 'New spreadsheet', 'forminator' ); ?></div>
			<div class="sui-tab-item forminator-google-spreadsheet-option"><?php esc_html_e( 'Existing spreadsheet', 'forminator' ); ?></div>
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
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is already escaped.
					echo Forminator_Admin::get_red_notice( esc_html( $vars['error_message'] ) );
				?>
			<?php endif; ?>

				<form>
					<div class="sui-form-field <?php echo esc_attr( ! empty( $vars['folder_id_error'] ) ? 'sui-form-field-error' : '' ); ?>">
						<label class="sui-label"><?php esc_html_e( 'Drive Folder ID', 'forminator' ); ?></label>
						<input
								class="sui-form-control"
								name="folder_id" placeholder="<?php esc_html_e( 'Folder ID', 'forminator' ); ?>"
								value="<?php echo esc_attr( $vars['folder_id'] ); ?>">
						<?php if ( ! empty( $vars['folder_id_error'] ) ) : ?>
							<span class="sui-error-message"><?php echo esc_html( $vars['folder_id_error'] ); ?></span>
						<?php endif; ?>
						<span class="sui-description" style="text-align:left">
							<ol class="instructions" id="directory-instructions" style="display: block;">
								<li>
									<?php esc_html_e( 'It is optional, if Drive Folder ID omitted / empty, new spreadsheet will be created in your Google Drive home / root folder.', 'forminator' ); ?>
								</li>
								<li>
									<?php
									printf(
										/* Translators: 1. Opening <a> tag with Google Drive link, 2. closing <a> tag. */
										esc_html__( 'Go to your %1$s.', 'forminator' ),
										'<a href="https://drive.google.com/#my-drive" target="_blank">' . esc_html__( 'Drive account', 'forminator' ) . '</a>'
									);
									?>
									<?php esc_html_e( 'Navigate to or create a new directory where you want to create a new spreadsheet. Make sure you are viewing the destination directory.', 'forminator' ); ?>
								</li>
								<li>
								<?php
								printf(
								/* Translators: 1. <em> tag with Directory ID link, 2. <em> tag with folders 3. <strong> tag with Directory ID. */
									esc_html__( 'The URL for the directory will be something similar to %1$s. The Directory ID would be the last part after %2$s, which is %3$s in this case.', 'forminator' ),
									'<em>https://drive.google.com/#folders/0B6GD66ctHXdCOWZKNDRIRGJJXS3</em>',
									'<em>/#folders/</em>',
									'<strong>0B6GD66ctHXdCOWZKNDRIRGJJXS3</strong>'
								);
								?>
								</li>
							</ol>
						</span>
					</div>

					<div class="sui-form-field <?php echo esc_attr( ! empty( $vars['file_name_error'] ) ? 'sui-form-field-error' : '' ); ?>" style="margin-bottom: 0;">
						<label class="sui-label"><?php esc_html_e( 'Spreadsheet File Name', 'forminator' ); ?></label>
						<input
								class="sui-form-control"
								name="file_name" placeholder="<?php esc_attr_e( 'File Name', 'forminator' ); ?>"
								value="<?php echo esc_attr( $vars['file_name'] ); ?>">
						<?php if ( ! empty( $vars['file_name_error'] ) ) : ?>
							<span class="sui-error-message"><?php echo esc_html( $vars['file_name_error'] ); ?></span>
						<?php endif; ?>
					</div>

					<input type="hidden" name="multi_id" value="<?php echo esc_attr( $vars['multi_id'] ); ?>">
					<input type="hidden" name="sheet_type" value="new"/>

				</form>
			</div>
		</div>
	</div>
</div>
