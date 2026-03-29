<?php
/**
 * Template admin/views/integrations/content.php
 *
 * @package Forminator
 */

$url = forminator_plugin_url(); ?>

<div class="sui-row-with-sidenav forminator-integrations-wrapper">

	<div class="sui-sidenav">

		<ul class="sui-vertical-tabs sui-sidenav-hide-md">

			<li class="sui-vertical-tab forminator-integrations" data-tab-id="forminator-integrations">
				<a href="#forminator-integrations" role="button"><?php esc_html_e( 'Applications', 'forminator' ); ?></a>
			</li>
			<?php if ( forminator_is_show_documentation_link() ) { ?>
				<li class="sui-vertical-tab forminator-api" data-tab-id="forminator-api">
					<a href="#forminator-api" role="button"><?php esc_html_e( 'API', 'forminator' ); ?></a>
				</li>
			<?php } ?>
		</ul>

		<select class="sui-mobile-nav sui-sidenav-hide-lg integration-nav">
			<option value="forminator-integrations"><?php esc_html_e( 'Applications', 'forminator' ); ?></option>
			<?php if ( forminator_is_show_documentation_link() ) { ?>
				<option value="forminator-api"><?php esc_html_e( 'API', 'forminator' ); ?></option>
			<?php } ?>
		</select>

	</div>

	<div id="forminator-integrations" class="wpmudev-settings--box" style="display: block;">

		<div class="sui-box">

			<div class="sui-box-header">

				<h2 class="sui-box-title"><?php esc_html_e( 'Applications', 'forminator' ); ?></h2>

			</div>

			<div id="forminator-integrations-page" class="sui-box-body">

				<p><?php esc_html_e( 'Forminator integrates with your favorite third party apps. You can connect to the available apps via their API here and activate them to collect data in the Integrations tab of your forms, polls or quizzes.', 'forminator' ); ?></p>

				<div id="forminator-integrations-display"></div>

			</div>

		</div>

	</div>
	<?php if ( forminator_is_show_documentation_link() ) { ?>
		<div id="forminator-api" class="wpmudev-settings--box" style="display: none;">

			<div class="sui-box">

				<div class="sui-box-header">

					<h2 class="sui-box-title"><?php esc_html_e( 'API', 'forminator' ); ?></h2>

				</div>

				<div class="sui-box sui-message">

					<?php if ( forminator_is_show_branding() ) : ?>
						<img src="<?php echo esc_url( $url ) . 'assets/images/forminator-disabled.png'; ?>"
							srcset="<?php echo esc_url( $url ) . 'assets/images/forminator-disabled.png'; ?> 1x,
							<?php echo esc_url( $url ) . 'assets/images/forminator-disabled@2x.png'; ?> 2x"
							alt="<?php esc_html_e( 'Forminator APIs', 'forminator' ); ?>"
							class="sui-image sui-image-center fui-image"/>
					<?php endif; ?>

					<div class="fui-limit-block-600 fui-limit-block-center">
						<p>
							<?php
							esc_html_e( 'Build your own integrations and custom Forminator apps using our full featured API! Visit the Forminator API Docs to get started.', 'forminator' );
							?>
						</p>
						<p>
							<a href="https://wpmudev.com/docs/wpmu-dev-plugins/forminator-api-docs/" target="_blank" class="sui-button sui-button-blue">
								<?php esc_html_e( 'Get Started', 'forminator' ); ?>
							</a>
						</p>
					</div>

				</div>

			</div>

		</div>
	<?php } ?>

</div>
