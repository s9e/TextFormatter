<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2013 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator;

interface RendererGenerator
{
	/**
	* Generate and return a renderer
	*
	* @param  Stylesheet $stylesheet Stylesheet used for rendering
	* @return Renderer               Instance of Renderer
	*/
	public function getRenderer(Stylesheet $stylesheet);
}