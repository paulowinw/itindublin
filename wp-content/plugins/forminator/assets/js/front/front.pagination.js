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
	var pluginName = "forminatorFrontPagination",
		defaults = {
			totalSteps: 0,
			step: 0,
			hashStep: 0,
			inline_validation: false
		};

	// The actual plugin constructor
	function ForminatorFrontPagination(element, options) {
		this.element = $(element);
		this.$el = this.element;
		this.totalSteps = 0;
		this.totalActiveSteps = 0; // Exclude hidden steps
		this.step = 0;
		this.actualStep = 0; // Exclude hidden steps
		this.finished = false;
		this.hashStep = false;
		this.next_button_txt = '';
		this.prev_button_txt = '';
		this.custom_label = [];
		this.form_id = 0;
		this.element = '';

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
	$.extend(ForminatorFrontPagination.prototype, {
		init: function () {
			var self = this;
			var draftPage = !! this.$el.data( 'draft-page' ) ? this.$el.data( 'draft-page' ) : 0;

			// Detect instant preview mode
			this.instantPreview = this.$el.closest( '#forminator-instant-preview' ).length > 0;

			this.next_button = this.settings.next_button ? this.settings.next_button : window.ForminatorFront.cform.pagination_next;
			this.prev_button = this.settings.prev_button ? this.settings.prev_button : window.ForminatorFront.cform.pagination_prev;

			if (this.$el.find('input[name=form_id]').length > 0) {
				this.form_id = this.$el.find('input[name=form_id]').val();
			}

			this.$form = this.$el;
			this.totalSteps = this.settings.totalSteps;
			this.totalActiveSteps = this.totalSteps
			this.step = this.settings.step;
			this.actualStep = this.step;
			this.quiz = this.settings.quiz;
			this.element = this.$el.find('div.forminator-pagination[data-step=' + this.step + ']').data('name');
			if (this.form_id && typeof window.Forminator_Cform_Paginations === 'object' && typeof window.Forminator_Cform_Paginations[this.form_id] === 'object') {
				this.custom_label = window.Forminator_Cform_Paginations[this.form_id];
			}

			this.$el.on('forminator:page-break:toggled', function(e) {
				self.totalActiveSteps = self.$el.find('.forminator-pagination:not(.forminator-page-hidden)').length;
				self.calculate_bar_percentage();
			});

			if ( this.instantPreview ) {
				// Initialize for instant preview mode
				this.init_instant_preview();
			} else {
				// Normal pagination initialization
				if ( draftPage > 0 ) {
					this.go_to( draftPage, true );
				} else if (this.settings.hashStep && this.step > 0) {
					this.go_to(this.step, true);
				} else if ( this.quiz ) {
					this.go_to(0, true);
				} else {
					this.go_to(0, false);
				}

				this.render_navigation();
				this.render_bar_navigation();
				this.render_footer_navigation();
				this.init_events();
				this.update_navigation();

				this.$el.find('.forminator-button.forminator-button-back, .forminator-button.forminator-button-next, .forminator-button.forminator-button-submit').on("click", function (e) {
					e.preventDefault();
					$(this).trigger('forminator.front.pagination.move');
					self.resetRichTextEditorHeight();
				});

				// Update progress bar percentage on form submit.
				this.$el.on('before:forminator:form:submit', function( e, formData ) {
					if( formData.get( 'save_draft' ) !== 'true' ) {
						self.update_progress_bar_percentage( 100 );
					}
				});

				this.$el.on('click', '.forminator-result--view-answers', function(e){
					e.preventDefault();
					$(this).trigger('forminator.front.pagination.move');
				});

				this.update_buttons();
			}
		},
		init_events: function () {
			var self = this;

			this.$el.find('.forminator-button-back').on('forminator.front.pagination.move',function (e) {
				self.handle_click('prev');
			});
			this.$el.on('forminator.front.pagination.move', '.forminator-result--view-answers', function (e) {
				self.handle_click('prev');
			});
			this.$el.find('.forminator-button-next').on('forminator.front.pagination.move', function (e) {
				self.handle_click('next');
			});

			this.$el.find('.forminator-step').on("click", function (e) {
				e.preventDefault();
				var step = $(this).data('nav');
				self.handle_step(step);
			});

			this.$el.on('reset', function (e) {
				self.on_form_reset(e);
			});

			this.$el.on('forminator:quiz:submit:success', function (e, ajaxData, formData, resultText) {
				if ( resultText ) {
					self.move_to_results(e);
				}
			});

			this.$el.on('forminator.front.pagination.focus.input', function (e, input) {
				self.on_focus_input(e, input);
			});

		},

		/**
		 * Initialize pagination for instant preview mode
		 * Shows all pages together with separators
		 */
		init_instant_preview: function () {
			var self = this;

			// Show all pages
			this.$el.find('.forminator-pagination').css({
				'height': 'auto',
				'opacity': '1',
				'visibility': 'visible'
			}).removeAttr( 'aria-hidden' ).removeAttr( 'hidden' );

			this.$el.find('.forminator-pagination .forminator-pagination--content').show();

			// Get all pagination steps
			const allSteps = this.$el.find('.forminator-pagination');
			const tempElement = this.element;
			const tempEl = this.$el;

			allSteps.each(function(index) {
				const $page = $(this);
				const stepNum = $page.data('step') || 0;
				const pageLabel = $page.data('actual-label') || '';

				const $parent = $page.wrap('<div></div>');

				// Add page separator before the entire pagination element
				var separator = '<div class="forminator-instant-preview-separator" data-page-step="' + stepNum + '">' +
					'<span class="sui-tag">' + self.encodeHTMLEntities(pageLabel) + '</span>' +
				'</div>';
				$page.before(separator);

				// Get element name for this page to determine button text
				self.element = $page.data('name');
				self.$el = $parent;
				self.step = stepNum;
				self.actualStep = stepNum;

				self.render_navigation();
				self.render_bar_navigation();
				self.render_footer_navigation();
				self.update_navigation();
			});

			// Restore original element
			this.element = tempElement;
			this.$el = tempEl;

			// Initialize scroll events for buttons
			this.init_instant_preview_events();
		},

		/**
		 * Generate footer button HTML (reusable for both normal and instant preview)
		 *
		 * @returns {string} Button HTML
		 */
		generate_footer_buttons_html: function() {
			const isMaterial = this.$form.hasClass('forminator-design--material');
			const extraClasses = this.instantPreview ? ' forminator-instant-preview-btn' : '';
			const prevDataAttr = this.instantPreview ? ' data-nav="' + Math.max(0, this.step - 1) + '"' : '';
			const nextDataAttr = this.instantPreview ? ' data-nav="' + Math.min(this.totalSteps, this.step + 1) + '"' : '';
			const buttonText = isMaterial ? '<span class="forminator-button--mask" aria-label="hidden"></span><span class="forminator-button--text"></span>' : '';

			return '<button class="forminator-button forminator-button-back' + extraClasses + '"' + prevDataAttr + '>' + buttonText + '</button>' +
					'<button class="forminator-button forminator-button-next' + extraClasses + '"' + nextDataAttr + '>' + buttonText + '</button>';
		},

		/**
		 * Initialize events for instant preview (scroll instead of navigate)
		 */
		init_instant_preview_events: function() {
			const self = this;

			// Handle navigation step clicks and next/prev button clicks - scroll to page
			this.$el.on('click', '.forminator-instant-preview-nav, .forminator-instant-preview-btn', function(e) {
				e.preventDefault();
				let step = $(this).data('nav');
				const $targetPage = self.$el.find('.forminator-instant-preview-separator[data-page-step="' + step + '"]');
				let spaceBefore = $('#forminator-builder-status').height();
				const $wpBody = $('#wpbody-content');
				if ($wpBody.length) {
					spaceBefore += $wpBody.offset().top;
				}
				if ($targetPage.length) {
					$('html, body').animate({
						scrollTop: $targetPage.offset().top - spaceBefore
					}, 500);
				}
			});
		},

		/**
		 * Move quiz to rezult page
		 */
		move_to_results: function (e) {
			this.finished = true;
			if ( this.$el.find('.forminator-submit-rightaway').length ) {
				this.$el.find('#forminator-submit').removeClass('forminator-hidden');
			} else {
				this.handle_click('next');
			}
		},

		/**
		 * On reset event of Form
		 *
		 * @since 1.0.3
		 *
		 * @param e
		 */
		on_form_reset: function (e) {
			// Trigger pagination to first page
			this.go_to(0, true);
			this.update_buttons();
		},

		/**
		 * On Input focused
		 *
		 * @param e
		 * @param input
		 */
		on_focus_input: function (e, input) {
			//Go to page where element exist
			var step = this.get_page_of_input(input);
			this.go_to(step, true);
			this.update_buttons();
		},

		/**
		 * Internal function to render footer navigation (shared by normal and instant preview)
		 */
		render_footer_navigation: function() {
			const lastStep = this.totalSteps - 1 === this.step;

			var footer_html = '',
				paypal_field = '',
				footer_align = ( this.custom_label['has-paypal'] === true ) ? ' style="align-items: flex-start;"' : '',
				save_draft_btn = this.$form.find( '.forminator-save-draft-link' ).length ? this.$form.find( '.forminator-save-draft-link' ) : ''
				;

			// For instant preview, use the stored original button. Otherwise, find it normally.
			if (this.instantPreview && this._original_save_draft_btn && this._original_save_draft_btn.length) {
				save_draft_btn = this._original_save_draft_btn.clone();
			} else {
				this._original_save_draft_btn = save_draft_btn;
			}

			const buttons_html = this.generate_footer_buttons_html();

			// Build footer HTML
			let footerClass = 'forminator-pagination-footer';
			if (this.instantPreview) {
				footerClass += ' forminator-instant-preview-footer';
			}
			footer_html = '<div class="' + footerClass + '"' + footer_align + '>' + buttons_html;
			// Add PayPal button only once on last step for instant preview
			if( this.custom_label['has-paypal'] === true && ( ! this.instantPreview || lastStep ) ) {
				paypal_field = ( this.custom_label['paypal-id'] ) ? this.custom_label['paypal-id'] : '';
				const paypalId = this.form_id ? ' id="paypal-button-container-' + this.form_id + '"' : '';
				footer_html += '<div class="forminator-payment forminator-button-paypal forminator-hidden ' + paypal_field + '-payment"' + paypalId + '>';
			}
			footer_html += '</div>';

			// Target is container, append footer
			this.$el.append(footer_html);

			// Handle save draft button
			if ( '' !== save_draft_btn ) {
				save_draft_btn.insertBefore( this.$el.find( '.forminator-button-next' ) );
			}

			// Handle button visibility for instant preview
			if (this.instantPreview) {
				this.update_buttons();
			}
		},

		render_bar_navigation: function () {

			var $navigation = this.$form.find( '.forminator-pagination-progress' );

			var $progressLabel = '<div class="forminator-progress-label">0%</div>',
				$progressBar   = '<div class="forminator-progress-bar"><span style="width: 0%"></span></div>'
			;

			if ( ! $navigation.length ) return;

			if( this.instantPreview ) {
				if ( ! this.$navigation ) {
					// first time adding navigation
					this.$navigation = $navigation.clone();
					$navigation.remove();
				}
				$navigation = this.$navigation.clone().prependTo( this.$el );
			}

			$navigation.html( $progressLabel + $progressBar );

			this.calculate_bar_percentage();

		},

		calculate_bar_percentage: function () {

			var total     = this.totalActiveSteps,
				current   = this.actualStep
			;
			if ( this.custom_label['pagination-header'] === 'bar' && this.custom_label['progress-bar-type'] === 'page-number') {
				current++;
			}
			var percentage = Math.round( (current / total) * 100 );

			this.update_progress_bar_percentage( percentage );
		},

		update_progress_bar_percentage: function ( percentage ) {
			const $progress = this.$el;
			if ( ! $progress.length ) return;

			if ( this.custom_label[ 'pagination-header' ] === 'bar' && this.custom_label[ 'progress-bar-type' ] === 'page-number' ) {
				let text = this.custom_label[ 'page-number-text' ];
				text = text.replace( '%1$s', this.actualStep + 1 ).replace( '%2$s', this.totalActiveSteps );
				$progress.find( '.forminator-progress-label' ).html( text );
			} else {
				$progress.find( '.forminator-progress-label' ).html( percentage + '%' );
			}

			$progress.find( '.forminator-progress-bar span' ).css( 'width', percentage + '%' );

		},

		encodeHTMLEntities( value ) {
			const textArea = document.createElement( 'textarea' );
			textArea.innerText = value;
			return textArea.innerHTML;
		},

		render_navigation: function () {
			var $navigation = this.$form.find('.forminator-pagination-steps');
			var finalSteps = this.$form.find('.forminator-pagination-start');

			if ( ! $navigation.length ) return;

			let render = $( this.$form ).data( 'forminator-render' ) || '';
			let $stepClass = 'forminator-step';

			if( this.instantPreview ) {
				if ( ! this.$navigation ) {
					// first time adding navigation
					this.$navigation = $navigation.clone();
					$navigation.remove();
				}
				$navigation = this.$navigation.clone().prependTo( this.$el );
				$stepClass += ' forminator-instant-preview-nav';
			}

			var steps = this.$form.find( '.forminator-pagination' ).not( '.forminator-pagination-start' );

			var basicDesign = this.$form.hasClass('forminator-design--basic');

			$navigation.append( '<div class="forminator-break"></div>' );

			var self = this;
			if ( basicDesign ) {
				$stepClass += ' has-text-color';
			}
			if ( self.instantPreview ) {
				// set sender random from 1 to 99 number to avoid duplicate ids
				render = Math.floor(Math.random() * 99) + 1;
			}

			steps.each( function() {

				var $step        = $( this ),
					$stepLabel   = self.encodeHTMLEntities( $step.data( 'label' ) ),
					$stepNumb    = $step.data('step') - 1,
					$stepControl = 'forminator-custom-form-' + self.form_id + '-' + render + '--page-' + $stepNumb,
					$stepId      = $stepControl + '-label'
				;

				var $stepMarkup = '<button role="tab" id="' + $stepId + '" class="' + $stepClass + ' forminator-step-' + $stepNumb + '" aria-selected="false" aria-controls="' + $stepControl + '" data-nav="' + $stepNumb + '">' +
					'<span class="forminator-step-label">' + $stepLabel + '</span>' +
					'<span class="forminator-step-dot" aria-hidden="true"></span>' +
				'</button>';

				var $stepBreak = '<div class="forminator-break" aria-hidden="true"></div>';

				$navigation.append( $stepMarkup + $stepBreak );

			});

			finalSteps.each(function () {
				var $step   = $(this),
					label   = self.encodeHTMLEntities( $step.data( 'label' ) ),
					numb    = steps.length,
					control = 'forminator-custom-form-' + self.form_id + '-' + render + '--page-' + numb,
					stepid  = control + '-label'
				;

				var $stepMarkup = '<button role="tab" id="' + stepid + '" class="' + $stepClass + ' forminator-step-' + numb + '" data-nav="' + numb + '" aria-selected="false" aria-controls="' + control + '">' +
					'<span class="forminator-step-label">' + label + '</span>' +
					'<span class="forminator-step-dot" aria-hidden="true"></span>' +
				'</button>';

				var $stepBreak = '<div class="forminator-break" aria-hidden="true"></div>';

				$navigation.append( $stepMarkup + $stepBreak );
			});
		},

		/**
		 * Handle step click
		 *
		 * @param step
		 */
		handle_step: function( step ) {
			if ( this.settings.inline_validation ) {
				for ( var i = 0; i < step; i++ ) {
					if ( this.step <= i ) {
						if ( ! this.is_step_inputs_valid( i ) ) {
							this.go_to( i, true );
							return;
						}
					}
				}
			}
			this.go_to( step, true );
			this.update_buttons();
		},

		handle_click: function (type) {
			var self = this;
			if (type === "prev" && this.step !== 0) {
				this.go_to_previous_page();
			} else if (type === "next") {
				//do validation before next if inline validation enabled
				if (this.settings.inline_validation) {
					if ( ! this.is_step_inputs_valid( this.step ) ) {
						return;
					}
				}

				if(typeof this.$el.data().forminatorFrontPayment !== "undefined") {
					var payment = this.$el.data().forminatorFrontPayment,
						page = this.$el.find('div.forminator-pagination[data-step=' + this.step + ']'),
						hasStripe = page.find(".forminator-stripe-element").not(".forminator-hidden .forminator-stripe-element")
					;


					// Check if Stripe exists on current step
					if (hasStripe.length > 0) {
						payment._stripe.createToken(payment._cardElement).then(function (result) {
							if (result.error) {
								payment.showCardError(result.error.message, true);
							} else {
								payment.hideCardError();
								self.go_to_next_page();
							}
						});
					} else {
						this.go_to_next_page();
					}
				} else {
					this.go_to_next_page();
				}
			}

			// re-init textarea floating labels.
			var form = $( this.$el );
			var textarea = form.find( '.forminator-textarea' );
			var isMaterial = form.hasClass( 'forminator-design--material' );

			if ( isMaterial ) {
				if ( textarea.length ) {
					textarea.each( function() {
						FUI.textareaMaterial( this );
					});
				}
			}
		},

		/**
		 * Check current inputs on step is in valid state
		 */
		is_step_inputs_valid: function ( step ) {
			var valid = true,
				errors = 0,
				validator = this.$el.data('validator'),
				page = this.$el.find('div.forminator-pagination[data-step=' + step + ']');

			//inline validation disabled
			if (typeof validator === 'undefined') {
				return true;
			}

			//get fields on current page
			page.find("input, select, textarea")
				.not(":submit, :reset, :image, :disabled")
				.not(".forminator-field-signature :input:not(.do-validate)")
				.not('[gramm="true"]')
				.each(function (key, element) {
					if (
						$( element ).is(
							':hidden:not(.forminator-wp-editor-required, .forminator-input-file-required, input[name$="_data"])'
						) &&
						! $( element ).closest( '.forminator-pagination' )
							.length
					) {
						return;
					}
					valid = validator.element(element);

					if (!valid) {
						if (errors === 0) {
							// focus on first error
							element.focus();
						}
						errors++;
					}
				});

			return errors === 0;
		},

		/**
		 * Get page on the input
		 *
		 * @since 1.0.3
		 *
		 * @param input
		 * @returns {number|*}
		 */
		get_page_of_input: function(input) {
			var step_page = this.step;
			var page = $(input).closest('.forminator-pagination');
			if (page.length > 0) {
				var step = $(page).data('step');
				if (typeof step !== 'undefined') {
					step_page = +step;
				}
			}

			return step_page;
		},

		update_buttons: function () {
			var hasDraft = this.$form.hasClass( 'draft-enabled' ),
				self     = this;

			if (this.step === 0) {
				if ( ! hasDraft ) {
					this.$el.find('.forminator-button-back').closest( '.forminator-pagination-footer' ).css({
						'justify-content': 'flex-end'
					});
				}

				this.$el.find('.forminator-button-back').addClass( 'forminator-hidden' );
				this.$el.find('.forminator-button-next').removeClass('forminator-hidden');
			} else {
				if ( this.totalSteps > 1 ) {
					if ( ! hasDraft ) {
						this.$el.find('.forminator-button-back').closest( '.forminator-pagination-footer' ).css({
							'justify-content': 'space-between'
						});
					}

					this.$el.find('.forminator-button-back, .forminator-button-next').removeClass('forminator-hidden');
				}
			}

			if (this.actualStep === this.totalActiveSteps && ! this.finished ) {
				//keep pagination content on last step before submit
				this.step--;
				this.actualStep--;
				this.$form.trigger( 'submit' );
			}

			var submitButtonClass = this.settings.submitButtonClass;
			if ( this.actualStep === ( this.totalActiveSteps - 1 ) && ! this.finished ) {

				var submit_button_text = this.$el.find('.forminator-pagination-submit').html(),
					loadingText = this.$el.find('.forminator-pagination-submit').data('loading'),
					last_button_txt = ( this.custom_label[ 'pagination-labels' ] === 'custom'
						&& this.custom_label['last-previous'] !== '' ) ? this.custom_label['last-previous'] : this.prev_button,
					forminatorPayment = self.$el.find('.forminator-payment'),
					nextBtn = this.$el.find('.forminator-button-next'),
					submitButton = this.$el.find( '.forminator-button-submit' );

				if ( this.$form.hasClass('forminator-design--material') ) {

					this.$el.find('.forminator-button-back .forminator-button--text').html( last_button_txt );
					nextBtn.removeClass('forminator-button-next').attr('id', 'forminator-submit');

					setTimeout(
						function() {
							nextBtn
							.addClass('forminator-button-submit ' + submitButtonClass )
							.find('.forminator-button--text')
							.html('')
							.html(submit_button_text).data('loading', loadingText);
							self.$el.trigger( 'forminator.front.pagination.buttons.updated' );
						},
						20
					);
				} else {
					this.$el.find('.forminator-button-back').html( last_button_txt );
					nextBtn.removeClass( 'forminator-button-next' ).attr( 'id', 'forminator-submit' );

					setTimeout(
						function() {
							nextBtn
							.addClass( 'forminator-button-submit ' + submitButtonClass )
							.html( submit_button_text ).data('loading', loadingText);
							self.$el.trigger( 'forminator.front.pagination.buttons.updated' );
						},
						20
					);
				}

				// Redeclare submit button.
				setTimeout(
					function() {
						submitButton = self.$el.find( '.forminator-button-submit' );
					},
					30
				);

				if ( this.$form.hasClass('forminator-quiz') && ! submit_button_text ) {
					submitButton.addClass('forminator-hidden');
					if ( this.$el.find( '.forminator-submit-rightaway').length ) {
						submitButton.html( window.ForminatorFront.quiz.view_results );
					}
				}

				if( this.custom_label['has-paypal'] === true ) {
					forminatorPayment.attr('id', 'forminator-paypal-submit');

					setTimeout(
						function() {
							const $stripe_element = self.$form.find('.forminator-field-stripe-ocs:not(.forminator-hidden), .forminator-field-stripe:not(.forminator-hidden)');
							if ( ! window.paypalHasCondition.includes( self.$el.data( 'form-id' ) )  ) {
							if( $stripe_element.length === 0 ){
								submitButton.addClass('forminator-hidden');
							}
								forminatorPayment.removeClass( 'forminator-hidden' );
							}
						},
						40
					);
				}

				if ( forminatorPayment.find('iframe').length > 0 ) {
					forminatorPayment.find('iframe').width('100%');
				}

			} else {
				this.element = this.$form.find('.forminator-pagination[data-step=' + this.step + ']').data('name');
				if ( this.custom_label[this.element] && this.custom_label['pagination-labels'] === 'custom'){
					this.prev_button_txt = this.custom_label[this.element]['prev-text'] !== '' ? this.custom_label[this.element]['prev-text'] : this.prev_button;
					this.next_button_txt = this.custom_label[this.element]['next-text'] !== '' ? this.custom_label[this.element]['next-text'] : this.next_button;
				}else{
					this.prev_button_txt = this.prev_button;
					this.next_button_txt = this.next_button;
				}
				if ( this.actualStep === ( this.totalActiveSteps - 1 ) && this.finished ) {
					this.next_button_txt = window.ForminatorFront.quiz.view_results;
				}
				if ( this.$form.hasClass('forminator-design--material') ) {
					this.$el.find( '#forminator-submit' )
						.removeAttr( 'id' )
						.removeClass( 'forminator-button-submit forminator-hidden ' + submitButtonClass )
						.addClass( 'forminator-button-next' );
					if( this.custom_label['has-paypal'] === true ) {
						this.$el.find( '#forminator-paypal-submit' ).removeAttr( 'id' ).addClass('forminator-hidden');
						this.$el.find( '.forminator-button-next' ).removeClass( 'forminator-button-submit forminator-hidden ' + submitButtonClass );
					}

					this.$el.find( '.forminator-button-back .forminator-button--text' ).html( this.prev_button_txt );
					this.$el.find( '.forminator-button-next .forminator-button--text' ).html( this.next_button_txt );

				} else {
					this.$el.find( '#forminator-submit' )
						.removeAttr( 'id' )
						.removeClass( 'forminator-button-submit forminator-hidden ' + submitButtonClass )
						.addClass( 'forminator-button-next' );
					if( this.custom_label['has-paypal'] === true ) {
						this.$el.find( '#forminator-paypal-submit' ).removeAttr( 'id' ).addClass('forminator-hidden');
						this.$el.find('.forminator-button-next').removeClass( 'forminator-button-submit forminator-hidden ' + submitButtonClass );
					}
					this.$el.find( '.forminator-button-back' ).html( this.prev_button_txt );
					this.$el.find( '.forminator-button-next' ).html( this.next_button_txt );

				}
				if ( this.actualStep === this.totalActiveSteps && this.finished ) {
					this.$el.find('.forminator-button-next, .forminator-button-back').addClass( 'forminator-hidden' );
				}
				this.$el.trigger( 'forminator.front.pagination.buttons.updated' );
			}
			// Reset the conditions to check if submit/paypal buttons should be visible
			this.$el.trigger( 'forminator.front.condition.restart' );
		},

		go_to: function (step, scrollToTop) {
			this.step = step;
			this.actualStep = this.get_current_visible_step_position();

			if (this.actualStep === this.totalActiveSteps && ! this.finished ) return false;

			// Check if the target step is hidden by page-break conditions
			var $targetStep = this.$el.find('div.forminator-pagination[data-step=' + step + ']');
			if ($targetStep.hasClass('forminator-page-hidden')) {
				// Find the next visible step
				var nextVisibleStep = this.find_next_visible_step(step);
				this.go_to(nextVisibleStep, scrollToTop);
				return;
			}

			// Hide all parts
			this.$el.find('.forminator-pagination').css({
				'height': '0',
				'opacity': '0',
				'visibility': 'hidden'
			}).attr( 'aria-hidden', 'true' ).attr( 'hidden', true );

			this.$el.find('.forminator-pagination .forminator-pagination--content').hide();

			// Show desired page
			$targetStep.css({
				'height': 'auto',
				'opacity': '1',
				'visibility': 'visible'
			}).removeAttr( 'aria-hidden' ).removeAttr( 'hidden' );

			$targetStep.find('.forminator-pagination--content').show();

			//exec responsive captcha
			var forminatorFront = this.$el.data('forminatorFront');
			if (typeof forminatorFront !== 'undefined') {
				forminatorFront.responsive_captcha();
			}

			this.update_navigation();

			if (scrollToTop) {
				this.scroll_to_top_form();
			}
		},

		/**
		 * Get the current step's position among visible steps (0-based)
		 *
		 * @returns {number} - Current step position among visible steps
		 */
		get_current_visible_step_position: function() {
			var position = this.step;
			for (var i = this.step; i >= 0; i--) {
				var $step = this.$el.find('div.forminator-pagination[data-step=' + i + ']');
				if ($step.length && $step.hasClass('forminator-page-hidden')) {
					position--;
				}
			}
			return position;
		},

		/**
		 * Find the next visible step after the given step
		 *
		 * @param {number} currentStep - The current step number
		 * @returns {number} - The next visible step number
		 */
		find_next_visible_step: function(currentStep) {
			for (var i = currentStep + 1; i < this.totalSteps; i++) {
				var $step = this.$el.find('div.forminator-pagination[data-step=' + i + ']');
				if ($step.length && !$step.hasClass('forminator-page-hidden')) {
					return i;
				}
			}
			return i;
		},

		/**
		 * Find the previous visible step before the given step
		 *
		 * @param {number} currentStep - The current step number
		 * @returns {number} - The previous visible step number, or 0 if none found
		 */
		find_previous_visible_step: function(currentStep) {
			for (var i = currentStep - 1; i >= 0; i--) {
				var $step = this.$el.find('div.forminator-pagination[data-step=' + i + ']');
				if ($step.length && !$step.hasClass('forminator-page-hidden')) {
					return i;
				}
			}
			return 0;
		},

		/**
		 * Navigate to the next visible page
		 */
		go_to_next_page: function() {
			var nextVisibleStep = this.find_next_visible_step(this.step);
			this.go_to(nextVisibleStep, true);
			this.update_buttons();
		},

		/**
		 * Navigate to the previous visible page
		 */
		go_to_previous_page: function() {
			var prevVisibleStep = this.find_previous_visible_step(this.step);
			this.go_to(prevVisibleStep, true);
			this.update_buttons();
		},

		update_navigation: function () {

			// Update navigation
			this.$el.find( '.forminator-current' ).attr( 'aria-selected', 'false' );
			this.$el.find( '.forminator-current' ).removeClass('forminator-current' );
			this.$el.find( '.forminator-step-' + this.step ).attr( 'aria-selected', 'true' );
			this.$el.find( '.forminator-step-' + this.step ).addClass( 'forminator-current' );

			this.$el.find( '.forminator-pagination:not(:hidden)' ).find( '.forminator-answer input' ).first().trigger( 'change' );

			this.calculate_bar_percentage();
		},

		/**
		 * Reset vertical screen position between sections
		 * https://app.asana.com/0/385581670491499/784073712068017/f
		 * Support Hustle Modal
		 */
		scroll_to_top_form: function () {
			var self            = this;
			var $element        = this.$el;
			// find first input row
			var first_input_row = this.$el.find('.forminator-row').not(':hidden').first();
			if (first_input_row.length) {
				$element = first_input_row;
			}

			if ($element.length) {
				var parent_selector = 'html,body';

				// check inside sui modal
				if (this.$el.closest('.sui-dialog').length > 0) {
					parent_selector = '.sui-dialog';
				}

				// check inside hustle modal (prioritize)
				if (this.$el.closest('.wph-modal').length > 0) {
					parent_selector = '.wph-modal';
				}

				const minScrollHeight = $( window ).height() / 2;
				let scrollTop =
					$element.offset().top -
					Math.max(
						minScrollHeight,
						$( window ).height() - $element.outerHeight( true )
					) /
						2;

				if ( this.quiz ) {
					scrollTop = $element.offset().top;
					if ( $( '#wpadminbar' ).length ) {
						scrollTop -= 35;
					}
				}

				$(parent_selector).animate({scrollTop: scrollTop}, 500, function () {
					if (!$element.attr("tabindex")) {
						$element.attr("tabindex", -1);
					}
					$element.focus();
				});
			}

		},

		resetRichTextEditorHeight: function () {
			if ( typeof tinyMCE !== 'undefined' ) {
				var form = this.$el,
					textarea = form.find( '.forminator-textarea' );

				textarea.each( function() {
					var tmceId = $( this ).attr( 'id' );

					if ( 0 !== form.find( '#'+ tmceId + '_ifr' ).length && form.find( '#'+ tmceId + '_ifr' ).is( ':visible' ) ) {
						form.find( '#' + tmceId + '_ifr' ).height( $( this ).height() );
					}
				});
			}
		},
	});

	// A really lightweight plugin wrapper around the constructor,
	// preventing against multiple instantiations
	$.fn[pluginName] = function (options) {
		return this.each(function () {
			if (!$.data(this, pluginName)) {
				$.data(this, pluginName, new ForminatorFrontPagination(this, options));
			}
		});
	};

})(jQuery, window, document);
