<?php

/**
 * Send mail, similar to PHP's mail
 *
 * A true return value does not automatically mean that the user received the
 * email successfully. It just only means that the method used was able to
 * process the request without any errors.
 *
 * Using the two 'wp_mail_from' and 'wp_mail_from_name' hooks allow from
 * creating a from address like 'Name <email@address.com>' when both are set. If
 * just 'wp_mail_from' is set, then just the email address will be used with no
 * name.
 *
 * The default content type is 'text/plain' which does not allow using HTML.
 * However, you can set the content type of the email by using the
 * 'wp_mail_content_type' filter.
 *
 * The default charset is based on the charset used on the blog. The charset can
 * be set using the 'wp_mail_charset' filter.
 *
 * @since 1.2.1
 * @uses apply_filters() Calls 'wp_mail' hook on an array of all of the parameters.
 * @uses apply_filters() Calls 'wp_mail_from' hook to get the from email address.
 * @uses apply_filters() Calls 'wp_mail_from_name' hook to get the from address name.
 * @uses apply_filters() Calls 'wp_mail_content_type' hook to get the email content type.
 * @uses apply_filters() Calls 'wp_mail_charset' hook to get the email charset
 *
 * @param string|array $to Array or comma-separated list of email addresses to send message.
 * @param string $subject Email subject
 * @param string $message Message contents
 * @param string|array $headers Optional. Additional headers.
 * @param string|array $attachments Optional. Files to attach.
 * @return bool Whether the email contents were sent successfully.
 */
