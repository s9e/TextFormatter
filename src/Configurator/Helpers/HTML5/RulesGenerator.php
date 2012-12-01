<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Helpers\HTML5;

use DOMDocument;
use DOMXPath;
use s9e\TextFormatter\Configurator\Collections\TagCollection;
use s9e\TextFormatter\Configurator\Items\Tag;

abstract class RulesGenerator
{
	/**
	* Generate rules based on HTML5 content models
	*
	* Possible options:
	*
	*  parentHTML: HTML leading to the start of the rendered text. Defaults to "<div>"
	*  renderer:   instance of Renderer, used to render tags that have no individual templates set.
	*              Must output valid XML, not HTML
	*
	* @param  TagCollection $tags    Tags collection
	* @param  array         $options Array of option settings
	* @return array ['root'=>[ruleName=>targets],'tags'=>[tagName=>[ruleName=>targets]]]
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
		$rootForensics = self::generateRootForensics($parentHTML);

		$templateForensics = array();
		foreach ($tags as $tagName => $tag)
		{
			$xsl = self::generateTagXSL($tagName, $tag, $options);

			$templateForensics[$tagName] = new TemplateForensics($xsl);
		}

		// Generate a full set of rules
		$rules = self::generateRules($templateForensics, $rootForensics);

		// Clean up tags' rules
		foreach ($rules['tags'] as $tagName => &$tagRules)
		{
			$tagRules = self::cleanUpRules($tagRules);
		}
		unset($tagRules);

		// Clean up root rules
		$rules['root'] = self::cleanUpRules($rules['root']);

		return $rules;
	}

	/**
	* 
	*
	* @return string
	*/
	protected static function generateTagXSL($tagName, Tag $tag, array $options)
	{
		$xsl = '<xsl:template xmlns:xsl="http://www.w3.org/1999/XSL/Transform">';

		if (count($tag->templates))
		{
			foreach ($tag->templates as $template)
			{
				$xsl .= $template;
			}
		}
		elseif (isset($options['renderer']))
		{
			$xml = '<' . $tagName;

			// Add namespace declaration if the name has a prefix
			$pos = strpos($tagName, ':');
			if ($pos !== false)
			{
				$prefix = substr($tagName, 0, $pos);
				$xml .= ' xmlns:' . $prefix . '="urn:s9e:TextFormatter:' . $prefix . '"';
			}

			// Add all attributes with an empty value
			foreach ($tag->attributes as $attrName => $attribute)
			{
				$xml .= ' ' . $attrName . '=""';
			}

			// Close the start tag
			$xml .= '>';

			// Add a unique token to identify whether and where the tag's content is displayed
			$uniqid = uniqid('', true);
			$xml .= $uniqid;

			// And finally append the end tag
			$xml .= '</' . $tagName . '>';

			// Add the renderered markup to our XSL
			/**
			* @todo ensure the result is valid XML, not HTML
			*/
			$xsl .= str_replace(
				$uniqid,
				'<xsl:apply-templates/>',
				$renderer->render($xml)
			);
		}

		$xsl .= '</xsl:template>';

		return $xsl;
	}

	/**
	* Generate a TemplateForensics instance for the root element
	*
	* @param  string            $html Root HTML, e.g. "<div>"
	* @return TemplateForensics
	*/
	protected static function generateRootForensics($html)
	{
		$dom = new DOMDocument;
		$dom->loadHTML($html);

		// Get the document's <body> element
		$body = $dom->getElementsByTagName('body')->item(0);

		// Grab the deepest node
		$node = $body;
		while ($node->firstChild)
		{
			$node = $node->firstChild;
		}

		// Now append an <xsl:apply-templates/> node to make the markup look like a normal template
		$node->appendChild($dom->createElementNS(
			'http://www.w3.org/1999/XSL/Transform',
			'xsl:apply-templates'
		));

		// Generate our XSL template
		$xsl = '<xsl:template xmlns:xsl="http://www.w3.org/1999/XSL/Transform">'
		     . $dom->saveXML($body)
		     . '</xsl:template>';

		// Finally create and return a new TemplateForensics instance
		return new TemplateForensics($xsl);
	}

