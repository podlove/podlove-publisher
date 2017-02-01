<?php 
namespace Podlove\Modules\ImportExport\Import;

use Podlove\Jobs\JobTrait;
use Podlove\Model;
use Podlove\Log;

class PodcastImportMediafilesJob {
	use JobTrait,
	    PodcastImportJobTrait,
	    PodcastImportJobTableTrait {
	    	PodcastImportJobTableTrait::setup insteadof JobTrait;
	    }

	public static function title()
	{
		return 'Podcast Import: Media Files';
	}

	public static function description()
	{
		return 'Imports Podcast Media Files';
	}

	protected static function get_import_table_class()
	{
		return '\Podlove\Model\MediaFile';
	}

	protected static function get_import_item_name()
	{
		return 'mediafile';
	}

}
