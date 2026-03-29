<?php
/**
 * Template admin/views/quiz/list/header.php
 *
 * @package Forminator
 */

$module_title  = esc_html__( 'Quizzes', 'forminator' );
$create_dialog = 'quizzes';
$import_dialog = 'import_quiz';
$hash          = '#quizzes';

require_once forminator_plugin_dir() . 'admin/views/common/list/header.php';
