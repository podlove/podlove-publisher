<?php

namespace Podlove\TemplatePages;

use Podlove\Model\Template;

add_action('template_redirect', '\Podlove\TemplatePages\intercept_template');

function intercept_template()
{
    $podlove_template_key = filter_input(INPUT_GET, 'podlove_template_page', FILTER_SANITIZE_SPECIAL_CHARS);

    if (!$podlove_template_key) {
        return;
    }

    $template = Template::find_one_by_title_with_fallback($podlove_template_key);

    if (!$template) {
        return;
    }

    echo \Podlove\template_shortcode(['template' => $template->title]);

    exit;
}
