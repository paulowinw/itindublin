<?php
/**
 * Forminator Google sheet form hooks
 *
 * @package Forminator
 */

/**
 * Class Forminator_Googlesheet_Form_Hooks
 *
 * @since 1.0 Google Sheets Integration
 */
class Forminator_Googlesheet_Form_Hooks extends Forminator_Integration_Form_Hooks {

	/**
	 * Return custom entry fields
	 *
	 * @param array $submitted_data Submitted data.
	 * @param array $current_entry_fields Current entry fields.
	 * @return array
	 */
	protected function custom_entry_fields( $submitted_data, $current_entry_fields ): array {
		$addon_setting_values = $this->settings_instance->get_settings_values();
		$data                 = array();

		foreach ( $addon_setting_values as $key => $addon_setting_value ) {
			// save it on entry field, with name `status-$MULTI_ID`, and value is the return result on sending data to Google Sheets.
			if ( $this->settings_instance->is_multi_id_completed( $key ) ) {
				// exec only on completed connection.
				$data[] = array(
					'name'  => 'status-' . $key,
					'value' => $this->get_status_on_create_row( $key, $submitted_data, $addon_setting_value, $current_entry_fields ),
				);
			}
		}

		return $data;
	}

	/**
	 * Get status on create Google Sheets row
	 *
	 * @since 1.0 Google Sheets Integration
	 *
	 * @param string $connection_id Connection Id.
	 * @param array  $submitted_data Submitted data.
	 * @param array  $connection_settings Connection settings.
	 * @param array  $form_entry_fields Form entry fields.
	 *
	 * @return array `is_sent` true means its success send data to Google Sheets, false otherwise.
	 * @throws Forminator_Integration_Exception Throws Integration Exception.
	 */
	public function get_status_on_create_row( $connection_id, $submitted_data, $connection_settings, $form_entry_fields ) {
		// initialize as null.
		$api = null;

		$form_id                = $this->module_id;
		$form_settings_instance = $this->settings_instance;
		$module                 = $form_settings_instance->get_module();

		try {
			/**
			 * Fires before checking and modifying headers row of googlesheet
			 *
			 * @since 1.2
			 *
			 * @param array                                      $connection_settings
			 * @param int                                        $form_id                current Form ID.
			 * @param array                                      $submitted_data
			 * @param array                                      $form_entry_fields
			 * @param Forminator_Googlesheet_Form_Settings $form_settings_instance Google Sheets Integration Form Settings instance.
			 */
			do_action( 'forminator_addon_googlesheet_before_prepare_sheet_headers', $connection_settings, $form_id, $submitted_data, $form_entry_fields, $form_settings_instance );

			$worksheet_id = 0;
			if ( ! empty( $connection_settings['sheet_type'] ) && 'existing' === $connection_settings['sheet_type'] && ! empty( $connection_settings['worksheet_id'] ) ) {
				$worksheet_id = $connection_settings['worksheet_id'];
			}
			// prepare headers.
			$header_fields = $this->get_sheet_headers( $connection_settings['file_id'], $worksheet_id );

			/**
			 * Filter Sheet headers fields that will be used to map the entrt rows
			 *
			 * @since 1.2
			 *
			 * @param array                                      $header_fields          sheet headers.
			 * @param array                                      $connection_settings
			 * @param int                                        $form_id                current Form ID.
			 * @param array                                      $submitted_data
			 * @param array                                      $form_entry_fields
			 * @param Forminator_Googlesheet_Form_Settings $form_settings_instance Google Sheets Integration Form Settings instance.
			 */
			$header_fields = apply_filters(
				'forminator_addon_googlesheet_sheet_headers',
				$header_fields,
				$connection_settings,
				$form_id,
				$submitted_data,
				$form_entry_fields,
				$form_settings_instance
			);

			/**
			 * Fires after headers row of googlesheet checked and modified
			 *
			 * @since 1.2
			 *
			 * @param array                                      $header_fields          sheet headers.
			 * @param array                                      $connection_settings
			 * @param int                                        $form_id                current Form ID.
			 * @param array                                      $submitted_data
			 * @param array                                      $form_entry_fields
			 * @param Forminator_Googlesheet_Form_Settings $form_settings_instance Google Sheets Integration Form Settings instance.
			 */
			do_action( 'forminator_addon_googlesheet_after_prepare_sheet_headers', $header_fields, $connection_settings, $form_id, $submitted_data, $form_entry_fields, $form_settings_instance );

			$keyed_form_entry_fields = array();
			foreach ( $form_entry_fields as $form_entry_field ) {
				if ( isset( $form_entry_field['name'] ) ) {
					$keyed_form_entry_fields[ $form_entry_field['name'] ] = array(
						'id'    => $form_entry_field['name'],
						'value' => $form_entry_field['value'],
					);
				}
			}
			$form_entry_fields = $keyed_form_entry_fields;

			$values = array();
			foreach ( $header_fields as $element_id => $header_field ) {
				$field_type = Forminator_Core::get_field_type( $element_id );
				$field      = $module->get_field( $element_id );

				$meta_value = '';
				// take from entry fields (to be saved).
				if ( isset( $form_entry_fields[ $element_id ] ) ) {
					$meta_value = $form_entry_fields[ $element_id ]['value'];
				} elseif ( isset( $submitted_data[ $element_id ] ) ) {
					// fallback to submitted_data.
					$meta_value = $submitted_data[ $element_id ];
				}
				forminator_addon_maybe_log( __METHOD__, $field_type, $meta_value );

				if ( in_array( $field_type, array( 'paypal', 'stripe', 'stripe-ocs' ), true ) ) {
					if ( isset( $meta_value['amount'] ) ) {
						$meta_value['amount'] = Forminator_Field::get_formatted_amount( $field, $meta_value, $module );
					}
				}
				$form_value = Forminator_Form_Entry_Model::meta_value_to_string( $field_type, $meta_value, false, PHP_INT_MAX, $field );

				// Replace custom_option with actual custom value.
				if ( false !== strpos( $form_value, 'custom_option' ) && isset( $submitted_data[ 'custom-' . $element_id ] ) ) {
					$form_value = str_replace( 'custom_option', $submitted_data[ 'custom-' . $element_id ], $form_value );
				}

				$value     = new ForminatorGoogleAddon\Google\Service\Sheets\ExtendedValue();
				$cell_data = new ForminatorGoogleAddon\Google\Service\Sheets\CellData();

				// Set as a number value only if it is numeric and does not start with 0.
				if ( substr( $form_value, 0, 1 ) !== '0' && is_numeric( $form_value ) ) {
					$value->setNumberValue( $form_value );
				} else {
					$value->setStringValue( $form_value );
				}
				$cell_data->setUserEnteredValue( $value );
				$values[] = $cell_data;
			}

			// Build the RowData.
			$row_data = new ForminatorGoogleAddon\Google\Service\Sheets\RowData();
			$row_data->setValues( $values );

			// Prepare the request.
			$append_request = new ForminatorGoogleAddon\Google\Service\Sheets\AppendCellsRequest();
			$append_request->setSheetId( $worksheet_id );
			$append_request->setRows( $row_data );
			$append_request->setFields( 'userEnteredValue' );

			// Set the request.
			$request = new ForminatorGoogleAddon\Google\Service\Sheets\Request();
			$request->setAppendCells( $append_request );
			// Add the request to the requests array.
			$requests   = array();
			$requests[] = $request;

			// Prepare the update.
			$batch_update_request = new ForminatorGoogleAddon\Google\Service\Sheets\BatchUpdateSpreadsheetRequest(
				array(

					'requests' => $requests,
				)
			);

			$google_client = $this->addon->get_google_client();
			$google_client->setAccessToken( $this->addon->get_client_access_token() );
			$google_client       = $this->addon->refresh_token_if_expired( $google_client );
			$spreadsheet_service = new ForminatorGoogleAddon\Google\Service\Sheets( $google_client );
			$spreadsheet_service->spreadsheets->batchUpdate( $connection_settings['file_id'], $batch_update_request );

			if ( $google_client->getAccessToken() !== $this->addon->get_client_access_token() ) {
				$this->addon->update_client_access_token( $google_client->getAccessToken() );
			}
			forminator_addon_maybe_log( __METHOD__, 'Success Send Data' );

			return array(
				'is_sent'         => true,
				'connection_name' => $connection_settings['name'],
				'description'     => esc_html__( 'Successfully send data to Google Sheets', 'forminator' ),
			);

		} catch ( ForminatorGoogleAddon\Google\Exception $e ) {
			forminator_addon_maybe_log( __METHOD__, 'Failed to Send to Google Sheets' );

			return array(
				'is_sent'         => false,
				'description'     => $e->getMessage(),
				'connection_name' => $connection_settings['name'],
			);
		} catch ( Forminator_Integration_Exception $e ) {
			forminator_addon_maybe_log( __METHOD__, 'Failed to Send to Google Sheets' );

			return array(
				'is_sent'         => false,
				'description'     => $e->getMessage(),
				'connection_name' => $connection_settings['name'],
			);
		}
	}

