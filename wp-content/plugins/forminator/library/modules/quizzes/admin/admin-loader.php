<?php
/**
 * The Forminator_Quiz_Admin class.
 *
 * @package Forminator
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_Quiz_Admin
 *
 * @property string            page_edit_nowrong
 * @property Forminator_Module module
 * @property string            page_edit_knowledge
 *
 * @since 1.0
 */
class Forminator_Quiz_Admin extends Forminator_Admin_Module {

	/**
	 * Module objects
	 *
	 * @var array
	 */
	public $module;

	/**
	 * Page nowrong edit
	 *
	 * @var string
	 */
	public $page_edit_nowrong;

	/**
	 * Page knowledge edit
	 *
	 * @var string
	 */
	public $page_edit_knowledge;

	/**
	 * Initialize
	 *
	 * @since 1.0
	 */
	public function init() {
		$this->module              = Forminator_Quizzes::get_instance();
		$this->page                = 'forminator-quiz';
		$this->page_edit_nowrong   = 'forminator-nowrong-wizard';
		$this->page_edit_knowledge = 'forminator-knowledge-wizard';
		$this->page_entries        = 'forminator-quiz-view';
	}

	/**
	 * Include required files
	 *
	 * @since 1.0
	 */
	public function includes() {
		include_once __DIR__ . '/admin-page-new-nowrong.php';
		include_once __DIR__ . '/admin-page-new-knowledge.php';
		include_once __DIR__ . '/admin-page-view.php';
		include_once __DIR__ . '/admin-page-entries.php';
		include_once __DIR__ . '/admin-renderer-entries.php';
	}

	/**
	 * Add module pages to Admin
	 *
	 * @since 1.0
	 */
	public function add_menu_pages() {
		new Forminator_Quiz_Page( $this->page, 'quiz/list', esc_html__( 'Quizzes', 'forminator' ), esc_html__( 'Quizzes', 'forminator' ), 'forminator' );
		new Forminator_Quiz_New_NoWrong( $this->page_edit_nowrong, 'quiz/nowrong', esc_html__( 'Edit Quiz', 'forminator' ), esc_html__( 'New Quiz', 'forminator' ), 'forminator' );
		new Forminator_Quiz_New_Knowledge( $this->page_edit_knowledge, 'quiz/knowledge', esc_html__( 'Edit Quiz', 'forminator' ), esc_html__( 'New Quiz', 'forminator' ), 'forminator' );
		new Forminator_Quiz_View_Page( $this->page_entries, 'quiz/entries', esc_html__( 'Submissions:', 'forminator' ), esc_html__( 'View Quizzes', 'forminator' ), 'forminator' );
	}

	/**
	 * Remove necessary pages from menu
	 *
	 * @since 1.0
	 */
	public function hide_menu_pages() {
		remove_submenu_page( 'forminator', $this->page_edit_nowrong );
		remove_submenu_page( 'forminator', $this->page_edit_knowledge );
		remove_submenu_page( 'forminator', $this->page_entries );
		remove_submenu_page( 'forminator', $this->page_entries );
	}

	/**
	 * Is the type of the quiz "knowledge"
	 *
	 * @since 1.0
	 * @return bool
	 */
	public function is_knowledge_wizard() {
		global $plugin_page;

		$page = Forminator_Core::sanitize_text_field( 'page' );
		if ( empty( $plugin_page ) ) {
			return $this->page_edit_knowledge === $page;
		}

		return $this->page_edit_knowledge === $plugin_page;
	}

	/**
	 * Is the type of the quiz "no wrong answer"
	 *
	 * @since 1.0
	 * @return bool
	 */
	public function is_nowrong_wizard() {
		global $plugin_page;

		$page = Forminator_Core::sanitize_text_field( 'page' );
		if ( empty( $plugin_page ) ) {
			return $this->page_edit_nowrong === $page;
		}

		return $this->page_edit_nowrong === $plugin_page;
	}

	/**
	 * Highlight parent page in sidebar
	 *
	 * @deprecated 1.1 No longer used because this function override prohibited WordPress global of $plugin_page
	 * @since      1.0
	 *
	 * @param string $file File.
	 *
	 * @return mixed
	 */
	public function highlight_admin_parent( $file ) {
		_deprecated_function( __METHOD__, '1.1', null );

		return $file;
	}

	/**
	 * Highlight submenu on admin page
	 *
	 * @since 1.1
	 *
	 * @param string $submenu_file Sub menu file.
	 * @param string $parent_file Parent file.
	 *
	 * @return string
	 */
	public function admin_submenu_file( $submenu_file, $parent_file ) {
		global $plugin_page;

		if ( 'forminator' !== $parent_file ) {
			return $submenu_file;
		}

		if ( $this->page_edit_nowrong === $plugin_page || $this->page_edit_knowledge === $plugin_page || $this->page_entries === $plugin_page ) {
			$submenu_file = $this->page;
		}

		return $submenu_file;
	}

