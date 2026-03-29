<?php
/**
 * The Addon Default Holder.
 *
 * @package    Forminator
 */

/**
 * Class Forminator_Integration_Default_Holder
 * Placeholder for nonexistent PRO Integration
 *
 * @since 1.1
 */
class Forminator_Integration_Default_Holder extends Forminator_Integration {

	/**
	 * Forminator_Integration_Default_Holder instance
	 *
	 * @var Forminator_Integration_Default_Holder
	 */
	protected static $instance = null;

	/**
	 * Slug
	 *
	 * @var string
	 */
	protected $_slug = '';

	/**
	 * Version
	 *
	 * @var string
	 */
	protected $_version = '1.0';

	/**
	 * Min Forminator Version
	 *
	 * @var integer
	 */
	protected $_min_forminator_version = PHP_INT_MAX; // make it un-activable.

	/**
	 * Short title
	 *
	 * @var string
	 */
	protected $_short_title = '';

	/**
	 * Title
	 *
	 * @var string
	 */
	protected $_title = '';

	/**
	 * URL
	 *
	 * @var string
	 */
	protected $_url = '';

	/**
	 * Dynamically set fields form array
	 *
	 * @since 1.1
	 *
	 * @param array $properties Properties.
	 *
	 * @return $this
	 */
	public function from_array( $properties ) {
		foreach ( $properties as $field => $value ) {
			if ( property_exists( $this, $field ) ) {
				$this->$field = $value;
			}
		}

		return $this;
	}

	/**
	 * Mark non existent integration as not connected always
	 *
	 * @since 1.1
	 * @return bool
	 */
	public function is_connected() {
		return false;
	}

	/**
	 * Authorized Callback
	 *
	 * @return bool
	 */
	public function is_authorized() {
		return false;
	}

	/**
	 * Mark non existent integration as form not connected always
	 *
	 * @since 1.1
	 * @param int    $module_id Form ID.
	 * @param string $module_slug Module type.
	 * @param bool   $check_lead Check is lead connected or not.
	 * @return bool
	 */
	public function is_module_connected( $module_id, $module_slug = 'form', $check_lead = false ) {
		return false;
	}

	/**
	 * Make this not activable
	 *
	 * @since 1.1
	 * @return bool
	 */
	public function check_is_activable() {
		return false;
	}
}
