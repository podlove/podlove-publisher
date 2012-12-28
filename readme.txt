=== Podlove Podcast Publisher ===
Contributors: eteubert
Donate link: http://flattr.com/thing/728463/Podlove-Podcasting-Plugin-for-WordPress
Tags: podcast, publishing
Requires at least: 3.0
Tested up to: 3.5
Stable tag: 1.0

The one and only next generation podcast publishing system. Seriously. It's magical and sparkles a lot.

== Description ==

The Podlove Podcast Publisher is a workflow-oriented solution for serious podcasters that want to save time and get full control over their Podcast publishing process, their feeds and the integrity of their publication.

The Publisher makes it easy to create highly expressive, efficient and super compatible podcast feeds with fine grained control over client behaviour (e.g. GUID control to replace faulty episodes and fore clients to reload) supporting all important meta data.

The Publisher also makes multi-format publishing - embracing all modern and legacy audio and video codecs - a snap. By adopting simple file name conventions, the plugin allows the podcaster to provide individual feeds for certain use cases or audiences without adding work for the podcaster during the publishing process.

The Publisher also comes with integrated with the Podlove  Web Player plugin (which you do not need to install separately) and fully support its advanced options including multiple audio (MP4 AAC, MP3, Vorbis, Opus) and video (MP4 H.264, WebM, Theora) format support for web browsers. This Web Player is fully HTML5 compatible (but provides Flash fallback for ancient environments) and is ready for all touch based clients too.

The Publisher also makes it easy to publish chapter information in the player to make access to structured episodes even easier. Full support for linking directly to any part of your podcast on the web with instant playback included.

To round it all up, a flexible template system enables you to published Podcasts in a defined fashion and change the style at any time without having to touch your individual postings later on.

And this is just the beginning. We have a rich roadmap that will bring even more interesting features: integration with helpful services, much improved timeline metadata support (show notes) and much more.

Development of the plugin is an open process. The current version is available on github:

http://github.com/eteubert/podlove

Feel free to contribute and to fix errors or send improvements via github.



== Frequently Asked Questions ==

### Why do my episodes look the same as my normal posts/missing some information?

The Podlove Podcast Publisher (PPP) uses "custom posts" for its episodes. Some themes treat normal posts and custom posts differently or just forgot to take into account that custom posts show up slightly different in the HTML.

Get in contact with the theme developer and ask if it is ready for custom posts. It is usually not very complicated to make a theme work with custom posts out of the box. PPP does work together will all templates that come with WordPress.


### My episodes do not show up on the home page. What's wrong?

Episodes are kep separate from blog posts but you can choose if you want episodes to be mixed with blog posts on the home page. To do this, check the "Display episodes on front page together with blog posts" setting in the Expert Settings panel.


### Episodes do not show up with the configured permalink URL. What's wrong?

Episodes are custom posts and are dealt with differently by WordPress. They show up under a common URL prefix. You can define the result URL with the "URL segment prefix for podcast episode posts" setting in the Expert Settings panel. This is set to "episode" by default resulting in an episode to show up under "/episode/<episode-slug>".


### Where do I put the URL of my media files?

You don't. The plugin assembles the media file URL by combining various components that you have configured in the Podlove settings. All media files have to reside under a base URL that you specify in the "Podcast Settings" pane. This basically defines which directory all files have to be uploaded to.

The exact media file name is made up of a) the Episode Media File Flug you set in the episode's meta data b) the suffix of the episode asset (as configured in the "Episode Asset" settings page) and c) the extension of the file type of the Episode Asset (as configured in the "File Types" settings page).


### Where is the Web Player / Download list?

Right now, these have to be inserted manually via so called shortcodes. They are [podlove-web-player] and [podlove-episode-downloads].

There are compatibility issues with the "Jetpack" plugin. If you use it, you might need to turn it off.

You can use the plugin's templates to make sure you have the proper shortcodes in every episode.



### A feed link directs me to a blog page. What's wrong?

This is an issue that sometimes arises out of the weirdness that is WordPress. Your settings might be totally okay but there  is some kind of amnesia going on in the WordPress core.

In order to free WordPress from its amnesia go to Settings > Permalinks, hit Save and try again.


### How do I add Flattr integration to my episodes?

If you haven't already, get the official Flattr plugin here:

   http://wordpress.org/extend/plugins/flattr/

