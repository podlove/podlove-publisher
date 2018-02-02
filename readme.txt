=== Podlove Podcast Publisher ===
Contributors: eteubert, chemiker
Donate link: http://podlove.org/donations/
Tags: podlove, podcast, publishing, rss, feed, audio, mp3, m4a, player, webplayer, iTunes, radio
Requires at least: 4.4
Tested up to: 4.7.5
Requires PHP: 5.4
Requires at least: 3.5
Tested up to: 4.8.0
Stable tag: trunk
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

### Where can I host my podcast files?

Any storage where you have control over the file naming is compatible with Podlove Podcast Publisher. You can manage files using a simple FTP/sFTP or use services like Amazon S3.

### Where can I ask questions and get support?

Free support where questions are answered by the community is available in the [Podlove Community Forum](http://community.podlove.org/). There is a German community in the [Sendegate](https://sendegate.de/). [Professional Support](http://publisher.podlove.org/support) by the plugin developer is also available.

### How can I help the project?

The continued success of Open Source project relies on the community. There are many ways you can help:

- If you enjoy the plugin, please [leave a review](https://wordpress.org/support/plugin/podlove-podcasting-plugin-for-wordpress/reviews/#new-post).
- You can answer questions of other fellow podcasters in the [Podlove Community](https://community.podlove.org/).
- You can buy [Support](https://publisher.podlove.org/support/) to financially support the project.

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

= 2018-02-02 =

* add Liberapay as donation service

= 2018-02-01 =

Fix various issues in the download table display. Until now, new downloads were calculated hourly, which provides a good estimate but often not exact numbers. The calculation could also get stuck, leading to missing data display.

From now on, the estimates are still calculated hourly but additionally _a full, precise aggregation is done once a day_, which should lead to more consistent numbers overall.

= 2018-01-15 =

* episode page: add option to display episode number

= 2.7.0 =

**New Module: Shows**

With shows you can offer feeds to subtopics of your podcast. Here's how it works: You create a show and define show meta, similar to a podcast: title, slug, subtitle, summary, image and language. These fields override your podcast settings. All other settings are the same as your podcast.

For each episode, you decide which show it's in. Each show has its own set of feeds that listeners can subscribe to. The main feed remains unchanged, containing all episodes from all shows.

The Podlove Subscribe Button can be configured to subscribe to a show by referencing the show slug. Use the shortcode `[podlove-subscribe-button show="show-slug"]` or the template tag `{ podcast.subscribeButton({show: 'show-slug'}) }}`.

We do not recommend using Shows and Seasons at the same time.

**Updated Metadata for Podcast/Episode/Seasons according to iOS11 Specification**

Apple announced an [updated specification for feed elements](http://podcasts.apple.com/resources/spec/ApplePodcastsSpecUpdatesiOS11.pdf). These changes enable the Apple Podcasts app to present podcasts in a better way. But since these feed extensions are readable by any podcast client, we expect others to take advantage of these new fields soon. Here is how we implemented the specification:

- The podcast has a new "type" field where you can select between "episodic" and "serial", which may affect the order of episodes. The field `<itunes:type>episodic</itunes:type>` appears in the feed.
- Episodes have a new "title" field. It defaults to the episode post title but can be set separately now, allowing you to define different titles for the website and podcast clients. The field `<itunes:title>Interview with Somebody Infamous</itunes:title>` will appear in the feed.
- Episodes have a new "type" field where you can select between "full" (default), "trailer" and "bonus". This won't have any effect in the Publisher but may be used by podcast clients. The field `<itunes:episodeType>full</itunes:episodeType>` appears in the feed.
- Episodes have a new "number" field. If used, `<itunes:episode>42</itunes:episode>` will appear in the feed.
- Episodes in seasons will have an `<itunes:season>2</itunes:season>` field in the feed automatically.

We decided to complement these changes by introducing a podcast mnemonic/abbreviation field. Now we can autogenerate blog episode titles, based on the episode number and title, if you like. The mnemonic can be set in podcast settings. The setting to autogenerate blog episode titles is an expert setting in the "Website" section.

To help existing podcasts to conform to these new fields we made a "Title Migration" module which will greet you with a notice once you update the Publisher. It will try to extract episode numbers and titles from your existing titles, saving you time and effort updating each episode one by one.

**Template API Changes**

- `episode.title` now returns the new episode title field, if it is set, but has a fallback to the post title. If you want a specific version, use `episode.title.clean` or `episode.title.blog`.
- the post title of an episode can still be accessed via `episode.post.post_title`
- new accessor: `episode.number`
- new accessor: `episode.type`
- new accessor: `podcast.mnemonic`
- new accessor: `podcast.type`
- new accessor: `season.mnemonic`

**Podlove Web Player 4**

The Shortcode `[podlove-web-player]` accepts several parameters, increasing its versatility.

With `post_id` you can embed episodes on any page, for example `[podlove-web-player post_id="1234"]`.

Every [config parameter available](http://docs.podlove.org/podlove-web-player/config.html) can be overridden using shortcode attributes. The only difference from the linked documentation page is the notation. For nested configs like `show.title` use underscores (`_`) instead. For example, display a green player with custom title like this: `[podlove-web-player show_title="Special Title" theme_main="#00ff00"]`

You can now also display a player with _live content_ like this: `[podlove-web-player mode="live" audio_0_url="http://mp3.theradio.cc/" audio_0_mimeType="audio/mp3" title="Livestream" link="https://theradio.cc"]`

Podlove Web Player 4 is the new default player.

**Other**

* analytics: show download totals for last 24 hours and last 7 days in overview
* Podigee Player: add support for transcripts
    - create a Podigee Transcript asset
    - set this asset in Expert Settings > Web Player
    - See https://cdn.podigee.com/ppp/samples/transcript.txt for an example transcript
* Podlove Web Player 4: support contributors
* fix quotes in contributor fields
* fix WordPress conditionals in episode archives
* fix deleting related episodes ([#907](https://github.com/podlove/podlove-publisher/issues/907))
* fix network admin bar now does not include broken links if Publisher is not activated network-wide ([#933](https://github.com/podlove/podlove-publisher/issues/933))
* fix import getting stuck issue ([#910](https://github.com/podlove/podlove-publisher/issues/910))
* Bitlove module: remove all frontend functionality because it has been dysfunctional for a long time 
* fix Auphonic module showing wrong status message after file upload
* fix Audacity chapter import when times contain commas
* fix email notification issue where not emails were sent ([#938](https://github.com/podlove/podlove-publisher/issues/938))
* fix feed redirect issue for HTTP/1.0 clients
* fix network module: only activate when the plugin is activated network-wide, not when the plugin in active within a multisite
* fix calculation of contribution counts
* enhance email error reporting
* enhance open graph module: detects WP SEO plugin and does not output any tags to avoid conflicts
* social services: add SlideShare
* show warning if upload directory is not fully qualified
* remove download section from default template (because it is included in PWP4)
* image cache: instead of returning invalid URLs with 0 width and 0 height when something goes wrong, return the source URL instead

----

Changes for previous versions can be found in the [`changelog.txt`](https://github.com/podlove/podlove-publisher/blob/master/changelog.txt).
