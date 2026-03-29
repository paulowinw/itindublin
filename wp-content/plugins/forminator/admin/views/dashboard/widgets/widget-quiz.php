<?php
/**
 * Template admin/views/dashboard/widgets/widget-quiz.php
 *
 * @package Forminator
 */

$module_slug        = 'quiz';
$module_title       = esc_html__( 'Quizzes', 'forminator' );
$icon               = 'sui-icon-academy';
$preview_title      = esc_html__( 'Preview', 'forminator' );
$delete_title       = esc_html__( 'Delete Quiz', 'forminator' );
$delete_description = esc_html__( 'Are you sure you wish to permanently delete this quiz?', 'forminator' );
$view_all           = esc_html__( 'View all quizzes', 'forminator' );
$total              = forminator_quizzes_total();
$recent_description = esc_html__( 'Your most recent quizzes.', 'forminator' );
$description        = esc_html__( 'Create fun or challenging quizzes your visitors can take and share on social media.', 'forminator' );

require forminator_plugin_dir() . 'admin/views/common/dashboard/widget.php';
