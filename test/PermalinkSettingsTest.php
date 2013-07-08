<?php
class PermalinkSettingsTest extends PHPUnit_Extensions_Selenium2TestCase {

	// before first test
	public static function setUpBeforeClass() {

		exec( 'gunzip -c test/fixtures/2-episodes.sql.gz > test/fixtures/dump.sql' );

        // clean database
        putenv( 'MYSQL_PWD=' . TEST_DATABASE_PASSWORD );
        $command = sprintf(
            "mysql -u %s %s < test/fixtures/dump.sql",
            TEST_DATABASE_USERNAME,
            TEST_DATABASE_DATABASE
        );
        exec( $command );

	}
	
	// after last test
	public static function tearDownAfterClass() {
		exec( 'rm test/fixtures/dump.sql' );
	}

	// before each test
    protected function setUp() {
        // setup selenium
        $this->setBrowser('firefox');
        $this->setBrowserUrl(TEST_WORDPRESS_ROOT);
    }

    private function login() {
        $this->timeouts()->implicitWait(5000);
        // login
        $this->url('/wp-login.php');
        usleep(500); // avoiding submit-issues
        $this->byId('user_login')->value('admin');
        $this->byId('user_pass')->value('admin');
        usleep(500); // avoiding submit-issues
        $this->byId('loginform')->submit();
        $this->assertEquals('Howdy, admin', $this->byCssSelector('#wp-admin-bar-my-account a')->text());
    }

    private function assertPageContains( $text ) {

        // use this JS hack so we can inspect feeds in Firefox
        $elementArray = $this->execute(array(
            'script' => 'return document.body;',
            'args' => array(),
        ));

        $this->assertContains( $text, $this->elementFromResponseValue($elementArray)->text() );
    }

    private function visitUrlAndAssertPageContainTex( $url, $text ) {
        $this->url( $url );
        $this->assertPageContains( $text );
    }

    public function testNonprettyPermalinks() {

        $this->login();

        $this->url('/wp-admin/options-permalink.php');
        $this->byXPath('//input[@type="radio"][1]')->click();
        $this->byId('submit')->click();

        $this->visitUrlAndAssertPageContainTex('/?p=1', 'Hello world!');
        $this->visitUrlAndAssertPageContainTex('/?page_id=2', 'Sample Page');
        $this->visitUrlAndAssertPageContainTex('?podcast=ppp001-the-title', 'PPP001 The Title');
        $this->visitUrlAndAssertPageContainTex('/?feed=mp3', 'ppp001.mp3' );
    }

    public function testPostnamePrettyLinks() {

        $this->login();

        $this->url('/wp-admin/options-permalink.php');
        $this->byXPath('//input[@value="/%postname%/"]')->click();
        $this->byId('submit')->click();

        $this->visitUrlAndAssertPageContainTex('/hello-world/', 'Hello world!');
        $this->visitUrlAndAssertPageContainTex('/sample-page/', 'Sample Page');
        $this->visitUrlAndAssertPageContainTex('/ppp001-the-title/', 'PPP001 The Title');
        $this->visitUrlAndAssertPageContainTex('/feed/mp3', 'ppp001.mp3' );
    }

    public function testPostnamePrettyAndEpisodeCustomLinks() {

        $this->login();

        $this->url('/wp-admin/options-permalink.php');
        $this->byXPath('//input[@value="/%postname%/"]')->click();
        $this->byId('submit')->click();

        $this->url('/wp-admin/admin.php?page=podlove_settings_settings_handle');
        $checkbox = $this->byId('use_post_permastruct');
        if ( $checkbox->selected() ) {
            $this->byId('use_post_permastruct')->click();
        }
        $this->byId('custom_episode_slug')->value('/episode/%podcast%/');
        $this->byId('submit')->click();

        $this->visitUrlAndAssertPageContainTex('/hello-world/', 'Hello world!');
        $this->visitUrlAndAssertPageContainTex('/sample-page/', 'Sample Page');
        $this->visitUrlAndAssertPageContainTex('/episode/ppp001-the-title/', 'PPP001 The Title');
        $this->visitUrlAndAssertPageContainTex('/feed/mp3', 'ppp001.mp3' );
    }

}