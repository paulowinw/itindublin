<?php
/**
 * The Forminator Front Action.
 *
 * @package Forminator
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_Front_Action
 *
 * Abstract class for front functions
 *
 * @since 1.0
 */
abstract class Forminator_Front_Action {

	/**
	 * Entry type
	 *
	 * @var string
	 */
	public static $entry_type = '';

	/**
	 * Response message
	 *
	 * @var array
	 */
	protected static $response = array();

	/**
	 * Additional response attributes
	 *
	 * @var array
	 */
	protected static $response_attrs = array();

	/**
	 * Module ID
	 *
	 * @var int
	 */
	public static $module_id = 0;

	/**
	 * Prepared submitted data
	 *
	 * @var array
	 */
	public static $prepared_data = array();

	/**
	 * Module object Forminator_Form_Model | Forminator_Poll_Model | Forminator_Quiz_Model
	 *
	 * @var object
	 */
	public static $module_object;

	/**
	 * Module settings
	 *
	 * @var array
	 */
	public static $module_settings;

	/**
	 * Is leads
	 *
	 * @var bool
	 */
	protected static $is_leads = false;

	/**
	 * Previous draft ID
	 *
	 * @var string
	 */
	protected static $previous_draft_id;

	/**
	 * Is draft
	 *
	 * @var bool
	 */
	protected static $is_draft = false;

	/**
	 * Is spam submission.
	 *
	 * @var bool
	 */
	protected static $is_spam = false;

	/**
	 * Is abandoned submission.
	 *
	 * @var bool
	 */
	protected static $is_abandoned = false;

	/**
	 * Fields info
	 *
	 * @var array
	 */
	public static $info = array(
		'stripe_field'          => array(),
		'paypal_field'          => array(),
		'captcha_settings'      => array(),
		'field_data_array'      => array(),
		'select_field_value'    => array(),
		'upload_in_customfield' => array(),
	);

	/**
	 * Forminator_Front_Action constructor
	 */
	public function __construct() {
		// Save entries.
		if ( ! empty( static::$entry_type ) ) {
			add_action( 'wp', array( $this, 'maybe_handle_submit' ), 9 );
			add_action( 'wp_ajax_forminator_submit_form_' . static::$entry_type, array( $this, 'save_entry' ) );
			add_action( 'wp_ajax_nopriv_forminator_submit_form_' . static::$entry_type, array( $this, 'save_entry' ) );

			add_action( 'wp_ajax_forminator_submit_preview_form_' . static::$entry_type, array( $this, 'save_entry_preview' ) );
			add_action( 'wp_ajax_nopriv_forminator_submit_preview_form_' . static::$entry_type, array( $this, 'save_entry_preview' ) );

			add_action( 'wp_ajax_forminator_update_payment_amount', array( $this, 'update_payment_amount' ) );
			add_action( 'wp_ajax_nopriv_forminator_update_payment_amount', array( $this, 'update_payment_amount' ) );

			add_action( 'wp_ajax_forminator_2fa_fallback_email', array( $this, 'fallback_email' ) );
			add_action( 'wp_ajax_nopriv_forminator_2fa_fallback_email', array( $this, 'fallback_email' ) );
		}

		add_action( 'wp_ajax_forminator_get_nonce', array( $this, 'get_nonce' ) );
		add_action( 'wp_ajax_nopriv_forminator_get_nonce', array( $this, 'get_nonce' ) );
	}

	/**
	 * Maybe handle form submit
	 *
	 * @since 1.0
	 */
	public function maybe_handle_submit() {
		$action = Forminator_Core::sanitize_text_field( 'action' );
		if ( ! $action || 'forminator_submit_form_' . static::$entry_type !== $action ) {
			return;
		}
		$form_id = Forminator_Core::sanitize_text_field( 'form_id' );
		if ( $form_id ) {
			$model = Forminator_Base_Form_Model::get_model( $form_id );
			if ( $model && method_exists( $model, 'is_ajax_submit' ) && $model->is_ajax_submit() ) {
				return;
			}
		}

		if ( $this->is_force_validate_submissions_nonce() ) {
			$forminator_nonce = Forminator_Core::sanitize_text_field( 'forminator_nonce' );
			$form_id          = Forminator_Core::sanitize_text_field( 'form_id' );
			if ( ! $forminator_nonce || ! wp_verify_nonce( $forminator_nonce, 'forminator_submit_form' . $form_id ) ) {
				return;
			}
		}

		$this->handle_submit();
	}

