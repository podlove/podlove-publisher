<?php 
namespace Podlove\Modules\Social\Jobs;

use Podlove\Jobs\JobTrait;
use Podlove\Modules\ImportExport\Import\PodcastImportJobTrait;
use Podlove\Modules\ImportExport\Import\PodcastImportJobTableTrait;

class PodcastImportServicesJob {
	use JobTrait,
	    PodcastImportJobTrait,
	    PodcastImportJobTableTrait {
	    	PodcastImportJobTableTrait::setup insteadof JobTrait;
	    }

	public static function title()
	{
		return 'Podcast Import: Services';
	}

	public static function description()
	{
		return 'Imports Podcast Services';
	}

	protected static function get_import_table_class()
	{
		return '\Podlove\Modules\Social\Model\Service';
	}

	protected static function get_import_item_name()
	{
		return 'service';
	}

}
