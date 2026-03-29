<?php
/**
 * Integration Name: Gutenberg
 * Version: 1.0
 * Plugin URI:  https://wpmudev.com/
 * Description: Gutenberg blocks for Forminator
 * Author: WPMU DEV
 * Author URI: http://wpmudev.com
 *
 * @package Forminator
 */

define( 'FORMINATOR_ADDON_GUTENBERG_VERSION', '1.0' );

// Load Gutenberg module after Forminator loaded.
add_action( 'init', array( 'Forminator_Gutenberg', 'init' ), 5 );

/**
 * Class Forminator_Gutenberg
 */
class Forminator_Gutenberg {
	/**
	 * Forminator_Gutenberg instance
	 *
	 * @var Forminator_Gutenberg|null
	 */
	private static $_instance = null;

	/**
	 * Get Instance
	 *
	 * @since 1.0 Gutenberg Integration
	 * @return self|null
	 */
	public static function get_instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Initialize integration
	 *
	 * @since 1.0 Gutenberg Integration
	 */
	public static function init() {
		// Load abstracts.
		require_once __DIR__ . '/library/class-forminator-gfblock-abstract.php';

		// Load blocks.
		self::load_blocks();
	}

	/**
	 * Automatically include blocks files
	 *
	 * @since 1.0 Gutenberg Integration
	 */
	public static function load_blocks() {
		// Load blocks automatically.
		foreach ( glob( plugin_dir_path( __FILE__ ) . 'library/blocks/class-forminator-gfblock-*.php' ) as $file ) {
			require_once $file;
		}
	}

	/**
	 * Return Integration URL
	 *
	 * @since 1.0 Gutenberg Integration
	 *
	 * @return mixed
	 */
	public function get_plugin_url() {
		return trailingslashit( forminator_plugin_url() . 'addons/pro/gutenberg' );
	}

	/**
	 * Return Integration DIR
	 *
	 * @since 1.0 Gutenberg Integration
	 *
	 * @return mixed
	 */
	public function get_plugin_dir() {
		return trailingslashit( __DIR__ );
	}
}

// Load Gutenberb functions.
require_once __DIR__ . '/functions.php';
