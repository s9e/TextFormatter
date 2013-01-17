<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2013 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Generic;

use Exception;
use InvalidArgumentException;
use RuntimeException;
use s9e\TextFormatter\Configurator\Collections\NormalizedCollection;
use s9e\TextFormatter\Configurator\Helpers\RegexpParser;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\Items\Variant;
use s9e\TextFormatter\Configurator\JavaScript\RegexpConvertor;
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

				// Create the attribute and ensure it's required
				$attribute = $tag->attributes->add($attrName);
				$attribute->required = true;

				// Create the regexp for the attribute
				$endToken = $tok['endToken'];

				$lpos = $tok['pos'];
				$rpos = $regexpInfo['tokens'][$endToken]['pos']
				      + $regexpInfo['tokens'][$endToken]['len'];

				$attrRegexp = $regexpInfo['delimiter']
				            . '^' . substr($regexpInfo['regexp'], $lpos, $rpos - $lpos) . '$'
				            . $regexpInfo['delimiter']
				            . str_replace('D', '', $regexpInfo['modifiers'])
				            . 'D';

				// Create a regexp filter and append it to this attribute's filterChain
				$filter = $this->configurator->attributeFilters->get('#regexp');
				$filter->setRegexp($attrRegexp);
				$attribute->filterChain->append($filter);
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

		$generics   = array();
		$jsGenerics = array();
		foreach ($this->collection as $tagName => $regexp)
		{
			$generics[] = array($tagName, $regexp);

			$jsRegexp = RegexpConvertor::toJS($regexp);
			$jsRegexp->flags .= 'g';

			$jsGenerics[] = array($tagName, $jsRegexp, $jsRegexp->map);
		}

		$variant = new Variant($generics);
		$variant->set('JS', $jsGenerics);

		return array('generics' => $variant);
	}
}