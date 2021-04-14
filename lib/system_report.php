<?php

namespace Podlove;

class SystemReport
{
    private $fields = [];
    private $notices = [];
    private $errors = [];

    public function __construct()
    {
        $errors = &$this->errors;
        $notices = &$this->notices;

        $this->fields = [
            'site' => ['title' => 'Website', 'callback' => function () {
                return get_site_url();
            }],
            'php_version' => ['title' => 'PHP Version', 'callback' => function () {
                return phpversion();
            }],
            'wp_version' => ['title' => 'WordPress Version', 'callback' => function () {
                return get_bloginfo('version');
            }],
            'theme' => ['title' => 'WordPress Theme', 'callback' => function () {
                $theme = wp_get_theme();

                return $theme->get('Name').' v'.$theme->get('Version');
            },
            ],
            'active plugins' => ['title' => 'Active Plugins', 'callback' => function () {
                $separator = "\n           - ";

                return $separator.implode(
                    $separator,
                    array_map(
                        function ($plugin_path) {
                            $plugin = get_plugin_data(trailingslashit(WP_PLUGIN_DIR).$plugin_path);

                            return sprintf('%s v%s', $plugin['Name'], $plugin['Version']);
                        },
                        get_option('active_plugins')
                    )
                );
            }],
            'db_charset' => ['title' => 'WordPress Database Charset', 'callback' => function () use (&$notices) {
                // Fetch Episode Database Info from "information_scheme" Table
                $db_connection = new \wpdb(DB_USER, DB_PASSWORD, 'information_schema', DB_HOST);
                $episode_database_info = $db_connection->get_row('SELECT * FROM `TABLES` WHERE `TABLE_SCHEMA` = \''.DB_NAME.'\' AND `TABLE_NAME` = \''.\Podlove\Model\Episode::table_name().'\'', OBJECT);

                if (is_object($episode_database_info) && !is_int(strpos($episode_database_info->TABLE_COLLATION, 'utf8'))) {
                    $notices[] = 'Episode Database Charset is not UTF-8! (is '.$episode_database_info->TABLE_COLLATION.')';
                }

                $db_connection->close();

                return DB_CHARSET;
            }],
            'db_collate' => ['title' => 'WordPress Database Collate', 'callback' => function () {
                return DB_COLLATE;
            }],
            'podlove_version' => ['title' => 'Publisher Version', 'callback' => function () {
                return \Podlove\get_plugin_header('Version');
            }],
            'podlove_database_version' => ['title' => 'Publisher Database Version', 'callback' => function () {
                return get_option('podlove_database_version');
            }],
            'player_version' => ['title' => 'Web Player Version', 'callback' => function () {
                return \Podlove\get_webplayer_setting('version');
            }],
            'monolog_version' => ['title' => 'Monolog Version', 'callback' => function () use (&$notices) {
                if (\Monolog\Logger::API > 1) {
                    $notices[] = sprintf(
                        'Monolog version is %s (required by another plugin) but Podlove Publisher requires 1.x. That means no logs can be written to the database.',
                        \Monolog\Logger::API
                    );
                }

                return \Monolog\Logger::API;
            }],
            'open_basedir' => ['callback' => function () use (&$notices) {
                $open_basedir = trim(ini_get('open_basedir'));

                if ($open_basedir != '') {
                    $notices[] = 'The PHP setting "open_basedir" is not empty. This is incompatible with curl, a library required by Podlove Publisher. We have a workaround in place but it is preferred to fix the issue. Please ask your hoster to unset "open_basedir".';
                }

                if ($open_basedir) {
                    return $open_basedir;
                }

                return 'ok';
            }],
            'curl' => ['title' => 'curl Version', 'callback' => function () use (&$errors) {
                $module_loaded = in_array('curl', get_loaded_extensions());
                $function_disabled = stripos(ini_get('disable_functions'), 'curl_exec') !== false;
                $out = '';

                if ($module_loaded) {
                    $curl = curl_version();
                    $out .= $curl['version'];
                } else {
                    $out .= 'EXTENSION MISSING';
                    $errors[] = 'curl extension is not loaded';
                }

                if ($function_disabled) {
                    $out .= ' | curl_exec is disabled';
                    $errors[] = 'curl_exec is disabled';
                }

                return $out;
            }],
            'iconv' => ['callback' => function () use (&$errors) {
                $iconv_available = function_exists('iconv');

                if (!$iconv_available) {
                    $errors[] = 'You need to install/activate php5-iconv';
                }

                return $iconv_available ? 'available' : 'MISSING';
            }],
            'simplexml' => ['callback' => function () use (&$errors) {
                if (!$simplexml = in_array('SimpleXML', get_loaded_extensions())) {
                    $errors[] = 'You need to install/activate the PHP SimpleXML module';
                }

                return $simplexml ? 'ok' : 'missing!';
            }],
            'max_execution_time' => ['callback' => function () {
                return ini_get('max_execution_time');
            }],
            'upload_max_filesize' => ['callback' => function () {
                return ini_get('upload_max_filesize');
            }],
            'memory_limit' => ['callback' => function () {
                return ini_get('memory_limit');
            }],
            'disable_classes' => ['callback' => function () {
                return ini_get('disable_classes');
            }],
            'disable_functions' => ['callback' => function () {
                return ini_get('disable_functions');
            }],
            'permalinks' => ['callback' => function () use (&$errors) {
                $permalinks = \get_option('permalink_structure');

                if (!$permalinks) {
                    $errors[] = sprintf(
                        __('You are using the default WordPress permalink structure. This may cause problems with some podcast clients. Go to %s and set it to anything but default (for example "Post name").', 'podlove-podcasting-plugin-for-wordpress'),
                        admin_url('options-permalink.php')
                    );

                    return __('"non-pretty" Permalinks: Please change permalink structure', 'podlove-podcasting-plugin-for-wordpress');
                }

                return "ok ({$permalinks})";
            }],
            'podlove_permalinks' => ['callback' => function () use (&$errors) {
                if (\Podlove\get_setting('website', 'use_post_permastruct') == 'on') {
                    return 'ok';
                }

                if (stristr(\Podlove\get_setting('website', 'custom_episode_slug'), '%podcast%') === false) {
                    $website_options = get_option('podlove_website');
                    $website_options['use_post_permastruct'] = 'on';
                    update_option('podlove_website', $website_options);
                }

                return 'ok';
            }],
            'podcast_settings' => ['callback' => function () use (&$errors) {
                $out = '';
                $podcast = Model\Podcast::get();

                if (!$podcast->title) {
                    $error = __('Your podcast needs a title.', 'podlove-podcasting-plugin-for-wordpress');
                    $errors[] = $error;
                    $out .= $error;
                }

                if (!$podcast->media_file_base_uri) {
                    $error = __('Your podcast needs an upload location for file storage.', 'podlove-podcasting-plugin-for-wordpress');
                    $errors[] = $error;
                    $out .= $error;
                }

                if (!$out) {
                    $out = 'ok';
                }

                return $out;
            }],
            'web_player' => ['callback' => function () use (&$errors) {
                foreach (get_option('podlove_webplayer_formats', []) as $_ => $media_types) {
                    foreach ($media_types as $extension => $asset_id) {
                        if ($asset_id) {
                            return 'ok';
                        }
                    }
                }

                $error = __('You need to assign at least one asset to the web player.', 'podlove-podcasting-plugin-for-wordpress');
                $errors[] = $error;

                return $error;
            }],
            'podlove_cache' => ['callback' => function () {
                return \Podlove\Cache\TemplateCache::is_enabled() ? 'on' : 'off';
            }],
            'assets' => ['callback' => function () {
                $assets = [];
                foreach (\Podlove\Model\EpisodeAsset::all() as $asset) {
                    $file_type = $asset->file_type();
                    $assets[] = [
                        'extension' => $file_type->extension,
                        'mime_type' => $file_type->mime_type,
                        'feed' => Model\Feed::find_one_by_episode_asset_id($asset->id),
                    ];
                }

                return "\n&nbsp; - ".implode("\n&nbsp; - ", array_map(function ($asset) {
                    return str_pad($asset['extension'], 7).str_pad($asset['mime_type'], 17).($asset['feed'] ? $asset['feed']->get_subscribe_url() : 'no feed');
                }, $assets));
            }],
            'cron' => [
                'callback' => function () use (&$notices) {
                    if (defined('ALTERNATE_WP_CRON') && ALTERNATE_WP_CRON) {
                        $notices[] = 'ALTERNATE_WP_CRON is active. This may sometimes cause failing downloads.';

                        return 'ALTERNATE_WP_CRON active';
                    }

                    return 'ok';
                },
            ],
            'duplicate_guids' => [
                'callback' => function () use (&$errors) {
                    $duplicates = \Podlove\Custom_Guid::find_duplicate_guids();
                    if (count($duplicates)) {
                        $message_base = 'Duplicate episode guids found. Fix as soon as possible as this will lead to trouble in podcast directories and podcast clients. Go to named episodes and use the "regenerate" function.';
                        $message_dups = [];

                        foreach ($duplicates as $guid => $post_ids) {
                            $post_titles = array_map('get_the_title', $post_ids);
                            $message_dups[] = "guid \"{$guid}\" is used by: ".implode(', ', $post_titles);
                        }

                        $errors[] = $message_base.' '.implode('; ', $message_dups);

                        return 'duplicate guids: '.count($duplicates);
                    }

                    return 'ok';
                },
            ],
        ];

        $this->fields = apply_filters('podlove_system_report_fields', $this->fields);

        $this->run();
    }

