<?php
/**
 * The Forminator_CForm_Front_Mail class.
 *
 * @package Forminator
 */

/**
 * Front ajax for custom forms
 *
 * @since 1.0
 */
class Forminator_CForm_Front_Mail extends Forminator_Mail {
	/**
	 * Module slug
	 *
	 * @var string
	 */
	protected static $module_slug = 'form';

	/**
	 * Skipped custom form_data parsing
	 *
	 * @since 1.0.3
	 * @var array
	 */
	private $skip_custom_form_data
		= array(
			'admin' => array(),
			'user'  => array(),
		);

	/**
	 * Replace all placeholders for mail properties
	 *
	 * @param array  $settings Settings.
	 * @param string $option_name Current option name.
	 * @param object $module Module.
	 * @param object $entry Saved entry.
	 * @param bool   $full_mode Use full mode or not.
	 * @return string
	 */
	private function replace_placeholders( $settings, $option_name, $module, $entry, $full_mode = false ) {
		if ( ! isset( $settings[ $option_name ] ) ) {
			return '';
		}
		if ( $full_mode ) {
			$text = forminator_replace_form_data( $settings[ $option_name ], $module, $entry, true );
		} else {
			$text = forminator_replace_form_data( $settings[ $option_name ] );
		}
		$text = forminator_replace_variables( $text, $module->id, $entry );
		$text = forminator_replace_custom_form_data( $text, $module, $entry, $this->skip_custom_form_data['admin'] );

		return $text;
	}

	/**
	 * Get files for email sending.
	 *
	 * @param object $custom_form Module.
	 * @param object $entry Saved entry.
	 * @return array
	 */
	private function get_files( $custom_form, $entry ) {
		$files       = array();
		$form_fields = $custom_form->get_fields();
		foreach ( $form_fields as $form_field ) {
			$files = self::add_field_files( $files, $form_field, $entry );
			if ( ! empty( $form_field->parent_group ) && ! empty( Forminator_CForm_Front_Action::$prepared_data[ $form_field->parent_group . '-copies' ] ) ) {
				foreach ( Forminator_CForm_Front_Action::$prepared_data[ $form_field->parent_group . '-copies' ] as $prefix ) {
					$clonned_form_field       = clone $form_field;
					$clonned_form_field->slug = $form_field->slug . '-' . $prefix;
					$files                    = self::add_field_files( $files, $clonned_form_field, $entry );
				}
			}
		}
		if ( ! empty( $files ) ) {
			$files = call_user_func_array( 'array_merge', $files );
		}

		return $files;
	}

	/**
	 * Maybe add files
	 *
	 * @param array  $files All files.
	 * @param object $form_field Form fields.
	 * @param object $entry Entry.
	 * @return array
	 */
	private static function add_field_files( $files, $form_field, $entry ) {
		$field_array = $form_field->to_formatted_array();
		$field_type  = $field_array['type'];
		$field_id    = $form_field->slug;
		if ( 'upload' === $field_type &&
				! in_array( $field_id, Forminator_CForm_Front_Action::$hidden_fields, true ) ) {
			$field_slug = isset( $entry->meta_data[ $field_id ] ) ? $entry->meta_data[ $field_id ] : '';
			if ( ! empty( $field_slug ) && ! empty( $field_slug['value']['file'] ) ) {
				$email_files = isset( $field_slug['value']['file'] ) && isset( $field_slug['value']['file']['file_path'] )
					? $field_slug['value']['file']['file_path']
					: array();
				$files[]     = is_array( $email_files ) ? $email_files : array( $email_files );
			}
		}

		return $files;
	}

