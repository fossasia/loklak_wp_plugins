=== Plugin Name ===
Contributors: fossasia
Tags: twitter, loklak, loklak api, tweets, oauth, api, rest, api, widget, sidebar
Requires at least: 3.5.1
Tested up to: 4.5.3
Stable tag: 1.0.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Tweets Widget compatible with the new Twitter API 1.1

== Description ==

Use anonymous Loklak API OR Connect your Twitter account to this plugin and the widget will display your latest tweets on your site.

This plugin is compatible with the new **Twitter API 1.1** and provides full **OAuth** authentication via the Wordpress admin area.


== Installation ==

1. Unzip all files to the `/wp-content/plugins/` directory
2. Log into Wordpress admin and activate the 'Tweets' plugin through the 'Plugins' menu

Once the plugin is installed and enabled you can use Loklak API or bind your plugin to a Twitter account as follows:

**Use Loklak API**

3. Tick the 'Loklak API' checkbox in API authentication settings.
4. Click on 'Save settings'

OR

**Use Twitter 1.1 API**

3. Register a Twitter application at https://dev.twitter.com/apps
4. Note the Consumer key and Consumer secret under OAuth settings
5. Log into Wordpress admin and go to Settings > Twitter API
6. Enter the consumer key and secret and click 'Save settings'
7. Click the 'Connect to Twitter' button and follow the prompts.

Once your site is authenticated you can configure the widget as follows:

8. Log into Wordpress admin and go to Appearance > Widgets
9. Drag 'Tweets' from 'Available widgets' to where you want it. e.g. Main Sidebar
10. Optionally configure the widget title and number of tweets to display.

== Frequently Asked Questions ==

= How can I style the widget? =

See the 'Other Notes' tab for theming information.

= Do I have to register my own Twitter app? =

Yes, if you want to use Twitter's new API 1.1 . If you decide to use loklak.org's anonymous API then no need. More info in the 'Description' tab.

= How I do know what my Twitter OAuth settings are? =

These details are available in the [Twitter dashboard](https://dev.twitter.com/apps)

= What do I put in the third and fourth fields? =

Once you've populated the first two fields, just click the *Connect* button and follow the prompts.

= What is the "Minimum popularity" field? =

Here you can specify a number of retweets and favourites that a tweet must have before it's displayed.
This is useful for only showing your most interesting content.

= How can I prevent SSL certificate errors? =

If you're unable too fix your [PHP cURL](https://php.net/manual/en/book.curl.php) installation, you can disable SSL verification of twitter.com by adding this to your theme functions.php:  
`add_filter('https_ssl_verify', '__return_false');`  
But, please do so at your own risk.


== Screenshots ==

1. Tweets rendered via Loklak API
2. Admin screen shows Loklak and Twitter API connect button and OAuth settings
3. Widget screen shows feed options

== Changelog ==

= 1.0.1 =
* Fixes FAQs
* Fixes minor URL bugs

= 1.0 =
* A whole new version!


== Shortcodes ==

You can embed tweets in the body of your posts using a Wordpress the shortcode `[tweets]`.

To specify a different user's timeline add the `user` attribute.  
To override the default number of 5 tweets add the `max` attribute, e.g: 

    [tweets max=10 user=KhoslaSopan]



== Theming ==

For starters you can alter some of the HTML using built-in WordPress features.  
See [Widget Filters](http://codex.wordpress.org/Plugin_API/Filter_Reference#Widgets)
and [Widgetizing Themes](http://codex.wordpress.org/Widgetizing_Themes)

**CSS**

This plugin contains no default CSS. That's deliberate, so you can style it how you want.

Tweets are rendered as a list which has various hooks you can use. Here's a rough template:

    .tweets {
        /* style tweet list wrapper */
    }
    .tweets h3 {
        /* style whatever you did with the header */
    }
    .tweets ul { 
        /* style tweet list*/
    }
    .tweets li {
       /* style tweet item */
    }
    .tweets .tweet-text {
       /* style main tweet text */
    }
    .tweets .tweet-text a {
       /* style links, hashtags and mentions */
    }
    .tweets .tweet-text .emoji {
      /* style embedded emoji image in tweet */ 
    }
    .tweets .tweet-details {
      /* style datetime and link under tweet */
    }


**Custom HTML**

If you want to override the default markup of the tweets, the following filters are also available:

* Add a header between the widget title and the tweets with `tweets_render_before`
* Perform your own rendering of the timestamp with `tweets_render_date`
* Render plain tweet text to your own HTML with `tweets_render_text`
* Render each composite tweet with `tweets_render_tweet`
* Override the unordered list for tweets with `tweets_render_list` 
* Add a footer before the end of the widget with `tweets_render_after`

Here's an **example** of using some of the above in your theme's functions.php file:

    add_filter('tweets_render_date', function( $created_at ){
        $date = DateTime::createFromFormat('D M d H:i:s O Y', $created_at );
        return $date->format('d M h:ia');
    }, 10 , 1 );
    
    add_filter('tweets_render_text', function( $text ){
        return $text; // <- will use default
    }, 10 , 1 );
    
    add_filter('tweets_render_tweet', function( $html, $date, $link, array $tweet ){
        $pic = $tweet['user']['profile_image_url_https'];
        return '<p class="my-tweet"><img src="'.$pic.'"/>'.$html.'</p><p class="my-date"><a href="'.$link.'">'.$date.'</a></p>';
    }, 10, 4 );
    
    add_filter('tweets_render_after', function(){
        return '<footer><a href="https://twitter.com/me">More from me</a></footer>';
    }, 10, 0 );

== Caching ==

Responses from the Twitter API are cached for 5 minutes by default. This means your new Tweets will not appear on your site in real time.

This is deliberate not only for performance, but also to avoid Twitter's strict rate limits of 15 requests every 15 minutes. 

You can override the 300 second cache by using the `tweets_cache_seconds` filter in your theme as follows:

This would extend the cache to 1 minute, which is the lowest value you should consider using on a live site:

    add_filter('tweets_cache_seconds', function( $ttl ){
        return 60;
    }, 10, 1 );

This would disable the cache (not recommended other than for debugging):

    add_filter('tweets_cache_seconds', function( $ttl ){
        return 0;
    }, 10, 1 );


== Emoji ==

If you want to disable Emoji image replacement, you can filter the replacement callback function to something empty, e.g:

    add_filter('tweets_emoji_callback', function( $func ){
        return '';
    } );

- or to strip Emoji characters from all tweets, return your own replacement function that returns something else, e.g:

    add_filter('tweets_emoji_callback', function( $func ){
        return function( array $match ){
            return '<!-- removed emoji -->';
        };
    } );


== Credits ==

Screenshot taken with permission from http://stayingalivefoundation.org/blog


* Portuguese translations by [Leandro Dimitrio](http://wordpress.org/support/profile/leandrodimitrio)
* German translations by [Florian Felsing](https://twitter.com/FlorianFelsing) and [David Noh](http://wordpress.org/support/profile/david_noh)
* Russian translations by [Andrey Yakovenko](https://twitter.com/YakovenkoAndrey)
* Dutch translations by [Daniel Wichers](https://twitter.com/dwichers)
* Spanish translations by [Pedro Pica](http://minimizo.com)


== Notes ==

Be aware of [Twitter's display requirements](https://dev.twitter.com/terms/display-requirements) when rendering tweets on your website.

Example code here uses PHP [closures](http://www.php.net/manual/en/class.closure.php) which require PHP>=5.3.0 and won't work on older systems.
