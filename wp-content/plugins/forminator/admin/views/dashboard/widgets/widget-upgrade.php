<?php
/**
 * Template admin/views/dashboard/widgets/widget-upgrade.php
 *
 * @package Forminator
 */

?>
<div class="sui-box">

	<div class="sui-box-header">

		<h3 class="sui-box-title">
			<span class="sui-icon-forminator" aria-hidden="true"></span>
			<?php esc_html_e( 'Forminator Pro', 'forminator' ); ?>
			<span class="sui-tag sui-tag-pro">PRO</span>
		</h3>

	</div>

	<div class="sui-box-body">

		<p><?php esc_html_e( 'Get Forminator Pro, our full lineup of WordPress marketing tools and more for free when you start your WPMU DEV membership.', 'forminator' ); ?></p>

		<ol class="sui-upsell-list">
			<li><span class="sui-icon-check sui-md" aria-hidden="true"></span> <?php esc_html_e( 'Accept subscription and recurring payments', 'forminator' ); ?></li>
			<li><span class="sui-icon-check sui-md" aria-hidden="true"></span> <?php esc_html_e( 'Unlock Advanced form features with Pro Add-ons', 'forminator' ); ?></li>
			<li><span class="sui-icon-check sui-md" aria-hidden="true"></span> <?php esc_html_e( 'Access pre-made form templates and save custom form templates in the cloud', 'forminator' ); ?></li>
			<li><span class="sui-icon-check sui-md" aria-hidden="true"></span> <?php esc_html_e( 'Generate, download, and share PDFs on form submissions', 'forminator' ); ?></li>
			<li><span class="sui-icon-check sui-md" aria-hidden="true"></span> <?php esc_html_e( 'Add auto-complete to Address fields via Google Maps API', 'forminator' ); ?></li>
			<li><span class="sui-icon-check sui-md" aria-hidden="true"></span> <?php esc_html_e( 'Premium form fields and E-Signature integration', 'forminator' ); ?></li>
			<li><span class="sui-icon-check sui-md" aria-hidden="true"></span> <?php esc_html_e( 'Early access to the newest form features', 'forminator' ); ?></li>
			<li><span class="sui-icon-check sui-md" aria-hidden="true"></span> <?php esc_html_e( 'Smush and Hummingbird Pro performance pack', 'forminator' ); ?></li>
			<li><span class="sui-icon-check sui-md" aria-hidden="true"></span> <?php esc_html_e( 'Complete marketing suite with Hustle Pro – pop-ups, email, and more', 'forminator' ); ?></li>
			<li><span class="sui-icon-check sui-md" aria-hidden="true"></span> <?php esc_html_e( 'Customizable Google analytic dashboards with Beehive Pro', 'forminator' ); ?></li>
			<li><span class="sui-icon-check sui-md" aria-hidden="true"></span> <?php esc_html_e( 'Manage unlimited WordPress sites from The Hub', 'forminator' ); ?></li>
			<li><span class="sui-icon-check sui-md" aria-hidden="true"></span> <?php esc_html_e( '24/7 live WordPress support', 'forminator' ); ?></li>
			<li><span class="sui-icon-check sui-md" aria-hidden="true"></span> <?php esc_html_e( 'The WPMU DEV Guarantee', 'forminator' ); ?></li>
		</ol>

	</div>

	<div class="sui-box-footer" style="padding-top: 0; border-top: 0;">

		<a
			href="<?php echo esc_url( forminator_get_link( 'plugin', 'forminator_dashboard_upsellwidget_button' ) ); ?>"
			class="sui-button sui-button-purple"
			target="_blank"
		>
			<?php esc_html_e( 'Upgrade to Pro', 'forminator' ); ?>
		</a>

	</div>

</div>
