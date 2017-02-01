<?php 
namespace Podlove\Modules\ImportExport\Import;

use Podlove\Jobs\JobTrait;
use Podlove\Model;
use Podlove\Log;

class PodcastImportTrackingAreaJob {
	use JobTrait,
	    PodcastImportJobTrait,
	    PodcastImportJobTableTrait {
	    	PodcastImportJobTableTrait::setup insteadof JobTrait;
	    }

	public static function title()
	{
		return 'Podcast Import: Tracking Areas';
	}

	public static function description()
	{
		return 'Imports Podcast Tracking Areas';
	}

	protected static function get_import_table_class()
	{
		return '\Podlove\Model\GeoArea';
	}

	protected static function get_import_item_name()
	{
		return 'geoarea';
	}

}
