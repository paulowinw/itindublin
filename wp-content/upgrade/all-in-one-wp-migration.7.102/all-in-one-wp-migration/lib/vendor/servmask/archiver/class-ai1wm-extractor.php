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

class Ai1wm_Extractor extends Ai1wm_Archiver {

	/**
	 * Total files count
	 *
	 * @type int
	 */
	protected $total_files_count = null;

	/**
	 * Total files size
	 *
	 * @type int
	 */
	protected $total_files_size = null;

	/**
	 * Overloaded constructor that opens the passed file for reading
	 *
	 * @param string $file_name        File to use as archive
	 * @param string $file_password    File password string
	 * @param string $file_compression File compression type
	 */
	public function __construct( $file_name, $file_password = null, $file_compression = null ) {
		// Call parent, to initialize variables
		parent::__construct( $file_name, $file_password, $file_compression, false );
	}

	public function list_files() {
		$files = array();

		// Seek to beginning of archive file
		if ( @fseek( $this->file_handle, 0, SEEK_SET ) === -1 ) {
			throw new Ai1wm_Not_Seekable_Exception( sprintf( __( 'Could not seek to beginning of file. File: %s', 'all-in-one-wp-migration' ), $this->file_name ) );
		}

		$offset = 0;

		// Loop over files
		while ( ( $block = @fread( $this->file_handle, 4377 ) ) ) {

			// End block has been reached
			if ( $block === $this->file_eof ) {
				continue;
			}

			// Get file data from the block
			if ( ( $data = $this->get_data_from_block( $block ) ) ) {
				// Store the position where the file begins - used for downloading from archive directly
				$data['offset'] = $offset;

				// Skip file content, so we can move forward to the next file
				if ( @fseek( $this->file_handle, $data['size'], SEEK_CUR ) === -1 ) {
					throw new Ai1wm_Not_Seekable_Exception( sprintf( __( 'Could not seek to offset of file. File: %s Offset: %d', 'all-in-one-wp-migration' ), $this->file_name, $data['size'] ) );
				}

				$files[] = $data;
			}

			$offset = @ftell( $this->file_handle );
		}

		return $files;
	}

	/**
	 * Get the total files count in an archive
	 *
	 * @return int
	 */
	public function get_total_files_count() {
		if ( is_null( $this->total_files_count ) ) {

			// Total files count
			$this->total_files_count = 0;

			// Total files size
			$this->total_files_size = 0;

			// Seek to beginning of archive file
			if ( @fseek( $this->file_handle, 0, SEEK_SET ) === -1 ) {
				throw new Ai1wm_Not_Seekable_Exception( sprintf( __( 'Could not seek to beginning of file. File: %s', 'all-in-one-wp-migration' ), $this->file_name ) );
			}

			// Loop over files
			while ( ( $block = @fread( $this->file_handle, 4377 ) ) ) {

				// End block has been reached
				if ( $block === $this->file_eof ) {
					continue;
				}

				// Get file data from the block
				if ( ( $data = $this->get_data_from_block( $block ) ) ) {

					// We have a file, increment the count
					$this->total_files_count += 1;

					// We have a file, increment the size
					$this->total_files_size += $data['size'];

					// Skip file content so we can move forward to the next file
					if ( @fseek( $this->file_handle, $data['size'], SEEK_CUR ) === -1 ) {
						throw new Ai1wm_Not_Seekable_Exception( sprintf( __( 'Could not seek to offset of file. File: %s Offset: %d', 'all-in-one-wp-migration' ), $this->file_name, $data['size'] ) );
					}
				}
			}
		}

		return $this->total_files_count;
	}

