=== Podlove Podcast Publisher ===
Contributors: eteubert, chemiker
Donate link: http://flattr.com/thing/728463/Podlove-Podcasting-Plugin-for-WordPress
Tags: podlove, podcast, publishing, blubrry, podpress, powerpress, feed, audio, video, player
Requires at least: 3.0
Tested up to: 3.8
Stable tag: trunk
License: MIT

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

https://github.com/podlove/podlove-publisher

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

== Installation ==

1. Download the Podlove Publisher Plugin to your desktop.
1. If downloaded as a zip archive, extract the Plugin folder to your desktop.
1. With your FTP program, upload the Plugin folder to the wp-content/plugins folder in your WordPress directory online.
1. Go to Plugins screen and find the newly uploaded Plugin in the list.
1. Click Activate Plugin to activate it.

== Changelog ==

= 1.10.11 =

* Fix: reenable "force download" option

= 1.10.10 =

We discovered incompatibilities between our tracking implementation and some clients. To avoid further trouble, we are *deactivating tracking* until we solve the issue. The option is still available, we just switch it off automatically with this release and it isn't on by default any more.

If you're of the curious type, feel free to activate it and tell us any issues you run into. Thanks!

= 1.10.9 =

* Fix: When tracking was active but no geo-location database available, downloads would fail. This exception is handled correctly now. You can check the status of tracking and the geo-location database in `Expert Settings > Tracking`

= 1.10.8 =

* Feature: Services in templates can be filtered by their type. That way, you can, for example, iterate over all Twitter accounts via `podcast.services({type: "twitter"})`. The previous "type" parameter (for choosing between "social", "donation" and "all") has been renamed to "category". All default templates have been adjusted accordingly _but if you were using this API in a custom template, you need to change it_.
* Feature: `podcast.contributors` in templates are sorted by name now. You can change the order by writing `podcast.contributors({order: "DESC"})`. When using grouping, each group will be sorted separately.
* Feature: `podcast.contributors({scope: "global-active"})` is limited to contributors with at least one contribution in a published episode. To list contributors ignoring this limitation, use `podcast.contributors({scope: "global"})`. "global-active" is the new default.
* Feature: Allow manual posting of ADN announcements
* Feature: Add contributor support to ADN announcements
* Feature: We are beginning to implement download intent tracking and statistics. As a first step, we are now tracking download intents. A following release will contain an analytics section where you can examine the statistics.
* Feature: The feed `<link>` can be configured in `Expert Settings > Website` now. It still defaults to the home page. Other options include the episode archive and any WordPress page.
* Enhancement: remove encryption for "protected feed" password to prevent autofill browser features to destroy contents
* Enhancement: default WordPress search now covers episode subtitle, summary and chapters
* Enhancement: add Vimeo, Gittip and about.me to services
* Enhancement: The expert setting "Display episodes on front page together with blog posts" changed to "Include episode posts on the front page and in the blog feed". So if you set it, episodes will additionally appear in `/feed`. However, only in the form of a post. You will not find enclosures, iTunes metadata etc. in `/feed` items.
* Enhancement: sort chapters imported from Auphonic by time
* Enhancement: Changes to feed list: redirect URL is shown and added screen options to hide columns
* Enhancement: Added Publisher version as an attribute to the export file. If a file is imported with a version different from the current Publisher, a warning is displayed.
* Fix: enable group and role selection in contributor shortcodes
* Fix: failing delayed ADN broadcast
* Fix: stop sending ADN announcements for old episodes
* Fix: refresh of Auphonic presets keeps current preset
* Fix: `contributor.episodes` does not return duplicate episodes any more
* Fix: Jabber URL scheme is now prefixed with `jabber:`
* Fix: Display podcast subtitle in feed description (it was the blog description before)
* Fix: Hide contributors missing a URI from feeds
* Fix: Escaping issue when saving podcast description settings

= 1.10.7 =

