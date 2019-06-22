<?php
namespace Podlove\Modules\Shows;

use Podlove\Modules\Shows\Model\Show;
use Podlove\Modules\SubscribeButton\Button;

class Shows extends \Podlove\Modules\Base
{
    protected $module_name        = 'Shows';
    protected $module_description = 'Release specific episodes of a podcast as Shows.';
    protected $module_group       = 'metadata';

    public function load()
    {
        add_action('init', array($this, 'register_show_taxonomy'));
        add_action('add_meta_boxes', array($this, 'add_meta_box'));

        add_action('podlove_register_settings_pages', function ($handle) {
            new \Podlove\Modules\Shows\Settings\Settings($handle);
        });
        add_action('admin_print_styles', array($this, 'scripts_and_styles'));

        add_filter('podlove_feed_title', array($this, 'override_feed_title'), 20);
        add_filter('podlove_feed_itunes_subtitle', array($this, 'override_feed_subtitle'), 5);
        add_filter('podlove_feed_itunes_summary', array($this, 'override_feed_summary'), 5);
        add_filter('podlove_rss_feed_description', array($this, 'override_feed_description'), 20);
        add_filter('podlove_feed_itunes_image', array($this, 'override_feed_image'), 5);
        add_filter('podlove_feed_itunes_image_url', array($this, 'override_feed_image_url'), 5);
        add_filter('podlove_feed_language', array($this, 'override_feed_language'), 5);

        add_filter('set-screen-option', function ($status, $option, $value) {
            if ($option == 'podlove_shows_per_page') {
                return $value;
            }

            return $status;
        }, 10, 3);

        add_filter('podlove_subscribe_button_data', [$this, 'override_subscribe_button'], 10, 3);
        add_filter('podlove_subscribe_button_args', [$this, 'override_subscribe_button_args'], 10, 2);

        add_action('podlove_subscribe_button_widget_settings_bottom', [$this, 'add_widget_settings'], 10, 2);
        add_filter('podlove_subscribe_button_widget_settings_update', [$this, 'add_widget_settings_update'], 10, 3);

        // Template accessors (Provides episode.show and podcasts.shows)
        \Podlove\Template\Episode::add_accessor(
            'show', array('\Podlove\Modules\Shows\TemplateExtensions', 'accessorEpisodesShow'), 5
        );

        \Podlove\Template\Podcast::add_accessor(
            'shows', array('\Podlove\Modules\Shows\TemplateExtensions', 'accessorPodcastShows'), 4
        );
    }

    public function add_widget_settings($widget, $instance)
    {
        $selected_show = isset($instance['show']) ? $instance['show'] : '';
        ?>
		<p>
			<label for="<?php echo $widget->get_field_id('show'); ?>">
				<?php _e('Show', 'podlove-podcasting-plugin-for-wordpress');?>
			</label>
			<select class="widefat" id="<?php echo $widget->get_field_id('show'); ?>" name="<?php echo $widget->get_field_name('show'); ?>">
				<option value="0" <?php selected($selected_show, 0)?>><?php _e('Podcast', 'podlove-podcasting-plugin-for-wordpress');?></option>
				<?php foreach (Show::all() as $show): ?>
					<option value="<?php echo esc_attr($show->slug) ?>" <?php selected($selected_show, $show->slug)?>><?php echo $show->title ?></option>
				<?php endforeach?>
			</select>
		</p>
		<?php
}

    public function add_widget_settings_update($instance, $new_instance, $old_instance)
    {
        $instance['show'] = !empty($new_instance['show']) ? strip_tags($new_instance['show']) : '';

        return $instance;
    }

    public function override_subscribe_button($data, $args, $podcast)
    {
        if (!isset($args['show']) || !$args['show']) {
            return $data;
        }

        $show = Show::find_one_term_by_property('slug', $args['show']);

        if (!$show) {
            return $data;
        }

        $feeds = Button::feeds(
            $podcast->feeds(['only_discoverable' => true]),
            'shows',
            $show->id
        );

        $show_data = [
            'title'       => $show->title,
            'subtitle'    => $show->subtitle,
            'description' => $show->summary,
            'cover'       => $show->image,
            'feeds'       => $feeds,
        ];

        // only override nonempty fields
        foreach (array_keys($show_data) as $key) {
            if ($show_data[$key] && !empty($show_data[$key])) {
                $data[$key] = $show_data[$key];
            }
        }

        return $data;
    }

    public function override_subscribe_button_args($args, $podcast)
    {
        if (!isset($args['show']) || !$args['show']) {
            return $args;
        }

        $show = Show::find_one_term_by_property('slug', $args['show']);

        if (!$show) {
            return $args;
        }

        if ($show->language) {
            $args['language'] = \Podlove\Modules\SubscribeButton\Button::language($show->language);
        }

        return $args;
    }