	/**
	 * Get the total files size in an archive
	 *
	 * @return int
	 */
	public function get_total_files_size() {
		if ( is_null( $this->total_files_size ) ) {

			// Total files count
			$this->total_files_count = 0;

			// Total files size
			$this->total_files_size = 0;

			// Seek to beginning of archive file
			if ( @fseek( $this->file_handle, 0, SEEK_SET ) === -1 ) {
				throw new Ai1wm_Not_Seekable_Exception( sprintf( __( 'Could not seek to beginning of file. File: %s', 'all-in-one-wp-migration' ), $this->file_name ) );
			}

			// Loop over files
			while ( ( $block = @fread( $this->file_handle, 4377 ) ) ) {

				// End block has been reached
				if ( $block === $this->file_eof ) {
					continue;
				}

				// Get file data from the block
				if ( ( $data = $this->get_data_from_block( $block ) ) ) {

					// We have a file, increment the count
					$this->total_files_count += 1;

					// We have a file, increment the size
					$this->total_files_size += $data['size'];

					// Skip file content so we can move forward to the next file
					if ( @fseek( $this->file_handle, $data['size'], SEEK_CUR ) === -1 ) {
						throw new Ai1wm_Not_Seekable_Exception( sprintf( __( 'Could not seek to offset of file. File: %s Offset: %d', 'all-in-one-wp-migration' ), $this->file_name, $data['size'] ) );
					}
				}
			}
		}

		return $this->total_files_size;
	}

	/**
	 * Extract one file to location
	 *
	 * @param string $location           Destination path
	 * @param array  $exclude_files      Exclude files by name
	 * @param array  $exclude_extensions Exclude files by extension
	 * @param array  $old_paths          Old replace paths
	 * @param array  $new_paths          New replace paths
	 * @param int    $file_bytes_read    Amount of the bytes we read
	 * @param int    $file_bytes_offset  File bytes offset
	 * @param int    $file_bytes_written Amount of the bytes we wrote
	 *
	 * @throws \Ai1wm_Not_Directory_Exception
	 * @throws \Ai1wm_Not_Seekable_Exception
	 *
	 * @return bool
	 */
	public function extract_one_file_to( $location, $exclude_files = array(), $exclude_extensions = array(), $old_paths = array(), $new_paths = array(), &$file_bytes_read = 0, &$file_bytes_offset = 0, &$file_bytes_written = 0 ) {
		if ( false === is_dir( $location ) ) {
			throw new Ai1wm_Not_Directory_Exception( sprintf( __( 'Location is not a directory: %s', 'all-in-one-wp-migration' ), $location ) );
		}

		// Replace forward slash with current directory separator in location
		$location = ai1wm_replace_forward_slash_with_directory_separator( $location );

		// Flag to hold if file data has been processed
		$completed = true;

		// Seek to file offset to archive file
		if ( $file_bytes_offset > 0 ) {
			if ( @fseek( $this->file_handle, - $file_bytes_offset - 4377, SEEK_CUR ) === -1 ) {
				throw new Ai1wm_Not_Seekable_Exception( sprintf( __( 'Could not seek to offset of file. File: %s Offset: %d', 'all-in-one-wp-migration' ), $this->file_name, - $file_bytes_offset - 4377 ) );
			}
		}

		// Read file header block
		if ( ( $block = @fread( $this->file_handle, 4377 ) ) ) {

			// We reached end of file, set the pointer to the end of the file so that feof returns true
			if ( $block === $this->file_eof ) {

				// Seek to end of archive file minus 1 byte
				@fseek( $this->file_handle, 1, SEEK_END );

				// Read 1 character
				@fgetc( $this->file_handle );

			} else {

				// Get file header data from the block
				if ( ( $data = $this->get_data_from_block( $block ) ) ) {

					// Set file name
					$file_name = $data['filename'];

					// Set file size
					$file_size = $data['size'];

					// Set file mtime
					$file_mtime = $data['mtime'];

					// Set file path
					$file_path = $data['path'];

					// Should we skip this file by name?
					$should_exclude_file = false;
					for ( $i = 0; $i < count( $exclude_files ); $i++ ) {
						if ( strpos( $file_name . DIRECTORY_SEPARATOR, ai1wm_replace_forward_slash_with_directory_separator( $exclude_files[ $i ] ) . DIRECTORY_SEPARATOR ) === 0 ) {
							$should_exclude_file = true;
							break;
						}
					}

					// Should we skip this file by extension?
					for ( $i = 0; $i < count( $exclude_extensions ); $i++ ) {
						if ( strrpos( $file_name, $exclude_extensions[ $i ] ) === strlen( $file_name ) - strlen( $exclude_extensions[ $i ] ) ) {
							$should_exclude_file = true;
							break;
						}
					}

					// Do we have a match?
					if ( $should_exclude_file === false ) {

						// Replace extract paths
						for ( $i = 0; $i < count( $old_paths ); $i++ ) {
							if ( strpos( $file_path . DIRECTORY_SEPARATOR, ai1wm_replace_forward_slash_with_directory_separator( $old_paths[ $i ] ) . DIRECTORY_SEPARATOR ) === 0 ) {
								$file_name = substr_replace( $file_name, ai1wm_replace_forward_slash_with_directory_separator( $new_paths[ $i ] ), 0, strlen( ai1wm_replace_forward_slash_with_directory_separator( $old_paths[ $i ] ) ) );
								$file_path = substr_replace( $file_path, ai1wm_replace_forward_slash_with_directory_separator( $new_paths[ $i ] ), 0, strlen( ai1wm_replace_forward_slash_with_directory_separator( $old_paths[ $i ] ) ) );
								break;
							}
						}

						// Escape Windows directory separator in file path
						if ( path_is_absolute( $file_path ) ) {
							$location_file_path = ai1wm_escape_windows_directory_separator( $file_path );
						} else {
							$location_file_path = ai1wm_escape_windows_directory_separator( $location . DIRECTORY_SEPARATOR . $file_path );
						}

						// Escape Windows directory separator in file name
						if ( path_is_absolute( $file_name ) ) {
							$location_file_name = ai1wm_escape_windows_directory_separator( $file_name );
						} else {
							$location_file_name = ai1wm_escape_windows_directory_separator( $location . DIRECTORY_SEPARATOR . $file_name );
						}

						// Check if location doesn't exist, then create it
						if ( false === is_dir( $location_file_path ) ) {
							@mkdir( $location_file_path, $this->get_permissions_for_directory(), true );
						}

						$file_bytes_read = 0;

						// We have a match, let's extract the file
						if ( ( $completed = $this->extract_to( $location_file_name, $file_name, $file_size, $file_mtime, $file_bytes_read, $file_bytes_offset, $file_bytes_written ) ) ) {
							$file_bytes_offset = $file_bytes_written = 0;
						}
					} else {

						// We don't have a match, skip file content
						if ( @fseek( $this->file_handle, $file_size, SEEK_CUR ) === -1 ) {
							throw new Ai1wm_Not_Seekable_Exception( sprintf( __( 'Could not seek to offset of file. File: %s Offset: %d', 'all-in-one-wp-migration' ), $this->file_name, $file_size ) );
						}
					}
				}
			}
		}

		return $completed;
	}

