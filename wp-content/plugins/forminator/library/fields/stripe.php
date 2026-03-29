<?php
/**
 * The Forminator_Stripe class.
 * It uses Stripe Card Element to process payment.
 *
 * @package Forminator
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_Stripe
 *
 * @since 1.7
 */
class Forminator_Stripe extends Forminator_Field {

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
	public $slug = 'stripe';

	/**
	 * Type
	 *
	 * @var string
	 */
	public $type = 'stripe';

	/**
	 * Position
	 *
	 * @var int
	 */
	public $position = 23;

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
	 * Payment plan hash
	 *
	 * @var string
	 */
	public string $payment_plan_hash = '';

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
			'field_label'              => esc_html__( 'Credit / Debit Card', 'forminator' ),
			'mode'                     => 'test',
			'currency'                 => $default_currency,
			'amount_type'              => 'fixed',
			'logo'                     => '',
			'company_name'             => '',
			'product_description'      => '',
			'customer_email'           => '',
			'receipt'                  => 'false',
			'billing'                  => 'false',
			'verify_zip'               => 'false',
			'card_icon'                => 'true',
			'language'                 => 'auto',
			'options'                  => array(),
			'base_class'               => 'StripeElement',
			'complete_class'           => 'StripeElement--complete',
			'empty_class'              => 'StripeElement--empty',
			'focused_class'            => 'StripeElement--focus',
			'invalid_class'            => 'StripeElement--invalid',
			'autofilled_class'         => 'StripeElement--webkit-autofill',
			'subscription_amount_type' => 'fixed',
			'quantity_type'            => 'fixed',
			'payments'                 => array(
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
		$is_ocs              = 'stripe-ocs' === $field['type'];

		// Don't render stripe field if there is stripe-ocs field in the form.
		if ( ! $is_ocs && $views_obj->has_field_type( 'stripe-ocs' ) ) {
			return '';
		}

		$id               = self::get_property( 'element_id', $field );
		$description      = self::get_property( 'description', $field, '' );
		$descr_position   = self::get_description_position( $field, $settings );
		$label            = esc_html( self::get_property( 'field_label', $field, '' ) );
		$element_name     = $id;
		$field_id         = $id . '-field';
		$mode             = self::get_property( 'mode', $field, 'test' );
		$currency         = self::get_property( 'currency', $field, $this->get_default_currency() );
		$amount           = self::get_property( 'amount', $field, 1 );
		$amount_variable  = self::get_property( 'variable', $field, '' );
		$card_icon        = self::get_property( 'card_icon', $field, true );
		$verify_zip       = self::get_property( 'verify_zip', $field, false );
		$zip_field        = self::get_property( 'zip_field', $field, '' );
		$language         = self::get_property( 'language', $field, 'auto' );
		$base_class       = self::get_property( 'base_class', $field, 'StripeElement' );
		$complete_class   = self::get_property( 'complete_class', $field, 'StripeElement--complete' );
		$empty_class      = self::get_property( 'empty_class', $field, 'StripeElement--empty' );
		$focused_class    = self::get_property( 'focused_class', $field, 'StripeElement--focus' );
		$invalid_class    = self::get_property( 'invalid_class', $field, 'StripeElement--invalid' );
		$autofilled_class = self::get_property( 'autofilled_class', $field, 'StripeElement--webkit-autofill' );
		$billing          = self::get_property( 'billing', $field, false );
		$billing_name     = self::get_property( 'billing_name', $field, '' );
		$billing_email    = self::get_property( 'billing_email', $field, '' );
		$billing_address  = self::get_property( 'billing_address', $field, '' );
		$receipt          = self::get_property( 'receipt', $field, false );
		$customer_email   = self::get_property( 'customer_email', $field, '' );
		$metadata         = self::get_property( 'options', $field, array() );
		$desc             = self::get_property( 'product_description', $field, '' );
		$company          = self::get_property( 'company_name', $field, '' );
		$uniqid           = Forminator_CForm_Front::$uid;
		$id_prefix        = $is_ocs ? 'payment-element' : 'card-element';
		$full_id          = $id_prefix . '-' . $uniqid;
		$prefix           = 'basic' === $settings['form-style'] ? 'basic-' : '';

		if ( mb_strlen( $company ) > 22 ) {
			$company = mb_substr( $company, 0, 19 ) . '...';
		}

		$customer_email = forminator_clear_field_id( $customer_email );
		$custom_fonts   = false;

		// Generate payment intent object.
		$this->mode = $mode;

		if ( isset( $settings[ $prefix . 'form-font-family' ] ) && 'custom' === $settings[ $prefix . 'form-font-family' ] ) {
			$custom_fonts = true;
		}

		if ( ! isset( $settings['form-substyle'] ) ) {
			$settings['form-substyle'] = 'default';
		}

		$data_font_family = 'inherit';
		$data_font_size   = '16px';
		$data_font_weight = '400';

		if ( ! empty( $settings[ $prefix . 'form-font-family' ] ) ) {
			$field_font_family = $this->get_form_setting( $prefix . 'cform-input-font-family', $settings, $data_font_family );
			$data_font_size    = $this->get_form_setting( $prefix . 'cform-input-font-size', $settings, '16' ) . 'px';
			$data_font_weight  = $this->get_form_setting( $prefix . 'cform-input-font-weight', $settings, $data_font_weight );

			if ( 'custom' === $field_font_family ) {
				$data_font_family = $this->get_form_setting( $prefix . 'cform-input-custom-family', $settings, $data_font_family );
			} else {
				$data_font_family = $field_font_family;
			}
		}

		$data_placeholder      = '#888888';
		$data_font_color       = '#000000';
		$data_font_color_focus = '#000000';
		$data_font_color_error = '#000000';
		$data_icon_color       = '#777771';
		$data_icon_color_hover = '#097BAA';
		$data_icon_color_focus = '#097BAA';
		$data_icon_color_error = '#E51919';

		if ( ! empty( $settings[ $prefix . 'cform-color-settings' ] ) ) {
			$data_placeholder      = $this->get_form_setting( $prefix . 'input-placeholder', $settings, $data_placeholder );
			$data_font_color       = $this->get_form_setting( $prefix . 'input-color', $settings, $data_font_color );
			$data_font_color_focus = $this->get_form_setting( $prefix . 'input-color', $settings, $data_font_color_focus );
			$data_font_color_error = $this->get_form_setting( $prefix . 'input-color', $settings, $data_font_color_error );
			$data_icon_color       = $this->get_form_setting( $prefix . 'input-icon', $settings, $data_icon_color );
			$data_icon_color_hover = $this->get_form_setting( $prefix . 'input-icon-hover', $settings, $data_icon_color_hover );
			$data_icon_color_focus = $this->get_form_setting( $prefix . 'input-icon-focus', $settings, $data_icon_color_focus );
			$data_icon_color_error = $this->get_form_setting( $prefix . 'label-validation-color', $settings, $data_icon_color_error );
		}

		$attr = array(
			'data-field-id'         => $uniqid,
			'data-is-payment'       => 'true',
			'data-payment-type'     => $this->type,
			'data-is-ocs'           => $is_ocs,
			'data-secret'           => '',
			'data-paymentid'        => '',
			'data-currency'         => strtolower( $currency ),
			'data-key'              => esc_html( $this->get_publishable_key( 'test' !== $mode ) ),
			'data-card-icon'        => filter_var( $card_icon, FILTER_VALIDATE_BOOLEAN ),
			'data-veify-zip'        => filter_var( $verify_zip, FILTER_VALIDATE_BOOLEAN ),
			'data-zip-field'        => esc_html( $zip_field ),
			'data-language'         => esc_html( $language ),
			'data-base-class'       => esc_html( $base_class ),
			'data-complete-class'   => esc_html( $complete_class ),
			'data-empty-class'      => esc_html( $empty_class ),
			'data-focused-class'    => esc_html( $focused_class ),
			'data-invalid-class'    => esc_html( $invalid_class ),
			'data-autofilled-class' => esc_html( $autofilled_class ),
			'data-billing'          => filter_var( $billing, FILTER_VALIDATE_BOOLEAN ),
			'data-billing-name'     => esc_html( $billing_name ),
			'data-billing-email'    => esc_html( $billing_email ),
			'data-billing-address'  => esc_html( $billing_address ),
			'data-receipt'          => filter_var( $receipt, FILTER_VALIDATE_BOOLEAN ),
			'data-receipt-email'    => esc_html( $customer_email ),
			'data-custom-fonts'     => $custom_fonts,
			'data-placeholder'      => $data_placeholder,
			'data-font-color'       => $data_font_color,
			'data-font-color-focus' => $data_font_color_focus,
			'data-font-color-error' => $data_font_color_error,
			'data-font-size'        => $data_font_size,
			'data-font-family'      => $data_font_family,
			'data-font-weight'      => $data_font_weight,
			'data-icon-color'       => $data_icon_color,
			'data-icon-color-hover' => $data_icon_color_hover,
			'data-icon-color-focus' => $data_icon_color_focus,
			'data-icon-color-error' => $data_icon_color_error,
		);

		if ( $is_ocs ) {
			$elements_options  = array(
				'loader'                => 'always',
				'locale'                => $language,
				'paymentMethodCreation' => 'manual',
			);
			$variables         = array(
				'fontWeightNormal'      => $data_font_weight,
				'fontSizeBase'          => $data_font_size,
				'iconColor'             => $data_icon_color,
				'iconHoverColor'        => $data_icon_color_hover,
				'iconCardErrorColor'    => $data_icon_color_error,
				'iconCardCvcErrorColor' => $data_icon_color_error,
				'colorTextPlaceholder'  => $data_placeholder,
			);
			$custom_appearance = self::get_property( 'custom_appearance', $field, false );
			if ( $custom_appearance ) {
				$spacing = self::get_property( 'spacing_unit', $field, '' );
				if ( $spacing ) {
					$variables['spacingUnit'] = $spacing . 'px';
				}
				$border_radius = self::get_property( 'border_radius', $field, '' );
				if ( $border_radius ) {
					$variables['borderRadius'] = $border_radius . 'px';
				}
				$variables['colorPrimary']    = self::get_property( 'primary_color', $field, '' );
				$variables['colorBackground'] = self::get_property( 'background_color', $field, '' );
				$variables['colorText']       = self::get_property( 'text_color', $field, '' );
				$variables['colorDanger']     = self::get_property( 'error', $field, '' );
			}
			// Remove empty values.
			$variables = array_filter( $variables );

			$appearance = array(
				'theme'     => self::get_property( 'theme', $field, 'stripe' ),
				'variables' => $variables,
			);
			if ( $custom_fonts && $data_font_family ) {
				$appearance['variables']['fontFamily'] = $data_font_family;
				$elements_options['fonts'][]           = array(
					'family' => $data_font_family,
					'cssSrc' => 'https://fonts.bunny.net/css?family=' . $data_font_family,
				);
			}
			$elements_options['appearance'] = $appearance;

			$dynamic_methods = self::get_property( 'automatic_payment_methods', $field, 'true' );
			// If Only card is enabled, disable other payment methods.
			if ( 'false' === $dynamic_methods ) {
				$elements_options['paymentMethodTypes'] = array( 'card' );
			}

			/**
			 * Filter Stripe OCS Elements options
			 *
			 * @since 1.38
			 *
			 * @param array $elements_options Elements options.
			 * @param array $field Field.
			 */
			$elements_options = apply_filters( 'forminator_field_stripe_ocs_elements_options', $elements_options, $field );

			$payment_options = array(
				'layout' => self::get_layout( $field ),
			);

			if ( 'false' === $dynamic_methods ) {
				$payment_options['wallets'] = array(
					'applePay'  => 'never',
					'googlePay' => 'never',
				);
			}
			/**
			 * Filter Stripe OCS Payment options
			 *
			 * @since 1.38
			 *
			 * @param array $payment_options Payment options.
			 * @param array $field Field.
			 */
			$payment_options = apply_filters( 'forminator_field_stripe_ocs_elements_options', $payment_options, $field );
			$billing_phone   = self::get_property( 'billing_phone', $field, '' );

			$attr = array(
				'data-elements-options' => wp_json_encode( $elements_options, JSON_PRETTY_PRINT ),
				'data-payment-options'  => wp_json_encode( $payment_options, JSON_PRETTY_PRINT ),
				'data-field-id'         => $uniqid,
				'data-is-payment'       => 'true',
				'data-payment-type'     => $this->type,
				'data-is-ocs'           => $is_ocs,
				'data-secret'           => '',
				'data-paymentid'        => '',
				'data-currency'         => strtolower( $currency ),
				'data-key'              => esc_html( $this->get_publishable_key( 'test' !== $mode ) ),
				'data-receipt'          => filter_var( $receipt, FILTER_VALIDATE_BOOLEAN ),
				'data-receipt-email'    => esc_html( $customer_email ),
				'data-billing'          => filter_var( $billing, FILTER_VALIDATE_BOOLEAN ),
				'data-billing-name'     => esc_html( $billing_name ),
				'data-billing-email'    => esc_html( $billing_email ),
				'data-billing-phone'    => esc_html( $billing_phone ),
				'data-billing-address'  => esc_html( $billing_address ),
				'data-return-url'       => esc_url( self::get_return_url() ),
			);
		}

		if ( ! empty( $description ) ) {
			$attr['aria-describedby'] = esc_attr( $full_id . '-description' );
		}

		$attributes = self::implode_attr( $attr );

		$html = '<div class="forminator-field">';

		$html .= self::get_field_label( $label, $id . '-field', true );

		if ( 'above' === $descr_position ) {
			$html .= self::get_description( $description, $full_id, $descr_position );
		}

		if ( 'material' === $settings['form-substyle'] ) {
			$classes = 'forminator-input--wrap forminator-input--stripe';

			if ( empty( $label ) ) {
				$classes .= ' forminator--no_label';
			}

			$html .= '<div class="' . $classes . '">';
		}

		$html .= sprintf( '<div id="%s" %s class="forminator-stripe-element%s"></div>', $full_id, $attributes, ( $is_ocs ? ' forminator-stripe-payment-element' : '' ) );

		$html .= sprintf( '<input type="hidden" name="paymentid" value="%s" id="forminator-stripe-paymentid"/>', '' );
		$html .= sprintf( '<input type="hidden" name="paymentmethod" value="%s" id="forminator-stripe-paymentmethod"/>', '' );
		$html .= sprintf( '<input type="hidden" name="subscriptionid" value="%s" id="forminator-stripe-subscriptionid"/>', '' );

		if ( 'material' === $settings['form-substyle'] ) {
			$html .= '</div>';
		}

		$html .= '<span class="forminator-card-message"><span class="forminator-error-message" aria-hidden="true"></span></span>';

		if ( 'above' !== $descr_position ) {
			$html .= self::get_description( $description, $full_id, $descr_position );
		}

		$html .= '</div>';

		return apply_filters( 'forminator_field_stripe_markup', $html, $attr, $field );
	}

