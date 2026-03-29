<?php
/**
 * The Forminator_Mixpanel_Modules class.
 *
 * @package Forminator
 */

/**
 * Mixpanel Modules class
 */
class Forminator_Mixpanel_Modules extends Events {

	/**
	 * Stripe OCS data
	 *
	 * @var array
	 */
	public static array $stripe_ocs = array();

	/**
	 * Initialize class.
	 *
	 * @since 1.27.0
	 */
	public static function init() {
		// Publish tracking.
		add_action( 'forminator_custom_form_action_update', array( __CLASS__, 'tracking_form_publish' ), 10, 5 );
		add_action( 'forminator_poll_action_update', array( __CLASS__, 'tracking_poll_publish' ), 10, 4 );
		add_action( 'forminator_quiz_action_update', array( __CLASS__, 'tracking_quiz_publish' ), 10, 6 );

		// Delete tracking.
		add_action( 'forminator_form_action_delete', array( __CLASS__, 'tracking_form_delete' ) );
		add_action( 'forminator_poll_action_delete', array( __CLASS__, 'tracking_poll_delete' ) );
		add_action( 'forminator_quiz_action_delete', array( __CLASS__, 'tracking_quiz_delete' ) );

		// Template tracking.
		add_action( 'forminator_after_template_save', array( __CLASS__, 'tracking_save_template' ), 10, 2 );
	}

	/**
	 * Form publish
	 *
	 * @param int    $id Form id.
	 * @param string $title Title.
	 * @param string $status Form status.
	 * @param array  $fields Fields wrapper.
	 * @param array  $settings Settings.
	 *
	 * @return void
	 * @since 1.27.0
	 */
	public static function tracking_form_publish( $id, $title, $status, $fields, $settings ) {
		$form_status = get_post_status( $id );
		if ( 'pdf_form' === $form_status || 'leads' === $form_status ) {
			return;
		}

		$properties = self::module_properties( $id, 'form', $status, $fields, $settings );

		self::track_event( 'for_form_published', $properties );
		self::track_module_updates( $properties['Update type'] );
	}

	/**
	 * Poll publish
	 *
	 * @param int    $id Poll id.
	 * @param string $status Poll status.
	 * @param array  $answers Answer.
	 * @param array  $settings Settings.
	 *
	 * @return void
	 * @since 1.27.0
	 */
	public static function tracking_poll_publish( $id, $status, $answers, $settings ) {
		$properties = self::module_properties( $id, 'poll', $status, $answers, $settings );

		self::track_event( 'for_poll_published', $properties );
		self::track_module_updates( $properties['Update type'] );
	}

	/**
	 * Track quiz publish
	 *
	 * @param int    $id Quiz id.
	 * @param string $type Quiz type.
	 * @param string $status Quiz status.
	 * @param array  $questions Questions.
	 * @param array  $results Result.
	 * @param array  $settings Settings.
	 *
	 * @return void
	 * @since 1.27.0
	 */
	public static function tracking_quiz_publish( $id, $type, $status, $questions, $results, $settings ) {
		$data       = array(
			'type'      => $type,
			'questions' => $questions,
			'result'    => $results,
		);
		$properties = self::module_properties( $id, 'quiz', $status, $data, $settings );

		self::track_event( 'for_quiz_published', $properties );
		self::track_module_updates( $properties['Update type'] );
	}

	/**
	 * Track delete form
	 *
	 * @param int $module_id Module Id.
	 *
	 * @return void
	 * @since 1.27.0
	 */
	public static function tracking_form_delete( $module_id ) {
		self::delete_module( $module_id, 'form' );
		self::track_module_updates( 'delete' );
	}

	/**
	 * Track delete poll
	 *
	 * @param int $module_id Module Id.
	 *
	 * @return void
	 * @since 1.27.0
	 */
	public static function tracking_poll_delete( $module_id ) {
		self::delete_module( $module_id, 'poll' );
		self::track_module_updates( 'delete' );
	}

	/**
	 * Track delete quiz
	 *
	 * @param int $module_id Module Id.
	 *
	 * @return void
	 * @since 1.27.0
	 */
	public static function tracking_quiz_delete( $module_id ) {
		self::delete_module( $module_id, 'quiz' );
		self::track_module_updates( 'delete' );
	}

	/**
	 * Track module update
	 *
	 * @param string $update_type Update type.
	 *
	 * @return void
	 */
	public static function track_module_updates( $update_type ) {
		$properties = Forminator_Mixpanel::module_updates( $update_type );
		self::track_event( 'for_module_updated', $properties );
	}

