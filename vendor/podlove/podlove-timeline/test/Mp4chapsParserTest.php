<?php
use \Podlove\Chapters\Parser\Mp4chaps;
use \Podlove\Chapters\Chapters;
use \Podlove\Chapters\Chapter;

class Mp4chapsParserTest extends PHPUnit_Framework_TestCase {

    public function testEmptyStringReturnsNull() {
        $result = Mp4chaps::parse("");
        $this->assertNull( $result );
    }

    public function testValidSingleLine() {
        $result = Mp4chaps::parse("3.45 Intro");

        $chapters = new Chapters();
        $chapters->addChapter( new Chapter( 3450, 'Intro' ) );

        $this->assertEquals( $chapters, $result );
    }

    public function testValidMultiLines() {
        $result = Mp4chaps::parse("3.45 Intro\n3.46 About us");

        $chapters = new Chapters();
        $chapters->addChapter( new Chapter( 3450, 'Intro' ) );
        $chapters->addChapter( new Chapter( 3460, 'About us' ) );

        $this->assertEquals( $chapters, $result );
    }

    public function testWhitespaceRemoval() {
         $result = Mp4chaps::parse(" 3.45 Intro    \r\n \t 3.46   About us ");

         $chapters = new Chapters();
         $chapters->addChapter( new Chapter( 3450, 'Intro' ) );
         $chapters->addChapter( new Chapter( 3460, 'About us' ) );

         $this->assertEquals( $chapters, $result );
    }

    public function testIgnoreEmptyLines() {
        $result = Mp4chaps::parse("\n
            \n
            \n3.45 Intro\n3.46 About us
            \n\n");

        $chapters = new Chapters();
        $chapters->addChapter( new Chapter( 3450, 'Intro' ) );
        $chapters->addChapter( new Chapter( 3460, 'About us' ) );

        $this->assertEquals( $chapters, $result );
    }

    public function testMultipleTimestampFormats() {
        $result = Mp4chaps::parse("1.234 Intro\n12:34 About us\n1:2:3 Later\n1..2 Invalid");

        $chapters = new Chapters();
        $chapters->addChapter( new Chapter( 1234, 'Intro' ) );
        $chapters->addChapter( new Chapter( 754000, 'About us' ) );
        $chapters->addChapter( new Chapter( 3723000, 'Later' ) );

        $this->assertEquals( $chapters, $result );
    }

    public function testAllowLink() {
        $result = Mp4chaps::parse("3.45 Intro   <http://example.com>");

        $chapters = new Chapters();
        $chapters->addChapter( new Chapter( 3450, 'Intro', 'http://example.com' ) );

        $this->assertEquals( $chapters, $result );
    }

    public function testRejectInvalidFilesWithSomeMatchingLines() {
        $result = Mp4chaps::parse("\n
            \n
            \n3.45 Intro
            A line without timestamp
            <p>yet another invalid line</p>
            \n\n");
        $this->assertNull( $result );
    }

    public function testChapterWithZeroTime() {
        $result = Mp4chaps::parse("00:00:00.000 Intro
00:00:19.000 Wochenrückblick");

        $this->assertEquals( 2, count( $result->toArray() ) );
    }

}
