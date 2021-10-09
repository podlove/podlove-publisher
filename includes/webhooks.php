<?php

/**
 * Quick local debugging.
 *
 * Put this in your wp-config.php:
 *
 *     define('PODLOVE_WEBHOOKS', [
 *         'episode_updated' => 'http://localhost:10003/?webhook_debugger=1',
 *     ]);
 *
 * And uncomment this or put it somewhere where it get executed:
 *
 *     add_action('init', function () {
 *         if (isset($_REQUEST['webhook_debugger'])) {
 *             error_log(print_r($_REQUEST, true));
 *         }
 *     });
 */

use Podlove\Model;
use Podlove\Webhook\Webhook;

add_action('podlove_fire_webhook', 'podlove_fire_webhook', 10, 4);

function podlove_fire_webhook($event, $method, $payload, $url)
{
    $webhook = new Webhook($event);
    $webhook
        ->method($method)
        ->payload($payload)
        ->send($url)
    ;
}

function podlove_init_webhooks($config)
{
    if (empty($config)) {
        return;
    }

    if (isset($config['episode_updated'])) {
        add_action('podlove_episode_content_has_changed', function ($episode_id) use ($config) {
            $event = 'episode_updated';
            if ($episode = Model\Episode::find_by_id($episode_id)) {
                wp_schedule_single_event(time() + 1, 'podlove_fire_webhook', [
                    'event' => $event,
                    'method' => 'POST',
                    'payload' => ['episode' => $episode->to_array()],
                    'url' => $config[$event]
                ]);
            }
        });
    }
}

if (defined('PODLOVE_WEBHOOKS')) {
    podlove_init_webhooks(PODLOVE_WEBHOOKS);
}
