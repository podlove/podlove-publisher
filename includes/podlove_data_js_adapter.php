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

function podlove_init_js_adapter()
{
    $data = apply_filters('podlove_data_js', []); ?>
    <script>
      window.PODLOVE_DATA = window.PODLOVE_DATA || {};
      <?php foreach ($data as $key => $value) { ?>
          window.PODLOVE_DATA['<?php echo $key; ?>'] = JSON.parse('<?php echo $value; ?>');
      <?php } ?>

      window.addEventListener('load', function () {
        if (window.initPodloveUI) {
          window.initPodloveUI(window.PODLOVE_DATA);
        }
      })
    </script>
<?php
}