	/**
	 * Check if attachments exceed size limit.
	 *
	 * @since 1.52.0
	 *
	 * @param array $files Files to check.
	 * @return bool
	 */
	private function is_attachment_size_limit_exceeded( $files ) {
		$total_size = 0;

		foreach ( $files as $file_path ) {
			if ( empty( $file_path ) || ! file_exists( $file_path ) ) {
				continue;
			}

			$file_size = filesize( $file_path );
			if ( false !== $file_size ) {
				$total_size += $file_size;
			}
		}

		$limit = 15 * MB_IN_BYTES;

		/**
		 * Filter maximum total attachment size for email notifications.
		 * Return 0 or negative value to disable the limit.
		 *
		 * @since 1.52.0
		 *
		 * @param int   $limit Default 15 MB.
		 * @param array $files Files to be attached.
		 * @param Forminator_CForm_Front_Mail $this Current mail instance.
		 */
		$limit = apply_filters( 'forminator_mail_attachment_max_bytes', $limit, $files, $this );
		$limit = is_numeric( $limit ) ? (int) $limit : 15 * MB_IN_BYTES;

		return ( $limit > 0 && $total_size >= $limit );
	}

	/**
	 * Process mail
	 *
	 * @since 1.0
	 *
	 * @param Forminator_Form_Model       $custom_form Form model.
	 * @param Forminator_Form_Entry_Model $entry - saved entry @since 1.0.3.
	 * @param array                       $submitted_data - submitted data from email draft form  @since 1.17.0.
	 */
	public function process_mail( $custom_form, Forminator_Form_Entry_Model $entry, $submitted_data = array() ) {
		self::$is_email_context = true;

		try {
			$data          = Forminator_CForm_Front_Action::$prepared_data;
			$notifications = $custom_form->notifications;

			if (
			empty( $data ) && ! empty( $submitted_data ) &&
			isset( $submitted_data['action'] ) &&
			'forminator_email_draft_link' === $submitted_data['action']
			) {
				$data = recreate_prepared_data( $custom_form, $entry );

				// Map data to prepared data.
				Forminator_Front_Action::$prepared_data = $data;
			}

			if ( empty( $data['current_url'] ) ) {
				$data['current_url'] = forminator_get_current_url();
			}

			$files       = $this->get_files( $custom_form, $entry );
			$exceeded    = $this->is_attachment_size_limit_exceeded( $files );
			$attachments = $exceeded ? array() : $files;
			$entry       = $this->maybe_remove_stripe_quantity( $entry );

			/**
			 * Message data filter
			 *
			 * @since 1.0.4
			 *
			 * @param array                        $data        - the post data.
			 * @param Forminator_Form_Model $custom_form - the form.
			 * @param Forminator_Form_Entry_Model  $entry       - saved entry @since 1.0.3.
			 *
			 * @return array $data
			 */
			$data = apply_filters( 'forminator_custom_form_mail_data', $data, $custom_form, $entry );

			/**
			 * Exclude HTML fields from email filter
			 *
			 * @since 1.50.0
			 *
			 * @param bool $exclude_html - whether to exclude HTML fields or not
			 * @param Forminator_Form_Model $custom_form - the form.
			 * @param array                        $data        - the post data.
			 * @param Forminator_Form_Entry_Model  $entry       - saved entry
			 *
			 * @return bool $exclude_html
			 */
			$exclude_html = apply_filters( 'forminator_custom_form_mail_exclude_html_fields', false, $custom_form, $data, $entry );

			if ( $exclude_html ) {
				$fields = $custom_form->fields;
				if ( ! empty( $fields ) ) {
					foreach ( $fields as $k => $v ) {
						if ( false !== strpos( $v->slug, 'html-' ) ) {
							unset( $custom_form->fields[ $k ] );
						}
					}
				}
			}

			/**
			 * Action called before mail is sent
			 *
			 * @param Forminator_CForm_Front_Mail - the current form
			 * @param Forminator_Form_Model - the current form
			 * @param array                       $data  - current data.
			 * @param Forminator_Form_Entry_Model $entry - saved entry @since 1.0.3.
			 */
			do_action( 'forminator_custom_form_mail_before_send_mail', $this, $custom_form, $data, $entry );

			// Process Email.
			if ( ! empty( $notifications ) ) {
				$this->init();
				// Process admin mail.
				foreach ( $notifications as $notification ) {

					// If notification is save_draft type, skip.
					if (
					isset( $data['action'] ) && 'forminator_email_draft_link' !== $data['action'] &&
					isset( $notification['type'] ) && 'save_draft' === $notification['type']
					) {
						continue;
					}

					if ( $this->is_condition( $notification ) ) {
						continue;
					}

					$recipients = $this->get_admin_email_recipients( $notification, $custom_form, $entry );

					if ( empty( $recipients ) ) {
						continue;
					}

					$subject = $this->replace_placeholders( $notification, 'email-subject', $custom_form, $entry, true );
					$message = $this->replace_placeholders( $notification, 'email-editor', $custom_form, $entry, true );
					/**
					 * Custom form mail subject filter
					 *
					 * @since 1.0.2
					 *
					 * @param string $subject
					 * @param Forminator_Form_Model - the current form
					 *
					 * @return string $subject
					 */
					$subject = apply_filters( 'forminator_custom_form_mail_admin_subject', $subject, $custom_form, $data, $entry, $this );

					/**
					 * Custom form mail message filter
					 *
					 * @since 1.0.2
					 *
					 * @param string $message
					 * @param Forminator_Form_Model - the current form
					 *
					 * @return string $message
					 */
					if ( $exceeded && isset( $notification['email-attachment'] ) && 'true' === $notification['email-attachment'] ) {
						$message .= '<p style="color:#f2ac40;"><em>' . esc_html__( 'Note: Attachments were not included due to size limits.', 'forminator' ) . '</em></p>';
					}

					$message = apply_filters( 'forminator_custom_form_mail_admin_message', $message, $custom_form, $data, $entry, $this );

					$headers = $this->prepare_headers( $notification, $custom_form, $data, $entry );
					$this->set_headers( $headers );

					$this->set_subject( $subject );
					$this->set_recipients( $recipients );
					$this->set_message_with_vars( $this->message_vars, $message );
					$this->set_pdfs( $notification );
					if ( ! empty( $attachments ) && isset( $notification['email-attachment'] ) && 'true' === $notification['email-attachment'] ) {
						$this->set_attachment( $attachments, $custom_form, $entry );
					} else {
						$this->set_attachment( array(), $custom_form, $entry );
					}

					// If draft, get the wp_mail response.
					if ( isset( $notification['type'] ) && 'save_draft' === $notification['type'] ) {
						return $this->send_multiple();
					} else {
						$this->send_multiple();
					}

					/**
					 * Action called after admin mail sent
					 *
					 * @param Forminator_CForm_Front_Mail - the current form
					 * @param Forminator_Form_Model - the current form
					 * @param array                       $data       - current data.
					 * @param Forminator_Form_Entry_Model $entry      - saved entry @since 1.0.3.
					 * @param array                       $recipients - array or recipients.
					 */
					do_action( 'forminator_custom_form_mail_admin_sent', $this, $custom_form, $data, $entry, $recipients );
				}
			}
			/**
			 * Action called after mail is sent
			 *
			 * @param Forminator_CForm_Front_Mail - the current form
			 * @param Forminator_Form_Model - the current form
			 * @param array $data - current data.
			 */
			do_action( 'forminator_custom_form_mail_after_send_mail', $this, $custom_form, $data );
		} finally {
			// Always reset email context, even if there's an error.
			self::$is_email_context = false;
		}
	}

