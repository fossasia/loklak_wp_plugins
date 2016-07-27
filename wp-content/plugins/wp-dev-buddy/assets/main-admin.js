(function($) {
	var n  = DBTF.pluginName;
	var sn = DBTF.pluginShortName;

	var optionsName  = DBTF.optionsNameMain;
	var optionsGroup = DBTF.optionsGroup;

	var $settingsForm = $( '#db_twitter_feed_settings' );

	// Disable all but the selected feed type
	$( '.input_feed_type' ).attr( { disabled:'disabled' } );
	$( 'input[name="' + optionsName + '[feed_type]"]' ).each( function() {
		if ( $( this ).is( ':checked' ) ) {
			enableFeedTypeInput( $( this ).val() );
		}
	} );

	// When the feed type option is changed, enable the corresponding feed term input field
	$( 'input[name="' + optionsName + '[feed_type]"]' ).on( 'change', function(e) {
		var $target = $( e.target );
		enableFeedTypeInput( $target.val() );
	} );

	// When feed values have been edited, reflect the change in the hidden values
	$( '.input_feed_type' ).on( 'change', function(e) {
		var termInput    = $( e.target ).attr( 'name' );
		var termInputHid = termInput.replace( /]/, '_hid]' );

		$( 'input[name="' + termInputHid + '"]' ).val( $( e.target ).val() );
	} );


	// Enable a feed term input field and disable all others
	function enableFeedTypeInput( feedTypeSelected ) {
		$( '.input_feed_type' ).attr( { disabled:'disabled' } );

        var fieldId = null;

		switch ( feedTypeSelected ) {
			case 'user_timeline':
                fieldId = 'twitter_username';
			break;

			case 'search':
                fieldId = 'search_term';
			break;

            default:
                fieldId = feedTypeSelected;
                break;
		}

		$( '#' + sn + '_' + fieldId ).removeAttr( 'disabled' );
	}


	/* Provide the plugin script with a means of discerning
	   between a Save submit and a Clear Cache submit by
	   setting a cache clear flag value */
	$( 'input#dbtf_batch_clear_cache' ).on( 'click', function(e) {
		$( '#' + sn + '_cache_clear_flag' ).val( 1 );
	} );


	/* Hitting the back button in certain browsers means that
	   values are not reset. When the "Save" submit button is
	   hit, we need to set the cache clear flag to a false
	   value */
	$( 'input#submit' ).on( 'click', function() {
		$( '#' + sn + '_cache_clear_flag' ).val( 0 );
	} );


	/* Ensure that when pressing "Enter" on a field not in the cache
	   section the correct submission type goes down */
	$settingsForm.on( 'keypress', function( e ) {
		if ( e.keyCode === 13 && $( e.target ).attr( 'id' ) !== sn + '_cache_segment' ) {
			e.preventDefault();
			$( '#submit' ).trigger( 'click' );
		}
	} );


})(jQuery);





// This file could do with a proper clean up


// Maybe one day.