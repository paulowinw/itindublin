<?php
/**
 * Forminator API
 *
 * @package Forminator
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * API methods class
 *
 * @since  1.2
 * @access public
 */
class Forminator_API {

	const MAX_CUSTOM_FORM_FIELDS_PER_WRAPPER = 4;

	/**
	 * Initialize
	 *
	 * @param bool $admin_files Admin files?.
	 * @return void
	 */
	public static function initialize( $admin_files = false ) {
		// If models are not initialized, init the plugin.
		if ( ! class_exists( 'Forminator_Form_Model' ) ) {
			/* @noinspection PhpUnusedLocalVariableInspection */
			$forminator = forminator();
		}
		if ( $admin_files ) {
			include_once dirname( __DIR__ ) . '/admin/abstracts/class-admin-module.php';
			include_once __DIR__ . '/modules/custom-forms/admin/admin-loader.php';
			include_once __DIR__ . '/modules/polls/admin/admin-loader.php';
			include_once __DIR__ . '/modules/quizzes/admin/admin-loader.php';
		}
	}

	/**
	 * Returns all form objects.
	 *
	 * @since      1.2.0
	 * @since      1.5.4 add pagination arguments
	 * @since      1.6 add status arguments
	 * @access     public
	 *
	 * @param null|array $form_ids Form Id.
	 * @param int        $page Page number.
	 * @param int        $per_page Limit per page.
	 * @param string     $status (draft,publish,any).
	 * @param null|int   $pdf_parent_id PDF parent Id.
	 *
	 * @return Forminator_Form_Model[]|WP_Error
	 */
	public static function get_forms( $form_ids = null, $page = 1, $per_page = 10, $status = '', $pdf_parent_id = null ) {
		// Initialize API.
		self::initialize();

		$temp = array();

		if ( is_null( $form_ids ) ) {
			$temp = Forminator_Form_Model::model()->get_all_paged( $page, $per_page, $status, $pdf_parent_id );
			if ( isset( $temp['models'] ) && is_array( $temp['models'] ) ) {
				return $temp['models'];
			}

			return array();
		} else {
			if ( ! is_array( $form_ids ) ) {
				return new WP_Error( 'invalid_arg', esc_html__( 'Invalid Arguments', 'forminator' ) );
			}

			if ( empty( $status ) ) {
				$status = 'publish';
			}

			foreach ( $form_ids as $form_id ) {
				$model = self::get_module( $form_id );
				if ( ! ( $model instanceof WP_Error )
						&& ( ( is_array( $status ) && in_array( $model->status, $status, true ) ) || $status === $model->status ) ) {
					$temp[] = $model;
				}
			}
		}

		return $temp;
	}

	/**
	 * Returns form object by given ID.
	 *
	 * @since  1.2
	 * @access public
	 *
	 * @param  int $form_id ID of the form.
	 *
	 * @return Forminator_Form_Model|WP_Error Custom Form Model on success or WP_Error otherwise
	 */
	public static function get_form( $form_id ) {
		return self::get_module( $form_id );
	}

	/**
	 * Returns module object by given ID.
	 *
	 * @since  1.2
	 * @access public
	 *
	 * @param  int $module_id ID of the module.
	 *
	 * @return Forminator_Base_Form_Model|WP_Error Module Model on success or WP_Error otherwise
	 */
	public static function get_module( $module_id ) {
		// Initialize API.
		self::initialize();

		if ( empty( $module_id ) ) {
			return new WP_Error( 'missing_id', esc_html__( 'Module ID is required!', 'forminator' ) );
		}

		$model = Forminator_Base_Form_Model::get_model( $module_id );
		if ( ! $model instanceof Forminator_Base_Form_Model ) {
			return new WP_Error( 'module_not_found', esc_html__( 'Module not found!', 'forminator' ) );
		}

		return $model;
	}

	/**
	 * Delete form with given ID
	 *
	 * @since  1.2
	 * @access public
	 *
	 * @param int $form_id ID of the form.
	 */
	public static function delete_form( $form_id ) {
		self::delete_module( $form_id );
	}

	/**
	 * Delete module with given ID
	 *
	 * @since  1.14.11
	 * @access public
	 *
	 * @param int $module_id Module id.
	 */
	public static function delete_module( $module_id ) {
		// Initialize API.
		self::initialize();

		Forminator_Admin_Module_Edit_Page::delete_module( $module_id );
	}

	/**
	 * Delete forms with given IDs
	 *
	 * @since        1.2
	 * @access       public
	 *
	 * @param  array $form_ids Array of Form IDs.
	 */
	public static function delete_forms( $form_ids ) {
		self::delete_modules( $form_ids );
	}

	/**
	 * Delete modules with given IDs
	 *
	 * @since        1.14.11
	 * @access       public
	 *
	 * @param  array $module_ids Array of Module IDs.
	 */
	public static function delete_modules( $module_ids ) {
		if ( ! is_array( $module_ids ) ) {
			$module_ids = func_get_args();
		}

		$module_ids = array_map( 'trim', $module_ids );

		if ( is_array( $module_ids ) && ! empty( $module_ids ) ) {
			foreach ( $module_ids as $id ) {
				self::delete_module( $id );
			}

			return true;
		} else {
			return new WP_Error( 'invalid', esc_html__( 'Invalid or empty array with IDs', 'forminator' ) );
		}
	}

	/**
	 * Add form
	 *
	 * @since  1.2
	 * @since  1.6 add `status` on param
	 * @access public
	 *
	 * @param string $name     Form name.
	 * @param array  $wrappers Array with form fields.
	 * @param array  $settings Array with form settings.
	 * @param string $status   status of form `draft` or `publish`.
	 *
	 * @return int|WP_Error ID of new form, or WP_Error on failure
	 */
	public static function add_form( $name, $wrappers = array(), $settings = array(), $status = Forminator_Form_Model::STATUS_PUBLISH ) {
		// Initialize API.
		self::initialize( true );

		if ( empty( $name ) ) {
			return new WP_Error( 'missing_name', esc_html__( 'Form name is required!', 'forminator' ) );
		}

		$template           = new stdClass();
		$template->fields   = $wrappers;
		$template->settings = $settings;

		$id = Forminator_Custom_Form_Admin::create( $name, $status, $template );

		if ( false === $id ) {
			return new WP_Error( 'form_save_error', esc_html__( 'There was a problem saving the form', 'forminator' ) );
		}

		return $id;
	}

	/**
	 * Update form
	 *
	 * @since  1.2
	 * @since  1.6 add status
	 * @access public
	 *
	 * @param int    $id       Form ID.
	 * @param array  $wrappers Array with form fields.
	 * @param array  $settings Array with form settings.
	 * @param string $status   status of form `draft`| `publish` | `` for keep as it is.
	 * @param array  $notifications Array with form notifications.
	 *
	 * @return int|WP_Error id of updated form, or WP_Error on failure
	 */
	public static function update_form( $id, $wrappers = array(), $settings = array(), $status = '', $notifications = array() ) {
		// Initialize API.
		self::initialize( true );

		if ( empty( $id ) ) {
			return new WP_Error( 'missing_id', esc_html__( 'Form ID is required!', 'forminator' ) );
		}

		// Set the post data.
		$title    = $settings['formName'];
		$template = new stdClass();

		$template->settings = $settings;
		$template->fields   = $wrappers;

		if ( isset( $notifications ) ) {
			$template->notifications = $notifications;
		}

		$id = Forminator_Custom_Form_Admin::update( $id, $title, $status, $template );

		if ( false === $id ) {
			return new WP_Error( 'form_save_error', esc_html__( 'There was a problem updating the form', 'forminator' ) );
		}

		return $id;
	}

