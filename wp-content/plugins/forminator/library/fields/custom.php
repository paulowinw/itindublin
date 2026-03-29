<?php
/**
 * The Forminator_Custom class.
 *
 * @package Forminator
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_Custom
 *
 * @since 1.0
 */
class Forminator_Custom extends Forminator_Field {

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
	public $slug = 'custom';

	/**
	 * Type
	 *
	 * @var string
	 */
	public $type = 'custom';

	/**
	 * Options
	 *
	 * @var array
	 */
	public $options = array();

	/**
	 * Category
	 *
	 * @var string
	 */
	public $category = '';

	/**
	 * Category
	 *
	 * @var string
	 */
	// public $category = 'posts';.
	// Disable for now until we know what to do with this.

	/**
	 * Forminator_Custom constructor.
	 *
	 * @since 1.0
	 */
	public function __construct() {
		parent::__construct();

		$this->name = esc_html__( 'Custom Field', 'forminator' );
	}

	/**
	 * Load settings
	 *
	 * @param array $settings Settings.
	 *
	 * @return array
	 */
	public function load_settings( $settings = array() ) {
		return array(
			array(
				'id'         => 'required',
				'type'       => 'Toggle',
				'name'       => 'required',
				'className'  => 'required-field',
				'hide_label' => true,
				'values'     => array(
					array(
						'value'      => 'true',
						'label'      => esc_html__( 'Required', 'forminator' ),
						'labelSmall' => 'true',
					),
				),
			),

			array(
				'id'         => 'separator-1',
				'type'       => 'Separator',
				'hide_label' => true,
			),

			array(
				'id'         => 'field-label',
				'type'       => 'Text',
				'name'       => 'field_label',
				'hide_label' => false,
				'label'      => esc_html__( 'Field Label', 'forminator' ),
				'className'  => 'text-field',
			),

			array(
				'id'           => 'field-type',
				'type'         => 'Select',
				'name'         => 'field_type',
				'className'    => 'select-field',
				'label_hidden' => false,
				'label'        => esc_html__( 'Field type', 'forminator' ),
				'values'       => array(
					array(
						'value' => 'text',
						'label' => esc_html__( 'Single line text', 'forminator' ),
					),
					array(
						'value' => 'textarea',
						'label' => esc_html__( 'Multi line text', 'forminator' ),
					),
					array(
						'value' => 'dropdown',
						'label' => esc_html__( 'Dropdown', 'forminator' ),
					),
					array(
						'value' => 'multiselect',
						'label' => esc_html__( 'Multi Select', 'forminator' ),
					),
					array(
						'value' => 'number',
						'label' => esc_html__( 'Number', 'forminator' ),
					),
					array(
						'value' => 'checkbox',
						'label' => esc_html__( 'Checkboxes', 'forminator' ),
					),
					array(
						'value' => 'radio',
						'label' => esc_html__( 'Radio Buttons', 'forminator' ),
					),
					array(
						'value' => 'hidden',
						'label' => esc_html__( 'Hidden', 'forminator' ),
					),
				),
			),

			array(
				'id'             => 'custom-field-name',
				'type'           => 'RadioContainer',
				'name'           => 'custom_field_name',
				'className'      => 'custom-field-name-field',
				'containerClass' => 'wpmudev-is_gray',
				'label'          => esc_html__( 'Custom field name', 'forminator' ),
				'values'         => array(
					array(
						'value' => 'existing',
						'label' => esc_html__( 'Existing field', 'forminator' ),
					),
					array(
						'value' => 'new',
						'label' => esc_html__( 'New field', 'forminator' ),
					),
				),
				'fields'         => array(
					array(
						'id'        => 'existing-field',
						'type'      => 'Select',
						'name'      => 'existing_field',
						'className' => 'existing-field',
						'label'     => esc_html__( 'Pick existing field', 'forminator' ),
						'tab'       => 'existing',
						'values'    => array(),
					),
				),
			),
		);
	}

	/**
	 * Field defaults
	 *
	 * @since 1.0
	 * @return array
	 */
	public function defaults() {
		return array(
			'value_type'  => 'select',
			'field_label' => '',
		);
	}

	/**
	 * Field front-end markup
	 *
	 * @since 1.0
	 * @param array                  $field Field.
	 * @param Forminator_Render_Form $views_obj Forminator_Render_Form object.
	 *
	 * @return mixed
	 */
	public function markup( $field, $views_obj ) {
		$required      = self::get_property( 'required', $field, false );
		$id            = self::get_property( 'element_id', $field );
		$name          = $id;
		$field_type    = self::get_property( 'field_type', $field );
		$placeholder   = esc_html( self::get_property( 'placeholder', $field ) );
		$description   = self::get_property( 'description', $field );
		$label         = esc_html( self::get_property( 'field_label', $field ) );
		$id            = $id . '-field';
		$html          = '';
		$default_value = esc_html( self::get_property( 'default_value', $field ) );
		$post_value    = self::get_post_data( $name, false );
		$settings      = $views_obj->model->settings;

		$descr_position = self::get_description_position( $field, $settings );

		switch ( $field_type ) {
			case 'text':
				$html .= sprintf(
					'<input class="forminator-name--field forminator-input" type="text" data-required="%s" name="%s" placeholder="%s" id="%s" %s/>',
					$required,
					$name,
					$placeholder,
					$id,
					( $post_value ? 'value= "' . $post_value . '"' : '' )
				);
				break;
			case 'textarea':
				$field_markup = array(
					'type'        => 'textarea',
					'class'       => 'forminator-textarea',
					'name'        => $name,
					'id'          => $id,
					'placeholder' => $placeholder,
					'required'    => $required,
				);
				$html        .= self::create_textarea( $field_markup, $label, $description, $required, $descr_position );
				break;
			case 'dropdown':
				break;
			case 'multiselect':
				break;
			case 'number':
				$html .= sprintf(
					'<input class="forminator-number--field forminator-input" type="number" data-required="%s" name="%s" placeholder="%s" value="%s" id="%s" />',
					$required,
					$name,
					$placeholder,
					( $post_value ? $post_value : $default_value ),
					$id
				);
				break;
			case 'checkbox':
				break;
			case 'radio':
				break;
			case 'hidden':
				$html .= sprintf( '<input class="forminator-hidden--field" type="hidden" id="%s" name="%s" value="%s" />', $id, $name, $default_value );
				break;
			default:
				break;
		}

		return apply_filters( 'forminator_field_custom_markup', $html, $id, $required, $field_type, $placeholder );
	}

	/**
	 * Field back-end validation
	 *
	 * @since 1.0
	 * @param array        $field Field.
	 * @param array|string $data Data.
	 */
	public function validate( $field, $data ) {
		if ( $this->is_required( $field ) ) {
			$id   = self::get_property( 'element_id', $field );
			$name = self::get_property( 'custom_field_name', $field, esc_html__( 'field name', 'forminator' ) );
			if ( empty( $data ) ) {
				/* translators: %s: Field name */
				$this->validation_message[ $id ] = sprintf( esc_html__( 'This field is required. Please enter the %s.', 'forminator' ), $name );
			}
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
		return $data;
	}
}
