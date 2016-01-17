<?php
namespace Podlove\Modules\Social;

/**
 * ADN <-> Social integration
 */
class AppDotNet {

	public static function init() {
		add_filter( 'podlove_adn_tags_description_contributors', array(__CLASS__, 'adn_tags_description') );
		add_filter( 'podlove_adn_example_data_contributors', array(__CLASS__, 'adn_example_data'), 10, 4);
		add_filter( 'podlove_adn_tags_contributors_contributors', array(__CLASS__, 'adn_tags'), 10, 4);
		add_action( 'init', array(__CLASS__, 'adn_contributor_filter') );
	}

	public static function adn_tags_description( $description ) {
		return $description . "\n<code title=\"" . __( 'The Contributors of your Epsiode', 'podlove-podcasting-plugin-for-wordpress' ) . "\">{episodeContributors}</code>";
	}

	public static function adn_example_data( $data, $post_id, $selected_role, $selected_group ) {
		$data['contributors'] = self::adn_tags( '{episodeContributors}', $post_id, $selected_role, $selected_group );
		return $data;
	}

	public static function adn_tags( $text, $post_id, $selected_role, $selected_group ) {
    	$contributor_adn_accounts = '';

    	$episode       = \Podlove\Model\Episode::find_or_create_by_post_id( $post_id );
    	$contributions = \Podlove\Modules\Contributors\Model\EpisodeContribution::find_all_by_episode_id( $episode->id );
    	$adn_service   = \Podlove\Modules\Social\Model\Service::find_one_by_property( 'type', 'app.net' );

    	if (!$adn_service)
    		return;

    	foreach ( $contributions as $contribution ) {
    		$contributor_adn_accounts .= '';
    		$social_accounts = \Podlove\Modules\Social\Model\ContributorService::find_all_by_contributor_id( $contribution->contributor_id );

    		array_map( function( $account ) use ( $adn_service, &$contributor_adn_accounts, $contribution, $selected_role, $selected_group ) {
    			if ( $account->service_id == $adn_service->id ) {
    				if ( $selected_role == '' ) {
    					if ( $selected_group == '' ) {
    						$contributor_adn_accounts .= "@" . $account->value . " ";
   						} else {
   		 					if ( $contribution->group_id == $selected_group )
   								$contributor_adn_accounts .= "@" . $account->value . " ";
    					}
    				} else {
						if ( $selected_group == '' && $contribution->role_id == $selected_role ) {
    						$contributor_adn_accounts .= "@" . $account->value . " ";
   						} else {
   		 					if ( $contribution->group_id == $selected_group && $contribution->role_id == $selected_role )
   								$contributor_adn_accounts .= "@" . $account->value . " ";
    					}
    				}
    			}
    		} , $social_accounts );
    	}

    	$total_text_length = strlen( $text ) + strlen( $contributor_adn_accounts );
 		
 		if ( $total_text_length <= 256 )
     		return str_replace( '{episodeContributors}' , $contributor_adn_accounts, $text) ;

    	return str_replace( '{episodeContributors}' , '', $text) ;
	}

	public static function adn_contributor_filter() {

		if (!is_admin())
			return;

		if (!\Podlove\Modules\Base::is_module_settings_page())
			return;

		if (!\Podlove\Modules\Base::is_active('app_dot_net'))
			return;

		$adn = \Podlove\Modules\AppDotNet\App_Dot_Net::instance();

		$roles  = \Podlove\Modules\Contributors\Model\ContributorRole::all();
		$groups = \Podlove\Modules\Contributors\Model\ContributorGroup::all();
		$selected_role  = $adn->get_module_option('adn_contributor_filter_role');
		$selected_group = $adn->get_module_option('adn_contributor_filter_group');

		if ( count($roles) > 0 || count($groups) > 0 ) { 
			$adn->register_option( 'contributor_filter', 'callback', array(
				'label' => __( 'Contributor Filter', 'podlove-podcasting-plugin-for-wordpress' ),
				'description' => __( '<br />Filter <code title="' . __( 'The contributors of the episode', 'podlove' ) . '">{episodeContributors}</code> by Group and/or role', 'podlove-podcasting-plugin-for-wordpress' ),
				'callback' => function() use ( $selected_role, $selected_group, $roles, $groups ) {													
					if ( count($groups) > 0 ) :
					?>
						<select class="chosen" id="podlove_module_app_dot_net_adn_contributor_filter_group" name="podlove_module_app_dot_net[adn_contributor_filter_group]">
							<option value="">&nbsp;</option>
							<?php
								foreach ( $groups as $group ) {
									echo "<option value='" . $group->id . "' " . ( $selected_group == $group->id ? "selected" : "" ) . ">" . $group->title . "</option>";
								}
							?>
						</select> Group
					<?php
					endif;
					if ( count($roles) > 0 ) :
					?>
						<select class="chosen" id="podlove_module_app_dot_net_adn_contributor_filter_role" name="podlove_module_app_dot_net[adn_contributor_filter_role]">
							<option value="">&nbsp;</option>
							<?php
								foreach ( $roles as $role ) {
									echo "<option value='" . $role->id . "' " . ( $selected_role == $role->id ? "selected" : "" ) . ">" . $role->title . "</option>";
								}
							?>
						</select> Role
					<?php 
					endif;
				}
			) );
		}
	}

}