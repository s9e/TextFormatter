<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Items\AttributeFilters;

class AlnumFilter extends RegexpFilter
{
	/**
	* Constructor
	*/
	public function __construct()
	{
		parent::__construct('/^[0-9A-Za-z]+$/D');
		$this->markAsSafeAsURL();
		$this->markAsSafeInCSS();
	}
}