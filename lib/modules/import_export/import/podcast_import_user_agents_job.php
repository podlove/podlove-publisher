<?php 
namespace Podlove\Modules\ImportExport\Import;

use Podlove\Jobs\JobTrait;
use Podlove\Model;
use Podlove\Log;

class PodcastImportUserAgentsJob {
	use JobTrait,
	    PodcastImportJobTrait,
	    PodcastImportJobTableTrait {
	    	PodcastImportJobTableTrait::setup insteadof JobTrait;
	    }

	public static function title()
	{
		return 'Podcast Import: User Agents';
	}

	public static function description()
	{
		return 'Imports Podcast User Agents';
	}

	protected static function get_import_table_class()
	{
		return '\Podlove\Model\UserAgent';
	}

	protected static function get_import_item_name()
	{
		return 'useragent';
	}

}
