=== DevBuddy Twitter Feed ===

Contributors: EjiOsigwe
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=XMXJEVPQ35YMJ
Tags: Twitter, Twitter Feed, Twitter 1.1, Twitter API, Twitter Shortcode, Twitter tweet, tweets, Twitter, Twitter connect, Twitter share, Twitter share button, DevBuddy
Requires at least: 3.1.0
Tested up to: 4.2.1
Stable tag: 4.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A Twitter (v1.1) feed plugin for the developers that's fully customisable and support timelines, searches and lists.

== Description ==

**NOTE: This plugin requires your server to have cURL enabled to work.**

**Features**:

* Supports User timelines, Search timelines and Lists
* Use either a template tag or a shortcode to render feeds
* Developers can utilise the plugin system to fully customise the plugin's functionality
* A default stylesheet is included that you can use either for display or for study when creating your own
* Sensitive OAuth and Consumer data is masked within the WordPress admin to prevent unauthorised access to your app data
* All feeds are cached on first render to reduce subsequent load times, along with the option to choose who many hours the cache lasts
* Embed multiple Twitter timelines on one page
* Nice and simple settings page, which can also be hidden
* Perfect for theme developers wanting a truly white label solution



Before this plugin can be used the end user will need to offer it Consumer and OAuth keys that can be obtained by creating an application at the Twitter developers site. Further information on this can be found under the "Installation" tab.

== Installation ==

**Getting Started**

Before this plugin can be used the end user will need to offer it Consumer and OAuth keys that are used to authenticate your communication with Twitter. To obtain these:

