<?php
/**
 * Copyright (C) 2014-2025 ServMask Inc.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * Attribution: This code is part of the All-in-One WP Migration plugin, developed by
 *
 * ███████╗███████╗██████╗ ██╗   ██╗███╗   ███╗ █████╗ ███████╗██╗  ██╗
 * ██╔════╝██╔════╝██╔══██╗██║   ██║████╗ ████║██╔══██╗██╔════╝██║ ██╔╝
 * ███████╗█████╗  ██████╔╝██║   ██║██╔████╔██║███████║███████╗█████╔╝
 * ╚════██║██╔══╝  ██╔══██╗╚██╗ ██╔╝██║╚██╔╝██║██╔══██║╚════██║██╔═██╗
 * ███████║███████╗██║  ██║ ╚████╔╝ ██║ ╚═╝ ██║██║  ██║███████║██║  ██╗
 * ╚══════╝╚══════╝╚═╝  ╚═╝  ╚═══╝  ╚═╝     ╚═╝╚═╝  ╚═╝╚══════╝╚═╝  ╚═╝
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Kangaroos cannot jump here' );
}

class Ai1wm_Backups_Controller {

	public static function index() {
		Ai1wm_Template::render(
			'backups/index',
			array(
				'backups'      => Ai1wm_Backups::get_files(),
				'labels'       => Ai1wm_Backups::get_labels(),
				'downloadable' => Ai1wm_Backups::are_downloadable(),
			)
		);
	}

	public static function clean( $params = array() ) {
		ai1wm_setup_environment();

		// Set params
		if ( empty( $params ) ) {
			$params = stripslashes_deep( $_POST );
		}

		// Set secret key
		$secret_key = null;
		if ( isset( $params['secret_key'] ) ) {
			$secret_key = trim( $params['secret_key'] );
		}

		try {
			// Ensure that unauthorized people cannot access backups list action
			ai1wm_verify_secret_key( $secret_key );
		} catch ( Ai1wm_Not_Valid_Secret_Key_Exception $e ) {
			exit;
		}

		// Delete storage files
		Ai1wm_Directory::delete( ai1wm_storage_path( $params ) );
		exit;
	}

	public static function delete( $params = array() ) {
		ai1wm_setup_environment();

		// Set params
		if ( empty( $params ) ) {
			$params = stripslashes_deep( $_POST );
		}

		// Set secret key
		$secret_key = null;
		if ( isset( $params['secret_key'] ) ) {
			$secret_key = trim( $params['secret_key'] );
		}

		// Set archive
		$archive = null;
		if ( isset( $params['archive'] ) ) {
			$archive = trim( $params['archive'] );
		}

		try {
			// Ensure that unauthorized people cannot access delete action
			ai1wm_verify_secret_key( $secret_key );
		} catch ( Ai1wm_Not_Valid_Secret_Key_Exception $e ) {
			exit;
		}

		try {
			Ai1wm_Backups::delete_file( $archive );
			Ai1wm_Backups::delete_label( $archive );
		} catch ( Ai1wm_Backups_Exception $e ) {
			ai1wm_json_response( array( 'errors' => array( $e->getMessage() ) ) );
			exit;
		}

		ai1wm_json_response( array( 'errors' => array() ) );
		exit;
	}

	public static function add_label( $params = array() ) {
		ai1wm_setup_environment();

		// Set params
		if ( empty( $params ) ) {
			$params = stripslashes_deep( $_POST );
		}

		// Set secret key
		$secret_key = null;
		if ( isset( $params['secret_key'] ) ) {
			$secret_key = trim( $params['secret_key'] );
		}

		// Set archive
		$archive = null;
		if ( isset( $params['archive'] ) ) {
			$archive = trim( $params['archive'] );
		}

		// Set backup label
		$label = null;
		if ( isset( $params['label'] ) ) {
			$label = trim( $params['label'] );
		}

		try {
			// Ensure that unauthorized people cannot access add label action
			ai1wm_verify_secret_key( $secret_key );
		} catch ( Ai1wm_Not_Valid_Secret_Key_Exception $e ) {
			exit;
		}

		try {
			Ai1wm_Backups::set_label( $archive, $label );
		} catch ( Ai1wm_Backups_Exception $e ) {
			ai1wm_json_response( array( 'errors' => array( $e->getMessage() ) ) );
			exit;
		}

		ai1wm_json_response( array( 'errors' => array() ) );
		exit;
	}

	public static function backup_list( $params = array() ) {
		ai1wm_setup_environment();

		// Set params
		if ( empty( $params ) ) {
			$params = stripslashes_deep( $_GET );
		}

		// Set secret key
		$secret_key = null;
		if ( isset( $params['secret_key'] ) ) {
			$secret_key = trim( $params['secret_key'] );
		}

		try {
			// Ensure that unauthorized people cannot access backups list action
			ai1wm_verify_secret_key( $secret_key );
		} catch ( Ai1wm_Not_Valid_Secret_Key_Exception $e ) {
			exit;
		}

		Ai1wm_Template::render(
			'backups/backups-list',
			array(
				'backups'      => Ai1wm_Backups::get_files(),
				'labels'       => Ai1wm_Backups::get_labels(),
				'downloadable' => Ai1wm_Backups::are_downloadable(),
			)
		);
		exit;
	}

	public static function backup_get_config( $params = array() ) {
		ai1wm_setup_environment();

		// Set params
		if ( empty( $params ) ) {
			$params = stripslashes_deep( $_POST );
		}

		// Set secret key
		$secret_key = null;
		if ( isset( $params['secret_key'] ) ) {
			$secret_key = trim( $params['secret_key'] );
		}

		try {
			// Ensure that unauthorized people cannot access backups list action
			ai1wm_verify_secret_key( $secret_key );
		} catch ( Ai1wm_Not_Valid_Secret_Key_Exception $e ) {
			exit;
		}

		try {
			// Open the archive file for reading
			$archive = new Ai1wm_Extractor( ai1wm_backup_path( $params ) );
			$archive->extract_by_files_array( ai1wm_storage_path( $params ), array( AI1WM_PACKAGE_NAME ) );
			$archive->close();
		} catch ( Exception $e ) {
			ai1wm_json_response( array( 'errors' => array( $e->getMessage() ) ) );
			exit;
		}

		ai1wm_json_response( array( 'errors' => array() ) );
		exit;
	}

	public static function backup_check_encryption( $params = array() ) {
		ai1wm_setup_environment();

		// Set params
		if ( empty( $params ) ) {
			$params = stripslashes_deep( $_POST );
		}

		// Set secret key
		$secret_key = null;
		if ( isset( $params['secret_key'] ) ) {
			$secret_key = trim( $params['secret_key'] );
		}

		try {
			// Ensure that unauthorized people cannot access backups list action
			ai1wm_verify_secret_key( $secret_key );
		} catch ( Ai1wm_Not_Valid_Secret_Key_Exception $e ) {
			exit;
		}

		// Read package.json file
		$handle = ai1wm_open( ai1wm_package_path( $params ), 'r' );

		// Parse package.json file
		$package = ai1wm_read( $handle, filesize( ai1wm_package_path( $params ) ) );
		$package = json_decode( $package, true );

		// Close handle
		ai1wm_close( $handle );

		// No encryption provided
		if ( empty( $package['Encrypted'] ) || empty( $package['EncryptedSignature'] ) ) {
			ai1wm_json_response( array( 'errors' => array() ) );
			exit;
		}

		// Check decryption support
		if ( ! ai1wm_can_decrypt() ) {
			ai1wm_json_response( array( 'errors' => array( __( 'Download a file from encrypted backup is not supported on this server. The process cannot continue. <a href="https://help.servmask.com/knowledgebase/unable-to-encrypt-and-decrypt-backups/" target="_blank">Technical details</a>', 'all-in-one-wp-migration' ) ) ) );
			exit;
		}

		// Validate decryption password
		if ( ! empty( $params['decryption_password'] ) ) {
			if ( ! ai1wm_is_decryption_password_valid( $package['EncryptedSignature'], $params['decryption_password'] ) ) {
				ai1wm_json_response( array( 'errors' => array( __( 'The decryption password is not valid. The process cannot continue.', 'all-in-one-wp-migration' ) ) ) );
				exit;
			}

			ai1wm_json_response( array( 'errors' => array() ) );
			exit;
		}

		ai1wm_json_response( array( 'check' => true, 'errors' => array() ) );
		exit;
	}

	public static function backup_list_content( $params = array() ) {
		ai1wm_setup_environment();

		// Set params
		if ( empty( $params ) ) {
			$params = stripslashes_deep( $_POST );
		}

		// Set secret key
		$secret_key = null;
		if ( isset( $params['secret_key'] ) ) {
			$secret_key = trim( $params['secret_key'] );
		}

		try {
			// Ensure that unauthorized people cannot access backups list action
			ai1wm_verify_secret_key( $secret_key );
		} catch ( Ai1wm_Not_Valid_Secret_Key_Exception $e ) {
			exit;
		}

		$files = array();

		try {
			$archive = new Ai1wm_Extractor( ai1wm_backup_path( $params ) );
			if ( ! $archive->is_valid() ) {
				throw new Ai1wm_Backups_Exception(
					__( 'Could not list the backup content. Please ensure the backup file is accessible and not corrupted.', 'all-in-one-wp-migration' )
				);
			}

			$files = $archive->list_files();
			$archive->close();
		} catch ( Exception $e ) {
			ai1wm_json_response( array( 'errors' => $e->getMessage() ) );
			exit;
		}

		ai1wm_json_response( $files );
		exit;
	}

	public static function download_file( $params = array() ) {
		ai1wm_setup_environment();

		// Set params
		if ( empty( $params ) ) {
			$params = stripslashes_deep( $_POST );
		}

		// Set secret key
		$secret_key = null;
		if ( isset( $params['secret_key'] ) ) {
			$secret_key = trim( $params['secret_key'] );
		}

		// Set decryption password
		$decryption_password = null;
		if ( isset( $params['decryption_password'] ) ) {
			$decryption_password = $params['decryption_password'];
		}

		// Set file name
		$file_name = null;
		if ( isset( $params['file_name'] ) ) {
			$file_name = trim( $params['file_name'] );
		}

		// Set file offset
		if ( isset( $params['file_offset'] ) ) {
			$file_offset = (int) $params['file_offset'];
		} else {
			$file_offset = 0;
		}

		try {
			// Ensure that unauthorized people cannot access backups list action
			ai1wm_verify_secret_key( $secret_key );
		} catch ( Ai1wm_Not_Valid_Secret_Key_Exception $e ) {
			exit;
		}

		// Read package.json file
		$handle = ai1wm_open( ai1wm_package_path( $params ), 'r' );

		// Parse package.json file
		$config = ai1wm_read( $handle, filesize( ai1wm_package_path( $params ) ) );
		$config = json_decode( $config, true );

		// Close handle
		ai1wm_close( $handle );

		// Get compression type
		$compression_type = null;
		if ( ! empty( $config['Compression']['Enabled'] ) ) {
			$compression_type = $config['Compression']['Type'];
		}

		// Open the archive file for reading
		$archive = new Ai1wm_Extractor( ai1wm_backup_path( $params ), $decryption_password, $compression_type );
		$archive->set_file_pointer( $file_offset );
		$archive->extract_one_file_to( ai1wm_storage_path( $params ) );
		$archive->close();

		try {
			// Download file
			if ( ( $file_handle = ai1wm_open( ai1wm_storage_path( $params ) . DIRECTORY_SEPARATOR . $file_name, 'rb' ) ) ) {
				while ( ! feof( $file_handle ) ) {
					$file_buffer = ai1wm_read( $file_handle, 1024 * 1024 );
					echo $file_buffer;
					ob_flush();
					flush();
				}

				ai1wm_close( $file_handle );
			}
		} catch ( Exception $e ) {
		}

		exit;
	}

	public static function download_backup( $params = array() ) {
		ai1wm_setup_environment();

		// Set params
		if ( empty( $params ) ) {
			$params = stripslashes_deep( $_POST );
		}

		// Set secret key
		$secret_key = null;
		if ( isset( $params['secret_key'] ) ) {
			$secret_key = trim( $params['secret_key'] );
		}

		try {
			// Ensure that unauthorized people cannot access backups list action
			ai1wm_verify_secret_key( $secret_key );
		} catch ( Ai1wm_Not_Valid_Secret_Key_Exception $e ) {
			exit;
		}

		try {
			// Download file
			if ( ( $file_handle = ai1wm_open( ai1wm_backup_path( $params ), 'rb' ) ) ) {
				while ( ! feof( $file_handle ) ) {
					$file_buffer = ai1wm_read( $file_handle, 1024 * 1024 );
					echo $file_buffer;
					ob_flush();
					flush();
				}

				ai1wm_close( $file_handle );
			}
		} catch ( Exception $e ) {
		}

		exit;
	}
}