	/**
	 * Init properties
	 *
	 * @param array $nonce_args Nonce arguments.
	 */
	protected function init_properties( $nonce_args = array() ) {
		$prepared_data       = $this->get_post_data( $nonce_args );
		$prepared_data       = self::make_nice_group_suffixes( $prepared_data );
		self::$prepared_data = $prepared_data;
		self::$module_id     = isset( self::$prepared_data['form_id'] ) ? self::$prepared_data['form_id'] : false;

		if ( self::$module_id ) {
			static::$module_object = Forminator_Base_Form_Model::get_model( self::$module_id );
			if ( ! static::$module_object && wp_doing_ajax() ) {
				wp_send_json_error(
					array(
						'message' => esc_html__( 'Error: Module object is corrupted!', 'forminator' ),
						'errors'  => array(),
					)
				);
			}
			self::$module_settings = method_exists( static::$module_object, 'get_form_settings' )
					? static::$module_object->get_form_settings() : static::$module_object->settings;

			self::$previous_draft_id = isset( self::$prepared_data['previous_draft_id'] ) ? self::$prepared_data['previous_draft_id'] : null;
			self::$is_draft          = isset( self::$prepared_data['save_draft'] ) ? filter_var( self::$prepared_data['save_draft'], FILTER_VALIDATE_BOOLEAN ) : false;
			// Check if save and continue is enabled.
			self::$is_draft = self::$is_draft &&
										isset( self::$module_settings['use_save_and_continue'] ) &&
										filter_var( self::$module_settings['use_save_and_continue'], FILTER_VALIDATE_BOOLEAN )
										? true
										: false;

			self::$is_abandoned = ! forminator_form_abandonment_disabled() && ! empty( self::$prepared_data['abandoned_form'] ) && ! empty( self::$module_settings['abandonment'] );
		} elseif ( wp_doing_ajax() ) {
				wp_send_json_error(
					array(
						'message' => esc_html__( 'Error: Your module ID doesn\'t exist!', 'forminator' ),
						'errors'  => array(),
					)
				);
		}
	}

