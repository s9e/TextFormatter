<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2013 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Generic;

use DOMAttr;
use DOMText;
use DOMXPath;
use Exception;
use InvalidArgumentException;
use LogicException;
use RuntimeException;
use s9e\TextFormatter\Configurator\Collections\NormalizedCollection;
use s9e\TextFormatter\Configurator\Helpers\RegexpParser;
use s9e\TextFormatter\Configurator\Helpers\TemplateHelper;
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
	* @var array Array of [tagName => [regexp, passthroughIdx]]
	*/
	protected $collection = [];

	/**
	* Add a generic replacement
	*
	* @param  string $regexp   Regexp to be used by the parser
	* @param  string $template Template to be used for rendering
	* @param  string $tagName  Name of the tag to create. If none is provided a name is
	*                          automatically generated based on the regexp
	* @return string           The name of the tag created to represent this replacement
	*/
	public function add($regexp, $template, $tagName = null)
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

		// Regexp used to find captures (supported: \1, $1, ${1}) in the replacement. We check that
		// the captures are not preceded with an odd number of backslashes (even number is fine)
		$assertion         = '(?<!\\\\)(?:\\\\\\\\)*\\K';
		$captureSubpattern = '(?:\\\\[0-9]+|\\$[0-9]+|\\$\\{[0-9]+\\})';
		$capturesRegexp    = '#' . $assertion . $captureSubpattern . '#';

		// Collect all the captures used in the replacement
		$captures = [];
		preg_match_all($capturesRegexp, $template, $matches);
		foreach ($matches[0] as $match)
		{
			$idx = trim($match, '\\${}');
			$captures[$idx] = false;
		}

		// Load the template as a DOM so we can inspect it
		$dom   = TemplateHelper::loadTemplate($template);
		$xpath = new DOMXPath($dom);

		// Find which captures are used in text nodes in the template. One of them may be replaced
		// with an <xsl:apply-templates/> element if the corresponding subpattern matches any text.
		// They are informally called "passthrough"
		$passthrough = [];
		foreach ($xpath->query('//text()') as $node)
		{
			preg_match_all($capturesRegexp, $node->textContent, $matches);
			foreach ($matches[0] as $match)
			{
				$idx = trim($match, '\\${}');

				// The value is false until we confirm that the subpattern is a match-all such as .*
				$passthrough[$idx] = false;
			}
		}

		// Collect the indices of the captures that are used as a URL, so that we can properly
		// filter the corresponding attribute later on. The indices are stored as keys for
		// convenience
		$urlCaptures = [];
		$nodes       = TemplateHelper::getURLNodes($dom);
		foreach ($nodes as $node)
		{
			// We only bother with literal attributes, and we only collect captures at the start
			// of an URL attribute
			if ($node instanceof DOMAttr
			 && preg_match('#^\\s*' . $captureSubpattern . '#', $node->value, $m))
			{
				$idx = preg_replace('#\\D+#', '', $m[0]);
				$urlCaptures[$idx] = $idx;
			}
		}

		// Generate a tag name based on the regexp
		if (!isset($tagName))
		{
			$tagName = sprintf('G%08X', crc32($regexp));
		}

		// Create the tag that will represent the regexp but don't add it right now
		$tag = new Tag;

		// Parse the regexp. We'll generate an attribute for every named capture, or capture used in
		// the replacement
		$regexpInfo = RegexpParser::parse($regexp);

		// List of attributes to create. The capture index is used as key, the subpattern's info as
		// value
		$attributes = [];

		// Capture index
		$idx = 0;

		foreach ($regexpInfo['tokens'] as $token)
		{
			if ($token['type'] !== 'capturingSubpatternStart')
			{
				continue;
			}

			++$idx;

			// Test whether this subpattern has a name
			if (!isset($token['name']))
			{
				// If the subpattern does not have a name and is not used in the template, we just
				// ignore it
				if (!isset($captures[$idx]))
				{
					continue;
				}

				// If the subpattern does not have a name and it's used in a text node in the
				// template and it can match anything (e.g. ".*?"), it can be a passthrough to be
				// replaced with an <xsl:apply-templates/> element
				if (isset($passthrough[$idx]) && preg_match('#^\\.[*+]\\??$#D', $token['content']))
				{
					$passthrough[$idx] = true;
				}
			}

			// This subpattern either has a name or it's used in the template so it will create an
			// attribute unless it turns out that it's used as a passthrough. For now we store its
			// information
			$attributes[$idx] = $token;
		}

		// Remove any passthrough whose value isn't true. We can only have one passthrough, so we
		// if there are more than one left, we ignore them all
		$passthrough    = array_filter($passthrough);
		$passthroughIdx = 0;
		if (count($passthrough) === 1)
		{
			$passthroughIdx = key($passthrough);

			// The capture used as passthrough should not create an attribute, unless it needs to be
			// filtered as a URL
			if (!isset($urlCaptures[$passthroughIdx]))
			{
				unset($attributes[$passthroughIdx]);
			}
		}

		// Subpatterns used in the template will be given a name if they don't have one already.
		// We need to record their name and position in the regexp
		$newNamedSubpatterns = [];

		// Now create all the attributes
		foreach ($attributes as $idx => $token)
		{
			if (isset($token['name']))
			{
				// Named subpatterns create an attribute of the same name
				$attrName = $token['name'];
			}
			elseif (isset($captures[$idx]))
			{
				// If this capture is in use, create an attribute named after the capture's index
				$attrName = '_' . $idx;

				// Record its name so we can alter the original regexp afterwards
				$newNamedSubpatterns[$attrName] = $token['pos'];
			}
			else
			{
				throw new LogicException('Tried to create an attribute for an unused capture with no name. Please file a bug');
			}

			if (isset($tag->attributes[$attrName]))
			{
				throw new RuntimeException('Duplicate named subpatterns are not allowed');
			}

			// Create the attribute and ensure it's required
			$attribute = $tag->attributes->add($attrName);
			$attribute->required = true;

			// Create the regexp for the attribute
			$endToken = $regexpInfo['tokens'][$token['endToken']];
			$lpos = $token['pos'];
			$rpos = $endToken['pos'] + $endToken['len'];

			$attrRegexp = $regexpInfo['delimiter']
			            . '^' . substr($regexpInfo['regexp'], $lpos, $rpos - $lpos) . '$'
			            . $regexpInfo['delimiter']
			            . str_replace('D', '', $regexpInfo['modifiers'])
			            . 'D';

			// Create a #regexp filter and append it to this attribute's filterChain
			$filter = $this->configurator->attributeFilters->get('#regexp');
			$filter->setRegexp($attrRegexp);
			$attribute->filterChain->append($filter);

			// Append a #url filter if the attribute is used as a URL
			if (isset($urlCaptures[$idx]))
			{
				$filter = $this->configurator->attributeFilters->get('#url');
				$attribute->filterChain->append($filter);
			}

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

		// Remove captures with that have not created an attribute from the list
		$captures = array_filter($captures);

		// Replace numeric references in the template with the value of the corresponding attribute
		// values or passthrough
		$template = TemplateHelper::replaceTokens(
			$template,
			$capturesRegexp,
			function ($m, $node) use ($captures, $passthroughIdx)
			{
				$idx  = (int) trim($m[0], '\\${}');

				// $0 copies the whole textContent
				if ($idx === 0)
				{
					return ['expression', '.'];
				}

				// Passthrough capture, does not include start/end tags
				if ($idx === $passthroughIdx)
				{
					// We only use it as a passthrough if it's inside a text node or there's no
					// corresponding attribute for it. It means that a capture that has been
					// identified as a URL will be replaced with a passthrough in text, and with the
					// filtered value in attributes
					if (!isset($captures[$idx])
					 || $node instanceof DOMText)
					{
						return ['passthrough', false];
					}
				}

				// Normal capture, replaced by the equivalent expression
				if (isset($captures[$idx]))
				{
					return ['expression', '@' . $captures[$idx]];
				}

				// Non-existent captures are simply ignored, similarly to preg_replace()
				return ['literal', ''];
			}
		);

		// Unescape backslashes and special characters in the template
		$template = TemplateHelper::replaceTokens(
			$template,
			'#\\\\+[0-9${\\\\]#',
			function ($m)
			{
				return ['literal', stripslashes($m[0])];
			}
		);

		// Now that all attributes have been created we can assign the template
		$tag->template = $template;

		// Normalize the tag's templates
		$this->configurator->templateNormalizer->normalizeTag($tag);

		// Check the safeness of this tag
		$this->configurator->templateChecker->checkTag($tag);

		// Add the tag to the configurator
		$this->configurator->tags->add($tagName, $tag);

		// Finally, record the regexp
		$this->collection[$tagName] = [$regexp, $passthroughIdx];

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
		foreach ($this->collection as $tagName => list($regexp, $passthroughIdx))
		{
			$generics[] = [$tagName, $regexp, $passthroughIdx];

			$jsRegexp = RegexpConvertor::toJS($regexp);
			$jsRegexp->flags .= 'g';

			$jsGenerics[] = [$tagName, $jsRegexp, $passthroughIdx, $jsRegexp->map];
		}

		return ['generics' => new Variant($generics, ['JS' => $jsGenerics])];
	}
}