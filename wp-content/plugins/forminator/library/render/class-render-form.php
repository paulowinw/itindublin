<?php
/**
 * The Forminator_Render_Form class.
 *
 * @package Forminator
 */

/**
 * Class Forminator_Render_Form
 *
 * @since 1.0
 */
abstract class Forminator_Render_Form {

	/**
	 * Class instance
	 *
	 * @var Forminator_Render_Form|null
	 */
	protected static $instance = null;

	/**
	 * Forms properties
	 *
	 * @var array
	 */
	protected $forms_properties = array();

	/**
	 * Styles to be enqueued
	 *
	 * @var array
	 */
	protected $styles = array();

	/**
	 * Scripts to be enqueued
	 *
	 * @var array
	 */
	protected $scripts = array();

	/**
	 * Script to be printed
	 *
	 * @var array
	 */
	protected $script = '';

	/**
	 * Model data
	 *
	 * @var Forminator_Base_Form_Model
	 */
	public $model = null;

	/**
	 * Checks if is admin
	 *
	 * @var bool
	 */
	protected $is_admin = false;

	/**
	 * Track Views
	 *
	 * @var bool
	 */
	protected $track_views = true;

	/**
	 * Mapper form with its instance, handling multiple same form rendered
	 *
	 * @var array
	 */
	protected static $render_ids = array();

	/**
	 * Checks if is preview
	 *
	 * @var bool
	 */
	protected $is_preview = false;

	/**
	 * Last submitted data
	 * useful when rendering via ajax and need older data for markup
	 *
	 * @since 1.6.1
	 * @var array
	 */
	protected $last_submitted_data = array();

	/**
	 * Original wp http referer
	 * When ajax load enabled wp_http_referer is replaced with admin-ajax
	 * This var will make it persistent for next render
	 *
	 * @since 1.6.2
	 * @var string
	 */
	protected $_wp_http_referer = '';

	/**
	 * Original page_id
	 * When ajax load enabled page_id cant be found
	 * This var will make it persistent for next render
	 *
	 * @since 1.6.2
	 * @var int
	 */
	protected $_page_id = 0;

	/**
	 * Draft Id
	 *
	 * @var string
	 */
	protected $draft_id = '';

	/**
	 * Draft data
	 *
	 * @var array
	 */
	public $draft_data = array();

	/**
	 * Unique Id
	 *
	 * @var string
	 */
	public static $uid = '';

	/**
	 * Return class instance
	 *
	 * @since 1.0
	 * @return Forminator_Render_Form
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new static();
		}

		return self::$instance;
	}

	/**
	 * Forminator_Render_Form constructor.
	 *
	 * @since 1.0
	 */
	public function __construct() {
		self::$uid      = uniqid();
		$this->is_admin = ( is_admin() && ! wp_doing_ajax() ) || ( defined( 'REST_REQUEST' ) && REST_REQUEST );
		$this->init();
	}

	/**
	 * Init method
	 *
	 * @since 1.0
	 */
	public function init() {
		add_shortcode( 'forminator_' . static::$module_slug, array( $this, 'render_shortcode' ) );
	}

	/**
	 * Render shortcode
	 *
	 * @since 1.0
	 *
	 * @param array $atts Attributes.
	 *
	 * @return string
	 */
	public function render_shortcode( $atts = array() ) {
		// use already created instance if already available.
		$view = new static();
		$id   = ! empty( $atts['id'] ) ? (string) (int) $atts['id'] : 0;
		if ( ! $id ) {
			return $view->message_required();
		}

		$is_preview = isset( $atts['is_preview'] ) ? $atts['is_preview'] : false;
		$is_preview = filter_var( $is_preview, FILTER_VALIDATE_BOOLEAN );

		if ( false === $is_preview && forminator_is_page_builder_preview() ) {
			$is_preview = true;
		}

		$is_preview = apply_filters( 'forminator_render_shortcode_is_preview', $is_preview );

		$preview_data = isset( $atts['preview_data'] ) ? $atts['preview_data'] : array();

		ob_start();

		$view->display( $id, $is_preview, $preview_data );
		$lead_data = ! empty( static::$lead_data ) ? static::$lead_data : array();
		$view->ajax_loader( $is_preview, $preview_data, $lead_data );

		return ob_get_clean();
	}

	/**
	 * Display form method
	 * Must be implemented by class that extend it
	 *
	 * @since 1.0
	 *
	 * @param int  $id Id.
	 * @param bool $is_preview Is preview.
	 *
	 * @return mixed
	 */
	abstract public function display( $id, $is_preview = false );

	/**
	 * Generate render_id for current form
	 * represented as integer, start from 0
	 *
	 * @param int  $id Module id.
	 * @param ?int $forced_render_id Optional. The render id to force for module id.
	 */
	public function generate_render_id( $id, $forced_render_id = null ) {
		// set render_id for mapping Front End with its form.
		if ( ! is_numeric( $forced_render_id ) ) {
			if ( ! isset( self::$render_ids[ $id ] ) ) {
				self::$render_ids[ $id ] = 0;
			} else {
				++self::$render_ids[ $id ];
			}
		} else {
			self::$render_ids[ $id ] = intval( $forced_render_id );
		}

		// Add other plugin classes here that causes additional render_id.
		if ( self::$render_ids[ $id ] > 0 && class_exists( 'DiviOverlaysCore' ) ) {
			--self::$render_ids[ $id ];
		}
	}

	/**
	 * Render form markup
	 *
	 * @since 1.0
	 *
	 * @param int  $id Id.
	 * @param bool $hide If true, display: none will be added on the form markup and later removed with JS.
	 * @param bool $is_preview Is preview.
	 * @param int  $render_id Render Id.
	 */
	public function render( $id, $hide = true, $is_preview = false, $render_id = 0 ) {
		$form_type        = $this->get_form_type();
		$form_fields      = $this->get_fields();
		$form_settings    = $this->get_form_settings();
		$post_id          = $this->get_post_id();
		$this->is_preview = $is_preview;

		do_action( 'forminator_before_form_render', $id, $form_type, $post_id, $form_fields, $form_settings );

		$this->get_form( $id, true, $hide, $render_id );

		do_action( 'forminator_after_form_render', $id, $form_type, $post_id, $form_fields, $form_settings );
	}


	/**
	 * Maybe wrap description
	 *
	 * @param string $html HTML.
	 * @param string $description Description.
	 * @param string $id ID.
	 * @param string $descr_position Description position.
	 *
	 * @return string
	 */
	public function maybe_wrap_description( $html, $description, $id, $descr_position ) {
		if ( empty( $html ) ) {
			return $html;
		}

		if ( 'above' === $descr_position && 'none' === $this->get_form_design() ) {
			$html = '<div>' . $html . '</div>';
		}

		return $html;
	}


