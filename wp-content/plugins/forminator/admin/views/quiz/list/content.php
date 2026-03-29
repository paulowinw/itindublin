<?php
/**
 * Template admin/views/quiz/list/content.php
 *
 * @package Forminator
 */

/**
 * Forminator_Quiz_Page
 *
 *  @var Forminator_Quiz_Page $this */

// Search keyword.
$search_keyword      = Forminator_Core::sanitize_text_field( 'module-search' );
$is_search           = (bool) $search_keyword;
$search_module_nonce = esc_attr( 'forminator-nonce-search-module' );

// Get modules.
$modules = $this->getModules();

// Count total quizzes.
$count = ! $is_search ? $this->countModules() : count( $modules );

// Start date for retrieving the information of the last 30 days in sql format.
$sql_month_start_date = gmdate( 'Y-m-d H:i:s', strtotime( '-30 days midnight' ) );

$entry_type  = 'quizzes';
$most_entry  = Forminator_Form_Entry_Model::get_most_entry( $entry_type );
$wizard_page = null;
if ( $most_entry ) {
	$most_entry_model = Forminator_Base_Form_Model::get_model( $most_entry->form_id );
	$wizard_page      = 'forminator-' . ( 'nowrong' === $most_entry_model->quiz_type ? $most_entry_model->quiz_type : 'knowledge' ) . '-wizard';
}
$empty_title   = esc_html__( 'Create fun or challenging quizzes for your visitors to take and share on social media.', 'forminator' );
$create_dialog = 'quizzes';
$preview_title = esc_html__( 'Preview', 'forminator' );

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
?>

<script>
	jQuery( '.fui-select-listing-data' ).change( function( e ) {
		var $el   = jQuery( this ),
			$parent = $el.closest( '.sui-accordion-item' ),
			submissions = $parent.find( '.forminator-leads-submissions' ),
			leads = $parent.find( '.forminator-leads-leads'),
			submissionsRate = $parent.find( '.forminator-submission-rate' ),
			leadsRate = $parent.find( '.forminator-leads-rate' ),
			statsChart = $parent.find( '.forminator-stats-chart'),
			leadsChart = $parent.find( '.forminator-leads-chart'),
			value = $el.val()
		;

		if ( value === 'leads' ) {
			submissions.hide();
			submissionsRate.hide();
			statsChart.hide();
			leads.show();
			leadsRate.show();
			leadsChart.show();
		} else {
			submissions.show();
			submissionsRate.show();
			statsChart.show();
			leads.hide();
			leadsRate.hide();
			leadsChart.hide();
		}
	}).trigger('change');
</script>
