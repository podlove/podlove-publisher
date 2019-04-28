=== Podlove Podcast Publisher ===
Contributors: eteubert, chemiker
Donate link: http://podlove.org/donations/
Tags: podlove, podcast, publishing, rss, feed, audio, mp3, m4a, player, webplayer, iTunes, radio
Requires at least: 4.4
Tested up to: 5.1.1
Requires PHP: 5.4
Stable tag: 2.8.0
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

= 2019-04-28 =

* rename backend js file to not be accidentally caught by content blockers
* performance: add index to downloads table
* performance: improve calculation of last months downloads
* fix: loading states in global analytics
* fix: restrict global analytics to last 30 days (instead of dynamic selector) because I can't guarantee acceptable performance with a dynamic date selector
* fix: transcripts in episode preview

= 2019-04-26 =

* fix: web player 4 when rendered as shortcode

= 2019-04-19 =

* fix: web player 4 config issue

= 2019-04-18 =

* fix(transcripts): detect if import file is not utf8
* merge changes from 2.7.x (stable)

= 2019-02-25 =

* fix: keep existing voice assignments when reparsing transcripts

= 2019-02-09 =

* transcripts: 
  * fix speaker avatars not appearing
  * various fixes and enhancements

= 2019-01-31 =

### Bug Fixes

