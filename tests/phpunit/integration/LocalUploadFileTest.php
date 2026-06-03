<?php

use Podlove\Model\LocalUploadFile;

/**
 * @internal
 *
 * @coversNothing
 */
class LocalUploadFileTest extends WP_UnitTestCase
{
    public function testPathForUrlResolvesUploadUrlToLocalPath(): void
    {
        $upload_dir = wp_upload_dir();
        $url = trailingslashit($upload_dir['baseurl']).'something/pling.vtt?download=1#transcript';
        $expected_path = wp_normalize_path(trailingslashit($upload_dir['basedir']).'something/pling.vtt');

        $this->assertSame($expected_path, LocalUploadFile::path_for_url($url));
    }

    public function testPathForUrlDecodesUrlEncodedPathSegments(): void
    {
        $upload_dir = wp_upload_dir();
        $url = trailingslashit($upload_dir['baseurl']).'something/space%20file.vtt';
        $expected_path = wp_normalize_path(trailingslashit($upload_dir['basedir']).'something/space file.vtt');

        $this->assertSame($expected_path, LocalUploadFile::path_for_url($url));
    }

    public function testPathForUrlRejectsNonUploadUrls(): void
    {
        $this->assertNull(LocalUploadFile::path_for_url('https://example.com/wp-content/uploads/something/pling.vtt'));
    }

    public function testPathForUrlRejectsTraversalSegments(): void
    {
        $upload_dir = wp_upload_dir();
        $url = trailingslashit($upload_dir['baseurl']).'something/%2e%2e/secret.vtt';

        $this->assertNull(LocalUploadFile::path_for_url($url));
    }
}
