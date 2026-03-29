<?php
/**
 * AI content generator and replacer file.
 *
 * @package {{package}}
 * @since 1.1.13
 */

namespace STImporter\Importer\Batch;

use STImporter\Importer\Batch\ST_Replace_Images;
use STImporter\Importer\ST_Importer_Helper;
use STImporter\Importer\ST_Importer_Log;


if ( ! class_exists( 'ST_Replace_Blocks_Images' ) ) :

	/**
	 * Block Editor Blocks Replacer
	 *
	 * @since 1.1.13
	 */
	class ST_Replace_Blocks_Images {

		/**
		 * Instance
		 *
		 * @since 1.1.13
		 * @access private
		 * @var object Class object.
		 */
		private static $instance = null;

		/**
		 * Initiator
		 *
		 * @since 1.1.13
		 * @return object initialized object of class.
		 */
		public static function get_instance() {

			if ( null === self::$instance ) {
				self::$instance = new self();
				ST_Importer_Log::add( 'ST_Replace_Blocks_Images instance created' );
			}
			return self::$instance;
		}

		/**
		 * Reusable block tracking.
		 *
		 * @var array<int,int>
		 */
		public static $reusable_blocks = array();

		/**
		 * Constructor
		 *
		 * @since 1.1.13
		 */
		public function __construct() {}

		/**
		 * Replace the content with AI generated data in Elementor Pages.
		 *
		 * @param object $post Post object.
		 *
		 * @since 1.1.13
		 * @return string
		 */
		public function parse_replace_images( $post ) {
			ST_Importer_Log::add( 'Starting parse_replace_images for post ID: ' . ( $post->ID ?? 'unknown' ) );

			$blocks = parse_blocks( $post->post_content ?? '' );
			ST_Importer_Log::add( 'Parsed ' . count( $blocks ) . ' blocks from post content' );

			// Get replaced blocks images.
			$content = serialize_blocks( $this->get_updated_blocks( $blocks ) );
			ST_Importer_Log::add( 'Serialized updated blocks into content' );

			$final_content = $this->replace_content_glitch( $content );
			ST_Importer_Log::add( 'Completed parse_replace_images for post ID: ' . ( $post->ID ?? 'unknown' ) );

			return $final_content;
		}

		/**
		 * Update the Blocks with new mapping data.
		 *
		 * @since 4.1.0
		 * @param array<mixed> $blocks Array of Blocks.
		 * @return array<mixed> $blocks Modified array of Blocks.
		 */
		public function get_updated_blocks( &$blocks ) {
			ST_Importer_Log::add( 'Processing ' . count( $blocks ) . ' blocks for updates' );
			$processed_count = 0;
			$skipped_count   = 0;

			foreach ( $blocks as $i => &$block ) {

				if ( is_array( $block ) ) {

					if ( '' === $block['blockName'] ) {
						$skipped_count++;
						continue;
					}

					ST_Importer_Log::add( 'Processing block #' . $i . ' - Block type: ' . $block['blockName'] );

					if ( 'core/block' === $block['blockName'] && isset( $block['attrs']['ref'] ) ) {
						$reusable_block_id = $block['attrs']['ref'];
						ST_Importer_Log::add( 'Found reusable block with ID: ' . $reusable_block_id );

						$reusable_block = get_post( $reusable_block_id );

						if ( empty( $reusable_block ) || ! is_object( $reusable_block ) ) {
							ST_Importer_Log::add( 'Reusable block not found or invalid for ID: ' . $reusable_block_id );
							continue;
						}

						if ( in_array( $reusable_block_id, self::$reusable_blocks, true ) ) {
							ST_Importer_Log::add( 'Reusable block already processed, skipping ID: ' . $reusable_block_id );
							continue;
						}

						self::$reusable_blocks[] = $reusable_block_id;
						ST_Importer_Log::add( 'Added reusable block to tracking list: ' . $reusable_block_id );

						// Update content.
						$update_result = wp_update_post(
							array(
								'ID'           => $reusable_block->ID,
								'post_content' => $this->parse_replace_images( $reusable_block ),
							)
						);

						// @phpstan-ignore-next-line - wp_update_post can return WP_Error despite PHPDoc type
						if ( is_wp_error( $update_result ) ) {
							ST_Importer_Log::add( 'Failed to update reusable block ID ' . $reusable_block->ID . ': ' . $update_result->get_error_message() );
						} else {
							ST_Importer_Log::add( 'Successfully updated reusable block ID: ' . $reusable_block->ID );
						}
					}

					/** Replace images if present in the block */
					$this->replace_images_in_blocks( $block );
					$processed_count++;

					if ( ! empty( $block['innerBlocks'] ) ) {
						ST_Importer_Log::add( 'Processing ' . count( $block['innerBlocks'] ) . ' inner blocks for block #' . $i );
						/** Find the last node of the nested blocks */
						$this->get_updated_blocks( $block['innerBlocks'] );
					}
				}
			}

			ST_Importer_Log::add( 'Completed get_updated_blocks - Processed: ' . $processed_count . ', Skipped: ' . $skipped_count );
			return $blocks;
		}

		/**
		 * Parse social icon list.
		 *
		 * @since {{since}}
		 * @param array<mixed> $block Block.
		 * @return array<mixed> $block Block.
		 */
		public function parse_social_icons( $block ) {
			ST_Importer_Log::add( 'Starting parse_social_icons' );

			$social_profile = ST_Importer_Helper::get_business_details( 'social_profiles' );

			if ( empty( $social_profile ) || ! is_array( $social_profile ) ) {
				ST_Importer_Log::add( 'No social profiles found, returning block unchanged' );
				return $block;
			}

			ST_Importer_Log::add( 'Retrieved ' . count( $social_profile ) . ' social profiles' );

			$social_icons = array_map(
				function( $item ) {
					return $item['type'];
				},
				$social_profile
			);

			$social_icons = array_merge( $social_icons, array( 'phone' ) );
			ST_Importer_Log::add( 'Social icons to process: ' . implode( ', ', $social_icons ) );

			foreach ( $social_icons as $key => $social_icon ) {
				if ( 'linkedin' === $social_icon ) {
					$social_icons[ $key ] = 'linkedin-in';
					ST_Importer_Log::add( 'Converted linkedin to linkedin-in' );
					break;
				}
			}

			$inner_blocks = $block['innerBlocks'];

			if ( is_array( $inner_blocks ) ) {

				$cached_first_item = isset( $block['innerBlocks'][0] ) ? $block['innerBlocks'][0] : [];

				$list_social_icons = array_map(
					function( $item ) {
						return isset( $item['attrs']['icon'] ) ? $item['attrs']['icon'] : '';
					},
					$inner_blocks
				);

				if ( ! in_array( 'facebook', $list_social_icons, true ) ) {
					ST_Importer_Log::add( 'Facebook icon not found in list, returning block unchanged' );
					return $block;
				}

				ST_Importer_Log::add( 'Found facebook icon, processing inner blocks' );
				$removed_count = 0;

				foreach ( $inner_blocks as $index => &$inner_block ) {

					if ( 'uagb/icon-list-child' !== $inner_block['blockName'] ) {
						continue;
					}

					$icon = $inner_block['attrs']['icon'];

					if ( empty( $icon ) ) {
						continue;
					}

					if ( ! in_array( $icon, $social_icons, true ) ) {
						unset( $block['innerBlocks'][ $index ] );
						$removed_count++;
						ST_Importer_Log::add( 'Removed social icon not in profile: ' . $icon );
					}
				}

				ST_Importer_Log::add( 'Removed ' . $removed_count . ' social icons not in profile' );

				$yelp_google = [
					'yelp'   => '<svg xmlns="https://www.w3.org/2000/svg" viewBox="0 0 384 512"><path d="M42.9 240.3l99.62 48.61c19.2 9.4 16.2 37.51-4.5 42.71L30.5 358.5a22.79 22.79 0 0 1 -28.21-19.6 197.2 197.2 0 0 1 9-85.32 22.8 22.8 0 0 1 31.61-13.21zm44 239.3a199.4 199.4 0 0 0 79.42 32.11A22.78 22.78 0 0 0 192.9 490l3.9-110.8c.7-21.3-25.5-31.91-39.81-16.1l-74.21 82.4a22.82 22.82 0 0 0 4.09 34.09zm145.3-109.9l58.81 94a22.93 22.93 0 0 0 34 5.5 198.4 198.4 0 0 0 52.71-67.61A23 23 0 0 0 364.2 370l-105.4-34.26c-20.31-6.5-37.81 15.8-26.51 33.91zm148.3-132.2a197.4 197.4 0 0 0 -50.41-69.31 22.85 22.85 0 0 0 -34 4.4l-62 91.92c-11.9 17.7 4.7 40.61 25.2 34.71L366 268.6a23 23 0 0 0 14.61-31.21zM62.11 30.18a22.86 22.86 0 0 0 -9.9 32l104.1 180.4c11.7 20.2 42.61 11.9 42.61-11.4V22.88a22.67 22.67 0 0 0 -24.5-22.8 320.4 320.4 0 0 0 -112.3 30.1z"></path></svg>',
					'google' => '<svg xmlns="https://www.w3.org/2000/svg" viewBox="0 0 488 512"><path d="M488 261.8C488 403.3 391.1 504 248 504 110.8 504 0 393.2 0 256S110.8 8 248 8c66.8 0 123 24.5 166.3 64.9l-67.5 64.9C258.5 52.6 94.3 116.6 94.3 256c0 86.5 69.1 156.6 153.7 156.6 98.2 0 135-70.4 140.8-106.9H248v-85.3h236.1c2.3 12.7 3.9 24.9 3.9 41.4z"></path></svg>',
				];

				$added_count = 0;
				foreach ( $yelp_google as $yelp_google_key => $yelp_google_item ) {
					if ( in_array( $yelp_google_key, $social_icons, true ) && ! empty( $cached_first_item ) ) {
						ST_Importer_Log::add( 'Adding Yelp/Google icon: ' . $yelp_google_key );

						$new_inner_block                  = $cached_first_item;
						$new_inner_block['attrs']['icon'] = $yelp_google_key;
						$link                             = '#';
						if ( is_array( $social_profile ) ) {
							foreach ( $social_profile as $social_icon ) {
								if ( $yelp_google_key === $social_icon['type'] ) {
									$link = $social_icon['url'];
									ST_Importer_Log::add( 'Found URL for ' . $yelp_google_key . ': ' . $link );
									break;
								}
							}
						}

						$new_inner_block['attrs']['link'] = $link;

						$svg_pattern = '/<svg.*?>.*?<\/svg>/s'; // The 's' modifier allows the dot (.) to match newline characters.
						preg_match( $svg_pattern, $new_inner_block['innerHTML'], $matches );
						if ( ! empty( $matches[0] ) ) {
							$new_inner_block['innerHTML'] = str_replace( $matches[0], $yelp_google_item, $new_inner_block['innerHTML'] );
						}

						$href_pattern = '/href=".*?"/s'; // The 's' modifier allows the dot (.) to match newline characters.
						preg_match( $href_pattern, $new_inner_block['innerHTML'], $href_matches );
						if ( ! empty( $href_matches ) ) {
							$new_inner_block['innerHTML'] = str_replace( $href_matches[0], 'href="' . $link . '"', $new_inner_block['innerHTML'] );
						}
						foreach ( $new_inner_block['innerContent'] as $key => $inner_content ) {
							if ( empty( $inner_content ) ) {
								continue;
							}
							preg_match( $svg_pattern, $inner_content, $matches );
							if ( ! empty( $matches ) ) {
								$new_inner_block['innerContent'][ $key ] = str_replace( $matches[0], $yelp_google_item, $new_inner_block['innerContent'][ $key ] );
							}

							preg_match( $href_pattern, $inner_content, $href_matches );
							if ( ! empty( $href_matches ) ) {
								$new_inner_block['innerContent'][ $key ] = str_replace( $href_matches[0], 'href="' . $link . '"', $new_inner_block['innerContent'][ $key ] );
							}
						}

						array_push( $block['innerBlocks'], $new_inner_block );

						$last_index = count( $block['innerContent'] ) - 1;
						array_splice( $block['innerContent'], $last_index, 0, '' );

						$new_last_index = count( $block['innerContent'] ) - 1;
						array_splice( $block['innerContent'], $new_last_index, 0, [ null ] );

						$added_count++;
					}
				}

				ST_Importer_Log::add( 'Added ' . $added_count . ' Yelp/Google icons' );
				$block['innerBlocks'] = array_values( $block['innerBlocks'] );
			}

			ST_Importer_Log::add( 'Completed parse_social_icons' );
			return $block;
		}

		/**
		 * Replace the image in the block if present from the AI generated images.
		 *
		 * @since 4.1.0
		 * @param array<mixed> $block Reference of Block array.
		 * @return void
		 */
		public function replace_images_in_blocks( &$block ) {
			$block_name = $block['blockName'];
			ST_Importer_Log::add( 'Checking block for image replacement: ' . $block_name );

			switch ( $block['blockName'] ) {
				case 'uagb/container':
					$block = $this->parse_spectra_container( $block );
					break;

				case 'uagb/image':
					$block = $this->parse_spectra_image( $block );
					break;

				case 'uagb/image-gallery':
					$block = $this->parse_spectra_gallery( $block );
					break;

				case 'uagb/info-box':
					$block = $this->parse_spectra_infobox( $block );
					break;

				case 'uagb/google-map':
					$block = $this->parse_spectra_google_map( $block );
					break;

				case 'uagb/forms':
					$block = $this->parse_spectra_form( $block );
					break;

				case 'uagb/icon-list':
					$block = $this->parse_social_icons( $block );
			}
		}

		/**
		 * Parses Spectra form block.
		 *
		 * @since 4.1.0
		 * @param array<mixed> $block Block.
		 * @return array<mixed>
		 */
		public function parse_spectra_form( $block ) {
			ST_Importer_Log::add( 'Starting parse_spectra_form' );

			$business_email = ST_Importer_Helper::get_business_details( 'business_email' );

			if ( ! empty( $business_email ) ) {
				$block['attrs']['afterSubmitToEmail'] = $business_email;
				ST_Importer_Log::add( 'Updated form email to: ' . ( is_string( $business_email ) ? $business_email : wp_json_encode( $business_email ) ) );
			} else {
				ST_Importer_Log::add( 'No business email found, form unchanged' );
			}

			return $block;
		}

		/**
		 * Parses Google Map for the Spectra Google Map block.
		 *
		 * @since 4.1.0
		 * @param array<mixed> $block Block.
		 * @return array<mixed> $block Block.
		 */
		public function parse_spectra_google_map( $block ) {
			ST_Importer_Log::add( 'Starting parse_spectra_google_map' );

			$address = ST_Importer_Helper::get_business_details( 'business_address' );
			if ( empty( $address ) ) {
				ST_Importer_Log::add( 'No business address found, returning block unchanged' );
				return $block;
			}

			$block['attrs']['address'] = $address;
			ST_Importer_Log::add( 'Updated Google Map address to: ' . ( is_string( $address ) ? $address : wp_json_encode( $address ) ) );

			return $block;
		}

		/**
		 * Parses images and other content in the Spectra Container block.
		 *
		 * @since 4.1.0
		 * @param array<mixed> $block Block.
		 * @return array<mixed> $block Block.
		 */
		public function parse_spectra_container( $block ) {
			ST_Importer_Log::add( 'Starting parse_spectra_container' );

			if (
			! isset( $block['attrs']['backgroundImageDesktop'] ) ||
			empty( $block['attrs']['backgroundImageDesktop'] ) ||
			ST_Replace_Images::is_skipable( $block['attrs']['backgroundImageDesktop']['url'] )
			) {
				ST_Importer_Log::add( 'No background image or skipable image, returning block unchanged' );
				return $block;
			}

			$old_url = $block['attrs']['backgroundImageDesktop']['url'];
			ST_Importer_Log::add( 'Found background image to replace: ' . $old_url );

			$image = ST_Replace_Images::get_instance()->get_image( ST_Replace_Images::$image_index );
			if ( empty( $image ) || ! is_array( $image ) ) {
				ST_Importer_Log::add( 'No replacement image available at index: ' . ST_Replace_Images::$image_index );
				return $block;
			}

			ST_Importer_Log::add( 'Retrieved replacement image from index: ' . ST_Replace_Images::$image_index );
			$image = ST_Importer_Helper::download_image( $image );

			if ( is_wp_error( $image ) ) {
				ST_Importer_Log::add( 'Failed to download image: ' . $image->get_error_message() );
				return $block;
			}

			ST_Importer_Log::add( 'Successfully downloaded image with ID: ' . $image );

			$attachment = wp_prepare_attachment_for_js( absint( $image ) );
			if ( ! is_array( $attachment ) ) {
				ST_Importer_Log::add( 'Failed to prepare attachment for JS' );
				return $block;
			}

			ST_Replace_Images::$old_image_urls[]      = $block['attrs']['backgroundImageDesktop']['url'];
			$block['attrs']['backgroundImageDesktop'] = $attachment;
			ST_Replace_Images::get_instance()->increment_image_index();

			ST_Importer_Log::add( 'Successfully replaced container background image - Old: ' . $old_url . ', New: ' . $attachment['url'] );

			return $block;
		}

		/**
		 * Parses images and other content in the Spectra Info Box block.
		 *
		 * @since 4.1.0
		 * @param array<mixed> $block Block.
		 * @return array<mixed> $block Block.
		 */
		public function parse_spectra_infobox( $block ) {
			ST_Importer_Log::add( 'Starting parse_spectra_infobox' );

			if (
			! isset( $block['attrs']['iconImage'] ) ||
			empty( $block['attrs']['iconImage'] ) ||
			ST_Replace_Images::is_skipable( $block['attrs']['iconImage']['url'] )
			) {
				ST_Importer_Log::add( 'No icon image or skipable image, returning block unchanged' );
				return $block;
			}

			$old_url = $block['attrs']['iconImage']['url'];
			ST_Importer_Log::add( 'Found icon image to replace: ' . $old_url );

			$image = ST_Replace_Images::get_instance()->get_image( ST_Replace_Images::$image_index );
			if ( empty( $image ) || ! is_array( $image ) ) {
				ST_Importer_Log::add( 'No replacement image available at index: ' . ST_Replace_Images::$image_index );
				return $block;
			}

			ST_Importer_Log::add( 'Retrieved replacement image from index: ' . ST_Replace_Images::$image_index );
			$image = ST_Importer_Helper::download_image( $image );

			if ( is_wp_error( $image ) ) {
				ST_Importer_Log::add( 'Failed to download image: ' . $image->get_error_message() );
				return $block;
			}

			ST_Importer_Log::add( 'Successfully downloaded image with ID: ' . $image );

			$attachment = wp_prepare_attachment_for_js( absint( $image ) );

			if ( ! is_array( $attachment ) ) {
				ST_Importer_Log::add( 'Failed to prepare attachment for JS' );
				return $block;
			}

			ST_Replace_Images::$old_image_urls[] = $block['attrs']['iconImage']['url'];
			if ( ! empty( $block['attrs']['iconImage']['url'] ) ) {

				$block['innerHTML'] = str_replace( $block['attrs']['iconImage']['url'], $attachment['url'], $block['innerHTML'] );
				ST_Importer_Log::add( 'Replaced icon image URL in innerHTML' );
			}

			$replaced_inner_content = 0;
			foreach ( $block['innerContent'] as $key => &$inner_content ) {

				if ( is_string( $block['innerContent'][ $key ] ) && '' === trim( $block['innerContent'][ $key ] ) ) {
					continue;
				}
				$block['innerContent'][ $key ] = str_replace( $block['attrs']['iconImage']['url'], $attachment['url'], $block['innerContent'][ $key ] );
				$replaced_inner_content++;
			}
			ST_Importer_Log::add( 'Replaced icon image URL in ' . $replaced_inner_content . ' inner content items' );

			$block['attrs']['iconImage'] = $attachment;
			ST_Replace_Images::get_instance()->increment_image_index();

			ST_Importer_Log::add( 'Successfully replaced infobox icon image - Old: ' . $old_url . ', New: ' . $attachment['url'] );

			return $block;
		}

		/**
		 * Parses images and other content in the Spectra Image block.
		 *
		 * @since 4.1.0
		 * @param array<mixed> $block Block.
		 * @return array<mixed> $block Block.
		 */
		public function parse_spectra_image( $block ) {
			ST_Importer_Log::add( 'Starting parse_spectra_image' );

			if (
			! isset( $block['attrs']['url'] ) ||
			ST_Replace_Images::is_skipable( $block['attrs']['url'] )
			) {
				ST_Importer_Log::add( 'No image URL or skipable image, returning block unchanged' );
				return $block;
			}

			$old_url = $block['attrs']['url'];
			ST_Importer_Log::add( 'Found image to replace: ' . $old_url );

			$image = ST_Replace_Images::get_instance()->get_image( ST_Replace_Images::$image_index );

			if ( empty( $image ) || ! is_array( $image ) ) {
				ST_Importer_Log::add( 'No replacement image available at index: ' . ST_Replace_Images::$image_index );
				return $block;
			}

			ST_Importer_Log::add( 'Retrieved replacement image from index: ' . ST_Replace_Images::$image_index );
			$image = ST_Importer_Helper::download_image( $image );

			if ( is_wp_error( $image ) ) {
				ST_Importer_Log::add( 'Failed to download image: ' . $image->get_error_message() );
				return $block;
			}

			ST_Importer_Log::add( 'Successfully downloaded image with ID: ' . $image );

			$attachment = wp_prepare_attachment_for_js( absint( $image ) );
			if ( ! is_array( $attachment ) ) {
				ST_Importer_Log::add( 'Failed to prepare attachment for JS' );
				return $block;
			}

			ST_Replace_Images::$old_image_urls[] = $block['attrs']['url'];
			$block['innerHTML']                  = str_replace( $block['attrs']['url'], $attachment['url'], $block['innerHTML'] );
			ST_Importer_Log::add( 'Replaced image URL in innerHTML' );

			$alt_text = ! empty( $attachment['alt'] ) ? $attachment['alt'] : '';
			if ( ! empty( $alt_text ) ) {
				$block['innerHTML'] = str_replace( 'alt=""', 'alt="' . esc_attr( $alt_text ) . '"', $block['innerHTML'] );
				ST_Importer_Log::add( 'Updated alt text to: ' . $alt_text );
			}

			if ( isset( $block['alt'] ) ) {
				$block['innerHTML'] = str_replace( $block['alt'], $attachment['alt'], $block['innerHTML'] );
			}

			$tablet_size_slug = ! empty( $block['attrs']['sizeSlugTablet'] ) ? $block['attrs']['sizeSlugTablet'] : '';
			$mobile_size_slug = ! empty( $block['attrs']['sizeSlugMobile'] ) ? $block['attrs']['sizeSlugMobile'] : '';
			$tablet_dest_url  = '';
			$mobile_dest_url  = '';

			if (
			isset( $block['attrs']['urlTablet'] ) &&
			! empty( $block['attrs']['urlTablet'] ) &&
			! empty( $tablet_size_slug )
			) {
				$tablet_dest_url             = isset( $attachment['sizes'][ $tablet_size_slug ]['url'] ) ? $attachment['sizes'][ $tablet_size_slug ]['url'] : $attachment['url'];
				$block['innerHTML']          = str_replace( $block['attrs']['urlTablet'], $tablet_dest_url, $block['innerHTML'] );
				$block['attrs']['urlTablet'] = $tablet_dest_url;
				ST_Importer_Log::add( 'Updated tablet image URL: ' . $tablet_dest_url );
			}
			if (
			isset( $block['attrs']['urlMobile'] ) &&
			! empty( $block['attrs']['urlMobile'] ) &&
			! empty( $mobile_size_slug )
			) {
				$mobile_dest_url             = isset( $attachment['sizes'][ $mobile_size_slug ]['url'] ) ? $attachment['sizes'][ $mobile_size_slug ]['url'] : $attachment['url'];
				$block['innerHTML']          = str_replace( $block['attrs']['urlMobile'], $mobile_dest_url, $block['innerHTML'] );
				$block['attrs']['urlMobile'] = $mobile_dest_url;
				ST_Importer_Log::add( 'Updated mobile image URL: ' . $mobile_dest_url );
			}

			$old_id             = $block['attrs']['id'];
			$block['innerHTML'] = str_replace( 'uag-image-' . $block['attrs']['id'], 'uag-image-' . $attachment['id'], $block['innerHTML'] );
			ST_Importer_Log::add( 'Updated image ID from ' . $old_id . ' to ' . $attachment['id'] );

			$replaced_inner_content = 0;
			foreach ( $block['innerContent'] as $key => &$inner_content ) {

				if ( is_string( $block['innerContent'][ $key ] ) && '' === trim( $block['innerContent'][ $key ] ) ) {
					continue;
				}
				$block['innerContent'][ $key ] = str_replace( $block['attrs']['url'], $attachment['url'], $block['innerContent'][ $key ] );

				if ( ! empty( $alt_text ) ) {
					$block['innerContent'][ $key ] = str_replace( 'alt=""', 'alt="' . esc_attr( $alt_text ) . '"', $block['innerContent'][ $key ] );
				}

				if ( isset( $block['attrs']['urlTablet'] ) && ! empty( $block['attrs']['urlTablet'] ) ) {
					$block['innerContent'][ $key ] = str_replace( $block['attrs']['urlTablet'], $tablet_dest_url, $block['innerContent'][ $key ] );
				}
				if ( isset( $block['attrs']['urlMobile'] ) && ! empty( $block['attrs']['urlMobile'] ) ) {
					$block['innerContent'][ $key ] = str_replace( $block['attrs']['urlMobile'], $mobile_dest_url, $block['innerContent'][ $key ] );
				}

				$block['innerContent'][ $key ] = str_replace( 'uag-image-' . $block['attrs']['id'], 'uag-image-' . $attachment['id'], $block['innerContent'][ $key ] );
				$replaced_inner_content++;
			}
			ST_Importer_Log::add( 'Replaced image URL in ' . $replaced_inner_content . ' inner content items' );

			$block['attrs']['url'] = $attachment['url'];
			$block['attrs']['id']  = $attachment['id'];
			$block['attrs']['alt'] = $attachment['alt'];

			ST_Replace_Images::get_instance()->increment_image_index();

			ST_Importer_Log::add( 'Successfully replaced Spectra image - Old: ' . $old_url . ', New: ' . $attachment['url'] );

			return $block;
		}

		/**
		 * Parses images and other content in the Spectra Info Box block.
		 *
		 * @since 4.1.0
		 * @param array<mixed> $block Block.
		 * @return array<mixed> $block Block.
		 */
		public function parse_spectra_gallery( $block ) {
			ST_Importer_Log::add( 'Starting parse_spectra_gallery' );

			$images         = $block['attrs']['mediaGallery'];
			$gallery_ids    = [];
			$replaced_count = 0;
			$skipped_count  = 0;

			ST_Importer_Log::add( 'Processing gallery with ' . count( $images ) . ' images' );

			foreach ( $images as $key => &$image ) {

				if (
				! isset( $image ) ||
				empty( $image ) ||
				ST_Replace_Images::is_skipable( $image['url'] )
				) {
					$skipped_count++;
					ST_Importer_Log::add( 'Skipping image #' . $key . ' - empty or skipable' );
					continue;
				}

				$old_url = $image['url'];
				ST_Importer_Log::add( 'Processing gallery image #' . $key . ': ' . $old_url );

				$new_image = ST_Replace_Images::get_instance()->get_image( ST_Replace_Images::$image_index );

				if ( empty( $new_image ) || ! is_array( $new_image ) ) {
					ST_Importer_Log::add( 'No replacement image available at index: ' . ST_Replace_Images::$image_index );
					continue;
				}

				ST_Importer_Log::add( 'Retrieved replacement image from index: ' . ST_Replace_Images::$image_index );
				$new_image = ST_Importer_Helper::download_image( $new_image );

				if ( is_wp_error( $new_image ) ) {
					ST_Importer_Log::add( 'Failed to download image: ' . $new_image->get_error_message() );
					continue;
				}

				ST_Importer_Log::add( 'Successfully downloaded image with ID: ' . $new_image );

				$attachment = wp_prepare_attachment_for_js( absint( $new_image ) );

				if ( ! is_array( $attachment ) ) {
					ST_Importer_Log::add( 'Failed to prepare attachment for JS' );
					continue;
				}

				$gallery_ids[]                       = $attachment['id'];
				ST_Replace_Images::$old_image_urls[] = $image['url'];

				$image['url']     = ! empty( $attachment['url'] ) ? $attachment['url'] : $image['url'];
				$image['sizes']   = ! empty( $attachment['sizes'] ) ? $attachment['sizes'] : $image['sizes'];
				$image['mime']    = ! empty( $attachment['mime'] ) ? $attachment['mime'] : $image['mime'];
				$image['type']    = ! empty( $attachment['type'] ) ? $attachment['type'] : $image['type'];
				$image['subtype'] = ! empty( $attachment['subtype'] ) ? $attachment['subtype'] : $image['subtype'];
				$image['id']      = ! empty( $attachment['id'] ) ? $attachment['id'] : $image['id'];
				$image['alt']     = ! empty( $attachment['alt'] ) ? $attachment['alt'] : $image['alt'];
				$image['link']    = ! empty( $attachment['link'] ) ? $attachment['link'] : $image['link'];

				ST_Replace_Images::get_instance()->increment_image_index();
				$replaced_count++;

				ST_Importer_Log::add( 'Successfully replaced gallery image #' . $key . ' - Old: ' . $old_url . ', New: ' . $attachment['url'] );
			}
			$block['attrs']['mediaGallery'] = $images;
			$block['attrs']['mediaIDs']     = $gallery_ids;

			ST_Importer_Log::add( 'Completed parse_spectra_gallery - Replaced: ' . $replaced_count . ', Skipped: ' . $skipped_count );

			return $block;
		}

		/**
		 * Fix to alter the Astra global color variables.
		 *
		 * @since {{since}}
		 * @param string $content Post Content.
		 * @return string $content Modified content.
		 */
		public function replace_content_glitch( $content ) {
			ST_Importer_Log::add( 'Starting replace_content_glitch' );

			$content = str_replace( 'var(\u002d\u002dast', 'var(--ast', $content );
			$content = str_replace( 'var(u002du002dast', 'var(--ast', $content );
			$content = str_replace( ' u0026', '&amp;', $content );
			$content = str_replace( '\u0026', '&amp;', $content );

			ST_Importer_Log::add( 'Completed replace_content_glitch' );
			return $content;
		}

	}

	/**
	 * Kicking this off by calling 'get_instance()' method
	 */
	ST_Replace_Blocks_Images::get_instance();

endif;
