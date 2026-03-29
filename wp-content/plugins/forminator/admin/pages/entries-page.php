<?php
/**
 * Forminator Entries Page
 *
 * @package Forminator
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_Entries_Page
 *
 * @since 1.0.5
 */
class Forminator_Entries_Page extends Forminator_Admin_Page {

	/**
	 * Merged default parameter with superglobal REQUEST
	 *
	 * @since 1.0.5
	 * @var array
	 */
	private $screen_params = array();

	/**
	 * HTML representative of entries page
	 *
	 * @since 1.0.5
	 * @var string
	 */
	private $entries_page = '';

	/**
	 * Current Form Model of requested entries
	 *
	 * @since 1.0.5
	 * @var null|Forminator_Base_Form_Model
	 */
	private $form_model = null;

	/**
	 * Populating Current Page Parameters
	 *
	 * @since 1.0.5
	 */
	public function populate_screen_params() {
		$form_type           = Forminator_Core::sanitize_text_field( 'form_type' );
		$this->screen_params = array(
			'form_type' => $form_type ? $form_type : get_option( 'forminator_submissions_form_type', 'forminator_forms' ),
			'form_id'   => Forminator_Core::sanitize_text_field( 'form_id', 0 ),
		);

		if ( empty( $this->screen_params['form_id'] ) ) {
			$forms    = self::get_entries_forms( $this->screen_params['form_type'] );
			$saved_id = (int) get_option( 'forminator_submissions_form_id' );
			$form_id  = in_array( $saved_id, array_map( 'intval', wp_list_pluck( $forms, 'id' ) ), true ) ? $saved_id : 0;

			if ( ! $form_id && 1 === count( $forms ) ) {
				$form_id = (int) reset( $forms )->id;
			}

			if ( $form_id ) {
				$url = add_query_arg(
					array(
						'form_type' => $this->screen_params['form_type'],
						'form_id'   => $form_id,
					)
				);
				if ( wp_safe_redirect( $url ) ) {
					exit;
				}
			}
		}
	}

	/**
	 * Executed Action before render the page
	 *
	 * @since 1.0.5
	 */
	public function before_render() {
		$this->populate_screen_params();
		$this->prepare_entries_page();
		$this->enqueue_entries_scripts();
	}

	/**
	 * Get Form types based on available modules
	 *
	 * @since 1.0.5
	 *
	 * @return mixed
	 */
	public function get_form_types() {
		$form_types = $this->modules_form_type();

		return apply_filters( 'forminator_entries_page_modules', $form_types );
	}

	/**
	 * Prepare Entries Page
	 *
	 * @since 1.0.5
	 */
	private function prepare_entries_page() {
		$this->form_model = $this->get_form_model();
		// Form not found.
		if ( ! $this->form_model instanceof Forminator_Base_Form_Model ) {
			// if form_id available remove it from request, and redirect.
			if ( $this->get_current_form_id() ) {
				$url = remove_query_arg( 'form_id' );
				if ( wp_safe_redirect( $url ) ) {
					exit;
				}
			}
		} else {
			switch ( $this->get_current_form_type() ) {
				case Forminator_Form_Model::model()->get_post_type():
					$entries_renderer = new Forminator_CForm_Renderer_Entries( 'custom-form/entries' );
					break;
				case Forminator_Poll_Model::model()->get_post_type():
					$entries_renderer = new Forminator_Poll_Renderer_Entries( 'poll/entries' );
					break;
				case Forminator_Quiz_Model::model()->get_post_type():
					$entries_renderer = new Forminator_Quiz_Renderer_Entries( 'quiz/entries' );
					break;
				default:
					$entries_renderer = null;
					break;
			}

			if ( $entries_renderer instanceof Forminator_Admin_Page ) {
				ob_start();
				// render the entries page.
				$entries_renderer->render_page_content();
				$this->entries_page = ob_get_clean();
			}
		}
	}

	/**
	 * Return rendered entries page
	 *
	 * @since 1.0.5
	 *
	 * @return string
	 */
	public function render_entries() {
		return $this->entries_page;
	}

	/**
	 * Get available forms for given type.
	 *
	 * @since 1.52.0
	 *
	 * @param string $form_type Form type.
	 *
	 * @return array
	 */
	public static function get_entries_forms( $form_type ) {
		$method = 'get_forms';
		$model  = 'Forminator_Form_Model';

		if ( Forminator_Poll_Model::model()->get_post_type() === $form_type ) {
			$method = 'get_polls';
			$model  = 'Forminator_Poll_Model';
		} elseif ( Forminator_Quiz_Model::model()->get_post_type() === $form_type ) {
			$method = 'get_quizzes';
			$model  = 'Forminator_Quiz_Model';
		}

		$forms = Forminator_API::$method( null, 1, 999, $model::STATUS_PUBLISH );

		return apply_filters( 'forminator_entries_get_forms', $forms, $form_type );
	}

