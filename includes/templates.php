<?php

use \Podlove\Model\Template;
use \Podlove\Model\TemplateAssignment;

add_filter( 'the_content', 'podlove_autoinsert_templates_into_content' );

function podlove_autoinsert_templates_into_content( $content ) {

	if ( get_post_type() !== 'podcast' || post_password_required() )
		return $content;

	$template_assignments = TemplateAssignment::get_instance();

	if ( $template_assignments->top ) {
		if ($template = Template::find_by_id( $template_assignments->top )) {
			$shortcode = '[podlove-template template="' . $template->title . '"]';
			if ( stripos( $content, $shortcode ) === false ) {
				$content = $shortcode . $content;
			}
		}
	}

	if ( $template_assignments->bottom ) {
		if ($template = Template::find_by_id( $template_assignments->bottom )) {
			$shortcode = '[podlove-template template="' . $template->title . '"]';
			if ( stripos( $content, $shortcode ) === false ) {
				$content = $content . $shortcode;
			}
		}
	}

	return $content;
}