	/**
	 * Extract specific files from archive
	 *
	 * @param string $location           Location where to extract files
	 * @param array  $include_files      Include files by name
	 * @param array  $exclude_files      Exclude files by name
	 * @param array  $exclude_extensions Exclude files by extension
	 * @param int    $file_bytes_read    Amount of the bytes we read
	 * @param int    $file_bytes_offset  File bytes offset
	 * @param int    $file_bytes_written Amount of the bytes we wrote
	 *
	 * @throws \Ai1wm_Not_Directory_Exception
	 * @throws \Ai1wm_Not_Seekable_Exception
	 *
	 * @return bool
	 */
	public function extract_by_files_array( $location, $include_files = array(), $exclude_files = array(), $exclude_extensions = array(), &$file_bytes_read = 0, &$file_bytes_offset = 0, &$file_bytes_written = 0 ) {
		if ( false === is_dir( $location ) ) {
			throw new Ai1wm_Not_Directory_Exception( sprintf( __( 'Location is not a directory: %s', 'all-in-one-wp-migration' ), $location ) );
		}

		// Replace forward slash with current directory separator in location
		$location = ai1wm_replace_forward_slash_with_directory_separator( $location );

		// Flag to hold if file data has been processed
		$completed = true;

		// Start time
		$start = microtime( true );

		// Seek to file offset to archive file
		if ( $file_bytes_offset > 0 ) {
			if ( @fseek( $this->file_handle, - $file_bytes_offset - 4377, SEEK_CUR ) === -1 ) {
				throw new Ai1wm_Not_Seekable_Exception( sprintf( __( 'Could not seek to offset of file. File: %s Offset: %d', 'all-in-one-wp-migration' ), $this->file_name, - $file_bytes_offset - 4377 ) );
			}
		}

		// We read until we reached the end of the file, or the files we were looking for were found
		while ( ( $block = @fread( $this->file_handle, 4377 ) ) ) {

			// We reached end of file, set the pointer to the end of the file so that feof returns true
			if ( $block === $this->file_eof ) {

				// Seek to end of archive file minus 1 byte
				@fseek( $this->file_handle, 1, SEEK_END );

				// Read 1 character
				@fgetc( $this->file_handle );

			} else {

				// Get file header data from the block
				if ( ( $data = $this->get_data_from_block( $block ) ) ) {

					// Set file name
					$file_name = $data['filename'];

					// Set file size
					$file_size = $data['size'];

					// Set file mtime
					$file_mtime = $data['mtime'];

					// Set file path
					$file_path = $data['path'];

					// Should we extract this file by name?
					$should_include_file = false;
					for ( $i = 0; $i < count( $include_files ); $i++ ) {
						if ( strpos( $file_name . DIRECTORY_SEPARATOR, ai1wm_replace_forward_slash_with_directory_separator( $include_files[ $i ] ) . DIRECTORY_SEPARATOR ) === 0 ) {
							$should_include_file = true;
							break;
						}
					}

					// Should we skip this file name?
					for ( $i = 0; $i < count( $exclude_files ); $i++ ) {
						if ( strpos( $file_name . DIRECTORY_SEPARATOR, ai1wm_replace_forward_slash_with_directory_separator( $exclude_files[ $i ] ) . DIRECTORY_SEPARATOR ) === 0 ) {
							$should_include_file = false;
							break;
						}
					}

					// Should we skip this file by extension?
					for ( $i = 0; $i < count( $exclude_extensions ); $i++ ) {
						if ( strrpos( $file_name, $exclude_extensions[ $i ] ) === strlen( $file_name ) - strlen( $exclude_extensions[ $i ] ) ) {
							$should_include_file = false;
							break;
						}
					}

					// Do we have a match?
					if ( $should_include_file === true ) {

						// Escape Windows directory separator in file path
						$location_file_path = ai1wm_escape_windows_directory_separator( $location . DIRECTORY_SEPARATOR . $file_path );

						// Escape Windows directory separator in file name
						$location_file_name = ai1wm_escape_windows_directory_separator( $location . DIRECTORY_SEPARATOR . $file_name );

						// Check if location doesn't exist, then create it
						if ( false === is_dir( $location_file_path ) ) {
							@mkdir( $location_file_path, $this->get_permissions_for_directory(), true );
						}

						$file_bytes_read = 0;

						// We have a match, let's extract the file
						if ( ( $completed = $this->extract_to( $location_file_name, $file_name, $file_size, $file_mtime, $file_bytes_read, $file_bytes_offset, $file_bytes_written ) ) ) {
							$file_bytes_offset = $file_bytes_written = 0;
						}
					} else {

						// We don't have a match, skip file content
						if ( @fseek( $this->file_handle, $file_size, SEEK_CUR ) === -1 ) {
							throw new Ai1wm_Not_Seekable_Exception( sprintf( __( 'Could not seek to offset of file. File: %s Offset: %d', 'all-in-one-wp-migration' ), $this->file_name, $file_size ) );
						}
					}

					// Time elapsed
					if ( ( $timeout = apply_filters( 'ai1wm_completed_timeout', 10 ) ) ) {
						if ( ( microtime( true ) - $start ) > $timeout ) {
							$completed = false;
							break;
						}
					}
				}
			}
		}

		return $completed;
	}

