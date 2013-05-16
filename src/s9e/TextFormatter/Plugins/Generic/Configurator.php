<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2013 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Generic;

use DOMXPath;
use Exception;
use InvalidArgumentException;
use RuntimeException;
use s9e\TextFormatter\Configurator\Collections\NormalizedCollection;
use s9e\TextFormatter\Configurator\Helpers\RegexpParser;
use s9e\TextFormatter\Configurator\Helpers\TemplateHelper;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\Items\Variant;
use s9e\TextFormatter\Configurator\JavaScript\RegexpConvertor;
use s9e\TextFormatter\Plugins\ConfiguratorBase;

/**
* NOTE: does not support duplicate named captures and does not support escaping in the replacement
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

		// Regexp used to find captures (supported: \1, $1, ${1}) in the replacement. Note that for
		// the sake of simplicity, escaping with \\ is not supported
		$capturesRegexp = '#(?:\\\\[0-9]+|\\$[0-9]+|\\$\\{[0-9]+\\})#';

		// Collect the captures used in the replacement
		$captures = [];
		preg_match_all($capturesRegexp, $template, $matches);
		foreach ($matches[0] as $match)
		{
			$idx = trim($match, '\\${}');
			$captures[$idx] = false;
		}

		// Generate a tag name based on the regexp
		$tagName = sprintf('G%08X', crc32($regexp));

		// Create the tag that will represent the regexp but don't add it right now
		$tag = new Tag;

		// Parse the regexp, and generate an attribute for every named capture, or capture used in
		// the replacement
		$regexpInfo = RegexpParser::parse($regexp);

		// Record the name and position of the subpatterns that need to be named
		$newNamedSubpatterns = [];

		// Subpattern's index
		$idx = 0;

		foreach ($regexpInfo['tokens'] as $tok)
		{
			if ($tok['type'] !== 'capturingSubpatternStart')
			{
				continue;
			}

			++$idx;

			if (isset($tok['name']))
			{
				// Named subpatterns create an attribute of the same name
				$attrName = $tok['name'];
			}
			elseif (isset($captures[$idx]))
			{
				// If this capture is in use, create an attribute named after the capture's index
				$attrName = '_' . $idx;

				// Record its name so we can alter the original regexp afterwards
				$newNamedSubpatterns[$attrName] = $tok['pos'];
			}
			else
			{
				continue;
			}

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

			// Record the name of the attribute to match it to the subpattern
			$captures[$idx] = $attrName;
		}

		// Alter the original regexp to inject the subpatterns' names. The position is equal to the
		// subpattern's position plus 2, to account for the delimiter at the start of the regexp and
		// the opening parenthesis of the subpattern. Also, we need to process them in reverse order
		// so that replacements don't effect the position of subsequent subpatterns
		foreach (array_reverse($newNamedSubpatterns) as $attrName => $pos)
		{
			$regexp = substr_replace($regexp, '?<' . $attrName . '>', 2 + $pos, 0);
		}

		// Replace numeric references in the template with the value of the corresponding attribute
		// values or passthrough
		$template = TemplateHelper::replaceTokens(
			$template,
			$capturesRegexp,
			function ($m) use ($captures)
			{
				$idx  = trim($m[0], '\\${}');

				return ($idx == '0')
				     ? ['passthrough', true]
				     : ['expression', '@' . $captures[$idx]];
			}
		);

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

		$generics   = [];
		$jsGenerics = [];
		foreach ($this->collection as $tagName => $regexp)
		{
			$generics[] = [$tagName, $regexp];

			$jsRegexp = RegexpConvertor::toJS($regexp);
			$jsRegexp->flags .= 'g';

			$jsGenerics[] = [$tagName, $jsRegexp, $jsRegexp->map];
		}

		$variant = new Variant($generics);
		$variant->set('JS', $jsGenerics);

		return ['generics' => $variant];
	}
}