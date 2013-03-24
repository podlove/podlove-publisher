<?php 
namespace Podlove\Modules\AuphonicProductionData;
use \Podlove\Model;

class Auphonic_Production_Data extends \Podlove\Modules\Base {

	protected $module_name = 'Auphonic Production Data';
	protected $module_description = 'Parse meta data from auphonic production data.';

	public function load() {

		add_action( 'add_meta_boxes', function () {
			add_meta_box( 'tagsdiv-auphonic-production-data',  __( 'Auphonic Production Data', 'podlove' ), array( &$this, 'metabox' ), 'podcast', 'side', 'default' );  
		});

	}

	public function metabox( $post ) {
		$podcast = Model\Podcast::get_instance();	
		$episode = Model\Episode::find_or_create_by_post_id( $post->ID );

		// here I'd actually like to check whether $episode->slug isset(), but somehow that's always true.

		$url = $podcast->media_file_base_uri . $episode->slug . '.json';
		$prod_data = file_get_contents($url);
		if (isset($prod_data)) {
			$prod_info = json_decode($prod_data);
			?>
			<script>
			function setData() {
				var form = document.getElementById('post');
				<?php 
				  if (isset($prod_info->metadata->title)) {
				  	?>form.elements.post_title.value = '<?php echo($prod_info->metadata->title)?>';<?php 
				  }
					  if (isset($prod_info->metadata->subtitle)) {
				  	?>form.elements._podlove_meta_subtitle.value = '<?php echo($prod_info->metadata->subtitle)?>';<?php 
				  }
				      if (isset($prod_info->metadata->summary)) {
				  	?>form.elements._podlove_meta_summary.value = '<?php echo($prod_info->metadata->summary)?>';<?php 
				  }
				  if (isset($prod_info->length_timestring)) {
				  	?>form.elements._podlove_meta_duration.value = '<?php echo($prod_info->length_timestring)?>';<?php 
				  }
				?>
			}
			</script>
			<input id="add_auphonic_meta_data" type="button" value="Add meta data" onclick="setData();" />
			<?php
		} else {
			echo("Could not parse data.");
		}
	}
}