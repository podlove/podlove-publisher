<?php

namespace Podlove\Modules\Transcripts\Jobs;

use Podlove\Jobs\JobTrait;
use Podlove\Modules\ImportExport\Import\PodcastImportJobTableTrait;
use Podlove\Modules\ImportExport\Import\PodcastImportJobTrait;

class ImportTranscriptsJob
{
    use JobTrait,
        PodcastImportJobTrait,
        PodcastImportJobTableTrait {
            PodcastImportJobTableTrait::setup insteadof JobTrait;
        }

    public static function title()
    {
        return 'Podcast Import: Transcripts';
    }

    public static function description()
    {
        return 'Imports Episode Transcripts';
    }

    protected static function get_import_table_class()
    {
        return \Podlove\Modules\Transcripts\Model\Transcript::class;
    }

    protected static function get_import_item_name()
    {
        return 'transcript';
    }
}
