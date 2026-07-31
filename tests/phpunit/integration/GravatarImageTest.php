<?php

use Podlove\ImageCache\Request;
use Podlove\ImageCache\SourcePolicy;
use Podlove\Model\Image;
use Podlove\Modules\Contributors\Model\Contributor;
use Podlove\Template\Image as TemplateImage;

/**
 * @internal
 *
 * @coversNothing
 */
class GravatarImageTest extends WP_UnitTestCase
{
    private const LEGACY_HASH = 'b58996c504c5638798eb6b511e6f49af';
    private const SHA256_HASH = '2b3b2b9ce842ab8b6a1c0e2057f57296b01516447e141f9b8816e66d1da0248f';

    public function tear_down()
    {
        Image::flush_cache();

        parent::tear_down();
    }

    public function testContributorUsesNormalizedSha256GravatarUrl(): void
    {
        $contributor = new Contributor();
        $contributor->avatar = ' User@Example.COM ';
        $contributor->publicname = 'Example User';

        $url = $contributor->avatar()->setWidth(150)->url();
        parse_str(wp_parse_url($url, PHP_URL_QUERY), $query);

        $this->assertSame('https', wp_parse_url($url, PHP_URL_SCHEME));
        $this->assertSame('0.gravatar.com', wp_parse_url($url, PHP_URL_HOST));
        $this->assertSame('/avatar/'.hash('sha256', 'user@example.com'), wp_parse_url($url, PHP_URL_PATH));
        $this->assertSame('150', $query['s']);
        $this->assertSame('mp', $query['d']);
        $this->assertSame('g', $query['r']);
    }

    public function testGravatarUsesLargestRequestedDimensionAndCapsSize(): void
    {
        $url = (new Image('https://0.gravatar.com/avatar/'.self::SHA256_HASH.'?s=512&d=mp&r=g', 'Avatar'))
            ->setWidth(2500)
            ->setHeight(100)
            ->url()
        ;
        parse_str(wp_parse_url($url, PHP_URL_QUERY), $query);

        $this->assertSame('2048', $query['s']);
    }

    public function testManuallyEnteredLegacyGravatarUrlBypassesImageCache(): void
    {
        $source = 'http://user:password@www.gravatar.com./avatar/'.strtoupper(self::LEGACY_HASH)
            .'.jpg?s=512&d=mm&r=G&unknown=value#fragment';
        $url = (new Image($source, 'Avatar'))
            ->setWidth(45)
            ->url()
        ;
        parse_str(wp_parse_url($url, PHP_URL_QUERY), $query);

        $this->assertSame('https', wp_parse_url($url, PHP_URL_SCHEME));
        $this->assertSame('0.gravatar.com', wp_parse_url($url, PHP_URL_HOST));
        $this->assertSame('/avatar/'.self::LEGACY_HASH, wp_parse_url($url, PHP_URL_PATH));
        $this->assertSame('45', $query['s']);
        $this->assertSame('mp', $query['d']);
        $this->assertSame('g', $query['r']);
        $this->assertArrayNotHasKey('unknown', $query);
        $this->assertNull(wp_parse_url($url, PHP_URL_FRAGMENT));
        $this->assertNull(wp_parse_url($url, PHP_URL_USER));
        $this->assertStringNotContainsString('/podlove/image/', $url);
    }

    public function testMalformedGravatarPathCannotReachHtml(): void
    {
        $source = 'https://0.gravatar.com/avatar/a.jpg#"></select><img src=x onerror=alert(document.domain)>';
        $url = (new Image($source, 'Avatar'))->setWidth(45)->url();

        $this->assertNull($url);
        $this->assertSame('', esc_url((string) $url));
        $this->assertFalse(SourcePolicy::allows_download($source));
    }

    public function testLookalikeHostIsNotTreatedAsGravatar(): void
    {
        $this->assertFalse(SourcePolicy::is_gravatar_avatar('https://www.gravatar.com.example.test/avatar/'.self::SHA256_HASH));
        $this->assertFalse(SourcePolicy::is_gravatar_avatar('https://docs.gravatar.com/image.jpg'));
    }

    public function testTrailingDotGravatarHostIsBlocked(): void
    {
        $url = 'https://0.gravatar.com./avatar/'.self::SHA256_HASH;

        $this->assertTrue(SourcePolicy::is_gravatar_avatar($url));
        $this->assertFalse(SourcePolicy::allows_download($url));
        $this->assertSame(
            'https://0.gravatar.com/avatar/'.self::SHA256_HASH,
            SourcePolicy::direct_url($url)
        );
    }

