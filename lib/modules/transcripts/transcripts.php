<?php
namespace Podlove\Modules\Transcripts;

use Podlove\Modules\Transcripts\Model\Transcript;
use Podlove\Modules\Transcripts\Model\VoiceAssignment;
use Podlove\Model;

use Podlove\Webvtt\Parser;
use Podlove\Webvtt\ParserException;

use Podlove\Modules\Contributors\Model\Contributor;

class Transcripts extends \Podlove\Modules\Base {

	protected $module_name = 'Transcripts';
	protected $module_description = 'Manage transcripts, show them on your site and in the web player.';
	protected $module_group = 'metadata';

	public function load()
	{
		add_action('podlove_module_was_activated_transcripts', [$this, 'was_activated']);
		add_filter('podlove_episode_form_data', [$this, 'extend_episode_form'], 10, 2);
		add_action('wp_ajax_podlove_transcript_import', [$this, 'ajax_transcript_import']);
		add_action('wp_ajax_podlove_transcript_get_contributors', [$this, 'ajax_transcript_get_contributors']);
		add_action('wp_ajax_podlove_transcript_get_voices', [$this, 'ajax_transcript_get_voices']);

		add_filter('podlove_episode_data_filter', function ($filter) {
			return array_merge($filter, [
				'transcript_voice'  => [ 'flags' => FILTER_REQUIRE_ARRAY, 'filter' => FILTER_SANITIZE_NUMBER_INT ]
			]);
		});

		add_filter('podlove_episode_data_before_save', function ($data) {

			$post_id = get_the_ID();
			$episode = Model\Episode::find_one_by_post_id($post_id);

			error_log(print_r($episode, true));

			if (!$episode) {
				return $data;
			}

			VoiceAssignment::delete_for_episode($episode->id);

			foreach ($data['transcript_voice'] as $voice => $id) {
				if ($id > 0) {
					$voice_assignment = new VoiceAssignment;
					$voice_assignment->episode_id = $episode->id;
					$voice_assignment->voice = $voice;
					$voice_assignment->contributor_id = $id;
					$voice_assignment->save();
				}
			}

			// not saved in traditional way
			unset($data['transcript_voice']); 
			return $data;
		});

		add_action('wp', [$this, 'serve_transcript_file']);
	}

	public function was_activated($module_name) {
		Transcript::build();
		VoiceAssignment::build();
	}

	public function extend_episode_form($form_data, $episode)
	{
		$form_data[] = array(
			'type' => 'callback',
			'key'  => 'transcripts',
			'options' => array(
				'callback' => function () use ($episode) {
					$data = '';
?>
<div id="podlove-transcripts-app-data" style="display: none"><?php echo $data ?></div>
<div id="podlove-transcripts-app"><transcripts></transcripts></div>
<?php
				},
				'label' => __( 'Transcripts', 'podlove-podcasting-plugin-for-wordpress' )
			),
			'position' => 425
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

		$parser = new Parser();

		try {
			$result = $parser->parse($content);
		} catch (ParserException $e) {
			$error = 'Error parsing webvtt file: ' . $e->getMessage();
			\Podlove\Log::get()->addError($error);
			\Podlove\AJAX\Ajax::respond_with_json(['error' => $error]);
		}

		Transcript::delete_for_episode($episode->id);
		
		foreach ($result['cues'] as $cue) {
			$line = new Transcript;
			$line->episode_id = $episode->id;
			$line->start      = $cue['start'] * 1000;
			$line->end        = $cue['end'] * 1000;
			$line->voice      = $cue['voice'];
			$line->content    = $cue['text'];
			$line->save();
		}

		wp_die();
	}

	public function ajax_transcript_get_contributors()
	{
		$contributors = Contributor::all();
		$contributors = array_map(function ($c) {
			return [
				'id' => $c->id,
				'name' => $c->getName(),
				'identifier' => $c->identifier,
				'avatar' => $c->avatar()->url()
			];
		}, $contributors);

		\Podlove\AJAX\Ajax::respond_with_json(['contributors' => $contributors]);
	}

	public function ajax_transcript_get_voices()
	{
		$post_id = intval($_GET['post_id'], 10);
		$episode = Model\Episode::find_one_by_post_id($post_id);
		$voices = Transcript::get_voices_for_episode_id($episode->id);
		\Podlove\AJAX\Ajax::respond_with_json(['voices' => $voices]);
	}

	public function serve_transcript_file()
	{
		if ( ! is_single() )
			return;

		$format = filter_input(INPUT_GET, 'podlove_transcript', FILTER_VALIDATE_REGEXP, [
			'options' => ['regexp' => "/^(json|webvtt)$/"]
		]);

		if ( ! $format )
			return;

		if ( ! $episode = Model\Episode::find_one_by_post_id( get_the_ID() ) )
			return;

		$renderer = new Renderer($episode);

		switch ($format) {
			case 'webvtt':
				header('Cache-Control: no-cache, must-revalidate');
				header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
				header("Content-Type: text/vtt");
				echo $renderer->as_webvtt();
				exit;
				break;
			case 'json':
				header('Cache-Control: no-cache, must-revalidate');
				header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
				header('Content-type: application/json');
				echo $renderer->as_json();
				exit;
				break;
		}
	}
}