	/**
	 * Returns all fields grouped by wrappers for specific form
	 *
	 * @since  1.5.0
	 * @access public
	 *
	 * @param int $id Form Id.
	 *
	 * @return array|WP_Error
	 */
	public static function get_form_wrappers( $id ) {
		// Initialize API.
		self::initialize();

		if ( empty( $id ) ) {
			return new WP_Error( 'missing_id', esc_html__( 'Form ID is required!', 'forminator' ) );
		}

		// Load form model.
		$model = Forminator_Base_Form_Model::get_model( $id );

		if ( ! is_object( $model ) ) {
			return new WP_Error( 'missing_object', esc_html__( 'Form model doesn\'t exist', 'forminator' ) );
		}

		$fields = $model->get_fields_grouped();

		if ( is_array( $fields ) && ! empty( $fields ) ) {
			return $fields;
		}

		return new WP_Error( 'missing_fields', esc_html__( 'Form has no fields', 'forminator' ) );
	}

	/**
	 * Returns specific wrapper by form_id and wrapper_id
	 *
	 * @since  1.5.0
	 * @access public
	 *
	 * @param int $form_id Form Id.
	 * @param int $id Wrapper Id.
	 *
	 * @return array|WP_Error
	 */
	public static function get_form_wrapper( $form_id, $id ) {
		// Initialize API.
		self::initialize();

		if ( empty( $form_id ) ) {
			return new WP_Error( 'missing_form_id', esc_html__( 'Form ID is required!', 'forminator' ) );
		}

		if ( empty( $id ) ) {
			return new WP_Error( 'missing_id', esc_html__( 'Wrapper ID is required!', 'forminator' ) );
		}

		// Load form model.
		$model = Forminator_Base_Form_Model::get_model( $form_id );

		if ( ! is_object( $model ) ) {
			return new WP_Error( 'missing_object', esc_html__( 'Form model doesn\'t exist', 'forminator' ) );
		}

		$wrapper = $model->get_wrapper( $id );

		if ( ! is_null( $wrapper ) ) {
			return $wrapper;
		}

		return new WP_Error( 'missing_field', esc_html__( 'Wrapper doesn\'t exist', 'forminator' ) );
	}

	/**
	 * Move form wrapper between position
	 *
	 * @since 1.5
	 *
	 * @param int    $form_id Form Id.
	 * @param string $id Wrapper Id.
	 * @param string $new_position negative value or greater than size of wrappers will move it to the end.
	 *                             if value passed is not `int` it will type cast-ed into `int`.
	 *                             Start from zero (0).
	 *
	 * @return array|WP_Error
	 */
	public static function move_form_wrapper( $form_id, $id, $new_position ) {

		$wrapper = self::get_form_wrapper( $form_id, $id );
		if ( is_wp_error( $wrapper ) ) {
			return $wrapper;
		}

		$wrappers = self::get_form_wrappers( $form_id );
		if ( is_wp_error( $wrappers ) ) {
			return $wrappers;
		}

		if ( ! is_int( $new_position ) ) {
			$new_position = (int) $new_position;
		}

		// negative value will move it to the end,.
		if ( $new_position < 0 || ( $new_position > count( $wrappers ) ) ) {
			$new_position = ( count( $wrappers ) - 1 );
		}

		$old_position = $wrapper['position'];
		// unchanged position.
		if ( $new_position === $old_position ) {
			return $wrapper;
		}

		// remove it, since its not needed for adding fields later.
		unset( $wrapper['position'] );

		$form_model = self::get_module( $form_id );
		if ( is_wp_error( $form_model ) ) {
			return $form_model;
		}

		unset( $wrappers[ $old_position ] );
		array_splice( $wrappers, $new_position, 0, array( $wrapper ) );

		// we need to empty fields cause we will send new data.
		$form_model->clear_fields();

		foreach ( $wrappers as $row ) {
			foreach ( $row['fields'] as $f ) {

				// re-set `id`.
				if ( ! isset( $f['id'] ) || $f['element_id'] !== $f['id'] ) {
					$f['id'] = $f['element_id'];
				}

				$field          = new Forminator_Form_Field_Model();
				$field->form_id = $row['wrapper_id'];
				$field->slug    = $f['id'];
				unset( $f['id'] );
				$field->parent_group = ! empty( $row['parent_group'] ) ? $row['parent_group'] : '';
				$field->import( $f );
				$form_model->add_field( $field );
			}
		}

		$form_id = $form_model->save();

		if ( is_wp_error( $form_id ) ) {
			return $form_id;
		}

		if ( false === $form_id ) {
			return new WP_Error( 'form_save_error', esc_html__( 'There was a problem moving form wrapper', 'forminator' ) );
		}

		return self::get_form_wrapper( $form_id, $id );
	}

	/**
	 * Delete wrapper with all fields inside
	 *
	 * @since  1.5.0
	 * @access public
	 *
	 * @param int    $form_id Form Id.
	 * @param string $id Wrapper Id.
	 *
	 * @return bool|WP_Error
	 */
	public static function delete_form_wrapper( $form_id, $id ) {
		// Initialize API.
		self::initialize();

		if ( empty( $form_id ) ) {
			return new WP_Error( 'missing_form_id', esc_html__( 'Form ID is required!', 'forminator' ) );
		}

		if ( empty( $id ) ) {
			return new WP_Error( 'missing_id', esc_html__( 'Wrapper ID is required!', 'forminator' ) );
		}

		// Load form model.
		$model = Forminator_Base_Form_Model::get_model( $form_id );

		if ( ! is_object( $model ) ) {
			return new WP_Error( 'missing_object', esc_html__( 'Form model doesn\'t exist', 'forminator' ) );
		}

		$wrapper = $model->get_wrapper( $id );

		if ( ! is_null( $wrapper ) ) {
			$fields = $wrapper['fields'];

			if ( ! empty( $fields ) ) {
				foreach ( $fields as $field ) {
					self::delete_form_field( $form_id, $field['element_id'] );
				}
			}

			return true;
		}

		return new WP_Error( 'missing_field', esc_html__( 'Wrapper doesn\'t exist', 'forminator' ) );
	}

	/**
	 * Returns all fields for specific form
	 *
	 * @since  1.5.0
	 * @access public
	 *
	 * @param int $id Form Id.
	 *
	 * @return array|WP_Error
	 */
	public static function get_form_fields( $id ) {
		// Initialize API.
		self::initialize();

		if ( empty( $id ) ) {
			return new WP_Error( 'missing_id', esc_html__( 'Form ID is required!', 'forminator' ) );
		}

		// Load form model.
		$model = Forminator_Base_Form_Model::get_model( $id );

		if ( ! is_object( $model ) ) {
			return new WP_Error( 'missing_object', esc_html__( 'Form model doesn\'t exist', 'forminator' ) );
		}

		$fields = $model->get_fields();

		if ( is_array( $fields ) && ! empty( $fields ) ) {
			return $fields;
		}

		return new WP_Error( 'missing_fields', esc_html__( 'Form has no fields', 'forminator' ) );
	}

	/**
	 * Returns all fields from specific type
	 *
	 * @since  1.5.0
	 * @access public
	 *
	 * @param int    $id Form Id.
	 * @param string $type Field type.
	 *
	 * @return array|WP_Error
	 */
	public static function get_form_fields_by_type( $id, $type ) {
		// Initialize API.
		self::initialize();

		if ( empty( $id ) ) {
			return new WP_Error( 'missing_id', esc_html__( 'Form ID is required!', 'forminator' ) );
		}

		// Load form model.
		$model = Forminator_Base_Form_Model::get_model( $id );

		if ( ! is_object( $model ) ) {
			return new WP_Error( 'missing_object', esc_html__( 'Form model doesn\'t exist', 'forminator' ) );
		}

		$fields = $model->get_fields_by_type( $type );

		if ( is_array( $fields ) && ! empty( $fields ) ) {
			return $fields;
		}

		return new WP_Error( 'missing_fields', esc_html__( 'No fields with that type', 'forminator' ) );
	}

