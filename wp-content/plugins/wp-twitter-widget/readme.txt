=== Plugin Name ===
Contributors: timwhitlock
Donate link: http://timwhitlock.info/donate-to-a-project/
Tags: twitter, tweets, oauth, api, rest, api, widget, sidebar
Requires at least: 3.5.1
Tested up to: 4.1.1
Stable tag: 1.1.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Latest Tweets widget compatible with the new Twitter API 1.1

== Description ==

Connect your Twitter account to this plugin and the widget will display your latest tweets on your site.

This plugin is compatible with the new **Twitter API 1.1** and provides full **OAuth** authentication via the Wordpress admin area.
 

Built by <a href="//twitter.com/timwhitlock">@timwhitlock</a> / <a rel="author" href="https://plus.google.com/106703751121449519322">Tim Whitlock</a>

The underlying Twitter API library is [available on Github](https://github.com/timwhitlock/wp-twitter-api)

Also by this author: [Loco Translate](http://wordpress.org/plugins/loco-translate/)


== Installation ==

1. Unzip all files to the `/wp-content/plugins/` directory
2. Log into Wordpress admin and activate the 'Latest Tweets' plugin through the 'Plugins' menu

Once the plugin is installed and enabled you can bind it to a Twitter account as follows:

3. Register a Twitter application at https://dev.twitter.com/apps
4. Note the Consumer key and Consumer secret under OAuth settings
5. Log into Wordpress admin and go to Settings > Twitter API
6. Enter the consumer key and secret and click 'Save settings'
7. Click the 'Connect to Twitter' button and follow the prompts.

Once your site is authenticated you can configure the widget as follows:

8. Log into Wordpress admin and go to Appearance > Widgets
9. Drag 'Latest Tweets' from 'Available widgets' to where you want it. e.g. Main Sidebar
10. Optionally configure the widget title and number of tweets to display.

== Frequently Asked Questions ==

= How can I style the widget? =

See the 'Other Notes' tab for theming information.

= Why do I have to register my own Twitter app? =

Because I'm providing code, not a service. If I set up a Twitter app for this plugin I'd be responsible for every person who uses it. 
If Twitter closed my account or revoked my keys every instance of this plugin would break. Twitter also place limits on the number of users that can connect to a single app.

= How I do know what my OAuth settings are? =

These details are available in the [Twitter dashboard](https://dev.twitter.com/apps)

= What do I put in the third and fourth fields? =

Once you've populated the first two fields, just click the *Connect* button and follow the prompts.

= What is the "Minimum popularity" field? =

Here you can specify a number of retweets and favourites that a tweet must have before it's displayed.
This is useful for only showing your most interesting content.

= How can I prevent SSL certificate errors? =

If you're unable too fix your [PHP cURL](https://php.net/manual/en/book.curl.php) installation, you can disable SSL verification of twitter.com by adding this to your theme functions.php:  
`add_filter('https_ssl_verify', '__return_false');`  
Do so at your own risk.

= Does this plugin show Emoji images in tweets? =

Yes, as of version 1.1.2 Emojis are rendered the same as on twitter.com. See the [Other Notes](http://wordpress.org/plugins/latest-tweets-widget/other_notes/) section for how to disable Emoji.


== Screenshots ==

1. Widget screen shows feed options
2. Admin screen shows Twitter connect button and OAuth settings

== Changelog ==

= 1.1.3 =
* Query string encoding fix
* Added Spanish translations
* Fixed missing text domain in date utils 

= 1.1.2 =
* Added Emoji image rendering

= 1.1.1 =
* broken release, don't use.

= 1.1.0 =
* Handling of truncated retweets
* Restructured library directory
* More friendly front end error when not configured
* Caching disabled in debug mode
* Empty timezone_string fix
* Better tweet linkifying using entities
* Better l10n bootstrapping
* Added minimum tweet popularity

= 1.0.15 =
* Passing additional params to widget_title filter
* Stripping four-byte Unicode sequences before wp cache inserts

= 1.0.14 =
* Timezone fixes
* Fixed bad status link
* Checking if APC disabled 
* Added Dutch translations

= 1.0.13 =
* Added Russian translations
* Fixed E_STRICT warning
* Passing more arguments to filters including profile data

= 1.0.12 =
* Critical bug fix affecting some older versions of PHP

= 1.0.11 =
* Better fulfillment of tweet count when skipping retwteets and replies
* Manual RTs now excluded when "Show Retweets" is disabled
* Caching applies to rendered tweets instead of raw API data
* Updated some German translations

= 1.0.10 =
* Added shortcode support
* Fixed bug rendering url fragments as hashtags

= 1.0.9 =
* Fixed pluralisation bug in date printing
* Now expanding t.co links unless render_text filter is used

= 1.0.8 =
* Added `latest_tweets_cache_seconds` filter
* Added German translations

= 1.0.7 =
* Allow library coexist across plugins

= 1.0.6 =
* Enabled translations and added pt_BR
* Switched dates to use i18n date formatter

= 1.0.5 =
* Moved widget title outside latest-tweets wrapper
* Using WordPress 'transient' cache when APC not available 

= 1.0.4 =
* Library update fixes dates for old PHP versions 

= 1.0.3 =
* Added theme filters
* Added configs for showing replies and RTs

= 1.0.2 =
* Fixed hook for PHP < 5.3

= 1.0.1 =
* First public release

== Upgrade Notice ==

= 1.1.3 =
* Minor bug fixes and improvements


== Shortcodes ==

You can embed tweets in the body of your posts using a Wordpress the shortcode `[tweets]`.

To specify a different user's timeline add the `user` attribute.  
To override the default number of 5 tweets add the `max` attribute, e.g: 

    [tweets max=10 user=timwhitlock]



== Theming ==

For starters you can alter some of the HTML using built-in WordPress features.  
See [Widget Filters](http://codex.wordpress.org/Plugin_API/Filter_Reference#Widgets)
and [Widgetizing Themes](http://codex.wordpress.org/Widgetizing_Themes)

**CSS**

This plugin contains no default CSS. That's deliberate, so you can style it how you want.

Tweets are rendered as a list which has various hooks you can use. Here's a rough template:

    .latest-tweets {
        /* style tweet list wrapper */
    }
    .latest-tweets h3 {
        /* style whatever you did with the header */
    }
    .latest-tweets ul { 
        /* style tweet list*/
    }
    .latest-tweets li {
       /* style tweet item */
    }
    .latest-tweets .tweet-text {
       /* style main tweet text */
    }
    .latest-tweets .tweet-text a {
       /* style links, hashtags and mentions */
    }
    .latest-tweets .tweet-text .emoji {
      /* style embedded emoji image in tweet */ 
    }
    .latest-tweets .tweet-details {
      /* style datetime and link under tweet */
    }


**Custom HTML**

If you want to override the default markup of the tweets, the following filters are also available:

* Add a header between the widget title and the tweets with `latest_tweets_render_before`
* Perform your own rendering of the timestamp with `latest_tweets_render_date`
* Render plain tweet text to your own HTML with `latest_tweets_render_text`
* Render each composite tweet with `latest_tweets_render_tweet`
* Override the unordered list for tweets with `latest_tweets_render_list` 
* Add a footer before the end of the widget with `latest_tweets_render_after`

Here's an **example** of using some of the above in your theme's functions.php file:

    add_filter('latest_tweets_render_date', function( $created_at ){
        $date = DateTime::createFromFormat('D M d H:i:s O Y', $created_at );
        return $date->format('d M h:ia');
    }, 10 , 1 );
    
    add_filter('latest_tweets_render_text', function( $text ){
        return $text; // <- will use default
    }, 10 , 1 );
    
    add_filter('latest_tweets_render_tweet', function( $html, $date, $link, array $tweet ){
        $pic = $tweet['user']['profile_image_url_https'];
        return '<p class="my-tweet"><img src="'.$pic.'"/>'.$html.'</p><p class="my-date"><a href="'.$link.'">'.$date.'</a></p>';
    }, 10, 4 );
    
    add_filter('latest_tweets_render_after', function(){
        return '<footer><a href="https://twitter.com/me">More from me</a></footer>';
    }, 10, 0 );

== Caching ==

Responses from the Twitter API are cached for 5 minutes by default. This means your new Tweets will not appear on your site in real time.

This is deliberate not only for performance, but also to avoid Twitter's strict rate limits of 15 requests every 15 minutes. 

You can override the 300 second cache by using the `latest_tweets_cache_seconds` filter in your theme as follows:

This would extend the cache to 1 minute, which is the lowest value you should consider using on a live site:

    add_filter('latest_tweets_cache_seconds', function( $ttl ){
        return 60;
    }, 10, 1 );

This would disable the cache (not recommended other than for debugging):

    add_filter('latest_tweets_cache_seconds', function( $ttl ){
        return 0;
    }, 10, 1 );


== Emoji ==

If you want to disable Emoji image replacement, you can filter the replacement callback function to something empty, e.g:

    add_filter('latest_tweets_emoji_callback', function( $func ){
        return '';
    } );

- or to strip Emoji characters from all tweets, return your own replacement function that returns something else, e.g:

    add_filter('latest_tweets_emoji_callback', function( $func ){
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

