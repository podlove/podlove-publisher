<?php

namespace Podlove\Api\Chapters;

use Podlove\Chapters\Chapter;
use Podlove\Chapters\Chapters;
use Podlove\Chapters\Printer;
use Podlove\Model\Episode;
use Podlove\NormalPlayTime;
use WP_REST_Controller;
use WP_REST_Server;

add_action('rest_api_init', function () {
    $controller = new WP_REST_PodloveChapters_Controller();
    $controller->register_routes();
});

class WP_REST_PodloveChapters_Controller extends WP_REST_Controller
{
    public function __construct()
    {
        $this->namespace = 'podlove/v2';
        $this->rest_base = 'chapters';
    }

    public function register_routes()
    {
        register_rest_route($this->namespace, '/'.$this->rest_base.'/(?P<id>[\d]+)', [
            'args' => [
                'id' => [
                    'description' => __('Unique identifier for the episode.', 'podlove-podcasting-plugin-for-wordpress'),
                    'type' => 'integer',
                    'required' => 'true'
                ],
            ],
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_item'],
                'permission_callback' => [$this, 'get_item_permissions_check'],
            ],
            [
                'args' => [
                    'chapters' => [
                        'description' => __('List of chapters, please use mp4chpat format.', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'start' => [
                                    'description' => __('Chapter begin timestamp', 'podlove-podcasting-plugin-for-wordpress'),
                                    'type' => 'string',
                                    'required' => 'true'
                                ],
                                'title' => [
                                    'description' => __('Chapter title', 'podlove-podcasting-plugin-for-wordpress'),
                                    'type' => 'string',
                                    'required' => 'true'
                                ],
                                'href' => [
                                    'description' => __('Chapter url', 'podlove-podcasting-plugin-for-wordpress'),
                                    'type' => 'string'
                                ]
                            ]
                        ],
                        'required' => 'true',
                        'validate_callback' => '\Podlove\Api\Validation::chapters'
                    ]
                ],
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'create_item'],
                'permission_callback' => [$this, 'create_item_permissions_check'],
            ],
            [
                'args' => [
                    'chapters' => [
                        'description' => __('List of chapters, please use mp4chaps format.', 'podlove-podcasting-plugin-for-wordpress'),
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'start' => [
                                    'description' => __('Chapter begin timestamp', 'podlove-podcasting-plugin-for-wordpress'),
                                    'type' => 'string',
                                    'required' => 'true'
                                ],
                                'title' => [
                                    'description' => __('Chapter title', 'podlove-podcasting-plugin-for-wordpress'),
                                    'type' => 'string',
                                    'required' => 'true'
                                ],
                                'href' => [
                                    'description' => __('Chapter url', 'podlove-podcasting-plugin-for-wordpress'),
                                    'type' => 'string'
                                ]
                            ]
                        ],
                        'required' => 'true',
                        'validate_callback' => '\Podlove\Api\Validation::chapters'
                    ]
                ],
                'description' => __('Edit the chapters list to an epsiode, old chapter list will be deleted.', 'podlove-podcasting-plugin-for-wordpress'),
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
    }

    public function get_item_permissions_check($request)
    {
        return true;
    }

    public function get_item($request)
    {
        $id = $request->get_param('id');
        $episode = Episode::find_by_id($id);

        if ($episode) {
            $data = array_map(function ($c) {
                $c->title = html_entity_decode(trim($c->title));

                return $c;
            }, (array) json_decode($episode->get_chapters('json')));
        }

        return new \Podlove\Api\Response\OkResponse([
            'chapters' => $data,
            '_version' => 'v2',
        ]);
    }

    public function create_item_permissions_check($request)
    {
        if (!current_user_can('edit_posts')) {
            return new \Podlove\Api\Error\ForbiddenAccess();
        }

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

        $chapters = new Chapters();
        $npt = 0;

        if (isset($request['chapters']) && is_array($request['chapters'])) {
            for ($i = 0; $i < count($request['chapters']); ++$i) {
                $timestamp = '';
                if (isset($request['chapters'][$i]['start'])) {
                    $timestamp = $request['chapters'][$i]['start'];
                    $npt = NormalPlayTime\Parser::parse($timestamp, 'ms');
                }
                $title = '';
                if (isset($request['chapters'][$i]['title'])) {
                    $title = $request['chapters'][$i]['title'];
                }
                $url = '';
                if (isset($request['chapters'][$i]['href'])) {
                    $url = $request['chapters'][$i]['href'];
                }
                if (strlen($url) == 0) {
                    $chapters->addChapter(new Chapter($npt, $title));
                } else {
                    $chapters->addChapter(new Chapter($npt, $title, $url));
                }
            }
        }

        $chapters->setPrinter(new Printer\Mp4chaps());
        $episode_data['chapters'] = (string) $chapters;
        $episode->update_attributes($episode_data);

        return new \Podlove\Api\Response\CreateResponse([
            'status' => 'ok'
        ]);
    }

    public function update_item_permissions_check($request)
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

        $chapters = new Chapters();
        $npt = 0;

        if (isset($request['chapters']) && is_array($request['chapters'])) {
            for ($i = 0; $i < count($request['chapters']); ++$i) {
                $timestamp = '';
                if (isset($request['chapters'][$i]['start'])) {
                    $timestamp = $request['chapters'][$i]['start'];
                    $npt = NormalPlayTime\Parser::parse($timestamp, 'ms');
                }
                $title = '';
                if (isset($request['chapters'][$i]['title'])) {
                    $title = $request['chapters'][$i]['title'];
                }
                $url = '';
                if (isset($request['chapters'][$i]['href'])) {
                    $url = $request['chapters'][$i]['href'];
                }
                if (strlen($url) == 0) {
                    $chapters->addChapter(new Chapter($npt, $title));
                } else {
                    $chapters->addChapter(new Chapter($npt, $title, $url));
                }
            }
        }

        $chapters->setPrinter(new Printer\Mp4chaps());
        $episode_data['chapters'] = (string) $chapters;
        $episode->update_attributes($episode_data);

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

        $episode_data['chapters'] = '';
        $episode->update_attributes($episode_data);

        return new \Podlove\Api\Response\OkResponse([
            'status' => 'ok'
        ]);
    }
}
