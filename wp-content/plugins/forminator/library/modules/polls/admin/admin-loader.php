<?php
/**
 * The Forminator_Poll_Admin class.
 *
 * @package Forminator
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_Poll_Admin
 *
 * @property Forminator_Polls module
 * @since 1.0
 */
class Forminator_Poll_Admin extends Forminator_Admin_Module {

	/**
	 * Module objects
	 *
	 * @var array
	 */
	public $module;

	/**
	 * Init
	 *
	 * @since 1.0
	 */
	public function init() {
		$this->module       = Forminator_Polls::get_instance();
		$this->page         = 'forminator-poll';
		$this->page_edit    = 'forminator-poll-wizard';
		$this->page_entries = 'forminator-poll-view';
		$this->dir          = __DIR__;
	}

	/**
	 * Add module pages to Admin
	 *
	 * @since 1.0
	 */
	public function add_menu_pages() {
		new Forminator_Poll_Page( $this->page, 'poll/list', esc_html__( 'Polls', 'forminator' ), esc_html__( 'Polls', 'forminator' ), 'forminator' );
		new Forminator_Poll_New_Page( $this->page_edit, 'poll/wizard', esc_html__( 'Edit Poll', 'forminator' ), esc_html__( 'New Poll', 'forminator' ), 'forminator' );
		new Forminator_Poll_View_Page( $this->page_entries, 'poll/entries', esc_html__( 'Submissions:', 'forminator' ), esc_html__( 'View Poll', 'forminator' ), 'forminator' );
	}

	/**
	 * Pass module defaults to JS
	 *
	 * @since 1.0
	 * @param array $data Data.
	 *
	 * @return mixed
	 */
	public function add_js_defaults( $data ) {
		$model = null;

		if ( $this->is_admin_wizard() ) {
			$data['application'] = 'poll';
			$data['formNonce']   = wp_create_nonce( 'forminator_save_poll' );

			$id = filter_input( INPUT_GET, 'id', FILTER_VALIDATE_INT );
			if ( $id && is_null( $model ) ) {
				$model = Forminator_Base_Form_Model::get_model( $id );
			}

			$answers = array();
			if ( is_object( $model ) ) {
				foreach ( (array) $model->get_fields() as $field ) {
					$a = array(
						'title'                 => html_entity_decode( $field->title ),
						'element_id'            => $field->element_id,
						'color'                 => $field->color,
						'answer_image'          => $field->answer_image,
						'answer_image_filename' => $field->answer_image_filename,
						'answer_image_alt'      => $field->answer_image_alt,
					);
					if ( filter_var( $field->use_extra, FILTER_VALIDATE_BOOLEAN ) === true ) {
						$a['use_extra'] = true;
						$a['extra']     = $field->extra;
					}
					$answers[] = $a;
				}
			}

			$form_id       = isset( $model->id ) ? $model->id : 0;
			$form_name     = isset( $model->name ) ? $model->name : '';
			$form_status   = isset( $model->status ) ? $model->status : 'draft';
			$form_settings = isset( $model->settings ) ? $model->settings : array();

			// Load stored record.
			$settings = apply_filters( 'forminator_poll_settings', $form_settings, $model, $data, $this );

			$data['currentForm'] = array(
				'answers'  => $answers,
				'settings' => array_merge(
					$settings,
					array(
						'form_id'     => $form_id,
						'form_name'   => $form_name,
						'form_status' => $form_status,
					)
				),
			);
		}

		$data['modules']['polls'] = array(
			'new_form_url'  => menu_page_url( $this->page_edit, false ),
			'form_list_url' => menu_page_url( $this->page, false ),
			'preview_nonce' => wp_create_nonce( 'forminator_popup_preview_poll' ),
		);

		return apply_filters( 'forminator_poll_admin_data', $data, $model, $this );
	}

