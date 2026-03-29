<?php
/**
 * The Forminator_Integration_Form_Hooks class.
 *
 * @package Forminator
 */

/**
 * Class Forminator_Integration
 * Any change(s) to this file is subject to:
 * - Properly Written DocBlock! (what is this, why is that, how to be like those, etc, as long as you want!)
 * - Properly Written Changelog!
 *
 * If you override any of these method, please add necessary hooks in it,
 * Which you can see below, as a reference and keep the arguments signature.
 * If needed you can call these method, as parent::method_name(),
 * and add your specific hooks.
 *
 * @since 1.1
 */
abstract class Forminator_Integration_Form_Hooks extends Forminator_Integration_Hooks {

	/**
	 * Module slug
	 *
	 * @var string
	 */
	protected static string $slug = 'form';

	/**
	 * Override this function to execute action before fields rendered
	 *
	 * If function generate output, it will output-ed,
	 * race condition between addon probably happen.
	 * Its void function, so return value will be ignored, and forminator process will always continue,
	 * unless it generates unrecoverable error, so please be careful on extending this function.
	 * If you want to `wp_enqueue_script` this might be the best place.
	 *
	 * @since 1.1
	 */
	public function on_before_render_form_fields() {
		/**
		 * Fires before form fields rendered by forminator
		 *
		 * Although it can be used for all addon.
		 * Please keep in mind that if the addon override this method,
		 * then this action won't be triggered.
		 * To be sure please check individual addon documentations.
		 *
		 * @since 1.1
		 *
		 * @param int                                          $form_id                current Form ID.
		 * @param Forminator_Integration_Form_Settings|null $form_settings_instance of Integration Form Settings.
		 */
		do_action(
			'forminator_addon_' . $this->addon->get_slug() . '_on_before_render_form_fields',
			$this->module_id,
			$this->settings_instance
		);
	}

	/**
	 * Override this function to execute action after all form fields rendered
	 *
	 * If function generate output, it will output-ed
	 * race condition between addon probably happen
	 * its void function, so return value will be ignored, and forminator process will always continue
	 * unless it generates unrecoverable error, so please be careful on extending this function
	 *
	 * @since 1.1
	 */
	public function on_after_render_form_fields() {
		/**
		 * Fires when addon rendering extra output after connected form fields rendered
		 *
		 * Although it can be used for all addon.
		 * Please keep in mind that if the addon override this method,
		 * then this action won't be triggered.
		 * To be sure please check individual addon documentations.
		 *
		 * @since 1.1
		 *
		 * @param int                                          $form_id                current Form ID.
		 * @param Forminator_Integration_Form_Settings|null $form_settings_instance of Integration Form Settings.
		 */
		do_action(
			'forminator_addon_' . $this->addon->get_slug() . '_on_after_render_form_fields',
			$this->module_id,
			$this->settings_instance
		);
	}

	/**
	 * Override this function to execute action after html markup form rendered completely
	 *
	 * If function generate output, it will output-ed
	 * race condition between addon probably happen
	 * its void function, so return value will be ignored, and forminator process will always continue
	 * unless it generates unrecoverable error, so please be careful on extending this function
	 *
	 * @since 1.1
	 */
	public function on_after_render_form() {
		/**
		 * Fires when connected form completely rendered
		 *
		 * Although it can be used for all addon.
		 * Please keep in mind that if the addon override this method,
		 * then this action won't be triggered.
		 * To be sure please check individual addon documentations.
		 *
		 * @since 1.1
		 *
		 * @param int                                          $form_id                current Form ID.
		 * @param Forminator_Integration_Form_Settings|null $form_settings_instance of Integration Form Settings.
		 */
		do_action(
			'forminator_addon_' . $this->addon->get_slug() . '_on_after_render_form',
			$this->module_id,
			$this->settings_instance
		);
	}

	/**
	 * Override this function to execute action after entry saved
	 *
	 * Its void function, so return value will be ignored, and forminator process will always continue
	 * unless it generates unrecoverable error, so please be careful on extending this function
	 *
	 * @since 1.1
	 *
	 * @param Forminator_Form_Entry_Model $entry_model Form entry model.
	 */
	public function after_entry_saved( Forminator_Form_Entry_Model $entry_model ) {
		$addon_slug             = $this->addon->get_slug();
		$form_id                = $this->module_id;
		$form_settings_instance = $this->settings_instance;

		/**
		 * Fires when entry already saved on db
		 *
		 * Although it can be used for all addon.
		 * Please keep in mind that if the addon override this method,
		 * then this filter probably won't be applied.
		 * To be sure please check individual addon documentations.
		 *
		 * @since 1.1
		 *
		 * @param int                                          $form_id                current Form ID.
		 * @param Forminator_Form_Entry_Model                  $entry_model            Forminator Entry Model.
		 * @param Forminator_Integration_Form_Settings|null $form_settings_instance of Integration Form Settings.
		 */
		do_action(
			'forminator_addon_' . $addon_slug . '_after_entry_saved',
			$form_id,
			$entry_model,
			$form_settings_instance
		);
	}

