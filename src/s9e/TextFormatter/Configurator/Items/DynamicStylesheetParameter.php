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
	* @param  mixed $expr This parameter's default XPath expression
	* @return void
	*/
	public function __construct($expr = null)
	{
		$this->expr = TemplateHelper::minifyXPath($expr);
	}
}