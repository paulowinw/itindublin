<?php
/**
 * The Forminator_Quiz_Front_Mail class.
 *
 * @package Forminator
 */

/**
 * Forminator_Quiz_Front_Mail
 *
 * @since 1.6.2
 */
class Forminator_Quiz_Front_Mail extends Forminator_Mail {
	/**
	 * Module slug
	 *
	 * @var string
	 */
	protected static $module_slug = 'quiz';

	/**
	 * Replace all placeholders for mail properties
	 *
	 * @param array  $settings Settings.
	 * @param string $option_name Current option name.
	 * @param object $module Module.
	 * @param object $entry Saved entry.
	 * @param object $lead_model Lead module.
	 * @return string
	 */
	private function replace_placeholders( $settings, $option_name, $module, $entry, $lead_model ) {
		if ( ! isset( $settings[ $option_name ] ) ) {
			return '';
		}

		$text = forminator_replace_variables( $settings[ $option_name ], $module->id, $entry );
		$text = forminator_replace_quiz_form_data( $text, $module, $entry );

		if ( $lead_model ) {
			$text = forminator_replace_form_data( $text, $lead_model, $entry );
			$text = forminator_replace_custom_form_data( $text, $lead_model, $entry );
		}

		return $text;
	}

	/**
	 * Process mail
	 *
	 * @since 1.6.2
	 *
	 * @param Forminator_Quiz_Model       $quiz Quiz model.
	 * @param Forminator_Form_Entry_Model $entry Form entry model.
	 * @param array                       $final_res Final response.
	 */
	public function process_mail( $quiz, Forminator_Form_Entry_Model $entry, $final_res = array() ) {
		self::$is_email_context = true;

		try {
			forminator_maybe_log( __METHOD__ );

			$setting       = $quiz->settings;
			$notifications = $quiz->notifications;
			$lead_model    = null;
			$result_slug   = isset( $final_res['slug'] ) ? $final_res['slug'] : '';
			$has_lead      = isset( $setting['hasLeads'] ) ? $setting['hasLeads'] : false;
			if ( $has_lead ) {
				$lead_id     = isset( $setting['leadsId'] ) ? $setting['leadsId'] : 0;
				$lead_model  = Forminator_Base_Form_Model::get_model( $lead_id );
				$form_fields = forminator_addon_format_form_fields( $lead_model );
				$lead_data   = forminator_addons_lead_submitted_data( $form_fields, $entry );
				// If lead data was missing and we have a lead entry id, try to reconstruct the lead data from the transient.
				if ( empty( $lead_data ) && isset( Forminator_Front_Action::$prepared_data['lead_entry_id'] ) ) {
					$lead_entry_id = Forminator_Front_Action::$prepared_data['lead_entry_id'];
					// Reconstruct the exact transient key we used when saving.
					$transient_key = 'forminator_lead_object_temporary_storage_' . $lead_entry_id;
					$lead_entry    = get_transient( $transient_key );
					if ( $lead_entry instanceof Forminator_Form_Entry_Model && $lead_entry->entry_id === $lead_entry_id ) {
						$lead_entry->entry_id = 0;
						$entry                = $lead_entry;
						$lead_data            = forminator_addons_lead_submitted_data( $form_fields, $entry );
						delete_transient( $transient_key );
					}
				}
				$files       = $this->get_lead_file_attachment( $lead_model, $entry );
				$print_value = ! empty( $lead_model->settings['print_value'] ) ? filter_var( $lead_model->settings['print_value'], FILTER_VALIDATE_BOOLEAN ) : false;

				Forminator_Front_Action::$prepared_data = array_merge( Forminator_Front_Action::$prepared_data, $lead_data );
				foreach ( Forminator_Front_Action::$prepared_data as $element => $element_value ) {
					if ( ! empty( $element_value ) && is_array( $element_value ) &&
					( stripos( $element, 'time-' ) !== false || stripos( $element, 'date-' ) !== false ) ) {
						foreach ( $element_value as $key => $value ) {
							$key_value = $element . '-' . $key;
							Forminator_Front_Action::$prepared_data[ $key_value ] = $value;
						}
					}
				}
			}

			/**
			 * Message data filter
			 *
			 * @since 1.6.2
			 *
			 * @param array                       $data - the post data.
			 * @param Forminator_Quiz_Model  $quiz - the quiz model.
			 * @param Forminator_Form_Entry_Model $entry
			 *
			 * @return array $data
			 */
			$data = apply_filters( 'forminator_quiz_mail_data', Forminator_Front_Action::$prepared_data, $quiz, $entry );

			/**
			 * Action called before mail is sent
			 *
			 * @param Forminator_Quiz_Front_Mail  $this - the current mail class.
			 * @param Forminator_Quiz_Model  $quiz - the current quiz.
			 * @param array                       $data - current data.
			 * @param Forminator_Form_Entry_Model $entry
			 */
			do_action( 'forminator_quiz_mail_before_send_mail', $this, $quiz, $data, $entry );

			if ( ! empty( $notifications ) ) {
				$this->init();
				// Process admin mail.
				foreach ( $notifications as $notification ) {

					if ( $this->is_condition( $notification, $data, $quiz, $result_slug ) ) {
						continue;
					}

					$recipients = $this->get_admin_email_recipients( $notification, $quiz, $entry, $lead_model );

					if ( ! empty( $recipients ) ) {
						$subject = $this->replace_placeholders( $notification, 'email-subject', $quiz, $entry, $lead_model );
						/**
						 * Quiz admin mail subject filter
						 *
						 * @since 1.6.2
						 *
						 * @param string                     $subject
						 * @param Forminator_Quiz_Model $quiz the current quiz modal.
						 *
						 * @return string $subject
						 */
						$subject = apply_filters( 'forminator_quiz_mail_admin_subject', $subject, $quiz, $data, $entry, $this );

						$message = $this->replace_placeholders( $notification, 'email-editor', $quiz, $entry, $lead_model );
						/**
						 * Quiz admin mail message filter
						 *
						 * @since 1.6.2
						 *
						 * @param string                     $message
						 * @param Forminator_Quiz_Model $quiz the current quiz.
						 * @param array                      $data
						 * @param Forminator_Quiz_Front_Mail $this
						 *
						 * @return string $message
						 */
						$message = apply_filters( 'forminator_quiz_mail_admin_message', $message, $quiz, $data, $entry, $this );

						$headers = $this->prepare_headers( $notification, $quiz, $data, $entry, $lead_model );
						$this->set_headers( $headers );

						$this->set_subject( $subject );
						$this->set_recipients( $recipients );
						$this->set_message_with_vars( $this->message_vars, $message );
						if ( ! empty( $files ) && isset( $notification['email-attachment'] ) && 'true' === $notification['email-attachment'] ) {
							$this->set_attachment( $files );
						} else {
							$this->set_attachment( array() );
						}
						$this->send_multiple();

						/**
						 * Action called after admin mail sent
						 *
						 * @param Forminator_Quiz_Front_Mail  $this       the mail class.
						 * @param Forminator_Quiz_Model  $quiz       the current quiz.
						 * @param array                       $data       - current data.
						 * @param Forminator_Form_Entry_Model $entry      - saved entry.
						 * @param array                       $recipients - array or recipients.
						 */
						do_action( 'forminator_quiz_mail_admin_sent', $this, $quiz, $data, $entry, $recipients );
					}
				}
			}

			/**
			 * Action called after mail is sent
			 *
			 * @param Forminator_Quiz_Front_Mail $this mail class.
			 * @param Forminator_Quiz_Model $quiz current quiz.
			 * @param array                      $data current data.
			 */
			do_action( 'forminator_quiz_mail_after_send_mail', $this, $quiz, $data );
		} finally {
			// Always reset email context, even if there's an error.
			self::$is_email_context = false;
		}
	}

