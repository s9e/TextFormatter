<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2022 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\MediaEmbed\Configurator\TemplateGenerators;

use s9e\TextFormatter\Plugins\MediaEmbed\Configurator\TemplateGenerator;

class Iframe extends TemplateGenerator
{
	/**
	* @var array Default iframe attributes
	*/
	protected $defaultIframeAttributes = [
		'allowfullscreen' => '',
		'loading'         => 'lazy',
		'scrolling'       => 'no',
		'style'           => ['border' => '0']
	];

	/**
	* @var string[] List of attributes to be passed to the iframe
	*/
	protected $iframeAttributes = ['allow', 'data-s9e-livepreview-ignore-attrs', 'data-s9e-livepreview-onrender', 'onload', 'scrolling', 'src', 'style'];

	/**
	* {@inheritdoc}
	*/
	protected function getContentTemplate()
	{
		$attributes = $this->mergeAttributes($this->defaultIframeAttributes, $this->getFilteredAttributes());

		return '<iframe>' . $this->generateAttributes($attributes) . '</iframe>';
	}

	/**
	* Filter the attributes to keep only those that can be used in an iframe
	*
	* @return array
	*/
	protected function getFilteredAttributes()
	{
		return array_intersect_key($this->attributes, array_flip($this->iframeAttributes));
	}
}