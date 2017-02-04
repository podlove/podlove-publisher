<?php
namespace Podlove\PHP;

/**
 * strpos wrapper that prefers mb_strpos but falls back to strpos.
 */
function strpos($haystack, $needle, $offset = 0, $encoding = 'UTF-8') {
  if (function_exists('mb_strpos'))
    return mb_strpos($haystack, $needle, $offset, $encoding);
  else
    return strpos($haystack, $needle, $offset);
}

/**
 * strlen wrapper that prefers mb_strlen but falls back to strlen.
 */
function strlen($str, $encoding = 'UTF-8') {
  if (function_exists('mb_strlen'))
    return mb_strlen($str, $encoding);
  else
    return strlen($str);
}

/**
 * substr wrapper that prefers mb_substr but falls back to substr.
 */
function substr($str, $start, $length = NULL, $encoding = 'UTF-8') {
  if (function_exists('mb_substr'))
    return mb_substr($str, $start, $length, $encoding);
  else
    return substr($str, $start, $length);
}

/**
 * Check string ends with a certain character or substring.
 *
 * @param  string $haystack String to search
 * @param  string $needle   Substring or character
 * @return bool
 */
function ends_with($haystack, $needle) {
	return $needle === substr($haystack, -strlen($needle));
}

/**
 * Escape WordPress Shortcodes
 *
 * Replaces the opening square bracket by its HTML code, which is
 * ignored by WordPress.
 *
 * @param  string $text
 * @return string
 */
function escape_shortcodes($text) {
    return preg_replace_callback('/' . get_shortcode_regex() . '/', function($matches) {
      return str_replace('[', '&#x005B;', $matches[0]);
    }, $text);
}

/**
 * Cleanup UTF8 control characters.
 *
 * - Remove UTF8 control characters
 *
 * @param  string $content
 * @return string
 */
function cleanup_utf8_ctrl( $content ) {
	return trim( preg_replace('/[\x00-\x09\x0B\x0C\x0E-\x1F\x7F]/', '', $content) );
}
