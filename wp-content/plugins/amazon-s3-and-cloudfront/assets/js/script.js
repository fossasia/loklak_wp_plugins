(function($) {
	var saved_settings;

	$( document ).ready( function() {

		$( '.as3cf-settings' ).each( function() {
			var $container = $( this );
			var $bucketList = $( '.as3cf-bucket-list' );
			var $createBucketForm = $container.find( '.as3cf-create-bucket-form' );
			var $manualBucketForm = $container.find( '.as3cf-manual-save-bucket-form' );

			if ( $createBucketForm.length ) {
				var $createBucketButton = $createBucketForm.find( 'button' );
				var origButtonText = $createBucketButton.text();

				$createBucketForm.on( 'submit', function( e ) {
					e.preventDefault();
					$( '.as3cf-bucket-error' ).hide();
					$bucketList.addClass( 'saving' );
					$createBucketButton.text( $createBucketButton.attr( 'data-working' ) );
					$createBucketButton.prop( 'disabled', true );
					var bucketName = $createBucketForm.find( 'input[name="bucket_name"]' ).val();

					var data = {
						action     : 'as3cf-create-bucket',
						bucket_name: bucketName,
						_nonce     : as3cf_i18n.create_bucket_nonce
					};

					$.ajax( {
						url     : ajaxurl,
						type    : 'POST',
						dataType: 'JSON',
						data    : data,
						error   : function( jqXHR, textStatus, errorThrown ) {
							$createBucketButton.text( origButtonText );
							show_bucket_error( as3cf_i18n.create_bucket_error, errorThrown );
						},
						success : function( data, textStatus, jqXHR ) {
							$createBucketButton.text( origButtonText );
							$createBucketButton.prop( 'disabled', false );
							if ( typeof data[ 'success' ] !== 'undefined' ) {
								bucket_select( bucketName, data[ 'region' ], data[ 'can_write' ] );
								// tidy up create bucket form
								$createBucketForm.find( 'input[name="bucket_name"]' ).val( '' );
								if ( $( '.as3cf-bucket-list-wrapper' ).is( ':visible' ) ) {
									loadBuckets();
								}
								$( '.as3cf-bucket-list a' ).removeClass( 'selected' );
								$bucketList.removeClass( 'saving' );
								$manualBucketForm.find( 'input[name="bucket_name"]' ).val( bucketName );
							} else {
								show_bucket_error( as3cf_i18n.create_bucket_error, data[ 'error' ] );
							}
						}
					} );
				} );
			}

			if ( $manualBucketForm.length ) {
				var $manualBucketButton = $manualBucketForm.find( 'button' );
				var origManualButtonText = $manualBucketButton.text();

				$manualBucketForm.on( 'submit', function( e ) {
					e.preventDefault();
					var bucketName = $manualBucketForm.find( 'input[name="bucket_name"]' ).val();
					if ( bucketName === $( '.as3cf-active-bucket' ).text() ) {
						$( '.as3cf-bucket-error' ).hide();
						$( '.as3cf-settings' ).addClass( 'as3cf-has-bucket' );
						return;
					}
					$( '.as3cf-bucket-error' ).hide();
					$manualBucketButton.text( $manualBucketButton.attr( 'data-working' ) );
					$manualBucketButton.prop( 'disabled', true );

					var data = {
						action     : 'as3cf-manual-save-bucket',
						bucket_name: bucketName,
						_nonce     : as3cf_i18n.manual_bucket_nonce
					};

					$.ajax( {
						url     : ajaxurl,
						type    : 'POST',
						dataType: 'JSON',
						data    : data,
						error   : function( jqXHR, textStatus, errorThrown ) {
							$manualBucketButton.text( origManualButtonText );
							show_bucket_error( as3cf_i18n.save_bucket_error, errorThrown );
						},
						success : function( data, textStatus, jqXHR ) {
							$manualBucketButton.text( origManualButtonText );
							$manualBucketButton.prop( 'disabled', false );
							if ( typeof data[ 'success' ] !== 'undefined' ) {
								bucket_select( bucketName, data[ 'region' ], data[ 'can_write' ] );
								$( '.as3cf-bucket-list a' ).removeClass( 'selected' );
							} else {
								show_bucket_error( as3cf_i18n.save_bucket_error, data[ 'error' ] );
							}
						}
					} );
				} );
			}

			var $changeBucket = $container.find( '.as3cf-change-bucket' );
			if ( $changeBucket.length ) {
				$changeBucket.on( 'click', function( e ) {
					e.preventDefault();
					$( '.updated' ).not( '.as3cf-notice' ).hide();
					$( '.as3cf-can-write-error' ).hide();
					$( '.as3cf-settings' ).removeClass( 'as3cf-has-bucket' );
					if ( $( '.as3cf-bucket-list-wrapper' ).is( ':visible' ) ) {
						loadBuckets();
						if ( $( '.as3cf-active-bucket' ).html ) {
							$( '.as3cf-cancel-bucket-select-wrap' ).show();
						}
					}
				} );
			}

			var $refreshBuckets = $container.find( '.as3cf-refresh-buckets' );
			if ( $refreshBuckets.length ) {
				$refreshBuckets.on( 'click', function( e ) {
					e.preventDefault();
					loadBuckets();
				} );
			}

			var $cancelChangeBucket = $container.find( '.as3cf-cancel-bucket-select' );
			if ( $cancelChangeBucket.length ) {
				$cancelChangeBucket.on( 'click', function( e ) {
					e.preventDefault();
					$( '.as3cf-bucket-error' ).hide();
					$( '.as3cf-settings' ).addClass( 'as3cf-has-bucket' );
				} );
			}

		} );

		var $bucketList = $( '.as3cf-bucket-list' );

		function loadBuckets() {
			$( '.as3cf-bucket-error' ).hide();
			$bucketList.html( '<li class="loading">' + $bucketList.attr( 'data-working' ) + '</li>' );

			var data = {
				action: 'as3cf-get-buckets',
				_nonce: as3cf_i18n.get_buckets_nonce
			};

			$.ajax( {
				url     : ajaxurl,
				type    : 'POST',
				dataType: 'JSON',
				data    : data,
				error   : function( jqXHR, textStatus, errorThrown ) {
					$bucketList.html( '' );
					show_bucket_error( as3cf_i18n.get_buckets_error, errorThrown );
				},
				success : function( data, textStatus, jqXHR ) {
					$bucketList.html( '' );
					if ( typeof data[ 'success' ] !== 'undefined' ) {
						$( data[ 'buckets' ] ).each( function( idx, bucket ) {
							var bucket_class = (
							bucket.Name === data[ 'selected' ]
							) ? 'selected' : '';
							$bucketList.append( '<li><a class="' + bucket_class + '" href="#" data-bucket="' + bucket.Name + '"><span class="bucket"><span class="dashicons dashicons-portfolio"></span> ' + bucket.Name + '</span><span class="spinner"></span></span></a></li>' );
						} );
						scroll_to_selected_bucket();
					} else {
						show_bucket_error( as3cf_i18n.get_buckets_error, data[ 'error' ] );
					}
				}
			} );
		}

		$bucketList.on( 'click', 'a', function( e ) {
			e.preventDefault();

			if ( $( this ).hasClass( 'selected' ) ) {
				$( '.as3cf-settings' ).addClass( 'as3cf-has-bucket' );
				return;
			}

			var bucket = this;
			var previous_bucket = $( '.as3cf-bucket-list a.selected' ).attr( 'data-bucket' );

			$( '.as3cf-bucket-list a' ).removeClass( 'selected' );
			$( bucket ).addClass( 'selected' );

			$bucketList.addClass( 'saving' );
			$( bucket ).find( '.spinner' ).show();
			var bucketName = $( bucket ).attr( 'data-bucket' );

			var data = {
				action     : 'as3cf-save-bucket',
				bucket_name: bucketName,
				_nonce     : as3cf_i18n.save_bucket_nonce
			};

			$.ajax( {
				url     : ajaxurl,
				type    : 'POST',
				dataType: 'JSON',
				data    : data,
				error   : function( jqXHR, textStatus, errorThrown ) {
					$bucketList.removeClass( 'saving' );
					show_bucket_error( as3cf_i18n.save_bucket_error, errorThrown );
					$( '.as3cf-bucket-list a' ).removeClass( 'selected' );
					$( '.as3cf-bucket-list a[data-bucket="' + previous_bucket + '"]' ).addClass( 'selected' );
				},
				success : function( data, textStatus, jqXHR ) {
					$( bucket ).find( '.spinner' ).hide();
					$bucketList.removeClass( 'saving' );
					if ( typeof data[ 'success' ] !== 'undefined' ) {
						bucket_select( bucketName, data[ 'region' ], data[ 'can_write' ] );
					} else {
						show_bucket_error( as3cf_i18n.save_bucket_error, data[ 'error' ] );
						$( '.as3cf-bucket-list a' ).removeClass( 'selected' );
						$( '.as3cf-bucket-list a[data-bucket="' + previous_bucket + '"]' ).addClass( 'selected' );
					}
				}
			} );
		} );

		function scroll_to_selected_bucket() {
			if ( ! $( '.as3cf-bucket-list a.selected' ).length ) {
				return;
			}

			var offset = $( 'ul.as3cf-bucket-list li' ).first().position().top + 150;

			$( 'ul.as3cf-bucket-list' ).animate( {
				scrollTop: $( 'ul.as3cf-bucket-list li a.selected' ).position().top - offset
			} );
		}

		function show_bucket_error( title, error ) {
			$( '.as3cf-bucket-error span.title' ).html( title );
			$( '.as3cf-bucket-error span.message' ).html( error );
			$( '.as3cf-bucket-error' ).show();
		}

		function bucket_select( bucket, region, can_write ) {
			if ( '' === $( '.as3cf-active-bucket' ).text() ) {
				// first time bucket select - enable main options by default
				set_checkbox( 'copy-to-s3-wrap' );
				set_checkbox( 'serve-from-s3-wrap' );
			}
			$( '.as3cf-active-bucket' ).text( bucket );
			$( 'form.as3cf-manual-save-bucket-form .as3cf-bucket-name' ).val( bucket );
			$( '#as3cf-bucket' ).val( bucket );
			$( '#as3cf-region' ).val( region );
			$( '.updated' ).not( '.as3cf-notice' ).show();
			// check permission on bucket
			if ( can_write === false ) {
				$( '.as3cf-can-write-error' ).show();
			}
			$( '.as3cf-settings' ).addClass( 'as3cf-has-bucket' );
			generate_url_preview();
		}

		$( '.as3cf-switch' ).on( 'click', 'span', function( e ) {
			if ( ! $( this ).parent().hasClass( 'disabled' ) ) {
				var parent_id = $( this ).parent().attr( 'id' );
				set_checkbox( parent_id );
			}
		} );

		function set_checkbox( checkbox_wrap ) {
			$( '#' + checkbox_wrap + ' span' ).toggleClass( 'checked' );
			var switch_on = $( '#' + checkbox_wrap + ' span.on' ).hasClass( 'checked' );
			var checkbox_name = $( '#' + checkbox_wrap ).data( 'checkbox' );
			var $checkbox = $( 'input#' + checkbox_name );
			$checkbox.attr( "checked", switch_on );
			$checkbox.trigger( "change" );
		}

		if ( $( '.as3cf-settings' ).length && ! $( '.as3cf-settings' ).hasClass( 'as3cf-has-bucket' ) ) {
			if ( $( '.as3cf-bucket-list-wrapper' ).is( ':visible' ) ) {
				loadBuckets();
			}
		}

		$( '.as3cf-settings' ).on( 'change', '.sub-toggle', function( e ) {
			var setting = $( this ).attr( 'id' );
			$( '.as3cf-setting.' + setting ).toggleClass( 'hide' );
		} );

		$( '.as3cf-settings' ).on( 'click', '.as3cf-manual-bucket-toggle', function( e ) {
			e.preventDefault();
			$( '.as3cf-bucket-select' ).addClass( 'manual' );
		} );

		$( '.as3cf-settings' ).on( 'click', '.as3cf-bucket-list-toggle', function( e ) {
			e.preventDefault();
			loadBuckets();
			$( '.as3cf-bucket-select' ).removeClass( 'manual' );
			$( '.as3cf-cancel-bucket-select-wrap' ).show();
		} );

		$( '.as3cf-domain' ).on( 'change', 'input[type="radio"]', function( e ) {
			var domain = $( 'input:radio[name="domain"]:checked' ).val();
			if ( 'cloudfront' === domain && $( '.as3cf-setting.cloudfront' ).hasClass( 'hide' ) ) {
				$( '.as3cf-setting.cloudfront' ).removeClass( 'hide' );
			} else {
				$( '.as3cf-setting.cloudfront' ).addClass( 'hide' );
			}
		} );

		$( '.as3cf-ssl' ).on( 'change', 'input[type="radio"]', function( e ) {
			var ssl = $( 'input:radio[name="ssl"]:checked' ).val();
			if ( 'https' === ssl ) {
				var domain = $( 'input:radio[name="domain"]:checked' ).val();
				if ( 'subdomain' === domain ) {
					$( 'input[name="domain"][value="path"]' ).attr( "checked", true );
				}
				$( '.subdomain-wrap input' ).attr( 'disabled', true );
				$( '.subdomain-wrap' ).addClass( 'disabled' );
			} else {
				$( '.subdomain-wrap input' ).removeAttr( 'disabled' );
				$( '.subdomain-wrap' ).removeClass( 'disabled' );
			}
		} );

		$( '.url-preview' ).on( 'change', 'input', function( e ) {
			generate_url_preview();
		} );

		function generate_url_preview() {
			$( '.as3cf-url-preview' ).html( 'Generating...' );

			var data = {
				_nonce: as3cf_i18n.get_url_preview_nonce
			};

			$.each( $( ".as3cf-main-settings form" ).serializeArray(), function( i, o ) {
				var n = o.name,
					v = o.value;
				n = n.replace( '[]', '' );
				data[ n ] = data[ n ] === undefined ? v
					: $.isArray( data[ n ] ) ? data[ n ].concat( v )
					: [ data[ n ], v ];
			} );

			// overwrite the save action stored in the form
			data[ 'action' ] = 'as3cf-get-url-preview';

			$.ajax( {
				url     : ajaxurl,
				type    : 'POST',
				dataType: 'JSON',
				data    : data,
				error   : function( jqXHR, textStatus, errorThrown ) {
					alert( as3cf_i18n.get_url_preview_error + errorThrown );
				},
				success : function( data, textStatus, jqXHR ) {
					if ( typeof data[ 'success' ] !== 'undefined' ) {
						$( '.as3cf-url-preview' ).html( data[ 'url' ] );
					} else {
						alert( as3cf_i18n.get_url_preview_error + data[ 'error' ] );
					}
				}
			} );
		}

		/**
		 * Return the serialized string of the settings form
		 * excluding the bucket and region inputs as they get saved via AJAX
		 *
		 * @returns {string}
		 */
		function serialized_form() {
			return $( '.as3cf-main-settings form' ).find( 'input:not(.no-compare)' ).serialize();
		}

		// save the original state of the form for comparison later
		saved_settings = serialized_form();

		// let the save settings submit happen as normal
		$( document ).on( 'submit', '.as3cf-main-settings form', function( event ) {
			// disable unload warning
			$( window ).off( 'beforeunload.as3cf-settings' );
		} );

		// prompt user with dialog if leaving the settings page with unsaved changes
		$( window ).on( 'beforeunload.as3cf-settings', function() {
			if ( serialized_form() !== saved_settings ) {
				return as3cf_i18n.save_alert;
			}
		} );

	} );

})(jQuery);