	/**
	 * Returns specific field by form_id and field_id
	 *
	 * @since  1.5.0
	 * @access public
	 *
	 * @param int    $form_id Form id.
	 * @param string $id Wrapper Id.
	 * @param bool   $to_array whether to return as array or Forminator_Form_Field_Model.
	 *
	 * @return array|Forminator_Form_Field_Model|WP_Error
	 */
	public static function get_form_field( $form_id, $id, $to_array = true ) {
		// Initialize API.
		self::initialize();

		if ( empty( $form_id ) ) {
			return new WP_Error( 'missing_form_id', esc_html__( 'Form ID is required!', 'forminator' ) );
		}

		if ( empty( $id ) ) {
			return new WP_Error( 'missing_id', esc_html__( 'Field ID is required!', 'forminator' ) );
		}

		// Load form model.
		$model = Forminator_Base_Form_Model::get_model( $form_id );

		if ( ! is_object( $model ) ) {
			return new WP_Error( 'missing_object', esc_html__( 'Form model doesn\'t exist', 'forminator' ) );
		}

		$field = $model->get_field( $id, $to_array );

		if ( ( is_array( $field ) || is_object( $field ) ) && ! empty( $field ) ) {
			return $field;
		}

		return new WP_Error( 'missing_field', esc_html__( 'Field doesn\'t exist', 'forminator' ) );
	}

	/**
	 * Update form
	 *
	 * @since  1.5
	 * @access public
	 *
	 * @param int    $form_id Form ID.
	 * @param string $id      Field ID.
	 * @param array  $data    Array with field settings.
	 *
	 * @return string|WP_Error id of updated field, or WP_Error on failure
	 */
	public static function update_form_field( $form_id, $id, $data = array() ) {
		// Initialize API.
		self::initialize();

		if ( empty( $form_id ) ) {
			return new WP_Error( 'missing_form_id', esc_html__( 'Form ID is required!', 'forminator' ) );
		}

		if ( empty( $id ) ) {
			return new WP_Error( 'missing_id', esc_html__( 'Field ID is required!', 'forminator' ) );
		}

		if ( empty( $data ) ) {
			return new WP_Error( 'missing_data', esc_html__( 'Field data is required!', 'forminator' ) );
		}

		// Load form model.
		$model = Forminator_Base_Form_Model::get_model( $form_id );

		if ( ! is_object( $model ) ) {
			return new WP_Error( 'missing_object', esc_html__( 'Form model doesn\'t exist', 'forminator' ) );
		}

		$field = $model->get_field( $id, false );

		// Escape data.
		// Remove field type, we can't change field type here.
		if ( isset( $data['type'] ) ) {
			unset( $data['type'] ); // remove field type.
		}

		// Remove field cols, we can't change field cols here.
		if ( isset( $data['cols'] ) ) {
			unset( $data['cols'] ); // remove field type.
		}

		// Remove field wrapper, we can't change field wrapper here.
		if ( isset( $data['form_id'] ) ) {
			unset( $data['form_id'] ); // remove wrapper id.
		}

		// Remove field wrapper, we can't change field wrapper here.
		if ( isset( $data['wrapper_id'] ) ) {
			unset( $data['wrapper_id'] ); // remove wrapper id.
		}

		// Remove field ID or SLUG, we can't change field ID here.
		if ( isset( $data['element_id'] ) || isset( $data['slug'] ) ) {
			if ( isset( $data['element_id'] ) ) {
				unset( $data['element_id'] );
			}

			if ( isset( $data['slug'] ) ) {
				unset( $data['slug'] );
			}
		}

		// Update field.
		$field->import( $data );

		$result = $model->save();

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return $id;
	}

	/**
	 * Update form
	 *
	 * @since  1.5
	 * @access public
	 *
	 * @param int         $form_id Form ID.
	 * @param string      $type Field Type.
	 * @param array       $data    Array with field settings.
	 * @param string|null $wrapper if omitted it will create new wrapper,.
	 *                             if specified it will check its existence on form,
	 *                             when its non existence, it will create new wrapper automatically.
	 *
	 * @return string|WP_Error id of new field, or WP_Error on failure
	 */
	public static function add_form_field( $form_id, $type, $data = array(), $wrapper = null ) {
		// Initialize API.
		self::initialize();

		if ( empty( $form_id ) ) {
			return new WP_Error( 'missing_form_id', esc_html__( 'Form ID is required!', 'forminator' ) );
		}

		if ( empty( $type ) ) {
			return new WP_Error( 'missing_type', esc_html__( 'Field Type is required!', 'forminator' ) );
		}

		if ( empty( $data ) ) {
			return new WP_Error( 'missing_data', esc_html__( 'Field data is required!', 'forminator' ) );
		}

		// Load form model.
		$model = Forminator_Base_Form_Model::get_model( $form_id );

		if ( ! is_object( $model ) ) {
			return new WP_Error( 'missing_object', esc_html__( 'Form model doesn\'t exist', 'forminator' ) );
		}

		// Generate field ID.
		$total_fields_type = self::get_form_fields_by_type( $form_id, $type );

		if ( is_wp_error( $total_fields_type ) ) {
			$total_fields_type = array();
		}

		// Count fields of type.
		$total_type = count( $total_fields_type ) + 1;

		// Create field ID.
		$field_id = $type . '-' . $total_type;

		// check wrapper existence.
		if ( ! empty( $wrapper ) ) {
			$wrapper = (string) $wrapper;
			$wrapper = self::get_form_wrapper( $form_id, $wrapper );
			if ( is_wp_error( $wrapper ) ) {
				// nullify when not exist, so it will create new wrapper instead.
				$wrapper = null;
			}
		}

		// Handle wrapper.
		if ( ! $wrapper ) {
			$wrapper = uniqid( 'wrapper-' );
		}

		// Create empty field.
		$field = new Forminator_Form_Field_Model();

		// Update field settings.
		$data['id']         = $field_id;
		$data['element_id'] = $field_id;
		$data['type']       = $type;
		$data['form_id']    = $wrapper;

		// Bind settings to the fieldget_form_wrapper.
		$field->import( $data );

		// Update field slug.
		$field->slug = $field_id;

		// Add the field to form.
		$model->add_field( $field );

		// Update all fields cols in the wrapper.
		$model->update_fields_by_wrapper( $wrapper );

		// Save the form.
		$result = $model->save();

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return $field_id;
	}

