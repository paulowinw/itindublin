<?php
/**
 * The Forminator_Address class.
 *
 * @package Forminator
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_Address
 *
 * @since 1.0
 */
class Forminator_Address extends Forminator_Field {

	/**
	 * Name
	 *
	 * @var string
	 */
	public $name = '';

	/**
	 * Slug
	 *
	 * @var string
	 */
	public $slug = 'address';

	/**
	 * Position
	 *
	 * @var int
	 */
	public $position = 4;

	/**
	 * Type
	 *
	 * @var string
	 */
	public $type = 'address';

	/**
	 * Options
	 *
	 * @var array
	 */
	public $options = array();

	/**
	 * Icon
	 *
	 * @var string
	 */
	public $icon = 'sui-icon-pin';

	/**
	 * Forminator_Address constructor.
	 *
	 * @since 1.0
	 */
	public function __construct() {
		parent::__construct();
		$this->name = esc_html__( 'Address', 'forminator' );

		// Set default required error messages.
		$street_required  = __( 'This field is required. Please enter the street address.', 'forminator' );
		$line_required    = __( 'This field is required. Please enter address line.', 'forminator' );
		$city_required    = __( 'This field is required. Please enter the city.', 'forminator' );
		$state_required   = __( 'This field is required. Please enter the state.', 'forminator' );
		$zip_required     = __( 'This field is required. Please enter the zip code.', 'forminator' );
		$country_required = __( 'This field is required. Please select the country.', 'forminator' );

		self::$default_required_messages[ 'street_address_' . $this->type ]  = $street_required;
		self::$default_required_messages[ 'address_line_' . $this->type ]    = $line_required;
		self::$default_required_messages[ 'address_city_' . $this->type ]    = $city_required;
		self::$default_required_messages[ 'address_state_' . $this->type ]   = $state_required;
		self::$default_required_messages[ 'address_zip_' . $this->type ]     = $zip_required;
		self::$default_required_messages[ 'address_country_' . $this->type ] = $country_required;
	}

	/**
	 * Field defaults
	 *
	 * @since 1.0
	 * @return array
	 */
	public function defaults() {
		return array(
			'street_address'        => 'true',
			'address_city'          => 'true',
			'address_state'         => 'true',
			'address_zip'           => 'true',
			'address_country'       => 'true',
			'address_line'          => 'true',
			'field_label'           => esc_html__( 'Address', 'forminator' ),
			'street_address_label'  => esc_html__( 'Street Address', 'forminator' ),
			'address_city_label'    => esc_html__( 'City', 'forminator' ),
			'address_state_label'   => esc_html__( 'State/Province', 'forminator' ),
			'address_zip_label'     => esc_html__( 'ZIP / Postal Code', 'forminator' ),
			'address_country_label' => esc_html__( 'Country', 'forminator' ),
			'address_line_label'    => esc_html__( 'Apartment, suite, etc', 'forminator' ),
		);
	}

	/**
	 * Autofill Setting
	 *
	 * @since 1.0.5
	 *
	 * @param array $settings Settings.
	 *
	 * @return array
	 */
	public function autofill_settings( $settings = array() ) {
		$street_address_providers = apply_filters( 'forminator_field_' . $this->slug . '_street_address_autofill', array(), $this->slug . '_street_address' );
		$address_line_providers   = apply_filters( 'forminator_field_' . $this->slug . '_address_line_autofill', array(), $this->slug . '_address_line' );
		$city_providers           = apply_filters( 'forminator_field_' . $this->slug . '_city_autofill', array(), $this->slug . '_city' );
		$state_providers          = apply_filters( 'forminator_field_' . $this->slug . '_state_autofill', array(), $this->slug . '_state' );
		$zip_providers            = apply_filters( 'forminator_field_' . $this->slug . '_zip_autofill', array(), $this->slug . '_zip' );

		$autofill_settings = array(
			'address-street_address' => array(
				'values' => forminator_build_autofill_providers( $street_address_providers ),
			),
			'address-address_line'   => array(
				'values' => forminator_build_autofill_providers( $address_line_providers ),
			),
			'address-city'           => array(
				'values' => forminator_build_autofill_providers( $city_providers ),
			),
			'address-state'          => array(
				'values' => forminator_build_autofill_providers( $state_providers ),
			),
			'address-zip'            => array(
				'values' => forminator_build_autofill_providers( $zip_providers ),
			),
		);

		return $autofill_settings;
	}

