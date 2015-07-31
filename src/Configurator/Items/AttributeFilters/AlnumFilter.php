<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Items\AttributeFilters;

use s9e\TextFormatter\Configurator\Items\AttributeFilter;

class AlnumFilter extends AttributeFilter
{
	/**
	* Constructor
	*
	* @return void
	*/
	public function __construct()
	{
		parent::__construct('s9e\\TextFormatter\\Parser\\BuiltInFilters::filterAlnum');

		$this->addParameterByName('attrValue');
		$this->setJS('BuiltInFilters.filterAlnum');
	}

	/**
	* {@inheritdoc}
	*/
	public function isSafeInCSS()
	{
		return true;
	}

	/**
	* {@inheritdoc}
	*/
	public function isSafeAsURL()
	{
		return true;
	}
}