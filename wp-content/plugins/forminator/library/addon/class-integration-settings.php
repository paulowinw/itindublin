<?php
/**
 * The Forminator_Integration_Settings class.
 *
 * @package Forminator
 */

/**
 * Class Forminator_Integration_Settings
 * Any change(s) to this file is subject to:
 * - Properly Written DocBlock! (what is this, why is that, how to be like those, etc, as long as you want!)
 * - Properly Written Changelog!
 *
 * @since 1.1
 */
abstract class Forminator_Integration_Settings {

	/**
	 * Current Module ID
	 *
	 * @since 1.1
	 * @var int
	 */
	protected $module_id;

	/**
	 * Integration instance
	 *
	 * @since 1.1
	 * @var Forminator_Integration
	 */
	protected $addon;

	/**
	 * Module settings for addon
	 *
	 * @since 1.1
	 * @var array
	 */
	protected $addon_settings = array();

	/**
	 * Get HTML select-options
	 *
	 * @param object $options Options.
	 * @param string $selected_value Saved value.
	 * @return string
	 */
	public static function get_select_html( $options, $selected_value = '' ) {
		$html = '<option value="">' . esc_html__( 'None', 'forminator' ) . '</option>';

		foreach ( $options as $id => $title ) {
			$html .= '<option value="' . esc_attr( $id ) . '" ' . selected(
				$selected_value,
				$id,
				false
			) . '>' . esc_html( $title ) . '</option>';
		}

		return $html;
	}

	/**
	 * Get HTML checkbox-options
	 *
	 * @param object $options Options.
	 * @param string $name Name attribute.
	 * @param array  $selected_values Saved values.
	 * @return string
	 */
	public static function get_checkboxes_html( $options, $name, $selected_values = array() ) {
		$html = '';

		foreach ( $options as $id => $title ) {
			$html .= '<label for="' . esc_attr( $id ) . '" class="sui-checkbox sui-checkbox-sm sui-checkbox-stacked">' .
				'<input id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" type="checkbox" value="' . esc_attr( $id ) . '"' .
				checked( is_array( $selected_values ) && in_array( $id, $selected_values, true ), true, false ) .
				'><span aria-hidden="true"></span><span>' . esc_html( $title ) . '</span></label>';
		}

		return $html;
	}

	/**
	 * Get HTML radio-options
	 *
	 * @param object $options Options.
	 * @param string $name Name attribute.
	 * @param array  $selected_value Saved values.
	 * @return string
	 */
	public static function get_radios_html( $options, $name, $selected_value = '' ) {
		$html = '';

		foreach ( $options as $id => $title ) {
			$html .= '<label for="' . esc_attr( $id ) . '" class="sui-radio sui-radio-sm sui-radio-stacked">' .
				'<input id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" type="radio" value="' . esc_attr( $id ) . '"' .
				checked( $id === $selected_value, true, false ) .
				'><span aria-hidden="true"></span><span>' . esc_html( $title ) . '</span></label>';
		}

		return $html;
	}

	/**
	 * Get HTML for refresh button
	 *
	 * @return string
	 */
	public static function refresh_button() {
		$html = '<button class="sui-button-icon sui-tooltip forminator-refresh-email-lists" data-tooltip="'
				. esc_html__( 'Refresh list', 'forminator' ) . '" type="button">'
				. '<span class="sui-loading-text" aria-hidden="true">'
				. '<i class="sui-icon-refresh"></i>'
				. '</span>'
				. '<i class="sui-icon-loader sui-loading" aria-hidden="true"></i>'
				. '<span class="sui-screen-reader-text">' . esc_html__( 'Refresh', 'forminator' ) . '</span>'
				. '</button>';

		return $html;
	}

	/**
	 * Meta key that will be used to save addon setting on WP post_meta
	 *
	 * @return string
	 */
	final public function get_settings_meta_key() {
		$addon     = $this->addon;
		$global_id = ! empty( $addon->multi_global_id ) ? '_' . $addon->multi_global_id : '';
		return 'forminator_addon_' . $addon->get_slug() . '_' . static::$module_slug . '_settings' . $global_id;
	}