	/**
	 * Prepare headers.
	 *
	 * @param array  $notification Settings.
	 * @param object $custom_form Module.
	 * @param array  $data Submitted data.
	 * @param object $entry Saved entry.
	 * @return array
	 */
	private function prepare_headers( $notification, $custom_form, $data, $entry ) {

		$from_name = $this->replace_placeholders( $notification, 'from-name', $custom_form, $entry, true );
		if ( empty( $from_name ) ) {
			$from_name = $this->sender_name;
		}
		/**
		 * Filter `From` name of mail that send to admin
		 *
		 * @since 1.5
		 *
		 * @param string                       $from_name
		 * @param Forminator_Form_Model $custom_form Current Form Model.
		 * @param array                        $data        POST data.
		 * @param Forminator_Form_Entry_Model  $entry       entry model.
		 * @param Forminator_CForm_Front_Mail  $this        mail class.
		 */
		$from_name = apply_filters( 'forminator_custom_form_mail_admin_from_name', $from_name, $custom_form, $data, $entry, $this );

		$from_email = $this->replace_placeholders( $notification, 'form-email', $custom_form, $entry );
		if ( ! is_email( $from_email ) ) {
			$from_email = $this->sender_email;
		}
		/**
		 * Filter `From` email address of mail that send to admin
		 *
		 * @since 1.5
		 *
		 * @param string                       $from_email
		 * @param Forminator_Form_Model $custom_form Current Form Model.
		 * @param array                        $data        POST data.
		 * @param Forminator_Form_Entry_Model  $entry       entry model.
		 * @param Forminator_CForm_Front_Mail  $this        mail class.
		 */
		$from_email = apply_filters( 'forminator_custom_form_mail_admin_from_email', $from_email, $custom_form, $data, $entry, $this );

		$reply_to_address = trim( $this->replace_placeholders( $notification, 'replyto-email', $custom_form, $entry ) );
		if ( ! is_email( $reply_to_address ) ) {
			$reply_to_address = '';
		}
		/**
		 * Filter `Reply To` email address of mail that send to admin
		 *
		 * @since 1.5
		 *
		 * @param string                       $reply_to_address
		 * @param Forminator_Form_Model $custom_form Current Form Model.
		 * @param array                        $data        POST data.
		 * @param Forminator_Form_Entry_Model  $entry       entry model.
		 * @param Forminator_CForm_Front_Mail  $this        mail class.
		 */
		$reply_to_address = apply_filters( 'forminator_custom_form_mail_admin_reply_to', $reply_to_address, $custom_form, $data, $entry, $this );

		$notification_cc_addresses = $this->replace_placeholders( $notification, 'cc-email', $custom_form, $entry );
		$notification_cc_addresses = array_map( 'trim', explode( ',', $notification_cc_addresses ) );

		$cc_addresses = array();
		foreach ( $notification_cc_addresses as $key => $notification_cc_address ) {
			if ( is_email( $notification_cc_address ) ) {
				$cc_addresses[] = $notification_cc_address;
			}
		}
		/**
		 * Filter `CC` email addresses of mail that send to admin
		 *
		 * @since 1.5
		 *
		 * @param array                        $cc_addresses
		 * @param Forminator_Form_Model $custom_form Current Form Model.
		 * @param array                        $data        POST data.
		 * @param Forminator_Form_Entry_Model  $entry       entry model.
		 * @param Forminator_CForm_Front_Mail  $this        mail class.
		 *
		 * @since 1.51.0 Added $notification parameter.
		 * @param array $notification Notification.
		 */
		$cc_addresses = apply_filters( 'forminator_custom_form_mail_admin_cc_addresses', $cc_addresses, $custom_form, $data, $entry, $this, $notification );

		$notification_bcc_addresses = $this->replace_placeholders( $notification, 'bcc-email', $custom_form, $entry );
		$notification_bcc_addresses = array_map( 'trim', explode( ',', $notification_bcc_addresses ) );

		$bcc_addresses = array();
		foreach ( $notification_bcc_addresses as $key => $notification_bcc_address ) {
			if ( is_email( $notification_bcc_address ) ) {
				$bcc_addresses[] = $notification_bcc_address;
			}
		}
		/**
		 * Filter `BCC` email addresses of mail that send to admin
		 *
		 * @since 1.5
		 *
		 * @param array                        $bcc_addresses
		 * @param Forminator_Form_Model $custom_form Current Form Model.
		 * @param array                        $data        POST data.
		 * @param Forminator_Form_Entry_Model  $entry       entry model.
		 * @param Forminator_CForm_Front_Mail  $this        mail class.
		 */
		$bcc_addresses = apply_filters( 'forminator_custom_form_mail_admin_bcc_addresses', $bcc_addresses, $custom_form, $data, $entry, $this );

		$content_type = $this->content_type;
		/**
		 * Filter `Content-Type` of mail that send to admin
		 *
		 * @since 1.5
		 *
		 * @param string                       $content_type
		 * @param Forminator_Form_Model $custom_form Current Form Model.
		 * @param array                        $data        POST data.
		 * @param Forminator_Form_Entry_Model  $entry       entry model.
		 * @param Forminator_CForm_Front_Mail  $this        mail class.
		 */
		$content_type = apply_filters( 'forminator_custom_form_mail_admin_content_type', $content_type, $custom_form, $data, $entry, $this );

		$headers = array();

		// only change From header if these two are valid.
		if ( ! empty( $from_name ) && ! empty( $from_email ) ) {
			$headers[] = 'From: ' . $from_name . ' <' . $from_email . '>';
		}

		if ( ! empty( $reply_to_address ) ) {
			$headers[] = 'Reply-To: ' . $reply_to_address;
		}

		if ( ! empty( $cc_addresses ) && is_array( $cc_addresses ) ) {
			$headers[] = 'Cc: ' . implode( ', ', $cc_addresses );
		}

		if ( ! empty( $bcc_addresses ) && is_array( $bcc_addresses ) ) {
			$headers[] = 'Bcc: ' . implode( ', ', $bcc_addresses );
		}

		if ( ! empty( $content_type ) ) {
			$headers[] = 'Content-Type: ' . $content_type;
		}

		/**
		 * Filter headers of mail that send to admin
		 *
		 * @since 1.5
		 *
		 * @param array                        $headers
		 * @param Forminator_Form_Model $custom_form Current Form Model.
		 * @param array                        $data        POST data.
		 * @param Forminator_Form_Entry_Model  $entry       entry model.
		 * @param Forminator_CForm_Front_Mail  $this        mail class.
		 */
		$headers = apply_filters( 'forminator_custom_form_mail_admin_headers', $headers, $custom_form, $data, $entry, $this );

		return $headers;
	}