	/**
	 * Get layout Stripe settings
	 *
	 * @param array $field Field.
	 * @return array
	 */
	private static function get_layout( $field ) {
		$layout = self::get_property( 'layout', $field, 'tabs' );
		if ( 'accordion+radio' === $layout ) {
			$radios = true;
			$layout = 'accordion';
		}
		$data = array(
			'type'             => $layout,
			'defaultCollapsed' => false,
		);

		if ( 'accordion' === $layout ) {
			$data['spacedAccordionItems'] = false;
			$data['radios']               = ! empty( $radios );
		}
		return $data;
	}

	/**
	 * Generate Payment Intent object
	 *
	 * @since 1.7.3
	 *
	 * @param int|float $amount Amount.
	 * @param array     $field Field.
	 *
	 * @return mixed
	 */
	public function generate_paymentIntent( $amount, $field ) {
		$currency    = self::get_property( 'currency', $field, $this->get_default_currency() );
		$mode        = self::get_property( 'mode', $field, 'test' );
		$metadata    = self::get_property( 'options', $field, array() );
		$description = esc_html( self::get_property( 'product_description', $field, '' ) );
		$company     = esc_html( self::get_property( 'company_name', $field, '' ) );

		if ( mb_strlen( $company ) > 22 ) {
			$company = mb_substr( $company, 0, 19 ) . '...';
		}

		$key = $this->get_secret_key( 'test' !== $mode );
		\Forminator\Stripe\Stripe::setApiKey( $key );

		Forminator_Gateway_Stripe::set_stripe_app_info();

		$metadata_object = array();

		foreach ( $metadata as $meta ) {
			$label = esc_html( $meta['label'] );
			$value = esc_html( $meta['value'] );
			// Payment doesn't work with empty meta labels.
			if ( '' === $label && '' === $value ) {
				continue;
			}
			if ( '' === $label ) {
				$label = $value;
			}

			$metadata_object[ $label ] = $value;
		}

		$form_id                               = ! empty( Forminator_Front_Action::$module_object->id ) ? (string) Forminator_Front_Action::$module_object->id : '';
		$metadata_object['forminator_form_id'] = $form_id;

		// Default options.
		$options = array(
			'amount'   => (int) $this->calculate_amount( $amount, $currency ),
			'currency' => $currency,
			'confirm'  => false,
		);

		$dynamic_methods = self::get_property( 'automatic_payment_methods', $field, 'true' );
		if ( 'false' === $dynamic_methods ) {
			$options['payment_method_types'] = array( 'card' );
		} else {
			$options['automatic_payment_methods'] = array(
				'enabled' => true,
			);
			$options['payment_method_options']    = array(
				'wechat_pay' => array(
					'client' => 'web', // Specify the client type.
				),
			);
		}

		if ( ! empty( Forminator_CForm_Front_Action::$prepared_data['paymentmethod'] ) ) {
			$options['payment_method'] = Forminator_CForm_Front_Action::$prepared_data['paymentmethod'];
		}

		// Check if metadata is not empty and add it to the options.
		if ( ! empty( $metadata_object ) ) {
			$options['metadata'] = $metadata_object;
		}

		// Check if statement_description is not empty and add it to the options.
		if ( ! empty( $company ) ) {
			$options['statement_descriptor_suffix'] = $company;
		}

		// Check if description is not empty and add it to the options.
		if ( ! empty( $description ) ) {
			$options['description'] = $description;
		}

		$options = apply_filters( 'forminator_stripe_payment_intent_options', $options, $field );

		try {
			// Create Payment Intent object.
			$intent = \Forminator\Stripe\PaymentIntent::create( $options );
		} catch ( Exception $e ) {
			$response = array(
				'message'     => $e->getMessage(),
				'errors'      => array(),
				'paymentPlan' => $this->payment_plan_hash,
			);

			wp_send_json_error( $response );
		}

		return $intent;
	}

