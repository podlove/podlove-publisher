<?php 
namespace Podlove\Modules\Contributors;
use \Podlove\Model;

class Contributors extends \Podlove\Modules\Base {

	protected $module_name = 'Contributors';
	protected $module_description = 'Manage contributors for each episode.';
	protected $module_group = 'metadata';

	public function load() {
		add_action( 'podlove_module_was_activated_contributors', array( $this, 'was_activated' ) );
		add_action( 'podlove_episode_form_beginning', array( $this, 'contributors_form' ), 10, 2 );
		add_action( 'save_post', array( $this, 'update_contributors' ), 10, 2 );
		add_action( 'wp_enqueue_scripts', array( $this, 'scripts_and_styles' ) );
		add_shortcode( 'podlove-contributors', array( $this, 'display_contributors') );
	}
	
	public function was_activated( $module_name ) {
		Contributor::build();
	}

	public function contributors_form( $wrapper ) {
		$wrapper->callback( 'contributors_form_table', array(
			'label'    => __( 'Contributors', 'podlove' ),
			'callback' => array( $this, 'contributors_form_table' )
		) );		
	}

	public function scripts_and_styles() {
		$module_url = $this->get_module_url();
		wp_register_style( 'podlove-contributors-style', $module_url . '/css/display_contributor.css' );
		wp_enqueue_style( 'podlove-contributors-style' );
	}

	public function display_contributors ( $attributes ) {
		$output = "";
		$output = $output."<script type=\"text/javascript\">
			/* <![CDATA[ */
		    (function() {
  		     var s = document.createElement('script'), t = document.getElementsByTagName('script')[0];
  		     s.type = 'text/javascript';
   		     s.async = true;
    		    s.src = 'http://api.flattr.com/js/0.6/load.js?mode=auto';
    		    t.parentNode.insertBefore(s, t);
   			 })();
			/* ]]> */</script>
		"."\n";
		$output = $output.'<div class="contributor_container">'."\n";

		$contributors = json_decode(get_post_meta(get_the_ID(), '_podlove_episode_contributors')[0]);
		$objectnumbercount = 1;

		if(isset($attributes["delimiter"])) {
			$delimiter = $attributes["delimiter"];
		} else {
			$delimiter = ", ";
		}

		if(count($contributors) > 0) {
			if(isset($attributes["id"])) {
				foreach($contributors as $contributorid => $contributor_details) {
					if(strtoupper($contributor_details->slug) == strtoupper($attributes["id"])) {
						if(isset($attributes["style"])) {
							$output = $output.$this->display_contributor_style_identifier($contributor_details->id, $attributes["style"], 1, 1, $delimiter);
						} else {
							$output = $output.$this->display_contributor_card($contributor_details->id);
						}						
					}
				}
			} else {
				$sorted_contributors = array();
				foreach($contributors as $contributorid => $contributor_details) {
					if(isset($attributes["roles"]) AND strpos($attributes["roles"], $contributor_details->role) !== FALSE) {
						$sorted_contributors[] = $contributor_details->id;
					}
					if(!isset($attributes["roles"]) ) {
						$sorted_contributors[] = $contributor_details->id;
					}
				}

				if(isset($attributes["style"])) {
					foreach($sorted_contributors as $sorted_contributor_id => $sorted_contributor) {
						$output = $output.$this->display_contributor_style_identifier($sorted_contributor, $attributes["style"], $objectnumbercount, count($sorted_contributors), $delimiter);
						$objectnumbercount++;
					}		
					$table_header_included = 0;
				} else {
					foreach($sorted_contributors as $sorted_contributor_id => $sorted_contributor) {
						$output = $output.$this->display_contributor_card($sorted_contributor);
					}
				}
				unset($sorted_contributors);
			}
		} else {
			return "";
		}
		$output = $output.'</div>'."\n";
		return $output;
	}

