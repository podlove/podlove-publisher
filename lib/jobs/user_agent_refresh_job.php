<?php

namespace Podlove\Jobs;

use Podlove\Model\DownloadIntentClean;
use Podlove\Model\UserAgent;

class UserAgentRefreshJob
{
    use JobTrait;

    public function setup()
    {
        $this->hooks['finished'] = [__CLASS__, 'delete_bots_from_clean_downloadintents'];

        if (!$this->job->state) {
            $this->job->state = ['previous_id' => 0];
        }
    }

    public static function title()
    {
        return __('User Agent Refresh', 'podlove-podcasting-plugin-for-wordpress');
    }

    public static function description()
    {
        return __('Updates user agent metadata based on device-detector library.', 'podlove-podcasting-plugin-for-wordpress');
    }

    public static function defaults()
    {
        return [
            'agents_total' => UserAgent::count(),
            'agents_per_step' => 500,
        ];
    }

    public function get_total_steps()
    {
        return $this->job->args['agents_total'];
    }

    public static function delete_bots_from_clean_downloadintents()
    {
        global $wpdb;

        $sql = 'DELETE FROM `'.DownloadIntentClean::table_name().'` WHERE `user_agent_id` IN (
			SELECT id FROM `'.UserAgent::table_name().'` ua WHERE ua.bot
		)';

        $wpdb->query($sql);
    }

    protected function do_step()
    {
        $previous_id = (int) $this->job->state['previous_id'];
        $agents_per_step = (int) $this->job->args['agents_per_step'];

        $agents = UserAgent::find_all_by_where(sprintf('id > %d ORDER BY id ASC LIMIT %d', $previous_id, $agents_per_step));

        $progress = 0;
        foreach ($agents as $ua) {
            $ua->parse()->save();
            ++$progress;
        }

        $this->job->update_state('previous_id', $ua->id);

        return $progress;
    }
}
