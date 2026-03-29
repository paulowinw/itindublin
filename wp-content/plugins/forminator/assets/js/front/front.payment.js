// the semi-colon before function invocation is a safety net against concatenated
// scripts and/or other plugins which may not be closed properly.
;// noinspection JSUnusedLocalSymbols
(function ($, window, document, undefined) {

	"use strict";

	// Polyfill
	if (!Object.assign) {
		Object.defineProperty(Object, 'assign', {
			enumerable: false,
			configurable: true,
			writable: true,
			value: function(target, firstSource) {
				'use strict';
				if (target === undefined || target === null) {
					throw new TypeError('Cannot convert first argument to object');
				}

				var to = Object(target);
				for (var i = 1; i < arguments.length; i++) {
					var nextSource = arguments[i];
					if (nextSource === undefined || nextSource === null) {
						continue;
					}

					var keysArray = Object.keys(Object(nextSource));
					for (var nextIndex = 0, len = keysArray.length; nextIndex < len; nextIndex++) {
						var nextKey = keysArray[nextIndex];
						var desc = Object.getOwnPropertyDescriptor(nextSource, nextKey);
						if (desc !== undefined && desc.enumerable) {
							to[nextKey] = nextSource[nextKey];
						}
					}
				}
				return to;
			}
		});
	}

	// undefined is used here as the undefined global variable in ECMAScript 3 is
	// mutable (ie. it can be changed by someone else). undefined isn't really being
	// passed in so we can ensure the value of it is truly undefined. In ES5, undefined
	// can no longer be modified.

	// window and document are passed through as local variables rather than global
	// as this (slightly) quickens the resolution process and can be more efficiently
	// minified (especially when both are regularly referenced in your plugin).

	// Create the defaults once
	var pluginName = "forminatorFrontPayment",
	    defaults   = {
		    type: 'stripe',
		    paymentEl: null,
		    paymentRequireSsl: false,
		    generalMessages: {},
	    };

	// The actual plugin constructor
	function ForminatorFrontPayment(element, options) {
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
		this._cardElement          = null;
		this._stripeToken		   = null;
		this._beforeSubmitCallback = null;
		this._form                 = null;
		this._paymentIntent        = null;
		this.init();
	}

	// Avoid Plugin.prototype conflicts
	$.extend(ForminatorFrontPayment.prototype, {
		init: function () {
			if (!this.settings.paymentEl || typeof this.settings.paymentEl.data() === 'undefined') {
				return;
			}

			var self         = this;
			this._stripeData = this.settings.paymentEl.data();

			if ( false === this.mountCardField() ) {
				return;
			}

			$(this.element).on('payment.before.submit.forminator', function (e, formData, callback) {
				self._form = self.getForm(e);
				self._beforeSubmitCallback = callback;
				self.validateStripe(e, formData);
			});

			this.$el.on("forminator:form:submit:stripe:3dsecurity", function(e, secret, subscription) {
				self.validate3d(e, secret, subscription);
			});

			// Listen for fields change to update ZIP mapping
			this.$el.find(
				'input.forminator-input, .forminator-input input, .forminator-field-textarea textarea, .forminator-checkbox input, .forminator-radio input, select.forminator-select2'
			).each(function () {
				$( this ).on( 'change', function ( e, param1 ) {
					if ( param1 !== 'forminator_emulate_trigger' ) {
						self.mapZip( e );
					}
				} );
			}).trigger( 'change' );
		},

		validate3d: function( e, secret, subscription ) {
			var self = this;

			if ( subscription ) {
				this._stripe.confirmCardPayment(secret, {
					payment_method: {
						card: self._cardElement,
						...self.getBillingData(),
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
					if ( result.paymentIntent.status === 'requires_action' ||  result.paymentIntent.status === 'requires_source_action' ) {
						self._stripe
							.confirmCardPayment( secret, {
								payment_method: {
									card: self._cardElement,
									...self.getBillingData(),
								},
							} )
							.then( function ( result ) {
								if ( self._beforeSubmitCallback ) {
									self._beforeSubmitCallback.call();
								}
							} );
					}
				});
			}
		},

		validateStripe: function(e, formData) {
			var self = this;

			this._stripe.createToken(this._cardElement).then(function (result) {
				if (result.error) {
					self.showCardError(result.error.message, true);
					self.$el.find( 'button' ).removeAttr( 'disabled' );
				} else {
					self.hideCardError();

					self._stripe.createPaymentMethod('card', self._cardElement, self.getBillingData()).then(function (result) {
						var paymentMethod = self.getObjectValue(result, 'paymentMethod');

						self._stripeData['paymentMethod'] = self.getObjectValue(paymentMethod, 'id');
						self.updateAmount(e, formData, result);
					});
				}
			});
		},

		isValid: function(focus) {
			var self = this;

			this._stripe.createToken(this._cardElement).then(function (result) {
				if (result.error) {
					self.showCardError(result.error.message, focus);
				} else {
					self.hideCardError();
				}
			});
		},

		getForm: function(e) {
			var $form = $( e.target );

			if(!$form.hasClass('forminator-custom-form')) {
				$form = $form.closest('form.forminator-custom-form');
			}

			return $form;
		},

		updateAmount: function(e, formData, result) {
			e.preventDefault();
			var self = this;
			var updateFormData = formData;
			var paymentMethod = this.getObjectValue(result, 'paymentMethod');

			//Method set() doesn't work in IE11
			updateFormData.append( 'action', 'forminator_update_payment_amount' );
			updateFormData.append( 'paymentid', this.getStripeData('paymentid') );
			updateFormData.append( 'payment_method', this.getObjectValue(paymentMethod, 'id') );

			var receipt = this.getStripeData('receipt');
			var receiptEmail = this.getStripeData('receiptEmail');
			var receiptObject = {};

			if( receipt && receiptEmail ) {
				var emailValue = this.get_field_value(receiptEmail) || '';

				updateFormData.append( 'receipt_email', emailValue );
			}

			$.ajax({
				type: 'POST',
				url: window.ForminatorFront.ajaxUrl,
				data: updateFormData,
				cache: false,
				contentType: false,
				processData: false,
				beforeSend: function () {
					if( typeof self.settings.has_loader !== "undefined" && self.settings.has_loader ) {
						// Disable form fields
						self._form.addClass('forminator-fields-disabled');

						var $target_message = self._form.find('.forminator-response-message');

						$target_message.html('<p>' + self.settings.loader_label + '</p>');

						self.focus_to_element($target_message);

						$target_message.removeAttr("aria-hidden")
							.prop("tabindex", "-1")
							.removeClass('forminator-success forminator-error')
							.addClass('forminator-loading forminator-show');
					}

					self._form.find('button').attr('disabled', true);
				},
				success: function (data) {
					if (data.success === true) {
						// Store payment id
						if (typeof data.data !== 'undefined' && typeof data.data.paymentid !== 'undefined') {
							self.$el.find('#forminator-stripe-paymentid').val(data.data.paymentid);
							self.$el.find('#forminator-stripe-paymentmethod').val(self._stripeData['paymentMethod']);
							self._stripeData['paymentid'] = data.data.paymentid;
							self._stripeData['secret'] = data.data.paymentsecret;

							self.handleCardPayment(data, e, formData);
						} else {
							self.show_error('Invalid Payment Intent ID');
						}
					} else {
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
					}
				},
				error: function (err) {
					var $message = err.status === 400 ? window.ForminatorFront.cform.upload_error : window.ForminatorFront.cform.error;

					self.show_error($message);
				}
			})
		},

		show_error: function(message) {
			var $target_message = this._form.find('.forminator-response-message');

			this._form.find('button').removeAttr('disabled');

			$target_message.removeAttr("aria-hidden")
				.prop("tabindex", "-1")
				.removeClass('forminator-loading')
				.addClass('forminator-error forminator-show');

			$target_message.html('<p>' + message + '</p>');

			this.focus_to_element($target_message);

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

		mapZip: function (e) {
			var verifyZip = this.getStripeData('veifyZip');
			var zipField = this.getStripeData('zipField');
			var changedField = $(e.currentTarget).attr('name');
			const fieldType = $( e.currentTarget ).attr( 'type' );
			// To handle the checkbox name[]
			if ( 'checkbox' === fieldType ) {
				changedField = changedField.replace( '[]', '' );
			}

			// Verify ZIP is enabled, mapped field is not empty and changed field is the mapped field, proceed
			if (verifyZip && zipField !== "" && changedField === zipField) {
				// Get field
				var value = this.get_field_value(zipField).toString();

				// Update card element
				this._cardElement.update({
					value: {
						postalCode: value
					}
				});
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

		getBillingData: function (formData) {
			var billing = this.getStripeData('billing');

			// If billing is disabled, return
			if (!billing) {
				return {}
			};

			// Get billing fields
			var billingName = this.getStripeData('billingName');
			var billingEmail = this.getStripeData('billingEmail');
			var billingAddress = this.getStripeData('billingAddress');

			// Create billing object
			var billingObject = {
				address: {}
			}

			if( billingName ) {
				var nameField = this.get_field_value(billingName);

				// Check if Name field is multiple
				if (!nameField) {
					var fName = this.get_field_value(billingName + '-first-name') || '';
					var lName = this.get_field_value(billingName + '-last-name') || '';

					nameField = fName + ' ' + lName;
				}

				// Check if Name field is empty in the end, if not assign to the object
				if (nameField) {
					billingObject.name = nameField;
				}
			}

			// Map email field
			if(billingEmail) {
				var billingEmailValue = this.get_field_value(billingEmail) || '';
				if (billingEmailValue) {
					billingObject.email = billingEmailValue;
				}
			}

			// Map address line 1 field
			var addressLine1 = this.get_field_value(billingAddress + '-street_address') || '';
			if (addressLine1) {
				billingObject.address.line1 = addressLine1;
			}

			// Map address line 2 field
			var addressLine2 = this.get_field_value(billingAddress + '-address_line') || '';
			if (addressLine2) {
				billingObject.address.line2 = addressLine2;
			}

			// Map address city field
			var addressCity = this.get_field_value(billingAddress + '-city') || '';
			if (addressCity) {
				billingObject.address.city = addressCity;
			}

			// Map address state field
			var addressState = this.get_field_value(billingAddress + '-state') || '';
			if (addressState) {
				billingObject.address.state = addressState;
			}

			// Map address country field
			var countryField = this.get_form_field(billingAddress + '-country');
			var addressCountry = countryField.find(':selected').data('country-code');

			if (addressCountry) {
				billingObject.address.country = addressCountry;
			}

			// Map address country field
			var addressZip = this.get_field_value(billingAddress + '-zip') || '';
				if (addressZip) {
				billingObject.address.postal_code = addressZip;
			}

			return {
				billing_details: billingObject
			}
		},

		handleCardPayment: function (data, e, formData) {
			var self = this,
				secret = data.data.paymentsecret || false,
				input = $( '.forminator-number--field, .forminator-currency, .forminator-calculation' );

			if ( input.inputmask ) {
				input.inputmask('remove');
			}

			if (self._beforeSubmitCallback) {
				self._beforeSubmitCallback.call();
			}
		},

		mountCardField: function () {
			var key = this.getStripeData('key');
			var cardIcon = this.getStripeData('cardIcon');
			var verifyZip = this.getStripeData('veifyZip');
			var zipField = this.getStripeData('zipField');
			var fieldId = this.getStripeData('fieldId');

			if ( null === key ) {
				return false;
			}

			// Init Stripe
			this._stripe = Stripe( key, {
				locale: this.getStripeData('language')
			} );

			// Create empty ZIP object
			var zipObject = {}

			if (!verifyZip) {
				// If verify ZIP is disabled, disable ZIP
				zipObject.hidePostalCode = true;
			} else {
				// Set empty post code, later will be updated when field is changed
				zipObject.value = {
					postalCode: '',
				};
			}

			var stripeObject = {};
			var fontFamily = this.getStripeData('fontFamily');
			var customFonts = this.getStripeData('customFonts');
			if (fontFamily && customFonts) {
				stripeObject.fonts = [
					{
						cssSrc: 'https://fonts.bunny.net/css?family=' + fontFamily,
					}
				];
			}

			var elements = this._stripe.elements(stripeObject);

			this._cardElement = elements.create('card', Object.assign(
				{
					classes: {
						base: this.getStripeData('baseClass'),
						complete: this.getStripeData('completeClass'),
						empty: this.getStripeData('emptyClass'),
						focus: this.getStripeData('focusedClass'),
						invalid: this.getStripeData('invalidClass'),
						webkitAutofill: this.getStripeData('autofilledClass'),
					},
					style: {
						base: {
							iconColor: this.getStripeData( 'iconColor' ),
							color: this.getStripeData( 'fontColor' ),
							lineHeight: this.getStripeData( 'lineHeight' ),
							fontWeight: this.getStripeData( 'fontWeight' ),
							fontFamily: this.getStripeData( 'fontFamily' ),
							fontSmoothing: 'antialiased',
							fontSize: this.getStripeData( 'fontSize' ),
							'::placeholder': {
								color: this.getStripeData( 'placeholder' ),
							},
							':hover': {
								iconColor: this.getStripeData( 'iconColorHover' ),
							},
							':focus': {
								iconColor: this.getStripeData( 'iconColorFocus' ),
							}
						},
						invalid: {
							iconColor: this.getStripeData( 'iconColorError' ),
							color: this.getStripeData( 'fontColorError' ),
						},
					},
					iconStyle: 'solid',
					hideIcon: !cardIcon,
				},
				zipObject
			));
			this._cardElement.mount('#card-element-' + fieldId);
			this.validateCard();
		},

		validateCard: function () {
			var self = this;
			this._cardElement.on( 'change', function( event ) {
				if ( self.$el.find( '.forminator-stripe-element' ).hasClass( 'StripeElement--empty' ) ) {
					self.$el.find( '.forminator-stripe-element' ).closest( '.forminator-field' ).removeClass( 'forminator-is_filled' );
				} else {
					self.$el.find( '.forminator-stripe-element' ).closest( '.forminator-field' ).addClass( 'forminator-is_filled' );
				}

				if ( self.$el.find( '.forminator-stripe-element' ).hasClass( 'StripeElement--invalid' ) ) {
					self.$el.find( '.forminator-stripe-element' ).closest( '.forminator-field' ).addClass( 'forminator-has_error' );
				}
			});

			this._cardElement.on('focus', function(event) {
				self.$el.find('.forminator-stripe-element').closest('.forminator-field').addClass('forminator-is_active');
			});

			this._cardElement.on('blur', function(event) {
				self.$el.find('.forminator-stripe-element').closest('.forminator-field').removeClass('forminator-is_active');

				self.isValid(false);
			});
		},

		hideCardError: function () {
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
			if ($element.length === 0) {
				//find element by its on name (for radio on singlevalue)
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
				value = Number($element.val());
			}

			return isNaN(value) ? 0 : value;
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
				$.data(this, pluginName, new ForminatorFrontPayment(this, options));
			}
		});
	};

})(jQuery, window, document);