	/**
	 * Calculate Stripe amount
	 *
	 * @since 1.11
	 *
	 * @param int|float $amount Amount.
	 * @param string    $currency Currency.
	 *
	 * @return float|int
	 */
	public function calculate_amount( $amount, $currency ) {
		$zero_decimal_currencies = $this->get_zero_decimal_currencies();

		// Check if currency is zero decimal, then return original amount.
		if ( in_array( $currency, $zero_decimal_currencies, true ) ) {
			return $amount;
		}

		// If JOD, amount needs to have 3 decimals and multiplied to 1000.
		if ( 'JOD' === $currency ) {
			$amount = number_format( $amount, 3, '.', '' );
			return $amount * 1000;
		}

		$amount = number_format( $amount, 2, '.', '' );

		// Currency has decimals, multiply by 100.
		return $amount * 100;
	}

	/**
	 * Return currencies without decimal
	 *
	 * @since 1.11
	 *
	 * @return array
	 */
	public function get_zero_decimal_currencies() {
		return array(
			'MGA',
			'BIF',
			'CLP',
			'PYG',
			'DJF',
			'RWF',
			'GNF',
			'UGX',
			'VND',
			'JPY',
			'VUV',
			'XAF',
			'KMF',
			'XOF',
			'KRW',
			'XPF',
		);
	}