	/**
	 * Tracking save cloud template
	 *
	 * @param int      $form_id Form ID.
	 * @param int|null $template_id Template ID.
	 *
	 * @since 1.35.0
	 */
	public static function tracking_save_template( int $form_id, ?int $template_id = null ) {
		self::track_event(
			'for_form_save_to_cloud',
			array(
				'Save Type' => ! empty( $template_id ) ? 'Update Template' : 'New Template',
			),
		);
	}

	/**
	 * Module properties
	 *
	 * @param int    $module_id Module Id.
	 * @param string $module_slug Module slug.
	 * @param string $status Status.
	 * @param array  $module_data Module data.
	 * @param array  $settings Settings.
	 *
	 * @return array
	 */
	private static function module_properties( $module_id, $module_slug, $status, $module_data, $settings ) {
		$default = array(
			'ID'                  => intval( $module_id ),
			'Update type'         => self::module_status( $status, $settings ),
			'Active Integrations' => self::module_integration( $module_id, $module_slug ),
		);

		switch ( $module_slug ) {
			case 'form':
				$properties = self::form_properties( $module_id, $module_data, $settings );
				break;
			case 'poll':
				$properties = self::poll_properties( $module_data, $settings );
				break;
			case 'quiz':
				$properties = self::quiz_properties( $module_data, $settings );
				break;
			default:
				$properties = array();
				break;
		}

		return array_merge( $default, $properties );
	}

	/**
	 * Get module addon
	 *
	 * @param int    $module_id Module Id.
	 * @param string $module_slug Module slug.
	 *
	 * @return string
	 */
	private static function module_integration( $module_id, $module_slug ) {
		$addons           = array();
		$connected_addons = forminator_get_registered_addons_grouped_by_module_connected( $module_id, $module_slug, true );
		if ( ! empty( $connected_addons['connected'] ) ) {
			foreach ( $connected_addons['connected'] as $addon ) {
				$addons[] = esc_html( $addon['short_title'] );
			}
		}

		return implode( ', ', $addons );
	}

	/**
	 * Form Properties
	 *
	 * @param int   $module_id Module Id.
	 * @param array $fields Fields.
	 * @param array $settings Settings.
	 *
	 * @return array
	 */
	private static function form_properties( $module_id, $fields, $settings ) {
		$property       = array();
		$form_style     = self::settings_value( $settings, 'form-style', 'default' );
		$form_sub_style = self::settings_value( $settings, 'form-substyle', 'default' );
		$form_style     = 'default' === $form_style ? $form_sub_style : $form_style;

		$property['List of fields']      = self::fields_list( $fields );
		$property['Design Style']        = $form_style;
		$property['Save and Continue']   = self::settings_value( $settings, 'use_save_and_continue', false );
		$property['Email Notifications'] = self::settings_value( $settings, 'notification_count', 0 );
		$property['CAPTCHA Type']        = self::captcha_type( $fields );

		if ( isset( self::$stripe_ocs['payment_method'] ) ) {
			if ( 'false' === self::$stripe_ocs['payment_method'] ) {
				$property['Stripe Payment Method'] = 'card only';
			} else {
				$property['Stripe Payment Method'] = 'dynamic';
			}
		}

		list( $confirm_email, $filter_type ) = self::email_field_properties( $fields );
		$property['Confirm Email']           = $confirm_email;
		$property['Filter Email Provider']   = $filter_type;

		if ( isset( self::$stripe_ocs['mode'] ) ) {
			$property['Stripe Mode'] = 'live' === self::$stripe_ocs['mode'] ? 'live' : 'test';
		}

		// Addon data.
		$addon_data                            = self::addon_data( $module_id, $fields, $settings );
		$property['PDF Addon']                 = $addon_data['pdf_addon'];
		$property['Stripe Subscription Addon'] = $addon_data['stripe_addon'];
		$property['Geolocation']               = $addon_data['geo_addon'];

		// Template properties.
		$template_name = self::settings_value( $settings, 'template_name' );
		if ( ! empty( $template_name ) ) {
			$property['Template Name'] = esc_html( $template_name );
		}

		$template_type = self::settings_value( $settings, 'template_type' );
		if ( ! empty( $template_type ) ) {
			$property['Template Type'] = ucfirst( $template_type ) . ' Template';
		}

		$trigger_from = self::settings_value( $settings, 'trigger_from' );
		if ( ! empty( $trigger_from ) ) {
			$property['Triggered From'] = 'template' === $trigger_from ? 'Template Page' : 'Form Builder';
		}

		return $property;
	}

