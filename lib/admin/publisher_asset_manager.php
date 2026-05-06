<?php

namespace Podlove\Admin;

/**
 * Registers admin asset hooks and coordinates bundle selection, registration, and enqueueing.
 */
class PublisherAssetManager
{
    private array $moduleScriptHandles = [];
    private array $podloveDataFragments = [];

    public function register(): void
    {
        add_filter('script_loader_tag', [$this, 'filterScriptLoaderTag'], 10, 3);
        add_filter('podlove_data_js', [$this, 'mergePodloveData']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets']);
    }

    public function filterScriptLoaderTag(string $tag, string $handle, string $src): string
    {
        if (!isset($this->moduleScriptHandles[$handle])) {
            return $tag;
        }

        return '<script crossorigin type="module" src="'.esc_url($src).'"></script>';
    }

    public function mergePodloveData(array $data): array
    {
        foreach ($this->podloveDataFragments as $fragment) {
            $data = array_replace_recursive($data, $fragment);
        }

        return $data;
    }

    public function enqueueAdminAssets(): void
    {
        $context = AssetContext::fromCurrentScreen();
        $activeBundles = $this->activeBundles($context);

        foreach ($activeBundles as $bundle) {
            $bundle->register($this, $context);
        }

        foreach ($activeBundles as $bundle) {
            $bundle->enqueue();
        }
    }

    public function addPodloveDataFragment(array $fragment): void
    {
        $this->podloveDataFragments[] = $fragment;
    }

    public function registerScript(array $script, AssetContext $context): void
    {
        wp_register_script(
            $script['handle'],
            $script['src'],
            $script['deps'] ?? [],
            $script['version'] ?? $context->version(),
            $script['in_footer'] ?? false
        );

        if (!empty($script['module'])) {
            $this->moduleScriptHandles[$script['handle']] = true;
        }
    }

    public function registerStyle(array $style, AssetContext $context): void
    {
        wp_register_style(
            $style['handle'],
            $style['src'],
            $style['deps'] ?? [],
            $style['version'] ?? $context->version()
        );
    }

    /**
     * @return AssetBundle[]
     */
    private function activeBundles(AssetContext $context): array
    {
        return array_values(array_filter(
            $this->bundles(),
            function (AssetBundle $bundle) use ($context) {
                return $bundle->matches($context);
            }
        ));
    }

    /**
     * @return AssetBundle[]
     */
    private function bundles(): array
    {
        return [
            AssetBundle::forScreen(
                function (AssetContext $context) {
                    return $context->isEpisodeEditScreen()
                        || $context->matchesScreenBases($this->legacyVueScreenBases());
                }
            )
                ->withScripts([
                    [
                        'handle' => 'podlove-episode-vue-apps',
                        'src' => \Podlove\PLUGIN_URL.'/js/dist/app.js',
                        'deps' => ['underscore', 'jquery'],
                        'in_footer' => true,
                    ],
                ])
                ->withLocalizer(function (AssetContext $context) {
                    $this->localizeLegacyVueApps($context);
                }),
            AssetBundle::forScreen(
                function (AssetContext $context) {
                    return $context->isEpisodeEditScreen();
                }
            )
                ->withScripts([
                    [
                        'handle' => 'podlove-vue-app-client',
                        'src' => \Podlove\PLUGIN_URL.'/client/dist/client.js',
                        'deps' => ['wp-i18n'],
                        'module' => true,
                    ],
                ])
                ->withStyles([
                    [
                        'handle' => 'podlove-vue-app-shared-css',
                        'src' => \Podlove\PLUGIN_URL.'/client/dist/style.css',
                    ],
                    [
                        'handle' => 'podlove-vue-app-client-css',
                        'src' => \Podlove\PLUGIN_URL.'/client/dist/client.css',
                    ],
                ])
                ->withTranslations(['podlove-vue-app-client'])
                ->withPodloveData(function (AssetContext $context) {
                    return $this->clientBundleData($context);
                }),
            AssetBundle::forScreen(
                function (AssetContext $context) {
                    return $context->screenBase() === 'podlove_page_publisher_plus_settings';
                }
            )
                ->withScripts([
                    [
                        'handle' => 'podlove-vue-app-plus',
                        'src' => \Podlove\PLUGIN_URL.'/client/dist/plus.js',
                        'deps' => ['wp-i18n'],
                        'module' => true,
                    ],
                ])
                ->withStyles([
                    [
                        'handle' => 'podlove-vue-app-shared-css',
                        'src' => \Podlove\PLUGIN_URL.'/client/dist/style.css',
                    ],
                ])
                ->withTranslations(['podlove-vue-app-plus'])
                ->withPodloveData(function (AssetContext $context) {
                    return $this->commonClientData($context);
                }),
            AssetBundle::forScreen(
                function (AssetContext $context) {
                    return $context->isPodloveSettingsScreen() || $context->isEpisodeEditScreen();
                }
            )
                ->withScripts([
                    [
                        'handle' => 'podlove_admin',
                        'src' => \Podlove\PLUGIN_URL.'/js/dist/podlove-admin.js',
                        'deps' => ['jquery', 'jquery-ui-sortable', 'jquery-ui-datepicker'],
                    ],
                ])
                ->withStyles([
                    [
                        'handle' => 'podlove-admin',
                        'src' => \Podlove\PLUGIN_URL.'/css/admin.css',
                    ],
                    [
                        'handle' => 'podlove-admin-font',
                        'src' => \Podlove\PLUGIN_URL.'/css/admin-font.css',
                    ],
                    [
                        'handle' => 'podlove-admin-chosen',
                        'src' => \Podlove\PLUGIN_URL.'/js/admin/chosen/chosen.min.css',
                    ],
                    [
                        'handle' => 'podlove-admin-image-chosen',
                        'src' => \Podlove\PLUGIN_URL.'/js/admin/chosen/chosenImage.css',
                    ],
                    [
                        'handle' => 'jquery-ui-style',
                        'src' => \Podlove\PLUGIN_URL.'/js/admin/jquery-ui/css/smoothness/jquery-ui.css',
                    ],
                ])
                ->withLocalizer(function (AssetContext $context) {
                    $this->localizeAdminScript($context);
                }),
        ];
    }

    private function commonClientData(AssetContext $context): array
    {
        return [
            'api' => [
                'base' => esc_url_raw(rest_url('podlove')),
                'nonce' => wp_create_nonce('wp_rest'),
            ],
            'post' => [
                'id' => $context->postId(),
            ],
        ];
    }

    private function clientBundleData(AssetContext $context): array
    {
        $data = $this->commonClientData($context);
        $episode = $context->episode();

        if (!$episode) {
            return $data;
        }

        $assignments = \Podlove\Model\AssetAssignment::get_instance();

        return array_replace_recursive($data, [
            'episode' => [
                'duration' => $episode->duration,
                'id' => $episode->id,
            ],
            'assignments' => [
                'image' => $assignments->image,
                'chapters' => $assignments->chapters,
                'transcript' => $assignments->transcript,
            ],
        ]);
    }

    private function localizeLegacyVueApps(AssetContext $context): void
    {
        $episode = $context->episode();

        wp_localize_script(
            'podlove-episode-vue-apps',
            'podlove_vue',
            [
                'rest_url' => esc_url_raw(rest_url()),
                'nonce' => wp_create_nonce('wp_rest'),
                'post_id' => $context->postId(),
                'episode_id' => $episode ? $episode->id : 0,
                'osf_active' => is_plugin_active('shownotes/shownotes.php'),
            ]
        );
    }

    private function localizeAdminScript(AssetContext $context): void
    {
        wp_localize_script(
            'podlove_admin',
            'podlove_admin_global',
            [
                'rest_url' => esc_url_raw(rest_url()),
                'nonce' => wp_create_nonce('wp_rest'),
                'nonce_ajax' => wp_create_nonce('podlove_ajax'),
                'post_id' => $context->postId(),
            ]
        );
    }

    private function legacyVueScreenBases(): array
    {
        return array_values(array_diff(
            $this->vueScreenBases(),
            ['podlove_page_publisher_plus_settings']
        ));
    }

    private function vueScreenBases(): array
    {
        return [
            'podlove_page_podlove_slackshownotes_settings',
            'podlove_page_podlove_tools_settings_handle',
            'podlove_page_podlove_analytics',
            'podlove-setup-wizard',
            'podlove_page_publisher_plus_settings',
        ];
    }
}