* Feature: Direct episode access in templates via `{{ podcast.episodes({slug: 'pod001'}).title }}`
* Feature: Episodes in templates can be filtered and ordered, for example `{{ podcast.episodes({orderby: 'title', 'order': 'ASC'}) }}`. For details, see [`podcast.episodes` documentation](http://docs.podlove.org/publisher/template-reference/#podcast)
* Feature: Direct contributor access in templates via `{{ podcast.contributors({id: 'john'}).name }}`
* Feature: Add shortcode `[podlove-podcast-social-media-list]`, which lists all social media accounts for the podcast
* Feature: Add shortcode `[podlove-podcast-donations-list]`, which lists all donation accounts for the podcast
* Feature: Add tag support for Auphonic
* Enhancement: Add "Save and Continue Editing" buttons to all table based management screens
* Enhancement: Use translations for month and day names in formatted template dates (if a language other than english is used)
* Enhancement: Add refresh buttons for Auphonic preset selector
* Enhancement: Pass more data to web player (as preparation for the next release)
* Enhancement: Improved export format: It has its own namespace and a version now. Publisher version and export date are included as XML comments. XML elements are indented for better readability.
* Remove default content for new templates
* Fix: "Network Activate" works now
* Fix: group and role filters for `[podlove-podcast-contributor-list]` shortcode work as expected now
* Fix: Add services and donations to export format
* Fix: `episode.player` in episode loops, outside the WordPress loop works now
* Fix: Auphonic chapter integration issue
* Fix: Instagram URL scheme

= 1.10.6 =

* Fix: contributor services will be saved correctly
* Enhancement: add a donation column to contributor management table

= 1.10.5 =

**Changes to the Templating System**

`episode.recordingDate` and `episode.publicationDate` are DateTime objects now. Available accessors are: year, month, day, hours, minutes, seconds. For custom formatting, use `episode.recordingDate.format("Y-m-d H:i:s")` for example. Calling `episode.recordingDate` directly is still supported and defaults to the format configured in WordPress.

**Other Changes**

* Enhancement: Add refresh buttons for ADN patter and broadcast channel selectors
* Fix: Avoid "Grey Goo" scenario of self-replicating contributors

= 1.10.4 =

* Hotfix: solve migration issue

= 1.10.3 =

**Changes to the Templating System**

* New filter: `padLeft(padCharacter, padLength)` can be used to append a character to the left of the given string until a certain length is reached. Example: `{{ "4"|padLeft("0",2) }}` returns "04";
* For consistency `{{ contributor.avatar }}` is now an object. To render an HTML image tag, use `{% include '@contributors/avatar.twig' with {'avatar': contributor.avatar} only %}`.
* `{{ episode.duration }}` has been turned into an object to enable custom time renderings. The duration object has the following accessors: hours, minutes, seconds, milliseconds and totalMilliseconds.

__DEPRECATIONS/WARNINGS__

* `{{ episode.duration }}` should not be used any more. The default templates are updated but if you have used it in a custom template, you must replace it. Example: `{{ episode.duration.hours }}:{{ episode.duration.minutes|padLeft("0",2) }}:{{ episode.duration.seconds|padLeft("0",2) }}`
* `{{ episode.license.html }}` and `{{ podcast.license.html }}` are deprecated. Use `{% include '@core/license.twig' %}` for the previous behaviour of choosing the correct license based on context. If you want to be more specific, use `{% include '@core/license.twig' with {'license': episode.license} %}` or `{% include '@core/license.twig' with {'license': podcast.license} %}`.

**Other Changes**

* Feature: ADN Module supports broadcasts
* Enhancement: Contributor shortcode defaults to `donations="yes"` to avoid confusion
* Enhancement: `[podlove-episode-downloads]` now uses templates internally
* Enhancement: Added 500px, Last.fm, OpenStreetMap and Soup to Services
* Enhancement: Use custom contributor social/donation titles as icon titles
* Enhancement: Template form has a "Save Changes and Continue Editing" button now
* Enhancement: feed validation is asynchronous now and has improved performance
* Enhancement: Licenses have a new interface and are compatible with Auphonic now: they can be imported from a finished production and are included when creating a production.
* Enhancement: Default MySQL character set is utf8 now when creating tables
* Enhancement: Add datepicker for episode recording date
* Fix: all default contributors appear in new episodes again
* Fix: change Tumblr URLs from https to http since Tumblr does not support them
* Fix: `[podlove-podcast-contributor-list]` shows the correct contributors now
* Fix: internal template warning when accessing empty contributor roles or groups
* Fix: episode rendering when no files are available
* Fix: flattr script in rss feeds
* Fix: importer issue where sometimes modules would not activate properly

= 1.10.2 =

* Feature: add template filter `formatBytes` to format an integer as kilobytes, megabytes etc. Example: `{{ file.size|formatBytes }}`
* Feature: New accessor `{{ file.id }}`. This is required to generate download forms.
* Fix: `[podlove-episode-contributor-list]` shortcode: Firstly, the "title" attribute works again. Secondly, output by group is optional now and defaults to "not grouped" (as it was before 1.10). If you are using contributor groups and would like grouped output, use `[podlove-episode-contributor-list groupby="group"]`
* Fix: division by zero bug in statistics dashboard
* Fix: parse time in statistics dashboard correctly as normalplaytime
* Fix: add missing template accessor `{{ episode.recordingDate }}`
* Remove separate "publication date" field in episodes. Instead, use the episode post publication date maintained by WordPress. It can be accessed via `{{ episode.publicationDate }}`
* Fix: missing contributor-edit-icon on last entries

= 1.10.1 =

* Fix: podlove-episode-contributor-list shortcode: add support for "group" and "role" attributes
* Fix: podlove-episode-contributor-list shortcode: fix broken flattr button
* Fix: feed widget: only compress if zlib extension is loaded

= 1.10.0 =

**All-new, mighty Templating system**

You can now use the [Twig Template Syntax](http://twig.sensiolabs.org/documentation) in all templates. Access all podcast/episode data via the new template API. Please read the [Template Guide](http://docs.podlove.org/guides/understanding-templates/) to get started.

If you have used templates before, please note that some shortcodes are now _DEPRECATED_. That means they still work but will be removed at some point. Following is a list of affected shortcodes and their replacements:

Instead of `[podlove-web-player]`, write `{{ episode.player }}`.

Instead of `[podlove-podcast-license]`, write `{{ podcast.license.html }}`.

Instead of `[podlove-episode-license]`, write `{{ episode.license.html }}`.

Instead of `[podlove-episode field="subtitle"]`, write `{{ episode.subtitle }}`. Instead of `[podlove-episode field="summary"]`, write `{{ episode.summary }}` etc. When in doubt, look at the [Episode Template Reference](http://docs.podlove.org/publisher/template-reference/#episode).

Changing the podcast data shortcodes works exactly the same: Instead of `[podlove-podcast field="title"]`, write `{{ podcast.title }}` etc. When in doubt, look at the [Podcast Template Reference](http://docs.podlove.org/publisher/template-reference/#podcast).

**Other Changes**

* Feature: The Podlove dashboard includes a section for feeds if you activate the "Feed Validation" module. It is intended as an overview for the state of your feeds. It shows the latest modification date, the number of entries, compressed and uncompressed size and the latest item. Additionally, you can validate your feeds against the w3c feed validator right from the dashboard.
* Feature" Better Bitlove integration. There is a new setting in `Podlove > Podcast Feeds > Directory Settings` called "Available via Bitlove?". It checks if there is a corresponding Bitlove feed and verifies it on a regular basis.
* Feature: Support for the oEmbed format
* New shortcode: `[podlove-episode-list]` lists all episodes including their episode image, publication date, title, subtitle and duration chronologically. This replaces the archive pages generated by the [Archivist - Custom Archive Templates](https://wordpress.org/plugins/archivist-custom-archive-templates/) plugin, if you are using it right now.
* New shortcode: `[podlove-feed-list]` lists all public feeds
* New shortcode: `[podlove-global-contributor-list]` shows all podcast contributors and lists related episodes.
* New shortcode: `[podlove-podcast-contributor-list]` shows regular podcast contributors
* Enhancement: The feed title may now include the asset title for easier discovery. This setting can be found at `Podlove > Feed Settings`
* Changed shortcode: `[podlove-contributor-list]` is _DEPRECATED_. Please use `[podlove-episode-contributor-list]` instead.
* Enhancement: add "autogrow" feature to chaptermarks text field
* Enhancement: globally hide the migration-tool banner once dismissed rather than per-client via cookie
* Fix: When setting the chapter asset to manual, delete all chapter caches to avoid hiccups
* Fix: Contributor links in the backend use an ID now rather than the contributor slug. That way they work when no slug is set.
* Fix ADN backslash escaping issue in post titles
* Fix: all contributions can be deleted

= 1.9.12 =
* Enhancement: Take over chapters when switching from chapter asset to manual
* Enhancement: Contributor tables look better in a wider range of themes
* Fix: Auphonic module: Buttons cannot be clicked again while the corresponding action is in progress

= 1.9.11 =
* Enhancement: Split podcast settings into tabs.
* Enhancement: Import/Export module supports contributors and contributions
* Enhancement: Separate "default contributors" and "podcast contributors". You can configure default contributors in "Contributor Settings > Defaults" and podcast contributors in "Podcast Settings > Contributors". Display podcast contributors using the shortcode `[podlove-podcast-contributor-list]`.
* Enhancements: Plethora of adjustments in contributor interfaces to avoid confusions and smoothen workflows
* Feature: Contributions may have a public comment (to describe the context of the person), which can be displayed in contributor lists.
* Fix: Skip contributions with missing contributors.

= 1.9.10 =
* Fix: episode images when using manual entry
* Fix: do not include episodes in blog feed
* Fix: paged feed calculation of number of pages when using global Publisher default
* Fix: remove unused IDs from contributor lists

= 1.9.9 =
* Fix: several contributor episode form bugs
* Fix: sum of all media file sizes in dashboard statistics
* Add lost bugfix: Bundle crt file to avoid StartSSL trust issues.

= 1.9.8 =
* Enhancement: WordPress has an option to close commenting for posts after a certain amount of days. This now also applies to podcast episodes.
* Enhancement: Fallback for Contributor Names.
* We had to change the generated Flattr URL for contributors in episodes to a less error prone scheme. Flattr counts for those buttons will therefore reset to 0 (the actual clicks are _not_ lost! they are just not displayed).
* fix sum of all media file sizes in dashboard statistics
* fix license URLs
* fix feed paging issue
* Fix: Feed Item Limit is now displayed correctly
* Fix: Ignore deleted contributors if they were assigned to an Episode or Podcast
* Fix: activation / deactivation of multiple modules at once works as expected now
* add filter "podlove_enable_gzip_for_feeds" to disable gzip feed compression
* Contributor role and group columns will be hidden if no roles or groups were added

= 1.9.7 =
* fix and enhance dashboard statistics
* gender statistics: use episode contributions instead of contributors for counting

= 1.9.6 =
* fix redirect issue after podcast migrations
* fix legacy ADN module publishing issue
* only show `itunes:complete` in feeds if it is set avoid a feedvalidator.org bug
* add experimental episode fun facts in dashboard
* add PayPal Button link in contributor settings
* other contributor admin enhancements
* contributor public name defaults to real name now

= 1.9.5 =
* Contributor Module improvements
  * New icon graphics
  * "Contributor Groups" as a new way to divide contributors by participation. For example, you might want to have a "Team" group and one for supporting contributions.
  * No more default roles. It's just not possible to provide a sensible default set. So just add the ones you need :) (existing roles will *not* be deleted)
  * The contributors defined in `Podcast Settings > Contributors` are now the default contributors for new episodes
  * Reworked contributor management table. Better use of space, hideable columns, avatars and more.
  * Reworked episode contributor table. Avatars, edit links and more.
  * Support for more services
  * ... and a bunch of other tweaks
* Web Player Update: compatible with WordPress theme "Twenty Fourteen"
* Fix: don't gzip feeds when zlib compression is active
* Fix: episode media file checkbox width for WP3.8
* Fix: menu icons for WP3.8

= 1.9.4 =
* Fix: gzip feeds on compatible systems only (avoids failing feed generation)
* Fix: Feed paging (again)

= 1.9.3 =
* Fix: provide global feed limit default on setup
* Fix: managing contributor roles no longer outputs permission issues
* Fix: corrected a faulty "Add New" contributor link
* Fix: paged feeds were broken

= 1.9.2 =
* Fix: _Module: Contributors_ prevent initial migration to import duplicate contributors
* Fix: _Module: Contributors_ Fix faulty default roles

= 1.9.0 / 1.9.1 =

**New Module: Contributors**

Podcasts are not possible without their active communities. Huge contributions are being made behind the scenes and nobody notices except the podcaster. The contributors module shines light on all those diligent people. It's now easy to manage contributors of an episode and list them on the blog. The list contains references to their social profiles and the donation service Flattr. Shortcode to display them in an episode post: [`[podlove-contributor-list]`](http://docs.podlove.org/publisher/shortcodes/#contributors).

**Simple Protected Feeds**

You can now protect some or all of your feeds using HTTP authentication. Authenticate via a defined username and password or use the WordPress user database as backend.

**License Selector**

We built an interface to generate a Creative Commons license for your podcast and episodes. You can still use a custom URL and name if you don't want a CC license. Use `[podlove-podcast-license]` and `[podlove-episode-license]` to display them in your episode posts.

**Other Changes**

* Feature: Add "Expert Settings" option to always redirect to media files instead of forcing a browser download. This is interesting for you if you want to minimize traffic on your server hosting the Publisher.
* Feature: add global setting to configure feed item limits
* Feature: Set "itunes:explicit" tag per episode if you want to (you have to activate the feature in the expert settings)
* Enhancement: Feeds are delivered with gzip compression if possible
* Enhancement: Support for temporary redirects in expert settings
* Fix: keep ?redirect=no flag in paged feeds
* Fix: _Module: Import/Export_ Importing episodes no longer causes floods of ADN posts.
* Fix: _Module: Auphonic_ respect Auphonic chapter offset
* _DEPRECATED_: `podlove-contributors` shortcode. Use `podlove-contributor-list` instead

= 1.8.13 =
* Feature: Update Web Player to 2.0.17 (for realsies). It fixes an issue with icon/font display.

= 1.8.12 =
* Feature: Update Web Player to 2.0.17
* Bugfix: Fix PHP 5.3 issue in import module

= 1.8.11 =
* Feature: New module for Import/Export. Now you can easily move all your podcast data to another WordPress instance.
* Feature: Add support for `<itunes:complete>` tag. If there won't be any additional episodes, you can go to `Podlove > Podcast Settings` and activate this setting.
* Bugfix: Bundle crt file to avoid StartSSL trust issues.

= 1.8.10 =
* Hotfix: Removes incompletely updated license feature which wasn't supposed to be in that release in the first place. Sorry!

= 1.8.9 =
* Feature: Update Web Player to 2.0.16
* Enhancement: Render Twitter and OpenGraph tags using a DOM-Generator to avoid all possible escaping issues.
* Enhancement: Allow multiple mime types for web player config slots. Fixes an issue with Firefox and Opus.
* Enhancement: I CAN HAZ SECURETEH?! auth.podlove.org haz https nao.
* Bugfix: Module settings screen rendering issue with PHP 5.3
* Bugfix: Fix link to shortcode documentation

= 1.8.7 / 1.8.8 =
* Enhancement: Refined Auphonic Workflow: Always import duration and slug; new option to automatically start productions after creation; new option to automatically publish episodes as soon as the production is ready
* Hotfix: escaping issue

= 1.8.6 =
* Enhancement: Change feed redirect hook and priority so it works better with Domain Mapping plugin
* Enhancement: Extend OpenGraph metadata by post thumbnail and episode description (thanks smichaelsen!)
* Feature: Update Web Player to 2.0.15
* Fix: Solve rare issue where first chapter line would be ignored
* Fix: Firefox display issue in migration assistant

= 1.8.5 (2013-08-11) =
* Fix: JavaScript issue preventing certain UI elements from working correctly (Tagging, Auphonic, …)

= 1.8.4 (2013-07-27) =
* Fix: Performance issue in Auphonic plugin

= 1.8.3 (2013-07-27) =
* Enhancement: dates with leading zeros in Auphonic module
* Enhancement: Auphonic UI smoothifications
* Enhancement: Update assets after successful production

= 1.8.2 (2013-07-27) =
Auphonic integration Enhancements

* Preset is only applied once
* Add Text for "Open Production" button
* "Start Production" button more prominent

= 1.8.1 (2013-07-27) =
* Fix Release

= 1.8.0 (2013-07-27) =
* Auphonic Module Update. You are now able to manage productions directly from within the Publisher without visiting Auphonic at all. As always, any feedback is more than welcome.
* App.net Module Update. Support for Patter, language annotations and delayed posting.
* Enhancement: Control sequence in which audio elements are printed in the web player. This encourages browsers to use superior codecs (rather than mp3).

= 1.7.3 (2013-07-18) =
* Enhancement: Show expected and actual mime type in log when an error occurs
* Bugfix: Fix Bitlove integration
* Bugfix: Correctly hide content in password protected posts
* Bugfix: ADN Plugin annonced new episode every time the episode got saved
* Fix some PHP 5.4 Strict warnings

= 1.7.2 (2013-07-11) =
* Feature: Update Web Player to 2.0.13
* Bugfix: Feed web player with existing/valid files only
* Bugfix: Downloads work without JavaScript enabled
* Bugfix: Episode previews should work now
* Bugfix: Migration Assistant: you are now able to import file slugs containing dots
* Bugfix: Fix podlove_alternate_url issue

= 1.7.1 (2013-07-06) =
* Logging Module: Deactivate sending of mails until we figure out what causes some misbehaviours
* Enhancement: System Report: check for SimpleXML availability
* Bugfix: ADN Announcements should work with all kinds of templates now

= 1.7.0-alpha (2013-07-03) =
* New Module: App.net. Right now, it lets you announce new podcast episodes on ADN whenever you publish a new one. It's the groundwork for more ADN integrations. (Thanks @chemiker!)
* New Module: Auphonic. We did not shy away from writing a completely new module to present to you the best Auphonic integration the world has seen in a WordPress plugin. It replaces the previous one ("Auphonic Production Data"). You are now able to import Auphonic production data without the need for a production description file. Like the ADN module, this lays the groundwork for much deeper Auphonic integration. (Thanks @chemiker!)
* Enhancement: Return the correct content type when initiating a download so devices may choose intelligently whether to save the file or open it in a certain application.
* Enhancement: Remove download button styles so the style adjusts based on used browser and theme
* Bugfix: Fix incompatibility to some file name schemes
* Bugfix: Fix 404 status for paged feedburner feeds

= 1.6.11-alpha =
* Bugfix: use NPT library

= 1.6.10-alpha =
* Fix release issues

= 1.6.7-alpha =
* Enhancement: Move file types settings to expert settings
* Enhancement: Saving a template redirects to template list
* Enhancement: System Report is a readonly textarea
* Enhancement: Group modules
* Enhancement: When creating an asset: if that web player slot is not taken yet, assign it automatically
* Enhancement: Accept time formats with minutes > 59 if no hours are given
* Bugfix: Fix "Chapters Visibility" setting

= 1.6.6-alpha =
* Enhancement: When validating, ignore timeouts (so files don't disappear from feeds just because one request took too long)
* Enhancement: When episode permalinks are invalid, try to autoresolve by switching to "Use Post Permastruct"
* Bugfix: Fix some expert setting migration issues
* Bugfix: Hide invalid media files from downloads

= 1.6.5-alpha =
* Feature: Feeds are sortable
* Feature: You can revalidate single media files in the dashboard
* Enhancement: Use pretty status icons
* Enhancement: Add "sortable handle" for asset and feed lists, so the sortability feature is more discoverable
* Enhancement: Add "Podlove" entry to WordPress toolbar
* Enhancement: Organize "Expert Settings" into tabs
* Enhancement: Don't log "File not Modified"
* Bugfix: Activate feature "Activate asset for all existing episodes" for pending episodes
* Bugfix: Solve issue with chapter asset cache invalidation
* Bugfix: Solve chapter encoding issue when chapters start with umlauts
* Bugfix: Fix video display in some themes
* Other small UI changes in various places

= 1.6.4-alpha =
* Bugfix: use manual chapter entries if available
* Bugfix: PSC assets work properly
* Bugfix: URL magic doesn't interfere with other post types
* Bugfix: deactivate preload in web player

= 1.6.3-alpha =
* Bugfix: "Display episodes on front page together with blog posts" works again
* Bugfix: chapters at 0 seconds are not ignored any more
* Bugfix: correctly show feed title in deletion confirmation
* Bugfix: handle missing/invalid PSC file with appropriate grace
* Bugfix: remove player from feed
* Bugfix: fix false negatives in error log; reenable logging-mails
* Bugfix: fix timezone in logs

= 1.6.2-alpha =
* Bugfix: fix template autoinsert migration issue

= 1.6.1-alpha =
* Bugfix: fix call-time pass-by-reference
* Bugfix: deactivate logging-mails until we find out what's wrong

= 1.6.0-alpha =
* Feature: New modules "Asset Validation" and "Logging". Automatically verify assets once in a while (fresh posts will be validated more often than old posts). Detailed logging in Podlove dashboard. Receive an email when all episode assets are unavailable.
* Feature: always print PSC in feed if any chapter format is available (psc, mp4chaps, json)
* Feature: upgrade web player to v2.0.10
* Enhancement: template autoinsert settings are on templates page now
* Enhancement: correctly fall back to podcast image when episode image is activated but missing
* Enhancement: various UI fixes (thanks @MaZderMind)
* Enhancement: improve feed deletion dialogue
* Enhancement: default title for episode assets is file format title
* Bugfix: solve permalink issue after migrations
* Bugfix: migrate comment hierarchy correctly

= 1.5.4-alpha =
* Feature: PubSubHubbub support via new module
* Enhancement: Check for iconv availability in system report
* Turn permalink compatibility up to eleven

= 1.5.3-alpha =
* Bugfix: more robust permalink fix

= 1.5.2-alpha =
* Bugfix: Fix using the same permalink structure / 404 on pages

= 1.5.1-alpha =
* Enhancement: episodes may share the same permalink structure with WordPress posts
* Enhancement: episode archive url can be configured
* Enhancement: run system report more intelligently
* Enhancement: Auphonic module works more smoothly for new episodes
* Enhancement: Fallback to 302 redirects for HTTP/1.0 clients
* Enhancement: Confirm before deleting feeds and templates
* Enhancement: Parse time strictly following the NPT specification: http://www.w3.org/TR/media-frags/#npttimedef
* Bugfix: don't use feed redirect when a feed archive page is specified

= 1.4.8-alpha =

Minor fixes and improvements:

* feed: remove style tags from content:encoded (feedvalidator.org warning)
* feed: ensure description precedes content:encoded (feedvalidator.org warning)
* prevent feed proxy issue
* `HEAD` requests for paged feeds return correct responses
* enable paging for `/podcast` archives
* add description to redirect settings
* rename "record date" to "recording date"

= 1.4.7-alpha =
* Hotfix: ignore empty redirect rules

= 1.4.6-alpha =
* Bugfix: The podcast archive is available via `/podcast` again.

= 1.4.5-alpha =
* Enhancement: always show critical errors found by system report
* Enhancement: flush rewrite rules after migration and feed changes
* Enhancement: redirect settings support URL parameters

= 1.4.4-alpha =
* Feature: configure permanent redirects in Expert Settings
* Bugfix: fix feed url generation for "default style" permalinks
* Bugfix: migration assistant shows enclosure errors/warnings
* Bugfix: add missing atom prefix in feed link elements
* Bugfix: generate valid episode permalinks for "Default"/"Not Pretty" permalink settings
* Bugfix: change default episode permalink structure from `%podcast%` to `podcast/%podcast%` to avoid conflicts with those setups using %postname% as WordPress permalink — which is quite common.

= 1.4.3-alpha =
* Bugfix: fix system report issue
* Bugfix: fix feed setting "No limit. Include all items."

= 1.4.2-alpha =
* Bugfix: add auphonic metadata file type
* Bugfix: fix bug regarding limiting feed items

= 1.4.1-alpha =
* Bugfix: reactivate /podcast url

= 1.4.0-alpha =
* Feature: "Soft Launch" for migration tool. It isn't activated by default but if you are adventurous, feel free to give it a try. Any feedback is greatly appreciated!
* Feature: Support paged feeds (RFC5005) so clients may always fetch all episodes even if the default feed only contains the most recent episodes
* Feature/Change: Similar to the web player setting, you now can insert templates automatically at the beginning or end of a post. You could even create multiple templates, one to append and one to prepend. This replaces the previous template-autoinsert feature.
* Feature: New module "Auphonic Production Data". Thanks @tobybaier!
* Enhancement: Update Web Player to v2.0.7
* Enhancement: open graph title is podcast title

= 1.3.30-alpha =
* Feature: Option to autoinsert web player at beginning or end of post
* Feature: Add "Support" page including a system report
* Enhancement: Add .post class to article-classes list to improve theme compatibility
* Bugfix: Fix feed validation mixup
* Bugfix: Support "future publishing" of episodes (thanks Marc!)

= 1.3.29-alpha =
* Bugfix: Fix some media file mixups

= 1.3.28-alpha =
* Feature: Two new episode fields `publication_date` and `record_date`. Accessible via episode shortcode. Must be enabled in expert settings.
* Feature: Assets can be sorted via drag'n'drop. Influences download button/list order.
* Bugfix: fix "No More Enclosures" feature. I was using a deprecated hook
* Enhancement: upgrade Podlove Web Player to 2.0.5
* Enhancement: move episode asset url to expert settings
* Change: Drop support for Atom feeds
* Change: Remove support for mnemonic and Episode Assistant module

In the beginning, everything evolved around the episode numbers and the
mnemonic. Then, it made sense to support this concept by something like the
episode assistant.

Now, the mnemonic is merely an afterthought. It's used by no part of
the system except the episode assistant. And this doesn't do a lot that
can't be done without it either. So we decided to drop both for now.

A similar concept might return once we tackle stuff like seasons.

= 1.3.27-alpha =
* Enhancement: enforce trailing slash at the end media file base url
* Enhancement: fix huge download-select-font
* Enhancement: doublecheck curl availablity
* Bugfix: double quote escaping for Web Player title, subtitle and summary

= 1.3.26-alpha =
* Enhancement: upgrade Podlove Web Player to 2.0.4

= 1.3.25-alpha =
* Feature: Setting for Web Player to show or hide chapters by default
* Enhancement: Open Graph now correctly excludes non-audio assets
* Enhancement: "File not found" errors now result in some debug output which may help tracing the issue
* Enhancement: upgrade Podlove Web Player
* Bugfix: Generated Template shortcodes now use the "id" attribute rather than "title"

= 1.3.24-alpha =
* Enhancement: remove mediaelementjs demo files

= 1.3.23-alpha =
* Enhancement: upgrade Podlove Web Player
* Enhancement: improve handling of url_fopen setting
* Enhancement: feed item limit is now a select box. default is now "all" instead of "WordPress Default"

= 1.3.22-alpha =
* Hotfix: solve White Screen of Death issue for PHP 5.4

= 1.3.21-alpha =
* Bugfix: allow deletion of unused assets
* Enhancement: if an asset shouldn't be deleted, display where it's in use (allow deletion anyway)
* Enhancement: Downloads redirect to file if `allow_url_fopen` is disabled.

= 1.3.20-alpha =
* Enhancement: always add a trailing slash to media file base url
* Bugfix: trying to fix escaping part whatnotsoever

= 1.3.19-alpha =
* Hotfix: slugs are not forced into lowercase any more

= 1.3.18-alpha =
* Feature: Module for Bitlove.org support! Adds links to torrent-files to the downloads-section of your episodes.
* Feature: add video support for web player
* Enhancement: fix a (possibly rare) memory bug when downloading files
* Enhancement: enable episodes on home page by default
* Enhancement: change default download widget style to the select-thingy
* Bugfix: fix feed warning

= 1.3.17-alpha =
* Bugfix: fix issue with 3rd party custom post types
* Enhancement: improve Feed Settings screen

= 1.3.16-alpha =
* Feature: new style for file downloads `[podlove-episode-downloads style="select"]`
* Enhancement: Solve feed url issues:
** ensure validity on save
** support non-pretty url format
* Enhancement: un-default some modules: episode assistant, twitter card summary
* Enhancement: fix asset & feed setting redirect issue
* Enhancement: add caption file types
* Enhancement: new icons!
* Enhancement: allow underscores and dots in slugs
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
