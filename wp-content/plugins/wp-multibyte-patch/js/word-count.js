/* global wordCountL10n */
var wpWordCount;
(function($,undefined) {
	wpWordCount = {

		settings : {
			strip : /<[a-zA-Z\/][^<>]*>/g, // strip HTML tags
			clean : /[0-9.(),;:!?%#$¿'"_+=\\/-]+/g, // regexp to remove punctuation, etc.
			count : /\S\s+/g // word-counting regexp
		},

		block : 0,

		wc : function(tx, type) {
			var t = this, w = $('.word-count'), tc = 0;

			if ( type === undefined )
				type = wordCountL10n.type;
			if ( type !== 'w' && type !== 'c' )
				type = 'w';

			if ( t.block )
				return;

			t.block = 1;

			setTimeout( function() {
				if ( tx ) {
					if ( type == 'w' ) { // word-counting
						tx = tx.replace( t.settings.strip, ' ' ).replace( /&nbsp;|&#160;/gi, ' ' );
						tx = tx.replace( t.settings.clean, '' );
						tx.replace( t.settings.count, function(){tc++;} );
					}
					else if ( type == 'c' ) { // char-counting for asian languages
						tx = tx.replace( t.settings.strip, '' ).replace( /^ +| +$/gm, '' );
						tx = tx.replace( / +|&nbsp;|&#160;/gi, ' ' );
						tx.replace( /[\S \u00A0\u3000]/g, function(){tc++;} );
					}
				}
				w.html(tc.toString());

				setTimeout( function() { t.block = 0; }, 2000 );
			}, 1 );
		}
	};

	$(document).bind( 'wpcountwords', function(e, txt) {
		wpWordCount.wc(txt);
	});
}(jQuery));