function wp_mail( $to, $subject, $message, $headers = '', $attachments = array() ) {
	$options = WP_SendGrid_Settings::get_settings();
	// Compact the input, apply the filters, and extract them back out
	extract( apply_filters( 'wp_mail', compact( 'to', 'subject', 'message', 'headers', 'attachments' ) ) );

	// If message just has \n SendGrid doesn't get newlines
	// Might only be a problem on Windows systems
	$message = str_replace( "\n", "\r\n", $message );

	if ( !is_array($attachments) )
		$attachments = explode( "\n", str_replace( "\r\n", "\n", $attachments ) );

	// Headers
	if ( empty( $headers ) ) {
		$headers = array();
	} else {
		if ( !is_array( $headers ) ) {
			// Explode the headers out, so this function can take both
			// string headers and an array of headers.
			$tempheaders = explode( "\n", str_replace( "\r\n", "\n", $headers ) );
		} else {
			$tempheaders = $headers;
		}
		$headers = array();
		$cc = array();
		$bcc = array();

		// If it's actually got contents
		if ( !empty( $tempheaders ) ) {
			// Iterate through the raw headers
			foreach ( (array) $tempheaders as $header ) {
				if ( strpos($header, ':') === false ) {
					if ( false !== stripos( $header, 'boundary=' ) ) {
						$parts = preg_split('/boundary=/i', trim( $header ) );
						$boundary = trim( str_replace( array( "'", '"' ), '', $parts[1] ) );
					}
					continue;
				}
				// Explode them out
				list( $name, $content ) = explode( ':', trim( $header ), 2 );

				// Cleanup crew
				$name    = trim( $name    );
				$content = trim( $content );

				switch ( strtolower( $name ) ) {
					// Mainly for legacy -- process a From: header if it's there
					case 'from':
						if ( strpos($content, '<' ) !== false ) {
							// So... making my life hard again?
							$from_name = substr( $content, 0, strpos( $content, '<' ) - 1 );
							$from_name = str_replace( '"', '', $from_name );
							$from_name = trim( $from_name );

							$from_email = substr( $content, strpos( $content, '<' ) + 1 );
							$from_email = str_replace( '>', '', $from_email );
							$from_email = trim( $from_email );
						} else {
							$from_email = trim( $content );
						}
						break;
					case 'content-type':
						if ( strpos( $content, ';' ) !== false ) {
							list( $type, $charset ) = explode( ';', $content );
							$content_type = trim( $type );
							if ( false !== stripos( $charset, 'charset=' ) ) {
								$charset = trim( str_replace( array( 'charset=', '"' ), '', $charset ) );
							} elseif ( false !== stripos( $charset, 'boundary=' ) ) {
								$boundary = trim( str_replace( array( 'BOUNDARY=', 'boundary=', '"' ), '', $charset ) );
								$charset = '';
							}
						} else {
							$content_type = trim( $content );
						}
						break;
					case 'cc':
						$cc = array_merge( (array) $cc, explode( ',', $content ) );
						break;
					case 'bcc':
						$bcc = array_merge( (array) $bcc, explode( ',', $content ) );
						break;
					default:
						// Add it to our grand headers array
						$headers[trim( $name )] = trim( $content );
						break;
				}
			}
		}
	}

	// From email and name
	// If we don't have a name from the input headers
	if ( !isset( $from_name ) )
		$from_name = 'WordPress';

	/* If we don't have an email from the input headers default to wordpress@$sitename
	 * Some hosts will block outgoing mail from this address if it doesn't exist but
	 * there's no easy alternative. Defaulting to admin_email might appear to be another
	 * option but some hosts may refuse to relay mail from an unknown domain. See
	 * http://trac.wordpress.org/ticket/5007.
	 */

	if ( !isset( $from_email ) ) {
		// Get the site domain and get rid of www.
		$sitename = strtolower( $_SERVER['SERVER_NAME'] );
		if ( substr( $sitename, 0, 4 ) == 'www.' ) {
			$sitename = substr( $sitename, 4 );
		}

		$from_email = 'wordpress@' . $sitename;
	}

	// Plugin authors can override the potentially troublesome default
	$from_email		= apply_filters( 'wp_mail_from'     , $from_email );
	$from_name		= apply_filters( 'wp_mail_from_name', $from_name  );

	// Set destination addresses
	if ( !is_array( $to ) )
		$to = explode( ',', $to );

	// Set Content-Type and charset
	// If we don't have a content-type from the input headers
	if ( !isset( $content_type ) )
		$content_type = 'text/plain';

	$content_type = apply_filters( 'wp_mail_content_type', $content_type );

	/* TODO
	// Set whether it's plaintext, depending on $content_type

		$phpmailer->IsHTML( true ); */

	// TODO: I don't know that SendGrid supports different charsets. Shouldn't be a problem in most cases
	/*
	 * // If we don't have a charset from the input headers
	 * if ( !isset( $charset ) )
	 *	$charset = get_bloginfo( 'charset' );
	 *
	 *
	 * // Set the content-type and charset
	 * $charset = apply_filters( 'wp_mail_charset', $charset );
	 */
	$attachment_array = array();
	if ( !empty( $attachments ) ) {
		if ( is_string( $attachments ) ) {
			$attachments = preg_split( '/,\s*/', $attachments );
		}
		if ( is_array( $attachments) ) {
			foreach ( $attachments as $attachment ) {
				if ( file_exists( $attachment ) ) {
					$contents = file_get_contents( $attachment );
					$file = basename( $attachment );
					$attachment_array[$file] = $contents;
				}
			}
		}
	}

	$args = array(
		'to' => $to,
		'subject' => $subject,
		'text' => $message,
		'api_user' => $options['username'],
		'api_key' => $options['password'],
		'files' => $attachment_array,
		'from' => $from_email,
		'fromname' => $from_name,
	);

	$xsmtpapi = apply_filters( 'wp_sendgrid_xsmtpapi', array(), $to, $subject, $message, $headers, $attachment_array );
	if ( !empty( $xsmtpapi ) ) {
		$args['x-smtpapi'] = json_encode( $xsmtpapi );
	}

	if ( 'text/plain' !== $content_type ) {
		$args['html'] = $message;
	}

	$args = apply_filters( 'wp_sendgrid_args', $args, $to, $subject, $message, $headers, $attachment_array );

	$response = json_decode( wp_remote_retrieve_body( wp_remote_post( 'https://sendgrid.com/api/mail.send.json', array( 'sslverify' => false, 'body' => $args ) ) ) );
	if ( isset( $response->message ) && $response->message == 'success' ) {
		return true;
	}

	return false;
}