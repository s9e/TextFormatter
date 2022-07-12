<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2022 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Items\AttributeFilters;

use s9e\TextFormatter\Configurator\Items\AttributeFilter;

class FalseFilter extends AttributeFilter
{
	/**
	* Constructor
	*/
	public function __construct()
	{
		parent::__construct('s9e\\TextFormatter\\Parser\\AttributeFilters\\FalseFilter::filter');
		$this->setJS('FalseFilter.filter');
	}
}