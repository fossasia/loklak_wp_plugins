<?php
/**
 * @group utils
 * @group utf8
 */
class UnicodeTest extends PHPUnit_Framework_TestCase {
    
    public function testAsciiPassThrough(){
        $ints = twitter_api_utf8_array( 'abc' );
        $this->assertSame( array(97,98,99), $ints );
    }
    
    public function testAsciiPassthroughReverse(){
        $chr = twitter_api_utf8_chr( 97 );
        $this->assertSame( 'a', $chr );
    }
    

    public function testTwoByteCharacter(){
        // U+00A9 copyright symbol
        $text = "\xC2\xA9";
        $ints = twitter_api_utf8_array( $text );
        $this->assertCount( 1, $ints );
        $this->assertSame( 0x00A9, $ints[0] );
    }    
    
    
    public function testTwoByteCharacterReverse(){
        $chr = twitter_api_utf8_chr( 0x00A9 );
        $this->assertSame( "\xC2\xA9", $chr );
    }


    public function testThreeByteCharacter(){
        // U+2122 trademark symbol
        $text = "\xE2\x84\xA2";
        $ints = twitter_api_utf8_array( $text );
        $this->assertCount( 1, $ints );
        $this->assertSame( 0x2122, $ints[0] );
    }    
    

    public function testThreeByteCharacterReverse(){
        $chr = twitter_api_utf8_chr( 0x2122 );
        $this->assertSame( "\xE2\x84\xA2", $chr );
    }
    
    
    public function testFourByteCharacter(){
        // mahjong tile red dragon
        $text = "\xF0\x9F\x80\x84";
        $ints = twitter_api_utf8_array( $text );
        $this->assertCount( 1, $ints );
        $this->assertSame( 0x1F004, $ints[0] );
    }
    
    
    public function testFourByteCharacterReverse(){
        $chr = twitter_api_utf8_chr( 0x1F004 );
        $this->assertSame( "\xF0\x9F\x80\x84", $chr );
    }


    public function testVariableByteLengthMixed(){
        $ints = twitter_api_utf8_array("A\xC2\xA9B\xE2\x84\xA2C\xF0\x9F\x80\x84D");
        $this->assertSame( array( ord('A'), 0xA9, ord('B'), 0x2122, ord('C'), 0x1F004, ord('D') ), $ints );
    }
    
    
    public function testHexCodesCorrectLengthAndCase(){
        $text = twitter_api_unicode_implode( array( 0x97, 0xA9, 0x2122, 0x1F004 ) );
        $this->assertSame( '0097-00a9-2122-1f004', $text );
    }
    
    
}