	/**
	 * Get user email from data
	 *
	 * @since 1.0.3
	 *
	 * @param int                   $data Data.
	 * @param Forminator_Form_Model $custom_form Form model.
	 *
	 * @return bool|string
	 */
	public function get_user_email_data( $data, $custom_form ) {
		// Get form fields.
		$fields = $custom_form->get_fields();
		if ( ! is_null( $fields ) ) {
			foreach ( $fields as $field ) {
				$field_array = $field->to_formatted_array();
				$field_type  = $field_array['type'];

				// Check if field is email.
				if ( 'email' === $field_type ) {
					$field_id = $field_array['element_id'];
					if ( isset( $data[ $field_id ] ) && ! empty( $data[ $field_id ] ) ) {
						return apply_filters(
							'forminator_get_user_email_data',
							$data[ $field_id ],
							$data,
							$custom_form,
							$this
						);
					}
				}
			}
		}

		return false;
	}

	/**
	 * Get user email
	 *
	 * @since 1.0.3
	 *
	 * @param int                   $data Data.
	 * @param Forminator_Form_Model $custom_form Form model.
	 *
	 * @return bool
	 */
	public function get_user_email( $data, $custom_form ) {
		$email      = false;
		$data_email = $this->get_user_email_data( $data, $custom_form );

		if ( $data_email && ! empty( $data_email ) ) {
			// We have data email, use it.
			$email = $data_email;
		} elseif ( is_user_logged_in() ) { // Check if user logged in.
			$email = $this->message_vars['user_email'];
		}

		return apply_filters( 'forminator_get_user_email', $email, $data, $custom_form, $data_email, $this );
	}

