<?php 
namespace Podlove\Modules\Social\Jobs;

use Podlove\Jobs\JobTrait;
use Podlove\Modules\ImportExport\Import\PodcastImportJobTrait;
use Podlove\Modules\ImportExport\Import\PodcastImportJobTableTrait;

class PodcastImportContributorServicesJob {
	use JobTrait,
	    PodcastImportJobTrait,
	    PodcastImportJobTableTrait {
	    	PodcastImportJobTableTrait::setup insteadof JobTrait;
	    }

	public static function title()
	{
		return 'Podcast Import: Contributor Services';
	}

	public static function description()
	{
		return 'Imports Podcast Contributor Services';
	}

	protected static function get_import_table_class()
	{
		return '\Podlove\Modules\Social\Model\ContributorService';
	}

	protected static function get_import_item_name()
	{
		return 'contributorService';
	}

}
