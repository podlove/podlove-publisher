<?php
namespace Podlove\Model;

use Symfony\Component\Yaml\Yaml;
use \Podlove\Cache\TemplateCache;
use \Podlove\Log;

/**
 * Image Object
 *
 * Usage
 *
 *     // get url, resized to 100px width, keep aspect ratio
 *     $image = (new Image($url))->setWidth(100)->url();
 *
 *     // get url, resized to 100px width and 50px height, cropped
 *     $image = (new Image($url))
 *      ->setWidth(100)
 *       ->setHeight(50)
 *           ->setCrop(true)
 *         ->url();
 *
 *   // get image tag with custom alt text and title
 *   $image = (new Image($url))->image(["alt" => "custom alt", "title" => "custom title"]);
 */
class Image
{

    // URL/file properties
    private $id;
    private $source_url;
    private $file_name;
    private $file_extension;
    private $upload_basedir;
    private $upload_baseurl;

    // image properties
    private $crop   = false;
    private $width  = null;
    private $height = null;

    // html rendering properties
    private $retina = true;

    /**
     * Create image object
     *
     * Manage remote image objects. Cache locally so we can resize and serve
     * optimized image dimensions.
     *
     * @param string $url  Remote image URL
     * @param string $name (optional) image file name prefix
     */
    public function __construct($url, $file_name = '')
    {
        $this->source_url = trim($url);
        $this->file_name  = sanitize_title($file_name);

        // manually remove troublemaking characters
        // @see https://community.podlove.org/t/solved-kind-of-cover-art-disappears-caching-issue/478/
        // @see https://sendegate.de/t/problem-mit-caching-von-grafiken/2947
        if (function_exists('iconv')) {
            $this->file_name = iconv('UTF-8', 'ASCII//TRANSLIT', $this->file_name);
        }
        $this->file_name = preg_replace('~[^-a-z0-9_]+~', '', $this->file_name);

        $this->file_extension = $this->extract_file_extension();
        $this->id             = md5($url . $this->file_name);

        // create subdirectories to avoid too many directories in the root directory
        $id_directory = substr($this->id, 0, 2) . '/' . substr($this->id, 2);

        $this->upload_basedir = self::cache_dir() . $id_directory;
        $this->upload_baseurl = content_url('cache/podlove/') . $id_directory;
    }

    public static function cache_dir()
    {
        return trailingslashit(WP_CONTENT_DIR) . 'cache/podlove/';
    }

    /**
     * Delete all image caches.
     */
    public static function flush_cache()
    {

        $dir = self::cache_dir();

        if (!file_exists($dir)) {
            return;
        }

        $it    = new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new \RecursiveIteratorIterator($it, \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }
        rmdir($dir);
    }

    /**
     * Set to true if resizing should crop when necessary.
     *
     * @param  bool $crop Crop image if given dimensions deviate from original aspect ratio.
     * @return $this for chaining
     */
    public function setCrop($crop)
    {
        $this->crop = (bool) $crop;
        return $this;
    }

    public function setWidth($width)
    {

        if (!$width) {
            return $this;
        }

        $this->width = (int) $width;
        $this->determineMissingDimension();

        return $this;
    }

    public function setHeight($height)
    {

        if (!$height) {
            return $this;
        }

        $this->height = (int) $height;
        $this->determineMissingDimension();

        return $this;
    }

    private function determineMissingDimension()
    {
        @list($width, $height) = getimagesize($this->original_file());

        if ($width * $height == 0) {
            Log::get()->addWarning('Unable to determine image size for ' . $this->original_file());
            return;
        }

        if (!$this->height) {
            $this->height = $this->determineHeightFromWidth($this->width);
        } else {
            $this->width = $this->determineWidthFromHeight($this->height);
        }
    }

    private function determineWidthFromHeight($givenHeight)
    {
        @list($width, $height) = getimagesize($this->original_file());

        if ($width * $height > 0) {
            return round($givenHeight * $width / $height);
        } else {
            return 0;
        }
    }

    private function determineHeightFromWidth($givenWidth)
    {
        @list($width, $height) = getimagesize($this->original_file());

        if ($width * $height > 0) {
            return round($givenWidth / $width * $height);
        } else {
            return 0;
        }
    }

    public function setRetina($retina)
    {
        $this->retina = (bool) $retina;
        return $this;
    }

