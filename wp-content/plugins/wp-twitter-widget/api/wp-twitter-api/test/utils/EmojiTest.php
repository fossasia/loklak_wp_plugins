<?php
/**
 * @group utils
 * @group emoji
 */
class EmojiTest extends PHPUnit_Framework_TestCase {

    
    public function _replace_blank( array $match ){
        $ref = twitter_api_emoji_ref( $match[0] );
        return $ref ? '' : 'invalid U+'.implode('',twitter_api_utf8_array($match[0]));
    }
    
    public function _replace_valid( array $match ){
        $ref = twitter_api_emoji_ref( $match[0] );
        return $ref ? 'valid' : 'invalid';
    }
    
    public function _replace_hexref( array $match ){
        return $ref = twitter_api_emoji_ref( $match[0] );
    }
    
    
    private function get_all(){
        static $emoji;
        if( ! isset($emoji) ){
            $emoji = include twitter_api_basedir().'/inc/return-emoji.php';
        }
        return $emoji;
    }

    
    /**
     * Match every emoji character with simple replacement callback
     */
    public function testSingleMatches(){
        $blanker = array( $this, '_replace_blank' );
        foreach( $this->get_all() as $raw => $key ){
            $replaced = twitter_api_replace_emoji( 'o'.$raw.'k', $blanker );
            $this->assertEquals( 'ok', $replaced );
        }
    }
    

    /**
     * Match all emoji characters in single block
     */    
    public function testTotalMatch(){
        $blanker = array( $this, '_replace_blank' );
        $splurge = implode( '', array_keys( $this->get_all() ) );
        $replaced = twitter_api_replace_emoji( 'o'.$splurge.'k', $blanker );
        $this->assertEquals( 'ok', $replaced );
    }    
    

    /**
     * Convert matched bytes back to unicode string reference
     */    
    public function testAllSequencesResolveUnicode(){
        foreach( $this->get_all() as $raw => $key ){
            $codes = twitter_api_utf8_array( $raw );
            $ucode = twitter_api_unicode_implode( $codes );
            $this->assertEquals( $key, $ucode );
        }
    }
    
    
    /**
     * Test default URL replacement
     */
    public function testDefaultUrlReplacement(){
        foreach( $this->get_all() as $raw => $key ){
            $html = twitter_api_replace_emoji( $raw );
            $want = '<img src="https://abs.twimg.com/emoji/v1/72x72/'.$key.'.png" style="width:1em;" class="emoji emoji-'.$key.'" />';
            $this->assertEquals( $want, $html );
        }
    }


    /**
     * Twitter emoji image files are lower case hex with ascii characters reduced to two characters 
     */
    public function testCorrectFormatHex(){
        $replacer = array( $this, '_replace_hexref' );
        // TM / Grinning Cat / GB flag / Enclosed 6 
        $text = "\xE2\x84\xA2 \xF0\x9F\x98\xB8 \xF0\x9F\x87\xAC\xF0\x9F\x87\xA7 \x36\xE2\x83\xA3";
        $want = '2122 1f638 1f1ec-1f1e7 36-20e3';
        $this->assertEquals( $want, twitter_api_replace_emoji( $text, $replacer ) );
    }     


    /**
     * Test common false positives
     */
    public function testFancyQuotesIntact(){
        $test = array ( 
            0x2018, 
            0x2019,
            0x201C,
            0x201D,
        );
        $validator = array( $this, '_replace_valid' );        
        foreach( $test as $code ){
            $hex = sprintf('%04x', $code );
            $leave = twitter_api_utf8_chr( $code );
            $bytes = twitter_api_unicode_debug_string( $leave );
            $valid = twitter_api_replace_emoji( $leave, $validator );
            $this->assertEquals( 'invalid', $valid, 'U+'.$hex.' wrongly matched: '.$bytes );
        }
    }
    
    
    /**
     * Test identification of enclosed characters
     */
    public function testEnclosingNumericsReplace(){
        $blanker = array( $this, '_replace_blank' );
        $text = "o0\xE2\x83\xA31\xE2\x83\xA3k"; 
        $text = twitter_api_replace_emoji( $text, $blanker );
        $this->assertEquals( 'ok', $text );
    }
    
}
