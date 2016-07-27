=== Custom Twitter Feeds ===
Author: Smash Balloon
Contributors: smashballoon, craig-at-smash-balloon
Support Website: http://smashballoon/custom-twitter-feeds/
Tags: Twitter, Twitter feed, Custom Twitter Feed, Twitter feeds, Custom Twitter Feeds, Tweets, Custom Tweets, Tweets feed, Twitter widget, Custom Twitter widget, Twitter plugin, Twitter API, Twitter tweets
Requires at least: 3.0
Tested up to: 4.5.3
Stable tag: 1.1.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Custom Twitter Feeds allows you to display completely customizable Twitter feeds of your user timeline, home timeline, or hashtag on your website.

== Description ==
Display **completely customizable**, **responsive** and **search engine crawlable** versions of your Twitter feed on your website. Completely match the look and feel of the site with tons of customization options!

* **Completely Customizable** - by default inherits your theme's styles
* Feed content is **crawlable by search engines** adding SEO value to your site
* **Completely responsive and mobile optimized** - works on any screen size
* Display tweets from any user, your own account and those you follow, or from a specific hashtag
* Display multiple feeds from different Twitter users on multiple pages or widgets
* Post caching means that your feed loads lightning fast and minimizes Twitter API requests
* **Infinitely load more** of your Tweets with the 'Load More' button
* Built-in easy to use "Custom Twitter Feeds" Widget
* Fully internationalized and translatable into any language
* Display a beautiful header at the top of your feed
* Enter your own custom CSS for even deeper customization

For simple step-by-step directions on how to set up the Custom Twitter Feeds plugin please refer to our [setup guide](http://smashballoon.com/custom-twitter-feeds/free/ 'Custom Twitter Feeds setup guide').

= Feedback or Support =
We're dedicated to providing the most customizable, robust and well supported Twitter feed plugin in the world, so if you have an issue or any feedback on how to improve the plugin then please [let us know](https://smashballoon.com/custom-twitter-feeds/support/ 'Twitter Feed Support').

If you like the plugin then please consider leaving a review, as it really helps to support the plugin. If you have an issue then please allow us to help you fix it before leaving a review. Just [let us know](https://smashballoon.com/custom-twitter-feeds/support/ 'Twitter Feed Support') what the problem is and we'll get back to you right away.

We recently released a [Pro version](http://smashballoon.com/custom-twitter-feeds/ 'Custom Twitter Feeds Pro') which includes some awesome additional features:

* Display Tweets from **multiple users or hashtags in the same feed**
* Display **photos**, **videos**, and **gifs** and view them in a **popup lightbox** directly on your site
* Multi-column **Masonry layout** [demo](http://smashballoon.com/custom-twitter-feeds/demo/masonry 'Custom Twitter Feeds Pro Masonry Demo')
* Allow **filtering** of user timelines include/exclude any/all keywords/hashtags
* Fully functional **search endpoint**
* Display Tweets you're mentioned in
* Tweet-specific **moderation system**
* **Twitter cards** displayed with links which support them
* Include **Tweet replies** (in reply to tweets)

Try the Pro version [demo here](http://smashballoon.com/custom-twitter-feeds/demo 'Custom Twitter Feeds Pro Demo')

== Installation ==
1. Install the Custom Twitter Feeds plugin either via the WordPress plugin directory, or by uploading the files to your web server (in the /wp-content/plugins/ directory).
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to the 'Twitter Feed' settings page to configure your feed.
4. Use the shortcode [custom-twitter-feeds] in your page, post or widget to display your feed.
5. You can display multiple feeds with different configurations by specifying the necessary parameters directly in the shortcode: [custom-twitter-feeds hashtag=#smashballoon].

For simple step-by-step directions on how to set up Custom Twitter Feeds plugin please refer to our [setup guide](http://smashballoon.com/custom-twitter-feeds/free/ 'Custom Twitter Feeds setup guide').

= Setting up the Free Custom Twitter Feeds WordPress Plugin =

The Custom Twitter Feeds plugin is brand new and so we're currently working on improving our documentation for it. If you have an issue or question please submit a support ticket and we'll get back to you as soon as we can.

1) Once you've installed the plugin click on the Twitter Feed item in your WordPress menu

2) Click on the large blue Twitter login button to get your Twitter Access Token and Twitter Secret. Note; if you have your own Twitter Developer App set up then you can enter your Twitter information manually by enabling the checkbox below the Twitter login button.

3) Authorize the plugin to read your Tweets.
Note; the plugin does not obtain permission to edit or write to your Twitter account, only to read your Twitter content.

4) Twitter sends back your Twitter Access Token and Twitter Secret which are then automatically saved by the Custom Twitter Feeds plugin. This information is required in order to connect to the Twitter API.

5) Enter a Twitter screenname to display Tweets from. Alternatively, choose to display Tweets from your Twitter home timeline or a Twitter hashtag.

6) Navigate to the Customize and Style pages to customize your Twitter feed.

7) Once you've customized your Twitter feed, click on the "Display Your Feed" tab for directions on how to display your Twitter feed (or multiple feeds).

