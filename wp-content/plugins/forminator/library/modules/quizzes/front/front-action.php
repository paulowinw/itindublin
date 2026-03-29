<?php
/**
 * The Forminator_Quiz_Front_Action class.
 *
 * @package Forminator
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Front action for quizzes
 *
 * @since 1.0
 */
class Forminator_Quiz_Front_Action extends Forminator_Front_Action {

	/**
	 * Module slug
	 *
	 * @var string
	 */
	protected static $module_slug = 'quiz';

	/**
	 * Entry type
	 *
	 * @since 1.0
	 * @var string
	 */
	public static $entry_type = 'quizzes';

	/**
	 * Entry type
	 *
	 * @since 1.0
	 * @var string
	 */
	public $model = null;

	/**
	 * Handle quiz submit
	 *
	 * @since 1.0
	 * @since 1.1 refactor $_POST to get_post_data to be able pre-processed
	 * @since 1.6.2 add $is_preview as arg
	 *
	 * @param bool $is_preview Is preview.
	 */
	public function submit_quizzes( $is_preview = false ) {
		$this->init_properties();

		if ( ! $this->validate_ajax( 'forminator_submit_form' . self::$module_id, 'POST', 'forminator_nonce' ) ) {
			wp_send_json_error( esc_html__( 'Invalid nonce. Please refresh your browser.', 'forminator' ) );
		}

		self::can_submit();

		if ( empty( self::$prepared_data['current_url'] ) ) {
			self::$prepared_data['current_url'] = forminator_get_current_url();
		}

		/**
		 * Forminator_Quiz_Model
		 *
		 * @var  Forminator_Quiz_Model $model */
		$this->model = Forminator_Base_Form_Model::get_model( self::$module_id );

		if ( ! is_object( $this->model ) ) {
			wp_send_json_error(
				array(
					'error' => apply_filters( 'forminator_submit_quiz_error_not_found', esc_html__( 'Form not found', 'forminator' ) ),
				)
			);
		}

		if ( ! empty( self::$prepared_data['entry_id'] ) ) {
			$invalid_request = false;
			if ( empty( $this->model->settings['hasLeads'] ) ) {
				// Do not allow if the quiz has no leads and the request contains an entry ID.
				$invalid_request = true;
			} else {
				$old_entry = new Forminator_Form_Entry_Model( intval( self::$prepared_data['entry_id'] ) );
				// Do not allow if the entry does not match the current quiz ID.
				// Do not allow if the quiz has leads and has already been submitted.
				if ( intval( $this->model->id ) !== intval( $old_entry->form_id ) || false !== $old_entry->get_meta( 'entry' ) ) {
					$invalid_request = true;
				}
			}
			if ( $invalid_request ) {
				wp_send_json_error(
					array(
						'error' => apply_filters( 'forminator_submit_quiz_invalid_request', esc_html__( 'Invalid request', 'forminator' ) ),
					)
				);
			}
		}

		/**
		 * Action called before submit quizzes
		 *
		 * @param Forminator_Quiz_Model $model - the quiz model.
		 * @param bool                       $is_preview
		 */
		do_action( 'forminator_before_submit_quizzes', $this->model, $is_preview );

		if ( 'nowrong' === $this->model->quiz_type ) {
			$this->process_nowrong_submit( $this->model, $is_preview );
		} elseif ( ! isset( $this->model->settings['results_behav'] ) || 'end' !== $this->model->settings['results_behav'] ) { // Real time results - 1 answer only.
			$this->process_knowledge_submit( $this->model, $is_preview );
			// On submission results - multiple answers.
		} else {
			$this->process_knowledge_submit_multiple_answers( $this->model, $is_preview );
		}
	}

	/**
	 * Check if submission is possible.
	 */
	private static function can_submit() {
		$form_submit = self::$module_object->form_can_submit();
		if ( ! $form_submit['can_submit'] ) {
			wp_send_json_error(
				array(
					'error' => $form_submit['error'],
				)
			);
		}
	}