	/**
	 * Replace '-' to '_' in keys because some integrations don't support dashes like tray.io and workato.
	 *
	 * @param array  $array_value Original array.
	 * @param string $endpoint Endpoint URL.
	 */
	public static function replace_dashes_in_keys( $array_value, $endpoint ) {
		// don't do it for zapier for backward compatibility.
		if ( strpos( $endpoint, 'zapier' ) ) {
			return $array_value;
		}

		foreach ( $array_value as $key => $value ) {
			if ( is_array( $value ) ) {
				// Replace it recursively.
				$value = self::replace_dashes_in_keys( $value, $endpoint );
			}
			unset( $array_value[ $key ] );
			$new_key                 = str_replace( '-', '_', $key );
			$array_value[ $new_key ] = $value;
		}

		return $array_value;
	}

	/**
	 * Get HTML for GDPR fields
	 *
	 * @param array $current_data Saved data.
	 * @return string
	 */
	protected static function gdpr_fields_html( $current_data ) {
		return '<div class="sui-form-field">' .
			'<label class="sui-label">' . esc_html__( 'Enable GDPR', 'forminator' ) . '</label>
			<input type="checkbox" name="enable_gdpr" value="1" ' . checked( 1, $current_data['enable_gdpr'], false ) . '>
		</div>

		<div class="sui-form-field">
			<label class="sui-label">' . esc_html__( 'GDPR Text', 'forminator' ) . '</label>
			<textarea name="gdpr_text">' . wp_kses_post( $current_data['gdpr_text'] ) . '</textarea>
		</div>';
	}

	/**
	 * Step mapping fields on wizard
	 *
	 * @param array $submitted_data Submitted data.
	 * @return array
	 */
	public function get_map_fields( $submitted_data ) {
		$is_close              = false;
		$is_submit             = ! empty( $submitted_data );
		$error_message         = '';
		$html_input_map_fields = '';
		$input_error_messages  = array();

		try {
			$fields_map      = array();
			$fields_list     = $this->get_addon_custom_fields();
			$fields_list_ids = wp_list_pluck( $fields_list, 'id' );

			foreach ( $fields_list_ids as $key ) {
				$fields_map[ $key ] = $submitted_data['fields_map'][ $key ] ?? $this->addon_settings['fields_map'][ $key ] ?? '';
			}

			/** Build table map fields input */
			$html_input_map_fields = $this->get_input_map_fields( $fields_list, $fields_map );

			if ( $is_submit ) {
				$this->step_map_fields_validate( $fields_list, $submitted_data );
				$this->save_module_settings_values();
				$is_close = true;
			}
		} catch ( Forminator_Integration_Settings_Exception $e ) {
			$input_error_messages = $e->get_input_exceptions();
			if ( ! empty( $html_input_map_fields ) ) {
				foreach ( $input_error_messages as $input_id => $message ) {
					if ( is_array( $message ) ) {
						foreach ( $message as $addr => $m ) {
							$html_input_map_fields = str_replace( '{{$error_css_class_' . $input_id . '_' . $addr . '}}', 'sui-form-field-error', $html_input_map_fields );
							$html_input_map_fields = str_replace( '{{$error_message_' . $input_id . '_' . $addr . '}}', '<span class="sui-error-message">' . esc_html( $m ) . '</span>', $html_input_map_fields );
						}
					} else {
						$html_input_map_fields = str_replace( '{{$error_css_class_' . $input_id . '}}', 'sui-form-field-error', $html_input_map_fields );
						$html_input_map_fields = str_replace( '{{$error_message_' . $input_id . '}}', '<span class="sui-error-message">' . esc_html( $message ) . '</span>', $html_input_map_fields );
					}
				}
			}
		} catch ( Forminator_Integration_Exception $e ) {
			$error_message = $e->get_error_notice();
		}

		// cleanup map fields input markup placeholder.
		if ( ! empty( $html_input_map_fields ) ) {
			$replaced_html_input_map_fields = $html_input_map_fields;
			$replaced_html_input_map_fields = preg_replace( '/\{\{\$error_css_class_(.+)\}\}/', '', $replaced_html_input_map_fields );
			$replaced_html_input_map_fields = preg_replace( '/\{\{\$error_message_(.+)\}\}/', '', $replaced_html_input_map_fields );
			if ( ! is_null( $replaced_html_input_map_fields ) ) {
				$html_input_map_fields = $replaced_html_input_map_fields;
			}
		}

		$buttons = array(
			'cancel' => array(
				'markup' => Forminator_Integration::get_button_markup( esc_html__( 'Back', 'forminator' ), 'sui-button-ghost forminator-addon-back' ),
			),
			'next'   => array(
				'markup' => '<div class="sui-actions-right">' .
					Forminator_Integration::get_button_markup( esc_html__( 'Save', 'forminator' ), 'sui-button-primary forminator-addon-finish' ) .
				'</div>',
			),
		);

		$notification = array();

		if ( $is_submit && empty( $error_message ) && empty( $input_error_messages ) ) {
			$notification = array(
				'type' => 'success',
				'text' => '<strong>' . $this->addon->get_title() . '</strong> ' . esc_html__( 'is activated successfully.', 'forminator' ),
			);
		}

		$html  = '<div class="forminator-integration-popup__header">';
		$html .= '<h3 id="dialogTitle2" class="sui-box-title sui-lg" style="overflow: initial; text-overflow: none; white-space: normal;">' . esc_html__( 'Map Fields', 'forminator' ) . '</h3>';
		$html .= '<p class="sui-description">' . esc_html__( 'Lastly, match up your module fields with the campaign fields to ensure that the data is sent to the right place.', 'forminator' ) . '</p>';
		$html .= $error_message;
		$html .= '</div>';
		$html .= '<form enctype="multipart/form-data">';
		$html .= $html_input_map_fields;
		$html .= '</form>';

		return array(
			'html'         => $html,
			'redirect'     => false,
			'is_close'     => $is_close,
			'buttons'      => $buttons,
			'has_errors'   => ! empty( $error_message ) || ! empty( $input_error_messages ),
			'notification' => $notification,
			'size'         => 'normal',
			'has_back'     => true,
		);
	}