	/**
	 * Field front-end markup
	 *
	 * @since 1.0
	 *
	 * @param array                  $field Field.
	 * @param Forminator_Render_Form $views_obj Forminator_Render_Form object.
	 * @param string                 $draft_value Draft value.
	 *
	 * @return mixed
	 */
	public function markup( $field, $views_obj, $draft_value = null ) {
		$settings            = $views_obj->model->settings;
		$this->field         = $field;
		$this->form_settings = $settings;
		$draft_value         = isset( $draft_value['value'] ) ? $draft_value['value'] : '';

		$field['descr_position'] = self::get_description_position( $field, $settings );

		// Address.
		$html = $this->get_address( $field, 'street_address', $draft_value );

		// Second Address.
		$html .= $this->get_address( $field, 'address_line', $draft_value );

		// City & State fields.
		$html .= $this->get_city_state( $field, $draft_value );

		// ZIP & Country fields.
		$html .= $this->get_zip_country( $field, $draft_value );

		return apply_filters( 'forminator_field_address_markup', $html, $field );
	}

	/**
	 * Return address input markup
	 *
	 * @since 1.0
	 *
	 * @param array  $field Field.
	 * @param string $slug Field slug.
	 * @param string $draft_value Draft value.
	 *
	 * @return string
	 */
	public function get_address( $field, $slug, $draft_value = null ) {

		$html        = '';
		$cols        = 12;
		$id          = self::get_property( 'element_id', $field );
		$address_id  = self::get_subfield_id( $id, '-' . $slug );
		$required    = self::get_property( $slug . '_required', $field, false, 'bool' );
		$ariareq     = 'false';
		$enabled     = self::get_property( $slug, $field );
		$description = self::get_property( $slug . '_description', $field );

		$descr_position = self::get_property( 'descr_position', $field );

		if ( (bool) self::get_property( $slug . '_required', $field, false ) ) {
			$ariareq = 'true';
		}

		$address = array(
			'type'          => 'text',
			'name'          => $address_id,
			'placeholder'   => $this->sanitize_value( self::get_property( $slug . '_placeholder', $field ) ),
			'id'            => self::get_field_id( $address_id ),
			'class'         => 'forminator-input',
			'data-required' => $required,
			'aria-required' => $ariareq,
		);

		// Add autocomplete attribute for browser autofill.
		$browser_autofill = self::get_property( $slug . '_browser_autofill', $field, 'enabled' );
		if ( 'enabled' === $browser_autofill ) {
			if ( 'street_address' === $slug ) {
				$address['autocomplete'] = 'address-line1';
			} elseif ( 'address_line' === $slug ) {
				$address['autocomplete'] = 'address-line2';
			}
		} else {
			$address['autocomplete'] = 'off';
		}

		if ( empty( $draft_value ) ) {

			$address = $this->replace_from_prefill( $field, $address, $slug );

		} elseif ( isset( $draft_value[ $slug ] ) ) {

			$address['value'] = esc_attr( $draft_value[ $slug ] );
		}

		if ( $enabled ) {

			$html .= '<div class="forminator-row">';

				$html .= sprintf( '<div id="%s" class="forminator-col">', $address['name'] );

					$html .= '<div class="forminator-field">';

						$html .= self::create_input(
							$address,
							self::get_property( $slug . '_label', $field ),
							$description,
							$required,
							$descr_position,
						);

					$html .= '</div>';

				$html .= '</div>';

			$html .= '</div>';
		}

		return $html;
	}

