<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator;

interface RendererGenerator
{
	/**
	* Generate and return a renderer
	*
	* @param  Rendering                   $rendering Rendering configuration
	* @return \s9e\TextFormatter\Renderer            Instance of Renderer
	*/
	public function getRenderer(Rendering $rendering);
}