<?php

namespace Podlove\Modules\PodloveWebPlayer\PlayerV5;

class Module
{
    public function load()
    {
        add_action('admin_notices', [$this, 'check_plugin_active']);
        add_filter('podlove_player_form_data', [$this, 'player_config_form_data']);
    }

    public function check_plugin_active()
    {
        $plugin = 'podlove-web-player/podlove-web-player.php';

        if (!is_plugin_active($plugin)) {
            $this->print_admin_notice();
        }
    }

    public function player_config_form_data($config)
    {
        $config[] = [
            'type' => 'callback',
            'key' => 'pwp5_notice',
            'options' => [
                'label' => __('Podlove Web Player 5 Settings', 'podlove-podcasting-plugin-for-wordpress'),
                'callback' => function () {
                    echo __('Podlove Web Player 5 has its own settings page:', 'podlove-podcasting-plugin-for-wordpress');
                    echo ' <a href="'.admin_url('options-general.php?page=podlove-web-player-settings').'">'.__('go to player settings', 'podlove-podcasting-plugin-for-wordpress').'</a>';
                },
            ],
            'position' => 10,
        ];

        return $config;
    }

    private function print_admin_notice()
    {
        ?>
      <div class="update-message notice notice-warning notice-alt">
        <p>
          <?php echo __('You need to install the Podlove Web Player plugin to use Podlove Web Player 5 with Podlove Publisher.', 'podlove-podcasting-plugin-for-wordpress'); ?>
           <a href="<?php echo admin_url('plugin-install.php?s=podlove+web+player&tab=search&type=term'); ?>"><?php echo __('Install Now', 'podlove-podcasting-plugin-for-wordpress'); ?></a>
        </p>
      </div>
      <?php
    }
}
