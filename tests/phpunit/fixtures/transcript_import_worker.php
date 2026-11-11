<?php

// Run against the same database and table prefix as the parent integration test.
require $argv[1].'wp-load.php';
$wpdb->select($argv[2]);
$wpdb->prefix = $argv[3];
wp_cache_flush();
require_once dirname(__DIR__, 3).'/podlove.php';

$episode = \Podlove\Model\Episode::find_by_id((int) $argv[4]);
$content = file_get_contents($argv[5]);
$mode = $argv[6];
$signal = $argv[7];
$release = $argv[8];

if ($mode === 'legacy') {
    add_action('podlove_model_save', function ($model) use ($signal, $release) {
        if (!$model instanceof \Podlove\Modules\Transcripts\Model\Transcript || file_exists($signal)) {
            return;
        }
        touch($signal);
        $deadline = microtime(true) + 15;
        while (!file_exists($release)) {
            if (microtime(true) > $deadline) {
                throw new \RuntimeException('Timed out waiting for test release.');
            }
            usleep(10000);
        }
    });
    $result = \Podlove\Modules\Transcripts\Transcripts::parse_and_import_webvtt($episode, $content);
} elseif ($mode === 'upload') {
    $request = new WP_REST_Request('PUT');
    $request->set_param('id', $episode->id);
    $request->set_param('content', $content);
    $controller = new \Podlove\Modules\Transcripts\WP_REST_PodloveTranscripts_Controller();
    $result = $controller->update_item($request);
} else {
    $result = \Podlove\Modules\Transcripts\Transcripts::transcript_import_from_asset($episode);
}

if (is_wp_error($result) || is_array($result) || $result === false) {
    fwrite(STDERR, 'Import failed: '.var_export($result, true));
    exit(1);
}