	/**
	 * Validate submitted data by user as expected by merge field on addon mail list
	 *
	 * @param array $addon_fields_list List of Mailjet fields.
	 * @param array $post_data POST data.
	 *
	 * @return array current addon form settings
	 * @throws Forminator_Integration_Exception When there is an integration error.
	 */
	public function step_map_fields_validate( $addon_fields_list, $post_data ) {
		$form_fields                  = $this->get_fields_for_type();
		$forminator_field_element_ids = wp_list_pluck( $form_fields, 'element_id' );

		$tag_mapped_addon_fields = array();
		$addon_required_fields   = array();
		foreach ( $addon_fields_list as $item ) {
			$tag_mapped_addon_fields[ $item->id ] = $item;
			if ( ! empty( $item->required ) ) {
				$addon_required_fields[] = $item;
			}
		}

		if ( ! isset( $post_data['fields_map'] ) ) {
			throw new Forminator_Integration_Exception( esc_html__( 'Please assign fields.', 'forminator' ) );
		}
		$post_data = $post_data['fields_map'];

		if ( ! isset( $this->addon_settings['fields_map'] ) ) {
			$this->addon_settings['fields_map'] = array();
		}

		// set fields_map from post_data for reuse.
		foreach ( $post_data as $field_tag => $forminator_field_id ) {
			$this->addon_settings['fields_map'][ $field_tag ] = $post_data[ $field_tag ];
		}

		$input_exceptions = new Forminator_Integration_Settings_Exception();
		// check required fields fulfilled.
		foreach ( $addon_required_fields as $required_addon_field ) {
			if ( empty( $post_data[ $required_addon_field->id ] ) ) {
				$input_exceptions->add_input_exception(
				/* translators: %s: Required field name */
					sprintf( esc_html__( '%s is required, please choose valid Forminator field.', 'forminator' ), esc_html( $required_addon_field->name ) ),
					$required_addon_field->id
				);
			}
		}

		// Check availability on forminator field.
		foreach ( $this->addon_settings['fields_map'] as $field_tag => $forminator_field_id ) {
			if ( empty( $forminator_field_id ) ) {
				continue;
			}
			$addon_field = $tag_mapped_addon_fields[ $field_tag ];

			// If required field is empty or value is not in forminator field list.
			if ( ! in_array( $forminator_field_id, $forminator_field_element_ids, true ) ) {
				$input_exceptions->add_input_exception(
				/* translators: %s: Integration Field name */
					sprintf( esc_html__( 'Please choose valid Forminator field for %s.', 'forminator' ), esc_html( $addon_field->name ) ),
					$field_tag
				);
			}
		}

		if ( $input_exceptions->input_exceptions_is_available() ) {
			throw $input_exceptions;
		}

		return $this->addon_settings;
	}

