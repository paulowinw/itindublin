<?php
/**
 * The Forminator Field.
 *
 * @package Forminator
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_Field
 *
 * @since 1.0
 * Abstract class for fields
 *
 * @since 1.0.5
 * @property array      form_settings
 * @property array      field
 * @property mixed|void autofill_settings
 * @property mixed|void advanced_settings
 * @property mixed|void markup
 */
abstract class Forminator_Field {

	/**
	 * The field
	 *
	 * @var array
	 */
	public $field = array();

	/**
	 * The Form settings
	 *
	 * @var array
	 */
	public $form_settings = array();

	/**
	 * The name
	 *
	 * @var string
	 */
	public $name = '';

	/**
	 * The slug
	 *
	 * @var string
	 */
	public $slug = '';

	/**
	 * The category
	 *
	 * @var string
	 */
	public $category = 'standard';

	/**
	 * Type
	 *
	 * @var string
	 */
	public $type = '';

	/**
	 * Options
	 *
	 * @var array
	 */
	public $options = array();

	/**
	 * The settings
	 *
	 * @var array
	 */
	public $settings = array();

	/**
	 * The autofill settings
	 *
	 * @var array
	 */
	public $autofill_settings = array();

	/**
	 * The defaults
	 *
	 * @var array
	 */
	public $defaults = array();

	/**
	 * The hide advanced
	 *
	 * @var bool
	 */
	public $hide_advanced = false;

	/**
	 * The position
	 *
	 * @var int
	 */
	public $position = 99;

	/**
	 * Is inout
	 *
	 * @var bool
	 */
	public $is_input = false;

	/**
	 * Has counter
	 *
	 * @var bool
	 */
	public $has_counter = false;

	/**
	 * Check if the input data for field is valid
	 *
	 * @var bool
	 */
	public $is_valid = true;

	/**
	 * Validation message
	 *
	 * @var array
	 */
	public $validation_message = array();

	/**
	 * Description position
	 *
	 * @var string
	 */
	public static $description_position = '';

	/**
	 * Activated Autofill Providers for this field based @see autofill_settings
	 *
	 * @since 1.0.5
	 * @var Forminator_Autofill_Provider_Abstract[]
	 */
	protected $activated_autofill_providers = array();

	/**
	 * Flag property value not exist
	 *
	 * Support backward compat, for non existent property from older forminator version
	 *
	 * @since 1.6
	 */
	const FIELD_PROPERTY_VALUE_NOT_EXIST = 'FORMINATOR_PROPERTY_VALUE_NOT_EXIST';

	/**
	 * The icon
	 *
	 * @var string
	 */
	public $icon = 'sui-icon-element-radio';

	/**
	 * Is calculable
	 *
	 * @var bool
	 */
	public $is_calculable = false;

	const FIELD_NOT_CALCULABLE = 'FIELD_NOT_CALCULABLE';

	/**
	 * Default error messages for required fields.
	 *
	 * @var array
	 */
	public static $default_required_messages = array();

	/**
	 * The Constructor
	 */
	public function __construct() {

		add_action( 'admin_init', array( &$this, 'admin_init_field' ) );
	}

	/**
	 * Admin init field
	 *
	 * @since 1.7
	 */
	public function admin_init_field() {

		$this->settings          = apply_filters( "forminator_field_{$this->slug}_general_settings", array() );
		$this->autofill_settings = apply_filters( "forminator_field_{$this->slug}_autofill_settings", $this->autofill_settings() );
		$this->defaults          = apply_filters( "forminator_field_{$this->slug}_defaults", $this->defaults() );
		$this->position          = apply_filters( "forminator_field_{$this->slug}_position", $this->position );
		$this->is_calculable     = apply_filters( "forminator_field_{$this->slug}_is_calculable", $this->is_calculable );
	}

	/**
	 * Return field name
	 *
	 * @since 1.0
	 * @return string
	 */
	public function get_name() {
		return $this->name;
	}

	/**
	 * Return field slug
	 *
	 * @since 1.0
	 * @return string
	 */
	public function get_slug() {
		return $this->slug;
	}

	/**
	 * Get Category
	 *
	 * @return string
	 */
	public function get_category() {
		return $this->category;
	}

	/**
	 * Return field settings
	 *
	 * @since 1.0
	 * @return array
	 */
	public function get_settings() {
		return $this->settings;
	}

	/**
	 * Create decimals pattern from decimals number
	 *
	 * @since 1.7
	 * @param integer $decimals Decimals.
	 * @return mixed
	 */
	protected static function create_step_string( $decimals = 2 ) {
		$step = 1;

		if ( ! empty( $decimals ) ) {
			for ( $i = 1; $i < $decimals; $i++ ) {
				$step = '0' . $step;
			}

			$step = '0.' . $step;
		}

		return $step;
	}

	/**
	 * Return field property
	 *
	 * @since 1.0
	 * @since 1.6 add $data_type, to cast it
	 *
	 * @param string $property Property.
	 * @param array  $field Field.
	 * @param string $fallback Fallback.
	 * @param string $data_type data type to return.
	 *
	 * @return mixed
	 */
	public static function get_property( $property, $field, $fallback = '', $data_type = null ) {

		$property_value = $fallback;

		if ( isset( $field[ $property ] ) ) {
			$property_value = $field[ $property ];
		}

		if ( ! empty( $data_type ) ) {
			$property_value = forminator_var_type_cast( $property_value, $data_type );
		}

		if ( 'required_message' === $property ) {
			$property_value = wp_kses_post( $property_value );
		}

		return $property_value;
	}

	/**
	 * Get options for radio and selectbox fields
	 *
	 * @param array $field Field settings.
	 * @return array
	 */
	public static function get_options( $field ) {
		$options = self::get_property( 'options', $field, array() );

		if ( ! empty( $field['options_order'] ) && 'random' === $field['options_order'] ) {
			shuffle( $options );
			if ( ! empty( $field['enable_custom_option'] ) ) {
				// Move custom option to the end of the array.
				$key = array_search( 'custom_option', array_column( $options, 'key' ), true );
				if ( false !== $key ) {
					$custom_option = $options[ $key ];
					unset( $options[ $key ] );
					$options[] = $custom_option;
					$options   = array_values( $options );
				}
			}
		}

		return $options;
	}

	/**
	 * Get description position
	 *
	 * @param array $field Field settings.
	 * @param array $settings Form Settings.
	 *
	 * @return string
	 */
	public static function get_description_position( array $field, array $settings ): string {
		$possible_values = array( 'above', 'below' );
		$field_pos       = self::get_property( 'description-position', $field );
		// Check field description position.
		if ( in_array( $field_pos, $possible_values, true ) ) {
			return $field_pos;
		}
		if ( ! empty( $field['type'] ) && 'group' === $field['type'] && empty( $field_pos ) ) {
			return 'above';
		}

		// Check form description position.
		if ( ! empty( $settings['description-position'] ) && in_array( $settings['description-position'], $possible_values, true ) ) {
			return $settings['description-position'];
		}

		// For backward compatibility.
		return 'below';
	}

	/**
	 * Markup
	 *
	 * @since 1.0
	 *
	 * @param mixed                  $field Field.
	 * @param Forminator_Render_Form $views_obj Forminator_Render_Form object.
	 *
	 * @return mixed
	 */
	public function markup( // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		// @noinspection PhpUnusedParameterInspection.
		$field,
		$views_obj
	) {
		return '';
	}

	/**
	 * Defaults
	 *
	 * @since 1.0
	 * @return array
	 */
	public function defaults() {
		return array();
	}

	/**
	 * Return description
	 *
	 * @since 1.0
	 *
	 * @param string $description Description.
	 * @param string $get_id Id.
	 * @param string $descr_position Description position.
	 *
	 * @return string
	 */
	public static function get_description( $description, $get_id = '', $descr_position = 'above' ) {
		$html = '';
		if ( ! empty( $description ) ) {
			$html .= sprintf(
				'<span id="%s" class="forminator-description">%s</span>',
				esc_attr( $get_id . '-description' ),
				self::convert_markdown( self::esc_description( $description, $get_id ) )
			);
		}

		return apply_filters(
			'forminator_field_description',
			$html,
			$description,
			$get_id,
			$descr_position
		);
	}

	/**
	 * Escape description
	 *
	 * @param string $description Description.
	 * @param string $field_id      Field ID.
	 *
	 * @return string
	 */
	public static function esc_description( $description, $field_id ) {
		$allowed_html = apply_filters(
			'forminator_field_description_allowed_html',
			array(
				'a'      => array(
					'href'   => true,
					'title'  => true,
					'target' => true,
					'rel'    => true,
				),
				'span'   => array(
					'class' => true,
				),
				'br'     => array(),
				'em'     => array(),
				'strong' => array(),
			),
			$description,
			$field_id
		);

		return wp_kses( $description, $allowed_html );
	}

