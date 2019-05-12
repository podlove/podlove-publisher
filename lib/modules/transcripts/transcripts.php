<?php
namespace Podlove\Modules\Transcripts;

use Podlove\Model;
use Podlove\Model\Episode;
use Podlove\Modules\Contributors\Model\Contributor;
use Podlove\Modules\Transcripts\Model\Transcript;
use Podlove\Modules\Transcripts\Model\VoiceAssignment;
use Podlove\Webvtt\Parser;
use Podlove\Webvtt\ParserException;

class Transcripts extends \Podlove\Modules\Base
{

    protected $module_name        = 'Transcripts';
    protected $module_description = 'Manage transcripts, show them on your site and in the web player.';
    protected $module_group       = 'metadata';

    public function load()
    {
        add_action('podlove_module_was_activated_transcripts', [$this, 'was_activated']);
        add_filter('podlove_episode_form_data', [$this, 'extend_episode_form'], 10, 2);
        add_action('wp_ajax_podlove_transcript_import', [$this, 'ajax_transcript_import']);
        add_action('wp_ajax_podlove_transcript_asset_import', [$this, 'ajax_transcript_asset_import']);
        add_action('wp_ajax_podlove_transcript_get_contributors', [$this, 'ajax_transcript_get_contributors']);
        add_action('wp_ajax_podlove_transcript_get_voices', [$this, 'ajax_transcript_get_voices']);

        // add_filter('podlove_episode_data_before_save', [$this, 'save_episode_voice_assignments']);
        add_filter('mime_types', [$this, 'ensure_vtt_mime_type_is_known'], 20);

        add_filter('podlove_player4_config', [$this, 'add_playerv4_config'], 10, 2);

        add_action('wp', [$this, 'serve_transcript_file']);

        # external assets
        add_action('podlove_asset_assignment_form', [$this, 'add_asset_assignment_form'], 10, 2);
        add_action('podlove_media_file_content_has_changed', [$this, 'handle_changed_media_file']);
        add_action('podlove_media_file_content_verified', [$this, 'handle_changed_media_file']);

        add_filter('podlove_twig_file_loader', function ($file_loader) {
            $file_loader->addPath(implode(DIRECTORY_SEPARATOR, array(\Podlove\PLUGIN_DIR, 'lib', 'modules', 'transcripts', 'twig')), 'transcripts');
            return $file_loader;
        });

        add_shortcode('podlove-transcript', [$this, 'transcript_shortcode']);

        \Podlove\Template\Episode::add_accessor(
            'transcript', array('\Podlove\Modules\Transcripts\TemplateExtensions', 'accessorEpisodeTranscript'), 4
        );

        add_action('rest_api_init', [$this, 'api_init']);

    }

    public function api_init()
    {
        $api = new REST_API();
        $api->register_routes();
    }

    public function ensure_vtt_mime_type_is_known($mime_types)
    {
        if (!array_key_exists('vtt', $mime_types)) {
            $mime_types['vtt'] = 'text/vtt';
        }

        return $mime_types;
    }

    public function transcript_shortcode($args = [])
    {
        if (isset($args['post_id'])) {
            $post_id = $args['post_id'];
            unset($args['post_id']);
        } else {
            $post_id = get_the_ID();
        }

        $episode = Model\Episode::find_one_by_post_id($post_id);
        $episode = new \Podlove\Template\Episode($episode);

        return \Podlove\Template\TwigFilter::apply_to_html('@transcripts/transcript.twig', ['episode' => $episode]);
    }

    public function was_activated($module_name)
    {
        Transcript::build();
        VoiceAssignment::build();
    }

    public function extend_episode_form($form_data, $episode)
    {
        $form_data[] = array(
            'type'     => 'callback',
            'key'      => 'transcripts',
            'options'  => array(
                'callback' => function () use ($episode) {
                    $data = '';
                    ?>
<div id="podlove-transcripts-app-data" style="display: none"><?php echo $data ?></div>
<div id="podlove-transcripts-app"><transcripts></transcripts></div>
<?php
},
                'label'    => __('Transcripts', 'podlove-podcasting-plugin-for-wordpress'),
            ),
            'position' => 425,
        );
        return $form_data;
    }