	/**
	 * Localize modules
	 *
	 * @since 1.0
	 * @param array $data Data.
	 *
	 * @return mixed
	 */
	public function add_l10n_strings( $data ) {

		$data['polls'] = array(
			'poll'                           => esc_html__( 'Poll', 'forminator' ),

			// Appearance » Poll results behavior.
			'poll_results_behav'             => esc_html__( 'Poll results behavior', 'forminator' ),
			'link_on'                        => esc_html__( 'Link on poll', 'forminator' ),
			'show_after'                     => esc_html__( 'Show after voted', 'forminator' ),
			'not_show'                       => esc_html__( 'Do not show', 'forminator' ),

			// Appearance » Poll results style.
			'poll_results_style'             => esc_html__( 'Poll results style', 'forminator' ),
			'chart_bar'                      => esc_html__( 'Bar chart', 'forminator' ),
			'chart_pie'                      => esc_html__( 'Pie chart', 'forminator' ),

			// Appearance » Submission.
			'submission'                     => esc_html__( 'Submission', 'forminator' ),
			'submission_notice'              => esc_html__( 'Enable AJAX to prevent refresh while submitting poll data.', 'forminator' ),
			'enable_ajax'                    => esc_html__( 'Enable AJAX', 'forminator' ),

			// Appearance » Poll votes count.
			'poll_votes_count'               => esc_html__( 'Poll votes count', 'forminator' ),
			'show_votes'                     => esc_html__( 'Show number of votes', 'forminator' ),
			'poll_votes_count_description'   => esc_html__( 'Enable this option to display number of votes on Bar Chart results.', 'forminator' ),

			// Appearance » Poll votes limit.
			'poll_votes_limit'               => esc_html__( 'Poll votes limit', 'forminator' ),
			'enable_limit'                   => esc_html__( 'Allow same visitor to vote more than once', 'forminator' ),
			'how_long'                       => esc_html__( 'How long before user can vote again', 'forminator' ),

			// Appearance » Poll privacy.
			'poll_privacy'                   => esc_html__( 'Poll privacy', 'forminator' ),
			'how_long_privacy'               => esc_html__( 'How long will you retain user IP address', 'forminator' ),
			'enable_ip_address_retention'    => esc_html__( 'Enable IP address retention', 'forminator' ),

			// Appearance » Poll design.
			'poll_design'                    => esc_html__( 'Poll design', 'forminator' ),
			'poll_design_description'        => esc_html__( 'Choose a pre-made style for your poll and further customize it\'s appearance', 'forminator' ),
			'customize_poll_colors'          => esc_html__( 'Customize poll colors', 'forminator' ),
			'customize_poll_container'       => esc_html__( 'Customize poll container', 'forminator' ),
			'enable_box_shadow'              => esc_html__( 'Add box shadow to your poll container', 'forminator' ),

			// Appearance » Customize poll colors.
			'poll_container'                 => esc_html__( 'Poll container', 'forminator' ),
			'poll_content'                   => esc_html__( 'Poll content', 'forminator' ),
			'description_color'              => esc_html__( 'Description color', 'forminator' ),
			'question_color'                 => esc_html__( 'Question color', 'forminator' ),
			'poll_answer'                    => esc_html__( 'Poll answer', 'forminator' ),
			'custom_answer'                  => esc_html__( 'Custom answer', 'forminator' ),
			'poll_link'                      => esc_html__( 'Poll link', 'forminator' ),

			// Appearance » Customize Radio and Checkbox Image Size.
			'field-image-size'               => 'custom',

			// CLEAN-UP (OLD).
			'add_answer'                     => esc_html__( 'Add Answer', 'forminator' ),
			'answer_placeholder'             => esc_html__( 'Enter poll answer', 'forminator' ),
			'custom_input_placeholder_label' => esc_html__( 'Custom input placeholder', 'forminator' ),
			'custom_input_placeholder'       => esc_html__( 'Type placeholder here...', 'forminator' ),
			'add_custom_field'               => esc_html__( 'Add custom input field', 'forminator' ),
			'remove_custom_field'            => esc_html__( 'Remove custom input field', 'forminator' ),
			'delete_answer'                  => esc_html__( 'Delete answer', 'forminator' ),
			'details'                        => esc_html__( 'Details', 'forminator' ),
			'appearance'                     => esc_html__( 'Appearance', 'forminator' ),
			'preview'                        => esc_html__( 'Preview', 'forminator' ),
			'details_title'                  => esc_html__( 'Details', 'forminator' ),
			'poll_title'                     => esc_html__( 'Title', 'forminator' ),
			'poll_desc'                      => esc_html__( 'Description', 'forminator' ),
			'poll_question'                  => esc_html__( 'Question', 'forminator' ),
			'poll_button'                    => esc_html__( 'Button label', 'forminator' ),
			'poll_title_placeholder'         => esc_html__( 'Enter title', 'forminator' ),
			'poll_desc_placeholder'          => esc_html__( 'Enter description', 'forminator' ),
			'poll_question_placeholder'      => esc_html__( 'Enter question title', 'forminator' ),
			'poll_button_placeholder'        => esc_html__( 'E.g. Vote', 'forminator' ),
			'appearance_title'               => esc_html__( 'Poll Appearance', 'forminator' ),

			'validate_form_name'             => esc_html__( 'Form name cannot be empty! Please pick a name for your poll.', 'forminator' ),
			'validate_form_question'         => esc_html__( 'Poll question cannot be empty! Please add questions for your poll.', 'forminator' ),
			'validate_form_answers'          => esc_html__( 'Poll answers cannot be empty! Please add answers to your poll.', 'forminator' ),
			'back'                           => esc_html__( 'Back', 'forminator' ),
			'cancel'                         => esc_html__( 'Cancel', 'forminator' ),
			'continue'                       => esc_html__( 'Continue', 'forminator' ),
			'finish'                         => esc_html__( 'Finish', 'forminator' ),

			'poll_title_desc'                => esc_html__( 'This name won\'t be displayed on your poll, but will help you to identify it.', 'forminator' ),
			'poll_question_desc'             => esc_html__( 'This is the question you will be asking to users.', 'forminator' ),

			'answer_color'                   => esc_html__( 'Answer (font color)', 'forminator' ),
			'button_styles'                  => esc_html__( 'Button styles', 'forminator' ),
			'results_link'                   => esc_html__( 'Results link', 'forminator' ),
			'results_link_hover'             => esc_html__( 'Results link (hover)', 'forminator' ),
			'results_link_active'            => esc_html__( 'Results link (active)', 'forminator' ),
		);

		return $data;
	}