	/**
	 * Save module settings
	 *
	 * @param type $addon_settings Integration settings.
	 */
	final public function save_module_settings_values( $addon_settings = null ) {
		if ( is_null( $addon_settings ) ) {
			$addon_settings = $this->addon_settings;
		}

		$addon_slug = $this->addon->get_slug();
		$module_id  = $this->module_id;

		/**
		 * Filter form settings data to be save to db
		 *
		 * @since 1.1
		 *
		 * @param mixed $addon_settings  current addon settings values.
		 * @param int   $module_id current module id.
		 */
		$addon_settings = apply_filters( 'forminator_addon_' . $addon_slug . '_save_' . static::$module_slug . '_settings_values', $addon_settings, $module_id );
		update_post_meta( $module_id, $this->get_settings_meta_key(), forminator_sanitize_array_field( $addon_settings ) );
	}

	/**
	 * Get fields for specific field type.
	 *
	 * @param string $type Field type.
	 * @return array
	 */
	protected function get_fields_for_type( $type = '' ) {
		if ( in_array( $type, array( 'email', 'upload' ), true ) ) {
			// find email type fields.
			$specific_fields = array();
			foreach ( $this->form_fields as $form_field ) {
				if ( $type === $form_field['type'] ) {
					$specific_fields[] = $form_field;
				}
			}

			return $specific_fields;
		} else {
			return $this->form_fields;
		}
	}

	/**
	 * Get HTML for header on Choose List step.
	 *
	 * @param string $error_message Error message.
	 * @return array
	 */
	protected static function get_choose_list_header( $error_message ) {
		$html  = '<div class="forminator-integration-popup__header">';
		$html .= '<h3 id="dialogTitle2" class="sui-box-title sui-lg" style="overflow: initial; text-overflow: none; white-space: normal;">' . esc_html__( 'Choose contact list', 'forminator' ) . '</h3>';
		$html .= '<p class="sui-description">' . esc_html__( 'Choose the contact list you want to send form data to', 'forminator' ) . '</p>';
		$html .= wp_kses_post( $error_message );
		$html .= '</div>';

		return $html;
	}

	/**
	 * Get HTML for Choose list field
	 *
	 * @param array  $current_data Saved data.
	 * @param array  $lists Lists.
	 * @param string $list_error Error messages.
	 * @return string
	 */
	protected static function get_choose_list_field( $current_data, $lists, $list_error ) {
		$html = '<div class="sui-form-field' . ( $list_error ? ' sui-form-field-error' : '' ) . '" style="margin-bottom: 10px;">
			<label class="sui-label">' . esc_html__( 'Contact list', 'forminator' ) . '</label>
			<div class="forminator-select-refresh">
				<select name="mail_list_id" class="sui-select">' .
					self::get_select_html( $lists, $current_data['mail_list_id'] )
				. '</select>' .
				self::refresh_button()
			. '</div>';
		if ( $list_error ) {
			$html .= '<span class="sui-error-message">' . esc_html( $list_error ) . '</span>';
		}
		$html .= '</div>';

		return $html;
	}

