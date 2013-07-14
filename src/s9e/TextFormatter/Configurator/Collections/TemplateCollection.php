<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2013 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;

use InvalidArgumentException;
use s9e\TextFormatter\Configurator\Helpers\TemplateHelper;
use s9e\TextFormatter\Configurator\Items\Template;
use s9e\TextFormatter\Configurator\TemplateNormalizer;

class TemplateCollection extends NormalizedCollection
{
	/**
	* Normalize a template for storage
	*
	* @param  mixed    $template Either a string or an instance of Template
	* @return Template           An instance of Template
	*/
	public function normalizeValue($template)
	{
		return ($template instanceof Template)
		     ? $template
		     : new Template($template);
	}
}