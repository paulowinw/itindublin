// the semi-colon before function invocation is a safety net against concatenated
// scripts and/or other plugins which may not be closed properly.
;// noinspection JSUnusedLocalSymbols
(function ($, window, document, undefined) {

	"use strict";

	// undefined is used here as the undefined global variable in ECMAScript 3 is
	// mutable (ie. it can be changed by someone else). undefined isn't really being
	// passed in so we can ensure the value of it is truly undefined. In ES5, undefined
	// can no longer be modified.

	// window and document are passed through as local variables rather than global
	// as this (slightly) quickens the resolution process and can be more efficiently
	// minified (especially when both are regularly referenced in your plugin).

	// Create the defaults once
	var pluginName = "forminatorFrontPayPal",
		defaults   = {
			type: 'paypal',
			paymentEl: null,
			paymentRequireSsl: false,
			generalMessages: {},
		};

	// The actual plugin constructor
	function ForminatorFrontPayPal(element, options) {
		this.element = element;
		this.$el     = $(this.element);
		this.forminator_selector = '#' + this.$el.attr('id') + '[data-forminator-render="' + this.$el.data('forminator-render') + '"]';

		// jQuery has an extend method which merges the contents of two or
		// more objects, storing the result in the first object. The first object
		// is generally empty as we don't want to alter the default options for
		// future instances of the plugin
		this.settings              = $.extend({}, defaults, options);
		this._defaults             = defaults;
		this._name                 = pluginName;
		this.paypalData            = null;
		this.paypalButton          = null;
		this.init();
	}

	// Avoid Plugin.prototype conflicts
	$.extend(ForminatorFrontPayPal.prototype, {
		init: function () {
			if (!this.settings.paymentEl) {
				return;
			}

			this.paypalData = this.settings.paymentEl;

			this.render_paypal_button( this.element );
			this.replaceScriptCurrency();
		},

		is_data_valid: function() {
			var paypalData = this.configurePayPal(),
				requireSsl = this.settings.paymentRequireSsl
			;

			if ( paypalData.amount <= 0 ) {
				return false;
			}

			if ( requireSsl && 'https:' !== location.protocol ) {
				return false;
			}

			return true;
		},

		is_form_valid: function() {
			var validate = this.$el.validate(); // Get validate instance
			var isValid = validate.checkForm(); // Valid?
			validate.submitted = {}; // Reset immediate form field checking mode

			return isValid;
		},

		render_paypal_button: function ( form ) {
			var $form = $( form ),
				self = this,
				paypalData = this.configurePayPal(),
				$target_message = $form.find('.forminator-response-message'),
				paypalActions,
				error_msg = ForminatorFront.cform.gateway.error,
				requireSsl = this.settings.paymentRequireSsl,
				generalMessage = this.settings.generalMessages,
				style_data = {
					shape: paypalData.shape,
					color: paypalData.color,
					label: paypalData.label,
					layout: paypalData.layout,
					height: parseInt( paypalData.height ),
				};

			if( paypalData.layout !== 'vertical' ) {
				style_data.tagline =  paypalData.tagline;
			}
			// Handle the case when PayPal is loaded in an iframe (e.g., in the theme editor).
			if ( window.parent !== window && window.parent.paypal ) {
				window.paypal = window.parent.paypal;
			}

			this.paypalButton = paypal.Buttons({
				onInit: function(data, actions) {
					actions.disable();

					if ( paypalData.amount_type === 'variable' && paypalData.variable !== '' ) {
						paypalData.amount = self.get_field_calculation( paypalData.variable );
					}

					// To handle field changes.
					const handleFieldChange = function () {
						if ( self.is_data_valid() && self.is_form_valid() ) {
							actions.enable();
							if ( $target_message.hasClass('forminator-error') ) {
								$target_message.html( '' ).attr( "aria-hidden", "true" );
								// In case of payment failed error
								$target_message.removeClass('forminator-show');
							}
						} else {
							actions.disable();
						}
					};

					// Listen for form field changes
					$form.find( 'input, select, textarea, .forminator-field-signature' ).on( 'change', handleFieldChange );
					// Listen for clone group event to bind change event to new fields.
					$form.on( 'forminator-clone-group', function (event) {
						$( event.target ).find( 'input, select, textarea, .forminator-field-signature' ).on( 'change', handleFieldChange );
					});

                    // Check if form has error to disable actions
                    $form.on( 'validation:error', function() {
                        actions.disable();
                    });

                    // Check if uploads has no error to enable actions
                    $form.on( 'forminator:uploads:valid', function() {
						if ( self.is_data_valid() && self.is_form_valid() ) {
                        	actions.enable();
						}
                    });

					// Check if the form is valid on init
					if ( self.is_data_valid() && self.is_form_valid() ) {
						actions.enable();
					}
				},

				env: paypalData.mode,
				style: style_data,
				onClick: function () {
					if( ! $form.valid() && paypalData.amount <= 0 ) {
						$target_message.removeClass('forminator-accessible').addClass('forminator-error').html('').removeAttr( 'aria-hidden' );
						$target_message.html('<label class="forminator-label--error"><span>' + generalMessage.payment_require_amount_error + '</span></label>');
						self.focus_to_element($target_message);
					} else if ( requireSsl && 'https:' !== location.protocol ) {
						$target_message.removeClass('forminator-accessible').addClass('forminator-error').html('').removeAttr( 'aria-hidden' );
						$target_message.html('<label class="forminator-label--error"><span>' + generalMessage.payment_require_ssl_error + '</span></label>');
						self.focus_to_element($target_message);
					} else if ( ! $form.valid() ) {
						$target_message.removeClass('forminator-accessible').addClass('forminator-error').html('').removeAttr( 'aria-hidden' );
						$target_message.html('<label class="forminator-label--error"><span>' + generalMessage.form_has_error + '</span></label>');
						self.focus_to_element($target_message);
                    } else {
						$form.trigger( 'forminator:preSubmit:paypal', [ $target_message ] );
						if ( $target_message.find( '.forminator-invalid-captcha' ).html() ) {
							self.focus_to_element($target_message);
							return false;
						}
					}

					if ( paypalData.amount_type === 'variable' && paypalData.variable !== '' ) {
						paypalData.amount = self.get_field_calculation( paypalData.variable );
					}
				},
				createOrder: function(data, actions) {
					$form.addClass('forminator-partial-disabled');

					var nonce = $form.find('input[name="forminator_nonce"]').val(),
						form_id = self.getPayPalData('form_id'),
						request_data = self.paypal_request_data()
					;
					return fetch( ForminatorFront.ajaxUrl + '?action=forminator_pp_create_order', {
						method: 'POST',
						mode: "same-origin",
						credentials: "same-origin",
						headers: {
							'content-type': 'application/json'
						},
						body: JSON.stringify({
							nonce: nonce,
							form_id: form_id,
							mode: self.getPayPalData('mode'),
							form_data: request_data,
							form_fields: $form.serialize()
						})
					}).then(function(res) {
						return res.json();
					}).then(function(response) {
						if ( response.success !== true ) {
							error_msg = response.data;

							return false;
						}

						var orderId = response.data.order_id;
						$form.find('.forminator-paypal-input').val( orderId );

						return orderId;
					});
				},
				onApprove: function(data, actions) {
					const $stripe_element = $form.find('.forminator-field-stripe-ocs:not(.forminator-hidden),.forminator-field-stripe:not(.forminator-hidden)');
					$stripe_element.addClass("forminator-hidden");
					if( typeof self.settings.has_loader !== "undefined" && self.settings.has_loader ) {
						// Disable form fields
						$form.addClass('forminator-fields-disabled');

						$target_message.html('<p>' + self.settings.loader_label + '</p>');

						$target_message.removeAttr("aria-hidden")
							.prop("tabindex", "-1")
							.removeClass('forminator-success forminator-error')
							.addClass('forminator-loading forminator-show');

						self.focus_to_element($target_message);
					}

					$form.trigger('submit.frontSubmit', 'forminator:submit:paypal' );
				},

				onCancel: function (data, actions) {
					if( typeof self.settings.has_loader !== "undefined" && self.settings.has_loader ) {
						// Enable form fields
						$form.removeClass('forminator-fields-disabled forminator-partial-disabled');

						$target_message.removeClass('forminator-loading');
					}

					return actions.redirect();
				},
				onError: function () {
					if( typeof self.settings.has_loader !== "undefined" && self.settings.has_loader ) {
						// Enable form fields
						$form.removeClass('forminator-fields-disabled forminator-partial-disabled');

						$target_message.removeClass('forminator-loading');
					}

					$target_message.removeClass('forminator-accessible').addClass('forminator-error').html('').removeAttr( 'aria-hidden' );
					$target_message.html('<label class="forminator-label--error"><span>' + error_msg + '</span></label>');
					self.focus_to_element($target_message);
				},
			});
			this.paypalButton.render( $form.find( '.forminator-button-paypal' )[0] );
		},

		configurePayPal: function () {
			var self   = this,
				paypalConfig = {
					form_id: this.getPayPalData('form_id'),
					sandbox_id: this.getPayPalData('sandbox_id'),
					currency: this.getPayPalData('currency'),
					live_id: this.getPayPalData('live_id'),
					amount: 0
				};

			paypalConfig.color = this.getPayPalData('color') ? this.getPayPalData('color') : 'gold';
			paypalConfig.shape = this.getPayPalData('shape') ? this.getPayPalData('shape') : 'rect';
			paypalConfig.label = this.getPayPalData('label') ? this.getPayPalData('label') : 'checkout';
			paypalConfig.layout = this.getPayPalData('layout') ? this.getPayPalData('layout') : 'vertical';
			paypalConfig.tagline = this.getPayPalData('tagline') ? this.getPayPalData('tagline') : 'true';
			paypalConfig.redirect_url = this.getPayPalData('redirect_url') ? this.getPayPalData('redirect_url') : '';
			paypalConfig.mode = this.getPayPalData('mode');
			paypalConfig.locale = this.getPayPalData('locale') ? this.getPayPalData('locale') : 'en_US';
			paypalConfig.debug_mode = this.getPayPalData('debug_mode') ? this.getPayPalData('debug_mode') : 'disable';
			paypalConfig.amount_type = this.getPayPalData('amount_type') ? this.getPayPalData('amount_type') : 'fixed';
			paypalConfig.variable = this.getPayPalData('variable') ? this.getPayPalData('variable') : '';
			paypalConfig.height = this.getPayPalData('height') ? this.getPayPalData('height') : 55;
			paypalConfig.shipping_address = this.getPayPalData('shipping_address') ? this.getPayPalData('shipping_address') : 'disable';
			var	amountType = this.getPayPalData('amount_type');
			if (amountType === 'fixed') {
				paypalConfig.amount = this.getPayPalData('amount');
			} else if( amountType === 'variable' && paypalConfig.variable !== '' ) {
				paypalConfig.amount =  this.get_field_calculation( paypalConfig.variable );
			}


			return paypalConfig;
		},

		getPayPalData: function (key) {
			if (typeof this.paypalData[key] !== 'undefined') {
				return this.paypalData[key];
			}

			return null;
		},

		// taken from forminatorFrontCondition
		get_form_field: function ( element_id ) {
			//find element by suffix -field on id input (default behavior)
			var $element = this.$el.find('#' + element_id + '-field');
			if ( $element.length === 0 ) {
				//find element by its on name (for radio on singlevalue)
				$element = this.$el.find('input[name=' + element_id + ']');
				if ( $element.length === 0 ) {
					// for text area that have uniqid, so we check its name instead
					$element = this.$el.find('textarea[name=' + element_id + ']');
					if ( $element.length === 0 ) {
						//find element by its on name[] (for checkbox on multivalue)
						$element = this.$el.find('input[name="' + element_id + '[]"]');
						if ( $element.length === 0 ) {
							//find element by select name
							$element = this.$el.find('select[name="' + element_id + '"]');
							if ($element.length === 0) {
								$element = this.$el.find('select[name="' + element_id + '[]"]');
								if ($element.length === 0) {
									//find element by direct id (for name field mostly)
									//will work for all field with element_id-[somestring]
									$element = this.$el.find('#' + element_id);
									if ( $element.length === 0 ) {
										$element = this.$el.find('select[name=' + element_id + ']');
									}
								}
							}
						}
					}
				}
			}

			return $element;
		},

		get_field_calculation: function (element_id) {
			var $element    = this.get_form_field(element_id);
			var value       = 0;
			var calculation = 0;
			var checked     = null;

			if (this.field_is_radio($element)) {
				checked = $element.filter(":checked");
				if (checked.length) {
					calculation = checked.data('calculation');
					if (calculation !== undefined) {
						value = Number(calculation);
					}
				}
			} else if (this.field_is_checkbox($element)) {
				$element.each(function () {
					if ($(this).is(':checked')) {
						calculation = $(this).data('calculation');
						if (calculation !== undefined) {
							value += Number(calculation);
						}
					}
				});

			} else if (this.field_is_select($element)) {
				checked = $element.find("option").filter(':selected');
				if (checked.length) {
					calculation = checked.data('calculation');
					if (calculation !== undefined) {
						value = Number(calculation);
					}
				}
			} else {
				if ( forminatorUtils().field_has_inputMask( $element ) ) {
					var unmaskVal =	$element.inputmask('unmaskedvalue');
					value = unmaskVal.replace(/,/g, '.');
				} else {
					value = Number( $element.val() );
				}
			}

			return isNaN(value) ? 0 : value;
		},

		focus_to_element: function ($element) {
			// force show in case its hidden of fadeOut
			$element.show();
			$('html,body').animate({scrollTop: ($element.offset().top - ($(window).height() - $element.outerHeight(true)) / 2)}, 500, function () {
				if (!$element.attr("tabindex")) {
					$element.attr("tabindex", -1);
				}
				$element.focus();
			});
		},

		paypal_request_data: function () {
			var paypalData = this.configurePayPal(),
				shipping_address = this.getPayPalData('shipping_address'),
				billing_details = this.getPayPalData('billing-details'),
				billingArr = this.getBillingData(),
				paypal_data = {};
			paypal_data.purchase_units = [{
				amount: {
					currency_code: this.getPayPalData('currency'),
					value: paypalData.amount
				}
			}];
			if ( 'enable' !== shipping_address ) {
				paypal_data.application_context = {
					shipping_preference: "NO_SHIPPING",
				};
			}
			if ( billing_details ) {
				paypal_data.payer = billingArr;
			}

			return paypal_data;
		},

		getBillingData: function () {
			// Get billing fields
			var billingName = this.getPayPalData( 'billing-name' ),
				billingEmail = this.getPayPalData( 'billing-email' ),
				billingAddress = this.getPayPalData( 'billing-address' );

			// Create billing object
			var billingObject = {}

			if ( billingName ) {
				billingObject.name = {};
				var nameField = this.get_field_value( billingName );

				// Check if Name field is multiple
				if ( ! nameField ) {
					var pfix  = this.get_field_value( billingName + '-prefix' ) || '',
						fName = this.get_field_value( billingName + '-first-name' ) || '',
						mname = this.get_field_value( billingName + '-middle-name' ) || '',
						lName = this.get_field_value( billingName + '-last-name' ) || '';

					nameField = pfix ? pfix + ' ' : '';
					nameField += fName;
					nameField += mname ? ' ' + mname : '';
				}

				// Check if Name field is empty in the end, if not assign to the object
				if ( nameField ) {
					billingObject.name.given_name = nameField;
					billingObject.name.surname = lName;
				}
			}

			// Map email field
			if( billingEmail ) {
				var billingEmailValue = this.get_field_value( billingEmail ) || '';
				if ( billingEmailValue ) {
					billingObject.email_address = billingEmailValue;
				}
			}
			if ( billingAddress ) {
				billingObject.address = {};
				//  Map address line 1 field
				var addressLine1 = this.get_field_value(billingAddress + '-street_address') || '';
				if ( addressLine1 ) {
					billingObject.address.address_line_1 = addressLine1;
				}

				//Map address line 2 field
				var addressLine2 = this.get_field_value(billingAddress + '-address_line') || '';
				if ( addressLine2 ) {
					billingObject.address.address_line_2 = addressLine2;
				}

				// Map address city field
				var addressCity = this.get_field_value(billingAddress + '-city') || '';
				if ( addressCity ) {
					billingObject.address.admin_area_2 = addressCity;
				}

				// Map address state field
				var addressState = this.get_field_value(billingAddress + '-state') || '';
				if ( addressState ) {
					billingObject.address.admin_area_1 = addressState;
				}

				// Map address country field
				var countryField = this.get_form_field(billingAddress + '-country') || '';
				if ( countryField ) {
					billingObject.address.country_code = countryField.find(':selected').data('country-code');
				}

				// Map address country field
				var addressZip = this.get_field_value(billingAddress + '-zip') || '';
				if ( addressZip ) {
					billingObject.address.postal_code = addressZip;
				}
			}

			return billingObject;
		},

		get_field_value: function ( element_id ) {
			var $element = this.get_form_field( element_id );
			var value    = '';
			var checked  = null;

			if ( this.field_is_radio( $element ) ) {
				checked = $element.filter(":checked");
				if ( checked.length ) {
					value = checked.val();
				}
			} else if ( this.field_is_checkbox( $element ) ) {
				$element.each(function () {
					if ( $( this ).is(':checked') ) {
						value = $( this ).val();
					}
				});

			} else if ( this.field_is_select( $element ) ) {
				value = $element.val();
			} else {
				value = $element.val()
			}

			return value;
		},

		field_is_radio: function ( $element ) {
			var is_radio = false;
			$element.each(function () {
				if ( $(this).attr('type') === 'radio' ) {
					is_radio = true;
					//break
					return false;
				}
			});

			return is_radio;
		},

		field_is_checkbox: function ( $element ) {
			var is_checkbox = false;
			$element.each(function () {
				if ( $( this ).attr('type') === 'checkbox' ) {
					is_checkbox = true;
					//break
					return false;
				}
			});

			return is_checkbox;
		},

		field_is_select: function ( $element ) {
			return $element.is('select');
		},

		/*
		 * Replaces the currency in the paypal script url params
		 * so it will match the currency of another form with paypal checkout
		 */
		replaceScriptCurrency: function () {
			var self = this,
				formCurrency = this.paypalData.currency;

			self.$el.on( 'click', function( e ) {
				var paypalScript = $( "script[src^='https://www.paypal.com/sdk/js']" ),
					paypalScriptSrc = paypalScript.attr( 'src' ),
					scriptCurrency = /currency=([^&]+)/.exec( paypalScriptSrc )[1];

				if ( formCurrency === scriptCurrency ) {
					return;
				}

				paypalScript.attr( 'src', paypalScriptSrc.replace( 'currency='+ scriptCurrency, 'currency='+ formCurrency ) );
				self.paypalButton.updateProps();
			});
		},

	});

	// A really lightweight plugin wrapper around the constructor,
	// preventing against multiple instantiations
	$.fn[pluginName] = function (options) {
		return this.each(function () {
			if (!$.data(this, pluginName)) {
				$.data(this, pluginName, new ForminatorFrontPayPal(this, options));
			}
		});
	};

})(jQuery, window, document);

