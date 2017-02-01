<?php 
namespace Podlove\Modules\ImportExport\Import;

use Podlove\Jobs\JobTrait;
use Podlove\Model;
use Podlove\Log;

class PodcastImportTrackingAreaNameJob {
	use JobTrait,
	    PodcastImportJobTrait,
	    PodcastImportJobTableTrait {
	    	PodcastImportJobTableTrait::setup insteadof JobTrait;
	    }

	public static function title()
	{
		return 'Podcast Import: Tracking Area Names';
	}

	public static function description()
	{
		return 'Imports Podcast Tracking Area Names';
	}

	protected static function get_import_table_class()
	{
		return '\Podlove\Model\GeoAreaName';
	}

	protected static function get_import_item_name()
	{
		return 'geoareaname';
	}

}
