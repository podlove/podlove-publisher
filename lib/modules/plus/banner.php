<?php

namespace Podlove\Modules\Plus;

/**
 * Banner.
 *
 * Modular banner component that can be reused to display different banners
 * with the same visual style but varying content.
 */
class Banner
{
    private $title;
    private $content;
    private $button_text;
    private $button_url;
    private $logo_text;

    /**
     * Constructor.
     *
     * @param string $title       Banner title
     * @param string $content     Banner content HTML
     * @param string $button_text Button text
     * @param string $button_url  Button URL
     * @param string $logo_text   Logo text
     */
    public function __construct($title, $content, $button_text, $button_url, $logo_text = 'A Publisher PLUS Feature')
    {
        $this->title = $title;
        $this->content = $content;
        $this->button_text = $button_text;
        $this->button_url = $button_url;
        $this->logo_text = $logo_text;
    }

    /**
     * Render the banner.
     */
    public function render()
    {
        extract([
            'title' => $this->title,
            'content' => $this->content,
            'button_text' => $this->button_text,
            'button_url' => $this->button_url,
            'logo_text' => $this->logo_text,
        ]);

        include __DIR__.'/banner.html.php';
    }

    /**
     * Create and render a feed proxy banner.
     */
    public static function feed_proxy()
    {
        $content = '<p>'
        .__('High-traffic RSS feeds can slow down your podcast hosting. Our <strong>Feed Proxy</strong> service offloads this traffic to our optimized servers, ensuring lightning-fast delivery even during traffic spikes. Stop worrying about server loads and focus on creating great content.', 'podlove-podcasting-plugin-for-wordpress')
        .'</p>';

        $banner = new self(
            __('Optimize Your Podcast\'s Performance', 'podlove-podcasting-plugin-for-wordpress'),
            $content,
            __('Enable Feed Proxy', 'podlove-podcasting-plugin-for-wordpress'),
            admin_url('admin.php?page=publisher_plus_settings')
        );

        $banner->render();
    }

    /**
     * Create and render a file storage banner.
     */
    public static function file_storage()
    {
        $content = '<p>'
        .__('Store your podcast files in fast and reliable cloud storage. Don\'t worry about dealing with WordPress performance issues as your podcast grows. Focus on creating great content and let us handle the rest.', 'podlove-podcasting-plugin-for-wordpress')
        .'</p>';

        $banner = new self(
            __('Reliable Podcast File Hosting', 'podlove-podcasting-plugin-for-wordpress'),
            $content,
            __('Enable File Storage', 'podlove-podcasting-plugin-for-wordpress'),
            admin_url('admin.php?page=publisher_plus_settings')
        );

        $banner->render();
    }
}
