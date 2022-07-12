<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2022 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\MediaEmbed\Configurator;

use DOMXPath;
use s9e\TextFormatter\Configurator\Helpers\TemplateLoader;
use s9e\TextFormatter\Plugins\MediaEmbed\Configurator\TemplateGenerators\Choose;
use s9e\TextFormatter\Plugins\MediaEmbed\Configurator\TemplateGenerators\Flash;
use s9e\TextFormatter\Plugins\MediaEmbed\Configurator\TemplateGenerators\Iframe;

class TemplateBuilder
{
	/**
	* @var array Generator names as keys, generators as values
	*/
	protected $templateGenerators = [];

	/**
	* Constructor
	*/
	public function __construct()
	{
		$this->templateGenerators['choose'] = new Choose($this);
		$this->templateGenerators['flash']  = new Flash;
		$this->templateGenerators['iframe'] = new Iframe;
	}

	/**
	* Generate and return a template for given site
	*
	* @param  string $siteId
	* @param  array  $siteConfig
	* @return string
	*/
	public function build($siteId, array $siteConfig)
	{
		return $this->addSiteId($siteId, $this->getTemplate($siteConfig));
	}

	/**
	* Generate and return a template based on given config
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
				return $generator->getTemplate($config[$type]);
			}
		}

		return '';
	}

	/**
	* Added the siteId value to given template in a data-s9e-mediaembed attribute
	*
	* @param  string $siteId   Site ID
	* @param  string $template Original template
	* @return string           Modified template
	*/
	protected function addSiteId($siteId, $template)
	{
		$dom   = TemplateLoader::load($template);
		$xpath = new DOMXPath($dom);
		$query = '//*[namespace-uri() != "' . TemplateLoader::XMLNS_XSL . '"]'
		       . '[not(ancestor::*[namespace-uri() != "' . TemplateLoader::XMLNS_XSL . '"])]';
		foreach ($xpath->query($query) as $element)
		{
			$element->setAttribute('data-s9e-mediaembed', $siteId);
		}

		return TemplateLoader::save($dom);
	}
}