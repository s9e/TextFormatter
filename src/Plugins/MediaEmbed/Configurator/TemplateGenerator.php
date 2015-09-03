<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\MediaEmbed\Configurator;
use s9e\TextFormatter\Configurator\Helpers\AVTHelper;
abstract class TemplateGenerator
{
	abstract public function getTemplate(array $attributes);
	protected function addResponsiveStyle(array $attributes)
	{
		$css = 'position:absolute;top:0;left:0;width:100%;height:100%';
		if (isset($attributes['style']))
			$attributes['style'] .= ';' . $css;
		else
			$attributes['style'] = $css;
		return $attributes;
	}
	protected function addResponsiveWrapper($template, array $attributes)
	{
		$height = \trim($attributes['height'], '{}');
		$width  = \trim($attributes['width'], '{}');
		$isFixedHeight = (bool) \preg_match('(^\\d+$)D', $height);
		$isFixedWidth  = (bool) \preg_match('(^\\d+$)D', $width);
		if ($isFixedHeight && $isFixedWidth)
			$padding = \round(100 * $height / $width, 2);
		else
		{
			if (!\preg_match('(^[@$]?[-\\w]+$)D', $height))
				$height = '(' . $height . ')';
			if (!\preg_match('(^[@$]?[-\\w]+$)D', $width))
				$width = '(' . $width . ')';
			$padding = '<xsl:value-of select="100*' . $height . ' div'. $width . '"/>';
		}
		return '<div><xsl:attribute name="style">display:inline-block;width:100%;max-width:' . $width . 'px</xsl:attribute><div><xsl:attribute name="style">height:0;position:relative;padding-top:' . $padding . '%</xsl:attribute>' . $template . '</div></div>';
	}
	protected function canBeResponsive(array $attributes)
	{
		if (empty($attributes['responsive']))
			return \false;
		return !\preg_match('([%<])', $attributes['width'] . $attributes['height']);
	}
	protected function generateAttributes(array $attributes, $addResponsive = \false)
	{
		if ($addResponsive)
			$attributes = $this->addResponsiveStyle($attributes);
		unset($attributes['responsive']);
		$xsl = '';
		foreach ($attributes as $attrName => $innerXML)
		{
			if (\strpos($innerXML, '<') === \false)
			{
				$tokens   = AVTHelper::parse($innerXML);
				$innerXML = '';
				foreach ($tokens as $_bada9f30)
				{
					list($type, $content) = $_bada9f30;
					if ($type === 'literal')
						$innerXML .= \htmlspecialchars($content, \ENT_NOQUOTES, 'UTF-8');
					else
						$innerXML .= '<xsl:value-of select="' . \htmlspecialchars($content, \ENT_QUOTES, 'UTF-8') . '"/>';
				}
			}
			$xsl .= '<xsl:attribute name="' . \htmlspecialchars($attrName, \ENT_QUOTES, 'UTF-8') . '">' . $innerXML . '</xsl:attribute>';
		}
		return $xsl;
	}
}