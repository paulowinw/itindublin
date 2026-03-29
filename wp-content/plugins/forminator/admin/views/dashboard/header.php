<?php
/**
 * Template admin/views/dashboard/header.php
 *
 * @package Forminator
 */

?>
<header class="sui-header">
<h1 class="sui-header-title"><?php esc_html_e( 'Dashboard', 'forminator' ); ?></h1>

<div class="sui-actions-right">
	<?php if ( forminator_is_show_documentation_link() ) : ?>
		<a href="https://wpmudev.com/docs/wpmu-dev-plugins/forminator/#dashboard" target="_blank" class="sui-button sui-button-ghost">
			<i class="sui-icon-academy" aria-hidden="true"></i> <?php esc_html_e( 'View Documentation', 'forminator' ); ?>
		</a>
	<?php endif; ?>
</div>
</header>