    public function add_meta_box()
    {
        add_meta_box(
            /* $id       */'podlove_podcast_show',
            /* $title    */__('Show', 'podlove-podcasting-plugin-for-wordpress'),
            /* $callback */array($this, 'episode_show_meta_box'),
            /* $page     */'podcast',
            /* $context  */'normal',
            /* $priority */'low'
        );
    }

    public function register_show_taxonomy()
    {
        register_taxonomy(
            'shows',
            'podcast',
            array(
                'label'              => __('Show', 'podlove-podcasting-plugin-for-wordpress'),
                'rewrite'            => array('slug' => 'show'),
                'show_ui'            => false,
                'show_in_menu'       => false,
                'show_in_quick_edit' => false,
                'show_in_rest'       => false,
                'hierarchical'       => false,
                'show_admin_column'  => false,
            )
        );
    }

    public function override_feed_title($title)
    {
        return self::get_feed_modification($title, 'title');
    }

    public function override_feed_subtitle($subtitle)
    {
        return self::get_feed_modification($subtitle, 'subtitle');
    }

    public function override_feed_description($description)
    {
        return self::get_feed_modification($description, 'description');
    }

    public function override_feed_summary($summary)
    {
        return self::get_feed_modification($summary, 'summary');
    }

    public function override_feed_image($image)
    {
        return self::get_feed_modification($image, 'image');
    }

    public function override_feed_image_url($url)
    {
        return self::get_feed_modification($url, 'image_url');
    }

    public function override_feed_language($language)
    {
        return self::get_feed_modification($language, 'language');
    }

    /*
     * Handles the feed modifications
     *
     * @param string $previous_value Previous value
     * @param string $new_value Used to replace $previous_value
     */
    private static function get_feed_modification($previous_value, $new_value)
    {
        global $wp_query;

        if (isset($wp_query->query_vars['shows'])) {
            $show = Show::find_one_term_by_property('slug', $wp_query->query_vars['shows']);

            switch ($new_value) {
                case 'title':
                    return $show->title;
                    break;
                case 'subtitle':
                    if ($show->subtitle) {
                        return sprintf('<itunes:subtitle>%s</itunes:subtitle>', $show->subtitle);
                    }
                    break;
                case 'description':
                    if ($show->subtitle) {
                        return $show->subtitle;
                    }
                    break;
                case 'summary':
                    if ($show->summary) {
                        return sprintf('<itunes:summary>%s</itunes:summary>', $show->summary);
                    }
                    break;
                case 'language':
                    if ($show->language) {
                        return $show->language;
                    }
                    break;
                case 'image':
                    if ($show->image) {
                        return sprintf('<itunes:image href="%s"/>', esc_attr($show->image));
                    }
                    break;
                case 'image_url':
                    return $show->image;
                    break;
            }
        }

        return $previous_value;
    }

    /**
     * Provides the Show meta box for the Episode UI
     *
     * NOTE: We do NOT use the default WordPress UI since it cannot be modified sufficiently enough
     */
    public function episode_show_meta_box()
    {
        $post     = get_post();
        $taxonomy = get_taxonomy('shows');
        ?>
		<div id="taxonomy-shows" class="categorydiv">
			<input type='hidden' name='tax_input[shows][]' value='0' />
			<ul id="showschecklist" class="categorychecklist form-no-clear">
				<?php
$terms     = get_terms('shows', array('hide_empty' => false));
        $postterms = get_the_terms($post->id, 'shows');
        $current   = (isset($postterms[0]) ? $postterms[0]->term_id : 0); // Fetch the first element of the term array. We expect that there is only one "Show" term since a show is a unique property of an episode.

        echo "
						<li class='fubar'>
							<label class='selectit'>
								<input type='radio' name='tax_input[shows]'"
        . checked($current, 0, false)
        . "value='0' />"
        . __("Podcast", "podlove-podcasting-plugin-for-wordpress")
        . " <span class='description'>(" . __("no show assignment", "podlove-podcasting-plugin-for-wordpress") . ")</span>"
            . "</label>
						</li>";

        foreach ($terms as $term) {
            $id = 'shows-' . (int) $term->term_id;

            echo "
							<li id='$id' class='fubar'>
								<label class='selectit'>
									<input type='radio' id='in-$id' name='tax_input[shows]'"
            . checked($current, $term->term_id, false)
            . "value='" . esc_attr($term->slug) . "' />"
            . esc_html($term->name) .
                "</label>
							</li>";
        }
        ?>
			</ul>
		</div>
		<?php
}

    public function scripts_and_styles()
    {
        if (filter_input(INPUT_GET, 'page') !== 'podlove_shows_settings') {
            return;
        }

        wp_enqueue_script(
            'podlove_shows_admin_script',
            $this->get_module_url() . '/js/admin.js',
            ['jquery'],
            \Podlove\get_plugin_header('Version')
        );
    }
}
