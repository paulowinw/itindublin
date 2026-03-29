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
	var pluginName = "forminatorFrontSubmit",
		defaults = {
			form_type: 'custom-form',
			forminatorFront: false,
			forminator_selector: '',
			chart_design: 'bar',
			chart_options: {}
		};

	// The actual plugin constructor
	function ForminatorFrontSubmit(element, options) {
		this.element = element;
		this.$el = $(this.element);
		this.forminatorFront = null;


		// jQuery has an extend method which merges the contents of two or
		// more objects, storing the result in the first object. The first object
		// is generally empty as we don't want to alter the default options for
		// future instances of the plugin
		this.settings = $.extend({}, defaults, options);
		this._defaults = defaults;
		this._name = pluginName;
		this.init();
	}

	// Avoid Plugin.prototype conflicts
	$.extend(ForminatorFrontSubmit.prototype, {
		init: function () {
			this.forminatorFront = this.$el.data('forminatorFront');
			switch (this.settings.form_type) {
				case 'custom-form':
					if (!this.settings.forminator_selector || !$(this.settings.forminator_selector).length) {
						this.settings.forminator_selector = '.forminator-custom-form';
					}
					this.handle_submit_custom_form();
					break;
				case 'quiz':
					if (!this.settings.forminator_selector || !$(this.settings.forminator_selector).length) {
						this.settings.forminator_selector = '.forminator-quiz';
					}
					this.handle_submit_quiz();
					break;
				case 'poll':
					if (!this.settings.forminator_selector || !$(this.settings.forminator_selector).length) {
						this.settings.forminator_selector = '.forminator-poll';
					}
					this.handle_submit_poll();
					break;

			}
		},

		decodeHtmlEntity: function(str) {
			return str.replace(/&#(\d+);/g, function(match, dec) {
				return String.fromCharCode(dec);
			});
		},

		addCountryCode: function( form ) {
			form.find('.forminator-field--phone').each(function() {
				var phone_element = $(this),
					national_mode = phone_element.data('national_mode') === 'enabled',
				    iti           = intlTelInput.getInstance(this);

				if ( !national_mode && iti ) {
					var dialCode = '+' + iti.getSelectedCountryData().dialCode;
					var currentInput = phone_element.val();
					if (currentInput !== '' && !currentInput.trim().startsWith('+')) {
						phone_element.closest('.iti').find('.iti__selected-dial-code').hide();
						phone_element.css('padding-inline-start', '45px');
						phone_element.val(dialCode + ' ' + currentInput);
					}
				}
			});
		},

		handle_submit_custom_form: function () {
			var self = this,
				saveDraftBtn = self.$el.find( '.forminator-save-draft-link' );

			var success_available = self.$el.find('.forminator-response-message').find('.forminator-label--success').not(':hidden');
			if (success_available.length) {
				self.focus_to_element(self.$el.find('.forminator-response-message'));
			}
			$('.def-ajaxloader').hide();
			var isSent = false;
			$('body').on('click', '#lostPhone', function (e) {
				e.preventDefault();
				var that = $(this);
				if (isSent === false) {
					isSent = true;
					$.ajax({
						type: 'GET',
						url: that.attr('href'),
						beforeSend: function () {
							that.attr('disabled', 'disabled');
							$('.def-ajaxloader').show();
						},
						success: function (data) {
							that.removeAttr('disabled');
							$('.def-ajaxloader').hide();
							$('.notification').text(data.data.message);
							isSent = false;
						}
					})
				}
			});

			$('body').on('click', '.auth-back', function (e) {
				e.preventDefault();
				var moduleId  = self.$el.attr( 'id' ),
					authId    = moduleId + '-authentication',
					authInput = $( '#' + authId + '-input' )
				;
				authInput.attr( 'disabled','disabled' );
				FUI.closeAuthentication();
			});

			if ( 0 !== saveDraftBtn.length ) {
				this.handle_submit_form_draft();
			}

			$( 'body' ).on(
				'click',
				'.forminator-authentication-box button[type="submit"]',
				function ( e ) {
					e.preventDefault();
					const thisForm = $( this ).closest( 'form' );
					thisForm.trigger(
						'submit.frontSubmit',
						'onSubmitAuthenticationBox'
					);
				}
			);

			$( 'body' ).off( 'forminator:preSubmit:paypal', this.settings.forminator_selector )
					.on( 'forminator:preSubmit:paypal', this.settings.forminator_selector, function( e, $target_message ) { return self.processCaptcha( self, e, $target_message, '' ); } );
			$( 'body' ).off( 'submit.frontSubmit', this.settings.forminator_selector );
			$( 'body' ).on( 'submit.frontSubmit', this.settings.forminator_selector, function ( e, submitter ) {

				if ( self.$el.find( '.forminator-button-submit' ).prop('disabled') ) {
					return false;
				}

				// Disable submit button right away.
				self.disable_form_submit( self, true );

				if ( 0 !== self.$el.find( 'input[type="hidden"][value="forminator_submit_preview_form_custom-forms"]' ).length ) {
					self.disable_form_submit( self, false );
					return false;
				}

				var $this = $(this),
					thisForm = this,
					submitEvent = e,
					formData = new FormData( this ),
					$target_message = $this.find('.forminator-response-message'),
					$saveDraft = 'true' === self.$el.find( 'input[name="save_draft"]' ).val() ? true : false,
					$datepicker = $('body').find( '#ui-datepicker-div.forminator-custom-form-' + self.$el.data( 'form-id' ) )
					;

				// add country code before submitting values to the form submissions.
				self.addCountryCode( $this );

				if( self.settings.inline_validation && self.$el.find('.forminator-uploaded-files').length > 0 && ! $saveDraft ) {
					var file_error = self.$el.find('.forminator-uploaded-files li.forminator-has_error');
					if( file_error.length > 0 ) {
						self.disable_form_submit( self, false );
						return false;
					}
				}

				//check originalEvent exists and submit button is not exits or hidden
				if( submitEvent.originalEvent !== undefined ) {
					var submitBtn = $(this).find('.forminator-button-submit').first();
					if( submitBtn.length === 0 || $( submitBtn ).closest('.forminator-col').hasClass('forminator-hidden') ) {
						self.disable_form_submit( self, false );
						return false;
					}
				}
				// Check if datepicker is open, prevent submit
				if ( 0 !== $datepicker.length && self.$el.datepicker( "widget" ).is(":visible") ) {
					self.disable_form_submit( self, false );
					return false;
				}

				if ( ( self.$el.data( 'forminatorFrontPayment' ) || self.$el.data('forminatorFrontStripe') ) && ! $saveDraft ) {
					// Disable submit button right away to prevent multiple submissions
					$this.find( '.forminator-button-submit' ).attr( 'disabled', true );
					if ( false === self.processCaptcha( self, e, $target_message, submitter ) ) {
						$this.find( '.forminator-button-submit' ).attr( 'disabled', false );
						self.disable_form_submit( self, false );
						return false;
					}
				}

				self.multi_upload_disable( $this, true );

				var submitCallback = function() {
					var pagination 	  = self.$el.find( '.forminator-pagination:visible' ),
						hasPagination = !! pagination.length,
						formStep	  = pagination.index( '.forminator-pagination' )
						;

					formData = new FormData(this); // reinit values

					// Set raw values for number, currency, and calculation fields instead of their masked values.
					self.$el.find('.forminator-number--field, .forminator-currency, .forminator-calculation').each(function () {
						if ( $( this ).inputmask ) {
							formData.set( $( this ).attr('name'), $( this ).val() );
						}
					});

					formData.append( 'form_uid', self.$el.data( 'uid' ) );
					if ( $saveDraft && hasPagination ) {
						formData.append( 'draft_page', formStep );
					}

					if ( ! self.$el.data( 'forminatorFrontPayment' ) && ! self.$el.data('forminatorFrontStripe' ) && ! $saveDraft ) {
						if ( false === self.processCaptcha( self, e, $target_message, submitter ) ) {
							self.disable_form_submit( self, false );
							return false;
						}
					}

					// Note: removeMaskOnSubmit is not working if form is not actually submitted,
					// so inputmask values are handled here specifically when submitting via PayPal.
					if ('forminator:submit:paypal' === submitter) {
						self.$el.find('input[data-inputmask]').each(function () {
							var inputName = $(this).attr('name');
							if (!inputName) {
								return;
							}
							formData.set(inputName, $(this).val());
						});
					}
					// Should check if submitted thru save draft button
					if ( self.$el.hasClass('forminator_ajax') || $saveDraft ) {
						$target_message.html('');
						self.$el.find('.forminator-button-submit').addClass('forminator-button-onload');

						// Safari FIX, if empty file input, ajax broken
						// Check if input empty
						self.$el.find("input[type=file]").each(function () {
							// IE does not support FormData.delete()
							if ($(this).val() === "") {
								if (typeof(window.FormData.prototype.delete) === 'function') {
									formData.delete($(this).attr('name'));
								}
							}
						});

						var form_type = '';
						if( typeof self.settings.has_loader !== "undefined" && self.settings.has_loader ) {
							// Disable form fields
							form_type = self.$el.find('input[name="form_type"]').val();
							if( 'login' !== form_type ) {
								self.$el.addClass('forminator-fields-disabled');
							}
							$target_message.html('<p>' + self.settings.loader_label + '</p>');
							self.focus_to_element( $target_message );

							$target_message.removeAttr("aria-hidden")
								.prop("tabindex", "-1")
								.removeClass('forminator-success forminator-error forminator-accessible')
								.addClass('forminator-loading forminator-show');
						}

						var keep_froze = false;
						e.preventDefault();
						$.ajax({
							type: 'POST',
							url: window.ForminatorFront.ajaxUrl,
							data: formData,
							cache: false,
							contentType: false,
							processData: false,
							beforeSend: function () {
								$this.find('button').attr('disabled', true);
								$this.trigger('before:forminator:form:submit', formData);
							},
							success: function( data ) {
								if( ( ! data && 'undefined' !== typeof data ) || 'object' !== typeof data.data ) {
									$this.find( 'button' ).removeAttr( 'disabled' );
									$target_message.addClass('forminator-error')
										.html( '<p>' + window.ForminatorFront.cform.error + '<br>(' + data.data + ')</p>');
									self.focus_to_element($target_message);

									if ( data.data ) {
										$this.trigger('forminator:form:submit:failed', [ formData, data.data ] );
									}

									return false;
								}

								// Process Save Draft's response
								if ( data.success && undefined !== data.data.type && 'save_draft' === data.data.type ) {
									self.showDraftLink( data.data );
									return false;
								}

								// Save lead_entry_id if available.
								if ( data.success && undefined !== data.data.lead_entry_id ) {
									const leadEntryId = data.data.lead_entry_id;
									let leadQuizId = '';

									// Get the quiz form id.
									const leadQuizInput = $this.find('input[name=lead_quiz]');
									if (leadQuizInput.length > 0) {
										leadQuizId = leadQuizInput.val();
									}
									// Save the lead entry id in quiz hidden field attribute to be used later.
									const entryIdInput = $('#forminator-module-'+leadQuizId).find('input[name=entry_id]');
									if (entryIdInput.length > 0) {
										entryIdInput.attr('lead-entry-id', leadEntryId);
									}
								}

								// Hide validation errors
								$this.find( '.forminator-error-message' ).not('.forminator-uploaded-files .forminator-error-message').remove();
								$this.find( '.forminator-field' ).removeClass( 'forminator-has_error' );

								// Remove invalid attribute for screen readers.
								$this.find( 'input, select, textarea' ).removeAttr( 'aria-invalid' );

								$this.find( 'button' ).removeAttr( 'disabled' );
								$target_message.html( '' ).removeClass( 'forminator-accessible forminator-error forminator-success' );
								if( self.settings.hasLeads && 'undefined' !== typeof data.data.entry_id ) {
									self.showQuiz( self.$el );
									$('#forminator-module-' + self.settings.quiz_id + ' input[name=entry_id]' ).val( data.data.entry_id );
									if( 'end' === self.settings.form_placement ) {
										$('#forminator-module-' + self.settings.quiz_id).submit();
									}

									return false;
								}
								if ( typeof data !== 'undefined' &&
									 typeof data.data !== 'undefined' &&
									 typeof data.data.authentication !== 'undefined' &&
									( 'show' === data.data.authentication || 'invalid' === data.data.authentication ) ) {
									var moduleId  = self.$el.attr( 'id' ),
										authId    = moduleId + '-authentication',
										authField = $( '#' + authId ),
										authInput = $( '#' + authId + '-input' ),
										authToken = $( '#' + authId + '-token' )
									;
									authField.find('.forminator-authentication-notice').removeClass('error');
									authField.find('.lost-device-url').attr('href', data.data.lost_url);

									if( 'show' === data.data.authentication ) {
										self.$el.find('.forminator-authentication-nav').html('').append( data.data.auth_nav );
										self.$el.find('.forminator-authentication-box').hide();
										if ( 'fallback-email' === data.data.auth_method ) {
											self.$el.find('.wpdef-2fa-email-resend input').click();
											self.$el.find('.notification').hide();
										}
										self.$el.find( '#forminator-2fa-' + data.data.auth_method ).show();
										self.$el.find('.forminator-authentication-box input').attr( 'disabled', true );
										self.$el.find( '#forminator-2fa-' + data.data.auth_method + ' input' ).attr( 'disabled', false );
										self.$el.find('.forminator-2fa-link').show();
										self.$el.find('#forminator-2fa-link-' + data.data.auth_method).hide();
										authInput.removeAttr( 'disabled' ).val(data.data.auth_method);
										authToken.val( data.data.auth_token );
										FUI.openAuthentication( authId, moduleId, authId + '-input' );
									}
									if ( 'invalid' === data.data.authentication ) {
										authField.find('.forminator-authentication-notice').addClass('error');
										authField.find('.forminator-authentication-notice').html('<p>' + data.data.message + '</p>');
										$this.trigger('forminator:form:submit:failed', [ formData, data.data.message ] );
									}

									return false;

								}
								var $label_class = data.success ? 'forminator-success' : 'forminator-error';

								if (typeof data.message !== "undefined") {
									$target_message.removeAttr("aria-hidden")
										.prop("tabindex", "-1")
										.addClass($label_class + ' forminator-show');
									self.focus_to_element( $target_message, false, data.fadeout, data.fadeout_time );
									$target_message.html( data.message );

									if(!data.data.success && data.data.errors.length) {
										var errors_html = '<ul class="forminator-screen-reader-only">';
										$.each(data.data.errors, function(index,value) {
											for(var propName in value) {
											    if(value.hasOwnProperty(propName)) {
											       errors_html += '<li>' + value[propName] + '</li>';
											    }
											}
										});
										errors_html += '</ul>';
										$target_message.append(errors_html);
									}
								} else {
									if (typeof data.data !== "undefined") {
										var isShowSuccessMessage = true;

										//Remove background of the success message if form behaviour is redirect and the success message is empty
										if (
											typeof data.data.url !== 'undefined' &&
											typeof data.data.newtab !== 'undefined' &&
											'newtab_thankyou' !== data.data.newtab
										) {
											isShowSuccessMessage = false;
										}
										if ( isShowSuccessMessage ) {
											$target_message.removeAttr("aria-hidden")
												.prop("tabindex", "-1")
												.addClass($label_class + ' forminator-show');
											self.focus_to_element( $target_message, false, data.data.fadeout, data.data.fadeout_time );
											$target_message.html( data.data.message );
										}

										if(!data.data.success && typeof data.data.errors !== 'undefined' && data.data.errors.length) {
											var errors_html = '<ul class="forminator-screen-reader-only">';
											$.each(data.data.errors, function(index,value) {
												//errors_html += '<li>' + value
												for(var propName in value) {
												    if(value.hasOwnProperty(propName)) {
												        errors_html += '<li>' + value[propName] + '</li>';
												    }
												}
											});
											errors_html += '</ul>';
											$target_message.append(errors_html);
										}

										if ( typeof data.data.stripe3d !== "undefined" ) {
											if ( typeof data.data.subscription !== "undefined" ) {
												$this.trigger('forminator:form:submit:stripe:3dsecurity', [ data.data.secret, data.data.subscription ]);
											} else {
												$this.trigger('forminator:form:submit:stripe:3dsecurity', [ data.data.secret, false ]);
											}
										} else if ( typeof data.data.redirect_to_url !== "undefined" ) {

											$this.trigger('forminator:form:submit:stripe:redirect', [ data.data.redirect_to_url, data.data.secret, data.data.subscription || '' ]);
										}
									}
								}
								if ( data.data.secret ) {
									keep_froze = true;
									return false;
								}

								if ( ! data.data.success ) {
									let errors = typeof data.data.errors !== 'undefined' && data.data.errors.length ? data.data.errors : '';
									$this.trigger('forminator:form:submit:failed', [ formData, errors ] );
									self.multi_upload_disable( $this, false );

									if ( errors ) {
										self.show_messages(errors);
									}
								}

								if (data.success === true) {
									var hideForm = typeof data.data.behav !== "undefined" && data.data.behav === 'behaviour-hide';
									var redirectSameTab = typeof data.data.url !== "undefined" && typeof data.data.newtab !== "undefined" && data.data.newtab === 'sametab';
									var resetEnabled = self.settings.resetEnabled;
									const isDraftSubmit = $this.find('input[name="previous_draft_id"]').length > 0;
									if (isDraftSubmit) {
										hideForm = true;
									}

									// Reset the form fields to accept a new submission
									// but skip resetting the form fields if the form behavior after submission
									// is set to redirect to specific url on the same tab or if set to be hidden
									if ($this[0] && resetEnabled && !hideForm && !redirectSameTab) {

										$this[0].reset();

										self.$el.trigger('forminator:field:condition:toggled');

										// reset signatures
										$this.find('.forminator-field-signature img').trigger('click');

										// Reset Select field submissions
										if (typeof data.data.select_field !== "undefined") {
											$.each(data.data.select_field, function (index, value) {
												if (value.length > 0) {
													$.each(value, function (i, v) {
														if (v['value']) {
															if (v['type'] === 'multiselect') {
																$this.find("#" + index + " input").filter((_, input) => input.value === v['value']).closest('.forminator-option').remove().trigger("change");
															}
															$this.find("#" + index + " option").filter((_, option) => option.value === v['value']).remove().trigger("change");
														}
													});
												}
											});
										}
										// Reset upload field
										$this.find(".forminator-button-delete").hide();
										$this.find('.forminator-file-upload input').val("");
										$this.find('.forminator-file-upload > span').html(window.ForminatorFront.cform.no_file_chosen);
										$this.find('ul.forminator-uploaded-files').html('');
										self.$el.find('ul.forminator-uploaded-files').html('');
										self.$el.find( '.forminator-multifile-hidden' ).val('');
										//self.$el.find( '.forminator-input-file' ).val('');

										// Reset selects
										if ( $this.find('.forminator-select2').length > 0 ) {
											$this.find('.forminator-select2').each(function (index, value) {
												// Reset Select2 checkboxes by removing the data-select2-id attribute.
												$(value).find('option').removeAttr('data-select2-id');
												var defaultValue = $(value).data('default-value');
												if ( '' === defaultValue ) {
													defaultValue = $(value).val();
												}
												$(value).val(defaultValue).trigger("change.select2");
											});
										}
										// Reset multiselect
										$this.find('.multiselect-default-values').each(function () {
											var defaultValuesObj = '' !== $(this).val() ?  $.parseJSON( $(this).val() ) : [],
												defaultValuesArr = Object.values( defaultValuesObj ),
												multiSelect = $(this).closest('.forminator-multiselect');
											multiSelect.find('input[type="checkbox"]').each(function (i, val) {
												if( -1 !== $.inArray( $(val).val(), defaultValuesArr ) ) {
													$(val).prop('checked', true);
													$(val).closest('label').addClass('forminator-is_checked');
												} else {
													$(val).prop('checked', false);
													$(val).closest('label').removeClass('forminator-is_checked');
												}
											});
										});

										// Reset Post data multiselect fields.
										$this.find( '.forminator-field-postdata' ).each(function () {
											let multiSelect = $(this).find( '.forminator-multiselect' );
											multiSelect.find('input[type="checkbox"]').each(function (i, val) {
												$(val).prop('checked', false);
												$(val).closest('label').removeClass('forminator-is_checked');
											});
										});

										// Reset slider.
										$this.find('.forminator-slider').each(function () {
											var $element = $(this),
												$slide = $element.find('.forminator-slide'),
												$slider = $slide.slider("option"),
												$minRange = parseFloat($slide.data('min')) || 0,
												$maxRange = parseFloat($slide.data('max')) || 100,
												$value = parseFloat($slide.data('value')) || $minRange,
												$valueMax = parseFloat($slide.data('value-max')) || $maxRange;

											// Remove slider custom labels.
											$element.find('.forminator-slider-labels').remove();

											$slider.create();
											if (true === $slider.range) {
												$slide.slider('values', [$value, $valueMax]);
											} else {
												$slide.slider('value', $value);
											}
										});

										self.multi_upload_disable( $this, false );

										// restart condition after form reset to ensure values of input already reset-ed too
										$this.trigger('forminator.front.condition.restart');
									}
									$this.trigger('forminator:form:submit:success', [ formData, data ]);

									if (typeof data.data.url !== "undefined") {

										//check if newtab option is selected
										if(typeof data.data.newtab !== "undefined" && data.data.newtab !== "sametab"){
											if ( 'newtab_hide' === data.data.newtab ) {
												//hide if newtab redirect with hide form option selected
												self.$el.hide();
											}
											//new tab redirection
											window.open( self.decodeHtmlEntity( data.data.url ), '_blank' );
										} else {
											//same tab redirection
											window.location.href = self.decodeHtmlEntity( data.data.url );
										}

									}

									if (hideForm) {
										self.$el.find('.forminator-row').hide();
										self.$el.find('.forminator-pagination-steps').hide();
										self.$el.find('.forminator-pagination-footer').hide();
										self.$el.find('.forminator-pagination-steps, .forminator-pagination-progress').hide();
									}
								}
							},
							error: function (err) {
								if ( 0 !== saveDraftBtn.length ) {
									self.$el.find( 'input[name="save_draft"]' ).val( 'false' );
									saveDraftBtn.addClass( 'disabled' );
								}

								$this.find('button').removeAttr('disabled');
								$target_message.html('');
								var $message = err.status === 400 ? window.ForminatorFront.cform.upload_error : window.ForminatorFront.cform.error;
								$target_message.html('<label class="forminator-label--notice"><span>' + $message + '</span></label>');
								self.focus_to_element($target_message);
								$this.trigger('forminator:form:submit:failed', [ formData, $message ] );
								self.multi_upload_disable( $this, false );
							},
							complete: function(xhr,status) {
								if (keep_froze) {
									return false;
								}
								self.$el.find('.forminator-button-submit').removeClass('forminator-button-onload');

								$this.trigger('forminator:form:submit:complete', formData);

								self.showLeadsLoader( self );
							}
						}).always(function () {
							if (keep_froze) {
								return false;
							}
							if( typeof self.settings.has_loader !== "undefined" && self.settings.has_loader ) {
								// Enable form fields
								self.$el.removeClass('forminator-fields-disabled forminator-partial-disabled');

								$target_message.removeClass('forminator-loading');
							}

							if ( 0 !== saveDraftBtn.length ) {
								self.$el.find( 'input[name="save_draft"]' ).val( 'false' );
								saveDraftBtn.addClass( 'disabled' );
							}

							self.disable_form_submit( self, false );
							$this.trigger('after:forminator:form:submit', formData);
						});
					} else {
						if( typeof self.settings.has_loader !== "undefined" && self.settings.has_loader ) {
							// Disable form fields
							self.$el.addClass('forminator-fields-disabled');

							$target_message.html('<p>' + self.settings.loader_label + '</p>');

							$target_message.removeAttr("aria-hidden")
								.prop("tabindex", "-1")
								.removeClass('forminator-success forminator-error forminator-accessible')
								.addClass('forminator-loading forminator-show');
						}

						$this.trigger( 'before:forminator:form:submit', formData );

						submitEvent.currentTarget.submit();

						self.showLeadsLoader( self );
					}
				};

				// payment setup
				const paymentElement = self.$el.find('div[data-is-payment="true"]');
				const paymentIsHidden = paymentElement
					.closest('.forminator-row, .forminator-col').hasClass('forminator-hidden') ||
					paymentElement.closest('.forminator-pagination').hasClass('forminator-page-hidden');
				if ( ( self.$el.data('forminatorFrontPayment') || self.$el.data('forminatorFrontStripe') ) && ! paymentIsHidden && ! $saveDraft ) {
					setTimeout( function() {
						self.$el.trigger('payment.before.submit.forminator', [formData, function () {
							submitCallback.apply(thisForm);
						}]);
					}, 200 );
				} else {
					submitCallback.apply(thisForm);
				}

				return false;
			});

		},

		handle_submit_form_draft: function () {
			var self = this;

			$('body').on( 'click', '.forminator-save-draft-link', function (e) {
				e.preventDefault();
				e.stopPropagation();

				var thisForm  = $( this ).closest( 'form' ),
					saveDraft = thisForm.find( 'input[name="save_draft"]' )
					;

				// prevent double clicks and clicking without any changes
				if (
					thisForm.closest( '#forminator-modal' ).hasClass( 'preview' ) ||
					'true' === saveDraft.val() ||
					$( this ).hasClass( 'disabled' )
				) {
					return;
				}

				saveDraft.val( 'true' );
				thisForm.trigger( 'submit.frontSubmit', 'draft_submit' );
			});

		},

		showDraftLink: function( data ) {
			var $form = this.$el;
			$form.trigger( 'forminator:form:draft:success', data );
			$form.find( '.forminator-response-message' ).html('');
			$form.hide();

			$( data.message ).insertBefore( $form );
			this.sendDraftLink( data );
		},

		sendDraftLink: function( data ) {
			var self = this,
				sendDraftForm = '#send-draft-link-form-' + data.draft_id
				;

			$( 'body' ).on( 'submit', sendDraftForm, function(e) {
				var form 		  = $( this ),
					draftData 	  = new FormData(this),
					emailWrap 	  = form.find( '#email-1' ),
					emailField 	  = emailWrap.find( '.forminator-field' ),
					submit		  = form.find( '.forminator-button-submit' ),
					targetMessage = form.find( '.forminator-response-message' ),
					emailResponse = form.prev( '.forminator-draft-email-response' );

				if (
					$( this ).hasClass( 'submitting' ) ||
					( $( this ).hasClass( 'forminator-has_error' ) && '' === emailWrap.find( 'input[name="email-1"]' ).val() )
				) {
					return false;
				}

				// Add submitting class and disable prop to prevent multi submissions
				form.addClass( 'submitting' );
				submit.attr( 'disabled', true );

				// Reset if there's error
				form.removeClass( 'forminator-has_error' );
				emailField.removeClass( 'forminator-has_error' );
				emailField.find( '.forminator-error-message' ).remove();

				e.preventDefault();
				$.ajax({
					type: 'POST',
					url: window.ForminatorFront.ajaxUrl,
					data: draftData,
					cache: false,
					contentType: false,
					processData: false,
					beforeSend: function () {
						form.trigger( 'before:forminator:draft:email:submit', draftData );
					},
					success: function( data ) {
						var res = data.data;
						if( ( ! data && 'undefined' !== typeof data ) || 'object' !== typeof res ) {
							submit.removeAttr( 'disabled' );
							targetMessage
								.addClass( 'forminator-error' )
								.html( '<p>' + window.ForminatorFront.cform.error + '<br>(' + res + ')</p>');
							self.focus_to_element( targetMessage );

							return false;
						}

						if (
							! data.success &&
							undefined !== res.field &&
							'email-1' === res.field &&
							! emailField.hasClass( 'forminator-has_error' )
						) {
							form.addClass( 'forminator-has_error' );
							emailField.addClass( 'forminator-has_error' );
							emailField.append( '<span class="forminator-error-message" aria-hidden="true">' + res.message + '</span>' );
						}

						if ( data.success ) {
							if ( res.draft_mail_sent ) {
								emailResponse.removeClass( 'draft-error' ).addClass( 'draft-success' );
							} else {
								emailResponse.removeClass( 'draft-success' ).addClass( 'draft-error' );
							}

							emailResponse.html( res.draft_mail_message );
							emailResponse.show();
							form.hide();
						}
					},
					error: function( error ) {
						form.removeClass( 'submitting' );
						submit.removeAttr( 'disabled' );
					}
				}).always( function() {
					form.removeClass( 'submitting' );
					submit.removeAttr( 'disabled' );
				});

				emailResponse.on( 'click', '.draft-resend-mail', function( e ) {
					e.preventDefault();

					emailResponse.slideUp( 50 );
					form.show();
				});
			} );
		},

		processCaptcha: function( self, e, $target_message, submitter ) {
			const $captcha_fields = self.$el.find('.forminator-g-recaptcha, .forminator-hcaptcha, .forminator-turnstile');
			let $captcha_field, $page;

			if ($captcha_fields.length) {
				for (let i = 0; i < $captcha_fields.length; i++) {
					//validate only first not hidden
					$captcha_field = $($captcha_fields.get(i));

					$page = $captcha_field.closest( '.forminator-pagination' );
					if ( ! $page.hasClass('forminator-page-hidden') ) {
						break;
					} else {
						$captcha_field = null;
					}
				}
			}

			if ($captcha_field && $captcha_field.length) {
				var captcha_size  = $captcha_field.data('size'),
					$captcha_parent = $captcha_field.parent( '.forminator-col' );

				// Recaptcha
				if ( $captcha_field.hasClass( 'forminator-g-recaptcha' ) ) {
					var captcha_widget  = $captcha_field.data( 'forminator-recapchta-widget' );

					// Ignore CAPTCHA re-check (only for the v2 checkbox option) during two-factor authentication.
					if (
						( captcha_size === 'normal' ||
							captcha_size === 'compact' ) &&
						'onSubmitAuthenticationBox' === submitter
					) {
						return;
					}

					if ( 0 !== $captcha_field.children().length ) {
						var $captcha_response = window.grecaptcha.getResponse( captcha_widget );
						if ( captcha_size === 'invisible' && 'forminator:preSubmit:paypal' === e.type ) {
							return;
						}
						if ( captcha_size === 'invisible' ) {
							if ( $captcha_response.length === 0 ) {
								window.grecaptcha.execute( captcha_widget );
								return false;
							}
						}

						// Ignore CAPTCHA validation after a PayPal payment.
						if( 'forminator:submit:paypal' === submitter ) {
							window.grecaptcha.reset(captcha_widget);
							return true;
						}

						// reset after getResponse
						if ( self.$el.hasClass( 'forminator_ajax' ) && 'forminator:preSubmit:paypal' !== e.type ) {
							window.grecaptcha.reset(captcha_widget);
						}
					}

				// Hcaptcha
				} else if ( $captcha_field.hasClass( 'forminator-hcaptcha' ) ) {

					var captcha_widget   = $captcha_field.data( 'forminator-hcaptcha-widget' ),
						$captcha_response = hcaptcha.getResponse( captcha_widget );

					if ( captcha_size === 'invisible' ) {
						if ( $captcha_response.length === 0 ) {
							hcaptcha.execute( captcha_widget );
							return false;
						}
					}

					// Ignore CAPTCHA validation after a PayPal payment.
					if( 'forminator:submit:paypal' === submitter ) {
						hcaptcha.reset( captcha_widget );
						return true;
					}

					// reset after getResponse
					if ( self.$el.hasClass( 'forminator_ajax' ) && 'forminator:preSubmit:paypal' !== e.type ) {
						hcaptcha.reset( captcha_widget );
					}
				} else if ( $captcha_field.hasClass( 'forminator-turnstile' ) ) {
					var captcha_widget   = $captcha_field.data( 'forminator-turnstile-widget' ),
						$captcha_response = $captcha_field.find( 'input[name="forminator-turnstile-response"]' ).val();

					// Ignore CAPTCHA validation after a PayPal payment.
					if( 'forminator:submit:paypal' === submitter ) {
						turnstile.reset( captcha_widget );
						return true;
					}

					// reset after getResponse
					if ( self.$el.hasClass( 'forminator_ajax' ) && 'forminator:preSubmit:paypal' !== e.type ) {
						turnstile.reset( captcha_widget );
					}
				}

				$target_message.html('');
				if ($captcha_field.hasClass("error")) {
					$captcha_field.removeClass("error");
				}

				if ( ! $captcha_response || $captcha_response.length === 0) {
					if (!$captcha_field.hasClass("error")) {
						$captcha_field.addClass("error");
					}

					$target_message.removeAttr("aria-hidden").html('<label class="forminator-label--error forminator-invalid-captcha"><span>' + window.ForminatorFront.cform.captcha_error + '</span></label>');

					if ( ! self.settings.inline_validation ) {
						self.focus_to_element($target_message);
					} else {

						if ( ! $captcha_parent.hasClass( 'forminator-has_error' ) && $captcha_field.data( 'size' ) !== 'invisible' ) {
							$captcha_parent.addClass( 'forminator-has_error' )
								.append( '<span class="forminator-error-message forminator-invalid-captcha" aria-hidden="true">' + window.ForminatorFront.cform.captcha_error + '</span>' );
							self.focus_to_element( $captcha_parent );
						}

					}

					return false;
				}
			}

		},

		hideForm: function( form ) {
			form.css({
				'height': 0,
				'opacity': 0,
				'overflow': 'hidden',
				'visibility': 'hidden',
				'pointer-events': 'none',
				'margin': 0,
				'padding': 0,
				'border': 0,
				'display': 'none',
			});
		},

		showForm: function( form ) {
			form.css({
				'height': '',
				'opacity': '',
				'overflow': '',
				'visibility': '',
				'pointer-events': '',
				'margin': '',
				'padding': '',
				'border': '',
				'display': 'block',
			});
		},

		showQuiz: function( form ) {
			var quizForm = $('#forminator-module-' + this.settings.quiz_id ),
				parent = $( '#forminator-quiz-leads-' + this.settings.quiz_id );

			this.hideForm( form );
			parent.find( '.forminator-lead-form-skip' ).hide();
			if( 'undefined' !== typeof this.settings.form_placement && 'beginning' === this.settings.form_placement ) {
				this.showForm( quizForm );
				if ( quizForm.find('.forminator-pagination').length ) {
					parent.find( '.forminator-quiz-intro').hide();
					quizForm.prepend('<button class="forminator-button forminator-quiz-start forminator-hidden"></button>')
							.find('.forminator-quiz-start').trigger('click').remove();
				}
			}
		},

		handle_submit_quiz: function( data ) {

			var self = this,
				hasLeads = 'undefined' !== typeof self.settings.hasLeads ? self.settings.hasLeads : false,
				leads_id = 'undefined' !== typeof self.settings.leads_id ? self.settings.leads_id : 0,
				quiz_id = 'undefined' !== typeof self.settings.quiz_id ? self.settings.quiz_id : 0;

			$( 'body' ).on( 'submit.frontSubmit', this.settings.forminator_selector, function( e ) {
				if ( 0 !== self.$el.find( 'input[type="hidden"][value="forminator_submit_preview_form_quizzes"]' ).length ) {
					return false;
				}
				var form       = $(this),
					ajaxData   = [],
					formData   = new FormData( this ),
					answer     = form.find( '.forminator-answer' ),
					button	   = self.$el.find('.forminator-button').last(),
					quizResult = self.$el.find( '.forminator-quiz--result' ),
					loadLabel  = button.data( 'loading' ),
					placement  = 'undefined' !== typeof self.settings.form_placement ? self.settings.form_placement : '',
					skip_form  = 'undefined' !== typeof self.settings.skip_form ? self.settings.skip_form : '',
					$target_message = self.$el.find( '.forminator-response-message' )
					;

				e.preventDefault();
				e.stopPropagation();

				// Enable all inputs
				self.$el.find( '.forminator-has-been-disabled' ).removeAttr( 'disabled' );

				// Serialize fields, that should be placed here!
				ajaxData = form.serialize();

				// Disable inputs again
				self.$el.find( '.forminator-has-been-disabled' ).attr( 'disabled', 'disabled' );

				if( hasLeads ) {
					// Get lead entry ID from hidden input to include in quiz submission
					let leadsFormID = self.$el.find('input[name=entry_id]').attr('lead-entry-id') || '';
					if ( leadsFormID ) {
						ajaxData += '&lead_entry_id=' + leadsFormID;
					}

					var entry_id  = '';
					if ( self.$el.find('input[name=entry_id]').length > 0 ) {
						entry_id = self.$el.find('input[name=entry_id]').val();
					}
					if( 'end' === placement && entry_id === '' ) {
						self.showForm( $('#forminator-module-' + leads_id ) );
						quizResult.addClass( 'forminator-hidden' );
						$('#forminator-quiz-leads-' + quiz_id + ' .forminator-lead-form-skip' ).show();

						return false;
					}

					if( ! skip_form && entry_id === '' ) {
						return false;
					}
				}

				// Add loading label.
				if ( loadLabel !== '' ) {
					button.text( loadLabel );
				}

				if ( self.settings.has_quiz_loader ) {
					answer.each( function() {
						var answer = $( this ),
							input  = answer.find( 'input' ),
							status = answer.find( '.forminator-answer--status' ),
							loader = '<i class="forminator-icon-loader forminator-loading"></i>'
							;

						if ( input.is( ':checked' ) ) {
							if ( 0 === status.html().length ) {
								status.html( loader );
							}
						}
					});
				}

				var pagination = !! self.$el.find('.forminator-pagination');

				$.ajax({
					type: 'POST',
					url: window.ForminatorFront.ajaxUrl,
					data: ajaxData,
					beforeSend: function() {
						if ( ! pagination ) {
							self.$el.find( 'button' ).attr( 'disabled', 'disabled' );
						}
						form.trigger( 'before:forminator:quiz:submit', [ ajaxData, formData ] );
					},
					success: function( data ) {

						if ( data.success ) {
							var resultText = '';

							quizResult.removeClass( 'forminator-hidden' );
							window.history.pushState( 'forminator', 'Forminator', data.data.result_url );

							if ( data.data.type === 'nowrong' ) {
								resultText = data.data.result;

								quizResult.html( resultText );
								if ( ! pagination ) {
									self.$el.find( '.forminator-answer input' ).attr( 'disabled', 'disabled' );
								}

							} else if ( data.data.type === 'knowledge' ) {
								resultText = data.data.finalText;

								if ( quizResult.length > 0 ) {
									quizResult.html( resultText );
								}

								Object.keys( data.data.result ).forEach( function( key ) {

									var responseClass,
										responseIcon,
										parent  = self.$el.find( '#' + key ),
										result  = parent.find( '.forminator-question--result' ),
										submit  = parent.find( '.forminator-submit-rightaway' ),
										answers = parent.find( '.forminator-answer input' )
										;

									// Check if selected answer is right or wrong.
									if ( data.data.result[key].isCorrect ) {
										responseClass = 'forminator-is_correct';
										responseIcon  = '<i class="forminator-icon-check"></i>';
									} else {
										responseClass = 'forminator-is_incorrect';
										responseIcon  = '<i class="forminator-icon-cancel"></i>';
									}

									// Show question result.
									result.text( data.data.result[key].message );
									result.addClass( 'forminator-show' );
									submit.attr( 'disabled', true );
									submit.attr( 'aria-disabled', true );

									// Prevent user from changing answer.
									answers.attr( 'disabled', true );
									answers.attr( 'aria-disabled', true );

									// For multiple answers per question
									if ( undefined === data.data.result[key].answer ) {
											var answersArray = data.data.result[key].answers;

											for ( var $i = 0; $i < answersArray.length; $i++ ) {
													var answer = parent.find( '[id|="' + answersArray[$i].id + '"]' ).closest( '.forminator-answer' );

													// Check if selected answer is right or wrong.
													answer.addClass( responseClass );
													if ( 0 === answer.find( '.forminator-answer--status' ).html().length ) {
															answer.find( '.forminator-answer--status' ).html( responseIcon );
													} else {

															if ( 0 !== answer.find( '.forminator-answer--status .forminator-icon-loader' ).length ) {
																	answer.find( '.forminator-answer--status' ).html( responseIcon );
															}
													}
											}

									// For single answer per question
									} else {
											var answer = parent.find( '[id|="' + data.data.result[key].answer + '"]' ).closest( '.forminator-answer' );

											// Check if selected answer is right or wrong.
											answer.addClass( responseClass );
											if ( 0 === answer.find( '.forminator-answer--status' ).html().length ) {
													answer.find( '.forminator-answer--status' ).html( responseIcon );
											} else {

													if ( 0 !== answer.find( '.forminator-answer--status .forminator-icon-loader' ).length ) {
															answer.find( '.forminator-answer--status' ).html( responseIcon );
													}
											}
									}

								});
							}

							form.trigger( 'forminator:quiz:submit:success', [ ajaxData, formData, resultText ] ) ;

							if ( 0 !== quizResult.find( '.forminator-quiz--summary' ).length && ! quizResult.parent().hasClass( 'forminator-pagination--content' ) ) {
								self.focus_to_element( quizResult.find( '.forminator-quiz--summary' ) );
							}

						} else {
							self.$el.find( 'button' ).removeAttr( 'disabled' );
							$target_message.removeClass( 'forminator-hidden' );
							$target_message.html( data.data.error );
							self.focus_to_element( $target_message );

							form.trigger( 'forminator:quiz:submit:failed', [ ajaxData, formData ] );
						}
					}
				}).always(function () {
					form.trigger('after:forminator:quiz:submit', [ ajaxData, formData ] );
					form.nextAll( '.leads-quiz-loader' ).remove();
				});
				return false;
			});

			$('body').on('click', '#forminator-quiz-leads-' + quiz_id + ' .forminator-lead-form-skip', function (e) {
				self.showQuiz( $('#forminator-module-' + leads_id) );

				if( 'undefined' !== typeof self.settings.form_placement && 'end' === self.settings.form_placement ) {
					self.settings.form_placement = 'skip';
					self.$el.submit();
				}
			});

			$('body').on('click', '.forminator-result--retake', function (e) {
				var pageId = self.$el.find('input[name="page_id"]').val();
				var ajaxData = {
					action: 'forminator_reload_quiz',
					pageId:	pageId,
					moduleId: self.$el.find('input[name="form_id"]').val(),
					nonce: self.$el.find('input[name="forminator_nonce"]').val()
				};

				e.preventDefault();

				$.post( window.ForminatorFront.ajaxUrl, ajaxData, function( response ) {
					if ( response.success == true && response.html ) {
						window.location.replace(response.html);
					}
				} );
			});
		},

		handle_submit_poll: function () {
			var self = this,
				poll_form = self.$el.html();

			// Hide (success) response message
			var success_available = self.$el.find( '.forminator-response-message' ).not( ':hidden' );

			if ( success_available.length ) {

				self.focus_to_element(
					self.$el.find( '.forminator-response-message' ),
					true
				);
			}

			$( 'body' ).on( 'submit.frontSubmit', this.settings.forminator_selector, function (e) {
				if ( 0 !== self.$el.find( 'input[type="hidden"][value="forminator_submit_preview_form_poll"]' ).length ) {
					return false;
				}
				var $this    = $( this ),
					formData  = new FormData( this ),
					ajaxData  = $this.serialize()
				;

				var $response = self.$el.find( '.forminator-response-message' ),
					$options  = self.$el.find( 'fieldset' ),
					$submit   = self.$el.find( '.forminator-button' )
				;

				function response_clean() {
					// Remove content
					$response.html( '' );

					// Remove all classes
					$response.removeClass( 'forminator-show' );
					$response.removeClass( 'forminator-error' );
					$response.removeClass( 'forminator-success' );

					// Hide for screen readers
					$response.removeAttr( 'tabindex' );
					$response.attr( 'aria-hidden', true );

					// Remove options error class
					$options.removeClass( 'forminator-has_error' );

				}

				function response_message( message, custom_class ) {

					// Print message
					$response.html( '<p>' + message + '</p>' );

					// Add necessary classes
					$response.addClass( 'forminator-' + custom_class );
					$response.addClass( 'forminator-show' );

					// Show for screen readers
					$response.removeAttr( 'aria-hidden' );
					$response.attr( 'tabindex', '-1' );

					// Focus message
					$response.focus();

					// Add options error class
					if ( 'error' === custom_class ) {

						if ( ! $options.find( 'input[type="radio"]' ).is( ':checked' ) ) {
							$options.addClass( 'forminator-has_error' );
						}
					}
				}

				if ( self.$el.hasClass( 'forminator_ajax' ) ) {
					response_clean();

					$.ajax({
						type: 'POST',
						url:  window.ForminatorFront.ajaxUrl,
						data: ajaxData,

						beforeSend: function() {

							// Animate "submit" button
							$submit.addClass( 'forminator-onload' );

							// Trigger "submit" action
							$this.trigger( 'before:forminator:poll:submit', [ ajaxData, formData ] );

						},

						success: function( data ) {

							var $label_class = data.success ? 'success' : 'error';

							// Stop "submit" animation
							$submit.removeClass( 'forminator-onload' );

							if ( false === data.success ) {

								// Print message
								response_message( data.data.message, $label_class );

								// Failed response
								$this.trigger( 'forminator:poll:submit:failed', [ ajaxData, formData ] );

							} else {

								if ( 'undefined' !== typeof data.data ) {

									$label_class = data.data.success ? 'success' : 'error';

									// Print message
									response_message( data.data.message, $label_class );

									// Auto close message
									setTimeout( function() {
										response_clean();
									}, 2500 );

								}
							}

							if ( true === data.success ) {

								if ( typeof data.data.url !== 'undefined' ) {
									window.location.href = data.data.url;
								} else {

									// url not exist, it will render chart on the fly if chart_data exist on response
									// check length is > 1, because [0] is header
									if ( typeof data.data.chart_data !== 'undefined' && data.data.chart_data.length > 1 ) {

										if ( 'link_on' === data.data.results_behav ) {

											if ( $this.find( '.forminator-note' ).length ) {
												$this.find( '.forminator-note' ).remove();
												$this.find( '.forminator-poll-footer' ).append( data.data.results_link );
											}
										}

										if ( 'show_after' === data.data.results_behav ) {

											self.render_poll_chart(
												data.data.chart_data,
												data.data.back_button,
												self,
												poll_form,
												[
													data.data.votes_text,
													data.data.votes_count,
													[
														data.data.grids_color,
														data.data.labels_color,
														data.data.onchart_label
													],
													[
														data.data.tooltips_bg,
														data.data.tooltips_color
													]
												]
											);

										}
									}
								}

								// Success response
								$this.trigger( 'forminator:poll:submit:success', [ ajaxData, formData ] );

							}
						},

						error: function() {

							response_clean();

							// Stop "submit" animation
							$submit.removeClass( '.forminator-onload' );

							// Failed response
							$this.trigger( 'forminator:poll:submit:failed', [ ajaxData, formData ] );

						}
					}).always( function() {

						$this.trigger( 'after:forminator:poll:submit', [ ajaxData, formData ] );

					});

					return false;

				}

				return true;

			});
		},

		render_poll_chart: function( chart_data, back_button, forminatorSubmit, poll_form, chart_extras ) {
			var pollId      = forminatorSubmit.$el.attr( 'id' ) + '-' + forminatorSubmit.$el.data('forminatorRender'),
				chartId     = 'forminator-chart-poll-' + pollId,
				pollBody    = forminatorSubmit.$el.find( '.forminator-poll-body' ),
				pollFooter  = forminatorSubmit.$el.find( '.forminator-poll-footer' )
				;

			function chart_clean() {

				var canvas = forminatorSubmit.$el.find( '.forminator-chart-wrapper' ),
					wrapper = forminatorSubmit.$el.find( '.forminator-chart' )
					;

				canvas.remove();
				wrapper.remove();

			}

			function chart_create() {
				var canvas = $( '<canvas id="' + chartId + '" class="forminator-chart" role="img" aria-hidden="true"></canvas>' );

				pollBody.append( canvas );
			}

			function chart_show() {
				var canvas = forminatorSubmit.$el.find( '.forminator-chart' ),
					wrapper = forminatorSubmit.$el.find( '.forminator-chart-wrapper' )
					;

				if ( wrapper.length ) {

					// Show canvas
					canvas.addClass( 'forminator-show' );

					// Show wrapper
					wrapper.addClass( 'forminator-show' );
					wrapper.removeAttr( 'aria-hidden' );
					wrapper.attr( 'tabindex', '-1' );

					// Focus message
					wrapper.focus();
				} else {
					// Fallback text
					canvas.html( '<p>Fallback text...</p>' );

					// Show canvas
					canvas.addClass( 'forminator-show' );
					canvas.removeAttr( 'aria-hidden' );
					canvas.attr( 'tabindex', '-1' );

					// Focus message
					canvas.focus();
				}
			}

			function hide_answers() {
				var answers = pollBody.find( '.forminator-field' );

				answers.hide();
				answers.attr( 'aria-hidden', 'true' );
			}

			function replace_footer() {

				var button = $( back_button );

				pollFooter.empty();
				pollFooter.append( button );

			}

			function back_to_poll() {

				var button = forminatorSubmit.$el.find( '.forminator-button' );

				button.click( function( e ) {

					if ( forminatorSubmit.$el.hasClass( 'forminator_ajax' ) ) {
						forminatorSubmit.$el.html( poll_form );
					} else {
						location.reload();
					}

					e.preventDefault();

				});
			}

			// Remove previously chart if exists
			chart_clean();

			// Create chart markup
			chart_create();

			// Load chart
			FUI.pollChart(
				'#' + chartId,
				chart_data,
				forminatorSubmit.settings.chart_design,
				chart_extras
			);

			// Hide poll answers
			hide_answers();

			// Show poll chart
			chart_show();

			// Replace footer
			replace_footer();
			back_to_poll();

		},

		focus_to_element: function ( $element, not_scroll, fadeout, fadeout_time ) {
			fadeout = fadeout || false;
			fadeout_time = fadeout_time || 0;
			not_scroll = not_scroll || false;
			var parent_selector = 'html,body';

			// check inside sui modal
			if ( $element.closest( '.sui-dialog' ).length > 0 ) {
				parent_selector = '.sui-dialog';
			}

			// check inside hustle modal (prioritize)
			if ( $element.closest( '.wph-modal' ).length > 0 ) {
				parent_selector = '.wph-modal';
			}

			// if element is not forminator textarea, force show in case its hidden of fadeOut
			if ( ! $element.hasClass( 'forminator-textarea' ) && ! $element.parent( '.wp-editor-container' ).length ) {
				$element.show();
			} else if ( $element.hasClass( 'forminator-textarea' ) && $element.parent( '.wp-editor-container' ).length ) {
				$element = $element.parent( '.wp-editor-container' );
			}

			function focusElement( $element ) {
				if ( ! $element.attr("tabindex") && $element.is( 'div' ) ) {
					$element.attr("tabindex", -1);
				}

				if ( ! $element.hasClass( 'forminator-select2' ) ) {
					$element.focus();
				}

				if (fadeout) {
					$element.show().delay( fadeout_time ).fadeOut('slow');
				}
			}

			if ( not_scroll ) {
				focusElement($element);
			} else {
				$( parent_selector ).animate({scrollTop: ($element.offset().top - ($(window).height() - $element.outerHeight(true)) / 2)}, 500, function () {
					focusElement($element);
				});
			}
		},

		show_messages: function( errors ) {
			var self = this,
				forminatorFrontCondition = self.$el.data('forminatorFrontCondition');
			if (typeof forminatorFrontCondition !== 'undefined') {

				// clear all validation message before show new one
				this.$el.find('.forminator-error-message').remove();
				var i = 0;

				errors.forEach( function( value ) {

					var elementId  = Object.keys( value ),
						getElement = forminatorFrontCondition.get_form_field( elementId )
						;

					var holder          = $( getElement ),
						holderField     = holder.closest( '.forminator-field' ),
						holderDate      = holder.closest( '.forminator-date-input' ),
						holderTime      = holder.closest( '.forminator-timepicker' ),
						holderError     = '',
						getColumn       = false,
						getError        = false,
						getDesc         = false,
						errorId         = holder.attr('id') + '-error',
						ariaDescribedby = holder.attr('aria-describedby')
						;

					var errorMessage = Object.values( value ),
						errorMarkup  = '<span class="forminator-error-message" id="'+ errorId +'"></span>'
						;

					if ( getElement.length ) {

						// Focus on first error
						if ( i === 0 ) {
							self.$el.trigger( 'forminator.front.pagination.focus.input', [getElement]);
							if( getElement.is( ':visible' ) ) {
								self.focus_to_element( getElement );
							}
						}

						// CHECK: Timepicker field.
						if ( holderDate.length > 0 ) {

							getColumn = holderDate.parent();
							getError  = getColumn.find( '.forminator-error-message[data-error-field="' + holder.data( 'field' ) + '"]' );
							getDesc   = getColumn.find( '.forminator-description' );

							errorMarkup = '<span class="forminator-error-message" data-error-field="' + holder.data( 'field' ) + '" id="'+ errorId +'"></span>';

							if ( 0 === getError.length ) {

								if ( 'day' === holder.data( 'field' ) ) {

									if ( getColumn.find( '.forminator-error-message[data-error-field="year"]' ).length ) {

										$( errorMarkup ).insertBefore( getColumn.find( '.forminator-error-message[data-error-field="year"]' ) );

									} else {
										forminatorUtils().add_error_message( getDesc, getColumn, errorMarkup );
									}

									if ( 0 === holderField.find( '.forminator-error-message' ).length ) {

										holderField.append(
											'<span class="forminator-error-message" id="'+ errorId +'"></span>'
										);
									}
								}

								if ( 'month' === holder.data( 'field' ) ) {

									if ( getColumn.find( '.forminator-error-message[data-error-field="day"]' ).length ) {

										$( errorMarkup ).insertBefore(
											getColumn.find( '.forminator-error-message[data-error-field="day"]' )
										);

									} else {
										forminatorUtils().add_error_message( getDesc, getColumn, errorMarkup );
									}

									if ( 0 === holderField.find( '.forminator-error-message' ).length ) {

										holderField.append(
											'<span class="forminator-error-message" id="'+ errorId +'"></span>'
										);
									}
								}

								if ( 'year' === holder.data( 'field' ) ) {

									forminatorUtils().add_error_message( getDesc, getColumn, errorMarkup );

									if ( 0 === holderField.find( '.forminator-error-message' ).length ) {

										holderField.append(
											'<span class="forminator-error-message" id="'+ errorId +'"></span>'
										);
									}
								}
							}

							holderError = getColumn.find( '.forminator-error-message[data-error-field="' + holder.data( 'field' ) + '"]' );

							// Insert error message
							holderError.html( errorMessage );
							holderField.find( '.forminator-error-message' ).html( errorMessage );

						} else if ( holderTime.length > 0 && errorMessage[0].length > 0 ) {

							getColumn = holderTime.parent();
							getError  = getColumn.find( '.forminator-error-message[data-error-field="' + holder.data( 'field' ) + '"]' );
							getDesc   = getColumn.find( '.forminator-description' );

							errorMarkup = '<span class="forminator-error-message" data-error-field="' + holder.data( 'field' ) + '" id="'+ errorId +'"></span>';

							if ( 0 === getError.length ) {

								if ( 'hours' === holder.data( 'field' ) ) {

									if ( getColumn.find( '.forminator-error-message[data-error-field="minutes"]' ).length ) {

										$( errorMarkup ).insertBefore(
											getColumn.find( '.forminator-error-message[data-error-field="minutes"]' )
										);
									} else {
										forminatorUtils().add_error_message( getDesc, getColumn, errorMarkup );
									}

									if ( 0 === holderField.find( '.forminator-error-message' ).length ) {

										holderField.append(
											'<span class="forminator-error-message" id="'+ errorId +'"></span>'
										);
									}
								}

								if ( 'minutes' === holder.data( 'field' ) ) {
									forminatorUtils().add_error_message( getDesc, getColumn, errorMarkup );

									if ( 0 === holderField.find( '.forminator-error-message' ).length ) {

										holderField.append(
											'<span class="forminator-error-message" id="'+ errorId +'"></span>'
										);
									}
								}
							}

							holderError = getColumn.find( '.forminator-error-message[data-error-field="' + holder.data( 'field' ) + '"]' );

							// Insert error message
							holderError.html( errorMessage );
							holderField.find( '.forminator-error-message' ).html( errorMessage );

						} else {

							var getError = holderField.find( '.forminator-error-message' ),
								getDesc  = holderField.find( '.forminator-description' )
								;

							if ( 0 === getError.length ) {
								forminatorUtils().add_error_message( getDesc, holderField, errorMarkup );
							}

							holderError = holderField.find( '.forminator-error-message' );

							// Insert error message
							holderError.html( errorMessage );

						}

						// Field aria describedby for screen readers
						if (ariaDescribedby) {
							var ids = ariaDescribedby.split(' ');
							var errorIdExists = ids.includes(errorId);
							if (!errorIdExists) {
								ids.push(errorId);
							}
							var updatedAriaDescribedby = ids.join(' ');
							holder.attr('aria-describedby', updatedAriaDescribedby);
						} else {
							holder.attr('aria-describedby', errorId);
						}

						// Field invalid status for screen readers
						holder.attr( 'aria-invalid', 'true' );

						// Field error status
						holderField.addClass( 'forminator-has_error' );

						i++;

					}
				});
			}

			return this;
		},

		multi_upload_disable: function ( $form, disable ) {
			$form.find( '.forminator-multi-upload input' ).each( function() {
				var file_method = $(this).data('method');
				if( 'ajax' === file_method ) {
					$(this).attr('disabled', disable);
				}
			});
		},

		disable_form_submit: function ( form, disable  ) {
			form.$el.find( '.forminator-button-submit' ).prop( 'disabled', disable );
		},

		showLeadsLoader: function ( quiz  ) {
			if( quiz.settings.hasLeads && 'end' === quiz.settings.form_placement ) {
				$( '#forminator-quiz-leads-' + quiz.settings.quiz_id )
				.append(
					'<div class="leads-quiz-loader forminator-response-message">' +
					'<i class="forminator-icon-loader forminator-loading" ' +
					'aria-hidden="true"></i>' +
					'<style>.leads-quiz-loader{padding:20px;text-align:center;}.leads-quiz-loader .forminator-loading:before{display:block;}</style>' +
					'</div>'
				);
			}
		}

	});

	// A really lightweight plugin wrapper around the constructor,
	// preventing against multiple instantiations
	$.fn[pluginName] = function (options) {
		return this.each(function () {
			if (!$.data(this, pluginName)) {
				$.data(this, pluginName, new ForminatorFrontSubmit(this, options));
			}
		});
	};

})(jQuery, window, document);
