<?php

use Podlove\ImageCache\GenerationGuard;
use Podlove\ImageCache\Request;
use Podlove\Model\Image;

/**
 * @internal
 *
 * @coversNothing
 */
class ImageCacheSignedUrlTest extends WP_UnitTestCase
{
    private $permalink_structure;

    public function set_up()
    {
        parent::set_up();

        $this->permalink_structure = get_option('permalink_structure');
        Image::flush_cache();
    }

    public function tear_down()
    {
        update_option('permalink_structure', $this->permalink_structure);
        Image::flush_cache();

        parent::tear_down();
    }

    public function testPrettyImageCacheUrlContainsValidSignature(): void
    {
        update_option('permalink_structure', '/%postname%/');

        $url = (new Image('https://example.test/cover.jpg', 'Test Cover'))->setWidth(500)->url();
        $segments = explode('/', trim(wp_parse_url($url, PHP_URL_PATH), '/'));

        $this->assertCount(8, $segments);
        $this->assertSame(['podlove', 'image'], array_slice($segments, 0, 2));

        $request = Request::from_query_vars(
            $segments[2],
            $segments[3],
            $segments[4],
            $segments[5],
            $segments[6],
            $segments[7]
        );

        $this->assertInstanceOf(Request::class, $request);
        $this->assertTrue($request->has_valid_signature());
        $this->assertSame('test-cover', $request->file_name());
    }

    public function testNonPrettyImageCacheUrlContainsValidSignature(): void
    {
        update_option('permalink_structure', '');

        $url = (new Image('https://example.test/cover.jpg', 'Test Cover'))->setHeight(400)->url();
        parse_str(wp_parse_url($url, PHP_URL_QUERY), $query);

        $request = Request::from_query_vars(
            $query['podlove_image_cache_url'],
            $query['podlove_width'],
            $query['podlove_height'],
            $query['podlove_crop'],
            $query['podlove_file_name'],
            $query['podlove_image_cache_signature']
        );

        $this->assertInstanceOf(Request::class, $request);
        $this->assertTrue($request->has_valid_signature());
    }

    public function testEmptyFilenameUsesRouteSafeToken(): void
    {
        update_option('permalink_structure', '/%postname%/');

        $url = (new Image('https://example.test/cover.jpg'))->setWidth(100)->url();
        $segments = explode('/', trim(wp_parse_url($url, PHP_URL_PATH), '/'));

        $this->assertSame(Request::EMPTY_FILE_NAME_TOKEN, $segments[6]);

        $request = Request::from_query_vars(
            $segments[2],
            $segments[3],
            $segments[4],
            $segments[5],
            $segments[6],
            $segments[7]
        );

        $this->assertTrue($request->has_valid_signature());
        $this->assertSame('', $request->file_name());
    }

    public function testChangingAnySignedValueInvalidatesSignature(): void
    {
        $request = Request::from_values('https://example.test/cover.jpg', 500, 400, true, 'cover');
        $this->assertInstanceOf(Request::class, $request);

        $values = [
            [bin2hex('https://attacker.test/cover.jpg'), '500', '400', '1', 'cover'],
            [$request->encoded_source_url(), '501', '400', '1', 'cover'],
            [$request->encoded_source_url(), '500', '401', '1', 'cover'],
            [$request->encoded_source_url(), '500', '400', '0', 'cover'],
            [$request->encoded_source_url(), '500', '400', '1', 'other'],
        ];

        foreach ($values as [$source, $width, $height, $crop, $file_name]) {
            $tampered = Request::from_query_vars(
                $source,
                $width,
                $height,
                $crop,
                $file_name,
                $request->signature()
            );

            $this->assertInstanceOf(Request::class, $tampered);
            $this->assertFalse($tampered->has_valid_signature());
        }
    }

    public function testInvalidAndOversizedDimensionsAreRejected(): void
    {
        $this->assertWPError(Request::from_values('https://example.test/cover.jpg', 0, 0, false, 'cover'));
        $this->assertWPError(Request::from_values('https://example.test/cover.jpg', 4097, 0, false, 'cover'));
        $this->assertWPError(Request::from_query_vars(
            bin2hex('https://example.test/cover.jpg'),
            '0500',
            '0',
            '0',
            'cover'
        ));
    }