	/**
	 * Poll Properties
	 *
	 * @param array $answer Answer.
	 * @param array $settings Settings.
	 *
	 * @return array
	 */
	private static function poll_properties( $answer, $settings ) {
		$property                      = array();
		$property['Number of Answers'] = count( $answer );
		$property['Design Style']      = self::settings_value( $settings, 'forminator-poll-design', 'default' );
		$property['Results']           = self::settings_value( $settings, 'results-behav', 'not_show' );
		$property['Vote count']        = self::settings_value( $settings, 'show-votes-count', false );

		return $property;
	}

	/**
	 * Quiz Properties
	 *
	 * @param array $module_data Module Data.
	 * @param array $settings Settings.
	 *
	 * @return array
	 */
	private static function quiz_properties( $module_data, $settings ) {
		$property = array();

		$property['Type of Quiz']        = 'nowrong' === $module_data['type'] ? 'Personality' : 'Knowledge';
		$property['Collect Leads']       = self::settings_value( $settings, 'hasLeads', false );
		$property['Number of Questions'] = count( $module_data['questions'] );

		// Quiz Pagination data.
		$pagination             = self::settings_value( $settings, 'pagination', '' );
		$property['Pagination'] = 'true' === $pagination ? 'Paginated' : 'No Pagination';

		// Quiz result data.
		$quiz_result         = self::settings_value( $settings, 'results_behav', 'after' );
		$property['Results'] = 'end' === $quiz_result ? 'On submission' : 'Real-time';

		return $property;
	}

	/**
	 * Field List
	 *
	 * @param array $fields Fields.
	 *
	 * @return string
	 */
	private static function fields_list( $fields ) {
		$field_list  = array();
		$field_array = self::fields_array( $fields, 'all' );

		if ( ! empty( $field_array ) ) {
			foreach ( $field_array as $field ) {
				if ( str_starts_with( $field['element_id'], 'group-' ) ) {
					if ( ! empty( $field['group_field'] ) ) {
						$group_list = array();
						foreach ( $field['group_field'] as $group_field ) {
							$group_list[] = ucfirst( esc_html( $group_field['type'] ) );
						}
						$group_name   = ucfirst( $field['element_id'] );
						$field_list[] = $group_name . ' [' . implode( ', ', $group_list ) . ']';
					}
				} else {
					$field_list[] = ucfirst( esc_html( $field['type'] ) );
					if ( ! empty( $field['type'] ) && 'stripe-ocs' === $field['type'] ) {
						self::$stripe_ocs = array(
							'mode'           => $field['mode'] ?? '',
							'payment_method' => $field['automatic_payment_methods'] ?? '',
						);
					}
				}
			}
		}

		return implode( ', ', $field_list );
	}

	/**
	 * Addon data
	 *
	 * @param int   $moduel_id Module Id.
	 * @param array $fields Fields.
	 * @param array $settings Settings.
	 *
	 * @return array
	 */
	private static function addon_data( $moduel_id, $fields, $settings ) {
		$addon_data = array(
			'stripe_addon' => false,
			'pdf_addon'    => 0,
			'geo_addon'    => '',
		);

		// Stripe data.
		if ( is_plugin_active( 'forminator-stripe/forminator-stripe.php' ) ) {
			$stripe_data = self::fields_array( $fields, 'stripe-ocs' );
			if ( empty( $stripe_data ) ) {
				$stripe_data = self::fields_array( $fields, 'stripe' );
			}
			if ( ! empty( $stripe_data[0]['payments'] ) ) {
				$addon_data['stripe_addon'] = in_array( 'subscription', array_column( $stripe_data[0]['payments'], 'payment_method' ), true );
			}
		}

		// PDF Data.
		if ( is_plugin_active( 'forminator-addons-pdf/forminator-addons-pdf.php' ) ) {
			$pdf_array = ( new Forminator_Custom_Form_Admin() )->get_pdf_data( $moduel_id );
			if ( ! empty( $pdf_array ) ) {
				$addon_data['pdf_addon'] = count( $pdf_array );
			}
		}

		// Geolocation Data.
		if ( is_plugin_active( 'forminator-addons-geolocation/forminator-geolocation.php' ) ) {
			$address_data     = self::fields_array( $fields, 'address' );
			$current_location = self::settings_value( $settings, 'geolocation_field', false );
			$geo_data         = array();
			if ( ! empty( $address_data ) ) {
				foreach ( $address_data as $data ) {
					if ( isset( $data['show_map'] ) && 'show' === $data['show_map'] ) {
						$geo_data[] = 'Show Map';
					}
					if ( isset( $data['auto_suggest'] ) && $data['auto_suggest'] ) {
						$geo_data[] = 'Autocomplete';
					}
				}
			}
			if ( $current_location ) {
				$geo_data[] = 'Current Location';
			}
			$addon_data['geo_addon'] = implode( ', ', $geo_data );
		}

		return $addon_data;
	}

