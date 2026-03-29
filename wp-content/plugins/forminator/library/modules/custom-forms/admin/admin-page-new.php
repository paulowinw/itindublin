<?php
/**
 * The Forminator_CForm_New_Page class.
 *
 * @package Forminator
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_CForm_New_Page
 *
 * @since 1.0
 */
class Forminator_CForm_New_Page extends Forminator_Admin_Page {

	/**
	 * Get wizard title
	 *
	 * @since 1.0
	 * @return mixed
	 */
	public function getWizardTitle() {
		$id = filter_input( INPUT_GET, 'id', FILTER_VALIDATE_INT );
		if ( $id ) {
			return esc_html__( 'Edit Form', 'forminator' );
		} else {
			return esc_html__( 'New Form', 'forminator' );
		}
	}

	/**
	 * Add page screen hooks
	 *
	 * @since 1.0
	 * @param string $hook Hook.
	 */
	public function enqueue_scripts( $hook ) {
		// Load admin scripts.
		wp_register_script(
			'forminator-admin',
			forminator_plugin_url() . 'build/form-scripts.js',
			array(
				'jquery',
				'wp-color-picker',
				'react',
				'react-dom',
				'wp-element',
			),
			FORMINATOR_VERSION,
			true
		);
		forminator_common_admin_enqueue_scripts( true );

		// for preview.
		$style_src     = forminator_plugin_url() . 'assets/css/intlTelInput.min.css';
		$style_version = '4.0.3';

		$script_src     = forminator_plugin_url() . 'assets/js/library/intlTelInput.min.js';
		$script_version = FORMINATOR_VERSION;

		wp_enqueue_style( 'intlTelInput-forminator-css', $style_src, array(), $style_version ); // intlTelInput.
		wp_enqueue_script( 'forminator-intlTelInput', $script_src, array( 'jquery' ), $script_version, false ); // intlTelInput.

		wp_enqueue_script(
			'forminator-field-datepicker-range',
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
			true
		); // inputmask.
		wp_enqueue_script(
			'forminator-jquery-inputmask',
			forminator_plugin_url() . 'assets/js/library/jquery.inputmask.min.js',
			array( 'jquery' ),
			FORMINATOR_VERSION,
			true
		); // jquery inputmask.
		wp_enqueue_script(
			'forminator-inputmask-binding',
			forminator_plugin_url() . 'assets/js/library/inputmask.binding.js',
			array( 'jquery' ),
			FORMINATOR_VERSION,
			true
		); // inputmask binding.

		Forminator_Assets_Enqueue_Form::load_dompurify_scripts();
	}

	/**
	 * Render page content with access guard for registration forms.
	 *
	 * @since 1.52.0
	 */
	protected function render_page_content() {
		$form_id = filter_input( INPUT_GET, 'id', FILTER_VALIDATE_INT );
		if ( $form_id ) {
			$model = Forminator_Base_Form_Model::get_model( absint( $form_id ) );
			if ( $model instanceof Forminator_Base_Form_Model
				&& isset( $model->settings['form-type'] )
				&& 'registration' === $model->settings['form-type']
				&& empty( forminator_get_accessible_user_roles() )
			) {
				status_header( 403 );
				$this->render_registration_restriction_notice();
				return;
			}
		}

		parent::render_page_content();
	}

	/**
	 * Render the registration access restriction notice.
	 *
	 * @since 1.52.0
	 */
	private function render_registration_restriction_notice() {
		?>
		<div class="sui-box">
			<div class="sui-box-body">
				<div
					role="alert"
					class="sui-notice sui-notice-error sui-active"
					style="display: block; text-align: left;"
					aria-live="assertive"
				>
					<div class="sui-notice-content">
						<div class="sui-notice-message">
							<span class="sui-notice-icon sui-icon-info" aria-hidden="true"></span>
							<p>
								<?php esc_html_e( "You don't have permission to edit this Registration Form. Contact a site administrator if you need access.", 'forminator' ); ?><br />
							</p>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}
}
