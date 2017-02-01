<?php
namespace Podlove\Modules\Seasons;

use Podlove\Jobs\JobTrait;
use Podlove\Modules\ImportExport\Import\PodcastImportJobTrait;
use Podlove\Modules\ImportExport\Import\PodcastImportJobTableTrait;

class PodcastImportSeasonsJob {
	use JobTrait,
	    PodcastImportJobTrait,
	    PodcastImportJobTableTrait {
	    	PodcastImportJobTableTrait::setup insteadof JobTrait;
	    }

	public static function title()
	{
		return 'Podcast Import: Seasons';
	}

	public static function description()
	{
		return 'Imports Podcast Seasons';
	}

	protected static function get_import_table_class()
	{
		return '\Podlove\Modules\Seasons\Model\Season';
	}

	protected static function get_import_item_name()
	{
		return 'season';
	}

}
