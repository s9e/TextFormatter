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
		$attributes['data'] = $attributes['src'];
		unset($attributes['src']);
		$isResponsive = $this->canBeResponsive($attributes);
		if ($isResponsive)
			$attributes = $this->addResponsiveStyle($attributes);
		$flashVars = (isset($attributes['flashvars'])) ? $attributes['flashvars'] : '';
		unset($attributes['flashvars']);
		$template = '<object type="application/x-shockwave-flash" typemustmatch="">';
		$template .= $this->generateAttributes($attributes, $isResponsive);
		$template .= '<param name="allowfullscreen" value="true"/>';
		if (!empty($flashVars))
		{
			$template .= '<param name="flashvars">';
			$template .= $this->generateAttributes(['value' => $flashVars]);
			$template .= '</param>';
		}
		$template .= '<embed type="application/x-shockwave-flash">';
		$attributes['src'] = $attributes['data'];
		$attributes['allowfullscreen'] = '';
		unset($attributes['data']);
		if (!empty($flashVars))
			$attributes['flashvars'] = $flashVars;
		$template .= $this->generateAttributes($attributes);
		$template .= '</embed></object>';
		if ($isResponsive)
			$template = $this->addResponsiveWrapper($template, $attributes);
		return $template;
	}
}