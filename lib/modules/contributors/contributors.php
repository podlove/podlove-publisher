<?php 
namespace Podlove\Modules\Contributors;

use \Podlove\Model;

class Contributors extends \Podlove\Modules\Base {

	protected $module_name = 'Contributors';
	protected $module_description = 'Manage contributors for each episode.';
	protected $module_group = 'metadata';

	public function load() {
		add_action( 'podlove_module_was_activated_contributors', array( $this, 'was_activated' ) );
		add_action( 'podlove_episode_form_beginning', array( $this, 'contributors_form_for_episode' ), 10, 2 );
		add_action( 'save_post', array( $this, 'update_contributors' ), 10, 2 );
		add_action( 'wp_enqueue_scripts', array( $this, 'scripts_and_styles' ) );
		add_action( 'podlove_podcast_form', array( $this, 'podcast_form_extension' ), 10, 2 );
		add_action( 'update_option_podlove_podcast', array( $this, 'save_setting' ), 10, 2 );
		add_shortcode( 'podlove-contributors', array( $this, 'display_contributors') );
	}
	
	public function was_activated( $module_name ) {
		Contributor::build();
		ContributorRole::build();
		EpisodeContribution::build();
		ShowContribution::build();

		if (!ContributorRole::count()) {
			$default_contributors = array(
				'moderator' => 'Moderator',
				'comoderator' => 'Co-Moderator',
				'guest' => 'Guest',
				'shownotes' => 'Shownotes',
				'camera' => 'Camera',
				'chatmod' => 'Chat Moderator'
			);
			foreach ($default_contributors as $slug => $title) {
				$c = new ContributorRole;
				$c->update_attributes(array('slug' => $slug, 'title' => $title));
				$c->save();
			}
		}
	}

	public function migrate_contributors( $module_name ) {

		$episodes = \Podlove\Model\Episode::all();
		$posted_contributors = array();

		$args = array(
			'hierarchical'  => false,
			'labels'        => array(),
			'show_ui'       => true,
			'show_tagcloud' => true,
			'query_var'     => true,
			'rewrite'       => array( 'slug' => 'contributor' ),
		);

		register_taxonomy( 'podlove-contributors', 'podcast', $args );

		foreach(get_terms('podlove-contributors', 'orderby=count&hide_empty=0') as $contributorid => $contributor) {
			$settings = $this->get_additional_settings_for_migration($contributor->term_id);

			if (isset($settings["contributor_email"])) {
				$privateemail = $settings["contributor_email"];
			} else {
				$privateemail = "";
			}

			$contributor_infos = array( "realname" => $contributor->name,
										"publicname" => $contributor->name,
										"slug" => $contributor->slug,
										"id" => $contributor->term_id,
										"showpublic" => 1,
										"privateemail" => $privateemail);

			$contributor_entry = new \Podlove\Modules\Contributors\Contributor;
			$contributor_entry->update_attributes($contributor_infos);
		}

		foreach($episodes as $episode_id => $episode_details) {
			$terms = get_the_terms($episode_details->post_id, 'podlove-contributors');
			if (isset($terms) AND !empty($terms)) {
				foreach($terms as $term_id => $term_details) {
					$posted_contributors[] = array('id' => $term_details->term_id, 'slug' => $term_details->slug);
				}
			}
			if (!empty($posted_contributors)) {
				update_post_meta( $episode_details->post_id, '_podlove_episode_contributors', json_encode($posted_contributors));
			}
		}
	}