	/**
	 * Return City and State fields markup
	 *
	 * @since 1.0
	 *
	 * @param array  $field Field.
	 * @param string $draft_value Draft value.
	 *
	 * @return string
	 */
	public function get_city_state( $field, $draft_value = null ) {
		$html           = '';
		$cols           = 12;
		$id             = self::get_property( 'element_id', $field );
		$city_id        = self::get_subfield_id( $id, '-city' );
		$state_id       = self::get_subfield_id( $id, '-state' );
		$city           = self::get_property( 'address_city', $field, false );
		$state          = self::get_property( 'address_state', $field, false );
		$city_desc      = self::get_property( 'address_city_description', $field );
		$state_desc     = self::get_property( 'address_state_description', $field );
		$city_required  = self::get_property( 'address_city_required', $field, false, 'bool' );
		$city_ariareq   = 'false';
		$state_required = self::get_property( 'address_state_required', $field, false, 'bool' );
		$state_ariareq  = 'false';
		$multirow       = 'false';
		$descr_position = self::get_property( 'descr_position', $field );

		if ( (bool) self::get_property( 'address_city_required', $field, false ) ) {
			$city_ariareq = 'true';
		}

		if ( (bool) self::get_property( 'address_state_required', $field, false ) ) {
			$state_ariareq = 'true';
		}

		// If both prefix & first name are enabled, change cols.
		if ( $city && $state ) {
			$cols     = 6;
			$multirow = 'true';
		}

		if ( $city || $state ) {

			$html .= sprintf( '<div class="forminator-row" data-multiple="%s">', $multirow );

			if ( $city ) {
				$browser_autofill = self::get_property( 'address_city_browser_autofill', $field, 'enabled' );
				$city_data        = array(
					'type'          => 'text',
					'name'          => $city_id,
					'placeholder'   => $this->sanitize_value( self::get_property( 'address_city_placeholder', $field ) ),
					'id'            => self::get_field_id( $city_id ),
					'class'         => 'forminator-input',
					'data-required' => $city_required,
					'aria-required' => $city_ariareq,
					'autocomplete'  => 'enabled' === $browser_autofill ? 'address-level2' : 'off',
				);

				if ( isset( $draft_value['city'] ) ) {

					$city_data['value'] = esc_attr( $draft_value['city'] );

				} else {

					$city_data = $this->replace_from_prefill( $field, $city_data, 'address_city' );

				}

				$html .= sprintf( '<div id="%s" class="forminator-col forminator-col-%s">', $city_data['name'], $cols );

					$html .= '<div class="forminator-field">';

						$html .= self::create_input(
							$city_data,
							self::get_property( 'address_city_label', $field ),
							$city_desc,
							$city_required,
							$descr_position,
						);

					$html .= '</div>';

				$html .= '</div>';

			}

			if ( $state ) {
				$browser_autofill = self::get_property( 'address_state_browser_autofill', $field, 'enabled' );
				$state_data       = array(
					'type'          => 'text',
					'name'          => $state_id,
					'placeholder'   => $this->sanitize_value( self::get_property( 'address_state_placeholder', $field ) ),
					'id'            => self::get_field_id( $state_id ),
					'class'         => 'forminator-input',
					'data-required' => $state_required,
					'aria-required' => $state_ariareq,
					'autocomplete'  => 'enabled' === $browser_autofill ? 'address-level1' : 'off',
				);

				if ( isset( $draft_value['state'] ) ) {

					$state_data['value'] = esc_attr( $draft_value['state'] );

				} else {

					$state_data = $this->replace_from_prefill( $field, $state_data, 'address_state' );

				}

				$html .= sprintf( '<div id="%s" class="forminator-col forminator-col-%s">', $state_data['name'], $cols );

					$html .= '<div class="forminator-field">';

						$html .= self::create_input(
							$state_data,
							self::get_property( 'address_state_label', $field ),
							$state_desc,
							$state_required,
							$descr_position,
						);

					$html .= '</div>';

				$html .= '</div>';

			}

			$html .= '</div>';

		}

		return $html;
	}

