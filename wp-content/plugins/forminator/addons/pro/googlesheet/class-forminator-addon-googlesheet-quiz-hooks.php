<?php
/**
 * Forminator Google sheet Quiz Hooks
 *
 * @package Forminator
 */

/**
 * Class Forminator_Googlesheet_Quiz_Hooks
 *
 * @since 1.6.2
 */
class Forminator_Googlesheet_Quiz_Hooks extends Forminator_Integration_Quiz_Hooks {

	/**
	 * Google sheet column titles
	 */
	const GSHEET_DATE_COLUMN_NAME     = 'Date';
	const GSHEET_QUESTION_COLUMN_NAME = 'Question';
	const GSHEET_ANSWER_COLUMN_NAME   = 'Answer';
	const GSHEET_RESULT_COLUMN_NAME   = 'Result';

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
	 * @since 1.6.2
	 *
	 * @param string $connection_id Connection Id.
	 * @param array  $submitted_data Submitted data.
	 * @param array  $connection_settings Connection settings.
	 * @param array  $quiz_entry_fields Quiz entry fields.
	 *
	 * @return array `is_sent` true means its success send data to Google Sheets, false otherwise.
	 * @throws Forminator_Integration_Exception Throws Integration Exception.
	 */
	public function get_status_on_create_row( $connection_id, $submitted_data, $connection_settings, $quiz_entry_fields ) {
		// initialize as null.
		$api = null;

		$quiz_id                = $this->module_id;
		$quiz_settings_instance = $this->settings_instance;
		$quiz_settings          = $quiz_settings_instance->get_quiz_settings();
		$addons_fields          = $this->settings_instance->get_form_fields();

		try {

			/**
			 * Fires before checking and modifying headers row of googlesheet
			 *
			 * @since 1.6.2
			 *
			 * @param array $connection_settings
			 * @param int $quiz_id current Quiz ID.
			 * @param array $submitted_data
			 * @param array $quiz_entry_fields
			 * @param Forminator_Googlesheet_Quiz_Settings $quiz_settings_instance Google Sheets Integration Quiz Settings instance.
			 */
			do_action( 'forminator_addon_quiz_googlesheet_before_prepare_sheet_headers', $connection_settings, $quiz_id, $submitted_data, $quiz_entry_fields, $quiz_settings_instance );

			$worksheet_id = 0;
			if ( ! empty( $connection_settings['sheet_type'] ) && 'existing' === $connection_settings['sheet_type'] && ! empty( $connection_settings['worksheet_id'] ) ) {
				$worksheet_id = $connection_settings['worksheet_id'];
			}
			// prepare headers.
			$header_fields = $this->get_sheet_headers( $connection_settings['file_id'], $quiz_settings, $worksheet_id );

			/**
			 * Filter Sheet headers fields that will be used to map the entry rows
			 *
			 * @since 1.6.2
			 *
			 * @param array $header_fields sheet headers.
			 * @param array $connection_settings
			 * @param int $quiz_id current Quiz ID.
			 * @param array $submitted_data
			 * @param array $quiz_entry_fields
			 * @param Forminator_Googlesheet_Quiz_Settings $quiz_settings_instance Google Sheets Integration Quiz Settings instance.
			 */
			$header_fields = apply_filters(
				'forminator_addon_quiz_googlesheet_sheet_headers',
				$header_fields,
				$connection_settings,
				$quiz_id,
				$submitted_data,
				$quiz_entry_fields,
				$quiz_settings_instance
			);

			/**
			 * Fires after headers row of googlesheet checked and modified
			 *
			 * @since 1.6.2
			 *
			 * @param array $header_fields sheet headers.
			 * @param array $connection_settings
			 * @param int $quiz_id current Quiz ID.
			 * @param array $submitted_data
			 * @param array $quiz_entry_fields
			 * @param Forminator_Googlesheet_Quiz_Settings $quiz_settings_instance Google Sheets Integration Quiz Settings instance.
			 */
			do_action( 'forminator_addon_quiz_googlesheet_after_prepare_sheet_headers', $header_fields, $connection_settings, $quiz_id, $submitted_data, $quiz_entry_fields, $quiz_settings_instance );

			$answers = array();

			if ( is_array( $quiz_entry_fields ) && isset( $quiz_entry_fields[0] ) ) {
				$quiz_entry = $quiz_entry_fields[0];
				if ( isset( $quiz_entry['name'] ) && isset( $quiz_entry['value'] ) && 'entry' === $quiz_entry['name'] ) {
					if ( is_array( $quiz_entry['value'] ) ) {

						// KNOWLEDGE.
						if ( 'knowledge' === $this->module->quiz_type ) {
							foreach ( $quiz_entry['value'] as $data ) {
								$question   = isset( $data['question'] ) ? $data['question'] : '';
								$answer     = isset( $data['answer'] ) ? $data['answer'] : '';
								$is_correct = isset( $data['isCorrect'] ) ? $data['isCorrect'] : false;

								$answers[] = array(
									'question' => $question,
									'answer'   => $answer,
									'result'   => $is_correct ? esc_html__( 'Correct', 'forminator' ) : esc_html__( 'Incorrect', 'forminator' ),
								);
							}
						} elseif ( 'nowrong' === $this->module->quiz_type ) {
							if ( isset( $quiz_entry['value'][0] )
								&& is_array( $quiz_entry['value'][0] )
								&& isset( $quiz_entry['value'][0]['value'] )
								&& is_array( $quiz_entry['value'][0]['value'] ) ) {

								$quiz_entry = $quiz_entry['value'][0]['value'];

								$result = ( isset( $quiz_entry['result'] ) && isset( $quiz_entry['result']['title'] ) ) ? $quiz_entry['result']['title'] : '';

								$entry_questions = ( isset( $quiz_entry['answers'] ) && is_array( $quiz_entry['answers'] ) ) ? $quiz_entry['answers'] : array();

								foreach ( $entry_questions as $entry_question ) {
									$question = isset( $entry_question['question'] ) ? $entry_question['question'] : '';
									$answer   = isset( $entry_question['answer'] ) ? $entry_question['answer'] : '';

									$answers[] = array(
										'question' => $question,
										'answer'   => $answer,
										'result'   => $result,
									);
								}
							}
						}
					}
				}
			}

			$form_entry_fields = get_addons_lead_form_entry_data( $quiz_settings, $submitted_data, $addons_fields );

			// all avail fields on library.
			$field_types = Forminator_Core::get_field_types();

			// sort by length, so stripos will work by traverse from longest field type first.
			$field_types_strlen = array_map( 'strlen', $field_types );
			array_multisort( $field_types_strlen, $field_types );
			$field_types = array_reverse( $field_types );

			$requests = array();
			$date     = date_i18n( 'Y-m-d H:i:s' );
			foreach ( $answers as $i => $answer ) {
				$values = array();
				foreach ( $header_fields as $column_name => $header_field ) {
					$lead_column_name = explode( '|', $column_name );
					if ( self::GSHEET_DATE_COLUMN_NAME === $column_name ) {
						$value     = new ForminatorGoogleAddon\Google\Service\Sheets\ExtendedValue();
						$cell_data = new ForminatorGoogleAddon\Google\Service\Sheets\CellData();

						// only once.
						$date_value = 0 === $i ? $date : '';
						$value->setStringValue( $date_value );
						$cell_data->setUserEnteredValue( $value );
						$values[] = $cell_data;
					} elseif ( self::GSHEET_QUESTION_COLUMN_NAME === $column_name ) {
						$value     = new ForminatorGoogleAddon\Google\Service\Sheets\ExtendedValue();
						$cell_data = new ForminatorGoogleAddon\Google\Service\Sheets\CellData();
						$value->setStringValue( $answer['question'] );
						$cell_data->setUserEnteredValue( $value );
						$values[] = $cell_data;
					} elseif ( self::GSHEET_ANSWER_COLUMN_NAME === $column_name ) {
						$value     = new ForminatorGoogleAddon\Google\Service\Sheets\ExtendedValue();
						$cell_data = new ForminatorGoogleAddon\Google\Service\Sheets\CellData();
						$value->setStringValue( $answer['answer'] );
						$cell_data->setUserEnteredValue( $value );
						$values[] = $cell_data;
					} elseif ( self::GSHEET_RESULT_COLUMN_NAME === $column_name ) {
						$value     = new ForminatorGoogleAddon\Google\Service\Sheets\ExtendedValue();
						$cell_data = new ForminatorGoogleAddon\Google\Service\Sheets\CellData();

						// only once for nowrong.
						$result_value = $answer['result'];
						if ( 'nowrong' === $this->module->quiz_type ) {
							$result_value = 0 === $i ? $result_value : '';
						}

						$value->setStringValue( $result_value );
						$cell_data->setUserEnteredValue( $value );
						$values[] = $cell_data;
					} elseif ( isset( $lead_column_name[1] ) && array_key_exists( $lead_column_name[1], $form_entry_fields ) ) {
						$field_type       = '';
						$form_column_name = $lead_column_name[1];

						foreach ( $field_types as $type ) {
							if ( false !== stripos( $form_column_name, $type . '-' ) ) {
								$field_type = $type;
								break;
							}
						}

						$meta_value = array();
						// take from entry fields (to be saved).
						if ( isset( $form_entry_fields[ $form_column_name ] ) ) {
							$meta_value = $form_entry_fields[ $form_column_name ];
						}

						forminator_addon_maybe_log( __METHOD__, $field_type, $meta_value );

						$value       = new ForminatorGoogleAddon\Google\Service\Sheets\ExtendedValue();
						$cell_data   = new ForminatorGoogleAddon\Google\Service\Sheets\CellData();
						$field_value = 0 === $i ? $meta_value : '';
						$value->setStringValue( $field_value );
						$cell_data->setUserEnteredValue( $value );
						$values[] = $cell_data;
					} else {
						// unknown column, set empty.
						$value     = new ForminatorGoogleAddon\Google\Service\Sheets\ExtendedValue();
						$cell_data = new ForminatorGoogleAddon\Google\Service\Sheets\CellData();
						$value->setStringValue( '' );
						$cell_data->setUserEnteredValue( $value );
						$values[] = $cell_data;
					}
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
				$requests[] = $request;
			}

			if ( empty( $requests ) ) {
				throw new Forminator_Integration_Exception( esc_html__( 'No data to be send to GoogleSheet', 'forminator' ) );
			}

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
		} catch ( Exception $e ) {
			forminator_addon_maybe_log( __METHOD__, 'Failed to Send to Google Sheets' );

			return array(
				'is_sent'         => false,
				'description'     => $e->getMessage(),
				'connection_name' => $connection_settings['name'],
			);
		}
	}

	/**
	 * Prepare headers of spreadsheet
	 *
	 * @since 1.6.2
	 *
	 * @param string $file_id File Id.
	 * @param array  $quiz_settings Quiz settings.
	 *
	 * @return array
	 * @throws Forminator_Integration_Exception Throws Integration Exception.
	 *
	 * @since 1.31
	 * @param string $worksheet_id Worksheet/tab Id.
	 */
	public function get_sheet_headers( $file_id, $quiz_settings = array(), $worksheet_id = 0 ) {

		$google_client = $this->addon->get_google_client();
		$google_client->setAccessToken( $this->addon->get_client_access_token() );
		$google_client = $this->addon->refresh_token_if_expired( $google_client );

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
				$key_range               = $sheet_title . '!' . Forminator_Googlesheet_Form_Hooks::column_number_to_letter( $column_number ) . '1';
				$header_fields[ $value ] = array(
					'range' => $key_range,
					'value' => $value,
				);
				++$column_number;
				++$columns_filled;
			}
		}