	/**
	 * Pass module defaults to JS
	 *
	 * @since 1.0
	 *
	 * @param array $data Date.
	 *
	 * @return mixed
	 */
	public function add_js_defaults( $data ) {
		$model         = null;
		$wrappers      = array();
		$lead_settings = array();

		if ( $this->is_knowledge_wizard() || $this->is_nowrong_wizard() ) {
			$id = filter_input( INPUT_GET, 'id', FILTER_VALIDATE_INT );
			if ( $id && is_null( $model ) ) {
				/**
				 * Forminator_Quiz_Model
				 *
				 * @var  Forminator_Quiz_Model $model */
				$model = Forminator_Base_Form_Model::get_model( $id );
			}

			if ( $this->is_knowledge_wizard() ) {
				$data['formNonce']   = wp_create_nonce( 'forminator_save_quiz' );
				$data['application'] = 'knowledge';

				// Load stored record.
				if ( is_object( $model ) ) {

					$settings = apply_filters( 'forminator_quiz_settings', $model->settings, $model, $data, $this );

					$has_lead   = isset( $settings['hasLeads'] ) ? $settings['hasLeads'] : false;
					$lead_id    = isset( $settings['leadsId'] ) ? $settings['leadsId'] : 0;
					$form_model = Forminator_Base_Form_Model::get_model( $lead_id );
					if ( is_object( $form_model ) && $has_lead ) {
						$wrappers      = $form_model->get_fields_grouped();
						$lead_settings = $form_model->settings;
					}

					$notifications = $this->get_quiz_notifications( $model );
					$notifications = apply_filters( 'forminator_quiz_notifications', $notifications, $model, $data, $this );

					$data['currentForm'] = array(
						'results'       => array(),
						'questions'     => $model->questions,
						'settings'      => array_merge(
							$settings,
							array(
								'formName'      => forminator_get_name_from_model( $model ),
								'form_id'       => $model->id,
								'form_status'   => $model->status,
								'quiz_title'    => $model->name,
								'version'       => FORMINATOR_VERSION,
								'wrappers'      => $wrappers,
								'lead_settings' => $lead_settings,
							)
						),
						'notifications' => $notifications,
					);
				} else {
					$data['currentForm'] = array();
				}
			}

			if ( $this->is_nowrong_wizard() ) {
				$data['formNonce']   = wp_create_nonce( 'forminator_save_quiz' );
				$data['application'] = 'nowrong';

				// Load stored record.
				if ( is_object( $model ) ) {
					unset( $model->settings['priority_order'] );
					$settings = apply_filters( 'forminator_quiz_settings', $model->settings, $model, $data, $this );

					$has_lead   = isset( $settings['hasLeads'] ) ? $settings['hasLeads'] : false;
					$lead_id    = isset( $settings['leadsId'] ) ? $settings['leadsId'] : 0;
					$form_model = Forminator_Base_Form_Model::get_model( $lead_id );
					if ( is_object( $form_model ) && $has_lead ) {
						$wrappers      = $form_model->get_fields_grouped();
						$lead_settings = $form_model->settings;
					}

					$notifications = $this->get_quiz_notifications( $model );
					$notifications = apply_filters( 'forminator_quiz_notifications', $notifications, $model, $data, $this );

					$data['currentForm'] = array(
						'results'       => $model->getResults(),
						'questions'     => $model->questions,
						'settings'      => array_merge(
							$settings,
							array(
								'formName'      => forminator_get_name_from_model( $model ),
								'form_id'       => $model->id,
								'form_status'   => $model->status,
								'quiz_title'    => $model->name,
								'version'       => FORMINATOR_VERSION,
								'wrappers'      => $wrappers,
								'lead_settings' => $lead_settings,
							)
						),
						'notifications' => $notifications,
					);
				} else {
					$data['currentForm'] = array();
				}
			}
		}

		$data['modules']['quizzes'] = array(
			'nowrong_url'   => menu_page_url( $this->page_edit_nowrong, false ),
			'knowledge_url' => menu_page_url( $this->page_edit_knowledge, false ),
			'form_list_url' => menu_page_url( $this->page, false ),
			'preview_nonce' => wp_create_nonce( 'forminator_popup_preview_quiz' ),
		);

		return apply_filters( 'forminator_quiz_admin_data', $data, $model, $this );
	}

	/**
	 * Common quiz default data
	 *
	 * @return array
	 */
	private static function common_default_data() {
		return array(
			// Pagination.
			'page-indicator-color'                    => '#888888',
			'start-button-background-static'          => '#097BAA',
			'start-button-background-hover'           => '#086A91',
			'start-button-background-active'          => '#086A91',
			'start-button-color-static'               => '#FFFFFF',
			'start-button-color-hover'                => '#FFFFFF',
			'start-button-color-active'               => '#FFFFFF',
			'start-button-font-family'                => 'Roboto',
			'start-button-font-size'                  => '14',
			'start-button-font-weight'                => '500',
			'navigation-button-background-static'     => '#067963',
			'navigation-button-background-hover'      => '#05614F',
			'navigation-button-background-active'     => '#05614F',
			'navigation-button-color-static'          => '#FFFFFF',
			'navigation-button-color-hover'           => '#FFFFFF',
			'navigation-button-color-active'          => '#FFFFFF',
			'navigation-button-font-family'           => 'Roboto',
			'navigation-button-font-size'             => '14',
			'navigation-button-font-weight'           => '500',
			'back-questions-button-background-static' => '#067963',
			'back-questions-button-background-hover'  => '#05614F',
			'back-questions-button-background-active' => '#05614F',
			'back-questions-button-color-static'      => '#FFFFFF',
			'back-questions-button-color-hover'       => '#FFFFFF',
			'back-questions-button-color-active'      => '#FFFFFF',
			'page-indicator-font-family'              => 'Roboto',
			'page-indicator-font-size'                => '13',
			'page-indicator-font-weight'              => '400',
			'store_submissions'                       => '1',
		);
	}

