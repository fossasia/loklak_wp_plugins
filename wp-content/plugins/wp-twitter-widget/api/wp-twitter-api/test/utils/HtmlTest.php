<?php
/**
 * @group utils
 * @group html
 */
class HtmlTest extends PHPUnit_Framework_TestCase {

    
    public function testUsersLink(){
        $text = 'Hi @timwhitlock!';
        $html = twitter_api_html( $text );
        $want = 'Hi <a class="twitter-screen-name" href="https://twitter.com/timwhitlock" target="_blank" rel="nofollow">@timwhitlock</a>!';
        $this->assertEquals( $want, $html );
    }    


    public function testUsersLinkWithEntities(){
        $text = '@ignore @timwhitlock!';
        $mock = array( 'user_mentions' => array (
            array( 'screen_name' => 'timwhitlock', 'indices' => '_ignored_' ),
        ) );
        $html = twitter_api_html_with_entities( $text, $mock );
        $want = '@ignore <a class="twitter-screen-name" href="https://twitter.com/timwhitlock" target="_blank" rel="nofollow">&#x40;timwhitlock</a>!';
        $this->assertEquals( $want, $html );
    }


    public function testHashtag(){
        $text = 'Foo #Bar!';
        $html = twitter_api_html( $text );
        $want = 'Foo <a class="twitter-hashtag" href="https://twitter.com/search?q=%23Bar&amp;src=hash" target="_blank" rel="nofollow">#Bar</a>!';
        $this->assertEquals( $want, $html );
    }    


    public function testHashtagWithEntities(){
        $text = 'Foo #Bar! #ignore';
        $mock = array( 'hashtags' => array (
            array( 'text' => 'Bar!', 'indices' => '_ignored_' ),
        ) );
        $html = twitter_api_html_with_entities( $text, $mock );
        $want = 'Foo <a class="twitter-hashtag" href="https://twitter.com/search?q=%23Bar%21&amp;src=hash" target="_blank" rel="nofollow">&#x23;Bar!</a> #ignore';
        $this->assertEquals( $want, $html );
    }    
    
    
    

}


