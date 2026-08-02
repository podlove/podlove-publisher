<?php

use Podlove\Custom_Guid;

/**
 * @internal
 *
 * @coversNothing
 */
class CustomGuidRenderingSecurityTest extends WP_UnitTestCase
{
    public function testCustomGuidIsSanitizedOnWrite(): void
    {
        $post_id = self::factory()->post->create(['post_type' => 'podcast']);

        Custom_Guid::save_form($post_id, ['guid' => '<script>alert(1)</script>custom-guid']);

        $guid = get_post_meta($post_id, '_podlove_guid', true);
        $this->assertStringNotContainsString('<script', strtolower($guid));
        $this->assertStringContainsString('custom-guid', $guid);
    }

    public function testCustomGuidIsEscapedInEpisodeEditor(): void
    {
        global $post;

        $post_id = self::factory()->post->create(['post_type' => 'podcast']);
        update_post_meta($post_id, '_podlove_guid', '"><img src=x onerror=alert(1)>');
        $post = get_post($post_id);
        setup_postdata($post);

        ob_start();
        Custom_Guid::meta_box_callback();
        $html = ob_get_clean();
        wp_reset_postdata();

        $this->assertStringNotContainsString('<img src=x', strtolower($html));
        $this->assertStringContainsString('&lt;img src=x onerror=alert(1)&gt;', $html);
        $this->assertStringContainsString('$("#guid_preview").text(result.guid);', $html);
        $this->assertStringNotContainsString('$("#guid_preview").html(result.guid);', $html);
    }
}