    /**
     * Get URL for resized image.
     *
     * Examples
     *
     *     $image->url(); // returns image URL
     *
     * @return string image URL
     */
    public function url()
    {

        if (empty($this->source_url)) {
            return null;
        }

        // In case the image cache doesn't work, it can be deactivated by
        // defining the PHP constant PODLOVE_DISABLE_IMAGE_CACHE = true.
        // It's not recommended since that leads to all images being delivered full size
        // instead of optimized resolutions.
        if (defined('PODLOVE_DISABLE_IMAGE_CACHE') && PODLOVE_DISABLE_IMAGE_CACHE) {
            return $this->source_url;
        }

        // if neither width nor height are available something went horribly wrong,
        // so we better bail and return the source url instead
        if (!$this->width && !$this->height) {
            return $this->source_url;
        }

        if (!$this->file_extension) {
            Log::get()->addWarning(sprintf(__('Unable to determine file extension for %s.'), $this->source_url));
            return apply_filters('podlove_image_url', $this->source_url);
        }

        // when PODLOVE_IMAGE_CACHE_FORCE_DYNAMIC_URL is set to true, the static
        // "physical" URL is never exposed, only the dynamic URL. This can be
        // helpful when page caches keep serving the static URL even though it
        // does not exist for some reason. The dynamic URL always works.
        // Drawback is that serving with the dynamic URL is a bit slower because
        // it has to go through the PHP stack.
        $force_dynamic_url = defined('PODLOVE_IMAGE_CACHE_FORCE_DYNAMIC_URL') && PODLOVE_IMAGE_CACHE_FORCE_DYNAMIC_URL;

        if (!$force_dynamic_url && file_exists($this->resized_file())) {
            $url = $this->resized_url();
        } else {

            $source_url = \Podlove\PHP\str2hex($this->source_url);
            $width      = (int) $this->width;
            $height     = (int) $this->height;
            $crop       = (int) $this->crop;
            $file_name  = urlencode($this->file_name);

            if (get_option('permalink_structure')) {
                $path = '/podlove/image/'
                    . $source_url
                    . '/' . $width
                    . '/' . $height
                    . '/' . $crop
                    . '/' . $file_name;
            } else {
                $path = add_query_arg([
                    'podlove_image_cache_url' => $source_url,
                    'podlove_width'           => $width,
                    'podlove_height'          => $height,
                    'podlove_crop'            => $crop,
                    'podlove_file_name'       => $file_name,
                ], 'index.php');
            }

            $url = home_url($path);

        }

        return apply_filters('podlove_image_url', $url);
    }

    /**
     * Get HTML image tag for resized image.
     *
     * Examples
     *
     *     $image->image(); // returns image tag
     *
     * @param  array $args List of arguments
     *         - id: Set image tag "id" attribute.
     *         - class: Set image tag "class" attribute.
     *         - style: Set image tag "style" attribute.
     *         - alt: Set image tag "alt" attribute.
     *         - title: Set image tag "title" attribute.
     *      - width: Image width. Set width and leave height blank to keep the orinal aspect ratio.
     *         - height: Image height. Set height and leave width blank to keep the orinal aspect ratio.
     *       - attributes: List of other HTML attributes, for example: ['data-foo' => 'bar']
     * @return string HTML image tag
     */
    public function image($args = [])
    {

        $defaults = [
            'id'         => '',
            'class'      => '',
            'style'      => '',
            'alt'        => '',
            'title'      => '',
            'width'      => $this->width,
            'height'     => $this->height,
            'attributes' => [],
        ];
        $args = wp_parse_args($args, $defaults);

        // put everything in 'attributes' for easy iteration
        foreach (['id', 'class', 'style', 'alt', 'title', 'width', 'height'] as $attr) {
            if ($args[$attr]) {
                $args['attributes'][$attr] = $args[$attr];
            }

        }

        $dom = new \Podlove\DomDocumentFragment;
        $img = $dom->createElement('img');

        foreach ($args['attributes'] as $key => $value) {
            $img->setAttribute($key, $value);
        }

        $img->setAttribute('src', $this->url());

        if ($this->retina && $srcset = $this->srcset()) {
            $img->setAttribute('srcset', $srcset);
        }

        $dom->appendChild($img);

        return (string) $dom;
    }