    public function ajax_transcript_import()
    {
        if (!isset($_FILES['transcript'])) {
            wp_die();
        }

        // todo: I don't really want it permanently uploaded, so ... delete when done
        $file = wp_handle_upload($_FILES['transcript'], array('test_form' => false));

        if (!$file || isset($file['error'])) {
            $error = 'Could not upload transcript file. Reason: ' . $file['error'];
            \Podlove\Log::get()->addError($error);
            \Podlove\AJAX\Ajax::respond_with_json(['error' => $error]);
        }

        if (stripos($file['type'], 'vtt') === false) {
            $error = 'Transcript file must be webvtt. Is: ' . $file['type'];
            \Podlove\Log::get()->addError($error);
            \Podlove\AJAX\Ajax::respond_with_json(['error' => $error]);
        }

        $post_id = intval($_POST['post_id'], 10);
        $episode = Model\Episode::find_one_by_post_id($post_id);

        if (!$episode) {
            $error = 'Could not find episode for this post object.';
            \Podlove\Log::get()->addError($error);
            \Podlove\AJAX\Ajax::respond_with_json(['error' => $error]);
        }

        $content = file_get_contents($file['file']);

        self::parse_and_import_webvtt($episode, $content);

        wp_die();
    }

    public function ajax_transcript_asset_import()
    {
        $post_id = intval($_GET['post_id'], 10);
        $episode = Model\Episode::find_one_by_post_id($post_id);

        if (!$episode) {
            $error = 'Could not find episode for this post object.';
            \Podlove\Log::get()->addError($error);
            \Podlove\AJAX\Ajax::respond_with_json(['error' => $error]);
        }

        if (($return = $this->transcript_import_from_asset($episode)) !== true) {
            if (is_array($return) && isset($return['error'])) {
                \Podlove\Log::get()->addError($return['error']);
                \Podlove\AJAX\Ajax::respond_with_json($return);
            }
        }

        wp_die();
    }

    /**
     * Import transcript from remote file
     */
    public function transcript_import_from_asset(Episode $episode)
    {
        $asset_assignment = Model\AssetAssignment::get_instance();

        if (!$transcript_asset = Model\EpisodeAsset::find_one_by_id($asset_assignment->transcript)) {
            return [
                'error' => sprintf(
                    __('No asset is assigned for transcripts yet. Fix this in %s', 'podlove-podcasting-plugin-for-wordpress'),
                    sprintf(
                        '%s%s%s',
                        '<a href="' . admin_url('admin.php?page=podlove_episode_assets_settings_handle') . '" target="_blank">',
                        __('Episode Assets', 'podlove-podcasting-plugin-for-wordpress'),
                        '</a>'
                    )
                ),
            ];
        }

        if (!$transcript_file = Model\MediaFile::find_by_episode_id_and_episode_asset_id($episode->id, $transcript_asset->id)) {
            return ['error' => __('No transcript file is available for this episode.', 'podlove-podcasting-plugin-for-wordpress')];
        }

        $transcript = wp_remote_get($transcript_file->get_file_url());

        if (is_wp_error($transcript)) {
            return ['error' => $transcript->get_error_message()];
        }

        self::parse_and_import_webvtt($episode, $transcript['body']);

        return true;
    }

    public static function parse_and_import_webvtt(Episode $episode, $content)
    {
        $parser = new Parser();

        if (function_exists('mb_check_encoding') && !mb_check_encoding($content, 'UTF-8')) {
            \Podlove\AJAX\Ajax::respond_with_json(['error' => 'Error parsing webvtt file: must be UTF-8 encoded']);
        }

        try {
            $result = $parser->parse($content);
        } catch (ParserException $e) {
            $error = 'Error parsing webvtt file: ' . $e->getMessage();
            \Podlove\Log::get()->addError($error);
            if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'podlove_transcript_import') {
                \Podlove\AJAX\Ajax::respond_with_json(['error' => $error]);
            }
            return;
        }

        Transcript::delete_for_episode($episode->id);

        foreach ($result['cues'] as $cue) {
            $line             = new Transcript;
            $line->episode_id = $episode->id;
            $line->start      = $cue['start'] * 1000;
            $line->end        = $cue['end'] * 1000;
            $line->voice      = $cue['voice'];
            $line->content    = $cue['text'];
            $line->save();
        }

        $voices = array_unique(array_map(function ($cue) {
            return $cue['voice'];
        }, $result['cues']));