	/**
	 * Knowledge quiz default data
	 *
	 * @param string $name Form name.
	 * @param bool   $has_leads Has lead.
	 *
	 * @since 1.14
	 *
	 * @return array
	 */
	public static function knowledge_default_data( $name, $has_leads ) {
		return array_merge(
			self::common_default_data(),
			array(
				'hasLeads'                                 => $has_leads,
				'formName'                                 => $name,
				'version'                                  => FORMINATOR_VERSION,
				'admin-email-recipients'                   => array(
					get_option( 'admin_email' ),
				),
				'admin-email-title'                        => esc_html__( 'New Quiz Submission for {quiz_name}', 'forminator' ),
				'admin-email-editor'                       => sprintf(
					'%1$s <br/><br/>{quiz_answer}<br/><br/>%2$s <br/>{quiz_result} <br/>---<br/> %3$s',
					esc_html__( 'You have a new quiz submission:', 'forminator' ),
					esc_html__( 'Quiz results:', 'forminator' ),
					esc_html__( 'This message was sent from {site_url}.', 'forminator' )
				),
				'results_behav'                            => 'after',
				'visual_style'                             => 'list',
				'forminator-quiz-theme'                    => 'default',
				'msg_correct'                              => esc_html__( 'Correct! It was %UserAnswer%.', 'forminator' ),
				'msg_incorrect'                            => esc_html__( 'Wrong! It was %CorrectAnswer%, sorry...', 'forminator' ),
				'msg_count'                                => esc_html__( 'You got %YourNum%/%Total% correct!', 'forminator' ),
				// KNOWLEDGE title.
				'knowledge-title-color'                    => '#333333',
				'knowledge-title-font-family'              => 'Roboto',
				'knowledge-title-font-size'                => '42',
				'knowledge-title-font-weight'              => '500',
				// KNOWLEDGE description.
				'knowledge-description-color'              => '#8C8C8C',
				'knowledge-description-font-family'        => 'Roboto',
				'knowledge-description-font-size'          => '20',
				'knowledge-description-font-weight'        => '400',
				// KNOWLEDGE question.
				'knowledge-question-color'                 => '#333333',
				'knowledge-question-font-family'           => 'Roboto',
				'knowledge-question-font-size'             => '24',
				'knowledge-question-font-weight'           => '700',
				'knowledge-question-description-color'     => '#8C8C8C',
				'question-description-font-family'         => 'Roboto',
				'question-description-font-size'           => '20',
				'question-description-font-weight'         => '400',
				// KNOWLEDGE answer.
				'knowledge-answer-background-static'       => '#FAFAFA',
				'knowledge-answer-background-hover'        => '#F3FBFE',
				'knowledge-answer-background-active'       => '#F3FBFE',
				'knowledge-aright-background'              => '#F4FCF2',
				'knowledge-awrong-background'              => '#FDF2F2',
				'knowledge-answer-border-static'           => '#EBEDEB',
				'knowledge-answer-border-hover'            => '#097BAA',
				'knowledge-answer-border-active'           => '#097BAA',
				'knowledge-aright-border'                  => '#0BC30B',
				'knowledge-awrong-border'                  => '#DA0000',
				'knowledge-answer-color-static'            => '#888888',
				'knowledge-answer-color-active'            => '#333333',
				'knowledge-aright-color'                   => '#0BC30B',
				'knowledge-awrong-color'                   => '#DA0000',
				'knowledge-answer-font-size'               => '14',
				'knowledge-answer-font-family'             => 'Roboto',
				'knowledge-answer-font-weight'             => '500',
				'knowledge-answer-check-border-static'     => '#BFBFBF',
				'knowledge-answer-check-border-active'     => '#097BAA',
				'knowledge-answer-check-border-correct'    => '#0BC30B',
				'knowledge-answer-check-border-incorrect'  => '#DA0000',
				'knowledge-answer-check-background-static' => '#FFFFFF',
				'knowledge-answer-check-background-active' => '#097BAA',
				'knowledge-answer-check-background-correct' => '#0BC30B',
				'knowledge-answer-check-background-incorrect' => '#DA0000',
				'knowledge-phrasing-color'                 => '#4D4D4D',
				'knowledge-phrasing-font-size'             => '16',
				'knowledge-phrasing-font-family'           => 'Roboto',
				'knowledge-phrasing-font-weight'           => '700',
				// KNOWLEDGE button.
				'knowledge-submit-background-static'       => '#097BAA',
				'knowledge-submit-background-hover'        => '#086A91',
				'knowledge-submit-background-active'       => '#086A91',
				'knowledge-submit-color-static'            => '#FFFFFF',
				'knowledge-submit-color-hover'             => '#FFFFFF',
				'knowledge-submit-color-active'            => '#FFFFFF',
				'knowledge-submit-font-family'             => 'Roboto',
				'knowledge-submit-font-size'               => '14',
				'knowledge-submit-font-weight'             => '500',
				// KNOWLEDGE summary.
				'knowledge-summary-color'                  => '#333333',
				'knowledge-summary-font-family'            => 'Roboto',
				'knowledge-summary-font-size'              => '40',
				'knowledge-summary-font-weight'            => '400',
				'knowledge-result-retake-font-family'      => 'Roboto',
				'knowledge-result-retake-font-size'        => '13',
				'knowledge-result-retake-font-weight'      => '500',
				'knowledge-result-retake-background-static' => '#222222',
				'knowledge-result-retake-background-hover' => '#222222',
				'knowledge-result-retake-background-active' => '#222222',
				// KNOWLEDGE social.
				'enable-share'                             => 'on',
				'knowledge-sshare-color'                   => '#4D4D4D',
				'knowledge-sshare-font-family'             => 'Roboto',
				'knowledge-sshare-font-size'               => '20',
				'knowledge-social-facebook'                => '#0084BF',
				'knowledge-social-twitter'                 => '#1DA1F2',
				'knowledge-social-google'                  => '#DB4437',
				'forminator-knowledge-social-linkedin'     => '#0084BF',
				'knowledge-social-size'                    => '36',
				// KNOWLEDGE Radio and Checkbox Image Size.
				'field-image-size'                         => 'custom',
			)
		);
	}

