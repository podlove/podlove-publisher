<?php 
namespace Podlove\Modules\ImportExport\Import;

use Podlove\Jobs\JobTrait;
use Podlove\Model;
use Podlove\Log;

class PodcastImportFiletypesJob {
	use JobTrait,
	    PodcastImportJobTrait,
	    PodcastImportJobTableTrait {
	    	PodcastImportJobTableTrait::setup insteadof JobTrait;
	    }

	public static function title()
	{
		return 'Podcast Import: File Types';
	}

	public static function description()
	{
		return 'Imports Podcast File Types';
	}

	protected static function get_import_table_class()
	{
		return '\Podlove\Model\FileType';
	}

	protected static function get_import_item_name()
	{
		return 'filetype';
	}

}