	/**
	 * Prepare headers.
	 *
	 * @param array  $notification Settings.
	 * @param object $quiz Module.
	 * @param array  $data Submitted data.
	 * @param object $entry Saved entry.
	 * @param object $lead_model Lead module.
	 * @return array
	 */
	private function prepare_headers( $notification, $quiz, $data, $entry, $lead_model ) {
		$from_name = $this->replace_placeholders( $notification, 'from-name', $quiz, $entry, $lead_model );
		if ( empty( $from_name ) ) {
			$from_name = $this->sender_name;
		}
		/**
		 * Filter `From` name of mail that send to admin
		 *
		 * @since 1.6.2
		 *
		 * @param string                      $from_name Form name.
		 * @param Forminator_Quiz_Model  $quiz  current quiz Model.
		 * @param array                       $data  POST data.
		 * @param Forminator_Form_Entry_Model $entry entry model.
		 * @param Forminator_Quiz_Front_Mail  $this  mail class.
		 */
		$from_name = apply_filters( 'forminator_quiz_mail_admin_from_name', $from_name, $quiz, $data, $entry, $this );

		$from_email = $this->replace_placeholders( $notification, 'form-email', $quiz, $entry, $lead_model );
		if ( ! is_email( $from_email ) ) {
			$from_email = $this->sender_email;
		}
		/**
		 * Filter `From` email address of mail that send to admin
		 *
		 * @since 1.6.2
		 *
		 * @param string                      $from_email
		 * @param Forminator_Quiz_Model  $quiz  current quiz Model.
		 * @param array                       $data  POST data.
		 * @param Forminator_Form_Entry_Model $entry entry model.
		 * @param Forminator_Quiz_Front_Mail  $this  mail class.
		 */
		$from_email = apply_filters( 'forminator_quiz_mail_admin_from_email', $from_email, $quiz, $data, $entry, $this );

		$reply_to_address = trim( $this->replace_placeholders( $notification, 'replyto-email', $quiz, $entry, $lead_model ) );
		if ( ! is_email( $reply_to_address ) ) {
			$reply_to_address = '';
		}
		/**
		 * Filter `Reply To` email address of mail that send to admin
		 *
		 * @since 1.6.2
		 *
		 * @param string                      $reply_to_address
		 * @param Forminator_Quiz_Model  $quiz  current quiz Model.
		 * @param array                       $data  POST data.
		 * @param Forminator_Form_Entry_Model $entry entry model.
		 * @param Forminator_Quiz_Front_Mail  $this  mail class.
		 */
		$reply_to_address = apply_filters( 'forminator_quiz_mail_admin_reply_to', $reply_to_address, $quiz, $data, $entry, $this );

		$cc_addresses              = array();
		$notification_cc_addresses = $this->replace_placeholders( $notification, 'cc-email', $quiz, $entry, $lead_model );
		$notification_cc_addresses = array_map( 'trim', explode( ',', $notification_cc_addresses ) );
		foreach ( $notification_cc_addresses as $key => $notification_cc_address ) {
			if ( is_email( $notification_cc_address ) ) {
				$cc_addresses[] = $notification_cc_address;
			}
		}
		/**
		 * Filter `CC` email addresses of mail that send to admin
		 *
		 * @since 1.6.2
		 *
		 * @param array                       $cc_addresses
		 * @param Forminator_Quiz_Model  $quiz  current quiz Model.
		 * @param array                       $data  POST data.
		 * @param Forminator_Form_Entry_Model $entry entry model.
		 * @param Forminator_Quiz_Front_Mail  $this  mail class.
		 */
		$cc_addresses = apply_filters( 'forminator_quiz_mail_admin_cc_addresses', $cc_addresses, $quiz, $data, $entry, $this );

		$bcc_addresses              = array();
		$notification_bcc_addresses = $this->replace_placeholders( $notification, 'bcc-email', $quiz, $entry, $lead_model );
		$notification_bcc_addresses = array_map( 'trim', explode( ',', $notification_bcc_addresses ) );
		foreach ( $notification_bcc_addresses as $key => $notification_bcc_address ) {
			if ( is_email( $notification_bcc_address ) ) {
				$bcc_addresses[] = $notification_bcc_address;
			}
		}
		/**
		 * Filter `BCC` email addresses of mail that send to admin
		 *
		 * @since 1.6.2
		 *
		 * @param array                       $bcc_addresses
		 * @param Forminator_Quiz_Model  $quiz  current quiz Model.
		 * @param array                       $data  POST data.
		 * @param Forminator_Form_Entry_Model $entry entry model.
		 * @param Forminator_Quiz_Front_Mail  $this  mail class.
		 */
		$bcc_addresses = apply_filters( 'forminator_quiz_mail_admin_bcc_addresses', $bcc_addresses, $quiz, $data, $entry, $this );

		$content_type = $this->content_type;
		/**
		 * Filter `Content-Type` of mail that send to admin
		 *
		 * @since 1.6.2
		 *
		 * @param string                      $content_type
		 * @param Forminator_Quiz_Model  $quiz  current quiz Model.
		 * @param array                       $data  POST data.
		 * @param Forminator_Form_Entry_Model $entry entry model.
		 * @param Forminator_Quiz_Front_Mail  $this  mail class.
		 */
		$content_type = apply_filters( 'forminator_quiz_mail_admin_content_type', $content_type, $quiz, $data, $entry, $this );

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
		 * @since 1.6.2
		 *
		 * @param array                       $headers
		 * @param Forminator_Quiz_Model  $quiz  current quiz Model.
		 * @param array                       $data  POST data.
		 * @param Forminator_Form_Entry_Model $entry entry model.
		 * @param Forminator_Quiz_Front_Mail  $this  mail class.
		 */
		$headers = apply_filters( 'forminator_quiz_mail_admin_headers', $headers, $quiz, $data, $entry, $this );

		return $headers;
	}

