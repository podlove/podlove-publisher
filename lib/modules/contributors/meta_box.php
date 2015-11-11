<?php
namespace Podlove\Modules\Contributors;

use Podlove\Model\Episode;
use Podlove\Model\Podcast;
use Podlove\Modules\Contributors\Contributors;
use Podlove\Modules\Contributors\Model\ContributorGroup;
use Podlove\Modules\Contributors\Model\ContributorRole;
use Podlove\Modules\Contributors\Model\DefaultContribution;
use Podlove\Modules\Contributors\Model\EpisodeContribution;

class MetaBox {

	public function __construct() {
		add_action('add_meta_boxes_podcast', [$this, 'add_meta_box']);
		add_action('save_post_podcast', [$this, 'save_post']);
	}

	public function add_meta_box() {
		add_meta_box(
			/* id       */ 'podlove_episode_contributors',
			/* title    */ __('Contributors', 'podlove'),
			/* callback */ [$this, 'meta_box_callback'],
			/* page     */ 'podcast',
			/* context  */ 'normal',
			/* priority */ 'high'
		);
	}

	public function meta_box_callback($post) {
		$post_id = $post->ID;

		$podcast = Podcast::get();
		$episode = Episode::find_or_create_by_post_id($post_id);

		$form_args = array(
			'context' => '_podlove_meta',
			'submit_button' => false,
			'form' => false,
			'is_table' => false
		);

		\Podlove\Form\build_for($episode, $form_args, function ($form) {
			$wrapper = new \Podlove\Form\Input\DivWrapper($form);

			$wrapper->callback('contributors_form_table', [
				'label'    => '',
				'callback' => [$this, 'contributors_form_for_episode_callback']
			]);

		});
	}

	public function save_post($post_id) {
		if (!$post_id || !isset($_POST["episode_contributor"]))
			return;
		
		$episode = Episode::find_one_by_post_id($post_id);

		if (!$episode)
			return;

		foreach (EpisodeContribution::find_all_by_episode_id($episode->id) as $contribution) {
			$contribution->delete();
		}

		$position = 0;

		foreach ($_POST["episode_contributor"] as $contributor_appearance) {
			foreach ($contributor_appearance as $contributor_id => $contributor) {

				if (!$contributor_id)
					continue;

				$c = new EpisodeContribution;
				
				if (!empty($contributor['role']))
					$c->role_id = ContributorRole::find_one_by_slug($contributor['role'])->id;

				if (!empty($contributor['group']))
					$c->group_id = ContributorGroup::find_one_by_slug($contributor['group'])->id;

				$c->episode_id     = $episode->id;
				$c->contributor_id = $contributor_id;
				$c->comment        = $contributor['comment'];
				$c->position       = $position++;
				$c->save();		
			}
		}
	}

	public function contributors_form_for_episode_callback() {

		$current_page = get_current_screen();
		$episode = Episode::find_one_by_post_id(get_the_ID());
		
		// determine existing contributions
		$contributions = [];
		if ($current_page->action == "add") {
			$i = 0;
			$permanent_contributors = [];
			foreach ( DefaultContribution::all() as $contribution_key => $contribution ) {
				$permanent_contributors[$contribution_key]['contributor'] = $contribution->getContributor();
				$permanent_contributors[$contribution_key]['role'] = $contribution->getRole();
				$permanent_contributors[$contribution_key]['group'] = $contribution->getGroup();
				$permanent_contributors[$contribution_key]['comment'] = $contribution->comment;
			}

			foreach ($permanent_contributors as $permanent_contributor) {
					$contrib = new EpisodeContribution;
					$contrib->contributor_id = $permanent_contributor['contributor']->id;

					if (isset($permanent_contributor['role'])) {
						$contrib->role = ContributorRole::find_by_id( $permanent_contributor['role']->id );
					}
					
					if (isset($permanent_contributor['group'])) {
						$contrib->group = ContributorGroup::find_by_id( $permanent_contributor['group']->id );
					}

					if (isset($permanent_contributor['comment'])) {
						$contrib->comment = $permanent_contributor['comment'];
					}

					$contributions[] = $contrib;						
			}

			// map indices to IDs
			$map = [];
			$i = 0;
			foreach ($contributions as $c) {
				$map["default" . $c->contributor_id . "_" . $i] = $c;
				$i++;
			}

		} else {
			$contributions = EpisodeContribution::all("WHERE `episode_id` = " . $episode->id . " ORDER BY `position` ASC");

			// map indices to IDs
			$map = [];
			foreach ($contributions as $c) {
				$map[$c->id] = $c;
			}
		}

		Contributors::contributors_form_table($map);
	}
}
