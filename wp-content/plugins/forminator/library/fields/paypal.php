<?php
/**
 * The Forminator_PayPal class.
 *
 * @package Forminator
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_PayPal
 *
 * @since 1.7
 */
class Forminator_PayPal extends Forminator_Field {

	/**
	 * Name
	 *
	 * @var string
	 */
	public $name = '';

	/**
	 * Slug
	 *
	 * @var string
	 */
	public $slug = 'paypal';

	/**
	 * Type
	 *
	 * @var string
	 */
	public $type = 'paypal';

	/**
	 * Position
	 *
	 * @var int
	 */
	public $position = 24;

	/**
	 * Options
	 *
	 * @var array
	 */
	public $options = array();

	/**
	 * Icon
	 *
	 * @var string
	 */
	public $icon = 'sui-icon forminator-icon-paypal';

	/**
	 * Is calculable
	 *
	 * @var bool
	 */
	public $is_connected = false;

	/**
	 * Forminator_PayPal constructor.
	 */
	public function __construct() {
		parent::__construct();

		$this->name = esc_html__( 'PayPal', 'forminator' );
	}

	/**
	 * Field defaults
	 *
	 * @return array
	 */
	public function defaults() {

		$default_currency = 'USD';
		try {
			$paypal           = new Forminator_PayPal_Express();
			$default_currency = $paypal->get_default_currency();
		} catch ( Forminator_Gateway_Exception $e ) {
			forminator_maybe_log( __METHOD__, $e->getMessage() );
		}

		return array(
			'mode'             => 'sandbox',
			'currency'         => $default_currency,
			'amount_type'      => 'fixed',
			'label'            => 'checkout',
			'color'            => 'gold',
			'shape'            => 'rect',
			'layout'           => 'vertical',
			'tagline'          => 'true',
			'shipping_address' => 'disable',
			'locale'           => 'en_US',
			'debug_mode'       => 'disable',
			'height'           => '40',
			'options'          => array(),
		);
	}

	/**
	 * Field front-end markup
	 *
	 * @param array                  $field Field.
	 * @param Forminator_Render_Form $views_obj Forminator_Render_Form object.
	 *
	 * @return mixed
	 */
	public function markup( $field, $views_obj ) {

		$settings            = $views_obj->model->settings;
		$this->field         = $field;
		$this->form_settings = $settings;

		$element_name        = self::get_property( 'element_id', $field );
		$field_id            = $element_name . '-field';
		$mode                = self::get_property( 'mode', $field, 'sandbox' );
		$currency            = self::get_property( 'currency', $field, $this->get_default_currency() );
		$amount_type         = self::get_property( 'amount_type', $field, 'fixed' );
		$amount              = self::get_property( 'amount', $field, '0' );
		$amount_variable     = self::get_property( 'variable', $field, '' );
		$logo                = self::get_property( 'logo', $field, '' );
		$company_name        = esc_html( self::get_property( 'company_name', $field, '' ) );
		$product_description = esc_html( self::get_property( 'product_description', $field, '' ) );
		$customer_email      = self::get_property( 'customer_email', $field, '' );
		$checkout_label      = esc_html( self::get_property( 'checkout_label', $field, '' ) );
		$collect_address     = esc_html( self::get_property( 'collect_address', $field, 'none', 'string' ) );
		$verify_zip          = esc_html( self::get_property( 'verify_zip', $field, false, 'bool' ) );
		$language            = self::get_property( 'language', $field, 'en' );

		$attr = array(
			'type'              => 'hidden',
			'name'              => $element_name,
			'id'                => 'forminator-' . $field_id . '_' . Forminator_CForm_Front::$uid,
			'class'             => 'forminator-paypal-input',
			'data-is-payment'   => 'true',
			'data-payment-type' => $this->type,
			'data-currency'     => esc_html( strtolower( $currency ) ),
			'data-amount-type'  => esc_html( $amount_type ),
			'data-amount'       => ( 'fixed' === $amount_type ? esc_html( $amount ) : $amount_variable ),
			'data-label'        => esc_html( $checkout_label ),
			'data-locale'       => esc_html( $language ),
		);

		if ( ! empty( $logo ) ) {
			$attr['data-image'] = esc_url( $logo );
		}

		if ( ! empty( $company_name ) ) {
			$attr['data-name'] = esc_html( $company_name );
		}

		if ( ! empty( $company_name ) ) {
			$attr['data-description'] = esc_html( $product_description );
		}

		if ( ! empty( $customer_email ) ) {
			$attr['data-email'] = esc_html( $customer_email );
		}

		if ( 'billing' === $collect_address || 'billing_shipping' === $collect_address ) {
			$attr['data-billing-address'] = 'true';
		}

		if ( 'billing_shipping' === $collect_address ) {
			$attr['data-shipping-address'] = 'true';
		}

		if ( $verify_zip ) {
			$attr['data-zip-code'] = 'true';
		}

		$html = self::create_input( $attr );

		return apply_filters( 'forminator_field_paypal_markup', $html, $attr, $field );
	}


