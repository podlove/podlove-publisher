<?php 
namespace Podlove\Modules\EpisodeNumbering;
use Podlove\Model;

class episode_numbering extends \Podlove\Modules\Base {

	protected $module_name = 'Episode Numbering';
	protected $module_description = 'Enable Numbering for Episodes.';
	protected $module_group = 'metadata';

	public function load() {
		add_action( 'podlove_episode_form', array( $this, 'episode_numbering' ), 10, 2 );
		add_action( 'save_post', array( $this, 'update_episode_numbering' ), 10, 2 );
		add_action( 'podlove_module_was_activated', array( $this, 'add_episode_number_rewrite_rules' ), 10, 2 );	
		add_action( 'podlove_module_was_deactivated', array( $this, 'delete_episode_number_rewrite_rules' ), 10, 2 );	
		add_action( 'init', array( $this, 'add_episode_number_rewrite_tags' ), 10, 2 );		
		add_action( 'wp', array( $this, 'check_for_episode_identifier_and_redirect' ), 10, 2 );	
	}
	
	public function add_episode_number_rewrite_rules() {
		add_rewrite_rule('[Ss](\d+)[Ee](\d+)/?', 'index.php?season=$matches[1]&episode=$matches[2]', 'top');
		add_rewrite_rule('(\D+)(\d+)/?', 'index.php?mnenomic=$matches[1]&episode=$matches[2]', 'top');
		flush_rewrite_rules();
	}
	
	public function delete_episode_number_rewrite_rules() {
		flush_rewrite_rules();
	}
	
	public function add_episode_number_rewrite_tags() {
		add_rewrite_tag('%season%','(\d+)');
		add_rewrite_tag('%episode%','(\d+)');
		add_rewrite_tag('%mnenomic%','(\D+)');
		add_rewrite_tag('%episode%','(\d+)');	
	}
	
	public function check_for_episode_identifier_and_redirect() {
		$episodes = \Podlove\Model\Episode::all();
				
		foreach($episodes as $episode_number => $episode_values) {
			$episode_custom_values = get_post_custom($episode_values->post_id);			
			
			if(isset($episode_custom_values) 
				AND isset($episode_custom_values["_podlove_episode_number"])
				AND count($episode_custom_values["_podlove_episode_number"]) > 0) {
				
				if(isset($episode_custom_values["_podlove_episode_season_number"])) {
					$current_season = $episode_custom_values["_podlove_episode_season_number"][count($episode_custom_values["_podlove_episode_season_number"]) - 1];
				}
				if(isset($episode_custom_values["_podlove_episode_number"])) {
					$current_episode = $episode_custom_values["_podlove_episode_number"][count($episode_custom_values["_podlove_episode_number"]) - 1];
				}
				if(isset($episode_custom_values["_podlove_episode_mnenomic"])) {
					$current_mnenomic = $episode_custom_values["_podlove_episode_mnenomic"][count($episode_custom_values["_podlove_episode_mnenomic"]) - 1];
				}
				
				if(get_query_var("episode") !== ""
				   AND get_query_var("season") !== "") {
						if(	get_query_var("episode") == $current_episode 
							AND get_query_var("season") == $current_season) {
								wp_redirect( home_url()."?p=".$episode_values->post_id ); 
								exit;
						}					
				}

				if(get_query_var("episode") !== ""
				   AND get_query_var("mnenomic") !== "") {
						if(	get_query_var("episode") == $current_episode 
							AND get_query_var("mnenomic") == $current_mnenomic) {
								wp_redirect( home_url()."?p=".$episode_values->post_id ); 
								exit;
						}					
				}						
			}
		}
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
					<input type="text" class="regular-text" id="_podlove_episode_season_number" name="_podlove_episode_season_number" <?php if(isset($custom_fields["_podlove_episode_season_number"])) { echo "value=\"".$custom_fields["_podlove_episode_season_number"][count($custom_fields["_podlove_episode_mnenomic"]) - 1]."\""; } ?> />
					<label for="_podlove_episode_season_number" class="description">Season number</label>
				</div>
				<div>
					<input type="text" class="regular-text" id="_podlove_episode_number" name="_podlove_episode_number" <?php if(isset($custom_fields["_podlove_episode_number"])) { echo "value=\"".$custom_fields["_podlove_episode_number"][count($custom_fields["_podlove_episode_mnenomic"]) - 1]."\""; } ?> />
					<label for="_podlove_episode_number" class="description">Episode number</label>
				</div>
				<div>
					<input type="text" class="regular-text" id="_podlove_episode_mnenomic" name="_podlove_episode_mnenomic" <?php if(isset($custom_fields["_podlove_episode_mnenomic"])) { echo "value=\"".$custom_fields["_podlove_episode_mnenomic"][count($custom_fields["_podlove_episode_mnenomic"]) - 1]."\""; } ?> />
					<label for="_podlove_episode_mnenomic" class="description">Mnenomic</label>
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
		if(isset($_POST["post_ID"])) {
			update_post_meta( $_POST["post_ID"], '_podlove_episode_season_number', $_POST["_podlove_episode_season_number"] );
			update_post_meta( $_POST["post_ID"], '_podlove_episode_number', $_POST["_podlove_episode_number"] );
			update_post_meta( $_POST["post_ID"], '_podlove_episode_mnenomic', $_POST["_podlove_episode_mnenomic"] );
		}
	}

}