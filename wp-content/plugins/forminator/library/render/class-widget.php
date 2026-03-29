<?php
/**
 * The Forminator_Widget class.
 *
 * @package Forminator
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_Widget
 *
 * @since 1.0
 */
class Forminator_Widget extends WP_Widget {

	/**
	 * Forminator_Widget constructor.
	 *
	 * @since 1.0
	 */
	public function __construct() {
		parent::__construct(
			'forminator_widget',
			esc_html__( 'Forminator Widget', 'forminator' ),
			array( 'description' => esc_html__( 'Forminator Widget', 'forminator' ) )
		);
	}

	/**
	 * Outputs the content of the widget
	 *
	 * @since 1.0
	 *
	 * @param array $args Arguments.
	 * @param array $instance Instance.
	 */
	public function widget( $args, $instance ) {

		// Print widget before markup.
		if ( isset( $args['before_widget'] ) && ! empty( $args['before_widget'] ) ) {
			echo wp_kses_post( $args['before_widget'] );
		}

		// widget title.
		$title = isset( $instance['title'] ) ? $instance['title'] : '';
		$title = apply_filters( 'widget_title', $title, $instance, $this->id_base );
		if ( ! empty( $title ) ) {
			$full_title = ( isset( $args['before_title'] ) ? $args['before_title'] : '' ) . $instance['title'] . ( isset( $args['after_title'] ) ? $args['after_title'] : '' );
			echo wp_kses_post( $full_title );
		}

		// Make sure $form_type is set.
		if ( isset( $instance['form_type'] ) && ! empty( $instance['form_type'] ) ) {
			switch ( $instance['form_type'] ) {
				case 'form':
					if ( isset( $instance['form_id'] ) && ! empty( $instance['form_id'] ) ) {
						echo forminator_form( $instance['form_id'], false ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Content loaded from template file.
					}
					break;
				case 'poll':
					if ( isset( $instance['poll_id'] ) && ! empty( $instance['poll_id'] ) ) {
						echo forminator_poll( $instance['poll_id'], false ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Content loaded from template file.
					}
					break;
				case 'quiz':
					if ( isset( $instance['quiz_id'] ) && ! empty( $instance['quiz_id'] ) ) {
						echo forminator_quiz( $instance['quiz_id'], false ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Content loaded from template file.
					}
					break;
				default:
					break;
			}
		}

		// Print widget after markup.
		if ( isset( $args['after_widget'] ) && ! empty( $args['after_widget'] ) ) {
			echo wp_kses_post( $args['after_widget'] );
		}
	}

	/**
	 * Outputs the options form on admin
	 *
	 * @since 1.0
	 * @since 1.3 add return empty string to comply with WP_Widget
	 *
	 * @param array $instance The widget options.
	 *
	 * @return string
	 */
	public function form( $instance ) {
		$widget_title = '';
		$form_type    = '';
		$form_id      = '';
		$poll_id      = '';
		$quiz_id      = '';

		if ( isset( $instance['title'] ) ) {
			$widget_title = $instance['title'];
		}

		if ( isset( $instance['form_type'] ) ) {
			$form_type = $instance['form_type'];
		}

		if ( isset( $instance['form_id'] ) ) {
			$form_id = $instance['form_id'];
		}

		if ( isset( $instance['poll_id'] ) ) {
			$poll_id = $instance['poll_id'];
		}

		if ( isset( $instance['quiz_id'] ) ) {
			$quiz_id = $instance['quiz_id'];
		}
		$form_style = '' === $form_type || 'form' === $form_type ? 'block' : 'none';
		$poll_style = 'poll' === $form_type ? 'block' : 'none';
		$quiz_style = 'quiz' === $form_type ? 'block' : 'none';
		?>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">
				<?php esc_html_e( 'Title', 'forminator' ); ?>
			</label>
			<input
					type="text"
					class="widefat"
					id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
					name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>"
					value="<?php echo esc_attr( $widget_title ); ?>">
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'form_type' ) ); ?>">
				<?php esc_html_e( 'Form Type', 'forminator' ); ?>
			</label>
			<select class="widefat forminator-form-type" id="<?php echo esc_attr( $this->get_field_id( 'form_type' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'form_type' ) ); ?>">
				<option value="form" <?php selected( 'form', $form_type ); ?>><?php esc_html_e( 'Form', 'forminator' ); ?></option>
				<option value="poll" <?php selected( 'poll', $form_type ); ?>><?php esc_html_e( 'Poll', 'forminator' ); ?></option>
				<option value="quiz" <?php selected( 'quiz', $form_type ); ?>><?php esc_html_e( 'Quiz', 'forminator' ); ?></option>
			</select>
		</p>

		<p id="forminator-wrapper-form" class="forminator-form-wrapper" style="display:<?php echo esc_attr( $form_style ); ?>">
			<label for="<?php echo esc_attr( $this->get_field_id( 'form_id' ) ); ?>">
				<?php esc_html_e( 'Select Form', 'forminator' ); ?>
			</label>
			<select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'form_id' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'form_id' ) ); ?>">
				<?php
				// Add default to prevent issues in some plugins.
				echo '<option value="">' . esc_html__( 'Choose a form', 'forminator' ) . '</option>';

