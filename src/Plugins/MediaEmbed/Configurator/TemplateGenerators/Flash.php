<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\MediaEmbed\Configurator\TemplateGenerators;
use s9e\TextFormatter\Plugins\MediaEmbed\Configurator\TemplateGenerator;
class Flash extends TemplateGenerator
{
	public function getTemplate(array $attributes)
	{
		$isResponsive = $this->canBeResponsive($attributes);
		if ($isResponsive)
			$attributes = $this->addResponsiveStyle($attributes);
		$template = $this->generateObjectElement($attributes, $isResponsive);
		if ($isResponsive)
			$template = $this->addResponsiveWrapper($template, $attributes);
		return $template;
	}
	protected function generateObjectElement(array $attributes, $isResponsive)
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
		          . $this->generateAttributes($attributes, $isResponsive)
		          . $this->generateParamElement('allowfullscreen', 'true')
		          . $flashVarsParam
		          . '</object>';
		return $template;
	}
	protected function generateParamElement($paramName, $paramValue)
	{
		return '<param name="' . \htmlspecialchars($paramName) . '">' . $this->generateAttributes(['value' => $paramValue]) . '</param>';
	}
}