	/**
	 * No wrong quiz default data
	 *
	 * @since 1.14
	 *
	 * @param string $name Form name.
	 * @param bool   $has_leads Has lead.
	 *
	 * @return array
	 */
	public static function nowrong_default_data( $name, $has_leads ) {
		return array_merge(
			self::common_default_data(),
			array(
				'hasLeads'                                => $has_leads,
				'formName'                                => $name,
				'version'                                 => FORMINATOR_VERSION,
				'admin-email-recipients'                  => array(
					get_option( 'admin_email' ),
				),
				'results_behav'                           => 'after',
				'visual_style'                            => 'list',
				'forminator-quiz-theme'                   => 'default',
				'msg_correct'                             => esc_html__( 'Correct! It was %UserAnswer%.', 'forminator' ),
				'msg_incorrect'                           => esc_html__( 'Wrong! It was %CorrectAnswer%, sorry...', 'forminator' ),
				'msg_count'                               => esc_html__( 'You got %YourNum%/%Total% correct!', 'forminator' ),
				// NOWRONG title.
				'nowrong-title-settings'                  => false,
				'nowrong-title-color'                     => '#333333',
				'nowrong-title-font-family'               => 'Roboto',
				'nowrong-title-font-size'                 => '42',
				'nowrong-title-font-weight'               => '500',
				// NOWRONG description.
				'nowrong-description-settings'            => false,
				'nowrong-description-color'               => '#8C8C8C',
				'nowrong-description-font-family'         => 'Roboto',
				'nowrong-description-font-size'           => '20',
				'nowrong-description-font-weight'         => '400',
				// NOWRONG image.
				'nowrong-image-settings'                  => false,
				'nowrong-image-border-color'              => '#000000',
				'nowrong-image-border-width'              => '0',
				'nowrong-image-border-style'              => 'solid',
				// NOWRONG question.
				'nowrong-question-settings'               => false,
				'nowrong-question-font-size'              => '24',
				'nowrong-question-font-family'            => 'Roboto',
				'nowrong-question-font-weight'            => '700',
				'nowrong-question-description-color'      => '#8C8C8C',
				'question-description-font-family'        => 'Roboto',
				'question-description-font-size'          => '20',
				'question-description-font-weight'        => '400',
				// NOWRONG answer.
				'nowrong-answer-settings'                 => false,
				'nowrong-answer-border-static'            => '#EBEDEB',
				'nowrong-answer-border-hover'             => '#097BAA',
				'nowrong-answer-border-active'            => '#097BAA',
				'nowrong-answer-background-static'        => '#FAFAFA',
				'nowrong-answer-background-hover'         => '#F3FBFE',
				'nowrong-answer-background-active'        => '#F3FBFE',
				'nowrong-answer-chkbo-static'             => '#BFBFBF',
				'nowrong-answer-chkbo-active'             => '#097BAA',
				'nowrong-answer-color-static'             => '#888888',
				'nowrong-answer-color-active'             => '#333333',
				'nowrong-answer-font-size'                => '14',
				'nowrong-answer-font-family'              => 'Roboto',
				'nowrong-answer-font-weight'              => '500',
				// NOWRONG submit.
				'nowrong-submit-background-static'        => '#097BAA',
				'nowrong-submit-background-hover'         => '#086A91',
				'nowrong-submit-background-active'        => '#086A91',
				'nowrong-submit-color-static'             => '#FFFFFF',
				'nowrong-submit-color-hover'              => '#FFFFFF',
				'nowrong-submit-color-active'             => '#FFFFFF',
				'nowrong-submit-font-family'              => 'Roboto',
				'nowrong-submit-font-size'                => '14',
				'nowrong-submit-font-weight'              => '500',
				// NOWRONG result.
				'nowrong-result-background-main'          => '#FAFAFA',
				'nowrong-result-background-header'        => '#FAFAFA',
				'nowrong-result-border-color'             => '#097BAA',
				'nowrong-result-quiz-color'               => '#888888',
				'nowrong-result-quiz-font-family'         => 'Roboto',
				'nowrong-result-quiz-font-size'           => '15',
				'nowrong-result-quiz-font-weight'         => '500',
				'nowrong-result-retake-font-family'       => 'Roboto',
				'nowrong-result-retake-font-size'         => '13',
				'nowrong-result-retake-font-weight'       => '500',
				'nowrong-result-retake-background-static' => '#222222',
				'nowrong-result-retake-background-hover'  => '#222222',
				'nowrong-result-retake-background-active' => '#222222',
				'nowrong-result-background-body'          => '#EBEDEB',
				'nowrong-result-title-color'              => '#333333',
				'nowrong-result-title-font-family'        => 'Roboto',
				'nowrong-result-title-font-size'          => '15',
				'nowrong-result-title-font-weight'        => '500',
				'nowrong-result-description-color'        => '#4D4D4D',
				'nowrong-result-description-font-family'  => 'Roboto',
				'nowrong-result-description-font-size'    => '13',
				'nowrong-result-description-font-weight'  => '400',
				// NOWRONG social.
				'enable-share'                            => 'on',
			)
		);
	}

