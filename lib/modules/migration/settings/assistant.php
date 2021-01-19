<?php

namespace Podlove\Modules\Migration\Settings;

use Podlove\Model;
use Podlove\Modules\Migration\Enclosure;
use Podlove\Modules\Migration\Migration;

class Assistant
{
    public static $pagehook;

    public function __construct($handle)
    {
        Assistant::$pagehook = add_submenu_page(
            // $parent_slug
            $handle,
            // $page_title
            'Migration Assistant',
            // $menu_title
            'Migration Assistant',
            // $capability
            'administrator',
            // $menu_slug
            'podlove_settings_migration_handle',
            // $function
            [$this, 'page']
        );

        if (isset($_REQUEST['reset_migration']) && $_REQUEST['reset_migration']) {
            $this->reset_migration();
            wp_redirect(admin_url('admin.php?page=podlove_settings_migration_handle'));
        }
    }

    public static function get_page_link($step = 1)
    {
        return sprintf('?page=%s&step=%s', 'podlove_settings_migration_handle', $step);
    }

    public function process_request()
    {
        if (!isset($_REQUEST['podlove_migration'])) {
            return;
        }

        $migration_settings = get_option('podlove_migration', []);

        foreach ($_REQUEST['podlove_migration'] as $setting_key => $setting_value) {
            $migration_settings[$setting_key] = $setting_value;
        }

        update_option('podlove_migration', $migration_settings);
    }

    public function page()
    {
        set_time_limit(1800); // increase max_execution_time for migration actions

        $wizard = [
            new Wizard\StepWelcome(),
            new Wizard\StepBasics(),
            new Wizard\StepPosts(),
            new Wizard\StepMigrate(),
            new Wizard\StepFinalize(),
        ];

        // start-index must be 1, not 0
        array_unshift($wizard, 'whatever');
        unset($wizard[0]);

        $this->process_request();

        $steps = array_map(function ($step) {
            return $step->title;
        }, $wizard);

        if (isset($_REQUEST['prev']) || isset($_REQUEST['next']) || isset($_REQUEST['stay'])) {
            $current_step = Migration::instance()->get_module_option('current_step', 1);
            if (isset($_REQUEST['next'])) {
                ++$current_step;
                Migration::instance()->update_module_option('current_step', $current_step);
            } elseif (isset($_REQUEST['prev'])) {
                --$current_step;
                Migration::instance()->update_module_option('current_step', $current_step);
            }
        } elseif (isset($_REQUEST['step']) && $_REQUEST['step'] > 0 && $_REQUEST['step'] <= count($steps)) {
            $current_step = (int) $_REQUEST['step'];
            Migration::instance()->update_module_option('current_step', $current_step);
        } else {
            $current_step = Migration::instance()->get_module_option('current_step', 1);
        }

        if ($current_step < 1) {
            $current_step = 1;
        } ?>

		<script type="text/javascript">
		jQuery(function($) {
			$('[data-toggle="tooltip"]').tooltip();
		});
		</script>

		<div class="wrap bootstrap">
			<h2 id="migration_header">
				<?php echo __('Migration Assistant'); ?>
				<p class="lead">
					Migrate your existing episodes to the Podlove Publisher.
					<br>
					If you get stuck, feel free to reset the migration and
					<span class="btn-group">
						<a href="<?php echo admin_url('admin.php?page=podlove_settings_migration_handle&step=1&reset_migration=1'); ?>"
						   class="btn btn-small"
						   data-placement="bottom"
						   data-toggle="tooltip"
						   title="<?php echo __('Deletes all episodes, assets, feeds and migration settings.', 'podlove-podcasting-plugin-for-wordpress'); ?>"
						   >
							<?php echo __('start from scratch', 'podlove-podcasting-plugin-for-wordpress'); ?>
						</a>
					</span>.
					<br>
					There's also <a href="http://docs.podlove.org/podlove-publisher/guides/podcast-migration.html" target="_blank">documentation</a> if you'd like to get into details.
				</p>
			</h2>

			<hr>

			<ul class="nav nav-pills">
				<?php foreach ($steps as $index => $title) { ?>
					<?php
                    $class = $index === $current_step ? 'active' : ($current_step < $index ? 'disabled' : '');
        if ($index > 1) {
            $title = sprintf(__('Step %s:', 'podlove-podcasting-plugin-for-wordpress'), $index - 1).' '.$title;
        }
        $link = ($class == 'disabled') ? '#' : self::get_page_link($index); ?>
					<li class="<?php echo $class; ?>">
						<a href="<?php echo $link; ?>"><?php echo $title; ?></a>
					</li>
				<?php } ?>
			</ul>

			<?php
            $wizard[$current_step]->template(); ?>
		</div>	
		<?php
    }

    public static function get_episode_slug($post, $slug_type = 'wordpress')
    {
        switch ($slug_type) {
            case 'wordpress':
                return $post->post_name;

                break;
            case 'number':
                return self::get_number_slug($post);

                break;
            case 'file':
                return self::get_file_slug($post);

                break;
        }
    }

    public static function get_number_slug($post)
    {
        if (preg_match('/\\d+/', \get_the_title($post->ID), $matches)) {
            return $matches[0];
        }

        return '';
    }

    public static function get_file_slug($post)
    {
        $enclosures = Enclosure::all_for_post($post->ID);
        foreach ($enclosures as $enclosure) {
            if (!$enclosure->errors) {
                $file_name = end(explode('/', $enclosure->url));

                return substr($file_name, 0, strrpos($file_name, '.'));
            }
        }

        return null;
    }

    private function reset_migration()
    {
        delete_option('podlove_module_migration');
        delete_option('podlove_migration');
        delete_option('podlove_migration_validation_cache');
        delete_option('podlove_asset_assignment');
        delete_option('podlove_migrated_posts_cache');

        $args = [
            'post_type' => 'podcast',
            'posts_per_page' => -1,
        ];
        $query = new \WP_Query($args);

        while ($query->have_posts()) {
            $query->the_post();
            wp_delete_post(get_the_ID());
        }

        wp_reset_postdata();

        foreach (Model\EpisodeAsset::all() as $asset) {
            $asset->delete();
        }

        foreach (Model\Feed::all() as $feed) {
            $feed->delete();
        }
    }
}