	/**
	 * Return Zip and County inputs
	 *
	 * @since 1.0
	 *
	 * @param array  $field Field.
	 * @param string $draft_value Draft value.
	 *
	 * @return string
	 */
	public function get_zip_country( $field, $draft_value = null ) {
		$html            = '';
		$cols            = 12;
		$id              = self::get_property( 'element_id', $field );
		$zip_id          = self::get_subfield_id( $id, '-zip' );
		$country_id      = self::get_subfield_id( $id, '-country' );
		$address_zip     = self::get_property( 'address_zip', $field, false );
		$address_country = self::get_property( 'address_country', $field, false );
		$zip_desc        = self::get_property( 'address_zip_description', $field );
		$country_desc    = self::get_property( 'address_country_description', $field );
		$descr_position  = self::get_property( 'descr_position', $field );

		$zip_required     = self::get_property( 'address_zip_required', $field, false, 'bool' );
		$country_required = self::get_property( 'address_country_required', $field, false, 'bool' );

		$zip_ariareq = 'false';

		if ( (bool) self::get_property( 'address_zip_required', $field, false ) ) {
			$zip_ariareq = 'true';
		}

		$multirow = 'false';

		// If both prefix & first name are enabled, change cols.
		if ( $address_zip && $address_country ) {
			$cols     = 6;
			$multirow = 'true';
		}

		if ( $address_zip || $address_country ) {

			$html .= sprintf( '<div class="forminator-row" data-multiple="%s">', $multirow );

			if ( $address_zip ) {
				$browser_autofill = self::get_property( 'address_zip_browser_autofill', $field, 'enabled' );
				$zip_data         = array(
					'type'         => 'text',
					'name'         => $zip_id,
					'placeholder'  => $this->sanitize_value( self::get_property( 'address_zip_placeholder', $field ) ),
					'id'           => self::get_field_id( $zip_id ),
					'class'        => 'forminator-input',
					'autocomplete' => 'enabled' === $browser_autofill ? 'postal-code' : 'off',
				);

				if ( isset( $draft_value['zip'] ) ) {

					$zip_data['value'] = esc_attr( $draft_value['zip'] );

				} else {

					$zip_data = $this->replace_from_prefill( $field, $zip_data, 'address_zip' );

				}

				$html .= sprintf( '<div id="%s" class="forminator-col forminator-col-%s">', $zip_data['name'], $cols );

					$html .= '<div class="forminator-field">';

						$html .= self::create_input(
							$zip_data,
							self::get_property( 'address_zip_label', $field ),
							$zip_desc,
							$zip_required,
							$descr_position,
						);

					$html .= '</div>';

				$html .= '</div>';

			}

			if ( $address_country ) {
				$browser_autofill = self::get_property( 'address_country_browser_autofill', $field, 'enabled' );
				$country_data     = array(
					'name'             => $country_id,
					'id'               => self::get_field_id( $this->form_settings['form_id'] . '__field--' . $country_id ),
					'class'            => 'forminator-select2',
					'data-search'      => 'true',
					'data-placeholder' => esc_html__( 'Select country', 'forminator' ),
					'autocomplete'     => 'enabled' === $browser_autofill ? 'country-name country' : 'off',
				);

				$countries = array(
					array(
						'value' => '',
						'label' => '',
					),
				);

				$options   = forminator_to_field_array( forminator_get_countries_list() );
				$countries = array_merge( $countries, $options );
				$country   = false;

				if ( isset( $draft_value['country'] ) ) {

					$country = esc_attr( $draft_value['country'] );

				} elseif ( $this->has_prefill( $field, 'address_country' ) ) {

					// We have pre-fill parameter, use its value or $value.
					$country = $this->get_prefill( $field, false, 'address_country' );

				}

				$new_countries = array();
				foreach ( $countries as $option ) {
					$selected = false;

					// Should use label here. Option values are 2-letter country codes.
					if ( strtolower( $option['label'] ) === strtolower( $country ) ) {
						$selected = true;
					}
					$new_countries[] = array(
						'value'    => $option['value'],
						'label'    => $option['label'],
						'selected' => $selected,
					);
				}

				/**
				 * Filter countries for <options> on <select> field
				 *
				 * @since 1.5.2
				 * @param array $countries
				 */
				$countries = apply_filters( 'forminator_countries_field', $new_countries );

				$html .= sprintf( '<div id="%s" class="forminator-col forminator-col-%s">', $country_data['name'], $cols );

					$html .= '<div class="forminator-field">';

						$html .= self::create_country_select(
							$country_data,
							self::get_property( 'address_country_label', $field ),
							$countries,
							self::get_property( 'address_country_placeholder', $field ),
							$country_desc,
							$country_required,
							$descr_position,
						);

					$html .= '</div>';

				$html .= '</div>';

			}

			$html .= '</div>';

		}

		return $html;
	}

