<?php
/**
 * Template admin/views/common/reports/content.php
 *
 * @package Forminator
 */

$section = Forminator_Core::sanitize_text_field( 'section', 'dashboard' );
?>
<div id="forminator-reports" class="sui-row-with-sidenav">
	<div class="sui-sidenav">
		<ul class="sui-vertical-tabs sui-sidenav-hide-md">
			<li class="sui-vertical-tab <?php echo esc_attr( 'dashboard' === $section ? 'current' : '' ); ?>">
				<a href="#" data-nav="dashboard"><?php esc_html_e( 'Dashboard', 'forminator' ); ?></a>
			</li>
			<li class="sui-vertical-tab <?php echo esc_attr( 'notification' === $section ? 'current' : '' ); ?>">
				<a href="#" data-nav="notification">
					<?php esc_html_e( 'Notifications', 'forminator' ); ?>
				</a>
			</li>
		</ul>
		<div class="sui-sidenav-settings">
			<div class="sui-form-field sui-sidenav-hide-lg">
				<label class="sui-label"><?php esc_html_e( 'Navigate', 'forminator' ); ?></label>
				<select id="forminator-sidenav" class="sui-select sui-mobile-nav">
					<option value="dashboard"><?php esc_html_e( 'Dashboard', 'forminator' ); ?></option>
					<option value="notification"><?php esc_html_e( 'Notifications', 'forminator' ); ?></option>
				</select>
			</div>
		</div>
	</div>
	<?php $this->template( 'common/reports/tab-dashboard' ); ?>
	<?php $this->template( 'common/reports/tab-notification' ); ?>
</div>