	/**
	* Generate and return rules based on a set of TemplateForensics
	*
	* @param  array             $templateForensics Array of [tagName => TemplateForensics]
	* @param  TemplateForensics $rootForensics     TemplateForensics for the root of the text
	* @return array
	*/
	protected static function generateRules(array $templateForensics, TemplateForensics $rootForensics)
	{
		$rules = array(
			'root' => array(),
			'tags' => array()
		);

		// Create a TemplateForensics object that will be used to determine whether to create a
		// nl2br rule
		$br = new TemplateForensics(
			'<xsl:template xmlns:xsl="http://www.w3.org/1999/XSL/Transform"><br/></xsl:template>'
		);

		foreach ($templateForensics as $srcTagName => $srcTag)
		{
			// Test whether this tag can be used with no parent
			$ruleName = ($rootForensics->allowsChild($srcTag)) ? 'allowChild' : 'denyChild';
			$rules['root'][$ruleName][] = $srcTagName;

			// Test whether this tag should be closed automatically
			if ($srcTag->isVoid())
			{
				$rules['tags'][$srcTagName]['autoClose'] = true;
			}

			// Test whether this tag should be reopened automatically
			if ($srcTag->autoReopen())
			{
				$rules['tags'][$srcTagName]['autoReopen'] = true;
			}

			// Create an denyAll rule if the tag's forensics call for it
			if ($srcTag->denyAll())
			{
				$rules['tags'][$srcTagName]['denyAll'] = true;
			}

			// Test whether text children should be ignored
			if (!$srcTag->allowsText())
			{
				$rules['tags'][$srcTagName]['ignoreText'] = true;
			}

			// Create an isTransparent rule if the tag is transparent
			if ($srcTag->isTransparent())
			{
				$rules['tags'][$srcTagName]['isTransparent'] = true;
			}

			// Create a noBrChild rule if the tag does not allow <br/> children
			if (!$srcTag->allowsChild($br))
			{
				$rules['tags'][$srcTagName]['noBrChild'] = true;
			}

			// Create a noBrDescendant rule if the tag does not allow <br/> descendants or if it
			// preserves whitespace (e.g. <pre>)
			if (!$srcTag->allowsDescendant($br) || $srcTag->preservesWhitespace())
			{
				$rules['tags'][$srcTagName]['noBrDescendant'] = true;
			}

			// Test whether this tag is a block-level element, which would mean its surrounding
			// whitespace should be trimmed
			if ($srcTag->isBlock())
			{
				$rules['tags'][$srcTagName]['trimWhitespace'] = true;
			}

			foreach ($templateForensics as $trgTagName => $trgTag)
			{
				// Test whether the target tag can be a child of the source tag and a descendant
				// of the parent markup
				if ($srcTag->allowsChild($trgTag)
				 && $rootForensics->allowsDescendant($trgTag))
				{
					$rules['tags'][$srcTagName]['allowChild'][] = $trgTagName;
				}
				else
				{
					$rules['tags'][$srcTagName]['denyChild'][] = $trgTagName;
				}

				// Test whether the target tag can be a descendant of the source tag
				if (!$srcTag->allowsDescendant($trgTag))
				{
					$rules['tags'][$srcTagName]['denyDescendant'][] = $trgTagName;
				}

				if ($srcTag->closesParent($trgTag))
				{
					$rules['tags'][$srcTagName]['closeParent'][] = $trgTagName;
				}
			}
		}

		return $rules;
	}

	/**
	* Prune conflicting rules from a set
	*
	* @param  array $rules Array of [ruleName => targets]
	* @return array
	*/
	protected static function cleanUpRules(array $rules)
	{
		// Prepare to resolve conflicting rules
		$precedence = array(
			array('denyDescendant', 'allowChild'),
			array('denyChild',      'allowChild')
		);

		// Apply precedence, e.g. if there's a denyChild rule, remove any allowChild rules
		foreach ($precedence as $pair)
		{
			list($k1, $k2) = $pair;

			if (!isset($rules[$k1], $rules[$k2]))
			{
				continue;
			}

			$rules[$k2] = array_diff(
				$rules[$k2],
				$rules[$k1]
			);
		}

		// Remove empty rules
		$rules = array_filter($rules);

		return $rules;
	}
}