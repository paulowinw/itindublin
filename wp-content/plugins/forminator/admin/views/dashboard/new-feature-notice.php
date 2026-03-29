<?php
/**
 * Template admin/views/dashboard/new-feature-notice.php
 *
 * @package Forminator
 */

$user      = wp_get_current_user();
$banner_1x = forminator_plugin_url() . 'assets/images/Feature_highlight.png';
$banner_2x = forminator_plugin_url() . 'assets/images/Feature_highlight@2x.png';
$url       = add_query_arg(
	array(
		'page'        => 'forminator-templates',
		'feature'     => 'preset-template',
		'from'        => 'new-features-modal',
		'page_action' => 'hub_connection',
	),
	admin_url( 'admin.php' )
);

$hub_connected = Forminator_Hub_Connector::hub_connector_connected();
?>

<div class="sui-modal sui-modal-md">

	<div
		role="dialog"
		id="forminator-new-feature"
		class="sui-modal-content"
		aria-live="polite"
		aria-modal="true"
		aria-labelledby="forminator-new-feature__title"
	>

		<div class="sui-box forminator-feature-modal" data-prop="forminator_dismiss_feature_1490"
			data-nonce="<?php echo esc_attr( wp_create_nonce( 'forminator_dismiss_notification' ) ); ?>">

			<div class="sui-box-header sui-flatten sui-content-center">

				<figure class="sui-box-banner" aria-hidden="true">
					<img
						src="<?php echo esc_url( $banner_1x ); ?>"
						srcset="<?php echo esc_url( $banner_1x ); ?> 1x, <?php echo esc_url( $banner_2x ); ?> 2x"
						alt=""
					/>
				</figure>

				<button class="sui-button-icon sui-button-white sui-button-float--right forminator-dismiss-new-feature" data-type="dismiss" data-modal-close>
					<span class="sui-icon-close sui-md" aria-hidden="true"></span>
					<span class="sui-screen-reader-text"><?php esc_html_e( 'Close this dialog.', 'forminator' ); ?></span>
				</button>

				<h3 class="sui-box-title sui-lg" style="overflow: initial; white-space: initial; text-overflow: initial;">
				<?php
					esc_html_e( 'New! Form Editor & Instant Preview', 'forminator' );
				?>
				</h3>

				<p class="sui-description">
				<?php
				printf(
					/* translators: 1. Admin name */
					esc_html__( 'Hey %s! We\'re excited to introduce the first phase of our Form Editor improvements. You\'ll notice a refreshed design and a new instant preview in the Appearance section, allowing you to see your changes as you customize your form. Check it out and let us know what you think!', 'forminator' ),
					esc_html( ucfirst( $user->display_name ) ),
				);
				?>
				</p>

			</div>

			<div class="sui-box-footer sui-flatten sui-content-center">

				<button class="sui-button forminator-dismiss-new-feature" data-modal-close>
					<?php esc_html_e( 'Got it', 'forminator' ); ?>
				</button>

			</div>

			<?php
			if ( ! forminator_usage_tracking_disabled() && ! Forminator_Core::is_tracking_active() ) {
				$settings_url = add_query_arg(
					array(
						'page'    => 'forminator-settings',
						'section' => 'dashboard',
					),
					admin_url( 'admin.php' )
				);
				?>

			<div class="sui-accordion sui-accordion-flushed" style="margin: 10px 0 -30px;">
				<div class="sui-accordion-item">
					<div class="sui-accordion-item-header">
						<div class="sui-accordion-item-title">
							<label for="forminator-usage_tracking" class="sui-toggle">
								<input type="checkbox" id="forminator-usage_tracking">
								<span class="sui-toggle-slider"></span>
								<span class="sui-screen-reader-text"><?php esc_html_e( 'Allow usage tracking', 'forminator' ); ?></span>
								<span class="sui-toggle-label">
									<?php esc_html_e( 'Help us improve Forminator', 'forminator' ); ?>
									<span
										class="sui-tooltip sui-tooltip-constrained"
										style="--tooltip-width: 150px; margin-left: 10px;"
										data-tooltip="<?php esc_attr_e( 'We use usage data to improve Forminator’s performance. Opt in to help make Forminator better.', 'forminator' ); ?>"
									>
										<span class="sui-icon-info sui-sm" aria-hidden="true"></span>
									</span>
								</span>
							</label>
						</div>
						<div class="sui-accordion-col-auto">
							<button class="sui-button-icon sui-accordion-open-indicator">
								<i class="sui-icon-chevron-down" aria-hidden="true"></i>
							</button>
						</div>
					</div>
					<div class="sui-accordion-item-body">
						<div class="sui-box">
							<div class="sui-box-body">
								<p class="sui-description">
								<?php
									printf(
										/* translators: 1. Open 'a' tag. 2. Open 'a' tag. 3. Close 'a' tag. */
										esc_html__( 'You can help improve Forminator by allowing anonymous usage tracking—no personal data is collected. We use usage data to improve Forminator’s performance and you can Opt out anytime in the %1$ssettings page%3$s. Learn more about usage data %2$shere%3$s.', 'forminator' ),
										'<a href="' . esc_url( $settings_url ) . '" target="_blank">',
										'<a href="https://wpmudev.com/docs/privacy/our-plugins/#usage-tracking-for" target="_blank">',
										'</a>'
									);
								?>
								</p>
							</div>
						</div>
					</div>
				</div>
			</div>
			<?php } ?>

		</div>

	</div>

</div>

<script type="text/javascript">
	jQuery('#forminator-new-feature .forminator-dismiss-new-feature').on('click', function (e) {
	e.preventDefault()

	var $notice = jQuery(e.currentTarget).closest('.forminator-feature-modal'),
		ajaxUrl = '<?php echo esc_url( forminator_ajax_url() ); ?>',
		$self   = jQuery(this),
		ajaxData = {
		action: 'forminator_dismiss_notification',
		prop: $notice.data('prop'),
		_ajax_nonce: $notice.data('nonce')
		}

	jQuery.post(ajaxUrl, ajaxData)
		.always(function () {
			$notice.hide();
			let link = $self.data('link');
			if ( link ) {
				location.href = link;
			}
		})
	})

	jQuery('#forminator-usage_tracking').on('change', function (e) {
		var $self = jQuery(this),
			ajaxUrl = '<?php echo esc_url( forminator_ajax_url() ); ?>',
			ajaxData = {
				action: 'forminator_usage_tracking',
				enabled: $self.prop('checked'),
				_ajax_nonce: '<?php echo esc_attr( wp_create_nonce( 'forminator_usage_tracking' ) ); ?>'
			};

		jQuery.post(ajaxUrl, ajaxData)
			.done(function (response) {
				if (response.success) {
					Forminator.Notification.open( 'success', response.data, 4000 );
				} else {
					Forminator.Notification.open( 'error', response.data, 4000 );
				}
			});
	});

</script>