	/**
	 * Move Form Field position
	 *
	 * This method able to move field inside it's wrapper
	 * and move field to another wrapper
	 *
	 * @since  1.5
	 * @access public
	 *
	 * @param int             $form_id Form Id.
	 * @param string          $id Wrapper Id.
	 * @param null            $new_position   negative value or  greater than size MAX_CUSTOM_FORM_FIELDS_PER_WRAPPER will move it to the end.
	 *                                        if value passed is not `int` it will type cast-ed into `int`.
	 *                                        Start from Zero (0).
	 * @param null|string|int $new_wrapper_id optional parameter which identify in which wrapper should the field moved.
	 *                                        If its omitted or `empty` (0,null,false,etc) or same with current wrapper, field will be moved inside its current wrapper.
	 *                                        If its specified, this function will check its existences on form.
	 *                                        When wrapper exist, it will move field to $position inside that $new_wrapper_id,
	 *                                        When wrapper not exist, it will return WP_Error
	 *                                        Negative value will make a new wrapper at bottom, and move field into this newly created wrapper.
	 *
	 * @return array|WP_Error moved field on success, or WP_Error on failure
	 */
	public static function move_form_field( $form_id, $id, $new_position, $new_wrapper_id = null ) {

		$form = self::get_module( $form_id );
		if ( is_wp_error( $form ) ) {
			return $form;
		}

		$field = self::get_form_field( $form_id, $id );
		if ( is_wp_error( $field ) ) {
			return $field;
		}

		$wrappers = self::get_form_wrappers( $form_id );
		if ( is_wp_error( $wrappers ) ) {
			return $wrappers;
		}

		$old_wrapper = self::get_form_wrapper( $form_id, $field['wrapper_id'] );
		if ( is_wp_error( $old_wrapper ) ) {
			return $old_wrapper;
		}
		$old_wrapper_id = $field['wrapper_id'];

		$is_cross_wrapper = false;

		// $new_wrapper_id specified ?
		if ( ! empty( $new_wrapper_id ) && ( $old_wrapper_id !== $new_wrapper_id ) ) {

			// definitely targeted to new wrapper, lets check existence.
			$is_cross_wrapper = true;
			// move to new wrapper ?
			if ( is_int( $new_wrapper_id ) && 0 > $new_wrapper_id ) {
				// create!
				$new_wrapper_id = uniqid( 'wrapper-' );
				// dummy wrapper struct with empty fields.
				$new_wrapper = array(
					'wrapper_id' => $new_wrapper_id,
					'fields'     => array(),
					'position'   => count( $wrappers ), // set at bottom.
				);
			} else {
				// expected to be existing wrapper.
				$new_wrapper_id = (string) $new_wrapper_id;
				$new_wrapper    = self::get_form_wrapper( $form_id, $new_wrapper_id );
				// nonexistent wrapper.
				if ( is_wp_error( $new_wrapper ) ) {
					return $new_wrapper;
				}
			}
		} else {
			// omitted ? operation will be done inside its current wrapper.
			$new_wrapper_id = $old_wrapper_id;
			$new_wrapper    = $old_wrapper;
		}

		if ( ! is_int( $new_position ) ) {
			$new_position = (int) $new_position;
		}

		// negative value will move it to the end,.
		if ( $new_position < 0 || ( $new_position > ( self::MAX_CUSTOM_FORM_FIELDS_PER_WRAPPER - 1 ) ) ) {
			$new_position = ( self::MAX_CUSTOM_FORM_FIELDS_PER_WRAPPER - 1 );
		}

		if ( $is_cross_wrapper && count( $new_wrapper['fields'] ) >= self::MAX_CUSTOM_FORM_FIELDS_PER_WRAPPER ) {
			return new WP_Error( 'target_wrapper_is_full', esc_html__( 'Target Wrapper is already full.', 'forminator' ), $new_wrapper );
		}

		// validation flag.
		$found_in_wrapper = false;
		$old_position     = 0;
		foreach ( $old_wrapper['fields'] as $key => $wrapper_field ) {
			if ( $field['element_id'] === $wrapper_field['element_id'] ) {
				$found_in_wrapper = true;
				$old_position     = $key;
				break;
			}
		}

		// should not be happened ever! unless storage directly modified.
		// but just in case... to avoid further fields corruption.
		if ( ! $found_in_wrapper ) {
			return new WP_Error( 'invalid_field', esc_html__( 'Invalid field', 'forminator' ), $old_wrapper );
		}

		// unchanged position.
		if ( $new_position === $old_position && $old_wrapper_id === $new_wrapper_id ) {
			return $field;
		}

		// inside its current wrapper.
		if ( ! $is_cross_wrapper ) {
			// remove field from old position.
			unset( $old_wrapper['fields'][ $old_position ] );
			$old_wrapper_fields = $old_wrapper['fields'];

			// move!
			array_splice( $old_wrapper_fields, $new_position, 0, array( $field ) );
			$old_wrapper['fields'] = $old_wrapper_fields;

			// replace it!
			$wrappers[ $old_wrapper['position'] ] = $old_wrapper;
		} else {
			// remove field from old position.
			unset( $old_wrapper['fields'][ $old_position ] );
			// replace old wrapper.
			$wrappers[ $old_wrapper['position'] ] = $old_wrapper;

			$new_wrapper_fields = $new_wrapper['fields'];

			// Add it.
			array_splice( $new_wrapper_fields, $new_position, 0, array( $field ) );
			$new_wrapper['fields'] = $new_wrapper_fields;

			// replace it!
			$wrappers[ $new_wrapper['position'] ] = $new_wrapper;

		}

		// reset fields.
		$form->clear_fields();

		foreach ( $wrappers as $row ) {
			foreach ( $row['fields'] as $f ) {
				// re-set `id`.
				if ( ! isset( $f['id'] ) || $f['element_id'] !== $f['id'] ) {
					$f['id'] = $f['element_id'];
				}

				// remove previous wrapper_id if exist.
				unset( $f['wrapper_id'] );
				unset( $f['form_id'] );

				$field          = new Forminator_Form_Field_Model();
				$field->form_id = $row['wrapper_id'];
				$field->slug    = $f['id'];
				unset( $f['id'] );
				$field->parent_group = ! empty( $row['parent_group'] ) ? $row['parent_group'] : '';
				$field->import( $f );
				$form->add_field( $field );
			}
		}

		// reset cols.
		$form->update_fields_by_wrapper( $old_wrapper_id );
		$form->update_fields_by_wrapper( $new_wrapper_id );

		$form_id = $form->save();
		if ( is_wp_error( $form_id ) ) {
			return $form_id;
		}

		return self::get_form_field( $form_id, $id );
	}

	/**
	 * Delete multiple fields from form
	 *
	 * @since  1.5.0
	 * @access public
	 *
	 * @param int   $form_id Form Id.
	 * @param array $field_ids Field Ids.
	 *
	 * @return bool|WP_Error
	 */
	public static function delete_form_fields( $form_id, $field_ids ) {
		// Initialize API.
		self::initialize();

		if ( is_array( $field_ids ) && ! empty( $field_ids ) ) {
			foreach ( $field_ids as $id ) {
				self::delete_form_field( $form_id, $id );
			}

			return true;
		} else {
			return new WP_Error( 'invalid', esc_html__( 'Invalid or empty array with IDs', 'forminator' ) );
		}
	}

	/**
	 * Delete field from form
	 *
	 * @since  1.5.0
	 * @access public
	 *
	 * @param int $form_id Form Id.
	 * @param int $id Field Id.
	 *
	 * @return string|WP_Error
	 */
	public static function delete_form_field( $form_id, $id ) {
		// Load form model.
		$model = Forminator_Base_Form_Model::get_model( $form_id );

		$wrapper = $model->delete_field( $id );

		if ( false === $wrapper ) {
			return new WP_Error( 'missing_field', esc_html__( 'Field doesn\'t exist', 'forminator' ) );
		}

		$model->update_fields_by_wrapper( $wrapper );

		// Save the form.
		$id = $model->save();

		if ( is_wp_error( $id ) ) {
			return $id;
		}

		if ( false === $id ) {
			return new WP_Error( 'form_save_error', esc_html__( 'There was a problem saving the form', 'forminator' ) );
		}

		return $id;
	}

	/**
	 * Update form setting
	 *
	 * @since  1.5
	 * @access public
	 *
	 * @param int    $form_id Form ID.
	 * @param string $setting Setting name.
	 * @param mixed  $value   Setting value.
	 *
	 * @return int|WP_Error id of form, or WP_Error on failure
	 */
	public static function update_form_setting( $form_id, $setting, $value ) {
		// Initialize API.
		self::initialize();

		if ( empty( $form_id ) ) {
			return new WP_Error( 'missing_form_id', esc_html__( 'Form ID is required!', 'forminator' ) );
		}

		if ( empty( $setting ) ) {
			return new WP_Error( 'missing_name', esc_html__( 'Setting name is required!', 'forminator' ) );
		}

		// Load form model.
		$model = Forminator_Base_Form_Model::get_model( $form_id );

		if ( ! is_object( $model ) ) {
			return new WP_Error( 'missing_object', esc_html__( 'Form model doesn\'t exist', 'forminator' ) );
		}

		// Set the setting.
		$model->settings[ $setting ] = sanitize_textarea_field( $value );

		// Save data.
		$id = $model->save();

		if ( is_wp_error( $id ) ) {
			return $id;
		}

		return $id;
	}

