<?php
/**
 * The Forminator_Quiz_Renderer_Entries class.
 *
 * @package Forminator
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_Quiz_Renderer_Entries
 *
 * @since 1.0.5
 */
class Forminator_Quiz_Renderer_Entries extends Forminator_Quiz_View_Page {

	/**
	 * Construct Entries Renderer
	 *
	 * @noinspection PhpMissingParentConstructorInspection
	 *
	 * @since 1.0.5
	 *
	 * @param string $folder Folder.
	 */
	public function __construct( $folder ) {
		$this->entries_construct( $folder );
	}
}