	/**
	 * Update amount
	 *
	 * @since 1.7.3
	 *
	 * @param array $submitted_data Submitted data.
	 * @param array $field Field.
	 * @throws Exception When there is an error.
	 */
	public function update_paymentIntent( $submitted_data, $field ) {
		$mode     = self::get_property( 'mode', $field, 'test' );
		$currency = self::get_property( 'currency', $field, $this->get_default_currency() );
		$is_multi = self::get_property( 'automatic_payment_methods', $field, 'true' );

		if ( ! empty( $this->payment_plan['payment_method'] ) && 'subscription' === $this->payment_plan['payment_method'] ) {
			$response_data = array(
				'paymentid'     => 'subscription',
				'paymentsecret' => 'subscription',
				'paymentPlan'   => $this->payment_plan_hash,
			);

			if ( 'false' === $is_multi && class_exists( 'Forminator_Stripe_Subscription' ) ) {
				try {
					$stripe_addon   = Forminator_Stripe_Subscription::get_instance();
					$field_object   = Forminator_Core::get_field_object( 'stripe' );
					$payment_plan   = $field_object->get_payment_plan( $field );
					$payment_intent = $stripe_addon->create_payment_intent( $field_object, Forminator_Front_Action::$module_object, Forminator_Front_Action::$prepared_data, $field, $payment_plan );

					$response_data['paymentid']     = $payment_intent->id;
					$response_data['paymentsecret'] = $payment_intent->client_secret;
				} catch ( Exception $e ) {
					$response_data['paymentmethod_failed'] = '1';
				}
			}
			wp_send_json_success( $response_data );
		}

		// apply merge tags to payment description.
		$product_description = isset( $field['product_description'] ) ? $field['product_description'] : '';
		if ( ! empty( $product_description ) ) {
			$product_description          = forminator_replace_form_data( $product_description, Forminator_Front_Action::$module_object );
			$field['product_description'] = $product_description;
		}

		// Get Stripe key.
		$key = $this->get_secret_key( 'test' !== $mode );

		// Set Stripe key.
		\Forminator\Stripe\Stripe::setApiKey( $key );

		Forminator_Gateway_Stripe::set_stripe_app_info();

		$field_id = Forminator_Field::get_property( 'element_id', $field );
		$amount   = $submitted_data[ $field_id ] ?? 0;
		$id       = $submitted_data['paymentid'];
		if ( ! $amount && ! empty( $submitted_data['stripe_first_payment_intent'] ) ) {
			// If amount is empty, set it to 1 for payment intent. Anyway, it will be updated during actual payment.
			$amount = 1;
			// Filter amount. It can be used to modify amount before creating payment intent for low-value currency
			// to achieve minimum Stripe charge amount .5 euro. Use $field['currency'] to get currency code.
			$amount = apply_filters( 'forminator_stripe_default_payment_intent_amount', $amount, $field );
		}
		$payment_method     = filter_var( $field['automatic_payment_methods'], FILTER_VALIDATE_BOOLEAN ) ? 'dynamic' : 'card';
		$payment_intent_key = $mode . '_' . $currency . '_' . $amount . '_' . substr( $key, -5 ) . '_' . $payment_method;
		$is_intent          = ! empty( $submitted_data['stripe-intent'] );
		// Check if we already have payment ID, if not generate new one.
		if ( empty( $id ) ) {
			$generate_new = ! $is_intent;
			$id           = $this->get_payment_intent_id( $amount, $field, $payment_intent_key, $generate_new );
		}

		try {
			// Retrieve PI object.
			$intent = \Forminator\Stripe\PaymentIntent::retrieve( $id );
			if ( 'succeeded' === $intent->status ) {
				// throw error if payment intent already succeeded.
				throw new Exception( esc_html__( 'Payment already succeeded.', 'forminator' ) );
			}
		} catch ( Exception $e ) {
			$id = $this->get_payment_intent_id( $amount, $field, $payment_intent_key, true );

			$intent = \Forminator\Stripe\PaymentIntent::retrieve( $id );
		}

		// Convert object to array.
		$metadata_key    = $intent->metadata->keys();
		$metadata_value  = $intent->metadata->values();
		$stored_metadata = array_combine( $metadata_key, $metadata_value );

		// New metadata array.
		$metadata = array();

		if ( ! empty( $stored_metadata ) ) {
			foreach ( (array) $stored_metadata as $key => $meta ) {
				if ( 'forminator_form_id' === $key ) {
					$metadata[ $key ] = (string) $meta;
					continue;
				}
				$metadata[ $key ] = forminator_replace_form_data( '{' . $meta . '}', Forminator_Front_Action::$module_object );
			}
		}

		// Throw error if payment ID is empty.
		if ( empty( $id ) ) {
			$response = array(
				'paymentPlan' => $this->payment_plan_hash,
				'message'     => esc_html__( 'Your Payment ID is empty, please reload the page and try again!', 'forminator' ),
				'errors'      => array(),
			);

			wp_send_json_error( $response );
		}

		if ( $is_intent ) {
			wp_send_json_success(
				array(
					'paymentid'     => $id,
					'paymentsecret' => $intent->client_secret,
					'paymentPlan'   => $this->payment_plan_hash,
				)
			);
		} elseif ( 'succeeded' === $intent->status ) {
			// Check if the PaymentIntent already succeeded and continue.
			wp_send_json_success(
				array(
					'paymentid'     => $id,
					'paymentsecret' => $intent->client_secret,
				)
			);
		} else {
			try {
				// Check payment method.
				if ( ! empty( $submitted_data['payment_method_type'] ) && in_array( $submitted_data['payment_method_type'], self::get_unsupported_payment_methods(), true ) ) {
					throw new Exception( esc_html__( 'The selected Payment Method is not supported.', 'forminator' ) );
				}
				// Check payment amount.
				if ( 0 > $amount ) {
					throw new Exception( esc_html__( 'Payment amount should be larger than 0.', 'forminator' ) );
				}

				// Check payment ID.
				if ( empty( $id ) ) {
					throw new Exception( esc_html__( 'Your Payment ID is empty!', 'forminator' ) );
				}

				// Check payment method.
				if ( empty( $submitted_data['payment_method'] ) ) {
					throw new Exception( esc_html__( 'Your Payment Method is empty!', 'forminator' ) );
				}

				$options = array(
					'amount' => $this->calculate_amount( $amount, $currency ),
				);

				if ( 'blik' !== $submitted_data['payment_method_type'] ) {
					$options['payment_method'] = $submitted_data['payment_method'];
				}

				// Update receipt email if set on front-end.
				if ( isset( $submitted_data['receipt_email'] ) && ! empty( $submitted_data['receipt_email'] ) ) {
					$options['receipt_email'] = $submitted_data['receipt_email'];
				}

				if ( ! empty( $metadata ) ) {
					$options['metadata'] = $metadata;
				}

				// Update Payment Intent amount.
				\Forminator\Stripe\PaymentIntent::update(
					$id,
					$options
				);

				// Return success.
				wp_send_json_success(
					array(
						'paymentid'     => $id,
						'paymentsecret' => $intent->client_secret,
						'paymentPlan'   => $this->payment_plan_hash,
					)
				);

			} catch ( Exception $e ) {
				$response = array(
					'message'     => $e->getMessage(),
					'errors'      => array(),
					'paymentPlan' => $this->payment_plan_hash,
				);

				wp_send_json_error( $response );
			}
		}
	}