	/**
	 * Return new input field
	 *
	 * @since 1.0
	 *
	 * @param array  $attr Attribute.
	 *
	 * @param string $label Label.
	 * @param string $description Description.
	 * @param bool   $required Required.
	 * @param string $descr_position Description position.
	 * @param array  $wrapper_input Wrapper Input.
	 *
	 * @return mixed
	 */
	public static function create_input( $attr = array(), $label = '', $description = '', $required = false, $descr_position = 'above', $wrapper_input = array() ) {

		$html = '';

		// Override value by the posted value.
		$value = isset( $attr['value'] ) ? $attr['value'] : false;

		if ( isset( $attr['name'] ) ) {
			$value = self::get_post_data( $attr['name'], $value );
		}

		$attr['value'] = $value;

		if ( ! empty( $description ) ) {
			$attr['aria-describedby'] = $attr['id'] . '-description';
		}

		$markup = self::implode_attr( $attr );

		// Get field id.
		$get_id = $attr['id'];

		$html .= self::get_field_label( $label, $get_id, $required );

		if ( 'above' === $descr_position ) {
			$html .= self::get_description( $description, $get_id, $descr_position );
		}

		if ( isset( $wrapper_input[0] ) ) {
			$html .= $wrapper_input[0];
		}

		if ( ! empty( $wrapper_input[2] ) ) {
			$html .= sprintf( '<span class="forminator-icon-%s" aria-hidden="true"></span>', $wrapper_input[2] );
		}

		if ( ! empty( $wrapper_input[3] ) ) {
			$html .= sprintf( '<span class="forminator-prefix">%s</span>', $wrapper_input[3] );
		}

			$html .= sprintf( '<input %s />', $markup );

		if ( isset( $wrapper_input[1] ) ) {
			$html .= $wrapper_input[1];
		}

		if ( 'above' !== $descr_position ) {
			$html .= self::get_description( $description, $get_id, $descr_position );
		}

		return apply_filters( 'forminator_field_create_input', $html, $attr, $label, $description );
	}


	/**
	 * Return new textarea field
	 *
	 * @since 1.0
	 *
	 * @param array  $attr Field Attribute.
	 * @param string $label Field Label.
	 * @param string $description Field Description.
	 * @param bool   $required Required.
	 * @param string $description_position Description Position.
	 *
	 * @return mixed
	 */
	public static function create_textarea( $attr = array(), $label = '', $description = '', $required = false, $description_position = 'above' ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

		$html    = '';
		$content = isset( $attr['content'] ) ? $attr['content'] : '';

		if ( isset( $attr['name'] ) ) {
			$content = self::get_post_data( $attr['name'], $content );
		}

		unset( $attr['content'] );

		if ( ! empty( $description ) ) {
			$attr['aria-describedby'] = $attr['id'] . '-description';
		}

		$markup = self::implode_attr( $attr );

		$html .= self::get_field_label( $label, $attr['id'], $required );

		if ( 'above' === $description_position ) {
			$html .= self::get_description( $description, $attr['id'], $description_position );
		}

		$html .= sprintf( '<textarea %s >%s</textarea>', $markup, wp_kses_post( $content ) );

		if ( 'above' !== $description_position ) {
			$html .= self::get_description( $description, $attr['id'], $description_position );
		}

		return apply_filters( 'forminator_field_create_textarea', $html, $attr, $label, $description );
	}

	/**
	 * Return wp_editor_field
	 *
	 * @since 1.0.2
	 *
	 * @param array   $attr Field Attribute.
	 * @param string  $label Field Label.
	 * @param string  $description Field Description.
	 * @param bool    $required Required.
	 * @param string  $default_height Height.
	 * @param integer $limit Limit.
	 *
	 * @return mixed
	 */
	public static function create_wp_editor( $attr = array(), $label = '', $description = '', $required = false, $default_height = '140', $limit = 0 ) {
		$html = '';

		$content = isset( $attr['content'] ) ? $attr['content'] : '';
		if ( isset( $attr['name'] ) ) {
			$content = self::get_post_data( $attr['name'], $content );
		}
		unset( $attr['content'] );

		$editor_id = 'forminator-wp-editor-' . ( isset( $attr['id'] ) ? $attr['id'] : '' );
		if ( $label ) {
			$html .= '<div class="forminator-field--label">';
			$html .= sprintf(
				'<label for="%s" id="forminator-label-%s" class="forminator-label">%s</label>',
				$editor_id,
				$attr['id'],
				self::convert_markdown( esc_html( $label ) ) . ( $required ? ' ' . forminator_get_required_icon() : '' )
			);
			$html .= '</div>';
		}

		if ( 'above' === self::$description_position ) {
			$html .= self::get_description( $description, '', self::$description_position );
		}

		$wp_editor_class = isset( $attr['class'] ) ? $attr['class'] : '';

		if ( $required ) {
			apply_filters( 'the_editor', array( __CLASS__, 'add_required_wp_editor' ) );
			$wp_editor_class .= ' do-validate forminator-wp-editor-required';
		} elseif ( ! empty( $limit ) ) {
			$wp_editor_class .= ' do-validate';
		}

		ob_start();
		wp_editor(
			$content,
			$editor_id,
			array(
				'textarea_name' => isset( $attr['name'] ) ? $attr['name'] : '',
				'media_buttons' => false,
				'editor_class'  => $wp_editor_class,
				'editor_height' => $default_height,
			)
		);

		$html .= ob_get_clean();

		if ( 'above' !== self::$description_position ) {
			$html .= self::get_description( $description, '', self::$description_position );
		}

		return apply_filters( 'forminator_field_create_wp_editor', $html, $attr, $label, $description );
	}

	/**
	 * Add Required attribute to wp_editor
	 *
	 * @since 1.0.2
	 *
	 * @param string $editor_markup Editor markup.
	 *
	 * @return mixed
	 */
	public static function add_required_wp_editor( $editor_markup ) {
		if ( stripos( $editor_markup, 'forminator-wp-editor-required' ) !== false ) {
			// mark required.
			$editor_markup = str_replace( '<textarea', '<textarea required="true"', $editor_markup );
		}

		return $editor_markup;
	}

	/**
	 * Maybe add label if it's not empty
	 *
	 * @param string $label Field label.
	 * @param string $id Field id.
	 * @param bool   $required Is field required or not.
	 * @return string
	 */
	protected static function get_field_label( $label, $id, $required ) {
		$html = '';
		if ( $label ) {
			$html .= sprintf(
				'<label for="%s" id="%s" class="forminator-label">%s</label>',
				esc_attr( $id ),
				esc_attr( $id . '-label' ),
				self::convert_markdown( esc_html( $label ) ) . ( $required ? ' ' . forminator_get_required_icon() : '' )
			);
		}
		return $html;
	}

	/**
	 * Convert markdown to HTML.
	 *
	 * @param string $original_string Original string.
	 *
	 * @return string
	 */
	public static function convert_markdown( string $original_string ): string {
		$string = $original_string;

		// Apply markdown replacements.
		$patterns = array(
			'/(?<=^|\s)`(\S(?:.*?\S)?)`(?=\s|$)/u'   => '<span class="forminator-monospace" style="font-family: monospace;">$1</span>',
			'/(?<=^|\s)~(\S(?:.*?\S)?)~(?=\s|$)/u'   => '<del>$1</del>',
			'/(?<=^|\s)_(\S(?:.*?\S)?)_(?=\s|$)/u'   => '<em>$1</em>',
			'/(?<=^|\s)\*(\S(?:.*?\S)?)\*(?=\s|$)/u' => '<strong>$1</strong>',
		);
		foreach ( $patterns as $pattern => $replacement ) {
			$string = preg_replace( $pattern, $replacement, $string );
		}

		/**
		 * Filter for custom markdown.
		 */
		return apply_filters( 'forminator_markdown_result', $string, $original_string );
	}

	/**
	 * Get full field id
	 *
	 * @param string $element_id Field slug.
	 * @return string
	 */
	protected static function get_field_id( $element_id ) {
		return 'forminator-field-' . $element_id . '_' . Forminator_CForm_Front::$uid;
	}

	/**
	 * Return new select field
	 *
	 * @since 1.0
	 *
	 * @param array  $attr Field attribute.
	 * @param string $label Field label.
	 * @param array  $options Field option.
	 * @param string $value Field value.
	 * @param string $description Field description.
	 * @param bool   $required Required.
	 * @param string $descr_position Description position.
	 *
	 * @return mixed
	 */
	public static function create_select( $attr = array(), $label = '', $options = array(), $value = '', $description = '', $required = false, $descr_position = 'above' ) {

		$html = '';

		if ( ! empty( $description ) ) {
			$attr['aria-describedby'] = $attr['id'] . '-description';
		}

		if ( isset( $attr['id'] ) ) {
			$get_id = $attr['id'];
		} else {
			$get_id = uniqid( 'forminator-select-' );
		}

		! empty( $label ) ? $attr['aria-labelledby'] = $get_id . '-label' : '';

		! empty( $description ) ? $attr['aria-describedby'] = $get_id . '-description' : '';

		$markup = self::implode_attr( $attr );

		if ( self::get_post_data( $attr['name'], false ) ) {
			$value = self::get_post_data( $attr['name'] );
		}

		$html .= self::get_field_label( $label, $get_id, $required );

		if ( 'above' === $descr_position ) {
			$html .= self::get_description( $description, $get_id, $descr_position );
		}

		$markup .= ' data-default-value="' . esc_attr( $value ) . '"';

		$html .= sprintf( '<select %s>', $markup );

			$html .= self::populate_options_for_select( $options, $value );

		$html .= '</select>';

		if ( 'above' !== $descr_position ) {
			$html .= self::get_description( $description, $get_id, $descr_position );
		}

		return apply_filters( 'forminator_field_create_select', $html, $attr, $label, $options, $value, $description );
	}