	/**
	 * Set Sender Email
	 *
	 * @since 1.1
	 *
	 * @param string $email - email address.
	 */
	public function set_sender_email( $email ) {
		$this->sender_email = $email;
	}

	/**
	 * Set Sender Name
	 *
	 * @since 1.1
	 *
	 * @param string $name - sender name.
	 */
	public function set_sender_name( $name ) {
		$this->sender_name = $name;
	}

	/**
	 * Get Recipient
	 *
	 * @param string $recipient Recipient.
	 * @param mixed  $custom_form Custom form.
	 * @param mixed  $entry Entry.
	 * @param mixed  $lead_model Lead model.
	 *
	 * @return string
	 */
	public function get_recipient( $recipient, $custom_form, $entry, $lead_model ) {
		$settings  = array( 'recipient' => $recipient );
		$recipient = $this->replace_placeholders( $settings, 'recipient', $custom_form, $entry );

		return $recipient;
	}

	/**
	 * Check if all conditions are met to send user email
	 *
	 * @since 1.0
	 *
	 * @param array $setting - the form settings.
	 *
	 * @return bool
	 */
	public function send_user_mail( $setting ) {
		if ( isset( $setting['use-user-email'] ) && ! empty( $setting['use-user-email'] ) ) {
			if ( filter_var( $setting['use-user-email'], FILTER_VALIDATE_BOOLEAN ) ) {
				if ( isset( $setting['user-email-title'] ) && isset( $setting['user-email-editor'] ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Get Recipients of user emails
	 *
	 * @since 1.6
	 *
	 * @param array                       $data submitted data.
	 * @param Forminator_Form_Model       $custom_form Form model.
	 * @param Forminator_Form_Entry_Model $entry Entry model.
	 *
	 * @return array
	 */
	public function get_user_email_recipients( $data, $custom_form, $entry ) {
		$email   = array();
		$setting = $custom_form->settings;
		if ( ! isset( $setting['user-email-recipients'] ) || ! is_array( $setting['user-email-recipients'] ) || empty( $setting['user-email-recipients'] ) ) {
			$default_email = $this->get_user_email( $data, $custom_form );
			$email         = array( $default_email );

		} else {

			$setting_recipients = $setting['user-email-recipients'];

			foreach ( $setting_recipients as $key => $setting_recipient ) {
				$setting_recipient = $this->replace_placeholders( $setting_recipients, $key, $custom_form, $entry );
				if ( is_email( $setting_recipient ) ) {
					$email[] = $setting_recipient;
				}
			}
		}

		return apply_filters( 'forminator_get_user_email_recipients', $email, $setting, $data, $custom_form, $entry );
	}

	/**
	 * Check if Field is hidden based on conditions property and POST-ed data
	 *
	 * @since 1.0
	 *
	 * @param array $notification Notification.
	 *
	 * @return bool
	 */
	public function is_condition( $notification ) {
		// empty conditions.
		if ( empty( $notification['conditions'] ) ) {
			return false;
		}

		$condition_action = isset( $notification['condition_action'] ) ? $notification['condition_action'] : 'send';
		$condition_rule   = isset( $notification['condition_rule'] ) ? $notification['condition_rule'] : 'all';

		$condition_fulfilled = 0;

		$all_conditions = $notification['conditions'];

		foreach ( $all_conditions as $condition ) {
			$is_condition_fulfilled = Forminator_Field::is_condition_matched( $condition );
			if ( $is_condition_fulfilled ) {
				++$condition_fulfilled;
			}
		}

		// initialized as hidden.
		if ( 'send' === $condition_action ) {
			if ( ( $condition_fulfilled > 0 && 'any' === $condition_rule ) || ( count( $all_conditions ) === $condition_fulfilled && 'all' === $condition_rule ) ) {
				return false;
			}

			return true;
		} else {
			// initialized as shown.
			if ( ( $condition_fulfilled > 0 && 'any' === $condition_rule ) || ( count( $all_conditions ) === $condition_fulfilled && 'all' === $condition_rule ) ) {
				return true;
			}

			return false;
		}
	}

	/**
	 * Check if notification is routing
	 *
	 * @since 1.0
	 *
	 * @param array $condition Condition.
	 * @param mixed $module Module.
	 *
	 * @return bool
	 */
	public function is_routing( $condition, $module ) {
		return Forminator_Field::is_condition_matched( $condition );
	}

	/**
	 * Remove Stripe quantity if payment_type is single/One time
	 *
	 * @since 1.19.0
	 *
	 * @param object $entry Forminator_Form_Entry_Model.
	 *
	 * @return Forminator_Form_Entry_Model
	 */
	public function maybe_remove_stripe_quantity( $entry ) {
		if ( isset( $entry->meta_data ) ) {
			foreach ( $entry->meta_data as $key => $val ) {
				if ( 0 === strpos( $key, 'stripe-' ) && ! empty( $val ) && ! empty( $val['value'] ) ) {
					if (
						isset( $val['value']['payment_type'] ) &&
						strtolower( esc_html__( 'One Time', 'forminator' ) ) === strtolower( $val['value']['payment_type'] )
					) {
						unset( $entry->meta_data[ $key ]['value']['quantity'] );
					}
				}
			}
		}

		return $entry;
	}
}