	/**
	 * Update form settings
	 *
	 * @since  1.5
	 * @access public
	 *
	 * @param int   $form_id  Form ID.
	 * @param array $settings array of settings and values.
	 *
	 * @return int|WP_Error id of form, or WP_Error on failure
	 */
	public static function update_form_settings( $form_id, $settings ) {
		// Initialize API.
		self::initialize();

		if ( empty( $form_id ) ) {
			return new WP_Error( 'missing_form_id', esc_html__( 'Form ID is required!', 'forminator' ) );
		}

		if ( empty( $settings ) ) {
			return new WP_Error( 'missing_settings', esc_html__( 'No settings', 'forminator' ) );
		}

		// Load form model.
		$model = Forminator_Base_Form_Model::get_model( $form_id );

		if ( ! is_object( $model ) ) {
			return new WP_Error( 'missing_object', esc_html__( 'Form model doesn\'t exist', 'forminator' ) );
		}

		// Load all settings.
		$form_settings = $model->settings;

		foreach ( $settings as $name => $value ) {
			// Set the setting.
			$form_settings[ $name ] = sanitize_textarea_field( $value );
		}

		// Save data.
		$id = $model->save();

		if ( is_wp_error( $id ) ) {
			return $id;
		}

		return $id;
	}

	/**
	 * Returns all poll objects.
	 *
	 * @since            1.2.0
	 * @since            1.5.4 add pagination arguments
	 * @access           public
	 *
	 * @param array|null $poll_ids Poll Ids.
	 * @param int        $page Page number.
	 * @param int        $per_page Limit per page.
	 * @param string     $status (draft,publish,any).
	 *
	 * @return Forminator_Poll_Model[]|WP_Error
	 */
	public static function get_polls( $poll_ids = null, $page = 1, $per_page = 10, $status = '' ) {
		// Initialize API.
		self::initialize();

		$temp = array();

		if ( is_null( $poll_ids ) ) {
			$temp = Forminator_Poll_Model::model()->get_all_paged( $page, $per_page, $status );
			if ( isset( $temp['models'] ) && is_array( $temp['models'] ) ) {
				return $temp['models'];
			}

			return array();
		} else {
			if ( ! is_array( $poll_ids ) ) {
				return new WP_Error( 'invalid_arg', esc_html__( 'Invalid Arguments', 'forminator' ) );
			}

			foreach ( $poll_ids as $poll_id ) {
				$model = self::get_module( $poll_id );
				if ( ! empty( $status ) && ! $model instanceof WP_Error && $status === $model->status ) {
					$temp[] = $model;
				}
			}
		}

		return $temp;
	}

	/**
	 * Returns poll object by given ID.
	 *
	 * @since  1.2
	 * @access public
	 *
	 * @param int $poll_id ID of the poll.
	 *
	 * @return Forminator_Poll_Model|WP_Error Forminator_Poll_Model on success, or WP_Error otherwise
	 */
	public static function get_poll( $poll_id ) {
		return self::get_module( $poll_id );
	}

	/**
	 * Delete poll with given ID
	 *
	 * @since  1.2
	 * @access public
	 *
	 * @param int $poll_id ID of the poll.
	 */
	public static function delete_poll( $poll_id ) {
		self::delete_module( $poll_id );
	}

	/**
	 * Delete polls with given IDs
	 *
	 * @since  1.2
	 * @access public
	 *
	 * @param int $poll_ids Array with IDs.
	 */
	public static function delete_polls( $poll_ids ) {
		self::delete_modules( $poll_ids );
	}

	/**
	 * Add a poll
	 *
	 * @since  1.2
	 * @access public
	 *
	 * @param string $name     Poll name.
	 * @param array  $fields   Array with poll fields.
	 * @param array  $settings Array with settings.
	 * @param string $status   (draft|publish), default publish.
	 *
	 * @return int|WP_Error ID of new Poll on success, or WP_Error otherwise
	 */
	public static function add_poll( $name, $fields = array(), $settings = array(), $status = Forminator_Poll_Model::STATUS_PUBLISH ) {
		// Initialize API.
		self::initialize( true );

		if ( empty( $name ) ) {
			return new WP_Error( 'missing_name', esc_html__( 'Poll name is required!', 'forminator' ) );
		}

		$template           = new stdClass();
		$template->fields   = $fields;
		$template->settings = $settings;

		$id = Forminator_Poll_Admin::create( $name, $status, $template );

		if ( false === $id ) {
			return new WP_Error( 'form_save_error', esc_html__( 'There was a problem saving the poll', 'forminator' ) );
		}

		return $id;
	}

	/**
	 * Update a poll
	 *
	 * @since  1.2
	 * @access public
	 *
	 * @param int    $id       Poll ID.
	 * @param array  $fields   Array with poll fields.
	 * @param array  $settings Array with settings.
	 * @param string $status   status of form `draft`| `publish` | `` for keep as it is.
	 *
	 * @return int|WP_Error ID of updated Poll on success, or WP_Error otherwise
	 */
	public static function update_poll( $id, $fields = array(), $settings = array(), $status = '' ) {
		// Initialize API.
		self::initialize( true );

		if ( empty( $id ) ) {
			return new WP_Error( 'missing_id', esc_html__( 'Poll ID is required!', 'forminator' ) );
		}

		// Set the post data.
		$title              = $settings['formName'];
		$template           = new stdClass();
		$template->settings = $settings;
		$template->answers  = $fields;

		$id = Forminator_Poll_Admin::update( $id, $title, $status, $template );
		if ( false === $id ) {
			return new WP_Error( 'form_save_error', esc_html__( 'There was a problem saving the poll', 'forminator' ) );
		}

		return $id;
	}


	/**
	 * Returns all quiz objects.
	 *
	 * @since      1.2.0
	 * @since      1.5.4 add pagination arguments
	 * @since      1.6.2 add $status on args
	 * @access     public
	 *
	 * @param array|null $quiz_ids Quiz Ids.
	 * @param int        $page Page number.
	 * @param int        $per_page Limit per page.
	 * @param string     $status Status.
	 *
	 * @return Forminator_Quiz_Model[]|WP_Error
	 */
	public static function get_quizzes( $quiz_ids = null, $page = 1, $per_page = 10, $status = '' ) {
		// Initialize API.
		self::initialize();

		$temp = array();

		if ( is_null( $quiz_ids ) ) {
			$temp = Forminator_Quiz_Model::model()->get_all_paged( $page, $per_page, $status );
			if ( isset( $temp['models'] ) && is_array( $temp['models'] ) ) {
				return $temp['models'];
			}

			return array();
		} else {
			if ( ! is_array( $quiz_ids ) ) {
				return new WP_Error( 'invalid_arg', esc_html__( 'Invalid Arguments', 'forminator' ) );
			}

			foreach ( $quiz_ids as $quiz_id ) {
				$model = self::get_module( $quiz_id );
				if ( ! empty( $status ) && ! $model instanceof WP_Error && $status === $model->status ) {
					$temp[] = $model;
				}
			}
		}

		return $temp;
	}

	/**
	 * Returns quiz object by given ID.
	 *
	 * @since  1.2
	 * @access public
	 *
	 * @param int $quiz_id ID of the Quiz.
	 *
	 * @return Forminator_Quiz_Model|WP_Error, Quiz Object on success, or WP_Error otherwise
	 */
	public static function get_quiz( $quiz_id ) {
		return self::get_module( $quiz_id );
	}

	/**
	 * Delete quiz with given ID
	 *
	 * @since  1.2
	 * @access public
	 *
	 * @param int $quiz_id ID of the quiz.
	 */
	public static function delete_quiz( $quiz_id ) {
		self::delete_module( $quiz_id );
	}

	/**
	 * Delete forms with given IDs
	 *
	 * @since  1.2
	 * @access public
	 *
	 * @param array $quiz_ids Array with IDs.
	 */
	public static function delete_quizzes( $quiz_ids ) {
		self::delete_modules( $quiz_ids );
	}

