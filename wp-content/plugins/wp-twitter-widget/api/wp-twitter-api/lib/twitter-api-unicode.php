<?php
/**
 * UTF-8 / Unicode utilities.
 * Not currently used by plugin - just used in tests
 */

  
 /**
 * Utility resolves UTF-8 bytes to array of code points
 */
function twitter_api_utf8_array( $s ){
    $a = array();
    $len = strlen($s);
    for( $i = 0; $i < $len; $i++ ){
        $c = $s{ $i };
        $n = ord( $c );
        // 7-bit ASCII
        if( 0 === ( $n & 128 ) ){
            isset( $t ) and $a [] = $t;
            $a[] = $n;
            unset( $t );
        }
        // Subsequent 10xxxxxx character
        else if( isset($t) && ( $n & 192 ) === 128 ){
            $t <<= 6;
            $t |= ( $n & 63 ); 
        }
        // Leading char in 2 byte sequence "110xxxxx"
        else if( ( $n & 224 ) === 192 ){
            isset( $t ) and $a [] = $t;
            $t = ( $n & 31 );
        }
        // Leading char in 3 byte sequence "1110xxxx"
        else if( ( $n & 240 ) === 224 ){
            isset( $t ) and $a [] = $t;
            $t = ( $n & 15 ); 
        }
        // Leading char in 4 byte sequence "11110xxx"
        else if( ( $n & 248 ) === 240 ){
            isset( $t ) and $a [] = $t;
            $t = ( $n & 7 );
        }
        else {
            throw new Exception('Invalid utf8 string, unexpected character at offset '.$i);
        }
    }
    // left over
    isset( $t ) and $a [] = $t;
    return $a;
}
 
 
/**
 * Encode a Unicode code point to a utf-8 encoded string
 * @example functions/enc/utf8_chr.php
 * @param int Unicode code point up to 0x10FFFF
 * @return string multibyte character sequence
 */
function twitter_api_utf8_chr( $u ){
    if( 127 === ( $u | 127 ) ){
        // 7-bit ASCII
        return chr( $u );
    }
    // Double byte sequence ( < 0x800 )
    // 00000yyy yyzzzzzz ==> 110yyyyy 10zzzzzz
    // if( $u < 0x800 ) {
    if( 0 === ( $u & 0xFFFFF800 ) ){
        $c = chr( $u & 63 | 128 );            // "10zzzzzz"
        $c = chr( ($u>>=6) & 31 | 192 ) . $c; // "110yyyyy"
    }
    // Triple byte sequence ( < 0x10000 )
    // xxxxyyyy yyzzzzzz ==> 1110xxxx 10yyyyyy 10zzzzzz
    // else if( $u < 0x10000 ) {
    else if( 0 === ( $u & 0xFFFF0000 ) ){
        // Table 3-7 in the Unicode 5.0 standard disalows D800-DFFF:
        //if( $u >= 0xD800 && $u <= 0xDFFF ){
        //  trigger_error("Unicode code point $u is invalid", E_USER_NOTICE );
        //}
        $c = chr( $u & 63 | 128 );            // "10zzzzzz"
        $c = chr( ($u>>=6) & 63 | 128 ) . $c; // "10yyyyyy"
        $c = chr( ($u>>=6) & 15 | 224 ) . $c; // "1110xxxx"
    }
    // Four byte sequence ( < 0x10FFFF )
    // 000wwwxx xxxxyyyy yyzzzzzz ==> 11110www 10xxxxxx 10yyyyyy 10zzzzzz
    // else if( $u <= 0x10FFFF ) {
    else if( 0 === ( $u & 0xE0000000 ) ){
        $c = chr( $u & 63 | 128 );            // "10zzzzzz"
        $c = chr( ($u>>=6) & 63 | 128 ) . $c; // "10yyyyyy"
        $c = chr( ($u>>=6) & 63 | 128 ) . $c; // "10xxxxxx"
        $c = chr( ($u>>=6) &  7 | 240 ) . $c; // "11110www"
    }
    else {
        // integer too big 
        trigger_error("Unicode code point too large, $u", E_USER_NOTICE );
        $c = '?';
    }
    return $c;
}     
 
 
 
/**
 * Convert array of unicodes to hex string for use in URLs or class names
 */
function twitter_api_unicode_implode( array $codes, $glue = '-' ){
    foreach( $codes as $i => $n ){
        if( $n > 0x7F ){
            $codes[$i] = sprintf('%04x', $n );
        }
        else {
            $codes[$i] = sprintf('%02x', $n );
        }
    }
    return implode( $glue, $codes );
}


/**
 * split a utf-8 string into a visual representation of single bytes
 */
function twitter_api_unicode_debug_string( $raw ){
    $debug = array();
    for( $i = 0; $i < strlen($raw); $i++ ){
        $debug[] = sprintf( '\\x%0X', ord( $raw{$i} ) );
    }
    return implode('',$debug);
}   
 