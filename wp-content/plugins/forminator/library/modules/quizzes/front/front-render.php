<?php
/**
 * The Forminator_QForm_Front class.
 *
 * @package Forminator
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Front render class for custom forms
 */
class Forminator_QForm_Front extends Forminator_Render_Form {

	/**
	 * Module slug
	 *
	 * @var string
	 */
	protected static $module_slug = 'quiz';

	/**
	 *  Lead data
	 *
	 * @var array
	 */
	protected static $lead_data = array();

	/**
	 * Steps count
	 *
	 * @var int
	 */
	private static $steps_count = 1;

	/**
	 * Display form method
	 *
	 * @since 1.0
	 *
	 * @param int        $id Id.
	 * @param bool       $is_preview Is preview.
	 * @param bool|array $data Data.
	 * @param bool       $hide If true, display: none will be added on the form markup and later removed with JS.
	 */
	public function display( $id, $is_preview = false, $data = false, $hide = true ) {
		$this->model = Forminator_Base_Form_Model::get_model( $id );
		if ( ! $this->model instanceof Forminator_Quiz_Model ) {
			return;
		}

		$this->set_lead_data();

		echo $this->lead_wrapper_start(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		if ( $this->model->form_is_visible( $is_preview ) ) {
			$version     = FORMINATOR_VERSION;
			$module_type = 'quiz';

			if ( $data && ! empty( $data ) ) {
				// New form, we have to update the form id.
				$has_id = filter_var( $id, FILTER_VALIDATE_BOOLEAN );

				if ( ! $has_id && isset( $data['settings']['form_id'] ) ) {
					$id = $data['settings']['form_id'];
				}

				$this->model = Forminator_Quiz_Model::model()->load_preview( $id, $data );
				// its preview!
				$this->model->id = $id;

				/*
				// If this module haven't been saved, the preview will be of the wrong module.
				// if ( ! isset( $data['settings']['quiz_title'] ) || $data['settings']['quiz_title'] !== $this->model->settings['quiz_title'] ) {.
				// echo $this->message_save_to_preview();.

				// return;.
				// }.
				*/
			}

			$this->maybe_define_cache_constants();

			// TODO: make preview and ajax load working similar.
			$is_ajax_load = $this->is_ajax_load( $is_preview );

			// Load assets conditionally.
			$assets = new Forminator_Assets_Enqueue_Quiz( $this->model, $is_ajax_load );
			$assets->enqueue_styles();
			$assets->enqueue_scripts();

			if ( $is_ajax_load ) {
				$this->generate_render_id( $id );
				$this->get_form_placeholder( esc_attr( $id ), true );

				if ( $this->has_lead() && ! $is_preview ) {
					$custom_form_view = Forminator_CForm_Front::get_instance();
					$custom_form_view->display( $this->get_leads_id(), $is_preview, $data, true, $this->model );
				}

				return;
			}

			if ( $this->is_displayable( $is_preview ) ) {

				$this->generate_render_id( $id );

				echo $this->get_html( $hide, $is_preview ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

				if ( is_admin() || $is_preview ) {
					$this->print_styles();
				}

				$google_fonts = $this->get_google_fonts();

				foreach ( $google_fonts as $font_name ) {
					if ( ! empty( $font_name ) ) {
						wp_enqueue_style( 'forminator-font-' . sanitize_title( $font_name ), 'https://fonts.bunny.net/css?family=' . $font_name, array(), '1.0' );
					}
				}

				add_action( 'wp_footer', array( $this, 'forminator_render_front_scripts' ), 9999 );
			}

			if ( $this->has_lead() && ! $is_preview && ! $is_ajax_load ) {
				$custom_form_view = Forminator_CForm_Front::get_instance();
				$custom_form_view->display( $this->get_leads_id(), $is_preview, $data, true, $this->model );
			}
		} else {
			$form_settings = $this->get_form_settings();
			?>
			<div class="forminator-quiz">
				<?php
				if ( isset( $form_settings['expire_message'] ) && '' !== $form_settings['expire_message'] ) {
					$message = $form_settings['expire_message'];
					?>
					<label class="forminator-label--info"><span><?php echo esc_html( $message ); ?></span></label>
				<?php } ?>
			</div>
					<?php
		}

		echo wp_kses_post( $this->lead_wrapper_end() );
	}

	/**
	 * Set lead data
	 */
	private function set_lead_data() {
		$lead_data = array();
		if ( $this->has_lead() ) {
			$lead_data['has_lead'] = $this->has_lead();
			$lead_data['leads_id'] = $this->get_leads_id();
		}

		static::$lead_data = $lead_data;
	}

	/**
	 * Return fields
	 *
	 * @since 1.0
	 * @return array
	 */
	public function get_fields() {
		return $this->model->questions;
	}

	/**
	 * Return form fields markup
	 *
	 * @since 1.0
	 *
	 * @param bool $render Render.
	 *
	 * @return mixed
	 */
	public function render_fields( $render = true ) {

		$form_settings   = $this->get_form_settings();
		$with_pagination = ! empty( $form_settings['pagination'] );
		$module_id       = $this->model->id;

		$html = '';

		$fields     = $this->get_fields();
		$num_fields = count( $fields );

		if ( $with_pagination ) {
			$step      = 1;
			$next_step = 1;
			$new_step  = true;

			$page_indicator     = ! isset( $form_settings['page-indicator'] ) || $form_settings['page-indicator'];
			$questions_per_page = empty( $form_settings['questions-per-page'] ) || 1 > $form_settings['questions-per-page']
					? 1 : $form_settings['questions-per-page'];
			self::$steps_count  = ceil( $num_fields / $questions_per_page );
		}

		$i = 0;

		foreach ( $fields as $key => $field ) {

			$last_field = false;

			if ( ++$i === $num_fields ) {
				$last_field = true;
			}

			do_action( 'forminator_before_field_render', $field );

			if ( $with_pagination ) {
				$step = ceil( $i / $questions_per_page );
				if ( $new_step ) {
					$new_step = false;
					$html    .= self::pagination_content_start( $module_id, $step - 1, $page_indicator );
				}
			}
				// Render field.
				$html .= $this->render_field( $field, $last_field );

			if ( $with_pagination ) {
				$next_step = ceil( ( $i + 1 ) / $questions_per_page );
				if ( $last_field || $step !== $next_step ) {
					$new_step = true;
					$html    .= self::pagination_content_end();
				}
			}
			do_action( 'forminator_after_field_render', $field );
		}

		if ( $render ) {
			echo wp_kses_post( $html );
		} else {
			return apply_filters( 'forminator_render_fields_markup', $html, $fields );
		}
	}

	/**
	 * Return pagination content start markup
	 *
	 * @param int $module_id Module Id.
	 * @param int $step Step.
	 * @param int $page_indicator Page indicator.
	 *
	 * @return string
	 */
	private static function pagination_content_start( $module_id, $step, $page_indicator ) {
		/* translators: 1. Step. */
		$label = sprintf( esc_html__( 'Page %s', 'forminator' ), $step );

		$html  = '<div role="tabpanel" id="forminator-custom-form-' . esc_attr( $module_id ) . '--page-' . esc_attr( $step ) . '" '
				. 'class="forminator-pagination forminator-hidden" '
				. 'aria-labelledby="forminator-custom-form-' . esc_attr( $module_id ) . '--page-' . esc_attr( $step ) . '-label" '
				. 'data-step="' . esc_attr( $step ) . '" data-label="' . esc_attr( $label ) . '">';
		$html .= '<div class="forminator-pagination--content">';
		if ( $page_indicator ) {
			$html .= '<div class="forminator-page-indicator"><span>';
			/* translators: 1. Step, 2. Total steps. */
			$html .= esc_html( sprintf( esc_html__( 'Page %1$s of %2$s', 'forminator' ), $step + 1, self::$steps_count ) );
			$html .= '</span></div>';
		}

		return apply_filters( 'forminator_quiz_pagination_content_start_markup', $html );
	}

	/**
	 * Return pagination end markup
	 *
	 * @return string
	 */
	private static function pagination_content_end() {
		$html = '</div></div>';

		return apply_filters( 'forminator_quiz_pagination_end_markup', $html );
	}

	/**
	 * Render field
	 *
	 * @since 1.0
	 *
	 * @param array $field Field.
	 * @param bool  $last_field Last field.
	 *
	 * @return mixed
	 */
	public function render_field( $field, $last_field = false ) {

		if ( isset( $field['type'] ) && 'knowledge' === $field['type'] ) {
			$html = $this->render_knowledge( $field, $last_field );
		} else {
			$html = $this->render_nowrong( $field, $last_field );
		}

		return apply_filters( 'forminator_field_markup', $html, $field, $this );
	}

	/**
	 * Render No wrong quiz
	 *
	 * @since 1.0
	 *
	 * @param array $field Field.
	 * @param bool  $last_field Last field.
	 *
	 * @return string
	 */
	private function render_nowrong( $field, $last_field ) {

		ob_start();

		$class         = '';
		$uniq_id       = '-' . uniqid();
		$field_slug    = uniqid();
		$form_settings = $this->get_form_settings();
		$form_design   = $this->get_quiz_theme();

		// Make sure slug key exist.
		if ( isset( $field['slug'] ) ) {
			$field_slug = $field['slug'];
		}

		$answers         = isset( $field['answers'] ) ? $field['answers'] : '';
		$has_answers     = ( isset( $answers ) && ! empty( $answers ) );
		$with_pagination = ! empty( $form_settings['pagination'] );
		?>

		<div
			tabindex="0"
			role="radiogroup"
			id="<?php echo esc_attr( $field_slug ); ?>"
			class="forminator-question<?php echo ( true === $last_field && ! $with_pagination ) ? ' forminator-last' : ''; ?>"
			data-question-type="<?php echo ( isset( $field['type'] ) && 'knowledge' === $field['type'] ) ? 'knowledge' : 'personality'; ?>"
			aria-labelledby="<?php echo esc_attr( $field_slug ) . '-label'; ?>"
			aria-describedby="<?php echo esc_attr( $field_slug ) . '-description'; ?>"
			aria-required="true"
		>

			<?php echo wp_kses_post( $this->get_question_intro( $field, $field_slug ) ); ?>

			<?php
			if ( $has_answers ) {

				foreach ( $answers as $k => $answer ) {

					$answer_id     = $field_slug . '-' . $k . $uniq_id;
					$label         = isset( $answer['title'] ) ? $answer['title'] : '';
					$image         = isset( $answer['image'] ) ? $answer['image'] : '';
					$image_alt     = isset( $answer['image_filename'] ) ? $answer['image_filename'] : '';
					$has_label     = isset( $label ) && '' !== $label;
					$has_image     = ( isset( $image ) && ! empty( $image ) );
					$has_image_alt = ( isset( $image_alt ) && ! empty( $image_alt ) );

					if ( $has_label && $has_image ) {
						$empty_class = '';
					} elseif ( $has_image ) {
							$empty_class = ' forminator-only--image';
					} elseif ( $has_label ) {
						$empty_class = ' forminator-only--text';
					} else {
						$empty_class = ' forminator-empty';
					}
					?>

					<label for="<?php echo esc_attr( $answer_id ); ?>" class="forminator-answer<?php echo esc_attr( $empty_class ); ?>">

						<input
							type="radio"
							name="answers[<?php echo esc_attr( $field_slug ); ?>]"
							value="<?php echo esc_attr( $k ); ?>"
							id="<?php echo esc_attr( $answer_id ); ?>"
							class="<?php echo esc_attr( $class ); ?>"
						/>

						<?php
						if ( 'none' !== $form_design ) {
							echo '<span class="forminator-answer--design" for="' . esc_attr( $answer_id ) . '">';
						}
						?>

						<?php if ( $has_image ) : ?>

							<?php if ( $has_image_alt ) { ?>
								<span
									class="forminator-answer--image"
									style="background-image: url('<?php echo esc_attr( $image ); ?>');"
								>
									<span><?php echo esc_html( $image_alt ); ?></span>
								</span>
							<?php } else { ?>
								<span
									class="forminator-answer--image"
									style="background-image: url('<?php echo esc_attr( $image ); ?>');"
									aria-hidden="true"
								></span>
							<?php } ?>

						<?php endif; ?>

						<span class="forminator-answer--status" aria-hidden="true">
							<i class="forminator-icon-check"></i>
						</span>

						<?php if ( $has_label ) : ?>
							<span class="forminator-answer--name"><?php echo esc_html( $label ); ?></span>
						<?php endif; ?>

						<?php
						if ( 'none' !== $form_design ) {
							echo '</span>';
						}
						?>

					</label>

					<?php
				}
			}
			?>

		</div>

		<?php
		return ob_get_clean();
	}

	/**
	 * Render knowledge quiz
	 *
	 * @since 1.0
	 *
	 * @param array $field Field.
	 * @param bool  $last_field Last field.
	 *
	 * @return string
	 */
	private function render_knowledge( $field, $last_field ) {

		ob_start();

		$result_behav  = isset( $this->model->settings['results_behav'] ) ? $this->model->settings['results_behav'] : '';
		$class         = ( ( isset( $result_behav ) && 'end' === $result_behav ) || 'end' === $this->get_form_placement() ) ? '' : 'forminator-submit-rightaway';
		$input_type    = ( isset( $result_behav ) && 'end' === $result_behav ) ? 'checkbox' : 'radio';
		$uniq_id       = '-' . uniqid();
		$field_slug    = uniqid();
		$form_settings = $this->get_form_settings();
		$form_design   = $this->get_quiz_theme();

		// Make sure slug key exist.
		if ( isset( $field['slug'] ) ) {
			$field_slug = $field['slug'];
		}

		$answers         = $field['answers'];
		$has_answers     = ( isset( $answers ) && ! empty( $answers ) );
		$role            = 'radio' === $input_type ? 'radiogroup' : 'checkbox';
		$with_pagination = ! empty( $form_settings['pagination'] );
		?>

		<div
			tabindex="0"
			role="<?php echo esc_attr( $role ); ?>"
			id="<?php echo esc_attr( $field_slug ); ?>"
			class="forminator-question<?php echo ( true === $last_field && ! $with_pagination ) ? ' forminator-last' : ''; ?>"
			data-multichoice="<?php echo esc_attr( $this->is_muti_choice( $answers ) ); ?>"
			aria-labelledby="<?php echo esc_attr( $field_slug ) . '-label'; ?>"
			aria-describedby="<?php echo esc_attr( $field_slug ) . '-description'; ?>"
			aria-required="true"
		>

			<?php echo wp_kses_post( $this->get_question_intro( $field, $field_slug ) ); ?>

			<?php
			if ( $has_answers ) {

				foreach ( $answers as $k => $answer ) {

					$answer_id     = $field_slug . '-' . $k . $uniq_id;
					$label         = $answer['title'];
					$input_name    = 'end' === $result_behav ? $field_slug . '-' . $k : $field_slug;
					$image         = isset( $answer['image'] ) ? $answer['image'] : '';
					$image_alt     = isset( $answer['image_filename'] ) ? $answer['image_filename'] : '';
					$has_label     = isset( $label ) && '' !== $label;
					$has_image     = ( ! empty( $image ) );
					$has_image_alt = ( isset( $image_alt ) && ! empty( $image_alt ) );

					if ( $has_label && $has_image ) {
						$empty_class = '';
					} elseif ( $has_image ) {
							$empty_class = ' forminator-only--image';
					} elseif ( $has_label ) {
						$empty_class = ' forminator-only--text';
					} else {
						$empty_class = ' forminator-empty';
					}
					?>

					<label for="<?php echo esc_attr( $answer_id ); ?>" class="forminator-answer<?php echo esc_attr( $empty_class ); ?>">

						<input
							type="<?php echo esc_attr( $input_type ); ?>"
							name="answers[<?php echo esc_attr( $input_name ); ?>]"
							value="<?php echo esc_attr( $k ); ?>"
							id="<?php echo esc_attr( $answer_id ); ?>"
							class="<?php echo esc_attr( $class ); ?>"
						/>

						<?php
						if ( 'none' !== $form_design ) {
							echo '<span class="forminator-answer--design" for="' . esc_attr( $answer_id ) . '">';
						}
						?>

						<?php if ( $has_image ) : ?>

							<?php if ( $has_image_alt ) { ?>
								<span
									class="forminator-answer--image"
									style="background-image: url('<?php echo esc_attr( $image ); ?>');"
								>
									<span><?php echo esc_html( $image_alt ); ?></span>
								</span>
							<?php } else { ?>
								<span
									class="forminator-answer--image"
									style="background-image: url('<?php echo esc_attr( $image ); ?>');"
									aria-hidden="true"
								></span>
							<?php } ?>

						<?php endif; ?>

						<span class="forminator-answer--status" aria-hidden="true"></span>

						<?php if ( $has_label ) : ?>
							<span class="forminator-answer--name"><?php echo esc_html( $label ); ?></span>
						<?php endif; ?>

						<?php
						if ( 'none' !== $form_design ) {
							echo '</span>';
						}
						?>

					</label>

					<?php
				}
			}
			?>

			<span id="<?php echo esc_attr( $field_slug ) . '-description'; ?>" class="forminator-question--result"></span>

		</div><?php // END .forminator-question. ?>

		<?php
		$html = ob_get_clean();

		return $html;
	}

	/**
	 * Returns true if there is more than 1 correct answer
	 *
	 * @param array $answers Array of answers.
	 * @return string
	 */
	private function is_muti_choice( $answers ) {
		$count = 0;

		foreach ( $answers as $answer ) {
			if ( isset( $answer['toggle'] ) && filter_var( $answer['toggle'], FILTER_VALIDATE_BOOLEAN ) ) {
				++$count;
			}
		}

		return $count > 1 ? 'true' : 'false';
	}

	/**
	 * Returns question intro according the order.
	 *
	 * @param array  $field Field data.
	 * @param string $field_slug Field slug.
	 * @return string
	 */
	private function get_question_intro( $field, $field_slug ) {
		global $wp_embed;
		$question    = $field['title'];
		$description = isset( $field['question_description'] ) ? wp_kses_post( $field['question_description'] ) : '';
		$image       = isset( $field['image'] ) ? $field['image'] : '';
		$image_alt   = isset( $field['image_alt'] ) ? $field['image_alt'] : '';

		$question = '<span id="' . esc_attr( $field_slug ) . '-label" class="forminator-legend">' . esc_html( $question ) . '</span>';

		if ( $description ) {
			$description = '<div class="forminator-question--description">' . wp_kses_post( $wp_embed->autoembed( $description ) ) . '</div>';
		}

		if ( ! empty( $image ) ) {
			$image = '<div class="forminator-image"' . ( ! empty( $image_alt ) ? '' : ' aria-hidden="true"' ) . '>' .
				'<img src="' . esc_url( $field['image'] ) . '"' .
					( ! empty( $image_alt ) ? ' alt="' . esc_attr( $image_alt ) . '"' : '' ) . ' />' .
			'</div>';
		}

		$order = ! empty( $this->model->settings['order'] )
				? $this->model->settings['order'] : array();

		$incorrect_items = array_diff( array( 'question', 'description', 'image' ), $order );
		if ( empty( $order ) || $incorrect_items ) {
			$html = $question . $description . $image;
		} else {
			$html = '';
			foreach ( $order as $item ) {
				$html .= $$item;
			}
		}

		return $html;
	}

	/**
	 * Return Save to preview message
	 *
	 * @since 1.0
	 * @return mixed
	 */
	public function message_save_to_preview() {
		return esc_html__( 'Please, save the quiz in order to preview it.', 'forminator' );
	}

	/**
	 * Return form design
	 *
	 * @since 1.0
	 * @since 1.2 Added Theme and Filter
	 * @return mixed|string
	 */
	public function get_form_design() {
		$form_settings = $this->get_form_settings();

		$form_design = '';

		$visual_style = 'list';
		if ( isset( $form_settings['visual_style'] ) ) {
			$visual_style = $form_settings['visual_style'];
		}

		$form_design .= $visual_style;

		$quiz_theme = $this->get_quiz_theme();
		if ( 'none' !== $quiz_theme ) {
			$form_design .= ' forminator-design--' . $quiz_theme;
		}

		return $form_design;
	}

	/**
	 * Render quiz header
	 *
	 * @param string $maybe_error Message.
	 *
	 * @since 1.0
	 * @return string
	 */
	public function render_form_header( $maybe_error = '' ) {
		global $wp_embed;
		ob_start();

		// TO-DO: Get featured image alt text.
		$feat_image_alt = ! empty( $this->model->settings['quiz_feat_image_alt'] ) ? $this->model->settings['quiz_feat_image_alt'] : '';
		?>

		<?php if ( isset( $this->model->settings['quiz_name'] ) && ! empty( $this->model->settings['quiz_name'] ) ) : ?>
			<h3 class="forminator-quiz--title"><?php echo esc_html( $this->model->settings['quiz_name'] ); ?></h3>
		<?php endif; ?>

		<?php if ( isset( $this->model->settings['quiz_feat_image'] ) && ! empty( $this->model->settings['quiz_feat_image'] ) ) : ?>
			<img
				src="<?php echo esc_url( $this->model->settings['quiz_feat_image'] ); ?>"
				class="forminator-quiz--image"
				<?php echo ( '' !== $feat_image_alt ) ? 'alt="' . esc_attr( $feat_image_alt ) . '"' : 'aria-hidden="true"'; ?>
			/>
		<?php endif; ?>

		<?php
		if ( isset( $this->model->settings['quiz_description'] ) && ! empty( $this->model->settings['quiz_description'] ) ) :

			$content = forminator_replace_variables( $this->model->settings['quiz_description'], $this->model->id );

			if ( stripos( $content, '{quiz_name}' ) !== false ) :
				$quiz_name = forminator_get_name_from_model( $this->model );
				$content   = str_ireplace( '{quiz_name}', $quiz_name, $content );
			endif;
			?>
			<div class="forminator-quiz--description"><?php echo wp_kses_post( $wp_embed->autoembed( $content ) ); ?></div>
		<?php endif; ?>

		<?php
		$intro = ob_get_clean();

		if ( ! empty( $this->model->settings['pagination'] ) ) {
			$start_text = $this->get_start_button_text();
			$html       = '<div class="forminator-quiz-intro">' . $intro;
			if ( ! $this->has_lead() || 'beginning' !== $this->get_form_placement() ) {
				$html .= '<button class="forminator-button forminator-quiz-start" style="margin: 0 auto; display: block;">' . esc_html( $start_text ) . '</button>';
			}
			$html .= '</div>';
		} else {
			$html = $intro;
		}

		return $html;
	}

	/**
	 * Get submit data
	 *
	 * @return array
	 */
	public function get_submit_data() {
		$settings = $this->get_form_settings();

		$data = array(
			'class'   => '',
			'label'   => esc_html__( 'Ready to send', 'forminator' ),
			'loading' => esc_html__( 'Calculating Result', 'forminator' ),
		);

		// Submit data is missing.
		if ( ! isset( $settings['submitData'] ) ) {
			return $data;
		}

		if ( isset( $settings['submitData']['button-text'] ) && ! empty( $settings['submitData']['button-text'] ) ) {
			$data['label'] = $settings['submitData']['button-text'];
		}

		if ( isset( $settings['submitData']['button-processing-text'] ) && ! empty( $settings['submitData']['button-processing-text'] ) ) {
			$data['loading'] = $settings['submitData']['button-processing-text'];
		}

		if ( isset( $settings['submitData']['custom-class'] ) ) {
			$data['class'] = $settings['submitData']['custom-class'];
		}

		return $data;
	}

	/**
	 * Return form submit button markup
	 *
	 * @since 1.0
	 *
	 * @param int  $form_id Form id.
	 * @param bool $render Render.
	 *
	 * @return mixed
	 */
	public function get_submit( $form_id, $render = true ) {

		// FIX:.
		// https://app.asana.com/0/385581670491499/789649735369091/f.
		$disabled = '';
		$html     = '';

		if ( $this->is_preview ) {
			$disabled = 'aria-disabled="true" disabled="disabled"';
		}

		$nonce   = $this->nonce_field( 'forminator_submit_form' . $form_id, 'forminator_nonce' );
		$post_id = $this->get_post_id();

		$submit_data  = $this->get_submit_data();
		$settings     = $this->model->settings ?? array();
		$pagination   = ! empty( $settings['pagination'] );
		$result_behav = $settings['results_behav'] ?? '';
		$lead_result  = 'beginning' === $this->get_form_placement() ? $result_behav : 'end';
		$current_url  = $this->is_ajax_load( $this->is_preview ) && isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : forminator_get_current_url();
		$classes      = $submit_data['class'];

		if ( $pagination ) {
			$classes .= ' forminator-pagination-submit';
			$html    .= self::pagination_content_start( $form_id, self::$steps_count, false );
		}
		$html .= '<div class="forminator-quiz--result">';
		$label = esc_html( $submit_data['label'] );
		if ( 'knowledge' === $this->model->quiz_type && $this->has_lead() && 'end' === $lead_result ) {

			if ( 'material' === $this->get_quiz_theme() ) {

				$html .= sprintf(
					'<button class="forminator-button forminator-button-submit %s" %s data-loading="%s" aria-live="polite"><span class="forminator-button--mask" aria-label="hidden"></span><span class="forminator-button--text">%s</span></button>',
					esc_attr( $classes ),
					$disabled,
					esc_attr( $submit_data['loading'] ),
					$label
				);
			} else {

				$html .= sprintf(
					'<button class="forminator-button forminator-button-submit %s" data-loading="%s" %s>%s</button>',
					esc_attr( $classes ),
					esc_attr( $submit_data['loading'] ),
					$disabled,
					$label
				);
			}
		} elseif ( 'nowrong' === $this->model->quiz_type || 'end' === $result_behav ) {

			if ( 'material' === $this->get_quiz_theme() ) {

				$html .= sprintf(
					'<button class="forminator-button forminator-button-submit %s" %s data-loading="%s" aria-live="polite"><span class="forminator-button--mask" aria-label="hidden"></span><span class="forminator-button--text">%s</span></button>',
					esc_attr( $classes ),
					$disabled,
					esc_attr( $submit_data['loading'] ),
					$label
				);
			} else {

				$html .= sprintf(
					'<button class="forminator-button forminator-button-submit %s" data-loading="%s" %s>%s</button>',
					esc_attr( $classes ),
					esc_attr( $submit_data['loading'] ),
					$disabled,
					$label
				);
			}
		}

		$html .= '</div>';

		if ( $pagination ) {
			$html .= self::pagination_content_end();
		}

		$html .= $nonce;
		$html .= sprintf( '<input type="hidden" name="has_lead" value="%s">', $this->has_lead() );
		$html .= sprintf( '<input type="hidden" name="form_id" value="%s">', intval( $form_id ) );
		$html .= sprintf( '<input type="hidden" name="page_id" value="%s">', intval( $post_id ) );
		$html .= sprintf( '<input type="hidden" name="current_url" value="%s">', esc_url( $current_url ) );

		if ( $this->has_lead() ) {
			$html .= sprintf( '<input type="hidden" name="entry_id" value="">' );
		}

		if ( $this->is_preview ) {
			$html .= '<input type="hidden" name="action" value="forminator_submit_preview_form_quizzes" />';
		} else {
			$html .= '<input type="hidden" name="action" value="forminator_submit_form_quizzes" />';
		}

		$html = apply_filters( 'forminator_render_form_submit_markup', $html, $form_id, $post_id, $nonce, $settings );
		if ( $render ) {
			echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		} else {
			return $html;
		}
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
		$theme = $this->get_quiz_theme();

		if ( isset( $this->model->quiz_type ) && 'knowledge' === $this->model->quiz_type ) {

			if ( 'none' !== $theme ) {
				return realpath( forminator_plugin_dir() . '/assets/js/front/templates/quiz/knowledge/global.html' );
			} else {
				return realpath( forminator_plugin_dir() . '/assets/js/front/templates/quiz/knowledge/grid.html' );
			}
		} elseif ( 'none' !== $theme ) {

				return realpath( forminator_plugin_dir() . '/assets/js/front/templates/quiz/nowrong/global.html' );
		} else {
			return realpath( forminator_plugin_dir() . '/assets/js/front/templates/quiz/nowrong/grid.html' );
		}
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

		if ( isset( $properties['hasLeads'] ) && filter_var( $properties['hasLeads'], FILTER_VALIDATE_BOOLEAN ) ) {
			$prefix = '.forminator-ui#forminator-quiz-leads-' . $properties['form_id'];
		} else {
			$prefix = '.forminator-ui.forminator-' . $slug . '-' . $properties['form_id'];
		}

		return $prefix . ' ';
	}

	/**
	 * Get Quiz Theme
	 *
	 * @since 1.2
	 * @return string
	 */
	public function get_quiz_theme() {
		$quiz_theme = 'default';
		$settings   = $this->get_form_settings();
		if ( isset( $settings['forminator-quiz-theme'] ) && ! empty( $settings['forminator-quiz-theme'] ) ) {
			$quiz_theme = $settings['forminator-quiz-theme'];
		}

		$quiz_id = $this->get_module_id();

		/**
		 * Filter Quiz Theme to be used
		 *
		 * @since 1.2
		 *
		 * @param string $quiz_theme ,
		 * @param int    $quiz_id
		 * @param array  $settings   quiz settings.
		 */
		$quiz_theme = apply_filters( 'forminator_quiz_theme', $quiz_theme, $quiz_id, $settings );

		return $quiz_theme;
	}

	/**
	 * Get Google Fonts setup on a quiz
	 *
	 * @since 1.2
	 * @return array
	 */
	public function get_google_fonts() {
		$fonts     = array();
		$settings  = $this->get_form_settings();
		$quiz_id   = $this->get_module_id();
		$quiz_type = $this->model->quiz_type;

		$custom_typography_enabled = false;
		// on clean design, disable google fonts.
		if ( 'none' !== $this->get_quiz_theme() ) {

			if ( 'nowrong' === $quiz_type ) {
				if ( isset( $settings['nowrong-toggle-typography'] ) ) {
					$custom_typography_enabled = filter_var( $settings['nowrong-toggle-typography'], FILTER_VALIDATE_BOOLEAN );
				}
			} elseif ( 'knowledge' === $quiz_type ) {
				if ( isset( $settings['knowledge-toggle-typography'] ) ) {
					$custom_typography_enabled = filter_var( $settings['knowledge-toggle-typography'], FILTER_VALIDATE_BOOLEAN );
				}
			}

			foreach ( $settings as $setting_key => $font_family_name ) {
				if ( ! strpos( $setting_key, '-font-family' ) ) {
					continue;
				}
				if ( ! $custom_typography_enabled || empty( $font_family_name ) || 'custom' === $font_family_name ) {
					$fonts[ $setting_key ] = false;
					continue;
				}

				$fonts[ $setting_key ] = $font_family_name;
			}
		}

		/**
		 * Filter google fonts to be loaded for a quiz
		 *
		 * @since 1.2
		 *
		 * @param array  $fonts
		 * @param int    $quiz_id
		 * @param string $quiz_type (nowrong|knowledge).
		 * @param array  $settings  quiz settings.
		 */
		$fonts = apply_filters( 'forminator_quiz_google_fonts', $fonts, $quiz_id, $quiz_type, $settings );

		$fonts = array_unique( $fonts );

		return $fonts;
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

		$id = $this->get_module_id();

		$this->render( $id, $hide, $is_preview );

		$this->set_forms_properties();

		$html = ob_get_clean();

		return $html;
	}

	/**
	 * Set module properties
	 */
	protected function set_forms_properties() {
		$id = $this->get_module_id();

		$this->forms_properties[] = array(
			'id'             => $id,
			'render_id'      => ! empty( self::$render_ids[ $id ] )
					? self::$render_ids[ $id ] : 0,
			'settings'       => $this->get_form_settings(),
			'fonts_settings' => $this->get_google_fonts(),
			'rendered'       => true,
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
		$questions = array();
		$results   = array();
		$msg_count = false;

		$title = isset( $data['settings']['quiz_name'] ) ? sanitize_text_field( $data['settings']['quiz_name'] ) : sanitize_text_field( $data['settings']['formName'] );

		// Detect action.
		$action = isset( $data['type'] ) ? sanitize_text_field( $data['type'] ) : '';
		if ( 'knowledge' === $action ) {
			$form_model->quiz_type = 'knowledge';
		} elseif ( 'nowrong' === $action ) {
			$form_model->quiz_type = 'nowrong';
		} else {
			return array();
		}

		// Check if results exist.
		if ( isset( $data['results'] ) && is_array( $data['results'] ) ) {
			$results = forminator_sanitize_array_field( $data['results'] );
			foreach ( $data['results'] as $key => $result ) {
				$description = '';
				if ( isset( $result['description'] ) ) {
					$description = wp_kses_post( $result['description'] );
				}
				$results[ $key ]['description'] = $description;
			}

			$form_model->results = $results;
		}

		// Check if answers exist.
		if ( isset( $data['questions'] ) ) {
			$questions = forminator_sanitize_array_field( $data['questions'] );

			// Check if questions exist.
			if ( isset( $questions ) ) {
				foreach ( $questions as &$question ) {
					$question['type'] = $form_model->quiz_type;
					if ( ! isset( $question['slug'] ) || empty( $question['slug'] ) ) {
						$question['slug'] = uniqid();
					}
				}
			}
		}

		// Handle quiz questions.
		$form_model->questions = $questions;

		if ( isset( $data['msg_count'] ) ) {
			$msg_count = wp_kses_post( $data['msg_count'] ); // Backup, we allow html here.
		}

		// Sanitize quiz description.
		if ( isset( $data['settings']['quiz_description'] ) ) {
			$form_model->settings['quiz_description'] = wp_kses_post( $data['settings']['quiz_description'] );
		}

		// Update with backuped version.
		if ( $msg_count ) {
			$form_model->settings['msg_count'] = $msg_count;
		}

		$form_model->settings['formName'] = $title;

		return $form_model;
	}

	/**
	 * Enqueue quiz scripts
	 *
	 * @param bool $is_preview Is preview.
	 * @param bool $is_ajax_load Is ajax load.
	 */
	public function enqueue_form_scripts( $is_preview, $is_ajax_load = false ) {
		$google_fonts = $this->get_google_fonts();
		foreach ( $google_fonts as $font_name ) {
			if ( ! empty( $font_name ) ) {
				$this->styles[ 'forminator-font-' . sanitize_title( $font_name ) ] =
					array( 'src' => 'https://fonts.bunny.net/css?family=' . $font_name );
			}
		}
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
			'quiz_id'         => $this->model->id,
			'form_type'       => $this->get_form_type(),
			'has_quiz_loader' => $this->form_has_loader( $form_properties ),
			'hasLeads'        => $this->has_lead(),
		);

		if ( ! empty( $form_properties['settings']['text-next'] ) ) {
			$options['text_next'] = $form_properties['settings']['text-next'];
		}
		if ( ! empty( $form_properties['settings']['text-previous'] ) ) {
			$options['text_prev'] = $form_properties['settings']['text-previous'];
		}
		if ( ! empty( $form_properties['settings']['submitData']['custom-class'] ) ) {
			$options['submit_class'] = $form_properties['settings']['submitData']['custom-class'];
		}

		if ( $this->has_lead() ) {
			$options['form_placement'] = $this->get_form_placement();
			$options['leads_id']       = $this->get_leads_id();
			$options['skip_form']      = $this->has_skip_form();
		}

		return $options;
	}

	/**
	 * Return if form has submission loader enabled
	 *
	 * @param array $properties Properties.
	 *
	 * @since 1.7.1
	 *
	 * @return bool
	 */
	public function form_has_loader( $properties ) {
		if ( ! isset( $properties['settings']['quiz-ajax-loader'] ) || 'show' === $properties['settings']['quiz-ajax-loader'] ) {
			return true;
		}

		return false;
	}

	/**
	 * Ajax handler to reload module
	 *
	 * @since 1.11
	 *
	 * @return void
	 */
	public static function ajax_reload_module() {
		$nonce = Forminator_Core::sanitize_text_field( 'nonce' );

		$module_id = Forminator_Core::sanitize_text_field( 'moduleId' );
		if ( ! wp_verify_nonce( $nonce, 'forminator_submit_form' . $module_id ) ) {
			wp_send_json_error( new WP_Error( 'invalid_code' ) );
		}

		$page_id = Forminator_Core::sanitize_text_field( 'pageId' );

		if ( $page_id ) {
			$link = get_permalink( $page_id );

			if ( $link ) {
				$response = array(
					'success' => true,
					'html'    => $link,
				);
				wp_send_json( $response );
			} else {
				wp_send_json_error( new WP_Error( 'invalid_post' ) );
			}
		} else {
			wp_send_json_error( new WP_Error( 'invalid_id' ) );
		}
	}

	/**
	 * Lead wrapper start
	 *
	 * @return string
	 */
	public function lead_wrapper_start() {
		$wrapper = '';
		if ( $this->has_lead() ) {
			$form_settings = $this->get_form_settings();
			$form_design   = $this->get_form_design();

			$quiz_spacing   = 'data-spacing="default"';
			$quiz_alignment = 'data-alignment="left"';

			if ( isset( $form_settings['quiz-spacing'] ) && ! empty( $form_settings['quiz-spacing'] ) ) {
				$quiz_spacing = 'data-spacing="' . $form_settings['quiz-spacing'] . '"';
			}

			if ( isset( $form_settings['quiz-alignment'] ) && ! empty( $form_settings['quiz-alignment'] ) ) {
				$quiz_alignment = 'data-alignment="' . $form_settings['quiz-alignment'] . '"';
			} elseif ( false !== strpos( $form_design, 'grid' ) ) {

					$quiz_alignment = 'data-alignment="center"';
			}

			$visual_style = isset( $form_settings['visual_style'] ) ? $form_settings['visual_style'] : 'list';

			$wrapper = sprintf(
				'<div id="forminator-quiz-leads-%s" class="forminator-ui forminator-quiz-leads forminator-quiz--%s" data-design="%s" data-color-option="default" %s %s>',
				esc_attr( $form_settings['form_id'] ),
				esc_attr( $visual_style ),
				esc_attr( $this->get_quiz_theme() ),
				esc_attr( $quiz_spacing ),
				esc_attr( $quiz_alignment )
			);
		}

		return $wrapper;
	}

	/**
	 * Lead wrapper end
	 *
	 * @return string
	 */
	public function lead_wrapper_end() {
		if ( $this->has_lead() ) {
			return '</div>';
		} else {
			return '';
		}
	}
}