	/**
	 * Extract file to
	 *
	 * @param string $location_file_name Location file name
	 * @param string $file_name          File name
	 * @param array  $file_size          File size (in bytes)
	 * @param array  $file_mtime         File modified time (in seconds)
	 * @param int    $file_bytes_read    Amount of the bytes we read
	 * @param int    $file_bytes_offset  File bytes offset
	 * @param int    $file_bytes_written Amount of the bytes we wrote
	 *
	 * @throws \Ai1wm_Not_Seekable_Exception
	 * @throws \Ai1wm_Not_Readable_Exception
	 * @throws \Ai1wm_Quota_Exceeded_Exception
	 *
	 * @return bool
	 */
	private function extract_to( $location_file_name, $file_name, $file_size, $file_mtime, &$file_bytes_read = 0, &$file_bytes_offset = 0, &$file_bytes_written = 0 ) {
		// Flag to hold if file data has been processed
		$completed = true;

		// Start time
		$start = microtime( true );

		// Seek to file offset to archive file
		if ( $file_bytes_offset > 0 ) {
			if ( @fseek( $this->file_handle, $file_bytes_offset, SEEK_CUR ) === -1 ) {
				throw new Ai1wm_Not_Seekable_Exception( sprintf( __( 'Could not seek to offset of file. File: %s Offset: %d', 'all-in-one-wp-migration' ), $this->file_name, $file_size ) );
			}
		}

		// Set file size
		$file_size -= $file_bytes_offset;

		// Should the extract overwrite the file if it exists? (fopen may return null for quarantined files)
		if ( ( $file_handle = @fopen( $location_file_name, ( $file_bytes_offset === 0 ? 'wb' : 'cb' ) ) ) ) {

			// Set file offset
			if ( @fseek( $file_handle, $file_bytes_written, SEEK_SET ) !== -1 ) {
				$file_bytes_read = 0;

				// Is the filesize more than 0 bytes?
				while ( $file_size > 0 ) {

					// Read the file in chunks of 512KB
					$chunk_size = min( $file_size, 512000 );

					// Do not decrypt or decompress config files
					if ( ! in_array( $file_name, ai1wm_config_filters() ) ) {

						// Get decryption chunk size
						if ( ! empty( $this->file_password ) ) {
							if ( $file_size > 512000 ) {
								$chunk_size += ai1wm_crypt_iv_length() * 2;
								$chunk_size  = min( $chunk_size, $file_size );
							}
						}

						// Read chunk header data
						if ( ! empty( $this->file_compression ) ) {
							$chunk_header_size = 4;

							// Get chunk header block
							if ( ( $chunk_header_block = fread( $this->file_handle, $chunk_header_size ) ) === false ) {
								throw new Ai1wm_Not_Readable_Exception( sprintf( __( 'Could not read content from file. File: %s', 'all-in-one-wp-migration' ), $this->file_name ) );
							}

							// Get chunk header data
							if ( ( $chunk_header_data = unpack( 'Nsize', $chunk_header_block ) ) ) {
								if ( isset( $chunk_header_data['size'] ) ) {
									$chunk_size = $chunk_header_data['size'];
								}
							}

							// Add the amount of bytes we read
							$file_bytes_read += $chunk_header_size;

							// Remove the amout of bytes we read
							$file_size -= $chunk_header_size;
						}
					}

					// Read data chunk by chunk from archive file
					if ( $chunk_size > 0 ) {

						// Read the file in chunks of 512KB from archiver
						if ( ( $file_content = @fread( $this->file_handle, $chunk_size ) ) === false ) {
							throw new Ai1wm_Not_Readable_Exception( sprintf( __( 'Could not read content from file. File: %s', 'all-in-one-wp-migration' ), $this->file_name ) );
						}

						// Add the amount of bytes we read
						$file_bytes_read += $chunk_size;

						// Remove the amout of bytes we read
						$file_size -= $chunk_size;

						// Do not decrypt or decompress config files
						if ( ! in_array( $file_name, ai1wm_config_filters() ) ) {

							// Add chunk data decryption
							if ( ! empty( $this->file_password ) ) {
								$file_content = ai1wm_decrypt_string( $file_content, $this->file_password );
							}

							// Add chunk data decompression
							if ( ! empty( $this->file_compression ) ) {
								switch ( $this->file_compression ) {
									case 'gzip':
										$file_content = gzuncompress( $file_content );
										break;

									case 'bzip2':
										$file_content = bzdecompress( $file_content );
										break;
								}
							}
						}

						// Write file contents
						if ( ( $file_bytes = @fwrite( $file_handle, $file_content ) ) !== false ) {
							if ( strlen( $file_content ) !== $file_bytes ) {
								throw new Ai1wm_Quota_Exceeded_Exception( sprintf( __( 'Out of disk space. Could not write content to file. File: %s', 'all-in-one-wp-migration' ), $location_file_name ) );
							}
						}

						// Add the amount of bytes we wrote
						$file_bytes_written += $file_bytes;
					}

					// Time elapsed
					if ( ( $timeout = apply_filters( 'ai1wm_completed_timeout', 10 ) ) ) {
						if ( ( microtime( true ) - $start ) > $timeout ) {
							$completed = false;
							break;
						}
					}
				}

				// Add the amount of bytes we read
				$file_bytes_offset += $file_bytes_read;
			}

			// Close the handle
			@fclose( $file_handle );

			// Let's apply last modified date
			@touch( $location_file_name, $file_mtime );

			// All files should chmoded to 644
			@chmod( $location_file_name, $this->get_permissions_for_file() );

		} else {

			// We don't have file permissions, skip file content
			if ( @fseek( $this->file_handle, $file_size, SEEK_CUR ) === -1 ) {
				throw new Ai1wm_Not_Seekable_Exception( sprintf( __( 'Could not seek to offset of file. File: %s Offset: %d', 'all-in-one-wp-migration' ), $this->file_name, $file_size ) );
			}
		}

		return $completed;
	}