	public function display_contributor_style_identifier($id, $style, $numberofobjects, $objectnumber, $delimiter) {
		$output = "";
		switch($style) {
			case "plaintext" :
				$output = $output.$this->display_contributor_plaintext($id, $numberofobjects, $objectnumber, $delimiter);
			break;
			case "linkedtext" :
				$output = $output.$this->display_contributor_linkedtext($id, $numberofobjects, $objectnumber, $delimiter);
			break;
			case "cards" :
				$output = $output.$this->display_contributor_card($id);
			break;
			case "light-cards" :
				$output = $output.$this->display_light_contributor_card($id);
			break;
			case "table" :
				$output = $output.$this->display_contributors_table($id, $objectnumber, $numberofobjects);
			break;
			default :
				$output = $output.$this->display_contributor_card($id);
		}
		return $output;
	}

	public function display_contributors_table($id, $objectnumber, $numberofobjects) {
		$contributor = \Podlove\Modules\Contributors\Contributor::find_by_id($id);
		$output = "";
		if($numberofobjects == 1) {
			$output = $output."<table class=\"contributors_table\">";
			$output = $output."	<thead>";
			$output = $output."		<tr>";
			$output = $output."			<th></th>";	
			$output = $output."			<th>Contributor</th>";
			$output = $output."			<th>Contact/Social</th>";
			$output = $output."			<th>Donations</th>";
			$output = $output."		</tr>";
			$output = $output."	<thead>";
			$output = $output."<tbody>";
		}

		if($contributor->showpublic == 1) {
			$output = $output."<tr>\n";
			$output = $output."	<td> </td>\n";
			$output = $output."	<td>".$contributor->publicname."</td>\n";
			$output = $output."	<td>\n";
				if ($contributor->publicemail !== NULL) {
					$output = $output.'<a class="contributor-contact email" href="mailto:'.$contributor->publicemail.'">E-mail</a>'."\n";
				}
				if ($contributor->www !== NULL) {
					$output = $output.'<a class="contributor-contact www" href="'.$contributor->www.'">Homepage</a>'."\n";
				}
				if ($contributor->adn !== NULL) {
					$output = $output.'<a class="contributor-contact adn" href="http://app.net/'.$contributor->adn.'">App.net</a>'."\n";
				}
				if ($contributor->twitter !== NULL) {
				$output = $output.'<a class="contributor-contact twitter" href="http://twitter.com/'.$contributor->twitter.'">Twitter</a>'."\n";
				}
				if ($contributor->facebook !== NULL) {
					$output = $output.'<a class="contributor-contact facebook" href="'.$contributor->facebook.'">Facebook</a>'."\n";
				}
				if ($contributor->wishlist !== NULL) {
					$output = $output.'<a class="contributor-contact wishlist" href="'.$contributor->wishlist.'">Wishlist</a>'."\n";
				}	
			$output = $output." </td>\n";	
			$output = $output."	<td>\n";				
				if ($contributor->flattr !== NULL) {
					$output = $output.'<a class="FlattrButton" style="display:none;" rev="flattr;button:compact;" href="https://flattr.com/profile/'.$contributor->flattr.'"></a>'."\n";
				}
			$output = $output." </td>\n";
			$output = $output."</tr>\n" ;
		}

		if($objectnumber == $numberofobjects) {
			$output = $output."</tbody>";
			$output = $output."</table>";
		}


		return $output;
	}

	public function display_contributor_plaintext($id, $numberofobjects, $objectnumber, $delimiter) {
		$contributor = \Podlove\Modules\Contributors\Contributor::find_by_id($id);
		if($contributor->showpublic == 1) {
			if($objectnumber <= $numberofobjects) {
				return $contributor->publicname;
			} else {
				return $contributor->publicname.$delimiter;
			}
		}
	}