	/**
	 * Return new select field
	 *
	 * @since 1.7.3
	 *
	 * @param array  $attr Attributes.
	 * @param string $label Text for label.
	 * @param array  $options Options.
	 * @param string $value Value.
	 * @param string $description Description content.
	 * @param bool   $required Is required.
	 * @param string $descr_position Description position.
	 *
	 * @return mixed
	 */
	public static function create_country_select( $attr = array(), $label = '', $options = array(), $value = '', $description = '', $required = false, $descr_position = 'above' ) {

		$html = '';

		if ( ! empty( $description ) ) {
			$attr['aria-describedby'] = $attr['id'] . '-description';
		}

		$markup = self::implode_attr( $attr );

		if ( isset( $attr['id'] ) ) {
			$get_id = $attr['id'];
		} else {
			$get_id = uniqid( 'forminator-select-' );
		}

		if ( self::get_post_data( $attr['name'], false ) ) {
			$value = self::get_post_data( $attr['name'] );
		}

		$html .= self::get_field_label( $label, $get_id, $required );

		if ( 'above' === $descr_position ) {
			$html .= self::get_description( $description, $get_id, $descr_position );
		}

		$markup .= ' data-default-value="' . esc_attr( $value ) . '"';

		$html .= sprintf( '<select %s>', $markup );

		foreach ( $options as $option ) {
			$selected = '';

			if ( ( $option['label'] == $value ) || ( isset( $option['selected'] ) && $option['selected'] ) ) { // phpcs:ignore -- loose comparison ok : possible compare '1' and 1.
				$selected = 'selected="selected"';
			}

			if ( 'Select country' === $option['label'] ) {
				$html .= sprintf( '<option value="" data-country-code="%s" %s>%s</option>', $option['value'], $selected, $option['label'] );
			} else {
				$html .= sprintf( '<option value="%s" data-country-code="%s" %s>%s</option>', $option['label'], $option['value'], $selected, $option['label'] );
			}
		}

		$html .= '</select>';

		if ( 'above' !== $descr_position ) {
			$html .= self::get_description( $description, $get_id, $descr_position );
		}

		return apply_filters( 'forminator_field_create_select', $html, $attr, $label, $options, $value, $description );
	}

	/**
	 * Return field inline validation rules
	 *
	 * @since 1.0
	 * @return string
	 */
	public function get_validation_rules() {

		$field = $this->field;
		$rules = '';

		$id      = self::get_property( 'element_id', $field );
		$street  = self::get_property( 'street_address', $field, false );
		$line    = self::get_property( 'address_line', $field, false );
		$city    = self::get_property( 'address_city', $field, false );
		$state   = self::get_property( 'address_state', $field, false );
		$zip     = self::get_property( 'address_zip', $field, false );
		$country = self::get_property( 'address_country', $field, false );

		$street_required  = self::get_property( 'street_address_required', $field, false, 'bool' );
		$line_required    = self::get_property( 'address_line_required', $field, false, 'bool' );
		$city_required    = self::get_property( 'address_city_required', $field, false, 'bool' );
		$state_required   = self::get_property( 'address_state_required', $field, false, 'bool' );
		$zip_required     = self::get_property( 'address_zip_required', $field, false, 'bool' );
		$country_required = self::get_property( 'address_country_required', $field, false, 'bool' );

		if ( $street ) {
			if ( $street_required ) {
				$rules .= '"' . $this->get_id( $field ) . '-street_address": "trim",';
				$rules .= '"' . $this->get_id( $field ) . '-street_address": "required",';
			}
		}
		if ( $line ) {
			if ( $line_required ) {
				$rules .= '"' . $this->get_id( $field ) . '-address_line": "trim",';
				$rules .= '"' . $this->get_id( $field ) . '-address_line": "required",';
			}
		}
		if ( $city ) {
			if ( $city_required ) {
				$rules .= '"' . $this->get_id( $field ) . '-city": "trim",';
				$rules .= '"' . $this->get_id( $field ) . '-city": "required",';
			}
		}
		if ( $state ) {
			if ( $state_required ) {
				$rules .= '"' . $this->get_id( $field ) . '-state": "trim",';
				$rules .= '"' . $this->get_id( $field ) . '-state": "required",';
			}
		}
		if ( $zip ) {
			if ( $zip_required ) {
				$rules .= '"' . $this->get_id( $field ) . '-zip": "trim",';
				$rules .= '"' . $this->get_id( $field ) . '-zip": "required",';
			}
		}
		if ( $country ) {
			if ( $country_required ) {
				$rules .= '"' . $this->get_id( $field ) . '-country": "trim",';
				$rules .= '"' . $this->get_id( $field ) . '-country": "required",';
			}
		}

		return apply_filters( 'forminator_field_address_validation_rules', $rules, $id, $field );
	}