	/**
	 * Get Recipient
	 *
	 * @param string $recipient Recipient.
	 * @param object $quiz Quiz model.
	 * @param object $entry Entry model.
	 * @param object $lead_model Lead model.
	 *
	 * @return string
	 */
	public function get_recipient( $recipient, $quiz, $entry, $lead_model ) {
		$recipient = forminator_replace_variables( $recipient, $quiz->id );
		$recipient = forminator_replace_quiz_form_data( $recipient, $quiz, $entry );
		if ( isset( $quiz->settings['hasLeads'] ) && $quiz->settings['hasLeads'] ) {
			$recipient = forminator_replace_form_data( $recipient, $lead_model, $entry );
			$recipient = forminator_replace_custom_form_data( $recipient, $lead_model, $entry );
		}

		return $recipient;
	}

	/**
	 * Lead file attachment
	 *
	 * @param object $lead_model Lead model.
	 * @param object $entry Entry model.
	 *
	 * @return array|mixed
	 */
	public function get_lead_file_attachment( $lead_model, $entry ) {
		$files       = array();
		$form_fields = $lead_model->get_fields();
		foreach ( $form_fields as $form_field ) {
			$field_array = $form_field->to_formatted_array();
			$field_type  = $field_array['type'];
			$field_id    = Forminator_Field::get_property( 'element_id', $field_array );
			if ( 'upload' === $field_type &&
					! in_array( $field_id, Forminator_CForm_Front_Action::$hidden_fields, true ) ) {
				$field_slug = isset( $entry->meta_data[ $form_field->slug ] ) ? $entry->meta_data[ $form_field->slug ] : '';
				if ( ! empty( $field_slug ) && ! empty( $field_slug['value']['file'] ) ) {
					$email_files = isset( $field_slug['value']['file'] ) ? $field_slug['value']['file']['file_path'] : array();
					$files[]     = is_array( $email_files ) ? $email_files : array( $email_files );
				}
			}
		}
		if ( ! empty( $files ) ) {
			$files = call_user_func_array( 'array_merge', $files );
		}

		return $files;
	}

