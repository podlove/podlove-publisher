<?php
namespace Podlove\Modules\PodloveWebPlayer\PlayerV5;

class Module
{
    public function load()
    {
        add_action('admin_notices', [$this, 'check_plugin_active']);
    }

    public function check_plugin_active()
    {
        $plugin = "podlove-web-player/podlove-web-player.php";

        if (!is_plugin_active($plugin)) {
            $this->print_admin_notice();
        }
    }

    private function print_admin_notice()
    {
        ?>
      <div class="update-message notice notice-warning notice-alt">
        <p>
          <?php echo __('You need to install the Podlove Web Player plugin to use Podlove Web Player 5 with Podlove Publisher.', 'podlove-podcasting-plugin-for-wordpress') ?>
           <a href="<?php echo admin_url('plugin-install.php?s=podlove+web+player&tab=search&type=term'); ?>"><?php echo __('Install Now', 'podlove-podcasting-plugin-for-wordpress') ?></a>
        </p>
      </div>
      <?php
}
}
