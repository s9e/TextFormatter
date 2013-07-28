<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2013 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter;

abstract class Bundle
{
	/**
	* Reset the cached parser and renderer
	*
	* @return void
	*/
	public static function reset()
	{
		static::$parser   = null;
		static::$renderer = null;
	}

	/**
	* Parse given text using a singleton instance of the bundled Parser
	*
	* @param  string $text Original text
	* @return string       Intermediate representation
	*/
	public static function parse($text)
	{
		if (!isset(static::$parser))
		{
			static::$parser = static::getParser();
		}

		return static::$parser->parse($text);
	}

	/**
	* Render an intermediate representation using a singleton instance of the bundled Renderer
	*
	* @param  string $xml    Intermediate representation
	* @param  array  $params Stylesheet parameters
	* @return string         Rendered result
	*/
	public static function render($xml, array $params = [])
	{
		if (!isset(static::$renderer))
		{
			static::$renderer = static::getRenderer();
		}

		static::$renderer->setParameters($params);

		return static::$renderer->render($xml);
	}

	/**
	* Render an array of intermediate representations using a singleton instance of the bundled Renderer
	*
	* @param  array $arr    Array of XML strings
	* @param  array $params Stylesheet parameters
	* @return array         Array of render results (same keys, same order)
	*/
	public static function renderMulti(array $arr, array $params = [])
	{
		if (!isset(static::$renderer))
		{
			static::$renderer = static::getRenderer();
		}

		static::$renderer->setParameters($params);

		return static::$renderer->renderMulti($arr);
	}

	/**
	* Transform an intermediate representation back to its original form
	*
	* @param  string $xml Intermediate representation
	* @return string      Original text
	*/
	public static function unparse($xml)
	{
		return Unparser::unparse($xml);
	}
}