	/**
	 * Add quiz
	 *
	 * @since  1.2
	 * @since  1.6.2 add new arg : $status
	 * @access public
	 *
	 * @param string $name      Quiz name.
	 * @param string $type      Quiz type (nowrong|knowledge).
	 * @param array  $questions Array with quiz questions.
	 * @param array  $results   Array with quiz results.
	 * @param array  $settings  Array with settings.
	 * @param string $status    Status of newly created quiz : draft/publish, default publish.
	 * @param bool   $has_leads Is this quiz has Leads or not, default false.
	 *
	 * @return int|WP_Error ID of new Quiz, or WP_Error otherwise
	 */
	public static function add_quiz( $name, $type, $questions = array(), $results = array(), $settings = array(), $status = null, $has_leads = false ) {
		// Initialize API.
		self::initialize( true );

		if ( empty( $name ) ) {
			return new WP_Error( 'missing_name', esc_html__( 'Quiz name is required!', 'forminator' ) );
		}

		if ( empty( $type ) ) {
			return new WP_Error( 'missing_type', esc_html__( 'Quiz type is required!', 'forminator' ) );
		}

		$template            = new stdClass();
		$template->settings  = $settings;
		$template->questions = $questions;
		$template->results   = $results;
		$template->quiz_type = $type;
		$template->has_leads = $has_leads;

		if ( ! $status ) {
			$status = Forminator_Quiz_Model::STATUS_PUBLISH;
		}

		$id = Forminator_Quiz_Admin::create( $name, $status, $template );

		if ( false === $id ) {
			return new WP_Error( 'quiz_save_error', esc_html__( 'There was a problem saving the quiz', 'forminator' ) );
		}

		return $id;
	}

	/**
	 * Update quiz
	 *
	 * @since  1.2
	 * @since  1.6.2 Add $status arg
	 * @access public
	 *
	 * @param int    $id        Quiz ID.
	 * @param array  $questions Array with quiz questions.
	 * @param array  $results   Array with quiz results.
	 * @param array  $settings  Array with settings.
	 * @param string $status    Update status of quiz, draft/publish.
	 *
	 * @return int|WP_Error ID of updated Quiz, or WP_Error otherwise
	 */
	public static function update_quiz( $id, $questions = array(), $results = array(), $settings = array(), $status = null ) {
		// Initialize API.
		self::initialize( true );

		if ( empty( $id ) ) {
			return new WP_Error( 'missing_id', esc_html__( 'Quiz ID is required!', 'forminator' ) );
		}

		// Set the post data.
		$title    = $settings['formName'];
		$template = new stdClass();

		$template->settings = $settings;

		// Bind questions.
		if ( ! empty( $questions ) ) {
			$template->questions = $questions;
		}

		// Bind results.
		if ( ! empty( $results ) ) {
			$template->results = $results;
		}

		$id = Forminator_Quiz_Admin::update( $id, $title, $status, $template );
		if ( false === $id ) {
			return new WP_Error( 'quiz_save_error', esc_html__( 'There was a problem saving the quiz', 'forminator' ) );
		}

		return $id;
	}

	// ENTRIES.

	/**
	 * Get entries objects
	 *
	 * @since 1.2
	 *
	 * @param int $form_id Form ID.
	 * @param int $per_page Limit per page.
	 * @param int $current_page Current page.
	 *
	 * @since 1.38 Added optional param per_page.
	 * @since 1.38 Added optional param current_page.
	 *
	 * @return Forminator_Form_Entry_Model[]|WP_Error
	 */
	public static function get_entries( $form_id, $per_page = 0, $current_page = 1 ) {
		// Initialize API.
		self::initialize();

		// Check if Form ID is set.
		if ( empty( $form_id ) ) {
			return new WP_Error( 'missing_form_id', esc_html__( 'Form ID is required!', 'forminator' ) );
		}
		if ( $per_page ) {
			$current_page = intval( $current_page );
			$current_page = $current_page > 0 ? $current_page : 1;
			$offset       = ( $current_page - 1 ) * $per_page;
			$args         = array(
				'form_id'  => $form_id,
				'is_spam'  => 0,
				'per_page' => $per_page,
				'offset'   => $offset,
			);
			return Forminator_Form_Entry_Model::query_entries( $args );
		}

		return Forminator_Form_Entry_Model::get_all_entries( $form_id );
	}

	/**
	 * Get entry object
	 *
	 * @since 1.2
	 *
	 * @param int $form_id Form Id.
	 * @param int $entry_id Entry Id.
	 *
	 * @return Forminator_Form_Entry_Model|WP_Error
	 */
	public static function get_entry( $form_id, $entry_id ) {
		// Initialize API.
		self::initialize();

		// Check if Form ID is set.
		if ( empty( $form_id ) ) {
			return new WP_Error( 'missing_form_id', esc_html__( 'Form ID is required!', 'forminator' ) );
		}

		// Check if Entry ID is set.
		if ( empty( $entry_id ) ) {
			return new WP_Error( 'missing_entry_id', esc_html__( 'Entry ID is required!', 'forminator' ) );
		}

		return new Forminator_Form_Entry_Model( $entry_id );
	}

	/**
	 * Delete entry
	 *
	 * @uses  Forminator_Form_Entry_Model::delete_by_entry
	 *
	 * @since 1.2
	 *
	 * @param int $form_id Form Id.
	 * @param int $entry_id Entry Id.
	 *
	 * @return bool|WP_Error
	 */
	public static function delete_entry( $form_id, $entry_id ) {
		// Initialize API.
		self::initialize();

		// Check if Form ID is set.
		if ( empty( $form_id ) ) {
			return new WP_Error( 'missing_form_id', esc_html__( 'Form ID is required!', 'forminator' ) );
		}

		// Check if Entry ID is set.
		if ( empty( $entry_id ) ) {
			return new WP_Error( 'missing_entry_id', esc_html__( 'Entry ID is required!', 'forminator' ) );
		}

		// Delete entry.
		Forminator_Form_Entry_Model::delete_by_entry( $entry_id );

		return true;
	}

	/**
	 * Delete multiple entries
	 *
	 * @uses  Forminator_Form_Entry_Model::delete_by_entrys
	 *
	 * @since 1.2
	 *
	 * @param int   $form_id Form Id.
	 * @param mixed $entries_ids Entry Ids.
	 *
	 * @return bool|WP_Error
	 */
	public static function delete_entries( $form_id, $entries_ids ) {
		// Initialize API.
		self::initialize();

		// Check if Form ID is set.
		if ( empty( $form_id ) ) {
			return new WP_Error( 'missing_form_id', esc_html__( 'Form ID is required!', 'forminator' ) );
		}

		// Check if Entry ID is set.
		if ( empty( $entries_ids ) ) {
			return new WP_Error( 'missing_entry_id', esc_html__( 'Entry IDs are required!', 'forminator' ) );
		}

		// Check if entries ids are array and convert to string.
		if ( is_array( $entries_ids ) ) {
			$entries_ids = implode( ',', $entries_ids );
		}

		// Delete entries.
		Forminator_Form_Entry_Model::delete_by_entrys( $form_id, $entries_ids );

		return true;
	}

	/**
	 * Count entries
	 *
	 * @uses  Forminator_Form_Entry_Model::count_entries
	 *
	 * @since 1.2
	 *
	 * @param int $form_id Form Id.
	 *
	 * @return int|WP_Error
	 */
	public static function count_entries( $form_id ) {
		// Initialize API.
		self::initialize();

		// Check if Form ID is set.
		if ( empty( $form_id ) ) {
			return new WP_Error( 'missing_form_id', esc_html__( 'Form ID is required!', 'forminator' ) );
		}

		return Forminator_Form_Entry_Model::count_entries( $form_id );
	}

	/**
	 * Get Form entries
	 *
	 * @access public
	 * @since  1.2
	 *
	 * @param int $form_id Form ID.
	 *
	 * @return Forminator_Form_Entry_Model[]|WP_Error
	 */
	public static function get_form_entries( $form_id ) {
		return self::get_entries( $form_id );
	}

