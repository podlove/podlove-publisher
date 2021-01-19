<?php

namespace Podlove\Settings;

use Podlove\Model;
use Podlove\Modules\Plus\FeedProxy;

class Feed
{
    use \Podlove\HasPageDocumentationTrait;

    const MENU_SLUG = 'podlove_feeds_settings_handle';

    public static $pagehook;

    public function __construct($handle)
    {
        self::$pagehook = add_submenu_page(
            // $parent_slug
            $handle,
            // $page_title
            __('Podcast Feeds', 'podlove-podcasting-plugin-for-wordpress'),
            // $menu_title
            __('Podcast Feeds', 'podlove-podcasting-plugin-for-wordpress'),
            // $capability
            'administrator',
            // $menu_slug
            self::MENU_SLUG,
            // $function
            [$this, 'page']
        );
        add_action('admin_init', [$this, 'process_form']);
        add_action('load-'.self::$pagehook, [$this, 'add_screen_options']);

        $this->init_page_documentation(self::$pagehook);

        if (isset($_GET['page']) && $_GET['page'] == 'podlove_feeds_settings_handle' && isset($_GET['update_settings']) && $_GET['update_settings'] == 'true') {
            add_action('admin_bar_init', [$this, 'save_global_feed_setting']);
        }
    }

    public function add_screen_options()
    {
        add_screen_option('per_page', [
            'label' => __('Feeds', 'podlove-podcasting-plugin-for-wordpress'),
            'default' => 10,
            'option' => 'podlove_feeds_per_page',
        ]);

        $this->table = new \Podlove\Feed_List_Table();
    }

    public static function get_action_link($feed, $title, $action = 'edit', $class = 'link')
    {
        return sprintf(
            '<a href="?page=%s&action=%s&feed=%s" class="%s">'.$title.'</a>',
            self::MENU_SLUG,
            $action,
            $feed->id,
            $class
        );
    }

    public function process_form()
    {
        if (!isset($_REQUEST['feed'])) {
            return;
        }

        do_action('podlove_feed_process', $_REQUEST['feed'], $_REQUEST['action']);

        $action = (isset($_REQUEST['action'])) ? $_REQUEST['action'] : null;

        set_transient('podlove_needs_to_flush_rewrite_rules', true);

        if ($action === 'save') {
            $this->save();
        } elseif ($action === 'create') {
            $this->create();
        } elseif ($action === 'delete') {
            $this->delete();
        }
    }

    public function page()
    {
        $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : null;

        if ($action == 'confirm_delete' && isset($_REQUEST['feed'])) {
            $feed = \Podlove\Model\Feed::find_by_id((int) $_REQUEST['feed']); ?>
			<div class="updated">
				<p>
					<strong>
						<?php echo sprintf(__('You selected to delete the feed "%s". Please confirm this action.', 'podlove-podcasting-plugin-for-wordpress'), $feed->name); ?>
					</strong>
				</p>
				<p>
					<?php echo __('Clients subscribing to this feed will no longer receive updates. If you are moving your feed, you must inform your subscribers.', 'podlove-podcasting-plugin-for-wordpress'); ?>
				</p>
				<p>
					<?php echo self::get_action_link($feed, __('Delete feed permanently', 'podlove-podcasting-plugin-for-wordpress'), 'delete', 'button'); ?>
					<?php echo self::get_action_link($feed, __('Don\'t change anything', 'podlove-podcasting-plugin-for-wordpress'), 'keep', 'button-primary'); ?>
				</p>
			</div>
			<?php
        } ?>
		<div class="wrap">
			<h2><?php echo __('Podcast Feeds', 'podlove-podcasting-plugin-for-wordpress'); ?> <a href="?page=<?php echo self::MENU_SLUG; ?>&amp;action=new" class="add-new-h2"><?php echo __('Add New', 'podlove-podcasting-plugin-for-wordpress'); ?></a></h2>
			<?php

            switch ($action) {
                case 'new':   $this->new_template();

break;
                case 'edit':  $this->edit_template();

break;
                case 'index': $this->view_template();

break;
                default:      $this->view_template();

break;
            } ?>
		</div>	
		<?php
    }

