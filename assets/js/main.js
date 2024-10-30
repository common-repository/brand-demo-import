jQuery( function ( $ ) {
	'use strict';

	/**
	 * ---------------------------------------
	 * ------------- Events ------------------
	 * ---------------------------------------
	 */

	/**
	 * No or Single predefined demo import button click.
	 */
	$( '.js-bdi-import-data' ).on( 'click', function () {

		// Reset response div content.
		$( '.js-bdi-ajax-response' ).empty();

		// Prepare data for the AJAX call
		var data = new FormData();
		data.append( 'action', 'bdi_import_demo_data' );
		data.append( 'security', bdi.ajax_nonce );
		data.append( 'selected', $( '#bdi__demo-import-files' ).val() );
		if ( $('#bdi__content-file-upload').length ) {
			data.append( 'content_file', $('#bdi__content-file-upload')[0].files[0] );
		}
		if ( $('#bdi__widget-file-upload').length ) {
			data.append( 'widget_file', $('#bdi__widget-file-upload')[0].files[0] );
		}
		if ( $('#bdi__customizer-file-upload').length ) {
			data.append( 'customizer_file', $('#bdi__customizer-file-upload')[0].files[0] );
		}

		// AJAX call to import everything (content, widgets, before/after setup)
		ajaxCall( data );

	});


	/**
	 * Grid Layout import button click.
	 */
	$( '.js-bdi-gl-import-data' ).on( 'click', function () {
		var selectedImportID = $( this ).val();
		var $itemContainer   = $( this ).closest( '.js-bdi-gl-item' );

		// If the import confirmation is enabled, then do that, else import straight away.
		if ( bdi.import_popup ) {
			displayConfirmationPopup( selectedImportID, $itemContainer );
		}
		else {
			gridLayoutImport( selectedImportID, $itemContainer );
		}
	});


	/**
	 * Grid Layout categories navigation.
	 */
	(function () {
		// Cache selector to all items
		var $items = $( '.js-bdi-gl-item-container' ).find( '.js-bdi-gl-item' ),
			fadeoutClass = 'bdi-is-fadeout',
			fadeinClass = 'bdi-is-fadein',
			animationDuration = 200;

		// Hide all items.
		var fadeOut = function () {
			var dfd = jQuery.Deferred();

			$items
				.addClass( fadeoutClass );

			setTimeout( function() {
				$items
					.removeClass( fadeoutClass )
					.hide();

				dfd.resolve();
			}, animationDuration );

			return dfd.promise();
		};

		var fadeIn = function ( category, dfd ) {
			var filter = category ? '[data-categories*="' + category + '"]' : 'div';

			if ( 'all' === category ) {
				filter = 'div';
			}

			$items
				.filter( filter )
				.show()
				.addClass( 'bdi-is-fadein' );

			setTimeout( function() {
				$items
					.removeClass( fadeinClass );

				dfd.resolve();
			}, animationDuration );
		};

		var animate = function ( category ) {
			var dfd = jQuery.Deferred();

			var promise = fadeOut();

			promise.done( function () {
				fadeIn( category, dfd );
			} );

			return dfd;
		};

		$( '.js-bdi-nav-link' ).on( 'click', function( event ) {
			event.preventDefault();

			// Remove 'active' class from the previous nav list items.
			$( this ).parent().siblings().removeClass( 'active' );

			// Add the 'active' class to this nav list item.
			$( this ).parent().addClass( 'active' );

			var category = this.hash.slice(1);

			// show/hide the right items, based on category selected
			var $container = $( '.js-bdi-gl-item-container' );
			$container.css( 'min-width', $container.outerHeight() );

			var promise = animate( category );

			promise.done( function () {
				$container.removeAttr( 'style' );
			} );
		} );
	}());


	/**
	 * Grid Layout search functionality.
	 */
	$( '.js-bdi-gl-search' ).on( 'keyup', function( event ) {
		if ( 0 < $(this).val().length ) {
			// Hide all items.
			$( '.js-bdi-gl-item-container' ).find( '.js-bdi-gl-item' ).hide();

			// Show just the ones that have a match on the import name.
			$( '.js-bdi-gl-item-container' ).find( '.js-bdi-gl-item[data-name*="' + $(this).val().toLowerCase() + '"]' ).show();
		}
		else {
			$( '.js-bdi-gl-item-container' ).find( '.js-bdi-gl-item' ).show();
		}
	} );

	/**
	 * ---------------------------------------
	 * --------Helper functions --------------
	 * ---------------------------------------
	 */

	/**
	 * Prepare grid layout import data and execute the AJAX call.
	 *
	 * @param int selectedImportID The selected import ID.
	 * @param obj $itemContainer The jQuery selected item container object.
	 */
	function gridLayoutImport( selectedImportID, $itemContainer ) {
		// Reset response div content.
		$( '.js-bdi-ajax-response' ).empty();

		// Hide all other import items.
		$itemContainer.siblings( '.js-bdi-gl-item' ).fadeOut( 500 );

		$itemContainer.animate({
			opacity: 0
		}, 500, 'swing', function () {
			$itemContainer.animate({
				opacity: 1
			}, 500 )
		});

		// Hide the header with category navigation and search box.
		$itemContainer.closest( '.js-bdi-gl' ).find( '.js-bdi-gl-header' ).fadeOut( 500 );

		// Append a title for the selected demo import.
		$itemContainer.parent().prepend( '<h3>' + bdi.texts.selected_import_title + '</h3>' );

		// Remove the import button of the selected item.
		$itemContainer.find( '.js-bdi-gl-import-data' ).remove();

		// Prepare data for the AJAX call
		var data = new FormData();
		data.append( 'action', 'bdi_import_demo_data' );
		data.append( 'security', bdi.ajax_nonce );
		data.append( 'selected', selectedImportID );

		// AJAX call to import everything (content, widgets, before/after setup)
		ajaxCall( data );
	}

	/**
	 * Display the confirmation popup.
	 *
	 * @param int selectedImportID The selected import ID.
	 * @param obj $itemContainer The jQuery selected item container object.
	 */
	function displayConfirmationPopup( selectedImportID, $itemContainer ) {
		var $dialogContiner         = $( '#js-bdi-modal-content' );
		var currentFilePreviewImage = bdi.import_files[ selectedImportID ]['import_preview_image_url'] || bdi.theme_screenshot;
		var previewImageContent     = '';
		var importNotice            = bdi.import_files[ selectedImportID ]['import_notice'] || '';
		var importNoticeContent     = '';
		var dialogOptions           = $.extend(
			{
				'dialogClass': 'wp-dialog',
				'resizable':   false,
				'height':      'auto',
				'modal':       true
			},
			bdi.dialog_options,
			{
				'buttons':
				[
					{
						text: bdi.texts.dialog_no,
						click: function() {
							$(this).dialog('close');
						}
					},
					{
						text: bdi.texts.dialog_yes,
						class: 'button  button-primary',
						click: function() {
							$(this).dialog('close');
							gridLayoutImport( selectedImportID, $itemContainer );
						}
					}
				]
			});

		if ( '' === currentFilePreviewImage ) {
			previewImageContent = '<p>' + bdi.texts.missing_preview_image + '</p>';
		}
		else {
			previewImageContent = '<div class="bdi__modal-image-container"><img src="' + currentFilePreviewImage + '" alt="' + bdi.import_files[ selectedImportID ]['import_file_name'] + '"></div>'
		}

		// Prepare notice output.
		if( '' !== importNotice ) {
			importNoticeContent = '<div class="bdi__modal-notice  bdi__demo-import-notice">' + importNotice + '</div>';
		}

		// Populate the dialog content.
		$dialogContiner.prop( 'title', bdi.texts.dialog_title );
		$dialogContiner.html(
			'<p class="bdi__modal-item-title">' + bdi.import_files[ selectedImportID ]['import_file_name'] + '</p>' +
			previewImageContent +
			importNoticeContent
		);

		// Display the confirmation popup.
		$dialogContiner.dialog( dialogOptions );
	}

	/**
	 * The main AJAX call, which executes the import process.
	 *
	 * @param FormData data The data to be passed to the AJAX call.
	 */
	function ajaxCall( data ) {
		$.ajax({
			method:      'POST',
			url:         bdi.ajax_url,
			data:        data,
			contentType: false,
			processData: false,
			beforeSend:  function() {
				$( '.js-bdi-ajax-loader' ).show();
			}
		})
		.done( function( response ) {
			if ( 'undefined' !== typeof response.status && 'newAJAX' === response.status ) {
				//ajaxCall( data );
			}
			/* else if ( 'undefined' !== typeof response.status && 'afterAllImportAJAX' === response.status ) {
				// Fix for data.set and data.delete, which they are not supported in some browsers.
				var newData = new FormData();
				newData.append( 'action', 'bdi_after_import_data' );
				newData.append( 'security', bdi.ajax_nonce );
				ajaxCall( newData );
			} */
			else if ( 'undefined' !== typeof response.status && 'Content import failed' === response.status ) {
				$( '.js-bdi-ajax-response' ).append( '<p>' + response.error + '</p>' );
				$( '.js-bdi-ajax-loader' ).hide();
			}
			else if ( 'undefined' !== typeof response.message ) {
				$( '.js-bdi-ajax-response' ).append( '<p>' + response.message + '</p>' );
				$( '.js-bdi-ajax-loader' ).hide();
			}
			else {
				$( '.js-bdi-ajax-response' ).append( '<div class="notice  notice-error  is-dismissible"><p>' + response + '</p></div>' );
				$( '.js-bdi-ajax-loader' ).hide();
			}
		})
		.fail( function( error ) {
			$( '.js-bdi-ajax-response' ).append( '<div class="notice  notice-error  is-dismissible"><p>Error: ' + error.statusText + ' (' + error.status + ')' + '</p></div>' );
			$( '.js-bdi-ajax-loader' ).hide();
		});
	}
} );