	/**
	 * Localize modules strings
	 *
	 * @since 1.0
	 *
	 * @param array $data Data.
	 *
	 * @return mixed
	 */
	public function add_l10n_strings( $data ) {
		$data['quizzes'] = array(
			'quizzes'                      => esc_html__( 'Quizzes', 'forminator' ),
			'popup_label'                  => esc_html__( 'Choose Quiz Type', 'forminator' ),
			'results'                      => esc_html__( 'Results', 'forminator' ),
			'questions'                    => esc_html__( 'Questions', 'forminator' ),
			'details'                      => esc_html__( 'Details', 'forminator' ),
			'settings'                     => esc_html__( 'Settings', 'forminator' ),
			'appearance'                   => esc_html__( 'Appearance', 'forminator' ),
			'preview'                      => esc_html__( 'Preview', 'forminator' ),
			'preview_quiz'                 => esc_html__( 'Preview Quiz', 'forminator' ),
			'list'                         => esc_html__( 'List', 'forminator' ),
			'grid'                         => esc_html__( 'Grid', 'forminator' ),
			'visual_style'                 => esc_html__( 'Visual style', 'forminator' ),
			'quiz_title'                   => esc_html__( 'Quiz Title', 'forminator' ),
			'quiz_title_desc'              => esc_html__( 'Further customize the appearance for quiz title. It appears as result\'s header.', 'forminator' ),
			'title'                        => esc_html__( 'Title', 'forminator' ),
			'title_desc'                   => esc_html__( 'Further customize appearance for quiz title.', 'forminator' ),
			'image_desc'                   => esc_html__( 'Further customize appearance for quiz featured image.', 'forminator' ),
			'enable_styles'                => esc_html__( 'Enable custom styles', 'forminator' ),
			'desc_desc'                    => esc_html__( 'Further customize appearance for quiz description / intro.', 'forminator' ),
			'description'                  => esc_html__( 'Description / Intro', 'forminator' ),
			'feat_image'                   => esc_html__( 'Featured image', 'forminator' ),
			'font_color'                   => esc_html__( 'Font color', 'forminator' ),
			'browse'                       => esc_html__( 'Browse', 'forminator' ),
			'clear'                        => esc_html__( 'Clear', 'forminator' ),
			'results_behav'                => esc_html__( 'Results behavior', 'forminator' ),
			'rb_description'               => esc_html__( 'Pick if you want to reveal the correct answer as user finishes question, or only after the whole quiz is completed.', 'forminator' ),
			'reveal'                       => esc_html__( 'When to reveal correct answer', 'forminator' ),
			'after'                        => esc_html__( 'After user picks answer', 'forminator' ),
			'before'                       => esc_html__( 'At the end of whole quiz', 'forminator' ),
			'phrasing'                     => esc_html__( 'Answer phrasing', 'forminator' ),
			'phrasing_desc_alt'            => esc_html__( 'Further customize appearance for answer message.', 'forminator' ),
			'msg_correct'                  => esc_html__( 'Correct answer message', 'forminator' ),
			'msg_incorrect'                => esc_html__( 'Incorrect answer message', 'forminator' ),
			'msg_count'                    => esc_html__( 'Final count message', 'forminator' ),
			'msg_count_info'               => esc_html__( 'You can now add some html content here to personalize even more text displayed as Final Count Message. Try it now!', 'forminator' ),
			'share'                        => esc_html__( 'Share on social media', 'forminator' ),
			'order'                        => esc_html__( 'Results priority order', 'forminator' ),
			'order_label'                  => esc_html__( 'Pick priority for results', 'forminator' ),
			'order_alt'                    => esc_html__( 'Quizzes can have even number of scores for 2 or more results, in those scenarios, this order will help determine the result.', 'forminator' ),
			'questions_title'              => esc_html__( 'Questions', 'forminator' ),
			'question_desc'                => esc_html__( 'Further customize appearance for quiz questions.', 'forminator' ),
			'result_title'                 => esc_html__( 'Result title', 'forminator' ),
			'result_description'           => esc_html__( 'Result description', 'forminator' ),
			'result_description_desc'      => esc_html__( 'Further customize the appearance for result description typography.', 'forminator' ),
			'result_title_desc'            => esc_html__( 'Further customize the appearance for result title typography.', 'forminator' ),
			'retake_button'                => esc_html__( 'Retake button', 'forminator' ),
			'retake_button_desc'           => esc_html__( 'Further customize the appearance for retake quiz button.', 'forminator' ),
			'validate_form_name'           => esc_html__( 'Form name cannot be empty! Please pick a name for your quiz.', 'forminator' ),
			'validate_form_question'       => esc_html__( 'Quiz question cannot be empty! Please add questions for your quiz.', 'forminator' ),
			'validate_form_answers'        => esc_html__( 'Quiz answers cannot be empty! Please add some questions.', 'forminator' ),
			'validate_form_answers_result' => esc_html__( 'Result answer cannot be empty! Please select a result.', 'forminator' ),
			'validate_form_correct_answer' => esc_html__( 'This question needs a correct answer. Please, select one before saving or proceeding to next step.', 'forminator' ),
			'validate_form_no_answer'      => esc_html__( 'Please add an answer for this question.', 'forminator' ),
			'answer'                       => esc_html__( 'Answers', 'forminator' ),
			'no_answer'                    => esc_html__( 'You don\'t have any answer for this question yet.', 'forminator' ),
			'answer_desc'                  => esc_html__( 'Further customize appearance for quiz answers.', 'forminator' ),
			'back'                         => esc_html__( 'Back', 'forminator' ),
			'cancel'                       => esc_html__( 'Cancel', 'forminator' ),
			'continue'                     => esc_html__( 'Continue', 'forminator' ),
			'correct_answer'               => esc_html__( 'Correct answer', 'forminator' ),
			'correct_answer_desc'          => esc_html__( 'Customize appearance for correct answers.', 'forminator' ),
			'finish'                       => esc_html__( 'Finish', 'forminator' ),
			'submit'                       => esc_html__( 'Submit', 'forminator' ),
			'submit_desc'                  => esc_html__( 'Further customize appearance for quiz submit button.', 'forminator' ),
			'main_styles'                  => esc_html__( 'Main styles', 'forminator' ),
			'border'                       => esc_html__( 'Border', 'forminator' ),
			'border_desc'                  => esc_html__( 'Further customize border for result\'s main container.', 'forminator' ),
			'padding'                      => esc_html__( 'Padding', 'forminator' ),
			'background'                   => esc_html__( 'Background', 'forminator' ),
			'background_desc'              => esc_html__(
				'The Results box has three different backgrounds: main container, header background (where quiz title and reload button are placed), and content background (where result title and description are placed). Here you can customize the three of them.',
				'forminator'
			),
			'bg_main'                      => esc_html__( 'Main BG', 'forminator' ),
			'bg_header'                    => esc_html__( 'Header BG', 'forminator' ),
			'bg_content'                   => esc_html__( 'Content BG', 'forminator' ),
			'color'                        => esc_html__( 'Color', 'forminator' ),
			'result_appearance'            => esc_html__( 'Result\'s Box', 'forminator' ),
			'margin'                       => esc_html__( 'Margin', 'forminator' ),
			'summary'                      => esc_html__( 'Summary', 'forminator' ),
			'summary_desc'                 => esc_html__( 'Further customize appearance for quiz final count message', 'forminator' ),
			'sshare'                       => esc_html__( 'Sharing text', 'forminator' ),
			'sshare_desc'                  => esc_html__( 'Further customize appearance for share on social media text', 'forminator' ),
			'social'                       => esc_html__( 'Social icons', 'forminator' ),
			'social_desc'                  => esc_html__( 'Further customize appearance for social media icons', 'forminator' ),
			'wrong_answer'                 => esc_html__( 'Wrong answer', 'forminator' ),
			'wrong_answer_desc'            => esc_html__( 'Customize appearance for wrong answers.', 'forminator' ),
			'facebook'                     => esc_html__( 'Facebook', 'forminator' ),
			'twitter'                      => esc_html__( 'X', 'forminator' ),
			'google'                       => esc_html__( 'Google', 'forminator' ),
			'linkedin'                     => esc_html__( 'LinkedIn', 'forminator' ),
			'title_styles'                 => esc_html__( 'Title Appearance', 'forminator' ),
			'enable'                       => esc_html__( 'Enable', 'forminator' ),
			'checkbox_styles'              => esc_html__( 'Checkbox styles', 'forminator' ),
			'main'                         => esc_html__( 'Main', 'forminator' ),
			'header'                       => esc_html__( 'Header', 'forminator' ),
			'content'                      => esc_html__( 'Content', 'forminator' ),
			'quiz_design'                  => esc_html__( 'Quiz design', 'forminator' ),
			'quiz_design_description'      => esc_html__( 'Choose a pre-made style for your quiz and further customize it\'s appearance.', 'forminator' ),
			'customize_quiz_colors'        => esc_html__( 'Customize quiz colors', 'forminator' ),
			'visual_style_description'     => esc_html__( 'There are two ways for displaying your quiz answers: grid or list.', 'forminator' ),
		);

		$data['quiz_details'] = array(
			'name'                => esc_html__( 'Quiz Name', 'forminator' ),
			'name_details'        => esc_html__( 'This won\'t be displayed on your quiz, but will help you to identify it.', 'forminator' ),
			'name_validate'       => esc_html__( 'Quiz name cannot be empty! Please, pick a name for your quiz.', 'forminator' ),
			'title'               => esc_html__( 'Quiz Title', 'forminator' ),
			'title_details'       => esc_html__( 'This is the main title of your quiz and will be displayed on front.', 'forminator' ),
			'image'               => esc_html__( 'Featured image', 'forminator' ),
			'image_details'       => esc_html__( 'Add some nice main image to your quiz.', 'forminator' ),
			'description'         => esc_html__( 'Description', 'forminator' ),
			'description_details' => esc_html__( 'Give more information related to your quiz. This content will be displayed on front.', 'forminator' ),
		);

		$data['quiz_appearance'] = array(
			'answer'               => esc_html__( 'Answer', 'forminator' ),
			'checkbox'             => esc_html__( 'Checkbox', 'forminator' ),
			'container_border'     => esc_html__( 'Container border', 'forminator' ),
			'container_background' => esc_html__( 'Container background', 'forminator' ),
			'customize_main'       => esc_html__( 'Customize main colors', 'forminator' ),
			'customize_question'   => esc_html__( 'Customize question colors', 'forminator' ),
			'customize_answer'     => esc_html__( 'Customize answer colors', 'forminator' ),
			'customize_result'     => esc_html__( 'Customize result\'s box colors', 'forminator' ),
			'customize_submit'     => esc_html__( 'Customize submit button colors', 'forminator' ),
			'main_container'       => esc_html__( 'Main container', 'forminator' ),
			'main_border'          => esc_html__( 'Main border', 'forminator' ),
			'main_styles'          => esc_html__( 'Main styles', 'forminator' ),
			'header_styles'        => esc_html__( 'Header styles', 'forminator' ),
			'content_styles'       => esc_html__( 'Content styles', 'forminator' ),
			'quiz_title'           => esc_html__( 'Quiz Title', 'forminator' ),
			'retake_button'        => esc_html__( 'Retake button', 'forminator' ),
			'result_title'         => esc_html__( 'Result title', 'forminator' ),
			'quiz_description'     => esc_html__( 'Quiz description', 'forminator' ),
			'result_description'   => esc_html__( 'Result description', 'forminator' ),
			'quiz_image'           => esc_html__( 'Quiz image', 'forminator' ),
			'question'             => esc_html__( 'Question', 'forminator' ),
			'answer_message'       => esc_html__( 'Answer message', 'forminator' ),
			'submit_button'        => esc_html__( 'Submit Button', 'forminator' ),
			'quiz_result'          => esc_html__( 'Quiz result', 'forminator' ),
			'social_share'         => esc_html__( 'Social share', 'forminator' ),
			'customize_colors'     => esc_html__( 'Customize colors', 'forminator' ),
			'customize_typography' => esc_html__( 'Customize typography', 'forminator' ),
			'checkbox_border'      => esc_html__( 'Checkbox border', 'forminator' ),
			'checkbox_background'  => esc_html__( 'Checkbox background', 'forminator' ),
			'checkbox_icon'        => esc_html__( 'Checkbox icon', 'forminator' ),
			'quiz_title_notice'    => esc_html__( 'The quiz title appears on result\'s header.', 'forminator' ),
		);

		return $data;
	}

