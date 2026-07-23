<?php

use Podlove\Modules\AssemblyAI\VttConverter;

/**
 * @internal
 * @coversNothing
 */
class VttConverterTest extends PHPUnit\Framework\TestCase
{
    public function testEmptyResponseReturnsHeader()
    {
        $result = VttConverter::convert([]);
        $this->assertEquals("WEBVTT\n\n", $result);
    }

    public function testEmptyWordsReturnsHeader()
    {
        $result = VttConverter::convert(['words' => []]);
        $this->assertEquals("WEBVTT\n\n", $result);
    }

    public function testSingleWord()
    {
        $response = [
            'words' => [
                ['text' => 'Hello', 'start' => 1000, 'end' => 1500],
            ],
        ];

        $result = VttConverter::convert($response);

        $expected = "WEBVTT\n\n"
            ."1\n"
            ."00:00:01.000 --> 00:00:01.500\n"
            ."Hello\n\n";

        $this->assertEquals($expected, $result);
    }

    public function testMultipleWordsInOneSegment()
    {
        $response = [
            'words' => [
                ['text' => 'Hello', 'start' => 1000, 'end' => 1500],
                ['text' => 'world', 'start' => 1600, 'end' => 2000],
            ],
        ];

        $result = VttConverter::convert($response);

        $expected = "WEBVTT\n\n"
            ."1\n"
            ."00:00:01.000 --> 00:00:02.000\n"
            ."Hello world\n\n";

        $this->assertEquals($expected, $result);
    }

    public function testTimestampFormatting()
    {
        $response = [
            'words' => [
                ['text' => 'Test', 'start' => 3661123, 'end' => 3661456],
            ],
        ];

        $result = VttConverter::convert($response);

        $this->assertStringContainsString('01:01:01.123 --> 01:01:01.456', $result);
    }

    public function testSpeakerLabels()
    {
        $response = [
            'words' => [
                ['text' => 'Hello', 'start' => 1000, 'end' => 1500],
            ],
            'utterances' => [
                ['speaker' => 'A', 'start' => 0, 'end' => 2000, 'text' => 'Hello'],
            ],
        ];

        $result = VttConverter::convert($response);

        $this->assertStringContainsString('<v Speaker A>Hello', $result);
    }

    public function testSpeakerChangeBreaksSegment()
    {
        $response = [
            'words' => [
                ['text' => 'Hello', 'start' => 1000, 'end' => 1500],
                ['text' => 'Hi', 'start' => 2000, 'end' => 2500],
            ],
            'utterances' => [
                ['speaker' => 'A', 'start' => 0, 'end' => 1500, 'text' => 'Hello'],
                ['speaker' => 'B', 'start' => 2000, 'end' => 2500, 'text' => 'Hi'],
            ],
        ];

        $result = VttConverter::convert($response);

        $this->assertStringContainsString('<v Speaker A>Hello', $result);
        $this->assertStringContainsString('<v Speaker B>Hi', $result);
        // Two separate cues
        $this->assertStringContainsString("1\n", $result);
        $this->assertStringContainsString("2\n", $result);
    }

    public function testSegmentBreaksOnDuration()
    {
        // Two words more than 5 seconds apart from segment start
        $response = [
            'words' => [
                ['text' => 'First', 'start' => 0, 'end' => 500],
                ['text' => 'Second', 'start' => 6000, 'end' => 6500],
            ],
        ];

        $result = VttConverter::convert($response);

        // Should produce two cues
        $this->assertStringContainsString("1\n", $result);
        $this->assertStringContainsString("2\n", $result);
        $this->assertStringContainsString("First\n", $result);
        $this->assertStringContainsString("Second\n", $result);
    }

    public function testSegmentBreaksOnTextLength()
    {
        // MAX_CHARS_PER_LINE * MAX_LINES_PER_SEGMENT = 42 * 2 = 84
        $longWord = str_repeat('x', 80);
        $response = [
            'words' => [
                ['text' => $longWord, 'start' => 0, 'end' => 500],
                ['text' => 'overflow', 'start' => 600, 'end' => 1000],
            ],
        ];

        $result = VttConverter::convert($response);

        // Should produce two cues because combined length > 84
        $this->assertStringContainsString("2\n", $result);
    }

