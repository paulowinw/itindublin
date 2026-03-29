<?php
/**
 * The Forminator_Poll_Front class.
 *
 * @package Forminator
 */

/**
 * Front render class for custom forms
 *
 * @since 1.0
 */
class Forminator_Poll_Front extends Forminator_Render_Form {

	/**
	 * Module slug
	 *
	 * @var string
	 */
	protected static $module_slug = 'poll';

	/**
	 * Scripts of graph results
	 *
	 * @var array
	 */
	private static $graph_result_scripts = array();

	/**
	 * Default Combination of Chart Colors
	 *
	 * @var array
	 */
	public static $default_chart_colors = array( '#F4B414', '#1ABC9C', '#097BAA', '#18485D', '#D30606' );

	/**
	 * Display form method
	 *
	 * @since 1.0
	 *
	 * @param int  $id Id.
	 * @param bool $is_preview Is preview.
	 * @param bool $data Data.
	 * @param bool $hide If true, display: none will be added on the form markup and later removed with JS.
	 */
	public function display( $id, $is_preview = false, $data = false, $hide = true ) {

		if ( $data && ! empty( $data ) ) {

			// New form, we have to update the form id.
			$has_id = filter_var( $id, FILTER_VALIDATE_BOOLEAN );

			if ( ! $has_id && isset( $data['settings']['form_id'] ) ) {
				$id = $data['settings']['form_id'];
			}

			$this->model = Forminator_Poll_Model::model()->load_preview( $id, $data );

			// its preview!
			$this->model->id = $id;

		} else {

			$this->model = Forminator_Base_Form_Model::get_model( $id );

			if ( ! $this->model instanceof Forminator_Poll_Model ) {
				return;
			}
		}

		$this->maybe_define_cache_constants();

		// TODO: make preview and ajax load working similar.
		$is_ajax_load = $this->is_ajax_load( $is_preview );

		// Load assets conditionally.
		$assets = new Forminator_Assets_Enqueue_Poll( $this->model, $is_ajax_load );
		$assets->enqueue_styles( $this );
		$assets->enqueue_scripts();

		if ( $is_ajax_load && $this->model->current_user_can_vote() ) {

			$this->generate_render_id( $id );
			$this->get_form_placeholder( esc_attr( $id ), true );

			wp_enqueue_script( 'google-charts', 'https://www.gstatic.com/charts/loader.js', array( 'jquery' ), '1.0', false );

			return;
		}

		if ( $this->is_displayable( $is_preview ) ) {

			$this->generate_render_id( $id );

			echo $this->get_html( $hide, $is_preview ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

			if ( is_admin() ) {
				$this->print_styles();
			}

			wp_enqueue_script( 'google-charts', 'https://www.gstatic.com/charts/loader.js', array( 'jquery' ), '1.0', false );

			add_action( 'wp_footer', array( $this, 'forminator_render_front_scripts' ), 9999 );
			add_action( 'wp_footer', array( $this, 'graph_scripts' ), 100 );
		}
	}

	/**
	 * Return form fields
	 *
	 * @since 1.0
	 * @return array|mixed
	 */
	public function get_fields() {

		if ( is_object( $this->model ) ) {
			return $this->model->get_fields_grouped();
		} else {
			return $this->message_not_found();
		}
	}

	/**
	 * Poll question
	 *
	 * @param object $model model.
	 *
	 * @since 1.0
	 * @return string
	 */
	public static function get_poll_question( $model ) {
		if ( is_object( $model ) && isset( $model->settings['poll-question'] ) ) {
			return $model->settings['poll-question'];
		} else {
			return '';
		}
	}

	/**
	 * Poll Description
	 *
	 * @param object $model model.
	 *
	 * @since 1.0
	 * @return string
	 */
	public static function get_poll_description( $model ) {
		if ( is_object( $model ) && isset( $model->settings['poll-description'] ) ) {
			return $model->settings['poll-description'];
		} else {
			return '';
		}
	}

	/**
	 * Poll Image
	 *
	 * @since 1.5.3
	 * @return string
	 */
	public function get_poll_image() {

		if ( is_object( $this->model ) && isset( $this->model->settings['poll-image'] ) ) {
			return $this->model->settings['poll-image'];
		} else {
			return '';
		}
	}

	/**
	 * Poll header
	 *
	 * @param string $maybe_error Error message.
	 *
	 * @since 1.0
	 * @return string
	 */
	public function render_form_header( $maybe_error = '' ) {

		$html         = '';
		$label_class  = '';
		$message_wrap = '';
		$status_info  = $this->model->opening_status();
		if ( 'open' !== $status_info['status'] ) {
			$html .= '<div role="alert" aria-live="polite" class="forminator-response-message forminator-error
 forminator-show">';
			$html .= '<p>' . esc_html( $status_info['msg'] ) . '</p>';
			$html .= '</div>';
		}

		$is_preview = $this->is_preview;
		if ( 'open' === $status_info['status'] ) {
			$hidden_wrap = '<div role="alert" aria-live="polite" class="forminator-response-message" aria-hidden="true">';
			if ( ! $is_preview ) {
				$label_class  = filter_input( INPUT_GET, 'saved', FILTER_VALIDATE_BOOLEAN ) ? 'forminator-success' : 'forminator-error';
				$message_wrap = '<div role="alert" aria-live="polite" class="forminator-response-message forminator-show ' . esc_attr( $label_class ) . '" >';
			} else {
				$message_wrap = $hidden_wrap;
			}
		}

		ob_start();
		do_action( 'forminator_poll_post_message', $this->model->id, self::$render_ids[ $this->model->id ] ); // prints html, so we need to capture this.
		$custom_message = ob_get_clean();
		$custom_message = trim( $custom_message );
		$html          .= $custom_message;

			ob_start();

		if ( filter_input( INPUT_GET, 'saved', FILTER_VALIDATE_BOOLEAN ) && ! filter_input( INPUT_GET, 'results', FILTER_VALIDATE_BOOLEAN ) ) {
			$form_id   = filter_input( INPUT_GET, 'form_id', FILTER_VALIDATE_INT );
			$render_id = filter_input( INPUT_GET, 'render_id', FILTER_VALIDATE_INT );
			if ( $form_id === (int) $this->model->id
				&& $render_id === (int) self::$render_ids[ $this->model->id ] ) {

				$this->track_views = false; ?>

					<p class="forminator-label--<?php echo esc_attr( $label_class ); ?>"><?php esc_html_e( 'Your vote has been saved.', 'forminator' ); ?></p>

				<?php
			}
		} else {

			$action = filter_input( INPUT_POST, 'action', FILTER_SANITIZE_SPECIAL_CHARS );
			if (
				! $is_preview &&
				! $this->model->current_user_can_vote() &&
				! $action
			) {

				$this->track_views = false;
				?>

					<p class="forminator-label--<?php echo esc_attr( $label_class ); ?>"><?php esc_html_e( 'You have already voted for this poll.', 'forminator' ); ?></p>

				<?php
			}
		}

			$message = ob_get_clean();

		if ( $message ) {
			$html .= $message_wrap . $message . '</div>';
		} elseif ( ! $custom_message && 'open' === $status_info['status'] ) {
			$html .= $hidden_wrap . '</div>';
		}

		$image       = $this->get_poll_image();
		$question    = self::get_poll_question( $this->model );
		$description = self::get_poll_description( $this->model );

		if (
			! empty( $question ) ||
			! empty( $image ) ||
			! empty( $description )
		) {

			$html .= '<div class="forminator-poll-header">';

			if ( ! empty( $question ) ) {
				$html .= sprintf( '<span id="%s" class="forminator-question forminator-poll--question">%s</span>', 'forminator-poll-' . esc_attr( $this->model->id ) . '--title', esc_attr( $question ) );
			}

			if ( ! empty( $description ) ) {
				$html .= sprintf( '<span class="forminator-description">%s</span>', esc_html( $description ) );
			}

			if ( ! empty( $image ) ) {
				$html .= sprintf( '<img class="forminator-image" src="%s" role="img" aria-hidden="true" />', esc_attr( $image ) );
			}

			$html .= '</div>';

		}

		return apply_filters( 'forminator_poll_header', $html, $this );
	}

	/**
	 * Poll question
	 *
	 * @since 1.0
	 * @return string
	 */
	public function get_submit_button_text() {

		if (
			is_object( $this->model ) &&
			isset( $this->model->settings['poll-button-label'] ) &&
			! empty( $this->model->settings['poll-button-label'] )
		) {
			return esc_html( $this->model->settings['poll-button-label'] );
		} else {
			return parent::get_submit_button_text();
		}
	}

	/**
	 * Button markup
	 *
	 * @param array $form_settings Form settings.
	 *
	 * @since 1.0
	 * @return string
	 */
	public function get_button_markup( $form_settings = array() ) {

		$disabled = '';
		$design   = $this->get_form_design();

		// If it's on admin then bypass current_user_can_vote.
		if (
			is_object( $this->model ) &&
			( $this->is_preview || $this->model->current_user_can_vote() )
		) {

			// If poll opening status is not open then disable submit button.
			$status_info = $this->model->opening_status();
			if ( 'open' !== $status_info['status'] ) {
				$disabled = ' disabled="disabled"';
			}

			$button = $this->get_submit_button_text();

			$html = '<div class="forminator-poll-footer forminator-poll--actions">';

				$html .= sprintf(
					'<button class="forminator-button forminator-button-submit" %s>',
					$disabled
				);

					$html .= sprintf(
						'<span>%s</span>',
						esc_html( $button )
					);

					$html .= ( 'none' !== $design ) ? '<i class="forminator-icon-loader forminator-loading" aria-hidden="true"></i>' : '';

					$html .= ( 'material' === $design ) ? '<span aria-hidden="true"></span>' : '';

				$html .= '</button>';

			if ( filter_input( INPUT_GET, 'saved', FILTER_VALIDATE_BOOLEAN ) || $this->show_link() ) {

				$url = '';

				// Fallback, disable view results in Preview.
				if ( $this->is_preview ) {
					$url = '#';
				} else {
					$url = add_query_arg(
						array(
							'results'   => 'true',
							'form_id'   => $this->model->id,
							'render_id' => self::$render_ids[ $this->model->id ],
						),
						$url
					);
				}

				if ( 0 === Forminator_Form_Entry_Model::count_entries( $this->model->id ) ) {

					$html .= sprintf(
						'<span class="forminator-note">%s</span>',
						esc_html__( 'No votes yet', 'forminator' )
					);
				} else {

					$html .= sprintf(
						'<a href="%s" class="forminator-link">%s</a>',
						esc_url( $url ),
						esc_html__( 'View results', 'forminator' )
					);
				}
			}

			$html .= '</div>';

			return apply_filters( 'forminator_render_button_markup', $html, $button );

		} else {

			$html = '<div class="forminator-poll-footer forminator-poll--actions">';

			if ( $this->show_link() ) {
				$url = '';
				// Fallback, disable view results in Preview.
				if ( $this->is_preview ) {
					$url = '#';
				} else {
					$url = add_query_arg(
						array(
							'results'   => 'true',
							'form_id'   => $this->model->id,
							'render_id' => self::$render_ids[ $this->model->id ],
						),
						$url
					);
				}
				$html .= sprintf( '<a href="%s">%s</a>', esc_url( $url ), esc_html__( 'View results', 'forminator' ) );
			}

			$html .= '</div>';

			return apply_filters( 'forminator_render_button_disabled_markup', $html, $this );
		}
	}

	/**
	 * Extra form classes for ajax
	 *
	 * @since 1.0
	 * @return string
	 */
	public function form_extra_classes() {

		$classes   = '';
		$ajax_form = $this->is_ajax_submit();

		if ( $ajax_form || $this->is_preview ) {
			$classes .= ' forminator_ajax';
		}

		if ( is_object( $this->model ) && ! $this->is_preview && ! $this->model->current_user_can_vote() ) {
			$classes .= ' forminator-poll-disabled';
		}

		return apply_filters( 'forminator_polls_form_extra_classes', $classes, $this );
	}

	/**
	 * Return before wrapper markup
	 *
	 * @since 1.0
	 *
	 * @param array $wrapper Wrapper.
	 *
	 * @return string
	 */
	public function render_wrapper_before( $wrapper ) {

		$form_settings = $this->get_form_settings();
		$visual_style  = isset( $form_settings['visual_style'] ) ? $form_settings['visual_style'] : 'list';

		$radio_title = 'forminator-poll-' . esc_attr( $this->model->id ) . '--title';

		if ( 'grid' === $visual_style ) {
			$html = '<div role="radiogroup" class="forminator-field forminator-poll--grid" aria-labelledby="' . esc_attr( $radio_title ) . '">';
		} else {
			$html = '<div role="radiogroup" class="forminator-field" aria-labelledby="' . esc_attr( $radio_title ) . '">';
		}

		return apply_filters( 'forminator_before_wrapper_markup', $html );
	}

	/**
	 * Return after wrapper markup
	 *
	 * @since 1.0
	 *
	 * @param array $wrapper Wrapper.
	 *
	 * @return mixed
	 */
	public function render_wrapper_after( $wrapper ) {

		$html = '</div>';

		return apply_filters( 'forminator_after_wrapper_markup', $html );
	}

	/**
	 * Return fields markup
	 *
	 * @since 1.0
	 *
	 * @param bool $render Render.
	 *
	 * @return string
	 */
	public function render_fields( $render = true ) {

		$html          = '';
		$wrappers      = $this->get_fields();
		$form_settings = $this->get_form_settings();
		$visual_style  = isset( $form_settings['visual_style'] ) ? $form_settings['visual_style'] : 'list';

		if ( ! empty( $wrappers ) ) {

			$html .= '<div class="forminator-poll-body">';

			foreach ( $wrappers as $key => $wrapper ) {

				if ( ! isset( $wrapper['fields'] ) ) {
					return;
				}

				// Render before wrapper markup.
				$html .= $this->render_wrapper_before( $wrapper );

				foreach ( $wrapper['fields'] as $k => $field ) {

					$form_settings = $this->get_form_settings();

					$images_enabled   = isset( $form_settings['enable_images'] ) ? filter_var( $form_settings['enable_images'], FILTER_VALIDATE_BOOLEAN ) : false;
					$option_image_url = array_key_exists( 'answer_image', $field ) ? $field['answer_image'] : '';

					if ( ! empty( $field['title'] ) ) {

						$uniq_id = uniqid();
						do_action( 'forminator_before_field_render', $field );

						if ( $images_enabled && 'grid' === $visual_style ) {
							$html .= '<div class="forminator-poll--column">';
						}

						// Render field.
						$html .= $this->render_field_radio( $field, $uniq_id );

						do_action( 'forminator_after_field_render', $field );

						$use_extra = Forminator_Field::get_property( 'use_extra', $field, false );
						$use_extra = filter_var( $use_extra, FILTER_VALIDATE_BOOLEAN );

						if ( $use_extra ) {

							if ( $images_enabled ) {
								$html .= '<div class="forminator-field forminator-hidden" aria-hidden="true">';
							} else {
								$html .= '<div class="forminator-field forminator-hidden" style="margin-left: 30px;" aria-hidden="true">';
							}

								$html .= $this->render_extra_field( $field, $uniq_id );

							// Render after field markup.
							$html .= '</div>';

						}

						if ( $images_enabled && 'grid' === $visual_style ) {
							$html .= '</div>';
						}
					}
				}

					// Render after wrapper markup.
					$html .= $this->render_wrapper_after( $wrapper );

			}

			$html .= '</div>';
		}

		if ( $render ) {

			echo wp_kses_post( $html );

		} else {

			return apply_filters(
				'forminator_render_fields_markup',
				$html,
				$wrappers,
				$this
			);
		}
	}

	/**
	 * Return field markup of Radio for poll
	 *
	 * @since 1.0
	 *
	 * @param array  $field Field.
	 * @param string $uniq_id Unique Id.
	 *
	 * @return mixed
	 */
	public function render_field_radio( $field, $uniq_id ) {

		$label         = Forminator_Field::get_property( 'title', $field, $this->model->id );
		$form_settings = $this->get_form_settings();

		// Get field object.
		$element_id       = Forminator_Field::get_property( 'element_id', $field );
		$name             = $this->model->id;
		$design           = isset( $form_settings['forminator-poll-design'] ) ? $form_settings['forminator-poll-design'] : 'default';
		$images_enabled   = isset( $form_settings['enable_images'] ) ? filter_var( $form_settings['enable_images'], FILTER_VALIDATE_BOOLEAN ) : false;
		$input_visibility = isset( $form_settings['input_visibility'] ) ? filter_var( $form_settings['input_visibility'], FILTER_VALIDATE_BOOLEAN ) : true;
		$option_image_url = array_key_exists( 'answer_image', $field ) ? $field['answer_image'] : '';
		$option_image_alt = array_key_exists( 'answer_image_alt', $field ) ? $field['answer_image_alt'] : '';

		if ( ! isset( $field['value'] ) ) {
			$field['value'] = sanitize_title( $label );
		}

		// form_id - render_id - element_id.
		$input_id   = $name . '-' . self::$render_ids[ $this->model->id ] . '-' . $element_id;
		$aria_label = sprintf(
			'<span class="forminator-screen-reader-only">%s</span>',
			wp_kses(
				$label,
				array(
					'a'      => array(
						'href'  => array(),
						'title' => array(),
					),
					'span'   => array(
						'class' => array(),
					),
					'b'      => array(),
					'i'      => array(),
					'br'     => array(),
					'em'     => array(),
					'strong' => array(),
				)
			)
		);

		$class = 'forminator-radio';

		if ( $images_enabled && ! empty( $option_image_url ) ) {

			$class .= ' forminator-has_image';

			if ( $input_visibility ) {
				$class .= ' forminator-has_bullet';
			}
		}

		// Print field markup.
		$html = sprintf( '<label for="%s" class="%s">', esc_attr( $input_id ), $class );

			$html .= $this->radio_field_markup( $field, $input_id, $name );

		if ( $input_visibility && ( $images_enabled && ! empty( $option_image_url ) ) ) {

			// Bullet + Label.
			$html .= '<span class="forminator-radio-bullet" aria-hidden="true"></span>';
			$html .= '<span class="forminator-radio-label">' . esc_html( $label ) . '</span>';

			// Image.
			if ( 'none' === $design || 'basic' === $design ) {
				$html .= '<img class="forminator-radio-image" src="' . esc_url( $option_image_url ) . '" alt="' . esc_attr( $option_image_alt ) . '" aria-hidden="true" />';
			} else {
				$html     .= '<span class="forminator-radio-image" aria-hidden="true">';
					$html .= '<span style="background-image: url(' . esc_url( $option_image_url ) . ');"><span>' . esc_attr( $option_image_alt ) . '</span></span>';
				$html     .= '</span>';
			}
		} elseif ( ! $input_visibility && ( $images_enabled && ! empty( $option_image_url ) ) ) {

			// Image.
			if ( 'none' === $design || 'basic' === $design ) {
				$html .= '<img class="forminator-radio-image" src="' . esc_url( $option_image_url ) . '" alt="' . esc_attr( $option_image_alt ) . '" aria-hidden="true" />';
			} else {
				$html     .= '<span class="forminator-radio-image" aria-hidden="true">';
					$html .= '<span style="background-image: url(' . esc_url( $option_image_url ) . ');"><span>' . esc_attr( $option_image_alt ) . '</span></span>';
				$html     .= '</span>';
			}

			// Aria Label.
			$html .= $aria_label;

		} else {

			// Bullet + Label.
			$html .= '<span class="forminator-radio-bullet" aria-hidden="true"></span>';
			$html .= '<span class="forminator-radio-label">' . esc_html( $label ) . '</span>';

		}

		$html .= '</label>';

		return apply_filters( 'forminator_field_markup', $html, $field, $this );
	}

	/**
	 * Radio field markup
	 *
	 * @since 1.0
	 *
	 * @param array  $field Field.
	 * @param int    $id Id.
	 * @param string $name Name.
	 *
	 * @return mixed
	 */
	public function radio_field_markup( $field, $id, $name ) {

		$required = Forminator_Field::get_property( 'required', $field, false );
		$value    = Forminator_Field::get_property( 'element_id', $field );
		$disabled = '';

		if ( ! $this->is_preview && ! $this->model->current_user_can_vote() ) {
			$disabled = 'disabled="disabled"';
		}

		$html = sprintf(
			'<input id="%s" type="radio" data-required="%s" name="%s" value="%s" %s />',
			esc_attr( $id ),
			boolval( $required ),
			esc_attr( $name ),
			esc_attr( $value ),
			$disabled
		);

		return apply_filters( 'forminator_field_radio_markup', $html, $id, $name, $required, $value );
	}

	/**
	 * Render extra field
	 *
	 * @since 1.0
	 *
	 * @param array  $field Field.
	 * @param string $uniq_id Unique Id.
	 * @param array  $form_settings Form settings.
	 *
	 * @return mixed
	 */
	public function render_extra_field( $field, $uniq_id, $form_settings = array() ) {

		$label  = Forminator_Field::get_property( 'title', $field, $this->model->id );
		$extra  = Forminator_Field::get_property( 'extra', $field );
		$design = $this->get_form_design();

		// Get field object.
		$element_id = Forminator_Field::get_property( 'element_id', $field );
		$name       = $this->model->id;

		// form_id - render_id - element_id.
		$input_id = $name . '-' . self::$render_ids[ $this->model->id ] . '-' . $element_id;

		$html = '';

		if ( ! isset( $field['value'] ) ) {
			$field['value'] = sanitize_title( $label );
		}

		if ( '' !== $label || '' !== $extra ) {

			$html = sprintf(
				'<label for="%s" class="forminator-screen-reader-only">%s</label>',
				$input_id . '-extra',
				( '' !== $label ) ? esc_html( $label ) : esc_html( $extra )
			);
		}

		if ( 'material' === $design ) {
			$html .= '<div class="forminator-input--wrap">';
		}

			$html .= sprintf(
				'<input
					type="text"
					name="%s"
					placeholder="%s"
					id="%s"
					class="forminator-input"
				/>',
				$name . '-extra',
				esc_html( $extra ),
				$input_id . '-extra'
			);

		if ( 'material' === $design ) {
			$html .= '</div>';
		}

		return apply_filters( 'forminator_field_textfield_extra_markup', $html, $name );
	}

	/**
	 * Return form design
	 *
	 * @since 1.0
	 * @return mixed
	 */
	public function get_form_design() {

		$form_settings = $this->get_form_settings();

		if ( ! isset( $form_settings['forminator-poll-design'] ) ) {
			return 'default';
		}

		return $form_settings['forminator-poll-design'];
	}

	/**
	 * Results chart
	 *
	 * @param array $settings Settings.
	 *
	 * @since 1.0
	 * @return string
	 */
	public static function get_chart_design( $settings ) {
		if ( ! isset( $settings['results-style'] ) ) {
			return 'bar';
		}

		return $settings['results-style'];
	}

	/**
	 * Results chart design
	 *
	 * @since 1.0
	 * @return string
	 */
	private function get_show_results() {

		$form_settings = $this->get_form_settings();

		if ( ! isset( $form_settings['results-behav'] ) || empty( $form_settings['results-behav'] ) ) {
			return 'not_show';
		}

		return $form_settings['results-behav'];
	}

	/**
	 * Show results after poll submit
	 *
	 * @since 1.0
	 * @return bool
	 */
	private function show_results() {

		$show_results = $this->get_show_results();

		if ( 'show_after' === $show_results ) {
			return true;
		}

		return false;
	}

	/**
	 * Show link after submit
	 *
	 * @since 1.0
	 * @return bool
	 */
	private function show_link() {

		$show_results = $this->get_show_results();

		if ( 'link_on' === $show_results ) {
			return true;
		}

		return false;
	}

	/**
	 * Render success
	 *
	 * @param bool  $render Render.
	 * @param array $form_settings Settings.
	 *
	 * @since 1.0
	 * @return string
	 */
	public function render_success( $render = true, $form_settings = array() ) {

		$design          = $this->get_form_design();
		$settings        = $this->get_form_settings();
		$answers_count   = $this->get_fields();
		$answers_count   = count( $answers_count[0]['fields'] );
		$adjusted_height = $answers_count < 6 ? 200 : $answers_count * 35;
		$alignment       = isset( $form_settings['poll_alignment'] ) ? $form_settings['poll_alignment'] : 'left';

		if ( is_object( $this->model ) ) {

			$post_id         = $this->get_post_id();
			$return_url      = get_permalink( $post_id );
			$chart_container = 'forminator_chart_poll_' . uniqid() . '_' . $this->model->id;

			ob_start();
			?>

			<form id="forminator-module-<?php echo esc_attr( $this->model->id ); ?>"
				class="forminator-ui forminator-poll forminator-poll-<?php echo esc_attr( $this->model->id ); ?> <?php echo esc_attr( $this->get_form_design_class() ); ?> <?php echo esc_attr( $this->get_fields_type_class() ); ?> <?php echo esc_attr( $this->form_extra_classes() ); ?>"
				method="GET"
				action="<?php echo esc_url( $return_url ); ?>"
				data-forminator-render="<?php echo esc_attr( self::$render_ids[ $this->model->id ] ); ?>"
				data-design="<?php echo esc_attr( $design ); ?>"
				data-color-option="default"
				data-alignment="<?php echo esc_attr( $alignment ); ?>"
			>

				<?php echo wp_kses_post( $this->render_form_header() ); ?>

				<div class="forminator-poll-body">

					<canvas id="<?php echo esc_attr( $chart_container ); ?>" class="forminator-chart forminator-show" role="img" aria-hidden="true" style="<?php printf( 'height:%spx;', esc_attr( $adjusted_height ) ); ?>"></canvas>

				</div>

				<?php if ( isset( $settings['enable-votes-limit'] ) && 'true' === $settings['enable-votes-limit'] ) { ?>

					<div class="forminator-poll-footer forminator-poll--actions">

						<?php if ( 'material' === $design ) : ?>

							<button class="forminator-button forminator-button-back-poll">
								<span class="forminator-button--mask" aria-label="hidden"></span>
								<span class="forminator-button--text"><?php esc_attr_e( 'Back to poll', 'forminator' ); ?></span>
							</button>

						<?php else : ?>

							<button class="forminator-button forminator-button-back"><?php esc_attr_e( 'Back to poll', 'forminator' ); ?></button>

						<?php endif; ?>

					</div>

				<?php } ?>

			</form>

			<?php
			self::$graph_result_scripts[] = array(
				'model'     => $this->model,
				'container' => $chart_container,
			);

			$html = ob_get_clean();

			if ( $render ) {
				$html = apply_filters( 'forminator_render_form_success_markup', $html, $this->model );
				// Return form with canvas.
				echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			} else {
				return apply_filters( 'forminator_render_form_success_markup', $html, $this->model );
			}
		}
	}

	/**
	 * Graph scripts
	 *
	 * @return void
	 */
	public function graph_scripts() {

		foreach ( self::$graph_result_scripts as $graph_script ) {
			$this->success_footer_script( $graph_script['model'], $graph_script['container'] );
		}
	}

	/**
	 * Return chart type
	 *
	 * @return string
	 */
	public function get_chart_type() {
		$type          = 'none';
		$form_settings = $this->get_form_settings();

		if ( isset( $form_settings['results-behav'] ) && ( 'link_on' === $form_settings['results-behav'] || 'show_after' === $form_settings['results-behav'] ) ) {
			$type = $form_settings['results-style'];
		}

		return $type;
	}

	/**
	 * Get Options for google chart
	 *
	 * @param object $model Model.
	 *
	 * @return array
	 */
	public static function get_default_chart_options( $model ) {
		$chart_colors     = forminator_get_poll_chart_colors( $model->id );
		$chart_design     = 'bar';
		$pie_tooltip_text = 'percentage';
		$form_settings    = $model->settings;
		if ( isset( $form_settings['results-style'] ) ) {
			$chart_design = $form_settings['results-style'];
		}

		if ( isset( $form_settings['show-votes-count'] ) && $form_settings['show-votes-count'] ) {
			if ( 'pie' === $chart_design ) {
				$pie_tooltip_text = 'both';
			}
		}

		if ( 'pie' !== $chart_design ) {
			$chart_options = array(
				'annotations'     => array(
					'textStyle' => array(
						'fontSize' => 13,
						'bold'     => false,
						'color'    => '#333',
					),
				),
				'backgroundColor' => 'transparent',
				'fontSize'        => 13,
				'fontName'        => 'Roboto',
				'hAxis'           => array(
					'format'        => 'decimal',
					'baselineColor' => '#4D4D4D',
					'gridlines'     => array(
						'color' => '#E9E9E9',
					),
					'textStyle'     => array(
						'color'    => '#4D4D4D',
						'fontSize' => 13,
						'bold'     => false,
						'italic'   => false,
					),
					'minValue'      => 0,
				),
				'vAxis'           => array(
					'baselineColor' => '#4D4D4D',
					'gridlines'     => array(
						'color' => '#E9E9E9',
					),
					'textStyle'     => array(
						'color'    => '#4D4D4D',
						'fontSize' => 13,
						'bold'     => false,
						'italic'   => false,
					),
					'minValue'      => 0,
				),
				'tooltip'         => array(
					'isHtml' => true,
				),
				'legend'          => array(
					'position' => 'none',
				),
			);
		} else {
			$chart_options = array(
				'colors'          => $chart_colors,
				'backgroundColor' => 'transparent',
				'fontSize'        => 13,
				'fontName'        => 'Roboto',
				'tooltip'         => array(
					'isHtml'  => true,
					'trigger' => 'focus',
					'text'    => $pie_tooltip_text,
				),
				'chartArea'       => array(
					'width' => '90%',
				),
			);
		}

		return apply_filters( 'forminator_poll_chart_options', $chart_options, $model );
	}

	/**
	 * Success footer scripts
	 *
	 * @param object $model Model.
	 * @param string $container_id Container Id.
	 *
	 * @since 1.0
	 */
	public function success_footer_script( $model, $container_id ) {

		if ( ! is_object( $model ) ) {
			return '';
		}

		$form_settings = $model->settings;

		$chart_design = 'bar';

		if ( isset( $form_settings['results-style'] ) ) {
			$chart_design = $form_settings['results-style'];
		}

		$number_votes_enabled = (bool) false;

		if ( isset( $form_settings['show-votes-count'] ) && $form_settings['show-votes-count'] ) {
			$number_votes_enabled = (bool) true;
		}

		$chart_colors = forminator_get_poll_chart_colors( $model->id );

		$default_chart_colors = $chart_colors;
		$votes_count          = 'false';

		// Votes count.
		if ( isset( $form_settings['show-votes-count'] ) ) {
			$votes_count = $form_settings['show-votes-count'];
		}

		$chart_data = forminator_get_chart_data( $model );

		// Chart basic colors.
		$grids_color   = ( isset( $form_settings['grid_lines'] ) && ! empty( $form_settings['grid_lines'] ) ) ? $form_settings['grid_lines'] : '#E5E5E5';
		$labels_color  = ( isset( $form_settings['grid_labels'] ) && ! empty( $form_settings['grid_labels'] ) ) ? $form_settings['grid_labels'] : '#777771';
		$onchart_label = ( isset( $form_settings['onbar_votes'] ) && ! empty( $form_settings['onbar_votes'] ) ) ? $form_settings['onbar_votes'] : '#333333';

		// Tooltips.
		$tooltips_bg    = ( isset( $form_settings['tooltips_background'] ) && ! empty( $form_settings['tooltips_background'] ) && ! empty( $form_settings['poll-colors'] ) ) ? $form_settings['tooltips_background'] : '#333333';
		$tooltips_color = ( isset( $form_settings['tooltips_text'] ) && ! empty( $form_settings['tooltips_text'] ) && ! empty( $form_settings['poll-colors'] ) ) ? $form_settings['tooltips_text'] : '#FFFFFF';
		?>

		<script type="text/javascript">

			( function ( $, doc ) {

				'use strict';

				$(function() {

					var chartExtras = [
						'<?php echo esc_html_e( 'vote(s)', 'forminator' ); ?>',
						<?php echo esc_html( $votes_count ); ?>,
						[
							'<?php echo esc_html( $grids_color ); ?>',
							'<?php echo esc_html( $labels_color ); ?>',
							'<?php echo esc_html( $onchart_label ); ?>'
						],
						[
							'<?php echo esc_html( $tooltips_bg ); ?>',
							'<?php echo esc_html( $tooltips_color ); ?>'
						]
					];

					FUI.pollChart(
						'#<?php echo esc_attr( $container_id ); ?>',
						<?php echo wp_json_encode( $chart_data ); ?>,
						'<?php echo esc_html( $chart_design ); ?>',
						chartExtras
					);

					var chartCanvas  = $( '#<?php echo esc_attr( $container_id ); ?>' ),
						chartBody    = chartCanvas.closest( '.forminator-poll-body' ),
						chartWrapper = chartBody.find( '.forminator-chart-wrapper' )
						;

					if ( chartWrapper.length ) {

						chartCanvas.addClass( 'forminator-show' );

						chartWrapper.addClass( 'forminator-show' );
						chartWrapper.removeAttr( 'aria-hidden' );

						// If poll is added to sidebar widget, let's not add auto-scroll.
						if ( ! chartWrapper.parents( 'form' ).parent().hasClass( 'widget_forminator_widget' ) ) {
							chartWrapper.attr( 'tabindex', '-1' );
						}

						chartWrapper.focus();

					}

				});

			}( jQuery, document ) );

		</script>

		<?php
	}

	/**
	 * Return if view votes setting is enabled
	 *
	 * @since 1.0
	 * @return bool
	 */
	public function has_votes_enabled() {
		$settings = $this->get_form_settings();
		if ( isset( $settings['show-votes-count'] ) && $settings['show-votes-count'] ) {
			return true;
		}

		return false;
	}

	/**
	 * Get CSS prefix
	 *
	 * @param string $prefix Default prefix.
	 * @param array  $properties CSS properties.
	 * @param string $slug Slug.
	 * @return string
	 */
	protected static function get_css_prefix( $prefix, $properties, $slug ) {
		if ( ! isset( $properties['forminator-poll-design'] ) || 'none' !== $properties['forminator-poll-design'] ) {
			$prefix .= ' ';
		}
		return $prefix;
	}

	/**
	 * Html markup of form
	 *
	 * @since 1.6.1
	 *
	 * @param bool $hide Hide.
	 * @param bool $is_preview Is preview.
	 *
	 * @return false|string
	 */
	public function get_html( $hide = true, $is_preview = false ) {
		ob_start();

		$is_same_form   = false;
		$is_same_render = false;
		$rendered       = false;
		$form_id        = Forminator_Core::sanitize_text_field( 'form_id' );
		$render_id      = Forminator_Core::sanitize_text_field( 'render_id' );
		$saved          = Forminator_Core::sanitize_text_field( 'saved' );
		$results        = Forminator_Core::sanitize_text_field( 'results' );
		if ( (int) $form_id === (int) $this->model->id ) {
			$is_same_form = true;
		}

		if ( (int) $render_id === (int) self::$render_ids[ $this->model->id ] ) {
			$is_same_render = true;
		}

		$status_info = $this->model->opening_status();

		if ( 'open' !== $status_info['status'] ) {
			$this->track_views = false;
			$this->render( $this->model->id, $hide, $is_preview );
			$rendered = true;
		} elseif ( $saved && $is_same_form && $is_same_render && $this->show_results() ) {
				$this->track_views = false;
				$this->render_success();
		} elseif ( $results && $is_same_form && $is_same_render && $this->show_link() ) {
			$this->track_views = false;
			$this->render_success();
		} elseif ( ( ! $this->is_admin || $is_preview ) && ( ! $this->model->current_user_can_vote() && ( $this->show_results() || $this->show_link() ) ) ) {
			$this->track_views = false;
			$this->render_success();
		} else {
			$this->render( $this->model->id, $hide, $is_preview );

			$rendered = true;
		}

		$this->set_forms_properties( $rendered );

		$html = ob_get_clean();

		return $html;
	}

	/**
	 * Set module properties
	 *
	 * @param bool $rendered Rendered flag.
	 */
	protected function set_forms_properties( $rendered = false ) {
		$this->forms_properties[] = array(
			'id'            => $this->model->id,
			'render_id'     => ! empty( self::$render_ids[ $this->model->id ] )
					? self::$render_ids[ $this->model->id ] : 0,
			'settings'      => $this->get_form_settings(),
			'chart_design'  => self::get_chart_design( $this->get_form_settings() ),
			'chart_options' => self::get_default_chart_options( $this->model ),
			'rendered'      => $rendered,
		);
	}

	/**
	 * Set options to Model object.
	 *
	 * @param object $form_model Model.
	 * @param array  $data Data.
	 * @return object
	 */
	protected function set_form_model_data( $form_model, $data ) {
		$answers = array();

		// Check if answers exist.
		if ( isset( $data['answers'] ) ) {
			$answers = forminator_sanitize_field( $data['answers'] );
		}
		foreach ( $answers as $answer ) {
			$field_model  = new Forminator_Form_Field_Model();
			$answer['id'] = $answer['element_id'];
			$field_model->import( $answer );
			$field_model->slug = $answer['element_id'];
			$form_model->add_field( $field_model );
		}

		return $form_model;
	}

	/**
	 * Enqueue poll scripts
	 *
	 * @param bool $is_preview Is preview.
	 * @param bool $is_ajax_load Is ajax load.
	 */
	public function enqueue_form_scripts( $is_preview, $is_ajax_load = false ) {
		ob_start();
		$this->graph_scripts();
		$this->script = ob_get_clean();
	}

	/**
	 * Get forminatorFront js init options to be passed
	 *
	 * @since 1.6.1
	 *
	 * @param array $form_properties Form properties.
	 *
	 * @return array
	 */
	public function get_front_init_options( $form_properties ) {

		if ( empty( $form_properties ) ) {
			return array();
		}

		$options = array(
			'form_type'     => $this->get_form_type(),
			'chart_design'  => $form_properties['chart_design'],
			'chart_options' => $form_properties['chart_options'],
		);

		return $options;
	}
}
