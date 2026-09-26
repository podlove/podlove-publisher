<?php

namespace Podlove\Modules\Contributors\Jobs;

use Podlove\Jobs\JobTrait;
use Podlove\Modules\ImportExport\Import\PodcastImportJobTableTrait;
use Podlove\Modules\ImportExport\Import\PodcastImportJobTrait;

class PodcastImportContributorsJob
{
    use JobTrait,
        PodcastImportJobTrait,
        PodcastImportJobTableTrait {
            PodcastImportJobTableTrait::setup insteadof JobTrait;
        }

    public static function title()
    {
        return __('Podcast Import: Contributors', 'podlove-podcasting-plugin-for-wordpress');
    }

    public static function description()
    {
        return __('Imports Podcast Contributors', 'podlove-podcasting-plugin-for-wordpress');
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
