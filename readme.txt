=== Simple Twitter Connect ===
Contributors: Otto42
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=otto%40ottodestruct%2ecom
Tags: twitter, connect, simple, otto, otto42, javascript
Requires at least: 3.0
Tested up to: 3.1
Stable tag: 0.14

== Description ==

Simple Twitter Connect is a series of plugins that let you add any sort of Twitter functionality you like to a WordPress blog. This lets you have an integrated site without a lot of coding, and still letting you customize it exactly the way you'd like.

First, you activate and set up the base plugin, which makes your site have basic Twitter functionality. Then, each of the add-on plugins will let you add small pieces of specific Twitter-related functionality, one by one.

Bonus: Unlike other Twitter plugins for WordPress, this one helps you create your own Twitter application and identity, so your tweets from here show up as being from Your Blog, not from some plugin system. You'll never see "posted by Simple Twitter Connect" in your tweet stream, you'll see "posted by Your Blog Name". Great way to drive traffic back to your own site and to see your own Twitter userbase.

Requires WordPress 2.9 and PHP 5. 

**Current add-ons**

* Login using Twitter
* Comment using Twitter credentials
* Users can auto-tweet their comments
* Tweet button (official one from twitter)
* Tweetmeme button
* Auto-tweet new posts to an account
* Manual Tweetbox after Publish
* Full @anywhere support
* Auto-link all twitter names on the site (with optional hovercards)
* Dashboard Twitter Widget

**Coming soon** 

* More direct retweet button (instead of using Tweetmeme)
* (Got more ideas? Tell me!)

If you have suggestions for a new add-on, feel free to email me at otto@ottodestruct.com .

Want regular updates? Become a fan of my sites on Facebook!
http://www.facebook.com/apps/application.php?id=116002660893
http://www.facebook.com/ottopress

Or follow my sites on Twitter!
http://twitter.com/ottodestruct

== Installation ==

