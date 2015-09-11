<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\MediaEmbed\Configurator\TemplateGenerators;

use s9e\TextFormatter\Plugins\MediaEmbed\Configurator\TemplateBuilder;
use s9e\TextFormatter\Plugins\MediaEmbed\Configurator\TemplateGenerator;

class Choose extends TemplateGenerator
{
	/**
	* @var TemplateBuilder
	*/
	protected $templateBuilder;

	/**
	* Constructor
	*
	* @param  TemplateBuilder $templateBuilder
	* @return void
	*/
	public function __construct(TemplateBuilder $templateBuilder)
	{
		$this->templateBuilder = $templateBuilder;
	}

	/**
	* {@inheritdoc}
	*/
	public function getTemplate(array $attributes)
	{
		$branches = (isset($attributes['when'][0])) ? $attributes['when'] : [$attributes['when']];
		$template = '<xsl:choose>';
		foreach ($branches as $when)
		{
			$template .= '<xsl:when test="' . htmlspecialchars($when['test'], ENT_COMPAT, 'UTF-8') . '">' . $this->templateBuilder->getTemplate($when) . '</xsl:when>';
		}
		$template .= '<xsl:otherwise>' . $this->templateBuilder->getTemplate($attributes['otherwise']) . '</xsl:otherwise></xsl:choose>';

		return $template;
	}
}