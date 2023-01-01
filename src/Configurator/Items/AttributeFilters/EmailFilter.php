<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Items\AttributeFilters;

use s9e\TextFormatter\Configurator\Items\AttributeFilter;

class EmailFilter extends AttributeFilter
{
	/**
	* Constructor
	*/
	public function __construct()
	{
		parent::__construct('s9e\\TextFormatter\\Parser\\AttributeFilters\\EmailFilter::filter');
		$this->setJS('EmailFilter.filter');
	}
}