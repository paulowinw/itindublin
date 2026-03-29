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
	var pluginName = "forminatorFront",
	    defaults   = {
		    form_type: 'custom-form',
		    rules: {},
		    messages: {},
		    conditions: {},
		    inline_validation: false,
		    print_value: false,
		    chart_design: 'bar',
		    chart_options: {},
		    forminator_fields: [],
		    general_messages: {
			    calculation_error: 'Failed to calculate field.',
			    payment_require_ssl_error: 'SSL required to submit this form, please check your URL.',
				payment_require_amount_error: 'PayPal amount must be greater than 0.',
			    form_has_error: 'Please correct the errors before submission.'
		    },
		    payment_require_ssl : false,
	    };

	// The actual plugin constructor
	function ForminatorFront(element, options) {
		this.element                    = element;
		this.$el                        = $(this.element);
		this.forminator_selector        = '#' + $(this.element).attr('id') + '[data-forminator-render="' + $(this.element).data('forminator-render') + '"]';
		this.forminator_loader_selector = 'div[data-forminator-render="' + $(this.element).data('forminator-render') + '"]' + '[data-form="' + $(this.element).attr('id') + '"]';

		// jQuery has an extend method which merges the contents of two or
		// more objects, storing the result in the first object. The first object
		// is generally empty as we don't want to alter the default options for
		// future instances of the plugin
		this.settings = $.extend({}, defaults, options);

		// special treatment for rules, messages, and conditions
		if (typeof this.settings.messages !== 'undefined') {
			this.settings.messages = this.maybeParseStringToJson(this.settings.messages, 'object');
		}
		if (typeof this.settings.rules !== 'undefined') {
			this.settings.rules = this.maybeParseStringToJson(this.settings.rules, 'object');
		}
		if (typeof this.settings.calendar !== 'undefined') {
			this.settings.calendar = this.maybeParseStringToJson(this.settings.calendar, 'array');
		}

		this._defaults = defaults;
		this._name     = pluginName;
		this.form_id   = 0;
		this.template_type = '';

		this.init();
		this.handleDiviPopup();
	}

	// Avoid Plugin.prototype conflicts
	$.extend(ForminatorFront.prototype, {
		init: function () {
			var self = this;

			if (this.$el.find('input[name="form_id"]').length > 0) {
				this.form_id = this.$el.find('input[name="form_id"]').val();
			}
			if (this.$el.find('input[name="form_type"]').length > 0) {
				this.template_type = this.$el.find('input[name="form_type"]').val();
			}

			$(this.forminator_loader_selector).remove();

			// If form from hustle popup, do not show
			if (this.$el.closest('.wph-modal').length === 0) {
				this.$el.show();
			}

			// Show form when popup trigger with click
			$(document).on("hustle:module:displayed", function (e, data) {
				var $modal = $('.wph-modal-active');
				$modal.find('form').css('display', '');
			});

			self.reint_intlTelInput();

			// Show form when popup trigger
			setTimeout(function () {
				var $modal = $('.wph-modal-active');
				$modal.find('form').css('display', '');
			}, 10);

			//selective activation based on type of form
			switch (this.settings.form_type) {
				case  'custom-form':
					$( this.element ).each( function() {
						self.init_custom_form( this );
					});

					this.$el.on( 'forminator-clone-group', function ( event ) {
						self.init_custom_form( event.target );
					} );

					break;
				case  'poll':
					this.init_poll_form();
					break;
				case  'quiz':
					this.init_quiz_form();
					break;

			}

			//init submit
			var submitOptions = {
				form_type: self.settings.form_type,
				forminator_selector: self.forminator_selector,
				chart_design: self.settings.chart_design,
				chart_options: self.settings.chart_options,
				has_quiz_loader: self.settings.has_quiz_loader,
				has_loader: self.settings.has_loader,
				loader_label: self.settings.loader_label,
				resetEnabled: self.settings.is_reset_enabled,
				inline_validation: self.settings.inline_validation,
			};

			if( 'leads' === this.template_type || 'quiz' === this.settings.form_type ) {
				submitOptions.form_placement = self.settings.form_placement;
				submitOptions.hasLeads = self.settings.hasLeads;
				submitOptions.leads_id = self.settings.leads_id;
				submitOptions.quiz_id = self.settings.quiz_id;
				submitOptions.skip_form = self.settings.skip_form;
			}

			$(this.element).forminatorFrontSubmit( submitOptions );


			// TODO: confirm usage on form type
			// Handle field activation classes
			this.activate_field();
			// Handle special classes for material design
			// this.material_field();

			// Init small form for all type of form
			this.small_form();

			// trigger form added in the DOM
			$(document).trigger('forminator:form:added')
		},
		init_custom_form: function ( form_selector ) {

			var self 			= this,
				instantPreview = $( form_selector ).closest( '#forminator-instant-preview' ).length,
				draftTimer
				;

			//initiate validator
			this.init_intlTelInput_validation( form_selector );

			if (this.settings.inline_validation) {

				$( form_selector ).forminatorFrontValidate({
					rules: self.settings.rules,
					messages: self.settings.messages
				});
			}

			// initiate calculator
			$( form_selector ).forminatorFrontCalculate({
				forminatorFields: self.settings.forminator_fields,
				generalMessages: self.settings.general_messages,
				memoizeTime: self.settings.calcs_memoize_time || 300,
			});

			// initiate merge tags
			$( form_selector ).forminatorFrontMergeTags({
				forminatorFields: self.settings.forminator_fields,
				print_value: self.settings.print_value,
			});

			//initiate pagination
			this.init_pagination( form_selector );

			if( self.settings.has_stripe ) {
				var stripe_payment = $(this.element).find('.forminator-stripe-element[data-type="stripe-ocs"]').first();
				if ( 1 > stripe_payment.length ) {
					stripe_payment = $(this.element).find('.forminator-stripe-element').first();
				}

				if ( $( self.element ).is( ':visible' ) ) {
					this.renderStripe( self, stripe_payment );
				}

				// Show Stripe on modal display.
				$( document ).on( "forminator:form:added", function () {
					self.renderStripe( self, stripe_payment );
				});
			}

			if( self.settings.has_paypal
					// Fix for Divi popup.
					&& ( ! $( self.element ).closest( '.et_pb_section' ).length
					|| $( self.element ).is( ':visible' ) ) ) {
				$(this.element).forminatorFrontPayPal({
					type: 'paypal',
					paymentEl: this.settings.paypal_config,
					paymentRequireSsl: self.settings.payment_require_ssl,
					generalMessages: self.settings.general_messages,
					has_loader: self.settings.has_loader,
					loader_label: self.settings.loader_label,
				});
			}

			//initiate condition - skip in instant preview
			if ( ! instantPreview ) {
				$( form_selector ).forminatorFrontCondition(this.settings.conditions, this.settings.calendar);
			}

			//initiate forminator ui scripts
			this.init_fui( form_selector );

			//initiate datepicker
			$( form_selector ).find('.forminator-datepicker').forminatorFrontDatePicker(this.settings.calendar);

			// Handle responsive captcha
			this.responsive_captcha( form_selector );

			// Handle field counter
			this.field_counter( form_selector );

			// Handle number input
			this.field_number( form_selector );

			// Handle time fields
			this.field_time();

			// Handle upload field change
			$( form_selector ).find('.forminator-multi-upload').forminatorFrontMultiFile( this.$el );

			this.upload_field( form_selector );

			this.init_login_2FA();

			self.maybeRemoveDuplicateFields( form_selector );

			self.checkComplianzBlocker();

			// Handle function on resize
			$(window).on('resize', function () {
				self.responsive_captcha( form_selector );
			});

			// Handle function on load
			$( window ).on( 'load', function () {
				// Repeat the function here, just in case our scripts gets loaded late
				self.maybeRemoveDuplicateFields( form_selector );
			});

			var $saveDrafts = this.$el.find( '.forminator-save-draft-link' );

			// We have to declare initialData here, after everything has been set initially, to prevent triggering change event.
			$saveDrafts.each(function() {
				const $saveDraft = $(this),
					$parent = $saveDraft.closest('.forminator-pagination, form');
				var initialData	= $parent.find(':input').serializeArray();
				let draftTimer;
				const triggerDraftCheck = function () {
					if ( $saveDraft.hasClass( 'disabled' ) ) {
						clearTimeout( draftTimer );
						draftTimer = setTimeout( function() {
								self.maybe_enable_save_draft( $parent, $saveDraft, initialData );
							},
							500
						);
					}
				};

				$parent.on('forminator-clone-group forminator-group-item-removed', triggerDraftCheck);
				$parent.find( ".forminator-field input, .forminator-row input[type=hidden], .forminator-field select, .forminator-field textarea, .forminator-field-signature").on( 'change input', triggerDraftCheck);
				
			});

			if( 'undefined' !== typeof self.settings.hasLeads ) {
				if( 'beginning' === self.settings.form_placement ) {
					$('#forminator-module-' + this.settings.quiz_id ).css({
						'height': 0,
						'opacity': 0,
						'overflow': 'hidden',
						'visibility': 'hidden',
						'pointer-events': 'none',
						'margin': 0,
						'padding': 0,
						'border': 0
					});
				}
				if( 'end' === self.settings.form_placement ) {
					$( form_selector ).css({
						'height': 0,
						'opacity': 0,
						'overflow': 'hidden',
						'visibility': 'hidden',
						'pointer-events': 'none',
						'margin': 0,
						'padding': 0,
						'border': 0
					});
				}
			}

		},
		init_poll_form: function() {

			var self       = this,
				$fieldset  = this.$el.find( 'fieldset' ),
				$selection = this.$el.find( '.forminator-radio input' ),
				$input     = this.$el.find( '.forminator-input' ),
				$field     = $input.closest( '.forminator-field' )
				;

			// Load input states
			FUI.inputStates( $input );

			// Show input when option has been selected
			$selection.on( 'click', function() {

				// Reset
				$field.addClass( 'forminator-hidden' );
				$field.attr( 'aria-hidden', 'true' );
				$input.removeAttr( 'tabindex' );
				$input.attr( 'name', '' );

				var checked = this.checked,
					$id     = $( this ).attr( 'id' ),
					$name   = $( this ).attr( 'name' )
					;

				// Once an option has been chosen, remove error class.
				$fieldset.removeClass( 'forminator-has_error' );

				if ( self.$el.find( '.forminator-input#' + $id + '-extra' ).length ) {

					var $extra = self.$el.find( '.forminator-input#' + $id + '-extra' ),
						$extraField = $extra.closest( '.forminator-field' )
						;

					if ( checked ) {

						$extra.attr( 'name', $name + '-extra' );

						$extraField.removeClass( 'forminator-hidden' );
						$extraField.removeAttr( 'aria-hidden' );

						$extra.attr( 'tabindex', '-1' );
						$extra.focus();

					} else {

						$extraField.addClass( 'forminator-hidden' );
						$extraField.attr( 'aria-hidden', 'true' );

						$extra.removeAttr( 'tabindex' );

					}
				}

				return true;

			});

			// Disable options
			if ( this.$el.hasClass( 'forminator-poll-disabled' ) ) {

				this.$el.find( '.forminator-radio' ).each( function() {

					$( this ).addClass( 'forminator-disabled' );
					$( this ).find( 'input' ).attr( 'disabled', true );

				});
			}
		},

		init_quiz_form: function () {
			var self = this,
				lead_placement = 'undefined' !== typeof self.settings.form_placement ? self.settings.form_placement : '',
				quiz_id = 'undefined' !== typeof self.settings.quiz_id ? self.settings.quiz_id : 0;

			this.$el.find('.forminator-button:not(.forminator-quiz-start)').each(function () {
				$(this).prop("disabled", true);
			});

			this.$el.find('.forminator-answer input').each(function () {
				$(this).attr('checked', false);
			});

			this.$el.find('.forminator-result--info button').on('click', function () {
				location.reload();
			});

			$('#forminator-quiz-leads-' + quiz_id + ' .forminator-quiz-intro .forminator-quiz-start').on('click', function(e){
				e.preventDefault();
				$(this).closest( '.forminator-quiz-intro').hide();
				self.$el.prepend('<button class="forminator-button forminator-quiz-start forminator-hidden"></button>')
						.find('.forminator-quiz-start').trigger('click').remove();
			});

			this.$el.on('click', '.forminator-quiz-start', function (e) {
				e.preventDefault();
				self.$el.find('.forminator-quiz-intro').hide();
				self.$el.find('.forminator-pagination').removeClass('forminator-hidden');
				//initiate pagination
				var args = {
					totalSteps: self.$el.find('.forminator-pagination').length - 1, //subtract the last step with result
					step: 0,
					quiz: true
				};
				if ( self.settings.text_next ) {
					args.next_button = self.settings.text_next;
				}
				if ( self.settings.text_prev ) {
					args.prev_button = self.settings.text_prev;
				}
				if ( self.settings.submit_class ) {
					args.submitButtonClass = self.settings.submit_class;
				}

				$(self.element).forminatorFrontPagination(args);
			});

			if( 'end' !== lead_placement ) {
				this.$el.find('.forminator-submit-rightaway').on("click", function () {
					self.$el.submit();
					$(this).closest('.forminator-question').find('.forminator-submit-rightaway').addClass('forminator-has-been-disabled').attr('disabled', 'disabled');
				});
			}

			if( self.settings.hasLeads ) {
				if( 'beginning' === lead_placement ) {
					self.$el.css({
						'height': 0,
						'opacity': 0,
						'overflow': 'hidden',
						'visibility': 'hidden',
						'pointer-events': 'none',
						'margin': 0,
						'padding': 0,
						'border': 0
					});
				}
				if( 'end' === lead_placement ) {
					self.$el.closest('div').find('#forminator-module-' + self.settings.leads_id ).css({
						'height': 0,
						'opacity': 0,
						'overflow': 'hidden',
						'visibility': 'hidden',
						'pointer-events': 'none',
						'margin': 0,
						'padding': 0,
						'border': 0
					});
					$('#forminator-quiz-leads-' + quiz_id + ' .forminator-lead-form-skip' ).hide();
				}
			}

			this.$el.on('click', '.forminator-social--icon a', function (e) {
				e.preventDefault();
				var social        = $(this).data('social'),
				    url           = $(this).closest('.forminator-social--icons').data('url'),
				    message       = $(this).closest('.forminator-social--icons').data('message'),
				    message       = encodeURIComponent(message),
					 social_shares = {
						'facebook': 'https://www.facebook.com/sharer/sharer.php?u=' + url + '&quote=' + message,
						'twitter': 'https://twitter.com/intent/tweet?&url=' + url + '&text=' + message,
						'google': 'https://plus.google.com/share?url=' + url,
						'linkedin': 'https://www.linkedin.com/shareArticle?mini=true&url=' + url + '&title=' + message
					};

				if (social_shares[social] !== undefined) {
					var newwindow = window.open(social_shares[social], social, 'height=' + $(window).height() + ',width=' + $(window).width());
					if (window.focus) {
						newwindow.focus();
					}
					return false;
				}
			});

			this.$el.on('change', '.forminator-answer input', function (e) {
				var paginated      = !!$( this ).closest('.forminator-pagination').length,
					parent         = paginated ? $( this ).closest('.forminator-pagination') : self.$el,
					count          = parent.find('.forminator-answer input:checked').length,
				    amount_answers = parent.find('.forminator-question').length,
				    parentQuestion = $( this ).closest( '.forminator-question' ),
					isMultiChoice  = parentQuestion.data( 'multichoice' )
					;

				self.$el.find('.forminator-button:not(.forminator-button-back)').each(function () {
					var disabled = count < amount_answers;
					$( this ).prop('disabled', disabled);
					if ( paginated ) {
						if ( disabled ) {
							$( this ).addClass('forminator-disabled');
						} else {
							$( this ).removeClass('forminator-disabled');
						}
					}
				});

				// If multichoice is false, uncheck other options
				if( this.checked && false === isMultiChoice ) {
					parentQuestion
					.find( '.forminator-answer' )
					.not( $( this ).parent( '.forminator-answer' ) )
					.each( function( i, el ){
						$( el ).find( '> input' ).prop( 'checked', false );
					});
				}

			});
		},

		small_form: function () {

			var form      = $( this.element ),
				formWidth = form.width()
				;

			if ( 783 < Math.max( document.documentElement.clientWidth, window.innerWidth || 0 ) ) {

				if ( form.hasClass( 'forminator-size--small' ) ) {

					if ( 480 < formWidth ) {
						form.removeClass( 'forminator-size--small' );
					}
				} else {
					var hasHustle = form.closest('.hustle-content');

					if ( form.is(":visible") && 480 >= formWidth && ! hasHustle.length ) {
						form.addClass( 'forminator-size--small' );
					}
				}
			}
		},

		init_intlTelInput_validation: function ( form_selector ) {

			var form        = $( form_selector ),
				is_material = form.is('.forminator-design--material'),
				fields      = form.find('.forminator-field--phone');

			if ( ! form.is('form') ) {
				// For cloning phone fields inside group fields.
				is_material = form.closest('form').is('.forminator-design--material');
			}

			fields.each(function () {

				// Initialize intlTelInput plugin on each field with "format check" enabled and
				// set to check either "international" or "standard" phones.
				var self              = this,
					is_national_phone = $(this).data('national_mode'),
					country           = $(this).data('country'),
					validation        = $(this).data('validation'),
					iti               = window.intlTelInput.getInstance(self);

				if ('undefined' !== typeof (is_national_phone)) {

					if (is_material) {
						$(this).unwrap('.forminator-input--wrap');
					}

					// If it's already intialised then first destroy it and then reinit.
					if ( iti ) {
						iti.destroy();
					}

					var args = {
						nationalMode: ('enabled' === is_national_phone) ? true : false,
						initialCountry: 'undefined' !== typeof ( country ) ? country : 'us',
						validationNumberTypes: null,
						loadUtils: () => import(window.ForminatorFront.cform.intlTelInput_utils_script),
						strictMode: true,
					};

					if ( 'undefined' !== typeof ( validation ) && 'standard' === validation ) {
						args.allowDropdown  = false;
					}
					// stop from removing country code.
					if ( 'undefined' !== typeof ( validation ) && 'international' === validation ) {
						args.autoHideDialCode = false;
						args.separateDialCode = true;
					}

					var iti = window.intlTelInput(self, args);

					if ( 'undefined' !== typeof ( validation ) && 'standard' === validation ) {
						// Reset country to default if changed and invalid previously.
						$( this ).on( 'blur', function() {
							if ( '' === $( self ).val() ) {
								iti.setCountry( country );
								form.validate().element( $( self ) );
							}
						});
					}

					if ( ! is_material ) {
						$(this).closest( '.forminator-field' ).find( 'div.iti' ).addClass( 'forminator-phone' );
					} else {
						$(this).closest( '.forminator-field' ).find( 'div.iti' ).addClass( 'forminator-input-with-phone' );

						if ( $(this).closest( '.forminator-field' ).find( 'div.iti' ).hasClass( 'iti--allow-dropdown' ) ) {
							$(this).closest( '.forminator-field' ).find( '.forminator-label' ).addClass( 'iti--allow-dropdown' );
						}
					}

					// intlTelInput plugin adds a markup that's not compatible with 'material' theme when 'allowDropdown' is true (default).
					// If we're going to allow users to disable the dropdown, this should be adjusted accordingly.
					if (is_material) {
						//$(this).closest('.intl-tel-input.allow-dropdown').addClass('forminator-phone-intl').removeClass('intl-tel-input');
						//$(this).wrap('<div class="forminator-input--wrap"></div>');

						// Wrap Element
						if (!$(this).parent().hasClass('forminator-input--wrap')) {
							$(this).wrap('<div class="forminator-input--wrap"></div>');
						}
					}
				}
			});

		},

		reint_intlTelInput: function () {

			var self = this;
			self.$el.on( 'after:forminator:form:submit', function (e, data) {
				self.init_intlTelInput_validation( self.forminator_selector );
			} );
		},

		init_fui: function ( form_selector ) {

			var form        = $( form_selector ),
				input       = form.find( '.forminator-input' ),
				textarea    = form.find( '.forminator-textarea' ),
				select2     = form.find( '.forminator-select2' ),
				multiselect = form.find( '.forminator-multiselect' ),
				stripe		= form.find( '.forminator-stripe-element' ),
				slider		= form.find( '.forminator-slider' ),
				rating      = form.find( '.forminator-rating' ),
				checkbox	= form.find( '.forminator-checkbox' ),
				radio		= form.find( '.forminator-radio' )
				;

			var isDefault  = ( form.attr( 'data-design' ) === 'default' ),
				isBold     = ( form.attr( 'data-design' ) === 'bold' ),
				isFlat     = ( form.attr( 'data-design' ) === 'flat' ),
				isMaterial = ( form.attr( 'data-design' ) === 'material' )
				;

			if ( input.length ) {
				input.each( function() {
					FUI.inputStates( this );
				});
			}

			if ( checkbox.length ) {
				checkbox.each( function() {
					FUI.checkboxStates( this );
				});
			}

			if ( radio.length ) {
				radio.each( function() {
					FUI.radioStates( this );
				});
			}

			if ( textarea.length ) {
				textarea.each( function() {
					FUI.textareaStates( this );
				});
			}

			if ( 'function' === typeof FUI.select2 ) {
				FUI.select2( select2.length );
			}

			if ( 'function' === typeof FUI.slider ) {
				FUI.slider();
			}

			if ( multiselect.length ) {
				FUI.multiSelectStates( multiselect );
			}

			if ( rating.length && 'function' === typeof FUI.rating ) {
				FUI.rating( rating );
			}

			if ( form.hasClass( 'forminator-design--material' ) ) {
				if ( input.length ) {
					input.each( function() {
						FUI.inputMaterial( this );
					});
				}

				if ( textarea.length ) {
					textarea.each( function() {
						FUI.textareaMaterial( this );
					});
				}

				if ( stripe.length ) {
					stripe.each( function() {
						if ($(this).hasClass('forminator-stripe-payment-element')) {
							return; // Skip to the next iteration
						}

						var field = $(this).closest('.forminator-field');
						var label = field.find('.forminator-label');

						if (label.length) {
							field.addClass('forminator-stripe-floating');
							// Add floating class
							label.addClass('forminator-floating--input');
						}
					});
				}
			}
		},

		responsive_captcha: function ( form_selector ) {
			$( form_selector ).find('.forminator-g-recaptcha').each(function () {
				var badge = $(this).data('badge'); // eslint-disable-line
				if ($(this).is(':visible') && 'inline' === badge ) {
					var width = $(this).parent().width(),
					    scale = 1;
					if (width < 302) {
						scale = width / 302;
					}
					$(this).css('transform', 'scale(' + scale + ')');
					$(this).css('-webkit-transform', 'scale(' + scale + ')');
					$(this).css('transform-origin', '0 0');
					$(this).css('-webkit-transform-origin', '0 0');
				}
			});
		},

		init_pagination: function ( form_selector ) {
			var self      = this,
			    num_pages = $( form_selector ).find(".forminator-pagination").length,
			    hash      = window.location.hash,
			    hashStep  = false,
			    step      = 0;

			if (num_pages > 0) {
				//find from hash
				if (typeof hash !== "undefined" && hash.indexOf('step-') >= 0) {
					hashStep = true;
					step     = hash.substr(6, 8);
				}

				$(this.element).forminatorFrontPagination({
					totalSteps: num_pages,
					hashStep: hashStep,
					step: step,
					inline_validation: self.settings.inline_validation,
					submitButtonClass: self.settings.submit_button_class
				});
			}
		},

		activate_field: function () {

			var form     = $( this.element );
			var input    = form.find( '.forminator-input' );
			var textarea = form.find( '.forminator-textarea' );

			function classFilled( el ) {

				var element       = $( el );
				var elementValue  = element.val().trim();
				var elementField  = element.closest( '.forminator-field' );
				var elementAnswer = element.closest( '.forminator-poll--answer' );

				var filledClass = 'forminator-is_filled';

				if ( '' !== elementValue ) {
					elementField.addClass( filledClass );
					elementAnswer.addClass( filledClass );
				} else {
					elementField.removeClass( filledClass );
					elementAnswer.removeClass( filledClass );
				}

				element.change( function( e ) {

					if ( '' !== elementValue ) {
						elementField.addClass( filledClass );
						elementAnswer.addClass( filledClass );
					} else {
						elementField.removeClass( filledClass );
						elementAnswer.removeClass( filledClass );
					}

					e.stopPropagation();

				});
			}

			function classHover( el ) {

				var element       = $( el );
				var elementField  = element.closest( '.forminator-field' );
				var elementAnswer = element.closest( '.forminator-poll--answer' );

				var hoverClass = 'forminator-is_hover';

				element.on( 'mouseover', function( e ) {
					elementField.addClass( hoverClass );
					elementAnswer.addClass( hoverClass );
					e.stopPropagation();
				}).on( 'mouseout', function( e ) {
					elementField.removeClass( hoverClass );
					elementAnswer.removeClass( hoverClass );
					e.stopPropagation();
				});
			}

			function classActive( el ) {

				var element       = $( el );
				var elementField  = element.closest( '.forminator-field' );
				var elementAnswer = element.closest( '.forminator-poll--answer' );

				var activeClass = 'forminator-is_active';

				element.focus( function( e ) {
					elementField.addClass( activeClass );
					elementAnswer.addClass( activeClass );
					e.stopPropagation();
				}).blur( function( e ) {
					elementField.removeClass( activeClass );
					elementAnswer.removeClass( activeClass );
					e.stopPropagation();
				});
			}

			function classError( el ) {

				var element       = $( el );
				var elementValue  = element.val().trim();
				var elementField  = element.closest( '.forminator-field' );
				var elementTime   = element.attr( 'data-field' );

				var timePicker = element.closest( '.forminator-timepicker' );
				var timeColumn = timePicker.parent();

				var errorField = elementField.find( '.forminator-error-message' );

				var errorClass = 'forminator-has_error';

				element.on( 'load change keyup keydown', function( e ) {

					if ( 'undefined' !== typeof elementTime && false !== elementTime ) {

						if ( 'hours' === element.data( 'field' ) ) {

							var hoursError = timeColumn.find( '.forminator-error-message[data-error-field="hours"]' );

							if ( '' !== elementValue && 0 !== hoursError.length ) {
								hoursError.remove();
							}
						}

						if ( 'minutes' === element.data( 'field' ) ) {

							var minutesError = timeColumn.find( '.forminator-error-message[data-error-field="minutes"]' );

							if ( '' !== elementValue && 0 !== minutesError.length ) {
								minutesError.remove();
							}
						}
					} else {

						if ( '' !== elementValue && errorField.text() ) {
							errorField.remove();
							elementField.removeClass( errorClass );
						}
					}

					e.stopPropagation();

				});
			}

			if ( input.length ) {

				input.each( function() {
					//classFilled( this );
					//classHover( this );
					//classActive( this );
					classError( this );
				});
			}

			if ( textarea.length ) {

				textarea.each( function() {
					//classFilled( this );
					//classHover( this );
					//classActive( this );
					classError( this );
				});
			}

			form.find('select.forminator-select2 + .forminator-select').each(function () {

				var $select = $(this);

				// Set field active class on hover
				$select.on('mouseover', function (e) {
					e.stopPropagation();
					$(this).closest('.forminator-field').addClass('forminator-is_hover');

				}).on('mouseout', function (e) {
					e.stopPropagation();
					$(this).closest('.forminator-field').removeClass('forminator-is_hover');

				});

				// Set field active class on focus
				$select.on('click', function (e) {
					e.stopPropagation();
					checkSelectActive();
					if ($select.hasClass('select2-container--open')) {
						$(this).closest('.forminator-field').addClass('forminator-is_active');
					} else {
						$(this).closest('.forminator-field').removeClass('forminator-is_active');
					}

				});


			});

			function checkSelectActive() {
				if (form.find('.select2-container').hasClass('select2-container--open')) {
					setTimeout(checkSelectActive, 300);
				} else {
					form.find('.select2-container').closest('.forminator-field').removeClass('forminator-is_active');
				}
			}
		},

		field_counter: function ( form_selector ) {
			var form = $( form_selector ),
				submit_button = form.find('.forminator-button-submit');

			form.find('.forminator-input, .forminator-input input, .forminator-textarea').each(function () {
				var $input   = $(this),
				    numwords = 0,
				    count    = 0;

				$input.on('keydown', function (e) {
					if ( ! $(this).hasClass('forminator-textarea') && e.keyCode === 13 ) {
						e.preventDefault();
						if ( submit_button.is(":visible") ) {
							submit_button.trigger('click');
						}
						return false;
					}
				});

				$input.on('change keyup keydown', function (e) {
					e.stopPropagation();
					var $field = $(this).closest('.forminator-col'),
					    $limit = $field.find('.forminator-description span')
					;

					if ($limit.length) {
						var textLimit = parseInt( $limit.data('limit') );
						if (textLimit) {
							if ($limit.data('type') !== "words") {
								if ( $limit.data( 'editor' ) === 1 ) {
									const content = $( this )
											.val()
											.replace( /<[^>]*>/g, '' ),
										content_text = $( '<textarea/>' )
											.html( content )
											.text();
									count = content_text.length;
									const isCtrlPressed =
											e.ctrlKey || e.metaKey; // Handle macOS Command key (metaKey).
									const isSpecialKey =
										[ 37, 38, 39, 40, 8, 46 ].indexOf(
											e.keyCode
										) !== -1;
									// Allow to delete and backspace when limit is reached.
									if (
										count >= textLimit &&
										! isCtrlPressed &&
										! isSpecialKey
									) {
										e.preventDefault();
									}
								} else {
									count = $(this).val().length;
								}
							} else {
								var fieldVal = sanitize_text_field( $(this).val() ),
									field_value = fieldVal.replace( /<[^>]*>/g, '' );
								count = field_value.trim().split(/\s+/).length;

								// Prevent additional words from being added when limit is reached.
								numwords = field_value.trim().split(/\s+/).length;
								if ( numwords >= textLimit ) {
									// Allow to delete and backspace when limit is reached.
									if( e.which === 32 ) {
										e.preventDefault();
									}
								}
							}
							$limit.text(count + ' / ' + textLimit);
						}
					}
				});

			});
		},

		field_number: function ( form_selector ) {
			// var form = $(this.element);
			// form.find('input[type=number]').on('change keyup', function () {
			// 	if( ! $(this).val().match(/^\d+$/) ){
			// 		var sanitized = $(this).val().replace(/[^0-9]/g, '');
			// 		$(this).val(sanitized);
			// 	}
			// });
			var form = $( form_selector );
			form.find('input[type=number]').each(function () {
				$(this).keypress(function (e) {
					var i;
					var allowed = [44, 45, 46];
					var key     = e.which;

					for (i = 48; i < 58; i++) {
						allowed.push(i);
					}

					if (!(allowed.indexOf(key) >= 0)) {
						e.preventDefault();
					}
				});
			});

			form.find('.forminator-number--field, .forminator-currency, .forminator-calculation').each(function () {
				var inputType = $( this ).attr( 'type' );
				if ( 'number' === inputType ) {
					var decimals = $( this ).data( 'decimals' );
					$( this ).on('change', function ( e ) {
						this.value = this.value !== '' ? parseFloat( this.value ).toFixed( decimals ) : '';
					});
					$( this ).trigger( 'change' );
				}
				/*
				* If you need to retrieve the formatted (masked) value, you can use something like this:
				* $element.inputmask({'autoUnmask' : false});
				* var value = $element.val();
				* $element.inputmask({'autoUnmask' : true});
				*/
				$( this ).inputmask({
					'alias': 'decimal',
					'rightAlign': false,
					'digitsOptional': false,
					'showMaskOnHover': false,
					'autoUnmask' : true, // Automatically unmask the value when retrieved - this prevents the "Maximum call stack size exceeded" console error that happens in some forms that contain number/calculation fields with localized masks.
					'removeMaskOnSubmit': true,
				});
			});

		},

		field_time: function () {
			var self = this;
			$('.forminator-input-time').on('input', function (e) {
				var $this = $(this),
				    value = $this.val()
				;

				// Allow only 2 digits for time fields
				if (value && value.length >= 2) {
					$this.val(value.substr(0, 2));
				}
			});

			// Apply time limits.
			this.$el.find( '.forminator-timepicker' ).each( function( i, el ) {
				var $tp   = $( el ),
					start = $tp.data( 'start-limit' ),
					end   = $tp.data( 'end-limit' )
				;

				if ( 'undefined' !== typeof start && 'undefined' !== typeof end ) {
					var hourSelect = $tp.find( '.time-hours' ),
						initHours  = hourSelect.html()
					;

					// Reset right away.
					self.resetTimePicker( $tp, start, end );
					// Reset onchange.
					$tp.find( '.time-ampm' ).on( 'change', function() {
						hourSelect.val('');
						hourSelect.html( initHours );
						self.resetTimePicker( $tp, start, end );
						setTimeout(
							function() {
								$tp.find( '.forminator-field' ).removeClass( 'forminator-has_error' );
							},
							10
						);
					});
				}
			});
		},

		// Remove hour options that are outside the limits.
		resetTimePicker: function ( timePicker, start, end ) {
			var meridiem = timePicker.find( '.time-ampm' ),
				[ startTime, startModifier ] = start.split(' '),
				[ startHour, startMinute ] = startTime.split(':'),
				startHour = parseInt( startHour ),
				[ endTime, endModifier ] = end.split(' '),
				[ endHour, endMinute ] = endTime.split(':'),
				endHour = parseInt( endHour )
				;

			if ( startModifier === endModifier ) {
				meridiem.find( 'option[value!="' + endModifier + '"]' ).remove();
			}

			timePicker.find( '.time-hours' ).children().each( function( optionIndex, optionEl ) {
				var optionValue = parseInt( optionEl.value );

				if (
					'' !== optionValue &&
					( optionValue < startHour || ( 0 !== startHour && 12 === optionValue ) ) &&
					meridiem.val() === startModifier
				) {
					optionEl.remove();
				}

				if (
					'' !== optionValue &&
					optionValue > endHour &&
					12 !== optionValue &&
					meridiem.val() === endModifier
				) {
					optionEl.remove();
				}
			});
		},

		init_login_2FA: function () {
			var self = this;
			this.two_factor_providers( 'totp' );
			$('body').on('click', '.forminator-2fa-link', function () {
				self.$el.find('#login_error').remove();
				self.$el.find('.notification').empty();
				var slug = $(this).data('slug');
				self.two_factor_providers( slug );
				if ('fallback-email' === slug) {
					self.resend_code();
				}
			});
			this.$el.find('.wpdef-2fa-email-resend input').on('click', function () {
				self.resend_code();
			});
		},
		two_factor_providers: function ( slug ) {
			var self = this;
			self.$el.find('.forminator-authentication-box').hide();
			self.$el.find('.forminator-authentication-box input').attr( 'disabled', true );
			self.$el.find( '#forminator-2fa-' + slug ).show();
			self.$el.find( '#forminator-2fa-' + slug + ' input' ).attr( 'disabled', false );
			if ( self.$el.find('.forminator-2fa-link').length > 0 ) {
				self.$el.find('.forminator-2fa-link').hide();
				self.$el.find('.forminator-2fa-link:not(#forminator-2fa-link-'+ slug +')').each(function() {
					self.$el.find('.forminator-auth-method').val( slug );
					$( this ).find('input').attr( 'disabled', false );
					$( this ).show();
				});
			}
		},

		// Logic for FallbackEmail method.
		resend_code: function () {
			// Work with the button 'Resen Code'.
			var self  = this;
			var that  = $('input[name="button_resend_code"]');
			var token = $('.forminator-auth-token');
			let data = {
				action: 'forminator_2fa_fallback_email',
				data: JSON.stringify({
					'token': token
				})
			};
			$.ajax({
				type: 'POST',
				url: window.ForminatorFront.ajaxUrl,
				data: data,
				beforeSend: function () {
					that.attr('disabled', 'disabled');
					$('.def-ajaxloader').show();
				},
				success: function (data) {
					that.removeAttr('disabled');
					$('.def-ajaxloader').hide();
					$('.notification').text(data.data.message);
				}
			})
		},

		material_field: function () {
			/*
			var form = $(this.element);
			if (form.is('.forminator-design--material')) {
				var $input    = form.find('.forminator-input--wrap'),
				    $textarea = form.find('.forminator-textarea--wrap'),
				    $date     = form.find('.forminator-date'),
				    $product  = form.find('.forminator-product');

				var $navigation = form.find('.forminator-pagination--nav'),
				    $navitem    = $navigation.find('li');

				$('<span class="forminator-nav-border"></span>').insertAfter($navitem);

				$input.prev('.forminator-field--label').addClass('forminator-floating--input');
				$input.closest('.forminator-phone-intl').prev('.forminator-field--label').addClass('forminator-floating--input');
				$textarea.prev('.forminator-field--label').addClass('forminator-floating--textarea');

				if ($date.hasClass('forminator-has_icon')) {
					$date.prev('.forminator-field--label').addClass('forminator-floating--date');
				} else {
					$date.prev('.forminator-field--label').addClass('forminator-floating--input');
				}
			}
			*/
		},

		toggle_file_input: function() {

			var $form = $( this.element );

			$form.find( '.forminator-file-upload' ).each( function() {

				var $field = $( this );
				var $input = $field.find( 'input' );
				var $remove = $field.find( '.forminator-button-delete' );

				// Toggle remove button depend on input value
				if ( '' !== $input.val() ) {
					$remove.show(); // Show remove button
				} else {
					$remove.hide(); // Hide remove button
				}
			});
		},

		upload_field: function ( form_selector ) {
			var self = this,
			    form = $( form_selector )
			;
			// Toggle file remove button
			this.toggle_file_input();

			// Handle remove file button click
			form.find( '.forminator-button-delete' ).on('click', function (e) {

				e.preventDefault();

				var $self  = $( this ),
				    $input = $self.siblings('input'),
				    $label = $self.closest( '.forminator-file-upload' ).find('> span')
					;

				// Cleanup
				$input.val('');
				$label.html( $label.data( 'empty-text' ) );
				$self.hide();
				// Reset related conditional fields.
				$input.trigger( 'forminator.change', 'forminator_emulate_trigger' );

			});

			form.find( '.forminator-input-file, .forminator-input-file-required' ).on('change', function () {
				var $nameLabel = $(this).closest( '.forminator-file-upload' ).find( '> span' ),
					vals = $(this).val(),
					val  = vals.length ? vals.split('\\').pop() : ''
				;

				$nameLabel.text(val);

				self.toggle_file_input();
			});

			form.find( '.forminator-button-upload' ).off();
			form.find( '.forminator-button-upload' ).on( 'click', function (e) {
				e.preventDefault();

				var $id        = $(this).attr('data-id'),
				    $target    = form.find('input#' + $id)
					;

				$target.trigger('click');
			});

			form.find( '.forminator-input-file, .forminator-input-file-required' ).on('change', function (e) {

				e.preventDefault();

				var $file   = $(this)[0].files.length,
				    $remove = $(this).find('.forminator-button-delete');

				if ($file === 0) {
					$remove.hide();
				} else {
					$remove.show();
				}

			});
		},

        // Remove duplicate fields created by other plugins/themes
		maybeRemoveDuplicateFields: function ( form_selector ) {
            var form = $( form_selector );

            // Check for Neira Lite theme
            if ( $( document ).find( "link[id='neira-lite-style-css']" ).length ) {
                var duplicateSelect  = form.find( '.forminator-select-container' ).next( '.chosen-container' ),
                    duplicateSelect2 = form.find( 'select.forminator-select2 + .forminator-select' ).next( '.chosen-container' ),
                    duplicateAddress = form.find( '.forminator-select' ).next( '.chosen-container' )
                ;

                if ( 0 !== duplicateSelect.length ) {
                    duplicateSelect.remove();
                }
                if ( 0 !== duplicateSelect2.length ) {
                    duplicateSelect2.remove();
                }
                if ( 0 !== duplicateAddress.length ) {
                    duplicateAddress.remove();
                }
            }
		},

		renderCaptcha: function (captcha_field) {
			var self = this;
			//render captcha only if not rendered
			if (typeof $(captcha_field).data('forminator-recapchta-widget') === 'undefined') {
				var size = $(captcha_field).data('size'),
				    data = {
					    sitekey: $(captcha_field).data('sitekey'),
					    theme: $(captcha_field).data('theme'),
					    size: size
				    };

				if (size === 'invisible') {
					data.badge    = $(captcha_field).data('badge');
					data.callback = function (token) {
						$(self.element).trigger('submit.frontSubmit');
					};
				} else {
					data.callback = function () {
						$(captcha_field).parent( '.forminator-col' )
							.removeClass( 'forminator-has_error' )
							.remove( '.forminator-error-message' );
					};
				}

				if (data.sitekey !== "") {
					// noinspection Annotator
					var widget = window.grecaptcha.render(captcha_field, data);
					// mark as rendered
					$(captcha_field).data('forminator-recapchta-widget', widget);
					this.addCaptchaAria( captcha_field );
					this.responsive_captcha();
				}
			}
		},

		renderHcaptcha: function ( captcha_field ) {
			var self = this;
			//render hcaptcha only if not rendered
			if (typeof $( captcha_field ).data( 'forminator-hcaptcha-widget' ) === 'undefined') {
				var size = $( captcha_field ).data( 'size' ),
				    data = {
					    sitekey: $( captcha_field ).data( 'sitekey' ),
					    theme: $( captcha_field ).data( 'theme' ),
					    size: size
				    };

				if ( size === 'invisible' ) {
					data.callback = function ( token ) {
						$( self.element ).trigger( 'submit.frontSubmit' );
					};
				} else {
					data.callback = function () {
						$( captcha_field ).parent( '.forminator-col' )
							.removeClass( 'forminator-has_error' )
							.remove( '.forminator-error-message' );
					};
				}

				if ( data.sitekey !== "" ) {
					// noinspection Annotator
					var widgetId = hcaptcha.render( captcha_field, data );
					// mark as rendered
					$( captcha_field ).data( 'forminator-hcaptcha-widget', widgetId );
					// this.addCaptchaAria( captcha_field );
					// this.responsive_captcha();
				}
			}
		},

		renderTurnstileCaptcha: function ( captcha_field ) {
			var self = this;
			//render captcha only if not rendered
			if (typeof $( captcha_field ).data( 'forminator-turnstile-widget' ) === 'undefined') {
				var sitekey = $( captcha_field ).data( 'sitekey' ),
					data = {
						'response-field-name': 'forminator-turnstile-response',
						callback: function (token, data, test) {
							$( captcha_field ).parent( '.forminator-col' )
								.removeClass( 'forminator-has_error' )
								.remove( '.forminator-error-message' );
						}
					};

				if ( sitekey !== "" ) {
					// noinspection Annotator
					var widgetId = turnstile.render( captcha_field, data );
					// mark as rendered
					$( captcha_field ).data( 'forminator-turnstile-widget', widgetId );
				}
			}
		},

		addCaptchaAria: function ( captcha_field ) {
			var gRecaptchaResponse = $( captcha_field ).find( '.g-recaptcha-response' ),
				gRecaptcha = $( captcha_field ).find( '>div' );

			if ( 0 !== gRecaptchaResponse.length ) {
				gRecaptchaResponse.attr( "aria-hidden", "true" );
				gRecaptchaResponse.attr( "aria-label", "do not use" );
				gRecaptchaResponse.attr( "aria-readonly", "true" );
			}
			if ( 0 !== gRecaptcha.length ) {
				gRecaptcha.css( 'z-index', 99 );
			}
		},

		hide: function () {
			this.$el.hide();
		},
		/**
		 * Return JSON object if possible
		 *
		 * We tried our best here
		 * if there is an error/exception, it will return empty object/array
		 *
		 * @param string
		 * @param type ('array'/'object')
		 */
		maybeParseStringToJson: function (string, type) {
			var object = {};
			// already object
			if (typeof string === 'object') {
				return string;
			}

			if (type === 'object') {
				string = '{' + string.trim() + '}';
			} else if (type === 'array') {
				string = '[' + string.trim() + ']';
			} else {
				return {};
			}

			try {
				// remove trailing comma, duh
				/**
				 * find `,`, after which there is no any new attribute, object or array.
				 * New attribute could start either with quotes (" or ') or with any word-character (\w).
				 * New object could start only with character {.
				 * New array could start only with character [.
				 * New attribute, object or array could be placed after a bunch of space-like symbols (\s).
				 *
				 * Feel free to hack this regex if you got better idea
				 * @type {RegExp}
				 */
				var trailingCommaRegex = /\,(?!\s*?[\{\[\"\'\w])/g;
				string                 = string.replace(trailingCommaRegex, '');

				object = JSON.parse(string);
			} catch (e) {
				console.error(e.message);
				if (type === 'object') {
					object = {};
				} else if (type === 'array') {
					object = [];
				}
			}

			return object;

		},

		/**
		 * Render Stripe once it's available
		 *
		 * @param string
		 * @param type ('array'/'object')
		 */
		renderStripe: function( form, stripe_payment, stripeLoadCounter = 0 ) {
			var self = this;

			setTimeout( function() {
				stripeLoadCounter++;

				if ( 'undefined' !== typeof Stripe ) {

					let options= {
						type: 'stripe',
						paymentEl: stripe_payment,
						paymentRequireSsl: form.settings.payment_require_ssl,
						generalMessages: form.settings.general_messages,
						has_loader: form.settings.has_loader,
						loader_label: form.settings.loader_label,
						stripe_depends: form.settings.stripe_depends,
					};

					if ( stripe_payment.data('is-ocs') ) {
						$( form.element ).forminatorFrontStripe( options );
					} else {
						$( form.element ).forminatorFrontPayment( options );
					}

				// Retry checking for 30 seconds
				} else if ( stripeLoadCounter < 300 ) {
					self.renderStripe( form, stripe_payment, stripeLoadCounter );
				} else {
					console.error( 'Failed to load Stripe.' );
				}
			}, 100 );
		},

        // Enable save draft button once a change is made
		maybe_enable_save_draft: function ( $parent, $saveDraft, initialData ) {
			var changedData = $parent.find(':input').serializeArray(),
				hasChanged	= false,
				hasSig		= $parent.find( '.forminator-field-signature' ).length ? true : false
				;

			// Remove signature field from changedData, will process later
			changedData = changedData.filter( function( val ) {
				return val.name.indexOf( 'ctlSignature' ) === -1 ;
			});

			initialData = JSON.stringify( initialData );
			changedData = JSON.stringify( changedData );

			// Check for field changes
			if ( initialData !== changedData ) {
				hasChanged = true;
			}

			// Check for signature change
			if ( hasSig && false === hasChanged ) {
				$parent.find( '.forminator-field-signature' ).each( function(e) {
					var sigPrefix = $( this ).find( '.signature-prefix' ).val();

					if (
						0 !== $( this ).find( '#ctlSignature' + sigPrefix + '_data' ).length &&
						'' !== $( this ).find( '#ctlSignature' + sigPrefix + '_data' ).val()
					) {
						hasChanged = true;
						return false;
					}
				});
			}

			if ( hasChanged ) {
				$saveDraft.removeClass( 'disabled' );
			} else {
				$saveDraft.addClass( 'disabled' );
			}
		},

		handleDiviPopup: function () {
			var self = this;
			if ( 'undefined' !== typeof DiviArea ) {
				DiviArea.addAction( 'show_area', function( area ) {
					setTimeout(
						function() {
							self.init();
							forminatorSignInit();
							forminatorSignatureResize();
						},
						100
					);
				});
			}
		},

		disableFields: function () {
			this.$el.addClass( 'forminator-fields-disabled' );
		},

        // Check if Complianz has added a blocker for reCaptcha.
		checkComplianzBlocker: function () {
			var complianzBlocker = this.$el.find( '.cmplz-blocked-content-container' );

			if ( complianzBlocker.length > 0 ) {
                var row = complianzBlocker.closest( '.forminator-row' );

				this.disableFields();
                row.insertBefore( this.$el.find( '.forminator-row' ).first() );
				row.css({ 'pointer-events': 'all', 'opacity': '1' });
				row.find( '*' ).css( 'pointer-events', 'all' );

                // For paginated.
                if ( row.closest( '.forminator-pagination--content' ).length > 0 ) {
                    row.closest( '.forminator-pagination--content' ).css({ 'pointer-events': 'all', 'opacity': '1' });
                    row.nextAll( '.forminator-row' ).css({ 'opacity': '0.5' });
                }

                // Reload window if accepted.
                $( 'body' ).on( 'click', '.cmplz-blocked-content-notice, .cmplz-accept', function() {
                    setTimeout(
                        function() {
                            window.location.reload();
                        },
                        50
                    );
                });
			}
		},
	});

	// A really lightweight plugin wrapper around the constructor,
	// preventing against multiple instantiations
	$.fn[pluginName] = function (options) {
		return this.each(function () {
			if (!$.data(this, pluginName)) {
				$.data(this, pluginName, new ForminatorFront(this, options));
			}
		});
	};

	// hook from wp_editor tinymce
	$(document).on('tinymce-editor-init', function (event, editor) {
		var editor_id = editor.id,
			$field = $('#' + editor_id ).closest('.forminator-col')
		;

		// Event listener to handle switching between Visual and Text tabs
		$( document ).on( 'click', '.wp-switch-editor', function () {
			setTimeout( function () {
				$field.find( '#' + editor_id ).trigger( 'change' );
			}, 100 ); // Small timeout to ensure editor is ready when switching
		} );

		// trigger editor change to save value to textarea,
		// default wp tinymce textarea update only triggered when submit
		var count  = 0;
		editor.on('change', function () {
			// only forminator
			if ( -1 !== editor_id.indexOf( 'forminator-field-textarea-' ) ) {
				editor.save();
				$field.find( '#' + editor_id ).trigger( 'change' );
			}

			if ( -1 !== editor_id.indexOf( 'forminator-field-post-content-' ) ) {
				editor.save();
				$field.find( '#' + editor_id ).trigger( 'change' );
			}
		});

		// Trigger onblur.
		editor.on( 'blur', function () {
			// only forminator
			if (
				-1 !== editor_id.indexOf( 'forminator-field-textarea-' ) ||
				-1 !== editor_id.indexOf( 'forminator-field-post-content-' )
			) {
				$field.find( '#' + editor_id ).valid();
			}
		});

		// Prevent typing when maximum characters/words is reached.
		editor.on( 'keydown', function ( e ) {
			let editor_id = editor.id,
				field = $( '#' + editor_id ).closest( '.forminator-col' ),
				limit = field.find( '.forminator-description span' ),
				content = editor.getContent().replace( /<[^>]*>/g, '' );
			if ( limit.length ) {
				const maxLength = parseInt( limit.data( 'limit' ) );
				if ( maxLength ) {
					content = $( '<div/>' ).html( content ).text();
					const isCtrlPressed = e.ctrlKey || e.metaKey; // Handle macOS Command key (metaKey).
					const isSpecialKey =
						[ 37, 38, 39, 40, 8, 46 ].indexOf( e.keyCode ) !== -1;
					if ( limit.data( 'type' ) !== 'words' ) {
						if (
							content.length >= maxLength &&
							! isCtrlPressed &&
							! isSpecialKey &&
							e.keyCode !== 13
						) {
							e.preventDefault(); // Prevent any further typing.
						}
					} else {
						const numberOfWords = content
							.trim()
							.split( /\s+/ ).length;
						if ( numberOfWords >= maxLength && e.which === 32 ) {
							e.preventDefault(); // Prevent any further typing.
						}
					}
				}
			}
		} );

		// Make the visual editor and html editor the same height
		if ( $( '#' + editor.id + '_ifr' ).is( ':visible' ) ) {
			$( '#' + editor.id + '_ifr' ).height( $( '#' + editor.id ).height() );
		}

		// Add aria-describedby.
		if ( -1 !== editor_id.indexOf( 'forminator' ) ) {
			$( '#' + editor_id ).closest( '.wp-editor-wrap' ).attr(
				'aria-describedby',
				editor_id + '-description'
			);
		}
	});

	$( document ).on( 'click', '.forminator-copy-btn', function( e ) {
		forminatorCopyTextToClipboard( $( this ).prev( '.forminator-draft-link' ).val() );
		if ( ! $( this ).hasClass( 'copied' ) ) {
			$( this ).addClass( 'copied' )
			$( this ).prepend( '&check;  ' );
		}
	} );

	// Copy: Async + Fallback
	// https://stackoverflow.com/a/30810322
	function forminatorFallbackCopyTextToClipboard( text ) {
		var textArea = document.createElement("textarea");
		textArea.value = text;

		// Avoid scrolling to bottom
		textArea.style.top = "0";
		textArea.style.left = "0";
		textArea.style.position = "fixed";

		document.body.appendChild(textArea);
		textArea.focus();
		textArea.select();

		try {
			var successful = document.execCommand('copy');
			var msg = successful ? 'successful' : 'unsuccessful';
			// console.log('Fallback: Copying text command was ' + msg);
		} catch (err) {
			// console.error('Fallback: Oops, unable to copy', err);
		}

		document.body.removeChild(textArea);
	}

	function forminatorCopyTextToClipboard (text ) {
		if (!navigator.clipboard) {
			forminatorFallbackCopyTextToClipboard(text);
			return;
		}
		navigator.clipboard.writeText(text).then(function() {
			// console.log('Async: Copying to clipboard was successful!');
		}, function(err) {
			// console.error('Async: Could not copy text: ', err);
		});
	}

	// Focus to nearest input when label is clicked
	function focus_to_nearest_input() {
		$( '.forminator-custom-form' ).find( '.forminator-label' ).on( 'click', function ( e ) {
			e.preventDefault();
			var fieldLabel = $( this );

			fieldLabel.next( '#' + fieldLabel.attr( 'for' ) ).focus();
		});
	}

	focus_to_nearest_input();
	$( document ).on( 'after.load.forminator', focus_to_nearest_input );

	$( document ).on( 'after.load.forminator', () => {
		forminator_render_captcha();
		forminator_render_hcaptcha();
		forminator_render_turnstile();
	} );

	// Elementor Popup show event
	jQuery( document ).on( 'elementor/popup/show', () => {
		forminator_render_captcha();
		forminator_render_hcaptcha();
		forminator_render_turnstile();
	} );

	/**
	 * Sanitize the user input string.
	 *
	 * @param {string} string
	 */
	function sanitize_text_field( string ) {
		if ( typeof string === 'string') {
			var str = String(string).replace(/<\/?[^>]+(>|$)/g, '');
			return str.trim();
		}

		return string;
	}

})(jQuery, window, document);

// noinspection JSUnusedGlobalSymbols
var forminator_render_turnstile = function () {
	jQuery('.forminator-turnstile').each(function () {
		// find closest form.
		var thisCaptcha = jQuery(this),
			form 		= thisCaptcha.closest('form');

		if ( form.length > 0 && '' === thisCaptcha.html() ) {
			window.setTimeout( function() {
				var forminatorFront = form.data( 'forminatorFront' );
				if ( typeof forminatorFront !== 'undefined' ) {
					forminatorFront.renderTurnstileCaptcha( thisCaptcha[0] );
				}
			}, 100 );
		}
	});
};

// noinspection JSUnusedGlobalSymbols
var forminator_render_captcha = function () {
	// TODO: avoid conflict with another plugins that provide recaptcha
	//  notify forminator front that grecaptcha has loaded and can be used
	jQuery('.forminator-g-recaptcha').each(function () {
		// find closest form
		var thisCaptcha = jQuery(this),
			form 		= thisCaptcha.closest('form');

		if ( form.length > 0 && '' === thisCaptcha.html() ) {
			window.setTimeout( function() {
				var forminatorFront = form.data( 'forminatorFront' );
				if ( typeof forminatorFront !== 'undefined' ) {
					forminatorFront.renderCaptcha( thisCaptcha[0] );
				}
			}, 100 );
		}
	});
};

// noinspection JSUnusedGlobalSymbols
var forminator_render_hcaptcha = function () {
	// TODO: avoid conflict with another plugins that provide hcaptcha
	//  notify forminator front that hcaptcha has loaded and can be used
	jQuery('.forminator-hcaptcha').each(function () {
		// find closest form
		var thisCaptcha = jQuery(this),
			form 		= thisCaptcha.closest('form');

		if ( form.length > 0 && '' === thisCaptcha.html() ) {
			window.setTimeout( function() {
				var forminatorFront = form.data( 'forminatorFront' );
				if ( typeof forminatorFront !== 'undefined' ) {
					forminatorFront.renderHcaptcha( thisCaptcha[0] );
				}
			}, 100 );
		}
	});
};

// Source: http://stackoverflow.com/questions/497790
var forminatorDateUtil = {
	month_number: function( v ) {
		var months_short = ['jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec'];
		var months_full = ['january', 'february', 'march', 'april', 'may', 'june', 'july', 'august', 'september', 'october', 'november', 'december'];
		if( v.constructor === Number ) {
			return v;
		}
		var n = NaN;
		if( v.constructor === String ) {
			v = v.toLowerCase();
			var index = months_short.indexOf( v );
			if( index === -1 ) {
				index = months_full.indexOf( v );
			}
			n = ( index === -1 ) ? NaN : index;
		}

		return n;
	},
    convert: function( d ) {
        // Converts the date in d to a date-object. The input can be:
        //   a date object: returned without modification
        //  an array      : Interpreted as [year,month,day]. NOTE: month is 0-11.
        //   a number     : Interpreted as number of milliseconds
        //                  since 1 Jan 1970 (a timestamp)
        //   a string     : Any format supported by the javascript engine, like
        //                  "YYYY/MM/DD", "MM/DD/YYYY", "Jan 31 2009" etc.
        //  an object     : Interpreted as an object with year, month and date
        //                  attributes.  **NOTE** month is 0-11.
        return (
            d.constructor === Date   ? d :
            d.constructor === Array  ? new Date( d[0], this.month_number( d[1] ), d[2] ) :
            jQuery.isNumeric( d )    ? new Date( 1 * d ) :
            d.constructor === Number ? new Date( d ) :
            d.constructor === String ? new Date( d ) :
            typeof d === "object"    ? new Date( d.year, this.month_number( d.month ), d.date ) :
            NaN
        );
    },
    compare: function( a, b ) {
        // Compare two dates (could be of any type supported by the convert
        // function above) and returns:
        //  -1 : if a < b
        //   0 : if a = b
        //   1 : if a > b
        // NaN : if a or b is an illegal date
        // NOTE: The code inside isFinite does an assignment (=).
        return (
            isFinite( a = this.convert( a ).valueOf() ) &&
            isFinite( b = this.convert( b ).valueOf() ) ?
            ( a > b ) - ( a < b ) :
            NaN
        );
    },
    inRange: function( d, start, end ) {
        // Checks if date in d is between dates in start and end.
        // Returns a boolean or NaN:
        //    true  : if d is between start and end (inclusive)
        //    false : if d is before start or after end
        //    NaN   : if one or more of the dates is illegal.
        // NOTE: The code inside isFinite does an assignment (=).
       return (
            isFinite( d = this.convert( d ).valueOf() ) &&
            isFinite( start = this.convert( start ).valueOf() ) &&
            isFinite( end = this.convert( end ).valueOf() ) ?
            start <= d && d <= end :
            NaN
        );
    },

    diffInDays: function( d1, d2 ) {
		d1 = this.convert( d1 );
		d2 = this.convert( d2 );
		if( typeof d1.getMonth !== 'function' || typeof d2.getMonth !== 'function' ) {
			return NaN;
		}

	    var t2 = d2.getTime();
	    var t1 = d1.getTime();

	    return parseFloat((t2-t1)/(24*3600*1000));
	},

	diffInWeeks: function( d1, d2 ) {
		d1 = this.convert( d1 );
		d2 = this.convert( d2 );
		if( typeof d1.getMonth !== 'function' || typeof d2.getMonth !== 'function' ) {
			return NaN;
		}

	    var t2 = d2.getTime();
	    var t1 = d1.getTime();

	    return parseInt((t2-t1)/(24*3600*1000*7));
	},

	diffInMonths: function( d1, d2 ) {
		d1 = this.convert( d1 );
		d2 = this.convert( d2 );
		if( typeof d1.getMonth !== 'function' || typeof d2.getMonth !== 'function' ) {
			return NaN;
		}

	    var d1Y = d1.getFullYear();
	    var d2Y = d2.getFullYear();
	    var d1M = d1.getMonth();
	    var d2M = d2.getMonth();

	    return (d2M+12*d2Y)-(d1M+12*d1Y);
	},

	diffInYears: function( d1, d2 ) {
		d1 = this.convert( d1 );
		d2 = this.convert( d2 );
		if( typeof d1.getMonth !== 'function' || typeof d2.getMonth !== 'function' ) {
			return NaN;
		}

	    return d2.getFullYear()-d1.getFullYear();
	},
};
