<?php

namespace Podlove\Admin;

/**
 * Describes one logical admin asset bundle, including activation rules and setup hooks.
 */
class AssetBundle
{
    private $matcher;
    private array $scripts = [];
    private array $styles = [];
    private array $translations = [];
    private $dataProvider;
    private $localizer;

    private function __construct(callable $matcher)
    {
        $this->matcher = $matcher;
    }

    public static function forScreen(callable $matcher): self
    {
        return new self($matcher);
    }

    public function withScripts(array $scripts): self
    {
        $this->scripts = $scripts;

        return $this;
    }

    public function withStyles(array $styles): self
    {
        $this->styles = $styles;

        return $this;
    }

    public function withTranslations(array $translations): self
    {
        $this->translations = $translations;

        return $this;
    }

    public function withPodloveData(callable $dataProvider): self
    {
        $this->dataProvider = $dataProvider;

        return $this;
    }

    public function withLocalizer(callable $localizer): self
    {
        $this->localizer = $localizer;

        return $this;
    }

    public function matches(AssetContext $context): bool
    {
        return ($this->matcher)($context);
    }

    public function register(PublisherAssetManager $manager, AssetContext $context): void
    {
        foreach ($this->scripts as $script) {
            $manager->registerScript($script, $context);
        }

        foreach ($this->styles as $style) {
            $manager->registerStyle($style, $context);
        }

        foreach ($this->translations as $handle) {
            wp_set_script_translations($handle, 'podlove-podcasting-plugin-for-wordpress');
        }

        if ($this->dataProvider) {
            $manager->addPodloveDataFragment(($this->dataProvider)($context));
        }

        if ($this->localizer) {
            ($this->localizer)($context);
        }
    }

    public function enqueue(): void
    {
        foreach ($this->styles as $style) {
            wp_enqueue_style($style['handle']);
        }

        foreach ($this->scripts as $script) {
            wp_enqueue_script($script['handle']);
        }
    }
}