	/**
	 * Get Form entry by Form ID and Entry ID
	 *
	 * @access public
	 * @since  1.2
	 *
	 * @param int $form_id  Form ID.
	 * @param int $entry_id Entry ID.
	 *
	 * @return Forminator_Form_Entry_Model|WP_Error
	 */
	public static function get_form_entry( $form_id, $entry_id ) {
		return self::get_entry( $form_id, $entry_id );
	}

	/**
	 * Delete Form entry
	 *
	 * @access public
	 * @since  1.2
	 *
	 * @param int $form_id Form Id.
	 * @param int $entry_id Entry Id.
	 *
	 * @return bool|WP_Error
	 */
	public static function delete_form_entry( $form_id, $entry_id ) {
		return self::delete_entry( $form_id, $entry_id );
	}

	/**
	 * Delete Form entries
	 *
	 * @access public
	 * @since  1.2
	 *
	 * @param int          $form_id   Form ID.
	 * @param array|string $entry_ids entry IDs in an array, or string glued with `,`(comma).
	 *
	 * @return bool|WP_Error
	 */
	public static function delete_form_entries( $form_id, $entry_ids ) {
		return self::delete_entries( $form_id, $entry_ids );
	}

	/**
	 * Count form entries
	 *
	 * @access public
	 * @since  1.2
	 *
	 * @param int $form_id Form Id.
	 *
	 * @return int|WP_Error
	 */
	public static function count_form_entries( $form_id ) {
		return self::count_entries( $form_id );
	}

	/**
	 * Get Poll entries
	 *
	 * @access public
	 * @since  1.2
	 *
	 * @param int $poll_id Poll ID.
	 *
	 * @return Forminator_Form_Entry_Model[]|WP_Error
	 */
	public static function get_poll_entries( $poll_id ) {
		return self::get_entries( $poll_id );
	}

	/**
	 * Get Poll entry by ID
	 *
	 * @access public
	 * @since  1.2
	 *
	 * @param int $poll_id  Poll ID.
	 * @param int $entry_id Entry ID.
	 *
	 * @return Forminator_Form_Entry_Model|WP_Error
	 */
	public static function get_poll_entry( $poll_id, $entry_id ) {
		return self::get_entry( $poll_id, $entry_id );
	}

	/**
	 * Delete Poll entry
	 *
	 * @access public
	 * @since  1.2
	 *
	 * @param int $poll_id Poll Id.
	 * @param int $entry_id Entry Id.
	 *
	 * @return bool|WP_Error
	 */
	public static function delete_poll_entry( $poll_id, $entry_id ) {
		return self::delete_entry( $poll_id, $entry_id );
	}

	/**
	 * Delete Poll entries
	 *
	 * @access public
	 * @since  1.2
	 *
	 * @param int          $poll_id Poll Id.
	 * @param array|string $entry_ids entry IDs in an array, or string separated with `,`(comma).
	 *
	 * @return bool|WP_Error
	 */
	public static function delete_poll_entries( $poll_id, $entry_ids ) {
		return self::delete_entries( $poll_id, $entry_ids );
	}

	/**
	 * Count poll entries
	 *
	 * @access public
	 * @since  1.2
	 *
	 * @param int $poll_id Poll Id.
	 *
	 * @return int|WP_Error
	 */
	public static function count_poll_entries( $poll_id ) {
		return self::count_entries( $poll_id );
	}

	/**
	 * Get Quiz entries
	 *
	 * @access public
	 * @since  1.2
	 *
	 * @param int $quiz_id Quiz ID.
	 *
	 * @return Forminator_Form_Entry_Model[]|WP_Error
	 */
	public static function get_quiz_entries( $quiz_id ) {
		return self::get_entries( $quiz_id );
	}

	/**
	 * Get Quiz entry by ID
	 *
	 * @access public
	 * @since  1.2
	 *
	 * @param int $quiz_id  Quiz ID.
	 * @param int $entry_id Entry ID.
	 *
	 * @return Forminator_Form_Entry_Model|WP_Error
	 */
	public static function get_quiz_entry( $quiz_id, $entry_id ) {
		return self::get_entry( $quiz_id, $entry_id );
	}

	/**
	 * Delete Quiz entry
	 *
	 * @access public
	 * @since  1.2
	 *
	 * @param int $quiz_id Quiz Id.
	 * @param int $entry_id Entry Id.
	 *
	 * @return bool|WP_Error
	 */
	public static function delete_quiz_entry( $quiz_id, $entry_id ) {
		return self::delete_entry( $quiz_id, $entry_id );
	}

	/**
	 * Delete Quiz entries
	 *
	 * @access public
	 * @since  1.2
	 *
	 * @param int   $quiz_id Quiz id.
	 * @param array $entry_ids Entry Ids.
	 *
	 * @return bool|WP_Error
	 */
	public static function delete_quiz_entries( $quiz_id, $entry_ids ) {
		return self::delete_entries( $quiz_id, $entry_ids );
	}

	/**
	 * Count quiz entries
	 *
	 * @access public
	 * @since  1.2
	 *
	 * @param int $quiz_id Quiz id.
	 *
	 * @return int|WP_Error
	 */
	public static function count_quiz_entries( $quiz_id ) {
		return self::count_entries( $quiz_id );
	}

	/**
	 * Add Form Entry
	 *
	 * @access public
	 * @since  1.5
	 *
	 * @param int   $form_id Form id.
	 * @param array $entry_meta ['name' => 'META_NAME', 'value' => 'META_VALUE'].
	 *
	 * @return int|WP_Error
	 */
	public static function add_form_entry( $form_id, $entry_meta ) {
		// validating form module.
		$model = self::get_module( $form_id );
		if ( is_wp_error( $model ) ) {
			return $model;
		}

		return self::add_entry( $form_id, 'custom-forms', $entry_meta );
	}

	/**
	 * Add Multiple Form Entry
	 *
	 * @access public
	 * @since  1.5
	 *
	 * @param int   $form_id Form Id.
	 * @param array $entry_metas nested array entry meta.
	 *
	 * @see    Forminator_API::add_form_entry()
	 *
	 * @return array|WP_Error array of entry id or WP_Error
	 */
	public static function add_form_entries( $form_id, $entry_metas ) {
		// validating form module.
		$model = self::get_module( $form_id );
		if ( is_wp_error( $model ) ) {
			return $model;
		}

		return self::add_entries( $form_id, 'custom-forms', $entry_metas );
	}

	/**
	 * Update form entry
	 *
	 * @access public
	 * @since  1.5
	 *
	 * @param int   $form_id Form id.
	 * @param int   $entry_id Entry Id.
	 * @param array $entry_meta ['name' => 'META_NAME', 'value' => 'META_VALUE'].
	 *
	 * @return bool|WP_Error
	 */
	public static function update_form_entry( $form_id, $entry_id, $entry_meta ) {
		// validating form module.
		$model = self::get_module( $form_id );
		if ( is_wp_error( $model ) ) {
			return $model;
		}

		return self::update_entry_meta( $form_id, $entry_id, $entry_meta );
	}

	/**
	 * Add Poll Entry
	 *
	 * @access public
	 * @since  1.5
	 *
	 * @param int   $poll_id Poll Id.
	 * @param array $entry_meta ['name' => 'META_NAME', 'value' => 'META_VALUE'].
	 *
	 * @return int|WP_Error
	 */
	public static function add_poll_entry( $poll_id, $entry_meta ) {
		// validating poll module.
		$model = self::get_module( $poll_id );
		if ( is_wp_error( $model ) ) {
			return $model;
		}

		return self::add_entry( $poll_id, 'poll', $entry_meta );
	}