    public function save_global_feed_setting()
    {
        $podcast_settings = get_option('podlove_podcast', []);

        $args = filter_var_array($_REQUEST['podlove_podcast'], [
            'limit_items' => FILTER_SANITIZE_NUMBER_INT,
            'feed_episode_title_variant' => FILTER_SANITIZE_STRING,
            'feed_episode_title_template' => FILTER_SANITIZE_STRING,
        ]);

        $podcast_settings['limit_items'] = $args['limit_items'];
        $podcast_settings['feed_episode_title_variant'] = $args['feed_episode_title_variant'];
        $podcast_settings['feed_episode_title_template'] = $args['feed_episode_title_template'];

        update_option('podlove_podcast', $podcast_settings);
        \Podlove\Cache\TemplateCache::get_instance()->setup_purge();
        header('Location: '.get_site_url().'/wp-admin/admin.php?page=podlove_feeds_settings_handle');
    }

    /**
     * Process form: save/update a format.
     */
    private function save()
    {
        if (!isset($_REQUEST['feed'])) {
            return;
        }

        $feed = \Podlove\Model\Feed::find_by_id($_REQUEST['feed']);
        $feed->update_attributes($_POST['podlove_feed']);

        if (isset($_POST['submit_and_stay'])) {
            $this->redirect('edit', $feed->id);
        } else {
            $this->redirect('index', $feed->id);
        }
    }

    /**
     * Process form: create a format.
     */
    private function create()
    {
        global $wpdb;

        $feed = new \Podlove\Model\Feed();
        $feed->update_attributes($_POST['podlove_feed']);

        if (isset($_POST['submit_and_stay'])) {
            $this->redirect('edit', $feed->id);
        } else {
            $this->redirect('index');
        }
    }

    /**
     * Process form: delete a format.
     */
    private function delete()
    {
        if (!isset($_REQUEST['feed'])) {
            return;
        }

        \Podlove\Model\Feed::find_by_id($_REQUEST['feed'])->delete();

        $this->redirect('index');
    }

    /**
     * Helper method: redirect to a certain page.
     *
     * @param mixed      $action
     * @param null|mixed $feed_id
     */
    private function redirect($action, $feed_id = null)
    {
        $page = 'admin.php?page='.self::MENU_SLUG;
        $show = ($feed_id) ? '&feed='.$feed_id : '';
        $action = '&action='.$action;

        wp_redirect(admin_url($page.$show.$action));
        exit;
    }

    private function new_template()
    {
        $feed = new \Podlove\Model\Feed(); ?>
		<h3><?php echo __('Add New Feed', 'podlove-podcasting-plugin-for-wordpress'); ?></h3>
		<?php
        $this->form_template($feed, 'create', __('Add New Feed', 'podlove-podcasting-plugin-for-wordpress'));
    }

    private function view_template()
    {
        $this->validate_feeds();

        $this->table->prepare_items();
        $this->table->display();

        do_action('podlove_before_feed_global_settings');

        $this->global_feed_settings_form();

        do_action('podlove_after_feed_global_settings');
    }