	/**
	 * Check if notification is routing
	 *
	 * @since 1.0
	 *
	 * @param array  $routing Routing.
	 * @param object $quiz_model Quiz model.
	 *
	 * @return bool
	 */
	public function is_routing( $routing, $quiz_model ) {
		// empty conditions.
		if ( empty( $routing ) ) {
			return false;
		}

		$form_data = Forminator_CForm_Front_Action::$prepared_data;

		$element_id = $routing['element_id'];
		if ( stripos( $element_id, 'signature-' ) !== false ) {
			// We have signature field.
			$signature_id   = $element_id;
			$signature_data = '';
			if ( isset( $form_data[ $signature_id ] ) && isset( $form_data[ $signature_id ]['file']['file_url'] ) ) {
				$signature_data = $form_data[ $signature_id ]['file']['file_url'];
			}
			return self::is_condition_fulfilled( $signature_data, $routing );
		} elseif ( stripos( $element_id, 'url-' ) !== false ) {
			$parts = ! empty( $routing['value'] ) ? wp_parse_url( $routing['value'] ) : false;
			if ( false !== $parts ) {
				if ( ! isset( $parts['scheme'] ) ) {
					$routing['value'] = 'http://' . $routing['value'];
				}
			}
			return self::is_condition_fulfilled( $form_data[ $element_id ], $routing );
		} elseif ( stripos( $element_id, 'checkbox-' ) !== false || stripos( $element_id, 'radio-' ) !== false ) {
			return self::is_condition_fulfilled( $form_data[ $element_id ], $routing );
		} elseif ( stripos( $element_id, 'question-' ) !== false ) {
			$is_correct = self::is_correct_answer( $element_id, $form_data['answers'][ $element_id ], $quiz_model );
			return self::is_condition_fulfilled( $is_correct, $routing );
		} elseif ( 'final_result' === $element_id ) {
			return self::is_condition_fulfilled( $form_data[ $element_id ], $routing );
		} elseif ( ! isset( $form_data[ $element_id ] ) ) {
			return false;
		} else {
			return self::is_condition_fulfilled( $form_data[ $element_id ], $routing );
		}
	}

