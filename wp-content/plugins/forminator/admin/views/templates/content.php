<?php
/**
 * Template admin/views/templates/content.php
 *
 * @package Forminator
 */

echo forminator_template( 'common/banner/content' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

$current_tab = filter_input( INPUT_GET, 'tab' ) ?? 'preset';
?>

<div id="forminator-templates" class="sui-tabs">

	<div role="tablist" class="sui-tabs-menu">

		<button
			type="button"
			role="tab"
			id="all-templates"
			class="sui-tab-item<?php echo 'preset' === $current_tab ? ' active' : ''; ?>"
			aria-controls="all-templates-content"
			aria-selected="<?php echo 'preset' === $current_tab ? 'true' : 'false" tabindex="-1'; ?>"
		>
			<?php esc_html_e( 'Preset Templates', 'forminator' ); ?>
		</button>
		<?php if ( ! forminator_cloud_templates_disabled() ) { ?>
			<button
				type="button"
				role="tab"
				id="cloud-templates"
				class="sui-tab-item <?php echo 'cloud' === $current_tab ? ' active' : ''; ?>"
				aria-controls="cloud-templates-content"
				aria-selected="<?php echo 'cloud' === $current_tab ? 'true' : 'false" tabindex="-1'; ?>"
			>
				<?php esc_html_e( 'Cloud Templates', 'forminator' ); ?>
			</button>
		<?php } ?>
	</div>

	<div class="sui-tabs-content">
		<?php
		echo forminator_template( 'templates/preset/content', array( 'current_tab' => $current_tab ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		if ( ! forminator_cloud_templates_disabled() ) {
			echo forminator_template( 'templates/cloud/content', array( 'current_tab' => $current_tab ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		?>
	</div>
	<?php echo forminator_template( 'templates/preset/popup' ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ ?>
</div>
