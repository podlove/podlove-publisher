<?php

namespace Podlove\Modules\ImportExport\Import;

use Podlove\Jobs\JobTrait;
use Podlove\Log;
use Podlove\Model;

class PodcastImportEpisodesJob
{
    use JobTrait;
    use PodcastImportJobTrait;

    public function setup()
    {
        $this->setupXml();
        $this->hooks['init'] = [$this, 'init_job'];
    }

    public static function title()
    {
        return 'Podcast Import: Episodes';
    }

    public static function description()
    {
        return 'Imports Podcast Episodes';
    }

    public function init_job()
    {
        Model\Episode::delete_all();
        $this->job->state = 0;
    }

    public function get_total_steps()
    {
        return count($this->xml->xpath('//wpe:episode'));
    }

    protected function do_step()
    {
        $episode = $this->xml->xpath('//wpe:episode')[$this->job->state];

        $new_episode = new Model\Episode();

        foreach ($episode->children('wpe', true) as $attribute) {
            if ($attribute->getName() == 'chapters') {
                $new_episode->chapters = str_replace('&#xD;', "\r\n", $attribute);
            } else {
                $new_episode->{$attribute->getName()} = self::escape((string) $attribute);
            }
        }

        if ($new_post_id = $this->getNewPostId($new_episode->post_id)) {
            $new_episode->post_id = $new_post_id;
            $new_episode->save();
            Log::get()->addInfo(sprintf('Import post %d (%s)', $new_post_id, $new_episode->post_title));
        } else {
            Log::get()->addWarning('Importer: no matching post for (old) post_id='.$new_episode->post_id);
        }

        ++$this->job->state;

        return 1;
    }

    /**
     * Get mapping for post id after post import.
     *
     * When importing posts, their IDs might change.
     * This function maps an existing post id to the new one.
     *
     * @param int $old_post_id
     *
     * @return null|int post_id on success, otherwise null
     */
    private function getNewPostId($old_post_id)
    {
        $query_for_post_id = new \WP_Query([
            'post_type' => 'podcast',
            'meta_query' => [
                [
                    'key' => 'import_id',
                    'value' => $old_post_id,
                    'compare' => '=',
                ],
            ],
        ]);

        if ($query_for_post_id->have_posts()) {
            $p = $query_for_post_id->next_post();

            return $p->ID;
        }

        return null;
    }
}
