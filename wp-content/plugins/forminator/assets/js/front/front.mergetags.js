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
	var pluginName = "forminatorFrontMergeTags",
	    defaults   = {
		    print_value: false,
		    forminatorFields: [],
	    };

	// The actual plugin constructor
	function forminatorFrontMergeTags(element, options) {
		this.element = element;
		this.$el     = $(this.element);

		// jQuery has an extend method which merges the contents of two or
		// more objects, storing the result in the first object. The first object
		// is generally empty as we don't want to alter the default options for
		// future instances of the plugin
		this.settings          = $.extend({}, defaults, options);
		this._defaults         = defaults;
		this._name             = pluginName;
		ForminatorFront.MergeTags = ForminatorFront.MergeTags || [];
		this.init();
	}

	// Avoid Plugin.prototype conflicts
	$.extend(forminatorFrontMergeTags.prototype, {
		init: function () {
			var self = this;
			var fields = this.$el.find('.forminator-merge-tags');
			const formId = this.getFormId();

			ForminatorFront.MergeTags[ formId ] = ForminatorFront.MergeTags[ formId ] || [];

			if (fields.length > 0) {
				fields.each(function () {
					let html = $(this).html(),
						fieldId = $(this).data('field');

					if ( self.$el.hasClass( 'forminator-grouped-fields' ) ) {
						// Get origin HTML during cloningGroup fields.
						const suffix = self.$el.data( 'suffix' );
						if ( ForminatorFront.MergeTags[ formId ][ fieldId ] ) {
							html = ForminatorFront.MergeTags[ formId ][ fieldId ]['value'];
							// get Fields in the current Group.
							const groupFields = self.$el.find( '[name]' ).map(function() {
								return this.name;
							}).get();
							$.each( groupFields, function( index, item ) {
								var fieldWithoutSuffix = item.replace( '-' + suffix, '' );
								if ( fieldWithoutSuffix === item ) {
									return; // continue.
								}
								const regexp = new RegExp( `{${fieldWithoutSuffix}}`, 'g' );
								html = html.replace( regexp, '{' + item + '}' );
							});
						}

						fieldId += '-' + suffix;
					}

					ForminatorFront.MergeTags[ formId ][ fieldId ] = {
						$input: $(this),
						value: html,
					};
				});
			}

			setTimeout(function () {
				self.replaceAll();
				self.attachEvents();
			}, 100);
		},

		getFormId: function () {
			let formId = '';
			if ( this.$el.hasClass( 'forminator-grouped-fields' ) ) {
				formId = this.$el.closest( 'form.forminator-ui' ).data( 'form-id' );
			} else {
				formId = this.$el.data( 'form-id' );
			}

			return formId;
		},

		attachEvents: function () {
			var self = this;

			this.$el.find(
				'.forminator-textarea, input.forminator-input, .forminator-input input, .forminator-checkbox input, .forminator-radio input, .forminator-input-file, select.forminator-select2, .forminator-multiselect input'
				+ ', input.forminator-slider-hidden, input.forminator-slider-hidden-min, input.forminator-slider-hidden-max, select.forminator-rating'
			).each(function () {
				$(this).on('change forminator.change', function () {
					// Give jquery sometime to apply changes
					setTimeout( function() {
					   self.replaceAll();
               }, 300 );
				});
			});

			// When remove a group item, we need to replace all merge tags.
			this.$el.on( 'forminator-group-item-removed', function () {
				self.replaceAll();
			} );
		},

		replaceAll: function () {
			const self = this,
					formId = this.getFormId(),
					formFields = ForminatorFront.MergeTags[ formId ];

			for ( const key in formFields ) {
				const formField = formFields[key];
				self.replace( formField );
			}
		},

		replace: function ( field ) {
			var $input = field.$input;
			var res = this.maybeReplaceValue(field.value);
			if ( typeof window.DOMPurify !== 'undefined' ) {
				let config = { ADD_ATTR: [ 'target' ] };
				// Allow iframe tags and attributes if the original value contains iframes.
				if( this.hasIframes(field.value) ) {
					config = {
						ADD_TAGS: ['iframe'],
						ADD_ATTR: [
							'align',
							'width',
							'height',
							'frameborder',
							'name',
							'src',
							'id',
							'class',
							'style',
							'scrolling',
							'marginwidth',
							'marginheight',
							'allowfullscreen',
							'target'
						]
					};
				}
				res = window.DOMPurify.sanitize( res, config );
			}
			$input.html(res);
		},

		hasIframes: function ( html ) {
			const $temp = $('<div>').html(html);
			return $temp.find('iframe').length > 0;
		},

		maybeReplaceValue: function (value) {
			var joinedFieldTypes      = this.settings.forminatorFields.join('|');
			var incrementFieldPattern = "(" + joinedFieldTypes + ")-\\d+";
			var pattern               = new RegExp('\\{(' + incrementFieldPattern + ')(\\-[0-9A-Za-z-_]+)?(\\-\\*)?\\}', 'g');
			var parsedValue           = value;

			var matches;
			while (matches = pattern.exec(value)) {
				var fullMatch = matches[0];
				var inputName = fullMatch.replace('{', '').replace('}', '');
				var fieldType = matches[2];

				var replace = fullMatch;

				if (fullMatch === undefined || inputName === undefined || fieldType === undefined) {
					continue;
				}

				// Check if the field is a grouped field.
				if( inputName.endsWith( '-*' ) ){
					inputName = inputName.replace( '-*', '' );
					replace = this.get_group_field_values( inputName );
				} else {
					replace = this.get_field_value(inputName);
				}

				parsedValue = parsedValue.replace(fullMatch, replace);
			}

			return parsedValue;
		},

		// taken from forminatorFrontCondition
		get_form_field: function (element_id, repeater = false) {
			let $form = this.$el;
			if ( $form.hasClass( 'forminator-grouped-fields' ) ) {
				$form = $form.closest( 'form.forminator-ui' );
			}
			if( repeater === true ) {
				// Find element by name start with element_id- (for repeater fields)
				return $form.find('[name^=' + element_id + '-]');
			}
			//find element by suffix -field on id input (default behavior)
			var $element = $form.find('#' + element_id + '-field');
			if ($element.length === 0) {
				//find element by its on name
				$element = $form.find('[name=' + element_id + ']');
				if ($element.length === 0) {
					//find element by its on name[] (for checkbox on multivalue)
					$element = $form.find('input[name="' + element_id + '[]"]');
					if ($element.length === 0) {
						$element = $form.find(
							'select[name="' + element_id + '[]"]'
						);
						if ($element.length === 0) {
							//find element by direct id (for name field mostly)
							//will work for all field with element_id-[somestring]
							$element = $form.find('#' + element_id);
						}
					}
				}
			}

			return $element;
		},

		get_group_field_values: function ( element_id ) {
			var $first_elements    	= this.get_form_field( element_id ),
				$repeated_elements 	= this.get_form_field( element_id, true),
				value       		= '',
				self        		= this;

			if ( $first_elements.length === 0 ) {
				return '';
			}

			let $elements = [$first_elements[0]];
			let seenElementIds = new Set();

			$.each($repeated_elements, function ( index, element ) {
				let elementId = $( element ).attr( 'name' ).replace( '[]', '' );
				if ( ! seenElementIds.has( elementId ) ) {
					seenElementIds.add( elementId );
					$elements.push( element );
				}
			});

			$.each( $elements, function( index, element ) {
				if ( $( element ).attr( 'name' ) !== undefined ) {
					let elementId = $( element ).attr( 'name' ).replace( '[]', '' );
					let result = self.get_field_value( elementId );
					if( result.trim() !== '' ) {
						value += '<p>' + result + '</p>';
					}
				}
			} );
			return value;
		},

		get_field_value: function (element_id) {
			var $element    = this.get_form_field(element_id),
				self        = this,
				value       = '',
				checked     = null;

			if ( $element.length === 0 ) {
				return '';
			}

			if ( forminatorUtils().is_hidden( $element ) ) {
				return '';
			}

			if (this.field_is_radio($element)) {
				checked = $element.filter(":checked");

				if (checked.length) {
					if ( this.settings.print_value ) {
						value = checked.val();
					} else {
						value = 0 === checked.siblings( '.forminator-radio-label' ).length
								? checked.siblings( '.forminator-screen-reader-only' ).text()
								: checked.siblings( '.forminator-radio-label' ).text();
					}
					value += self.append_custom_input_value_if_present( checked, 'radio' );
				}
			} else if (this.field_is_checkbox($element)) {
				$element.each(function () {
					if ($(this).is(':checked')) {
						if(value !== "") {
							value += ', ';
						}

						if ( undefined !== $(this).attr('id') && $(this).attr('id').indexOf('forminator-field-consent') > -1 ) {
							value += $(this).val();
						}

						var multiselect = !! $(this).closest('.forminator-multiselect').length;

						if ( self.settings.print_value ) {
							value += $(this).val();
						} else if ( multiselect ) {
							value += $(this).closest('label').text();
						} else {
							value += 0 === $(this).siblings( '.forminator-checkbox-label' ).length
									 ? $(this).siblings( '.forminator-screen-reader-only' ).text()
									 : $(this).siblings( '.forminator-checkbox-label' ).text();
						}
						value += self.append_custom_input_value_if_present( $(this), 'checkbox' );
					}
				});

			} else if (this.field_is_select($element)) {
				checked = $element.find("option").filter(':selected');
				if (checked.length) {
					checked.each( function () {
						if ( value !== '' ) {
							value += ', ';
						}
						if ( self.settings.print_value ) {
							value += $( this ).val();
						} else {
							value += $( this ).text();
						}
						value += self.append_custom_input_value_if_present( $(this), 'select' );
					} );
				}
			} else if (this.field_is_upload($element)) {
				value = $element.val().split('\\').pop();
			} else if (this.field_has_inputMask($element)) {
				$element.inputmask({'autoUnmask' : false});
				value = $element.val();
				$element.inputmask({'autoUnmask' : true});
			} else {
				value = $element.val();
			}

			return this.sanitize_text_field( value );
		},

		append_custom_input_value_if_present: function ( $element, type ) {
			let value = '';
			if( $element.val() === 'custom_option' ) {
				const customInput = $element.closest( '.forminator-field-' + type ).find( '.forminator-custom-input .forminator-input' );
				if( customInput.length && customInput.val() !== '' ) {
					value += ": " + customInput.val();
				}
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

		field_is_upload: function ($element) {
			if ($element.attr('type') === 'file') {
				return true;
			}

			return false;
		},

		field_is_select: function ($element) {
			return $element.is('select');
		},

		/**
		 * Sanitize the user input value.
		 *
		 * @param {value} value
		 */
		sanitize_text_field: function ( value ) {
			if ( typeof value === 'string' ) {
				const sanitizedValue = value.replace( /<\/?[^>]+(>|$)/g, '' );
				return sanitizedValue.trim();
			}
			return value;
		},
	});

	// A really lightweight plugin wrapper around the constructor,
	// preventing against multiple instantiations
	$.fn[pluginName] = function (options) {
		return this.each(function () {
			if (!$.data(this, pluginName)) {
				$.data(this, pluginName, new forminatorFrontMergeTags(this, options));
			}
		});
	};

})(jQuery, window, document);