	/**
	 * Get file header data from the block
	 *
	 * @param string $block Binary file header
	 *
	 * @return array
	 */
	private function get_data_from_block( $block ) {
		$data = false;

		// prepare our array keys to unpack
		$format = array(
			$this->block_format[0] . 'filename/',
			$this->block_format[1] . 'size/',
			$this->block_format[2] . 'mtime/',
			$this->block_format[3] . 'path',
		);
		$format = implode( '', $format );

		// Unpack file header data
		if ( ( $data = unpack( $format, $block ) ) ) {

			// Set file details
			$data['filename'] = trim( $data['filename'] );
			$data['size']     = (int) trim( $data['size'] );
			$data['mtime']    = (int) trim( $data['mtime'] );
			$data['path']     = trim( $data['path'] );

			// Set file name
			$data['filename'] = ( $data['path'] === '.' ? $data['filename'] : $data['path'] . DIRECTORY_SEPARATOR . $data['filename'] );

			// Set file path
			$data['path'] = ( $data['path'] === '.' ? '' : $data['path'] );

			// Replace forward slash with current directory separator in file name
			$data['filename'] = ai1wm_replace_forward_slash_with_directory_separator( $data['filename'] );

			// Replace forward slash with current directory separator in file path
			$data['path'] = ai1wm_replace_forward_slash_with_directory_separator( $data['path'] );
		}

		return $data;
	}

	/**
	 * Check if file has reached end of file
	 * Returns true if file has reached eof, false otherwise
	 *
	 * @return bool
	 */
	public function has_reached_eof() {
		return @feof( $this->file_handle );
	}

	/**
	 * Check if file has reached end of file
	 * Returns true if file has NOT reached eof, false otherwise
	 *
	 * @return bool
	 */
	public function has_not_reached_eof() {
		return ! @feof( $this->file_handle );
	}

	/**
	 * Get directory permissions
	 *
	 * @return int
	 */
	public function get_permissions_for_directory() {
		if ( defined( 'FS_CHMOD_DIR' ) ) {
			return FS_CHMOD_DIR;
		}

		return 0755;
	}

	/**
	 * Get file permissions
	 *
	 * @return int
	 */
	public function get_permissions_for_file() {
		if ( defined( 'FS_CHMOD_FILE' ) ) {
			return FS_CHMOD_FILE;
		}

		return 0644;
	}
}
