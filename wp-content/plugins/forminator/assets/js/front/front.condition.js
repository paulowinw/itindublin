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

	window.paypalHasCondition = [];

	// Create the defaults once
	var pluginName = "forminatorFrontCondition",
		defaults = {
			fields: {},
			relations: {}
		};

	// The actual plugin constructor
	function ForminatorFrontCondition(element, options, calendar) {
		this.element = element;
		this.$el = $(this.element);

		// jQuery has an extend method which merges the contents of two or
		// more objects, storing the result in the first object. The first object
		// is generally empty as we don't want to alter the default options for
		// future instances of the plugin
		this.settings = $.extend({}, defaults, options);
		this._defaults = defaults;
		this._name = pluginName;
		this.calendar = calendar[0];
		this.init();
	}
	// Avoid Plugin.prototype conflicts
	$.extend(ForminatorFrontCondition.prototype, {
		init: function () {
			var self = this,
				form = this.$el,
				$forminatorFields = this.$el.find( ".forminator-field input, .forminator-row input[type=hidden], .forminator-field select, .forminator-field textarea, .forminator-field-signature, .forminator-rating")
				;

			// Duplicate rules for new repeated Group fields.
			if ( form.hasClass( 'forminator-grouped-fields' ) ) {
				let suffix = form.data( 'suffix' );
				$.each( this.settings.fields, function ( key, val ) {
					let newKey = key + '-' + suffix;
					if ( ! form.find( '[name="' + newKey + '"]' ).length && ! form.find( '#' + newKey ).length ) {
						return;
					}
					let newVal = self.updateConditions( val, suffix, form );
					self.settings['fields'][ newKey ] = newVal;
				} );
			}

			this.add_missing_relations();

			$forminatorFields.on( 'change input forminator.change', function (e) {
				var $element = $(this),
					element_id = $element.closest('.forminator-col').attr('id')
					;

				if ( element_id && 0 === element_id.indexOf('group-') && $element.attr('id') ) {
					// If the element is inside a group, it means that element doesn't have forminator-col wrap.
					// Unset element_id to follow existing logic.
					element_id = undefined;
				}

				if ( $element.is( 'input[type="radio"]' ) ) {
					var $radioGroup = $element.closest( '.forminator-radio' );
					forminatorUtils().show_hide_custom_input( $radioGroup, 'radio' );
				}

				if ( $element.is( 'input[type="radio"]' ) && 'input' === e.type ) {
					// Skip input events for radio buttons, handle only change events for them.
					return;
				}

				if ( $element.is( 'input[type="checkbox"]' ) ) {
					var $checkboxGroup = $element.closest( '.forminator-checkbox' );
					forminatorUtils().show_hide_custom_input( $checkboxGroup, 'checkbox' );
				}

				if ( $element.closest( '.forminator-select2' ) ) {
					var $selectGroup = $element.closest( '.forminator-select2' );
					forminatorUtils().show_hide_custom_input( $selectGroup, 'select' );
				}

				if ( $element.closest( '.forminator-multiselect' ) ) {
					var $multiselectGroup = $element.closest( '.forminator-multiselect' );
					forminatorUtils().show_hide_custom_input( $multiselectGroup, 'multiselect' );
				}

				// Handle the email field when email confirmation is enabled.
				if ( $element.is( 'input[type="email"]' ) && element_id === undefined &&
					$element.attr( 'id' ).startsWith( 'forminator-field-email-' ) ) {
					element_id = $element.closest( '.forminator-row-with-confirmation-email' ).parent( '.forminator-col' ).attr( 'id' );
				}

				if (typeof element_id === 'undefined' || 0 === element_id.indexOf( 'slider-' ) ) {
                    /*
                     * data-multi attribute was added to Name field - multiple
                     * We had to use name attribute for Name multi-field because we cannot change
                     * the IDs of elements. Some functions rely on the ID text pattern already.
                     */
					if ( $element.attr( 'data-multi' ) === '1' || 'hidden' === $element.attr( 'type' ) ) {
					   element_id = $element.attr( 'name' );
                    } else {
					   element_id = $element.attr( 'id' );
                    }
				}
				element_id = element_id.trim();
				//lookup condition of fields
				if (!self.has_relations(element_id) && !self.has_siblings(element_id)) return false;

				if( self.has_siblings(element_id) ) {
					self.trigger_fake_parent_date_field(element_id);
				}
				if(!self.has_relations(element_id) && self.has_siblings(element_id)){
					self.trigger_siblings(element_id);
					return false;
				}

				self.process_relations( element_id, $element, e );

				self.paypal_button_condition();

				self.maybe_clear_upload_container();
			});

            // Trigger change event to textarea that has tinyMCE editor
            // For non-ajax form load
            $( document ).on( 'tinymce-editor-init', function ( event, editor ) {
                editor.on( 'change', function( e ) {
                    form.find( '#' + $(this).attr( 'id' ) ).change();
                });
            });
            // For ajax form load
            if ( typeof tinyMCE !== 'undefined' && tinyMCE.activeEditor ) {
                tinyMCE.activeEditor.on( 'change', function( e ) {
                    form.find( '#' + $(this).attr( 'id' ) ).change();
                });
            }

			this.$el.find('.forminator-button.forminator-button-back, .forminator-button.forminator-button-next').on("click", function () {
				form.find('.forminator-field input:not([type="file"]), .forminator-row input[type=hidden], .forminator-field select, .forminator-field textarea').trigger( 'forminator.change', 'forminator_emulate_trigger' );
			});
			// Simulate change
			this.$el.find('.forminator-field input, .forminator-row input[type=hidden], .forminator-field select, .forminator-field textarea').trigger( 'forminator.change', 'forminator_emulate_trigger' );
			this.init_events();
		},

		updateConditions: function( data, suffix, form ) {
			// Clone data.
			var newData = JSON.parse(JSON.stringify(data));

			if ( ! newData.conditions || ! Array.isArray( newData.conditions ) ) {
				return newData;
			}

			const currentGroup = form.closest('.forminator-col').prop( 'id' );
			newData.conditions.forEach( function ( condition ) {
				if ( ! condition.field || ! condition.group ) {
					return;
				}

				// Update dependency field from if it's in the same group.
				if ( condition.group === currentGroup ) {
					condition.field = condition.field + '-' + suffix;
				}
			} );

			return newData;
		},

		process_relations: function( element_id, $element, e ) {
			var self = this;
			// Check if the field has any relations
			var relations = self.get_relations( element_id );
			// Loop all relations the field have
			relations.forEach(function (relation) {
				var logic = self.get_field_logic(relation),
					action = logic.action,
					rule = logic.rule,
					conditions = logic.conditions, // Conditions rules
					matches = 0 // Number of matches
				;

				// If paypal has logic, add form id to paypalHasCondition.
				if ( 0 === relation.indexOf( 'paypal' ) ) {
					if (
						0 !== logic.length &&
						! window.paypalHasCondition.includes( self.$el.data( 'form-id' ) )
					) {
						window.paypalHasCondition.push( self.$el.data( 'form-id' ) );
					}
				}

				conditions.forEach(function (condition) {
					// If rule is applicable save in matches
					if (self.is_applicable_rule(condition)) {
						matches++;
					}
				});

				if ((rule === "all" && matches === conditions.length) || (rule === "any" && matches > 0)) {
					//check if the given $element is an jQuery object
					if( $element instanceof jQuery ) {
						var pagination = $element.closest('.forminator-pagination');
					}
					if (relation === 'submit' && typeof pagination !== 'undefined') {
						self.toggle_field(relation, 'show', "valid");
					}
					self.toggle_field(relation, action, "valid");
				} else {
					self.toggle_field(relation, action, "invalid");
				}
				self.check_sub_relations(relation, e);
			});
		},

		/**
		 * Register related events
		 *
		 * @since 1.0.3
		 */
		init_events: function () {
			var self = this;
			this.$el.on('forminator.front.condition.restart', function (e) {
				self.on_restart(e);
			});
		},

		/**
		 * Restart conditions
		 *
		 * @since 1.0.3
		 *
		 * @param e
		 */
		on_restart: function (e) {
			// restart condition
			this.$el.find('.forminator-field input, .forminator-row input[type=hidden], .forminator-field select, .forminator-field textarea').trigger( 'forminator.change', 'forminator_emulate_trigger' );
		},

		/**
		 * Add missing relations based on fields.conditions
		 */
		add_missing_relations: function () {
			var self = this;
			var missedRelations = {};
			if (typeof this.settings.fields !== "undefined") {
				var conditionsFields = this.settings.fields;
				Object.keys(conditionsFields).forEach(function (key) {
					var conditions = conditionsFields[key]['conditions'];
					conditions.forEach(function (condition) {
						var relatedField = condition.field;
						if (!self.has_relations(relatedField)) {
							if (typeof missedRelations[relatedField] === 'undefined') {
								missedRelations[relatedField] = [];
							}
							missedRelations[relatedField].push(key);

						} else {
							let relations = self.get_relations(relatedField);
							if ( -1 !== $.inArray( key, relations ) ) {
								return;
							}
							self.settings.relations[relatedField].push( key );
						}
					});
				});
			}
			Object.keys(missedRelations).forEach(function (relatedField) {
				self.settings.relations[relatedField] = missedRelations[relatedField];
			});
		},

		get_field_logic: function (element_id) {
			if (typeof this.settings.fields[element_id] === "undefined") return [];
			return this.settings.fields[element_id];
		},

		has_relations: function (element_id) {
			return typeof this.settings.relations[element_id] !== "undefined";
		},

		get_relations: function (element_id) {
			if (!this.has_relations(element_id)) return [];

			return $.unique( this.settings.relations[element_id] );
		},

		get_field_value: function (element_id) {
            if ( '' === element_id ) {
                return '';
            }

			var $element = this.get_form_field(element_id),
				value = $element.val();

			//check the type of input
			if (this.field_is_radio($element)) {
				value = $element.filter(":checked").val();
			} else if (this.field_is_signature($element)) {
				value = $element.find( "input[id$='_data']" ).val();
			} else if (this.field_is_checkbox($element)) {
				value = [];
				$element.each(function () {
					if ($(this).is(':checked')) {
						value.push($(this).val().toLowerCase());
					}
				});

				// if value is empty, return it as null
                if ( 0 === value.length ) {
                    value = null;
                }
			// Check if the element is a multi-select.
			} else if (this.field_is_select($element) && $element.attr( "multiple" ) ) {
                value = [];
                var selected = $element.find("option").filter(':selected');
                if (selected.length > 0) {
                    selected.each(function () {
                        value.push($(this).val().toLowerCase());
                    });
                } else {
                    value = null;
                }
            } else if ( this.field_is_textarea_wpeditor( $element ) ) {
                if ( typeof tinyMCE !== 'undefined' && tinyMCE.activeEditor ) {
                    value = tinyMCE.activeEditor.getContent();
                }
			} else if ( this.field_has_inputMask( $element ) ) {
				value = parseFloat( $element.inputmask('unmaskedvalue').replace(',','.') );
				if ( 0 <= element_id.indexOf( 'calculation-' ) ) {
					return value;
				}
			} else if ( this.field_is_rating( $element ) ) {
				value = (typeof value === 'string' && value.split("/")[0]) || 0;
				return value;
			} else if ( this.field_is_upload( element_id ) ) {
				var uploaded = $element.closest('.forminator-field-upload');
				value = value.split("\\").pop();
				if ( uploaded.length && $element.attr( "multiple" ) ) {
					let $files = uploaded.find('.forminator-uploaded-file--title');
					value = $files
						.map(function () {
							return $(this).text();
						})
						.get() // convert jQuery object to array
						.join(',');
				}
			}
			if ( value === undefined || value === null || value === '' ) {
				return '';
			}

			return value;
		},

		get_date_field_value: function(element_id){
            if ( '' === element_id ) {
                return '';
            }

			var $element = this.get_form_field(element_id);
			//element may not be a real jQuery element for fake virtual parent date field
			var fake_field = true;
			if( $element instanceof jQuery ) {
				fake_field = false;
				//element may just be the wrapper div of child fields
				if( $element.hasClass('forminator-col') ) {
					fake_field = true;
				}
			}

			var value = "";

			if ( !fake_field && this.field_is_datepicker($element) ){
				value = $element.val();
				//check if formats are accepted
				switch ( $element.data('format') ) {
					case 'dd/mm/yy':
						value = $element.val().split("/").reverse().join("-");
						break;
					case 'dd.mm.yy':
						value = $element.val().split(".").reverse().join("-");
						break;
					case 'dd-mm-yy':
						value = $element.val().split("-").reverse().join("-");
						break;
             	}

				if ( '' !== value ) {
					var formattedDate = new Date(value);
					value = {'year':formattedDate.getFullYear(), 'month':formattedDate.getMonth(), 'date':formattedDate.getDate(), 'day':formattedDate.getDay()};
				}


			} else {

				var parent 	 = ( fake_field === true )? element_id : $element.data('parent');
				var	year 	 = this.get_form_field_value(parent+'-year'),
					mnth 	 = this.get_form_field_value(parent+'-month'),
					day  	 = this.get_form_field_value(parent+'-day');

				if( year !== "" && mnth !== "" && day !== "" ){
					var formattedDate = new Date( year + '-' + mnth + '-' + day );
					value = {'year':formattedDate.getFullYear(), 'month':formattedDate.getMonth(), 'date':formattedDate.getDate(), 'day':formattedDate.getDay() };
				}

			}

			if (!value) return "";

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

		field_is_signature: function($element) {
			var is_signature = false;

			$element.each(function () {
				if ($(this).find('.forminator-field-signature').length > 0) {
					is_signature = true;
					//break
					return false;
				}
			});

			return is_signature;
		},

		field_is_datepicker: function ($element) {
			var is_date = false;
			$element.each(function () {
				if ($(this).hasClass('forminator-datepicker')) {
					is_date = true;
					//break
					return false;
				}
			});

			return is_date;
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

		/* field_is_consent: function ( $element ) {
			var is_consent = false;

			$( 'input[name="' + $element + '"]' ).each(function () {
				if ( $element.indexOf( 'consent' ) >= 0 ) {
					is_consent = true;
					//break
					return false;
				}
			});

			return is_consent;
		}, */

		field_is_select: function ($element) {
			return $element.is('select');
		},

        field_is_textarea_wpeditor: function ($element) {
			var is_textarea_wpeditor = false;
			$element.each(function () {
				if ( $(this).parent( '.wp-editor-container' ).parent( 'div' ).hasClass( 'tmce-active' ) ) {
					is_textarea_wpeditor = true;
					//break
					return false;
				}
			});

			return is_textarea_wpeditor;
		},

        field_is_upload: function ($element) {
			var is_upload = false;

			if ( -1 !== $element.indexOf( 'upload' ) ) {
				is_upload = true;
			}

			return is_upload;
		},

		field_is_rating: function ($element) {
			var is_rating = false;
			$element.each(function () {
				if ( $(this).hasClass('forminator-rating') ) {
					is_rating = true;
					//break
					return false;
				}
			});

			return is_rating;
		},

		// used in forminatorFrontCalculate
		get_form_field: function (element_id) {
			let $form = this.$el;
			if ( $form.hasClass( 'forminator-grouped-fields' ) ) {
				$form = $form.closest( 'form.forminator-ui' );
			}

			//find element by suffix -field on id input (default behavior)
			var $element = $form.find('#' + element_id + '-field');
			if ($element.length === 0) {
				$element = $form.find('.' + element_id + '-payment');
				if ($element.length === 0) {
					//find element by its on name (for radio on singlevalue)
					$element = $form.find('input[name="' + element_id + '"]');
					if ($element.length === 0) {
						// for text area that have uniqid, so we check its name instead
						$element = $form.find('textarea[name="' + element_id + '"]');
						if ($element.length === 0) {
							//find element by its on name[] (for checkbox on multivalue)
							$element = $form.find('input[name="' + element_id + '[]"]');
							if ($element.length === 0) {
								//find element by select name
								$element = $form.find('select[name="' + element_id + '"]');
								if ($element.length === 0) {
									$element = $form.find('select[name="' + element_id + '[]"]');
									if ($element.length === 0) {
										//find element by direct id (for name field mostly)
										//will work for all field with element_id-[somestring]
										$element = $form.find('#' + element_id);
									}
								}
							}
						}
					}
				}
			}

			return $element;
		},

		// Extension of get_form_field to get value
		get_form_field_value: function (element_id) {
			//find element by suffix -field on id input (default behavior)
			var $form_id = this.$el.data( 'form-id' ),
				$uid 	 = this.$el.data( 'uid' ),
				$element = this.$el.find('#forminator-form-' + $form_id + '__field--' + element_id + '_' + $uid );
			if ($element.length === 0) {
				var $element = this.$el.find('#' + element_id + '-field' );
				if ($element.length === 0) {
					//find element by its on name (for radio on singlevalue)
					$element = this.$el.find('input[name="' + element_id + '"]');
					if ($element.length === 0) {
						// for text area that have uniqid, so we check its name instead
						$element = this.$el.find('textarea[name="' + element_id + '"]');
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
			}

			return $element.val();
		},

		is_numeric: function (number) {
			return !isNaN(parseFloat(number)) && isFinite(number);
		},

		is_date_rule: function(operator){

			var dateRules  = ['day_is', 'day_is_not', 'month_is', 'month_is_not', 'is_before', 'is_after', 'is_before_n_or_more_days', 'is_before_less_than_n_days', 'is_after_n_or_more_days', 'is_after_less_than_n_days'];

			return dateRules.includes( operator );

		},

		has_siblings: function(element){
            if ( '' === element ) {
                return false;
            }

			element = this.get_form_field(element);
			if( element.data('parent') ) return true;
			return false;

		},

		trigger_fake_parent_date_field: function(element_id){
			var	element = this.get_form_field(element_id),
				parent  = element.data('parent');
				this.process_relations( parent, {}, {});
		},

		trigger_siblings: function(element_id){
			var self = this,
				element = self.get_form_field(element_id),
				parent = element.data('parent'),
				siblings = [];

			siblings 	= [parent+'-year', parent+'-month', parent+'-day'];

			$.each(siblings, function( index, sibling ) {
			  	if( element_id !== sibling && self.has_relations(sibling) ){
					self.get_form_field(sibling).trigger('change');
				}
			});

		},

		is_applicable_rule: function (condition) {
			if (typeof condition === "undefined") return false;

			if( this.is_date_rule( condition.operator ) ){
				var value1 = this.get_date_field_value(condition.field);
			}else{
				var value1 = this.get_field_value(condition.field);
			}

			var value2 = condition.value,
				operator = condition.operator
			;

			const $element_id = this.get_form_field(condition.field);

			return ! forminatorUtils().is_hidden($element_id) && this.is_matching(value1, value2, operator);
		},

		is_matching: function (value1, value2, operator) {
			// Match values case
			var isArrayValue = Array.isArray(value1);

			// Match values case
			if (typeof value1 === 'string') {
				value1 = value1.toLowerCase();
			}

			if(typeof value2 === 'string'){
				value2 = value2.toLowerCase();

				if(operator === 'month_is' || operator === 'month_is_not'){
					var months = {
						'jan':0,
						'feb':1,
						'mar':2,
						'apr':3,
						'may':4,
						'jun':5,
						'jul':6,
						'aug':7,
						'sep':8,
						'oct':9,
						'nov':10,
						'dec':11
					};
					if($.inArray(value2, months)){
						value2 = months[ value2 ];
					}
				}
				if(operator === 'day_is' || operator === 'day_is_not'){
					var days = {
						'su':0,
						'mo':1,
						'tu':2,
						'we':3,
						'th':4,
						'fr':5,
						'sa':6
					};
					if($.inArray(value2, days)){
						value2 = days[ value2 ];
					}
				}
			}

			switch (operator) {
				case "is":
					if (!isArrayValue) {
						if ( this.is_numeric( value1 ) && this.is_numeric( value2 ) ) {
							return Number( value1 ) === Number( value2 );
						}

						return value1 === value2;
					} else {
						return $.inArray(value2, value1) > -1;
					}
				case "is_not":
					if (!isArrayValue) {
						if ( this.is_numeric( value1 ) && this.is_numeric( value2 ) ) {
							return Number( value1 ) !== Number( value2 );
						}

						return value1 !== value2;
					} else {
						return $.inArray(value2, value1) === -1;
					}
				case "is_great":
					// typecasting to integer, with return `NaN` when its literal chars, so `is_numeric` will fail
					value1 = +value1;
					value2 = +value2;
					return this.is_numeric(value1) && this.is_numeric(value2) ? value1 > value2 : false;
				case "is_less":
					value1 = +value1;
					value2 = +value2;
					return this.is_numeric(value1) && this.is_numeric(value2) ? value1 < value2 : false;
				case "contains":
					return this.contains(value1, value2);
				case "does_not_contain":
					return !this.contains(value1, value2);
				case "starts":
					return value1.startsWith(value2);
				case "ends":
					return value1.endsWith(value2);
				case "month_is":
					return value1.month === value2;
				case "month_is_not":
					return value1.month !== value2;
				case "day_is":
					return value1.day === value2;
				case "day_is_not":
					return value1.day !== value2;
				case "is_before":
					return this.date_is_smaller( value1, value2 );
				case "is_after":
					return this.date_is_grater( value1, value2 );
				case "is_before_n_or_more_days":
					return this.date_is_n_days_before_current_date( value1, value2 );
				case "is_before_less_than_n_days":
					return this.date_is_less_than_n_days_before_current_date( value1, value2 );
				case "is_after_n_or_more_days":
					return this.date_is_n_days_after_current_date( value1, value2 );
				case "is_after_less_than_n_days":
					return this.date_is_less_than_n_days_after_current_date( value1, value2 );
			}

			// Return false if above are not valid
			return false;
		},

		contains: function (field_value, value) {
			return field_value.toLowerCase().indexOf(value) >= 0;
		},

		date_is_grater: function( date1, date2 ) {
			date1 = this.set_date_as_utc_time( date1 );
			return forminatorDateUtil.compare( date1, date2 ) === 1;
		},

		set_date_as_utc_time( date ) {
			if (
				undefined !== date.month &&
				undefined !== date.date &&
				undefined !== date.year
			) {
				const utcDate = new Date(
					`${ date.month + 1 }/${ date.date }/${ date.year } UTC`
				);
				date = utcDate.getTime();
			}
			return date;
		},

		date_is_smaller: function( date1, date2 ) {
			date1 = this.set_date_as_utc_time( date1 );
			return forminatorDateUtil.compare( date1, date2 ) === -1;
		},

		date_is_equal: function( date1, date2 ) {
			return forminatorDateUtil.compare( date1, date2 ) === 0;
		},

		date_is_n_days_before_current_date: function( date1, n ) {
			n = parseInt( n );
			var current_date = this.get_current_date();
			var diff = forminatorDateUtil.diffInDays( date1, current_date );
			if( isNaN( diff ) ) {
				return false;
			}
			if( n === 0 ) {
				return ( diff === n );
			} else {
				return ( diff >= n );
			}
		},

		date_is_less_than_n_days_before_current_date: function( date1, n ) {
			n = parseInt( n );
			var current_date = this.get_current_date();
			var diff = forminatorDateUtil.diffInDays( date1, current_date );
			if( isNaN( diff ) ) {
				return false;
			}

			return ( diff < n && diff > 0 );
		},

		date_is_n_days_after_current_date: function( date1, n ) {
			n = parseInt( n );
			var current_date = this.get_current_date();
			var diff = forminatorDateUtil.diffInDays( current_date, date1 );
			if( isNaN( diff ) ) {
				return false;
			}
			if( n === 0 ) {
				return ( diff === n );
			} else {
				return ( diff >= n );
			}
		},

		date_is_less_than_n_days_after_current_date: function( date1, n ) {
			n = parseInt( n );
			var current_date = this.get_current_date();
			var diff = forminatorDateUtil.diffInDays( current_date, date1 );
			if( isNaN( diff ) ) {
				return false;
			}

			return ( diff < n && diff > 0 );
		},

		get_current_date: function() {
			return new Date();
		},

		toggle_field: function (element_id, action, type) {
			var self = this,
				$element_id = this.get_form_field(element_id),
				$column_field = $element_id.closest('.forminator-col'),
				$hidden_upload = $column_field.find('.forminator-input-file-required'),
				$hidden_signature = $column_field.find('[id ^=ctlSignature][id $=_data]'),
				$hidden_wp_editor = $column_field.find('.forminator-wp-editor-required'),
				$row_field = $column_field.closest('.forminator-row'),
				$sub_row_field = $column_field.closest('.forminator-row-inside'), // Used for Email Confirmation.
				$sub_row_fields = $sub_row_field.add($sub_row_field.parent()), // Include Email Confirmation parent column.
				$pagination_next_field = this.$el.find('.forminator-pagination-footer').find('.forminator-button-next'),
				submit_selector = 'submit' === element_id ? '.forminator-button-submit' : '#forminator-paypal-submit',
				$pagination_field = this.$el.find( submit_selector )
				;

			// Handle page-break elements specially
			if (element_id.startsWith('page-break-')) {
				this.toggle_page_break_field(element_id, action, type);
				return;
			}

			// Handle show action
			if (action === "show") {
				if (type === "valid") {
					$row_field.removeClass('forminator-hidden');
					$sub_row_fields.removeClass('forminator-hidden');
					$column_field.removeClass('forminator-hidden');
					$pagination_next_field.removeClass('forminator-hidden');
					if ($hidden_upload.length > 0) {
						$hidden_upload.addClass('do-validate');
					}
					if ($hidden_wp_editor.length > 0) {
						$hidden_wp_editor.addClass('do-validate');
					}
					if ($hidden_signature.length > 0) {
						$hidden_signature.addClass('do-validate');
					}
					setTimeout(
						function() {
							$pagination_field = self.$el.find( submit_selector );
							if ( 'submit' === element_id ) {
								$pagination_field.removeClass('forminator-hidden');
							}
							if ( 0 === element_id.indexOf( 'paypal' ) ) {
								self.$el.find( '.forminator-button-submit' ).addClass( 'forminator-hidden' );
								$pagination_field.removeClass( 'forminator-hidden' );
							}
						},
						100
					);
				} else {
					$column_field.addClass('forminator-hidden');
					setTimeout(
						function() {
							$pagination_field = self.$el.find( submit_selector );
							if ( 'submit' === element_id ) {
								$pagination_field.addClass('forminator-hidden');
							}
							if ( 0 === element_id.indexOf( 'paypal' ) ) {
								self.$el.find( '.forminator-button-submit' ).removeClass( 'forminator-hidden' );
								$pagination_field.addClass('forminator-hidden');
							}
						},
						100
					);
					if ($hidden_upload.length > 0) {
						$hidden_upload.removeClass('do-validate');
					}
					if ($hidden_wp_editor.length > 0) {
						$hidden_wp_editor.removeClass('do-validate');
					}
					if ($hidden_signature.length > 0) {
						$hidden_signature.removeClass('do-validate');
					}
					if ($sub_row_field.find('> .forminator-col:not(.forminator-hidden)').length === 0) {
						$sub_row_fields.addClass('forminator-hidden');
					}
					if ($row_field.find('> .forminator-col:not(.forminator-hidden)').length === 0) {
						$row_field.addClass('forminator-hidden');
					}
				}
			}

			// Handle hide action
			if (action === "hide") {
				if (type === "valid") {
					$column_field.addClass('forminator-hidden');
					$pagination_field.addClass('forminator-hidden');
					if ($hidden_upload.length > 0) {
						$hidden_upload.removeClass('do-validate');
					}
					if ($hidden_wp_editor.length > 0) {
						$hidden_wp_editor.removeClass('do-validate');
					}
					if ($hidden_signature.length > 0) {
						$hidden_signature.removeClass('do-validate');
					}
					if ($row_field.find('> .forminator-col:not(.forminator-hidden)').length === 0) {
						$row_field.addClass('forminator-hidden');
					}
					if ($sub_row_field.find('> .forminator-col:not(.forminator-hidden)').length === 0) {
						$sub_row_fields.addClass('forminator-hidden');
					}
					setTimeout(
						function() {
							$pagination_field = self.$el.find( submit_selector );
							if ( 'submit' === element_id ) {
								$pagination_field.addClass('forminator-hidden');
							}
							if ( 0 === element_id.indexOf( 'paypal' ) ) {
								self.$el.find( '.forminator-button-submit' ).removeClass( 'forminator-hidden' );
								$pagination_field.addClass( 'forminator-hidden' );
							}
						},
						100
					);
				} else {
					$row_field.removeClass('forminator-hidden');
					$sub_row_fields.removeClass('forminator-hidden');
					$column_field.removeClass('forminator-hidden');
					$pagination_field.removeClass('forminator-hidden');
					if ($hidden_upload.length > 0) {
						$hidden_upload.addClass('do-validate');
					}
					if ($hidden_wp_editor.length > 0) {
						$hidden_wp_editor.addClass('do-validate');
					}
					if ($hidden_signature.length > 0) {
						$hidden_signature.addClass('do-validate');
					}
					setTimeout(
						function() {
							$pagination_field = self.$el.find( submit_selector );
							if ( 'submit' === element_id ) {
								$pagination_field.removeClass('forminator-hidden');
							}
							if ( 0 === element_id.indexOf( 'paypal' ) ) {
								self.$el.find( '.forminator-button-submit' ).addClass( 'forminator-hidden' );
								$pagination_field.removeClass( 'forminator-hidden' );
							}
						},
						100
					);
				}
			}

			this.$el.trigger('forminator:field:condition:toggled');

			this.toggle_confirm_field( element_id, action, type );
		},

		/**
		 * Handle page-break field visibility
		 *
		 * @param {string} element_id - The page-break element ID (e.g., 'page-break-1')
		 * @param {string} action - The action ('show' or 'hide')
		 * @param {string} type - The type ('valid' or 'invalid')
		 */
		toggle_page_break_field: function (element_id, action, type) {
			const HIDDEN_CLASSES = 'forminator-hidden forminator-page-hidden';

			// Find the pagination div that contains this page-break
			var $current_pagination = this.$el.find('div.forminator-pagination[data-name="' + element_id + '"]');

			if ($current_pagination.length === 0) {
				console.warn('Could not find pagination div for page-break:', element_id);
				return;
			}

			// Find the next pagination div after the current one
			var $actual_pagination = $current_pagination.next('div.forminator-pagination');

			if ($actual_pagination.length === 0) {
				console.warn('Could not find next pagination div after page-break:', element_id);
				return;
			}

			// Check if the actual pagination is the last one (no pagination after it)
			if ($actual_pagination.next('div.forminator-pagination').length === 0) {
				// This is the last pagination div, do nothing
				return;
			}

			// Get the step number from the actual pagination
			var step_number = $actual_pagination.attr('data-step');
			var $step_element = this.$el.find('.forminator-step-' + step_number);
			var $break_element = $step_element.next('.forminator-break');

			// Handle show action
			if (action === "show") {
				if (type === "valid") {
					// Show the actual pagination div
					$actual_pagination.removeClass(HIDDEN_CLASSES);
					$actual_pagination.removeAttr('hidden');

					// Show the step element and break
					if ($step_element.length) {
						$step_element.removeClass(HIDDEN_CLASSES);
					}
					if ($break_element.length) {
						$break_element.removeClass(HIDDEN_CLASSES);
					}
				} else {
					// Invalid show - hide the actual pagination div
					$actual_pagination.addClass(HIDDEN_CLASSES);
					$actual_pagination.attr('hidden', 'hidden');

					// Hide the step element and break
					if ($step_element.length) {
						$step_element.addClass(HIDDEN_CLASSES);
					}
					if ($break_element.length) {
						$break_element.addClass(HIDDEN_CLASSES);
					}
				}
			}

			// Handle hide action
			if (action === "hide") {
				if (type === "valid") {
					// Hide the actual pagination div
					$actual_pagination.addClass(HIDDEN_CLASSES);
					$actual_pagination.attr('hidden', 'hidden');

					// Hide the step element and break
					if ($step_element.length) {
						$step_element.addClass(HIDDEN_CLASSES);
					}
					if ($break_element.length) {
						$break_element.addClass(HIDDEN_CLASSES);
					}
				} else {
					// Invalid hide - show the actual pagination div
					$actual_pagination.removeClass(HIDDEN_CLASSES);
					$actual_pagination.removeAttr('hidden');

					// Show the step element and break
					if ($step_element.length) {
						$step_element.removeClass(HIDDEN_CLASSES);
					}
					if ($break_element.length) {
						$break_element.removeClass(HIDDEN_CLASSES);
					}
				}
			}

			this.$el.trigger('forminator:field:condition:toggled');
			this.$el.trigger('forminator:page-break:toggled');
		},

		clear_value: function(element_id, e) {
			var $element = this.get_form_field(element_id),
				value = this.get_field_value(element_id)
			;
			if ( $element.hasClass('forminator-cleared-value') ) {
				return;
			}
			$element.addClass('forminator-cleared-value');

			// Execute only on human action
			if (e.originalEvent !== undefined) {
				if (this.field_is_radio($element)) {
					$element.attr('data-previous-value', value);
					$element.removeAttr('checked');
				} else if (this.field_is_checkbox($element)) {
					$element.each(function () {
						$(this).attr('data-previous-value', value);
						$(this).prop('checked', false);
					});
				} else {
					$element.attr('data-previous-value', value);
					$element.val('');
				}
			}
		},

		restore_value: function(element_id, e) {
			var $element = this.get_form_field(element_id),
				value = $element.attr('data-previous-value')
			;
			if ( ! $element.hasClass('forminator-cleared-value') ) {
				return;
			}

			// Execute only on human action
			if (e.originalEvent === undefined) {
				return;
			}

			$element.removeClass('forminator-cleared-value');

			// Return after class is removed if field is upload
			if ( this.field_is_upload( element_id ) ) {
				return;
			}

			if(!value) return;

			if (this.field_is_radio($element)) {
				$element.val([value]);
			} else if (this.field_is_checkbox($element)) {
				$element.each(function () {
					var value = $(this).attr('data-previous-value');

					if (!value) return;

					if (value.indexOf($(this).val().toLowerCase()) >= 0) {
						$(this).prop( "checked", true );
					}
				});
			} else {
				$element.val(value);
			}
		},

		check_sub_relations: function (relation, e){
			var self          = this,
				sub_relations = self.get_relations(relation)
            ;

			if (!sub_relations.length){
				return;
			}

			this.restore_value(relation, e);
			this.textareaFix(this.$el, relation, e);

			sub_relations.forEach(function (sub_relation) {
				var logic = self.get_field_logic(sub_relation),
					action = logic.action,
					rule = logic.rule,
					conditions = logic.conditions, // Conditions rules
					matches = 0 // Number of matches
				;

				conditions.forEach(function (condition) {
					// If rule is applicable save in matches
					if (self.is_applicable_rule(condition)) {
						matches++;
					}
				});

				if ((rule === "all" && matches === conditions.length) || (rule === "any" && matches > 0)) {
					self.toggle_field(sub_relation, action, "valid");
				}else{
					self.toggle_field(sub_relation, action, "invalid");
				}
				self.check_sub_relations(sub_relation, e);
			});
		},

		paypal_button_condition: function() {
			var paymentElement = this.$el.find('.forminator-paypal-row'),
				paymentPageElement = this.$el.find('.forminator-pagination-footer').find('.forminator-button-paypal');
			const $stripe_element = this.$el.find('.forminator-field-stripe-ocs:not(.forminator-hidden), .forminator-field-stripe:not(.forminator-hidden)');
			this.$el.find('.forminator-button-submit').closest('.forminator-row').removeClass('forminator-hidden');
			if( paymentElement.length > 0 && $stripe_element.length === 0 ) {
				if( ! paymentElement.hasClass('forminator-hidden') ) {
					this.$el.find('.forminator-button-submit').closest('.forminator-row').addClass('forminator-hidden');
				}
			}
			if ( paymentPageElement.length > 0 ) {
				if( paymentPageElement.hasClass('forminator-hidden') ) {
					this.$el.find('.forminator-button-submit').removeClass('forminator-hidden');
				} else{
					this.$el.find('.forminator-button-submit').addClass('forminator-hidden');
				}
			}
		},

		maybe_clear_upload_container: function() {
			this.$el.find( '.forminator-row.forminator-hidden input[type="file"]' ).each( function () {
				if ( '' === $(this).val() ) {
					if ( $(this).parent().hasClass( 'forminator-multi-upload' ) ) {
						$(this).parent().siblings( '.forminator-uploaded-files' ).empty();
					} else {
						$(this).siblings( 'span' ).text( $(this).siblings( 'span' ).data( 'empty-text' ) );
						$(this).siblings( '.forminator-button-delete' ).hide();
					}
				}
			});
		},

        // Fixes textarea bug with labels when using Material design style
		textareaFix: function (form ,relation, e){
			var label = $( '#' + relation + ' .forminator-label' )
            ;

            if ( relation.includes( 'textarea' ) && form.hasClass( 'forminator-design--material' ) && 0 < label.length ) {
                var materialTextarea = $( '#' + relation + ' .forminator-textarea'),
                    labelPaddingTop  = label.height() + 9 // Based on forminator-form.js
                ;

                label.css({
                  'padding-top': labelPaddingTop + 'px'
                });

                materialTextarea.css({
                  'padding-top': labelPaddingTop + 'px'
                });
            }
		},

		// Maybe toggle confirm field if necessary.
		toggle_confirm_field: function ( element_id, action, type ) {
			const confirmFieldId = 'confirm_' + element_id,
				$confirmField = this.get_form_field( confirmFieldId );

			if ( $confirmField.length ) {
				this.toggle_field(confirmFieldId, action, type);
			}
		},
	});

	// A really lightweight plugin wrapper around the constructor,
	// preventing against multiple instantiations
	$.fn[pluginName] = function (options, calendar) {
		return this.each(function () {
			if (!$.data(this, pluginName)) {
				$.data(this, pluginName, new ForminatorFrontCondition(this, options, calendar));
			}
		});
	};

})(jQuery, window, document);
