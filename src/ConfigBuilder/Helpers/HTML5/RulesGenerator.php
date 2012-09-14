<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\ConfigBuilder\Helpers\HTML5;

use s9e\TextFormatter\ConfigBuilder\Collections\TagCollection;

abstract class RulesGenerator
{
	/**
	* Generate rules based on HTML5 content models
	*
	* Possible options:
	*
	*  renderer:   instance of Renderer, used to render tags that have no individual templates set
	*  parentHTML: 
	*
	* @param  TagCollection $tags    Tags collection
	* @param  array         $options Array of option settings
	* @return array
	*/
	public static function getRules(TagCollection $tags, array $options = array())
	{
		// Unless specified otherwise, we consider that the renderered text will be displayed as
		// the child of a <div> element
		$parentHTML = (isset($options['parentHTML']))
		            ? $options['parentHTML']
		            : '<div>';

		// Create a proxy for the parent markup so that we can determine which tags are allowed at
		// the root of the message (IOW, with no parent) or even disabled altogether
		$rootProxy = self::generateRootProxy($parentHTML);

		$tagProxies = array();
		foreach ($tags as $tagName => $tag)
		{
			$tagProxies[$tagName] = new TagProxy($tag->templates, $options);
		}

		return self::cleanUpRules(self::generateRules($tagProxies, $rootProxy));
	}

	/**
	* 
	*
	* @return void
	*/
	protected static function generateRootProxy($html)
	{
		$dom = new DOMDocument;
		$dom->loadHTML($html);

		$xpath = new DOMXPath($dom);

		// Get the document's <body> element
		$body = $dom->getElementsByTagName('body')->item(0);

		// Get the first child, which we'll consider the root of our parent DOM
		$root = $body->firstChild;
		$node = $body;

		// Go as deep as possible
		while ($node->firstChild)
		{
			$node = $node->firstChild;
		}

		// Now append an <xsl:apply-templates/> node to make the markup looks like a normal template
		$node->appendChild($dom->createElementNS(
			'http://www.w3.org/1999/XSL/Transform',
			'xsl:apply-templates'
		);

		// Create a fake templateset
		$templates = array($dom->saveXML($root));

		// Finally create and return a new TagProxy instance
		return new TagProxy($templates);
	}

	/**
	* 
	*
	* @return array
	*/
	protected static function generateRules(array $tagProxies, TagProxy $rootProxy)
	{
		$rules = array();
		foreach ($tagProxies as $srcTagName => $srcTag)
		{
			// Test whether this tag can be used with no parent
			if (!$srcTag->canBeAChildOf($rootProxy))
			{
				$rules[$srcTagName]['disallowAtRoot'] = true;
			}

			// Create an inheritRules rule if the tag is transparent
			if ($srcTag->isTransparent())
			{
				$rules[$srcTagName]['inheritRules'] = true;
			}

			foreach ($tagProxies as $trgTagName => $trgTag)
			{
				// Test whether the target tag can be a child of the source tag
				if ($trgTag->canBeAChildOf($srcTag))
				{
					$rules[$srcTagName]['allowChild'][] = $trgTagName;
				}
				else
				{
					$rules[$srcTagName]['denyChild'][] = $trgTagName;
				}

				// Test whether the target tag can be a descendant of the source tag
				if (!$trgTag->canBeADescendantOf($srcTag))
				{
					$rules[$srcTagName]['denyDescendant'][] = $trgTagName;
				}

				if ($srcTag->closesParent($trgTag))
				{
					$rules[$srcTagName]['closeParent'][] $trgTagName;
				}
			}
		}

		return $rules;
	}

	/**
	* 
	*
	* @return array
	*/
	protected function cleanUpRules(array $rules)
	{
		// Prepare to deduplicate rules and resolve conflicting rules
		$precedence = array(
			array('denyDescendant', 'denyChild'),
			array('denyDescendant', 'allowChild'),
			array('denyChild', 'allowChild')
		);

		foreach ($rules as $tagName => &$tagrules)
		{
			// Flip the rules targets so we can use them as keys
			$tagRules = array_map('array_flip', $tagRules);

			// Apply precedence, e.g. if there's a denyChild rule, remove any allowChild rules
			foreach ($precedence as $pair)
			{
				list($k1, $k2) = $pair;

				if (!isset($tagRules[$k1], $tagRules[$k2]))
				{
					continue;
				}

				$tagRules[$k2] = array_diff_key(
					$tagRules[$k2],
					$tagRules[$k1]
				);
			}

			// Flip the rules again
			$tagRules = array_map('array_keys', $tagRules);

			// Remove empty rules
			$tagRules = array_filter($tagRules);
		}

		return $rules;
	}
}