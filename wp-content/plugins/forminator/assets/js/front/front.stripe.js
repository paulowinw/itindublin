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
	var pluginName = "forminatorFrontStripe",
	    defaults   = {
		    type: 'stripe',
		    paymentEl: null,
		    paymentRequireSsl: false,
		    generalMessages: {},
	    };

	// The actual plugin constructor
	function ForminatorFrontStripe(element, options) {
		this.element = element;
		this.$el     = $(this.element);

		// jQuery has an extend method which merges the contents of two or
		// more objects, storing the result in the first object. The first object
		// is generally empty as we don't want to alter the default options for
		// future instances of the plugin
		this.settings              = $.extend({}, defaults, options);
		this._defaults             = defaults;
		this._name                 = pluginName;
		this._stripeData           = null;
		this._stripe			   = null;
		this._elements             = null;
		this._paymentElement       = null;
		this._beforeSubmitCallback = null;
		this._form                 = null;
		this.intent                = true;
		this.billingDetails        = {};
		this.init();
	}

	// Avoid Plugin.prototype conflicts
	$.extend(ForminatorFrontStripe.prototype, {
		init: function () {
			if (!this.settings.paymentEl || typeof this.settings.paymentEl.data() === 'undefined') {
				return;
			}

			var self         = this;
			this._stripeData = this.settings.paymentEl.data();

			// if ( false === this.mountStripeField() ) {
			// 	return;
			// }
			this._form = this.$el;

			if ( 0 < this.settings.stripe_depends.length ) {
				let selector = this.settings.stripe_depends.map(function(id) {
					return '[name="' + id + '"]';
				}).join(', ');

				// Lister fields' change to update Stripe plan
				this.$el.find(
					selector
				).each(function () {
					$( this ).on( 'change', function ( e, param1 ) {
						if ( param1 !== 'forminator_emulate_trigger' ) {
							self.intent = true;
							self.updateAmount(e);
						}
					} );
				});
			}

			// update amount for the first time.
			this.updateAmount();

			$(this.element).on('payment.before.submit.forminator', async (e, formData, callback) => {
				self.intent = false;
				self._beforeSubmitCallback = callback;
				const {error: submitError} = await this._elements.submit();
				if (submitError) {
					if ( 'undefined' !== typeof submitError.message ) {
						self.show_error(submitError.message);
					}
					return;
				}

				// If Blik payment method, create PaymentIntent and confirm on client side
				if ( self._stripeData['paymentMethodType'] === 'blik' ) {
					self.updateAmount();
				} else {

				self._stripe.createPaymentMethod( { elements: self._elements, params: { billing_details: this.billingDetails } } ).then(function (result) {
					if (result.error) {
						let resultError = result.error.message || window.ForminatorFront.cform.payment_failed;
						self.show_error(resultError);
						return;
					}
					var paymentMethod = self.getObjectValue(result, 'paymentMethod');

					self._stripeData['paymentMethod'] = self.getObjectValue(paymentMethod, 'id');
					self._stripeData['paymentMethodType'] = self.getObjectValue(paymentMethod, 'type');

					self.$el.find('#forminator-stripe-paymentmethod').val('');
					self.$el.find('#forminator-stripe-subscriptionid').val('');

					self.updateAmount();
				});
				}
			});

			this.$el.on("forminator:form:submit:stripe:3dsecurity", function(e, secret, subscription) {
				self.validate3d(e, secret, subscription);
			});

			this.$el.on("forminator:form:submit:stripe:redirect", this.paymentMethodRedirect.bind(this) );

			// Listen for fields change to update Billing Details
			this.$el.find(
				'input.forminator-input, select.forminator-select2'
			).each(function () {
				$( this ).on( 'change', function ( e, param1 ) {
					if ( param1 === 'forminator_emulate_trigger' ) {
						return true;
					}

					self.updateBillingDetails( e );
				} );
			});
		},

		paymentMethodRedirect: function( e, redirectUrl, clientSecret, subscription ) {

			var self = this;
			self.$el.find('#forminator-stripe-subscriptionid').val( subscription );
			const stripePopup = window.open(
				redirectUrl,
				'PaymentMethodPopup',
				'width=800,height=600,scrollbars=yes'
			);
			// This function polls Stripe to check the status of the payment
			const interval = setInterval(async () => {
				const {error, paymentIntent} = await self._stripe.retrievePaymentIntent(clientSecret);

				if (error) {
					clearInterval(interval);
					return;
				}

				if (paymentIntent.status === 'requires_capture' || paymentIntent.status === 'succeeded') {
					// Payment completed successfully!
					clearInterval(interval);
					stripePopup.close();
					if ( self._beforeSubmitCallback ) {
						self._beforeSubmitCallback.call();
					}
				} else if (paymentIntent.status === 'requires_payment_method' || paymentIntent.status === 'canceled') {
					let errorMessage = '';
					if ( paymentIntent.status === 'canceled' ) {
						errorMessage = window.ForminatorFront.cform.payment_cancelled;
					} else {
						errorMessage = window.ForminatorFront.cform.payment_failed;
					}
					clearInterval(interval);
					stripePopup.close();
					self.$el.find('#forminator-stripe-paymentmethod').val('');
					self.show_error(errorMessage);
				}
			}, 3000); // Poll every 3 seconds
		},

		validate3d: function( e, secret, subscription ) {
			var self = this;

			if ( subscription ) {
				this._stripe.confirmPayment({
					clientSecret: secret,
					elements: self._elements,
					redirect: 'if_required',
					confirmParams: {
						return_url: this.getStripeData('returnUrl'),
					},
				})
				.then(function(result) {
					self.$el.find('#forminator-stripe-subscriptionid').val( subscription );

					if (self._beforeSubmitCallback) {
						self._beforeSubmitCallback.call();
					}
				});
			} else {
				this._stripe.retrievePaymentIntent(
					secret
				).then(function(result) {
					if ( ['requires_action', 'requires_confirmation', 'requires_source_action'].includes( result.paymentIntent.status )
						||  self.getStripeData('paymentMethodType') === 'blik'
					) {
						self._stripe
							.confirmPayment({
								clientSecret: secret,
								elements: self._elements,
								redirect: 'if_required',
							} )
							.then( function ( result ) {
								if ( result.error ) {
									self.show_error(result.error.message);
								} else if ( self._beforeSubmitCallback ) {
									self._beforeSubmitCallback.call();
								}
							} );
					}
				});
			}
		},

		getForm: function(e) {
			var $form = $( e.target );

			if(!$form.hasClass('forminator-custom-form')) {
				$form = $form.closest('form.forminator-custom-form');
			}

			return $form;
		},

		updateAmount: function(e) {
			if ( e ) {
				e.preventDefault();
			}
			var formData = new FormData( this.$el[0] );
			var self = this;
			var updateFormData = new FormData();

			// Remove action from formData.
			formData.forEach(function (value, key) {
				if (key !== 'action') {
					updateFormData.append(key, value);
				}
			});

			//Method set() doesn't work in IE11
			updateFormData.append( 'action', 'forminator_update_payment_amount' );
			updateFormData.append( 'paymentPlan', this.getStripeData('paymentPlan') );
			updateFormData.append( 'payment_method', this.getStripeData('paymentMethod') );
			updateFormData.append( 'payment_method_type', this.getStripeData('paymentMethodType') );
			updateFormData.append( 'paymentid', '' );

			if ( this.intent ) {
				updateFormData.append( 'stripe-intent', true );
				updateFormData.append( 'stripe_first_payment_intent', ! this._paymentElement ? 1 : 0 );
			}
			var receipt = this.getStripeData('receipt');
			var receiptEmail = this.getStripeData('receiptEmail');

			if( receipt && receiptEmail ) {
				var emailValue = this.get_field_value(receiptEmail) || '';

				updateFormData.append( 'receipt_email', emailValue );
			}
			var $target_message = this._form.find('.forminator-response-message');

			$.ajax({
				type: 'POST',
				url: window.ForminatorFront.ajaxUrl,
				data: updateFormData,
				cache: false,
				contentType: false,
				processData: false,
				beforeSend: function () {
					if( typeof self.settings.has_loader !== "undefined" && self.settings.has_loader ) {
						if ( ! self.intent ) {
							$target_message.html('<p>' + self.settings.loader_label + '</p>');

							self.focus_to_element($target_message);

							$target_message.removeAttr("aria-hidden")
								.prop("tabindex", "-1")
								.removeClass('forminator-success forminator-error')
								.addClass('forminator-loading forminator-show')
							;
						}
					}

					self._form.find('button').attr('disabled', true);
				},
				success: function (data) {
					if (data.success === true) {
						// Store payment id
						if (typeof data.data !== 'undefined') {
							let hasPaymentId = 'undefined' !== typeof data.data.paymentid;
							let hasPaymentPlan = 'undefined' !== typeof data.data.paymentPlan;
							if (hasPaymentId) {
								self.$el.find('#forminator-stripe-paymentid').val(data.data.paymentid);
								self.$el.find('#forminator-stripe-paymentmethod').val(self._stripeData['paymentMethod']);
								self._stripeData['paymentid'] = data.data.paymentid;
								self._stripeData['secret'] = data.data.paymentsecret;
								if ( self.intent ) {
									self.mountStripeField(data.data.paymentsecret);
								}
							}
							if (data.data.paymentmethod_failed) {
								self.$el.find('#forminator-stripe-paymentmethod').val('');
							}

							if (hasPaymentPlan) {
								self._stripeData['paymentPlan'] = data.data.paymentPlan;
							}
							if (!self.intent){
								self.handlePayment();
							} else {
								self.unfrozeForm($target_message);
							}

						} else {
							self.show_error('Invalid Payment Intent ID');
						}
					} else if ( ! self.intent ) {
						// Not success for payment.
						self.show_error(data.data.message);

						if(data.data.errors.length) {
							self.show_messages(data.data.errors);
						}

						var $captcha_field = self._form.find('.forminator-g-recaptcha');

						if ($captcha_field.length) {
							$captcha_field = $($captcha_field.get(0));

							var recaptcha_widget = $captcha_field.data('forminator-recapchta-widget'),
								recaptcha_size = $captcha_field.data('size');

							if (recaptcha_size === 'invisible') {
								window.grecaptcha.reset(recaptcha_widget);
							}
						}
					} else {
						// Not success for intent.
						if ( typeof data.data.paymentPlan !== 'undefined' ) {
							self._stripeData['paymentPlan'] = data.data.paymentPlan;
						}
						self.unfrozeForm($target_message);
					}
				},
				error: function (err) {
					var $message = err.status === 400 ? window.ForminatorFront.cform.upload_error : window.ForminatorFront.cform.error;

					self.show_error($message);
				},
			}).always(
				function () {
					if ( !self.intent ) {
						self.$el.find('#forminator-stripe-paymentmethod').val('');
					}

					// Mount Stripe field if it still isn't mounted.
					if ( ! self._paymentElement ) {
						self.mountStripeField();
					}
			})
		},

		show_error: function(message) {
			var $target_message = this._form.find('.forminator-response-message');
			$target_message.html('<p>' + message + '</p>');
			this.unfrozeForm($target_message);
		},

		unfrozeForm: function($target_message) {
			this._form.find('button').removeAttr('disabled');

			if ( ! this.intent ) {
				$target_message.removeAttr("aria-hidden")
					.prop("tabindex", "-1")
					.removeClass('forminator-loading forminator-accessible')
					.addClass('forminator-error forminator-show');
				this.focus_to_element($target_message);

			}

			this.enable_form();
		},

		enable_form: function() {
			if( typeof this.settings.has_loader !== "undefined" && this.settings.has_loader ) {
				var $target_message = this._form.find('.forminator-response-message');

				// Enable form fields
				this._form.removeClass('forminator-fields-disabled');

				$target_message.removeClass('forminator-loading');
			}
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

		show_messages: function (errors) {
			var self = this,
				forminatorFrontCondition = self.$el.data('forminatorFrontCondition');
			if (typeof forminatorFrontCondition !== 'undefined') {
				// clear all validation message before show new one
				this.$el.find('.forminator-error-message').remove();
				var i = 0;
				errors.forEach(function (value) {
					var element_id = Object.keys(value),
						message = Object.values(value),
						element = forminatorFrontCondition.get_form_field(element_id);
					if (element.length) {
						if (i === 0) {
							// focus on first error
							self.$el.trigger('forminator.front.pagination.focus.input',[element]);
							self.focus_to_element(element);
						}

						if ($(element).hasClass('forminator-input-time')) {
							var $time_field_holder = $(element).closest('.forminator-field:not(.forminator-field--inner)'),
								$time_error_holder = $time_field_holder.children('.forminator-error-message');

							if ($time_error_holder.length === 0) {
								$time_field_holder.append('<span class="forminator-error-message" aria-hidden="true"></span>');
								$time_error_holder = $time_field_holder.children('.forminator-error-message');
							}
							$time_error_holder.html(message);
						}

						var $field_holder = $(element).closest('.forminator-field--inner');

						if ($field_holder.length === 0) {
							$field_holder = $(element).closest('.forminator-field');
							if ($field_holder.length === 0) {
								// handling postdata field
								$field_holder = $(element).find('.forminator-field');
								if ($field_holder.length > 1) {
									$field_holder = $field_holder.first();
								}
							}
						}

						var $error_holder = $field_holder.find('.forminator-error-message');

						if ($error_holder.length === 0) {
							$field_holder.append('<span class="forminator-error-message" aria-hidden="true"></span>');
							$error_holder = $field_holder.find('.forminator-error-message');
						}
						$(element).attr('aria-invalid', 'true');
						$error_holder.html(message);
						$field_holder.addClass('forminator-has_error');
						i++;
					}
				});
			}

			return this;
		},

		isRelevantField: function( e, billingName, billingEmail, billingPhone, billingAddress ) {
			if ( ! e ) {
				return true;
			}
			const fieldName = $(e.target).attr('name');
			if ( ! billingName ) {
				return false;
			}

			return billingEmail && fieldName === billingEmail
				|| billingPhone && fieldName === billingPhone
				|| billingName && ( fieldName === billingName || fieldName.startsWith( billingName + '-' ) )
				|| billingAddress && ( fieldName === billingAddress || fieldName.startsWith( billingAddress + '-' ) );
		},

		updateBillingDetails: function (e) {
			var billing = this.getStripeData('billing');

			// If billing is disabled, return
			if (!billing || !this._paymentElement) {
				return true;
			}

			// Get billing fields
			var billingName = this.getStripeData('billingName');
			var billingEmail = this.getStripeData('billingEmail');
			var billingPhone = this.getStripeData('billingPhone');
			var billingAddress = this.getStripeData('billingAddress');
			var billingDetails = {};

			if( ! this.isRelevantField( e, billingName, billingEmail, billingPhone, billingAddress ) ) {
				return true;
			}
			var nameField = this.get_field_value(billingName);

			// Check if Name field is multiple
			if (!nameField) {
				var fName = this.get_field_value(billingName + '-first-name') || '';
				var lName = this.get_field_value(billingName + '-last-name') || '';

				nameField = fName + ' ' + lName;
			}

			// Check if Name field is empty in the end, if not assign to the object
			if (' ' !== nameField) {
				billingDetails.name = nameField;
			}

			// Map email field
			var billingEmailValue = this.get_field_value(billingEmail) || '';
			if (billingEmailValue) {
				billingDetails.email = billingEmailValue;
			}

			// Map phone field
			var billingPhoneValue = this.get_field_value(billingPhone) || '';
			if (billingPhoneValue) {
				billingDetails.phone = billingPhoneValue;
			}

			let address = {};
			// Map address line 1 field
			var addressLine1 = this.get_field_value(billingAddress + '-street_address') || '';
			if (addressLine1) {
				address.line1 = addressLine1;
			}

			// Map address line 2 field
			var addressLine2 = this.get_field_value(billingAddress + '-address_line') || '';
			if (addressLine2) {
				address.line2 = addressLine2;
			}

			// Map address city field
			var addressCity = this.get_field_value(billingAddress + '-city') || '';
			if (addressCity) {
				address.city = addressCity;
			}

			// Map address state field
			var addressState = this.get_field_value(billingAddress + '-state') || '';
			if (addressState) {
				address.state = addressState;
			}

			// Map address country field
			var countryField = this.get_form_field(billingAddress + '-country');
			var addressCountry = countryField.find(':selected').data('country-code');

			if (addressCountry) {
				address.country = addressCountry;
			}

			// Map address country field
			var addressZip = this.get_field_value(billingAddress + '-zip') || '';
			if (addressZip) {
				address.postal_code = addressZip;
			}

			if ( Object.keys(address).length ) {
				billingDetails.address = address;
			}

			if ( Object.keys(billingDetails).length ) {
				this.billingDetails = billingDetails;
				this._paymentElement.update({
					defaultValues: {
						billingDetails,
					}
				});
			}
		},

		handlePayment: function () {
			var self = this,
				input = $( '.forminator-number--field, .forminator-currency, .forminator-calculation' );

			if ( input.inputmask ) {
				input.inputmask('remove');
			}

			if (self._beforeSubmitCallback) {
				self._beforeSubmitCallback.call();
			}
		},

		mountStripeField: function ( clientSecret = null ) {
			if ( 'subscription' === clientSecret ) {
				clientSecret = null;
			}
			if ( this._paymentElement ) {
				this._paymentElement.unmount();
			}
			let fieldId = this.getStripeData('fieldId'),
				key = this.getStripeData('key'),
				paymentOptions = { ...this.getStripeData('paymentOptions') }
			;

			if ( null === key ) {
				return false;
			}

			// Init Stripe
			this._stripe = Stripe( key );

			let stripeObject = { ...this.getStripeData('elementsOptions') };
			if ( clientSecret ) {
				// unset paymentMethodTypes because we can't set it without mode attribute.
				delete  stripeObject.paymentMethodTypes;
				stripeObject.clientSecret = clientSecret;
			} else {
				stripeObject.mode = 'setup';
				stripeObject.currency = this.getStripeData('currency') || 'usd';
			}

			this._elements = this._stripe.elements(stripeObject);

			this._paymentElement = this._elements.create('payment', paymentOptions );

			this._paymentElement.mount('#payment-element-' + fieldId);

			var self = this;
			this._paymentElement.on('ready', function(event) {
				self.updateBillingDetails();
			});
			this._paymentElement.on('change', function(event) {
				let isBlik = event.value.type === 'blik';
				self._stripeData['paymentMethodType'] = isBlik ? 'blik' : '';
			});
		},

		hideCardError: function () {
			// todo: it's for pagination
			var $field_holder = this.$el.find('.forminator-card-message');
			var $error_holder = $field_holder.find('.forminator-error-message');

			if ($error_holder.length === 0) {
				$field_holder.append('<span class="forminator-error-message" aria-hidden="true"></span>');
				$error_holder = $field_holder.find('.forminator-error-message');
			}

			$field_holder.closest('.forminator-field').removeClass('forminator-has_error');
			$error_holder.html('');
		},

		showCardError: function (message, focus) {
			// todo: it's for pagination
			var $field_holder = this.$el.find('.forminator-card-message');
			var $error_holder = $field_holder.find('.forminator-error-message');

			if ($error_holder.length === 0) {
				$field_holder.append('<span class="forminator-error-message" aria-hidden="true"></span>');
				$error_holder = $field_holder.find('.forminator-error-message');
			}

			$field_holder.closest('.forminator-field').addClass('forminator-has_error');
			$field_holder.closest('.forminator-field').addClass( 'forminator-is_filled' );
			$error_holder.html(message);

			if(focus) {
				this.focus_to_element($field_holder.closest('.forminator-field'));
			}
		},

		getStripeData: function (key) {
			if ( (typeof this._stripeData !== 'undefined') && (typeof this._stripeData[key] !== 'undefined') ) {
				return this._stripeData[key];
			}

			return null;
		},

		getObjectValue: function(object, key) {
			if (typeof object[key] !== 'undefined') {
				return object[key];
			}

			return null;
		},

		// taken from forminatorFrontCondition
		get_form_field: function (element_id) {
			//find element by suffix -field on id input (default behavior)
			var $element = this.$el.find('#' + element_id + '-field');
			if ($element.length === 0 && element_id) {
				//find element by its on name (for radio on single value)
				$element = this.$el.find('input[name=' + element_id + ']');
				if ($element.length === 0) {
					// for text area that have uniqid, so we check its name instead
					$element = this.$el.find('textarea[name=' + element_id + ']');
					if ($element.length === 0) {
						//find element by its on name[] (for checkbox on multivalue)
						$element = this.$el.find('input[name="' + element_id + '[]"]');
						if ($element.length === 0) {
							//find element by select name
							$element = this.$el.find('select[name="' + element_id + '"]');
							if ($element.length === 0) {
								$element = this.$el.find('select[name="' + element_id + '[]"]');
								if ($element.length === 0) {
									//find element by direct id (for name field mostly)
									//will work for all field with element_id-[somestring]
									$element = this.$el.find('#' + element_id);
								}
							}
						}
					}
				}
			}

			return $element;
		},

		get_field_value: function (element_id) {
			var $element = this.get_form_field(element_id);
			var value    = '';
			var checked  = null;

			if (this.field_is_radio($element)) {
				checked = $element.filter(":checked");
				if (checked.length) {
					value = checked.val();
				}
			} else if (this.field_is_checkbox($element)) {
				$element.each(function () {
					if ($(this).is(':checked')) {
						value = $(this).val();
					}
				});

			} else if (this.field_is_select($element)) {
				value = $element.val();
			} else if ( this.field_has_inputMask( $element ) ) {
				value = parseFloat( $element.inputmask( 'unmaskedvalue' ) );
			} else {
				value = $element.val()
			}

			return value;
		},

		field_has_inputMask: function ( $element ) {
			var hasMask = false;

			$element.each(function () {
				if ( undefined !== $( this ).attr( 'data-inputmask' ) ) {
					hasMask = true;
					//break
					return false;
				}
			});

			return hasMask;
		},

		field_is_radio: function ($element) {
			var is_radio = false;
			$element.each(function () {
				if ($(this).attr('type') === 'radio') {
					is_radio = true;
					//break
					return false;
				}
			});

			return is_radio;
		},

		field_is_checkbox: function ($element) {
			var is_checkbox = false;
			$element.each(function () {
				if ($(this).attr('type') === 'checkbox') {
					is_checkbox = true;
					//break
					return false;
				}
			});

			return is_checkbox;
		},

		field_is_select: function ($element) {
			return $element.is('select');
		},
	});

	// A really lightweight plugin wrapper around the constructor,
	// preventing against multiple instantiations
	$.fn[pluginName] = function (options) {
		return this.each(function () {
			if (!$.data(this, pluginName)) {
				$.data(this, pluginName, new ForminatorFrontStripe(this, options));
			}
		});
	};

})(jQuery, window, document);
