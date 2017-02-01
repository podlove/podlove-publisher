<?php 
namespace Podlove\Modules\ImportExport\Import;

use Podlove\Jobs\JobTrait;
use Podlove\Model;
use Podlove\Log;

class PodcastImportAssetsJob {
	use JobTrait,
	    PodcastImportJobTrait,
	    PodcastImportJobTableTrait {
	    	PodcastImportJobTableTrait::setup insteadof JobTrait;
	    }

	public static function title()
	{
		return 'Podcast Import: Assets';
	}

	public static function description()
	{
		return 'Imports Podcast Assets';
	}

	protected static function get_import_table_class()
	{
		return '\Podlove\Model\EpisodeAsset';
	}

	protected static function get_import_item_name()
	{
		return 'asset';
	}

}