	/**
	 * Field back-end validation
	 *
	 * @param array        $field Field.
	 * @param array|string $data Data.
	 */
	public function validate( $field, $data ) {
		$id = self::get_property( 'element_id', $field );
	}

	/**
	 * Sanitize data
	 *
	 * @param array        $field Field.
	 * @param array|string $data - the data to be sanitized.
	 *
	 * @return array|string $data - the data after sanitization
	 */
	public function sanitize( $field, $data ) {
		$original_data = $data;
		// Sanitize.
		$data = forminator_sanitize_field( $data );

		return apply_filters( 'forminator_field_paypal_sanitize', $data, $field, $original_data );
	}

	/**
	 * Is available
	 *
	 * @since 1.7
	 * @inheritdoc
	 * @param mixed $field Field.
	 */
	public function is_available( $field ) {
		$mode = self::get_property( 'mode', $field, 'sandbox' );
		try {
			$paypal = new Forminator_PayPal_Express();

			if ( 'sandbox' !== $mode ) {
				$paypal->set_live( true );
			}

			if ( $paypal->is_ready() ) {
				return true;
			}
		} catch ( Forminator_Gateway_Exception $e ) {
			return false;
		}
	}

	/**
	 * Get publishable key
	 *
	 * @since 1.7
	 *
	 * @param bool $live Is live?.
	 *
	 * @return bool|string
	 */
	private function get_publishable_key( $live = false ) {
		try {
			$paypal = new Forminator_PayPal_Express();

			if ( $live ) {
				return $paypal->get_live_id();
			}

			return $paypal->get_sandbox_id();
		} catch ( Forminator_Gateway_Exception $e ) {
			return false;
		}
	}

	/**
	 * Get default currency
	 *
	 * @return string
	 */
	private function get_default_currency() {
		try {
			$paypal = new Forminator_PayPal_Express();

			return $paypal->get_default_currency();

		} catch ( Forminator_Gateway_Exception $e ) {
			return 'USD';
		}
	}

	/**
	 * Process entry data
	 *
	 * @param array $field Field.
	 *
	 * @return array
	 */
	public function process_to_entry_data( $field ) {
		$entry_data = array(
			'mode'             => '',
			'status'           => '',
			'amount'           => '',
			'currency'         => '',
			'transaction_id'   => '',
			'transaction_link' => '',
		);

		$element_id = self::get_property( 'element_id', $field );
		$mode       = self::get_property( 'mode', $field, 'sandbox' );
		$currency   = self::get_property( 'currency', $field, $this->get_default_currency() );

		$entry_data['mode']     = $mode;
		$entry_data['currency'] = $currency;
		$charge_amount          = $this->get_payment_amount( $field );
		$charge_amount          = number_format( $charge_amount, 2, '.', '' );
		$transaction_id         = Forminator_CForm_Front_Action::$prepared_data['payment_transaction_id'];

		$paypal = new Forminator_PayPal_Express();
		$order  = $paypal->get_order( $transaction_id, $mode );

		// Validate intent.
		if ( ! isset( $order->intent ) || 'CAPTURE' !== $order->intent ) {
			return array(
				'error' => esc_html__( 'Error! Something went wrong during checkout and payment couldn\'t be approved.', 'forminator' ),
			);
		}

		// Validate status.
		if ( ! isset( $order->status ) || 'APPROVED' !== $order->status ) {
			return array(
				'error' => esc_html__( 'Error! Something went wrong during checkout and payment couldn\'t be approved.', 'forminator' ),
			);
		}

		// Validate amount.
		if ( ! isset( $order->purchase_units[0]->amount->value ) || floatval( $order->purchase_units[0]->amount->value ) !== floatval( $charge_amount ) ) {
			return array(
				'error' => esc_html__( 'Error! Invalid payment amount!', 'forminator' ),
			);
		}

		// Validate currency.
		if ( ! isset( $order->purchase_units[0]->amount->currency_code ) || $order->purchase_units[0]->amount->currency_code !== $currency ) {
			return array(
				'error' => esc_html__( 'Error! Invalid currency code!', 'forminator' ),
			);
		}

		$entry_data['amount']         = $charge_amount;
		$entry_data['status']         = $order->status;
		$entry_data['transaction_id'] = $transaction_id;

		$entry_data['transaction_link'] = self::get_transanction_link( $mode, $transaction_id );
		/**
		 * Filter PayPal entry data that will be stored
		 *
		 * @since 1.7
		 *
		 * @param array                        $entry_data
		 * @param array                        $field            field properties.
		 * @param Forminator_Form_Model $module_object
		 * @param array                        $submitted_data
		 * @param array                        $field_data_array current entry meta.
		 *
		 * @return array
		 */
		$entry_data = apply_filters( 'forminator_field_paypal_process_to_entry_data', $entry_data, $field, Forminator_Front_Action::$module_object, Forminator_CForm_Front_Action::$prepared_data, Forminator_CForm_Front_Action::$info['field_data_array'] );

		return $entry_data;
	}

