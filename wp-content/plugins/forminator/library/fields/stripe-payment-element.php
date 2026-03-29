<?php
/**
 * The Forminator_Stripe_Payment_Element class.
 * It uses Stripe Payment Element to process payment.
 *
 * @package Forminator
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_Stripe_Payment_Element
 *
 * @since 1.37
 */
class Forminator_Stripe_Payment_Element extends Forminator_Field {

	/**
	 * Slug
	 *
	 * @var string
	 */
	public $slug = 'stripe-ocs';

	/**
	 * Type
	 *
	 * @var string
	 */
	public $type = 'stripe-ocs';

	/**
	 * Position
	 *
	 * @var int
	 */
	public $position = 23;


	/**
	 * Icon
	 *
	 * @var string
	 */
	public $icon = 'sui-icon forminator-icon-stripe';

	/**
	 * Is connected
	 *
	 * @var bool
	 */
	public $is_connected = false;

	/**
	 * Mode
	 *
	 * @var string
	 */
	public $mode = 'test';

	/**
	 * Payment plan
	 *
	 * @var array
	 */
	public $payment_plan = array();

	/**
	 * Forminator_Stripe constructor.
	 */
	public function __construct() {
		parent::__construct();

		$this->name = esc_html__( 'Stripe', 'forminator' );

		try {
			$stripe = new Forminator_Gateway_Stripe();
			if ( $stripe->is_test_ready() && $stripe->is_live_ready() ) {
				$this->is_connected = true;
			}
		} catch ( Forminator_Gateway_Exception $e ) {
			$this->is_connected = false;
		}
	}

	/**
	 * Field defaults
	 *
	 * @return array
	 */
	public function defaults() {

		$default_currency = 'USD';
		try {
			$stripe           = new Forminator_Gateway_Stripe();
			$default_currency = $stripe->get_default_currency();
		} catch ( Forminator_Gateway_Exception $e ) {
			forminator_maybe_log( __METHOD__, $e->getMessage() );
		}

		return array(
			'field_label'               => esc_html__( 'Credit / Debit Card', 'forminator' ),
			'mode'                      => 'test',
			'currency'                  => $default_currency,
			'company_name'              => '',
			'product_description'       => '',
			'customer_email'            => '',
			'receipt'                   => '',
			'billing'                   => '',
			'language'                  => 'auto',
			'options'                   => array(),
			'payments'                  => array(
				array(
					'plan_name'                => esc_html__( 'Plan 1', 'forminator' ),
					'payment_method'           => 'single',
					'amount_type'              => 'fixed',
					'amount'                   => '',
					'subscription_amount_type' => 'fixed',
					'quantity_type'            => 'fixed',
					'quantity'                 => '1',
					'bill_input'               => '1',
				),
			),
			'primary_color'             => '#0570DE',
			'background_color'          => '#FFFFFF',
			'text_color'                => '#30313D',
			'error'                     => '#DF1B41',
			'automatic_payment_methods' => 'true',
		);
	}

	/**
	 * Migrate stripe settings
	 *
	 * @param array $stripe_field Old stripe field.
	 *
	 * @return array
	 */
	public function migrate_stripe_settings( $stripe_field ) {
		// Merge default settings with old stripe field.
		$new_stripe_field = array_merge( $this->defaults(), $stripe_field );

		// Update stripe-ocs settings.
		$new_stripe_field['id']                        = 'stripe-ocs-1';
		$new_stripe_field['element_id']                = 'stripe-ocs-1';
		$new_stripe_field['type']                      = 'stripe-ocs';
		$new_stripe_field['automatic_payment_methods'] = 'false';
		$new_stripe_field['form_id']                   = 'wrapper-0000-0000';
		$new_stripe_field['wrapper_id']                = 'wrapper-0000-0000';
		$new_stripe_field['receipt']                   = 'true' === $new_stripe_field['receipt'] ? '1' : '';
		$new_stripe_field['billing']                   = 'true' === $new_stripe_field['billing'] ? '1' : '';

		// Remove unused settings.
		unset(
			$new_stripe_field['card_icon'],
			$new_stripe_field['verify_zip'],
			$new_stripe_field['zip_field'],
			$new_stripe_field['base_class'],
			$new_stripe_field['complete_class'],
			$new_stripe_field['empty_class'],
			$new_stripe_field['focused_class'],
			$new_stripe_field['invalid_class'],
			$new_stripe_field['autofilled_class'],
		);

		return $new_stripe_field;
	}
}
