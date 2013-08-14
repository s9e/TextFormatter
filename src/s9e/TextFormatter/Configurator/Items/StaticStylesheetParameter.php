<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2013 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Items;

use s9e\TextFormatter\Configurator\Helpers\TemplateHelper;

class StaticStylesheetParameter extends StylesheetParameter
{
	/**
	* @var string Literal value of this parameter
	*/
	protected $value;

	/**
	* Constructor
	*
	* @param  mixed $value This parameter's default value as a literal
	* @return void
	*/
	public function __construct($value = null)
	{
		$this->expr  = TemplateHelper::asXPath($value);
		$this->value = (string) $value;
	}

	/**
	* Return the literal value of this parameter
	*
	* @return string
	*/
	public function getValue()
	{
		return $this->value;
	}
}