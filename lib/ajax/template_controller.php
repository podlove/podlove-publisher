<?php

namespace Podlove\AJAX;

use Podlove\Cache\TemplateCache;
use Podlove\Model\Template;

class TemplateController
{
    public static function init()
    {
        $actions = [
            'get', 'update', 'create', 'delete',
        ];

        foreach ($actions as $action) {
            if (is_network_admin()) {
                // No need to deactivate the scope because the script dies
                // after the main action anyway.
                add_action('wp_ajax_podlove-template-'.$action, [__CLASS__, 'activate_network_scope'], 9);
            }

            add_action('wp_ajax_podlove-template-'.$action, [__CLASS__, str_replace('-', '_', $action)]);
        }
    }

    public static function activate_network_scope()
    {
        Template::activate_network_scope();
    }

    public static function get()
    {
        if (!current_user_can('administrator')) {
            Ajax::respond_with_json(['success' => false]);
        }

        $id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);

        if ($template = Template::find_by_id($id)) {
            $response = [
                'id' => $template->id,
                'title' => $template->title,
                'content' => $template->content,
            ];
        } else {
            $response = [];
        }

        Ajax::respond_with_json($response);
    }

    public static function update()
    {
        if (!current_user_can('administrator')) {
            Ajax::respond_with_json(['success' => false]);
        }

        $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
        $title = filter_input(INPUT_POST, 'title');
        $content = filter_input(INPUT_POST, 'content');

        if (!$id || !$title) {
            Ajax::respond_with_json(['success' => false]);
        }

        $template = Template::find_by_id($id);
        $template->title = $title;
        $template->content = $content;
        $template->save();

        if (is_network_admin()) {
            TemplateCache::get_instance()->setup_purge_in_all_blogs();
            TemplateCache::get_instance()->purge();
        } else {
            TemplateCache::get_instance()->purge();
        }

        Ajax::respond_with_json(['success' => true]);
    }

    public static function create()
    {
        if (!current_user_can('administrator')) {
            Ajax::respond_with_json(['success' => false]);
        }

        $template = new Template();
        $template->title = 'new template';
        $template->save();

        Ajax::respond_with_json(['id' => $template->id]);
    }

    public static function delete()
    {
        if (!current_user_can('administrator')) {
            Ajax::respond_with_json(['success' => false]);
        }

        $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
        $template = Template::find_by_id($id);

        if (!$id || !$template) {
            Ajax::respond_with_json(['success' => false]);
        } else {
            $template->delete();
            Ajax::respond_with_json(['success' => true]);
        }
    }
}
