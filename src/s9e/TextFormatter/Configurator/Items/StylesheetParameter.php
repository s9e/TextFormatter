<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2013 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Items;

use s9e\TextFormatter\Configurator\Traits\TemplateSafeness;

abstract class StylesheetParameter
{
	use TemplateSafeness;

	/**
	* @var string This parameter's default value, expressed in XPath
	*/
	protected $value;

	/**
	* Constructor
	*
	* @param  mixed $value This parameter's default value
	* @return void
	*/
	abstract public function __construct($value = null);

	/**
	* Return this parameter's default value
	*
	* @return void
	*/
	public function __toString()
	{
		return $this->value;
	}
}