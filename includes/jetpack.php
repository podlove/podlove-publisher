<?php

// enable jetpack's publicize for our podcast post type
add_action('init', 'podlove_jetpack_enable_publicize');

function podlove_jetpack_enable_publicize() {
    add_post_type_support('podcast', 'publicize');
}