	/**
	 * Maybe add group fields which were cloned by repeater.
	 *
	 * @param array $form_fields Form fields.
	 * @return array
	 */
	private static function maybe_add_group_cloned_fields( $form_fields ) {
		$prepared_data = Forminator_CForm_Front_Action::$prepared_data;
		if ( empty( $prepared_data ) ) {
			return $form_fields;
		}

		$result       = array();
		$group_fields = array();

		foreach ( $form_fields as $key => $field ) {
			$parent      = $field['parent_group'] ?? '';
			$next_parent = $form_fields[ $key + 1 ]['parent_group'] ?? '';

			$result[] = $field;

			if ( $parent ) {
				$group_fields[ $parent ][] = $field;

				// When leaving a group, append all clones for that group.
				if ( $next_parent !== $parent ) {
					for ( $i = 2; isset( $prepared_data[ $group_fields[ $parent ][0]['element_id'] . '-' . $i ] ); $i++ ) {
						foreach ( $group_fields[ $parent ] as $gf ) {
							$cloned_id = $gf['element_id'] . '-' . $i;
							if ( isset( $prepared_data[ $cloned_id ] ) ) {
								$result[] = array_merge( $gf, array( 'element_id' => $cloned_id ) );
							}
						}
					}
				}
			}
		}

		return $result;
	}

