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
	var pluginName = "forminatorFrontDatePicker",
		defaults = {};

	// The actual plugin constructor
	function ForminatorFrontDatePicker(element, options) {
		this.element = element;
		this.$el = $(this.element);

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
	$.extend(ForminatorFrontDatePicker.prototype, {
		init: function () {
			var self = this,
				dateFormat = this.$el.data('format'),
				restrictType = this.$el.data('restrict-type'),
				restrict = this.$el.data('restrict'),
				restrictedDays = this.$el.data('restrict'),
				minYear = this.$el.data('start-year'),
				maxYear = this.$el.data('end-year'),
				pastDates = this.$el.data('past-dates'),
				dateValue = this.$el.val(),
				startOfWeek = this.$el.data('start-of-week'),
				minDate = this.$el.data('start-date'),
				maxDate = this.$el.data('end-date'),
				startField = this.$el.data('start-field'),
				endField = this.$el.data('end-field'),
				startOffset = this.$el.data('start-offset'),
				endOffset = this.$el.data('end-offset'),
				disableDate = this.$el.data('disable-date'),
				disableRange = this.$el.data('disable-range');

			//possible restrict only one
			if (!isNaN(parseFloat(restrictedDays)) && isFinite(restrictedDays)) {
				restrictedDays = [restrictedDays.toString()];
			} else {
				restrictedDays = restrict.split(',');
			}
			disableDate = disableDate.split(',');
			disableRange = disableRange.split(',');

			if (!minYear) {
				minYear = "c-95";
			}
			if (!maxYear) {
				maxYear = "c+95";
			}
			var disabledWeekDays = function ( current_date ) {
				return self.restrict_date( restrictedDays, disableDate, disableRange, current_date );
			};

			var parent = this.$el.closest('.forminator-custom-form'),
				add_class = "forminator-calendar";

			if ( parent.hasClass('forminator-design--default') ) {
				add_class = "forminator-calendar--default";
			} else if ( parent.hasClass('forminator-design--material') ) {
				add_class = "forminator-calendar--material";
			} else if ( parent.hasClass('forminator-design--flat') ) {
				add_class = "forminator-calendar--flat";
			} else if ( parent.hasClass('forminator-design--bold') ) {
				add_class = "forminator-calendar--bold";
			} else if ( parent.hasClass('forminator-design--basic') ) {
				add_class = "forminator-calendar--basic";
			}

			// if parent has data-color-option add it to the datepicker
			if ( parent.data( 'color-option' ) ) {
				add_class += ' forminator-color-option--' + parent.data( 'color-option' );
			}


			this.$el.datepicker({
				"beforeShow": function (input, inst) {
					// Check for any popup modal (Hustle, Bootstrap, Elementor, or other common modals)
					var popup = $(this).closest(
						'.modal, .elementor-popup-modal, .mfp-wrap, .fancybox-container, [role="dialog"], [data-popup]'
					);

					// Remove all Hustle UI related classes
					( inst.dpDiv ).removeClass( function( index, css ) {
						return ( css.match ( /\bhustle-\S+/g ) || []).join( ' ' );
					});

					// Remove all Forminator UI related classes
					( inst.dpDiv ).removeClass( function( index, css ) {
						return ( css.match ( /\bforminator-\S+/g ) || []).join( ' ' );
					});
					( inst.dpDiv ).addClass( 'forminator-custom-form-' + parent.data( 'form-id' ) + ' ' + add_class );
					// Enable/disable past dates
					if ( 'disable' === pastDates ) {
						$(this).datepicker( 'option', 'minDate', dateValue );
					} else {
						$(this).datepicker( 'option', 'minDate', null );
					}
					if( minDate ) {
						var min_date = new Date( minDate.replace(/-/g, '\/').replace(/T.+/, '') );
						$(this).datepicker( 'option', 'minDate', min_date );
					}
					if( maxDate ) {
						var max_date = new Date( maxDate.replace(/-/g, '\/').replace(/T.+/, '') );
						$(this).datepicker( 'option', 'maxDate', max_date );
					}
					if( startField ) {
						var startDateVal = self.getLimitDate( startField, startOffset );
						if( 'undefined' !== typeof startDateVal ) {
							$(this).datepicker( 'option', 'minDate', startDateVal );
						}
					}

					if( endField ) {
						var endDateVal = self.getLimitDate( endField, endOffset );
						if( 'undefined' !== typeof endDateVal ) {
							$(this).datepicker( 'option', 'maxDate', endDateVal );
						}
					}

					// Positioning inside popup modals
					if( popup.length ) {
						$(input).after($('#ui-datepicker-div'));
						setTimeout(function() {
							inst.dpDiv.position({
								my: 'left top',
								at: 'left bottom',
								of: input,
								collision: 'flipfit'
							});
						}, 0);
					} else {
						$('body').append($('#ui-datepicker-div'));
					}
				},
				"beforeShowDay": disabledWeekDays,
				"monthNames": datepickerLang.monthNames,
				"monthNamesShort": datepickerLang.monthNamesShort,
				"dayNames": datepickerLang.dayNames,
				"dayNamesShort": datepickerLang.dayNamesShort,
				"dayNamesMin": datepickerLang.dayNamesMin,
				"changeMonth": true,
				"changeYear": true,
				"dateFormat": dateFormat,
				"yearRange": minYear + ":" + maxYear,
				"minDate": new Date(minYear, 0, 1),
				"maxDate": new Date(maxYear, 11, 31),
				"firstDay" : startOfWeek,
				"onClose": function () {
					//Called when the datepicker is closed, whether or not a date is selected
					$(this).valid();
				},
			});

			//Disables google translator for the datepicker - this prevented that when selecting the date the result is presented as follows: NaN/NaN/NaN
			$('.ui-datepicker').addClass('notranslate');
		},

		getLimitDate: function ( dependentField, offset ) {
			var fieldVal = $('input[name ="'+ dependentField + '"]').val();
			if( typeof fieldVal !== 'undefined' ) {
				var DateFormat = $('input[name ="'+ dependentField + '"]').data('format').replace(/y/g, 'yy'),
					sdata = offset.split('_'),
					newDate = moment( fieldVal, DateFormat.toUpperCase() );
				if( '-' === sdata[0] ) {
					newDate = newDate.subtract( sdata[1], sdata[2] );
				} else {
					newDate = newDate.add( sdata[1], sdata[2] );
				}
				return newDate.toDate();
			}
		},

		restrict_date: function ( restrictedDays, disableDate, disableRange, date ) {
			var hasRange = true,
				day = date.getDay(),
				date_string = jQuery.datepicker.formatDate('mm/dd/yy', date);

			if ( 0 !== disableRange[0].length ) {
				for ( var i = 0; i < disableRange.length; i++ ) {

					var disable_date_range = disableRange[i].split("-"),
						start_date = new Date( disable_date_range[0].trim() ),
						end_date = new Date( disable_date_range[1].trim() );
					if ( date >= start_date && date <= end_date ) {
						hasRange = false;
						break;
					}
				}
			}

			if ( -1 !== restrictedDays.indexOf( day.toString() ) ||
				-1 !== disableDate.indexOf( date_string ) ||
				false === hasRange
			) {
				return [false, "disabledDate"]
			} else {
				return [true, "enabledDate"]
			}
		},
	});

	// A really lightweight plugin wrapper around the constructor,
	// preventing against multiple instantiations
	$.fn[pluginName] = function (options) {
		return this.each(function () {
			if (!$.data(this, pluginName)) {
				$.data(this, pluginName, new ForminatorFrontDatePicker(this, options));
			}
		});
	};
})(jQuery, window, document);