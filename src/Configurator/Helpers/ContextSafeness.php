<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2021 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Helpers;

abstract class ContextSafeness
{
	/**
	* Get the list of UTF-8 characters that are disallowed as a URL
	*
	* ":" is disallowed to prevent the URL to have a scheme.
	*
	* @return string[]
	*/
	public static function getDisallowedCharactersAsURL()
	{
		return [':'];
	}

	/**
	* Get the list of UTF-8 characters that are disallowed in CSS
	*
	* - "(" and ")" are disallowed to prevent executing CSS functions or proprietary extensions that
	*   may execute JavaScript.
	* - ":" is disallowed to prevent setting extra CSS properties as well as possibly misusing the
	*   url() function with javascript: URIs.
	* - "\", '"' and "'" are disallowed to prevent breaking out of or interfering with strings.
	* - ";", "{" and "}" to prevent breaking out of a declaration
	*
	* @return string[]
	*/
	public static function getDisallowedCharactersInCSS()
	{
		return ['(', ')', ':', '\\', '"', "'", ';', '{', '}'];
	}

	/**
	* Get the list of UTF-8 characters that are disallowed in JS
	*
	* Allowing *any* input inside of a JavaScript context is a risky proposition. The use cases are
	* also pretty rare. This list of disallowed characters attempts to block any character that is
	* potentially unsafe either inside or outside of a string.
	*
	* - "(" and ")" are disallowed to prevent executing functions.
	* - '"',  "'", "\" and "`" are disallowed to prevent breaking out of or interfering with strings.
	* - "\r", "\n", U+2028 and U+2029 are disallowed inside of JavaScript strings.
	* - ":" and "%" are disallowed to prevent potential exploits that set document.location to a
	*   javascript: URI.
	* - "=" is disallowed to prevent overwriting existing vars (or constructors, such as Array's) if
	*   the input is used outside of a string
	*
	* @return string[]
	*/
	public static function getDisallowedCharactersInJS()
	{
		return ['(', ')', '"', "'", '\\', '`', "\r", "\n", "\xE2\x80\xA8", "\xE2\x80\xA9", ':', '%', '='];
	}
}