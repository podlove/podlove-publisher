<?php

namespace Podlove\Modules\ImportExport\Import;

use Podlove\Jobs\JobTrait;

class PodcastImportUserAgentsJob
{
    use JobTrait,
        PodcastImportJobTrait,
        PodcastImportJobTableTrait {
            PodcastImportJobTableTrait::setup insteadof JobTrait;
        }

    public static function title()
    {
        return __('Podcast Import: User Agents', 'podlove-podcasting-plugin-for-wordpress');
    }

    public static function description()
    {
        return __('Imports Podcast User Agents', 'podlove-podcasting-plugin-for-wordpress');
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
