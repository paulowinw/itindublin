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
	var pluginName = "forminatorFrontMultiFile",
		defaults = {},
		uploadRequests = [],
		uploadCompleted = [];

	// The actual plugin constructor
	function ForminatorFrontMultiFile(element, options) {
		this.element = element;
		this.$el = $(this.element);

		// jQuery has an extend method which merges the contents of two or
		// more objects, storing the result in the first object. The first object
		// is generally empty as we don't want to alter the default options for
		// future instances of the plugin
		this.form = $.extend({}, defaults, options);
		this._defaults = defaults;
		this._name = pluginName;
		this.form_id = 0;
		this.uploader = this.$el;
		this.element = this.uploader.data('element');

		this.init();
	}

	// Avoid Plugin.prototype conflicts
	$.extend(ForminatorFrontMultiFile.prototype, {
		init: function () {
			var self = this,
				fileList = [],
				ajax_request = [];

			if (this.form.find('input[name=form_id]').length > 0) {
				this.form_id = this.form.find('input[name=form_id]').val();
			}
			uploadRequests[this.form_id] = uploadRequests[this.form_id] || 0;
			uploadCompleted[this.form_id] = uploadCompleted[this.form_id] || 0;

			this.uploader.on("drag dragstart dragend dragover dragenter dragleave drop", function(e) {
				e.preventDefault();
				e.stopPropagation();
			});
			this.uploader.on("dragover dragenter", function(a) {
				$(this).addClass("forminator-dragover");
			});
			this.uploader.on("dragleave dragend drop", function(a) {
				$(this).removeClass("forminator-dragover");
			});
			this.uploader.find( ".forminator-upload-file--forminator-field-" + this.element ).on("click", function(e) {
				self.form.find( '.forminator-field-' + self.element + '-' + self.form_id ).click();
			});

			this.uploader.on("drop", function(e) {
				document.querySelector( '.forminator-field-' + self.element + '-' + self.form_id ).files = e.originalEvent.dataTransfer.files;
				self.form.find( '.forminator-field-' + self.element + '-' + self.form_id ).change();
			});

			this.uploader.on("click", function(e) {
				if ( e.target === e.currentTarget ) {
					self.form.find( '.forminator-field-' + self.element + '-' + self.form_id ).click();
				}
			});
			this.uploader.find('.forminator-multi-upload-message, .forminator-multi-upload-message p, .forminator-multi-upload-message .forminator-icon-upload').on("click", function(e) {
				if ( e.target === e.currentTarget ) {
					self.form.find( '.forminator-field-' + self.element + '-' + self.form_id ).click();
				}
			});
			this.$el.on("fail:forminator:multiple:upload  delete:forminator:multiple:upload", function (e) {
				const element = self.uploader.find("input[type='file']");
				if (self.uploader.parent().find('.forminator-uploaded-file.forminator-has_error').length > 0) {
					$(element).data("valid", false)
				} else {
					$(element).data("valid", true)
				}
			});
			this.form.on("forminator:form:submit:success", function(e) {
				fileList = [];
			});

			this.form.on('forminator.front.pagination.buttons.updated', function (e) {
				// Disable the submit button if there are files being uploaded.
				if ( self.form.find( '.forminator-button-submit' ).length > 0 && uploadRequests[self.form_id] !== uploadCompleted[self.form_id]) {
					self.form.find('.forminator-button-submit').attr( 'disabled', true ).attr( 'data-uploading', true );
				} else if ( self.form.find( '.forminator-button-next' ).length > 0 && self.form.find( '.forminator-button-next' ).attr('data-uploading') === 'true' ) {
					self.form.find('.forminator-button-next').attr( 'disabled', false ).removeAttr( 'data-uploading' );
				}
			});

			this.form.find( '.forminator-field-' + self.element + '-' + self.form_id ).on("change", function(e) {
				if( ! self.uploadingFile ){
					self.uploadingFile = 1;

					var $this = $(this),
						param = this.files,
						uploadParam = [];

					$.when().then(function(){
						$this.closest('.forminator-field').removeClass('forminator-has_error');
						for ( var i = 0; i < param.length; i++ ) {
							uploadParam.push( param[ i ] );
							fileList.push( param[ i ] );
						}

						ajax_request = self.handleChangeCallback( uploadParam, $this, ajax_request );
						var file_list = Array.prototype.slice.call( fileList );

						if ( file_list.length > 0 ) {
							param = self.FileObjectItem(file_list);
							if ( 'submission' === $this.data( 'method' ) ) {
								$this.prop( 'files', param );
							}
						}
					}).done(function(){
						self.uploadingFile = null;
					});
				}
			});

			this.delete_files( fileList, ajax_request );
		},

		/**
		 * Upload Ajax call
		 *
		 * @param param
		 * @param $this
		 * @param ajax_request
		 */
		handleChangeCallback: function ( param, $this, ajax_request ) {
			var self = this,
				uploadData = new FormData,
				nonce = this.form.find('input[name="forminator_nonce"]').val(),
				method = $this.data('method'),
				elementId = self.element;

			elementId = elementId.split('_')[0];
			uploadData.append( "action", "forminator_multiple_file_upload" );
			uploadData.append( "form_id", this.form_id );
			uploadData.append( "element_id", elementId );
			uploadData.append( "nonce", nonce );

			$.each( param, function ( i, item ) {
				var unique_id = self.progress_bar( item, method ),
					totalFile = self.form.find('.upload-container-' + self.element + ' li').length,
					fileType = 'undefined' !== typeof $this.data('filetype') ? $this.data('filetype') : '',
					file_reg = new RegExp("(.*?)\.("+ fileType +")$"),
					itemName = item.name.toLowerCase();
				if ( 'undefined' !== typeof $this.data('size') && $this.data('size') <= item.size ) {
					error_messsage = $this.data('size-message');
					self.upload_fail_response( unique_id, error_messsage );
					self.$el.trigger('fail:forminator:multiple:upload', uploadData);
					return;
				} else if( ! file_reg.test( itemName ) ) {
					var ext = itemName.split('.').pop();
					error_messsage = '.' + ext + ' ' + $this.data('filetype-message');
					self.upload_fail_response( unique_id, error_messsage );
					self.$el.trigger('fail:forminator:multiple:upload', uploadData);
					return;
				}
				if( 'ajax' === method ) {
					uploadData.delete( self.element );
					uploadData.delete( 'totalFiles' );
					uploadData.append( "totalFiles", totalFile );
					uploadData.append( elementId, item );
					ajax_request.push( $.ajax({
						xhr: function () {
							var xhr = new window.XMLHttpRequest();
							xhr.upload.addEventListener("progress", function (evt) {
								if (evt.lengthComputable) {
									var percentComplete = ( ( evt.loaded / evt.total ) * 100 );
									if( 90 > percentComplete ) {
										self.form.find('#' + unique_id + ' .progress-percentage')
											.html(Math.round(percentComplete) + '% of ');
									}
								}
							}, false);
							return xhr;
						},
						type: 'POST',
						url: window.ForminatorFront.ajaxUrl,
						data: uploadData,
						cache: false,
						contentType: false,
						processData: false,
						beforeSend: function () {
							self.form.find('.forminator-button-submit, .forminator-button-next').attr( 'disabled', true ).attr( 'data-uploading', true );
							self.$el.trigger('before:forminator:multiple:upload', uploadData);
							uploadRequests[self.form_id]++;
						},
						success: function (data) {
							var element = self.element,
								current_file = {
									success: data.success,
									message: 'undefined' !== data.data.message ? data.data.message : '',
									file_id: unique_id,
									file_name: 'undefined' !== typeof data.data.file_url ? data.data.file_url.replace(/^.*[\\\/]/, '') : item.name,
									mime_type: item.type,
								};
							self.add_upload_file( element, current_file );
							if ( true === data.success && true === data.data.success && 'undefined' !== typeof data.data ) {
								self.upload_success_response( unique_id );
								self.$el.trigger('success:forminator:multiple:upload', uploadData);
							} else {
								self.upload_fail_response( unique_id, data.data.message );
								if( 'undefined' !== typeof data.data.error_type && 'limit' === data.data.error_type ) {
									self.form.find('#' + unique_id).addClass('forminator-upload-limit_error');
								}
								self.$el.trigger('fail:forminator:multiple:upload', uploadData);
							}
						},
						complete: function (xhr, status) {
							uploadCompleted[self.form_id]++;
							if ( uploadRequests[self.form_id] === uploadCompleted[self.form_id] ) {
								self.form.find('.forminator-button-submit, .forminator-button-next').attr( 'disabled', false ).removeAttr( 'data-uploading' );
							}
							self.$el.trigger('complete:forminator:multiple:upload', uploadData);
							self.$el.find('input[type="file"]').trigger('forminator.change', 'forminator_emulate_trigger' );
						},
						error: function (err) {
							self.upload_fail_response( unique_id, window.ForminatorFront.cform.process_error );
						}
					}))
				} else {
					var has_error = true,
						error_messsage = window.ForminatorFront.cform.process_error;

					if( 'undefined' !== typeof $this.data('limit') && $this.data('limit') < totalFile ) {
						has_error = false;
						self.form.find('#' + unique_id).addClass('forminator-upload-limit_error');
						error_messsage = $this.data('limit-message');
					}

					if( ! has_error ) {
						self.upload_fail_response( unique_id, error_messsage );

					} else {
						self.upload_success_response( unique_id );
					}
					self.$el.find('input[type="file"]').trigger('forminator.change', 'forminator_emulate_trigger' );
				}
			});

			return ajax_request;
		},

		/**
		 * Ajax fail response
		 *
		 * @param unique_id
		 * @param message
		 */
		upload_fail_response: function( unique_id, message ) {
			this.form.trigger( 'validation:error' );
			this.form.find('#' + unique_id).addClass('forminator-has_error');
			this.form.find('#' + unique_id).find('.forminator-uploaded-file--size [class*="forminator-icon-"]')
				.addClass('forminator-icon-warning')
				.removeClass('forminator-icon-loader')
				.removeClass('forminator-loading');
			this.form.find('#' + unique_id + ' .progress-percentage').html('0% of ');
			this.form.find('#' + unique_id + ' .forminator-uploaded-file--content')
				.after('<div class="forminator-error-message">' + message + '</div>');
		},

		/**
		 * Ajax success response
		 *
		 * @param unique_id
		 */
		upload_success_response: function( unique_id ) {
			this.form.find('#' + unique_id + ' .progress-percentage').html('100% of ');
			this.form.find('#' + unique_id + ' .forminator-uploaded-file--size [class*="forminator-icon-"]').remove();
			this.form.find('#' + unique_id + ' .progress-percentage').remove();
		},

		/**
		 * Show progress bar
		 *
		 * @param file
		 * @param method
		 */
		progress_bar: function( file, method ) {
			var self = this,
				uniqueID = Math.random().toString( 36 ).substr( 2, 7 ),
				uniqueId = 'upload-process-' + uniqueID,
				filename = file.name,
				filesize = self.bytes_to_size( file.size, 2 ),
				wrapper  = this.uploader.closest( '.forminator-field' ).find( '.forminator-uploaded-files' ),
				markup   = ''
			;

			this.progress_image_preview( file, uniqueId );

			function getFileExtension( element ) {
				var parts = element.split( '.' );
				return parts[ parts.length - 1 ];
			}

			function isImage( element ) {

				var ext = getFileExtension( element );

				switch ( ext.toLowerCase() ) {
					case 'jpg':
					case 'jpe':
					case 'jpeg':
					case 'png':
					case 'gif':
					case 'ico':
						return true;
				}

				return false;

			}

			/**
			 * File Preview Markup.
			 *
			 * Get the icon file or replace it with image preview.
			 */
			var preview = '<div class="forminator-uploaded-file--preview" aria-hidden="true">' +
				'<span class="forminator-icon-file" aria-hidden="true"></span>' +
				'</div>';

			if ( isImage( filename ) ) {
				preview = '<div class="forminator-uploaded-file--image" aria-hidden="true">' +
					'<div class="forminator-img-preview" role="image"></div>' +
					'</div>';
			}

			/**
			 * File Name.
			 *
			 * Get the name of the uploaded file (extension included).
			 */

			var name = '<p class="forminator-uploaded-file--title">' + filename.replace( /[<>:"/\\|?*]+/g, '_' ) + '</p>';

			/**
			 * File Size.
			 *
			 * Depending on the state of the file user will get a:
			 * - Loading Icon: When file is still being uploaded.
			 *   This will be accompanied by percent amount.
			 * - Warning Icon: When file finished loading but an
			 *   error happened.
			 * - File Size.
			 */

			var size = '<p class="forminator-uploaded-file--size">' +
				'<span class="forminator-icon-loader forminator-loading" aria-hidden="true"></span>' +
				'<span class="progress-percentage">29% of </span>' +
				filesize +
				'</p>';

			/**
			 * File Delete Button.
			 *
			 * This icon button will have the ability to remove
			 * the uploaded file.
			 */

			var trash = '<button type="button" class="forminator-uploaded-file--delete forminator-button-delete" data-method="' + method + '" data-element="' + self.element + '" data-value="' + uniqueId + '">' +
				'<span class="forminator-icon-close" aria-hidden="true"></span>' +
				'<span class="forminator-screen-reader-only">Delete uploaded file</span>' +
				'</button>';

			/**
			 * Markup.
			 */

			markup += '<li id="' + uniqueId + '" class="forminator-uploaded-file">';
			markup += '<div class="forminator-uploaded-file--content">';
			markup += preview;
			markup += '<div class="forminator-uploaded-file--text">';
			markup += name;
			markup += size;
			markup += '</div>';
			markup += trash;
			markup += '</div>';
			markup += '</li>';

			/**
			 * Has Files Class.
			 *
			 * Add "forminator-has-files" class to wrapper.
			 */

			if ( ! wrapper.hasClass( '.forminator-has-files' ) ) {
				wrapper.addClass( 'forminator-has-files' );
			}

			return wrapper.append( markup ), uniqueId;

		},

		bytes_to_size: function ( bytes, decimals ) {

			if ( 0 === bytes ) return '0 Bytes';

			var k = 1024,
				dm = decimals < 0 ? 0 : decimals,
				sizes = [ 'Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB' ],
				i = Math.floor( Math.log( bytes ) / Math.log( k ) );

			return parseFloat( ( bytes / Math.pow( k, i ) ).toFixed( dm ) ) + ' ' + sizes[ i ];
		},

		/**
		 * image preview
		 *
		 * @param image
		 * @param uniqueId
		 */
		progress_image_preview: function ( image, uniqueId ) {
			if ( image ) {
				var reader = new FileReader();
				reader.onload = function (e) {
					$('#'+ uniqueId + ' .forminator-img-preview').css('background-image', 'url(' + e.target.result + ')');
				};
				reader.readAsDataURL(image);
			}
		},

		/**
		 * Get all uploaded file
		 *
		 * @returns {*}
		 */
		get_uplaoded_files: function () {
			var uploaded_value = this.form.find( '.forminator-multifile-hidden' ), files;

			files = uploaded_value.val();
			files = ( typeof files === "undefined" ) || files === '' ? {} : $.parseJSON( files );

			return files;
		},

		/**
		 * Get file by element
		 *
		 * @param element
		 * @returns {*}
		 */
		get_uplaoded_file: function ( element ) {
			var uploaded_file = this.get_uplaoded_files();

			if( typeof uploaded_file[ element ] === 'undefined' )
				uploaded_file[ element ] = [];

			return uploaded_file[ element ];
		},

		/**
		 * Add uploaded file
		 *
		 * @param element
		 * @param response
		 */
		add_upload_file: function ( element, response ) {
			var files = this.get_uplaoded_file( element );

			files.unshift( response );
			this.set_upload_file( element, files );
		},

		/**
		 * Set upload file
		 *
		 * @param element
		 * @param files
		 */
		set_upload_file: function ( element, files ) {
			var upload_file = this.get_uplaoded_files(),
				uploaded_value = this.form.find( '.forminator-multifile-hidden' );
			upload_file[ element ] = files;
			uploaded_value.val( JSON.stringify( upload_file ) );
		},

		/**
		 * Get uploaded by file id
		 *
		 * @param element
		 * @param file_id
		 * @returns {*}
		 */
		get_uploaded_file_id: function ( element, file_id ) {
			var file_index = null,
				upload_file = this.get_uplaoded_file( element );
			$.each( upload_file, function ( key, val ) {
				if( file_id === val['file_id'] ) file_index = key;
			});

			return file_index;
		},

		/**
		 * Delete files
		 */
		delete_files: function ( fileList, ajax_request ) {
			var self = this;
			$( document ).on( "click", ".forminator-uploaded-file--delete", function( e ) {
				e.preventDefault();
				var deleteButton = $( this ),
					file_id = deleteButton.data('value'),
					method = deleteButton.data('method'),
					element_id = deleteButton.data('element');
				if( 'undefined' !== typeof file_id && 'undefined' !== typeof element_id && 'undefined' !== typeof method ) {

					var index = self.form.find('#' + file_id ).index(),
						fileContainer = $( deleteButton ).closest( 'li#' + file_id ),
						uploaded_arr = self.get_uplaoded_files(),
						uploaded_value = self.form.find( '.forminator-multifile-hidden' );

					if ( uploaded_arr && 'ajax' === method ) {

						if( 'undefined' !== typeof ajax_request[ index ] ) {
							ajax_request[ index ].abort();
							ajax_request.splice( index, 1 );
						}
						if( 'undefined' !== typeof uploaded_value ) {
							var file_index = self.get_uploaded_file_id( element_id, file_id );
							if( '' !== file_index && null !== file_index ) {
								uploaded_arr[ element_id ]?.splice( file_index, 1 );
							}
							uploaded_value.val( JSON.stringify( uploaded_arr ) );
						}
					}

					if( 'undefined' !== typeof method && 'submission' === method ) {
						self.remove_object( index, fileList, element_id );
					}

					$( fileContainer ).remove();
				}

				var fileInput = self.form.find( '.forminator-field-'+ element_id + '-' + self.form_id );
				var liList = self.form.find('.upload-container-' + element_id + ' li' );
				if( 'undefined' !== typeof fileInput.data('limit') ) {
					$.each( liList,function( index ) {
						if( fileInput.data('limit') > index && $(this).hasClass('forminator-upload-limit_error') ) {
							var fileID = $(this).attr('id'),
								fileIndex = self.get_uploaded_file_id( element_id, fileID );
							$(this).removeClass('forminator-has_error');
							$(this).find('.forminator-error-message, .forminator-icon-warning, .progress-percentage').remove();
							if( '' !== fileIndex && null !== fileIndex && 'undefined' !== typeof uploaded_arr[ element_id ][ fileIndex ] ) {
								uploaded_arr[ element_id ][ fileIndex ].success = true;
							}
						}
					});
					uploaded_value.val( JSON.stringify( uploaded_arr ) );
				}

				// empty file input value if no files left
				if ( liList.length === 0 ) {
					fileInput.val('');
				}

				// Re-enable submit button if there are no errors on the upload fields.
				if ( 0 === self.form.find( '.forminator-uploaded-file.forminator-has_error' ).length ) {
					self.form.trigger( 'forminator:uploads:valid' );
					self.form.find('.forminator-button-submit').attr( 'disabled', false );
				}
				fileInput.trigger( 'forminator.change', 'forminator_emulate_trigger' );
				self.$el.trigger('delete:forminator:multiple:upload');
			})
		},

		remove_object: function( index, fileList, element_id ) {
			var upload_input = document.querySelector( '.forminator-field-'+ element_id + '-' + this.form_id );
			if( 'undefined' !== typeof upload_input ) {
				var	upload_files = upload_input.files;
				if( upload_files.length > 0 ) {
					var upload_slice = Array.prototype.slice.call( upload_files );
					fileList.splice( index, 1 );
					upload_slice.splice( index, 1 );
					upload_input.files = this.FileObjectItem( upload_slice );
				}
			}
		},

		/**
		 * File list object
		 *
		 * @param a
		 * @returns {FileList}
		 * @constructor
		 */
		FileObjectItem: function ( a ) {
			a = [].slice.call( Array.isArray( a ) ? a : arguments );
			a = a.reverse();
			for ( var c, b = c = a.length, d = !0; b-- && d; ) {
				d = a[ b ] instanceof File;
			}
			if ( ! d ) throw new TypeError("expected argument to FileList is File or array of File objects");
			for ( b = ( new ClipboardEvent("") ).clipboardData || new DataTransfer; c--;) {
				b.items.add( a[ c ] );
			}

			return b.files
		}
	});

	// A really lightweight plugin wrapper around the constructor,
	// preventing against multiple instantiations
	$.fn[pluginName] = function (options) {
		return this.each(function () {
			if (!$.data(this, pluginName)) {
				$.data(this, pluginName, new ForminatorFrontMultiFile(this, options));
			}
		});
	};

})(jQuery, window, document);
