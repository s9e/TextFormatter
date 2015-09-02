<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\MediaEmbed\Configurator\TemplateGenerators;

use s9e\TextFormatter\Plugins\MediaEmbed\Configurator\TemplateGenerator;

class Iframe extends TemplateGenerator
{
	/**
	* {@inheritdoc}
	*/
	public function getTemplate(array $attributes)
	{
		// Add the default attributes
		$attributes += [
			'allowfullscreen' => '',
			'frameborder'     => '0',
			'scrolling'       => 'no'
		];

		// Build the template
		$isResponsive = $this->canBeResponsive($attributes);
		$template = '<iframe>' . $this->generateAttributes($attributes, $isResponsive) . '</iframe>';

		if ($isResponsive)
		{
			$template = $this->addResponsiveWrapper($template, $attributes);
		}

		return $template;
	}
}