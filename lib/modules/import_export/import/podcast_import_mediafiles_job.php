<?php

namespace Podlove\Modules\ImportExport\Import;

use Podlove\Jobs\JobTrait;

class PodcastImportMediafilesJob
{
    use JobTrait,
        PodcastImportJobTrait,
        PodcastImportJobTableTrait {
            PodcastImportJobTableTrait::setup insteadof JobTrait;
        }

    public static function title()
    {
        return __('Podcast Import: Media Files', 'podlove-podcasting-plugin-for-wordpress');
    }

    public static function description()
    {
        return __('Imports Podcast Media Files', 'podlove-podcasting-plugin-for-wordpress');
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