	/**
	 * Get payment intent ID
	 *
	 * @param int|float $amount Amount.
	 * @param array     $field Field.
	 * @param string    $payment_intent_key Payment intent key.
	 * @param bool      $force Use saved payment intents or not.
	 *
	 * @return string
	 */
	private function get_payment_intent_id( $amount, $field, $payment_intent_key, $force = false ): string {
		$saved_payment_intents = get_option( 'forminator_stripe_payment_intents', array() );

		/**
		 * Filter to force payment intent generation
		 *
		 * @param bool  $force Force payment intent generation.
		 * @param array $field Field.
		 */
		$force = apply_filters( 'forminator_stripe_force_payment_intent', $force, $field );
		if ( ! $force && ! empty( $saved_payment_intents[ $payment_intent_key ] ) ) {
			$id = $saved_payment_intents[ $payment_intent_key ];
		} else {
			$payment_intent = $this->generate_paymentIntent( $amount, $field );

			$id = $payment_intent->id;

			$saved_payment_intents[ $payment_intent_key ] = $id;
			update_option( 'forminator_stripe_payment_intents', $saved_payment_intents );
		}

		return $id;
	}

	/**
	 * Get unsupported payment methods
	 *
	 * @return array
	 */
	private static function get_unsupported_payment_methods() {
		return apply_filters(
			'forminator_stripe_unsupported_payment_methods',
			// All Stripe dynamic payment methods without immediate confirmation.
			array(
				'sepa_debit',
				'multibanco',
				'boleto',
				'ach_credit_transfer',
				'ach_debit',
				'sofort',
				'funded',
			)
		);
	}

	/**
	 * Get form setting
	 *
	 * @since 1.9
	 *
	 * @param int   $id Id.
	 * @param array $settings Settings.
	 * @param mixed $fallback Fallback method.
	 *
	 * @return mixed
	 */
	public function get_form_setting( $id, $settings, $fallback ) {
		// Check if user settings exist.
		if ( isset( $settings[ $id ] ) ) {
			return $settings[ $id ];
		}

		// Return fallback.
		return $fallback;
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

		return apply_filters( 'forminator_field_stripe_sanitize', $data, $field, $original_data );
	}

