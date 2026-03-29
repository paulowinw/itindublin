<?php
/**
 * The Forminator_Form_Model class.
 *
 * @package Forminator
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Author: Hoang Ngo
 */
class Forminator_Form_Model extends Forminator_Base_Form_Model {

	/**
	 * Module slug
	 *
	 * @var string
	 */
	public static $module_slug = 'form';

	/**
	 * Post type
	 *
	 * @var string
	 */
	protected $post_type = 'forminator_forms';

	/**
	 * Get field
	 *
	 * @since 1.0
	 *
	 * @param int  $id Id.
	 * @param bool $to_array Return in array.
	 *
	 * @return array|null|Forminator_Form_Field_Model
	 */
	public function get_field( $id, $to_array = true ) {
		foreach ( $this->get_fields() as $field ) {
			if ( $field->slug === $id ) {
				if ( $to_array ) {
					return $field->to_array();
				} else {
					return $field;
				}
			}
		}

		// probably it's repeated field.
		$explode = explode( '-', $id );
		$last    = array_pop( $explode );
		if ( 1 < count( $explode ) && is_numeric( $last ) ) {
			$original_id = implode( '-', $explode );
			return $this->get_field( $original_id, $to_array );
		}

		return null;
	}

	/**
	 * Return fields as array
	 *
	 * @since 1.5
	 *
	 * @param string $type Field type.
	 *
	 * @return array
	 */
	public function get_fields_by_type( $type ) {
		$fields = array();

		if ( empty( $this->fields ) ) {
			return $fields;
		}

		foreach ( $this->fields as $field ) {
			$field_settings = $field->to_array();

			if ( isset( $field_settings['type'] ) && $field_settings['type'] === $type ) {
				$fields[] = $field;
			}
		}

		return $fields;
	}

	/**
	 * Get wrapper
	 *
	 * @since 1.5
	 *
	 * @param string $id Wrapper Id.
	 *
	 * @return array|null
	 */
	public function get_wrapper( $id ) {
		$position = 0;
		foreach ( $this->get_fields_grouped() as $wrapper ) {
			if ( $wrapper['wrapper_id'] === $id ) {
				$wrapper['position'] = $position;

				return $wrapper;
			}

			++$position;
		}

		return null;
	}

	/**
	 * Delete form field by ID
	 *
	 * @since 1.5
	 *
	 * @param int $id Field Id.
	 *
	 * @return string|false
	 */
	public function delete_field( $id ) {
		$counter = 0;
		foreach ( $this->fields as $field ) {
			if ( $field->slug === $id ) {
				unset( $this->fields[ $counter ] );

				return $field->form_id;
			}

			++$counter;
		}

		return false;
	}

	/**
	 * Update fields cols in specific wrapper
	 *
	 * @since 1.5
	 *
	 * @param string $wrapper_id Wrapper Id.
	 *
	 * @return bool
	 */
	public function update_fields_by_wrapper( $wrapper_id ) {
		// Get wrapper.
		$wrapper = $this->get_wrapper( $wrapper_id );

		// Check if any fields in the wrapper.
		if ( ! isset( $wrapper['fields'] ) ) {
			return false;
		}

		// Get total fields in the wrapper.
		$total = count( $wrapper['fields'] );

		if ( $total > 0 ) {
			$cols = 12 / $total;

			// Update fields.
			foreach ( $wrapper['fields'] as $field ) {
				$field_object = $this->get_field( $field['element_id'], false );

				// Update field object.
				$field_object->import(
					array(
						'cols' => $cols,
					)
				);
			}

			return true;
		}

		return false;
	}

	/**
	 * Prepare data for preview
	 *
	 * @param object $form_model Model.
	 * @param array  $data Passed data.
	 *
	 * @return object
	 */
	public static function prepare_data_for_preview( $form_model, $data ) {
		// build the field.
		$fields = array();
		if ( isset( $data['wrappers'] ) ) {
			$fields = $data['wrappers'];
			unset( $data['wrappers'] );
		}

		if ( ! empty( $fields ) ) {
			foreach ( $fields as $row ) {
				foreach ( $row['fields'] as $f ) {
					$field          = new Forminator_Form_Field_Model();
					$field->form_id = $row['wrapper_id'];
					$field->slug    = $f['element_id'];

					$field->parent_group = ! empty( $row['parent_group'] ) ? $row['parent_group'] : '';
					$field->import( $f );
					$form_model->add_field( $field );
				}
			}
		}

		return $form_model;
	}

	/**
	 * Return Form Settings
	 *
	 * @since 1.1
	 *
	 * @return mixed
	 */
	public function get_form_settings() {
		// If not using the new "submission-behaviour" setting, set it according to the previous settings.
		if ( ! isset( $this->settings['submission-behaviour'] ) ) {
			$redirect = ( isset( $this->settings['redirect'] ) && filter_var( $this->settings['redirect'], FILTER_VALIDATE_BOOLEAN ) );

			if ( $redirect ) {
				$this->settings['submission-behaviour'] = 'behaviour-redirect';
			} else {
				$this->settings['submission-behaviour'] = 'behaviour-thankyou';
			}
		}

		if ( $this->has_stripe_or_paypal() && $this->is_ajax_submit() ) {
			if ( isset( $this->settings['submission-behaviour'] ) && 'behaviour-thankyou' === $this->settings['submission-behaviour'] ) {
				$this->settings['submission-behaviour'] = 'behaviour-hide';
			}
		}

		$this->settings = apply_filters( 'forminator_form_settings', $this->settings, $this );

		return $this->settings;
	}