	/**
	 * Get Submit form error message
	 *
	 * @since 1.1
	 * @return string
	 */
	public function get_submit_error_message() {
		$addon_slug             = $this->addon->get_slug();
		$form_id                = $this->module_id;
		$form_settings_instance = $this->settings_instance;

		$error_message = $this->submit_error_message;
		/**
		 * Filter addon columns to be displayed on export submissions
		 *
		 * Although it can be used for all addon.
		 * Please keep in mind that if the addon override this method,
		 * then this filter probably won't be applied.
		 * To be sure please check individual addon documentations.
		 *
		 * @since 1.1
		 *
		 * @param array                                        $export_columns         column to be exported.
		 * @param int                                          $form_id                current Form ID.
		 * @param Forminator_Integration_Form_Settings|null $form_settings_instance of Integration Form Settings.
		 */
		$error_message = apply_filters(
			'forminator_addon_' . $addon_slug . '_submit_form_error_message',
			$error_message,
			$form_id,
			$form_settings_instance
		);

		return $error_message;
	}

	/**
	 * Find Meta value from entry fields
	 *
	 * @since 1.7
	 *
	 * @param string $element_id Element Id.
	 * @param array  $form_entry_fields Form entry fields.
	 *
	 * @return array
	 */
	public static function find_meta_value_from_entry_fields( $element_id, $form_entry_fields ) {
		$meta_value = array();

		foreach ( $form_entry_fields as $form_entry_field ) {
			if ( isset( $form_entry_field['name'] ) && $form_entry_field['name'] === $element_id ) {
				$meta_value = isset( $form_entry_field['value'] ) ? $form_entry_field['value'] : array();
			}
		}

		/**
		 * Filter meta value of element_id from form entry fields
		 *
		 * @since 1.7
		 *
		 * @param array  $meta_value
		 * @param string $element_id
		 * @param array  $form_entry_fields
		 *
		 * @return array
		 */
		$meta_value = apply_filters( 'forminator_addon_meta_value_entry_fields', $meta_value, $element_id, $form_entry_fields );

		return $meta_value;
	}

	/**
	 * Check if element_id is calculation type
	 *
	 * @since 1.7
	 *
	 * @param string $element_id Element Id.
	 *
	 * @return bool
	 */
	public static function element_is_calculation( $element_id ) {
		$is_calculation = stripos( $element_id, self::CALCULATION_ELEMENT_PREFIX ) !== false;

		/**
		 * Filter calculation flag of element
		 *
		 * @since 1.7
		 *
		 * @param bool   $is_calculation
		 * @param string $element_id
		 *
		 * @return bool
		 */
		$is_calculation = apply_filters( 'forminator_addon_element_is_calculation', $is_calculation, $element_id );

		return $is_calculation;
	}

	/**
	 * Find calculations fields from entry fields
	 *
	 * @since 1.7
	 *
	 * @param array $form_entry_fields Form entry fields.
	 *
	 * @return array
	 */
	public static function find_calculation_fields_meta_from_entry_fields( $form_entry_fields ) {
		$meta_value = array();

		foreach ( $form_entry_fields as $form_entry_field ) {
			if ( isset( $form_entry_field['name'] ) ) {
				$element_id = $form_entry_field['name'];
				if ( self::element_is_calculation( $form_entry_field['name'] ) ) {
					$meta_value[ $element_id ] = isset( $form_entry_field['value'] ) ? $form_entry_field['value'] : array();
				}
			}
		}

		/**
		 * Filter calculations fields meta value form form entry fields
		 *
		 * @since 1.7
		 *
		 * @param array $meta_value
		 * @param array $form_entry_fields
		 *
		 * @return array
		 */
		$meta_value = apply_filters( 'forminator_addon_calculation_fields_entry_fields', $meta_value, $form_entry_fields );

		return $meta_value;
	}

