<?php

namespace Podlove\Modules\Locations;

use Podlove\Model\Episode;
use Podlove\Modules\Locations\Model\Location;

class Meta_Box
{
    public function __construct()
    {
        add_action('add_meta_boxes', [$this, 'register']);
        add_action('save_post_podcast', [$this, 'save'], 10, 2);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function register()
    {
        add_meta_box(
            'podlove_podcast_locations',
            __('Locations', 'podlove-podcasting-plugin-for-wordpress'),
            [$this, 'render'],
            'podcast',
            'normal',
            'low'
        );
    }

    public function render($post)
    {
        $episode = Episode::find_one_by_property('post_id', $post->ID);

        $subject = $this->get_location_data($episode, 'subject');
        $creator = $this->get_location_data($episode, 'creator');

        wp_nonce_field('podlove_locations_save', 'podlove_locations_nonce');
        ?>
		<div id="podlove-episode-location-wrapper">
			<div class="podlove-location-tabs">
				<button type="button" class="podlove-location-tab active" data-tab="subject">
					<?php esc_html_e('Subject Location', 'podlove-podcasting-plugin-for-wordpress'); ?>
				</button>
				<button type="button" class="podlove-location-tab" data-tab="creator">
					<?php esc_html_e('Creator Location', 'podlove-podcasting-plugin-for-wordpress'); ?>
				</button>
			</div>

			<?php $this->render_tab_panel('subject', $subject, __('Where is this episode about?', 'podlove-podcasting-plugin-for-wordpress')); ?>
			<?php $this->render_tab_panel('creator', $creator, __('Where was this episode recorded?', 'podlove-podcasting-plugin-for-wordpress')); ?>
		</div>
		<?php
    }

    public function save($post_id, $post)
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if (!isset($_POST['podlove_locations_nonce'])
            || !wp_verify_nonce($_POST['podlove_locations_nonce'], 'podlove_locations_save')
        ) {
            return;
        }

        if (!isset($_POST['podlove_episode_location'])) {
            return;
        }

        $episode = Episode::find_one_by_property('post_id', $post_id);
        if (!$episode) {
            return;
        }

        $all_data = $_POST['podlove_episode_location'];

        foreach (['subject', 'creator'] as $rel) {
            $data = isset($all_data[$rel]) ? $all_data[$rel] : [];
            $this->save_rel($episode->id, $rel, $data);
        }

        $episode->delete_caches();
        if (function_exists('\podlove_clear_feed_cache_for_post')) {
            \podlove_clear_feed_cache_for_post($episode->post_id);
        }
    }

