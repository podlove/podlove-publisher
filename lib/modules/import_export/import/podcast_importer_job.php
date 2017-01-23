<?php 
namespace Podlove\Modules\ImportExport\Import;

use Podlove\Jobs\JobTrait;

class PodcastImporterJob {
	use JobTrait;

	// path to import file
	private $file;

	// SimpleXML document of import file
	private $xml;

	public static function title() { return 'Podcast Importer'; }

	public static function description() { return 'Imports Podcast Settings'; }

	public function setup() {
		$this->hooks['init'] = [$this, 'init_job'];
	}

	public function init_job()
	{
		$this->job->state = [
			// '0' for 'to do', '1' for 'done'
			'actions' => [
				'episodes'           => 0,
				'options'            => 0,
				'filetypes'          => 0,
				'assets'             => 0,
				'feeds'              => 0,
				'mediafiles'         => 0,
				'tracking_area'      => 0,
				'tracking_areaname'  => 0,
				'tracking_useragent' => 0,
				'templates'          => 0,
				'other'              => 0,
				'migrations'         => 0
			]
		];
	}

	public function get_total_steps() {
		return count($this->job->state['actions']);
	}

	protected function do_step() {
		
		// fetch next action
		$actions_left = array_filter($this->job->state['actions'], function($x) { return $x < 1; });
		$next_action = array_keys($actions_left)[0];

		$importer = new \Podlove\Modules\ImportExport\Import\PodcastImporter(get_option('podlove_import_file'));

		switch ($next_action) {
			case 'episodes':
				$importer->importEpisodes();
				break;
			case 'options':
				$importer->importOptions();
				break;
			case 'filetypes':
				$importer->importFileTypes();
				break;
			case 'assets':
				$importer->importAssets();
				break;
			case 'feeds':
				$importer->importFeeds();
				break;
			case 'mediafiles':
				$importer->importMediaFiles();
				break;
			case 'tracking_area':
				$importer->importTrackingArea();
				break;
			case 'tracking_areaname':
				$importer->importTrackingAreaName();
				break;
			case 'tracking_useragent':
				$importer->importTrackingUserAgent();
				break;
			case 'templates':
				$importer->importTemplates();
				break;
			case 'other':
				$importer->importOther();
				break;
			case 'migrations':
				\Podlove\run_database_migrations();
				break;
		}

		// mark action as done
		$state = $this->job->state;
		$state['actions'][$next_action] = 1;
		$this->job->state = $state;

		return 1;
	}
}