    /**
     * Validate Feeds and show appropriate error messages.
     */
    private function validate_feeds()
    {
        $errors = [];

        // check for missing mandatory fields
        foreach (Model\Feed::all() as $feed) {
            if (!strlen(trim($feed->slug))) {
                $errors[] = sprintf(
                    __('The feed %s has no slug.', 'podlove-podcasting-plugin-for-wordpress'),
                    '<strong>'.$feed->name.'</strong>'
                )
                          .\Podlove\get_help_link('podlove_help_feed_slug')
                          .' '.self::get_action_link($feed, __('Go fix it', 'podlove-podcasting-plugin-for-wordpress'));
            }
            if (!$feed->episode_asset_id) {
                $errors[] = sprintf(
                    __('The feed %s has no assigned asset.', 'podlove-podcasting-plugin-for-wordpress'),
                    '<strong>'.$feed->name.'</strong>'
                )
                          .\Podlove\get_help_link('podlove_help_feed_asset')
                          .' '.self::get_action_link($feed, __('Go fix it', 'podlove-podcasting-plugin-for-wordpress'));
            }
        }

        // check for duplicate slugs
        foreach (Model\Feed::find_duplicate_slugs() as $duplicate) {
            $feeds = array_map(function ($feed_id) {
                return Model\Feed::find_by_id($feed_id);
            }, $duplicate['feed_ids']);

            $feed_links = array_map(function ($feed) {
                return self::get_action_link($feed, $feed->name);
            }, $feeds);

            $errors[] = sprintf(
                __('Some feeds (%s) use identical slugs. Please assign unique slugs.'),
                implode(', ', $feed_links)
            ).\Podlove\get_help_link('podlove_help_feed_slug');
        }

        if (count($errors)) {
            ?>
			<div class="error">
				<p>
					<strong><?php echo __('Please resolve these issues so your feeds can work.', 'podlove-podcasting-plugin-for-wordpress'); ?></strong>
				</p>
				<p>
					<?php echo implode('</p><p>', $errors); ?>
				</p>
			</div>
			<?php
        }
    }

    private function global_feed_settings_form()
    {
        ?>
		<div class="podlove-form-card">
		<form method="post" action="admin.php?page=podlove_feeds_settings_handle&amp;update_settings=true">
			<?php settings_fields(Podcast::$pagehook); ?>

			<?php
            $podcast = \Podlove\Model\Podcast::get();

        $form_attributes = [
            'context' => 'podlove_podcast',
            'form' => false,
        ];

        \Podlove\Form\build_for($podcast, $form_attributes, function ($form) {
            $wrapper = new \Podlove\Form\Input\TableWrapper($form);
            $podcast = $form->object;

            $wrapper->subheader(__('Feed Global Defaults', 'podlove-podcasting-plugin-for-wordpress'));

            $limit_options = [
                '-1' => __('No limit. Include all items.', 'podlove-podcasting-plugin-for-wordpress'),
                '0' => __('Use WordPress Default', 'podlove-podcasting-plugin-for-wordpress').' ('.get_option('posts_per_rss').')',
                '1' => 1,
            ];
            for ($i = 1; $i * 5 <= 100; ++$i) {
                $limit_options[$i * 5] = $i * 5;
            }

            $limit_options = apply_filters('podlove_feed_limit_options', $limit_options);

            $wrapper->select('limit_items', [
                'label' => __('Limit Items', 'podlove-podcasting-plugin-for-wordpress'),
                'description' => __('If you have a lot of episodes, you might want to restrict the feed size. Additional limits can be set for the feeds individually.', 'podlove-podcasting-plugin-for-wordpress'),
                'options' => $limit_options,
                'please_choose' => false,
                'default' => '-1',
            ]);

            $wrapper->select('feed_episode_title_variant', [
                'label' => __('Episode Title', 'podlove-podcasting-plugin-for-wordpress'),
                'description' => __('How should the episode title appear in the feed?', 'podlove-podcasting-plugin-for-wordpress'),
                'options' => [
                    'blog' => __('Blog Post Title', 'podlove-podcasting-plugin-for-wordpress'),
                    'episode' => __('Episode Title', 'podlove-podcasting-plugin-for-wordpress'),
                    'template' => __('Custom Template', 'podlove-podcasting-plugin-for-wordpress'),
                ],
                'please_choose' => false,
                'default' => 'blog',
            ]);

            $wrapper->string('feed_episode_title_template', [
                'label' => __('Episode Title Template', 'podlove-podcasting-plugin-for-wordpress'),
                'description' => __('If you chose in the template option above, define the template here. Placeholders: ', 'podlove-podcasting-plugin-for-wordpress').'%mnemonic%, %episode_number%, %season_number%, %episode_title%',
                'html' => ['class' => 'regular-text required'],
                'default' => '%mnemonic%%episode_number% %episode_title%',
            ]);
        }); ?>
		</form>
		</div>
		<?php
    }

