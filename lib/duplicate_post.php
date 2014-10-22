<?php
namespace Podlove;

use Podlove\Model\Episode;
use Podlove\Modules\Contributors\Model\EpisodeContribution;

class DuplicatePost {

	public static function init() {
		add_action('dp_duplicate_post', array(__CLASS__, 'regenerate_guid'), 10, 2);

		if (\Podlove\Modules\Base::is_active('contributors'))
			add_action('dp_duplicate_post', array(__CLASS__, 'clone_contributors'), 10, 2);			
	}

	public static function regenerate_guid($new_post_id, $old_post_object) {
		delete_post_meta($new_post_id, '_podlove_guid');
		\Podlove\Custom_Guid::generate_guid_for_episodes($new_post_id, get_post($new_post_id));
	}

	public static function clone_contributors($new_post_id, $old_post_object) {
		$old_episode = Episode::find_one_by_post_id($old_post_object->ID);
		$new_episode = Episode::find_or_create_by_post_id($new_post_id);
		$old_contributions = EpisodeContribution::find_all_by_episode_id($old_episode->id);

		foreach ($old_contributions as $old_contribution) {
			$c = new EpisodeContribution;
			$c->contributor_id = $old_contribution->contributor_id;
			$c->episode_id = $new_episode->id;
			$c->role_id = $old_contribution->role_id;
			$c->group_id = $old_contribution->group_id;
			$c->position = $old_contribution->position;
			$c->comment = $old_contribution->comment;
			$c->save();
		}
	}

}