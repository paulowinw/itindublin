<?php
/**
 * Template admin/views/common/list/header.php
 *
 * @package Forminator
 */

?>
<header class="sui-header">

	<h1 class="sui-header-title"><?php echo esc_html( $module_title ); ?></h1>

	<div class="sui-actions-left">

		<button class="sui-button sui-button-blue wpmudev-button-open-modal" data-modal="<?php echo esc_attr( $create_dialog ); ?>"><i class="sui-icon-plus" aria-hidden="true"></i> <?php esc_html_e( 'Add New', 'forminator' ); ?></button>

		<?php if ( Forminator::is_import_export_feature_enabled() ) : ?>

			<a href="#"
				class="sui-button wpmudev-open-modal"
				data-modal="<?php echo esc_attr( $import_dialog ); ?>"
				data-modal-title=""
				data-nonce="<?php echo esc_attr( wp_create_nonce( 'forminator_popup_' . $import_dialog ) ); ?>"><i class="sui-icon-upload-cloud" aria-hidden="true"></i> <?php esc_html_e( 'Import', 'forminator' ); ?></a>

		<?php endif; ?>

	</div>

	<div class="sui-actions-right">
		<?php if ( forminator_is_show_documentation_link() ) : ?>
			<a href="https://wpmudev.com/docs/wpmu-dev-plugins/forminator/<?php echo esc_attr( $hash ); ?>" target="_blank" class="sui-button sui-button-ghost">
				<i class="sui-icon-academy" aria-hidden="true"></i> <?php esc_html_e( 'View Documentation', 'forminator' ); ?>
			</a>
		<?php endif; ?>
	</div>
</header>
