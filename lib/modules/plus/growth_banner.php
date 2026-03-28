<?php

namespace Podlove\Modules\Plus;

class GrowthBanner
{
    public const MIN_EPISODES = 10;
    public const DISMISS_NONCE_ACTION = 'podlove_plus_growth_banner_dismiss';
    public const BANNER_NAME = PromotionCoordinator::GROWTH_BANNER;

    private $coordinator;

    public function __construct(PromotionCoordinator $coordinator)
    {
        $this->coordinator = $coordinator;
    }

    public function init()
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('admin_init', [$this, 'maybe_handle_dismiss']);
        add_action('admin_notices', [$this, 'render']);
        add_action('wp_ajax_podlove_plus_growth_banner_dismiss', [$this, 'ajax_dismiss']);
    }

    public function enqueue_assets()
    {
        if (!$this->should_render()) {
            return;
        }

        $version = \Podlove\get_plugin_header('Version');
        wp_enqueue_style('podlove-admin', \Podlove\PLUGIN_URL.'/css/admin.css', [], $version);
        wp_enqueue_style('podlove-admin-font', \Podlove\PLUGIN_URL.'/css/admin-font.css', [], $version);
    }

    public function render()
    {
        if (!$this->should_render()) {
            return;
        }

        $dismiss_url = wp_nonce_url(add_query_arg('podlove_dismiss_plus_growth_banner', '1'), self::DISMISS_NONCE_ACTION);
        ?>
        <div id="podlove-plus-growth-banner-wrap" style="margin: 20px 20px 0 2px;">
            <div id="podlove-plus-growth-banner" class="plus-banner" style="max-width: none;">
                <a
                    class="podlove-plus-growth-banner-dismiss"
                    href="<?php echo esc_url($dismiss_url); ?>"
                    aria-label="<?php esc_attr_e('Dismiss', 'podlove-podcasting-plugin-for-wordpress'); ?>"
                    style="position: absolute; top: 12px; right: 14px; color: rgba(255, 255, 255, 0.85); text-decoration: none; font-size: 22px; line-height: 1;"
                >&times;</a>
                <h3><?php esc_html_e('Make your podcast delivery more reliable', 'podlove-podcasting-plugin-for-wordpress'); ?></h3>
                <div class="plus-banner-content">
                    <p><?php esc_html_e('Publisher PLUS helps you keep your feed fast during traffic spikes and host your podcast files on infrastructure built for podcast delivery, so your WordPress site has less to handle.', 'podlove-podcasting-plugin-for-wordpress'); ?></p>
                </div>
                <div class="plus-banner-footer">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=publisher_plus_settings')); ?>" class="btn">
                        <?php esc_html_e('Explore Publisher PLUS', 'podlove-podcasting-plugin-for-wordpress'); ?>
                    </a>
                    <div class="corner-logo">
                        <svg class="logo" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 99.32 160.81" style="width: 18px; height: 24px;">
                            <path fill="#ffffff" d="M78.119 9c6.728 0 12.201 5.474 12.201 12.202V139.61c0 6.728-5.474 12.201-12.201 12.201H21.2c-6.727 0-12.2-5.473-12.2-12.201V21.202C9 14.474 14.473 9 21.2 9zm0-9H21.2C9.493 0 0 9.493 0 21.202V139.61c0 11.708 9.493 21.201 21.2 21.201h56.919c11.71 0 21.201-9.492 21.201-21.201V21.202C99.32 9.493 89.829 0 78.119 0z"/>
                            <path fill="#ffffff" d="M49.576 90.412c12.742 0 23.069 10.327 23.069 23.068 0 12.74-10.327 23.069-23.069 23.069-12.738 0-23.067-10.329-23.067-23.069 0-12.741 10.329-23.068 23.067-23.068m0-9c-17.682 0-32.067 14.386-32.067 32.068 0 17.683 14.385 32.069 32.067 32.069 17.683 0 32.069-14.386 32.069-32.069.001-17.682-14.386-32.068-32.069-32.068z"/>
                            <g clip-rule="evenodd">
                                <path fill="none" stroke="#ffffff" stroke-miterlimit="10" stroke-width="9" d="M72.895 46.223l-23.57 23.583L25.758 46.22c-2.649-2.7-4.285-6.399-4.285-10.481 0-8.267 6.702-14.968 14.968-14.968 5.485 0 10.278 2.949 12.885 7.347 2.606-4.398 7.401-7.347 12.884-7.347 8.268 0 14.97 6.701 14.97 14.968 0 4.082-1.636 7.783-4.285 10.484z"/>
                                <path fill="#ffffff" fill-rule="evenodd" d="M49.577 105.223c4.561 0 8.26 3.698 8.26 8.257 0 4.562-3.699 8.258-8.26 8.258-4.56 0-8.257-3.696-8.257-8.258 0-4.559 3.697-8.257 8.257-8.257z"/>
                            </g>
                        </svg>
                        <div class="logo-text"><?php esc_html_e('Publisher PLUS', 'podlove-podcasting-plugin-for-wordpress'); ?></div>
                    </div>
                </div>
            </div>
        </div>
        <script>
            (function() {
                document.addEventListener('click', function(event) {
                    const dismissButton = event.target.closest('#podlove-plus-growth-banner .podlove-plus-growth-banner-dismiss');
                    if (!dismissButton) {
                        return;
                    }

                    const data = new window.FormData();
                    data.append('action', 'podlove_plus_growth_banner_dismiss');
                    data.append('_ajax_nonce', '<?php echo esc_js(wp_create_nonce(self::DISMISS_NONCE_ACTION)); ?>');

                    fetch(ajaxurl, {
                        method: 'POST',
                        body: data,
                        credentials: 'same-origin'
                    });
                });
            }());
        </script>
        <?php
    }

    public function maybe_handle_dismiss()
    {
        if (!isset($_GET['podlove_dismiss_plus_growth_banner'])) {
            return;
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        check_admin_referer(self::DISMISS_NONCE_ACTION);
        $this->coordinator->dismiss(self::BANNER_NAME);

        wp_safe_redirect(remove_query_arg(['podlove_dismiss_plus_growth_banner', '_wpnonce']));
        exit;
    }

    public function ajax_dismiss()
    {
        check_ajax_referer(self::DISMISS_NONCE_ACTION);

        if (!current_user_can('manage_options')) {
            wp_send_json_error([], 403);
        }

        $this->coordinator->dismiss(self::BANNER_NAME);
        wp_send_json_success();
    }

    private function should_render()
    {
        return $this->coordinator->should_render(self::BANNER_NAME);
    }
}