        foreach ($voices as $voice) {
            $contributor = Contributor::find_one_by_property("identifier", $voice);

            if (!VoiceAssignment::is_voice_set($episode->id, $voice) && $contributor) {
                $voice_assignment                 = new VoiceAssignment;
                $voice_assignment->episode_id     = $episode->id;
                $voice_assignment->voice          = $voice;
                $voice_assignment->contributor_id = $contributor->id;
                $voice_assignment->save();
            }
        }
    }

    public function ajax_transcript_get_contributors()
    {
        $contributors = Contributor::all();
        $contributors = array_map(function ($c) {
            return [
                'id'         => $c->id,
                'name'       => $c->getName(),
                'identifier' => $c->identifier,
                'avatar'     => $c->avatar()->url(),
            ];
        }, $contributors);

        \Podlove\AJAX\Ajax::respond_with_json(['contributors' => $contributors]);
    }

    public function ajax_transcript_get_voices()
    {
        $post_id = intval($_GET['post_id'], 10);
        $episode = Model\Episode::find_one_by_post_id($post_id);
        $voices  = Transcript::get_voices_for_episode_id($episode->id);
        \Podlove\AJAX\Ajax::respond_with_json(['voices' => $voices]);
    }

    public function serve_transcript_file()
    {
        $format = filter_input(INPUT_GET, 'podlove_transcript', FILTER_VALIDATE_REGEXP, [
            'options' => ['regexp' => "/^(json_grouped|json|webvtt|xml)$/"],
        ]);

        if (!$format) {
            return;
        }

        $post_id = get_the_ID();
        if (!$post_id) {
            $post_id = intval($_GET['p'], 10);
        }

        if (!$post_id) {
            return;
        }

        if (!$episode = Model\Episode::find_or_create_by_post_id($post_id)) {
            return;
        }

        $renderer = new Renderer($episode);

        http_response_code(200);

        switch ($format) {
            case 'xml':
                header('Cache-Control: no-cache, must-revalidate');
                header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
                header("Content-Type: application/xml; charset=utf-8");
                echo $renderer->as_xml();
                exit;
                break;
            case 'webvtt':
                header('Cache-Control: no-cache, must-revalidate');
                header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
                header("Content-Type: text/vtt");
                echo $renderer->as_webvtt();
                exit;
                break;
            case 'json':
            case 'json_grouped':
                header('Cache-Control: no-cache, must-revalidate');
                header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
                header('Content-type: application/json');
                $mode = ($format == 'json' ? 'flat' : 'grouped');
                echo $renderer->as_json($mode);
                exit;
                break;
        }
    }

    public function add_playerv4_config($config, $episode)
    {
        if (Transcript::exists_for_episode($episode->id)) {
            $url                   = add_query_arg('podlove_transcript', 'json', get_permalink($episode->post_id));
            $config['transcripts'] = $url;
        }
        return $config;
    }

    public function add_asset_assignment_form($wrapper, $asset_assignment)
    {
        $transcript_options = [
            'manual' => __('Manual Upload', 'podlove-podcasting-plugin-for-wordpress'),
        ];

        $episode_assets = Model\EpisodeAsset::all();
        foreach ($episode_assets as $episode_asset) {
            $file_type = $episode_asset->file_type();
            if ($file_type && $file_type->extension === 'vtt') {
                $transcript_options[$episode_asset->id]
                = sprintf(__('Asset: %s', 'podlove-podcasting-plugin-for-wordpress'), $episode_asset->title);
            }
        }

        $wrapper->select('transcript', [
            'label'   => __('Episode Transcript', 'podlove-podcasting-plugin-for-wordpress'),
            'options' => $transcript_options,
        ]);
    }

    /**
     * When vtt media file changes, reimport transcripts.
     */
    public function handle_changed_media_file($media_file_id)
    {
        $media_file = Model\MediaFile::find_by_id($media_file_id);

        if (!$media_file) {
            return;
        }

        $asset = $media_file->episode_asset();

        if (!$asset) {
            return;
        }

        $file_type = $asset->file_type();

        if (!$file_type) {
            return;
        }

        if ($file_type->extension !== 'vtt') {
            return;
        }

        $this->transcript_import_from_asset($media_file->episode());
    }
}
