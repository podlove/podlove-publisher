=== Podlove Podcasting Plugin for WordPress ===
Contributors: eteubert
Donate link: http://flattr.com/thing/728463/Podlove-Podcasting-Plugin-for-WordPress
Tags: podcast, publishing
Requires at least: 3.0
Tested up to: 3.3.2
Stable tag: 1.0

Podcast plugin. Still in early stages of development. Don't use for production unless you know what you're doing.

== Description ==

"The Mac OS X, 10.0, of podcasting. The Podcasting Plugin for the next decade." — Tim Pritlove & map

Podlove Podcasting Plugin for WordPress is a full-features podcast publishing system — well, it will be at some point. It is still in early development, so please handle with care. Feel free to use it in the real world but don't be surprised if something breaks until we reach a stable release. We already try to not break stuff with updates but there is no guarantee.

Development of the plugin is an open process. The current version is available on github:

http://github.com/eteubert/podlove

Feel free to contribute and to fix errors or send improvements via github.

== Changelog ==

= 1.2.14-alpha =
* Enhancement: rename "media locations" to "episode assets" for clarity

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