Find the setting Flattr > Advanced Settings > Flattrable content > Post Types and check "podcast". Save changes. There is no step 3 ;)


== Changelog ==

= 1.3.16-alpha =
* Enhancement: Solve feed url issues:
** ensure validity on save
** support non-pretty url format
* Enhancement: un-default some modules: episode assistant, twitter card summary
* Enhancement: fix asset & feed setting redirect issue
* Enhancement: add caption file types
* Enhancement: new icons!
* Enhancement: allow underscores in slugs
* Bugfix: fix issue with multiple backslash-escapings

= 1.3.15-alpha =
* Hotfix: fix 404 issue concerning episode prefixes and posts

= 1.3.14-alpha =
* Feature: ajaxy asset revalidation in dashboard
* Feature: duration support for web player
* Feature: add option to provide web players with opus format
* Enhancement: slightly improved web player settings pane
* Enhancement: deprecate [podlove-template title=""] in favor of [podlove-template id=""] for clarity
* Enhancement: move category support for episodes into a module
* Enhancement: force feed & episode slugs into url conformity
* update plugin description and add a FAQ section

= 1.3.13-alpha =
* Bugfix: Podcast model works with `switch_to_blog` now

= 1.3.12-alpha =
* Enhancement: don't embed cover image fallback in feed as episode image when there is no episode image
* Feature: add action link for assets to enable it for all existing episodes. useful when adding a new asset for an existing podcast

= 1.3.11-alpha =
* Enhancement: Image input fields try to show pasted image immediately
* Enhancement: remove unused "post episode to show" setting
* Bugfix: fix asset preview glitch when changing the episode slug
* Bugfix: fix GUID upgrade migration

= 1.3.10-alpha =
* Hotfix: too much escaping when `get_magic_quotes` is on

= 1.3.9-alpha =
* Enhancement: rectify feed generator title
* Bugfix: add missing sql escaping

= 1.3.8-alpha =
* Bugfix: fix episode image fallback to podcast image

= 1.3.7-alpha =
* Enhancement: In feed settings, URL preview updates live now
* Enhancement: "Add New" button in blank list table views
* Enhancement: display `<language>` tag in RSS channel and correct xml:lang in ATOM
* Enhancement: forbid asset deletion when used in feed or web player
* Bugfix: Templates list view highlights template preview correctly now for more than one entry
* Bugfix: remove duplicate rel="self" entry from RSS feeds
* Bugfix: correct escaping for all input fields
* Bugfix: fix 404s when using an empty episode url prefix

= 1.3.6-alpha =
* Bugfix: Minor WordPress 3.5 compatibility issue
* Bugfix: Use correct shortcodes in default template
* Enhancement: Add support for `[podlove-episode field="title"]`
* Enhancement: Improve auto-updating of media files. It will now work correctly without the need to save the post after changing the media file slug. It updates every time you change the slug and lose focus of the input field.

= 1.3.5-alpha =
* Bugfix: pages and menu items don't appear unexpectedly in main loop any more
* Bugfix: when using the WordPress importer, don't create new GUIDs
* Enhancement: rename GUID meta so it doesn't appear as custom field

= 1.3.4-alpha =
* Hotfix: fix asset creation issue

= 1.3.3-alpha =
* Enhancement: Use episode image fallback to podcast image in webplayer.

= 1.3.2-alpha =
* Feature: When using manual mp4chaps style chapter marks, the Publisher generates "Podlove Simple Chapters" for the feed automatically. Includes link support using chevrons (example: `00:00:00 Intro <http://podlove.org>`).

= 1.3.1-alpha =
* update web player to 1.2.1

