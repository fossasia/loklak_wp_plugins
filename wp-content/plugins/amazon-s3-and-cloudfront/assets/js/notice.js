(function( $ ) {

	$( 'body' ).on( 'click', '.as3cf-notice .notice-dismiss', function( e ) {
		var id = $( this ).parents( '.as3cf-notice' ).attr( 'id' );
		if ( id ) {
			var data = {
				action   : 'as3cf-dismiss-notice',
				notice_id: id,
				_nonce   : as3cf_notice.nonces.dismiss_notice
			};

			$.ajax( {
				url     : ajaxurl,
				type    : 'POST',
				dataType: 'JSON',
				data    : data,
				error   : function( jqXHR, textStatus, errorThrown ) {
					alert( as3cf_notice.strings.dismiss_notice_error + errorThrown );
				}
			} );
		}
	} );

})( jQuery );