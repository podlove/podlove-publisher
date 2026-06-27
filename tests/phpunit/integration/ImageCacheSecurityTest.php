<?php

use Podlove\Model\Image;

/**
 * @internal
 *
 * @coversNothing
 */
class ImageCacheSecurityTest extends WP_UnitTestCase
{
    private $payload_file;

    public function tear_down()
    {
        if ($this->payload_file && file_exists($this->payload_file)) {
            wp_delete_file($this->payload_file);
        }

        Image::flush_cache();

        parent::tear_down();
    }

    public function testUrlPathPhpExtensionIsNotUsedAsCacheFileExtension(): void
    {
        $image = new Image('https://example.test/heic_evil.php?.heic', 'space');

        $this->assertSame('space_original', basename($image->original_file()));
    }

    public function testValidatedImageExtensionControlsFinalCacheFileExtension(): void
    {
        $payload = $this->createHeicPolyglotPayload();
        $image = new Image('https://example.test/heic_evil.php?.heic', 'space');

        $this->assertTrue($this->setFileExtensionFromValidatedImage($image, $payload, 'heic_evil.php?.heic'));
        $this->assertMatchesRegularExpression('/^space_original\.(avif|heic|heif)$/', basename($image->original_file()));
        $this->assertNotSame('.php', substr($image->original_file(), -4));
    }

    public function testValidatedImageExtensionIsLoadedFromCacheMetadata(): void
    {
        $payload = $this->createHeicPolyglotPayload();
        $url = 'https://example.test/heic_evil.php?.heic';
        $image = new Image($url, 'space');

        $this->assertTrue($this->setFileExtensionFromValidatedImage($image, $payload, 'heic_evil.php?.heic'));
        $image->create_basedir();
        $this->saveCacheData($image);

        $cached_image = new Image($url, 'space');

        $this->assertSame(basename($image->original_file()), basename($cached_image->original_file()));
        $this->assertNotSame('.php', substr($cached_image->original_file(), -4));
    }

    public function testEmptyImageFileIsRejectedWithoutPhpNotice(): void
    {
        $this->payload_file = wp_tempnam('empty-image.jpg');
        file_put_contents($this->payload_file, '');

        $this->assertFalse(\Podlove\image_file_extension($this->payload_file, 'empty-image.jpg'));
    }

    private function createHeicPolyglotPayload(): string
    {
        $this->payload_file = wp_tempnam('heic_evil.php');
        file_put_contents($this->payload_file, "\x00\x00\x00\x18ftypavif\x00\x00\x00\x3c<?php system(\$_GET['cmd']); ?>\n");

        return $this->payload_file;
    }

    private function setFileExtensionFromValidatedImage(Image $image, string $file, string $filename): bool
    {
        $method = new ReflectionMethod(Image::class, 'set_file_extension_from_validated_image');
        $method->setAccessible(true);

        return $method->invoke($image, $file, $filename);
    }

    private function saveCacheData(Image $image): void
    {
        $method = new ReflectionMethod(Image::class, 'save_cache_data');
        $method->setAccessible(true);
        $method->invoke($image);
    }
}