	/**
	 * Return form markup
	 *
	 * @since 1.0
	 *
	 * @param int  $id Id.
	 * @param bool $render Render.
	 * @param bool $hide Hide.
	 * @param int  $render_id_ajax Render id.
	 *
	 * @return mixed|void
	 */
	public function get_form( $id, $render = true, $hide = true, $render_id_ajax = 0 ) {
		$id            = (int) $id;
		$html          = '';
		$forminator_ui = '';
		$maybe_draft   = '';

		$form_type         = $this->get_form_type();
		$form_fields       = $this->get_fields();
		$form_settings     = $this->get_form_settings();
		$form_design       = $this->get_form_design();
		$extra_attrs       = $this->form_enctype();
		$extra_classes     = $this->form_extra_classes();
		$track_views       = $this->can_track_views();
		$fields_type_class = $this->get_fields_type_class();
		$design_class      = $this->get_form_design_class();
		$form_uid          = 'data-uid="' . esc_attr( self::$uid ) . '"';

		// If rendered on Preview, the array is empty and sometimes PHP notices show up.
		if ( $this->is_admin && ( empty( self::$render_ids ) || ! $id ) ) {
			self::$render_ids[ $id ] = 0;
		}

		if ( isset( $form_settings['use_ajax_load'] ) && empty( $form_settings['use_ajax_load'] ) ) {
			$render_id = self::$render_ids[ $id ];
		} else {
			$render_id = $render_id_ajax;
		}

		$forminator_ui = 'forminator-ui ';

		if ( ! empty( $form_settings['cform-color-option'] ) && 'theme' === $form_settings['cform-color-option'] ) {
			$extra_attrs .= ' data-color-option="theme"';
		} else {
			$extra_attrs .= ' data-color-option="default"';
		}

		if ( 'quiz' === $form_type ) {
			$extra_attrs .= ' data-design="' . $this->get_quiz_theme() . '"';
		} else {
			$extra_attrs .= ' data-design="' . $this->get_form_design() . '"';
		}

		if ( 'custom-form' === $form_type ) {
			$is_draft_enabled = isset( $form_settings['use_save_and_continue'] ) ? filter_var( $form_settings['use_save_and_continue'], FILTER_VALIDATE_BOOLEAN ) : false;
			$maybe_draft      = $this->set_draft_data( $is_draft_enabled );
			$extra_attrs     .= ' data-grid="' . $this->get_fields_style() . '"';

			if ( $is_draft_enabled ) {
				$extra_classes .= ' draft-enabled';
			}

			if ( ! empty( $form_settings['abandonment'] ) ) {
				$extra_classes .= ' forminator-tracking-abandonment';
				$extra_attrs   .= $this->get_abandonment_required_fields();
			}

			if ( $this->has_pagination() ) {
				$extra_attrs .= $this->get_draft_page();
			}

			add_filter( 'forminator_field_description', array( $this, 'maybe_wrap_description' ), 10, 4 );
		}

		// Markup Loader.
		$loader = sprintf(
			'<div class="%sforminator-%s forminator-%s-%s %s %s %s" data-forminator-render="%s" data-form="forminator-module-%s" %s><br/></div>',
			$forminator_ui,
			$form_type,
			$form_type,
			$id,
			$design_class,
			$fields_type_class,
			$extra_classes,
			$render_id,
			$id,
			$form_uid
		);

		// To-Do: Remove when live preview for Poll & Quiz implemented.
		if ( 'custom-form' !== $form_type ) {
			$loader = '';
		}

		if ( 'quiz' === $form_type ) {
			$quiz_type    = $this->model->quiz_type;
			$extra_attrs .= ' data-quiz="' . $quiz_type . '"';
			$extra_attrs .= ' aria-live="polite"'; // Listen to live changes on form.

			if ( isset( $form_settings['quiz-spacing'] ) && ! empty( $form_settings['quiz-spacing'] ) ) {
				$extra_attrs .= ' data-spacing="' . $form_settings['quiz-spacing'] . '"';
			} else {
				$extra_attrs .= ' data-spacing="default"';
			}

			if ( isset( $form_settings['quiz-alignment'] ) && ! empty( $form_settings['quiz-alignment'] ) ) {
				$extra_attrs .= ' data-alignment="' . $form_settings['quiz-alignment'] . '"';
			} elseif ( false !== strpos( $form_design, 'grid' ) ) {
				$extra_attrs .= ' data-alignment="center"';
			} else {
				$extra_attrs .= ' data-alignment="left"';
			}

			if ( isset( $form_settings['visual_style'] ) && 'grid' === $form_settings['visual_style'] ) {
				if ( isset( $form_settings['quiz-grid-cols'] ) ) {
					$extra_attrs .= ' data-columns="' . $form_settings['quiz-grid-cols'] . '"';
				} else {
					$extra_attrs .= ' data-columns="3"';
				}
			}
		}

		$has_lead = isset( $form_settings['hasLeads'] ) ? $form_settings['hasLeads'] : false;

		$html .= $loader;

		if ( $hide && ! $this->is_preview && ! is_admin() ) {
			$extra_attrs .= ' style="display: none;"';
		}

		if ( 'quiz' === $form_type && $has_lead ) {
			$html .= $this->render_form_header();
		}

		$html .= sprintf(
			'<form
				id="forminator-module-%s"
				class="%sforminator-%s forminator-%s-%s %s %s %s"
				method="post"
				data-forminator-render="%s"
				data-form-id="%s"
				%s
				%s
			>',
			$id,
			$forminator_ui,
			$form_type,
			$form_type,
			$id,
			$design_class,
			$fields_type_class,
			$extra_classes,
			$render_id,
			$id,
			$extra_attrs,
			$form_uid
		);
		if ( 'quiz' === $form_type ) {
			$html .= '<div role="alert" aria-live="polite" class="forminator-response-message forminator-error forminator-hidden"></div>';
		}

		if ( ! $has_lead ) {
			$html .= $this->render_form_header( $maybe_draft );
		}

		$html .= $this->render_fields( false );

		$html .= $this->referer_url_field( false );

		$defender_data = forminator_defender_compatibility();
		if ( $defender_data['is_activated'] ) {
			$html .= $this->render_form_authentication();
		}

		$html .= $this->get_submit( $id, false, $render_id );

		$this->add_script_for_refresh_nonce();

		$html .= sprintf( '</form>' );

		if ( 'custom-form' === $form_type ) {
			$html .= $this->render_skip_form_content();
		}

		// Add edit module link.
		$html .= $this->edit_module_link( $id, $form_type, $this->is_preview );

		if ( $track_views && empty( $this->draft_data ) ) {
			$form_view = Forminator_Form_Views_Model::get_instance();
			$post_id   = $this->get_post_id();

			if ( ! $this->is_admin && forminator_global_tracking() ) {
				$form_view->save_view( $id, $post_id );
			}
		}

		if ( $render ) {
			echo apply_filters( 'forminator_render_form_markup', $html, $form_fields, $form_type, $form_settings, $form_design, $render_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		} else {
			/* @noinspection PhpInconsistentReturnPointsInspection */
			return apply_filters( 'forminator_render_form_markup', $html, $form_fields, $form_type, $form_settings, $form_design, $render_id );
		}
	}

	/**
	 * Refresh nonce to avoid static cache
	 */
	private function add_script_for_refresh_nonce() {
		if ( empty( $this->model->settings['use_ajax_load'] ) || '1' !== $this->model->settings['use_ajax_load'] ) {
			$form_id = $this->model->id;
			add_action(
				'wp_footer',
				function () use ( $form_id ) {
					echo '<script type="text/javascript">jQuery(function() {'
						. 'jQuery.ajax({'
							. "url: '" . esc_url( forminator_ajax_url() ) . "',"
							. 'type: "POST",'
							. 'data: {'
								. 'action: "forminator_get_nonce",'
								. 'form_id: "' . (int) $form_id . '",'
							. '},'
							. 'success: function (response) {'
								. "jQuery('#forminator-module-" . (int) $form_id . " #forminator_nonce').val( response.data );"
							. '}'
						. '});'
					. '})</script>';
				},
				99
			);
		}
	}

	/**
	 * Return form placeholder markup
	 *
	 * @since 1.6.1
	 *
	 * @param int  $id Id.
	 * @param bool $render Render.
	 *
	 * @return mixed|void
	 */
	public function get_form_placeholder( $id, $render = true ) {
		$html      = '';
		$id        = (int) $id;
		$form_type = $this->get_form_type();
		// if rendered on Preview, the array is empty and sometimes PHP notices show up.
		if ( $this->is_admin && ( empty( self::$render_ids ) || ! $id ) ) {
			self::$render_ids[ $id ] = 0;
		}

		$render_id = self::$render_ids[ $id ];
		$form_uid  = 'data-uid="' . esc_attr( self::$uid ) . '"';

		$html .= sprintf(
			'<form id="forminator-module-%s" class="forminator-%s forminator-%s-%s" method="post" data-forminator-render="%s" %s>',
			$id,
			$form_type,
			$form_type,
			$id,
			$render_id,
			$form_uid
		);

			$html .= $this->render_form_header();

		$html .= '</form>';

		if ( $render ) {
			echo apply_filters( 'forminator_render_form_placeholder_markup', $html, $form_type, $render_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		} else {
			/* @noinspection PhpInconsistentReturnPointsInspection */
			return apply_filters( 'forminator_render_form_placeholder_markup', $html, $form_type, $render_id );
		}
	}

	/**
	 * Get Additional CSS class to be aplied based on fields style (enclosed or not)
	 *
	 * @since 1.0.5
	 * @return string
	 */
	public function get_fields_type_class() {
		$form_type    = $this->get_form_type();
		$fields_style = $this->get_fields_style();
		if ( 'custom-form' === $form_type ) {
			if ( 'open' !== $fields_style ) {
				$fields_type = 'forminator-' . $fields_style;
			} else {
				$fields_type = '';
			}
		} else {
			$fields_type = '';
		}

		/**
		 * Filter CSS of fields_type that will be added on user
		 *
		 * @since 1.0.5
		 *
		 * @param string $fields_type  current fields type CSS class that aplied.
		 * @param string $form_type    (custom-form / poll / quiz).
		 * @param string $fields_style (enclosed ?).
		 */
		return apply_filters( 'forminator_render_fields_type_class', $fields_type, $form_type, $fields_style );
	}

	/**
	 * Get Additional CSS class to be aplied based on get_form_design
	 *
	 * @since 1.0.5
	 * @return string
	 */
	public function get_form_design_class() {

		$form_type   = $this->get_form_type();
		$form_design = $this->get_form_design();

		if ( 'quiz' === $form_type ) {
			$design_class = 'forminator-quiz--' . $form_design;
		} elseif ( 'none' === $form_design ) {

				$design_class = '';
		} else {
			$design_class = 'forminator-design--' . $form_design;
		}

		/**
		 * Filter design CSS class that will be aplied on <form
		 *
		 * @since 1.0.5
		 *
		 * @param string $design_class current design CSS class applied.
		 * @param string $form_design  (clean/material, etc).
		 */
		return apply_filters( 'forminator_render_form_design_class', $design_class, $form_design );
	}

	/**
	 * Return form submit button markup
	 *
	 * @since 1.0
	 *
	 * @param int  $form_id Form Id.
	 * @param bool $render Render.
	 *
	 * @return mixed|void
	 */
	public function get_submit( $form_id, $render = true ) {
		$nonce     = $this->nonce_field( 'forminator_submit_form' . $form_id, 'forminator_nonce' );
		$post_id   = $this->get_post_id();
		$html      = $this->get_button_markup();
		$form_type = $this->get_form_type();
		$html     .= $nonce;
		$html     .= sprintf( '<input type="hidden" name="form_id" value="%s">', $form_id );
		$html     .= sprintf( '<input type="hidden" name="page_id" value="%s">', $post_id );
		$settings  = $this->model->settings ?? array();
		if ( isset( self::$render_ids[ $form_id ] ) ) {
			$html .= sprintf( '<input type="hidden" name="render_id" value="%s">', self::$render_ids[ $form_id ] );
		}

		if ( $this->is_preview ) {
			$html .= sprintf( '<input type="hidden" name="action" value="%s">', 'forminator_submit_preview_form_' . $form_type );
		} else {
			$html .= sprintf( '<input type="hidden" name="action" value="%s">', 'forminator_submit_form_' . $form_type );
		}
		$html = apply_filters( 'forminator_render_form_submit_markup', $html, $form_id, $post_id, $nonce, $settings );
		if ( $render ) {
			echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		} else {
			/* @noinspection PhpInconsistentReturnPointsInspection */
			return $html;
		}
	}

	/**
	 * Return button markup
	 *
	 * @since 1.0
	 * @return mixed
	 */
	public function get_button_markup() {

		$button = $this->get_submit_button_text();

		$html              = '<div class="forminator-row">';
			$html         .= '<div class="forminator-col">';
				$html     .= '<div class="forminator-field">';
					$html .= '<button class="forminator-button forminator-button-submit">';
		if ( 'material' === $this->get_form_design() ) {
			$html .= sprintf( '<span>%s</span>', esc_html( $button ) );
			$html .= '<span aria-hidden="true"></span>';
		} else {
			$html .= esc_html( $button );
		}
					$html .= '</button>';
				$html     .= '</div>';
			$html         .= '</div>';
		$html             .= '</div>';

		return apply_filters( 'forminator_render_button_markup', $html, $button );
	}

	/**
	 * Submit button text
	 *
	 * @since 1.0
	 * @return string
	 */
	public function get_submit_button_text() {
		return esc_html__( 'Submit', 'forminator' );
	}

	/**
	 * Start button text
	 *
	 * @param array $settings Module settings.
	 * @return string
	 */
	public function get_start_button_text( $settings = null ) {
		if ( is_null( $settings ) ) {
			$settings = $this->model->settings;
		}
		return ! empty( $settings['text-start'] ) ? $settings['text-start'] : esc_html__( 'Start Quiz', 'forminator' );
	}

	/**
	 * Return Form ID required message
	 *
	 * @since 1.0
	 * @return mixed
	 */
	public function message_required() {
		return esc_html__( 'Module ID attribute is required!', 'forminator' );
	}

	/**
	 * Return Module ID not found message
	 *
	 * @since 1.0
	 * @return string
	 */
	public function message_not_found() {
		return esc_html__( 'Module ID not found!', 'forminator' );
	}

	/**
	 * Get module ID
	 *
	 * @since 1.11
	 *
	 * @return string
	 */
	public function get_module_id() {
		if ( is_object( $this->model ) ) {
			return $this->model->id;
		}

		return $this->model['id'];
	}

	/**
	 * Check if form should be displayed
	 *
	 * @since 1.6.1
	 *
	 * @param bool $is_preview Is preview.
	 *
	 * @return bool
	 */
	public function is_displayable( $is_preview ) {
		$id     = $this->get_module_id();
		$status = $this->model->status;

		if ( $this->model instanceof Forminator_Form_Model && isset( $this->lead_model->status ) ) {
			$status = $this->lead_model->status;
		}
		$class = 'Forminator_' . forminator_get_prefix( static::$module_slug, '', true ) . '_Model';

		if ( $this->model instanceof $class && ( $is_preview || $class::STATUS_PUBLISH === $status ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Ajax submit
	 * Check if the form is ajax submit
	 *
	 * @since 1.0
	 * @return bool
	 */
	public function is_ajax_submit() {
		$settings = $this->get_form_settings();

		if ( ! isset( $settings['enable-ajax'] ) || empty( $settings['enable-ajax'] ) ) {
			return false;
		}

		return filter_var( $settings['enable-ajax'], FILTER_VALIDATE_BOOLEAN );
	}

	/**
	 * Return styles template path
	 *
	 * @param string $theme Theme.
	 *
	 * @since 1.0
	 * @return bool|string
	 */
	public function styles_template_path( $theme ) {
		if ( 'none' === $theme ) {
			return realpath( forminator_plugin_dir() . '/assets/js/front/templates/common/vanilla.html' );
		} elseif ( 'basic' === $theme ) {
			return realpath( forminator_plugin_dir() . '/assets/js/front/templates/' . static::$module_slug . '/basic.html' );
		}

		return realpath( forminator_plugin_dir() . '/assets/js/front/templates/' . static::$module_slug . '/main.html' );
	}

	/**
	 * Get specific template if exists, otherwise get global one
	 *
	 * @param string $theme Design theme.
	 * @param string $filename Filename.
	 * @return string
	 */
	public static function get_template( $theme, $filename ) {
		$path      = forminator_plugin_dir() . '/assets/js/front/templates/' . static::$module_slug . '/';
		$full_path = realpath( $path . $theme . '/' . $filename );
		if ( file_exists( $full_path ) ) {
			return $theme . '/' . $filename;
		} else {
			return 'global/' . $filename;
		}
	}

	/**
	 * Get Properties styles of each rendered modules
	 *
	 * @return array
	 */
	public function get_styles_properties() {
		$properties = array();
		if ( ! empty( $this->forms_properties ) ) {
			// avoid same custom style printed.
			$style_rendered = array();
			foreach ( $this->forms_properties as $form_properties ) {
				if ( ! in_array( $form_properties['id'], $style_rendered, true ) ) {
					$properties[]     = $form_properties;
					$style_rendered[] = $form_properties['id'];
				}
			}
		}

		return $properties;
	}

	/**
	 * Get custom CSS.
	 *
	 * @param array $properties Module properties.
	 * @return string
	 */
	protected static function get_custom_css( $properties ) {
		if ( empty( $properties['use-custom-css'] ) || 'false' === $properties['use-custom-css'] ) {
			return '';
		}

		$slug   = forminator_get_prefix( static::$module_slug, 'custom-' );
		$prefix = '.forminator-ui.forminator-' . $slug . '-' . $properties['form_id'];
		if ( method_exists( static::class, 'get_css_prefix' ) ) {
			$prefix = static::get_css_prefix( $prefix, $properties, $slug );
		}

		$custom_css = forminator_prepare_css(
			$properties['custom_css'],
			$prefix,
			false,
			true,
			'forminator-' . $slug
		);

		return $custom_css;
	}

	/**
	 * Initiate `forminatorFront` front javascript for rendered module(s)
	 *
	 * @since 1.0
	 */
	public function forminator_render_front_scripts() {
		?>
		<script type="text/javascript">
			jQuery(function () {
				<?php
				if ( ! empty( $this->forms_properties ) ) {
					foreach ( $this->forms_properties as $form_properties ) {
						if ( ! empty( $form_properties['rendered'] ) ) {
							$options = $this->get_front_init_options( $form_properties );
							?>
							jQuery('#forminator-module-<?php echo esc_attr( $form_properties['id'] ); ?>[data-forminator-render="<?php echo esc_attr( $form_properties['render_id'] ); ?>"]')
								.forminatorFront(<?php echo wp_json_encode( $options ); ?>);
							<?php
						}
					}
				}
				?>
			});
		</script>
		<?php
	}

	/**
	 * Return form fields
	 *
	 * @since 1.0
	 * @return array
	 */
	public function get_fields() {
		// That function will be overwritten by form class.
		return array();
	}


	/**
	 * Return form fields markup
	 *
	 * @since 1.0
	 *
	 * @param bool $render Render.
	 *
	 * @return mixed|void
	 */
	public function render_fields( $render = true ) {
		$html = '';

		$fields = $this->get_fields();
		foreach ( $fields as $key => $field ) {
			do_action( 'forminator_before_field_render', $field );

			// Render before field markup.
			$html .= $this->render_field_before( $field );

			// Render field.
			$html .= $this->render_field( $field );

			do_action( 'forminator_after_field_render', $field );

			// Render after field markup.
			$html .= $this->render_field_after( $field );
		}

		if ( $render ) {
			echo wp_kses_post( $html );
		} else {
			/* @noinspection PhpInconsistentReturnPointsInspection */
			return apply_filters( 'forminator_render_fields_markup', $html, $fields );
		}
	}

	/**
	 * Return referer url field markup
	 *
	 * @since ?
	 *
	 * @param bool $render Render.
	 *
	 * @return string|void
	 */
	public function referer_url_field( $render = true ) {
		$referer_url = '';
		if ( isset( $_REQUEST['extra']['referer_url'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$referer_url = sanitize_text_field( wp_unslash( $_REQUEST['extra']['referer_url'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		} elseif ( isset( $_SERVER['HTTP_REFERER'] ) ) {
			$referer_url = Forminator_Core::sanitize_text_field( $_SERVER['HTTP_REFERER'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput
		}

		$html = sprintf( '<input type="hidden" name="referer_url" value="%s" />', esc_attr( $referer_url ) );

		if ( $render ) {
			echo wp_kses_post( $html );
		} else {
			return $html;
		}
	}

	/**
	 * Return field classes
	 *
	 * @since 1.0
	 *
	 * @param array $field Field.
	 *
	 * @return string
	 */
	public function get_classes(
		/* @noinspection PhpUnusedParameterInspection */
		$field
	) {
		return 'forminator-field';
	}

	/**
	 * Return markup before field
	 *
	 * @since 1.0
	 *
	 * @param array $field Field.
	 *
	 * @return mixed
	 */
	public function render_field_before( $field ) {
		$class = $this->get_classes( $field );
		$html  = sprintf( '<div class="%s">', $class );

		return apply_filters( 'forminator_before_field_markup', $html, $class );
	}

	/**
	 * Return markup after field
	 *
	 * @since 1.0
	 *
	 * @param array $field Field.
	 *
	 * @return mixed
	 */
	public function render_field_after( $field ) {
		$html = sprintf( '</div>' );

		return apply_filters( 'forminator_after_field_markup', $html, $field );
	}

	/**
	 * Return sanitized form data
	 *
	 * @since 1.0
	 *
	 * @param string $content Content.
	 *
	 * @return mixed
	 */
	public function sanitize_output( $content ) {
		return htmlentities( $content, ENT_QUOTES );
	}

	/**
	 * Return field markup
	 *
	 * @since 1.0
	 *
	 * @param array $field Field.
	 *
	 * @return mixed|void
	 */
	public function render_field( $field ) {
	}

	/**
	 * Return form settings
	 *
	 * @since 1.0
	 * @return array
	 */
	public function get_form_settings() {
		if ( is_object( $this->model ) ) {
			return $this->model->settings;
		}

		if ( is_array( $this->model ) ) {
			return $this->model['settings'];
		}

		return array();
	}

	/**
	 * Check has lead
	 *
	 * @param array $form_settings Form settings.
	 *
	 * @return bool
	 */
	public function has_lead( $form_settings = null ) {
		if ( is_null( $form_settings ) ) {
			$form_settings = $this->get_form_settings();
		}

		if ( isset( $form_settings['hasLeads'] ) && $form_settings['hasLeads'] ) {
			return true;
		}

		return false;
	}

	/**
	 * Check has lead
	 *
	 * @param array $form_settings Form settings.
	 *
	 * @return bool
	 */
	public function has_skip_form( $form_settings = null ) {
		if ( is_null( $form_settings ) ) {
			$form_settings = $this->get_form_settings();
		}

		if ( isset( $form_settings['skip-form'] ) && $form_settings['skip-form'] ) {
			return true;
		}

		return false;
	}

	/**
	 * Check has lead
	 *
	 * @param array|null $form_settings Form settings.
	 *
	 * @return bool
	 */
	public function get_leads_id( $form_settings = null ) {
		if ( is_null( $form_settings ) ) {
			$form_settings = $this->get_form_settings();
		}
		$leads_id = 0;

		if ( $this->has_lead( $form_settings ) && isset( $form_settings['leadsId'] ) ) {
			$leads_id = $form_settings['leadsId'];
		}

		return $leads_id;
	}

	/**
	 * Check has lead
	 *
	 * @param array|null $form_settings Form settings.
	 * @return bool
	 */
	public function get_form_placement( $form_settings = null ) {
		$placement = '';
		if ( is_null( $form_settings ) ) {
			$form_settings = $this->get_form_settings();
		}

		if ( $this->has_lead( $form_settings ) && ! isset( $form_settings['form-placement'] ) ) {
			$placement = 'beginning';
		}

		if ( $this->has_lead( $form_settings ) && isset( $form_settings['form-placement'] ) ) {
			$placement = $form_settings['form-placement'];
		}

		return $placement;
	}

	/**
	 * Return field ID
	 *
	 * @since 1.0
	 *
	 * @param array $field Field.
	 *
	 * @return string
	 */
	public function get_field_id( $field ) {
		return isset( $field['element_id'] ) ? $field['element_id'] : '0';
	}

	/**
	 * Return post ID
	 *
	 * @since 1.0
	 * @return int|string|bool
	 */
	public function get_post_id() {
		$post_id = $this->_page_id;
		if ( empty( $post_id ) ) {
			$post_id = get_post() ? get_the_ID() : '0';
		}

		return $post_id;
	}

	/**
	 * Return form type
	 *
	 * @since 1.0
	 * @return string
	 */
	public function get_form_type() {
		return forminator_get_prefix( static::$module_slug, 'custom-' );
	}

	/**
	 * Return form design
	 *
	 * @since 1.0
	 * @return string
	 */
	public function get_form_design() {
		return '';
	}

	/**
	 * Return fields style
	 *
	 * @since 1.0
	 * @return string
	 */
	public function get_fields_style() {
		return '';
	}

	/**
	 * Render form header
	 *
	 * @param string $maybe_error Error message.
	 *
	 * @since 1.0
	 */
	public function render_form_header( $maybe_error = '' ) {
		return '';
	}

	/**
	 * Render form header
	 *
	 * @since 1.0
	 */
	public function render_form_authentication() {
		return '';
	}

	/**
	 * Form enctype
	 *
	 * @since 1.0
	 * @return string
	 */
	public function form_enctype() {
		return '';
	}

	/**
	 * Form extra classes
	 *
	 * @since 1.0
	 */
	public function form_extra_classes() {
		return '';
	}

	/**
	 * Check if can track views
	 *
	 * @since 1.0
	 * @return bool
	 */
	public function can_track_views() {
		return $this->track_views;
	}

	/**
	 * Define cache constants
	 *
	 * @since 1.6.1
	 */
	public function maybe_define_cache_constants() {
		if ( $this->model instanceof Forminator_Base_Form_Model ) {
			if ( $this->model->is_use_donotcachepage_constant() ) {
				if ( ! defined( 'DONOTCACHEPAGE' ) ) {
					define( 'DONOTCACHEPAGE', 1 );
				}
			}
		}
	}

	/**
	 * Get load ajax status
	 *
	 * @since 1.6.1
	 *
	 * @param bool $force Force.
	 * @param int  $quiz_id Quiz ID - uses only for Form Lead.
	 *
	 * @return bool
	 */
	public function is_ajax_load( $force = false, $quiz_id = null ) {

		if ( ! empty( $quiz_id ) ) {
			$this->lead_model = Forminator_Base_Form_Model::get_model( $quiz_id );
			if ( ! $force ) {
				return isset( $this->lead_model->settings['use_ajax_load'] ) ? $this->lead_model->settings['use_ajax_load'] : false;
			}
		}

		// somehow, it could be incompatible model,.
		// lets return as false when it happens.
		if ( $this->model instanceof Forminator_Base_Form_Model ) {
			return $this->model->is_ajax_load( $force );
		}

		return false;
	}

	/**
	 * Script loader module via ajax
	 *
	 * @since 1.6.1
	 *
	 * @param bool  $is_preview Is preview.
	 * @param array $preview_data Preview data.
	 * @param array $lead_data Lead data.
	 */
	public function ajax_loader( $is_preview, $preview_data = array(), $lead_data = array() ) {

		if ( ! $this->model instanceof Forminator_Base_Form_Model ) {
			return;
		}
		// Load module only via ajax.
		if ( ! $this->is_ajax_load( $is_preview ) ) {
			return;
		}

		$id                        = (string) (int) $this->model->id;
		$this->last_submitted_data = Forminator_Core::sanitize_array( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		// if rendered on Preview, the array is empty and sometimes PHP notices show up.
		if ( $this->is_admin && ( empty( self::$render_ids ) || ! $id ) ) {
			self::$render_ids[ $id ] = 0;
		}

		$this->_wp_http_referer = Forminator_Core::sanitize_text_field( $_SERVER['REQUEST_URI'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$this->_page_id         = $this->get_post_id();

		if ( ! isset( self::$render_ids[ $id ] ) ) {
			return;
		}

		$ajax_options = array(
			'action'           => 'forminator_load_' . static::$module_slug,
			'type'             => $this->model->get_post_type(),
			'id'               => $id,
			'render_id'        => self::$render_ids[ $id ],
			'is_preview'       => $is_preview,
			'preview_data'     => $preview_data,
			'last_submit_data' => $this->last_submitted_data,
			'nonce'            => wp_create_nonce( 'forminator_load_module' ),
			'extra'            => array(
				'_wp_http_referer' => Forminator_Core::sanitize_text_field( $_SERVER['REQUEST_URI'] ), // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
				'page_id'          => $this->get_post_id(),
				'referer_url'      => '', // Original referer url where the user come from. This field will be set via JS.
			),
		);

		if ( ! empty( $lead_data ) ) {
			$ajax_options['has_lead'] = $lead_data['has_lead'];
			$ajax_options['leads_id'] = $lead_data['leads_id'];
		}

		$front_loader_config = wp_json_encode(
			$ajax_options
		);

		$forminator_loader_script = '
		(function ($, document, window) {
				"use strict";
				(function () {
					$(function () {
						if (window.elementorFrontend) {
							if (typeof elementorFrontend.hooks !== "undefined") {
								elementorFrontend.hooks.addAction("frontend/element_ready/global", function ( $scope ) {
									if ( $scope.find( "#forminator-module-' . $id . '" ).length > 0 ) {
										if (typeof ($.fn.forminatorLoader) !== \'undefined\') {
											renderForminatorAjax( ' . $id . ', ' . $front_loader_config . ', ' . self::$render_ids[ $id ] . ' );
										}
									}
								});
							}
							// Elementor Popup
                            $( document ).on( \'elementor/popup/show\', () => {
                                if (typeof ($.fn.forminatorLoader) !== \'undefined\') {
                                    renderForminatorAjax( ' . $id . ', ' . $front_loader_config . ', ' . self::$render_ids[ $id ] . ' );
                                }
                            } );
						}

						if (typeof ($.fn.forminatorLoader) === \'undefined\') {
							console.log(\'forminator scripts not loaded\');
						} else {
							renderForminatorAjax( ' . $id . ', ' . $front_loader_config . ', ' . self::$render_ids[ $id ] . ' );
						}
					});
					function renderForminatorAjax ( id, frontLoaderConfig, renderId ) {
    					var front_loader_config = frontLoaderConfig;
    					front_loader_config.extra.referer_url = document.referrer;
    					$(\'#forminator-module-\' + id + \'[data-forminator-render="\' + renderId + \'"]\')
    						.forminatorLoader(front_loader_config);
				    }
				})();
			}(jQuery, document, window));';

		// on real render use add_inline_script to avoid late initialization.
		if ( ! $is_preview ) {
			wp_add_inline_script( 'forminator-front-scripts', $forminator_loader_script );
		} else {
			// we are on preview, and its ajax called, so scripts need to be output-ed rather than add it on enqueued script.
			?>
			<script type="text/javascript">
				<?php echo $forminator_loader_script; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</script>
			<?php
		}
	}

	/**
	 * Ajax handler to load module
	 *
	 * @since 1.6.1
	 */
	public static function ajax_load_module() {
		/**
		 * Filters whether nonce should be verified when loading form with ajax. By default, it's set to false.
		 *
		 * @since 1.15.13
		 */
		$verify_nonce = apply_filters( 'forminator_ajax_load_module_nonce_verification', false );
		$nonce        = Forminator_Core::sanitize_text_field( 'nonce' );
		$is_preview   = filter_input( INPUT_POST, 'is_preview', FILTER_VALIDATE_BOOLEAN );
		$live_preview = filter_input( INPUT_POST, 'instant_preview', FILTER_VALIDATE_BOOLEAN );

		// For preview, nonce verification is required to ensure the request is legitimate.
		if ( $is_preview && ! wp_verify_nonce( $nonce, 'forminator_load_module' ) ) {
			wp_send_json_error( new WP_Error( 'invalid_code' ) );
		}

		if ( $verify_nonce && ! $is_preview && ! wp_verify_nonce( $nonce, 'forminator_load_module' ) ) {
			wp_send_json_error( new WP_Error( 'invalid_code' ) );
		}

		$id                = filter_input( INPUT_POST, 'id', FILTER_VALIDATE_INT );
		$type              = Forminator_Core::sanitize_text_field( 'type' );
		$preview_data      = isset( $_POST['preview_data'] ) ? Forminator_Core::sanitize_array( $_POST['preview_data'], 'preview_data' ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$last_submit_data  = isset( $_POST['last_submit_data'] ) ? Forminator_Core::sanitize_array( $_POST['last_submit_data'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$extra             = isset( $_POST['extra'] ) ? Forminator_Core::sanitize_array( $_POST['extra'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$has_lead          = Forminator_Core::sanitize_text_field( 'has_lead' );
		$leads_id          = filter_input( INPUT_POST, 'leads_id', FILTER_VALIDATE_INT );
		$lead_preview_data = isset( $_POST['lead_preview_data'] ) ? Forminator_Core::sanitize_array( $_POST['lead_preview_data'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$render_id         = filter_input( INPUT_POST, 'render_id', FILTER_VALIDATE_INT );

		if ( empty( $id ) && ! $is_preview ) {
			wp_send_json_error( new WP_Error( 'invalid_id' ) );
		}

		if ( empty( $type ) ) {
			wp_send_json_error( new WP_Error( 'invalid_type' ) );
		}

		if ( ! empty( $preview_data ) ) {
			if ( ! is_array( $preview_data ) ) {
				$preview_data = json_decode( $preview_data, true );
			}
		}

		// Force set the render id as each ajax request requires specific render_id.
		self::get_instance()->generate_render_id( $id, $render_id );

		$view = null;
		if ( 'forminator_forms' === $type ) {
			$view = Forminator_CForm_Front::get_instance();
			if ( ! empty( $preview_data ) ) {
				$preview_data = forminator_data_to_model_form( $preview_data );
			}
		} elseif ( 'forminator_polls' === $type ) {
			if ( ! empty( $preview_data ) && is_array( $preview_data ) ) {
				$preview_data = forminator_data_to_model_poll( $preview_data );
			}
			$view = Forminator_Poll_Front::get_instance();
		} elseif ( 'forminator_quizzes' === $type ) {
			$view = Forminator_QForm_Front::get_instance();
			if ( ! empty( $preview_data ) && is_array( $preview_data ) ) {
				$preview_data = forminator_data_to_model_quiz( $preview_data );
			}
		}

		if ( ! $view instanceof Forminator_Render_Form ) {
			wp_send_json_error( new WP_Error( 'invalid_module' ) );
		}

		$response = $view->ajax_display( $id, $is_preview, $preview_data, true, $last_submit_data, $extra, 0, $render_id );

		if ( $has_lead ) {
			$lead_view                = Forminator_CForm_Front::get_instance();
			$lead_wrapper_start       = $is_preview ? $view->lead_wrapper_start() : '';
			$lead_wrapper_end         = $is_preview ? $view->lead_wrapper_end() : '';
			$lead_response            = $lead_view->ajax_display( $leads_id, $is_preview, $lead_preview_data, true, $last_submit_data, $extra, $id );
			$response['html']         = $lead_wrapper_start . $response['html'] . $lead_response['html'] . $lead_wrapper_end;
			$response['styles']       = array_merge( $response['styles'], $lead_response['styles'] );
			$response['scripts']      = array_merge( $response['scripts'], $lead_response['scripts'] );
			$response['script']       = $response['script'] . $lead_response['script'];
			$response['style']        = $response['style'] . $lead_response['style'];
			$response['lead_options'] = $lead_response['options'];
		}

		wp_send_json_success( $response );
	}

	/**
	 * Update live preview
	 */
	public static function update_live_preview() {
		$nonce = Forminator_Core::sanitize_text_field( '_wpnonce' );
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'forminator_update_live_preview' ) ) {
			wp_send_json_error( new WP_Error( 'invalid_nonce' ) );
		}

		$id    = filter_input( INPUT_POST, 'id', FILTER_VALIDATE_INT );
		$model = Forminator_Base_Form_Model::get_model( $id );

		if ( 'form' !== $model::$module_slug ) {
			wp_send_json_error( new WP_Error( 'invalid_module_type' ) );
		}
		$preview_data = isset( $_POST['preview_data'] ) ? Forminator_Core::sanitize_array( $_POST['preview_data'], 'preview_data' ) : '{}'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$preview_data = json_decode( $preview_data, true );

		if ( ! empty( $preview_data['settings'] ) ) {
			$model->settings = $preview_data['settings'];
		}

		$obj = new Forminator_CForm_Front();
		ob_start();
		$obj->generate_styles( $model );
		$styles   = ob_get_clean();
		$response = array(
			'styles' => $styles,
		);

		wp_send_json_success( $response );
	}

	/**
	 * Get Front class based on module post type
	 *
	 * @param string $type Post type.
	 * @return string
	 */
	public static function get_front_class( $type ) {
		$class = '';
		if ( 'forminator_forms' === $type ) {
			$class = 'Forminator_CForm_Front';
		} elseif ( 'forminator_polls' === $type ) {
			$class = 'Forminator_Poll_Front';
		} elseif ( 'forminator_quizzes' === $type ) {
			$class = 'Forminator_QForm_Front';
		}

		return $class;
	}

	/**
	 * Ajax response for displaying module
	 *
	 * @since 1.6.1
	 * @since 1.6.2 add $extra args
	 *
	 * @param int   $id Id.
	 * @param bool  $is_preview Is preview.
	 * @param bool  $data Data.
	 * @param bool  $hide Hide.
	 * @param array $last_submit_data Last submit data.
	 * @param array $extra extra config to display.
	 * @param int   $quiz_id Quiz Id.
	 * @param int   $render_id Optional. The render id to force for module.
	 *
	 * @return array
	 */
	public function ajax_display(
		$id,
		$is_preview = false,
		$data = false,
		$hide = true,
		$last_submit_data = array(),
		$extra = array(),
		$quiz_id = 0,
		$render_id = 0
	) {
		// The first module and preview for it.
		$id = isset( $id ) ? intval( $id ) : null;

		if ( ( is_null( $id ) || $id <= 0 ) && $is_preview && $data ) {
			$settings   = array();
			$class      = 'Forminator_' . forminator_get_prefix( static::$module_slug, '', true ) . '_Model';
			$form_model = new $class();

			if ( isset( $data['settings'] ) ) {
				// Sanitize settings.
				$settings = forminator_sanitize_field( $data['settings'] );
				$form_model->set_var_in_array( 'name', 'formName', $settings );
			} else {
				$form_model->set_var_in_array( 'name', 'formName', $data, 'forminator_sanitize_field' );
			}
			$form_model->settings = $settings;

			$form_model = $this->set_form_model_data( $form_model, $data );

			// Sanitize admin email message.
			if ( isset( $data['settings']['admin-email-editor'] ) ) {
				$form_model->settings['admin-email-editor'] = $data['settings']['admin-email-editor'];
			}

			$form_model->settings['version'] = '1.0';

			$status             = $class::STATUS_PUBLISH;
			$form_model->status = $status;

			$this->model     = $form_model;
			$this->model->id = $id;
		} elseif ( $data ) {
			$class       = 'Forminator_' . forminator_get_prefix( static::$module_slug, '', true ) . '_Model';
			$this->model = $class::model()->load_preview( $id, $data );
			// its preview!
			if ( is_object( $this->model ) ) {
				$this->model->id = $id;
			}
		} else {
			$this->model = Forminator_Base_Form_Model::get_model( $id );
		}

		$is_ajax_load = $this->is_ajax_load( $is_preview, $quiz_id );

		$response = array(
			'html'         => '',
			'style'        => '',
			'styles'       => array(),
			'scripts'      => array(),
			'script'       => '',
			'callback'     => '',
			'is_ajax_load' => false,
		);

		if ( ! $this->is_displayable( $is_preview ) ) {
			return $response;
		}

		if ( ! $is_ajax_load ) {
			// return nothing.
			return $response;
		}

		// setup extra param.
		if ( ! empty( $extra ) && is_array( $extra ) ) {
			if ( isset( $extra['_wp_http_referer'] ) ) {
				$this->_wp_http_referer = $extra['_wp_http_referer'];
			}
			if ( isset( $extra['page_id'] ) ) {
				$this->_page_id = $extra['page_id'];
			}
		}

		if ( ! empty( $last_submit_data ) && is_array( $last_submit_data ) ) {
			$_POST = $last_submit_data;
		}

		$response['is_ajax_load'] = true;
		$response['html']         = $this->get_html( $hide, $is_preview, $render_id );

		$properties = isset( $this->forms_properties[0] ) ? $this->forms_properties[0] : array();

		if ( $is_preview ) {
			ob_start();
			$this->print_styles();
			$styles            = ob_get_clean();
			$response['style'] = $styles;
		}

		$response['options'] = $this->get_front_init_options( $properties );

		$this->enqueue_form_scripts( $is_preview, $is_ajax_load );

		$response['styles']  = $this->styles;
		$response['scripts'] = $this->scripts;
		$response['script']  = $this->script;

		return $response;
	}

	/**
	 * Return front-end styles
	 *
	 * @param obj|null $model Model obj.
	 * @param bool     $echo_styles Optional. Echo or return.
	 * @return string
	 */
	public function generate_styles( $model = null, $echo_styles = true ) {

		if ( empty( $this->model ) && ! empty( $model ) ) {
			$this->model = $model;

			$this->set_forms_properties();
		}

		$style_properties = $this->get_styles_properties();

		if ( ! empty( $style_properties ) ) {

			foreach ( $style_properties as $style_property ) {

				if ( empty( $style_property['settings'] ) ) {
					continue;
				}

				$properties = $style_property['settings'];
				if ( method_exists( $this, 'get_pp_field_properties' ) ) {
					$paypal_properties = $this->get_pp_field_properties();
				}

				// Merge paypal properties to styles ( width & height are used in the styles ).
				if ( ! empty( $paypal_properties ) ) {
					$properties = array_merge( $properties, $paypal_properties );
				}

				// use this to properly check font settings is enabled.
				$properties['fonts_settings'] = array();
				if ( isset( $style_property['fonts_settings'] ) ) {
					$properties['fonts_settings'] = $style_property['fonts_settings'];
				}

				// If we don't have a form_id use $model->id.
				if ( ! isset( $properties['form_id'] ) ) {
					if ( ! isset( $style_property ['id'] ) ) {
						continue;
					}
					$properties['form_id'] = $style_property['id'];
				}

				ob_start();

				if ( isset( $properties['custom_css'] ) && isset( $properties['form_id'] ) ) {
					$properties['custom_css'] = static::get_custom_css( $properties );
				} else {
					$properties['custom_css'] = '';
				}

				$theme = $this->get_form_design();
				if ( 'quiz' !== static::$module_slug && ! in_array( $theme, array( 'bold', 'flat', 'material', 'basic', 'none' ), true ) ) {
					$theme = 'default';
				}

				/* @noinspection PhpIncludeInspection */
				include $this->styles_template_path( $theme );
				$styles         = ob_get_clean();
				$trimmed_styles = trim( $styles );

				if ( isset( $properties['form_id'] ) && strlen( $trimmed_styles ) > 0 ) {
					$styles = wp_strip_all_tags( $trimmed_styles );
					if ( $echo_styles ) {
						?>
						<style type="text/css"
								id="forminator-module-styles-<?php echo esc_attr( $properties['form_id'] ); ?>">
							<?php echo $styles; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</style>
						<?php
					} else {
						return $styles;
					}
				}
			}
		}
		return '';
	}

	/**
	 * Generate CSS file.
	 *
	 * @param int $id Module ID.
	 */
	public static function regenerate_css_file( $id ) {
		$model = Forminator_Base_Form_Model::get_model( $id );

		// Prevent creation of external CSS for PDFs.
		if ( 'pdf_form' === $model->status ) {
			return;
		}

		$front_class = self::get_front_class( $model->raw->post_type );
		if ( ! $front_class ) {
			return;
		}
		$obj      = new $front_class();
		$styles   = $obj->generate_styles( $model, false );
		$filename = Forminator_Assets_Enqueue::get_css_upload( $id, 'dir' );

		if ( ! function_exists( 'wp_filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		global $wp_filesystem;
		if ( ! WP_Filesystem() ) {
			// Could not initialize the filesystem.
			return false;
		}

		$wp_filesystem->put_contents( $filename, $styles, FS_CHMOD_FILE );
	}

	/**
	 * Return font specific front-end styles
	 *
	 * @since 1.0
	 */
	public function print_styles() {
		$this->generate_styles();
	}

	/**
	 * Generate nonce field
	 * Respect _wp_http_referer to avoid being replaced when ajax load enabled
	 *
	 * @since 1.6.2
	 *
	 * @param string $action Action.
	 * @param string $name Name.
	 * @param string $referer_url Optional. Referer URL.
	 *
	 * @return string
	 */
	protected function nonce_field( $action, $name, $referer_url = '' ) {
		// Don't generate nonce field when it's preview, as preview is only for admin and it doesn't have real form action.
		if ( $this->is_preview ) {
			return '';
		}
		if ( $referer_url ) {
			$referer = $referer_url;
		} elseif ( ! empty( $this->_wp_http_referer ) ) {
			$referer = $this->_wp_http_referer;
		}
		$with_referer = ! empty( $referer ) ? false : true;
		$nonce        = wp_nonce_field( $action, $name, $with_referer, false );

		if ( ! $with_referer ) {
			$nonce .= sprintf( '<input type="hidden" name="_wp_http_referer" value="%s" />', esc_attr( $referer ) );
		}

		return $nonce;
	}

	/**
	 * Returns the render id of a given module.
	 *
	 * @since 1.15.12
	 *
	 * @param int $module_id Optional. Module id.
	 *
	 * @return int The render id.
	 */
	public static function get_render_id( $module_id = null ) {
		if ( is_null( $module_id ) ) {
			return 0;
		}

		$module_id = intval( $module_id );

		return isset( self::$render_ids[ $module_id ] ) ? self::$render_ids[ $module_id ] : 0;
	}

	/**
	 * Returns all render ids of runtime.
	 *
	 * @since 1.15.12
	 *
	 * @return array A list which contains all render ids.
	 */
	public static function get_render_ids() {
		return self::$render_ids;
	}

	/**
	 * Set draft_data
	 *
	 * @param bool $is_draft_enabled Is draft enabled.
	 *
	 * @since 1.17.0
	 *
	 * @return string
	 */
	public function set_draft_data( $is_draft_enabled ) {
		if ( ! $is_draft_enabled ) {
			return;
		}

		$this->draft_id = isset( $_REQUEST['draft'] ) ? Forminator_Core::sanitize_text_field( 'draft' ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( empty( $this->draft_id ) ) {
			return;
		}

		$draft = new Forminator_Form_Entry_Model( $this->draft_id );
		if ( is_null( $draft->form_id ) && $is_draft_enabled ) {
			return esc_html__( 'Can\'t find the draft associated with the draft ID in the URL. This draft was either submitted or has expired.', 'forminator' );
		}

		if ( (int) $draft->form_id === $this->model->id ) {
			$this->draft_data = $draft->meta_data;
		}
	}

	/**
	 * Get draft page from entry data
	 *
	 * @since 1.17.0
	 *
	 * @return string
	 */
	public function get_draft_page() {
		$draft_page = '';

		if ( isset( $this->draft_data['draft_page'] ) ) {
			$draft_page = ' data-draft-page="' . esc_attr( $this->draft_data['draft_page']['value'] ) . '"';
		}

		return $draft_page;
	}

	/**
	 * Get abandonment required fields
	 *
	 * @return string
	 */
	public function get_abandonment_required_fields() {
		$required_fields = '';

		if ( ! empty( $this->model->settings['abandonment_required_fields'] )
				&& is_array( $this->model->settings['abandonment_required_fields'] ) ) {
			$required_fields = ' data-abandonment-required-fields="'
				. esc_attr( implode( ',', $this->model->settings['abandonment_required_fields'] ) ) . '"';
		}

		return $required_fields;
	}

	/**
	 * Create edit form button
	 *
	 * @since 1.17.0
	 *
	 * @param int    $module_id Module Id.
	 * @param string $module_type Module type.
	 * @param bool   $is_preview Is preview.
	 *
	 * @return string
	 */
	public function edit_module_link( $module_id, $module_type, $is_preview = false ) {
		if (
			! current_user_can( forminator_get_permission( 'forminator-cform' ) ) ||
			$is_preview
		) {
			return;
		}

		$class = 'forminator-module-edit-link';

		switch ( $module_type ) {
			case 'quiz':
				$wizard_page = 'forminator-' . $this->model->quiz_type . '-wizard';
				$text        = esc_html__( 'Edit quiz', 'forminator' );
				break;
			case 'poll':
				$wizard_page = 'forminator-poll-wizard';
				$text        = esc_html__( 'Edit poll', 'forminator' );
				break;
			default:
				$wizard_page = 'forminator-cform-wizard';
				$text        = esc_html__( 'Edit form', 'forminator' );
				break;
		}
		$link = admin_url( 'admin.php?page=' . $wizard_page . '&id=' . $module_id );

		$html = '<div class="forminator-edit-module"><small>';

			$html .= '<a
						class="' . esc_attr( $class ) . '"
						title="' . esc_attr( $text ) . '"
						href="' . esc_url( $link ) . '"
						target="_blank"
						rel="noreferrer"
					>'
					. esc_html( $text ) .
					'</a>';

		$html .= '</small></div>';

		return $html;
	}
}
