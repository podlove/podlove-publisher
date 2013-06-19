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
        $this->setBrowserUrl('http://localhost/Sites/wordpress-podlove-test/');
    }

    private function save_and_open_screenshot() {
    	file_put_contents('/tmp/screen.jpg', $this->currentScreenshot());
    	system('open /tmp/screen.jpg');
    }
 
    public function testTitle() {
        $this->url('/');
        $this->assertEquals('Hello world!', $this->byCssSelector('h1.entry-title a')->text());
    }
 
}