	/**
	 * Prepare headers of spreadsheet
	 *
	 * @param string $file_id File Id.
	 *
	 * @return array
	 * @throws Forminator_Integration_Exception Throws Integration Exception.
	 *
	 * @since 1.31
	 * @param string $worksheet_id Worksheet/tab Id.
	 */
	public function get_sheet_headers( $file_id, $worksheet_id = 0 ) {
		$form_fields = $this->settings_instance->get_form_fields();
		$form_fields = self::maybe_add_group_cloned_fields( $form_fields );

		/**
		 * Filter form fields to be sent to Google Sheets integration
		 *
		 * @since 1.51
		 *
		 * @param array $form_fields Form fields.
		 * @param int $module_id Current Module ID.
		 */
		$form_fields = apply_filters( 'forminator_addon_googlesheet_form_fields', $form_fields, $this->module_id );

		$google_client = $this->addon->get_google_client();
		$google_client->setAccessToken( $this->addon->get_client_access_token() );
		$google_client       = $this->addon->refresh_token_if_expired( $google_client );
		$spreadsheet_service = new ForminatorGoogleAddon\Google\Service\Sheets( $google_client );
		$spreadsheet         = $spreadsheet_service->spreadsheets->get( $file_id );
		$sheets              = $spreadsheet->getSheets();
		$sheet_key           = false;
		if ( ! empty( $sheets ) ) {
			foreach ( $sheets as $key => $sheet ) {
				if ( strval( $sheet->getProperties()->getSheetId() ) === strval( $worksheet_id ) ) {
					$sheet_key = $key;
				}
			}
		}

		if ( false === $sheet_key || ! isset( $sheets[ $sheet_key ] ) || ! isset( $sheets[ $sheet_key ]->properties ) ) {
			throw new Forminator_Integration_Exception( esc_html__( 'No sheet found', 'forminator' ) );
		}

		if ( ! isset( $sheets[ $sheet_key ]->properties->title ) || empty( $sheets[ $sheet_key ]->properties->title ) ) {
			throw new Forminator_Integration_Exception( esc_html__( 'Sheet title not found', 'forminator' ) );
		}

		if ( ! isset( $sheets[ $sheet_key ]->properties->gridProperties ) || ! isset( $sheets[ $sheet_key ]->properties->gridProperties->columnCount ) ) {
			throw new Forminator_Integration_Exception( esc_html__( 'Failed to get column count of the sheet', 'forminator' ) );
		}

		$sheet_title        = $sheets[ $sheet_key ]->properties->title;
		$sheet_column_count = $sheets[ $sheet_key ]->properties->gridProperties->columnCount;

		$headers_range = $sheet_title . '!1:1';
		$header_rows   = $spreadsheet_service->spreadsheets_values->get(
			$spreadsheet->getSpreadsheetId(),
			$headers_range
		);

		$values = $header_rows->getValues();

		forminator_addon_maybe_log( __METHOD__, '$sheet_column_count', $sheet_column_count );

		$header_fields = array();

		$column_number  = 1;
		$columns_filled = 0;
		if ( isset( $values[0] ) && is_array( $values[0] ) ) {
			foreach ( $values[0] as $value ) {
				$key_range = $sheet_title . '!' . self::column_number_to_letter( $column_number ) . '1';
				// forminator header field format = 'FIELD-label|field-id'.
				$header_values                = explode( '|', $value );
				$element_id                   = end( $header_values );
				$header_fields[ $element_id ] = array(
					'range' => $key_range,
					'value' => $value,
				);
				++$column_number;
				++$columns_filled;
			}
		}

		$new_column_count = 0;
		$update_bodies    = array();
		foreach ( $form_fields as $form_field ) {
			$element_id            = $form_field['element_id'];
			$expected_header_value = $form_field['field_label'] . '|' . $element_id;
			if ( ! in_array( $element_id, array_keys( $header_fields ), true ) ) {
				// add.
				$new_range = $sheet_title . '!' . self::column_number_to_letter( $column_number ) . '1';

				// update headers map.
				$header_fields[ $element_id ] = array(
					'range' => $new_range,
					'value' => $expected_header_value,
				);

				// increment for next usage.
				++$column_number;
				$update_body = new ForminatorGoogleAddon\Google\Service\Sheets\ValueRange();
				$update_body->setRange( $new_range );
				$update_body->setValues( array( array( $expected_header_value ) ) );
				$update_bodies[] = $update_body;
				++$new_column_count;
			} else {
				$header_field = $header_fields[ $element_id ];
				if ( $expected_header_value !== $header_field['value'] ) {
					// update headers map.
					$header_fields[ $element_id ]['value'] = $expected_header_value;

					// update sheet.
					$update_body = new ForminatorGoogleAddon\Google\Service\Sheets\ValueRange();
					$update_body->setRange( $header_field['range'] );
					$update_body->setValues( array( array( $expected_header_value ) ) );
					$update_bodies[] = $update_body;
				}
			}
		}

		// calc column to be added.
		$total_column_needed = $columns_filled + $new_column_count;
		$new_column_needed   = $total_column_needed - $sheet_column_count;
		if ( $new_column_needed > 0 ) {
			$dimension_range = new ForminatorGoogleAddon\Google\Service\Sheets\DimensionRange();
			$dimension_range->setSheetId( $worksheet_id );
			$dimension_range->setDimension( 'COLUMNS' );
			$dimension_range->setStartIndex( $sheet_column_count );
			$dimension_range->setEndIndex( $total_column_needed );

			$insert_dimension = new ForminatorGoogleAddon\Google\Service\Sheets\InsertDimensionRequest();
			$insert_dimension->setRange( $dimension_range );
			$insert_dimension->setInheritFromBefore( true );

			$request = new ForminatorGoogleAddon\Google\Service\Sheets\Request();
			$request->setInsertDimension( $insert_dimension );

			$request_body = new ForminatorGoogleAddon\Google\Service\Sheets\BatchUpdateSpreadsheetRequest();
			$request_body->setRequests( array( $request ) );

			$spreadsheet_service->spreadsheets->batchUpdate( $file_id, $request_body );
		}
		if ( ! empty( $update_bodies ) ) {
			$request_body = new ForminatorGoogleAddon\Google\Service\Sheets\BatchUpdateValuesRequest();
			$request_body->setData( $update_bodies );
			$request_body->setValueInputOption( 'RAW' );
			$spreadsheet_service->spreadsheets_values->batchUpdate( $file_id, $request_body );
		}

		$grid_properties = new ForminatorGoogleAddon\Google\Service\Sheets\GridProperties();
		$grid_properties->setFrozenRowCount( 1 );

		$sheet_properties = new ForminatorGoogleAddon\Google\Service\Sheets\SheetProperties();
		$sheet_properties->setSheetId( $worksheet_id );
		$sheet_properties->setGridProperties( $grid_properties );

		$update_properties = new ForminatorGoogleAddon\Google\Service\Sheets\UpdateSheetPropertiesRequest();
		$update_properties->setProperties( $sheet_properties );
		$update_properties->setFields( 'gridProperties(frozenRowCount)' );

		$request = new ForminatorGoogleAddon\Google\Service\Sheets\Request();
		$request->setUpdateSheetProperties( $update_properties );

		$request_body = new ForminatorGoogleAddon\Google\Service\Sheets\BatchUpdateSpreadsheetRequest();
		$request_body->setRequests( array( $request ) );

		$spreadsheet_service->spreadsheets->batchUpdate( $file_id, $request_body );

		if ( $google_client->getAccessToken() !== $this->addon->get_client_access_token() ) {
			$this->addon->update_client_access_token( $google_client->getAccessToken() );
		}

		return $header_fields;
	}

	/**
	 * Convert column number to letter format for spreadsheet
	 *
	 * Start from 1
	 *
	 * @param int $column_number Column Number.
	 *
	 * @return string
	 */
	public static function column_number_to_letter( $column_number ) {
		$chars = array(
			'A',
			'B',
			'C',
			'D',
			'E',
			'F',
			'G',
			'H',
			'I',
			'J',
			'K',
			'L',
			'M',
			'N',
			'O',
			'P',
			'Q',
			'R',
			'S',
			'T',
			'U',
			'V',
			'W',
			'X',
			'Y',
			'Z',
		);

		/**
		 * 1 = A
		 * 27 = AA
		 */
		$base               = 26;
		$temp_number        = $column_number;
		$output_column_name = '';
		while ( $temp_number > 0 ) {
			$position = $temp_number % $base;
			if ( 0 === $position ) {
				$output_column_name = 'Z' . $output_column_name;
			} elseif ( $position > 0 ) {
					$output_column_name = $chars[ $position - 1 ] . $output_column_name;
			} else {
				$output_column_name = $chars[0] . $output_column_name;
			}
			--$temp_number;
			$temp_number = floor( $temp_number / $base );
		}

		return $output_column_name;
	}
}
