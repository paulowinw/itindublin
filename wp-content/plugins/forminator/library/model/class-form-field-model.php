<?php
/**
 * The Forminator_Form_Field_Model class.
 *
 * Author: Hoang Ngo
 *
 * @package Forminator
 */

/**
 * Forminator_Form_Field_Model
 *
 * Hold Field as model
 * Heads up! this class use __get and __set magic method, use with care
 *
 * @property string $wrapper_id
 */
class Forminator_Form_Field_Model {
	/**
	 * This should be unique
	 *
	 * @var string
	 */
	public $slug;

	/**
	 * This is parent form ID, optional
	 *
	 * @var int
	 */
	public $form_id;

	/**
	 * This is parent group, optional
	 *
	 * @var string
	 */
	public $parent_group = '';

	/**
	 * This contains all the parsed json data from frontend form
	 *
	 * @var array
	 */
	protected $raw = array();

	/**
	 * This contains all form settings for field migration
	 *
	 * @var array
	 */
	protected $form_settings = array();

	/**
	 * Forminator_Form_Field_Model constructor.
	 *
	 * @param null|array $settings Settings.
	 */
	public function __construct( $settings = null ) {
		if ( ! empty( $settings ) ) {
			$this->form_settings = $settings;
		}
	}

	/**
	 * Get
	 *
	 * @since 1.0
	 *
	 * @param string $name Field name.
	 *
	 * @return mixed|null
	 */
	public function __get( $name ) {
		if ( property_exists( $this, $name ) ) {
			return $this->$name;
		}

		$value = isset( $this->raw[ $name ] ) ? $this->raw[ $name ] : null;
		$value = apply_filters( 'forminator_get_field_' . $this->slug, $value, $this->form_id, $name );

		return $value;
	}

	/**
	 * Set
	 *
	 * @since 1.0
	 *
	 * @param string $name Field name.
	 * @param mixed  $value Field value.
	 */
	public function __set( $name, $value ) {
		if ( property_exists( $this, $name ) ) {
			$this->$name = $value;

			return;
		}
		$value              = apply_filters( 'forminator_set_field_' . $this->slug, $value, $this->form_id, $name );
		$this->raw[ $name ] = $value;
	}

	/**
	 * To JSON
	 *
	 * @since 1.0
	 * @return string
	 */
	public function to_json() {
		return wp_json_encode( $this->to_array() );
	}

	/**
	 * To array
	 *
	 * @since 1.0
	 * @return array
	 */
	public function to_array() {
		$data = array(
			'id'           => $this->slug,
			'element_id'   => $this->slug,
			'form_id'      => $this->form_id,
			'parent_group' => $this->parent_group,
		);

		return array_merge( $data, $this->raw );
	}

	/**
	 * Format to array
	 *
	 * @since 1.0
	 * @return array
	 */
	public function to_formatted_array() {
		$field_settings = $this->raw;

		$field_settings['parent_group'] = $this->parent_group;
		return Forminator_Migration::migrate_field( $field_settings, $this->form_settings );
	}

	/**
	 * Import
	 *
	 * @since 1.0
	 * @since 1.5 add `wrapper_id` on the attribute
	 *
	 * @param array $data Data.
	 */
	public function import( $data ) {
		if ( empty( $data ) ) {
			return;
		}

		foreach ( $data as $key => $val ) {
			$key        = sanitize_key( $key ); // Attempt ti sanitize key.
			$this->$key = $val;
		}

		// Add `wrapper_id` when necessary.
		if ( ! isset( $this->wrapper_id ) ) {
			$wrapper_id = '';
			if ( isset( $this->form_id ) && ! empty( $this->form_id ) && false !== stripos( $this->form_id, 'wrapper-' ) ) {
				$wrapper_id = $this->form_id;
			} elseif ( isset( $this->formID ) && ! empty( $this->formID )  // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
					&& false !== stripos( $this->formID, 'wrapper-' ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

				// Backward compat formID.
				$wrapper_id = $this->formID; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			}

			if ( ! empty( $wrapper_id ) ) {
				$this->wrapper_id = $wrapper_id;
			}
		}
	}

	/**
	 * Check if subfield is enabled or disabled
	 *
	 * @param string $subfield_name Subfield name.
	 * @return boolean
	 */
	public function is_subfield_enabled( $subfield_name ) {
		$subfield_slugs = array(
			'hours'          => 'element_id', // Always enabled.
			'minutes'        => 'element_id',
			'ampm'           => 'element_id',
			'year'           => 'element_id',
			'day'            => 'element_id',
			'month'          => 'element_id',
			'min'            => 'element_id',
			'max'            => 'element_id',
			'street_address' => 'street_address',
			'address_line'   => 'address_line',
			'city'           => 'address_city',
			'state'          => 'address_state',
			'zip'            => 'address_zip',
			'country'        => 'address_country',
			'prefix'         => 'prefix',
			'first-name'     => 'fname',
			'middle-name'    => 'mname',
			'last-name'      => 'lname',
		);

		$subfield_slug = isset( $subfield_slugs[ $subfield_name ] )
				? $subfield_slugs[ $subfield_name ] : $subfield_name;

		$is_enabled = ! empty( $this->raw[ $subfield_slug ] );

		/**
		 * Filter is subfield enabled or not
		 *
		 * @param boolean                     $is_enabled Filtered result.
		 * @param string                      $subfield_name Subfield name.
		 * @param Forminator_Form_Field_Model $object Field object.
		 */
		$is_enabled = apply_filters( 'forminator_is_subfield_enabled', $is_enabled, $subfield_name, $this );

		return $is_enabled;
	}

	/**
	 * Get Field Label For Entry
	 *
	 * @since 1.0.3
	 *
	 * @return string
	 */
	public function get_label_for_entry() {
		$field_type = $this->__get( 'type' );
		$label      = $this->__get( 'field_label' );

		if ( empty( $label ) ) {
			$label = $this->title;
		}

		if ( empty( $label ) ) {
			$label = ucfirst( $field_type );
		}

		return $label;
	}
}
