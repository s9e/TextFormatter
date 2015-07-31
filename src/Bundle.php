<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter;
abstract class Bundle
{
	public static function reset()
	{
		static::$parser   = \null;
		static::$renderer = \null;
	}
	public static function parse($text)
	{
		if (!isset(static::$parser))
			static::$parser = static::getParser();
		if (isset(static::$beforeParse))
			$text = \call_user_func(static::$beforeParse, $text);
		$xml = static::$parser->parse($text);
		if (isset(static::$afterParse))
			$xml = \call_user_func(static::$afterParse, $xml);
		return $xml;
	}
	public static function render($xml, array $params = array())
	{
		if (!isset(static::$renderer))
			static::$renderer = static::getRenderer();
		if (!empty($params))
			static::$renderer->setParameters($params);
		if (isset(static::$beforeRender))
			$xml = \call_user_func(static::$beforeRender, $xml);
		$output = static::$renderer->render($xml);
		if (isset(static::$afterRender))
			$output = \call_user_func(static::$afterRender, $output);
		return $output;
	}
	public static function unparse($xml)
	{
		if (isset(static::$beforeUnparse))
			$xml = \call_user_func(static::$beforeUnparse, $xml);
		$text = Unparser::unparse($xml);
		if (isset(static::$afterUnparse))
			$text = \call_user_func(static::$afterUnparse, $text);
		return $text;
	}
}