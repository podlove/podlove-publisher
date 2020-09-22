=== Podlove Podcast Publisher ===
Contributors: eteubert, chemiker
Donate link: http://podlove.org/donations/
Tags: podlove, podcast, publishing, rss, feed, audio, mp3, m4a, player, webplayer, iTunes, radio
Requires at least: 5.2
Tested up to: 5.5.1
Requires PHP: 7.0
Stable tag: 3.0.4
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

= 3.1.0 =

* improve: use OPAWG podcast user agent database in addition to Matomo database
* improve: detect plugins using older/incompatible versions of Twig. Display a warning on the site (instead of an error) and a detailed explanation on "Podlove > Support" screen.
* fix: editing/deleting shows ([#1077](https://github.com/podlove/podlove-publisher/issues/1077))

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
