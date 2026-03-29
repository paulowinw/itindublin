/**********
 * Common functions
 *
 ***********/
class forminatorFrontUtils {

	constructor() {}

	field_is_checkbox($element) {
		var is_checkbox = false;
		$element.each(function () {
			if (jQuery(this).attr('type') === 'checkbox') {
				is_checkbox = true;
				//break
				return false;
			}
		});

		return is_checkbox;
	}

	field_is_radio($element) {
		var is_radio = false;
		$element.each(function () {
			if (jQuery(this).attr('type') === 'radio') {
				is_radio = true;
				//break
				return false;
			}
		});

		return is_radio;
	}

	field_is_select($element) {
		return $element.is('select');
	}

	field_has_inputMask( $element ) {
		var hasMask = false;

		$element.each(function () {
			if ( undefined !== jQuery( this ).attr( 'data-inputmask' ) ) {
				hasMask = true;
				//break
				return false;
			}
		});

		return hasMask;
	}

	get_field_value( $element ) {
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
				if (jQuery(this).is(':checked')) {
					calculation = jQuery(this).data('calculation');
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
		} else if ( this.field_has_inputMask( $element ) ) {
			value = parseFloat( $element.inputmask('unmaskedvalue').replace(',','.') );
		} else if ( $element.length ) {
			var number = $element.val();
			value = parseFloat( number.replace(',','.') );
		}

		return isNaN(value) ? 0 : value;
	}

	show_hide_custom_input( selector, field_type ) {
		if( ! selector ) {
			return;
		}
		let $elements = null;
		if( field_type === 'select2' || field_type === 'select' ) {
			$elements = jQuery( selector );
		} else {
			$elements = jQuery( selector ).closest( '.forminator-field' ).find( 'input[type="checkbox"]:checked, input[type="radio"]:checked' );
		}

		if( ! $elements.length ) {
			// If no elements found, hide all custom inputs.
			jQuery( selector ).closest( '.forminator-field' ).find( '.forminator-custom-input' ).hide();
			return;
		}

		$elements.each( function() {
			if( jQuery( this ).val() && jQuery( this ).val().includes( 'custom_option' ) ) {
				// Display custom option input.
				jQuery( this ).closest( '.forminator-field' ).find( '.forminator-custom-input' ).show();
			} else {
				// Hide custom option input.
				jQuery( this ).closest( '.forminator-field' ).find( '.forminator-custom-input' ).hide();
			}
		});
	}

	// Add error message.
	add_error_message($description, $column, errorMarkup) {
		// If the description is empty or description placement is above input.
		if ( 0 === $description.length || $description.next().length > 0 ) {
			// Append the error markup to the column.
			$column.append( errorMarkup );
		} else {
			// Otherwise, insert the error markup before the description.
			jQuery( errorMarkup ).insertBefore( $description );
		}
	}

	is_hidden( $element_id ) {
		const $column_field = $element_id.closest('.forminator-col'),
			$group_field = $element_id.closest('.forminator-field-group'),
			$pagination_field = $element_id.closest('.forminator-pagination'),
			$address_field = $element_id.closest('.forminator-field-address'),
			$name_field = $element_id.closest('.forminator-field-name'),
			$row_field = $column_field.closest('.forminator-row')
		;

		if( $column_field.hasClass("forminator-hidden-calculator") ) {
			// If it's a calculation field with isHidden option - it means it's not hidden by visibility conditions. Always return false.
			return false;
		}

		if( $row_field.hasClass("forminator-hidden") || $column_field.hasClass("forminator-hidden") ) {
			return true;
		}

		if( ( $group_field.length > 0 && $group_field.hasClass( 'forminator-hidden' ) )
			|| ( $address_field.length > 0 && $address_field.hasClass( 'forminator-hidden' ) )
			|| ( $name_field.length > 0 && $name_field.hasClass( 'forminator-hidden' ) ) ) {
			return true;
		}

		if( $pagination_field.length > 0 && $pagination_field.hasClass( 'forminator-page-hidden' ) ) {
			return true;
		}

		return false;
	}
}

if (window['forminatorUtils'] === undefined) {
	window.forminatorUtils = function () {
		return new forminatorFrontUtils();
	}
}