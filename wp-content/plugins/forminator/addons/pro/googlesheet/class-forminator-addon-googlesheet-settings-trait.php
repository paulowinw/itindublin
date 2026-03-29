<?php
/**
 * Trait for common methods for Googlesheet settings classes
 *
 * @since 1.30
 * @package Googlesheet Integration
 */

/**
 * Trait Forminator_Googlesheet_Settings_Trait
 */
trait Forminator_Googlesheet_Settings_Trait {

	/**
	 * Google Sheets Module Settings wizard
	 *
	 * @since 1.0 Google Sheets Integration
	 * @return array
	 */
	public function module_settings_wizards() {
		$post_data = $this->get_post_data();
		// numerical array steps.
		$steps = array(
			array(
				'callback'     => array( $this, 'pick_name' ),
				'is_completed' => array( $this, 'setup_name_is_completed' ),
			),
			array(
				'callback'     => array( $this, 'setup_spread_sheet' ),
				'is_completed' => array( $this, 'setup_sheet_is_completed' ),
			),
			// @since 1.31 Google Sheets Addon
			array(
				'callback'     => array( $this, 'update_worksheet' ),
				'is_completed' => array( $this, 'setup_sheet_is_completed' ),
			),
		);
		// Remove the step 3 when customer choose new spreadsheet.
		if ( empty( $post_data['change_form_type'] ) && ! empty( $post_data['sheet_type'] ) && 'new' === $post_data['sheet_type'] ) {
			unset( $steps[2] );
		}
		return $steps;
	}

	/**
	 * Get Post data.
	 *
	 * @since 1.31 Google Sheets Addon
	 * @return array
	 */
	private function get_post_data() {
		// Sanitize in Forminator_Core::sanitize_array.
		// phpcs:ignore WordPress.Security
		$post_data = isset( $_POST['data'] ) ? Forminator_Core::sanitize_array( $_POST['data'], 'data' ) : array();

		if ( ! is_array( $post_data ) && is_string( $post_data ) ) {
			$post_string = $post_data;
			$post_data   = array();
			wp_parse_str( $post_string, $post_data );
		}
		return $post_data;
	}

	/**
	 * Setup Connection Name
	 *
	 * @since 1.0 Google Sheets Integration
	 * @param array $submitted_data Submitted data.
	 * @return array
	 * @throws Forminator_Integration_Exception Throws Integration Exception.
	 */
	public function pick_name( $submitted_data ) {
		$template = forminator_addon_googlesheet_dir() . 'views/module-settings/pick-name.php';

		$multi_id = $this->generate_multi_id();
		if ( isset( $submitted_data['multi_id'] ) ) {
			$multi_id = $submitted_data['multi_id'];
		}

		$template_params = array(
			'name'       => $this->get_multi_id_settings( $multi_id, 'name' ),
			'file_id'    => $this->get_multi_id_settings( $multi_id, 'file_id' ),
			'name_error' => '',
			'multi_id'   => $multi_id,
		);

		unset( $submitted_data['multi_id'] );

		$is_submit  = ! empty( $submitted_data );
		$has_errors = false;
		if ( $is_submit ) {
			$name                    = isset( $submitted_data['name'] ) ? $submitted_data['name'] : '';
			$template_params['name'] = $name;

			try {
				if ( empty( $name ) ) {
					throw new Forminator_Integration_Exception( esc_html__( 'Please pick valid name', 'forminator' ) );
				}

				$time_added = $this->get_multi_id_settings( $multi_id, 'time_added', time() );
				$this->save_multi_id_setting_values(
					$multi_id,
					array(
						'name'       => $name,
						'time_added' => $time_added,
					)
				);

			} catch ( Forminator_Integration_Exception $e ) {
				$template_params['name_error'] = $e->getMessage();
				$has_errors                    = true;
			}
		}

		$buttons                   = $this->get_buttons( $multi_id );
		$buttons['next']['markup'] = '<div class="sui-actions-right">' .
			Forminator_Integration::get_button_markup( esc_html__( 'Next', 'forminator' ), 'forminator-addon-next' ) .
			'</div>';

		return array(
			'html'       => Forminator_Integration::get_template( $template, $template_params ),
			'buttons'    => $buttons,
			'redirect'   => false,
			'has_errors' => $has_errors,
		);
	}

