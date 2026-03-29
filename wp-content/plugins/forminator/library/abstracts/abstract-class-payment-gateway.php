<?php
/**
 * The Forminator Payment Gateway.
 *
 * @package Forminator
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_Payment_Gateway
 *
 * Payment Gateway abstract class
 *
 * @since 1.0
 */
abstract class Forminator_Payment_Gateway {

	/**
	 * Gateway slug
	 *
	 * @var string
	 */
	protected $_slug = '';

	/**
	 * Enabled based on settings
	 *
	 * @var bool
	 */
	protected $_enabled = false;

	/**
	 * The total field name
	 *
	 * @var string
	 */
	protected $_total_field = '';

	/**
	 * Forminator_Payment_Gateway constructor.
	 *
	 * @since 1.0
	 */
	public function __construct() {
		// Init settings.
		$this->init_settings();

		// Handle purchases.
		if ( $this->_enabled ) {
			add_filter( 'forminator_cform_process_purchase', array( $this, 'process_handle_purchase' ), 10, 5 );
		}
	}

	/**
	 * Initialize settings
	 *
	 * @since 1.0
	 */
	abstract public function init_settings();

	/**
	 * Handle Purchase
	 *
	 * @since 1.0
	 *
	 * @param array $response       - the response array.
	 * @param array $product_fields - the product fields.
	 * @param array $field_data_array Field data.
	 * @param int   $entry_id       - the entry id ( reference for callback).
	 * @param int   $page_id        - the page id. Used to generate a return url.
	 * @param int   $shipping       - the shipping cost.
	 *
	 * @return array $response
	 */
	public function process_handle_purchase( $response, $product_fields, $field_data_array, $entry_id, $page_id, $shipping ) {
		return $this->handle_purchase( $response, $product_fields, $field_data_array, $entry_id, $page_id, $shipping );
	}

	/**
	 * Handle Purchase
	 * Implemented in child class
	 *
	 * @since 1.0
	 * @param array $response - the response array.
	 * @param array $product_fields - the product fields.
	 * @param array $field_data_array Field data.
	 * @param int   $entry_id - the entry id ( reference for callback).
	 * @param int   $page_id - the page id. Used to generate a return url.
	 * @param int   $shipping - the shipping cost.
	 *
	 * @return array $response
	 */
	protected function handle_purchase( $response, $product_fields, $field_data_array, $entry_id, $page_id, $shipping ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		return $response;
	}

	/**
	 * Gateway footer scripts
	 *
	 * @since 1.0
	 */
	abstract public function gateway_footer_scripts();
}
