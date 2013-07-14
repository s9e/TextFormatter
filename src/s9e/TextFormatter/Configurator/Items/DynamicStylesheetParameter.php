<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2013 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Items;

use s9e\TextFormatter\Configurator\Helpers\TemplateHelper;

class DynamicStylesheetParameter extends StylesheetParameter
{
	/**
	* Constructor
	*
	* @param  mixed $value This parameter's default value
	* @return void
	*/
	public function __construct($value = null)
	{
		$this->value = TemplateHelper::minifyXPath($value);
	}
}