	/**
	 * Get buttons.
	 *
	 * @param string $multi_id Id.
	 *
	 * @since 1.31 Google Sheets Addon
	 * @return array
	 */
	private function get_buttons( $multi_id ) {
		$buttons = array();
		if ( $this->setup_name_is_completed( array( 'multi_id' => $multi_id ) ) ) {
			$buttons['disconnect']['markup'] = Forminator_Integration::get_button_markup(
				esc_html__( 'Deactivate', 'forminator' ),
				'sui-button-ghost sui-tooltip sui-tooltip-top-center forminator-addon-form-disconnect',
				esc_html__( 'Deactivate Google Sheets Integration from this module.', 'forminator' )
			);
		}

		return $buttons;
	}

	/**
	 * Setup the spread sheet.
	 *
	 * @since 1.31 Google Sheets Addon
	 * @param array $submitted_data Submitted data.
	 * @return array
	 */
	public function setup_spread_sheet( $submitted_data ) {
		$multi_id   = $submitted_data['multi_id'];
		$sheet_type = $this->get_multi_id_settings( $multi_id, 'sheet_type', 'new' );
		$file_id    = $this->get_multi_id_settings( $multi_id, 'file_id' );
		if ( ! empty( $file_id ) ) {
			$sheet_type = 'existing';
		}
		if ( ! empty( $submitted_data['sheet_type'] ) ) {
			$sheet_type = $submitted_data['sheet_type'];
		}
		if ( ! empty( $submitted_data['change_form_type'] ) ) {
			$sheet_type = 'new' === $submitted_data['sheet_type'] ? 'existing' : 'new';
		}
		if ( 'existing' === $sheet_type ) {
			return $this->setup_existing_sheet( $submitted_data );
		}
		return $this->setup_sheet( $submitted_data );
	}

