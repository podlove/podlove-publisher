=== Podlove Podcast Publisher ===
Contributors: eteubert
Donate link: https://opencollective.com/podlove
Tags: podlove, podcast, publishing, rss, feed, audio, mp3, m4a, player, webplayer, iTunes, radio
Requires at least: 4.9.6
Tested up to: 5.9
Requires PHP: 7.0
Stable tag: 3.8.0
License: MIT

The one and only next generation podcast publishing system. Seriously. It's magical and sparkles a lot.

== Description ==

We built the Podlove Podcast Publisher because existing solutions are stuck in the past, complex and unwieldy. The Publisher helps you save time, worry less and provides a cutting edge listening experience for your audience.

Official Site: [publisher.podlove.org](http://publisher.podlove.org/)

= Video Tutorial: Getting started with Podlove Publisher =

[youtube http://www.youtube.com/watch?v=Hmrm-jUe6u4]

= Compatible Feeds =

The Publisher makes it easy to create highly expressive, efficient and super compatible podcast feeds with fine grained control over client behavior (e.g. GUID control to replace faulty episodes and for clients to reload) supporting all important meta data.

= Multi-Format Publishing =

The Publisher also makes multi-format publishing - embracing all modern and legacy audio and video codecs - a snap. By adopting simple file name conventions, the plugin allows the podcaster to provide individual feeds for certain use cases or audiences without adding work for the podcaster during the publishing process.

= Optimized Web Player =

The Publisher also comes with integrated with the Podlove Web Player plugin (which you do not need to install separately) and fully support its advanced options including multiple audio (MP4 AAC, MP3, Vorbis, Opus) and video (MP4 H.264, WebM, Theora) format support for web browsers. This Web Player is fully HTML5 compatible (but provides Flash fallback for ancient environments) and is ready for all touch based clients too.

= Chapter Support =

The Publisher also makes it easy to publish chapter information in the player to make access to structured episodes even easier. Full support for linking directly to any part of your podcast on the web with instant playback included.

= Flexible Templates =

To round it all up, a flexible template system enables you to published Podcasts in a defined fashion and change the style at any time without having to touch your individual postings later on.

And this is just the beginning. We have a rich roadmap that will bring even more interesting features: integration with helpful services, much improved timeline metadata support (show notes) and much more.

= Further Reading =

* [Podlove Publisher](http://publisher.podlove.org/)
* [Podlove Project](http://podlove.org/)
* [Podlove Community](https://community.podlove.org/)
* [Documentation](http://docs.podlove.org/)
* [Bug Tracker](https://github.com/podlove/podlove-publisher/issues)
* [Donate](http://podlove.org/donations/)

Development of the plugin is an open process. The current version is available [on GitHub](https://github.com/podlove/podlove-publisher) Feel free to contribute and to fix errors or send improvements via GitHub.

Requires PHP 5.4+

== Frequently Asked Questions ==

### Is Podlove Podcast Publisher free?

Yes! The core features of Podlove Podcast Publisher are and always will be free. [Paid Professional Support](https://publisher.podlove.org/support/) is available but not necessary to run the plugin.

### Are there Download Statistics?

Yes! Podcast Downloads can be tracked and analyzed. You can easily see how many people downloaded you podcast episodes, which clients they used, if they prefer to subscribe to the feed or listen on your website using the web player—and much more.

### Are there Privacy / GDPR considerations?

Podlove Publisher is GDPR compliant and provides prewritten text snippets for your privacy page. See https://docs.podlove.org/podlove-publisher/guides/dsgvo-gdpr.html

### Where can I host my podcast files?

Any storage where you have control over the file naming is compatible with Podlove Podcast Publisher. You can manage files using a simple FTP/sFTP or use services like Amazon S3.

### Where can I ask questions and get support?

Free support where questions are answered by the community is available in the [Podlove Community Forum](http://community.podlove.org/). There is a German community in the [Sendegate](https://sendegate.de/).

### How can I help the project?

The continued success of Open Source project relies on the community. There are many ways you can help:

- If you enjoy the plugin, please [leave a review](https://wordpress.org/support/plugin/podlove-podcasting-plugin-for-wordpress/reviews/#new-post).
- You can answer questions of other fellow podcasters in the [Podlove Community](https://community.podlove.org/).

---

This product includes GeoLite2 data created by MaxMind, available from http://www.maxmind.com.

== Installation ==

1. Download the Podlove Publisher Plugin to your desktop.
1. If downloaded as a zip archive, extract the Plugin folder to your desktop.
1. With your FTP program, upload the Plugin folder to the wp-content/plugins folder in your WordPress directory online.
1. Go to Plugins screen and find the newly uploaded Plugin in the list.
1. Click Activate Plugin to activate it.

== Screenshots ==
1. Custom episode post type separates media from your blog content.
2. Download analytics provide you with all the data you ever wanted.
3. The Publisher automatically checks the health of your media files.
4. The mighty template engine gives you full control over the episode presentation.
5. Includes the Podlove Subscribe Button, the easiest way for listeners to subscribe to your podcast.
6. Includes the Podlove Web Player. One more thing: you can manage and present all contributors easily.

== Changelog ==

= 3.8.0 =

**New module: Automatic Numbering**

Automatically increase the Episode number when creating episodes.
When using the "Shows" module, each show has its own numbering.

This module is active by default for new setups.
To use it in your current setup, go to `Podlove > Modules`, search for "Automatic Numbering" and activate it.

**Other Changes:**

- Shows Module: each show can define its own Auphonic production preset
- episode: after file revalidation, auto-detect duration
- contributors: assign a `Sponsor` role and it will appear as `<podcast:person ... role="sponsor">...` in the RSS feed
- fix: correct PHP version number in message (https://github.com/podlove/podlove-publisher/pull/1272)
- fix: feed cache issue when using the "Shows" module

= 3.7.0 =

**Shownotes**

The Shownotes module helps you manage link based show notes to display on your website and podcatchers.

The module UI has been rewritten and streamlined for efficient workflows.
A new UI element was added to allow for quickly sorting long lists of links into topics:
Whenever a link is dragged, a floating list of all topics appears next to the cursor.
The link can then be dropped under the desired topic there instead of scrolling through the whole list of shownotes.

Disclaimer: URL metadata detection uses a service hosted at [plus.podlove.org](https://plus.podlove.org). It is currently available for all users of Podlove Publisher. In the future, metadata detection may only be availabe to Publisher PLUS users as it requires infrastructure to run. The rest of the Shownotes functionality will stay available to all Podlove users as usual.

Documentation: https://docs.podlove.org/podlove-publisher/modules/shownotes

**Contributors**

* Notifications: add "always send to..." section. Contributors selected there will always receive update notifications.
* Avatars: default avatar is now a static svg instead of Gravatar (can be customized using the WordPress Filter `podlove_default_contributor_avatar_url`)

**Enhancements for creating Auphonic productions** (thanks [lumaxis](https://github.com/lumaxis)!):

* when the episode title is set, send this instead of the post title ([#1240](https://github.com/podlove/podlove-publisher/pull/1240))
* send the episode number as track number ([#1240](https://github.com/podlove/podlove-publisher/pull/1240))
* when the post thumbnail is configured as cover image, use it as direct fallback  ([#1241](https://github.com/podlove/podlove-publisher/pull/1241))

**Webhooks**

Define a webhook that gets triggered every time an episode updates.

The webhook is a `POST` request with an `event` parameter and a `payload`.
`event` is the webhook name ("episode_updated"), `payload` is a serialized
JSON object of the current episode.

Configuration:

    # wp-config.php
    define('PODLOVE_WEBHOOKS', [
        'episode_updated' => 'https://example.com/webhook-endpoint'
    ]);

**Other Changes**

* soundbites: add title field ([#1257](https://github.com/podlove/podlove-publisher/pull/1257), [#1237](https://github.com/podlove/podlove-publisher/issues/1237))
* allow detection of episode duration on mp4 ([#1249](https://github.com/podlove/podlove-publisher/pull/1249))
* update OPAWG data (for download analytics / user agent detection)

**Fixes**

* fix: parameters for shortcode `[podlove-episode-contributor-list]` ([#1233](https://github.com/podlove/podlove-publisher/issues/1233))
* fix: PHP 8 warnings ([#1258](https://github.com/podlove/podlove-publisher/issues/1258))
* fix: deleting an episode deletes its transcript from the database ([#1252](https://github.com/podlove/podlove-publisher/issues/1252))
* fix(contributors): notification test email ([#1247](https://github.com/podlove/podlove-publisher/issues/1247))
* fix(analytics): filtering of httprange requests with one or two bytes ([#1243](https://github.com/podlove/podlove-publisher/issues/1243))
* fix(image cache): redirect to source URL if image can't be downloaded into the cache

= 3.6.1 =

* fix: sql issue when creating the episode database tables

= 3.6.0 =

**New Module: Soundbite**

Adds support for the `<podcast:soundbite>` RSS feed tag.
The intended use includes episodes previews, discoverability, audiogram generation, episode highlights, etc.

Using this module you can specify an audio segment for each episode that can be read by for example audiogram generation services.

**New Module: WordPress File Upload**

If you are using WordPress Media as storage for your Podlove assets, this new
module adds conveniences.

First, you define a `Upload subdirectory` for your Podlove assets. This overrides
any WordPress settings, so for example you can safely enable the typical date/month
structure for WordPress attachments and it will not affect your Podlove Uploads.

Then you can update your "Podlove - Media - Upload Location" setting. You can keep
it empty to let Podlove Publisher take care of it, or set it yourself if you have
a custom file hostname.

Now there is an "Upload Media File" button in your episode form above
the "Episode Media File Slug" where you can directly upload your files.
If you are using multiple assets, you can upload them all there. Just make sure
they all have the same filename (except the file extension) before you upload.

= 3.5.5, 3.5.6 =

**Fixes**

* SECURITY: sql injection in "Social & Donations" module
* transcript API returns list again
* PLUS open graph images (use new API)
* handle webvtt voice, missing Contributors
* related episodes: remove whitespace in shortcode HTML to fix rendering in Spotify

**Changes**

* webvtt transcripts use public contributor name
* transcript voices / contributors:
  * you can now select "none" in the voice assignment
  * only voices with an assigned contributor (and not "none") appear in public transcripts
* generate default copyright claim if it is not explcitly set

= 3.5.4 =

* adds copyright field in "Podcast Settings - Directory", which is apparently required by the Apple Podcast Directory since yesterday.
* perf: remove frontend.js (inline logic to download button HTML)

= 3.5.2 / 3.5.3 =

This releases reverses all changes to Permalinks in releases 3.5.0 and 3.5.1.

I severely underestimated the effect these changes would have and revert all changes until I find a better solution. It’s simply not acceptable to change episode URLs, especially without an option for automatic redirects.

Please verify your episode URLs and the two expert settings “Permalink structure for episodes” and “Episode Pages”.

What to do if you have used the “PODLOVE_ENABLE_PERMALINK_MAGIC” constant? It has no effect any more and you can safely remove it from your config file.

What happened to the “Simple Episode Permalink” setting from release 3.5.1? It has been removed, too.

Sorry for the trouble.
Happy podcasting :)

**Other**

* fix: remove usage of PHP 7.1 syntax in one file

= 3.5.1 =

= 2021-04-14 =

* add: expert setting to make episode permalinks `/%postname%/`
* add: include Publisher Database Version in system report
* drop WordPress version requirement to 4.9.6

= 3.5.0 =

**Breaking Change**

Removes two expert settings:

* "Permalink structure for episodes" and
* "Episode pages"

These settings allowed to define custom URL structures for episodes and the episode archive.
However they have caused trouble for a long time (see [#1038](https://github.com/podlove/podlove-publisher/issues/1038))
and the only viable way out seems to remove them.

How does that affect you?

If you have never touched these settings, feel free to shrug, smile and move on.

If you _are_ using these settings, I encourage you to consider not using them as they are mostly of cosmetic nature.
Should you however prefer to keep everything as is (including the known bugs of erratically broken permalinks / URLs), you can
enable the settings back with a single line of code in your wp-config.php:

    `define('PODLOVE_ENABLE_PERMALINK_MAGIC', true);`

**Experimental: Full-Page Podlove Templates**

If you want to create a 100% custom page based on an episode but without all the WordPress theme around, this is for you.
Possible use case: A dedicated page to print the episode transcript.

1. create a new Podlove Template, for example `page-episode-transcipt`
2. Write that transcript as a _full HTML page_. That means it starts with `<!doctype html><html>` and ends with `</html>`!
3. Append `?podlove_template_page=page-episode-transcipt` to your public episode URL. For example if your episode is `https://example.com/ep001/`, then open `https://example.com/ep001/?podlove_template_page=page-episode-transcipt`

Very simple example template:

    <!doctype html>
    <html>

    <head>
      <meta charset="utf-8">
      <title>Transcript | {{ episode.title }} | {{ podcast.title }}</title>
      <meta name="viewport" content="width=device-width, initial-scale=1">
    </head>

    <body>

      <p>
        Here's the transcript for podcast <strong>{{ podcast.title }}</strong> episode <strong>{{ episode.title }}</strong>:
      </p>

      [podlove-transcript]

    </body>

    </html>

Enjoy!

**Shownotes Module**

* provide website screenshot as fallback when no sharing image is available (requires PLUS token)
* show images
* show image in edit view
* when importing, show all entries
* show import progress when unfurling
* fix osf importer
* fix encoding issue when importing from HTML

**Miscellaneous**

* update database for podcast user agents -- notably includes classification of Apple Watch downloads as bot [#1203](https://github.com/podlove/podlove-publisher/issues/1203)
* transcript: add some basic info about podcast and episode into webvtt as a note
* analytics: add hook `podlove_useragent_opawg_data` to add custom user agent detection
* Podlove Templates: add `dataUri` method to images. Takes same arguments as `url` but returns a data uri. Useful if you want to generate a self-contained HTML page. If you're not sure, better use `url`.
* fix: transcripts with trailing newlines don't confuse the importer
* fix: don't count contributors multiple times if they have multiple contributions in an episode ([#1200](https://github.com/podlove/podlove-publisher/issues/1200))
* fix: calling wptexturize too early ([#1194](https://github.com/podlove/podlove-publisher/issues/1194))

= 3.4.1 =

* fix: analytics shows section now does not include other taxonomies
* use image caching for shownotes images
* analytics shows section is now ordered by downloads

= 3.4.0 =

**podcastindex namespace**

Both additions add metadata to the feed automatically if the data is present. No new user interfaces or data entry is necessary.

* add support for feed tag [`podcast:transcript`](https://github.com/Podcastindex-org/podcast-namespace/blob/main/docs/1.0.md#transcript), linking to the transcript in various formats (json, webvtt, xml)
* add support for feed tag [`podcast:person`](https://github.com/Podcastindex-org/podcast-namespace/blob/main/docs/1.0.md#person) on episode level

**analytics**

* for selected date range, total downloads are shown
* for selected date range, display downloads per show (only visible when shows module is enabled)

= 3.3.2 =

* fix: in analytics, the "Export as CSV" section is now clickable when global statistics are loading or have no data
* fix: "Export as CSV" works again
* fix: "global statistics" charts idling indefinitely until a custom date range is chosen

= 3.3.0 / 3.3.1 =

* add support for feed tag [`podcast:funding`](https://github.com/Podcastindex-org/podcast-namespace/blob/main/docs/1.0.md#funding) (see Podcast Settings -> Directory)
* unfurl uses https://plus.podlove.org/api/unfurl as API endpoint
* add banner linking to donations page (can be dismissed)
* shownotes:
  * add shortcode `[podlove-episode-shownotes]`
  * display links even if unfurling failed
  * template improvements
  * add "delete all" button
  * polished failure section UI and allow editing original URL
  * API: add missing permission callbacks
  * fix: keep order when importing via slacknotes
* slacknotes: update to new API
* change donation URL to https://opencollective.com/podlove
* fix: handle missing templates in TwigLoaderPodloveDatabase

= 3.2.2 =

* fix: crash when creating new episodes

= 3.2.1 =

* fix: coverart url encoding [#1181](https://github.com/podlove/podlove-publisher/pull/1181)
* fix: some settings not applying to episode title tag (thanks Dirk)
* fix: crash when accessing season data for an episode without season

= 3.2.0 =

* when automatically generated episode titles are used, use the blogpost title as fallback for the episode title
* fix: disable slug auto-updating after importing from Auphonic
* fix: webvtt-parser autoloading issues [#1175](https://github.com/podlove/podlove-publisher/issues/1175)
* fix: escape ampersands in itunes:image hrefs in the feed [#1176](https://github.com/podlove/podlove-publisher/issues/1176) (fixes incompatibilities with Jetpack image CDN)

= 3.1.* =

* fix twig namespace prefixing related issues
* remove unused vendor-bin directory from releases

= 3.1.1 =

* tracking: fix operating systems appearing twice in different spellings
* chore: prefix all composer packages (solves Twig related incompatibilities & crashes)
* chore: add content and files to episodes api (#1165)

= 3.1.0 =

* analytics: new chart showing download development from episode to episode [#1100](https://github.com/podlove/podlove-publisher/pull/1100/files) thanks [@poschi3](https://github.com/poschi3)!
* Auphonic: show production warnings in module (https://twitter.com/auphonic/status/1305849345762185217)
* download tracking: use OPAWG podcast user agent database in addition to Matomo database
* stability: detect plugins using older/incompatible versions of Twig. Display a warning on the site (instead of an error) and a detailed explanation on "Podlove > Support" screen.
* enhance: podcast file validation in dashboard includes all post stati and checks for missing slug [#1161](https://github.com/podlove/podlove-publisher/pull/1161)
* enhance: only allow episode numbers of 0 and higher in form input [#1158](https://github.com/podlove/podlove-publisher/pull/1158)
* api: add public endpoint for transcripts
* api: add public endpoint for shownotes
* fix: Podlove Web Player 5 includes all downloadable assets in download section
* fix: transcript API URL [#1145](https://github.com/podlove/podlove-publisher/pull/1145) thanks [gibso](https://github.com/gibso)!
* fix: editing/deleting shows ([#1077](https://github.com/podlove/podlove-publisher/issues/1077))
* fix: episodes and shows API
* fix: migration for Shownotes only when the database table exists

= 3.0.4 =

* fix: contributor notifications settings can be saved again ([#1144](https://github.com/podlove/podlove-publisher/issues/1144))
* fix: do not include invisible contributors in Web Player 5 API ([#1142](https://github.com/podlove/podlove-publisher/issues/1142))
* fix: detect Yoast SEO, wpSEO: disables Open Graph module ([#1132](https://github.com/podlove/podlove-publisher/issues/1132))
* fix: use podcast summary as RSS Feed `<description>` if subtitle is not set ([#1092](https://github.com/podlove/podlove-publisher/issues/1092))

= 3.0.3 =

* fix: title escaping in RSS feed when using native (not auto-generated) titles

= 3.0.2 =

* add: Untappd social service
* fix: Auphonic module (wrong HTTP API headers)
* chore: update npm dependencies

= 3.0.1 =

* fix: escaping issue in RSS feed (itunes:author and itunes:owner)
* fix: remove (rare) accidental double enclosure tag in RSS feed when "enclosure" post meta is present

= 3.0.0 =

**Breaking Changes**

* requires PHP 7.0 (or newer)
* requires WordPress 5.2 (or newer)
* Web Player:
  * removes Podlove Web Player 2
  * removes Podlove Web Player 3
  * removes "insert player automatically" option (probably does not affect anyone as the web player is by default inserted via template)
  * removes "Chapters Visibility" option (use dedicated Web Player settings instead)

**New Publisher PLUS**

=> [plus.podlove.org](https://plus.podlove.org/)

Publisher PLUS is a new service providing Feed Proxy and Podcast Subscriber statistics for Podlove Publisher.

To use it, enable the *Publisher PLUS* module, then visit [plus.podlove.org](https://plus.podlove.org/) to create an account.

Subscriber Statistics are only the beginning. Expect more features soon!

**Experimental: Shownotes**

Generate and manage episode show notes. Helps you provide rich metadata for URLs. Full support for Publisher Templates.

This module is a work-in-progress. But it's usable, so feel free to give it a try, especially if your shownotes are link-heavy and you're comfortable writing Podlove (Twig) templates.

The module is currently hidden. Make it visible by setting a PHP constant, for example in your `wp-config.php`: `define('PODLOVE_MODULE_SHOWNOTES_VISBLE', true);`.

Use this template as a starting point: https://gist.github.com/eteubert/d6c51c52372dc2da2f1734a5f54c7918

**Shortcodes**

* `podlove-episode-contributor-list`
  * new design
  * renders text-only in RSS feed
* `podlove-podcast-contributor-list`
  * new design
* `podlove-episode-downloads`
  * the text link variant is now the default style

**Miscellaneous**

* remove Bitlove module (service does not exist any more)
* remove Flattr module
* remove "Website Protocol" setting (not necessary any more as Let's Encrypt is widely supported)
* enable episode chapters by default
* convenience: "Copy to Clipboard" function for Podlove Template shortcodes
* expose iTunes id/URL in podcast feed ([#1078](https://github.com/podlove/podlove-publisher/pull/1078))
* improve feed rendering: use XML generator for all tags with user input to guarantee valid feeds for all inputs
* add function to remove a transcript from an episode ([#1131](https://github.com/podlove/podlove-publisher/issues/1131))
* add Steady as donation service
* add template tag: `episode.post_title` ([#1136](https://github.com/podlove/podlove-publisher/issues/1136))
* add template tag: `service.type` (https://community.podlove.org/t/replacing-social-icons/2321)
* add default avatar to transcript preview
* fix: search logic ([#1072](https://github.com/podlove/podlove-publisher/issues/1072))
* fix: fetch Podlove News via https ([#1037](https://github.com/podlove/podlove-publisher/issues/1037))
* fix: don't send Publisher logs to system log when WP_DEBUG is on ([#1065](https://github.com/podlove/podlove-publisher/issues/1065))
* fix: ensure uploads for webvtt (transcripts) and gz (exports) are allowed
* fix: ensure contributors module is active when transcripts are used
* fix: ensure permissions in shownotes and transcripts APIs
* fix: don't count download requests with http range header of `bytes=0-0` ([#1135](https://github.com/podlove/podlove-publisher/issues/1135))
* update dependencies
* build releases with GitHub Actions (in favour of TravisCI)

----

Changes for previous versions can be found in the [`changelog.txt`](https://github.com/podlove/podlove-publisher/blob/master/changelog.txt).
