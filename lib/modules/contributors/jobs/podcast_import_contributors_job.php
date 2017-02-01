<?php
namespace Podlove\Modules\Contributors\Jobs;

use Podlove\Jobs\JobTrait;
use Podlove\Modules\ImportExport\Import\PodcastImportJobTrait;
use Podlove\Modules\ImportExport\Import\PodcastImportJobTableTrait;

class PodcastImportContributorsJob {
	use JobTrait,
	    PodcastImportJobTrait,
	    PodcastImportJobTableTrait {
	    	PodcastImportJobTableTrait::setup insteadof JobTrait;
	    }

	public static function title()
	{
		return 'Podcast Import: Contributors';
	}

	public static function description()
	{
		return 'Imports Podcast Contributors';
	}

	protected static function get_import_table_class()
	{
		return '\Podlove\Modules\Contributors\Model\Contributor';
	}

	protected static function get_import_item_name()
	{
		return 'contributor';
	}

}