		// dont use translation because it will be used as reference.
		$required_header_columns = array(
			self::GSHEET_DATE_COLUMN_NAME,
			self::GSHEET_QUESTION_COLUMN_NAME,
			self::GSHEET_ANSWER_COLUMN_NAME,
			self::GSHEET_RESULT_COLUMN_NAME,
		);

		if ( isset( $quiz_settings['hasLeads'] ) && $quiz_settings['hasLeads'] ) {
			$form_fields     = $this->lead_settings_instance->get_form_fields();
			$lead_form_field = array();
			if ( ! empty( $form_fields ) ) {
				foreach ( $form_fields as $form_field ) {
					$element_id        = $form_field['element_id'];
					$lead_form_field[] = $form_field['field_label'] . '|' . $element_id;

				}
			}
			$required_header_columns = array_merge( $required_header_columns, $lead_form_field );
		}

		$new_column_count = 0;
		$update_bodies    = array();
		foreach ( $required_header_columns as $required_header_column ) {
			$expected_header_value = $required_header_column;
			if ( ! in_array( $required_header_column, array_keys( $header_fields ), true ) ) {
				// add.
				$new_range = $sheet_title . '!' . Forminator_Googlesheet_Form_Hooks::column_number_to_letter( $column_number ) . '1';

				// update headers map.
				$header_fields[ $required_header_column ] = array(
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
				$header_field = $header_fields[ $required_header_column ];
				if ( $expected_header_value !== $header_field['value'] ) {
					// update headers map.
					$header_fields[ $required_header_column ]['value'] = $expected_header_value;

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
}