= 1.3.0-alpha =
* Feature: [Podlove Deep Linking](http://podlove.org/deep-link/) support
* Feature: support for new web player
* Bugfix: enable tag and category search results for all post types
* Bugfix: Feed item limit setting works now
* Bugfix: avoid rare curl warning
* Bugfix: improve feed validity
* Enhancement: remove unused feed setting `show description`
* Enhancement: Podlove feeds don't override /feed/* WordPress feeds any more
* Enhancement: Rename plugin to "Podlove Podcast Publisher"
* Enhancement: Move asset assignments from podcast settings to asset settings

= 1.2.24-alpha =
* Bugfix: don't show milliseconds in feed so feedvalidator.org stops complaining

= 1.2.22/23-alpha =
* Fix deployment bug, delete unused files from SVN

= 1.2.21-alpha =
* Bugfix: check for asset relations (not just media file relations) when trying to delete assets
* Bugfix: asset form can handle file types using brackets now
* Bugfix: There was an undocumented way to just show episodes on the front page. However, this made using static pages as front page unusable. So for now, this functionality has been deactivated. The expert option to display both episodes and articles on the front page is not affected and will continue to work.
* Enhancement: duration is now normalized and can be printed full (HH:MM:SS.mmm) or HH:MM:SS using `[podlove-episode field="duration" format="full/HH:MM:SS"]`
* Enhancement: curl requests set user agent

= 1.2.20-alpha =
* Bugfix: forbid deletion of episode assets referenced by existing media files
* Bugfix: fix episode asset type selector

= 1.2.19-alpha =
* Feature: add episode image shortcode `[podlove-episode field="image"]`
* Bugfix: fix some bugs
* Enhancement: when creating new form entries, the user is now redirected to the index page rather than the edit form

= 1.2.18-alpha =
* Feature: 4 new podcast fields: publisher_name, publisher_url, license_name, license_url
* Feature: Shortcode `[podlove-podcast]` to access podcast data. See [Shortcode Documentation](https://github.com/eteubert/podlove/wiki/Shortcodes) for more details.
* Feature: Shortcode `[podlove-episode]` to access episode data. *all previous episode accessors are deprecated!* See [Shortcode Documentation](https://github.com/eteubert/podlove/wiki/Shortcodes) for more details.
* Feature: Add support for tags and categories in episodes.
* Feature: Chapter File (txt and psc) as episode asset
* Feature: Feed redirects can be a) turned off and b) permanent c) temporary
* Feature: Module for Twitter Card support
* Enhancement: Minor template editor enhancements and updated default template.
* Enhancement: Enable revisions for episodes.
* Enhancement: RSS/Atom cleanup. Less WordPress, more Podlove.
* Enhancement: UI improvements in episode asset forms
* Enhancement: Menu reorganisation. Moved important stuff up, expert stuff down. Separate site for modules.

= 1.2.17-alpha =
* Nothing. Just some WordPress-Plugin-Directory-Thingamajig-Version-Foobar.

= 1.2.16-alpha =
* Feature: Episode templates. Go to `Podlove > Templates` to find out more. See [Shortcode Documentation](https://github.com/eteubert/podlove/wiki/Shortcodes) for more details.
* Feature: Custom GUID for episodes. A GUID in the form of "podlove-`time`-`hash`" is generated for each new episode. It removes the ambiguity of the permalink-ish looking WordPress GUID. Bonus: If you need podcatchers to redownload all media files (maybe you detected a glitch in your files and fixed it), you are now able to change the GUID to achieve that.
* Enhancement: remove episode excerpt support in favor of episode summary
* Bugfix: Short Episode Routing compatibility

= 1.2.15-alpha =
* Bugfix: remove all Show model references for now
* Enhancement: proper summary/description feed elements

= 1.2.14-alpha =
* Enhancement: rename "media locations" to "episode assets" for clarity
* Enhancement: rename "podlove formats" to "file types" for clarity
* Enhancement: start to rework validation section
* Enhancement: check for episode files when slug changes

= 1.2.13-alpha =
* Enhancement: use episode summary as excerpt if available
* Bugfix: episode assistant file slugs respect mnemonic case
* Bugfix: solve 404 issue with pages

= 1.2.12-alpha =
* Bugfix: Minor JavaScript glitch

= 1.2.11-alpha =
* New Module: Contributors Taxonomy — display with shortcode `[podlove-contributors]` (go to `Podlove > Settings` to activate the module)

= 1.2.10-alpha =
* Feature: Add Shortcodes to display episode data: `[podlove-episode-subtitle] [podlove-episode-summary] [podlove-episode-slug] [podlove-episode-duration] [podlove-episode-chapters]`
* Feature: Add Opus File Format ([see Auphonic blog for more info](http://auphonic.com/blog/2012/09/26/opus-revolutionary-open-audio-codec-podcasts-and-internet-audio/))
* Feature: Show red warning in dashboard if one of the following podlove settings is missing: `title`, `mnemonic`, `base url`
* Enhancement: Remove pagination from formats settings page

== Upgrade Notice ==

= 1.2.0-alpha =
Before you update, delete all shows but one to ensure your important data stays. Watch out: Your feed URLs will change!