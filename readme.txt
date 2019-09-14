=== Podlove Podcast Publisher ===
Contributors: eteubert, chemiker
Donate link: http://podlove.org/donations/
Tags: podlove, podcast, publishing, rss, feed, audio, mp3, m4a, player, webplayer, iTunes, radio
Requires at least: 4.4
Tested up to: 5.2.2
Requires PHP: 5.4
Stable tag: 2.9.4
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

= 2.9.4 =

* fix: error on "file types" settings page

**IAB Conformity**

When it comes to tracking download intents, Podlove Publisher was always close to IAB recommendations, with one exception: the time window in which two requests count as two. Podlove Publisher deduplicates by hour, IAB recommends a day.

There is a new setting in `Podlove > Expert Settings > Tracking`: "Deduplication Window". It enables you to change the window to "day". This is an opt-in setting, the default will continue to be hourly.

See also: [docs.podlove.org: IAB Conformity](https://docs.podlove.org/podlove-publisher/guides/download-analytics.html#iab-conformity)

This feature is sponsored by [Lage der Nation](https://lagedernation.org).

= 2.9.3 =

* add quick edit for episode number [#1096](https://github.com/podlove/podlove-publisher/pull/1069)
* fix settings tab issues when using a language in WordPress other than english ([e613e99](https://github.com/podlove/podlove-publisher/commit/e613e99bb4f07bb88234146567e76d21ce06f5ff))
* fix issue with category search / pages
* fix auphonic module issue in Gutenberg editor

= 2.9.2 =

* update Podlove Web Player (fixes issue when sharing/embedding the player)
* fix PHP notices [#1066](https://github.com/podlove/podlove-publisher/issues/1066) [#1064](https://github.com/podlove/podlove-publisher/issues/1064)

= 2.9.1 =

* fix web player sharing when using CDN player
* fix duplicating posts: create new guid; do not copy analytics [#1048](https://github.com/podlove/podlove-publisher/issues/1048)

= 2.9.0 =

**New Apple iTunes Categories**

Apple updated their list of available iTunes categories. 
Please check in `Podlove > Podcast Settings > Directory > iTunes Category` if you need or want to update your category.
In case your previously selected category does not exist any more, a warning is shown.

Only one category is selectable now (instead of previously 3) to conform with iTunes specifications.

**Download tracking with Google Analytics**

Set your Google Analytics Tracking ID in Podlove > Expert Settings > Tracking. 
Then every download intent will be forwarded to Google Analytics.

[#1058](https://github.com/podlove/podlove-publisher/pull/1058)

**Other**

* fix: check if podlovePlayer function is available before calling it [#1060](https://github.com/podlove/podlove-publisher/pull/1060)

= 2.8.10 =

* update Podlove Web Player 4 to latest version

= 2.8.9 =

* update Podlove Web Player 4 to latest version
* remove PHP dependency leth/ip-address

= 2.8.8 =

* update Podlove Web Player 4 to latest version

= 2.8.7 =

* update Podlove Web Player 4 to latest version
* add player setting to either use the podcast language or user's browser language for web player interface ([#1008](https://github.com/podlove/podlove-publisher/pull/1008))
* fix [#1047 Use of PHP 5.6 feature in Shows module](https://github.com/podlove/podlove-publisher/issues/1047)
* report duplicate guids in system report

= 2.8.0 =

**Transcripts**

“Transcripts” is the new module to manage transcripts, show them on your site and in the web player. You can import them from webvtt files. If you are already using the Podlove Publisher contributors, you can assign people to the voices inside the webvtt. Then you even get avatars automatically in your transcripts.

See [https://forschergeist.de/podcast/fg066-klimaneutralitaet/](https://forschergeist.de/podcast/fg066-klimaneutralitaet/) for an example episode with transcripts in the web player.

**Transcripts: Shortcode**

The shortcode `[podlove-transcript]` displays a pretty html version of the transcript for your website. 

**Transcripts: Twig Template Support**

Of course there is a fully featured template API for transcripts as well. For example:

{% for group in episode.transcript %}
    <div class="ts-group">

        <div class="ts-speaker-avatar">
            {{ group.contributor.image.html({width: 50}) }}
        </div>

        <div class="ts-text">
            <div class="ts-speaker">
                {{ group.contributor.name }}
            </div>

            <div class="ts-content">
                {% for line in group.items %}
                <span class="ts-line">{{ line.content }}</span>
                {% endfor %}
            </div>
        </div>
        
    </div>
{% endfor %}

See [https://docs.podlove.org/podlove-publisher/reference/template-tags.html](https://docs.podlove.org/podlove-publisher/reference/template-tags.html "documentation") for all details.

**Global Podcast Analytics**

The following metrics are now available for the whole podcast:

- downloads per month
- top episodes
- episode asset
- podcast client
- operating system
- download source

**Raw Analytics**

I wouldn’t call this an Analytics API but since it exists to power the analytics screen, I might as well document it. The following endpoints return results in CSV format for easy processing or import to spreadsheets.

Here is an example call that returns the number of downloads in March 2019:

	https://your.domain/wp-admin/admin-ajax.php?action=podlove-analytics-global-downloads-per-month&date_from=2019-03-01T00:00:00.000Z&date_to=2019-03-31T23:59:59.999Z

All requests take the same three parameters:

- `action` defines what data you want
- `date_from` is the start date in ISO 8601
- `date_end` is the end date in ISO 8601

Available actions are:

- podlove-analytics-global-downloads-per-month
- podlove-analytics-global-top-episodes
- podlove-analytics-global-assets
- podlove-analytics-global-clients
- podlove-analytics-global-systems
- podlove-analytics-global-sources

You need to be logged in with admin permissions for the requests to work.

Disclaimer: Depending on the popularity of your podcast and chosen date range, the requests may take a long time to respond, or even fail if the calculation takes longer than the timeout defined in your web server.

**Other**

- background jobs: add button to abort job
- new tab style for chapter marks section
- Podlove Web Player 4 fallback for old browsers and disabled JavaScript

----

Changes for previous versions can be found in the [`changelog.txt`](https://github.com/podlove/podlove-publisher/blob/master/changelog.txt).
