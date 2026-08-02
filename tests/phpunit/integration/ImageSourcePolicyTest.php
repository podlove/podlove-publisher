<?php

use Podlove\ImageCache\Request;
use Podlove\ImageCache\SourcePolicy;
use Podlove\Model\Image;

/**
 * @internal
 *
 * @coversNothing
 */
class ImageSourcePolicyTest extends WP_UnitTestCase
{
    public function tear_down()
    {
        Image::flush_cache();

        parent::tear_down();
    }

    public function testDataUrisAreBlocked(): void
    {
        $sources = [
            'data:image/png;base64,iVBORw0KGgo=',
            'data:image/svg+xml;base64,PHN2ZyBvbmxvYWQ9ImFsZXJ0KDEpIj48L3N2Zz4=',
            'data:image/svg+xml,<svg onload="alert(1)"></svg>',
            'data:not-valid',
            'DATA:image/png;base64,iVBORw0KGgo=',
            '  data:image/png;base64,iVBORw0KGgo=  ',
            "da\nta:image/svg+xml,<svg></svg>",
            "d\tata:image/svg+xml,<svg></svg>",
            "d\rata:image/svg+xml,<svg></svg>",
            "\x01data:image/svg+xml,<svg></svg>",
        ];

        foreach ($sources as $source) {
            $this->assertTrue(SourcePolicy::is_blocked_source($source));
            $this->assertFalse(SourcePolicy::allows_download($source));
            $this->assertNull(SourcePolicy::direct_url($source));
        }
    }

    public function testSvgSourcesAreBlockedByParsedPath(): void
    {
        $sources = [
            'https://example.test/avatar.svg',
            'http://example.test/avatar.SVG?size=45#fragment',
            'https://example.test/avatar%2Esvg',
            'https://example.test/avatar.svg/',
            'https://example.test/avatar.svg/.',
            'https://example.test/avatar.svg/placeholder/..',
            'https://example.test/avatar.svg/%2e',
            "https://example.test/avatar.s\nvg",
            'https://example.test/avatar.svg\\',
            '/images/avatar.svg',
            'ftp://example.test/avatar.svg',
        ];

        foreach ($sources as $source) {
            $this->assertTrue(SourcePolicy::is_blocked_source($source));
            $this->assertFalse(SourcePolicy::allows_download($source));
            $this->assertNull(SourcePolicy::direct_url($source));
        }
    }

    public function testRasterSourceIsNotMisclassified(): void
    {
        $sources = [
            'https://example.test/avatar.svg.png?format=svg',
            'https://example.test/avatar.svg/./avatar.png',
        ];

        foreach ($sources as $source) {
            $this->assertFalse(SourcePolicy::is_blocked_source($source));
            $this->assertTrue(SourcePolicy::allows_download($source));
            $this->assertSame($source, SourcePolicy::direct_url($source));
        }
    }

    public function testParentPathSegmentsAreBlocked(): void
    {
        $sources = [
            'https://example.test/avatar.svg/../avatar.png',
            'https://example.test/avatar.svg/placeholder/../../avatar.png',
            'https://example.test/images/%2e%2e/avatar.png',
            'https://example.test/images%2f%2E%2E%2favatar.png',
        ];

        foreach ($sources as $source) {
            $this->assertTrue(SourcePolicy::is_blocked_source($source));
            $this->assertFalse(SourcePolicy::allows_download($source));
            $this->assertNull(SourcePolicy::direct_url($source));
        }
    }

    public function testBlockedSourceCannotBeRenderedOrDownloaded(): void
    {
        $source = 'data:image/png;base64,iVBORw0KGgo=';
        $image = (new Image($source, 'Blocked'))->setWidth(45);

        $this->assertNull($image->url());
        $this->assertSame('', $image->image());

        $result = Image::download_url($source);

        $this->assertWPError($result);
        $this->assertSame('http_download_forbidden', $result->get_error_code());
        $this->assertFileDoesNotExist($image->original_file());
    }

    public function testBundledRasterSourceIsCopiedWithoutHttpRequest(): void
    {
        $source = \Podlove\PLUGIN_URL.'/images/contributor-default-avatar.png';
        $image = new Image($source, 'Bundled');
        $http_requests = 0;
        $count_http_request = function () use (&$http_requests) {
            ++$http_requests;

            return new WP_Error('unexpected_http_request');
        };
        add_filter('pre_http_request', $count_http_request);

        try {
            $image->download_source();

            $this->assertSame(0, $http_requests);
            $this->assertTrue($image->source_exists());
            $this->assertFileEquals(\Podlove\PLUGIN_DIR.'images/contributor-default-avatar.png', $image->original_file());
        } finally {
            remove_filter('pre_http_request', $count_http_request);
        }
    }

    public function testLookalikePluginUrlCannotUseLocalFilesystemShortcut(): void
    {
        $source = home_url('/not-a-plugin/'.basename(\Podlove\PLUGIN_DIR).'/images/contributor-default-avatar.png');
        $image = new Image($source, 'Lookalike');
        $http_requests = 0;
        $count_http_request = function () use (&$http_requests) {
            ++$http_requests;

            return new WP_Error('expected_http_request');
        };
        add_filter('pre_http_request', $count_http_request);

        try {
            $image->download_source();

            $this->assertSame(1, $http_requests);
            $this->assertFalse($image->source_exists());
        } finally {
            remove_filter('pre_http_request', $count_http_request);
        }
    }

    public function testSignedSvgCacheRequestIsRejectedBeforeDownload(): void
    {
        $source = 'https://example.test/avatar.svg';
        $unsigned_request = Request::from_values($source, 45, 45, true, 'Avatar');
        $request = Request::from_query_vars(
            $unsigned_request->encoded_source_url(),
            '45',
            '45',
            '1',
            'avatar',
            $unsigned_request->signature()
        );
        $http_requests = 0;
        $count_http_request = function ($response) use (&$http_requests) {
            ++$http_requests;

            return $response;
        };
        add_filter('pre_http_request', $count_http_request);

        try {
            $result = podlove_resolve_image_cache_file($request, $request->has_valid_signature());

            $this->assertWPError($result);
            $this->assertSame('podlove_image_cache_download_forbidden', $result->get_error_code());
            $this->assertSame(0, $http_requests);
        } finally {
            remove_filter('pre_http_request', $count_http_request);
        }
    }

    public function testDataUriCacheRequestIsRejectedDuringParsing(): void
    {
        $request = Request::from_query_vars(
            bin2hex('data:image/png;base64,iVBORw0KGgo='),
            '45',
            '45',
            '1',
            'avatar'
        );

        $this->assertWPError($request);
        $this->assertSame('podlove_image_cache_invalid_source', $request->get_error_code());
    }
}