    public function testUnsignedCacheMissPerformsNoHttpRequest(): void
    {
        $http_requests = 0;
        $count_http_request = function ($response) use (&$http_requests) {
            ++$http_requests;

            return $response;
        };
        add_filter('pre_http_request', $count_http_request);

        try {
            $request = Request::from_query_vars(
                bin2hex('https://example.test/not-cached.jpg'),
                '500',
                '0',
                '0',
                'not-cached'
            );
            $file = podlove_resolve_image_cache_file($request, false);

            $this->assertWPError($file);
            $this->assertSame('podlove_image_cache_legacy_miss', $file->get_error_code());
            $this->assertSame(0, $http_requests);
        } finally {
            remove_filter('pre_http_request', $count_http_request);
        }
    }

    public function testUnsignedRequestCanReadExistingRendition(): void
    {
        $source_url = 'https://example.test/email.png';
        $file_name = 'email';
        $image = new Image($source_url, $file_name);
        $image->create_basedir();
        $image->copy_as_original_file(\Podlove\PLUGIN_DIR.'lib/modules/social/images/icons/email.png');
        $image->setWidth(10)->generate_resized_copy();

        $request = Request::from_query_vars(bin2hex($source_url), '10', '0', '0', $file_name);
        $file = podlove_resolve_image_cache_file($request, false);

        $this->assertIsString($file);
        $this->assertFileExists($file);
    }

    public function testValidSignedRequestCanDownloadAndGenerateRendition(): void
    {
        $source_url = 'https://example.test/email.png';
        $unsigned_request = Request::from_values($source_url, 10, 0, false, 'email');
        $request = Request::from_query_vars(
            $unsigned_request->encoded_source_url(),
            '10',
            '0',
            '0',
            'email',
            $unsigned_request->signature()
        );
        $http_requests = 0;
        $mock_http_request = function ($response, $args) use (&$http_requests) {
            ++$http_requests;
            copy(\Podlove\PLUGIN_DIR.'lib/modules/social/images/icons/email.png', $args['filename']);

            return [
                'headers' => ['content-length' => (string) filesize($args['filename'])],
                'body' => '',
                'response' => ['code' => 200, 'message' => 'OK'],
                'cookies' => [],
            ];
        };
        add_filter('pre_http_request', $mock_http_request, 10, 2);

        try {
            $file = podlove_resolve_image_cache_file($request, $request->has_valid_signature());

            $this->assertIsString($file);
            $this->assertFileExists($file);
            $this->assertSame(1, $http_requests);
        } finally {
            remove_filter('pre_http_request', $mock_http_request, 10);
        }
    }

    public function testDownloadRejectsResponsesAboveByteLimit(): void
    {
        $max_source_bytes = function () {
            return 8;
        };
        $mock_http_request = function ($response, $args) {
            file_put_contents($args['filename'], '123456789');

            return [
                'headers' => ['content-length' => '9'],
                'body' => '',
                'response' => ['code' => 200, 'message' => 'OK'],
                'cookies' => [],
            ];
        };

        add_filter('podlove_image_cache_max_source_bytes', $max_source_bytes);
        add_filter('pre_http_request', $mock_http_request, 10, 2);

        try {
            $result = Image::download_url('https://example.test/large.jpg');

            $this->assertWPError($result);
            $this->assertSame('http_image_too_large', $result->get_error_code());
        } finally {
            remove_filter('pre_http_request', $mock_http_request, 10);
            remove_filter('podlove_image_cache_max_source_bytes', $max_source_bytes);
        }
    }

    public function testGenerationGuardLocksAndBacksOff(): void
    {
        $key = hash('sha256', 'test-generation-guard');
        $first = new GenerationGuard($key);
        $second = new GenerationGuard($key);

        $this->assertTrue($first->acquire());
        $this->assertFalse($second->acquire());

        $first->release();
        $this->assertTrue($second->acquire());
        $second->record_failure();
        $this->assertTrue($second->is_backed_off());
        $second->clear_failure();
        $second->release();
    }
}