    public function testNoUtterancesProducesNoSpeakerTags()
    {
        $response = [
            'words' => [
                ['text' => 'Hello', 'start' => 1000, 'end' => 1500],
                ['text' => 'world', 'start' => 1600, 'end' => 2000],
            ],
        ];

        $result = VttConverter::convert($response);

        $this->assertStringNotContainsString('<v ', $result);
    }

    public function testMultipleSpeakersWithMultipleUtterances()
    {
        $response = [
            'words' => [
                ['text' => 'Welcome', 'start' => 1000, 'end' => 1500],
                ['text' => 'everyone.', 'start' => 1600, 'end' => 2200],
                ['text' => 'Thanks', 'start' => 3000, 'end' => 3400],
                ['text' => 'for', 'start' => 3500, 'end' => 3700],
                ['text' => 'having', 'start' => 3800, 'end' => 4200],
                ['text' => 'me.', 'start' => 4300, 'end' => 4500],
            ],
            'utterances' => [
                ['speaker' => 'A', 'start' => 1000, 'end' => 2200, 'text' => 'Welcome everyone.'],
                ['speaker' => 'B', 'start' => 3000, 'end' => 4500, 'text' => 'Thanks for having me.'],
            ],
        ];

        $result = VttConverter::convert($response);

        $this->assertStringContainsString('<v Speaker A>Welcome everyone.', $result);
        $this->assertStringContainsString('<v Speaker B>Thanks for having me.', $result);
    }

    public function testWordsOutsideUtterancesHaveNoSpeaker()
    {
        // Word at time 5000 doesn't fall within any utterance
        $response = [
            'words' => [
                ['text' => 'Hello', 'start' => 1000, 'end' => 1500],
                ['text' => 'orphan', 'start' => 5000, 'end' => 5500],
            ],
            'utterances' => [
                ['speaker' => 'A', 'start' => 0, 'end' => 2000, 'text' => 'Hello'],
            ],
        ];

        $result = VttConverter::convert($response);

        // First word gets speaker A
        $this->assertStringContainsString('<v Speaker A>', $result);
        // The orphan word should still appear
        $this->assertStringContainsString('orphan', $result);
    }

    public function testRealisticTranscript()
    {
        $response = [
            'words' => [
                ['text' => 'Hello', 'start' => 1500, 'end' => 1900],
                ['text' => 'everyone,', 'start' => 2000, 'end' => 2600],
                ['text' => 'welcome', 'start' => 2700, 'end' => 3100],
                ['text' => 'to', 'start' => 3200, 'end' => 3300],
                ['text' => 'the', 'start' => 3400, 'end' => 3500],
                ['text' => 'show.', 'start' => 3600, 'end' => 4000],
                ['text' => 'Thanks', 'start' => 5000, 'end' => 5300],
                ['text' => 'for', 'start' => 5400, 'end' => 5500],
                ['text' => 'having', 'start' => 5600, 'end' => 5900],
                ['text' => 'me.', 'start' => 6000, 'end' => 6200],
            ],
            'utterances' => [
                ['speaker' => 'A', 'start' => 1500, 'end' => 4000, 'text' => 'Hello everyone, welcome to the show.'],
                ['speaker' => 'B', 'start' => 5000, 'end' => 6200, 'text' => 'Thanks for having me.'],
            ],
        ];

        $result = VttConverter::convert($response);

        // Starts with WEBVTT header
        $this->assertStringStartsWith("WEBVTT\n\n", $result);

        // Contains both speakers
        $this->assertStringContainsString('Speaker A', $result);
        $this->assertStringContainsString('Speaker B', $result);

        // Contains correct timestamps
        $this->assertStringContainsString('00:00:01.500', $result);
        $this->assertStringContainsString('00:00:06.200', $result);

        // Contains all text
        $this->assertStringContainsString('Hello everyone, welcome to the show.', $result);
        $this->assertStringContainsString('Thanks for having me.', $result);
    }
}