1. Visit the [create application page](https://dev.twitter.com/apps/new) on the Twitter developers site. You may be required to sign in, your usual Twitter.com login credentials will work here.
2. Fill in the necessary details and click the "Create your Twitter application" button at the bottom. Don't worry about being creative here, the details you put in won't be public (unless you make them public, that is).
3. If all goes well you'll be taken to the "Details" tab of the new app. Scroll down and look for the "Create my access token" button near the bottom of the page and click on it.
4. Finally, click on the "OAuth Tool" tab. This page holds the Consumer Key, Consumer Secret, Access Token, and Access Token Secret necessary for this plugin to function. Copy them over into your settings.

**Rendering the feed**

You can use either the:

* `<?php db_twitter_feed() ?>` template tag, which takes an associative array as its only parameter; or the
* `[db_twitter_feed]` shortcode

Both accept the same arguments/attributes which are all listed and explained below. All arguments/attributes are optional.

**Options set via tempate tag or shortcode take highest priority. If an option is not set in the tag/shortcode this plugin will then check to see if the option is set in the WordPress admin. If no options have been set the plugin will render with the defaults, listed below**

**feed_type (string)**; `user_timeline`, `search`, `list` *default*: `user_timeline`
> The type of feed that is to be rendered. `user_timeline` produces a feed based on what is set for the `user` option, and `search` produces a feed based on what is set for the `search_term` option, `list` produces a feed based on what is set for the `list` option.

**user (string)**; *default*: twitter
> Any valid Twitter username.

**search_term (string)**; *default*: #twitter
> The term you wish to search Twitter for, with or without a hashtag.

**list (string)**; *default*: twitter-ir/twitter
> The slug of a list followed by the username of the owner, separated by a "/".

**count (int)**; *default*: 10
> The number of tweets you want displayed. The maximum Twitter allows per request is at 200. Page speed can be substantially affected when loading multiple feeds on one page.

**exclude_replies (string)**: `yes` or `no`; *default*: `no`
> The option of whether or not to keep replies out of the feed displayed. Go with `no` to keep replies in, `yes` to take them out.

**exclude_retweets (string)**: `yes` or `no`; *default*: `no`
> The option of whether or not to keep retweets out of the feed displayed. Go with `no` to keep retweets in, `yes` to take them out.

**relative_times (string)**: `yes` or `no`; *default*: `yes`
> The option of whether or not to display times as relative to when they were posted or as absolute times. As an example, a relative time would be "10 mins ago", while an absolute time would be "17:38, Tue". An absolute time is useful where feeds are being cached where relative times can quickly become inaccurate.

**show_images (string)**: `yes` or `no`; *default*: `no`
> The option of whether or not to display embedded images within the rendered feed. The caching of images is left to the browser to handle.

**https (string)**: `yes` or `no`; *default*: `no`
> The option of whether or not to load media from Twitter over their secure connection.

**default_styling (string)**: `yes` or `no`; *default*: `no`
> The option of whether or not to load the default stylesheet bundled with this plugin. Go with `yes` to load it, `no` to skip loading it. Bear in mind that once the stylesheet is loaded it is loaded to the page so all feeds on the page will be affected by it. Hence, when rendering multiple feeds you only need to `yes` with one, and leave it out of the others.

**cache_hours (int)**; *default*: 0
> The number of hours you would like the feed cached for. The cache is saved using WordPress' own `set_transient()` function.

**clear_cache (string)**: `yes` or `no`; *default*: `no`
> Clears the cached version of the feed. If a cached version exists this plugin skips looking at the options altogether so this is a must if you're changing any options. If you're using either the template tag or the shortcode *without* passing information (i.e. all settings from settings page), the cache will be cleared each time the "Save Changes" button is clicked on the plugin's settings page.

**consumer_key**,
**consumer_secret**,
**oauth_access_token**,
**oauth_access_token_secret (string)**; *default*: N/A
> See the first part of the "Installation" tab to find out how to get these. They are necessary for authenticating your communication with Twitter and this plugin unfortunately won't work without them.

== Frequently Asked Questions ==

**How do I contribute to this plugin?**
>  Fork from the [DevBuddy Twitter Feed GitHub repo](https://github.com/EjiOsigwe/devbuddy-twitter-feed), make your changes and make a pull request.

**How do I create my own template tag and shortcode?**
> Simply take a look at the sample files in the `assets` directory of this plugin. The code in these files can be copied over to your theme (included in your functions.php file) and customised to suit your needs. The sample files are well commented and will work right out of the box.

**How can I use this plugin as a native feature of my theme rather than as a plugin?**
> Move the plugin folder from the plugin directory into your theme's directory. Then include the `devbuddy-twitter-feed.php` file from your `functions.php` file. After that, open up the `devbuddy-twitter-feed.php` file and amend the `DBTF_PATH` and `DBTF_URL` constants accordingly. And that's it.

**Can I hide this plugin's option page from the WordPress admin menu?**
> You can. Simply add `if ( is_object( $dbtf ) ) { $dbtf->hide_wp_admin_menu_item(); }` to your theme's functions.php file. If you've moved the plugin folder into your theme and included it, you'll need to ensure that this line comes **after** the include for it to work.

**Why am I not getting any results when I render a search feed?**
> According to Twitter, using the API to perform a search has a recency restriction of one week. This means that if your search term hasn't been tweeted in the past week Twitter will return no results, thus resulting in an empty feed.


== Changelog ==

= 4.0.0 =
* Feeds can now be Twitter Lists! Simply add `'feed_type' => 'list'` and `'list' => 'list-slug/ownerUsername'` to your template tag or `feed_type="list"` and `list="list-slug/ownerUsername"` to your shortcode to render a list.
* The plugin has had Internationalization implemented, no translations are currently bundled with the plugin
* The option to exclude retweets is now available. Simply add `'exclude_retweets' => 'yes'` to your template tag array or `exclude_retweets="yes"` to your shortcode
* The feed now attempts to honour the number of tweets requested by the user where tweet exclusions have been established
* Times on tweets can now be set to be absolute ("17.15") rather than relative ("5 mins ago"). Useful where you don't want cached feeds to appear inaccurate.
* General housekeeping and minor code refinement.

= 3.2.0 =
* Feeds now have the facility to display embedded image media along with the tweet. Simply update the option on the settings page, or add `show_images="yes"` to your shortcode, or add `'show_images' => 'yes'` to the options array of your template tag
* Feeds have the option of loading media content over HTTPS. Simply update the option on the settings page, or add `https="yes"` to your shortcode, or add `'https' => 'yes'` to the options array of your template tag
* The bundled stylesheet has been modified to improve UX on touchscreen devices
* General housekeeping and minor improvements.

= 3.1.3 =

Bug fix: A lack of a feed term cache in in website cache no longer prompts error message

= 3.1.2 =

Refined error and debugging functionality, as well as silencing error_log() notices should one's server not handle it well

= 3.1.1 =
* Amended code that required more recent versions of PHP so that older versions are now supported
* Submissions on the settings page now check for quotes in fields and reject any that are found
* Clear cache notice fixed in settings page
* Some minor code improvements

= 3.1.0 =
* Cache management facility added to settings page
* Setting `cache_hours` to `0` will clear the cache if one exists
* Smilies no longer break the feed
* Plugin now utilises WP's error logging and other custom debugging facilities
* Implemented partial API functionality, further work and documentation to come
* General code improvements

= 3.0.2 =

Bug fix: Unchanged OAuth Access Token should no longer become incorrect when changes are saved via the admin

= 3.0.1 =
**Please note that Twitter has a recency restriction of one week on searches. If a search term hasn't been tweeted in the past week Twitter will return no results.**
* Implemented code amendment that ensures that the plugin provides the user with useful feedback if a search term returns no tweets
* General code housekeeping

= 3.0.0 =

Timelines can now be Searches! Check the settings page to test it out. Or, in your template tag/shortcode, add the `feed_type` option with a value of `search` along with the `search_term` option and your search as its value

= 2.3.2 =

Bug fix: Masked OAuth Access Token data is now properly unmasked upon saving on the settings page, meaning that connection credentials are not erroneous when it comes to communicating with Twitter. NOTE: It's likely you will need to re-enter your OAuth Access Token.

= 2.3.1 =

Bug fix: Rectified errors in the code that caused fatal error

= 2.3.0 =
* Added HTML rendering class to assist with customising the feed's HTML layout
* Updated the sample template tag and shortcode files

= 2.2.2 =

Added template tag and shortcode sample code in the `assets` folder

= 2.2.1 =

Bug fix: Fix to a bug that prevented the feed from using the Tweet count set by the user

= 2.2.0 =

Developers can now utilise the plugin system to create their own template tags and shortcodes. Samples are included in the "assets" directory of this plugin.

= 2.1.0 =
* Default stylesheet has been updated to be responsive, and to match your theme's appearance as much as possible
* Despite the work in the 2.0.2 release, the empty timeline feedback didn't render but it does now
* Minor refactor work on the code to make it less bug and error prone

= 2.0.3 =

Bug Fix: Using the shortcode to render the feed in the WordPress editor places the feed within the content rather than directly above it.

= 2.0.2 =
* Bug fix: The feed now extracts the string versions of IDs rather than the integer versions. This means long IDs are no longer susceptible to being read mathematically, i.e. 372489002391470081 instead of 3.7248900239147E+17.
* The feed now offers friendly feedback should the timeline requested be empty.

= 2.0.1 =

Minor rectifications to code that prevented the default stylesheet from loading

= 2.0.0 =
* Complete overhaul of the plugin's code. Code is now much more modular and refined
* `cache_hours` was added and implemented as a feed configuration option
* Addition of masking/unmasking facilities utilised within the admin to hide sensitive OAuth and Consumer Key/Secret data

= 1.0.1 =
Amendment of plugin description and settings page to include important and useful information.

= 1.0.0 =

First release.

== Upgrade Notice ==

= 4.0.0 =

New Features: Feeds can now be Twitter Lists, Internationalisation, retweets can now be excluded and more. NOTE: You may need to save new settings twice before they save properly.

= 3.2.0 =
* Feeds now have the facility to display embedded image media along with the tweet. Simply update the option on the settings page, or add `show_images="yes"` to your shortcode, or add `'show_images' => 'yes'` to the options array of your template tag
* Feeds have the option of loading media content over HTTPS. Simply update the option on the settings page, or add `https="yes"` to your shortcode, or add `'https' => 'yes'` to the options array of your template tag
* The bundled stylesheet has been modified to improve UX on touchscreen devices
* General housekeeping and minor improvements.

= 3.1.3 =

Bug fix: A lack of a feed term cache in in website cache no longer prompts error message

= 3.1.2 =

Refined error and debugging functionality, as well as silencing error_log() notices should one's server not handle it well

= 3.1.1 =
* Amended code that required more recent versions of PHP so that older versions are now supported
* Submissions on the settings page now check for quotes in fields and reject any that are found
* Clear cache notice fixed in settings page
* Some minor code improvements

= 3.1.0 =
* Cache management facility added to settings page
* Setting `cache_hours` to `0` will clear the cache if one exists
* Smilies no longer break the feed
* Plugin now utilises WP's error logging and other custom debugging facilities
* Implemented partial API functionality, further work and documentation to come
* General code improvements

= 3.0.2 =

Bug fix: Unchanged OAuth Access Token should no longer become incorrect when changes are saved via the admin

= 3.0.1 =

Code amendment that ensures that the plugin provides the user with useful feedback if a search term returns no tweets and some other general code maintenance work.

= 3.0.0 =

Plugin now supports searches as timelines. Visit the settings page to change options or the plugin's WordPress page for usage instructions with template tags/shortcodes.

= 2.3.2 =

Masked OAuth Access Token data is now properly unmasked upon saving on the settings page, meaning that connection credentials are not erroneous when it comes to communicating with Twitter. NOTE: It's likely you will need to re-enter your OAuth Access Token.

= 2.3.1 =

Rectified errors in the code that caused fatal error

= 2.3.0 =

Added HTML rendering class to assist with customising the feed's HTML layout, as well as an update of the sample template tag and shortcode files.

= 2.2.2 =

Added template tag and shortcode sample code in the `assets` folder

= 2.2.1 =

Fixes a bug that stops the feed from using the Tweet count set by the user.

= 2.2.0 =

Developers can now utilise the plugin system to create their own template tags and shortcodes. Samples are included in the "assets" directory of this plugin.

= 2.1.0 =

Default stylesheet has been updated to be responsive, and to match your theme's appearance as much as possible. That and some code cleanup.

= 2.0.3 =

Fixes a bug that meant the feed would be rendered before the content, rather than within, if the shortcode was used in the WordPress editor.

= 2.0.2 =

Fixes a bug that led to IDs being read mathematically. As some of the links rendered by the feed use these IDs, those links may have been faulty as a result.

= 2.0.1 =

Minor rectifications to code that prevented the default stylesheet from loading. Update to be able to take advantage of the bundled stylesheet.

= 2.0.0 =

The plugin code structure has undergone considerable changes but this won't be noticeable to the user. Additionally, you can now change the number of hours that the feed is cached for and sensitive OAuth and Consumer Key/Secret data is now masked in the admin.

= 1.0.1 =

Amendment of plugin description and settings page to include important and useful information. Not an urgent upgrade.

= 1.0.0 =

First release.