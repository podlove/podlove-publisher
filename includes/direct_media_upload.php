<?php

add_filter('podlove_episode_form_data', 'podlove_add_upload_button_to_form', 10, 2);
add_action('podlove_episode_meta_box_end', 'podlove_add_upload_button_styles');

function podlove_add_upload_button_to_form($form_data, $episode)
{
    $form_data[] = [
        'type' => 'upload',
        'key' => 'file_upload',
        'options' => [
            'label' => __('File Upload', 'podlove-podcasting-plugin-for-wordpress'),
            'media_title' => __('Media File', 'podlove-podcasting-plugin-for-wordpress'),
            'media_button_text' => __('Use Media File', 'podlove-podcasting-plugin-for-wordpress'),
            'form_button_text' => __('Upload Media File', 'podlove-podcasting-plugin-for-wordpress'),
        ],
        'position' => 512,
    ];

    return $form_data;
}

function podlove_add_upload_button_styles()
{
    ?>
<style>
#_podlove_meta_file_upload,
.podlove-media-upload-wrap .podlove_preview_pic,
.podlove-media-upload-wrap p
{
  display: none !important;
}
</style>
<script>
const uploadUrlInput = document.getElementById('_podlove_meta_file_upload')
const slugInput = document.getElementById('_podlove_meta_slug');

uploadUrlInput.addEventListener('change', function (e) {
    const value = e.target.value;
    const slug = value.split('\\').pop().split('/').pop().split('.').shift()
    console.log({slug: slug});
    slugInput.value = slug;
});
</script>
    <?php
}

// ====
// == Override upload_dir so it ignores date subdirectories etc.
// == Instead, defines a dedicated upload directory.
// ====

add_filter('upload_dir', 'podlove_custom_media_upload_dir');

function podlove_custom_media_upload_dir($upload)
{
    $id = (int) $_REQUEST['post_id'];
    $parent = get_post($id)->post_parent;

    // Check the post-type of the current post
    if ('podcast' == get_post_type($id) || 'podcast' == get_post_type($parent)) {
        // override subdir, removing date directories if they are configured
        $upload['subdir'] = '/podcast-media';
    }

    $upload['path'] = $upload['basedir'].$upload['subdir'];
    $upload['url'] = $upload['baseurl'].$upload['subdir'];

    return $upload;
}

// TODO: try multi-file-upload
// TODO: make subdir configurable
// TODO: try empty subdir, does it upload to `/uploads`?
// TODO: extract into module
// TODO: admin notice on activation explaining things (at least mention asset base url)
// TODO: written setup guide
