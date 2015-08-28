var as3cfModal = (function ( $ ) {

	var modal = {
		prefix: 'as3cf',
		loading: false
	};

	var modals = {};

	/**
	 * Target to key
	 *
	 * @param {string} target
	 *
	 * @return {string}
	 */
	function targetToKey( target ) {
		return target.replace( /[^a-z]/g, '' );
	}

	/**
	 * Open modal
	 *
	 * @param {string}   target
	 * @param {function} callback
	 * @param {string}   customClass
	 */
	modal.open = function ( target, callback, customClass ) {
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

		if ( undefined !== customClass ) {
			$modal.addClass( customClass );
		}

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
	 * @param {function} callback
	 */
	modal.close = function ( callback ) {
		if ( modal.loading ) {
			return;
		}

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

	/**
	 * Set loading state
	 *
	 * @param {bool} state
	 */
	modal.setLoadingState = function ( state ) {
		modal.loading = state;
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