8) Copy the [custom-twitter-feeds] shortcode and paste it into any page, post or widget where you want the Twitter feed to appear.

9) You can paste the [custom-twitter-feeds] shortcode directly into your page editor.

10) You can use the default WordPress 'Text' widget to display your Twitter Feed in a sidebar or other widget area.
 
11) View your website to see your Twitter feed(s) in all their glory!

== Frequently Asked Questions ==

= Can I display multiple Twitter feeds on my site or on the same page? =

Yep. You can display multiple Twitter feeds by using our built-in shortcode options, for example: `[custom-twitter-feeds screenname="smashballoon" num=3]`.

= How do I embed the Twitter Feed directly into a WordPress page template? =

You can embed your Twitter feed directly into a template file by using the WordPress [do_shortcode](http://codex.wordpress.org/Function_Reference/do_shortcode) function: `<?php echo do_shortcode('[custom-twitter-feeds]'); ?>`.

== Other Notes ==

= Twitter API Error Message Reference =

If you receive an error message when trying to display your Twitter Feed then you can use the error reference below to diagnose the issue and find the relevant solution.

**Error:**
Could not authenticate you

**Causes:**
- You may be using Twitter access tokens that are not valid - See #1 below
- You checked the box to enter your own Twitter app information, but one or more of the fields are incorrect - See #2 below

**Error:**
Invalid or expired token

**Causes:**
- You may not have entered your Twitter access tokens or they are not valid - See #1 below

**Error:**
Unable to load tweets (with no other explanation)

**Causes:**
- You may need to raise the number of tweets to retrieve and display in your Twitter feed - See #3 below

**Error:**
Sorry, that page does not exist

**Causes:**
- There may be a typo in the Twitter screen name or hashtag you are attempting to use

**Solutions**

**#1 - Your Twitter access tokens might not be valid**

The easiest way to verify this is by going back to the Settings page for the Custom Twitter Feeds plugin and clicking the big blue button on the "Configure" tab to get new Twitter access tokens. If you haven't set up your own Twitter App, the only way to use the Custom Twitter Feeds plugin is to click the big blue button to get a Twitter access token and Twitter access token secret that is compatible with the default Twitter client used by Smash Balloon.

**#2 - You have checked the box to use your own Twitter app information but one or more of the fields are incorrect**

All four fields, consumer token, consumer secret, access token, and access token secret, need to come from the Twitter app that you set up on Twitter.com. Try returning to your personal Twitter app management page https://apps.twitter.com/ and confirming that all four fields, consumer token, consumer secret, access token, and access token secret, and entered correctly in the corresponding fields on the plugin's "Configure" tab.

**#3 - You may need to raise the number of tweets to retrieve**

Navigate to the type of Twitter feed you are trying to display on twitter.com. For example, if you are displaying a feed from the screenname "smashballoon", go to https://twitter.com/smashballoon. Make sure there are tweets visible. Then click on the link "Tweets & replies". If there are a more recent replies than original tweets, there may be too many tweets being filtered out to display any. Replies are removed by default. You can raise the number of tweets to retrieve initially by going to the Custom Twitter Feed Settings page, "Customize" tab, and navigating to the "Advanced" area. Then raise the "Tweet Multiplier" and test to see if your Twitter feed now displays tweets.

If you're still having trouble displaying your Tweets after trying the common issues above then please [contact support](https://smashballoon.com/custom-twitter-feeds/support/) for assistance.

== Screenshots ==

1. Default Custom Twitter Feeds plugin styling
2. Custom Twitter Feeds plugin Settings pages

== Changelog ==
= 1.1.2 =
* New: Launched a [Pro version](http://smashballoon.com/custom-twitter-feeds/ 'Custom Twitter Feeds Pro') which includes some awesome additional features!
* Fix: Minor bug fixes

= 1.1.1 =
* Fix: Added SSL support for avatar images so https version is used
* Fix: Fixed an issue with the "hours" custom text string displaying the "minutes" text instead

= 1.1 =
* New: Added a setting to translate the "Retweeted" text
* Tweak: If there aren't enough Tweets to populate the feed them Ajax in more automatically
* Fix: Custom JavaScript is now rerun every time the Load More button is used
* Fix: CSS display tweaks

= 1.0.1 =
* Bug fixes

= 1.0 =
* Launched the Custom Twitter Feeds plugin!