<?php
/**
 * The Forminator_Postdata class.
 *
 * @package Forminator
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_Postdata
 *
 * @since 1.0
 */
class Forminator_Postdata extends Forminator_Field {

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
	public $slug = 'postdata';

	/**
	 * Type
	 *
	 * @var string
	 */
	public $type = 'postdata';

	/**
	 * Position
	 *
	 * @var int
	 */
	public $position = 15;

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
	public $category = 'posts';

	/**
	 * Icon
	 *
	 * @var string
	 */
	public $icon = 'sui-icon-post-pin';

	/**
	 * Draft values
	 *
	 * @var array
	 */
	public $draft_values = array();

	/**
	 * Image extensions
	 *
	 * @var array
	 */
	public $image_extensions = array( 'jpg', 'jpeg', 'jpe', 'gif', 'png', 'bmp' );

	/**
	 * Forminator_Postdata constructor.
	 *
	 * @since 1.0
	 */
	public function __construct() {
		parent::__construct();

		$this->name = esc_html__( 'Post Data', 'forminator' );
		$required   = __( 'This field is required. Please enter the post title.', 'forminator' );

		self::$default_required_messages[ $this->type ] = $required;
	}

	/**
	 * Field defaults
	 *
	 * @since 1.0
	 * @return array
	 */
	public function defaults() {

		return apply_filters(
			'forminator_post_data_defaults_settings',
			array(
				'data_status'        => 'pending',
				'post_title_label'   => esc_attr__( 'Post Title', 'forminator' ),
				'post_content_label' => esc_attr__( 'Post Content', 'forminator' ),
				'post_excerpt_label' => esc_attr__( 'Post Excerpt', 'forminator' ),
				'post_image_label'   => esc_attr__( 'Featured Image', 'forminator' ),
				'category_label'     => esc_attr__( 'Category', 'forminator' ),
				'post_tag_label'     => esc_attr__( 'Tags', 'forminator' ),
				'select_author'      => 1,
				'category_multiple'  => '0',
				'post_tag_multiple'  => '0',
				'post_type'          => 'post',
				'options'            => array(
					array(
						'label' => '',
						'value' => '',
					),
				),
			)
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
		return $settings;

		/*
		// TODO: support autofill-for-postdata.
		// $title_providers    = apply_filters( 'forminator_field_' . $this->slug . '_post_titlle_autofill', array(), $this->slug . '_post_titlle' );.
		// $content_providers  = apply_filters( 'forminator_field_' . $this->slug . '_post_content_autofill', array(), $this->slug . '_post_content' );.
		// $excerpt_providers  = apply_filters( 'forminator_field_' . $this->slug . '_post_excerpt_autofill', array(), $this->slug . '_post_excerpt' );.
		//
		// $autofill_settings = array(.
		// 'postdata-post-title'    => array(.
		// 'values' => forminator_build_autofill_providers( $title_providers ),.
		// ),.
		// 'postdata-post-content'  => array(.
		// 'values' => forminator_build_autofill_providers( $content_providers ),.
		// ),.
		// 'postdata-post-excerpt'  => array(.
		// 'values' => forminator_build_autofill_providers( $excerpt_providers ),.
		// ),.
		// );.
		//
		// return $autofill_settings;.
		*/
	}

	/**
	 * Field front-end markup
	 *
	 * @since 1.0
	 *
	 * @param array                  $field Field.
	 * @param Forminator_Render_Form $views_obj Forminator_Render_Form object.
	 * @param array                  $draft_value Draft value.
	 *
	 * @return mixed
	 */
	public function markup( $field, $views_obj, $draft_value = null ) {

		$settings           = $views_obj->model->settings;
		$this->field        = $field;
		$this->draft_values = ! empty( $draft_value['value'] ) ? $draft_value['value'] : array();

		self::$description_position = self::get_description_position( $field, $settings );

		$html     = '';
		$required = self::get_property( 'required', $field, false );
		$id       = self::get_property( 'element_id', $field );
		$name     = $id;
		$design   = $this->get_form_style( $settings );
		$ajax     = ! empty( $settings['use_ajax_load'] );

		$html .= $this->get_post_title( $id, $name, $field, $required, $design );
		$html .= $this->get_post_content( $id, $name, $field, $required, $ajax );
		$html .= $this->get_post_excerpt( $id, $name, $field, $required, $design );
		$html .= $this->get_post_image( $id, $name, $field, $required, $design );
		$html .= $this->get_post_categories( $id, $name, $field, $required );
		$html .= $this->render_custom_fields( $id, $name, $field, $required );

		return apply_filters( 'forminator_field_postdata_markup', $html, $field, $required, $id, $this );
	}

	/**
	 * Return post title
	 *
	 * @since 1.0
	 *
	 * @param int    $id Id.
	 * @param string $name Name.
	 * @param array  $field Field.
	 * @param bool   $required Required.
	 * @param string $design Design.
	 *
	 * @return string
	 */
	public function get_post_title( $id, $name, $field, $required, $design ) {

		return apply_filters(
			'forminator_field_postdata_post_title',
			$this->_get_post_field(
				$id,
				$name,
				$field,
				$required,
				'post_title',
				'text',
				'forminator-input',
				'post-title',
				array(),
				'',
				$design
			)
		);
	}

	/**
	 * Return post content
	 *
	 * @since 1.0
	 *
	 * @param int    $id Id.
	 * @param string $name Name.
	 * @param array  $field Field.
	 * @param bool   $required Required.
	 * @param bool   $ajax Ajax.
	 *
	 * @return string
	 */
	public function get_post_content( $id, $name, $field, $required, $ajax ) {
		return apply_filters( 'forminator_field_postdata_post_content', $this->_get_post_field( $id, $name, $field, $required, 'post_content', 'wp_editor', 'forminator-textarea', 'post-content', array( 'ajax' => $ajax ) ) );
	}

	/**
	 * Return post excerpt
	 *
	 * @since 1.0
	 *
	 * @param int    $id Id.
	 * @param string $name Name.
	 * @param array  $field Field.
	 * @param bool   $required Required.
	 * @param string $design Design.
	 *
	 * @return string
	 */
	public function get_post_excerpt( $id, $name, $field, $required, $design ) {

		return apply_filters(
			'forminator_field_postdata_post_excerpt',
			$this->_get_post_field(
				$id,
				$name,
				$field,
				$required,
				'post_excerpt',
				'textarea',
				'forminator-textarea',
				'post-excerpt',
				array(),
				'',
				$design
			)
		);
	}

	/**
	 * Return post featured image
	 *
	 * @since 1.0
	 *
	 * @param int    $id Id.
	 * @param string $name Name.
	 * @param array  $field Field.
	 * @param bool   $required Required.
	 * @param string $design Design.
	 *
	 * @return string
	 */
	public function get_post_image( $id, $name, $field, $required, $design ) {

		return apply_filters(
			'forminator_field_postdata_post_image',
			$this->_get_post_field(
				$id,
				$name,
				$field,
				$required,
				'post_image',
				'file',
				'forminator-upload',
				'post-image',
				array(),
				'',
				$design
			)
		);
	}

	/**
	 * Return categories
	 *
	 * @since 1.0
	 *
	 * @param int    $id Id.
	 * @param string $name Name.
	 * @param array  $field Field.
	 * @param bool   $required Required.
	 *
	 * @return string
	 */
	public function get_post_categories( $id, $name, $field, $required ) {
		$html          = '';
		$post_type     = self::get_property( 'post_type', $field, 'post' );
		$category_list = forminator_post_categories( $post_type );

		if ( ! empty( $category_list ) ) {
			foreach ( $category_list as $category ) {
				$options    = array();
				$categories = get_categories(
					array(
						'orderby'    => 'name',
						'order'      => 'ASC',
						'hide_empty' => false,
						'taxonomy'   => $category['value'],
					)
				);

				$categories = apply_filters( 'forminator_field_postdata_' . $category['value'] . '_list', $categories );

				foreach ( $categories as $cat ) {
					$options[] = array(
						'value' => $cat->term_id,
						'label' => $cat->name,
					);
				}

				$value          = '';
				$design         = '';
				$multiple       = self::get_property( $category['value'] . '_multiple', $field, false );
				$allow_multiple = $multiple ? 'multiple' : '';
				$select_type    = $multiple ? 'multiselect' : 'select';

				$html .= apply_filters(
					'forminator_field_postdata_' . $category['value'],
					$this->_get_post_field( $id, $name, $field, $required, $category['value'], $select_type, 'forminator-select2', $category['value'], $options, $value, $design, $allow_multiple )
				);
			}
		}
		return $html;
	}

	/**
	 * Return post field
	 *
	 * @since 1.0
	 *
	 * @param int    $id Id.
	 * @param string $name Name.
	 * @param array  $field Field.
	 * @param bool   $required Required.
	 * @param string $field_name Field name.
	 * @param string $type Field type.
	 * @param string $class_name Class name.
	 * @param string $input_suffix Input suffix.
	 * @param array  $options Options.
	 * @param string $value Field value.
	 * @param string $design Design.
	 * @param string $multiple Multiple.
	 *
	 * @return string
	 */
	public function _get_post_field( $id, $name, $field, $required, $field_name, $type, $class_name, $input_suffix, $options = array(), $value = '', $design = '', $multiple = '' ) { // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore

		$html          = '';
		$field_enabled = self::get_property( $field_name, $field, '' );
		$type          = trim( $type );
		$full_id       = self::get_field_id( $input_suffix . '-' . $id );
		$is_preview    = filter_input( INPUT_POST, 'is_preview', FILTER_VALIDATE_BOOLEAN );
		$draft_value   = isset( $this->draft_values[ $input_suffix ] ) ? $this->draft_values[ $input_suffix ] : '';

		if ( ! empty( $field_enabled ) ) {
			$cols         = 12;
			$placeholder  = esc_html( self::get_property( $field_name . '_placeholder', $field ) );
			$label        = esc_html( self::get_property( $field_name . '_label', $field ) );
			$description  = esc_html( self::get_property( $field_name . '_description', $field ) );
			$field_markup = array(
				'type'        => $type,
				'name'        => $id . '-' . $input_suffix,
				'placeholder' => $placeholder,
				'id'          => $full_id,
				'class'       => $class_name,
			);

			if ( $required ) {
				$field_markup['required'] = $required;
			}

			if ( ! empty( $multiple ) ) {
				$field_markup['multiple'] = $multiple;
				$field_markup['name']     = $field_markup['name'] . '[]';
			}

			$html .= '<div class="forminator-row">';

				$html .= sprintf( '<div class="forminator-col forminator-col-%s">', $cols );

					$html .= '<div class="forminator-field">';

			$ajax = ! empty( $options['ajax'] );
			if ( 'wp_editor' === $type ) {
				// multiple wp_editor support.
				$field_markup['id'] = $field_markup['id'];
			}

			if ( 'wp_editor' === $type && ! $is_preview && ! $ajax ) {

				if ( ! empty( $draft_value ) ) {
					$field_markup['content'] = $draft_value;
				}

				$html .= self::create_wp_editor(
					$field_markup,
					$label,
					$description,
					$required
				);
			} elseif ( ( 'textarea' === $type || 'wp_editor' === $type ) && ( $ajax || $is_preview ) ) {

				if ( ! empty( $draft_value ) ) {
					$field_markup['content'] = $draft_value;
				}

				$html .= self::create_textarea(
					$field_markup,
					$label,
					$description,
					$required,
					self::$description_position,
				);

				if ( 'wp_editor' === $type ) {
					$_id   = $field_markup['id'];
					$args  = self::get_tinymce_args( $_id );
					$html .= '<script>wp.editor.initialize("' . esc_attr( $_id ) . '", ' . $args . ');</script>';
				}
			} elseif ( 'select' === $type ) {

				if ( empty( $options ) ) {
					unset( $field_markup['required'] );
				}

				if ( ! empty( $draft_value ) ) {
					// Users might switch from multi select.
					$value = is_array( $draft_value ) ? $draft_value[0] : $draft_value;
				}

				$html .= self::create_select(
					$field_markup,
					$label,
					$options,
					$value,
					$description,
					$required,
					self::$description_position,
				);
			} elseif ( 'multiselect' === $type ) {
				$html .= self::get_field_label( $label, $id . '-field', $required );

				if ( ! empty( $draft_value ) ) {
					// Users might switch from single select.
					$post_value = is_array( $draft_value ) ? $draft_value : array( $draft_value );
				} else {
					$post_value = self::get_post_data( $name, self::FIELD_PROPERTY_VALUE_NOT_EXIST );
				}

				$name   = $id . '-' . $field_name . '[]';
				$get_id = $id . '-' . $field_name;
				$i      = 1;

				if ( 'above' === self::$description_position ) {
					$html .= self::get_description( $description, $get_id, self::$description_position );
				}
				$html .= '<div class="forminator-multiselect">';

				foreach ( $options as $option ) {

					$value    = $option['value'] ? $option['value'] : $option['label'];
					$input_id = $id . '-' . $i . '-' . $field_name;

					$selected = false;

					if ( self::FIELD_PROPERTY_VALUE_NOT_EXIST !== $post_value || ! empty( $draft_value ) ) {
						if ( is_array( $post_value ) ) {
							$selected = in_array( $value, $post_value ); // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
						}
					}

					if ( $selected ) {
						$selected       = esc_attr( 'checked="checked"' );
						$selected_class = esc_attr( 'forminator-option forminator-is_checked' );
					} else {
						$selected       = '';
						$selected_class = esc_attr( 'forminator-option' );
					}

					$html .= sprintf( '<label for="%s" class="%s">', $input_id, $selected_class );

					$html .= sprintf(
						'<input type="checkbox" name="%s" value="%s" id="%s" %s />',
						$name,
						$value,
						$input_id,
						$selected
					);

					$html .= $option['label'];

					$html .= '</label>';

					++$i;
				}

				$html .= '</div>';

				if ( 'above' !== self::$description_position ) {
					$html .= self::get_description( $description, $get_id, self::$description_position );
				}
			} elseif ( 'file' === $type ) {

				$label_id = $full_id;

				$html .= self::get_field_label( $label, $label_id, $required );

				$html .= self::create_file_upload(
					$input_suffix . '-' . $id . '_' . Forminator_CForm_Front::$uid,
					$name . '-' . $input_suffix,
					$description,
					$required,
					$design,
					'single',
					0,
					array(
						'accept' => '.' . implode( ',.', $this->image_extensions ),
					)
				);
			} else {
				if ( ! empty( $draft_value ) ) {
					$field_markup['value'] = $draft_value;
				}

				$html .= self::create_input(
					$field_markup,
					$label,
					$description,
					$required,
					self::$description_position,
				);
			}

					$html .= '</div>';

				$html .= '</div>';

			$html .= '</div>';

		}

		return $html;
	}

	/**
	 * Render custom fields
	 *
	 * @since 1.0
	 *
	 * @param int    $id Id.
	 * @param string $name Name.
	 * @param array  $field Field.
	 * @param bool   $required Required.
	 *
	 * @return string
	 */
	private function render_custom_fields( $id, $name, $field, $required ) {
		$html              = '';
		$cols              = 12;
		$has_custom_fields = self::get_property( 'post_custom', $field, false );

		if ( $has_custom_fields ) {
			$custom_vars = self::get_property( 'options', $field );

			if ( ! empty( $custom_vars ) ) {
				$html .= '<div class="forminator-row forminator-row--inner">';
				$i     = 1;
				foreach ( $custom_vars as $variable ) {
					$html .= sprintf( '<div class="forminator-col forminator-col-%s">', $cols );
					$value = '';
					if ( ! empty( $variable['value'] ) ) {
						$value = $variable['value'];
					}
					$input_id     = $id . '-post_meta-' . $i;
					$label        = $variable['label'];
					$field_markup = array(
						'type'        => 'text',
						'class'       => 'forminator-input',
						'name'        => $input_id,
						'id'          => $input_id,
						'placeholder' => $label,
						'value'       => $value,
					);

					$html .= self::create_input( $field_markup, $label, '' );
					$html .= '</div>';
					++$i;
				}
			}

			$html .= '</div>';
		}

		return $html;
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

		$post_title               = self::get_property( 'post_title', $field, '' );
		$post_content             = self::get_property( 'post_content', $field, '' );
		$post_excerpt             = self::get_property( 'post_excerpt', $field, '' );
		$setting_required_message = self::get_property( 'required_message', $field, '' );
		$post_type                = self::get_property( 'post_type', $field, 'post' );
		$post_image               = self::get_property( 'post_image', $field, '' );

		$title         = isset( $data['post-title'] ) ? $data['post-title'] : '';
		$content       = isset( $data['post-content'] ) ? $data['post-content'] : '';
		$excerpt       = isset( $data['post-excerpt'] ) ? $data['post-excerpt'] : '';
		$image         = isset( $data['post-image'] ) ? $data['post-image'] : '';
		$category_list = forminator_post_categories( $post_type );

		if ( $this->is_required( $field ) ) {
			if ( empty( $data ) ) {
				$postdata_validation_message     = apply_filters(
					'forminator_postdata_field_validation_message',
					( ! empty( $setting_required_message ) ? $setting_required_message : esc_html__( 'This field is required. Please fill in post data.', 'forminator' ) ),
					$id
				);
				$this->validation_message[ $id ] = $postdata_validation_message;
			} elseif ( is_array( $data ) ) {

				if ( ! empty( $post_title ) && empty( $title ) ) {

					$postdata_post_title_validation_message          = apply_filters(
						'forminator_postdata_field_post_title_validation_message',
						( ! empty( $setting_required_message ) ? $setting_required_message : esc_html( self::$default_required_messages[ $this->type ] ) ),
						$id
					);
					$this->validation_message[ $id . '-post-title' ] = $postdata_post_title_validation_message;
				}
				if ( ! empty( $post_content ) && empty( $content ) ) {
					$postdata_post_content_validation_message          = apply_filters(
						'forminator_postdata_field_post_content_validation_message',
						( ! empty( $setting_required_message ) ? $setting_required_message : esc_html__( 'This field is required. Please enter the post content.', 'forminator' ) ),
						$id
					);
					$this->validation_message[ $id . '-post-content' ] = $postdata_post_content_validation_message;
				}
				if ( ! empty( $post_excerpt ) && empty( $excerpt ) ) {
					$postdata_post_excerpt_validation_message          = apply_filters(
						'forminator_postdata_field_post_excerpt_validation_message',
						( ! empty( $setting_required_message ) ? $setting_required_message : esc_html__( 'This field is required. Please enter the post excerpt.', 'forminator' ) ),
						$id
					);
					$this->validation_message[ $id . '-post-excerpt' ] = $postdata_post_excerpt_validation_message;
				}
				if ( ! empty( $post_image ) && empty( $image ) ) {
					$postdata_post_image_validation_message          = apply_filters(
						'forminator_postdata_field_post_image_validation_message',
						( ! empty( $setting_required_message ) ? $setting_required_message : esc_html__( 'This field is required. Please upload a post image.', 'forminator' ) ),
						$id
					);
					$this->validation_message[ $id . '-post-image' ] = $postdata_post_image_validation_message;
				}
				if ( ! empty( $category_list ) ) {
					foreach ( $category_list as $cat ) {
						$post_category = self::get_property( $cat['value'], $field, '' );
						$category      = isset( $data[ $cat['value'] ] ) ? $data[ $cat['value'] ] : '';
						if ( ! empty( $post_category ) && empty( $category ) ) {
							$postdata_post_category_validation_message             = apply_filters(
								'forminator_postdata_field_' . $cat['value'] . '_validation_message',
								( ! empty( $setting_required_message ) ? $setting_required_message : sprintf( /* translators: %s: Category Label */ esc_html__( 'This field is required. Please select a %s.', 'forminator' ), $cat['label'] ) ),
								$id
							);
							$this->validation_message[ $id . '-' . $cat['value'] ] = $postdata_post_category_validation_message;
						}
					}
				}
			}
		} else {
			// validation for postdata when its not required.
			// `wp_insert_post` required at least ONE OF THESE to be available title / content / excerpt.
			// check only when user send some data.
			if ( ! empty( $data ) && is_array( $data ) ) {
				if ( ! $title && ! $content && ! $excerpt ) {
					// check if there is any field with content.
					$is_content_available = false;
					foreach ( $data as $datum ) {
						if ( ! empty( $datum ) ) {
							$is_content_available = true;
							break;
						}
					}

					// when $is_content_available false means, field not required, and user didnt put any content on form.
					if ( $is_content_available ) {
						// check if on postdata these sub field is avail available.
						if ( ! empty( $post_title ) ) {
							$this->validation_message[ $id . '-post-title' ] = apply_filters(
								// nr = not required.
								'forminator_postdata_field_post_title_nr_validation_message',
								esc_html__( 'At least one of these fields is required: Post Title, Post Excerpt or Post Content.', 'forminator' ),
								$id
							);
						}
						if ( ! empty( $post_content ) ) {
							$this->validation_message[ $id . '-post-content' ] = apply_filters(
								// nr = not required.
								'forminator_postdata_field_post_content_nr_validation_message',
								esc_html__( 'At least one of these fields is required: Post Title, Post Excerpt or Post Content.', 'forminator' ),
								$id
							);
						}
						if ( ! empty( $post_excerpt ) ) {
							$this->validation_message[ $id . '-post-excerpt' ] = apply_filters(
								// nr = not required.
								'forminator_postdata_field_post_excerpt_nr_validation_message',
								esc_html__( 'At least one of these fields is required: Post Title, Post Excerpt or Post Content.', 'forminator' ),
								$id
							);
						}
					}
				}
			}
			$image_field_name = $id . '-post-image';
			if ( ! empty( $post_image ) && isset( $_FILES[ $image_field_name ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				if ( isset( $_FILES[ $image_field_name ]['name'] ) && ! empty( $_FILES[ $image_field_name ]['name'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
					$file_name = sanitize_file_name( $_FILES[ $image_field_name ]['name'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
					$valid     = wp_check_filetype( $file_name );

					if ( false === $valid['ext'] || ! in_array( $valid['ext'], $this->image_extensions, true ) ) {
						$this->validation_message[ $image_field_name ] = apply_filters(
							'forminator_postdata_field_post_image_nr_validation_message',
							esc_html__( 'Uploaded file\'s extension is not allowed.', 'forminator' ),
							$id
						);
					}
				}
			}
		}
	}

	/**
	 * Upload post image
	 *
	 * @since 1.0
	 *
	 * @param array  $field      - the field.
	 * @param string $field_name - the field name.
	 *
	 * @return array|bool - if success, return an array
	 */
	public function upload_post_image( $field, $field_name ) {
		$post_image = self::get_property( 'post_image', $field, '' );

		if ( empty( $post_image ) ) {
			return true;
		}
		if ( ! empty( $_FILES[ $field_name ]['name'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$file_name = sanitize_file_name( $_FILES[ $field_name ]['name'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

			if ( ! function_exists( 'wp_filesystem' ) ) {
				require_once ABSPATH . 'wp-admin/includes/file.php';
			}

			global $wp_filesystem;
			if ( ! WP_Filesystem() ) {
				// Could not initialize the filesystem.
				return array(
					'attachment_id' => 0,
					'uploaded_file' => 0,
				);
			}

			$file_data = $wp_filesystem->get_contents( $_FILES[ $field_name ]['tmp_name'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput

			$upload_dir       = wp_upload_dir(); // Set upload folder.
			$unique_file_name = wp_unique_filename( $upload_dir['path'], $file_name );
			$filename         = basename( $unique_file_name ); // Create base file name.

			if ( wp_mkdir_p( $upload_dir['path'] ) ) {
				$file = $upload_dir['path'] . '/' . $filename;
			} else {
				$file = $upload_dir['basedir'] . '/' . $filename;
			}

			// Check image file type.
			$wp_filetype = wp_check_filetype( $filename, null );
			$image_exts  = apply_filters( 'forminator_field_postdata_image_file_types', $this->image_extensions );
			if ( in_array( (string) $wp_filetype['ext'], $image_exts, true ) ) {

				// Create the file on the server.
				if ( $wp_filesystem->put_contents( $file, $file_data, FS_CHMOD_FILE ) ) {
					// Set attachment data.
					$attachment = array(
						'post_mime_type' => $wp_filetype['type'],
						'post_title'     => sanitize_file_name( $filename ),
						'post_content'   => '',
						'post_status'    => 'inherit',
					);

					// Create the attachment.
					$attachment_id = wp_insert_attachment( $attachment, $file );
					self::generate_upload_metadata( $attachment_id, $file );

					$uploaded_file = wp_get_attachment_image_src( $attachment_id, 'large', false );
					if ( $uploaded_file && is_array( $uploaded_file ) ) {
						return array(
							'attachment_id' => $attachment_id,
							'uploaded_file' => $uploaded_file,
						);
					}
				}
			}
		}

		return array(
			'attachment_id' => 0,
			'uploaded_file' => 0,
		);
	}

	/**
	 * Save post
	 *
	 * @since 1.0
	 *
	 * @param array $field - field array.
	 * @param array $data  - post data.
	 *
	 * @return bool|int - success is post id
	 */
	public function save_post( $field, $data ) {
		$post_type            = self::get_property( 'post_type', $field, 'post' );
		$force_default_author = self::get_property( 'default_author', $field, false );
		$force_default_author = filter_var( $force_default_author, FILTER_VALIDATE_BOOLEAN );

		// default behavior.
		if ( is_user_logged_in() ) {
			$post_author = get_current_user_id();
		} else {
			$post_author = self::get_property( 'select_author', $field, 1 );
			if ( empty( $post_author ) || ! get_user_by( 'ID', $post_author ) ) {
				$post_author = $this->set_anonymous_author();
			}
		}

		// force to selected author.
		if ( $force_default_author ) {
			$post_author = self::get_property( 'select_author', $field, 1 );
		}

		$post_status = self::get_property( 'data_status', $field, 'draft' );
		$title       = isset( $data['post-title'] ) ? $data['post-title'] : '';
		$content     = isset( $data['post-content'] ) ? $data['post-content'] : '';
		$excerpt     = isset( $data['post-excerpt'] ) ? $data['post-excerpt'] : '';
		$image       = isset( $data['post-image'] ) ? $data['post-image'] : '';
		$post_meta   = isset( $data['post-custom'] ) ? $data['post-custom'] : '';

		$post = array(
			'post_author'  => $post_author,
			'post_content' => wp_kses_post( $content ),
			'post_excerpt' => $excerpt,
			'post_name'    => sanitize_text_field( $title ),
			'post_status'  => $post_status,
			'post_title'   => $title,
			'post_type'    => $post_type,
		);

		$category_list = forminator_post_categories( $post_type );
		if ( ! empty( $category_list ) ) {
			$taxonomy = array();
			foreach ( $category_list as $cat ) {
				$cat_value = $cat['value'];
				$category  = isset( $data[ $cat_value ] ) ? $data[ $cat_value ] : '';
				if ( ! empty( $category ) ) {
					if ( is_array( $category ) ) {
						$taxonomy[ $cat_value ] = array_map( 'intval', $category );
					} else {
						$taxonomy[ $cat_value ] = array( intval( $category ) );
					}
				}
			}
			$post['tax_input'] = $taxonomy;
		}

		$post = apply_filters( 'forminator_post_data_post_info', $post, $field, $data );

		// trigger wp_error for is_wp_error to be correctly identified.
		$post_id = wp_insert_post( $post, true );
		if ( ! is_wp_error( $post_id ) ) {
			$category_list = forminator_post_categories( $post_type );
			if ( ! empty( $category_list ) ) {
				foreach ( $category_list as $cat ) {
					$cat_value = $cat['value'];
					$category  = isset( $data[ $cat_value ] ) ? $data[ $cat_value ] : '';
					if ( ! empty( $category ) ) {
						if ( is_array( $category ) ) {
							$taxonomy_tags = array_map( 'intval', $category );
						} else {
							$taxonomy_tags = array( intval( $category ) );
						}
						wp_set_post_terms( $post_id, $taxonomy_tags, $cat_value );
					}
				}
			}
			$post_image = self::get_property( 'post_image', $field, '' );
			if ( ! empty( $post_image ) && ! empty( $image ) && is_array( $image ) ) {
				set_post_thumbnail( $post_id, $image['attachment_id'] );
			}

			if ( ! empty( $post_meta ) ) {
				foreach ( $post_meta as $meta ) {
					add_post_meta( $post_id, $meta['key'], $meta['value'] );
				}
				add_post_meta( $post_id, '_has_forminator_meta', true );
			}

			do_action( 'forminator_post_data_field_post_saved', $post_id, $field, $data, $this );

			return $post_id;
		}

		return false;
	}

	/**
	 * Set anonymous author
	 *
	 * @return int|mixed|WP_Error
	 */
	private function set_anonymous_author() {
		$user = get_user_by( 'login', 'anonymous_user' );
		if ( $user ) {
			return $user->ID;
		} else {
			$userdata = array(
				'user_login'    => 'anonymous_user',
				// Set different user_nicename and display_name for security.
				'user_nicename' => 'anonymous',
				'display_name'  => 'Anonymous',
				'role'          => 'author',
				'user_pass'     => null,
			);
			$new_user = wp_insert_user( $userdata );
			if ( ! is_wp_error( $new_user ) ) {
				return $new_user;
			}

			return 1;
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
		$image         = '';
		$content       = '';

		// Do not sanitize image URL.
		if ( isset( $data['post-image'] ) ) {
			$image = $data['post-image'];
		}

		// Do not sanitize post content.
		if ( isset( $data['post-content'] ) ) {
			$content = wp_kses_post( $data['post-content'] );
		}

		// Sanitize.
		$data = forminator_sanitize_array_field( $data );

		// Return image url original value.
		if ( isset( $data['post-image'] ) ) {
			$data['post-image'] = $image;
		}

		// Return post content original value.
		if ( isset( $data['post-content'] ) ) {
			$data['post-content'] = $content;
		}

		return apply_filters( 'forminator_field_postdata_sanitize', $data, $field, $original_data );
	}

	/**
	 * Return field inline validation rules
	 * Workaround for actually input file is hidden, so its not accessible via standar html5 `required` attribute
	 *
	 * @since 1.1
	 * @return string
	 */
	public function get_validation_rules() {
		$field              = $this->field;
		$id                 = self::get_property( 'element_id', $field );
		$is_required        = $this->is_required( $field );
		$post_image         = self::get_property( 'post_image', $field, '' );
		$post_type          = self::get_property( 'post_type', $field, 'post' );
		$post_image_enabled = ! empty( $post_image );
		$rules              = '';

		if ( $post_image_enabled ) {
			$rules .= '"' . $this->get_id( $field ) . '-post-image": {';
			if ( $is_required ) {
				$rules .= '"required": true,';
			}
			$rules .= '},';
		}
		$category_list = forminator_post_categories( $post_type );
		if ( ! empty( $category_list ) ) {
			foreach ( $category_list as $category ) {
				$post_category_enabled = self::get_property( $category['value'], $field, '' );
				if ( $is_required && $post_category_enabled ) {
					$rules .= '"' . $this->get_id( $field ) . '-' . $category['value'] . '[]": {';
					if ( $is_required ) {
						$rules .= '"required": true,';
					}
					$rules .= '},';
				}
			}
		}

		return apply_filters( 'forminator_field_postdata_validation_rules', $rules, $id, $field );
	}

	/**
	 * Return field inline validation messages
	 *
	 * @since 1.1
	 * @return string
	 */
	public function get_validation_messages() {
		$field       = $this->field;
		$id          = $this->get_id( $field );
		$is_required = $this->is_required( $field );
		$messages    = '';

		$post_title               = self::get_property( 'post_title', $field, '' );
		$post_content             = self::get_property( 'post_content', $field, '' );
		$post_excerpt             = self::get_property( 'post_excerpt', $field, '' );
		$post_image               = self::get_property( 'post_image', $field, '' );
		$setting_required_message = self::get_property( 'required_message', $field, '' );
		$post_type                = self::get_property( 'post_type', $field, 'post' );

		$post_title_enabled   = ! empty( $post_title );
		$post_content_enabled = ! empty( $post_content );
		$post_excerpt_enabled = ! empty( $post_excerpt );
		$post_image_enabled   = ! empty( $post_image );
		$category_list        = forminator_post_categories( $post_type );

		if ( $is_required ) {
			if ( $post_title_enabled ) {
				$messages .= '"' . $id . '-post-title": {' . "\n";

				$required_message = apply_filters(
					'forminator_postdata_field_post_title_validation_message',
					( ! empty( $setting_required_message ) ? $setting_required_message : self::$default_required_messages[ $this->type ] ),
					$id,
					$field
				);
				$messages         = $messages . '"required": "' . forminator_addcslashes( $required_message ) . '",' . "\n";

				$messages .= '},' . "\n";
			}
			if ( $post_content_enabled ) {
				$messages .= '"' . $id . '-post-content": {' . "\n";

				$required_message = apply_filters(
					'forminator_postdata_field_post_content_validation_message',
					( ! empty( $setting_required_message ) ? $setting_required_message : esc_html__( 'This field is required. Please enter the post content.', 'forminator' ) ),
					$id,
					$field
				);
				$messages         = $messages . '"required": "' . forminator_addcslashes( $required_message ) . '",' . "\n";

				$messages .= '},' . "\n";
			}
			if ( $post_excerpt_enabled ) {
				$messages .= '"' . $id . '-post-excerpt": {' . "\n";

				$required_message = apply_filters(
					'forminator_postdata_field_post_excerpt_validation_message',
					( ! empty( $setting_required_message ) ? $setting_required_message : esc_html__( 'This field is required. Please enter the post excerpt.', 'forminator' ) ),
					$id,
					$field
				);
				$messages         = $messages . '"required": "' . forminator_addcslashes( $required_message ) . '",' . "\n";

				$messages .= '},' . "\n";
			}
			if ( $post_image_enabled ) {
				$messages .= '"' . $id . '-post-image": {' . "\n";

				$required_message = apply_filters(
					'forminator_postdata_field_post_image_validation_message',
					( ! empty( $setting_required_message ) ? $setting_required_message : esc_html__( 'This field is required. Please upload a post image.', 'forminator' ) ),
					$id,
					$field
				);
				$messages         = $messages . '"required": "' . forminator_addcslashes( $required_message ) . '",' . "\n";

				$messages .= '},' . "\n";
			}
			if ( ! empty( $category_list ) ) {
				foreach ( $category_list as $category ) {
					$post_category_enabled = self::get_property( $category['value'], $field, '' );
					if ( $post_category_enabled ) {
						$post_category_multiple = self::get_property( $category['value'] . '_multiple', $field, '' );
						if ( $post_category_multiple ) {
							$messages .= '"' . $id . '-' . $category['value'] . '[]": {' . "\n";
						} else {
							$messages .= '"' . $id . '-' . $category['value'] . '": {' . "\n";
						}

						$required_message = apply_filters(
							'forminator_postdata_field_' . $category['value'] . '_validation_message',
							( ! empty( $setting_required_message ) ? $setting_required_message : sprintf( /* translators: %s: Category singular */ esc_html__( 'This field is required. Please select a %s.', 'forminator' ), $category['singular'] ) ),
							$id,
							$field
						);
						$messages         = $messages . '"required": "' . forminator_addcslashes( $required_message ) . '",' . "\n";

						$messages .= '},' . "\n";
					}
				}
			}
		}
		if ( $post_image_enabled ) {
			$messages .= '"' . $id . '-post-image": {' . "\n";
			$messages .= '"extension": "' . forminator_addcslashes( esc_html__( 'Uploaded file\'s extension is not allowed.', 'forminator' ) ) . '",' . "\n";
			$messages .= '},' . "\n";
		}

		return $messages;
	}
}