	/**
	 * Setup an existing Spread sheet tab
	 *
	 * @since 1.31 Google Sheets Addon
	 * @param array $submitted_data Submitted data.
	 * @return array
	 * @throws ForminatorGoogleAddon\Google\Exception Google Exception.
	 */
	public function update_worksheet( $submitted_data ) {
		$template = forminator_addon_googlesheet_dir() . 'views/module-settings/setup-choose-worksheet.php';
		if ( ! isset( $submitted_data['multi_id'] ) ) {
			return $this->get_force_closed_wizard();
		}
		$multi_id = $submitted_data['multi_id'];
		unset( $submitted_data['multi_id'] );
		$file_id            = $this->get_multi_id_settings( $multi_id, 'file_id' );
		$saved_worksheet_id = $this->get_multi_id_settings( $multi_id, 'worksheet_id' );
		if ( '' === $saved_worksheet_id ) {
			$folder_id = $this->get_multi_id_settings( $multi_id, 'folder_id', false );
			if ( false !== $folder_id ) {
				$saved_worksheet_id = 0;
			}
		}
		$worksheet_id    = isset( $submitted_data['worksheet_id'] ) ? $submitted_data['worksheet_id'] : $saved_worksheet_id;
		$template_params = array(
			'worksheet_id'  => $worksheet_id,
			'file_id'       => $file_id,
			'error_message' => '',
			'multi_id'      => $multi_id,
			'worksheets'    => array(),
		);
		$has_errors      = false;
		$is_submit       = isset( $submitted_data['worksheet_id'] );
		$notification    = array();
		$is_close        = false;

		try {
			$input_exceptions = new Forminator_Integration_Settings_Exception();
			$google_client    = $this->addon->get_google_client();
			$google_client->setAccessToken( $this->addon->get_client_access_token() );
			$google_client = $this->addon->refresh_token_if_expired( $google_client );
			if ( ! empty( $file_id ) ) {
				try {
					$service      = new ForminatorGoogleAddon\Google\Service\Sheets( $google_client );
					$spreadsheets = $service->spreadsheets->get( $file_id );
					$sheets       = $spreadsheets->getSheets();
					foreach ( $sheets as $sheet ) {
						$template_params['worksheets'][ $sheet->getProperties()->getSheetId() ] = $sheet->getProperties()->getTitle();
					}
				} catch ( ForminatorGoogleAddon\Google\Exception $google_exception ) {
					// catch 404.
					if ( false !== stripos( $google_exception->getMessage(), 'Requested entity was not found' ) ) {
						$input_exceptions->add_input_exception( esc_html__( 'Spreadsheet not found, please put Spreadsheet ID.', 'forminator' ), 'error_message' );
					} else {
						throw $google_exception;
					}
				}
			}

			if ( $input_exceptions->input_exceptions_is_available() ) {
				throw $input_exceptions;
			}
		} catch ( Forminator_Integration_Settings_Exception $e ) {
			$input_errors    = $e->get_input_exceptions();
			$template_params = array_merge( $template_params, $input_errors );
			$has_errors      = true;
		} catch ( Forminator_Integration_Exception $e ) {
			$template_params['error_message'] = $e->getMessage();
			$has_errors                       = true;
		} catch ( ForminatorGoogleAddon\Google\Exception $e ) {
			$template_params['error_message'] = $e->getMessage();
			$has_errors                       = true;
		}

		if ( $is_submit ) {
			try {
				$input_exceptions = new Forminator_Integration_Settings_Exception();
				if ( '' === $worksheet_id ) {
					$input_exceptions->add_input_exception( esc_html__( 'Please select a Worksheet', 'forminator' ), 'worksheet_id_error' );
					throw $input_exceptions;
				}
				if ( ! in_array( strval( $worksheet_id ), array_map( 'strval', array_keys( $template_params['worksheets'] ) ), true ) ) {
					$input_exceptions->add_input_exception( esc_html__( 'Invalid Worksheet', 'forminator' ), 'worksheet_id_error' );
					throw $input_exceptions;
				}
				$this->save_multi_id_setting_values(
					$multi_id,
					array(
						'worksheet_id' => $worksheet_id,
					)
				);
				$notification = array(
					'type' => 'success',
					'text' => '<strong>' . $this->addon->get_title() . '</strong> ' . esc_html__( 'Successfully connected to your module', 'forminator' ),
				);
				$is_close     = true;
			} catch ( Forminator_Integration_Settings_Exception $e ) {
				$input_errors    = $e->get_input_exceptions();
				$template_params = array_merge( $template_params, $input_errors );
				$has_errors      = true;
			}
		}

		$buttons                   = $this->get_buttons( $multi_id );
		$buttons['next']['markup'] = '<div class="sui-actions-right">' .
			Forminator_Integration::get_button_markup( esc_html__( 'Activate', 'forminator' ), 'forminator-addon-next' ) .
			'</div>';

		return array(
			'html'         => Forminator_Integration::get_template( $template, $template_params ),
			'buttons'      => $buttons,
			'redirect'     => false,
			'has_errors'   => $has_errors,
			'has_back'     => true,
			'notification' => $notification,
			'is_close'     => $is_close,
			'size'         => 'normal',
		);
	}

