<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Items\AttributeFilters;

use s9e\TextFormatter\Configurator\Items\AttributeFilter;

class UintFilter extends AttributeFilter
{
	/**
	* Constructor
	*/
	public function __construct()
	{
		parent::__construct('s9e\\TextFormatter\\Parser\\AttributeFilters\\NumericFilter::filterUint');
		$this->setJS('NumericFilter.filterUint');
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
	public function isSafeInJS()
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