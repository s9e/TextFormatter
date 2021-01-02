<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2021 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Parser;

use s9e\TextFormatter\Parser;

class FilterProcessing
{
	/**
	* Execute all the attribute preprocessors of given tag
	*
	* @private
	*
	* @param  Tag   $tag       Source tag
	* @param  array $tagConfig Tag's config
	* @return void
	*/
	public static function executeAttributePreprocessors(Tag $tag, array $tagConfig)
	{
		if (empty($tagConfig['attributePreprocessors']))
		{
			return;
		}

		foreach ($tagConfig['attributePreprocessors'] as list($attrName, $regexp, $map))
		{
			if ($tag->hasAttribute($attrName))
			{
				self::executeAttributePreprocessor($tag, $attrName, $regexp, $map);
			}
		}
	}

	/**
	* Filter the attributes of given tag
	*
	* @private
	*
	* @param  Tag    $tag            Tag being checked
	* @param  array  $tagConfig      Tag's config
	* @param  array  $registeredVars Array of registered vars for use in attribute filters
	* @param  Logger $logger         This parser's Logger instance
	* @return void
	*/
	public static function filterAttributes(Tag $tag, array $tagConfig, array $registeredVars, Logger $logger)
	{
		$attributes = [];
		foreach ($tagConfig['attributes'] as $attrName => $attrConfig)
		{
			$attrValue = false;
			if ($tag->hasAttribute($attrName))
			{
				$vars = [
					'attrName'       => $attrName,
					'attrValue'      => $tag->getAttribute($attrName),
					'logger'         => $logger,
					'registeredVars' => $registeredVars
				];
				$attrValue = self::executeAttributeFilterChain($attrConfig['filterChain'], $vars);
			}

			if ($attrValue !== false)
			{
				$attributes[$attrName] = $attrValue;
			}
			elseif (isset($attrConfig['defaultValue']))
			{
				$attributes[$attrName] = $attrConfig['defaultValue'];
			}
			elseif (!empty($attrConfig['required']))
			{
				$tag->invalidate();
			}
		}
		$tag->setAttributes($attributes);
	}

	/**
	* Execute a tag's filterChain
	*
	* @private
	*
	* @param  Tag    $tag        Tag to filter
	* @param  Parser $parser     Parser
	* @param  array  $tagsConfig Tags' config
	* @param  Tag[]  $openTags   List of open tags
	* @return void
	*/
	public static function filterTag(Tag $tag, Parser $parser, array $tagsConfig, array $openTags)
	{
		$tagName   = $tag->getName();
		$tagConfig = $tagsConfig[$tagName];

		// Record the tag being processed into the logger it can be added to the context of
		// messages logged during the execution
		$logger = $parser->getLogger();
		$logger->setTag($tag);

		// Prepare the variables that are accessible to filters
		$text = $parser->getText();
		$vars = [
			'innerText'      => '',
			'logger'         => $logger,
			'openTags'       => $openTags,
			'outerText'      => substr($text, $tag->getPos(), $tag->getLen()),
			'parser'         => $parser,
			'registeredVars' => $parser->registeredVars,
			'tag'            => $tag,
			'tagConfig'      => $tagConfig,
			'tagText'        => substr($text, $tag->getPos(), $tag->getLen()),
			'text'           => $text
		];
		$endTag = $tag->getEndTag();
		if ($endTag)
		{
			$vars['innerText'] = substr($text, $tag->getPos() + $tag->getLen(), $endTag->getPos() - $tag->getPos() - $tag->getLen());
			$vars['outerText'] = substr($text, $tag->getPos(), $endTag->getPos() + $endTag->getLen() - $tag->getPos());
		}
		foreach ($tagConfig['filterChain'] as $filter)
		{
			if ($tag->isInvalid())
			{
				break;
			}
			self::executeFilter($filter, $vars);
		}

		// Remove the tag from the logger
		$logger->unsetTag();
	}

	/**
	* Execute an attribute's filterChain
	*
	* @param  array $filterChain Attribute's filterChain
	* @param  array $vars        Callback vars
	* @return mixed              Filtered value
	*/
	protected static function executeAttributeFilterChain(array $filterChain, array $vars)
	{
		$vars['logger']->setAttribute($vars['attrName']);
		foreach ($filterChain as $filter)
		{
			$vars['attrValue'] = self::executeFilter($filter, $vars);
			if ($vars['attrValue'] === false)
			{
				break;
			}
		}
		$vars['logger']->unsetAttribute();

		return $vars['attrValue'];
	}

	/**
	* Execute an attribute preprocessor
	*
	* @param  Tag      $tag
	* @param  string   $attrName
	* @param  string   $regexp
	* @param  string[] $map
	* @return void
	*/
	protected static function executeAttributePreprocessor(Tag $tag, $attrName, $regexp, $map)
	{
		$attrValue = $tag->getAttribute($attrName);
		$captures  = self::getNamedCaptures($attrValue, $regexp, $map);
		foreach ($captures as $k => $v)
		{
			// Attribute preprocessors cannot overwrite other attributes but they can
			// overwrite themselves
			if ($k === $attrName || !$tag->hasAttribute($k))
			{
				$tag->setAttribute($k, $v);
			}
		}
	}

	/**
	* Execute a filter
	*
	* @see s9e\TextFormatter\Configurator\Items\ProgrammableCallback
	*
	* @param  array $filter Programmed callback
	* @param  array $vars   Variables to be used when executing the callback
	* @return mixed         Whatever the callback returns
	*/
	protected static function executeFilter(array $filter, array $vars)
	{
		// Add vars from the registeredVars array to the list of vars
		$vars += ['registeredVars' => []];
		$vars += $vars['registeredVars'];

		// Prepare the list of arguments
		$args = [];
		if (isset($filter['params']))
		{
			foreach ($filter['params'] as $k => $v)
			{
				$args[] = (isset($vars[$k])) ? $vars[$k] : $v;
			}
		}

		return call_user_func_array($filter['callback'], $args);
	}

	/**
	* Execute a regexp and return the values of the mapped captures
	*
	* @param  string   $str
	* @param  string   $regexp
	* @param  string[] $map
	* @return array
	*/
	protected static function getNamedCaptures($str, $regexp, $map)
	{
		if (!preg_match($regexp, $str, $m))
		{
			return [];
		}

		$values = [];
		foreach ($map as $i => $k)
		{
			if (isset($m[$i]) && $m[$i] !== '')
			{
				$values[$k] = $m[$i];
			}
		}

		return $values;
	}
}