    /**
     * Generate srcset attribute for img tag
     *
     * @return string|NULL
     */
    private function srcset()
    {
        @list($max_width, $max_height) = getimagesize($this->original_file());

        if ($this->width * 2 > $max_width) {
            return null;
        }

        $sizes = ['1x' => $this->url()];

        if ($this->width * 2 <= $max_width) {
            $img2x = (new Image($this->source_url, $this->file_name))
                ->setCrop($this->crop)
                ->setRetina($this->retina)
                ->setWidth($this->width * 2);

            $sizes['2x'] = $img2x->url();
        }

        if ($this->width * 3 <= $max_width) {
            $img3x = (new Image($this->source_url, $this->file_name))
                ->setCrop($this->crop)
                ->setRetina($this->retina)
                ->setWidth($this->width * 3);

            $sizes['3x'] = $img3x->url();
        }

        $sources = [];
        foreach ($sizes as $factor => $url) {
            $sources[] = $url . ' ' . $factor;
        }

        return implode(", ", $sources);
    }

    public function file_name($size_slug)
    {
        if ($this->file_name) {
            return $this->file_name . '_' . $size_slug . '.' . $this->file_extension;
        } else {
            return $size_slug . '.' . $this->file_extension;
        }
    }

    public function source_exists()
    {
        return is_file($this->original_file());
    }

    private function cache_file()
    {
        return implode(DIRECTORY_SEPARATOR, [$this->upload_basedir, 'cache.yml']);
    }

    public function original_file()
    {
        return implode(DIRECTORY_SEPARATOR, [$this->upload_basedir, $this->file_name('original')]);
    }

    public function resized_file()
    {
        return implode(DIRECTORY_SEPARATOR, [$this->upload_basedir, $this->file_name($this->size_slug())]);
    }

    private function original_url()
    {
        return implode('/', [$this->upload_baseurl, $this->file_name('original')]);
    }

    private function resized_url()
    {
        return implode('/', [$this->upload_baseurl, $this->file_name($this->size_slug())]);
    }

    public function generate_resized_copy()
    {

        if (!\Podlove\is_image($this->original_file())) {
            Log::get()->addWarning('Podlove Image Cache: Not an image (' . $this->original_file() . ')');
            return;
        }

        $editor_args = [
            'mime_type' => \Podlove\get_image_mime_type(\Podlove\get_image_type($this->original_file())),
        ];

        $image = wp_get_image_editor($this->original_file(), $editor_args);

        if (is_wp_error($image)) {
            Log::get()->addWarning('Podlove Image Cache: Unable to resize. ' . $image->get_error_message());
            return;
        }

        $result = $image->resize($this->width, $this->height, $this->crop);

        if (is_wp_error($result)) {
            Log::get()->addWarning('Podlove Image Cache: Unable to resize. ' . $result->get_error_message());
            return;
        }

        $result = $image->save($this->resized_file());

        if (is_wp_error($result)) {
            Log::get()->addWarning('Podlove Image Cache: Unable to resize. ' . $result->get_error_message());
            return;
        }

        // when a new image size is created, Templace Cache must be cleared
        TemplateCache::get_instance()->setup_global_purge();
    }

    private function size_slug()
    {

        $crop = $this->crop ? 'c' : '';

        if ($this->width || $this->height) {
            return $this->width . 'x' . $this->height . $crop;
        } else {
            return 'original';
        }

    }

    public function redownload_source()
    {
        $this->download_source();
        $this->delete_resized_versions();
    }

    private function delete_resized_versions()
    {
        $resized_versions = implode(DIRECTORY_SEPARATOR, [$this->upload_basedir, "*x*.*"]);
        array_map('unlink', glob($resized_versions));
    }

    public function download_source()
    {

        $source_url  = $this->source_url;
        $current_url = $this->source_url;

        $source_domain  = parse_url($source_url, PHP_URL_HOST);
        $current_domain = explode(":", $_SERVER["HTTP_HOST"])[0];

        // if domains match, see if the image is part of the Publisher
        // and can be copied on the filesystem, skipping http
        if ($current_domain == $source_domain) {

            $plugin_dirname = basename(\Podlove\PLUGIN_DIR, true);

            if (stristr($source_url, $plugin_dirname)) {
                $path = explode($plugin_dirname, $source_url)[1];
                $file = untrailingslashit(\Podlove\PLUGIN_DIR) . $path;

                if (file_exists($file) && \Podlove\is_image($file)) {
                    $this->create_basedir();
                    $this->save_cache_data();
                    $this->copy_as_original_file($file);
                    return;
                }
            }
        }

        /**
         * The following section is only reached if the downloaded image is not part of the Publisher.
         */

        // for download_url()
        require_once ABSPATH . 'wp-admin/includes/file.php';

        $result = $this->download_url($this->source_url);

        # TODO idea:
        # - whenever an image fetch fails, blacklist that URL from image caching
        # - when more than 100(?) URLs are blacklisted, deactivate image caching per setting
        # - when that setting is set, display an info somewhere why that is, what it is and what to do about it

        if (is_wp_error($result)) {
            Log::get()->addWarning(
                sprintf(__('Podlove Image Cache: Unable to download image. %s.'), $result->get_error_message()),
                ['url' => $this->source_url]
            );
            return;
        }

        list($temp_file, $response) = $result;

        if (is_wp_error($temp_file)) {
            Log::get()->addWarning(
                sprintf(__('Podlove Image Cache: Unable to download image. %s.'), $temp_file->get_error_message()),
                ['url' => $this->source_url]
            );
        }

        if (!\Podlove\is_image($temp_file)) {
            Log::get()->addWarning(
                sprintf(__('Podlove Image Cache: Downloaded file is not an image.')),
                ['url' => $this->source_url]
            );
            @unlink($temp_file);
            return;
        }

        $this->create_basedir();
        $this->save_cache_data($response);
        $this->move_as_original_file($temp_file);
        @unlink($temp_file);
        $this->add_donotbackup_dotfile();
    }

