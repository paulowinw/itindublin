<?php
/**
 * Template admin/views/templates/cloud/listings.php
 *
 * @package Forminator
 */

?>
<div class="sui-box-header">
	<h2 class="sui-box-title"><?php esc_html_e( 'Cloud Templates', 'forminator' ); ?></h2>
</div>
<div class="sui-box-body">
	<div class="sui-box-settings-row sui-flushed">
		<p><?php esc_html_e( 'Select a form template from your Hub cloud account below to instantly create a new form.', 'forminator' ); ?></p>
	</div>
	<div class="sui-box-settings-row sui-flushed">
		<div>
			<div class="sui-form-field sui-input-md">
				<input
					type="search"
					placeholder="<?php esc_html_e( 'Search', 'forminator' ); ?>"
					class="sui-form-control search_template search_cloud_template"
				/>
			</div>
		</div>
	</div>
	<!-- loading templates -->
	<div class="sui-progress-block" id="cloud-templates-loading">
		<div class="sui-progress">
			<span class="sui-progress-icon" aria-hidden="true">
				<span class="sui-icon-loader sui-loading"></span>
			</span>
			<span class="sui-progress-text" aria-live="polite">
				<?php esc_html_e( 'Loading templates...', 'forminator' ); ?>
			</span>
		</div>
	</div>
	<div class="sui-box-settings-row sui-flushed" style="display: none;">
		<div class="sui-description">
			<span class="sui-field-prefix">Display</span>
			<span>
				<select id="template-page-items-header" class="sui-select sui-select-inline sui-select-sm">
					<option>10</option>
					<option>20</option>
					<option>30</option>
					<option>40</option>
				</select>
			</span>
			<span class="sui-field-suffix">results per page</span>
		</div>

		<div class="sui-actions-right">
			<div class="sui-pagination-wrap">
				<span class="sui-pagination-results">25 results</span>
				<!-- ELEMENT: List of Pages. -->
				<ul class="sui-pagination">

					<li><a href="" disabled="">
						<span class="sui-icon-arrow-skip-back" aria-hidden="true"></span>
						<span class="sui-screen-reader-text">Go to first page</span>
					</a></li>

					<li><a href="" disabled="">
						<span class="sui-icon-chevron-left" aria-hidden="true"></span>
						<span class="sui-screen-reader-text">Previous page</span>
					</a></li>

					<li class="sui-active"><a href="">1</a></li>
					<li><a href="">2</a></li>
					<li><a href="">3</a></li>
					<li><a href="">4</a></li>
					<li><a href="">5</a></li>

					<li><a href="">
						<span class="sui-icon-chevron-right" aria-hidden="true"></span>
						<span class="sui-screen-reader-text">Next page</span>
					</a></li>

					<li><a href="">
						<span class="sui-icon-arrow-skip-forward" aria-hidden="true"></span>
						<span class="sui-screen-reader-text">Go to last page</span>
					</a></li>

				</ul>
			</div>
		</div>
	</div>
	<div class="sui-box-builder sui-flushed" id="cloud-templates-list" style="display: none;">
		<div class="sui-box-builder-body">
			<div class="sui-builder-fields">
			</div>
		</div>
	</div>
</div>

<div class="sui-box sui-message sui-message-lg" id="forminator-no-cloud-result" style="display: none;">
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
<!--<div class="sui-box-footer" style="display: none;">-->
<!--	<div class="sui-description">-->
<!--		<span class="sui-field-prefix">Display</span>-->
<!--		<span>-->
<!--			<select id="template-page-items-footer" class="sui-select sui-select-inline sui-select-sm">-->
<!--				<option>10</option>-->
<!--				<option>20</option>-->
<!--				<option>30</option>-->
<!--				<option>40</option>-->
<!--			</select>-->
<!--		</span>-->
<!--		<span class="sui-field-suffix">results per page</span>-->
<!--	</div>-->
<!--	<div class="sui-actions-right">-->
<!--		<div class="sui-pagination-wrap">-->
<!--			<span class="sui-pagination-results">25 results</span>-->
			<!-- ELEMENT: List of Pages. -->
<!--			<ul class="sui-pagination">-->
<!---->
<!--				<li><a href="" disabled="">-->
<!--					<span class="sui-icon-arrow-skip-back" aria-hidden="true"></span>-->
<!--					<span class="sui-screen-reader-text">Go to first page</span>-->
<!--				</a></li>-->
<!---->
<!--				<li><a href="" disabled="">-->
<!--					<span class="sui-icon-chevron-left" aria-hidden="true"></span>-->
<!--					<span class="sui-screen-reader-text">Previous page</span>-->
<!--				</a></li>-->
<!---->
<!--				<li class="sui-active"><a href="">1</a></li>-->
<!--				<li><a href="">2</a></li>-->
<!--				<li><a href="">3</a></li>-->
<!--				<li><a href="">4</a></li>-->
<!--				<li><a href="">5</a></li>-->
<!---->
<!--				<li><a href="">-->
<!--					<span class="sui-icon-chevron-right" aria-hidden="true"></span>-->
<!--					<span class="sui-screen-reader-text">Next page</span>-->
<!--				</a></li>-->
<!---->
<!--				<li><a href="">-->
<!--					<span class="sui-icon-arrow-skip-forward" aria-hidden="true"></span>-->
<!--					<span class="sui-screen-reader-text">Go to last page</span>-->
<!--				</a></li>-->
<!---->
<!--			</ul>-->
<!--		</div>-->
<!--	</div>-->
<!--</div>-->