	/**
	 * Check if element_id is stripe type
	 *
	 * @since 1.7
	 *
	 * @param string $element_id Element Id.
	 *
	 * @return bool
	 */
	public static function element_is_stripe( $element_id ) {
		$is_stripe = stripos( $element_id, self::STRIPE_ELEMENT_PREFIX ) !== false;

		/**
		 * Filter stripe flag of element
		 *
		 * @since 1.7
		 *
		 * @param bool   $is_stripe
		 * @param string $element_id
		 *
		 * @return bool
		 */
		$is_stripe = apply_filters( 'forminator_addon_element_is_stripe', $is_stripe, $element_id );

		return $is_stripe;
	}

	/**
	 * Check if element_id is Datepicker
	 *
	 * @since 1.15.12
	 *
	 * @param string $element_id Element Id.
	 *
	 * @return bool
	 */
	public static function element_is_datepicker( $element_id ) {
		$is_datepicker = stripos( $element_id, 'date-' ) !== false;

		/**
		 * Filter date flag of element
		 *
		 * @since 1.15.12
		 *
		 * @param bool   $is_datepicker
		 * @param string $element_id
		 *
		 * @return bool
		 */
		$is_datepicker = apply_filters( 'forminator_addon_element_is_datepicker', $is_datepicker, $element_id );

		return $is_datepicker;
	}

	/**
	 * Check if element_id is Multi option
	 *
	 * @since 1.49
	 *
	 * @param string $element_id Element Id.
	 * @param int    $form_id Form Id.
	 *
	 * @return bool
	 */
	public static function element_is_multi_option( $element_id, $form_id ) {
		$is_multi_option = false;
		if ( strpos( $element_id, 'checkbox-' ) !== false ) {
			$is_multi_option = true;
		} elseif ( strpos( $element_id, 'select-' ) !== false ) {
			$field = Forminator_API::get_form_field( $form_id, $element_id );
			if ( 'multiselect' === $field['value_type'] ) {
				$is_multi_option = true;
			}
		}

		/**
		 * Filter Multi option flag of element
		 *
		 * @since 1.49
		 *
		 * @param bool   $is_multi_option Multi option flag.
		 * @param string $element_id Element Id.
		 * @param int    $form_id Form Id.
		 *
		 * @return bool
		 */
		$is_multi_option = apply_filters( 'forminator_addon_element_is_multi_option', $is_multi_option, $element_id, $form_id );

		return $is_multi_option;
	}

	/**
	 * Check if element_id is Signature
	 *
	 * @param string $element_id Field slug.
	 *
	 * @return bool
	 */
	public static function element_is_signature( $element_id ) {
		$is_signature = stripos( $element_id, 'signature-' ) !== false;

		/**
		 * Filter date flag of element
		 *
		 * @since 1.16.0
		 *
		 * @param bool   $is_signature
		 * @param string $element_id Field slug
		 *
		 * @return bool
		 */
		$is_signature = apply_filters( 'forminator_addon_element_is_signature', $is_signature, $element_id );

		return $is_signature;
	}

	/**
	 * Find stripe fields from entry fields
	 *
	 * @since 1.7
	 *
	 * @param array $form_entry_fields Form entry fields.
	 *
	 * @return array
	 */
	public static function find_stripe_fields_meta_from_entry_fields( $form_entry_fields ) {
		$meta_value = array();

		foreach ( $form_entry_fields as $form_entry_field ) {
			if ( isset( $form_entry_field['name'] ) ) {
				$element_id = $form_entry_field['name'];
				if ( self::element_is_stripe( $form_entry_field['name'] ) ) {
					$meta_value[ $element_id ] = isset( $form_entry_field['value'] ) ? $form_entry_field['value'] : array();
				}
			}
		}

		/**
		 * Filter stripe fields meta value form form entry fields
		 *
		 * @since 1.7
		 *
		 * @param array $meta_value
		 * @param array $form_entry_fields
		 *
		 * @return array
		 */
		$meta_value = apply_filters( 'forminator_addon_stripe_fields_entry_fields', $meta_value, $form_entry_fields );

		return $meta_value;
	}

	/**
	 * Check if element_id is upload
	 *
	 * @since 1.15.7
	 *
	 * @param string $element_id Element Id.
	 *
	 * @return bool
	 */
	public static function element_is_upload( $element_id ) {
		$is_upload = stripos( $element_id, 'upload' ) !== false;

		/**
		 * Filter upload flag of element
		 *
		 * @since 1.15.7
		 *
		 * @param bool   $is_upload
		 * @param string $element_id
		 *
		 * @return bool
		 */
		$is_upload = apply_filters( 'forminator_addon_element_is_upload', $is_upload, $element_id );

		return $is_upload;
	}