	public function display_contributor_linkedtext($id, $numberofobjects, $objectnumber, $delimiter) {
		$contributor = \Podlove\Modules\Contributors\Contributor::find_by_id($id);
		if($contributor->showpublic == 1) {
			if($objectnumber <= $numberofobjects) {
				if($contributor->wwww !== NULL) {
					return "<a href=\"".$contributor->www."\">".$contributor->publicname."</a>";
				} else {
					return $contributor->publicname;
				}
			} else {
				if($contributor->www !== NULL) {
					return "<a href=\"".$contributor->www."\">".$contributor->publicname."</a>".$delimiter;
				} else {
					return $contributor->publicname.$delimiter;
				}
			}
		}
	}

	public function display_contributor_card($id) {
		$output = "";
		$contributor = \Podlove\Modules\Contributors\Contributor::find_by_id($id);
		if($contributor->showpublic == 1) {
			$output = $output.'<div class="contributor">'."\n";
			$output = $output.'<h1>'.$contributor->publicname.'</h1>'."\n";
			if($contributor->avatar !== NULL AND strpos($contributor->avatar, "@") === FALSE) {
				$output = $output.'<img src="'.$contributor->avatar.'" class="biopic" alt="'.$contributor->publicname.'" title="'.$contributor->publicname.'" />'."\n";
			} else {
				if($contributor->avatar === NULL) {
					$output = $output.'<img src="'.$this->get_gravatar_url("foo@foo.de", 100).'" class="biopic" alt="'.$contributor->publicname.'" title="'.$contributor->publicname.'" />'."\n";
				} else {
					$output = $output.'<img src="'.$this->get_gravatar_url($contributor->avatar, 100).'" class="biopic" alt="'.$contributor->publicname.'" title="'.$contributor->publicname.'" />'."\n";				
				}		
			}
			$output = $output.'<ul class="contributor-contact">'."\n";
			if ($contributor->publicemail !== NULL) {
				$output = $output.'<li><a class="contributor-contact email" href="mailto:'.$contributor->publicemail.'">E-mail</a></li>'."\n";
			}
			if ($contributor->www !== NULL) {
				$output = $output.'<li><a class="contributor-contact www" href="'.$contributor->www.'">Homepage</a></li>'."\n";
			}
			if ($contributor->adn !== NULL) {
				$output = $output.'<li><a class="contributor-contact adn" href="http://app.net/'.$contributor->adn.'">App.net</a></li>'."\n";
			}
			if ($contributor->twitter !== NULL) {
				$output = $output.'<li><a class="contributor-contact twitter" href="http://twitter.com/'.$contributor->twitter.'">Twitter</a></li>'."\n";
			}
			if ($contributor->facebook !== NULL) {
				$output = $output.'<li><a class="contributor-contact facebook" href="'.$contributor->facebook.'">Facebook</a></li>'."\n";
			}
			if ($contributor->wishlist !== NULL) {
				$output = $output.'<li><a class="contributor-contact wishlist" href="'.$contributor->wishlist.'">Wishlist</a></li>'."\n";
			}						
			$output = $output.'</ul>'."\n";
			$output = $output.'<div class="FlattrWrapper">'."\n";
			if ($contributor->flattr !== NULL) {
				$output = $output.'	<a class="FlattrButton" style="display:none;" rev="flattr;button:compact;" href="https://flattr.com/profile/'.$contributor->flattr.'"></a>'."\n";
			}
			$output = $output.'</div>'."\n";
			$output = $output.'</div>'."\n";	
			return $output;
		}
	}