	/**
	 * Get transaction link
	 *
	 * @param string $mode Payment mode.
	 * @param string $transaction_id Transaction id.
	 * @return string
	 */
	public static function get_transanction_link( $mode, $transaction_id ) {
		if ( 'sandbox' === $mode ) {
			$link_base = 'https://www.sandbox.paypal.com/activity/payment/';
		} else {
			$link_base = 'https://www.paypal.com/activity/payment/';
		}
		$transaction_link = $link_base . rawurlencode( $transaction_id );

		return $transaction_link;
	}

	/**
	 * Make linkify transaction_id
	 *
	 * @param string $transaction_id Transaction Id.
	 * @param array  $meta_value Meta value.
	 *
	 * @return string
	 */
	public static function linkify_transaction_id( $transaction_id, $meta_value ) {
		$transaction_link = $transaction_id;
		if ( isset( $meta_value['transaction_link'] ) && ! empty( $meta_value['transaction_link'] ) ) {
			$url              = $meta_value['transaction_link'];
			$transaction_link = '<a href="' . $url . '" target="_blank" rel="noopener noreferrer" title="' . $transaction_id . '">' . $transaction_id . '</a>';
		}

		/**
		 * Filter link to PayPal transaction id
		 *
		 * @since 1.7
		 *
		 * @param string $transaction_link
		 * @param string $transaction_id
		 * @param array  $meta_value
		 *
		 * @return string
		 */
		$transaction_link = apply_filters( 'forminator_field_paypal_linkify_transaction_id', $transaction_link, $transaction_id, $meta_value );

		return $transaction_link;
	}

	/**
	 * Get payment amount
	 *
	 * @since 1.7
	 *
	 * @param array $field Field.
	 *
	 * @return double
	 */
	public function get_payment_amount( $field ) {
		$payment_amount = 0.0;
		$amount_type    = self::get_property( 'amount_type', $field, 'fixed' );
		$amount         = self::get_property( 'amount', $field, '0' );
		$amount_var     = self::get_property( 'variable', $field, '' );

		if ( 'fixed' === $amount_type ) {
			$payment_amount = $amount;
		} else {
			$form_field = Forminator_Front_Action::$module_object->get_field( $amount_var, false );
			if ( $form_field ) {
				$form_field = $form_field->to_formatted_array();
				if ( isset( $form_field['type'] ) ) {
					$field_id             = $form_field['element_id'];
					$submitted_field_data = isset( Forminator_CForm_Front_Action::$prepared_data[ $field_id ] )
							? Forminator_CForm_Front_Action::$prepared_data[ $field_id ]
							: null;
					if ( 'calculation' === $form_field['type'] ) {

						// Calculation field get the amount from pseudo_submit_data.
						if ( isset( Forminator_CForm_Front_Action::$prepared_data[ $amount_var ] ) ) {
							$payment_amount = Forminator_CForm_Front_Action::$prepared_data[ $amount_var ];
						}
					} elseif ( 'currency' === $form_field['type'] ) {
						// Currency field get the amount from submitted_data.
						if ( ! is_null( $submitted_field_data ) ) {
							$payment_amount = self::forminator_replace_number( $form_field, $submitted_field_data );
						}
					} else {
						$field_object = Forminator_Core::get_field_object( $form_field['type'] );
						if ( $field_object ) {
							$payment_amount = $field_object::get_calculable_value( $submitted_field_data, $form_field );
						}
					}
				}
			}
		}

		if ( ! is_numeric( $payment_amount ) ) {
			$payment_amount = 0.0;
		}

		/**
		 * Filter payment amount of PayPal
		 *
		 * @since 1.7
		 *
		 * @param double                       $payment_amount
		 * @param array                        $field field settings.
		 * @param Forminator_Form_Model $module_object
		 * @param array                        $prepared_data
		 */
		$payment_amount = apply_filters( 'forminator_field_paypal_payment_amount', $payment_amount, $field, Forminator_Front_Action::$module_object, Forminator_CForm_Front_Action::$prepared_data );

		return $payment_amount;
	}

	/**
	 * Get the fields that an amount depends on
	 *
	 * @param array $field_settings Field settings.
	 * @return array
	 */
	public function get_amount_dependent_fields( $field_settings ) {
		$depend_field = array();
		$amount_type  = self::get_property( 'amount_type', $field_settings, 'fixed' );
		$amount_var   = self::get_property( 'variable', $field_settings, '' );

		if ( 'variable' === $amount_type && ! empty( $amount_var ) ) {
			$depend_field[] = $amount_var;
		}

		return $depend_field;
	}
}