	/**
	 * Change group suffixes in submitted data array
	 *
	 * @param array $prepared_data Submitted data.
	 * @return array Updated submitted data array
	 */
	private static function make_nice_group_suffixes( $prepared_data ) {
		$new_suffixes = array();
		foreach ( $prepared_data as $key => $suffixes ) {
			if ( 0 !== strpos( $key, 'group-' ) || '-copies' !== substr( $key, -7 ) ) {
				continue;
			}
			$new_value = array();
			foreach ( $suffixes as $index => $suffix ) {
				$new_suffixes[ $suffix ] = $index + 2;
				$new_value[ $suffix ]    = $index + 2;
				// prepare FILES.
				$relevant_file_keys = preg_grep( '/-' . $suffix . '$/', array_keys( $_FILES ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
				foreach ( $relevant_file_keys as $file_key ) {
					$new_key            = str_replace( $suffix, $index + 2, $file_key );
					$_FILES[ $new_key ] = $_FILES[ $file_key ]; // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput
					unset( $_FILES[ $file_key ] );
				}

				if ( ! empty( $prepared_data['forminator-multifile-hidden'] ) ) {
					$multi_file_keys = preg_grep( '/-' . $suffix . '$/', array_keys( $prepared_data['forminator-multifile-hidden'] ) );
					if ( ! empty( $multi_file_keys ) ) {
						foreach ( $multi_file_keys as $mfile_key ) {
							$new_mkey = str_replace( $suffix, $index + 2, $mfile_key );
							$prepared_data['forminator-multifile-hidden'][ $new_mkey ] = $prepared_data['forminator-multifile-hidden'][ $mfile_key ];
							unset( $prepared_data['forminator-multifile-hidden'][ $mfile_key ] );
						}
					}
				}
			}
			$prepared_data[ $key ] = $new_value;
		}

		if ( ! $new_suffixes ) {
			return $prepared_data;
		}

		foreach ( $prepared_data as $key => $suffixes ) {
			$new_key = str_replace( array_keys( $new_suffixes ), array_values( $new_suffixes ), $key );
			if ( $key === $new_key ) {
				continue;
			}
			$prepared_data[ $new_key ] = $prepared_data[ $key ];
			unset( $prepared_data[ $key ] );
		}

		return $prepared_data;
	}

	/**
	 * Get submitted data
	 *
	 * @return array
	 */
	protected static function get_submitted_data() {
		$data = self::$prepared_data;
		unset( $data['forminator_nonce'], $data['form_id'], $data['action'] );

		$data = apply_filters( 'forminator_addon_formatted_' . static::$module_slug . '_submitted_data', $data );

		return $data;
	}

	/**
	 * Handle submit
	 *
	 * @since 1.0
	 */
	public function handle_submit() {
		$this->init_properties();
		if ( ! self::$module_id ) {
			return;
		}
		/**
		 * Action called before full module submit
		 *
		 * @since 1.0.2
		 *
		 * @param int $form_id - the form id.
		 */
		do_action( 'forminator_' . static::$module_slug . '_before_handle_submit', self::$module_id );

		$response = $this->handle_form();

		// sanitize front end message.
		if ( ! empty( $response['message'] ) ) {
			$response['message'] = wp_kses_post( $response['message'] );
		}

		/**
		 * Filter submit response
		 *
		 * @since 1.0.2
		 *
		 * @param array $response - the post response.
		 * @param int $form_id - the form id.
		 *
		 * @return array $response
		 */
		$response = apply_filters( 'forminator_' . static::$module_slug . '_submit_response', $response, self::$module_id );

		/**
		 * Action called after full form submit
		 *
		 * @since 1.0.2
		 *
		 * @param int $form_id - the form id.
		 * @param array $response - the post response.
		 */
		do_action( 'forminator_' . static::$module_slug . '_after_handle_submit', self::$module_id, $response );
		if ( $response && is_array( $response ) ) {
			self::$response = $response;
			if ( $response['success'] ) {
				if ( isset( $response['url'] ) && ( ! isset( $response['newtab'] ) || 'sametab' === $response['newtab'] ) ) {
					$url = apply_filters( 'forminator_' . static::$module_slug . '_submit_url', $response['url'], self::$module_id );
					wp_safe_redirect( $url );
					exit;
				} else {
					add_action( 'forminator_' . static::$module_slug . '_post_message', array( $this, 'form_response_message' ), 10, 2 );
					// cleanup submitted data.
					$_POST = array();
				}
			} else {
				if ( $response['message'] ) {
					add_action( 'forminator_' . static::$module_slug . '_post_message', array( $this, 'form_response_message' ), 10, 2 );
					// cleanup submitted data.
					$_POST = array();
				}
				add_action( 'wp_footer', array( $this, 'footer_message' ) );
			}
		}
	}

	/**
	 * Add Error message on footer script if available
	 *
	 * @since 1.0
	 * @since 1.1 change superglobal POST to `get_post_data`
	 * @since 1.5.1 utilize `_post_data` which already defined on submit
	 */
	public function footer_message() {
		$submitted_data = self::$prepared_data;

		$response  = self::$response;
		$form_id   = isset( $submitted_data['form_id'] ) ? sanitize_text_field( $submitted_data['form_id'] ) : false;
		$render_id = isset( $submitted_data['render_id'] ) ? sanitize_text_field( $submitted_data['render_id'] ) : '';
		$selector  = '#forminator-module-' . $form_id . '[data-forminator-render="' . $render_id . '"]';
		if ( ! empty( $response['errors'] ) ) {
			?>
			<script type="text/javascript">var ForminatorValidationErrors =
				<?php
				echo wp_json_encode(
					array(
						'selector' => $selector,
						'errors'   => $response['errors'],
					)
				);
				?>
			</script>
			<?php
		}
	}

	/**
	 * Validate ajax
	 *
	 * @since 1.0
	 *
	 * @param string|null $original_action - the HTTP action.
	 * @param string      $request_method HTTP request method.
	 * @param string      $nonce_field Nonce field.
	 *
	 * @return bool
	 */
	public function validate_ajax( $original_action = null, $request_method = 'POST', $nonce_field = '_wpnonce' ) {
		if ( ! $this->is_force_validate_submissions_nonce() ) {
			$request_action = Forminator_Core::sanitize_text_field( 'action' );
			if ( $original_action === $request_action ) {
				return true;
			}
		}

		$nonce = Forminator_Core::sanitize_text_field( $nonce_field );
		if ( wp_verify_nonce( $nonce, $original_action ) ) {
			return true;
		} else {
			// if default nonce verifier fail, check other $request_method and auto detect action.
			switch ( $request_method ) {
				case 'REQUEST':
				case 'any':
				case 'GET':
					$action = $original_action;
					if ( empty( $action ) ) {
						$get_action = Forminator_Core::sanitize_text_field( 'action' );
						if ( empty( $get_action ) ) {
							$action = $get_action;
						}
					}
					$nonce = Forminator_Core::sanitize_text_field( $nonce_field );
					if ( wp_verify_nonce( $nonce, $action ) ) {
						return true;
					}
					break;
			}
			switch ( $request_method ) {
				case 'REQUEST':
				case 'any':
				case 'POST':
				default:
					$action = $original_action;
					if ( empty( $action ) ) {
						$post_action = Forminator_Core::sanitize_text_field( 'action' );
						if ( empty( $post_action ) ) {
							$action = $post_action;
						}
					}
					$nonce = Forminator_Core::sanitize_text_field( $nonce_field );
					if ( wp_verify_nonce( $nonce, $action ) ) {
						return true;
					}
					break;
			}
		}
		// Make sure its invalidated if all other above failed.
		return false;
	}

	/**
	 * Validate nonce
	 */
	public function validate_nonce() {
		if ( self::$is_abandoned ) {
			$form_uid = filter_input( INPUT_POST, 'form_uid' );
			$valid    = $this->validate_ajax( 'forminator_abandonment_form' . $form_uid, 'POST', 'forminator_abandonment_nonce' );
		} else {
			$valid = $this->validate_ajax( 'forminator_submit_form' . self::$module_id, 'POST', 'forminator_nonce' );
		}

		if ( ! $valid ) {
			wp_send_json_error( esc_html__( 'Invalid nonce. Please refresh your browser.', 'forminator' ) );
		}
	}

	/**
	 * Save Entry
	 *
	 * @since 1.0
	 */
	public function save_entry() {
		$this->init_properties();
		$status_suffix = '';
		if ( self::$is_spam ) {
			$status_suffix = '_spam';
		} elseif ( self::$is_abandoned ) {
			$status_suffix = '_abandoned';
		} elseif ( self::$is_draft ) {
			$status_suffix = '_draft';
		}

		$this->validate_nonce();

		/**
		 * Action called before module ajax
		 *
		 * @since 1.0.2
		 *
		 * @param int $form_id - the form id.
		 */
		do_action( 'forminator_' . static::$module_slug . $status_suffix . '_before_save_entry', self::$module_id );

		$response = $this->handle_form();

		if ( self::$is_draft && $response['success'] ) {
			wp_send_json_success( self::show_draft_link( self::$module_id, $response ) );
		}

		// sanitize front end message.
		if ( is_array( $response ) && ! empty( $response['message'] ) ) {
			$response['message'] = wp_kses_post( $response['message'] );
		}

		/**
		 * Filter ajax response
		 *
		 * @since 1.0.2
		 *
		 * @param array $response - the post response.
		 * @param int $form_id - the form id.
		 */
		$response = apply_filters( 'forminator_' . static::$module_slug . $status_suffix . '_ajax_submit_response', $response, self::$module_id );

		/**
		 * Action called after form ajax
		 *
		 * @since 1.0.2
		 *
		 * @param int $form_id - the form id.
		 * @param array $response - the post response.
		 */
		do_action( 'forminator_' . static::$module_slug . $status_suffix . '_after_save_entry', self::$module_id, $response );

		if ( $response && is_array( $response ) ) {
			if ( ! $response['success'] ) {
				wp_send_json_error( $response );
			} else {
				wp_send_json_success( $response );
			}
		}
		wp_send_json_error( esc_html__( 'Invalid form response', 'forminator' ) );
	}

	/**
	 * Show draft link and maybe the send-draft-link form
	 *
	 * @param int   $form_id Form Id.
	 * @param array $response Response.
	 *
	 * @since 1.17.0
	 */
	private static function show_draft_link( $form_id, $response ) {
		$response['form_id']    = $form_id;
		$response['type']       = 'save_draft';
		$draft_link             = esc_url( add_query_arg( 'draft', $response['draft_id'], get_permalink( $response['page_id'] ) ) );
		$send_draft_email_nonce = esc_attr( 'forminator_nonce_email_draft_link_' . $response['draft_id'] );
		$message                = str_replace( '{retention_period}', $response['retention_period'], $response['message'] );
		$message                = forminator_replace_form_data( $message, static::$module_object );
		$autofill_email         = isset( $response['first_email'] ) ? $response['first_email'] : '';

		ob_start();
		?>
			<div
				class="forminator-ui forminator-draft-wrap"
				data-id="<?php echo esc_attr( $form_id ); ?>"
				style="<?php echo isset( $setting['form-font-family'] ) && ! empty( $setting['form-font-family'] ) ? esc_attr( 'font-family:' . $setting['form-font-family'] . ';' ) : ''; ?>"
			>
				<div class="forminator-draft-notice draft-success"><?php echo wp_kses_post( $message ); ?></div>
				<div class="forminator-copy-field">
					<input
						type="text"
						class="forminator-draft-link"
						value="<?php echo esc_url( $draft_link ); ?>"
						disabled
					>
					<button class="forminator-copy-btn"><?php esc_html_e( 'Copy Link', 'forminator' ); ?></button>
				</div>
				<?php if ( $response['enable_email_link'] ) { ?>
					<div class="forminator-draft-email-response forminator-draft-notice" style="display: none;"></div>
					<form
						id="send-draft-link-form-<?php echo esc_attr( $response['draft_id'] ); ?>"
						class="forminator-ui forminator-custom-form forminator-draft-form "
						data-grid="open"
						method="post"
						novalidate="novalidate"
					>
						<div class="forminator-response-message"></div>
						<div class="forminator-row">
							<div id="email-1" class="forminator-col forminator-col-12 ">
								<div class="forminator-field">
									<label for="forminator-field-email-1" class="forminator-label">
										<?php
										// PHPCS:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
										echo Forminator_Field::convert_markdown( esc_html( $response['email_label'] ) );
										?>
									</label>
									<input
										type="email"
										name="email-1"
										value="<?php echo esc_attr( $autofill_email ); ?>"
										placeholder="<?php echo esc_attr( $response['email_placeholder'] ); ?>"
										id="forminator-field-email-1"
										class="forminator-input forminator-email--field"
									>
								</div>
							</div>
						</div>
						<div class="forminator-row forminator-row-last">
							<div class="forminator-col forminator-col-12 ">
								<div class="forminator-field">
									<button class="forminator-button-submit"><?php echo esc_html( $response['email_button_label'] ); ?></button>
								</div>
							</div>
						</div>
						<?php wp_nonce_field( $send_draft_email_nonce, $send_draft_email_nonce ); ?>
						<input type="hidden" name="action" value="forminator_email_draft_link">
						<input type="hidden" name="form_id" value="<?php echo esc_attr( $form_id ); ?>">
						<input type="hidden" name="draft_id" value="<?php echo esc_attr( $response['draft_id'] ); ?>">
						<input type="hidden" name="draft_link" value="<?php echo esc_url( $draft_link ); ?>">
						<input type="hidden" name="retention_period" value="<?php echo esc_attr( $response['retention_period'] ); ?>">
					</form>
				<?php } ?>
			</div>
		<?php
		$response['message'] = ob_get_clean();
		unset( $response['enable_email_link'] );
		unset( $response['email_label'] );
		unset( $response['email_placeholder'] );
		unset( $response['email_button_label'] );

		/**
		 * Filter save draft response
		 *
		 * @since 1.17.0
		 *
		 * @param array $response - the post response.
		 * @param int $form_id - the form id.
		 *
		 * @return array $response
		 */
		$response = apply_filters( 'forminator_form_save_draft_response', $response, $form_id );

		return $response;
	}

	/**
	 * Save Entry for Preview
	 *
	 * @since 1.6
	 */
	public function save_entry_preview() {
		$this->init_properties();

		if ( ! $this->validate_ajax( 'forminator_submit_form' . self::$module_id, 'POST', 'forminator_nonce' ) ) {
			return;
		}

		/**
		 * Action called before module ajax
		 *
		 * @since 1.0.2
		 *
		 * @param int $form_id - the form id.
		 */
		do_action( 'forminator_' . static::$module_slug . '_before_save_entry', self::$module_id );

		$response = $this->handle_form( true );
		// sanitize front end message.
		if ( is_array( $response ) && ! empty( $response['message'] ) ) {
			$response['message'] = wp_kses_post( $response['message'] );
		}

		/**
		 * Filter ajax response
		 *
		 * @since 1.0.2
		 *
		 * @param array $response - the post response.
		 * @param int $form_id - the form id.
		 */
		$response = apply_filters( 'forminator_' . static::$module_slug . '_ajax_submit_response', $response, self::$module_id );

		/**
		 * Action called after form ajax
		 *
		 * @since 1.0.2
		 *
		 * @param int $form_id - the form id.
		 * @param array $response - the post response.
		 */
		do_action( 'forminator_' . static::$module_slug . '_after_save_entry', self::$module_id, $response );

		if ( $response && is_array( $response ) ) {
			if ( ! $response['success'] ) {
				wp_send_json_error( $response );
			} else {
				wp_send_json_success( $response );
			}
		}
	}

	/**
	 * Prepare submitted data to sending to addons
	 *
	 * @param array $current_entry_fields Entry fields.
	 * @return array
	 */
	protected static function get_prepared_submitted_data_for_addons( $current_entry_fields ) {
		return static::get_submitted_data();
	}

	/**
	 * Executor to add more entry fields for attached addons
	 *
	 * @since 1.1
	 *
	 * @param array $current_entry_fields Entry fields.
	 * @param array $entry Entry.
	 *
	 * @return array added fields to entry
	 */
	protected static function attach_addons_add_entry_fields( $current_entry_fields, $entry = null ) {
		if ( self::$is_spam ) {
			return $current_entry_fields;
		}

		$additional_fields_data = array();
		$submitted_data         = static::get_prepared_submitted_data_for_addons( $current_entry_fields );

		$connected_addons = forminator_get_addons_instance_connected_with_module( static::$module_id, static::$module_slug );

		foreach ( $connected_addons as $connected_addon ) {
			if ( ! self::are_integration_conditions_matched( $connected_addon ) ) {
				continue;
			}
			try {
				if ( method_exists( $connected_addon, 'get_addon_hooks' ) ) {
					$hooks = $connected_addon->get_addon_hooks( static::$module_id, static::$module_slug );
				}
				if ( isset( $hooks ) && $hooks instanceof Forminator_Integration_Hooks ) {
					$addon_fields = $hooks->add_entry_fields( $submitted_data, $current_entry_fields, $entry );
					// reformat additional fields.
					$addon_fields           = self::format_addon_additional_fields( $connected_addon, $addon_fields );
					$additional_fields_data = array_merge( $additional_fields_data, $addon_fields );
				}
			} catch ( Exception $e ) {
				forminator_addon_maybe_log( $connected_addon->get_slug(), 'failed to ' . static::$module_slug . ' add_entry_fields', $e->getMessage() );
			}
		}

		return array_merge( $current_entry_fields, $additional_fields_data );
	}

	/**
	 * Check - are integration conditions matched or not
	 *
	 * @param object $connected_addon Connected addon object.
	 * @return boolean
	 */
	protected static function are_integration_conditions_matched( $connected_addon ) {
		if ( 'form' !== static::$module_slug ) {
			return true;
		}
		$integration_id = 0;
		if ( ! empty( $connected_addon->multi_id ) ) {
			$integration_id = $connected_addon->multi_id;
		}
		if ( ! empty( $connected_addon->multi_global_id ) ) {
			$integration_id = $connected_addon->multi_global_id;
		}
		if ( empty( static::$module_object->integration_conditions[ $integration_id ] ) ) {
			return true;
		}
		$data = static::$module_object->integration_conditions[ $integration_id ];

		if ( empty( $data['conditions'] ) ) {
			// If it doesn't have any conditions - return true.
			return true;
		}
		$condition_rule      = isset( $data['condition_rule'] ) ? $data['condition_rule'] : 'all';
		$condition_action    = isset( $data['condition_action'] ) ? $data['condition_action'] : 'send';
		$condition_fulfilled = 0;

		$all_conditions = $data['conditions'];

		foreach ( $all_conditions as $condition ) {
			$is_condition_fulfilled = Forminator_Field::is_condition_matched( $condition );
			if ( $is_condition_fulfilled ) {
				++$condition_fulfilled;
			}
		}

		if ( ( $condition_fulfilled > 0 && 'any' === $condition_rule )
				|| ( count( $all_conditions ) === $condition_fulfilled && 'all' === $condition_rule ) ) {
			// Conditions are matched.
			return 'send' === $condition_action;
		}

		return 'send' !== $condition_action;
	}

	/**
	 * Executor action for attached addons after entry saved on storage
	 *
	 * @since 1.1
	 *
	 * @param Forminator_Form_Entry_Model $entry_model Form entry model.
	 */
	protected static function attach_addons_after_entry_saved( Forminator_Form_Entry_Model $entry_model ) {
		if ( self::$is_draft || self::$is_spam || self::$is_abandoned ) {
			return;
		}

		$connected_addons = forminator_get_addons_instance_connected_with_module( self::$module_id, static::$module_slug );

		foreach ( $connected_addons as $connected_addon ) {
			if ( ! self::are_integration_conditions_matched( $connected_addon ) ) {
				continue;
			}
			try {
				if ( method_exists( $connected_addon, 'get_addon_hooks' ) ) {
					$hooks = $connected_addon->get_addon_hooks( static::$module_id, static::$module_slug );
				}
				if ( isset( $hooks ) && $hooks instanceof Forminator_Integration_Hooks ) {
					$hooks->after_entry_saved( $entry_model );// run and forget.
				}
			} catch ( Exception $e ) {
				forminator_addon_maybe_log( $connected_addon->get_slug(), 'failed to ' . static::$module_slug . ' attach_addons_after_entry_saved', $e->getMessage() );
			}
		}
	}

	/**
	 * Update payment amount
	 *
	 * @since 1.7.3
	 */
	public function update_payment_amount() {}

	/**
	 * Handle file upload
	 *
	 * @since 1.0
	 * @since 1.1 Bugfix filter `forminator_file_upload_allow` `$file_name` passed arg
	 *
	 * @param string $field_name - the input file name.
	 *
	 * @return bool|array
	 */
	public function handle_file_upload( $field_name ) {
		if ( isset( $_FILES[ $field_name ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			if ( isset( $_FILES[ $field_name ]['name'] ) && ! empty( $_FILES[ $field_name ]['name'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				$file_name = sanitize_file_name( $_FILES[ $field_name ]['name'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
				$valid     = wp_check_filetype( $file_name );

				if ( false === $valid['ext'] ) {
					return array(
						'success' => false,
						'message' => esc_html__( 'Error saving form. Uploaded file extension is not allowed.', 'forminator' ),
					);
				}

				$allow = apply_filters( 'forminator_file_upload_allow', true, $field_name, $file_name, $valid );
				if ( false === $allow ) {
					return array(
						'success' => false,
						'message' => esc_html__( 'Error saving form. Uploaded file extension is not allowed.', 'forminator' ),
					);
				}

				require_once ABSPATH . 'wp-admin/includes/file.php';
				WP_Filesystem();
				/**
				 * WP_Filesystem_Base
				 *
				 * @var WP_Filesystem_Base $wp_filesystem */
				global $wp_filesystem;
				if ( ! is_uploaded_file( $_FILES[ $field_name ]['tmp_name'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput
					return array(
						'success' => false,
						'message' => esc_html__( 'Error saving form. Failed to read uploaded file.', 'forminator' ),
					);
				}

				$upload_dir       = wp_upload_dir(); // Set upload folder.
				$unique_file_name = wp_unique_filename( $upload_dir['path'], $file_name );
				$filename         = basename( $unique_file_name ); // Create base file name.

				if ( 0 === $_FILES[ $field_name ]['size'] || $_FILES[ $field_name ]['size'] > wp_max_upload_size() ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput

					$max_size = wp_max_upload_size();
					$max_size = round( $max_size / 1000000 ) . ' MB';

					return array(
						'success' => false,
						'message' => sprintf( /* translators: %s: Maximum size */ esc_html__( 'Error saving form. Uploaded file size exceeds %s upload limit. ', 'forminator' ), $max_size ),
					);
				}

				if ( UPLOAD_ERR_OK !== $_FILES[ $field_name ]['error'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput
					return array(
						'success' => false,
						'message' => esc_html__( 'Error saving form. Upload error. ', 'forminator' ),
					);
				}

				if ( ! $wp_filesystem->is_dir( $upload_dir['path'] ) ) {
					$wp_filesystem->mkdir( $upload_dir['path'] );
				}

				if ( $wp_filesystem->is_writable( $upload_dir['path'] ) ) {
					$file_path = $upload_dir['path'] . '/' . $filename;
					$file_url  = $upload_dir['url'] . '/' . $filename;
				} else {
					$file_path = $upload_dir['basedir'] . '/' . $filename;
					$file_url  = $upload_dir['baseurl'] . '/' . $filename;
				}

				// use move_uploaded_file instead of $wp_filesystem->put_contents.
				// increase performance, and avoid permission issues.
				if ( false !== move_uploaded_file( $_FILES[ $field_name ]['tmp_name'], $file_path ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput
					return array(
						'success'   => true,
						'file_url'  => $file_url,
						'file_path' => wp_normalize_path( $file_path ),
					);
				} else {
					return array(
						'success' => false,
						'message' => esc_html__( 'Error saving form. Upload error. ', 'forminator' ),
					);
				}
			}
		}

		return false;
	}

	/**
	 * Get superglobal POST data
	 *
	 * @since 1.1
	 *
	 * @param array $nonce_args         {.
	 *                                  nonce validation options, its numeric array
	 *                                  0 => 'action' string of action name to be validated,
	 *                                  2 => 'nonce_field' string of field name on $_POST contains nonce value
	 *                                  }.
	 *
	 * @param array $sanitize_callbacks {
	 *                                  custom sanitize options, its assoc array
	 *                                  'field_name_1' => 'function_to_call_1' function will called with `call_user_func_array`,
	 *                                  'field_name_2' => 'function_to_call_2',
	 *                                  }.
	 *
	 * @return array
	 */
	protected function get_post_data( $nonce_args = array(), $sanitize_callbacks = array() ) {
		// do nonce / caps check when requested.
		$nonce_action = '';
		$nonce_field  = '';
		if ( isset( $nonce_args[0] ) && ! empty( $nonce_args[0] ) ) {
			$nonce_action = $nonce_args[0];
		}
		if ( isset( $nonce_args[1] ) && ! empty( $nonce_args[1] ) ) {
			$nonce_field = $nonce_args[1];
		}
		if ( ! empty( $nonce_action ) && ! empty( $nonce_field ) ) {
			$validated = $this->validate_ajax( $nonce_action, 'POST', $nonce_field );
			if ( ! $validated ) {
				// return empty data when its not validated.
				return array();
			}
		}

		$post_data = Forminator_Core::sanitize_array( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified in validate_ajax().

		// do some sanitize.
		foreach ( $sanitize_callbacks as $field => $sanitize_func ) {
			if ( isset( $post_data[ $field ] ) ) {
				if ( is_callable( $sanitize_func ) ) {
					$post_data[ $field ] = call_user_func_array( array( $sanitize_func ), array( $post_data[ $field ] ) );
				}
			}
		}

		$post_data = $this->remove_uploads_uid( $post_data );

		return $post_data;
	}

	/**
	 * Remove the Form UID in the element_id of Ajax multi-upload fields
	 *
	 * @since 1.18.0
	 *
	 * @param array $post_data Post data.
	 *
	 * @return $post_data
	 */
	protected function remove_uploads_uid( $post_data ) {
		if ( ! empty( $post_data['forminator-multifile-hidden'] ) ) {
			$post_data['forminator-multifile-hidden'] = json_decode( stripslashes( $post_data['forminator-multifile-hidden'] ), true );

			foreach ( $post_data['forminator-multifile-hidden'] as $key => $val ) {
				if ( 0 === strpos( $key, 'upload-' ) ) {
					$new_key = preg_replace( '/_[^-]+/', '', $key );
					$post_data['forminator-multifile-hidden'][ $new_key ] = $val;
					unset( $post_data['forminator-multifile-hidden'][ $key ] );
				}
			}
		}

		return $post_data;
	}

	/**
	 * Formatting additional fields from addon
	 * Format used is `forminator_addon_{$slug}_{$field_name}`
	 *
	 * @since 1.6.1
	 *
	 * @param Forminator_Integration $addon Addon.
	 * @param mixed                  $additional_fields Additional fields.
	 *
	 * @return array
	 */
	protected static function format_addon_additional_fields( Forminator_Integration $addon, $additional_fields ) {
		// to `name` and `value` basis.
		$formatted_additional_fields = array();
		if ( ! is_array( $additional_fields ) ) {
			return array();
		}

		foreach ( $additional_fields as $additional_field ) {
			if ( ! isset( $additional_field['name'] ) || ! isset( $additional_field['value'] ) ) {
				continue;
			}
			$formatted_additional_fields[] = array(
				'name'  => 'forminator_addon_' . $addon->get_slug() . '_' . $additional_field['name'],
				'value' => $additional_field['value'],
			);
		}

		return $formatted_additional_fields;
	}

	/**
	 * Check if validate nonce should be executed
	 *
	 * @return bool
	 */
	protected function is_force_validate_submissions_nonce() {
		/*
		By default nonce validation is active unless `FORMINATOR_FORCE_VALIDATE_SUBMISSIONS_NONCE` is defined and
		set to false.
		This behaviour might cause submissions to fail when page cache is active. In such cases we can advise to
		use ajax form load or define the following const or filter.
		*/
		$enabled = defined( 'FORMINATOR_FORCE_VALIDATE_SUBMISSIONS_NONCE' ) ? boolval( FORMINATOR_FORCE_VALIDATE_SUBMISSIONS_NONCE ) : true;

		/**
		 * Filter the status of nonce submissions
		 *
		 * @since 1.6.1
		 *
		 * @param bool $enabled current status of nonce submissions.
		 */
		$enabled = apply_filters( 'forminator_is_force_validate_submissions_nonce', $enabled );

		return $enabled;
	}

	/**
	 * Get Akismet fail message or false for storing the current submission and marking it as spam.
	 *
	 * @return boolean|string
	 */
	protected static function get_akismet_fail_message() {
		if ( empty( self::$module_settings['akismet-protection-behavior'] ) || 'fail' === self::$module_settings['akismet-protection-behavior'] ) {
			if ( ! empty( self::$module_settings['spam-fail-message'] ) ) {
				$fail_message = self::$module_settings['spam-fail-message'];
			} else {
				$fail_message = esc_html__( 'Something went wrong.', 'forminator' );
			}
			return $fail_message;
		} else {
			return false;
		}
	}

	/**
	 * Prepare error array.
	 *
	 * @param string $error Error message.
	 * @return array
	 */
	protected static function return_error( $error ) {
		$response = array(
			'message' => $error,
			'success' => false,
			'notice'  => 'error',
			'form_id' => static::$module_id,
		);

		if ( ! empty( static::$submit_errors ) ) {
			$response['errors'] = static::$submit_errors;
		}

		unset( self::$response_attrs['message'] );

		if ( self::$response_attrs ) {
			$response = array_merge( $response, self::$response_attrs );
		}

		/**
		 * Filter response for failed submission
		 *
		 * @param array $response Response.
		 */
		return apply_filters( 'forminator_submission_error', $response );
	}

	/**
	 * Prepare success array.
	 *
	 * @param string $message Message.
	 *
	 * @return array
	 */
	protected static function return_success( $message = null ) {
		$response = array(
			'message' => ! is_null( $message ) ? $message : esc_html__( 'Form entry saved', 'forminator' ),
			'success' => true,
			'form_id' => static::$module_id,
		);

		if ( self::$response_attrs ) {
			$response = array_merge( $response, self::$response_attrs );
		}

		/**
		 * Filter response for successful submission
		 *
		 * @param array $response Response.
		 */
		return apply_filters( 'forminator_submission_success', $response );
	}

	/**
	 * Check if it's spam submission or not
	 *
	 * @return boolean
	 * @throws Exception When fails.
	 */
	protected static function is_spam() {
		/**
		 * Handle spam protection
		 * Add-ons use this filter to check if content has spam data
		 *
		 * @since 1.0.2
		 *
		 * @param bool false - defauls to false
		 * @param array $field_data_array - the entry data.
		 * @param int $form_id - the form id.
		 * @param string $form_type - the form type.
		 *
		 * @return bool true|false
		 */
		$is_spam = apply_filters( 'forminator_spam_protection', false, self::$info['field_data_array'], self::$module_id, static::$module_slug );
		if ( $is_spam ) {
			$fail_message = self::get_akismet_fail_message();
			if ( false !== $fail_message ) {
				throw new Exception( esc_html( $fail_message ) );
			} else {
				return true;
			}
		}
	}

	/**
	 * Get form entry model
	 */
	protected static function get_entry() {
		$entry = new Forminator_Form_Entry_Model();

		if ( isset( self::$prepared_data['lead_quiz'] ) && self::$is_leads ) {
			$entry->entry_type = 'quizzes';
			$entry->form_id    = self::$prepared_data['lead_quiz'];
		} else {
			$entry->entry_type = static::$entry_type;
			$entry->form_id    = self::$module_id;
		}

		return $entry;
	}

	/**
	 * Should it prevent storing submission?
	 *
	 * @return boolean
	 */
	protected static function prevent_store() {
		$prevent_store = false;
		if ( self::$is_leads && isset( self::$prepared_data['lead_quiz'] ) ) {
			$quiz_model = Forminator_Base_Form_Model::get_model( self::$prepared_data['lead_quiz'] );
			if ( isset( $quiz_model->settings ) ) {
				$prevent_store = static::$module_object->is_prevent_store( self::$prepared_data['lead_quiz'], $quiz_model->settings );
			}
		} else {
			$prevent_store = static::$module_object->is_prevent_store();
		}

		return $prevent_store;
	}

	/**
	 * Get nonce to avoid Static cache
	 */
	public function get_nonce() {
		$form_id = filter_input( INPUT_POST, 'form_id', FILTER_VALIDATE_INT );
		wp_send_json_success( wp_create_nonce( 'forminator_submit_form' . $form_id ) );
	}
}
