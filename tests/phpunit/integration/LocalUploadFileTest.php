<?php

use Podlove\Model\LocalFile;
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

    public function testGenericResolverMapsUrlWithinConfiguredRoot(): void
    {
        $url = 'https://example.test/static/space%20file.png?download=1#preview';
        $expected_path = wp_normalize_path(\Podlove\PLUGIN_DIR.'images/space file.png');

        $this->assertSame(
            $expected_path,
            LocalFile::path_for_url($url, 'https://example.test/static', \Podlove\PLUGIN_DIR.'images')
        );
    }

    public function testGenericResolverRejectsLookalikeAndTraversalUrls(): void
    {
        $base_url = 'https://example.test/static';
        $base_dir = \Podlove\PLUGIN_DIR.'images';

        $this->assertNull(LocalFile::path_for_url('https://example.test/not-static/image.png', $base_url, $base_dir));
        $this->assertNull(LocalFile::path_for_url($base_url.'/images/../secret.png', $base_url, $base_dir));
        $this->assertNull(LocalFile::path_for_url($base_url.'/images/%2e%2e/secret.png', $base_url, $base_dir));
        $this->assertNull(LocalFile::path_for_url($base_url.'/images%2f..%2fsecret.png', $base_url, $base_dir));
    }

    public function testExistingResolverReturnsContainedPluginAsset(): void
    {
        $url = \Podlove\PLUGIN_URL.'/images/contributor-default-avatar.png';
        $expected_path = wp_normalize_path(realpath(\Podlove\PLUGIN_DIR.'images/contributor-default-avatar.png'));

        $this->assertSame(
            $expected_path,
            LocalFile::existing_path_for_url($url, \Podlove\PLUGIN_URL, \Podlove\PLUGIN_DIR)
        );
    }

    public function testExistingResolverRejectsSymlinkOutsideConfiguredRoot(): void
    {
        $upload_dir = wp_upload_dir();
        $test_dir = trailingslashit($upload_dir['basedir']).'local-file-resolver-test-'.wp_generate_uuid4();
        $base_dir = $test_dir.'/base';
        $outside_file = $test_dir.'/outside.png';
        $symlink = $base_dir.'/linked.png';
        wp_mkdir_p($base_dir);
        copy(\Podlove\PLUGIN_DIR.'images/contributor-default-avatar.png', $outside_file);

        if (!function_exists('symlink') || !@symlink($outside_file, $symlink)) {
            wp_delete_file($outside_file);
            rmdir($base_dir);
            rmdir($test_dir);
            $this->markTestSkipped('Symbolic links are not available.');
        }

        try {
            $this->assertNull(
                LocalFile::existing_path_for_url('https://example.test/static/linked.png', 'https://example.test/static', $base_dir)
            );
        } finally {
            unlink($symlink);
            wp_delete_file($outside_file);
            rmdir($base_dir);
            rmdir($test_dir);
        }
    }
}