	public static function get_additional_settings_for_migration( $term_id ) {
		$all_contributor_settings = get_option( 'podlove_contributors', array() );		
		if ( ! isset( $all_contributor_settings[ $term_id ] ) )
			$all_contributor_settings[ $term_id ] = array();
		return $all_contributor_settings[ $term_id ];
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

		$contributors_raw = get_post_meta(get_the_ID(), '_podlove_episode_contributors');
		$contributors = json_decode($contributors_raw[0]);
		$objectnumbercount = 1;

		if (isset($attributes["delimiter"])) {
			$delimiter = $attributes["delimiter"];
		} else {
			$delimiter = ", ";
		}

		if (count($contributors) > 0) {
			if (isset($attributes["id"])) {
				foreach($contributors as $contributorid => $contributor_details) {
					if (strtoupper($contributor_details->slug) == strtoupper($attributes["id"])) {
						if (isset($attributes["style"])) {
							$output = $output.$this->display_contributor_style_identifier($contributor_details->id, $attributes["style"], 1, 1, $delimiter);
						} else {
							$output = $output.$this->display_contributor_card($contributor_details->id);
						}						
					}
				}
			} else {
				$sorted_contributors = array();
				foreach($contributors as $contributorid => $contributor_details) {
					if (isset($attributes["roles"]) AND strpos($attributes["roles"], $contributor_details->role) !== FALSE) {
						$sorted_contributors[] = $contributor_details->id;
					}
					if (!isset($attributes["roles"]) ) {
						$sorted_contributors[] = $contributor_details->id;
					}
				}

				if (isset($attributes["style"])) {
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
				$output = $this->display_contributor_plaintext($id, $numberofobjects, $objectnumber, $delimiter);
			break;
			case "linkedtext" :
				$output = $this->display_contributor_linkedtext($id, $numberofobjects, $objectnumber, $delimiter);
			break;
			case "cards" :
				$output = $this->display_contributor_card($id);
			break;
			case "light-cards" :
				$output = $this->display_light_contributor_card($id);
			break;
			case "table" :
				$output = $this->display_contributors_table($id, $objectnumber, $numberofobjects);
			break;
			default :
				$output = $this->display_contributor_card($id);
		}

		return $output;
	}

	public function display_contributors_table($id, $objectnumber, $numberofobjects) {
		$contributor = \Podlove\Modules\Contributors\Contributor::find_by_id($id);
		$output = "";
		if ($numberofobjects == 1) {
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

		if (isset($contributor) AND $contributor->showpublic == 1) {
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

		if ($objectnumber == $numberofobjects) {
			$output = $output."</tbody>";
			$output = $output."</table>";
		}


		return $output;
	}

	public function display_contributor_plaintext($id, $numberofobjects, $objectnumber, $delimiter) {
		$contributor = \Podlove\Modules\Contributors\Contributor::find_by_id($id);
		if (isset($contributor) AND $contributor->showpublic == 1) {
			if ($objectnumber <= $numberofobjects) {
				return $contributor->publicname;
			} else {
				return $contributor->publicname.$delimiter;
			}
		}
	}

	public function display_contributor_linkedtext($id, $numberofobjects, $objectnumber, $delimiter) {
		$contributor = \Podlove\Modules\Contributors\Contributor::find_by_id($id);
		if (isset($contributor) AND $contributor->showpublic == 1) {
			if ($objectnumber <= $numberofobjects) {
				if ($contributor->wwww !== NULL) {
					return "<a href=\"".$contributor->www."\">".$contributor->publicname."</a>";
				} else {
					return $contributor->publicname;
				}
			} else {
				if ($contributor->www !== NULL) {
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
		if (isset($contributor) AND $contributor->showpublic == 1) {
			$output = $output.'<div class="contributor">'."\n";
			$output = $output.'<h1>'.$contributor->publicname.'</h1>'."\n";
			if ($contributor->avatar !== NULL AND strpos($contributor->avatar, "@") === FALSE) {
				$output = $output.'<img src="'.$contributor->avatar.'" class="biopic" alt="'.$contributor->publicname.'" title="'.$contributor->publicname.'" />'."\n";
			} else {
				if ($contributor->avatar === NULL) {
					$output = $output.'<img src="'.self::get_gravatar_url("foo@foo.de", 100).'" class="biopic" alt="'.$contributor->publicname.'" title="'.$contributor->publicname.'" />'."\n";
				} else {
					$output = $output.'<img src="'.self::get_gravatar_url($contributor->avatar, 100).'" class="biopic" alt="'.$contributor->publicname.'" title="'.$contributor->publicname.'" />'."\n";				
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
		if (isset($contributor) AND $contributor->showpublic == 1) {
			$output = $output.'<div class="contributor-light">'."\n";
			$output = $output.'<h1>'.$contributor->publicname.'</h1>'."\n";
			if ($contributor->avatar !== NULL AND strpos($contributor->avatar, "@") === FALSE) {
				$output = $output.'<img src="'.$contributor->avatar.'" class="biopic-light" alt="'.$contributor->publicname.'" title="'.$contributor->publicname.'" />'."\n";
			} else {
				if ($contributor->avatar === NULL) {
					$output = $output.'<img src="'.self::get_gravatar_url("foo@foo.de", 100).'" class="biopic-light" alt="'.$contributor->publicname.'" title="'.$contributor->publicname.'" />'."\n";
				} else {
					$output = $output.'<img src="'.self::get_gravatar_url($contributor->avatar, 100).'" class="biopic-light" alt="'.$contributor->publicname.'" title="'.$contributor->publicname.'" />'."\n";				
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

	public function update_contributors($post_id)
	{
		if (!isset($_POST["episode_contributor"]))
			return;
		
		$episode = Model\Episode::find_one_by_post_id($post_id);

		foreach (EpisodeContribution::find_all_by_episode_id($episode->id) as $contribution) {
			$contribution->delete();
		}

		$position = 0;
		foreach ($_POST["episode_contributor"] as $contributor_id => $contributor) {
			$c = new EpisodeContribution;
			$c->role_id = ContributorRole::find_one_by_slug($contributor['role'])->id;
			$c->episode_id = $episode->id;
			$c->contributor_id = $contributor_id;
			$c->position = $position++;
			$c->save();
		}
	}

	public function contributors_form_for_episode( $wrapper ) {
		$wrapper->callback( 'contributors_form_table', array(
			'label'    => __( 'Contributors', 'podlove' ),
			'callback' => function() {

				$current_page = get_current_screen();
				$episode = Model\Episode::find_one_by_post_id(get_the_ID());
				
				// determine existing contributions
				$contributions = array();
				if ($current_page->action == "add") {
					$permanent_contributors = Contributor::find_all_by_property("permanentcontributor", "1");
					foreach ($permanent_contributors as $permanent_contributor) {
						$contrib = new EpisodeContribution;
						$contrib->contributor_id = $permanent_contributor->id;
						$contrib->role = ContributorRole::find_by_id($permanent_contributor->role_id);
						$contributions[] = $contrib;
					}
				} else {
					$contributions = EpisodeContribution::all("WHERE `episode_id` = " . $episode->id . " ORDER BY `position` ASC");
				}

				$this->contributors_form_table($contributions);
			}
		) );		
	}

	/**
	 * Contributors extension for podcast settings screen.
	 * 
	 * @param  TableWrapper $wrapper form wrapper
	 * @param  Podcast      $podcast podcast model
	 */
	public function podcast_form_extension($wrapper, $podcast)
	{
		$wrapper->subheader(
			__( 'Contributors', 'podlove' ),
			__( 'You may define contributors for the whole podcast.', 'podlove' )
		);

    	$wrapper->callback( 'contributors', array(
			'label'    => __( 'Contributors', 'podlove' ),
			'callback' => array( $this, 'podcast_form_extension_form' )
		) );
	}

	public function podcast_form_extension_form()
	{
		$contributions = ShowContribution::all();
		$this->contributors_form_table($contributions, 'podlove_podcast[contributor]');
	}

	public function save_setting($old, $new)
	{
		if (!isset($new['contributor']))
			return;

		$contributors = $new['contributor'];

		foreach (ShowContribution::all() as $contribution) {
			$contribution->delete();
		}

		$position = 0;
		foreach ($contributors as $contributor_id => $contributor) {
			$c = new ShowContribution;
			$c->role_id = ContributorRole::find_one_by_slug($contributor['role'])->id;
			$c->contributor_id = $contributor_id;
			$c->position = $position++;
			$c->save();
		}
	}

	public function contributors_form_table($current_contributions = array(), $form_base_name = 'episode_contributor') {
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
						<th style="width: 60px">Remove</th>
						<th style="width: 30px"></th>
					</tr>
				</thead>
				<tbody id="contributors_table_body" style="min-height: 50px;">
					<tr class="contributors_table_body_placeholder" style="display: none;">
						<td><em><?php echo __('No contributors were added yet.', 'podlove') ?></em></td>
					</tr>
				</tbody>
			</table>

			<?php
			$contributors_roles = ContributorRole::selectOptions();
			?>

			<div id="add_new_contributor_wrapper">
				<select id="add_new_contributor_selector" class="contributor-dropdown chosen">
					<?php foreach ( Contributor::all() as $contributor ): ?>
						<?php if (!in_array($contributor->id, array_map(function($c){ return $c->contributor_id; }, $current_contributions), true)): ?>
							<option value="<?php echo $contributor->id ?>" data-contributordefaultrole="<?php echo $contributor->role ?>">
								<?php echo $contributor->realname; ?>
							</option>
						<?php endif; ?>
					<?php endforeach; ?>
				</select>
				<input class="button" id="add_new_contributor_button" value="+" type="button" />
			</div>

			<script type="text/template" id="contributor-row-template">
			<tr class="media_file_row" data-contributor-id="{{contributor-id}}">
				<td>{{contributor-name}}</td>
				<td>
					<select name="<?php echo $form_base_name ?>[{{contributor-id}}][role]" class="chosen">
						<?php foreach ( $contributors_roles as $role_slug => $role_title ): ?>
							<option value="<?php echo $role_slug ?>"><?php echo $role_title ?></option>
						<?php endforeach; ?>
					</select>
				</td>
				<td>
					<span class="contributor_remove">
						<i class="clickable podlove-icon-remove"></i>
					</span>
				</td>
				<td class="move column-move"><i class="reorder-handle podlove-icon-reorder"></i></td>
			</tr>
			</script>

			<script type="text/javascript">
				var PODLOVE = PODLOVE || {};
				var existing_contributions = [<?php echo implode(",", array_map(function($c){ return $c->contributor_id; }, $current_contributions)) ?>];

				<?php 
				$cjson = array();
				foreach (Contributor::all() as $contributor) {
					$cjson[$contributor->id] = array(
						'id'   => $contributor->id,
						'slug' => $contributor->slug,
						'role' => $contributor->role,
						'realname' => $contributor->realname,
						'permanentcontributor' => $contributor->permanentcontributor
					);
				}

				// override contributor roles with scoped roles
				foreach ($current_contributions as $current_contribution) {
					$cjson[$current_contribution->contributor_id]['role'] = $current_contribution->getRole()->slug;
				}
				?>

				PODLOVE.Contributors = <?php echo json_encode($cjson); ?>;

				(function($) {

					function determine_blank_slate_visibility() {
						var placeholder = $(".contributors_table_body_placeholder");

						if ($('#contributors_table_body tr').size() > 0) {
							placeholder.hide();
						} else {
							placeholder.show();
						}
					}

					function determine_contributor_selector_visibility() {
						var contributor_selector = $("#add_new_contributor_selector_chzn, #add_new_contributor_button");

						if ($('#add_new_contributor_selector option').size() == 0) {
							contributor_selector.hide();
						} else {
							contributor_selector.show();
						}
					}

					function update_contributor_list() {
						$(".chosen").chosen().trigger("liszt:updated");
						determine_blank_slate_visibility();
						determine_contributor_selector_visibility();
					}

					function add_contributor_row(contributor) {
						var row = '';

						// add contributor to table
						row = $("#contributor-row-template").html();
						row = row.replace(/\{\{contributor-name\}\}/g, contributor.realname);
						row = row.replace(/\{\{contributor-id\}\}/g, contributor.id);
						el = $("#contributors_table_body").append(row);
						
						var new_row = $("#contributors_table_body tr:last");

						// select default role
						new_row.find('select option[value="' + contributor.role + '"]').attr('selected',true);
					}

					$(document).on('click', "#add_new_contributor_button", function() {
						var selected_contributor = $("#add_new_contributor_selector :selected"),
							contributor_id = selected_contributor.val(),
							contributor = PODLOVE.Contributors[contributor_id];

						add_contributor_row(contributor);

						// remove contributor from select
						selected_contributor.remove();

						update_contributor_list();
					});

					$(document).on('click', '.contributor_remove',  function() {
						var contributor_id = $(this).closest("tr").data('contributor-id'),
							contributor = PODLOVE.Contributors[contributor_id];

						// remove this contributor row
						$(this).closest("tr").remove();

						// add to list of available contributors
						var option = '<option value="' + contributor_id + '">' + contributor.realname + '</option>';

						$("#add_new_contributor_selector").append(option);

						update_contributor_list();
					});

					$(document).ready(function() {
						update_contributor_list();

						$.each(existing_contributions, function(index, contributor_id) {
							add_contributor_row(PODLOVE.Contributors[contributor_id]);
						});

						$("#contributors_table_body td").each(function(){
						    $(this).css('width', $(this).width() +'px');
						});

						$("#contributors_table_body").sortable({
							handle: ".reorder-handle",
							helper: function(e, tr) {
							    var $originals = tr.children();
							    var $helper = tr.clone();
							    $helper.children().each(function(index) {
							    	// Set helper cell sizes to match the original sizes
							    	$(this).width($originals.eq(index).width());
							    });
							    return $helper.css({
							    	background: '#EAEAEA'
							    });
							}
						});
					});
				}(jQuery));

			</script>
		</div>
		<table class="form-table">
		<?php		
	}

	/**
	 * Get Gravatar URL for a specified email address.
	 *
	 * Yes, I know there is get_avatar() but that returns the img tag and I need the URL.
	 *
	 * @param string $email The email address
	 * @param string $s Size in pixels, defaults to 80px [ 1 - 2048 ]
	 * @param string $d Default imageset to use [ 404 | mm | identicon | monsterid | wavatar ]
	 * @param string $r Maximum rating (inclusive) [ g | pg | r | x ]
	 * @param array $atts Optional, additional key/value attributes to include in the IMG tag
	 * @return String containing either just a URL or a complete image tag
	 * @source http://gravatar.com/site/implement/images/php/
	 */
	public static function get_gravatar_url( $email, $s = 80, $d = 'mm', $r = 'g', $atts = array() ) {
		$url = 'http://www.gravatar.com/avatar/';
		$url .= md5( strtolower( trim( $email ) ) );
		$url .= "?s=$s&d=$d&r=$r";
		return $url;
	}

}