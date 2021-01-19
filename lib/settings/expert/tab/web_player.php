<?php

namespace Podlove\Settings\Expert\Tab;

use Podlove\Model;
use Podlove\Settings\Expert\Tab;
use Podlove\Settings\Settings;

class WebPlayer extends Tab
{
    public function get_slug()
    {
        return 'web-player';
    }

    public function init()
    {
        add_settings_section(
            // $id
            'podlove_settings_episode',
            // $title
            '',
            // $callback
            function () {
                echo '<h3>'.__('Web Player Settings', 'podlove-podcasting-plugin-for-wordpress').'</h3>';
            },
            // $page
            Settings::$pagehook
        );

        register_setting(Settings::$pagehook, 'podlove_webplayer_formats');
    }

    public function page()
    {
        ?>
		<form method="post" action="options.php">
			<?php if (isset($_REQUEST['podlove_tab'])) { ?>
				<input type="hidden" name="podlove_tab" value="<?php echo esc_attr($_REQUEST['podlove_tab']); ?>" />
			<?php } ?>
			<?php settings_fields(Settings::$pagehook); ?>
			<?php do_settings_sections(Settings::$pagehook); ?>

			<?php _e('Web players are able to provide various media formats depending on context. Try to provide as many as possible to maximize compatibility with all browsers.', 'podlove-podcasting-plugin-for-wordpress'); ?>

			<table class="form-table">
				<?php $this->form_fields(); ?>
			</table>
			
			<?php submit_button(__('Save Changes'), 'button-primary', 'submit', true); ?>
		</form>
		<?php
    }

    /**
     * Config array containing list of supported web player assets.
     * Each type (audio, video) lists supported extensions with their title and mime_type.
     *
     * @return array
     */
    public static function formats()
    {
        return [
            'audio' => [
                'mp3' => [
                    'title' => __('MP3 Audio', 'podlove-podcasting-plugin-for-wordpress'),
                    'mime_types' => ['audio/mpeg'],
                ],
                'mp4' => [
                    'title' => __('MP4 Audio', 'podlove-podcasting-plugin-for-wordpress'),
                    'mime_types' => ['audio/mp4'],
                ],
                'ogg' => [
                    'title' => __('OGG Audio', 'podlove-podcasting-plugin-for-wordpress'),
                    'mime_types' => ['audio/ogg'],
                ],
                'opus' => [
                    'title' => __('Opus Audio', 'podlove-podcasting-plugin-for-wordpress'),
                    'mime_types' => ['audio/ogg;codecs=opus', 'audio/opus'],
                ],
            ],
            'video' => [
                'mp4' => [
                    'title' => __('MP4 Video', 'podlove-podcasting-plugin-for-wordpress'),
                    'mime_types' => ['video/mp4'],
                ],
                'ogg' => [
                    'title' => __('OGG Video', 'podlove-podcasting-plugin-for-wordpress'),
                    'mime_types' => ['video/ogg'],
                ],
                'webm' => [
                    'title' => __('WebM Video', 'podlove-podcasting-plugin-for-wordpress'),
                    'mime_types' => ['video/webm'],
                ],
            ],
            'transcript' => [
                'transcript' => [
                    'title' => __('Transcript (Only Podigee Player)', 'podlove-podcasting-plugin-for-wordpress'),
                    'mime_types' => ['text/plain', 'application/x-subrip'],
                ],
            ],
        ];
    }

    public function form_fields()
    {
        $formats_data = get_option('podlove_webplayer_formats', []);
        $episode_assets = Model\EpisodeAsset::all();

        foreach (self::formats() as $format => $extensions) {
            ?>
			<tr valign="top">
				<th scope="row" valign="top" colspan="2">
					<h3><?php echo ucfirst($format); ?></h3>
				</th>
			</tr>
			<?php
            foreach ($extensions as $extension => $extension_data) {
                $label = $extension_data['title'];
                $mime_types = $extension_data['mime_types'];

                $id = sprintf('podlove_webplayer_formats_%s_%s', $format, $extension);
                $name = sprintf('podlove_webplayer_formats[%s][%s]', $format, $extension);
                $value = (isset($formats_data[$format], $formats_data[$format][$extension])) ? $formats_data[$format][$extension] : 0; ?>
				<tr valign="top">
					<th scope="row" valign="top">
						<label for="<?php echo $id; ?>"><?php echo $label; ?></label>
					</th>
					<td>
						<div>
							<select name="<?php echo $name; ?>" id="<?php echo $id; ?>">
								<option value="0" <?php selected(0, $value); ?> ><?php _e('Unused', 'podlove-podcasting-plugin-for-wordpress'); ?></option>
								<?php foreach ($episode_assets as $episode_asset) { ?>
									<?php $file_type = $episode_asset->file_type(); ?>
									<?php if ($file_type && in_array($file_type->mime_type, $mime_types)) { ?>
										<option value="<?php echo $episode_asset->id; ?>" <?php selected($episode_asset->id, $value); ?>><?php echo $episode_asset->title; ?></option>
									<?php } ?>
								<?php } ?>
							</select>
						</div>
					</td>
				</tr>
				<?php
            }
        }
    }
}
