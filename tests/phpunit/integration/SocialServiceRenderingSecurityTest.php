<?php

use Podlove\Modules\Social\Model\Service;
use Podlove\Modules\Social\Model\ShowService;
use Podlove\Modules\Social\Shortcodes;
use Podlove\Modules\Social\Social;

/**
 * @internal
 *
 * @coversNothing
 */
class SocialServiceRenderingSecurityTest extends WP_UnitTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        podlove_setup_database_tables();
        Social::instance()->load();
        Service::build();
        ShowService::build();
        ShowService::delete_all();
        Service::delete_all();
    }

    public function tearDown(): void
    {
        ShowService::delete_all();
        Service::delete_all();

        parent::tearDown();
    }

    public function testSocialMediaShortcodeKeepsStoredServicePayloadsInert(): void
    {
        $service = new Service();
        $service->category = 'social';
        $service->type = 'test';
        $service->title = '<script>alert(1)</script>';
        $service->description = '" onmouseover="alert(1)';
        $service->logo = 'website.svg';
        $service->url_scheme = '%account-placeholder%';
        $service->save();

        $show_service = new ShowService();
        $show_service->service_id = $service->id;
        $show_service->value = 'javascript:alert(1)';
        $show_service->position = 1;
        $show_service->save();

        $html = Shortcodes::social_media_list();

        $this->assertStringNotContainsString('javascript:', strtolower($html));
        $this->assertStringNotContainsString('<script', strtolower($html));
        $this->assertStringNotContainsString('onmouseover=', strtolower($html));
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
    }
}
