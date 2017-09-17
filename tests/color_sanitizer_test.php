<?php 
use \Podlove\Modules\PodloveWebPlayer\PlayerV4\Html5Printer;

class ColorSanitizerTest extends PHPUnit\Framework\TestCase {

	public function testTruth() {
		$this->assertEquals(true, true);
	}

	public function testFixesHex6DoubleHashes()
	{
		$this->assertEquals(Html5Printer::sanitize_color('##ffffff'), '#ffffff');
	}

	public function testFixesHex6MissingHashe()
	{
		$this->assertEquals(Html5Printer::sanitize_color('ffffff'), '#ffffff');
	}

	public function testFixesHex3DoubleHashes()
	{
		$this->assertEquals(Html5Printer::sanitize_color('##fff'), '#fff');
	}

	public function testFixesHex3MissingHashe()
	{
		$this->assertEquals(Html5Printer::sanitize_color('fff'), '#fff');
	}

	public function testAcceptRgb()
	{
		$this->assertEquals(Html5Printer::sanitize_color('rgb(1,2,3)'), 'rgb(1,2,3)');
	}

	public function testAcceptRgba()
	{
		$this->assertEquals(Html5Printer::sanitize_color('rgba(1,2,3,1)'), 'rgba(1,2,3,1)');
	}

	public function testDefault()
	{
		$this->assertEquals(Html5Printer::sanitize_color('nonsense'), '#000');
	}

}