    public function run()
    {
        $this->errors = [];
        $this->notices = [];

        foreach ($this->fields as $field_key => $field) {
            $result = call_user_func($field['callback']);

            if (is_array($result) && isset($result['message'])) {
                $this->fields[$field_key]['value'] = $result['message'];
                if (isset($result['error'])) {
                    $this->errors[] = $result['error'];
                }
                if (isset($result['notice'])) {
                    $this->notices[] = $result['notice'];
                }
            } else {
                $this->fields[$field_key]['value'] = $result;
            }
        }

        update_option('podlove_global_messages', ['errors' => $this->errors, 'notices' => $this->notices]);
    }

    public function render()
    {
        $rfill = function ($string, $length, $fillchar = ' ') {
            while (strlen($string) < $length) {
                $string .= $fillchar;
            }

            return $string;
        };

        $titles = array_map(function ($entry) {
            return isset($entry['title']) ? $entry['title'] : '';
        }, $this->fields);

        $titles = array_merge(array_keys($titles), array_values($titles));

        $fill_length = 1 + max(array_map(function ($k) {
            return strlen($k);
        }, $titles));

        $out = '';

        foreach ($this->fields as $field_key => $field) {
            $title = isset($field['title']) ? $field['title'] : $field_key;
            $out .= $rfill($title, $fill_length).$field['value']."\n";
        }

        $out .= "\n";

        if ($number_of_errors = count($this->errors)) {
            $out .= sprintf(_n('%s ERROR', '%s ERRORS', $number_of_errors, 'podlove-podcasting-plugin-for-wordpress'), $number_of_errors);
            $out .= ": \n";
            foreach ($this->errors as $error) {
                $out .= "- {$error}\n";
            }
        } else {
            $out .= "0 errors\n";
        }

        if ($number_of_notices = count($this->notices)) {
            $out .= sprintf(_n('%s NOTICE', '%s NOTICES', $number_of_notices, 'podlove-podcasting-plugin-for-wordpress'), $number_of_notices);
            $out .= " (no dealbreaker, but should be fixed if possible): \n";
            foreach ($this->notices as $error) {
                $out .= "- {$error}\n";
            }
        } else {
            $out .= "0 notices\n";
        }

        if (count($this->errors) + count($this->notices) === 0) {
            $out .= 'Nice, Everything looks fine!';
        }

        return $out;
    }
}
