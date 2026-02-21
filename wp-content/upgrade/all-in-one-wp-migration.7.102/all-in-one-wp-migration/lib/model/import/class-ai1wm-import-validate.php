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

class Ai1wm_Import_Validate {

	public static function execute( $params ) {

		// Verify file if size > 2GB and PHP = 32-bit
		if ( ! ai1wm_is_filesize_supported( ai1wm_archive_path( $params ) ) ) {
			throw new Ai1wm_Import_Exception(
				wp_kses(
					__(
						'Your server uses 32-bit PHP and cannot process files larger than 2GB. Please switch to 64-bit PHP and try again.
						<a href="https://help.servmask.com/knowledgebase/php-32bit/" target="_blank">Technical details</a>',
						'all-in-one-wp-migration'
					),
					ai1wm_allowed_html_tags()
				)
			);
		}

		// Verify file name extension
		if ( ! ai1wm_is_filename_supported( ai1wm_archive_path( $params ) ) ) {
			throw new Ai1wm_Import_Exception(
				wp_kses(
					__(
						'Invalid file type. Please ensure your file is a <strong>.wpress</strong> backup created with All-in-One WP Migration.
						<a href="https://help.servmask.com/knowledgebase/invalid-backup-file/" target="_blank">Technical details</a>',
						'all-in-one-wp-migration'
					),
					ai1wm_allowed_html_tags()
				)
			);
		}

		// Set progress
		Ai1wm_Status::info( __( 'Unpacking configuration...', 'all-in-one-wp-migration' ) );

		// Open the archive file for reading
		$archive = new Ai1wm_Extractor( ai1wm_archive_path( $params ) );

		// Validate the archive file consistency
		if ( ! $archive->is_valid() ) {
			throw new Ai1wm_Import_Exception(
				wp_kses(
					__( 'The archive file appears to be corrupted. Follow <a href="https://help.servmask.com/knowledgebase/corrupted-archive/" target="_blank">this article</a> for possible fixes.', 'all-in-one-wp-migration' ),
					ai1wm_allowed_html_tags()
				)
			);
		}

		// Unpack package.json and multisite.json files
		$archive->extract_by_files_array( ai1wm_storage_path( $params ), array( AI1WM_PACKAGE_NAME, AI1WM_MULTISITE_NAME ) );

		// Check package.json file
		if ( false === is_file( ai1wm_package_path( $params ) ) ) {
			throw new Ai1wm_Import_Exception(
				wp_kses(
					__(
						'Please ensure your file was created with the All-in-One WP Migration plugin.
						<a href="https://help.servmask.com/knowledgebase/invalid-backup-file/" target="_blank">Technical details</a>',
						'all-in-one-wp-migration'
					),
					ai1wm_allowed_html_tags()
				)
			);
		}

		// Close the archive file
		$archive->close();

		return $params;
	}
}