				$modules = Forminator_API::get_forms( null, 1, 999, Forminator_Form_Model::STATUS_PUBLISH );
				foreach ( $modules as $module ) {
					$module = (array) $module;

					$title = forminator_get_form_name( $module['id'] );
					if ( strlen( $title ) > 25 ) {
						$title = substr( $title, 0, 25 ) . '...';
					}
					echo '<option value="' . esc_attr( $module['id'] ) . '" ' . selected( $module['id'], $form_id, false ) . '>' . esc_html( $title . ' - ID: ' . $module['id'] ) . '</option>';
				}
				?>
			</select>
		</p>

		<p id="forminator-wrapper-poll" class="forminator-form-wrapper" style="display:<?php echo esc_attr( $poll_style ); ?>">
			<label for="<?php echo esc_attr( $this->get_field_id( 'poll_id' ) ); ?>">
				<?php esc_html_e( 'Select Poll', 'forminator' ); ?>
			</label>
			<select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'poll_id' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'poll_id' ) ); ?>">
				<?php
				// Add default to prevent issues in some plugins.
				echo '<option value="">' . esc_html__( 'Choose a poll', 'forminator' ) . '</option>';

				$modules = Forminator_API::get_polls( null, 1, 999, Forminator_Poll_Model::STATUS_PUBLISH );
				foreach ( $modules as $module ) {
					$module = (array) $module;

					$title = forminator_get_form_name( $module['id'] );
					if ( strlen( $title ) > 25 ) {
						$title = substr( $title, 0, 25 ) . '...';
					}
					echo '<option value="' . esc_attr( $module['id'] ) . '" ' . selected( $module['id'], $poll_id, false ) . '>' . esc_html( $title . ' - ID: ' . $module['id'] ) . '</option>';
				}
				?>
			</select>
		</p>

		<p id="forminator-wrapper-quiz" class="forminator-form-wrapper" style="display:<?php echo esc_attr( $quiz_style ); ?>">
			<label for="<?php echo esc_attr( $this->get_field_id( 'quiz_id' ) ); ?>">
				<?php esc_html_e( 'Select Quiz', 'forminator' ); ?>
			</label>
			<select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'quiz_id' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'quiz_id' ) ); ?>">
				<?php
				// Add default to prevent issues in some plugins.
				echo '<option value="">' . esc_html__( 'Choose a quiz', 'forminator' ) . '</option>';

				$modules = Forminator_API::get_quizzes( null, 1, 999, Forminator_Quiz_Model::STATUS_PUBLISH );
				foreach ( $modules as $module ) {
					$module = (array) $module;

					$title = forminator_get_form_name( $module['id'] );
					if ( strlen( $title ) > 25 ) {
						$title = substr( $title, 0, 25 ) . '...';
					}
					echo '<option value="' . esc_attr( $module['id'] ) . '" ' . selected( $module['id'], $quiz_id, false ) . '>' . esc_html( $title . ' - ID: ' . $module['id'] ) . '</option>';
				}
				?>
			</select>
		</p>

		<script type="text/javascript">
			jQuery(function () {
				jQuery(".forminator-form-type").on('change', function () {
					var value   = jQuery(this).val(),
						$widget = jQuery(this).closest('.widget-content')
					;

					$widget.find(".forminator-form-wrapper").hide();
					$widget.find("#forminator-wrapper-" + value).show();
				});
			});
		</script>
		<?php
		return '';
	}

	/**
	 * Processing widget options on save
	 *
	 * @since 1.0
	 *
	 * @param array $new_instance The new options.
	 * @param array $old_instance The previous options.
	 *
	 * @return array
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array();

		if ( isset( $new_instance['title'] ) ) {
			$instance['title'] = trim( wp_strip_all_tags( $new_instance['title'] ) );
		}

		if ( isset( $new_instance['form_type'] ) ) {
			$instance['form_type'] = $new_instance['form_type'];
		}

		if ( isset( $new_instance['form_id'] ) ) {
			$instance['form_id'] = $new_instance['form_id'];
		}

		if ( isset( $new_instance['poll_id'] ) ) {
			$instance['poll_id'] = $new_instance['poll_id'];
		}

		if ( isset( $new_instance['quiz_id'] ) ) {
			$instance['quiz_id'] = $new_instance['quiz_id'];
		}

		return $instance;
	}
}

// Functions.
require_once plugin_dir_path( __FILE__ ) . 'functions.php';
