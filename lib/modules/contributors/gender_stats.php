<?php 
namespace Podlove\Modules\Contributors;

use \Podlove\Modules\Contributors\Model\EpisodeContribution;
use \Podlove\Modules\Contributors\Model\ContributorGroup;
use \Podlove\Modules\Contributors\Model\ContributorRole;

class GenderStats {

	public static function init() {
		add_action('podlove_dashboard_meta_boxes', array(__CLASS__, 'dashboard_gender_statistics'));
		add_filter('podlove_dashboard_statistics_network', array(__CLASS__, 'dashboard_network_statistics_row'));
	}

	public static function dashboard_gender_statistics() {
		add_meta_box(
			\Podlove\Settings\Dashboard::$pagehook . '_gender',
			__( 'Gender Statistics', 'podlove' ),
			[__CLASS__, 'dashboard_gender_statistics_widget'],
			\Podlove\Settings\Dashboard::$pagehook,
			'normal', 
			'default'
		);
	}

	public static function dashboard_gender_statistics_widget($post) {

		if (EpisodeContribution::count() === 0) {
			?>
			<p>
				<?php echo __('Gender statistics will be available once you start assigning contributors to episodes.', 'podlove') ?>
			</p>
			<?php
			return;
		}

		$gender_distribution = self::fetch_contributors_for_dashboard_statistics();
		?>
		<div class="podlove_gender_widget_column">
			<h4><?php _e('Total', 'podlove'); ?></h4>
			<table cellspacing="0" cellspadding="0">
				<thead>
					<tr>
						<th><?php _e('Female', 'podlove'); ?></th>
						<th><?php _e('Male', 'podlove'); ?></th>
						<th><?php _e('Not Attributed', 'podlove'); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><?php echo self::get_percentage($gender_distribution['global']['by_gender']['female'], $gender_distribution['global']['total']) ?>%</td>
						<td><?php echo self::get_percentage($gender_distribution['global']['by_gender']['male'], $gender_distribution['global']['total']) ?>%</td>
						<td><?php echo self::get_percentage($gender_distribution['global']['by_gender']['none'], $gender_distribution['global']['total']) ?>%</td>
					</tr>
				</tbody>
			</table>
		</div>
		<div class="podlove_gender_widget_column">
			<h4><?php _e('By Group', 'podlove'); ?></h4>
			<?php self::group_or_role_stats_table('group', $gender_distribution['by_group']); ?>
		</div>
		<div class="podlove_gender_widget_column">
			<h4><?php _e('By Role', 'podlove'); ?></h4>
			<?php self::group_or_role_stats_table('role', $gender_distribution['by_role']); ?>
		</div>
		<?php
	}

	private static function get_percentage($value, $relative_to) {

		if ($relative_to === 0)
			return "—";

		return round($value / $relative_to * 100);
	}

	private static function group_or_role_stats_table($context, $numbers) {
		?>
		<table cellspacing="0" cellspadding="0">
			<thead>
				<tr>
					<th><?php echo ( $context == 'group' ? __('Group', 'podlove') : __('Role', 'podlove') ); ?></th>
					<th><?php _e('Female', 'podlove'); ?></th>
					<th><?php _e('Male', 'podlove'); ?></th>
					<th><?php _e('Not Attributed', 'podlove'); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php
			foreach ($numbers as $group_or_role_id => $group_or_role_numbers) {
				$group_or_role = ( $context == 'group' ? ContributorGroup::find_one_by_id($group_or_role_id) : ContributorRole::find_one_by_id($group_or_role_id) ); // This return either a group or a role object	

				if ( !$group_or_role )
					continue;
				?>
					<tr>
						<td><?php echo $group_or_role->title; ?></td>
						<td><?php echo self::get_percentage($group_or_role_numbers['by_gender']['female'], $group_or_role_numbers['total']); ?>%</td>
						<td><?php echo self::get_percentage($group_or_role_numbers['by_gender']['male'],   $group_or_role_numbers['total']); ?>%</td>
						<td><?php echo self::get_percentage($group_or_role_numbers['by_gender']['none'],   $group_or_role_numbers['total']); ?>%</td>
					</tr>
				<?php
			}
			?>
			</tbody>
		</table>
		<?php
	}

	private static function fetch_contributors_for_dashboard_statistics() {
		return \Podlove\Cache\TemplateCache::get_instance()->cache_for('podlove_dashboard_stats_contributors', function() {
			return (new Model\ContributionGenderStatistics)->get();
		}, 3600);
	}

	public static function dashboard_network_statistics_row( $genders ) {
		$podcasts = \Podlove\Modules\Networks\Model\Network::podcast_blog_ids();
		$podcasts_with_contributors_active = 0;
		$relative_gender_numbers = array( 
			'male'   => 0,
			'female' => 0,
			'none'   => 0
		);

		foreach ( $podcasts as $podcast ) {
			switch_to_blog( $podcast );
			if ( \Podlove\Modules\Base::is_active('contributors') ) {
				$global_gender_numbers = self::fetch_contributors_for_dashboard_statistics();
				if ($global_gender_numbers['global']['total'] > 0) {
					foreach ( $global_gender_numbers['global']['by_gender'] as $gender => $number_of_contributions ) {
						 $relative_gender_numbers[$gender] += $number_of_contributions / $global_gender_numbers['global']['total'] * 100;
					}
				}
				$podcasts_with_contributors_active++;
			}
			restore_current_blog();
		}
		?>
		<tr>
			<td class="podlove-dashboard-number-column">
				<?php echo __('Genders', 'podlove') ?>
			</td>
			<td>
				<?php
				echo implode(', ', array_map(function($percent, $gender) use ( $podcasts_with_contributors_active ) {
					return round($percent/$podcasts_with_contributors_active) . "% " . ( $gender == 'none' ? 'not attributed' : $gender );
				}, $relative_gender_numbers, array_keys($relative_gender_numbers)));
				?>
			</td>
		</tr>
		<?php
	}
}
