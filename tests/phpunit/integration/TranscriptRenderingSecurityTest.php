<?php

use Podlove\Model\Episode;
use Podlove\Modules\Transcripts\Template\Group;
use Podlove\Modules\Transcripts\Template\Line;
use Podlove\Modules\Transcripts\TemplateExtensions;
use Podlove\Modules\Transcripts\Transcripts;
use Podlove\Template\Episode as TemplateEpisode;
use Podlove\Template\TwigFilter;

/**
 * @internal
 *
 * @coversNothing
 */
class TranscriptRenderingSecurityTest extends WP_UnitTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Transcripts::instance()->load();
    }

    public function testPublicTranscriptEscapesCueContent(): void
    {
        $line = new Line([
            'start_ms' => 0,
            'end_ms' => 1000,
            'text' => '<script>alert(1)</script><img src=x onerror=alert(1)>',
        ]);
        $group = new Group([$line], null, 'speaker');
        $episode = new TemplateEpisode(new Episode());
        $add_episode = function ($context) use ($episode) {
            $context['episode'] = $episode;

            return $context;
        };
        $add_transcript = function () use ($group) {
            return [$group];
        };

        remove_filter('podlove_template_episode_method_transcript', [TemplateExtensions::class, 'accessorEpisodeTranscript']);
        add_filter('podlove_template_episode_method_transcript', $add_transcript);
        add_filter('podlove_templates_global_context', $add_episode);
        $html = TwigFilter::apply_to_html('@transcripts/transcript.twig');
        remove_filter('podlove_templates_global_context', $add_episode);
        remove_filter('podlove_template_episode_method_transcript', $add_transcript);
        add_filter('podlove_template_episode_method_transcript', [TemplateExtensions::class, 'accessorEpisodeTranscript'], 10, 4);

        $this->assertStringNotContainsString('<script', strtolower($html));
        $this->assertStringNotContainsString('<img', strtolower($html));
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
        $this->assertStringContainsString('&lt;img src=x onerror=alert(1)&gt;', $html);
    }
}
