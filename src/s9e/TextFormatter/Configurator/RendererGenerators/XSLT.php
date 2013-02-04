<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2013 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RendererGenerators;

use s9e\TextFormatter\Configurator\RendererGenerator;
use s9e\TextFormatter\Configurator\Stylesheet;
use s9e\TextFormatter\Renderers\XSLT as XSLTRenderer;

class XSLT implements RendererGenerator
{
	/**
	* {@inheritdoc}
	*/
	public function getRenderer(Stylesheet $stylesheet)
	{
		return new XSLTRenderer($stylesheet->get());
	}
}