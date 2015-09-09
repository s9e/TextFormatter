<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\MediaEmbed\Configurator;

use s9e\TextFormatter\Configurator\Helpers\AVTHelper;

abstract class TemplateGenerator
{
	/**
	* Build a template based on a list of attributes
	*
	* @param  array  $attributes
	* @return string
	*/
	abstract public function getTemplate(array $attributes);

	/**
	* Add the attributes required for responsive embeds
	*
	* @param  array $attributes Array of [name => value] where value can be XSL code
	* @return array             Modified attributes
	*/
	protected function addResponsiveStyle(array $attributes)
	{
		$css = 'position:absolute;top:0;left:0;width:100%;height:100%';
		if (isset($attributes['style']))
		{
			$attributes['style'] .= ';' . $css;
		}
		else
		{
			$attributes['style'] = $css;
		}

		return $attributes;
	}

	/**
	* Add the attributes required for responsive embeds
	*
	* @param  string $template   Original template
	* @param  array  $attributes Array of [name => value] where value can be XSL code
	* @return string             Modified template
	*/
	protected function addResponsiveWrapper($template, array $attributes)
	{
		// Remove braces from the values
		$height = trim($attributes['height'], '{}');
		$width  = trim($attributes['width'], '{}');

		$isFixedHeight = (bool) preg_match('(^\\d+$)D', $height);
		$isFixedWidth  = (bool) preg_match('(^\\d+$)D', $width);

		if ($isFixedHeight && $isFixedWidth)
		{
			$padding = round(100 * $height / $width, 2);
		}
		else
		{
			if (!preg_match('(^[@$]?[-\\w]+$)D', $height))
			{
				$height = '(' . $height . ')';
			}
			if (!preg_match('(^[@$]?[-\\w]+$)D', $width))
			{
				$width = '(' . $width . ')';
			}

			$padding = '<xsl:value-of select="100*' . $height . ' div'. $width . '"/>';
		}

		return '<div><xsl:attribute name="style">display:inline-block;width:100%;max-width:' . $width . 'px</xsl:attribute><div><xsl:attribute name="style">height:0;position:relative;padding-top:' . $padding . '%</xsl:attribute>' . $template . '</div></div>';
	}

	/**
	* Test whether given dimensions can be made repsonsive
	*
	* @param  array $attributes Array of [name => value] where value can be XSL code
	* @return bool
	*/
	protected function canBeResponsive(array $attributes)
	{
		if (empty($attributes['responsive']))
		{
			return false;
		}

		// Cannot be responsive if dimensions contain a percentage of an XSL element
		return !preg_match('([%<])', $attributes['width'] . $attributes['height']);
	}

	/**
	* Generate xsl:attributes elements from an array
	*
	* @param  array  $attributes    Array of [name => value] where value can be XSL code
	* @param  bool   $addResponsive Whether to add the responsive style attributes
	* @return string                XSL source
	*/
	protected function generateAttributes(array $attributes, $addResponsive = false)
	{
		if ($addResponsive)
		{
			$attributes = $this->addResponsiveStyle($attributes);
		}

		unset($attributes['responsive']);

		$xsl = '';
		foreach ($attributes as $attrName => $innerXML)
		{
			if (strpos($innerXML, '<') === false)
			{
				$innerXML = AVTHelper::toXSL($innerXML);
			}

			$xsl .= '<xsl:attribute name="' . htmlspecialchars($attrName, ENT_QUOTES, 'UTF-8') . '">' . $innerXML . '</xsl:attribute>';
		}

		return $xsl;
	}
}