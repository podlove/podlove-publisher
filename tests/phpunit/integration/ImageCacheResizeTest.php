<?php

use Podlove\ImageCache\GenerationGuard;
use Podlove\ImageCache\Request;
use Podlove\Model\Image;

/**
 * @internal
 *
 * @coversNothing
 */
class ImageCacheResizeTest extends WP_UnitTestCase
{
    private const SOURCE_URL = 'https://example.test/resize-email.png';

    public function set_up()
    {
        parent::set_up();
        Image::flush_cache();

        $image = new Image(self::SOURCE_URL, 'email');
        $image->create_basedir();
        $image->copy_as_original_file(\Podlove\PLUGIN_DIR.'lib/modules/social/images/icons/email.png');
    }

    public function tear_down()
    {
        Image::flush_cache();
        parent::tear_down();
    }

    /**
     * @dataProvider unchangedDimensions
     *
     * @param mixed $width
     * @param mixed $height
     * @param mixed $crop
     */
    public function testUnchangedRenditionIsCachedWithoutResizeFailure($width, $height, $crop): void
    {
        $request = Request::from_values(self::SOURCE_URL, $width, $height, $crop, 'email');
        $file = podlove_resolve_image_cache_file($request, true);

        $this->assertIsString($file);
        $this->assertFileExists($file);
        $original = (new Image(self::SOURCE_URL, 'email'))->original_file();
        $this->assertSame(hash_file('sha256', $original), hash_file('sha256', $file));
        $this->assertSame($file, podlove_resolve_image_cache_file($request, false));
        $this->assertSame($file, podlove_resolve_image_cache_file($request, true));

        $guard = new GenerationGuard($request->rendition_key(min($width, 256), min($height, 256)));
        $this->assertFalse($guard->is_backed_off());
    }

    public static function unchangedDimensions(): array
    {
        return [
            'oversized width' => [500, 0, false],
            'equal width' => [256, 0, false],
            'oversized height' => [0, 500, false],
            'equal height' => [0, 256, false],
            'both oversized' => [500, 500, false],
            'both equal' => [256, 256, false],
            'oversized crop' => [500, 500, true],
        ];
    }

    public function testMixedDimensionsStillDownscaleAndCrop(): void
    {
        foreach ([false => [100, 100], true => [256, 100]] as $crop => $expected) {
            $request = Request::from_values(self::SOURCE_URL, 500, 100, (bool) $crop, 'email');
            $file = podlove_resolve_image_cache_file($request, true);

            $this->assertIsString($file);
            $this->assertSame($expected, array_slice(getimagesize($file), 0, 2));
        }
    }

    public function testOversizedDataUriUsesOriginalBytes(): void
    {
        $image = new Image(self::SOURCE_URL, 'email');
        $template = new \Podlove\Template\Image($image);
        $uri = $template->dataUri(['width' => 500]);

        $this->assertStringStartsWith('data:image/png;base64,', $uri);
        $this->assertSame(file_get_contents($image->original_file()), base64_decode(explode(',', $uri, 2)[1]));
        $this->assertFileExists($image->resized_file());
    }

    public function testActualResizeFailureStillFails(): void
    {
        $reject_resize = static function () {
            return false;
        };
        add_filter('image_resize_dimensions', $reject_resize);

        try {
            $request = Request::from_values(self::SOURCE_URL, 100, 0, false, 'email');
            $file = podlove_resolve_image_cache_file($request, true);

            $this->assertWPError($file);
            $this->assertSame('podlove_image_cache_resize_failed', $file->get_error_code());
        } finally {
            remove_filter('image_resize_dimensions', $reject_resize);
            $guard = new GenerationGuard($request->rendition_key(100, 0));
            $guard->clear_failure();
        }
    }
}
