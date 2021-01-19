<?php

namespace Podlove\Settings\Expert\Tab;

use Podlove\Settings\Expert\Tab;
use Podlove\Settings\Settings;

class Metadata extends Tab
{
    public function get_slug()
    {
        return 'metadata';
    }

    public function init()
    {
        add_settings_section(
            // $id
            'podlove_settings_episode',
            // $title
            __('', 'podlove-podcasting-plugin-for-wordpress'),
            // $callback
            function () {
                echo '<h3>'.__('Episode Metadata Settings', 'podlove-podcasting-plugin-for-wordpress').'</h3>';
            },
            // $page
            Settings::$pagehook
        );

        add_settings_field(
            // $id
            'podlove_setting_episode_recording_date',
            // $title
            sprintf(
                '<label for="enable_episode_recording_date">%s</label>',
                __('Enable recording date field.', 'podlove-podcasting-plugin-for-wordpress')
            ),
            // $callback
            function () {
                ?>
				<label>
					<input name="podlove_metadata[enable_episode_recording_date]" id="enable_episode_recording_date" type="radio" value="1" <?php checked(\Podlove\get_setting('metadata', 'enable_episode_recording_date'), 1); ?> /> <?php echo __('enable', 'podlove-podcasting-plugin-for-wordpress'); ?>
				</label>
				<label>
					<input name="podlove_metadata[enable_episode_recording_date]" id="enable_episode_recording_date" type="radio" value="0" <?php checked(\Podlove\get_setting('metadata', 'enable_episode_recording_date'), 0); ?> /> <?php echo __('disable', 'podlove-podcasting-plugin-for-wordpress'); ?>
				</label>
				<?php
            },
            // $page
            Settings::$pagehook,
            // $section
            'podlove_settings_episode'
        );

        add_settings_field(
            // $id
            'podlove_setting_episode_explicit',
            // $title
            sprintf(
                '<label for="enable_episode_explicit">%s</label>',
                __('Enable explicit content field.', 'podlove-podcasting-plugin-for-wordpress')
            ), // $callback
            function () {
                ?>
				<label>
					<input name="podlove_metadata[enable_episode_explicit]" id="enable_episode_explicit" type="radio" value="1" <?php checked(\Podlove\get_setting('metadata', 'enable_episode_explicit'), 1); ?> /> <?php echo __('enable', 'podlove-podcasting-plugin-for-wordpress'); ?>
				</label>
				<label>
					<input name="podlove_metadata[enable_episode_explicit]" id="enable_episode_explicit" type="radio" value="0" <?php checked(\Podlove\get_setting('metadata', 'enable_episode_explicit'), 0); ?> /> <?php echo __('disable', 'podlove-podcasting-plugin-for-wordpress'); ?>
				</label>
				<?php
            },
            // $page
            Settings::$pagehook,
            // $section
            'podlove_settings_episode'
        );

        add_settings_field(
            // $id
            'podlove_setting_episode_license',
            // $title
            sprintf(
                '<label for="enable_episode_license">%s</label>',
                __('Enable license field.', 'podlove-podcasting-plugin-for-wordpress')
            ),
            // $callback
            function () {
                ?>
				<label>
					<input name="podlove_metadata[enable_episode_license]" id="enable_episode_license" type="radio" value="1" <?php checked(\Podlove\get_setting('metadata', 'enable_episode_license'), 1); ?> /> <?php echo __('enable', 'podlove-podcasting-plugin-for-wordpress'); ?>
				</label>
				<label>
					<input name="podlove_metadata[enable_episode_license]" id="enable_episode_license" type="radio" value="0" <?php checked(\Podlove\get_setting('metadata', 'enable_episode_license'), 0); ?> /> <?php echo __('disable', 'podlove-podcasting-plugin-for-wordpress'); ?>
				</label>
				<?php
            },
            // $page
            Settings::$pagehook,
            // $section
            'podlove_settings_episode'
        );

        register_setting(Settings::$pagehook, 'podlove_metadata');
    }
}