	public function display_light_contributor_card($id) {
		$output = "";
		$contributor = \Podlove\Modules\Contributors\Contributor::find_by_id($id);
		if($contributor->showpublic == 1) {
			$output = $output.'<div class="contributor-light">'."\n";
			$output = $output.'<h1>'.$contributor->publicname.'</h1>'."\n";
			if($contributor->avatar !== NULL AND strpos($contributor->avatar, "@") === FALSE) {
				$output = $output.'<img src="'.$contributor->avatar.'" class="biopic-light" alt="'.$contributor->publicname.'" title="'.$contributor->publicname.'" />'."\n";
			} else {
				if($contributor->avatar === NULL) {
					$output = $output.'<img src="'.$this->get_gravatar_url("foo@foo.de", 100).'" class="biopic-light" alt="'.$contributor->publicname.'" title="'.$contributor->publicname.'" />'."\n";
				} else {
					$output = $output.'<img src="'.$this->get_gravatar_url($contributor->avatar, 100).'" class="biopic-light" alt="'.$contributor->publicname.'" title="'.$contributor->publicname.'" />'."\n";				
				}		
			}
			$output = $output.'<ul class="contributor-contact-light">'."\n";
			if ($contributor->publicemail !== NULL) {
				$output = $output.'<li><a class="contributor-contact-light email" href="mailto:'.$contributor->publicemail.'">E-mail</a></li>'."\n";
			}
			if ($contributor->www !== NULL) {
				$output = $output.'<li><a class="contributor-contact-light www" href="'.$contributor->www.'">Homepage</a></li>'."\n";
			}
			if ($contributor->adn !== NULL) {
				$output = $output.'<li><a class="contributor-contact-light adn" href="http://app.net/'.$contributor->adn.'">App.net</a></li>'."\n";
			}
			if ($contributor->twitter !== NULL) {
				$output = $output.'<li><a class="contributor-contact-light twitter" href="http://twitter.com/'.$contributor->twitter.'">Twitter</a></li>'."\n";
			}
			if ($contributor->facebook !== NULL) {
				$output = $output.'<li><a class="contributor-contact-light facebook" href="'.$contributor->facebook.'">Facebook</a></li>'."\n";
			}
			if ($contributor->wishlist !== NULL) {
				$output = $output.'<li><a class="contributor-contact-light wishlist" href="'.$contributor->wishlist.'">Wishlist</a></li>'."\n";
			}						
			$output = $output.'</ul>'."\n";
			$output = $output.'<div class="FlattrWrapper">'."\n";
			if ($contributor->flattr !== NULL) {
				$output = $output.'	<a class="FlattrButton" style="display:none;" rev="flattr;button:compact;" href="https://flattr.com/profile/'.$contributor->flattr.'"></a>'."\n";
			}
			$output = $output.'</div>'."\n";
			$output = $output.'</div>'."\n";	
			return $output;
		}
	}

	public function update_contributors() {
		if(isset($_POST["post_ID"]) 
			AND isset($_POST["_podlove_episode_contributors"]) 
			AND $_POST["_podlove_episode_contributors"] !== "") {

			$contributors = explode(' ', trim($_POST["_podlove_episode_contributors"]));
			$posted_contributors = array();

			foreach($contributors as $contributorid) {
				$contributor = \Podlove\Modules\Contributors\Contributor::find_by_id($contributorid);
				$posted_contributors[] = array('id' => $contributorid, 'role' => $_POST[$contributorid."-role"], 'slug' => $contributor->slug);
			}

			update_post_meta( $_POST["post_ID"], '_podlove_episode_contributors', json_encode($posted_contributors) );
		}
	}

	public function contributors_form_table() {
		$module_url = $this->get_module_url();
		$all_contributors = \Podlove\Modules\Contributors\Contributor::all();
		$contributors_roles = array("moderator" => "Moderator", "comoderator" => "Co-Moderator", "guest" => "Guest", "camera" => "Camera", "chatmoderator" => "Chat-Moderator", "shownoter" => "Shownoter");
		?>
		</table>
		<style type="text/css">
			#add_new_contributor_selector {
				width: 250px;
			}