	/**
	 * Return field inline validation errors
	 *
	 * @since 1.0
	 * @return string
	 */
	public function get_validation_messages() {
		$field    = $this->field;
		$id       = $this->get_id( $field );
		$messages = '';

		$street  = self::get_property( 'street_address', $field, false );
		$line    = self::get_property( 'address_line', $field, false );
		$city    = self::get_property( 'address_city', $field, false );
		$state   = self::get_property( 'address_state', $field, false );
		$zip     = self::get_property( 'address_zip', $field, false );
		$country = self::get_property( 'address_country', $field, false );

		$street_required  = self::get_property( 'street_address_required', $field, false, 'bool' );
		$line_required    = self::get_property( 'address_line_required', $field, false, 'bool' );
		$city_required    = self::get_property( 'address_city_required', $field, false, 'bool' );
		$state_required   = self::get_property( 'address_state_required', $field, false, 'bool' );
		$zip_required     = self::get_property( 'address_zip_required', $field, false, 'bool' );
		$country_required = self::get_property( 'address_country_required', $field, false, 'bool' );

		if ( $street && $street_required ) {
			$required_message = $this->get_field_multiple_required_message(
				$id,
				$field,
				'street_address_required_message',
				'street_address',
				self::$default_required_messages[ 'street_address_' . $this->type ]
			);
			$messages        .= '"' . $this->get_id( $field ) . '-street_address": "' . forminator_addcslashes( $required_message ) . '",' . "\n";
		}
		if ( $line && $line_required ) {
			$required_message = $this->get_field_multiple_required_message(
				$id,
				$field,
				'address_line_required_message',
				'address_line',
				self::$default_required_messages[ 'address_line_' . $this->type ]
			);
			$messages        .= '"' . $this->get_id( $field ) . '-address_line": "' . forminator_addcslashes( $required_message ) . '",' . "\n";
		}

		if ( $city && $city_required ) {
			$required_message = $this->get_field_multiple_required_message(
				$id,
				$field,
				'address_city_required_message',
				'address_city',
				self::$default_required_messages[ 'address_city_' . $this->type ]
			);
			$messages        .= '"' . $this->get_id( $field ) . '-city": "' . forminator_addcslashes( $required_message ) . '",' . "\n";
		}

		if ( $state && $state_required ) {
			$required_message = $this->get_field_multiple_required_message(
				$id,
				$field,
				'address_state_required_message',
				'address_state',
				self::$default_required_messages[ 'address_state_' . $this->type ]
			);
			$messages        .= '"' . $this->get_id( $field ) . '-state": "' . forminator_addcslashes( $required_message ) . '",' . "\n";
		}

		if ( $zip && $zip_required ) {
			$required_message = $this->get_field_multiple_required_message(
				$id,
				$field,
				'address_zip_required_message',
				'address_zip',
				self::$default_required_messages[ 'address_zip_' . $this->type ]
			);
			$messages        .= '"' . $this->get_id( $field ) . '-zip": "' . forminator_addcslashes( $required_message ) . '",' . "\n";
		}

		if ( $country && $country_required ) {
			$required_message = $this->get_field_multiple_required_message(
				$id,
				$field,
				'address_country_required_message',
				'address_country',
				self::$default_required_messages[ 'address_country_' . $this->type ]
			);
			$messages        .= '"' . $this->get_id( $field ) . '-country": "' . forminator_addcslashes( $required_message ) . '",' . "\n";
		}

		return $messages;
	}

