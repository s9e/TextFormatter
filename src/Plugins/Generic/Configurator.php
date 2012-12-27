<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Generic;

use Exception;
use InvalidArgumentException;
use RuntimeException;
use s9e\TextFormatter\Configurator\Collections\NormalizedCollection;
use s9e\TextFormatter\Configurator\Helpers\RegexpConvertor;
use s9e\TextFormatter\Configurator\Helpers\RegexpParser;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Plugins\ConfiguratorBase;

/**
* NOTE: does not support duplicate named captures
*/
class Configurator extends ConfiguratorBase
{
	/**
	* @var NormalizedCollection
	*/
	protected $collection;

	/**
	* {@inheritdoc}
	*/
	public function setUp()
	{
		$this->collection = new NormalizedCollection;
	}

	/**
	* Add a generic replacement
	*
	* @param  string $regexp   Regexp to be used by the parser
	* @param  string $template Template to be used for rendering
	* @return string           The name of the tag created to represent this replacement
	*/
	public function add($regexp, $template)
	{
		$valid = false;

		try
		{
			$valid = @preg_match_all($regexp, '', $m);
		}
		catch (Exception $e)
		{
		}

		if ($valid === false)
		{
			throw new InvalidArgumentException('Invalid regexp');
		}

		// Generate a tag name based on the regexp
		$tagName = sprintf('G%08X', crc32($regexp));

		// Create the tag that will represent the regexp but don't add it right now
		$tag = new Tag;

		// Parse the regexp, and generate an attribute for every named capture
		$regexpInfo = RegexpParser::parse($regexp);

		foreach ($regexpInfo['tokens'] as $tok)
		{
			if ($tok['type'] === 'capturingSubpatternStart'
			 && isset($tok['name']))
			{
				$attrName = $tok['name'];

				if (isset($tag->attributes[$attrName]))
				{
					throw new RuntimeException('Duplicate named subpatterns are not allowed');
				}

				$endToken = $tok['endToken'];

				$lpos = $tok['pos'];
				$rpos = $regexpInfo['tokens'][$endToken]['pos']
				      + $regexpInfo['tokens'][$endToken]['len'];

				$attrRegexp = $regexpInfo['delimiter']
				            . '^' . substr($regexpInfo['regexp'], $lpos, $rpos - $lpos) . '$'
				            . $regexpInfo['delimiter']
				            . str_replace('D', '', $regexpInfo['modifiers'])
				            . 'D';

				$attribute = $tag->attributes->add($attrName);

				$attribute->required = true;
				$attribute->filterChain->append('#regexp', array('regexp' => $attrRegexp));
			}
		}

		// Now that all attributes have been created we can assign the template
		$tag->defaultTemplate = $template;

		// Add the tag to the configurator
		$this->configurator->tags->add($tagName, $tag);

		// Finally, record the regexp
		$this->collection[$tagName] = $regexp;

		return $tagName;
	}

	/**
	* {@inheritdoc}
	*/
	public function asConfig()
	{
		if (!count($this->collection))
		{
			return false;
		}

		$generics = array();
		foreach ($this->collection as $tagName => $regexp)
		{
			$generics[] = array($tagName, $regexp);
		}

		return array('generics' => $generics);
	}

	/**
	* Generate and return the Javascript source for this plugin's parser
	*
	* @return string|bool Javascript source, or FALSE if no JS parser is available
	*/
	public function getJSParser()
	{
		// Start with the normal config
		$config = $this->asConfig();

		// If there's no generics, no need for a parser -- this is not supposed to happen though
		if ($config === false)
		{
			return false;
		}

		$src = '[';
		foreach ($config['generics'] as $entry)
		{
			list($tagName, $regexp) = $entry;
			$map = array();

			// Convert the PCRE regexp to a Javascript regexp literal with the global flag
			$regexp = RegexpConvertor::toJS($regexp, $map) . 'g';

			// Add the entry to the list
			$src .= '[' . json_encode($tagName) . ',' . $regexp . ',' . json_encode($map) . '],';
		}
		unset($entry);

		// Remove the last comma and close the list
		$src = substr($src, 0, -1) . ']';

		// Append the plugin's source
		$src .= parent::getJSParser();

		return $src;
	}
}