    public function create_basedir()
    {
        if (!wp_mkdir_p($this->upload_basedir)) {
            Log::get()->addWarning(
                sprintf(
                    __('Podlove Image Cache: Unable to create directory %s. Is its parent directory writable by the server?'),
                    $this->upload_basedir
                )
            );
        }
    }

    public function move_as_original_file($file)
    {
        $move_new_file = @rename($file, $this->original_file());

        if (false === $move_new_file) {
            Log::get()->addWarning(
                sprintf(
                    __('Podlove Image Cache: The downloaded image could not be moved to %s.'),
                    $this->original_file()
                )
            );
        }
    }

    public function copy_as_original_file($file)
    {
        $move_new_file = @copy($file, $this->original_file());

        if (false === $move_new_file) {
            Log::get()->addWarning(
                sprintf(
                    __('Podlove Image Cache: The downloaded image could not be moved to %s.'),
                    $this->original_file()
                )
            );
        }
    }

    private function add_donotbackup_dotfile()
    {
        file_put_contents(
            trailingslashit(self::cache_dir()) . '.donotbackup',
            "Backup plugins are encouraged to not backup folders and subfolders when this file is inside.\n"
        );
    }

    /**
     * Save data relevant for cache invalidation to file.
     *
     * @param  array $response
     */
    private function save_cache_data($response = [])
    {

        $cache_info = [
            'source'   => $this->source_url,
            'filename' => $this->file_name,
        ];

        if (!empty($response)) {
            $cache_info['etag']          = wp_remote_retrieve_header($response, 'etag');
            $cache_info['last-modified'] = wp_remote_retrieve_header($response, 'last-modified');
            $cache_info['expires']       = wp_remote_retrieve_header($response, 'expires');
        }

        file_put_contents($this->cache_file(), Yaml::dump($cache_info));
    }

    /**
     * Downloads a url to a local temporary file using the WordPress HTTP Class.
     * Please note, That the calling function must unlink() the file.
     *
     * This is a modified copy of WP Core download_url().
     * I copied it because I need to look into the header of the response but
     * unfortunately the original implementation does not expose it.
     *
     * @param string $url the URL of the file to download
     * @param int $timeout The timeout for the request to download the file default 300 seconds
     * @return mixed WP_Error on failure, array with Filename & http response on success.
     */
    private function download_url($url, $timeout = 300)
    {
        //WARNING: The file is not automatically deleted, The script must unlink() the file.
        if (!$url) {
            return new \WP_Error('http_no_url', __('Invalid URL Provided.'));
        }

        $tmpfname = wp_tempnam($url);
        if (!$tmpfname) {
            return new \WP_Error('http_no_file', __('Could not create Temporary file.'));
        }

        $args = [
            'timeout'   => $timeout,
            'stream'    => true,
            'filename'  => $tmpfname,
            'sslverify' => \Podlove\get_setting('website', 'ssl_verify_peer') == 'on',
        ];
        $response = wp_safe_remote_get($url, $args);

        if (is_wp_error($response)) {
            unlink($tmpfname);
            return $response;
        }

        if (200 != wp_remote_retrieve_response_code($response)) {
            unlink($tmpfname);
            return new \WP_Error('http_404', trim(wp_remote_retrieve_response_message($response)));
        }

        return [$tmpfname, $response];
    }

    private function extract_file_extension()
    {
        $url = parse_url($this->source_url);
        return pathinfo($url['path'], PATHINFO_EXTENSION);
    }
}