	/**
	 * Field back-end validation
	 *
	 * @since 1.0
	 *
	 * @param array        $field Field.
	 * @param array|string $data Data.
	 */
	public function validate( $field, $data ) {
		$id = self::get_property( 'element_id', $field );

		$street  = self::get_property( 'street_address', $field, false );
		$line    = self::get_property( 'address_line', $field, false );
		$city    = self::get_property( 'address_city', $field, false );
		$state   = self::get_property( 'address_state', $field, false );
		$zip     = self::get_property( 'address_zip', $field, false );
		$country = self::get_property( 'address_country', $field, false );

		$street_required  = self::get_property( 'street_address_required', $field, false, 'bool' );
		$line_required    = self::get_property( 'address_line_required', $field, false, 'bool' );
		$city_required    = self::get_property( 'address_city_required', $field, false, 'bool' );
		$state_required   = self::get_property( 'address_state_required', $field, false, 'bool' );
		$zip_required     = self::get_property( 'address_zip_required', $field, false, 'bool' );
		$country_required = self::get_property( 'address_country_required', $field, false, 'bool' );

		$street_data  = isset( $data['street_address'] ) ? $data['street_address'] : '';
		$line_data    = isset( $data['address_line'] ) ? $data['address_line'] : '';
		$zip_data     = isset( $data['zip'] ) ? $data['zip'] : '';
		$country_data = isset( $data['country'] ) ? $data['country'] : '';
		$city_data    = isset( $data['city'] ) ? $data['city'] : '';
		$state_data   = isset( $data['state'] ) ? $data['state'] : '';

		if ( $street && $street_required && empty( $street_data ) ) {
			$this->validation_message[ $id . '-street_address' ] = $this->get_field_multiple_required_message(
				$id,
				$field,
				'street_address_required_message',
				'street_address',
				esc_html( self::$default_required_messages[ 'street_address_' . $this->type ] )
			);
		}
		if ( $line && $line_required && empty( $line_data ) ) {
			$this->validation_message[ $id . '-address_line' ] = $this->get_field_multiple_required_message(
				$id,
				$field,
				'address_line_required_message',
				'address_line',
				esc_html( self::$default_required_messages[ 'address_line_' . $this->type ] )
			);
		}

		if ( $city && $city_required && empty( $city_data ) ) {
			$this->validation_message[ $id . '-city' ] = $this->get_field_multiple_required_message(
				$id,
				$field,
				'address_city_required_message',
				'address_city',
				esc_html( self::$default_required_messages[ 'address_city_' . $this->type ] )
			);
		}

		if ( $state && $state_required && empty( $state_data ) ) {
			$this->validation_message[ $id . '-state' ] = $this->get_field_multiple_required_message(
				$id,
				$field,
				'address_state_required_message',
				'address_state',
				esc_html( self::$default_required_messages[ 'address_state_' . $this->type ] )
			);
		}

		if ( $zip && $zip_required && empty( $zip_data ) ) {
			$this->validation_message[ $id . '-zip' ] = $this->get_field_multiple_required_message(
				$id,
				$field,
				'address_zip_required_message',
				'address_zip',
				esc_html( self::$default_required_messages[ 'address_zip_' . $this->type ] )
			);
		}

		if ( $country && $country_required && empty( $country_data ) ) {
			$this->validation_message[ $id . '-country' ] = $this->get_field_multiple_required_message(
				$id,
				$field,
				'address_country_required_message',
				'address_country',
				esc_html( self::$default_required_messages[ 'address_country_' . $this->type ] )
			);
		}
	}

	/**
	 * Sanitize data
	 *
	 * @since 1.0.2
	 *
	 * @param array        $field Field.
	 * @param array|string $data - the data to be sanitized.
	 *
	 * @return array|string $data - the data after sanitization
	 */
	public function sanitize( $field, $data ) {
		$original_data = $data;
		// Sanitize.
		if ( is_array( $data ) ) {
			$data = forminator_sanitize_array_field( $data );
		} else {
			$data = forminator_sanitize_field( $data );
		}

		return apply_filters( 'forminator_field_address_sanitize', $data, $field, $original_data );
	}
}
