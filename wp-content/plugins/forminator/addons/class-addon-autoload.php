<?php
/**
 * The Addon autoload.
 *
 * @package Forminator
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/class-addon-default-holder.php';

/**
 * Class Forminator_Addon_Autoload
 * Handling Autoloader
 *
 * @since 1.1
 */
class Forminator_Addon_Autoload {

	/**
	 * Pro integrations list
	 *
	 * @since 1.1
	 * @var array
	 */
	protected $pro_addons = array();

	/**
	 * Forminator_Addon_Autoload constructor.
	 * Intialize with custom pro integrations, or pass empty array otherwise
	 *
	 * @since 1.1
	 *
	 * @param array $pro_addons Pro addons.
	 */
	public function __construct( $pro_addons = array() ) {
		$this->pro_addons = $pro_addons;
	}

	/**
	 * Load Integrations which lies in `addons/pro` directory
	 * And load placeholder for pro integrations that are defined but not available in the pro directory
	 *
	 * @since 1.1
	 */
	public function load() {
		$pro_addons = $this->pro_addons;

		$pro_addons_dir = __DIR__ . '/pro/';

		/**
		 * Filter path of Pro integrations directory located
		 *
		 * @since 1.1
		 *
		 * @param string $pro_addons_dir current dir path of pro integrations.
		 */
		$pro_addons_dir = apply_filters( 'forminator_addon_pro_addons_dir', $pro_addons_dir );

		// All of Forminator Official Integrations must be registered here with fallback array.
		// fallback array will be used to display pro integrations on the list of integrations, without files on `/pro` being available.
		if ( empty( $pro_addons ) ) {
			$pro_addons = forminator_get_pro_addon_list();
		}
		// Load Available Pro Integration.
		$directory = new DirectoryIterator( $pro_addons_dir );
		foreach ( $directory as $d ) {
			if ( $d->isDot() || $d->isFile() ) {
				continue;
			}
			// take directory name as integration name.
			$addon_name = $d->getBasename();

			// new Integration !
			// valid integration is when integration have `addon_name.php` inside its integration directory.
			$addon_initiator = $d->getPathname() . DIRECTORY_SEPARATOR . $addon_name . '.php';
			if ( ! file_exists( $addon_initiator ) ) {
				continue;
			}
			// @noinspection PhpIncludeInspection.
			include_once $addon_initiator;
		}

		// Load unavailable Pro Integrations.
		$pro_slugs        = Forminator_Integration_Loader::get_instance()->get_addons()->get_slugs();
		$unavailable_pros = array_diff( array_keys( $pro_addons ), $pro_slugs );

		foreach ( $unavailable_pros as $unavailable_pro ) {
			if ( array_key_exists( $unavailable_pro, $pro_addons ) ) {
				$addon                                   = new Forminator_Integration_Default_Holder();
				$pro_addons[ $unavailable_pro ]['_slug'] = $unavailable_pro;

				$addon->from_array( $pro_addons[ $unavailable_pro ] );
				Forminator_Integration_Loader::get_instance()->register( $addon );
			}
		}
	}
}
