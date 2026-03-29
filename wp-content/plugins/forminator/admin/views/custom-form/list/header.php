<?php
/**
 * Template admin/views/custom-form/list/header.php
 *
 * @package Forminator
 */

$module_title  = esc_html__( 'Forms', 'forminator' );
$create_dialog = 'custom_forms';
$import_dialog = 'import_form';
$hash          = '#forms';

require_once forminator_plugin_dir() . 'admin/views/common/list/header.php';
