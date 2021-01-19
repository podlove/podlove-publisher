<?php

namespace Podlove\Modules\Contributors\Jobs;

use Podlove\Jobs\JobTrait;
use Podlove\Modules\ImportExport\Import\PodcastImportJobTableTrait;
use Podlove\Modules\ImportExport\Import\PodcastImportJobTrait;

class PodcastImportContributorRolesJob
{
    use JobTrait,
        PodcastImportJobTrait,
        PodcastImportJobTableTrait {
            PodcastImportJobTableTrait::setup insteadof JobTrait;
        }

    public static function title()
    {
        return 'Podcast Import: Contributor Roles';
    }

    public static function description()
    {
        return 'Imports Podcast Contributor Roles';
    }

    protected static function get_import_table_class()
    {
        return '\Podlove\Modules\Contributors\Model\ContributorRole';
    }

    protected static function get_import_item_name()
    {
        return 'contributor-role';
    }
}
