<?php
namespace Podlove\Modules\EpisodeAssistant;

class Episode_Assistant extends \Podlove\Modules\Base {

	protected $module_name = 'Episode Assistant';
	protected $module_description = <<<EOT
Adds more conventions to episodes and uses them to automate the episode creation process.
<ul style="list-style-type: disc; margin-left: 50px">
  <li>introduces episode numbers</li>
  <li>guesses next episode number for new episodes</li>
  <li>configurable episode title format</li>
</ul>
EOT;

	public function load() {
		# code...
	}

}