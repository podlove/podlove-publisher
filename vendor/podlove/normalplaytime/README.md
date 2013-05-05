# Normal Play Time Parser

[![Build Status](https://travis-ci.org/podlove/normalplaytime.png?branch=master)](https://travis-ci.org/podlove/normalplaytime)

PHP parser for Normal Play Time (RFC 2326)

- http://www.ietf.org/rfc/rfc2326.txt
- http://www.w3.org/TR/media-frags/#npttimedef

## Usage

```php
<?php
use \Podlove\NormalPlayTime\Parser;

// get seconds or milliseconds
Parser::parse("1.834");      // 1834
Parser::parse("1.834", "s"); // 1

// invalid returns NULL
Parser::parse("abc"); // NULL

// valid example NPT strings
Parser::parse("1");        // 1000
Parser::parse("12:34");    // 754000
Parser::parse("12:34.56"); // 754560
Parser::parse("1:2");      // 62000
Parser::parse("1:2:3.4");  // 3723400
```