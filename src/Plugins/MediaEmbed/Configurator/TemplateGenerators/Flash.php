<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\MediaEmbed\Configurator\TemplateGenerators;

use s9e\TextFormatter\Plugins\MediaEmbed\Configurator\TemplateGenerator;

class Flash extends TemplateGenerator
{
	/**
	* {@inheritdoc}
	*/
	public function getTemplate(array $attributes)
	{
		$isResponsive = $this->canBeResponsive($attributes);
		if ($isResponsive)
		{
			$attributes = $this->addResponsiveStyle($attributes);
		}

		$template = $this->generateObjectStartTag($attributes, $isResponsive) . $this->generateEmbedElement($attributes) . '</object>';
		if ($isResponsive)
		{
			$template = $this->addResponsiveWrapper($template, $attributes);
		}

		return $template;
	}

	/**
	* Generate a complete embed element
	*
	* @param  array $attributes
	* @return string
	*/
	protected function generateEmbedElement(array $attributes)
	{
		$attributes['allowfullscreen'] = '';

		return '<embed type="application/x-shockwave-flash">' . $this->generateAttributes($attributes) . '</embed>';
	}

	/**
	* Generate the start tag of an object element
	*
	* @link http://www.whatwg.org/specs/web-apps/current-work/multipage/the-iframe-element.html#the-object-element
	* @link http://helpx.adobe.com/flash/kb/pass-variables-swfs-flashvars.html
	*
	* @param  array  $attributes
	* @param  bool   $isResponsive
	* @return string
	*/
	protected function generateObjectStartTag(array $attributes, $isResponsive)
	{
		$attributes['data']          = $attributes['src'];
		$attributes['type']          = 'application/x-shockwave-flash';
		$attributes['typemustmatch'] = '';
		unset($attributes['src']);

		$flashVarsParam = '';
		if (isset($attributes['flashvars']))
		{
			$flashVarsParam = $this->generateParamElement('flashvars', $attributes['flashvars']);
			unset($attributes['flashvars']);
		}

		$template = '<object type="application/x-shockwave-flash" typemustmatch="">'
		          . $this->generateAttributes($attributes)
		          . $this->generateParamElement('allowfullscreen', 'true')
		          . $flashVarsParam;

		return $template;
	}

	/**
	* Generate a param element to be used inside of an object element
	*
	* @param  string $paramName
	* @param  string $paramValue
	* @return string
	*/
	protected function generateParamElement($paramName, $paramValue)
	{
		return '<param name="' . htmlspecialchars($paramName) . '">' . $this->generateAttributes(['value' => $paramValue]) . '</param>';
	}
}