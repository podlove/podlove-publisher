<?php

namespace Podlove\Modules\Contributors\Jobs;

use Podlove\Jobs\JobTrait;
use Podlove\Modules\ImportExport\Import\PodcastImportJobTableTrait;
use Podlove\Modules\ImportExport\Import\PodcastImportJobTrait;

class PodcastImportContributorShowContributionsJob
{
    use JobTrait,
        PodcastImportJobTrait,
        PodcastImportJobTableTrait {
            PodcastImportJobTableTrait::setup insteadof JobTrait;
        }

    public static function title()
    {
        return __('Podcast Import: Contributor Show Contributions', 'podlove-podcasting-plugin-for-wordpress');
    }

    public static function description()
    {
        return __('Imports Podcast Contributor Show Contributions', 'podlove-podcasting-plugin-for-wordpress');
    }

    protected static function get_import_table_class()
    {
        return '\Podlove\Modules\Contributors\Model\ShowContribution';
    }

    protected static function get_import_item_name()
    {
        return 'contributor-show-contribution';
    }
}
