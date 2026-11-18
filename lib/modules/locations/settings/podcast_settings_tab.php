<?php

namespace Podlove\Modules\Locations\Settings;

use Podlove\Settings\Podcast\Tab;

class Podcast_Settings_Tab extends Tab
{
    private static $nonce = 'update_podcast_settings_locations';
    private static $option_key = 'podlove_episode_location_podcast';

    public function init()
    {
        add_action($this->page_hook, [$this, 'register_page']);
        add_action('admin_init', [$this, 'process_form']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function process_form()
    {
        if (!isset($_POST['podlove_podcast_location']) || !$this->is_active()) {
            return;
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        if (!wp_verify_nonce($_REQUEST['_podlove_nonce'] ?? '', self::$nonce)) {
            return;
        }

        $raw = $_POST['podlove_podcast_location'];
        $data = [
            'location_name' => self::sanitize_location_name($raw['location_name'] ?? ''),
            'location_lat' => self::sanitize_coordinate($raw['location_lat'] ?? '', 'lat'),
            'location_lng' => self::sanitize_coordinate($raw['location_lng'] ?? '', 'lng'),
            'location_address' => sanitize_text_field($raw['location_address'] ?? ''),
            'location_country' => strtoupper(substr(sanitize_text_field($raw['location_country'] ?? ''), 0, 2)),
            'location_osm' => sanitize_text_field($raw['location_osm'] ?? ''),
        ];

        update_option(self::$option_key, $data);
        self::purge_cache();

        wp_safe_redirect(admin_url('admin.php?page=podlove_settings_podcast_handle&podlove_tab='.$this->get_slug()));
        exit;
    }

    public function register_page()
    {
        $data = self::get_podcast_location();
        ?>
		<form method="post" action="<?php echo esc_url($this->get_url()); ?>">
			<?php wp_nonce_field(self::$nonce, '_podlove_nonce'); ?>

			<p class="podlove-location-tab-hint" style="font-style: italic; color: #666; margin-bottom: 16px;">
				<?php esc_html_e('Set a default creator location for this podcast. It is emitted at the channel level in your RSS feed and used as a fallback in templates when an episode has no explicit creator location.', 'podlove-podcasting-plugin-for-wordpress'); ?>
			</p>

			<div id="podlove-podcast-location-wrapper">
				<div class="podlove-location-search-wrapper">
					<label for="podlove-location-search-podcast">
						<?php esc_html_e('Search Location', 'podlove-podcasting-plugin-for-wordpress'); ?>
					</label>
					<div class="podlove-location-search-row">
						<input
							type="text"
							id="podlove-location-search-podcast"
							class="regular-text podlove-location-search-input"
							data-rel="podcast"
							placeholder="<?php esc_attr_e('Search for a place or address...', 'podlove-podcasting-plugin-for-wordpress'); ?>"
						/>
						<button type="button" class="button podlove-location-search-btn" data-rel="podcast">
							<?php esc_html_e('Search', 'podlove-podcasting-plugin-for-wordpress'); ?>
						</button>
					</div>
					<div id="podlove-location-search-results-podcast" class="podlove-location-search-results"></div>
				</div>

				<div id="podlove-location-map-podcast" class="podlove-location-map"></div>

				<div class="podlove-location-fields">
					<div class="podlove-location-field-row">
						<label for="podlove-location-name-podcast">
							<?php esc_html_e('Location Name', 'podlove-podcasting-plugin-for-wordpress'); ?>
						</label>
						<input
							type="text"
							id="podlove-location-name-podcast"
							name="podlove_podcast_location[location_name]"
							class="regular-text"
							value="<?php echo esc_attr($data['location_name']); ?>"
							placeholder="<?php esc_attr_e('e.g. Berlin, Home Studio...', 'podlove-podcasting-plugin-for-wordpress'); ?>"
						/>
					</div>

					<div class="podlove-location-field-row podlove-location-coords-row">
						<div class="podlove-location-coord">
							<label for="podlove-location-lat-podcast">
								<?php esc_html_e('Latitude', 'podlove-podcasting-plugin-for-wordpress'); ?>
							</label>
							<input
								type="text"
								id="podlove-location-lat-podcast"
								name="podlove_podcast_location[location_lat]"
								class="regular-text"
								value="<?php echo esc_attr($data['location_lat']); ?>"
								readonly
							/>
						</div>
						<div class="podlove-location-coord">
							<label for="podlove-location-lng-podcast">
								<?php esc_html_e('Longitude', 'podlove-podcasting-plugin-for-wordpress'); ?>
							</label>
							<input
								type="text"
								id="podlove-location-lng-podcast"
								name="podlove_podcast_location[location_lng]"
								class="regular-text"
								value="<?php echo esc_attr($data['location_lng']); ?>"
								readonly
							/>
						</div>
					</div>

					<div class="podlove-location-field-row">
						<label for="podlove-location-address-podcast">
							<?php esc_html_e('Address', 'podlove-podcasting-plugin-for-wordpress'); ?>
						</label>
						<input
							type="text"
							id="podlove-location-address-podcast"
							name="podlove_podcast_location[location_address]"
							class="large-text"
							value="<?php echo esc_attr($data['location_address']); ?>"
							placeholder="<?php esc_attr_e('Full address (auto-filled from search)', 'podlove-podcasting-plugin-for-wordpress'); ?>"
						/>
					</div>

					<div class="podlove-location-field-row podlove-location-extra-row">
						<div class="podlove-location-coord">
							<label for="podlove-location-country-podcast">
								<?php esc_html_e('Country', 'podlove-podcasting-plugin-for-wordpress'); ?>
							</label>
							<input
								type="text"
								id="podlove-location-country-podcast"
								name="podlove_podcast_location[location_country]"
								class="small-text"
								value="<?php echo esc_attr($data['location_country']); ?>"
								maxlength="2"
								placeholder="<?php esc_attr_e('e.g. GB', 'podlove-podcasting-plugin-for-wordpress'); ?>"
							/>
						</div>
						<div class="podlove-location-coord">
							<label for="podlove-location-osm-podcast">
								<?php esc_html_e('OSM ID', 'podlove-podcasting-plugin-for-wordpress'); ?>
							</label>
							<input
								type="text"
								id="podlove-location-osm-podcast"
								name="podlove_podcast_location[location_osm]"
								class="regular-text"
								value="<?php echo esc_attr($data['location_osm']); ?>"
								placeholder="<?php esc_attr_e('e.g. R113314', 'podlove-podcasting-plugin-for-wordpress'); ?>"
							/>
						</div>
					</div>

					<div class="podlove-location-actions">
						<button type="button" class="button podlove-location-clear-btn" data-rel="podcast">
							<?php esc_html_e('Clear Location', 'podlove-podcasting-plugin-for-wordpress'); ?>
						</button>
						<span class="podlove-location-hint">
							<?php esc_html_e('Search for a location or click on the map to set the pin. Drag the marker to adjust.', 'podlove-podcasting-plugin-for-wordpress'); ?>
						</span>
					</div>
				</div>
			</div>

			<?php submit_button(__('Save Changes', 'podlove-podcasting-plugin-for-wordpress')); ?>
		</form>
		<?php
    }

    public function enqueue_assets()
    {
        if (!$this->is_active()) {
            return;
        }

        if (filter_input(INPUT_GET, 'page') !== 'podlove_settings_podcast_handle') {
            return;
        }

        wp_enqueue_style(
            'leaflet',
            'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
            [],
            '1.9.4'
        );

        wp_enqueue_script(
            'leaflet',
            'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
            [],
            '1.9.4',
            true
        );

        wp_enqueue_style(
            'podlove_locations_admin_style',
            \Podlove\PLUGIN_URL.'/lib/modules/locations/css/admin.css',
            ['leaflet'],
            \Podlove\get_plugin_header('Version')
        );

        wp_enqueue_script(
            'podlove_locations_admin_script',
            \Podlove\PLUGIN_URL.'/lib/modules/locations/js/admin.js',
            ['jquery', 'leaflet'],
            \Podlove\get_plugin_header('Version'),
            true
        );
    }

    public static function get_podcast_location()
    {
        $defaults = [
            'location_name' => '',
            'location_lat' => '',
            'location_lng' => '',
            'location_address' => '',
            'location_country' => '',
            'location_osm' => '',
        ];

        $data = get_option(self::$option_key, $defaults);

        return wp_parse_args($data, $defaults);
    }

    public static function has_podcast_location()
    {
        $data = self::get_podcast_location();

        return !empty($data['location_name'])
            || (!empty($data['location_lat']) && !empty($data['location_lng']))
            || !empty($data['location_osm'])
            || !empty($data['location_country']);
    }

    public static function purge_cache()
    {
        \Podlove\Cache\TemplateCache::get_instance()->setup_purge();
    }

    private static function sanitize_location_name($value)
    {
        $value = sanitize_text_field($value);

        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, 128);
        }

        return substr($value, 0, 128);
    }

    private static function sanitize_coordinate($value, $type)
    {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        if (!is_numeric($value)) {
            return '';
        }

        $float = (float) $value;

        if ($type === 'lat' && ($float < -90 || $float > 90)) {
            return '';
        }

        if ($type === 'lng' && ($float < -180 || $float > 180)) {
            return '';
        }

        return sprintf('%.8F', $float);
    }
}
