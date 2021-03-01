<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2021 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RendererGenerators;

use s9e\TextFormatter\Configurator\Helpers\TemplateHelper;
use s9e\TextFormatter\Configurator\RendererGenerator;
use s9e\TextFormatter\Configurator\Rendering;
use s9e\TextFormatter\Configurator\TemplateNormalizer;
use s9e\TextFormatter\Renderers\XSLT as XSLTRenderer;

class XSLT implements RendererGenerator
{
	/**
	* @var TemplateNormalizer
	*/
	public $normalizer;

	/**
	* Constructor
	*/
	public function __construct()
	{
		$this->normalizer = new TemplateNormalizer([
			'MergeConsecutiveCopyOf',
			'MergeIdenticalConditionalBranches',
			'OptimizeNestedConditionals',
			'RemoveLivePreviewAttributes'
		]);
	}

	/**
	* {@inheritdoc}
	*/
	public function getRenderer(Rendering $rendering)
	{
		return new XSLTRenderer($this->getXSL($rendering));
	}

	/**
	* Generate an XSL stylesheet based on given rendering configuration
	*
	* @param  Rendering $rendering
	* @return string
	*/
	public function getXSL(Rendering $rendering)
	{
		$groupedTemplates = [];
		$templates        = $rendering->getTemplates();

		// Replace simple templates if there are at least 3 of them
		TemplateHelper::replaceHomogeneousTemplates($templates, 3);

		// Group tags with identical templates together
		foreach ($templates as $tagName => $template)
		{
			$template = $this->normalizer->normalizeTemplate($template);
			$groupedTemplates[$template][] = $tagName;
		}

		// Declare all the namespaces in use at the top
		$xsl = '<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform"';

		// Append the namespace declarations to the stylesheet
		$prefixes = $this->getPrefixes(array_keys($templates));
		if (!empty($prefixes))
		{
			foreach ($prefixes as $prefix)
			{
				$xsl .= ' xmlns:' . $prefix . '="urn:s9e:TextFormatter:' . $prefix . '"';
			}

			/**
			* Exclude those prefixes to keep the HTML neat
			*
			* @link http://lenzconsulting.com/namespaces-in-xslt/#exclude-result-prefixes
			*/
			$xsl .= ' exclude-result-prefixes="' . implode(' ', $prefixes) . '"';
		}

		// Start the stylesheet with the boilerplate stuff
		$xsl .= '><xsl:output method="html" encoding="utf-8" indent="no"/><xsl:decimal-format decimal-separator="."/>';

		// Add stylesheet parameters
		foreach ($rendering->getAllParameters() as $paramName => $paramValue)
		{
			$xsl .= $this->writeTag('xsl:param', ['name' => $paramName], htmlspecialchars($paramValue, ENT_NOQUOTES));
		}

		// Add templates
		foreach ($groupedTemplates as $template => $tagNames)
		{
			$xsl .= $this->writeTag('xsl:template', ['match' => implode('|', $tagNames)], $template);
		}

		$xsl .= '</xsl:stylesheet>';

		return $xsl;
	}

	/**
	* Extract and return the sorted list of prefixes used in given list of tag names
	*
	* @param  string[] $tagNames
	* @return string[]
	*/
	protected function getPrefixes(array $tagNames)
	{
		$prefixes = [];
		foreach ($tagNames as $tagName)
		{
			$pos = strpos($tagName, ':');
			if ($pos !== false)
			{
				$prefixes[substr($tagName, 0, $pos)] = 1;
			}
		}
		$prefixes = array_keys($prefixes);
		sort($prefixes);

		return $prefixes;
	}

	/**
	* Serialize given tag to XML
	*
	* @param  string $tagName
	* @param  array  $attributes
	* @param  string $content
	* @return string
	*/
	protected function writeTag($tagName, array $attributes, $content)
	{
		$xml = '<' . $tagName;
		foreach ($attributes as $attrName => $attrValue)
		{
			$xml .= ' ' . $attrName . '="' . htmlspecialchars($attrValue) . '"';
		}
		if ($content === '')
		{
			$xml .= '/>';
		}
		else
		{
			$xml .= '>' . $content . '</' . $tagName . '>';
		}

		return $xml;
	}
}