// the semi-colon before function invocation is a safety net against concatenated
// scripts and/or other plugins which may not be closed properly.
;// noinspection JSUnusedLocalSymbols
(function ($, window, document, undefined) {

	"use strict";

	// Trigger repeater functions after form load.
	forminator_handle_all_group_field_copies();
	forminator_add_listener_on_repeater_add_remove();

	// Trigger repeater functions after form load using Ajax.
	$( document ).on( 'after.load.forminator', () => {
		forminator_handle_all_group_field_copies();
		forminator_add_listener_on_repeater_add_remove();
	} );

	// Trigger repeater functions after elementor popup show.
	$( document ).on( 'elementor/popup/show', () => {
		forminator_handle_all_group_field_copies();
		forminator_add_listener_on_repeater_add_remove();
	} );

	function forminator_handle_all_group_field_copies() {
	setTimeout( function() {
		// Init Group fields. Clone group fields if minimum more than 1.
		$( 'div.forminator-all-group-copies' ).each( function() {
			const groupField = $( this ),
				form = groupField.closest( 'form.forminator-custom-form' ),
				firstBlock = groupField.find( '>.forminator-grouped-fields:first-child' ),
				fieldOptions = firstBlock.data('options');

			if ( ! fieldOptions.is_repeater ) {
				return;
			}

			// Initialize pre-existing cloned groups (from draft/preview).
			groupField.find( '>.forminator-grouped-fields[data-suffix]' ).each( function() {
				$( this ).trigger( 'forminator-clone-group' );
			} );
			// Restart the conditions to reset the visibility of the cloned group fields.
			form.trigger( 'forminator.front.condition.restart' );

			if ( 'variable' === fieldOptions.min_type ) {
				const dependMinFromField = form.find( '[name="' + fieldOptions.min + '"]' );

				// Add handler on changing min limit field.
				dependMinFromField.on( 'change', function( e ) {
					const changedMinField = $( e.target );
					forminatorChangedRepeaterMin( groupField, forminatorUtils().get_field_value( changedMinField ) );
				} ).trigger( 'change' );
			} else {
				forminatorChangedRepeaterMin( groupField, fieldOptions.min );
			}

			if ( 'variable' === fieldOptions.max_type ) {
				const dependMaxFromField = form.find( '[name="' + fieldOptions.max + '"]' );

				// Add handler on changing max limit field.
				dependMaxFromField.on( 'change', function( e ) {
					const changedMaxField = $( e.target );
					forminatorChangedRepeaterMax( groupField, forminatorUtils().get_field_value( changedMaxField ) );
				} );
			}

			forminatorHideIrrelevantActions( fieldOptions, groupField );

		} );
	}, 100 );
	}

	// Click on Add Item \ Remove Item.
	function forminator_add_listener_on_repeater_add_remove() {
	$( 'form.forminator-custom-form' ).on( 'click', '.forminator-repeater-remove, .forminator-repeater-add', function( e ) {
		e.stopImmediatePropagation();
		e.preventDefault();

		const actionButton = $( this ),
			currentBlock = actionButton.closest( '.forminator-grouped-fields' ),
			fieldOptions = currentBlock.data('options'),
			groupField = actionButton.closest( '.forminator-all-group-copies' ),
			firstBlock = groupField.find( '>.forminator-grouped-fields:first-child' );

		// Click Add Item.
		if ( actionButton.hasClass( 'forminator-repeater-add' ) ) {
			forminatorCloneItem( groupField );
		}

		// Click Remove Item.
		if ( actionButton.hasClass( 'forminator-repeater-remove' ) ) {
			forminatorRemoveItem( currentBlock, fieldOptions );
		}

		forminatorHideIrrelevantActions( fieldOptions, groupField );
	});
	}

	/**
	 * Remove item
	 */
	function forminatorRemoveItem( removingBlock, fieldOptions ) {
		const groupField = removingBlock.closest( '.forminator-all-group-copies' ),
			blockAmount = groupField.find( '>.forminator-grouped-fields' ).length,
			form = groupField.closest( 'form.forminator-custom-form' ),
			min = forminatorGetMin( fieldOptions, form );

		if ( min >= blockAmount ) {
			return false;
		}

		// If removing the first element
		if ( ! removingBlock.prev().length ) {
			// The first Block isn't possible to remove because visibility conditions of other fields can be based on this fields.
			return false;
		}

		removingBlock.remove();
		form.trigger( 'forminator-group-item-removed' );
		form.trigger( 'forminator:recalculate' );
	}


	/**
	 * Clone item
	 */
	function forminatorCloneItem( groupField ) {
		const firstBlock = groupField.find( '>.forminator-grouped-fields:first-child' ),
			fieldOptions = firstBlock.data('options'),
			form = groupField.closest( 'form.forminator-custom-form' ),
			blockAmount = groupField.find( '>.forminator-grouped-fields' ).length,
			max = forminatorGetMax( fieldOptions, form );

		if ( ! firstBlock.length || max <= blockAmount ) {
			return false;
		}
		const newBlock = forminatorPrepareCloningBlock( firstBlock );
		groupField.append( newBlock );

		var pattern = new RegExp('((?:calculation|number|slider|currency|radio|select|checkbox)-\\d+(?:-min|-max)?)', 'g');
		var matches;

		const names = newBlock.find('[name]');
		names.each(function () {
			while( matches = pattern.exec( $(this).attr('name') ) ) {
				const selector  = matches[1],
					groupCalculation = form.find("[data-formula*='" + selector + "-*']");
				if ( groupCalculation.length ) {
					$(this).on( 'change', function (e) {
						form.trigger( 'forminator:recalculate' );
					});
				}
			}
		});

		newBlock.trigger( 'forminator-clone-group' );
		form.trigger( 'forminator:recalculate' );
	}

	/**
	 * Prepare block for cloning
	 */
	function forminatorPrepareCloningBlock( baseBlock ) {
		const newSuffix = String( Date.now().toString(32) + Math.random().toString(16) ).replace(/\./g, ''),
				form = baseBlock.closest( 'form.forminator-custom-form' ),
				grouId = baseBlock.closest( 'div[id^="group-"]' ).prop( 'id' ),
				formId = form.data( 'form-id' );

		let newBlock = baseBlock.clone();

		if (form.find('input[name="previous_draft_id"]').length > 0) {
            newBlock.find('.forminator-input').attr('value', '');
            newBlock.find('.forminator-textarea').empty();
            newBlock.find('input[type="radio"], input[type="checkbox"]').each(function () {
                $(this).attr('checked', false);
            });

            if (newBlock.find('.forminator-select2').length > 0) {
                newBlock.find('.forminator-select2').each(function (index, value) {
                    $(value).find('option:selected').attr('selected', false);
                    $(value).find('option:first').attr('selected', 'selected');
                });
            }

            if (newBlock.find('.forminator-rating').length > 0) {
                newBlock.find('.forminator-rating').each(function (index, value) {
                    $(value).find('option:selected').attr('selected', false);
                    $(value).find('option:first').attr('selected', 'selected');
                });
            }

            newBlock.find('.forminator-slider').each(function () {
                let $element = $(this),
                    $slide = $element.find('.forminator-slide'),
                    $minRange = parseInt($slide.data('min')) || 0,
                    $maxRange = parseInt($slide.data('max')) || 100;

                if ('1' === $slide.attr('data-is-range')) {
                    $slide.attr('data-value-max', $maxRange);
                    $slide.attr('data-value', $minRange);
                } else {
                    $slide.attr('data-value', $minRange);
                }
            });
        }

		// Remove slider custom labels in the cloned block.
		newBlock.find('.forminator-slider').each(function () {
			$(this).find('.forminator-slider-labels').remove();
		});

		newBlock.find( '.select2-container, .forminator-error-message, .iti__country-container' ).remove();

		// Cloning Rich-Text editors.
		newBlock.find( '.wp-editor-wrap' ).each( function() {
			let textarea = $( this ).find( 'textarea' );
			textarea.css( 'display', 'block' );
			$( this ).replaceWith( textarea );
		} );

		// Cloning Phone fields - material mode.
		newBlock.find('.forminator-field-phone .forminator-input-with-phone').each(function() {
			$(this).replaceWith($(this).contents().contents());
		});
		// Cloning Phone fields - other modes.
		newBlock.find('.forminator-field-phone .forminator-phone').each(function() {
			$(this).replaceWith($(this).contents());
		});

		// Cloning Singular File Upload.
		newBlock.find( '.forminator-file-upload [data-empty-text]' ).each( function() {
			let text = $( this ).data( 'empty-text' ) || '';
			$( this ).text( text );
		} );

		// Unselect options in Multiselect.
		newBlock.find( '.forminator-multiselect' ).each( function (j, multiSelect) {
			$(multiSelect).find('input[type="checkbox"]').each(function (i, val) {
				if( $(val).attr('checked' ) ) {
					$(val).closest('label').addClass('forminator-is_checked');
				} else {
					$(val).closest('label').removeClass('forminator-is_checked');
				}
			});
		});

		// Remove selected files for Multiple Upload fields.
		newBlock.find( '.forminator-uploaded-files.forminator-has-files, .forminator-slide' ).html('');

		// Change id and name attributes.
		let newHtml = newBlock.html().replace( /(id=|name=|for=|data-element=|wp.editor.initialize\()"([^"]+?)(\[\]|-multiselect-default-values|-label)?"/g, '$1"$2-' + newSuffix + '$3"' );

		// for cloning Multiple Upload fields.
		newHtml = newHtml.replace( /(forminator-upload-file--forminator-field-upload-|upload-container-upload-)([^" ]+)/g, '$1$2-' + newSuffix );
		const regexp = new RegExp( `(forminator-field-upload-)([^"]+?)(-${formId})`, 'g' );
		newHtml = newHtml.replace( regexp, '$1$2-' + newSuffix + '$3' );

		newHtml = newHtml.replace( /hasDatepicker|forminator-has_error|forminator-input-with-phone/g, '' );
		
		newHtml = newHtml.replace( /(data-(?:start|end)-field=)"([^"]+?)"/g, function(match, p1, p2) {
			const newField = p2 + '-' + newSuffix;
			if (!newHtml.includes(`name="${newField}"`)) {
				return match;
			}
			return p1 + '"' + newField + '"';
		});

		newHtml = forminatorUpdateCalculationFormulas( newHtml, newSuffix, baseBlock );

		const copyInput = '<input name="' + grouId + '-copies[]" type="hidden" value="' + newSuffix + '" />';
		newBlock.html( copyInput + newHtml );
		newBlock.data( 'suffix', newSuffix );

		return newBlock;
	}

	function forminatorUpdateCalculationFormulas( newHtml, newSuffix, baseBlock ) {
		const groupFields = baseBlock.find( '[name]' ).map(function() {
			return this.name;
		}).get();

		$.each( groupFields, function( index, fieldName ) {
			fieldName = fieldName.replace( '[]', '' );
			const regexp = new RegExp( `{${fieldName}}`, 'g' );
			newHtml = newHtml.replace( regexp, '{' + fieldName + '-' + newSuffix + '}' );
		} );


		return newHtml;
	}

	/**
	 * Add new group items if min limit field is increased
	 */
	function forminatorChangedRepeaterMin( groupField, newMin ) {
		const itemAmount = groupField.find('>.forminator-grouped-fields').length,
				firstBlock = groupField.find( '>.forminator-grouped-fields:first-child' ),
				fieldOptions = firstBlock.data('options'),
				diff = newMin - itemAmount;

		// Add Group items.
		for ( var i = 0; i < diff; i++ ) {
			forminatorCloneItem( groupField );
		}

		forminatorHideIrrelevantActions( fieldOptions, groupField );
	}

	/**
	 * Remove group items if max limit field is decreased
	 */
	function forminatorChangedRepeaterMax( groupField, newMax ) {
		const itemAmount = groupField.find('>.forminator-grouped-fields').length,
				firstBlock = groupField.find( '>.forminator-grouped-fields:first-child' ),
				fieldOptions = firstBlock.data('options'),
				diff = itemAmount - newMax;

		if ( 1 > newMax ) {
			return false;
		}

		// Remove Group items.
		for ( var i = 0; i < diff; i++ ) {
			const lastBlock = groupField.find('>.forminator-grouped-fields:last-child');

			forminatorRemoveItem( lastBlock, lastBlock.data('options') );
		}

		forminatorHideIrrelevantActions( fieldOptions, groupField );
	}

	/**
	 * Get min limit
	 */
	function forminatorGetMin( fieldOptions, form ) {
		let min = fieldOptions.min;
		if ( 'variable' === fieldOptions.min_type ) {
			const dependFromField = form.find( '[name="' + min + '"]' );

			min = forminatorUtils().get_field_value( dependFromField );
		}

		return Math.max( 1, min );
	}

	/**
	 * Get max limit
	 */
	function forminatorGetMax( fieldOptions, form ) {
		const min = forminatorGetMin( fieldOptions, form );
		let max = fieldOptions.max;
		if ( 'variable' === fieldOptions.max_type ) {
			const dependFromField = form.find( '[name="' + fieldOptions.max + '"]' );

			max = forminatorUtils().get_field_value( dependFromField );
		}

		return Math.max( 1, max, min );
	}

	/**
	 * Hide impossible action buttons
	 */
	function forminatorHideIrrelevantActions( fieldOptions, groupField ) {
		const form = groupField.closest( 'form.forminator-custom-form' ),
			min = forminatorGetMin( fieldOptions, form ),
			max = forminatorGetMax( fieldOptions, form ),
			items = groupField.find( '>.forminator-grouped-fields' ),
			addButtons = items.find( '>.forminator-action-buttons .forminator-repeater-add' ),
			removeButtons = items.find( '>.forminator-action-buttons .forminator-repeater-remove' ),
			blockAmount = items.length;

		if ( blockAmount >= max ) {
			addButtons.hide();
		} else {
			addButtons.show();
		}

		if ( blockAmount <= min ) {
			removeButtons.hide();
		} else {
			removeButtons.show();
			// The first Block isn't possible to remove because visibility conditions of other fields can be based on this fields.
			$( removeButtons[0] ).hide();
		}

	}

})(jQuery, window, document);