	/**
	 * Create quiz module
	 *
	 * @since 1.14
	 */
	public function create_module() {
		if ( ( ! $this->is_knowledge_wizard() && ! $this->is_nowrong_wizard() ) || self::is_edit() ) {
			return;
		}

		$nonce = Forminator_Core::sanitize_text_field( 'create_nonce' );
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'forminator_create_module' ) ) {
			return;
		}

		// Get module name.
		$name = Forminator_Core::sanitize_text_field( 'name' );

		// Get if quiz has leads.
		$has_leads = Forminator_Core::sanitize_text_field( 'leads', false );

		if ( $this->is_knowledge_wizard() ) {
			$quiz_type = 'knowledge';
		} else {
			$quiz_type = 'nowrong';
		}

		$status = Forminator_Quiz_Model::STATUS_DRAFT;

		$template            = new stdClass();
		$template->quiz_type = $quiz_type;
		$template->has_leads = $has_leads;

		$pagination = filter_input( INPUT_GET, 'pagination' );
		if ( ! empty( $pagination ) ) {
			$template->has_pagination = true;
		}

		$id = self::create( $name, $status, $template );

		if ( is_wp_error( $id ) ) {
			return;
		}

		$wizard_url = admin_url( 'admin.php?page=forminator-' . $quiz_type . '-wizard&create-status=success&id=' . $id );

		wp_safe_redirect( $wizard_url );
	}

	/**
	 * Create quiz
	 *
	 * @param string $name Name.
	 * @param string $status Status.
	 * @param object $template Template.
	 * @return int post ID
	 */
	public static function create( $name, $status, $template ) {
		$has_leads = ! empty( $template->has_leads );
		$quiz_type = ! empty( $template->quiz_type ) ? $template->quiz_type : '';
		// If we have leads, create leads form automatically.
		if ( $has_leads ) {
			$leads_id = self::create_leads_form( $name );
			if ( is_wp_error( $leads_id ) ) {
				return $leads_id;
			}
		}

		if ( 'knowledge' === $quiz_type ) {
			$settings = self::knowledge_default_data( $name, $has_leads );
		} else {
			$settings = self::nowrong_default_data( $name, $has_leads );
		}

		if ( $has_leads && ! empty( $leads_id ) ) {
			$settings['leadsId'] = $leads_id;
		}

		$model            = new Forminator_Quiz_Model();
		$model->quiz_type = $quiz_type;
		$model->results   = ! empty( $template->results )
			? $template->results : array();
		$model->questions = ! empty( $template->questions )
			? $template->questions : array();
		$model->name      = sanitize_title( $name );
		$model->status    = $status;

		if ( ! empty( $template->settings ) ) {
			$settings = array_merge( $settings, $template->settings );
		}
		if ( ! empty( $template->has_pagination ) ) {
			$settings['pagination'] = 'true';
		}
		$model->settings = self::validate_settings( $settings );

		if ( $has_leads ) {
			if ( 'knowledge' === $quiz_type ) {
				$email_body = sprintf(
					'%1$s {name-1},<br/><br/>%2$s<br/><br/><b>{quiz_name}</b><br/>{quiz_answer}<br/><br/>%3$s<br/><br/>---<br/><br/>%4$s',
					esc_html__( 'Hey', 'forminator' ),
					esc_html__( 'Thanks for participating in {quiz_name} quiz.', 'forminator' ),
					esc_html__( 'Want to retake the quiz? Follow this link {embed_url}', 'forminator' ),
					esc_html__( 'This message was sent from {site_url}.', 'forminator' )
				);
			} else {
				$email_body = sprintf(
					'%1$s {name-1},<br/><br/>%2$s<br/><br/>%3$s<br/>{quiz_answer}<br/><br/>%4$s<br/><br/>---<br/><br/>%5$s',
					esc_html__( 'Hey', 'forminator' ),
					esc_html__( 'Thanks for participating in our {quiz_name} quiz.', 'forminator' ),
					esc_html__( 'You scored {quiz_result} on this quiz and following are your answers:', 'forminator' ),
					esc_html__( 'Want to retake the quiz? Follow this link {embed_url}', 'forminator' ),
					esc_html__( 'This message was sent from {site_url}.', 'forminator' )
				);
			}
			$model->notifications = array(
				array(
					'slug'             => 'notification-1234-4567',
					'label'            => esc_html__( 'Admin Notification', 'forminator' ),
					'email-recipients' => 'default',
					'recipients'       => get_option( 'admin_email' ),
					'email-subject'    => esc_html__( 'New Quiz Submission #{submission_id} for {quiz_name}', 'forminator' ),
					'email-editor'     => sprintf(
						'%1$s <br/><br/>%2$s<br/>{all_fields}<br/><br/>---<br/><br/>%3$s <br/>{quiz_result} <br/>{quiz_answer}<br/><br/>%4$s',
						esc_html__( 'You have a new {quiz_type} quiz submission:', 'forminator' ),
						esc_html__( 'Lead details:', 'forminator' ),
						esc_html__( 'Quiz details:', 'forminator' ),
						esc_html__( 'This message was sent from {site_url}.', 'forminator' )
					),
				),
				array(
					'slug'             => 'notification-4567-8765',
					'label'            => __( "Participant's Notification", 'forminator' ),
					'email-recipients' => 'default',
					'recipients'       => '{email-1}',
					'email-subject'    => esc_html__( 'Your quiz result', 'forminator' ),
					'email-editor'     => $email_body,
				),
			);
		}

		// Save data.
		$id = $model->save();

		return $id;
	}

	/**
	 * Update quiz
	 *
	 * @param string $id Module ID.
	 * @param string $title Name.
	 * @param string $status Status.
	 * @param object $template Template.
	 * @return WP_Error post ID
	 */
	public static function update( $id, $title, $status, $template ) {
		if ( is_null( $id ) || $id <= 0 ) {
			$form_model = new Forminator_Quiz_Model();
			$action     = 'create';

			if ( empty( $status ) ) {
				$status = Forminator_Poll_Model::STATUS_PUBLISH;
			}
		} else {
			$form_model = Forminator_Base_Form_Model::get_model( $id );
			$action     = 'update';

			if ( ! is_object( $form_model ) ) {
				return new WP_Error( esc_html__( 'Quiz model doesn\'t exist', 'forminator' ) );
			}

			if ( empty( $status ) ) {
				$status = $form_model->status;
			}

			// we need to empty fields cause we will send new data.
			$form_model->clear_fields();
		}

		// Detect action.
		$form_model->quiz_type = 'knowledge';
		if ( ! empty( $template->type ) && 'forminator_save_quiz_nowrong' === $template->type ) {
			$form_model->quiz_type = 'nowrong';
		}

		$results = array();
		// Check if results exist.
		if ( isset( $template->results ) && is_array( $template->results ) ) {
			$results = forminator_sanitize_array_field( $template->results );
			foreach ( $template->results as $key => $result ) {
				$description = '';
				if ( isset( $result['description'] ) ) {
					$description = wp_kses_post( $result['description'] );
				}
				$results[ $key ]['description'] = $description;

				// Check if order exists.
				if ( ! isset( $result['order'] ) ) {
					$results[ $key ]['order'] = $key;
				}
			}

			$form_model->results = $results;
		}

		$questions = array();
		// Check if answers exist.
		if ( isset( $template->questions ) ) {
			$questions = $template->questions;

			// Check if questions exist.
			foreach ( $questions as &$question ) {
				$question['type'] = $form_model->quiz_type;
				if ( ! isset( $question['slug'] ) || empty( $question['slug'] ) ) {
					$question['slug'] = uniqid();
				}
			}
		}

		$form_model->name = sanitize_title( $title );

		// Handle quiz questions.
		$form_model->questions = $questions;

		$settings = isset( $template->settings ) ? $template->settings : array();

		$notifications = array();
		if ( isset( $template->notifications ) ) {
			$notifications = forminator_sanitize_array_field( $template->notifications );

			$count = 0;
			foreach ( $notifications as $notification ) {
				if ( isset( $notification['email-editor'] ) ) {
					$notifications[ $count ]['email-editor'] = wp_kses_post( $template->notifications[ $count ]['email-editor'] );
				}

				++$count;
			}
		}

		$form_model->settings        = self::validate_settings( $settings );
		$settings['previous_status'] = get_post_status( $id );

		$form_model->notifications = $notifications;
		$form_model->status        = $status;

		// Save data.
		$id = $form_model->save();

		if ( is_wp_error( $id ) ) {
			return $id;
		}
		// Remove temporary settings.
		Forminator_Base_Form_Model::remove_temp_settings( $id );

		$type = $form_model->quiz_type;

		/**
		 * Action called after quiz saved to database
		 *
		 * @since 1.11
		 *
		 * @param int    $id - quiz id.
		 * @param string $type - quiz type.
		 * @param string $status - quiz status.
		 * @param array  $questions - quiz questions.
		 * @param array  $results - quiz results.
		 * @param array  $settings - quiz settings.
		 */
		do_action( 'forminator_quiz_action_' . $action, $id, $type, $status, $questions, $results, $settings );

		Forminator_Render_Form::regenerate_css_file( $id );

		return $id;
	}

	/**
	 * Create leads form
	 *
	 * @since 1.14
	 *
	 * @param string $name Form name.
	 *
	 * @return mixed
	 */
	public static function create_leads_form( $name ) {
		$model = new Forminator_Form_Model();

		$name = $name . esc_html__( ' - Leads form', 'forminator' );

		$model->name          = $name;
		$model->notifications = array();

		$template = new Forminator_Template_Leads();

		// Setup template fields.
		foreach ( $template->fields() as $row ) {
			foreach ( $row['fields'] as $f ) {
				$field          = new Forminator_Form_Field_Model();
				$field->form_id = $row['wrapper_id'];
				$field->slug    = $f['element_id'];
				unset( $f['element_id'] );
				$field->import( $f );
				$model->add_field( $field );
			}
		}

		$settings = $template->settings();

		// form name & version.
		$settings['formName'] = $name;
		$settings['version']  = FORMINATOR_VERSION;

		// settings.
		$model->settings = $settings;

		// status.
		$model->status = Forminator_Form_Model::STATUS_PUBLISH;

		// Save data.
		$id = $model->save();

		return $id;
	}

	/**
	 * Return quiz notifications
	 *
	 * @since 1.1
	 *
	 * @param Forminator_Quiz_Model|null $quiz Quiz model.
	 *
	 * @return mixed
	 */
	public function get_quiz_notifications( $quiz ) {
		if ( ! isset( $quiz ) || ! isset( $quiz->notifications ) ) {
			return array(
				array(
					'slug'             => 'notification-1234-4567',
					'label'            => esc_html__( 'Admin Email', 'forminator' ),
					'email-recipients' => 'default',
					'recipients'       => get_option( 'admin_email' ),
					'email-subject'    => esc_html__( 'New Quiz Submission for {quiz_name}', 'forminator' ),
					'email-editor'     => sprintf(
						'%1$s <br/><br/>{quiz_answer}<br/><br/>%2$s <br/>{quiz_result} <br/>---<br/> %3$s',
						esc_html__( 'You have a new quiz submission:', 'forminator' ),
						esc_html__( 'Quiz results:', 'forminator' ),
						esc_html__( 'This message was sent from {site_url}.', 'forminator' )
					),
				),
			);
		}

		return $quiz->notifications;
	}
}