* **player:** missing config filter parameter ([0fa377f](https://github.com/podlove/podlove-publisher/commit/0fa377f))
* **transcripts:** don't break when contributors are missing ([d6cf132](https://github.com/podlove/podlove-publisher/commit/d6cf132))
* **transcripts:** ensure vtt is allowed to upload ([f1f021e](https://github.com/podlove/podlove-publisher/commit/f1f021e))
* **transcripts:** speaker-voice assignment ([4f48c99](https://github.com/podlove/podlove-publisher/commit/4f48c99))

### Features

* **transcripts:** add "import from asset" button ([6cf3b39](https://github.com/podlove/podlove-publisher/commit/6cf3b39))
* **transcripts:** add whitespace after full stop in preview ([70a62f6](https://github.com/podlove/podlove-publisher/commit/70a62f6))
* **transcripts:** show voice default if contributor is not available ([fb40fc5](https://github.com/podlove/podlove-publisher/commit/fb40fc5))

= 2019-01-16 =

* (maybe) fix Gutenberg issues when creating a new episode

= 2019-01-14 =

**Bug Fixes**

* **slacknotes:** url re-fetching when changing dates ([dc8fa7c](https://github.com/podlove/podlove-publisher/commit/dc8fa7c))

= 2019-01-13 =

= 2.7.21 =

**Bug Fixes**

* **slacknotes:** avoid duplicate vue for-loop keys ([7578cdf](https://github.com/podlove/podlove-publisher/commit/7578cdf))
* **slacknotes:** date range filter ([2982f2b](https://github.com/podlove/podlove-publisher/commit/2982f2b))
* **slacknotes:** fix loading of datepicker component ([13ca12b](https://github.com/podlove/podlove-publisher/commit/13ca12b))
* **slacknotes:** follow redirects when resolving URLs ([5b39746](https://github.com/podlove/podlove-publisher/commit/5b39746))
* **slacknotes:** handle slack-resolved URLs in pipes format ([b08ec53](https://github.com/podlove/podlove-publisher/commit/b08ec53))
* **slacknotes:** hide link-fetch prompt while fetching ([f4e78e3](https://github.com/podlove/podlove-publisher/commit/f4e78e3))
* **slacknotes:** url re-fetching when changing dates ([344456d](https://github.com/podlove/podlove-publisher/commit/344456d))

**Features**

* **slacknotes:** add setting for link ordering ([c4c824e](https://github.com/podlove/podlove-publisher/commit/c4c824e))
* **slacknotes:** show link time ([53077b1](https://github.com/podlove/podlove-publisher/commit/53077b1))
* **slacknotes:** when resolving URLs, use effective URL ([974c7f8](https://github.com/podlove/podlove-publisher/commit/974c7f8))

= 2019-01-12 =

**Slacknotes**

This release is sponsored by [Lage der Nation](https://lagedernation.org).

The new "Slacknotes" module extracts links and their metadata from a Slack channel and generates HTML that can be used as show notes.
A short demo video is available [in the documentation](https://docs.podlove.org/podlove-publisher/guides/slacknotes.html).

**Other**

* the "Modules" screen has been redesigned
* updated JavaScript and CSS processing library and other dependencies

= 2018-12-15 =

* reimport transcript every time file is verified

= 2018-12-14 =

We are now compatible to the new WordPress 5.0 Gutenberg block editor. 
You can choose to use the new editor or stay with the classic editor for now by installing the classic editor plugin by WordPress.

* feed: do not include `<itunes:summary>` tag if it is empty (Apple Podcast requirement)
* adjustments for Gutenberg compatibility:
  * Shows metabox moved from sidebar to main area
  * remove broken form field autogrow behavior
  * fix contributors UI initialization

= 2018-11-29 =

* merge changes from public releases
* fix issue when importing a transcript more than once

= 2018-11-11 =

* apply new tab style from transcripts section to chapter marks section
* internal: when batch-enabling an asset, trigger podlove_media_file_content_has_changed action for all affected media file objects
* migration assistant: remove "lighter" font weights

= 2018-11-09 =

improve transcripts module

- show rendered transcript in episode screen
- improve "empty state"
- give the tab contents some border

= 2018-11-07 =

* automatically assign transcript voices if their webvtt identifier matches a contributor identifier
* merge changes from public releases

= 2018-10-26 =

Improvements in global analytics:

There's a "top episodes" widget now.

I am not sure if it works out but added a date picker as well. Still rough around the edges but you can pick a custom date range now for global analytics widgets.

= 2018-09-27 =

* player rendering fix

= 2018-09-23 =

* new shortcode `[podlove-transcript]` that displays the transcript of the current episode. `[podlove-transcript post_id="123"]` to display transcript of the given post.

merge 2.7.12 changes:

* use wp_enqueue_script instead of inline JS when calling PWP4, improving compatibility to other plugins ([#1000](https://github.com/podlove/podlove-publisher/issues/1000))
* uninstall: be more specific which options are deleted ([#997](https://github.com/podlove/podlove-publisher/issues/997))
* new filter `podlove_network_module_activate` to force-enable network module ([#995](https://github.com/podlove/podlove-publisher/issues/995))
* new social services: Mastodon, Fediverse, Friendica ([#987](https://github.com/podlove/podlove-publisher/issues/987), [#968](https://github.com/podlove/podlove-publisher/issues/968))
* fix related episodes disappearing when using post scheduling ([#980](https://github.com/podlove/podlove-publisher/issues/980))
* fix seasons error when there are no episodes ([#963](https://github.com/podlove/podlove-publisher/issues/963))
* related episodes: order by post date ([#947](https://github.com/podlove/podlove-publisher/issues/947))

= 2018-09-02 =

* new global chart: downloads per month

= 2018-08-31 =

* merge changes from master / stable release
* show some global charts over all time in analytics dashboard

= 2018-08-19 =

* add `group.start`, `group.end` template accessors
* fix transcripts: don't rely on contributor identifier internally

= 2018-08-17 =

* merge changes from master / stable release
* add template tag `contributor.avatar.html`; accepts same arguments as `image.html`

= 2018-08-10 =

* included all changes from the 2.7.x branch
* transcripts can now be exported as JSON grouped by speaker and a preliminary/inofficial XML format
* Template API groups by speaker as well. Use this example template as base:
* Analytics:
  * UI crash fix (Chrome)
  * add client location chart
  * remove weekday chart 

    <style type="text/css">
    .ts-speaker { font-weight: bold; }
    .ts-items { margin-left: 20px; }
    .ts-time { font-size: small; color: #999; }
    </style>
    
    {% for group in episode.transcript %}
      <div class="ts-group">
    
        {% if group.contributor %}
          <div class="ts-speaker">{{ group.contributor.name }}</div>
        {% endif %}
    
        <div class="ts-items">
        {% for line in group.items %}
          <span class="ts-time">{{ line.start }}&ndash;{{ line.end }}</span>
          <div class="ts-content">{{ line.content }}</div>
        {% endfor %}
        </div>
      </div>
    {% endfor %}

= 2018-05-23 =

* jobs dashboard: add button to abort running jobs

= 2018-05-04 =

**Preparation for GDPR/DSGVO**

If you are using Podlove Publisher Tracking/Analytics, an update to this version is highly recommended.

Tracking uses a `request_id` to be able to determine when two requests came from the same user and should be counted as one unique access. This request id used to be a hash of the original IP address and the user agent. This approach however is vulnerable to a brute force attack to get the IP address back from the hash. Here's what we are doing about that:

First, we anonymize the IP before generating the hash. So instead of using `171.23.11.209`, we use `171.23.11.0`.

Second, you need to deal with the existing `request_id`s. There is a new "DSGVO" section under "Tools" with a button that will rehash all existing `request_id`s with a randomly generated salt. That way it will become unfeasible to determine the original IP address but your analytics will stay the same.

In case you have a lot of downloads (let's say much more than 50.000), you may want to do this via command line because that will be _much_ quicker than via the tools section. You need [wp-cli](https://wp-cli.org/), then simply call `wp eval 'podlove_rehash_tracking_request_ids();'`. On a multisite, pass the blog id as a parameter: `wp eval 'podlove_rehash_tracking_request_ids(42);'`.

**Other**

* fix Podlove Subscribe Button language parameter
* fix `rel="self"` link in show feeds
* fix Podlove Subscribe Button not delivering show feeds
* templates: handle episode.show access when there is no show
* templates: allow episode filtering by show, for example: `{% for episode in podcast.episodes({show: "example"}) %}`

_2.7.4_

No changes, but the previous release is not delivered correctly by WordPress, so this is simply a re-release attempt to fix it.

= 2018-04-15 =

* allow file access even when it's not marked as "downloadable"

= 2018-04-04 =

* update webvtt parser

Add changes from 2.7 branch:

_2.7.3_

* fix: geo database updater
* update Podlove Web Player 2: remove Flash Fallback
* update Podlove Web Player 4

_2.7.2_

* fix: `itunes:image` tag in show feeds
* fix: "Debug Tracking" choosing wrong media files to check availability
* enhancement: "Debug Tracking" now suggests disabling SSL-peer-verification if URL cannot be resolved and https is used
* system report: include active plugins

_2.7.1_

* fix: PHP warning when the_title filter is called with only one parameter
* fix: handle colons in migration tool
* fix: PWP4 warning when using shortcode
* new service: letterboxd

= 2018-03-25 =

- when a tracking request does not work because the asset is configured as not downloadable, respond with 403 instead of 404

= 2018-03-24 =

Transcripts can now be rendered using the Template API. Here's an example template to get started:

    <style>
    .ts-line { margin-bottom: 5px; }
    .ts-line .time { font-family: monospace; }
    </style>
    
    {% for line in episode.transcript %}
     <div class="ts-line">
         <small>
         <span class="time">{{ line.start }}&ndash;{{ line.end }}</span>
         {% if line.contributor %}
           <strong>{{ line.contributor.name }}</strong>
         {% endif %}
         </small>
         <div>{{ line.content }}</div>
     </div>
    {% endfor %}

= 2018-03-23 =

- fix: no transcripts tab in player when there are no transcripts

= 2.8.0 prototype =

2.8.0 adds the "Transcripts" module. If you are using Podlove Web Player 4 and are have transcripts (or are willing to create them), give it a go. Currently webvtt is the only supported transcript format.

Once you enable the module, you can import transcripts in the episode screen below the chapter marks UI. You should also have the "Contributors" module enabled so you can assign "voices" in the transcript. 

----

Changes for previous versions can be found in the [`changelog.txt`](https://github.com/podlove/podlove-publisher/blob/master/changelog.txt).