	/**
	 * Process No wrong quiz
	 *
	 * @since 1.0
	 * @since 1.6.2 add $is_preview as arg
	 *
	 * @param Forminator_Quiz_Model $model Quiz model.
	 * @param bool                  $is_preview Is preview.
	 */
	private function process_nowrong_submit( $model, $is_preview = false ) {
		// counting the result.
		$results     = array();
		$result_data = array();

		if ( isset( self::$prepared_data['answers'] ) ) {
			foreach ( self::$prepared_data['answers'] as $id => $answer ) {
				// collecting the results from answer.
				$results[]                = $model->getResultFromAnswer( $id, $answer );
				$question                 = $model->getQuestion( $id );
				$a                        = $model->getAnswer( $id, $answer );
				$result_data['answers'][] = array(
					'question' => $question['title'],
					'answer'   => $a['title'],
				);
			}
		}

		/**
		 * Collecting the results from answer with count as values
		 * {
		 *      'result-id-1' => `COUNT`,
		 *      'result-id-2' => `COUNT`,
		 * }
		 */
		$answer_results = array_count_values( $results );
		$final_res      = $model->get_nowrong_result( $answer_results );

		$result_data['result'] = $final_res;

		$addon_error = $this->attach_addons_on_quiz_submit( $model->id, $model );
		if ( true !== $addon_error ) {
			wp_send_json_error(
				array(
					'error' => $addon_error,
				)
			);
		}

		$entry = $this->save_form_entry(
			$model,
			// why on earth it saved like this.
			array(
				array(
					'name'  => 'entry',
					'value' => $result_data,
				),
			),
			$is_preview
		);
		$entries  = new Forminator_Form_Entry_Model( $entry->entry_id );
		$entry_id = $entry->entry_id;

		$result = new Forminator_QForm_Result();
		$result->set_entry( $entry_id );
		$result->set_postdata();

		// Email.
		$forminator_mail_sender = new Forminator_Quiz_Front_Mail();
		$forminator_mail_sender->process_mail( $model, $entries, $final_res );

		// dont push history on preview.
		$result_url = ! $is_preview ? $result->build_permalink() : '';

		// replace tags if any.
		foreach ( array( 'title', 'description' ) as $key ) {
			if ( isset( $final_res[ $key ] ) ) {
				if ( 'description' === $key ) {
					$final_res[ $key ] = do_shortcode( wp_kses_post( $final_res[ $key ] ) );
				}
				$final_res[ $key ] = forminator_replace_quiz_form_data( $final_res[ $key ], $model, $entry );
			}
		}

		wp_send_json_success(
			array(
				'result'     => $this->render_nowrong_result( $model, $final_res, $entry ),
				'result_url' => $result_url,
				'type'       => 'nowrong',
			)
		);
	}

	/**
	 * Get result quiz buttons
	 *
	 * @param object $model Model.
	 * @param bool   $is_material_design True if material design is selected.
	 * @return string
	 */
	private static function get_result_quiz_buttons( $model, $is_material_design = false ) {
		$html = '';

		$is_button = 'true';

		if ( $is_material_design ) {
			$is_button = 'false';
		}

		$can_shrink = '';

		if ( 'true' === $is_button ) {
			$can_shrink = ' data-shrink="true"';

			if ( ! empty( $model->settings['pagination'] ) ) {
				$can_shrink = ' data-shrink="false"';
			}
		}

		$retake = sprintf(
			'<button type="button" class="%s" data-button="%s" data-shrink="false">%s%s%s%s</button>',
			'forminator-button forminator-button-dynamic forminator-result--retake', // class.
			$is_material_design ? 'false' : 'true', // data-button.
			$is_material_design ? '' : '<span class="forminator-icon-refresh" aria-hidden="true"></span>', // icon markup.
			$is_material_design ? '' : '<span>',
			esc_html__( 'Retake Quiz', 'forminator' ),
			$is_material_design ? '' : '</span>'
		);

		$review = sprintf(
			'<button type="button" class="%s" data-button="%s" data-shrink="false">%s%s%s%s</button>',
			'forminator-button forminator-button-dynamic forminator-result--view-answers', // class.
			$is_material_design ? 'false' : 'true', // data-button.
			$is_material_design ? '' : '<span class="forminator-icon-chevron-left" aria-hidden="true"></span>', // icon markup.
			$is_material_design ? '' : '<span>',
			esc_html__( 'View Answers', 'forminator' ),
			$is_material_design ? '' : '</span>'
		);

		$html .= '<div class="forminator-quiz--action-buttons">';

		if ( ! empty( $model->settings['pagination'] ) ) {
			$html .= $review;
		}

			$html .= $retake;

		$html .= '</div>';

		return $html;
	}

