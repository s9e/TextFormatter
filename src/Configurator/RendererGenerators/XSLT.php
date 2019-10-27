<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RendererGenerators;
use s9e\TextFormatter\Configurator\Helpers\TemplateHelper;
use s9e\TextFormatter\Configurator\RendererGenerator;
use s9e\TextFormatter\Configurator\RendererGenerators\XSLT\Optimizer;
use s9e\TextFormatter\Configurator\Rendering;
use s9e\TextFormatter\Configurator\TemplateNormalizer;
use s9e\TextFormatter\Renderers\XSLT as XSLTRenderer;
class XSLT implements RendererGenerator
{
	public $normalizer;
	public function __construct()
	{
		$this->normalizer = new TemplateNormalizer([
			'MergeConsecutiveCopyOf',
			'MergeIdenticalConditionalBranches',
			'OptimizeNestedConditionals',
			'RemoveLivePreviewAttributes'
		]);
	}
	public function getRenderer(Rendering $rendering)
	{
		return new XSLTRenderer($this->getXSL($rendering));
	}
	public function getXSL(Rendering $rendering)
	{
		$groupedTemplates = [];
		$templates        = $rendering->getTemplates();
		TemplateHelper::replaceHomogeneousTemplates($templates, 3);
		foreach ($templates as $tagName => $template)
		{
			$template = $this->normalizer->normalizeTemplate($template);
			$groupedTemplates[$template][] = $tagName;
		}
		$xsl = '<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform"';
		$prefixes = $this->getPrefixes(\array_keys($templates));
		if (!empty($prefixes))
		{
			foreach ($prefixes as $prefix)
				$xsl .= ' xmlns:' . $prefix . '="urn:s9e:TextFormatter:' . $prefix . '"';
			$xsl .= ' exclude-result-prefixes="' . \implode(' ', $prefixes) . '"';
		}
		$xsl .= '><xsl:output method="html" encoding="utf-8" indent="no"/>';
		foreach ($rendering->getAllParameters() as $paramName => $paramValue)
			$xsl .= $this->writeTag('xsl:param', ['name' => $paramName], \htmlspecialchars($paramValue, \ENT_NOQUOTES));
		foreach ($groupedTemplates as $template => $tagNames)
			$xsl .= $this->writeTag('xsl:template', ['match' => \implode('|', $tagNames)], $template);
		$xsl .= '</xsl:stylesheet>';
		return $xsl;
	}
	protected function getPrefixes(array $tagNames)
	{
		$prefixes = [];
		foreach ($tagNames as $tagName)
		{
			$pos = \strpos($tagName, ':');
			if ($pos !== \false)
				$prefixes[\substr($tagName, 0, $pos)] = 1;
		}
		$prefixes = \array_keys($prefixes);
		\sort($prefixes);
		return $prefixes;
	}
	protected function writeTag($tagName, array $attributes, $content)
	{
		$xml = '<' . $tagName;
		foreach ($attributes as $attrName => $attrValue)
			$xml .= ' ' . $attrName . '="' . \htmlspecialchars($attrValue) . '"';
		if ($content === '')
			$xml .= '/>';
		else
			$xml .= '>' . $content . '</' . $tagName . '>';
		return $xml;
	}
}