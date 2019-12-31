<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2020 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Renderers;

use s9e\TextFormatter\Renderer;

/**
* This renderer returns a plain text version of rich text. It is meant to be used as a last resort
* when every other renderer is unavailable
*/
class Unformatted extends Renderer
{
	/**
	* {@inheritdoc}
	*/
	protected function renderRichText($xml)
	{
		return str_replace("\n", "<br>\n", htmlspecialchars(strip_tags($xml), ENT_COMPAT, 'UTF-8', false));
	}
}