	/**
	 * Check if Field is hidden based on conditions property and POST-ed data
	 *
	 * @since 1.0
	 *
	 * @param array  $notification Notification.
	 * @param array  $form_data Form data.
	 * @param object $quiz_model Quiz model.
	 *
	 * @return bool
	 */
	public function is_condition( $notification, $form_data, $quiz_model ) {
		// empty conditions.
		if ( empty( $notification['conditions'] ) ) {
			return false;
		}

		$condition_action = isset( $notification['condition_action'] ) ? $notification['condition_action'] : 'send';
		$condition_rule   = isset( $notification['condition_rule'] ) ? $notification['condition_rule'] : 'all';

		$condition_fulfilled = 0;

		$all_conditions = $notification['conditions'];

		foreach ( $all_conditions as $condition ) {
			$element_id = $condition['element_id'];

			if ( stripos( $element_id, 'signature-' ) !== false ) {
				// We have signature field.
				$signature_id   = $element_id;
				$signature_data = '';
				if ( isset( $form_data[ $signature_id ] ) && isset( $form_data[ $signature_id ]['file']['file_url'] ) ) {
					$signature_data = $form_data[ $signature_id ]['file']['file_url'];
				}
				$is_condition_fulfilled = self::is_condition_fulfilled( $signature_data, $condition );
			} elseif ( stripos( $element_id, 'url-' ) !== false ) {
				// We have signature field.
				$parts = ! empty( $routing['value'] ) ? wp_parse_url( $condition['value'] ) : false;
				if ( false !== $parts ) {
					if ( ! isset( $parts['scheme'] ) ) {
						$condition['value'] = 'http://' . $condition['value'];
					}
				}
				$is_condition_fulfilled = self::is_condition_fulfilled( $form_data[ $element_id ], $condition );
			} elseif ( stripos( $element_id, 'checkbox-' ) !== false || stripos( $element_id, 'radio-' ) !== false ) {
				$is_condition_fulfilled = self::is_condition_fulfilled( $form_data[ $element_id ], $condition );
			} elseif ( stripos( $element_id, 'question-' ) !== false ) {

				$question_id = array_filter(
					$form_data['answers'],
					function ( $key ) use ( $element_id ) {
						return strpos( $key, $element_id ) !== false;
					},
					ARRAY_FILTER_USE_KEY
				);

				$is_correct             = self::is_correct_answer( $element_id, $form_data['answers'][ key( $question_id ) ], $quiz_model );
				$is_condition_fulfilled = self::is_condition_fulfilled( $is_correct, $condition );

			} elseif ( stripos( $element_id, 'result-' ) !== false ) {
				$result_id              = self::get_result_slug( $form_data );
				$is_condition_fulfilled = self::is_condition_fulfilled( $result_id, $condition );
			} elseif ( 'final_result' === $element_id ) {
				$is_condition_fulfilled = self::is_condition_fulfilled( $form_data[ $element_id ], $condition );
			} elseif ( ! isset( $form_data[ $element_id ] ) ) {
				$is_condition_fulfilled = false;
			} else {
				$is_condition_fulfilled = self::is_condition_fulfilled( $form_data[ $element_id ], $condition );
			}

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
}
