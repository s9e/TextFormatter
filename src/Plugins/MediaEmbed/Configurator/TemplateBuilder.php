<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\MediaEmbed\Configurator;
use s9e\TextFormatter\Plugins\MediaEmbed\Configurator\TemplateGenerators\Choose;
use s9e\TextFormatter\Plugins\MediaEmbed\Configurator\TemplateGenerators\Flash;
use s9e\TextFormatter\Plugins\MediaEmbed\Configurator\TemplateGenerators\Iframe;
class TemplateBuilder
{
	public $responsiveEmbeds = \false;
	protected $templateGenerators = array();
	public function __construct()
	{
		$this->templateGenerators['choose'] = new Choose($this);
		$this->templateGenerators['flash']  = new Flash;
		$this->templateGenerators['iframe'] = new Iframe;
	}
	public function getTemplate(array $config)
	{
		foreach ($this->templateGenerators as $type => $generator)
			if (isset($config[$type]))
			{
				$config[$type] += array('responsive' => $this->responsiveEmbeds);
				return $generator->getTemplate($config[$type]);
			}
		return '';
	}
}