	/**
	 * Get HTML for Double Opt-in field
	 *
	 * @param array $current_data Saved data.
	 * @return string
	 */
	protected static function get_double_optin_field( $current_data ) {
		$html  = '<div class="sui-form-field">';
		$html .= '<label class="sui-toggle">';
		$html .= '<input type="checkbox" name="enable_double_opt_in" value="1" id="forminator_addon_enable_double_opt_in" ' . checked( 1, $current_data['enable_double_opt_in'], false ) . ' />';
		$html .= '<span class="sui-toggle-slider"></span>';
		$html .= '<span class="sui-toggle-label">' . esc_html__( 'Use Double Opt in', 'forminator' ) . '</span>';
		$html .= '</label>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * Override this function to generate multiple id on module settings
	 * Default is the uniqid
	 *
	 * @since 1.2
	 * @return string
	 */
	public function generate_multi_id() {
		return uniqid( $this->addon->get_slug() . '_', true );
	}

	/**
	 * Check if connection to module completed
	 *
	 * @since 1.0
	 * @param string $multi_id Multi ID.
	 *
	 * @return bool
	 */
	public function is_multi_id_completed( string $multi_id ): bool {
		$data  = array( 'multi_id' => $multi_id );
		$steps = wp_list_pluck( $this->module_settings_wizards(), 'is_completed' );

		foreach ( $steps as $step ) {
			$is_completed = call_user_func( $step, $data );
			if ( ! $is_completed ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Get Module settings value
	 * its already hooked with
	 *
	 * @see   before_get_settings_values
	 *
	 * @since 1.1
	 *
	 * @return array
	 */
	final public function get_settings_values() {
		// get single meta key.
		$values = get_post_meta( $this->module_id, $this->get_settings_meta_key(), true );

		if ( ! $values ) {
			$values = array();
		}

		$addon_slug = $this->addon->get_slug();
		$module_id  = $this->module_id;

		/**
		 * Filter retrieved module settings data from db
		 *
		 * @since 1.1
		 *
		 * @param mixed $values
		 * @param int   $module_id current module id.
		 */
		$values = apply_filters( 'forminator_addon_' . $addon_slug . '_get_' . static::$module_slug . '_settings_values', $values, $module_id );

		return $values;
	}

	/**
	 * Force Close Wizard with message
	 *
	 * @since 1.2
	 *
	 * @return array
	 */
	public function get_force_closed_wizard() {
		return array(
			'html'         => '',
			'buttons'      => '',
			'is_close'     => true,
			'redirect'     => false,
			'has_errors'   => false,
			'has_back'     => false,
			'notification' => array(
				'type' => 'error',
				'text' => '<strong>' . $this->addon->get_title() . '</strong> ' . esc_html__( 'Please pick valid connection', 'forminator' ),
			),
		);
	}

	/**
	 * Disconnect Module with this addon
	 * Override when needed
	 *
	 * @since 1.1
	 *
	 * @param array $submitted_data Submitted data.
	 */
	public function disconnect_module( $submitted_data ) {
		$addon_settings = array();
		// only execute if multi_id provided on submitted data.
		if ( ! empty( $submitted_data['multi_id'] ) ) {
			$addon_settings = $this->get_settings_values();
			unset( $addon_settings[ $submitted_data['multi_id'] ] );
		}
		$this->save_module_settings_values( $addon_settings );
	}

	/**
	 * Override this function to retrieve your multiple ids on module settings
	 * Default is the array keys as id and label of settings_values
	 *
	 * @return array
	 */
	public function get_multi_ids(): array {
		$multi_ids       = array();
		$settings_values = $this->get_settings_values();
		foreach ( $settings_values as $key => $value ) {
			$multi_ids[] = array(
				'id'    => $key,
				// use name that was added by user on creating connection.
				'label' => $value['name'] ?? $key,
			);
		}

		/**
		 * Filter labels of multi_id on integrations tab
		 *
		 * @param array $multi_ids
		 * @param array $settings_values
		 */
		return apply_filters( 'forminator_addon_' . static::$module_slug . '_' . $this->addon->get_slug() . '_multi_id_labels', $multi_ids, $settings_values );
	}

	/**
	 * Get multi Setting value of multi_id
	 * Override when needed
	 *
	 * @since 1.2
	 *
	 * @param int    $multi_id Multi Id.
	 * @param string $key key.
	 * @param mixed  $default_value Default value.
	 *
	 * @return mixed|string
	 */
	public function get_multi_id_settings( $multi_id, $key, $default_value = '' ) {
		$this->addon_settings = $this->get_settings_values();
		if ( isset( $this->addon_settings[ $multi_id ] ) ) {
			$multi_settings = $this->addon_settings[ $multi_id ];
			if ( isset( $multi_settings[ $key ] ) ) {
				return $multi_settings[ $key ];
			}

			return $default_value;
		}

		return $default_value;
	}

	/**
	 * Properties exist
	 *
	 * @param mixed $submitted_data Submitted data.
	 * @param mixed $properties Properties.
	 * @return bool
	 */
	protected function if_properties_exist( $submitted_data, $properties ) {
		$multi_id = $submitted_data['multi_id'] ?? '';

		if ( empty( $multi_id ) ) {
			return false;
		}
		$properties = (array) $properties;
		foreach ( $properties as $property_name ) {
			$property = $this->get_multi_id_settings( $multi_id, $property_name );

			if ( is_string( $property ) ) {
				$property = trim( $property );
			}

			if ( empty( $property ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Check if pick name step completed
	 *
	 * @since 1.0
	 * @param array $submitted_data Submitted data.
	 * @return bool
	 */
	public function setup_name_is_completed( $submitted_data ) {
		return $this->if_properties_exist( $submitted_data, 'name' );
	}

	/**
	 * Check if setup list completed
	 *
	 * @since 1.0
	 * @param array $submitted_data Submitted data.
	 * @return bool
	 */
	public function select_list_is_completed( $submitted_data ) {
		return $this->if_properties_exist( $submitted_data, 'list_id' );
	}

	/**
	 * Append multi settings or replace multi settings
	 *
	 * @since 1.2
	 *
	 * @param int   $multi_id Multi id.
	 * @param array $settings Settings.
	 * @param bool  $replace Replace.
	 */
	public function save_multi_id_setting_values( $multi_id, $settings, $replace = false ) {
		$this->addon_settings = $this->get_settings_values();

		// merge old values if not replace.
		if ( isset( $this->addon_settings[ $multi_id ] ) && ! $replace ) {
			$current_settings = $this->addon_settings[ $multi_id ];
			$settings         = array_merge( $current_settings, $settings );
		}

		$this->addon_settings = array_merge(
			$this->addon_settings,
			array(
				$multi_id => $settings,
			)
		);
		$this->save_module_settings_values();
	}

	/**
	 * Find one active connection on current module
	 * Override when needed
	 *
	 * @since 1.2
	 * @return bool|array false on no connection, or settings on available
	 */
	public function find_one_active_connection() {
		$addon_settings = $this->get_settings_values();

		foreach ( $addon_settings as $multi_id => $addon_setting ) {
			if ( true === $this->is_multi_id_completed( $multi_id ) ) {
				return $addon_setting;
			}
		}

		return false;
	}

	/**
	 * Has lead
	 *
	 * @return bool
	 */
	public function has_lead() {
		return false;
	}

	/**
	 * Get input of Map Fields
	 * its table with html select options as input
	 *
	 * @param array $addon_fields Integration fields.
	 * @param array $fields_map Fields map.
	 * @return string HTML table
	 */
	protected function get_input_map_fields( $addon_fields, $fields_map ) {
		ob_start();
		?>
		<table class="sui-table">
			<thead>
			<tr>
				<th><?php esc_html_e( 'Integration Fields', 'forminator' ); ?></th>
				<th><?php esc_html_e( 'Forminator Field', 'forminator' ); ?></th>
			</tr>
			</thead>
			<tbody>
			<?php
			foreach ( $addon_fields as $item ) {
				$type       = $item->type ?? $item->datatype;
				$all_fields = $this->get_fields_for_type( $type );
				?>
				<tr>
					<td><?php echo esc_html( $item->name ); ?>
						<?php if ( ! empty( $item->required ) ) { ?>
							<span class="integrations-required-field">*</span>
						<?php } ?>
					</td>
					<td>
						<div class="sui-form-field {{$error_css_class_<?php echo esc_attr( $item->id ); ?>}}">
							<select class="sui-select" name="fields_map[<?php echo esc_attr( $item->id ); ?>]">
								<?php if ( empty( $item->required ) || empty( $all_fields ) ) { ?>
									<option value=""><?php esc_html_e( 'None', 'forminator' ); ?></option>
								<?php } ?>
								<?php foreach ( $all_fields as $form_field ) { ?>
									<option value="<?php echo esc_attr( $form_field['element_id'] ); ?>"
										<?php selected( $fields_map[ $item->id ], $form_field['element_id'] ); ?>>
										<?php echo esc_html( wp_strip_all_tags( $form_field['field_label'] ) . ' | ' . $form_field['element_id'] ); ?>
									</option>
								<?php } ?>
							</select>
							{{$error_message_<?php echo esc_attr( $item->id ); ?>}}
						</div>
					</td>
				</tr>
				<?php
			}
			?>
			</tbody>
		</table>
		<?php

		return ob_get_clean();
	}

	/**
	 * Get additional addon fields.
	 *
	 * @return array
	 */
	protected function get_additional_addon_fields() {
		return array(
			'email' => (object) array(
				'name'     => __( 'Email', 'forminator' ),
				'id'       => 'email',
				'type'     => 'email',
				'required' => true,
			),
		);
	}

	/**
	 * Get custom addon fields.
	 *
	 * @return array
	 */
	protected function get_addon_custom_fields() {
		$additional_fields = $this->get_additional_addon_fields();
		$fields_list       = $this->addon->get_api()->get_contact_properties();
		return $additional_fields + $fields_list;
	}

	/**
	 * Get prepared array of Integration lists
	 *
	 * @return array
	 */
	protected function get_prepared_lists(): array {
		try {
			$lists = $this->addon->get_api()->get_all_lists();
			$lists = wp_list_pluck( $lists, 'name', 'id' );
		} catch ( Forminator_Integration_Exception $e ) {
			forminator_addon_maybe_log( __METHOD__, $e->getMessage() );
			return array();
		}

		return $lists;
	}
}
