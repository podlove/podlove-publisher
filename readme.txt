=== Podlove Podcast Publisher ===
Contributors: eteubert
Donate link: https://opencollective.com/podlove
Tags: podlove, podcast, publishing, rss, feed, audio, mp3, m4a, player, webplayer, iTunes, radio
Tested up to: 6.4.2
Stable tag: 4.0.11
Requires at least: 4.9.6
Requires PHP: 8.0
License: MIT

The one and only next generation podcast publishing system. Seriously. It's magical and sparkles a lot.

== Description ==

We built the Podlove Podcast Publisher because existing solutions are stuck in the past, complex and unwieldy. The Publisher helps you save time, worry less and provides a cutting edge listening experience for your audience.

Official Site: [podlove.org/podlove-podcast-publisher](https://podlove.org/podlove-podcast-publisher)

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

Requires PHP 8.0+

== Frequently Asked Questions ==

### Is Podlove Podcast Publisher free?

Yes! The core features of Podlove Podcast Publisher are and always will be free.

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
Episode title in API now follows the same rules as in RSS feed. There's a new field 'title_clean' for accessing the specifically set plain episode title, but that might be null in some cases, so it's better to default the 'title' attribute to the usual rules.
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

= 4.0.12 =

**Security**

- fix SSRF vulnerability in Slacknotes module
- add missing capability check and nonce validation to importer functions
- add missing capability check and nonce validation to exporter functions

= 4.0.11 =

- new: show admin notice when a database migration fails
- fix bug where tracking data could be lost by disabling a media file checkbox
- fix bug where imported Hindenburg chapters were not sorted by time
- fix build script (correctly delete all vendor prefixed dependencies)
- fix deprecation warnings ([#1431](https://github.com/podlove/podlove-publisher/pull/1431), [#1430](https://github.com/podlove/podlove-publisher/pull/1430))
- update js dependencies
- update help text for missing curl module

= 4.0.10 =

- fix security issues (XSS)
- do not unnecessarily flush rewrite rules ([Issue#1432](https://github.com/podlove/podlove-publisher/issues/1432))
- fix link to Slacknotes and Subscribe Button documentation
- fix psr library not removed after prefixing ([Issue#1421](https://github.com/podlove/podlove-publisher/issues/1421))

= 4.0.9 =

**Enhancements**

- trim whitespaces from beginning and end of file slug
- soundbite: change placeholder to HH:MM:SS to clarify format

**Bugfixes**

- fix division by zero in analytics
- fix default contributors missing position attribute
- fix Auphonic chapter timestamp import
- fix page reload when clicking chapter upload button

= 4.0.8 =

**Bugfixes**

- fix broken analytics episode screen

= 4.0.7 =

**Bugfixes**

- fix media verification not saving
- fix shownotes unfurling
- avoid failure during database migration

**Misc**

- update/cleanup various js dependencies

= 4.0.6 =

**Bugfixes**

- Auphonic: saving production not working when slug is not set (bug introduced in 4.0.5)

= 4.0.5 =

**Bugfixes**


- Auphonic: restore previous behaviour:
  - automatically fill in file slug, validate media files and fill in duration when production finishes
  - use slug as "output_basename" if it is set

**Misc**

- cleanup legacy js app (dependency updates, deletion of unused code etc.)

= 4.0.3 / 4.0.4 =

**Enhancements**

- Auphonic: sort presets alphabetically
- Contributors: make better use of available space

**Bugfixes**

- episode metadata not saving reliably for some people
- Auphonic: fix chapter time import
- WordPress File Upload: display slug input (should be filled automatically but does not seem to work reliably)

= 4.0.2 =

**Bugfixes**

- Auphonic: Chapters can be imported from production metadata
- Contributors: Add support for Gravatar and default contributor image on edit screens
- Dashboard: Asset Validation / Detection is working again [#1396](https://github.com/podlove/podlove-publisher/issues/1396)
- Automatic Numbering: error when selecting a show

= 4.0.1 =

**Enhancements**

- Auphonic: autosave before "Start Production" so it is not required to explicitly save before starting

**Bugfixes**

- Auphonic: open productions with missing algorithm information
- Templates: fix broken core templates `downloads-select` and `related-episodes-list`
- Classic Editor: display Episode Title Placeholder based on Blog Post Title

= 4.0.0 =

Podlove Publisher 4.0 is here, bringing a spring-clean (in November!) of the episode page. We tore up the foundation to bring you an all-new user interface.

**Warning:** PHP 8.0 and above is now required!

**Highlights**

- **Episode Form** User Interface is modernised and auto-saves, so no work is ever lost.
- **Auphonic module** includes Multitrack Support.
- **New Contributors** can be added without leaving the episode page.
- **Chapters** support images.
- **REST API V2** is now, including many more endpoints. See the [API documentation](https://docs.podlove.org/podlove-publisher/api) for all the details.


**Tidbits**

- file “slug” field is prefixed with the media location so it’s more obvious what it is used for
- episode duration is always auto-detected and not an input field any more
- Auphonic Preset can be selected directly in the episode and does not rely on global module setting any more
- removed module “Twitter Card Integration” (RIP). By the way, if you want to follow us on social media, find us here: https://fosstodon.org/@podlove
- fix various PHP notices and warnings to be PHP 8.0+ compatible

----

Changes for previous versions can be found in the [`changelog.txt`](https://github.com/podlove/podlove-publisher/blob/master/changelog.txt).

== Upgrade Notice ==

= 4.0.0 =

An all-new episode creation experience. Requires PHP 8.0 and above.
