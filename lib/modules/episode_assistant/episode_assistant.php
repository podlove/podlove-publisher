<?php
namespace Podlove\Modules\EpisodeAssistant;
use \Podlove\Model;

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

		$this->register_option( 'title_template', 'string', array(
			'label'       => __( 'Title Template', 'podlove' ),
			'description' => __( 'Placeholders: %podcast_slug%, %episode_number%, %episode_title%', 'podlove' ),
			'default'     => '%podcast_slug%%episode_number% %episode_title%',
			'html'        => array( 'class' => 'regular-text' )
		) );

		$this->register_option( 'leading_zeros', 'select', array(
			'label'       => __( 'Number Digits', 'podlove' ),
			'description' => __( 'Add leading zeroes to episode number. Example: 003 instead of 3.', 'podlove' ),
			'default'     => 3,
			'options'     => array( 'no' => 'no', 1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5 )
		) );

		if ( is_admin() ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'register_assets' ) );
			add_action( 'admin_footer', array( $this, 'modal_box_html' ) );
		}
	}

	public function register_assets() {
		wp_register_script(
			'podlove_module_episode_assistant',
			$this->get_module_url() . '/js/episode_assistant.js',
			array( 'jquery', 'jquery-ui-core', 'jquery-ui-button', 'jquery-ui-dialog' ),
			'1.2' 
		);

		// see http://www.arashkarimzadeh.com/jquery/7-editable-jquery-plugin.html
		wp_register_script(
			'jquery-editable',
			$this->get_module_url() . '/js/jquery.editable-1.3.3.min.js',
			array( 'jquery' ),
			'1.3.3'
		);

		wp_enqueue_script( 'jquery-editable' );
		wp_enqueue_script( 'podlove_module_episode_assistant' );

		// TODO: not sure if we should bundle our own theme
		wp_register_style( 'jquery-style', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.1/themes/smoothness/jquery-ui.css', true);
		wp_enqueue_style( 'jquery-style' );
	}

	/**
	 * Try to guess next episode number/id from previous slug.
	 *
	 * The slug is expected to consist of the podcast slug and the episode number.
	 * Something like these: cre162, ppp000, wrint42
	 * I am looking for the first number and add one. If there is no episode,
	 * start with number 1.
	 * 
	 * Add leading zeroes if it is configured.
	 * 
	 * @return string episode number/id with or without leading zeroes
	 */
	public function guess_next_episode_id_for_show() {

		// try to derive next number from previous episode slug
		$number = 1;
		$episode = Model\Episode::last();
		if ( $episode && preg_match( "/\d+/", $episode->slug, $matches ) ) {
			$number = (int) $matches[0] + 1;
		}
		$number = "$number";

		// add leading zeros
		$leading_zeros = $this->get_module_option( 'leading_zeros', 3 );
		if ( $leading_zeros !== 'no' ) {
			while ( strlen( $number ) < $leading_zeros ) {
				$number = "0$number";
			}
		}

		return $number;
	}

	public function modal_box_html() {

		$podcast = Model\Podcast::get_instance();
		$episode_assets = Model\EpisodeAsset::all();

		if ( ! $episode_assets )
			return;

		$episode_asset  = $episode_assets[0];

		$podcast_data = array(
			'slug'        => $podcast->slug,
			'name'        => $podcast->title,
			'next_number' => $this->guess_next_episode_id_for_show(),
			'base_url'    => $podcast->media_file_base_uri,
			'episode_asset' => array(
				'suffix'   => $episode_asset->suffix ? $episode_asset->suffix : "",
				'template' => $podcast->url_template
			)
		);
		?>
		<div id="new-episode-modal" class="hidden wrap" title="Create New Episode">
			<div class="hidden" id="new-episode-podcast-data"><?php echo json_encode( $podcast_data ) ?></div>
			<p>
				<div id="titlediv">
					<p>
						<strong>Episode Number</strong>
						<input type="text" name="episode_number" value="<?php echo $this->guess_next_episode_id_for_show(); ?>" class="really-huge-text episode_number" autocomplete="off">
					</p>
					<p>
						<strong>Episode Title</strong>
						<input type="text" name="episode_title" value="" class="really-huge-text episode_title" autocomplete="off">
					</p>
					<p class="media_file_info result">
						<strong>Media Files</strong>
						<span class="url">Loading ...</span>
					</p>
					<p class="post_info result">
						<strong>Post Title</strong>
						<span class="post_title" data-template="<?php echo $this->get_module_option( 'title_template', '%podcast_slug%%episode_number% %episode_title%' ) ?>">Loading ...</span>
					</p>
				</div>
			</p>
		</div>

		<style type="text/css">
		#new-episode-modal .media_file_info, #new-episode-modal .post_info {
			color: #666;
		}

		#new-episode-modal p.result strong {
			display: inline-block;
			width: 115px;
		}

		#episode_file_slug {
			cursor: pointer;
			font-style: italic;
			color: black;
		}

		#episode_file_slug input {
			width: 70px;
			-webkit-border-radius: 3px;
			border-radius: 3px;
			border-width: 1px;
			border-style: solid;
			border-color: #DFDFDF;
		}

		input.really-huge-text {
			padding: 3px 8px;
			font-size: 1.7em;
			line-height: 100%;
			width: 100%;
		}
		</style>
		<?php
	}

}