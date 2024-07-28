<?php

namespace Podlove\Modules\Transcripts\Jobs;

use Podlove\Jobs\JobTrait;
use Podlove\Modules\ImportExport\Import\PodcastImportJobTableTrait;
use Podlove\Modules\ImportExport\Import\PodcastImportJobTrait;

class ImportVoiceAssignmentsJob
{
    use JobTrait,
        PodcastImportJobTrait,
        PodcastImportJobTableTrait {
            PodcastImportJobTableTrait::setup insteadof JobTrait;
        }

    public static function title()
    {
        return 'Podcast Import: Transcript Voices';
    }

    public static function description()
    {
        return 'Imports Episode Transcript Voice Assignments';
    }

    protected static function get_import_table_class()
    {
        return \Podlove\Modules\Transcripts\Model\VoiceAssignment::class;
    }

    protected static function get_import_item_name()
    {
        return 'voice_assignment';
    }
}