	/**
	 * Return default module settings
	 *
	 * @since 1.14
	 *
	 * @param string $name Form name.
	 * @param array  $settings Settings.
	 *
	 * @return array[]
	 */
	public static function get_default_settings( $name, $settings = array() ) {
		return array_merge(
			array(
				'answers'            => array(),
				'settings'           => array(
					'formName'               => $name,
					'version'                => FORMINATOR_VERSION,
					'admin-email-recipients' => array(
						get_option( 'admin_email' ),
					),
					'admin-email-title'      => esc_html__( 'New Poll submission for {poll_name}', 'forminator' ),
					'admin-email-editor'     => esc_html__(
						'You have a new poll submission: <br/><br/>{poll_answer}<br/><br/>Current results: <br/>{poll_result} <br/>---<br/> This message was sent from {site_url}.',
						'forminator'
					),
				),
				'akismet-protection' => true,
				'formName'           => $name,
				'version'            => FORMINATOR_VERSION,
				'store_submissions'  => '1',
			),
			$settings
		);
	}

	/**
	 * Create quiz module
	 *
	 * @since 1.14
	 */
	public function create_module() {
		if ( ! $this->is_admin_wizard() || self::is_edit() ) {
			return;
		}

		$nonce = Forminator_Core::sanitize_text_field( 'create_nonce' );
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'forminator_create_module' ) ) {
			return;
		}

		$name   = Forminator_Core::sanitize_text_field( 'name' );
		$status = Forminator_Poll_Model::STATUS_DRAFT;

		$id = self::create( $name, $status );

		if ( is_wp_error( $id ) ) {
			return;
		}

		$wizard_url = admin_url( 'admin.php?page=forminator-poll-wizard&create-status=success&id=' . $id );

		wp_safe_redirect( $wizard_url );
	}

	/**
	 * Create poll
	 *
	 * @param string $name Name.
	 * @param string $status Status.
	 * @param object $template Template.
	 * @return int post ID
	 */
	public static function create( $name, $status, $template = null ) {
		// Set settings.
		$custom_settings = $template && ! empty( $template->settings )
				? $template->settings : array();
		$settings        = self::get_default_settings( $name, $custom_settings );

		$model = new Forminator_Poll_Model();

		if ( $template && ! empty( $template->fields ) ) {
			// Set fields.
			foreach ( $template->fields as $field_data ) {
				// Create new field model.
				$field            = new Forminator_Form_Field_Model();
				$field_data['id'] = $field_data['element_id'];

				// Import field data to model.
				$field->import( $field_data );
				$field->slug = $field_data['element_id'];

				// Add field to the form.
				$model->add_field( $field );
			}
		}

		$model->name     = sanitize_title( $name );
		$model->settings = self::validate_settings( $settings );
		$model->status   = $status;

		// Save data.
		$id = $model->save();

		return $id;
	}

	/**
	 * Update poll
	 *
	 * @param string $id Module ID.
	 * @param string $title Name.
	 * @param string $status Status.
	 * @param object $template Template.
	 * @return WP_Error post ID
	 */
	public static function update( $id, $title, $status, $template ) {
		if ( is_null( $id ) || $id <= 0 ) {
			$form_model = new Forminator_Poll_Model();
			$action     = 'create';

			if ( empty( $status ) ) {
				$status = Forminator_Poll_Model::STATUS_PUBLISH;
			}
		} else {
			$form_model = Forminator_Base_Form_Model::get_model( $id );
			$action     = 'update';

			if ( ! is_object( $form_model ) ) {
				return new WP_Error( 'forminator_model_not_exist', esc_html__( 'Poll model doesn\'t exist', 'forminator' ) );
			}

			if ( empty( $status ) ) {
				$status = $form_model->status;
			}

			// we need to empty fields cause we will send new data.
			$form_model->clear_fields();
		}

		$form_model->name = sanitize_title( $title );

		$answers = array();
		// Check if answers exist.
		if ( isset( $template->answers ) ) {
			$answers = forminator_sanitize_array_field( $template->answers );
			$answers = wp_slash( $answers );
		}

		foreach ( $answers as $answer ) {
			$field_model  = new Forminator_Form_Field_Model();
			$answer['id'] = $answer['element_id'];
			$field_model->import( $answer );
			$field_model->slug = $answer['element_id'];
			$form_model->add_field( $field_model );
		}

		$settings                    = self::validate_settings( $template->settings );
		$settings['previous_status'] = get_post_status( $id );

		$form_model->settings = $settings;
		$form_model->status   = $status;

		// Save data.
		$id = $form_model->save();

		if ( is_wp_error( $id ) ) {
			return $id;
		}

		// Remove temporary settings.
		Forminator_Base_Form_Model::remove_temp_settings( $id );

		/**
		* Action called after poll saved to database
		*
		* @since 1.11
		*
		* @param int    $id - poll id.
		* @param string $status - poll status.
		* @param array  $answers - poll answers.
		* @param array  $settings - poll settings.
		*/
		do_action( 'forminator_poll_action_' . $action, $id, $status, $answers, $settings );

		// add privacy settings to global option.
		$override_privacy = false;
		if ( isset( $settings['enable-ip-address-retention'] ) ) {
			$override_privacy = filter_var( $settings['enable-ip-address-retention'], FILTER_VALIDATE_BOOLEAN );
		}
		$retention_number = null;
		$retention_unit   = null;
		if ( $override_privacy ) {
			$retention_number = 0;
			$retention_unit   = 'days';
			if ( isset( $settings['ip-address-retention-number'] ) ) {
				$retention_number = (int) $settings['ip-address-retention-number'];
			}
			if ( isset( $settings['ip-address-retention-unit'] ) ) {
				$retention_unit = $settings['ip-address-retention-unit'];
			}
		}

		forminator_update_poll_submissions_retention( $id, $retention_number, $retention_unit );

		Forminator_Render_Form::regenerate_css_file( $id );

		return $id;
	}
}