			#add_new_contributor_button {
				width: 30px;
				height: 25px;
				font-size: 1.5em;
			}

			#add_new_contributor_selector, #add_new_contributor_button {
				float: right;

			}

			#add_new_contributor_wrapper {
				width: 285px;
				margin: 5px 0px 0px 0px;
			}

			#contributors_table_body select {
				width: 180px;
			}	

			span.contributor_remove {
				font-size: 1.6em;
			}		
		</style>
		<div id="contributors-form">
			<table class="media_file_table" style="margin-top: 1em;" border="0" cellspacing="0">
				<thead>
					<tr>
						<th>Contributor</th>
						<th>Role</th>
						<th>Remove</th>
					</tr>
				</thead>
				<tbody id="contributors_table_body" style="min-height: 50px;">
				<tr class="contributors_table_body_placeholder" style="display: none;"><td><em>No contributors were added yet.</em></td></tr>
					<?php
						$current_page = get_current_screen();
						$contributor_data = get_post_meta( get_the_ID(), "_podlove_episode_contributors");
						$list_of_added_contributors = "";
						$contributor_chosen = "";

						if($current_page->action == "add") {
							$contributors = \Podlove\Modules\Contributors\Contributor::find_all_by_property("permanentcontributor", "1");
							foreach ($contributors as $contributor => $contributor_details) {
								echo '<tr class="media_file_row">';
								echo '	<td>'.$contributor_details->realname.'</td>';
								echo '	<td><select id="'.$contributor_details->id.'-role" name="'.$contributor_details->id.'-role">';
								foreach ($contributors_roles as $contributors_roles_key => $contributors_roles_value) {
									if($contributors_roles_key == $contributor_details->role) {
										echo "<option value=\"".$contributors_roles_key."\" selected>".$contributors_roles_value."</option>";
									} else {
										echo "<option value=\"".$contributors_roles_key."\">".$contributors_roles_value."</option>";									
									}
								}
								echo '	</select></td>';
								echo '	<td><span class="contributor_remove" data-realname="'.$contributor_details->realname.'" data-contributordefaultrole="'.$contributor_details->role.'" data-contributorid="'.$contributor_details->id.'"><i class="clickable podlove-icon-remove"></i></span></td>';
								echo '</tr>';
								$list_of_added_contributors = $list_of_added_contributors.$contributor_details->id." ";
								$contributor_chosen = $contributor_chosen.'jQuery("#'.$contributor_details->id.'-role").chosen();'."\n";
							}
						} else {
							foreach(json_decode($contributor_data[count($contributor_data) - 1]) as $contributor => $contributor_details) {
								$contributor = \Podlove\Modules\Contributors\Contributor::find_by_id($contributor_details->id);
								echo '<tr class="media_file_row">';
								echo '	<td>'.$contributor->realname.'</td>';
								echo '	<td><select id="'.$contributor->id.'-role" name="'.$contributor->id.'-role">';
								foreach ($contributors_roles as $contributors_roles_key => $contributors_roles_value) {
									if($contributors_roles_key == $contributor_details->role) {
										echo "<option value=\"".$contributors_roles_key."\" selected>".$contributors_roles_value."</option>";
									} else {
										echo "<option value=\"".$contributors_roles_key."\">".$contributors_roles_value."</option>";									
									}
								}
								echo '	</select></td>';
								echo '	<td><span class="contributor_remove" data-realname="'.$contributor->realname.'" data-contributordefaultrole="'.$contributor_details->role.'" data-contributorid="'.$contributor_details->id.'"><i class="clickable podlove-icon-remove"></i></span></td>';
								echo '</tr>';
								$list_of_added_contributors = $list_of_added_contributors.$contributor_details->id." ";
								$contributor_chosen = $contributor_chosen.'jQuery("#'.$contributor->id.'-role").chosen();'."\n";
							} 
						}
					?>
				</tbody>
			</table>
			<div id="add_new_contributor_wrapper">
				<select id="add_new_contributor_selector" class="contributor-dropdown">
					<?php
						foreach($all_contributors as $contributor_array_id => $contributor_infos) {
							if(!in_array($contributor_infos->id, explode(' ', $list_of_added_contributors), true)) {
								echo "<option value=".$contributor_infos->id." data-contributordefaultrole=".$contributor_infos->role.">".$contributor_infos->realname."</option>";
							}
						}
					?>
				</select>
				<input class="button" id="add_new_contributor_button" value="+" type="button" />
				<input type="hidden" id="_podlove_episode_contributors" name="_podlove_episode_contributors" value="<?php if(isset($list_of_added_contributors) AND $list_of_added_contributors !== "") { echo $list_of_added_contributors; }?>" />
			</div>
			<script type="text/javascript">
				function roledropdown(id) {
					return '<select name="' + id +'-role" id="' + id +'-role"><option value="moderator">Moderator</option><option value="comoderator">Co-Moderator</option><option value="guest">Guest</option><option value="camera">Camera</option><option value="chatmoderator">Chat-Moderator</option><option value="shownoter">Shownoter</option></select>';
				}

				jQuery(document).on('click', "#add_new_contributor_button", function() {
					jQuery("#contributors_table_body").append('<tr><td>' + jQuery("#add_new_contributor_selector option:selected").text() + '</td><td>' + roledropdown(jQuery("#add_new_contributor_selector option:selected").val()) +'</td><td><span class="contributor_remove" data-realname="' + jQuery("#add_new_contributor_selector option:selected").text() + '" data-contributordefaultrole="' + jQuery("#add_new_contributor_selector option:selected").data("contributordefaultrole") +'" data-contributorid="' + jQuery("#add_new_contributor_selector option:selected").val() +'"><i class="clickable podlove-icon-remove"></i></span></td></tr>');
					jQuery("#_podlove_episode_contributors").val(jQuery("#_podlove_episode_contributors").val() + jQuery("#add_new_contributor_selector :selected").val() + ' ');
					jQuery("#" + jQuery("#add_new_contributor_selector :selected").val() + "-role option[value=" + jQuery("#add_new_contributor_selector :selected").data("contributordefaultrole") +"]").attr('selected',true);
					jQuery("#" + jQuery("#add_new_contributor_selector :selected").val() + "-role").chosen();
					jQuery("#add_new_contributor_selector option:selected").remove();
					jQuery(".contributor-dropdown").trigger("liszt:updated");
					jQuery(".contributors_table_body_placeholder").hide();
					if(jQuery('#add_new_contributor_selector option').size() == 0) {
						jQuery("#add_new_contributor_selector_chzn, #add_new_contributor_button").hide();
					}
				});

				jQuery(document).on('click', '.contributor_remove',  function() {
					jQuery(this).parent().parent().remove();
					jQuery("#add_new_contributor_selector").append('<option value="' + jQuery(this).data("contributorid") + '" data-contributordefaultrole="' + jQuery(this).data("contributordefaultrole") + '">' + jQuery(this).data("realname") +'</option>');
					jQuery("#_podlove_episode_contributors").val(jQuery("#_podlove_episode_contributors").val().replace(jQuery(this).data("contributorid") + ' ', ''));
					jQuery(".contributor-dropdown").trigger("liszt:updated");
					if(jQuery('#contributors_table_body tr').size() == 1) {
						jQuery(".contributors_table_body_placeholder").show();
					}
					if(jQuery('#add_new_contributor_selector option').size() > 0) {
						jQuery("#add_new_contributor_selector_chzn, #add_new_contributor_button").show();
					}
				});

				jQuery(document).ready(function() {
					if(jQuery('#contributors_table_body tr').size() == 1) {
						jQuery(".contributors_table_body_placeholder").show();
					}	
					if(jQuery('#add_new_contributor_selector option').size() == 0) {
						jQuery("#add_new_contributor_selector_chzn, #add_new_contributor_button").hide();
					}				
				});

				jQuery(".contributor-dropdown").chosen();
				<?php
					if(isset($contributor_chosen)) {
						echo $contributor_chosen;
					}
				?>
			</script>
		</div>
		<table class="form-table">
		<?php		
	}

	public function get_gravatar_url( $email, $s = 80, $d = 'mm', $r = 'g', $atts = array() ) {
		$url = 'http://www.gravatar.com/avatar/';
		$url .= md5( strtolower( trim( $email ) ) );
		$url .= "?s=$s&d=$d&r=$r";
		return $url;
	}

}