<?php 
namespace Podlove\Modules\Seasons;

use Podlove\Model\Episode;
use Podlove\Modules\Seasons\Model\Season;

class Seasons extends \Podlove\Modules\Base {

	protected $module_name = 'Seasons';
	protected $module_description = 'Group your episodes into seasons.';
	protected $module_group = 'metadata';

	public function load() {
		
		// module lifecycle
		add_action('podlove_module_was_activated_seasons', [$this, 'was_activated']);

		// register settings page
		add_action('podlove_register_settings_pages', function($handle) {
			new \Podlove\Modules\Seasons\Settings\Settings($handle);
		});

		add_action('admin_print_styles', [$this, 'scripts_and_styles']);

		add_action('podlove_xml_export', array($this, 'expandExportFile'));
		add_filter('podlove_import_jobs', array($this, 'expandImport'));

		add_filter( "set-screen-option", function($status, $option, $value) {
			if ($option == 'podlove_seasons_per_page')
				return $value;
			
			return $status;
		}, 10, 3 );

		add_action('podlove_append_to_feed_entry', [$this, 'add_season_number_to_feed'], 10, 4);

		add_filter('podlove_episode_form_data', [$this, 'add_season_number_to_episode_form'], 10, 2);
		add_filter('podlove_generated_post_title', [$this, 'set_season_in_post_title'], 10, 2);
		add_filter('podlove_js_data_for_post_title', [$this, 'set_season_in_post_title_js'], 10, 2);

		\Podlove\Template\Podcast::add_accessor(
			'seasons', ['\Podlove\Modules\Seasons\TemplateExtensions', 'accessorPodcastSeasons'], 3
		);

		\Podlove\Template\Episode::add_accessor(
			'season', ['\Podlove\Modules\Seasons\TemplateExtensions', 'accessorEpisodeSeason'], 4
		);

	}

	public function was_activated( $module_name ) {
		Season::build();
	}

	public function scripts_and_styles() {

		$is_seasons_settings_page = filter_input(INPUT_GET, 'page') === 'podlove_seasons_settings';

		if (!$is_seasons_settings_page && !\Podlove\is_episode_edit_screen())
			return;

		wp_enqueue_style(
			'podlove_seasons_admin_style',
			\Podlove\PLUGIN_URL . '/lib/modules/seasons/css/admin.css',
			false,
			\Podlove\get_plugin_header('Version')
		);

		wp_enqueue_script(
			'podlove_seasons_admin_script',
			$this->get_module_url() . '/js/admin.js',
			['jquery'],
			\Podlove\get_plugin_header('Version')
		);
	}


	public function add_season_number_to_feed($podcast, $episode, $feed, $format) {
		$season = Season::for_episode($episode);

		if (!$season)
			return;

		$number = $season->number();

		echo sprintf("\n\t\t<itunes:season>%d</itunes:season>", $number);
	}

	/**
	 * Expands "Import/Export" module: export logic
	 */
	public function expandExportFile(\SimpleXMLElement $xml) {
		\Podlove\Modules\ImportExport\Export\PodcastExporter::exportTable($xml, 'seasons', 'season', '\Podlove\Modules\Seasons\Model\Season');
	}

	/**
	 * Expands "Import/Export" module: import logic
	 */
	public function expandImport($xml)
	{
		$jobs[] = '\Podlove\Modules\Seasons\PodcastImportSeasonsJob';
		return $jobs;
	}

	public function add_season_number_to_episode_form($form_data, $episode)
	{
		$season = Season::for_episode($episode);

		if (!$season)
			return $form_data;

		$title  = __('Season', 'podlove-podcasting-plugin-for-wordpress') . ' ' . $season->number();

		$entry = array(
			'type' => 'callback',
			'key'  => 'season',
			'options' => array(
				'callback' => function () use ($title) {
					?>
					<span><?php echo $title ?></span>
					<?php
				}
			),
			'position' => 1250
		);

		$form_data[] = $entry;

		return $form_data;
	}

	public function set_season_in_post_title($title, $episode)
	{
		return str_replace(
			'%season_number%', 
			self::get_printable_season_number($episode), 
			$title
		);
	}

	function set_season_in_post_title_js($data, $post_id)
	{
		$episode = Episode::find_one_by_property('post_id', $post_id);
		$data['season_number'] = self::get_printable_season_number($episode);

		return $data;
	}

	public static function get_printable_season_number($episode) {
		if ($episode && ($season = Season::for_episode($episode))) {
			return $season->number();
		} else {
			return '??';
		}		
	}
}