    public function enqueue_assets($hook_suffix)
    {
        if (!in_array($hook_suffix, ['post.php', 'post-new.php'], true)) {
            return;
        }

        if (!\Podlove\is_episode_edit_screen()) {
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

    private function render_tab_panel($rel, $data, $hint)
    {
        $active = ($rel === 'subject') ? ' active' : '';
        ?>
		<div class="podlove-location-tab-panel<?php echo $active; ?>" data-tab="<?php echo esc_attr($rel); ?>">
			<p class="podlove-location-tab-hint"><?php echo esc_html($hint); ?></p>

			<div class="podlove-location-search-wrapper">
				<label for="podlove-location-search-<?php echo esc_attr($rel); ?>">
					<?php esc_html_e('Search Location', 'podlove-podcasting-plugin-for-wordpress'); ?>
				</label>
				<div class="podlove-location-search-row">
					<input
						type="text"
						id="podlove-location-search-<?php echo esc_attr($rel); ?>"
						class="regular-text podlove-location-search-input"
						data-rel="<?php echo esc_attr($rel); ?>"
						placeholder="<?php esc_attr_e('Search for a place or address...', 'podlove-podcasting-plugin-for-wordpress'); ?>"
					/>
					<button type="button" class="button podlove-location-search-btn" data-rel="<?php echo esc_attr($rel); ?>">
						<?php esc_html_e('Search', 'podlove-podcasting-plugin-for-wordpress'); ?>
					</button>
				</div>
				<div id="podlove-location-search-results-<?php echo esc_attr($rel); ?>" class="podlove-location-search-results"></div>
			</div>

			<div id="podlove-location-map-<?php echo esc_attr($rel); ?>" class="podlove-location-map"></div>

			<div class="podlove-location-fields">
				<div class="podlove-location-field-row">
					<label for="podlove-location-name-<?php echo esc_attr($rel); ?>">
						<?php esc_html_e('Location Name', 'podlove-podcasting-plugin-for-wordpress'); ?>
					</label>
					<input
						type="text"
						id="podlove-location-name-<?php echo esc_attr($rel); ?>"
						name="podlove_episode_location[<?php echo esc_attr($rel); ?>][location_name]"
						class="regular-text"
						value="<?php echo esc_attr($data['location_name']); ?>"
						placeholder="<?php esc_attr_e('e.g. Berlin, Conference Hall...', 'podlove-podcasting-plugin-for-wordpress'); ?>"
					/>
				</div>

				<div class="podlove-location-field-row podlove-location-coords-row">
					<div class="podlove-location-coord">
						<label for="podlove-location-lat-<?php echo esc_attr($rel); ?>">
							<?php esc_html_e('Latitude', 'podlove-podcasting-plugin-for-wordpress'); ?>
						</label>
						<input
							type="text"
							id="podlove-location-lat-<?php echo esc_attr($rel); ?>"
							name="podlove_episode_location[<?php echo esc_attr($rel); ?>][location_lat]"
							class="regular-text"
							value="<?php echo esc_attr($data['location_lat']); ?>"
							readonly
						/>
					</div>
					<div class="podlove-location-coord">
						<label for="podlove-location-lng-<?php echo esc_attr($rel); ?>">
							<?php esc_html_e('Longitude', 'podlove-podcasting-plugin-for-wordpress'); ?>
						</label>
						<input
							type="text"
							id="podlove-location-lng-<?php echo esc_attr($rel); ?>"
							name="podlove_episode_location[<?php echo esc_attr($rel); ?>][location_lng]"
							class="regular-text"
							value="<?php echo esc_attr($data['location_lng']); ?>"
							readonly
						/>
					</div>
				</div>

				<div class="podlove-location-field-row">
					<label for="podlove-location-address-<?php echo esc_attr($rel); ?>">
						<?php esc_html_e('Address', 'podlove-podcasting-plugin-for-wordpress'); ?>
					</label>
					<input
						type="text"
						id="podlove-location-address-<?php echo esc_attr($rel); ?>"
						name="podlove_episode_location[<?php echo esc_attr($rel); ?>][location_address]"
						class="large-text"
						value="<?php echo esc_attr($data['location_address']); ?>"
						placeholder="<?php esc_attr_e('Full address (auto-filled from search)', 'podlove-podcasting-plugin-for-wordpress'); ?>"
					/>
				</div>

				<div class="podlove-location-field-row podlove-location-extra-row">
					<div class="podlove-location-coord">
						<label for="podlove-location-country-<?php echo esc_attr($rel); ?>">
							<?php esc_html_e('Country', 'podlove-podcasting-plugin-for-wordpress'); ?>
						</label>
						<input
							type="text"
							id="podlove-location-country-<?php echo esc_attr($rel); ?>"
							name="podlove_episode_location[<?php echo esc_attr($rel); ?>][location_country]"
							class="small-text"
							value="<?php echo esc_attr($data['location_country']); ?>"
							maxlength="2"
							placeholder="<?php esc_attr_e('e.g. GB', 'podlove-podcasting-plugin-for-wordpress'); ?>"
						/>
					</div>
					<div class="podlove-location-coord">
						<label for="podlove-location-osm-<?php echo esc_attr($rel); ?>">
							<?php esc_html_e('OSM ID', 'podlove-podcasting-plugin-for-wordpress'); ?>
						</label>
						<input
							type="text"
							id="podlove-location-osm-<?php echo esc_attr($rel); ?>"
							name="podlove_episode_location[<?php echo esc_attr($rel); ?>][location_osm]"
							class="regular-text"
							value="<?php echo esc_attr($data['location_osm']); ?>"
							placeholder="<?php esc_attr_e('e.g. R113314', 'podlove-podcasting-plugin-for-wordpress'); ?>"
						/>
					</div>
				</div>

				<div class="podlove-location-actions">
					<button type="button" class="button podlove-location-clear-btn" data-rel="<?php echo esc_attr($rel); ?>">
						<?php esc_html_e('Clear Location', 'podlove-podcasting-plugin-for-wordpress'); ?>
					</button>
					<span class="podlove-location-hint">
						<?php esc_html_e('Search for a location or click on the map to set the pin. Drag the marker to adjust.', 'podlove-podcasting-plugin-for-wordpress'); ?>
					</span>
				</div>
			</div>
		</div>
		<?php
    }

    private function get_location_data($episode, $rel)
    {
        $defaults = [
            'location_name' => '',
            'location_lat' => '',
            'location_lng' => '',
            'location_address' => '',
            'location_country' => '',
            'location_osm' => '',
        ];

        if (!$episode) {
            return $defaults;
        }

        $location = Location::find_by_episode_id_and_rel($episode->id, $rel);
        if (!$location) {
            return $defaults;
        }

        return [
            'location_name' => $location->location_name,
            'location_lat' => $location->location_lat,
            'location_lng' => $location->location_lng,
            'location_address' => $location->location_address,
            'location_country' => $location->location_country,
            'location_osm' => $location->location_osm,
        ];
    }

    private function save_rel($episode_id, $rel, $data)
    {
        $location_name = self::sanitize_location_name($data['location_name'] ?? '');
        $location_lat = self::sanitize_coordinate($data['location_lat'] ?? '', 'lat');
        $location_lng = self::sanitize_coordinate($data['location_lng'] ?? '', 'lng');
        $location_address = sanitize_text_field($data['location_address'] ?? '');
        $location_country = strtoupper(substr(sanitize_text_field($data['location_country'] ?? ''), 0, 2));
        $location_osm = sanitize_text_field($data['location_osm'] ?? '');

        $location = Location::find_by_episode_id_and_rel($episode_id, $rel);

        if (empty($location_name) && empty($location_lat) && empty($location_lng)
            && empty($location_address) && empty($location_country) && empty($location_osm)
        ) {
            if ($location) {
                $location->delete();
            }

            return;
        }

        if (!$location) {
            $location = new Location();
            $location->episode_id = $episode_id;
            $location->rel = $rel;
        }

        $location->location_name = $location_name;
        $location->location_lat = $location_lat;
        $location->location_lng = $location_lng;
        $location->location_address = $location_address;
        $location->location_country = $location_country;
        $location->location_osm = $location_osm;
        $location->save();
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
