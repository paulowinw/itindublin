<?php
/**
 * The Forminator_CForm_Renderer_Entries class.
 *
 * @package Forminator
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_CForm_Renderer_Entries
 *
 * @since 1.0.5
 */
class Forminator_CForm_Renderer_Entries extends Forminator_CForm_View_Page {

	/**
	 * Forminator_CForm_Renderer_Entries constructor
	 *
	 * @noinspection PhpMissingParentConstructorInspection
	 *
	 * Construct Entries Renderer
	 *
	 * @since 1.0.5
	 *
	 * @param string $folder Folder.
	 */
	public function __construct( $folder ) {
		$this->entries_construct( $folder );
	}
}