	/**
	 * Add Multiple Poll Entry
	 *
	 * @access public
	 * @since  1.5
	 *
	 * @param int   $poll_id Poll Id.
	 * @param array $entry_metas nested array entry meta.
	 *
	 * @see    Forminator_API::add_form_entry()
	 *
	 * @return array|WP_Error array of entry id or WP_Error
	 */
	public static function add_poll_entries( $poll_id, $entry_metas ) {
		// validating poll module.
		$model = self::get_module( $poll_id );
		if ( is_wp_error( $model ) ) {
			return $model;
		}

		return self::add_entries( $poll_id, 'poll', $entry_metas );
	}

	/**
	 * Update poll entry
	 *
	 * @access public
	 * @since  1.5
	 *
	 * @param int   $poll_id Poll Id.
	 * @param int   $entry_id Entry id.
	 * @param array $entry_meta ['name' => 'META_NAME', 'value' => 'META_VALUE'].
	 *
	 * @return bool|WP_Error
	 */
	public static function update_poll_entry( $poll_id, $entry_id, $entry_meta ) {
		// validating poll module.
		$model = self::get_module( $poll_id );
		if ( is_wp_error( $model ) ) {
			return $model;
		}

		return self::update_entry_meta( $poll_id, $entry_id, $entry_meta );
	}

	/**
	 * Add Quiz Entry
	 *
	 * @access public
	 * @since  1.5
	 *
	 * @param int   $quiz_id Quiz Id.
	 * @param array $entry_meta ['name' => 'META_NAME', 'value' => 'META_VALUE'].
	 *
	 * @return int|WP_Error
	 */
	public static function add_quiz_entry( $quiz_id, $entry_meta ) {
		// validating quiz module.
		$model = self::get_module( $quiz_id );
		if ( is_wp_error( $model ) ) {
			return $model;
		}

		return self::add_entry( $quiz_id, 'quizzes', $entry_meta );
	}

	/**
	 * Add Multiple Quiz Entry
	 *
	 * @access public
	 * @since  1.5
	 *
	 * @param int   $quiz_id Quiz Id.
	 * @param array $entry_metas nested array entry meta.
	 *
	 * @see    Forminator_API::add_form_entry()
	 *
	 * @return array|WP_Error array of entry id or WP_Error
	 */
	public static function add_quiz_entries( $quiz_id, $entry_metas ) {
		// validating quiz module.
		$model = self::get_module( $quiz_id );
		if ( is_wp_error( $model ) ) {
			return $model;
		}

		return self::add_entries( $quiz_id, 'quizzes', $entry_metas );
	}

	/**
	 * Update quiz entry
	 *
	 * @access public
	 * @since  1.5
	 *
	 * @param int   $quiz_id Quiz Id.
	 * @param int   $entry_id Entry Id.
	 * @param array $entry_meta ['name' => 'META_NAME', 'value' => 'META_VALUE'].
	 *
	 * @return bool|WP_Error
	 */
	public static function update_quiz_entry( $quiz_id, $entry_id, $entry_meta ) {
		// validating quiz module.
		$model = self::get_module( $quiz_id );
		if ( is_wp_error( $model ) ) {
			return $model;
		}

		return self::update_entry_meta( $quiz_id, $entry_id, $entry_meta );
	}

	/**
	 * Add Entry
	 *
	 * This function assumed module_id already validated
	 *
	 * @access public
	 * @since  1.5
	 *
	 * @param int    $module_id Module Id.
	 * @param string $entry_type available : `custom-forms`, `poll`, `quizzes`.
	 * @param array  $entry_meta Entry meta data.
	 *
	 * @return int|WP_Error
	 */
	public static function add_entry( $module_id, $entry_type, $entry_meta ) {
		// Initialize API.
		self::initialize();

		$entry_types = array(
			'custom-forms',
			'poll',
			'quizzes',
		);

		if ( ! in_array( $entry_type, $entry_types, true ) ) {
			return new WP_Error( 'invalid_entry_type', esc_html__( 'Invalid entry type.', 'forminator' ) );
		}

		$entry             = new Forminator_Form_Entry_Model();
		$entry->entry_type = $entry_type;
		$entry->form_id    = $module_id;
		$entry_saved       = $entry->save();

		if ( ! $entry_saved || empty( $entry->entry_id ) ) {
			return new WP_Error( 'save_entry_error', esc_html__( 'Failed to save entry.', 'forminator' ) );
		}

		$meta_saved = $entry->set_fields( $entry_meta );

		if ( ! $meta_saved ) {
			return new WP_Error( 'save_entry_meta_error', esc_html__( 'Failed to save entry meta.', 'forminator' ) );
		}

		return $entry->entry_id;
	}

	/**
	 * Add Multiple Entries
	 *
	 * @access public
	 * @since  1.5
	 *
	 * @param int    $module_id Module Id.
	 * @param string $entry_type Entry type.
	 * @param array  $entry_metas Nested entry meta.
	 *
	 * @see    Forminator_API::add_entry()
	 *
	 * @return array|WP_Error
	 */
	public static function add_entries( $module_id, $entry_type, $entry_metas ) {
		$entry_ids = array();
		foreach ( $entry_metas as $entry_meta ) {
			$entry_id = self::add_entry( $module_id, $entry_type, $entry_meta );
			if ( is_wp_error( $entry_id ) ) {
				// return wp_error with data = entry_ids that already added.
				$entry_id->add_data( $entry_ids );

				return $entry_id;
			}
			$entry_ids[] = $entry_id;
		}

		return $entry_ids;
	}

	/**
	 * Update entry meta
	 *
	 * This function will automatically add new meta(s) if it was previously not exists
	 * and also update meta that its value has changed
	 *
	 * @access public
	 * @since  1.5
	 *
	 * @param int   $module_id Module Id.
	 * @param int   $entry_id Entry Id.
	 * @param array $entry_meta Entry Meta.
	 *
	 * @return bool|Forminator_Form_Entry_Model|WP_Error
	 */
	public static function update_entry_meta( $module_id, $entry_id, $entry_meta ) {
		// Initialize API.
		self::initialize();

		// basic entry validation.
		$entry = self::get_entry( $module_id, $entry_id );

		if ( is_wp_error( $entry ) ) {
			return $entry;
		}

		if ( empty( $entry->entry_id ) ) {
			return new WP_Error( 'entry_not_found', esc_html__( 'Entry not found.', 'forminator' ) );
		}

		if ( (int) $module_id !== (int) $entry->form_id ) {
			return new WP_Error( 'entry_not_valid', esc_html__( 'Entry is not valid for module.', 'forminator' ) );
		}

		$current_meta_data = $entry->meta_data;
		$update_entry_meta = array();
		$new_entry_meta    = array();

		foreach ( $entry_meta as $item ) {
			// only process array that has name and value.
			if ( ! isset( $item['name'] ) || ! isset( $item['value'] ) ) {
				continue;
			}

			$key   = $item['name'];
			$value = $item['value'];
			// exists on current ?
			if ( in_array( $key, array_keys( $current_meta_data ), true ) ) {
				// check if value changed.
				$current_meta_data_value = $current_meta_data[ $key ]['value'];
				if ( $current_meta_data_value !== $value ) {
					$update_entry_meta[] = array(
						'id'    => $current_meta_data[ $key ]['id'],
						'name'  => $key,
						'value' => $value,
					);
				}
			} else {
				// new meta.
				$new_entry_meta[] = $item;
			}
		}

		// adding new meta.
		if ( ! empty( $new_entry_meta ) ) {
			$new_meta_saved = $entry->set_fields( $new_entry_meta );
			if ( ! $new_meta_saved ) {
				return new WP_Error( 'save_new_entry_meta_error', esc_html__( 'Failed to save new entry meta.', 'forminator' ), $new_entry_meta );
			}
		}

		// updating existing meta.
		if ( ! empty( $update_entry_meta ) ) {
			$date_updated = date_i18n( 'Y-m-d H:i:s' );
			foreach ( $update_entry_meta as $item ) {
				$entry->update_meta( $item['id'], $item['name'], $item['value'], $date_updated );
			}
		}

		return true;
	}
}
