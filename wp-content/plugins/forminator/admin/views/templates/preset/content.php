<?php
/**
 * Template admin/views/templates/preset/content.php
 *
 * @package Forminator
 */

$section              = Forminator_Core::sanitize_text_field( 'section', 'all' );
$custom_form_instance = Forminator_Custom_Forms::get_instance();
$templates            = $custom_form_instance->get_templates();
$templates_categories = $custom_form_instance->get_templates_categories();
?>
<div
	role="tabpanel"
	tabindex="0"
	id="all-templates-content"
	class="sui-tab-content<?php echo 'preset' === $args['current_tab'] ? ' active' : ''; ?>"
	aria-labelledby="all-templates"
>
	<div class="sui-row-with-sidenav">
		<!-- Navigation -->
		<div role="navigation" class="sui-sidenav">
			<div class="forminator-category-name">
				<?php esc_html_e( 'Categories', 'forminator' ); ?>
			</div>
			<ul class="sui-vertical-tabs sui-sidenav-hide-md">
				<?php foreach ( $templates_categories as $category ) { ?>
					<li class="sui-vertical-tab <?php echo $category['slug'] === $section ? 'current' : ''; ?>">
						<a href="#" role="button" data-nav="<?php echo esc_attr( $category['slug'] ); ?>">
							<?php echo esc_html( $category['name'] ); ?>
						</a>
						<span class="sui-tag forminator-tag-number">
							<?php echo esc_html( $category['templates_count'] ); ?>
						</span>
					</li>
				<?php } ?>
			</ul>
			<div class="sui-sidenav-hide-lg">
				<select id="mobile_select_categories" class="sui-select sui-mobile-nav" style="display: none;">
					<?php foreach ( $templates_categories as $category ) { ?>
						<option value="<?php echo esc_html( $category['slug'] ); ?>">
							<?php echo esc_html( $category['name'] ); ?>
						</option>
					<?php } ?>
				</select>
			</div>
		</div>
		<?php foreach ( $templates_categories as $category ) { ?>
			<div class="sui-box" data-nav="<?php echo esc_attr( $category['slug'] ); ?>" style="<?php echo $section !== $category['slug'] ? 'display: none;' : ''; ?>">
				<div class="sui-box-header">
					<h2 class="sui-box-title"><?php echo esc_attr( $category['name'] ); ?></h2>
				</div>
				<div class="sui-box-body">
					<div class="sui-box-settings-row sui-flushed">
						<p><?php esc_html_e( 'Use the pre-designed templates below to instantly create a form.', 'forminator' ); ?></p>
					</div>
					<div class="sui-box-settings-row sui-flushed">
						<div>
							<div class="sui-form-field sui-input-md">
								<input
									type="search"
									placeholder="<?php esc_html_e( 'Search', 'forminator' ); ?>"
									class="sui-form-control search_template"
								/>
							</div>
						</div>
<!--						<div class="sui-actions-right">-->
<!--							<div class="sui-pagination-wrap">-->
								<!-- ELEMENT: List of Pages. -->
<!--								<ul class="sui-pagination">-->
<!--									<li class="sui-active"><a href="#" role="button">1</a></li>-->
<!--									<li><a href="#" role="button">2</a></li>-->
<!--									<li><a href="#" role="button">3</a></li>-->
<!--									<li><a href="#" role="button">...</a></li>-->
<!--									<li><a href="#" role="button">10</a></li>-->
<!--								</ul>-->
<!--							</div>-->
<!--						</div>-->
					</div>
					<div class="forminator-template-cards">
						<div class="sui-box-selectors sui-box-selectors-col-3">
							<ul>
								<?php
								foreach ( $templates as $template ) {
									if ( 'all' === $category['slug'] || $template['category'] === $category['slug'] ) {
										Forminator_Admin_Addons_Page::get_instance()->render_template(
											'admin/views/templates/preset/listing',
											$template
										);
									}
								}
								?>
							</ul>
							<div class="forminator-template-notice">
								<h2><?php esc_html_e( 'Looking for more templates?', 'forminator' ); ?></h2>
								<p>
									<?php
									printf(
										/* translators: %1$s - opening anchor tag, %2$s - closing anchor tag */
										esc_html__( 'We\'re working hard to bring you more amazing templates. Stay tuned for new template updates. Got any suggestions? %1$sDrop them here.%2$s', 'forminator' ),
										'<a target="_blank" href="https://docs.google.com/forms/d/1hrh79ugkazCQIO7pEOqR98O3S7UEmz6IyicOFcjAuRk">',
										'</a>'
									);
									?>
								</p>
							</div>
						</div>
					</div>
				</div>
<!--				<div class="sui-box-footer">-->
<!--					<div><span class="sui-description">Showing 27 of 27 Results</span></div>-->
<!--					<div class="sui-actions-right">-->
<!--						<div class="sui-pagination-wrap">-->
							<!-- ELEMENT: List of Pages. -->
<!--							<ul class="sui-pagination">-->
<!--								<li class="sui-active"><a href="#" role="button">1</a></li>-->
<!--								<li><a href="#" role="button">2</a></li>-->
<!--								<li><a href="#" role="button">3</a></li>-->
<!--								<li><a href="#" role="button">...</a></li>-->
<!--								<li><a href="#" role="button">10</a></li>-->
<!--							</ul>-->
<!--						</div>-->
<!--					</div>-->
<!--				</div>-->
			</div>
		<?php } ?>
		<div class="sui-box sui-message sui-message-lg" id="forminator-no-search-result" style="display: none;">
			<img src="<?php echo esc_url( forminator_plugin_url() . 'assets/images/forminator-no-result.png' ); ?>"
				srcset="<?php echo esc_url( forminator_plugin_url() . 'assets/images/forminator-no-result.png' ); ?> 1x, <?php echo esc_url( forminator_plugin_url() . 'assets/images/forminator-no-result@2x.png' ); ?> 2x"
				alt="<?php esc_attr_e( 'Forminator no result', 'forminator' ); ?>"
				class="sui-image sui-image-center fui-image">
			<div class="sui-message-content">
				<h3 data-title="<?php esc_attr_e( 'No result for “{search_text}”', 'forminator' ); ?>"></h3>
				<p>
					<?php esc_html_e( 'We couldn\'t find any template matching your search keyword. Please try again.', 'forminator' ); ?>
				</p>
			</div>
		</div>
	</div>
</div>