	/**
	 * Return field data value as string
	 *
	 * @since 1.15.7
	 *
	 * @param string $element_id Element Id.
	 * @param array  $element Element.
	 *
	 * @return bool
	 */
	public static function get_field_value( $element_id, $element ) {

		if ( is_array( $element ) ) {

			if ( self::element_is_upload( $element_id ) && isset( $element['file']['file_url'] ) ) {
				if ( is_array( $element['file']['file_url'] ) ) {
					$element_value = implode( ',', $element['file']['file_url'] );
				} else {
					$element_value = $element['file']['file_url'];
				}
			} else {
				$element_value = implode( ',', $element );
			}
		} else {
			$element_value = trim( $element );
		}

		/**
		 * Filter element value
		 *
		 * @since 1.15.7
		 *
		 * @param bool   $element_value
		 * @param string $element_id
		 *
		 * @return bool
		 */
		$element_value = apply_filters( 'forminator_addon_element_value', $element_value, $element_id );

		return $element_value;
	}

	/**
	 * Return date value as Unix timestamp in milliseconds
	 *
	 * @since 1.15.12
	 *
	 * @param string $element_id Element Id.
	 * @param mixed  $value Field value.
	 * @param int    $form_id Form Id.
	 *
	 * @return bool
	 */
	public static function get_date_in_ms( $element_id, $value, $form_id ) {
		$field             = Forminator_API::get_form_field( $form_id, $element_id );
		$normalized_format = new Forminator_Date();
		$normalized_format = $normalized_format->normalize_date_format( $field['date_format'] );
		$date              = date_create_from_format( $normalized_format, $value );
		$date->setTimezone( timezone_open( 'UTC' ) );
		$date->modify( 'midnight' );

		return $date->getTimestamp() * 1000;
	}

	/**
	 * Prepare field value for passing to addon
	 *
	 * @param string $element_id Field slug.
	 * @param mixed  $form_entry_fields Form entry fields.
	 * @param array  $submitted_data Submitted data.
	 * @return string
	 */
	public static function prepare_field_value_for_addon( $element_id, $form_entry_fields, $submitted_data ) {
		$element_value = null;
		if ( self::element_is_calculation( $element_id ) ) {
			$meta_value    = self::find_meta_value_from_entry_fields( $element_id, $form_entry_fields );
			$element_value = Forminator_Form_Entry_Model::meta_value_to_string( 'calculation', $meta_value );
		} elseif ( self::element_is_stripe( $element_id ) ) {
			$meta_value    = self::find_meta_value_from_entry_fields( $element_id, $form_entry_fields );
			$element_value = Forminator_Form_Entry_Model::meta_value_to_string( 'stripe', $meta_value );
		} elseif ( self::element_is_signature( $element_id ) ) {
			$meta_value    = self::find_meta_value_from_entry_fields( $element_id, $form_entry_fields );
			$element_value = Forminator_Form_Entry_Model::meta_value_to_string( 'signature', $meta_value );
		} elseif ( ! empty( $submitted_data[ $element_id ] ) ) {
			$field_type = Forminator_Core::get_field_type( $element_id );
			// Replace the `submission_id` with the form entry ID if it exists.
			if ( 'submission_id' === $submitted_data[ $element_id ] ) {
				$meta_value    = self::find_meta_value_from_entry_fields( $element_id, $form_entry_fields );
				$element_value = Forminator_Form_Entry_Model::meta_value_to_string( $field_type, $meta_value );
			} else {
				$element_value = Forminator_Form_Entry_Model::meta_value_to_string( $field_type, $submitted_data[ $element_id ] );
			}
		}

		return $element_value;
	}

	/**
	 * Prepare Stripe subscription id for passing to addon
	 *
	 * @param array $submitted_data Submitted data.
	 * @param array $form_entry_fields Form entry fields.
	 * @return array
	 */
	public static function prepare_stripe_subscription_id_for_addon( $submitted_data, $form_entry_fields ) {
		if ( ! empty( $submitted_data['paymentid'] ) && 'subscription' === $submitted_data['paymentid'] ) {
			$field_data = wp_list_pluck( $form_entry_fields, 'value', 'name' );
			foreach ( $field_data as $element_id => $value ) {
				if ( self::element_is_stripe( $element_id ) && ! empty( $value['subscription_id'] ) ) {
					$submitted_data['subscriptionid'] = $value['subscription_id'];
					break;
				}
			}
		}
		return $submitted_data;
	}
}
