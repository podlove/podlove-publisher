<?php 
namespace Podlove\Modules\ImportExport\Import;

use Podlove\Jobs\JobTrait;
use Podlove\Model;
use Podlove\Log;

class PodcastImportTemplatesJob {
	use JobTrait,
	    PodcastImportJobTrait,
	    PodcastImportJobTableTrait {
	    	PodcastImportJobTableTrait::setup insteadof JobTrait;
	    }

	public static function title()
	{
		return 'Podcast Import: Templates';
	}

	public static function description()
	{
		return 'Imports Podcast Templates';
	}

	protected static function get_import_table_class()
	{
		return '\Podlove\Model\Template';
	}

	protected static function get_import_item_name()
	{
		return 'template';
	}

}
