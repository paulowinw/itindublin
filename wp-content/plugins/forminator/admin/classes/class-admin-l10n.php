<?php
/**
 * Forminator Admin L10n
 *
 * @package Forminator
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_Admin_L10n
 *
 * @since 1.0
 */
class Forminator_Admin_L10n {

	/**
	 * Forminator
	 *
	 * @var null|Forminator
	 */
	public $forminator = null;

	/**
	 * Forminator_Admin_L10n constructor
	 */
	public function __construct() {
	}

	/**
	 * Get l10n strings
	 *
	 * @return array
	 */
	public function get_l10n_strings() {
		$l10n = $this->admin_l10n();

		$admin_locale = require_once forminator_plugin_dir() . 'admin/locale.php';

		$locale = array(
			'' => array(
				'localeSlug' => 'default',
			),
		);

		$l10n['locale'] = array_merge( $locale, (array) $admin_locale );

		return apply_filters( 'forminator_l10n', $l10n );
	}

	/**
	 * Default Admin properties
	 *
	 * @return array
	 */
	public function admin_l10n() {
		$current_user = wp_get_current_user();
		$properties   = array(
			'popup'         => array(
				'form_name_label'              => esc_html__( 'Name your form', 'forminator' ),
				'form_name_placeholder'        => esc_html__( 'E.g., Contact Form', 'forminator' ),
				'name'                         => esc_html__( 'Name', 'forminator' ),
				'fields'                       => esc_html__( 'Fields', 'forminator' ),
				'date'                         => esc_html__( 'Date', 'forminator' ),
				'clear_all'                    => esc_html__( 'Clear All', 'forminator' ),
				'your_exports'                 => esc_html__( 'Your exports', 'forminator' ),
				'edit_login_form'              => esc_html__( 'Edit Login or Register form', 'forminator' ),
				'edit_scheduled_export'        => esc_html__( 'Edit Scheduled Export', 'forminator' ),
				'frequency'                    => esc_html__( 'Frequency', 'forminator' ),
				'daily'                        => esc_html__( 'Daily', 'forminator' ),
				'weekly'                       => esc_html__( 'Weekly', 'forminator' ),
				'monthly'                      => esc_html__( 'Monthly', 'forminator' ),
				'week_day'                     => esc_html__( 'Day of the week', 'forminator' ),
				'month_day'                    => esc_html__( 'Day of the month', 'forminator' ),
				'month_year'                   => esc_html__( 'Month of the year', 'forminator' ),
				'monday'                       => esc_html__( 'Monday', 'forminator' ),
				'tuesday'                      => esc_html__( 'Tuesday', 'forminator' ),
				'wednesday'                    => esc_html__( 'Wednesday', 'forminator' ),
				'thursday'                     => esc_html__( 'Thursday', 'forminator' ),
				'friday'                       => esc_html__( 'Friday', 'forminator' ),
				'saturday'                     => esc_html__( 'Saturday', 'forminator' ),
				'sunday'                       => esc_html__( 'Sunday', 'forminator' ),
				'day_time'                     => esc_html__( 'Time of the day', 'forminator' ),
				'email_to'                     => esc_html__( 'Email export data to', 'forminator' ),
				'email_placeholder'            => esc_html__( 'E.g., john@doe.com', 'forminator' ),
				'schedule_help'                => esc_html__( 'Leave blank if you don\'t want to receive exports via email.', 'forminator' ),
				'congratulations'              => esc_html__( 'Congratulations!', 'forminator' ),
				'is_ready'                     => esc_html__( 'is ready!', 'forminator' ),
				'new_form_desc'                => esc_html__( 'Add it to any post / page by clicking Forminator button, or set it up as a Widget.', 'forminator' ),
				'paypal_settings'              => esc_html__( 'Edit PayPal credentials', 'forminator' ),
				'preview_cforms'               => esc_html__( 'Preview Custom Form', 'forminator' ),
				'preview_polls'                => esc_html__( 'Preview Poll', 'forminator' ),
				'preview_quizzes'              => esc_html__( 'Preview Quiz', 'forminator' ),
				'captcha_settings'             => esc_html__( 'Edit reCAPTCHA credentials', 'forminator' ),
				'currency_settings'            => esc_html__( 'Edit default currency', 'forminator' ),
				'pagination_entries'           => esc_html__( 'Submissions | Pagination Settings', 'forminator' ),
				'pagination_listings'          => esc_html__( 'Listings | Pagination Settings', 'forminator' ),
				'email_settings'               => esc_html__( 'Email Settings', 'forminator' ),
				'uninstall_settings'           => esc_html__( 'Uninstall Settings', 'forminator' ),
				'privacy_settings'             => esc_html__( 'Privacy Settings', 'forminator' ),
				'validate_form_name'           => esc_html__( 'Form name cannot be empty! Please pick a name for your form.', 'forminator' ),
				'close'                        => esc_html__( 'Close', 'forminator' ),
				'close_label'                  => esc_html__( 'Close this dialog window', 'forminator' ),
				'close_preview'                => esc_html__( 'Close preview', 'forminator' ),
				'disable_validations'          => esc_html__( 'Disable validations', 'forminator' ),
				'go_back'                      => esc_html__( 'Go back', 'forminator' ),
				'records'                      => esc_html__( 'Records', 'forminator' ),
				'delete'                       => esc_html__( 'Delete', 'forminator' ),
				'confirm'                      => esc_html__( 'Confirm', 'forminator' ),
				'are_you_sure'                 => esc_html__( 'Are you sure?', 'forminator' ),
				'cannot_be_reverted'           => esc_html__( 'Have in mind this action cannot be reverted.', 'forminator' ),
				'are_you_sure_form'            => esc_html__( 'Are you sure you wish to permanently delete this form?', 'forminator' ),
				'are_you_sure_poll'            => esc_html__( 'Are you sure you wish to permanently delete this poll?', 'forminator' ),
				'are_you_sure_quiz'            => esc_html__( 'Are you sure you wish to permanently delete this quiz?', 'forminator' ),
				'delete_form'                  => esc_html__( 'Delete Form', 'forminator' ),
				'delete_poll'                  => esc_html__( 'Delete Poll', 'forminator' ),
				'delete_quiz'                  => esc_html__( 'Delete Quiz', 'forminator' ),
				'confirm_action'               => esc_html__( 'Please confirm that you want to do this action.', 'forminator' ),
				'confirm_title'                => esc_html__( 'Confirm Action', 'forminator' ),
				'confirm_field_delete'         => esc_html__( 'Please confirm that you want to delete this field', 'forminator' ),
				'cancel'                       => esc_html__( 'Cancel', 'forminator' ),
				'save_alert'                   => esc_html__( 'The changes you made may be lost if you navigate away from this page.', 'forminator' ),
				'save_changes'                 => esc_html__( 'Save Changes', 'forminator' ),
				'save'                         => esc_html__( 'Save', 'forminator' ),
				'export_form'                  => esc_html__( 'Export Form', 'forminator' ),
				'export_poll'                  => esc_html__( 'Export Poll', 'forminator' ),
				'export_quiz'                  => esc_html__( 'Export Quiz', 'forminator' ),
				'import_form'                  => esc_html__( 'Import Form', 'forminator' ),
				'import_form_cf7'              => esc_html__( 'Import', 'forminator' ),
				'import_form_ninja'            => esc_html__( 'Import Ninja Forms', 'forminator' ),
				'import_form_gravity'          => esc_html__( 'Import Gravity Forms', 'forminator' ),
				'import_poll'                  => esc_html__( 'Import Poll', 'forminator' ),
				'import_quiz'                  => esc_html__( 'Import Quiz', 'forminator' ),
				'enable_scheduled_export'      => esc_html__( 'Enable scheduled exports', 'forminator' ),
				'scheduled_export_if_new'      => esc_html__( 'Send email only if there are new submissions', 'forminator' ),
				'download_csv'                 => esc_html__( 'Download CSV', 'forminator' ),
				'scheduled_exports'            => esc_html__( 'Scheduled Exports', 'forminator' ),
				'manual_exports'               => esc_html__( 'Manual Exports', 'forminator' ),
				'manual_description'           => esc_html__( 'Download the submissions list in .csv format.', 'forminator' ),
				'scheduled_description'        => esc_html__( 'Enable scheduled exports to get the submissions list in your email.', 'forminator' ),
				'disable'                      => esc_html__( 'Disable', 'forminator' ),
				'enable'                       => esc_html__( 'Enable', 'forminator' ),
				'enter_name'                   => esc_html__( 'Enter a name', 'forminator' ),
				'new_form_desc2'               => esc_html__( 'Name your new form, then let\'s start building!', 'forminator' ),
				'new_poll_desc2'               => esc_html__( 'Name your new poll, then let\'s start building!', 'forminator' ),
				'new_quiz_desc2'               => esc_html__( 'Choose whether you want to collect participants details (e.g. name, email, etc.) on your quiz.', 'forminator' ),
				'learn_more'                   => esc_html__( 'Learn more', 'forminator' ),
				'input_label'                  => esc_html__( 'Input Label', 'forminator' ),
				'form_name_validation'         => esc_html__( 'Form name cannot be empty.', 'forminator' ),
				'poll_name_validation'         => esc_html__( 'Poll name cannot be empty.', 'forminator' ),
				'quiz_name_validation'         => esc_html__( 'Quiz name cannot be empty.', 'forminator' ),
				'new_form_placeholder'         => esc_html__( 'E.g., Blank Form', 'forminator' ),
				'new_poll_placeholder'         => esc_html__( 'E.g., Blank Poll', 'forminator' ),
				'new_quiz_placeholder'         => esc_html__( 'E.g., My Awesome Quiz', 'forminator' ),
				'create'                       => esc_html__( 'Add New', 'forminator' ),
				'reset'                        => esc_html__( 'RESET', 'forminator' ),
				'disconnect'                   => esc_html__( 'Disconnect', 'forminator' ),
				'apply_submission_filter'      => esc_html__( 'Apply Submission Filters', 'forminator' ),
				'registration_notice'          => esc_html__( 'This template allows you to create your own registration form and insert it on a custom page. This doesn\'t modify the default registration form.', 'forminator' ),
				'login_notice'                 => esc_html__( 'This template allows you to create your own login form and insert it on a custom page. This doesn\'t modify the default login form.', 'forminator' ),
				'approve_user'                 => esc_html__( 'Approve', 'forminator' ),
				'registration_name'            => esc_html__( 'User Registration', 'forminator' ),
				'login_name'                   => esc_html__( 'User Login', 'forminator' ),
				'deactivate'                   => esc_html__( 'Deactivate', 'forminator' ),
				'deactivateContent'            => esc_html__( 'Are you sure you want to deactivate this Add-on?', 'forminator' ),
				'deactivateAnyway'             => esc_html__( 'Deactivate Anyway', 'forminator' ),
				'forms'                        => esc_html__( 'Forms', 'forminator' ),
				'quizzes'                      => esc_html__( 'Quizzes', 'forminator' ),
				'polls'                        => esc_html__( 'Polls', 'forminator' ),
				'back'                         => esc_html__( 'Back', 'forminator' ),
				'settings_label'               => esc_html__( 'Settings', 'forminator' ),
				'settings_description'         => esc_html__( 'Configure and customize the content of your report.', 'forminator' ),
				'label_description'            => esc_html__( 'The label is to help you identify this report notification.', 'forminator' ),
				'module_description'           => esc_html__( 'Choose a module for this report.', 'forminator' ),
				'select_forms'                 => esc_html__( 'Select forms', 'forminator' ),
				'select_forms_description'     => esc_html__( 'Choose the forms you want to include in this report.', 'forminator' ),
				'all_forms'                    => esc_html__( 'All Forms', 'forminator' ),
				'selected_forms'               => esc_html__( 'Selected Forms', 'forminator' ),
				'selected_forms_description'   => esc_html__( 'Select one or more forms to include in this report.', 'forminator' ),
				'all_quizzes'                  => esc_html__( 'All Quizzes', 'forminator' ),
				'select_quizzes'               => esc_html__( 'Select quizzes', 'forminator' ),
				'selected_quizzes'             => esc_html__( 'Selected quizzes', 'forminator' ),
				'select_quizzes_description'   => esc_html__( 'Choose the quizzes you want to include in this report.', 'forminator' ),
				'selected_quizzes_description' => esc_html__( 'Select one or more quizzes to include in this report.', 'forminator' ),
				'all_polls'                    => esc_html__( 'All Polls', 'forminator' ),
				'select_polls'                 => esc_html__( 'Select polls', 'forminator' ),
				'selected_polls'               => esc_html__( 'Selected polls', 'forminator' ),
				'select_polls_description'     => esc_html__( 'Choose the polls you want to include in this report.', 'forminator' ),
				'selected_polls_description'   => esc_html__( 'Select one or more polls to include in this report.', 'forminator' ),
				'report_stats'                 => esc_html__( 'Report stats', 'forminator' ),
				'all_stats'                    => esc_html__( 'All stats', 'forminator' ),
				'stats'                        => esc_html__( 'Stats', 'forminator' ),
				'stats_description'            => esc_html__( 'Select the stats data to show in this reports.', 'forminator' ),
				'views'                        => esc_html__( 'Views', 'forminator' ),
				'bounce_rates'                 => esc_html__( 'Bounce rates', 'forminator' ),
				'submissions'                  => esc_html__( 'Submissions', 'forminator' ),
				'conversion_rates'             => esc_html__( 'Conversion rates', 'forminator' ),
				'payments'                     => esc_html__( 'Payments', 'forminator' ),
				'leads'                        => esc_html__( 'Leads', 'forminator' ),
				'schedule_label'               => esc_html__( 'Schedule', 'forminator' ),
				'schedule_description'         => esc_html__( 'Choose how often you want to receive this report email.', 'forminator' ),
				'frequency_description'        => esc_html__( 'Choose the frequency of receiving the report email.', 'forminator' ),
				'frequency_time'               => self::get_timezone_string(),
				'recipients_label'             => esc_html__( 'Recipients', 'forminator' ),
				'recipients_description'       => esc_html__( 'Add the report email recipients below.', 'forminator' ),
				'add_users'                    => esc_html__( 'Add users', 'forminator' ),
				'add_by_email'                 => esc_html__( 'Add by email', 'forminator' ),
				'search_user'                  => esc_html__( 'Search users', 'forminator' ),
				'users'                        => esc_html__( 'Users', 'forminator' ),
				'search_placeholder'           => esc_html__( 'Type username', 'forminator' ),
				'no_recipients'                => esc_html__( 'You\'ve not added the users. In order to activate the notification you need to add users first.', 'forminator' ),
				'no_recipient_disable'         => esc_html__( 'You\'ve removed all recipients.', 'forminator' ),
				'recipient_exists'             => esc_html__( 'Recipient already exists.', 'forminator' ),
				'add_user'                     => esc_html__( 'Add user', 'forminator' ),
				'added_users'                  => esc_html__( 'Added users', 'forminator' ),
				'remove_user'                  => esc_html__( 'Remove user', 'forminator' ),
				'resend_invite'                => esc_html__( 'Resend invite email', 'forminator' ),
				'awaiting_confirmation'        => esc_html__( 'Awaiting confirmation', 'forminator' ),
				'invite_description'           => esc_html__( 'Add recipient details.', 'forminator' ),
				'first_name'                   => esc_html__( 'First name', 'forminator' ),
				'email_address'                => esc_html__( 'Email address', 'forminator' ),
				'add_recipient'                => esc_html__( 'Add recipient', 'forminator' ),
				'adding_recipient'             => esc_html__( 'Adding recipient', 'forminator' ),
				'name_placeholder'             => esc_html__( 'E.g. John', 'forminator' ),
				'invite_no_recipient'          => esc_html__( 'No recipients added. Please add one or more recipients above to finish scheduling the report.', 'forminator' ),
				'status_label'                 => esc_html__( 'Activate report', 'forminator' ),
				'configure'                    => esc_html__( 'Configure', 'forminator' ),
				'deactivate_report'            => esc_html__( 'Deactivate report', 'forminator' ),
				'activate_report'              => esc_html__( 'Activate report', 'forminator' ),
				'form_reports'                 => esc_html__( 'Form reports', 'forminator' ),
				'showing_report_from'          => esc_html__( 'Showing report from', 'forminator' ),
				'time_interval'                => self::get_times(),
				'week_days'                    => forminator_week_days(),
				'month_days'                   => self::get_months(),
				'fetch_nonce'                  => wp_create_nonce( 'forminator-fetch' ),
				'save_nonce'                   => wp_create_nonce( 'forminator-save' ),
			),
			'quiz'          => array(
				'choose_quiz_title'       => esc_html__( 'Create Quiz', 'forminator' ),
				'choose_quiz_description' => esc_html__( 'Let\'s start by giving your quiz a name and choosing the appropriate quiz type based on your goal.', 'forminator' ),
				'quiz_name'               => esc_html__( 'Quiz Name', 'forminator' ),
				'quiz_type'               => esc_html__( 'Quiz Type', 'forminator' ),
				'collect_leads'           => esc_html__( 'Collect Leads', 'forminator' ),
				'no_pagination'           => esc_html__( 'No Pagination', 'forminator' ),
				'paginate_quiz'           => esc_html__( 'Paginated Quiz', 'forminator' ),
				'presentation'            => esc_html__( 'Presentation', 'forminator' ),
				'quiz_pagination'         => esc_html__( 'Quiz Presentation', 'forminator' ),
				'quiz_pagination_descr'   => esc_html__( 'How do you want the quiz questions to be presented to your users? You can break your quiz questions into pages, and display a number of questions at a time or show all questions at once.', 'forminator' ),
				'quiz_pagination_descr2'  => esc_html__( 'You can adjust this configuration at any time in the Behavior settings for your quiz.', 'forminator' ),
				'knowledge_label'         => esc_html__( 'Knowledge Quiz', 'forminator' ),
				'knowledge_description'   => esc_html__( 'Test the knowledge of your visitors on a subject and final score is calculated based on number of right answers. E.g., Test your music knowledge.', 'forminator' ),
				'nowrong_label'           => esc_html__( 'Personality Quiz', 'forminator' ),
				'nowrong_description'     => esc_html__( 'Show different outcomes depending on the visitor\'s answers. There are no wrong answers. E.g., Which superhero are you?', 'forminator' ),
				'continue_button'         => esc_html__( 'Continue', 'forminator' ),
				'quiz_leads_toggle'       => esc_html__( 'Collect leads on your quiz', 'forminator' ),
				'create_quiz'             => esc_html__( 'Create Quiz', 'forminator' ),
				'quiz_leads_desc'         => esc_html__( 'We will automatically create a default lead generation form for you. The lead generation form uses the Forms module, and some of the settings are shared between this quiz and the leads form.', 'forminator' ),
			),
			'form'          => array(
				'form_template_title'       => esc_html__( 'Choose a template or create a blank form', 'forminator' ),
				'form_template_description' => esc_html__( 'Customize one of our pre-made form templates, or start from scratch.', 'forminator' ),
				'continue_button'           => esc_html__( 'Continue', 'forminator' ),
				'result'                    => esc_html__( 'result', 'forminator' ),
				'results'                   => esc_html__( 'results', 'forminator' ),
				'preset_templates'          => esc_html__( 'Preset Templates', 'forminator' ),
				'saved_templates'           => esc_html__( 'Cloud Templates', 'forminator' ),
				'categories'                => esc_html__( 'Categories', 'forminator' ),
				'preview'                   => esc_html__( 'Preview', 'forminator' ),
				'create_blank_form'         => esc_html__( 'Create Blank Form', 'forminator' ),
				'pro'                       => esc_html__( 'PRO', 'forminator' ),
			),
			'sidebar'       => array(
				'label'         => esc_html__( 'Label', 'forminator' ),
				'value'         => esc_html__( 'Value', 'forminator' ),
				'add_option'    => esc_html__( 'Add Option', 'forminator' ),
				'delete'        => esc_html__( 'Delete', 'forminator' ),
				'pick_field'    => esc_html__( 'Pick a field', 'forminator' ),
				'field_will_be' => esc_html__( 'This field will be', 'forminator' ),
				'if'            => esc_html__( 'if', 'forminator' ),
				'shown'         => esc_html__( 'Shown', 'forminator' ),
				'hidden'        => esc_html__( 'Hidden', 'forminator' ),
			),
			'colors'        => array(
				'poll_shadow'       => esc_html__( 'Poll shadow', 'forminator' ),
				'title'             => esc_html__( 'Title text', 'forminator' ),
				'question'          => esc_html__( 'Question text', 'forminator' ),
				'answer'            => esc_html__( 'Answer text', 'forminator' ),
				'input_background'  => esc_html__( 'Input field bg', 'forminator' ),
				'input_border'      => esc_html__( 'Input field border', 'forminator' ),
				'input_placeholder' => esc_html__( 'Input field placeholder', 'forminator' ),
				'input_text'        => esc_html__( 'Input field text', 'forminator' ),
				'btn_background'    => esc_html__( 'Button background', 'forminator' ),
				'btn_text'          => esc_html__( 'Button text', 'forminator' ),
				'link_res'          => esc_html__( 'Results link', 'forminator' ),
			),
			'options'       => array(
				'browse'                => esc_html__( 'Browse', 'forminator' ),
				'clear'                 => esc_html__( 'Clear', 'forminator' ),
				'no_results'            => esc_html__( 'You don\'t have any results yet.', 'forminator' ),
				'select_result'         => esc_html__( 'Select result', 'forminator' ),
				'no_answers'            => esc_html__( 'You don\'t have any answer yet.', 'forminator' ),
				'placeholder_image'     => esc_html__( 'Click browse to add image...', 'forminator' ),
				'placeholder_image_alt' => esc_html__( 'Click on browse to add an image', 'forminator' ),
				'placeholder_answer'    => esc_html__( 'Add an answer here', 'forminator' ),
				'multiqs_empty'         => esc_html__( 'You don\'t have any questions yet.', 'forminator' ),
				'add_question'          => esc_html__( 'Add Question', 'forminator' ),
				'add_new_question'      => esc_html__( 'Add New Question', 'forminator' ),
				'question_title'        => esc_html__( 'Question title', 'forminator' ),
				'question_title_error'  => esc_html__( 'Question title cannot be empty! Please, add some content to your question.', 'forminator' ),
				'answers'               => esc_html__( 'Answers', 'forminator' ),
				'add_answer'            => esc_html__( 'Add Answer', 'forminator' ),
				'add_new_answer'        => esc_html__( 'Add New Answer', 'forminator' ),
				'add_result'            => esc_html__( 'Add Result', 'forminator' ),
				'delete_result'         => esc_html__( 'Delete Result', 'forminator' ),
				'title'                 => esc_html__( 'Title', 'forminator' ),
				'image'                 => esc_html__( 'Image (optional)', 'forminator' ),
				'description'           => esc_html__( 'Description', 'forminator' ),
				'trash_answer'          => esc_html__( 'Delete this answer', 'forminator' ),
				'correct'               => esc_html__( 'Correct answer', 'forminator' ),
				'no_options'            => esc_html__( 'You don\'t have any options yet.', 'forminator' ),
				'delete'                => esc_html__( 'Delete', 'forminator' ),
				'restricted_dates'      => esc_html__( 'Restricted dates:', 'forminator' ),
				'add'                   => esc_html__( 'Add', 'forminator' ),
				'custom_date'           => esc_html__( 'Pick custom date(s) to restrict:', 'forminator' ),
				'form_data'             => esc_html__( 'Form Data', 'forminator' ),
				'required_form_fields'  => esc_html__( 'Required Fields', 'forminator' ),
				'optional_form_fields'  => esc_html__( 'Optional Fields', 'forminator' ),
				'all_fields'            => esc_html__( 'All Submitted Fields', 'forminator' ),
				'form_name'             => esc_html__( 'Form Name', 'forminator' ),
				'misc_data'             => esc_html__( 'Misc Data', 'forminator' ),
				'form_based_data'       => esc_html__( 'Add form data', 'forminator' ),
				'been_saved'            => esc_html__( 'has been saved.', 'forminator' ),
				'been_published'        => esc_html__( 'has been published.', 'forminator' ),
				'error_saving'          => esc_html__( 'Error! Form cannot be saved.', 'forminator' ),
				'default_value'         => esc_html__( 'Default Value', 'forminator' ),
				'admin_email'           => get_option( 'admin_email' ),
				'delete_question'       => esc_html__( 'Delete this question', 'forminator' ),
				'remove_image'          => esc_html__( 'Remove image', 'forminator' ),
				'answer_settings'       => esc_html__( 'Show extra settings', 'forminator' ),
				'add_new_result'        => esc_html__( 'Add New Result', 'forminator' ),
				'multiorder_validation' => esc_html__( 'You need to add at least one result for this quiz so you can re-order the results priority.', 'forminator' ),
				'user_ip_address'       => esc_html__( 'User IP Address', 'forminator' ),
				'date'                  => esc_html__( 'Date', 'forminator' ),
				'embed_id'              => esc_html__( 'Embed Post/Page ID', 'forminator' ),
				'embed_title'           => esc_html__( 'Embed Post/Page Title', 'forminator' ),
				'embed_url'             => esc_html__( 'Embed URL', 'forminator' ),
				'user_agent'            => esc_html__( 'HTTP User Agent', 'forminator' ),
				'refer_url'             => esc_html__( 'HTTP Refer URL', 'forminator' ),
				'display_name'          => esc_html__( 'User Display Name', 'forminator' ),
				'user_email'            => esc_html__( 'User Email', 'forminator' ),
				'user_login'            => esc_html__( 'User Login', 'forminator' ),
				'shortcode_copied'      => esc_html__( 'Shortcode has been copied successfully.', 'forminator' ),
				'uri_copied'            => esc_html__( 'URI has been copied successfully.', 'forminator' ),
			),
			'commons'       => array(
				'color'                          => esc_html__( 'Color', 'forminator' ),
				'colors'                         => esc_html__( 'Colors', 'forminator' ),
				'border_color'                   => esc_html__( 'Border color', 'forminator' ),
				'border_color_hover'             => esc_html__( 'Border color (hover)', 'forminator' ),
				'border_color_active'            => esc_html__( 'Border color (active)', 'forminator' ),
				'border_color_correct'           => esc_html__( 'Border color (correct)', 'forminator' ),
				'border_color_incorrect'         => esc_html__( 'Border color (incorrect)', 'forminator' ),
				'border_width'                   => esc_html__( 'Border width', 'forminator' ),
				'border_style'                   => esc_html__( 'Border style', 'forminator' ),
				'background'                     => esc_html__( 'Background', 'forminator' ),
				'background_hover'               => esc_html__( 'Background (hover)', 'forminator' ),
				'background_active'              => esc_html__( 'Background (active)', 'forminator' ),
				'background_correct'             => esc_html__( 'Background (correct)', 'forminator' ),
				'background_incorrect'           => esc_html__( 'Background (incorrect)', 'forminator' ),
				'font_color'                     => esc_html__( 'Font color', 'forminator' ),
				'font_color_hover'               => esc_html__( 'Font color (hover)', 'forminator' ),
				'font_color_active'              => esc_html__( 'Font color (active)', 'forminator' ),
				'font_color_correct'             => esc_html__( 'Font color (correct)', 'forminator' ),
				'font_color_incorrect'           => esc_html__( 'Font color (incorrect)', 'forminator' ),
				'font_background'                => esc_html__( 'Font background (hover)', 'forminator' ),
				'font_background_active'         => esc_html__( 'Font background (active)', 'forminator' ),
				'font_family'                    => esc_html__( 'Font family', 'forminator' ),
				'font_family_custom'             => esc_html__( 'Custom font family', 'forminator' ),
				'font_family_placeholder'        => esc_html__( 'E.g., \'Arial\', sans-serif', 'forminator' ),
				'font_family_custom_description' => esc_html__( 'Here you can type the font family you want to use, as you would in CSS.', 'forminator' ),
				'icon_size'                      => esc_html__( 'Icon size', 'forminator' ),
				'enable'                         => esc_html__( 'Enable', 'forminator' ),
				'dropdown'                       => esc_html__( 'Dropdown', 'forminator' ),
				'appearance'                     => esc_html__( 'Appearance', 'forminator' ),
				'expand'                         => esc_html__( 'Expand', 'forminator' ),
				'placeholder'                    => esc_html__( 'Placeholder', 'forminator' ),
				'preview'                        => esc_html__( 'Preview', 'forminator' ),
				'icon_color'                     => esc_html__( 'Icon color', 'forminator' ),
				'icon_color_hover'               => esc_html__( 'Icon color (hover)', 'forminator' ),
				'icon_color_active'              => esc_html__( 'Icon color (active)', 'forminator' ),
				'icon_color_correct'             => esc_html__( 'Icon color (correct)', 'forminator' ),
				'icon_color_incorrect'           => esc_html__( 'Icon color (incorrect)', 'forminator' ),
				'box_shadow'                     => esc_html__( 'Box shadow', 'forminator' ),
				'enable_settings'                => esc_html__( 'Enable settings', 'forminator' ),
				'font_size'                      => esc_html__( 'Font size', 'forminator' ),
				'font_weight'                    => esc_html__( 'Font weight', 'forminator' ),
				'text_align'                     => esc_html__( 'Text align', 'forminator' ),
				'regular'                        => esc_html__( 'Regular', 'forminator' ),
				'medium'                         => esc_html__( 'Medium', 'forminator' ),
				'large'                          => esc_html__( 'Large', 'forminator' ),
				'light'                          => esc_html__( 'Light', 'forminator' ),
				'normal'                         => esc_html__( 'Normal', 'forminator' ),
				'bold'                           => esc_html__( 'Bold', 'forminator' ),
				'typography'                     => esc_html__( 'Typography', 'forminator' ),
				'padding_top'                    => esc_html__( 'Top padding', 'forminator' ),
				'padding_right'                  => esc_html__( 'Right padding', 'forminator' ),
				'padding_bottom'                 => esc_html__( 'Bottom padding', 'forminator' ),
				'padding_left'                   => esc_html__( 'Left padding', 'forminator' ),
				'border_radius'                  => esc_html__( 'Border radius', 'forminator' ),
				'date_placeholder'               => esc_html__( '20 April 2018', 'forminator' ),
				'left'                           => esc_html__( 'Left', 'forminator' ),
				'center'                         => esc_html__( 'Center', 'forminator' ),
				'right'                          => esc_html__( 'Right', 'forminator' ),
				'none'                           => esc_html__( 'None', 'forminator' ),
				'solid'                          => esc_html__( 'Solid', 'forminator' ),
				'dashed'                         => esc_html__( 'Dashed', 'forminator' ),
				'dotted'                         => esc_html__( 'Dotted', 'forminator' ),
				'delete_option'                  => esc_html__( 'Delete option', 'forminator' ),
				'label'                          => esc_html__( 'Label', 'forminator' ),
				'value'                          => esc_html__( 'Value', 'forminator' ),
				'reorder_option'                 => esc_html__( 'Re-order this option', 'forminator' ),
				'forminator_ui'                  => esc_html__( 'Forminator UI', 'forminator' ),
				'forminator_bold'                => esc_html__( 'Forminator Bold', 'forminator' ),
				'forminator_flat'                => esc_html__( 'Forminator Flat', 'forminator' ),
				'material_design'                => esc_html__( 'Material Design', 'forminator' ),
				'no_file_chosen'                 => esc_html__( 'No file chosen', 'forminator' ),
				'update_successfully'            => esc_html__( 'saved successfully!', 'forminator' ),
				'update_unsuccessfull'           => esc_html__( 'Error! Settings were not saved.', 'forminator' ),
				'approve_user_successfull'       => esc_html__( 'User approved successfully.', 'forminator' ),
				'error_message'                  => esc_html__( 'Something went wrong!', 'forminator' ),
				'approve_user_unsuccessfull'     => esc_html__( 'Error! User was not approved.', 'forminator' ),
				/* translators: %s - error code(s) */
				'turnstile_error'                => esc_html__( 'Turnstile error code(s): %s', 'forminator' ),
			),
			'social'        => array(
				'facebook'    => esc_html__( 'Facebook', 'forminator' ),
				'twitter'     => esc_html__( 'X', 'forminator' ),
				'google_plus' => esc_html__( 'Google+', 'forminator' ),
				'linkedin'    => esc_html__( 'LinkedIn', 'forminator' ),
			),
			'calendar'      => array(
				'day_names_min' => self::get_short_days_names(),
				'month_names'   => self::get_months_names(),
			),
			'exporter'      => array(
				'export_nonce' => wp_create_nonce( 'forminator_export' ),
				'form_id'      => forminator_get_form_id_helper(),
				'form_type'    => forminator_get_form_type_helper(),
				'enabled'      => filter_var( forminator_get_exporter_info( 'enabled', forminator_get_form_id_helper() . forminator_get_form_type_helper() ), FILTER_VALIDATE_BOOLEAN ),
				'interval'     => forminator_get_exporter_info( 'interval', forminator_get_form_id_helper() . forminator_get_form_type_helper() ),
				'month_day'    => forminator_get_exporter_info( 'month_day', forminator_get_form_id_helper() . forminator_get_form_type_helper() ),
				'day'          => forminator_get_exporter_info( 'day', forminator_get_form_id_helper() . forminator_get_form_type_helper() ),
				'hour'         => forminator_get_exporter_info( 'hour', forminator_get_form_id_helper() . forminator_get_form_type_helper() ),
				'email'        => forminator_get_exporter_info( 'email', forminator_get_form_id_helper() . forminator_get_form_type_helper() ),
				'if_new'       => forminator_get_exporter_info( 'if_new', forminator_get_form_id_helper() . forminator_get_form_type_helper() ),
				'noResults'    => esc_html__( 'No Result Found', 'forminator' ),
				'searching'    => esc_html__( 'Searching', 'forminator' ),
			),
			'exporter_logs' => forminator_get_export_logs( forminator_get_form_id_helper() ),
			'geolocation'   => array(
				'configure_title' => esc_html__( 'Configure Geolocation', 'forminator' ),
			),
			'templates'     => array(
				'create_form'       => esc_html__( 'Create Form', 'forminator' ),
				'open_actions'      => esc_html__( 'Open actions', 'forminator' ),
				'alt_logo'          => esc_html__( 'WPMU DEV Logo', 'forminator' ),
				'login'             => esc_html__( 'Log In to Your WPMU DEV Account', 'forminator' ),
				'login_plugin'      => esc_html__( 'Looks like your site is not connected to WPMU DEV. Log in to your WPMU DEV account to access cloud templates.', 'forminator' ),
				'login_plugin2'     => esc_html__( 'Looks like your site is not connected to WPMU DEV. Log in to your WPMU DEV account to unlock the complete list of preset templates.', 'forminator' ),
				'login_button'      => esc_html__( 'LOG IN TO WPMU DEV', 'forminator' ),
				'login_dashboard'   => esc_url( network_admin_url( 'admin.php?page=wpmudev' ) ),
				'renew_button'      => esc_html__( 'Renew Membership', 'forminator' ),
				'expired'           => esc_html__( 'WPMU DEV Membership Expired', 'forminator' ),
				'renew_membership'  => sprintf(
					/* translators: %s - current user display name */
					esc_html__( 'Hey %s, your WPMU DEV membership has expired. You need an active membership to use the preset templates. Renew your membership to get instant access to our pre-designed form templates.', 'forminator' ),
					esc_html( $current_user->display_name )
				),
				'renew_membership2' => sprintf(
					/* translators: %s - current user display name */
					esc_html__( 'Hey %s, your WPMU DEV membership has expired. You need an active membership to access cloud templates. Renew your membership to get instant access to the cloud templates.', 'forminator' ),
					esc_html( $current_user->display_name )
				),
				'install_dashboard' => esc_html__( 'You don\'t have the WPMU DEV Dashboard plugin, which you\'ll need to access Pro preset templates. Install and log in to the dashboard to unlock the complete list of preset templates.', 'forminator' ),
				'install_button'    => esc_html__( 'Install Plugin', 'forminator' ),
				'no_templates'      => esc_html__( 'No saved templates yet', 'forminator' ),
				'no_templates_desc' => esc_html__( 'Save any of your forms as cloud templates to reuse them across your sites connected to The Hub – no need to start from scratch. Your saved templates will appear here. ', 'forminator' ),
				'no_templates_link' => sprintf(
					/* translators: %1$s - opening anchor tag, %2$s - closing anchor tag */
					esc_html__( '%1$sLearn how to save forms as cloud templates%2$s.', 'forminator' ),
					'<a href="https://wpmudev.com/docs/wpmu-dev-plugins/forminator/#templates" target="_blank">',
					'</a>'
				),
				'duplicate'         => esc_html__( 'Duplicate', 'forminator' ),
				'rename'            => esc_html__( 'Rename', 'forminator' ),
				'rename_template'   => esc_html__( 'Rename Template', 'forminator' ),
				'rename_descr'      => esc_html__( 'Enter a new name for your template.', 'forminator' ),
				'delete_template'   => esc_html__( 'Delete Template', 'forminator' ),
				'delete_nonce'      => wp_create_nonce( 'forminator-delete-cloud-template' ),
				'rename_nonce'      => wp_create_nonce( 'forminator-rename-cloud-template' ),
				'duplicate_nonce'   => wp_create_nonce( 'forminator-duplicate-cloud-template' ),
				'delete_confirm'    => esc_html__( 'Are you sure you want to permanently delete the selected template from your Hub cloud account?', 'forminator' ),
				'empty_search'      => esc_html__( 'We couldn\'t find any template matching your search keyword. Please try again.', 'forminator' ),
				'not_found'         => esc_html__( 'No result found', 'forminator' ),
				'search'            => esc_html__( 'Search', 'forminator' ),
				'no_results'        => esc_html__( 'No result for “{search_text}”', 'forminator' ),
				'loading_templates' => esc_html__( 'Loading templates...', 'forminator' ),
				'load_categories'   => esc_html__( 'Loading categories...', 'forminator' ),
				'upgrade'           => esc_html__( 'Upgrade', 'forminator' ),
				'free_plan'         => esc_html__( 'Free Plan', 'forminator' ),
			),
		);

		$properties = self::add_notice( $properties );

		return $properties;
	}

