<?php

use Podlove\Cache\TemplateCache;
use Podlove\Model\Template;

/**
 * @internal
 *
 * @coversNothing
 */
class TemplateShortcodeCacheTest extends WP_UnitTestCase
{
    private $template;

    public function setUp(): void
    {
        parent::setUp();

        TemplateCache::get_instance()->purge();

        $this->template = new Template();
        $this->template->title = 'template-cache-test-'.wp_generate_uuid4();
        $this->template->content = 'source output';
        $this->template->save();
    }

    public function tearDown(): void
    {
        $this->template->delete();
        TemplateCache::get_instance()->purge();

        parent::tearDown();
    }

    public function testTwigFilterSignalsRenderFailure(): void
    {
        $render_failed = false;
        $mark_render_failed = static function () use (&$render_failed) {
            $render_failed = true;
        };

        $this->template->content = '{% include "missing-template" %}';
        $this->template->save();

        add_action('podlove_twig_render_error', $mark_render_failed);

        try {
            $html = \Podlove\Template\TwigFilter::apply_to_html($this->template->title);
        } finally {
            remove_action('podlove_twig_render_error', $mark_render_failed);
        }

        $this->assertTrue($render_failed);
        $this->assertStringStartsWith('Twig Error:', $html);
    }

    public function testFailedRenderIsReturnedButNotCached(): void
    {
        $render_count = 0;
        $render_template = static function ($html) use (&$render_count) {
            ++$render_count;

            if (1 === $render_count) {
                do_action('podlove_twig_render_error', null);

                return 'Twig Error: forced test failure';
            }

            return 'fixed output';
        };

        add_filter('podlove_template_raw', $render_template, 20);

        try {
            $first_render = \Podlove\template_shortcode(['template' => $this->template->title]);
            $second_render = \Podlove\template_shortcode(['template' => $this->template->title]);
            $third_render = \Podlove\template_shortcode(['template' => $this->template->title]);
        } finally {
            remove_filter('podlove_template_raw', $render_template, 20);
        }

        $this->assertStringStartsWith('Twig Error:', $first_render);
        $this->assertSame('fixed output', $second_render);
        $this->assertSame('fixed output', $third_render);
        $this->assertSame(2, $render_count);
    }
}
