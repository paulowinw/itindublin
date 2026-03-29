<?php
/**
 * Template admin/views/settings/content.php
 *
 * @package Forminator
 */

$section = Forminator_Core::sanitize_text_field( 'section', 'dashboard' );

/**
 * Forminator Settings Sections filter
 *
 * @param array $sections Settings Sections
 */
$sections = apply_filters(
	'forminator_settings_sections',
	array(
		'dashboard'          => __( 'General', 'forminator' ),
		'accessibility'      => __( 'Accessibility', 'forminator' ),
		'appearance-presets' => __( 'Appearance Presets', 'forminator' ),
		'data'               => __( 'Data', 'forminator' ),
		'captcha'            => __( 'CAPTCHA', 'forminator' ),
		'import'             => __( 'Import', 'forminator' ),
		'submissions'        => __( 'Submissions', 'forminator' ),
	)
);

// Show payment settings only if payments are enabled.
if ( ! forminator_payments_disabled() ) {
	$sections['payments'] = __( 'Payments', 'forminator' );
}

// Show Permission settings for admins only.
if ( current_user_can( forminator_get_admin_cap() ) ) {
	$sections['permissions'] = __( 'Permissions', 'forminator' );
}

?>
<div class="sui-row-with-sidenav">

	<div class="sui-sidenav">

		<ul class="sui-vertical-tabs sui-sidenav-hide-md">
			<?php
			foreach ( $sections as $section_key => $section_title ) {
				?>
				<li class="sui-vertical-tab <?php echo $section_key === $section ? 'current' : ''; ?>">
					<a href="#" data-nav="<?php echo esc_attr( $section_key ); ?>"><?php echo esc_html( $section_title ); ?></a>
				</li>
				<?php
			}
			?>
		</ul>

		<div class="sui-sidenav-settings">

			<div class="sui-form-field sui-sidenav-hide-lg">

				<label class="sui-label"><?php esc_html_e( 'Navigate', 'forminator' ); ?></label>

				<select id="forminator-sidenav" class="sui-select sui-mobile-nav">
					<?php
					foreach ( $sections as $section_key => $section_title ) {
						?>
							<option value="<?php echo esc_attr( $section_key ); ?>"><?php echo esc_html( $section_title ); ?></option>
						<?php
					}
					?>
				</select>

			</div>

		</div>

	</div>

	<?php $this->template( 'settings/tab-general' ); ?>
	<?php $this->template( 'settings/tab-recaptcha' ); ?>
	<?php $this->template( 'settings/tab-appearance-presets' ); ?>
	<?php $this->template( 'settings/tab-data' ); ?>
	<?php $this->template( 'settings/tab-submissions' ); ?>
	<?php $this->template( 'settings/tab-accessibility' ); ?>
	<?php $this->template( 'settings/tab-import' ); ?>
	<?php
	// Show payment settings only if payments are enabled.
	if ( ! forminator_payments_disabled() ) {
		$this->template( 'settings/tab-payments' );
	}
	// Show only for admins.
	if ( current_user_can( forminator_get_admin_cap() ) ) {
		$this->template( 'settings/tab-permissions' );
	}
	?>

	<?php
		/**
		 * Forminator Settings Content action
		 */
		do_action( 'forminator_settings_content' );
	?>

</div>