	/**
	 * Return behaviors options.
	 *
	 * @return array
	 */
	public function get_behavior_array() {
		if ( empty( $this->behaviors ) ) {
			$settings = $this->get_form_settings();
			// Backward compatibility | Default.
			return array(
				array(
					'slug'                    => 'behavior-1234-4567',
					'label'                   => '',
					'autoclose-time'          => isset( $settings['autoclose-time'] ) ? $settings['autoclose-time'] : 5,
					'autoclose'               => isset( $settings['autoclose'] ) ? $settings['autoclose'] : true,
					'newtab'                  => isset( $settings['newtab'] ) ? $settings['newtab'] : 'sametab',
					'thankyou-message'        => isset( $settings['thankyou-message'] ) ? $settings['thankyou-message'] : '',
					'email-thankyou-message'  => isset( $settings['email-thankyou-message'] ) ? $settings['email-thankyou-message'] : '',
					'manual-thankyou-message' => isset( $settings['manual-thankyou-message'] ) ? $settings['manual-thankyou-message'] : '',
					'submission-behaviour'    => isset( $settings['submission-behaviour'] ) ? $settings['submission-behaviour'] : 'behaviour-thankyou',
					'redirect-url'            => isset( $settings['redirect-url'] ) ? $settings['redirect-url'] : '',
				),
			);
		}

		return $this->behaviors;
	}

	/**
	 * Check if submit is handled with AJAX
	 *
	 * @since 1.9
	 *
	 * @return bool
	 */
	public function is_ajax_submit() {
		$form_settings = $this->settings;

		// Force AJAX submit if form contains Stripe payment field.
		if ( $this->has_stripe_field() ) {
			return true;
		}

		if ( empty( $form_settings['enable-ajax'] ) ) {
			return false;
		}

		return filter_var( $form_settings['enable-ajax'], FILTER_VALIDATE_BOOLEAN );
	}

	/**
	 * Get field
	 *
	 * Call this method when you need get field and migrate it as well
	 *
	 * @since 1.7
	 *
	 * @param string $id Field Id.
	 *
	 * @return array|null
	 */
	public function get_formatted_array_field( $id ) {
		foreach ( $this->get_fields() as $field ) {
			if ( $field->slug === $id ) {
				return $field->to_formatted_array();
			}
		}

		return null;
	}

	/**
	 * Disable payments fields if payments are disabled
	 *
	 * @param array $fields Fields.
	 * @return array
	 */
	protected static function disable_fields( $fields ) {
		$disabled_fields = apply_filters( 'forminator_disabled_fields', array() );

		if ( forminator_payments_disabled() ) {
			$disabled_fields = array_merge( $disabled_fields, array( 'stripe', 'stripe-ocs', 'paypal' ) );
		}

		if ( $disabled_fields && $fields ) {
			$fields = array_filter(
				$fields,
				function ( $field ) use ( $disabled_fields ) {
					return empty( $field['type'] ) || ! in_array( $field['type'], $disabled_fields, true );
				}
			);
		}

		return $fields;
	}

	/**
	 * Flag whether ssl required when payment exists
	 *
	 * @since 1.7
	 *
	 * @return bool
	 */
	public function is_payment_require_ssl() {
		$form_id        = (int) $this->id;
		$form_settings  = $this->settings;
		$global_enabled = defined( 'FORMINATOR_PAYMENT_REQUIRE_SSL' ) && FORMINATOR_PAYMENT_REQUIRE_SSL;

		$enabled = isset( $form_settings['payment_require_ssl'] ) ? $form_settings['payment_require_ssl'] : false;
		$enabled = filter_var( $enabled, FILTER_VALIDATE_BOOLEAN );

		$enabled = $global_enabled || $enabled;

		/**
		 * Filter is ajax load for Custom Form
		 *
		 * @since 1.6.1
		 *
		 * @param bool  $enabled
		 * @param bool  $global_enabled
		 * @param int   $form_id
		 * @param array $form_settings
		 *
		 * @return bool
		 */
		$enabled = apply_filters( 'forminator_custom_form_is_payment_require_ssl', $enabled, $global_enabled, $form_id, $form_settings );

		return $enabled;
	}

	/**
	 * Check if Custom form has stripe field
	 *
	 * @since 1.7
	 * @return object|false
	 */
	public function has_stripe_field() {
		$fields = $this->get_real_fields();
		foreach ( $fields as $field ) {
			$field_array = $field->to_formatted_array();
			if ( isset( $field_array['type'] ) && ( 'stripe' === $field_array['type'] || 'stripe-ocs' === $field_array['type'] ) ) {
				return $field;
			}
		}

		return false;
	}

	/**
	 * Check if Custom form has paypal field
	 *
	 * @since 1.41
	 * @return object|false
	 */
	public function has_paypal_field() {
		$fields = $this->get_real_fields();
		foreach ( $fields as $field ) {
			$field_array = $field->to_formatted_array();
			if ( isset( $field_array['type'] ) && 'paypal' === $field_array['type'] ) {
				return $field;
			}
		}

		return false;
	}

	/**
	 * Check if form has stripe or paypal field
	 *
	 * @since 1.9.3
	 * @return bool
	 */
	public function has_stripe_or_paypal() {
		$fields = $this->get_real_fields();
		foreach ( $fields as $field ) {
			$field = $field->to_formatted_array();
			if ( isset( $field['type'] ) && in_array( $field['type'], array( 'paypal', 'stripe', 'stripe-ocs' ), true ) ) {
				return true;
			}
		}

		return false;
	}
}
