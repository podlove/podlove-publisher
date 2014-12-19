<?php
require_once 'vendor/autoload.php';

if (!getenv('WPBASE')) {
	die("You need to set the environment variable WPBASE to your WordPress root\n");
}

require_once dirname(__FILE__) . '/../lib/helper.php';
require_once getenv('WPBASE') . '/wp-load.php';
require_once dirname(__FILE__) . '/../bootstrap/bootstrap.php';

$parser = new Console_CommandLine();
$parser->description = 'Generate test data for WordPress';
$parser->version = '1.0';

$downloadsCommand = $parser->addCommand('downloads', array(
	'description' => 'generate download intents'
));

$downloadsCommand->addOption('episode', array(
   'short_name'  => '-e',
   'long_name'   => '--episode',
   'action'      => 'StoreInt',
   'description' => 'episode id to generate data for'
));

$downloadsCommand->addOption('post', array(
   'short_name'  => '-p',
   'long_name'   => '--post',
   'action'      => 'StoreInt',
   'description' => 'post id related to the episode id to generate data for. is ignored if the episode option is set'
));

$downloadsCommand->addOption('number', array(
	'short_name'  => '-n',
	'long_name'   => '--number',
	'description' => 'number of downloads to generate',
	'action'      => 'StoreInt',
	'default'     => 100
));

$downloadsCommand->addOption('source', array(
	'short_name'  => '-s',
	'long_name'   => '--source',
	'description' => 'download source',
	'action'      => 'StoreString',
	'default'     => 'generator'
));

$downloadsCommand->addOption('context', array(
	'short_name'  => '-c',
	'long_name'   => '--context',
	'description' => 'download context',
	'action'      => 'StoreString',
	'default'     => ''
));

$downloadsCommand->addOption('timespan', array(
	'short_name'  => '-t',
	'long_name'   => '--timespan',
	'description' => 'timespan to distribute downloads in. Either "now" or an integer denoting the number of days to the past.',
	'action'      => 'StoreString',
	'default'     => 'now'
));

try {
	$result = $parser->parse();
	// find which command was entered
	switch ($result->command_name) {
	case 'downloads':
		generate_downloads($result->command);
		exit(0);
	default:
		// no command entered
		exit(0);
	}
} catch (Exception $exc) {
	$parser->displayError($exc->getMessage());
}

/**
 * returns float between 0 and 1 inclusive, in a bell curve shape
 * @return float
 */
function bellcurve() {
    $sum = 0;
    $entropy = 6;
    for($i=0; $i<$entropy; $i++) $sum += rand(0,15);
    return ($sum+rand(0,1000000)/1000000)/(15*$entropy);
}

function generate_downloads($command) {
	$episode_id = $command->options['episode'];
	$post_id    = $command->options['post'];
	$number     = $command->options['number'];
	$source     = $command->options['source'];
	$context    = $command->options['context'];
	$timespan   = $command->options['timespan'];

	if (!$episode_id) {

		if (!$post_id)
			die("either 'episode' or 'post' must be given");

		$post = get_post($post_id);
		$episode = \Podlove\Model\Episode::find_one_by_post_id($post_id);
	} else {
		$episode = \Podlove\Model\Episode::find_one_by_id($episode_id);
	}

	if (!$episode)
		die("no episode for id '$episode_id'");

	$files = $episode->media_files();

	if (!count($files))
		die("no files for episode with episode_id '$episode_id' or post_id '$post_id'");

	for ($i=0; $i < $number; $i++) { 
		$file = $files[array_rand($files)];
		$di = new \Podlove\Model\DownloadIntent;
		$di->media_file_id = $file->id;

		if ($timespan == "now") {
			$di->accessed_at = date('Y-m-d H:i:s');
		} else {
			$di->accessed_at = date('Y-m-d H:i:s', strtotime( round(bellcurve() * $timespan) . " days ago"));
		}

		$di->source = $source;
		$di->context = $context;
		$di->save();
	}
}