	/**
	 * Return new simple select field
	 *
	 * @since 1.0
	 *
	 * @param array  $attr Field Attribute.
	 * @param array  $options Field Options.
	 * @param string $value Field Value.
	 * @param string $description Field Description.
	 *
	 * @return mixed
	 */
	public static function create_simple_select( $attr = array(), $options = array(), $value = '', $description = '' ) {

		_deprecated_function( 'create_simple_select', '1.6.1', 'create_select' );

		$html = '';

		$get_id = uniqid( 'forminator-select-' );

		! empty( $description ) ? $attr['aria-describedby'] = $get_id . '-description' : '';

		$markup = self::implode_attr( $attr );

		if ( self::get_post_data( $attr['name'], false ) ) {
			$value = self::get_post_data( $attr['name'] );
		}

		$html .= sprintf( '<select %s>', $markup );

			$html .= self::populate_options_for_select( $options, $value );

		$html .= '</select>';

		if ( ! empty( $description ) ) {
			$html .= self::get_description( $description, $get_id );
		}

		return apply_filters( 'forminator_field_create_simple_select', $html, $attr, $options, $value, $description );
	}

	/**
	 * Populate <options>s for <select>
	 *
	 * @since 1.5.2
	 *
	 * @param array  $options Options.
	 * @param string $selected_value Selected value.
	 *
	 * @return string
	 */
	public static function populate_options_for_select( $options, $selected_value = '' ) {
		$html = '';
		foreach ( $options as $option ) {
			$selected = '';
			$disabled = isset( $option['disabled'] ) && $option['disabled'] ? ' disabled' : '';

			if ( isset( $option['value'] ) && is_array( $option['value'] ) ) {
				$populated_optgroup_options = self::populate_options_for_select( $option['value'], $selected_value );
				$html                      .= sprintf( '<optgroup label="%s">%s</optgroup>', esc_attr( $option['label'] ), $populated_optgroup_options );
			} else {
				if ( ( '' === $selected_value && '' === $option['value'] )
						|| ( '' !== $selected_value && $option['value'] == $selected_value ) // phpcs:ignore Universal.Operators.StrictComparisons.LooseEqual -- loose comparison ok : possible compare '01' and '1'.
						|| ! empty( $option['selected'] ) ) {
					$selected = 'selected="selected"';
				}
				$html .= sprintf( '<option value="%s" %s%s>%s</option>', esc_html( $option['value'] ), $selected, $disabled, esc_html( $option['label'] ) );
			}
		}

		return $html;
	}

	/**
	 * Create file upload
	 *
	 * @since 1.0
	 *
	 * @param string  $id Field Id.
	 * @param string  $name Field name.
	 * @param string  $description Field description.
	 * @param bool    $required Field required.
	 * @param string  $design Design.
	 * @param string  $file_type File type.
	 * @param integer $form_id Form Id.
	 * @param array   $upload_attr Upload attributes.
	 *
	 * @return string $html
	 */
	public static function create_file_upload( $id, $name, $description, $required, $design, $file_type = 'single', $form_id = 0, $upload_attr = array() ) {

		$html             = '';
		$style            = '';
		$field_id         = $id;
		$id               = 'forminator-field-' . $field_id;
		$button_id        = 'forminator-field-' . $field_id . '_button';
		$mainclass        = 'forminator-file-upload';
		$class            = 'forminator-input-file';
		$aria_describedby = ( ! empty( $description ) ? ' aria-describedby="' . esc_attr( $id . '-description' ) . '"' : '' );

		if ( $required ) {
			$class .= '-required do-validate';
		}

		if ( 'multiple' === $file_type ) {
			$mainclass = 'forminator-multi-upload';
			$class    .= ' ' . $id . '-' . $form_id;
		} elseif ( 'none' !== $design ) {
			$upload_attr['tabindex'] = '-1';
		}

		$upload_data = self::implode_attr( $upload_attr );

		if ( 'above' === self::$description_position ) {
			$html .= self::get_description( $description, $id, self::$description_position );
		}

		$html .= sprintf(
			'<div class="%s %s" data-element="%s"%s>',
			$mainclass,
			$style,
			$field_id,
			$aria_describedby
		);

			$html .= sprintf( '<input type="file" name="%s" id="%s" class="%s" %s>', $name, $id, $class, $upload_data );

		if ( 'none' === $design ) {

			$html .= sprintf( '<button class="forminator-upload--remove" style="display: none;">%s</button>', esc_html__( 'Remove', 'forminator' ) );

		} elseif ( 'multiple' === $file_type ) {

				$html .= '<div class="forminator-multi-upload-message" aria-hidden="true">';

					$html .= '<span class="forminator-icon-upload" aria-hidden="true"></span>';

					$html .= '<p>';
						// translators: %1$s - Opening anchor tag, %2$s - Closing anchor tag.
						$html .= sprintf( esc_html__( 'Drag and Drop (or) %1$sChoose Files%2$s', 'forminator' ), '<a class="forminator-upload-file--' . $id . '" href="#" onclick="return false;">', '</a>' );
					$html     .= '</p>';

				$html .= '</div>';

		} else {

			$html .= sprintf( '<button id="%s" class="forminator-button forminator-button-upload" data-id="%s">', $button_id, $id );

			if ( 'material' === $design ) {

				$html .= sprintf(
					'<span>%s</span>',
					esc_html__( 'Choose File', 'forminator' )
				);

				$html .= '<span aria-hidden="true"></span>';

			} else {
				$html .= esc_html__( 'Choose File', 'forminator' );
			}

				$html .= '</button>';

				$html .= sprintf(
					'<span data-empty-text="%s">%s</span>',
					esc_html__( 'No file chosen', 'forminator' ),
					esc_html__( 'No file chosen', 'forminator' )
				);

			$html .= '<button class="forminator-button-delete" style="display: none;">';

				$html .= '<i class="forminator-icon-close" aria-hidden="true"></i>';

				$html .= sprintf(
					'<span class="forminator-screen-reader-only">%s</span>',
					esc_html__( 'Delete uploaded file', 'forminator' )
				);

			$html .= '</button>';
		}

		$html .= '</div>';

		// Check if description is not empty and append it.
		if ( 'above' !== self::$description_position ) {
			$html .= self::get_description( $description, $id, self::$description_position );
		}

		return apply_filters( 'forminator_field_create_file_upload', $html, $id, $name, $required );
	}

	/**
	 * Return string from array
	 *
	 * @since 1.0
	 *
	 * @param array $args Attributes.
	 *
	 * @return string
	 */
	public static function implode_attr( $args ) {
		$data = array();

		foreach ( $args as $key => $value ) {
			$data[] = $key . '="' . esc_attr( $value ) . '"';
		}

		return implode( ' ', $data );
	}

	/**
	 * Validate data
	 *
	 * @since 1.0
	 *
	 * @param array        $field Field.
	 * @param array|string $data - the data to be validated.
	 */
	public function validate( $field, $data ) {
	}

	/**
	 * Validate field data
	 *
	 * @param array        $field_array Field settings.
	 * @param array|string $field_data - the data to be validated.
	 * @return array       validated field data.
	 */
	public function validate_entry( $field_array, $field_data ) {
		// Validate data when its available.
		if ( $this->is_available( $field_array ) ) {

			/**
			 * Mayble re autofill, when autofill not editable, it should return autofill value
			 *
			 * @since 1.0.5
			 */
			$field_data = $this->maybe_autofill( $field_array, $field_data, Forminator_CForm_Front_Action::$module_settings );
			$this->validate( $field_array, $field_data );
		}

		return $field_data;
	}

	/**
	 * Check if entry is valid for the field
	 *
	 * @since 1.0
	 * @return bool|array true on valid, or array validation messages on invalid
	 */
	public function is_valid_entry() {
		$this->is_valid = empty( $this->validation_message );
		if ( ! $this->is_valid ) {
			foreach ( $this->validation_message as $field_name => $error ) {
				Forminator_CForm_Front_Action::$submit_errors[][ $field_name ] = $error;
			}
			return $this->validation_message;
		}

		return $this->is_valid;
	}

