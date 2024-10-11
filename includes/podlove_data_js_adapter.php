<?php

/**
 * Add data to the window.PODLOVE_DATA interface using the podlove_data_js hook.
 *
 * Example:
 *
 *     add_filter('podlove_data_js', function ($data) {
 *         $data['my_module_name'] = ['my' => 'module values'];
 *         return $data;
 *     });
 */
add_action('admin_head', 'podlove_init_js_adapter', 3);

add_filter('podlove_data_js', 'podlove_js_adapter_inject_settings');

function podlove_init_js_adapter()
{
    $data = apply_filters('podlove_data_js', []); ?>

    <script>
      window.PODLOVE_DATA = window.PODLOVE_DATA || { baseUrl: '<?php echo home_url(); ?>' };
      <?php foreach ($data as $key => $value) { ?>
          window.PODLOVE_DATA['<?php echo $key; ?>'] = <?php echo wp_json_encode($value); ?>;
      <?php } ?>

      window.addEventListener('load', function () {
        if (window.initPodloveUI) {
          window.initPodloveUI(window.PODLOVE_DATA);
        }
      })
    </script>
<?php
}

function podlove_js_adapter_inject_settings($data)
{
    $defaults = \Podlove\get_setting_defaults();
    $podcast = \Podlove\Model\Podcast::get();

    $settings_tab_names = ['website', 'metadata', 'tracking'];

    $data['expert_settings'] = array_reduce($settings_tab_names, function ($tabs, $tab_name) use ($defaults) {
        $tabs[$tab_name] = array_reduce(array_keys($defaults[$tab_name]), function ($settings, $setting_name) use ($tab_name) {
            $settings[$setting_name] = \Podlove\get_setting($tab_name, $setting_name);

            return $settings;
        }, []);

        return $tabs;
    }, []);

    $data['media'] = ['base_uri' => $podcast->get_media_file_base_uri()];
    $data['modules'] = \Podlove\Modules\Base::get_active_module_names();

    return $data;
}
