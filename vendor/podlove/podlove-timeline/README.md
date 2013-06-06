# Podlove Timeline

[![Build Status](https://travis-ci.org/podlove/podlove-timeline.png?branch=master)](https://travis-ci.org/podlove/podlove-timeline)

PHP library providing a toolkit to handle various timeline/chapter formats.

Supported formats:

- mp4chaps
- psc
- JSON
- WebVTT (soon)

## Usage

### Create Chapters Programmatically

```php
use \Podlove\Chapters\Chapters;
use \Podlove\Chapters\Chapter;

$chapters = new Chapters();
$chapters->addChapter( new Chapter( 1234, 'Intro' ) );
$chapters->addChapter( new Chapter( 5234, 'Second Chapter' ) );
echo $chapters[0]->get_title(); // => "Intro"
```

### Printer

#### Printer — PSC (Podlove Simple Chapters)

```php
use \Podlove\Chapters\Chapters;
use \Podlove\Chapters\Chapter;
use \Podlove\Chapters\Printer;

$chapters = new Chapters();
$chapters->addChapter( new Chapter( 1234, 'Intro', 'http://example.com' ) );
$chapters->addChapter( new Chapter( 1235, 'Second Chapter' ) );
$chapters->setPrinter( new Printer\PSC() );
echo (string) $chapters;
/* =>
<psc:chapters xmlns:psc="http://podlove.org/simple-chapters" version="1.2">
  <psc:chapter start="00:00:01.234" title="Intro" href="http://example.com"/>
  <psc:chapter start="00:00:01.235" title="Second Chapter"/>
</psc:chapters>
*/
```

#### Printer — mp4chaps

```php
use \Podlove\Chapters\Chapters;
use \Podlove\Chapters\Chapter;
use \Podlove\Chapters\Printer;

$chapters = new Chapters();
$chapters->addChapter( new Chapter( 1234, 'Intro', 'http://example.com' ) );
$chapters->addChapter( new Chapter( 754000, 'About us' ) );
$chapters->addChapter( new Chapter( 3723000, 'Later' ) );
$chapters->setPrinter( new Printer\Mp4chaps() );
echo (string) $chapters;
/* =>
00:00:01.234 Intro <http://example.com>
00:12:34.000 About us
01:02:03.000 Later
*/
```

#### Printer — JSON

```php
use \Podlove\Chapters\Chapters;
use \Podlove\Chapters\Chapter;
use \Podlove\Chapters\Printer;

$chapters = new Chapters();
$chapters->addChapter( new Chapter( 1234, 'Intro', 'http://example.com' ) );
$chapters->addChapter( new Chapter( 754000, 'About us' ) );
$chapters->addChapter( new Chapter( 3723000, 'Later', '', 'http://example.com/foo.jpg' ) );
$chapters->setPrinter( new Printer\JSON() );
/* =>
[
	{ "start": "00:00:01.234", "title": "Intro", "href": "http://example.com", "image": "" },
	{ "start": "00:12:34.000", "title": "About us", "href": "", "image": "" },
	{ "start": "01:02:03.000", "title": "Later", "href": "", "image": "http://example.com/foo.jpg" }
]
*/
```

### Parser

Parse chapters in various formats.

#### Parser — PSC (Podlove Simple Chapters)

```php 
use \Podlove\Chapters\Parser;

$psc_string = '<psc:chapters xmlns:psc="http://podlove.org/simple-chapters" version="1.2">
  <psc:chapter start="00:00:01.234" title="Intro" href="http://example.com"/>
  <psc:chapter start="00:12:34.000" title="The End"/>
</psc:chapters>';

$chapters = Parser\PSC::parse( $psc_string );
echo $chapters[0]->get_title(); // => "Intro"
```

#### Parser — mp4chaps

```php
use \Podlove\Chapters\Parser;

$mp4chaps_string = "3.45 Intro\n3.46 The End";
$chapters = Parser\Mp4chaps::parse( $mp4chaps_string );
echo $chapters[0]->get_title(); // => "Intro"
```

#### Parser — JSON

```php
use \Podlove\Chapters\Parser;

$json_string = "[
	{ "start": "00:00:01.234", "title": "Intro", "href": "http://example.com" },
]";
$chapters = Parser\Mp4chaps::parse( $json_string );
echo $chapters[0]->get_title(); // => "Intro"
```
