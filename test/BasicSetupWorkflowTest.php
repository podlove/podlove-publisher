<?php
require_once 'PHPUnit/Extensions/SeleniumTestCase.php';

class BasicSetupWorkflowTest extends PHPUnit_Extensions_Selenium2TestCase {

	// before first test
	public static function setUpBeforeClass() {
		exec( 'gunzip -c ' . TEST_DATABASE_FILE . ' > test/fixtures/dump.sql' );
	}
	
	// after last test
	public static function tearDownAfterClass() {
		exec( 'rm test/fixtures/dump.sql' );
	}

	// before each test
    protected function setUp() {

    	// clean database
    	putenv( 'MYSQL_PWD=' . TEST_DATABASE_PASSWORD );
    	$command = sprintf(
    		"mysql -u %s %s < test/fixtures/dump.sql",
    		TEST_DATABASE_USERNAME,
    		TEST_DATABASE_DATABASE
    	);
    	exec( $command );

    	// setup selenium
        $this->setBrowser('firefox');
        $this->setBrowserUrl(TEST_WORDPRESS_ROOT);
    }

    private function save_and_open_screenshot() {
    	file_put_contents('/tmp/screen.jpg', $this->currentScreenshot());
    	system('open /tmp/screen.jpg');
    }
 
    public function testSetupWorkflow() {

    	// wait up to 5 seconds per action
    	$this->timeouts()->implicitWait(5000);

    	// login
        $this->url('/wp-login.php');
        $this->byId('user_login')->value('admin');
        $this->byId('user_pass')->value('admin');
        $this->byId('loginform')->submit();
        $this->assertEquals('Howdy, admin', $this->byCssSelector('#wp-admin-bar-my-account a')->text());

        // podcast settings
        $this->url('/wp-admin/admin.php?page=podlove_settings_podcast_handle');
        $this->byId('podlove_podcast_title')->value('Test Podcast');
        $this->byId('podlove_podcast_subtitle')->value('the one and only');
        $this->byId('podlove_podcast_media_file_base_uri')->value('http://satoripress.com/wp-content/ppp/');
        $this->byId('submit')->click();

        // create assets
        $this->byLinkText('Episode Assets')->click();
        $this->byCssSelector('.wrap > h2 > a')->click();
        $this->select( $this->byId('podlove_episode_asset_type') )->selectOptionByLabel('audio');
        $this->select( $this->byId('podlove_episode_asset_file_type_id') )->selectOptionByLabel('MP3 Audio (mp3)');
        $this->byId('submit')->click();
        $this->assertEquals('MP3 Audio', $this->byCssSelector('table.episode_assets .title a')->text());

        // create feeds
        $this->byLinkText('Podcast Feeds')->click();
        $this->byCssSelector('.wrap > h2 > a')->click();
        $this->select( $this->byId('podlove_feed_episode_asset_id') )->selectOptionByLabel('MP3 Audio');
        $this->byId('podlove_feed_name')->value('MP3 Audio Feed');
        $this->byId('podlove_feed_slug')->value('mp3');
        $this->byId('submit')->click();
        $this->assertEquals('MP3 Audio Feed', $this->byCssSelector('table.feeds .name a')->text());

        // create episode
        $this->url('/wp-admin/post-new.php?post_type=podcast');
        $this->byId('title')->value('PPP001 The Title');
        $this->byId('_podlove_meta_slug')->value('ppp001');
        $this->byId('publish')->click();
        $this->assertEquals(1, count($this->elements($this->using('css selector')->value('.podlove-icon-ok'))));

        // view episode
        $this->url('/?podcast=ppp001-the-title');
        $this->assertEquals('PPP001 The Title', $this->byCssSelector('article .entry-title')->text());

        // view feed
        $this->url('/?feed=mp3');
        $this->assertContains('ppp001.mp3', $this->get_body_source());

        // $this->save_and_open_screenshot();
    }

    private function get_body_source( $value='' ) {
    	$elementArray = $this->execute(array(
            'script' => 'return document.body;',
            'args' => array(),
        ));
    	return $this->elementFromResponseValue($elementArray)->text();
    }
 
}