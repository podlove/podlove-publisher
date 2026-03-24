<?php

use Podlove\Model\Podcast;
use Podlove\Modules\Base;
use Podlove\Modules\Plus\FeedProxy;

class PlusFeedProxyTest extends WP_UnitTestCase
{
    private $was_plus_active;

    public function setUp(): void
    {
        parent::setUp();
        $this->was_plus_active = Base::is_active('plus');
        podlove_test_activate_module('plus', \Podlove\Modules\Plus\Plus::class);
    }

    public function tearDown(): void
    {
        $podcast = Podcast::get();
        $podcast->plus_enable_proxy = false;
        $podcast->save();

        if ($this->was_plus_active) {
            Base::activate('plus');
        } else {
            Base::deactivate('plus');
        }

        parent::tearDown();
    }

    public function test_is_enabled_requires_plus_module()
    {
        $podcast = Podcast::get();
        $podcast->plus_enable_proxy = true;
        $podcast->save();

        Base::deactivate('plus');
        $this->assertFalse(FeedProxy::is_enabled());

        Base::activate('plus');
        $this->assertTrue(FeedProxy::is_enabled());
    }
}
