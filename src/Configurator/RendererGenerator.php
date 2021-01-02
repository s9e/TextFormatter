<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2021 The s9e authors
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