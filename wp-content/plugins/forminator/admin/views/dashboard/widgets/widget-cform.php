<?php
/**
 * Template admin/views/dashboard/widgets/widget-cform.php
 *
 * @package Forminator
 */

$module_slug        = 'form';
$module_title       = esc_html__( 'Forms', 'forminator' );
$icon               = 'sui-icon-clipboard-notes';
$preview_title      = esc_html__( 'Preview', 'forminator' );
$delete_title       = esc_html__( 'Delete Form', 'forminator' );
$delete_description = esc_html__( 'Are you sure you wish to permanently delete this form?', 'forminator' );
$view_all           = esc_html__( 'View all forms', 'forminator' );
$total              = forminator_cforms_total();
$recent_description = esc_html__( 'Your most recent forms.', 'forminator' );
$description        = esc_html__( 'Create any type of form using one of our pre-made templates, or build your own from scratch.', 'forminator' );

require forminator_plugin_dir() . 'admin/views/common/dashboard/widget.php';