    public function testGravatarDownloadIsRejectedWithoutCreatingCacheFiles(): void
    {
        $url = 'https://0.gravatar.com/avatar/'.self::SHA256_HASH.'?s=80';
        $image = new Image($url, 'Avatar');

        $result = Image::download_url($url);
        $image->download_source();

        $this->assertWPError($result);
        $this->assertSame('http_download_forbidden', $result->get_error_code());
        $this->assertFalse($image->source_exists());
        $this->assertFileDoesNotExist($image->original_file());
    }

    public function testGravatarCannotBeResolvedByImageCacheRoute(): void
    {
        $request = Request::from_values('https://0.gravatar.com/avatar/'.self::SHA256_HASH.'?s=512', 45, 45, true, 'Avatar');

        $result = podlove_resolve_image_cache_file($request, true);

        $this->assertWPError($result);
        $this->assertSame('podlove_image_cache_download_forbidden', $result->get_error_code());
    }

    public function testGravatarDataUriFallsBackToDirectUrl(): void
    {
        $image = new Image('https://0.gravatar.com/avatar/'.self::SHA256_HASH.'?s=512&d=mp&r=g', 'Avatar');
        $template_image = new TemplateImage($image);

        $url = $template_image->dataUri(['width' => 100]);
        parse_str(wp_parse_url($url, PHP_URL_QUERY), $query);

        $this->assertSame('0.gravatar.com', wp_parse_url($url, PHP_URL_HOST));
        $this->assertSame('100', $query['s']);
        $this->assertFalse(str_starts_with($url, 'data:'));
        $this->assertFileDoesNotExist($image->original_file());
    }

    public function testGravatarSrcsetUsesDirectProviderUrls(): void
    {
        $image = (new Image('https://0.gravatar.com/avatar/'.self::SHA256_HASH.'?s=512&d=mp&r=g', 'Avatar'))->setWidth(100);
        $document = new DOMDocument();
        $document->loadHTML($image->image());
        $srcset = $document->getElementsByTagName('img')->item(0)->getAttribute('srcset');
        $candidates = explode(', ', $srcset);

        $this->assertCount(3, $candidates);

        foreach ([100, 200, 300] as $index => $expected_size) {
            [$url, $descriptor] = explode(' ', $candidates[$index]);
            parse_str(wp_parse_url($url, PHP_URL_QUERY), $query);

            $this->assertSame('0.gravatar.com', wp_parse_url($url, PHP_URL_HOST));
            $this->assertSame((string) $expected_size, $query['s']);
            $this->assertSame(($index + 1).'x', $descriptor);
            $this->assertStringNotContainsString('/podlove/image/', $url);
        }

        $this->assertFileDoesNotExist($image->original_file());
    }

    public function testRedirectToTrailingDotGravatarHostIsRejectedBeforeRequest(): void
    {
        $requests = [];
        $request_args = [];
        $mock_http_request = function ($response, $args, $url) use (&$requests, &$request_args) {
            $requests[] = $url;
            $request_args[] = $args;
            $location = 'https://0.gravatar.com./avatar/'.self::SHA256_HASH;

            try {
                do_action_ref_array('requests-requests.before_redirect', [&$location]);
            } catch (\Throwable $exception) {
                return new WP_Error('http_request_failed', $exception->getMessage());
            }

            $this->fail('The Gravatar redirect was not rejected.');
        };

        add_filter('pre_http_request', $mock_http_request, 10, 3);

        try {
            $result = Image::download_url('https://example.com/image.jpg');

            $this->assertWPError($result);
            $this->assertSame('http_download_forbidden', $result->get_error_code());
            $this->assertSame(['https://example.com/image.jpg'], $requests);
            $this->assertSame(Request::redirect_limit(), $request_args[0]['redirection']);
        } finally {
            remove_filter('pre_http_request', $mock_http_request, 10);
        }
    }

    public function testDownloadDelegatesRedirectHandlingToWordPress(): void
    {
        $request_args = [];
        $mock_http_request = function ($response, $args) use (&$request_args) {
            $request_args[] = $args;
            file_put_contents($args['filename'], 'image');

            return [
                'headers' => ['content-length' => '5'],
                'body' => '',
                'response' => ['code' => 200, 'message' => 'OK'],
                'cookies' => [],
            ];
        };

        add_filter('pre_http_request', $mock_http_request, 10, 3);

        try {
            $result = Image::download_url('https://example.com/image.jpg');

            $this->assertIsArray($result);
            $this->assertSame(Request::redirect_limit(), $request_args[0]['redirection']);
            $this->assertFileExists($result[0]);
            wp_delete_file($result[0]);
        } finally {
            remove_filter('pre_http_request', $mock_http_request, 10);
        }
    }
}
