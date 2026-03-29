<?php
/**
 * Template admin/views/custom-form/list/content.php
 *
 * @package Forminator
 */

/**
 * Forminator_CForm_Page
 *
 * @var Forminator_CForm_Page $this */

// Search keyword.
$search_keyword      = Forminator_Core::sanitize_text_field( 'module-search' );
$is_search           = (bool) $search_keyword;
$search_module_nonce = esc_attr( 'forminator-nonce-search-module' );

// Get modules.
$modules = $this->getModules();

// Count total forms.
$count = ! $is_search ? $this->countModules() : count( $modules );

// Start date for retrieving the information of the last 30 days in sql format.
$sql_month_start_date = gmdate( 'Y-m-d H:i:s', strtotime( '-30 days midnight' ) );

$entry_type    = 'custom-forms';
$wizard_page   = 'forminator-cform-wizard';
$create_dialog = 'custom_forms';
$preview_title = esc_html__( 'Preview', 'forminator' );
$empty_title   = esc_html__( 'Create custom forms for all your needs with as many fields as you like. From contact forms to quote requests and everything in between.', 'forminator' );

require_once forminator_plugin_dir() . 'admin/views/common/list/summary.php';

if ( $count > 0 ) {
	require_once forminator_plugin_dir() . 'admin/views/common/list/main_content.php';
} else {
	require_once forminator_plugin_dir() . 'admin/views/common/list/empty_content.php';
}

$chart_args = array(
	'start_date' => $sql_month_start_date,
);
$this->template( 'common/list/chart_script', $chart_args );
