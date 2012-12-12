=== Podlove Podcast Publisher ===
Contributors: eteubert
Donate link: http://flattr.com/thing/728463/Podlove-Podcasting-Plugin-for-WordPress
Tags: podcast, publishing
Requires at least: 3.0
Tested up to: 3.5
Stable tag: 1.0

Podcast plugin. Still in early stages of development. Don't use for production unless you know what you're doing.

== Description ==

"The Mac OS X, 10.0, of podcasting. The Podcasting Plugin for the next decade." — Tim Pritlove & map

Podlove Podcasting Plugin for WordPress is a full-features podcast publishing system — well, it will be at some point. It is still in early development, so please handle with care. Feel free to use it in the real world but don't be surprised if something breaks until we reach a stable release. We already try to not break stuff with updates but there is no guarantee.

Development of the plugin is an open process. The current version is available on github:

http://github.com/eteubert/podlove

Feel free to contribute and to fix errors or send improvements via github.

== Changelog ==

= 1.3.7-alpha =
* Enhancement: In feed settings, URL preview updates live now
* Enhancement: "Add New" button in blank list table views
* Enhancement: display `<language>` tag in RSS channel
* Enhancement: forbid asset deletion when used in feed or web player
* Bugfix: Templates list view highlights template preview correctly now for more than one entry
* Bugfix: remove duplicate rel="self" entry from RSS feeds
* Bugfix: correct escaping for all input fields

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