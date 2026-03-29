<?php
/**
 * Template admin/views/poll/entries/content.php
 *
 * @package Forminator
 */

$module_title  = esc_html__( 'Polls', 'forminator' );
$create_dialog = 'polls';
$import_dialog = 'import_poll';
$hash          = '#polls';

require_once forminator_plugin_dir() . 'admin/views/common/list/header.php';