	/**
	 * Maybe add notices to properties
	 *
	 * @param array $properties Properties.
	 *
	 * @return array
	 */
	private static function add_notice( $properties ) {
		if ( isset( $_GET['forminator_notice'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$notices = self::get_notices_list();
			$key     = (string) Forminator_Core::sanitize_text_field( 'forminator_notice' );
			if ( ! empty( $notices[ $key ] ) ) {
				$properties['notices'][] = $notices[ $key ];
			}
		}

		if ( isset( $_GET['forminator_text_notice'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$properties['notices']['custom_notice'] = Forminator_Core::sanitize_text_field( 'forminator_text_notice' );
		}

		if ( isset( $_GET['forminator_error_notice'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$properties['notices']['error_notice'] = Forminator_Core::sanitize_text_field( 'forminator_error_notice' );
		}

		return $properties;
	}

	/**
	 * All possible notices that can be shown after refreshing page
	 *
	 * @return array
	 */
	private static function get_notices_list() {
		$list = array(
			'settings_reset'  => esc_html__( 'Data and settings have been reset successfully!', 'forminator' ),
			'form_deleted'    => esc_html__( 'Form successfully deleted.', 'forminator' ),
			'poll_deleted'    => esc_html__( 'Poll successfully deleted.', 'forminator' ),
			'quiz_deleted'    => esc_html__( 'Quiz successfully deleted.', 'forminator' ),
			'form_duplicated' => esc_html__( 'Form successfully duplicated.', 'forminator' ),
			'poll_duplicated' => esc_html__( 'Poll successfully duplicated.', 'forminator' ),
			'quiz_duplicated' => esc_html__( 'Quiz successfully duplicated.', 'forminator' ),
			'form_reset'      => esc_html__( 'Form tracking data successfully reset.', 'forminator' ),
			'poll_reset'      => esc_html__( 'Poll tracking data successfully reset.', 'forminator' ),
			'quiz_reset'      => esc_html__( 'Quiz tracking data successfully reset.', 'forminator' ),
			'preset_deleted'  => esc_html__( 'The selected preset has been successfully deleted.', 'forminator' ),
		);

		return $list;
	}

	/**
	 * Get short days names html escaped and translated
	 *
	 * @return array
	 * @since 1.5.4
	 */
	public static function get_short_days_names() {
		return array(
			esc_html__( 'Su', 'forminator' ),
			esc_html__( 'Mo', 'forminator' ),
			esc_html__( 'Tu', 'forminator' ),
			esc_html__( 'We', 'forminator' ),
			esc_html__( 'Th', 'forminator' ),
			esc_html__( 'Fr', 'forminator' ),
			esc_html__( 'Sa', 'forminator' ),
		);
	}

	/**
	 * Get months names html escaped and translated
	 *
	 * @return array
	 * @since 1.5.4
	 */
	public static function get_months_names() {
		return array(
			esc_html__( 'January', 'forminator' ),
			esc_html__( 'February', 'forminator' ),
			esc_html__( 'March', 'forminator' ),
			esc_html__( 'April', 'forminator' ),
			esc_html__( 'May', 'forminator' ),
			esc_html__( 'June', 'forminator' ),
			esc_html__( 'July', 'forminator' ),
			esc_html__( 'August', 'forminator' ),
			esc_html__( 'September', 'forminator' ),
			esc_html__( 'October', 'forminator' ),
			esc_html__( 'November', 'forminator' ),
			esc_html__( 'December', 'forminator' ),
		);
	}

	/**
	 * Return times frame for select box
	 *
	 * @return mixed
	 * @since 1.20.0
	 */
	private static function get_times() {
		$data = array();
		for ( $i = 0; $i < 24; $i++ ) {
			foreach ( apply_filters( 'forminator_get_times_interval', array( '00' ) ) as $min ) {
				$time_key   = $i . ':' . $min;
				$time_value = date_format( date_create( $time_key ), 'h:i A' );
				$data[]     = apply_filters( 'forminator_get_times_hour_min', $time_value );
			}
		}

		return apply_filters( 'forminator_get_times', $data );
	}

	/**
	 * Return time zone string.
	 *
	 * @return string
	 * @since 3.1.1
	 */
	private static function get_timezone_string() {
		$current_offset = get_option( 'gmt_offset' );
		$tzstring       = get_option( 'timezone_string' );

		if ( empty( $tzstring ) ) { // Create a UTC+- zone if no timezone string exists.
			if ( 0 === $current_offset ) {
				$tzstring = 'UTC+0';
			} elseif ( $current_offset < 0 ) {
				$tzstring = 'UTC' . $current_offset;
			} else {
				$tzstring = 'UTC+' . $current_offset;
			}
		}

		$timezone_string = sprintf(
		/* translators: %1$s - time zone, %2$s - current time, %3$s - WordPress setting link, %4$s - <a> tag closing */
			esc_html__( 'Your site\'s current time is %1$s %2$s based on your %3$sWordPress Settings%4$s', 'forminator' ),
			'<strong>' . esc_html( date_i18n( 'h:i a' ) ) . '</strong>',
			'<strong>' . esc_html( $tzstring ) . '</strong>',
			'<a href="' . esc_url( admin_url( 'options-general.php' ) ) . '" target="_blank">',
			'</a>'
		);

		return $timezone_string;
	}

	/**
	 * Return months frame for select box
	 *
	 * @return mixed
	 * @since 1.20.0
	 */
	private static function get_months() {
		$days_data = array();
		$days      = range( 1, 28 );
		foreach ( $days as $day ) {
			$days_data[] = $day;
		}

		return apply_filters( 'forminator_get_months', $days_data );
	}
}
