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
    private $external;

    /**
     * Constructor.
     *
     * @param string $title       Banner title
     * @param string $content     Banner content HTML
     * @param string $button_text Button text
     * @param string $button_url  Button URL
     * @param string $logo_text   Logo text
     * @param bool   $external    Whether the link should open in a new tab
     */
    public function __construct($title, $content, $button_text, $button_url, $logo_text = 'A Publisher PLUS Feature', $external = false)
    {
        $this->title = $title;
        $this->content = $content;
        $this->button_text = $button_text;
        $this->button_url = $button_url;
        $this->logo_text = $logo_text;
        $this->external = $external;
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
            'external' => $this->external,
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

    public static function plus_main()
    {
        $content = '<p><strong>Tired of fiddling with FTP or overloading your WordPress host when you release an episode?</strong><br>
 With <strong>Publisher PLUS</strong>, your podcast files are stored in fast, secure cloud storage—no setup required.</p>

<ul class="banner-feature-list">
  <li>Simple uploads</li>
  <li>Reliable delivery</li>
  <li>Optimized for podcasting</li>
</ul>

 <p><strong>Start your PLUS upgrade today.</strong></p>';

        $banner = new self(
            __('Introducing Publisher PLUS: File Hosting Built for Podcasters', 'podlove-podcasting-plugin-for-wordpress'),
            $content,
            __('Get Publisher PLUS &#10140;', 'podlove-podcasting-plugin-for-wordpress'),
            'https://plus.podlove.org/pricing',
            'A Publisher PLUS Feature',
            true
        );

        $banner->render();
    }

    /**
     * Create and render a banner for authenticated PLUS users.
     */
    public static function plus_authenticated()
    {
        $content = 'Manage your account  and access advanced features from your dashboard.</p>';

        $banner = new self(
            __('Manage Your Publisher PLUS Account', 'podlove-podcasting-plugin-for-wordpress'),
            $content,
            __('Go to PLUS Dashboard &#10140;', 'podlove-podcasting-plugin-for-wordpress'),
            'https://plus.podlove.org/dashboard',
            'Publisher PLUS',
            true
        );

        $banner->render();
    }
}
