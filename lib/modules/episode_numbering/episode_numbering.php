<?php 
namespace Podlove\Modules\EpisodeNumbering;
use Podlove\Model;

// Adding Custrom URL stuff

    	

class episode_numbering extends \Podlove\Modules\Base {

	protected $module_name = 'Episode Numbering';
	protected $module_description = 'Enable Numbering for Episodes.';
	protected $module_group = 'metadata';

	public function load() {
	
		add_action( 'podlove_episode_form', array( $this, 'episode_numbering' ), 10, 2 );
		add_action( 'save_post', array( $this, 'update_episode_numbering' ), 10, 2 );
		
	}
	
    public function episode_numbering( $wrapper ) {
    	$wrapper->callback( 'episode_numbering_form_field', array(
			'label'    => __( 'Episode Numbering', 'podlove' ),
			'callback' => array( $this, 'episode_numbering_form_field' )
		) );			
    }
	
	public function episode_numbering_form_field() {
		$custom_fields = get_post_custom(get_the_ID());
		?>
			<div id="_podlove_meta_episode_numbering_form_field">
				<div>
					<input type="text" class="regular-text" id="_podlove_episode_season_number" name="_podlove_episode_season_number" <?php if(isset($custom_fields["_podlove_episode_season_number"][count($custom_fields["_podlove_episode_mnemomic"]) - 1])) { echo "value=\"".$custom_fields["_podlove_episode_season_number"][count($custom_fields["_podlove_episode_mnemomic"]) - 1]."\""; } ?> />
					<label for="_podlove_episode_season_number" class="description">Season number</label>
				</div>
				<div>
					<input type="text" class="regular-text" id="_podlove_episode_number" name="_podlove_episode_number" <?php if(isset($custom_fields["_podlove_episode_number"][count($custom_fields["_podlove_episode_mnemomic"]) - 1])) { echo "value=\"".$custom_fields["_podlove_episode_number"][count($custom_fields["_podlove_episode_mnemomic"]) - 1]."\""; } ?> />
					<label for="_podlove_episode_number" class="description">Episode number</label>
				</div>
				<div>
					<input type="text" class="regular-text" id="_podlove_episode_mnemomic" name="_podlove_episode_mnemomic" <?php if(isset($custom_fields["_podlove_episode_mnemomic"][count($custom_fields["_podlove_episode_mnemomic"]) - 1])) { echo "value=\"".$custom_fields["_podlove_episode_mnemomic"][count($custom_fields["_podlove_episode_mnemomic"]) - 1]."\""; } ?> />
					<label for="_podlove_episode_mnemomic" class="description">Mnemomic</label>
				</div>
			</div>
			
			<style type="text/css">
				div#_podlove_meta_episode_numbering_form_field div {
					width: 200px;
					display: inline-block;
				}
				
				label.description {
					font-style: italic;
				}
			</style>
		<?php
	}
	
	public function update_episode_numbering() {
		add_post_meta( $_POST["post_ID"], '_podlove_episode_season_number', $_POST["_podlove_episode_season_number"] ) || update_post_meta( $_POST["post_ID"], '_podlove_episode_season_number', $_POST["_podlove_episode_season_number"] );
		add_post_meta( $_POST["post_ID"], '_podlove_episode_number', $_POST["_podlove_episode_number"] ) || update_post_meta( $_POST["post_ID"], '_podlove_episode_number', $_POST["_podlove_episode_number"] );
		add_post_meta( $_POST["post_ID"], '_podlove_episode_mnemomic', $_POST["_podlove_episode_mnemomic"] ) || update_post_meta( $_POST["post_ID"], '_podlove_episode_mnemomic', $_POST["_podlove_episode_mnemomic"] );
	}

}