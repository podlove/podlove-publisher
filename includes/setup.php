<?php 
/**
 * Plugin Setup
 */

use Podlove\Model;

function podlove_setup_database_tables() {
	Model\Feed::build();
	Model\FileType::build();
	Model\EpisodeAsset::build();
	Model\MediaFile::build();
	Model\Episode::build();
	Model\Template::build();
	Model\DownloadIntent::build();
	Model\DownloadIntentClean::build();
	Model\UserAgent::build();
	Model\GeoArea::build();
	Model\GeoAreaName::build();
}

function podlove_setup_file_types() {

	if (Model\FileType::has_entries())
		return;

	$default_types = [
		['name' => 'MP3 Audio',              'type' => 'audio',    'mime_type' => 'audio/mpeg',  'extension' => 'mp3'],
		['name' => 'BitTorrent (MP3 Audio)', 'type' => 'audio',    'mime_type' => 'application/x-bittorrent',  'extension' => 'mp3.torrent'],
		['name' => 'MPEG-1 Video',           'type' => 'video',    'mime_type' => 'video/mpeg',  'extension' => 'mpg'],
		['name' => 'MPEG-4 AAC Audio',       'type' => 'audio',    'mime_type' => 'audio/mp4',   'extension' => 'm4a'],
		['name' => 'MPEG-4 ALAC Audio',      'type' => 'audio',    'mime_type' => 'audio/mp4',   'extension' => 'm4a'],
		['name' => 'MPEG-4 Video',           'type' => 'video',    'mime_type' => 'video/mp4',   'extension' => 'mp4'],
		['name' => 'M4V Video (Apple)',      'type' => 'video',    'mime_type' => 'video/x-m4v', 'extension' => 'm4v'],
		['name' => 'Ogg Vorbis Audio',       'type' => 'audio',    'mime_type' => 'audio/ogg',   'extension' => 'oga'],
		['name' => 'Ogg Vorbis Audio',       'type' => 'audio',    'mime_type' => 'audio/ogg',   'extension' => 'ogg'],
		['name' => 'Ogg Theora Video',       'type' => 'video',    'mime_type' => 'video/ogg',   'extension' => 'ogv'],
		['name' => 'WebM Audio',             'type' => 'audio',    'mime_type' => 'audio/webm',  'extension' => 'webm'],
		['name' => 'WebM Video',             'type' => 'video',    'mime_type' => 'video/webm',  'extension' => 'webm'],
		['name' => 'FLAC Audio',             'type' => 'audio',    'mime_type' => 'audio/flac',  'extension' => 'flac'],
		['name' => 'Opus Audio',             'type' => 'audio',    'mime_type' => 'audio/ogg;codecs=opus',  'extension' => 'opus'],
		['name' => 'Matroska Audio',         'type' => 'audio',    'mime_type' => 'audio/x-matroska',  'extension' => 'mka'],
		['name' => 'Matroska Video',         'type' => 'video',    'mime_type' => 'video/x-matroska',  'extension' => 'mkv'],
		['name' => 'PDF Document',           'type' => 'ebook',    'mime_type' => 'application/pdf',  'extension' => 'pdf'],
		['name' => 'ePub Document',          'type' => 'ebook',    'mime_type' => 'application/epub+zip',  'extension' => 'epub'],
		['name' => 'PNG Image',              'type' => 'image',    'mime_type' => 'image/png',   'extension' => 'png'],
		['name' => 'JPEG Image',             'type' => 'image',    'mime_type' => 'image/jpeg',  'extension' => 'jpg'],
		['name' => 'mp4chaps Chapter File',  'type' => 'chapters', 'mime_type' => 'text/plain',  'extension' => 'chapters.txt'],
		['name' => 'Podlove Simple Chapters','type' => 'chapters', 'mime_type' => 'application/xml',  'extension' => 'psc'],
		['name' => 'Subrip Captions',        'type' => 'captions', 'mime_type' => 'application/x-subrip',  'extension' => 'srt'],
		['name' => 'WebVTT Captions',        'type' => 'captions', 'mime_type' => 'text/vtt',  'extension' => 'vtt'],
		['name' => 'Auphonic Production Description', 'type' => 'metadata', 'mime_type' => 'application/json',  'extension' => 'json'],
	];
	
	foreach ($default_types as $file_type) {
		$f = new Model\FileType;
		foreach ($file_type as $key => $value) {
			$f->{$key} = $value;
		}
		$f->save();
	}
}

function podlove_setup_podcast() {
	$podcast = Model\Podcast::get();
	if (!$podcast->limit_items) {
		$podcast->limit_items = Model\Feed::ITEMS_NO_LIMIT;
	}
	$podcast->save();
}

function podlove_setup_modules() {
	
	// required for all module hooks to fire correctly
	add_option('podlove_active_modules', []);
	
	// set default modules
	$default_modules = [
		'logging',
		'podlove_web_player',
		'open_graph',
		'asset_validation',
		'oembed',
		'feed_validation',
		'import_export',
		'subscribe_button'
	];

	foreach ($default_modules as $module) {
		\Podlove\Modules\Base::activate($module);
	}
}

function podlove_setup_expert_settings() {

	if (get_option('podlove', []) !== []) 
		return;

	update_option('podlove', [
		'merge_episodes'         => 'on',
		'hide_wp_feed_discovery' => 'off',
		'use_post_permastruct'   => 'on',
		'episode_archive'        => 'on',
		'episode_archive_slug'   => '/podcast/',
		'custom_episode_slug'    => '/podcast/%podcast%/'
	]);
}

function podlove_setup_default_template() {

	$template = Model\Template::find_one_by_property('title', 'default');

	if ($template)
		return;

	// set default template
	$template = new Model\Template;
	$template->title = 'default';
	$template->content = <<<EOT
{% if not is_feed() %}

	{# display web player for episode #}
	{{ episode.player }}
	
	{# display download menu for episode #}
	{% include "@core/shortcode/downloads-select.twig" %}

{% endif %}
EOT;
	$template->save();

	$assignment = Model\TemplateAssignment::get_instance();
	$assignment->top = $template->id;
	$assignment->save();
}
