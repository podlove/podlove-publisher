<?php

use Podlove\Model\Template;
use Podlove\Model\TemplateAssignment;

add_filter('the_content', 'podlove_autoinsert_templates_into_content');
add_action('wp_head', 'podlove_autoinsert_templates_head');
add_action('wp_footer', 'podlove_autoinsert_templates_footer');
add_action('wp_body_open', 'podlove_autoinsert_templates_header');

function podlove_autoinsert_templates_into_content($content)
{
    if (get_post_type() !== 'podcast' || post_password_required()) {
        return $content;
    }

    $template_assignments = TemplateAssignment::get_instance();

    if ($template_assignments->top) {
        if ($template = Template::find_one_by_title_with_fallback($template_assignments->top)) {
            $shortcode = '[podlove-template template="'.$template->title.'"]';
            if (stripos($content, $shortcode) === false) {
                $content = $shortcode.$content;
            }
        }
    }

    if ($template_assignments->bottom) {
        if ($template = Template::find_one_by_title_with_fallback($template_assignments->bottom)) {
            $shortcode = '[podlove-template template="'.$template->title.'"]';
            if (stripos($content, $shortcode) === false) {
                $content = $content.$shortcode;
            }
        }
    }

    return $content;
}

function podlove_autoinsert_templates_head()
{
    $template_assignments = TemplateAssignment::get_instance();

    if ($template_assignments->head) {
        if ($template = Template::find_one_by_title_with_fallback($template_assignments->head)) {
            echo \Podlove\template_shortcode([
                'template' => $template->title,
            ]);
        }
    }
}

function podlove_autoinsert_templates_footer()
{
    $template_assignments = TemplateAssignment::get_instance();

    if ($template_assignments->footer) {
        if ($template = Template::find_one_by_title_with_fallback($template_assignments->footer)) {
            echo \Podlove\template_shortcode([
                'template' => $template->title,
            ]);
        }
    }
}

function podlove_autoinsert_templates_header()
{
    $template_assignments = TemplateAssignment::get_instance();

    if ($template_assignments->header) {
        if ($template = Template::find_one_by_title_with_fallback($template_assignments->header)) {
            echo \Podlove\template_shortcode([
                'template' => $template->title,
            ]);
        }
    }
}
