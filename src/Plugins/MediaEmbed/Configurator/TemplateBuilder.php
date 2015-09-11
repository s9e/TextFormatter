<?php

/**
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
	/**
	* @var bool Whether to enable responsive embeds
	*/
	public $responsiveEmbeds = false;

	/**
	* @var array Generator names as keys, generators as values
	*/
	protected $templateGenerators = [];

	/**
	* Constructor
	*
	* @return void
	*/
	public function __construct()
	{
		$this->templateGenerators['choose'] = new Choose($this);
		$this->templateGenerators['flash']  = new Flash;
		$this->templateGenerators['iframe'] = new Iframe;
	}

	/**
	* Generate and return a template for given site config
	*
	* @param  array  $config
	* @return string
	*/
	public function getTemplate(array $config)
	{
		foreach ($this->templateGenerators as $type => $generator)
		{
			if (isset($config[$type]))
			{
				$config[$type] += ['responsive' => $this->responsiveEmbeds];

				return $generator->getTemplate($config[$type]);
			}
		}

		return '';
	}
}