    private function form_template($feed, $action, $button_text = null)
    {
        $form_args = [
            'context' => 'podlove_feed',
            'hidden' => [
                'feed' => $feed->id,
                'action' => $action,
            ],
            'submit_button' => false, // for custom control in form_end
            'form_end' => function () {
                echo '<p>';
                submit_button(__('Save Changes'), 'primary', 'submit', false);
                echo ' ';
                submit_button(__('Save Changes and Continue Editing', 'podlove-podcasting-plugin-for-wordpress'), 'secondary', 'submit_and_stay', false);
                echo '</p>';
            },
        ];

        \Podlove\Form\build_for($feed, $form_args, function ($form) {
            $wrapper = new \Podlove\Form\Input\TableWrapper($form);

            $feed = $form->object;

            $podcast = \Podlove\Model\Podcast::get();

            $episode_assets = \Podlove\Model\EpisodeAsset::all();
            $assets = [];
            foreach ($episode_assets as $asset) {
                $assets[$asset->id] = $asset->title;
            }

            $wrapper->subheader(__('Basic Settings', 'podlove-podcasting-plugin-for-wordpress'));

            $wrapper->select('episode_asset_id', [
                'label' => __('Episode Media File', 'podlove-podcasting-plugin-for-wordpress').\Podlove\get_help_link('podlove_help_feed_asset'),
                'options' => $assets,
                'html' => ['class' => 'required'],
            ]);

            $wrapper->string('name', [
                'label' => __('Feed Name', 'podlove-podcasting-plugin-for-wordpress'),
                'description' => __('Some podcast clients may display this title to describe the feed content.', 'podlove-podcasting-plugin-for-wordpress'),
                'html' => ['class' => 'regular-text required podlove-check-input'],
            ]);

            $wrapper->checkbox('append_name_to_podcast_title', [
                'label' => __('Append Feed Name to Podcast title', 'podlove-podcasting-plugin-for-wordpress'),
                'description' => sprintf(__('Structure of the feed title. Preview: %s', 'podlove-podcasting-plugin-for-wordpress'), $podcast->title.'<span id="feed_title_preview_append"></span>'),
                'default' => false,
            ]);

            $wrapper->string('slug', [
                'label' => __('Slug', 'podlove-podcasting-plugin-for-wordpress').\Podlove\get_help_link('podlove_help_feed_slug'),
                'description' => ($feed) ? sprintf(__('Feed identifier. URL Preview: %s', 'podlove-podcasting-plugin-for-wordpress'), '<span data-url="'.esc_attr($feed->get_subscribe_url()).'" id="feed_subscribe_url_preview">'.$feed->get_subscribe_url().'</span>') : '',
                'html' => ['class' => 'regular-text required podlove-check-input'],
            ]);

            $wrapper->checkbox('discoverable', [
                'label' => __('Discoverable?', 'podlove-podcasting-plugin-for-wordpress'),
                'description' => __('Embed a meta tag into the head of your site so browsers and feed readers will find the link to the feed.', 'podlove-podcasting-plugin-for-wordpress'),
                'default' => true,
            ]);

            $wrapper->checkbox('embed_content_encoded', [
                'label' => __('Include HTML Content', 'podlove-podcasting-plugin-for-wordpress'),
                'description' => __('Include episode show notes in the feed.', 'podlove-podcasting-plugin-for-wordpress'),
                'default' => true,
            ]);

            $podcast_settings = get_option('podlove_podcast');
            if ($podcast_settings['limit_items'] < 0) {
                $limit_default = 'No limit';
            } else {
                $limit_default = $podcast_settings['limit_items'];
            }
            $limit_options = [
                '-2' => __('Use Podlove default ('.$limit_default.')', 'podlove-podcasting-plugin-for-wordpress'),
                '-1' => __('No limit. Include all items.', 'podlove-podcasting-plugin-for-wordpress'),
                '0' => __('Use WordPress Default', 'podlove-podcasting-plugin-for-wordpress').' ('.get_option('posts_per_rss').')',
                1 => 1,
            ];
            for ($i = 1; $i * 5 <= 100; ++$i) {
                $limit_options[$i * 5] = $i * 5;
            }

            $limit_options = apply_filters('podlove_feed_limit_options', $limit_options);

            $wrapper->select('limit_items', [
                'label' => __('Limit Items', 'podlove-podcasting-plugin-for-wordpress'),
                'description' => __('If you have a lot of episodes, you might want to restrict the feed size.', 'podlove-podcasting-plugin-for-wordpress'),
                'options' => $limit_options,
                'please_choose' => false,
                'default' => '-2',
            ]);

            $wrapper->subheader(__('Directory Settings', 'podlove-podcasting-plugin-for-wordpress'));

            $wrapper->checkbox('enable', [
                'label' => __('Allow Submission to Directories', 'podlove-podcasting-plugin-for-wordpress'),
                'description' => __('Allow this feed to appear in podcast directories.', 'podlove-podcasting-plugin-for-wordpress'),
                'default' => true,
            ]);

            do_action('podlove_feeds_directories', $wrapper);

            $wrapper->string('itunes_feed_id', [
                'label' => __('iTunes Feed ID', 'podlove-podcasting-plugin-for-wordpress'),
                'description' => __('Is used to generate a link to the iTunes directory.', 'podlove-podcasting-plugin-for-wordpress').(($feed->itunes_feed_id) ? ' <a href="http://itunes.apple.com/podcast/id'.$feed->itunes_feed_id.'" target="_blank">'.__('Open in iTunes directory').'</a>' : ''),
                'html' => ['class' => 'regular-text podlove-check-input'],
            ]);

            $wrapper->subheader(__('Feed Proxy', 'podlove-podcasting-plugin-for-wordpress'));

            if (!FeedProxy::is_enabled()) {
                $wrapper->select('redirect_http_status', [
                    'label' => __('Redirect Method', 'podlove-podcasting-plugin-for-wordpress'),
                    'description' => __('"Temporary Redirect" is recommended.', 'podlove-podcasting-plugin-for-wordpress'),
                    'options' => [
                        '0' => __('Don\'t redirect', 'podlove-podcasting-plugin-for-wordpress'),
                        '307' => __('Temporary Redirect (HTTP Status 307)', 'podlove-podcasting-plugin-for-wordpress'),
                        '301' => __('Permanent Redirect (HTTP Status 301)', 'podlove-podcasting-plugin-for-wordpress'),
                    ],
                    'default' => 0,
                    'please_choose' => false,
                ]);
            } else {
                do_action('podlove_feed_settings_proxy', $wrapper, $feed);
            }

            $wrapper->string('redirect_url', [
                'label' => __('Redirect Url', 'podlove-podcasting-plugin-for-wordpress'),
                'description' => __('e.g. Feedburner URL', 'podlove-podcasting-plugin-for-wordpress'),
                'html' => ['class' => 'regular-text podlove-check-input', 'data-podlove-input-type' => 'url'],
            ]);

            do_action('podlove_feed_settings_bottom', $wrapper);
        });
    }

    private function edit_template()
    {
        $feed = \Podlove\Model\Feed::find_by_id($_REQUEST['feed']);
        echo '<h3>'.sprintf(__('Edit Feed: %s', 'podlove-podcasting-plugin-for-wordpress'), $feed->name).'</h3>';
        $this->form_template($feed, 'save');
    }
}