	/**
	 * Setup an existing Spread sheet
	 *
	 * @since 1.31 Google Sheets Addon
	 * @param array $submitted_data Submitted data.
	 * @return array
	 * @throws ForminatorGoogleAddon\Google\Exception Google Exception.
	 */
	public function setup_existing_sheet( $submitted_data ) {
		$template = forminator_addon_googlesheet_dir() . 'views/module-settings/setup-sheet-existing.php';

		if ( ! isset( $submitted_data['multi_id'] ) ) {
			return $this->get_force_closed_wizard();
		}

		$multi_id = $submitted_data['multi_id'];
		unset( $submitted_data['multi_id'] );

		$template_params = array(
			'worksheet_id'  => $this->get_multi_id_settings( $multi_id, 'worksheet_id', 0 ),
			'file_id'       => $this->get_multi_id_settings( $multi_id, 'file_id' ),
			'error_message' => '',
			'multi_id'      => $multi_id,
		);

		$is_submit    = ! empty( $submitted_data ) && empty( $submitted_data['change_form_type'] );
		$has_errors   = false;
		$notification = array();
		$is_close     = false;
		if ( $is_submit ) {
			$file_id   = isset( $submitted_data['file_id'] ) ? $submitted_data['file_id'] : '';
			$file_name = $this->get_multi_id_settings( $multi_id, 'file_name' );
			try {
				$input_exceptions = new Forminator_Integration_Settings_Exception();
				if ( empty( $file_id ) ) {
					$input_exceptions->add_input_exception( esc_html__( 'Please enter a valid spreadsheet ID', 'forminator' ), 'file_id_error' );
					throw $input_exceptions;
				}
				$template_params['file_id'] = $file_id;
				$google_client              = $this->addon->get_google_client();
				$google_client->setAccessToken( $this->addon->get_client_access_token() );
				$google_client = $this->addon->refresh_token_if_expired( $google_client );
				if ( ! empty( $file_id ) ) {
					try {
						$service      = new ForminatorGoogleAddon\Google\Service\Sheets( $google_client );
						$spreadsheets = $service->spreadsheets->get( $file_id );
						$file_name    = $spreadsheets->getProperties()->getTitle();
						$sheets       = $spreadsheets->getSheets();
						foreach ( $sheets as $sheet ) {
							$template_params['worksheets'][ $sheet->getProperties()->getSheetId() ] = $sheet->getProperties()->getTitle();
						}
					} catch ( ForminatorGoogleAddon\Google\Exception $google_exception ) {
						// catch 404.
						if ( false !== stripos( $google_exception->getMessage(), 'Requested entity was not found' ) ) {
							$input_exceptions->add_input_exception( esc_html__( 'Spreadsheet not found, please enter a valid spreadsheet ID.', 'forminator' ), 'file_id_error' );
						} else {
							throw $google_exception;
						}
					}
				}

				if ( $input_exceptions->input_exceptions_is_available() ) {
					throw $input_exceptions;
				}

				$this->save_multi_id_setting_values(
					$multi_id,
					array(
						'file_name'  => $file_name,
						'file_id'    => $file_id,
						'sheet_type' => 'existing',
					)
				);
			} catch ( Forminator_Integration_Settings_Exception $e ) {
				$input_errors    = $e->get_input_exceptions();
				$template_params = array_merge( $template_params, $input_errors );
				$has_errors      = true;
			} catch ( Forminator_Integration_Exception $e ) {
				$template_params['error_message'] = $e->getMessage();
				$has_errors                       = true;
			} catch ( ForminatorGoogleAddon\Google\Exception $e ) {
				$template_params['error_message'] = $e->getMessage();
				$has_errors                       = true;
			}
		}

		$buttons                   = $this->get_buttons( $multi_id );
		$buttons['next']['markup'] = '<div class="sui-actions-right">' .
			Forminator_Integration::get_button_markup( esc_html__( 'Continue', 'forminator' ), 'forminator-addon-next' ) .
			'</div>';

		return array(
			'html'         => Forminator_Integration::get_template( $template, $template_params ),
			'buttons'      => $buttons,
			'redirect'     => false,
			'has_errors'   => $has_errors,
			'has_back'     => true,
			'notification' => $notification,
			'is_close'     => $is_close,
			'size'         => 'normal',
		);
	}

