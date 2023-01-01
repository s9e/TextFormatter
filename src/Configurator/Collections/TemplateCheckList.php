<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;

use s9e\TextFormatter\Configurator\TemplateCheck;

class TemplateCheckList extends NormalizedList
{
	/**
	* Normalize the value to an instance of TemplateCheck
	*
	* @param  mixed         $check Either a string, or an instance of TemplateCheck
	* @return TemplateCheck        An instance of TemplateCheck
	*/
	public function normalizeValue($check)
	{
		if (!($check instanceof TemplateCheck))
		{
			$className = 's9e\\TextFormatter\\Configurator\\TemplateChecks\\' . $check;
			$check     = new $className;
		}

		return $check;
	}
}