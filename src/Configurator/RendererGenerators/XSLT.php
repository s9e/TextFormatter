<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2017 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RendererGenerators;

use s9e\TextFormatter\Configurator\Helpers\TemplateHelper;
use s9e\TextFormatter\Configurator\RendererGenerator;
use s9e\TextFormatter\Configurator\RendererGenerators\XSLT\Optimizer;
use s9e\TextFormatter\Configurator\Rendering;
use s9e\TextFormatter\Renderers\XSLT as XSLTRenderer;

class XSLT implements RendererGenerator
{
	/**
	* @var Optimizer
	*/
	public $optimizer;

	/**
	* Constructor
	*/
	public function __construct()
	{
		$this->optimizer = new Optimizer;
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
		$prefixes         = [];
		$templates        = $rendering->getTemplates();

		// Replace simple templates if there are at least 3 of them
		TemplateHelper::replaceHomogeneousTemplates($templates, 3);

		// Group tags with identical templates together
		foreach ($templates as $tagName => $template)
		{
			$template = $this->optimizer->optimizeTemplate($template);
			$groupedTemplates[$template][] = $tagName;

			// Record the tag's prefix if applicable
			$pos = strpos($tagName, ':');
			if ($pos !== false)
			{
				$prefixes[substr($tagName, 0, $pos)] = 1;
			}
		}

		// Declare all the namespaces in use at the top
		$xsl = '<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform"';

		// Append the namespace declarations to the stylesheet
		$prefixes = array_keys($prefixes);
		sort($prefixes);
		foreach ($prefixes as $prefix)
		{
			$xsl .= ' xmlns:' . $prefix . '="urn:s9e:TextFormatter:' . $prefix . '"';
		}

		/**
		* Exclude those prefixes to keep the HTML neat
		*
		* @link http://lenzconsulting.com/namespaces-in-xslt/#exclude-result-prefixes
		*/
		if (!empty($prefixes))
		{
			$xsl .= ' exclude-result-prefixes="' . implode(' ', $prefixes) . '"';
		}

		// Start the stylesheet with the boilerplate stuff
		$xsl .= '><xsl:output method="html" encoding="utf-8" indent="no"';
		$xsl .= '/>';

		// Add stylesheet parameters
		foreach ($rendering->getAllParameters() as $paramName => $paramValue)
		{
			$xsl .= '<xsl:param name="' . htmlspecialchars($paramName) . '"';

			if ($paramValue === '')
			{
				$xsl .= '/>';
			}
			else
			{
				$xsl .= '>' . htmlspecialchars($paramValue) . '</xsl:param>';
			}
		}

		// Add templates
		foreach ($groupedTemplates as $template => $tagNames)
		{
			// Open the template element
			$xsl .= '<xsl:template match="' . implode('|', $tagNames) . '"';

			// Make it a self-closing element if the template is empty
			if ($template === '')
			{
				$xsl .= '/>';
			}
			else
			{
				$xsl .= '>' . $template . '</xsl:template>';
			}
		}

		$xsl .= '</xsl:stylesheet>';

		return $xsl;
	}
}