	/**
	 * Render No wrong result
	 *
	 * @since 1.0
	 *
	 * @param Forminator_Quiz_Model       $model Quiz model.
	 * @param array                       $result Result.
	 * @param Forminator_Form_Entry_Model $entry Form entry model.
	 *
	 * @return string
	 */
	private function render_nowrong_result( $model, $result, Forminator_Form_Entry_Model $entry ) {
		ob_start();

		$theme = isset( $model->settings['forminator-quiz-theme'] ) ? $model->settings['forminator-quiz-theme'] : '';
		if ( ! $theme ) {
			$theme = 'default';
		}

		$description = '';
		// replace tags if any.
		if ( ! empty( $result['description'] ) ) {
			$description = forminator_replace_quiz_form_data( $result['description'], $model, $entry );
		}
		?>

		<?php if ( 'none' === $theme ) { ?>

			<div class="forminator-result" role="group">

				<p><strong><?php echo esc_html( $result['title'] ); ?></strong></p>

				<?php if ( ! empty( $description ) ) : ?>
					<p><?php echo wp_kses_post( $description ); ?></p>
				<?php endif; ?>

				<?php if ( isset( $result['image'] ) && ! empty( $result['image'] ) ) : ?>
					<img src="<?php echo esc_url( $result['image'] ); ?>" aria-hidden="true" class="forminator-result--image"<?php echo ! empty( $result['image_alt'] ) ? ' alt="' . esc_attr( $result['image_alt'] ) . '"' : ''; ?> />
				<?php endif; ?>

				<?php echo self::get_result_quiz_buttons( $model ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

			</div>

		<?php } else { ?>

			<div class="forminator-result" role="group">

				<?php if ( 'material' === $theme ) { ?>

					<?php if ( isset( $result['image'] ) && ! empty( $result['image'] ) ) { ?>
						<img src="<?php echo esc_url( $result['image'] ); ?>" class="forminator-result--image"<?php echo ! empty( $result['image_alt'] ) ? ' alt="' . esc_attr( $result['image_alt'] ) . '"' : ''; ?> aria-hidden="true" />
					<?php } ?>

					<div class="forminator-result--content">

						<p class="forminator-result--title"><?php echo esc_html( $result['title'] ); ?></p>

						<?php if ( ! empty( $description ) ) : ?>
							<div class="forminator-result--description"><?php echo wp_kses_post( $description ); ?></div>
						<?php endif; ?>

						<hr />

						<?php echo self::get_result_quiz_buttons( $model, true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

					</div>

				<?php } else { ?>

					<div class="forminator-result--content">

						<div class="forminator-result--text">

							<p class="forminator-result--title"><?php echo esc_html( $result['title'] ); ?></p>

							<?php if ( ! empty( $description ) ) : ?>
								<div class="forminator-result--description"><?php echo wp_kses_post( $description ); ?></div>
							<?php endif; ?>

						</div>

						<?php if ( isset( $result['image'] ) && ! empty( $result['image'] ) ) { ?>
							<div class="forminator-result--image" style="background-image: url('<?php echo esc_html( $result['image'] ); ?>');" aria-hidden="true">
								<img src="<?php echo esc_url( $result['image'] ); ?>"<?php echo ! empty( $result['image_alt'] ) ? ' alt="' . esc_attr( $result['image_alt'] ) . '"' : ''; ?> />
							</div>
						<?php } ?>

					</div>

					<?php echo self::get_result_quiz_buttons( $model ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

				<?php } ?>

			</div>

		<?php } ?>

		<?php
		$is_enabled = isset( $model->settings['enable-share'] ) && 'on' === $model->settings['enable-share'];
		$is_fb      = isset( $model->settings['facebook'] ) && filter_var( $model->settings['facebook'], FILTER_VALIDATE_BOOLEAN );
		$is_tw      = isset( $model->settings['twitter'] ) && filter_var( $model->settings['twitter'], FILTER_VALIDATE_BOOLEAN );
		$is_li      = isset( $model->settings['linkedin'] ) && filter_var( $model->settings['linkedin'], FILTER_VALIDATE_BOOLEAN );

		if ( $is_enabled ) {
			if ( $is_fb || $is_tw || $is_li ) :
				$result_message = forminator_get_social_message( $model, $model->settings['formName'], $result['title'] );
				?>
				<div class="forminator-quiz--social">
					<p class="forminator-social--text"><?php esc_html_e( 'Share your results', 'forminator' ); ?></p>
					<ul class="forminator-social--icons"
						data-message="<?php echo esc_attr( $result_message ); ?>"
						data-url="<?php echo esc_url( self::$prepared_data['current_url'] ); ?>">
						<?php if ( $is_fb ) : ?>
							<li class="forminator-social--icon">
								<a href="#" data-social="facebook" aria-label="<?php esc_html_e( 'Share on Facebook', 'forminator' ); ?>">
									<i class="forminator-icon-social-facebook" aria-hidden="true"></i>
									<span class="forminator-screen-reader-only"><?php esc_html_e( 'Share on Facebook', 'forminator' ); ?></span>
								</a>
							</li>
						<?php endif; ?>
						<?php if ( $is_tw ) : ?>
							<li class="forminator-social--icon">
								<a href="#" data-social="twitter" aria-label="<?php esc_html_e( 'Share on X', 'forminator' ); ?>">
									<i class="forminator-icon-social-twitter" aria-hidden="true"></i>
									<span class="forminator-screen-reader-only"><?php esc_html_e( 'Share on X', 'forminator' ); ?></span>
								</a>
							</li>
						<?php endif; ?>
						<?php if ( $is_li ) : ?>
							<li class="forminator-social--icon">
								<a href="#" data-social="linkedin" aria-label="<?php esc_html_e( 'Share on LinkedIn', 'forminator' ); ?>">
									<i class="forminator-icon-social-linkedin" aria-hidden="true"></i>
									<span class="forminator-screen-reader-only"><?php esc_html_e( 'Share on LinkedIn', 'forminator' ); ?></span>
								</a>
							</li>
						<?php endif; ?>
					</ul>
				</div>
			<?php endif; ?>
		<?php } ?>

		<?php

		$nowrong_result_html = ob_get_clean();

		/**
		 * Filter to modify nowrong results
		 *
		 * @since 1.0.2
		 * @since 1.6.2 change $final_res to $result with property
		 *
		 * @param string                     $nowrong_result_html - the return html.
		 * @param Forminator_Quiz_Model $model               - the model.
		 * @param string                     $result              - the final result.
		 *
		 * @return string $nowrong_result_html
		 */
		return apply_filters( 'forminator_quizzes_render_nowrong_result', $nowrong_result_html, $model, $result );
	}

	/**
	 * Process knowledge quiz
	 *
	 * @since 1.0
	 * @since 1.6.2 add $is_preview on arg
	 *
	 * @param object $model Quiz model.
	 * @param bool   $is_preview Is preview.
	 */
	private function process_knowledge_submit( $model, $is_preview = false ) {
		$answers = isset( self::$prepared_data['answers'] ) ? self::$prepared_data['answers'] : null;
		if ( ! is_array( $answers ) || 0 === count( $answers ) ) {
			wp_send_json_error(
				array(
					'error' => apply_filters( 'forminator_quizzes_process_knowledge_submit_no_answer_error', esc_html__( 'You haven\'t answered any questions', 'forminator' ) ),
				)
			);
		}
		$results   = array();
		$is_finish = true;
		/**
		 * Forminator_Quiz_Model
		 *
		 * @var Forminator_Quiz_Model $model */
		if ( count( $model->questions ) !== count( $answers ) ) {
			if ( 'end' === $model->settings['results_behav'] ) {
				// need to check if all the questions are answered.
				wp_send_json_error(
					array(
						'error' => apply_filters( 'forminator_quizzes_process_knowledge_submit_answer_all_error', esc_html__( 'Please answer all the questions', 'forminator' ) ),
					)
				);
			} else {
				$is_finish = false;
			}
		}
		// todo need to have a filter for answers if we use the result when chose.
		$right_counter = 0;
		$result_data   = array();
		$final_text    = isset( $model->settings['msg_count'] ) ? wp_kses_post( $model->settings['msg_count'] ) : '';
		foreach ( $answers as $id => $pick ) {
			$question = $model->getQuestion( $id );
			$meta     = array(
				'question' => $question['title'],
			);

			$correct_answers = $model->get_correct_answers_for_question( $id );

			$is_correct  = $model->is_correct_answer_for_question( $id, $pick );
			$user_answer = $model->getAnswer( $id, $pick );

			$correct_text   = isset( $model->settings['msg_correct'] ) ? $model->settings['msg_correct'] : '';
			$incorrect_text = isset( $model->settings['msg_incorrect'] ) ? $model->settings['msg_incorrect'] : '';

			if ( $is_correct ) {
				if ( isset( $user_answer['title'] ) ) {
					$correct_text = str_replace(
						'%UserAnswer%',
						$user_answer['title'],
						$correct_text
					);
				}

				// make sure correct answer exists before pluck it.
				if ( ! empty( $correct_answers ) && is_array( $correct_answers ) ) {
					$correct_text = str_replace(
						'%CorrectAnswer%',
						implode( ', ', wp_list_pluck( $correct_answers, 'title' ) ),
						$correct_text
					);
				}

				$results[ $id ]['message']   = $correct_text;
				$results[ $id ]['isCorrect'] = true;
				$results[ $id ]['answer']    = $id . '-' . $pick;

				$meta['answer']    = $user_answer['title'];
				$meta['isCorrect'] = true;

				++$right_counter;

			} else {
				if ( isset( $user_answer['title'] ) ) {
					$incorrect_text = str_replace(
						'%UserAnswer%',
						$user_answer['title'],
						$incorrect_text
					);
				}

				// make sure correct answer exists before pluck it.
				if ( ! empty( $correct_answers ) && is_array( $correct_answers ) ) {
					$incorrect_text = str_replace(
						'%CorrectAnswer%',
						implode( ', ', wp_list_pluck( $correct_answers, 'title' ) ),
						$incorrect_text
					);
				}

				$results[ $id ]['message']   = $incorrect_text;
				$results[ $id ]['isCorrect'] = false;
				$results[ $id ]['answer']    = $id . '-' . $pick;

				$meta['answer']    = $user_answer['title'];
				$meta['isCorrect'] = false;
			}
			$result_data[] = $meta;
		}

		$addon_error = $this->attach_addons_on_quiz_submit( $model->id, $model );
		if ( true !== $addon_error ) {
			wp_send_json_error(
				array(
					'error' => $addon_error,
				)
			);
		}

		$entry    = null;
		$entries  = null;
		$entry_id = 0;

		if ( $is_finish ) {
			$entry    = $this->save_form_entry( $model, $result_data, $is_preview );
			$entries  = new Forminator_Form_Entry_Model( $entry->entry_id );
			$entry_id = $entry->entry_id;
		}

		$result = new Forminator_QForm_Result();
		$result->set_entry( $entry_id );
		$result->set_postdata();

		self::$prepared_data['final_result'] = $right_counter;

		if ( $is_finish && ! is_null( $entry ) ) {
			// Email.
			$forminator_mail_sender = new Forminator_Quiz_Front_Mail();
			$forminator_mail_sender->process_mail( $model, $entries );
			// Replace quiz form data.
			$final_text = forminator_replace_quiz_form_data( $final_text, $model, $entry );
		}

		// Don't push history on preview.
		$result_url = ! $is_preview ? $result->build_permalink() : '';
		// Store.
		wp_send_json_success(
			array(
				'result'     => $results,
				'type'       => 'knowledge',
				'entry'      => $entry_id,
				'result_url' => $result_url,
				'finalText'  => $is_finish ? $this->render_knowledge_result(
					str_replace(
						'%YourNum%',
						$right_counter,
						str_replace( '%Total%', count( $results ), $final_text )
					),
					$model,
					$right_counter,
					count( $results )
				) : '',
			)
		);
	}

	/**
	 * Process knowledge quiz - multiple answers
	 *
	 * @since 1.14.2
	 *
	 * @param object $model Model.
	 * @param bool   $is_preview Is preview.
	 */
	private function process_knowledge_submit_multiple_answers( $model, $is_preview = false ) {
		$user_answers = isset( self::$prepared_data['answers'] ) ? self::$prepared_data['answers'] : null;
		if ( ! is_array( $user_answers ) || 0 === count( $user_answers ) ) {
			wp_send_json_error(
				array(
					'error' => apply_filters( 'forminator_quizzes_process_knowledge_submit_no_answer_error', esc_html__( 'You haven\'t answered any questions', 'forminator' ) ),
				)
			);
		}

		/**
		 * Since we are allowing multiple answers for each question,
		 * we should count the answered questions
		 */
		$questions          = $model->questions;
		$answered_questions = $model->count_answered_questions( $questions, $user_answers );

		if ( count( $questions ) > $answered_questions ) {
			// need to check if all the questions are answered.
			wp_send_json_error(
				array(
					'error' => apply_filters( 'forminator_quizzes_process_knowledge_submit_answer_all_error', esc_html__( 'Please answer all the questions', 'forminator' ) ),
				)
			);
		}

		// todo need to have a filter for answers if we use the result when chose.
		$results       = array();
		$result_data   = array();
		$final_text    = isset( $model->settings['msg_count'] ) ? wp_kses_post( $model->settings['msg_count'] ) : '';
		$is_finish     = true;
		$total_counter = 0;

		foreach ( $questions as $question ) {
			$question_slug   = $question['slug'];
			$question        = $model->getQuestion( $question_slug );
			$correct_answers = $model->get_correct_answers_for_question( $question_slug );
			$correct_text    = isset( $model->settings['msg_correct'] ) ? $model->settings['msg_correct'] : '';
			$incorrect_text  = isset( $model->settings['msg_incorrect'] ) ? $model->settings['msg_incorrect'] : '';
			$meta            = array( 'question' => $question['title'] );
			$right_counter   = 0;
			$wrong_counter   = 0;
			$index_counter   = 0; // We need this because $id is not an index.

			foreach ( $user_answers as $id => $pick ) {
				$question_id = preg_replace( '/(-\d+$)/', '', $id );

				if ( $question_slug !== $question_id ) {
					continue; }

				$is_correct  = $model->is_correct_answer_for_question( $question_id, $pick );
				$user_answer = $model->getAnswer( $question_id, $pick );

				if ( ! isset( $results[ $question_id ]['answers'] ) ) {
					$results[ $question_id ]['answers'] = array();
				}

				if ( $is_correct ) {
					if ( ! in_array( $id, $results[ $question_id ]['answers'] ) ) { // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
						$results[ $question_id ]['answers'][ $index_counter ]['id'] = $id;
						$meta['answers'][] = $user_answer['title'];
					}

					++$right_counter;

				} else {
					if ( ! in_array( $id, $results[ $question_id ]['answers'] ) ) { // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
						$results[ $question_id ]['answers'][ $index_counter ]['id'] = $id;
						$meta['answers'][] = $user_answer['title'];
					}

					++$wrong_counter;
				}
				++$index_counter;
			}

			// make sure correct answer exists before pluck it.
			if ( ! empty( $correct_answers ) && is_array( $correct_answers ) ) {
				$answer_titles = implode( ', ', wp_list_pluck( $correct_answers, 'title' ) );
				if ( count( $correct_answers ) > 1 ) {
					$answer_titles = preg_replace( '/(,(?!.*,))/', esc_html__( ' and', 'forminator' ), $answer_titles );
				}
			}

			// If all answers to current questin.
			if ( count( $correct_answers ) === $right_counter && 0 === $wrong_counter ) {
				++$total_counter;

				// make sure correct answer exists before pluck it.
				if ( ! empty( $correct_answers ) && is_array( $correct_answers ) ) {
					$correct_text = str_replace(
						'%UserAnswer%',
						$answer_titles,
						$correct_text
					);
				}

				$results[ $question_slug ]['message']   = $correct_text;
				$results[ $question_slug ]['isCorrect'] = true;

				$meta['isCorrect'] = true;

			} else {
				// make sure correct answer exists before pluck it.
				if ( ! empty( $correct_answers ) && is_array( $correct_answers ) ) {
					$incorrect_text = str_replace(
						'%CorrectAnswer%',
						$answer_titles,
						$incorrect_text
					);
				}

				$results[ $question_slug ]['message']   = $incorrect_text;
				$results[ $question_slug ]['isCorrect'] = false;

				$meta['isCorrect'] = false;

			}
			$result_data[] = $meta;
		}

		$addon_error = $this->attach_addons_on_quiz_submit( $model->id, $model );
		if ( true !== $addon_error ) {
			wp_send_json_error(
				array(
					'error' => $addon_error,
				)
			);
		}

		$entry    = null;
		$entries  = null;
		$entry_id = 0;

		if ( $is_finish ) {
			$entry    = $this->save_form_entry( $model, $result_data, $is_preview );
			$entries  = new Forminator_Form_Entry_Model( $entry->entry_id );
			$entry_id = $entry->entry_id;
		}

		$result = new Forminator_QForm_Result();
		$result->set_entry( $entry_id );
		$result->set_postdata();

		self::$prepared_data['final_result'] = $total_counter;

		if ( $is_finish && ! is_null( $entry ) ) {
			// Email.
			$forminator_mail_sender = new Forminator_Quiz_Front_Mail();
			$forminator_mail_sender->process_mail( $model, $entries );
			// Replace quiz form data.
			$final_text = forminator_replace_quiz_form_data( $final_text, $model, $entry );
		}

		// Don't push history on preview.
		$result_url = ! $is_preview ? $result->build_permalink() : '';
		// Store.
		wp_send_json_success(
			array(
				'result'     => $results,
				'type'       => 'knowledge',
				'entry'      => $entry_id,
				'result_url' => $result_url,
				'finalText'  => $is_finish ? $this->render_knowledge_result(
					str_replace(
						'%YourNum%',
						$total_counter,
						str_replace( '%Total%', count( $results ), $final_text )
					),
					$model,
					$total_counter,
					count( $results )
				) : '',
			)
		);
	}

	/**
	 * Render knowledge result
	 *
	 * @since 1.0
	 *
	 * @param string $text Text.
	 * @param object $model Model.
	 * @param string $right_answers Right answers.
	 * @param string $total_answers Total answers.
	 *
	 * @return string
	 */
	private function render_knowledge_result( $text, $model, $right_answers, $total_answers ) {
		ob_start();
		?>

		<div role="alert" class="forminator-quiz--summary">
			<?php echo wp_kses_post( wpautop( $text, true ) ); ?>
			<?php echo wp_kses_post( self::get_result_quiz_buttons( $model ) ); ?>
		</div>

		<?php
		$is_enabled = true;

		$is_fb = isset( $model->settings['facebook'] ) && filter_var( $model->settings['facebook'], FILTER_VALIDATE_BOOLEAN );
		$is_tw = isset( $model->settings['twitter'] ) && filter_var( $model->settings['twitter'], FILTER_VALIDATE_BOOLEAN );
		$is_li = isset( $model->settings['linkedin'] ) && filter_var( $model->settings['linkedin'], FILTER_VALIDATE_BOOLEAN );

		if ( isset( $model->settings['enable-share'] ) && 'off' === $model->settings['enable-share'] ) {
			$is_enabled = false;
		}

		if ( true === $is_enabled ) {

			if ( $is_fb || $is_tw || $is_li ) :

				$result         = $right_answers . '/' . $total_answers;
				$result_message = forminator_get_social_message( $model, $model->settings['formName'], $result );
				?>
				<div class="forminator-quiz--social">
					<p class="forminator-social--text"><?php esc_html_e( 'Share your results', 'forminator' ); ?></p>
					<ul class="forminator-social--icons"
						data-message="<?php echo esc_textarea( $result_message ); ?>"
						data-url="<?php echo esc_url( self::$prepared_data['current_url'] ); ?>">
						<?php if ( $is_fb ) : ?>
							<li class="forminator-social--icon">
								<a href="#" data-social="facebook" aria-label="<?php esc_html_e( 'Share on Facebook', 'forminator' ); ?>">
									<i class="forminator-icon-social-facebook" aria-hidden="true"></i>
									<span class="forminator-screen-reader-only"><?php esc_html_e( 'Share on Facebook', 'forminator' ); ?></span>
								</a>
							</li>
						<?php endif; ?>
						<?php if ( $is_tw ) : ?>
							<li class="forminator-social--icon">
								<a href="#" data-social="twitter" aria-label="<?php esc_html_e( 'Share on X', 'forminator' ); ?>">
									<i class="forminator-icon-social-twitter" aria-hidden="true"></i>
									<span class="forminator-screen-reader-only"><?php esc_html_e( 'Share on X', 'forminator' ); ?></span>
								</a>
							</li>
						<?php endif; ?>
						<?php if ( $is_li ) : ?>
							<li class="forminator-social--icon">
								<a href="#" data-social="linkedin" aria-label="<?php esc_html_e( 'Share on LinkedIn', 'forminator' ); ?>">
									<i class="forminator-icon-social-linkedin" aria-hidden="true"></i>
									<span class="forminator-screen-reader-only"><?php esc_html_e( 'Share on LinkedIn', 'forminator' ); ?></span>
								</a>
							</li>
						<?php endif; ?>
					</ul>
				</div>
			<?php endif; ?>

		<?php } ?>

		<?php
		$knowledge_result_html = ob_get_clean();
		$knowledge_result_html = do_shortcode( $knowledge_result_html );

		/**
		 * Filter to modify knowledge results
		 *
		 * @since 1.0.2
		 *
		 * @param string                     $knowledge_result_html - the return html.
		 * @param string                     $text                  - the summary text.
		 * @param Forminator_Quiz_Model $model                 - the model.
		 *
		 * @return string $knowledge_result_html
		 */
		return apply_filters( 'forminator_quizzes_render_knowledge_result', $knowledge_result_html, $text, $model );
	}

	/**
	 * Save entry
	 *
	 * @since 1.0
	 * @return void /json Json response
	 */
	public function save_entry() {
		$this->submit_quizzes();
	}

	/**
	 * Save entry
	 *
	 * @since 1.6
	 * @return void
	 */
	public function save_entry_preview() {
		$this->submit_quizzes( true );
	}

	/**
	 * Save entry
	 *
	 * @since 1.0
	 * @since 1.2 return entry id on success, or false on fail
	 * @since 1.6.2 change 1st arg from form_id to quiz model
	 *        - Add $is_preview as func arg
	 *
	 * @param Forminator_Quiz_Model $quiz Quiz model.
	 * @param mixed                 $field_data Field data.
	 * @param bool                  $is_preview Is preview.
	 *
	 * @return Forminator_Form_Entry_Model
	 */
	private function save_form_entry( $quiz, $field_data, $is_preview = false ) {
		$quiz_id           = $quiz->id;
		$entry             = new Forminator_Form_Entry_Model();
		$entry->entry_type = self::$entry_type;
		$entry->form_id    = $quiz_id;

		$data_entry = isset( self::$prepared_data['entry_id'] ) ? self::$prepared_data['entry_id'] : null;
		$skip_form  = false;

		if ( $this->has_skip_form() && empty( $data_entry ) ) {
			$skip_form = true;
		}

		$is_prevent_store = $quiz->is_prevent_store();
		if ( $is_preview || $is_prevent_store || $entry->save( null, $data_entry ) ) {
			$field_data_array = array(
				array(
					'name'  => 'entry',
					'value' => $field_data,
				),
				array(
					'name'  => 'quiz_url',
					'value' => self::$prepared_data['current_url'],
				),
				array(
					'name'  => 'skip_form',
					'value' => $skip_form,
				),
			);

			// ADDON add_entry_fields.
			$added_data_array = self::attach_addons_add_entry_fields( $field_data_array, $entry );

			/**
			 * Action called before setting fields to database
			 *
			 * @since 1.0.2
			 *
			 * @param Forminator_Form_Entry_Model $entry      - the entry model.
			 * @param int                         $quiz_id    - the quiz id.
			 * @param array                       $field_data - the entry data.
			 */
			do_action( 'forminator_quizzes_submit_before_set_fields', $entry, $quiz_id, $field_data );
			$entry->set_fields( $added_data_array );

			// ADDON after_entry_saved.
			self::attach_addons_after_entry_saved( $entry );
		}

		return $entry;
	}

	/**
	 * Footer message
	 *
	 * @return void
	 */
	public function footer_message() {}

	/**
	 * Handle submit
	 *
	 * @return void
	 */
	public function handle_submit() {}

	/**
	 * Executor On quiz submit for attached addons
	 *
	 * @see   Forminator_Integration_Quiz_Hooks::on_module_submit()
	 * @since 1.6.2
	 *
	 * @param int                   $quiz_id Quiz Id.
	 * @param Forminator_Quiz_Model $quiz_model Quiz model.
	 *
	 * @return bool true on success|string error message from addon otherwise
	 */
	private function attach_addons_on_quiz_submit( $quiz_id, Forminator_Quiz_Model $quiz_model ) {
		$submitted_data = static::get_submitted_data();
		// Find is_form_connected.
		$connected_addons = forminator_get_addons_instance_connected_with_module( $quiz_id, 'quiz' );

		foreach ( $connected_addons as $connected_addon ) {
			try {
				$quiz_hooks = $connected_addon->get_addon_hooks( $quiz_id, 'quiz' );
				if ( $quiz_hooks instanceof Forminator_Integration_Quiz_Hooks ) {
					$addon_return = $quiz_hooks->on_module_submit( $submitted_data );
					if ( true !== $addon_return ) {
						return $quiz_hooks->get_submit_error_message();
					}
				}
			} catch ( Exception $e ) {
				forminator_addon_maybe_log( $connected_addon->get_slug(), 'failed to attach_addons_on_quiz_submit', $e->getMessage() );
			}
		}

		return true;
	}

	/**
	 * Check has lead skip
	 *
	 * @return bool
	 */
	public function has_skip_form() {
		$form_settings = isset( $this->model->settings ) ? $this->model->settings : array();

		if ( isset( $form_settings['skip-form'] ) && $form_settings['skip-form'] ) {

			return true;
		}

		return false;
	}
}
