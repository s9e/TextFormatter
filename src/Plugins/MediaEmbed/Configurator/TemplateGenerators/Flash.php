<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\MediaEmbed\Configurator\TemplateGenerators;

use s9e\TextFormatter\Plugins\MediaEmbed\Configurator\TemplateGenerator;

class Flash extends TemplateGenerator
{
	/**
	* {@inheritdoc}
	*
	* @link http://www.whatwg.org/specs/web-apps/current-work/multipage/the-iframe-element.html#the-object-element
	* @link http://helpx.adobe.com/flash/kb/pass-variables-swfs-flashvars.html
	*/
	protected function getContentTemplate()
	{
		$attributes = [
			'data'          => $this->attributes['src'],
			'style'         => $this->attributes['style'],
			'type'          => 'application/x-shockwave-flash',
			'typemustmatch' => ''
		];

		$flashVarsParam = '';
		if (isset($this->attributes['flashvars']))
		{
			$flashVarsParam = $this->generateParamElement('flashvars', $this->attributes['flashvars']);
		}

		$template = '<object>'
		          . $this->generateAttributes($attributes)
		          . $this->generateParamElement('allowfullscreen', 'true')
		          . $flashVarsParam
		          . '</object>';

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