1. Upload the files to the `/wp-content/plugins/simple-twitter-connect/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= Whoa, what's with all these plugins? =

The principle behind this plugin is to enable small pieces of Twitter functionality, one at a time.

Thus, you have the base plugin, which does nothing except to enable your site for Twitter OAuth in general. It's required by all the other plugins.

Then you have individual plugins, one for each piece of functionality. One for enabling comments, one for adding Login, etc. These are all smaller and simpler, for the most part, because they don't have to add all the Twitter connections stuff that the base plugin adds.

= The comments plugin isn't working! =

You have to modify your theme to use the comments plugin.

In your comments.php file (or wherever your comments form is), you need to do the following.

1. Find the three inputs for the author, email, and url information. They need to have those ID's on the inputs (author, email, url). This is what the default theme and all standardized themes use, but some may be slightly different. You'll have to alter them to have these ID's in that case.

2. Just before the first input, add this code:
[div id="comment-user-details"]
[?php do_action('alt_comment_login'); ?]

(Replace the []'s with normal html greater/less than signs).

3. Just below the last input (not the comment text area, just the name/email/url inputs, add this:
[/div]

That will add the necessary pieces to allow the script to work.

Hopefully, a future version of WordPress will make this simpler.

= Twitter Avatars look wrong. =

Twitter avatars use slightly different code than other avatars. They should style the same, but not all themes will have this working properly, due to various theme designs and such. 

However, it is almost always possible to correct this with some simple CSS adjustments. For this reason, they are given a special "twitter-avatar" class, for you to use to style them as you need. Just use .twitter-avatar in your CSS and add styling rules to correct those specific avatars.

= Why can't I email people who comment using Twitter? =

Twitter offers no way to get a valid email address for a user. So the comments plugin uses a fake address of the twitter's username @fake.twitter.com. The "fake" is the giveaway here.

= When users connect using Twitter on the Comments section, there's a delay before their info appears. =

Yes. In order to make the plugin more compatible with caching plugins like WP-Super-Cache, the data for a Twitter connect account is retreived from the server using an AJAX request. This means that there will be a slight delay while the data is retrieved, but the page has already been loaded and displayed. Most of the time this will not be noticable.

= Why does the settings screen warn me that I don't have a URL shortener plugin? =

Simple Twitter Connect does not implement a URL shortening service in favor of letting other plugins implement one for it. WordPress 3.0 includes a new shortlink method for plugins to implement this properly.

A shortener plugin should implement the "get_shortlink" filter for it to be detected. WordPress 3.0 will be required for this to work.

The WordPress.com stats plugin implements this, and it provides shortlinks to "wp.me" URLs. If you wish to use another shortener plugin, tell that plugin's author to implement this same standard, and the plugin will automatically be detected and used by Simple Twitter Connect.

== Screenshots ==

1. Login screen showing both Simple Facebook Connect and Simple Twitter Connect login buttons.
2. Twitter Connect on My Profile screen.
3. Simple Facebook Connect and Simple Twitter Connect button on comments form.
4. Login info (before styling) after using Twitter connect button on comments form.

== Upgrade Notice ==

= 0.15 =
* Minor fixes for minor things

= 0.14 =
* Several tweet button fixes. Normally I wouldn't push another update so soon, but this one seemed necessary.

= 0.13 =
* Minor bug fixes
* Custom Post Types support for auto-publish.

= 0.12 = 
* Bug fix release. Logins may not have worked with 0.11 for all people.
* New option in settings screen to choose what your Twitter sign in button looks like, as apologies for the need for the bug fix release. :)

= 0.11 =
* Added official Twitter Tweet button. This is a replacement for the TweetMeme button. Full support for styling and related users.

== Changelog ==

= 0.15 =
* Remove default source for tweet button.
* Logout fix.
* Fix &nbsp; in tweets for some cases
* Add twitter publisher filters
* Fix plugin URI

= 0.14 =
* Tweet button doesn't show plus signs instead of spaces any more.
* Minor documentation and regexp change (colons not the same in the data-related anymore).
* Fix default styling of tweet button.

= 0.13 =
* Fix tweet button issues. Tweet links in feeds and such work better now. Colons don't disappear from tweets.
* Added contextual help screens.
* Change links in help to point to proper place to set up apps.
* Publish meta box shows up on Pages now.
* Minor issues with some shortlink plugins corrected.
* Custom Post Types support in Publish. Auto-publish *should* now work on custom post types. No guarantees.

= 0.12 =
* Modified tweet button to use custom shortlinks (if any). This can solve the copy/paste problem for manual RT's. Also makes searching work better.
* Add select box for choosing your style of Twitter button.
* Bug fix. Some users may have been seeing login errors or blank screens with 0.11. This should correct that.

= 0.11 =
* Moved session_start into the init method. Somehow this is more compatible, I guess?
* Added official Twitter Tweet button. This is a replacement for the TweetMeme button which is still available if you prefer it.

= 0.10 =
* Added followers widget. Look inside the widget code for example CSS to add to your theme.
* Added shortcode to follow widget. Use [tweetfollow user="username"] to add a follow button into posts. Alternatively, use stc_follow_button($user); in your theme to put a follow button anywhere.
* Added support for predefined KEY/SECRETs. Just define STC_CONSUMER_KEY and/or STC_CONSUMER_SECRET in your wp-config file.
* Added Twitter Dashboard widget, thanks to John Bloch.
* Modified Publish to size Tweet box dynamically.
* Fixed autopublish case that would result in a missing shortlink

= 0.9 =
* Added hovercard support to Linkify plugin.
* Added manual tweetbox to edit post page (using @anywhere tweetbox code).

= 0.8 =
* Follow button widget
* Fix for broken Twitter avatars due to SPIURL and Twitter API changes.

= 0.7 = 
* Support for WordPress 3.0. Works with Multi-Site, but it must be configured separately for each individual site, it will not work sitewide. Sitewide coming soon for a small percentage of configurations (it's only possible if all sites are on the same domain, not in multiple domains).
* Publish only publishes posts and pages now, to prevent new custom post types from mucking about and causing weird Twitter posts.
* Add Twitter announcement feed to settings page. If I find a feed that informs of outages, I'll use that one instead.
* @anywhere support! The @anywhere javascript is automatically added to the site when the base plugin is set up.
* Linkify plugin added to demonstrate how @anywhere script works.

= 0.6.1 = 
* 0.6 had a fatal error in it, do not use that version.

= 0.6 = 
* Added proper uninstaller.
* Added shortlink support for WordPress 3.0 shortlink API.

= 0.5 =
* Comments plugin is a bit smarter now. Settings page fixed, and the "Send to Twitter" can be disabled. Disabling also prevents the google ajax libraries from loading (they are used to get location of the user for location info in tweets).
* Tweetmeme script now using HTML comments, so as not to show up tweetmeme settings in strange places (feeds, FB Share, etc). 
* Automatic Tweeting on Post Publishing added. Supports posting to alternate Twitter accounts. Manual publishing coming soon.

= 0.4 =
* Warning about shortlinks.
* Login now displays an error message when somebody attempts to login as a user that isn't recognized yet.
* Added Tweetmeme button plugin (STC is compatible with the already existing TweetMeme plugins also, but for completeness, this needed to be in STC as well).

= 0.3.1 =
* Fixed error in 0.3 that caused comments to not load on some server configurations.

= 0.3 =
* Fix logout bug and comments bug.
* Remove person extensions. They don't work right anyway. Revisit later.
* Add urlencoding to fix login for some odd server configurations.

= 0.2 =
* Login security issue fixed.
* Logout link added to comments.
* Minor internal design changes.

= 0.1 =

* Initial release
