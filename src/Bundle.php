<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2021 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter;

abstract class Bundle
{
	/**
	* Return a cached instance of the parser
	*
	* @return Parser
	*/
	public static function getCachedParser()
	{
		if (!isset(static::$parser))
		{
			static::$parser = static::getParser();
		}

		return static::$parser;
	}

	/**
	* Return a cached instance of the renderer
	*
	* @return Renderer
	*/
	public static function getCachedRenderer()
	{
		if (!isset(static::$renderer))
		{
			static::$renderer = static::getRenderer();
		}

		return static::$renderer;
	}

	/**
	* Return a new instance of s9e\TextFormatter\Parser
	*
	* @return Parser
	*/
	abstract public static function getParser();

	/**
	* Return a new instance of s9e\TextFormatter\Renderer
	*
	* @return Renderer
	*/
	abstract public static function getRenderer();

	/**
	* Return the source of the JavaScript parser if available
	*
	* @return string
	*/
	public static function getJS()
	{
		return '';
	}

	/**
	* Parse given text using a singleton instance of the bundled Parser
	*
	* @param  string $text Original text
	* @return string       Intermediate representation
	*/
	public static function parse($text)
	{
		if (isset(static::$beforeParse))
		{
			$text = call_user_func(static::$beforeParse, $text);
		}

		$xml = static::getCachedParser()->parse($text);

		if (isset(static::$afterParse))
		{
			$xml = call_user_func(static::$afterParse, $xml);
		}

		return $xml;
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
		$renderer = static::getCachedRenderer();

		if (!empty($params))
		{
			$renderer->setParameters($params);
		}

		if (isset(static::$beforeRender))
		{
			$xml = call_user_func(static::$beforeRender, $xml);
		}

		$output = $renderer->render($xml);

		if (isset(static::$afterRender))
		{
			$output = call_user_func(static::$afterRender, $output);
		}

		return $output;
	}

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
	* Transform an intermediate representation back to its original form
	*
	* @param  string $xml Intermediate representation
	* @return string      Original text
	*/
	public static function unparse($xml)
	{
		if (isset(static::$beforeUnparse))
		{
			$xml = call_user_func(static::$beforeUnparse, $xml);
		}

		$text = Unparser::unparse($xml);

		if (isset(static::$afterUnparse))
		{
			$text = call_user_func(static::$afterUnparse, $text);
		}

		return $text;
	}
}