	/**
	 * Field array
	 *
	 * @param array  $fields Fields.
	 * @param string $field_type Field type.
	 *
	 * @return array
	 */
	private static function fields_array( $fields, $field_type = '' ) {
		$field_array = array();
		if ( ! empty( $fields ) ) {
			foreach ( $fields as $row ) {
				foreach ( $row['fields'] as $field ) {
					if ( $field_type === $field['type'] ) {
						$field_array[] = $field;
					} elseif ( 'all' === $field_type && empty( $row['parent_group'] ) ) {
						if ( 'group' === $field['type'] ) {
							$group_element              = $field['element_id'];
							$group_fields               = self::get_grouped_fiels( $fields, $group_element );
							$group_array['element_id']  = $group_element;
							$group_array['group_field'] = self::fields_array( $group_fields, 'group' );
							$field_array[]              = $group_array;
						} else {
							$field_array[] = $field;
						}
					} elseif ( 'group' === $field_type ) {
						$field_array[] = $field;
					}
				}
			}
		}

		return $field_array;
	}

	/**
	 * Get captcha provider label.
	 *
	 * @param array $fields Fields.
	 *
	 * @return string
	 */
	private static function captcha_type( $fields ) {
		$captcha_data = self::fields_array( $fields, 'captcha' );
		$captcha_type = '';
		if ( ! empty( $captcha_data ) ) {
			$provider = $captcha_data[0]['captcha_provider'] ?? '';
			if ( 'recaptcha' === $provider ) {
				$captcha_type = 'reCAPTCHA';
			} elseif ( 'hcaptcha' === $provider ) {
				$captcha_type = 'hCaptcha';
			} elseif ( 'turnstile' === $provider ) {
				$captcha_type = 'Cloudflare Turnstile';
			}
		}

		return $captcha_type;
	}

	/**
	 * Get email field properties.
	 *
	 * @param array $fields Fields.
	 *
	 * @return array
	 */
	private static function email_field_properties( $fields ) {
		$confirm_email         = 'Disabled';
		$filter_email_provider = 'None';
		$email_fields          = self::fields_array( $fields, 'email' );
		if ( ! empty( $email_fields ) ) {
			foreach ( $email_fields as $field ) {
				if ( 'Disabled' === $confirm_email && ! empty( $field['confirm-email'] ) && filter_var( $field['confirm-email'], FILTER_VALIDATE_BOOLEAN ) ) {
					$confirm_email = 'Enabled';
				}
				if ( 'None' === $filter_email_provider && ! empty( $field['filter_type'] ) ) {
					if ( 'deny' === $field['filter_type'] && ! empty( $field['denylist'] ) ) {
						$filter_email_provider = 'Deny';
					} elseif ( 'allow' === $field['filter_type'] && ! empty( $field['allowlist'] ) ) {
						$filter_email_provider = 'Allow';
					}
				}
			}
		}

		return array( $confirm_email, $filter_email_provider );
	}

	/**
	 * Module status
	 *
	 * @param string $status Status.
	 * @param array  $settings Settings.
	 *
	 * @return string|void
	 */
	private static function module_status( $status, $settings ) {
		if ( ! isset( $settings['previous_status'] ) ) {
			return;
		}
		if ( 'draft' === $status ) {
			return 'draft';
		} elseif ( 'draft' === $settings['previous_status'] ) {
			return 'publish';
		} else {
			return 'update';
		}
	}

	/**
	 * Delete module
	 *
	 * @param int    $module_id Module Id.
	 * @param string $module_type Module type.
	 *
	 * @return void
	 */
	private static function delete_module( $module_id, $module_type ) {

		$properties = array(
			'ID'          => $module_id,
			'Update Type' => 'removed',
		);

		self::track_event( 'for_' . $module_type . '_removed', $properties );
	}

	/**
	 * Get filtered wrappers by group. If group ID is empty - it returns ungrouped wrappers
	 *
	 * @param array  $fields Fields wrapper.
	 * @param string $group_id Group ID.
	 *
	 * @return array
	 */
	public static function get_grouped_fiels( $fields, $group_id = '' ) {
		return array_filter(
			$fields,
			function ( $value ) use ( $group_id ) {
				return ! $group_id ? empty( $value['parent_group'] ) : ! empty( $value['parent_group'] ) && $group_id === $value['parent_group'];
			}
		);
	}
}
