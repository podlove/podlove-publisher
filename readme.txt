=== Podlove Podcast Publisher ===
Contributors: eteubert
Donate link: https://opencollective.com/podlove
Tags: podlove, podcast, publishing, rss, audio
Tested up to: 6.7.2
Stable tag: 4.2.7
Requires at least: 4.9.6
Requires PHP: 8.0
License: MIT

The one and only next generation podcast publishing system. Seriously. It's magical and sparkles a lot.

== Description ==

We started the Podlove Podcast Publisher project in 2012 because existing solutions were stuck in the past, complex and unwieldy. The Publisher helps you save time, worry less and provides a cutting edge listening experience for your audience.

Official Site: [podlove.org/podlove-podcast-publisher](https://podlove.org/podlove-podcast-publisher)

### Video Tutorial: Getting started with Podlove Publisher

[youtube http://www.youtube.com/watch?v=Hmrm-jUe6u4]

### Compatible RSS Feeds

The Publisher makes it easy to create highly expressive, efficient and super compatible podcast feeds with fine grained control over client behavior (e.g. GUID control to replace faulty episodes and for clients to reload) supporting all important meta data.

### Multi-Format Publishing

The Publisher also makes multi-format publishing - embracing all modern and legacy audio and video codecs - a snap. By adopting simple file name conventions, the plugin allows the podcaster to provide individual feeds for certain use cases or audiences without adding work for the podcaster during the publishing process.

### Optimized Web Player

The Publisher also comes integrated with the Podlove Web Player plugin and fully supports its advanced options including multiple audio (MP4 AAC, MP3, Vorbis, Opus) and video (MP4 H.264, WebM, Theora) format support for web browsers. This Web Player is fully HTML5 compatible and is ready for all touch based clients too.


### Metadata Galore

* **Chapter Marks:** The Publisher also makes it easy to publish chapter information in the player to make access to structured episodes even easier. Full support for linking directly to any part of your podcast on the web with instant playback included.
* **Contributors:** Bring your team and guests front and center. Manage contributors, including their names, avatars and web urls.
* **Transcripts:** WebVTT transcripts can be imported and even connected to your contributors. They are referenced in the RSS feed so they can be displayed by podcast apps.
* **Seasons:** Does your podcast have seasons? We got you covered with a dedicated "Seasons" module.
* **Related Episodes:** Manage and display related episodes on your website.

### Auphonic Integration

Auphonic is your all-in-one audio post production webtool to achieve a professional quality result. We provide a first class integration module for ease of use and best automation experience.

### Flexible Templates

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

= 4.2.7 =

- security: Improved handling of image files in the download cache to block malicious uploads.

Note: this should not affect normal image delivery, but please keep an eye out for any unexpected issues with cached images.

= 4.2.6 =

- fix: open redirection vulnerability

= 4.2.5 =

- fix: an episode that is assigned to a show can now be taken out of that show
- load more Auphonic productions to select from (50)

= 4.2.4 =

- fix: when upgrading YOAST SEO while the Publisher is active, permalinks do not break any more
- onboarding: show warning if application passwords are disabled
- include feeds in `GET podlove/v2/shows` result

= 4.2.3 =

- feat: add API route to list public feeds: `GET podlove/v2/feeds`
- security: remove unused code (which contained an XSS vulnerability)

= 4.2.2 =

Fix GUIDs for shows:

- generate a guid for all existing shows
- use the guid in the RSS feed, instead of the podcast guid
- new shows will get a new guid

= 4.2.1 =

- security: XSS vulnerability in podcast summary

= 4.2.0 =

This release introduces the all-new **Onboarding Assistant**, enabling you to
either setup a new podcast or move your existing podcast to Podlove with just
your RSS Feed URL.

This makes it much easier for potential podcasters to get started, as it makes
the initial setup process as simple and quick as possible, now only requiring
the podcaster to define what's really necessary about the podcast, without
needing to worry about technical details.

And for the first time, you can now move any of your existing podcasts to
Podlove Publisher. All you need is the RSS feed. Podlove Publisher finds all the
episode data and metadata like the audio file, title, description, chapters,
transcript and contributors and imports them automatically.

A huge Thank You goes to the [Prototype Fund](https://prototypefund.de/project/podlove-publisher-onboarding-import-assistant/)
for sponsoring the development of the Onboarding Assistant module.

**Other Changes**

- removes outdated "Migration Wizard"
- generate a guid for the podcast and use it in the RSS feed `podcast:guid` tag
- API changes:
  - episodes: allow filtering by `guid`
  - podcast: include the following fields in responses: guid, language, feeds
- fix: respect slashes in file slugs when urlencoding
- security: XSS vulnerability in feed name

= 4.1.25 =

* fix: Auphonic "Start Production" is working again

= 4.1.24 =

* security: XSS vulnerability in episode assets title
* feat: add API route to clear caches: `DELETE podlove/v2/tools/clear-caches`
* feat: add bluesky to social services

= 4.1.23 =

* new: Auphonic file uploads display their upload progress
* fix: image cache sometimes saved the resized files under a wrong filename
* fix: image cache is now more robust, redirecting to the original image url instead of failing when any processing step fails
* fix: oembed xml generation with escaping
* fix: various PHP warnings/notices when trying to access nonexistent array keys

= 4.1.22 =

* fix: empty vtt transcripts (when no voices were assigned to contributors)
* fix: error when unsetting a transcript voice assignment
* fix: PHP warning while using a deprecated podcast category

= 4.1.21 =

* fix: encode tracking urls and their redirected urls

= 4.1.20 =

* fix: when using the "Post Thumbnail" setting for episode images, the chosen
image is now immediately shown in the "Episode Description" box and it is sent
to Auphonic when saving a production.
* fix: Auphonic status polling now only gets called when appropriate, instead of on every page load
* fix: improve WordPress 6.7 compatibility (related to translation api)

= 4.1.19 =

* fix some templates by allowing `service.name` (but it's deprecated, so you should change your templates to use `service.title` instead)

= 4.1.18 =

* fix `episode.transcript` accessor

= 4.1.17 =

* fix some templates that broke with 4.1.16

= 4.1.16 =

* security: use Twig in Sandbox mode
* change: reenable recently disabled Twig Template filters "filter", "map" and "reduce"
* fix: plugin crash on setup when default WordPress roles do not exist

= 4.1.15 =

* security: disable unsafe Twig Template filters "filter", "map" and "reduce"

= 4.1.14 =

* security: add nonces to template actions
* security: use output escaping for episode slug

= 4.1.13 =

* add transcripts to import/export
* add confirmation button on modal to input episode image url
* fix: selecting an episode image updates the preview

= 4.1.12 =

* new: when selecting a file for Auphonic, its name is suggested to be used as episode slug
* fix: PHP deprecation warnings

= 4.1.11 =

* new: support `podcast:license` tag in RSS feed
* fix: imports correct chapter times from Auphonic (when using features that affect the episode duration)
* maintenance: update various javascript dependencies

= 4.1.10 =

* fix: bug introduced in latest patch that created duplicate database entries for media files

= 4.1.9 =

Various fixes regarding adjusted media file handling introduced in version 4.0.11:

- dashboard shows a grey bar again when the file is inactive
- RSS feed correctly skips items with inactive files again

= 4.1.8 =

* changes in an episode immediately affect the RSS feed

There are various caches in place to ensure efficient delivery of the RSS feed
to podcast clients. However it can be hard to guess how long it will take for a
change to appear in the feed and podcatchers. Now, any change to the episode
metadata or enabling/disabling an asset immediately clears the cache for that
feed item, resulting in the change to be visible in the RSS feed immediately.

* transcripts: contributors in voices selection are sorted alphabetically
* fix: episode license and explicit value are not emptied when saving the episode
* maintenance: fix various notices from WordPress Plugin Check tool
* maintenance: js dependency updates


= 4.1.7 =

* fix `itunes:explicit` RSS tag. It now contains the valid values "true" or "false".
* fix typo in API: `explicit` field was mistyped as `expicit`

= 4.1.6 =

* Shows Settings: sort Auphonic presets alphabetically

= 4.1.5 =

* Hotfix: Creating Auphonic productions works again

= 4.1.4 =

* Auphonic: reduce preset cache to 10 seconds so manual refreshing is not necessary (and remove broken refresh button on module page)
* Auphonic: select configured show preset when selecting a show
* Auphonic: fix status updates when opening an episode with an already running production
* performance: don't try to fetch file headers twice for unreachable URLs
* performance: faster saving of slug changes

= 4.1.3 =

* Shows selection in the episode was rewritten for the new frontend stack. It is now compatible with the Automatic Numbering module again.

= 4.1.2 =

* transcripts: include json link in RSS again when "Publisher Generated" is selected
* transcripts: polish timestamp rendering in preview (shorter, monospaced, right aligned, unselectable)
* chapters: fix "unknown" durations

= 4.1.1 =

* security: add nonces to tools actions

= 4.1.0 =

**Feature: Better control over transcripts in RSS feed**

There is a new feature under `Podlove > Podcast Feeds` called "Episode
Transcripts" to control how episode transcripts should be referenced in your RSS
feed. The default is "Publisher Generated vtt", which is nearly the same
behavior as before (In previous versions, three variants were referenced: vtt,
json and srt. The json and srt URLs still work but they are not referenced in
the RSS feed any more as they are not neccessary and removing them reduces feed
size). There is a dedicated "Do not include in feed" option, in case you do not
want the transcripts in your RSS feed.

Finally, you may prefer to host your transcript assets externally, just like
your audio files. If you have configured a transcript asset, you can now select
it in this setting. Then the RSS feed will reference this external file
directly.

**Auphonic improvements**

- add button to always access the import screen
- add button to delete a track
- show status in production selection
- rearrange / polish various some button and information positions

**Other**

- add: capability "podlove_manage_contributors" for contributors settings screens
- fix: sometimes missing voices in Podlove Web Player transcripts
- fix: sometimes an enabled asset is disabled a few moments later
- fix: show files as "not found" when they become unreachable
- transcripts: rename "delete" action to "clear"
- transcripts: show timestamps in editor preview
- upgrade heroicons (icon library) from v1 to v2

----

Changes for previous versions can be found in the [`changelog.txt`](https://github.com/podlove/podlove-publisher/blob/master/changelog.txt).

== Upgrade Notice ==

= 4.0.0 =

An all-new episode creation experience. Requires PHP 8.0 and above.
