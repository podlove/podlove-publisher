<?php

namespace Podlove\Api\Episodes;

use Podlove\Model\Episode;
use Podlove\Modules\Contributors\Model\Contributor;
use Podlove\Modules\Contributors\Model\ContributorGroup;
use Podlove\Modules\Contributors\Model\ContributorRole;
use Podlove\Modules\Contributors\Model\DefaultContribution;
use Podlove\Modules\Contributors\Model\EpisodeContribution;
use WP_REST_Controller;
use WP_REST_Server;

class WP_REST_PodloveEpisodeContributions_Controller extends WP_REST_Controller
{
  public function __construct()
  {
    $this->namespace = 'podlove/v2';
    $this->rest_base = 'episodes';
  }

  public function register_routes()
  {
    register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)/contributions', [
      'args' => [
        'id' => [
          'description' => __('Unique identifier for the episode.', 'podlove-podcasting-plugin-for-wordpress'),
          'type' => 'integer',
        ],
      ],
      [
        'methods' => WP_REST_Server::READABLE,
        'callback' => [$this, 'get_item'],
        'permission_callback' => [$this, 'get_item_permissions_check'],
      ],
      [
        'methods' => WP_REST_Server::CREATABLE,
        'callback' => [$this, 'create_item'],
        'permission_callback' => [$this, 'create_item_permissions_check'],
      ],
      [
        'args' => [
          'contributors' => [
            'description' => __('List of contributors of the episode', 'podlove-podcasting-plugin-for-wordpress'),
            'type' => 'array',
            'items' => [
              'type' => 'object',
              'properties' => [
                'contributor_id' => [
                  'description' => __('Id of a contributor'),
                  'type' => 'integer',
                  'required' => 'true'
                ],
                'group_id' => [
                  'description' => __('Id of group of the contributor', 'podlove-podcasting-plugin-for-wordpress'),
                  'type' => 'integer',
                  'validate_callback' => '\Podlove\Api\Validation::isContributorGroupIdExist'
                ],
                'role_id' => [
                  'description' => __('Id of role of the contributor', 'podlove-podcasting-plugin-for-wordpress'),
                  'type' => 'integer',
                  'validate_callback' => '\Podlove\Api\Validation::isContributorRoleIdExist'
                ],
                'comment' => [
                  'description' => __('Comment to the contribution', 'podlove-podcasting-plugin-for-wordpress'),
                  'type' => 'string'
                ],
                'default_contributor' => [
                  'description' => __('Is the contributor a default contributor', 'podlove-podcasting-plugin-for-wordpress'),
                  'type' => 'boolean'
                ]
              ]
            ]
          ]
        ],
        'methods' => WP_REST_Server::EDITABLE,
        'callback' => [$this, 'update_item'],
        'permission_callback' => [$this, 'update_item_permissions_check'],
      ],
      [
        'methods' => WP_REST_Server::DELETABLE,
        'callback' => [$this, 'delete_item'],
        'permission_callback' => [$this, 'delete_item_permissions_check'],
      ]
    ]);
    register_rest_route($this->namespace, '/' . $this->rest_base . '/contributions/(?P<id>[\d]+)', [
      'args' => [
        'id' => [
          'description' => __('Unique identifier for the contribution to an episode.', 'podlove-podcasting-plugin-for-wordpress'),
          'type' => 'integer',
        ],
      ],
      [
        'methods' => WP_REST_Server::READABLE,
        'callback' => [$this, 'get_contribution'],
        'permission_callback' => [$this, 'get_item_permissions_check'],
      ],
      [
        'methods' => WP_REST_Server::EDITABLE,
        'callback' => [$this, 'update_contribution'],
        'permission_callback' => [$this, 'update_item_permissions_check'],
      ],
      [
        'methods' => WP_REST_Server::DELETABLE,
        'callback' => [$this, 'delete_contribution'],
        'permission_callback' => [$this, 'delete_item_permissions_check'],
      ]
    ]);
  }

  public function get_item($request)
  {
    $id = $request->get_param('id');

    $results = array_map(function ($contributor) {

      if (self::isContributorVisible($contributor->contributor_id) == false) {
        if (!current_user_can('edit_posts')) {
          return;
        }
      }

      $comment = $contributor->comment;
      if ($comment == null) {
        $comment = '';
      }
      $group_id = $contributor->group_id;
      if ($group_id == null) {
        $group_id = 0;
      }
      $role_id = $contributor->role_id;
      if ($role_id == null) {
        $role_id = 0;
      }

      return [
        'id' => $contributor->id,
        'contributor_id' => $contributor->contributor_id,
        'role_id' => $role_id,
        'group_id' => $group_id,
        'position' => $contributor->position,
        'comment' => $comment,
        'default_contributor' => self::isContributorDefault($contributor->contributor_id),
      ];
    }, EpisodeContribution::find_all_by_episode_id($id));

    $results_clean = array_filter($results, fn($item) => $this->isNotEmpty($item));

    return new \Podlove\Api\Response\OkResponse([
      '_version' => 'v2',
      'contribution' => $results_clean
    ]);
  }

  public function get_contribution($request)
  {
    $id = $request->get_param('id');

    $contribution = EpisodeContribution::find_by_id($id);
    if (!$contribution) {
      return new \Podlove\Api\Error\NotFound();
    }

    if (self::isContributorVisible($contribution->contributor_id) == false) {
      if (!current_user_can('edit_posts')) {
        return new \Podlove\Api\Error\ForbiddenAccess();
      }
    }

    $comment = $contribution->comment;
    if ($comment == null) {
      $comment = '';
    }
    $group_id = $contribution->group_id;
    if ($group_id == null) {
      $group_id = 0;
    }
    $role_id = $contribution->role_id;
    if ($role_id == null) {
      $role_id = 0;
    }

    return new \Podlove\Api\Response\OkResponse([
      'id' => $contribution->id,
      'contributor_id' => $contribution->contributor_id,
      'role_id' => $role_id,
      'group_id' => $group_id,
      'position' => $contribution->position,
      'comment' => $comment,
      'default_contributor' => self::isContributorDefault($contribution->contributor_id),
    ]);
  }

  public function get_item_permissions_check($request)
  {
    return true;
  }

  public function create_item($request)
  {
    $id = $request->get_param('id');
    if (!$id) {
      return;
    }

    $episode = Episode::find_by_id($id);

    if (!$episode) {
      return new \Podlove\Api\Error\NotFound();
    }

    $contribution = new EpisodeContribution();
    $contribution->episode_id = $id;
    $contribution->save();

    return new \Podlove\Api\Response\CreateResponse([
      'status' => 'ok',
      'id' => $contribution->id
    ]);
  }

  public function create_item_permissions_check($request)
  {
    if (!current_user_can('edit_posts')) {
      return new \Podlove\Api\Error\ForbiddenAccess();
    }

    return true;
  }

  public function update_item($request)
  {
    $id = $request->get_param('id');
    if (!$id) {
      return;
    }

    $episode = Episode::find_by_id($id);

    if (!$episode) {
      return new \Podlove\Api\Error\NotFound();
    }

    $contributions = EpisodeContribution::find_all_by_episode_id($id);
    foreach ($contributions as $contribution) {
      $contribution->delete();
    }

    $warning = [];
    if (isset($request['contributors']) && is_array($request['contributors'])) {
      for ($i = 0; $i < count($request['contributors']); ++$i) {
        $contrib = new EpisodeContribution();
        $contrib->episode_id = $id;
        if (isset($request['contributors'][$i]['contributor_id'])) {
          $contrib->contributor_id = $request['contributors'][$i]['contributor_id'];
        }
        if (isset($request['contributors'][$i]['group_id'])) {
          $group_id = $request['contributors'][$i]['group_id'];
          $group = ContributorGroup::find_by_id($group_id);
          if ($group) {
            $contrib->group_id = $group_id;
          } else {
            if ($group_id > 0) {
              array_push($warning, 'group_id ' . $group_id . ' not exist!');
            }
          }
        }
        if (isset($request['contributors'][$i]['role_id'])) {
          $role_id = $request['contributors'][$i]['role_id'];
          $role = ContributorRole::find_by_id($role_id);
          if ($role) {
            $contrib->role_id = $role_id;
          } else {
            if ($role_id) {
              array_push($warning, 'role_id ' . $role_id . ' not exist!');
            }
          }
        }
        if (isset($request['contributors'][$i]['comment'])) {
          $contrib->comment = $request['contributors'][$i]['comment'];
        }
        if (isset($request['contributors'][$i]['position'])) {
          $contrib->position = $request['contributors'][$i]['position'];
        }
        $contrib->save();
      }
    }

    if (empty($warning)) {
      return new \Podlove\Api\Response\OkResponse([
        'status' => 'ok'
      ]);
    } else {
      return new \Podlove\Api\Response\OkResponse([
        'status' => 'ok',
        'warning' => $warning
      ]);
    }
  }

  public function update_contribution($request)
  {
    $id = $request->get_param('id');

    $contribution = EpisodeContribution::find_by_id($id);
    if (!$contribution) {
      return new \Podlove\Api\Error\NotFound();
    }

    $warning = [];
    if (isset($request['contributor_id'])) {
      $contribution->contributor_id = $request['contributor_id'];
    }
    if (isset($request['group_id'])) {
      $group_id = $request['group_id'];
      $group = ContributorGroup::find_by_id($group_id);
      if ($group) {
        $contribution->group_id = $group_id;
      } else {
        if ($group_id > 0) {
          array_push($warning, 'group_id ' . $group_id . ' not exist!');
        }
      }
    }
    if (isset($request['role_id'])) {
      $role_id = $request['role_id'];
      $role = ContributorRole::find_by_id($role_id);
      if ($role) {
        $contribution->role_id = $role_id;
      } else {
        if ($role_id > 0) {
          array_push($warning, 'role_id ' . $role_id . ' not exist!');
        }
      }
    }
    if (isset($request['comment'])) {
      $contribution->comment = $request['comment'];
    }
    if (isset($request['position'])) {
      $contribution->position = $request['position'];
    }
    $contribution->save();

    if (empty($warning)) {
      return new \Podlove\Api\Response\OkResponse([
        'status' => 'ok'
      ]);
    } else {
      return new \Podlove\Api\Response\OkResponse([
        'status' => 'ok',
        'warning' => $warning
      ]);
    }
  }

  public function update_item_permissions_check($request)
  {
    if (!current_user_can('edit_posts')) {
      return new \Podlove\Api\Error\ForbiddenAccess();
    }

    return true;
  }

  public function delete_item($request)
  {
    $id = $request->get_param('id');
    if (!$id) {
      return;
    }

    $episode = Episode::find_by_id($id);

    if (!$episode) {
      return new \Podlove\Api\Error\NotFound();
    }

    $contributors = EpisodeContribution::find_all_by_episode_id($id);

    foreach ($contributors as $contributor) {
      $contributor->delete();
    }

    return new \Podlove\Api\Response\OkResponse([
      'status' => 'ok'
    ]);
  }

  public function delete_contribution($request)
  {
    $id = $request->get_param('id');
    if (!$id) {
      return;
    }

    $contribution = EpisodeContribution::find_by_id($id);

    if (!$contribution) {
      return new \Podlove\Api\Error\NotFound();
    }

    $contribution->delete();

    return new \Podlove\Api\Response\OkResponse([
      'status' => 'ok'
    ]);
  }

  public function delete_item_permissions_check($request)
  {
    if (!current_user_can('edit_posts')) {
      return new \Podlove\Api\Error\ForbiddenAccess();
    }

    return true;
  }

  private function isContributorDefault($id)
  {
    return (bool) DefaultContribution::find_all_by_property('contributor_id', $id);
  }

  private function isContributorVisible($id)
  {
    $contributor = Contributor::find_by_id($id);
    return $contributor && $contributor->visibility > 0;
  }

  private function isNotEmpty($var)
  {
    return ($var !== NULL && $var !== "");
  }
}