	/**
	 * Check if field has input limit
	 *
	 * @since 1.0
	 *
	 * @param array $field Field.
	 *
	 * @return bool
	 */
	public function has_limit( $field ) {
		if ( isset( $field['limit'] ) && intval( $field['limit'] ) > 0 ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if field is required
	 *
	 * @since 1.0
	 *
	 * @param array $field Field.
	 *
	 * @return bool
	 */
	public function is_required( $field ) {
		$required = self::get_property( 'required', $field, false );
		$required = filter_var( $required, FILTER_VALIDATE_BOOLEAN );

		return $required;
	}

	/**
	 * Check if Field is hidden based on conditions property and POST-ed data
	 *
	 * @param array  $field_settings Field settings.
	 * @param array  $extra_conditions payment plan conditions.
	 * @param string $group_suffix Group suffix.
	 *
	 * @return bool
	 */
	public static function is_hidden( $field_settings, $extra_conditions = array(), $group_suffix = '' ) {
		$conditions       = isset( $extra_conditions['conditions'] ) ? $extra_conditions['conditions'] : self::get_field_conditions( $field_settings, $group_suffix );
		$condition_rule   = isset( $extra_conditions['condition_rule'] ) ? $extra_conditions['condition_rule'] : self::get_property( 'condition_rule', $field_settings, 'all' );
		$condition_action = isset( $extra_conditions['condition_action'] ) ? $extra_conditions['condition_action'] : self::get_property( 'condition_action', $field_settings, 'show' );

		// empty conditions.
		if ( empty( $conditions ) ) {
			return false;
		}

		$condition_fulfilled = 0;
		$conditions_count    = 0;

		foreach ( $conditions as $condition ) {
			$element_id = $condition['element_id'];
			// Increase conditions count.
			++$conditions_count;

			if ( in_array( $element_id, Forminator_CForm_Front_Action::$hidden_fields, true ) ) {
				$current_is_hidden      = true;
				$is_condition_fulfilled = isset( $condition['rule'] ) && 'is_not' === $condition['rule']
					&& isset( $condition['value'] ) && ! is_null( $condition['value'] ) && '' !== $condition['value'];
			} else {
				$current_is_hidden      = false;
				$is_condition_fulfilled = self::is_condition_matched( $condition );
			}

			if ( $is_condition_fulfilled ) {
				++$condition_fulfilled;
			} elseif ( 'all' === $condition_rule ) {
				// if condition rule is ALL and at least one condition isn't matched - we don't need to check others.
				break;
			}

			// Check parent conditions only if the current condition is matched.
			if ( $is_condition_fulfilled && ! $current_is_hidden && Forminator_Front_Action::$module_object ) {
				$parent_field = Forminator_Front_Action::$module_object->get_field( $element_id );
				// If the parent field and the current field are in the same group, we need to pass the group suffix to check their visibility correctly.
				if ( ! empty( $field_settings['parent_group'] ) && ! empty( $parent_field['parent_group'] )
					&& $field_settings['parent_group'] === $parent_field['parent_group'] ) {
						$parent_hidden = self::is_hidden( $parent_field, array(), $group_suffix );
				} else {
					$parent_hidden = self::is_hidden( $parent_field );
				}

				if ( $parent_hidden ) {
					--$condition_fulfilled;
					if ( 'all' === $condition_rule ) {
						break;
					}
				}
			}
			// There is no sense to continue checking if at least 1 condition is matched for ANY condition rule.
			if ( 'any' === $condition_rule && $condition_fulfilled ) {
				break;
			}
		}

		$all_matched = ( $condition_fulfilled > 0 && 'any' === $condition_rule ) || ( $conditions_count === $condition_fulfilled && 'all' === $condition_rule );

		// initialized as hidden.
		if ( 'show' === $condition_action ) {
			return ! $all_matched;
		} else {
			// initialized as shown.
			return $all_matched;
		}
	}

	/**
	 * Get field conditions
	 *
	 * @param array  $field_settings Field settings.
	 * @param string $group_suffix Group suffix.
	 * @return array
	 */
	public static function get_field_conditions( $field_settings, $group_suffix ) {
		$conditions = self::get_property( 'conditions', $field_settings, array() );

		foreach ( $conditions as $key => $condition ) {
			if ( forminator_old_field( $condition['element_id'], Forminator_Front_Action::$module_object->get_fields(), Forminator_Front_Action::$module_id ) ) {
				unset( $conditions[ $key ] );
			}
		}

		if ( ! $group_suffix || empty( $conditions ) ) {
			return $conditions;
		}

		$parent_group = $field_settings['parent_group'] ?? '';
		if ( empty( $parent_group ) ) {
			return $conditions;
		}

		foreach ( $conditions as $key => $condition ) {
			$dependent = Forminator_Front_Action::$module_object->get_field( $condition['element_id'], false );
			$dep_group = $dependent ? $dependent->parent_group : '';

			if ( $dep_group === $parent_group ) {
				$conditions[ $key ]['element_id'] .= $group_suffix;
			}
		}

		return $conditions;
	}

	/**
	 * Check if passed condition is matched
	 *
	 * @param array $condition Current condition.
	 * @return bool
	 */
	public static function is_condition_matched( $condition ) {
		$form_data = Forminator_CForm_Front_Action::$prepared_data;

		// empty conditions.
		if ( empty( $condition ) || empty( $form_data['form_id'] ) ) {
			return false;
		}

		$form_id     = $form_data['form_id'];
		$element_id  = $condition['element_id'];
		$field_value = isset( $form_data[ $element_id ] ) ? $form_data[ $element_id ] : '';

		if ( stripos( $element_id, 'upload-' ) !== false && ! isset( $form_data[ $element_id ] ) && isset( $form_data['forminator-multifile-hidden'] ) ) {
			$form_upload_data = $form_data['forminator-multifile-hidden'];
			if ( $form_upload_data && isset( $form_upload_data[ $element_id ] ) ) {
				$field_value = $form_upload_data[ $element_id ];
			}
		}

		// If date field is dropdown type.
		if (
			stripos( $element_id, 'date-' ) !== false &&
			isset( $form_data[ $element_id . '-month' ] ) &&
			isset( $form_data[ $element_id . '-day' ] ) &&
			isset( $form_data[ $element_id . '-year' ] )
		) {
			$field_value       = $form_data[ $element_id . '-year' ] . '-' . $form_data[ $element_id . '-month' ] . '-' . $form_data[ $element_id . '-day' ];
			$date_format       = Forminator_API::get_form_field( $form_id, $element_id, false )->date_format;
			$normalized_format = new Forminator_Date();
			$normalized_format = $normalized_format->normalize_date_format( $date_format );
			$date              = date_create_from_format( 'Y-m-d', $field_value );
			$field_value       = false !== $date ? date_format( $date, $normalized_format ) : '';
		}

		if ( stripos( $element_id, 'signature-' ) !== false ) {
			// We have signature field.
			$is_condition_fulfilled = false;
			$signature_id           = 'field-' . $element_id;

			if ( isset( $form_data[ $signature_id ] ) ) {
				$signature_data = 'ctlSignature' . $form_data[ $signature_id ] . '_data';

				if ( isset( $form_data[ $signature_data ] ) ) {
					$is_condition_fulfilled = self::is_condition_fulfilled( $form_data[ $signature_data ], $condition );
				}
			}
		} elseif ( stripos( $element_id, 'calculation-' ) !== false || stripos( $element_id, 'stripe-' ) !== false ) {
			$is_condition_fulfilled = false;
			if ( isset( Forminator_CForm_Front_Action::$prepared_data[ $element_id ] ) ) {
				// Condition's value is saved as a string value.
				$is_condition_fulfilled = self::is_condition_fulfilled( (string) Forminator_CForm_Front_Action::$prepared_data[ $element_id ], $condition );
			}
		} elseif ( stripos( $element_id, 'checkbox-' ) !== false || stripos( $element_id, 'radio-' ) !== false ) {
			$is_condition_fulfilled = self::is_condition_fulfilled( $field_value, $condition );
		} elseif ( stripos( $element_id, 'rating-' ) !== false ) {
			$rating_value           = explode( '/', $field_value )[0] ?? 0;
			$is_condition_fulfilled = self::is_condition_fulfilled( $rating_value, $condition );
		} elseif ( stripos( $element_id, 'currency-' ) !== false
			|| stripos( $element_id, 'number-' ) !== false ) {
			$is_condition_fulfilled = false;
			$form_field             = Forminator_Front_Action::$module_object->get_field( $element_id );
			if ( $form_field ) {
				$field_value            = self::forminator_replace_number( $form_field, $field_value );
				$is_condition_fulfilled = self::is_condition_fulfilled( $field_value, $condition );
			}
		} else {
			$is_condition_fulfilled = self::is_condition_fulfilled( $field_value, $condition, $form_id );
		}

		return $is_condition_fulfilled;
	}

	/**
	 * Check if Form Field value fullfilled the condition
	 *
	 * @since 1.0
	 *
	 * @param mixed        $form_field_value Form field.
	 * @param array        $condition Condition.
	 * @param null|integer $form_id Form ID.
	 *
	 * @return bool
	 */
	public static function is_condition_fulfilled( $form_field_value, $condition, $form_id = null ) {
		if ( is_array( $form_field_value ) ) {
			$form_field_value = forminator_htmlspecialchars_decode_array( $form_field_value );
			$form_field_value = forminator_trim_array( $form_field_value );
		} else {
			$form_field_value = htmlspecialchars_decode( $form_field_value );
			$form_field_value = strtolower( trim( $form_field_value ) );
		}

		$form_field_value = wp_unslash( $form_field_value );
		$condition_value  = trim( $condition['value'] );

		// Remove lines below coz strtolower is already applied using forminator_trim_array.
		// if ( is_array( $form_field_value ) ) {
		// $form_field_value = array_map( 'strtolower', $form_field_value );
		// } else if ( is_string( $form_field_value ) ) {
		// $form_field_value = strtolower( $form_field_value );
		// }.

		$element_id = $condition['element_id'];
		if ( stripos( $element_id, 'upload-' ) !== false ) {
			// Single file upload type.
			if ( ! empty( $form_field_value['file']['name'] ) ) {
				$form_field_value = $form_field_value['file']['name'];
				// Multiple file upload type.
			} elseif ( ! empty( $form_field_value['file'] ) && is_array( $form_field_value['file'] ) ) {
				$file_names = array();
				foreach ( $form_field_value['file'] as $file ) {
					if ( ! empty( $file['file_name'] ) ) {
						$file_names[] = $file['file_name'];
					}
				}
				$form_field_value = $file_names;
			}
		}

		if ( is_string( $condition_value ) ) {
			$condition_value = strtolower( $condition_value );
		}

		switch ( $condition['rule'] ) {
			case 'is':
				if ( is_array( $form_field_value ) ) {
					// possible input is "1" to be compared with 1.
					return in_array( $condition_value, $form_field_value ); //phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
				}
				if ( is_numeric( $condition_value ) ) {
					return ( (float) $form_field_value === (float) $condition_value );
				}

				return ( $form_field_value === $condition_value );
			case 'is_not':
				if ( is_array( $form_field_value ) ) {
					// possible input is "1" to be compared with 1.
					return ! in_array( $condition_value, $form_field_value ); //phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
				}

				if ( is_numeric( $condition_value ) ) {
					return ( (float) $form_field_value !== (float) $condition_value );
				}

				return ( $form_field_value !== $condition_value );
			case 'is_great':
				if ( ! is_numeric( $condition_value ) ) {
					return false;
				}
				if ( ! is_numeric( $form_field_value ) ) {
					return false;
				}

				return $form_field_value > $condition_value;
			case 'is_less':
				if ( ! is_numeric( $condition_value ) ) {
					return false;
				}
				if ( ! is_numeric( $form_field_value ) ) {
					return false;
				}

				return $form_field_value < $condition_value;
			case 'contains':
				if ( is_array( $form_field_value ) ) {
					foreach ( $form_field_value as $value ) {
						if ( stripos( $value, $condition_value ) !== false ) {
							return true;
						}
					}
					return false;
				}
				return ( stripos( $form_field_value, $condition_value ) === false ? false : true );
			case 'does_not_contain':
				if ( is_array( $form_field_value ) ) {
					foreach ( $form_field_value as $value ) {
						if ( stripos( $value, $condition_value ) !== false ) {
							return false;
						}
					}
					return true;
				}
				return ! ( stripos( $form_field_value, $condition_value ) !== false );
			case 'starts':
				if ( is_array( $form_field_value ) ) {
					foreach ( $form_field_value as $value ) {
						if ( stripos( $value, $condition_value ) === 0 ) {
							return true;
						}
					}
					return false;
				}
				return ( stripos( $form_field_value, $condition_value ) === 0 ? true : false );
			case 'ends':
				if ( is_array( $form_field_value ) ) {
					foreach ( $form_field_value as $value ) {
						if ( substr( $value, - strlen( $condition_value ) ) === $condition_value ) {
							return true;
						}
					}
					return false;
				}
				return ( substr( $form_field_value, - strlen( $condition_value ) ) === $condition_value );

			case 'day_is':
				if ( null !== $form_id && ! empty( $form_field_value ) ) {
					$day = self::get_day_or_month( $form_field_value, $condition['element_id'], $form_id, 'D' );
					return strtolower( $day ) === $condition_value;
				}

				return false;
			case 'day_is_not':
				if ( null !== $form_id && ! empty( $form_field_value ) ) {
					$day = self::get_day_or_month( $form_field_value, $condition['element_id'], $form_id, 'D' );
					return strtolower( $day ) !== $condition_value;
				}

				return false;
			case 'month_is':
				if ( null !== $form_id && ! empty( $form_field_value ) ) {
					$month = self::get_day_or_month( $form_field_value, $condition['element_id'], $form_id, 'M' );
					return strtolower( $month ) === $condition_value;
				}

				return false;
			case 'month_is_not':
				if ( null !== $form_id && ! empty( $form_field_value ) ) {
					$month = self::get_day_or_month( $form_field_value, $condition['element_id'], $form_id, 'M' );
					return strtolower( $month ) !== $condition_value;
				}

				return false;
			case 'is_before':
				if ( null !== $form_id && ! empty( $form_field_value ) ) {
					$date = self::get_day_or_month( $form_field_value, $condition['element_id'], $form_id, 'j F Y' );

					return strtotime( $date ) < Forminator_Date::prepare_condition_date( $condition_value );
				}

				return false;
			case 'is_after':
				if ( null !== $form_id && ! empty( $form_field_value ) ) {
					$date = self::get_day_or_month( $form_field_value, $condition['element_id'], $form_id, 'j F Y' );
					return strtotime( $date ) > Forminator_Date::prepare_condition_date( $condition_value );
				}

				return false;
			case 'is_before_n_or_more_days':
				if ( null !== $form_id && ! empty( $form_field_value ) ) {
					$date = self::get_day_or_month( $form_field_value, $condition['element_id'], $form_id, 'Y-m-d' );
					return strtotime( $date ) <= strtotime( '-' . $condition_value . ' days' );
				}

				return false;
			// date_is_less_than_n_days_before_current_date.
			case 'is_before_less_than_n_days':
				if ( null !== $form_id && ! empty( $form_field_value ) ) {
					$date         = self::get_day_or_month( $form_field_value, $condition['element_id'], $form_id, 'Y-m-d' );
					$rule_date    = strtotime( '-' . $condition_value . ' days' );
					$current_date = strtotime( 'today' );
					return $rule_date < strtotime( $date ) && strtotime( $date ) <= $current_date;
				}

				return false;
			case 'is_after_n_or_more_days':
				if ( null !== $form_id && ! empty( $form_field_value ) ) {
					$date = self::get_day_or_month( $form_field_value, $condition['element_id'], $form_id, 'Y-m-d' );
					return strtotime( $date ) >= strtotime( '+' . $condition_value . ' days' );
				}

				return false;
			// date_is_less_than_n_days_after_current_date.
			case 'is_after_less_than_n_days':
				if ( null !== $form_id && ! empty( $form_field_value ) ) {
					$date         = self::get_day_or_month( $form_field_value, $condition['element_id'], $form_id, 'Y-m-d' );
					$rule_date    = strtotime( '+' . $condition_value . ' days' );
					$current_date = strtotime( 'today' );
					return $rule_date > strtotime( $date ) && strtotime( $date ) >= $current_date;
				}

				return false;

			case 'is_correct':
				return $form_field_value ? true : false;
			case 'is_incorrect':
				return ! $form_field_value ? true : false;
			case 'is_final_result':
				return $form_field_value === $condition['element_id'];
			case 'is_not_final_result':
				return $form_field_value !== $condition['element_id'];
			default:
				return false;
		}
	}

	/**
	 * Get the day or month from given date
	 *
	 * @since 1.14
	 *
	 * @param mixed   $form_field_value Form field value.
	 * @param string  $element_id Element Id.
	 * @param integer $form_id Form ID.
	 * @param string  $format Format.
	 *
	 * @return string|bool
	 */
	public static function get_day_or_month( $form_field_value, $element_id, $form_id, $format ) {

		if ( empty( $form_field_value ) ) {
			return false;
		}
		$date_format       = Forminator_API::get_form_field( $form_id, $element_id, false )->date_format;
		$normalized_format = new Forminator_Date();
		$normalized_format = $normalized_format->normalize_date_format( $date_format );
		$date              = date_create_from_format( $normalized_format, $form_field_value );

		if ( false === $date ) {
			$date = date_create_from_format( 'Y/m/d', $form_field_value );
		}

		if ( 'D' === $format ) {
			// Day format is based on fields' visibility day format.
			return substr( date_format( $date, $format ), 0, -1 );
		} else {
			return date_format( $date, $format );
		}
	}

	/**
	 * Get subfield id.
	 *
	 * @param string $id Field ID.
	 * @param string $prefix Field prefix.
	 * @return string
	 */
	protected static function get_subfield_id( $id, $prefix ) {
		$parts    = explode( '-', $id );
		$real_id  = implode( '-', array_slice( $parts, 0, 2 ) );
		$group_id = implode( '-', array_slice( $parts, 2 ) );

		$subfield_id = $real_id . $prefix;
		if ( $group_id ) {
			$subfield_id .= '-' . $group_id;
		}

		return $subfield_id;
	}

	/**
	 * Return field ID
	 *
	 * @since 1.0
	 *
	 * @param array $field Field.
	 *
	 * @return string
	 */
	public function get_id( $field ) {
		return self::get_property( 'element_id', $field );
	}

	/**
	 * Field validation rules
	 *
	 * @since 1.0
	 * @return string
	 */
	public function get_validation_rules() {
		return '';
	}

	/**
	 * Field validation messages
	 *
	 * @since 1.0
	 * @return string
	 */
	public function get_validation_messages() {
		return '';
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
	public function sanitize(
		// @noinspection PhpUnusedParameterInspection.
		$field,
		$data
	) {
		return $data;
	}

	/**
	 * Sanitize value
	 *
	 * @param mixed $value Value.
	 * @return string
	 */
	public function sanitize_value( $value ) {
		return htmlspecialchars( $value, ENT_COMPAT );
	}

	/**
	 * Check if field is available
	 * Override it for field that needs dependencies
	 * Example : `captcha` that needs `captcha_key` to be displayed properly
	 *
	 * @see   Forminator_Captcha::is_available()
	 *
	 * @since 1.0.3
	 *
	 * @param array $field Field.
	 *
	 * @return bool
	 */
	public function is_available(
		// @noinspection PhpUnusedParameterInspection.
		$field
	) {
		return true;
	}

	/**
	 * Return form style
	 *
	 * @since 1.0.3
	 *
	 * @param array $settings Settings.
	 *
	 * @return string|bool
	 */
	public function get_form_style( $settings ) {
		if ( isset( $settings['form-style'] ) ) {
			return $settings['form-style'];
		}

		return false;
	}

	/**
	 * Return value stored in $_POST, or the fallback value
	 *
	 * @since 1.1
	 *
	 * @param string $id Field Id.
	 * @param mixed  $fallback Fallback.
	 *
	 * @return mixed value of $_POST[$id] or $fallback when unavailable
	 */
	public static function get_post_data( $id, $fallback = '' ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- The nonce is verified before the method call.
		if ( isset( $_POST[ $id ] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput -- The nonce is verified before the method call and sanitized in Forminator_Core::sanitize_array.
			$post_data = Forminator_Core::sanitize_array( $_POST[ $id ], $id );
		}
		if ( ! empty( $post_data ) ) {
			return self::get_post_data_sanitize( $post_data, $fallback );
		}

		return $fallback;
	}

	/**
	 * Return sanitized $_POST value, or the fallback value
	 *
	 * @since 1.6.3
	 *
	 * @param string $data Post data.
	 * @param mixed  $fallback Fallback.
	 *
	 * @return mixed value of $_POST[$id] or $fallback when unavailable
	 */
	public static function get_post_data_sanitize( $data, $fallback ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		if ( is_array( $data ) ) {
			$escaped = array();

			foreach ( $data as $key => $value ) {
				$escaped[ $key ] = self::get_post_data_sanitize( $value, '' );
			}

			return $escaped;
		}

		return esc_html( $data );
	}

	/**
	 * Abstraction of autofill settings
	 *
	 * @since 1.0.5
	 *
	 * @param array $settings Settings.
	 *
	 * @return array
	 */
	public function autofill_settings( $settings = array() ) {
		return $settings;
	}

	/**
	 * Get Autofill setting as paired ['element_id' => $setting]
	 *
	 * @since 1.0.5
	 *
	 * @param array $settings Settings.
	 *
	 * @return array
	 */
	public static function get_autofill_setting( $settings ) {

		// Autofill not enabled.
		if ( ! self::is_autofill_enabled( $settings ) ) {
			return array();
		}

		if ( ! empty( $settings['fields-autofill'] ) ) {
			// build to array key.
			$fields_autofill      = $settings['fields-autofill'];
			$fields_autofill_pair = array();
			if ( ! is_array( $fields_autofill ) ) {
				return array();
			}

			foreach ( $fields_autofill as $field_autofill ) {
				if ( empty( $field_autofill['element_id'] ) ) {
					continue;
				}
				$fields_autofill_pair[ $field_autofill['element_id'] ] = $field_autofill;
			}

			return $fields_autofill_pair;
		}

		return array();
	}

	/**
	 * Check if autofill enabled on this form
	 *
	 * @since 1.0.5
	 *
	 * @param array $settings Settings.
	 *
	 * @return bool
	 */
	public static function is_autofill_enabled( $settings ) {
		return isset( $settings['use-autofill'] ) ? forminator_var_type_cast( $settings['use-autofill'], 'bool' ) : false;
	}

	/**
	 * Restore value of the POST fields if its not editable, so we ensure its not modified by anykind
	 * Happens before this POST fields getting validated, so autofill-ed value will be getting validated too
	 *
	 * @since 1.0.5
	 *
	 * @param array $field_array Field array.
	 * @param mixed $field_data Field data.
	 * @param array $settings Settings.
	 *
	 * @return array|mixed|string
	 */
	public function maybe_autofill( $field_array, $field_data, $settings ) {
		if (
			(
				isset( $settings['form-type'] ) &&
				in_array( $settings['form-type'], array( 'registration', 'login' ), true )
			) ||
			! is_user_logged_in()
		) {
			return $field_data;
		}

		$autofill_settings = self::get_autofill_setting( $settings );

		if ( empty( $autofill_settings ) ) {
			return $field_data;
		}

		$element_id = self::get_property( 'element_id', $field_array );
		if ( is_array( $field_data ) ) {
			foreach ( $field_data as $element_id_suffix => $field_datum ) {
				$element_id                = $element_id . '-' . $element_id_suffix;
				$element_autofill_settings = self::get_element_autofill_settings( $element_id, $autofill_settings );
				if ( ! self::element_autofill_is_editable( $element_autofill_settings ) ) {
					// refill with autofill provider.
					$field_data[ $element_id_suffix ] = $this->maybe_replace_to_autofill_value( $field_datum, $element_autofill_settings );
				}
			}
		} else {
			$element_autofill_settings = self::get_element_autofill_settings( $element_id, $autofill_settings );

			if ( ! self::element_autofill_is_editable( $element_autofill_settings ) ) {
				$current_data = $field_data;
				// refill with autofill provider.
				$field_data = $this->maybe_replace_to_autofill_value( $field_data, $element_autofill_settings );
				if ( ! strlen( $field_data ) ) {
					$field_data = $current_data;
				}
			}
		}

		return $field_data;
	}


	/**
	 * Get autofill as markup attributes to used later
	 *
	 * @since   1.0.5
	 *
	 * @example {
	 *  'value' => [] // VALUE.
	 *   'readonly' => 'readonly'
	 * }
	 *
	 * @param string $element_id Element Id.
	 *
	 * @return array
	 */
	public function get_element_autofill_markup_attr( $element_id ) {
		$settings = $this->form_settings;
		if ( ! self::is_autofill_enabled( $settings ) ) {
			return array();
		}

		$autofill_settings = self::get_autofill_setting( $settings );

		if ( empty( $autofill_settings ) ) {
			return array();
		}

		$element_autofill_settings = self::get_element_autofill_settings( $element_id, $autofill_settings );
		$value                     = $this->maybe_replace_to_autofill_value( '', $element_autofill_settings );

		// only return value when its autofilled.
		if ( ! empty( $value ) ) {
			$markup_attr = array(
				'value' => $value,
			);
			// only disable if value is not empty.
			if ( ! self::element_autofill_is_editable( $element_autofill_settings ) ) {
				$markup_attr['readonly'] = 'readonly';
			}

			return $markup_attr;
		}

		return array();
	}

	/**
	 * Get element autofill value if all requirement(s) fulfilled
	 * - Autofill Provider activated
	 *
	 * @param mixed $element_value Element value.
	 * @param array $element_autofill_settings Element settings.
	 *
	 * @return mixed|string
	 */
	public function maybe_replace_to_autofill_value( $element_value, $element_autofill_settings ) {
		if ( ! empty( $element_autofill_settings['provider'] ) ) {
			$attribute_provider = $element_autofill_settings['provider'];
			$provider_parts     = explode( '.', $attribute_provider );
			if ( ! empty( $provider_parts[1] ) ) {
				$provider_slug     = $provider_parts[0];
				$provider_instance = forminator_autofill_init_provider( $provider_slug );
				if ( $provider_instance ) {
					$element_value = $provider_instance->fill( $provider_parts[1] );
				}
			}
		}

		return $element_value;
	}

	/**
	 * Get individial element autofill setting
	 *
	 * @since 1.0.5
	 *
	 * @param string $element_id Element id.
	 * @param array  $autofill_settings Settings.
	 *
	 * @return array
	 */
	public static function get_element_autofill_settings( $element_id, $autofill_settings ) {
		$autofill_element_settings = array();

		if ( isset( $autofill_settings[ $element_id ] ) && is_array( $autofill_settings[ $element_id ] ) ) {
			$autofill_element_settings = $autofill_settings[ $element_id ];
		}

		return $autofill_element_settings;
	}

	/**
	 * Check if an element is editable when autofill enabled
	 *
	 * @param array $element_autofill_settings Settings.
	 *
	 * @return bool
	 */
	public static function element_autofill_is_editable( $element_autofill_settings ) {
		if ( isset( $element_autofill_settings['is_editable'] ) && 'yes' === $element_autofill_settings['is_editable'] ) {
			return true;
		}

		return false;
	}

	/**
	 * Get required message for multiple name field
	 *
	 * @param string $id Field Id.
	 * @param array  $field Field.
	 * @param string $property Property.
	 * @param string $slug Slug.
	 * @param mixed  $fallback Fallback.
	 *
	 * @return string
	 */
	protected function get_field_multiple_required_message( $id, $field, $property, $slug, $fallback ) {
		// backward compat *_required_message.
		$required_message = self::get_property( $property, $field, self::FIELD_PROPERTY_VALUE_NOT_EXIST, 'string' );
		if ( self::FIELD_PROPERTY_VALUE_NOT_EXIST === $required_message || empty( $required_message ) ) {
			$required_message = $fallback;
		}

		$required_message = wp_kses_post( $required_message );

		$required_message = apply_filters( "forminator_{$this->slug}_field_{$slug}_required_validation_message", $required_message, $id, $field );

		return $required_message;
	}

	/**
	 * Get calculable value
	 *
	 * @since 1.7
	 *
	 * @param array|mixed $submitted_field_data Field data.
	 * @param array       $field_settings Settings.
	 *
	 * @return float|string
	 */
	public static function get_calculable_value( $submitted_field_data, $field_settings ) {
		$field_slug       = $field_settings['type'];
		$calculable_value = self::FIELD_NOT_CALCULABLE;

		/**
		 * Filter formula being used on calculable value on abstract level
		 * this hook can be used on un-implemented calculation field
		 *
		 * @since 1.7
		 *
		 * @param float $calculable_value
		 * @param array $submitted_field_data
		 * @param array $field_settings
		 *
		 * @return string|int|float formula, or hardcoded value
		 */
		$calculable_value = apply_filters( "forminator_field_{$field_slug}_calculable_value", $calculable_value, $submitted_field_data, $field_settings );

		return $calculable_value;
	}

	/**
	 *
	 * Get calculable precision
	 *
	 * @since 1.7
	 *
	 * @param array $field_settings Settings.
	 *
	 * @return int
	 */
	public static function get_calculable_precision( $field_settings ) {
		$fallback  = ! empty( $field_settings['type'] ) && 'number' === $field_settings['type'] ? 0 : 2;
		$precision = self::get_property( 'precision', $field_settings, $fallback, 'num' );

		/**
		 * Filter formula being used on calculable value on abstract level
		 * this hook can be used on un-implemented calculation field
		 *
		 * @param int   $precision
		 * @param array $submitted_data
		 * @param array $field_settings
		 *
		 * @return string|int|float formula, or hardcoded value
		 */
		$precision = apply_filters( 'forminator_field_calculable_precision', $precision, Forminator_CForm_Front_Action::$prepared_data, $field_settings );

		return $precision;
	}

	/**
	 * Get calculable number format
	 *
	 * @since 1.52.0
	 *
	 * @param mixed $field_settings Field settings.
	 * @param mixed $value Value.
	 * @return string
	 */
	public static function get_calculable_number_format( $field_settings, $value ) {
		$precision = self::get_calculable_precision( $field_settings );
		$value     = number_format( floatval( $value ), $precision, '.', '' );

		return $value;
	}

	/**
	 * Return if field has pre-fill value filled
	 *
	 * @since 1.10
	 *
	 * @param array          $field Field.
	 * @param boolean|string $prefix Prefix.
	 * @return bool
	 */
	public function has_prefill( $field, $prefix = false ) {
		if ( $prefix ) {
			$prefix = $prefix . '_';
		}

		$prefill = self::get_property( $prefix . 'prefill', $field, false );

		if ( $prefill ) {
			return true;
		}

		return false;
	}

	/**
	 * Get pre-fill value if set, else return $default
	 *
	 * @since 1.10
	 *
	 * @param array       $field Field.
	 * @param mixed       $default_value Default value.
	 * @param bool|string $prefix Prefix.
	 * @return mixed
	 */
	public function get_prefill( $field, $default_value, $prefix = false ) {
		if ( $prefix ) {
			$prefix = $prefix . '_';
		}

		$prefill = self::get_property( $prefix . 'prefill', $field, false );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- The nonce is verified before the method call.
		if ( isset( $_REQUEST[ $prefill ] ) ) {
			if ( 'textarea' === $field['type'] ) {
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- The nonce is verified before the method call.
				$return = rawurldecode( wp_kses_post( wp_unslash( $_REQUEST[ $prefill ] ) ) );
			} else {
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- The nonce is verified before the method call.
				$return = rawurldecode( esc_html( wp_unslash( $_REQUEST[ $prefill ] ) ) );
			}

			if ( $return ) {
				return $return;
			}
		}

		return $default_value;
	}

	/**
	 * Replace object value from prefill
	 *
	 * @since 1.10
	 *
	 * @param array  $field Field.
	 * @param array  $attributes Attributes.
	 * @param string $prefix Prefix.
	 * @param bool   $default_value Default value.
	 * @return mixed
	 */
	public function replace_from_prefill( $field, $attributes, $prefix, $default_value = false ) {
		if ( $this->has_prefill( $field, $prefix ) ) {
			// We have pre-fill parameter, use its value or $value.
			$value = $this->get_prefill( $field, $default_value, $prefix );

			$attributes['value'] = esc_html( $value );
		}

		return $attributes;
	}

	/**
	 * Get TinyMCE arguments for js on front-end
	 *
	 * @param string $id Editor ID.
	 * @return string
	 */
	public static function get_tinymce_args( $id ) {
		$args = "{
			tinymce: {
				wpautop  : true,
				theme    : 'modern',
				skin     : 'lightgray',
				language : 'en',
				formats  : {
					alignleft  : [
						{ selector: 'p,h1,h2,h3,h4,h5,h6,td,th,div,ul,ol,li', styles: { textAlign: 'left' } },
						{ selector: 'img,table,dl.wp-caption', classes: 'alignleft' }
					],
					aligncenter: [
						{ selector: 'p,h1,h2,h3,h4,h5,h6,td,th,div,ul,ol,li', styles: { textAlign: 'center' } },
						{ selector: 'img,table,dl.wp-caption', classes: 'aligncenter' }
					],
					alignright : [
						{ selector: 'p,h1,h2,h3,h4,h5,h6,td,th,div,ul,ol,li', styles: { textAlign: 'right' } },
						{ selector: 'img,table,dl.wp-caption', classes: 'alignright' }
					],
					strikethrough: { inline: 'del' }
				},
				relative_urls       : false,
				remove_script_host  : false,
				convert_urls        : false,
				browser_spellcheck  : true,
				fix_list_elements   : true,
				entities            : '38,amp,60,lt,62,gt',
				entity_encoding     : 'raw',
				keep_styles         : false,
				paste_webkit_styles : 'font-weight font-style color',
				preview_styles      : 'font-family font-size font-weight font-style text-decoration text-transform',
				tabfocus_elements   : ':prev,:next',
                plugins    : 'charmap,hr,media,paste,tabfocus,textcolor,fullscreen,wptextpattern,lists,wordpress,wpeditimage,wpgallery,link,wplink,wpdialogs,wpview'," // phpcs:ignore WordPress.WP.CapitalPDangit.MisspelledInText -- false positive.
				. "
				resize     : 'vertical',
				menubar    : false,
				indent     : false,
				toolbar1   : 'formatselect,bold,italic,bullist,numlist,blockquote,alignleft,aligncenter,alignright,link,wp_more,fullscreen,wp_adv',
				toolbar2   : 'strikethrough,underline,hr,forecolor,pastetext,alignjustify,removeformat,charmap,outdent,indent,undo,redo,wp_help',
				toolbar3   : '',
				toolbar4   : '',
				body_class : 'id post-type-post post-status-publish post-format-standard',
				wpeditimage_disable_captions: false,
				wpeditimage_html5_captions  : true

			},
			quicktags: true,
		}";

		/**
		 * Filter TinyMCE arguments for js on front-end.
		 *
		 * @since 1.1
		 *
		 * @param string $args TinyMCE arguments.
		 * @param string $id   Editor ID.
		 */
		$args = apply_filters( 'forminator_tinymce_args', $args, $id );

		return $args;
	}

	/**
	 * Number separators
	 *
	 * @param string $separator Separator.
	 * @param array  $field Field.
	 *
	 * @return array
	 */
	public static function forminator_separators( $separator, $field ) {

		$separators = array(
			'blank'       => array(
				'point'     => '.',
				'separator' => '',
			),
			'comma_dot'   => array(
				'point'     => '.',
				'separator' => ',',
			),
			'dot_comma'   => array(
				'point'     => ',',
				'separator' => '.',
			),
			'space_comma' => array(
				'point'     => ',',
				'separator' => ' ',
			),
		);
		if ( 'custom' === $separator ) {
			$data_point     = self::get_property( 'decimal-separators', $field, '.' );
			$data_separator = self::get_property( 'thousand-separators', $field, ',' );
		} else {
			$data_point     = $separators[ $separator ]['point'];
			$data_separator = $separators[ $separator ]['separator'];
		}

		return array(
			'separator' => $data_separator,
			'point'     => $data_point,
		);
	}

	/**
	 * Number formatting
	 *
	 * @param string $field Field.
	 * @param string $number Number.
	 *
	 * @return string
	 */
	public static function forminator_number_formatting( $field, $number ) {
		$precision  = self::get_calculable_precision( $field );
		$separator  = self::get_property( 'separators', $field, 'blank' );
		$separators = self::forminator_separators( $separator, $field );
		// All unformatted numbers use either a comma or a dot as the decimal point. We need to convert commas to dots.
		$data_value = (float) str_replace( ',', '.', $number );
		$formatted  = number_format( $data_value, $precision, $separators['point'], $separators['separator'] );

		if ( ! empty( $field['prefix'] ) || ! empty( $field['suffix'] ) ) {
			// Prefix.
			$formatted = ( ! empty( $field['prefix'] ) ? $field['prefix'] . ' ' : '' ) . $formatted;
			// Suffix.
			$formatted = $formatted . ( ! empty( $field['suffix'] ) ? ' ' . $field['suffix'] : '' );
		} elseif ( ! empty( $field['currency'] ) ) {
			$formatted .= ' ' . $field['currency'];
		}

		return $formatted;
	}

	/**
	 * Replace number formatting
	 *
	 * @param array  $field Field.
	 * @param string $number Number.
	 *
	 * @return string
	 */
	public static function forminator_replace_number( $field, $number ) {
		$separator  = self::get_property( 'separators', $field, 'blank' );
		$separators = self::forminator_separators( $separator, $field );

		// Replace decimal with # temporarily to prevent being replaced with a separator.
		$number = str_replace( $separators['point'], '#', $number );
		$number = str_replace( $separators['separator'], '', $number );
		$number = str_replace( '#', '.', $number );

		return $number;
	}

	/**
	 * Check index and htaccess files inside root directory. And create them if need it.
	 */
	public static function check_upload_root_index_file() {
		$upload_root = forminator_upload_root();
		if ( is_wp_error( $upload_root ) ) {
			return;
		}
		// Make sure it was not called before WP init.
		if ( ! file_exists( $upload_root . 'index.php' ) && function_exists( 'insert_with_markers' ) ) {
			self::add_index_file( $upload_root );
			self::add_htaccess_file();
		}
	}

	/**
	 * Create index file
	 *
	 * @param string $dir Directory.
	 *
	 * @return void
	 */
	public static function add_index_file( $dir ) {
		$dir = untrailingslashit( $dir );
		if ( ! is_dir( $dir ) || ! wp_is_writable( $dir ) || is_link( $dir ) ) {
			return;
		}
		$dp = opendir( $dir );
		if ( ! $dp ) {
			return;
		}

		if ( ! function_exists( 'wp_filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		global $wp_filesystem;
		if ( WP_Filesystem() ) {
			$index_file_path = $dir . '/index.php';
			// creates an empty index.php file.
			$wp_filesystem->put_contents( $index_file_path, '', FS_CHMOD_FILE );
		}

		// restores error handler.
		restore_error_handler();
		while ( ( false !== $file = readdir( $dp ) ) ) { // phpcs:ignore Generic.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition -- false positive
			if ( is_dir( "$dir/$file" ) && '.' !== $file && '..' !== $file ) {
				self::add_index_file( "$dir/$file" );
			}
		}
		closedir( $dp );
	}

	/**
	 * Add index file
	 *
	 * @param string|integer $form_id Form Id.
	 * @param string         $path Path.
	 *
	 * @return void
	 */
	public static function forminator_upload_index_file( $form_id, $path = '' ) {

		$form_id     = absint( $form_id );
		$upload_root = forminator_upload_root();

		if ( is_wp_error( $upload_root ) || ! is_dir( $upload_root ) ) {
			return;
		}

		self::check_upload_root_index_file();
		if ( ! file_exists( forminator_get_upload_path( $form_id ) . 'index.php' ) ) {
			self::add_index_file( forminator_get_upload_path( $form_id ) );
		}
		if ( ! file_exists( $path . 'index.php' ) ) {
			self::add_index_file( $path );
		}
	}

	/**
	 * Add htaccess file
	 */
	public static function add_htaccess_file() {
		global $wp_locale_switcher;

		// Return if $wp_locale_switcher is not ready.
		if ( ! $wp_locale_switcher ) {
			return false;
		}

		$upload_root = forminator_upload_root();

		if ( is_wp_error( $upload_root ) || ! is_dir( $upload_root ) ) {
			return;
		}

		if ( ! wp_is_writable( $upload_root ) ) {
			return;
		}

		$htaccess_file = $upload_root . '.htaccess';
		if ( file_exists( $htaccess_file ) ) {
			wp_delete_file( $htaccess_file );
		}
		$rules = '# Disable parsing of PHP for some server configurations.
<Files *>
  SetHandler none
  SetHandler default-handler
  Options -ExecCGI
  Options -Indexes
  RemoveHandler .cgi .php .php3 .php4 .php5 .phtml .pl .py .pyc .pyo
</Files>
<IfModule mod_php5.c>
  php_flag engine off
</IfModule>
<IfModule headers_module>
  Header set X-Robots-Tag "noindex"
</IfModule>';

		/**
		 * A filter to allow the modification/disabling of parsing certain PHP
		 *
		 * @since 1.23.1
		 *
		 * @param mixed $rules The Rules of what to parse or not to parse
		 */
		$rules = apply_filters( 'forminator_upload_root_htaccess_rules', $rules );
		if ( ! empty( $rules ) ) {
			insert_with_markers( $htaccess_file, 'Forminator', $rules );
		}
	}

	/**
	 * Update metadata for uploaded file.
	 *
	 * @param int    $attachment_id Attachment Id.
	 * @param string $file File.
	 *
	 * @return void
	 */
	public static function generate_upload_metadata( $attachment_id, $file ) {
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		// Generate and save the attachment metas into the database.
		wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $file ) );
	}

	/**
	 * Get required error message
	 *
	 * @return string
	 */
	protected function get_required_error_message() {
		return self::$default_required_messages[ $this->type ];
	}

	/**
	 * Maybe add custom option input.
	 *
	 * @param mixed $field Field.
	 * @param mixed $options Field options.
	 * @param mixed $attributes Input field attributes.
	 * @param array $draft_value Draft value.
	 * @return string
	 */
	public static function maybe_add_custom_option( $field, $options, $attributes = array(), $draft_value = null ) {
		$enable_custom_option = self::get_property( 'enable_custom_option', $field, false );
		$html                 = '';
		if ( $enable_custom_option ) {
			if ( ! isset( $attributes['class'] ) ) {
				$attributes['class'] = 'forminator-input';
			}
			// Get placeholder from options if exists.
			$key = array_search( 'custom_option', array_column( $options, 'key' ), true );
			if ( false !== $key ) {
				if ( ! empty( $options[ $key ]['placeholder'] ) ) {
					$attributes['placeholder'] = $options[ $key ]['placeholder'];
				}
				if ( ! isset( $attributes['aria-labelledby'] ) && ! empty( $options[ $key ]['label'] ) ) {
					$attributes['aria-label'] = $options[ $key ]['label'];
				}
			}
			if ( isset( $draft_value['custom_value'] ) ) {
				$attributes['value'] = isset( $draft_value['custom_value']['value'] ) ? $draft_value['custom_value']['value'] : '';
			}
			$html .= '<div class="forminator-field forminator-custom-input">';
			$html .= self::create_input(
				$attributes,
				false,
				'',
				true,
			);
			$html .= '</div>';
		}
		return $html;
	}

	/**
	 * * Get formatted amount for Stripe and PayPal fields
	 *
	 * @param array        $field Field.
	 * @param string|array $amount_data Amount Data.
	 * @param object       $module Module.
	 * @return string
	 */
	public static function get_formatted_amount( $field, $amount_data, $module = null ) {
		if ( is_array( $amount_data ) ) {
			$amount = $amount_data['amount'];
			// find the selected payment plan.
			if ( ! empty( $field['payments'] ) && ! empty( $amount_data['product_name'] ) ) {
				foreach ( $field['payments'] as $payment ) {
					if ( $payment['plan_name'] === $amount_data['product_name'] ) {
						$field = $payment;
						break;
					}
				}
			}
		} else {
			$amount = $amount_data;
		}

		$amount_type     = self::get_property( 'amount_type', $field, 'fixed' );
		$amount_variable = self::get_property( 'variable', $field, '' );
		if ( empty( $amount_variable ) || 'fixed' === $amount_type ) {
			return $amount;
		}
		if ( null === $module ) {
			if ( empty( Forminator_Front_Action::$module_object ) ) {
				return $amount;
			}
			$module = Forminator_Front_Action::$module_object;
		}
		$field          = $module->get_field( $amount_variable, false );
		$field_settings = $field->to_formatted_array();
		if ( ! $field_settings ) {
			return $amount;
		}
		// Remove the currency key since it's displayed separately.
		unset( $field_settings['currency'] );
		return self::forminator_number_formatting( $field_settings, $amount );
	}
}
