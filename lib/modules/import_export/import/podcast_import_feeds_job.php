<?php 
namespace Podlove\Modules\ImportExport\Import;

use Podlove\Jobs\JobTrait;
use Podlove\Model;
use Podlove\Log;

class PodcastImportFeedsJob {
	use JobTrait,
	    PodcastImportJobTrait,
	    PodcastImportJobTableTrait {
	    	PodcastImportJobTableTrait::setup insteadof JobTrait;
	    }

	public static function title()
	{
		return 'Podcast Import: Feeds';
	}

	public static function description()
	{
		return 'Imports Podcast Feeds';
	}

	protected static function get_import_table_class()
	{
		return '\Podlove\Model\Feed';
	}

	protected static function get_import_item_name()
	{
		return 'feed';
	}

}
