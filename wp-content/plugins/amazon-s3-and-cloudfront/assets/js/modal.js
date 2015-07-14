var as3cfModal = (function ( $ ) {

	var modal = {
		prefix: 'as3cf'
	};

	var modals = {};

	/**
	 * Target to key
	 *
	 * @param string target
	 *
	 * @return string
	 */
	function targetToKey( target ) {
		return target.replace( /[^a-z]/g, '' );
	}

	/**
	 * Open modal
	 *
	 * @param string   target
	 * @param function callback
	 */
	modal.open = function ( target, callback ) {
		var key = targetToKey( target );

		// Overlay
		$( 'body' ).append( '<div id="as3cf-overlay"></div>' );
		var $overlay = $( '#as3cf-overlay' );

		// Modal container
		$overlay.append( '<div id="as3cf-modal"><span class="close-as3cf-modal">×</span></div>' );
		var $modal = $( '#as3cf-modal' );

		if ( undefined === modals[ key ] ) {
			var content = $( target );
			modals[ key ] = content.clone( true ).css( 'display', 'block' );
			content.remove();
		}
		$modal.data( 'as3cf-modal-target', target ).append( modals[ key ] );

		if ( 'function' === typeof callback ) {
			callback( target );
		}

		// Handle modals taller than window height,
		// overflow & padding-right remove duplicate scrollbars.
		$( 'body' ).addClass( 'as3cf-modal-open' );

		$overlay.fadeIn( 150 );
		$modal.fadeIn( 150 );

		$( 'body' ).trigger( 'as3cf-modal-open', [ target ] );
	};

	/**
	 * Close modal
	 *
	 * @param function callback
	 */
	modal.close = function ( callback ) {
		var target = $( '#as3cf-modal' ).data( 'as3cf-modal-target' );

		$( '#as3cf-overlay' ).fadeOut( 150, function () {
			if ( 'function' === typeof callback ) {
				callback( target );
			}

			$( 'body' ).removeClass( 'as3cf-modal-open' );

			$( this ).remove();
		} );

		$( 'body' ).trigger( 'as3cf-modal-close', [ target ] );
	};

	// Setup click handlers
	$( document ).ready( function () {

		$( 'body' ).on( 'click', '[data-as3cf-modal]', function ( e ) {
			e.preventDefault();
			modal.open( $( this ).data( 'as3cf-modal' ) + '.' + modal.prefix );
		} );

		$( 'body' ).on( 'click', '#as3cf-overlay, .close-as3cf-modal', function ( e ) {
			e.preventDefault();

			// Don't allow children to bubble up click event
			if ( e.target !== this ) {
				return false;
			}

			modal.close();
		} );

	} );

	return modal;

})( jQuery );

var as3cfFindAndReplaceModal = (function ( $, as3cfModal ) {

	var modal = {
		selector: '.as3cf-find-replace-container',
		isBulk: false,
		link: null,
		payload: {}
	};

	/**
	 * Open modal
	 *
	 * @param string link
	 * @param mixed  payload
	 */
	modal.open = function ( link, payload ) {
		if ( typeof link !== 'undefined' ) {
			modal.link = link;
		}
		if ( typeof payload !== 'undefined' ) {
			modal.payload = payload;
		}

		as3cfModal.open( modal.selector );

		$( modal.selector ).find( '.single-file' ).show();
		$( modal.selector ).find( '.multiple-files' ).hide();
		if ( modal.isBulk ) {
			$( modal.selector ).find( '.single-file' ).hide();
			$( modal.selector ).find( '.multiple-files' ).show();
		}
	};

	/**
	 * Close modal
	 */
	modal.close = function () {
		as3cfModal.close( modal.selector );
	};

	/**
	 * Set the isBulk flag
	 */
	modal.setBulk = function ( isBulk ) {
		modal.isBulk = isBulk;
	};

	/**
	 * Create the loading state
	 */
	modal.startLoading = function () {
		$( modal.selector + ' [data-find-replace]' ).prop( 'disabled', true ).siblings( '.spinner' ).css( 'visibility', 'visible' ).show();
	};

	/**
	 * Remove the loading state
	 */
	modal.stopLoading = function () {
		$( modal.selector + ' [data-find-replace]' ).prop( 'disabled', false ).siblings( '.spinner' ).css( 'visibility', 'hidden' ).hide();
	};

	// Setup click handlers
	$( document ).ready( function () {

		$( 'body' ).on( 'click', modal.selector + ' [data-find-replace]', function ( e ) {
			var findAndReplace = $( this ).data( 'find-replace' );

			if ( !modal.link ) {
				// If there is no link set then this must be an AJAX
				// request so trigger an event instead
				$( modal.selector ).trigger( 'as3cf-find-and-replace', [ findAndReplace, modal.payload ] );
				return;
			}

			if ( findAndReplace ) {
				modal.link += '&find_and_replace=1';
			}

			modal.startLoading();

			window.location = modal.link;
		} );

		$( 'body' ).on( 'as3cf-modal-close', function ( e ) {
			modal.isBulk = false;
			modal.link = null;
			modal.payload = {};
		} );

	} );

	return modal;

})( jQuery, as3cfModal );