	/**
	 * Setup Contact List
	 *
	 * @since 1.0 Google Sheets Integration
	 * @param array $submitted_data Submitted data.
	 * @return array
	 * @throws ForminatorGoogleAddon\Google\Exception Throws Google Exception.
	 */
	public function setup_sheet( $submitted_data ) {
		$template = forminator_addon_googlesheet_dir() . 'views/module-settings/setup-sheet.php';

		if ( ! isset( $submitted_data['multi_id'] ) ) {
			return $this->get_force_closed_wizard();
		}

		$multi_id = $submitted_data['multi_id'];
		unset( $submitted_data['multi_id'] );

		$template_params = array(
			'folder_id'      => $this->get_multi_id_settings( $multi_id, 'folder_id' ),
			'file_name'      => $this->get_multi_id_settings( $multi_id, 'file_name' ),
			'spreadsheet_id' => $this->get_multi_id_settings( $multi_id, 'spreadsheet_id' ),
			'file_id'        => $this->get_multi_id_settings( $multi_id, 'file_id' ),
			'error_message'  => '',
			'multi_id'       => $multi_id,
		);

		$is_submit    = ! empty( $submitted_data ) && empty( $submitted_data['change_form_type'] );
		$has_errors   = false;
		$notification = array();
		$is_close     = false;

		if ( $is_submit ) {
			$folder_id                    = isset( $submitted_data['folder_id'] ) ? $submitted_data['folder_id'] : '';
			$template_params['folder_id'] = $folder_id;
			$file_name                    = isset( $submitted_data['file_name'] ) ? $submitted_data['file_name'] : '';
			$template_params['file_name'] = $file_name;

			try {
				$input_exceptions = new Forminator_Integration_Settings_Exception();
				if ( empty( $file_name ) ) {
					$input_exceptions->add_input_exception( esc_html__( 'Please put valid Spreadsheet name', 'forminator' ), 'file_name_error' );
				}

				$google_client = $this->addon->get_google_client();
				$google_client->setAccessToken( $this->addon->get_client_access_token() );
				$google_client = $this->addon->refresh_token_if_expired( $google_client );

				if ( ! empty( $folder_id ) ) {
					$drive = new ForminatorGoogleAddon\Google\Service\Drive( $google_client );
					try {
						$folder = $drive->files->get( $folder_id, array( 'supportsAllDrives' => true ) );
						// its from API var.
						// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
						if ( Forminator_Googlesheet::MIME_TYPE_GOOGLE_DRIVE_FOLDER !== $folder->mimeType ) {
							$input_exceptions->add_input_exception( esc_html__( 'This is not a folder, please use a valid Folder ID.', 'forminator' ), 'folder_id_error' );
						}
					} catch ( ForminatorGoogleAddon\Google\Exception $google_exception ) {
						// catch 404.
						if ( false !== stripos( $google_exception->getMessage(), 'File not found' ) ) {
							$input_exceptions->add_input_exception( esc_html__( 'Folder not found, please put Folder ID.', 'forminator' ), 'folder_id_error' );
						} else {
							throw $google_exception;
						}
					}
				}

				if ( $input_exceptions->input_exceptions_is_available() ) {
					throw $input_exceptions;
				}

				$file = new ForminatorGoogleAddon\Google\Service\Drive\DriveFile();
				$file->setMimeType( Forminator_Googlesheet::MIME_TYPE_GOOGLE_SPREADSHEET );
				$file->setName( $file_name );

				if ( ! empty( $folder_id ) ) {
					$file->setParents( array( $folder_id ) );
				}

				$drive     = new ForminatorGoogleAddon\Google\Service\Drive( $google_client );
				$new_sheet = $drive->files->create( $file, array( 'supportsAllDrives' => true ) );

				$this->save_multi_id_setting_values(
					$multi_id,
					array(
						'folder_id'    => $folder_id,
						'file_name'    => $file_name,
						'file_id'      => $new_sheet->getId(),
						'sheet_type'   => 'new',
						'worksheet_id' => 0,
					)
				);

				$notification = array(
					'type' => 'success',
					'text' => '<strong>' . $this->addon->get_title() . '</strong> ' . esc_html__( 'Successfully created spreadsheet and connected to your module', 'forminator' ),
				);
				$is_close     = true;

			} catch ( Forminator_Integration_Settings_Exception $e ) {
				$input_errors    = $e->get_input_exceptions();
				$template_params = array_merge( $template_params, $input_errors );
				$has_errors      = true;
			} catch ( Forminator_Integration_Exception $e ) {
				$template_params['error_message'] = $e->getMessage();
				$has_errors                       = true;
			} catch ( ForminatorGoogleAddon\Google\Exception $e ) {
				$template_params['error_message'] = $e->getMessage();
				$has_errors                       = true;
			}
		}

		$buttons                   = $this->get_buttons( $multi_id );
		$buttons['next']['markup'] = '<div class="sui-actions-right">' .
			Forminator_Integration::get_button_markup( esc_html__( 'Create', 'forminator' ), 'forminator-addon-next' ) .
			'</div>';

		return array(
			'html'         => Forminator_Integration::get_template( $template, $template_params ),
			'buttons'      => $buttons,
			'redirect'     => false,
			'has_errors'   => $has_errors,
			'has_back'     => true,
			'notification' => $notification,
			'is_close'     => $is_close,
			'size'         => 'normal',
		);
	}

	/**
	 * Check if select contact list completed
	 *
	 * @since 1.0 Google Sheets Integration
	 * @param array $submitted_data Submitted data.
	 * @return bool
	 */
	public function setup_sheet_is_completed( $submitted_data ) {
		return $this->if_properties_exist( $submitted_data, array( 'file_name', 'file_id' ) );
	}
}