	/**
	 * Is available
	 *
	 * @since 1.7
	 * @inheritdoc
	 * @param array $field Field.
	 */
	public function is_available( $field ) {
		$mode = self::get_property( 'mode', $field, 'test' );
		try {
			$stripe = new Forminator_Gateway_Stripe();

			if ( 'test' !== $mode ) {
				$stripe->set_live( true );
			}

			if ( $stripe->is_ready() ) {
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
	 * @param bool $live Live?.
	 *
	 * @return bool|string
	 */
	private function get_publishable_key( $live = false ) {
		try {
			$stripe = new Forminator_Gateway_Stripe();

			if ( $live ) {
				return $stripe->get_live_key();
			}

			return $stripe->get_test_key();
		} catch ( Forminator_Gateway_Exception $e ) {
			return false;
		}
	}

	/**
	 * Get publishable key
	 *
	 * @since 1.7
	 *
	 * @param bool $live Live?.
	 *
	 * @return bool|string
	 */
	private function get_secret_key( $live = false ) {
		try {
			$stripe = new Forminator_Gateway_Stripe();

			if ( $live ) {
				return $stripe->get_live_secret( true );
			}

			return $stripe->get_test_secret( true );
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
			$stripe = new Forminator_Gateway_Stripe();

			return $stripe->get_default_currency();

		} catch ( Forminator_Gateway_Exception $e ) {
			return 'USD';
		}
	}

	/**
	 * Process to entry data
	 *
	 * @param array $field Field.
	 *
	 * @return array|WP_Error
	 * @throws Exception When there is an error.
	 */
	public function process_to_entry_data( $field ) {

		$entry_data = array(
			'mode'             => '',
			'product_name'     => '',
			'payment_type'     => '',
			'amount'           => '',
			'quantity'         => '',
			'currency'         => '',
			'transaction_id'   => '',
			'transaction_link' => '',
		);

		$mode     = self::get_property( 'mode', $field, 'test' );
		$currency = self::get_property( 'currency', $field, $this->get_default_currency() );

		try {
			// Get Payment intent.
			$intent = $this->get_paymentIntent( $field );

			if ( is_wp_error( $intent ) ) {
				throw new Exception( $intent->get_error_message() );
			} elseif ( ! is_object( $intent ) ) {
				// Make sure Payment Intent is object.
				throw new Exception( esc_html__( 'Payment Intent object is not valid Payment object.', 'forminator' ) );
			}

			// Check if the PaymentIntent is set or empty.
			if ( empty( $intent->id ) ) {
				throw new Exception( esc_html__( 'Payment Intent ID is not valid!', 'forminator' ) );
			}
			// Check if the PaymentIntent is valid.
			if ( ! self::is_valid_payment_intent( $intent->id ) ) {
				return new WP_Error(
					'forminator_stripe_payment_intent_already_handled',
					esc_html__( 'Payment Intent ID is not valid', 'forminator' )
				);
			}

			$charge_amount     = $this->get_payment_amount( $field );
			$expected_amount   = (int) $this->calculate_amount( $charge_amount, $currency );
			$intent_amount     = isset( $intent->amount ) ? (int) $intent->amount : 0;
			$intent_currency   = isset( $intent->currency ) ? strtoupper( (string) $intent->currency ) : '';
			$expected_currency = strtoupper( (string) $currency );

			$form_id_meta = isset( $intent->metadata->forminator_form_id ) ? (string) $intent->metadata->forminator_form_id : '';

			$expected_form_id = ! empty( Forminator_Front_Action::$module_object->id ) ? (string) Forminator_Front_Action::$module_object->id : '';

			if (
				( $form_id_meta !== $expected_form_id )
				|| ( $expected_amount !== $intent_amount )
				|| ( $expected_currency !== $intent_currency )
			) {
				return new WP_Error( 'forminator_stripe_error', esc_html__( 'Payment Intent ID is not valid', 'forminator' ) );
			}

			$entry_data['mode']     = $mode;
			$entry_data['currency'] = $currency;
			$entry_data['amount']   = $charge_amount;
			if ( ! empty( $this->payment_plan ) ) {
				$entry_data['product_name'] = $this->payment_plan['plan_name'];
				$entry_data['payment_type'] = $this->payment_method( $this->payment_plan['payment_method'] );
				$entry_data['quantity']     = $this->payment_plan['quantity'];
			}

			$entry_data['transaction_link'] = self::get_transanction_link( $mode, $intent->id );
			$entry_data['transaction_id']   = $intent->id;
		} catch ( Exception $e ) {
			$entry_data['error'] = $e->getMessage();
		}

		/**
		 * Filter stripe entry data that will be stored
		 *
		 * @since 1.7
		 *
		 * @param array                        $entry_data Entry data.
		 * @param array                        $field Field properties.
		 * @param Forminator_Form_Model $module_object Forminator_Form_Model.
		 * @param array                        $submitted_data Submitted data.
		 * @param array                        $field_data_array current entry meta.
		 *
		 * @return array
		 */
		$entry_data = apply_filters( 'forminator_field_stripe_process_to_entry_data', $entry_data, $field, Forminator_Front_Action::$module_object, Forminator_CForm_Front_Action::$prepared_data, Forminator_CForm_Front_Action::$info['field_data_array'] );

		return $entry_data;
	}

	/**
	 * Check if payment intent is valid
	 *
	 * @param string $intent_id Payment Intent ID.
	 *
	 * @return bool
	 */
	private static function is_valid_payment_intent( $intent_id ): bool {
		if ( empty( Forminator_CForm_Front_Action::$info['stripe_field']['type'] )
				|| 'stripe-ocs' !== Forminator_CForm_Front_Action::$info['stripe_field']['type'] ) {
			return true;
		}
		$payment_intents = get_option( 'forminator_stripe_payment_intents', array() );

		if ( is_array( $payment_intents ) && in_array( $intent_id, $payment_intents, true ) ) {
			// Remove payment intent after handling it.
			add_action(
				'forminator_custom_form_mail_before_send_mail',
				function () use ( $intent_id ) {
					$option_key      = 'forminator_stripe_payment_intents';
					$payment_intents = get_option( $option_key, array() );
					$payment_intents = array_diff( $payment_intents, array( $intent_id ) );
					update_option( $option_key, $payment_intents );
				}
			);

			return true;
		}
		return false;
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
		 * Filter link to Stripe transaction id
		 *
		 * @since 1.7
		 *
		 * @param string $transaction_link
		 * @param string $transaction_id
		 * @param array  $meta_value
		 *
		 * @return string
		 */
		$transaction_link = apply_filters( 'forminator_field_stripe_linkify_transaction_id', $transaction_link, $transaction_id, $meta_value );

		return $transaction_link;
	}

	/**
	 * Retrieve PaymentIntent object
	 *
	 * @param array $field Field.
	 *
	 * @return mixed object|string
	 * @throws Exception When there is an error.
	 */
	public function get_paymentIntent( $field ) {
		$mode     = self::get_property( 'mode', $field, 'test' );
		$currency = self::get_property( 'currency', $field, $this->get_default_currency() );

		// Check Stripe key.
		$key = $this->get_secret_key( 'test' !== $mode );

		// Set Stripe key.
		\Forminator\Stripe\Stripe::setApiKey( $key );

		Forminator_Gateway_Stripe::set_stripe_app_info();

		try {
			// Makue sure payment ID exist.
			if ( empty( Forminator_CForm_Front_Action::$prepared_data['paymentid'] ) ) {
				throw new Exception( esc_html__( 'Stripe Payment ID does not exist.', 'forminator' ) );
			}

			// Check payment amount.
			$intent = \Forminator\Stripe\PaymentIntent::retrieve( Forminator_CForm_Front_Action::$prepared_data['paymentid'] );

			return $intent;
		} catch ( Exception $e ) {
			return $this->get_error( $e );
		}
	}

	/**
	 * Retrieve PaymentMethod object
	 *
	 * @since 1.15
	 *
	 * @param array $field Field.
	 * @param array $submitted_data Submitted data.
	 *
	 * @return mixed object|string
	 * @throws Exception When there is an error.
	 */
	public function get_paymentMethod( $field, $submitted_data ) {
		$mode     = self::get_property( 'mode', $field, 'test' );
		$currency = self::get_property( 'currency', $field, $this->get_default_currency() );

		// Check Stripe key.
		$key = $this->get_secret_key( 'test' !== $mode );

		// Set Stripe key.
		\Forminator\Stripe\Stripe::setApiKey( $key );

		Forminator_Gateway_Stripe::set_stripe_app_info();

		try {
			// Makue sure payment ID exist.
			if ( ! isset( $submitted_data['paymentid'] ) ) {
				throw new Exception( esc_html__( 'Stripe Payment ID does not exist.', 'forminator' ) );
			}

			// Check payment amount.
			$intent = \Forminator\Stripe\PaymentMethod::retrieve( $submitted_data['paymentmethod'] );

			return $intent;
		} catch ( Exception $e ) {
			return $this->get_error( $e );
		}
	}

	/**
	 * Get Stripe return URL to pass it in API calls
	 *
	 * @return string
	 */
	public static function get_return_url() {
		return apply_filters( 'forminator_stripe_return_url', 'https://stripe.com' );
	}

	/**
	 * Confirm paymentIntent
	 *
	 * @param mixed $intent Payment Intent.
	 *
	 * @since 1.14.9
	 *
	 * @return object|WP_Error
	 */
	public function confirm_paymentIntent( $intent ) {
		try {
			return $intent->confirm( array( 'return_url' => self::get_return_url() ) );
		} catch ( Exception $e ) {
			return $this->get_error( $e );
		}
	}

	/**
	 * Get the exception error and return WP_Error
	 *
	 * @param mixed $e Exception.
	 *
	 * @since 1.14.9
	 *
	 * @return WP_Error
	 */
	private function get_error( $e ) {
		$code = $e->getCode();

		if ( is_int( $code ) ) {
			$code = ( 0 === $code ) ? 'zero' : $code;

			return new WP_Error( $code, $e->getMessage() );
		} else {
			return new WP_Error( $e->getError()->code, $e->getMessage() );
		}
	}

	/**
	 * Get ALL fields that payment amount depends on
	 *
	 * @param array $field_settings Field settings.
	 * @return array
	 */
	public function get_amount_dependent_fields_all( $field_settings ) {
		$depend_field = self::get_conditions_dependent_fields( $field_settings );

		$plans = self::get_property( 'payments', $field_settings, array() );
		foreach ( $plans as $plan ) {
			$plan_depends = self::get_plan_dependent_fields( $plan );
			$depend_field = array_merge( $depend_field, $plan_depends );
		}

		return array_values( array_unique( $depend_field ) );
	}

	/**
	 * Get the fields that an amount depends on
	 *
	 * @param array $field_settings Field settings.
	 * @return array
	 */
	public function get_amount_dependent_fields( $field_settings ) {
		$this->payment_plan = $this->get_payment_plan( $field_settings );
		$plan               = $this->payment_plan;

		$amount                  = $this->get_payment_amount( $field_settings );
		$this->payment_plan_hash = md5( wp_json_encode( $plan ) . $amount );

		$conditions_depends = self::get_conditions_dependent_fields( $field_settings );
		$plan_depends       = self::get_plan_dependent_fields( $plan );
		$depend_field       = array_merge( $conditions_depends, $plan_depends );

		return array_unique( $depend_field );
	}

	/**
	 * Get the fields that conditions based on
	 *
	 * @param array $field_settings Field settings.
	 *
	 * @return array
	 */
	private static function get_conditions_dependent_fields( $field_settings ) {
		$depend_field = array();

		$all_conditions = self::get_property( 'conditions', $field_settings, array() );

		$payments = self::get_property( 'payments', $field_settings, array() );

		foreach ( $payments as $payment ) {
			$conditions = $payment['conditions'] ?? array();
			if ( empty( $conditions ) || ! is_array( $conditions ) ) {
				continue;
			}
			$all_conditions = array_merge( $all_conditions, $conditions );
		}

		foreach ( $all_conditions as $condition ) {
			if ( ! empty( $condition['element_id'] ) ) {
				$depend_field[] = $condition['element_id'];
			}
		}

		return $depend_field;
	}

	/**
	 * Get the fields that a plan depends on
	 *
	 * @param array $plan Plan.
	 * @return array
	 */
	private static function get_plan_dependent_fields( $plan ) {
		$depend_field = array();
		if ( empty( $plan['payment_method'] ) ) {
			return $depend_field;
		}

		if ( 'single' === $plan['payment_method']
				&& ! empty( $plan['amount_type'] )
				&& 'variable' === $plan['amount_type']
				&& ! empty( $plan['variable'] ) ) {
			$depend_field[] = $plan['variable'];
		}

		if ( 'subscription' === $plan['payment_method']
				&& ! empty( $plan['subscription_amount_type'] )
				&& 'variable' === $plan['subscription_amount_type']
				&& ! empty( $plan['subscription_variable'] ) ) {
			$depend_field[] = $plan['subscription_variable'];
		}

		return $depend_field;
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
		$payment_amount  = 0.0;
		$amount_type     = self::get_property( 'amount_type', $field, 'fixed' );
		$amount          = self::get_property( 'amount', $field, '0' );
		$amount_variable = self::get_property( 'variable', $field, '' );
		$submitted_data  = Forminator_CForm_Front_Action::$prepared_data;

		if ( ! empty( $this->payment_plan ) ) {
			$amount_type     = isset( $this->payment_plan['amount_type'] ) ? $this->payment_plan['amount_type'] : $amount_type;
			$amount          = isset( $this->payment_plan['amount'] ) ? $this->payment_plan['amount'] : $amount;
			$amount_variable = isset( $this->payment_plan['variable'] ) ? $this->payment_plan['variable'] : $amount_variable;
		}

		if ( 'fixed' === $amount_type ) {
			$payment_amount = $amount;
		} else {
			$amount_var = $amount_variable;
			$form_field = Forminator_Front_Action::$module_object->get_field( $amount_var, false );
			if ( $form_field ) {
				$form_field = $form_field->to_formatted_array();
				if ( isset( $form_field['type'] ) ) {
					if ( 'calculation' === $form_field['type'] ) {

						// Calculation field get the amount from pseudo_submit_data.
						if ( isset( Forminator_CForm_Front_Action::$prepared_data[ $amount_var ] ) ) {
							$payment_amount = Forminator_CForm_Front_Action::$prepared_data[ $amount_var ];
						}
					} elseif ( 'currency' === $form_field['type'] ) {
						// Currency field get the amount from submitted_data.
						$field_id = $form_field['element_id'];
						if ( isset( $submitted_data[ $field_id ] ) ) {
							$payment_amount = self::forminator_replace_number( $form_field, $submitted_data[ $field_id ] );
						}
					} else {
						$field_object = Forminator_Core::get_field_object( $form_field['type'] );
						if ( $field_object ) {

							$field_id             = $form_field['element_id'];
							$submitted_field_data = isset( $submitted_data[ $field_id ] ) ? $submitted_data[ $field_id ] : null;
							$payment_amount       = $field_object::get_calculable_value( $submitted_field_data, $form_field );
						}
					}
				}
			}
		}

		if ( ! is_numeric( $payment_amount ) ) {
			$payment_amount = 0.0;
		}

		/**
		 * Filter payment amount of stripe
		 *
		 * @since 1.7
		 *
		 * @param double                       $payment_amount
		 * @param array                        $field field settings.
		 * @param Forminator_Form_Model $module_object
		 * @param array                        $prepared_data
		 */
		$payment_amount = apply_filters( 'forminator_field_stripe_payment_amount', $payment_amount, $field, Forminator_Front_Action::$module_object, Forminator_CForm_Front_Action::$prepared_data );

		return $payment_amount;
	}

	/**
	 * Get Payment plan
	 *
	 * @param array $field Field.
	 *
	 * @return array
	 */
	public function get_payment_plan( $field ) {
		$payments = self::get_property( 'payments', $field, array() );

		if ( ! empty( $payments ) ) {
			foreach ( $payments as $payment_settings ) {
				$payment_settings['condition_rule']   = ! empty( $payment_settings['condition_rule'] ) ? $payment_settings['condition_rule'] : 'all';
				$payment_settings['condition_action'] = 'show';
				if ( ! Forminator_Field::is_hidden( $field, $payment_settings ) ) {
					return $payment_settings;
				}
			}
		}

		return array();
	}

	/**
	 * Get transaction link
	 *
	 * @param string $mode Payment mode.
	 * @param string $transaction_id Transaction id.
	 * @return string
	 */
	public static function get_transanction_link( $mode, $transaction_id ) {
		if ( 'test' === $mode ) {
			$link_base = 'https://dashboard.stripe.com/test/payments/';
		} else {
			$link_base = 'https://dashboard.stripe.com/payments/';
		}
		$transaction_link = $link_base . rawurlencode( $transaction_id );

		return $transaction_link;
	}

	/**
	 * Payment method
	 *
	 * @param string $method Payment method.
	 *
	 * @return string|void
	 */
	public function payment_method( $method ) {
		switch ( $method ) {
			case 'single':
				$method = esc_html__( 'One Time', 'forminator' );
				break;
			case 'subscription':
				$method = esc_html__( 'Subscription', 'forminator' );
				break;
			default:
				$method = '';
		}

		return $method;
	}
}
