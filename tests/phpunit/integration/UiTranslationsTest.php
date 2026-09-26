<?php

/**
 * @internal
 *
 * @coversNothing
 */
class UiTranslationsTest extends WP_UnitTestCase
{
    public function testModuleMetadataIsTranslatedWhenRead(): void
    {
        $module = \Podlove\Modules\Categories\Categories::instance();
        $translate = static function ($translation, $text, $domain) {
            return $domain === 'podlove-podcasting-plugin-for-wordpress' ? 'Translated: '.$text : $translation;
        };

        add_filter('gettext', $translate, 10, 3);

        try {
            $this->assertSame('Translated: Categories', $module->get_module_name());
            $this->assertSame('Translated: Enable categories for episodes.', $module->get_module_description());
            $this->assertSame('Shownotes', \Podlove\Modules\Shownotes\Shownotes::instance()->get_module_name());
        } finally {
            remove_filter('gettext', $translate, 10);
        }
    }

    public function testLicenseDisplayTranslationPreservesStoredAndCustomNames(): void
    {
        $translate = static function ($translation, $text, $domain) {
            if ($domain === 'podlove-podcasting-plugin-for-wordpress' && $text === 'Public Domain License') {
                return 'Translated public domain';
            }

            return $translation;
        };
        add_filter('gettext', $translate, 10, 3);

        try {
            $attributes = [
                'license_url' => 'https://creativecommons.org/publicdomain/zero/1.0/',
                'license_name' => 'Public Domain License',
            ];
            $license = new \Podlove\Model\License('episode', $attributes);
            $this->assertSame('Translated public domain', $license->getName());
            $this->assertSame('Public Domain License', $license->name);
            $this->assertSame('Public Domain License', \Podlove\Model\License::get_name_from_license(['version' => 'cc0']));

            $attributes['license_name'] = 'My custom license';
            $custom_license = new \Podlove\Model\License('episode', $attributes);
            $this->assertSame('My custom license', $custom_license->getName());
        } finally {
            remove_filter('gettext', $translate, 10);
        }
    }
}