	/**
	 *  Render Form switcher / select based on current form_type
	 *
	 * @param string $form_type Form type.
	 * @param int    $form_id Form Id.
	 * @param array  $forms Forms list to render.
	 *
	 * @since 1.0.5
	 */
	public static function render_form_switcher( $form_type = 'forminator_forms', $form_id = 0, $forms = array() ) {
		$classes = 'sui-select';
		// Using this method for Create Appearance Preset.
		if ( 0 !== $form_id ) {
			$classes .= ' sui-select-sm sui-select-inline';
		}

		$empty_option = esc_html__( 'Choose Form', 'forminator' );

		if ( Forminator_Poll_Model::model()->get_post_type() === $form_type ) {
			$empty_option = esc_html__( 'Choose Poll', 'forminator' );
		} elseif ( Forminator_Quiz_Model::model()->get_post_type() === $form_type ) {
			$empty_option = esc_html__( 'Choose Quiz', 'forminator' );
		}

		echo '<select name="form_id" data-allow-search="1" data-minimum-results-for-search="0" class="' . esc_attr( $classes ) . '" data-search="true" data-search="true" data-placeholder="' . esc_attr( $empty_option ) . '">';
		echo '<option><option>';

		$forms = empty( $forms ) ? self::get_entries_forms( $form_type ) : $forms;

		foreach ( $forms as $form ) {
			/**
			 * Forminator_Base_Form_Model
			 *
			 * @var Forminator_Base_Form_Model $form */
			$title = ! empty( $form->settings['formName'] ) ? $form->settings['formName'] : $form->raw->post_title;
			echo '<option value="' . esc_attr( $form->id ) . '" ' . selected( $form->id, $form_id, false ) . '>' . esc_html( $title ) . '</option>';
		}

		echo '</select>';
	}

	/**
	 * Get current form type
	 *
	 * @since 1.0.5
	 *
	 * @return mixed
	 */
	public function get_current_form_type() {
		return $this->screen_params['form_type'];
	}

	/**
	 * Get current form id
	 *
	 * @since 1.0.5
	 *
	 * @return mixed
	 */
	public function get_current_form_id() {
		return $this->screen_params['form_id'];
	}

	/**
	 * Custom scripts that only used on submissions page
	 *
	 * @since 1.5.4
	 */
	public function enqueue_entries_scripts() {
		wp_enqueue_script(
			'forminator-entries-datepicker-range',
			forminator_plugin_url() . 'assets/js/library/daterangepicker.min.js',
			array( 'moment' ),
			'3.0.3',
			true
		);

		wp_enqueue_script(
			'forminator-inputmask',
			forminator_plugin_url() . 'assets/js/library/inputmask.min.js',
			array( 'jquery' ),
			FORMINATOR_VERSION,
			false
		); // inputmask.

		wp_enqueue_script(
			'forminator-jquery-inputmask',
			forminator_plugin_url() . 'assets/js/library/jquery.inputmask.min.js',
			array( 'jquery' ),
			FORMINATOR_VERSION,
			false
		); // jquery inputmask.

		wp_enqueue_script(
			'forminator-inputmask-binding',
			forminator_plugin_url() . 'assets/js/library/inputmask.binding.js',
			array( 'jquery' ),
			FORMINATOR_VERSION,
			false
		); // inputmask binding.

		// use inline script to allow hooking into this.
		$daterangepicker_ranges
			= sprintf(
				"
			var forminator_entries_datepicker_ranges = {
				'%s': [moment(), moment()],
		        '%s': [moment().subtract(1,'days'), moment().subtract(1,'days')],
		        '%s': [moment().subtract(6,'days'), moment()],
		        '%s': [moment().subtract(29,'days'), moment()],
		        '%s': [moment().startOf('month'), moment().endOf('month')],
		        '%s': [moment().subtract(1,'month').startOf('month'), moment().subtract(1,'month').endOf('month')]
			};",
				esc_html__( 'Today', 'forminator' ),
				esc_html__( 'Yesterday', 'forminator' ),
				esc_html__( 'Last 7 Days', 'forminator' ),
				esc_html__( 'Last 30 Days', 'forminator' ),
				esc_html__( 'This Month', 'forminator' ),
				esc_html__( 'Last Month', 'forminator' )
			);

		/**
		 * Filter ranges to be used on submissions date range
		 *
		 * @since 1.5.4
		 *
		 * @param string $daterangepicker_ranges
		 */
		$daterangepicker_ranges = apply_filters( 'forminator_entries_datepicker_ranges', $daterangepicker_ranges );

		wp_add_inline_script( 'forminator-entries-datepicker-range', $daterangepicker_ranges );

		add_filter( 'forminator_l10n', array( $this, 'add_l10n' ) );
	}

	/**
	 * Hook into forminator_l10n
	 *
	 * Allow to modify `daterangepicker` locale
	 *
	 * @param array $l10n locale.
	 *
	 * @return mixed
	 */
	public function add_l10n( $l10n ) {
		$daterangepicker_lang = array(
			'daysOfWeek' => Forminator_Admin_L10n::get_short_days_names(),
			'monthNames' => Forminator_Admin_L10n::get_months_names(),
		);

		/**
		 * Filter daterangepicker locale to be used
		 *
		 * @since 1.5.4
		 *
		 * @param array $daterangepicker_lang
		 */
		$daterangepicker_lang    = apply_filters( 'forminator_l10n_daterangepicker', $daterangepicker_lang );
		$l10n['daterangepicker'] = $daterangepicker_lang;

		return $l10n;
	}

	/**
	 * Override scripts to be loaded
	 *
	 * @since 1.11
	 *
	 * @param string $hook Hook name.
	 */
	public function enqueue_scripts( $hook ) {
		parent::enqueue_scripts( $hook );

		forminator_print_front_styles();
		forminator_print_front_scripts();
	}
}
