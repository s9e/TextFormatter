<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2022 The s9e authors
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
	*/
	public function __construct(TemplateBuilder $templateBuilder)
	{
		$this->templateBuilder = $templateBuilder;
	}

	/**
	* {@inheritdoc}
	*/
	protected function needsWrapper()
	{
		return false;
	}

	/**
	* {@inheritdoc}
	*/
	protected function getContentTemplate()
	{
		$branches = (isset($this->attributes['when'][0])) ? $this->attributes['when'] : [$this->attributes['when']];
		$template = '<xsl:choose>';
		foreach ($branches as $when)
		{
			$template .= '<xsl:when test="' . htmlspecialchars($when['test'], ENT_COMPAT, 'UTF-8') . '">' . $this->templateBuilder->getTemplate($when) . '</xsl:when>';
		}
		$template .= '<xsl:otherwise>' . $this->templateBuilder->getTemplate($this->attributes['otherwise']) . '</xsl:otherwise></xsl:choose>';

		return $template;
	}
}